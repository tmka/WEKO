<?php
// --------------------------------------------------------------------
//
// $Id: Doi.class.php 49641 2015-03-09 07:02:34Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/common/WekoAction.class.php';

/**
 * [[アイテム管理actionアクション]]
 * 検索削除action
 * 
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_Action_Edit_Item_Searchdelete extends WekoAction
{
    // *********************
    // リクエストパラメータ
    // *********************
    // チェックボックスのチェック状態
    public $delete_search_items = null;
    
    // 検索削除のタブNo
    const SEARCH_DELETE_TAB_NUMBER = 2;
    
    // 入力した検索キーワード
    public $searchkeyword = null;
    
    // 検索タイプ
    public $search_type = null;
    
    /**
     * 検索削除 execute
     *
     * @access  public
     */
    public function executeApp()
    {
        // ログ
        $this->infoLog("RequestParameter:searchkeyword=[".$this->searchkeyword."]:search_type=[".$this->search_type."]" , __FILE__, __CLASS__, __LINE__);
        
        // 選択タブの保存
        $this->Session->setParameter("item_setting_active_tab", self::SEARCH_DELETE_TAB_NUMBER);
        
        // キーワードの保存
        $this->Session->setParameter("searchkeywordForDelete", $this->searchkeyword);
        
        // 検索タイプの保存
        $this->Session->setParameter("search_type", $this->search_type);
        
        // セッションから検索結果を取得
        $deleteList = $this->Session->getParameter("search_delete_item_data");
        
        // 削除したアイテム数を保存する変数初期化
        $delete_success_num = 0;
        
        // チェックが入っているアイテムを削除
        for( $ii=0; $ii<count($this->delete_search_items); $ii++ )
        {
            // チェックの入ったアイテムの$cnt
            $cnt = $this->delete_search_items[$ii];
            
            $item_id = $deleteList[$cnt]['item_id'];
            $item_no = $deleteList[$cnt]['item_no'];
            
            // ビジネスクラスの削除処理
            $itemDelete = BusinessFactory::getFactory()->getBusiness("businessItemdelete");
            $delete_result = $itemDelete->deleteItem($item_id, $item_no, $this->Session, $this->repository_admin_base, $this->repository_admin_room);
            
            if($delete_result === true){
                $delete_success_num += 1;
            }
        }
        // 削除件数の保存
        $this->Session->setParameter("delete_success_num", $delete_success_num);
        
        // 検索結果のセッションクリア
        $this->Session->removeParameter("search_delete_item_data");
        
        return 'success';
    }
    
}
?>
