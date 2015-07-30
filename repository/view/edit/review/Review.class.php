<?php
// --------------------------------------------------------------------
//
// $Id: Review.class.php 1603 2010-10-05 06:03:04Z atsushi_suzuki $
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
class Repository_View_Edit_Review extends RepositoryAction
{
	// セッションとデータベースのオブジェクトを受け取る
    var $Session = null;
    var $Db = null;
	
    // htmlの表示に使用
    var $item_review_info = null;
    
    
    var $supple_review_info = null;
	var $review_active_tab	= null;	// 選択されているタブ情報 active tab info
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
	
	/**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
   		try {
    		//アクション初期化処理
	        $result = $this->initAction();
	        if ( $result === false ) {
	            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	            //$DetailMsg = null;                              //詳細メッセージ文字列作成
	            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
	        }
	        
	        // エラーコード開放
	        $this->Session->removeParameter("error_code");
	        $this->Session->removeParameter("supple_error_code");
			$this->Session->removeParameter("error_msg");
	        
	        // 初期化
	        $this->item_review_info = array();
	        $this->supple_review_info = array();
	        $array_item_id_no_mod = array();
	        $array_supple_data = array();
	        
	        // 表示中タブ情報
	        if($this->review_active_tab == ""){
	        	$this->review_active_tab = $this->Session->getParameter("review_active_tab");
		        if($this->review_active_tab == ""){
	       			$this->review_active_tab = 0;
				}
	        }
			$this->Session->removeParameter("review_active_tab");

	        
	        // 全アイテムから承認待ちのデータを取得する
	        // ただし却下したものは取得しない
   			$query = "SELECT * ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE review_status = ? AND ".
                     "reject_status = ? AND ".
   					 "is_delete = ?; ";
    		$params = null;
            $params[] = 0;	// review_status
            $params[] = 0;	// reject_status
            $params[] = 0;	// is_delete
            //SELECT実行
            $result_Item = $this->Db->execute($query, $params);
            if($result_Item === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code", $errMsg);
                //エラー処理を行う
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
	        if(count($result_Item) == 0){
	        	// エラーコード: 承認待ちアイテムなし
	        	$this->Session->setParameter("error_code", 8);
	        } else {
				for($nCnt=0;$nCnt<count($result_Item);$nCnt++){
		   		   	$query = "SELECT * ".
		                     "FROM ". DATABASE_PREFIX ."repository_item_type ".
		                     "WHERE item_type_id = ? AND ".
		   					 "is_delete = ?; ";
		    		$params = null;
		            $params[] = $result_Item[$nCnt]['item_type_id'];	// item_type_id
		            $params[] = 0;	// is_delete
		            //SELECT実行
		            $result = $this->Db->execute($query, $params);
		            if($result === false){
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", $errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            // データ格納
		            array_push($this->item_review_info,
		            			array('title' => $result_Item[$nCnt]['title'],
		            				  'title_en' => $result_Item[$nCnt]['title_english'],
	          						  'item_type_name' => $result[0]['item_type_name'],
		            				  'item_id' => $result_Item[$nCnt]['item_id'],
		            				  'item_no' => $result_Item[$nCnt]['item_no']
	           					)
		            );
		            array_push($array_item_id_no_mod,
		            			array('item_id' => $result_Item[$nCnt]['item_id'],
		            				  'item_no' => $result_Item[$nCnt]['item_no'],
		            				  'mod_date' => $result_Item[$nCnt]['mod_date']
		            			)
		            );
				}
		        // item_id,item_no,更新日時をSessionに格納
				$this->Session->setParameter("item_id_no_mod_for_review", $array_item_id_no_mod);
	        }
			
			// Add supple review 2009/09/17 A.Suzuki --start--
			// 全サプリコンテンツから承認待ちのデータを取得する
	        // ただし却下したものは取得しない
   			$query = "SELECT * ".
                     "FROM ". DATABASE_PREFIX ."repository_supple ".
                     "WHERE supple_review_status = 0 ".
                     "AND supple_reject_status = 0 ".
   					 "AND is_delete = 0; ";
            //SELECT実行
            $result_supple = $this->Db->execute($query);
            if($result_supple === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code", $errMsg);
                //エラー処理を行う
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
	        if(count($result_supple) == 0){
	        	// エラーコード: 承認待ちサプリコンテンツなし
	        	$this->Session->setParameter("supple_error_code", 8);
	        } else {
				for($nCnt=0;$nCnt<count($result_supple);$nCnt++){
		   		   	$query = "SELECT * ".
		                     "FROM ". DATABASE_PREFIX ."repository_item ".
		                     "WHERE item_id = ? ".
		   		   			 "AND item_no = ? ".
		   					 "AND is_delete = 0; ";
		    		$params = null;
		            $params[] = $result_supple[$nCnt]['item_id'];	// item_id
		            $params[] = $result_supple[$nCnt]['item_no'];	// item_no
		            //SELECT実行
		            $result = $this->Db->execute($query, $params);
		            if($result === false){
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", $errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;                              //詳細メッセージ文字列作成
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
		                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
		                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
		                throw $exception;
		            }
		            // データ格納
		            array_push($this->supple_review_info,
		            			array('supple_title' => $result_supple[$nCnt]['supple_title'],
		            				  'supple_title_en' => $result_supple[$nCnt]['supple_title_en'],
	          						  'item_title' => $result[0]['title'],
		            				  'item_title_en' => $result[0]['title_english'],
		            				  'item_id' => $result_supple[$nCnt]['item_id'],
		            				  'item_no' => $result_supple[$nCnt]['item_no'],
		            				  'attribute_id' => $result_supple[$nCnt]['attribute_id'],
		            				  'supple_no' => $result_supple[$nCnt]['supple_no'],
		            				  'supple_url' => $result_supple[$nCnt]['uri']
	           					)
		            );
		            
		            array_push($array_supple_data,
		            			array('item_id' => $result_supple[$nCnt]['item_id'],
		            				  'item_no' => $result_supple[$nCnt]['item_no'],
		            				  'attribute_id' => $result_supple[$nCnt]['attribute_id'],
		            				  'supple_no' => $result_supple[$nCnt]['supple_no'],
		            				  'mod_date' => $result_supple[$nCnt]['mod_date']
		            			)
		            );
				}
		        // サプリコンテンツの情報をセッションに格納
				$this->Session->setParameter("supple_data_for_review", $array_supple_data);
	        }
			
	   		//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
	 		if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;                              //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
				//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				throw $exception;
			}
			// Add review mail setting 2009/09/30 Y.Nakao --start--
			$this->setLangResource();
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

			return 'success';
    	}
		catch ( RepositoryException $Exception) {
		
    	    //エラーログ出力
    	    /*
        	logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
	        	$Exception->getCode(),			//ログID
	        	$Exception->getMessage(),		//主メッセージ
	        	$Exception->getDetailMsg() );	//詳細メッセージ
	        */
        	//アクション終了処理
      		$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
        
	        //異常終了
    	    return "error";
		}
    }
}
?>
