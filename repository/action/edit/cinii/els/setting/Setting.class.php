<?php
// --------------------------------------------------------------------
//
// $Id: Setting.class.php 945 2010-07-05 06:09:05Z atsushi_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

class Repository_Action_Edit_Cinii_Els_Setting extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	
	var $els_login_id = null;
	var $auto_entry = null;
	var $path_ssh = null;
	var $path_scp = null;
	
	/**
	 *
	 * @access  public
	 */
	function execute()
	{
		try {
			$result = $this->initAction();
	        if ( $result === false ) {
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
	            $DetailMsg = null;
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );
	            $this->failTrans(); // ROLLBACK
	            throw $exception;
	        }
	        
	        // get user id
	        $user_id = $this->Session->getParameter("_user_id");
	        // set actib tab
	        $this->Session->setParameter("els_active_tab", 1);
	        
	        // 1. Entry login id for ELS
			$query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
					" SET param_value = ?, ".
					" mod_user_id = ?, ".
					" mod_date = ? ".
					" WHERE param_name = ? ";
			$params = array();
			$params[] = $this->els_login_id;
			$params[] = $user_id;
			$params[] = $this->TransStartDate;
			$params[] = 'els_login_id';
			$result = $this->Db->execute($query, $params);
			if($result === false){
				//error
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_msg", $errMsg);
				return 'error';
			}
			
			// 2. Entry auto els entry is run or not
			if(strlen($this->auto_entry) > 0 && $this->auto_entry=='on'){
				$this->auto_entry = 'true';
			} else {
				$this->auto_entry = 'false';
			}
			$query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
					" SET param_value = ?, ".
					" mod_user_id = ?, ".
					" mod_date = ? ".
					" WHERE param_name = ? ";
			$params = array();
			$params[] = $this->auto_entry;
			$params[] = $user_id;
			$params[] = $this->TransStartDate;
			$params[] = 'els_auto';
			$result = $this->Db->execute($query, $params);
			if($result === false){
				//error
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_msg", $errMsg);
				return 'error';
			}
			
			// 3. Entry SSH command path
			if( strlen($this->path_ssh) > 0 ){
				if($this->path_ssh[strlen($this->path_ssh)-1] != DIRECTORY_SEPARATOR){
					$this->path_ssh .= DIRECTORY_SEPARATOR;
				}
			}
			$query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
					" SET param_value = ?, ".
					" mod_user_id = ?, ".
					" mod_date = ? ".
					" WHERE param_name = ? ";
			$params = array();
			$params[] = $this->path_ssh;
			$params[] = $user_id;
			$params[] = $this->TransStartDate;
			$params[] = 'path_ssh';
			$result = $this->Db->execute($query, $params);
			if($result === false){
				//error
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_msg", $errMsg);
				return 'error';
			}
			
			// 4. Entry SCP command path
			if( strlen($this->path_scp) > 0 ){
				if($this->path_scp[strlen($this->path_scp)-1] != DIRECTORY_SEPARATOR){
					$this->path_scp .= DIRECTORY_SEPARATOR;
				}
			}
			$query = "UPDATE ".DATABASE_PREFIX."repository_parameter ".
					" SET param_value = ?, ".
					" mod_user_id = ?, ".
					" mod_date = ? ".
					" WHERE param_name = ? ";
			$params = array();
			$params[] = $this->path_scp;
			$params[] = $user_id;
			$params[] = $this->TransStartDate;
			$params[] = 'path_scp';
			$result = $this->Db->execute($query, $params);
			if($result === false){
				//error
				$errMsg = $this->Db->ErrorMsg();
				$this->Session->setParameter("error_msg", $errMsg);
				return 'error';
			}
	        
			// commit
			$result = $this->exitAction();
			
	        return 'success';
	        
		}
		catch ( RepositoryException $Exception) {
			//end action
		  	$this->exitAction(); // ROLLBACK
			
			//error
			return 'error';
		}
	}
}
?>
