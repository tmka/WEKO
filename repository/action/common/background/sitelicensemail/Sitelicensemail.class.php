<?php
// --------------------------------------------------------------------
//
// $Id: Sitelicensemail.class.php 56700 2015-08-19 12:30:34Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/components/mail/Main.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/BackgroundProcess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/LogAnalyzor.class.php';


/**
 * Delete deleted file in background process
 *
 * @package     NetCommons
 * @author      R.Matsuura(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Background_Sitelicensemail extends BackgroundProcess
{

    const PARAM_NAME = "Repository_Action_Common_Background_Sitelicensemail";
    
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
     * mail_main class
     *
     * @var Object
     */
    public $mailMain = null;
    /**
     * smarty assign
     *
     * @var Object
     */
    public $smartyAssign = null;
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
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * get renewal item of a search table 
     *
     * @param fileList deleted file
     */
    protected function prepareBackgroundProcess(&$sl_user_info) {
        $this->debugLog(__FUNCTION__, __FILE__, __CLASS__, __LINE__);
        
        // check login
        $return = $this->checkExecuteAuthority();
        if($return == false){
            print("Incorrect Login!\n");
            $this->failTrans();
            $this->exitAction();
            return false;
        }
        
        $this->setLangResource();
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // サイトライセンスユーザー情報1件取得
        $sl_user_info = $sendSitelicense->getSendSitelicenseUser();
        if(count($sl_user_info) == 0) {
            return false;
        }
        
        return true;
    }
    
    /** 
     * 
     * execute background process
     * 
     * @param Array $sl_user_info
     */
    protected function executeBackgroundProcess($sl_user_info) {
        $this->debugLog(__FUNCTION__, __FILE__, __CLASS__, __LINE__);
        
        if(strlen($sl_user_info[0]["mail_address"]) > 0) {
            $send_zip_name = "";
            
            $this->createFeedbackReport($sl_user_info[0]["organization_id"]);
            $this->compressFile($send_zip_name);
            $this->sendSitelicenseReport($send_zip_name, $sl_user_info[0]["mail_address"], $sl_user_info[0]["organization_name"]);
        }
        // 送信が終わったサイトライセンスユーザー情報を削除する
        $this->deleteSitelicenseUserInfo($sl_user_info);
    }
    
    /** 
     * create feedback report
     * 
     * @param int $sitelicense_id
     */
    private function createFeedbackReport($sitelicense_id) {
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // 一時ディレクトリが存在しない場合作成する
        $sendSitelicense = $sendSitelicense->createSitelicenseMailTmpDir();
        
        // レポート作成に必要なパラメータを作成する
        // サイトライセンスログの範囲を指定する
        $start_date = $this->createStartYearMonthStringFormatYmdhis($this->year, $this->month);
        $finish_date = $this->createFinishYearMonthStringFormatYmdhis($this->year, $this->month);
        
        // サイト名を取得する
        $language = $this->Session->getParameter("_lang");
        $query = "SELECT conf_value FROM ". DATABASE_PREFIX. "config_language ".
                 "WHERE conf_name = ? ".
                 "AND lang_dirname = ? ;";
        $params = array();
        $params[] = "sitename";
        $params[] = $language;
        $result = $this->dbAccess->executeQuery($query, $params);
        $organization_name = $result[0]["conf_value"];
        
        // 検索レポートを作成する
        $this->createSearchReport($start_date, $finish_date, $organization_name, $sitelicense_id);       /* 中の処理をビジネスに移す予定 */
        // ダウンロードレポートを作成する
        $this->createDownloadReport($start_date, $finish_date, $organization_name, $sitelicense_id);
        // 利用統計レポートを作成する
        $this->createUsagestaticsReport($start_date, $finish_date, $organization_name, $sitelicense_id);
    }
    
    /** 
     * create search log report
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param string $organization_name
     * @param int    $sitelicense_id
     */
    private function createSearchReport($start_date, $finish_date, $organization_name, $sitelicense_id) {
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // 検索回数デフォルト値
        $search_count = 0;
        // 指定した機関のサイトライセンスユーザーによる検索ログの件数を取得する
        $result = $sendSitelicense->getLogCountBySitelicenseId($start_date, $finish_date, $sitelicense_id, RepositoryConst::LOG_OPERATION_SEARCH);
        if(isset($result) && count($result) > 0) {
            $search_count = $result[0]["CNT"];
        }
        // レポート文面の作成
        $log_file = "SearchReport_".$this->createYearMonthStringFormatYm($this->year, $this->month).".tsv";
        
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".$this->createYearMonthStringFormatYmAddHyphen($this->year, $this->month)."\r\n".
                         "\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_interface_name")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_search_keyword")."\r\n";
        $report_body =   $this->smartyAssign->getLang("repository_sitelicense_mail_body_weko_database")."\t".
                         $organization_name."\t".$search_count;
        
        $report = $report_header. $report_body;
        
        // レポートファイル作成
        $sendSitelicense->createReport($log_file, $report);
    }
    
    /** 
     * create download log report
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param string $organization_name
     * @param int    $sitelicense_id
     */
    private function createDownloadReport($start_date, $finish_date, $organization_name, $sitelicense_id) {
        // ダウンロード合計回数デフォルト値
        $sum_download = 0;
        // 指定した機関のサイトライセンスユーザーによる利用統計情報を取得する
        $result = $this->getUsagePerJournalPerMonthly($start_date, $finish_date, $sitelicense_id);
        
        // レポート文面の作成
        for($ii = 0; $ii < count($result["index_info"]);  $ii++) {
            $sum_download += intval($result["index_info"][$ii]["download"]);
        }
        $log_file = "DownloadReport_".$this->createYearMonthStringFormatYm($this->year, $this->month).".tsv";
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".$this->createYearMonthStringFormatYmAddHyphen($this->year, $this->month)."\r\n".
                         "\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_setspec")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_interface_name")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_online_issn")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_file_download")."\r\n";
        
        $report_body = "";
        $report_body .= "Total for all journals"."\t"."\t".$organization_name."\t"."\t".$sum_download."\r\n";
        
        $jtitle_lang = "";
        if($this->Session->getParameter("_lang") == "english") {
            $jtitle_lang = "jtitle_en";
        } else {
            $jtitle_lang = "jtitle";
        }
        for($ii = 0; $ii < count($result["index_info"]); $ii++) {
            $report_body .= $result["index_info"][$ii][$jtitle_lang]."\t".
                            $result["index_info"][$ii]["set_spec"]."\t".
                            $organization_name."\t".
                            $result["index_info"][$ii]["issn"]."\t".
                            $result["index_info"][$ii]["download"]."\r\n";
        }
        $report = $report_header. $report_body;
        
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // レポートファイル作成
        $sendSitelicense->createReport($log_file, $report);
    }
    
    /** 
     * create usage statistics report
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param string $organization_name
     * @param int    $sitelicense_id
     */
    private function createUsagestaticsReport($start_date, $finish_date, $organization_name, $sitelicense_id) {
        // 指定した機関のサイトライセンスユーザーによる利用統計情報を取得する
        $result = $this->getUsagePerJournalPerMonthly($start_date, $finish_date, $sitelicense_id);
        
        // レポート文面の作成
        $log_file = "UsagestatisticsReport_".$this->createYearMonthStringFormatYm($this->year, $this->month).".tsv";
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".$this->createYearMonthStringFormatYmAddHyphen($this->year, $this->month)."\r\n".
                         "\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_setspec")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_interface_name")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_online_issn")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_view")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_file_download")."\r\n";
        
        $report_body = "";
        
        $jtitle_lang = "";
        if($this->Session->getParameter("_lang") == "english") {
            $jtitle_lang = "jtitle_en";
        } else {
            $jtitle_lang = "jtitle";
        }
        for($ii = 0; $ii < count($result["index_info"]); $ii++) {
            $report_body .= $result["index_info"][$ii][$jtitle_lang]."\t".
                            $result["index_info"][$ii]["set_spec"]."\t".
                            $organization_name."\t".
                            $result["index_info"][$ii]["issn"]."\t".
                            $result["index_info"][$ii]["view"]."\t".
                            $result["index_info"][$ii]["download"]."\r\n";
        }
        $report = $report_header. $report_body;
        
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // レポートファイル作成
        $sendSitelicense->createReport($log_file, $report);
    }
    
    /** 
     * get usage journal per monthly
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param int    $sitelicense_id
     */
    private function getUsagePerJournalPerMonthly($start_date, $finish_date, $sitelicense_id) {
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // 統計情報配列にISSN情報設定
        $online_issn = $sendSitelicense->getOnlineIssn();
        if(count($online_issn) == 0) {
            return array();
        }
        $statistics = array();
        $statistics["index_info"] = $online_issn;
        
        // クエリパラメータ用のISSN文字列を作成する
        $issn_param = "";
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            if($ii > 0) {
                $issn_param .= ",";
            }
            $issn_param .= "'". $statistics["index_info"][$ii]["issn"]. "'";
        }
        
        // ISSN毎のダウンロードログ取得
        $download_result = $sendSitelicense->getLogBySitelicenseId($issn_param, $start_date, $finish_date, $sitelicense_id, RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE);
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            // initialize value of downlooad
            $statistics["index_info"][$ii]["download"] = 0;
            for($jj = 0; $jj < count($download_result); $jj++) {
                if($download_result[$jj]["online_issn"] == $statistics["index_info"][$ii]["issn"]) {
                    $statistics["index_info"][$ii]["download"] = $download_result[$jj]["CNT"];
                }
            }
        }
        
        // ISSN毎の閲覧ログ取得
        $view_result = $sendSitelicense->getLogBySitelicenseId($issn_param, $start_date, $finish_date, $sitelicense_id, RepositoryConst::LOG_OPERATION_DETAIL_VIEW);
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            // initialize value of view
            $statistics["index_info"][$ii]["view"] = 0;
            for($jj = 0; $jj < count($view_result); $jj++) {
                if($view_result[$jj]["online_issn"] == $statistics["index_info"][$ii]["issn"]) {
                    $statistics["index_info"][$ii]["view"] = $view_result[$jj]["CNT"];
                }
            }
        }
        
        return $statistics;
    }
    
    /** 
     * compress file
     * 
     * @param string &$zip_file
     */
    private function compressFile(&$zip_file) {
        // set zip file name
        $zip_file = "SiteLicenseUserReport_". $this->createYearMonthStringFormatYm($this->year, $this->month). ".zip";
        
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // compress zip file
        $sendSitelicense->compressToZip($zip_file);
    }
    
    /** 
     * send site license report mail
     * 
     * @param string $file_path
     * @param string $mail_address
     * @param string $organization_name
     */
    private function sendSitelicenseReport($file_name, $mail_address, $organization_name) {
        $file_path = WEBAPP_DIR. "/uploads/repository/";
        
        // サイト名を取得する
        $language = $this->Session->getParameter("_lang");
        $query = "SELECT conf_value FROM ". DATABASE_PREFIX. "config_language ".
                 "WHERE conf_name = ? ".
                 "AND lang_dirname = ? ;";
        $params = array();
        $params[] = "sitename";
        $params[] = $language;
        $site_name = $this->dbAccess->executeQuery($query, $params);
        // 管理者メールアドアレスを取得する
        $query = "SELECT conf_value FROM ". DATABASE_PREFIX. "config ".
                 "WHERE conf_name = ? ;";
        $params = array();
        $params[] = "from";
        $admin_mail = $this->dbAccess->executeQuery($query, $params);
        
        // タイトル
        $sub = "[". $site_name[0]["conf_value"]. "] ".
               $this->createYearMonthStringFormatYmAddHyphen($this->year, $this->month).
               " ".$this->smartyAssign->getLang("repository_sitelicense_mail_subject");
        $this->mailMain->setSubject($sub);
        // 本文
        $body = sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_dear"), $organization_name)."\n\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_thank"), $site_name[0]["conf_value"])."\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_announcement"), $this->createYearMonthStringFormatYmAddHyphen($this->year, $this->month))."\n\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_unnecessary"), $admin_mail[0]["conf_value"])."\n\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_rule_1")."\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_rule_2")."\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_format")."\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_all_file")."\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_search_report_1")."\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_search_report_2")."\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_download_report_1")."\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_download_report_2")."\n\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_usagestatistics_1")."\n".
                $this->smartyAssign->getLang("repository_sitelicense_mail_body_explain_usagestatistics_2")."\n\n\n".
                "------------------------------------------------"."\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_footer_contact"), $site_name[0]["conf_value"])."\n".
                $admin_mail[0]["conf_value"];
        $this->mailMain->setBody($body);
        // 送り先
        $users = array();
        $users[0]["email"] = $mail_address;
        $users[0]["handle"] = $organization_name;
        $this->mailMain->setToUsers($users);
        
        // 添付ファイル
        $attachment = array();
        $attachment[0] = $file_path. $file_name;        // binary string([5] = true) or path([5] = false)
        $attachment[1] = basename($file_name, ".zip");  // file name
        $attachment[2] = $file_name;        // name
        $attachment[3] = "base64";          // encoding
        $attachment[4] = "application/zip"; // Content-Type
        $attachment[5] = false;             // binary is true
        $attachment[6] = "attachment";      // Content-Disposition
        $attachment[7] = "";                // Content-ID(if disposition is "inline", must fill)
        
        $this->mailMain->_mailer->attachment = array($attachment);
        if(count($users) > 0){
            // 送信者がいる場合は送信
            $return = $this->mailMain->send();
            if($return){
                echo 'send mail:true';
            }else {
                echo 'send mail:false';
            }
        unlink($file_path. $file_name);
        }

    }
    
    /** 
     * delete info from site license user table
     * 
     * @param Array $sl_user_info
     */
    private function deleteSitelicenseUserInfo($sl_user_info) {
        // サイトライセンスメール用のビジネスクラス取得
        $this->infoLog("businessSendsitelicensemail", __FILE__, __CLASS__, __LINE__);
        $sendSitelicense = BusinessFactory::getFactory()->getBusiness("businessSendsitelicensemail");
        
        // 1件指定して削除
        $sendSitelicense->deleteSendSitelicenseuser($sl_user_info[0]["organization_id"]);
    }
    
    /**
     * check execute authority
     *
     * @return bool
     */
    private function checkExecuteAuthority() {
        // Init user authorities
        $this->user_authority_id = "";
        $this->authority_id = "";
        $this->user_id = "";
        
        // Check login
        $result = null;
        $error_msg = null;
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false) {
            $this->failTrans();
            $this->exitAction();
            print("Incorrect Login!\n");
            return false;
        }
        
        // Check user authority id
        if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room) {
            $this->failTrans();
            $this->exitAction();
            print("You do not have permission to update.\n");
            return false;
        }
        
        return true;
    }
    
    private function createStartYearMonthStringFormatYmdhis($requestYear, $requestMonth){
        if( isset($requestYear) && 
            isset($requestMonth) && 
            intval($requestYear) > 0 && 
            intval($requestMonth) > 0 && 
            12 >= intval($requestMonth)){
            
            return date("Y-m-d", mktime(0, 0, 0, intval($requestMonth), 1, intval($requestYear))). " 00:00:00.000";
        } else {
            return date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))). " 00:00:00.000";
        }
    }
    
    private function createFinishYearMonthStringFormatYmdhis($requestYear, $requestMonth){
        if( isset($requestYear) && 
            isset($requestMonth) && 
            intval($requestYear) > 0 && 
            intval($requestMonth) > 0 && 
            12 >= intval($requestMonth)){
            
            return date("Y-m-d", mktime(0, 0, 0, intval($requestMonth) + 1, 0, intval($requestYear))). " 23:59:59:999";
        } else {
            return date("Y-m-d", mktime(0, 0, 0, date("m"), 0, date("Y"))). " 23:59:59:999";
        }
    }
    
    private function createYearMonthStringFormatYm($requestYear, $requestMonth){
        if( isset($requestYear) && 
            isset($requestMonth) && 
            intval($requestYear) > 0 && 
            intval($requestMonth) > 0 && 
            12 >= intval($requestMonth)){
            
            return date("Ym", mktime(0, 0, 0, intval($requestMonth), 1, intval($requestYear)));
        } else {
            return date("Ym", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
        }
    }
    
    private function createYearMonthStringFormatYmAddHyphen($requestYear, $requestMonth){
    if( isset($requestYear) && 
            isset($requestMonth) && 
            intval($requestYear) > 0 && 
            intval($requestMonth) > 0 && 
            12 >= intval($requestMonth)){
            
            return date("Y-m", mktime(0, 0, 0, intval($requestMonth), 1, intval($requestYear)));
        } else {
            return date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
        }
    }
}
?>