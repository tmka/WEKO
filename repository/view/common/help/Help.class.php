<?php
// --------------------------------------------------------------------
//
// $Id: Help.class.php 39149 2014-07-28 08:37:06Z rei_matsuura $
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
class Repository_View_Common_Help extends RepositoryAction
{
	// セッションとデータベースのオブジェクトを受け取る
    var $Session = null;
    var $Db = null;
    
    //リクエストパラメータ
    var $helpID = null;
    var $helpPath = "";
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    //Path作成
    //helpファイルの判別
    // Modify Directory specification K.Matsuo 2011/9/2 -----start-----
    switch($this->helpID){
    	case 'search_keyword':
    		$this->helpPath = 'weko/help/ja/html/search.html#keyword';
    		break;
    	case 'search_directory':
    		$this->helpPath = 'weko/help/ja/html/search.html#index';
    		break;
    	case 'ranking':
    		$this->helpPath = 'weko/help/ja/html/ranking.html';
    		break;
    	case 'export':
    		$this->helpPath = 'weko/help/ja/html/search.html#item_list';
    		break;
    	case 'view_metadata':
    		$this->helpPath = 'weko/help/ja/html/item_detail.html';
    		break;
    	case 'select_itemtype':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#select';
    		break;
    	case 'entry_file':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#file';
    		break;
    	case 'entry_file_lisence':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#lisence';
    		break;
    	case 'edit_metadata':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#metadata';
    		break;
    	case 'edit_link':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#link';
    		break;
    	case 'edit_doi':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#doi';
    		break;
    	case 'confirm':
    		$this->helpPath = 'weko/help/ja/html/item_entry.html#confirm';
    		break;
    	case 'edittree':
    		$this->helpPath = 'weko/help/ja/html/tree_edit.html#top';
    		break;
    	case 'itemtype_making':
    		$this->helpPath = 'weko/help/ja/html/itemtype_metadata.html#itemtype_create';
    		break;
    	case 'itemtype_edit':
    		$this->helpPath = 'weko/help/ja/html/itemtype_metadata.html#itemtype_metadata';
    		break;
    	case 'itemtype_icon':
    		$this->helpPath = 'weko/help/ja/html/itemtype_metadata.html#itemtype_icon';
    		break;
    	case 'itemtype_confirm':
    		$this->helpPath = 'weko/help/ja/html/itemtype_metadata.html#itemtype_confirm';
    		break;
    	case 'itemtype_mapping':
    		$this->helpPath = 'weko/help/ja/html/itemtype_mapping.html#mapping';
    		break;
    	case 'itemtype_mapping_conf':
    		$this->helpPath = 'weko/help/ja/html/itemtype_mapping.html#confirm';
    		break;
    	case 'item_customsort':
    		$this->helpPath = 'weko/help/ja/html/item_setting.html#customsort';
    		break;
    	case 'item_embargo':
    		$this->helpPath = 'weko/help/ja/html/item_setting.html#embargo';
    		break;
    	case 'item_listdelete':
    		$this->helpPath = 'weko/help/ja/html/item_setting.html#listdelete';
    		break;
    	case 'item_doibulkgrant':
    		$this->helpPath = 'weko/help/ja/html/item_setting.html#doibulkgrant';
    		break;
    	case 'review_item':
    		$this->helpPath = 'weko/help/ja/html/review.html#item';
    		break;
    	case 'review_supple':
    		$this->helpPath = 'weko/help/ja/html/review.html#supple';
    		break;
    	case 'import':
    		$this->helpPath = 'weko/help/ja/html/import.html#top';
    		break;
    	case 'import_authority':
    		$this->helpPath = 'weko/help/ja/html/import.html#authority';
    		break;
    	case 'import_result':
    		$this->helpPath = 'weko/help/ja/html/import.html#result';
    		break;
    	case 'import_authority_result':
    		$this->helpPath = 'weko/help/ja/html/import.html#authority_result';
    		break;
    	case 'log':
    		$this->helpPath = 'weko/help/ja/html/log.html#top';
    		break;
    	case 'setting_view':
    		$this->helpPath = 'weko/help/ja/html/setting.html#view';
    		break;
    	case 'setting_run':
    		$this->helpPath = 'weko/help/ja/html/setting.html#run';
    		break;
    	case 'setting_server':
    		$this->helpPath = 'weko/help/ja/html/setting.html#server';
    		break;
    	case 'workflow_entry':
    		$this->helpPath = 'weko/help/ja/html/workflow.html#entry';
    		break;
    	case 'workflow_review':
    		$this->helpPath = 'weko/help/ja/html/workflow.html#review';
    		break;
    	case 'workflow_public':
    		$this->helpPath = 'weko/help/ja/html/workflow.html#public';
    		break;
    	case 'suppleworkflow_entry':
    		$this->helpPath = 'weko/help/ja/html/workflow_supple.html#entry';
    		break;
    	case 'suppleworkflow_review':
    		$this->helpPath = 'weko/help/ja/html/workflow_supple.html#review';
    		break;
    	case 'suppleworkflow_public':
    		$this->helpPath = 'weko/help/ja/html/workflow_supple.html#public';
    		break;
    	case 'editprivatetree':
    		$this->helpPath = 'weko/help/ja/html/privatetree_edit.html#public';
    		break;

    	default:
    		break;
    }
    // Modify Directory specification K.Matsuo 2011/9/2 -----end-----

    return "redirect";
    }
}
?>
