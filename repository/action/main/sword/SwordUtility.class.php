<?php
// --------------------------------------------------------------------
//
// $Id: SwordUtility.class.php 24783 2013-08-20 07:31:46Z yuko_nakao $
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

// Add xml escape string 2008/10/15 Y.Nakao --start--
/**
 * SWORD for WEKO Utility
 */
class SwordUtility extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	var $TransStartDate = null;
	
	function SwordUtility($session, $db, $ins_date){
		if($session!=null){
			$this->Session = $session;
		}
		if($db!=null){
			$this->Db = $db;
		}
		if($ins_date!=null){
			$this->TransStartDate = $ins_date;
		}
	}


	// Generate SWORD element for WEKO
    // 2008/11/05 S.Kawasaki
    function generateSwordElements($element, &$error_msg){
    	$value = '';
    	// switch by element name
    	switch ($element) {
    		case 'sword:level':				// Server Level
    			$value = '1';
    			break;
    		case 'sword:noOp':				// noOp Supported
    			$value = 'false';
    			break;
    		case 'sword:verbose':			// Verbose Supported
    			$value = 'false';
    			break;
//  		case 'repository_name':			// Repository Name
//    			$value = 'false';
//    			break;
    		default:
    			break;
    	}
    	return $value;
    }
    
    // Get SWORD Collections for WEKO
    // 2008/11/05 S.Kawasaki
    function getSwordCollections(){
    	$collections = array();
    	// 2008/11/05 0-th : for WEKO Import
    	array_push($collections, BASE_URL .'/weko/sword/deposit.php');
    	return $collections;
    }
    
    // Get SWORD Workspace for WEKO
	// $workspace['workspace']       : Repository Common Information
	// $workspace['collections'][ii] : ii-th Collection Information
	// 2008/11/10 S.Kawasaki
	function getSwordWorkspace(&$workspace,&$Error_Msg)
	{
		$workspace = array();	// output value, workspace
		$root = array();
		$collections = array();	// output value, collection * N
		// get common params.
		// -Repository Name
		$query = "SELECT param_value ".				// parameter value
				 "FROM ". DATABASE_PREFIX ."repository_parameter ".		// WEKO parameter table
				 "WHERE param_name = ? AND ".			// parameter name
				 "	  is_delete = ?; ";			// and active
		$params = null;
		$params[] = 'prvd_Identify_repositoryName';		// repository name
		$params[] = 0;	
		$result = $this->Db->execute($query, $params);
	 	if($result === false){
			$error_msg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_cord",-1);
			return false;
		}
		$workspace_name = $result[0]['param_value'];
		// -Max Upload Size
		$query = "SELECT conf_value ".					// config value
				 "FROM ". DATABASE_PREFIX ."config ".	// NC2 config table
				 "WHERE conf_name = ?; ";				// config name
		$params = null;
		$params[] = 'upload_max_capacity_group';		// Max Upload Size
		$result = $this->Db->execute($query, $params);
	 	if($result === false){
			$error_msg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_cord",-1);
			return false;
		}
		$upload_max_capacity_group = $result[0]['conf_value'];
		if($upload_max_capacity_group == 0){
			// when not limit, setting from php.ini.
			$ini_upload_max_size = ini_get('upload_max_filesize');
			$ini_upload_max_size = str_replace("K", "000", $ini_upload_max_size);
			$ini_upload_max_size = str_replace("M", "000000", $ini_upload_max_size);
			$ini_upload_max_size = str_replace("G", "000000000", $ini_upload_max_size);
			$upload_max_capacity_group = $ini_upload_max_size;
		}

		// --------------------------------------------------------------
		// Set Repository Common Information.
		// --------------------------------------------------------------
		$root['repository_name'] = $workspace_name;					// Repository Name
	    $root['noOp']            = 'false';							// noOp Supported
	    $root['verbose']         = 'false';							// Verbose Supported
		// V1.2 => V1.3 : <sword:level> removed...
//	    $root['level']           = '1';								// Server Level	
		// V1.2 => V1.3 : <sword:version> added.(mandately)
	    $root['version']         = 2.0;								// Server Version
	    // V1.2 => V1.3 : <sword:maxUploadSize> added.(option)
	    $root['maxUploadSize']   = intval($upload_max_capacity_group)/1024;	// maxUploadSize (KB)	
		
		// --------------------------------------------------------------
		// Set Repository Collection Information.
		// --------------------------------------------------------------
		// 1st Collection, "Repository Review"
		$review = array();
		$review['collection']      = BASE_URL . '/weko/sword/deposit.php';	// collection uri
	   	$review['repository_name'] = $workspace_name;					// Repository Name
	    $review['collection_name'] = 'Repository Review';				// Collection Name
	    $review['accept'] = array();									// accept type (*N)
	    array_push($review['accept'],'application/zip');
	    $review['abstract']        = 'This is the review repository.';	// Collection description
	    $review['collectionPolicy']= 'This collection accepts packages from any admin/moderator users on WEKO.';	// Collection Policy
	    $review['formatNamespace'] = 'WEKO';							// Format Namespace
	    $review['treatment']       = 'Deposited items(zip) will be treated as WEKO import file which contains any WEKO contents information, and will be imported to WEKO.';			// Treatment description
	    $review['mediation']       = 'true';							// Mediation allowed
   	    // V1.2 => V1.3 : <sword:acceptPackaging> added.(option)
//	    $review['acceptPackaging']  = 'dummy';							// acceptPackaging
	    // V1.2 => V1.3 : <sword:service> added.(option)
//	    $review['service']  = 'dummy';									// nested service document	    
     	array_push($collections,$review);

     	// 2nd Collection "User InBox"
     	/*
	    $inbox = array();
	    $inbox['collection']     = BASE_URL . '/weko/sword/inbox.php';	// collection uri
	   	$inbox['repository_name'] = $workspace_name;				// Repository Name
	    $inbox['collection_name'] = 'User InBox';			// Collection Name
	    $inbox['accept'] = array();							// accept type (*N)
	    array_push($inbox['accept'],'application/zip');
	    $inbox['abstract']        = 'This is user inbox.';	// Collection description
	    $inbox['collectionPolicy']= 'Now Preparing';		// Collection Policy
	    $inbox['formatNamespace'] = 'WEKO';					// Format Namespace
	    $inbox['treatment']       = 'Now Preparing';		// Treatment description
	    $inbox['mediation']       = 'true';					// Mediation allowed
     	array_push($collections,$inbox);
		*/
 
    	// --------------------------------------------------------------
		// Output Repository Information.
		// --------------------------------------------------------------
		$workspace['workspace']   = $root;
     	$workspace['collections'] = $collections;
//	    array_push($workspace,$review);
	    
	    // end
	    return true;
	}  
}

// Add xml escape string 2008/10/15 Y.Nakao --end--
?>
