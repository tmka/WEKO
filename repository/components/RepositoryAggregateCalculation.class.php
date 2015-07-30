<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryAggregateCalculation.class.php 43113 2014-10-21 04:02:44Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/**
 * RepositoryAggregateCalculation
 */
 
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';

class RepositoryAggregateCalculation extends RepositoryLogicBase
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
    
    /**
     * countItem 
     * 
     * @return array $items
     */
    public function countItem()
    {
        // set values
        $items = array();
        $items["total"] = $this->cntTotalItems();
        $items["public"] = $this->cntPublicItems();
        $items["private"] = $items["total"] - $items["public"];
        $items["includeFulltext"] = $this->cntFileExistsItems();
        $items["excludeFulltext"] = $items["total"] - $items["includeFulltext"];
        
        return $items;
    }
    
    /**
     * アイテム総数を取得
     * 
     * @return アイテム総数
     */
    private function cntTotalItems()
    {
        // get total items
        $query = "SELECT count(*) AS RESULT ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE is_delete = ?;";
        $params = array();
        $params[] = 0;
        
        $result = $this->dbAccess->executeQuery($query, $params);
        return $result[0]["RESULT"];
    }
    
    /**
     * アイテムの所属インデックスに対してグループごとの閲覧権限が設定されているか
     * 
     * @return boolean ture:グループに対して閲覧権限が設定されている
     */
    private function isIndexBrowsingGroups()
    {
        $notNullGroupTable = false;
        $count_query = "SELECT index_id ".
                       "FROM ".DATABASE_PREFIX."repository_index_browsing_groups ;";
        $count_result = $this->dbAccess->executeQuery($count_query);
        if(isset($count_result) && count($count_result) > 0)
        {
            $notNullGroupTable = true;
        }
        return $notNullGroupTable;
    }
    
    private function cntPublicItems()
    {
        $notNullGroupTable = $this->isIndexBrowsingGroups();
        
        // get released items
        $query = "SELECT COUNT( ITEM_IDS.TOTAL ) AS RESULT FROM ( ".
                     "SELECT ITEM.item_id AS TOTAL ".
                     "FROM ".DATABASE_PREFIX."repository_item AS ITEM, ".
                     DATABASE_PREFIX."repository_index AS IDX, ".
                     DATABASE_PREFIX."repository_position_index AS POS, ".
                     DATABASE_PREFIX."repository_index_browsing_authority AS AUTH ";
        $query .=    "WHERE ITEM.is_delete = ? ".
                     "AND ITEM.item_id = POS.item_id ".
                     "AND ITEM.item_no = POS.item_no ".
                     "AND ITEM.shown_status = ? ".
                     "AND ITEM.shown_date <= NOW() ".
                     "AND POS.index_id = AUTH.index_id ";
        if($notNullGroupTable)
        {
            // グループに対して閲覧権限が設定されているものは「公開」ではないため集計から除外する
            $query .=    "AND (".
                     "     POS.index_id NOT IN ( ".
                     "         SELECT GROUPS.index_id ".
                     "         FROM ".DATABASE_PREFIX."repository_index_browsing_groups AS GROUPS ".
                     "         WHERE GROUPS.exclusive_acl_group_id = ? ".
                     "         AND GROUPS.is_delete = ? ".
                     "     ) ".
                     "    ) ";
        }
        $query .=    "AND IDX.index_id = POS.index_id ".
                     "AND IDX.is_delete = ? ".
                     "AND ! ( ".
                     "    AUTH.public_state = ? ".
                     "    OR AUTH.pub_date > NOW() ".
                     "    OR AUTH.exclusive_acl_role_id > ? ".
                     "    OR AUTH.exclusive_acl_room_auth > ? ".
                     ") ".
                     "GROUP BY ITEM.item_id ".
                 ") AS ITEM_IDS; ";
        
        $params = array();
        $params[] = 0;
        $params[] = 1;
        if($notNullGroupTable)
        {
            $params[] = 0;
            $params[] = 0;
        }
        $params[] = 0;
        $params[] = 0;
        $params[] = 0;
        $params[] = -1;
        
        $result = $this->dbAccess->executeQuery($query, $params);
        return $result[0]["RESULT"];
    }
    
    /**
     * 本文ありのアイテム数
     * 
     * @return 本文ありアイテム数
     */
    private function cntFileExistsItems()
    {
        $query = "SELECT COUNT(DISTINCT item_id) AS RESULT ".
                " FROM {repository_file} ".
                " WHERE is_delete = ? ";
        $params = array();
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        return $result[0]["RESULT"];
    }
}
?>
