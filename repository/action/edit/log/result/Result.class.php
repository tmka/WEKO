<?php
// --------------------------------------------------------------------
//
// $Id: Result.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';

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
class Repository_Action_Edit_Log_Result extends RepositoryAction
{
    const CRLF = "\r\n";
    
    // out type
    // 1:insert
    // 2:download
    // 3:view
    var $type_log=2;
    // per type
    // 1:month
    // 2:week
    // 3:day
    // 4:year
    // 5:item
    // 6:host
    var $per_log=4;
    // start date
    var $sy_log='';
    var $sm_log='';
    var $sd_log='';
    // end date
    var $ey_log='';
    var $em_log='';
    var $ed_log='';

    // download type
    // 0:html
    // 1:csv
    // 2:tsv
    var $is_csv_log=0;

    var $queryParam=null;

    // Add log exception from ip address 2008.11.10 Y.Nakao --start--
    var $log_exception = "";
    // Add log exception from ip address 2008.11.10 Y.Nakao --end--

    // Add lang resource 2008/11/27 Y.Nakao --start--
    // component
    var $Session = null;
    var $smartyAssign = null;
    // Add lang resource 2008/11/27 Y.Nakao --end--

    // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
    var $repositoryDownload = null;
    // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
    
    // Add log exclusion from user-agaent 2010/07/02 Y.Nakao --end--
    var $robots_exclusion = "";
    // Add log exclusion from user-agaent 2010/07/02 Y.Nakao --end--
    
    function execute()
    {
        try {
            
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            {
                $tmpArr1 = explode("&",$_SERVER["QUERY_STRING"]);
                for ( $i=0; $i<count($tmpArr1); $i++ ) {
                    $tmpArr2 = explode("=", $tmpArr1[$i]);
                    $this->queryParam[$tmpArr2[0]] = $tmpArr2[1];
                }
                $this->sy_log = $this->queryParam['sy_log'];
                $this->sm_log = $this->queryParam['sm_log'];
                $this->sd_log = $this->queryParam['sd_log'];
                $this->ey_log = $this->queryParam['ey_log'];
                $this->em_log = $this->queryParam['em_log'];
                $this->ed_log = $this->queryParam['ed_log'];
                $this->type_log = $this->queryParam['type_log'];
                $this->per_log = $this->queryParam['per_log'];
                $this->is_csv_log = $this->queryParam['is_csv_log'];
            }
             
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload = new RepositoryDownload();
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
            
            // Add log exception from ip address 2008.11.10 Y.Nakao --start--
            // -----------------------------------------
            // make log exception SQL
            // -----------------------------------------
            $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = 'log_exclusion'; ";
            $ip_list = $this->Db->execute($query);
            if($ip_list === false){
                return 'error';
            }
                
            // Add log exclusion from user-agaent 2011/04/28 H.Ito --start--
            $this->log_exception = $this->createLogExclusion(1);
            $this->robots_exclusion = $this->createLogExclusion(2);
            // Add log exclusion from user-agaent 2011/04/28 H.Ito --end--
                
            // Add lang resource 2008/11/27 Y.Nakao --start--
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");
            // Add lang resource 2008/11/27 Y.Nakao --end--
                
            // -----------------------------------------
            // make log result
            // -----------------------------------------
            // disp log result
            if ($this->per_log==5) {
                // per item
                $this->getPerItem();
                // Add host log Y.Nakao 2010/03/03 Y.Nakao --start--
            } else if ($this->per_log == 6) {
                // per host
                $this->getPerHost();
                // Add host log Y.Nakao 2010/03/03 Y.Nakao --end--
            } else {
                // per date
                $this->getPerDate();
            }
            
            exit();
             
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
            $Exception->getCode(),          //ログID
            $Exception->getMessage(),       //主メッセージ
            $Exception->getDetailMsg() );   //詳細メッセージ
             
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる

            //異常終了
            return "error";
        }
    }

    // Add lang resource 2008/11/27 Y.Nakao --start--
    /**
     * per date log result format HTML
     *
     */
    function getPerDate()
    {
        $CSV = "";
        $TSV = "";
        $html = "";
        // ------------------------------------------
        // set log start - end date
        // ------------------------------------------
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
//      $su = mktime(0,0,0,$sm,$sd,$sy);
//      $eu = mktime(0,0,0,$em,$ed,$ey);
//      // second per day
//      $sec = 60 * 60 * 24; // 60[s] * 60[m] * 24[h]
//      // get date
//      $date_list = array();
//      $log_per_date = array();
//      for ( $i = $su;$i <= $eu;$i += $sec ) {
//          array_push($date_list, date("Y-m-d", $i));
//      }

        // get date
        $date_list = array();
        $log_per_date = array();
        
        $s_date = $sy."-".$sm."-".$sd;
        $e_date = $ey."-".$em."-".$ed;
        $query = "SELECT DATEDIFF('".$e_date."', '".$s_date."') AS date_diff;";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $diff = $result[0]['date_diff'];
        for ($i=0;$i<=$diff;$i++) {
            $query = "SELECT DATE_ADD('".$s_date."', INTERVAL ".$i." DAY) AS str_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            array_push($date_list, $result[0]['str_date']);
        }
        
        // ------------------------------------------
        // read log file per date
        // ------------------------------------------
        $width_max = 0;     // max count
        //$su_db = $su;
        $su_db = $s_date;
        for($ii=$sy; $ii<=$ey; $ii++){
            if(file_exists(WEBAPP_DIR."/logs/weko/logfile/log_per_date_$ii.txt")){
                $date = "";
                $cnt = 0;
                $now_key = "";
                $old_key = "";
                $fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_date_$ii.txt", "r");
                $lastDateFlag = false;
                while(!feof($fp)){
                    // read line
                    // record_date  year_week   item_count  download_count  view_count
                    $file_line = fgets($fp);
                    $file_line = str_replace("\r\n", "", $file_line);
                    $file_line = str_replace("\n", "", $file_line);
                    $line = split("\t", $file_line);
                    if(in_array($line[0], $date_list)){
                        // cntを合計するキーを取得
                        $now_key = "";
                        if($this->per_log == "1") {
                            // per date
                            $now_key = $line[0];
                        } else if($this->per_log == "2") {
                            // per week
                            $now_key = $line[1];
                        } else if($this->per_log == "3") {
                            // per month
                            $now_key = substr($line[0], 0, 7);
                            // 末日を保持
                            $tmp = explode("-", $old_key);
                            //$date  = date("Y-m-d", mktime(0, 0, 0, $tmp[1]+1, 0, $tmp[0]));
                            $query = "SELECT LAST_DAY('".$tmp[0]."-".($tmp[1])."-01') AS str_date;";
                            $result = $this->Db->execute($query);
                            if($result === false || count($result) != 1){
                                return false;
                            }
                            $date = $result[0]['str_date'];
                        } else if($this->per_log == "4") {
                            // per year
                            $now_key = substr($line[0], 0, 4);
                            // 末尾を保持
                            if(intval($old_key."1231") < intval($ey.$em.$ed)){
                                $date = $old_key."-12-31";
                            } else {
                                $date = $ey."-".$em."-".$ed;
                            }
                        } else {
                            // else
                            continue;
                        }
                        
                        if(strlen($old_key) == 0){
                            // 初回のみ
                            $old_key = $now_key;
                            $date = $line[0];
                        } else if($old_key != $now_key){
                            // 合計するキーが変わった場合、前の情報を格納して初期化
                            array_push($log_per_date, array('day'=>$date, 'day2'=> $old_key, 'cnt'=>$cnt));
                            // cntの最大値を保持
                            if($width_max < intval($cnt)){
                                $width_max = intval($cnt);
                            }
                            $old_key = $now_key;
                            $date = $line[0];
                            $cnt = 0;
                        }
                        
                        // cntを合計
                        $cnt += intval($line[intval($this->type_log+1)]);
                        
                        //$tmp = explode("-", $line[0]);
                        //$su_db = mktime(0,0,0,$tmp[1],$tmp[2]+1,$tmp[0]);
                        $query = "SELECT DATE_ADD('".$line[0]."', INTERVAL 1 DAY) AS str_date;";
                        $result = $this->Db->execute($query);
                        if($result === false || count($result) != 1){
                            return false;
                        }
                        $su_db = $result[0]['str_date'];
                        if(intval($ey.$em.$ed) <= intval(str_replace("-", "", $line[0]))){
                            array_push($log_per_date, array('day'=>$date, 'day2'=> $now_key, 'cnt'=>$cnt));
                            // cntの最大値を保持
                            if($width_max < intval($cnt)){
                                $width_max = intval($cnt);
                            }
                            $lastDateFlag = true;
                            // Excess period
                            break;
                        }
                    }
                }
                if(!$lastDateFlag){
                    array_push($log_per_date, array('day'=>$date, 'day2'=> $now_key, 'cnt'=>$cnt));
                    // cntの最大値を保持
                    if($width_max < intval($cnt)){
                       $width_max = intval($cnt);
                    }
                }
                fclose($fp);
            }
        }
        
        // ------------------------------------------
        // make between dates
        // ------------------------------------------
        $query = "DROP TEMPORARY TABLE IF EXISTS ".DATABASE_PREFIX."repository_date ";
        $ret = $this->Db->execute($query);
        if($ret === false){
            return "";
        }
        $query = "CREATE TEMPORARY TABLE ".DATABASE_PREFIX."repository_date ( ".
                "  `day` VARCHAR(23) ".
                " ) ";
        $ret = $this->Db->execute($query);
        if($ret === false){
            exit();
        }
        
        //$su = mktime(0,0,0,$sm,$sd,$sy);
        //$eu = mktime(0,0,0,$em,$ed,$ey);
        // second per day
        //$sec = 60 * 60 * 24; // 60[s] * 60[m] * 24[h]
        // get date
        
        $date_query = "SELECT DATEDIFF('".$e_date."', '".$su_db."') AS date_diff;";
        $result = $this->Db->execute($date_query);
        if($result === false || count($result) != 1){
            return false;
        }
        $diff = $result[0]['date_diff'];
        
//      for ( $i = $su_db;$i <= $eu;$i += $sec ) {
//          //array_push($dates, date("Y-m-d", $i));
//          if($i != $su_db){
//              $query .= " , ";
//          }
//          $query .= " ( '".date("Y-m-d", $i)."' ) ";
//      }
        
        if($diff > 0){
            $query = " INSERT INTO ".DATABASE_PREFIX."repository_date VALUES ";
            for ($i=0;$i<=$diff;$i++) {
                if($i != 0){
                    $query .= " , ";
                }
                $date_query = "SELECT DATE_ADD('".$su_db."', INTERVAL ".$i." DAY) AS str_date;";
                $result = $this->Db->execute($date_query);
                if($result === false || count($result) != 1){
                    return false;
                }
                $query .= " ( '".$result[0]['str_date']."' ) ";
            }
            $ret = $this->Db->execute($query);
            if($ret === false){
                exit();
            }
        }
        $every="";
        $query="";
        switch ( $this->per_log ) {
            case 1:
                // per day
                $every = $this->smartyAssign->getLang("repository_log_one_day");
                $query = "SELECT t1.day, t2.cnt ".
                        " FROM ". DATABASE_PREFIX ."repository_date AS t1 ".
                        " LEFT JOIN ( ".
                        "   SELECT CAST( log.record_date AS DATE ) AS d1, count(*) AS cnt ";
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
                if($this->type_log == 2){
                    $query .= "  FROM (".
                              "      SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                              "       ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
                              "      FROM ". DATABASE_PREFIX ."repository_log".
                              "      WHERE operation_id=".$this->type_log." ) AS log";
                } else {
                    $query .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
                }
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
                $query .= "     WHERE log.record_date >= '$sy-$sm-$sd 00:00:00.000' ".
                          "     AND log.record_date <= '$ey-$em-$ed 23:59:99.999' ".
                          "     AND log.operation_id = '".$this->type_log."' ".
                            $this->log_exception.
                            $this->robots_exclusion.
                          "     GROUP BY d1 ". 
                          " ) AS t2 ON ( t1.day = t2.d1 ) ";
                $ret = $this->Db->execute($query." WHERE cnt IS NOT NULL ORDER BY cnt DESC LIMIT 0 , 1 ");
                if($width_max < intval($ret[0]['cnt'])){
                    $width_max = intval($ret[0]['cnt']);
                }
                $query .= " ORDER BY t1.day ";
                break;
            case 2:
                // per week
                $every = $this->smartyAssign->getLang("repository_log_one_week");
                $query = "SELECT t1.day, t1.day2, t2.cnt ".
                        " FROM ( ".
                        "   SELECT `day`, YEARWEEK( CAST( `day` AS DATE ) )  AS day2 ".
                        "   FROM ( ".
                        "       SELECT * FROM ".DATABASE_PREFIX ."repository_date ".
                        "       ORDER BY `day` ASC ".
                        "   ) AS t3 ".
                        "   GROUP BY `day2` ".
                        " ) AS t1 ".
                        " LEFT JOIN ( ".
                        "   SELECT YEARWEEK( CAST( `record_date` AS DATE ) ) AS d1, count(*) AS cnt ";
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
                if($this->type_log == 2){
                    $query .= "  FROM (".
                              "      SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                              "       ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
                              "      FROM ". DATABASE_PREFIX ."repository_log".
                              "      WHERE operation_id=".$this->type_log." ) AS log";
                } else {
                    $query .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
                }
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
                $query .= "     WHERE log.record_date >= '$sy-$sm-$sd 00:00:00.000' ".
                        "   AND log.record_date <= '$ey-$em-$ed 23:59:99.999' ".
                        "   AND log.operation_id = '".$this->type_log."' ".
                            $this->log_exception.
                            $this->robots_exclusion.
                        "   GROUP BY d1 ". 
                        " ) AS t2 ON ( t1.day2 = t2.d1 ) ";
                $ret = $this->Db->execute($query." WHERE cnt IS NOT NULL ORDER BY cnt DESC LIMIT 0 , 1 ");
                if($width_max < intval($ret[0]['cnt'])){
                    $width_max = intval($ret[0]['cnt']);
                }
                $query .= " ORDER BY t1.day ";
                break;
            case 3:
                // per month
                $every = $this->smartyAssign->getLang("repository_log_one_month");
                $query = "SELECT t1.day, t1.day2, t2.cnt ".
                        " FROM ( ".
                        "   SELECT `day`, SUBSTRING(CAST( day AS DATE), 1, 7) AS day2 ".
                        "   FROM ( ".
                        "       SELECT * FROM ".DATABASE_PREFIX ."repository_date ".
                        "       ORDER BY `day` DESC ".
                        "   ) AS t3 ".
                        "   GROUP BY `day2` ".
                        " ) AS t1 ".
                        " LEFT JOIN ( ".
                        "   SELECT SUBSTRING(CAST(log.record_date AS DATE), 1, 7) AS d1, count(*) AS cnt ";
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
                if($this->type_log == 2){
                    $query .= "  FROM (".
                              "      SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                              "       ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
                              "      FROM ". DATABASE_PREFIX ."repository_log".
                              "      WHERE operation_id=".$this->type_log." ) AS log";
                } else {
                    $query .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
                }
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
                $query .= "     WHERE record_date >= '$sy-$sm-$sd 00:00:00.000' ".
                          "     AND record_date <= '$ey-$em-$ed 23:59:99.999' ".
                          "     AND log.operation_id = '".$this->type_log."' ".
                                $this->log_exception.
                                $this->robots_exclusion.
                          "     GROUP BY d1 ". 
                          " ) AS t2 ON ( t1.day2 = t2.d1 ) ";
                $ret = $this->Db->execute($query." WHERE cnt IS NOT NULL ORDER BY cnt DESC LIMIT 0 , 1 ");
                if(count($ret) > 0 && $width_max < intval($ret[0]['cnt'])){
                    $width_max = intval($ret[0]['cnt']);
                }
                $query .= " ORDER BY t1.day2 ";
                break;
            case 4:
                // per year
                $every = $this->smartyAssign->getLang("repository_log_one_year");
                $query = "SELECT t1.day, t1.day2, t2.cnt ".
                        " FROM ( ".
                        "   SELECT `day`, SUBSTRING(CAST( day AS DATE), 1, 4) AS day2 ".
                        "   FROM ( ".
                        "       SELECT * FROM ".DATABASE_PREFIX ."repository_date ".
                        "       ORDER BY `day` DESC ".
                        "   ) AS t3 ".
                        "   GROUP BY `day2` ".
                        " ) AS t1 ".
                        " LEFT JOIN ( ".
                        "   SELECT SUBSTRING(CAST(log.record_date AS DATE), 1, 4) AS d1, count(*) AS cnt ";
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
                if($this->type_log == 2){
                    $query .= "  FROM (".
                              "      SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                              "       ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
                              "      FROM ". DATABASE_PREFIX ."repository_log".
                              "      WHERE operation_id=".$this->type_log." ) AS log";
                } else {
                    $query .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
                }
                // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
                $query .= "     WHERE record_date >= '$sy-$sm-$sd 00:00:00.000' ".
                        "   AND record_date <= '$ey-$em-$ed 23:59:99.999' ".
                        "   AND log.operation_id = '".$this->type_log."' ".
                            $this->log_exception.
                            $this->robots_exclusion.
                        "   GROUP BY d1 ". 
                        " ) AS t2 ON ( t1.day2 = t2.d1 ) ";
                $ret = $this->Db->execute($query." WHERE cnt IS NOT NULL ORDER BY cnt DESC LIMIT 0 , 1 ");
                if($width_max < intval($ret[0]['cnt'])){
                    $width_max = intval($ret[0]['cnt']);
                }
                $query .= " ORDER BY t1.day2 ";
                break;
            default:
                break;
        }
        
        $items = $this->Db->execute($query);
        $query = "DROP TEMPORARY TABLE IF EXISTS ".DATABASE_PREFIX."repository_date ";
        $ret = $this->Db->execute($query);
        if($ret === false){
            return "";
        }
        
        // items merge log_per_date
//      print_r($log_per_date);
//      echo "<br/><br/>";
//      print_r($items);
        
        if($this->per_log == "2" || $this->per_log == "4"){
            // per week, per year
            if(count($log_per_date) > 0 && count($items) > 0){
                // $log_per_dateの末尾と$itemsの先頭が同じ週または年だった場合加算
                $log_cnt = count($log_per_date)-1;
                if(intval($log_per_date[$log_cnt]['day2']) == intval($items[0]['day2'])){
                    $items[0]['day'] = $log_per_date[$log_cnt]['day'];
                    $items[0]['cnt'] += $log_per_date[$log_cnt]['cnt'];
                    if($width_max < $items[0]['cnt']){
                        $width_max = $items[0]['cnt'];
                    }
                    unset($log_per_date[$log_cnt]);
                }
            }
        }
        $items = array_merge($log_per_date, $items);
        
        $type="";
        switch ( $this->type_log ) {
            case 1:
                // insert item log
                $type = $this->smartyAssign->getLang("repository_log_entry");
                break;
            case 2:
                // download item log
                $type = $this->smartyAssign->getLang("repository_log_download");
                break;
            case 3:
                // view item log
                $type = $this->smartyAssign->getLang("repository_log_refer");
                break;
            default:
                break;
        }
        
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == 2){
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
                    case 1:
                        // per day
                        $period = $items[$ii]['day'];
                        break;
                    case 2:
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
                                return false;
                            }
                            $period = $items[$ii]['day']." - ".$result[0]['prev_week'];
                        } else {
                            //$date = explode("-", $items[$ii]['day']);
                            //$next_week = mktime(0,0,0,$date[1],($date[2]+6),$date[0]);
                            //$period = $items[$ii]['day']." - ".date("Y-m-d", $next_week);
                            $query = "SELECT DATE_ADD('".$items[$ii]['day']."', INTERVAL 6 DAY) AS next_week;";
                            $result = $this->Db->execute($query);
                            if($result === false || count($result) != 1){
                                return false;
                            }
                            $period = $items[$ii]['day']." - ".$result[0]['next_week'];
                        }
                        break;
                    case 3:
                        // per month
                        if($ii == 0){
                            $period = $sy."-".$sm."-".$sd." - ".$items[$ii]['day'];
                        } else {
                            $period = $items[$ii]['day2']."-01"." - ".$items[$ii]['day'];
                        }
                        break;
                    case 4:
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
                if($this->is_csv_log == 1){
                    // CSV
                    $CSV = $CSV."\"".$period."\",\"".$cnt."\"".self::CRLF;
                } else if($this->is_csv_log == 2){
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

        if($this->is_csv_log == 1){
            // CSV
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$CSV, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        } else if($this->is_csv_log == 2){
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
     * view item log per item
     *
     */
    function getPerItem()
    {
        $CSV = "";
        $TSV = "";
        $html = "";
        // ------------------------------------------
        // set log start - end date
        // ------------------------------------------
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
        // ------------------------------------------
        // set log start - end date
        // ------------------------------------------
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
//      $su = mktime(0,0,0,$sm,$sd,$sy);
//      $eu = mktime(0,0,0,$em,$ed,$ey);
//      // second per day
//      $sec = 60 * 60 * 24; // 60[s] * 60[m] * 24[h]
        // get date
        $date_list = array();
//      for ( $i = $su;$i <= $eu;$i += $sec ) {
//          array_push($date_list, date("Y-m-d", $i));
//      }
        
        $s_date = $sy."-".$sm."-".$sd;
        $e_date = $ey."-".$em."-".$ed;
        $query = "SELECT DATEDIFF('".$e_date."', '".$s_date."') AS date_diff;";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $diff = $result[0]['date_diff'];
        for ($i=0;$i<=$diff;$i++) {
            $query = "SELECT DATE_ADD('".$s_date."', INTERVAL ".$i." DAY) AS str_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            array_push($date_list, $result[0]['str_date']);
        }
        
        $this->TransStartDate = str_replace(" ", "", $this->TransStartDate);
        $this->TransStartDate = str_replace(":", "", $this->TransStartDate);
        $this->TransStartDate = str_replace(".", "", $this->TransStartDate);
        $query = "CREATE TEMPORARY TABLE `".DATABASE_PREFIX."repository_log_".$this->TransStartDate."` ( ".
                " item_id INT, item_no INT, cnt INT default 0, ".
                " PRIMARY KEY(`item_id`, `item_no`) ".
                " ); ";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        }
        
        // ------------------------------------------
        // make log from log table
        // ------------------------------------------
        $query=" INSERT INTO `".DATABASE_PREFIX."repository_log_".$this->TransStartDate."` ".
                " SELECT log.item_id, log.item_no, count(*) AS cnt ";
        // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
        if($this->type_log == 2){
            $query .= "  FROM (".
                      "   SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                      "    ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
                      "   FROM ". DATABASE_PREFIX ."repository_log".
                      "   WHERE operation_id=".$this->type_log." ) AS log";
        } else {
            $query .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
        }
        // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
        $query .= " WHERE log.record_date >= '$sy-$sm-$sd 00:00:00.000' ". 
                  " AND log.record_date <= '$ey-$em-$ed 23:59:99.999' ".
                  " AND log.operation_id='$this->type_log' ".
                  $this->log_exception.
                  $this->robots_exclusion.
                  " GROUP BY item_id, item_no ";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        }
        
        // ------------------------------------------
        // read log file per item
        // ------------------------------------------
        $width_max = 0;
        $log_per_item = array();
        $year = $sy;
        $month = $sm;
        while(intval($year.$month) <= intval($ey.$em)){
            if(file_exists(WEBAPP_DIR."/logs/weko/logfile/log_per_item_$year$month.txt")){
                $fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_item_$year$month.txt", "r");
                while(!feof($fp)){
                    // read line
                    // record_date  item_id item_no download_count  view_count
                    $file_line = fgets($fp);
                    $file_line = str_replace("\r\n", "", $file_line);
                    $file_line = str_replace("\n", "", $file_line);
                    $line = split("\t", $file_line);
                    if(in_array($line[0], $date_list)){
                        $item_id = split("-", $line[1]);
                        $query = " INSERT INTO `".DATABASE_PREFIX."repository_log_".$this->TransStartDate."` VALUES ".
                                " ('".$item_id[0]."', '".$item_id[1]."', '".$line[$this->type_log]."') ".
                                " ON DUPLICATE KEY UPDATE ".
                                " cnt = cnt + ".intval($line[$this->type_log])." ; ";
                        $result = $this->Db->execute($query);
                        if($result === false){
                            return false;
                        }
                    }
                }
                fclose($fp);
            }
            //$date = mktime(0,0,0,$month+1,1,$year);
            //$year = date("Y", $date);
            //$month = date("m", $date);
            
            $query = "SELECT DATE_FORMAT(DATE_ADD('".$year."-".$month."-01', INTERVAL 1 MONTH), '%Y') AS tmp_year,".
                     " DATE_FORMAT(DATE_ADD('".$year."-".$month."-01', INTERVAL 1 MONTH), '%m') AS tmp_month;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            $year = $result[0]['tmp_year'];
            $month = $result[0]['tmp_month'];
        }
        
        /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -start- */
        $sqlCmd=" SELECT log.item_id, item.title, item.title_english, cnt ".
                " FROM `".DATABASE_PREFIX."repository_log_".$this->TransStartDate."` AS log, ".DATABASE_PREFIX."repository_item AS item ". 
                " WHERE log.item_id = item.item_id ".
                " AND log.item_no = item.item_no ".
                " AND cnt > 0 ". 
                " ORDER BY cnt DESC, log.item_id ASC ";
        /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -start- */
        $items = $this->Db->execute($sqlCmd);
        if($result === false){
            return false;
        }
        
        $query = "DROP TEMPORARY TABLE `".DATABASE_PREFIX."repository_log_".$this->TransStartDate."` ;";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        }
                
        if (count($items)>0) {
            $width_max = $items[0]['cnt'];
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
                if($this->is_csv_log == 1){
                    // CSV
                    if(strlen($CSV) == 0){
                        $csv_file = WEBAPP_DIR."/uploads/repository/log_result_".$this->TransStartDate.".csv";
                        $csv_fp = fopen($csv_file, "w");
                    }
                    $CSV = "true";
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -start- */
                    fwrite($csv_fp, "\"".$items[$ii]['item_id']."\",\"".$title."\",\"".$items[$ii]['cnt']."\"".self::CRLF);
                    /* Mod add item_id to custom report 2012/8/17 Tatsuya.Koyasu -end- */
                } else if($this->is_csv_log == 2){
                    // TSV
                    if(strlen($TSV) == 0){
                        $tsv_file = WEBAPP_DIR."/uploads/repository/log_result_".$this->TransStartDate.".tsv";
                        $tsv_fp = fopen($tsv_file, "w");
                        // add header 2014/3/6 R.Matsuura --start--
                        $type="";
                        switch ( $this->type_log ) {
                            case 2:
                                // download item log
                                $type = $this->smartyAssign->getLang("repository_log_download");
                                break;
                            case 3:
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
                    $TSV = "true";
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
        if($this->is_csv_log == 1){
            // CSV
            fclose($csv_fp);
            $fp = fopen( $csv_file, "rb" );
            $file = fread( $fp, filesize($csv_file) );
            fclose($fp);
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$file, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
            // delet CSV file
            unlink($csv_fp);
        } else if($this->is_csv_log == 2){
            // TSV
            fclose($tsv_fp);
            $fp = fopen( $tsv_file, "rb" );
            $file = fread( $fp, filesize($tsv_file) );
            fclose($fp);
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$file, "log.tsv", "text/tab-separated-values");
            // delet TSV file
            unlink($tsv_fp);
        } else {
            $html_tmp = $html;
            $html = "";
            $html .= '<center>';
            $html .= '<div class="paging ofx_auto ofy_hidden pd02" id="print_area_repos">';
            $html .= '<table class="tb_repos text_color full">';
            $html .= '<tr><th colspan="3" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
            if($this->type_log == 2){
                $html .= $this->smartyAssign->getLang("repository_log_item_download");
            } else if($this->type_log == 3){
                $html .= $this->smartyAssign->getLang("repository_log_item_view");
            }
            $html .= '</div></th></tr>';
            $html .= '<tr>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_itemname").'</th>';
            $html .= '<th class="th_col_repos ac" colspan="2">';
            if($this->type_log == 2){
                $html .= $this->smartyAssign->getLang("repository_log_num_download");
            } else if($this->type_log == 3){
                $html .= $this->smartyAssign->getLang("repository_log_num_refer");
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

    // Add lang resource 2008/11/27 Y.Nakao --end--

    // Add host log 2010/02/28 Y.Nakao --start--
    /**
     * insert, download, view log result per Host format HTML
     *
     */
    function getPerHost()
    {
        $CSV = "";
        $TSV = "";
        $html = "";
        $html_all = "";
        $cnt_all_host = 0;
        $cnt_host = 0;
        // ------------------------------------------
        // set log start - end date
        // ------------------------------------------
        $sy = sprintf("%04d",$this->sy_log);
        $sm = sprintf("%02d",$this->sm_log);
        $sd = sprintf("%02d",$this->sd_log);
        $ey = sprintf("%04d",$this->ey_log);
        $em = sprintf("%02d",$this->em_log);
        $ed = sprintf("%02d",$this->ed_log);
        
//      $su = mktime(0,0,0,$sm,$sd,$sy);
//      $eu = mktime(0,0,0,$em,$ed,$ey);
//      // second per day
//      $sec = 60 * 60 * 24; // 60[s] * 60[m] * 24[h]
        // get date
        $date_list = array();
//      for ( $i = $su;$i <= $eu;$i += $sec ) {
//          array_push($date_list, date("Y-m-d", $i));
//      }
        
        $s_date = $sy."-".$sm."-".$sd;
        $e_date = $ey."-".$em."-".$ed;
        $query = "SELECT DATEDIFF('".$e_date."', '".$s_date."') AS date_diff;";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $diff = $result[0]['date_diff'];
        for ($i=0;$i<=$diff;$i++) {
            $query = "SELECT DATE_ADD('".$s_date."', INTERVAL ".$i." DAY) AS str_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            array_push($date_list, $result[0]['str_date']);
        }
        
        // ------------------------------------------
        // set log result type lang resource
        // ------------------------------------------
        switch ( $this->type_log ) {
            case 1:
                // insert item log
                $type = $this->smartyAssign->getLang("repository_log_entry");
                break;
            case 2:
                // download item log
                $type = $this->smartyAssign->getLang("repository_log_download");
                break;
            case 3:
                // view item log
                $type = $this->smartyAssign->getLang("repository_log_refer");
                break;
            default:break;
        }
        
        // add header 2014/3/6 R.Matsuura --start--
        if($this->is_csv_log == 2){
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
        
        // ------------------------------------------
        // read log file per host
        // ------------------------------------------
        $width_max = 0;
        $log_per_host = array();
        $year = $sy;
        $month = $sm;
        while(intval($year.$month) <= intval($ey.$em)){
            if(file_exists(WEBAPP_DIR."/logs/weko/logfile/log_per_host_$year$month.txt")){
                $fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_host_$year$month.txt", "r");
                while(!feof($fp)){
                    // read line
                    // record_date  ip_address  host    item_count  download_count  view_count
                    $file_line = fgets($fp);
                    $file_line = str_replace("\r\n", "", $file_line);
                    $file_line = str_replace("\n", "", $file_line);
                    $line = split("\t", $file_line);
                    if(in_array($line[0], $date_list)){
                        if(array_key_exists($line[1], $log_per_host)){
                            $log_per_host[$line[1]]['cnt'] += $line[intval($this->type_log+2)];
                        } else if(intval($line[intval($this->type_log+2)]) > 0){
                            $log_per_host[$line[1]] = array();
                            $log_per_host[$line[1]]['host'] = $line[2];
                            $log_per_host[$line[1]]['ip_address'] = $line[1];
                            $log_per_host[$line[1]]['cnt'] += $line[intval($this->type_log+2)];
                        }
                    }
                }
                fclose($fp);
            }
            //$date = mktime(0,0,0,$month+1,1,$year);
            //$year = date("Y", $date);
            //$month = date("m", $date);
            
            $query = "SELECT DATE_FORMAT(DATE_ADD('".$year."-".$month."-01', INTERVAL 1 MONTH), '%Y') AS tmp_year,".
                     " DATE_FORMAT(DATE_ADD('".$year."-".$month."-01', INTERVAL 1 MONTH), '%m') AS tmp_month;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            $year = $result[0]['tmp_year'];
            $month = $result[0]['tmp_month'];
        }

        // ------------------------------------------
        // get item download log per item
        // ------------------------------------------
        $sqlCmd = "SELECT host, ip_address, count(*) AS cnt ";
        // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
        if($this->type_log == 2){
            $sqlCmd .= "  FROM (".
                       "      SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
                       "       ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent, host".
                       "      FROM ". DATABASE_PREFIX ."repository_log".
                       "      WHERE operation_id=".$this->type_log." ) AS log";
        } else {
            $sqlCmd .= "   FROM ".DATABASE_PREFIX ."repository_log AS log ";
        }
        // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
        $sqlCmd .= " WHERE log.record_date >= '$sy-$sm-$sd 00:00:00.000' ". 
                   " AND log.record_date <= '$ey-$em-$ed 23:59:99.999' ".
                   " AND log.operation_id='$this->type_log' ".
        //$this->log_exception.
                   $this->robots_exclusion.
                   " GROUP BY ip_address ".
                   " ORDER BY cnt DESC , ip_address ASC; ";
        $items = $this->Db->execute($sqlCmd);
        
//      print_r($log_per_host);
//      echo "<br/><br/>";
//      print_r($items);
        
        // マージ
        for($ii=0; $ii<count($items);$ii++){
            if(array_key_exists($items[$ii]['ip_address'], $log_per_host)){
                $log_per_host[$items[$ii]['ip_address']]['cnt'] += $items[$ii]['cnt'];
            } else {
                $log_per_host[$items[$ii]['ip_address']]['ip_address'] = $items[$ii]['ip_address'];
                $log_per_host[$items[$ii]['ip_address']]['host'] = $items[$ii]['host'];
                $log_per_host[$items[$ii]['ip_address']]['cnt'] = $items[$ii]['cnt'];
            }
        }
        
        // ソート
        $items = array();
        foreach ($log_per_host as $ip => $val){
            array_push($items, $val);
            for($ii=count($items)-1;$ii>0;$ii--){
                if(intval($items[$ii-1]['cnt']) < intval($items[$ii]['cnt'])){
                    $tmp = $items[$ii-1];
                    $items[$ii-1] = $items[$ii];
                    $items[$ii] = $tmp;
                } else {
                    break;
                }
            }
        }
        
//      echo "<br/><br/>";
//      print_r($items);
        $cnt_width = 0;
        if ( count($items)>0 ) {
            $width_max_all = $items[0]['cnt'];
            $width_max = $items[0]['cnt'];
            $cnt_width = mb_strlen($width_max);
            for ( $ii=0; $ii<count($items) && $width_max>0; $ii++ )
            {
                // 除外対象のホスト
                if(is_numeric(strpos($this->log_exception, "'".$items[$ii]['ip_address']."'")) ){
                    if($this->is_csv_log == 1){
                        // CSV
                        //$CSV = $CSV.$items[$ii]['host'].",".$items[$ii]['ip_address'].",".$items[$ii]['cnt']."".self::CRLF;
                    }else if($this->is_csv_log == 2){
                        // TSV
                        //$TSV = $TSV.$items[$ii]['host']."\t".$items[$ii]['ip_address']."\t".$items[$ii]['cnt']."".self::CRLF;
                    } else {
                        // HTML
                        $width = sprintf("%.0f",100*($items[$ii]['cnt']/$width_max_all));
                        if($cnt_all_host%2==0){
                            $html_all .= '<tr class="list_line_repos1">';
                        } else {
                            $html_all .= '<tr class="list_line_repos2">';
                        }
                        $html_all .= '<td class="list_paging" style="text-align: left;" ><span class="al" style="width:100px;" >'.wordwrap($items[$ii]['host'], 20, "<br />",1).'</span></td>';
                        $html_all .= '<td class="ac">'.$items[$ii]['ip_address'].'</td>';
                        $html_all .= '<td class="list_paging nobr" align="center">';
                        $html_all .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_except")
                            .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'1,'. $this->type_log. ', 0, createLogErrorMsg());'.'" disabled="true" />';
                        $html_all .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_release")
                            .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'2,'. $this->type_log. ', 0, createLogErrorMsg());'.'"/>';
                        $html_all .= '</td>';
                        $html_all .= '<td class="list_paging ar" style="width:'.$cnt_width.'ex;" ><span style="width:'.$cnt_width.'ex;" >'.$items[$ii]['cnt'].'</span></td>';
                        $html_all .= '<td class="td_graph_repos list_paging" style="text-align: left;"><img src="./images/repository/default/graph_bar.gif" style="text-align: left; width: '.$width.'%; height: 10px;"></td>';
                        $html_all .= '</tr>';
                    }
                    $cnt_all_host++;
                // 除外対象でないホスト
                } else {
                    if($this->is_csv_log == 1){
                        // CSV
                        $CSV = $CSV."\"".$items[$ii]['host']."\",\"".$items[$ii]['ip_address']."\",\"".$items[$ii]['cnt']."\"".self::CRLF;
                    } else if($this->is_csv_log == 2){
                        // TSV
                        $TSV = $TSV."\"".$items[$ii]['host']."\"\t\"".$items[$ii]['ip_address']."\"\t\"".$items[$ii]['cnt']."\"".self::CRLF;
                    } else {
                        // HTML
                        if($cnt_host == 0 )
                        {
                            $width_max =  $items[$ii]['cnt'];
                        }
                        $width = sprintf("%.0f",100*($items[$ii]['cnt']/$width_max));
                        if($cnt_host%2==0){
                            $html .= '<tr class="list_line_repos1">';
                        } else {
                            $html .= '<tr class="list_line_repos2">';
                        }
                        $html .= '<td class="list_paging" style="text-align: left;" ><span class="al" style="width:100px" >'.wordwrap($items[$ii]['host'], 20, "<br />",1).'</span></td>';
                        $html .= '<td class="list_paging ac">'.$items[$ii]['ip_address'].'</td>';
                        $html .= '<td class="list_paging nobr" align="center">';
                        $html .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_except")
                            .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'1,'. $this->type_log. ', 0, createLogErrorMsg());'.'"/>';
                        $html .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_release")
                            .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'2,'. $this->type_log. ', 0, createLogErrorMsg());'.'" disabled="true" />';
                        $html .= '</td>';
                        $html .= '<td class="list_paging ar" style="width:'.$cnt_width.'ex;" ><span style="width:'.$cnt_width.'ex;" >'.$items[$ii]['cnt'].'</span></td>';
                        //$html .= '<td class="list_paging" style="width:'.$cnt_width.'ex;">'.$items[$ii]['cnt'].'</td>';
                        $html .= '<td class="td_graph_repos list_paging" style="text-align: left;"><img src="./images/repository/default/graph_bar.gif" style="text-align: left; width: '.$width.'%; height: 10px;"></td>';
                        $html .= '</tr>';

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
                        $html_all .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_except")
                                                    .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'1,'. $this->type_log. ', 0, createLogErrorMsg());'.'"/>';
                        $html_all .= '<input class="btn_white" type="button" value="'.$this->smartyAssign->getLang("repository_release")
                                                    .'" onclick="javascript: ChangeHostStatus('
                            ."'".$items[$ii]['ip_address']."'".','.'2,'. $this->type_log. ', 0, createLogErrorMsg());'.'" disabled="true" />';
                        $html_all .= '</td>';
                        $html_all .= '</td>';
                        $html_all .= '<td class="list_paging ar" style="width:'.$cnt_width.'ex;" ><span style="width:'.$cnt_width.'ex;" >'.$items[$ii]['cnt'].'</span></td>';
                        //$html_all .= '<td class="list_paging" style="width:'.$cnt_width.'ex;">'.$items[$ii]['cnt'].'</td>';
                        $html_all .= '<td class="td_graph_repos list_paging" style="text-align: left;"><img src="./images/repository/default/graph_bar.gif" style="text-align: left; width: '.$width.'%; height: 10px;"></td>';
                        $html_all .= '</tr>';
                    }
                    $cnt_host++;
                    $cnt_all_host++;
                }
            }
        }
        else
        {
            $html .= '<tr class="tr_repos">';
            $html .= '<td colspan=5 class="al">'.$this->smartyAssign->getLang("repository_log_nodata").'</td>';
            $html .= '</tr>';
        }

        if($this->is_csv_log == 1){
            // CSV
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$CSV, "log.csv", "text/csv");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        } else if($this->is_csv_log == 2){
            // TSV
            // download
            $this->repositoryDownload->download(pack('C*',0xEF,0xBB,0xBF).$TSV, "log.tsv", "text/tab-separated-values");
        } else {
            // HTML
            $html_tmp = $html;
            $html = "";
            $html .= '<center>';
            $html .= '<div id="print_area_repos" name="print_area_repos" class="ofx_auto ofy_hidden pd02" style="display:block;">';
            $html .= '<table class="tb_repos text_color full">';
            $html .= '<colgroup span="2" width="100px">';
            $html .= '<colgroup width="160px">';
            $html .= '<colgroup width="'.$cnt_width.'ex;">';
            $html .= '<colgroup width="*%">';
            //          $html .= '<tr><th colspan="5" style="text-align: left;"><div class="th_repos">';
            $html .= '<tr><th colspan="5" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
            $html .= '<p class="al fl">';
            $html .= $this->smartyAssign->getLang("repository_log_per_host").
            $this->smartyAssign->getLang("repository_log_of").
            $type.
            $this->smartyAssign->getLang("repository_log_num").'</p>';
            $html .= '<p class="ar"><a href="javascript: hostdisplayClicked()" name="host_display_all" id="host_display_all" style="text-align: left;">'.$this->smartyAssign->getLang("repository_log_host_all_show").'</a>';
            $html .= '</p></div></th>';
            //$html = $html.'<th class="th_col_repos" colspan="3" style="text-align: right;" >';
            //$html = $html.'<a href="javascript: hostdisplayClicked()" name="host_display_all" id="host_display_all" style="text-align: left;">'.$this->smartyAssign->getLang("repository_log_host_all_show").'</a></th>';
            $html .= '<tr>';
            $html .= '<th class="th_col_repos ac" style="width:100px">'.$this->smartyAssign->getLang("repository_log_host").'</th>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_ip_address").'</th>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_except")."/".$this->smartyAssign->getLang("repository_release").'</th>';
            $html .= '<th class="th_col_repos ac" colspan="2">'.$type.$this->smartyAssign->getLang("repository_log_num").'</th>';
            $html .= '</tr>';
            $html .= $html_tmp;
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div class="paging" id="print_paging" name="print_paging" style="display:block;">';
            $html .= '<a class="btn_blue white" href="javascript: printGraph_repos('."'print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
            $html .= '</div>';
            $html .= '</center>';
                
            // HTML(all-host)
            $html .= '<center>';
            $html .= '<div id="all_print_area_repos" name="all_print_area_repos" class="ofx_auto ofy_hidden pd02" style="display:none;">';
            $html .= '<table class="tb_repos text_color full" >';
            $html .= '<colgroup span="2" width="100px">';
            $html .= '<colgroup width="160px">';
            $html .= '<colgroup width="'.$cnt_width.'ex;">';
            $html .= '<colgroup width="*%">';
            $html .= '<tr><th colspan="5" style="text-align: left;"><div class="th_repos_title_bar text_color mb10">';
            $html .= '<p class="al fl">';
            $html .= $this->smartyAssign->getLang("repository_log_per_host").
            $this->smartyAssign->getLang("repository_log_of").$type.$this->smartyAssign->getLang("repository_log_num").'</p>';
            $html .= '<p class="ar"><a href="javascript: hostdisplayClicked()" name="host_display" id="host_display" style="text-align: left;">';
            $html .= $this->smartyAssign->getLang("repository_log_host_limit").'</a>';
            $html .= '</p></div></th>';
            //$html = $html.'<th class="th_col_repos" colspan="3" style="text-align: right;" >';
            //$html = $html.'<a href="javascript: hostdisplayClicked()" name="host_display" id="host_display" style="text-align: left;">'.
            //$this->smartyAssign->getLang("repository_log_host_limit").'</a></th>';
            $html .= '<tr>';
            $html .= '<th class="th_col_repos ac" style="width:100px">'.$this->smartyAssign->getLang("repository_log_host").'</th>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_log_ip_address").'</th>';
            $html .= '<th class="th_col_repos ac">'.$this->smartyAssign->getLang("repository_except")."/".$this->smartyAssign->getLang("repository_release").'</th>';
            $html .= '<th class="th_col_repos ac" colspan="2">'.$type.$this->smartyAssign->getLang("repository_log_num").'</th>';
            $html .= '</tr>';
            $html .= $html_all;
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div class="paging" id="all_print_paging" name="all_print_paging" style="display:none;">';
            $html .= '<a class="btn_blue white" href="javascript: printGraph_repos('."'all_print_area_repos'".')">'.$this->smartyAssign->getLang("repository_log_print").'</a>';
            $html .= '</div>';
            $html .= '</center>';
            // download
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $this->repositoryDownload->download($html, "log.html");
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        }

    }
    // Add host log 2010/02/28 Y.Nakao --end--
}


?>
