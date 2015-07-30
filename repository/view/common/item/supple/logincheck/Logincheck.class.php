<?php
// --------------------------------------------------------------------
//
// $Id: Logincheck.class.php 36236 2014-05-26 07:53:04Z satoshi_arata $
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
class Repository_View_Common_Item_Supple_Logincheck extends RepositoryAction
{
	// リクエストパラメタ
	var $ej_item_id = null;				// EJWEKOアイテムID
	var $ej_item_no = null;				// EJWEKOアイテム通番
	var $ej_workflow_flag = null;		// EJWEKOのワークフローから遷移してきたことを示す
	var $ej_workflow_active_tab = null;	// EJWEKOのワークフローのタブ情報
	var $edit_item_id = null;			// 編集するアイテムのitem_id
	var $ej_attribute_id = null;		// EJWEKOにおけるサプリコンテンツ所属属性ID
	var $ej_supple_no = null;			// EJWEKOにおけるサプリコンテンツ通番
	var $ej_page_id = null;				// EJWEKOのpage_id
	var $ej_block_id = null;			// EJWEKOのblock_id
	
	var $Session = null;
	var $Db = null;
	
    /**
     * [[機能説明]]
     *
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
            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
            $user_error_msg = 'initで既に・・・';
            throw $exception;
        }
        
        // 使用するセッションを初期化
        $this->Session->removeParameter("ej_item_id");
    	$this->Session->removeParameter("ej_item_no");
    	$this->Session->removeParameter("ej_weko_url");
    	$this->Session->removeParameter("ej_page_id");
	    $this->Session->removeParameter("ej_block_id");
	    $this->Session->removeParameter("add_supple_flag");
	    $this->Session->removeParameter("ej_workflow_flag");
		$this->Session->removeParameter("ej_workflow_active_tab");
		$this->Session->removeParameter("ej_attribute_id");
		$this->Session->removeParameter("ej_supple_no");
		$this->Session->removeParameter("supple_login");
        $this->Session->removeParameter("supple_edit_item_id");

		// get block_id and page_id
		$block_info = $this->getBlockPageId();
		
        // EJWEKOのitem_id, item_noをセッションに保存
		$this->Session->setParameter("ej_item_id", $this->ej_item_id);
    	$this->Session->setParameter("ej_item_no", $this->ej_item_no);
    	
        // make redirect URL
        $redirect_url = BASE_URL;
    	
        // EJWEKOのURLをセッションに保存
        $ej_weko_url = $_SERVER["HTTP_REFERER"];
        
        // Add branch for smartphone 2012/04/06 A.Suzuki --start--
        if($this->smartphoneFlg){
            $this->Session->setParameter("supple_login", "smartphone");
            $redirect_url .= "/index.php?action=pages_view_main".
                             "&active_action=repository_view_main_item_snippet";
        }
        // Add branch for smartphone 2012/04/04 A.Suzuki --end--
        
        // Fix check referer 2011/05/31 A.Suzuki --start--
        else if(strlen($ej_weko_url)!=0){
            $tmp_str = strstr($ej_weko_url, "?");
            $ej_weko_url = str_replace($tmp_str, "", $ej_weko_url);
            $ej_weko_url = str_replace("index.php", "", $ej_weko_url);
            $this->Session->setParameter("ej_weko_url", $ej_weko_url);
            
            // EJWEKOのpage_id, block_idをセッションに保存
            if($this->ej_page_id != null && $this->ej_block_id != null){
                $this->Session->setParameter("ej_page_id", $this->ej_page_id);
                $this->Session->setParameter("ej_block_id", $this->ej_block_id);
            } else {
                $param_str = substr($tmp_str, 1);
                $param_array = array();
                parse_str($param_str, $param_array);
                $this->Session->setParameter("ej_page_id", $param_array['page_id']);
                $this->Session->setParameter("ej_block_id", $param_array['block_id']);
            }
            
            if($this->ej_workflow_flag == "true"){
                // EJWEKOのワークフローから来たことを示すフラグ
                $this->Session->setParameter("ej_workflow_flag", "true");
                $this->Session->setParameter("ej_workflow_active_tab", $this->ej_workflow_active_tab);
                $this->Session->setParameter("ej_attribute_id", $this->ej_attribute_id);
                $this->Session->setParameter("ej_supple_no", $this->ej_supple_no);
            }
            
            $user_id = $this->Session->getParameter("_user_id");
            if($user_id == '0'){
                // ログインしていない
                $this->Session->setParameter("supple_login", "true");
                $this->Session->setParameter("supple_edit_item_id", $this->edit_item_id);
                $redirect_url .= "/index.php?action=pages_view_main".
                                 "&active_action=repository_view_main_item_snippet";
            } else {
                // ログイン済
                $user_auth_id = $this->Session->getParameter("_user_auth_id");
                
                // Fix get _auth_id 2011/05/31 A.Suzuki --start--
                $container =& DIContainerFactory::getContainer();
                $authCheck =& $container->getComponent("authCheck");
                $auth_id = $authCheck->getPageAuthId($user_id, $block_info["page_id"]);
                // Fix get _auth_id 2011/05/31 A.Suzuki --end--
                
                if($this->ej_workflow_flag == "true"){
                    // アイテム編集権限チェック
                    $query = "SELECT * ".
                             "FROM ". DATABASE_PREFIX ."repository_item ".
                             "WHERE item_id = ? ".
                             "AND item_no = ? ".
                             "AND is_delete = ?; ";
                    $params = null;
                    // $queryの?を置き換える配列
                    $params[] = $this->edit_item_id;    // item_id
                    $params[] = 1;                      // item_no
                    $params[] = 0;                      // is_delete
                    //　SELECT実行
                    $result_Item_Table = $this->Db->execute($query, $params);
                    if($result_Item_Table === false){
                        return false;
                    }
                    $item_user = $result_Item_Table[0]['ins_user_id'];
                    // 登録ユーザ or モデレータ以上
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if($this->Session->getParameter("_user_auth_id") >= _AUTH_MODERATE || $item_user == $user_id){
                    if(($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) || $item_user == $user_id){
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        $result = $this->getEditItemDataForSupple($this->edit_item_id, 1);
                        if($result == 'texts'){
                            // ファイルなし -> メタデータ編集画面へ
                            $redirect_url .= "/index.php?action=pages_view_main".
                                             "&active_action=repository_view_main_item_edittexts";
                        } else if($result == 'files') {
                            // ファイルあり -> ファイル編集画面へ
                            $redirect_url .= "/index.php?action=pages_view_main".
                                             "&active_action=repository_view_main_item_editfiles";
                        } else {
                            // エラー
                            $redirect_url .= "/index.php?action=pages_view_main".
                                             "&active_action=repository_view_main_item_snippet";
                        }
                    }
                    // その他ユーザ
                    else {
                        // アイテム編集不可
                        $this->Session->setParameter("supple_login", "guest");
                        $redirect_url .= "/index.php?action=pages_view_main".
                                         "&active_action=repository_view_main_item_snippet";
                    }
                } else {
                    // アイテム登録権限チェック(アイテム登録可能な権限以上)
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if($this->Session->getParameter("_user_auth_id") >= _AUTH_GENERAL){
                    if($auth_id >= REPOSITORY_ITEM_REGIST_AUTH){
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        $redirect_url .= "/index.php?action=pages_view_main".
                                         "&active_action=repository_view_main_item_selecttype";
                    }
                    // アイテム登録可能な権限未満
                    else {
                        // アイテム作成不可
                        $this->Session->setParameter("supple_login", "guest");
                        $redirect_url .= "/index.php?action=pages_view_main".
                                         "&active_action=repository_view_main_item_snippet";
                    }
                }
            }
        } else {
            // no referer (Can not know EJWEKO URL)
            $this->Session->setParameter("supple_login", "guest");
            $redirect_url .= "/index.php?action=pages_view_main".
                             "&active_action=repository_view_main_item_snippet";
        }
        // Fix check referer 2011/05/31 A.Suzuki --start--
        
        $redirect_url .= "&page_id=". $block_info["page_id"] .
                         "&block_id=". $block_info["block_id"];
        
        // EJWEKOから来たことを示すフラグ
        $this->Session->setParameter("add_supple_flag", "true");
		
		//アクション終了処理
		$result = $this->exitAction();     // トランザクションが成功していればCOMMITされる
		
		// redirect
		header("HTTP/1.1 301 Moved Permanently");
  		header("Location: ".$redirect_url);
		
		return;
    }
}
?>
