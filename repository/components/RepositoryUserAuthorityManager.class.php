<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryUserAuthorityManager.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';

class RepositoryUserAuthorityManager extends RepositoryLogicBase
{
    /**
     * initialize
     *
     * @param var $session session
     * @param var $dbAccess dbAccess
     * @param string $transStartDate transStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        parent::__construct($session, $dbAccess, $transStartDate);
    }
    
    function getRoomAuthorityID($user_id = ""){
        if(strlen($user_id) == 0){
            $user_id = $this->Session->getParameter("_user_id");    
        }
        if($user_id != "0"){
            // get room authority
            $block_id = $this->getBlockPageId();
            $query = "SELECT user_authority_id AS authority_id ".
                    " FROM ".DATABASE_PREFIX."authorities ".
                    " WHERE role_authority_id = ( ".
                    "   SELECT role_authority_id ".
                    "   FROM ".DATABASE_PREFIX."pages_users_link ".
                    "   WHERE user_id = '".$user_id."' ". 
                    "   AND room_id = ( ".
                    "       SELECT room_id ". 
                    "       FROM ".DATABASE_PREFIX."pages ". 
                    "       WHERE page_id = '".$block_id['page_id']."' ".
                    "   ) ".
                    " ); ";
            $result = $this->dbAccess->executeQuery($query);
            if($result === false){
                $error_msg = $this->dbAccess->ErrorMsg();
                return false;
            }
            if(count($result) == 1)
            {
                return $result[0]['authority_id'];
            }
            else if($block_id['space_type'] == _SPACE_TYPE_PUBLIC)
            {
                return $this->getDefaultEntryAuthPublic();
            }
            else
            {
                return _AUTH_GUEST;
            }
        } else {
            return _AUTH_GUEST; 
        }
    }
    
    
    /**
     * ユーザの登録グループ一覧を取得する
     */
    function getUsersGroupList(&$user_group, &$error_msg){
        // get List from pages Table
        $query = "SELECT * FROM ". DATABASE_PREFIX ."pages_users_link ".
                 "WHERE user_id = ?; ";
        $params = null;
        $params[] = $this->Session->getParameter("_user_id");
        // SELECT実行
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result === false){
            $error_msg = $this->dbAccess->ErrorMsg();
            return false;
        }
        // Add Nonmember
        array_push($result, array("room_id" => "0"));
        // 結果を返す
        $user_group = $result;
        return true;
    }
    
    /**
     * delete pade_id of private room and public space from $usersGroups
     *
     * @param array[$ii][room_id] $usersGroups
     * @return boolean: false->mysql error
     */
    public function deleteRoomIdOfMyRoomAndPublicSpace(&$usersGroups)
    {
        $retUsersGroups = array();
        
        $params = array();
        $query = " SELECT room_id ". 
                 " FROM ". DATABASE_PREFIX. "pages ".
                 " WHERE private_flag=? ". 
                 " AND room_id IN (";
        $params[] = 1;
        for($ii=0; $ii<count($usersGroups); $ii++){
            if($ii != 0){
                $query .= ',';
            }
            $query .= '?';
            $params[] = $usersGroups[$ii]['room_id'];
        }
        $query .= ");";
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result === false){
            return false;
        }
        
        $roomIds = ',';
        for($ii=0; $ii<count($result); $ii++){
            $roomIds .= $result[$ii]['room_id']. ',';
        }
        
        for($ii=0; $ii<count($usersGroups); $ii++){
            // remove room_id of private space
            // remove room_id of public space
            if( !is_numeric(strpos($roomIds, ','. $usersGroups[$ii]['room_id']. ',')) && 
                $usersGroups[$ii]['room_id'] != 1){

                array_push($retUsersGroups, $usersGroups[$ii]);
            }
        }
        
        $usersGroups = $retUsersGroups;
    }
    
    function getBlockPageId(){
        // check NC version 2010/06/07 A.Suzuki --start--
        // get NC version
        $version = $this->getNCVersion();
        
        if(str_replace(".", "", $version) < 2300){
            // Before NetCommons2.3.0.0
            // change sql to get id for WEKO in public space 2008/11/20 Y.Nakao --start--
            // get repository block_id and page_id
            $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type ".
                     "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                              DATABASE_PREFIX ."pages AS pages ".
                     "WHERE blocks.action_name = ? ".
                     "AND blocks.page_id = pages.page_id ".
                     "ORDER BY blocks.insert_time ASC; ";
            $params = array();
            $params[] = "repository_view_main_item_snippet";
            // change sql to get id for WEKO in public space 2008/11/20 Y.Nakao --start--
            $container =& DIContainerFactory::getContainer();
            $filterChain =& $container->getComponent("FilterChain");
            $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
            $result = $this->dbAccess->executeQuery($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->dbAccess->ErrorNo();
                $Error_Msg = $this->dbAccess->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
            if(count($result)==1){
                // WEKOがNC上に一つしかない
                $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id']);
            }else{
                // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
                for($ii=0; $ii<count($result); $ii++){
                    if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC){
                        $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                        break;
                    }
                }
            }
        } else {
            // On and after NetCommons2.3.0.0
            // get repository block_id and page_id
            $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type, pages.lang_dirname ".
                     "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                              DATABASE_PREFIX ."pages AS pages ".
                     "WHERE blocks.action_name = ? ".
                     "AND blocks.page_id = pages.page_id ".
                     "ORDER BY blocks.insert_time ASC; ";
            $params = array();
            $params[] = "repository_view_main_item_snippet";
            $result = $this->dbAccess->executeQuery($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->dbAccess->ErrorNo();
                $Error_Msg = $this->dbAccess->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
            $lang = $this->Session->getParameter("_lang");
            if(count($result)==1){
                // WEKOがNC上に一つしかない
                $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id'],'space_type'=>$result[0]['space_type']);
            }else{
                // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
                for($ii=0; $ii<count($result); $ii++){
                    if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==$lang){
                        $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                    } else if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==""){
                        // lang_dirname is empty
                        $tmp_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                    }
                }
                if(empty($return_array) && isset($tmp_array)){
                    $return_array = $tmp_array;
                }
            }
        }
        // check NC version 2010/06/07 A.Suzuki --end--
        return $return_array;
    }
    
    /**
     * Get default auth at public room
     *
     */
    function getDefaultEntryAuthPublic()
    {
        $container =& DIContainerFactory::getContainer();
        $authoritiesView =& $container->getComponent("authoritiesView");
        $configView =& $container->getComponent("configView");
        
        // Get default entry role_auth_id at public room from config
        $config = $configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
        if($config === false)
        {
            return _AUTH_GUEST;
        }
        $defaultEntryRoleAuthPublic = $config['default_entry_role_auth_public']['conf_value'];
        
        // Get user_auth_id from role_auth_id
        $authorities =& $authoritiesView->getAuthorityById($defaultEntryRoleAuthPublic);
        if($authorities === false) {
            return _AUTH_GUEST;
        }
        
        return $authorities['user_authority_id'];
    }
    
    /**
     * 現在稼働しているNetCommonsのバージョンを取得する
     * 
     * @return string $version
     */
    function getNCVersion(){
        // now version
        $container =& DIContainerFactory::getContainer();
        $configView =& $container->getComponent("configView");
        $config_version = $configView->getConfigByConfname(_SYS_CONF_MODID, "version");
        if(isset($config_version) && isset($config_version['conf_value'])) {
            $version = $config_version['conf_value'];
        } else {
            $version = _NC_VERSION;
        }
        return $version;
    }

    
    /**
     *  Get administrator's value that related to parameter name in parameter table  
     *
     * @param $param_name parameter name 
     * @param $param_value value
     * @param $error_msg error message
     * @return result(true or false)
     */
    public function getAdminParam($param_name, &$param_value, &$error_msg)
    {
        $param_value = "";
        // get param_value that correspond to param_name 
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".     
                "WHERE param_name = ? ".        
                "AND is_delete = ?; ";          
        $params = array();
        $params[0] = $param_name;       
        $params[1] = 0;     
        // execute SQL
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result)!= 1)
        {
            $error_msg = "The parameter is illegal. there is a lot of data gotten."; 
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        $param_value = $result[0]['param_value'];
        return true;
    }
}
?>
