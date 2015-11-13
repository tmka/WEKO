<?php
// --------------------------------------------------------------------
//
// $Id: Tree.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/JSON.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

class Repository_Action_Main_Tree extends RepositoryAction
{
    // components
    var $Session = null;
    var $Db = null;
    var $uploadsView = null;

    // request parameter
    var $click_id = null;       // click index id
    var $sel_mode = null;       // select mode
                                // '' : snippet
                                // 'check' : check box
                                // 'rootcheck' : check box + root index
                                // 'link' : item link
                                // 'sel' : embargo
                                // 'els' : ELS
                                // 'edit' : edit tree
                                // 'insert' : insert index
                                // 'delete' : delete index
                                // 'subIndexList' : sub index list    // Add sub index list view for smartphone Y.Nakao 2012/04/06
                                // 'editPrivatetree'                      // Add edit privatetree K.Matsuo 2012/04/15
                                // 'detail_search'                        // Add detail search index tree
                                // 'harvestingCheck'                    // for harvesting index tree
    var $edit_id = null;        // edit index id
                                // if sel_mode is 'insert', insert parent index id
    var $chk_index_id = null;   // checked index id
    var $more_idx = null;       // click more index id

    // member
    var $open_ids = "";             // open index ids delimiter ","
    var $lang = "";                 // get language
    var $CheckedIds = null;         // checked index ids delmiter "|"

    // Add config management authority 2010/02/23 Y.Nakao --start--
    var $auth_id = "";
    // Add config management authority 2010/02/23 Y.Nakao --end--

    // prevent double registration for ELS 2010/10/20 A.Suzuki --start--
    private $elsRegisteredIndex_ = array();
    // prevent double registration for ELS 2010/10/20 A.Suzuki --end--

    // Bugfix modules id 2011/06/29 --start--
    private $block_info = array();
    // Bugfix modules id 2011/06/29 --end--

    // Add tree access control list 2012/02/29 T.Koyasu -start-
    private $canAccessIndexIds_ = array();
    // Add tree access control list 2012/02/29 T.Koyasu -end-

    // Add repository parameter 2013/04/11 K.Matsuo -start-
    private $admin_params_ = array();
    // Add repository parameter 2013/04/11 K.Matsuo -start-
    // Add child index left margin 2013/06/17 K.Matsuo -start-
    private $childLeftMargin = 12;
    // Add child index left margin 2013/06/17 K.Matsuo -end-
    // Add detail search index tree T.Koyasu -start-
    public $id_module = null;
    public  $detail_check_ids = null;
    public  $detail_all_check = null;
    // Add detail search index tree T.Koyasu -end-
    public $updateOpenInfo = null;
    private $privateTreeRootHierarchy = null;
    private $publicIndexQuery = "";
    // bug fix root select of private tree
    public $private_root_select = null;
    
    function execute()
    {
        ///////////////////////
        // init action
        ///////////////////////
        $result = $this->initAction();
        if ( $result === false ) {
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
            $DetailMsg = null;
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );
            $this->failTrans();
            throw $exception;
        }
        ///////////////////////////////
        // init tree info
        ///////////////////////////////
        // remove tree html
        $tree_html = "";
        // get language
        $this->lang = $this->Session->getParameter("_lang");
        // get WEKO info
        $this->block_info = $this->getBlockPageId();

        $error_msg = null;
        $return = $this->getParamTableData($this->admin_params_, $error_msg);
        if($return === false){
            return false;
        }
        // Add config management authority 2010/02/23 Y.Nakao --start--
        $this->setConfigAuthority();
        $user_id = $this->Session->getParameter("_user_id");
        $this->auth_id = $this->getRoomAuthorityID();
        // Add config management authority 2010/02/23 Y.Nakao --end--

        // get tree open nodes and set else info
        if($this->sel_mode == "check" || $this->sel_mode == "harvestingCheck"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_insert_item");
            // get check index ids
            if($this->CheckedIds == ""){
                $chk_idx = $this->Session->getParameter("indice");
                for($ii=0; $ii<count($chk_idx); $ii++){
                    $this->CheckedIds .= "|".$chk_idx[$ii]["index_id"];
                }
                $this->CheckedIds .= "|";
            } else {
                $this->CheckedIds = "|".str_replace(",", "|", $this->CheckedIds)."|";
            }
        } else if($this->sel_mode == "rootcheck"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_default_index");
        } else if($this->sel_mode == "link"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_item_link");
            // 検索インデックスの親インデックスがオープンでない場合、オープンする  set search index's parent index open
            $searsh_index = $this->Session->getParameter("search_index_id_link");
            if($searsh_index != "" && $this->click_id == ""){
                do{
                    $idx_data = $this->getIndexData($searsh_index);
                    if( $idx_data["parent_index_id"] != "0" &&
                        !is_numeric(strpos(",".$this->open_ids.",", ",".$idx_data["parent_index_id"].","))){
                        if($this->open_ids == ""){
                            $this->open_ids = $idx_data["parent_index_id"];
                        } else {
                            $this->open_ids .= ",".$idx_data["parent_index_id"];
                        }
                    }
                    $searsh_index = $idx_data["parent_index_id"];
                } while($searsh_index != "0");
            }
            // get check index ids
            if($this->CheckedIds == ""){
//              $chk_idx = $this->Session->getParameter("indice");
//              for($ii=0; $ii<count($chk_idx); $ii++){
//                  $this->CheckedIds .= "|".$chk_idx[$ii]["index_id"];
//              }
//              $this->CheckedIds .= "|";
            } else {
                $this->CheckedIds = "|".str_replace(",", "|", $this->CheckedIds)."|";
            }
        } else if($this->sel_mode == "sel"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_select");
        // prevent double registration for ELS 2010/10/20 A.Suzuki --start--
        } else if($this->sel_mode == "els"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_select");
            // get registered index
            $query = "SELECT `param_value` ".
                     "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                     "WHERE `param_name` = 'els_registered_index';";
            $result = $this->Db->execute($query);
            if ($result === false) {
                $this->failTrans();
                return false;
            }
            $this->elsRegisteredIndex_ = explode(",", $result[0]['param_value']);
        // prevent double registration for ELS 2010/10/20 A.Suzuki --end--
        } else if($this->sel_mode == "edit"){
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_edit");
        }
        // Add sub index list view for smartphone Y.Nakao 2012/04/06 --start--
        else if($this->sel_mode == "subIndexList")
        {
            $this->open_ids = '';
            $this->CheckedIds = '';
            $tmpIdxId = $this->Session->getParameter("searchIndexId");
            $this->Session->removeParameter("searchIndexId");
            $tree_html = '';
            // Fix smartphone index tree error. 2013/12/10 Y.Nakao --start--
            $tmpArray = array();
            if(!isset($this->privateTreeRootHierarchy))
            {
                $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
            }
            $this->getShowIndex($tmpArray, array($this->click_id), $this->privateTreeRootHierarchy, -1);
            // Bug Fix get index list by triangle in index list T.Koyasu 2014/07/24 --start--
            $tree_html = $tmpArray[intval($this->click_id)]['html'];
            // Bug Fix get index list by triangle in index list T.Koyasu 2014/07/24 --end--
            // Fix smartphone index tree error. 2013/12/10 Y.Nakao --end--

            $this->Session->setParameter("searchIndexId", $tmpIdxId);
            $repositoryDownload = new RepositoryDownload();
            $repositoryDownload->download($tree_html, "index_tree.html");
            exit();
        }
        // Add sub index list view for smartphone Y.Nakao 2012/04/06 --end--
        // Add edit privatetree K.Matsuo 2012/04/15 --start--
        else if($this->sel_mode == "editPrivatetree")
        {
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id_editPrivatetree");
        }
        // Add edit privatetree K.Matsuo 2012/04/15 --end--
        // Add detail search index tree T.Koyasu 2013/12/09 -start-
        else if($this->sel_mode == "detail_search")
        {
            // チェックインデックスの親インデックスがオープンでない場合、オープンする  set search index's parent index open
            $this->open_ids = "";
            $detailChkIdxArray = explode(",", $this->detail_check_ids);
            foreach ($detailChkIdxArray as $detailChkIdx)
            {
                if(strlen($detailChkIdx) > 0)
                {
                    $this->setOpenIdsForDetailSearch($detailChkIdx);
                }
            }
        }
        // Add detail search index tree T.Koyasu 2013/12/09 -end-
        else
        {
            $this->open_ids = $this->Session->getParameter("view_open_node_index_id");
            if($this->more_idx == null){
                // 検索インデックスの親インデックスがオープンでない場合、オープンする  set search index's parent index open
                $searsh_index = $this->Session->getParameter("searchIndexId");
                if($searsh_index != ""){
                    $idx_data = $this->getIndexData($searsh_index);
                    if(is_array($idx_data) && array_key_exists("parent_index_id", $idx_data) && $idx_data["parent_index_id"] != "0" &&
                        !(is_numeric(strpos(",".$this->open_ids.",", ",".$idx_data["parent_index_id"].","))) && $this->click_id == ""){
                        if($this->open_ids == ""){
                            $this->open_ids = $idx_data["parent_index_id"];
                        } else {
                            $this->open_ids .= ",".$idx_data["parent_index_id"];
                        }
                    }
                }
            }
            $this->sel_mode = "";
        }
        if($this->open_ids==null || $this->open_ids==""){
            $this->open_ids = ",,";
        } else {
            $this->open_ids = ",".$this->open_ids.",";
        }

        ///////////////////////////////
        // check request parameter
        ///////////////////////////////
        if($this->click_id != ""){
            // 表示は変更しない(open_idsのみ変更)
            if(!isset($this->privateTreeRootHierarchy))
            {
                $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
            }
            $clickIndexHerarchy = $this->getHierarchy($this->click_id);
            $idx_data = $this->getIndexData($this->click_id);
            $idx_data["hierarchy"] = $clickIndexHerarchy;
            $tmpArray = array();
            if(is_numeric(strpos($this->open_ids, ",".$this->click_id.",")) && $this->more_idx == ""){
                // ツリークローズ  this node will close
                $this->open_ids = str_replace(",".$this->click_id.",", ",", $this->open_ids);
            } else {
                // ツリーオープン this node will open
                if($this->more_idx == ""){
                    if($this->open_ids == ",,"){
                        $this->open_ids = ",".$this->click_id.",";
                    } else {
                        $this->open_ids .= $this->click_id.",";
                    }
                }
                if(empty($this->updateOpenInfo)){
                    // change this index show image
                    $this->getShowIndex($tmpArray, array($this->click_id), $this->privateTreeRootHierarchy, $clickIndexHerarchy);
                    $tree_html .= '<div id="child_'.$this->click_id.'" style="display:block; padding:0px;">';
                    $tree_html .= $tmpArray[$this->click_id]["html"];
                    $tree_html .= '</div>';
                }
            }
        } else if($this->edit_id != ""){
            // edit_id以下のみ出力
            if($this->sel_mode == "editPrivatetree"){
                if($this->edit_id == $this->Session->getParameter("MyPrivateTreeRootId") ){
                    // 全表示
                    $idx_data = $this->getIndexData($this->edit_id);
                    // get root open tree data
                    $this->getRootPrivateTreeHTML($idx_data, $tree_html);
                    $tmpArray = array();
                    $this->getShowIndex($tmpArray, array($idx_data["index_id"]), 0);
                    $tree_html .= $tmpArray[$idx_data["index_id"]]["html"];
                    $tree_html .= "</div>";
                } else {
                    $tmpArray = array();
                    if(!isset($this->privateTreeRootHierarchy))
                    {
                        $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
                    }
                    $editIndexHerarchy = $this->getHierarchy($this->edit_id);
                    $idx_data = $this->getIndexData($this->edit_id);
                    $idx_data["hierarchy"] = $editIndexHerarchy - $this->privateTreeRootHierarchy - 1;
                    $this->getShowIndex($tmpArray, array($this->edit_id), 0, $editIndexHerarchy - $this->privateTreeRootHierarchy - 1);
                    if(count($tmpArray) > 0){
                        $this->outputTreeHtml($idx_data, true, $tree_html);
                        if(is_numeric(strpos($this->open_ids, ",".$this->edit_id.","))){
                            $tree_html .= '<div id="child_'.$this->edit_id.'" style="display:block; padding:0px;">';
                        } else {
                            $tree_html .= '<div id="child_'.$this->edit_id.'" style="display:none"; padding:0px;>';
                        }
                        $tree_html .= $tmpArray[$this->edit_id]["html"];
                        $tree_html .= '</div>';
                    } else {
                        // ツリークローズ  this node will close
                        $this->open_ids = str_replace(",".$this->edit_id.",", ",", $this->open_ids);
                        $this->outputTreeHtml($idx_data, false, $tree_html);
                    }
                }
            } else {
                if($this->edit_id == "0"){
                    // 全表示
                    if($this->sel_mode=="edit"){
                        // set root index show HTML
                        $this->getRootIndexHTML($tree_html, 'edit');
                    } else if($this->sel_mode=="rootcheck"){
                        // set root index show HTML
                        $this->getRootIndexHTML($tree_html, 'rootcheck');
                    }
                    $tmpArray = array();
                    if(!isset($this->privateTreeRootHierarchy))
                    {
                        $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
                    }
                    $this->getShowIndex($tmpArray, array(0), $this->privateTreeRootHierarchy);
                    $tree_html .= $tmpArray[0]["html"];
                    if($this->sel_mode=="edit" || $this->sel_mode=="rootcheck"){
                        $tree_html .= "</div>";
                    }
                } else {
                    $tmpArray = array();
                    if(!isset($this->privateTreeRootHierarchy))
                    {
                        $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
                    }
                    $editIndexHerarchy = $this->getHierarchy($this->edit_id);
                    $idx_data = $this->getIndexData($this->edit_id);
                    $idx_data["hierarchy"] = $editIndexHerarchy;
                    $this->getShowIndex($tmpArray, array($this->edit_id), $this->privateTreeRootHierarchy, $editIndexHerarchy);
                    if(count($tmpArray) > 0){
                        $this->outputTreeHtml($idx_data, true, $tree_html);
                        if(is_numeric(strpos($this->open_ids, ",".$this->edit_id.","))){
                            $tree_html .= '<div id="child_'.$this->edit_id.'" style="display:block; padding:0px;">';
                        } else {
                            $tree_html .= '<div id="child_'.$this->edit_id.'" style="display:none; padding:0px;">';
                        }
                        $tree_html .= $tmpArray[$this->edit_id]["html"];
                        $tree_html .= '</div>';
                    } else {
                        // ツリークローズ  this node will close
                        $this->open_ids = str_replace(",".$this->edit_id.",", ",", $this->open_ids);
                        $this->outputTreeHtml($idx_data, false, $tree_html);
                    }
                }
            }
        } else {
            // 初回表示(全表示)
            if($this->sel_mode=="editPrivatetree"){
                $idx_data = $this->getIndexData($this->Session->getParameter("MyPrivateTreeRootId"));
                // get root open tree data
                $this->getRootPrivateTreeHTML($idx_data, $tree_html);
                $tmpArray = array();
                $this->getShowIndex($tmpArray, array($idx_data["index_id"]), 0);
                $tree_html .= $tmpArray[$idx_data["index_id"]]["html"];
                $tree_html .= "</div>";
            } else {
                if($this->sel_mode=="edit"){
                    // set root index show HTML
                    $this->getRootIndexHTML($tree_html, 'edit');
                } else if($this->sel_mode=="rootcheck"){
                    // set root index show HTML
                    $this->getRootIndexHTML($tree_html, 'rootcheck');
                }
                $tmpArray = array();
                if(!isset($this->privateTreeRootHierarchy))
                {
                    $this->privateTreeRootHierarchy = $this->getHierarchy($this->admin_params_['privatetree_parent_indexid']);
                }
                $this->getShowIndex($tmpArray, array(0), $this->privateTreeRootHierarchy);
                if(isset($tmpArray[0]["html"]))
                {
                    $tree_html .= $tmpArray[0]["html"];
                }
                if($this->sel_mode=="edit" || $this->sel_mode=="rootcheck"){
                    $tree_html .= "</div>";
                }
            }
//            if($this->sel_mode == ""){
//                // get root open tree data
//                $view_tree_html = "";
//                if(isset($tmpArray[0]["session_html"]))
//                {
//                    $view_tree_html = str_replace("'", "\'", $tmpArray[0]["session_html"]);
//                }
//                $this->Session->setParameter("view_tree_html", $view_tree_html);
//            }

        }
        ///////////////////////////////
        // set session open node id
        ///////////////////////////////
        $len = strlen($this->open_ids);
        $this->open_ids = substr($this->open_ids,1,$len-2);
        if($this->sel_mode == "check" || $this->sel_mode == "harvestingCheck"){
            $this->Session->setParameter("view_open_node_index_id_insert_item", $this->open_ids);
        } else if($this->sel_mode == "rootcheck"){
            $this->Session->setParameter("view_open_node_index_id_default_index", $this->open_ids);
        } else if($this->sel_mode == "link"){
            $this->Session->setParameter("view_open_node_index_id_item_link", $this->open_ids);
        } else if($this->sel_mode == "sel" || $this->sel_mode == "els"){
            $this->Session->setParameter("view_open_node_index_id_select", $this->open_ids);
        } else if($this->sel_mode == "edit"){
            $this->Session->setParameter("view_open_node_index_id_edit", $this->open_ids);
        // Add edit privatetree K.Matsuo 2012/04/15 --start--
        } else if($this->sel_mode == "editPrivatetree"){
            $this->Session->setParameter("view_open_node_index_id_editPrivatetree", $this->open_ids);
        // Add edit privatetree K.Matsuo 2012/04/15 --end--
        } else if($this->sel_mode == "detail_search"){
            // replace for detail search
            $id = $this->id_module;
            $tree_html = str_replace('child_', 'detail_child'. $id. '_', $tree_html);
        } else {
            $this->Session->setParameter("view_open_node_index_id", $this->open_ids);
        }
        ///////////////////////////////
        // html download
        ///////////////////////////////
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download($tree_html, "index_tree.html");
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--

        ///////////////////////////////
        // end
        ///////////////////////////////
        exit();
    }

    /**
     * check click index data
     * $not_for_html
     *
     */
    function getIndexData($id){
        // get click node data for make html
        $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ". $id ." AND ".
                 "is_delete = 0; ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)==0) {
            return "";
        }
        if(count($result)==1){
            return $result[0];
        }

    }

    /**
     * out root index's HTML
     *
     * @param $tree_html index tree view HTML
     */
    function getRootIndexHTML(&$tree_html, $type){
        if($type == 'edit'){
            $tree_html .= '<div class="node_repos0" id="tree_node0" style="display: block;">';
            //$tree_html .= '<img class="folder_repos" id="tree_folderimg0" unselectable="on" ';
            //$tree_html .= ' src="'.BASE_URL.'/images/repository/tree/folderclose.png" ';  // Modify Directory specification K.Matsuo 2011/9/1
            //$tree_html .= ' onclick="clickSelect(\'0\');" ';
            //$tree_html .= ' onmouseover="dragOver(\'0\');"';
            //$tree_html .= ' onmouseout="dragOut(\'0\');"';
            //$tree_html .= ' onmouseup="dragMouseUp(\'0\', \'true\');"';
            //$tree_html .= ' />';
            $tree_html .= '<a class="nodelabel_repos" id="tree_nodelabel0" unselectable="on" name="root_tree" ';
            $tree_html .= ' onclick="repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\'0\');" ';
            $tree_html .= ' onmouseover="dragOver(\'0\');"';
            $tree_html .= ' onmouseout="dragOut(\'0\');"';
            $tree_html .= ' onmouseup="dragMouseUp(\'0\', \'true\');"';
            $tree_html .= ' >';
            $tree_html .= '<div id="tree_nodeblock0"  class="indexLine">';
            $tree_html .= $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
            $tree_html .= ' </div>';
            $tree_html .= ' </a>';
            $tree_html .= '<div id="tree_sentry0" class="sentry_repos" ';
            $tree_html .= ' style="display: block;" ';
            $tree_html .= ' onmouseover="dragSentryOver(\'0\');"';
            $tree_html .= ' onmouseout="dragSentryOut(\'0\');"';
            $tree_html .= ' onmouseup="dragMouseUp(\'0\', \'first\');"';
            $tree_html .= '>';
            $tree_html .= '</div>';
        } else if($type == 'check' || $this->sel_mode == "harvestingCheck"){
            // insert folder
            //$tree_html .= '<img class="folder_repos" id="tree_folderimg0"';
            //$tree_html .= ' unselectable="on"';
            //$tree_html .= ' src="'.BASE_URL.'/images/repository/tree/folderclose.png" ';  // Modify Directory specification K.Matsuo 2011/9/1
            //$tree_html .= ' />';
            $tree_html .= '<input type="checkbox" class="chk_repos" id="tree_check0" ';
            $tree_html .= ' unselectable="on"';
            if($this->chk_index_id == 0){
                $tree_html .= ' checked ';
            }
            $tree_html .= ' onclick="repositoryClickTreeCheck_'.$this->block_info["block_id"].'(0, \'';
            $tree_html .= $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
            $tree_html .= '\');" />';
            // insert index name
            $tree_html .= $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
        } else if($type == 'rootcheck'){
            // insert folder
            //$tree_html .= '<img class="folder_repos" id="tree_folderimg0"';
            //$tree_html .= ' unselectable="on"';
            //$tree_html .= ' src="'.BASE_URL.'/images/repository/tree/folderclose.png" ';  // Modify Directory specification K.Matsuo 2011/9/1
            //$tree_html .= ' />';
            $tree_html .= '<div id="tree_node0" class="node_repos0">';
            $tree_html .= '<input type="checkbox" class="chk_repos" id="tree_check0" ';
            $tree_html .= ' unselectable="on"';
            if($this->chk_index_id == 0){
                $tree_html .= ' checked ';
            }
            $tree_html .= ' onclick="repositoryClickTreeCheck_'.$this->block_info["block_id"].'(0, \'';
            $tree_html .= $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
            $tree_html .= '\');" />';
            // insert index name
            $tree_html .= '<label for="tree_check0" style="width:100%" >';
            $tree_html .= $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
            $tree_html .= '</label>';
        }
    }

    /**
     * check access right for insert item
     *
     * @param $access_role access auth's room_id delemit is ","
     * @param $access_group access group's room_id delemit is ","
     * @param $indexId check index id
     */
    function checkAccessIndex($access_role, $access_group, $indexId){
        // Add config management authority 2010/02/23 Y.Nakao --start--

        $user_id = $this->Session->getParameter("_user_id");
        $query = " SELECT count(*) ".
                " FROM ".DATABASE_PREFIX."repository_index ".
                " WHERE index_id = ? ".
                " AND owner_user_id = ? ".
                " AND is_delete = 0; ";
        $params = array();
        $params[] = $indexId;
        $params[] = $user_id;
        $result = $this->Db->execute($query, $params);
        if(count($result) != 0 && $result[0]['count(*)'] != 0) {
            return true;
        }
        if(strlen(str_replace(",","",str_replace("|","",$access_role))) == 0 &&
            strlen(str_replace(",","",$access_group)) == 0){
            return false;
        }
        $base_auth = "";
        $room_auth = 0;
        $role = explode("|", $access_role);
        if(count($role) == 0){
            return false;
        } else if(count($role) == 1){
            $base_auth = $role[0];
            $room_auth = _AUTH_CHIEF;
        } else if(count($role) >= 2){
            $base_auth = $role[0].",";
            $room_auth = substr($role[1], 0, 1); // Fix tree insert item authority Y.Nakao 2011/12/02
        }
        if(strlen($room_auth) == 0){
            $room_auth = _AUTH_CHIEF;
        }
        // get user's role auth id
        $query = "SELECT role_authority_id FROM ". DATABASE_PREFIX ."users ".
                 "WHERE user_id = ?; ";
        $params = array();
        $params[] = $this->Session->getParameter("_user_id");
        $role_auth_id = $this->Db->execute($query, $params);
        if($role_auth_id === false || count($role_auth_id)!=1) {
            return false;
        }
        if(is_numeric(strpos($base_auth, ",".$role_auth_id[0]["role_authority_id"].","))){
            // base authority is OK
            //check room authority
            if(intval($this->auth_id) >= intval($room_auth)) {
                return true;
            }
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--

        // get user's entry groups
        $result = $this->getUsersGroupList($groups, $error);
        for($ii=0; $ii<count($groups); $ii++){
            if(is_numeric(strpos($access_group, ",".$groups[$ii]["room_id"].","))){
                return true;
            }
        }
        return false;
    }

    /**
     * check index public status
     *
     * @param $idx_id index_id
     * @return bool true:public / false:closed
     */
    function checkPublic($idx_id){
        $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index ".
                " WHERE index_id = ".$idx_id." ".
                " AND `public_state` = 1 ".
                //" AND `pub_date` <= '".date('Y-m-d 00:00:00.000',mktime())."'; ";
                " AND `pub_date` <= NOW() ".
                " AND `owner_user_id` = '';";   // Add not show privateTree K.Matsuo 2013/04/10
        $result = $this->Db->execute($query);
        if($result === false) {
            return false;
        }
        if(count($result)==0){
            return false;
        }
        return true;
    }
    /**
     * out my privatetree root index's HTML
     *
     * @param $data my privatetree root
     * @param $tree_html index tree view HTML
     */
    function getRootPrivateTreeHTML($data, &$tree_html){
        $id = $data['index_id'];
        if($this->lang == "japanese")
        {
            if(strlen($data["index_name"]) > 0)
            {
                $name = htmlspecialchars($data["index_name"]);
            }
            else
            {
                $name = htmlspecialchars($data["index_name_english"]);
            }
        } else {
            if(strlen($data["index_name_english"]) > 0)
            {
                $name = htmlspecialchars($data["index_name_english"]);
            }
            else
            {
                $name = htmlspecialchars($data["index_name"]);
            }
        }
        $tree_html .= '<div class="node_repos0" id="tree_node'.$id.'" style="display: block;">';
        $tree_html .= '<a class="nodelabel_repos" id="tree_nodelabel'.$id.'" unselectable="on" name="root_private_tree" ';
        $tree_html .= ' onclick="repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\');"';
        $tree_html .= ' onmouseover="dragOver(\''.$id.'\');"';
        $tree_html .= ' onmouseout="dragOut(\''.$id.'\');"';
        $tree_html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'true\');"';
        $tree_html .= ' >';
        $tree_html .= '<div id="tree_nodeblock'.$id.'"  class="indexLine">';
        $tree_html .= $name;
        $tree_html .= ' </div>';
        $tree_html .= ' </a>';
        $tree_html .= '<div id="tree_sentry'.$id.'" class="sentry_repos" ';
        $tree_html .= ' style="display: block;" ';
        $tree_html .= ' onmouseover="dragSentryOver(\''.$id.'\');"';
        $tree_html .= ' onmouseout="dragSentryOut(\''.$id.'\');"';
        $tree_html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'first\');"';
        $tree_html .= '>';
        $tree_html .= '</div>';
    }

    // Add output tree 2013/06/13 K.Matsuo --start--
    /**
     * get public index array
     *  closed index under is not get
     *
     * @param array $ret return search result 取得結果
     * @param array $idx_id search parent_index_id 親インデックスID
     * @param array $checkPrivateTreeHierarchy hierarchy check private tree プライベートツリーが存在する階層（true：表示する）
     * @param array $hierarchy set hierarchy 現在の階層
     * @param array[$ii][room_id] $usersGroups // Add tree access control list 2012/02/29 T.Koyasu ユーザーグループリスト（権限判定）
     */
    public function getShowIndex(&$ret, $idx_id, $checkPrivateTreeHierarchy=0, $hierarchy=0, $usersGroups=array() ){
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        
        if($checkPrivateTreeHierarchy == $hierarchy && $this->admin_params_['is_make_privatetree'] == 1 && $this->private_root_select != 1 && $this->sel_mode != "sel")
        {
            $query = $this->getMixturePrivateTreeQuery($idx_id);
        }
        else
        {
            $query = "SELECT * ".
	                 "FROM {repository_index} ".
	                 "WHERE parent_index_id IN (".implode(",", $idx_id).") ".
	                 "AND is_delete = 0 ";
	        if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room || $this->sel_mode == "rootcheck"){
	            $query .= "AND index_id IN (".$this->getPublicIndexQuery().") ";
	        }
	        if($this->sel_mode == "rootcheck" || $this->sel_mode == "harvestingCheck" || $this->sel_mode == "sel"){
	            $query .= "AND `owner_user_id` = '' ";
	        }
	        $query .= "ORDER BY `show_order`; ";
	    }
        
        $retIndexIds = $this->Db->execute($query);
        if($retIndexIds === false){
            return;
        }
        if(count($retIndexIds) == 0){
            return;
        }
        if(count($retIndexIds) > 0){

            // when $hierarchy is negative integer, not search lower tree.
            if($hierarchy > -1)
            {
                $tmpArray = array();
                $indexArray = array();
                for($ii=0; $ii<count($retIndexIds); $ii++){
                    if(is_numeric(strpos(",".$this->open_ids.",", ",".$retIndexIds[$ii]['index_id'].",")))
                    {
                        array_push($indexArray, $retIndexIds[$ii]['index_id']);
                    }
                    else if($this->sel_mode == "detail_search" && isset($this->detail_all_check))
                    {
                        if($this->open_ids == ",,"){
                            $this->open_ids = ",".$retIndexIds[$ii]['index_id'].",";
                        } else {
                            $this->open_ids .= $retIndexIds[$ii]['index_id'].",";
                        }
                        array_push($indexArray, $retIndexIds[$ii]['index_id']);
                    }
                }
                if(count($indexArray) > 0)
                {
                    $this->getShowIndex($tmpArray, $indexArray, $checkPrivateTreeHierarchy, $hierarchy+1, $usersGroups);
                }
            }

            if($this->sel_mode == ""){
                $more_flg = true;
            } else {
                $more_flg = false;
            }
            for($ii=0; $ii<count($retIndexIds); $ii++){
                $retIndexIds[$ii]['hierarchy'] = $hierarchy + 1;
                // arrayにデータ追加(more表示を行うかの確認)
                if(!isset($ret[$retIndexIds[$ii]["parent_index_id"]])){
                    $ret[$retIndexIds[$ii]["parent_index_id"]] = array();
                    $tmpData = $this->getIndexData($retIndexIds[$ii]["parent_index_id"]);
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["more_flag"] = $more_flg;

                    // Add confirm that array's index exists  2013/09/13 K.Matsushita --start--
                    if(isset($tmpData["display_more"])){
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["display_more"] = $tmpData["display_more"];
                    }
                    // Add confirm that array's index exists  2013/09/13 K.Matsushita --end--

                    if(($retIndexIds[$ii]["parent_index_id"] == "0" || $ret[$retIndexIds[$ii]["parent_index_id"]]["display_more"] == "")){    // modify array's index  2013/09/13 K.Matsushita
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["more_flag"] = false;
                    }
                    if($ret[$retIndexIds[$ii]["parent_index_id"]]["more_flag"] && $this->sel_mode == "" && $this->Session->getParameter("searchIndexId") != ""){
                        $search_index = $this->getIndexData($this->Session->getParameter("searchIndexId"));
                        // 検索インデックスと親インデックスが一致しているか check search index's parent index is equal $parent_index_id
                        if(is_array($search_index) && array_key_exists("parent_index_id", $search_index)
                            && $search_index["parent_index_id"] == $retIndexIds[$ii]["parent_index_id"]){
                            // 表示範囲が決められているか check display more is setting
                            if($index_data["display_more"]!="" &&
                                $search_index["show_order"] > $index_data["display_more"]){
                                // 表示範囲外に検索結果がある場合、表示範囲を狭めない
                                // when search index is in display "more...", not display "more..."
                                $ret[$retIndexIds[$ii]["parent_index_id"]]["more_flag"] = false;
                            }
                        }
                    }
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] = "";
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] = "";
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["setNum"] = 0;
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] = "";
                }
                // Check has child
                $hasChild = false;
                $query = "SELECT COUNT(*) ".
                         "FROM {repository_index} ".
                         "WHERE parent_index_id = ? ".
                         "AND is_delete = 0 ";
                if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room || $this->sel_mode == "rootcheck"){
                    $query .= "AND index_id IN (".$this->getPublicIndexQuery().") ";
                }
                if($this->sel_mode == "rootcheck" || $this->sel_mode == "harvestingCheck" || $this->sel_mode == "sel"){
                    $query .= "AND `owner_user_id` = '' ";
                }
                $params = array();
                $params[] = $retIndexIds[$ii]["index_id"];
                $retChildCount = $this->Db->execute($query, $params);
                if($retChildCount !== false && $retChildCount[0]["COUNT(*)"] > 0){
                    $hasChild = true;
                }

                // more表示ではない領域
                if($ret[$retIndexIds[$ii]["parent_index_id"]]["more_flag"] == false
                || $ret[$retIndexIds[$ii]["parent_index_id"]]["setNum"] < intval($ret[$retIndexIds[$ii]["parent_index_id"]]["display_more"] ))
                {
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .=  '<div class="node_repos0" unselectable="on" id="tree_node'.$retIndexIds[$ii]["index_id"].'" style="display: block;">';
                    $nodeText = "";
                    if(isset($tmpArray[$retIndexIds[$ii]["index_id"]]["html"])){
                        $this->outputTreeHtml($retIndexIds[$ii], $hasChild, $nodeText);
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= $nodeText;
                        if(is_numeric(strpos($this->open_ids, ",".$retIndexIds[$ii]["index_id"].","))){
                            $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= '<div id="child_'.$retIndexIds[$ii]["index_id"].'" style="display:block; padding:0px;">';
                        } else {
                            $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= '<div id="child_'.$retIndexIds[$ii]["index_id"].'" style="display:none; padding:0px;">';
                        }
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= $tmpArray[$retIndexIds[$ii]["index_id"]]["html"];
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= '</div>';
                    } else {
                        $this->outputTreeHtml($retIndexIds[$ii], $hasChild, $nodeText);
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= $nodeText;
                    }
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["html"] .= '</div>';
                    if($this->sel_mode==""){
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .=  '<div class="node_repos0" unselectable="on" id="tree_node'.$retIndexIds[$ii]["index_id"].'" style="display: block;">';
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .= $nodeText;
                        if(isset($tmpArray[$retIndexIds[$ii]["index_id"]]["session_html"])){
                            if(is_numeric(strpos($this->open_ids, ",".$retIndexIds[$ii]["index_id"].","))){
                                $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .= '<div id="child_'.$retIndexIds[$ii]["index_id"].'" style="display:block; padding:0px;">';
                                $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .= $tmpArray[$retIndexIds[$ii]["index_id"]]["session_html"];
                                $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .= '</div>';
                            }
                        }
                    }
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["session_html"] .= '</div>';
                    if(($this->sel_mode=="edit" || $this->sel_mode=="editPrivatetree") ){
                        $this->getSentryHTML($retIndexIds[$ii], $ret[$retIndexIds[$ii]["parent_index_id"]]["html"]);
                    }
                } else {
                    //more 表示の領域
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .=  '<div class="node_repos0" unselectable="on" id="tree_node'.$retIndexIds[$ii]["index_id"].'" style="display: block;">';
                    if(isset($tmpArray[$retIndexIds[$ii]["index_id"]]["html"])){
                        $this->outputTreeHtml($retIndexIds[$ii], $hasChild, $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"]);
                        if(is_numeric(strpos($this->open_ids, ",".$retIndexIds[$ii]["index_id"].","))){
                            $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .= '<div id="child_'.$retIndexIds[$ii]["index_id"].'" style="display:block; padding:0px;">';
                        } else {
                            $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .= '<div id="child_'.$retIndexIds[$ii]["index_id"].'" style="display:none; padding:0px;">';
                        }
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .= $tmpArray[$retIndexIds[$ii]["index_id"]]["html"];
                        $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .= '</div>';
                    } else {
                        $this->outputTreeHtml($retIndexIds[$ii], $hasChild, $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"]);
                    }
                    $ret[$retIndexIds[$ii]["parent_index_id"]]["moreAreaHtml"] .= '</div>';
                }
                $ret[$retIndexIds[$ii]["parent_index_id"]]["setNum"]++;
            }

        }
        $id = "_".$this->block_info["block_id"];
        foreach ($ret as $key => $value){
            if($value["moreAreaHtml"] != ""){
                $idxData = array();
                $idxData["index_id"] = $key;
                $idxData["hierarchy"] = $hierarchy + 1;
                $ret[$key]["html"] .= '<div id="more'.$key.'" class="nodeline_repos" unselectable="on">';
                $this->addFolderOpenCloseIcon($idxData, false, $ret[$key]["html"]);
                $ret[$key]["html"] .= '<a onclick="javascript: repositoryClickMoreTree'.$id.'(\''.$key.'\', \'tree_node'.$key.'\');" >';
                $ret[$key]["html"] .= 'more...';
                $ret[$key]["html"] .= '</a>';
                $ret[$key]["html"] .= '</div>';
                $ret[$key]["html"] .= '<div id="moreHiddenArea'.$key.'" unselectable="on" style="display:none" >';
                $ret[$key]["html"] .= $value["moreAreaHtml"];
                $ret[$key]["html"] .= '</div>';
            }
        }
    }
    /**
     * get public index array
     *  closed index under is not get
     *
     * @param array $ret return search result
     * @param array $idx_id search parent_index_id
     * @param array[$ii][room_id] $usersGroups // Add tree access control list 2012/02/29 T.Koyasu
     */
    public function outputTreeHtml($indexData, $hasChild, &$html){
        $name = "";
        if($this->lang == "japanese")
        {
            if(strlen($indexData["index_name"]) > 0)
            {
                $name = htmlspecialchars($indexData["index_name"]);
            }
            else
            {
                $name = htmlspecialchars($indexData["index_name_english"]);
            }
        } else {
            if(strlen($indexData["index_name_english"]) > 0)
            {
                $name = htmlspecialchars($indexData["index_name_english"]);
            }
            else
            {
                $name = htmlspecialchars($indexData["index_name"]);
            }
        }
        $indexData["name"] = $name;
        if($this->sel_mode=="check" || $this->sel_mode == "harvestingCheck"){        // for import
            $this->outputCheckTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode=="rootcheck"){
            $this->outputRootCheckTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode=="link"){
            $this->outputLinkTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode=="sel"){
            $this->outputSelTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode=="els"){        // for els
            $this->outputELSTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode == "edit" || $this->sel_mode == "editPrivatetree"){        // for edit and privateTree edit
            $this->outputEditTreeHtml($indexData, $hasChild, $html);
        } else if($this->sel_mode == "subIndexList"){
            // Bug Fix get index list by triangle in index list T.Koyasu 2014/07/24 --start--
            $this->outputSnippetTreeHtml($indexData, false, $html);
            // Bug Fix get index list by triangle in index list T.Koyasu 2014/07/24 --end--
        } else if($this->sel_mode){
            $this->outputDetailSearch($indexData, $hasChild, $html);
        } else {            // for top
            $this->outputSnippetTreeHtml($indexData, $hasChild, $html);
        }
    }
    // Add output tree 2013/06/14 K.Matsuo --end--

    // Add output tree 2013/06/17 K.Matsuo --start--
    /**
     * get check(import) tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputCheckTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];
        $name = $indexData["name"];
        $name_script = str_replace("'", "\'", $name);
        $access_group = ",".$indexData["access_group"].",";
        $access_role = ",".$indexData["access_role"].",";
        // insert folder

        $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine" >';
        if($this->checkAccessIndex($access_role, $access_group, $id)){
            $html .= '<label for="tree_check'.$id.'" style="display:inline-block; width:100%" >';
            $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
            // when access day was gone public date.
            // this index is public and not privatetree.
            $html .= '<input type="checkbox" class="chk_repos" id="tree_check'.$id.'" ';
            $html .= ' unselectable="on" ';
            if( $this->chk_index_id == $id ){
                $html .= ' checked ';
            }
            $html .= ' onclick="repositoryClickTreeCheck_'.$this->block_info["block_id"].'('.$id.', \''.$name_script.'\');" />';
            $html .= $name.'</label>';
        } else {
            $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
            $html .= $name;
        }
        // insert index name
        $html .= '</div>';
    }
    /**
     * get root check(manage, harvest) tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputRootCheckTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];
        $name = $indexData["name"];
        $name_script = str_replace("'", "\'", $name);
        $access_group = ",".$indexData["access_group"].",";
        $access_role = ",".$indexData["access_role"].",";
        // insert folder
        // insert check box
        // publick check
        $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine" >';
        $html .= '<label for="tree_check'.$id.'" style="display:inline-block; width:100%" >';
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        // when access day was gone public date.
        // this index is public and not privatetree.
        $html .= '<input type="checkbox" a class="chk_repos" id="tree_check'.$id.'" ';
        $html .= ' unselectable="on"';
        if( $this->chk_index_id == $id ){
            $html .= ' checked ';
        }
        $html .= ' onclick="repositoryClickTreeCheck_'.$this->block_info["block_id"].'('.$id.', \''.$name_script.'\');" />';
        $html .= $name.'</label>';

        $html .= '</div>';
    }

    /**
     * get link(item regist) tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputLinkTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];
        $name = $indexData["name"];
        $name_script = str_replace("'", "\'", $name);
        $access_group = ",".$indexData["access_group"].",";
        $access_role = ",".$indexData["access_role"].",";
        if($this->Session->getParameter("search_index_id_link") == $id){
            // insert click ivent
            $html .= '<a class="nodelabel_s_repos" name="link_tree" ';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            $html .= ' onclick="javascript: repositoryCls[\'_'.$this->block_info["block_id"].'\'].sendSearchKeywordItemLink(\'index_search\', '.$id.');"';
            $html .=  '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLineSelect" >';
        } else {
            // insert click ivent
            $html .= '<a class="nodelabel_repos" name="link_tree" ';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            $html .= ' onclick="javascript: repositoryCls[\'_'.$this->block_info["block_id"].'\'].sendSearchKeywordItemLink(\'index_search\', '.$id.');"';
            $html .= '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine">';
        }
        // insert folder
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        if($this->checkAccessIndex($access_role, $access_group, $id)){
            // insert check box
            $html .= '<input type="checkbox" class="chk_repos" id="tree_check'.$id.'" ';
            $html .= ' unselectable="on"';
            if( is_numeric(strpos($this->CheckedIds, "|".$id."|")) ){
                $html .= ' checked ';
            }
            $html .= ' onclick="javascript: repositoryClickTreeCheck_'.$this->block_info["block_id"].'(event, '.$id.', \''.$name_script.'\'); return true;" />';
        }
        // insert search action
        $html .= $name;
        $html .= '</div>';
        $html .= '</a>';
    }
    /**
     * get sel(item manage) tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputSelTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];
        $name = $indexData["name"];
        $name_script = str_replace("'", "\'", $name);
        // insert click ivent
        if($this->Session->getParameter("searchIndexId") == $id){
            $html .= '<a class="nodelabel_s_repos" name="index_tree"';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\', \''.$name_script.'\');"';
            $html .= '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLineSelect">';
        } else {
            $html .= '<a class="nodelabel_repos" name="index_tree"';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\', \''.$name_script.'\');"';
            $html .= '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine">';
        }
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        $html .= $name;
        $html .= '</div>';
        $html .= '</a>';
    }
    /**
     * get els tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputELSTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];
        $name = $indexData["name"];
        $name_script = str_replace("'", "\'", $name);
        // ELS registered index check
        if(in_array($id, $this->elsRegisteredIndex_)){
            $elsRegisteredFlag = "true";
        } else {
            $elsRegisteredFlag = "false";
        }
        // insert click ivent
        $html .= '<a class="nodelabel_repos" name="els_tree" ';
        $html .= ' id="tree_nodelabel'.$id.'"';
        $html .= ' unselectable="on"';
        $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\', \''.$name_script.'\', \''.$elsRegisteredFlag.'\');"';
        $html .= '>';
        $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine">';
        // insert folder
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        $html .= $name;
        $html .= '</div>';
        $html .= '</a>';
    }

    /**
     * get edit tree html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputEditTreeHtml($indexData, $hasChild, &$html)
    {
        $leftMarginP = ($indexData["hierarchy"]+1) * $this->childLeftMargin;
        $id = $indexData["index_id"];
        
        // all private tree can not edit in tree edit
        // root private tree can not edit in private tree edit
        $owner_user_id = $indexData["owner_user_id"];
        $pid = $indexData["parent_index_id"];
        $errMsg = "";
        $this->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $errMsg);
        
        if($this->Session->getParameter("edit_index") == $id){
            // insert click ivent
            $html .= '<a ';
            $html .= ' id="tree_nodelabel'.$id.'" name="edit_tree" ';
            $html .= ' unselectable="on"';
            $html .= ' class="nodelabel_s_repos"; ';
            if($this->sel_mode == "edit"){
                if(strlen($owner_user_id) === 0){
                    $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\');"';
                }
            } else if($this->sel_mode == "editPrivatetree"){
                $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\');"';
            }
            $html .= ' onmouseover="dragOver(\''.$id.'\');"';
            $html .= ' onmouseout="dragOut(\''.$id.'\');"';
            $html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'true\');"';
            $html .= '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLineSelect">';
        } else {
            // insert click ivent
            $html .= '<a class="nodelabel_repos" name="edit_tree"';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            if($this->sel_mode == "edit"){
                if(strlen($owner_user_id) === 0){
                    $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\');"';
                }
            } else if($this->sel_mode == "editPrivatetree"){
                $html .= ' onclick="javascript: repositoryClickTreeSelect_'.$this->block_info["block_id"].'(\''.$id.'\');"';
            }
            $html .= ' onmouseover="dragOver(\''.$id.'\');"';
            $html .= ' onmouseout="dragOut(\''.$id.'\');"';
            $html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'true\');"';
            $html .= '>';
            $html .= '<div id="tree_nodeblock'.$id.'" class="indexLine">';
        }
        // insert folder
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        $html .= $indexData["name"];
        $html .= '</div>';
        $html .= '</a>';
        // insert sentry
        if($hasChild ){
            // for front
            $html .= '<div id="tree_sentryP'.$id.'" class="sentryP_repos" ';
            if(is_numeric(strpos($this->open_ids, ",".$id.",")) ){
                $html .= ' style="display: block; margin-left:'.$leftMarginP.'px" ';
            } else {
                $html .= ' style="display: none; margin-left:'.$leftMarginP.'px" ';
            }
            $html .= ' unselectable="on"';
            $html .= ' onmouseover="dragSentryPOver(\''.$id.'\');"';
            $html .= ' onmouseout="dragSentryPOut(\''.$id.'\');"';
            $html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'first\');"';
            $html .= '><div></div>';    // for mouseover on IE
            $html .= '</div>';
        }
    }
    function getSentryHTML($indexData, &$tree_html){
        $leftMargin = ($indexData["hierarchy"]) * $this->childLeftMargin;
        $id = $indexData["index_id"];
        $tree_html .= '<div id="tree_sentry'.$id.'" class="sentry_repos" ';
        $tree_html .= ' style="display: block; margin-left:'.$leftMargin.'px;" ';
        $tree_html .= ' unselectable="on"';
        $tree_html .= ' onmouseover="dragSentryOver(\''.$id.'\');"';
        $tree_html .= ' onmouseout="dragSentryOut(\''.$id.'\');"';
        $tree_html .= ' onmouseup="dragMouseUp(\''.$id.'\', \'false\');"';
        $tree_html .= '><div></div>';   // for mouseover on IE
        $tree_html .= '</div>';
    }

    /**
     * get snippet html
     *
     * @param $indexData show index data
     * @param $hasChild is index had child
     * @param $html output html
     */
    private function outputSnippetTreeHtml($indexData, $hasChild, &$html)
    {
        $id = $indexData["index_id"];

        $sort_order = 0;
        //キーワードが指定されていなかった場合 インデックス検索開始状態にする
        if($this->Session->getParameter("searchkeyword") == null || strlen($this->Session->getParameter("searchkeyword")) == 0)
        {
            $sort_order = $this->Session->getParameter("sort_order");
        }

        if($this->Session->getParameter("searchIndexId") == $id){
            $html .= '<a class="nodelabel_s_repos" name="snipet_tree"';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            // Add Parameter list_view_num sort_order page_no 2009/12/11 K.ando ---start---
            $html .= ' href="'.BASE_URL.'/?action=repository_opensearch&index_id='.$id.    // change id into $id 2013/09/13 K.Matsushita
                        '&count='.$this->Session->getParameter("list_view_num").
                        '&order='.$sort_order.
                        '&pn=1"';
            // Add Parameter list_view_num sort_order page_no 2009/12/11 K.ando ---end---
            $html .=  '>';
            $html .= '<div id="tree_nodeblock'.$id.'"  class="indexLineSelect" >';
        } else {
            // insert click ivent
            $html .= '<a class="nodelabel_repos" name="snipet_tree"';
            $html .= ' id="tree_nodelabel'.$id.'"';
            $html .= ' unselectable="on"';
            // Add Parameter list_view_num sort_order page_no 2009/12/11 K.ando ---start---
            $html .= ' href="'.BASE_URL.'/?action=repository_opensearch&index_id='.$id.
                        '&count='.$this->Session->getParameter("list_view_num").
                        '&order='.$sort_order.
                        '&pn=1"';
            // Add Parameter list_view_num sort_order page_no 2009/12/11 K.ando ---end---
            $html .=  '>';
            $html .= '<div id="tree_nodeblock'.$id.'"  class="indexLine" >';
        }
        // insert folder
        $this->addFolderOpenCloseIcon($indexData, $hasChild, $html);
        // insert search action
        $html .= $indexData["name"];
        $html .= '</div>';
        $html .= '</a>';
    }
    // Add output tree 2013/06/17 K.Matsuo --end--

    /**
     * add tree open close icon
     *
     * @param $id index id
     * @param $html outputhtml
     */
    public function addFolderOpenCloseIcon($indexData, $hasChild, &$html)
    {
        if( $this->sel_mode == 'edit' || $this->sel_mode == "editPrivatetree" ||  $this->sel_mode == 'rootcheck'){
            $leftMargin = $indexData["hierarchy"] * $this->childLeftMargin;
        } else {
            $leftMargin = ($indexData["hierarchy"] - 1) * $this->childLeftMargin;
        }
        if($indexData["index_id"] != $this->Session->getParameter("MyPrivateTreeRootId") || $this->sel_mode != "edit"){
            if($hasChild){
                // insert open icon
                if($this->sel_mode == "edit" || $this->sel_mode == "editPrivatetree"){
                    // insert draggable image
                    $html .= '<img class="draganddrop" align="left" height="20" style="margin:0 5px 0 '.$leftMargin.'px"';
                    $html .= ' onmouseover="makeDraggable(this, \''.$indexData["index_id"].'\');" >';
                    $html .= '<img class="branch_repos" id="tree_branch'.$indexData["index_id"].'" ';
                    $html .= ' unselectable="on" align="left" ';
                } else {
                    $html .= '<img class="branch_repos" id="tree_branch'.$indexData["index_id"].'" ';
                    $html .= ' unselectable="on" align="left" style="margin-left:'.$leftMargin.'px" ';
                }
                $id = "_".$this->block_info["block_id"];
                if(is_numeric(strpos($this->open_ids, ",".$indexData["index_id"].","))){
                    $html .= 'onclick="javascript: repositoryClickTreeOpenClose'.$id.'(event, '.$indexData["index_id"].', \''.$this->sel_mode.'\'); return false;" ';
                    $html .= 'src="'.BASE_URL.'/images/repository/tree/open.png"';    // Modify Directory specification K.Matsuo 2011/9/1
                } else {
                    $html .= 'onclick="javascript: repositoryClickTreeOpenClose'.$id.'(event, '.$indexData["index_id"].', \''.$this->sel_mode.'\'); return false;" ';
                    $html .= 'src="'.BASE_URL.'/images/repository/tree/close.png"';    // Modify Directory specification K.Matsuo 2011/9/1
                }
                $html .= ' />';
            } else {
                // insert space
                $dummyIconSpace = 16;
                if($this->sel_mode == "edit" || $this->sel_mode == "editPrivatetree"){
                    // insert draggable image
                    $html .= '<img class="draganddrop" align="left" height="20" style="margin:0 '.($dummyIconSpace+5).'px 0 '.$leftMargin.'px"';
                    $html .= ' onmouseover="makeDraggable(this, \''.$indexData["index_id"].'\');" >';
                } else {
                    $html .= '<span class="branch_repos" id="tree_branch'.$indexData["index_id"].'" align="left" style="margin-left:'.($leftMargin + $dummyIconSpace).'px;" ></span>';
                }
            }
        }
    }
    // Add output tree 2013/06/13 K.Matsuo --start--
    // Add sort privateTree K.Matsuo 2013/06/14 --start--
    /**
     * get child index List from registration index of Privatetree
     *
     * @param $parent_index_id parent index id (Privatetree Parent)
     */
    public function getMixturePrivateTreeQuery($idx_id)
    {
        $user_id = $this->Session->getParameter("_user_id");
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        // プライベートツリーのソートオーダー
        $order = " ORDER BY ";
        if($this->admin_params_['privatetree_sort_order'] == 0){
            $order .= "show_order";
        } else {
            $order .= "idxName ";
            if($this->admin_params_['privatetree_sort_order'] == 1){
                $order .= " ASC";
            } else {
                $order .= " DESC";
            }
        }

        if($this->lang == "japanese")
        {
            $priorityName = "index_name";
        } else {
            $priorityName = "index_name_english";
        }
        // プライベートツリー以外のインデックスを取得
        $query = " SELECT * ".
                 " FROM ( ".
                 "  SELECT index_name AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                 "  FROM ". DATABASE_PREFIX ."repository_index ".
                 "  WHERE parent_index_id IN (".implode(",", $idx_id).") ";
        // 管理者ではないとき、公開日を確認する
        if ($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room) {
            // Bug Fix view public index only 2014/08/28 --start--
            $query .= " AND index_id IN (".$this->getPublicIndexQuery().") ";
            // Bug Fix view public index only 2014/08/28 --end--
        }
        $query .= "  AND is_delete = 0 ".
                  "  AND LENGTH( owner_user_id ) = 0 ".
                  "  ORDER BY `show_order` ) AS TABLE1 ";
        // プライベートツリーを取得指定のソート順で取得
        $privateTreeQuery = "SELECT * ".
                            "FROM ( ".
                            " ( ".
                            "  SELECT ".$priorityName." AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                            "  FROM ". DATABASE_PREFIX ."repository_index ".
                            "  WHERE parent_index_id IN (".implode(",", $idx_id).") ".
                            "  AND is_delete = 0 ".
                            "  AND LENGTH( owner_user_id ) > 0 ".
                            "  AND LENGTH( index_name ) > 0 ".
                            "  AND LENGTH( index_name_english ) > 0 ";
        // 管理者ではないとき、公開日を確認する
        if ($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room) {
            $privateTreeQuery .= " AND ((public_state = 1 ".
                                 " AND pub_date <= '".$this->TransStartDate."' ) ".
                                 " OR owner_user_id = '".$user_id."' ) ";
        }
        $privateTreeQuery .= " ) UNION ( ".
                             "  SELECT index_name_english AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                             "  FROM ". DATABASE_PREFIX ."repository_index ".
                             "  WHERE parent_index_id IN (".implode(",", $idx_id).") ".
                             "  AND is_delete = 0 ".
                             "  AND LENGTH( owner_user_id ) > 0 ".
                             "  AND LENGTH( index_name ) = 0 ";
        // 管理者ではないとき、公開日を確認する
        if ($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room) {
            $privateTreeQuery .= " AND ((public_state = 1 ".
                                 " AND pub_date <= '".$this->TransStartDate."' ) ".
                                 " OR owner_user_id = '".$user_id."' ) ";
        }
        $privateTreeQuery .= " ) ";
        $privateTreeQuery .= $order." ) AS TABLE2 ";

        $query .= " UNION ". $privateTreeQuery;
        return $query;
    }
    // Add sort privateTree K.Matsuo 2013/06/14 --end--

    /**
     * get hierarchy infomation
     *
     * @param $idx_id index id
     */
    public function getHierarchy($idx_id)
    {
        $hierarchy = 0;
        if($idx_id == 0)
        {
            return $hierarchy;
        }
        $query = " SELECT parent_index_id ".
                 " FROM ". DATABASE_PREFIX ."repository_index ".
                 " WHERE index_id = ".$idx_id. " ;";

        $result = $this->Db->execute($query);
        if($result === false){
            echo $this->Db->mysqlError();
        }
        if(count($result) > 0){
            $hierarchy = $this->getHierarchy($result[0]["parent_index_id"]) + 1;
        }
        return $hierarchy;
    }

    /**
     * create index tree for detail search
     *
     * @param array  i   $indexData
     * @param bool   i   $hasChild
     * @param string i/o $html
     */
    private function outputDetailSearch($indexData, $hasChild, &$html)
    {
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $indexId = $indexData["index_id"];

        // set index name by show language
        if($this->lang === "japanese")
        {
            $indexName = $indexData["index_name"];
            if(strlen($indexName) === 0)
            {
                $indexName = $indexData["index_name_english"];
            }
        }
        else
        {
            $indexName = $indexData["index_name_english"];
        }

        $id = $this->id_module;

        // set margin left by index structure
        $childMargin = $this->childLeftMargin + 4;
        $leftMarginP = ($indexData["hierarchy"] + 1) * $childMargin;

        // set html
        $childIndexIdStr = '';
        $html .= "<div id=\"detail_tree_nodeblock". $indexId. $id. "\" class=\"indexLine\">";
        if($hasChild)
        {
            // 子インデックスのIDを取得
            $query = "SELECT index_id ".
                     "FROM {repository_index} ".
                     "WHERE parent_index_id = ? ".
                     "AND is_delete = 0 ";
            if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room){
                $query .= "AND index_id IN (".$this->getPublicIndexQuery().") ";
            }
            $params = array();
            $params[] = $indexId;
            $retChildIdx = $this->Db->execute($query, $params);
            foreach ($retChildIdx as $chiledIdx)
            {
                if(strlen($chiledIdx["index_id"]) > 0){
                    if(strlen($childIndexIdStr) > 0){
                        $childIndexIdStr .= ",";
                    }
                    $childIndexIdStr .= $chiledIdx["index_id"];
                }
            }

            $leftMarginP = $leftMarginP - $childMargin;
            $html .= "<img id=\"detail_tree_point_". $indexId. $id. "\" ".
                " class=\"branch_repos\" align=\"left\" ";
            if(is_numeric(strpos($this->open_ids, ",".$indexId.","))){
                $html .= " src=\"". BASE_URL. "/images/repository/tree/open.png\" ";
            } else {
                $html .= " src=\"". BASE_URL. "/images/repository/tree/close.png\" ";
            }
            $html .= " onClick=\"javascript: repositoryDetailClickTreeOpenClose". $id. "(event, ". $indexId. "); return false;\"".
                " style=\"margin-left:". $leftMarginP. "px; padding-top: 2px;\"".
                " unselectable=\"on\">";
            $leftMarginP = 0;
        }

        // 全チェックフラグがON または チェック済みインデックスIDと一致する場合、チェックボックスはONにする
        $chkStatus = false;
        if(isset($this->detail_all_check) || is_numeric(strpos(",".$this->detail_check_ids.",", ",".$indexId.",")))
        {
            $chkStatus = true;
        }

        $html .= "<label id=\"detail_tree_branch". $indexId. $id. "\" class=\"branch_repos\" align\"left\" style=\"margin-left:". $leftMarginP. "px; \" onClick=\"javascript: updateSelectedIndexIds". $id. "(event, ". $indexId. "); return true;\">".
                "<input id=\"detail_tree_check". $indexId. $id. "\" class=\"chk_repos\" type=\"checkbox\" unselectable=\"on\" ";
        if($chkStatus){
            $html .= "checked ";
        }
        $html .= ">";
        $html .= $indexName.
                "</label>".
                "<input id=\"detail_tree_id". $indexId. $id. "\" value=\"". $childIndexIdStr. "\" style=\"display:none; \">".
                "<input id=\"detail_tree_name". $indexId. $id. "\" value=\"". $indexName. "\" style=\"display:none; \">".
                "</div>";

    }

    /**
     * Set open_id for search_detail
     *
     * @param int  i   $index_id
     */
    private function setOpenIdsForDetailSearch($index_id)
    {
        $idx_data = $this->getIndexData($index_id);
        if(is_array($idx_data) && array_key_exists("parent_index_id", $idx_data) && $idx_data["parent_index_id"] != "0" &&
        !is_numeric(strpos(",".$this->open_ids.",", ",".$idx_data["parent_index_id"].","))){
            if($this->open_ids == ""){
                $this->open_ids = $idx_data["parent_index_id"];
            } else {
                $this->open_ids .= ",".$idx_data["parent_index_id"];
            }
            $this->setOpenIdsForDetailSearch($idx_data["parent_index_id"]);
        }
    }

    /**
     * Get public index query
     *
     */
    private function getPublicIndexQuery()
    {
        if(empty($this->publicIndexQuery))
        {
            if($this->sel_mode == "rootcheck")
            {
                // ログイン情報を退避し、未ログイン状態にしておく
                $tmp_user_id = $this->Session->getParameter("_user_id");
                $tmp_user_auth_id = $this->Session->getParameter("_user_auth_id");
                $this->Session->setParameter("_user_id", "0");
                $this->Session->setParameter("_user_auth_id", "");
            }

            $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->Db, $this->TransStartDate);
            $this->publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);

            if($this->sel_mode == "rootcheck")
            {
                // ログイン情報を元に戻す
                $this->Session->setParameter("_user_id", $tmp_user_id);
                $this->Session->setParameter("_user_auth_id", $tmp_user_auth_id);
            }
        }

        return $this->publicIndexQuery;
    }
}

?>
