<?php
// --------------------------------------------------------------------
//
// $Id: Setting.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
 * repositoryモジュール アイテムタイプ設定 アイテムタイプ選択
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Setting extends RepositoryAction
{
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
    	// セッション情報初期化 for アイテムタイプ設定
    	$this->Session->removeParameter("item_type_id");		// アイテムタイプID
    	$this->Session->removeParameter("item_type"); 		// アイテムタイプ
    	$this->Session->removeParameter("metadata_table");	// アイテムタイプ属性テーブル
    	
    	// セッション情報初期化 メタデータ 2008/03/04
    	$this->Session->removeParameter("metadata_num");
    	$this->Session->removeParameter("metadata_title");
   		$this->Session->removeParameter("metadata_type");
    	$this->Session->removeParameter("metadata_required");
   		$this->Session->removeParameter("metadata_disp");
   		$this->Session->removeParameter("metadata_candidate");
   		
   		$this->Session->removeParameter("import_item_type_name");
   		
   		// エラーコード開放
   		$this->Session->removeParameter("error_code");
   		
        return 'success';
    }
}
?>
