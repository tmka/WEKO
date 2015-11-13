<?php
// --------------------------------------------------------------------
//
// $Id: Workdirectory.class.php 56989 2015-08-24 11:02:11Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/OperateFileSystem.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/CreateWorkDirectory.class.php';

class Repository_Components_business_Workdirectory extends BusinessBase
{
    /**
     * 作成した一時ディレクトリリスト
     */
    private $tmpDirectoryList = array();
    
    /**
     * create temporary directory
     *
     * @return temporary directory path
     * 
     */
    public function create()
    {
        // 一時ディレクトリパス
        $tmpDirPath = "";
        
        // 作成
        $tmpDirPath = Repository_Components_Util_CreateWorkDirectory::create(WEBAPP_DIR. "/uploads/repository/");
        if($tmpDirPath === false){
            $errorMsg = "create temp directory is failed.";
            $this->errorLog($errorMsg, __FILE__, __CLASS__, __LINE__);
            $e = new AppException($errorMsg);
            $e->addError($errorMsg);
            throw $e;
        }
        
        // 作成済みリストに追加する
        $this->debugLog("Create Work Directory : ". $tmpDirPath, __FILE__, __CLASS__, __LINE__);
        $this->tmpDirectoryList[] = $tmpDirPath;
        
        return $tmpDirPath;
    }
    
    /**
     * 最終処理
     * 
     */
    protected function onFinalize() {
        $this->debugLog("[".__FUNCTION__."]"." Start Remove Work Directory", __FILE__, __CLASS__, __LINE__);
        for($ii = 0; $ii < count($this->tmpDirectoryList); $ii++) {
            if(file_exists($this->tmpDirectoryList[$ii])) {
                $this->debugLog("Remove Work Directory : ". $this->tmpDirectoryList[$ii], __FILE__, __CLASS__, __LINE__);
                Repository_Components_Util_OperateFileSystem::removeDirectory($this->tmpDirectoryList[$ii]);
            } else {
                $this->debugLog("Already Removed Work Directory : ". $this->tmpDirectoryList[$ii], __FILE__, __CLASS__, __LINE__);
            }
        }
        // リストクリア
        $this->tmpDirectoryList = null;
    }
}
?>