<?php
// --------------------------------------------------------------------
//
// $Id: Review.class.php 22441 2013-05-08 07:01:15Z koji_matsuo $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Review extends RepositoryAction
{
	// セッションとデータベースのオブジェクトを受け取る
    var $Session = null;
    var $Db = null;
    var $languagesView = null;
    
    // リクエストパラメタ
    var $select_review = null;	// 未承認/承認/却下
    var $reject_reason = null;	// 却下理由
    var $block_id = null;		// ブロックID
    
    // Add review mail setting 2009/09/30 Y.Nakao --start--
    var $mailMain = null;
    // Add review mail setting 2009/09/30 Y.Nakao --end--

    // add supple review 2009/009/18 A.Suzuki --start--
    var $select_supple_review = null;	// サプリコンテンツ: 未承認/承認/却下
    var $supple_reject_reason = null;	// サプリコンテンツ: 却下理由
    var $type = null;					// "item" or "supple"
    var $review_active_tab = null;		// 表示中タブ情報
    // add supple review 2009/009/18 A.Suzuki --end--
    
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
	            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	            //$DetailMsg = null;                              //詳細メッセージ文字列作成
	            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
	        }
	        
	        // 表示中タブ情報
	        $this->Session->setParameter("review_active_tab", $this->review_active_tab);
	        
	        // ユーザIDを取得
	        $user_id = $this->Session->getParameter("_user_id");
	        
	        // add supple review 2009/009/18 A.Suzuki --start--
	        // アイテムの査読
	        if($this->type == "item"){
				// Sessionからitem_id,item_no,mod_dateを取得
		        $item_id_no_mod = $this->Session->getParameter("item_id_no_mod_for_review");
	
		        // 承認アイテムのitem_id,item_no,titleを格納
		        $item_id_no = "";
		        
		        // アイテムの自動公開処理追加 2008/08/08 --start--
				$query = "SELECT `param_value` ".
						 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
						 "WHERE `param_name` = 'item_auto_public';";
				$ret = $this->Db->execute($query);
				if ($ret === false) {
					$this->outputError();
					return false;
				}
				$item_auto_public = $ret[0]['param_value'];
		        // アイテムの自動公開処理追加 2008/08/08 --end--
		        
				$update_count = 0; 	// Add update OK message 2009/01/23 A.Suzuki
				
				// Add review mail setting 2009/09/30 Y.Nakao --start--
				$review_item = array();
				// Add review mail setting 2009/09/30 Y.Nakao --end--
				
		        for($nCnt=0;$nCnt<count($this->select_review);$nCnt++){
		        	// itemのアップデート確認
		        	//更新前に対象レコードをロックする。
		            //最低限、更新日時（mod_date）のみ取得すれば良い。
		            //必要であれば他のカラムを取得しても良い。
		            $query = "SELECT * ".
		                     "FROM ". DATABASE_PREFIX ."repository_item ".
		                     "WHERE item_id = ? AND ".
		            		 "item_no = ? AND ".
		                     "is_delete = ? AND ".
		            		 "mod_date = ? ".
		                     "FOR UPDATE; ";
					$params = null;
					$params[] = $item_id_no_mod[$nCnt]['item_id'];	// item_id
					$params[] = $item_id_no_mod[$nCnt]['item_no'];	// item_no
					$params[] = 0;									// is_delete
		            $params[] = $item_id_no_mod[$nCnt]['mod_date'];	// mod_date
		            $ret = $this->Db->execute($query, $params);
		            //SQLエラーの場合
		            if($ret === false) {
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", $errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            //取得結果が0件の場合
		            //この場合、UPDATE対象のレコードは存在しないこととなる。
		            //以降のUPDATE処理は行わないこと。
		            if(count($ret)==0) {
		                $this->Session->setParameter("error_code", 7);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            $title = $ret[0]['title'];
		            $title_english = $ret[0]['title_english'];
		            $uri = $ret[0]['uri'];
		            // Add review mail setting 2009/09/30 Y.Nakao --start--
		            $ind_user_id = $ret[0]['ins_user_id'];
		            // 査読実施後に前回の更新者(編集者)の情報が消えるので保持しておく
		            $mod_user_id = $ret[0]['mod_user_id'];
		            // Add review mail setting 2009/09/30 Y.Nakao --end--
		            // UpDate OK
		        	if($this->select_review[$nCnt] == 1){
						// get index_id
						$query = "SELECT ".DATABASE_PREFIX."repository_index.index_id, ".DATABASE_PREFIX."repository_index.public_state ".
			                     "FROM ".DATABASE_PREFIX."repository_index, ".DATABASE_PREFIX."repository_position_index ".
			                     "WHERE ".DATABASE_PREFIX."repository_position_index.item_id = ? ".
			            		 "AND ".DATABASE_PREFIX."repository_position_index.item_no = ? ".
			                     "AND ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
						         "AND ".DATABASE_PREFIX."repository_position_index.index_id = ".DATABASE_PREFIX."repository_index.index_id ".
								 "AND ".DATABASE_PREFIX."repository_index.is_delete = 0; ";
						$params = null;
						$params[] = $item_id_no_mod[$nCnt]['item_id'];	// item_id
						$params[] = $item_id_no_mod[$nCnt]['item_no'];	// item_no
			            $result = $this->Db->execute($query, $params);
			            //SQLエラーの場合
			            if($result === false) {
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                $this->Session->setParameter("error_code", $errMsg);
			                //エラー処理を行う
			                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
			                throw $exception;
			            }
				            
				        // Add count contents 2009/01/05 A.Suzuki --start--
						// check item_auto_public
						if($item_auto_public == 1){	
							// Add check unpublic index 2009/02/05 A.Suzuki --start--
				    		$pub_index_flag = false;
				  			for($ii=0; $ii<count($result); $ii++){
				  				// 公開中のインデックスがあるか
				  				if($result[$ii]['public_state'] == "1"){
				  					// 親インデックスが公開されているか
				  					if($this->checkParentPublicState($result[$ii]['index_id'])){
				  						$pub_index_flag = true;
				  						// アイテムが非公開である場合
				  						if($ret[0]['shown_status'] == 0){
					  						// 公開中のインデックスのみコンテンツ数を増やす
							            	$this->addContents($result[$ii]['index_id']);
											$this->deletePrivateContents($result[$ii]['index_id']);		// Add private_contents count K.Matsuo 2013/05/07
				  						}
				  					}
				  				}
				  			}
				  			
				    		if($pub_index_flag){
					    		// Add send item infomation to whatsnew module 2009/01/27 A.Suzuki
								$result = $this->addWhatsnew($item_id_no_mod[$nCnt]['item_id'], $item_id_no_mod[$nCnt]['item_no']);
								if ($result === false) {
									return false;
								}
				    		} else {
				    			$this->deleteWhatsnew($item_id_no_mod[$nCnt]['item_id']);
				    		}
				    		// Add check unpublic index 2009/02/05 A.Suzuki --end--
						} else {
							if($ret[0]['shown_status'] == 1){
								for($ii=0; $ii<count($result); $ii++){
					  				// 公開中のインデックスがあるか
					  				if($result[$ii]['public_state'] == "1"){
					  					// 親インデックスが公開されているか
					  					if($this->checkParentPublicState($result[$ii]['index_id'])){
					  						// 公開中であるインデックスのみコンテンツ数を減らす
							            	$this->deleteContents($result[$ii]['index_id']);
											$this->addPrivateContents($result[$ii]['index_id']);		// Add private_contents count K.Matsuo 2013/05/07
					  					}
					  				}
					  			}
							}
							// 新着情報から削除
							$this->deleteWhatsnew($item_id_no_mod[$nCnt]['item_id']);
						}
						// Add count contents 2009/01/05 A.Suzuki --end--
		        		
		        		// 承認処理
		        		$query = "UPDATE ". DATABASE_PREFIX ."repository_item ".
		                    	 "SET review_status = ?, ".
		        				 "review_date = ?, ".
		        				 "shown_status = ?, ".	// Add 2008/08/08 Y.Nakao 
		        				 //"shown_date = ?, ".	// Add 2008/08/08 Y.Nakao
		        				 "mod_user_id = ?, ".
								 "mod_date = ?, ".
								 "is_delete = ? ".
		                    	 "WHERE item_id = ? AND ".
		        				 "item_no = ?; ";
						$params = null;
						$params[] = 1;						// review_status
						$params[] = $this->TransStartDate;	// review_date
						$params[] = $item_auto_public;		// shown_status 自動公開フラグを使用
//						if($item_auto_public == 1){
//							$params[] = $this->TransStartDate;	// shown_date
//						} else {
//							$params[] = "";					// shown_date
//						}
						$params[] = $user_id;				// mod_user_id
						$params[] = $this->TransStartDate;	// mod_date
						$params[] = 0;						// is_delete
						$params[] = $item_id_no_mod[$nCnt]['item_id'];	// item_id
						$params[] = $item_id_no_mod[$nCnt]['item_no'];	// item_no
			            //UPDATE実行
		        	    $result = $this->Db->execute($query,$params);
						if($result === false){
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                $this->Session->setParameter("error_code", $errMsg);
			                //エラー処理を行う
			                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
			                throw $exception;
						}
						$item_id_no = $item_id_no_mod[$nCnt]['item_id']."_".$item_id_no_mod[$nCnt]['item_no']."_".$title;
						$update_count++; 	// Add update OK message 2009/01/23 A.Suzuki
							
						// Add review mail setting 2009/09/30 Y.Nakao --start--
						array_push($review_item, array(	"item_id" => $item_id_no_mod[$nCnt]['item_id'],
														"item_no" => $item_id_no_mod[$nCnt]['item_no'],
														"ins_user_id" => $ind_user_id,
														"mod_user_id" => $mod_user_id,
														"review" => 1,
														"reject_reason" => "")
									);
						// Add review mail setting 2009/09/30 Y.Nakao --end--
						
		        	} elseif($this->select_review[$nCnt] == 2){
		        		// 却下処理
		        		$query = "UPDATE ". DATABASE_PREFIX ."repository_item ".
		                    	 "SET review_status = ?, ".
		        				 "review_date = ?, ".
		        				 "reject_status = ?, ".
		        				 "reject_date = ?, ".
		        				 "reject_reason = ?, ".
		        				 "shown_status = ?, ".
		                    	 "mod_user_id = ?, ".
								 "mod_date = ?, ".
								 "is_delete = ? ".
		                    	 "WHERE item_id = ? AND ".
		        				 "item_no = ?; ";
						$params = null;
						$params[] = 0;								// review_status
						$params[] = $this->TransStartDate;			// review_date
						$params[] = 1;								// reject_status
						$params[] = $this->TransStartDate;			// reject_date
						if($this->reject_reason[$nCnt] == " "){
							$params[] = "";
							$this->reject_reason[$nCnt] = "";
						} else {
							$params[] = $this->reject_reason[$nCnt];	// reject_reason
						}
						$params[] = 0;								// shown_status
						$params[] = $user_id;						// mod_user_id
						$params[] = $this->TransStartDate;			// mod_date
						$params[] = 0;								// is_delete
						$params[] = $item_id_no_mod[$nCnt]['item_id'];	// item_id
						$params[] = $item_id_no_mod[$nCnt]['item_no'];	// item_no
			            //UPDATE実行
		        	    $result = $this->Db->execute($query,$params);
						if($result === false){
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                $this->Session->setParameter("error_code", $errMsg);
			                //エラー処理を行う
			                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
			                throw $exception;
						}
						
						// 新着情報から削除	
						$this->deleteWhatsnew($item_id_no_mod[$nCnt]['item_id']);
						
						$update_count++; 	// Add update OK message 2009/01/23 A.Suzuki
						
						// Add review mail setting 2009/09/30 Y.Nakao --start--
						array_push($review_item, array(	"item_id" => $item_id_no_mod[$nCnt]['item_id'],
														"item_no" => $item_id_no_mod[$nCnt]['item_no'],
														"ins_user_id" => $ind_user_id,
														"mod_user_id" => $mod_user_id,
														"review" => 0,
														"reject_reason" => $this->reject_reason[$nCnt])
									);
						// Add review mail setting 2009/09/30 Y.Nakao --end--
		        	}// 0は未承認のため何もしない
		        }
		        // Add review mail setting 2009/09/30 Y.Nakao --start--
		        // 査読結果通知メール送信処理
		        $this->sendMailReviewResult($review_item);
		        // Add review mail setting 2009/09/30 Y.Nakao --end--
	        }
	        
	        // サプリコンテンツの査読
	        else{
	        	// Sessionからitem_id,item_no,mod_dateを取得
		        $supple_data = $this->Session->getParameter("supple_data_for_review");
		        
				$update_count = 0; 	// Add update OK message 2009/01/23 A.Suzuki
				$review_item_supple = array();
				
		        for($nCnt=0;$nCnt<count($this->select_supple_review);$nCnt++){
		        	// サプリコンテンツのアップデート確認
		        	// 更新前に対象レコードをロックする。
		            // 最低限、更新日時（mod_date）のみ取得すれば良い。
		            // 必要であれば他のカラムを取得しても良い。
		            $query = "SELECT * ".
		                     "FROM ". DATABASE_PREFIX ."repository_supple ".
		                     "WHERE item_id = ? ".
		            		 "AND item_no = ? ".
		            		 "AND attribute_id = ? ".
		            		 "AND supple_no = ? ".
		                     "AND is_delete = ? ".
		            		 "AND mod_date = ? ".
		                     "FOR UPDATE; ";
					$params = null;
					$params[] = $supple_data[$nCnt]['item_id'];			// item_id
					$params[] = $supple_data[$nCnt]['item_no'];			// item_no
					$params[] = $supple_data[$nCnt]['attribute_id'];	// attribute_id
					$params[] = $supple_data[$nCnt]['supple_no'];		// supple_no
					$params[] = 0;										// is_delete
		            $params[] = $supple_data[$nCnt]['mod_date'];		// mod_date
		            $ret = $this->Db->execute($query, $params);
		            //SQLエラーの場合
		            if($ret === false) {
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", $errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            //取得結果が0件の場合
		            //この場合、UPDATE対象のレコードは存在しないこととなる。
		            //以降のUPDATE処理は行わないこと。
		            if(count($ret)==0) {
		                $this->Session->setParameter("error_code", 7);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            
		            // 査読実施後に前回の更新者(編集者)の情報が消えるので保持しておく
		            $ind_user_id = $ret[0]['ins_user_id'];
		            $mod_user_id = $ret[0]['mod_user_id'];
		            
		            // UpDate OK
		        	if($this->select_supple_review[$nCnt] == 1){
		        		// 承認処理
		        		$query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
		                    	 "SET supple_review_status = ?, ".
		        				 "supple_review_date = ?, ".
		        				 "mod_user_id = ?, ".
								 "mod_date = ? ".
		                    	 "WHERE item_id = ? ".
		        				 "AND item_no = ? ".
		        				 "AND attribute_id = ? ".
		            			 "AND supple_no = ?;";
						$params = array();
						$params[] = 1;						// supple_review_status
						$params[] = $this->TransStartDate;	// supple_review_date
						$params[] = $user_id;	// mod_user_id
						$params[] = $this->TransStartDate;	// mod_date
						$params[] = $supple_data[$nCnt]['item_id'];			// item_id
						$params[] = $supple_data[$nCnt]['item_no'];			// item_no
						$params[] = $supple_data[$nCnt]['attribute_id'];	// attribute_id
						$params[] = $supple_data[$nCnt]['supple_no'];		// supple_no
			            //UPDATE実行
		        	    $result = $this->Db->execute($query,$params);
						if($result === false){
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                $this->Session->setParameter("error_code", $errMsg);
			                //エラー処理を行う
			                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
			                throw $exception;
						}
						$update_count++;
						
						// メール送信用情報作成
						array_push($review_item_supple, array(	"item_id" => $supple_data[$nCnt]['item_id'],
																"item_no" => $supple_data[$nCnt]['item_no'],
																"attribute_id" => $supple_data[$nCnt]['attribute_id'],
																"supple_no" => $supple_data[$nCnt]['supple_no'],
																"ins_user_id" => $ind_user_id,
																"mod_user_id" => $mod_user_id,
																"review" => 1,
																"reject_reason" => "")
									);
						
		        	} elseif($this->select_supple_review[$nCnt] == 2){
		        		// 却下処理
		        		$query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
		                    	 "SET supple_review_status = ?, ".
		        				 "supple_review_date = ?, ".
		        				 "supple_reject_status = ?, ".
		        				 "supple_reject_date = ?, ".
		        				 "supple_reject_reason = ?, ".
		        				 "mod_user_id = ?, ".
								 "mod_date = ? ".
		                    	 "WHERE item_id = ? ".
		        				 "AND item_no = ? ".
		        				 "AND attribute_id = ? ".
		            			 "AND supple_no = ?;";
						$params = array();
						$params[] = 0;						// supple_review_status
						$params[] = $this->TransStartDate;	// supple_review_date
						$params[] = 1;						// supple_reject_status
						$params[] = $this->TransStartDate;	// supple_reject_date
		        		if($this->supple_reject_reason[$nCnt] == " "){
							$params[] = "";
						} else {
							$params[] = $this->supple_reject_reason[$nCnt];	// supple_reject_reason
						}
						$params[] = $user_id;	// mod_user_id
						$params[] = $this->TransStartDate;	// mod_date
						$params[] = $supple_data[$nCnt]['item_id'];			// item_id
						$params[] = $supple_data[$nCnt]['item_no'];			// item_no
						$params[] = $supple_data[$nCnt]['attribute_id'];	// attribute_id
						$params[] = $supple_data[$nCnt]['supple_no'];		// supple_no
			            //UPDATE実行
		        	    $result = $this->Db->execute($query,$params);
						if($result === false){
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                $this->Session->setParameter("error_code", $errMsg);
			                //エラー処理を行う
			                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
			                throw $exception;
						}
						$update_count++;
						
						// メール送信用情報作成
						array_push($review_item_supple, array(	"item_id" => $supple_data[$nCnt]['item_id'],
																"item_no" => $supple_data[$nCnt]['item_no'],
																"attribute_id" => $supple_data[$nCnt]['attribute_id'],
																"supple_no" => $supple_data[$nCnt]['supple_no'],
																"ins_user_id" => $ind_user_id,
																"mod_user_id" => $mod_user_id,
																"review" => 0,
																"reject_reason" => $this->supple_reject_reason[$nCnt])
									);
		        	}// 0は未承認のため何もしない
		        }
		        // Add review mail setting 2009/09/30 Y.Nakao --start--
		        // 査読結果通知メール送信処理
		        $this->sendMailReviewResult($review_item_supple);
		        // Add review mail setting 2009/09/30 Y.Nakao --end--
	        }
	        
	        //アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
			if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;                              //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
				//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				throw $exception;
			}
			
			// Add update OK message 2009/01/23 A.Suzuki --start--
	        if($update_count>0){
	        	$this->Session->setParameter("redirect_flg", "review");
	        	return 'redirect';
	        }
	        // Add update OK message 2009/01/23 A.Suzuki --end--
			
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
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる

	        //異常終了
    	    return "error";
		}
    }
    
    // Add review mail setting 2009/09/30 Y.Nakao --start--
    /**
     * send mail for review result
     * 査読結果通知メール送信処理
     * 
     * @param array $review_item 査読アイテム
     * 				[ii]['item_id']
     * 				[ii]['item_no']
     * 				[ii]['attribute_id'](supple only)
     * 				[ii]['supple_id'](supple only)
     * 				[ii]['ins_user_id']
     * 				[ii]['mod_user_id']
     * 				[ii]['review']  : 1->accept, 0->reject
     * 				[ii]['reject_reason']
     */
    function sendMailReviewResult($review_item){
    	// 言語リソース取得
    	$smartyAssign = $this->Session->getParameter("smartyAssign");
    	
    	// page_idおよびblock_idを取得
		$block_info = $this->getBlockPageId();
    	
    	// array for send mail info
    	// 送信するメール情報を保持する配列
    	// キーはユーザID
    	$send_mail = array();
    	for($ii=0; $ii<count($review_item); $ii++){
    		// 登録者のユーザIDを取得
    		$ins_user_id = $review_item[$ii]['ins_user_id'];
    		// 登録者が通知メールを受け取るかどうかチェック(チェックは1回のみ)
    		if( !array_key_exists($ins_user_id, $send_mail)){
    			$send_mail[$ins_user_id] = array();
    			$send_mail[$ins_user_id]['review_mail_flg'] = false;
    			// コンテンツ登録者が通知メールを受信するかチェック
    			$query = "SELECT contents_mail_flg, supple_mail_flg ".
    					" FROM ".DATABASE_PREFIX."repository_users ".
    					" WHERE user_id = ?; ";
    			$param = array();
    			$param[] = $ins_user_id;
    			$result = $this->Db->execute($query,$param);
				if($result === false){
	                //必要であればSQLエラー番号・メッセージ取得
	                $errMsg = $this->Db->ErrorMsg();
	                //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
				}
				
				if(count($result) != 0 && (($result[0]['contents_mail_flg']==1 && $this->type=="item") || ($result[0]['supple_mail_flg']==1 && $this->type=="supple"))){
					$send_mail[$ins_user_id]['review_mail_flg'] = true;
					$send_mail[$ins_user_id]['e-mail'] = "";
					$send_mail[$ins_user_id]['e-mail_mobile'] = "";
					// メールアドレスを取得
					$query = "SELECT * FROM ".DATABASE_PREFIX."users_items_link ".
							" WHERE user_id = ? ".
							" AND (item_id = ? OR item_id = ?) ".
							" AND email_reception_flag = ? ".
							" AND content != ''; ";
					$param = array();
					$param[] = $ins_user_id;
					$param[] = 5;	// email address
					$param[] = 6;	// mobile email address
					$param[] = 1;	// email reception = 1, not = 0
					$result = $this->Db->execute($query, $param);
					if($result === false){
						$this->error_msg = $this->Db->ErrorMsg();
						//アクション終了処理
						$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
						return false;
					}
					for($jj=0; $jj<count($result); $jj++){
						if($result[$jj]['item_id'] == 5){
							$send_mail[$ins_user_id]['e-mail'] = $result[$jj]['content'];
						} else if($result[$jj]['item_id'] == 6){
							$send_mail[$ins_user_id]['e-mail_mobile'] = $result[$jj]['content'];
						}
					}
				}
				
    			// 登録者がメールを受信する場合、メール情報を作成する
	    		if($send_mail[$ins_user_id]['review_mail_flg']){
	    			if($this->type == "item"){
	    				// アイテムの査読の場合
	    				$send_mail[$ins_user_id]["subject"] = $smartyAssign->getLang("repository_mail_review_contents_subject");
	    			} else {
	    				// サプリコンテンツの査読の場合
		    			$send_mail[$ins_user_id]["subject"] = $smartyAssign->getLang("repository_mail_review_supple_subject");
	    			}
	    		}
    		}
    		
    		// 編集者のユーザIDを取得
    		$mod_user_id = $review_item[$ii]['mod_user_id'];
    		// 登録者と編集者が一致しない場合、編集者用のメール設定を作成
    		if($ins_user_id != $mod_user_id){// 編集者が通知メールを受け取るかどうかチェック(チェックは1回のみ)
	    		if( !array_key_exists($mod_user_id, $send_mail)){
	    			$send_mail[$mod_user_id] = array();
	    			$send_mail[$mod_user_id]['review_mail_flg'] = false;
	    			// コンテンツ編集者が通知メールを受信するかチェック
	    			$query = "SELECT contents_mail_flg, supple_mail_flg ".
	    					" FROM ".DATABASE_PREFIX."repository_users ".
	    					" WHERE user_id = ?; ";
	    			$param = array();
	    			$param[] = $mod_user_id;
	    			$result = $this->Db->execute($query,$param);
					if($result === false){
		                //必要であればSQLエラー番号・メッセージ取得
		                $errMsg = $this->Db->ErrorMsg();
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
					}
					
	    			if(count($result) != 0 && (($result[0]['contents_mail_flg']==1 && $this->type=="item") || ($result[0]['supple_mail_flg']==1 && $this->type=="supple"))){
						$send_mail[$mod_user_id]['review_mail_flg'] = true;
						$send_mail[$mod_user_id]['e-mail'] = "";
						$send_mail[$mod_user_id]['e-mail_mobile'] = "";
						// メールアドレスを取得
						$query = "SELECT * FROM ".DATABASE_PREFIX."users_items_link ".
								" WHERE user_id = ? ".
								" AND (item_id = ? OR item_id = ?) ".
								" AND email_reception_flag = ? ".
								" AND content != ''; ";
						$param = array();
						$param[] = $mod_user_id;
						$param[] = 5;	// email address
						$param[] = 6;	// mobile email address
						$param[] = 1;	// email reception = 1, not = 0
						$result = $this->Db->execute($query, $param);
						if($result === false){
							$this->error_msg = $this->Db->ErrorMsg();
							//アクション終了処理
							$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
							return false;
						}
						for($jj=0; $jj<count($result); $jj++){
							if($result[$jj]['item_id'] == 5){
								$send_mail[$mod_user_id]['e-mail'] = $result[$jj]['content'];
							} else if($result[$jj]['item_id'] == 6){
								$send_mail[$mod_user_id]['e-mail_mobile'] = $result[$jj]['content'];
							}
						}
					}
					
		    		// 編集者がメールを受信する場合、メール情報を作成する
		    		if($send_mail[$ins_user_id]['review_mail_flg']){
		    			if($this->type == "item"){
		    				// アイテムの査読の場合
		    				$send_mail[$mod_user_id]["subject"] = $smartyAssign->getLang("repository_mail_review_contents_subject");
		    			} else {
		    				// サプリコンテンツの査読の場合
			    			$send_mail[$mod_user_id]["subject"] = $smartyAssign->getLang("repository_mail_review_supple_subject");
		    			}
		    		}
	    		}
    		}
    		
    		// -----------------------------------------------------------------------------------
			// コンテンツ情報取得 / get contents data
			//	[アイテム査読 / item review]
			//   	コンテンツタイトル / contents title
			//   	コンテンツ詳細画面URL / contents detail page URL
			//		査読結果 / review result
			//		却下理由 / reject reason
			//	[サプリメンタルコンテンツ査読 / supplemental contents review]
			//		サプリメンタルコンテンツタイトル / supplemental contents title
			//		サプリメンタルコンテンツ詳細画面URL / supplemental contents detail page URL
			//		サプリメンタルコンテンツ登録先コンテンツタイトル / is registed contents title
			//		サプリメンタルコンテンツ登録先コンテンツ詳細画面URL / is registed contents detail page URL
			//		査読結果 / review result
			//		却下理由 / reject reason
			// -----------------------------------------------------------------------------------
    		if($send_mail[$ins_user_id]['review_mail_flg'] === true || $send_mail[$mod_user_id]['review_mail_flg'] === true){
    			if($this->type == "item"){
    				// アイテム査読の場合
    				$query = "SELECT title, title_english, uri ".
    						 "FROM ".DATABASE_PREFIX."repository_item ".
    						 "WHERE item_id = ? ".
    						 "AND item_no = ? ".
    						 "AND is_delete = ?;";
    				$params = array();
    				$params[] = $review_item[$ii]['item_id'];
    				$params[] = $review_item[$ii]['item_no'];
    				$params[] = 0;
    				$result = $this->Db->execute($query, $params);
    				if($result === false){
    					$this->error_msg = $this->Db->ErrorMsg();
						//アクション終了処理
						$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
						return false;
    				}
    				
    				// メール本文
					// set Mail body
					if($send_mail[$ins_user_id]['review_mail_flg'] === true){
						// ヘッダ & フッタ
						if($send_mail[$ins_user_id]['header'] == ""){
							$send_mail[$ins_user_id]['header'] = $smartyAssign->getLang("repository_mail_review_contents_body")."\n\n";
							$send_mail[$ins_user_id]['header'] .= $smartyAssign->getLang("repository_mail_review_contents")."\n";
							$send_mail[$ins_user_id]['body'] = "";
							$send_mail[$ins_user_id]['footer'] = $smartyAssign->getLang("repository_mail_review_contents_setting")."\n";
							$send_mail[$ins_user_id]['footer'] .= $smartyAssign->getLang("repository_mail_review_contents_setting_url")."\n";
							$send_mail[$ins_user_id]['footer'] .= BASE_URL;
							if(substr(BASE_URL,-1,1) != "/"){
								$send_mail[$ins_user_id]['footer'] .= "/";
							}
							$send_mail[$ins_user_id]['footer'] .= "?active_action=repository_view_main_workflow".
																  "&page_id=".$block_info['page_id'].
																  "&block_id=".$block_info['block_id'];
						}
						
						// タイトル
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_title");
						if($this->Session->getParameter("_lang") == "japanese"){
							if($result[0]['title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $result[0]['title']."\n";
							} else if($result[0]['title_english'] != ""){
								$send_mail[$ins_user_id]['body'] .= $result[0]['title_english']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						} else {
							if($result[0]['title_english'] != ""){
								$send_mail[$ins_user_id]['body'] .= $result[0]['title_english']."\n";
							} else if($result[0]['title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $result[0]['title']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						}
						
						// 詳細画面URL
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_detailurl");
						$send_mail[$ins_user_id]['body'] .= $result[0]['uri']."\n";
						
						// 査読結果
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_result");
						if($review_item[$ii]['review'] == 1){
							// 承認
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_accept")."\n\n";
						} else {
							// 却下
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_reject")."\n";
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_reject_reason");
							$send_mail[$ins_user_id]['body'] .= $review_item[$ii]['reject_reason']."\n\n";
							$send_mail[$ins_user_id]['reject_flag'] = true;
						}
					}
					if($ins_user_id != $mod_user_id){
	    				if($send_mail[$mod_user_id]['review_mail_flg'] === true){
	    					if($send_mail[$mod_user_id]['header'] == ""){
								$send_mail[$mod_user_id]['header'] = $smartyAssign->getLang("repository_mail_review_body")."\n\n";
								$send_mail[$mod_user_id]['header'] .= $smartyAssign->getLang("repository_mail_review_contents")."\n";
								$send_mail[$mod_user_id]['header'] .= $smartyAssign->getLang("repository_mail_review_title");
								$send_mail[$mod_user_id]['body'] = "";
								$send_mail[$mod_user_id]['footer'] = $smartyAssign->getLang("repository_mail_review_contents_setting")."\n";
								$send_mail[$mod_user_id]['footer'] .= $smartyAssign->getLang("repository_mail_review_contents_setting_url")."\n";
		    					$send_mail[$mod_user_id]['footer'] .= BASE_URL;
								if(substr(BASE_URL,-1,1) != "/"){
									$send_mail[$mod_user_id]['footer'] .= "/";
								}
								$send_mail[$mod_user_id]['footer'] .= "?active_action=repository_view_main_workflow".
																	  "&page_id=".$block_info['page_id'].
																	  "&block_id=".$block_info['block_id'];
	    					}
	    				
							// タイトル
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_title");
							if($this->Session->getParameter("_lang") == "japanese"){
								if($result[0]['title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $result[0]['title']."\n";
								} else if($result[0]['title_english'] != ""){
									$send_mail[$mod_user_id]['body'] .= $result[0]['title_english']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							} else {
								if($result[0]['title_english'] != ""){
									$send_mail[$mod_user_id]['body'] .= $result[0]['title_english']."\n";
								} else if($result[0]['title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $result[0]['title']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							}
							
							// 詳細画面URL
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_detailurl");
							$send_mail[$mod_user_id]['body'] .= $result[0]['uri']."\n";
							
							// 査読結果
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_result");
							if($review_item[$ii]['review'] == 1){
								// 承認
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_accept")."\n\n";
							} else {
								// 却下
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_reject")."\n";
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_contents_reject_reason");
								$send_mail[$mod_user_id]['body'] .= $review_item[$ii]['reject_reason']."\n\n";
								$send_mail[$mod_user_id]['reject_flag'] = true;
							}
						}
					}
    			} else {
    				// サプリコンテンツ査読の場合
    				$query = "SELECT supple_title, supple_title_en, uri ".
    						 "FROM ".DATABASE_PREFIX."repository_supple ".
    						 "WHERE item_id = ? ".
    						 "AND item_no = ? ".
    						 "AND attribute_id = ? ".
    						 "AND supple_no = ? ".
    						 "AND is_delete = ?;";
    				$params = array();
    				$params[] = $review_item[$ii]['item_id'];
    				$params[] = $review_item[$ii]['item_no'];
    				$params[] = $review_item[$ii]['attribute_id'];
    				$params[] = $review_item[$ii]['supple_no'];
    				$params[] = 0;
    				$supple_data = $this->Db->execute($query, $params);
    				if($supple_data === false){
    					$this->error_msg = $this->Db->ErrorMsg();
						//アクション終了処理
						$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
						return false;
    				}
    				
    				$query = "SELECT title, title_english, uri ".
    						 "FROM ".DATABASE_PREFIX."repository_item ".
    						 "WHERE item_id = ? ".
    						 "AND item_no = ? ".
    						 "AND is_delete = ?;";
    				$params = array();
    				$params[] = $review_item[$ii]['item_id'];
    				$params[] = $review_item[$ii]['item_no'];
    				$params[] = 0;
    				$item_data = $this->Db->execute($query, $params);
    				if($item_data === false){
    					$this->error_msg = $this->Db->ErrorMsg();
						//アクション終了処理
						$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
						return false;
    				}
    				
    				// メール本文
					// set Mail body
					if($send_mail[$ins_user_id]['review_mail_flg'] === true){
						// ヘッダ & フッタ
						if($send_mail[$ins_user_id]['header'] == ""){
							$send_mail[$ins_user_id]['header'] = $smartyAssign->getLang("repository_mail_review_supple_body")."\n\n";
							$send_mail[$ins_user_id]['header'] .= $smartyAssign->getLang("repository_mail_review_supple")."\n";
							$send_mail[$ins_user_id]['body'] = "";
							$send_mail[$ins_user_id]['footer'] = $smartyAssign->getLang("repository_mail_review_supple_setting")."\n";
							$send_mail[$ins_user_id]['footer'] .= $smartyAssign->getLang("repository_mail_review_contents_setting_url")."\n";
							$send_mail[$ins_user_id]['footer'] .= BASE_URL;
							if(substr(BASE_URL,-1,1) != "/"){
								$send_mail[$ins_user_id]['footer'] .= "/";
							}
							$send_mail[$ins_user_id]['footer'] .= "?active_action=repository_view_main_suppleworkflow".
																  "&page_id=".$block_info['page_id'].
																  "&block_id=".$block_info['block_id'];
						}
						
						// サプリコンテンツタイトル
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_title");
						if($this->Session->getParameter("_lang") == "japanese"){
							if($supple_data[0]['supple_title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $supple_data[0]['supple_title']."\n";
							} else if($supple_data[0]['supple_title_en'] != ""){
								$send_mail[$ins_user_id]['body'] .= $supple_data[0]['supple_title_en']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						} else {
							if($supple_data[0]['supple_title_en'] != ""){
								$send_mail[$ins_user_id]['body'] .= $supple_data[0]['supple_title_en']."\n";
							} else if($supple_data[0]['supple_title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $supple_data[0]['supple_title']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						}
						
						// サプリコンテンツ詳細画面URL
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_detailurl");
						$send_mail[$ins_user_id]['body'] .= $supple_data[0]['uri']."\n";
						
						// 登録先アイテムタイトル
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_title_is_registed");
						if($this->Session->getParameter("_lang") == "japanese"){
							if($item_data[0]['title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $item_data[0]['title']."\n";
							} else if($item_data[0]['title_english'] != ""){
								$send_mail[$ins_user_id]['body'] .= $item_data[0]['title_english']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						} else {
							if($item_data[0]['title_english'] != ""){
								$send_mail[$ins_user_id]['body'] .= $item_data[0]['title_english']."\n";
							} else if($item_data[0]['title'] != ""){
								$send_mail[$ins_user_id]['body'] .= $item_data[0]['title']."\n";
							} else {
								$send_mail[$ins_user_id]['body'] .= "no title\n";
							}
						}
						
						// 登録先アイテム詳細画面URL
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_detailurl_is_registed");
						$send_mail[$ins_user_id]['body'] .= $item_data[0]['uri']."\n";
						
						// 査読結果
						$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_result");
						if($review_item[$ii]['review'] == 1){
							// 承認
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_accept")."\n\n";
						} else {
							// 却下
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_reject")."\n";
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_reject_reason");
							$send_mail[$ins_user_id]['body'] .= $review_item[$ii]['reject_reason']."\n\n";
							$send_mail[$ins_user_id]['reject_flag'] = true;
						}
					}
					if($ins_user_id != $mod_user_id){
	    				if($send_mail[$mod_user_id]['review_mail_flg'] === true){
	    					if($send_mail[$mod_user_id]['header'] == ""){
								$send_mail[$mod_user_id]['header'] = $smartyAssign->getLang("repository_mail_review_supple_body")."\n\n";
								$send_mail[$mod_user_id]['header'] .= $smartyAssign->getLang("repository_mail_review_supple")."\n";
								$send_mail[$mod_user_id]['body'] = "";
								$send_mail[$mod_user_id]['footer'] = $smartyAssign->getLang("repository_mail_review_supple_setting")."\n";
								$send_mail[$mod_user_id]['footer'] .= $smartyAssign->getLang("repository_mail_review_contents_setting_url")."\n";
		    					$send_mail[$mod_user_id]['footer'] .= BASE_URL;
								if(substr(BASE_URL,-1,1) != "/"){
									$send_mail[$mod_user_id]['footer'] .= "/";
								}
								$send_mail[$mod_user_id]['footer'] .= "?active_action=repository_view_main_suppleworkflow".
																	  "&page_id=".$block_info['page_id'].
																	  "&block_id=".$block_info['block_id'];
	    					}
	    				
							// サプリコンテンツタイトル
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_title");
							if($this->Session->getParameter("_lang") == "japanese"){
								if($supple_data[0]['supple_title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $supple_data[0]['supple_title']."\n";
								} else if($supple_data[0]['supple_title_en'] != ""){
									$send_mail[$mod_user_id]['body'] .= $supple_data[0]['supple_title_en']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							} else {
								if($supple_data[0]['supple_title_en'] != ""){
									$send_mail[$mod_user_id]['body'] .= $supple_data[0]['supple_title_en']."\n";
								} else if($supple_data[0]['supple_title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $supple_data[0]['supple_title']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							}
							
							// サプリコンテンツ詳細画面URL
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_detailurl");
							$send_mail[$mod_user_id]['body'] .= $supple_data[0]['uri']."\n";
							
							// 登録先アイテムタイトル
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_title_is_registed");
							if($this->Session->getParameter("_lang") == "japanese"){
								if($result[0]['title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $item_data[0]['title']."\n";
								} else if($item_data[0]['supple_title_en'] != ""){
									$send_mail[$mod_user_id]['body'] .= $item_data[0]['title_english']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							} else {
								if($item_data[0]['title_english'] != ""){
									$send_mail[$mod_user_id]['body'] .= $item_data[0]['title_english']."\n";
								} else if($item_data[0]['title'] != ""){
									$send_mail[$mod_user_id]['body'] .= $item_data[0]['title']."\n";
								} else {
									$send_mail[$mod_user_id]['body'] .= "no title\n";
								}
							}
							
							// 登録先アイテム詳細画面URL
							$send_mail[$ins_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_detailurl_is_registed");
							$send_mail[$ins_user_id]['body'] .= $item_data[0]['uri']."\n";
							
							// 査読結果
							$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_result");
							if($review_item[$ii]['review'] == 1){
								// 承認
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_accept")."\n\n";
							} else {
								// 却下
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_reject")."\n";
								$send_mail[$mod_user_id]['body'] .= $smartyAssign->getLang("repository_mail_review_supple_reject_reason");
								$send_mail[$mod_user_id]['body'] .= $review_item[$ii]['reject_reason']."\n\n";
								$send_mail[$mod_user_id]['reject_flag'] = true;
							}
						}
					}
    			}
    		}
    	}
    	
    	// 査読結果メール送信処理
		foreach($send_mail as $val){
			// ユーザ毎にメールを送信
			
			// send review mail
			// 査読通知メールを送信する
			// 件名
			// set subject
			$this->mailMain->setSubject($val['subject']);
			
			// メール本文をリソースから読み込む
			// set Mail body
			$mail_body = "";
			$mail_body .= $val['header'];
			$mail_body .= $val['body'];
			if($val['reject_flag']){
				if($this->type == "item"){
					$mail_body .= $smartyAssign->getLang("repository_mail_review_contents_reject_close")."\n\n";
				} else {
					$mail_body .= $smartyAssign->getLang("repository_mail_review_supple_reject_close")."\n\n";
				}
			}
			$mail_body .= $val['footer'];
			$this->mailMain->setBody($mail_body);
			
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
			$users = array();
			if($val['e-mail'] != ""){
				array_push($users, array("email" => $val['e-mail']));
			}
			if($val['e-mail_mobile'] != ""){
				array_push($users, array("email" => $val['e-mail_mobile']));
			}
			$this->mailMain->setToUsers($users);
			
			// ---------------------------------------------
			// メール送信
			// send confirm mail
			// ---------------------------------------------
			if(count($users) > 0){
				// 送信者がいる場合は送信
				$return = $this->mailMain->send();
				if($return === false){
					$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
					return false;
				}
			}
		}
				 
		// 言語リソース開放
		$this->Session->removeParameter("smartyAssign");
    }
    // Add review mail setting 2009/09/30 Y.Nakao --end--
}
?>
