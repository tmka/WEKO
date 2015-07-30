<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryUsagestatisticsSendMail.class.php 30569 2014-01-09 07:37:40Z rei_matsuura $
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
require_once WEBAPP_DIR. '/components/mail/Main.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';

/**
 * Repository module usagestatistics send mail class
 *
 * @package repository
 * @access  public
 */
class RepositoryUsagestatisticsSendMail extends RepositoryAction
{
    /**
     * target mail address
     *
     * @var string
     */
    private $mailAddress = "";
    
    /**
     * target mail address order number
     *
     * @var int
     */
    private $orderNum = 0;
    
    /**
     * progress file path
     *
     * @var string
     */
    private $workFile = "";
    
    /**
     * tmp_progress file path
     *
     * @var string
     */
    private $tmpWorkFile  = "";
    
    /**
     * log file path
     *
     * @var string
     */
    private $logFile = "";
    
    /**
     * Send mail status
     * 
     * @var string status:start/running/end/block
     */
    private $status = "block";
    
    /**
     * Seconds to sleep
     *
     * @var int
     */
    private $sleepSec = 1;
    
    /**
     * Mail_Main class object
     *
     * @var Mail_Main
     */
    private $mailMain = null;
    
    /**
     * RepositoryUsagestatistics class object
     *
     * @var RepositoryUsagestatistics
     */
    private $repositoryUsagestatistics = null;
    
    /**
     * target author ID
     * 
     * @var int
     */
    private $authorId = null;
    
    /**
     * author flag(true:target is author, false:target is user)
     * 
     * @var bool
     */
    private $isAuthor = false;
    
    /**
     * Constructor
     * 
     * @param Session $Session
     * @param Db $Db
     * @param string $transStartDate
     * @return RepositoryUsagestatisticsSendMail
     */
    public function __construct($Session, $Db, $transStartDate){
        $this->Session = $Session;
        $this->Db = $Db;
        $this->TransStartDate = $transStartDate;
        // check class format
        if(is_a($Db, 'DbObjectAdodb'))
        {
            $this->dbAccess = new RepositoryDbAccess($Db);
        }
        else if(is_a($Db, 'RepositoryDbAccess'))
        {
            $this->dbAccess = $Db;
        }
        $this->workFile = WEBAPP_DIR."/logs/weko/feedback/progress.tsv";
        $this->tmpWorkFile = WEBAPP_DIR."/logs/weko/feedback/tmp_progress.tsv";
        $this->logFile = WEBAPP_DIR."/logs/weko/feedback/send_mail_log.txt";
        
        $this->repositoryUsagestatistics = new RepositoryUsagestatistics($Session, $Db, $transStartDate);
        $this->mailMain = new Mail_Main();
    }
    
    /**
     * Get now date
     * 
     * @return string
     */
    private function getNowDate()
    {
        $DATE = new Date();
        return $DATE->getDate().".000";
    }
    
    /**
     * Get member: status
     * 
     * @return string member:status
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Open progress file
     * 
     * @param bool $executeFlg execute or not
     */
    public function openProgressFile($executeFlg=true)
    {
        // Check progress file exists
        if(!file_exists($this->workFile))
        {
            // Progress file is not exist
            // -> Set status to "start".
            $this->status = "start";
        }
        else
        {
            // Check file read rights
            if(is_readable($this->workFile) && is_writable($this->workFile))
            {
                // Get only one line
                $handle = fopen($this->workFile, "r");
                $line = fgets($handle);
                $line = str_replace("\r\n", "", $line);
                $line = str_replace("\n", "", $line);
                $line = trim($line);
                fclose($handle);
                
                // There is contents in progress file
                if($executeFlg)
                {
                    chmod($this->workFile, 0100);   // --x --- ---
                    
                    // Interval for request to repository
                    sleep($this->sleepSec);
                }
                
                if(strlen($line) > 0)
                {
                    // -> Set status to "running" and get params.
                    $this->status = "running";
                    
                    // Explode string
                    $progressArray = explode("\t", $line, 2);
                    // Add e-person 2013/11/26 R.Matsuura --start--
                    if(isset($progressArray[1]) && $progressArray[1] != null && strlen($progressArray[1]) > 0)
                    {
                        $this->mailAddress = $progressArray[0];
                        $this->orderNum = $progressArray[1];
                        $this->isAuthor = false;
                    }
                    else
                    {
                        $this->authorId = $progressArray[0];
                        $this->isAuthor = true;
                    }
                    // Add e-person 2013/11/26 R.Matsuura --end--
                }
                else
                {
                    // Progress file is empty
                    // -> Set status to "end".
                    $this->status = "end";
                }
            }
            else
            {
                $this->status = "block";
            }
        }
    }
    
    /**
     * Create progress file
     * 
     * @return bool
     */
    public function createProgressFile()
    {
        $handle = null;
        
        try
        {
            $progressText = "";
            $addressList = array();
            
            // Get target user address list
            $addressList = $this->getAddressList();
            
            // Create progress file
            $prevAddress = "";
            $orderNumber = 0;
            foreach($addressList as $address)
            {
                if($address["content"] == $prevAddress)
                {
                    $orderNumber++;
                }
                else
                {
                    $orderNumber = 0;
                    $prevAddress = $address["content"];
                }
                $progressText .= $address["content"]."\t".$orderNumber."\n";
            }
            
            // Add e-person 2013/11/26 R.Matsuura --start--
            // Create auhotr progress file
            $query = "SELECT DISTINCT author_id ".
                     "FROM ".DATABASE_PREFIX. "repository_send_feedbackmail_author_id ".
                     "ORDER BY author_id ASC ;";
            $authors = $this->dbAccess->executeQuery($query);
            if($authors === false)
            {
                return false;
            }
            for($cnt = 0; $cnt < count($authors); $cnt++)
            {
                $progressText .= $authors[$cnt]["author_id"]."\n";
            }
            // Add e-person 2013/11/26 R.Matsuura --end--
            
            $handle = fopen($this->workFile, "w");
            fwrite($handle, $progressText);
            fclose($handle);
            chmod($this->workFile, 0700);   // rwx --- ---
            
            return true;
        }
        catch (Exception $ex)
        {
            // File close
            if($handle != null)
            {
                fclose($handle);
            }
            return false;
        }
    }
    
    /**
     * Update progress file
     * 
     * @return bool
     */
    public function updateProgressFile()
    {
        if(!file_exists($this->workFile))
        {
            // Force exit
            $this->updateSendMailEndDate($this->getNowDate());
            
            return false;
        }
        
        chmod($this->workFile, 0700);   // rwx --- ---
        $w_fp = fopen($this->tmpWorkFile, "w");
        $r_fp = fopen($this->workFile, "r");
        $cnt = 0;
        while(!feof($r_fp))
        {
            $r_line = fgets($r_fp);
            $r_line = str_replace("\r\n", "", $r_line);
            $r_line = str_replace("\n", "", $r_line);
            if($cnt > 0)
            {
                if(strlen($r_line) > 0){
                    // For second line below
                    fwrite($w_fp, $r_line."\n");
                }
            }
            $cnt++;
        }
        fclose($r_fp);
        fclose($w_fp);
        unlink($this->workFile);
        rename($this->tmpWorkFile, $this->workFile);
        chmod($this->workFile, 0700);   // rwx --- ---
        
        return true;
    }
    
    /**
     * Delete progress file
     * 
     */
    private function deleteSendMailWorkFile()
    {
        // delete work file
        if(file_exists($this->workFile))
        {
            chmod($this->workFile, 0700);   // rwx --- ---
            unlink($this->workFile);
        }
    }
    
    /**
     * Delete log file
     * 
     */
    private function deleteSendMailLogFile()
    {
        // delete log file
        if(file_exists($this->logFile))
        {
            chmod($this->logFile, 0700);    // rwx --- ---
            unlink($this->logFile);
        }
    }
    
    /**
     * Send mail start process
     * 
     * @return bool
     */
    public function startSendMail()
    {
        $ret = false;
        
        // Entry start date
        $startDate = $this->getNowDate();
        $ret = $this->updateSendMailStartDate($startDate);
        if($ret === false)
        {
            return false;
        }
        
        // Delete end date later
        $ret = $this->updateSendMailEndDate();
        if($ret === false)
        {
            return false;
        }
        
        // Create log file
        $this->deleteSendMailLogFile();
        $logText = "Start send Feedback mail : ".$startDate."\n\n";
        $handle = fopen($this->logFile, "w");
        fwrite($handle, $logText);
        fclose($handle);
        chmod($this->logFile, 0500);   // r-x --- ---
        
        // Aggregate usage statistics
        if(!$this->repositoryUsagestatistics->aggregateUsagestatistics())
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send mail end process
     * 
     * @return bool
     */
    public function endSendMail()
    {
        $ret = false;
        
        // Delete send mail work file.
        $this->deleteSendMailWorkFile();
        
        // Entry end date
        $endDate = $this->getNowDate();
        $ret = $this->updateSendMailEndDate($this->getNowDate());
        
        // Finalize log file
        $logText = "\nEnd send Feedback mail : ".$endDate."\n";
        chmod($this->logFile, 0700);    // rwx --- ---
        $handle = fopen($this->logFile, "a");
        fwrite($handle, $logText);
        fclose($handle);
        chmod($this->logFile, 0500);    // r-x --- ---
        
        return $ret;
    }
    
    /**
     * Update send mail start date
     * 
     * @param string $startDate
     * @return bool
     */
    private function updateSendMailStartDate($startDate="")
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
                 "SET param_value = ? ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = $startDate;
        $params[] = "send_feedback_mail_start_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        
        return true;
    }
    
    /**
     * Get send mail start date
     * 
     * @param string &$startDate
     * @return bool
     */
    private function getSendMailStartDate(&$startDate)
    {
        $startDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "send_feedback_mail_start_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(strlen($result[0]["param_value"]) > 0)
        {
            $startDate = $result[0]["param_value"];
        }
        
        return true;
    }
    
    /**
     * Update send mail end date
     * 
     * @param string $endDate
     * @return bool
     */
    public function updateSendMailEndDate($endDate="")
    {
        $query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
                 "SET param_value = ? ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = $endDate;
        $params[] = "send_feedback_mail_end_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        
        return true;
    }
    
    /**
     * Get send mail end date
     * 
     * @param string &$endDate
     * @return bool
     */
    private function getSendMailEndDate(&$endDate)
    {
        $startDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "send_feedback_mail_end_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(strlen($result[0]["param_value"]) > 0)
        {
            $endDate = $result[0]["param_value"];
        }
        
        return true;
    }
    
    /**
     * Send mail log
     * 
     * @param string $status
     */
    private function writeSendMailLog($status)
    {
        chmod($this->logFile, 0700);   // rwx --- ---
        $handle = fopen($this->logFile, "a");
        if($this->isAuthor == false)
        {
            fwrite($handle, $this->getNowDate()."\t".$status."\t".$this->mailAddress."\t".$this->orderNum."\n");
        }
        else
        {
            fwrite($handle, $this->getNowDate()."\t".$status."\t".$this->mailAddress."\n");
        }
        fclose($handle);
        chmod($this->logFile, 0500);   // r-x --- ---
            
        return true;
    }
    
    /**
     * kill send mail process
     * -> delete workFile
     */
    public function killProcess()
    {
        // delete workFile
        $this->deleteSendMailWorkFile();
        
        // end time output
        $this->updateSendMailEndDate($this->getNowDate());
    }
    
    /**
     * Check setting config
     *
     * @return bool
     */
    public function checkSettingConfig()
    {
        $ret = false;
        
        // Get setting_config
        if(isset($this->mailMain))
        {
            $ret = $this->mailMain->setting_config;
        }
        
        return $ret;
    }
    
    /**
     * Get address list
     * 
     * @return array
     */
    private function getAddressList()
    {
        $addressList = array();
        
        // Get exclude address list
        $excludeAddress = $this->getExcludeAddress();
        
        // Get target address list
        $addressList = $this->getTargetAddressList($excludeAddress);
        
        return $addressList;
    }
    
    /**
     * Get exclude address
     * 
     * @return string
     */
    private function getExcludeAddress()
    {
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_NAME." = ? ;";
        $params = array();
        $params[] = "exclude_address_for_feedback";
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)==0)
        {
            return "";
        }
        
        return $result[0][RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE];
    }
    
    /**
     * Get target address list
     * 
     * @param string $excludeAddress
     * @return array
     */
    private function getTargetAddressList($excludeAddress)
    {
        $query = "SELECT DISTINCT U.user_id, UIL.content ".
                 "FROM ".DATABASE_PREFIX."users AS U, ".DATABASE_PREFIX."users_items_link AS UIL ".
                 "WHERE U.user_id = UIL.user_id ".
                 "AND (UIL.item_id = 5 OR (UIL.item_id = 6 AND UIL.email_reception_flag = 1)) ".
                 "AND UIL.content <> '' ";
        if(strlen($excludeAddress) > 0)
        {
            $tmpExcludeAddressList = explode(",", $excludeAddress);
            $excludeAddressList = array();
            foreach($tmpExcludeAddressList as $address)
            {
                array_push($excludeAddressList, "'".$address."'");
            }
            $excludeAddressText = implode(",", $excludeAddressList);
            $query .= "AND UIL.content NOT IN (".$excludeAddressText.") ";
        }
        $query .= "ORDER BY UIL.content ASC, U.user_id ASC;";
        $result = $this->Db->execute($query);
        if($result === false || count($result)==0)
        {
            return array();
        }
        
        return $result;
    }
    
    /**
     * Execute send mail
     * 
     * @param int $year
     * @param int $month
     * @return bool
     */
    public function executeSendMail($year, $month)
    {
        try
        {
            $userName = "";
            $items = array();
            $lang = $this->Session->getParameter("_lang");
            if($this->isAuthor == false)
            {
                $userId = $this->getUserIdByMailAddress($this->mailAddress, $this->orderNum);
                if(strlen($userId) == 0)
                {
                    throw new Exception();
                }
                else
                {
                    // Get item registered by user
                    $items = $this->getItemRegisteredByUser($userId);
                    if(count($items) == 0)
                    {
                        // No regist items
                        return true;
                    }
                }
                // get UserName
                $userName = $this->getUserName($userId);
            }
            else
            {
                $items = $this->getItemRegisteredByAuthor($this->authorId);
                if(count($items) == 0)
                {
                    // No regist items
                    return true;
                }
                // get mail address
                $this->mailAddress = $this->getAuthorMailAddressByAuthorId($this->authorId);
                // check mail address
                if(!preg_match('/^[-+.\w]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i', $this->mailAddress))
                {
                    throw new Exception();
                }
                // get Author Name
                $userName = $this->getAuthorName($this->authorId, $lang);
            }
            
            // Get lang resource
            $this->setLangResource();
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            
            // ---------------------------------------------
            // create mail body
            // ---------------------------------------------
            // set subject
            $yearMonth = sprintf("%d-%02d", $year, $month);
            $subj = $smartyAssign->getLang("repository_feedback_mail_subject");
            $this->mailMain->setSubject("[{X-SITE_NAME}]".$yearMonth." ".$subj);
            
            // set Mail body
            $body = sprintf($smartyAssign->getLang("repository_feedback_mail_body_dear"), $userName)."\n\n";
            $body .= sprintf($smartyAssign->getLang("repository_feedback_mail_body_announcement"), $userName)."\n\n";
            $body .= $smartyAssign->getLang("repository_feedback_mail_body_unnecessary")."\n\n";
            $body .= $smartyAssign->getLang("repository_feedback_mail_body_month").$yearMonth."\n\n";
            
            foreach($items as $item)
            {
                // Get usage statistics
                $views = $this->repositoryUsagestatistics->getUsagesViews(
                                $item["item_id"], $item["item_no"], $year, $month);
                $downloads = $this->repositoryUsagestatistics->getUsagesDownloads(
                                $item["item_id"], $item["item_no"], $year, $month);
                
                $title = "";
                if($lang == "japanese")
                {
                    $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    if(strlen($title) == 0)
                    {
                        $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    }
                }
                else
                {
                    $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    if(strlen($title) == 0)
                    {
                        $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    }
                }
                
                $body .= "----------------------------------------\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_title").$this->forXmlChange($title)."\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_url").$item[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI]."\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_views")."(".sprintf("%6s", $views["total"]).")\n";
                if(count($downloads) > 0)
                {
                    $body .= $smartyAssign->getLang("repository_feedback_mail_body_downloads")."\n";
                }
                foreach($downloads as $download)
                {
                    $fileName = $download["display_name"];
                    if(strlen($fileName) == 0)
                    {
                        $fileName = $download["file_name"];
                    }
                    $body .= "\t".$this->forXmlChange($fileName)." (".sprintf("%6s", $download["usagestatistics"]["total"]).")\n";
                }
                $body .= "\n";
            }
            
            $this->mailMain->setBody($body);
            
            // ---------------------------------------------
            // set send to user
            // ---------------------------------------------
            $users = array();
            array_push($users, array("email" => $this->mailAddress, "handle" => $this->handle));
            $this->mailMain->setToUsers($users);
            
            // ---------------------------------------------
            // send mail
            // ---------------------------------------------
            $return = $this->mailMain->send();
            if($return === false)
            {
                throw new Exception();
            }
            
            $this->writeSendMailLog("OK");
            return true;
            
        }
        catch (Exception $ex)
        {
            $this->writeSendMailLog("NG");
            return false;
        }
    }
    
    /**
     * Get user_id by mail address
     * 
     * @param string $address
     * @param int $orderNum
     * @return string
     */
    private function getUserIdByMailAddress($address, $orderNum)
    {
        $query = "SELECT DISTINCT U.user_id, UIL.content ".
                 "FROM ".DATABASE_PREFIX."users AS U, ".DATABASE_PREFIX."users_items_link AS UIL ".
                 "WHERE U.user_id = UIL.user_id ".
                 "AND (UIL.item_id = 5 OR (UIL.item_id = 6 AND UIL.email_reception_flag = 1)) ".
                 "AND UIL.content = ? ".
                 "ORDER BY U.user_id ASC;";
        $params = array();
        $params[] = $address;   // content
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) == 0)
        {
            return "";
        }
        
        if(!isset($result[$this->orderNum]))
        {
            return "";
        }
        
        return $result[$this->orderNum]["user_id"];
    }
    
    /**
     * Get item registered by user
     * 
     * @param string $userId
     * @return array $ret[n]["item_id"]
     *                      ["item_no"]
     *                      ["title"]
     *                      ["title_english"]
     *                      ["uri"]
     */
    private function getItemRegisteredByUser($userId)
    {
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID.", ".
                           RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO.", ".
                           RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE.", ".
                           RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH.", ".
                           RepositoryConst::DBCOL_REPOSITORY_ITEM_URI." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." ".
                 "WHERE ".RepositoryConst::DBCOL_COMMON_INS_USER_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_URI." <> '' ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." ASC;";
        $params = array();
        $params[] = $userId;    // ins_user_id
        $params[] = 0;          // is_delete
        $result = $this->Db->execute($query, $params);  
        if($result === false)
        {
            return array();
        }
        
        return $result;
    }
    
    /**
     * Get user name by user_id
     * 
     * @param string $userId
     * @return string
     */
    private function getUserName($userId)
    {
        $userName = "";
        
        // Get handle
        $query = "SELECT handle ".
                 "FROM ".DATABASE_PREFIX."users ".
                 "WHERE user_id = ? ;";
        $params = array();
        $params[] = $userId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)==0)
        {
            return $userName;
        }
        $userName = $result[0]["handle"];
        
        // Get name (Only 'public_flag = 1')
        $query = "SELECT content ".
                 "FROM ".DATABASE_PREFIX."users_items_link ".
                 "WHERE user_id = ? ".
                 "AND item_id = 4 ".
                 "AND public_flag = 1";
        $params = array();
        $params[] = $userId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)==0)
        {
            return $userName;
        }
        if(strlen(trim($result[0]["content"])) > 0)
        {
            $userName = $result[0]["content"];
        }
        
        return $userName;
    }
    
    /**
     * Get item registered by author
     * 
     * @param string $authorId
     * @return array $ret[n]["item_id"]
     *                      ["item_no"]
     *                      ["title"]
     *                      ["title_english"]
     *                      ["uri"]
     */
    private function getItemRegisteredByAuthor($authorId)
    {
        $query = "SELECT ITEM." .RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID.", ".
                        "ITEM." .RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO.", ".
                        "ITEM." .RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE.", ".
                        "ITEM." .RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH.", ".
                        "ITEM." .RepositoryConst::DBCOL_REPOSITORY_ITEM_URI." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." AS ITEM, ".
                 DATABASE_PREFIX."repository_send_feedbackmail_author_id AS AUTHOR ".
                 "WHERE ITEM.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = AUTHOR.item_id ".
                 "AND ITEM.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = AUTHOR.item_no ".
                 "AND AUTHOR.author_id = ? ".
                 "AND ITEM.".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ? ".
                 "AND ITEM.".RepositoryConst::DBCOL_REPOSITORY_ITEM_URI." <> '' ".
                 "AND ITEM.".RepositoryConst::DBCOL_REPOSITORY_ITEM_URI." IS NOT NULL ".
                 "ORDER BY ITEM.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." ASC;";
        $params = array();
        $params[] = $authorId;    // author_id
        $params[] = 0;          // is_delete
        $result = $this->dbAccess->executeQuery($query, $params); 
        
        return $result;
    }
    
    /**
     * Get author name
     * 
     * @param string $authorId
     * @return string
     */
    private function getAuthorName($authorId, $lang)
    {
        $query = "SELECT language, family, name ".
                 "FROM ".DATABASE_PREFIX."repository_name_authority ".
                 "WHERE author_id = ? ".
                 "AND is_delete = ?;";
        $params = array();
        $params[] = $authorId;    // author_id
        $params[] = 0;          // is_delete
        $result = $this->dbAccess->executeQuery($query, $params);  
        if(count($result) < 1)
        {
            return "";
        }
        $authorName = "";
        $sameLangExist = false;
        for($cnt = 0; $cnt < count($result); $cnt++)
        {
            if($result[$cnt]["language"] == $lang)
            {
                if($lang == "english")
                {
                    $authorName = $result[$cnt]["name"];
                    if(strlen($result[$cnt]["name"]) > 0 && strlen($result[$cnt]["family"]) > 0)
                    {
                        $authorName .= " ";
                    }
                    $authorName .= $result[$cnt]["family"];
                }
                else
                {
                    $authorName = $result[$cnt]["family"];
                    if(strlen($result[$cnt]["name"]) > 0 && strlen($result[$cnt]["family"]) > 0)
                    {
                        $authorName .= " ";
                    }
                    $authorName .= $result[$cnt]["name"];
                }
                $sameLangExist = true;
                break;
            }
        }
        if($sameLangExist == false)
        {
            $authorName = $result[0]["family"];
            if(strlen($result[0]["name"]) > 0 && strlen($result[0]["family"]) > 0)
            {
                $authorName .= " ";
            }
            $authorName .= $result[0]["name"];
        }
        return $authorName;
    }
    
    /**
     * Get mailaddress by author id
     * 
     * @param string $authorId
     * @return string
     */
    private function getAuthorMailAddressByAuthorId($authorId)
    {
        $query = "SELECT suffix ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_suffix ".
                 "WHERE author_id = ? ".
                 "AND prefix_id = ? ".
                 "AND is_delete = ? ";
        $params = array();
        $params[] = $authorId;    // author_id
        $params[] = 0;          // prefix_id
        $params[] = 0;          // is_delete
        
        // Get exclude address list
        $excludeAddress = $this->getExcludeAddress();
        
        if(strlen($excludeAddress) > 0)
        {
            $tmpExcludeAddressList = explode(",", $excludeAddress);
            $excludeAddressList = array();
            foreach($tmpExcludeAddressList as $address)
            {
                array_push($excludeAddressList, "'".$address."'");
            }
            $excludeAddressText = implode(",", $excludeAddressList);
            $query .= "AND suffix NOT IN (".$excludeAddressText.") ";
        }
        $query .= " ; ";
        
        $result = $this->dbAccess->executeQuery($query, $params);  
        if(count($result) < 1)
        {
            return "";
        }
        
        return $result[0]["suffix"];
    }
}

?>
