<?php
// --------------------------------------------------------------------
//
// $Id: Editdoi.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * アイテム登録：DOI付与画面からの入力処理アクション
 *
 * @access      public
 */
class Repository_Action_Main_Item_Editdoi extends RepositoryAction
{
    // リクエストパラメーター
    /**
     * 処理モード
     *   'selecttype'   : アイテムタイプ選択画面
     *   'files'        : ファイル選択画面
     *   'texts'        : メタデータ入力画面
     *   'links'        : リンク設定画面
     *   'doi'          : DOI設定画面
     *   'confirm'      : 確認画面
     *   'stay'         : save
     *   'next'         : go next page
     * @var string
     */
    public $save_mode = null;
    
    /**
     * JaLC DOI チェック情報
     * @var string
     */
    public $entry_jalcdoi_checkbox = null;
    
    /**
     * JaLC DOI URI
     * @var string
     */
    public $entry_jalcdoi_hidden = null;
    
    /**
     * Cross Ref チェック情報
     * @var string
     */
    public $entry_crossref_checkbox = null;
    
    /**
     * Cross Ref URI
     * @var string
     */
    public $entry_crossref_hidden = null;
    
    /**
     * DataCite チェック情報
     * @var string
     */
    public $entry_datacite_checkbox = null;
    
    /**
     * DataCite URI
     * @var string
     */
    public $entry_datacite_hidden = null;
    
    /**
     * Library JaLC DOI 入力値
     * @var string
     */
    public $entry_library_jalcdoi_text = null;
    
    /**
     * Library JaLC DOI URI
     * @var string
     */
    public $entry_library_jalcdoi_hidden = null;
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    public function executeApp()
    {
        // セッション情報取得
        $item_id = intval($this->Session->getParameter("edit_item_id"));
        $item_no = intval($this->Session->getParameter("edit_item_no"));
        
        // インスタンス作成
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->accessDate);
        $suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
        // Add Library JaLC DOI
        if(isset($this->entry_library_jalcdoi_text) && strlen($this->entry_library_jalcdoi_text) > 0)
        {
            $repositoryHandleManager->registLibraryJalcdoiSuffix($item_id, $item_no, $this->entry_library_jalcdoi_text);
        }
        // Add JaLC DOI
        else if(isset($this->entry_jalcdoi_checkbox) && strlen($this->entry_jalcdoi_checkbox) > 0 && strlen($suffix) > 0)
        {
            $repositoryHandleManager->registJalcdoiSuffix($item_id, $item_no, $suffix);
        }
        // Add Cross Ref
        else if(isset($this->entry_crossref_checkbox) && strlen($this->entry_crossref_checkbox) > 0 && strlen($suffix) > 0)
        {
            $repositoryHandleManager->registCrossrefSuffix($item_id, $item_no, $suffix);
        }
        // Add DataCite 2015/02/09 K.Sugimoto --start--
        // Add DataCite
        else if(isset($this->entry_datacite_checkbox) && strlen($this->entry_datacite_checkbox) > 0 && strlen($suffix) > 0)
        {
            $repositoryHandleManager->registDataciteSuffix($item_id, $item_no, $suffix);
        }
        // Add DataCite 2015/02/09 K.Sugimoto --end--
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "doi",
                                $this->save_mode,
                                $this->Session->getParameter("isfile"),
                                $this->Session->getParameter("doi_itemtype_flag"),
                                $this->Session->getParameter("base_attr"),
                                $this->Session->getParameter("item_pub_date"),
                                $this->Session->getParameter("item_attr_type"),
                                $this->Session->getParameter("item_attr"),
                                $this->Session->getParameter("item_num_attr"),
                                $this->Session->getParameter("indice"),
                                $this->Session->getParameter("edit_item_id"),
                                $this->Session->getParameter("edit_item_no")
        );
        $ret = $transCheck->getDestination();
        foreach($transCheck->getErrorMsg() as $msg){
            $this->addErrMsg($msg);
        }
        $this->warningMsg = array_merge($this->warningMsg, $transCheck->getWarningMsg());
        
        // warningをViewに渡す処理
        if(count($this->warningMsg) > 0){
            $container =& DIContainerFactory::getContainer();
            $request =& $container->getComponent("Request");
            $request->setParameter("warningMsg", $this->warningMsg);
        }
        
        return $ret;
    }
}
?>
