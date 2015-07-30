<?php
// --------------------------------------------------------------------
//
// $Id: serviceitemtype.php 32116 2014-02-27 07:50:53Z rei_matsuura $
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
// This is an handler called by Apache/mod_perl to provide the 
// Service Document for SWORD.
//
// --------------------------------------------------------------------

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
// Add 2009/03/17 Y.Nakao --start--
if(file_exists(HTDOCS_DIR . "/weko/sword/config.txt")){
    $file_handle = fopen(HTDOCS_DIR . "/weko/sword/config.txt", "r");
    while (!feof($file_handle)) {
        $line = fgets($file_handle);
        $line = str_replace("\r\n", "", $line);
        $line = str_replace("\n", "", $line);
        $conf = explode("\t", $line);
        if(count($conf) == 2){
            $_SESSION[$conf[0]] = $conf[1];
        }
    }
    fclose($file_handle);
}
// Add 2009/03/17 Y.Nakao --end--
/*
// --------------------------------------------------------------
// TestCode : Dump request params and headers
// --------------------------------------------------------------
//$fh=fopen("servicedoc_log.txt","w");
$fh=fopen(WEBAPP_DIR."/logs/weko/sword/servicedoc_log.txt","w");
chmod(WEBAPP_DIR."/logs/weko/sword/servicedoc_log.txt", 0600);
fwrite($fh, "check 0\n");       // 2008/11/12 kawa check point
foreach($_SERVER as $key => $server_params) fwrite($fh, "\$_SERVER['" . $key . "'] = " . $server_params ."\n");
if(!isset($_SERVER['HTTP_X_ON_BEHALF_OF'])){
    $_SERVER['HTTP_X_ON_BEHALF_OF'] = "";
}
fwrite($fh, "X-On-Behalf-Of : " . $_SERVER['HTTP_X_ON_BEHALF_OF'] ."\n");
*/
// --------------------------------------------------------------
// Authorization
// --------------------------------------------------------------
$response_auth = authenticate($_SESSION,$_SERVER, $Cookies);
/*
# ★TestCode : Check and Dump Authorize Rsoponse to Server Text.
foreach($response_auth as $key => $resparams) fwrite($fh, "['" . $key . "'] = " . $resparams . "\n");
*/
// $response['status_code']defined means there was an authentication error
if( isset($response_auth['status_code'])){
    header("Unauthorized", true, 401);
    header("WWW-Authenticate: Basic realm=\"SWORD\"");
    header("X-Error-Code: " . $response_auth['x_error_code']);
    $_SESSION->terminate;
    exit;
}
// authrize succeed.
if(!isset($response_auth["login_id"])){
    $response_auth["login_id"] = "";
}
$userID = $response_auth["login_id"];       // Login ID of Client
if(!isset($response_auth["owner"])){
    $response_auth["owner"] = "";
}
$owner  = $response_auth["owner"];          // X-On-Behalf-Of (if Exists)
if(!isset($response_auth["login_user_email"])){
    $response_auth["login_user_email"] = "";
}
$login_user_email = $response_auth["login_user_email"]; // E-mail address of Client
if(!isset($response_auth["owner_email"])){
    $response_auth["owner_email"] = "";
}
$owner_email = $response_auth["owner_email"];           // E-mail address of Owner

// --------------------------------------------------------------
// Get Service Document and response
// --------------------------------------------------------------
// setting HTTP header
$svc_option = array( 
    "timeout" => "10",
    "allowRedirects" => true, 
    "maxRedirects" => 3, 
);
// Add 2009/03/17 Y.Nakao --start--
$svc_url = '?action=repository_action_main_sword_serviceitemtype';
if(isset($_SESSION['SWORD_PATH_HTDOCS']) && $_SESSION['SWORD_PATH_HTDOCS'] != null && $_SESSION['SWORD_PATH_HTDOCS'] != ''){
    $svc_url = $_SESSION['SWORD_PATH_HTDOCS'].$svc_url;
    //fwrite($fh, "read config : ".$_SESSION['SWORD_PATH_HTDOCS']."\n");
} else {
    $svc_url = BASE_URL.'/index.php'.$svc_url;      // Modify Directory specification K.Matsuo 2011/9/2
}
// Add 2009/03/17 Y.Nakao --end--
//$svc_url = BASE_URL.'/index.php?action=repository_action_main_sword_servicedocument';     // Modify Directory specification K.Matsuo 2011/9/2
$svc_req = new HTTP_Request($svc_url, $svc_option);
if(isset($_SERVER['HTTP_USER_AGENT']))
{
    $svc_req->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
}
else
{
    $svc_req->addHeader("User-Agent", 'SWORD Client for WEKO');
}
if(!isset($_SERVER['HTTP_REFERER'])){
    $_SERVER['HTTP_REFERER'] = "";
}
$svc_req->addHeader("Referer", $_SERVER['HTTP_REFERER']);
$svc_req->setMethod(HTTP_REQUEST_METHOD_GET);

foreach ($Cookies as $key => $value)
{
    $svc_req->addCookie($value['name'],$value['value']);
}
$svc_res = $svc_req->sendRequest();     // send to "WEKO ServiceDocument Action"

// Succeed 
if (!PEAR::isError($svc_res)){
    $svc_code = $svc_req->getResponseCode();// ResponseCode(200等)を取得 
    $svc_header = $svc_req->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
    $svc_body = $svc_req->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
    $xmlsize = strlen($svc_body);   
    //fwrite($fh, $svc_body . "\n");
    //fclose($fh);
    header("OK", true, 200);
    header("Content-Type: application/atomsvc+xml;charset=\"utf-8\"");
    header("Content-Length: ".$xmlsize);
    echo $svc_body;
// Failed
} else {
    // Error Occured in "WEKO ServiceDocumentAction"
    // サーバ側の問題のため、詳細エラー情報はクライアントに返さない
    header("Internal Server Error", true, 500);
    header("X-Error-Code: " . "WekoServiceDocumentActionError");
    $_SESSION->terminate;
    exit;
}
?> 
