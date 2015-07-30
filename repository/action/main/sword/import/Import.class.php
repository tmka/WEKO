<?php
// --------------------------------------------------------------------
//
// $Id: Import.class.php 42307 2014-09-29 06:18:07Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * **********************************************
 * this action is called by outside
 * this action make for SWORD action
 * so on WEKO action must not call this action
 * **********************************************
 */
class Repository_Action_Main_Sword_Import extends RepositoryAction
{
    // component
    var $Session = null;
    var $Db = null;

    // request param
    var $checkedIds = null;     // delimiter is "|"
                                // ins_idx_id|ins_idx_id|ins_idx_id|...
    var $filename_zip = null;   // upload zip file name
    var $newIndex = null;       // delimiter is "," and "|"
                                // pid,name,pubdate|pid,name,pubdate|...
    var $insert_user = null;    // login_id of insert user

    public $login_id = null;    // login_id
    public $password = null;    // password

    // member
    var $index_id = array();    // insert index list


    // Add review mail setting 2009/09/30 Y.Nakao --start--
    var $mailMain = null;
    // Add review mail setting 2009/09/30 Y.Nakao --end--

    private $logFh = null;                  // File handle for log
    private $isCreateLog = true;            // default: true
    private $isAddDateToLogName = false;    // default: false
    private $deleteUploadFile = true;       // default: true

    /**
     * construct
     *
     * @param SessionObject $session
     * @param DbObject $db
     * @param string $transStartDate
     * @return class
     */
    public function __construct($session = null, $db = null, $transStartDate = null)
    {
        // Call at other action, don't run execcute method.
        // so set components.
        if(isset($session))
        {
            $this->Session = $session;
        }
        if(isset($db))
        {
            $this->Db = $db;
            $this->dbAccess = new RepositoryDbAccess($this->Db);
        }
        if(isset($transStartDate))
        {
            $this->TransStartDate = $transStartDate;
        }
    }

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
                $logName = WEBAPP_DIR."/logs/weko/sword/sword_import_log.txt";
                if($this->isAddDateToLogName)
                {
                    // Add date to logName
                    $logName = WEBAPP_DIR."/logs/weko/sword/sword_import_log_".date("YmdHis").".txt";
                }
                $this->logFh = fopen($logName, "w");
                chmod($logName, 0600);
                fwrite($this->logFh, "Start SWORD import. (".date("Y/m/d H:i:s").")\n");
                fwrite($this->logFh, "\n");
                fwrite($this->logFh, "[Request parameters]\n");
                fwrite($this->logFh, "  checkedIds: ".$this->checkedIds."\n");
                fwrite($this->logFh, "  filename_zip: ".$this->filename_zip."\n");
                fwrite($this->logFh, "  newIndex: ".$this->newIndex."\n");
                fwrite($this->logFh, "  insert_user: ".$this->insert_user."\n");
                fwrite($this->logFh, "  login_id: ".$this->login_id."\n");
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
                $this->failTrans();
                $this->exitAction();
                exit();
            }

            // Add check login 2012/02/16 Y.Nakao --start--
            if(strlen($this->login_id)==0 || strlen($this->password)==0)
            {
                $this->outputError("RequestParameterIsEmpty", "Request param error : Not login user.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed: login_id or password is empty. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
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
                    $this->failTrans();
                    $this->exitAction();
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
                $this->failTrans();
                $this->exitAction();
                exit();
            }
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 2: Login check OK. (".date("Y/m/d H:i:s").")\n");
            }
            // Add check login 2012/02/16 Y.Nakao --end--

            // check request parameter
            // Fix check index_id Y.Nakao 2013/06/07 --start--
            if( strlen($this->checkedIds) > 0)
            {
                // チェックインデックスが存在しないインデックスの場合は除外する
                $indexIds = explode('|', $this->checkedIds);
                $this->checkedIds = "";
                for($ii=0; $ii<count($indexIds); $ii++)
                {
                    if( $this->existsIndex( intval($indexIds[$ii]) ) )
                    {
                        // exists index
                        if(strlen($this->checkedIds) > 0)
                        {
                            $this->checkedIds .= "|";
                        }
                        $this->checkedIds .= $indexIds[$ii];
                    } else if(intval($indexIds[$ii]) != -1){
                        // Add for error check 2014/09/12 T.Ichikawa --start--
                        $error_list = array();
                        $error_list[] = new DetailErrorInfo(0, "", "Index is not find");
                        $this->outputError("IndexToPostIsNothing", "Index is not find.", $error_list);
                        if(isset($this->logFh))
                        {
                            fwrite($this->logFh, "Not find index. (".date("Y/m/d H:i:s").")\n");
                            fclose($this->logFh);
                        }
                        // Add for error check 2014/09/12 T.Ichikawa --end--
                    }
                }
            }
            // Fix check index_id Y.Nakao 2013/06/07 --start--
            if(strlen($this->checkedIds) == 0 && strlen($this->newIndex) == 0)
            {
                $this->newIndex = "-1,import-".date("Y-m-dTH:i:sZ");
            }

            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Validator check index id and new index.\n");
                fwrite($this->logFh, "  checkedIds : ".$this->checkedIds."\n");
                fwrite($this->logFh, "  newIndex : ".$this->newIndex."\n");
            }

            if(strlen($this->filename_zip) == 0){
                $this->outputError("RequestParameterIsEmpty", "File name is null");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "File name is null. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }
            if(strlen($this->insert_user) == 0){
                $this->outputError("RequestParameterIsEmpty", "Not fill insert user's login id.");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Not fill insert user's login id. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            } else {

                // Fix check index authority 2013/06/12 Y.Nakao --start--
                // 登録者(ログインユーザーまたは代理投稿者)の情報をセッションに保存
                $result = $this->setSessionUserAuthority($this->insert_user);
                if($result === false)
                {
                    // error user data.
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                // Fix check index authority 2013/06/12 Y.Nakao --end--

                $user_auth_id = $this->Session->getParameter("_user_auth_id");
                $auth_id = $this->Session->getParameter("_auth_id");
                // Fix check index_id Y.Nakao 2013/06/07 --end--
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

                // Modify sword import authority. change authority weko admin => item authority. -Y.Nakao--start--
                if($auth_id >= REPOSITORY_ITEM_REGIST_AUTH)
                {
                    $insert_auth_ids = $this->getInsertAuthIds();
                } else {
                    // アイテム登録権限なし
                    // Add for error check 2014/09/12 T.Ichikawa --start--
                    $error_list = array();
                    $error_list[] = new DetailErrorInfo(0, "", "Not found this user's authority. Login id : ".$this->insert_user);
                    $this->outputError("ErrorCheckInsertAuthority", "Not found this user's authority. Login id : ".$this->insert_user, $error_list);
                    // Add for error check 2014/09/12 T.Ichikawa --end--
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Not found this user's authority. Login id : ".$this->insert_user." (".date("Y/m/d H:i:s").")\n");
                        fclose($this->logFh);
                    }
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                // Modify sword import authority. change authority weko admin => item authority. Y.Nakao --end--

                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 4: Check insert_auth_ids. insert_auth_ids: ".$insert_auth_ids." (".date("Y/m/d H:i:s").")\n");
                }
            }
            // upload zip file extract folder pass
            $tmp_dir = $this->extraction($this->filename_zip);
            if($tmp_dir == false){
                // not zip error
                $this->outputError("RequestParameterIsEmpty", "Not found zip file. File name : ".$this->filename_zip.".zip");
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Not found zip file. File name : ".$this->filename_zip.".zip (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
                exit();
            }

            /////////////////////////////////////////
            // insert index and select check index
            /////////////////////////////////////////

            // Fix auto private index for contributor 2013/07/08 Y.Nakao --start--
            $insUserId = $this->Session->getParameter("_user_id");
            // Fix auto private index for contributor 2013/07/08 Y.Nakao --end--

            // Fix check index authority 2013/06/12 Y.Nakao --start--
            // この時点でのセッション情報=登録者(ログインユーザーまたは代理投稿者)の情報となっている。
            // インデックス操作はログインユーザーの権限に準拠するため、一旦セッションの情報を上書きする
            if($this->login_id != $this->insert_user)
            {
                $result = $this->setSessionUserAuthority($this->login_id);
                if($result === false)
                {
                    // error user data.
                    if($writeLog && isset($this->logFh))
                    {
                        fwrite($this->logFh, "Check Point 5-Z: check login user data.\n");
                    }
                    return false;
                }
            }
            // Fix check index authority 2013/06/12 Y.Nakao --end--

            $result = $this->insertNewIndexAndSelectCheckIndex(
                             $this->newIndex, $this->checkedIds, $this->index_id, $error_msg, $insUserId);
            if($result===false)
            {
                // Error
                $this->outputError("ErrorCheckIndex", $error_msg);
                $this->removeDirectory($tmp_dir);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, $error_msg." (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
                exit();
            }

            // 登録者(ログインユーザーまたは代理投稿者)の情報をセッションに保存
            $result = $this->setSessionUserAuthority($this->insert_user);
            if($result === false)
            {
                // error user data.
                $this->failTrans();
                $this->exitAction();
                exit();
            }
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->Session->getParameter("_auth_id");
            // Fix check index authority 2013/06/12 Y.Nakao --end--


            // Add review mail setting 2009/09/30 Y.Nakao --start--
            // get mail conponent
            // メール送信用クラス生成
            if($this->mailMain== null){
                $this->mailMain = new Mail_Main();
            }
            // 言語リソースは取得できない(VIEWアクションを通過しないため)
            /////////////////////////////////////////
            // check send review mail
            /////////////////////////////////////////
            // 新規査読アイテム登録メール送信処理
            // 査読・承認を行うか否か
            $query = "SELECT `param_value` ".
                     "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                     "WHERE `param_name` = 'review_mail_flg';";
            $ret = $this->Db->execute($query);
            if ($ret === false) {
                array_push($error_msg, $this->Db->ErrorMsg());
                // roll back
                $this->failTrans();
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed get review_mail_flg. ".$this->Db->ErrorMsg()." (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                $this->exitAction();
                exit();
            }
            $review_mail_flg = $ret[0]['param_value'];
            // 査読対象コンテンツ有無
            $review_flg = false;
            // get lang
            $lang = $this->Session->getParameter("_lang");
            // 件名
            // set subject
            if($lang == "japanese"){
                $subj = "査読対象コンテンツ登録通知";
            } else {
                $subj = "Review contents notification";
            }
            $this->mailMain->setSubject($subj);

            // page_idおよびblock_idを取得
            $block_info = $this->getBlockPageId();
            // set Mail body
            $body = '';
            if($lang == "japanese"){
                $body .= "査読対象となるコンテンツが登録されたのでお知らせいたします。"."\n\n";
                $body .= "■査読対象のコンテンツ\n";
                $body .= "title : ";
            } else {
                $body .= "I will inform you that contents to be reviewed were registered."."\n\n";
                $body .= " * Review contents\n";
                $body .= "title : ";
            }
            // Add review mail setting 2009/09/30 Y.Nakao --end--


            /////////////////////////////////////////
            // import item
            /////////////////////////////////////////
            // import common class new
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 7: Before create ImportCommon class. (".date("Y/m/d H:i:s").")\n");
            }
            $import_common = new ImportCommon($this->Session, $this->Db, $this->TransStartDate);
            // get XML data
            if(isset($this->logFh))
            {
                $import_common->setLogFileHandle($this->logFh);
                fwrite($this->logFh, "Check Point 8: Before call 'XMLAnalysis' method in ImportCommon class. (".date("Y/m/d H:i:s").")\n");
            }
            $error_list = array();
            $return = $import_common->XMLAnalysis($tmp_dir, $array_item_data, $error_list);
            if($return === false){
                $this->outputError("ErrorXML", "ERROR XML : XML for WEKO import is wrong.", $error_list);
                $this->removeDirectory($tmp_dir);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "ERROR XML : XML for WEKO import is wrong. (".date("Y/m/d H:i:s").")\n");

                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
                exit();
            }
            //////////////////////////////
            // Insert item type
            //////////////////////////////
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 9: Before call 'itemtypeEntry' method in ImportCommon class. (".date("Y/m/d H:i:s").")\n");
            }
            $return = $import_common->itemtypeEntry($array_item_data['item_type'], $tmp_dir, $item_type_info, $error_msg);
            if($return === false){
                $this->outputError("ErrorInsertItemType", $error_msg);
                $this->removeDirectory($tmp_dir);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Item type insert is not complete. ".$error_msg." (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
                exit();
            }
            // check itemtype num item num
            if(count($item_type_info) != count($array_item_data['item'])){
                $this->outputError("ErrorInsertItemType", "Item type insert is not complete.");
                $this->removeDirectory($tmp_dir);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Item type insert is not complete. No match itemtypes num and items num. (".date("Y/m/d H:i:s").")\n");
                    fwrite($this->logFh, "  Itemtype count: ".count($item_type_info)." / Items count: ".count($array_item_data['item'])."\n");
                    fclose($this->logFh);
                }
                $this->failTrans();
                $this->exitAction();
                exit();
            }

            ////////////////////////////////////////
            // insert item
            ////////////////////////////////////////
            $tmp_array = array();
            $array_item = array();
            $error_msg = array();
            $start_item_id = "";
            $end_item_id = "";
            $detail_uri = array();
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 10: Before call 'itemEntry' method in ImportCommon class. (".date("Y/m/d H:i:s").")\n");
                fwrite($this->logFh, "  item count: ".count($array_item_data['item'])."\n");
            }
            for($nCnt=0;$nCnt<count($array_item_data['item']);$nCnt++){
                // insert 1 item
                $msg = "";
                $ret = $import_common->itemEntry($array_item_data['item'][$nCnt],
                                                $tmp_dir,
                                                $array_item,
                                                $this->index_id,
                                                $item_type_info[$nCnt],
                                                $array_item_data['item_type'][$nCnt],
                                                $msg,
                                                $ins_item_id,
                                                $uri);
                if($ret === false){
                    $this->outputError("ErrorInsertItem", $msg);
                    $this->removeDirectory($tmp_dir);
                    if(isset($this->logFh))
                    {
                        fwrite($this->logFh, "Insert item error. (".date("Y/m/d H:i:s").")\n");
                        fwrite($this->logFh, "  count: ".$nCnt." / message: ".$msg."\n");
                        fclose($this->logFh);
                    }
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }

                array_push($error_msg, $msg);
                if($start_item_id == ""){
                    $start_item_id = $ins_item_id;
                }
                $end_item_id = $ins_item_id;

                // return detail URL is "[BASE_URL]/?action=repository_uri&item_id=xxx".
                // Not "http://id.nii.ac.jp/xxxx/xxxxxxxx/"
                $tmpUri = "";
                if($array_item[$nCnt]["status"] == "success")
                {
                    $tmpUri = BASE_URL . "/?action=repository_uri&item_id=".$ins_item_id;
                }
                array_push($detail_uri, $tmpUri);

                // Add review mail setting 2009/09/30 Y.Nakao --start--
                // 査読対象のコンテンツ情報をメール本文に記載する
                // write mail body to revire contents information
                if($array_item[$nCnt]["review_status"] == 0){
                    $review_flg = true;
                    if($this->Session->getParameter("_lang") == "japanese"){
                        if(strlen($array_item[$nCnt]["title"]) > 0){
                            $body .= $array_item[$nCnt]["title"];
                        } else if(strlen($array_item[$nCnt]["title_english"]) > 0){
                            $body .= $array_item[$nCnt]["title_english"];
                        } else {
                            $body .= "no title";
                        }
                    } else {
                        if(strlen($array_item[$nCnt]["title_english"]) > 0){
                            $body .= $array_item[$nCnt]["title_english"];
                        } else if(strlen($array_item[$nCnt]["title"]) > 0){
                            $body .= $array_item[$nCnt]["title"];
                        } else {
                            $body .= "no title";
                        }
                    }
                    $body .= "\n";
                    if($lang == "japanese"){
                        $body .= "詳細画面URL : ".$uri."\n";
                    } else {
                        $body .= "Abstract URL : ".$uri."\n";
                    }
                    $body .= "\n\n";
                }
                // Add review mail setting 2009/09/30 Y.Nakao --end--
            }
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 11: Check error_msg in itemEntry. (".date("Y/m/d H:i:s").")\n");
                if(count($error_msg) == 0)
                {
                    fwrite($this->logFh, "  No error in itemEntry!.\n");
                }
                else
                {
                    $tmpErrMsg = "";
                    foreach($error_msg as $tmpMsg)
                    {
                        if(strlen($tmpMsg) > 0)
                        {
                            $tmpErrMsg .= "  ".$tmpMsg."\n";
                        }
                    }
                    if(strlen($tmpErrMsg) == 0)
                    {
                        $tmpErrMsg = "  No error in itemEntry!.\n";
                    }
                    fwrite($this->logFh, $tmpErrMsg);
                }
            }

            // end action
            // COMMIT
            $result = $this->exitAction();
            if ( $result == false ){
                $this->outputError("ErrorUnknown", "Failed in import action");
                $this->removeDirectory($tmp_dir);
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Failed call exitAction. (".date("Y/m/d H:i:s").")\n");
                    fclose($this->logFh);
                }
                exit();
            }

            // del work dir
            $this->removeDirectory($tmp_dir);

            // Add review mail setting 2009/09/30 Y.Nakao --start--
            // set Mail body
            if($lang == "japanese"){
                $body .= "査読・承認はこちらから行ってください。";
            } else {
                $body .= "Please go here in screening and approval.";
            }
            $body .= BASE_URL;
            if(substr(BASE_URL,-1,1) != "/"){
                $body .= "/";
            }
            $body .= "?active_action=repository_view_edit_review&page_id=".$block_info["page_id"]."&block_id=".$block_info["block_id"];
            $body .= "\n";
            if($lang == "japanese"){
                $body .= "\n\n"."査読通知メールの受信が不要になった場合は、お手数ですが管理者までご連絡ください。";
            } else {
                $body .= "\n\n"."Please contact the manager when the notification becomes unnecessary.";
            }
            $this->mailMain->setBody($body);
            // ---------------------------------------------
            // 送信メール情報取得
            //   送信者のメールアドレス
            //   送り主の名前
            //   送信先ユーザを取得
            // create mail body
            //   get send from user mail address
            //   get send from user name
            //   get send to user
            // ---------------------------------------------
            $users = array();
            $this->getReviewMailInfo($users);
            if($review_flg && $review_mail_flg==1 && count($users) > 0){
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 12-A1: Before send ReviewMail. (".date("Y/m/d H:i:s").")\n");
                    fwrite($this->logFh, "  email: ".$users["email"]."\n");
                    fwrite($this->logFh, "  handle: ".$users["handle"]."\n");
                    fwrite($this->logFh, "  type: ".$users["type"]."\n");
                    fwrite($this->logFh, "  lang_dirname: ".$users["lang_dirname"]."\n");
                }
                // ---------------------------------------------
                // 送信先を設定
                // set send to user
                // ---------------------------------------------
                // 送信ユーザを設定
                // $usersの中身
                // $users["email"] : 送信先メールアドレス
                // $user["handle"] : ハンドルネーム
                //                   なければ空白が自動設定される
                // $user["type"]   : type (html(email) or text(mobile_email))
                //                   なければhtmlが自動設定される
                // $user["lang_dirname"] : 言語
                //                         なければ現在の選択言語が自動設定される
                $this->mailMain->setToUsers($users);
                // ---------------------------------------------
                // メール送信
                // send confirm mail
                // ---------------------------------------------
                // 送信ユーザがいる場合送信
                $return = $this->mailMain->send();
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 12-A2: After send ReviewMail. (".date("Y/m/d H:i:s").")\n");
                }
            }
            else
            {
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "Check Point 12-B: Not send ReviewMail. (".date("Y/m/d H:i:s").")\n");
                }
            }
            // Add review mail setting 2009/09/30 Y.Nakao --end--

            ////////// make return XML ///////////
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 13: Return xml data. (".date("Y/m/d H:i:s").")\n");
                fwrite($this->logFh, "  start_item_id: ".$start_item_id."\n");
                fwrite($this->logFh, "  end_item_id: ".$end_item_id."\n");
            }
            // header
            header("Content-Type: text/xml; charset=utf-8");
            // XML
            $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
            $ret_xml .= '<result>';
            $ret_xml .= '<status>success</status>';
            $ret_xml .= '<start_id>'.$start_item_id.'</start_id>';
            $ret_xml .= '<end_id>'.$end_item_id.'</end_id>';
            // detail uri list
            for($ii=0; $ii<count($detail_uri); $ii++){
                $ret_xml .= '<contents_uri>'.htmlspecialchars($detail_uri[$ii], ENT_QUOTES, 'UTF-8').'</contents_uri>';
                if(isset($this->logFh))
                {
                    fwrite($this->logFh, "  contents_uri: ".$detail_uri[$ii]."\n");
                }
            }
            $ret_xml .= '</result>';

            print $ret_xml;

            if(isset($this->logFh))
            {
                fwrite($this->logFh, "\nSWORD import completed. (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }

            exit();

        } catch (Exception $ex){
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Exception occurred: ".$ex->getMessage()." (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }
            $this->outputError("ErrorUnknown", "Failed in import action");
            $this->failTrans();
            $this->exitAction();
            exit();
        }
    }

    /*
     * zip file extract
     */
    function extraction($tmp_file){

        // check file kind
        //if($tmp_file != "zip"){
        //  return false;
        //}
        // make file path
        $dir_path = WEBAPP_DIR. "/uploads/repository/";
        $file_path = $dir_path . $tmp_file . ".zip";
        if(!file_exists($file_path)){
            return false;
        }

        // make dir for extract
        $dir = $dir_path . $tmp_file;
        if(file_exists($dir)){
            $this->removeDirectory($dir);
        }
        mkdir($dir, 0777);

        // extract zip file
        File_Archive::extract(
        File_Archive::read($file_path . "/"),
        File_Archive::appender($dir)
        );

        // delete upload file
        if($this->deleteUploadFile)
        {
            unlink($file_path);
        }

        return $dir;
    }

    /**
     * get New Index ID
     *
     * transplant from getNewId function in tree_repository.js
     *
     */
    function getNewIndexID(){
        // get ID list
        $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index; ";
        $result = $this->Db->execute($query);
        if($result == false){
            return "";
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
                return $cnt_id;
            }
            $cnt_id++;
        }
        return -1;
    }

    /**
     * output error xml
     */
    function outputError($error_msg, $summary, $error_list=array()){
        // header
        header("Content-Type: text/xml; charset=utf-8");
        // XML
        $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $ret_xml .= '<result>';
        $ret_xml .= '<status>'. $error_msg .'</status>';
        $ret_xml .= '<summary>'. $summary .'</summary>';
        // Add for error check 2014/09/12 T.Ichikawa --start--
        if(count($error_list) > 0) {
            // sword treatment
            $ret_xml .= '<treatment>XML Analysis Failed</treatment>';
            // sword description
            $description = "";
            for($ii = 0; $ii < count($error_list); $ii++) {
                $description .= "ERROR: ".$error_list[$ii]->error." ";
                if($error_list[$ii]->item_id > 0) {
                    $description .= "at Item ID ".$error_list[$ii]->item_id.";";
                }
            }
            $ret_xml .= '<description>'.$description.'</description>';
        }
        // Add for error check 2014/09/12 T.Ichikawa --end--
        $ret_xml .= '</result>';

        print $ret_xml;

        return;
    }

    /**
     * check access right for insert item
     *
     * @param $access_role access auth's room_id delemit is ","
     * @param $access_group access group's room_id delemit is ","
     * @param $owner_user_id string private tree owner
     */
    function checkAccessIndex($access_role, $access_group, $owner_user_id){

        // Fix check index authority 2013/06/12 Y.Nakao --start--
        $user_id = $this->Session->getParameter("_user_id");
        if(strlen($user_id) > 0 && $user_id == $owner_user_id)
        {
            // private tree
            return true;
        }
        // Fix check index authority 2013/06/12 Y.Nakao --end--

        // Add config management authority 2010/02/23 Y.Nakao --start--
        if(strlen(str_replace(",","",str_replace("|","",$access_role))) == 0 &&
            strlen(str_replace(",","",$access_group)) == 0){
            return false;
        }
        $base_auth = "";
        $room_auth = 0;
        $role = explode("|", $access_role);
        if(count($role) == 0){
            return false;
        } else if(count($role) == 1){
            $base_auth = $role[0];
            $room_auth = _AUTH_CHIEF;
        } else if(count($role) >= 2){
            $base_auth = $role[0].",";
            $room_auth = substr($role[1], 0, 1); // Fix tree insert item authority Y.Nakao 2011/12/02
        }
        if(strlen($room_auth) == 0){
            $room_auth = _AUTH_CHIEF;
        }
        // get user's role auth id
        $query = "SELECT role_authority_id FROM ". DATABASE_PREFIX ."users ".
                 "WHERE user_id = ?; ";
        $params = array();
        $params[] = $user_id;
        $role_auth_id = $this->Db->execute($query, $params);
        if($role_auth_id === false || count($role_auth_id)!=1) {
            return false;
        }
        if(is_numeric(strpos($base_auth, ",".$role_auth_id[0]["role_authority_id"].","))){
            // base authority is OK
            //check room authority
            $auth_id = $this->getRoomAuthorityID();
            if(intval($auth_id) >= intval($room_auth)) {
                return true;
            }
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--

        // get user's entry groups
        $result = $this->getUsersGroupList($groups, $error);
        for($ii=0; $ii<count($groups); $ii++){
            if(is_numeric(strpos($access_group, ",".$groups[$ii]["room_id"].","))){
                return true;
            }
        }
        return false;
    }

    /**
     * Get insert auth ids
     *
     * @return string
     */
    public function getInsertAuthIds()
    {
        $insAuthIds = '';

        // setting item insert user authority
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_ADMIN)
        {
            // _ROLE_AUTH_ADMIN = systemAdmin, 7=admin(not define)
            $insAuthIds .= _ROLE_AUTH_ADMIN.',7';
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_CHIEF)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_CHIEF.','._ROLE_AUTH_CLERK;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_MODERATE)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_MODERATE;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GENERAL)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_GENERAL;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GUEST)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_GUEST;
        }

        return $insAuthIds;
    }

    /**
     * Insert new index and select check index
     *
     * @param string $newIndex
     * @param string $checkedIds
     * @param array $checkedIds
     * @param string $errorMsg
     * @param bool $writeLog
     * @return bool
     */
    public function insertNewIndexAndSelectCheckIndex($newIndex, &$checkedIds, &$indexIds, &$errorMsg, $insUserId, $writeLog=true)
    {
        // Fix check index authority 2013/06/12 Y.Nakao --start--
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->Session->getParameter("_auth_id");

        // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --start--
        if($writeLog && isset($this->logFh))
        {
            fwrite($this->logFh, "        new index = ".$newIndex."\n");
            fwrite($this->logFh, "      check index = ".$checkedIds."\n");
        }

        fwrite($this->logFh, "      insert user id (contributor) = ".$insUserId."\n");

        $chk_index = array();
        if(strlen($checkedIds) > 0)
        {
            $chk_index = explode("|", $checkedIds);
        }
        $indice = array();
        $indice = $this->addPrivateTreeInPositionIndex($indice, $insUserId);
        for($ii=0; $ii<count($indice); $ii++)
        {
            if(!is_numeric(array_search($indice[$ii]['index_id'], $chk_index)))
            {
                array_push($chk_index, $indice[$ii]['index_id']);
            }
        }
        $checkedIds = implode("|", $chk_index);
        if($writeLog && isset($this->logFh))
        {
            fwrite($this->logFh, "        new index = ".$newIndex."\n");
            fwrite($this->logFh, "      check index = ".$checkedIds."\n");
        }
        $this->getAdminParam("is_make_privatetree", $isMakePrivateTree, $errorMsg);
        // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --end--

        /////////////////////////////////////////
        // insert index and select check index
        /////////////////////////////////////////

        if(strlen($newIndex) > 0 && ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) )
        {
            $repositoryIndexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);

            // インデックスの追加は管理者のみ。管理者以外はimportインデックスすら作成不可能とする。
            if($writeLog && isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 5-A: Create new index. (".date("Y/m/d H:i:s").")\n");
            }
            /////////////// make new index ///////////////
            $array_index = array();
            $array_index = explode("|", $newIndex);
            for($ii=0; $ii<count($array_index); $ii++){
                $index_info = explode(",", $array_index[$ii]);
                // $index_info[0] is parent_index_id
                // $index_info[1] is new index name
                if(count($index_info) == 2 || count($index_info) == 3){
                    if(count($index_info) == 2){
                        array_push($index_info, "");
                    }
                    // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --start--
                    // $checkedIdsが空ではない場合、importには所属しない
                    if($isMakePrivateTree=="1" && _REPOSITORY_PRIVATETREE_AUTO_AFFILIATION && $index_info[0]=="-1" && count($checkedIds) > 0)
                    {
                        continue;
                    }
                    // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --end--
                    if($index_info[0]=="-1"){
                        // Add index for general sword criant 2008/11/26 Y.Nakao --start--
                        // check index name = "import" from parent index is root
                        $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index ".
                                 "WHERE index_name = 'import' ".
                                 "AND parent_index_id = '0' ".
                                " AND is_delete = 0; ";
                        $result = $this->Db->execute( $query );
                        if($result === false){
                            // not select index
                            $errorMsg = "MySQL ERROR : For search index name = 'import'.";
                            return false;
                        }
                        if(count($result)==0){
                            $now_date = explode(" ", $this->TransStartDate);
                            $pubDate = $now_date[0]." 00:00:00.000";

                            $index = array(
                                "index_name"              => "import",
                                "index_name_english"      => "import",
                                "parent_index_id"         => "0",
                                "public_state"            => "1",
                                "pub_date"                => $pubDate,
                                "access_role"             => $this->getInsertAuthIds()."|".$this->Session->getParameter("_auth_id"),
                                "access_group"            => "",
                                "exclusive_acl_role_id"   => 0,
                                "exclusive_acl_room_auth" => -1,
                                "exclusive_acl_group_id"  => '',
                                "repository_id"           => "",
                                "set_spec"                => "",
                                "create_cover_flag"       => 0,
                                "harvest_public_state"    => 1
                            );
                            $result = $repositoryIndexManager->addIndex(false, $index, $errorMsg, $this->logFh);

                            if($result === false){
                                $errorMsg = $tmpErrorMsg." insert index name : import";
                                return false;
                            }

                            $index_info[0] = $result;
                        } else {
                            $index_info[0] = $result[0]["index_id"];
                        }
                        // Add index for general sword criant 2008/11/26 Y.Nakao --end--
                    }
                    if(strlen($pub_date) > 0)
                    {
                        $pubDate = RepositoryOutputFilter::zeroPaddingDate($pub_date);
                    }
                    else
                    {
                        $now_date = explode(" ", $this->TransStartDate);
                        $pubDate = $now_date[0]." 00:00:00.000";
                    }
                    if ($pubDate == -1) {
                        $errorMsg = "Pub date for new index is wrong. Pub date :" + $pub_date;
                    } else if ($pubDate == -2) {
                        $errorMsg = "Pub date for new index is wrong. Pub date :" + $pub_date;
                    }
                    else if(!isset($pubDate))
                    {
                        $now_date = explode(" ", $this->TransStartDate);
                        $pubDate = $now_date[0]." 00:00:00.000";
                    }
                    $index = array(
                        "index_name"              => $index_info[1],
                        "index_name_english"      => $index_info[1],
                        "parent_index_id"         => $index_info[0],
                        "public_state"            => "1",
                        "pub_date"                => $pubDate,
                        "access_role"             => $this->getInsertAuthIds()."|".$this->Session->getParameter("_auth_id"),
                        "access_group"            => "",
                        "exclusive_acl_role_id"   => 0,
                        "exclusive_acl_room_auth" => -1,
                        "exclusive_acl_group_id"  => '',
                        "repository_id"           => "",
                        "set_spec"                => "",
                        "create_cover_flag"       => 0,
                        "harvest_public_state"    => 1
                    );
                    $index_id = $repositoryIndexManager->addIndex(false, $index, $errorMsg, $this->logFh);
                    if($index_id === false)
                    {
                        $errorMsg =  $tmpErrorMsg." insert index name : ".$index_info[1];
                        return false;
                    }
                    if($index_id != "")
                    {
                        if($checkedIds != '')
                        {
                            $checkedIds .= "|";
                        }
                        else
                        {
                            $checkedIds = "";
                        }
                        $checkedIds .= $index_id;
                    }
                } else {
                    $errorMsg = "Not fill parent index or new index name";
                    return false;
                }
            }
        }
        else
        {
            if($writeLog && isset($this->logFh))
            {
                fwrite($this->logFh, "Check Point 5-B: No create new index. or deposit user not administrator (".date("Y/m/d H:i:s").")\n");
            }
        }
        /////////////// check select index ///////////////
        $indexIds = array();
        if( $checkedIds != null && $checkedIds != '' ){
            $indexIdList = explode('|', $checkedIds);
        }
        if( count($indexIdList) < 1 ){
            // not select index
            $errorMsg = "Check import index : Not check index for item import.";
            return false;
        }
        // check index id
        // Add Open Depo 2013/12/03 R.Matsuura --start--
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $this->setConfigAuthority();
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Mod OpenDepo 2014/01/31 S.Arata --end--
        // Add Open Depo 2013/12/03 R.Matsuura --end--
        $chk_index = array();
        for($ii=0; $ii<count($indexIdList); $ii++){
            if(strlen($indexIdList[$ii]) == 0){
                continue;
            }
            $query = " SELECT index_id, access_role, access_group, owner_user_id ".
                    " FROM ". DATABASE_PREFIX ."repository_index ".
                     " WHERE index_id = '". $indexIdList[$ii]. "' ".
                     // " AND public_state = '1' ". // 後で閲覧権限および投稿権限を参照するのでここの判定は削除（ゆくゆくは非公開インデックスも指定可能にするため） 2013/06/07 Y.Nakao// TODO
                     " AND is_delete = '0'; ";
            $result = $this->Db->execute( $query );
            if($result === false){
                // not select index
                $errorMsg = "MySQL ERROR : Not Found insert index.";
                return false;
            }
            if(count($result)!=1)
            {
                // インデックスが存在しないので無視。
                $errorMsg .= "  warning : index id is Repetition. index_id = ".$indexIdList[$ii];
                continue;
            }
            // check authority
            // Fix check index_id Y.Nakao 2013/06/07 --start--
            // for access permission（閲覧権限チェック)
            if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // admin
            }
            // Mod OpenDepo 2014/01/31 S.Arata --start--
            else
            {
            	//検索特化対応
            	if(_REPOSITORY_HIGH_SPEED){
	                $publicIndex = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $indexIdList[$ii]);
	                if (count($publicIndex) == 0) {
	                    // this node is not show in index tree
	                    $errorMsg .= " user_auth_id : $user_auth_id / auth_id : $auth_id ";
	                    $errorMsg .= " | index_id = ".$indexIdList[$ii]." => not access permission for read. ";
	                    continue;
	                }
            	}
            }
            // Mod OpenDepo 2014/01/31 S.Arata --end--
            // Fix check index_id Y.Nakao 2013/06/07 --end--
            // for insert (投稿権限チェック)
            $access_role = ",".$result[0]["access_role"].",";
            $access_group = ",".$result[0]["access_group"].",";
            if( $this->checkAccessIndex($access_role, $access_group, $result[0]["owner_user_id"]) ){
                // this user can insert this index
                array_push($chk_index, $indexIdList[$ii]);
            }
            else
            {
                $errorMsg .= " | index_id = ".$indexIdList[$ii]." => not access permission for insert. ";
            }
        }
        if(count($chk_index) <= 0){
            $errorMsg .= "Not found index to this user can import item.";
            return false;
        }

        $indexIds = $chk_index;
        if($writeLog && isset($this->logFh))
        {
            fwrite($this->logFh, "Check Point 6: Import index is '".implode("|", $indexIds)."'. (".date("Y/m/d H:i:s").")\n");
        }

        return true;

        // Fix check index authority 2013/06/12 Y.Nakao --end--
    }


    // Fix check index_id Y.Nakao 2013/06/07 --start--
    /**
     * set session _user_id, _role_authority_id, _user_authority_id, _auth_id
     *
     * @param unknown_type $loginId
     */
    function setSessionUserAuthority($loginId)
    {
        $query = "SELECT user_id, role_authority_id FROM ". DATABASE_PREFIX ."users ".
                 "WHERE login_id = ?; ";
        $params = array();
        $params[] = $loginId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)!=1)
        {
            // is not user
            $this->outputError("ErrorCheckInsertUser", "Not found this user. Login id : ".$loginId);
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Not found this user. Login id : ".$loginId." (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }
            return false;
        }
        $this->Session->setParameter("_user_id", $result[0]["user_id"]);
        $this->Session->setParameter("_role_authority_id", $result[0]["role_authority_id"]);
        // get user user_authority_id
        $query = "SELECT user_authority_id FROM ". DATABASE_PREFIX ."authorities ".
                " WHERE role_authority_id = '".$result[0]["role_authority_id"]."' ";
        $result = $this->Db->execute( $query );
        if($result === false || count($result) != 1)
        {
            // アイテム投稿権限なし there is not insert right
            $this->outputError("ErrorCheckInsertAuthority", "Not found this user's authority. Login id : ".$this->insert_user);
            if(isset($this->logFh))
            {
                fwrite($this->logFh, "Not found this user's authority. Login id : ".$this->insert_user." (".date("Y/m/d H:i:s").")\n");
                fclose($this->logFh);
            }
            return false;
        }

        $user_auth_id = $result[0]["user_authority_id"];
        $this->Session->setParameter("_user_auth_id", $user_auth_id);
        $auth_id = $this->getRoomAuthorityID($result[0]["user_id"]);
        $this->Session->setParameter("_auth_id", $auth_id);

        return true;
    }
    // Fix check index_id Y.Nakao 2013/06/07 --end--

}
?>
