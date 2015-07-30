<?php
// --------------------------------------------------------------------
//
// $Id: Update.class.php 41684 2014-09-18 00:04:18Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/BackgroundProcess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';

/**
 * Harvesting
 *
 * @package     NetCommons
 * @author      A.Suzuki(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Search_Update extends BackgroundProcess
{
    // all metadata key
    const MAX_RECORDS = "1";
    // all metadata table name
    const PARAM_NAME = "Repository_Action_Common_Search_Update";
    
    /**
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * get renewal item of a search table 
     *
     * @param itemList Processing data
     */
    protected function prepareBackgroundProcess(&$itemList)
    {
        // get update item
        $query = "SELECT item_id, item_no ".
                 "FROM ". DATABASE_PREFIX ."repository_search_update_item ".
                 "ORDER BY item_id ".
                 "LIMIT 0, ".self::MAX_RECORDS.";";
        $itemList = $this->dbAccess->executeQuery($query);
        if(count($itemList) == 0){
            return false;
        }
        return true;
    }
    
    /**
     * update search table of a processing data item 
     *
     * @param itemList Processing data
     */
    protected function executeBackgroundProcess($itemList)
    {
        // update search table
        $searchPrcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchPrcessing->setDataToSearchTable($itemList);
        
        // delete update item form repository_search_update_item table
        
        $query = "DELETE FROM ". DATABASE_PREFIX ."repository_search_update_item ".
                 "WHERE (item_id, item_no) IN (";
        $params = array();
        $count = 0;
        for($ii = 0; $ii < count($itemList); $ii++){
            if($count > 0){
                $query .= ",";
            }
            $query .= "(?,?)";
            $params[] = $itemList[$ii]["item_id"];
            $params[] = $itemList[$ii]["item_no"];
            $count++;
        }
        if($count == 0){
        	return;
        } else {
        	$query .= ");";
        }
        $itemList = $this->dbAccess->executeQuery($query, $params);
    }
    
}
?>