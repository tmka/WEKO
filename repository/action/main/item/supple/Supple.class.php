<?php
// --------------------------------------------------------------------
//
// $Id: Supple.class.php 3 2010-02-02 05:07:44Z atsushi_suzuki $
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
 * サプリアイテム既存登録＆削除用アクション
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Supple extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	var $Session = null;
	var $Db = null;
	var $mailMain = null;
	
	// リクエストパラメータ
	var $item_id = null;
	var $item_no = null;
	var $mode = null;
	var $weko_key = null;
	var $supple_no = null;
	var $workflow_flag = null;
	var $workflow_active_tab = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        try {
	        //アクション初期化処理
	        $result = $this->initAction();
	        
	        if ( $result === false ) {
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
        	}
        	
        	// サプリアクションからの遷移を示すフラグ
            $this->Session->setParameter("supple_flag", "true");
            
            // ユーザID取得
            $user_id = $this->Session->getParameter("_user_id");
        	
        	// 既存サプリアイテム登録
        	if($this->mode == "add_existing"){
        		// IDサーバのアドレスを取得
        		$query = "SELECT param_value ".
        				 "FROM ".DATABASE_PREFIX."repository_parameter ".
        				 "WHERE param_name = 'IDServer';";
        		$result = $this->Db->execute($query);
        		if($result === false){
                	return "error";
                }
                $id_server = $result[0]['param_value'];
                
                // サプリWEKOのPrefixIDを取得
                $prefix_id = $this->getSupplePrefixIDFromOpenSearch();
                if($prefix_id === false){
                	return "error";
                } else if($prefix_id == "") {
                	// no url error
					$this->Session->setParameter("supple_error", 2);
					return "error";
                }
        		
        		// 入力値精査
        		$this->weko_key = str_replace(" ", "", $this->weko_key);
        		// パーマリンク用アドレス入力の場合
        		if(ereg($id_server.$prefix_id, $this->weko_key)){
        			// 入力されたアドレスからsuffixを取得
        			$this->weko_key = str_replace("Permalink:", "", $this->weko_key);
        			$this->weko_key = str_replace($id_server.$prefix_id, "", $this->weko_key);
        			$this->weko_key = str_replace("/", "", $this->weko_key);
        		}
        		// "Prefix/Suffix"形式で入力の場合
        		else if(ereg($prefix_id."/[0-9]{8}", $this->weko_key)){
        			// 入力値からsuffixを取得
        			$this->weko_key = str_replace($prefix_id, "", $this->weko_key);
        			$this->weko_key = str_replace("/", "", $this->weko_key);
        		}
        		// その他: "Suffix"のみでの入力の場合
        		else {
        			$this->weko_key = str_replace("/", "", $this->weko_key);
        		}
        		
        		// 整数に整形
        		$this->weko_key = (int)$this->weko_key;
        		
	        	if($this->weko_key != "" && $this->weko_key != null){
					// 0詰めの8桁に揃える ex)673 -> 00000673
					if($this->weko_key != 0){
						if(strlen($this->weko_key) <= 8){
							$this->weko_key = sprintf("%08d", $this->weko_key);
						} else {
							// 不正な値
							$this->weko_key = 0;
						}
					}
				}
        		
        		$supple_data = array();
                $supple_data = $this->getSuppleDataFromOpenSearch("weko_id", $this->weko_key);
                if($supple_data === false){
                	return "error";
                }
				
				// 該当サプリアイテムなし
				if(count($supple_data) == 0){
					// no item error
					$this->Session->setParameter("supple_error", 3);
					return "error";
				}
				
				// アイテムタイプ情報を取得
				$query = "SELECT attr_type.item_type_id, attr_type.attribute_id, item.uri, item.title, item.title_english ".
						 "FROM ".DATABASE_PREFIX."repository_item_attr_type AS attr_type, ".DATABASE_PREFIX."repository_item AS item ".
						 "WHERE item.item_id = ? ".
						 "AND item.item_no = ? ".
						 "AND item.item_type_id = attr_type.item_type_id ".
						 "AND attr_type.input_type = 'supple' ".
						 "AND item.is_delete = 0 ".
						 "AND attr_type.is_delete = 0;";
				$params = array();
				$params[] = $this->item_id;	// item_id
				$params[] = $this->item_no;	// item_no
				$item_type_result = $this->Db->execute($query, $params);
				if($item_type_result === false){
					return "error";
				}
				
				// サプリテーブルを検索
	        	$query = "SELECT MAX(supple_no) FROM ".DATABASE_PREFIX."repository_supple ".
	        			 "WHERE item_id = ? ".
	        			 "AND item_no = ? ".
	        			 "AND attribute_id = ? ".
	        			 "AND item_type_id = ?;";
	        	$params = array();
				$params[] = $this->item_id;	// item_id
				$params[] = $this->item_no;	// item_no
				$params[] = $item_type_result[0]['attribute_id'];	// attribute_id
				$params[] = $item_type_result[0]['item_type_id'];	// item_type_id
				$result = $this->Db->execute($query, $params);
				if($result === false){
					return "error";
				}
	        	
				if($result[0]['MAX(supple_no)'] == null){
					$supple_num = 1;
				} else {
					$supple_num = $result[0]['MAX(supple_no)'] + 1;
				}
				
				// 新規査読アイテム登録メール送信処理
				// 査読通知メールを送るか否か
	    	    $query = "SELECT param_value ".
						 "FROM ".DATABASE_PREFIX."repository_parameter ".
						 "WHERE param_name = 'review_mail_flg';";
				$result = $this->Db->execute($query);
				if ($result === false) {
					array_push($error_msg, $this->Db->ErrorMsg());
					// roll back
					$this->failTrans();
					return 'error';
				}
				$review_mail_flg = $result[0]['param_value'];
				
				// サプリコンテンツの査読を行うか否か
        		$query = "SELECT param_value ".
        				 "FROM ".DATABASE_PREFIX."repository_parameter ".
        				 "WHERE param_name = 'review_flg_supple';";
        		$result = $this->Db->execute($query);
        		if($result === false){
                	return "error";
                }
                $review_flg_supple = $result[0]['param_value'];
				
				// サプリアイテム登録
				$query = "INSERT INTO ". DATABASE_PREFIX ."repository_supple ".
						 "(item_id, item_no, attribute_id, supple_no, ".
						 " item_type_id, supple_weko_item_id, supple_title, supple_title_en,".
						 " uri, supple_item_type_name, mime_type, file_id, supple_review_status,".
						 " supple_review_date, ins_user_id, mod_user_id, ins_date, mod_date, del_date, is_delete) ".
						 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
	        	$params = array();
				$params[] = $this->item_id;	// item_id
				$params[] = $this->item_no;	// item_no
				$params[] = $item_type_result[0]['attribute_id'];	// attribute_id
				$params[] = $supple_num;		// supple_no
				$params[] = $item_type_result[0]['item_type_id'];	// item_type_id
				$params[] = $supple_data["supple_weko_item_id"];	// supple_weko_item_id
				$params[] = $supple_data["supple_title"];	// supple_title
				$params[] = $supple_data["supple_title_en"];	// supple_title_en
				$params[] = $supple_data["uri"];	// uri
				$params[] = $supple_data["supple_item_type_name"];	// supple_item_type_name
				$params[] = $supple_data["mime_type"];	// mime_type
				$params[] = $supple_data["file_id"];	// file_id
				if($review_flg_supple == 1){
					// 査読を行う
					$params[] = 0;	// supple_review_status
					$params[] = "";	// supple_review_date
				} else {
					// 査読を行わない（自動的に承認する）
					$params[] = 1;	// supple_review_status
					$params[] = $this->TransStartDate;	// supple_review_date
				}
				$params[] = $user_id;	// ins_user_id
				$params[] = $user_id;	// mod_user_id
				$params[] = $this->TransStartDate;	// ins_date
				$params[] = $this->TransStartDate;	// mod_date
				$params[] = null;	// del_date
				$params[] = 0;	// is_delete
				$result = $this->Db->execute($query, $params);
				if($result === false){
					return "error";
				}
				
	        	// 新規査読サプリコンテンツ登録メール送信処理
				if($review_flg_supple == 1){
					// 査読を行う
					if($review_mail_flg == 1){
						// 言語リソース取得
						$smartyAssign = $this->Session->getParameter("smartyAssign");
						// send review mail
						// 査読通知メールを送信する
						// 件名
						// set subject
						$subj = $smartyAssign->getLang("repository_mail_review_subject");
						$this->mailMain->setSubject($subj);
						
						// page_idおよびblock_idを取得
						$block_info = $this->getBlockPageId();
						// メール本文をリソースから読み込む
						// set Mail body
						$body = '';
						$body .= $smartyAssign->getLang("repository_mail_review_body")."\n\n";
						$body .= $smartyAssign->getLang("repository_mail_review_suppple_contents")."\n";
						$body .= $smartyAssign->getLang("repository_mail_review_supple_title");
						if($this->Session->getParameter("_lang") == "japanese"){
							if($supple_data["supple_title"] != ""){
								$body .= $supple_data["supple_title"];
							} else if($supple_data["supple_title_en"] != ""){
								$body .= $supple_data["supple_title_en"];
							} else {
								$body .= "no title";
							}
						} else {
							if($supple_data["supple_title_en"] != ""){
								$body .= $supple_data["supple_title_en"];
							} else if($supple_data["supple_title"] != ""){
								$body .= $supple_data["supple_title"];
							} else {
								$body .= "no title";
							}
						}
						
						$body .= "\n";
						$body .= $smartyAssign->getLang("repository_mail_review_supple_detailurl").$supple_data["uri"]."\n";
						$body .= $smartyAssign->getLang("repository_mail_review_supple_title_is_registed");
						if($this->Session->getParameter("_lang") == "japanese"){
							if($item_type_result[0]['title'] != ""){
								$body .= $item_type_result[0]['title'];
							} else if($item_type_result[0]['title_english'] != ""){
								$body .= $item_type_result[0]['title_english'];
							} else {
								$body .= "no title";
							}
						} else {
							if($item_type_result[0]['title_english'] != ""){
								$body .= $item_type_result[0]['title_english'];
							} else if($item_type_result[0]['title'] != ""){
								$body .= $item_type_result[0]['title'];
							} else {
								$body .= "no title";
							}
						}
						$body .= "\n";
						$body .= $smartyAssign->getLang("repository_mail_review_supple_detailurl_is_registed").$item_type_result[0]['uri']."\n";
						$body .= "\n";
						$body .= $smartyAssign->getLang("repository_mail_review_reviewurl")."\n";
						$body .= BASE_URL;
						if(substr(BASE_URL,-1,1) != "/"){
							$body .= "/";
						}
						$body .= "?active_action=repository_view_edit_review&review_active_tab=1&page_id=".$block_info["page_id"]."&block_id=".$block_info["block_id"];
						$body .= "\n\n".$smartyAssign->getLang("repository_mail_review_close");
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
						$this->getReviewMailInfo($users);
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
				}
        	}
        	
        	// サプリアイテム削除
        	else if($this->mode == "delete"){
        		$query = "UPDATE ".DATABASE_PREFIX."repository_supple ".
        				 "SET mod_user_id = ?, del_user_id = ?, mod_date = ?, del_date = ?, is_delete = ? ".
        				 "WHERE item_id = ? ".
        				 "AND item_no = ? ".
        				 "AND supple_no = ?;";
        		$params = array();
        		$params[] = $user_id;				// mod_user_id
        		$params[] = $user_id;				// del_user_id
        		$params[] = $this->TransStartDate;	// mod_date
        		$params[] = $this->TransStartDate;	// del_date
        		$params[] = 1;						// is_delete
        		$params[] = $this->item_id;			// item_id
        		$params[] = $this->item_no;			// item_no
        		$params[] = $this->supple_no;		// supple_no
        		
        		$result = $this->Db->execute($query, $params);
        		if($result === false){
        			return "error";
        		}
        		if($this->workflow_flag == "true"){
        			$this->Session->setParameter("supple_workflow_active_tab", $this->workflow_active_tab);
        		}
        	}
        	
        	//アクション終了処理
			$result = $this->exitAction();     // トランザクションが成功していればCOMMITされる
        	
			if($this->workflow_flag == "true"){
        		return "workflow";
        	} else {
	    		return "success";
        	}
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
      		$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
      		        				
	        //異常終了
    	    return "error";
		}
    }
}
?>
