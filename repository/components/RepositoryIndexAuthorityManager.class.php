<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryIndexAuthorityManager.class.php 42836 2014-10-09 10:42:29Z yuko_nakao $
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
        $params[] = $indexId;
        $params[] = $childExclusiveBaseAuth;
        $params[] = $childExclusiveRoomAuth;
        $params[] = $childPublicState;
        $params[] = $childPublicDate;
        $params[] = $childHarvestPublicState;
        // 挿入処理用共通パラメータ追加
        $this->addSystemPramsForInsert($params);
        $params[] = ''; // del_user_id
        $params[] = ''; // del_date
        $params[] = $childExclusiveBaseAuth;
        $params[] = $childExclusiveRoomAuth;
        $params[] = $childPublicState;
        $params[] = $childPublicDate;
        $params[] = $childHarvestPublicState;
        // 更新処理用共通パラメータ追加
        $this->addSystemPramsForUpdate($params);
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
            $params[] = $childExclusiveGroup[$ii];
            // 挿入処理用共通パラメータ追加
            $this->addSystemPramsForInsert($params);
        }
        // 更新処理用共通パラメータ追加
        $this->addSystemPramsForUpdate($params);
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
}
?>
