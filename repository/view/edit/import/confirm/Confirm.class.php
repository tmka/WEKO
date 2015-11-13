<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 48455 2015-02-16 10:53:40Z atsushi_suzuki $
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

/**
 * [[view import result]]
 *
 * @access      public
 */
class Repository_View_Edit_Import_Confirm extends RepositoryAction
{
    // component 
    var $Session = null;
    // member
    var $error_msg = null;
    
    // Add e-person 2013/12/04 R.Matsuura --start--
    /**
     * import mode
     */
    public $importmode = null;
    /**
     * authority import success number
     */
    public $successnum = null;
    /**
     * help icon display flag
     */
    public $help_icon_display =  null;
    // Add e-person 2013/12/04 R.Matsuura --end--
    
    /**
     * @access  public
     */
    function executeApp()
    {
        $this->error_msg = $this->Session->getParameter("error_msg");
        $this->Session->removeParameter("error_msg");
        $this->importmode = $this->Session->getParameter("importmode");
        $this->Session->removeParameter("importmode");
        $this->successnum = $this->Session->getParameter("successnum");
        $this->Session->removeParameter("successnum");
        
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
        return 'success';
    }
}
?>
