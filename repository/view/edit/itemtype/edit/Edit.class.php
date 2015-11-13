<?php
// --------------------------------------------------------------------
//
// $Id: Edit.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// Set help icon setting 2010/02/10 K.Ando --start--
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
// Set help icon setting 2010/02/10 K.Ando --end--
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * repositoryモジュール アイテムタイプ作成 編集画面表示前に呼ばれるアクション
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_View_Edit_Itemtype_Edit extends RepositoryAction 
{	
	// 使用コンポーネントを受け取るため
	var $Session = null;			// sessionコンポーネント
	var $Db = null;
	//リクエストパラメータを受け取るため

	// メタデータ表示用メンバ
	// (追加項目分はここに詰めるが、基本属性分も他のメンバに持たせておく必要あり多分。)
	var $metadata_array = null;		// メタデータ表示内容配列
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータタイプ配列
	var $metadata_required = null;	// メタデータ必須フラグ列
	var $metadata_disp = null;		// メタデータ一覧表示フラグ列
	var $item_type_name = null;		//前画面で入力したアイテムタイプ名
	var $metadata_candidate = null;	// 選択肢
	var $metadata_plural = null;	// メタデータ複数可否 2008/03/04 追加
	var $metadata_newline = null;	// メタデータ改行指定 2008/03/13
	var $metadata_hidden = null;	// メタデータ非表示指定 2009/01/27
	
	var $item_type_data = null;		// 既存アイテムタイプロード用のため追加 2008/09/01 Y.Nakao

    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
	
//	var $menba = null;
//	var $menba2 = null;

    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        
        // Add theme_name for image file Y.Nakao 2011/08/03 --start--
        $this->setThemeName();
        // Add theme_name for image file Y.Nakao 2011/08/03 --end--
        
    	// 
    	// メタデータ入力情報初期設定
		//
    	$this->metadata_array = array();
    	for($ii=0; $ii<$this->Session->getParameter("metadata_num"); $ii++) {
 			array_push($this->metadata_array , array("", "text", 0, 0, 0, 0, 0, $ii));
    	}
    	
    	//
    	// メタデータ情報がセッションにある場合は値をコピー
    	//
    	
    	// 項目名
    	$this->metadata_title = array();
    	$this->metadata_title = $this->Session->getParameter("metadata_title");
   	   	for($ii=0; $ii<count($this->metadata_title); $ii++) {
    	   	$this->metadata_array[$ii][0] = $this->metadata_title[$ii];
    	}
    	// 属性
    	$this->metadata_type = array();
    	$this->metadata_type = $this->Session->getParameter("metadata_type");
   	   	for($ii=0; $ii<count($this->metadata_type); $ii++) {
    	   	$this->metadata_array[$ii][1] = $this->metadata_type[$ii];
    	}
    	// 必須
    	$this->metadata_required = array();
    	$this->metadata_required = $this->Session->getParameter("metadata_required");
   	   	for($ii=0; $ii<count($this->metadata_required); $ii++) {
    	   	$this->metadata_array[$ii][2] = $this->metadata_required[$ii];
    	}
    	// 一覧表示
        $this->metadata_disp = array();
    	$this->metadata_disp = $this->Session->getParameter("metadata_disp");
   	   	for($ii=0; $ii<count($this->metadata_disp); $ii++) {
    	   	$this->metadata_array[$ii][3] = $this->metadata_disp[$ii];
    	}
        // 複数可否
        $this->metadata_plural = array();
    	$this->metadata_plural = $this->Session->getParameter("metadata_plural");
   	   	for($ii=0; $ii<count($this->metadata_plural); $ii++) {
    	   	$this->metadata_array[$ii][4] = $this->metadata_plural[$ii];
    	}
	    // 改行指定
        $this->metadata_newline = array();
    	$this->metadata_newline = $this->Session->getParameter("metadata_newline");
   	   	for($ii=0; $ii<count($this->metadata_newline); $ii++) {
    	   	$this->metadata_array[$ii][5] = $this->metadata_newline[$ii];
    	}
    	// Add hidden metadata 2009/01/28 A.Suzuki --start--
    	// 非表示指定
        $this->metadata_hidden = array();
    	$this->metadata_hidden = $this->Session->getParameter("metadata_hidden");
   	   	for($ii=0; $ii<count($this->metadata_hidden); $ii++) {
    	   	$this->metadata_array[$ii][6] = $this->metadata_hidden[$ii];
    	}
    	// Add hidden metadata 2009/01/28 A.Suzuki --end--
    	
    	// Extension Itemtype 2008/09/01 Y.Nakao --start-- 
    	// 既存の全アイテムタイプをDBから取得
		$result = $this->Db->selectExecute("repository_item_type", array('is_delete' => 0), array("item_type_id" => "ASC"));
        if($result === false) {
    		return 'error';
    	}
    	// アイテムタイプが0の場合はエラー用テンプレートに遷移
    	if( count($result)<1 ) {
    		return 'noitemtype';
    	}
   		// default item type is header   	
   		$default_itemtype = array();
   		$create_itemtype = array();
    	for($ii=0; $ii<count($result); $ii++) {
    		if($result[$ii]['item_type_id']>10000){
                if($result[$ii]['item_type_id']<20001)
                {
                    array_push($default_itemtype,
                    array($result[$ii]['item_type_id'], $result[$ii]['item_type_name']));
                }
    		} else {
    			array_push($create_itemtype,
    				array($result[$ii]['item_type_id'], $result[$ii]['item_type_name']));
    		}
    	}
    	$this->itemtype_data = array();
    	$this->itemtype_data = array_merge($default_itemtype, $create_itemtype);
    	// Extension Itemtype 2008/09/01 Y.Nakao --end--
    	
    	// Add id server connect check for "file_price" 2009/04/01 Y.Nakao --start--
    	// get prefixID
    	$this->Session->removeParameter("id_server");
		$this->Session->setParameter("id_server", 'false');

        $this->dbAccess = new RepositoryDbAccess($this->Db);
        $DATE = new Date();
        $this->TransStartDate = $DATE->getDate().".000";
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
        
        $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);

    	if( is_numeric($prefixID) ){
			$this->Session->setParameter("id_server", 'true');
		}
		$array_input_type = $this->Session->getParameter("metadata_type");
		$chk_file_price = 0;
		for($ii=0; $ii<count($array_input_type); $ii++){
	    	if($array_input_type[$ii] == "file_price"){
	    		$chk_file_price++;
	    		if($chk_file_price > 1){
	    			$this->Session->setParameter("error_code", 7);
	    		} else if($this->Session->getParameter("id_server") != "true"){
	    			$this->Session->setParameter("error_code", 7);
	    		}
	    	}
		}
    	// Add id server connect check for "file_price" 2009/04/01 Y.Nakao --end--

        // Set help icon setting 2010/02/10 K.Ando --start--
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
		if ( $result == false ){
			$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
		}
        // Set help icon setting 2010/02/10 K.Ando --end--
		
        return 'success';
    }
}
?>
