<?php
// --------------------------------------------------------------------
//
// $Id: Usagestatistics.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
 * Usage statistics
 *
 * @package     NetCommons
 * @author      A.Suzuki(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Usagestatistics extends RepositoryAction
{
    // Request parameter
    /**
     * login_id
     *
     * @var string
     */
    public $login_id = null;
    /**
     * password
     *
     * @var string
     */
    public $password = null;
    
    // Member
    /**
     * user_authority_id
     *
     * @var int
     */
    public $user_authority_id = "";
    /**
     * authority_id
     *
     * @var int
     */
    public $authority_id = "";
    /**
     * user_id
     *
     * @var string
     */
    public $user_id = "";
    
    /**
     * Execute
     *
     * @return string
     */
    public function executeApp()
    {
        // Init action
        $result = $this->initAction();
        if ($result === false) {
            $this->errorLog("Failed initAction", __FILE__, __CLASS__, __LINE__);
            throw new AppException("Failed to execute.");
        }
        
        // check execute authority
        if(!$this->checkExecuteAuthority()) {
            $this->infoLog("failed to login for usagestatistics", __FILE__, __CLASS__, __LINE__);
            print("Login is failed.\n");
            return "error";
        }
        
        // Aggregate usage statistics
        $this->infoLog("businessUsagestatistics", __FILE__, __CLASS__, __LINE__);
        $usageStatistics = BusinessFactory::getFactory()->getBusiness("businessUsagestatistics");
        if(!$usageStatistics->aggregateUsagestatistics()) {
            $this->errorLog("", __FILE__, __CLASS__, __LINE__);
            print("Update usage statistics is failed.\n");
            throw new AppException("Failed to usage statistics update");
        }
        
        // finalize
        $this->exitAction();
        print("Successfully updated.\n");
        return "success";
    }
    
    /**
     * check execute authority
     *
     * @return bool
     */
    private function checkExecuteAuthority() {
        // Init user authorities
        $this->user_authority_id = "";
        $this->authority_id = "";
        $this->user_id = "";
        
        // Check login
        $result = null;
        $error_msg = null;
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false) {
            print("Incorrect Login!\n");
            return false;
        }
        
        // Check user authority id
        if($this->user_authority_id < $this->repository_admin_base || 
           $this->authority_id < $this->repository_admin_room) {
            print("You do not have permission to update.\n");
            return false;
        }
        
        return true;
    }
}
?>