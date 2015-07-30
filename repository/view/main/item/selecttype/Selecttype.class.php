<?php
// --------------------------------------------------------------------
//
// $Id: Selecttype.class.php 39149 2014-07-28 08:37:06Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * [[アイテム登録：アイテムタイプ選択画面用VIEW]]
 *
 * @package     [[package名]]
 * @access      public
 * @version 1.0 新規作成
 *          2.0 登録フロー表示改善対応 2008/06/26 Y.Nakao  
 */
class Repository_View_Main_Item_Selecttype extends RepositoryAction
{
	// メンバ変数
	/*
	 * item type data
	 * 0: item type id
	 * 1: item type name
	 * 2: flag thag the item type is able to grant doi or not
	 *    can grant:1, cannot grant:0
	 */
	var $itemtype_data= array();
	var $error_msg = array();		// エラーメッセージ
	var $itemtype_file = array();  // アイテムタイプの属性にファイルが含まれているか否か

    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
	
	/**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {    	
    	$istest = true;				// テスト用フラグ
       	try {    		
	        //アクション初期化処理
	        $result = $this->initAction();
    		if ( $result === false ) {
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
	        }
                
	    	// タブ切替時にセッションの初期化
	    	$this->Session->removeParameter("edit_flag");
	    	$this->Session->removeParameter("edit_item_id");
	    	$this->Session->removeParameter("edit_item_no");
	    	$this->Session->removeParameter("edit_start_date");
	    	$this->Session->removeParameter("delete_file_list");
	    	$this->Session->removeParameter('item_pub_date');
	    	$this->Session->removeParameter('item_keyword');
	    	$this->Session->removeParameter("item_type_all");
	    	$this->Session->removeParameter("item_attr_type");
	    	$this->Session->removeParameter("item_num_cand");
	    	$this->Session->removeParameter("option_data");
	    	$this->Session->removeParameter("isfile");
	    	$this->Session->removeParameter("item_num_attr");
	    	$this->Session->removeParameter("item_attr");
	    	$this->Session->removeParameter("base_attr");
	    	$this->Session->removeParameter("indice");
	    	$this->Session->removeParameter("link");
	    	$this->Session->removeParameter("link_search");
	    	$this->Session->removeParameter("link_searchkeyword");
	    	$this->Session->removeParameter("open_node_index_id_link");
	    	$this->Session->removeParameter("open_node_index_id_index");
	    	$this->Session->removeParameter("license_master");
	    	$this->Session->removeParameter("error_msg");
			// 登録フローに表示されるファイル登録部分の改善対応 update 2008/06/26 Y.Nakao --start--
	    	$this->Session->removeParameter("attr_file_flg");
			// 登録フローに表示されるファイル登録部分の改善対応 update 2008/06/26 Y.Nakao --end--
			$this->Session->removeParameter("doi_itemtype_flag");
			
            // Add e-person 2013/11/20 R.Matsuura --start--
            $this->Session->removeParameter("feedback_mailaddress_str");
            $this->Session->removeParameter("feedback_mailaddress_author_str");
            $this->Session->removeParameter("feedback_mailaddress_array");
            $this->Session->removeParameter("feedback_mailaddress_author_array");
            // Add e-person 2013/11/20 R.Matsuura --end--
			
	    	// Add file price Y.Nakao 2008/08/28 --start--
			$this->Session->removeParameter("all_group");
			$this->Session->removeParameter("user_group");
			// Add file price Y.Nakao 2008/08/28 --end--

			// change index tree 2008/12/03 Y.Nakao --start--
	    	$this->Session->removeParameter("view_open_node_index_id_insert_item");
	    	$this->Session->removeParameter("view_open_node_index_id_item_link");
	    	// change index tree 2008/12/03 Y.Nakao --end--
				    	    	
	    	// 既存の全アイテムタイプをDBから取得
	       	$query = "SELECT * ".
	       			 "FROM ". DATABASE_PREFIX ."repository_item_type ".		// アイテムタイプテーブル
	       			 "WHERE is_delete = ?; ";			// 削除フラグ
	       	$params = null;
	       	$params[] = 0;		
	    	//　SELECT実行
	        $item_type = $this->Db->execute($query, $params);
	 		if($item_type === false){
	    		$errNo = $this->Db->ErrorNo();
			    $errMsg = $this->Db->ErrorMsg();
			    $this->Session->setParameter("error_msg",$errMsg);
	    		return 'error';
	    	}
	    	// 主キーとアイテム名を抜き出し
	    	$this->itemtype_data = array();
	    	// 登録フローに表示されるファイル登録部分の改善対応 2008/06/26 Y.Nakao --start--
	    	// default item type 2008/09/17 Y.Nakao --start--
	    	$default_itemtype = array();
   			$create_itemtype = array();
   			$default_file = array();
   			$create_file = array();
	    	for($ii=0; $ii<count($item_type); $ii++) {
				// 現アイテムタイプの属性を取得
	    		$query = "SELECT INPUT_TYPE ".
						 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
						 "WHERE item_type_id = ? ".
	    				 "AND is_delete = ?; ";
	    		$params = null;
	    		$params[] = $item_type[$ii]['item_type_id'];
	    		$params[] = 0;
	    		$item_attr = $this->Db->execute($query, $params);
	    		if($item_attr === false){
	    			$errNo = $this->Db->ErrorNo();
			    	$errMsg = $this->Db->ErrorMsg();
			    	$this->Session->setParameter("error_msg",$errMsg);
	    			return 'error';
	    		}
	    		$attr_file = 0;
	    		for($jj=0; $jj<count($item_attr); $jj++){
	    			if( strcmp($item_attr[$jj]['INPUT_TYPE'], "file") == 0 || 
	    				strcmp($item_attr[$jj]['INPUT_TYPE'], "file_price") == 0 || // Add file price Y.Nakao 2008/08/28
	    				strcmp($item_attr[$jj]['INPUT_TYPE'], "thumbnail") == 0){
	    				$attr_file = 1;
	    				break;
	    			}
	    		}
	    		// default item type show top
	    		if($item_type[$ii]['item_type_id']>1000){
                    if($item_type[$ii]['item_type_id']<20001)
                    {
                        array_push($default_itemtype,
                            array($item_type[$ii]['item_type_id'], $item_type[$ii]['item_type_name']));
                        array_push($default_file, $attr_file);
                    }
	    		} else {
	    			array_push($create_itemtype,
	    				array($item_type[$ii]['item_type_id'], $item_type[$ii]['item_type_name']));
	    			array_push($create_file, $attr_file);
	    		}
	    		
	    	}
	    	$this->itemtype_data = array();
    		$this->itemtype_data = array_merge($default_itemtype, $create_itemtype);
    		$this->itemtype_file = array();
    		$this->itemtype_file = array_merge($default_file, $create_file);
    		// default item type 2008/09/17 Y.Nakao --end--
			// 登録フローに表示されるファイル登録部分の改善対応 2008/06/26 Y.Nakao --end--
	    	
    		for($cnt = 0; $cnt < count($this->itemtype_data); $cnt++)
    		{
                $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
                if($CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_JALC_DOI) || 
                   $CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_CROSS_REF))
                {
                    array_push($this->itemtype_data[$cnt], 1);
                }
                else
                {
                    array_push($this->itemtype_data[$cnt], 0);
                }
	    	}
	    	
	    	// セッションからエラーメッセージのコピー
	    	$this->error_msg = $this->Session->getParameter("error_msg");
   		 	$this->Session->removeParameter("error_msg");    
        
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
   		 	
       	    // アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
			}
	    	return 'success';
        } catch ( RepositoryException $Exception) {
    	    //エラーログ出力
        	$this->logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
	        	$Exception->getCode(),			//ログID
	        	$Exception->getMessage(),		//主メッセージ
	        	$Exception->getDetailMsg() );	//詳細メッセージ	        
        	//アクション終了処理
      		$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
	        //異常終了
	        $this->Session->setParameter("error_msg", $user_error_msg);
    	    return "error";
		}
    }
}
?>
