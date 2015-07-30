<?php
// --------------------------------------------------------------------
//
// $Id: Deletepopup.class.php 9461 2011-06-16 00:39:22Z yuko_nakao $
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
class Repository_View_Common_Item_Supple_Deletepopup extends RepositoryAction
{
	// リクエストパラメタ
	var $item_id = null;					// アイテムID
	var $item_no = null;					// アイテム通番
	var $supple_no = null;					// サプリメンタルコンテンツ通番
	var $supple_url = null;					// 本体詳細画面URL
	var $supple_workflow_active_tab = null;	// 選択中タブ情報
	
	var $Session = null;
	var $Db = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
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
        
        // Bugfix check delete authority. Y.Nakao 2011/06/15 --start--
        // 削除権限確認
        $query = "SELECT * ".
                " FROM ".DATABASE_PREFIX."repository_supple ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                " AND supple_no = ? ".
                " AND is_delete = ?; ";
        $param = array();
        $param[] = $this->item_id;
        $param[] = $this->item_no;
        $param[] = $this->supple_no;
        $param[] = 0;
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $result = $this->exitAction();
            return 'error';
        }
        if(count($result) != 1){
            $result = $this->exitAction();
            return 'error';
        }
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $role_auth_id = $this->Session->getParameter("_role_auth_id");
        $auth_id = $this->Session->getParameter("_auth_id");
        if($this->repository_admin_base && $auth_id >= $this->repository_admin_room){
            // admin
            $result = $this->exitAction();
            return 'success';
        }
        if($result[0]['ins_user_id'] == $user_id){
            // supple contents insert user
            $result = $this->exitAction();
            return 'success';
        }
        $query = "SELECT * ".
                " FROM ".DATABASE_PREFIX."repository_item ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                " AND is_delete = ?; ";
        $param = array();
        $param[] = $this->item_id;
        $param[] = $this->item_no;
        $param[] = 0;
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $result = $this->exitAction();
            return 'error';
        }
        if(count($result) != 1){
            $result = $this->exitAction();
            return 'error';
        }
        if($result[0]['ins_user_id'] == $user_id){
            // supple contents insert user
            $result = $this->exitAction();
            return 'success';
        }
        //Bugfix check delete authority. Y.Nakao 2011/06/15 --end--
        return 'error';
    }
}
?>
