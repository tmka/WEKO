<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 36236 2014-05-26 07:53:04Z satoshi_arata $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Prefix_Confirm extends RepositoryAction
{
	// component 
	var $Session = null;
	var $Db = null;
	
	var $prefix = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        /////////////////////////////////
        // get PrefixID from DB
        /////////////////////////////////
        
        $repositoryDbAccess = new RepositoryDbAccess($this->Db);
        
        // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --start--
        $DATE = new Date();
        $this->TransStartDate = $DATE->getDate(). ".000";
        // Bug fix WEKO-2014-006 2014/04/28 T.Koyasu --end--
        
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $repositoryDbAccess, $this->TransStartDate);
        
        $this->prefix = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        
        return 'success';
    }
}
?>
