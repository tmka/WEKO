<?php
// --------------------------------------------------------------------
//
// $Id: Tree.class.php 20897 2013-01-11 07:26:13Z ayumi_jin $
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
require_once WEBAPP_DIR. '/modules/repository/action/edit/tree/Tree.class.php';

class Repository_Action_Main_Privatetree extends RepositoryAction
{

    // component
    public $Session = null;
    public $Db = null;
    public $uploadsView = null;
    
    // request parameter
    public $edit_id = null;         // click index ID
    public $edit_mode = null;           // edit mode
                                    // '' : select edit index
                                    // 'insert' : make new index
                                    // 'update' : edit index
                                    // 'delete' : delete index
                                    // 'sort' : sort index
    // request parameter for now edit index data
    public $name_jp = null;             // now edit index japanese name
    public $name_en = null;             // now edit index english name
    public $comment = null;             // now edit index comment
    public $pid = null;                 // now edit index parent_index_id
    public $show_order= null;               // now edit index show order
    public $pub_chk = null;             // now edit index pub flg
    public $pub_year = null;                // now edit index pub year
    public $pub_month = null;               // now edit index pub month
    public $pub_day = null;             // now edit index pub day 
    public $access_group_ids = null;        // now edit index entry item group id
    public $not_access_group_ids = null;    // now edit index not entry item group id
    public $access_role_ids = null;     // now edit index entry item auth id
    public $not_access_role_ids = null; // now edit index not entry item auth id
    public $mod_date = null;                // now edit index mod date
    public $drag_id = null;             // drag index id at drag ivent
    public $drop_id = null;             // drop index id at drop ivent
    public $drop_index = null;              // true  : index drop in index
                                        // false : index drop in sentry
    // Add child index display more 2009/01/16 Y.Nakao --start--
    public $display_more = null;            // first display child index show all or a little
    public $display_more_num = null;        // first display child index num
    // Add child index display more 2009/01/16 Y.Nakao --end--
    
    public $rss_display = null;         // RSS icon display
    
    // Add config management authority 2010/02/23 Y.Nakao --start--
    public $access_role_room = null;        // now edit index access OK room authority
    // Add config management authority 2010/02/23 Y.Nakao --end--
    
    // Add contents page 2010/08/06 Y.Nakao --start--
    public $display_type = null;
    // Add contents page 2010/08/06 Y.Nakao --end--
    
    // Add index list 2011/4/5 S.Abe --start--
    public $select_index_list_display = null;
    public $select_index_list_name = null;
    public $select_index_list_name_english = null;
    // Add index list 2011/4/5 S.Abe --end--

    public $smartyAssign = null;

    // Add index thumbnail 2010/08/20 Y.Nakao --start--
    public $thumbnail_del = null;
    // Add index thumbnail 2010/08/20 Y.Nakao --end--
    
    // Add tree access control list 2012/02/22 T.Koyasu -start-
    public $exclusiveAclRoleIds = null;
    public $exclusiveAclRoomAuth = null;
    public $exclusiveAclGroupIds = null;
    // Add tree access control list 2012/02/22 T.Koyasu -end-
        
    public $create_cover_flag = null;
    // Add harvest public flag 2013/07/05 K.Matsuo --start--
    public $harvest_public_state = null;
    // Add harvest public flag 2013/07/05 K.Matsuo --end--
    function execute()
    {
        // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --start--
        $this->validatorPrivateTree();
        // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --end--
        
        $treeInstance = new Repository_Action_Edit_Tree();
        $treeInstance->Session = $this->Session;
        $treeInstance->Db = $this->Db;
        $treeInstance->edit_id = $this->edit_id;
        $treeInstance->edit_mode = $this->edit_mode;
        $treeInstance->name_jp = $this->name_jp;
        $treeInstance->name_en = $this->name_en;
        $treeInstance->comment = $this->comment;
        $treeInstance->pid = $this->pid;
        $treeInstance->show_order = $this->show_order;
        $treeInstance->pub_chk = $this->pub_chk;
        $treeInstance->pub_year = $this->pub_year;
        $treeInstance->pub_month = $this->pub_month;
        $treeInstance->pub_day = $this->pub_day;
        $treeInstance->access_group_ids = $this->access_group_ids;
        $treeInstance->not_access_group_ids = $this->not_access_group_ids;
        $treeInstance->access_role_ids = $this->access_role_ids;
        $treeInstance->not_access_role_ids = $this->not_access_role_ids;
        $treeInstance->mod_date = $this->mod_date;
        $treeInstance->drag_id = $this->drag_id;
        $treeInstance->drop_id = $this->drop_id;
        $treeInstance->drop_index = $this->drop_index;          // false : index drop in sentry
        $treeInstance->display_more = $this->display_more;
        $treeInstance->display_more_num = $this->display_more_num;
        $treeInstance->rss_display = $this->rss_display;
        $treeInstance->access_role_room = $this->access_role_room;
        $treeInstance->display_type = $this->display_type;
        $treeInstance->select_index_list_display = $this->select_index_list_display;
        $treeInstance->select_index_list_name = $this->select_index_list_name;
        $treeInstance->select_index_list_name_english = $this->select_index_list_name_english;  
        $treeInstance->smartyAssign = $this->smartyAssign;
        $treeInstance->thumbnail_del = $this->thumbnail_del;
        $treeInstance->exclusiveAclRoleIds = $this->exclusiveAclRoleIds;
        $treeInstance->exclusiveAclRoomAuth = $this->exclusiveAclRoomAuth;
        $treeInstance->exclusiveAclGroupIds = $this->exclusiveAclGroupIds;
        $treeInstance->create_cover_flag = $this->create_cover_flag;
        $treeInstance->harvest_public_state = $this->harvest_public_state;
        
        $result = $treeInstance->execute();
        $this->Session->setParameter("redirect_flg", "privatetree_update");
        return $result;
    }
    
    // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --start--
    /**
     * public private tree
     * 
     */
    private function validatorPrivateTree()
    {
        // check published private tree status.
        if(_REPOSITORY_PRIVATETREE_PUBLIC)
        {
            // public_state is ON
            $this->pub_chk = "true";
            
            // set TransStartDate
            $this->initAction();
            
            // pub_date is past date
            $pubDate = $this->pub_year.$this->pub_month.$this->pub_day;
            $nowDate = substr($this->TransStartDate, 0, 10);
            $nowDate = str_replace("-", "", $nowDate);
            if($pubDate > $nowDate)
            {
                // when pub_date is past date, set now_date in pub_date.
                $this->pub_year  = substr($this->TransStartDate, 0, 4);
                $this->pub_month = substr($this->TransStartDate, 5, 2);
                $this->pub_day   = substr($this->TransStartDate, 7, 2);
            }
            
            // exit database transe
            $this->exitAction();
            
        }
    }
    // Add specialized support for open.repo "Be published private tree" Y.Nakao 2013/06/21 --end--
}

?>
