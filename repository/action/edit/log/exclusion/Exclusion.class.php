<?php
// --------------------------------------------------------------------
//
// $Id: Exclusion.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';

/**
 * log result action
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Log_Exclusion extends WekoAction
{
    /**
     * additional exclude ip address
     *
     * @var string
     *         ex) 172.17.72.110,172.17.72.111
     */
    public $log_exclusion = null;

    /**
     * add excluded Ip Address List by request parameter
     *
     */
    function executeApp()
    {
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        $logManager->addExcludedIpAddrToDatabase($this->log_exclusion);
        
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        echo $smartyAssign->getLang("repository_log_update_exclude_address"). 
             "\n". 
             $smartyAssign->getLang("repository_log_announce_update_clowler_list");
	}
}
?>
