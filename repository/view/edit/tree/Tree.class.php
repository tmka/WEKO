<?php
// --------------------------------------------------------------------
//
// $Id: Tree.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
class Repository_View_Edit_Tree extends RepositoryAction
{
	// member
	var $error_flg = null;
	var $view_popup = null;
	
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    public $tree_error_msg = '';
	
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
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
	        }

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
			
			//ツリー情報をセッションに設定する処理
			// change index tree view action 2008/12/04 Y.Nakao --start--
			//$this->setIndexTreeData2Session(true);
			// change index tree view action 2008/12/04 Y.Nakao --end--
			$this->Session->removeParameter("error_code");
			$this->Session->removeParameter("error_msg");
			
			$this->tree_error_msg = $this->Session->getParameter("tree_error_msg");
			$this->Session->removeParameter("tree_error_msg");
			$this->Session->removeParameter("MyPrivateTreeRootId");		// Add remove privateTree edit flag  K.Matsuo 2013/04/15
			// change index tree 2008/12/08 Y.Nakao --start--
			// ツリー情報保持処理追加 2008/07/09 Y.Nakao --start--
			//タブ押下の場合,open index情報削除
			//if( !($this->Session->getParameter("edit_tree_from_action")) ){
				if($this->Session->getParameter("edit_tree_continue")==null || $this->Session->getParameter("edit_tree_continue")==""){
					// for open tree node
					// string index_id1,index_id2,index_id3,...
					$this->Session->removeParameter("view_open_node_index_id_edit");
					$this->Session->removeParameter("view_open_node_index_id_editPrivatetree");
					// for tree mod date
					$this->Session->setParameter("tree_mod_Date", $this->TransStartDate);
				}
				// for select index focus
				// string now focus index_id
				$this->Session->removeParameter("edit_index");
				// for edit contine flg
				$this->Session->removeParameter("edit_tree_continue");
				// 更新成功ポップアップ表示用 for update OK popup 
				$this->view_popup = $this->Session->getParameter("repository_edit_update");
				$this->Session->removeParameter("repository_edit_update");
				// get lang resource
				$this->setLangResource();
			//}
			//$this->Session->removeParameter("edit_tree_from_action",0);
			// ツリー情報保持処理追加 2008/07/09 Y.Nakao --end--
			// change index tree 2008/12/08 Y.Nakao --end--
	        
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
