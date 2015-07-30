<?php
// --------------------------------------------------------------------
//
// $Id: Report.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';

/**
 * Make log report
 *
 * @package	 NetCommons
 * @author	  Y.Nakao(IVIS)
 * @copyright   2006-2009 NetCommons Project
 * @license	 http://www.netcommons.org/license.txt  NetCommons License
 * @project	 NetCommons Project, supported by National Institute of Informatics
 * @access	  public
 */
class Repository_Action_Edit_Log_Report extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	
	// member
	var $address = null; 
	
	function execute()
	{
		try {
			// -----------------------------------------------
			// init
			// -----------------------------------------------
			// start action
			$result = $this->initAction();
			if ( $result === false ) {
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
				$DetailMsg = null;
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );
				$this->failTrans();
				throw $exception;
			}
			
			// -----------------------------------------------
			// get lang resource
			// -----------------------------------------------
			$this->setLangResource();
			$smarty = $this->Session->getParameter("smartyAssign");
			
			// -----------------------------------------------
			// check mail address
			// -----------------------------------------------
			$this->address = str_replace("\r\n", "\n", $this->address);
			$add = array();
			$add = explode("\n", $this->address);
			$this->address = "";
			for($ii=0; $ii<count($add); $ii++){
				if(strlen($add[$ii]) > 0){
					if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $add[$ii])){
						$this->address .= $add[$ii]."\n"; 
					}
				}
			}
			
			// -----------------------------------------------
			// entry report send mail address
			// -----------------------------------------------
			$query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
					" SET param_value = ?, ".
					" mod_user_id = ? ".
					" WHERE param_name = ?; ";
			$params = array();
			$params[] = $this->address;
			$params[] = $this->Session->getParameter("_user_id");
			$params[] = "log_report_mail";
			$result = $this->Db->execute($query, $params);
			if($result === false){
				echo "";
				exit();
			}
			
			// -----------------------------------------------
			// end action
			// -----------------------------------------------
			$result = $this->exitAction();
			if ( $result == false ){
				echo "";
				exit();
			}
			
			echo $this->address;
			exit();
			
		}
		catch ( RepositoryException $Exception) {
			$this->logFile(
				"SampleAction",
				"execute",
				$Exception->getCode(),
				$Exception->getMessage(),
				$Exception->getDetailMsg() );
			$this->exitAction();
			return "error";
		}
	}
}
?>
