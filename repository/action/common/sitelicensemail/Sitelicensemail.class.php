<?php
// --------------------------------------------------------------------
//
// $Id: Sitelicensemail.class.php 57108 2015-08-26 01:03:29Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/business/Logbase.class.php';

/**
 * Sitelicensemail
 *
 * @package     NetCommons
 * @author      T.Ichikawa(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Sitelicensemail extends WekoAction
{
    //----------------------------
    // Request parameters
    //----------------------------
    /**
     * login_id
     *
     * @var string
     */
    public $login_id = null;
    /**
     * password to login
     *
     * @var string
     */
    public $password = null;
    /**
     * authority_id
     *
     * @var string
     */    
    public $user_authority_id = "";
    /**
     * authority_id
     *
     * @var int
     */
    public $authority_id = "";
    /**
     * user_id
     *
     * @var string
     */
    public $user_id = "";
    /**
     * year
     *
     * @var int
     */
    public $year = null;
    
    /**
     * month
     *
     * @var int
     */
    public $month = null;
    
    /**
     * サイトライセンスメール送信処理開始
     * 
     */
    public function executeApp() {
        // ログインチェック
        if(!$this->checkExecuteAuthority()) {
            return "error";
        }
        
        $removingLogFlag = $this->isExecuteRemovingLog();
        if($removingLogFlag)
        {
            $exception = new AppException("repository_log_excluding", Repository_Components_Business_Logbase::APP_EXCEPTION_KEY_REMOVING_LOG);
            $exception->addError("repository_log_excluding");
            throw $exception;
        }
        
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // サイトライセンスメールの送信フラグのチェック
        $send_flag = $sendSitelicense->checkSendSitelicense();
        
        if($send_flag == true) {
            // サイトライセンスメール送信対象者リストの作成
            $sendSitelicense->insertSendSitelicenseMailList();
            
            // サイトライセンス送信のバックグラウンド処理のリクエストを送信する
            // Call oneself by async
            if(!$this->callAnotherProcessByAsync())
            {
                // Print error message.
                print("Failed to send site license mail.\n");
                $this->exitFlag = true;
                return "error";
            } 
            
            // Print message.
            print("Start send site license mail.\n");
            
            return "success";
        }
        
        return "error";
    }
    
    /**
     * Call another process by async
     *
     * @return bool
     */
    public function callAnotherProcessByAsync()
    {
        // Request parameter for next URL
        $lang = $this->Session->getParameter("_lang");
        $nextRequest = BASE_URL."/?action=repository_action_common_background_sitelicensemail".
                       "&login_id=".$this->login_id."&password=".$this->password. "&lang=". $lang;
        if( isset($this->year) && 
            isset($this->month) && 
            intval($this->year) > 0 && 
            intval($this->month) > 0 && 
            12 >= intval($this->month)){
            
            $nextRequest .= "&year=". $this->year. "&month=". $this->month;
        }
        $url = parse_url($nextRequest);
        $nextRequest = str_replace($url["scheme"]."://".$url["host"], "",  $nextRequest);
        
        // Call oneself by async
        $host = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", BASE_URL, $host);
        $hostName = $host[1];
        if($hostName == "localhost"){
            $hostName = gethostbyname($_SERVER['SERVER_NAME']);
        }
        $hostSock = $hostName;
        if($_SERVER["SERVER_PORT"] == 443)
        {
            $hostSock = "ssl://".$hostName;
        }
        
        $handle = fsockopen($hostSock, $_SERVER["SERVER_PORT"]);
        if (!$handle)
        {
            return false;
        }
        
        stream_set_blocking($handle, false);
        fwrite($handle, "GET ".$nextRequest." HTTP/1.1\r\nHost: ". $hostName."\r\n\r\n");
        fclose ($handle);
        
        return true;
    }
    
    /**
     * check execute authority
     *
     * @return bool
     */
    private function checkExecuteAuthority()
    {
        // check login
        $result = null;
        $error_msg = null;
        
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->Db;
        $repositoryAction->TransStartDate = $this->accessDate;
        $repositoryAction->setConfigAuthority();
        $repositoryAction->dbAccess = $this->Db;
        
        $return = $repositoryAction->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false){
            print("Incorrect Login!\n");
            return false;
        }
        
        // check user authority id
        if($this->user_authority_id < $repositoryAction->repository_admin_base || $this->authority_id < $repositoryAction->repository_admin_room){
            print("You do not have permission to update.\n");
            return false;
        }
        
        return true;
    }
    
    /**
     * ロボットリストログ削除中かどうか判定する
     *
     * @return bool
     */
    private function isExecuteRemovingLog() {
        // check removing log process
        $query = "SELECT status ". 
                 " FROM ". DATABASE_PREFIX. "repository_lock ". 
                 " WHERE process_name = ? ;";
        $params = array();
        $params[] = 'Repository_Action_Common_Robotlist';
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        // when execute removing log, throw exception
        for($cnt = 0; $cnt < count($result); $cnt++)
        {
            if(intval($result[$cnt]['status']) > 0){
                return true;
            }
        }
        
        return false;
    }
}

?>