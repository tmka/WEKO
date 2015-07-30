<?php
// --------------------------------------------------------------------
//
// $Id: Usagestatisticsmail.class.php 30569 2014-01-09 07:37:40Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatistics.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatisticsSendMail.class.php';

/**
 * Usagestatisticsmail
 *
 * @package     NetCommons
 * @author      A.Suzuki(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Usagestatisticsmail extends RepositoryAction
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
     * user_authority_id
     *
     * @var int
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
    
    function execute()
    {
        try {
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                throw $exception;
            }
            
            $this->user_authority_id = "";
            $this->authority_id = "";
            $this->user_id = "";
            
            // check login
            $result = null;
            $error_msg = null;
            $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
            if($return == false){
                print("Incorrect Login!\n");
                $this->failTrans();
                $this->exitAction();
                return false;
            }
            
            // check user authority id
            if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
                print("You do not have permission to update.\n");
                $this->failTrans();
                $this->exitAction();
                return false;
            }
            
            // Set user id to session
            $this->Session->setParameter("_user_id", $this->user_id);
            
            // Set year and month
            $this->setYearAndMonth();
            
            // RepositoryUsagestatisticsSendMail class
            $SendMail = new RepositoryUsagestatisticsSendMail($this->Session, $this->Db, $this->TransStartDate);
            
            $SendMail->openProgressFile();
            if($SendMail->getStatus() == "start")
            {
                // --------------------
                // Start send mail
                // --------------------
                // If activate flag is unavailable, cannot start send mail.
                if(!$this->getSendFeedbackMailActivateFlag())
                {
                    // Print error message.
                    print("Cannot to send feedback mail. Because setting is not enabled.\n");
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                
                // Call send mail start process
                $SendMail->startSendMail();
                
                // Create progress file
                if(!$SendMail->createProgressFile())
                {
                    // Print error message.
                    print("Failed to send feedback mail.\n");
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                
                // Call oneself by async
                if(!$this->callAnotherProcessByAsync())
                {
                    // Print error message.
                    print("Failed to send feedback mail.\n");
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                // Print message.
                print("Start send feedback mail.\n");
            }
            else if($SendMail->getStatus() == "running")
            {
                // --------------------
                // Running send mail
                // --------------------
                // Execute send mail
                $SendMail->executeSendMail($this->year, $this->month);
                
                // Update progress file
                if(!$SendMail->updateProgressFile())
                {
                    // Print error message.
                    print("Failed to send feedback mail.\n");
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                
                // Call oneself by async
                if(!$this->callAnotherProcessByAsync())
                {
                    // Print error message.
                    print("Failed to send feedback mail.\n");
                    $this->failTrans();
                    $this->exitAction();
                    exit();
                }
                // Print message.
                print("Send feedback mail runnung continue.\n");
            }
            else if($SendMail->getStatus() == "end")
            {
                // --------------------
                // End send mail
                // --------------------
                // Call send mail end process
                $SendMail->endSendMail();
                
                // Print message.
                print("Send feedback mail completed.\n");
            }
            else
            {
                // Print message.
                print("Cannot execute send mail, because running other process.\n");
            }
            
            // Finalize
            $this->exitAction();
            exit();
        }
        catch (Exception $exception)
        {
            // rollback
            $this->failTrans();
            $this->exitAction();
            print($exception->getMessage()."\n");
            exit();
        }
    }
    
    /**
     * Call another process by async
     *
     * @return bool
     */
    public function callAnotherProcessByAsync()
    {
        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_usagestatisticsmail".
                       "&year=".$this->year."&month=".$this->month.
                       "&login_id=".$this->login_id."&password=".$this->password;
        
        // Call oneself by async
        $host = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", BASE_URL, $host);
        $hostName = $host[1];
        if($hostName == "localhost"){
            $hostName = gethostbyname($_SERVER['SERVER_NAME']);
        }
        if($_SERVER["SERVER_PORT"] == 443)
        {
            $hostName = "ssl://".$hostName;
        }
        $handle = fsockopen($hostName, $_SERVER["SERVER_PORT"]);
        if (!$handle)
        {
            return false;
        }
        
        stream_set_blocking($handle, false);
        fwrite($handle, "GET ".$nextRequest." HTTP/1.0\r\n\r\n");
        fclose ($handle);
        
        return true;
    }
    
    /**
     * Set year and month
     */
    private function setYearAndMonth()
    {
        if( strlen($this->year) == 0 || intval($this->year) < 1 ||
            strlen($this->month) == 0 || intval($this->month) < 1 || intval($this->month) > 12)
        {
            // Get previous month
            $prevYearMonth = $this->getPreviousMonth();
            $prevYearMonthArray = explode("-", $prevYearMonth, 2);
            $prevYear = intval($prevYearMonthArray[0]);
            $prevMonth = intval($prevYearMonthArray[1]);
            $this->year = $prevYear;
            $this->month = $prevMonth;
        }
    }
    
    /**
     * Get previous month
     *
     * @return string
     */
    private function getPreviousMonth()
    {
        // Get previous month (format: YYYY-MM)
        $query = "SELECT DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m') AS prevMonth ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1)
        {
            return "";
        }
        
        return $result[0]["prevMonth"];
    }
    
    /**
     * Get send feedback mail activate flag
     *
     * @return bool
     */
    private function getSendFeedbackMailActivateFlag()
    {
        $rtn = false;
        
        // Get send feedback mail activate flag in parameter
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_NAME." = 'send_feedback_mail_activate_flg';";
        $result = $this->Db->execute($query);
        if($result !== false && count($result)==1 && $result[0][RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE]=="1")
        {
            $rtn = true;
        }
        
        return $rtn;
    }
}
?>