<?php
// --------------------------------------------------------------------
//
// $Id: BackgroundProcess.class.php 55181 2015-07-02 09:25:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryProcessUtility.class.php';

class BackgroundProcess extends RepositoryAction
{
    /**
     * process name for lock
     *
     * @var string
     */
    private $process_name = null;
    
    /**
     * background process finish flag
     *
     * @var string
     */
    private $isFinish = false;
    
    /**
     * constructer
     *
     * @param paramter 
     */
    protected function __construct($parameter)
    {
        $this->process_name = $parameter;
    }
    
    /**
     * execute
     */
    protected function executeApp()
    {
        $this->exitFlag = true;
        
        // check process
        $status = $this->lockProcess();
        
        // init background process
        if($status != 0){
            $this->isFinish = true;
            return;
        }
        
        // get target 
        $executeFlag = $this->prepareBackgroundProcess($target);
        
        if($executeFlag == false){
            $this->unlockProcess();
            $this->isFinish = true;
            return;
        }
        
        // execute Background Process
        $this->executeBackgroundProcess($target);
        
        // execute next process
        $this->unlockProcess();
    }
    
    /**
     * トランザクション外後処理
     * 
     * 次の処理を呼び出す
     */
    final protected function afterTrans()
    {
        if(!$this->isFinish)
        {
            $this->callAsyncProcess();
        }
    }
    
    /**
     * check and lock background process
     */
    private function lockProcess()
    {
        // update process status
        $query = "UPDATE ".DATABASE_PREFIX."repository_lock ".
                 "SET status = ? ".
                 "WHERE process_name = ? ".
                 "AND status = ?;";
        $params = array();
        $params[] = 1;
        $params[] = $this->process_name;
        $params[] = 0;
        $retRef = $this->dbAccess->executeQuery($query, $params);
        $count = $this->dbAccess->affectedRows();
        
        if($count == 0){
            return 1;
        } 
        return 0;
    }
    
    /**
     * prepare background process
     */
    protected function prepareBackgroundProcess(&$target)
    {
        // for override
        return true;
    }
    
    /**
     * execute background process
     */
    protected function executeBackgroundProcess($target)
    {
        // for override
    }
    
    /**
     * Call another process by async
     */
    private function callAsyncProcess()
    {
        // Request parameter for next URL
        $nextRequest = BASE_URL;
        $count = 0;
        foreach($_GET as $key => $value){
            if($count == 0){
                $nextRequest .= "/?";
            } else {
                $nextRequest .= "&";
            }
            $nextRequest .= $key."=".$value;
            $count++;
        }
        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }
    
    /**
     * unlock background process
     */
    private function unlockProcess()
    {
        // update process status
        $query = "UPDATE ".DATABASE_PREFIX."repository_lock ".
                 "SET status = ? ".
                 "WHERE process_name = ?; ";
        $params = array();
        $params[] = 0;
        $params[] = $this->process_name;
        $retRef = $this->dbAccess->executeQuery($query, $params);
        return;
    }
    
}

?>
