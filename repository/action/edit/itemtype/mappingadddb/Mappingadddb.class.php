<?php
// --------------------------------------------------------------------
//
// $Id: Mappingadddb.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
 * repositoryモジュール アイテムタイプ作成マッピングDB登録アクション
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Mappingadddb extends RepositoryAction
{
	// リクエストパラメタ
		
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
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
		    }
	    	// 初期設定
//	    	$tr_start_date = timezone_date(null, false, "Ymd");		// トランザクション開始日時 = 更新日時
//	    	$tr_start_date = date("Ymd");							// トランザクション開始日時 = 更新日時
	    	$tr_start_date = $this->TransStartDate;					// トランザクション開始日時 = 更新日時
	    	$bef_mod_date = $this->Session->getParameter("item_type_update");		// 既存アイテムタイプレコードの更新日
	    	$user_id = $this->Session->getParameter("_user_id");	// ユーザID
 
	    	//*******************************************************
	    	// アイテムタイプのマッピング設定を更新
	    	// ※ただし、変化がない場合はレコードを更新しない
	    	// 更新の可不可を検査
	    	// 編集開始時の更新時間と現在のレコードの更新時間が等しければ、更新可能
	    	//*******************************************************
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
			$params[] = $this->Session->getParameter("itemtype_id");	// item_type_id
			$params[] = 0;
	        $params[] = $bef_mod_date;
	        $ret = $this->Db->execute($query, $params);
	        //SQLエラーの場合
	        if($ret === false) {
	        	//必要であればSQLエラー番号・メッセージ取得
	            $errNo = $this->Db->ErrorNo();
	            $errMsg = $this->Db->ErrorMsg();
	            $this->Session->setParameter("error_code", $errMsg);
	            //エラー処理を行う
	            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
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
	            $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
	      		$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
			    //異常終了 この場合アイテムタイプ選択に戻る
	        	return "error";
	        }	    	
	    	
	    	// 検査用リファレンスレコードを取得
	 		$itemtypeRef = $this->Db->selectExecute(
	 					"repository_item_type",
	 					array( "item_type_id" => $this->Session->getParameter("itemtype_id"),)
	 					);
	        if($itemtypeRef === false) {
	    		return 'error';
	    	}
	    	// セッションからアイテムタイプ情報を取得
	    	$itemtype = $this->Session->getParameter("itemtype");
	    	// アイテムタイプのマッピング情報(type)を更新
    		// アイテムタイプ更新情報を作成
	    	$params = array(
				"mapping_info" => $itemtype['mapping_info'],	// マッピング設定
	    		"mod_user_id" => $user_id,						// 更新ユーザID
	    		"mod_date" => $tr_start_date					// 更新日
			);
            // Add detail search 2013/11/25 K.Matsuo --start--
			$searchTableUpdateFlag = false;
			if($itemtypeRef[0]['mapping_info'] != $itemtype['mapping_info']){
				$searchTableUpdateFlag = true;
			}
            // Add detail search 2013/11/25 K.Matsuo --end--
			// アイテムタイプ更新
	    	$result = $this->Db->updateExecute(
				    		"repository_item_type",
				    		$params,
				    		array("item_type_id" => $itemtype['item_type_id'])
		    			);
	    	if ($result === false) {
	    		$this->failTrans(); 
		   		return 'error';		// 登録失敗。errorページをmaple.iniに書いておくこと。
	    	}
	    	
	    	//*******************************************************
	    	// 全てのメタデータのマッピング設定を更新
	    	// ※ただし、DublinCore/JuNii2/DisplayLanguageTypeに変化がない場合はレコードを更新しない
	    	//*******************************************************
	
	    	// 検査用リファレンスレコードを取得
	    	$params = array( "item_type_id" => $this->Session->getParameter("itemtype_id"),
		    					 "is_delete" => 0 );
	 		$itemtypeAttrRef = $this->Db->selectExecute("repository_item_attr_type", $params);
	        if($itemtypeAttrRef === false) {
	    		return 'error';
	    	}
	    	// セッション情報の取得, メタデータ部分だけパラメタに詰める
	    	$metadata_table = $this->Session->getParameter("metadata_table");
	    	// 書誌情報追加 2008/08/22 Y.Nakao --start--

	    	$update_count = 0; // Add update OK message 2009/01/23 A.Suzuki
	    	
	    	// メタデータループ
	     	for ($ii=0; $ii<count($metadata_table); $ii++ ) {
	     		// 更新精査用
		    	$update_flg = false;
		    	// マッピング情報
		    	$bib_dcm = "";
		    	$bib_jn2 = "";
		    	$bib_lom = "";       // LOM対応 2013/01/28
		    	$bib_lang = "";
		    	// add LIDO 2014/04/15 R.Matsuura --start--
		    	$lido_mapping_info = "";
		    	// add LIDO 2014/04/15 R.Matsuura --end--
		    	//add SPASE Takahiro.M
		    	$spase_mapping_info = "";
	     		// 書誌情報の場合、マッピング情報をつなげる
		    	if($metadata_table[$ii]['input_type']=="biblio_info"){
		    		$bib_dcm = sprintf($metadata_table[$ii]['dublin_core_mapping']);
		    		$bib_jn2 .= "";
		    		// fix biblio mapping 2010/01/28 A.Suzuki --start--
			    	// "jtitle"は1つだけにする($metadata_table[$ii]['junii2_mapping'][0]は無視)
		    		for($jj=1;$jj<count($metadata_table[$ii]['junii2_mapping']);$jj++){
	    				if($jj != 1){
		    				$bib_jn2 .= ",";
		    			}
				        $bib_jn2 .= sprintf($metadata_table[$ii]['junii2_mapping'][$jj]);
		    		}
		    		// fix biblio mapping 2010/01/28 A.Suzuki --end--
		    		
                    //Add LOM Column 2013/01/28 A.Jin --start--
                    $bib_lom = sprintf($metadata_table[$ii]['lom_mapping']);
                    //Add LOM Column 2013/01/28 A.Jin --end--
                    
                    // add LIDO 2014/04/15 R.Matsuura --start--
                    $lido_mapping_info = sprintf($metadata_table[$ii]['lido_mapping']);
                    // add LIDO 2014/04/15 R.Matsuura --end--
                    	//add SPASE Takahiro.M
                    $spase_mapping_info = sprintf($metadata_table[$ii]['spase_mapping']);
                    
		    		// 選択言語は指定なしで固定
		    		$bib_lang .= "";
	    			// 更新の有無を検査
			        if(($metadata_table[$ii]['dublin_core_mapping'] != $itemtypeAttrRef[$ii]['dublin_core_mapping'] ||
			            $metadata_table[$ii]['lom_mapping'] != $itemtypeAttrRef[$ii]['lom_mapping'] ||
		    	    	$bib_jn2 != $itemtypeAttrRef[$ii]['junii2_mapping'] ||
	    	            $lido_mapping_info != $itemtypeAttrRef[$ii]['lido_mapping'] || 
			        	$spase_mapping_info != $itemtypeAttrRef[$ii]['spase_mapping'] ||
		    	    	$bib_lang != $itemtypeAttrRef[$ii]['display_lang_type'])&&
	    				($tr_start_date > $itemtypeAttrRef[$ii]['mod_date']))
	    			{   
	    				$update_flg = true;
	    			}

		    	// 書誌情報ではない場合
		    	} else {
		    		// そのまま登録
		    		$bib_dcm = $metadata_table[$ii]['dublin_core_mapping'];
			        $bib_jn2 = $metadata_table[$ii]['junii2_mapping'];
			        $bib_lom = $metadata_table[$ii]['lom_mapping'];
			        $lido_mapping_info = $metadata_table[$ii]['lido_mapping'];
			        $spase_mapping_info = $metadata_table[$ii]['spase_mapping'];
			        $bib_lang = $metadata_table[$ii]['display_lang_type'];
			        // 更新の有無を検査
			        if(($bib_dcm != $itemtypeAttrRef[$ii]['dublin_core_mapping'] ||
		    	    	$bib_jn2 != $itemtypeAttrRef[$ii]['junii2_mapping'] || 
		    	    	$bib_lom != $itemtypeAttrRef[$ii]['lom_mapping'] || 
			            $lido_mapping_info != $itemtypeAttrRef[$ii]['lido_mapping'] || 
			        	$spase_mapping_info != $itemtypeAttrRef[$ii]['spase_mapping'] ||
		    	    	$bib_lang != $itemtypeAttrRef[$ii]['display_lang_type'])&&
	    				($tr_start_date > $itemtypeAttrRef[$ii]['mod_date']))
	    			{   
	    				$update_flg = true;
	    			}
		    	}
		    	// 更新があった場合カラムをアップデート
		    	if($update_flg){
		    		$searchTableUpdateFlag = true;
	    			// アイテム属性タイプ更新用クエリー
					$query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_type ".
			            	     "SET dublin_core_mapping = ?, ".
			    				 "junii2_mapping = ?, ".
                                 "lom_mapping = ?, ".
                                 "lido_mapping = ?, ".
                                 "spase_mapping = ?, ".
								 "display_lang_type = ?, ".
			    				 "mod_user_id = ?, ".
								 "mod_date = ? ".
			                 	 "WHERE item_type_id = ? AND ".
			    				 "attribute_id = ? AND ".
			       				 "is_delete = ?; ";
					$params = null;
			    	// $queryの?を置き換える配列
		    		$params[] = $bib_dcm;
		    		$params[] = $bib_jn2;
		    		$params[] = $bib_lom;
		    		$params[] = $lido_mapping_info;
		    		$params[] = $spase_mapping_info;
		    		$params[] = $bib_lang;
			        $params[] = $user_id;
			        $params[] = $tr_start_date;
			    	$params[] = $metadata_table[$ii]['item_type_id'];
			        $params[] = $metadata_table[$ii]['attribute_id'];
			        $params[] = 0;
			        //UPDATE実行
			        $result = $this->Db->execute($query,$params);
		    		if($result === false){
			        	//必要であればSQLエラー番号・メッセージ取得
			            $errNo = $this->Db->ErrorNo();
			            $Error_Msg = $this->Db->ErrorMsg();
			            $this->failTrans(); 
						echo $Error_Msg;
			            //トランザクション失敗を設定(ROLLBACK)
			            return 'error';
		    		}
		    		$update_count++;	// Add update OK message 2009/01/23 A.Suzuki
				}
	    	}
	    	// 書誌情報追加 2008/08/22 Y.Nakao --end--
            // Add detail search 2013/11/25 K.Matsuo --start--
            if($searchTableUpdateFlag){
                $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
                $searchTableProcessing->updateSearchTableForItemtype($this->Session->getParameter("itemtype_id"));
            }
            // Add detail search 2013/11/25 K.Matsuo --end--
	    	// セッション情報初期化 for アイテムタイプ設定
	    	$this->Session->removeParameter("itemtype_id");		// アイテムタイプID
	    	$this->Session->removeParameter("itemtype");		// アイテムタイプ
	    	$this->Session->removeParameter("metadata_table");	// アイテムタイプ属性テーブル
	    	$this->Session->removeParameter("item_type_update");
	    	$this->Session->removeParameter("error_code");
        	// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				//print "終了処理失敗";
			}
			
			// Add update OK message 2009/01/23 A.Suzuki --start--
	        if($update_count>0){
	        	$this->Session->setParameter("redirect_flg", "itemtype");
	        	return 'redirect';
	        }
	        // Add update OK message 2009/01/23 A.Suzuki --end--
			
	        return 'success';
	    }
	    catch ( RepositoryException $Exception) {
	        //アクション終了処理
	      	$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
	        
	        //異常終了
	        return "error";
	    }
	}
}
?>
