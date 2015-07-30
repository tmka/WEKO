<?php
// --------------------------------------------------------------------
//
// $Id: User.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
 * repository module admin action
 *
 * @package	 NetCommons
 * @author	  IVIS
 * @copyright   2006-2009 NetCommons Project
 * @license	 http://www.netcommons.org/license.txt  NetCommons License
 * @project	 NetCommons Project, supported by National Institute of Informatics
 * @access	  public
 */
class Repository_Action_Main_User extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	var $mailMain = null;
	
	// request parameter
	var $check = null;
	var $setting = null;
	var $tab = null;
	
	/**
	 * @access  public
	 */
	function execute()
	{
		try {
			// ----------------------------------------------------
			// call init action
			// ----------------------------------------------------
			$result = $this->initAction();			
			if ( $result === false ) {
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
				$DetailMsg = null;							  //詳細メッセージ文字列作成
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}
			
			// get user id
			// ユーザID取得
			$user_id = $this->Session->getParameter("_user_id");
			
			// ----------------------------------------------------
			// check user info
			// ユーザ情報があるかどうかチェックする
			// ----------------------------------------------------
			$query = "SELECT * FROM ".DATABASE_PREFIX."repository_users ".
					" WHERE user_id = ? ";
			$param = array();
			$param[] = $user_id;
			$result = $this->Db->execute($query, $param);
			if($result === false){
				$this->error_msg = $this->Db->ErrorMsg();
				//アクション終了処理
				$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
				return false;
			}
			if(count($result) == 0){
				// insert info
				// 結果通知を受け取るかどうかの情報がないので追加する
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_users ".
						" (`user_id`, ".
						"`ins_user_id`, `mod_user_id`, `del_user_id`, ".
						"`ins_date`, `mod_date`, `del_date`, `is_delete`) ".
						" VALUES (?, ?, ?, ?, ?, ?, ?, ?); ";
				$param = array();
				$param[] = $user_id;
				$param[] = $user_id;
				$param[] = $user_id;
				$param[] = '';
				$param[] = $this->TransStartDate;
				$param[] = $this->TransStartDate;
				$param[] = '';
				$param[] = 0;
				$result = $this->Db->execute($query, $param);
				if($result === false){
					$this->error_msg = $this->Db->ErrorMsg();
					//アクション終了処理
					$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
					return false;
				}
			}
			// update info
			// 情報があるので更新する
			$query = "UPDATE ".DATABASE_PREFIX."repository_users ";
			if($this->setting == "2"){
				$query .= " SET supple_mail_flg = ?, ";
			} else {
				$query .= " SET contents_mail_flg = ?, ";
			}
			$query .= " mod_user_id = ?, ";
			$query .= " mod_date = ?, ";
			$query .= " is_delete = ? ";
			$query .= " WHERE user_id = ?; ";
			$param = array();
			$param[] = $this->check;
			$param[] = $user_id;
			$param[] = $this->TransStartDate;
			$param[] = 0;
			$param[] = $user_id;
			$result = $this->Db->execute($query, $param);
			if($result === false){
				$this->error_msg = $this->Db->ErrorMsg();
				//アクション終了処理
				$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
				return false;
			}
			// ----------------------------------------------------
			// call end action
			// ----------------------------------------------------
			//アクション終了処理
			$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
	 		if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				throw $exception;
			}
			
			if($this->setting == 2){
				$this->Session->setParameter("supple_workflow_active_tab", $this->tab);
				return 'supple';
			} else {
				$this->Session->setParameter("repository_workflow_active_tab", $this->tab);
				return 'workflow';
			}
			
		}
		catch ( RepositoryException $Exception) {
			//エラーログ出力
			$this->logFile(
				"SampleAction",					//クラス名
				"execute",						//メソッド名
				$Exception->getCode(),			//ログID
				$Exception->getMessage(),		//主メッセージ
				$Exception->getDetailMsg() );	//詳細メッセージ			
			//アクション終了処理
	  		$this->exitAction();				   //トランザクションが失敗していればROLLBACKされる		
			//異常終了
			return "error";
		}
	}
}
?>
