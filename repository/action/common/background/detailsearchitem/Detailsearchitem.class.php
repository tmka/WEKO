<?php
// --------------------------------------------------------------------
//
// $Id: Detailsearchitem.class.php 48595 2015-02-18 08:36:51Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/BackgroundProcess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * Make detail search item in background process
 *
 * @package     NetCommons
 * @author      K.Sugimoto(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Background_Detailsearchitem extends BackgroundProcess
{

    const PARAM_NAME = "Repository_Action_Common_Background_Detailsearchitem";
    
    /**
     * constructer
     */
    public function __construct()
    {
        parent::__construct(self::PARAM_NAME);
    }
    
    /**
     * get search log added to detail search item
     *
     * @param Array $logNoList
     */
    protected function prepareBackgroundProcess(&$endNo) {
    	if(isset($_GET['log_no']) && intval($_GET['log_no']) > 0)
    	{
    		$startNo = $_GET['log_no'];
    	}
    	else
    	{
    		$startNo = 1;
    	}
    	
        $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        
        $ret = $logManager->isInsertDetailSearchAndCalcInsertLog($startNo, $endNo);
    	
    	return $ret;
    }
    
    /** 
     * 
     * execute background process
     * 
     * @param Array $logNoList
     */
    protected function executeBackgroundProcess($endNo) {
        AppLogger::infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
        $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
        $logManager->addDetailSearchItem();
        
        $_GET['log_no'] = $endNo + 1;
    }
}
?>