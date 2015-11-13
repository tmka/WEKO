<?php
// --------------------------------------------------------------------
//
// $Id: Editlinks.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * アイテム登録：リンク設定画面からの入力処理アクション
 * 
 * @access      public
 */
class Repository_Action_Main_Item_Editlinks extends RepositoryAction
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
     * アイテム間リンク：関係性 入力値配列
     * @var array
     */
    public $item_relation_select = null;
    
    /**
     * インデックス開閉情報
     *   delemit is ","
     * @var string
     */
    public $OpendIds = null;
    
    /**
     * インデックスチェック情報：ID
     *   delemit is "|"
     * @var string
     */
    public $CheckedIds = null;
    
    /**
     * インデックスチェック情報：インデックス名
     *   delemit is "|"
     * @var string
     */
    public $CheckedNames = null;
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        // インスタンス作成
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        
        // セッション情報取得
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        $link = $this->Session->getParameter("link");
        $base_attr = $this->Session->getParameter("base_attr");
        $item_id = intval($this->Session->getParameter("edit_item_id"));
        $item_no = intval($this->Session->getParameter("edit_item_no"));
        
        for($ii=0; $ii<count($link); $ii++){
            $relation = '';
            if($this->item_relation_select[$ii]!=' ') {
                $relation = $this->item_relation_select[$ii];
            }
            $link[$ii]['relation'] = $relation;
        }
        
        // set session to index open info
        if($this->OpendIds != null && $this->OpendIds != '') {  
            $arOpenIndexId = array();
            $arOpenIndexId = explode(",", $this->OpendIds);
            $this->Session->removeParameter("open_node_index_id_index");
            $this->Session->setParameter("open_node_index_id_index", $arOpenIndexId);
        }
        // set session to check index info
        $indice = array();
        if( $this->CheckedIds != null && $this->CheckedIds != '' ){
            $checked_ids = explode('|', $this->CheckedIds);
            $checked_names = explode('|', str_replace("&#039;", "'", html_entity_decode($this->CheckedNames)));
            for($ii=0; $ii<count($checked_ids); $ii++) {
                array_push($indice, array(
                        'index_id' => $checked_ids[$ii],
                        'index_name' => $checked_names[$ii])
                        );
            }
        }
        $ItemRegister->setInsUserId($this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        $indice = $this->addPrivateTreeInPositionIndex($indice, $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        
        $item = array("item_id"=>$item_id, "item_no"=>$item_no);
        $result = $ItemRegister->entryPositionIndex($item, $indice, $error);
        if($result === false){
            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($error);
            $exception->addError($error);
            throw $exception;
        }
        $result = $ItemRegister->entryReference($item, $link, $error);
        if($result === false){
            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($error);
            $exception->addError($error);
            throw $exception;
        }
        $ItemRegister->updateInsertUserIdForContributor(
                intval($this->Session->getParameter("edit_item_id")),
                $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        // set y handle suffix
        $repositoryHandleManager->setSuffix($base_attr['title'], $item_id, $item_no);
        
        // セッション情報更新
        $this->Session->setParameter("link", $link);
        $this->Session->setParameter("indice", $indice);
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "links",
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
        
        if($ret != "links"){
            $this->Session->removeParameter("search_index_id_link");
            $this->Session->removeParameter("link_searchkeyword");
            $this->Session->removeParameter("link_search");
            $this->Session->removeParameter("link_searchtype");
            $this->Session->removeParameter("view_open_node_index_id_item_link");
        }
        
        return $ret;
    }
}
?>
