<?php
// --------------------------------------------------------------------
//
// $Id: Edit.class.php 48455 2015-02-16 10:53:40Z atsushi_suzuki $
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
class Repository_Action_Edit_Itemtype_Edit extends RepositoryAction
{
	// リクエストパラメータを受け取るため
	var $item_type_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
	var $item_type_id = null;		//前画面で選択したアイテムタイプID(編集時)
	
	
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

	    	//
	    	// セッション情報保存 for アイテムタイプ登録
	    	
	    	// 新規登録時
	 		if($this->item_type_name != NULL) {
	 			// セッション情報削除
	 			$this->Session->removeParameter("metadata_num");
	 			$this->Session->removeParameter("item_type_name");
	 			$this->Session->removeParameter("metadata_title");
		   		$this->Session->removeParameter("metadata_type");
		    	$this->Session->removeParameter("metadata_required");
		   		$this->Session->removeParameter("metadata_disp");
		   		$this->Session->removeParameter("metadata_candidate");
		   		$this->Session->removeParameter("metadata_plural"); // 2008/03/03
		   		$this->Session->removeParameter("metadata_newline"); // 2008/03/03
		   		$this->Session->removeParameter("metadata_hidden"); // 2009/01/28
		   		$this->Session->removeParameter("metadata_multi_title"); // 2013/07/22 K.Matsuo
		   		
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // remove upload icon
                $this->Session->removeParameter("upload_icon");
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
		   		
		   		$query = "SELECT * ".
                	     "FROM ". DATABASE_PREFIX ."repository_item_type ".
                    	 "WHERE item_type_name = ? AND ".
                  		 "is_delete = 0;" ;
		   		$params = null;
	            $params[] = $this->item_type_name;		//column1 = ? 部を置き換える
        	    //SELECT実行
            	$result = $this->Db->execute($query, $params);
		   		
		        if($result === false) {
	                //必要であればSQLエラー番号・メッセージ取得
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
		    	}
		    	// 重複あり=>エラーメッセージ付で前ページに戻るか。
		    	if(count($result) > 0) {
	                //必要であればSQLエラー番号・メッセージ取得
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->Session->setParameter("error_code", 1); // 重複エラー 2008/02/28
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
		    	}
		    	$name = array();
		    	$name = preg_split("/[\s,]+|　/", $this->item_type_name);
		    	$nullCnt = 0;
		    	for($nCnt=0;$nCnt<count($name);$nCnt++){
		    		if($name[$nCnt] == ""){
		    			$nullCnt++;
		    		}
		    	}
		    	if(count($name)==$nullCnt){
		    		$this->Session->setParameter("error_code", 9);
		    		 //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
		    	}
		    	
                $lang_list = $this->Session->getParameter("lang_list");
                $array_metadata_multi_title = array();
                foreach($lang_list as $key => $lang){
                    $array_metadata_multi_title[0][$key] = "";
                } 
	 			// 新規作成時, アイテムタイプ名をセッションに保存
	 			$this->Session->setParameter("item_type_edit_flag", 0);					// 0:new create
	 			$this->Session->setParameter("item_type_name", $this->item_type_name);	// Save item type name
	 			$this->Session->setParameter("metadata_multi_title", $array_metadata_multi_title);	// Save item type name
	 			// 新規作成時のメタデータはからのデータを一つ持つ
	 			// Sub metadata num Y.Nakao 2008/09/08 --start--
	 			$this->Session->setParameter("metadata_num", 1);
	 			// Sub metadata num Y.Nakao 2008/09/08 --end--
			// 既存編集時
	 		} elseif($this->item_type_id != NULL) {
	 			// アイテムタイプ名をセッションに保存
	 			$this->Session->setParameter("item_type_edit_flag", 1);					// 1:Edit
	 			$this->Session->setParameter("item_type_id", $this->item_type_id);		// Save item type ID
				
                $lang_list = $this->Session->getParameter("lang_list");
	 			// セッション情報初期化 メタデータ 2008/03/04
		    	$this->Session->removeParameter("metadata_num");
		    	$this->Session->removeParameter("metadata_title");
		   		$this->Session->removeParameter("metadata_type");
		    	$this->Session->removeParameter("metadata_required");
		   		$this->Session->removeParameter("metadata_disp");
		   		$this->Session->removeParameter("metadata_candidate");
		   		$this->Session->removeParameter("attribute_id");
		   		$this->Session->removeParameter("metadata_plural"); // 2008/03/03
		   		$this->Session->removeParameter("metadata_newline");	// 改行指定追加 2008/03/13
		   		$this->Session->removeParameter("metadata_hidden");	// 2009/01/28 非表示指定追加
		   		$this->Session->removeParameter("metadata_multi_title"); // 2013/07/22 K.Matsuo
		   		
		   		$this->Session->removeParameter("upload_icon"); // アイテムタイプアイコン追加 Y.Nakao 2008/07/24
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
                // remove show default icon flg
                $this->Session->removeParameter("icon_edit");
                // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
		   		
		   		// メタデータ編集反映不具合対応 2008/06/17 Y.Nakao --start--
   				$this->Session->removeParameter("del_attribute_id");
   				$this->Session->setParameter("del_attribute_id", null);
   				//$this->Session->removeParameter("attribute_id");
		   		// メタデータ編集反映不具合対応 2008/06/17 Y.Nakao --end--
	   		
	 			// アイテムタイプを検索してセッション情報に保存
	 			$query = "SELECT * ".
                	     "FROM ". DATABASE_PREFIX ."repository_item_type ".
                    	 "WHERE item_type_id = ? AND ".
                  		 "is_delete = 0;" ;
		   		$params = null;
	            $params[] = $this->item_type_id;
        	    //SELECT実行
            	$result = $this->Db->execute($query, $params);
            	
		        if($result === false) {
	                //必要であればSQLエラー番号・メッセージ取得
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                //$this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                //throw $exception;
	                $this->Session->setParameter("error_code", 2); // DBに登録なし 2008/02/28
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
		    	}
		    	$this->Session->setParameter("item_type_name", $result[0]['item_type_name']);

		    	// 更新日時格納
		    	$this->Session->setParameter("item_type_update_date",$result[0]['mod_date']);

	    		// アイテム属性タイプを検索してセッション情報に保存
	    		$query = "SELECT * ".
	                     "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
	                     "WHERE item_type_id = ? AND ".
	                     "is_delete = ? ".
	    				 "order by show_order; ";
	    		$params = null;
	            $params[] = $this->item_type_id;
	            $params[] = 0;
	            //SELECT実行
	            $result = $this->Db->execute($query, $params);
	            
		        if($result === false) {
	                //必要であればSQLエラー番号・メッセージ取得
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                //エラー処理を行う
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                throw $exception;
		    	}
		
                $array_metadata_multi_title = array();
                $attrMultiID = array();
		    	if(count($result) > 0) {
		    		$array_attr_name = array();
		    		$array_input_type = array();
		    		$array_attr_candidate = array();
		    		$array_required = array();
		    		$array_list_enable = array();
		    		$array_attr_id = array();	// Sessionに属性IDを保持するための配列 2008/03/03
		    		$array_plural = array();	// 複数可否を保持する配列 2008/03/04
		    		$array_newline = array();	// 改行指定を保持する配列 2008/03/13
		    		$array_hidden = array();	// 非表示設定を保持する配列 2009/01/28
		    		
                    $multiLang = array();
                    foreach($lang_list as $key => $lang){
                        $multiLang[$key] = "";
                    }
			    	for($ii=0; $ii<count($result); $ii++) {
			    		array_push($array_attr_id, $result[$ii]['attribute_id']); // 2008/03/03
			    		array_push($array_attr_name, $result[$ii]['attribute_name']);
			    		array_push($array_input_type, $result[$ii]['input_type']);
			    		//array_push($array_required, $result[$ii]['is_required']);
			    		//array_push($array_list_enable, $result[$ii]['list_view_enable']);
			    		if($result[$ii]['is_required'] == 0)
			    		{
			    			array_push($array_required, 0);
			    		} else {
			    			array_push($array_required, 1);
			    		}
			    		if($result[$ii]['list_view_enable'] == 0)
			    		{
			    			array_push($array_list_enable, 0);
			    		} else {
			    			array_push($array_list_enable, 1);
			    		}
			    		// 複数可否追加 2008/03/04
			    		if($result[$ii]['plural_enable'] == 0){
			    			array_push($array_plural, 0);
			    		} else {
			    			array_push($array_plural, 1);
			    		}
			    		// 改行指定追加 2008/03/13
			    		if($result[$ii]['line_feed_enable'] == 0)
			    		{
			    			array_push($array_newline, 0);
			    		} else {
			    			array_push($array_newline, 1);
			    		}
			    		// 非表示追加 2009/01/28
			    		if($result[$ii]['hidden'] == 0)
			    		{
			    			array_push($array_hidden, 0);
			    		} else {
			    			array_push($array_hidden, 1);
			    		}
			    		// ↓ 2008/02/28
			    		// アイテムタイプ属性から選択肢の情報を取得しに行く
			    		if($result[$ii]['input_type'] == "checkbox" || 
			    		   $result[$ii]['input_type'] == "radio" || $result[$ii]['input_type'] == "select"){
			    			// DBからデータを取得する
			    			$query = "SELECT * ".
	                     		 	"FROM ". DATABASE_PREFIX ."repository_item_attr_candidate ".
	                     			"WHERE item_type_id = ? AND ".
	                     			"attribute_id = ?  AND ".
	                     			"is_delete = ? ".
			    					"order by candidate_no; ";
			    			$params = null;
			    			$params[] = $this->item_type_id;
			    			$params[] = $result[$ii]['attribute_id'];
			    			$params[] = 0;
				            //SELECT実行
	        			    $res_candidata = $this->Db->execute($query, $params);
			    			if($res_candidata === false){
				                //必要であればSQLエラー番号・メッセージ取得
				                $errNo = $this->Db->ErrorNo();
				                $errMsg = $this->Db->ErrorMsg();
				                //エラー処理を行う
				                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
				                //$DetailMsg = null;                              //詳細メッセージ文字列作成
				                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				                $this->failTrans();                                    //トランザクション失敗を設定(ROLLBACK)
				                $exception = null;
				                throw $exception;
			    			}
			    			$str_candidata = "";
			    			for($nCnt=0;$nCnt<count($res_candidata);$nCnt++){
			    				if($nCnt != 0){
			    					$str_candidata .= "|";
			    				}
			    				$str_candidata .= $res_candidata[$nCnt]['candidate_value'];
			    			}
			    			array_push($array_attr_candidate,$str_candidata);
			    		}
			    		else 
			    		{
			    			array_push($array_attr_candidate, "");
			    		}
                        // Add itemtype multi-lang K.Matsuo 2013/7/23 --start--
                        $attrMultiID[$result[$ii]['attribute_id']] = $ii;
                        array_push($array_metadata_multi_title,$multiLang);
                        // Add itemtype multi-lang K.Matsuo 2013/7/23 --end--
			    		// 2008/02/28
			    	}
			    	$this->Session->setParameter("metadata_title",$array_attr_name);
			   		$this->Session->setParameter("metadata_type", $array_input_type);
			    	$this->Session->setParameter("metadata_required", $array_required);
			   		$this->Session->setParameter("metadata_disp", $array_list_enable);
			   		$this->Session->setParameter("metadata_candidate", $array_attr_candidate);
			   		$this->Session->setParameter("attribute_id", $array_attr_id); // 2008/03/03
			   		$this->Session->setParameter("metadata_plural", $array_plural); // 2008/03/04
			   		$this->Session->setParameter("metadata_newline", $array_newline); // 2008/03/04
			   		$this->Session->setParameter("metadata_hidden", $array_hidden); // 2009/01/28
		    	}
		    	
                // Add itemtype multi-lang K.Matsuo 2013/7/23 --start--
                // アイテム属性タイプを検索してセッション情報に保存
                $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_item_type_name_multilanguage ".
                         "WHERE item_type_id = ? AND ".
                         "is_delete = ?; ";
                $params = null;
                $params[] = $this->item_type_id;
                $params[] = 0;
                //SELECT実行
                $itemTypeLang = $this->Db->execute($query, $params);
                if($itemTypeLang === false) {
                    //必要であればSQLエラー番号・メッセージ取得
                    $errNo = $this->Db->ErrorNo();
                    $errMsg = $this->Db->ErrorMsg();
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
                for($ii = 0; $ii < count($itemTypeLang); $ii++){
                    $lang = $itemTypeLang[$ii]['language'];
                    $id = $attrMultiID[ $itemTypeLang[$ii]['attribute_id'] ];
                    $array_metadata_multi_title[$id][$lang] = $itemTypeLang[$ii]['item_type_name'];
                }
                $this->Session->setParameter("metadata_multi_title", $array_metadata_multi_title);
                // Add itemtype multi-lang K.Matsuo 2013/7/23 --end--
		
	 			// メタデータ数を登録
	 			$this->Session->setParameter("metadata_num", count($result));
	 		} else {
	 			$this->Session->setParameter("error_code", 9);
	 			return 'error_create';
	 		}
	    	// ※ただしリクエストパラメータがnullの場合は設定せず。戻ったときとか。
	   		$this->Session->setParameter("error_code", 0); // エラーなし 2008/02/28

			//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
			if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;                              //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
				//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				throw $exception;
			}
			
	        return 'success';
   		}//catch ( RepositoryException $Exception)
   		catch (RepositoryException $Exception) {
    	    //エラーログ出力
    	    /*
        	$this->logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
	        	$Exception->getCode(),			//ログID
	        	$Exception->getMessage(),		//主メッセージ
	        	$Exception->getDetailMsg() );	//詳細メッセージ
	        */
        	
			//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
        
	        //異常終了
      		// 新規登録時
	 		if($this->item_type_name != NULL) {
	 			return 'error_create';
	 		// 既存編集時
	 		} elseif($this->item_type_id != NULL) {
	 			return 'error_edit';
	 		}
    	    return "error";
		}

	}
}
?>
