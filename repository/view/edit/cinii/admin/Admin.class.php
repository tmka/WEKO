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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryShelfregistration.class.php';

/**
 * [[機能説明]]
 *
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_View_Edit_Cinii_Admin extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	
	// member
	var $itemtype_data = null;
	var $els_result = null;
	var $selIdx_name = null;
	var $position_index = null;
	var $selIdx_id = null;
	var $all_success = null;
	var $els_download = null;
	var $els_auto_entry = null;
	var $els_entry = null;
	// visible tab index
	var $els_active_tab = null;
	// ssh path
	var $scp_cmd = null;
	var $ssh_cmd = null;
	// scp path
	var $path_ssh = null;
	var $path_scp = null;
	// ELS aut entry check
	var $els_auto = null;
	// login id for ELS
	var $els_login_id = null;
	// ELS server connect status
	var $els_connect = null;
	var $els_scp = null;
	
	// Add file copy to contents lab 2010/06/25 A.Suzuki --start--
	var $lab_connect = null;
	var $lab_scp = null;
	// Add file copy to contents lab 2010/06/25 A.Suzuki --end--
	
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
    /**
     * show 'now executing...' or not
     *
     * @var string: true : file is exists -> now executing
     *              false: file is not exists -> ready
     */
    public $shelfRegistrationFlg = 'false';
    
    /**
     * ELS convert failed index ids and index names of the last shelf registration
     *
     * @var array: convertFailedIndexList[cnt]['indexId'] = index_id
     *                                        ['indexName'] = index_name
     *                                        ['url'] = opensearchUrl
     */
    public $convertFailedIndexList = array();
    
    /**
     * for kill process
     *
     * @var string
     */
    public $kill_flg = null;
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
	
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
    // add constructor
    public function Repository_View_Edit_Cinii_Admin($Session = null, $Db = null)
    {
        if(isset($Session))
        {
            $this->Session = $Session;
        }
        if(isset($Db))
        {
            $this->Db = $Db;
        }
    }
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
    
	/**
	 * [[機能説明]]
	 *
	 * @access  public
	 */
	function execute()
	{
		// show result
		$this->els_result = $this->Session->getParameter("els_result");
		$this->Session->removeParameter("els_result");
		// show sel index name
		$this->selIdx_name = $this->Session->getParameter("selIdx_name");
		$this->Session->removeParameter("selIdx_name");
		// show item position index name
		$this->position_index = $this->Session->getParameter("position_index");
		$this->Session->removeParameter("position_index");
		// show selIdx_id
		$this->selIdx_id = $this->Session->getParameter("selIdx_id");
		$this->Session->removeParameter("selIdx_id");
		// show convert result all success or not
		$this->all_success = $this->Session->getParameter("all_success");
		$this->Session->removeParameter("all_success");
		// get tree data
		// change index tree view action 2008/12/04 Y.Nakao --start--
		//$this->setIndexTreeData2Session();
		// change index tree view action 2008/12/04 Y.Nakao --end--
		$this->els_download = $this->Session->getParameter("els_download");
		$this->Session->removeParameter("els_download");
		$this->els_auto_entry = $this->Session->getParameter("els_auto_entry");
		$this->Session->removeParameter("els_auto_entry");
		
        // Add contents all entry 2012/10/21 T.Koyasu -start-
        $this->shelfRegistrationFlg = $this->checkExecuteShelfRegistration();
        // Add contents all entry 2012/10/21 T.Koyasu -end-
		
		// Add for get lang resource 
		$this->setLangResource();
		
		// Add ELS auto entry 2009.08.31 Y.Nakao --start--
		// ------------------------------------------------
		// get parameter for ELS from DB
		// ------------------------------------------------
		$this->els_active_tab = 0;
		if(strlen($this->Session->getParameter("els_active_tab")) > 0){
			$this->els_active_tab = $this->Session->getParameter("els_active_tab");
		}
		$this->Session->removeParameter("els_active_tab");
		
		$this->path_ssh = '';
		$this->path_scp = '';
		$this->els_auto = 'false';
		$this->els_login_id = '';
		$els_host = '';
		$query = "SELECT param_name, param_value ".
				 "FROM ". DATABASE_PREFIX ."repository_parameter ".
				 "WHERE param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name = ? OR ".
				 "param_name =? AND ".
				 "is_delete = ?; ";
		$params = array();
		$params[] = 'path_ssh';
		$params[] = 'path_scp';
		$params[] = 'els_auto';
		$params[] = 'els_login_id';
		$params[] = 'els_host';
		$params[] = 'lab_login_id';
		$params[] = 'lab_host';
		$params[] = 'lab_dir';
		$params[] = 0;
		$result = $this->Db->execute($query, $params);
		if($result === false){
			//error
			$errMsg = $this->Db->ErrorMsg();
			$this->Session->setParameter("error_msg", $errMsg);
			return false;
		}
		for($ii=0; $ii<count($result); $ii++){
			if(strcmp($result[$ii]["param_name"], 'path_ssh') == 0){
				$this->path_ssh = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'path_scp') == 0){
				$this->path_scp = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'els_auto') == 0){
				$this->els_auto = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'els_login_id') == 0){
				$this->els_login_id = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'els_host') == 0){
				$els_host = $result[$ii]["param_value"];
			}
			// Add file copy to contents lab 2010/06/25 A.Suzuki --start--
			if(strcmp($result[$ii]["param_name"], 'lab_login_id') == 0){
				$lab_login_id = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'lab_host') == 0){
				$lab_host = $result[$ii]["param_value"];
			}
			if(strcmp($result[$ii]["param_name"], 'lab_dir') == 0){
				$lab_dir = $result[$ii]["param_value"];
			}
			// Add file copy to contents lab 2010/06/25 A.Suzuki --end--
		}
		
		// getr lang resource
//		$smartyAssign = $this->Session->getParameter("smartyAssign");
		// ---------------------------
		// get private key
		// ---------------------------
		$prv_key_pass = '';
		$local_file = '';
		$scp_filename = '';
		$ppk_dir = WEBAPP_DIR.DIRECTORY_SEPARATOR.
					'modules'.DIRECTORY_SEPARATOR.
					'repository'.DIRECTORY_SEPARATOR.
					'files'.DIRECTORY_SEPARATOR.
					'els'.DIRECTORY_SEPARATOR;
		if ($handle = opendir($ppk_dir)) {
			while (false !== ($filename = readdir($handle))) {
				if(!is_dir($filename)){
					$elm = explode(".", $filename);
					if($elm[count($elm)-1] == "ppk"){
						$prv_key_pass = $ppk_dir.$filename;
					}
					if($elm[count($elm)-1] == "txt"){
						$local_file = $ppk_dir.$filename;
						$scp_filename = $filename;
					}
				}
			}
		}
//		if(strlen($prv_key_pass) == 0){
//			$this->els_connect .= $smartyAssign->getLang("repository_cinii_els_connect_NG_key").'<br/>';
//		}
		if(strlen($local_file) == 0){
			// make ELS test file
			$scp_filename = "els_test.txt";
			$local_file = $ppk_dir.$scp_filename;
			$file = fopen($local_file, "w");
			fwrite($file, "");
			fclose($file);
		}
		
		// ------------------------
		// check command file exest
		// ------------------------
		$this->els_connect = 'false';
		$this->ssh_cmd = 'false';
		$this->scp_cmd = 'false';
		$this->els_scp = 'false';
		$ssh_cmd = '';
		$scp_cmd = '';
		// Add file copy to contents lab 2010/06/25 A.Suzuki --start--
		$this->lab_connect = 'false';
		$this->lab_scp = 'false';
		$lab_ssh_cmd = '';
		$lab_scp_cmd = '';
		// Add file copy to contents lab 2010/06/25 A.Suzuki --end--
		
		if(strlen($this->path_ssh) > 0){
			if(	file_exists($this->path_ssh.'ssh')){
				$this->ssh_cmd_flg = 'true';
				// make command
				$ssh_cmd = $this->path_ssh.'ssh ';
				$lab_ssh_cmd = $this->path_ssh.'ssh ';
			} else if(file_exists($this->path_ssh.'plink') || file_exists($this->path_ssh.'PLINK.EXE')){
				$this->ssh_cmd_flg = 'true';
				// make command
				$ssh_cmd = $this->path_ssh.'plink ';
				$lab_ssh_cmd = $this->path_ssh.'plink ';
			}
		}
		if(strlen($this->path_scp) > 0){
			if(	file_exists($this->path_scp.'scp')){
				$this->scp_cmd_flg = 'true';
				// make command
				$scp_cmd = $this->path_scp.'scp ';
				$lab_scp_cmd = $this->path_scp.'scp ';
			} else if(file_exists($this->path_scp.'pscp') || file_exists($this->path_scp.'PSCP.EXE')){
				$this->scp_cmd_flg = 'true';
				// make command
				$scp_cmd = $this->path_scp.'pscp ';
				$lab_scp_cmd = $this->path_scp.'pscp ';
			}
		}
		
		// ---------------------------
		// check connect SCP
		// ---------------------------
		try {
			ini_set('max_execution_time', '30');
			if($this->ssh_cmd_flg == 'true' && $this->scp_cmd_flg == 'true'){
				if(	strlen($this->els_login_id) > 0 &&
					strlen($prv_key_pass) > 0 &&
					file_exists($prv_key_pass) &&
					strlen($scp_filename) > 0 && 
					strlen($local_file) > 0 &&
					file_exists($local_file) &&
					strlen($els_host) > 0	){
					
					$remote_file = '/home/'.$this->els_login_id.'/ELS';	// $this->els_login_id.'/'
					$scp_cmd .= ' -i '.$prv_key_pass.' ';	// private key
					$scp_cmd .= $local_file.' ';			// local file
					$scp_cmd .= $this->els_login_id.'@'.$els_host.':'.$remote_file;	// remote file
					$output = array();
					$ret = null;
					exec($scp_cmd, $output, $ret);
					// SSHでログインし、転送したファイルがあるか確認
					$ssh_cmd .= ' -i '.$prv_key_pass.' ';	// private key
					$ssh_cmd .= $this->els_login_id.'@'.$els_host;	// account
					exec($ssh_cmd.' ls '.$remote_file.'/', $output, $ret);
					if(array_search($scp_filename, $output) !== false){
						// 転送OK
						$this->els_connect = 'true';
						// ELS自動登録可能
						$this->els_scp = 'true';
						// 接続テストに使用したファイルを削除
						exec($ssh_cmd.' rm '.$remote_file.'/'.$scp_filename, $output, $ret);
					} else {
						// 転送失敗
					}
				}
				
				// Add file copy to contents lab 2010/06/25 A.Suzuki --start--
				if(	strlen($lab_login_id) > 0 &&
					strlen($prv_key_pass) > 0 &&
					file_exists($prv_key_pass) &&
					strlen($scp_filename) > 0 && 
					strlen($local_file) > 0 &&
					file_exists($local_file) &&
					strlen($lab_host) > 0 &&
					strlen($lab_dir) > 0 &&
					_REPOSITORY_ELS_CONTENTS_LAB == 1 ){
					
					$lab_scp_cmd .= ' -i '.$prv_key_pass.' ';	// private key
					$lab_scp_cmd .= $local_file.' ';			// local file
					$lab_scp_cmd .= $lab_login_id.'@'.$lab_host.':'.$lab_dir;	// remote file
					$output = array();
					$ret = null;
					exec($lab_scp_cmd, $output, $ret);
					// SSHでログインし、転送したファイルがあるか確認
					$lab_ssh_cmd .= ' -i '.$prv_key_pass.' ';		// private key
					$lab_ssh_cmd .= $lab_login_id.'@'.$lab_host;	// account
					exec($lab_ssh_cmd.' ls '.$lab_dir.'/', $output, $ret);
					if(array_search($scp_filename, $output) !== false){
						// 転送OK
						$this->lab_connect = 'true';
						// コンテンツラボ登録可能
						$this->lab_scp = 'true';
						// 接続テストに使用したファイルを削除
						exec($lab_ssh_cmd.' rm '.$lab_dir.'/'.$scp_filename, $output, $ret);
					} else {
						// 転送失敗
					}
				}
				// Add file copy to contents lab 2010/06/25 A.Suzuki --end--
			}
			
			// end action
			$result = $this->exitAction();	// COMMIT
			if ( $result == false ){
				// error
				return false;
			}
			
			return 'success';
			
		} catch ( RepositoryException $Exception) {
			//end action
		  	$this->exitAction(); // ROLLBACK
			
			//error
			return 'error';
		}
		// Add ELS auto entry 2009.08.31 Y.Nakao --end--

	}
	
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
    /**
     * check temporary file exists
     *
     * @return string:true  :executing
     *                false :is not execute
     */
    private function checkExecuteShelfRegistration()
    {
        $shelfregistration = new RepositoryShelfregistration($this->Session, $this->Db);
        
        // kill process
        if(isset($this->kill_flg) && $this->kill_flg == "true")
        {
            $shelfregistration->killProcess();
            // get convert failed index list
            $this->setConvertFailedIndexList($shelfregistration);
            return 'false';
        }
        
        // true -> now executing
        if($shelfregistration->checkExistsWorkFile())
        {
            return 'true';
        } else {
            // get convert failed index list
            $this->setConvertFailedIndexList($shelfregistration);
            return 'false';
        }
    }
    
    /**
     * set ELS convert failed index list
     *
     * @param RepositoryShelfregistration $shelfregistration
     * @return boolean
     */
    private function setConvertFailedIndexList($shelfregistration)
    {
        $this->convertFailedIndexList = array();
        
        // get failed index ids
        $indexIds = $shelfregistration->getConvertFailedIndexList();
        
        if(count($indexIds) > 0)
        {
            $indexIdsText = implode(",", $indexIds);
            
            $params = array();
            $query = "SELECT index_id, index_name, index_name_english, parent_index_id ". 
                     " FROM ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. 
                     " WHERE index_id IN (". $indexIdsText. ")". 
                     " AND is_delete = ?; ";
            $params[] = 0;
            
            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                return false;
            }
            
            for($ii = 0; $ii < count($result); $ii++)
            {
                // get parent index information
                $parentIndexResult = array();
                $this->getParentIndex($result[$ii]['index_id'], $parentIndexResult);
                $parentIndexList = array();
                if(count($parentIndexResult) > 1)
                {
                    for($jj = 0; $jj < count($parentIndexResult) - 1; $jj++)
                    {
                        // set index name
                        $parentIndexName = "";
                        if($this->Session->getParameter("_lang") == "japanese") {
                            if(strlen($parentIndexResult[$jj]['index_name']) > 0) {
                                $parentIndexName = $parentIndexResult[$jj]['index_name'];
                            } else {
                                $parentIndexName = $parentIndexResult[$jj]['index_name_english'];
                            }
                        } else {
                            if(strlen($parentIndexResult[$jj]['index_name_english']) > 0) {
                                $parentIndexName = $parentIndexResult[$jj]['index_name_english'];
                            } else {
                                $parentIndexName = $parentIndexResult[$jj]['index_name'];
                            }
                        }
                        array_push($parentIndexList, array('indexId' => $parentIndexResult[$jj]['index_id'], 
                                                           'indexName' => $parentIndexName));
                    }
                }
                
                // set index name
                $indexName = "";
                if($this->Session->getParameter("_lang") == "japanese") {
                    if(strlen($result[$ii]['index_name']) > 0) {
                        $indexName = $result[$ii]['index_name'];
                    } else {
                        $indexName = $result[$ii]['index_name_english'];
                    }
                } else {
                    if(strlen($result[$ii]['index_name_english']) > 0) {
                        $indexName = $result[$ii]['index_name_english'];
                    } else {
                        $indexName = $result[$ii]['index_name'];
                    }
                }
                array_push($this->convertFailedIndexList, array('indexId' => $result[$ii]['index_id'], 
                                                                'indexName' => $indexName, 
                                                                'url' => BASE_URL. '/?action=repository_opensearch&index_id='. $result[$ii]['index_id'], 
                                                                'parentIndexList' => $parentIndexList 
                                                                ));
            }
        }
        
        return true;
    }
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
}
?>
