<?php
// --------------------------------------------------------------------
//
// $Id: Search.class.php 48455 2015-02-16 10:53:40Z atsushi_suzuki $
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
 * Reconstruct index view rights table
 *
 * @package     NetCommons
 * @author      R.Matsuura(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Reconstruction_Search extends RepositoryAction
{
    // request parameter
    var $login_id = null;
    var $password = null;
    
    // user's authority level
    public $user_authority_id = "";
    public $authority_id = '';
    
    function executeApp()
    {
        // check login
        $result = null;
        $error_msg = null;
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false){
            print("Incorrect Login!\n");
            return false;
        }
        
        // check user authority id
        if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
            print("You do not have permission to update.\n");
            return false;
        }
        
        require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->updateSearchTableForAllItem();
        
        print("Successfully updated.\n");
        return 'success';
    }
}
?>