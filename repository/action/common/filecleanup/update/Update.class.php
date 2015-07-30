<?php
// --------------------------------------------------------------------
//
// $Id: Update.class.php 32789 2014-03-13 08:46:25Z rei_matsuura $
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

/**
 * Delete deleted file in background process
 *
 * @package     NetCommons
 * @author      R.Matsuura(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Filecleanup_Update extends BackgroundProcess
{
    // all metadata key
    const MAX_RECORDS = "50";
    // all metadata table name
    const PARAM_NAME = "Repository_Action_Common_Filecleanup_Update";
    
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
     * @param fileList deleted file
     */
    protected function prepareBackgroundProcess(&$fileList)
    {
        // get update item
        $query = "SELECT item_id, attribute_id, file_no, extension ".
                 "FROM ". DATABASE_PREFIX ."repository_filecleanup_deleted_file ".
                 "ORDER BY item_id ASC, attribute_id ASC, file_no ASC ".
                 "LIMIT 0, ".self::MAX_RECORDS.";";
        $fileList = $this->dbAccess->executeQuery($query);
        if(count($fileList) == 0){
            return false;
        }
        return true;
    }
    
    /**
     * update search table of a processing data item 
     *
     * @param fileList deleted file
     */
    protected function executeBackgroundProcess($fileList)
    {
        
        set_time_limit(0);
        
        $fileContentsPath = $this->getFileSavePath("file");
        if(strlen($fileContentsPath) == 0){
            // default directory
            $fileContentsPath = BASE_DIR.'/webapp/uploads/repository/files';
        }
        for($ii=0; $ii<count($fileList); $ii++)
        {
            $filePath = $fileContentsPath."/".$fileList[$ii]["item_id"]."_".
                        $fileList[$ii]["attribute_id"]."_".$fileList[$ii]["file_no"].".".
                        $fileList[$ii]["extension"];
            if(file_exists($filePath))
            {
                chmod($filePath, 0777 );
                unlink($filePath);
            }
        }
        
        // delete record from repository_filecleanup_deleted_file
        $query = "DELETE FROM ".DATABASE_PREFIX ."repository_filecleanup_deleted_file ".
                 "WHERE (item_id, attribute_id, file_no) IN (";
        $params = array();
        $count = 0;
        for($ii = 0; $ii < count($fileList); $ii++){
            if($count > 0){
                $query .= ",";
            }
            $query .= "(?,?,?)";
            $params[] = $fileList[$ii]["item_id"];
            $params[] = $fileList[$ii]["attribute_id"];
            $params[] = $fileList[$ii]["file_no"];
            $count++;
        }
        if($count == 0){
        	return;
        } else {
        	$query .= ");";
        }
        $fileList = $this->dbAccess->executeQuery($query, $params);
    }
    
}
?>