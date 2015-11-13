<?php
// --------------------------------------------------------------------
//
// $Id: Bibtex.class.php 48455 2015-02-16 10:53:40Z atsushi_suzuki $
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
class Repository_Bibtex extends RepositoryAction
{
	// リクエストパラメータを受け取るため
	var $verb = null;
	var $itemId = null;
	var $itemNo = null;
	
	// ダウンロード用メンバ
	var $uploadsView = null;
	
	// 改行
	var $LF = "\n";
	// タブシフト
	var $TAB_SHIFT = "\t";

	// 出力文字列
	var $feed = '';
	
	// エラーメッセージ
	var $errorMsg = "";
	
	// グローバル
	var $bibtex_fields = array();
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// ヘッダ出力
    	// header("Content-Type: text/plain; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/plain")
    	
    	// 初期処理
    	$this->initAction();
    	
		// フィード文字列取得
		$feed = $this->outputBibtex();
    	
    	// 取得結果がfalseでなければ
    	if ( $feed != false ) {
    		// ヘッダ出力
    		header("Content-Type: text/plain; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/plain")
	    	// フィード出力
			print $feed;
       	}else{
       		// ヘッダ出力
    		header("Content-Type: text/html; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/html")
       		// エラー出力
       		print $this->errorMsg;
       	}
		
		// テキスト書き出し終了後にexit関数を呼び出す
    	exit();

    }
	
    function outputBibtex()
    {
    	// アイテム情報の取得
    	$query = 'SELECT ITEMTYPE.mapping_info, '.
    			 '		 ITEM.title, '.
    			 '		 ITEM.title_english, '.
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
			$this->errorMsg = 'Database access error.';
			return false;
		}
		
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
    	
    	// Add check item public status 2010/01/12 Y.Nakao --start--
        // Add tree access control list 2012/03/07 T.Koyasu -start-
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
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        if(!$itemAuthorityManager->checkItemPublicFlg($this->itemId, $this->itemNo, $this->repository_admin_base, $this->repository_admin_room)){
			// item close
			$retItem[0]['shown_status'] = "0";
		}
		// Mod OpenDepo 2014/01/31 S.Arata --end--
		// Add check item public status 2010/01/12 Y.Nakao --end--

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
    			//Add display lang type 2009/09/01 K.Ito --start--
    			 '		 display_lang_type '.
    			//Add display lang type 2009/09/01 K.Ito --end--
    			 'FROM '.DATABASE_PREFIX.'repository_item_attr_type '.
				 'WHERE item_type_id = ? '.
    			 '	AND is_delete = 0 '.
    			 'order by show_order;';
	    $params = null;
	    $params[] = $retItem[0]['item_type_id'];
    	$retAttr = $this->Db->execute($query, $params);
		if ($retAttr === false) {
			$this->errorMsg = 'Database access error.';
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
			$this->errorMsg = 'Database access error.';
			return false;
		}
    	
    	// Add LIDO 2014/05/09 S.Suzuki --start--
    	for ($ii = 0; $ii < count($retAttrValue); $ii++) {
    		$retAttrValue[$ii]['attribute_value'] = RepositoryOutputFilter::exclusiveReservedWords($retAttrValue[$ii]['attribute_value']);
    	}
		// Add LIDO 2014/05/09 S.Suzuki --end--
    	
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
		
    	// 書誌情報を持つ場合、各データを取得
        $query = 'SELECT BIBLIO_INFO.biblio_name, '.
        		 '		 BIBLIO_INFO.volume, '.
        		 '		 BIBLIO_INFO.biblio_name_english, '.
        		 '		 BIBLIO_INFO.issue, '.
        		 '		 BIBLIO_INFO.start_page, '.
        		 '		 BIBLIO_INFO.end_page, '.
	    	     '		 BIBLIO_INFO.date_of_issued '.
        		 'FROM '.DATABASE_PREFIX.'repository_biblio_info BIBLIO_INFO, '.
        		 '	   '.DATABASE_PREFIX.'repository_item_attr_type ATTRTYPE '.
        		 'WHERE ATTRTYPE.input_type = "biblio_info" '.
        		 '	AND ATTRTYPE.item_type_id = BIBLIO_INFO.item_type_id '.
        		 '	AND ATTRTYPE.attribute_id = BIBLIO_INFO.attribute_id '.
        		 '	AND ATTRTYPE.item_type_id = ? '.
        		 '	AND BIBLIO_INFO.item_id = ? '.
        		 '	AND BIBLIO_INFO.item_no = ? '.
        		 '	AND ATTRTYPE.is_delete = 0 '.
        		 // Fix output hidden metadata 2011/11/28 Y.Nakao --start--
        		 '	AND ATTRTYPE.hidden = 0 '.
        		 // Fix output hidden metadata 2011/11/28 Y.Nakao --end--
        		 '	AND BIBLIO_INFO.is_delete = 0;';
        $params = null;
        $params[] = $retItem[0]['item_type_id'];
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
    	$retBiblio_info = $this->Db->execute($query, $params);
		if ($retBiblio_info === false) {
			$this->errorMsg = 'Database access error.';
			return false;
		}
    	
    	// Add LIDO 2014/05/09 S.Suzuki --start--
    	for ($ii = 0; $ii < count($retBiblio_info); $ii++) {
    		$retBiblio_info[$ii]['biblio_name']         = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['biblio_name']);
    		$retBiblio_info[$ii]['volume']              = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['volume']);
    		$retBiblio_info[$ii]['biblio_name_english'] = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['biblio_name_english']);
    		$retBiblio_info[$ii]['issue']               = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['issue']);
    		$retBiblio_info[$ii]['start_page']          = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['start_page']);
    		$retBiblio_info[$ii]['end_page']            = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['end_page']);
    		$retBiblio_info[$ii]['date_of_issued']      = RepositoryOutputFilter::exclusiveReservedWords($retBiblio_info[$ii]['date_of_issued']);
    	}
		// Add LIDO 2014/05/09 S.Suzuki --end--
    	
		// 書誌情報に含まれるデータを加工
		if(count($retBiblio_info) > 0){
	    	// ページ数の連結
	    	$biblio_info_pages = null;
	    	if($retBiblio_info[0]['start_page'] != null && $retBiblio_info[0]['end_page'] != null){
				$biblio_info_pages = $retBiblio_info[0]['start_page'].'--'.$retBiblio_info[0]['end_page'];
	    	}
			
	    	// 発行年月の取得
	    	$biblio_info_year = null;
	    	$biblio_info_month = null;
	    	if($retBiblio_info[0]['date_of_issued'] != null){
	    		$split_date = split("-", $retBiblio_info[0]['date_of_issued']);
	    		$biblio_info_year = $split_date[0];
	    		if($split_date[1] != null){
	    			switch($split_date[1]){
	    				case '01':
	    					$biblio_info_month = 'jan';
	    					break;
	    				case '02':
	    					$biblio_info_month = 'feb';
	    					break;
	    				case '03':
	    					$biblio_info_month = 'mar';
	    					break;
	    				case '04':
	    					$biblio_info_month = 'apr';
	    					break;
	    				case '05':
	    					$biblio_info_month = 'may';
	    					break;
	    				case '06':
	    					$biblio_info_month = 'jun';
	    					break;
	    				case '07':
	    					$biblio_info_month = 'jul';
	    					break;
	    				case '08':
	    					$biblio_info_month = 'aug';
	    					break;
	    				case '09':
	    					$biblio_info_month = 'sep';
	    					break;
	    				case '10':
	    					$biblio_info_month = 'oct';
	    					break;
	    				case '11':
	    					$biblio_info_month = 'nov';
	    					break;
	    				case '12':
	    					$biblio_info_month = 'dec';
	    					break;
	    				default:
	    					$biblio_info_month = $split_date[1];
	    			}
	    		}
	    	}
		}
		
		// 出力データ作成
		//Add multiple language 2009/09/02 K.Ito --start--
		if($this->Session->getParameter("_lang") == "japanese"){
			if($retItem[0]['title'] != ""){
				$this->bibtex_fields['title'] = $retItem[0]['title'];
			}else if($retItem[0]['title_english'] != ""){
				$this->bibtex_fields['title'] = $retItem[0]['title_english'];
			}
			if($retBiblio_info[0]['biblio_name'] != ""){
				$this->bibtex_fields['booktitle'] = $retBiblio_info[0]['biblio_name'];
				$this->bibtex_fields['journal'] = $retBiblio_info[0]['biblio_name'];
			}else if($retBiblio_info[0]['biblio_name_english'] != ""){
				$this->bibtex_fields['booktitle'] = $retBiblio_info[0]['biblio_name_english'];
				$this->bibtex_fields['journal'] = $retBiblio_info[0]['biblio_name_english'];
			}
		}else{
			if($retItem[0]['title_english'] != ""){
				$this->bibtex_fields['title'] = $retItem[0]['title_english'];
			}else if($retItem[0]['title'] != ""){
				$this->bibtex_fields['title'] = $retItem[0]['title'];
			}
			if($retBiblio_info[0]['biblio_name_english'] != ""){
				$this->bibtex_fields['booktitle'] = $retBiblio_info[0]['biblio_name_english'];
				$this->bibtex_fields['journal'] = $retBiblio_info[0]['biblio_name_english'];
			}else if($retBiblio_info[0]['biblio_name'] != ""){
				$this->bibtex_fields['booktitle'] = $retBiblio_info[0]['biblio_name'];
				$this->bibtex_fields['journal'] = $retBiblio_info[0]['biblio_name'];
			}
		}
		//Add multiple language 2009/09/02 K.Ito --end--
		//$this->bibtex_fields['title'] = $retItem[0]['title'];
		//$this->bibtex_fields['booktitle'] = $retBiblio_info[0]['biblio_name'];
		//$this->bibtex_fields['journal'] = $retBiblio_info[0]['biblio_name'];
		$this->bibtex_fields['volume'] = $retBiblio_info[0]['volume'];
		$this->bibtex_fields['number'] = $retBiblio_info[0]['issue'];
		$this->bibtex_fields['pages'] = $biblio_info_pages;
		$this->bibtex_fields['month'] = $biblio_info_month;
		$this->bibtex_fields['year'] = $biblio_info_year;
    	//print_r($retAttr);
    	
    	// init
    	$author_sub = null;
    	$jtitle_sub = null;
    	$publisher_sub = null;
    	$contributor_sub = null;
    	
    	
			for($ii=0;$ii<count($retAttr);$ii++){//print_r($this->bibtex_fields);
			// Fix output hidden metadata 2011/11/28 Y.Nakao --start--
			if($retAttr[$ii]['hidden'] == '1')
			{
				continue;
			}
			// Fix output hidden metadata 2011/11/28 Y.Nakao --end--
				switch($retAttr[$ii]['junii2_mapping']){
				case 'creator':
					//Add multiple language for creator 2009/09/02 K.Ito --start--
					if(!isset($this->bibtex_fields['author'])){
						if($retAttr[$ii]['input_type'] == 'name'){
					    	// Authorを取得
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
							$params[] = $retAttr[$ii]['attribute_id'];
					    	$retName = $this->Db->execute($query, $params);
							if ($retName === false) {
								$this->errorMsg = 'Database access error.';
								return false;
							}
							
							// Add LIDO 2014/05/22 S.Suzuki --start--
					    	for ($jj = 0; $jj < count($retName); $jj++) {
					    		$retName[$jj]['family'] = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['family']);
					    		$retName[$jj]['name']   = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['name']);
					    	}
							
							//一時保存用Name初期化
							$Name = "";
					
					    	// 氏名を連結
					    	for($jj=0;$jj<count($retName);$jj++){
					    		if($jj != 0){
					    			$Name .= ' and ';
					    		}
					    		if ($retName[$jj]['family'] !== '' && $retName[$jj]['name'] !== '') {
					    			$Name .= $retName[$jj]['family'].','.$retName[$jj]['name'];
					    		}
					    		else if ($retName[$jj]['family'] !== '') {
					    			$Name .= $retName[$jj]['family'];
					    		}
					    		else {
					    			$Name .= $retName[$jj]['name'];
					    		}
					    	}
							// Add LIDO 2014/05/22 S.Suzuki --end--
							
					    	//氏名を格納
							if($this->Session->getParameter("_lang") == "japanese"){
								if($retAttr[$ii]['display_lang_type'] == "japanese" || $retAttr[$ii]['display_lang_type'] == ""){
									if(!isset($this->bibtex_fields['author'])){
					    				$this->bibtex_fields['author'] = $Name;
									}
									else{
										$this->bibtex_fields['author'] .= $Name;
									}
								}else{
									if($author_sub == null){
										$author_sub = $Name;
									}
								}
					    	}else{
					    		if($retAttr[$ii]['display_lang_type'] == "english" || $retAttr[$ii]['display_lang_type'] == ""){
					    			$this->bibtex_fields['author'] .= $Name;
					    		}else{
					    			if($author_sub == null){
					    				$author_sub = $Name;
					    			}
					    		}	
					    	}
						}else{
							//$Name初期化
							$Name = "";
							for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
								if($jj != 0){
					    			$Name .= ' and ';
					    		}
					    		$Name .= $retAttr[$ii]['value'][$jj];					
							}
							//氏名を格納
							if($this->Session->getParameter("_lang") == "japanese"){
								if($retAttr[$ii]['display_lang_type'] == "japanese" || $retAttr[$ii]['display_lang_type'] == ""){
					    			$this->bibtex_fields['author'] .= $Name;
								}else{
									//上書きはしない。最初のsubを保持する
									if($author_sub == null){
										$author_sub = $Name;
									}
								}
					    	}else{
					    		if($retAttr[$ii]['display_lang_type'] == "english" || $retAttr[$ii]['display_lang_type'] == ""){
					    			$this->bibtex_fields['author'] .= $Name;
					    		}else{
					    			if($author_sub == null){
					    				$author_sub = $Name;
					    			}
					    		}	
					    	}
						}
					}
					//Add multiple language for creator 2009/09/02 K.Ito --end--
					break;
					
				case 'jtitle':
					//Add multiple language 2009/09/02 K.Ito --start--
					if($this->bibtex_fields['booktitle'] == null && $this->bibtex_fields['journal'] == null){
						if($this->Session->getParameter("_lang") == "japanese"){
							if($retAttr[$ii]['display_lang_type'] == "japanese" || $retAttr[$ii]['display_lang_type'] == ""){
								for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
									if($jj != 0){
										$this->bibtex_fields['booktitle'] .= ', ';
										$this->bibtex_fields['journal'] .= ', ';
									}
									$this->bibtex_fields['booktitle'] .= $retAttr[$ii]['value'][$jj];
									$this->bibtex_fields['journal'] .= $retAttr[$ii]['value'][$jj];							
								}
							}else{
								if($jtitle_sub == null){
									for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
										if($jj != 0){
											$jtitle_sub .= ', ';
										}
										$jtitle_sub .= $retAttr[$ii]['value'][$jj];						
									}
								}
							}
						}else{
							if($retAttr[$ii]['display_lang_type'] == "english" || $retAttr[$ii]['display_lang_type'] == ""){
								for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
									if($jj != 0){
										$this->bibtex_fields['booktitle'] .= ', ';
										$this->bibtex_fields['journal'] .= ', ';
									}
									$this->bibtex_fields['booktitle'] .= $retAttr[$ii]['value'][$jj];
									$this->bibtex_fields['journal'] .= $retAttr[$ii]['value'][$jj];							
								}
							}else{
								if($jtitle_sub == null){
									for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
										if($jj != 0){
											$jtitle_sub .= ', ';
										}
										$jtitle_sub .= $retAttr[$ii]['value'][$jj];	
									}						
								}
							}
						}
					}
					//Add multiple language 2009/09/02 K.Ito --end--
					break;
				
				case 'volume':
					if(!isset($this->bibtex_fields['volume']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							if($jj != 0){
								$this->bibtex_fields['volume'] .= ', ';
							}
							$this->bibtex_fields['volume'] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
					
				case 'issue':
					if(!isset($this->bibtex_fields['number']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							if($jj != 0){
								$this->bibtex_fields['number'] .= ', ';
							}
							$this->bibtex_fields['number'] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
					
				case 'spage':
					if(!isset($this->bibtex_fields['spage']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							$this->bibtex_fields['spage'][$jj] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
					
				case 'epage':
					if(!isset($this->bibtex_fields['epage']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							$this->bibtex_fields['epage'][$jj] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
					
				case 'publisher':
					if(!isset($this->bibtex_fields['publisher'])){
						//Add multiple language 2009/09/03 K.Ito --start--
						//$pub 初期化;
						$pub = null;
							if($retAttr[$ii]['input_type'] == 'name'){
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
								$params[] = $retAttr[$ii]['attribute_id'];
						    	$retName = $this->Db->execute($query, $params);
								if ($retName === false) {
									$this->errorMsg = 'Database access error.';
									return false;
								}
								
								// Add LIDO 2014/05/22 S.Suzuki --start--
						    	$tmp_name = array();
								
								for ($jj = 0; $jj < count($retName); $jj++) {
						    		$retName[$jj]['family'] = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['family']);
						    		$retName[$jj]['name']   = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['name']);
						    		if ($retName[$jj]['family'] !== '' || $retName[$jj]['name'] !== '') {
						    			array_push($tmp_name, $retName[$jj]);
						    		}
						    	}
								
						    	for($jj=0;$jj<count($tmp_name);$jj++){
						    		if($jj != 0){
						    			$pub .= ' and ';
						    		}
						    		if ($tmp_name[$jj]['family'] !== '' && $tmp_name[$jj]['name'] !== '') {
						    			$pub .= $tmp_name[$jj]['family'].','.$tmp_name[$jj]['name'];
						    		}
						    		else if ($tmp_name[$jj]['family'] !== '') {
					    				$pub .= $tmp_name[$jj]['family'];
						    		}
						    		else {
						    			$pub .= $tmp_name[$jj]['name'];
						    		}
						    	}
								// Add LIDO 2014/05/22 S.Suzuki --end--
							}else{
								if(isset($retAttr[$ii]['value'])){
									for ($jj = 0; $jj < count($retAttr[$ii]['value']); $jj++) {
										if($jj != 0){
											$pub .= ', ';
										}
										$pub .= $retAttr[$ii]['value'][$jj];
									}
								}
							}
						//最後に$pub格納
						if($this->Session->getParameter("_lang") == "japanese"){
							if($retAttr[$ii]['display_lang_type'] == "japanese" || $retAttr[$ii]['display_lang_type'] == ""){
								$this->bibtex_fields['publisher'] = $pub;		
							}else{
								if($publisher_sub == null){
									$publisher_sub = $pub;
								}
							}
						}else{
							if($retAttr[$ii]['display_lang_type'] == "english" || $retAttr[$ii]['display_lang_type'] == ""){
								$this->bibtex_fields['publisher'] = $pub;
							}else{
								if($publisher_sub == null){
									$publisher_sub = $pub;
								}
							}
						}
						//Add multiple language 2009/09/03 K.Ito --end--
					}
					break;
					
				case 'format':
					if(!isset($this->bibtex_fields['howpublished']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							if($jj != 0){
								$this->bibtex_fields['howpublished'] .= ', ';
							}
							$this->bibtex_fields['howpublished'] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
					
				case 'contributor':
					if(!isset($this->bibtex_fields['institution'])){
						//Add multiple language 2009/09/03 K.Ito --start--
						//con初期化
						$con = null;
							if($retAttr[$ii]['input_type'] == 'name'){
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
								$params[] = $retAttr[$ii]['attribute_id'];
						    	$retName = $this->Db->execute($query, $params);
								if ($retName === false) {
									$this->errorMsg = 'Database access error.';
									return false;
								}
								
								// Add LIDO 2014/05/22 S.Suzuki --start--
								for ($jj = 0; $jj < count($retName); $jj++) {
						    		$retName[$jj]['family'] = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['family']);
						    		$retName[$jj]['name']   = RepositoryOutputFilter::exclusiveReservedWords($retName[$jj]['name']);
						    	}
								
						    	for($jj=0;$jj<count($retName);$jj++){
						    		if($jj != 0){
						    			$con .= ' and ';
						    		}
						    		if ($retName[$jj]['family'] !== '' && $retName[$jj]['name'] !== '') {
						    			$con .= $retName[$jj]['family'].','.$retName[$jj]['name'];
						    		}
						    		else if ($retName[$jj]['family'] !== '') {
						    			$con .= $retName[$jj]['family'];
						    		}
						    		else {
						    			$con .= $retName[$jj]['name'];
						    		}
						    	}
								// Add LIDO 2014/05/22 S.Suzuki --end--
							}else{
								if(isset($retAttr[$ii]['value'])){
									for ($jj = 0; $jj < count($retAttr[$ii]['value']); $jj++) {
										if($jj != 0){
											$con .= ', ';
										}
										$con .= $retAttr[$ii]['value'][$jj];
									}
								}
							}
						//最後に$pub格納
						if($this->Session->getParameter("_lang") == "japanese"){
							if($retAttr[$ii]['display_lang_type'] == "japanese" || $retAttr[$ii]['display_lang_type'] == ""){
								$this->bibtex_fields['institution'] = $con;		
							}else{
								if($contributor_sub == null){
									$contributor_sub = $con;
								}
							}
						}else{
							if($retAttr[$ii]['display_lang_type'] == "english" || $retAttr[$ii]['display_lang_type'] == ""){
								$this->bibtex_fields['institution'] = $con;
							}else{
								if($contributor_sub == null){
									$contributor_sub = $con;
								}
							}
						}
						//Add multiple language 2009/09/03 K.Ito --start--
					}
					break;
					
				case 'type':
					if(!isset($this->bibtex_fields['type']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							if($jj != 0){
								$this->bibtex_fields['type'] .= ', ';
							}
							$this->bibtex_fields['type'] .= $retAttr[$ii]['value'][$jj];					
						}
					}
					break;
				
				case 'dateofissued':
					if(!isset($this->bibtex_fields['year']) && isset($retAttr[$ii]['value'])){
						for($jj=0;$jj<count($retAttr[$ii]['value']);$jj++){
							if($jj != 0){
								$this->bibtex_fields['year'] .= ', ';
							}
							$split_date = split("-", $retAttr[$ii]['value'][$jj]);
				    		$this->bibtex_fields['year'] .= $split_date[0];
				    		if($split_date[1] != null){
					    		if($jj != 0){
									$this->bibtex_fields['month'] .= ', ';
								}
				    			switch($split_date[1]){
				    				case '01':
				    					$this->bibtex_fields['month'] .= 'jan';
				    					break;
				    				case '02':
				    					$this->bibtex_fields['month'] .= 'feb';
				    					break;
				    				case '03':
				    					$this->bibtex_fields['month'] .= 'mar';
				    					break;
				    				case '04':
				    					$this->bibtex_fields['month'] .= 'apr';
				    					break;
				    				case '05':
				    					$this->bibtex_fields['month'] .= 'may';
				    					break;
				    				case '06':
				    					$this->bibtex_fields['month'] .= 'jun';
				    					break;
				    				case '07':
				    					$this->bibtex_fields['month'] .= 'jul';
				    					break;
				    				case '08':
				    					$this->bibtex_fields['month'] .= 'aug';
				    					break;
				    				case '09':
				    					$this->bibtex_fields['month'] .= 'sep';
				    					break;
				    				case '10':
				    					$this->bibtex_fields['month'] .= 'oct';
				    					break;
				    				case '11':
				    					$this->bibtex_fields['month'] .= 'nov';
				    					break;
				    				case '12':
				    					$this->bibtex_fields['month'] .= 'dec';
				    					break;
				    				default:
				    					$this->bibtex_fields['month'] .= $split_date[1];
				    			}
				    		}
						}
					}
					break;
				default:
			}
		}
		
		// ページのデータがなかった場合、作成
		if(!isset($this->bibtex_fields['pages']) && isset($this->bibtex_fields['spage']) && isset($this->bibtex_fields['epage'])){
			for($ii=0;$ii<count($this->bibtex_fields['spage']);$ii++){
				if($this->bibtex_fields['spage'][$ii] != null && $this->bibtex_fields['epage'][$ii] != null){
					if(isset($this->bibtex_fields['pages'])){
						$this->bibtex_fields['pages'] .= ', ';
					}
					$this->bibtex_fields['pages'] .= $this->bibtex_fields['spage'][$ii].'--'.$this->bibtex_fields['epage'][$ii];
				}
			}
		}
		
		//Add multiple language 2009/09/03 K.Ito --start--
		//authorがなかった場合
		if( empty($this->bibtex_fields['author']) ){
			$this->bibtex_fields['author'] = $author_sub;
		}
		
		//jtitleがなかった場合
		if( empty($this->bibtex_fields['booktitle']) ){
			$this->bibtex_fields['booktitle'] = $jtitle_sub;
			$this->bibtex_fields['journal'] =  $jtitle_sub;
		}
		
		//publisherがなかった場合
    	if( empty($this->bibtex_fields['publisher']) ){
			$this->bibtex_fields['publisher'] = $publisher_sub;
		}
		
		//contributorがなかった場合
    	if( empty($this->bibtex_fields['institution']) ){
			$this->bibtex_fields['institution'] = $contributor_sub;
		}
		//Add multiple language 2009/09/03 K.Ito --end--

		
    	$feed = null;
    	
    	switch($retItem[0]['mapping_info']) {
    		case 'Journal Article':
    		case 'Departmental Bulletin Paper':
    		case 'Article':
    			if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['journal'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getArticle();
    			} else {
    				$feed = $this->getMisc();
    			}		
    			break;

    		case 'Conference Paper':
    			if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['booktitle'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getInproceedings();
    			} else {
    				$feed = $this->getMisc();
    			}
    			break;
    			
    		case 'Presentation':
    		case 'Preprint':
    			if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['note'])){	
    				$feed = $this->getUnpublished();
    			} else {
    				$feed = $this->getMisc();
    			}
    			break;
    			
    		case 'Book':
    			// incollection
    			if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['booktitle'])
    			&& isset($this->bibtex_fields['publisher'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getIncollection();
    				
    			// inbook
    			} else if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['pages'])
    			&& isset($this->bibtex_fields['publisher'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getInbook();
    				
    			// book
    			} else if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['publisher'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getBook();
    			// booklet
    			} else if(isset($this->bibtex_fields['publisher'])
    			&& isset($this->bibtex_fields['title'])){
    				$feed = $this->getBooklet();
    			} else {
    				$feed = $this->getMisc();
    			}
    			break;
    			
    		case 'Technical Report':
    		case 'Research Paper':
    			if(isset($this->bibtex_fields['title'])
    			&& isset($this->bibtex_fields['author'])
    			&& isset($this->bibtex_fields['institution'])
    			&& isset($this->bibtex_fields['year'])){
    				$feed = $this->getTechreport();
    			} else {
    				$feed = $this->getMisc();
    			}
    			break;
 
    		// schoolフィールドにリマップできないため、
    		// "Thesis or Dissertation"はmiscにリマップ
    		case 'Thesis or Dissertation':
    		case 'Learning Material':
    		case 'Data or Dataset':
    		case 'Software':
    		case 'Others':
    			$feed = $this->getMisc();
    			break;
    		default:
    			$feed = $this->getMisc();
    	}
    	return $feed;
    }
    
    function getArticle(){
    	// 文献種類、引用キー
    	$feed = '@article{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// journal
		$feed .= '   journal'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['journal'].'",'.$this->LF;
		
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	
    	// volume(OPT)
    	if(isset($this->bibtex_fields['volume'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   volume'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['volume'].'"';
    	}
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// pages(OPT)
    	if(isset($this->bibtex_fields['pages'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   pages'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['pages'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }

    function getInproceedings(){
    	// 文献種類、引用キー
    	$feed = '@inproceedings{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// booktitle
    	$feed .= '   booktitle'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['booktitle'].'",'.$this->LF;
    	
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	
    	// volume(OPT)
    	if(isset($this->bibtex_fields['volume'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   volume'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['volume'].'"';
    	}
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// pages(OPT)
    	if(isset($this->bibtex_fields['pages'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   pages'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['pages'].'"';
    	}
    	// publisher(OPT)
    	if(isset($this->bibtex_fields['publisher'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   publisher'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['publisher'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	} 	
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }
    
    function getUnpublished(){
    	// 文献種類、引用キー
    	$feed = '@unpublished{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// note
    	$feed .= '   note '.$this->TAB_SHIFT.' = "';
    	for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    		if($ii != 0){
    			$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    		}
    		$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    	}
    	$feed .= '"';
    	
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// year(OPT)
    	if(isset($this->bibtex_fields['year'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	}
		$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }

    function getTechreport(){
    	// 文献種類、引用キー
    	$feed = '@techreport{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'",'.$this->LF;
    	
    	// institution
    	$feed .= '   institution'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['institution'].'"';
    	
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	// type(OPT)
    	if(isset($this->bibtex_fields['type'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   type '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['type'].'"';
    	}
		$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }
    
    function getMisc(){
    	// 文献種類、引用キー
    	$feed = '@misc{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author(OPT)
    	if(isset($this->bibtex_fields['author'])){
    		$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'"';
    	}
    	
    	// title(OPT)
    	if(isset($this->bibtex_fields['title'])){
    		if($this->bibtex_fields['author'] != null){
    			$feed .= ','.$this->LF;
    		}
    		$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'"';
    	}
    	// howpublished(OPT)
    	if(isset($this->bibtex_fields['howpublished'])){
    		if($this->bibtex_fields['author'] != null 
    		|| $this->bibtex_fields['title']){
    			$feed .= ','.$this->LF;
    		}
    		$feed .= '   howpublished'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['howpublished'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		if($this->bibtex_fields['author'] != null 
    		|| $this->bibtex_fields['title'] 
    		|| $this->bibtex_fields['howpublished']){
    			$feed .= ','.$this->LF;
    		}
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// year(OPT)
    	if(isset($this->bibtex_fields['year'])){
    		if($this->bibtex_fields['author'] != null 
    		|| $this->bibtex_fields['title'] 
    		|| $this->bibtex_fields['howpublished']
    		|| $this->bibtex_fields['month']){
    			$feed .= ','.$this->LF;
    		}
    		$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		if($this->bibtex_fields['author'] != null 
    		|| $this->bibtex_fields['title'] 
    		|| $this->bibtex_fields['howpublished']
    		|| $this->bibtex_fields['month']
    		|| $this->bibtex_fields['year']){
    			$feed .= ','.$this->LF;
    		}
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }

    function getBook(){
    	// 文献種類、引用キー
    	$feed = '@book{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	    	
    	// publisher
    	$feed .= '   publisher'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['publisher'].'",'.$this->LF;
    	
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	
    	// volume(OPT)
    	if(isset($this->bibtex_fields['volume'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   volume'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['volume'].'"';
    	}
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }

    function getInbook(){
    	// 文献種類、引用キー
    	$feed = '@inbook{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// pages
    	$feed .= '   pages'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['pages'].'",'.$this->LF;
    	
    	// publisher
    	$feed .= '   publisher'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['publisher'].'",'.$this->LF;
    	
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	
    	// volume(OPT)
    	if(isset($this->bibtex_fields['volume'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   volume'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['volume'].'"';
    	}
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }

    function getBooklet(){
    	// 文献種類、引用キー
    	$feed = '@booklet{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'"';
    	
    	// author(OPT)
    	if(isset($this->bibtex_fields['author'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'"';
    	}
    	// howpublished(OPT)
    	if(isset($this->bibtex_fields['howpublished'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   howpublished'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['howpublished'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// year(OPT)
    	if(isset($this->bibtex_fields['year'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }
    
    function getIncollection(){
    	// 文献種類、引用キー
    	$feed = '@incollection{weko_'.$this->itemId.'_'.$this->itemNo.','.$this->LF;
    	
    	// author
    	$feed .= '   author'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['author'].'",'.$this->LF;
    	
    	// title
    	$feed .= '   title'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['title'].'",'.$this->LF;
    	
    	// booktitle
    	$feed .= '   booktitle'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['booktitle'].'",'.$this->LF;
    	
    	// publisher
    	$feed .= '   publisher'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['publisher'].'",'.$this->LF;
    	
    	// year
    	$feed .= '   year '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['year'].'"';
    	
    	// volume(OPT)
    	if(isset($this->bibtex_fields['volume'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   volume'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['volume'].'"';
    	}
    	// number(OPT)
    	if(isset($this->bibtex_fields['number'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   number'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['number'].'"';
    	}
    	// pages(OPT)
    	if(isset($this->bibtex_fields['pages'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   pages'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['pages'].'"';
    	}
    	// month(OPT)
    	if(isset($this->bibtex_fields['month'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   month'.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['month'].'"';
    	}
    	// note(OPT)
    	if(isset($this->bibtex_fields['note'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   note '.$this->TAB_SHIFT.' = "';
    		for($ii=0;$ii<count($this->bibtex_fields['note']);$ii++){
    			if($ii != 0){
    				$feed .= ','.$this->LF.$this->TAB_SHIFT.$this->TAB_SHIFT.'    ';
    			}
    			$feed .= $this->bibtex_fields['note'][$ii]['attribute_name'].':'.$this->bibtex_fields['note'][$ii]['attribute_value'];
    		}
    		$feed .= '"';
    	}
    	// type(OPT)
    	if(isset($this->bibtex_fields['type'])){
    		$feed .= ','.$this->LF;
    		$feed .= '   type '.$this->TAB_SHIFT.' = "'.$this->bibtex_fields['type'].'"';
    	}
    	$feed .= $this->LF.'}'.$this->LF;
    	return $feed;
    }
}
?>
