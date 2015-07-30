<?php
// --------------------------------------------------------------------
//
// $Id: Editfiles.class.php 10292 2011-08-03 08:53:51Z yuko_nakao $
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
class Repository_View_Main_Item_Editfiles extends RepositoryAction
{
   	var $error_msg = "";		// エラーメッセージ
	
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
        
        // Add theme_name for image file Y.Nakao 2011/08/03 --start--
        $this->setThemeName();
        // Add theme_name for image file Y.Nakao 2011/08/03 --end--
        
    	// セッションからエラーメッセージのコピー
    	$this->error_msg = $this->Session->getParameter("error_msg");
    	$this->Session->removeParameter("error_msg");

    	// Add registered info save action 2009/01/29 Y.Nakao --start--
    	$this->setLangResource();
    	// Add registered info save action 2009/01/29 Y.Nakao --end--
    	
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
    	
/*
    	// アイテムタイプをDBから取得
    	$id = $this->session->getParameter("item_type_id");
    	$params = array("item_type_id" => $id);
    	$item_type = $this->db->selectExecute("repository_item_type",$params);
    	if($item_type === false) {
    		return 'false';
    	}
    	// アイテムタイプ名をセッションに登録
    	$this->session->setParameter("item_type_name", $item_type[0]['item_type_name']);
    	// アイテム属性タイプをDBから取得
    	$item_attr_type = $this->db->selectExecute("repository_item_attr_type" ,$params);
*/
        return 'success';
    }
}
?>
