<?php
// --------------------------------------------------------------------
//
// $Id: Factory.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';
include_once WEBAPP_DIR. '/modules/repository/files/pear/Date.php';
require_once WEBAPP_DIR. '/modules/repository/components/Swordmanager.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputTSV.class.php';

class Repository_Components_Factory
{
    
    /**
     * get component
     *
     * @param var $Session Session
     * @param var $db DbObjectAdodb or RepositoryDbAccess
     * @param string $TransStartDate TransStartDate
     */
    public static function getComponent($entryName)
    {
        $instance = null;
        
        $container =& DIContainerFactory::getContainer();
        $Db =& $container->getComponent("DbObject");
        $Session =& $container->getComponent("Session");
        $DATE = new Date();
        $TransStartDate = $DATE->getDate().".000";
        
        switch($entryName)
        {
            case 'Repository_Components_Swordmanager':
                $instance = new Repository_Components_Swordmanager($Session, $Db, $TransStartDate);
                break;
            case 'ExportCommon':
                $instance = new ExportCommon($Db, $Session, $TransStartDate);
                break;
            case 'RepositoryOutputTSV':
                $instance = new RepositoryOutputTSV($Db, $Session);
                break;
            case 'Repository_Components_Loganalyzor':
                require_once WEBAPP_DIR. '/modules/repository/components/LogAnalyzor.class.php';
                $instance = new Repository_Components_Loganalyzor($Session, $Db, $TransStartDate);
                break;
            default:
                break;
        }
        return $instance;
    }
}
?>
