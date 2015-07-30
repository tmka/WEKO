<?php
// --------------------------------------------------------------------
//
// $Id: Logreport.class.php 41730 2014-09-18 12:57:04Z yuko_nakao $
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
class Repository_Logreport extends RepositoryAction
{
    // component
    var $Session = null;
    
    // start date
    var $sy_log = null;
    var $sm_log = null;
    var $sd_log = 01;
    var $start_date = '';
    var $disp_start_date = '';
    // end date
    var $ey_log = "";
    var $em_log = "";
    var $ed_log = 31;
    var $end_date = '';
    var $disp_end_date = '';
    // for lang resource
    var $smartyAssign = null;
    // site license
    var $site_licrnse = array();
    // log exception
    var $log_exception = '';
    var $lang = "";
    
    // Add send mail for log report 2010/03/10 Y.Nakao --start--
    var $mail = null;
    var $mailMain = null;
    var $login_id = null;
    var $password = null;
    var $user_authority_id = "";
    var $authority_id = "";
    // Add send mail for log report 2010/03/10 Y.Nakao --end--
    
    function execute()
    {
        try {
            // -----------------------------------------------
            // init
            // -----------------------------------------------
            // start action
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
                $DetailMsg = null;
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );
                $this->failTrans();
                throw $exception;
            }
            // get language
            $this->lang = $this->Session->getParameter("_lang");
            // Add send mail for log report 2010/03/10 Y.Nakao --start--
            // get language resource
            $this->setLangResource();
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");

            // Fix bulk log file 2014/08/11 Y.Nakao --start--
            ini_set('max_execution_time', 2400);
            ini_set('memory_limit', '2048M');
            // Fix bulk log file 2014/08/11 Y.Nakao --end--
            
            // -----------------------------------------------
            // check login
            // -----------------------------------------------
            $user_id = $this->Session->getParameter("_user_id");
            if($user_id != "0"){
                // check auth
                $this->user_authority_id = $this->Session->getParameter("_user_auth_id");
                $this->authority_id = $this->getRoomAuthorityID($user_id);
            } else if($this->login_id != null && strlen($this->login_id) > 0 &&
                $this->password != null && strlen($this->password) > 0){
                $ret = $this->checkLogin($this->login_id, $this->password, $Result_List, $error_msg);
                if($ret === false){
                    echo $this->smartyAssign->getLang("repository_log_report_error");
                    return false;
                }
            } else {
                echo $this->smartyAssign->getLang("repository_log_report_error");
                return false;
            }
            if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
                // not admin
                echo $this->smartyAssign->getLang("repository_log_report_error");
                return false;
            }
            // Add send mail for log report 2010/03/10 Y.Nakao --end--
            
            // set start date
            if($this->sy_log == null || strlen($this->sy_log) == 0 || intval($this->sy_log) < 1 || 
                $this->sm_log == null || strlen($this->sm_log) == 0 || intval($this->sm_log) < 1 || 12 < intval($this->sm_log) ){

                $NOW_DATE = new Date();
                $query = "SELECT DATE_FORMAT(DATE_SUB('".$NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-".$NOW_DATE->getDay()."', INTERVAL 1 MONTH), '%Y') AS tmp_year,".
                         " DATE_FORMAT(DATE_SUB('".$NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-".$NOW_DATE->getDay()."', INTERVAL 1 MONTH), '%m') AS tmp_month;";
                $result = $this->Db->execute($query);
                if($result === false || count($result) != 1){
                    return false;
                }
                $this->sy_log = $result[0]['tmp_year'];
                $this->sm_log = sprintf("%02d",$result[0]['tmp_month']);
            }
            
            // send mail address check
            $users = array();
            if($this->mail == "true"){
                $this->getLogReportMailInfo($users);
                if(count($users) == 0){
                    echo $this->smartyAssign->getLang("repository_log_mail_text");
                    return false;
                }
            }
            // set start date
            $this->start_date = sprintf("%d-%02d-%02d",$this->sy_log, $this->sm_log,$this->sd_log);
            $this->disp_start_date = sprintf("%d-%02d",$this->sy_log, $this->sm_log);
            // set end date
            $this->ey_log = $this->sy_log;
            $this->em_log = $this->sm_log;
            $this->end_date = sprintf("%d-%02d-%02d",$this->ey_log, $this->em_log,$this->ed_log);
            $this->disp_end_date = sprintf("%d-%02d",$this->ey_log, $this->em_log);
            
            // Add log move 2010/05/21 Y.Nakao --start--
            // -----------------------------------------------
            // check logreport exist
            // -----------------------------------------------
            // set zip file name
            $zip_file = "logReport_".
                        str_replace("-", "", $this->disp_start_date).".zip";
            if(file_exists(WEBAPP_DIR."/logs/weko/logreport/".$zip_file)){
                // when log file exist, download that log file
                $repositoryDownload = new RepositoryDownload();
                $repositoryDownload->downloadFile(WEBAPP_DIR."/logs/weko/logreport/".$zip_file, $zip_file);
                exit();
            }
            // Add log move 2010/05/21 Y.Nakao --end--
            
            // add for compress files
            $output_files = array();
            //$date = date("YmdHis");
            $query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            $date = $result[0]['now_date'];
            $tmp_dir = WEBAPP_DIR."/uploads/repository/_".$date;
            mkdir( $tmp_dir, 0777 );
            // set log exclusion

            // Add log exclusion from user-agaent 2011/05/09 H.Ito --start--
            $this->log_exception = $this->createLogExclusion();
            // Add log exclusion from user-agaent 2011/05/09 H.Ito --end--
            
            // -----------------------------------------------
            // make log report
            //  operation_id =  1:アイテム登録(entry item)
            //                  2:ファイルダウンロード(download file)
            //                  3:詳細画面表示(detail view)
            //                  4:検索(search)
            //                  5:TOP画面(TOP)
            // -----------------------------------------------
            
            $BOM = pack('C*',0xEF,0xBB,0xBF);
            
            //$start_time = microtime(true); // 計測開始
            
            // サイトライセンス別アクセス数(access num as site lisence)
            $log_str = $this->makeAccessLogReport();
            $log_file = $tmp_dir . "/logReport_SiteAccess_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            
            // 課金ファイルダウンロード数(download file num)
            $fileLogStr = "";
            $priceLogStr = "";
            $this->makeFileDownloadLogReport($fileLogStr, $priceLogStr);
            // FileView
            $log_file = $tmp_dir . "/logReport_FileView_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$fileLogStr);
            fclose($log_report);
            array_push( $output_files, $log_file );
            
            // PayPerView
            $log_file = $tmp_dir . "/logReport_PayPerView_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$priceLogStr);
            fclose($log_report);
            array_push( $output_files, $log_file );
            
            // インデックごとの詳細画面アクセス数(detail view as index)
            $log_str = $this->makeIndexLogReport();
            $log_file = $tmp_dir . "/logReport_IndexAccess_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // check supple link 2010/03/16 Y.Nakao --start--
            if($this->checkSuppleWEKOlink(true)){
            // Add supple log report 2009/09/04 A.Suzuki --start--
            // サプリコンテンツの閲覧回数およびダウンロード回数(detail view and download file num as supple items)
            $log_str = $this->makeSuppleLogReport();
            $log_file = $tmp_dir . "/logReport_SuppleAccess_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add supple log report 2009/09/04 A.Suzuki --end--
            }
            // check supple link 2010/03/16 Y.Nakao --end--
            
            // Add access log per host 2010/04/01 Y.Nakao --start--
            $log_str = $this->makeHostLogReport();
            $log_file = $tmp_dir . "/logReport_HostAccess_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add access log per host 2010/04/01 Y.Nakao --end--
            
            
            /**
            // FLASH表示はなくなったのでログを出力しないように対応 2013/06/12 Y.Nakao
            // Add Flash view log 2011/03/01 Y.Nakao --start--
            $log_str = $this->makeFlashViewLogReport();
            $log_file = $tmp_dir . "/logReport_FlashView_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add Flash view log 2011/03/01 Y.Nakao --end--
            */
            
            
            // Add detail view log 2011/03/10 Y.Nakao --start--
            $log_str = $this->makeDetailViewLogReport();
            $log_file = $tmp_dir . "/logReport_DetailView_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add detail view log 2011/03/10 Y.Nakao --end--
            
            // Add user info 2011/07/25 Y.Nakao --start--
            $log_str = $this->makeUserLogReport();
            $log_file = $tmp_dir . "/logReport_UserAffiliate_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add user info 2011/07/25 Y.Nakao --end--

            // Add user download logreport 2012/10/29 A.jin --start--
            $log_str = $this->makeUsersDLLogReport();
            $log_file = $tmp_dir . "/logReport_FileViewPerUser_".
                        str_replace("-", "", $this->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $BOM.$log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add user download logreport 2012/10/29 A.jin --end--
            
            // -----------------------------------------------
            // compress zip file
            // -----------------------------------------------
            // set zip file name
            $zip_file = "logReport_".
                        str_replace("-", "", $this->disp_start_date).".zip";
            // compress zip file    
            File_Archive::extract(
                $output_files,
                File_Archive::toArchive($zip_file, File_Archive::toFiles( $tmp_dir."/" ))
            );

            // -----------------------------------------------
            // download zip file
            // -----------------------------------------------
            // Add send mail for log report 2010/03/10 Y.Nakao --start--
            if($this->mail == "true"){
                // send mail
                $this->sendMailLogReport($tmp_dir."/".$zip_file, $zip_file);
            } else {
            // ダウンロードアクション処理(download)
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $repositoryDownload = new RepositoryDownload();
            $repositoryDownload->downloadFile($tmp_dir."/".$zip_file, $zip_file);
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
            }
            // Add send mail for log report 2010/03/10 Y.Nakao --end--
            
            // delete tmp folder
            $this->removeDirectory($tmp_dir);
            
            // -----------------------------------------------
            // end action
            // -----------------------------------------------
            $result = $this->exitAction();
            if ( $result == false ){
                return "error";
            }
            
            exit();
            
        }
        catch ( RepositoryException $Exception) {
            $this->logFile(
                "SampleAction",
                "execute",
                $Exception->getCode(),
                $Exception->getMessage(),
                $Exception->getDetailMsg() );
            $this->exitAction();
            return "error";
        }
    }
    
    /**
     * サイトライセンス別アクセス数(access num as site lisence)
     *
     * @return string log report 
     */
    function makeAccessLogReport(){
        $log_data = array();
        // -----------------------------------------------
        // site license or not init
        // -----------------------------------------------
        // site license total
        $is_sitelicense = $this->smartyAssign->getLang("repository_log_is_sitelicense");
        $log_data[$is_sitelicense]['top'] = 0;
        $log_data[$is_sitelicense]['search'] = 0;
        $log_data[$is_sitelicense]['detail'] = 0;
        $log_data[$is_sitelicense]['download'] = 0;
        // not site license total
        $not_sitelicense = $this->smartyAssign->getLang("repository_log_not_sitelicense");
        $log_data[$not_sitelicense]['top'] = 0;
        $log_data[$not_sitelicense]['search'] = 0;
        $log_data[$not_sitelicense]['detail'] = 0;
        $log_data[$not_sitelicense]['download'] = 0;
        // -----------------------------------------------
        // get site license organization and init
        // -----------------------------------------------
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'site_license'; ";
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        if(strlen($result[0]['param_value']) > 0){
            $site_license = explode("|", $result[0]['param_value']);
            for($ii=0; $ii<count($site_license); $ii++){
                $param_site_license = explode(",", $site_license[$ii]);
                $organization = $param_site_license[0];
                // Bugfix Input scrutiny 2011/06/17 Y.Nakao --start--
                // decode explode delimiters.
                $organization = str_replace("&#124;", "|", $organization);
                $organization = str_replace("&#44;", ",", $organization);
                $organization = str_replace("&#46;", ".", $organization);
                // Bugfix Input scrutiny 2011/06/17 Y.Nakao --end--
                $log_data[$organization]['top'] = 0;
                $log_data[$organization]['search'] = 0;
                $log_data[$organization]['detail'] = 0;
                $log_data[$organization]['download'] = 0;
            }
        }
        
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
        // Get param table data : site_license_item_type_id
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'site_license_item_type_id'; ";
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        $siteLicenseItemTypeId = explode(",", trim($result[0]['param_value']));
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
        
        // -----------------------------------------------
        // get WEKO Top page access
        // -----------------------------------------------
        $query = "SELECT ip_address, site_license FROM ". DATABASE_PREFIX ."repository_log AS log ".
                " WHERE log.record_date >= '$this->start_date 00:00:00.000' ". 
                " AND log.record_date <= '$this->end_date 23:59:99.999' ".
                " AND log.operation_id='5' ".
                $this->log_exception;
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        // check site license
        for($ii=0; $ii<count($result); $ii++){
            if(isset($result[$ii]['site_license']))
            {
                // Exist site license info in log recode
                if($result[$ii]['site_license'] == 1)
                {
                    // Site license ON
                    // Check site license organization
                    if(!$this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                    {
                        // Not exist organization
                        $organization = $result[$ii]['ip_address'];
                    }
                    if(!isset($log_data[$organization]))
                    {
                        $log_data[$organization]['top'] = 0;
                        $log_data[$organization]['search'] = 0;
                        $log_data[$organization]['detail'] = 0;
                        $log_data[$organization]['download'] = 0;
                    }
                    $log_data[$organization]['top']++;
                    $log_data[$is_sitelicense]['top']++;
                }
                else
                {
                    // Site license OFF
                    $log_data[$not_sitelicense]['top']++;
                }
            }
            else
            {
                // Not exist site license info in log recode
                $organization = "";
                if($this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization)){
                    $log_data[$organization]['top']++;
                    $log_data[$is_sitelicense]['top']++;
                } else {
                    $log_data[$not_sitelicense]['top']++;
                }
            }
        }
        // -----------------------------------------------
        // get search result page access
        // -----------------------------------------------
        $query = "SELECT ip_address, site_license FROM ". DATABASE_PREFIX ."repository_log AS log ".
                " WHERE log.record_date >= '$this->start_date 00:00:00.000' ". 
                " AND log.record_date <= '$this->end_date 23:59:99.999' ".
                " AND log.operation_id='4' ".
                $this->log_exception;
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        // check site license
        for($ii=0; $ii<count($result); $ii++){
            if(isset($result[$ii]['site_license']))
            {
                // Exist site license info in log recode
                if($result[$ii]['site_license'] == 1)
                {
                    // Site license ON
                    // Check site license organization
                    if(!$this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                    {
                        // Not exist organization
                        $organization = $result[$ii]['ip_address'];
                    }
                    if(!isset($log_data[$organization]))
                    {
                        $log_data[$organization]['top'] = 0;
                        $log_data[$organization]['search'] = 0;
                        $log_data[$organization]['detail'] = 0;
                        $log_data[$organization]['download'] = 0;
                    }
                    $log_data[$organization]['search']++;
                    $log_data[$is_sitelicense]['search']++;
                }
                else
                {
                    // Site license OFF
                    $log_data[$not_sitelicense]['search']++;
                }
            }
            else
            {
                // Not exist site license info in log recode
                $organization = "";
                if($this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization)){
                    $log_data[$organization]['search']++;
                    $log_data[$is_sitelicense]['search']++;
                } else {
                    $log_data[$not_sitelicense]['search']++;
                }
            }
        }
        // -----------------------------------------------
        // get detail display page access
        // -----------------------------------------------
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
        $query = "SELECT ip_address, item_type_id, site_license ".
                 "FROM ". DATABASE_PREFIX ."repository_log AS log ".
                 "LEFT JOIN ".DATABASE_PREFIX."repository_item AS item ".
                 "ON log.item_id = item.item_id AND log.item_no = item.item_no ".
                 " WHERE log.record_date >= '$this->start_date 00:00:00.000' ".
                 " AND log.record_date <= '$this->end_date 23:59:99.999' ".
                 " AND log.operation_id='3' ".
                 $this->log_exception;
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        // check site license
        for($ii=0; $ii<count($result); $ii++){
            if(isset($result[$ii]['site_license']))
            {
                // Exist site license info in log recode
                if($result[$ii]['site_license'] == 1)
                {
                    // Site license ON
                    // Check site license organization
                    if(!$this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                    {
                        // Not exist organization
                        $organization = $result[$ii]['ip_address'];
                    }
                    if(!isset($log_data[$organization]))
                    {
                        $log_data[$organization]['top'] = 0;
                        $log_data[$organization]['search'] = 0;
                        $log_data[$organization]['detail'] = 0;
                        $log_data[$organization]['download'] = 0;
                    }
                    $log_data[$organization]['detail']++;
                    $log_data[$is_sitelicense]['detail']++;
                }
                else
                {
                    // Site license OFF
                    $log_data[$not_sitelicense]['detail']++;
                }
            }
            else
            {
                // Not exist site license info in log recode
                $organization = "";
                if($this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                {
                    // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
                    $matchFlag = false;
                    for($jj=0; $jj<count($siteLicenseItemTypeId); $jj++)
                    {
                        if($siteLicenseItemTypeId[$jj] == $result[$ii]['item_type_id'])
                        {
                            $matchFlag = true;
                            break;
                        }
                    }
                    if($matchFlag)
                    {
                        $log_data[$not_sitelicense]['detail']++;
                    }
                    else
                    {
                        $log_data[$organization]['detail']++;
                        $log_data[$is_sitelicense]['detail']++;
                    }
                    // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
                } else {
                    $log_data[$not_sitelicense]['detail']++;
                }
            }
        }
        // -----------------------------------------------
        // get download access
        // -----------------------------------------------
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
        // Modify for remove IE Continuation log K.Matsuo 2011/11/15 --start-- 
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --start--
        $query = "SELECT DISTINCT DATE_FORMAT( record_date, '%Y%m%d%H%i' ), ".
                 " ip_address, user_agent, log.item_id, log.item_no, attribute_id, file_no, ".
                 " user_id, item.item_type_id, site_license ".
                 "FROM ". DATABASE_PREFIX ."repository_log AS log ".
                 "LEFT JOIN ".DATABASE_PREFIX."repository_item AS item ".
                 "ON log.item_id = item.item_id AND log.item_no = item.item_no ".
                 "WHERE log.record_date >= '$this->start_date 00:00:00.000' ". 
                 "AND log.record_date <= '$this->end_date 23:59:99.999' ".
                 "AND log.operation_id='2' ".
                 $this->log_exception;
        // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --end--
        $result = $this->Db->execute($query);
        if($result === false){
            $str = $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        // check site license
        for($ii=0; $ii<count($result); $ii++){
            if(isset($result[$ii]['site_license']))
            {
                // Exist site license info in log recode
                if($result[$ii]['site_license'] == 1)
                {
                    // Site license ON
                    // Check site license organization
                    if(!$this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                    {
                        // Not exist organization
                        $organization = $result[$ii]['ip_address'];
                    }
                    if(!isset($log_data[$organization]))
                    {
                        $log_data[$organization]['top'] = 0;
                        $log_data[$organization]['search'] = 0;
                        $log_data[$organization]['detail'] = 0;
                        $log_data[$organization]['download'] = 0;
                    }
                    $log_data[$organization]['download']++;
                    $log_data[$is_sitelicense]['download']++;
                }
                else
                {
                    // Site license OFF
                    $log_data[$not_sitelicense]['download']++;
                }
            }
            else
            {
                // Not exist site license info in log recode
                $organization = "";
                if($this->checkSiteLicenseForLogReport($result[$ii]['ip_address'], $organization))
                {
                    // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
                    $matchFlag = false;
                    for($jj=0; $jj<count($siteLicenseItemTypeId); $jj++)
                    {
                        if($siteLicenseItemTypeId[$jj] == $result[$ii]['item_type_id'])
                        {
                            $matchFlag = true;
                            break;
                        }
                    }
                    if($matchFlag)
                    {
                        $log_data[$not_sitelicense]['download']++;
                    }
                    else
                    {
                        $log_data[$organization]['download']++;
                        $log_data[$is_sitelicense]['download']++;
                    }
                    // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
                } else {
                    $log_data[$not_sitelicense]['download']++;  
                }
            }
        }
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
        $str .= $is_sitelicense."\t";
        $str .= $log_data[$is_sitelicense]['top']."\t";
        $str .= $log_data[$is_sitelicense]['search']."\t";
        $str .= $log_data[$is_sitelicense]['detail']."\t";
        $str .= $log_data[$is_sitelicense]['download'];
        $str .= "\r\n";
        $str .= $not_sitelicense."\t";
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
     * サイトライセンス判定(check site license)
     *
     * @param string $access_ip check ip address
     * @param string $organization 
     *                  when $access_ip is site license, 
     *                  set $organization is site license organization.
     * @return string where wuery for site license
     */
    function checkSiteLicenseForLogReport($access_ip, &$organization){
        // get user ip address
        $ipaddress = explode(".", $access_ip);
        $ipaddress = sprintf("%03d", $ipaddress[0]).
                     sprintf("%03d", $ipaddress[1]).
                     sprintf("%03d", $ipaddress[2]).
                     sprintf("%03d", $ipaddress[3]);
        // get param table data : site_license
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'site_license'; ";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        }
        $site_license = explode("|", $result[0]['param_value']);
        
        for($ii=0; $ii<count($site_license); $ii++){
            $param_site_license = explode(",", $site_license[$ii]);
            $ipaddress_from = explode(",", $param_site_license[1]);
            if($param_site_license[2] != ""){
                // from to
                $ipaddress_to = explode(",", $param_site_license[2]);
                $ipaddress_from = explode(".", $ipaddress_from[0]);
                $ipaddress_to = explode(".", $ipaddress_to[0]);
                $from = sprintf("%03d", $ipaddress_from[0]).
                        sprintf("%03d", $ipaddress_from[1]).
                        sprintf("%03d", $ipaddress_from[2]).
                        sprintf("%03d", $ipaddress_from[3]);
                $to   = sprintf("%03d", $ipaddress_to[0]).
                        sprintf("%03d", $ipaddress_to[1]).
                        sprintf("%03d", $ipaddress_to[2]).
                        sprintf("%03d", $ipaddress_to[3]);  
                if( $from <= $ipaddress && $ipaddress <= $to ){
                    $organization = $param_site_license[0];
                    // Bugfix Input scrutiny 2011/06/17 Y.Nakao --start--
                    // decode explode delimiters.
                    $organization = str_replace("&#124;", "|", $organization);
                    $organization = str_replace("&#44;", ",", $organization);
                    $organization = str_replace("&#46;", ".", $organization);
                    // Bugfix Input scrutiny 2011/06/17 Y.Nakao --end--
                    return true;
                }
            } else {
                // same ip
                if($access_ip == $ipaddress_from[0]){
                    $organization = $param_site_license[0];
                    // Bugfix Input scrutiny 2011/06/17 Y.Nakao --start--
                    // decode explode delimiters.
                    $organization = str_replace("&#124;", "|", $organization);
                    $organization = str_replace("&#44;", ",", $organization);
                    $organization = str_replace("&#46;", ".", $organization);
                    // Bugfix Input scrutiny 2011/06/17 Y.Nakao --end--
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * インデックごとの詳細画面アクセス数(detail view as index)
     *
     * @return string log report 
     */
    function makeIndexLogReport(){
        $log_data = array();
        // -----------------------------------------------
        // get All detail access num
        // -----------------------------------------------
        $query = "SELECT ip_address FROM ". DATABASE_PREFIX ."repository_log AS log ".
                 " WHERE log.record_date BETWEEN '".$this->start_date." 00:00:00.000'".
                 " AND '".$this->end_date." 23:59:99.999'".
                 " AND log.operation_id='3' ".
                 $this->log_exception;
        $result = $this->Db->execute($query);
        if($result === false){
            $str .= $query."\r\n";
            $str .= $this->Db->ErrorMsg();
            return $str;
        }
        $total_access = count($result);
        // -----------------------------------------------
        // get all index's child item access num
        // -----------------------------------------------
        
        $index_data = array();
        $index_data = $this->getIndexTree();
        
        // -----------------------------------------------
        // make log report
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_index_report_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        //$str .= $this->smartyAssign->getLang("repository_log_index_attention")."\r\n";
        //$str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_search_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_access_num");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_index_detail_access_total")."\t";
        $str .= $total_access."\t";
        $str .= "\r\n";
//      for($ii=0; $ii<count($index_data); $ii++){
//          $str .= $index_data[$ii]['index_name']."\t";
//          $str .= $index_data[$ii]['detail_view']."\r\n";
//      }
        $this->outIndexTree($index_data, $index_data['0'], "", $str);
        return $str;
    }
    
    /**
     * get index tree
     *
     * @return unknown
     */
    function getIndexTree(){
        $index = array();
        // log report have closed indexs.
        $query = " SELECT idx.index_id, idx.index_name, idx.index_name_english, idx.parent_index_id, cnt.detail_view ".
                " FROM ".DATABASE_PREFIX."repository_index AS idx ".
                " LEFT JOIN ( ".
                "   SELECT pos.index_id, count(log.log_no) AS detail_view ".
                "   FROM ".DATABASE_PREFIX."repository_position_index AS pos, ".DATABASE_PREFIX."repository_log AS log ".
                "   WHERE log.record_date BETWEEN '".$this->start_date." 00:00:00.000'".
                "   AND '".$this->end_date." 23:59:99.999'".
                "   AND log.operation_id = '3' ".$this->log_exception.
                "   AND pos.is_delete = '0' ".
                "   AND pos.item_id = log.item_id AND pos.item_no = log.item_no ".
                "   GROUP BY pos.index_id ".
                " ) AS cnt ".
                " ON cnt.index_id = idx.index_id ".
                " WHERE idx.is_delete = '0' ".
                " ORDER BY show_order, index_id ";
        $result = $this->Db->execute($query);
        if($result === false){
            $this->error_msg = $this->Db->ErrorMsg();
            //アクション終了処理
            $result = $this->exitAction();   //トランザクションが成功していればCOMMITされる
            return false;
        }
        for($ii=0; $ii<count($result); $ii++){
            $node = array(
                        'id'=>$result[$ii]['index_id'],
                        'name'=>"",
                        'pid'=>$result[$ii]['parent_index_id'],
                        'detail_view'=>intval($result[$ii]['detail_view'])
                    );
            if($this->lang == "japanese"){
                $node['name'] = $result[$ii]['index_name'];
            } else {
                $node['name'] = $result[$ii]['index_name_english'];
            }
            if($node['detail_view'] == null || strlen($node['detail_view']) == 0 || !is_numeric($node['detail_view'])){
                $node['detail_view'] = 0;
            }
            
            if(!isset($index[$node['pid']])){
                $index[$node['pid']] = array();
            }
            array_push($index[$node['pid']], $node);
        }
        return $index;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $all_index
     * @param unknown_type $index
     * @param unknown_type $parent_name
     */
    function outIndexTree(&$all_index, $index, $parent_name, &$str){
        //$log_file = "IndexTree.txt";
        //$log_report = fopen($log_file, "a");
        foreach ($index as $key => $val){
            if(strlen($parent_name) > 0){
                $val['name'] = $parent_name."\\".$val['name'];
            }
            $str .= $val['name']."\t".$val['detail_view']."\r\n";
            if(is_array($all_index[$val['id']]) && count($all_index[$val['id']]) > 0){
                $this->outIndexTree($all_index, $all_index[$val['id']], $val['name'], $str);
                unset($all_index[$val['id']]);
            }
        }
        //fclose($log_report);
    }
    
    /**
     * 通常ファイルおよび課金ファイルのダウンロード数を集計(make file view download report / pay per view download report)
     * 
     * @param string $fileReport
     * @param string $priceReport
     * @return string log report 
     */
    function makeFileDownloadLogReport(&$fileReport, &$priceReport)
    {
        $fileReport = "";
        $priceReport = "";
        
        // -----------------------------------------------
        // Get file download log
        // -----------------------------------------------
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --start--
        $query = "SELECT DISTINCT DATE_FORMAT(".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE.", '%Y-%m-%d %H:%i' ) AS ".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_IP_ADDRESS.", ".RepositoryConst::DBCOL_REPOSITORY_LOG_USER_AGENT.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_ID.", ".RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_NO.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_ATTRIBUTE_ID.", ".RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_NO.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS.", ".RepositoryConst::DBCOL_REPOSITORY_LOG_SITE_LICENSE.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_INPUT_TYPE.", ".RepositoryConst::DBCOL_REPOSITORY_LOG_LOGIN_STATUS.", ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_GROUP_ID." ".
                 "FROM ". DATABASE_PREFIX .RepositoryConst::DBTABLE_REPOSITORY_LOG." AS log ".
                 "WHERE log.".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE." >= '$this->start_date 00:00:00.000' ". 
                 "AND log.".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE." <= '$this->end_date 23:59:99.999' ".
                 "AND log.".RepositoryConst::DBCOL_REPOSITORY_LOG_OPERATION_ID." = '".RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE."' ".
                 $this->log_exception." ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_ID." ASC, ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_ATTRIBUTE_ID." ASC, ".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_NO." ASC";
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --end--
        $log = $this->Db->execute($query);
        if($log === false)
        {
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // Get data for make log
        // -----------------------------------------------
        $priceLog = array();
        $priceOpenLog = array();
        $fileLog = array();
        $fileOpenLog = array();
        $result = $this->makeDownloadInfo($log, $priceLog, $priceOpenLog, $fileLog, $fileOpenLog);
        if($result === false)
        {
            return false;
        }
        // -----------------------------------------------
        // Get data for make log
        // -----------------------------------------------
        $fileReport = $this->makeFileDownloadLogReportStr($fileLog, $fileOpenLog);
        $priceReport = $this->makeFilePriceDownloadLogReportStr($priceLog, $priceOpenLog);
        
        return true;
    }
    
    /**
     * Create file download info
     *
     * @param array $log
     * @param array $priceLog
     * @param array $priceOpenLog
     * @param array $fileLog
     * @param array $fileOpenLog
     * @return bool
     */
    function makeDownloadInfo($log, &$priceLog, &$priceOpenLog, &$fileLog, &$fileOpenLog)
    {
        for($ii=0; $ii<count($log); $ii++)
        {
            $fileStatus = "";
            $siteLicense = "";
            $inputType = "";
            $loginStatus = "";
            $groupId = "";
            $result = $this->checkFileDownloadStatus($log[$ii], $fileName, $fileStatus, $siteLicense, $inputType, $loginStatus, $groupId);
            if($result === false)
            {
                continue;
            }
            
            $key =  $log[$ii]['item_id'].'_'.$log[$ii]['item_no'].'_'.
                    $log[$ii]['attribute_id'].'_'.$log[$ii]['file_no'];
            
            if($inputType == RepositoryConst::LOG_INPUT_TYPE_FILE)
            {
                if($fileStatus == RepositoryConst::LOG_FILE_STATUS_OPEN)
                {
                    $this->setFileLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, $fileOpenLog);
                }
                else if($fileStatus == RepositoryConst::LOG_FILE_STATUS_CLOSE)
                {
                    $this->setFileLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, $fileLog);
                }
            }
            else if($inputType == RepositoryConst::LOG_INPUT_TYPE_FILE_PRICE)
            {
                if($fileStatus == RepositoryConst::LOG_FILE_STATUS_OPEN)
                {
                    $this->setFilePriceLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, $groupId, $priceOpenLog);
                }
                else if($fileStatus == RepositoryConst::LOG_FILE_STATUS_CLOSE)
                {
                    $this->setFilePriceLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, $groupId, $priceLog);
                }
            }
        }
        return true;
    }
    
    /**
     * 
     *
     * @param string $key item_id_item_no_attribute_id_file_no
     * @return string index name
     */
    function getIndexName($key, $index_id){
        if($key != ""){
            $index_name = '';
            $key = explode("_", $key);
            $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_position_index ".
                    " WHERE item_id = '$key[0]' ".
                    " AND item_no = '$key[1]'; ";
            $result = $this->Db->execute($query);
            if($result === false){
                return "";
            }
            for($ii=0; $ii<count($result); $ii++){
                if($ii != 0){
                    $index_name .= " | ";
                }
                $index_name .= $this->getIndexName("", $result[$ii]['index_id']);
            }
        } else {
            $index_name = ''; 
            // get this index info
            $query = "SELECT index_name, index_name_english, parent_index_id ".
                    " FROM ". DATABASE_PREFIX ."repository_index ".
                    " WHERE index_id = '$index_id' ";
            $result = $this->Db->execute($query);
            if($result === false){
                return "";
            }
            if(count($result) == 0){
                return "";
            }
            if($this->Session->getParameter("_lang") == "japanese"){
                $index_name = $result[0]['index_name'];
            } else {
                $index_name = $result[0]['index_name_english'];
            }
            // search parent index name
            $p_name = $this->getIndexName("", $result[0]['parent_index_id']);
            if($p_name != ""){
                $index_name = $p_name."\\".$index_name;
            }
        }
        
        return $index_name;
    }
    
    /**
     * download type
     *
     * @param unknown_type $price
     * @param unknown_type $user_id
     * @return unknown
     */
    function getDownloadType($price, $user_id){
        $room_id = '0';
        $room_name = '';
        ///// get groupID and price /////
        $room_price = explode("|",$price);
        ///// ユーザが入っているroom_idを取得 /////
        $query = "SELECT room_id FROM ". DATABASE_PREFIX ."pages_users_link ".
                 "WHERE user_id = ?; ";
        $params = null;
        $params[] = $user_id;
        // SELECT実行
        $user_group = $this->Db->execute($query, $params);
        if($user_group === false){
            return false;
        }
        // Add Nonmember
        // search file price for setting download type
        for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
            $price = explode(",", $room_price[$price_Cnt]);
            // There is a pair of room_id and the price. 
            if($price!=null && count($price)==2) {
                // It is judged whether it is user's belonging group.
                for($user_group_cnt=0;$user_group_cnt<count($user_group);$user_group_cnt++){
                    if($price[0] == $user_group[$user_group_cnt]["room_id"]){
                        // When the price is set to the belonging group
                        if($file_price==""){
                            // The price is maintained at the unsetting. 
                            $file_price = $price[1];
                            $room_id = $user_group[$user_group_cnt]["room_id"];
                        } else if(intval($file_price) > intval($price[1])){
                            // It downloads it by the lowest price. 
                            $file_price = $price[1];
                            $room_id = $user_group[$user_group_cnt]["room_id"];
                        }
                    }
                }
            }
        }
        return $room_id;
    }
    
    // Add supple log report 2009/09/04 A.Suzuki --start--
    /**
     * サプリコンテンツの閲覧回数およびダウンロード回数(detail view and download file num as supple items)
     *
     * @return string log report 
     */
    function makeSuppleLogReport(){
        // サプリテーブルの情報を取得
        $query = "SELECT * FROM ".DATABASE_PREFIX."repository_supple ".
                 "WHERE is_delete = 0;";
        $result = $this->Db->execute($query);
        
        // サプリコンテンツのサプリWEKOアイテムIDを連結する
        $item_ids = "";
        foreach($result as $val){
            if($item_ids != ""){
                $item_ids .= ",";
            }
            $item_ids .= $val['supple_weko_item_id'];
        }
        
        // request URL send for supple weko
        // パラメタテーブルからサプリWEKOのアドレスを取得する
        $query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = 'supple_weko_url';";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        }
        if($result[0]['param_value'] == ""){
            return false;
        } else {
            $send_param = $result[0]['param_value'];
        }
        
        $send_param .= "/?action=repository_opensearch&item_ids=".$item_ids."&log_term=".$this->disp_start_date."&format=rss";
        
        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        // Modfy proxy 2011/12/06 Y.Nakao --start--
        $proxy = $this->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        // Modfy proxy 2011/12/06 Y.Nakao --end--
        $http = new HTTP_Request($send_param, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        // get response
        $response_xml = $charge_body;
        
        /////////////////////////////
        // parse response XML
        /////////////////////////////
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                return false;
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            return false;
        }
        
        /////////////////////////////
        // get XML data
        /////////////////////////////
        $item_flag = false;
        $supple_data = array();
        foreach($vals as $val){
            if($val['tag'] == "ITEM"){
                    if($val['type'] == "open"){
                        $item_flag = true;
                        $item_data = array();
                    }
                    if($item_flag == true && $val['type'] == "close"){
                        $item_flag = false;
                        if($item_data["supple_weko_item_id"] != "" && $item_data["supple_weko_item_id"] != null){
                            array_push($supple_data, $item_data);
                        }
                    }
            }
            if($item_flag){
                switch($val['tag']){
                    case "DC:IDENTIFIER":   // サプリアイテム:アイテムID
                        if(ereg("^file_id:", $val['value']) == 0){
                            $item_data["supple_weko_item_id"] = $val['value'];
                        }
                        break;
                    case "WEKOLOG:VIEW":    // サプリアイテム:閲覧回数
                        $item_data["log_view"] = $val['value'];
                        break;
                    case "WEKOLOG:DOWNLOAD":    // サプリアイテム:ダウンロード回数
                        $item_data["log_download"] = $val['value'];
                        break;
                    default :
                        break;
                }
            }
        }
        
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
     * 
     * 再帰的にインデックスツリーを作成
     *  配下のアイテムのサプリコンテンツ閲覧回数とダウンロード回数を出す
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
                " WHERE is_delete = '0' ".
                " AND parent_index_id = '$pid_id' ".
                " ORDER BY show_order ";
        $children = $this->Db->execute($query);
        if($children === false){
            //array_push($index_array, "MySQL ERROR : ".$this->Db->ErrorMsg());
            return false;
        }
        
        // インデックス直下にあるサプリアイテムを検索
        if($pid_id != "0"){
            $supple_result = array();
            $query = "SELECT item.title, item.title_english, supple.supple_title, supple.supple_title_en, supple.supple_weko_item_id ".
                     "FROM ".DATABASE_PREFIX."repository_index AS idx ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_position_index AS p_idx ON idx.index_id = p_idx.index_id ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_item AS item ON item.item_id = p_idx.item_id AND item.item_no = p_idx.item_no ".
                     "LEFT JOIN ".DATABASE_PREFIX."repository_supple AS supple ON item.item_id = supple.item_id AND item.item_no = supple.item_no ".
                     "WHERE idx.index_id = '$pid_id' ".
                     "AND idx.is_delete = 0 ".
                     "AND p_idx.is_delete = 0 ".
                     "AND item.is_delete = 0 ".
                     "AND supple.is_delete = 0 ".
                     "ORDER BY idx.show_order ASC, item.item_id ASC, supple.supple_no ASC;";
            $supple_result = $this->Db->execute($query);
            if($supple_result === false){
                //array_push($index_array, "MySQL ERROR : ".$this->Db->ErrorMsg());
                return false;
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
    // Add supple log report 2009/09/04 A.Suzuki --end--
    
    // Add send mail for log report 2010/03/10 Y.Nakao --start--
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
            return false;
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
    // Add send mail for log report 2010/03/10 Y.Nakao --end--
    
    // Add access log per host 2010/04/01 Y.Nakao --start--
    /**
     * host access log report
     *
     * @return string log report text
     */
    function makeHostLogReport(){
        // -----------------------------------------------
        // init
        // -----------------------------------------------
        $str = "";
        $host_cnt = array();
        
        // -----------------------------------------------
        // get access log report per host
        // count top page access
        // -----------------------------------------------
        $query = "SELECT host, ip_address, operation_id, count( * ) AS cnt ". 
                " FROM ".DATABASE_PREFIX."repository_log AS log ".
                " WHERE log.record_date >= '$this->start_date 00:00:00.000' ". 
                " AND log.record_date <= '$this->end_date 23:59:99.999' ".
                " AND operation_id = '5' ".
                $this->log_exception.
                " GROUP BY operation_id, ip_address ".
                " ORDER BY ip_address; ";
        $result = $this->Db->execute($query);
        if($result === false){
            return 'error';
        }
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
    // Add access log per host 2010/04/01 Y.Nakao --end--
    
    // Add entry Flash log Y.Nakao 2011/03/01 --start--
    /**
     * Flash閲覧数を集計(make Flash view report)
     * 
     * @return string log report
     */
    function makeFlashViewLogReport(){
        // -----------------------------------------------
        // get pay file download log
        // -----------------------------------------------
        $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_log AS log ".
                " WHERE log.record_date BETWEEN '$this->start_date 00:00:00.000' ". 
                " AND '$this->end_date 23:59:99.999' ".
                " AND log.operation_id='6' ".
                $this->log_exception;
        $log = $this->Db->execute($query);
        if($log === false){
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // get group list(room_id and room_name list)
        // -----------------------------------------------
        $result = $this->getGroupList($all_group, $error_msg);
        if($result === false){
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // get data for make log
        // -----------------------------------------------
        $viewLog = array();
        $result = $this->makeFlashViewInfo($log, $viewLog);
        if($result === false){
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // make file price download log
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_Fashview_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_Fashview_title");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_filename")."\t";
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
            $str .= $value['file_name']."\t";
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
     * Enter description here...
     *
     * @param array $log
     * @param array viewLog
     * @return boolean true/false
     */
    function makeFlashViewInfo($log, &$viewLog){
        for($ii=0; $ii<count($log); $ii++){
            $key =  $log[$ii]['item_id'].'_'.$log[$ii]['item_no'].'_'.
                    $log[$ii]['attribute_id'].'_'.$log[$ii]['file_no'];
            // ファイル情報取得(get file info)
            $query = "SELECT file_name, pub_date ".
                    " FROM ". DATABASE_PREFIX ."repository_file ".
                    " WHERE item_id = '".$log[$ii]['item_id']."' AND ".
                    " item_no = '".$log[$ii]['item_no']."' AND ".
                    " attribute_id = '".$log[$ii]['attribute_id']."' AND ".
                    " file_no = '".$log[$ii]['file_no']."' ";
            $file = $this->Db->execute($query);
            if($file === false){
                return false;
            }
            if( !isset($viewLog[$key]) ){
                $viewLog[$key]['file_name'] = $file[0]['file_name'];
                $viewLog[$key]['index_name'] = $this->getIndexName($key);
                $viewLog[$key]['total'] = 0;// トータル(total)
                $viewLog[$key]['not_login'] = 0;// 個人(not login)
                $viewLog[$key]['group'] = array();// グループ(group(room))
                $viewLog[$key]['group']['0'] = 0;// 非会員(login user(not affiliate))
            }
            // 課金かどうかチェック(check input type is "file_price")
            $query = "SELECT input_type ".
                    " FROM ". DATABASE_PREFIX ."repository_item as item, ".
                    DATABASE_PREFIX ."repository_item_attr_type as attr_type ".
                    " WHERE item.item_type_id = attr_type.item_type_id ".
                    " AND item.item_id = '".$log[$ii]['item_id']."' ".
                    " AND item.item_no = '".$log[$ii]['item_no']."' ".
                    " AND attr_type.attribute_id = '".$log[$ii]['attribute_id']."' ";
            $result = $this->Db->execute($query);
            if($result === false){
                return false;
            }
            // 個人ダウンロード(未ログインダウンロード)かどうか判定(check not login download)
            $viewLog[$key]['total']++;
            if($log[$ii]['user_id'] == "0"){
                $viewLog[$key]['not_login']++;
            } else if($result[0]['input_type'] == "file"){
                ///// ユーザが入っているroom_idを取得(get user group list) /////
                $user_group = $this->getUserGroupIds($log[$ii]['user_id']);
                // select first room_id
                $group = $user_group[0]['room_id'];
                if(isset($viewLog[$key]['group'][$group])){
                    $viewLog[$key]['group'][$group]++;
                } else {
                    $viewLog[$key]['group'][$group] = 1;
                }
            } else if($result[0]['input_type'] == "file_price"){
                // 課金ファイル情報取得(get file price info)
                $query = "SELECT price ".
                        " FROM ". DATABASE_PREFIX ."repository_file_price ".
                        " WHERE item_id = '".$log[$ii]['item_id']."' AND ".
                        " item_no = '".$log[$ii]['item_no']."' AND ".
                        " attribute_id = '".$log[$ii]['attribute_id']."' AND ".
                        " file_no = '".$log[$ii]['file_no']."' ";
                $price = $this->Db->execute($query);
                if($price === false){
                    return false;
                }
                if(count($price) == 1){
                    $group = $this->getFlashViewUserGroup($price[0]['price'], $log[$ii]['user_id']);
                    if(isset($viewLog[$key]['group'][$group])){
                        $viewLog[$key]['group'][$group]++;
                    } else {
                        $viewLog[$key]['group'][$group] = 1;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * ユーザが入っているroom_idを取得(get user group list)
     * 
     * @param string user_id
     * @return array
     *
     */
    function getUserGroupIds($user_id){
        $query = "SELECT DISTINCT links.room_id ".
                " FROM ".DATABASE_PREFIX."pages_users_link AS links, ".
                       DATABASE_PREFIX."pages AS pages ".
                " WHERE links.user_id = ? ".
                " AND pages.private_flag = ? ".
                " AND pages.space_type = ? ".
                " AND NOT pages.thread_num = ? ".
                " AND pages.room_id = pages.page_id ".
                " AND links.room_id = pages.room_id; ";
        $params = null;
        $params[] = $user_id;
        $params[] = 0;
        $params[] = _SPACE_TYPE_GROUP;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return array();
        }
        return $result;
    }
    
    /**
     * Enter description here...
     *
     * @param string $price
     * @param string $user_id
     * @return int room_id(group)
     */
    function getFlashViewUserGroup($price, $user_id){
        $room_id = '0';
        $room_name = '';
        ///// get groupID and price /////
        $room_price = explode("|",$price);
        ///// ユーザが入っているroom_idを取得(get user group list) /////
        $user_group = $this->getUserGroupIds($user_id);
        // Add Nonmember
        // search file price for setting download type
        for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
            $price = explode(",", $room_price[$price_Cnt]);
            // There is a pair of room_id and the price. 
            if($price!=null && count($price)==2) {
                // It is judged whether it is user's belonging group.
                for($user_group_cnt=0;$user_group_cnt<count($user_group);$user_group_cnt++){
                    if($price[0] == $user_group[$user_group_cnt]["room_id"]){
                        // When the price is set to the belonging group
                        if($file_price==""){
                            // The price is maintained at the unsetting. 
                            $file_price = $price[1];
                            $room_id = $user_group[$user_group_cnt]["room_id"];
                        } else if(intval($file_price) > intval($price[1])){
                            // It downloads it by the lowest price. 
                            $file_price = $price[1];
                            $room_id = $user_group[$user_group_cnt]["room_id"];
                        }
                    }
                }
            }
        }
        
        if($room_id == "0"){
            // When donwload type 'nomembe', user any affiliate group.
            if(count($user_group) > 0){
                return $user_group[0]['room_id'];
            }
        }
        
        return $room_id;
    }
    // Add entry Flash log Y.Nakao 2011/03/01 --end--
    
    
    // Add detail view log 2011/03/10 Y.Nakao --start--
    /**
     * make detail view log 
     *
     */
    private function makeDetailViewLogReport(){
        // -----------------------------------------------
        // get detaile view log
        // -----------------------------------------------
        $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_log AS log ".
                " WHERE log.record_date BETWEEN '$this->start_date 00:00:00.000' ". 
                " AND '$this->end_date 23:59:99.999' ".
                " AND log.operation_id='3' ".
                $this->log_exception;
        $log = $this->Db->execute($query);
        if($log === false){
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // get group list(room_id and room_name list)
        // -----------------------------------------------
        $result = $this->getGroupList($all_group, $error_msg);
        if($result === false){
            return $this->Db->ErrorMsg();
        }
        // -----------------------------------------------
        // get data for make log
        // -----------------------------------------------
        $viewLog = array();
        $result = $this->makeDetailViewInfo($log, $viewLog);
        if($result === false){
            return $this->Db->ErrorMsg();
        }
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
     * Enter description here...
     *
     * @param unknown_type $log
     * @param unknown_type $viewLog
     */
    private function makeDetailViewInfo($log, &$viewLog){
        for($ii=0; $ii<count($log); $ii++){
            $key =  $log[$ii]['item_id'].'_'.$log[$ii]['item_no'];
            // ファイル情報取得(get file info)
            $query = "SELECT title, title_english ".
                    " FROM ". DATABASE_PREFIX ."repository_item ".
                    " WHERE item_id = '".$log[$ii]['item_id']."' AND ".
                    " item_no = '".$log[$ii]['item_no']."' ";
            $item = $this->Db->execute($query);
            if($item === false){
                return false;
            }
            if( !isset($viewLog[$key]) ){
                $viewLog[$key]['title'] = $item[0]['title'];
                $viewLog[$key]['title_en'] = $item[0]['title_english'];
                $viewLog[$key]['index_name'] = $this->getIndexName($key);
                $viewLog[$key]['total'] = 0;// トータル(total)
                $viewLog[$key]['not_login'] = 0;// 個人(not login)
                $viewLog[$key]['group'] = array();// グループ(group(room))
                $viewLog[$key]['group']['0'] = 0;// 非会員(login user(not affiliate))
            }
            // 個人ダウンロード(未ログインダウンロード)かどうか判定(check not login download)
            $viewLog[$key]['total']++;
            if($log[$ii]['user_id'] == "0"){
                $viewLog[$key]['not_login']++;
            } else {
                ///// ユーザが入っているroom_idを取得(get user group list) /////
                $user_group = $this->getUserGroupIds($log[$ii]['user_id']);
                // select first room_id
                $group = $user_group[0]['room_id'];
                if(isset($viewLog[$key]['group'][$group])){
                    $viewLog[$key]['group'][$group]++;
                } else {
                    $viewLog[$key]['group'][$group] = 1;
                }
            }
        }
        return true;
    }
    
    // Add user info 2011/07/25 Y.Nakao --start--
    /**
     * 権限別ユーザ数/研究会別ユーザ数を取得する
     *
     * @return string logReport by user text
     */
    function makeUserLogReport(){
        // -----------------------------------------------
        // init
        // -----------------------------------------------
        $str = "";
        
        // -----------------------------------------------
        // get user per BASE_AUTHOHRITY
        // -----------------------------------------------
        $query = "SELECT auth.role_authority_name, count( users.user_id ) cnt ".
                " FROM ".DATABASE_PREFIX."authorities AS auth ".
                " LEFT JOIN ".DATABASE_PREFIX."users AS users ON users.role_authority_id = auth.role_authority_id ".
                " GROUP BY auth.role_authority_id; ";
        $userAuth = $this->Db->execute($query);
        if($userAuth === false){
            return 'error';
        }
        
        $result = $this->getGroupList($all_group, $error_msg);
        if($result === false){
            return 'error';
        }
        // -----------------------------------------------
        // get user per room
        // -----------------------------------------------
        $query = "SELECT links.room_id, count(users.user_id) AS cnt ".
                " FROM ".DATABASE_PREFIX."users AS users, ".DATABASE_PREFIX."pages_users_link AS links ".
                " WHERE users.user_id=links.user_id ".
                " GROUP BY room_id ";
        $userRoom = $this->Db->execute($query);
        if($userRoom === false){
            return 'error';
        }
        
        for($ii=0; $ii<count($all_group); $ii++){
            $all_group[$ii]['cnt'] = 0;
            for($jj=0; $jj<count($userRoom); $jj++){
                if($all_group[$ii]['room_id'] == $userRoom[$jj]['room_id']){
                    $all_group[$ii]['cnt'] = $userRoom[$jj]['cnt'];
                }
            }
            if($jj == count($userRoom)){
                unset($userRoom[$jj]);
                $userRoom = array_values($userRoom);
            }
        }
        
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
            if(strlen(constant($userAuth[$ii]['role_authority_name'])) > 0){
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
    // Add user info 2011/07/25 Y.Nakao --end--
    
    
    // Add UsersDownloadsNum 2012/11/01 A.jin --start--
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
        
        // ---------------------------------------------
        // get data
        // ---------------------------------------------
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --start--
        $query = "SELECT USERS.user_id, USERS.login_id, USERS.handle , AUTH.role_authority_name , IFNULL(LOGCOUNT.filecount, 0) AS DLCount ".
                 "FROM ".DATABASE_PREFIX."users AS USERS ".
                 "LEFT JOIN ".DATABASE_PREFIX."authorities AS AUTH".
                 " ON USERS.role_authority_id=AUTH.role_authority_id ".
                 "LEFT JOIN (".
                 " SELECT USERLOG.user_id, COUNT(USERLOG.user_id) AS filecount".
                 " FROM (".
                 " SELECT DISTINCT DATE_FORMAT(record_date, '%Y%m%d%H%i'), user_id, ip_address, user_agent, item_id, item_no, attribute_id, file_no".
                 " FROM ".DATABASE_PREFIX."repository_log AS log".
                 " WHERE log.record_date BETWEEN '$this->start_date 00:00:00.000'".
                 " AND '$this->end_date 23:59:99.999'".
                 " AND log.operation_id=2".
                 $this->log_exception.
                 " ) AS USERLOG".
                 " GROUP BY USERLOG.user_id )".
                 " AS LOGCOUNT".
                 " ON USERS.user_id=LOGCOUNT.user_id;";
        // Fix WEKO-2014-003 / 同一ユーザーのアクセスかを判定する条件を IPアドレス、ユーザーエージェント、ユーザーIDが一致していること に修正した 2014/06/11 Y.Nakao --end--
        $result = $this->Db->execute($query);
        if($result === false){
            return $this->Db->ErrorMsg();
        }
        
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
            $user_id = $result[$ii]['user_id'];
            $login_id = $result[$ii]['login_id'];
            $handle = $result[$ii]['handle'];
            $name = "";
            
            // 会員氏名取得
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX."users_items_link ".
                     "WHERE user_id = ?".
                     "AND item_id = ?;";
            $params = array();
            $params[] = $user_id;   // user_id
            $params[] = 4;          // item_id = 4 : 会員氏名
            $ret = $this->Db->execute($query, $params);
            if($ret === false){
                return $this->Db->ErrorMsg();
            }
            if(count($ret)>0 && isset($ret[0]["content"]))
            {
                $name = str_replace("\t", " ", $ret[0]["content"]);
            }
            
            //Add ルーム権限取得処理 2012/12/05 A.Jin --start--
            $room_authority_name = "";
            //4以上だったら _AUTH_CHIEF_NAME
            //3 _AUTH_MODERATE_NAME
            //2 _AUTH_GENERAL_NAME
            //1 _AUTH_GUEST_NAME
            //それ以外 _AUTH_GUEST_NAME
            $auth_id = $this->getRoomAuthorityID($user_id);
            if($auth_id >= 4){
                $room_authority_name = _AUTH_CHIEF_NAME;
            } else if($auth_id == 3){
                $room_authority_name = _AUTH_MODERATE_NAME;
            } else if($auth_id == 2){
                $room_authority_name = _AUTH_GENERAL_NAME;
            } else if($auth_id == 1){
                $room_authority_name = _AUTH_GUEST_NAME;
            } else {
                $room_authority_name = _AUTH_GUEST_NAME;
            }
            //Add ルーム権限取得処理 2012/12/05 A.Jin --end--
            
            $base_authority_name = constant($result[$ii]['role_authority_name']);
            if($base_authority_name == ""){
                $base_authority_name = $result[$ii]['role_authority_name'];
            }
            $group_name_list = $this->getUserGroupNameList($user_id);
            $dl_count = $result[$ii]['DLCount'];
            
            // ---------------------------------------------
            // create output a row text
            // ---------------------------------------------
            $str .= $login_id."\t";
            $str .= $handle."\t";
            $str .= $name."\t";
            $str .= $base_authority_name.'/'.$room_authority_name."\t";
            $str .= $group_name_list."\t";
            $str .= strval($dl_count)."\r\n";
        }
        
        return $str;
    }
    // Add UsersDownloadsNum 2012/11/1 A.jin --end--

    // Add UserGroupNameList 2012/11/01 A.jin --start--
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
    // Add UserGroupNameList 2012/11/1 A.jin --end--
    
    // Add file download status to log 2012/11/15 A.Suzuki --start--
    /**
     * Check file download status and file input type
     *
     * @param array $logRecord 'repository_log' table record
     * @param int $fileStatus 0:unknown / 1: public / -1: private
     * @param int $inputType 0: file / 1: file_price
     * @return bool true: success / false: failed
     */
    private function checkFileDownloadStatus($logRecord, &$fileName, &$fileStatus, &$siteLicense, &$inputType, &$loginStatus, &$groupId)
    {
        // Init
        $fileName = "";
        $fileStatus = RepositoryConst::LOG_FILE_STATUS_UNKNOWN;
        $siteLicense = RepositoryConst::LOG_SITE_LICENSE_OFF;
        $inputType = null;
        $loginStatus = null;
        $groupId = null;
        
        // Check params
        if(!is_array($logRecord))
        {
            return false;
        }
        
        $itemId = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_ID];
        $itemNo = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_NO];
        $attributeId = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_ATTRIBUTE_ID];
        $fileNo = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_NO];
        
        if(!isset($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS])
            || $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS] == RepositoryConst::LOG_FILE_STATUS_UNKNOWN)
        {
            // File status is not set or 'unknown'
            // Check input type is "file" or "file_price"
            $key =  $itemId.'_'.$itemNo.'_'.$attributeId.'_'.$fileNo;
            $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." as item, ".
                             DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." as attr_type ".
                     "WHERE item.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_TYPE_ID." = attr_type.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." ".
                     "AND item.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = '".$itemId."' ".
                     "AND item.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = '".$itemNo."' ".
                     "AND attr_type.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." = '".$attributeId."' ";
            $result = $this->Db->execute($query);
            if($result === false)
            {
                return false;
            }
            
            // Set input type
            if($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_FILE)
            {
                $inputType = RepositoryConst::LOG_INPUT_TYPE_FILE;
            }
            else if($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE)
            {
                $inputType = RepositoryConst::LOG_INPUT_TYPE_FILE_PRICE;
            }
            else
            {
                // Illegal input type
                return false;
            }
            
            // Check site license
            $organization = "";
            if($this->checkSiteLicenseForLogReport($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_IP_ADDRESS], $organization))
            {
                // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --start--
                // Get item_type_id
                $query = "SELECT item_type_id ".
                         "FROM ".DATABASE_PREFIX."repository_item ".
                         "WHERE item_id = ? ".
                         "AND item_no = ? ".
                         "AND is_delete = 0;";
                $params = array();
                $params[] = $itemId;
                $params[] = $itemNo;
                $result = $this->Db->execute($query, $params);
                if($result === false)
                {
                    return false;
                }
                $itemTypeId = $result[0]['item_type_id'];
                
                // Get param table data : site_license_item_type_id
                $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                         "WHERE param_name = 'site_license_item_type_id'; ";
                $result = $this->Db->execute($query);
                if($result === false)
                {
                    return false;
                }
                $siteLicenseItemTypeIdArray = explode(",", trim($result[0]['param_value']));
                
                $matchFlag = false;
                for($ii=0; $ii<count($siteLicenseItemTypeIdArray); $ii++)
                {
                    if($siteLicenseItemTypeIdArray[$ii] == $itemTypeId)
                    {
                        $matchFlag = true;
                        break;
                    }
                }
                if(!$matchFlag)
                {
                    $siteLicense = RepositoryConst::LOG_SITE_LICENSE_ON;
                }
                // Add exclude item_type_id for site license 2013/07/01 A.Suzuki --end--
            }
            
            // Get file info
            $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME.", ".
                               RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE.", ".
                               RepositoryConst::DBCOL_COMMON_INS_USER_ID." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = '".$itemId."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = '".$itemNo."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = '".$attributeId."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = '".$fileNo."' ";
            $file = $this->Db->execute($query);
            if($file === false)
            {
                return false;
            }
            
            // Set file name
            $fileName = $file[0][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME];
            
            // Check open access or pay per view
            $pub_date = explode(" ", $file[0][RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE]);
            $pub_date = explode("-", $pub_date[0]);
            $pub_date = sprintf("%04d%02d%02d", $pub_date[0],$pub_date[1],$pub_date[2]);
            $log_date = explode(" ", $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE]);
            $log_date = explode("-", $log_date[0]);
            $log_date = sprintf("%04d%02d%02d", $log_date[0],$log_date[1],$log_date[2]);
            $download_log = array();
            if($pub_date <= $log_date){
                // Open access
                $fileStatus = RepositoryConst::LOG_FILE_STATUS_OPEN;
            } else {
                // Need login file / Pay per view
                $fileStatus = RepositoryConst::LOG_FILE_STATUS_CLOSE;
            }
            
            // Check not login download
            if(strlen($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID]) == 0 || $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID] == "0")
            {
                // No login user
                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_NO_LOGIN;
            }
            else
            {
                // Check user's authority
                $userAuthId = $this->getUserAuthIdByUserId($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID]);
                $authId = $this->getRoomAuthorityID($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID]);
                if(strlen($userAuthId) > 0 && ($userAuthId >= $this->repository_admin_base && $authId >= $this->repository_admin_room))
                {
                    // Admin user
                    $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_ADMIN;
                }
                else if($logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID] == $file[0][RepositoryConst::DBCOL_COMMON_INS_USER_ID])
                {
                    // Register
                    $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_REGISTER;
                }
                else
                {
                    // Login user
                    $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_LOGIN;
                    if($inputType == RepositoryConst::LOG_INPUT_TYPE_FILE_PRICE)
                    {
                        // Get file price info
                        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_PRICE." ".
                                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE_PRICE." ".
                                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ITEM_ID." = '".$itemId."' ".
                                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ITEM_NO." = '".$itemNo."' ".
                                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ATTRIBUTE_ID." = '".$attributeId."' ".
                                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_FILE_NO." = '".$fileNo."' ";
                        $price = $this->Db->execute($query);
                        if($price === false)
                        {
                            return false;
                        }
                        
                        // Check download user's affiliate
                        $groupId = $this->getDownloadType($price[0][RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_PRICE], $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_USER_ID]);
                        if(strlen($groupId) > 0 && $groupId != "0")
                        {
                            // Group user
                            $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_GROUP;
                        }
                    }
                }
            }
        }
        else
        {
            // Get file status by log record.
            $fileStatus = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS];
            $siteLicense = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_SITE_LICENSE];
            $inputType = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_INPUT_TYPE];
            $loginStatus = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_LOGIN_STATUS];
            $groupId = $logRecord[RepositoryConst::DBCOL_REPOSITORY_LOG_GROUP_ID];
            
            // Get file name
            $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = '".$itemId."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = '".$itemNo."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = '".$attributeId."' ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = '".$fileNo."' ";
            $file = $this->Db->execute($query);
            if($file === false)
            {
                return false;
            }
            $fileName = $file[0][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME];
        }
        
        return true;
    }
    // Add file download status to log 2012/11/15 A.Suzuki --end--
    
    // Add file download log report 2012/11/21 A.Suzuki --start--
    /**
     * Set file download log report to array
     *
     * @param string $key
     * @param string $fileName
     * @param int $fileStatus
     * @param int $siteLicense
     * @param int $loginStatus
     * @param array $fileLog
     */
    private function setFileLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, &$fileLog)
    {
        if(!isset($fileLog[$key]))
        {
            $fileLog[$key]['file_name'] = $fileName;
            $fileLog[$key]['index_name'] = $this->getIndexName($key);
            $fileLog[$key]['total'] = 0;        // トータル(total)
            $fileLog[$key]['not_login'] = 0;    // 個人(not login)
            $fileLog[$key]['login'] = 0;        // ログインユーザー(login)
            $fileLog[$key]['site_license'] = 0; // Download by site license
            $fileLog[$key]['admin'] = 0;        // Download by admin
            $fileLog[$key]['register'] = 0;     // Download by register
        }
        
        // Check not login download
        $fileLog[$key]['total']++;
        if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_ADMIN)
        {
            $fileLog[$key]['admin']++;
        }
        else if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_REGISTER)
        {
            $fileLog[$key]['register']++;
        }
        else if($siteLicense == RepositoryConst::LOG_SITE_LICENSE_ON)
        {
            $fileLog[$key]['site_license']++;
        }
        else if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_NO_LOGIN)
        {
            $fileLog[$key]['not_login']++;
        }
        else
        {
            $fileLog[$key]['login']++;
        }
    }
    
    /**
     * Set file_price download log report to array
     *
     * @param string $key
     * @param string $fileName
     * @param int $fileStatus
     * @param int $siteLicense
     * @param int $loginStatus
     * @param int $groupId
     * @param array $fileLog
     */
    private function setFilePriceLogArray($key, $fileName, $fileStatus, $siteLicense, $loginStatus, $groupId, &$priceLog)
    {
        if(!isset($priceLog[$key]))
        {
            $priceLog[$key]['file_name'] = $fileName;
            $priceLog[$key]['index_name'] = $this->getIndexName($key);
            $priceLog[$key]['total'] = 0;           // トータル(total)
            $priceLog[$key]['not_login'] = 0;       // 個人(not login)
            $priceLog[$key]['group'] = array();     // グループ(group(room))
            $priceLog[$key]['group']['0'] = 0;      // 非会員(login user(not affiliate))
            $priceLog[$key]['site_license'] = 0;    // Download by site license
            $priceLog[$key]['admin'] = 0;           // Download by admin
            $priceLog[$key]['register'] = 0;        // Download by register
        }
        
        // Check not login download
        $priceLog[$key]['total']++;
        if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_ADMIN)
        {
            $priceLog[$key]['admin']++;
        }
        else if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_REGISTER)
        {
            $priceLog[$key]['register']++;
        }
        else if($siteLicense == RepositoryConst::LOG_SITE_LICENSE_ON)
        {
            $priceLog[$key]['site_license']++;
        }
        else if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_NO_LOGIN)
        {
            $priceLog[$key]['not_login']++;
        }
        else if($loginStatus == RepositoryConst::LOG_LOGIN_STATUS_GROUP)
        {
            if(isset($priceLog[$key]['group'][$groupId]))
            {
                $priceLog[$key]['group'][$groupId]++;
            } else {
                $priceLog[$key]['group'][$groupId] = 1;
            }
        }
        else
        {
            $priceLog[$key]['group']['0']++;
        }
    }
    
    /**
     * Create file download report string
     *
     * @return string file download log report 
     */
    private function makeFileDownloadLogReportStr($fileLog, $fileOpenLog)
    {
        // -----------------------------------------------
        // Make file download log
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_file_download_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_file");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_filename")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_total")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_not_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_admin")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_register");
        $str .= "\r\n";
        foreach ($fileLog as $key => $value)
        {
            $str .= $value['file_name']."\t";
            $str .= $value['index_name']."\t";
            $str .= $value['total']."\t";
            $str .= $value['not_login']."\t";
            $str .= $value['login']."\t";
            $str .= $value['site_license']."\t";
            $str .= $value['admin']."\t";
            $str .= $value['register'];
            $str .= "\r\n";
        }
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_open");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_filename")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_total")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_not_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_admin")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_register");
        $str .= "\r\n";
        foreach ($fileOpenLog as $key => $value)
        {
            $str .= $value['file_name']."\t";
            $str .= $value['index_name']."\t";
            $str .= $value['total']."\t";
            $str .= $value['not_login']."\t";
            $str .= $value['login']."\t";
            $str .= $value['site_license']."\t";
            $str .= $value['admin']."\t";
            $str .= $value['register'];
            $str .= "\r\n";
        }
        
        return $str;
    }
    
    /**
     * Create file price download report string
     *
     * @return string file price download log report 
     */
    public function makeFilePriceDownloadLogReportStr($priceLog, $priceOpenLog)
    {
        // -----------------------------------------------
        // Get group list(room_id and room_name list)
        // -----------------------------------------------
        $all_group = array();
        $error_msg = "";
        $result = $this->getGroupList($all_group, $error_msg);
        if($result === false)
        {
            return $this->Db->ErrorMsg();
        }
        
        // -----------------------------------------------
        // make file price download log
        // -----------------------------------------------
        $str = '';
        $str .= $this->smartyAssign->getLang("repository_log_download_title")."\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_totaling_month")."\t".$this->disp_start_date."\r\n";
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_price");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_filename")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_total")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_not_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_item_gest")."\t";
        for($ii=0; $ii<count($all_group); $ii++)
        {
            $str .= $all_group[$ii]['page_name']."\t";
        }
        $str .= $this->smartyAssign->getLang("repository_log_download_deleted_group")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_admin")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_register");
        $str .= "\r\n";
        foreach($priceLog as $key => $value)
        {
            $deletedGroup = $value['total'] - $value['not_login'] - $value['group']['0'] -
                            $value['site_license'] - $value['admin'] - $value['register'];
            $str .= $value['file_name']."\t";
            $str .= $value['index_name']."\t";
            $str .= $value['total']."\t";
            $str .= $value['not_login']."\t";
            $str .= $value['group']['0']."\t";
            for($ii=0; $ii<count($all_group); $ii++)
            {
                if($value['group'][$all_group[$ii]['room_id']] == null)
                {
                    $str .= "0\t";
                }
                else
                {
                    $str .= $value['group'][$all_group[$ii]['room_id']]."\t";
                    $deletedGroup -= $value['group'][$all_group[$ii]['room_id']];
                }
            }
            $str .= $deletedGroup."\t";
            $str .= $value['site_license']."\t";
            $str .= $value['admin']."\t";
            $str .= $value['register'];
            $str .= "\r\n";
        }
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_open");
        $str .= "\r\n";
        $str .= $this->smartyAssign->getLang("repository_log_download_filename")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_index")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_total")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_not_login")."\t";
        $str .= $this->smartyAssign->getLang("repository_item_gest")."\t";
        for($ii=0; $ii<count($all_group); $ii++)
        {
            $str .= $all_group[$ii]['page_name']."\t";
        }
        $str .= $this->smartyAssign->getLang("repository_log_download_deleted_group")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_sitelicense")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_admin")."\t";
        $str .= $this->smartyAssign->getLang("repository_log_download_register");
        $str .= "\r\n";
        foreach($priceOpenLog as $key => $value)
        {
            $deletedGroup = $value['total'] - $value['not_login'] - $value['group']['0'] -
                            $value['site_license'] - $value['admin'] - $value['register'];
            $str .= $value['file_name']."\t";
            $str .= $value['index_name']."\t";
            $str .= $value['total']."\t";
            $str .= $value['not_login']."\t";
            $str .= $value['group']['0']."\t";
            for($ii=0; $ii<count($all_group); $ii++)
            {
                if($value['group'][$all_group[$ii]['room_id']] == null)
                {
                    $str .= "0\t";
                }
                else
                {
                    $str .= $value['group'][$all_group[$ii]['room_id']]."\t";
                    $deletedGroup -= $value['group'][$all_group[$ii]['room_id']];
                }
            }
            
            $str .= $deletedGroup."\t";
            $str .= $value['site_license']."\t";
            $str .= $value['admin']."\t";
            $str .= $value['register'];
            $str .= "\r\n";
        }
        
        return $str;
    }
    // Add file download log report 2012/11/21 A.Suzuki --end--
}
?>
