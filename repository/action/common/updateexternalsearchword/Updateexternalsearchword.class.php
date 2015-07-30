<?php
// --------------------------------------------------------------------
//
// $Id: Updateexternalsearchword.class.php 36507 2014-05-30 02:18:58Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/BackgroundProcess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryExternalSearchWordManager.class.php';

/**
 * update external search word
 *
 * @package     NetCommons
 * @author      R.Matsuura(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Updateexternalsearchword extends BackgroundProcess
{
    const MAX_RECORDS = 100;
    const PARAM_NAME = "Repository_Action_Common_Updateexternalsearchword";
    
    //----------------------------
    // Request parameters
    //----------------------------
    /**
     * login_id
     *
     * @var string
     */
    public $log_id = null;
    /**
     * login_id
     *
     * @var string
     */
    public $login_id = null;
    /**
     * password
     *
     * @var string
     */
    public $password = null;
    
    /**
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * get log infomartion
     *
     * @param Object log_info
     */
    protected function prepareBackgroundProcess(&$log_info) {
        
        // check login
        $result = null;
        $error_msg = null;
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false){
            print("Incorrect Login!\n");
            $this->failTrans();
            return false;
        }
        if($this->log_id == null || $this->log_id == ""){
            $this->log_id = 0;
        }
        $query = "SELECT log_no, item_id, item_no, referer FROM ". DATABASE_PREFIX. "repository_log ".
                 "WHERE LENGTH(referer) > ? ".
                 "AND operation_id = ? ".
                 "AND log_no > ? ".
                 "LIMIT 0, ? ;";
        $params = array();
        $params[] = 1;
        $params[] = 3;
        $params[] = $this->log_id;
        $params[] = self::MAX_RECORDS;
        $log_info = $this->dbAccess->executeQuery($query, $params);
        
        if(count($log_info) == 0) {
            return false;
        }
        for($ii = 0; $ii < count($log_info); $ii++) {
            if($this->log_id < $log_info[$ii]["log_no"]) {
                $this->log_id = $log_info[$ii]["log_no"];
            }
        }
        $_GET["log_id"] = $this->log_id;
        return true;
    }
    
    /** 
     * 
     * execute background process
     * 
     * @param Object $log_info
     */
    protected function executeBackgroundProcess($log_info) {
        $searchWordManager = new Repository_Components_RepositoryExternalSearchWordManager($this->Session, $this->Db, $this->TransStartDate);
        for($ii = 0; $ii < count($log_info); $ii++) {
            $searchWordManager->insertExternalSearchWordFromURL($log_info[$ii]["item_id"], $log_info[$ii]["item_no"], $log_info[$ii]["referer"]);
        }
        // Print message.
        print("Start Update Stopword.\n");
    }
}
?>