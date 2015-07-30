<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryIndexManager.class.php 42836 2014-10-09 10:42:29Z yuko_nakao $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUserAuthorityManager.class.php';

class RepositoryIndexManager extends RepositoryLogicBase
{
    // Add new prefix 2014/01/15 T.Ichikawa --start--
    private $repositorySearchTableProcessing = null;
    // Add new prefix 2014/01/15 T.Ichikawa --end--
    
    /**
     * コンストラクタ
     *
     * @param var $session Session
     * @param var $this->dbAccess dbAccess
     * @param string $TransStartDate TransStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        parent::__construct($session, $dbAccess, $transStartDate);
    }

    /**
     * 新規インデックスIDを発番する
     *
     * @param var $logFh log handle file
     */
    private function newIndexId($logFh=null)
    {
        if ( isset($logFh) ){
            fwrite($logFh, "-- Start newIndexId --\n");
        }

        $query = "SELECT MAX(index_id) ".
                 "FROM ".DATABASE_PREFIX."repository_index ;";

        if ( isset($logFh) ){
            fwrite($logFh, "  Execute query: ".$query."\n");
        }

        $result = $this->dbAccess->executeQuery($query);

        if ( isset($logFh) ){
            fwrite($logFh, "    Complete execute query.\n");
        }

        $indexId = $result[0]["MAX(index_id)"];
        $indexId++;

        if ( isset($logFh) ){
            fwrite($logFh, "  NewIndexID is ".$indexId.".\n"."-- End newIndexId --\n");
        }

        return $indexId;
    }

    /**
     * 新規表示順番を発番する
     *
     * @param var $indexId index id
     * @param bool $isAddHead add position of index, head or tail
     * @param var $logFh log handle file
     */
    private function newShowOrder($indexId, $isAddHead,$logFh=null)
    {
        if ( isset($logFh) ){
            fwrite($logFh,  "-- Start newShowOrder --\n");
        }

        if($isAddHead)
        {
            // update showOrder of existence indexes
            $query = "UPDATE ".DATABASE_PREFIX ."repository_index ".
                    "SET show_order = show_order + 1, ".
                    "mod_user_id = ?, ".
                    "mod_date = ? ".
                    "WHERE parent_index_id = ? ".
                    "AND is_delete = ?;";
            $params = array();
            $params[] = $this->Session->getParameter('_user_id');
            $params[] = $this->transStartDate;
            $params[] = $indexId;
            $params[] = 0;

            if ( isset($logFh) ){
                fwrite($logFh, "  Execute query: ".$query."\n");
            }

            $result = $this->dbAccess->executeQuery($query, $params);

            $showOrder = 1;
        }
        else
        {
            $query = "SELECT MAX(show_order) ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE parent_index_id = ".$indexId;

            if ( isset($logFh) ){
                fwrite($logFh, "  Execute query: ".$query."\n");
            }

            $result = $this->dbAccess->executeQuery($query);

            $showOrder = $result[0]["MAX(show_order)"];
            $showOrder++;
        }

        if ( isset($logFh) ){
            fwrite($logFh, "  ShowOrder is ".$showOrder.".\n"."-- End newShowOrder --\n");
        }

        return $showOrder;
    }

    /**
     * インデックス表示設定が「表示する」になっているインデックスのリストを取得する
     *
     */
    public function getDisplayIndexList($adminBaseAuth, $adminRoomAuth)
    {
        try{
            // セッション情報の取得
            $user_id = $this->Session->getParameter("_user_id");
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->Session->getParameter("_auth_id");
            $language = $this->Session->getParameter("_lang");

            // インデックスリストの取得
            $query = "SELECT index_id, index_name, index_name_english, select_index_list_name, select_index_list_name_english ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE select_index_list_display = 1 ".
                     "AND is_delete = 0 ;";

            $result = $this->dbAccess->executeQuery($query);
            if (count($result) == 0) {
                return array();
            }

            // 閲覧可能なインデックスIDの取得
            $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);

            $displayIndexList = array();

            // インデックス名のリストを作成
            for ($ii = 0; $ii < count($result); $ii++)
            {
                // check user authority.
                if ($user_auth_id >= $adminBaseAuth && $auth_id >= $adminRoomAuth)
                {
                    // when admin, ok
                }
                // Mod OpenDepo 2014/01/31 S.Arata --start--
                else
                {
                    $publicIndex = $indexAuthorityManager->getPublicIndex(false, $adminBaseAuth, $adminRoomAuth, $result[$ii]["index_id"]);
                    if (count($publicIndex) == 0) {
                    // close index.
                    continue;
                }
                }
                // Mod OpenDepo 2014/01/31 S.Arata --end--

                $indexData = array();
                $indexData["index_id"] = $result[$ii]["index_id"];

                if ( strlen($result[$ii]["select_index_list_name"]) == 0 && strlen($result[$ii]["select_index_list_name_english"]) == 0){
                    if($language == "japanese"){
                        $indexData["select_index_list_name"] = $result[$ii]["index_name"];
                    } else {
                        $indexData["select_index_list_name"] = $result[$ii]["index_name_english"];
                    }
                } else if ($language == "japanese"){
                    if (strlen($result[$ii]["select_index_list_name"]) != 0){
                        $indexData["select_index_list_name"] = $result[$ii]["select_index_list_name"];
                    } else {
                        $indexData["select_index_list_name"] = $result[$ii]["select_index_list_name_english"];
                    }
                } else {
                    if (strlen($result[$ii]["select_index_list_name_english"]) != 0){
                        $indexData["select_index_list_name"] = $result[$ii]["select_index_list_name_english"];
                    } else {
                        $indexData["select_index_list_name"] = $result[$ii]["select_index_list_name"];
                    }
                }

                $indexData["select_index_list_name"] = htmlspecialchars($indexData["select_index_list_name"], ENT_QUOTES, "UTF-8");

                array_push($displayIndexList, $indexData);
            }
        }
        catch (RepositoryException $exception) {
            return array();
        }
        return $displayIndexList;
    }

    /**
     * 新規インデックスのデータを登録する
     *
     * @param var $isAddHead position of new index, head or tail
     * @param var $index_data a new index data
     * @param var $errorMsg error message
     * @param var $logFh log handle file
     */
    public function addIndex($isAddHead, &$index_data, &$errMsg, $logFh=null)
    {
        if ( isset($logFh) ){
            fwrite($logFh,  "-- Start addIndex --\n");
        }

        // 新規インデックスID取得
        try {
            $newIndexId = $this->newIndexId($logFh);
        } catch (RepositoryException $exception) {
            if ( isset($logFh) ){
                fwrite($logFh,  "  NewIndexID is none. Query is failed: ".$this->dbAccess->ErrorMsg()."\n");
                fwrite($logFh,  "-- End newIndexId --\n");
                $errMsg = "Not found new Index ID";
            }
            $errMsg = "Not found new Index ID";
            return false;
        }
        // 新規表示順番取得
        try {
            $showOrder = $this->newShowOrder($index_data["parent_index_id"], $isAddHead,$logFh);
        } catch (RepositoryException $exception) {
            if ( isset($logFh) ){
                fwrite($logFh,  "  NewShowOrder is none. Query is failed: ".$this->dbAccess->ErrorMsg()."\n");
                fwrite($logFh,  "-- End newShowOrder --\n");
            }
            $errMsg = "MySQL ERROR : Not found show order for ner index.\n";
            return false;
        }
        // Add new prefix 2014/01/15 T.Ichikawa --start--
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        $index_data["biblio_flag"] = 0;
        $index_data["online_issn"] = "";
        $query = "SELECT biblio_flag,online_issn ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE index_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $index_data["parent_index_id"];
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) > 0) {
            // biblio flag
            if($result[0]["biblio_flag"] != 0){
                $index_data["biblio_flag"] = $result[0]["biblio_flag"];
            }
            // online issn
            if($result[0]["online_issn"] != ""){
                $index_data["online_issn"] = $result[0]["online_issn"];
            }
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        // Add new prefix 2014/01/15 T.Ichikawa --end--
        // 新規インデックス情報を登録する
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_index (".
                 "index_id, ".
                 "index_name, ".
                 "index_name_english, ".
                 "parent_index_id, ".
                 "show_order, ".
                 "public_state, ".
                 "pub_date, ";
        if (isset($index_data["access_role"])) {
            $query .= "access_role, ";
        }
        if (isset($index_data["access_group"])) {
            $query .= "access_group, ";
        }
        $query .= "exclusive_acl_role, ".
                  "exclusive_acl_group, ";
        if (isset($index_data["comment"])) {
            $query .= "comment, ";
        }
        if (isset($index_data["display_more"])) {
            $query .= "display_more, ";
        }
        if (isset($index_data["rss_display"])) {
            $query .= "rss_display, ";
        }
        if (isset($index_data["repository_id"])) {
            $query .= "repository_id, ";
        }
        if (isset($index_data["set_spec"])) {
            $query .= "set_spec, ";
        }
        if (isset($index_data["create_cover_flag"])) {
            $query .= "create_cover_flag, ";
        }
        if (isset($index_data["owner_user_id"])) {
            $query .= "owner_user_id, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        if (isset($index_data["biblio_flag"])) {
            $query .= "biblio_flag, ";
        }
        if (isset($index_data["online_issn"])) {
            $query .= "online_issn, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        // Fix harvest_public_state 2014/03/15 Y.Nakao --start--
        if (isset($index_data["harvest_public_state"])) {
            $query .= "harvest_public_state, ";
        }
        // Fix harvest_public_state 2014/03/15 Y.Nakao --end--
        $query .= "ins_user_id, ".
                  "mod_user_id, ".
                  "ins_date, ".
                  "mod_date, ".
                  "is_delete) ".
                  "VALUES (?, ?, ?, ?, ?, ?, ?, ";
        if (isset($index_data["access_role"])) {
            $query .= "?, ";
        }
        if (isset($index_data["access_group"])) {
           $query .= "?, ";
        }
        $query .= "?, ?, ";
        if (isset($index_data["comment"])) {
           $query .= "?, ";
        }
        if (isset($index_data["display_more"])) {
           $query .= "?, ";
        }
        if (isset($index_data["rss_display"])) {
           $query .= "?, ";
        }
        if (isset($index_data["repository_id"])) {
           $query .= "?, ";
        }
        if (isset($index_data["set_spec"])) {
           $query .= "?, ";
        }
        if (isset($index_data["create_cover_flag"])) {
           $query .= "?, ";
        }
        if (isset($index_data["owner_user_id"])) {
           $query .= "?, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        if (isset($index_data["biblio_flag"])) {
           $query .= "?, ";
        }
        if (isset($index_data["online_issn"])) {
           $query .= "?, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        // Fix harvest_public_state 2014/03/15 Y.Nakao --start--
        if (isset($index_data["harvest_public_state"])) {
            $query .= "?, ";
        }
        // Fix harvest_public_state 2014/03/15 Y.Nakao --end--
        $query .= "?, ?, ?, ?, ?);";

        $params = array();
        $params[] = $newIndexId;
        $params[] = $index_data["index_name"];
        $params[] = $index_data["index_name_english"];
        $params[] = $index_data["parent_index_id"];
        $params[] = $showOrder;
        if($index_data["public_state"] == "true" || $index_data["public_state"] == "1"){
            $index_data["public_state"] = 1;
            $params[] = 1;
        } else {
            $index_data["public_state"] = 0;
            $params[] = 0;
        }
        $params[] = $index_data["pub_date"];
        if (isset($index_data["access_role"])) {
            $params[] = $index_data["access_role"];
        }
        // Mod Bug fix no.55 2014/03/24 T.Koyasu --start--
        if (isset($index_data["access_group"])) {
            $params[] = $index_data["access_group"];
        }
        // Mod Bug fix no.55 2014/03/24 T.Koyasu --end--
        if (isset($index_data["exclusive_acl_id,"])) {
        $params[] = $index_data["exclusive_acl_id,"]."|".$index_data["exclusive_acl_room_auth"];
        } else {
            $params[] = "|".$index_data["exclusive_acl_room_auth"];
        }
        if (isset($index_data["exclusive_acl_group_id"])) {
            $params[] = $index_data["exclusive_acl_group_id"];
        } else {
            $params[] =  "";
        }
        if (isset($index_data["comment"])) {
            $params[] = $index_data["comment"];
        }
        if (isset($index_data["display_more"])) {
            $params[] = $index_data["display_more"];
        }
        if (isset($index_data["rss_display"])) {
            $params[] = $index_data["rss_display"];
        }
        if (isset($index_data["repository_id"])) {
            $params[] = $index_data["repository_id"];
        }
        if (isset($index_data["set_spec"])) {
            $params[] = $index_data["set_spec"];
        }
        if (isset($index_data["create_cover_flag"])) {
            $params[] = $index_data["create_cover_flag"];
        }
        if (isset($index_data["owner_user_id"])) {
            $params[] = $index_data["owner_user_id"];
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        if (isset($index_data["biblio_flag"])) {
           $params[] = $index_data["biblio_flag"];
        }
        if (isset($index_data["online_issn"])) {
           $params[] = $index_data["online_issn"];
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        // Fix harvest_public_state 2014/03/15 Y.Nakao --start--
        if (isset($index_data["harvest_public_state"])) {
            $params[] = $index_data["harvest_public_state"];
        }
        // Fix harvest_public_state 2014/03/15 Y.Nakao --end--
        // 挿入処理共通パラメータ追加
        $this->addSystemPramsForInsert($params);

        if ( isset($logFh) ){
            fwrite($logFh, "  Execute query: ".$query."\n");
            foreach ($params as $key => $value){
                fwrite($logFh, "  Execute params :".$key.": ".$value."\n");
            }
            fwrite($logFh, get_class($this->dbAccess)."\n");
        }
        $result = $this->dbAccess->executeQuery($query, $params);

        if ( isset($logFh) ){
            fwrite($logFh, "    Complete execute query.\n");
        }

        // インデックスの権限を登録する
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $parentBaseAuth = null;
        $parentRoomAuth = null;
        $parentGroups = null;
        $parentPubState = null;
        $parentPubDate = null;
        $parentHarvestPubState = null;

        try {
            // 親インデックスの権限を取得
            $repositoryIndexAuthorityManager->getBrowsingAuth(
                $index_data["parent_index_id"],
                $parentBaseAuth,
                $parentRoomAuth,
                $parentGroups,
                $parentPubState,
                $parentPubDate,
                $parentHarvestPubState,
                $logFh
            );

            // インデックスの権限を登録
            $repositoryIndexAuthorityManager->updateBrowsingAuth(
                $newIndexId,
                $parentBaseAuth,
                $parentRoomAuth,
                $parentGroups,
                $parentPubState,
                $parentPubDate,
                $parentHarvestPubState,
                $index_data["exclusive_acl_role_id"],
                $index_data["exclusive_acl_room_auth"],
                explode(",", $index_data["exclusive_acl_group_id"]),
                $index_data["public_state"],
                $index_data["pub_date"],
                $index_data["harvest_public_state"],
                $logFh
            );
        } catch (RepositoryException $exception) {
            if ( isset($logFh) ){
                $str = "SQL ERROR : ".$this->dbAccess->ErrorMsg()."\n".$exception->getDetailMsg();
                fwrite($logFh, "  Insert BrowsingAuthority is failed. Query is failed: ".$str.".\n");
                fwrite($logFh, "-- End addBrowsingAuthority --\n");
            }
            $errMsg = "MySQL ERROR : Insert BrowsingAuthority is failed.\n";
            return false;
        }
        if ( isset($logFh) ){
            fwrite($logFh,  "  insertIndex completed.\n"."-- End addIndex --\n");
        }

        return $newIndexId;
    }

    /**
     * 指定したインデックスのパラメータの更新を行う
     * 更新時に親インデックスの権限を継承し、継承後の権限を子インデックスに継承する
     *
     * @param array $index_data an update index data
     */
    public function updateIndex(&$index_data)
    {
        // インデックス名英語が配列内にあるかをチェック
        if(strlen($index_data["index_name_english"]) == 0){
            return "noEnglishName";
        }
        // add issn and biblio flag 2014/04/18 T.Ichikawa --start--
        // ISSNの値が形式に沿ったものであるかチェック
        $issn_flg = "";
        $issn_flg = $this->checkIssnFormat($index_data["online_issn"]);
        if($issn_flg == "error"){
            return "wrongFormatIssn";
        }
        
        // ISSNに変更があるか判定するフラグ値
        $change_issn = false;
        // add issn and biblio flag 2014/04/18 T.Ichikawa --end--

        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET ";
        if(isset($index_data["index_name"])){
            $query .= "index_name = ?, ";
        }
        $query .= "index_name_english = ?, ";
        if(isset($index_data["parent_index_id"])){
            $query .= "parent_index_id = ?, ";
        }
        if(isset($index_data["show_order"])){
            $query .= "show_order = ?, ";
        }
        if(isset($index_data["public_state"])){
            $query .= "public_state = ?, ";
        }
        if(isset($index_data["pub_date"])){
            $query .= "pub_date = ?, ";
        }
        if(isset($index_data["access_role"]) || isset($index_data["access_role_id"]) || isset($index_data["access_role_room"])){
            $query .= "access_role = ?, ";
        }
        if(isset($index_data["access_group"]) || isset($index_data["access_group_id"])){
            $query .= "access_group = ?, ";
        }
        if(isset($index_data["exclusive_acl_role"]) || isset($index_data["exclusive_acl_role_id"]) || isset($index_data["exclusive_acl_room_auth"])) {
            $query .= "exclusive_acl_role = ?, ";
        }
        if(isset($index_data["exclusive_acl_group_id"])) {
            $query .= "exclusive_acl_group = ?, ";
        } elseif (isset($index_data["exclusive_acl_group"])) {
            $index_data["exclusive_acl_group_id"] = $index_data["exclusive_acl_group"];
            $query .= "exclusive_acl_group = ?, ";
        }
        if(isset($index_data["comment"])){
            $query .= "comment = ?, ";
        }
        if(isset($index_data["display_more"])){
            $query .= "display_more = ?, ";
        }
        if(isset($index_data["display_type"])){
            $query .= "display_type = ?, ";
        }
        if(isset($index_data["select_index_list_display"])){
            $query .= "select_index_list_display = ?, ";
        }
        if(isset($index_data["select_index_list_name"])){
            $query .= "select_index_list_name = ?, ";
        }
        if(isset($index_data["select_index_list_name_english"])){
            $query .= "select_index_list_name_english = ?, ";
        }
        if(isset($index_data["rss_display"])){
            $query .= "rss_display = ?, ";
        }
        if(isset($index_data["owner_user_id"])){
            $query .= "owner_user_id = ?, ";
        }
        if(isset($index_data["repository_id"])){
            $query .= "repository_id = ?, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        if (isset($index_data["biblio_flag"])) {
            $query .= "biblio_flag = ?, ";
        }
        if (isset($index_data["online_issn"])) {
            $query .= "online_issn = ?, ";
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        if(isset($index_data["set_spec"])){
            $query .= "set_spec = ?, ";
        }
        if(isset($index_data["create_cover_flag"])){
            $query .= "create_cover_flag = ?, ";
        }
        if(isset($index_data["harvest_public_state"])){
            $query .= "harvest_public_state = ?, ";
        }
        $query .= "mod_user_id = ?, ".
                  "mod_date = ?, ".
                  "is_delete = ? ".
                  "WHERE index_id = ?; ";
        $params = array();
        if(isset($index_data["index_name"])){
            $params[] = $index_data["index_name"];
        }
        $params[] = $index_data["index_name_english"];
        if(isset($index_data["parent_index_id"])){
            $params[] = $index_data["parent_index_id"];
        }
        if(isset($index_data["show_order"])){
            $params[] = $index_data["show_order"];
        }
        if($index_data["public_state"] == "true" || $index_data["public_state"] == "1"){
            $index_data["public_state"] = 1;
            $params[] = 1;
        } elseif (isset($index_data["public_state"])) {
            $index_data["public_state"] = 0;
            $params[] = 0;
        }
        if(isset($index_data["pub_date"])){
            if(strlen($index_data["pub_date"]) >= 23)
            {
                $params[] = $index_data["pub_date"];
            }
            else
            {
                $params[] = $index_data["pub_date"]." 00:00:00.000";
            }
        }
        if(isset($index_data["access_role"])){
            $params[] = $index_data["access_role"];
        } else {
            $params[] = $index_data["access_role_id"]."|".$index_data["access_role_room"];
        }
        if(isset($index_data["access_group"])){
            $params[] = $index_data["access_group"];
        } elseif (isset($index_data["access_group_id"])){
            $params[] = $index_data["access_group_id"];
        }
        if(isset($index_data["exclusive_acl_role"])) {
            $params[] = $index_data["exclusive_acl_role"];
        } elseif(isset($index_data["exclusive_acl_role_id"]) || isset($index_data["exclusive_acl_room_auth"])) {
            $params[] = $index_data["exclusive_acl_role_id"]. '|'. $index_data["exclusive_acl_room_auth"];
        }
        if(isset($index_data["exclusive_acl_group_id"])) {
            $params[] = $index_data["exclusive_acl_group_id"];
        }
        if(isset($index_data["comment"])) {
            $params[] = $index_data["comment"];
        }
        if(isset($index_data["display_more"])) {
            $params[] = $index_data["display_more"];
        }
        if(isset($index_data["display_type"])) {
            $params[] = $index_data["display_type"];
        }
        if(isset($index_data["select_index_list_display"])) {
            $params[] = $index_data["select_index_list_display"];
        }
        if(isset($index_data["select_index_list_name"])) {
            $params[] = $index_data["select_index_list_name"];
        }
        if(isset($index_data["select_index_list_name_english"])) {
            $params[] = $index_data["select_index_list_name_english"];
        }
        if(isset($index_data["rss_display"])) {
            $params[] = $index_data["rss_display"];
        }
        if(isset($index_data["owner_user_id"])){
           $params[] = $index_data["owner_user_id"];
        }
        if(isset($index_data["repository_id"])){
            $params[] = $index_data["repository_id"];
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        if (isset($index_data["biblio_flag"])) {
            $params[] = $index_data["biblio_flag"];
        }
        if (isset($index_data["online_issn"])) {
            $params[] = $index_data["online_issn"];
            if($index_data["online_issn"] != $this->checkOnlineIssnValue($index_data["index_id"])) {
                $change_issn = true;
            }
        }
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        if(isset($index_data["set_spec"])){
            $params[] = $index_data["set_spec"];
        }
        if(isset($index_data["create_cover_flag"])){
            $params[] = $index_data["create_cover_flag"];
        }
        if(isset($index_data["harvest_public_state"])){
            $params[] = $index_data["harvest_public_state"];
        }
        // 更新処理共通パラメータ追加
        $this->addSystemPramsForUpdate($params);
        $params[] = 0;                                          // is_delete
        $params[] = $index_data["index_id"];                    // 検索条件

        // クエリ実行
        $result = $this->dbAccess->executeQuery($query,$params);

        // サムネイル情報の更新
        $thumbnail = $this->Session->getParameter("tree_thumbnail");
        if(isset($index_data["thumbnail_del"]) && $index_data["thumbnail_del"] == 1 &&
            (!is_array($thumbnail) || count($thumbnail) == 0) ){
            // サムネイル削除
            $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                    " SET thumbnail = ?, ".
                    " thumbnail_name = ?, ".
                    " thumbnail_mime_type = ? ".
                    " WHERE index_id = ? ";
            $params = array();
            $params[] = "";
            $params[] = $thumbnail["file_name"];
            $params[] = $thumbnail["mimetype"];
            $params[] = $index_data["index_id"];
            $result = $this->dbAccess->executeQuery($query,$params);
        } else if(is_array($thumbnail) && count($thumbnail) > 0 &&
            is_numeric(strpos($thumbnail["mimetype"],"image"))){
            // サムネイル登録
            $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                    " SET thumbnail = ?, ".
                    " thumbnail_name = ?, ".
                    " thumbnail_mime_type = ? ".
                    " WHERE index_id = ? ";
            $params = array();
            $params[] = "";
            $params[] = $thumbnail["file_name"];
            $params[] = $thumbnail["mimetype"];
            $params[] = $index_data["index_id"];
            $result = $this->dbAccess->executeQuery($query,$params);
            $path = WEBAPP_DIR. "/uploads/repository/".$thumbnail["physical_file_name"];
            $ret = $this->dbAccess->updateBlobFile(
                "repository_index",
                "thumbnail",
                $path,
                "index_id = ". $index_data["index_id"],
                "LONGBLOB"
            );
        }
        $this->Session->removeParameter("tree_thumbnail");

        // インデックスの権限を更新する
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $parentBaseAuth = null;
        $parentRoomAuth = null;
        $parentGroups = null;
        $parentPubState = null;
        $parentPubDate = null;
        $parentHarvestPubState = null;

        // 親インデックスの権限を取得
        $repositoryIndexAuthorityManager->getBrowsingAuth(
            $index_data["parent_index_id"],
            $parentBaseAuth,
            $parentRoomAuth,
            $parentGroups,
            $parentPubState,
            $parentPubDate,
            $parentHarvestPubState
        );

        // インデックスの権限を更新
        $childExclusiveGroup = explode(",", $index_data["exclusive_acl_group_id"]);
        $repositoryIndexAuthorityManager->updateBrowsingAuth(
            $index_data["index_id"],
            $parentBaseAuth,
            $parentRoomAuth,
            $parentGroups,
            $parentPubState,
            $parentPubDate,
            $parentHarvestPubState,
            $index_data["exclusive_acl_role_id"],
            $index_data["exclusive_acl_room_auth"],
            $childExclusiveGroup,
            $index_data["public_state"],
            $index_data["pub_date"],
            $index_data["harvest_public_state"]
        );

        // 子インデックスに権限を継承する
        $this->updateDependentIndexParam(
            $index_data["index_id"],
            $index_data["exclusive_acl_role_id"],
            $index_data["exclusive_acl_room_auth"],
            $childExclusiveGroup,
            $index_data["public_state"],
            $index_data["pub_date"],
            $index_data["harvest_public_state"],
            // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
            $index_data["biblio_flag"],
            $index_data["online_issn"]
            // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        );
        
        // online_issn設定を変更に合わせて、雑誌情報テーブルを修正する
        if($change_issn && strlen($index_data["online_issn"]) != 0) {
            $this->updateIssnTable($index_data["online_issn"], $index_data["index_id"], $index_data["index_name"], $index_data["index_name_english"]);
    }
    }

    /**
     * 指定したインデックスを、子孫のインデックスも含めて論理削除を行う
     * 削除したインデックスの下にプライベートツリーが存在する場合は、プライベートツリーをルートインデックスの下に移動する
     *
     * @param var $index_data a delete index data
     */
    public function deleteIndex($index_id)
    {
        // インデックスを削除する
        $this->deleteAllChildIndex($index_id);

        // プライベートツリーのIDを取得
        $parentPrivateTreeId = null;
        $userAuthorityManager = new RepositoryUserAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $userAuthorityManager->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $error_msg);

        // プライベートツリーをルートインデックスの下に移動
        if($parentPrivateTreeId == $index_id){
            $params = array();
            $params[] = "0";                    // param_value
            // 更新処理共通パラメータ追加
            $this->addSystemPramsForUpdate($params);
            $params[] = "privatetree_parent_indexid";               // param_name
            $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".  // パラメタテーブル
                     "SET param_value = ?, ".       // パラメタ値
                     "mod_user_id = ?, ".           // 更新ユーザID
                     "mod_date = ? ".               // 更新日
                     "WHERE param_name = ?; ";      // パラメタ名(PK)
            // UPDATE実行
            $this->dbAccess->executeQuery($query, $params);
        }
    }

    /**
     * 子孫のインデックスの論理削除を行ったのち、指定したインデックスの論理削除を行う
     *
     * @param var $index_data a delete index data
     */
    private function deleteAllChildIndex($indexId) {
        // 子インデックスを取得
        $childIndexList = $this->getAllChildIndex($indexId);

        // 子インデックスを削除
        for ($ii=0; $ii<count($childIndexList); $ii++){
            $this->deleteAllChildIndex($childIndexList[$ii]["index_id"]);
        }

        // インデックスを削除
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index SET ".
            "mod_user_id = ?, ".
            "del_user_id = ?, ".
            "mod_date = ?, ".
            "del_date = ?, ".
            "is_delete = ? ".
            "WHERE index_id = ?;";
        $params = array();
        // 削除処理共通パラメータ追加
        $this->addSystemPramsForDelete($params);
        $params[] = $indexId;                                   // index_id
        // UPDATE実行
        $this->dbAccess->executeQuery($query, $params);

        // インデックスの権限を削除
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $repositoryIndexAuthorityManager->deleteBrowsingAuth($indexId);
    }

    /**
     * 親インデックスIDに紐づく子インデックスに親から子に設定値を再帰的に継承する
     *
     * @param var $parentIndexId a parent index data
     * @param var $exclusiveBaseAuth a parent base auth
     * @param var $exclusiveRoomAuth a parent room auth
     * @param var $exclusiveGroup a parent groups
     * @param var $publicState
     * @param var $publicDate
     * @param var $harvestPublicState
     */
    private function updateDependentIndexParam( $parentIndexId,
                                                $exclusiveBaseAuth,
                                                $exclusiveRoomAuth,
                                                $exclusiveGroup,
                                                $publicState,
                                                $publicDate,
                                                $harvestPublicState,
                                                $biblioFlag,
                                                $onlineIssn) {
        // 子インデックスを取得
        $childIndexList = $this->getAllChildIndex($parentIndexId);

        // 子インデックスを更新
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        for ($ii=0; $ii<count($childIndexList); $ii++)
        {
            $childExclusiveGroup = explode(",", $childIndexList[$ii]["exclusive_acl_group_id"]);
            $repositoryIndexAuthorityManager->updateBrowsingAuth(
                $childIndexList[$ii]["index_id"],
                $exclusiveBaseAuth,
                $exclusiveRoomAuth,
                $exclusiveGroup,
                $publicState,
                $publicDate,
                $harvestPublicState,
                $childIndexList[$ii]["exclusive_acl_role_id"],
                $childIndexList[$ii]["exclusive_acl_room_auth"],
                $childExclusiveGroup,
                $childIndexList[$ii]["public_state"],
                $childIndexList[$ii]["pub_date"],
                $childIndexList[$ii]["harvest_public_state"]
            );
            
            // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
            if($biblioFlag != 0){
                $this->updateIndexBiblioFlag($biblioFlag, $childIndexList[$ii]["index_id"]);
            }
            if(strlen($onlineIssn) != 0){
                $this->updateIndexOnlineIssn($onlineIssn, $childIndexList[$ii]["index_id"]);
            }
            // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
            // 子インデックスの更に子インデックスを更新
            $this->updateDependentIndexParam(
                $childIndexList[$ii]["index_id"],
                $childIndexList[$ii]["exclusive_acl_role_id"],
                $childIndexList[$ii]["exclusive_acl_room_auth"],
                $childExclusiveGroup,
                $childIndexList[$ii]["public_state"],
                $childIndexList[$ii]["pub_date"],
                $childIndexList[$ii]["harvest_public_state"], 
                $biblioFlag,
                $onlineIssn
            );
        }
    }

    /**
     * 子インデックスの権限情報を取得する
     *
     * @param var $parentIndexId a parent index data
     */
    private function getAllChildIndex($indexId) {
        // 子のインデックス情報を取得する
        $query = "SELECT ".
            "index_id, ".
            "exclusive_acl_role, ".
            "exclusive_acl_group AS `exclusive_acl_group_id`, ".
            "public_state, ".
            "pub_date, ".
            "harvest_public_state ".
            "FROM ". DATABASE_PREFIX ."repository_index ".
            "WHERE parent_index_id = ? AND ".
            "is_delete = ?;";
        $params = array();
        $params[] = $indexId;
        $params[] = 0;

        // クエリ実行
        $childIndexList = $this->dbAccess->executeQuery($query, $params);

        // 子インデックス情報のリストを作成する
        $retChildIndexList = array();
        for ($ii=0; $ii<count($childIndexList); $ii++){
            $childIndexData = array();
            $childIndexData["index_id"] = $childIndexList[$ii]["index_id"];
            // ベース権限とルーム権限を分割する
            $exclusive_acl_role = explode("|", $childIndexList[$ii]["exclusive_acl_role"]);
            if (strlen($exclusive_acl_role[0]) == 0) {
                $childIndexData["exclusive_acl_role_id"] = "0";
            } else {
                $childIndexData["exclusive_acl_role_id"] = $exclusive_acl_role[0];
            }

            if (strlen($exclusive_acl_role[1]) == 0) {
                $childIndexData["exclusive_acl_room_auth"] = "-1";
            } else {
                $childIndexData["exclusive_acl_room_auth"] = $exclusive_acl_role[1];
            }

            $childIndexData["exclusive_acl_group_id"] = $childIndexList[$ii]["exclusive_acl_group_id"];

            $childIndexData["public_state"] = $childIndexList[$ii]["public_state"];
            $childIndexData["pub_date"] = $childIndexList[$ii]["pub_date"];
            $childIndexData["harvest_public_state"] = $childIndexList[$ii]["harvest_public_state"];

            array_push($retChildIndexList, $childIndexData);
        }

        return $retChildIndexList;
    }

    /**
     * インデックステーブルからユーザーが入力したインデックスの閲覧権限を取得し、
     * 検索テーブル用の閲覧権限を作成し、登録する
     *
     */
    public function createIndexBrowsingAuthority() {
        // update index browsing authority
        $this->updateDependentIndexParam(0, // index_id
                                        0,  // default base auth_id
                                        -1, // default room auth id
                                        '', // default group id
                                        1,  // public state
                                        "0000-00-00 00:00:00.000", // public date
                                        1,  // harvest public state
                                        0,  // biblio flag
                                        '');// online issn
    }

    /**
     * インデックステーブルのOnlineISSNの値を更新する
     * 
     * @param string $onlineIssn
     * @param int $indexId
     */
    private function updateIndexOnlineIssn($onlineIssn, $indexId)
    {
        if(strlen($onlineIssn) != 0){
            $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                     "SET online_issn = ? ".
                     "WHERE index_id = ? ".
                     "AND is_delete = ? ;";
            $params = array();
            $params[] = $onlineIssn;
            $params[] = $indexId;
            $params[] = 0;
            $this->dbAccess->executeQuery($query, $params);
        }
    }

    /**
     * インデックステーブルのBiblioFlagの値を更新する
     * 
     * @param string $biblioFlag
     * @param int $indexId
     */
    private function updateIndexBiblioFlag($biblioFlag, $indexId)
    {
        if($biblioFlag != 0){
            $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                     "SET biblio_flag = ? ".
                     "WHERE index_id = ? ".
                     "AND is_delete = ? ;";
            $params = array();
            $params[] = $biblioFlag;
            $params[] = $indexId;
            $params[] = 0;
            $this->dbAccess->executeQuery($query, $params);
        }
    }
    
    private function checkIssnFormat(&$issn)
    {
        $issn_flg = "";
        if(!isset($issn) || strlen($issn) == 0) {
            // 値が入っていないか空文字だった場合、何も処理を行わない
        } else if(strlen($issn) != mb_strlen($issn)) {
            // 2バイト文字が含まれていた場合エラー
            $issn_flg = "error";
        } else if(preg_match("/^\d{7}[a-zA-Z0-9]$/", $issn)) {
            // 7桁の数字+1桁の英数字の場合、ハイフンを挿入する
            $issn = preg_replace("/^(\d{4})(\d{3}[a-zA-Z0-9])$/", "$1-$2", $issn);
        } else if(preg_match("/^\d{4}\-\d{3}[a-zA-Z0-9]$/", $issn)) {
            // ハイフン有の7桁の数字+1桁の英数字の場合、何も処理を行わない
        } else {
            // その他の値の場合
            $issn_flg = "error";
        }
        
        return $issn_flg;
    }
    
    /**
     * 変更前のISSN設定値を取得する
     */
    private function checkOnlineIssnValue($index_id) {
        $query = "SELECT online_issn FROM ". DATABASE_PREFIX. "repository_index ".
                 "WHERE index_id = ? ;";
        $params = array();
        $params[] = $index_id;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        return $result[0]["online_issn"];
    }
    
    /**
     * 雑誌情報テーブルの値を更新する
     */
     private function updateIssnTable($online_issn, $index_id, $index_name, $index_name_english) {
         //SetSpecの値を再帰的に取得する
         $query = "SELECT parent_index_id FROM ". DATABASE_PREFIX. "repository_index ".
                  "WHERE index_id = ? ;";
         $params = array();
         $params[] = $index_id;
         $result = $this->dbAccess->executeQuery($query, $params);
         $specs = array();
         array_unshift($specs, sprintf('%05d', $index_id));
         $this->strictlySetSpec($result[0]["parent_index_id"], $specs);
         $set_spec = "";
         for($ii = 0; $ii < count($specs); $ii++) {
             if(strlen($specs[$ii]) != 0){
                 if($ii != 0 ) {
                     $set_spec .= ":";
                 }
                 $set_spec .= $specs[$ii];
             }
         }
         
         // 雑誌情報テーブルに同じISSNの値が存在しない場合、新しく追加する
         // 存在していた場合はインデックス名の更新を実行する
         $query = "INSERT INTO ". DATABASE_PREFIX. "repository_issn ".
                  "(issn, jtitle, jtitle_en, set_spec, ".
                  "ins_user_id, mod_user_id, del_user_id, ".
                  "ins_date, mod_date, del_date, is_delete) ".
                  "VALUES ".
                  "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ".
                  "ON DUPLICATE KEY UPDATE ".
                  "jtitle = ?, ".
                  "jtitle_en = ?, ".
                  "set_spec = ?, ".
                  "mod_user_id = ?, ".
                  "mod_date = ?, ".
                  "is_delete = ? ;";
         $params = array();
         $params[] = $online_issn;
         $params[] = $index_name;
         $params[] = $index_name_english;
         $params[] = $set_spec;
         $params[] = $this->Session->getParameter('_user_id');
         $params[] = $this->Session->getParameter('_user_id');
         $params[] = "0";
         $params[] = $this->transStartDate;
         $params[] = $this->transStartDate;
         $params[] = "";
         $params[] = 0;
         $params[] = $index_name;
         $params[] = $index_name_english;
         $params[] = $set_spec;
         $params[] = $this->Session->getParameter('_user_id');
         $params[] = $this->transStartDate;
         $params[] = 0;
         $result = $this->dbAccess->executeQuery($query, $params);
     }
    
    private function strictlySetSpec($index_id, &$specs) {
        if($index_id != 0) {
             $query = "SELECT parent_index_id FROM ". DATABASE_PREFIX. "repository_index ".
                      "WHERE index_id = ? ;";
             $params = array();
             $params[] = $index_id;
             $result = $this->dbAccess->executeQuery($query, $params);
             array_unshift($specs, sprintf('%05d', $index_id));
             $this->strictlySetSpec($result[0]["parent_index_id"], $specs);
        }
    }
}
?>
