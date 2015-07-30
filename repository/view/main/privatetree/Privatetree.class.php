<?php
// --------------------------------------------------------------------
//
// $Id: Tree.class.php 15374 2012-02-10 12:41:17Z yuko_nakao $
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
require_once WEBAPP_DIR. '/modules/repository/view/edit/tree/Tree.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Privatetree extends RepositoryAction
{
    // member
    public $error_flg = null;
    public $view_popup = null;
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    public $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    public $tree_error_msg = '';
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $this->initAction();
        $this->setConfigAuthority();
        $editTree = new Repository_View_Edit_Tree();
        $editTree->Session = $this->Session;
        $editTree->Db = $this->Db;
        $editTree->error_flg = $this->error_flg;
        $result = $editTree->execute();
        $this->help_icon_display = $editTree->help_icon_display;
        $this->tree_error_msg = $editTree->tree_error_msg;
        $this->view_popup = $editTree->view_popup;
        // プライベートツリーの親インデックスIDの取得
        $makePrivateTree = null;
        $error_msg = null;
        $return = $this->getAdminParam('is_make_privatetree', $makePrivateTree, $error_msg);
        if($return == false){
            return false;
        }
        if($makePrivateTree == 0){
            return "error";
        }
        $parentIndexId = null;
        $error_msg = null;
        $return = $this->getAdminParam('privatetree_parent_indexid', $parentIndexId, $error_msg);
        if($return == false){
            return false;
        }
        
        // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --start--
        // Modify get private tree index id at function
        $privateTreeIndexId = $this->getPrivateTreeIndexId();
        // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --end--
        
        // Add tree access control list 2012/02/29 T.Koyasu -start-
        // get auth level of user
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        
        // get user's room authority
        $auth_id = $this->getRoomAuthorityID();
        // when user is not admin
        if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room) {
            if(!$this->checkParentPublicState($privateTreeIndexId)){
                return "error";
            }
        }
        $this->Session->setParameter("MyPrivateTreeRootId", $privateTreeIndexId);
        $this->exitAction();
        return $result;
    }
   
    /**
     * [[アクション初期化処理]]
     *
     * @access  public
     */
    function initAction()
    {
        try {
            //トランザクション開始日時の取得
            //$this->TransStartDate = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
            $DATE = new Date();
            $this->TransStartDate = $DATE->getDate().".000";
            //トランザクション開始処理
            //$this->Db->StartTrans();
            // Add fix login data 2009/07/31 A.Suzuki --start--
            // ログイン情報復元処理
            //$this->fixSessionData();
            // Add fix login data 2009/07/31 A.Suzuki --end--

            // Add config management authority 2010/02/22 Y.Nakao --start--
            //$this->setConfigAuthority();
            // Add config management authority 2010/02/22 Y.Nakao --end--

            // Add theme_name for image file Y.Nakao 2011/08/03 --start--
            //$this->setThemeName();
            // Add theme_name for image file Y.Nakao 2011/08/03 --end--
            //if(!defined("SHIB_ENABLED")){
                //define("SHIB_ENABLED", 0);
            //}
            // Add shiboleth login 2009/03/17 Y.Nakao --start--
            //$this->shib_login_flg = SHIB_ENABLED;
            // Add shiboleth login 2009/03/17 Y.Nakao --end--

            //if(defined("_REPOSITORY_CINII"))
            //{
            //    $this->Session->setParameter("_repository_cinii", _REPOSITORY_CINII);
            //}

            // Add smart phone support T.Koyasu 2012/04/02 -start-
            //if(isset($_SERVER['HTTP_USER_AGENT']))
            //{
            //   $userAgent = $_SERVER['HTTP_USER_AGENT'];
            //    if(preg_match('/Android..*Mobile|iPhone|IEMobile/', $userAgent) > 0){
            //        $this->smartphoneFlg = true;
            //    }
            //}
            // Add smart phone support T.Koyasu 2012/04/02 -end-

            // Add Correspond OpenDepo S.Arata 2013/12/20 --start--
            //if(_REPOSITORY_SMART_PHONE_DISPLAY)
            //{
            //    $this->smartphoneFlg = true;
            //}

            // Add Correspond OpenDepo S.Arata 2013/12/20 --end--

            // Add addin Y.Nakao 2012/07/27 --start--
            //if(get_class($this)!='RepositoryAction' && !isset($this->addin))
            //{
            //    $this->addin = new RepositoryAddinCaller($this);
            //}
            // Add addin Y.Nakao 2012/07/27 --end--
            // Add private tree K.Matsuo 2013/04/08
            //$this->createPrivateTree();

            // Add database access class R.Matsuura 2013/11/12 --start--
            $this->dbAccess = new RepositoryDbAccess($this->Db);
            // Add database access class R.Matsuura 2013/11/12 --end--

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }
    
    /**
     * [[アクション終了処理]]
     *
     * @access  public
     */
    function exitAction()
    {
        try {
            //トランザクション終了処理
            //$this->Db->CompleteTrans();

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }
}
?>
