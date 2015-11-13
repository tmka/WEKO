<?php
/**
 * $Id: Sendusagestatisticsmail.class.php 51356 2015-04-01 09:26:22Z tomohiro_ichikawa $
 * 
 * フィードバックメール送信ビジネスクラス
 * 
 * @author IVIS
 * @sinse 2014/11/11
 */
 require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';
 require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
 
class Repository_Components_Business_Sendusagestatisticsmail extends BusinessBase
{
    // プログレスファイル
    private $workFile = "";
    // 一次プログレスファイル
    private $tmpWorkFile = "";
    // フィードバックメール送信ログ
    private $logFile = "";
    
    // 処理停止時間
    private $sleepSec = 1;
    
    /**
     * constructer
     */
    public function __construct()
    {
        // メンバ変数は文字列連結で定義できないのでコンストラクタで設定する
        $this->workFile = WEBAPP_DIR."/logs/weko/feedback/progress.tsv";
        $this->tmpWorkFile = WEBAPP_DIR."/logs/weko/feedback/tmp_progress.tsv";
        $this->logFile = WEBAPP_DIR."/logs/weko/feedback/send_mail_log.txt";
    }
    
    /**
     * プログレスファイルを読み込む
     * 
     * @param  string $mailAddress
     * @param  int    $orderNum
     * @param  bool   $isAuthor
     * @param  int    $authorId
     * @param  bool   $executeFlg execute or not
     * @return bool
     */
    public function openProgressFile(&$mailAddress="", &$orderNum=0, &$isAuthor=false, &$authorId=0, $executeFlg=true) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $status = "block";
        // Check progress file exists
        if(!file_exists($this->workFile)) {
            // Progress file is not exist
            $status = "start";
        } else {
            // Check file read rights
            if(is_readable($this->workFile) && is_writable($this->workFile)) {
                // Get only one line
                $handle = fopen($this->workFile, "r");
                $line = fgets($handle);
                $line = str_replace("\r\n", "", $line);
                $line = str_replace("\n", "", $line);
                $line = trim($line);
                fclose($handle);
                
                // There is contents in progress file
                if($executeFlg) {
                    chmod($this->workFile, 0100);   // --x --- ---
                    
                    // Interval for request to repository
                    sleep($this->sleepSec);
                }
                
                if(strlen($line) > 0) {
                    // -> Set status to "running" and get params.
                    $status = "running";
                    
                    // Explode string
                    $progressArray = explode("\t", $line, 2);
                    // Add e-person 2013/11/26 R.Matsuura --start--
                    if(isset($progressArray[1]) && $progressArray[1] != null && strlen($progressArray[1]) > 0) {
                        $mailAddress = $progressArray[0];
                        $orderNum = $progressArray[1];
                        $isAuthor = false;
                    } else {
                        $authorId = $progressArray[0];
                        $isAuthor = true;
                    }
                    // Add e-person 2013/11/26 R.Matsuura --end--
                } else {
                    // Progress file is empty
                    // -> Set status to "end".
                    $status = "end";
                }
            } else {
                $status = "block";
            }
        }
        
        return $status;
    }
    
    /**
     * プログレスファイルを作成する
     * 
     * @return bool
     */
    public function createProgressFile() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $handle = null;
        
        try {
            $progressText = "";
            $addressList = array();
            
            // Get target user address list
            $addressList = $this->getAddressList();
            
            // Create progress file
            $prevAddress = "";
            $orderNumber = 0;
            foreach($addressList as $address) {
                if($address["content"] == $prevAddress) {
                    $orderNumber++;
                } else {
                    $orderNumber = 0;
                    $prevAddress = $address["content"];
                }
                $progressText .= $address["content"]."\t".$orderNumber."\n";
            }
            
            // Create auhotr progress file
            $query = "SELECT DISTINCT author_id ".
                     "FROM ".DATABASE_PREFIX. "repository_send_feedbackmail_author_id ".
                     "ORDER BY author_id ASC ;";
            $authors = $this->Db->execute($query);
            if($authors === false) {
                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                throw new AppException($this->Db->ErrorMsg());
            }
            for($cnt = 0; $cnt < count($authors); $cnt++) {
                $progressText .= $authors[$cnt]["author_id"]."\n";
            }
            
            $handle = fopen($this->workFile, "w");
            fwrite($handle, $progressText);
            fclose($handle);
            chmod($this->workFile, 0700);   // rwx --- ---
            
            return true;
        } catch (Exception $ex) {
            // File close
            if($handle != null) {
                fclose($handle);
            }
            return false;
        }
    }
    
    /**
     * プログレスファイルを更新する
     * 
     * @return bool
     */
    public function updateProgressFile() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        if(!file_exists($this->workFile))
        {
            // Force exit
            $DATE = new Date();
            $this->updateSendMailEndDate($DATE->getDate().".000");
            
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
     * フィードバックメール送信の前処理を行う
     * 
     * @return bool
     */
    public function startSendMail() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $ret = false;
        
        // Entry start date
        $DATE = new Date();
        $startDate = $DATE->getDate().".000";
        $ret = $this->updateSendMailStartDate($startDate);
        if($ret === false) {
            return false;
        }
        
        // Delete end date later
        $ret = $this->updateSendMailEndDate();
        if($ret === false) {
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
        $this->infoLog("businessUsagestatistics", __FILE__, __CLASS__, __LINE__);
        $usagestatistics = BusinessFactory::getFactory()->getBusiness("businessUsagestatistics");
        if(!$usagestatistics->aggregateUsagestatistics()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * フィードバックメール送信の後処理を行う
     * 
     * @return bool
     */
    public function endSendMail() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $ret = false;
        
        // Delete send mail work file.
        $this->deleteSendMailWorkFile();
        
        // Entry end date
        $DATE = new Date();
        $endDate = $DATE->getDate().".000";
        $ret = $this->updateSendMailEndDate($endDate);
        
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
     * フィードバックメール送信の最終実行時刻を更新する
     * 
     * @param  string $endDate
     * @return bool
     */
    public function updateSendMailEndDate($endDate="") {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
                 "SET param_value = ? ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = $endDate;
        $params[] = "send_feedback_mail_end_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return true;
    }
    
    /**
     * 処理を強制終了する
     */
    public function killProcess() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // delete workFile
        $this->deleteSendMailWorkFile();
        
        // end time output
        $DATE = new Date();
        $this->updateSendMailEndDate($DATE->getDate().".000");
    }
    
    /**
     * Check setting config
     *
     * @return bool
     */
    public function checkSettingConfig() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $ret = false;
        
        // Get setting_config
        $this->infoLog("mailMain", __FILE__, __CLASS__, __LINE__);
        $mailMain = BusinessFactory::getFactory()->getBusiness("mailMain");
        if(isset($mailMain)) {
            $ret = $mailMain->setting_config;
        }
        
        return $ret;
    }
    
    /**
     * Execute send mail
     * 
     * @param  string $mailAddress
     * @param  int    $orderNum
     * @param  int    $authorId
     * @param  bool   $isAuthor
     * @param  int    $year
     * @param  int    $month
     * @param  string $language
     * @return bool
     */
    public function executeSendMail($mailAddress, $orderNum, $authorId, $isAuthor, $year, $month, $language) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        try {
            // セッション等を扱うためRepositoryActionを仕様
            $this->infoLog("Session", __FILE__, __CLASS__, __LINE__);
            $container = & DIContainerFactory::getContainer();
            $session = $container->getComponent("Session");
            if(strlen($language) > 0) {
                $session->setParameter("_lang", $language);
            }
            
            $repositoryAction = new RepositoryAction();
            $repositoryAction->Session = $session;
            $repositoryAction->Db = $this->Db;
            $repositoryAction->dbAccess = $this->Db;
            $repositoryAction->TransStartDate = $this->accessDate;
            
            $userName = "";
            $items = array();
            $lang = $repositoryAction->Session->getParameter("_lang");
            if($isAuthor == false) {
                $userId = $this->getUserIdByMailAddress($mailAddress, $orderNum);
                if(strlen($userId) == 0) {
                    throw new AppException("Not Login");
                } else {
                    // Get item registered by user
                    $items = $this->getItemRegisteredByUser($userId);
                    if(count($items) == 0) {
                        // No regist items
                        return true;
                    }
                }
                // get UserName
                $userName = $this->getUserName($userId);
            } else {
                $items = $this->getItemRegisteredByAuthor($authorId);
                if(count($items) == 0) {
                    // No regist items
                    return true;
                }
                // get mail address
                $mailAddress = $this->getAuthorMailAddressByAuthorId($authorId);
                // check mail address
                if(!preg_match('/^[-+.\w]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i', $mailAddress)) {
                    throw new AppException("Invalid MailAddress");
                }
                // get Author Name
                $userName = $this->getAuthorName($authorId, $lang);
            }
            // Get lang resource
            $repositoryAction->setLangResource();
            $smartyAssign = $repositoryAction->Session->getParameter("smartyAssign");
            
            // ---------------------------------------------
            // create mail body
            // ---------------------------------------------
            $this->infoLog("mailMain", __FILE__, __CLASS__, __LINE__);
            $mailMain = BusinessFactory::getFactory()->getBusiness("mailMain");
            $this->infoLog("businessUsagestatistics", __FILE__, __CLASS__, __LINE__);
            $usagestatistics = BusinessFactory::getFactory()->getBusiness("businessUsagestatistics");
            
            // set subject
            $yearMonth = sprintf("%d-%02d", $year, $month);
            $subj = $smartyAssign->getLang("repository_feedback_mail_subject");
            $mailMain->setSubject("[{X-SITE_NAME}]".$yearMonth." ".$subj);
            
            // set Mail body
            $body = sprintf($smartyAssign->getLang("repository_feedback_mail_body_dear"), $userName)."\n\n";
            $body .= sprintf($smartyAssign->getLang("repository_feedback_mail_body_announcement"), $userName)."\n\n";
            $body .= $smartyAssign->getLang("repository_feedback_mail_body_unnecessary")."\n\n";
            $body .= $smartyAssign->getLang("repository_feedback_mail_body_month").$yearMonth."\n\n";
            
            foreach($items as $item) {
                // Get usage statistics
                $views = $usagestatistics->getUsagesViews($item["item_id"], $item["item_no"], $year, $month);
                $downloads = $usagestatistics->getUsagesDownloads($item["item_id"], $item["item_no"], $year, $month);
                
                $title = "";
                if($lang == "japanese") {
                    $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    if(strlen($title) == 0) {
                        $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    }
                } else {
                    $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                    if(strlen($title) == 0) {
                        $title = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                    }
                }
                
                $body .= "----------------------------------------\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_title").$repositoryAction->forXmlChange($title)."\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_url").$item[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI]."\n";
                $body .= $smartyAssign->getLang("repository_feedback_mail_body_views")."(".sprintf("%6s", $views["total"]).")\n";
                if(count($downloads) > 0) {
                    $body .= $smartyAssign->getLang("repository_feedback_mail_body_downloads")."\n";
                }
                foreach($downloads as $download) {
                    $fileName = $download["display_name"];
                    if(strlen($fileName) == 0) {
                        $fileName = $download["file_name"];
                    }
                    $body .= "\t".$repositoryAction->forXmlChange($fileName)." (".sprintf("%6s", $download["usagestatistics"]["total"]).")\n";
                }
                $body .= "\n";
            }
            
            $mailMain->setBody($body);
            
            // ---------------------------------------------
            // set send to user
            // ---------------------------------------------
            $users = array();
            array_push($users, array("email" => $mailAddress, "handle" => $this->handle));
            $mailMain->setToUsers($users);
            
            // ---------------------------------------------
            // send mail
            // ---------------------------------------------
            $return = $mailMain->send();
            if($return === false) {
                throw new Exception("Failed Sending");
            }
            
            $this->writeSendMailLog("OK", $mailAddress, $orderNum, $isAuthor);
            return true;
            
        } catch (Exception $ex) {
            $this->writeSendMailLog("NG", $mailAddress, $orderNum, $isAuthor);
            return false;
        }
    }
    
    /**
     * Delete progress file
     */
    private function deleteSendMailWorkFile() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // delete work file
        if(file_exists($this->workFile)) {
            chmod($this->workFile, 0700);   // rwx --- ---
            unlink($this->workFile);
        }
    }
    
    /**
     * Delete log file
     */
    private function deleteSendMailLogFile() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        // delete log file
        if(file_exists($this->logFile)) {
            chmod($this->logFile, 0700);    // rwx --- ---
            unlink($this->logFile);
        }
    }
    
    /**
     * Update send mail start date
     * 
     * @param string $startDate
     * @return bool
     */
    private function updateSendMailStartDate($startDate="") {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
                 "SET param_value = ? ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = $startDate;
        $params[] = "send_feedback_mail_start_date";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return true;
    }
    
    /**
     * Get send mail start date
     * 
     * @param string &$startDate
     * @return bool
     */
    private function getSendMailStartDate(&$startDate) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $startDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "send_feedback_mail_start_date";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        if(strlen($result[0]["param_value"]) > 0) {
            $startDate = $result[0]["param_value"];
        }
        
        return true;
    }
    
    /**
     * Get send mail end date
     * 
     * @param string &$endDate
     * @return bool
     */
    private function getSendMailEndDate(&$endDate) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $startDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "send_feedback_mail_end_date";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        if(strlen($result[0]["param_value"]) > 0) {
            $endDate = $result[0]["param_value"];
        }
        
        return true;
    }
    
    /**
     * Send mail log
     * 
     * @param string $status
     * @param string $mailAddress
     * @param int    $orderNum
     * @param bool   $isAuthor
     * @return bool
     */
    private function writeSendMailLog($status, $mailAddress, $orderNum, $isAuthor) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $DATE = new Date();
        $nowDate = $DATE->getDate().".000";    
    
        chmod($this->logFile, 0700);   // rwx --- ---
        $handle = fopen($this->logFile, "a");
        
        if($isAuthor == false) {
            fwrite($handle, $nowDate."\t".$status."\t".$mailAddress."\t".$orderNum."\n");
        } else {
            fwrite($handle, $nowDate."\t".$status."\t".$mailAddress."\n");
        }
        fclose($handle);
        chmod($this->logFile, 0500);   // r-x --- ---
            
        return true;
    }
    
    /**
     * Get address list
     * 
     * @return array
     */
    private function getAddressList() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
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
    private function getExcludeAddress() {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_NAME." = ? ;";
        $params = array();
        $params[] = "exclude_address_for_feedback";
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result) == 0) {
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
    private function getTargetAddressList($excludeAddress) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "SELECT DISTINCT U.user_id, UIL.content ".
                 "FROM ".DATABASE_PREFIX."users AS U, ".DATABASE_PREFIX."users_items_link AS UIL ".
                 "WHERE U.user_id = UIL.user_id ".
                 "AND (UIL.item_id = 5 OR (UIL.item_id = 6 AND UIL.email_reception_flag = 1)) ".
                 "AND UIL.content <> '' ";
        if(strlen($excludeAddress) > 0) {
            $tmpExcludeAddressList = explode(",", $excludeAddress);
            $excludeAddressList = array();
            foreach($tmpExcludeAddressList as $address) {
                array_push($excludeAddressList, "'".$address."'");
            }
            $excludeAddressText = implode(",", $excludeAddressList);
            $query .= "AND UIL.content NOT IN (".$excludeAddressText.") ";
        }
        $query .= "ORDER BY UIL.content ASC, U.user_id ASC;";
        $result = $this->Db->execute($query);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result) == 0) {
            return array();
        }
        
        return $result;
    }
    
    /**
     * Get user_id by mail address
     * 
     * @param string $address
     * @param int $orderNum
     * @return string
     */
    private function getUserIdByMailAddress($address, $orderNum) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "SELECT DISTINCT U.user_id, UIL.content ".
                 "FROM ".DATABASE_PREFIX."users AS U, ".DATABASE_PREFIX."users_items_link AS UIL ".
                 "WHERE U.user_id = UIL.user_id ".
                 "AND (UIL.item_id = 5 OR (UIL.item_id = 6 AND UIL.email_reception_flag = 1)) ".
                 "AND UIL.content = ? ".
                 "ORDER BY U.user_id ASC;";
        $params = array();
        $params[] = $address;   // content
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(!isset($result[$orderNum])) {
            return "";
        }
        
        return $result[$orderNum]["user_id"];
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
    private function getItemRegisteredByUser($userId) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
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
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
     * Get user name by user_id
     * 
     * @param string $userId
     * @return string
     */
    private function getUserName($userId) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $userName = "";
        
        // Get handle
        $query = "SELECT handle ".
                 "FROM ".DATABASE_PREFIX."users ".
                 "WHERE user_id = ? ;";
        $params = array();
        $params[] = $userId;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result)==0) {
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
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result) == 0) {
            return $userName;
        }
        if(strlen(trim($result[0]["content"])) > 0) {
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
    private function getItemRegisteredByAuthor($authorId) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
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
        $result = $this->Db->execute($query, $params); 
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result;
    }
    
    /**
     * Get author name
     * 
     * @param string $authorId
     * @return string
     */
    private function getAuthorName($authorId) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
        $query = "SELECT language, family, name ".
                 "FROM ".DATABASE_PREFIX."repository_name_authority ".
                 "WHERE author_id = ? ".
                 "AND is_delete = ?;";
        $params = array();
        $params[] = $authorId;    // author_id
        $params[] = 0;          // is_delete
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result) == 0) {
            return "";
        }
        
        $authorName = "";
        $sameLangExist = false;
        for($cnt = 0; $cnt < count($result); $cnt++) {
            if($result[$cnt]["language"] == $lang) {
                if($lang == "english") {
                    $authorName = $result[$cnt]["name"];
                    if(strlen($result[$cnt]["name"]) > 0 && strlen($result[$cnt]["family"]) > 0) {
                        $authorName .= " ";
                    }
                    $authorName .= $result[$cnt]["family"];
                } else {
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
        if($sameLangExist == false) {
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
    private function getAuthorMailAddressByAuthorId($authorId) {
        $this->debugLog(__FUNCTION__ , __FILE__, __CLASS__, __LINE__);
        
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
        
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        if(count($result) == 0) {
            return "";
        }
        
        return $result[0]["suffix"];
    }
}
?>