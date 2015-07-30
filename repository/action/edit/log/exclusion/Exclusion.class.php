<?php
// --------------------------------------------------------------------
//
// $Id: Exclusion.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
class Repository_Action_Edit_Log_Exclusion extends RepositoryAction
{
	// component
	var $Session = null;
	var $smartyAssign = null;
	var $uploadsView = null;

	// out type
	// 1:exception
	// 2:release
	var $queryParam = null;
	var $log_exclusion = null;

	function execute()
	{
		try {
			$result = $this->initAction();
			if ( $result === false ) {
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
				$DetailMsg = null;                              //詳細メッセージ文字列作成
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				$this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}

			// -----------------------------------------
			// make log exception SQL
			// -----------------------------------------
			$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
					 "WHERE param_name = 'log_exclusion'; ";
			$ip_list = $this->Db->execute($query);
			if(ip_list === false){
				return 'error';
			}
			
			if(strcmp($this->queryParam, 1) == 0)
			{
				// except ip address
				$params[0] = $ip_list[0]['param_value']."\n".$this->log_exclusion;	// param_value
				$params[1] = $this->Session->getParameter("_user_id");	// mod_user_id
				$params[2] = $this->TransStartDate;	// mod_date
				$params[3] = 'log_exclusion';		// param_name
				$result = $this->updateParamTableData($params, $Error_Msg);
				if ($result === false) {
					$errMsg = $this->Db->ErrorMsg();
					$tmpstr = sprintf("log_exclusion update failed : %s", $errMsg ); 
					$this->Session->setParameter("error_msg", $tmpstr);
					$this->failTrans();		//ROLLBACK
					return 'error';
				}
			}
			else
			{
				$ip_list = str_replace("\r\n", "\n", $ip_list[0]["param_value"]);
				$ip_list = str_replace("\r", "\n", $ip_list);
				// remove ip address
				$ip_list = str_replace("\n".$this->log_exclusion."\n", "\n", $ip_list);	
				$ip_list = str_replace($this->log_exclusion."\n", "", $ip_list);	
				$ip_list = str_replace("\n".$this->log_exclusion, "", $ip_list);	
				$params[0] = $ip_list;
				$params[1] = $this->Session->getParameter("_user_id");	// mod_user_id
				$params[2] = $this->TransStartDate;	// mod_date
				$params[3] = 'log_exclusion';		// param_name
				$result = $this->updateParamTableData($params, $Error_Msg);
				if ($result === false) {
					$errMsg = $this->Db->ErrorMsg();
					$tmpstr = sprintf("log_exclusion update failed : %s", $errMsg ); 
					$this->Session->setParameter("error_msg", $tmpstr);
					$this->failTrans();		//ROLLBACK
					return 'error';
				}
			}
				
			// lang resource 
			$this->smartyAssign = $this->Session->getParameter("smartyAssign");
			print ("success"."\n");
			
			$this->exitAction();
			 
		} catch ( RepositoryException $Exception) {
			//エラーログ出力
			$this->logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
			$Exception->getCode(),			//ログID
			$Exception->getMessage(),		//主メッセージ
			$Exception->getDetailMsg() );	//詳細メッセージ
			 
			//アクション終了処理
			$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる

			//異常終了
			return "error";
		}
	}

}


?>
