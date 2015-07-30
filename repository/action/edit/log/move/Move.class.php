<?php
// --------------------------------------------------------------------
//
// $Id: Move.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/logreport/Logreport.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatistics.class.php';

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
class Repository_Action_Edit_Log_Move extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	var $smartyAssign = null;
	
	// request parameter
	var $start_year = null;
	var $start_month = null;
	var $end_year = null;
	var $end_month = null;
	var $log_term = null;
	
	// login info
	var $user_id = null;
	var $login_id = null;
	var $password = null;
	var $user_authority_id = null;
	var $authority_id = null;
	
	// member
	var $log_exception = "";
	
	function execute()
	{
		try {
			ini_set('memory_limit', -1);
			// -------------------------------------------
			// init
			// -------------------------------------------
			$result = $this->initAction();
			if ( $result === false ) {
				$this->exitAction(); 
				echo "ERROR";
				exit();
			}
			if($this->Db == null){
	    		$container =& DIContainerFactory::getContainer();
	    		$this->Db =& $container->getComponent("DbObject");
	    	}
	    	if($this->Session == null){
	    		$container =& DIContainerFactory::getContainer();
				$this->Session =& $container->getComponent("Session");
	    	}
			
			// -----------------------------------------------
			// get lang resource
			// -----------------------------------------------
			$this->smartyAssign = $this->Session->getParameter("smartyAssign");
			
			// -------------------------------------------
			// check login
			// -------------------------------------------
			$user_id = $this->Session->getParameter("_user_id");
			if($user_id != "0"){
				// check auth
				$this->user_authority_id = $this->Session->getParameter("_user_auth_id");
				$this->authority_id = $this->Session->getParameter("_auth_id");
			} else if($this->login_id != null && strlen($this->login_id) > 0 &&
				$this->password != null && strlen($this->password) > 0){
				$result = $this->checkLogin($this->login_id, $this->password, $Result_List, $error_msg);
				if($result === false){
					echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
						 $this->smartyAssign->getLang("repository_log_move_error_login");
					exit();
				}
			} else {
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_login");
				exit();
			}

			// -------------------------------------------
			// Check request parameter, auto fill date
			//   start date <- first month
			//   end date <- first month + period or last month
			// -------------------------------------------
			if(strlen($this->start_year) == 0 && strlen($this->start_month) == 0){
				// not setting start date, auto fill first month
				$query = "SELECT DATE_FORMAT( MIN( record_date ) , '%Y' ) AS sy, ".
						" DATE_FORMAT( MIN( record_date ) , '%m' ) AS sm ".
						" FROM ".DATABASE_PREFIX."repository_log ";
				$result = $this->Db->execute($query);
				if($result === false || count($result) != 1){
					// error
					$this->exitAction(); 
					echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
						 $this->smartyAssign->getLang("repository_log_move_error_date");
					exit();
				}
				$this->start_year = $result[0]['sy'];
				$this->start_month = $result[0]['sm'];
			}
			if(strlen($this->end_year) == 0 && strlen($this->end_month) == 0){
				// not setting end date
				if(strlen($this->log_term) == 0 || !is_numeric($this->log_term)){
					// not setting term, auto fill last month
					//$today = getdate();
					//$lastMonth = mktime(0, 0, 0, $today['mon'] - 1, 1, $today['year']);
					//$this->end_year = date("Y", $lastMonth);
					//$this->end_month = date("m", $lastMonth);
					
					$query = "SELECT DATE_FORMAT( DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y') AS ey, ".
							 "DATE_FORMAT( DATE_SUB(NOW(), INTERVAL 1 MONTH), '%m') AS em;";
					$result = $this->Db->execute($query);
					if($result === false || count($result) != 1){
						// error
						$this->exitAction(); 
						echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
							 $this->smartyAssign->getLang("repository_log_move_error_date");
						exit();
					}
					$this->end_year = $result[0]['ey'];
					$this->end_month = $result[0]['em'];
				} else {
					// setting term, fill month
					//$tarmMonth = mktime(0, 0, 0, $this->start_month + $this->log_term-1, 1, $this->start_year);
					//$this->end_year = date("Y", $tarmMonth);
					//$this->end_month = date("m", $tarmMonth);
					
					$query = "SELECT DATE_FORMAT( DATE_ADD('".$this->start_year."-".$this->start_month."-1', INTERVAL ".($this->log_term-1)." MONTH), '%Y') AS ey, ".
							 "DATE_FORMAT( DATE_ADD('".$this->start_year."-".$this->start_month."-1', INTERVAL ".($this->log_term-1)." MONTH), '%m') AS em;";
					$result = $this->Db->execute($query);
					if($result === false || count($result) != 1){
						// error
						$this->exitAction(); 
						echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
							 $this->smartyAssign->getLang("repository_log_move_error_date");
						exit();
					}
					$this->end_year = $result[0]['ey'];
					$this->end_month = $result[0]['em'];
				}
			}
			
			// -------------------------------------------
			// Check at period
			// -------------------------------------------
			// 0 burials
			$this->start_year = sprintf("%04d", $this->start_year);
			$this->start_month = sprintf("%02d", $this->start_month);
			$this->end_year = sprintf("%04d", $this->end_year);
			$this->end_month = sprintf("%02d", $this->end_month);
			// end date older than start date
			$start_date = $this->start_year.$this->start_month;
			$end_date = $this->end_year.$this->end_month;
			if(intval($start_date) > intval($end_date)){
				// when start date older than end date, error
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_date");
				exit();
			}
			// end date now month is error
			//$today = getdate();
			$query = "SELECT DATE_FORMAT(NOW(), '%Y') AS tmp_y, ".
					 "DATE_FORMAT(NOW(), '%m') AS tmp_m;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				// error
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_date");
				exit();
			}
//			if(	$this->end_year == sprintf("%04d", $today['year']) && 
//				$this->end_month == sprintf("%02d", $today['mon'])){
			if(	$this->end_year == sprintf("%04d", $result[0]['tmp_y']) && 
				$this->end_month == sprintf("%02d", $result[0]['tmp_m'])){
				// when start date older than end date, error
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_date");
				exit();
			}
			
			// Add log exclusion from user-agaent 2011/05/09 H.Ito --start--
            $this->log_exception = $this->createLogExclusion();
            // Add log exclusion from user-agaent 2011/05/09 H.Ito --end--
			
			// -------------------------------------------
			// dump log data
			// -------------------------------------------
			$result = $this->dumpLogData();
			if($result === false){
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_dump");
				exit();
			}
			
			// -------------------------------------------
			// make log report
			// -------------------------------------------
			$result = $this->makeLogReport();
			if($result === false){
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_report");
				exit();
			}
			
            // -------------------------------------------
            // Aggregate usage statistics
            // -------------------------------------------
            $RepositoryUsagestatistics = new RepositoryUsagestatistics($this->Session, $this->Db, $this->TransStartDate);
            if(!$RepositoryUsagestatistics->aggregateUsagestatistics())
            {
                $this->failTrans();
                $this->exitAction();
                echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
                     $this->smartyAssign->getLang("repository_log_move_error_usagestatistics");
                exit();
            }
            
			// -------------------------------------------
			// move log data
			// -------------------------------------------
			$result = $this->moveLogData();
			if($result === false){
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_move");
				exit();
			}
			
			// -------------------------------------------
			// delete log data
			// -------------------------------------------
			$result = $this->deleteLogData();
			if($result === false){
				$this->exitAction(); 
				echo $this->smartyAssign->getLang("repository_log_move_error")."\n".
					 $this->smartyAssign->getLang("repository_log_move_error_delete");
				exit();
			}
			
			// -------------------------------------------
			// end
			// -------------------------------------------
			$this->exitAction();
			
			echo $this->smartyAssign->getLang("repository_log_move_success");
			
			exit();
			
			 
		} catch ( RepositoryException $Exception) {
			// error log
			$this->logFile(
	        	"SampleAction",	
	        	"execute",
			$Exception->getCode(),
			$Exception->getMessage(),
			$Exception->getDetailMsg() );
			 
			// end action
			$this->exitAction();

			// exit
			echo $this->smartyAssign->getLang("repository_log_move_error");
			exit();
		}
	}
	
	/**
	 * move log data
	 * between start date to end date
	 *
	 * @return bool retult
	 */
	function moveLogData(){
		// log move to file
		// -----------------------------------------------
		// set start date and end date
		// -----------------------------------------------
		$query = "SELECT SUBSTRING(MIN(CAST( record_date AS DATE )),9,2) AS start_date ".
				" FROM ".DATABASE_PREFIX."repository_log ";
		$result = $this->Db->execute($query);
		if($result === false || count($result) != 1){
			return false;
		}
		$start_day = $result[0]['start_date'];
		$start_date = $this->start_year."-".$this->start_month."-".$start_day." 00:00:00.000";
		$end_date = $this->end_year."-".$this->end_month."-31 23:59:59.999";
		//$last_day = date('t', mktime(0,0,0,$this->end_month,'01',$this->end_year));
		
		$query = "SELECT DATE_FORMAT(LAST_DAY('".$this->end_year."-".$this->end_month."-1'), '%d') AS lastday;";
		$result = $this->Db->execute($query);
		if($result === false || count($result) != 1){
			return false;
		}
		$last_day = $result[0]['lastday'];

		// ------------------------------------------
		// make move folder
		// ------------------------------------------
		if(!file_exists(WEBAPP_DIR."/logs/weko/logfile")){
			mkdir(WEBAPP_DIR."/logs/weko/logfile");
		}
		chmod ( WEBAPP_DIR."/logs/weko/logfile", 0300 );
		
		// ------------------------------------------
		// fill date
		// ------------------------------------------
		//$su = mktime(0,0,0,$this->start_month,$start_day,$this->start_year);
		//$eu = mktime(0,0,0,$this->end_month,$last_day,$this->end_year);
		//$sec = 60 * 60 * 24; // 60[s] * 60[m] * 24[h]
		
		// ファイル書き込み用配列初期化
		$log_per_date = array();
		$log_per_host = array();
		$log_per_item = array();
//		for ( $i = $su;$i <= $eu;$i += $sec ) {
//			$date = date("Y-m-d", $i);
//			
//			// log per date
//			$log_per_date[$date]['record_date'] = $date;
//			$log_per_date[$date]['year_week'] = "";
//			$query = "SELECT YEARWEEK('$date') AS d2 ";
//			$result = $this->Db->execute($query);
//			if($result !== false && count($result) == 1){
//				$log_per_date[$date]['year_week'] = $result[0]['d2'];
//			}
//			$log_per_date[$date]['item_count'] = 0;
//			$log_per_date[$date]['download_count'] = 0;
//			$log_per_date[$date]['view_count'] = 0;
//		}

		$s_date = $this->start_year."-".$this->start_month."-".$start_day;
		$e_date = $this->end_year."-".$this->end_month."-".$last_day;
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
			$date = $result[0]['str_date'];
			
			// log per date
			$log_per_date[$date]['record_date'] = $date;
			$log_per_date[$date]['year_week'] = "";
			$query = "SELECT YEARWEEK('$date') AS d2 ";
			$result = $this->Db->execute($query);
			if($result !== false && count($result) == 1){
				$log_per_date[$date]['year_week'] = $result[0]['d2'];
			}
			$log_per_date[$date]['item_count'] = 0;
			$log_per_date[$date]['download_count'] = 0;
			$log_per_date[$date]['view_count'] = 0;
		}
			
		// ------------------------------------------
		// calc log statistics per date
		// ------------------------------------------
			
			// ------------------------------------------
			// fill insert item count
			// ------------------------------------------
			$query = "SELECT CAST( log.record_date AS DATE ) AS d1, count(*) AS cnt ".
					" FROM  ".DATABASE_PREFIX."repository_log AS log ".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '1' ".
					$this->log_exception.
					" GROUP BY d1 ";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0; $ii<count($result); $ii++){
				$log_per_date[$result[$ii]['d1']]['item_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// fill download count
			// ------------------------------------------
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
			$query = "SELECT CAST( log.record_date AS DATE ) AS d1, count(*) AS cnt ".
					 " FROM (".
					 "   SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
					 "   ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
					 "   FROM ". DATABASE_PREFIX ."repository_log ".
					 "   WHERE operation_id='2' ) AS log".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '2' ".
					$this->log_exception.
					" GROUP BY d1 ";
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0; $ii<count($result); $ii++){
				$log_per_date[$result[$ii]['d1']]['download_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// fill view count
			// ------------------------------------------
			$query ="SELECT CAST( log.record_date AS DATE ) AS d1, count(*) AS cnt ".
					" FROM  ".DATABASE_PREFIX."repository_log AS log ".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '3' ".
					$this->log_exception.
					" GROUP BY d1 ".
					" ORDER BY d1 ";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0; $ii<count($result); $ii++){
				$log_per_date[$result[$ii]['d1']]['view_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// make log file(yyyy.txt)
			// ------------------------------------------
			$year = "";
			foreach ($log_per_date as $date => $val){
				if(substr($date, 0, 4) != $year){
					fclose($fp);
					$year = substr($date, 0, 4);
					$fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_date_$year.txt", "a");
				}
				fwrite($fp, $val['record_date']."\t".
							$val['year_week']."\t".
							$val['item_count']."\t".
							$val['download_count']."\t".
							$val['view_count']."\n"
				);
			}
			fclose($fp);
			
		// ------------------------------------------
		// calc log statistics per host
		// ------------------------------------------
			
			// ------------------------------------------
			// fill item count
			// ------------------------------------------
			$query = " SELECT CAST( log.record_date AS DATE ) AS d1, ip_address AS addr, host, count(*) AS cnt ".
					" FROM  ".DATABASE_PREFIX."repository_log AS log ".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '1' ".
					$this->log_exception.
					" GROUP BY d1, addr ".
					" ORDER BY d1, addr ";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0;$ii<count($result);$ii++){
				$date = $result[$ii]['d1'];
				$ip = $result[$ii]['addr'];
				if(!isset($log_per_host[$date])){
					$log_per_host[$date] = array();
				}
				if(!isset($log_per_host[$date][$ip])){
					$log_per_host[$date][$ip] = array();
					$log_per_host[$date][$ip]['record_date'] = $result[$ii]['d1'];
					$log_per_host[$date][$ip]['ip_address'] = $result[$ii]['addr'];
					$log_per_host[$date][$ip]['host'] = $result[$ii]['host'];
					$log_per_host[$date][$ip]['item_count'] = 0;
					$log_per_host[$date][$ip]['download_count'] = 0;
					$log_per_host[$date][$ip]['view_count'] = 0;
				}
				$log_per_host[$date][$ip]['item_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// fill download count
			// ------------------------------------------
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
			$query =" SELECT CAST( log.record_date AS DATE ) AS d1, ip_address AS addr, host, count(*) AS cnt ".
					" FROM (".
					"   SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
					"	ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent, host".
					"   FROM ". DATABASE_PREFIX ."repository_log ".
					"   WHERE operation_id='2' ) AS log".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '2' ".
					$this->log_exception.
					" GROUP BY d1, addr ".
					" ORDER BY d1, addr ";
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0;$ii<count($result);$ii++){
				$date = $result[$ii]['d1'];
				$ip = $result[$ii]['addr'];
				if(!isset($log_per_host[$date])){
					$log_per_host[$date] = array();
				}
				if(!isset($log_per_host[$date][$ip])){
					$log_per_host[$date][$ip] = array();
					$log_per_host[$date][$ip]['record_date'] = $result[$ii]['d1'];
					$log_per_host[$date][$ip]['ip_address'] = $result[$ii]['addr'];
					$log_per_host[$date][$ip]['host'] = $result[$ii]['host'];
					$log_per_host[$date][$ip]['item_count'] = 0;
					$log_per_host[$date][$ip]['download_count'] = 0;
					$log_per_host[$date][$ip]['view_count'] = 0;
				}
				$log_per_host[$date][$ip]['download_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// fill view count
			// ------------------------------------------
			$query =" SELECT CAST( log.record_date AS DATE ) AS d1, ip_address AS addr, host, count(*) AS cnt ".
					" FROM  ".DATABASE_PREFIX."repository_log AS log ".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '3' ".
					$this->log_exception.
					" GROUP BY d1, addr ".
					" ORDER BY d1, addr ";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0;$ii<count($result);$ii++){
				$date = $result[$ii]['d1'];
				$ip = $result[$ii]['addr'];
				if(!isset($log_per_host[$date])){
					$log_per_host[$date] = array();
				}
				if(!isset($log_per_host[$date][$ip])){
					$log_per_host[$date][$ip] = array();
					$log_per_host[$date][$ip]['record_date'] = $result[$ii]['d1'];
					$log_per_host[$date][$ip]['ip_address'] = $result[$ii]['addr'];
					$log_per_host[$date][$ip]['host'] = $result[$ii]['host'];
					$log_per_host[$date][$ip]['item_count'] = 0;
					$log_per_host[$date][$ip]['download_count'] = 0;
					$log_per_host[$date][$ip]['view_count'] = 0;
				}
				$log_per_host[$date][$ip]['view_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// make log file(log_per_host_yyyyMM.txt)
			// ------------------------------------------
			$month = "";
			foreach ($log_per_host as $date => $list){
				if(substr($date, 0, 4).substr($date, 5, 2) != $month){
					fclose($fp);
					$month = substr($date, 0, 4).substr($date, 5, 2);
					$fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_host_".$month.".txt", "a"); 
				}
				foreach ($list as $ip => $val){
					fwrite($fp, $val['record_date']."\t".
								$val['ip_address']."\t".
								$val['host']."\t".
								$val['item_count']."\t".
								$val['download_count']."\t".
								$val['view_count']."\n"
					);
				}
			}
			fclose($fp);
			
		// ------------------------------------------
		// calc log statistics per item
		// ------------------------------------------
		
			// ------------------------------------------
			// fill download count
			// ------------------------------------------
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
			$query =" SELECT DISTINCT CAST( log.record_date AS DATE ) AS d1, item_id, item_no, count(*) AS cnt ".
					" FROM (".
					"   SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,".
					"	ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent".
					"   FROM ". DATABASE_PREFIX ."repository_log ".
					"   WHERE operation_id='2' ) AS log".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '2' ".
					$this->log_exception.
					" GROUP BY d1, item_id, item_no ".
					" ORDER BY d1, item_id, item_no; ";
			// Modify for remove IE Continuation log K.Matsuo 2011/11/17 --end-- 
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0;$ii<count($result);$ii++){
				$date = $result[$ii]['d1'];
				$item_id = $result[$ii]['item_id'];
				$item_no = $result[$ii]['item_no'];
				if(!isset($log_per_item[$date])){
					$log_per_item[$date] = array();
				}
				if(!isset($log_per_item[$date][$item_id])){
					$log_per_item[$date][$item_id] = array();
					$log_per_item[$date][$item_id][$item_no] = array();
					$log_per_item[$date][$item_id][$item_no]['record_date'] = $date;
					$log_per_item[$date][$item_id][$item_no]['item_id'] = $item_id;
					$log_per_item[$date][$item_id][$item_no]['item_no'] = $item_no;
					$log_per_item[$date][$item_id][$item_no]['download_count'] = 0;
					$log_per_item[$date][$item_id][$item_no]['view_count'] = 0;
				}
				$log_per_item[$date][$item_id][$item_no]['download_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// fill view count
			// ------------------------------------------
			$query =" SELECT DISTINCT CAST( log.record_date AS DATE ) AS d1, item_id, item_no, count(*) AS cnt ".
					" FROM  ".DATABASE_PREFIX."repository_log AS log ".
					" WHERE log.record_date >= '$start_date' ".
					" AND log.record_date <= '$end_date' ".
					" AND log.operation_id = '3' ".
					$this->log_exception.
					" GROUP BY d1, item_id, item_no ".
					" ORDER BY d1, item_id, item_no; ";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			for($ii=0;$ii<count($result);$ii++){
				$date = $result[$ii]['d1'];
				$item_id = $result[$ii]['item_id'];
				$item_no = $result[$ii]['item_no'];
				if(!isset($log_per_item[$date])){
					$log_per_item[$date] = array();
				}
				if(!isset($log_per_item[$date][$item_id])){
					$log_per_item[$date][$item_id] = array();
					$log_per_item[$date][$item_id][$item_no] = array();
					$log_per_item[$date][$item_id][$item_no]['record_date'] = $date;
					$log_per_item[$date][$item_id][$item_no]['item_id'] = $item_id;
					$log_per_item[$date][$item_id][$item_no]['item_no'] = $item_no;
					$log_per_item[$date][$item_id][$item_no]['download_count'] = 0;
					$log_per_item[$date][$item_id][$item_no]['view_count'] = 0;
				}
				$log_per_item[$date][$item_id][$item_no]['view_count'] = $result[$ii]['cnt'];
			}
			
			// ------------------------------------------
			// make log file(log_per_host_yyyyMM.txt)
			// ------------------------------------------
			$month = "";
			foreach ($log_per_item as $date => $list){
				if(substr($date, 0, 4).substr($date, 5, 2) != $month){
					fclose($fp);
					$month = substr($date, 0, 4).substr($date, 5, 2);
					$fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_item_$month.txt", "a");
				}
				foreach ($list as $item_id => $item){
					foreach ($item as $item_no => $val){
						fwrite($fp, $val['record_date']."\t".
									$val['item_id']."-".$val['item_no']."\t".
									$val['download_count']."\t".
									$val['view_count']."\n"
						);
					}
				}
			}
			fclose($fp);
		
		return true;
	}
	
	/**
	 * make log report
	 * save to webapp/logs/weko/logreport/logReport_YYYYMM.zip
	 * 
	 * @return bool retult
	 */
	function makeLogReport(){
		$block_id = $this->getBlockPageId();
		$logreport = new Repository_Logreport();
		//$cnt_month = 0;
		//$end_time = mktime(0, 0, 0, $this->end_month, 1, $this->end_year);
		
		$start_date = $this->start_year."-".$this->start_month."-01";
		$end_date = $this->end_year."-".$this->end_month."-01";
		$query = "SELECT DATEDIFF('".$end_date."', '".$start_date."') AS date_diff;";
		$result = $this->Db->execute($query);
		if($result === false || count($result) != 1){
			return false;
		}
		$diff = $result[0]['date_diff'];
		$tmp_date = $start_date;
		
		//while( ($month = mktime(0, 0, 0, $this->start_month+$cnt_month, 1, $this->start_year)) <= $end_time){
		while( $diff >= 0){
			//$report_file = WEBAPP_DIR."/logs/weko/logreport/logReport_".date("Y", $month).date("m", $month).".zip";
			
			$query = "SELECT DATE_FORMAT('".$tmp_date."', '%Y') AS tmp_y, ".
					 "DATE_FORMAT('".$tmp_date."', '%m') AS tmp_m;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				return false;
			}
			$tmp_year = $result[0]['tmp_y'];
			$tmp_month = $result[0]['tmp_m'];
			$report_file = WEBAPP_DIR."/logs/weko/logreport/logReport_".$tmp_year.$tmp_month.".zip";
			
			// -----------------------------------------------
			// make log report
			//   copy source from repository/view/edit/logreport.class.php
			// -----------------------------------------------
			// -----------------------------------------------
			// init
			// -----------------------------------------------
			$logreport->smartyAssign = $this->smartyAssign;
			$logreport->Session = $this->Session;
			$logreport->Db = $this->Db;
			//$logreport->sy_log = date("Y", $month);
			//$logreport->sm_log = date("m", $month);
			$logreport->sy_log = $tmp_year;
			$logreport->sm_log = $tmp_month;
			$logreport->mail = false;
			// set start date
			$logreport->start_date = sprintf("%d-%02d-%02d",$logreport->sy_log, $logreport->sm_log,$logreport->sd_log);
			$logreport->disp_start_date = sprintf("%d-%02d",$logreport->sy_log, $logreport->sm_log);
			// set end date
			$logreport->ey_log = $logreport->sy_log;
			$logreport->em_log = $logreport->sm_log;
			$logreport->end_date = sprintf("%d-%02d-%02d",$logreport->ey_log, $logreport->em_log,$logreport->ed_log);
			$logreport->disp_end_date = sprintf("%d-%02d",$logreport->ey_log, $logreport->em_log);
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
			// setting log ecception
			$log_report->log_exception = $this->log_exception;
			
			// -----------------------------------------------
			// make
			// -----------------------------------------------
			// site license
			$log_str = $logreport->makeAccessLogReport();
			$log_file = $tmp_dir . "/logReport_SiteAccess_".
						str_replace("-", "", $logreport->disp_start_date).".tsv";
			$log_report = fopen($log_file, "w");
			fwrite($log_report, $log_str);
			fclose($log_report);
			array_push( $output_files, $log_file );
			
			// download file num
			$log_str = $logreport->makeFilePriceDownloadLogReportStr();
			$log_file = $tmp_dir . "/logReport_PayPerView_".
						str_replace("-", "", $logreport->disp_start_date).".tsv";
			$log_report = fopen($log_file, "w");
			fwrite($log_report, $log_str);
			fclose($log_report);
			array_push( $output_files, $log_file );
			
			// detail view as index
			$log_str = $logreport->makeIndexLogReport();
			$log_file = $tmp_dir . "/logReport_IndexAccess_".
						str_replace("-", "", $logreport->disp_start_date).".tsv";
			$log_report = fopen($log_file, "w");
			fwrite($log_report, $log_str);
			fclose($log_report);
			array_push( $output_files, $log_file );
			
			// detail view and download file num as supple items
			$log_str = $logreport->makeSuppleLogReport();
			$log_file = $tmp_dir . "/logReport_SuppleAccess_".
						str_replace("-", "", $logreport->disp_start_date).".tsv";
			$log_report = fopen($log_file, "w");
			fwrite($log_report, $log_str);
			fclose($log_report);
			array_push( $output_files, $log_file );
			
			// host log
			$log_str = $logreport->makeHostLogReport();
			$log_file = $tmp_dir . "/logReport_HostAccess_".
						str_replace("-", "", $logreport->disp_start_date).".tsv";
			$log_report = fopen($log_file, "w");
			fwrite($log_report, $log_str);
			fclose($log_report);
			array_push( $output_files, $log_file );
			
            // Add Flash view log 2011/03/01 Y.Nakao --start--
            $log_str = $logreport->makeFlashViewLogReport();
            $log_file = $tmp_dir . "/logReport_FlashView_".
                        str_replace("-", "", $logreport->disp_start_date).".tsv";
            $log_report = fopen($log_file, "w");
            fwrite($log_report, $log_str);
            fclose($log_report);
            array_push( $output_files, $log_file );
            // Add Flash view log 2011/03/01 Y.Nakao --end--
			
			// -----------------------------------------------
			// compress zip file
			// -----------------------------------------------
			// set zip file name
			$zip_file = "logReport_".
						str_replace("-", "", $logreport->disp_start_date).".zip";
			// compress zip file	
			File_Archive::extract(
				$output_files,
				File_Archive::toArchive($zip_file, File_Archive::toFiles( $tmp_dir."/" ))
			);
			// copy zip file
			copy($tmp_dir."/".$zip_file, $report_file);
			// delete tmp folder
			$this->removeDirectory($tmp_dir);
			if(!file_exists($report_file)){
				return false;
			}
			
			//$cnt_month++;
			$query = "SELECT DATE_ADD('".$tmp_date."', INTERVAL 1 MONTH) AS next_date;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				return false;
			}
			$tmp_date = $result[0]['next_date'];
			$query = "SELECT DATEDIFF('".$end_date."', '".$tmp_date."') AS date_diff;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				return false;
			}
			$diff = $result[0]['date_diff'];
		}
		return true;
	}
	
	/**
	 * dump log data for SQL or CSV
	 * between start date to end date
	 *
	 * @return bool retult
	 */
	function dumpLogData(){
		// check log folder exist
		if(!file_exists(WEBAPP_DIR ."/logs/weko/logdump" )){
			mkdir(WEBAPP_DIR ."/logs/weko/logdump");
		}
		// setting permission(d-wx------)
		chmod(WEBAPP_DIR ."/logs/weko/logdump", 0300);
		
		// dump file path
		//$path_dumpfile = WEBAPP_DIR ."/logs/weko/logdump/".date("Y-m-d_His");
		$query = "SELECT DATE_FORMAT(NOW(), '%Y-%m-%d_%H%i%s') AS now_date;";
		$result = $this->Db->execute($query);
		if($result === false || count($result) != 1){
			return false;
		}
		$path_dumpfile = WEBAPP_DIR ."/logs/weko/logdump/".$result[0]['now_date'];
		$path_dumpfile = str_replace('\\', '\\\\', $path_dumpfile);
		
		// check envpath for mysqldump
		$path_mysqldump = "";
		$path_env = getenv('PATH');
		$path = split(PATH_SEPARATOR, $path_env);
		for($ii=0; $ii<count($path); $ii++){
			if(	file_exists($path[$ii].DIRECTORY_SEPARATOR."mysqldump") || 
				file_exists($path[$ii].DIRECTORY_SEPARATOR."mysqldump.exe")){
				$path_mysqldump = $path[$ii];
				break;
			}
		}
		if(strlen($path_mysqldump) > 0){
			// Setting mysqldump PATH environment variable
			// try mysqldump
			$arrayList = explode("/",substr(stristr(DATABASE_DSN, "/"), 2));
			// Get of database name
			$dbname = $arrayList[1];
			$arrayList = explode(":", $arrayList[0]);
			// Get of mysql user name
			$mysqlUser = $arrayList[0];
			// Get of mysql password
			$mysqlPass = substr($arrayList[1], 0, strpos($arrayList[1], "@"));					
			//$logName = date(Ymd);
			$query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d') AS now_date;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				return false;
			}
			$logName = $result[0]['now_date'];
			$language = null;
			if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$language = "sjis";
			} else {
				$language = "utf8";
			}
			$cmd = $path_mysqldump.DIRECTORY_SEPARATOR."mysqldump ".
						" --default-character-set=".$language." ".
						" --user=".$mysqlUser." ".
						" --password=".$mysqlPass." ".
					$dbname." ".DATABASE_PREFIX ."repository_log ". 
					" > ".
					$path_dumpfile.".sql";
			exec($cmd);
		} else {
			// Don't setting mysqldump PATH environment variable
			// Change the mode of the weko folder(d-wx----wx)
			chmod(WEBAPP_DIR ."/logs/weko/", 0303);
			$start_date = $this->start_year."-".$this->start_month."-01 00:00:00.000";
			$end_date = $this->end_year."-".$this->end_month."-31 23:59:59.999";
			$query = "SELECT * FROM ".DATABASE_PREFIX."repository_log ".
					" WHERE record_date >= '$start_date' ".
					" AND record_date <= '$end_date' ".
					" INTO OUTFILE '".$path_dumpfile.".csv' ".
					" FIELDS TERMINATED BY ',' ".
					" ENCLOSED BY '\'' LINES TERMINATED BY '\\r\\n'";
			$result = $this->Db->execute($query);
			if($result === false){
				return false;
			}
			// Change the mode of the weko folder(d-wx------)
			chmod(WEBAPP_DIR ."/logs/weko/", 0300);
		}
		
		$path_dumpfile = str_replace('\\\\', '\\', $path_dumpfile);
		if(!file_exists($path_dumpfile.".sql") && !file_exists($path_dumpfile.".csv")){
			return false;
		}
		return true;
	}
	
	/**
	 * delete log data
	 * between start date to end date
	 *
	 * @return bool retult
	 */
	function deleteLogData(){
		$start_date = $this->start_year."-".$this->start_month."-01 00:00:00.000";
		$end_date = $this->end_year."-".$this->end_month."-31 59:59:59.999";
		$query = "DELETE FROM ".DATABASE_PREFIX."repository_log ".
				" WHERE record_date >= '$start_date' ".
				" AND record_date <= '$end_date' ";
		$result = $this->Db->execute($query);
		if($result === false){
			return false;
		}
		
		// OPTIMIZE
		$query = "OPTIMIZE TABLE ".DATABASE_PREFIX."repository_log ";
		$result = $this->Db->execute($query);
		if($result === false){
			return false;
		}
	}
}


?>
