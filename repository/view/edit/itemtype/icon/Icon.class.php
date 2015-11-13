<?php
// --------------------------------------------------------------------
//
// $Id: Icon.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Itemtype_Icon extends RepositoryAction 
{
	var $Session = null;
	var $Db = null;
	
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
	
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
        
        // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
        // session.icon_edit is const value
        $uploadIcon = $this->Session->getParameter('upload_icon'); 
        // add the if construst
        if(isset($uploadIcon)){
            $this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_UPLOAD_ICON);
        }
		else if($this->Session->getParameter("item_type_edit_flag")==1){
	    	// 既存編集の場合
	    	$query = "SELECT * FROM ". DATABASE_PREFIX ."repository_item_type ".
	    			 "WHERE item_type_id = ?;";
	    	$params = null;
	    	$params = $this->Session->getParameter("item_type_id");
	    	$result = $this->Db->execute($query, $params);
	    	if($result===false){
	    		$this->Session->setParameter("icon_edit",RepositoryConst::SESSION_PARAM_DEFAULT_ICON);
	    		echo "SELECT ICON ERROR ";
	    		return 'success';
	    	}
	    	
            // changed condition(if exists icon in database and pushes icon delete button)
            $defaultIconFlg = $this->Session->getParameter('icon_edit');
	    	if($result[0]['icon'] && $defaultIconFlg != RepositoryConst::SESSION_PARAM_DEFAULT_ICON){
	    		// アイコン登録済みアイコンあり
	    		$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DATABASE_ICON);
	    	} else {
	    		// アイコン未登録(共通リソース)
	    		$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DEFAULT_ICON);
	    	}
	    	
		} else {
			// 新規作成
			$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_NEW_ITEM_TYPE);
		}
        // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
		
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
