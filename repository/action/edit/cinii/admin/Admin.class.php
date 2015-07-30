<?php
// --------------------------------------------------------------------
//
// $Id: Admin.class.php 20119 2012-11-02 10:33:38Z yuko_nakao $
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
require_once WEBAPP_DIR. '/modules/repository/action/common/download/Download.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/cinii/ElsCommon.class.php';

class Repository_Action_Edit_Cinii_Admin extends RepositoryAction
{
	// memba
	var $lang = null;
	
	// component
	var $Session = null;
	var $Db = null;
	
	var $smartyAssign = null;
	
	var $selIdx_id = null;
	var $selIdx_name = null;
	var $entry_type = null;
	
    // prevent double registration for ELS 2010/10/21 A.Suzuki --start--
    private $elsRegisteredIndex_ = null;
    // prevent double registration for ELS 2010/10/21 A.Suzuki --end--
	
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
    /**
     * flg of shelf registration to contents lab
     *
     * @var boolean: true->shelf registration
     *             : false->normal entry
     */
    private $shelfregistrationFlg_ = false;
    
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
	        
			/////////////// get lang ///////////////
			$this->lang = $this->Session->getParameter("_lang");
			$this->smartyAssign = $this->Session->getParameter("smartyAssign");
			
			// prevent double registration for ELS 2010/10/21 A.Suzuki --start--
			if(intval($this->entry_type) == 2){
    			// get registered index
                $query = "SELECT `param_value` ".
                         "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                         "WHERE `param_name` = 'els_registered_index';";
                $result = $this->Db->execute($query);
                if ($result === false) {
                    $this->failTrans();
                    return false;
                }
                $this->elsRegisteredIndex_ = explode(",", $result[0]['param_value']);
			} else {
			    $this->elsRegisteredIndex_ = array();
			}
            // prevent double registration for ELS 2010/10/21 A.Suzuki --end--
            
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
            // prevent double registration to contents lab
            if($this->shelfregistrationFlg_)
            {
                // get registed index to contents lab
                $query = "SELECT `param_value` ".
                         "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                         "WHERE `param_name` = 'contents_lab_registered_index';";
                $result = $this->Db->execute($query);
                if ($result === false) {
                    $this->failTrans();
                    return false;
                }
                $this->elsRegisteredIndex_ = explode(",", $result[0]['param_value']);
            }
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
			
			/////////////// select public item from under select index ///////////////
			// init
			$els_index_id = array();
			$this->Session->setParameter("selIdx_name", $this->selIdx_name);
			
			// get child index from select index id
			// prevent double registration for ELS 2010/10/21 A.Suzuki --start--
            if(!in_array($this->selIdx_id, $this->elsRegisteredIndex_)){
                array_push($els_index_id, array("index_id" => $this->selIdx_id));
            }
            // prevent double registration for ELS 2010/10/21 A.Suzuki --end--
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
            // if execute shelf registration, not include sub index
            if(!$this->shelfregistrationFlg_)
            {
                $result = $this->getSubIndexId($this->selIdx_id, $els_index_id);
                if ($result === false) {
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
                    $this->failTrans();								 //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
            }
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
			
			// get item from index and child index
			$els_item = array();
			for($ii=0;$ii<count($els_index_id);$ii++){
				// get item info
				$result = $this->getItemInfo($els_index_id[$ii]["index_id"], $els_item);
				if ($result === false) {
					//エラー処理を行う
					$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
					$this->failTrans();								 //トランザクション失敗を設定(ROLLBACK)
					throw $exception;
				}
			}
			// if noitem 
			if(count($els_item) == 0){
				$this->Session->setParameter("els_result", "no_item");
                                //$this->Session->setParameter("elsDebugLog", $this->selIdx_id." : ".$this->shelfregistrationFlg_.":".$els_index_id[0]['index_id']." : ".count($els_index_id));
				return 'success';
			}
			/////////////// Change ELS Mapping to ELS Format ///////////////
			$els_common = new ElsCommon($this->Session, $this->Db, $this->smartyAssign);
			$return = $els_common->createElsData($els_item, $buf, $result_message, $els_file_data);
			if($buf != ""){
				if(intval($this->entry_type) == 1){
					$this->Session->setParameter("els_download", "true");
					$this->Session->removeParameter("els_file_data");
				} else if(intval($this->entry_type) == 2){
					$this->Session->setParameter("els_auto_entry", "true");
					$this->Session->setParameter("els_file_data", $els_file_data);
				}
				$buf .= "\r\n";
				$this->Session->setParameter("els_data", $buf);
			} else {
				$this->Session->removeParameter("els_download");
				$this->Session->removeParameter("els_data");
				$this->Session->removeParameter("els_file_data");
			}
			$this->Session->setParameter("els_result", $result_message);
			
			/////////////// set item index info //////////////////
			$item_index = array();
			$all_success = "true";
			for($ii=0; $ii<count($els_item); $ii++){
				array_push($item_index, $els_item[$ii]["index_name"]);
				if($all_success == "true" && $result_message[$ii][0] == "0"){
					$all_success = "false";
				}
			}
			$this->Session->setParameter("position_index", $item_index);
			$this->Session->setParameter("selIdx_id", $this->selIdx_id);
			$this->Session->setParameter("all_success", $all_success);
			
			return 'success';
			
			// if mail to Result
//			$insert = $this->smartyAssign->getLang("repository_els_mail_body");
//			$result_message = count($els_text_array) .$insert."\n\n\n".$result_message;
//			
//			$subj = $this->smartyAssign->getLang("repository_els_mail_subject");
//			$this->mailMain->setSubject($subj);
//			$this->mailMain->setBody($result_message);
//			 
//			// send user
//			$user_id = $this->Session->getParameter("_user_id");
//			$params = array();
//			$params[] = "user_id = ".$user_id;
//			$block_info = $this->getBlockPageId();
//			
//			// select send user
//			$users = $this->usersView->getSendMailUsers(null, null, null, array("{users}.user_id" => $user_id));
//			$this->mailMain->setToUsers($users);
//			$this->mailMain->send();

			// show error message
			// download action run flag
			
		}
		catch ( RepositoryException $Exception) {
			//end action
		  	$this->exitAction(); // ROLLBACK
			
			//error
			return false;
		}
	}
	
	/**
	 * get all child index from index
	 *
	 * @param $index_id index id
	 * @param $embargo_index_id result
	 */
	function getSubIndexId($index_id, &$embargo_index_id){
		// get childindex
		$query = "SELECT index_id ".
				 "FROM ". DATABASE_PREFIX ."repository_index ".
				 "WHERE parent_index_id = ? AND ".
				 "is_delete = ?; ";
		$params = array();
		$params[] = $index_id;
		$params[] = 0;
		$result = $this->Db->execute($query, $params);
		if($result === false){
			//error
			$errMsg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_msg", $errMsg);
			return false;
		}
		// get child's childindex
		for($ii=0;$ii<count($result);$ii++){
			$sub_result = $this->getSubIndexId($result[$ii]["index_id"], $embargo_index_id);
			if($sub_result === false){
				return false;
			}
			if(!in_array($result[$ii]["index_id"], $this->elsRegisteredIndex_)){
                array_push($embargo_index_id, array("index_id" => $result[$ii]["index_id"]));
			}
		}
		return true;
	}
	
	/**
	 * get item data from index
	 *
	 * @param $index_id indexID
	 * @param $item_info result
	 */
	function getItemInfo($index_id, &$item_info){
		$query = "SELECT item_id, item_no ".
				 "FROM ". DATABASE_PREFIX ."repository_position_index ".
				 "WHERE index_id = ? AND ".
				 "is_delete = ?; ";
		$params = null;
		$params[] = $index_id;
		$params[] = 0;
		$result_item = $this->Db->execute($query, $params);
		if($result_item === false){
			//error
			$errMsg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_msg", $errMsg);
			return false;
		}
		
		$query = "SELECT index_name ".
				 "FROM ". DATABASE_PREFIX ."repository_index ".
				 "WHERE index_id = ? AND ".
				 "is_delete = ?; ";
		$params = null;
		$params[] = $index_id;
		$params[] = 0;
		$result_index = $this->Db->execute($query, $params);
		if($result_index === false){
			//error
			$errMsg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_msg", $errMsg);
			return false;
		}
		for($ii=0; $ii<count($result_item); $ii++){
			$result_item[$ii]["index_name"] = $result_index[0]["index_name"];
		}
		
		$item_info = array_merge($item_info, $result_item);
		
		return true;
	}
}
?>
