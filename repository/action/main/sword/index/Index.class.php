<?php
// --------------------------------------------------------------------
//
// $Id: Index.class.php 36534 2014-05-30 06:57:56Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/import/ImportCommon.class.php';
require_once WEBAPP_DIR. '/components/mail/Main.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * **********************************************
 * this action is called by outside
 * this action make for SWORD action
 * so on WEKO action must not call this action
 * **********************************************
 */
class Repository_Action_Main_Sword_Index extends RepositoryAction
{
    // component
    var $Session = null;
    var $Db = null;

    // request param
    var $checkedIds = null;     // check parent index, set only
    var $filename = null;       // upload XML file name
    var $insert_user = null;    // login_id of insert user
    
    public $login_id = null;   // login id
    public $password = null;   // password
    
    private $logFh = null;                  // File handle for log 
    private $isCreateLog = true;            // default: true
    private $isAddDateToLogName = false;    // default: false
    private $deleteUploadFile = true;       // default: true
    
    /**
     * return 'error' any error
     *        'index_error' create index error
     *        'requestparam_error' request parameter error
     *        'upload_error' upload file error
     *        'warning' insert item warning
     *        'success' success
     */
    function execute()
    {
        try {
            // Create log file
            if($this->isCreateLog)
            {
                $logName = WEBAPP_DIR."/logs/weko/sword/sword_index_log.txt";
                if($this->isAddDateToLogName)
                {
                    // Add date to logName
                    $logName = WEBAPP_DIR."/logs/weko/sword/sword_index_log_".date("YmdHis").".txt";
                }
                $this->logFh = fopen($logName, "w");
                chmod($logName, 0600);
                fwrite($this->logFh, "Start SWORD index import. (".date("Y/m/d H:i:s").")\n");
                fwrite($this->logFh, "\n");
                fwrite($this->logFh, "[Request parameters]\n");
                fwrite($this->logFh, "  checkedIds: ".$this->checkedIds."\n");
                fwrite($this->logFh, "  filename: ".$this->filename."\n");
                fwrite($this->logFh, "  insert_user: ".$this->insert_user."\n");
                fwrite($this->logFh, "  login_id: ".$this->login_id."\n");
                fwrite($this->logFh, "  password: ".$this->password."\n");
                fwrite($this->logFh, "\n");
            }
            /////////////// init ///////////////
            // check Session and Db Object
            if($this->Session == null){
                $container =& DIContainerFactory::getContainer();
                $this->Session =& $container->getComponent("Session");
            }
            if($this->Db== null){
                $container =& DIContainerFactory::getContainer();
                $this->Db =& $container->getComponent("DbObject");
            }
            // init action
            $result = $this->initAction();
            if ( $result == false ){
                $this->outputError("ErrorUnknown", "Failed in import action at init.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed call initAction. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            
            if(strlen($this->filename) == 0){
                $this->outputError("RequestParameterIsEmpty", "File name is null");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "File name is null. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            
            // Add check login 2012/02/16 Y.Nakao --start--
            if(strlen($this->login_id)==0 || strlen($this->password)==0)
            {
                $this->outputError("RequestParameterIsEmpty", "Request param error : Not login user.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Request param error : Not login user. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            
            // send http request
            $option = array( 
                "timeout" => "10",
                "allowRedirects" => true, 
                "maxRedirects" => 3, 
            );
            $proxy = $this->getProxySetting();
            if($proxy['proxy_mode'] == 1)
            {
                $option = array( 
                        "timeout" => "10",
                        "allowRedirects" => true, 
                        "maxRedirects" => 3,
                        "proxy_host"=>$proxy['proxy_host'],
                        "proxy_port"=>$proxy['proxy_port'],
                        "proxy_user"=>$proxy['proxy_user'],
                        "proxy_pass"=>$proxy['proxy_pass']
                    );
            }
            
            $url = BASE_URL.'/?action=repository_action_main_sword_login'.
                    '&login_id='.$this->login_id.'&password='.$this->password;
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 1: Before HTTP_Request to '".$url."'. (".date("Y/m/d H:i:s").")\n");
            }
            $http = new HTTP_Request($url, $option);
            // run HTTP request 
            $response = $http->sendRequest(); 
            if (!PEAR::isError($response))
            {
                $body = $http->getResponseBody();
                if(strpos($body, 'success') === false)
                {
                    $this->outputError("RequestParameterIsEmpty", "Request param error : Not login user.");
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Failed login.(error in login check) (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    exit();
                }
            }
            else
            {
                $this->outputError("RequestParameterIsEmpty", "Request param error : Not login user.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed login.(error in HTTP_Request) (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 2: Login check OK. (".date("Y/m/d H:i:s").")\n");
            }
            // Add check login 2012/02/16 Y.Nakao --end--
            
            if(strlen($this->insert_user) == 0){
                $this->outputError("RequestParameterIsEmpty", "Not fill insert user's login id.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Request param error : Not fill insert user's login id. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            } else {
                $query = "SELECT user_id, role_authority_id FROM ". DATABASE_PREFIX ."users ".
                         "WHERE login_id = '". $this->insert_user ."'; ";
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "  Execute query: ". $query."\n");
                }
                $result = $this->Db->execute( $query );
                if($result === false || count($result)!=1){
                    // is not user
                    $this->outputError("ErrorCheckInsertUser", "Not found this user. Login id : ".$this->insert_user);
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Not found this user. Login id : ".$this->insert_user." (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    exit();
                }
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "    Complete execute query.\n");
                }
                $this->Session->setParameter("_user_id", $result[0]["user_id"]);
                $this->Session->setParameter("_role_authority_id", $result[0]["role_authority_id"]);
                // get user user_authority_id
                $query = "SELECT user_authority_id FROM ". DATABASE_PREFIX ."authorities ".
                        " WHERE role_authority_id = '".$result[0]["role_authority_id"]."' ";
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "  Execute query: ". $query."\n");
                }
                $result = $this->Db->execute( $query );
                if($result === false || count($result) != 1){
                    // アイテム投稿権限なし there is not insert right
                    $this->outputError("ErrorCheckInsertAuthority", "Not found this user's authority. Login id : ".$this->insert_user);
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Not found this user's authority. Login id : ".$this->insert_user." (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    exit();
                }
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "    Complete execute query.\n");
                }
                
                $user_auth_id = $result[0]["user_authority_id"];
                $this->Session->setParameter("_user_auth_id", $user_auth_id);
                $auth_id = $this->getRoomAuthorityID($result[0]["user_id"]);
                $this->Session->setParameter("_auth_id", $auth_id);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 3: Check authorities. (".date("Y/m/d H:i:s").")\n");
                    fwrite($this->logFh, "  user_id: ".$this->Session->getParameter("_user_id")."\n");
                    fwrite($this->logFh, "  role_authority_id: ".$this->Session->getParameter("_role_authority_id")."\n");
                    fwrite($this->logFh, "  user_authority_id: ".$user_auth_id."\n");
                    fwrite($this->logFh, "  auth_id: ".$auth_id."\n");
                    fwrite($this->logFh, "  repository_admin_base: ".$this->repository_admin_base."\n");
                    fwrite($this->logFh, "  repository_admin_room: ".$this->repository_admin_room."\n");
                }
                
                $insert_auth_ids = '';
                
                if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
                {
                    // Fix tree insert item authority Y.Nakao 2011/12/02 --start--
                    // setting item insert user authority
                    if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_ADMIN)
                    {
                        // _ROLE_AUTH_ADMIN = systemAdmin, 7=admin(not define)
                        $insert_auth_ids .= _ROLE_AUTH_ADMIN.',7';
                    }
                    if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_CHIEF)
                    {
                        if(strlen($insert_auth_ids) > 0)
                        {
                            $insert_auth_ids .= ',';
                        }
                        $insert_auth_ids .= _ROLE_AUTH_CHIEF.','._ROLE_AUTH_CLERK;
                    }
                    if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_MODERATE)
                    {
                        if(strlen($insert_auth_ids) > 0)
                        {
                            $insert_auth_ids .= ',';
                        }
                        $insert_auth_ids .= _ROLE_AUTH_MODERATE;
                    }
                    if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GENERAL)
                    {
                        if(strlen($insert_auth_ids) > 0)
                        {
                            $insert_auth_ids .= ',';
                        }
                        $insert_auth_ids .= _ROLE_AUTH_GENERAL;
                    }
                    if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GUEST)
                    {
                        if(strlen($insert_auth_ids) > 0)
                        {
                            $insert_auth_ids .= ',';
                        }
                        $insert_auth_ids .= _ROLE_AUTH_GUEST;
                    }
                    // Fix tree insert item authority Y.Nakao 2011/12/02 --end--
                } else {
                    // GENERAL
                    // アイテム投稿権限なし there is not insert right
                    $this->outputError("ErrorCheckInsertAuthority", "Not found this user's authority. Login id : ".$this->insert_user);
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Not found this user's authority. Login id : ".$this->insert_user." (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    exit();
                }
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 4: Check insert_auth_ids. insert_auth_ids: ".$insert_auth_ids." (".date("Y/m/d H:i:s").")\n");
                }
            }
            
            // check request parameter
            if(strlen($this->checkedIds) == 0 || intval($this->checkedIds) < 0){
                // insert index root
                // check index name = "import" from parent index is root
                $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index ".
                         "WHERE index_name = 'import' ".
                         "AND parent_index_id = '0' ".
                        " AND is_delete = 0; ";
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "  Execute query: ". $query."\n");
                }
                $result = $this->Db->execute( $query );
                if($result === false){
                    // not select index
                    $this->outputError("ErrorCheckIndex", "MySQL ERROR : For search index name = 'import'.");
                    $this->removeDirectory($tmp_dir);
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "MySQL ERROR : For search index name = 'import' (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    exit();
                }
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "    Complete execute query.\n");
                }
                
                $now_date = explode(" ", $this->TransStartDate);
                $pubDate = $now_date[0];
                
                if(count($result)==0){
                    $index = array(
                                "index_name"              => "import",
                                "index_name_english"      => "import",
                                "parent_index_id"         => "0",
                                "comment"                 => "",
                                "public_state"            => "1",
                                "pub_date"                => $pubDate,
                                "access_role"             => $insert_auth_ids."|".$this->Session->getParameter("_auth_id"),
                                "access_group"            => "",
                                "exclusive_acl_role_id"   => 0,
                                "exclusive_acl_room_auth" => -1,
                                "exclusive_acl_group_id"  => '',
                                "display_more"            => "",
                                "rss_display"             => 0,
                                "repository_id"           => "",
                                "set_spec"                => "",
                                "create_cover_flag"       => 0,
                                "harvest_public_state"    => 1,
                            );
                            
                    $repositoryIndexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
                    
                    $result = $repositoryIndexManager->addIndex(false, $index, $errorMsg, $this->logFh);
                    
                    if($result === false){
                        $this->outputError("ErrorInsertIndex", $error_msg." insert index name : import");
                        $this->removeDirectory($tmp_dir);
                        if(isset($this->logFh))
                        {
                            fwrite($this->logFh, $error_msg." insert index name : import (".date("Y/m/d H:i:s").")\n");
                            fclose($this->logFh);
                        }
                        exit();
                    }
                    $this->checkedIds = $result;
                } else {
                    $this->checkedIds = $result[0]["index_id"];
                }
            } else if(!is_numeric($this->checkedIds)){
                $this->outputError("ErrorUnknown", "Not fill import root index id.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed: Not fill import root index id. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 5: Checked Index id is '".$this->checkedIds."'. (".date("Y/m/d H:i:s").")\n");
            }
            
            /////////////////////////////////////////
            // insert index and select check index
            /////////////////////////////////////////
            $file_path = WEBAPP_DIR. "/uploads/repository/".$this->filename.".xml";
            if(file_exists($file_path)){
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 6: Before parse XML '".$file_path."'. (".date("Y/m/d H:i:s").")\n");
                }
                /////////////// XML perser ///////////////
                try{
                    $content = file_get_contents($file_path);
                    $xml_parser = xml_parser_create();
                    $rtn = xml_parse_into_struct( $xml_parser, $content, $vals );
                    if($rtn == 0){
                        $this->outputError("ErrorXML", "Can't Separate XML");
                        if(isset($this->logFh))
                        {
                            fwrite($this->logFh, "Failed: Can't Separate XML. (".date("Y/m/d H:i:s").")\n");
                            fclose($this->logFh);
                        }
                        exit();
                    }
                    xml_parser_free($xml_parser);
                } catch(Exception $ex){
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Failed: Exception occurrd in XML parse. (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    return false;
                }
                /////////////// XML analysis ///////////////
                $index_info = array();
                $index_id = -1;
                foreach($vals as $val){
                    switch($val['tag']){
                        case "RDF:RDF":
                            break;
                        case "RDF:DESCRIPTION":
                            if($val['type'] == "open"){
                                $index_id = 0;
                            }else if($val['type'] == "close"){
                                $index_id = -1;
                            }
                            break;
                        case "DC:IDENTIFIER":
                            if($index_id >= 0){
                                if(!isset($index_info[$val['value']])){
                                    $index_id = $val['value'];
                                    $index_info[$index_id] = array();
                                    $index_info[$index_id]['id'] = $val['value'];
                                    $index_info[$index_id]['pid'] = $this->checkedIds;
                                    $index_info[$index_id]['title'] = "";
                                    $index_info[$index_id]['title_en'] = "";
                                    $index_info[$index_id]['comment'] = "";
                                    $index_info[$index_id]['pub_date'] = "";
                                    $index_info[$index_id]['more'] = "";
                                    $index_info[$index_id]['rss'] = "";
                                    $index_info[$index_id]['pid_chk'] = false;
                                } else {
                                    $this->outputError("ErrorXML", "XML description is NG");
                                    exit();
                                }
                            }
                            break;
                        case "DC:TITLE":
                            if($index_id >= 0){
                                if($val['attributes']['XML:LANG'] == "ja"){
                                    $index_info[$index_id]['title'] = $val['value'];
                                } else if($val['attributes']['XML:LANG'] == "en"){
                                    $index_info[$index_id]['title_en'] = $val['value'];
                                } else {
                                    $index_info[$index_id]['title'] = $val['value'];
                                    $index_info[$index_id]['title_en'] = $val['value'];
                                }
                            }
                            break;
                        case "DC:COMMENT":
                            $index_info[$index_id]['comment'] = $val['value'];
                            break;
                        case "DC:PUBDATE":
                            $index_info[$index_id]['pub_date'] = $val['value'];
                            break;
                        case "DC:MORE":
                            if(is_numeric($val['value'])){
                                $index_info[$index_id]['more'] = intval($val['value']);
                            }
                            break;
                        case "DC:RSS":
                            $index_info[$index_id]['rss'] = $val['value'];
                            break;
                        case "DC:RELATION":
                            if($index_id >= 0){
                                $index_info[$index_id]['pid'] = $val['value'];
                                $index_info[$index_id]['pid_chk'] = true;
                            }
                            break;
                        default:
                            break;
                    }
                }
                /////////////// insert index ///////////////
                $pid_list = array();    // parent index id list
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 7: Create index. (".date("Y/m/d H:i:s").")\n");
                }
                foreach ($index_info as $key => $val){
                    // check list
                    if($val['pid_chk'] && isset($pid_list[$val['pid']])){
                        $val['pid'] = $pid_list[$val['pid']];
                    }
                    
                    $repositoryIndexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
                    
                    if (strlen($val['pub_date']) > 0){
                        $pubDate = RepositoryOutputFilter::zeroPaddingDate($val['pub_date']);
                        if ($pubDate == -1 || $pubDate == -2) {
                            $errorMsg = "Pub date for new index is wrong. Pub date :" + $val['pub_date'];
                            $pubDate = false;
                        } 
                    } else {
                        $now_date = explode(" ", $this->TransStartDate);
                        $pubDate = $now_date[0];
                    }
                    
                    if ($pubDate != false) {
                        $pubDate .= " 00:00:00.000";
                        $index = array(
                            "index_name"              => $val['title'],
                            "index_name_english"      => $val['title_en'],
                            "parent_index_id"         => $val['pid'],
                            "comment"                 => $val['comment'],
                            "public_state"            => "1",
                            "pub_date"                => $pubDate,
                            "access_role"             => $insert_auth_ids."|".$this->Session->getParameter("_auth_id"),
                            "access_group"            => "",
                            "exclusive_acl_role_id"   => 0,
                            "exclusive_acl_room_auth" => -1,
                            "exclusive_acl_group_id"  => '',
                            "display_more"            => "",
                            "rss_display"             => 0,
                            "repository_id"           => "",
                            "set_spec"                => "",
                            "create_cover_flag"       => 0,
                            "harvest_public_state"    => 1
                        );
                    
                        $index_id = $repositoryIndexManager->addIndex(false, $index, $errorMsg, $this->logFh);
                    }
                    if(!empty($errorMsg)){
                        if(isset($this->logFh))
                        {
                            fwrite($this->logFh, "Failed: Failed insert index. (".date("Y/m/d H:i:s").")\n");
                            fwrite($this->logFh, "  ErrorMsg: ".$error_msg."\n");
                            foreach($val as $tmpKey => $tmpVal)
                            {
                                fwrite($this->logFh, "  ".$tmpKey.": ".$tmpVal."\n");
                            }
                            fclose($this->logFh);
                        }
                        $this->outputError("ErrorInsertIndex", "Failed in insert index. name:".$val['title']);
                        exit();
                    }
                    // add list
                    if(!isset($pid_list[$val['id']])){
                        $pid_list[$val['id']] = $index_id;
                    }
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "  [index_id: ".$key."]\n");
                        foreach($val as $tmpKey => $tmpVal)
                        {
                            fwrite($this->logFh, "    ".$tmpKey.": ".$tmpVal."\n");
                        }
                    }
                }
            }
            // Bugfix xml not exists 2011/06/06 Y.Nakao --start--
             else {
                $this->outputError("ErrorXML", "Can't Separate XML");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed: Can't Separate XML. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            // Bugfix xml not exists 2011/06/06 Y.Nakao --end--
            $result = $this->exitAction();   //if transaction success, do commit
            if ( $result === false) {
                $this->outputError("ErrorUnknown", "Failed in exit action");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed: Failed in exit action. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            
            // delete file
            if($this->deleteUploadFile)
            {
                if(file_exists($file_path)){
                    unlink($file_path);
                }
            }
            
            ////////// make return XML ///////////
            // header
            header("Content-Type: text/xml; charset=utf-8");
            // XML
            $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
            $ret_xml .= '<result>';
            $ret_xml .= '<status>success</status>';
            $ret_xml .= '</result>';
            
            print $ret_xml;
            
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "\nSWORD index import completed. (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }
            
            exit();
            
        } catch (Exception $ex){
            $this->outputError("ErrorUnknown", "Failed in import action");
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Exception occurred: ".$ex->getMessage()." (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }
            exit();
        }
    }

    /**
     * get New Index ID
     * 
     * transplant from getNewId function in tree_repository.js 
     *
     */
    function getNewIndexID(){
        if(isset($this->logFh))
        {
            fwrite($this->logFh, "-- Start getNewIndexID --\n");
        }
        // get ID list
        $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index; ";
        if(isset($this->logFh))
        {
            fwrite($this->logFh, "  Execute query: ". $query."\n");
        }
        $result = $this->Db->execute($query);
        if($result === false){
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "  NewIndexID is none. Query is failed: ".$this->Db->ErrorMsg()."\n");
                fwrite($this->logFh, "-- End getNewIndexID --\n");
            }
            return "";
        }
        if(isset($this->logFh))
        {
            fwrite($this->logFh, "    Complete execute query.\n");
        }
        
        $cnt_id = 1;
        $len = count($result);
        for ( $ii=0; $ii<$len+2; $ii++ ) {
            $flag=0;
            for ( $jj=0; $jj<$len; $jj++ ) {
                $node_id = $result[$jj]["index_id"];
                if ( $node_id == $cnt_id )
                {
                    $flag = 1;
                    break;
                }
            }
            if ($flag == 0) {
                fwrite($this->logFh, "  NewIndexID is '".$cnt_id."'.\n");
                fwrite($this->logFh, "-- End getNewIndexID --\n");
                return $cnt_id;
            }
            $cnt_id++;
        }
        if(isset($this->logFh))
        {
            fwrite($this->logFh, "  NewIndexID is '-1'.\n");
            fwrite($this->logFh, "-- End getNewIndexID --\n");
        }
        return -1;
    }
    
    /**
     * output error xml
     */
    function outputError($error_msg, $summary){
        // header
        header("Content-Type: text/xml; charset=utf-8");
        // XML
        $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $ret_xml .= '<result>';
        $ret_xml .= '<status>'. $error_msg .'</status>';
        $ret_xml .= '<summary>'. $summary .'</summary>';
        $ret_xml .= '</result>';
        
        print $ret_xml;
        
        return; 
    }
}
?>
