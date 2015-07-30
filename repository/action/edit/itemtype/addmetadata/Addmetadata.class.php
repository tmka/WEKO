<?php
// --------------------------------------------------------------------
//
// $Id: Addmetadata.class.php 41322 2014-09-10 11:56:44Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * repositoryモジュール アイテムタイプ作成 編集画面でメタデータ追加時に呼ばれるアクション
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Addmetadata
{
	// 使用コンポーネントを受け取るため
	var $session = null;
	var $request = null;
	//var $itemtype_name = null;		//前画面で入力したアイテムタイプ名(新規作成時)
	
	// リクエストパラメタ
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータタイプ配列
	var $metadata_required = null;	// メタデータ必須フラグ列
	var $metadata_disp = null;		// メタデータ一覧表示フラグ列
	
	var $metadata_candidate = null;	// メタデータ選択肢配列 2008/02/28
	var $metadata_plural = null;	// メタデータ複数可否配列 2008/03/04
	var $metadata_newline = null;	// メタデータ改行指定配列 2008/03/13
	var $metadata_hidden = null;	// メタデータ非表示設定配列 2009/01/28

	var $item_type_name = null;		// Add metadata name edit 2008/09/04 Y.Nakao
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// 現在のメタデータ設定値をセッションに保存
//    	for($ii=0; $ii<1; $ii++) {
//    		// ピクリとも反応せず。nullでなくて0文字が戻る？紛らわしい。
//    		$tmpstr = sprintf("metadeta_title[%d]", $ii);
//    		$tmptitle = null;
//    		$tmptitle = $this->request->getParameter($tmpstr);
//    		if( $tmptitle != null ) {
//				$this->session->setParameter($tmpstr, $tmptitle);
//    		}
//    	}
    
    	// 1個ずつだがうまくいった例
//	    for($ii=0; $ii<count($this->metadata_title); $ii++) {
//	    	$this->session->setParameter(sprintf("metadata_title%d",$ii), $this->metadata_title[$ii]);
//	   	}
	   	
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
    	
    	// Add metadata name edit 2008/09/04 Y.Nakao --start--
    	// Save item type name
    	// Mod metadata name edit 2009/12/10 K.Ando --start--
    	//$this->session->setParameter("itemtype_name", $this->item_type_name);
    	$this->session->setParameter("item_type_name", $this->item_type_name);
    	// Mod metadata name edit 2009/12/10 K.Ando --end--
    	// Add metadata name edit 2008/09/04 Y.Nakao --end--
    	
        // metadata_titleをまとめて配列でセッションに保存
        $this->session->setParameter("metadata_title", $array_title);
	   	// metadata_typeをまとめて配列でセッションに保存
	   	$this->session->setParameter("metadata_type", $this->metadata_type);

	   	// 2008/02/28 選択肢をまとめて配列でセッションに保存
	   	$this->session->setParameter("metadata_candidate", $array_candidate);

	   	//チェックボックスはチェックの入ったnameのvalueのみが送信されるため、データを調整
	   	// フラグもまとめてセッションに保存
	   	$array_req = array();
	   	$array_dis = array();
	   	$array_plu = array(); // 2008/03/04 複数可否追加
	   	$array_newline = array();	// 改行指定追加 2008/03/13
	   	$array_hidden = array();	// 非表示設定追加 2009/01/28
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
	   	$this->session->setParameter("metadata_required", $array_req);
	   	$this->session->setParameter("metadata_disp", $array_dis);
	   	$this->session->setParameter("metadata_plural", $array_plu);
	   	$this->session->setParameter("metadata_newline", $array_newline);
	   	$this->session->setParameter("metadata_hidden", $array_hidden);

	   	// 既存編集時 2008/03/03
 		if($this->session->getParameter("item_type_edit_flag") == 1) {
 			$array_attri_id = $this->session->getParameter("attribute_id");
            if(!isset($array_attri_id)) {
                $array_attri_id = array();
            }
 			array_push($array_attri_id,-1);
 			// 一行増えた場合、その分sessionにも反映
 			$this->session->setParameter("attribute_id", $array_attri_id);
 		}
 		//2008/03/03
 		
    	// メタデータ数を増やす
    	$this->session->setParameter("metadata_num", $this->session->getParameter("metadata_num") + 1);
    	
    	// エラーなし 2008/02/28
    	$this->session->setParameter("error_code", 0);
        // Add multi language K.Matsuo 2013/07/24 --start--
        $lang_list = $this->session->getParameter("lang_list");
        $array_metadata_multi_title = $this->session->getParameter("metadata_multi_title");
        $multiLang = array();
        foreach($lang_list as $key => $lang){
            $multiLang[$key] = "";
        }
        array_push($array_metadata_multi_title,$multiLang);
        $this->session->setParameter("metadata_multi_title", $array_metadata_multi_title);
        // Add multi language K.Matsuo 2013/07/24 --end--
        return 'success';

    }
}
?>
