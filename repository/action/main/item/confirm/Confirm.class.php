<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Confirm extends RepositoryAction
{
    // JaLC DOI
    public $entry_jalcdoi_checkbox = null;
    public $entry_jalcdoi_hidden = null;
    public $entry_crossref_checkbox = null;
    public $entry_crossref_hidden = null;
    public $entry_library_jalcdoi_text = null;
    public $entry_library_jalcdoi_hidden = null;
    
    public $save_mode = null;      // 'stay' : save
                                // 'next' : go next page
    // Add registered info save action 2009/02/13 Y.Nakao --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    public function executeForWeko()
    {                        
            $item_id = intval($this->Session->getParameter("edit_item_id"));
            $item_no = intval($this->Session->getParameter("edit_item_no"));
            
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
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
            
            if($this->save_mode == "stay")
            {
                return 'stay';
            }
            else if($this->save_mode == "next")
            {
                return 'success';
            }
            else
            {
                return 'error';
            }
    }
}
?>
