<?php
// --------------------------------------------------------------------
//
// $Id: Harvesting.class.php 44462 2014-11-28 02:42:41Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHarvesting.class.php';

/**
 * Harvesting
 *
 * @package     NetCommons
 * @author      A.Suzuki(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Harvesting extends RepositoryAction
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
    
    /**
     * allItemAcquisition
     * 
     * @var bool
     */
    private $allItemAcquisition = null;
    
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
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
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
                return false;
            }
            
            // check user authority id
            if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
                print("You do not have permission to update.\n");
                $this->failTrans();
                return false;
            }
            // Add selective Harvesting R.Matsuura 2013/10/01 --start--
            // get all item acquisition flag
            $this->allItemAcquisition = $this->Session->getParameter("harvesting_all_item_acquisition_flag");
            // Add selective Harvesting R.Matsuura 2013/10/01 --end--
            // Set user id to session
            $this->Session->setParameter("_user_id", $this->user_id);
            
            // RepositoryHarvesting class
            $Harvesting = new RepositoryHarvesting($this->Session, $this->Db);
            $Harvesting->TransStartDate = $this->TransStartDate;
            
            $Harvesting->openProgressFile();
            $status = $Harvesting->getStatus();
            if($status == RepositoryHarvesting::STATUS_START)
            {
                // --------------------
                // Start harvesting
                // --------------------
                // Call harvesting start process
                $Harvesting->startHarvesting();
                
                // Create progress file
                if(!$Harvesting->createProgressFile($this->allItemAcquisition))
                {
                    // Print error message.
                    print("Failed to harvesting.\n".__CLASS__." ".__LINE__);
                    $this->failTrans();
                    exit();
                }
                
                // Finalize
                $this->exitAction();
                
                // Call oneself by async
                if(!$this->callAnotherProcessByAsync())
                {
                    // Print error message.
                    print("Failed to harvesting.\n".__CLASS__." ".__LINE__);
                    $this->failTrans();
                    exit();
                }
                // Print message.
                print("Start harvesting.\n");
            }
            else if($status == RepositoryHarvesting::STATUS_RUNNING)
            {
                // --------------------
                // Running harvesting
                // --------------------
                // Execute harvesting
                if(!$Harvesting->executeHarvesting())
                {
                    // If an error occurs, nextUrl to empty.
                    $Harvesting->setNextUrl("");
                }
                
                // Update progress file
                if(!$Harvesting->updateProgressFile())
                {
                    // Print error message.
                    print("Failed to harvesting.\n".__CLASS__." ".__LINE__);
                    $this->failTrans();
                    exit();
                }
                
                // Finalize
                $this->exitAction();
                
                // Call oneself by async
                if(!$this->callAnotherProcessByAsync())
                {
                    // Print error message.
                    print("Failed to harvesting.\n".__CLASS__." ".__LINE__);
                    $this->failTrans();
                    exit();
                }
                // Print message.
                print("Harvesting runnung continue.\n");
            }
            else if($status == RepositoryHarvesting::STATUS_END)
            {
                // --------------------
                // End harvesting
                // --------------------
                // Call harvesting end process
                $Harvesting->endHarvesting();
                
                // Print message.
                print("Harvesting completed.\n");
                
                // Add selective Harvesting R.Matsuura 2013/10/01 --start--
                $this->Session->removeParameter("harvesting_all_item_acquisition_flag");
                // Add selective Harvesting R.Matsuura 2013/10/01 --end--
                
                // Finalize
                $this->exitAction();
            }
            else
            {
                // Print message.
                print("Cannot execute harvesting, because running other process.\n");
                // Finalize
                $this->exitAction();
            }
            
            exit();
        }
        catch (Exception $exception)
        {
            // rollback
            $this->failTrans();
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
        $nextRequest = BASE_URL."/?action=repository_action_common_harvesting".
                       "&login_id=".$this->login_id."&password=".$this->password;
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
}
?>