<?php
// --------------------------------------------------------------------
//
// $Id: Oaiore.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Oaiore extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	//var $session = null;
	var $db = null;
	
	
	// リクエストパラメータを受け取るため
	var $itemId = null;
	var $itemNo = null;
	var $indexId = null;
	
	// ダウンロード用メンバ
	var $uploadsView = null;
	
	// 改行
	var $LF = "\n";
	// タブシフト
	var $TAB_SHIFT = "\t";

	// .atomへのURI
	var $atmUri = '';
	
	// サーバ名
	var $server_name = "";
	
	// 公開インデックス取得用インスタンス
	// Add OpenDepo 2014/01/31 S.Arata --start--
	var $indexAuthorityManager = null;
	// Add OpenDepo 2014/01/31 S.Arata --end--
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// ヘッダ出力
    	header("Content-Type: text/xml; charset=utf-8");	// レスポンスのContent-Typeを明示的に指定する("text/xml")
    	
    	// 初期処理
    	$this->initAction();
    	
		// Fix get server name 2010/04/19 A.Suzuki --start--
		$this->server_name = BASE_URL;
		if(substr($this->server_name, -1, 1)!="/"){
			$this->server_name .= "/";
		}
		// Fix get server name 2010/04/19 A.Suzuki --end--
		
		// Add check close index 2009/12/21 Y.Nakao --start--
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        // Add Open Depo 2013/12/03 R.Matsuura --start--
        $this->setConfigAuthority();
        $this->indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Add Open Depo 2013/12/03 R.Matsuura --end--
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
        // Add tree access control list 2012/03/07 T.Koyasu -end-
		// Add check close index 2009/12/21 Y.Nakao --end--
    	
		// atomフィード文字列取得
    	$xml = $this->getOaiOreByAtom($this->itemId, $this->itemNo, $this->indexId);
    	
    	// 取得結果がfalseでなければ
    	if ( $xml != false ) {
	    	// atomフィード出力
			print $xml;
       	} else {
       		// Add check close index 2009/12/21 Y.Nakao --start--
       		// when return false, print null
       		$xml .= '<?xml version="1.0" encoding="UTF-8" ?>'.$this->LF.
	    			'<atom:feed  xmlns:atom="http://www.w3.org/2005/Atom"'.$this->LF.
	    			'    xmlns:ore="http://www.openarchives.org/ore/terms/"'.$this->LF.
	    			'    xmlns:grddl="http://www.w3.org/2003/g/data-view#"'.$this->LF.
	    			'    xmlns:dcterms="http://purl.org/dc/terms/"'.$this->LF.
	    			'    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.$this->LF.
    				'    grddl:transformation="http://www.openarchives.org/ore/atom/atom-grddl.xsl">'.$this->LF.
    				$this->LF.
    				'</atom:feed>';
    		print $xml;
    		// Add check close index 2009/12/21 y.Nakao --end--
       	}
       	
		// 終了処理
		$this->exitAction();
		
		// XML書き出し終了後にexit関数を呼び出す
    	exit();

    }
	
    function getOaiOreByAtom($itemId, $itemNo, $indexId)
    {
    	// 関連アイテムの検索
    	// rootインデックスのReMを出力する場合
     	if ($itemId == null && $itemNo == null && $indexId == null){
	    	$query = 'SELECT'. 
		    		 '    index_id, '.
		    		 '    index_name, '.
	    			 '    index_name_english, '.
	    			 '    ins_date, '.
		    		 '    mod_date '.
	    			 'FROM'. 
	    			 '    '.DATABASE_PREFIX.'repository_index '.
					 'WHERE'.
					 "     parent_index_id = 0 ".
	    	// Add check close index 2009/12/21 Y.Nakao --start--
					 //" AND pub_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
					 " AND pub_date <= NOW() ".
	    			 " AND public_state = 1 ".
					 " AND is_delete = 0; ";
	    	// Add check close index 2009/12/21 Y.Nakao --end--
	    	$retRef = $this->Db->execute($query);
			if ($retRef === false) {
				return false;
			}
			//関連アイテムが無くても、自分自身は表示する
    	}
    	// アイテムのReMを出力する場合
        else if ($itemId != null && $itemNo != null && $indexId == null){
	    	$query = 'SELECT'. 
	    			 '    REF.dest_reference_item_id, '.
	    			 '    REF.dest_reference_item_no, '.
					 '    REFITEM.title, '.
	    			 '    REFITEM.title_english, '.
					 '    ITEMTYPE.mapping_info, '.
					 '    REFITEM.ins_date, '.
					 '    REFITEM.mod_date '.
	    			 'FROM'. 
	    			 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
	    			 '    '.DATABASE_PREFIX.'repository_item_type ITEMTYPE, '.
	    			 '    '.DATABASE_PREFIX.'repository_item REFITEM, '.
	    			 '    '.DATABASE_PREFIX.'repository_reference REF '.
					 'WHERE'.
					 '    ITEM.item_id = REF.org_reference_item_id '.
	   				 'AND ITEM.item_no = REF.org_reference_item_no '.
	    			 'AND ITEMTYPE.item_type_id = REFITEM.item_type_id '.
	    			 'AND REFITEM.item_id = REF.dest_reference_item_id '.
	    			 'AND REFITEM.item_no = REF.dest_reference_item_no '.
	    			 'AND ITEM.item_id = ? '.
					 'AND ITEM.item_no = ? '.
					 'AND ITEM.is_delete = 0 '.
	    	// Add check close index 2009/12/21 Y.Nakao --start--		
	    			 'AND ITEM.shown_status = 1 '.
	    			 //"AND ITEM.shown_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
	    			 "AND ITEM.shown_date <= NOW() ".
	    	// Add check close index 2009/12/21 Y.Nakao --end--
	    			 'AND REF.is_delete = 0;';
	    	$params = null;
			$params[] = $itemId;
			$params[] = $itemNo;
	        $retRef = $this->Db->execute($query, $params);
			if ($retRef === false) {
				return false;
			}
			//関連アイテムが無くても、自分自身は表示する
    	}
    	// インデックスのReMを出力する場合
    	else if($indexId != null && $itemId == null && $itemNo == null ){
    	    // Mod OpenDepo 2014/01/31 S.Arata --start--
            $pub_index = $this->indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $indexId);
		    if (count($pub_index) == 0) {
                // Mod OpenDepo 2014/01/31 S.Arata --end--
    			// this index is close index
    			return false;
    		}
	    	$query = 'SELECT'.
	    			 '    REFITEM.item_id, '.
	    			 '    REFITEM.item_no, '.
	    			 '    REFITEM.title, '.
					 '    REFITEM.title_english, '.
					 '    REFITEM.ins_date, '.
					 '    REFITEM.mod_date, '.
                     '    ITEMTYPE.mapping_info '.
	    			 'FROM'. 
	    			 '    '.DATABASE_PREFIX.'repository_position_index IDX, '.
                     '    '.DATABASE_PREFIX.'repository_item_type ITEMTYPE, '.
	    			 '    '.DATABASE_PREFIX.'repository_item REFITEM '.
					 'WHERE'.
					 '    IDX.item_id = REFITEM.item_id '.
	   				 'AND IDX.item_no = REFITEM.item_no '.
	   				 'AND REFITEM.is_delete = 0 '.
                     'AND REFITEM.item_type_id = ITEMTYPE.item_type_id '.
	    	// Add check close index 2009/12/21 Y.Nakao --start--		
	    			 'AND REFITEM.shown_status = 1 '.
	    			 //"AND REFITEM.shown_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
	    			 "AND REFITEM.shown_date <= NOW() ".
	    	// Add check close index 2009/12/21 Y.Nakao --end--
	    			 'AND IDX.index_id = ? ';
	    	$params = null;
			$params[] = $indexId;
	    	$retRef = $this->Db->execute($query, $params);
			if ($retRef === false) {
				return false;
			}
    	}
    	
		// Aggregationの情報を取得
		$retItem = null;
		$retRootCreateDate = null;
		$retRootModDate = null;
		// rootインデックスのReMを出力する場合
		if ($itemId == null && $itemNo == null && $indexId == null) {
			$query = 'SELECT'. 
		    		 '    insert_time '.
					 'FROM'. 
		    		 '    '.DATABASE_PREFIX.'modules '.
					 'WHERE'.
					 '    action_name LIKE \'repository%\' ';
	    	$params = null;
			$retRootCreateDate = $this->Db->execute($query, $params);
			if ($retRootCreateDate === false) {
				return false;
			}
			$query = 'SELECT'. 
		    		 '    MAX( mod_date ) '.
					 'FROM'. 
		    		 '    '.DATABASE_PREFIX.'repository_index '.
					 'WHERE'.
					 '    parent_index_id = 0 ';
	    	$params = null;
			$retRootModDate = $this->Db->execute($query, $params);
			if ($retRootModDate === false) {
				return false;
			}
		}
		else{
			// アイテムのReMを出力する場合
			if ($itemId != null && $itemNo != null && $indexId == null) {
		    	$query = 'SELECT'. 
			    		 '    ITEM.title, '.
		    			 '    ITEM.title_english, '.
		    			 '    ITEMTYPE.mapping_info, '.
			    		 '    ITEM.ins_date, '.
			        	 '    ITEM.mod_date '.
						 'FROM'. 
			    		 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
		    			 '    '.DATABASE_PREFIX.'repository_item_type ITEMTYPE '.
						 'WHERE'.
						 '    ITEM.item_id = ? '.
		    			 'AND ITEM.item_no = ? '.
		    			 'AND ITEMTYPE.item_type_id = ITEM.item_type_id '.
		    	// Add check close index 2009/12/21 Y.Nakao --start--		
	    			 	 'AND ITEM.shown_status = 1 '.
		    			 //"AND ITEM.shown_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
		    			 "AND ITEM.shown_date <= NOW() ".
		    	// Add check close index 2009/12/21 Y.Nakao --end--
		    			 'AND ITEM.is_delete = 0; ';
		    	$params = null;
				$params[] = $itemId;
				$params[] = $itemNo;
			}
			// インデックスのReMを出力する場合
			else if($indexId != null && $itemId == null && $itemNo == null ){
		    	$query = 'SELECT'. 
			    		 '    IDX.index_name, '.
		    			//Add index name english 2009/09/01 K.Ito --start--
		    			 '    IDX.index_name_english, '.
		    			//Add index name english 2009/09/01 K.Ito --end--
			    		 '    IDX.ins_date, '.
			        	 '    IDX.mod_date '.
						 'FROM'. 
			    		 '    '.DATABASE_PREFIX.'repository_index IDX '.
						 'WHERE'.
						 '    IDX.index_id = ? '.
		    			// Add check close index 2009/12/21 Y.Nakao --start--
						 //" AND pub_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
		    			 " AND pub_date <= NOW() ".
		    			 " AND public_state = 1 ".
						 " AND is_delete = 0; ";
	    				// Add check close index 2009/12/21 Y.Nakao --end--
		    	$params = null;
				$params[] = $indexId;
			}
			$retItem = $this->Db->execute($query, $params);
			if ($retItem === false) {
				return false;
			}
		}
    	
		//XMLヘッダー出力
        $atomUri = '';
        $siteNameElm = '';
        $xml = $this->createOutPutHeaderXml($atomUri, $siteNameElm);

    	// atom:title
    	// rootインデックスのReMを出力する場合
		if ($itemId == null && $itemNo == null && $indexId == null) {
			//Add multiple language 2009/09/01 K.Ito --start--
			if($this->Session->getParameter("_lang") == "japanese"){
				//言語のiniからもってこれないのでベタ書きです
				$rootname = "ルートインデックス";
			}else{
				$rootname = "root index";
			}
			$xml .= '    <atom:title>'.$rootname.'</atom:title>'.$this->LF;
			//Add multiple language 2009/09/01 K.Ito --end--
		}
		// アイテムのReMを出力する場合
    	else if ($itemId != null && $itemNo != null && $indexId == null) {
    		if($this->Session->getParameter("_lang") == "japanese"){
	    		if($retItem[0]['title'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['title']).'</atom:title>'.$this->LF;
	    		}else if($retItem[0]['title_english'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['title_english']).'</atom:title>'.$this->LF;
	    		}
	    	}else{
	    		if($retItem[0]['title_english'] != ""){
	    			$xml .= $indent. '<atom:title>'.$this->forXmlChange($retItem[0]['title_english']).'</atom:title>'.$this->LF;
	    		}else if($retItem[0]['title'] != ""){
	    			$xml .= $indent. '<atom:title>'.$this->forXmlChange($retItem[0]['title']).'</atom:title>'.$this->LF;
	    		}
	    	}
			
	    	//$xml .= '    <atom:title>'.$this->forXmlChange($retItem[0]['title']).'</atom:title>'.$this->LF;
    	}
    	// インデックスのReMを出力する場合
    	else if($indexId != null && $itemId == null && $itemNo == null ){
    		if($this->Session->getParameter("_lang") == "japanese"){
	    		if($retItem[0]['index_name'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['index_name']).'</atom:title>'.$this->LF;
	    		}else if($retItem[0]['index_name_english'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['index_name_english']).'</atom:title>'.$this->LF;
	    		}
	    	}else{
	    		if($retItem[0]['index_name_english'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['index_name_english']).'</atom:title>'.$this->LF;
	    		}else if($retItem[0]['index_name'] != ""){
	    			$xml .='    <atom:title>'.$this->forXmlChange($retItem[0]['index_name']).'</atom:title>'.$this->LF;
	    		}
	    	}
	    	//$xml .= '    <atom:title>'.$this->forXmlChange($retItem[0]['index_name']).'</atom:title>'.$this->LF;
    	}
    	
    	// atom:category(Aggregation)
    	$xml .= '    <atom:category  scheme="http://www.openarchives.org/ore/terms/"'.$this->LF. 
        		'        term="http://www.openarchives.org/ore/terms/Aggregation"'.$this->LF. 
        		'        label="Aggregation" />'.$this->LF;
    	
    	// atom:category(junii2)
    	// アイテムのReMを出力する場合
    	if ($itemId != null && $itemNo != null && $indexId == null) {
    		$xml .= $this->getDCCategoryElement($retItem[0]['mapping_info'],1);
    	}
    	// インデックス、rootインデックスのReMを出力する場合
    	else {
    		$xml .= $this->getDCCategoryElement('Others',1);
    	}
    	
    	// Add output atom:link(rel="related") 2008/10/21 A.Suzuki --start--
    	if ($itemId != null && $itemNo != null && $indexId == null) {
	    	// Aggregationの逆参照のアイテム情報を取得
	    	$query = 'SELECT'. 
	    			 '    REF.org_reference_item_id, '.
	    			 '    REF.org_reference_item_no '.
	    			 'FROM'. 
	    			 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
	    			 '    '.DATABASE_PREFIX.'repository_reference REF '.
					 'WHERE'.
					 '    ITEM.item_id = REF.dest_reference_item_id '.
	   				 'AND ITEM.item_no = REF.dest_reference_item_no '.
	    			 'AND ITEM.item_id = ? '.
					 'AND ITEM.item_no = ? '.
	    			 'AND ITEM.is_delete = 0 '.
					 'AND REF.is_delete = 0;';
	    	$params = null;
			$params[] = $itemId;
			$params[] = $itemNo;
			$retRelItem = $this->Db->execute($query, $params);
	    	if ($retRelItem === false) {
				return false;
			}
			for($i=0;$i<count($retRelItem);$i++) {
				$detailUri = null;
				// Add detail uri 2008/11/13 Y.Nakao --start--
			    $detailUri = $this->getDetailUri($retRelItem[$i]['org_reference_item_id'], $retRelItem[$i]['org_reference_item_no']);
			    if($detailUri==null || $detailUri == ""){
			    	return false;
			    }
		    	$xml .= '    <atom:link'.$this->LF.
		    			'        rel="related"'.$this->LF.
		    			'        type="text/html"'.$this->LF.
		    			'        href="'.$this->forXmlChange($detailUri).'" />'.$this->LF;
				// Add detail uri 2008/11/13 Y.Nakao --end--
			}
    	} else if($indexId != null && $itemId == null && $itemNo == null) {
    	// Aggregationの逆参照のインデックス情報を取得
	    	$query = 'SELECT'. 
	    			 '    parent_index_id '.
	    			 'FROM'. 
	    			 '    '.DATABASE_PREFIX.'repository_index '.
					 'WHERE'.
					 '    index_id = ? '.
	    			 'AND public_state  = 1 '.
					 'AND is_delete = 0;';
	    	$params = null;
			$params[] = $indexId;
			$retRelIndex = $this->Db->execute($query, $params);
	    	if ($retRelIndex === false) {
				return false;
			}
			for($i=0;$i<count($retRelIndex);$i++) {
				// 親インデックスの情報取得用URL
		    	$detailUri = null;
	    		$detailUri = $this->forXmlChange($this->server_name.'?action=repository_oaiore&indexId='.$retRelIndex[0]['parent_index_id']);
		    	$xml .= '    <atom:link'.$this->LF.
		    			'        rel="related"'.$this->LF.
		    			'        type="text/html"'.$this->LF.
		    			'        href="'.$detailUri.'" />'.$this->LF;
			}
    	}
    	// Add output atom:link(rel="related") 2008/10/21 A.Suzuki --end--
    	    	
    	// ResourseMap
    	// atom:link
    	$xml .= '    <atom:link'.$this->LF.
    			'        rel="self"'.$this->LF.
    			'        type="application/atom+xml"'.$this->LF.
    			'        href="'.$atomUri.'" />'.$this->LF;
    	
    	// atom:generator
    	$host = ereg_replace('://.*$', '://', BASE_URL).$_SERVER['HTTP_HOST'];
    	$xml .= '    <atom:generator uri="'.$host.'" >'.$siteNameElm['conf_value'].'</atom:generator>'.$this->LF;
    		
    	// TODO: rootインデックスの考慮が必要である
    	// atom:updated
    	if ($itemId == null && $itemNo == null && $indexId == null) {
            
    		$updatedDate = $this->changeDatetimeToW3C($retRootModDate[0]['MAX( mod_date )']);
    	}
    	else {
    		$updatedDate = $this->changeDatetimeToW3C($retItem[0]['mod_date']);
    	}
    	$xml .= '    <atom:updated>'.$updatedDate.'</atom:updated>'.$this->LF;
    	
    	// TODO: rootインデックスの考慮が必要である
    	// dcterms:created
    	if ($itemId == null && $itemNo == null && $indexId == null) {
    		$createdDate = $this->changeDatetimeToW3C($this->dateChg($retRootCreateDate[0]['insert_time']));
    	}
    	else {
    		$createdDate = $this->changeDatetimeToW3C($retItem[0]['ins_date']);
    	}
    	$xml .= '    <rdf:Description rdf:about="'.$atomUri.'">'.$this->LF;
    	$xml .= '        <dcterms:created>'.$this->forXmlChange($createdDate).'</dcterms:created>'.$this->LF;
    	$xml .= '    </rdf:Description>'.$this->LF;
    	
    	// atom:entry
    	// インデックスのReMを出力する場合
    	if ($itemId == null && $itemNo == null && $indexId != null) {
	    	// Indexのentity
	    	$query = 'SELECT'. 
		    		 '    idx.index_id, '.
		    		 '    idx.index_name, '.
	    			 '    idx.index_name_english, '.
	    			 '    idx.ins_date, '.
		    		 '    idx.mod_date '.
	    			 'FROM'. 
		    		 '    '.DATABASE_PREFIX.'repository_index idx '.
					 'WHERE'.
					 '    idx.parent_index_id = ? '.
	    			 'AND public_state = 1 '.
	    			 // Add check close index 2009/12/21 Y.Nakao --start--
					 //" AND pub_date <= '".date('Y-m-d 00:00:00.000',mktime())."' ".
					 " AND pub_date <= NOW() ".
					 " AND is_delete = 0; ";
    				// Add check close index 2009/12/21 Y.Nakao --end--
    	
	    	$params = null;
			$params[] = $indexId;	 
			$retIndex = $this->Db->execute($query, $params);
			if ($retIndex === false) {
				return false;
			}
			for ($i = 0; $i < count($retIndex); $i++) {
                // Add tree access control list 2012/03/07 T.Koyasu -start-
                // Mod OpenDepo 2014/01/31 S.Arata --start--
			    $pub_index = $this->indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $retIndex[$i]['index_id']);
                if(count($pub_index) > 0){
                    // Mod OpenDepo 2014/01/31 S.Arata --end--
                    $xml .= $this->getIndexEntryElement($retIndex[$i]['index_id'],
                    								    $retIndex[$i]['index_name'],
                    								    //Add name english 2009/09/01 K.Ito --start--
                    								    $retIndex[$i]['index_name_english'],
                    								    //Add name english 2009/09/01 K.Ito --end--
                    								    $indexId,
                    								    $retIndex[$i]['ins_date'],
                    								    $retIndex[$i]['mod_date'],
                    								    1);
                }
                // Add tree access control list 2012/03/07 T.Koyasu -end-
			}
    	}
		// Itemのentity
    	for ($i = 0; $i < count($retRef); $i++) {
    	    //ルートインデックス
    		if($itemId == null && $itemNo == null && $indexId == null) {
                // Add tree access control list 2012/03/07 T.Koyasu -start-
                // Mod OpenDepo 2014/01/31 S.Arata --start--
                $pub_index = $this->indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $retRef[$i]['index_id']);
                if(count($pub_index) > 0){
                    // Mod OpenDepo 2014/01/31 S.Arata --end--
                    $xml .= $this->getIndexEntryElement($retRef[$i]['index_id'],
                    								    $retRef[$i]['index_name'],
                    								    //Add name english 2009/09/01 K.Ito --start--
                    								    $retRef[$i]['index_name_english'],
                    								    //Add name english 2009/09/01 K.Ito --end--
                    								    0,
                    								    $retRef[$i]['ins_date'],
                    								    $retRef[$i]['mod_date'],
                    								    1);
                }
                // Add tree access control list 2012/03/07 T.Koyasu -end-
    		}
    		//ルート以外のインデックス
    		else if($indexId != null && $itemId == null && $itemNo == null ){
	    		$xml .= $this->getItemEntryElement($retRef[$i]['item_id'],
	    									   $retRef[$i]['item_no'],
	    									   $retRef[$i]['title'],
	    									   //Add name english 2009/09/01 K.Ito --start--
	    									   $retRef[$i]['title_english'],
	    									   //Add name english 2009/09/01 K.Ito --end--
	    									   $retRef[$i]['mapping_info'],
	    									   $retRef[$i]['ins_date'],
	    									   $retRef[$i]['mod_date'],
	    									   1);
    		}
            
            //アイテムは => getItemLinkXml()でアイテム間リンク情報/getSuppleItemXml()でサプリメンタルコンテンツ情報を出力する
    	}
    	
    	//出力項目の追加 2013/3/12 Add jin --start--
    	//アイテム間リンク情報の出力
        $xml .= $this->getItemLinkXml();
        //サプリメンタルコンテンツ情報の出力
        $xml .= $this->getSuppleItemXml();
        //出力項目の追加2013/3/12 Add jin --end--
        
    	//$this->getEntryElement()
    	
    	// atom:feed の終了
    	$xml .= '</atom:feed>'.$this->LF;
    	
    	return $xml;

    }
    /*
     * Header Xmlタグ生成処理
     * 
     */
    private function createOutPutHeaderXml(&$atomUri, &$siteNameElm){
        $atomUri = BASE_URL;
        if(substr($atomUri, -1, 1)!="/"){
            $atomUri .= "/";
        }
        $atomUri .= "?".$_SERVER['QUERY_STRING'];
        $atomUri = $this->forXmlChange($atomUri);
        // Fix get atomUri 2010/04/19 A.Suzuki --end--
        
        // サイト名取得
        $container =& DIContainerFactory::getContainer(); 
        $configView =& $container->getComponent("configView");
        $siteNameElm = $configView->getConfigByConfname(_SYS_CONF_MODID, "sitename");
                
        // xmlヘッダ出力
        $xml = "";
        $xml .= '<?xml version="1.0" encoding="UTF-8" ?>'.$this->LF.
                '<atom:feed  xmlns:atom="http://www.w3.org/2005/Atom"'.$this->LF.
                '    xmlns:ore="http://www.openarchives.org/ore/terms/"'.$this->LF.
                '    xmlns:grddl="http://www.w3.org/2003/g/data-view#"'.$this->LF.
                '    xmlns:dcterms="http://purl.org/dc/terms/"'.$this->LF.
                '    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.$this->LF.
                '    grddl:transformation="http://www.openarchives.org/ore/atom/atom-grddl.xsl">'.$this->LF.
                $this->LF;
                
        // atom:id
        $xml .= '    <atom:id>'.$atomUri.'</atom:id>'.$this->LF;
        
        // atom:author
        $xml .= $this->getAuthorElement($this->itemId, $this->itemNo, $this->indexId, 1);
        
        
        return $xml;
    }
    
    /**
     * [[機能説明]]
     * <atom:entry>要素を出力
     * @access  public
     */
	function getItemEntryElement($itemId, 
	                             $itemNo, 
	                             $title, 
	                             $title_english, 
	                             $category, 
	                             $insDate, 
	                             $modDate, 
	                             $indentNum, 
	                             $supple_base_url='', 
	                             $supple_creator=array(), 
	                             $supple_detail_url='') {
		// インデント調整
		$indentParent = $this->getTabIndent($indentNum);
		$indent = $indentParent. $this->TAB_SHIFT;
		
		// 開始
		$out = $indentParent . '<atom.entry>'.$this->LF;
		// atom:id
        if($supple_base_url==null || $supple_base_url == ''){
            $atomUri = $this->forXmlChange($this->server_name.'?action=repository_oaiore&itemId='.$itemId.'&itemNo='.$itemNo);
        }else{
            $atomUri = $this->forXmlChange($supple_base_url.'?action=repository_oaiore&itemId='.$itemId.'&itemNo='.$itemNo);
        }
        $out .= $indent. '<atom:id>'.$atomUri.'</atom:id>'.$this->LF;
        
        // atom:author
        if($supple_creator==null || count($supple_creator) == 0){
            $out .= $this->getAuthorElement($itemId, $itemNo, null, $indentNum+1);
        }else{
            for($ii=0; $ii<count($supple_creator); $ii++){
                $out .= $indentParent . '<atom:author>'.$this->LF;
                $out .= $indent. '<atom:name>'.$this->forXmlChange($supple_creator[$ii]).'</atom:name>'. $this->LF;
                $out .= $indentParent . '</atom:author>'.$this->LF;
            }
        }
        //Add multiple language title 2009/08/31 K.Ito --start--
        // atom:title
        //$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
  
    	if($this->Session->getParameter("_lang") == "japanese"){
    		if($title != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
    		}else{
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title_english).'</atom:title>'.$this->LF;
    		}
    	}else{
    		if($title_english != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title_english).'</atom:title>'.$this->LF;
    		}else{
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
    		}
    	}
		
        //Add multiple language title 2009/08/31 K.Ito --end--
        // atom:updated
        $patern = "/^(\\d{4})-?(\\d{2})?-?(\\d{2})?T?(\\d{2})?:?(\\d{2})?:?(\\d{2})?(\\+|-|Z)?(\\d{2})?:?(\\d{2})?$/";
        //W3C形式の場合 (ex 2013-02-17T18:40:14+09:00)
        if(preg_match($patern, $modDate) == 1){
            $updatedDate = $modDate;
            //$updatedDate = preg_replace("/(\\+|-|Z)?(\\d{2})?:?(\\d{2})$/",'Z',$modDate);
        }
        //W3C形式以外の場合
        else{
            $updatedDate = $this->changeDatetimeToW3C($modDate);
        }
        $out .= $indent. '<atom:updated>'.$updatedDate.'</atom:updated>'.$this->LF;
        // atom:created
        //W3C形式の場合
        if(preg_match($patern, $insDate) == 1){
            $createdDate = $insDate;
            //$createdDate = preg_replace("/(\\+|-|Z)?(\\d{2})?:?(\\d{2})$/",'Z',$insDate);
        }
        //W3C形式以外の場合
        else{
            $createdDate = $this->changeDatetimeToW3C($insDate);
        }
    	$out .= $indent. '<rdf:Description rdf:about="'.$atomUri.'">'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. '<dcterms:created>'.$createdDate.'</dcterms:created>'.$this->LF;
    	$out .= $indent. '</rdf:Description>'.$this->LF;
    	// atom:category
    	$out .= $this->getDCCategoryElement($category,1);
    	// atom:link(alternate)
    	$out .= $indent. '<atom:link'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'rel="alternate"'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'type="text/html"'.$this->LF;
    	// アイテム詳細ページのURL
    	// Add detail uri 2008/11/13 Y.Nakao --start--
    	if($supple_detail_url == null && $supple_detail_url == ''){
            $detailUri = $this->getDetailUri($itemId, $itemNo);
            if($detailUri==null || $detailUri == ""){
            	return false;
            }
            $detailUri = $this->forXmlChange($detailUri);
        }else{
            $detailUri = $this->forXmlChange($supple_detail_url);
        }
		// Add detail uri 2008/11/13 Y.Nakao --end--
    	$out .= $indent. $this->TAB_SHIFT. 'href="'.$detailUri.'" />'.$this->LF;
    	
    	/* ARでlink rel=relatedは表示しない
    	// link rel=relatedで出力するitem情報の取得
	    $query = 'SELECT'. 
	    		 '    org_reference_item_id, '.
	    		 '    org_reference_item_no '.
    			 'FROM'. 
	    		 '    '.DATABASE_PREFIX.'repository_reference '.
				 'WHERE'.
				 '    dest_reference_item_id = ? '.
	    		 'AND dest_reference_item_no = ? '.
	    		 'AND is_delete = 0';
    	$params = null;
		$params[] = $itemId;
		$params[] = $itemNo;
		$retRelItem = $this->Db->execute($query, $params);
		if ($retRelItem === false) {
			return false;
		}
    	for ($i=0; $i<count($retRelItem); $i++){
	    	// atom:link(related)
	    	$out .= $indent. '<atom:link'.$this->LF;
	    	$out .= $indent. $this->TAB_SHIFT. 'rel="related"'.$this->LF;
	    	// AggregationのURI
	    	$detailUri = $this->forXmlChange('http://'.$_SERVER['HTTP_HOST'].'/htdocs/?action=repository_oaiore&itemId='.$retRelItem[$i]['org_reference_item_id'].'&itemNo='.$retRelItem[$i]['org_reference_item_no']);
	    	$out .= $indent. $this->TAB_SHIFT. 'href="'.$detailUri.'" />'.$this->LF;
    	}
		$query = 'SELECT'. 
	    		 '    index_id '.
    			 'FROM'. 
	    		 '    '.DATABASE_PREFIX.'repository_position_index '.
				 'WHERE'.
				 '    item_id = ? '.
	    		 'AND item_no = ?';
    	$params = null;
		$params[] = $itemId;
		$params[] = $itemNo;
		$retRelIndex = $this->Db->execute($query, $params);
		if ($retRelIndex === false) {
			return false;
		}
	    for ($i=0; $i<count($retRelIndex); $i++){
	    	// atom:link(related)
	    	$out .= $indent. '<atom:link'.$this->LF;
	    	$out .= $indent. $this->TAB_SHIFT. 'rel="related"'.$this->LF;
	    	// AggregationのURI
	    	$detailUri = $this->forXmlChange('http://'.$_SERVER['HTTP_HOST'].'/htdocs/?action=repository_oaiore&indexId='.$retRelIndex[$i]['index_id']);
	    	$out .= $indent. $this->TAB_SHIFT. 'href="'.$detailUri.'" />'.$this->LF;
	    }
		*/
    	// 終了
		$out .= $indentParent . '</atom.entry>'.$this->LF;
    	return $out;
	}

	function getIndexEntryElement($indexId, $title, $title_english, $parentIndexid, $insDate, $modDate, $indentNum) {
		// インデント調整
		$indentParent = $this->getTabIndent($indentNum);
		$indent = $indentParent. $this->TAB_SHIFT;
		
		// 開始
		$out = $indentParent. '<atom.entry>'.$this->LF;
		// atom:id
		$atomUri = $this->forXmlChange($this->server_name.'?action=repository_oaiore&indexId='.$indexId);
		$out .= $indent.'<atom:id>'.$atomUri.'</atom:id>'.$this->LF;
    	// atom:author
    	$out .= $this->getAuthorElement(null, null, $indexId, $indentNum+1);
    	//Add multiple language title 2009/09/01 K.Ito --start--
    	// atom:title
    	//$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
		if($this->Session->getParameter("_lang") == "japanese"){
    		if($title != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
    		}else if($title_english != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title_english).'</atom:title>'.$this->LF;
    		}
    	}else{
    		if($title_english != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title_english).'</atom:title>'.$this->LF;
    		}else if($title != ""){
    			$out .= $indent. '<atom:title>'.$this->forXmlChange($title).'</atom:title>'.$this->LF;
    		}
    	}
    	//Add multiple language title 2009/09/01 K.Ito --end--
    	// atom:updated
    	$updatedDate = $this->changeDatetimeToW3C($modDate);
    	$out .= $indent. '<atom:updated>'.$updatedDate.'</atom:updated>'.$this->LF;
    	// atom:created
    	$createdDate = $this->changeDatetimeToW3C($insDate);
    	$out .= $indent. '<rdf:Description rdf:about="'.$atomUri.'">'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. '<dcterms:created>'.$createdDate.'</dcterms:created>'.$this->LF;
    	$out .= $indent. '</rdf:Description>'.$this->LF;
    	// atom:category
    	$out .= $this->getDCCategoryElement('Others',1);
    	// atom:link(alternate)
    	$out .= $indent. '<atom:link'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'rel="alternate"'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'type="text/xml"'.$this->LF;
    	// Add detail uri 2008/11/13 Y.Nakao --start--
    	// index realities
		$detailUri = $atomUri; // $atomUri is aloready encode
    	// Add detail uri 2008/11/13 Y.Nakao --end--
    	$out .= $indent. $this->TAB_SHIFT. 'href="'.$detailUri.'" />'.$this->LF;
    	
    	/* entryにrelatedは不要
    	// atom:link(related)
    	$out .= $indent. '<atom:link'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'rel="related"'.$this->LF;
    	$out .= $indent. $this->TAB_SHIFT. 'type="text/html"'.$this->LF;
    	if ($parentIndexid == 0) {
    		$detailUri = $this->forXmlChange('http://'.$_SERVER['HTTP_HOST'].'/htdocs/?action=repository_oaiore');
    	}
    	else {
    		$detailUri = $this->forXmlChange('http://'.$_SERVER['HTTP_HOST'].'/htdocs/?action=repository_oaiore&indexId='.$parentIndexid);
    	}
    	$out .= $indent. $this->TAB_SHIFT. 'href="'.$detailUri.'" />'.$this->LF;
    	*/
    	
    	// 終了
		$out .= $indentParent . '</atom.entry>'.$this->LF;
		
		return $out;
	}
	
	function getAuthorElement($itemId, $itemNo, $indexId, $indentNum)
	{
		// インデント調整
		$indentParent = $this->getTabIndent($indentNum);
		$indent = $indentParent. $this->TAB_SHIFT;
		
		// クエリ作成
    	$query = '';
    	$params = null;
    	$authorName = null;

		// 引数未設定
		if ($itemId == null && $itemNo == null && $indexId == null){
			// ありえない
		}
		// ItemのAuthorの場合
		else if ($itemId != null && $itemNo != null && $indexId == null){
		//Add creator name 2009/08/31 K.Ito --start--
			//論文の氏名、名前を取得する creator で入力タイプが氏名のみ
			$authorName = null;
			$query = "SELECT name.family, ".
					     "   name.name, ".
						 "   attr_type.display_lang_type ".
						 "FROM ". DATABASE_PREFIX ."repository_item AS item, ".
						 "     ". DATABASE_PREFIX ."repository_personal_name AS name, ".
						 "     ". DATABASE_PREFIX ."repository_item_type AS item_type, ".
						 "     ". DATABASE_PREFIX ."repository_item_attr_type AS attr_type ".
						 "WHERE item.item_id = name.item_id ".
						 "  AND item.item_type_id = item_type.item_type_id ".
						 "  AND (item_type.mapping_info != null OR  item_type.mapping_info != '') ".
						 "  AND item.item_type_id = attr_type.item_type_id ".
						 "  AND name.attribute_id = attr_type.attribute_id ".
						 "  AND attr_type.junii2_mapping = 'creator' ".
						 "  AND item.is_delete != 1 ".
						 "  AND item.item_id = ? ".
						 // Fix output hidden metadata 2011/11/28 Y.Nakao --start--
						 "  AND attr_type.hidden = 0 ".
						 // Fix output hidden metadata 2011/11/28 Y.Nakao --end--
						 "  AND name.is_delete = 0 ;";
			$params1 = null;
			$params1[] = $itemId;
			$ret_personal_name = $this->Db->execute($query, $params1);
			if ($ret_personal_name === false) {
				return false;
			}
			
			// Add LIDO 2014/05/13 S.Suzuki --start--
			for ($ii = 0; $ii < count($ret_personal_name); $ii++) {
				$ret_personal_name[$ii]['family']            = RepositoryOutputFilter::exclusiveReservedWords($ret_personal_name[$ii]['family']);
				$ret_personal_name[$ii]['name']              = RepositoryOutputFilter::exclusiveReservedWords($ret_personal_name[$ii]['name']);
				$ret_personal_name[$ii]['display_lang_type'] = RepositoryOutputFilter::exclusiveReservedWords($ret_personal_name[$ii]['display_lang_type']);
			}
			// Add LIDO 2014/05/13 S.Suzuki --end--
			
			for ($nElem=0; $nElem<count($ret_personal_name); $nElem++) {
				if($ret_personal_name[$nElem]['family'] != "" && $ret_personal_name[$nElem]['name'] != ""){
					$authorName[] = $ret_personal_name[$nElem]['family'].", ".$ret_personal_name[$nElem]['name'];
				}
				if($ret_personal_name[$nElem]['family'] != "" || $ret_personal_name[$nElem]['name'] != ""){
					$authorName[] = $ret_personal_name[$nElem]['family'].$ret_personal_name[$nElem]['name'];
				}
			}
			//Junni2がクリエイター、でも入力タイプは氏名ではないもの
			$query = "SELECT attr.attribute_value ".
					 "FROM ". DATABASE_PREFIX ."repository_item AS item, ".
					 "     ". DATABASE_PREFIX ."repository_item_attr AS attr, ".
					 "     ". DATABASE_PREFIX ."repository_item_type AS item_type, ".
					 "     ". DATABASE_PREFIX ."repository_item_attr_type AS attr_type ".
					 "WHERE item.item_id = attr.item_id ".
					 "  AND item.item_type_id = item_type.item_type_id ".
					 "  AND (item_type.mapping_info != null OR  item_type.mapping_info != '') ".
					 "  AND item.item_type_id = attr_type.item_type_id ".
					 "  AND attr.attribute_id = attr_type.attribute_id ".
					 "  AND item.is_delete != 1 ".
					 "  AND attr_type.junii2_mapping = 'creator' ".
					 "  AND item.item_id = ? ".
					 // Fix output hidden metadata 2011/11/28 Y.Nakao --start--
					 "  AND attr_type.hidden = 0 ".
					 // Fix output hidden metadata 2011/11/28 Y.Nakao --end--
					 "  AND attr.is_delete = 0 ;";
			$ret_item_attr = $this->Db->execute($query, $params1);
			if ($ret_item_attr === false) {
				$this->outputError();
				return false;
			}
			
			// Add LIDO 2014/05/13 S.Suzuki --start--
			for ($ii = 0; $ii < count($ret_item_attr); $ii++) {
				$ret_personal_name[$ii]['attribute_value'] = RepositoryOutputFilter::exclusiveReservedWords($ret_personal_name[$ii]['attribute_value']);
			}
			
			for ($nElem=0; $nElem<count($ret_item_attr); $nElem++) {
				if($ret_item_attr[$nElem]['attribute_value'] != ""){
					$authorName[] = $ret_item_attr[$nElem]['attribute_value'];
				}
			}
			// Add LIDO 2014/05/13 S.Suzuki --end--
			
			//creatorがなかった場合には、ユーザーＩＤ。それもなかったらハンドル名にする
			if($authorName[0] == null){
				/*
				$query = 'SELECT '.
						 '    ATTR.attribute_value '. 
						 'FROM '.
						 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
						 '    '.DATABASE_PREFIX.'repository_item_attr_type TYPE, '. 
						 '    '.DATABASE_PREFIX.'repository_item_attr ATTR '.
						 'WHERE '. 
						 '    ITEM.item_type_id = TYPE.item_type_id '. 
						 'AND TYPE.dublin_core_mapping = \'creator\' '. 
						 'AND TYPE.item_type_id = ATTR.item_type_id '. 
						 'AND TYPE.attribute_id = ATTR.attribute_id '. 
						 'AND ITEM.item_id = ATTR.item_id '. 
						 'AND ITEM.item_no = ATTR.item_no '.
						 'AND ITEM.item_id = ? '.
						 'AND ITEM.item_no = ? ';
				$params[] = $itemId;
				$params[] = $itemNo;
				$ret = $this->Db->execute($query, $params);
				if ($ret === false) {
					return false;
				}
				else if( count($ret) == 0 ){
				//既に削除されている場合の判定がクエリには入ってない？
				}else {
					for ($i=0; $i<count($ret); $i++){
						$authorName[] = $this->forXmlChange($ret[$i]['attribute_value']);
					}
				}
				*/
				$query = 'SELECT '.
						 '    USER.handle, '. 
						 '    USERITEM.public_flag, '. 
						 '    USERITEM.content '. 
						 'FROM '.
						 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
						 '    '.DATABASE_PREFIX.'users USER, '.
						 '    '.DATABASE_PREFIX.'users_items_link USERITEM '.
						 'WHERE '. 
						 '    ITEM.ins_user_id = USER.user_id '. 
						 'AND USER.user_id = USERITEM.user_id '.
						 'AND ITEM.item_id = ? '.
						 'AND ITEM.item_no = ? ';
				$params = null; //初期化
				$params[] = $itemId;
				$params[] = $itemNo;
				$ret = $this->Db->execute($query, $params);
				if ($ret === false) {
					return false;
				}
				//結果がヒットしているか
				if($ret != null){
					if ($ret[0]['public_flag'] == 0){
						$authorName[] = $ret[0]['handle'];
					}
					else{
						$authorName[] = $ret[0]['content'];
					}
				}else{
					//items_linkテーブルに設定がない場合はハンドル名
					$query = 'SELECT '.
							 '    USER.handle '. 
							 'FROM '.
							 '    '.DATABASE_PREFIX.'repository_item ITEM, '.
							 '    '.DATABASE_PREFIX.'users USER '.
							 'WHERE '. 
							 '    ITEM.ins_user_id = USER.user_id '. 
							 'AND ITEM.item_id = ? '.
							 'AND ITEM.item_no = ? ';
					$params = null; //初期化
					$params[] = $itemId;
					$params[] = $itemNo;
					$ret = $this->Db->execute($query, $params);
					if ($ret === false) {
						return false;
					}
					$authorName[] = $ret[0]['handle'];
				}
			}
		}
		//Add creator name 2009/08/31 K.Ito --end--
		// IndexのAuthorの場合
		else if ($itemId == null && $itemNo == null && $indexId != null){
			$query = 'SELECT '.
					 '    USER.handle, '. 
					 '    USERITEM.public_flag, '. 
					 '    USERITEM.content '. 
					 'FROM '. 
					 '    '.DATABASE_PREFIX.'repository_index IDX, '. 
					 '    '.DATABASE_PREFIX.'users USER, '. 
					 '    '.DATABASE_PREFIX.'users_items_link USERITEM '.
					 'WHERE '. 
					 '    IDX.index_id = ? '.
					 'AND IDX.ins_user_id = USER.user_id '.
					 'AND USERITEM.user_id = USER.user_id ';
			$params[] = $indexId;
			$ret = $this->Db->execute($query, $params);
			if ($ret === false) {
				return false;
			}
			//戻り値がある場合のみauthorを設定する
			if($ret != null){
				if ($ret[0]['public_flag'] == 0){
					$authorName[] = $ret[0]['handle'];
				}
				else{
					$authorName[] = $ret[0]['content'];
				}
			}
		}
		// 開始
		$out = '';
		for ($i=0; $i<count($authorName); $i++){
			$out .= $indentParent . '<atom:author>'.$this->LF;
			// atom:name
			$out .= $indent. '<atom:name>'.$this->forXmlChange($authorName[$i]).'</atom:name>'. $this->LF;
			/* WEKOでは元データとなる要素が無いため出力しない
			// atom:uri
			$out .= $indent. '<atom:uri>http://xxxxx.xx.xx/</atom:uri>'. $this->LF;
			*/
			// 終了
			$out .= $indentParent . '</atom:author>'.$this->LF;
		}
		//
		return $out;
	}
    
    //2013/3/12 Add jin アイテム間リンク情報のXMLを作成する処理--start--
    /*
     * アイテム間リンク情報のXMLを作成
     */
    private function getItemLinkXml(){
        $xml = "";
        
        //1. アイテム間リンク情報を取得する。
        $query = 'SELECT * '.
                 'FROM '.DATABASE_PREFIX.'repository_reference '.
                 'WHERE'.
                 ' org_reference_item_id=?'.
                 ' AND'.
                 ' org_reference_item_no=?'.
                 ' AND'.
                 ' is_delete=? ;';
                 
        $params = null; //初期化
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
        $params[] = 0;
        $ret_link = $this->Db->execute($query, $params);
        if ($ret_link === false) {
            return "";
        }
        
        //2. 1で取得したレコードのdest_reference_item_id, dest_reference_item_noからアイテム間リンク情報を取得する。
        $relation_items = array();
        
        for($index=0; $index < count($ret_link); $index++){
            $query = 'SELECT '.
                     ' ITEM.item_id,'.
                     ' ITEM.item_no,'.
                     ' ITEM.title,'.
                     ' ITEM.title_english,'.
                     ' ITEM.ins_date,'.
                     ' ITEM.mod_date,'.
                     ' ITEMTYPE.mapping_info '.
                     'FROM '.DATABASE_PREFIX.'repository_item ITEM, '.
                            DATABASE_PREFIX.'repository_item_type ITEMTYPE '.
                     'WHERE '.
                     ' ITEM.item_type_id = ITEMTYPE.item_type_id'.
                     ' AND'.
                     ' ITEM.item_id = ?'.
                     ' AND'.
                     ' ITEM.item_no = ?'.
                     ' AND'.
                     ' ITEM.is_delete = ?;';
            $params = null; //初期化
            $params[] = $ret_link[$index]['dest_reference_item_id'];
            $params[] = $ret_link[$index]['dest_reference_item_no'];
            $params[] = 0;
            $ret_item = $this->Db->execute($query, $params);
            if($ret_item != false){
                array_push($relation_items, $ret_item);
            }
        }
        
        //3. 2で取得したアイテム間リンク情報からXMLを生成する。
        for ($i = 0; $i < count($relation_items); $i++) {
            if($this->itemId != null && $this->itemNo != null && $this->indexId == null) {
                  $xml .= $this->getItemEntryElement($relation_items[$i][0]['item_id'],
                                                 $relation_items[$i][0]['item_no'],
                                                 $relation_items[$i][0]['title'],
                                                 $relation_items[$i][0]['title_english'],
                                                 $relation_items[$i][0]['mapping_info'],
                                                 $relation_items[$i][0]['ins_date'],
                                                 $relation_items[$i][0]['mod_date'],
                                                 1);
            }
        }
        
        return $xml;
    }
    //2013/3/12 Add jin アイテム間リンク情報のXMLを作成する処理--end--

    //2013/3/12 Add jin サプリメンタルコンテンツ情報のXMLを作成する処理--start--
    /*
     *  サプリメンタルコンテンツ情報のXMLを作成
     */
    private function getSuppleItemXml(){
        //戻り値の宣言
        $xml = "";
        //サプリメンタルWEKOから取得するサプリデータの配列の宣言
        $supple_array = array();
        //サプリメンタルWEKOのBASE_URL
        $supple_base_url = '';
        
        //1. サプリメンタルコンテンツ情報を取得する。
        $query = 'SELECT * '.
                 'FROM '.DATABASE_PREFIX.'repository_supple '.
                 'WHERE'.
                 ' item_id=?'.
                 ' AND'.
                 ' item_no=?'.
                 ' AND'.
                 ' is_delete=? ;';
                 
        $params = null; //初期化
        $params[] = $this->itemId;
        $params[] = $this->itemNo;
        $params[] = 0;
        $ret_supple = $this->Db->execute($query, $params);
        if ($ret_supple === false) {
            return "";
        }
        
        //2. 1で取得したサプリメンタルコンテンツ情報をキーとして、連携しているサプリメンタルWEKOから、RSS形式サプリメンタルコンテンツXMLを取得する。
        
        //サプリメンタルWEKOのBASE_URLを取得する
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name=?;";
        $params = null; //初期化
        $params[] = 'supple_weko_url';
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            return "";
        }
        $supple_base_url = $ret[0]['param_value'];
        // '/'を付ける
        if(substr($supple_base_url, -1, 1)!="/"){
            $supple_base_url .= "/";
        }
        
        // 1で取得したサプリメンタルコンテンツ情報の数分ループする。
        for($ii=0;$ii<count($ret_supple);$ii++){
            //連携しているサプリメンタルWEKOからXMLデータ(1件分)を取得
            $supple_xmldata = $this->getSuppleDataXMLData($supple_base_url, $ret_supple[$ii]['supple_weko_item_id']);
            //RSS形式XMLデータからアイテム(1件分)のデータを配列で取得する
            $supple_array = $this->setSuppleData($supple_base_url, $supple_xmldata, $ret_supple[$ii]);
            if($supple_array == false){
                continue;
            }
            // 2で取得したRSS形式サプリメンタルコンテンツXMLをOAI-ORE形式のXMLを作成する。
            if($this->itemId != null && $this->itemNo != null && $this->indexId == null) {
                //creatorは複数
                $xml .= $this->getItemEntryElement(
                                                 $supple_array['supple_weko_item_id'],      //サプリメンタルデータのitem_id
                                                 1,                                         //サプリメンタルデータのitem_no 1:固定
                                                 $supple_array['supple_title'],             //サプリメンタルデータのタイトル
                                                 $supple_array['supple_title_en'],          //サプリメンタルデータのタイトル(英)
                                                 $supple_array['supple_item_type'],         //サプリメンタルデータのniitype
                                                 $supple_array['supple_ins_date'],          //サプリメンタルデータの作成日時
                                                 $supple_array['supple_mod_date'],          //サプリメンタルデータの更新日時
                                                 1,                                        //サプリメンタルデータのindentNum
                                                 $supple_array['supple_base_url'],          //サプリメンタルWEKOのBASE_URL
                                                 $supple_array['supple_creator'],           //サプリメンタルデータの作成者
                                                 $supple_array['supple_detail_url']         //サプリメンタルデータの詳細画面のURL
                                                 );
                                                 
            }
        }
        
        return $xml;
    }
    /**
     * 連携しているサプリメンタルWEKOから、RSS形式サプリメンタルコンテンツXMLデータを取得
     * @param サプリメンタルWEKOのBASE_URL
     * @param サプリデータのitem_id
     * @return array 
     */
    private function getSuppleDataXMLData($supple_base_url, $item_ids){
        
        if($supple_base_url == ""){
            $this->Session->setParameter("supple_error", 1);
            return array();
        }
        $supple_base_url .= "?action=repository_opensearch&prefix=false&format=rss&item_ids="."$item_ids";
        
        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        $proxy = $this->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        $http = new HTTP_Request($supple_base_url, $option);
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']); 
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        
        /////////////////////////////
        // run HTTP request 
        /////////////////////////////
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response)) { 
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得 
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得 
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得 
            $charge_Cookies = $http->getResponseCookies();// クッキーを取得 
        }
        // get response
        $response_xml = $charge_body;

//XML出力用 BASE_URL配下に出力されます
//        $fno = fopen("test".$item_ids.".txt", 'w');
//        fwrite($fno, $response_xml);
//        fclose($fno);

        /////////////////////////////
        // parse response XML
        /////////////////////////////
        try{
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $response_xml, $vals );
            if($rtn == 0){
                $this->Session->setParameter("supple_error", 2);
                return array();
            }
            xml_parser_free($xml_parser);
        } catch(Exception $ex){
            $this->Session->setParameter("supple_error", 2);
            return array();
        }
        
        return $vals;
    }
    /*
     * RSS形式XMLからアイテムのデータを1件ずつ配列で取得
     * @param string xml RSS形式XML
     * @return array $return_array サプリデータ
     */
    private function setSuppleData($supple_base_url, $supple_xml_data, $supple_item){
        //戻り値の宣言
        $supple_array = array('supple_weko_item_id'=>'',
                            'supple_title'=>'',
                            'supple_title_en'=>'',
                            'supple_item_type'=>'',
                            'supple_creator'=>array(),
                            'supple_ins_date'=>'',
                            'supple_mod_date'=>'',
                            'supple_detail_url'=>'');
        
        //下記4項目をサプリWEKOから取得する
        //niitype
        //author…
        //updated
        //created…
        //詳細URL
        $supple_xml_array = array('type'=>'', 'creator'=>array(), 'createdate'=>'', 'modificationdate'=>'', 'supple_detail_url'=>'');
        
        $isReadStart = false;   //読み出し開始フラグ
        $isReadStop = false;    //読み出し終了フラグ
        foreach($supple_xml_data as $val){
            switch($val['tag']){
                case "ITEM":
                    //typeがopenで<ITEM ...>開始
                    if($val['type'] == 'open'){
                        //読み出し開始
                        $isReadStart = true;
                        $isReadStop = false;
                    }
                    //typeがcloseで<ITEM .../>終了
                    else if($val['type'] == 'close'){
                        //読み出し終了
                        $isReadStart = false;
                        $isReadStop = true;
                    }
                    break;
                case "PRISM:AGGREGATIONTYPE":
                    if($isReadStart == true){
                        $supple_xml_array['type']= $val['value'];
                    }
                    break;
                case "DC:CREATOR":
                    if($isReadStart == true){
                        array_push($supple_xml_array['creator'],$val['value']);
                    }
                    break;
                case "PRISM:CREATIONDATE":
                    if($isReadStart == true){
                        $supple_xml_array['createdate']= $val['value'];
                    }
                    break;
                case "PRISM:MODIFICATIONDATE":
                    if($isReadStart == true){
                        $supple_xml_array['modificationdate']= $val['value'];
                    }
                    break;
                case "LINK":
                    if($isReadStart == true){
                        $supple_xml_array['supple_detail_url']= $val['value'];
                    }
                    break;
                default :
                    break;
            }
            
            //読み出しが終了し、かつ、いづれかが設定されていたらデータを生成する。
            if( ($isReadStart == false && $isReadStop == true)
                && ($supple_xml_array['type']!='' 
                || count($supple_xml_array['creator'])>0
                || $supple_xml_array['createdate']!='' 
                || $supple_xml_array['modificationdate']!=''
                || $supple_xml_array['supple_detail_url']!=''))
            {
                //自分のDBに登録されているサプリコンテンツ情報
                $supple_array['supple_base_url'] = $supple_base_url;
                $supple_array['supple_weko_item_id'] = $supple_item['supple_weko_item_id'];
                $supple_array['supple_title'] = $supple_item['supple_title'];
                $supple_array['supple_title_en'] = $supple_item['supple_title_en'];
                $supple_array['supple_detail_url'] = $supple_item['uri'];
                //HttpRequestから取得したサプリコンテンツ情報
                $supple_array['supple_item_type'] = $supple_xml_array['type'];
                $supple_array['supple_creator'] = $supple_xml_array['creator'];
                $supple_array['supple_ins_date'] = $supple_xml_array['createdate'];
                $supple_array['supple_mod_date'] = $supple_xml_array['modificationdate'];
                $supple_array['supple_detail_url'] = $supple_xml_array['supple_detail_url'];
                
                break;
            }
        }
        
        return $supple_array;
    }
    //2013/3/12 Add jin サプリメンタルコンテンツ情報のXMLを作成する処理--end--

	
	function getDCCategoryElement($label, $indentNum)
	{
		// インデント調整
		$indentParent = $this->getTabIndent($indentNum);
		$indent = $indentParent. $this->TAB_SHIFT;
		
		// 開始
		$out = $indentParent . '<atom:category'.$this->LF;
		// scheme
		$scheme = 'http://ju.nii.ac.jp/junii2/';
		$out .= $indent. 'scheme="'.$scheme.'"'. $this->LF;
		// term
		$term = 'term="http://ju.nii.ac.jp/junii2/'.$label.'"';
		$out .= $indent.$term.$this->LF;
		// label
		$out .= $indent. 'label="'.$label.'"'.$this->LF;
		// 終了
		$out .= $indentParent . ' />'.$this->LF;
		
		return $out;
	}
	
	function getTabIndent($indentNum){
		$indentParent = '';
		for($i=0; $i<$indentNum; $i++) {
			$indentParent.=$this->TAB_SHIFT;
		} 
		return $indentParent;
	}
	
	function dateChg ($tmp) {
		global $debug;
		if (ereg("^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$", $tmp)) {
			$tmp_date = $tmp;
		} else {
			$tmp_yyyy = substr($tmp,0,4);
			$tmp_mm = substr($tmp,4,2);
			$tmp_dd = substr($tmp,6,2);
			$tmp_hh = substr($tmp,8,2);
			$tmp_mm2 = substr($tmp,10,2);
			$tmp_ss = substr($tmp,12);
			if (ereg("(\+[0-9]+|-[0-9]+)$", $tmp, $regs)) {
				$tmp_tz = $regs[1] * -1;
			} else {
				$tmp_tz = 0;
			}
			
			if ($debug==1) {
				print "DATE=$tmp=".$tmp_yyyy."-".$tmp_mm."-".$tmp_dd." ".$tmp_hh.":".$tmp_mm2.":".$tmp_ss."<BR>\n";
			}
			$DATE = new Date($tmp_yyyy.'-'.$tmp_mm.'-'.$tmp_dd.' '.$tmp_hh.':'.$tmp_mm2.':'.$tmp_ss);
			$tmp_date = $DATE->getDate(DATE_FORMAT_ISO);
		}
		
		return $tmp_date;
	}
}
?>
