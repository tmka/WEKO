<?php
// --------------------------------------------------------------------
//
// $Id: Result.class.php 56591 2015-08-18 01:37:11Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';

/**
 * log result action
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Log_Result extends WekoAction
{
    const CRLF = "\r\n";
    
    // out type
    // 1:insert
    // 2:download
    // 3:view
    const TYPE_LOG_REGIST_ITEM = 1;
    const TYPE_LOG_DOWNLOAD = 2;
    const TYPE_LOG_VIEW = 3;
    public $type_log = null;
    // per type
    // 1:month
    // 2:week
    // 3:day
    // 4:year
    // 5:item
    // 6:host
    const PER_LOG_DAY = 1;
    const PER_LOG_WEEK = 2;
    const PER_LOG_MONTH = 3;
    const PER_LOG_YEAR = 4;
    const PER_LOG_ITEM = 5;
    const PER_LOG_HOST = 6;
    public $per_log = null;
    // start date
    public $sy_log = null;
    public $sm_log = null;
    public $sd_log = null;
    // end date
    public $ey_log = null;
    public $em_log = null;
    public $ed_log = null;

    // download type
    const DOWNLOAD_TYPE_HTML = 0;   // 0:html
    const DOWNLOAD_TYPE_CSV = 1;    // 1:csv
    const DOWNLOAD_TYPE_TSV = 2;    // 2:tsv
    public $is_csv_log = null;

    /**
     * language resource object
     *
     * @var object
     */
    private $smartyAssign = null;

    /**
     * download object
     *
     * @var object
     */
    private $repositoryDownload = null;
    
    /**
     * execute each custom report process
     *
     */
    function executeApp()
    {
        // ------------------------------------------
        // set log start - end date
        // ------------------------------------------
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
        $this->infoLog("businessCustomreport", __FILE__, __CLASS__, __LINE__);
        $customReport = BusinessFactory::getFactory()->getBusiness("businessCustomreport");
        
        // set member
        $customReport->setStartYear($this->sy_log);
        $customReport->setStartMonth($this->sm_log);
        $customReport->setStartDay($this->sd_log);
        $customReport->setEndYear($this->ey_log);
        $customReport->setEndMonth($this->em_log);
        $customReport->setEndDay($this->ed_log);
        $customReport->setCountType($this->type_log);
        $customReport->setCountPer($this->per_log);
        
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        $this->repositoryDownload = new RepositoryDownload();
        
        try {
            // create custom report data
            $customReport->execute();
            
            $items = $customReport->getCustomReportData();
            
            switch($this->per_log)
            {
                case self::PER_LOG_DAY:
                case self::PER_LOG_WEEK:
                case self::PER_LOG_MONTH:
                case self::PER_LOG_YEAR:
                    $this->createPerDateData($items);
                    break;
                case self::PER_LOG_HOST:
                    $this->createPerHostData($items);
                    break;
                case self::PER_LOG_ITEM:
                    $this->createPerItemData($items);
                    break;
                default:
                    $this->warnLog("input is invalid", __FILE__, __CLASS__, __LINE__);
                    break;
            }
        } catch (AppException $e) {
            $this->createErrorMessage();
        }
        
        $this->exitFlag = true;
        
        return "";
    }

    /**
     * create error message by removing log
     *
     */
    private function createErrorMessage()
    {
        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV || $this->is_csv_log == self::DOWNLOAD_TYPE_TSV)
        {
            echo "<script class='nc_script' type='text/javascript'>alert('".$this->smartyAssign->getLang('repository_log_excluding')."');</script>";
        } 
        else if ($this->is_csv_log == self::DOWNLOAD_TYPE_HTML)
        {
            $html = "";
            $html .= '<center>';
            $html .= '<div class="error_msg al">';
            $html .= $this->smartyAssign->getLang('repository_log_excluding').'<br/>';
            $html .= '</div>';
            $html .= '</center>';
            
            // download
            $this->repositoryDownload->download($html, "log.html");
            
        }
    }

    /**
     * create html, csv file or tsv file by custom report
     *
     * @param array $items
     */
    private function createPerDateData($items)
    {
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
        $width_max = $this->getMaxWidthByResult($items);
        
        $every = "";
        switch ( $this->per_log ) {
            case self::PER_LOG_DAY:
                $every = $this->smartyAssign->getLang("repository_log_one_day");
                break;
            case self::PER_LOG_WEEK:
                $every = $this->smartyAssign->getLang("repository_log_one_week");
                break;
            case self::PER_LOG_MONTH:
                $every = $this->smartyAssign->getLang("repository_log_one_month");
                break;
            case self::PER_LOG_YEAR:
                $every = $this->smartyAssign->getLang("repository_log_one_year");
                break;
            default:
                break;
        }
    
        $type = $this->getTypeByTypeLog();
        $html = "";
        $TSV = "";
        $CSV = "";
        
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            // 期間
            $log_str_period = $this->smartyAssign->getLang("repository_log_period");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $CSV .= $log_str_period.",".$type.$log_str_num.self::CRLF;
        }
        // add header 2014/3/6 R.Matsuura --end--
    
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            // 期間
            $log_str_period = $this->smartyAssign->getLang("repository_log_period");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $TSV .= $log_str_period."\t".$type.$log_str_num.self::CRLF;
        }
        // add header 2014/3/6 R.Matsuura --end--
        
        // ------------------------------------------
        // get log result per date
        // ------------------------------------------
        if (count($items)>0) {
            $cnt_data = 0;
            for ( $ii=0; $ii<count($items); $ii++ ){
                $period = "";
                $cnt = intval($items[$ii]['cnt']);
                switch ( $this->per_log ) {
                    case self::PER_LOG_DAY:
                        // per day
                        $period = $items[$ii]['day'];
                        break;
                    case self::PER_LOG_WEEK:
                        // per week
                        if(($ii+1) == count($items)){
                            $period = $items[$ii]['day']." - ".$ey."-".$em."-".$ed;
                        } else if($ii == 0){
                            //$date = explode("-", $items[$ii+1]['day']);
                            //$prev_week = mktime(0,0,0,$date[1],($date[2]-1),$date[0]);
                            //$period = $items[$ii]['day']." - ".date("Y-m-d", $prev_week);
                            $query = "SELECT DATE_SUB('".$items[$ii+1]['day']."', INTERVAL 1 DAY) AS prev_week;";
                            $result = $this->Db->execute($query);
                            if($result === false || count($result) != 1){
                                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                                throw new AppException($this->Db->ErrorMsg());
                            }
                            $period = $items[$ii]['day']." - ".$result[0]['prev_week'];
                        } else {
                            //$date = explode("-", $items[$ii]['day']);
                            //$next_week = mktime(0,0,0,$date[1],($date[2]+6),$date[0]);
                            //$period = $items[$ii]['day']." - ".date("Y-m-d", $next_week);
                            $query = "SELECT DATE_ADD('".$items[$ii]['day']."', INTERVAL 6 DAY) AS next_week;";
                            $result = $this->Db->execute($query);
                            if($result === false || count($result) != 1){
                                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                                throw new AppException($this->Db->ErrorMsg());
                            }
                            $period = $items[$ii]['day']." - ".$result[0]['next_week'];
                        }
                        break;
                    case self::PER_LOG_MONTH:
                        // per month
                        if($ii == 0){
                            $period = $sy."-".$sm."-".$sd." - ".$items[$ii]['day'];
                        } else {
                            $period = $items[$ii]['day2']."-01"." - ".$items[$ii]['day'];
                        }
                        break;
                    case self::PER_LOG_YEAR:
                        // per year
                        if($ii == 0){
                            $period = $sy."-".$sm."-".$sd." - ".$items[$ii]['day'];
                        } else {
                            $period = $items[$ii]['day2']."-01-01"." - ".$items[$ii]['day'];
                        }
                        break;
                    default:
                        break;
                }
                if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
                    // CSV
                    $CSV = $CSV."\"".$period."\",\"".$cnt."\"".self::CRLF;
                } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
                    // TSV
                    $TSV = $TSV."\"".$period."\"\t\"".$cnt."\"".self::CRLF;
                } else {
                    // HTML
                    $width = 0;
                    if($width_max > 0)
                    {
                        $width = sprintf("%.0f",100*($cnt/$width_max));
                    }
                    if($ii%2==0){
                        $html .= '<tr class="list_line_repos1">';
                    } else {
                        $html .= '<tr class="list_line_repos2">';
                    }
                    $html .= '<td width="25%" class="list_paging ac">'.$period.'</td>';
                    $html .= '<td width="1%" class="list_paging ar">'.$cnt.'</td>';
                    $html .= '<td class="td_graph_repos list_paging"><img src="./images/repository/default/graph_bar.gif" style="width: '.$width.'%; height: 10px;"></td>';
                    $html .= '</tr>';
                }
            }
        } else {
            $html .= '<tr class="tr_repos">';
            $html .= '<td colspan=5 class="al">'.$this->smartyAssign->getLang("repository_log_nodata").'</td>';
            $html .= '</tr>';
        }

        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$CSV, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            // download
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$TSV, "log.tsv", "text/tab-separated-values");
        } else {
            // HTML
            $html_tmp = $html;
            $html = "";
            $html .= '<center>';
            $html .= '<div id="print_area_repos" class="ofx_auto ofy_hidden pd02">';
            $html .= '<table class="tb_repos text_color full" cellspacing="0">';
            $html .= '<tr><th colspan="3" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
            $html .= $every.$this->smartyAssign->getLang("repository_log_of");
            $html .= $type.$this->smartyAssign->getLang("repository_log_num");
            $html .= '</div></th></tr>';
            $html .= '<tr>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_period").'</th>';
            $html .= '<th class="th_col_repos ac" colspan="2">'.$type.$this->smartyAssign->getLang("repository_log_num").'</th>';
            $html .= '</tr>';
            $html .= $html_tmp;
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div class="paging">';
            $html .= '<a class="btn_blue white" href="javascript: printGraph_repos('."'print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
            $html .= '</div>';
            $html .= '</center>';

            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download($html, "log.html");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        }
    }
    
    /**
     * create html, csv file or tsv file by custom report
     *
     * @param array $items
     */
    private function createPerItemData($items)
    {
        $width_max = $this->getMaxWidthByResult($items);
        $html = "";
        $CSV = "";
        $TSV = "";
        
        $tmpDate = str_replace(" ", "", $this->accessDate);
        $tmpDate = str_replace(":", "", $tmpDate);
        $tmpDate = str_replace(".", "", $tmpDate);
        
        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            $csv_file = WEBAPP_DIR."/uploads/repository/log_result_".$tmpDate.".csv";
            $csv_fp = fopen($csv_file, "w");
            // add header 2014/3/6 R.Matsuura --start--
            $type="";
            switch ( $this->type_log ) {
                case self::TYPE_LOG_DOWNLOAD:
                    // download item log
                    $type = $this->smartyAssign->getLang("repository_log_download");
                    break;
                case self::TYPE_LOG_VIEW:
                    // view item log
                    $type = $this->smartyAssign->getLang("repository_log_refer");
                    break;
                default:
                    break;
            }
            // アイテムID
            $log_str_itemid = $this->smartyAssign->getLang("repository_log_itemid");
            // アイテム名
            $log_str_itemname = $this->smartyAssign->getLang("repository_log_itemname");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $CSV .= $log_str_itemid.",".$log_str_itemname.",".$type.$log_str_num.self::CRLF;
            fwrite($csv_fp, $CSV);
            // add header 2014/3/6 R.Matsuura --end--
        } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            $tsv_file = WEBAPP_DIR."/uploads/repository/log_result_".$tmpDate.".tsv";
            $tsv_fp = fopen($tsv_file, "w");
            // add header 2014/3/6 R.Matsuura --start--
            $type="";
            switch ( $this->type_log ) {
                case self::TYPE_LOG_DOWNLOAD:
                    // download item log
                    $type = $this->smartyAssign->getLang("repository_log_download");
                    break;
                case self::TYPE_LOG_VIEW:
                    // view item log
                    $type = $this->smartyAssign->getLang("repository_log_refer");
                    break;
                default:
                    break;
            }
            // アイテムID
            $log_str_itemid = $this->smartyAssign->getLang("repository_log_itemid");
            // アイテム名
            $log_str_itemname = $this->smartyAssign->getLang("repository_log_itemname");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $TSV .= $log_str_itemid."\t".$log_str_itemname."\t".$type.$log_str_num.self::CRLF;
            fwrite($tsv_fp, $TSV);
            // add header 2014/3/6 R.Matsuura --end--
        }

        if (count($items)>0) {
            for ( $ii=0; $ii<count($items) && $width_max>0; $ii++ )
            {
                $title = "";
                if($this->Session->getParameter("_lang") == "japanese"){
                    if(strlen($items[$ii]['title']) > 0){
                        $title = $items[$ii]['title'];
                    } else {
                        $title = $items[$ii]['title_english'];
                    }
                } else {
                    if(strlen($items[$ii]['title_english']) > 0){
                        $title = $items[$ii]['title_english'];
                    } else {
                        $title = $items[$ii]['title'];
                    }
                }
                if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
                    // CSV
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -start- */
                    fwrite($csv_fp, "\"".$items[$ii]['item_id']."\",\"".$title."\",\"".$items[$ii]['cnt']."\"".self::CRLF);
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -end- */
                } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
                    // TSV
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -start- */
                    fwrite($tsv_fp, "\"".$items[$ii]['item_id']."\"\t\"".$title."\"\t\"".$items[$ii]['cnt']."\"".self::CRLF);
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -end- */
                } else {
                    // HTML
                    $width = sprintf("%.0f",100*($items[$ii]['cnt']/$width_max));
                    if($ii%2==0){
                        $html .= '<tr class="list_line_repos1">';
                    } else {
                        $html .= '<tr class="list_line_repos2">';
                    }
                    $html .= '<td width="25%" class="list_paging al">'.$title.'</td>';
                    $html .= '<td width="1%" class="list_paging ar">'.$items[$ii]['cnt'].'</td>';
                    $html .= '<td class="td_graph_repos list_paging"><img src="./images/repository/default/graph_bar.gif" style="width: '.$width.'%; height: 10px;"></td>';
                    $html .= '</tr>';
                }
            }
        } else {
            $html .= '<tr class="tr_repos">';
            $html .= '<td colspan=5 class="al">'.$this->smartyAssign->getLang("repository_log_nodata").'</td>';
            $html .= '</tr>';
        }
        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            fclose($csv_fp);
            $fp = fopen( $csv_file, "rb" );
            $file = fread( $fp, filesize($csv_file) );
            fclose($fp);
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$file, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
            // delet CSV file
            unlink($csv_file);
        } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            fclose($tsv_fp);
            $fp = fopen( $tsv_file, "rb" );
            $file = fread( $fp, filesize($tsv_file) );
            fclose($fp);
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$file, "log.tsv", "text/tab-separated-values");
            // delet TSV file
            unlink($tsv_file);
        } else {
            $html_tmp = $html;
            $html = "";
            $html .= '<center>';
            $html .= '<div class="paging ofx_auto ofy_hidden pd02" id="print_area_repos">';
            $html .= '<table class="tb_repos text_color full">';
            $html .= '<tr><th colspan="3" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
            if($this->type_log == self::TYPE_LOG_DOWNLOAD){
                $html .= $this->smartyAssign->getLang("repository_log_item_download");
            } else if($this->type_log == self::TYPE_LOG_VIEW){
                $html .= $this->smartyAssign->getLang("repository_log_item_view");
            }
            $html .= '</div></th></tr>';
            $html .= '<tr>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_itemname").'</th>';
            $html .= '<th class="th_col_repos ac" colspan="2">';
            if($this->type_log == self::TYPE_LOG_DOWNLOAD){
                $html .= $this->smartyAssign->getLang("repository_log_download").$this->smartyAssign->getLang("repository_log_num");
            } else if($this->type_log == self::TYPE_LOG_VIEW){
                $html .= $this->smartyAssign->getLang("repository_log_refer").$this->smartyAssign->getLang("repository_log_num");
            }
            $html .= '</th>';
            $html .= '</tr>';
            $html .= $html_tmp;
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div class="paging">';
            $html .= '<a class="btn_blue white" href="javascript:  printGraph_repos('."'print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
            $html .= '</div>';
            $html .= '</center>';
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download($html, "log.html");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        }
    }
    
    /**
     * create html, csv file or tsv file by custom report
     *
     * @param array $items
     */
    private function createPerHostData($items)
    {
        $width_max = $this->getMaxWidthByResult($items);
        $html = "";
        $html_all = "";
        $CSV = "";
        $TSV = "";
        
        // ------------------------------------------
        // set log result type lang resource
        // ------------------------------------------
        $type = $this->getTypeByTypeLog();
        
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            // ホスト
            $log_str_host = $this->smartyAssign->getLang("repository_log_host");
            // IPアドレス
            $log_str_idaddress = $this->smartyAssign->getLang("repository_log_ip_address");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $CSV .= $log_str_host.",".$log_str_idaddress.",".$type.$log_str_num.self::CRLF;
        }
        // add header 2014/3/6 R.Matsuura --end--
        
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            // ホスト
            $log_str_host = $this->smartyAssign->getLang("repository_log_host");
            // IPアドレス
            $log_str_idaddress = $this->smartyAssign->getLang("repository_log_ip_address");
            // 回数
            $log_str_num = $this->smartyAssign->getLang("repository_log_num");
            $TSV .= $log_str_host."\t".$log_str_idaddress."\t".$type.$log_str_num.self::CRLF;
        }
        // add header 2014/3/6 R.Matsuura --end--
        
        // get exclude ip address list
        $query = "SELECT param_value ". 
                 " FROM ". DATABASE_PREFIX. "repository_parameter ". 
                 " WHERE param_name = ?;";
        $params = array();
        $params[] = 'log_exclusion';
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        $cnt_host = 0;
        $cnt_all_host = 0;
        $cnt_width = 0;
        if ( count($items)>0 ) {
            $width_max_all = $items[0]['cnt'];
            $cnt_width = mb_strlen($width_max);
            for ( $ii=0; $ii<count($items) && $width_max>0; $ii++ )
            {
                if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
                    // CSV
                    $CSV = $CSV."\"".$items[$ii]['host']."\",\"".$items[$ii]['ip_address']."\",\"".$items[$ii]['cnt']."\"".self::CRLF;
                } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
                    // TSV
                    $TSV = $TSV."\"".$items[$ii]['host']."\"\t\"".$items[$ii]['ip_address']."\"\t\"".$items[$ii]['cnt']."\"".self::CRLF;
                } else {
                    $width = sprintf("%.0f",100*($items[$ii]['cnt']/$width_max));
                    // ip address is in exclude list
                    if(is_numeric(strpos($result[0]['param_value'], $items[$ii]['ip_address'])) === false)
                    {
                        if($cnt_host % 2 == 0){
                            $html .= '<tr class="list_line_repos1">';
                        } else {
                            $html .= '<tr class="list_line_repos2">';
                        }
                        $html .= '<td class="list_paging" style="text-align: left;" ><span class="al" style="width:100px" >'.wordwrap($items[$ii]['host'], 20, "<br />",1).'</span></td>';
                        $html .= '<td class="list_paging ac">'.$items[$ii]['ip_address'].'</td>';
                        $html .= '<td class="list_paging nobr" align="center">';
                        $html .= '<input id="check_'. $ii. '" type="checkbox" value="'. $items[$ii]['ip_address']. '"/>';
                        $html .= '</td>';
                        $html .= '<td class="list_paging ar" style="width:'.$cnt_width.'ex;" ><span style="width:'.$cnt_width.'ex;" >'.$items[$ii]['cnt'].'</span></td>';
                        //$html .= '<td class="list_paging" style="width:'.$cnt_width.'ex;">'.$items[$ii]['cnt'].'</td>';
                        $html .= '<td class="td_graph_repos list_paging" style="text-align: left;"><img src="./images/repository/default/graph_bar.gif" style="text-align: left; width: '.$width.'%; height: 10px;"></td>';
                        $html .= '</tr>';
                    }

                    // HTML (all host)
                    $width = sprintf("%.0f",100*($items[$ii]['cnt']/$width_max_all));
                    if($ii%2==0){
                        $html_all .= '<tr class="list_line_repos1">';
                    } else {
                        $html_all .= '<tr class="list_line_repos2">';
                    }
                    $html_all .= '<td class="list_paging" style="text-align: left;" ><span class="al" style="width:100px" >'.wordwrap($items[$ii]['host'], 20, "<br />",1).'</span></td>';
                    $html_all .= '<td class="list_paging ac">'.$items[$ii]['ip_address'].'</td>';
                    $html_all .= '<td class="list_paging nobr" align="center">';
                    $html_all .= '<input id="check_'. $ii. '" type="checkbox" value="" ';
                    // ip address is in exclude list
                    if(is_numeric(strpos($result[0]['param_value'], $items[$ii]['ip_address'])))
                    {
                        $html_all .= 'checked="true" disabled/>';
                    }
                    else
                    {
                        $html_all .= ' "/>';
                    }
                    $html_all .= '</td>';
                    $html_all .= '</td>';
                    $html_all .= '<td class="list_paging ar" style="width:'.$cnt_width.'ex;" ><span style="width:'.$cnt_width.'ex;" >'.$items[$ii]['cnt'].'</span></td>';
                    //$html_all .= '<td class="list_paging" style="width:'.$cnt_width.'ex;">'.$items[$ii]['cnt'].'</td>';
                    $html_all .= '<td class="td_graph_repos list_paging" style="text-align: left;"><img src="./images/repository/default/graph_bar.gif" style="text-align: left; width: '.$width.'%; height: 10px;"></td>';
                    $html_all .= '</tr>';
                }
                // ip address is in exclude list
                if(is_numeric(strpos($result[0]['param_value'], $items[$ii]['ip_address'])) === false)
                {
                    $cnt_host++;
                }
                $cnt_all_host++;
            }
        }
        else
        {
            $html .= '<tr class="tr_repos">';
            $html .= '<td colspan=5 class="al">'.$this->smartyAssign->getLang("repository_log_nodata").'</td>';
            $html .= '</tr>';
        }
        
        // no data by exclude log
        if($cnt_host === 0)
        {
            $html .= '<tr class="tr_repos">';
            $html .= '<td colspan=5 class="al">'.$this->smartyAssign->getLang("repository_log_nodata").'</td>';
            $html .= '</tr>';
        }

        if($this->is_csv_log == self::DOWNLOAD_TYPE_CSV){
            // CSV
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$CSV, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        } else if($this->is_csv_log == self::DOWNLOAD_TYPE_TSV){
            // TSV
            // download
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$TSV, "log.tsv", "text/tab-separated-values");
        } else {
            // HTML
            $html = $this->createHostAccessHtml($html, $cnt_width, true, $cnt_host);
            
            // HTML(all-host)
            $html .= $this->createHostAccessHtml($html_all, $cnt_width, false, $cnt_all_host);
            
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download($html, "log.html");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        }
    }
    
    /**
     * create host access report table html
     *
     * @param array $accessHtml -> custom log report table
     * @param int $maxWidth -> max cnt length
     * @param boolean $isDisplay -> true : exclude by excluded ip address
     *                              false: all host custom report
     * @param int $rowNum -> num of custom report row
     * @return string -> host access report table html
     */
    private function createHostAccessHtml($accessHtml, $maxWidth, $isDisplay, $rowNum)
    {
        $type = $this->getTypeByTypeLog();
        
        $html = "";
        $html .= '<center>';
        if($isDisplay)
        {
            $html .= '<div id="print_area_repos"     name="print_area_repos"     class="ofx_auto ofy_hidden pd02" style="display:block">';
        }
        else
        {
            $html .= '<div id="all_print_area_repos" name="all_print_area_repos" class="ofx_auto ofy_hidden pd02" style="display:none;">';
        }
        $html .= '<table class="tb_repos text_color full" >';
        $html .= '<colgroup span="2" width="100px">';
        $html .= '<colgroup width="160px">';
        $html .= '<colgroup width="'.$maxWidth.'ex;">';
        $html .= '<colgroup width="*%">';
        $html .= '<tr><th colspan="5" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
        $html .= '<p class="al fl">';
        $html .= $this->smartyAssign->getLang("repository_log_per_host").
                 $this->smartyAssign->getLang("repository_log_of").
                 $type.
                 $this->smartyAssign->getLang("repository_log_num").'</p>';
                 
        if($isDisplay)
        {
            $html .= '<p class="ar"><a href="javascript: hostdisplayClicked()" name="host_display"     id="host_display"     style="text-align: left;">';
            $html .= $this->smartyAssign->getLang("repository_log_host_all_show").'</a>';
        }
        else
        {
            $html .= '<p class="ar"><a href="javascript: hostdisplayClicked()" name="host_display_all" id="host_display_all" style="text-align: left;">';
            $html .= $this->smartyAssign->getLang("repository_log_host_limit").'</a>';
        }
        $html .= '</p></div></th>';
        $html .= '<tr>';
        $html .= '<th class="th_col_repos ac" style="width:100px">'.$this->smartyAssign->getLang("repository_log_host").'</th>';
        $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_ip_address").'</th>';
        $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_admin_log_exclusion").'</th>';
        $html .= '<th class="th_col_repos ac" colspan="2">'.$type.$this->smartyAssign->getLang("repository_log_num").'</th>';
        $html .= '</tr>';
        $html .= $accessHtml;
        $html .= '</table>';
        $html .= '</div>';
        
        if($isDisplay)
        {
            $html .= '<div class="paging" id="print_paging"     name="print_paging"     style="display:block">';
            $html .= '<a class="btn_blue white" style="margin-right:5px !important;" href="javascript: setExcludeIpAddress('. $rowNum. ')">'.$this->smartyAssign->getLang("repository_log_set_exclude_address").'</a>';
            $html .= '<a class="btn_blue white" style="margin-left:5px !important;"  href="javascript: printGraph_repos('."'print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
        }
        else
        {
            $html .= '<div class="paging" id="all_print_paging" name="all_print_paging" style="display:none;">';
            $html .= '<a class="btn_blue white" style="margin-right:5px !important;" href="javascript: setExcludeIpAddress('. $rowNum. ')">'.$this->smartyAssign->getLang("repository_log_set_exclude_address").'</a>';
            $html .= '<a class="btn_blue white" style="margin-left:5px !important;"  href="javascript: printGraph_repos('."'all_print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
        }
        $html .= '</div>';
        $html .= '</center>';
        
        return $html;
    }
    
    /**
     * get max width by cnt
     *
     * @param array $items
     * @return int
     */
    private function getMaxWidthByResult($items)
    {
        $width_max = 0;
        for($cnt = 0; $cnt < count($items); $cnt++)
        {
            if($width_max < $items[$cnt]['cnt'])
            {
                $width_max = $items[$cnt]['cnt'];
            }
        }
        
        return $width_max;
    }
    
    /**
     * get message of output type
     *
     * @return string
     */
    private function getTypeByTypeLog()
    {
        switch ( $this->type_log )
        {
            case self::TYPE_LOG_REGIST_ITEM:
                // insert item log
                $type = $this->smartyAssign->getLang("repository_log_entry");
                break;
            case self::TYPE_LOG_DOWNLOAD:
                // download item log
                $type = $this->smartyAssign->getLang("repository_log_download");
                break;
            case self::TYPE_LOG_VIEW:
                // view item log
                $type = $this->smartyAssign->getLang("repository_log_refer");
                break;
            default:
                break;
        }
        return $type;
    }
}
?>
