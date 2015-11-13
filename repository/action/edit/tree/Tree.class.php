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
//require_once WEBAPP_DIR. '\modules\Repository\components\RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';

/**
 * repository edit index tree action
 *
 * @package  NetCommons
 * @author    S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license  http://www.netcommons.org/license.txt  NetCommons License
 * @project  NetCommons Project, supported by National Institute of Informatics
 * @access    public
 */
class Repository_Action_Edit_Tree extends RepositoryAction
{   
    // change index tree 2008/12/08 Y.Nakao --start--
    
    // component
    var $Session = null;
    var $Db = null;
    var $uploadsView = null;
    
    // request parameter
    var $edit_id = null;            // click index ID
    var $edit_mode = null;          // edit mode
                                    // '' : select edit index
                                    // 'insert' : make new index
                                    // 'update' : edit index
                                    // 'delete' : delete index
                                    // 'sort' : sort index
                                    // 'copy_tree' : copy index tree
    // request parameter for now edit index data
    var $name_jp = null;                // now edit index japanese name
    var $name_en = null;                // now edit index english name
    var $comment = null;                // now edit index comment
    var $pid = null;                    // now edit index parent_index_id
    var $show_order= null;              // now edit index show order
    var $pub_chk = null;                // now edit index pub flg
    var $pub_year = null;               // now edit index pub year
    var $pub_month = null;              // now edit index pub month
    var $pub_day = null;                // now edit index pub day 
    var $access_group_ids = null;       // now edit index entry item group id
    var $not_access_group_ids = null;   // now edit index not entry item group id
    var $access_role_ids = null;        // now edit index entry item auth id
    var $not_access_role_ids = null;    // now edit index not entry item auth id
    var $mod_date = null;               // now edit index mod date
    var $drag_id = null;                // drag index id at drag event
    var $drop_id = null;                // drop index id at drop event
    var $drop_index = null;             // true  : index drop in index
                                        // false : index drop in sentry
    // Add child index display more 2009/01/16 Y.Nakao --start--
    var $display_more = null;           // first display child index show all or a little
    var $display_more_num = null;       // first display child index num
    // Add child index display more 2009/01/16 Y.Nakao --end--
    
    var $rss_display = null;            // RSS icon display
    
    // Add config management authority 2010/02/23 Y.Nakao --start--
    var $access_role_room = null;       // now edit index access OK room authority
    // Add config management authority 2010/02/23 Y.Nakao --end--
    
    // Add contents page 2010/08/06 Y.Nakao --start--
    var $display_type = null;
    // Add contents page 2010/08/06 Y.Nakao --end--
    
    // Add index list 2011/4/5 S.Abe --start--
    var $select_index_list_display = null;
    var $select_index_list_name = null;
    var $select_index_list_name_english = null;
    // Add index list 2011/4/5 S.Abe --end--

    var $smartyAssign = null;

    // Add index thumbnail 2010/08/20 Y.Nakao --start--
    public $thumbnail_del = null;
    // Add index thumbnail 2010/08/20 Y.Nakao --end--
    
    // Add tree access control list 2012/02/22 T.Koyasu -start-
    public $exclusiveAclRoleIds = null;
    public $exclusiveAclRoomAuth = null;
    public $exclusiveAclGroupIds = null;
    // Add tree access control list 2012/02/22 T.Koyasu -end-
    
    // Add tree access control list 2011/12/28 Y.Nakao --start--
    /**
     * default access role ids
     *
     * @var string
     */
    private $defaultAccessRoleIds_ = '';
    /**
     * default access role room
     *
     * @var int
     */
    private $defaultAccessRoleRoom_ = '';
    /**
     * default access group
     *
     * @var string
     */
    private $defaultAccessGroups_ = '';
    /**
     * default exclusive access control list for role_authority_id.
     *
     * @var string
     */
    private $defaultExclusiveAclRoleIds_ = '';
    /**
     * default exclusive access control list for room authority.
     * 
     * @var unknown_type
     */
    private $defaultExclusiveAclRoleRoom_ = '';
    /**
     * default exclusive access control list for group.
     *
     * @var string
     */
    private $defaultExclusiveAclGroups_ = '';
    // Add tree access control_ list 2011/12/28 Y.Nakao --end--
    
    public $create_cover_flag = null;
    
    // Add harvest public flag 2013/07/05 K.Matsuo --start--
    public $harvest_public_state = null;
    // Add harvest public flag 2013/07/05 K.Matsuo --end--
    // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
    public $biblio_flag = null;
    public $online_issn = null;
    // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
    
    // Add recursively uppdate flag 2015/01/21 S.Suzuki --start--
    public $pubdate_recursive = null;
    public $create_cover_recursive = null;
    public $aclRoleIds_recursive = null;
    public $aclRoomAuth_recursive = null;
    public $aclGroupIds_recursive = null;
    // Add recursively uppdate flag 2015/01/21 S.Suzuki --end--
    
    // Add change view role flag 2015/03/10 T.Ichikawa --start--
    public $changeBrowsingAuthorityFlag = false;
    // Add change view role flag 2015/03/10 T.Ichikawa --end--
    
    function execute()
    {
        try {
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
            
            // Add rollback bug of #292 2012/01/12 T.Koyasu -start-
            if(is_numeric($this->edit_id))
            {
                $this->edit_id = intval($this->edit_id);
            }
            else
            {
                $this->edit_id = 0;
            }
            // Add rollback bug of #292 2012/01/12 T.Koyasu -end-
            
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $repositoryDownload = new RepositoryDownload();
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
            // Add RepositoryIndexManager 2013/11/29 R.Matsuura --start--
            $indexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
            // Add RepositoryIndexManager 2013/11/29 R.Matsuura --end--
            
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");
            $this->Session->removeParameter("tree_error_msg");
            
            // Add insert new private tree tree 2013/04/23 K.Matsuo --start--
            // Add tree access control list 2011/12/28 Y.Nakao --start--
            if($this->edit_id != 0){
                $owner_user_id = $this->getIndexOwnerUserId($this->edit_id);
                if(strlen($owner_user_id) > 0){
                    $this->setPrivateTreeDefaultAccessControlList();
                } else {
                    $this->setDefaultAccessControlList();
                }
            } else {
                $this->setDefaultAccessControlList();
            }
            // Add tree access control list 2011/12/28 Y.Nakao --end--
            // Add insert new private tree tree 2013/04/23 K.Matsuo --end--
            /////////////////////////////
            // check edit mode
            /////////////////////////////
            if($this->edit_mode == "insert"){
                //////////////////////////////////
                // set new index data
                //////////////////////////////////
                /* modify 2011/10/07 T.Koyasu -start- */
                $params = array();
                /* modify 2011/10/07 T.Koyasu -end- */
                $new_index_data = array();
                $new_id = $this->getNewIndexId();
                /* modify 2011/10/07 T.Koyasu -start- */
                // set new index to the head of index tree
                $show_order = 1;
                /* modify 2011/10/07 T.Koyasu -end- */
                // set focus
                $this->Session->setParameter("edit_index", $new_id);
                
                // get now date
                $DATE = new Date();
                $now_date = explode(" ", $DATE->getDate(), 2);
                
                // get defalt access group data
                $group_data = array();
                $this->getAccessGroupData($this->defaultAccessRoleRoom_, $this->defaultExclusiveAclGroups_, $group_data);
                // get defalt access auth data
                // 権限による投稿制限は"一般"以上(role_authority_id 1~4) default is auth 1~4
                $auth_data = array();
                // 設定するのは一般以上(role_authority_idでは1が管理者で5がゲスト) set role auth id
                $this->getAccessAuthData($this->defaultAccessRoleIds_, $this->defaultExclusiveAclRoleIds_, $auth_data);
                // make new index data
                $new_index_data["index_id"] = $new_id;
                $new_index_data["index_name"] = "New Node"; 
                $new_index_data["index_name_english"] = "New Node For English";
                $new_index_data["parent_index_id"] = $this->edit_id;
                $new_index_data["show_order"] = $show_order;
                $new_index_data["public_state"] = "false";
                // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --start--
                // プライベートツリーを利用するか
                $isMakePrivateTree = "";
                $errorMsg = "";
                $this->getAdminParam("is_make_privatetree", $isMakePrivateTree, $errorMsg);
                // Fix harvest_public_state 2014/03/15 Y.Nakao --start--
                $new_index_data["harvest_public_state"] = 1;
                // 対象のツリーがプライベートツリーであるか
                $parent_index_owner = $this->getIndexOwnerUserId($this->edit_id);
                if($isMakePrivateTree=="1" && strlen($parent_index_owner) > 0)
                {
                    if(_REPOSITORY_PRIVATETREE_PUBLIC == true)
                    {
                        $new_index_data["public_state"] = "true";
                    }
                    else
                    {
                        $new_index_data["harvest_public_state"] = 0;
                    }
                }
                // Fix harvest_public_state 2014/03/15 Y.Nakao --end--
                // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --end--
                $new_index_data["pub_year"] = $DATE->getYear();
                $new_index_data["pub_month"] = sprintf("%02d",$DATE->getMonth());
                $new_index_data["pub_day"] = sprintf("%02d",$DATE->getDay());
                $new_index_data["pub_date"] = $now_date[0].' 00:00:00.000';
                $new_index_data["access_group"] = $group_data["access_group_id"];
                $new_index_data["access_role_id"] = $auth_data["access_role_id"];
                $new_index_data["comment"] = "";
                $new_index_data["display_more"] = ""; // Add child index display more 2009/01/16 Y.Nakao
                $new_index_data["rss_display"] = 0;    // Add RSS icon display 2009/07/06 A.Suzuki
                // Add config management authority 2010/02/23 Y.Nakao --start--
                $new_index_data["access_role_room"] = $this->defaultAccessRoleRoom_;
                // Add config management authority 2010/02/23 Y.Nakao --end--
                // Add OpenDepo 2013/11/29 R.Matsuura --start--
                $new_index_data["access_role"] = $new_index_data["access_role_id"]."|".$new_index_data["access_role_room"];
                // Add OpenDepo 2013/11/29 R.Matsuura --end--
                $new_index_data["display_type"] = "";   // Add contents page Y.Nakao
                // Add index list 2011/4/6 S.Abe --start--
                $new_index_data["select_index_list_display"] = "";
                $new_index_data["select_index_list_name"] = "";
                $new_index_data["select_index_list_name_english"] = "";
                // Add index list 2011/4/6 S.Abe --end--
                // Add tree access control list 2012/02/22 T.Koyasu -start-
                $new_index_data["exclusive_acl_role_id"] = $this->defaultExclusiveAclRoleIds_;
                $new_index_data["exclusive_acl_room_auth"] = $this->defaultExclusiveAclRoleRoom_;
                $new_index_data["exclusive_acl_group_id"] = $this->defaultExclusiveAclGroups_;
                // Add tree access control list 2012/02/22 T.Koyasu -end-
                $new_index_data["repository_id"] = 0;
                $new_index_data["set_spec"] = "";
                $new_index_data["create_cover_flag"] = 0;
                // Add PrivateTree owner 2013/04/10 K.Matsuo --start--
                $owner_user_id = "";
                if($this->edit_id != 0){
                    // 登録先のowner_user_idを設定する
                    $owner_user_id = $this->getIndexOwnerUserId($this->edit_id);
                }
                $new_index_data["owner_user_id"] = $owner_user_id;
                // Add PrivateTree owner 2013/04/10 K.Matsuo --end--
                // Fix harvest_public_state 2014/03/15 Y.Nakao
                // insert new index
                $errMsg = '';
                $result = $indexManager->addIndex(true, $new_index_data, $errMsg);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                } else if($result === "noEnglishName"){
                    $this->failTrans(); // ROLLBACK
                    $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
                    return 'error';
                // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
                } else if($result === "wrongFormatIssn"){
                    $this->failTrans(); // ROLLBACK
                    $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
                    return 'error';
                }
                // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
                // end action
                $result = $this->exitAction();  // COMMIT
                if ( $result == false ){
                    print "failed in end action";
                }
                // set session for open node
                if($this->edit_id != "0"){
                    $open_ids = $this->Session->getParameter("view_open_node_index_id_edit");
                    if( !(is_numeric(strpos(",".$open_ids.",", ",".$this->edit_id.","))) ){
                        if($open_ids != ""){
                            $open_ids .= ",".$this->edit_id;
                        } else {
                            $open_ids = $this->edit_id;
                        }
                    }
                    $this->Session->setParameter("view_open_node_index_id_edit", $open_ids);
                    $open_ids = $this->Session->getParameter("view_open_node_index_id_editPrivatetree");
                    if( !(is_numeric(strpos(",".$open_ids.",", ",".$this->edit_id.","))) ){
                        if($open_ids != ""){
                            $open_ids .= ",".$this->edit_id;
                        } else {
                            $open_ids = $this->edit_id;
                        }
                    }
                    $this->Session->setParameter("view_open_node_index_id_editPrivatetree", $open_ids);
                }
                // set session edit index id
                $this->Session->setParameter("edit_index", $new_id);
                
                // Add new prefix 2013/12/25 T.Ichikawa --start--
                $new_index_data["mod_date"] = $this->TransStartDate;
                $new_index_data["access_group_name"] = $group_data["access_group_name"];
                $new_index_data["not_access_group_id"] = $group_data["not_access_group_id"];
                $new_index_data["not_access_group_name"] = $group_data["not_access_group_name"];
                $new_index_data["access_role_name"] = $auth_data["access_role_name"];
                $new_index_data["not_access_role_id"] = $auth_data["not_access_role_id"];
                $new_index_data["not_access_role_name"] = $auth_data["not_access_role_name"];
                $new_index_data["room_auth_moderate"] = "false";
                $new_index_data["room_auth_general"] = "false";
                $new_index_data["acl_role_id"] = $auth_data["acl_role_id"];
                $new_index_data["acl_role_name"] = $auth_data["acl_role_name"];
                $new_index_data["exclusive_acl_role_id"] = $auth_data["exclusive_acl_role_id"];
                $new_index_data["exclusive_acl_role_name"] = $auth_data["exclusive_acl_role_name"];
                $new_index_data["acl_group_id"] = $group_data["acl_group_id"];
                $new_index_data["acl_group_name"] = $group_data["acl_group_name"];
                $new_index_data["exclusive_acl_group_id"] = $group_data["exclusive_acl_group_id"];
                $new_index_data["exclusive_acl_group_name"] = $group_data["exclusive_acl_group_name"];
                $new_index_data["acl_room_auth_moderate"] = "true";
                $new_index_data["acl_room_auth_general"] = "true";
                $new_index_data["acl_room_auth_guest"] = "true";
                $new_index_data["acl_room_auth_logout"] = "true";
                $new_index_data["acl_user_auth_id"] = $auth_data["acl_user_auth_id"];
                $new_index_data["exclusive_acl_user_auth_id"] = $auth_data["exclusive_acl_user_auth_id"];
                
                $this->sendIndexParameterToHtml($new_index_data);
                // Add new prefix 2013/12/25 T.Ichikawa --end--
                exit();
            } else if($this->edit_mode == "delete"){
                //////////////////////////////////
                // check delete index data
                //////////////////////////////////
                // check this index has node or item
                // send data
                $del_flg = "false";
                // check has index from DB
                if( !($this->hasChild($this->edit_id)) ){
                    // check has item
                    if( !($this->hasItem($this->edit_id)) ){
                        // delete OK from DB
                        $del_flg = "true";
                        // delete index
                        $result = $indexManager->deleteIndex( $this->edit_id );
                        if($result === false){
                            $errMsg = $this->Db->ErrorMsg();
                            $this->failTrans(); // ROLLBACK
                            return 'error';
                        }
                        // end action
                        $result = $this->exitAction();  // COMMIT
                        if ( $result == false ){
                            print "failed in end action";
                        }
                    }
                }
                $del_json = '';
                $del_json .= '{';
                $del_json .= '"del_flg":"'.$del_flg.'",';
                $del_json .= '"id":"'.$this->edit_id.'",';
                $del_json .= '"pid":"'.$this->pid.'",';
                if($del_flg == "false"){
                    $del_json .= '"mod_date":"'.$this->mod_date.'",';
                }
                // Bugfix input scrutiny Y.Nakao --start--
                $index_name = "";
                if($this->Session->getParameter("_lang") == "japanese"){
                    $index_name = $this->name_jp;
                } else {
                    $index_name = $this->name_en;
                }
                $del_json .= '"name":"'.$this->escapeJSON($index_name).'"';
                // Bugfix input scrutiny Y.Nakao --end--
                $del_json .= '}';
                // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
                $repositoryDownload->download($del_json, "del_conf.txt");
                //$this->uploadsView->download($del_json, "del_conf.txt");
                // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
                exit();
            } else if($this->edit_mode == "delete_all"){
                //////////////////////////////////
                // delete all
                //////////////////////////////////
                // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --start-- 
                $result = $this->subIndexContents($this->pid, $this->edit_id);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --end--
                // delete all index data
                $child_idx_ids = array();
                $result = $this->getAllChildIndexID($this->edit_id, $child_idx_ids);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return false;
                }
                array_push($child_idx_ids, $this->edit_id);
                $result = $this->deleteIndexItem( $child_idx_ids );
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                }
                $this->Session->setParameter("repository_edit_update", "delete");
            } else if($this->edit_mode == "delete_move"){
                /////////////////////////////////////
                // move item and check index delete
                /////////////////////////////////////
                // bottom's item and index move to parent index's bottom
                // check index id
                $result = $this->moveItemIndexToParentIndex($this->edit_id, $this->pid);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                }
                // delete this index
                $result = $indexManager->deleteIndex($this->edit_id);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                }
                $this->Session->setParameter("repository_edit_update", "move");
            } else if($this->edit_mode == "sort"){
                //////////////////////////////////
                // sort index
                //////////////////////////////////
                // sort action
                if($this->drop_index == "true"){
                    // drop in index
                    // $this->drop_idを親インデックスとして、末尾に挿入
                    // change parent index
                    $result = $this->changeParentIndex($this->drag_id, $this->drop_id, 'last');
                } else if($this->drop_index == "false"){
                    // drop in sentry
                    // $this->drop_idの次に挿入。親インデックスは$this->drop_idとおなじ。
                    // get drop index's parent index id
                    $drop_pid = $this->getParentIndexId($this->drop_id);
                    // change parent index
                    $result = $this->changeParentIndex($this->drag_id, $drop_pid, $this->drop_id);
                } else if($this->drop_index == "first"){
                    // drop in sentry for parentindex 
                    // $this->drop_idを親インデックスとして、先頭に挿入
                    // change parent index
                    $result = $this->changeParentIndex($this->drag_id, $this->drop_id, 'first');
                }
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                }
            } else if($this->edit_mode == "update"){
                // Add count contents for unpublic index 2009/02/16 A.Suzuki --start--
                // update前の公開状況と親インデックスのidを取得       // owner_user_idを追加 K.Matsuo 2013/04/12
                $query = "SELECT public_state, parent_index_id, owner_user_id ".
                         "FROM ".DATABASE_PREFIX."repository_index ".
                         "WHERE index_id = ? ".
                         "AND is_delete = 0;";
                $params = array();
                $params[] = $this->edit_id;
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                
                $parent_id = $result[0]['parent_index_id'];
                
                // 上位インデックスの公開状況を調べる
                if($this->checkParentPublicState($this->edit_id)){  // 上位はすべて公開中
                    if($result[0]['public_state'] == "1"){
                        // 自身も公開中
                        $old_state = "public_all";
                    } else {
                        // 自身は非公開
                        $old_state = "unpublic";
                    }
                } else {    // 上位に非公開あり
                    $old_state = "unpublic_parent";
                }
                // Add count contents for unpublic index 2009/02/16 A.Suzuki --end--
                
                //////////////////////////////////
                // update index data for DB
                //////////////////////////////////
                
                // get edit index data
                // Add new prefix 2013/12/25 T.Ichikawa --start--
                $edit_index_data = array();
                // Add new prefix 2013/12/25 T.Ichikawa --end--
                $edit_index_data["index_id"] = $this->edit_id;
                $edit_index_data["index_name"] = $this->name_jp;
                $edit_index_data["index_name_english"] = $this->name_en;
                $edit_index_data["parent_index_id"] = $this->pid;
                $edit_index_data["show_order"] = $this->show_order;
                $edit_index_data["mod_date"] = $this->mod_date;
                // mod recursive update child index 2015/02/10 S.Arata --start--
                if ($this->pub_chk == "true") {
                    $edit_index_data["public_state"] = "1";
                } else {
                    $edit_index_data["public_state"] = "0";
                }
                // mod recursive update child index 2015/02/10 S.Arata  --end--
                // Bugfix input scrutiny heck pubdate 2011/06/17 Y.Nakao --start--
                $edit_index_data["pub_year"] = $this->pub_year;
                $edit_index_data["pub_month"] = $this->pub_month;
                $edit_index_data["pub_day"] = $this->pub_day;
                $edit_index_data["pub_date"] = sprintf("%d-%02d-%02d 00:00:00.000", $this->pub_year, $this->pub_month, $this->pub_day);
                if(!$this->checkDate($this->pub_year, $this->pub_month, $this->pub_day)){
                    $DATE = new Date();
                    $now_date = explode(" ", $DATE->getDate(), 2);
                    $edit_index_data["pub_year"] = $DATE->getYear();
                    $edit_index_data["pub_month"] = sprintf("%02d",$DATE->getMonth());
                    $edit_index_data["pub_day"] = sprintf("%02d",$DATE->getDay());
                    $edit_index_data["pub_date"] = $now_date[0].' 00:00:00.000';
                }
                // Bugfix input scrutiny heck pubdate 2011/06/17 Y.Nakao --end--
                $edit_index_data["comment"] = $this->comment;
                // Add child index display more 2009/01/19 Y.Nakao --start--
                if($this->display_more == "true"){
                    $edit_index_data["display_more"] = intval($this->display_more_num);
                } else {
                    $edit_index_data["display_more"] = "";
                }
                // Add child index display more 2009/01/19 Y.Nakao --end--
                // Add RSS icon display 2009/07/06 A.Suzuki --start--
                if($this->rss_display == "true"){
                    $edit_index_data["rss_display"] = "1";
                } else {
                    $edit_index_data["rss_display"] = "0";
                }
                // Add RSS icon display 2009/07/06 A.Suzuki --end--
                // Add contents page 2010/07/02 Y.Nakao --start--
                $edit_index_data["display_type"] = intval($this->display_type);
                // Add contents page 2010/07/02 Y.Nakao --end--
                // Add index list 2011/4/6 S.Abe --start--
                $edit_index_data["select_index_list_display"] = intval($this->select_index_list_display);
                $edit_index_data["select_index_list_name"] = $this->checkBlank($this->select_index_list_name);
                $edit_index_data["select_index_list_name_english"] = $this->checkBlank($this->select_index_list_name_english);
                // Add index list 2011/4/6 S.Abe --end--
                $edit_index_data["access_group_id"] = $this->access_group_ids;
                $edit_index_data["not_access_group_id"] = $this->not_access_group_ids;
                $edit_index_data["access_role_id"] = $this->access_role_ids;
                $edit_index_data["not_access_role_id"] = $this->not_access_role_ids;
                $edit_index_data["access_role_room"] = $this->access_role_room;
                // Add OpenDepo 2013/11/29 R.Matsuura --start--
                $edit_index_data["access_role"] = $edit_index_data["access_role_id"] . "|" . $edit_index_data["access_role_room"];
                // Add tree thumbnail 2010/08/20 Y.Nakao --start--
                $edit_index_data["thumbnail_del"] = $this->thumbnail_del;
                // Add tree thumbnail 2010/08/20 Y.Nakao --end--
                // Add tree access control list 2012/02/22 T.Koyasu -start-
                // set exclusive_acl_role in repository_index
                $edit_index_data["exclusive_acl_role_id"] = $this->exclusiveAclRoleIds;
                $edit_index_data["exclusive_acl_room_auth"] = $this->exclusiveAclRoomAuth;
                // set exclusive_acl_group in repository_index
                $edit_index_data["exclusive_acl_group_id"] = $this->exclusiveAclGroupIds;
                // Add tree access control list 2012/02/22 T.Koyasu -end-
                // Add change view role flag 2015/03/10 T.Ichikawa --start--
                // 閲覧権限に変更があった場合フラグをtrueにする
                $this->changeBrowsingAuthorityFlag = $this->checkChangeBrowsingAuthority($edit_index_data["index_id"], 
                                                                                         $edit_index_data["exclusive_acl_role_id"], 
                                                                                         $edit_index_data["exclusive_acl_room_auth"], 
                                                                                         $edit_index_data["exclusive_acl_group_id"]);
                // Add change view role flag 2015/03/10 T.Ichikawa --end--
                // Add RSS icon display 2009/07/06 A.Suzuki --start--
                if($this->create_cover_flag == "true"){
                    $edit_index_data["create_cover_flag"] = "1";
                } else {
                    $edit_index_data["create_cover_flag"] = "0";
                }
                // Add RSS icon display 2009/07/06 A.Suzuki --end--
                
                $edit_index_data["owner_user_id"] = $result[0]['owner_user_id'];    // Add for owner_user_id not change K.Matsuo 2013/04/12
                // Add harvest public flag 2013/07/04 K.Matsuo --start--
                if($this->harvest_public_state == "true"){
                    $edit_index_data["harvest_public_state"] = "1";
                } else {
                    $edit_index_data["harvest_public_state"] = "0";
                }
                // Add harvest public flag 2013/07/04 K.Matsuo --end--
                // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
                $edit_index_data["biblio_flag"] = $this->biblio_flag;
                $edit_index_data["online_issn"] = $this->online_issn;
                // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
                // update edit data
                $result = $indexManager->updateIndex($edit_index_data);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans(); // ROLLBACK
                    return 'error';
                } else if($result === "noEnglishName"){
                    $this->failTrans(); // ROLLBACK
                    $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
                    return 'error';
                // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
                } else if($result === "wrongFormatIssn"){
                    $this->failTrans(); // ROLLBACK
                    $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
                    return 'error';
                }
                
                // Add recursively uppdate flag 2015/01/21 S.Suzuki --start--
                if ($this->pubdate_recursive == "true") {
                    $pubdate_recursive = true;
                } else {
                    $pubdate_recursive = false;
                }
                if ($this->create_cover_recursive == "true") {
                    $create_cover_recursive = true;
                } else {
                    $create_cover_recursive = false;
                }
                if ($this->aclRoleIds_recursive == "true") {
                    $aclRoleIds_recursive = true;
                } else {
                    $aclRoleIds_recursive = false;
                }
                if ($this->aclRoomAuth_recursive == "true") {
                    $aclRoomAuth_recursive = true;
                } else {
                    $aclRoomAuth_recursive = false;
                }
                if ($this->aclGroupIds_recursive == "true") {
                    $aclGroupIds_recursive = true;
                } else {
                    $aclGroupIds_recursive = false;
                }
                
                if ($pubdate_recursive || $create_cover_recursive || $aclRoleIds_recursive || 
                    $aclRoomAuth_recursive || $aclGroupIds_recursive){
                        
                        $indexManager->recursiveUpdate($edit_index_data, 
                                                       $pubdate_recursive, 
                                                       $create_cover_recursive, 
                                                       $aclRoleIds_recursive, 
                                                       $aclRoomAuth_recursive, 
                                                       $aclGroupIds_recursive);
                }
                // Add recursively uppdate flag 2015/01/21 S.Suzuki --end--
                
                // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
                // Add count contents for unpublic index 2009/02/13 A.Suzuki --start--
                // 上位インデックスが公開であるか閲覧権限が変更された時のみ再集計を行う
                if($old_state != "unpublic_parent"){
                    if($old_state == "public_all"){
                        if($this->pub_chk == "false"){
                            // 公開 -> 非公開
                            // 親インデックスのコンテンツ数から自身のコンテンツ数を引く
                            $result = $this->subIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 自身以下のコンテンツ数をリセットする
                            $result = $this->resetContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // このインデックス以下の非公開コンテンツ数を再計算
                            $result = $this->recountPrivateContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 上位インデックスに非公開コンテンツ数追加 add index private_contents num for after move index parent index
                            $result = $this->addIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // Add private_contents count K.Matsuo 2013/05/07 --end--
                        } else {
                            // 公開 -> 公開
                            // Add private_contents count K.Matsuo 2013/05/07 --start--
                            // 親インデックスの非公開コンテンツ数から自身の非公開コンテンツ数を引く
                            $result = $this->subIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 自身以下のコンテンツ数を再取得する
                            $result = $this->recountContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // このインデックス以下の非公開コンテンツ数を再計算
                            $result = $this->recountPrivateContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 親インデックスのコンテンツ数に自身のコンテンツ数を足す
                            $result = $this->addIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // Add private_contents count K.Matsuo 2013/05/07 --end--
                        }
                    } else if($old_state == "unpublic"){
                        if($this->pub_chk != "false"){
                            // 非公開 -> 公開
                            // Add private_contents count K.Matsuo 2013/05/07 --start--
                            // 親インデックスの非公開コンテンツ数から自身の非公開コンテンツ数を引く
                            $result = $this->subIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 自身以下のコンテンツ数を再取得する
                            $result = $this->recountContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // このインデックス以下の非公開コンテンツ数を再計算
                            $result = $this->recountPrivateContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 親インデックスのコンテンツ数に自身のコンテンツ数を足す
                            $result = $this->addIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // Add private_contents count K.Matsuo 2013/05/07 --end--
                        } else {
                            // 非公開 -> 非公開
                            // 親インデックスのコンテンツ数から自身のコンテンツ数を引く
                            $result = $this->subIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 自身以下のコンテンツ数をリセットする
                            $result = $this->resetContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // このインデックス以下の非公開コンテンツ数を再計算
                            $result = $this->recountPrivateContents($this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // 上位インデックスに非公開コンテンツ数追加 add index private_contents num for after move index parent index
                            $result = $this->addIndexContents($parent_id, $this->edit_id);
                            if($result === false){
                                $error = $this->Db->ErrorMsg();
                                return false; 
                            }
                            // Add private_contents count K.Matsuo 2013/05/07 --end--
                        }
                    }
                }
                // Add deleteWhatsnew 2009/02/09 A.Suzuki --start--
                if($this->pub_chk == "false"){
                    $this->deleteWhatsnewForIndex($this->edit_id);
                }
                // Add deleteWhatsnew 2009/02/09 A.Suzuki --end--
                // Add change view role flag 2015/03/10 T.Ichikawa --start--
                if($this->changeBrowsingAuthorityFlag) {
                    $result = $this->subIndexContents($parent_id, $this->edit_id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // 自身以下のコンテンツ数を再取得する
                    $result = $this->recountContents($this->edit_id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // このインデックス以下の非公開コンテンツ数を再計算
                    $result = $this->recountPrivateContents($this->edit_id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // 親インデックスのコンテンツ数に自身のコンテンツ数を足す
                    $result = $this->addIndexContents($parent_id, $this->edit_id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                }
                // Add change view role flag 2015/03/10 T.Ichikawa --end--
                // Add count contents for unpublic index 2009/02/13 A.Suzuki --end--
                // $this->Session->setParameter("repository_edit_update", "update");
                $this->Session->setParameter("redirect_flg", "tree_update");
            } else if($this->edit_mode == "copy_tree"){
                $indexManager->copyIndexTree($this->drag_id, $this->drop_id);
            } else { 
                //////////////////////////////////
                // get edit index data
                //////////////////////////////////
                // set session edit index id
                $this->Session->setParameter("edit_index", $this->edit_id);
                // return edit index infomation
                $index_data = array();
                if($this->edit_id == "0"){
                    return "";
                }
                $index_data = $this->getIndexEditData($this->edit_id);                
                // Add new Prefix 2013/12/24 T.Ichikawa --start--
                $this->sendIndexParameterToHtml($index_data);
                // Add new Prefix 2013/12/24 T.Ichikawa --end--
                exit();
            }
            // end action
            $result = $this->exitAction();  // COMMIT
            if ( $result == false ){
                print "failed in end action";
            }
            // not remove edit data
            $this->Session->setParameter("edit_tree_continue", "continue");
                
            if($this->edit_mode == "update"){
                return 'redirect';
            } else {
                // call view action
                return 'success';
            }
        }
        catch ( RepositoryException $Exception) {
            // error output
            $this->logFile(
                "SampleAction",                 //class
                "execute",                      //method
            $Exception->getCode(),          //LogID
            $Exception->getMessage(),       //message
            $Exception->getDetailMsg() );   //detail message
             
            //end action
            $this->exitAction();    //ROLLBACK

            //end error
            return "error";
        }
    }
    
    /**
     * 新規index_idを取得する
     * get new index id
     *
     * @return new index id
     */
    function getNewIndexId(){
        // get new index id
        $query = "SELECT MAX(index_id) FROM ". DATABASE_PREFIX ."repository_index; ";
        $ret_idx = $this->Db->execute($query);
        if($ret_idx === false){
            return "";
        }
        $new_id = $ret_idx[0]["MAX(index_id)"];
        $new_id++;
        
        return $new_id;
    }
    
    /**
     * $pid直下にあるindexの最大show_orderを取得する
     * get $pid bottom index max show order
     *
     * @param $pid
     */ 
    function getShowOrder($pid){
        // get new parent index bottom's max show_order
        $query = "SELECT COUNT(show_order) FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE `is_delete` = 0 AND ".
                 "parent_index_id = ". $pid ."; ";
        $ret_show = $this->Db->execute($query);
        if($ret_show === false || $ret_show[0]["COUNT(show_order)"]<=0){
            $show_order = 0;
        } else {
            $show_order = $ret_show[0]["COUNT(show_order)"];                
        }
        return $show_order;
    }
    
    // Add tree access control list 2011/12/28 Y.Nakao --start--
    /**
     * NetCommonsに登録されている全グループを取得し、
     * 投稿権限のあるグループID、グループ名、投稿権限のないグループID、グループ名のリストを取得する。
     * 
     * get edit index access group list
     *
     * @param $access_group_id access OK group room ids
     * @param $exclusive_acl_group acl NG group room ids
     * @param $edit_index add result in this parameter
     * @return true or false
     */
    function getAccessGroupData($access_group_id, $exclusive_acl_group_id, &$edit_index)
    {
        // get access group or not
        $result = $this->getGroupList($all_group, $error);
        if($result === false){
            return false;;
        }
        // add get (not member)
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        $add_array = array("page_id"=>'0', "page_name"=>$smartyAssign->getLang("repository_item_gest"));
        array_unshift($all_group, $add_array);
        
        // 投稿権限
        $edit_index["access_group_id"] = '';
        $edit_index["access_group_name"] = '';
        $edit_index["not_access_group_id"] = '';
        $edit_index["not_access_group_name"] = '';
        
        // 閲覧権限
        $edit_index["acl_group_id"] = '';
        $edit_index["acl_group_name"] = '';
        $edit_index["exclusive_acl_group_id"] = '';
        $edit_index["exclusive_acl_group_name"] = '';
        
        for($ii=0; $ii<count($all_group); $ii++)
        {
            if(is_numeric(strpos(",".$access_group_id.",", ",".$all_group[$ii]["page_id"].",")))
            {
                if($edit_index["access_group_id"] != "")
                {
                    $edit_index["access_group_id"] .= ",";
                    $edit_index["access_group_name"] .= ",";
                }
                $edit_index["access_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["access_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            else
            {
                if($edit_index["not_access_group_id"] != "")
                {
                    $edit_index["not_access_group_id"] .= ",";
                    $edit_index["not_access_group_name"] .= ",";
                }
                $edit_index["not_access_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["not_access_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            
            if(is_numeric(strpos(",".$exclusive_acl_group_id.",", ",".$all_group[$ii]["page_id"].",")))
            {
                if(strlen($edit_index["exclusive_acl_group_id"]) > 0)
                {
                    $edit_index["exclusive_acl_group_id"] .= ",";
                    $edit_index["exclusive_acl_group_name"] .= ",";
                }
                $edit_index["exclusive_acl_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["exclusive_acl_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            else
            {
                if(strlen($edit_index["acl_group_id"]) > 0)
                {
                    $edit_index["acl_group_id"] .= ",";
                    $edit_index["acl_group_name"] .= ",";
                }
                $edit_index["acl_group_id"] .= $all_group[$ii]["page_id"];
                $edit_index["acl_group_name"] .= '"'.$all_group[$ii]["page_name"].'"';
            }
            
        }
        if($edit_index["access_group_name"] == '')
        {
            $edit_index["access_group_name"] = '""';
        }
        if($edit_index["not_access_group_name"] == '')
        {
            $edit_index["not_access_group_name"] = '""';
        }
        
        if(strlen($edit_index["acl_group_name"]) == 0)
        {
            $edit_index["acl_group_name"] = '""';
        }
        if(strlen($edit_index["exclusive_acl_group_name"]) == 0)
        {
            $edit_index["exclusive_acl_group_name"] = '""';
        }
        
        return true;
    }
    
    /**
     * NetCommonsに登録されている全権限を取得し、
     * 投稿権限のある権限ID、権限名、投稿権限のない権限ID、権限名のリストを取得する。
     * 
     * get edit index access auth list
     *
     * @param $access_role_id access OK group room ids
     * @param $edit_index add result in this parameter
     * @return true or false
     */
    function getAccessAuthData($access_role, $exclusive_acl_role, &$edit_index){
        // Add config management authority 2010/02/23 Y.Nakao --start--
        // separate access role base authority and room authority
        $access_auth = explode("|", $access_role);
        $access_role_id = $this->defaultAccessRoleIds_;
        $access_role_room = $this->defaultAccessRoleRoom_;
        if(count($access_auth) == 2){
            $access_role_id = $access_auth[0];
            $access_role_room = $access_auth[1];
        } else if(count($access_auth) == 1){
            $access_role_id = $access_auth[0];
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--
        
        $aclAuthorities = explode("|", $exclusive_acl_role);
        $exclusiveAclRoleId = $this->defaultAccessRoleIds_;
        $exclusiveAclRoleRoom = $this->defaultAccessRoleRoom_;
        if(count($aclAuthorities) == 2)
        {
            // max user_authority_id
            $exclusiveAclRoleId = $aclAuthorities[0];
            $exclusiveAclRoleRoom = $aclAuthorities[1];
        }
        else if(count($aclAuthorities) == 1)
        {
            $exclusiveAclRoleId = $aclAuthorities[0];
        }
        
        // get all access auth
        $query = "SELECT * FROM ". DATABASE_PREFIX ."authorities;";
        $all_auth = $this->Db->execute($query);
        if($all_auth === false){
            return false;
        }
        // add get (not member)
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // 投稿権限
        $edit_index["access_role_id"] = '';
        $edit_index["access_role_name"] = '';
        $edit_index["not_access_role_id"] = '';
        $edit_index["not_access_role_name"] = '';
        
        // 閲覧権限
        $edit_index["acl_role_id"] = '';
        $edit_index["acl_role_name"] = '';
        $edit_index["exclusive_acl_role_id"] = '';
        $edit_index["exclusive_acl_role_name"] = '';
        // Add tree access control list 2012/03/02 T.Koyasu -start-
        $edit_index["acl_user_auth_id"] = '';
        $edit_index["exclusive_acl_user_auth_id"] = '';
        // Add tree access control list 2012/03/02 T.Koyasu -end-
        
        for($ii=0; $ii<count($all_auth); $ii++){
            if(is_numeric(strpos(",".$access_role_id.",", ",".$all_auth[$ii]["role_authority_id"].","))){
                if($edit_index["access_role_id"] != ""){
                    $edit_index["access_role_id"] .= ",";
                    $edit_index["access_role_name"] .= ",";
                }
                $edit_index["access_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["access_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
            } else {
                if($edit_index["not_access_role_id"] != ""){
                    $edit_index["not_access_role_id"] .= ",";
                    $edit_index["not_access_role_name"] .= ",";
                }
                $edit_index["not_access_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["not_access_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
            }
            
            // Add tree access control list 2012/03/02 T.Koyasu -start-
            if($exclusiveAclRoleId >= intval($all_auth[$ii]["user_authority_id"]))
            {
                // Mod access_role_id -> exclusive_acl_role_id
                if($edit_index["exclusive_acl_role_id"] != "")
                {
                    $edit_index["exclusive_acl_role_id"] .= ",";
                    $edit_index["exclusive_acl_role_name"] .= ",";
                    // add user_authority_id for gimic by Koyasu
                    $edit_index["exclusive_acl_user_auth_id"] .= ",";
                }
                $edit_index["exclusive_acl_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["exclusive_acl_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
                // add user_authority_id for gimic by Koyasu
                $edit_index["exclusive_acl_user_auth_id"] .= $all_auth[$ii]["user_authority_id"];
            }
            else
            {
                if($edit_index["acl_role_id"] != "")
                {
                    $edit_index["acl_role_id"] .= ",";
                    $edit_index["acl_role_name"] .= ",";
                    // add user_authority_id for gimic by Koyasu
                    $edit_index["acl_user_auth_id"] .= ",";
                }
                $edit_index["acl_role_id"] .= $all_auth[$ii]["role_authority_id"];
                $edit_index["acl_role_name"] .= '"'.$all_auth[$ii]["role_authority_name"].'"';
                // add user_authority_id for gimic by Koyasu
                $edit_index["acl_user_auth_id"] .= $all_auth[$ii]["user_authority_id"];
            }
            // Add tree access control list 2012/03/02 T.Koyasu -end-
            
        }
        if($edit_index["access_role_name"] == ''){
            $edit_index["access_role_name"] = '""';
        }
        if($edit_index["not_access_role_name"] == ''){
            $edit_index["not_access_role_name"] = '""';
        }
        
        if(strlen($edit_index["acl_role_name"]) == 0)
        {
            $edit_index["acl_role_name"] = '""';
        }
        if(strlen($edit_index["exclusive_acl_role_name"]) == 0)
        {
            $edit_index["exclusive_acl_role_name"] = '""';
        }
        
        // Add config management authority 2010/02/23 Y.Nakao --start--
        // set access role for room authority
        if(intval($access_role_room) == _AUTH_GENERAL){
            $edit_index["room_auth_moderate"] = "true";
            $edit_index["room_auth_general"] = "true";
        } else if(intval($access_role_room) == _AUTH_MODERATE){
            $edit_index["room_auth_moderate"] = "true";
            $edit_index["room_auth_general"] = "false";
        } else {
            $edit_index["room_auth_moderate"] = "false";
            $edit_index["room_auth_general"] = "false";;
        }
        // Add config management authority 2010/02/23 Y.Nakao --end--
        
        // Add tree access control list 2012/02/22 T.Koyasu -start-
        // modify true/false with value of exclusive_acl_role column
        // Add tree access control list 2011/12/28 Y.Nakao --start--
        if(intval($exclusiveAclRoleRoom) == _AUTH_OTHER)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "true";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_GUEST)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_GENERAL)
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "false";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else if(intval($exclusiveAclRoleRoom) == _AUTH_MODERATE)
        {
            $edit_index['acl_room_auth_moderate'] = "false";
            $edit_index['acl_room_auth_general'] = "false";
            $edit_index['acl_room_auth_guest'] = "false";
            $edit_index['acl_room_auth_logout'] = "false";
        }
        else
        {
            $edit_index['acl_room_auth_moderate'] = "true";
            $edit_index['acl_room_auth_general'] = "true";
            $edit_index['acl_room_auth_guest'] = "true";
            $edit_index['acl_room_auth_logout'] = "true";
        }
        // Add tree access control list 2011/12/28 Y.Nakao --end--
        // Add tree access control list 2012/02/22 T.Koyasu -end-
        return true;
    }
    
    /**
     * indexの新規登録(プライベートツリーで使用)
     * insert index
     *
     * @param $index_data update index data
     * @return true or false
     */
    function insertIndex($index_data){
        // Fix index authority table. Y.Nakao --start--
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        $errMsg = "";
        return $indexManager->addIndex(false, $index_data, $errMsg);
    }
    // Add tree access control list 2011/12/28 Y.Nakao --end--
    
    // Add insert multiple index 2013/10/11 K.Matsuo --start--
    /**
     * indexの新規登録
     * insert index
     *
     * @param $index_data update index data
     * @return true or false
     */
    function insertMultiIndex($index_data_list){
        // Fix Not call indexAuthorityManager 2014/03/15 Y.Nakao --start--
        foreach($index_data_list as $index_data){
            if(strlen($index_data["index_name_english"]) == 0){
                return false;
            }
            $this->insertIndex($index_data);
        }
        // Fix Not call indexAuthorityManager 2014/03/15 Y.Nakao --end--
    }
    // Add insert multiple index 2013/10/11 K.Matsuo --end--
    
    /**
     * index_idが$pidのインデックスに子インデックスがあるかどうか調べる
     * check this index has child index or not
     *
     * @param $pid
     * @return  true:has child index
     *          false:not has child index
     */
    function hasChild($pid){
        $query = "SELECT DISTINCT index_id FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE parent_index_id = ". $pid ." AND ".
                 "is_delete = 0; ";
        $result = $this->Db->execute($query);
        if($result === false) {
            return false;
        }
        if(count($result)>0){
            return true;
        }
        return false;
    }
    
    /**
     * インデックスIDが$idのインデックスの直下にアイテムがあるかどうか調べる
     * check this index has item
     *
     * @param $id index id
     * @return true:has item, false:not item
     */
    function hasItem($id){
        $query = "SELECT DISTINCT item_id FROM ". DATABASE_PREFIX ."repository_position_index ".
                 "WHERE index_id = '". $id ."' AND ".
                 "is_delete = '0'; ";
        $result = $this->Db->execute($query);
        if($result === false) {
            return false;
        }
        if(count($result)>0){
            return true;
        }
        return false;
    }
    
    /**
     * 指定されたIndexIdのインデックスを削除する
     * delete index
     *
     * @param 削除対象のIndexId
     * @return 成功フラグ
     */
    function deleteIndex( $index_id )
    {
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        return $indexManager->deleteIndex($index_data);
    }
    
    /**
     * 指定されたIndexIdに紐づくIndexIdをすべて取得する
     * 
     * @return array
     */
    function getAllChildIndexID( $index_id, &$index_info )
    {   
        $query = "SELECT index_id ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE parent_index_id = ? AND ".
                 "is_delete = ?; ";
        $params = Array();
        $params[] = $index_id;
        $params[] = 0;          // is_delete
        //execute
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $tmpstr = sprintf("No Parent Index Error, index : %d", $index_id );
            $this->Session->setParameter("error_msg", $tmpstr);
            return false;
        }
        for($ii=0; $ii<count($result); $ii++){
            array_push($index_info, $result[$ii]["index_id"]);
            $ret = $this->getAllChildIndexID($result[$ii]['index_id'], $index_info);
            if($ret === false){
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 削除対象のIndexに紐づくアイテムが削除対象かを判断し、削除対象ならば削除
     * また、自身のindexを削除する
     *
     * @param $index_info 削除対象のIndexId
     * @return 成功フラグ
     */
    function deleteIndexItem( $index_info, $is_index_del=true )
    {
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        for($i=0; $i<count($index_info); $i++ ){
            // 処理対象のindex_idを保持
            $index_id = $index_info[$i];
            
            // 対象に紐づくitem_idとitem_noを取得
            $query = "SELECT item_id, item_no ".
                     "FROM ". DATABASE_PREFIX ."repository_position_index ".
                     "WHERE index_id = ? AND ".
                     "is_delete = ?; ";
            $params = Array();
            $params[] = $index_id;
            $params[] = 0;          // is_delete
            //execute
            $item_id_no = $this->Db->execute($query,$params);
            if($item_id_no === false){
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf($errMsg." index : %d", $index_id );
                $this->Session->setParameter("error_msg", $tmpstr);
                return false;
            }
            if( count($item_id_no) > 0 ){
                // 対象のindex直下にアイテムが存在する
                for( $j=0; $j<count($item_id_no); $j++ ){
                    // アイテムが削除対象かどうか判断する
                    // 対象のアイテムが紐づいているインデックス情報を取得
                    $query = "SELECT index_id ".
                             "FROM ". DATABASE_PREFIX ."repository_position_index ".
                             "WHERE item_id = ? AND ".
                             "item_no = ? AND ".
                             "is_delete = ?; ";
                    $params = Array();
                    $params[] = $item_id_no[$j]['item_id'];
                    $params[] = $item_id_no[$j]['item_no'];
                    $params[] = 0;          // is_delete
                    //execute
                    $item_index = $this->Db->execute($query,$params);
                    if($item_index === false){
                        $errMsg = $this->Db->ErrorMsg();
                        $this->Session->setParameter("error_msg", $errMsg);
                        return false;
                    }
                    // 対象のアイテムが紐づいているインデックスに削除対象インデックス以外があった場合、アイテムの削除は行わない
                    $del_flg = 0;
                    for( $k=0; $k<count($item_index); $k++ ){
                        for( $l=0; $l<count($index_info); $l++ ){
                            if( $item_index[$k]['index_id'] == $index_info[$l] ){    // Operator is not '==='
                                $del_flg++;
                            }
                        }
                    }
                    if( count($item_index) === $del_flg){
                        // アイテム削除実行
                        $result = $this->deleteItem( $item_id_no[$j]['item_id'], $item_id_no[$j]['item_no'] );
                        if($result === false){
                            $errMsg = $this->Db->ErrorMsg();
                            $this->Session->setParameter("error_msg", $errMsg);
                            return false;
                        }
                    } else {
                        // 対象Indexに紐づくアイテムの情報を削除
                        $query = "UPDATE ". DATABASE_PREFIX ."repository_position_index ".
                                 "SET is_delete = ?, ".
                                 "del_user_id = ?, ".
                                 "del_date = ? ".
                                 "WHERE index_id = ? AND ".
                                 "item_id = ? AND ".
                                 "item_no = ?; ";
                        $params = Array();
                        $params[] = 1; //is_delete
                        $params[] = $this->Session->getParameter("_user_id"); // del_user_id
                        $params[] = $this->TransStartDate; // del_date
                        $params[] = $index_id;
                        $params[] = $item_id_no[$j]['item_id'];
                        $params[] = $item_id_no[$j]['item_no'];
                        //execute
                        $result = $this->Db->execute($query,$params);
                        if($result === false){
                            $errMsg = $this->Db->ErrorMsg();
                            $this->Session->setParameter("error_msg", $errMsg);
                            return false;
                        }
                    }
                }
            }
            // 対象Indexを削除
            if($is_index_del == true){
                $result = $indexManager->deleteIndex( $index_id );
                if($result === false){
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * move item to parent index
     *
     * @param $id position index id
     * @param $pid position index's parent index id
     * @return true or false
     */
    function moveItemIndexToParentIndex($id, $pid){
        // Fix contents update action 2010/07/02 Y.Nakao --start--
        // get index data
        $index = $this->getIndexEditData($id);
        if($index === false){
            $error = $this->Db->ErrorMsg();
            return false;
        }
        // recount contents
        if($this->checkParentPublicState($id) && $index['public_state']=="false"){
            // 自分が非公開[0]で親が公開[N]の場合   When $id is close and parent index is public,
            // 公開配下になるので自分以下の公開アイテムについて集計する public index contents retry calc.
            // 自分の公開フラグをON　index public_status change 1
            $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                    " SET public_state = ? ".
                    " WHERE index_id = ?; ";
            $params = array();
            $params[] = 1;
            $params[] = $id;
            $result = $this->Db->execute($query,$params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
            $index['public_state'] = "true";
            // Add private_contents count K.Matsuo 2013/06/03 --start--
            $result = $this->subIndexContents($pid, $id);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false; 
            }
            // Add private_contents count K.Matsuo 2013/06/03 --end--
            // recount own contents
            $result = $this->recountContents($id);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false; 
            }
            // Add private_contents count K.Matsuo 2013/05/07 --start--
            // このインデックス以下の非公開コンテンツ数を再計算
            $result = $this->recountPrivateContents($id);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false; 
            }
            // Add private_contents count K.Matsuo 2013/05/07 --end--
            // own contents add parent index contents
            $result = $this->addIndexContents($pid, $id);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false; 
            }
            // 自分の公開フラグをOFF　index public_status change 0
            $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                    " SET public_state = ? ".
                    " WHERE index_id = ?; ";
            $params = array();
            $params[] = 0;
            $params[] = $id;
            $result = $this->Db->execute($query,$params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
        }
        // Fix contents update action 2010/07/02 Y.Nakao --end--
        // Add delete private tree root 2013/06/12 K.Matsuo --start--
        $parentPrivateTreeId = null;
        $error_msg = null;
        $return = $this->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $error_msg);
        if($return == false){
            return false;
        }
        if($parentPrivateTreeId == $id){
            $params = null;                // パラメタテーブル更新用クエリ
            $params[] = $pid;  // param_value
            $params[] = $user_id;                // mod_user_id
            $params[] = $this->TransStartDate;    // mod_date
            $params[] = 'privatetree_parent_indexid';        // param_name
            $result = $this->updateParamTableData($params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("privatetree_parent_indexid update failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                $this->exitAction();
                return false;
            }
        }
        // Add delete private tree root 2013/06/12 K.Matsuo --end--
        // 直下のインデックスをひとつ上に移動move child index to parent index bottom
        $show_order = $this->getShowOrder($pid);
        $move_index = $this->getChildIndexInfo($id);
        for($ii=0; $ii<count($move_index); $ii++){
            $show_order++;
            $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                     "SET parent_index_id  = ?, ".
                     "show_order = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ? ".
                     "WHERE parent_index_id = ? AND ".
                     "index_id = ? AND ".
                     "is_delete = ?; ";
            $params = array();
            $params[] = $pid;
            $params[] = $show_order;
            $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
            $params[] = $this->TransStartDate; // mod_date
            $params[] = $id;
            $params[] = $move_index[$ii]["index_id"];
            $params[] = 0; // is_delete
            //execute
            $result = $this->Db->execute($query,$params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
        }
        // 移動するアイテムのidとnoを取得　get move item idand no
        // Fix change file download action 2013/5/9 Y.Nakao --start--
        require_once WEBAPP_DIR.'/modules/repository/components/RepositorySearch.class.php';
        $repositorySearch = new RepositorySearch();
        $repositorySearch->Db = $this->Db;
        $repositorySearch->Session = $this->Session;
        $repositorySearch->listResords = "all";
        $repositorySearch->keyword = "";
        $repositorySearch->index_id = $id;
        $move_item = $repositorySearch->search();
        // Fix change file download action 2013/5/9 Y.Nakao --end--
        for($ii=0; $ii<count($move_item); $ii++){
            // 移動先にすでに同じアイテムが登録されていないかチェック check move to index is already entry this item or not
            $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_position_index ".
                     "WHERE item_id = ".$move_item[$ii]["item_id"]. " AND ".
                     "item_no = ".$move_item[$ii]["item_no"]. " AND ".
                     "index_id = ".$pid."; ";
            $result = $this->Db->execute($query);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
            if(count($result) > 0){
                // すでに同じアイテムが紐づいている there is this item
                // 紐づいている情報のis_deleteを0に更新 there is item's is_delete is update to "0"
                $query = "UPDATE ". DATABASE_PREFIX ."repository_position_index ".
                         "SET is_delete  = ?, ".
                         "mod_user_id = ?, ".
                         "mod_date = ? ".
                         "WHERE index_id = ? AND ".
                         "item_id = ? AND ".
                         "item_no = ?; ";
                $params = array();
                $params[] = 0;
                $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
                $params[] = $this->TransStartDate; // mod_date
                $params[] = $pid;
                $params[] = $move_item[$ii]["item_id"];
                $params[] = $move_item[$ii]["item_no"];
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                // 移動する方のis_deleteを1に更新 move index's position info delete
                $query = "UPDATE ". DATABASE_PREFIX ."repository_position_index ".
                         "SET is_delete  = ?, ".
                         "mod_user_id = ?, ".
                         "del_user_id = ?, ".
                         "mod_date = ?, ".
                         "del_date = ? ".
                         "WHERE index_id = ? AND ".
                         "item_id = ? AND ".
                         "item_no = ?; ";
                $params = array();
                $params[] = 1;
                $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
                $params[] = $this->Session->getParameter("_user_id");
                $params[] = $this->TransStartDate; // mod_date
                $params[] = $this->TransStartDate;
                $params[] = $id;
                $params[] = $move_item[$ii]["item_id"];
                $params[] = $move_item[$ii]["item_no"];
                $result = $this->Db->execute($query,$params);
                // 移動する方のis_deleteを1に更新 move index's position info delete
                $query = "SELECT shown_status ".
                         "FROM ". DATABASE_PREFIX ."repository_item ".
                         "WHERE item_id = ? AND ".
                         "item_no = ? AND ".
                         "is_delete = 0; ";
                $params = array();
                $params[] = $move_item[$ii]["item_id"];
                $params[] = $move_item[$ii]["item_no"];
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                $shown_status = 0;
                if(count($result) > 0){
                    $shown_status = $result[0]['shown_status'];
                }
                // Fix contents update action 2010/07/02 Y.Nakao --start--
                if($this->checkParentPublicState($id) && $index['public_state'] == "true"){
                    // 自分が公開インデックス[N]で親インデックスも公開[N]の場合 When $id is public and parent index public,
                    if($shown_status == "1"){
                        // このアイテムが公開アイテムならば When this item public,
                        // 親インデックスのコンテンツ数を1削る delete parent index id's contents -1
                        $result = $this->deleteContents($pid);
                        if($result === false){
                            $errMsg = $this->Db->ErrorMsg();
                            return false;
                        }
                    }
                // Add private_contents count K.Matsuo 2013/05/07 --start-- 
                    else {
                        // 親インデックスの非公開コンテンツ数を1削る delete parent index id's contents -1
                        $result = $this->deletePrivateContents($pid);
                        if($result === false){
                            $errMsg = $this->Db->ErrorMsg();
                            return false;
                        }
                    }
                } else {
                    // 親インデックスの非公開コンテンツ数を1削る delete parent index id's contents -1
                    $result = $this->deletePrivateContents($pid);
                    if($result === false){
                        $errMsg = $this->Db->ErrorMsg();
                        return false;
                    }
                // Add private_contents count K.Matsuo 2013/05/07 --start--
                }
                // Fix contents update action 2010/07/02 Y.Nakao --end--
            } else {
                // 紐づいていない there is not this item
                // 移動 move to parent index
                $query = "UPDATE ". DATABASE_PREFIX ."repository_position_index ".
                         "SET index_id  = ?, ".
                         "mod_user_id = ?, ".
                         "mod_date = ? ".
                         "WHERE index_id = ? AND ".
                         "item_id = ? AND ".
                         "item_no = ? AND ".
                         "is_delete = ?; ";
                $params = array();
                $params[] = $pid;
                $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
                $params[] = $this->TransStartDate; // mod_date
                $params[] = $id;
                $params[] = $move_item[$ii]["item_id"];
                $params[] = $move_item[$ii]["item_no"];
                $params[] = 0; // is_delete
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * check index movable
     * if index is root private tree and parent_index_id changes, index can not move
     *
     * @param array $indexData
     * @param int $pid
     * @return boolean
     *              treu : movable
     *              false: not movable
     */
    private function checkMovable($indexData, $pid){
        
        // check this index is private tree
        if(strlen($indexData["owner_user_id"]) === 0){
            return true;
        }
        
        // $index_date[parent_index_id] is not privatetree_parent_indexid
        $errMsg = "";
        $this->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $errMsg);
        if($indexData["parent_index_id"] != $parentPrivateTreeId){
            return true;
        }
        
        // check move in same parent_index_id
        if($indexData["parent_index_id"] == $pid){
            return true;
        }
        
        return false;
    }
    
    /**
     * change parent index
     * 
     * @param $id      this index move このインデックスを
     * @param $pid　        throw in this index bottom このインデックスの下の
     * @param $sort_id this index after この後に挿入。
     *                 first : 先頭
     *                 last  : 末尾 
     * 
     */
    function changeParentIndex($id, $pid, $sort_id){
        // 移動するインデックスの情報を取得 get move index's data
        $index_data = $this->getIndexEditData($id);
        
        if(!$this->checkMovable($index_data, $pid)){
            return false;
        }
        
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        // 移動するインデックスの親と移動先の親がおなじ move index's parent index == move to index's parent index
        if($index_data["parent_index_id"] == $pid){
            // sort show order
            $result = $this->sortIndexData($id, $pid, $sort_id);
            if($result === false){
                return false;
            }
            return true;
        }
        // 移動するインデックスが移動先の親の場合 move index is move to index's parent index 
        if($id == $pid){
            // 移動できない can't move
            return false;
        }
        // 投下するインデックス($id)の全子インデックスのIDを取得 get all child index id
        $child_idx_ids = array();
        $result = $this->getAllChildIndexID($id, $child_idx_ids);
        if($result === false){
            return false;
        }
        // 移動するインデックスと移動先インデックスの親子関係をチェック check move index between move to index
        for($ii=0; $ii<count($child_idx_ids); $ii++){
            if($pid == $child_idx_ids[$ii]){
                // 親のインデックスを子インデックス以下へ移動はできない can't move parent index to child index
                return false;
            }
        }
        // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --start--
        // 移動前の親インデックスから移動するインデックスのコンテンツ数を引く sub index contents num for befor move index parent index
//      $result = $this->subIndexContents($index_data["parent_index_id"], $id);
//      if($result === false){
//          $error = $this->Db->ErrorMsg();
//          return false; 
//      }
//      // 移動先の親インデックスから移動するインデックスのコンテンツ数を足す add index contents num for after move index parent index
//      $result = $this->addIndexContents($pid, $id);
//      if($result === false){
//          $error = $this->Db->ErrorMsg();
//          return false; 
//      }
        // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --end--
        
        // インデックスツリーコンテンツ数対応 add index contents item num 2009/02/16 A.Suzuki --start--
        // 移動前の公開状況を示すフラグ
        $old_state = "unpublic";                // 非公開のインデックス
        // 移動するインデックスが公開中のとき
        if($index_data['public_state'] == "true"){
            $old_state = "public";              // 公開だが上位に非公開あり
            // 上位インデックスの公開状況を取得
            if($this->checkParentPublicState($id)){
                $old_state = "public_all";      // 上位もすべて公開中
            }
        }
        // Add private_contents count K.Matsuo 2013/05/07 --start--
        // 移動前の親インデックスから移動するインデックスのコンテンツ数を引く sub index contents num for befor move index parent index
        $result = $this->subIndexContents($index_data["parent_index_id"], $id);
        if($result === false){
            $error = $this->Db->ErrorMsg();
            return false; 
        }
        // Add private_contents count K.Matsuo 2013/05/07 --end--
        // インデックスツリーコンテンツ数対応 add index contents item num 2009/02/16 A.Suzuki --end--
        // 移動するインデックス及びその子インデックスに移動先のowner_user_idを設定する。 Add K.Matsuo 2013/04/16 --start--
        $parentPrivateTreeId = null;
        $error_msg = null;
        $return = $this->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $error_msg);
        if($return == false){
            return false;
        }
        
        // 移動するインデックスの親IDをつなぎ換える change move index's parent index id
        $index_data["parent_index_id"] = $pid;
        
        // change move index's owner_user_id
        if($pid == 0){
            $index_data["owner_user_id"] = "";
        } else {
            $index_data["owner_user_id"] = $this->getIndexOwnerUserId($pid);
        }
        
        $result = $indexManager->updateIndex($index_data);
        if($result === false){
            $error = $this->Db->ErrorMsg();
            return false;
        } else if($result === "noEnglishName"){
            $this->failTrans(); // ROLLBACK
            $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
            return 'error';
        // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
        } else if($result === "wrongFormatIssn"){
            $this->failTrans(); // ROLLBACK
            $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
            return 'error';
        }
        // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
        
        // change child index's owner_user_id of move index
        for($ii=0; $ii<count($child_idx_ids); $ii++){
            $child_index_data = $this->getIndexEditData($child_idx_ids[$ii]);
            
            // set parent index's owner_user_id
            $child_index_data["owner_user_id"] = $index_data["owner_user_id"];
            
            // update child indexes
            $result = $indexManager->updateIndex($child_index_data);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false;
            } else if($result === "noEnglishName"){
                $this->failTrans(); // ROLLBACK
                $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
                return 'error';
                // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
            } else if($result === "wrongFormatIssn"){
                $this->failTrans(); // ROLLBACK
                $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
                return 'error';
            }
            // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
        }
        
        // インデックスツリーコンテンツ数対応 add index contents item num 2009/02/16 A.Suzuki --start--
        // 非公開インデックスの場合コンテンツ数は変化しない
        
//      if($old_state != "unpublic"){   // Comment out private_contents count K.Matsuo 2013/05/07
            // 移動後の上位インデックスの公開状況を取得
            if($this->checkParentPublicState($id) == true){     // 上位はすべて公開中
                if($old_state == "public_all"){
                    // 公開 -> 公開
                    // 上位インデックスにコンテンツ数追加 add index contents num for after move index parent index
                    $result = $this->addIndexContents($pid, $id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                } else if($old_state == "public") {
                    // 非公開 -> 公開
                    // このインデックス以下のコンテンツ数を再計算
                    $result = $this->recountContents($id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // Add private_contents count K.Matsuo 2013/05/07 --start--
                    // このインデックス以下の非公開コンテンツ数を再計算
                    $result = $this->recountPrivateContents($id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // Add private_contents count K.Matsuo 2013/05/07 --end--
                    // 上位インデックスにコンテンツ数追加 add index contents num for after move index parent index
                    $result = $this->addIndexContents($pid, $id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                } else if($old_state == "unpublic"){
                    // 非公開 -> 非公開
                    // 上位インデックスに非公開コンテンツ数追加 add index private_contents num for after move index parent index
                    $result = $this->addIndexContents($pid, $id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                }
            } else {    // 上位に非公開あり
                if($old_state == "public_all"){
                    // 公開 -> 非公開
                    // このインデックス以下のコンテンツ数を0にする
                    $result = $this->resetContents($id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // Add private_contents count K.Matsuo 2013/05/07 --start--
                    // このインデックス以下の非公開コンテンツ数を再計算
                    $result = $this->recountPrivateContents($id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // 上位インデックスに非公開コンテンツ数追加 add index private_contents num for after move index parent index
                    $result = $this->addIndexContents($pid, $id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // Add private_contents count K.Matsuo 2013/05/07 --end--
                } else if($old_state == "public" || $old_state == "unpublic") {
                    // 非公開 -> 非公開
                    // コンテンツ数に変化なし
                    // Add private_contents count K.Matsuo 2013/05/07 --start--
                    // 上位インデックスに非公開コンテンツ数追加 add index private_contents num for after move index parent index
                    $result = $this->addIndexContents($pid, $id);
                    if($result === false){
                        $error = $this->Db->ErrorMsg();
                        return false; 
                    }
                    // Add private_contents count K.Matsuo 2013/05/07 --end--
                }
            }
//      }   // Comment out private_contents count K.Matsuo 2013/05/07
        // インデックスツリーコンテンツ数対応 add index contents item num 2009/02/16 A.Suzuki --end--
        
        // 非公開インデックス以下に移動した場合新着情報から削除する add delete whatsnew 2009/02/10 A.Suzuki --start--
        // 移動先の上位インデックスに非公開がある場合
        if($this->checkParentPublicState($id) == false){
            $this->deleteWhatsnewForIndex($id);
        }
        // 非公開インデックス以下に移動した場合新着情報から削除する add delete whatsnew 2009/02/10 A.Suzuki --end--
        
        // 移動先のshow_orderを並べる
        $result = $this->sortIndexData($id, $pid, $sort_id);
        if($result === false){
            return false;
        }
    }
    
    /**
     * 親Index_idを取得する
     *
     * @param $index_id 検索対象のIndexId
     * @param $mod_date 更新日時に変更がないか確認する
     * @return 親IndexId 
     */
    function getParentIndexId ( $index_id )
    {
        $query = "SELECT parent_index_id ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ? AND ".
                 "is_delete = ?; ";
        $params = Array();
        $params[] = $index_id;
        $params[] = 0;          // is_delete
        //execute
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $tmpstr = sprintf("No Parent Index Error, index : %d", $index_id );
            $this->Session->setParameter("error_msg", $tmpstr);
            //$this->failTrans();               //トランザクション失敗を設定(ROLLBACK)
            return null;
        }
        return $result[0]['parent_index_id'];
    }
    
    /**
     * get edit index data
     *
     * @param $id edit index id
     */
    function getIndexEditData($id){
        // 編集に必要な情報
        // index_id、名前(日/英)、公開/非公開、公開日、投稿権限あり、投稿権限なし、下にアイテム/インデックスがあるかないか
        $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ". $id ." AND ".
                 "is_delete = 0; ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1) {
            return "";
        }
        if(count($result)==1){
            $edit_index = $result[0];
            $edit_index["old_index_id"] = $result[0]["index_id"];
            if($edit_index["public_state"] == "1"){
                // set pub dtate for html 
                $edit_index["public_state"] = "true";
                // set pub date 
                // Bugfix input scrutiny 2011/06/17 --start--
                $pos = strpos($edit_index["pub_date"], " ");
                if(!is_numeric($pos)){
                    $pos = strlen($edit_index["pub_date"]);
                }
                // Bugfix input scrutiny 2011/06/17 --end--
                $edit_index["pub_date"] = substr($edit_index["pub_date"],0,$pos);
                $date = explode("-", $edit_index["pub_date"]);
                $edit_index["pub_year"] = $date[0];
                $edit_index["pub_month"] = $date[1];
                $edit_index["pub_day"] = $date[2];
            } else {
                // set pub dtate for html
                $edit_index["public_state"] = "false";
                // set pub date to now date
                $DATE = new Date();
                $edit_index["pub_year"] = $DATE->getYear();
                $edit_index["pub_month"] = sprintf("%02d",$DATE->getMonth());
                $edit_index["pub_day"] = sprintf("%02d",$DATE->getDay());
            }
            // Add index thumbnail 2010/08/11 Y.Nakao --start--
            if(strlen($edit_index["thumbnail"]) > 0){
                $edit_index["thumbnail"] = "true";
            } else {
                $edit_index["thumbnail"] = "false";
            }
            $edit_index["thumbnail_name"] = "";
            $edit_index["thumbnail_mime_type"] = "";
            // Add index thumbnail 2010/08/11 Y.Nakao --start--
            $this->getAccessGroupData($edit_index["access_group"], $edit_index["exclusive_acl_group"], $edit_index);
            $this->getAccessAuthData($edit_index["access_role"], $edit_index["exclusive_acl_role"], $edit_index);
            return $edit_index;
        }
    }
    
    /**
     * 1アイテム削除
     * 
     * @param 
     * @param 
     * @return 成功フラグ
     */
    function deleteItem( $item_id, $item_no )
    {
        // ユーザIDゲット
        $user_id = $this->Session->getParameter("_user_id");
        
        $result = $this->deleteItemData($item_id,$item_no,$user_id,$error_msg);
        if($result === false){
            $this->Session->setParameter("error_msg",$error_msg);
            return false;
        }
        
        return true;
    }
    
    /**
     * 
     * $parent_index_id直下のインデックス情報をshow_order順にソートして渡す 
     * show_order順で並ぶ
     * 新規作成分も込み
     * 
     * @return array
     */
    private function getChildIndexInfo($parent_index_id)
    {
        // 既存の$parent_index_id直下の子インデックス情報を取得 get parent index's child index data from DB
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE `is_delete` = 0 AND ".
                 "`parent_index_id` = ". $parent_index_id." ".
                 "ORDER BY `show_order`; ";
        $result = $this->Db->execute($query);
        if($result === false) {
            return false;
        }
        
        for ($ii=0; $ii<count($result); $ii++){
            // ベース権限とルーム権限を分割する
            $exclusive_acl_role = explode("|", $result[$ii]["exclusive_acl_role"]);
            if (strlen($exclusive_acl_role[0]) == 0) {
                $result[$ii]["exclusive_acl_role_id"] = "0";
            } else {
                $result[$ii]["exclusive_acl_role_id"] = $exclusive_acl_role[0];
            }

            if (strlen($exclusive_acl_role[1]) == 0) {
                $result[$ii]["exclusive_acl_room_auth"] = "-1";
            } else {
                $result[$ii]["exclusive_acl_room_auth"] = $exclusive_acl_role[1];
            }
        }
        return $result;
    }
    
    /**
     * reset show order
     *
     * @param $id      this index move
     * @param $pid　        throw in this index bottom
     * @param $sort_id this index after
     *                 first : 先頭
     *                 last  : 末尾 
     * @return true or false
     */
    function sortIndexData($id, $pid, $sort_id){
        // 親インデックス直下のインデックス情報を全部取得 get parent index's child index
        $child_idx = array();   // 親インデックス直下の全インデックスID
        $child_idx = $this->getChildIndexInfo($pid);
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        if($child_idx === false){
            return false;
        }
        // 途中挿入の場合に一時保存用として使用　use this param for add middle
        $update_idx = null;
        $show_order = 1;
        for($ii=0; $ii<count($child_idx); $ii++){
            // show_order付け替え sort order by show_order
            if($sort_id == 'first'){
                // 先頭に追加 add first
                if($child_idx[$ii]["index_id"] == $id){
                    // 移動するインデックスなので先頭に when move index, add first
                    $child_idx[$ii]["show_order"] = 1;
                } else if($ii==0){
                    // 先頭に追加されるので一個あける add first index is already there
                    $show_order++;
                    $child_idx[$ii]["show_order"] = $show_order;
                } else {
                    // そのまま as it is
                    $child_idx[$ii]["show_order"] = $show_order;
                }
            } else if($sort_id == 'last'){
                // 末尾に追加 add last
                if($child_idx[$ii]["index_id"] == $id){
                    // ループ最後の加算処理よけ roop last show_ordera add delete 
                    $show_order--;
                    // 移動するインデックスなので末尾に add last
                    $child_idx[$ii]["show_order"] = count($child_idx);
                } else {
                    // そのまま as it is
                    $child_idx[$ii]["show_order"] = $show_order;
                }
            } else {
                // 途中挿入 add middle
                // 挿入更新フラグ
                if($sort_id == $child_idx[$ii]["index_id"]){
                    // このインデックスの後に挿入 add this index after
                    $child_idx[$ii]["show_order"] = $show_order;
                    $show_order++;
                    if($update_idx != null){
                        $update_idx["show_order"] = $show_order;
                        $result = $indexManager->updateIndex($update_idx);
                        if($result === false){
                            $error = $this->Db->ErrorMsg();
                            return false;
                        } else if($result === "noEnglishName"){
                            $this->failTrans(); // ROLLBACK
                            $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
                            return 'error';
                        // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
                        } else if($result === "wrongFormatIssn"){
                            $this->failTrans(); // ROLLBACK
                            $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
                            return 'error';
                        }
                        // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
                    } else {
                        $update_idx = $show_order;
                    }
                } else if($id == $child_idx[$ii]["index_id"]){
                    // ループ最後の加算処理よけ roop last show_ordera add delete 
                    $show_order--;
                    if($update_idx != null){
                        $child_idx[$ii]["show_order"] = $update_idx;
                    } else {
                        $update_idx = $child_idx[$ii];
                    }
                } else {
                    $child_idx[$ii]["show_order"] = $show_order;
                }
            }
            $show_order++;
            $result = $indexManager->updateIndex($child_idx[$ii]);
            if($result === false){
                $error = $this->Db->ErrorMsg();
                return false;
            } else if($result === "noEnglishName"){
                $this->failTrans(); // ROLLBACK
                $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_noEnglishName"));
                return 'error';
            // Add issn and biblio flag 2014/04/18 T.Ichikawa --start--
            } else if($result === "wrongFormatIssn"){
                $this->failTrans(); // ROLLBACK
                $this->Session->setParameter("tree_error_msg", $this->smartyAssign->getLang("repository_tree_wrongFormatIssn"));
                return 'error';
            }
            // Add issn and biblio flag 2014/04/18 T.Ichikawa --end--
        }
        return true;
    }
    
    /**
     * update index key index_id and mod_date
     *
     * @param $index_data update index data
     * @return true or false
     */
    function updateIndex($index_data)
    {
        $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
        return $indexManager->updateIndex($index_data);
    }
    // change index tree 2008/12/26 Y.Nakao --end--
    
    /**
     * インデックス及び所属インデックスのカラムをロックする
     * 
     * @param $index_id 対象レコードのIndexId
     * @return 成功フラグ
     */
    function rockIndexRecorde( $index_id, $mod_date )
    {
        //最低限、更新日時（mod_date）のみ取得すれば良い。
        //必要であれば他のカラムを取得しても良い。
        
        // repository_indexテーブルをロック
        $query = "SELECT mod_date ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ? AND ".
                 "is_delete = ? AND ".
                 "mod_date = ? ".
                 "FOR UPDATE;";
        $params = Array();
        $params[] = $index_id;          // item_type_id
        $params[] = 0;
        $params[] = $mod_date;
        $result = $this->Db->execute($query, $params);
        //SQLエラーの場合
        if($result === false) {
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_code", $errMsg);
            //エラー処理を行う
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
            return false;
        }
        //取得結果が0件の場合、UPDATE対象のレコードは存在しないこととなる。
        //以降のUPDATE処理は行わないこと。
        if(count($result)==0) {
            $this->Session->setParameter("error_code", "No Index Data");
            //エラー処理を行う
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
            return false;
        }
        return true;
    }
    // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --start--
    /**
     * index_idが$pidのインデックスコンテンツ数から、index_idが$idのインデックスコンテンツ数を引く
     *  index contents num for index_id is $pid sub index contents num for index_id is $id
     * 
     * @param $pid parent index id
     * @param $id index id
     */ 
    function subIndexContents($pid, $id){
        // 親インデックスをチェック check parent index id
        if($pid == 0){
            // 親インデックスがルートの場合、計算しない when $pid is root, do not calculation
            return true;
        }
        // 引くコンテンツ数を取得する get contents num
        $parent_index = $this->getIndexEditData($pid);
        if($parent_index === false){
            $error = $this->Db->ErrorMsg();
            return false;
        }
        $index = $this->getIndexEditData($id);
        if($index === false){
            $error = $this->Db->ErrorMsg();
            return false;
        }
        // コンテンツ数を減算 sub coontents num
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET contents = ?, ".
                 "private_contents = ?, ".	    // インデックスツリー非公開コンテンツ数対応 2013/05/15 K.Matsuo
                 "mod_user_id = ?, ".
                 "mod_date = ?, ".
                 "is_delete = ? ".
                 "WHERE index_id = ?; ";
        $params = array();
        $params[] = $parent_index["contents"] - $index["contents"];
        $params[] = $parent_index["private_contents"] - $index["private_contents"];    // インデックスツリー非公開コンテンツ数対応 2013/05/15 K.Matsuo
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->TransStartDate;
        $params[] = 0;
        $params[] = $pid;
        $result = $this->Db->execute($query, $params);
        // error check
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        // 親インデックスにも実行 run at parent index 
        $result = $this->subIndexContents($parent_index["parent_index_id"], $id);
        return $result;
    }
    
    /**
     * index_idが$pidのインデックスコンテンツ数から、index_idが$idのインデックスコンテンツ数を足す
     *  index contents num for index_id is $pid add index contents num for index_id is $id
     * 
     * @param $pid parent index id
     * @param $id index id
     */ 
    function addIndexContents($pid, $id){
        // 親インデックスをチェック check parent index id
        if($pid == 0){
            // 親インデックスがルートの場合、計算しない when $pid is root, do not calculation
            return true;
        }
        // 加えるコンテンツ数を取得する get contents num
        $parent_index = $this->getIndexEditData($pid);
        if($parent_index === false){
            $error = $this->Db->ErrorMsg();
            return false;
        }
        $index = $this->getIndexEditData($id);
        if($index === false){
            $error = $this->Db->ErrorMsg();
            return false;
        }
        // コンテンツ数を加算 add coontents num
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET contents = ?, ".
                 "private_contents = ?, ".	    // インデックスツリー非公開コンテンツ数対応 2013/05/15 K.Matsuo
                 "mod_user_id = ?, ".
                 "mod_date = ?, ".
                 "is_delete = ? ".
                 "WHERE index_id = ?; ";
        $params = array();
        $params[] = $parent_index["contents"] + $index["contents"];
        $params[] = $parent_index["private_contents"] + $index["private_contents"];    // インデックスツリー非公開コンテンツ数対応 2013/05/15 K.Matsuo
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->TransStartDate;
        $params[] = 0;
        $params[] = $pid;
        $result = $this->Db->execute($query, $params);
        // error check
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        // 親インデックスにも実行 run at parent index
        $result = $this->addIndexContents($parent_index["parent_index_id"], $id);
        return $result;
    }
    // インデックスツリーコンテンツ数対応 add index contents item num 2008/12/26 Y.Nakao --end-- 

    // インデックス以下のアイテムを新着情報から削除する　2009/02/09 A.Suzuki --start--
    /**
     * インデックスおよび非公開の子インデックスにのみ所属している場合新着情報から削除
     *
     * @param  $index_id
     */
    function deleteWhatsnewForIndex($index_id){
        // インデックスに所属するアイテムのitem_id, item_noを取得
        $query = "SELECT item_id, item_no ".
                 "FROM ".DATABASE_PREFIX."repository_position_index ".
                 "WHERE index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $items = $this->Db->execute($query, $params);
        if($items === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        // 各アイテムの所属を確認
        for($ii=0; $ii<count($items); $ii++){
            $no_delete_flg = false;
            
            $query = "SELECT ".DATABASE_PREFIX."repository_index.index_id, ".
                     "       ".DATABASE_PREFIX."repository_index.public_state ".
                     "FROM   ".DATABASE_PREFIX."repository_index, ".
                     "       ".DATABASE_PREFIX."repository_position_index ".
                     "WHERE  ".DATABASE_PREFIX."repository_position_index.item_id = ? ".
                     "AND    ".DATABASE_PREFIX."repository_position_index.item_no = ? ".
                     "AND    ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
                     "AND    ".DATABASE_PREFIX."repository_position_index.index_id = ".DATABASE_PREFIX."repository_index.index_id ".
                     "AND    ".DATABASE_PREFIX."repository_index.is_delete = 0;";
            $params = array();
            $params[] = $items[$ii]['item_id'];
            $params[] = $items[$ii]['item_no'];
            $result = $this->Db->execute($query, $params);
            if($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                return false;
            }
            
            for($jj=0; $jj<count($result); $jj++){
                if($result[$jj]['index_id'] != $index_id){
                    // 編集中のインデックス以外に所属しているインデックスの公開状況を確認
                    if($result[$jj]['public_state'] == 1){
                        // 公開中である場合、その親が非公開でないかチェック
                        if($this->checkParentPublicState($result[$jj]['index_id']) == true){
                            // 親に非公開がない場合
                            $no_delete_flg = true;
                            break;
                        }
                    }
                }
            }
            
            // 非公開インデックスおよび非公開の子インデックスにのみ所属している場合新着情報から削除
            if($no_delete_flg == false){
                $this->deleteWhatsnew($items[$ii]['item_id']);
            }
        }
        
        // 子インデックスのindex_idを取得
        $query = "SELECT index_id ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE parent_index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        for($ii=0; $ii<count($result); $ii++){
            // 子インデックスおよび非公開の子インデックスにのみ所属している場合新着情報から削除する
            $this->deleteWhatsnewForIndex($result[$ii]['index_id']);
        }
    }
    // インデックス以下のアイテムを新着情報から削除する　2009/02/09 A.Suzuki --end--
    
    // コンテンツ数対応 2009/02/16 A.Suzuki --start--
    /**
     * 指定インデックス以下のコンテンツ数を再計算し、更新する
     *
     * @param  $index_id
     * @return $contents インデックス以下のコンテンツ数
     */
    function recountContents($index_id=0){
        $contents = 0;
        if($index_id != 0) {    // ルートインデックス以外
            // インデックス公開フラグ
            $index_public_flag = $this->checkIndexState($index_id);
            if($index_public_flag === false) {
                return false;
            }
            
            if($index_public_flag == "public"){
                // インデックス直下のコンテンツ数取得
                $query = "SELECT ".DATABASE_PREFIX."repository_position_index.item_id, ".
                         "       ".DATABASE_PREFIX."repository_position_index.item_no, ".
                         "       ".DATABASE_PREFIX."repository_position_index.index_id ".
                         "FROM   ".DATABASE_PREFIX."repository_position_index, ".
                         "       ".DATABASE_PREFIX."repository_item ".
                         "WHERE  ".DATABASE_PREFIX."repository_position_index.index_id = ? ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_id = ".DATABASE_PREFIX."repository_item.item_id ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_no = ".DATABASE_PREFIX."repository_item.item_no ".
                         "AND    ".DATABASE_PREFIX."repository_item.shown_status = 1 ".
                         "AND    ".DATABASE_PREFIX."repository_item.is_delete = 0; ";
                $params = array();
                $params[] = $index_id;
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                
                for($ii=0; $ii<count($result); $ii++){
                    $contents++;
                }
            }
        }
        
        // 子インデックスのindex_id, public_stateを取得
        $query = "SELECT index_id, public_state ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE parent_index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        for($ii=0; $ii<count($result); $ii++){
            // 子インデックスが公開中である場合
            if($result[$ii]["public_state"] == "1"){
                // 子インデックスのアイテム数を取得
                $contents += $this->recountContents($result[$ii]["index_id"]);
            }
        }
        
        // update contents
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET contents = ? ".
                 "WHERE index_id = ? ";
                 "AND is_delete = 0; ";
        $params = array();
        $params[] = $contents;
        $params[] = $index_id;
        //execute
        $result = $this->Db->execute($query,$params);
        if($result == false){
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        return $contents;
    }
    
    /**
     * 指定インデックス以下の非公開コンテンツ数を再計算し、更新する
     *
     * @param  $index_id
     * @return $contents インデックス以下のコンテンツ数
     */
    function recountPrivateContents($index_id=0){
        $contents = 0;
        if($index_id != 0) {    // ルートインデックス以外
            // インデックス公開フラグ
            $index_public_flag = $this->checkIndexState($index_id);
            if($index_public_flag === false) {
                return false;
            }
            
            if($index_public_flag == "public"){
                // インデックスが公開状態の場合、非公開設定のアイテムのみ取得する
                $query = "SELECT ".DATABASE_PREFIX."repository_position_index.item_id, ".
                         "       ".DATABASE_PREFIX."repository_position_index.item_no, ".
                         "       ".DATABASE_PREFIX."repository_position_index.index_id ".
                         "FROM   ".DATABASE_PREFIX."repository_position_index, ".
                         "       ".DATABASE_PREFIX."repository_item ".
                         "WHERE  ".DATABASE_PREFIX."repository_position_index.index_id = ? ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_id = ".DATABASE_PREFIX."repository_item.item_id ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_no = ".DATABASE_PREFIX."repository_item.item_no ".
                         "AND    ".DATABASE_PREFIX."repository_item.shown_status = 0 ".
                         "AND    ".DATABASE_PREFIX."repository_item.is_delete = 0; ";
                $params = array();
                $params[] = $index_id;
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                
                for($ii=0; $ii<count($result); $ii++){
                    $contents++;
                }
            } else {
                // インデックスが非公開状態の場合、全てのアイテムを取得する
                $query = "SELECT ".DATABASE_PREFIX."repository_position_index.item_id, ".
                         "       ".DATABASE_PREFIX."repository_position_index.item_no, ".
                         "       ".DATABASE_PREFIX."repository_position_index.index_id ".
                         "FROM   ".DATABASE_PREFIX."repository_position_index, ".
                         "       ".DATABASE_PREFIX."repository_item ".
                         "WHERE  ".DATABASE_PREFIX."repository_position_index.index_id = ? ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_id = ".DATABASE_PREFIX."repository_item.item_id ".
                         "AND    ".DATABASE_PREFIX."repository_position_index.item_no = ".DATABASE_PREFIX."repository_item.item_no ".
                         "AND    ".DATABASE_PREFIX."repository_item.is_delete = 0; ";
                $params = array();
                $params[] = $index_id;
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    return false;
                }
                for($ii=0; $ii<count($result); $ii++){
                    $contents++;
                }               
            }
        }
        
        // 子インデックスのindex_idを取得
        $query = "SELECT index_id ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE parent_index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        for($ii=0; $ii<count($result); $ii++){
            // 子インデックスのアイテム数を取得
            $contents += $this->recountPrivateContents($result[$ii]["index_id"]);
        }
        
        // update contents
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET private_contents = ? ".
                 "WHERE index_id = ? ";
                 "AND is_delete = 0; ";
        $params = array();
        $params[] = $contents;
        $params[] = $index_id;
        //execute
        $result = $this->Db->execute($query,$params);
        if($result == false){
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        return $contents;
    }
    
    /**
     * 指定インデックス以下のコンテンツ数をリセットする
     *
     * @param  $index_id
     */
    function resetContents($index_id=0){
        // update contents
        $query = "UPDATE ". DATABASE_PREFIX ."repository_index ".
                 "SET contents = 0 ".
                 "WHERE index_id = ? ";
                 "AND is_delete = 0; ";
        $params = array();
        $params[] = $index_id;
        //execute
        $result = $this->Db->execute($query,$params);
        if($result == false){
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        // 子インデックスのindex_idを取得
        $query = "SELECT index_id ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE parent_index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        
        for($ii=0; $ii<count($result); $ii++){
            // 子インデックスのコンテンツ数をリセットする
            $this->resetContents($result[$ii]["index_id"]);
        }
        
        return true;
    }
    // Add index list 2011/4/14 S.Abe --start--
    /**
     * check whether input value is blank only or not
     *
     * @param $name index list name
     * @return $name index list name
     */
    function checkBlank($name) {
        $check_name = str_replace(" ","", $name);
        $check_name = str_replace("　","",$check_name);
        if(strlen($check_name) == 0) {
            $name = "";
        }
        return $name;
    }
    // Add index list 2011/4/14 S.Abe --end--
    
    // Bugfix input scrutiny 2011/06/17 Y.Nakao --start--
    /**
     * escape JSON
     *
     * @param array $index_data
     */
    function escapeJSON($str, $lineFlg=false){
        
        $str = str_replace("\\", "\\\\", $str);
        $str = str_replace('[', '\[', $str);
        $str = str_replace(']', '\]', $str);
        $str = str_replace('"', '\"', $str);
        if($lineFlg){
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\n", "\\n", $str);
        }
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        
        return $str;
    }
    // Bugfix input scrutiny 2011/06/17 Y.Nakao --end--
    
    // Add tree access control list 2011/12/28 Y.Nakao --start--
    /**
     * set default
     *
     */
    // Add harvesting 2012/03/13 T.Koyasu -start-
    // changed access(private->public)
    public function setDefaultAccessControlList()
    // Add harvesting 2012/03/13 T.Koyasu -end-
    {
        $this->defaultAccessRoleIds_ = '';
        $this->defaultAccessRoleRoom_ = _AUTH_CHIEF;
        $this->defaultAccessGroups_ = '';
        $this->defaultExclusiveAclRoleIds_ = '';
        $this->defaultExclusiveAclRoleRoom_ = RepositoryConst::TREE_DEFAULT_EXCLUSIVE_ACL_ROLE_ROOM;
        $this->defaultExclusiveAclGroups_ = '';
        
        // setting defaultAccessRole_
        $query = "SELECT `".RepositoryConst::DBCOL_AUTHORITIES_ROLE_AUTHORITY_ID."` ".
                " FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_AUTHORITIES.
                " ORDER BY `".RepositoryConst::DBCOL_AUTHORITIES_ROLE_AUTHORITY_ID."` ASC;";
        $result = $this->Db->execute($query);
        if($result===false || count($result) == 0)
        {
            return;
        }
        for($ii=0;$ii<count($result); $ii++)
        {
            if($result[$ii][RepositoryConst::DBCOL_AUTHORITIES_ROLE_AUTHORITY_ID] == _ROLE_AUTH_GUEST)
            {
                continue;
            }
            if(strlen($this->defaultAccessRoleIds_) > 0)
            {
                $this->defaultAccessRoleIds_ .= ',';
            }
            $this->defaultAccessRoleIds_ .= $result[$ii][RepositoryConst::DBCOL_AUTHORITIES_ROLE_AUTHORITY_ID];
        }
        // setting defaultAccessGroup_
        // setting defaultExclusiveAclRole_
        // setting defaultExclusiveAclGroup_
    }
    // Add tree access control list 2011/12/28 Y.Nakao --end--
    
    
    // Add private_tree access control list 2013/04/23 K.Matsuo --start--
    public function setPrivateTreeDefaultAccessControlList()
    {
        // Mod Bug fix No.55 2014/03/24 T.Koyasu --start--
        $this->defaultAccessRoleIds_ = "";
        // Mod Bug fix No.55 2014/03/24 T.Koyasu --end--
        $this->defaultAccessRoleRoom_ = _AUTH_CHIEF;
        $this->defaultAccessGroups_ = '';
        $this->defaultExclusiveAclRoleIds_ = '';
        $this->defaultExclusiveAclRoleRoom_ = RepositoryConst::TREE_DEFAULT_EXCLUSIVE_ACL_ROLE_ROOM;
        $this->defaultExclusiveAclGroups_ = '';
    }
    // Add private_tree access control list 2013/04/23 K.Matsuo --end--
    
    // Fix private tree auth id. 2013/06/12 Y.Nakao --start--
    /**
     * Get insert auth ids
     *
     * @return string
     */
    public function getInsertAuthIds()
    {
        $insAuthIds = '';
        
        // setting item insert user authority
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_ADMIN)
        {
            // _ROLE_AUTH_ADMIN = systemAdmin, 7=admin(not define)
            $insAuthIds .= _ROLE_AUTH_ADMIN.',7';
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_CHIEF)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_CHIEF.','._ROLE_AUTH_CLERK;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_MODERATE)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_MODERATE;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GENERAL)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_GENERAL;
        }
        if(REPOSITORY_ITEM_REGIST_AUTH <= _AUTH_GUEST)
        {
            if(strlen($insAuthIds) > 0)
            {
                $insAuthIds .= ',';
            }
            $insAuthIds .= _ROLE_AUTH_GUEST;
        }
        
        return $insAuthIds;
    }
    // Fix private tree auth id. 2013/06/12 Y.Nakao --end--
    
    // Add get owner_user_id 2013/04/17 K.Matsuo --start--
    /**
     * owner_user_idを取得する
     *
     * @param $index_id 検索対象のIndexId
     * @return owner_user_id 
     */
    function getIndexOwnerUserId ( $index_id )
    {
        $query = "SELECT owner_user_id ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ? AND ".
                 "is_delete = ?; ";
        $params = Array();
        $params[] = $index_id;
        $params[] = 0;          // is_delete
        //execute
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $tmpstr = sprintf("No Parent Index Error, index : %d", $index_id );
            $this->Session->setParameter("error_msg", $tmpstr);
            //$this->failTrans();               //トランザクション失敗を設定(ROLLBACK)
            return null;
        }
        return $result[0]['owner_user_id'];
    }
    // Add get owner_user_id 2013/04/17 K.Matsuo --end--
    
    // Add harvesting 2012/03/13 T.Koyasu -start-
    // get private member
    public function getDefaultAccessRoleIds()
    {
        return $this->defaultAccessRoleIds_;
    }
    public function getDefaultAccessRoleRoom()
    {
        return $this->defaultAccessRoleRoom_;
    }
    public function getDefaultAccessGroups()
    {
        return $this->defaultAccessGroups_;
    }
    public function getDefaultExclusiveRoleIds()
    {
        return $this->defaultExclusiveAclRoleIds_;
    }
    public function getDefaultExclusiveAclRoleRoom()
    {
        return $this->defaultExclusiveAclRoleRoom_;
    }
    public function getDefaultExclusiveAclGroups()
    {
        return $this->defaultExclusiveAclGroups_;
    }
    // Add harvesting 2012/03/13 T.Koyasu -end-
    // Add new prefix id 2013/12/24 T.Ichikawa --start--
    public function sendIndexParameterToHtml($index_data)
    {
        $repositoryDownload = new RepositoryDownload();
        
        // make JSON
        $index_json = '';
        $index_json .= '{';
        $index_json .= '"id": "'.$index_data["index_id"].'", ';
        $name_jp = $this->escapeJSON($index_data["index_name"]);
        $index_json .= '"name_jp": "'.$name_jp.'", ';
        $name_en = $this->escapeJSON($index_data["index_name_english"]);
        $index_json .= '"name_en": "'.$name_en.'", ';
        $comment = $this->escapeJSON($index_data["comment"], true);
        $index_json .= '"comment": "'.$comment.'", ';
        $index_json .= '"pid": "'.$index_data["parent_index_id"].'", ';
        $index_json .= '"show_order": "'.$index_data["show_order"].'", ';
        $index_json .= '"display_more": "'.$index_data["display_more"].'", ';
        if($this->hasChild($index_data["index_id"]) == true && $index_data["index_id"] !== "0") {
            $has_child = "true";
        } else {
            $has_child = "false";
        }
        $index_json .= '"has_child": "'.$has_child.'", ';
        if($index_data["rss_display"] == "1" && $index_data["index_id"] != "0") {
            $rss_display = "true";
        } else {
            $rss_display = "false";
        }
        $index_json .= '"rss_display": "'.$rss_display.'", ';
        $index_json .= '"display_type": "'.$index_data["display_type"].'", ';
        $index_json .= '"select_index_list_display": "'.$index_data["select_index_list_display"].'", ';
        $select_index_list_name = $this->escapeJSON($index_data["select_index_list_name"]);
        $index_json .= '"select_index_list_name": "'.$select_index_list_name.'", ';
        $select_index_list_name_english = $this->escapeJSON($index_data["select_index_list_name_english"]);
        $index_json .= '"select_index_list_name_english": "'.$select_index_list_name_english.'", ';
        $index_json .= '"mod_date": "'.$index_data["mod_date"].'", ';
        $index_json .= '"public_state": "'.$index_data["public_state"].'", ';
        $index_json .= '"pub_year": "'.$index_data["pub_year"].'", ';
        $index_json .= '"pub_month": "'.$index_data["pub_month"].'", ';
        $index_json .= '"pub_day": "'.$index_data["pub_day"].'", ';
        if(!isset($index_data["access_group_id"])) {
            $index_data["access_group_id"] = "";
        }
        $index_json .= '"access_group_id": "'.$index_data["access_group_id"].'", ';
        $index_json .= '"access_group_name": ['.$index_data["access_group_name"].'], ';
        $index_json .= '"not_access_group_id": "'.$index_data["not_access_group_id"].'", ';
        $index_json .= '"not_access_group_name": ['.$index_data["not_access_group_name"].'], '; //　設計書ではグレーアウトされてた。executeと処理違う
        $index_json .= '"access_role_id": "'.$index_data["access_role_id"].'", ';
        $index_json .= '"access_role_name": ['.$index_data["access_role_name"].'], ';
        $index_json .= '"not_access_role_id": "'.$index_data["not_access_role_id"].'", ';
        $index_json .= '"not_access_role_name": ['.$index_data["not_access_role_name"].'], ';
        $index_json .= '"room_auth_moderate": "'.$index_data["room_auth_moderate"].'", ';
        $index_json .= '"room_auth_general": "'.$index_data["room_auth_general"].'", ';
        $index_json .= '"acl_role_id": "'.$index_data["acl_role_id"].'", ';
        $index_json .= '"acl_role_name": ['.$index_data["acl_role_name"].'], ';
        $index_json .= '"exclusive_acl_role_id": "'.$index_data["exclusive_acl_role_id"].'", ';
        $index_json .= '"exclusive_acl_role_name": ['.$index_data["exclusive_acl_role_name"].'], ';
        $index_json .= '"acl_group_id": "'.$index_data["acl_group_id"].'", ';
        $index_json .= '"acl_group_name": ['.$index_data["acl_group_name"].'], ';
        $index_json .= '"exclusive_acl_group_id": "'.$index_data["exclusive_acl_group_id"].'", ';
        $index_json .= '"exclusive_acl_group_name": ['.$index_data["exclusive_acl_group_name"].'], ';
        $index_json .= '"acl_room_auth_moderate": "'.$index_data["acl_room_auth_moderate"].'",';
        $index_json .= '"acl_room_auth_general": "'.$index_data["acl_room_auth_general"].'",';
        $index_json .= '"acl_room_auth_guest": "'.$index_data["acl_room_auth_guest"].'",';
        $index_json .= '"acl_room_auth_logout": "'.$index_data["acl_room_auth_logout"].'",';
        $index_json .= '"acl_user_auth_id": "'.$index_data["acl_user_auth_id"].'",';
        $index_json .= '"exclusive_acl_user_auth_id": "'.$index_data["exclusive_acl_user_auth_id"].'",';
        $index_json .= '"opensearch_uri": "'.BASE_URL.'/?action=repository_opensearch&index_id='.$index_data["index_id"].'",';
        if($index_data["create_cover_flag"] == "1" && $index_data["index_id"] != "0") {
            $create_cover_flag = "true";
        } else {
            $create_cover_flag = "false";
        }
        $index_json .= '"create_cover_flag": "'.$create_cover_flag.'",';
        $index_json .= '"owner_user_id": "'.$index_data["owner_user_id"].'", ';
        if($index_data["harvest_public_state"] == "1" && $index_data["index_id"] != "0") {
            $harvest_public_state = "true";
        } else {
            $harvest_public_state = "false";
        }
        $index_json .= '"harvest_public_state": "'.$harvest_public_state.'", ';
        if(!isset($index_data["thumbnail"])) {
            $index_data["thumbnail"] = "";
        }
        $index_json .= '"thumbnail": "'.$index_data["thumbnail"].'", ';
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --start--
        $index_json .= '"online_issn": "'.$index_data["online_issn"].'", ';
        $index_json .= '"biblio_flag": "'.$index_data["biblio_flag"].'"';
        // Add issn and biblio flag 2014/04/16 T.Ichikawa --end--
        $index_json .= '}';

        $repositoryDownload->download($index_json, "index_json.txt");
    }
    // Add new prefix id 2013/12/24 T.Ichikawa --end--
    
    // Add change view authority flag 2015/03/10 T.Ichikawa --start--
    /**
     * 閲覧権限が変更されたかチェックする
     * 
     * @param int $indexId             インデックスID
     * @param int $exclusiveRoleId     除外閲覧権限ID
     * @param int $exclusiveRoomAuth   除外ルーム権限
     * @param string $exclusiveGroupId 除外グループID
     * @return bool
     */
    private function checkChangeBrowsingAuthority($indexId, $exclusiveRoleId, $exclusiveRoomAuth, $exclusiveGroupId)
    {
        // 閲覧除外権限情報の取得
        $query = "SELECT exclusive_acl_role, exclusive_acl_group ".
                 "FROM ".DATABASE_PREFIX. "repository_index ".
                 "WHERE index_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $indexId;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            // 何か起きたら処理しない
            return false;
        }
        
        // ベース権限とルーム権限に分ける
        $roles = explode("|", $result[0]["exclusive_acl_role"]);
        $exclusive_acl_role_id = $roles[0];
        $exclusive_acl_room_auth = $roles[1];
        
        // 画面上で設定した値とDBの値が違う場合はtrueを返す
        // ベース権限の変更チェック
        if($exclusive_acl_role_id != $exclusiveRoleId) {
            return true;
        }
        // ルーム権限の変更チェック
        if($exclusive_acl_room_auth != $exclusiveRoomAuth) {
            return true;
        }
        
        // 画面上で設定した除外グループ権限
        $exclusiveGroupIds = explode(",", $exclusiveGroupId);
        // DBに保存された除外グループ権限
        $exclusive_acl_group_id = explode(",", $result[0]["exclusive_acl_group"]);
        // 除外グループ数が違う場合は変更があったという事なのでtrueを返す
        if(count($exclusive_acl_group_id) != count($exclusiveGroupIds)) {
            return true;
        } else {
            for($ii = 0; $ii < count($exclusive_acl_group_id); $ii++) {
                // DBの値と除外グループ配列の値をチェックして、一致しない値がある場合はtrueを返す
                if(!in_array($exclusive_acl_group_id[$ii], $exclusiveGroupIds)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 指定インデックスの公開状況をチェックする
     *
     * @param  int $index_id インデックスID
     * @return string "public" "private"
     */
    private function checkIndexState($index_id) {
        // インデックス自身の公開状況取得
        $query = "SELECT public_state ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        // インデックス自身の公開フラグのチェック
        if($result[0]["public_state"] == 0) {
            return "private";
        }
        
        // ユーザー閲覧権限取得
        $query = "SELECT exclusive_acl_role_id, exclusive_acl_room_auth, public_state ".
                 "FROM ". DATABASE_PREFIX. "repository_index_browsing_authority ".
                 "WHERE index_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $index_id;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        // インデックス閲覧権限のチェック
        // なんらかの権限情報が存在する場合
        if(count($result) > 0) {
            if($result[0]["public_state"] == 0 ||          // 公開フラグが非公開状態
               $result[0]["exclusive_acl_role_id"] > 0 ||  // 閲覧ベース権限に制限がかかっている
               $result[0]["exclusive_acl_room_auth"] > -1) // 閲覧ルーム権限に制限がかかっている
            {
                return "private";
            }
        }
        
        // 閲覧グループ権限取得
        $query = "SELECT exclusive_acl_group_id ".
                 "FROM ". DATABASE_PREFIX. "repository_index_browsing_groups ".
                 "WHERE index_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $index_id;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            return false;
        }
        // インデックス閲覧権限のチェック
        // なんらかの権限情報が存在する場合
        if(count($result) > 0) {
            for($ii=0; $ii < count($result); $ii++) {
                // 「非会員」のグループ権限が除外権限に設定されている場合
                if($result[$ii]["exclusive_acl_group_id"] == 0) {
                    return "private";
                }
            }
        }
        
        return "public";
    }
    // Add change view authority flag 2015/03/10 T.Ichikawa --end--
}

?>
