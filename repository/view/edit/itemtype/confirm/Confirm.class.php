<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 10292 2011-08-03 08:53:51Z yuko_nakao $
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
class Repository_View_Edit_Itemtype_Confirm extends RepositoryAction 
{
	// 使用コンポーネントを受け取るため
	var $Session = null;
	// メタデータ表示用メンバ
	var $metadata_array = null;		// メタデータ表示内容配列
	var $metadata_title = null;		// メタデータ項目名配列
	var $metadata_type = null;		// メタデータタイプ配列
	var $metadata_required = null;	// メタデータ必須フラグ列
	var $metadata_disp = null;		// メタデータ一覧表示フラグ列
	var $itemtype_name = null;		//前画面で入力したアイテムタイプ名
	
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
        
        // Add theme_name for image file Y.Nakao 2011/08/03 --start--
        $this->setThemeName();
        // Add theme_name for image file Y.Nakao 2011/08/03 --end--
        
    	/* 2008/02/27 htmlから直接セッション情報を読み込む形に変更
    	// 材料はsessionに保存済み。本当は今更することはないか。
    	
       	// 
    	// メタデータ入力情報初期設定
		//
    	$this->metadata_array = array();
    	$this->metadata_title = $this->session->getParameter("metadata_title");
    	$this->metadata_type = $this->session->getParameter("metadata_type");
	    $this->metadata_required = $this->session->getParameter("metadata_required");
   		$this->metadata_disp = $this->session->getParameter("metadata_disp");
    	for($ii=0; $ii<count($this->metadata_title); $ii++) {
    		array_push($this->metadata_array , array($this->metadata_title[$ii],
    				$this->metadata_type[$ii], $this->metadata_required[$ii],
    				$this->metadata_disp[$ii]));
    	}
    	*/
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
