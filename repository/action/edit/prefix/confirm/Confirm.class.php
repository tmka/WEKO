<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
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
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Prefix_Confirm extends RepositoryAction
{
	// リクエストパラメタ
	var $captcha_string = null;

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
			
			$this->Session->removeParameter("error_flg");
			$auth_session_id = $this->Session->getParameter("auth_session_id");
			
			// トライ回数取得
			$try_num = $this->Session->getParameter("try_num");
			if($try_num == null || $try_num == ""){
				$try_num = 1;
			} else {
				$try_num++;
			}
			$this->Session->setParameter("try_num", $try_num);
			// アイテム識別子管理機能 2014/01/22 T.Ichikawa --start--
			$repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
			$result = $repositoryHandleManager->registerYHandlePrefix($this->captcha_string, $auth_session_id);
			// アイテム識別子管理機能 2014/01/22 T.Ichikawa --end--
			
            // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --start--
            // error method of compare
			if($result == false){
				if($try_num < 5){
					// エラーのためリトライ
					$this->Session->setParameter("error_flg", "retry");
					return "retry";
				} else {
					// エラー5回目で終了
					$this->Session->setParameter("error_flg", "end");
					// ワークディレクトリ削除
					$this->removeDirectory($this->Session->getParameter("tmp_dir"));
					unlink("./.rnd");
					
					// セッション情報削除
					$this->Session->removeParameter("auth_session_id");
					$this->Session->removeParameter("tmp_dir");
					$this->Session->removeParameter("try_num");
					return "success";
				}
			}
            // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --end--
			
			// ワークディレクトリ削除
			$this->removeDirectory($this->Session->getParameter("tmp_dir"));
			unlink("./.rnd");
			
			// セッション情報削除
			$this->Session->removeParameter("auth_session_id");
			$this->Session->removeParameter("tmp_dir");
			$this->Session->removeParameter("try_num");
			
			// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			return 'success';
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
