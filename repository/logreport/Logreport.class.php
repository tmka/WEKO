<?php
// --------------------------------------------------------------------
//
// $Id: Logreport.class.php 57652 2015-09-03 10:28:00Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';

/**
 * Make log report
 *
 * @package  NetCommons
 * @author    Y.Nakao(IVIS)
 * @copyright   2006-2009 NetCommons Project
 * @license  http://www.netcommons.org/license.txt  NetCommons License
 * @project  NetCommons Project, supported by National Institute of Informatics
 * @access    public
 */
class Repository_Logreport extends WekoAction
{
    // const
    const FILE_NAME_SITE_ACCESS = "logReport_SiteAccess_";
    const FILE_NAME_FILE_DOWNLOAD = "logReport_FileView_";
    const FILE_NAME_PAY_PER_VIEW = "logReport_PayPerView_";
    const FILE_NAME_INDEX_ACCESS = "logReport_IndexAccess_";
    const FILE_NAME_SUPPLE_ACCESS = "logReport_SuppleAccess_";
    const FILE_NAME_HOST_ACCESS = "logReport_HostAccess_";
    const FILE_NAME_DETAIL_VIEW = "logReport_DetailView_";
    const FILE_NAME_USER_AFFILIATE = "logReport_UserAffiliate_";
    const FILE_NAME_DOWNLOAD_PER_USER = "logReport_FileViewPerUser_";
    const FILE_NAME_SEARCH_COUNT = "logReport_SearchCount_";
    
    const IS_SITELICENSE = "is_sitelicense";
    const IS_NOT_SITELICENSE = "is_not_sitelicense";
    
    // start date
    public $sy_log = null;
    public $sm_log = null;
    public $sd_log = 01;
    public $start_date = '';
    public $disp_start_date = '';
    // end date
    public $ey_log = "";
    public $em_log = "";
    public $ed_log = 31;
    public $end_date = '';
    public $disp_end_date = '';
    
    /**
     * If this value is true, send mail with log report
     *
     * @var boolean
     */
    public $mail = null;
    
    /**
     * send mail object
     *
     * @var object
     */
    public $mailMain = null;
    
    /**
     * nc2 login id with admin authority
     *
     * @var string
     */
    public $login_id = null;
    
    /**
     * nc login password with admin authority
     *
     * @var string
     */
    public $password = null;
    
    /**
     * language resource
     *
     * @var object
     */
    private $smartyAssign = null;
    
    /**
     * display language by Session
     *
     * @var string
     */
    private $lang = "";
    
    /**
     * NC2 group list for write log report
     *
     * @var array
     */
    private $groupList = null;
    
    /**
     * create log report(download or send mail)
     *
     */
    protected function executeApp()
    {
        $this->initForLogreport();
        
        if($this->isLoginAdministrator() === false)
        {
            echo $this->smartyAssign->getLang("repository_log_report_error");
            return "";
        }
        
        // set start date
        $this->start_date = sprintf("%d-%02d-%02d",$this->sy_log, $this->sm_log,$this->sd_log);
        $this->disp_start_date = sprintf("%d-%02d",$this->sy_log, $this->sm_log);
        // set end date
        $this->ey_log = $this->sy_log;
        $this->em_log = $this->sm_log;
        $this->end_date = sprintf("%d-%02d-%02d",$this->ey_log, $this->em_log,$this->ed_log);
        $this->disp_end_date = sprintf("%d-%02d",$this->ey_log, $this->em_log);
        
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Db = $this->Db;
        
        // -----------------------------------------------
        // check logreport exist
        // -----------------------------------------------
        if($this->isExistLogReportFileAndDownload())
        {
            $zip_file = "logReport_". sprintf("%d%02d",$this->sy_log, $this->sm_log).".zip";
            // when log file exist, download that log file
            $repositoryDownload = new RepositoryDownload();
            $repositoryDownload->downloadFile(WEBAPP_DIR."/logs/weko/logreport/".$zip_file, $zip_file);
        }
        else 
        {
            $tmp_dir = $this->createTempDirectory();
            
            $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
            $businessLogreport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
            $businessLogreport->setStartYear($this->sy_log);
            $businessLogreport->setStartMonth($this->sm_log);
            $businessLogreport->setEndYear($this->ey_log);
            $businessLogreport->setEndMonth($this->em_log);
            $businessLogreport->setAdminBase($this->repository_admin_base);
            $businessLogreport->setAdminRoom($this->repository_admin_room);
            
            try {
                $businessLogreport->execute();
                
                // create each log report
                $output_files = array();
                $output_files = $this->createLogReport($tmp_dir);
                
                // -----------------------------------------------
                // compress zip file
                // -----------------------------------------------
                // set zip file name
                $zip_file = "logReport_". $this->getStrOfJoinedStartYearAndStartMonth().".zip";
                // compress zip file    
                File_Archive::extract(
                    $output_files,
                    File_Archive::toArchive($zip_file, File_Archive::toFiles( $tmp_dir ))
                );
        
                // -----------------------------------------------
                // download zip file
                // -----------------------------------------------
                if($this->mail == "true"){
                    // send mail
                    $this->sendMailLogReport($tmp_dir.$zip_file, $zip_file);
                } else {
                    // ダウンロードアクション処理(download)
                    $repositoryDownload = new RepositoryDownload();
                    $repositoryDownload->downloadFile($tmp_dir.$zip_file, $zip_file);
                }
            } catch (AppException $e) {
                    echo "<script class='nc_script' type='text/javascript'>alert('".$this->smartyAssign->getLang('repository_log_excluding')."');</script>";
            }
        }
        
        return "";
    }
    
    /**
     * access num as site lisence
     *
     * @return string log report 
     */
    private function makeAccessLogReport(){
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $businessLogreport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $businessLogreport->setAdminBase($this->repository_admin_base);
        $businessLogreport->setAdminRoom($this->repository_admin_room);
        
        $log_data = $businessLogreport->createSiteAccessReport();
        
        $is_sitelicense = self::IS_SITELICENSE;
        $not_sitelicense = self::IS_NOT_SITELICENSE;
        
        // -----------------------------------------------
        // make log report
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_access_report_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= "\t";
        $str .= $this->smartyAssign->getLang("repository_log_weko_top")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_search_result")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_item_detail")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_file_download");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_is_sitelicense")."\t";
        $str .= $log_data[$is_sitelicense]['top']."\t";
        $str .= $log_data[$is_sitelicense]['search']."\t";
        $str .= $log_data[$is_sitelicense]['detail']."\t";
        $str .= $log_data[$is_sitelicense]['download'];
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_not_sitelicense")."\t";
        $str .= $log_data[$not_sitelicense]['top']."\t";
        $str .= $log_data[$not_sitelicense]['search']."\t";
        $str .= $log_data[$not_sitelicense]['detail']."\t";
        $str .= $log_data[$not_sitelicense]['download'];
        $str .= "\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_access_report_title");
        $str .= $this->smartyAssign->getLang("repository_log_detail_info")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_organization")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_weko_top")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_search_result")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_item_detail")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_file_download");
        $str .= "\r\n";
        foreach ($log_data as $key => $value) {
            if($key != $is_sitelicense && $key != $not_sitelicense){
                $str .= $key."\t";
                $str .= $log_data[$key]['top']."\t";
                $str .= $log_data[$key]['search']."\t";
                $str .= $log_data[$key]['detail']."\t";
                $str .= $log_data[$key]['download'];
                $str .= "\r\n";
            }
        }
        return $str;
    }
    
    /**
     * detail view per index
     *
     * @return string log report 
     */
    private function makeIndexLogReport(){
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        $indexAccessReport = $logReport->createIndexAccessReport();
        
        $total_access = $indexAccessReport["totalAccess"];
        
        // -----------------------------------------------
        // get all index's child item access num
        // -----------------------------------------------
        
        $index_data = $indexAccessReport["detailViewPerIndex"];
        
        // -----------------------------------------------
        // make log report
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_index_report_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_access_num");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_index_detail_access_total")."\t";
        $str .= $total_access."\t";
        $str .= "\r\n";
        
        $this->outputDetailViewPerIndexes($index_data, $index_data['0'], "", $str);
        return $str;
    }
    
    /**
     * output detail view num per each indexes in recursive
     *
     * @param array $all_index
     * @param array $index
     * @param string $parent_name : parent indexes path
     * @param string $str : output data
     */
    function outputDetailViewPerIndexes(&$all_index, $index, $parent_name, &$str){
        foreach ($index as $key => $val){
            if(strlen($parent_name) > 0){
                $val['name'] = $parent_name."\\".$val['name'];
            }
            $str .= $val['name']."\t".$val['detail_view']."\r\n";
            if(isset($all_index[$val['id']]) && is_array($all_index[$val['id']]) && count($all_index[$val['id']]) > 0){
                $this->outputDetailViewPerIndexes($all_index, $all_index[$val['id']], $val['name'], $str);
                unset($all_index[$val['id']]);
            }
        }
    }
    
    /**
     * create file view download report / pay per view download report
     * 
     * @param string $fileReport
     * @param string $priceReport
     * @return boolean process result
     */
    private function makeFileDownloadLogReport($fileLogPath, $priceLogPath)
    {
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $fileDownloadReport = $logReport->createFileViewReport();
        
        // -----------------------------------------------
        // Make log report of related file
        // -----------------------------------------------
        $this->makeFileView($fileLogPath, $fileDownloadReport["fileViewReport"]);
        $this->makePayPerView($priceLogPath, $fileDownloadReport["payPerViewReport"]);
    }
    
    /**
     * create detail view and download file num per supple items
     *
     * @return string log report 
     */
    function makeSuppleLogReport(){
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        $supple_data = $logReport->createSuppleReport();
        
        // -----------------------------------------------
        // make log report
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_supple_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_item_title")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_supple_contents_title")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_access_num")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_total")."\t";
        $str .= "\r\n";
        
        $this->getIndexAndItemInfoForSuppleLog("", "0", $supple_data, $str);
        return $str;
    }
    
    /**
     * create index tree infomation by recursive processing
     * calculate supplemental contents detail view and download in each index
     * 
     *
     * @param string $pid_name parent index name
     * @param string $pid_id parent index id
     * @param array $supple_data 
     * @return bool true  : success
     *              false : error
     */
    function getIndexAndItemInfoForSuppleLog($pid_name, $pid_id, $supple_data, &$str_log){
        // -----------------------------------------------
        // get child index
        // -----------------------------------------------
        // $pid_id直下のインデックスIDを取得[show_order順] (get child index)
        $children = array();
        $query = "SELECT index_id, index_name, index_name_english, parent_index_id ".
                " FROM ". DATABASE_PREFIX ."repository_index ".
                " WHERE is_delete = ? ".
                " AND parent_index_id = ? ".
                " ORDER BY show_order ";
        $params = array();
        $params[] = 0;
        $params[] = $pid_id;
        $children = $this->Db->execute($query, $params);
        if($children === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        // インデックス直下にあるサプリアイテムを検索
        if($pid_id != "0"){
            $supple_result = array();
            $query = "SELECT item.title, item.title_english, supple.supple_title, supple.supple_title_en, supple.supple_weko_item_id ".
                     "FROM ".DATABASE_PREFIX."repository_index AS idx ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_position_index AS p_idx ON idx.index_id = p_idx.index_id ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_item AS item ON item.item_id = p_idx.item_id AND item.item_no = p_idx.item_no ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_supple AS supple ON item.item_id = supple.item_id AND item.item_no = supple.item_no ".
                     "WHERE idx.index_id = ? ".
                     "AND idx.is_delete = ? ".
                     "AND p_idx.is_delete = ? ".
                     "AND item.is_delete = ? ".
                     "AND supple.is_delete = ? ".
                     "ORDER BY idx.show_order ASC, item.item_id ASC, supple.supple_no ASC;";
            $params = array();
            $params[] = $pid_id;
            $params[] = 0;
            $params[] = 0;
            $params[] = 0;
            $params[] = 0;
            $supple_result = $this->Db->execute($query, $params);
            if($supple_result === false){
                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                throw new AppException($this->Db->ErrorMsg());
            }
            
            for($ii=0; $ii<count($supple_result); $ii++){
                // インデックス名
                $str_log .= $pid_name."\t";
                
                // アイテムタイトル
                if($this->lang == "japanese"){
                    if($supple_result[$ii]['title'] != ""){
                        $str_log .= $supple_result[$ii]['title']."\t";
                    } else {
                        $str_log .= $supple_result[$ii]['title_english']."\t";
                    }
                } else {
                    if($supple_result[$ii]['title_english'] != ""){
                        $str_log .= $supple_result[$ii]['title_english']."\t";
                    } else {
                        $str_log .= $supple_result[$ii]['title']."\t";
                    }
                }
                
                // サプリアイテムタイトル
                if($this->lang == "japanese"){
                    if($supple_result[$ii]['supple_title'] != ""){
                        $str_log .= $supple_result[$ii]['supple_title']."\t";
                    } else {
                        $str_log .= $supple_result[$ii]['supple_title_en']."\t";
                    }
                } else {
                    if($supple_result[$ii]['supple_title_en'] != ""){
                        $str_log .= $supple_result[$ii]['supple_title_en']."\t";
                    } else {
                        $str_log .= $supple_result[$ii]['supple_title']."\t";
                    }
                }
                
                // 閲覧回数、DL回数
                $supple_view = 0;
                $supple_download = 0;
                for($jj=0; $jj<count($supple_data); $jj++){
                    if($supple_data[$jj]["supple_weko_item_id"] == $supple_result[$ii]["supple_weko_item_id"]){
                        $supple_view = $supple_data[$jj]["log_view"];
                        $supple_download = $supple_data[$jj]["log_download"];
                        break;
                    }
                }
                $str_log .= $supple_view."\t".$supple_download."\r\n";
            }
        }
            
        for($ii=0; $ii<count($children); $ii++){
            // 再帰的に子孫を追加(check child index)
            if($this->lang == "japanese"){
                if($pid_name != ""){
                    $index_name = $pid_name."\\".$children[$ii]['index_name'];
                } else {
                    $index_name = $children[$ii]['index_name'];
                }
            } else {
                if($pid_name != ""){
                    $index_name = $pid_name."\\".$children[$ii]['index_name_english'];
                } else {
                    $index_name = $children[$ii]['index_name_english'];
                }
            }
            $this->getIndexAndItemInfoForSuppleLog($index_name, $children[$ii]['index_id'], $supple_data, $str_log);
        }

        return true;
    }
    
    /**
     * send mail for log report
     *
     * @param string $zip_file zip file path
     */
    function sendMailLogReport($file_path, $file_name){
        // get report year and month
        $year = substr($this->disp_start_date, 0, 4);
        $month = substr($this->disp_start_date, 5, 2);
        // send log report mail
        // 定型レポートを送信する
        // 件名
        // set subject
        $subj = $year."-".$month." ".$this->smartyAssign->getLang("repository_log_mail_subj");
        $this->mailMain->setSubject($subj);
        
        // メール本文をリソースから読み込む
        // set Mail body
        $body = '';
        $body .= $year."-".$month." ".$this->smartyAssign->getLang("repository_log_mail_body")."\n\n";
        $this->mailMain->setBody($body);
        // ---------------------------------------------
        // 送信メール情報取得
        //   送信者のメールアドレス
        //   送り主の名前
        //   送信先ユーザを取得
        // create mail body
        //   get send from user mail address
        //   get send from user name
        //   get send to user
        // ---------------------------------------------
        $users = array();
        $this->getLogReportMailInfo($users);
        // ---------------------------------------------
        // 送信先を設定
        // set send to user
        // ---------------------------------------------
        // 送信ユーザを設定
        // $usersの中身
        // $users["email"] : 送信先メールアドレス
        // $user["handle"] : ハンドルネーム
        //                   なければ空白が自動設定される
        // $user["type"]   : type (html(email) or text(mobile_email))
        //                   なければhtmlが自動設定される
        // $user["lang_dirname"] : 言語
        //                         なければ現在の選択言語が自動設定される
        $this->mailMain->setToUsers($users);
        
        // ---------------------------------------------
        // 添付ファイルを設定
        // set attachment file
        // ---------------------------------------------
        // NetCommonsのメールクラスは添付ファイルに対応していない
        // しかし利用しているメールライブラリ(PHPMailer)は添付ファイルを利用可能である
        $attachment = array();
        $attachment[0] = $file_path;        // binary string([5] = true) or path([5] = false)
        $attachment[1] = basename($file_name, ".zip");  // file name
        $attachment[2] = $file_name;        // name
        $attachment[3] = "base64";          // encoding
        $attachment[4] = "application/zip"; // Content-Type
        $attachment[5] = false;             // binary is true
        $attachment[6] = "attachment";      // Content-Disposition
        $attachment[7] = "";                // Content-ID(if disposition is "inline", must fill)
        
        $this->mailMain->_mailer->attachment = array($attachment);
        
        // ---------------------------------------------
        // メール送信
        // send confirm mail
        // ---------------------------------------------
        if(count($users) > 0){
            // 送信者がいる場合は送信
            $return = $this->mailMain->send();
            if($return === false)
            {
                $this->errorLog("logreport: send mail is failed", __FILE__, __CLASS__, __LINE__);
            }
        }
         
        // 言語リソース開放
        $this->Session->removeParameter("smartyAssign");
    }
    
    /**
     * get send users for log report
     *
     * @param array $users
     */
    function getLogReportMailInfo(&$users){
        $users = array();       // メール送信先
        $query = "SELECT param_name, param_value ".
                " FROM ". DATABASE_PREFIX ."repository_parameter ".     // パラメタテーブル
                " WHERE is_delete = ? ".
                " AND param_name = ? ";
        $params = array();
        $params[] = 0;
        $params[] = 'log_report_mail';
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        if(count($result) == 1){
            if($result[0]['param_name'] == 'log_report_mail'){
                $result[0]['param_value'] = str_replace("\r\n", "\n", $result[0]['param_value']);
                $email = explode("\n", $result[0]['param_value']);
                for($jj=0; $jj<count($email); $jj++){
                    if(strlen($email[$jj]) > 0){
                        array_push($users, array("email" => $email[$jj]));
                    }
                }
                
            }
        }
        return true;
    }
    
    /**
     * host access log report
     *
     * @return string log report text
     */
    private function makeHostLogReport(){
        // -----------------------------------------------
        // init
        // -----------------------------------------------
        $str = "";
        $host_cnt = array();
        
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        $result = $logReport->createHostAccessReport();
        
        // -----------------------------------------------
        // make log report text
        // -----------------------------------------------
        $str .= $this->smartyAssign->getLang("repository_log_host_access")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_host")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_ip_address")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_weko_top")."\t";
        $str .= "\r\n";
        for($ii=0; $ii<count($result); $ii++){
            $str .= $result[$ii]['host']."\t";
            $str .= $result[$ii]['ip_address']."\t";
            $str .= $result[$ii]['cnt']."\r\n";
        }
        return $str;
    }
    
    /**
     * make detail view log 
     *
     */
    private function makeDetailViewLogReport(){
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        
        // -----------------------------------------------
        // get group list(room_id and room_name list)
        // -----------------------------------------------
        $all_group = $this->groupList;
        
        // -----------------------------------------------
        // get data for make log
        // -----------------------------------------------
        $viewLog = $logReport->createDetailViewReport();
        
        // -----------------------------------------------
        // make file price download log
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_DetailView_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_DetailView_title");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_DetailView_content_title")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_Fashview_total")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_not_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_Fashview_guest")."\t";
        for($ii=0; $ii<count($all_group); $ii++){
            if($ii != 0){
                $str .= "\t";
            }
            $str .= $all_group[$ii]['page_name'];
        }
        $str .= "\r\n";
        foreach ($viewLog as $key => $value) {
            if(strlen($value['title']) > 0){
                $str .= $value['title'];
            } else if(strlen($value['title_en']) > 0){
                $str .= $value['title_en'];
            }
            $str .= "\t";
            $str .= $value['index_name']."\t";
            $str .= $value['total']."\t";
            $str .= $value['not_login']."\t";
            $str .= $value['group']['0']."\t";
            for($ii=0; $ii<count($all_group); $ii++){
                if($ii != 0){
                    $str .= "\t";
                }
                if(isset($value['group'][$all_group[$ii]['room_id']])){
                    $str .= $value['group'][$all_group[$ii]['room_id']];
                } else {
                    $str .= "0";
                }
            }
            $str .= "\r\n";
        }
        return $str;
    }
    
    /**
     * get user num per authority and user num per group
     *
     * @return string logReport by user text
     */
    private function makeUserLogReport(){
        // -----------------------------------------------
        // init
        // -----------------------------------------------
        $str = "";
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        $userAffiliation = $logReport->createUserAffiliateReport();
        
        $userAuth = $userAffiliation["userAuth"];
        $all_group = $userAffiliation["all_group"];
        
        // -----------------------------------------------
        // make log report text
        // -----------------------------------------------
        $str .= $this->smartyAssign->getLang("repository_log_user_affiliate")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_user_baseauthority_cnt")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_user_baseauthority")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_user");
        $str .= "\r\n";
        for($ii=0; $ii<count($userAuth); $ii++){
            if(defined($userAuth[$ii]['role_authority_name']) && strlen(constant($userAuth[$ii]['role_authority_name'])) > 0){
                $str .= constant($userAuth[$ii]['role_authority_name'])."\t";
            } else {
                $str .= $userAuth[$ii]['role_authority_name']."\t";
            }
            $str .= $userAuth[$ii]['cnt'];
            $str .= "\r\n";
        }
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_user_room_cnt")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_user_room")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_user");
        $str .= "\r\n";
        for($ii=0; $ii<count($all_group); $ii++){
            $str .= $all_group[$ii]['page_name']."\t";
            $str .= $all_group[$ii]['cnt'];
            $str .= "\r\n";
        }
        return $str;
    }
    
    /**
     * The download log output character string classified by user is acquired.
     * 
     * @return string downloadLog by user text
     */
    private function makeUsersDLLogReport(){
        // -----------------------------------------------
        // init
        // -----------------------------------------------
        $str = "";
        
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        $this->traceLog("logReport::getFileViewPerUser", __FILE__, __CLASS__, __LINE__);
        $result = $logReport->createFileViewPerUser();
        
        // -----------------------------------------------
        // make log report text
        // -----------------------------------------------
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_loginid")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_handle")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_name")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_authority")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_affiliatelist")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_count_user_num")."\t";
        $str .= "\r\n";
        
        for($ii=0; $ii<count($result); $ii++){
            // ---------------------------------------------
            // create output a row text
            // ---------------------------------------------
            $str .= $result[$ii]["login_id"]."\t";
            $str .= $result[$ii]["handle"]."\t";
            $str .= $result[$ii]["name"]."\t";
            $str .= $result[$ii]["base_authority_name"].'/'.$result[$ii]["room_authority_name"]."\t";
            $str .= $result[$ii]["group_name_list"]."\t";
            $str .= strval($result[$ii]["dl_count"])."\r\n";
        }
        
        return $str;
    }
    
    /**
     * The list of affiliation group names is acquired to the user who specified.
     *
     * @param string $user_id
     * @return string UserGroupNameList 
     */
    private function getUserGroupNameList($user_id){
        // ---------------------------------------------
        // init
        // ---------------------------------------------
        $str = "";
        // ---------------------------------------------
        // get List from pages Table
        // ---------------------------------------------
        $query = "SELECT PAGES.page_name".
                 " FROM ".DATABASE_PREFIX."pages AS PAGES".
                 " INNER JOIN ".DATABASE_PREFIX."pages_users_link AS PAGES_USERS_LINK".
                 " ON PAGES.room_id=PAGES_USERS_LINK.room_id".
                 " WHERE PAGES_USERS_LINK.user_id = ?".
                 " AND PAGES.private_flag = ? AND PAGES.space_type = ?".
                 " ORDER BY PAGES.page_name ASC;";
        $params = null;
        $params[] = $user_id;
        $params[] = 0;
        $params[] = _SPACE_TYPE_GROUP;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            return false;
        }
        for($ii=0; $ii<count($result); $ii++){
            
            if($ii == count($result)-1){
                $str .= $result[$ii]['page_name'];
            }else{
                $str .= $result[$ii]['page_name'].",";
            }
        }

        return $str;
    }
    
    /**
     * Create file download report
     *
     * @param string filePath : logReport_FileView
     * @param array() $fileViewReport
     */
    private function makeFileView($logFilePath, $fileViewReport)
    {
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        
        $this->traceLog("open file download report", __FILE__, __CLASS__, __LINE__);
        $fp = fopen($logFilePath, "w");
        if($fp === false){
            $ex = new AppException("mistake file open::". $logFilePath);
            throw $ex;
        }
        
        try{
            $this->traceLog("write bom", __FILE__, __CLASS__, __LINE__);
            $this->writeReport($fp, $BOM);
            
            $this->writeCloseFileAccessReport($fp, $fileViewReport);
            
            $this->writeOpenFileAccessReport($fp, $fileViewReport);
        } catch(AppException $ex) {
            fclose($fp);
            throw $ex;
        }
        
        // write file log report
        $this->traceLog("close file download report", __FILE__, __CLASS__, __LINE__);
        $result = fclose($fp);
        if($result === false){
            $ex = new AppException("mistake file close::". $logFilePath);
            throw $ex;
        }
    }
    
    /**
     * Create file price download report
     *
     * @param string $logFilePath : PayPerView
     * @param array() $payPerViewReport
     */
    private function makePayPerView($logFilePath, $payPerViewReport)
    {
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        
        $this->traceLog("open file download report", __FILE__, __CLASS__, __LINE__);
        $fp = fopen($logFilePath, "w");
        if($fp === false){
            $ex = new AppException("mistake file open::". $logFilePath);
            throw $ex;
        }
        try{
            $this->writeReport($fp, $BOM);
            
            $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
            $this->writeClosePriceFileAccessReport($fp, $payPerViewReport);
            
            $this->writeOpenPriceFileAccessReport($fp, $payPerViewReport);
            $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        } catch(AppException $ex){
            fclose($fp);
            throw $ex;
        }
        
        // write file log report
        $this->traceLog("close file download report", __FILE__, __CLASS__, __LINE__);
        $result = fclose($fp);
        if($result === false){
            $ex = new AppException("mistake file close::". $logFilePath);
            throw $ex;
        }
    }
    
    /**
     * create search log report
     * 
     */
    private function makeSearchLogReport(){
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        
        $keywordRanking = $logReport->createKeywordRankingReport();
        
        // -----------------------------------------------
        // make log report
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_search_keyword_report_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_search_keyword_name")."\t";
        $str .= $this->smartyAssign->getLang("repository_search_num");
        $str .= "\r\n";
        for($ii=0; $ii<count($keywordRanking); $ii++){
            $str .= $keywordRanking[$ii]['search_keyword']."\t";
            $str .= $keywordRanking[$ii]['CNT']."\r\n";
        }
        return $str;
    }
    
    /**
     * set start date - now date to table
     *
     */
    private function setStartDateByCurrentDate()
    {
        $NOW_DATE = new Date();
        $query = "SELECT DATE_FORMAT(DATE_SUB(?, INTERVAL 1 MONTH), '%Y') AS tmp_year,".
                 " DATE_FORMAT(DATE_SUB(?, INTERVAL 1 MONTH), '%m') AS tmp_month;";
        $params = array();
        $params[] = $NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-".$NOW_DATE->getDay();
        $params[] = $NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-".$NOW_DATE->getDay();
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) != 1){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        $this->sy_log = $result[0]['tmp_year'];
        $this->sm_log = sprintf("%02d",$result[0]['tmp_month']);
    }
    
    /**
     * is exist moved log report?
     *
     */
    private function isExistLogReportFileAndDownload()
    {
        $zip_file = "logReport_". sprintf("%d%02d",$this->sy_log, $this->sm_log).".zip";
            // when log file exist, download that log file
        if(file_exists(WEBAPP_DIR."/logs/weko/logreport/".$zip_file)){
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Ecreate temporary directory and return path
     *
     * @return string
     */
    private function createTempDirectory()
    {
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
        $tmp_dir = $businessWorkdirectory->create();
        
        return $tmp_dir;
    }
    
    /**
     * return start date string(YYYYMM)
     *
     * @return string
     */
    private function getStrOfJoinedStartYearAndStartMonth()
    {
        return sprintf("%d-%02d",$this->sy_log, $this->sm_log);
    }
    
    /**
     * write log report add bom
     *
     * @param string $logStr
     * @param string $logFileName
     */
    private function writeLogReport($logStr, $logFileName)
    {
        $BOM = pack('C*',0xEF,0xBB,0xBF);
        
        $log_report = fopen($logFileName, "w");
        fwrite($log_report, $BOM.$logStr);
        fclose($log_report);
    }
    
    /**
     * create each log report
     *
     * @param string $tmp_dir
     * @return array
     */
    public function createLogReport($tmp_dir)
    {
        $output_files = array();
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        // サイトライセンス別アクセス数(access num as site lisence)
        $this->traceLog("write access report per sitelicense", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeAccessLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_SITE_ACCESS. $this->getStrOfJoinedStartYearAndStartMonth().".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        // 課金ファイルダウンロード数(download file num)
        // common file download report path
        $fileLogPath = $tmp_dir. self::FILE_NAME_FILE_DOWNLOAD. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        // accounting file download report path
        $priceLogPath = $tmp_dir. self::FILE_NAME_PAY_PER_VIEW. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        
        $this->traceLog("output file download report data", __FILE__, __CLASS__, __LINE__);
        $this->makeFileDownloadLogReport($fileLogPath, $priceLogPath);
        array_push( $output_files, $fileLogPath );
        array_push( $output_files, $priceLogPath );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        // インデックごとの詳細画面アクセス数(detail view as index)
        $this->traceLog("write access report per index", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeIndexLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_INDEX_ACCESS. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        $this->infoLog("businessLogreport", __FILE__, __CLASS__, __LINE__);
        $logReport = BusinessFactory::getFactory()->getBusiness("businessLogreport");
        $logReport->setAdminBase($this->repository_admin_base);
        $logReport->setAdminRoom($this->repository_admin_room);
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        if($logReport->isCreateSuppleReport()){
            // サプリコンテンツの閲覧回数およびダウンロード回数(detail view and download file num as supple items)
            $this->traceLog("write supple contents usagestatics report", __FILE__, __CLASS__, __LINE__);
            $log_str = $this->makeSuppleLogReport();
            $log_file = $tmp_dir. self::FILE_NAME_SUPPLE_ACCESS. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
            $this->writeLogReport($log_str, $log_file);
            array_push( $output_files, $log_file );
            
            $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        }
        
        $this->traceLog("write access report per host", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeHostLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_HOST_ACCESS. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        $this->traceLog("write item detail report", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeDetailViewLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_DETAIL_VIEW. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        $this->traceLog("write user affiliation report", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeUserLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_USER_AFFILIATE. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        $this->traceLog("write file download report per user", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeUsersDLLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_DOWNLOAD_PER_USER. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        // 検索キーワードランキング
        $this->traceLog("write search keyword report", __FILE__, __CLASS__, __LINE__);
        $log_str = $this->makeSearchLogReport();
        $log_file = $tmp_dir. self::FILE_NAME_SEARCH_COUNT. $this->getStrOfJoinedStartYearAndStartMonth(). ".tsv";
        $this->writeLogReport($log_str, $log_file);
        array_push( $output_files, $log_file );
        
        $this->debugLog("usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        
        return $output_files;
    }

    /**
     * check be able to login or not and login user has authority of administrator
     *
     * @return : is admin login
     */
    private function isLoginAdministrator()
    {
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->Db;
        $repositoryAction->TransStartDate = $this->accessDate;
        $repositoryAction->setConfigAuthority();
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
        $repositoryAction->dbAccess = new RepositoryDbAccess($this->Db);
        
        // -----------------------------------------------
        // check login
        // -----------------------------------------------
        $user_id = $this->Session->getParameter("_user_id");
        if($user_id != "0"){
            // check auth
            $user_authority_id = $this->Session->getParameter("_user_auth_id");
            $authority_id = $repositoryAction->getRoomAuthorityID($user_id);
        } else if(isset($this->login_id) && strlen($this->login_id) > 0 &&
                  isset($this->password) && strlen($this->password) > 0){
            
            $ret = $repositoryAction->checkLogin($this->login_id, $this->password, $Result_List, $error_msg);
            if($ret === false){
                return false;
            }
            
            $container =& DIContainerFactory::getContainer();
            $authoritiesView =& $container->getComponent("authoritiesView");
            $authorities =& $authoritiesView->getAuthorityById($Result_List[0]["role_authority_id"]);
            $user_authority_id = $authorities['user_authority_id'];
            $authority_id = $repositoryAction->getRoomAuthorityID($Result_List[0]["user_id"]);
        } else {
            return false;
        }
        
        if($user_authority_id < $repositoryAction->repository_admin_base || $authority_id < $repositoryAction->repository_admin_room){
            return false;
        }
        
        return true;
    }
    
    /**
     * initialize member value for logReport
     *
     */
    private function initForLogreport()
    {
        ini_set('max_execution_time', 2400);
        ini_set('memory_limit', '2048M');
        
        // get language
        $this->lang = $this->Session->getParameter("_lang");
        
        $this->setupLanguageResourceForOtherAction();
        
        // If start year and start month is not set, start year and start month set to current year and current month
        // set start date
        if(!isset($this->sy_log) || strlen($this->sy_log) == 0 || intval($this->sy_log) < 1 || 
            !isset($this->sm_log) || strlen($this->sm_log) == 0 || intval($this->sm_log) < 1 || 12 < intval($this->sm_log) ){
            
            $this->setStartDateByCurrentDate();
        }
        
        // send mail address check
        $users = array();
        if($this->mail == "true"){
            $this->getLogReportMailInfo($users);
            if(count($users) == 0){
                $this->addErrMsg($this->Db->ErrorMsg());
                throw new AppException($this->Db->ErrorMsg());
            }
        }
        
        $this->setupGroupList();
        
        // for download and send mail
        $this->exitFlag = true;
    }
    
    /**
     * set language resource for execute by other action(repository_edit_log_move)
     *
     */
    public function setupLanguageResourceForOtherAction()
    {
        // get language resource
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $this->smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
    }
    
    /**
     * set group list
     *
     */
    public function setupGroupList()
    {
        // set all nc2 group list
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Db = $this->Db;
        $result = $repositoryAction->getGroupList($all_group, $error_msg);
        if($result === false){
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        $this->groupList = $all_group;
    }
    
    /**
     * write close file access report
     *
     * @param handle $fp
     * @param array() $fileViewReport
     */
    private function writeCloseFileAccessReport($fp, $fileViewReport)
    {
        // access log of close file
        $fileLog = $fileViewReport["fileLog"];
        
        // -----------------------------------------------
        // Make file download log
        // -----------------------------------------------
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_file_download_title")."\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n");
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_file"));
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_filename")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_index")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_total")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_not_login")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_login")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_admin")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_register"));
        $this->writeReport($fp, "\r\n");
        foreach ($fileLog as $key => $value)
        {
            $this->writeReport($fp, $value['file_name']."\t");
            $this->writeReport($fp, $value['index_name']."\t");
            $this->writeReport($fp, $value['total']."\t");
            $this->writeReport($fp, $value['not_login']."\t");
            $this->writeReport($fp, $value['login']."\t");
            $this->writeReport($fp, $value['site_license']."\t");
            $this->writeReport($fp, $value['admin']."\t");
            $this->writeReport($fp, $value['register']);
            $this->writeReport($fp, "\r\n");
        }
        $this->writeReport($fp, "\r\n");
    }
    
    /**
     * write open file access report
     *
     * @param handle $fp
     * @param array() $fileViewReport
     */
    private function writeOpenFileAccessReport($fp, $fileViewReport)
    {
        // access log of open file
        $fileOpenLog = $fileViewReport["fileOpenLog"];
        
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_open"));
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_filename")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_index")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_total")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_not_login")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_login")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_admin")."\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_register"));
        $this->writeReport($fp, "\r\n");
        foreach ($fileOpenLog as $key => $value)
        {
            $this->writeReport($fp, $value['file_name']."\t");
            $this->writeReport($fp, $value['index_name']."\t");
            $this->writeReport($fp, $value['total']."\t");
            $this->writeReport($fp, $value['not_login']."\t");
            $this->writeReport($fp, $value['login']."\t");
            $this->writeReport($fp, $value['site_license']."\t");
            $this->writeReport($fp, $value['admin']."\t");
            $this->writeReport($fp, $value['register']);
            $this->writeReport($fp, "\r\n");
        }
    }
    
    /**
     * make par per view report for close file
     *
     * @param handle $fp
     * @param array() $payPerViewReport
     */
    private function writeClosePriceFileAccessReport($fp, $payPerViewReport)
    {
        // log of close price file
        $priceLog = $payPerViewReport["priceLog"];
        
        // -----------------------------------------------
        // Get group list(room_id and room_name list)
        // -----------------------------------------------
        $all_group = array();
        $error_msg = "";
        
        $all_group = $this->groupList;
        
        // -----------------------------------------------
        // make file price download log
        // -----------------------------------------------
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_title"));
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_totaling_month"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->disp_start_date);
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_price"));
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_filename"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_index"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_total"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_not_login"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_item_gest"));
        $this->writeReport($fp, "\t");
        for($ii=0; $ii<count($all_group); $ii++)
        {
            $this->writeReport($fp, $all_group[$ii]['page_name']);
            $this->writeReport($fp, "\t");
        }
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_deleted_group"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_sitelicense"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_admin"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_register"));
        $this->writeReport($fp, "\r\n");
        $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        foreach($priceLog as $key => $value)
        {
            $deletedGroup = $value['total'] - $value['not_login'] - $value['group']['0'] -
                            $value['site_license'] - $value['admin'] - $value['register'];
            $this->writeReport($fp, $value['file_name']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['index_name']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['total']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['not_login']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['group']['0']);
            $this->writeReport($fp, "\t");
            for($ii=0; $ii<count($all_group); $ii++)
            {
                if(!isset($value['group'][$all_group[$ii]['room_id']]))
                {
                    $this->writeReport($fp, "0\t");
                }
                else
                {
                    $this->writeReport($fp, $value['group'][$all_group[$ii]['room_id']]);
                    $this->writeReport($fp, "\t");
                    $deletedGroup -= $value['group'][$all_group[$ii]['room_id']];
                }
            }
            $this->writeReport($fp, $deletedGroup);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['site_license']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['admin']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['register']);
            $this->writeReport($fp, "\r\n");
        }
        $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        $this->writeReport($fp, "\r\n");
    }
    
    /**
     * make payper view report for open file
     *
     * @param handle $fp
     * @param array() $payPerViewReport
     */
    private function writeOpenPriceFileAccessReport($fp, $payPerViewReport)
    {
        // log of openprice file
        $priceOpenLog = $payPerViewReport["priceOpenLog"];
        
        $all_group = array();
        $error_msg = "";
        
        $all_group = $this->groupList;
        
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_open"));
        $this->writeReport($fp, "\r\n");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_filename"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_index"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_total"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_not_login"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_item_gest"));
        $this->writeReport($fp, "\t");
        for($ii=0; $ii<count($all_group); $ii++)
        {
            $this->writeReport($fp, $all_group[$ii]['page_name']);
            $this->writeReport($fp, "\t");
        }
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_deleted_group"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_sitelicense"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_admin"));
        $this->writeReport($fp, "\t");
        $this->writeReport($fp, $this->smartyAssign->getLang("repository_log_download_register"));
        $this->writeReport($fp, "\r\n");
        $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
        foreach($priceOpenLog as $key => $value)
        {
            $deletedGroup = $value['total'] - $value['not_login'] - $value['group']['0'] -
                            $value['site_license'] - $value['admin'] - $value['register'];
            $this->writeReport($fp, $value['file_name']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['index_name']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['total']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['not_login']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['group']['0']);
            $this->writeReport($fp, "\t");
            for($ii=0; $ii<count($all_group); $ii++)
            {
                if(!isset($value['group'][$all_group[$ii]['room_id']]))
                {
                    $this->writeReport($fp, "0\t");
                }
                else
                {
                    $this->writeReport($fp, $value['group'][$all_group[$ii]['room_id']]);
                    $this->writeReport($fp, "\t");
                    $deletedGroup -= $value['group'][$all_group[$ii]['room_id']];
                }
            }
            
            $this->writeReport($fp, $deletedGroup);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['site_license']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['admin']);
            $this->writeReport($fp, "\t");
            $this->writeReport($fp, $value['register']);
            $this->writeReport($fp, "\r\n");
        }
        $this->debugLog(__FUNCTION__. "::usage_memory = ". memory_get_usage(), __FILE__, __CLASS__, __LINE__);
    }
    
    /**
     * write report files
     *
     * @param handle $fp
     * @param string $line
     * @return int write bytes
     */
    private function writeReport($fp, $line)
    {
        $result = fwrite($fp, $line);
        if($result === false){
            $ex = new AppException("mistake write file.");
            throw $ex;
        }
        return $result;
    }
}
?>
