<?php
// --------------------------------------------------------------------
//
// $Id: Adddb.class.php 30197 2013-12-19 09:55:45Z rei_matsuura $
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
 * repositoryモジュール アイテムタイプ作成 アイテムタイプDB登録アクション
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Adddb extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	//var $session = null;
	//var $db = null;
	
	// リクエストパラメタ
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータタイプ配列
	var $metadata_required = null;	// メタデータ必須フラグ列
	var $metadata_disp = null;		// メタデータ一覧表示フラグ列
	
	// 2008/02/28
	var $metadata_candidate = null;	// メタデータ選択候補配列
	var $metadata_plural = null;	// メタデータ複数可否配列
	var $metadata_newline = null;	// メタデータ改行指定配列
	
	// 2009/01/28
	var $metadata_hidden = null;	// メタデータ非表示設定配列
		
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
	    	// 注:このアクションに飛ぶ場合、全てのDBバリデートはすんでいるものとする。
	    	// つまり、即座にレコードを追加できるものとする。
	    	
	    	//
	    	// 準備処理, セッション情報の取得
	    	//
	    	$metadata_num = $this->Session->getParameter("metadata_num");
	    	$item_type_name = $this->Session->getParameter("item_type_name");
	    	$this->metadata_title = $this->Session->getParameter("metadata_title");
	    	$this->metadata_type = $this->Session->getParameter("metadata_type");
	    	$this->metadata_required = $this->Session->getParameter("metadata_required");
	    	$this->metadata_disp = $this->Session->getParameter("metadata_disp");
	    	$this->metadata_candidate = $this->Session->getParameter("metadata_candidate");
	    	// 2008/03/04
	    	$this->metadata_plural = $this->Session->getParameter("metadata_plural");
	    	$this->metadata_newline = $this->Session->getParameter("metadata_newline");
	    	// 2009/01/28
	    	$this->metadata_hidden = $this->Session->getParameter("metadata_hidden");

	    	// ユーザID取得
	    	$user_id = $this->Session->getParameter("_user_id");
            $attrMultiID = array();
	    	
	    	//////////////////// 新規登録の場合 //////////////////////
	    	if($this->Session->getParameter("item_type_edit_flag") == 0){
		
		    	//
		    	// アイテムタイプIDを決める
		    	//
		    	$item_type_id = $this->Db->nextSeq("repository_item_type");
		    	/*
		    	while(1) {
		    		/* 2008/03/06 クエリー変更
					$count = $this->db->countExecute(
						"repository_item_type",array("item_type_id" => $item_type_id)
					);
					if($count === false){
						return 'error';
					}
		    	    if ($count == 0) {
						break;
		    		}
					
		    		$query = "SELECT * ".
                     		 "FROM ". DATABASE_PREFIX ."repository_item_type ".
                     		 "WHERE item_type_id = ?; ";
		    		$params = null;
		            $params[] = $item_type_id;
            		//SELECT実行
            		$result = $this->Db->execute($query, $params);
            		if($result === false){
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                //エラー処理を行う
		                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
		                //throw $exception;
		                return 'error';
            		}
            		if(!(isset($result[0]))){
            			break;
            		}
		    		$item_type_id++;
		    	}
		    	*/
			    $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type ".
			    		 "(item_type_id, item_type_name, item_type_short_name, ".
			    		 "explanation, mapping_info, icon_name , icon_mime_type, icon_extension, icon, ".
			    		 "ins_user_id, mod_user_id, ".
			    		 "del_user_id, ins_date, mod_date, del_date, is_delete) ".
	                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
	            $params[] = $item_type_id;		// item_type_id
	            $params[] = $item_type_name;	// item_type_name
	            $params[] = $item_type_name;	// item_type_short_name
	            $params[] = "";					// explanation
	            $params[] = "";					// mapping_info
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // session.icon_edit -> const
	            if( $this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_UPLOAD_ICON ){
	            	$itemtype_icon = $this->Session->getParameter("upload_icon");
	            	$params[] = $itemtype_icon['upload']['file_name'];	// icon_name
		            $params[] = $itemtype_icon['upload']['mimetype'];	// icon_mine_type
		            $params[] = $itemtype_icon['upload']['extension'];	// icon_extension
	            } else {
		            $params[] = "";					// icon_name
		            $params[] = "";					// icon_mine_type
		            $params[] = "";					// icon_extension
	            }
	            // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
	            $params[] = "";					// icon(BLOB)初めは空で登録
	            $params[] = $user_id;			// ins_user_id
	            $params[] = $user_id;			// mod_user_id
	            // user_idのString対応 2008/06/03 Y.Nakao --Start--
	            //$params[] = 0;					// del_user_id  
	            $params[] = "";					// del_user_id
	            // user_idのString対応 2008/06.03 Y.Nakao --End--
	            $params[] = $this->TransStartDate;	// ins_date
	            $params[] = $this->TransStartDate;	// mod_date
	            $params[] = "";					// del_date
	            $params[] = 0;					// is_delete
	            //INSERT実行
	            $result = $this->Db->execute($query, $params);
			    // ↑ 2008/02/26
				if ($result === false) {
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
		    	// アイコンがあればBLOBを登録 2008/07/22 Y.Nakao --start--
		    	// Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
		    	// session.icon_edit -> const
		    	if( $this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_UPLOAD_ICON ){
		    		$icon_name = $itemtype_icon['upload']['physical_file_name'];
		    		$filePath = WEBAPP_DIR. "/uploads/repository/";
		    		$ret = $this->Db->updateBlobFile(
						'repository_item_type',
						'icon',
						$filePath . $icon_name, 
						'item_type_id = '. $item_type_id,
						'LONGBLOB'
					);
					if ($ret === false) {
						// delete upload icon file
			    		unlink($filePath . $icon_name);
						//必要であればSQLエラー番号・メッセージ取得
						$errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", "icon DB INSERT ERROR".$errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
			    	}
			    	// delete upload icon file
			    	unlink($filePath . $icon_name);
		    	}
		    	// Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
				// アイコンがあればBLOBを登録 2008/07/22 Y.Nakao --end--
						    	
				//
				// アイテムタイプ属性テーブル登録 (n個)
				//
				for($ii=0; $ii<$metadata_num; $ii++) {
				    //
					// アイテムタイプ属性IDを決める(本当は主キーなどいらんと思うが、何故か主キーの無いテーブルで登録がうまくいかない)
					// 
					$item_type_attr_id = 1;
			    	while(1) {
			    		/* 2008/03/06 クエリー変更
						$count = $this->Db->countExecute(
							"repository_item_attr_type",array("item_type_id" => $item_type_id, "attribute_id" => $item_type_attr_id)
						);			    		
						if($count === false){
							return 'error';
						}
			    	    if ($count == 0) {
							break;
			    		}
						*/
			    		$query = "SELECT * ".
                     			 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
                     			 "WHERE item_type_id = ? AND ".
                     			 "attribute_id = ?; ";
			    		$params = null;
			            $params[] = $item_type_id;
            			$params[] = $item_type_attr_id;
            			//SELECT実行
            			$result = $this->Db->execute($query, $params);
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
            			if(!(isset($result[0]))){
            				break;
            			}
            			
			    		$item_type_attr_id++;
			    	}
			    	$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_type ". 
			    			 "(item_type_id, attribute_id, show_order, ".
			    			 " attribute_name, attribute_short_name, input_type, is_required, ".
			    			 " plural_enable, line_feed_enable, list_view_enable, hidden, ".
			    			 " junii2_mapping, dublin_core_mapping, lom_mapping, display_lang_type, ins_user_id, mod_user_id, ".
			    			 " del_user_id, ins_date, mod_date, del_date, is_delete) ".
	                		 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
					$params = null;
		            $params[] = $item_type_id;				// item_type_id
		            $params[] = $ii+1;						// attribute_id
		            $params[] = $ii+1;						// show_order
		            $params[] = $this->metadata_title[$ii];	// attribute_name
		            $params[] = $this->metadata_title[$ii];	// attribute_short_name
		            $params[] = $this->metadata_type[$ii];	// input_type
		            $params[] = $this->metadata_required[$ii];	// is_required
		            $params[] = $this->metadata_plural[$ii];	// plural_enable
		            $params[] = $this->metadata_newline[$ii];	// line_feed_enable
		            $params[] = $this->metadata_disp[$ii];		// list_view_enable
		            $params[] = $this->metadata_hidden[$ii];	// hidden
		            $params[] = "";								// junii2_mapping
		            $params[] = "";								// dublin_core_mapping
		            $params[] = "";                             // lom_mapping
		            $params[] = "";								// display_lang_type
		            $params[] = $user_id;						// ins_user_id
		            $params[] = $user_id;						// mod_user_id
		            // user_idのString対応 2008/06/03 Y.Nakao --Start--
		            $params[] = "";					// del_user_id
		            // user_idのString対応 2008/06.03 Y.Nakao --End--
		            $params[] = $this->TransStartDate;			// ins_date
		            $params[] = $this->TransStartDate;			// mod_date
		            $params[] = "";								// del_date
		            $params[] = 0;								// is_delete
		            //INSERT実行
		            $result = $this->Db->execute($query, $params);
		            
			    	// ↑ 2008/02/26
				   	if ($result === false) {
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
			    	
			    	// 選択候補のある属性の場合、アイテム属性入力候補テーブルに追加
					if($this->metadata_candidate[$ii] != ""){
						// データ有
						$str = $this->metadata_candidate[$ii];
						//$candidata = explode("|", $str);
						$candidata = explode("|", $this->metadata_candidate[$ii]);
						if($candidata === false){
			                //必要であればSQLエラー番号・メッセージ取得
			                $errNo = $this->Db->ErrorNo();
			                $errMsg = $this->Db->ErrorMsg();
			                //エラー処理を行う
			                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
			                //$DetailMsg = null;                              //詳細メッセージ文字列作成
			                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
			                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
			                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
			                //throw $exception;
						}
						for($nCnt=0;$nCnt<count($candidata);$nCnt++){
							$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_candidate ".
									 "(item_type_id, attribute_id, candidate_no, candidate_value, ".
									 " candidate_short_value, ins_user_id, mod_user_id, del_user_id, ".
									 " ins_date, mod_date, del_date, is_delete) ".
	                     			 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
							$params = null;
				            $params[] = $item_type_id;		// item_type_id
				            $params[] = $ii+1;				// attribute_id
				            $params[] = $nCnt+1;			// candidate_no
				            $params[] = $candidata[$nCnt];	// candidate_value
				            $params[] = $candidata[$nCnt];	// candidate_short_value
				            $params[] = $user_id;			// ins_user_id
				            $params[] = $user_id;			// mod_user_id
				            // user_idのString対応 2008/06/03 Y.Nakao --Start--
				            $params[] = "";					// del_user_id
				            // user_idのString対応 2008/06.03 Y.Nakao --End--
				            $params[] = $this->TransStartDate;	// ins_date
				            $params[] = $this->TransStartDate;	// mod_date
				            $params[] = "";					// del_date
				            $params[] = 0;					// is_delete
				            //INSERT実行
				            $result = $this->Db->execute($query, $params);
						   	if ($result === false) {
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
						}	
					}
					array_push($attrMultiID, $ii+1);
		    	}
		    	// エラーコード解除
				$this->Session->removeParameter("error_code");
				
				//アクション終了処理
				$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
				if ( $result === false ) {
					$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
					//$DetailMsg = null;                              //詳細メッセージ文字列作成
					//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
					//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
					throw $exception;
				}
				$this->addMultiLanguageTitle($item_type_id, $attrMultiID);
				// セッション情報初期化 for アイテムタイプ登録
		    	$this->Session->removeParameter("error_code");	// エラーメッセージ削除
		    	$this->Session->removeParameter("error_msg");	// エラーメッセージ削除
				// セッション情報初期化 for アイテムタイプ登録
		    	$this->Session->removeParameter("item_type_id");	// アイテムタイプID
		    	$this->Session->removeParameter("itemtype_name");	// アイテムタイプ名 削除
		    	$this->Session->removeParameter("metadata_title");	// メタデータ項目削除
		    	$this->Session->removeParameter("metadata_type");	// メタデータタイプ削除
		    	$this->Session->removeParameter("metadata_required");	// メタデータ必須フラグ削除
		    	$this->Session->removeParameter("metadata_disp");	// メタデータ一覧表示フラグ削除
		    	$this->Session->removeParameter("metadata_hidden");	// メタデータ非表示フラグ削除
		    	$this->Session->removeParameter("metadata_num");	// アイテムメタデータ数を1に
		    	
		    	// Add id server connect check for "file_price" 2009/04/01 Y.Nakao --start--
		        $this->Session->removeParameter("id_server");
		        // Add id server connect check for "file_price" 2009/04/01 Y.Nakao --end--
		    	
		        $this->Session->setParameter("redirect_flg", "itemtype");	// Add update OK message 2009/01/23 A.Suzuki
		        
		        return 'redirect';
//		    	return 'success_create';
	    	}
		   	//////////////// 既存編集の場合 2008/03/03 ///////////////////
	    	else if($this->Session->getParameter("item_type_edit_flag") == 1){
		    	// アイテムタイプ属性IDを取得
				$item_type_id = $this->Session->getParameter("item_type_id");
				if($item_type_id == null){
					return 'error';
				}
				// Sessionから、attrbute_id配列を取得
				$attribute_id = $this->Session->getParameter("attribute_id");
				
				//////////////////// データ更新 ///////////////////////
	    		//更新前に対象レコードをロックする。
	            //最低限、更新日時（mod_date）のみ取得すれば良い。
	            //必要であれば他のカラムを取得しても良い。
	            $query = "SELECT mod_date ".
	                     "FROM ". DATABASE_PREFIX ."repository_item_type ".
	                     "WHERE item_type_id = ? AND ".
	                     "is_delete = ? AND ".
	            		 "mod_date = ? ".
	                     "FOR UPDATE;";
				$params = null;
				$params[] = $item_type_id;			// item_type_id
				$params[] = 0;
	            $params[] = $this->Session->getParameter("item_type_update_date");
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
	                //throw $exception;
					//アクション終了処理
					$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
					if ( $result === false ) {
						$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
						//$DetailMsg = null;                              //詳細メッセージ文字列作成
						//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
						//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
						throw $exception;
					}
			        //異常終了 この場合アイテムタイプ選択に戻る
        			return "error_update";
	            }
	            // item_type更新
	    		$query = "UPDATE ". DATABASE_PREFIX ."repository_item_type ".
                    	 "SET item_type_name = ?, ". 
                    	 "mod_user_id = ?, ".
						 "mod_date = ?, ".
						 "is_delete = ? ";
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // session.icon_edit -> const
	    		if($this->Session->getParameter("icon_edit") != RepositoryConst::SESSION_PARAM_DATABASE_ICON){
		    		$query .= ", icon_name = ?, ".
		    				 "icon_mime_type = ?, ".
		    				 "icon_extension = ?, ".
		    				 "icon = ? ";
	    		}
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
                $query .= "WHERE item_type_id = ?; ";
				$params = null;
				$params[] = $this->Session->getParameter("item_type_name");
				$params[] = $user_id;							// mod_user_id
				$params[] = $this->TransStartDate;				// mod_date
				$params[] = 0;									// is_delete
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // session.icon_edit -> const
	    		if( $this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_UPLOAD_ICON ){
	            	$itemtype_icon = $this->Session->getParameter("upload_icon");
	            	$params[] = $itemtype_icon['upload']['file_name'];	// icon_name
		            $params[] = $itemtype_icon['upload']['mimetype'];	// icon_mine_type
		            $params[] = $itemtype_icon['upload']['extension'];	// icon_extension
		            $params[] = "";					// icon(BLOB)変更ありはNULL
	    		} else if( $this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_DATABASE_ICON ){
	    			// 変更なしのためなにもしない
	            } else {
		            $params[] = "";					// icon_name
		            $params[] = "";					// icon_mine_type
		            $params[] = "";					// icon_extension
		            $params[] = "";					// icon(BLOB)変更ありはNULL
	            }
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
	            
				$params[] = $item_type_id;						// item_type_id
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
				// アイコンがあればBLOBを登録 2008/07/22 Y.Nakao --start--
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // session.icon_edit -> const
		    	if( $this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_UPLOAD_ICON ){
		    		$icon_name = $itemtype_icon['upload']['physical_file_name'];
		    		$filePath = WEBAPP_DIR. "/uploads/repository/";
		    		$ret = $this->Db->updateBlobFile(
						'repository_item_type',
						'icon',
						$filePath . $icon_name, 
						'item_type_id = '. $item_type_id,
						'LONGBLOB'
					);
					if ($ret === false) {
						//必要であればSQLエラー番号・メッセージ取得
						$errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", "icon DB INSERT ERROR".$errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
			    	}
		    	}
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
				// アイコンがあればBLOBを登録 2008/07/22 Y.Nakao --end--
				
	            
	            //　アイテム属性タイプ更新
				for($nCnt=0;$nCnt<$metadata_num;$nCnt++){
					if($attribute_id[$nCnt] != -1){
						//////////////////////// 更新 /////////////////////////
						$count = $nCnt + 1;
						$query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_type ".
	                    		 "SET input_type = ?, ".
								 "is_required = ?, ".
								 "list_view_enable = ?, ".
								 "attribute_name = ?, ".
								 "attribute_short_name = ?, ".
								 "show_order = ?, ".
								 "line_feed_enable = ?, ".
								 "plural_enable = ?, ".
								 "hidden = ?, ".
								 "mod_user_id = ?, ".
								 "mod_date = ?, ".
								 "is_delete = ? ".
	                    		 "WHERE item_type_id = ? AND ".
								 "attribute_id = ?; ";
						$params = null;
						$params[] = $this->metadata_type[$nCnt];		// input_type
						$params[] = $this->metadata_required[$nCnt];	// is_required
						$params[] = $this->metadata_disp[$nCnt];		//　list_view_enable
						$params[] = $this->metadata_title[$nCnt];		// attribute_name
						$params[] = $this->metadata_title[$nCnt];		// attribute_short_name
						$params[] = $count;								// show_order
						$params[] = $this->metadata_newline[$nCnt];		// line_feed_enable
						$params[] = $this->metadata_plural[$nCnt];		// plural_enable
						$params[] = $this->metadata_hidden[$nCnt];		// hidden
						$params[] = $user_id;							// mod_user_id
						$params[] = $this->TransStartDate;				// mod_date
						$params[] = 0;									// is_delete
						$params[] = $item_type_id;						// item_type_id
						$params[] = $attribute_id[$nCnt];				// attribute_id

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
						//////////////////// 選択肢の更新 /////////////////////
						// 現在DBにある選択肢情報をすべて論理削除
                        // Fix item attr candidate delete action 2013/05/10 Y.Nakao --start--
                        $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                " SET del_user_id = ?, ".
                                " del_date = ?, ".
                                " mod_user_id = ?, ".
                                " mod_date = ?, ".
                                " is_delete = ? ".
                                " WHERE item_type_id = ? ".
                                " AND attribute_id = ? ";
                        $params = array();
                        $params[] = $user_id;
                        $params[] = $this->TransStartDate;
                        $params[] = $user_id;
                        $params[] = $this->TransStartDate;
                        $params[] = 1;
                        $params[] = $item_type_id;
                        $params[] = $attribute_id[$nCnt];
                        $result = $this->Db->execute($query, $params);
                        if($result === false)
                        {
                            $this->failTrans();
                            throw $exception;
                        }
                        
                        // 更新された選択肢を新規登録
                        if($this->metadata_candidate[$nCnt] != "")
                        {
                            $candidata = explode("|", $this->metadata_candidate[$nCnt]);
                            for($ii=0; $ii<count($candidata); $ii++)
                            {
                                $query = " SELECT * ".
                                        " FROM ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                        " WHERE item_type_id = ? ".
                                        " AND attribute_id = ? ".
                                        " AND candidate_no = ? ";
                                $params = array();
                                $params[] = $item_type_id;
                                $params[] = $attribute_id[$nCnt];
                                $params[] = $ii+1;
                                $result = $this->Db->execute($query, $params);
                                if($result === false)
                                {
                                    $this->failTrans();
                                    throw $exception;
                                }
                                $query = "";
                                $params = array();
                                if(count($result) == 1)
                                {
                                    // update
                                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                            " SET candidate_value = ?, ".
                                            " candidate_short_value = ? ,".
                                            " del_user_id = ?, ".
                                            " del_date = ?, ".
                                            " mod_user_id = ?, ".
                                            " mod_date = ?, ".
                                            " is_delete = ? ".
                                            " WHERE item_type_id = ? ".
                                            " AND attribute_id = ? ".
                                            " AND candidate_no = ? ";
                                    $params = array();
                                    $params[] = $candidata[$ii];
                                    $params[] = $candidata[$ii];
                                    $params[] = "";
                                    $params[] = "";
                                    $params[] = $user_id;
                                    $params[] = $this->TransStartDate;
                                    $params[] = 0;
                                    $params[] = $item_type_id;
                                    $params[] = $attribute_id[$nCnt];
                                    $params[] = $ii+1;
                                }
                                else
                                {
                                    // insert
                                    $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_candidate ".
                                         "(item_type_id, attribute_id, candidate_no, ".
                                         " candidate_value, candidate_short_value, ".
                                         " ins_user_id, mod_user_id, del_user_id, ".
                                         " ins_date, mod_date, del_date, is_delete) ".
                                         "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
                                    $params = array();
                                    $params[] = $item_type_id;          // item_type_id
                                    $params[] = $attribute_id[$nCnt];   // attribute_id
                                    $params[] = $ii+1;                  // candidate_no
                                    $params[] = $candidata[$ii];        // candidate_value
                                    $params[] = $candidata[$ii];        // candidate_short_value
                                    $params[] = $user_id;               // ins_user_id
                                    $params[] = $user_id;               // mod_user_id
                                    $params[] = "";                     // del_user_id
                                    $params[] = $this->TransStartDate;  // ins_date
                                    $params[] = $this->TransStartDate;  // mod_date
                                    $params[] = "";                     // del_date
                                    $params[] = 0;                      // is_delete
                                }
                                // update or insert 
                                $result = $this->Db->execute($query, $params);
                                if($result === false)
                                {
                                    $this->failTrans();
                                    throw $exception;
                                }
                            }
                        }
                        array_push($attrMultiID, $attribute_id[$nCnt]);
                        // Fix item attr candidate delete action 2013/05/10 Y.Nakao --end--
					} else if($attribute_id[$nCnt] == -1){
						////////////////////// 追加登録 /////////////////////
						// アイテムタイプ属性IDを決める
				    	$attr_id = 1;
				    	while(1) {
				    		$query = "SELECT * ".
                     		 		 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
                     				 "WHERE item_type_id = ? AND ".
			    					 "attribute_id = ?; ";
				    		$params = null;
				            $params[] = $item_type_id;
				            $params[] = $attr_id;
		            		//SELECT実行
		            		$result = $this->Db->execute($query, $params);
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
		            		if(!(isset($result[0]))){
		            			break;
		            		}
				    		$attr_id++;
				    	}
				    	//
			   			// 必須、一覧表示チェックをDB登録用のデータに修正
			   			// ""-> 0 , "on"->1に 
			   			//
			   			for($nn=0;$nn<count($this->metadata_required);$nn++){
			   				if($this->metadata_required[$nn] == ""){
			   					$this->metadata_required[$nn] = 0;
			   				}
			   				else{
			   					$this->metadata_required[$nn] = 1;
			   				}
			   			}
			   			for($nn=0;$nn<count($this->metadata_disp);$nn++){
			   				if($this->metadata_disp[$nn] == ""){
			   					$this->metadata_disp[$nn] = 0;
			   				}
			   				else{
			   					$this->metadata_disp[$nn] = 1;
			   				}
			   			}
			   			
			   			// 追加登録データ
			   			$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_type ". 
			    			 "(item_type_id, attribute_id, show_order, ".
			    			 " attribute_name, attribute_short_name, ". 
			    			 " input_type, is_required, ".
			    			 " plural_enable, line_feed_enable, list_view_enable, hidden, ".
			   				 " junii2_mapping, dublin_core_mapping, lom_mapping,".
			   				 " ins_user_id, mod_user_id, del_user_id, ".
			   				 " ins_date, mod_date, del_date, is_delete) ".
	                		 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
			   			$params = null;
			            $params[] = $item_type_id;					// item_type_id
			            $params[] = $attr_id;						// attribute_id
			            $params[] = $nCnt+1;						// show_order
			            $params[] = $this->metadata_title[$nCnt];	// attribute_name
			            $params[] = $this->metadata_title[$nCnt];	// attribute_short_name
			            $params[] = $this->metadata_type[$nCnt];	// input_type
			            $params[] = $this->metadata_required[$nCnt];// is_required
			            $params[] = $this->metadata_plural[$nCnt];	// plural_enable
			            $params[] = 0;								// line_feed_enable
			            $params[] = $this->metadata_disp[$nCnt];	// list_view_enable
			            $params[] = $this->metadata_hidden[$nCnt];	// hidden
			            $params[] = "";								// junii2_mapping
			            $params[] = "";								// dublin_core_mapping
			            $params[] = "";                             // lom_mapping
			            $params[] = $user_id;						// ins_user_id
			            $params[] = $user_id;						// mod_user_id
			            // user_idのString対応 2008/06/03 Y.Nakao --Start--
			            $params[] = "";					// del_user_id
			            // user_idのString対応 2008/06.03 Y.Nakao --End--
			            $params[] = $this->TransStartDate;			// ins_date
			            $params[] = $this->TransStartDate;			// mod_date
			            $params[] = "";								// del_date
			            $params[] = 0;								// is_delete
			            //INSERT実行
			            $result = $this->Db->execute($query, $params);
						
						//　メタデータをアイテムタイプ属性テーブルに追加
					   	if ($result === false) {
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
				    	
				    	// 選択候補のある属性の場合、アイテム属性入力候補テーブルに追加
						if($this->metadata_candidate[$nCnt] != " "){
							// データ有
							$candidata = explode("|", $this->metadata_candidate[$nCnt]);
							if($candidata === false){
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
							for($ii=0;$ii<count($candidata);$ii++){
								if($candidata[$ii] != ""){
									$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_candidate ".
										 "(item_type_id, attribute_id, candidate_no, candidate_value, ".
										 " candidate_short_value, ins_user_id, mod_user_id, del_user_id, ".
										 " ins_date, mod_date, del_date, is_delete) ".
		                     			 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
									$params = null;
						            $params[] = $item_type_id;	// item_type_id
						            $params[] = $attr_id;		// attribute_id
						            $params[] = $ii+1;			// candidate_no
						            $params[] = $candidata[$ii];// candidate_value
						            $params[] = $candidata[$ii];// candidate_short_value
						            $params[] = $user_id;		// ins_user_id
						            $params[] = $user_id;		// mod_user_id
						            // user_idのString対応 2008/06/03 Y.Nakao --Start--
						            $params[] = "";					// del_user_id
						            // user_idのString対応 2008/06.03 Y.Nakao --End--
						            $params[] = $this->TransStartDate;	// ins_date
						            $params[] = $this->TransStartDate;	// mod_date
						            $params[] = "";				// del_date
						            $params[] = 0;				// is_delete
						            //INSERT実行
						            $result = $this->Db->execute($query, $params);
								   	if ($result === false) {
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
								}
							}
						}
                        array_push($attrMultiID, $attr_id);
					}
				}
				$this->addMultiLanguageTitle($item_type_id, $attrMultiID);
				//////////////////// データ削除 ///////////////////
				$searchTableUpdateFlag = false;
				$del_attribute_id = $this->Session->getParameter("del_attribute_id");
				for($nCnt=0;$nCnt<count($del_attribute_id);$nCnt++){
					$searchTableUpdateFlag = true;
					$query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_type ".
	                    	 "SET del_user_id = ?, ".
							 "del_date = ?, ".
							 "mod_user_id = ?, ".
							 "mod_date = ?, ".
							 "is_delete = ? ".
	                    	 "WHERE item_type_id = ? AND ".
							 "attribute_id = ?; ";
					$params = null;
					$params[] = $user_id;				// del_user_id
					$params[] = $this->TransStartDate;	// del_date
					$params[] = $user_id;				// mod_user_id  
					$params[] = $this->TransStartDate;	// mod_date 
					$params[] = 1;						// is_delete
					$params[] = $item_type_id;			// item_type_id
					$params[] = $del_attribute_id[$nCnt];	// attribute_id
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

					// 選択肢があった場合、削除
                    // Fix item attr candidate delete action 2013/05/10 Y.Nakao --start--
                    $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_candidate ".
                             "SET del_user_id = ?, ".
                             "del_date = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ?, ".
                             "is_delete = ? ".
                             "WHERE item_type_id = ? AND ".
                             "attribute_id = ? ";
                    $params = null;
                    $params[] = $user_id;               // del_user_id
                    $params[] = $this->TransStartDate;  // del_date
                    $params[] = $user_id;               // mod_user_id  
                    $params[] = $this->TransStartDate;  // mod_date 
                    $params[] = 1;                      // is_delete
                    $params[] = $item_type_id;
                    $params[] = $del_attribute_id[$nCnt];
                    //UPDATE実行
                    $result = $this->Db->execute($query,$params);
                    if($result === false){
                        //必要であればSQLエラー番号・メッセージ取得
                        $errNo = $this->Db->ErrorNo();
                        $errMsg = $this->Db->ErrorMsg();
                        $this->Session->setParameter("error_code", $errMsg);
                        //エラー処理を行う
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                        throw $exception;
                    }
                    // Fix item attr candidate delete action 2013/05/10 Y.Nakao --end--
				}
                // Add detail search 2013/11/25 K.Matsuo --start--
                if($searchTableUpdateFlag){
                    $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
                    $searchTableProcessing->updateSearchTableForItemtype($item_type_id);
                }
                // Add detail search 2013/11/25 K.Matsuo --end--
				// エラーコード解除
				$this->Session->removeParameter("error_code");
				
				//アクション終了処理
				$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
				if ( $result === false ) {
					$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
					//$DetailMsg = null;                              //詳細メッセージ文字列作成
					//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
					//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
					throw $exception;
				}
		    	$this->Session->removeParameter("error_code");	// エラーメッセージ削除
		    	$this->Session->removeParameter("error_msg");	// エラーメッセージ削除
				// セッション情報初期化 for アイテムタイプ登録
		    	$this->Session->removeParameter("item_type_id");	// アイテムタイプID
		    	$this->Session->removeParameter("itemtype_name");	// アイテムタイプ名 削除
		    	$this->Session->removeParameter("metadata_title");	// メタデータ項目削除
		    	$this->Session->removeParameter("metadata_type");	// メタデータタイプ削除
		    	$this->Session->removeParameter("metadata_required");	// メタデータ必須フラグ削除
		    	$this->Session->removeParameter("metadata_disp");	// メタデータ一覧表示フラグ削除
		    	$this->Session->removeParameter("metadata_hidden");	// メタデータ非表示フラグ削除
		    	$this->Session->removeParameter("metadata_num");	// アイテムメタデータ数を1に
		    	
		    	$this->Session->setParameter("redirect_flg", "itemtype");	// Add update OK message 2009/01/23 A.Suzuki
				return 'redirect';
//		    	return 'success_edit';
	    	}
	    	
	    	// 2008/03/03
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
    
    // Add multi language K.Matsuo 2013/07/24 --start--
    /**
     * Add Multi language to DB
     *
     * @param int $itemTypeId itemtype id
     * @param array $attrMultiID attribute_id arrayid List
     */
    private function addMultiLanguageTitle($itemTypeId, $attrMultiID)
    {
        $user_id = $this->Session->getParameter("_user_id");
        // 編集しているアイテムタイプの多言語データを論理削除
        $query = "UPDATE ". DATABASE_PREFIX ."repository_item_type_name_multilanguage ".
                 "SET is_delete  = ?, ".
                 "mod_user_id = ?, ".
                 "del_user_id = ?, ".
                 "mod_date = ?, ".
                 "del_date = ? ".
                 "WHERE item_type_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = 1;
        $params[] = $user_id; // mod_user_id
        $params[] = $user_id;
        $params[] = $this->TransStartDate; // mod_date
        $params[] = $this->TransStartDate;
        $params[] = $itemTypeId;
        $params[] = 0;
        $result = $this->Db->execute($query,$params);
        
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_code", $errMsg);
            //エラー処理を行う
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
            $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
        $lang_list = $this->Session->getParameter("lang_list");
        $array_metadata_multi_title = $this->Session->getParameter("metadata_multi_title");
        // 設定した多言語を挿入または更新(既存の場合更新になる)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type_name_multilanguage ".
                 "VALUES ";
        $params = array();
        $cont = 0;
        for($ii = 0; $ii < count($array_metadata_multi_title); $ii++){
            foreach($array_metadata_multi_title[$ii] as $key => $value){
                if($value == ""){
                    continue;
                }
                if($cont != 0){
                    $query .= ", ";
                }
                $query .= " (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
                $params[] = $itemTypeId;
                $params[] = $attrMultiID[$ii];
                $params[] = $key;
                $params[] = $value;
                $params[] = $user_id;
                $params[] = $user_id;
                $params[] = "";
                $params[] = $this->TransStartDate;
                $params[] = $this->TransStartDate;
                $params[] = "";
                $params[] = 0;
                $cont++;
            }
        }
        $query .= " ON DUPLICATE KEY UPDATE item_type_name=VALUES(`item_type_name`), ".
                  " ins_user_id=ins_user_id , ins_date =ins_date , ".
                  " mod_user_id=VALUES(`mod_user_id`), mod_date=VALUES(`mod_date`), ".
                  " del_user_id=VALUES(`del_user_id`), del_date=VALUES(`del_date`), ".
                  " is_delete=VALUES(`is_delete`);";
        if($cont != 0){
            $result = $this->Db->execute($query,$params);
        }
        
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_code", $errMsg);
            //エラー処理を行う
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
            $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
    }
    // Add multi language K.Matsuo 2013/07/24 --end--
}
?>
