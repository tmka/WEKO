<?php
// --------------------------------------------------------------------
//
// $Id: Editdoi.class.php 39186 2014-07-29 04:33:06Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Editdoi extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    public $Session = null;
    public $Db = null;
    
    public $item_relation_select = null;   // アイテム間リンク：関係性
    
    public $OpendIds = null;       // open index ids(delemit is ",")
    public $CheckedIds = null;     // check index ids(delemit is "|")
    public $CheckedNames = null;   // check index names(delemit is "|")
    
    public $save_mode = null;      // 'stay' : save
                                // 'next' : go next page
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeForWeko()
    {
        $smarty_assign = $this->Session->getParameter("smartyAssign");
        $err_msg = array();
        $warning = "";
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        
        $link = $this->Session->getParameter("link");
        $relation = '';
        
        for($ii=0; $ii<count($link); $ii++){
            if($this->item_relation_select[$ii]!=' ') {
                $relation = $this->item_relation_select[$ii];
            }else{
                $relation = '';
            }
            $link[$ii]['relation'] = $relation;
        }
        $this->Session->setParameter("link", $link);
        
        // Add join set insert index and set item links 2008/12/17 Y.Nakao --start--
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
        
        $this->Session->setParameter("indice", $indice);
        // check index, you most check index 1 more
        if(count($indice) < 1) {
            $msg = $smarty_assign->getLang("repository_item_error_index");
            array_push($err_msg, $msg);
        }
        // Add join set insert index and set item links 2008/12/17 Y.Nakao --end--
        
        $item["item_id"] = intval($this->Session->getParameter("edit_item_id"));
        $item["item_no"] = intval($this->Session->getParameter("edit_item_no"));
        
        $result = $ItemRegister->entryPositionIndex($item, $indice, $error);
        if($result === false){
            array_push($err_msg, $error);
            $this->Session->setParameter("error_msg", $err_msg);
            return 'error';
        }
        $result = $ItemRegister->entryReference($item, $link, $error);
        if($result === false){
            array_push($err_msg, $error);
            $this->Session->setParameter("error_msg", $err_msg);
            return 'error';
        }
        
        $ItemRegister->updateInsertUserIdForContributor(
                intval($this->Session->getParameter("edit_item_id")),
                $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        
        
        $item_id = $item["item_id"];
        $item_no = $item["item_no"];
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        // set y handle suffix
        $base_attr = $this->Session->getParameter("base_attr");
        $repositoryHandleManager->setSuffix($base_attr['title'], $item_id, $item_no);
        // check this item can be granted doi
        $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
        $displays_jalcdoi_flag = $CheckDoi->checkDoiGrant($item_id, $item_no, Repository_Components_Checkdoi::TYPE_JALC_DOI);
        $displays_crossref_flag = $CheckDoi->checkDoiGrant($item_id, $item_no, Repository_Components_Checkdoi::TYPE_CROSS_REF);
        // check this item was already granted doi or not
        $doi_status = $CheckDoi->getDoiStatus($item_id, $item_no);
        if($this->save_mode == "next" && ($displays_jalcdoi_flag || $displays_crossref_flag || $doi_status >= 1))
        {
            // next is doi grant display
            $this->save_mode = "next_doi";
        }
        
        $this->Session->removeParameter("error_msg");
        $this->Session->removeParameter("warning");
        if(count($err_msg)>0){
            // 入力にエラーがあるときは画面遷移しない
            $this->save_mode = "stay";
        }
        if($this->save_mode == "stay"){
            $this->Session->setParameter("error_msg", $err_msg);
            $this->Session->setParameter("warning", $warning);
            return 'stay';
        } else if($this->save_mode == "next" || $this->save_mode == "next_doi"){
            $this->Session->removeParameter("search_index_id_link");
            $this->Session->removeParameter("link_searchkeyword");
            $this->Session->removeParameter("link_search");
            $this->Session->removeParameter("view_open_node_index_id_item_link");
            if($this->save_mode == "next_doi")
            {
                return 'editdoi';
            }
            else
            {
                return 'success';
            }
        } else {
            return 'error';
        }
    }
}
?>
