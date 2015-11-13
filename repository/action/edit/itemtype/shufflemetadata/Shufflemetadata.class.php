<?php
// --------------------------------------------------------------------
//
// $Id: Shufflemetadata.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
 * repositoryモジュール アイテムタイプ作成 編集画面でメタデータ追入れ替えクリック時に呼ばれるアクション
 *
 * @package     NetCommons
 * @author      nakao(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Shufflemetadata extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	var $request = null;
    // アイテムタイプ名保持用変数　追加 2009/12/10 K.Ando --start--
	//var $itemtype_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
	var $item_type_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
    // アイテムタイプ名保持用変数　追加 2009/12/10 K.Ando --end--
	
	
	// jsの引数がリクエストとして送信される
	var $shuffle_idx = null;	// 移動されるIndex
	var $shuffle_flg = null;	// true:上に移動, false:下に移動	
	
	// メタデータ用配列
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータ属性配列
	var $metadata_candidate = null;	// メタデータ選択肢配列
	var $metadata_required = null;	// メタデータ必須
	var $metadata_disp = null;		// メタデータ一覧表示
	var $metadata_plural = null;	// メタデータ複数可否配列
	var $metadata_newline = null;	// メタデータ改行指定追加
	var $metadata_hidden = null;	// メタデータ非表示指定	2009/02/09 A.Suzuki
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
    	$metadata_title = $this->metadata_title;
    	$metadata_type = $this->metadata_type;
    	$metadata_candidate = $this->metadata_candidate;
    	$metadata_required = $this->metadata_required;
    	$metadata_disp = $this->metadata_disp;
    	$metadata_plural = $this->metadata_plural;
    	$metadata_newline = $this->metadata_newline;
    	$metadata_hidden = $this->metadata_hidden;
        $array_metadata_multi_title = $this->Session->getParameter("metadata_multi_title");
    	
	    // チェックボックスはチェックの入ったnameのvalueのみが送信されるため、データを調整
	   	$array_req = array();
	   	$array_dis = array();
	   	$array_plu = array(); // 2008/03/04 複数可否追加
	   	$array_newline = array();	// 2008/03/20 改行指定追加
	   	$array_hidden = array();	// 2009/02/09 非表示指定追加
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
	   	
    	// $shuffle_idx行目を上に移動
    	if($this->shuffle_flg == "true"){
    		// 入れ替え処理
    		for($nCnt=1;$nCnt<count($this->metadata_title);$nCnt++){
    			if($nCnt == $this->shuffle_idx){
    				// 項目名入れ替え
    				$tmp = $metadata_title[$this->shuffle_idx];
    				$metadata_title[$this->shuffle_idx] = $metadata_title[$this->shuffle_idx-1];
    				$metadata_title[$this->shuffle_idx-1] = $tmp;
    				// 属性入れ替え
    				$tmp = $metadata_type[$this->shuffle_idx];
    				$metadata_type[$this->shuffle_idx] = $metadata_type[$this->shuffle_idx-1];
    				$metadata_type[$this->shuffle_idx-1] = $tmp;
    				// 選択肢配列入れ替え
    				$tmp = $metadata_candidate[$this->shuffle_idx];
    				$metadata_candidate[$this->shuffle_idx] = $metadata_candidate[$this->shuffle_idx-1];
    				$metadata_candidate[$this->shuffle_idx-1] = $tmp;
    				// 必須チェック
    				$tmp = $array_req[$this->shuffle_idx];
    				$array_req[$this->shuffle_idx] = $array_req[$this->shuffle_idx-1];
    				$array_req[$this->shuffle_idx-1] = $tmp;
    				// 一覧表示チェック
    				$tmp = $array_dis[$this->shuffle_idx];
    				$array_dis[$this->shuffle_idx] = $array_dis[$this->shuffle_idx-1];
    				$array_dis[$this->shuffle_idx-1] = $tmp;
	   				// 複数可否チェック
    				$tmp = $array_plu[$this->shuffle_idx];
    				$array_plu[$this->shuffle_idx] = $array_plu[$this->shuffle_idx-1];
    				$array_plu[$this->shuffle_idx-1] = $tmp;
    				// 改行指定チェック
    				$tmp = $array_newline[$this->shuffle_idx];
    				$array_newline[$this->shuffle_idx] = $array_newline[$this->shuffle_idx-1];
    				$array_newline[$this->shuffle_idx-1] = $tmp;
    				// 非表示指定チェック
    				$tmp = $array_hidden[$this->shuffle_idx];
    				$array_hidden[$this->shuffle_idx] = $array_hidden[$this->shuffle_idx-1];
    				$array_hidden[$this->shuffle_idx-1] = $tmp;
                    // アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --start--
    				$tmp = $array_metadata_multi_title[$this->shuffle_idx];
    				$array_metadata_multi_title[$this->shuffle_idx] = $array_metadata_multi_title[$this->shuffle_idx-1];
    				$array_metadata_multi_title[$this->shuffle_idx-1] = $tmp;
                    // アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --end--
    				// 既存編集の場合
    				if($this->Session->getParameter("item_type_edit_flag") == 1){
    					// attribute_id配列入れ替え
    					$array_attri_id = $this->Session->getParameter("attribute_id");
	    				$tmp = $array_attri_id[$this->shuffle_idx];
	    				$array_attri_id[$this->shuffle_idx] = $array_attri_id[$this->shuffle_idx-1];
	    				$array_attri_id[$this->shuffle_idx-1] = $tmp;
	    				$this->Session->setParameter("attribute_id",$array_attri_id);
    				}
    				break;
    			}    			
    		}
    	}
    	// $shuffle_idx行目を上に移動
    	else {
    	// 入れ替え処理
    		for($nCnt=0;$nCnt<count($this->metadata_title)-1;$nCnt++){
    			if($nCnt == $this->shuffle_idx){
    				// 項目名入れ替え
    				$tmp = $metadata_title[$this->shuffle_idx];
    				$metadata_title[$this->shuffle_idx] = $metadata_title[$this->shuffle_idx+1];
    				$metadata_title[$this->shuffle_idx+1] = $tmp;
    				// 属性入れ替え
    				$tmp = $this->metadata_type[$this->shuffle_idx];
    				$metadata_type[$this->shuffle_idx] = $metadata_type[$this->shuffle_idx+1];
    				$metadata_type[$this->shuffle_idx+1] = $tmp;
    				// 選択肢配列入れ替え
    				$tmp = $this->metadata_candidate[$this->shuffle_idx];
    				$metadata_candidate[$this->shuffle_idx] = $metadata_candidate[$this->shuffle_idx+1];
    				$metadata_candidate[$this->shuffle_idx+1] = $tmp;
    				// 必須チェック
    				$tmp = $array_req[$this->shuffle_idx];
    				$array_req[$this->shuffle_idx] = $array_req[$this->shuffle_idx+1];
    				$array_req[$this->shuffle_idx+1] = $tmp;
    				// 一覧表示チェック
    				$tmp = $array_dis[$this->shuffle_idx];
    				$array_dis[$this->shuffle_idx] = $array_dis[$this->shuffle_idx+1];
    				$array_dis[$this->shuffle_idx+1] = $tmp;
	   				// 複数可否チェック
    				$tmp = $array_plu[$this->shuffle_idx];
    				$array_plu[$this->shuffle_idx] = $array_plu[$this->shuffle_idx+1];
    				$array_plu[$this->shuffle_idx+1] = $tmp;
    				// 改行指定チェック
    				$tmp = $array_newline[$this->shuffle_idx];
    				$array_newline[$this->shuffle_idx] = $array_newline[$this->shuffle_idx+1];
    				$array_newline[$this->shuffle_idx+1] = $tmp;
    				// 非表示指定チェック
    				$tmp = $array_hidden[$this->shuffle_idx];
    				$array_hidden[$this->shuffle_idx] = $array_hidden[$this->shuffle_idx+1];
    				$array_hidden[$this->shuffle_idx+1] = $tmp;
                    // アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --start--
    				$tmp = $array_metadata_multi_title[$this->shuffle_idx];
    				$array_metadata_multi_title[$this->shuffle_idx] = $array_metadata_multi_title[$this->shuffle_idx+1];
    				$array_metadata_multi_title[$this->shuffle_idx+1] = $tmp;
                    // アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --end--
    				// 既存編集の場合
    				if($this->Session->getParameter("item_type_edit_flag") == 1){
    					// attribute_id配列入れ替え
    					$array_attri_id = $this->Session->getParameter("attribute_id");
	    				$tmp = $array_attri_id[$this->shuffle_idx];
	    				$array_attri_id[$this->shuffle_idx] = $array_attri_id[$this->shuffle_idx+1];
	    				$array_attri_id[$this->shuffle_idx+1] = $tmp;
	    				$this->Session->setParameter("attribute_id",$array_attri_id);
    				}
    				break;
    			}    			
    		}
    	}

        $array_title = array();	// 項目名一時保管用 
    	$array_candidate = array();	// 選択肢一時保管用		
	    // 項目名(metadata_title)が空のチェック
    	for($nCnt=0;$nCnt<count($metadata_title);$nCnt++){
    		if($metadata_title[$nCnt] == " "){
	    		array_push($array_title, "");
    		} else {
	    		array_push($array_title, $metadata_title[$nCnt]);
    		}
    	}
    	// 選択肢(metadata_candidate)のチェック
    	for($nCnt=0;$nCnt<count($metadata_type);$nCnt++){
    		if( $metadata_type[$nCnt] == "checkbox" ||
    			$metadata_type[$nCnt] == "radio" ||
    			$metadata_type[$nCnt] == "select"){
    			if($metadata_candidate[$nCnt] == " "){
	    			array_push($array_candidate, "");
    			} else {
    				array_push($array_candidate, $metadata_candidate[$nCnt]);
    			}
    		} else {
    			array_push($array_candidate, "");
    		}
    	}
    	
		// Sessionの保存
		// metadata_titleをまとめて配列でセッションに保存
        $this->Session->setParameter("metadata_title", $array_title);
	   	// metadata_typeをまとめて配列でセッションに保存
	   	$this->Session->setParameter("metadata_type", $metadata_type);
	   	// 選択肢をまとめて配列でセッションに保存
	   	$this->Session->setParameter("metadata_candidate",$array_candidate);
    	// 必須チェック
		$this->Session->setParameter("metadata_required", $array_req);
		// 一覧表示チェック
	   	$this->Session->setParameter("metadata_disp", $array_dis);
	   	// 複数可否チェック
	   	$this->Session->setParameter("metadata_plural", $array_plu);
	   	// 改行指定チェック
	   	$this->Session->setParameter("metadata_newline", $array_newline);
	   	// 非表示指定チェック
	   	$this->Session->setParameter("metadata_hidden", $array_hidden);
    	// アイテムタイプ名保存処理　追加 2009/12/10 K.Ando --start--
    	//$this->Session->setParameter("itemtype_name", $this->item_type_name);
    	$this->Session->setParameter("item_type_name", $this->item_type_name);
    	// アイテムタイプ名保存処理　追加 2009/12/10 K.Ando --end--	   	
    	// アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --start--
        $this->Session->setParameter("metadata_multi_title", $array_metadata_multi_title);
    	// アイテムタイプ項目名多言語 2013/7/24 K.Matsuo --end--
	   	return 'success';
		
    }
}
?>
