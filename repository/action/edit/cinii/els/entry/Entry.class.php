<?php
// --------------------------------------------------------------------
//
// $Id: Entry.class.php 22551 2013-05-13 00:57:50Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * [[機能説明]]
 *
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_Action_Edit_Cinii_Els_Entry extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	
	// parameter
	var $els_connect = null;
	var $lab_connect = null;
	// prevent double registration for ELS 2010/10/21 A.Suzuki --start--
	var $sel_index_id = null;
	// prevent double registration for ELS 2010/10/21 A.Suzuki --start--
	
	/**
	 * [[機能説明]]
	 *
	 * @access  public
	 */
	function execute()
	{
		try {
			//ini_set('max_execution_time', '30');
			$result = $this->initAction();
	        if ( $result === false ) {
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );
	            $DetailMsg = null;
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );
	            $this->failTrans(); // ROLLBACK
	            throw $exception;
	        }
	        
	        // Add file copy to contents lab 2010/06/28 A.Suzuki --start--
	        // Get ELS contents lab setting from config file
			$config = parse_ini_file(BASE_DIR.'/webapp/modules/repository/config/main.ini');
			if( isset($config["define:_REPOSITORY_ELS_CONTENTS_LAB"]) && 
				strlen($config["define:_REPOSITORY_ELS_CONTENTS_LAB"]) > 0 && 
				is_numeric($config["define:_REPOSITORY_ELS_CONTENTS_LAB"])){
				$contents_lab_setting = intval($config["define:_REPOSITORY_ELS_CONTENTS_LAB"]);
			} else {
				$contents_lab_setting = "0";
			}
			// Add file copy to contents lab 2010/06/28 A.Suzuki --end--
	        
	        // ------------------------------------------------
			// get parameter for ELS from DB
			// ------------------------------------------------
			$path_ssh = '';
			$path_scp = '';
			$els_login_id = '';
			$els_host = '';
			$lab_login_id = '';
			$lab_host = '';
			$lab_dir = '';
			$query = "SELECT param_name, param_value ".
					 "FROM ". DATABASE_PREFIX ."repository_parameter ".
					 "WHERE param_name = ? OR ".
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
				// delete tmp folder
				$this->removeDirectory($tmp_dir);
				return false;
			}
			for($ii=0; $ii<count($result); $ii++){
				if(strcmp($result[$ii]["param_name"], 'path_ssh') == 0){
					$path_ssh = $result[$ii]["param_value"];
				}
				if(strcmp($result[$ii]["param_name"], 'path_scp') == 0){
					$path_scp = $result[$ii]["param_value"];
				}
				if(strcmp($result[$ii]["param_name"], 'els_login_id') == 0){
					$els_login_id = $result[$ii]["param_value"];
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
			// -----------------------------------------------
			// check command
			// -----------------------------------------------
			// get private key
			$prv_key_pass = '';
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
					}
				}
			}
			$ssh_cmd = '';
			$scp_cmd = '';
			$ssh_cmd_flg = false;
			$scp_cmd_flg = false;
			// Add file copy to contents lab 2010/06/24 A.Suzuki --start--
			$lab_ssh_cmd = '';
			$lab_scp_cmd = '';
			// Add file copy to contents lab 2010/06/24 A.Suzuki --end--
			if(strlen($path_ssh) > 0){
				if(	file_exists($path_ssh.'ssh')){
					$ssh_cmd_flg = true;
					// make command
					$ssh_cmd = $path_ssh.'ssh ';
					$lab_ssh_cmd = $path_ssh.'ssh ';
				} else if(file_exists($path_ssh.'plink') || file_exists($path_ssh.'PLINK.EXE')){
					$ssh_cmd_flg = true;
					// make command
					$ssh_cmd = $path_ssh.'plink ';
					$lab_ssh_cmd = $path_ssh.'plink ';
				}
			}
			if(strlen($path_scp) > 0){
				if(	file_exists($path_scp.'scp')){
					$scp_cmd_flg = true;
					// make command
					$scp_cmd = $path_scp.'scp ';
					$lab_scp_cmd = $path_scp.'scp ';
				} else if(file_exists($path_scp.'pscp') || file_exists($path_scp.'PSCP.EXE')){
					$scp_cmd_flg = true;
					// make command
					$scp_cmd = $path_scp.'pscp ';
					$lab_scp_cmd = $path_scp.'pscp ';
				}
			}
			$entry_flg = false;
			$lab_entry_flg = false;
			
			// ------------------------------------------------
			// make ELS entry zip file
			// ------------------------------------------------
		 	$buf = $this->Session->getParameter("els_data");
		 	$els_file_data = $this->Session->getParameter("els_file_data");
	        $this->Session->removeParameter("els_data");
	        $this->Session->removeParameter("els_download");
	        $this->Session->removeParameter("els_auto_entry");
	        $this->Session->removeParameter("els_file_data");

			// change encoding
			$buf = mb_convert_encoding($buf, "SJIS", "UTF-8");
			
			$now_time = $this->TransStartDate;
			$now_time = str_replace("-", "", $now_time);
			$now_time = str_replace(" ", "_", $now_time);
			$now_time = str_replace(":", "", $now_time);
			$now_time = str_replace(".", "", $now_time);
			$now_time = substr($now_time, 0, 15);	// YYYYMMDD_hhmmssの形式にする
			
			//Add Download zip file in tsv 2009/08/24 K.Ito --start--
			//$date = date("YmdHis");
			$query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
			$result = $this->Db->execute($query);
			if($result === false || count($result) != 1){
				return false;
			}
			$date = $result[0]['now_date'];
			$tmp_dir = WEBAPP_DIR."/uploads/repository/_".$date;
			mkdir( $tmp_dir, 0777 );
			$file_name = $tmp_dir.DIRECTORY_SEPARATOR.$now_time. ".tsv";
			$file_report = fopen($file_name, "w");
			fwrite($file_report, $buf);
			fclose($file_report);
			$output_files = array($file_name);
			
			// Add file copy to contents lab 2010/06/24 A.Suzuki --start--
			if($contents_lab_setting == "1" && $els_file_data !== null){
				// copy tsv file for contents lab
				$tmp_dir_lab = WEBAPP_DIR."/uploads/repository/_".$date."_lab";
				mkdir( $tmp_dir_lab, 0777 );
				$lab_scp_dir_name = "contentslab_ELS_".$now_time;
				$lab_scp_dir = $tmp_dir_lab.DIRECTORY_SEPARATOR.$lab_scp_dir_name;
				mkdir( $lab_scp_dir, 0777 );
				$lab_tsv = $lab_scp_dir.DIRECTORY_SEPARATOR."els.tsv";
				copy($file_name, $lab_tsv);
				
				$contents_path = $this->getFileSavePath("file");
				if(strlen($contents_path) == 0){
					// default directory
					$contents_path = BASE_DIR.'/webapp/uploads/repository/files';
				}
				// check directory exists 
				if( file_exists($contents_path) ){
					for($ii=0;$ii<count($els_file_data);$ii++){
						if(file_exists($contents_path.DIRECTORY_SEPARATOR.$els_file_data[$ii][0])){
							copy($contents_path.DIRECTORY_SEPARATOR.$els_file_data[$ii][0],
								 $lab_scp_dir.DIRECTORY_SEPARATOR.$els_file_data[$ii][1]);
						}
					}
				}
			}
			// Add file copy to contents lab 2010/06/24 A.Suzuki --end--
			
			// set zip file name
			$zip_file = 'ELS_'.$els_login_id.'_'.$now_time.".zip";
			// compress zip file	
			File_Archive::extract(
				$output_files,
				File_Archive::toArchive($zip_file, File_Archive::toFiles( $tmp_dir."/" ))
			);
			$local_file = $tmp_dir.DIRECTORY_SEPARATOR.$zip_file;
			
			
			// ------------------------------------------------
			// SCP ELS file
			// ------------------------------------------------
			if($ssh_cmd_flg && $scp_cmd_flg){
				// SCP to ELS server
				if(	strlen($els_login_id) > 0 &&
					strlen($prv_key_pass) > 0 &&
					file_exists($prv_key_pass) &&
					strlen($local_file) > 0 &&
					file_exists($local_file) &&
					strlen($els_host) > 0 &&
					$this->els_connect == "true" ){
					
					$remote_file = '/home/'.$els_login_id.'/ELS';	// $this->els_login_id.'/'
					$scp_cmd .= ' -i '.$prv_key_pass.' ';	// private key
					$scp_cmd .= $local_file.' ';			// local file
					$scp_cmd .= $els_login_id.'@'.$els_host.':'.$remote_file;	// remote file
					$output = array();
					$ret = null;
					exec($scp_cmd, $output, $ret);
					// SSHでログインし、転送したファイルがあるか確認
					$ssh_cmd .= ' -i '.$prv_key_pass.' ';	// private key
					$ssh_cmd .= $els_login_id.'@'.$els_host;	// account
					exec($ssh_cmd.' ls '.$remote_file.'/', $output, $ret);
					if(array_search($zip_file, $output) !== false){
						// 転送成功
						$entry_flg = true;
						exec($ssh_cmd.' chmod 774 '.$remote_file.'/'.$zip_file, $output, $ret);
					} else {
						// 転送失敗
					}
				}
				// Add file copy to contents lab 2010/06/21 A.Suzuki --start--
				// SCP to contents lab server
				if($contents_lab_setting == "1"){
					if(	strlen($lab_login_id) > 0 &&
						strlen($prv_key_pass) > 0 &&
						file_exists($prv_key_pass) &&
						strlen($lab_scp_dir) > 0 &&
						is_dir($lab_scp_dir) &&
						strlen($lab_host) > 0 &&
						strlen($lab_dir) > 0 &&
						$this->lab_connect == "true" ){
					
						$lab_scp_cmd .= ' -i '.$prv_key_pass.' ';	// private key
						$lab_scp_cmd .= ' -r '.$lab_scp_dir.' ';	// local directory
						$lab_scp_cmd .= $lab_login_id.'@'.$lab_host.':'.$lab_dir;	// remote file
						$output = array();
						$ret = null;
						exec($lab_scp_cmd, $output, $ret);
						// SSHでログインし、転送したファイルがあるか確認
						$lab_ssh_cmd .= ' -i '.$prv_key_pass.' ';		// private key
						$lab_ssh_cmd .= $lab_login_id.'@'.$lab_host;	// account
						exec($lab_ssh_cmd.' ls '.$lab_dir.'/', $output, $ret);
						if(array_search($lab_scp_dir_name, $output) !== false){
							// 転送成功
							$lab_entry_flg = true;
							//exec($lab_ssh_cmd.' chmod -R 774 '.$lab_dir.'/'.$lab_scp_dir_name, $output, $ret);
							exec($lab_ssh_cmd.' chmod 777 '.$lab_dir.'/'.$lab_scp_dir_name, $output, $ret);
							exec($lab_ssh_cmd.' chmod 666 '.$lab_dir.'/'.$lab_scp_dir_name.'/*', $output, $ret);
						} else {
							// 転送失敗
						}
					}
				}
				// Add file copy to contents lab 2010/06/21 A.Suzuki --end--
			}
			// delete tmp folder
			$this->removeDirectory($tmp_dir);
			$this->removeDirectory($tmp_dir_lab);
			
			// prevent double registration for ELS 2010/10/21 A.Suzuki --start--
			if($entry_flg == true){
			    $this->entryRegisteredIndex($this->sel_index_id);
			}
			// prevent double registration for ELS 2010/10/21 A.Suzuki --end--
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
            // prevent double registration for contents lab
            if($lab_entry_flg == true)
            {
                $this->entryRegisteredIndexForContentsLab($this->sel_index_id);
            }
            // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
			
			// end action
			$result = $this->exitAction();	// COMMIT
			if ( $result == false ){
				// error
				echo 'false';
			}
			
			if($entry_flg == true && $lab_entry_flg == true){
				// both success
				echo 'true';
			} else if($this->lab_connect == "true" && $lab_entry_flg == false && $entry_flg == true){
				// ELS: OK, lab: NG
				echo 'lab_NG';
			} else if($this->els_connect == "true" && $entry_flg == false && $lab_entry_flg == true){
				// ELS: NG, lab: OK
				echo 'ELS_NG';
			} else if($this->els_connect == "true" && $entry_flg == false && $this->lab_connect == "true" && $lab_entry_flg == false){
				// ELS: NG, lab: NG
				echo 'NG';
			} else if($entry_flg == true && $lab_entry_flg == false){
				// ELS: OK, lab: no access
				echo 'ELS_OK';
			} else if($entry_flg == false && $lab_entry_flg == true){
				// ELS: no access, lab: OK
				echo 'lab_OK';
			} else {
				echo 'false';
			}
			
			exit();
			
		} catch ( RepositoryException $Exception) {
			//end action
		  	$this->exitAction(); // ROLLBACK
			// delete tmp folder
			$this->removeDirectory($tmp_dir);
			$this->removeDirectory($tmp_dir_lab);
			//error
			echo 'false';
			
			exit();
		}
		// Add ELS auto entry 2009.08.31 Y.Nakao --end--

	}
	
    /**
     * Entry registered index_id to parameter table
     *
     * @param $index_id int indexID
     */
    function entryRegisteredIndex($index_id){
        // get registered index
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'els_registered_index';";
        $result = $this->Db->execute($query);
        if ($result === false) {
            $this->failTrans();
            return false;
        }
        if($result[0]['param_value']!=""){
            $elsRegisteredIndex = explode(",", $result[0]['param_value']);
        } else {
            $elsRegisteredIndex = array();
        }
        
        // get child index
        $index_array = array();
        $this->getChildIndexId($index_id, $index_array);
        
        // check index_id
        for($ii=0;$ii<count($index_array);$ii++){
            if(!in_array($index_array[$ii], $elsRegisteredIndex)){
                array_push($elsRegisteredIndex, $index_array[$ii]);
            }
        }
        
        // sort index_id
        sort($elsRegisteredIndex, SORT_NUMERIC);
        
        // implode array
        $index_ids_text = implode(",", $elsRegisteredIndex);
        
        // update parameter table data
        $params = array();
        $params[] = $index_ids_text;                          // param_value
        $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
        $params[] = $this->TransStartDate;                    // mod_date
        $params[] = "els_registered_index";                   // param_name
        $result = $this->updateParamTableData($params, $Error_Msg);
        if ($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            $tmpstr = sprintf("prefixID update failed : %s", $ii, $jj, $errMsg ); 
            $this->Session->setParameter("error_msg", $tmpstr);
            $this->failTrans();     //ROLLBACK
            return 'error';
        }
    }
    
    /**
     * Get child index_id
     *
     * @param $index_id int
     * @param &$index_array array
     */
    function getChildIndexId($index_id, &$index_array) {
        array_push($index_array, $index_id);
        $query = 'SELECT index_id '.
                 'FROM '.DATABASE_PREFIX.'repository_index '.
                 'WHERE parent_index_id = ? '.
                 '  AND is_delete = 0;';
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if ($result === false) {
            return false;
        }
        
        // Get child index data
        if(count($result) != 0){
            for($ii=0;$ii<count($result);$ii++){
                $this->getChildIndexId($result[$ii]['index_id'], $index_array);
            }
        }
    }
    
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
    /**
     * update registered index list of contents lab
     *
     * @param int $index_id
     */
    private function entryRegisteredIndexForContentsLab($index_id)
    {
        // get registered index
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'contents_lab_registered_index';";
        $result = $this->Db->execute($query);
        if ($result === false) {
            $this->failTrans();
            return false;
        }
        if($result[0]['param_value'] != ""){
            $contentsLabRegisteredIndex = explode(",", $result[0]['param_value']);
        } else {
            $contentsLabRegisteredIndex = array();
        }
        
        // normal entry process -> need to get child index
        $index_array = array();
        $this->getChildIndexId($index_id, $index_array);
        
        // check index_id
        for($ii=0;$ii<count($index_array);$ii++){
            if(!in_array($index_array[$ii], $contentsLabRegisteredIndex)){
                array_push($contentsLabRegisteredIndex, $index_array[$ii]);
            }
        }
        
        // sort index_id
        sort($contentsLabRegisteredIndex, SORT_NUMERIC);
        
        // implode array
        $index_ids_text = implode(",", $contentsLabRegisteredIndex);
        
        // update parameter table data
        $params = array();
        $params[] = $index_ids_text;                          // param_value
        $params[] = $this->Session->getParameter("_user_id"); // mod_user_id
        $params[] = $this->TransStartDate;                    // mod_date
        $params[] = "contents_lab_registered_index";          // param_name
        $result = $this->updateParamTableData($params, $Error_Msg);
        if ($result === false) {
            $errMsg = $this->Db->ErrorMsg();
            $tmpstr = sprintf("prefixID update failed : %s", $ii, $jj, $errMsg ); 
            $this->Session->setParameter("error_msg", $tmpstr);
            $this->failTrans();     //ROLLBACK
            return 'error';
        }
    }
    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
}
?>
