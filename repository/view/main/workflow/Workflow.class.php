<?php
// --------------------------------------------------------------------
//
// $Id: Workflow.class.php 16916 2012-04-06 05:10:11Z ayumi_jin $
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
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_View_Main_Workflow extends RepositoryAction
{
	// 使用コンポーネントを受け取るため
	var $Session = null;
	var $Db = null;
	//var $request = null;
	
	// html表示用
	// Add for workflow separate 2009/01/27 Y.Nakao --start--
	// 表示アイテム情報 item info for display
	var $item_unregistered = array();	// unregistered
	var $item_review	   = array();	// review
	var $item_accepted	 = array();	// accepted
	var $item_reject	   = array();	// reject
	
	// 選択されているタブ情報 active tab info
	var $workflow_active_tab	= null;
	
	// 1ページに表示するアイテムの数 item number for display 1 page
	var $page_item		 = 20;
	
	// 各タブのページ数 all page number
	var $page_num_unregistered = 0;
	var $page_num_review	   = 0;
	var $page_num_accepted	   = 0;
//	var $page_num_reject	   = null;
	
	// 表示しているページ番号 display page number
	var $page_disp_unregistered = null;
	var $page_disp_review	    = null;
	var $page_disp_accepted	    = null;
//	var $page_disp_reject	    = null;
	
	// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --start--
	// ページ番号表示用 for display page number
//	var $page_unregistered = null;
//	var $page_review	   = null;
//	var $page_accepted	   = null;
//	var $page_reject	   = null;
	// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --end--
	
	// error_msg
	var $error_msg		 = "";
	// Add for workflow separate 2009/01/27 Y.Nakao --end--
	
	// Add review mail setting 2009/09/30 Y.Nakao --start--
	var $review_mail_flg = 0;
	var $review_result_mail = 0;
	// Add review mail setting 2009/09/30 Y.Nakao --end--
	
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
	
	/**
	 * [[ワークフロー表示用処理]]
	 * @access  public
	 */
	function execute()
	{
		// check Session and Db Object
		if($this->Session == null){
			$container =& DIContainerFactory::getContainer();
	        $this->Session =& $container->getComponent("Session");
		}
		if($this->Db== null){
			$container =& DIContainerFactory::getContainer();
			$this->Db =& $container->getComponent("DbObject");
		}
		
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
        
        
        // ログインチェック
        if($this->Session->getParameter("_user_id") == '0'){
        	$this->Session->setParameter("login_redirect", "workflow");
        	$this->Session->removeParameter("login_redirect_flag");
        	return "login";
        } else {
            $auth_id = $this->Session->getParameter("_auth_id");
            if($auth_id < REPOSITORY_ITEM_REGIST_AUTH){
                $this->setLangResource();
                echo $this->Session->getParameter("smartyAssign")->getLang("_invalid_auth");
                $this->failTrans();
                exit();
            }
        }
		
		// free error session
		$this->Session->removeParameter("error_msg");
		
		// Add for workflow separate 2009/01/27 Y.Nakao --start--
		// 初期化  init
		$this->item_unregistered = array();
		$this->item_review = array();
		$this->item_accepted = array();
		$this->item_reject = array();
		if($this->workflow_active_tab == ""){
			$this->workflow_active_tab = $this->Session->getParameter("repository_workflow_active_tab");
			if($this->workflow_active_tab == ""){
				$this->workflow_active_tab = 0;
			}
		}
		$this->Session->removeParameter("repository_workflow_active_tab");
		// 表示するページ番号 display page number
		if($this->page_disp_unregistered == ""){
			$this->page_disp_unregistered = "1";
		}
		if($this->page_disp_review == ""){
			$this->page_disp_review = "1";
		}
		if($this->page_disp_accepted == ""){
			$this->page_disp_accepted = "1";
		}
//		if($this->page_disp_reject == ""){
//			$this->page_disp_reject = "1";
//		}
			
		// ユーザの権限により表示するデータを決定する
		$user_id = $this->Session->getParameter("_user_id");					// ユーザID
		$user_name = $this->Session->getParameter("_handle");					// ユーザのハンドル名(使わないと思うが)
		$user_auth_id = $this->Session->getParameter("_user_auth_id");			// 会員の会員権限ID
		$role_auth_id = $this->Session->getParameter("_role_auth_id");			// 会員のロール権限ID
		$system_user_flag = $this->Session->getParameter("_system_user_flag");	// システム管理者ならば1
		$timezone_offset = $this->Session->getParameter("_timezone_offset");	// ???
		
		//////// 表示用のデータを取得 get display data /////////////////
		// 登録中 unregistered item data (却下も含む)
		$ret_unregistered = $this->getUserItemData($user_id, "", "");
		if($ret_unregistered === false){
			// roll back
			$this->failTrans();
			
            //Connect From SPhone
            if($this->smartphoneFlg == true){
                return 'error_sp';
            }
            //Connect From PC
            else{
                return 'error';
            }
		}
		$this->item_unregistered = array();
		for($ii=0; $ii<$this->page_item; $ii++){
			$cnt = ($this->page_disp_unregistered-1)*$this->page_item + $ii;
			if($cnt == count($ret_unregistered)){
				break;
			}
			array_push($this->item_unregistered, $ret_unregistered[$cnt]);
		}
		//$this->page_num_unregistered = round(count($ret_unregistered)/$this->page_item + 0.5);
		// 表示されるページ数計算
        $this->page_num_unregistered = (int)(count($ret_unregistered)/$this->page_item);
        if((count($ret_unregistered)%$this->page_item) != 0){
        	$this->page_num_unregistered++;
        }
		
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --start--
//		$this->page_unregistered = array();
//		for($ii=2; $ii<$this->page_num_unregistered; $ii++){
//			array_push($this->page_unregistered, $ii);
//		}
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --end--
		
		// 承認待 review item data
		$ret_review = $this->getUserItemData($user_id, "0", "0");
		if($ret_review === false){
			// roll back
			$this->failTrans();
            //Connect From SPhone
            if($this->smartphoneFlg == true){
                return 'error_sp';
            }
            //Connect From PC
            else{
                return 'error';
            }
		}
		$this->item_review = array();
		for($ii=0; $ii<$this->page_item; $ii++){
			$cnt = ($this->page_disp_review-1)*$this->page_item + $ii;
			if($cnt == count($ret_review)){
				break;
			}
			array_push($this->item_review, $ret_review[$cnt]);
		}
		//$this->page_num_review = round(count($ret_review)/$this->page_item + 0.5);
		// 表示されるページ数計算
        $this->page_num_review = (int)(count($ret_review)/$this->page_item);
        if((count($ret_review)%$this->page_item) != 0){
        	$this->page_num_review++;
        }
		
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --start--
//  	  	$this->page_review = array();
//		for($ii=2; $ii<$this->page_num_review; $ii++){
//			array_push($this->page_review, $ii);
//		}
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --end--
		
		// 承認済　accepted item data
		$ret_accepted = $this->getUserItemData($user_id, "1", "0");
		if($ret_accepted === false){
			// roll back
			$this->failTrans();
			
            //Connect From SPhone
            if($this->smartphoneFlg == true){
                return 'error_sp';
            }
            //Connect From PC
            else{
                return 'error';
            }
		}
		$this->item_accepted = array();
		for($ii=0; $ii<$this->page_item; $ii++){
			$cnt = ($this->page_disp_accepted-1)*$this->page_item + $ii;
			if($cnt == count($ret_accepted)){
				break;
			}
			array_push($this->item_accepted, $ret_accepted[$cnt]);
		}
		//$this->page_num_accepted = round(count($ret_accepted)/$this->page_item + 0.5);
		// 表示されるページ数計算
        $this->page_num_accepted = (int)(count($ret_accepted)/$this->page_item);
        if((count($ret_accepted)%$this->page_item) != 0){
        	$this->page_num_accepted++;
        }
		
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --start--
//		$this->page_accepted = array();
//		for($ii=2; $ii<$this->page_num_accepted; $ii++){
//			array_push($this->page_accepted, $ii);
//		}
		// ページ番号表示処理修正のため削除 2009/08/07 A.Suzuki --end--
		
//		// 却下 reject item data
//		$ret_reject = $this->getUserItemData($user_id, "0", "1");
//		if($ret_reject === false){
//			// roll back
//			$this->failTrans();
//			return 'error';
//		}
//		$this->item_reject = array();
//		for($ii=0; $ii<$this->page_item; $ii++){
//			$cnt = ($this->page_disp_reject-1)*$this->page_item + $ii;
//			if($cnt == count($ret_reject)){
//				break;
//			}
//			array_push($this->item_reject, $ret_reject[$cnt]);
//		}
//		$this->page_num_reject = intval(count($ret_reject)/$this->page_item + 0.5);
//		$this->page_reject = array();
//		for($ii=2; $ii<$this->page_num_reject; $ii++){
//			array_push($this->page_reject, $ii);
//		}
		
		// Add for workflow separate 2009/01/27 Y.Nakao --end--
		
        
        // Add review mail setting 2009/09/30 Y.Nakao --start--
        $this->setUserMailSetting();
		// Add review mail setting 2009/09/30 Y.Nakao --end--
		
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
        
        //アクション終了処理
		$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
 		if ( $result === false ) {
			$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
			//$DetailMsg = null;							  //詳細メッセージ文字列作成
			//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
			//$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
			throw $exception;
		}

        //Connect From SPhone
        if($this->smartphoneFlg == true){
            return 'success_sp';
        }
        //Connect From PC
        else{
            return 'success';
        }
		//return 'success';
	}
	
	/**
	 * $user_idのユーザが作成したアイテムの情報を取得する
	 *
	 * @param $user_id ユーザID userID
	 * @param $s_num 開始番号 start number
	 * @param $review_status 査読状況 review status
	 * 						 -1 : 登録中 unregistered
	 * 						  0 : 承認待 wating review
	 * 						  1 : 承認済 accepted
	 * @param reject_status 却下状況 reject status
	 * 						0 : 却下されていない
	 * 						1 : 却下
	 * @return item data
	 */
	function getUserItemData($user_id, $review_status, $reject_status){
		$query = "SELECT  ITEM.item_id, ".			// item id
						" ITEM.item_no, ".			// item no	
						" ITEM.title, ".			// title
						" ITEM.title_english, ".	// title_english
						" ITEM.shown_status, ".		// shown status
						" ITEM.reject_reason, ".	// reject reason
						" ITEM.mod_date, ".			// mod date
						" ITEMTYPE.item_type_name ".// item type name
				 "FROM ". DATABASE_PREFIX ."repository_item ITEM, ".				// アイテムテーブル
				 		  DATABASE_PREFIX ."repository_item_type ITEMTYPE ".	// アイテムタイプテーブル
				 "WHERE (ITEM.ins_user_id = '". $user_id ."' OR ITEM.mod_user_id = '". $user_id ."' ) ".	// 作成者が自分
				 " AND ITEM.is_delete = '0' ";			// 削除されていない
		if($review_status!=null && $review_status!=""){
			$query .= " AND ITEM.review_status = '".$review_status ."' ";	// 査読状況
		}
		if($reject_status!=null && $reject_status!=""){
		 	$query .= " AND ITEM.reject_status = '".$reject_status."' ";	// 却下状況
		}
		if($review_status==null && $review_status=="" &&
			$reject_status==null && $reject_status==""){
			$query .= 	" AND ( (ITEM.review_status = '-1' AND ITEM.reject_status = '')".
						" OR (ITEM.review_status = '0' AND ITEM.reject_status = '1')".
						" ) ";
		}
		$query .= " AND ITEMTYPE.item_type_id = ITEM.item_type_id ".
				  "order by ITEM.item_id; ";			// アイテムIDでソート
		//　SELECT実行
		$item = $this->Db->execute($query);
		if($item === false){
			$this->error_msg = $this->Db->ErrorMsg();
			//アクション終了処理
			$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
			return false;
		}
		// reject reason make new line
		if($review_status==null && $review_status=="" &&
			$reject_status==null && $reject_status==""){
			for($ii=0; $ii<count($item); $ii++){
				if($item[$ii]["reject_reason"] != ""){
					$reject_reason = array();
					$item[$ii]["reject_reason"] = str_replace("\r\n", "\n",  $item[$ii]["reject_reason"]);
					$item[$ii]["reject_reason"] = str_replace("\n", "\n",  $item[$ii]["reject_reason"]);
					$reject_reason = explode("\n", $item[$ii]["reject_reason"]);
					$item[$ii]["reject_reason"] = array();
					$item[$ii]["reject_reason"] = $reject_reason;
				}
			}
		}
		
		return $item;
	}
	
	// Add review mail setting 2009/09/30 Y.Nakao --start--
	/**
	 * set user mail setting
	 * ユーザのメール設定状況を取得する
	 *
	 */
	function setUserMailSetting(){
		// get user id
		// ユーザIDを取得
		$user_id = $this->Session->getParameter("_user_id");
		// ユーザのメールアドレスをチェック check user's mail address
		$this->review_mail_flg = 0;
		$query = "SELECT * FROM ".DATABASE_PREFIX."users_items_link ".
				" WHERE user_id = ? ".
				" AND (item_id = ? OR item_id = ?) ".
				" AND email_reception_flag = ? ".
				" AND content != ''; ";
		$param = array();
		$param[] = $user_id;
		$param[] = 5;	// email address
		$param[] = 6;	// mobile email address
		$param[] = 1;	// email reception = 1, not = 0
		$result = $this->Db->execute($query, $param);
		if($result === false){
			$this->error_msg = $this->Db->ErrorMsg();
			//アクション終了処理
			$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
			return false;
		}
		if(count($result) > 0){
			$this->review_mail_flg = 1;
		}
		// get user mail setting
		//　ユーザが通知メールを受け取るかどうかの設定を読み込む
		$query = "SELECT contents_mail_flg FROM ".DATABASE_PREFIX."repository_users ".
				" WHERE user_id = ? ".
				" AND is_delete = ?; ";
		$param = array();
		$param[] = $user_id;
		$param[] = 0;
		$result = $this->Db->execute($query, $param);
		if($result === false){
			$this->error_msg = $this->Db->ErrorMsg();
			//アクション終了処理
			$result = $this->exitAction();	 //トランザクションが成功していればCOMMITされる
			return false;
		}
		if(count($result) == 1){
			$this->review_result_mail = $result[0]['contents_mail_flg'];
		}
	}
	// Add review mail setting 2009/09/30 Y.Nakao --end--
}
?>
