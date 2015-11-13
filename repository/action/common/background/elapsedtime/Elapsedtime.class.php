<?php
// --------------------------------------------------------------------
//
// $Id: Elapsedtime.class.php 52763 2015-04-28 00:12:16Z shota_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/BackgroundProcess.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Background_Elapsedtime extends BackgroundProcess
{
    const PARAM_NAME = "Repository_Action_Common_Background_Elapsedtime";
    
    /**
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * search unregistered log
     */
    protected function prepareBackgroundProcess(&$target)
    {
        // get unregistered logs 
        $unregisteredLogs = $this->getUnregisteredLogs();
        
        if (count($unregisteredLogs) == 0){
            return false;
        }
        
        $target = $unregisteredLogs;
        
        return true;
    }
    
    /** 
     * execute background process
     */
    protected function executeBackgroundProcess(&$target) 
    {
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        
        for ($ii = 0; $ii < count($target); $ii++) 
        {
            $logManager->insertElapsedTimeRecord(
                $target[$ii]["operation_id"],
                $target[$ii]["record_date"],
                $target[$ii]["ip_address"],
                $target[$ii]["user_agent"],
                $target[$ii]["item_id"],
                $target[$ii]["item_no"],
                $target[$ii]["attribute_id"],
                $target[$ii]["file_no"],
                $target[$ii]["user_id"],
                $target[$ii]["log_no"],
                $target[$ii]["search_keyword"]
            );
        }
    }
    
    /** 
     * get unregistered logs from log table
     */
    private function getUnregisteredLogs()
    {
        $query = "SELECT * ". 
                 "FROM ". DATABASE_PREFIX. "repository_log " . 
                 "WHERE log_no NOT IN ( ".
                     "SELECT log_no ". 
                     "FROM ". DATABASE_PREFIX. "repository_log_elapsed_time " . 
                  ") " . 
                  "LIMIT 100 ; ";
        
        $result = $this->Db->execute($query);
        
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
}
?>
