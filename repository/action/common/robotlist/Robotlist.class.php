<?php
// --------------------------------------------------------------------
//
// $Id: Robotlist.class.php 51750 2015-04-08 04:02:25Z shota_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Robotlist extends WekoAction
{
    const PARAM_NAME = "Repository_Action_Common_Robotlist";
    
    /**
     * [[機能説明]]
     *
     * @access public
     */
    function executeApp()
    {
        $this->infoLog("businessRobotlistbase", __FILE__, __CLASS__, __LINE__);
        $businessRobotlistbase = BusinessFactory::getFactory()->getBusiness("businessRobotlistbase");
        
        // lock robotlist
        $businessRobotlistbase->lockRobotListTable();
        
        // get robotlist_master
        $result = $this->getRobotListMaster();
        
        for ($ii = 0; $ii < count($result); $ii++) 
        {
            $isRobotlistUse = $result[$ii]["is_robotlist_use"];
            $robotlistId = $result[$ii]["robotlist_id"];
            
            // get robotlist data
            $robotlist = $businessRobotlistbase->getRobotList($robotlistId);
            
            // update robotlist data
            $robotlist = $businessRobotlistbase->updateRobotList($robotlistId, $robotlist);
        }
        
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $businessLogmanager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        
        // delete exclusion logs
        $businessLogmanager->removeExclusionAddress();
        
        // Improve Log 2015/06/22 K.Sugimoto --start--
        $this->infoLog("Commit SQL.", __FILE__, __CLASS__, __LINE__);
        if($this->Db->CompleteTrans() === false)
        {
            $this->infoLog("Failed commit trance.", __FILE__, __CLASS__, __LINE__);
            throw new AppException("Failed commit trance.");
        }
        // Improve Log 2015/06/22 K.Sugimoto --end--
        
        // access to deleterobotlist
        $this->accessTodeleterobotlist();
        
        return "success";
    }
    
    /**
    * ロボットリスト非同期削除クラスにアクセスする
    */
    private function accessTodeleterobotlist()
    {
        $url = BASE_URL . "/?action=repository_action_common_background_deleterobotlist";
        
        $option = array("timeout" => 10, 
                        "allowRedirects" => "true", 
                        "maxRedirects" => 3);
        
        $request = new HTTP_Request($url, $option);
        $request->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        
        $response = $request->sendRequest(); 
    }
    
    /**
    * ロボットリストマスタを取得する
    */
    private function getRobotListMaster()
    {
        $query = "SELECT * " . 
                 "FROM " . DATABASE_PREFIX . "repository_robotlist_master " . 
                 "WHERE is_robotlist_use = ? AND is_delete = ? ; ";
        
        $params = array();
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
    * ロボットリストデータを更新する
    */
    private function updateRobotListData($robotlistId, $status)
    {
        $query = "UPDATE ". DATABASE_PREFIX. "repository_robotlist_data ". 
                 "SET status = ? " . 
                 "WHERE robotlist_id = ? ; ";
        
        $params = array();
        $params[] = $status;
        $params[] = $robotlistId;
        
        $update = $this->Db->execute($query, $params);
        
        if($update === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
    }
}
?>
