<?php
// --------------------------------------------------------------------
//
// $Id: Delete.class.php 30197 2013-12-19 09:55:45Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';

/**
 * repositoryモジュール アイテムタイプ作成 アイテムタイプ削除アクション
 *
 * @package	 NetCommons
 * @author	  S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license	 http://www.netcommons.org/license.txt  NetCommons License
 * @project	 NetCommons Project, supported by National Institute of Informatics
 * @access	  public
 */
class Repository_Action_Edit_Itemtype_Delete extends RepositoryAction
{
	
	// リクエストパラメタ
	var $item_type_id = null;		// アイテムタイプID
		
	/**
	 * [[機能説明]]
	 *
	 * @access  public
	 */
	function execute()
	{
		try {
			// INIT
			$result = $this->initAction();
			if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;							  //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				//$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}
			
			//////////////////// Lock for update ///////////////////////
			$query = "SELECT mod_date ".
					 "FROM ". DATABASE_PREFIX ."repository_item_type ".
					 "WHERE item_type_id = ? AND ".
					 "is_delete = ? ".
					 "FOR UPDATE;";
			$params = null;
			$params[] = $this->item_type_id;			// item_type_id
			$params[] = 0;
			$result = $this->Db->execute($query, $params);
			//Error
			if($result === false) {
				//Get error
				$errNo = $this->Db->ErrorNo();
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_code", $errMsg);
				//エラー処理を行う
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				//$DetailMsg = null;
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $str1, $str2 );
				//$exception->setDetailMsg( $DetailMsg );
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			// count = 0 is no update recorde
			if(count($result)==0) {
				//Get error
				$errNo = $this->Db->ErrorNo();
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_code", $errMsg);
				//エラー処理を行う
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				//$DetailMsg = null;
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $str1, $str2 );
				//$exception->setDetailMsg( $DetailMsg );
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			// Delete item type
			$query = "UPDATE ". DATABASE_PREFIX ."repository_item_type ".
					 "SET mod_user_id = ?, ".
					 "mod_date = ?, ".
					 "del_user_id = ?, ".
					 "del_date = ?, ".
					 "is_delete = ? ".
					 "WHERE item_type_id = ?; ";
			$params = null;
			$params[] = $user_id;							// mod_user_id
			$params[] = $this->TransStartDate;				// mod_date
			$params[] = $user_id;							// del_user_id
			$params[] = $this->TransStartDate;				// del_date
			$params[] = 1;									// is_delete
			$params[] = $this->item_type_id;						// item_type_id
			//Run update
			$result = $this->Db->execute($query,$params);
			if($result === false){
				//Get DB error
				$errNo = $this->Db->ErrorNo();
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_code", $errMsg);
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				//$DetailMsg = null;
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $str1, $str2 );
				//$exception->setDetailMsg( $DetailMsg );
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			
            // Add detail search 2013/11/25 K.Matsuo --start--
            $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            $searchTableProcessing->deleteDataFromSearchTable();
            // Add detail search 2013/11/25 K.Matsuo --end--
            
			//exit commit
			$result = $this->exitAction();
			if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			
			return 'success';
		}
		catch ( RepositoryException $Exception) {
			//エラーログ出力
			/*
			logFile(
				"SampleAction",					//クラス名
				"execute",						//メソッド名
				$Exception->getCode(),			//ログID
				$Exception->getMessage(),		//主メッセージ
				$Exception->getDetailMsg() );	//詳細メッセージ
			*/
			//アクション終了処理
	  		$this->exitAction();				   //トランザクションが失敗していればROLLBACKされる
		
			//異常終了
			return "error";
		}
	}
}
?>
