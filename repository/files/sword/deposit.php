<?php
// --------------------------------------------------------------------
//
// $Id: deposit.php 42307 2014-09-29 06:18:07Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
// --------------------------------------------------------------------
//
// PURPOSE:
//
// This handler manages deposits made through SWORD.
//
// --------------------------------------------------------------------

$isAddDateToLogName = false;     // false: Log name without date
                                //  true: Log name with date

// --------------------------------------------------------------
// Initialize
// --------------------------------------------------------------
//error_reporting(E_ALL);
session_start();
// path transform
function transPathSeparator($path) {
    if ( DIRECTORY_SEPARATOR != '/' ) {
        // IIS6 doubles the \ chars
        $path = str_replace( strpos( $path, '\\\\', 2 ) ? '\\\\' : DIRECTORY_SEPARATOR, '/', $path);
    }
    return $path;
}
// NetCommons, index.php
define('START_INDEX_DIR', dirname(dirname(transPathSeparator(dirname(__FILE__)))));
// NetCommons, load config
define('INSTALLINC_PATH', dirname(START_INDEX_DIR) . "/webapp/config/install.inc.php");
require_once INSTALLINC_PATH;
// include  utility functions for SWORD
require_once(HTDOCS_DIR . "/weko/sword/utils.php");
//header("Content-Type: application/atomsvc+xml;charset=\"utf-8\"");

// --------------------------------------------------------------
// TestCode : Dump request params and headers
// --------------------------------------------------------------
if($isAddDateToLogName){
    $logName = WEBAPP_DIR."/logs/weko/sword/deposit_log_".date("YmdHis").".txt";
}else{
    $logName = WEBAPP_DIR."/logs/weko/sword/deposit_log.txt";
}
$fh = fopen($logName, "w");
chmod($logName, 0600);
//fwrite($fh, "check 1\n");     // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 1: Created log file.\n");
# TestCode : Check and Dump Request Headers to Server Text.
foreach($_SERVER as $key => $server_params) fwrite($fh, "\$_SERVER['" . $key . "'] = " . $server_params ."\n");
//foreach($_POST as $key => $post_params) fwrite($fh, "\$_POST['" . $key . "'] = " . $post_params ."\n");

// --------------------------------------------------------------
// Authorization
// --------------------------------------------------------------
$response_auth = authenticate($_SESSION,$_SERVER);
# TestCode : Check and Dump Authorize Rsoponse to Server Text.
foreach($response_auth as $key => $resparams) fwrite($fh, "['" . $key . "'] = " . $resparams . "\n");
//fwrite($fh, "check 2\n");     // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 2: Before check authorization.\n");
// $response['status_code']defined means there was an authentication error
if( isset($response_auth['status_code'])){
    header("Unauthorized", true, 401);
    header("WWW-Authenticate: Basic realm=\"SWORD\"");
    header("X-Error-Code: " . $response_auth['x_error_code']);
    session_destroy();
    fwrite($fh, "Check Point 3-B: Status code is '".$response_auth['status_code']."'.\n");
    fclose($fh);
    exit;
}
fwrite($fh, "Check Point 3-A: No error at authorization.\n");
// authrize succeed.
// Login ID of Client
if(isset($response_auth["login_id"])){
	$userID = $response_auth["login_id"];
}else{
	$userID = "";
}
// X-On-Behalf-Of (if Exists)
if(isset($response_auth["owner"])){
	$owner =$response_auth["owner"];
}else{
	$owner ="";
}
// E-mail address of Client
if(isset($response_auth["login_user_email"])){
	$login_user_email=$response_auth["login_user_email"];
}else{
	$login_user_email="";
}
// E-mail address of Owner
if(isset($response_auth["owner_email"])){
	$owner_email=$response_auth["owner_email"];
}else{
	$owner_email="";
}
// --------------------------------------------------------------
// Get Service Document
// --------------------------------------------------------------
// this collection is "Repository Review". get Service Document from NC2.
$svc_option = array(
    "timeout" => "10",
    "allowRedirects" => true,
    "maxRedirects" => 3,
);
// Add 2009/03/17 Y.Nakao --start--
$svc_url = '?action=repository_action_main_sword_servicedocument&index_flg=false';
if(!empty( $_SESSION['SWORD_PATH_HTDOCS'] )){
    $svc_url = $_SESSION['SWORD_PATH_HTDOCS'].$svc_url;
    fwrite($fh, "read config : ".$_SESSION['SWORD_PATH_HTDOCS']."\n");
} else {
    $svc_url = BASE_URL.'/index.php'.$svc_url;      // Modify Directory specification K.Matsuo 2011/9/2
}
// Add 2009/03/17 Y.Nakao --start--
fwrite($fh, "Check Point 4: Before http request to '".$svc_url."' for get servicedocument.\n");
$svc_req = new HTTP_Request($svc_url, $svc_option);
if(isset($_SERVER['HTTP_USER_AGENT'])){
	$svc_req->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); // setting HTTP header
}
if(isset($_SERVER['HTTP_REFERER'])){
	$svc_req->addHeader("Referer", $_SERVER['HTTP_REFERER']);
}
$svc_req->setMethod(HTTP_REQUEST_METHOD_GET);
$svc_res = $svc_req->sendRequest();
$svc_body = $svc_req->getResponseBody();// ResponseBody(レスポンステキスト)を取得
$parser = xml_parser_create();
xml_parse_into_struct($parser, $svc_body, $vals, $index);
xml_parser_free($parser);
fwrite($fh, "Check Point 5: Parsed servicedocument.\n");
# TestCode : Check and Dump ServiceDocument to Server Text.
//foreach($vals as $key => $resparams) fwrite($fh, "['" . $key . "'] = " . $resparams['tag'] . "/" .$resparams['value'] . "/" .$resparams['level']."\n");
//foreach($vals as $key => $resparams) {
//  fwrite($fh, "['" . $key . "']\n");
//  foreach($resparams as $key2 => $resparams2) fwrite($fh, "   ['" . $key2  . "'] = " . $resparams2 . "\n");
//}
//collection hrefがパースできていない？

// --------------------------------------------------------------
// Check header parameters
// --------------------------------------------------------------

# Check that the collection exists on this repository:
$collection_cnt = -1;
$ismatch = false;
for( $ii=0; $ii<count($index['ATOM:TITLE']); $ii++ ) {
    // atom:title level3 => Repository Name
    // atom:title level4 => Collection Name
    if($vals[$index['ATOM:TITLE'][$ii]]['level'] == 4) {
        $collection_cnt++;
        if($vals[$index['ATOM:TITLE'][$ii]]['value'] == 'Repository Review') {
            $ismatch = true;
            break;
        }
    }
}
if($ismatch == false) {
    # NOTE: 'UnknownCollection' is not specified by SWORD
    header("HTTP/1.1 400 Bad Request");
    header("X-Error-Code: UnknownCollection");
    session_destroy();
    fwrite($fh, "Check Point 6-B: No match Collection Name.\n");
    fclose($fh);
    exit;
}
    fwrite($fh, "Check Point 6-A: Matched Collection Name.\n");

//fwrite($fh, "check 3\n");
# Processing HTTP headers in order to retrieve SWORD options
# リクエストヘッダの内容がapp要件を満たしていない場合はbad requestとする。
# ただしsword level1コンプライアンスを満たしているかの検査は行わない
# => sword levelの概念はSWORD V1.3からなくなりました
$header_params = process_headers($_SERVER);
//fwrite($fh, "check 4\n");
fwrite($fh, "Check Point 7: Before check request to SWORD.\n");
# status_code is set when there is an error
if( isset($header_params['status_code'])){
    // SWORD request error
    fwrite($fh, "Check Point 8-B: SWORD request error");
    if( isset($header_params['x_error_code'])){
        //fwrite($fh, "check 4-1\n");
        fwrite($fh, " / X-Error-Code: " . $header_params['x_error_code']);
        header("X-Error-Code: " . $header_params['x_error_code']);
    }
    if( $header_params['status_code'] == 401 ){
        //fwrite($fh, "check 4-2\n");
        fwrite($fh, " / status_code: 401");
        header("Unauthorized", true, 401);
        header("WWW-Authenticate: Basic realm=\"SWORD\"");
    } else {
        //fwrite($fh, "check 4-3\n");
        fwrite($fh, " / status_code: ".$header_params['status_code']);
        header("Bad Request", true, 400);
    }
    session_destroy();
    fwrite($fh, "\n");
    fclose($fh);
    exit;
}
fwrite($fh, "Check Point 8-A: No error at SWORD request.\n");

fwrite($fh, "contentType : ".$header_params['contentType']."\n");
//fwrite($fh, "check 5\n");
#  send key name is modified. Added Prefix "HTTP_", 'abc' to 'ABC', and '-' to '_'
# exsample, "X-Format-Namespace" modified to "HTTP_X_FORMAT_NAMESPACE"
# owner & depositor information
if(isset($owner) && $owner!='' ) {      // owner != depositor
    $header_params['owner']              = $owner;
    $header_params['depositor']          = $userID;
    $header_params['owner_email']        = $owner_email;                // owner E-mail
    $header_params['depositor_email']    = $login_user_email;           // depositor E-mail
} else {                                    // owner == depositor
    $header_params['owner']              = $userID;
    $header_params['owner_email']        = $login_user_email;           // owner = depositor E-mail
}
$header_params['owner'] = rawurlencode($header_params['owner']);
# other information
$header_params['version']            = $vals[$index['SWORD:VERSION'][0]]['value'];                  // server version
$header_params['sword_treatment']    = $vals[$index['SWORD:TREATMENT'][$collection_cnt]]['value'];  // treatment (depend on collection)
$header_params['collectionName']     = 'Repository Review';             // collection name (depend on collection)
$header_params['allow_mediation']    = $vals[$index['SWORD:MEDIATION'][$collection_cnt]]['value'];  // mediation allowed (depend on collection)
$header_params['update']             = date("Y-m-dTH:i:sZ");

# Unless this is disabled in the conf:
fwrite($fh, "Check Point 9: Before check allow_mediation.\n");
if( isset($header_params['depositor']) && $header_params['allow_mediation']=='false' ) {
    # Mediation not allowed, but depositor exists. => Error
    header("HTTP/1.1 403 Forbidden");
    header("X-Error-Code: MediationNotAllowed");
    session_destroy();
    fwrite($fh, "Check Point 10-B: Mediation not allowed, but depositor exists.\n");
    fclose($fh);
    exit;
}
fwrite($fh, "Check Point 10-A: Before allow_mediation check OK.\n");

// --------------------------------------------------------------
// Check POSTed contents
// --------------------------------------------------------------
# Saving the data/file sent through POST
// 以下、後処理アクションに要求を出し、そちらで必要に応じてインデックス作成＆インポート
//mb_http_output ( 'UTF-8' );       // <= 2008/11/26 Linuxだと落ちるぞ
//fwrite($fh, "check\n");       // 2008/11/12 kawa check point.
// ファイルデータの取得
// economize memory 2009/01/08 A.Suzuki --start--
//$requestBody = file_get_contents("php://input");
fwrite($fh, "Check Point 11: Before get file data by 'php://input'.\n");
$getdata = fopen("php://input", "rb");
if(fread($getdata, 1) != ""){   // ファイルの先頭を読み込み有無を判断
    $requestBody = true;
}else{
    $requestBody = false;
}
fclose($getdata);
// economize memory 2009/01/08 A.Suzuki --end--
//fwrite($fh, "check\n");       // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 12: After get file data. RequestBody flag is '".$requestBody."'\n");
//$newIndex = null;
$base_name = null;
$server_file = '';      // Posted item path on server

// Fix multi-form upload 2010/01/18 --start--
// when request has body, body is upload files.
if(isset($_FILES['file'])){
	$file=$_FILES['file'];
}
if ($requestBody != false && !isset($file)){
	//fwrite($fh, "check 7-1\n");       // 2008/11/12 kawa check point.
    fwrite($fh, "Check Point 13-A1: Get upload file.\n");

    $base_name = $header_params['contentDisposition'];  // POST file name
    $server_file = "";
    if($header_params['contentType'] == "text/xml"){
        $server_file = dirname(START_INDEX_DIR)."/webapp/uploads/repository/". $base_name.".xml";
    } else {
        $server_file = dirname(START_INDEX_DIR)."/webapp/uploads/repository/". $base_name.".zip";
    }
    // economize memory 2009/01/08 A.Suzuki --start--
    //file_put_contents( $server_file, $requestBody );
    $putdata = fopen("php://input", "rb");
    $fp = fopen($server_file, "w");
    while($data = fread($putdata, 1024)){   // 1MBずつ追記する
        fwrite($fp, $data);
    }
    fclose($fp);
    fclose($putdata);
    // economize memory 2009/01/08 A.Suzuki --end--
    // if both "Insert_Index" and "New_Index" are null, create new index "import_TIMESTUMP"
    if(isset($header_params['insert_index'])) {
        $checkedIds = $header_params['insert_index'];
    }
    if(isset($header_params['new_index'])) {
        $newIndex  = $header_params['new_index'];
    }
    // if both "Insert_Index" and "New_Index" are null, create new index "import_TIMESTAMP"
    if(!isset($header_params['insert_index']) && !isset($header_params['new_index'])){
        $newIndex = "-1,import-" . $header_params['update'];        // '-1' means "root/import" index.
    }
    $base_name = rawurlencode($base_name);
} else if(is_array($_FILES) && count($_FILES) > 0){
	//fwrite($fh, "check 7-2\n");
    fwrite($fh, "Check Point 13-B1: Get file by parameter.\n");

    foreach($file as $key => $params) fwrite($fh, "\$_FILES['" . $key . "'] = " . $params ."\n");

    $tmp_size = sprintf("%u", filesize($file['tmp_name']));
    $content_size = 0;
    if(isset($_SERVER["HTTP_CONTENT_LENGTH"]))
    {
        $content_size = sprintf("%u", $_SERVER["HTTP_CONTENT_LENGTH"]);
    }
    else if(isset($_SERVER["CONTENT_LENGTH"]))
    {
        $content_size = $_SERVER["CONTENT_LENGTH"];
    }
    $upload_size = sprintf("%u", $header_params["contentLength"]);
    fwrite($fh, "\$file['tmp_name']".$tmp_size."\n");
    fwrite($fh, "\$_SERVER['HTTP_CONTENT_LENGTH']".$content_size."\n");
    fwrite($fh, "\$header_params['contentLength']".$upload_size."\n");

    if(!file_exists($file['tmp_name'])){
        // upload size shortage.
        header("HTTP/1.1 400 Bad Request");
        header("X-Error-Code: Bad Request");
        fwrite($fh, "400 Bad Request. pattern 1.\n");
        session_destroy();
        fclose($fh);
        exit;
    }

    // HTMLのフォーム、自作SWORDクライアントからPOSTされた場合
    if ( !isset( $file['name'] )) {
        // リクエストパラメタが足りない
        header("HTTP/1.1 400 Bad Request");
        header("X-Error-Code: Bad Request");
        fwrite($fh, "400 Bad Request. pattern 2.\n");
        session_destroy();
        fclose($fh);
        exit;
    }

    // if both "Insert_Index" and "New_Index" are null, create new index "import_TIMESTUMP"
    if(isset($header_params['insert_index'])) {
        $checkedIds = $header_params['insert_index'];
    } else if(isset($_POST['checkedIds'])){
        $checkedIds = $_POST['checkedIds'];
    }
    if(isset($header_params['new_index'])) {
        $newIndex = $header_params['new_index'];
    } else if(isset($_POST['newIndex'])){
        $newIndex = $_POST['newIndex'];
    }
    // if both "Insert_Index" and "New_Index" are null, create new index "import_TIMESTAMP"
    if(!isset($header_params['insert_index']) && !isset($header_params['new_index'])){
        $newIndex = "-1,import-" . $header_params['update'];        // '-1' means "root/import" index.
    }

    if($header_params['contentType'] == 'text/xml'){
        // XML
        $data = pathinfo( $file['name'] );
        if ( $data['extension'] != 'xml' )
        {
            // not XML
            session_destroy();
            header("HTTP/1.1 415 Unsupported Media Type");
            header("X-Error-Code: Unsupported Media Type");
            fclose($fh);
            exit;
        }
        $uploaddir = dirname(START_INDEX_DIR).'/webapp/uploads/repository/';
        $base_name = basename($file['name'], '.xml' );
        $base_name = rawurlencode($base_name);
        $uploadfile = $uploaddir . $file['name'];
        fwrite($fh, "base_name : " . $base_name ."\n");     // 2008/11/12 kawa check point.
        fwrite($fh, "uploadfile : " . $uploadfile ."\n");       // 2008/11/12 kawa check point.
        $movestat = move_uploaded_file($file['tmp_name'], $uploadfile);
        $server_file = $uploadfile;
    } else {
        // ZIP
        $data = pathinfo( $file['name'] );
        if ( $data['extension'] != 'zip' )
        {
            // Not zip
            session_destroy();
            header("HTTP/1.1 415 Unsupported Media Type");
            header("X-Error-Code: Unsupported Media Type");
            fclose($fh);
            exit;
        }
        // BugFix single cotation Y.Nakao 2013/06/07 --start--
        $file['name'] = preg_replace("/^\"|^\'/", "", $file['name']);
        $file['name'] = preg_replace("/\"$|\'$/", "", $file['name']);
        // BugFix single cotation Y.Nakao 2013/06/07 --end--

        $uploaddir = dirname(START_INDEX_DIR).'/webapp/uploads/repository/';
        $base_name = basename($file['name'], '.zip' );
        $base_name = rawurlencode($base_name);
        $uploadfile = $uploaddir . $file['name'];
        fwrite($fh, "base_name : " . $base_name ."\n");     // 2008/11/12 kawa check point.
        fwrite($fh, "uploadfile : " . $uploadfile ."\n");       // 2008/11/12 kawa check point.
        $movestat = move_uploaded_file($file['tmp_name'], $uploadfile);
        $server_file = $uploadfile;
    }
} else {
    // can't get upload files.
    //fwrite($fh, "check 7-3\n");
    fwrite($fh, "Check Point 13-C: Cannot get upload files. ");

    if(empty($_FILES)){
        fwrite($fh, "file empty\n");
        fclose($fh);
        return new CMMesse_ErrorBean("Data is Empty");
    }

    header("HTTP/1.1 400 Bad Request");
    header("X-Error-Code: UnknownCollection");
    session_destroy();
    fwrite($fh, "\n");
    fclose($fh);
    exit;
}
// Fix multi-form upload 2010/01/18 --end--

# Check the MD5 we received is correct
# (ある場合のみ検査する。appクライアントからはは送信されない。)
# アルファベットが小文字の場合と大文字の場合がある。・・・ので、大文字小文字の区別は行わない
fwrite($fh, "Check Point 14: Before check contentMd5.\n");
if(isset($header_params['contentMd5'])){
    fwrite($fh, "Check Point 15-A: contentMd5 is set.\n");
    $md5_server = md5_file($server_file);
    fwrite($fh, "Client-Hash : " . $header_params['contentMd5'] ."\n");     // 2008/11/12 TestCode
    fwrite($fh, "Server-Hash : " . $md5_server ."\n");                      // 2008/11/12 TestCode
//  if($header_params['contentMd5'] != $md5_server) {
    if(strnatcasecmp($header_params['contentMd5'], $md5_server)){
        // V1.2 => V1.3, Create Error Document
        $header_params['summary'] = 'Client-MD5:'   . $header_params['contentMd5'] .
                                    ', Server-MD5:' . $md5_server ."\n";
        generateErrorDocument($header_params, $error_doc);
        $xmlsize = strlen($error_doc);
        fwrite($fh, $error_doc . "\n");     // 2008/11/12 kawa check point.
        // header output
        header("Precondition failed MAYMUSTMD5412", true, 412);
        header("X-Error-Code: ErrorChecksumMismatch");
        header("Content-Type: application/atom+xml;charset=\"utf-8\"");
        header("Content-Length: ".$xmlsize);
        session_destroy();
        fclose($fh);
        echo $error_doc;
        exit;
    }
}else{
    fwrite($fh, "Check Point 15-B: contentMd5 is not set.\n");
}


// --------------------------------------------------------------
// Import the contents. Call "WEKO Import Action".
// --------------------------------------------------------------
#E-Printsもリクエスト(POST)の処理はせいぜいこんなもの。
#WEKOの本コレクションではtreamentにもあるように、Importに利用される
$import_option = array(
    "timeout" => "3600",        // インポートアクションのMAXレスポンス時間。大きめの値をセットしておく
    "allowRedirects" => true,
    "maxRedirects" => 3,
);
// Add 2009/03/17 Y.Nakao --start--
if($header_params['contentType'] == 'text/xml'){
    $import_url = '?action=repository_action_main_sword_index';
    $param = "&filename=".$base_name;
} else {
    $import_url = '?action=repository_action_main_sword_import';
    $param = "&filename_zip=".$base_name;
}
if(!empty($_SESSION['SWORD_PATH_HTDOCS'])){
    $import_url = $_SESSION['SWORD_PATH_HTDOCS'].$import_url;
    fwrite($fh, "read config : ".$_SESSION['SWORD_PATH_HTDOCS']."\n");
} else {
    $import_url = BASE_URL.'/index.php'.$import_url;        // Modify Directory specification K.Matsuo 2011/9/2
}
// Add 2009/03/17 Y.Nakao --end--
$param .= "&insert_user=".$header_params['owner'];
if(isset($checkedIds)){
    $param .= "&checkedIds=".$checkedIds;
}
if(isset($newIndex)) {
    $param .= "&newIndex=".$newIndex;
}
$param .= "&login_id=".rawurlencode($_SERVER['PHP_AUTH_USER'])."&password=".rawurlencode($_SERVER['PHP_AUTH_PW']);

fwrite($fh, "import url : ".$import_url."\n");
fwrite($fh, "import_index : " . $param ."\n");

$import_url .= $param;
//fwrite($fh, "check 8\n");     // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 16: Before HTTP_Request.\n");
$import_req = new HTTP_Request($import_url, $import_option);
// setting HTTP header
if(isset($_SERVER['HTTP_USER_AGENT'])){
	$import_req->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
}
if(isset($_SERVER['HTTP_REFERER'])){
	$import_req->addHeader("Referer",$_SERVER['HTTP_REFERER']);
}
$import_req->setMethod(HTTP_REQUEST_METHOD_GET);
$import_res = $import_req->sendRequest();

//fwrite($fh, "check 9\n");     // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 17: After HTTP_Request.\n");

// --------------------------------------------------------------
// Creates the XML provided in the answer, "Atom Entry Document".
// --------------------------------------------------------------
// Parse ImportAction's Response
$import_body = $import_req->getResponseBody();// ResponseBody(レスポンスXML)を取得
$parser = xml_parser_create();
xml_parse_into_struct($parser, $import_body, $vals, $index);
xml_parser_free($parser);
fwrite($fh, "Check Point 18: Parse import response.\n");

# TestCode : Check and Dump Authorize Rsoponse to Server Text.
foreach($vals as $key => $resparams) {
	if( isset($resparams['tag'])&&isset($resparams['value'])){
    	fwrite($fh, "['" . $key . "'] = " .$resparams['tag']. "/" .$resparams['value']."\n");
	}
}

#Check Status of Import Action

$import_status = $vals[$index['STATUS'][0]]['value'];
//fwrite($fh, $import_status."\n");
fwrite($fh, "Check Point 19: Check import status. Status: ".$import_status."\n");
if ( $import_status != "success" ){
    // V1.2 => V1.3, Create Error Document
    $header_params['summary'] = $vals[$index['SUMMARY'][0]]['value'];
    // Add for error check 2014/09/12 T.Ichikawa --start--
    if(isset($vals[$index['TREATMENT'][0]])) {
        $header_params['treatment'] = $vals[$index['TREATMENT'][0]]['value'];
    }
    if(isset($vals[$index['DESCRIPTION'][0]])) {
        $header_params['description'] = $vals[$index['DESCRIPTION'][0]]['value'];
    }
    // Add for error check 2014/09/12 T.Ichikawa --end--
    generateErrorDocument($header_params, $error_doc);
    $xmlsize = strlen($error_doc);
    fwrite($fh, $error_doc . "\n");     // 2008/11/12 kawa check point.
    // header output
    header("Internal Server Error", true, 500);
    header("X-Error-Code: " . $import_status);      // X-Error-Code is generated by WEKO.
    header("Content-Type: application/atom+xml;charset=\"utf-8\"");
    header("Content-Length: ".$xmlsize);
    session_destroy();
    fclose($fh);
    echo $error_doc;
    exit;
}
$import_code = $import_req->getResponseCode();      // ResponseCode(200等)を取得
$import_header = $import_req->getResponseHeader();  // ResponseHeader(レスポンスヘッダ)を取得
$header_params['startid'] = $vals[$index['START_ID'][0]]['value'];  // Get Start ID (or Suffix)
$header_params['endid'] = $vals[$index['END_ID'][0]]['value'];      // Get End ID (or Suffix)
$uris = array();
$cnt_uri = count($index['CONTENTS_URI']);
for($cnt=0; $cnt<$cnt_uri; $cnt++ ) {
    array_push($uris, $vals[$index['CONTENTS_URI'][$cnt]]['value']);
}
$header_params['contents'] = $uris;                                 // Get Contents URIs
// 2008/11/11 S.Kawasaki retrieve SWORD options End


// generate Atom entry document
$atom_entry_doc = '';
if($header_params['contentType'] == "text/xml"){
     $atom_entry_doc = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n";
} else {
    generateEntryDocument($header_params, $atom_entry_doc);
}
//$atom_entry_doc_enc = mb_convert_encoding($atom_entry_doc, "UTF-8", "auto");
$xmlsize = strlen($atom_entry_doc);
# TestCode : Check and Dump Atom Entry Document to Server Text.
fwrite($fh, $atom_entry_doc . "\n");        // 2008/11/12 kawa check point.
fwrite($fh, "Check Point 20: Successfully completed SWORD import.\n");
fclose($fh);
// header output
header("Created", true, 201);
header("Location: ".$header_params['generator']);
header("Content-Type: application/atom+xml;type=entry;charset=\"utf-8\"");
header("Content-Length: ".$xmlsize);
// output　Atom entry document
echo $atom_entry_doc;
exit;
?>
