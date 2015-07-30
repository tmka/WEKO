<?php
// --------------------------------------------------------------------
//
// $Id: Log.class.php 30892 2014-01-17 08:20:21Z shota_suzuki $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAggregateCalculation.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Log extends RepositoryAction
{
	// component
	var $Session = null;
	var $request = null;

	// menber
	var $year_option_start=Array();
	var $month_option_start=Array();
	var $day_option_start=Array();
	var $year_option_end=Array();
	var $month_option_end=Array();
	var $day_option_end=Array();
	
    // Set help icon setting 2010/02/10 K.Ando --start--
	var $Db = null;
	var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    
	// Add send mail for log report 2010/03/10 Y.Nakao --start--
	var $mail_address = null;
	var $mail_url = null;
	// Add send mail for log report 2010/03/10 Y.Nakao --start--
	
	// Add log move 2010/05/21 Y.Nakao --start--
	var $startmonth = array();
	var $lastmonth = array();
	// Add log move 2010/05/21 Y.Nakao --end--
	
	// Add number of the items 2014/01/09 S.Suzuki --start--
	public $items = array();
	// Add number of the items 2014/01/09 S.Suzuki --end--
	
	/**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// 開始・終了日の選択候補（デフォルトチェック込み）文字列の作成
    	// 年
    	//$now = mktime();
    	$NOW_DATE = new Date();
    	
    	// get min log record year
		//$sy = date('Y',$now);
		//$sm = date('m',$now);
		//$sd = date('d',$now);
		$sy = $NOW_DATE->getYear();
		$sm = sprintf("%02d",$NOW_DATE->getMonth());
		$sd = sprintf("%02d",$NOW_DATE->getDay());
    	$query = " SELECT MIN( DATE_FORMAT(record_date, '%Y-%m-%d') ) AS min_date ".
				" FROM ".DATABASE_PREFIX."repository_log; ";
    	$result = $this->Db->execute($query);
    	if($result !== false || count($result) == 1){
    		$date = explode("-", $result[0]['min_date']);
    		if(count($date) == 3){
	    		$sy = $date[0];
	    		$sm = $date[1];
	    		$sd = $date[2];
    		}
    	}
    	
    	// move to db 
//    	$query = " SELECT MIN( DATE_FORMAT(record_date, '%Y-%m-%d') ) AS min_date ".
//				" FROM ".DATABASE_PREFIX."repository_log_per_date ".
//    			" WHERE record_date < '$sy-$sm-$sd 0:00:00.000'";
    	$query = " SELECT MIN( record_date ) AS min_date ".
				" FROM ".DATABASE_PREFIX."repository_log_per_date ".
    			" WHERE record_date <= '$sy-$sm'";
    	$result = $this->Db->execute($query);
    	if($result !== false || count($result) == 1){
    		$date = explode("-", $result[0]['min_date']);
    		if(count($date) == 2){
	    		$sy = $date[0];
	    		$sm = $date[1];
	    		$sd = "01";
    		}
    	}
    	// move to file
    	for($ii=$sy; $ii>2008; $ii--){
    		if(file_exists(WEBAPP_DIR."/logs/weko/logfile/log_per_date_$ii.txt")){
				$fp = fopen(WEBAPP_DIR."/logs/weko/logfile/log_per_date_$ii.txt", "r");
				$line = fgets($fp);
				$line = str_replace("\r\n", "", $line);
				$line = str_replace("\n", "", $line);
				$line = preg_replace("/\t.*/", "", $line);
				$date = explode("-", $line);
				$sy = $date[0];
				$sm = $date[1];
				$sd = $date[2];
				fclose($fp);
			}
    	}
    	
    	//$cnt_year = $sy - date('Y',$now);
    	$cnt_year = $sy - $NOW_DATE->getYear();
    	for ($ii=$cnt_year; $ii<=0; $ii++) {
//    		$str_year = date(
//    						"Y",
//    						mktime(
//    							date('H',$now),
//    							date('i',$now),
//    							date('s',$now),
//    							date('m',$now),
//    							date('d',$now),
//    							date('Y',$now)+$ii
//    						)
//    		);
			$str_year = $NOW_DATE->getYear() + $ii;
    		$select_s = $ii==$cnt_year ? 1 : 0;
    		$select_e = $ii==0 ? 1 : 0;
    		array_push($this->year_option_start,Array($str_year,$select_s));
    		array_push($this->year_option_end,Array($str_year,$select_e));
    	}
    	// 月
    	for ( $ii=1; $ii<=12; $ii++ ) {
    		$str_month = ($ii);
    		$select_s = $str_month==$sm ? 1 : 0;
    		//$select_e = $str_month==date('m',$now) ? 1 : 0;
    		$select_e = $str_month==$NOW_DATE->getMonth() ? 1 : 0;
    		array_push($this->month_option_start,Array($str_month,$select_s));
    		array_push($this->month_option_end,Array($str_month,$select_e));
    	}
    	// 日
    	for ( $ii=1; $ii<=31; $ii++ ) {
    		$str_day = ($ii);
    		$select_s = $str_day==$sd ? 1 : 0;
    		//$select_e = $str_day==date('d',$now) ? 1 : 0;
    		$select_e = $str_day==$NOW_DATE->getDay() ? 1 : 0;
    		array_push($this->day_option_start,Array($str_day,$select_s));
    		array_push($this->day_option_end,Array($str_day,$select_e));
		}
		
		// Add lang resource 2008/11/27 Y.Nakao --start--
		$this->setLangResource();
		// Add lang resource 2008/11/27 Y.Nakao --end--
		
        // Set help icon setting 2010/02/10 K.Ando --start--
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
		if ( $result == false ){
			$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
		}
        // Set help icon setting 2010/02/10 K.Ando --end--
        
		// Add send mail for log report 2010/03/10 Y.Nakao --start--
		$block_id = $this->getBlockPageId();
    	$result = $this->getAdminParam('log_report_mail', $this->mail_address, $Error_Msg);
		if ( $result == false ){
			$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
		}
		$this->mail_url = BASE_URL."/?action=repository_logreport&mail=true".
						"&block_id=".$block_id['block_id']."&page_id=".$block_id['page_id'].
						"&login_id=[login_id]&password=[password]";
		// Add send mail for log report 2010/03/10 Y.Nakao --end--
		
		// Add log move 2010/05/21 Y.Nakao --start--
    	//$today = getdate();
		//$date = mktime(0, 0, 0, $today['mon'] - 1, 1, $today['year']);
		$query = "SELECT DATE_FORMAT(DATE_SUB('".$NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-01', INTERVAL 1 MONTH), '%Y') AS tmp_year,".
				 " DATE_FORMAT(DATE_SUB('".$NOW_DATE->getYear()."-".$NOW_DATE->getMonth()."-01', INTERVAL 1 MONTH), '%m') AS tmp_month;";
		$result = $this->Db->execute($query);
		if($result === false || count($result) != 1){
			return false;
		}
		
		//$this->lastmonth['year'] = date("Y", $date);
		//$this->lastmonth['month'] = date("m", $date);
		$this->lastmonth['year'] = $result[0]['tmp_year'];
		$this->lastmonth['month'] = sprintf("%02d",$result[0]['tmp_month']);
    	$query = " SELECT MIN( DATE_FORMAT(record_date, '%Y-%m') ) AS min_date ".
				" FROM ".DATABASE_PREFIX."repository_log; ";
    	$result = $this->Db->execute($query);
    	if($result == false || count($result) != 1){
    		// error
    		$this->startmonth['year'] = $this->lastmonth['year'];
    		$this->startmonth['month'] = $this->lastmonth['month'];
    	} else { 
    		$date = explode("-", $result[0]['min_date']);
    		$this->startmonth['year'] = $date[0];
    		$this->startmonth['month'] = $date[1];
    	}
    	if(	$this->startmonth['year'] >= $this->lastmonth['year'] && 
    		$this->startmonth['month'] >= $this->lastmonth['month']){
    		$this->lastmonth['year'] = $this->startmonth['year'];
    		$this->lastmonth['month'] = $this->startmonth['month'];
    	}
		// Add log move 2010/05/21 Y.Nakao --end--
    	
    	// Add get total items 2014/01/14 S.Suzuki --start-- 
    	$DATE = new Date();
        $this->TransStartDate = $DATE->getDate().".000";
        $this->dbAccess = new RepositoryDbAccess($this->Db);
    	
    	$repositoryAggregateCalculation = new RepositoryAggregateCalculation($this->Session, $this->dbAccess, $this->TransStartDate);
    	$this->items = $repositoryAggregateCalculation->countItem();
    	// Add get total items 2014/01/14 S.Suzuki --end-- 
    	
		return 'success';
    }
}
?>
