<?php
// --------------------------------------------------------------------
//
// $Id: Mailtest.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
class Repository_Action_Edit_Mailtest extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	var $mailMain = null;
	
	// member
	var $smartyAssign = null;
	var $review_mail = null;
	
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
			
			// 言語リソース取得
			$this->smartyAssign = $this->Session->getParameter("smartyAssign");
			
			$user_id = $this->Session->getParameter("_user_id");	// ユーザID
			
			// 査読通知メール設定
			$params = null;						// パラメタテーブル更新用クエリ			
			$params[] = 1;						// param_value
			$params[] = $user_id;				// mod_user_id
			$params[] = $this->TransStartDate;	// mod_date
			$params[] = 'review_mail_flg';		// param_name
			$result = $this->updateParamTableData($params, $Error_Msg);
			if ($result === false) {
				$errMsg = $this->Db->ErrorMsg();
				$tmpstr = sprintf("review_mail_flg update failed : %s", $ii, $jj, $errMsg ); 
				$this->Session->setParameter("error_msg", $tmpstr);
				$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
				return false;
			}
			
            // Bugfix input scrutiny 2011/06/17 Y.Nakao --start--
            // check 'Review mail address'
            $this->review_mail = str_replace("\r\n", "\n", $this->review_mail);
            $add = array();
            $add = explode("\n", $this->review_mail);
            $this->review_mail = "";
            for($ii=0; $ii<count($add); $ii++){
                if(strlen($add[$ii]) > 0){
                    if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $add[$ii]) > 0){
                        $this->review_mail .= $add[$ii]."\n"; 
                    }
                }
            }
            echo $this->review_mail;
            // Bugfix input scrutiny 2011/06/17 Y.Nakao --end--
			
			// 査読通知メールアドレスをDBに保存
			$params = null;						// パラメタテーブル更新用クエリ			
			$params[] = $this->review_mail;		// param_value
			$params[] = $user_id;				// mod_user_id
			$params[] = $this->TransStartDate;	// mod_date
			$params[] = 'review_mail';			// param_name
			$result = $this->updateParamTableData($params, $Error_Msg);
			if ($result === false) {
				$errMsg = $this->Db->ErrorMsg();
				$tmpstr = sprintf("review_mail update failed : %s", $ii, $jj, $errMsg ); 
				$this->Session->setParameter("error_msg", $tmpstr);
				$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
				return false;
			}
			
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
			$fromEmail = '';
			$fromName = '';
			$users = array();
			$this->getReviewMailInfo($users);
			if(count($users) == 0){
				return true;
			}
			
			// ---------------------------------------------
			// 送信メール生成
			// create mail body
			// ---------------------------------------------
			// 送り主のメールアドレス
			// set send from user mail address
			// NetCommonsの管理設定を利用するので何も設定しない
			//$this->mailMain->setFromEmail($fromEmail);
			
			// 送り主の名前
			// set send from user name
			// NetCommonsの管理設定を利用するので何も設定しない
			//$this->mailMain->setFromName($fromName);
			
			// 件名
			// set subject
			$subj = $this->smartyAssign->getLang("repository_testmail_review_subject");
			$this->mailMain->setSubject($subj);
			
			// メール本文をリソースから読み込む
			// set Mail body
			$body = $this->smartyAssign->getLang("repository_testmail_review_body")."\n\n";
			$body .= $this->smartyAssign->getLang("repository_mail_review_close");
			$this->mailMain->setBody($body);
			
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
			// 確認メール送信
			// send confirm mail
			// ---------------------------------------------
			$return = $this->mailMain->send();
			if($return === false){
				$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
				return false;
			}

			// 言語リソース開放
			//$this->Session->removeParameter("smartyAssign");
			
			//アクション終了処理
			$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
	 		if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;							  //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
				//$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				throw $exception;
			}
			
			return true;
			
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
