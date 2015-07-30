<?php
// --------------------------------------------------------------------
//
// $Id: Login.class.php 36294 2014-05-27 06:29:14Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * WEKO login check for SWROD
 */
class Repository_Action_Main_Sword_Login  extends RepositoryAction
{
    // request parameter
    var $login_id = null;
    var $password = null;
    var $sword_owner = null;
    
    // container
    var $Session = null;
    var $Db = null;
    
    // set parameter from login validate
    var $user_id = "";
    var $user_authority_id = "";
    var $authority_id = "";
        
    function execute()
    {
        $this->initAction();
        //////////////////////////////////////////
        // check login usesr and owner data
        //////////////////////////////////////////
        // init
        $login_email = "";
        $owner_email = "";
        
        // Add config management authority 2010/02/23 Y.Nakao --start--
        //////////////////////////////////////////
        // set admin authority
        //////////////////////////////////////////
        $this->setConfigAuthority();
        // Add config management authority 2010/02/23 Y.Nakao --end--
        
        /////////////////////////////
        // check request parameter
        /////////////////////////////
        if($this->login_id == "" && $this->login_id == null){
            // request parameter is null
            $this->outputError("RequestParameterError", "Not fill login ID");
            exit();
        }
        if($this->password == "" && $this->password == null){
            // request parameter is null
            $this->outputError("RequestParameterError", "Not fill password");
            exit();
        }
        /////////////////////////////
        // check this user login
        /////////////////////////////
        // get component
        $container =& DIContainerFactory::getContainer();
        if($this->Session == null){
            $this->Session =& $container->getComponent("Session");
        }
        if($this->Db == null){
            $this->Db =& $container->getComponent("DbObject");
        }
        // check login and set user_id, user_authority_id, authority_id etc.
        $result = $this->checkLogin($this->login_id, $this->password);
        if($result === false) {
            // this user is not
            $this->outputError("LoginError", "Login ID or password is wrong.");
            exit();
        }
        // set session login info.
        $this->Session->setParameter("_login_id", $this->login_id);
        $this->Session->setParameter("_user_id", $this->user_id);
        $this->Session->setParameter("_user_auth_id", $this->user_authority_id);
        $this->Session->setParameter("_auth_id", $this->authority_id);
        /////////////////////////////
        // check owner
        /////////////////////////////
        if($this->sword_owner!=null && $this->sword_owner!=""){
            // check login user auth level
            // Add config management authority 2010/02/23 Y.Nakao --start--
            //if($this->user_authority_id < _AUTH_MODERATE){
            if ($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room) {
            // Add config management authority 2010/02/23 Y.Nakao --start--
                // The authority is insufficient.
                $this->outputError("DepositterNotAllowedDeposit", "this user dosen't have authority for import. Login ID : ".$this->login_id);
                exit();
            }
            // get login user's email address
            $result = $this->getEmailAddress($this->user_id, $login_email);
            if($result === false){
                // not user?
                $this->outputError("ErrorUnknown", "Not found this user's e-mail address. Login ID : ".$this->login_id);
                exit();
            }
            ////////// check owner user //////////
            // check this owner is this WEKO's memeber
            $query = "SELECT users.user_id, auth.user_authority_id ".
                     "FROM ". DATABASE_PREFIX ."users AS users, ".
                              DATABASE_PREFIX ."authorities AS auth ".
                     "WHERE auth.role_authority_id = users.role_authority_id ".
                     "AND users.login_id = ? ; ";
            $params = array();
            $params[] = $this->sword_owner;
            $result = $this->Db->execute( $query, $params );
            if(count($result)!=1){
                // not member
                $this->outputError("TargetOwnerNotAlloedDeposit", "Not fount this owner's authority. Login ID : ".$this->sword_owner);
                exit();
            }
            // Add config management authority 2010/02/23 Y.Nakao --start--
            // get room authority
            $block_id = $this->getBlockPageId();
            $query = "SELECT user_authority_id AS authority_id".
                    " FROM ".DATABASE_PREFIX."authorities ".
                    " WHERE role_authority_id = ( ".
                    "   SELECT role_authority_id ".
                    "   FROM ".DATABASE_PREFIX."pages_users_link ".
                    "   WHERE user_id = ? ". 
                    "   AND room_id = ( ".
                    "       SELECT room_id ". 
                    "       FROM ".DATABASE_PREFIX."pages ". 
                    "       WHERE page_id = ? ".
                    "   ) ".
                    " ); ";
            $params = array();
            $params[] = $result[0]["user_id"];
            $params[] = $block_id['page_id'];
            $ret = $this->Db->execute($query, $params);
            if($ret === false){
                $this->outputError("TargetOwnerNotAlloedDeposit", "this owner dosen't entry Item. Login ID : ".$this->sword_owner);
                exit();
            }
            $authId = _AUTH_GUEST;
            if(count($ret) == 1)
            {
                $authId = $ret[0]["authority_id"];
            }
            else if($block_id['space_type'] == _SPACE_TYPE_PUBLIC)
            {
                $authId = $this->getDefaultEntryAuthPublic();
            }
            else
            {
                // The authority is insufficient.
                $this->outputError("TargetOwnerNotAlloedDeposit", "this owner dosen't entry Item. Login ID : ".$this->sword_owner);
                exit();
            }
            // check this owner can insert item or not
            if($authId < REPOSITORY_ITEM_REGIST_AUTH){
            // Add config management authority 2010/02/23 Y.Nakao --end--
                // The authority is insufficient.
                $this->outputError("TargetOwnerNotAlloedDeposit", "this owner dosen't entry Item. Login ID : ".$this->sword_owner);
                exit();
            } else {
                // get owner's email address
                $result = $this->getEmailAddress($result[0]["user_id"], $owner_email);
                if($result === false){
                    // not user?
                    $this->outputError("ErrorUnknown", "Not found this owner's e-mail address. Login ID : ".$this->sword_owner);
                    exit();
                }
            }
        } else {
            // check login user auth level
            // Add config management authority 2010/02/23 Y.Nakao --start--
            if($this->authority_id < REPOSITORY_ITEM_REGIST_AUTH){
            // Add config management authority 2010/02/23 Y.Nakao --end--
                // The authority is insufficient.
                $this->outputError("DepositterNotAllowedDeposit", "this owner dosen't entry Item. Login ID : ".$this->login_id);
                exit();
            } else {
                // get login user's email address
                $result = $this->getEmailAddress($this->user_id, $login_email);
                if($result === false){
                    // not user?
                    $this->outputError("ErrorUnknown", "Not found this owner's e-mail address. Login ID : ".$this->login_id);
                    exit();
                }
            }
        }
        ///////////////////////////////////
        // make return XML
        ///////////////////////////////////
        // header
        header("Content-Type: text/xml; charset=utf-8");
        $ret_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $ret_xml .= '<result>';
        $ret_xml .= '<status>success</status>';
        $ret_xml .= '<login_authority>'.$this->user_authority_id.'</login_authority>';
        if($login_email != ""){
            $ret_xml .= '<login_email>'.$login_email.'</login_email>';
        }
        if($owner_email != ""){
            $ret_xml .= '<owner_email>'.$owner_email.'</owner_email>';
        }
        
        $ret_xml .= '</result>';
        
        print $ret_xml;
        
        $this->exitAction();
        exit();
        
    }
    
    function getEmailAddress($user_id, &$email){
        // init
        $email = "";
        // SQL query for get email address from user_id
        $query =    "SELECT links.content ".
                    "FROM ". DATABASE_PREFIX ."items AS items, ".
                             DATABASE_PREFIX ."users_items_link AS links ".
                    "WHERE items.type = 'email' ".
                    "  AND items.item_id = links.item_id ".
                    "  AND links.user_id = ?; ";
        // get login user's email address
        $params = array();
        $params = $user_id;
        $result = $this->Db->execute( $query, $params );
        if($result === false){
            // not user?
            return false;
        }
        if(count($result) == 1){
            // get email address
            $email = $result[0]["content"];
        }
        
        return true;
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
    
    /**
     * [[アクション初期化処理]]
     *
     * @access  public
     */
    function initAction()
    {
        try {
            //トランザクション開始日時の取得
            //$this->TransStartDate = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
            $DATE = new Date();
            $this->TransStartDate = $DATE->getDate().".000";
            //トランザクション開始処理
            //$this->Db->StartTrans();
            // Add fix login data 2009/07/31 A.Suzuki --start--
            // ログイン情報復元処理
            //$this->fixSessionData();
            // Add fix login data 2009/07/31 A.Suzuki --end--

            // Add config management authority 2010/02/22 Y.Nakao --start--
            //$this->setConfigAuthority();
            // Add config management authority 2010/02/22 Y.Nakao --end--

            // Add theme_name for image file Y.Nakao 2011/08/03 --start--
            //$this->setThemeName();
            // Add theme_name for image file Y.Nakao 2011/08/03 --end--
            //if(!defined("SHIB_ENABLED")){
                //define("SHIB_ENABLED", 0);
            //}
            // Add shiboleth login 2009/03/17 Y.Nakao --start--
            //$this->shib_login_flg = SHIB_ENABLED;
            // Add shiboleth login 2009/03/17 Y.Nakao --end--

            //if(defined("_REPOSITORY_CINII"))
            //{
            //    $this->Session->setParameter("_repository_cinii", _REPOSITORY_CINII);
            //}

            // Add smart phone support T.Koyasu 2012/04/02 -start-
            //if(isset($_SERVER['HTTP_USER_AGENT']))
            //{
            //   $userAgent = $_SERVER['HTTP_USER_AGENT'];
            //    if(preg_match('/Android..*Mobile|iPhone|IEMobile/', $userAgent) > 0){
            //        $this->smartphoneFlg = true;
            //    }
            //}
            // Add smart phone support T.Koyasu 2012/04/02 -end-

            // Add Correspond OpenDepo S.Arata 2013/12/20 --start--
            //if(_REPOSITORY_SMART_PHONE_DISPLAY)
            //{
            //    $this->smartphoneFlg = true;
            //}

            // Add Correspond OpenDepo S.Arata 2013/12/20 --end--

            // Add addin Y.Nakao 2012/07/27 --start--
            //if(get_class($this)!='RepositoryAction' && !isset($this->addin))
            //{
            //    $this->addin = new RepositoryAddinCaller($this);
            //}
            // Add addin Y.Nakao 2012/07/27 --end--
            // Add private tree K.Matsuo 2013/04/08
            //$this->createPrivateTree();

            // Add database access class R.Matsuura 2013/11/12 --start--
            $this->dbAccess = new RepositoryDbAccess($this->Db);
            // Add database access class R.Matsuura 2013/11/12 --end--

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }
    
    /**
     * [[アクション終了処理]]
     *
     * @access  public
     */
    function exitAction()
    {
        try {
            //トランザクション終了処理
            //$this->Db->CompleteTrans();

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }
}

?>
