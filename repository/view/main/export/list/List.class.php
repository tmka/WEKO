<?php
// --------------------------------------------------------------------
//
// $Id: List.class.php 22783 2013-05-22 02:32:04Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Export_List extends RepositoryAction
{
    // Components
    var $Session = null;
    
    // member
    var $size_over_msg = "";
    var $count_over_msg = "";
    var $max_export_count_msg = "";
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        //$this->setLangResource();
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        if($smartyAssign == null || !isset($smartyAssign))
        {
            $this->setLangResource();
            $smartyAssign = $this->Session->getParameter("smartyAssign");
        }
        
        if($this->Session->getParameter("size_over")==true){
            $this->size_over_msg = $smartyAssign->getLang("repository_export_file_size_over");
        }
        
        if($this->Session->getParameter("count_over")==true){
            $this->count_over_msg = $smartyAssign->getLang("repository_export_item_count_over");
        }
        
        if(($this->size_over_msg!=null || $this->count_over_msg!=null) && $this->Session->getParameter("max_export_count")!=null){
            $this->max_export_count_msg = sprintf($smartyAssign->getLang("repository_export_no_export_over_items"), $this->Session->getParameter("max_export_count")+1);
        }

        $this->Session->removeParameter("size_over");
        $this->Session->removeParameter("count_over");
        $this->Session->removeParameter("max_export_count");
        $this->Session->removeParameter("export_print");
        
        return 'success';
    }
}
?>
