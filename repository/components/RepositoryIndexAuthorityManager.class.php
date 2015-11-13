<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryIndexAuthorityManager.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUserAuthorityManager.class.php';

class RepositoryIndexAuthorityManager extends RepositoryLogicBase
{
    private $defaultAccessRoleIds_ = "";
    private $defaultAccessRoleRoom_ = _AUTH_CHIEF;
    private $defaultAccessGroups_ = '';
    private $defaultExclusiveAclRoleIds_ = '';
    private $defaultExclusiveAclRoleRoom_ = RepositoryConst::TREE_DEFAULT_EXCLUSIVE_ACL_ROLE_ROOM;
    private $defaultExclusiveAclGroups_ = '';
    
    /**
     * 閲覧権限のあるインデックスIDのリストを取得する
     *
     * @param var $harvestFlag harvest flag
     * @param var $adminBaseAuth admin base auth
     * @param var $adminRoomAuth admin room auth
     * @param int $indexId index_id
     */
    // Mod OpenDepo 2014/01/31 S.Arata --start--
    public function getPublicIndex($harvestFlag, $adminBaseAuth, $adminRoomAuth, $indexId = null){
        $query = $this->getPublicIndexQuery($harvestFlag, $adminBaseAuth, $adminRoomAuth, $indexId);
        $result = $this->dbAccess->executeQuery($query);
        $indexList = array();

        if(count($result) > 0){
            for($ii=0; $ii<count($result); $ii++){
                array_push($indexList, $result[$ii]['index_id']);
            }
        }
        // インデックスの指定が無い場合（またはルートインデックスが指定されている場合）ルートインデックスは閲覧可能
        if(!is_numeric(array_search("0", $indexList)) && (!isset($indexId) || $indexId == 0)){
            array_push($indexList, "0");
        }

        return $indexList;
    }
    // Mod OpenDepo 2014/01/31 S.Arata --end--
    
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

    /**
     * create query that get index ID of viewable
     *
     * @param var $harvestFlag harvest flag
     * @param var $adminBaseAuth admin base auth
     * @param var $adminRoomAuth admin room auth
     * @param int $indexId index_id
     */
    // Mod OpenDepo 2014/01/31 S.Arata --start--
    public function getPublicIndexQuery($harvestFlag, $adminBaseAuth, $adminRoomAuth, $indexId = null)
    {
        // get user_id and
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        if(!isset($user_auth_id) || strlen($user_auth_id) == 0){
            $user_auth_id = 1;
        }
        $isLogin = false;
        if($user_id != "0")
        {
            $isLogin = true;
        }

        // check user room authority
        $repositoryUserAuthorityManager = new RepositoryUserAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $roomAuthority = $repositoryUserAuthorityManager->getRoomAuthorityID($user_id);

        // get user group list. $usersGroupList in public space and group space.
        $repositoryUserAuthorityManager->getUsersGroupList($usersGroupList, $errorMsg);

        // when user has user group and login.
        if (count($usersGroupList) > 0 && $isLogin){
            // delete public space from $usersGroupList
            $repositoryUserAuthorityManager->deleteRoomIdOfMyRoomAndPublicSpace($usersGroupList);
        }

        // create query for get public index.
        $query = "SELECT ind.index_id ".
                 "FROM ".DATABASE_PREFIX."repository_index ind ".
                 "INNER JOIN ".DATABASE_PREFIX."repository_index_browsing_authority auth ON ".
                 "ind.index_id = auth.index_id ".
                 "WHERE ";

        $query .= "(";
            // see public flag.
            $query .= "auth.public_state = 1 ";
            $query .= "AND auth.pub_date <= '".$this->transStartDate."' ";
            if (isset($indexId)) {
                $query .= " AND auth.index_id = ".$indexId." ";
            }
            // login user
            if ($user_auth_id < $adminBaseAuth || $roomAuthority < $adminRoomAuth)
            {
                // when not WEKO admin, see base authority and room authority.
                $query .= " AND auth.exclusive_acl_role_id < ".$user_auth_id.
                          " AND auth.exclusive_acl_room_auth < ".$roomAuthority;
                if(count($usersGroupList) > 0){
                    $usersGroup = array();
                    for($ii=0; $ii<count($usersGroupList); $ii++){
                        array_push($usersGroup, $usersGroupList[$ii]["room_id"]);
                    }
                    if(count($usersGroup) > 0){
                        $query .= " AND ( EXISTS ( ".
                                  " SELECT * FROM ".DATABASE_PREFIX."pages_users_link ".
                                  " WHERE room_id IN (".implode("," ,$usersGroup) ." ) ".
                                  " AND room_id NOT IN ( ".
                                  "  SELECT exclusive_acl_group_id ".
                                  "  FROM ".DATABASE_PREFIX."repository_index_browsing_groups AS groups ".
                                  "  WHERE groups.index_id = ind.index_id ".
                                  "  AND groups.is_delete = 0 ".
                                  " ) ".
                                  ") ";
                        $query .= " OR NOT EXISTS ( ".
                                  "  SELECT * ".
                                  "  FROM ".DATABASE_PREFIX."repository_index_browsing_groups AS groups ".
                                  "  WHERE groups.is_delete = 0 AND groups.index_id = ind.index_id ".
                                  "  AND groups.exclusive_acl_group_id = 0 ".
                                  " ) ".
                                  ") ";
                    }
                }
            }
        $query .= ") ";
        $query .= " AND auth.is_delete = 0 ";
        $query .= " AND ind.is_delete = 0 ";
        if($harvestFlag == "True") {
            $query .= " AND auth.harvest_public_state = 1 ";
        }
        // check owner_user_id
        $query .= " UNION SELECT ind2.index_id ".
                  " FROM ".DATABASE_PREFIX."repository_index ind2 ".
                  " WHERE ind2.owner_user_id = '".$user_id . "' ".
                  " AND ind2.is_delete = 0 ";
        if (isset($indexId)) {
            $query .= " AND ind2.index_id = ".$indexId." ";
        }
        return $query;
    }
    // Mod OpenDepo 2014/01/31 S.Arata --end--

    /**
     * get authority of index
     *
     * @param var $indexId index ID
     * @param var $exclusive_acl_role_id
     * @param var $exclusive_acl_room_auth
     * @param var $exclusive_acl_group
     * @param var $publicState
     * @param var $publicDate
     * @param var $harvestPublicState
     * @param var $logFh log file handle
     */
    public function getBrowsingAuth($indexId, &$exclusive_acl_role_id, &$exclusive_acl_room_auth, &$exclusive_acl_group, &$publicState, &$publicDate, &$harvestPublicState, $logFh=null)
    {
        if ( isset($logFh) ) {
            fwrite($logFh, "-- Start getBrowsingAuth --\n");
        }

        if ($indexId == 0){
            // インデックスの権限のデフォルト値を設定
            $exclusive_acl_role_id = 0;
            $exclusive_acl_room_auth = -1;
            $exclusive_acl_group = array();
            $publicState = 1;
            $publicDate = "0000-00-00 00:00:00.000";
            $harvestPublicState = 1;

            if ( isset($logFh) ) {
                fwrite($logFh, "-- End getBrowsingAuth --\n");
            }
            return;
        }

        $query = "SELECT exclusive_acl_role_id, exclusive_acl_room_auth, public_state, pub_date, harvest_public_state ".
                 "FROM ".DATABASE_PREFIX."repository_index_browsing_authority ".
                 "WHERE index_id = ? ".
                 "AND is_delete = ? ; ";
        $params = array();
        $params[] = $indexId;
        $params[] = 0;

        if ( isset($logFh) ) {
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
        }

        $result_limit = $this->dbAccess->executeQuery($query, $params);
        
        // Fix index not found. 2014/10/09 --start--
        if(count($result_limit) != 1)
        {
            if ( isset($logFh) ) {
                fwrite($logFh, "    Not found index.".__CLASS__." ".__LINE__."\n");
            }
            $exception = new RepositoryException( __CLASS__." ".__LINE__, 00001 );
            $exception->setDetailMsg("Not found index.");
            
            throw $exception;
        }
        // Fix index not found. 2014/10/09 --end--

        if ( isset($logFh) ) {
            fwrite($logFh, "    Complete execute query.\n");
        }

        $exclusive_acl_role_id = $result_limit[0]["exclusive_acl_role_id"];
        $exclusive_acl_room_auth = $result_limit[0]["exclusive_acl_room_auth"];
        $publicState = $result_limit[0]["public_state"];
        $publicDate = $result_limit[0]["pub_date"];
        $harvestPublicState = $result_limit[0]["harvest_public_state"];


        $query = "SELECT exclusive_acl_group_id ".
                 "FROM ".DATABASE_PREFIX."repository_index_browsing_groups ".
                 "WHERE index_id = ? ".
                 " AND is_delete = ?; ";
        $params = array();
        $params[] = $indexId;
        $params[] = 0;

        if ( isset($logFh) ) {
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
        }

        $result_ban = $this->dbAccess->executeQuery($query, $params);

        if ( isset($logFh) ) {
            fwrite($logFh, "    Complete execute query.\n");
        }

        $exclusive_acl_group = array();
        foreach($result_ban as $key => $value){
            array_push($exclusive_acl_group, $value["exclusive_acl_group_id"]);
        }

        if ( isset($logFh) ) {
            fwrite($logFh, "-- End getBrowsingAuth --\n");
        }
    }

    /**
     * 投稿権限があるインデックスである
     * @param string $auth_id auth_id
     * @param string $user_id user_id
     * @param string $indexId インデックスID
     * @param boolean $isError エラー発生
     * @return boolean true:投稿権限がある false:投稿権限がない
     */
    public function isRegistItemToIndex($auth_id, $user_id, $indexId, &$isError)
    {
        $isError = false;
        $access_role = $this->getIndexAccessRoleFromDb($indexId,$isError);
        if($isError === true)
        {
            return false;
        }

        $access_group = $this->getIndexAccessGroupFromDb($indexId,$isError);
        if($isError === true)
        {
            return false;
        }

        if($this->isIndexOwner($indexId,$user_id))
        {
            return true;
        }
        if(strlen(str_replace(",","",str_replace("|","",$access_role))) == 0 &&
                strlen($access_group) == 0){
            return false;
        }

        // access_roleをベース権限とルーム権限に分ける
        $this->explodeBaseAndRoom($access_role,$base_auth,$room_auth);
        // get user's role auth id
        $role_auth_id = $this->getRoleAuthorityIdFromDb($user_id,$isError);
        if($isError === true || strlen($role_auth_id) == 0)
        {
            return false;
        }
        // ベース権限、ルーム権限チェック
        if(is_numeric(strpos($base_auth, $role_auth_id))){
            if(intval($auth_id) >= intval($room_auth)) {
                return true;
            }
        }

        // get user's entry groups
        $result = $this->getUsersGroupList($groups, $error);
        for($ii=0; $ii<count($groups); $ii++){
            if(is_numeric(strpos($access_group, $groups[$ii]["room_id"]))){
                return true;
            }
        }
        return false;
    }

    /**
     * get lower authority
     *
     * @param var $parentExclusiveBaseAuth base authority of parant index
     * @param var $parentExclusiveRoomAuth room authority of parant index
     * @param string $parentExclusiveGroup array of exclusive parent index 「,」区切で閲覧対象外のグループIDが入った文字列
     * @param var $parentPublicState public state
     * @param var $parentPublicDate public date
     * @param var $parentHarvestPublicState harvest public state
     * @param var $childExclusiveBaseAuth base authority of child index
     * @param var $childExclusiveRoomAuth room authority of child index
     * @param string $childExclusiveGroup array of exclusive child index 「,」区切で閲覧対象外のグループIDが入った文字列
     * @param var $childPublicState public state
     * @param var $childPublicDate public date
     * @param var $childHarvestPublicState harvest public state
     * @param var $logFh log file handle
     */
    private function decideBrowsingAuth($parentExclusiveBaseAuth,
                                        $parentExclusiveRoomAuth,
                                        $parentExclusiveGroup,
                                        $parentPublicState,
                                        $parentPublicDate,
                                        $parentHarvestPublicState,
                                        &$childExclusiveBaseAuth,
                                        &$childExclusiveRoomAuth,
                                        &$childExclusiveGroup,
                                        &$childPublicState,
                                        &$childPublicDate,
                                        &$childHarvestPublicState,
                                        $logFh=null)
    {
        if ( isset($logFh) ){
            fwrite($logFh, "-- Start decideBrowsingAuth --\n");
        }

        if ($parentExclusiveBaseAuth > $childExclusiveBaseAuth){
            $childExclusiveBaseAuth = $parentExclusiveBaseAuth;
        }

        if ($parentExclusiveRoomAuth > $childExclusiveRoomAuth){
            $childExclusiveRoomAuth = $parentExclusiveRoomAuth;
        }

        if ($parentPublicState < $childPublicState) {
            $childPublicState = $parentPublicState;
        }

        if ($parentPublicDate > $childPublicDate) {
            $childPublicDate = $parentPublicDate;
        }

        if ($parentHarvestPublicState < $childHarvestPublicState) {
            $childHarvestPublicState = $parentHarvestPublicState;
        }

        // Fix PHP Notice: array_diff is not support first arg of empty array 2014/06/05 T.Koyasu --start--
        if(count($parentExclusiveGroup) === 0){
            $notContain = array();
        } else {
            $notContain = array_diff($parentExclusiveGroup, $childExclusiveGroup);
        }
        // Fix PHP Notice: array_diff is not support first arg of empty array 2014/06/05 T.Koyasu --end--
        if(count($notContain) > 0){
            foreach($notContain as $key => $value){
                array_push($childExclusiveGroup, $value);
            }
        }

        if ( isset($logFh) ){
            fwrite($logFh, "    Complete execute query.\n"."-- End decideBrowsingAuth --\n");
        }
    }


    /**
     * update exclusive authority
     *
     * @param var $indexId index ID
     * @param var $parentExclusiveBaseAuth base authority of parant index
     * @param var $parentExclusiveBaseAuth room authority of parant index
     * @param var $parentExclusiveGroup array of exclusive parent index
     * @param var $parentPublicState public state
     * @param var $parentPublicDate public date
     * @param var $parentHarvestPublicState harvest public state
     * @param var $childExclusiveBaseAuth base authority of child index
     * @param var $childExclusiveRoomAuth room authority of child index
     * @param array $childExclusiveGroup array of exclusive child index 閲覧対象外のグループIDが入った配列
     * @param var $childPublicState public state
     * @param var $childPublicDate public date
     * @param var $childHarvestPublicState harvest public state
     * @param var $logFh log file handle
     */
    public function updateBrowsingAuth( $indexId,
                                        $parentExclusiveBaseAuth,
                                        $parentExclusiveRoomAuth,
                                        $parentExclusiveGroup,
                                        $parentPublicState,
                                        $parentPublicDate,
                                        $parentHarvestPublicState,
                                        &$childExclusiveBaseAuth,
                                        &$childExclusiveRoomAuth,
                                        &$childExclusiveGroup,
                                        &$childPublicState,
                                        &$childPublicDate,
                                        &$childHarvestPublicState,
                                        $logFh=null)
    {
        if ( isset($logFh) ){
            fwrite($logFh, "-- Start updateBrowsingAuth --\n");
        }

        // Fix PHP Warning: array_filter is not support first arg of empty value 2014/06/05 T.Koyasu --start--
        if(!is_array($parentExclusiveGroup)){
            $parentExclusiveGroup = array();
        }
        // Fix PHP Warning: array_filter is not support first arg of empty value 2014/06/05 T.Koyasu --end--
        // Fix validate $parentExclusiveGroup and $childExclusiveGroup 2013.12.11 Y.Nakao --start--
        $parentExclusiveGroup = array_filter($parentExclusiveGroup, 'strlen');
        $childExclusiveGroup  = array_filter($childExclusiveGroup, 'strlen');
        // Fix validate $parentExclusiveGroup and $childExclusiveGroup 2013.12.11 Y.Nakao --end--

        $this->decideBrowsingAuth(  $parentExclusiveBaseAuth,
                                    $parentExclusiveRoomAuth,
                                    $parentExclusiveGroup,
                                    $parentPublicState,
                                    $parentPublicDate,
                                    $parentHarvestPublicState,
                                    $childExclusiveBaseAuth,
                                    $childExclusiveRoomAuth,
                                    $childExclusiveGroup,
                                    $childPublicState,
                                    $childPublicDate,
                                    $childHarvestPublicState,
                                    $logFh);

        $query = "INSERT INTO ".DATABASE_PREFIX."repository_index_browsing_authority ".
                 "(index_id, exclusive_acl_role_id, exclusive_acl_room_auth, public_state, pub_date, harvest_public_state,
                   ins_user_id, mod_user_id, ins_date, mod_date, del_user_id, del_date, is_delete) ".
                 "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ".
                 "ON DUPLICATE KEY UPDATE ".
                 "exclusive_acl_role_id = ?, ".
                 "exclusive_acl_room_auth = ?, ".
                 "public_state = ?, ".
                 "pub_date = ?, ".
                 "harvest_public_state = ?, ".
                 "mod_user_id = ?, ".
                 "mod_date = ?, ".
                 "del_user_id = ?, ".
                 "del_date = ?, ".
                 "is_delete = ? ;";
        $params = array();
        $params[] = (int)$indexId;
        $params[] = (int)$childExclusiveBaseAuth;
        $params[] = (int)$childExclusiveRoomAuth;
        $params[] = (int)$childPublicState;
        $params[] = $childPublicDate;
        $params[] = (int)$childHarvestPublicState;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = $this->transStartDate;
        $params[] = ''; // del_user_id
        $params[] = ''; // del_date
        $params[] = 0;
        $params[] = (int)$childExclusiveBaseAuth;
        $params[] = (int)$childExclusiveRoomAuth;
        $params[] = (int)$childPublicState;
        $params[] = $childPublicDate;
        $params[] = (int)$childHarvestPublicState;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = ''; // del_user_id
        $params[] = ''; // del_date
        $params[] = 0;

        if ( isset($logFh) ){
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
            fwrite($logFh, get_class($this->dbAccess)."\n");
        }

        $result = $this->dbAccess->executeQuery($query, $params);

        if ( isset($logFh) ){
            foreach($childExclusiveGroup as $key => $val)
            {
                fwrite($logFh, "$key : $val \n");
            }
            fwrite($logFh, "    Complete execute query.\n");
        }

        // ------------------------------------
        // 閲覧権限テーブルを更新
        // ------------------------------------
        $query = "UPDATE ".DATABASE_PREFIX."repository_index_browsing_groups ".
                 "SET mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                 "WHERE index_id = ? AND is_delete = ? ";
        $params = array();
        // 削除処理用共通パラメータ追加
        $this->addSystemPramsForDelete($params);
        $params[] = $indexId;   // index_id
        $params[] = 0;          // is_delete

        if ( isset($logFh) ){
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
        }

        $result = $this->dbAccess->executeQuery($query, $params);

        if ( isset($logFh) ){
            fwrite($logFh, "    Complete execute query.\n");
        }

        // ------------------------------------
        // 追加
        // ------------------------------------
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_index_browsing_groups ".
                 "(index_id, exclusive_acl_group_id, ins_user_id, mod_user_id, ins_date, mod_date, is_delete) ".
                 "VALUES ";
        for ($ii = 0; $ii < count($childExclusiveGroup); $ii++) {
            if($ii > 0)
            {
                $query .= " , ";
            }
            $query .= " (?, ?, ?, ?, ?, ?, ?) ";
        }
        $query .= "ON DUPLICATE KEY UPDATE ".
                 " mod_user_id = ?, ".
                 " mod_date = ?, ".
                 " is_delete = ? ;";
        $params = array();
        for ($ii = 0; $ii < count($childExclusiveGroup); $ii++) {
            $params[] = $indexId;
            $params[] = (int)$childExclusiveGroup[$ii];
            $params[] = $this->Session->getParameter("_user_id");
            $params[] = $this->Session->getParameter("_user_id");
            $params[] = $this->transStartDate;
            $params[] = $this->transStartDate;
            $params[] = 0;
        }
        // 更新処理用共通パラメータ追加
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = 0;

        if ( isset($logFh) ){
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
        }
        if(count($childExclusiveGroup) > 0)
        {
            $result = $this->dbAccess->executeQuery($query, $params);
        }

        if ( isset($logFh) ){
            fwrite($logFh, "    Complete execute query.\n"."-- End updateBrowsingAuth --\n");
        }
    }

    /**
     * delete exclusive authority
     *
     * @param var $indexId index ID
     *
     */
    public function deleteBrowsingAuth ($indexId)
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_index_browsing_authority ".
                "SET ".
                "mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                "WHERE index_id = ? ;";
        $params = array();
        // 削除処理用共通パラメータ追加
        $this->addSystemPramsForDelete($params);
        $params[] = $indexId;   // index_id

        $result = $this->dbAccess->executeQuery($query, $params);

        $query = "UPDATE ".DATABASE_PREFIX."repository_index_browsing_groups ".
                "SET ".
                "mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
                "WHERE index_id = ? ;";
        $params = array();
        // 削除処理用共通パラメータ追加
        $this->addSystemPramsForDelete($params);
        $params[] = $indexId;   // index_id

        $result = $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * check public state
     *
     * @param var $indexId index ID
     */
    public function checkPublicState ($indexId)
    {
        $query = "SELECT public_state FROM ".DATABASE_PREFIX."repository_index_browsing_authority ".
                " WHERE index_id = ? AND ".
                " is_delete = ?;";
        $params = array();
        $params[] = $indexId;     // index_id
        $params[] = 0;            // is_delete

        $result = $this->dbAccess->executeQuery($query, $params);

        if ($result[0]["public_state"] = 1) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * get harvest public index query
     */
    public function getHarvestPublicIndexQuery(){
        $query = " SELECT DISTINCT index_id ".
                " FROM ".DATABASE_PREFIX."repository_index_browsing_authority ".
                " WHERE harvest_public_state = 1 ";
        return $query;
    }
    
    /**
     * delete all record from index browsing authority 
     */
    private function deleteAllRecordFromIndexBrowsingAuthority(){
        $query = " TRUNCATE ".DATABASE_PREFIX."repository_index_browsing_authority ;";
        $this->dbAccess->executeQuery($query);
    }
    
    /**
     * delete all record from index browsing groups
     */
    private function deleteAllRecordFromIndexBrowsingGroups(){
        $query = " TRUNCATE ".DATABASE_PREFIX."repository_index_browsing_groups ;";
        $this->dbAccess->executeQuery($query);
    }
    
    /**
     * delete all record from index browsing groups
     */
    public function reconstructIndexAuthorityTable(){
        $this->deleteAllRecordFromIndexBrowsingAuthority();
        $this->deleteAllRecordFromIndexBrowsingGroups();
        
        // update table
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
        $indexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->transStartDate);
        $indexManager->createIndexBrowsingAuthority();
    }
    
    /**
     * NetCommonsに登録されている全グループを取得し、
     * 投稿権限のあるグループID、グループ名、投稿権限のないグループID、グループ名のリストを取得する。
     * 
     * get edit index access group list
     *
     * @param $access_group_id access OK group room ids
     * @param $exclusive_acl_group acl NG group room ids
     * @param $edit_index add result in this parameter
     * @return true or false
     */
    function getAccessGroupData($access_group_id, $exclusive_acl_group_id, &$edit_index)
    {
        // get access group or not
        $result = $this->getGroupList($all_group, $error);
        if($result === false){
            return false;;
        }
        // add get (not member)
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        $add_array = array("page_id"=>'0', "page_name"=>$smartyAssign->getLang("repository_item_gest"));
        array_unshift($all_group, $add_array);
        
        // 投稿権限
        $edit_index["access_group_id"] = '';
        $edit_index["access_group_name"] = '';
        $edit_index["not_access_group_id"] = '';
        $edit_index["not_access_group_name"] = '';
        
        // 閲覧権限
        $edit_index["acl_group_id"] = '';
        $edit_index["acl_group_name"] = '';
        $edit_index["exclusive_acl_group_id"] = '';
        $edit_index["exclusive_acl_group_name"] = '';
        
        for($ii=0; $ii<count($all_group); $ii++)
        {
            if(is_numeric(strpos(",".$access_group_id.",", ",".$all_group[$ii]["page_id"].",")))
            {
                if($edit_index["access_group_id"] != "")
                {
                    $edit_index["access_group_id"] .= ",";
                    $edit_index["access_group_name"] .= ",";
                }
                $edit_index["access_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["access_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            else
            {
                if($edit_index["not_access_group_id"] != "")
                {
                    $edit_index["not_access_group_id"] .= ",";
                    $edit_index["not_access_group_name"] .= ",";
                }
                $edit_index["not_access_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["not_access_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            
            if(is_numeric(strpos(",".$exclusive_acl_group_id.",", ",".$all_group[$ii]["page_id"].",")))
            {
                if(strlen($edit_index["exclusive_acl_group_id"]) > 0)
                {
                    $edit_index["exclusive_acl_group_id"] .= ",";
                    $edit_index["exclusive_acl_group_name"] .= ",";
                }
                $edit_index["exclusive_acl_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["exclusive_acl_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            else
            {
                if(strlen($edit_index["acl_group_id"]) > 0)
                {
                    $edit_index["acl_group_id"] .= ",";
                    $edit_index["acl_group_name"] .= ",";
                }
                $edit_index["acl_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["acl_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            
        }
        if($edit_index["access_group_name"] == '')
        {
            $edit_index["access_group_name"] = '""';
        }
        if($edit_index["not_access_group_name"] == '')
        {
            $edit_index["not_access_group_name"] = '""';
        }
        
        if(strlen($edit_index["acl_group_name"]) == 0)
        {
            $edit_index["acl_group_name"] = '""';
        }
        if(strlen($edit_index["exclusive_acl_group_name"]) == 0)
        {
            $edit_index["exclusive_acl_group_name"] = '""';
        }
        
        return true;
    }
    
    /**
     * NetCommonsに登録されている全権限を取得し、
     * 投稿権限のある権限ID、権限名、投稿権限のない権限ID、権限名のリストを取得する。
     * 
     * get edit index access auth list
     *
     * @param $access_role_id access OK group room ids
     * @param $edit_index add result in this parameter
     * @return true or false
     */
    function getAccessAuthData($access_role, $exclusive_acl_role, &$edit_index){
        // Add config management authority 2010/02/23 Y.Nakao --start--
        // separate access role base authority and room authority
        $access_auth = explode("|", $access_role);
        $access_role_id = $this->defaultAccessRoleIds_;
        $access_role_room = $this->defaultAccessRoleRoom_;
        if(count($access_auth) == 2){
            $access_role_id = $access_auth[0];
            $access_role_room = $access_auth[1];
        } else if(count($access_auth) == 1){
            $access_role_id = $access_auth[0];
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--
        
        $aclAuthorities = explode("|", $exclusive_acl_role);
        $exclusiveAclRoleId = $this->defaultAccessRoleIds_;
        $exclusiveAclRoleRoom = $this->defaultAccessRoleRoom_;
        if(count($aclAuthorities) == 2)
        {
            // max user_authority_id
            $exclusiveAclRoleId = $aclAuthorities[0];
            $exclusiveAclRoleRoom = $aclAuthorities[1];
        }
        else if(count($aclAuthorities) == 1)
        {
            $exclusiveAclRoleId = $aclAuthorities[0];
        }
        
        // get all access auth
        $query = "SELECT * FROM ". DATABASE_PREFIX ."authorities;";
        $all_auth = $this->dbAccess->executeQuery($query);
        if($all_auth === false){
            return false;
        }
        // add get (not member)
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // 投稿権限
        $edit_index["access_role_id"] = '';
        $edit_index["access_role_name"] = '';
        $edit_index["not_access_role_id"] = '';
        $edit_index["not_access_role_name"] = '';
        
        // 閲覧権限
        $edit_index["acl_role_id"] = '';
        $edit_index["acl_role_name"] = '';
        $edit_index["exclusive_acl_role_id"] = '';
        $edit_index["exclusive_acl_role_name"] = '';
        // Add tree access control list 2012/03/02 T.Koyasu -start-
        $edit_index["acl_user_auth_id"] = '';
        $edit_index["exclusive_acl_user_auth_id"] = '';
        // Add tree access control list 2012/03/02 T.Koyasu -end-
        
        for($ii=0; $ii<count($all_auth); $ii++){
            if(is_numeric(strpos(",".$access_role_id.",", ",".$all_auth[$ii]["role_authority_id"].","))){
                if($edit_index["access_role_id"] != ""){
                    $edit_index["access_role_id"] .= ",";
                    $edit_index["access_role_name"] .= ",";
                }
                $edit_index["access_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["access_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
            } else {
                if($edit_index["not_access_role_id"] != ""){
                    $edit_index["not_access_role_id"] .= ",";
                    $edit_index["not_access_role_name"] .= ",";
                }
                $edit_index["not_access_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["not_access_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
            }
            
            // Add tree access control list 2012/03/02 T.Koyasu -start-
            if($exclusiveAclRoleId >= intval($all_auth[$ii]["user_authority_id"]))
            {
                // Mod access_role_id -> exclusive_acl_role_id
                if($edit_index["exclusive_acl_role_id"] != "")
                {
                    $edit_index["exclusive_acl_role_id"] .= ",";
                    $edit_index["exclusive_acl_role_name"] .= ",";
                    // add user_authority_id for gimic by Koyasu
                    $edit_index["exclusive_acl_user_auth_id"] .= ",";
                }
                $edit_index["exclusive_acl_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["exclusive_acl_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
                // add user_authority_id for gimic by Koyasu
                $edit_index["exclusive_acl_user_auth_id"] .= $all_auth[$ii]["user_authority_id"];
            }
            else
            {
                if($edit_index["acl_role_id"] != "")
                {
                    $edit_index["acl_role_id"] .= ",";
                    $edit_index["acl_role_name"] .= ",";
                    // add user_authority_id for gimic by Koyasu
                    $edit_index["acl_user_auth_id"] .= ",";
                }
                $edit_index["acl_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["acl_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
                // add user_authority_id for gimic by Koyasu
                $edit_index["acl_user_auth_id"] .= $all_auth[$ii]["user_authority_id"];
            }
            // Add tree access control list 2012/03/02 T.Koyasu -end-
            
        }
        if($edit_index["access_role_name"] == ''){
            $edit_index["access_role_name"] = '""';
        }
        if($edit_index["not_access_role_name"] == ''){
            $edit_index["not_access_role_name"] = '""';
        }
        
        if(strlen($edit_index["acl_role_name"]) == 0)
        {
            $edit_index["acl_role_name"] = '""';
        }
        if(strlen($edit_index["exclusive_acl_role_name"]) == 0)
        {
            $edit_index["exclusive_acl_role_name"] = '""';
        }
        
        // Add config management authority 2010/02/23 Y.Nakao --start--
        // set access role for room authority
        if(intval($access_role_room) == _AUTH_GENERAL){
            $edit_index["room_auth_moderate"] = "true";
            $edit_index["room_auth_general"] = "true";
        } else if(intval($access_role_room) == _AUTH_MODERATE){
            $edit_index["room_auth_moderate"] = "true";
            $edit_index["room_auth_general"] = "false";
        } else {
            $edit_index["room_auth_moderate"] = "false";
            $edit_index["room_auth_general"] = "false";;
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--
        
        // Add tree access control list 2012/02/22 T.Koyasu -start-
        // modify true/false with value of exclusive_acl_role column
        // Add tree access control list 2011/12/28 Y.Nakao --start--
        if(intval($exclusiveAclRoleRoom) == _AUTH_OTHER)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "true";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_GUEST)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_GENERAL)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "false";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_MODERATE)
        {
            $edit_index['acl_room_auth_moderate'] = "false";
            $edit_index['acl_room_auth_general'] = "false";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "true";
            $edit_index['acl_room_auth_logout'] = "true";
        }
        // Add tree access control list 2011/12/28 Y.Nakao --end--
        // Add tree access control list 2012/02/22 T.Koyasu -end-
        return true;
    }
    
    // Add file price 2008/08/28 Y.Nakao --start--
    /**
     * NC上に存在するグループの一覧を取得
     * @return $Result_List['groupe_list'][ii]['～']形式で値が帰る
     */
    function getGroupList(&$all_group, &$error_msg){
        // get List from pages Table
        $query = "SELECT * FROM ". DATABASE_PREFIX ."pages ".
                 "WHERE space_type = ? AND ".
                 "private_flag = ? AND ".
                 "NOT thread_num = ? AND ".
        // Fix select group list 2009/02/03 Y.Nakao --start--
                 "room_id = page_id; ";
        // Fix select group list 2009/02/03 Y.Nakao --end--
        $params = null;
        $params[] = _SPACE_TYPE_GROUP;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            return false;
        }
        // 結果を格納
        $all_group = $result;
        return true;
    }

    
    /**
     * アクセスグループの取得
     * @param string $indexID インデックスID
     * @param boolean $isError エラー発生か否か
     * @return string access_group
     */
    private function getIndexAccessGroupFromDb($indexID,&$isError)
    {
        $isError = false;
        $query = " SELECT access_group ".
                " FROM ".DATABASE_PREFIX."repository_index ".
                " WHERE index_id = ? ".
                " AND is_delete = 0; ";
        $params = array();
        $params[] = $indexID;
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result === false)
        {
            $isError = true;
            return "";
        }

        $accessGroup = "";
        if(strlen($result[0]['access_group']) > 0)
        {
            $accessGroup = $result[0]['access_group'];
        }

        return $accessGroup;
    }

    /**
     * アクセスロールの取得
     * @param string $indexID インデックスID
     * @param boolean $isError エラー発生か否か
     */
    private function getIndexAccessRoleFromDb($indexID,&$isError)
    {
        $isError = false;
        $query = "SELECT access_role FROM ".DATABASE_PREFIX."repository_index ".
                "WHERE index_id = ? ".
                "AND is_delete = 0;";
        $params = array();
        $params[] = $indexID;
        $ret = $this->dbAccess->executeQuery($query, $params);
        if($ret === false ){
            $isError = true;
        }

        return $ret[0]['access_role'];
    }

    /**
     * 対象インデックスのowner_user_idの個数確認
     * @param unknown $indexId インデックスID
     * @param unknown $user_id userID
     * @return boolean 0個以上存在するか否か
     */
    private function isIndexOwner($indexId,$user_id)
    {
        $query = " SELECT count(*) ".
                " FROM ".DATABASE_PREFIX."repository_index ".
                " WHERE index_id = ? ".
                " AND owner_user_id = ? ".
                " AND is_delete = 0; ";
        $params = array();
        $params[] = $indexId;
        $params[] = $user_id;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) != 0 && $result[0]['count(*)'] != 0) {
            return true;
        }else {
            return false;
        }
    }

    /**
     * role_authority_idの取得
     * @param string $user_id
     * @param string $base_auth
     * @param string $room_auth
     * @param string $auth_id
     * @return string
     */
    private function getRoleAuthorityIdFromDb($user_id,&$isError)
    {
        $isError = false;

        $query = "SELECT role_authority_id FROM ". DATABASE_PREFIX ."users ".
                "WHERE user_id = ?; ";
        $params = array();
        $params[] = $user_id;
        $role_auth_id = $this->dbAccess->executeQuery($query, $params);
        if($role_auth_id === false) {
            $isError = true;
            return "";
        }
        else if(count($role_auth_id)!=1)
        {
            return "";
        }

        return $role_auth_id[0]["role_authority_id"];
    }

    /**
     * ユーザの登録グループ一覧を取得する
     */
    private function getUsersGroupList(&$user_group, &$error_msg){
        $userAuthorityManager = new RepositoryUserAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        return $userAuthorityManager->getUsersGroupList($user_group, $error_msg);
    }

    /**
     * access_roleをBase権限とRoom権限に分ける
     * @param string $access_role access_role
     * @param string $base_auth Base権限
     * @param string $room_auth Room権限
     * @return boolean 分けられたか否か
     */
    private function explodeBaseAndRoom($access_role,&$base_auth,&$room_auth)
    {
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
            $room_auth = substr($role[1], 0, 1);
        }
        if(strlen($room_auth) == 0){
            $room_auth = _AUTH_CHIEF;
        }

        return true;
    }
}
?>
