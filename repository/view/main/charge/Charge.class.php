<?php
// --------------------------------------------------------------------
//
// $Id: Charge.class.php 41863 2014-09-22 08:45:13Z yuko_nakao $
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
 * view charge list
 *
 * @package     WEKO
 * @access      public
 */
class Repository_View_Main_Charge extends RepositoryAction
{
    // component
    var $Session = null;
    var $Db = null;
    
    // member
    var $charge_url = "";
    
    function execute()
    {
        
        // Modify charge list 2011/11/09 Y.Nakao --start--
        
        // start action
        $this->initAction();
        
        // Modify Price method move validator K.Matsuo 2011/10/18 --start--
        require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
        $validator = new Repository_Validator_DownloadCheck();
        $initResult = $validator->setComponents($this->Session, $this->Db);
        if($initResult === 'error'){
            return 'error';
        }
        $charge_pass = $validator->getChargePass();
        // Modify Price method move validator K.Matsuo 2011/10/18 --end--
        $this->charge_url = "https://".$charge_pass["charge_fqdn"]."/weko-usage/list/".$charge_pass["sys_id"]."/";
        
        // end action
        $result = $this->exitAction();
        if ( $result === false ) {
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );
            throw $exception;
        }
        
        return 'success';
        
    }
}
?>
