<?php
// --------------------------------------------------------------------
//
// $Id: Logdeletecancel.class.php 51725 2015-04-07 09:33:19Z shota_suzuki $
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

class Repository_Action_Edit_Logdeletecancel extends RepositoryAction
{
    /**
     * @access  public
     */
    public function executeApp()
    {
        $this->infoLog("businessRobotlistbase", __FILE__, __CLASS__, __LINE__);
        $businessRobotlistbase = BusinessFactory::getFactory()->getBusiness("businessRobotlistbase");
        
        $businessRobotlistbase->unlockRobotListTable();
        
        return "success";
    }
}
?>
