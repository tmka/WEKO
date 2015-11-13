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

/**
 * [[アイテム管理actionアクション]]
 * JaLC DOI一括付与action
 * 
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_Action_Edit_Item_Doi extends RepositoryAction
{
    // *********************
    // リクエストパラメータ
    // *********************
    // 選択インデックスID
    public $targetIndexId = null;
    // JaLC DOi付与アイテムリスト
    public $registing_jalcdoi_items = null;
    public $registing_crossref_items = null;
    // Add DataCite 2015/02/09 K.Sugimoto --start--
    public $registing_datacite_items = null;
    // Add DataCite 2015/02/09 K.Sugimoto --end--
    
    const JALC_DOI_MANAGEMENT_TAB_NUMBER = 3;
    
    /**
     * JaLC DOI一括付与 execute
     *
     * @access  public
     */
    public function executeApp()
    {
        // 選択タブの保存
        $this->Session->setParameter("item_setting_active_tab", self::JALC_DOI_MANAGEMENT_TAB_NUMBER);
        
        // 引数チェック //
        if( $this->targetIndexId == null || $this->targetIndexId == "" ){
            $this->Session->setParameter("error_msg", "Select Index Error.");
            return 'error';
        }
        //セッションクリア
        $this->Session->removeParameter("targetIndexId");
        $this->Session->removeParameter("searchIndexId");
        
        $this->Session->setParameter("targetIndexId", $this->targetIndexId);
        $this->Session->setParameter("searchIndexId", $this->targetIndexId);
        
        $doi_count = 0;
        
        if(count($this->registing_jalcdoi_items) > 0)
        {
            $item_no = 1;
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            $repositorySearchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            foreach($this->registing_jalcdoi_items as $item_id)
            {
                $suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
                $repositoryHandleManager->registJalcdoiSuffix($item_id, $item_no, $suffix);
                $repositorySearchTableProcessing->updateSelfDoiSearchTable($item_id, $item_no);
                $this->updateModDate($item_id, $item_no);
                $doi_count++;
            }
        }
        if(count($this->registing_crossref_items) > 0)
        {
            $item_no = 1;
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            $repositorySearchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            foreach($this->registing_crossref_items as $item_id)
            {
                $suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
                $repositoryHandleManager->registCrossrefSuffix($item_id, $item_no, $suffix);
                $repositorySearchTableProcessing->updateSelfDoiSearchTable($item_id, $item_no);
                $this->updateModDate($item_id, $item_no);
                $doi_count++;
            }
        }
        // Add DataCite 2015/02/09 K.Sugimoto --start--
        if(count($this->registing_datacite_items) > 0)
        {
            $item_no = 1;
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            $repositorySearchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            foreach($this->registing_datacite_items as $item_id)
            {
                $suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
                $repositoryHandleManager->registDataciteSuffix($item_id, $item_no, $suffix);
                $repositorySearchTableProcessing->updateSelfDoiSearchTable($item_id, $item_no);
                $this->updateModDate($item_id, $item_no);
                $doi_count++;
            }
        }
        
        $this->Session->setParameter("doi_count", $doi_count);
        // Add DataCite 2015/02/09 K.Sugimoto --end--
        
        // エラーメッセージ開放
        $this->Session->removeParameter("error_msg");
        return 'success';
    }
    
    /**
     * 更新日の更新
     *
     * @param $item_id
     * @param $item_no
     */
    private function updateModDate($item_id, $item_no)
    {
        $query = "UPDATE ". DATABASE_PREFIX ."repository_item ".
                 "SET mod_date = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ?; ";
        $params = null;
        $params[] = $this->TransStartDate;         // mod_date
        $params[] = $item_id;                   // item_id
        $params[] = $item_no;                   // item_no
        //UPDATE
        $result = $this->dbAccess->executeQuery($query, $params);              
    }
}
?>
