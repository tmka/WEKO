<?php
// --------------------------------------------------------------------
//
// $Id: Dellmetadata.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
 * repositoryモジュール アイテムタイプ作成 編集画面でメタデータ削除時に呼ばれるアクション
 *
 * @package     NetCommons
 * @author      nakao(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Dellmetadata extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	var $request = null;
    // Mod metadata name edit 2009/12/10 K.Ando --start--
	//var $itemtype_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
	var $item_type_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
    // Mod metadata name edit 2009/12/10 K.Ando --edit--
	
	
	// jsの引数がリクエストとして送信される
	var $dell_metadata_number = null;
	
	
	// メタデータ用配列
	var $metadata_title = null;
	var $metadata_type = null;
	var $metadata_required = null;
	var $metadata_disp = null;
	
	var $metadata_candidate = null;	// メタデータ選択肢配列 2008/02/28
	var $metadata_plural = null;	// メタデータ複数可否配列 2008/03/04
	var $metadata_newline = null;	// メタデータ改行指定配列 2008/03/13
	var $metadata_hidden = null;	// メタデータ非表示設定配列 2009/01/28
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
    	// 送信されたFormデータから削除対称を削除する
		if($this->metadata_title != null && $this->metadata_type != null)
		{
			// 削除ボタンが押されたメタデータを削除
			// 項目名削除
			array_splice($this->metadata_title, $this->dell_metadata_number,1);
			// 属性名削除
			array_splice($this->metadata_type, $this->dell_metadata_number,1);
			
			// 選択肢削除
			array_splice($this->metadata_candidate, $this->dell_metadata_number,1);
			
			// 必須、一覧表示フラグ削除 2008/02/27
			// 削除対称に必須チェックがついていたか判定
			$nDelFlg_req = 0;
			for($nCnt=0;$nCnt<count($this->metadata_required);$nCnt++){
				if($this->metadata_required[$nCnt] == $this->dell_metadata_number){
					$nDelFlg_req = 1;
				}
				if($this->metadata_required[$nCnt] > $this->dell_metadata_number){
					if($nDelFlg_req == 1){
						// 削除対称にチェックがある場合
						if($this->metadata_required[$nCnt-1] >= $this->dell_metadata_number){
							$this->metadata_required[$nCnt-1] = $this->metadata_required[$nCnt]-1;
						}
					} else {
						// 削除対称にチェックが無い場合
						$this->metadata_required[$nCnt] = $this->metadata_required[$nCnt]-1;
					}
				}
			}
			// 削除対称にチェックがついていた場合、配列を削る
			if($nDelFlg_req == 1){			
				array_pop($this->metadata_required);
			}
			// 削除対称に一覧表示チェックがついていたか判定
			$nDelFlg_dis = 0;
			for($nCnt=0;$nCnt<count($this->metadata_disp);$nCnt++){
				if($this->metadata_disp[$nCnt] == $this->dell_metadata_number){
					$nDelFlg_dis = 1;
				}
				if($this->metadata_disp[$nCnt] > $this->dell_metadata_number){
					if($nDelFlg_dis == 1){
						// 削除対称にチェックがある場合
						if($this->metadata_disp[$nCnt-1] >= $this->dell_metadata_number){
							$this->metadata_disp[$nCnt-1] = $this->metadata_disp[$nCnt]-1;
						}
					} else {
						//削除対称にチェックが無い場合
						$this->metadata_disp[$nCnt] = $this->metadata_disp[$nCnt]-1;
					}
				}
			}
			// 削除対象にチェックがついていた場合、配列を削る
			if($nDelFlg_dis == 1){				
				array_pop($this->metadata_disp);
			}
			
			// 削除対称に複数可否チェックがついていたか判定
			$nDelFlg_plu = 0;
			for($nCnt=0;$nCnt<count($this->metadata_plural);$nCnt++){
				if($this->metadata_plural[$nCnt] == $this->dell_metadata_number){
					$nDelFlg_plu = 1;
				}
				if($this->metadata_plural[$nCnt] > $this->dell_metadata_number){
					if($nDelFlg_plu == 1){
						// 削除対称にチェックがある場合
						if($this->metadata_plural[$nCnt-1] >= $this->dell_metadata_number){
							$this->metadata_plural[$nCnt-1] = $this->metadata_plural[$nCnt]-1;
						}
					} else {
						//削除対称にチェックが無い場合
						$this->metadata_plural[$nCnt] = $this->metadata_plural[$nCnt]-1;
					}
				}
			}
			// 削除対象にチェックがついていた場合、配列を削る
			if($nDelFlg_plu == 1){				
				array_pop($this->metadata_plural);
			}
			
			// 削除対称に複数可否チェックがついていたか判定 改行
			$nDelFlg_newline = 0;
			for($nCnt=0;$nCnt<count($this->metadata_newline);$nCnt++){
				if($this->metadata_newline[$nCnt] == $this->dell_metadata_number){
					$nDelFlg_newline = 1;
				}
				if($this->metadata_newline[$nCnt] > $this->dell_metadata_number){
					if($nDelFlg_newline == 1){
						// 削除対称にチェックがある場合
						if($this->metadata_newline[$nCnt-1] >= $this->dell_metadata_number){
							$this->metadata_newline[$nCnt-1] = $this->metadata_newline[$nCnt]-1;
						}
					} else {
						//削除対称にチェックが無い場合
						$this->metadata_newline[$nCnt] = $this->metadata_newline[$nCnt]-1;
					}
				}
			}
			// 削除対象にチェックがついていた場合、配列を削る
			if($nDelFlg_newline == 1){				
				array_pop($this->metadata_newline);
			}
			
			// 削除対称に非表示チェックがついていたか判定 2009/01/28 A.Suzuki --start--
			$nDelFlg_hidden = 0;
			for($nCnt=0;$nCnt<count($this->metadata_hidden);$nCnt++){
				if($this->metadata_hidden[$nCnt] == $this->dell_metadata_number){
					$nDelFlg_hidden = 1;
				}
				if($this->metadata_hidden[$nCnt] > $this->dell_metadata_number){
					if($nDelFlg_hidden == 1){
						// 削除対称にチェックがある場合
						if($this->metadata_hidden[$nCnt-1] >= $this->dell_metadata_number){
							$this->metadata_hidden[$nCnt-1] = $this->metadata_hidden[$nCnt]-1;
						}
					} else {
						//削除対称にチェックが無い場合
						$this->metadata_hidden[$nCnt] = $this->metadata_hidden[$nCnt]-1;
					}
				}
			}
			// 削除対象にチェックがついていた場合、配列を削る
			if($nDelFlg_hidden == 1){				
				array_pop($this->metadata_hidden);
			}
			// 削除対称に非表示チェックがついていたか判定 2009/01/28 A.Suzuki --end--
		}
		// メタデータ数を減らす.
		if($this->Session->getParameter("metadata_num") > 0)
		{
			$this->Session->setParameter("metadata_num", $this->Session->getParameter("metadata_num") - 1);
		}
		// 既存編集時 2008/03/03
		if($this->Session->getParameter("item_type_edit_flag") == 1) {
			// 削除前の属性IDのリスト
			$array_attr_id = $this->Session->getParameter("attribute_id");
			
			// 削除対象配列を更新
 			$del_attr_id = $this->Session->getParameter("del_attribute_id");
 			if($del_attr_id == null){
 				$del_attr_id = array($array_attr_id[$this->dell_metadata_number]);
 			}
 			else {
 				if($array_attr_id != null){
		 			array_push($del_attr_id, $array_attr_id[$this->dell_metadata_number]);
 				}
 			}
 			$this->Session->setParameter("del_attribute_id",$del_attr_id);
			// 一行減った場合、、その行の属性IDを削除し、sessionに反映
			array_splice($array_attr_id, $this->dell_metadata_number,1);
 			$this->Session->setParameter("attribute_id", $array_attr_id); 			
 		}
 		//2008/03/03
        
 		////////////////////////// " "をnull文字列に ///////////////////////////
    	$array_title = array();	// 項目名一時保管用 
    	$array_candidate = array();	// 選択肢一時保管用		
	    // 項目名(metadata_title)が空のチェック
    	for($nCnt=0;$nCnt<count($this->metadata_title);$nCnt++){
    		if($this->metadata_title[$nCnt] == " "){
	    		array_push($array_title, "");
    		} else {
	    		array_push($array_title, $this->metadata_title[$nCnt]);
    		}
    	}
    	// 選択肢(metadata_candidate)のチェック
    	for($nCnt=0;$nCnt<count($this->metadata_type);$nCnt++){
    		if( $this->metadata_type[$nCnt] == "checkbox" ||
    			$this->metadata_type[$nCnt] == "radio" ||
    			$this->metadata_type[$nCnt] == "select"){
    			if($this->metadata_candidate[$nCnt] == " "){
	    			array_push($array_candidate, "");
    			} else {
    				array_push($array_candidate, $this->metadata_candidate[$nCnt]);
    			}
    		} else {
    			array_push($array_candidate, "");
    		}
    	}
 		
		// sessionの保存
		// metadata_titleをまとめて配列でセッションに保存
        $this->Session->setParameter("metadata_title", $array_title);
	   	// metadata_typeをまとめて配列でセッションに保存
	   	$this->Session->setParameter("metadata_type", $this->metadata_type);
	   	
    	// Save item type name
    	$this->Session->setParameter("item_type_name", $this->item_type_name);
	   	
	   	// 2008/02/28 選択肢をまとめて配列でセッションに保存 nakao
	   	$this->Session->setParameter("metadata_candidate", $array_candidate);
	   	
    	//チェックボックスはチェックの入ったnameのvalueのみが送信されるため、データを調整
	   	// フラグもまとめてセッションに保存
	   	$array_req = array();
	   	$array_dis = array();
	   	$array_plu = array(); // 2008/03/04 複数可否追加
	   	$array_newline = array(); // 2008/03/13 改行指定配列追加
	   	$array_hidden = array(); // 2009/01/28 非表示設定配列追加
        for($ii=0; $ii<count($this->metadata_title); $ii++) {
        	array_push($array_req, 0);
        	array_push($array_dis, 0);
        	array_push($array_plu, 0);
        	array_push($array_newline,0);
        	array_push($array_hidden,0);
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
	   	
        // Add multi language K.Matsuo 2013/07/24 --start--
        $array_metadata_multi_title = $this->Session->getParameter("metadata_multi_title");
        array_splice($array_metadata_multi_title, $this->dell_metadata_number,1);
        $this->Session->setParameter("metadata_multi_title", $array_metadata_multi_title);
        // Add multi language K.Matsuo 2013/07/24 --end--
	   	
	   	return 'success';
		
    }
}
?>
