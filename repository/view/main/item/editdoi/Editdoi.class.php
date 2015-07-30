<?php
// --------------------------------------------------------------------
//
// $Id: Editdoi.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Editdoi extends RepositoryAction
{
    public $help_icon_display =  "";
    
    public $displays_doi_grant = false;
    public $doi_status = 0;
    
    /**
     * registed doi
     * 0:not registed
     * 1:jalcdoi registed
     * 2:crossref registed
     */
    public $registed_doi = 0;
    
    /**
     * registed library jalcdoi
     * 0:not registed
     * 1:registed
     */
    public $registed_library_jalcdoi = 0;
    
    /**
     * prefix of JaLC DOI
     *
     * @var string
     */
    public $prefix_jalcdoi = null;
    
    /**
     * prefix of Cross Ref
     *
     * @var string
     */
    public $prefix_crossref = null;
    
    /**
     * prefix of Library JaLC DOI
     *
     * @var string
     */
    public $prefix_library_jalcdoi = null;
    
    /**
     * suffix
     *
     * @var string
     */
    public $suffix = null;
    
    /**
     * when drop doi, it going to be 'true'
     *
     * @var string
     */
    public $drop_doi = null;
    
    /**
     * 0:new item
     * 1:edit item
     * 
     * @var string
     */
    public $edit_flag = 0;
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeForWeko()
    {
        $item_id = intval($this->Session->getParameter("edit_item_id"));
        $item_no = intval($this->Session->getParameter("edit_item_no"));
        
        // DOI付与可能かどうかをチェック
        //$DATE = new Date();
        //$this->TransStartDate = $DATE->getDate().".000";
        $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        if($this->drop_doi == 'true')
        {
            $repositoryHandleManager->dropDoiSuffix($item_id, $item_no);
        }
        $displays_jalcdoi_grant = $CheckDoi->checkDoiGrant(
            $item_id, $item_no, Repository_Components_Checkdoi::TYPE_JALC_DOI);
        $displays_crossref_grant = $CheckDoi->checkDoiGrant(
            $item_id, $item_no, Repository_Components_Checkdoi::TYPE_CROSS_REF);
        $displays_library_jalcdoi_grant = $CheckDoi->checkDoiGrant(
            $item_id, $item_no, Repository_Components_Checkdoi::TYPE_LIBRARY_JALC_DOI);
        if($displays_jalcdoi_grant)
        {
            $this->prefix_jalcdoi = $repositoryHandleManager->getJalcDoiPrefix();
            $this->suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
            $this->displays_doi_grant = true;
        }
        if($displays_crossref_grant)
        {
            $this->prefix_crossref = $repositoryHandleManager->getCrossRefPrefix();
            $this->suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
            $this->displays_doi_grant = true;
        }
        if($displays_library_jalcdoi_grant)
        {
            $this->prefix_library_jalcdoi = $repositoryHandleManager->getLibraryJalcDoiPrefix();
            $this->suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
            $this->displays_doi_grant = true;
        }
        $this->doi_status = $CheckDoi->getDoiStatus($item_id, $item_no);
        if($this->doi_status == 1)
        {
            $jalcdoi_suffix = $repositoryHandleManager->getJalcdoiSuffix($item_id, $item_no);
            if(strlen($jalcdoi_suffix) > 0)
            {
                $this->registed_doi = 1;
            }
            else
            {
                $this->registed_doi = 2;
            }
            
            $library_jalcdoi_suffix = $repositoryHandleManager->getLibraryJalcdoiSuffix($item_id, $item_no);
            if(strlen($library_jalcdoi_suffix) > 0)
            {
                $this->registed_library_jalcdoi = 1;
            }
            else
            {
                $this->registed_library_jalcdoi = 0;
            }
        }
        
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
        $uri = $repositoryHandleManager->getSubstanceUri($item_id, $item_no);
        if(strlen($uri) == 0)
        {
            $this->edit_flag = 0;
        }
        else
        {
            $this->edit_flag = 1;
        }
        // $this->edit_flag = $this->Session->getParameter("edit_flag");
        
        // DOI付与画面を通過したことを示すフラグをセッションに保存
        $this->Session->removeParameter("edit_jalc_flag");
        $this->Session->setParameter("edit_jalc_flag", "true");
        
        return 'success';
    }
}
?>
