<?php
// --------------------------------------------------------------------
//
// $Id: Sitelicensemail.class.php 43734 2014-11-07 03:59:44Z tatsuya_koyasu $
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
            print("You do not have permission to send sitelicense mail.\n");
            $this->failTrans();
            $this->exitAction();
            return false;
        }
        
        $this->setLangResource();
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // 機関データ取得
        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_send_mail_sitelicense ".
                 "ORDER BY no ASC ".
                 "LIMIT 0,1 ;";
        $result = $this->dbAccess->executeQuery($query);
        if(count($result) == 0) {
            return false;
        } else {
            $sl_user_info = $result;
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
        if($sl_user_info[0]["mail_address"] != "") {
            $send_zip_name = "";
            
            $this->createFeedbackReport(WEBAPP_DIR.'/uploads/repository/tmp/', $sl_user_info[0]["start_ip_address"], $sl_user_info[0]["finish_ip_address"]);
            $this->compressFile(WEBAPP_DIR.'/uploads/repository/tmp/', $send_zip_name);
            $this->sendSitelicenseReport(WEBAPP_DIR.'/uploads/repository/', $send_zip_name, $sl_user_info[0]["mail_address"], $sl_user_info[0]["organization_name"]);
        }
        $this->deleteSitelicenseUserInfo($sl_user_info);
    }
    
    /** 
     * create feedback report
     * 
     * @param string $tmp_dir
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function createFeedbackReport($tmp_dir, $start_ip, $finish_ip) {
        // 一時ディレクトリが存在しない場合作成する
	    if(!file_exists($tmp_dir)) {
            mkdir( $tmp_dir, 0777 );
        }
        
        // レポート作成に必要なパラメータを作成する
        // サイトライセンスログの範囲を指定する
        $start_date = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))). " 00:00:00.000";
        $finish_date = date("Y-m-d", mktime(0, 0, 0, date("m"), 0, date("Y"))). " 23:59:59:999";
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
        $this->createSearchReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name);
        $this->createDownloadReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name);
        $this->createUsagestaticsReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name);
    }
    
    /** 
     * create search log report
     * 
     * @param string $tmp_dir
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function createSearchReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name) {
        $search_count = 0;
        $result = $this->getSearchKeywordPerMonthly($start_date, $finish_date, $start_ip, $finish_ip);
        if(strlen($result[0]["COUNT(record_date)"]) != 0) {
            $search_count = $result[0]["COUNT(record_date)"];
        }
        $log_file = $tmp_dir. "SearchReport_".date("Ym", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))).".tsv";
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y")))."\r\n".
                         "\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_interface_name")."\t".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_search_keyword")."\r\n";
        $report_body =   $this->smartyAssign->getLang("repository_sitelicense_mail_body_weko_database")."\t".
                         $organization_name."\t".$search_count;
        
        $report = $report_header. $report_body;
        
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        $log_report = fopen($log_file, "w");
        fwrite($log_report, $BOM.$report);
        fclose($log_report);
    }
    
    /** 
     * create download log report
     * 
     * @param string $tmp_dir
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function createDownloadReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name) {
        $result = $this->getUsagePerJournalPerMonthly($start_date, $finish_date, $start_ip, $finish_ip);
        
        $sum_download = 0;
        for($ii = 0; $ii < count($result["index_info"]);  $ii++) {
            $sum_download += intval($result["index_info"][$ii]["download"]);
        }
        $log_file = $tmp_dir. "DownloadReport_".date("Ym", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))).".tsv";
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y")))."\r\n".
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
        
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        $log_report = fopen($log_file, "w");
        fwrite($log_report, $BOM.$report);
        fclose($log_report);
        
    }
    
    /** 
     * create usage statistics report
     * 
     * @param string $tmp_dir
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function createUsagestaticsReport($tmp_dir, $start_date, $finish_date, $start_ip, $finish_ip, $organization_name) {
        $result = $this->getUsagePerJournalPerMonthly($start_date, $finish_date, $start_ip, $finish_ip);
        
        $log_file = $tmp_dir. "UsagestatisticsReport_".date("Ym", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))).".tsv";
        $report_header = $this->smartyAssign->getLang("repository_sitelicense_mail_body_title")."\t".$organization_name."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_create_date")."\t".date("Y-m-d")."\r\n".
                         $this->smartyAssign->getLang("repository_sitelicense_mail_body_month")."\t".date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y")))."\r\n".
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
        
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        $log_report = fopen($log_file, "w");
        fwrite($log_report, $BOM.$report);
        fclose($log_report);        
    }
    
    /** 
     * get search keyword per monthly
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function getSearchKeywordPerMonthly($start_date, $finish_date, $start_ip, $finish_ip) {
        $query = "SELECT *,COUNT(record_date),". Repository_Components_Loganalyzor::dateformatMonthlyQuery(""). 
                 " FROM ". Repository_Components_Loganalyzor::execlusiveDoubleAccessSubQuery(RepositoryConst::LOG_OPERATION_SEARCH, "", $start_date, $finish_date, RepositoryConst::LOG_OPERATION_SEARCH). 
                 " WHERE record_date >= ? ".
                 Repository_Components_Loganalyzor::execlusiveIpAddressQuery(""). 
                 Repository_Components_Loganalyzor::execlusiveRobotsQuery(""). 
                 " AND operation_id = ? ". 
                 $this->getTargetIpAddressRangeQuery($start_ip, $finish_ip, ""). 
                 Repository_Components_Loganalyzor::perMonthlyQuery(). " ;";
        $params = array();
        $params[] = $start_date;
        $params[] = RepositoryConst::LOG_OPERATION_SEARCH;
        
        $result = $this->dbAccess->executeQuery($query, $params);
        
        return $result;
    }
    
    /** 
     * get usage journal per monthly
     * 
     * @param string $start_date
     * @param string $finish_date
     * @param string $start_ip
     * @param string $finish_ip
     */
    private function getUsagePerJournalPerMonthly($start_date, $finish_date, $start_ip, $finish_ip) {
        $statistics = array();
        $query = "SELECT issn, jtitle, jtitle_en, set_spec ".
                 "FROM ". DATABASE_PREFIX. "repository_issn ".
                 "WHERE is_delete = ? ;";
        $params = array();
        $params[] = 0;
        $statistics["index_info"] = $this->dbAccess->executeQuery($query, $params);

        $issn = array("");
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            $issn[] = $statistics["index_info"][$ii]["issn"];
        }
        // set download values group by issn
        $download_result = $this->getItemsDownloadPerMonthly($issn, $start_date, $finish_date, $start_ip, $finish_ip);
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            // initialize value of downlooad
            $statistics["index_info"][$ii]["download"] = 0;
            for($jj = 0; $jj < count($download_result); $jj++) {
                if($download_result[$jj]["online_issn"] == $statistics["index_info"][$ii]["issn"]) {
                    $statistics["index_info"][$ii]["download"] = $download_result[$jj]["COUNT(DISTINCT LOG.record_date)"];
                }
            }
        }
        
        // set view values group by issn
        $view_result = $this->getItemsViewPerMonthly($issn, $start_date, $finish_date, $start_ip, $finish_ip);
        for($ii = 0; $ii < count($statistics["index_info"]); $ii++) {
            // initialize value of view
            $statistics["index_info"][$ii]["view"] = 0;
            for($jj = 0; $jj < count($view_result); $jj++) {
                if($view_result[$jj]["online_issn"] == $statistics["index_info"][$ii]["issn"]) {
                    $statistics["index_info"][$ii]["view"] = $view_result[$jj]["COUNT(DISTINCT LOG.record_date)"];
                }
            }
        }
        
        return $statistics;
    }
    
    /** 
     * get item download records per month
     * 
     * @param string $issn
     * @param string $start_date
     * @param string $finish_date
     */
    private function getItemsDownloadPerMonthly($issn, $start_date, $finish_date, $start_ip, $finish_ip) {
        $online_issn = "";
        for($ii = 0; $ii < count($issn); $ii++) {
            if($ii != 0) {
                $online_issn .= ",";
            }
            $online_issn .= "'". $issn[$ii]. "'";
        }
        $query = "SELECT COUNT(DISTINCT LOG.record_date), ". 
                 " IDX.online_issn, ".
                 Repository_Components_Loganalyzor::dateformatMonthlyQuery("LOG").
                 " FROM (". Repository_Components_Loganalyzor::execlusiveDoubleAccessSubQuery(RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE, "", $start_date, $finish_date, RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE). ") AS LOG, ".
                        DATABASE_PREFIX. "repository_index AS IDX, ".
                        DATABASE_PREFIX. "repository_position_index AS POS, ".
                        DATABASE_PREFIX. "repository_item AS ITEM ".
                 " WHERE LOG.record_date >= ? ".
                 " AND LOG.record_date <= ? ".
                 " ". Repository_Components_Loganalyzor::execlusiveIpAddressQuery("LOG").
                 " ". Repository_Components_Loganalyzor::execlusiveRobotsQuery("LOG").
                 " AND LOG.operation_id = ? ".
                 " AND IDX.biblio_flag = ? ".
                 " AND IDX.online_issn IN ( ". $online_issn. " ) ".
                 " AND IDX.index_id = POS.index_id ".
                 " AND POS.item_id = LOG.item_id ".
                 " AND POS.item_id = ITEM.item_id ".
                 $this->getTargetIpAddressRangeQuery($start_ip, $finish_ip, "LOG").
                 $this->getExclusiveSitelicenseItemtype("ITEM").
                 " ". Repository_Components_Loganalyzor::perMonthlyQuery(). ", IDX.online_issn ;";
        $params = array();
        $params[] = $start_date;
        $params[] = $finish_date;
        $params[] = RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE;
        $params[] = 1;
        
        $result = $this->dbAccess->executeQuery($query, $params);
        
        return $result;
    }
    
    /** 
     * get item view records per month
     * 
     * @param string $issn
     * @param string $start_date
     * @param string $finish_date
     */
    private function getItemsViewPerMonthly($issn, $start_date, $finish_date, $start_ip, $finish_ip) {
        $online_issn = "";
        for($ii = 0; $ii < count($issn); $ii++) {
            if($ii != 0) {
                $online_issn .= ",";
            }
            $online_issn .= "'". $issn[$ii]. "'";
        }
    
        $query = "SELECT COUNT(DISTINCT LOG.record_date), ".
                 " IDX.online_issn, ".
                 Repository_Components_Loganalyzor::dateformatMonthlyQuery("LOG").
                 " FROM ". Repository_Components_Loganalyzor::execlusiveDoubleAccessSubQuery(RepositoryConst::LOG_OPERATION_DETAIL_VIEW, "", $start_date, $finish_date, RepositoryConst::LOG_OPERATION_DETAIL_VIEW). " AS LOG, ".
                        DATABASE_PREFIX. "repository_index AS IDX, ".
                        DATABASE_PREFIX. "repository_position_index AS POS, ".
                        DATABASE_PREFIX. "repository_item AS ITEM ".
                 " WHERE LOG.record_date >= ? ".
                 " AND LOG.record_date <= ? ".
                 " ". Repository_Components_Loganalyzor::execlusiveIpAddressQuery("LOG").
                 " ". Repository_Components_Loganalyzor::execlusiveRobotsQuery("LOG").
                 " AND LOG.operation_id = ? ".
                 " AND IDX.biblio_flag = ? ".
                 " AND IDX.online_issn IN ( ". $online_issn. " ) ".
                 " AND IDX.index_id = POS.index_id ".
                 " AND POS.item_id = LOG.item_id ".
                 " AND POS.item_id = ITEM.item_id ".
                 $this->getTargetIpAddressRangeQuery($start_ip, $finish_ip, "LOG").
                 $this->getExclusiveSitelicenseItemtype("ITEM").
                 " ". Repository_Components_Loganalyzor::perMonthlyQuery(). ", IDX.online_issn ;";
        $params = array();
        $params[] = $start_date;
        $params[] = $finish_date;
        $params[] = RepositoryConst::LOG_OPERATION_DETAIL_VIEW;
        $params[] = 1;
        
        $result = $this->dbAccess->executeQuery($query, $params);

        return $result;
        
    }
    
    /** 
     * make ip range query parts
     * 
     * @param stirng $start_ip
     * @param string $finish_ip
     * @param string $abbreviation
     */
    private function getTargetIpAddressRangeQuery($start_ip, $finish_ip, $abbreviation) {
        $query_parts_ip = "";
        if($abbreviation == "") {
            $query_parts_ip = " AND numeric_ip_address >= ". $start_ip.
                              " AND numeric_ip_address <= ". $finish_ip;
        } else {
            $query_parts_ip = " AND ". $abbreviation. ".numeric_ip_address >= ".$start_ip.
                              " AND ". $abbreviation. ".numeric_ip_address <= ". $finish_ip;
        }
        
        return $query_parts_ip;
    }
    
    /** 
     * compress file
     * 
     * @param string $dir_path
     */
    private function compressFile($dir_path, &$zip_file) {
        $output_files = array($dir_path);
        // set zip file name
        $zip_file = "SiteLicenseUserReport_". date("Ym", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))). ".zip";
        // compress zip file	
        File_Archive::extract(
            $output_files,
            File_Archive::toArchive($zip_file, File_Archive::toFiles(WEBAPP_DIR.'/uploads/repository/'))
        );
        if ($handle = opendir($dir_path)) {
            while (false !== ($file = readdir($handle))) {
                unlink($dir_path. $file);
            }
        closedir($handle);
        $this->removeDirectory($dir_path);
        }
    }
    
    /** 
     * send site license report mail
     * 
     * @param string $file_path
     * @param string $mail_address
     * @param string $organization_name
     */
    private function sendSitelicenseReport($file_path, $file_name, $mail_address, $organization_name) {
        // サイト名と管理者メールアドレスを取得する
        $query = "SELECT conf_value FROM ". DATABASE_PREFIX. "config ".
                 "WHERE conf_name = ? ".
                 "OR conf_name = ? ".
                 "ORDER BY conf_id ASC ;";
        $params = array();
        $params[] = "sitename";
        $params[] = "from";
        $result = $this->dbAccess->executeQuery($query, $params);
        
        // タイトル
        $sub = "[". $result[0]["conf_value"]. "] ".
               date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))).
               " ".$this->smartyAssign->getLang("repository_sitelicense_mail_subject");
        $this->mailMain->setSubject($sub);
        // 本文
        $body = sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_dear"), $organization_name)."\n\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_thank"), $result[0]["conf_value"])."\n".
                date("Y-m", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))).$this->smartyAssign->getLang("repository_sitelicense_mail_body_announcement")."\n\n".
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_body_unnecessary"), $result[1]["conf_value"])."\n\n\n".
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
                sprintf($this->smartyAssign->getLang("repository_sitelicense_mail_footer_contact"), $result[0]["conf_value"])."\n".
                $result[1]["conf_value"];
        $this->mailMain->setBody($body);
        // 送り先
        $users = array();
        // メールアドレスをデコード
        $mail_address = str_replace("&#124;", "|", $mail_address);
        $mail_address = str_replace("&#44;", ",", $mail_address);
        $mail_address = str_replace("&#46;", ".", $mail_address);
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
        $query = "DELETE FROM ".DATABASE_PREFIX ."repository_send_mail_sitelicense ".
                 "WHERE organization_name = ? ".
                 "AND start_ip_address = ? ".
                 "AND finish_ip_address = ? ".
                 "AND mail_address = ? ;";
        $params = array();
        $params[] = $sl_user_info[0]["organization_name"];
        $params[] = $sl_user_info[0]["start_ip_address"];
        $params[] = $sl_user_info[0]["finish_ip_address"];
        $params[] = $sl_user_info[0]["mail_address"];
        $this->dbAccess->executeQuery($query, $params);
        
        $this->exitAction();
    }
    
    /** 
     * get exclusive sitelicense itemtype
     * 
     * @param string $abbreviation
     */
    private function getExclusiveSitelicenseItemtype($abbreviation) {
        $query = "SELECT param_value FROM ".DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params = "site_license_item_type_id";
        $result = $this->dbAccess->executeQuery($query, $params);
        $item_type_id_query = "";
        if(strlen($result[0]["param_value"]) > 0) {
            if($abbreviation == "") {
                $item_type_id_query = " AND item_type_id NOT IN (". $result[0]["param_value"]. ") ";
            } else {
                $item_type_id_query = " AND ". $abbreviation.".item_type_id NOT IN (". $result[0]["param_value"]. ") ";
            }
        }
        
        return $item_type_id_query;
    }
}
?>