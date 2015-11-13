<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
class Repository_Action_Edit_Itemtype_Confirm extends RepositoryAction
{
	// リクエストパラメタ
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータタイプ配列
	var $metadata_required = null;	// メタデータ必須フラグ列
	var $metadata_disp = null;		// メタデータ一覧表示フラグ列
	
	var $metadata_candidate = null;	// メタデータ選択肢配列 2008/02/28
	var $metadata_plural = null;	// メタデータ複数可否配列 2008/03/04 追加
	var $metadata_newline = null;	// メタデータ改行指定配列 2008/03/13
	var $metadata_hidden = null;	// メタデータ非表示設定配列 2009/01/28
	
	var $item_type_name = null;		// Add metadata name edit 2008/09/04 Y.Nakao
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    { 	
    	////////////////////////// データチェック ///////////////////////////
    	$nullError = 0;		// null文字チェック用
    	$array_title = array();	// 項目名一時保管用 
    	$array_candidate = array();	// 選択肢一時保管用
    	// 選択肢(metadata_candidate)のチェック
    	for($nCnt=0;$nCnt<count($this->metadata_type);$nCnt++){
    		if( $this->metadata_type[$nCnt] == "checkbox" ||
    			$this->metadata_type[$nCnt] == "radio" ||
    			$this->metadata_type[$nCnt] == "select"){
    			if($this->metadata_candidate[$nCnt] == " "){
    				$nullError = 5;
	    			array_push($array_candidate, "");
    			} else {
	    			// データ有
					$str = $this->metadata_candidate[$nCnt];
					$candidata = explode("|", $this->metadata_candidate[$nCnt]);
					if($candidata === false){
		                $nullError = 5;
					}
					// 選択肢の空白を除去
					for($ii=count($candidata)-1;$ii>=0;$ii--){
						if($candidata[$ii] == ""){
							array_splice($candidata, $ii,1);
						}
					}
					if($candidata == null){
						$nullError = 5;
	    				array_push($array_candidate, "");
					} else{
						// 選択肢列再作成
						$candi_str = "";
						for($ii=0;$ii<count($candidata);$ii++){
							if($ii != 0){
								$candi_str .= "|";
							}
							$candi_str .= $candidata[$ii];
						}
						array_push($array_candidate, $candi_str);
					}
    			}
    		} else {
    			array_push($array_candidate, "");
    		}
    	}
    	// 項目名(metadata_title)が空のチェック
    	for($nCnt=0;$nCnt<count($this->metadata_title);$nCnt++){
    		if($this->metadata_title[$nCnt] == " "){
    			$nullError = 3;
	    		array_push($array_title, "");
    		} else {
	    		array_push($array_title, $this->metadata_title[$nCnt]);
    		}
    	}
    	// Add metadata name edit 2008/09/04 Y.Nakao --start--
    	if($this->item_type_name!=null && $this->item_type_name!=""){
    		if($this->Session->getParameter("item_type_name") != $this->item_type_name){
    			$query = "SELECT * ".
                	     "FROM ". DATABASE_PREFIX ."repository_item_type ".
                    	 "WHERE item_type_name = ?; ";
		   		$params = null;
	            $params[] = $this->item_type_name;		//column1 = ? 部を置き換える
        	    //SELECT実行
            	$result = $this->Db->execute($query, $params);
		   		
		        if($result === false) {
	                //必要であればSQLエラー番号・メッセージ取得
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                $this->Session->setParameter("error_code", $errMsg);
	                //エラー処理を行う
	                //$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                //throw $exception;
	                return "error";
		    	}
		    	// 重複あり=>エラーメッセージ付で前ページに戻るか。
		    	if(count($result) > 0) {
	                //必要であればSQLエラー番号・メッセージ取得
	                //エラー処理を行う
	                //$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	                //$DetailMsg = null;                              //詳細メッセージ文字列作成
	                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	                $this->Session->setParameter("error_code", 1); // 重複エラー 2008/02/28
	                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	                //throw $exception;
	                return "error";
		    	}
		    	$this->Session->setParameter("item_type_name", $this->item_type_name);
    		}
    	} else {
    		// アイテムタイプ名NULLエラー
    		$this->Session->setParameter("error_code", 6);
    		return 'error';
    	}
    	// Add metadata name edit 2008/09/04 Y.Nakao --end--
    	
        // metadata_titleをまとめて配列でセッションに保存
	   	$this->Session->setParameter("metadata_title", $array_title);
	   	// metadata_typeをまとめて配列でセッションに保存
	   	$this->Session->setParameter("metadata_type", $this->metadata_type);
	   	
	   	// 2008/02/28 選択肢をまとめて配列でセッションに保存 nakao
	   	$this->Session->setParameter("metadata_candidate",$array_candidate);
	   	
		// フラグもまとめてセッションに保存
	   	$array_req = array();
	   	$array_dis = array();
	   	$array_plu = array(); // 2008/03/04 複数可否追加
	   	$array_newline = array();	// 2008/03/13 改行指定追加
	   	$array_hidden = array();	// 2009/01/28 非表示設定追加
        for($ii=0; $ii<count($this->metadata_title); $ii++) {
        	array_push($array_req, 0);
        	array_push($array_dis, 0);
        	array_push($array_plu, 0);
        	array_push($array_newline, 0);
        	array_push($array_hidden, 0);
        	$tmp_str = sprintf("%d", $ii);
        	for($jj=0; $jj<count($this->metadata_required); $jj++) {
        		if( strcmp($tmp_str, $this->metadata_required[$jj]) == 0 ){
        			$array_req[$ii] = 1;
        			break;
        		}
        	}
		    for($jj=0; $jj<count($this->metadata_disp); $jj++) {
        		if( strcmp($tmp_str, $this->metadata_disp[$jj]) == 0 ){
        			$array_dis[$ii] = 1;
        			break;
        		}
        	}
        	for($jj=0; $jj<count($this->metadata_plural); $jj++) {
        		if( strcmp($tmp_str, $this->metadata_plural[$jj]) == 0 ){
        			$array_plu[$ii] = 1;
        			break;
        		}
        	}
   		    for($jj=0; $jj<count($this->metadata_newline); $jj++) {
  	      		if( strcmp($tmp_str, $this->metadata_newline[$jj]) == 0 ){
        			$array_newline[$ii] = 1;
        			break;
        		}
        	}
        	for($jj=0; $jj<count($this->metadata_hidden); $jj++) {
  	      		if( strcmp($tmp_str, $this->metadata_hidden[$jj]) == 0 ){
        			$array_hidden[$ii] = 1;
        			break;
        		}
        	}
	   	}
		$this->Session->setParameter("metadata_required", $array_req);
	   	$this->Session->setParameter("metadata_disp", $array_dis);
	   	$this->Session->setParameter("metadata_plural", $array_plu);
	   	$this->Session->setParameter("metadata_newline", $array_newline);
	   	$this->Session->setParameter("metadata_hidden", $array_hidden);
	   	
	   	$this->Session->setParameter("error_code", $nullError);
	   	
	   	if($nullError != 0){
	   		return 'error';
	   	}
	   	
		// 編集時に起こる属性名よ属性IDのずれを修正 2008/06/19 Y.Nakao 上記バグの対処 --start--
		$metadata_num = $this->Session->getParameter("metadata_num");
		$attribute_id = $this->Session->getParameter("attribute_id");
		if($metadata_num != count($attribute_id)){
			if($metadata_num === 1 || !is_array($attribute_id)){
				$attribute_id = array();
			}
 			array_push($attribute_id,-1);
 			// sessionにも反映
 			$this->Session->setParameter("attribute_id", $attribute_id);
		}
	   	// 2008/06/19 Y.Nakao --end--
	   	
		$this->Session->removeParameter("error_code");
		
	   	return 'success';
	   	
    }
}
?>
