<?php
// --------------------------------------------------------------------
//
// $Id: utils.php 56714 2015-08-19 13:30:20Z tomohiro_ichikawa $
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
// This is the general API for the SWORD implementation on NC2 WEKO,
// as used by the other modules.
//
// METHODS:
//
// authenticate( $session, $request )
//  Parameters:     $session -> the current Session object
//          $request -> the mod_perl object
//  Returns:    in case of error: a hash with HTTP status code and related error codes set (eg. X-Error-Code in SWORD)
//          otherwise: a hash with the user and behalf user objects
//  Notes:      This also tests whether the (optional) mediation is allowed or not
//
// process_headers( $session, $request )
//  Parameters: cf. above
//  Returns:    in case of error: a hash with HTTP status code and related error codes set (eg. X-Error-Code in SWORD)
//          otherwise: a hash with the options sent through HTTP headers
//  Notes:      This will test if the options are valid (and set default options if not)
//
// create_xml():
//  Internal method which creates the XML provided in the answer.
//
// --------------------------------------------------------------------

//require_once('/var/www/html/netcommons2.1/maple/includes/pear/HTTP/Request.php');     // OK
//require_once ('D:/xampp/htdocs/netcommons21/maple/includes/pear/HTTP/Request.php');
//$_CONF['have_pear'] = false;      // OK
//require_once('../../maple/includes/pear/HTTP/Request.php');   // NG
require_once('HTTP/Request.php');       // OK
//require_once('HTTP/Client.php');

// Check authenticate parameters.
// equivalent of E-Prints, "authenticate"
// Success : Return Value is Response Params.
// Error   : Retun Status-Code
function authenticate(&$session, $request, &$login_Cookies=array())
{

    //$fh=fopen("authen_log.txt","w");
    $fh=fopen(WEBAPP_DIR."/logs/weko/sword/authen_log.txt","w");
    chmod(WEBAPP_DIR."/logs/weko/sword/authen_log.txt", 0600);
    fwrite($fh, "check 1\n");
    $response = array();        // return params

    // check session params whether already authorized or not.　
    // => Don't Check Session, but Check "X-On-Behalf-Of"
    // ・・・ログインユーザID、X-On-Behalf-Ofはセッション保存しない。毎回クライアントから送られてくるものとする。
    // 情報が間違っていると認証ヘッダを送り返すが、成功してからは毎回認証成功するようなログインID＋PASSの組み合わせが送られてくる。
/*
    if( isset($session["login_id"]) && $session["login_id"]!='' ){
        // already authorized.
        $response["login_id"] = $session["login_id"];
        // if behalf user exists.
        if(isset($session["behalf"])){
            $response["behalf"] = $session["behalf"];
        }
        return $response;
    }
*/
    // if "X-On-Behalf-Of" parametaer exists in request ($_SERVER)...
    $is_behalf_session   = false;
    $is_behalf_request   = false;
    $name_behalf_session = '';
    $name_behalf_request = '';
    if(isset($session["behalf"])){
        $is_behalf_session   = true;
        $name_behalf_session = $session["behalf"];
    }
    if(isset($request['HTTP_X_ON_BEHALF_OF'])){
        $is_behalf_request   = true;
        $name_behalf_request = $request['HTTP_X_ON_BEHALF_OF'];
    }
    if($is_behalf_session != $is_behalf_request) {
        // OWNER情報が変わった。毎回認証するので問題なし？
    }
    if( ($is_behalf_session && $is_behalf_request) &&
        ($name_behalf_session != $name_behalf_request)) {
        // OWNER情報が変わった。毎回認証するので問題なし？
    }

    // ---------------------------------------------
    // check request header
    // ---------------------------------------------

    // if doesn't include authorize information.
    if( !isset($request["PHP_AUTH_USER"])){
        $response["status_code"]  = 401;
        $response["x_error_code"] = "Unauthorized";
        return $response;
    }
    if( $request["PHP_AUTH_USER"]==''){
        $response["status_code"]  = 401;
        $response["x_error_code"] = "Unauthorized";
        return $response;
    }
    // get authorize information.
    $userID     = $request["PHP_AUTH_USER"];
    $password   = $request["PHP_AUTH_PW"];
    if(isset($request['HTTP_X_ON_BEHALF_OF'])) {    // owner != depositor
        $owner   = $request['HTTP_X_ON_BEHALF_OF'];
    }
    /*
    // Check we have Basic authentication sent in the headers, and decode the Base64 string:
    if($authen =~ /^Basic\ (.*)$/)
        {
                $authen = $1;
        }
        my $decode_authen = MIME::Base64::decode_base64( $authen );
        if(!defined $decode_authen)
        {
                $response{status_code} = 401;
        $response{x_error_code} = "ErrorAuth";
                return \%response;
        }

        my $username;
        my $password;

    if($decode_authen =~ /^(\w+)\:(\w+)$/)
        {
                $username = $1;
                $password = $2;
        }
        else
        {
                $response{status_code} = 401;
        $response{x_error_code} = "ErrorAuth";
                return \%response;
        }
    */
    fwrite($fh, "check 4\n");
    // Does user exist in NetCommons2/WEKO?
    // Add 2009/03/17 Y.Nakao --start--
    $send_param ='?action=repository_action_main_sword_login&login_id='.$userID.'&password='.$password;
    if(isset($_SESSION['SWORD_PATH_HTDOCS']) && $_SESSION['SWORD_PATH_HTDOCS'] != null && $_SESSION['SWORD_PATH_HTDOCS'] != ''){
        $send_param = $_SESSION['SWORD_PATH_HTDOCS'].$send_param;
        fwrite($fh, "read config : ".$_SESSION['SWORD_PATH_HTDOCS']."\n");
    } else {
        $send_param = BASE_URL.'/index.php'.$send_param;      // Modify Directory specification K.Matsuo 2011/9/2
    }
    // Add 2009/03/17 Y.Nakao --end--
    if(isset($owner)) {
        $send_param .= '&sword_owner='.$owner;
    }
    $option = array(    // HTTP_Request init/send http request
        "timeout" => "10",
        "allowRedirects" => true,
        "maxRedirects" => 3,
    );
    fwrite($fh, "check\n");
    $http = new HTTP_Request($send_param, $option);
    if(isset($_SERVER['HTTP_USER_AGENT']))
    {
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);    // setting HTTP header
    }
    else
    {
        $http->addHeader("User-Agent", 'SWORD Client for WEKO');    // setting HTTP header
    }
    fwrite($fh, "check\n");
//  $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
    fwrite($fh, "check\n");
    $response_auth = $http->sendRequest();                              // run HTTP request
    fwrite($fh, "check\n");
    // Check response of WEKO login-action.
    if (!PEAR::isError($response_auth)) {
        fwrite($fh, "check 5-1, ERROR\n");
        fwrite($fh, "status :". $response_auth . "\n");
        $login_code = $http->getResponseCode();// ResponseCode(200等)を取得
        $login_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得
        $login_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得
        $login_Cookies = $http->getResponseCookies();// クッキーを取得
        fwrite($fh, "ResponseCode :". $login_code . "\n");
        fwrite($fh, "ResponseBody :". $login_body . "\n");
    } else {
        fwrite($fh, "check 5-2, ERROR\n");
        $response["status_code"]  = 401;
        $response["x_error_code"] = "ErrorAuth";
        return $response;
    }

/*
    // HTTP_Clientを使用したコード (デモ環境だRequest/Clizent共にうまくいかない)
    $http =& new HTTP_Client();         // 2008.11.25 test
    fwrite($fh, "check\n");             // 2008.11.25 test
    $http->get($send_param);            // 2008.11.25 test
    fwrite($fh, "check\n");
    $response_auth = $http->currentResponse();
    fwrite($fh, "check\n");
    fwrite($fh, "body = ". $response_auth['body']."\n");
    $login_body = $response_auth['body'];
*/

    fwrite($fh, "check 6\n");
    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $login_body, $vals, $index);
    xml_parser_free($parser);
    // Get Login Result Status
    $auth_status = $vals[$index['STATUS'][0]]['value'];
    // Get Login User's E-mail
    if(isset($index['LOGIN_EMAIL'])) {      // may not exists
        $login_user_email = $vals[$index['LOGIN_EMAIL'][0]]['value'];
    }
    // Get Owner's E-mail
    if(isset($index['OWNER_EMAIL'])) {      // may not exists
        $owner_email = $vals[$index['OWNER_EMAIL'][0]]['value'];
    }
    foreach($vals as $key => $resparams) {
        if(!isset($resparams['tag'])){
            $resparams['tag'] = "";
        }
        if(!isset($resparams['value'])){
            $resparams['value'] = "";
        }
        fwrite($fh, "['" . $key . "'] = " . $resparams['tag'] . "/" .$resparams['value'] ."\n");
    }
    fwrite($fh, "check 7\n");

    // Get Login User's Authority
    $login_user_authority = $vals[$index['LOGIN_AUTHORITY'][0]]['value'];
    if ( $auth_status == "success" ){
        fwrite($fh, "check 7-1\n");
        $response["login_id"] = $userID;
        if(isset($owner)) {
            $response["owner"]= $owner;
        }
        if(isset($login_user_email)) {
            $response["login_user_email"] = $login_user_email;
        }
        if(isset($owner_email)) {
            $response["owner_email"] = $owner_email;
        }
    } else {
        fwrite($fh, "check 7-2\n");
        // When failed, clear session.
        $response["status_code"]  = 401;
        $response["x_error_code"] = $auth_status;
    }
//  foreach($response as $key => $resparams) fwrite($fh, "['" . $key . "'] = " . $resparams . "\n");
//  foreach($session as $key => $sesparams) fwrite($fh, "['" . $key . "'] = " . $sesparams . "\n");
    fclose($fh);
    return $response;
}

// Check request header and set response parameters.
// equivalent of E-Prints, "process_headers"
// Success : Return Value is Header Params.
// Error   : Retun Status-Code
function process_headers($request)
{
    // ---------------------------------------------------
    // mandately (app and SWORD)
    // - Content-Type
    // - Content-Length
    // option (SWORD Level:1)
    // - Content-MD5
    // - Content-Disposition
    // - X-Verbose
    // - X-No-Op
    // - X-Format-Namespace
    // - Slug
    // option (SWORD V1.3)
    // - User-Agent
    // - X-Packaging
    // option (WEKO)
    // - Insert_Index
    // - New_Index
    // ---------------------------------------------------
    $response = array();

    // ---------------------------------------------------
    // mandately (app and SWORD)
    // ---------------------------------------------------
    # first let's check some mandatory fields:

    # Content-Type
    if(isset($request['CONTENT_TYPE'])){
        $content_type = $request['CONTENT_TYPE'];
    # 2008/11/27 "HTTP_CONTENT_TYPE" case exists
    } else if(isset($request['HTTP_CONTENT_TYPE'])){
        $content_type = $request['HTTP_CONTENT_TYPE'];
    }
    if(!isset($content_type)){
        $response["status_code"] = 400; # Bad Request
        return $response;
    }
    if( $content_type == 'application/xml' ){
        $content_type = 'text/xml';
    }
    $response['contentType'] = $content_type;

    # CONTENT-LENGTH
    # 2010/02/09 "HTTP_CONTENT_LENGTH" case exists
    if(!isset($request['CONTENT_LENGTH']) && isset($request['HTTP_CONTENT_LENGTH'])){
        $request['CONTENT_LENGTH'] = $request['HTTP_CONTENT_LENGTH'];
    }
    if(!isset($request['CONTENT_LENGTH'])) {
        $response["status_code"] = 400; # Bad Request
        return $response;
    }
    $response['contentLength'] = $request['CONTENT_LENGTH'];

    # Collection
    if(isset($request['uri'])){
		$uri =$request['uri'];
	}


    # TODO more checks on the URI part
//  if( $uri =~ /^.*\/(.*)$/ ) {
//      $collection = $1;
//   }
//  if(!defined $collection) {
//      $response["status_code"] = 400; # Bad Request
//      return $response;
//  }
    # Note that we don't check (here) if the collection exists or not in this repository
//  $response{collection} = $collection;

    // Addition of HTTPS check 2010/02/03 S.Nonomura --start--
    if ( (false === empty($_SERVER['HTTPS']))&&('off' !==$_SERVER['HTTPS'])) {
        $generator = 'https://'.$request['HTTP_HOST'].$request['REQUEST_URI'];
    } else {
        $generator = 'http://'.$request['HTTP_HOST'].$request['REQUEST_URI'];
    }
    // Addition of HTTPS check 2010/02/03 S.Nonomura --end--

    //$generator = 'http://'.$request['HTTP_HOST'].$request['REQUEST_URI'];
    $response['generator']          = ereg_replace('\?.*$', '', $generator);

    // ---------------------------------------------------
    // option (SWORD Level:1)
    // ---------------------------------------------------
    # now we can parse the rest (or set default values if not found in headers):

    # Content-MD5
    if(isset( $request['HTTP_CONTENT_MD5'])) {
        $response['contentMd5'] = $request['HTTP_CONTENT_MD5'];
    }

    # Content-Disposition
    $deposition = $request['HTTP_CONTENT_DISPOSITION'];
    if(isset($deposition)){
        # get original filename
        $filename = "deposit";
        $token = split('[;]', $deposition);
        foreach($token as $value) {
            $filename = split('[=]', $value);
            if(!strcmp($filename[0],'filename')) {
                $filename = $filename[1];
                // BugFix single cotation Y.Nakao 2013/06/07 --start--
                $filename = preg_replace("/^\"|^\'/", "", $filename);
                $filename = preg_replace("/\"$|\'$/", "", $filename);
                // BugFix single cotation Y.Nakao 2013/06/07 --end--
                
                // Bugfix support multi-byte string T.Koyasu 2015/08/12 --start--
                $filename = rawurldecode($filename);
                // Bugfix support multi-byte string T.Koyasu 2015/08/12 --end--
                break;
            }
        }
        $response['contentDisposition'] = $filename;
    } else {
        $response['contentDisposition'] = "deposit";    # default value
    }

    # X-Verbose (NOT SUPPORTED)
    if(isset($request['HTTP_X_VERBOSE'])) {
        $response['xVerbose'] = $request['HTTP_X_VERBOSE'];
    }

    # X-No-Op (NOT SUPPORTED)
    if(isset($request['HTTP_X_NO_OP'])) {
        $response['xNoOp'] = $request['HTTP_X_NO_OP'];
    }

    # X-Format-Namespace
    if(isset($request['HTTP_X_FORMAT_NAMESPACE'])) {
        $response['xFormatNamespace'] = $request['HTTP_X_FORMAT_NAMESPACE'];
    } else {
        $response['xFormatNamespace'] = "WEKO";     # WEKO NS
    }

    # Slug
    if(isset($request['HTTP_SLUG'])) {
        $response['slugHeader'] = $request['HTTP_SLUG'];
    }

    // ---------------------------------------------------
    // option (SWORD V1.3)
    // ---------------------------------------------------
    # User-Agent
    # V1.2b => V1.3 User-Agent added.
    if(isset( $request['HTTP_USER_AGENT'])) {
        $response['user_agent'] = $request['HTTP_USER_AGENT'];
    }

    # X-Packaging
    # V1.2b => V1.3 X-Packaging added.
    if(isset( $request['HTTP_X_PACKAGING'])) {
        $response['x_packaging'] = $request['HTTP_X_PACKAGING'];
    }

    // ---------------------------------------------------
    // option (WEKO)
    // ---------------------------------------------------
    # Insert_Index
    if(isset( $request['HTTP_INSERT_INDEX']) && strlen($request['HTTP_INSERT_INDEX']) > 0) {
        $response['insert_index'] = $request['HTTP_INSERT_INDEX'];
    }
    # New_Index
    if(isset( $request['HTTP_NEW_INDEX']) && strlen($request['HTTP_NEW_INDEX']) > 0) {
        $response['new_index'] = $request['HTTP_NEW_INDEX'];
    }

    return $response;
}

// genedrate "Atom Entry Document" (on Successful case)
// equivalent of E-Prints, "create_xml"
// htmlspecialcharsを使用すると何故かhttpヘッダ(status code = 200, OK)が送信される。
// ・・・そのためステータスをヘッダにセットしてから本メソッドを使用すること
// ・・・しかしそうするとhtmlspecialcharsを使用時に"ヘッダ送信済み"警告が勝手にechoされるため使わないのが無難
function generateEntryDocument($infos, &$response)
{
    # get entry document information
    if(isset($infos['contentType'])){
		$content_type=$infos['contentType'];
	}else{
		$content_type="";
	}
    if(isset($infos['contentDisposition'])){
		$filename=$infos['contentDisposition'];
	}else{
		$filename="";
	}
    if(isset($infos['xFormatNamespace'])){
		$format_ns=$infos['xFormatNamespace'];
	}else{
		$format_ns="";
	}
    // Not Supported
	if(isset($infos['slugHeader'])){
		$slug=$infos['slugHeader'];
	}else{
		$slug="";
	}
    if(isset($infos['sword_treatment'])){
		$sword_treatment =$infos['sword_treatment'];
	}else{
		$sword_treatment ="";
	}
    // true owner of contents (author)
	if(isset($infos['owner'])){
		$owner=$infos['owner'];
	}else{
		$owner="";
	}
    // owner E-mail
	if(isset($infos['owner_email'])){
		$owner_email=$infos['owner_email'];
	}else{
		$owner_email="";
	}
    // depositor
	if(isset($infos['depositor'])){
		$depositor=$infos['depositor'];
	}else{
		$depositor="";
	}
    // depositor E-mail
	if(isset($infos['depositor_email'])){
		$depositor_email =$infos['depositor_email'];
	}else{
		$depositor_email ="";
	}
    // user agent
	if(isset($infos['user_agent'])){
		$user_agent=$infos['user_agent'];
	}else{
		$user_agent="";
	}
    // x-packaging
	if(isset($infos['x_packaging'])){
		$x_packaging=$infos['x_packaging'];
	}else{
		$x_packaging="";
	}
    // server version
	if(isset($infos['version'])){
		$version=$infos['version'];
	}else{
		$version="";
	}
    // source generator (uri)
	if(isset($infos["generator"])){
		$source_gen=$infos["generator"];
	}else{
		$source_gen="";
	}
    // import start id
	if(isset($infos['startid'])){
		$startid=intval($infos['startid']);
	}else{
		$startid="";
	}
    // import end id
	if(isset($infos['endid'])){
		$endid=intval($infos['endid']);
	}else{
		$endid="";
	}
    if(isset($infos['startid'])){
		$idterm =$infos['startid'];
	}else{
		$idterm ="";
	}
    if($startid < $endid) {
        $idterm .= '-' .$infos['endid'];
    }
    // imported contents URIs
	if(isset($infos['contents'])){
		$contents=$infos['contents'];
	}else{
		$contents="";
	}

    # Header
    $response = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n";
    # ENTRY
    $response.= "<entry xmlns=\"http://www.w3.org/2005/Atom\" xmlns:sword=\"http://purl.org/net/sword/\">" . "\n";
    # TITLE
    $response.= "  <title>" . $infos['collectionName'] . "</title>" . "\n";
    # ID    ( or SLUG => at the mo, the Slug value is kept in the db, but not shown in the answer )
    $response.= "  <id>" . $idterm . "</id>" . "\n";                    // startid - endid
    # UPDATED
    if(isset($infos['update'])){
		$time =$infos['update'];
	}else{
		$time ="";
	}
    // htmlspecialcharsを使用すると何故かhttpヘッダ(status code = 200, OK)が送信される。意味不明なので保留
    $response.= '  <updated>' . htmlspecialchars_self($time) . '</updated>' . "\n";

    # AUTHOR/CONTRIBUTOR
    # 代理人がいる場合、コンテンツのオーナーとPOSTした代理人の双方を出力
    if( $depositor != null ){
        # 1st : output owner's name, email
        $response.= "  <author>" . "\n";
        $response.= "    <name>" . $owner . "</name>" . "\n";
        if($owner_email != null) {
            $response.= "    <email>" . $owner_email . "</email>" . "\n";
        }
        $response.= "  </author>" . "\n";
        # 2nd : output $depositor(=NC2 user)'s name, email
        $response.= "  <contributor>" . "\n";
        $response.= "    <name>"  . $depositor     . "</name>" . "\n";
        if($depositor_email != null) {
            $response.= "    <email>" . $depositor_email . "</email>" . "\n";
        }
        $response.= "  </contributor>" . "\n";
    # 代理人がいない場合、コンテンツのオーナーを出力
    } else {
        # 1st : output owner's name, email
        $response.= "  <author>" . "\n";
        $response.= "    <name>" . $owner . "</name>" . "\n";
        if($owner_email != null) {
            $response.= "    <email>" . $owner_email . "</email>" . "\n";
        }
        $response.= "  </author>" . "\n";
    }

    # SUMMARY
    # ⇒ not mandately, pending.

    # CONTENT
    # item detail pages URIs
    for($ii=0; $ii<count($contents); $ii++) {
        $response.= "  <content type=\"text/html\" src=\"" . htmlspecialchars_self($contents[$ii], ENT_QUOTES, 'UTF-8') . "\"/>" . "\n";
    }
    # EDIT-MEDIA
    # ⇒ not mandately, pending.

    # EDIT
    # ⇒ not mandately, pending.

    # SOURCE GENERATOR
    $response.= "  <source>" . "\n";
    $response.= "    <generator uri=\"". htmlspecialchars_self($source_gen)."\" version=\"" . $version . "\"/>" . "\n";
    $response.= "  </source>" . "\n";
    # SWORD TREATMEMT
    $response.= "  <sword:treatment>" . htmlspecialchars_self($sword_treatment) . "</sword:treatment>" . "\n";
    # FORMAT NAMESPACE
    $response.= "  <sword:formatNamespace>" . htmlspecialchars_self($format_ns) . "</sword:formatNamespace>" . "\n";
    # User-Agent
    # V1.2b => V1.3 <sword:userAgent> added.
    if(isset($user_agent)) {
        $response.= "  <sword:userAgent>" . htmlspecialchars_self($user_agent) . "</sword:userAgent>" . "\n";
    }
    # X-Packaging
    # V1.2b => V1.3 <sword:packaging> added.
    if(isset($x_packaging)) {
        $response.= "  <sword:packaging>" . htmlspecialchars_self($x_packaging) . "</sword:packaging>" . "\n";
    }
    # Hooter
    $response.= "</entry>";
    //return $response;
}

// genedrate "Error Document" (on Error cases)
// htmlspecialcharsを使用すると何故かhttpヘッダ(status code = 200, OK)が送信される。
// ・・・そのためステータスをヘッダにセットしてから本メソッドを使用すること
function generateErrorDocument($infos, &$response)
{
    # get error document information
    // true owner of contents (author)
	if(isset($infos['owner'])){
		$owner=$infos['owner'];
	}else{
		$owner="";
	}
    // owner E-mail
	if(isset($infos['owner_email'])){
		$owner_email=$infos['owner_email'];
	}else{
		$owner_email="";
	}
    // depositor
	if(isset($infos['depositor'])){
		$depositor=$infos['depositor'];
	}else{
		$depositor="";
	}
    // depositor E-mail
	if(isset($infos['depositor_email'])){
		$depositor_email =$infos['depositor_email'];
	}else{
		$depositor_email ="";
	}
    // server version
	if(isset($infos['version'])){
		$version=$infos['version'];
	}else{
		$version="";
	}
    // summary (of error)
	if(isset($infos['summary'])){
		$summary=$infos['summary'];
	}else{
		$summary="";
	}
    // source generator (uri)
	if(isset($infos["generator"])){
		$source_gen=$infos["generator"];
	}else{
		$source_gen="";
	}
	// treatment
	if(isset($infos["treatment"])){
		$treatment=$infos["treatment"];
	}else{
		$treatment="";
	}
	// description
	if(isset($infos["description"])){
		$description=$infos["description"];
	}else{
		$description="";
	}
	
    # Header
    $response = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n";
    # ERROR
    $response.= "<sword:error xmlns=\"". htmlspecialchars_self("http://www.w3.org/2005/Atom").
                "\" xmlns:sword=\""    . htmlspecialchars_self("http://purl.org/net/sword/") .
                "\" xmlns:arxiv=\""    . htmlspecialchars_self("http://arxiv.org/schemas/atom") .
                "\" href=\""           . htmlspecialchars_self("http://example.org/errors/BadManifest"). "\">" . "\n";
    # TITLE (const string)
    $response.= "  <title>" . "ERROR" . "</title>" . "\n";
    # UPDATED
    $time = date("Y-m-dTH:i:sZ");
    $response.= "  <updated>" . htmlspecialchars_self($time) . "</updated>" . "\n";
    # AUTHOR/CONTRIBUTOR
    if( $depositor != null ){
        # 1st : output owner's name, email
        $response.= "  <author>" . "\n";
        $response.= "    <name>" . $owner . "</name>" . "\n";
        if($owner_email != null) {
            $response.= "    <email>" . $owner_email . "</email>" . "\n";
        }
        $response.= "  </author>" . "\n";
        # 2nd : output $depositor(=NC2 user)'s name, email
        $response.= "  <contributor>" . "\n";
        $response.= "    <name>"  . $depositor     . "</name>" . "\n";
        if($depositor_email != null) {
            $response.= "    <email>" . $depositor_email . "</email>" . "\n";
        }
        $response.= "  </contributor>" . "\n";
    } else {
        # 1st : output owner's name, email
        $response.= "  <author>" . "\n";
        $response.= "    <name>" . $owner . "</name>" . "\n";
        if($owner_email != null) {
            $response.= "    <email>" . $owner_email . "</email>" . "\n";
        }
        $response.= "  </author>" . "\n";
    }
    # SOURCE GENERATOR
    $response.= "  <source>" . "\n";
    $response.= "    <generator uri=\"". htmlspecialchars_self($source_gen)."\" version=\"" . $version . "\"/>" . "\n";
    $response.= "  </source>" . "\n";
    # SWORD TREATMEMT
    if(strlen($treatment) > 0) {
        $response.= "  <sword:treatment>" . htmlspecialchars_self($treatment) . "</sword:treatment>" . "\n";
    } else {
        $response.= "  <sword:treatment>" . "processing failed" . "</sword:treatment>" . "\n";
    }
    # SUMMARY
    if(isset($summary)) {
        $response.= "  <summary>" . htmlspecialchars_self($summary) . "</summary>" . "\n";
    }
    # DESCRIPTION
    if(strlen($description) > 0) {
        $response.= "  <sword:verboseDescription>" . htmlspecialchars_self($description) . "</sword:verboseDescription>" . "\n";
    }
    # Hooter
    $response.= "</sword:error>" . "\n";
    return $response;
}

// htmlspecialchars self implement ver.
// htmlspecialcharsを呼ぶと何故かheaderのsattus code 200が送信される。それでは自前でステータスコードをセットできないため困る
function htmlspecialchars_self($source){
    // < => &lt;
    // > => &gt;
    // & => &amp;
    // ' => &apos;
    // " => &quot;
    $outstr = str_replace('<', '&lt;', $source);
    $outstr = str_replace('>', '&gt;', $outstr);
    $outstr = str_replace('&', '&amp;', $outstr);
    $outstr = str_replace('\'', '&apos;', $outstr);
    $outstr = str_replace('\"', '&quot;', $outstr);
    return $outstr;
}

?>
