<?php
// --------------------------------------------------------------------
//
// $Id: Popup.class.php 9286 2011-06-03 02:22:01Z yuko_nakao $
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
class Repository_View_Common_Item_Supple_Popup extends RepositoryAction
{
	// リクエストパラメタ
	var $item_id = null;			// アイテムID
	var $item_no = null;			// アイテム通番
	var $supple_weko_url = "";	// サプリWEKOURL
	
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
        
    	// パラメタテーブルからサプリWEKOのアドレスを取得する
		$query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
				 "WHERE param_name = 'supple_weko_url';";
		$result = $this->Db->execute($query);
		if($result === false){
			return false;
		}
		if($result[0]['param_value'] != ""){
			$this->supple_weko_url = $result[0]['param_value'].
									 "/?action=repository_view_common_item_supple_logincheck".
									 "&ej_item_id=".$this->item_id.
									 "&ej_item_no=".$this->item_no;
		}
    	
    	//アクション終了処理
		$result = $this->exitAction();     // トランザクションが成功していればCOMMITされる
    	
        return 'success';
    }
}
?>
