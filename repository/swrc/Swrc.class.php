<?php
// --------------------------------------------------------------------
//
// $Id: Swrc.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Swrc extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	//var $session = null;
	var $db = null;
	
	// リクエストパラメータを受け取るため
	var $itemId = null;
	var $itemNo = null;
	
	// ダウンロード用メンバ
	var $uploadsView = null;

	// 改行
	var $LF = "\n";
	
	// タブシフト
	var $TAB_SHIFT = "\t";
	
	// 出力文字列
	var $xml = '';
	
	// エラーメッセージ
	var $errorMsg = "";
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// ヘッダ出力
    	//header("Content-Type: text/xml; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/xml")
    	
    	// 初期処理
    	$this->initAction();
    	
		// フィード文字列取得
		$xml = $this->outputSwrc();
    	
    	// 取得結果がfalseでなければ
    	if ( $xml != false ) {
    		// ヘッダ出力
    		header("Content-Type: text/xml; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/xml")
	    	// フィード出力
			print $xml;
       	}else{
       		// ヘッダ出力
       		header("Content-Type: text/html; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/html")
       		print $this->errorMsg;
       	}
		
       	// 終了処理
		$this->exitAction();
       	
		// XML書き出し終了後にexit関数を呼び出す
    	exit();

    }
	
    function outputSwrc()
    {
    	// アイテム情報の取得
    	$query = 'SELECT ITEMTYPE.mapping_info, '.
    			 '		 ITEM.title, '.
    			 //Add title english & serch_key_english 2009/08/28 K.Ito --start--
    			 '		 ITEM.serch_key_english, '.
    			 '		 ITEM.title_english, '.
    			 //Add title english & serch_key_english 2009/08/28 K.Ito --end--
    			 '		 ITEM.language, '.
    			 '		 ITEM.item_type_id, '.
    			 '		 ITEM.serch_key, '.
    			 '		 ITEM.shown_status '.
    			 'FROM '.DATABASE_PREFIX.'repository_item ITEM, '.
    			 '     '.DATABASE_PREFIX.'repository_item_type ITEMTYPE '.
				 'WHERE ITEM.item_type_id = ITEMTYPE.item_type_id '.
   				 '  AND ITEM.item_id = ? '.
    			 '  AND ITEM.item_no = ? '.
    			 '  AND ITEM.is_delete = 0;';
    	$params = null;
		$params[] = $this->itemId;
		$params[] = $this->itemNo;
 
    	$retItem = $this->Db->execute($query, $params);
		if ($retItem === false) {
			return false;
		}
    	
    	// Add LIDO 2014/05/20 S.Suzuki --start--
    	$tmp_key = array();
    	$tmp_key_eng = array();
		
		for ($ii = 0; $ii < count($retItem); $ii++) {
			$retItem[$ii]['mapping_info']      = RepositoryOutputFilter::exclusiveReservedWords($retItem[$ii]['mapping_info']);
			$retItem[$ii]['title']             = RepositoryOutputFilter::exclusiveReservedWords($retItem[$ii]['title']);
			$retItem[$ii]['title_english']     = RepositoryOutputFilter::exclusiveReservedWords($retItem[$ii]['title_english']);
			
			$skey = explode("|", $retItem[$ii]['serch_key']);
			for($jj = 0; $jj < count($skey); $jj++) {
				$value = RepositoryOutputFilter::exclusiveReservedWords($skey[$jj]);
				if ($value !== '') {
	                array_push($tmp_key, $value);
	            }
			}
			$retItem[$ii]['serch_key'] = implode("|", $tmp_key);
			
			$skey_eng = explode("|", $retItem[$ii]['serch_key_english']);
			for($jj = 0; $jj < count($skey_eng); $jj++) {
				$value = RepositoryOutputFilter::exclusiveReservedWords($skey_eng[$jj]);
				if ($value !== '') {
	                array_push($tmp_key_eng, $value);
	            }
			}
			$retItem[$ii]['serch_key'] = implode("|", $tmp_key);
		}
    	// Add LIDO 2014/05/20 S.Suzuki --end--
    	
		// アイテム情報が無い場合、終了
		if (count($retItem) < 1){
			$this->errorMsg = 'This item data is not found.';
			return false;
    	}
    	// アイテムタイプのマッピング情報がない場合終了
    	if($retItem[0]['mapping_info'] == null){
    		$this->errorMsg = 'This item has no mapping info.';
    		return false;
    	}
    	
    	// Add check item puloc status 2010/01/12 Y.Nakao --start--
        // Add tree access control list 2012/03/07 T.Koyasu -start-
        $public_index = array();
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
        // Add tree access control list 2012/03/07 T.Koyasu -end-
		// アイテム公開チェック
        // Add Advanced Search 2013/11/26 R.Matsuura --start--
        $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Add Advanced Search 2013/11/26 R.Matsuura --end--
        if(!$itemAuthorityManager->checkItemPublicFlg($this->itemId, $this->itemNo, $this->repository_admin_base, $this->repository_admin_room)){
			// item close
			$retItem[0]['shown_status'] = "0";
		}
		// Add check item puloc status 2010/01/12 Y.Nakao --end--
    	
        // アイテムが非公開の場合エラー
    	if($retItem[0]['shown_status'] != 1){
    		$this->errorMsg = 'This item is private.';
    		return false;
    	}
    	
    	// メタデータ取得
    	$query = 'SELECT attribute_id, '.
    	    	 '		 show_order, '.
    			 '		 attribute_name, '.
    			 '		 input_type, '.
    			 '		 junii2_mapping, '.
    			 // Fix output hidden metadata 2011/11/28 Y.Nakao --start--
    			 '		 hidden, '.
    			 // Fix output hidden metadata 2011/11/28 Y.Nakao --end--
    			 //Add display lang type 2009/08/28 K.Ito --start--
    			 '       display_lang_type '.
    			 //Add display lang type 2009/08/28 K.Ito --end--
    			 'FROM '.DATABASE_PREFIX.'repository_item_attr_type '.
				 'WHERE item_type_id = ? '.
    			 '	AND is_delete = 0 '.
    			 'order by show_order;';
	    $params = null;
	    $params[] = $retItem[0]['item_type_id'];
    	$retAttr = $this->Db->execute($query, $params);
		if ($retAttr === false) {
			return false;
		}
		
		// 入力されているメタデータのvalueを取得
    	$query = 'SELECT attribute_id, '.
    	    	 '		 attribute_no, '.
    			 '		 attribute_value '.
    			 'FROM '.DATABASE_PREFIX.'repository_item_attr '.
				 'WHERE item_id = ? '.
    			 '	AND item_no = ? '.
    			 '	AND item_type_id = ? '.
    			 '	AND is_delete = 0 '.
    			 'order by attribute_id;';
	    $params = null;
	    $params[] = $this->itemId;
	    $params[] = $this->itemNo;
	    $params[] = $retItem[0]['item_type_id'];
    	$retAttrValue = $this->Db->execute($query, $params);
		if ($retAttrValue === false) {
			return false;
		}
		
    	// Add LIDO 2014/05/13 S.Suzuki --start--
		for ($ii = 0; $ii < count($retAttrValue); $ii++) {
			$retAttrValue[$ii]['attribute_id']    = RepositoryOutputFilter::exclusiveReservedWords($retAttrValue[$ii]['attribute_id']);
			$retAttrValue[$ii]['attribute_no']    = RepositoryOutputFilter::exclusiveReservedWords($retAttrValue[$ii]['attribute_no']);
			$retAttrValue[$ii]['attribute_value'] = RepositoryOutputFilter::exclusiveReservedWords($retAttrValue[$ii]['attribute_value']);
		}
    	// Add LIDO 2014/05/13 S.Suzuki --end--
		
		// 複数入力の紐づけ
    	for($ii=0;$ii<count($retAttr);$ii++){
    		$cntValue = 0;
    		for($jj=0;$jj<count($retAttrValue);$jj++){
	    		if($retAttr[$ii]['attribute_id'] == $retAttrValue[$jj]['attribute_id']){
					$retAttr[$ii]['value'][$cntValue] = $retAttrValue[$jj]['attribute_value'];
					$cntValue++;
				}
    		}
		}

		// アイテム詳細画面のURLを取得
		$item_detail = null;
		// Add detail uri 2008/11/13 Y.Nakao --start--
		$item_detail = $this->getDetailUri($this->itemId, $this->itemNo);
		if($item_detail==null || $item_detail == ""){
			return false;
		}
		$item_detail = $item_detail;
		// Add detail uri 2008/11/13 Y.Nakao --end--
		
    	$xml = null;
    	
    	// xmlヘッダ出力
    	$xml = 	'<?xml version="1.0" encoding="UTF-8" ?>'.$this->LF.
    			'<rdf:RDF'.$this->LF.
    			'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.$this->LF.
    			'xmlns:owl="http://www.w3.org/2002/07/owl#"'.$this->LF.
    			'xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"'.$this->LF.
    			'xmlns:swrc="http://swrc.ontoware.org/ontology#"'.$this->LF.
    			'xmlns:dc="http://purl.org/dc/elements/1.1/">'.$this->LF.$this->LF;
    	
    	// アイテム詳細画面のURLを出力
    	$xml .= '<rdf:Description rdf:about="'.$this->forXmlChange($item_detail).'">'.$this->LF;
    	
    	// アイテム基本情報の出力
    	// クラスを出力
    	$swrcClass = null;
    	switch($retItem[0]['mapping_info']){
    		case 'Book':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#Book';
    			break;
    		case 'Departmental Bulletin Paper':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#UnrefereedArticle';
    			break;
    		case 'Research Paper':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#ResearchPaper';
    			break;
    		case 'Thesis or Dissertation':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#Thesis';
    			break;
    		case 'Technical Report':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#TechnicalReport';
    			break;
    		case 'Preprint':
    		case 'Presentation':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#Unpublished';
    			break;
    		case 'Journal Article':
    		case 'Article':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#Article';
    			break;
    		case 'Conference Paper':
    			$swrcClass = 'http://swrc.ontoware.org/ontology#InProceedings';
    			break;
    		case 'Learning Material':
    		case 'Data or Dataset':
    		case 'Software':
    		case 'Others':
    		default:
    			$swrcClass = 'http://swrc.ontoware.org/ontology#Misc';
    	}
    	$xml .= $this->TAB_SHIFT.'<rdf:type>'.$swrcClass.'</rdf:type>'.$this->LF;
    	
    	//Add multiple language 2009/08/28 K.Ito --start--
    	// タイトル
    	if($retItem[0]['language'] == "ja"){
    		if($retItem[0]['title'] != ""){
    			$xml .= $this->TAB_SHIFT.'<swrc:title xml:lang="ja">'.htmlspecialchars($retItem[0]['title'], ENT_QUOTES, 'UTF-8').'</swrc:title>'.$this->LF;
    		}else if($retItem[0]['title_english'] != ""){
    			$xml .= $this->TAB_SHIFT.'<swrc:title xml:lang="en">'.htmlspecialchars($retItem[0]['title_english'], ENT_QUOTES, 'UTF-8').'</swrc:title>'.$this->LF;
    		}
    	}else{
    		if($retItem[0]['title_english'] != ""){
    			$xml .= $this->TAB_SHIFT.'<swrc:title xml:lang="en">'.htmlspecialchars($retItem[0]['title_english'], ENT_QUOTES, 'UTF-8').'</swrc:title>'.$this->LF;
    		}else if($retItem[0]['title'] != ""){
    			$xml .= $this->TAB_SHIFT.'<swrc:title xml:lang="ja">'.htmlspecialchars($retItem[0]['title'], ENT_QUOTES, 'UTF-8').'</swrc:title>'.$this->LF;
    		}
    	}

    	// 言語
    	$xml .= $this->TAB_SHIFT.'<dc:language>'.$retItem[0]['language'].'</dc:language>'.$this->LF;
    	
    	// キーワード
    	if($retItem[0]['language'] == "ja"){
    		if($retItem[0]['serch_key'] != null){
				$keyword = explode('|',$retItem[0]['serch_key']);
				for($ii=0;$ii<count($keyword);$ii++){
					if ($keyword[$ii] !== RepositoryConst::BLANK_WORD) {
	    				$xml .= $this->TAB_SHIFT.'<swrc:keyword xml:lang="ja">'.htmlspecialchars($keyword[$ii], ENT_QUOTES, 'UTF-8').'</swrc:keyword>'.$this->LF;
					}
    			}
    		}else if($retItem[0]['serch_key_english'] != null){
    			$keyword = explode('|',$retItem[0]['serch_key_english']);
				for($ii=0;$ii<count($keyword);$ii++){
					if ($keyword[$ii] !== RepositoryConst::BLANK_WORD) {
	    				$xml .= $this->TAB_SHIFT.'<swrc:keyword xml:lang="en">'.htmlspecialchars($keyword[$ii], ENT_QUOTES, 'UTF-8').'</swrc:keyword>'.$this->LF;
					}
				}
    		}
    	}else{
    		if($retItem[0]['serch_key_english'] != null){
    			$keyword = explode('|',$retItem[0]['serch_key_english']);
				for($ii=0;$ii<count($keyword);$ii++){
					if ($keyword[$ii] !== RepositoryConst::BLANK_WORD) {
	    				$xml .= $this->TAB_SHIFT.'<swrc:keyword xml:lang="en">'.htmlspecialchars($keyword[$ii], ENT_QUOTES, 'UTF-8').'</swrc:keyword>'.$this->LF;
					}
				}
    		}else if($retItem[0]['serch_key'] != null){
    			$keyword = explode('|',$retItem[0]['serch_key']);
				for($ii=0;$ii<count($keyword);$ii++){
	    			if ($keyword[$ii] !== RepositoryConst::BLANK_WORD) {
	    				$xml .= $this->TAB_SHIFT.'<swrc:keyword xml:lang="ja">'.htmlspecialchars($keyword[$ii], ENT_QUOTES, 'UTF-8').'</swrc:keyword>'.$this->LF;
	    			}
				}
    		}
    	}
    	/*
    	if($retItem[0]['serch_key'] != null){
			//$keyword = str_replace('|', ', ', $retItem[0]['serch_key']);
			$keyword = explode('|',$retItem[0]['serch_key']);
			for($ii=0;$ii<count($keyword);$ii++){
	    		$xml .= $this->TAB_SHIFT.'<swrc:keyword>'.$keyword[$ii].'</swrc:keyword>'.$this->LF;
			}
    	}
		*/
    	//Add multiple language 2009/08/28 K.Ito --end--
    	$spageArray = null;
    	$epageArray = null;
    	
    	// プロパティの出力
    	for($ii=0;$ii<count($retAttr);$ii++){
    		// Fix output hidden metadata 2011/11/28 Y.Nakao --start--
    		if($retAttr[$ii]['hidden'] == '1')
    		{
    			continue;
    		}
    		// Fix output hidden metadata 2011/11/28 Y.Nakao --end--
    		switch($retAttr[$ii]['junii2_mapping']){
    			case 'format':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:howpublished');
    				break;
    			case 'description':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:abstract');
    				break;
    			case 'publisher':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:publisherOf');
    				break;
    			case 'creator':
					$xml .= $this->outputProperty($retAttr[$ii], 'swrc:editor');
    				break;
    			case 'source':
    				// LIDO 2014/06/03 S.Suzuki --start--
					$values = $retAttr[$ii]['value'];
	    			for ($jj = 0; $jj < count($values); $jj++) {
	    				$links = explode("|", $values[$jj]);
	    				for ($kk = 0; $kk < count($links); $kk++) {
		    				if ($links[$kk] === RepositoryConst::BLANK_WORD) {
		    					$links[$kk] = RepositoryOutputFilter::exclusiveReservedWords($links[$kk]);
		    				}
	    				}
	    				$values[$jj] = implode("|", $links);
    				}
					$retAttr[$ii]['value'] = $values;
    				// LIDO 2014/06/03 S.Suzuki --end--
    				
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:source');
    				break;
    			case 'type':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:type');
    				break;
    			case 'jtitle':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:journal');
    				break;
    			case 'volume':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:volume');
    				break;
    			case 'spage':
    				if(count($retAttr[$ii]['value']) > 0){
						$spageArray = $retAttr[$ii]['value'];
    				}
    				break;
    			case 'epage':
    				if(count($retAttr[$ii]['value']) > 0){
	    				$epageArray = $retAttr[$ii]['value'];
    				}
    				break;
    			case 'issue':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:number');
    				break;
    			case 'date':
    			case 'dateofissued':
    				$xml .= $this->outputProperty($retAttr[$ii], 'swrc:date');
    				break;
    			case 'subject':
    			case 'NIIsubject':
    			case 'NDC':
    			case 'NDLC':
    			case 'BSH':
    			case 'NDLSH':
    			case 'MeSH':
    			case 'DDC':
    			case 'LCC':
    			case 'UDC':
    			case 'LCSH':
    				$xml .= $this->outputProperty($retAttr[$ii], 'dc:subject');
    				break;
    			case 'contributor':
    				$xml .= $this->outputProperty($retAttr[$ii], 'dc:contributor');
    				break;
    			case 'identifier':
    			case 'URI':
    			case 'fullTextURL':
    			case 'issn':
    			case 'NCID':
    				$xml .= $this->outputProperty($retAttr[$ii], 'dc:identifier');
    				break;
    			case 'relation':
    			case 'pmid':
    			case 'doi':
    			case 'isVersionOf':
    			case 'hasVersion':
    			case 'isReplacedBy':
    			case 'replaces':
    			case 'isRequiredBy':
    			case 'requires':
    			case 'isPartOf':
    			case 'hasPart':
    			case 'isReferencedBy':
    			case 'references':
    			case 'isFormatOf':
    			case 'hasFormat':
					$xml .= $this->outputProperty($retAttr[$ii], 'dc:relation');
    				break;
    			case 'coverage':
    			case 'spatial':
    			case 'NIIspatial':
    			case 'temporal':
    			case 'NIItemporal':
    				$xml .= $this->outputProperty($retAttr[$ii], 'dc:coverage');
    				break;
    			case 'rights':
    				$xml .= $this->outputProperty($retAttr[$ii], 'dc:rights');
    				break;
    			case 'jtitle,volume,issue,spage,epage,dateofissued':
    		    	// 書誌情報の各データを取得
			        $query = 'SELECT biblio_name, '.
			        		 '		 volume, '.
			        		 //Add name english 2009/08/31 K.Ito --start-- 
			        		 '		 biblio_name_english, '.
			        		 //Add name english 2009/08/31 K.Ito --end--
			        		 '		 issue, '.
			        		 '		 start_page, '.
			        		 '		 end_page, '.
				    	     '		 date_of_issued '.
			        		 'FROM '.DATABASE_PREFIX.'repository_biblio_info '.
			        		 'WHERE item_id = ? '.
			        		 '	AND item_no = ? '.
			        		 '	AND attribute_id = ? '.
			        		 '	AND is_delete = 0;';
			        $params = null;
			        $params[] = $this->itemId;
			        $params[] = $this->itemNo;
			        $params[] = $retAttr[$ii]['attribute_id'];
			    	$retBiblio_info = $this->Db->execute($query, $params);
					if ($retBiblio_info === false) {
						return false;
					}
    			
					// Add LIDO 2014/05/13 S.Suzuki --start--
					for ($jj = 0; $jj < count($retBiblio_info); $jj++) {
						$retBiblio_info[$jj]['biblio_name']         = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['biblio_name']);
						$retBiblio_info[$jj]['volume']              = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['volume']);
						$retBiblio_info[$jj]['biblio_name_english'] = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['biblio_name_english']);
						$retBiblio_info[$jj]['issue']               = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['issue']);
						$retBiblio_info[$jj]['start_page']          = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['start_page']);
						$retBiblio_info[$jj]['end_page']            = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$jj]['end_page']);
					}
			    	// Add LIDO 2014/05/13 S.Suzuki --end--
					
					if(count($retBiblio_info) > 0){
						//Add multiple language 2009/08/31 K.Ito --start--
						if($retItem[0]['language'] == "ja"){
							if($retBiblio_info[0]['biblio_name'] != null){
								$xml .= $this->TAB_SHIFT.'<swrc:journal xml:lang="ja">'.$this->forXmlChange($retBiblio_info[0]['biblio_name']).'</swrc:journal>'.$this->LF;
							}else if($retBiblio_info[0]['biblio_name_english'] != null){
								$xml .= $this->TAB_SHIFT.'<swrc:journal xml:lang="en">'.$this->forXmlChange($retBiblio_info[0]['biblio_name_english']).'</swrc:journal>'.$this->LF;
							}
						}else{
							if($retBiblio_info[0]['biblio_name_english'] != null){
								$xml .= $this->TAB_SHIFT.'<swrc:journal xml:lang="en">'.$this->forXmlChange($retBiblio_info[0]['biblio_name_english']).'</swrc:journal>'.$this->LF;
							}else if($retBiblio_info[0]['biblio_name'] != null){
								$xml .= $this->TAB_SHIFT.'<swrc:journal xml:lang="ja">'.$this->forXmlChange($retBiblio_info[0]['biblio_name']).'</swrc:journal>'.$this->LF;
							}
						}
						//Add multiple language 2009/08/31 K.Ito --end--
						if($retBiblio_info[0]['volume'] != null){
							$xml .= $this->TAB_SHIFT.'<swrc:volume>'.$this->forXmlChange($retBiblio_info[0]['volume']).'</swrc:volume>'.$this->LF;
						}
						if($retBiblio_info[0]['issue'] != null){
							$xml .= $this->TAB_SHIFT.'<swrc:number>'.$this->forXmlChange($retBiblio_info[0]['issue']).'</swrc:number>'.$this->LF;
						}
						if($retBiblio_info[0]['start_page'] != null && $retBiblio_info[0]['end_page'] != null){
							$xml .= $this->TAB_SHIFT.'<swrc:pages>'.$this->forXmlChange($retBiblio_info[0]['start_page'].'-'.$retBiblio_info[0]['end_page']).'</swrc:pages>'.$this->LF;
						}
						if($retBiblio_info[0]['date_of_issued'] != null){
							$xml .= $this->TAB_SHIFT.'<swrc:date>'.$this->forXmlChange($retBiblio_info[0]['date_of_issued']).'</swrc:date>'.$this->LF;
						}
					}
    				break;
    			default:
    		}
    		if($spageArray != null && $epageArray != null){
    			for($jj=0;$jj<count($spageArray);$jj++){
    				$pages = null;
    				if($spageArray[$jj] != null && $epageArray[$jj] != null){
    					$pages = $spageArray[$jj].'-'.$epageArray[$jj];
	    				$xml .= $this->TAB_SHIFT.'<swrc:pages>'.$pages.'</swrc:pages>'.$this->LF;
    				}
    			}
    		}
    	}
    	
    	$xml .= '</rdf:Description>'.$this->LF;
    	$xml .= '</rdf:RDF>';
    	return $xml;
	}

	function outputProperty ($itemData, $propertyName) {
		$rtnXml = '';
		if($itemData['input_type'] == 'name'){
    		$outputValue = $this->getName($itemData['attribute_id']);
    	}else{
            if(array_key_exists('value', $itemData))
            {
                $outputValue = $itemData['value'];
            }
            else
            {
                $outputValue = array();
            }
    	}
		if(count($outputValue) > 0){
			//Add multiple language 2009/08/31 K.Ito --start--
			if($itemData['display_lang_type'] == ""){
				for($ii=0;$ii<count($outputValue);$ii++){
			    	$rtnXml .= $this->TAB_SHIFT.'<'.$propertyName.'>';
				    $rtnXml .= $this->forXmlChange($outputValue[$ii]);
			    	$rtnXml .= '</'.$propertyName.'>'.$this->LF;
				}
			}else if($itemData['display_lang_type'] == "japanese"){
				for($ii=0;$ii<count($outputValue);$ii++){
			    	$rtnXml .= $this->TAB_SHIFT.'<'.$propertyName.' xml:lang="ja">';
				    $rtnXml .= $this->forXmlChange($outputValue[$ii]);
			    	$rtnXml .= '</'.$propertyName.'>'.$this->LF;
				}
			}else{
				for($ii=0;$ii<count($outputValue);$ii++){
			    	$rtnXml .= $this->TAB_SHIFT.'<'.$propertyName.' xml:lang="en">';
				    $rtnXml .= $this->forXmlChange($outputValue[$ii]);
			    	$rtnXml .= '</'.$propertyName.'>'.$this->LF;
				}
			}
			//Add multiple language 2009/08/31 K.Ito --end--
		}
	    return $rtnXml;
	}
	
	function getName ($attribute_id) {
		// 氏名を取得
        $query = 'SELECT family, '.
    			 '		 name '.
    			 'FROM '.DATABASE_PREFIX.'repository_personal_name '.
				 'WHERE item_id = ? '.
    			 '  AND item_no = ? '.
    			 '  AND attribute_id = ? '.
    			 '  AND is_delete = 0;';
	    $params = null;
		$params[] = $this->itemId;
		$params[] = $this->itemNo;
		$params[] = $attribute_id;
    	$retName = $this->Db->execute($query, $params);
		if ($retName === false) {
			return false;
		}
		
		// Add LIDO 2014/05/20 S.Suzuki --start--
		for ($ii = 0; $ii < count($retName); $ii++) {
			$retName[$ii]['family'] = RepositoryOutputFilter::exclusiveReservedWords($retName[$ii]['family']);
			$retName[$ii]['name']   = RepositoryOutputFilter::exclusiveReservedWords($retName[$ii]['name']);
		}
    	// Add LIDO 2014/05/20 S.Suzuki --end--
		
		// 氏名を連結
		$name_array = array();
    	for($ii=0;$ii<count($retName);$ii++){
    		if ($retName[$ii]['family'] != '' || $retName[$ii]['name'] != '') {
    			array_push($name_array, $retName[$ii]['family'].' '.$retName[$ii]['name']);
    		}
    	}
		
		return $name_array;
	}
}
?>
