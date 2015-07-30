<?php
// --------------------------------------------------------------------
//
// $Id: Setting.class.php 3 2010-02-02 05:07:44Z atsushi_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * repositoryモジュール アイテムタイプ設定 アイテムタイプ選択
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Setting
{
	// コンポーネント用
	var $session = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// セッション情報初期化 for アイテムタイプ設定
    	$this->session->removeParameter("item_type_id");		// アイテムタイプID
    	$this->session->removeParameter("item_type"); 		// アイテムタイプ
    	$this->session->removeParameter("metadata_table");	// アイテムタイプ属性テーブル
    	
    	// セッション情報初期化 メタデータ 2008/03/04
    	$this->session->removeParameter("metadata_num");
    	$this->session->removeParameter("metadata_title");
   		$this->session->removeParameter("metadata_type");
    	$this->session->removeParameter("metadata_required");
   		$this->session->removeParameter("metadata_disp");
   		$this->session->removeParameter("metadata_candidate");
   		
   		$this->session->removeParameter("import_item_type_name");
   		
   		// エラーコード開放
   		$this->session->removeParameter("error_code");
   		
        return 'success';
    }
}
?>
