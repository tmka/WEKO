<?php
// --------------------------------------------------------------------
//
// $Id: Usagestatistics.class.php 18959 2012-08-07 05:03:29Z atsushi_suzuki $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatistics.class.php';

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
    public function execute()
    {
        try {
            // Init action
            $result = $this->initAction();
            if ( $result === false ) {
                $this->failTrans();
                $this->exitAction();
                return "error";
            }
            
            // Init user authorities
            $this->user_authority_id = "";
            $this->authority_id = "";
            $this->user_id = "";
            
            // Check login
            $result = null;
            $error_msg = null;
            $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
            if($return == false){
                $this->failTrans();
                $this->exitAction();
                print("Incorrect Login!\n");
                return "error";
            }
            
            // Check user authority id
            if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
                $this->failTrans();
                $this->exitAction();
                print("You do not have permission to update.\n");
                return "error";
            }
            
            // Aggregate usage statistics
            $RepositoryUsagestatistics = new RepositoryUsagestatistics($this->Session, $this->Db, $this->TransStartDate, $this->user_id);
            if(!$RepositoryUsagestatistics->aggregateUsagestatistics())
            {
                $this->failTrans();
                $this->exitAction();
                print("Update usage statistics is failed.\n");
                return "error";
            }
            
            // finalize
            $this->exitAction();
            print("Successfully updated.\n");
            return "success";
        }
        catch (RepositoryException $Exception)
        {
            $this->failTrans();
            $this->exitAction();
            return "error";
        }
    }
}
?>