<?php
// --------------------------------------------------------------------
//
// $Id: Getpublicitem.class.php 42829 2014-10-09 08:51:27Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
 
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAggregateCalculation.class.php';

/**
 * Getpublicitem action
 */
class Repository_Action_Common_Getpublicitem extends RepositoryAction
{
    //----------------------------
    // Request parameters
    //----------------------------
    /**
     * login_id
     *
     * @var string
     */
    public $login_id = null;
    /**
     * password to login
     *
     * @var string
     */
    public $password = null;
    /**
     * output format of total items
     *
     * @var string
     */
    public $format = null;
    /**
     * user base auth
     *
     * @var int
     */
    public $user_authority_id = 0;
    /**
     * user room auth
     *
     * @var int
     */
    public $authority_id = 0;
    
    function executeForWeko() {
        // login check
        $result = null;
        $error_msg = null;
        
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        
        if($return == false || $this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
            print("Incorrect Login!\n");
            return false;
        }
        
        // get number of the items
        $repositoryAggregateCalculation = new RepositoryAggregateCalculation($this->Session, $this->dbAccess, $this->TransStartDate);
        
        $items = $repositoryAggregateCalculation->countItem();
        
        // output xml, json or error
        if ( strcasecmp($this->format, "xml") == 0) {
            header("Content-Type: text/xml; charset=utf-8");
            
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>".
                   "<items>".
                   "<total>".$items["total"]."</total>".
                   "<public>".$items["public"]."</public>".
                   "<private>".$items["private"]."</private>".
                   "<includeFulltext>".$items["includeFulltext"]."</includeFulltext>".
                   "<excludeFulltext>".$items["excludeFulltext"]."</excludeFulltext>".
                   "</items>";
            
            echo $xml;
        } else if ( strcasecmp($this->format, "JSON") == 0 ) {
            $json = "{ ".
                    "\"items\" : { ".
                    "\"total\" : ".$items["total"].", ".
                    "\"public\" : ".$items["public"].", ".
                    "\"private\" : ".$items["private"].", ".
                    "\"includeFulltext\" : ".$items["includeFulltext"].", ".
                    "\"excludeFulltext\" : ".$items["excludeFulltext"].
                    "} ".
                    "}";
            
            echo $json;
        } else {
            print("Incorrect Request Parameter!\n");
            return false;
        }
        
        $this->exitAction();
        exit();
    }
}
?>
