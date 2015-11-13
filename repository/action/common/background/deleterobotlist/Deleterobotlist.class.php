<?php
// --------------------------------------------------------------------
//
// $Id: Deleterobotlist.class.php 51588 2015-04-06 06:08:02Z shota_suzuki $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Background_Deleterobotlist extends BackgroundProcess
{
    const PARAM_NAME = "Repository_Action_Common_Background_Deleterobotlist";
    
    /**
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * search undeleted log
     */
    protected function prepareBackgroundProcess(&$target)
    {
        $undeleted = $this->getUndeletedList();
        
        // 削除するデータがないならロックを解放して終了
        if (count($undeleted) == 0) {
            $this->infoLog("businessRobotlistbase", __FILE__, __CLASS__, __LINE__);
            $businessRobotlistbase = BusinessFactory::getFactory()->getBusiness("businessRobotlistbase");
            
            $businessRobotlistbase->unlockRobotListTable();
            return false;
        }
        
        // ロボットリストバックグラウンドロックがされていないなら終了
        $lockList = $this->checkRobotlistLock();
        
        if ($lockList == true) {
            return false;
        }
        
        return true;
    }
    
    /** 
     * execute background process
     */
    protected function executeBackgroundProcess(&$target) 
    {
        $undeleted = $this->getUndeletedList();
        
        // logmanager class
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logmanager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        
        // ログ削除
        $logmanager->removeLogByRobotlistWord($undeleted[0]["del_column"], $undeleted[0]["word"]);
        
        // 更新済みにする
        $this->updateStatus($undeleted[0]["robotlist_id"], $undeleted[0]["list_id"]);
    }
    
    /**
    * ロボットリストデータのステータスを更新する
    * 
    * @return array
    */
    private function updateStatus($robotlistId, $listId)
    {
        $query = "UPDATE ". DATABASE_PREFIX. "repository_robotlist_data ". 
                 "SET status = ? " . 
                 "WHERE robotlist_id = ? AND list_id = ? ; ";
        
        $params = array();
        $params[] = 1;
        $params[] = $robotlistId;
        $params[] = $listId;
        
        $result = $this->Db->execute($query, $params);
        
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
    }
    
    /**
    * 更新されていないロボットリストデータを取得する
    * 
    * @return array
    */
    private function getUndeletedList()
    {
        $query = "SELECT * " . 
                 "FROM " . DATABASE_PREFIX . "repository_robotlist_master AS MASTER, " . 
                           DATABASE_PREFIX . "repository_robotlist_data AS DATAS " . 
                 "WHERE MASTER.is_robotlist_use = ? AND " .
                 "MASTER.robotlist_id = DATAS.robotlist_id AND " .
                 "DATAS.status != ? AND " .
                 "DATAS.is_delete = ? ; ";
        
        $params = array();
        $params[] = 1;
        $params[] = 1;
        $params[] = 0;
        
        $result = $this->Db->execute($query, $params);
        
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
    * ロボットリストデータバックグラウンドのステータスを確認する
    * 
    * @return bool true  = lock
    *              false = unlock
    */
    private function checkRobotlistLock()
    {
        $query = "SELECT * ". 
                 " FROM ". DATABASE_PREFIX. "repository_lock ". 
                 " WHERE process_name = ? ; ";
        
        $params = array();
        $params[] = "Repository_Action_Common_Robotlist";
        
        $result = $this->Db->execute($query, $params);
        
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if ($result[0]["status"] == 0){
            return true;
        }
        else {
            return false;
        }
    }
}
?>
