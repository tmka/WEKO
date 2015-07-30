<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryShelfregistration.class.php 22551 2013-05-13 00:57:50Z yuko_nakao $
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
require_once WEBAPP_DIR. '/modules/repository/action/edit/cinii/admin/Admin.class.php';
require_once WEBAPP_DIR. '/modules/repository/view/edit/cinii/admin/Admin.class.php';

/**
 * Repository module cinii class
 *
 * @package repository
 * @access public
 */
class RepositoryShelfregistration extends RepositoryAction
{
    /**
     * progress file path
     *
     * @var string
     */
    private $workFile = "";
    
    /**
     * temporary progress file path
     *
     * @var string
     */
    private $tmpWorkFile = "";
    
    /**
     * directory path of progress file
     *
     * @var string
     */
    private $workFileDir = "";
    
    /**
     * ELS convert failed index_id list file path
     *
     * @var string
     */
    private $convertFailedIndexListFile = "";
    
    /**
     * state of shelf registration
     *
     * @var string : start -> create progress file and start shelf registration
     *             : running -> executing shelf registration
     *             : end -> delete progress file and end shelf registration
     *             : block -> progress file can not read or write
     */
    private $status = "";
    
    /**
     * index id is registration
     *
     * @var int
     */
    private $selIndexId = 0;
    
    /**
     * check result to connect lab contents
     *
     * @var string: true -> connect is success
     *              false-> connect is failed
     */
    private $lab_connect = "";
    
    /**
     * constructor
     *
     * @param Object $Session
     * @param Object $Db
     * @return RepositoryShelfregistration
     */
    public function RepositoryShelfregistration($Session, $Db, $TransStartDate){
        $this->Session = $Session;
        $this->Db = $Db;
        $this->TransStartDate = $TransStartDate;
        
        $this->workFileDir = WEBAPP_DIR. "/logs/weko/shelfregistration";
        $this->workFile = $this->workFileDir. "/progress.tsv";
        $this->tmpWorkFile = $this->workFileDir. "/tmpprogress.tsv";
        $this->convertFailedIndexListFile = $this->workFileDir. "/convertFailedIndexList.txt";
    }
    
    /**
     * check work file is exists or not
     *
     * @return boolean
     */
    public function checkExistsWorkFile()
    {
        if(file_exists($this->workFile))
        {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * get now status from workfile
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Open progress file
     * 
     * @param $executeFlg execute or not
     */
    public function openProgressFile($executeFlg=true)
    {
        // Check progress file exists
        if(!file_exists($this->workFile))
        {
            // Progress file is not exist
            // -> Set status to "start".
            $this->status = "start";
        }
        else
        {
            // Check file read rights
            if(is_readable($this->workFile) && is_writable($this->workFile))
            {
                // Get only one line
                $handle = fopen($this->workFile, "r");
                $line = fgets($handle);
                $line = str_replace("\r\n", "", $line);
                $line = str_replace("\n", "", $line);
                $line = trim($line);
                fclose($handle);
                
                // There is contents in progress file
                if($executeFlg)
                {
                    chmod($this->workFile, 0100); // -wx --- ---
                    
                    // Interval for request to repository
                    sleep($this->sleepSec);
                }
                
                if(strlen($line) > 0)
                {
                    // -> Set status to "running" and get params.
                    $this->status = "running";
                    
                    // Explode string
                    $progressArray = explode("\t", $line, 1);
                    $this->selIndexId = intval($progressArray[0]);
                    
                }
                else
                {
                    // Progress file is empty
                    // -> Set status to "end".
                    $this->status = "end";
                }
            }
            else
            {
                $this->status = "block";
            }
        }
    }
    
    /**
     * Create progress file
     */
    public function createProgressFile()
    {
        $handle = null;
        
        try
        {
            // check directory
            if( !file_exists($this->workFileDir ) ){
                // make shelf registration folder
                mkdir($this->workFileDir);
            }
            chmod ( $this->workFileDir, 0300 );
            
            // get all index ids
            $indexIds = $this->getAllIndexIds();
            if($indexIds === false){
                return false;
            }
            
            $progressText = "";
            
            for($ii = 0; $ii < count($indexIds); $ii++)
            {
                if(strlen($progressText) !== 0)
                {
                    $progressText .= "\n";
                }
                $progressText .= $indexIds[$ii];
            }
            
            // Create progress file
            $handle = fopen($this->workFile, "w");
            fwrite($handle, $progressText);
            fclose($handle);
            chmod($this->workFile, 0700);
            
            return true;
        }
        catch (Exception $ex)
        {
            // File close
            if($handle != null)
            {
                fclose($handle);
            }
            return false;
        }
    }
    
    /**
     * get no deleted all index ids 
     *
     * @return array
     */
    private function getAllIndexIds()
    {
        $indexIds = array();
        
        $params = array();
        $query = "SELECT ". RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID. 
                 " FROM ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. 
                 " WHERE ". RepositoryConst::DBCOL_COMMON_IS_DELETE. " = ? ";
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) === 0){
            return false;
        }
        for($ii = 0; $ii < count($result); $ii++)
        {
            array_push($indexIds, $result[$ii]['index_id']);
        }
        
        return $indexIds;
    }
    
    /**
     * Delete progress file
     */
    public function deleteShelfregistrationFiles()
    {
        // delete work files
        if(file_exists($this->workFile))
        {
            chmod($this->workFile, 0700); // rwx --- ---
            unlink($this->workFile);
        }
    }
    
    /**
     * Shlef registration end process
     *
     */
    public function endShelfregistration()
    {
        // Delete harvesting files.
        $this->deleteShelfregistrationFiles();
        
    }

    /**
     * Update progress file
     * 
     * @param string $url
     * @return bool
     */
    public function updateProgressFile()
    {
        if(!file_exists($this->workFile))
        {
            // Force exit
            return false;
            
        }
        chmod($this->workFile, 0700); // rwx --- ---
        $w_fp = fopen($this->tmpWorkFile, "w");
        $r_fp = fopen($this->workFile, "r");
        $cnt = 0;
        while(!feof($r_fp))
        {
            $r_line = fgets($r_fp);
            $r_line = str_replace("\r\n", "", $r_line);
            $r_line = str_replace("\n", "", $r_line);
            if($cnt != 0)
            {
                if(strlen($r_line) > 0){
                    // For second line below
                    fwrite($w_fp, $r_line."\n");
                }
            }
            $cnt++;
        }
        fclose($r_fp);
        fclose($w_fp);
        unlink($this->workFile);
        rename($this->tmpWorkFile, $this->workFile);
        chmod($this->workFile, 0700); // rwx --- ---
        
        return true;
    }
    
    /**
     * execute shelf registration
     *
     * @return boolean
     */
    public function executeShelfRegistration()
    {
        // get index_name
        $params = array();
        $query = "SELECT ". RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_NAME. ", ". RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_NAME_ENGLISH. 
                 " FROM ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. 
                 " WHERE ". RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID. " = ?;";
        $params[] = $this->selIndexId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) != 1)
        {
            return false;
        }
        if($this->Session->getParameter("_lang") == "japanese"){
            $selIndexName = $result[0]["index_name"];
        } else {
            $selIndexName = $result[0]["index_name_english"];
        }
        
        // -------------------------------------------
        // create transfer data
        // -------------------------------------------
        $createData = new Repository_Action_Edit_Cinii_Admin();
        $createData->Session = $this->Session;
        $createData->Db = $this->Db;
        $createData->selIdx_id = $this->selIndexId;
        $createData->selIdx_name = $selIndexName;
        $createData->entry_type = 2;
        $createData->shelfregistrationFlg_ = true;
        
        if($createData->execute() != "success")
        {
            $this->outputLog("Failed at Repository_Action_Edit_Cinii_Admin");
            return false;
        }
        // if item is not exist under index, entry is not execute
        $createResult = $this->Session->getParameter("els_result");
        if($createResult == "no_item")
        {
            $this->outputLog("no contents");
            //$this->outputLog($this->Session->getParameter("elsDebugLog"));
            return true;
        }
        // if translation to els form is failed, entry is not execute and output error log
        $allSuccess = $this->Session->getParameter("all_success");
        if($allSuccess == "false")
        {
            // output convert failed index_id
            $this->outputLog("There is convert failed item");
            return true;
        }
        
        // -------------------------------------------
        // check connect and transfer data
        // -------------------------------------------
        $checkConnect = new Repository_View_Edit_Cinii_Admin($this->Session, $this->Db);
        if($checkConnect->execute() != "success")
        {
            $this->outputLog("Failed server connection");
            return false;
        }
        
        // get infomation for entry from checkConnect
        $this->lab_connect = $checkConnect->lab_connect;
        
        // -------------------------------------------
        // entry transfered data
        // -------------------------------------------
        $result = $this->entryData();
        if($result != 'true')
        {
            $this->outputLog("Failed SCP file");
            return false;
        }
        
        return true;
    }
    
    /**
     * entry data
     * this method is the copy of action/edit/cinii/els/entry
     *
     * @return string: true -> success
     *                 lab_NG -> lab connect is success, entry to contents lab is failed
     *                 false -> failed
     */
    private function entryData()
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
            $lab_login_id = '';
            $lab_host = '';
            $lab_dir = '';
            $query = "SELECT param_name, param_value ".
                     "FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = ? OR ".
                     "param_name = ? OR ".
                     "param_name = ? OR ".
                     "param_name = ? OR ".
                     "param_name =? AND ".
                     "is_delete = ?; ";
            $params = array();
            $params[] = 'path_ssh';
            $params[] = 'path_scp';
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
                return 'false';
            }
            for($ii=0; $ii<count($result); $ii++){
                if(strcmp($result[$ii]["param_name"], 'path_ssh') == 0){
                    $path_ssh = $result[$ii]["param_value"];
                }
                if(strcmp($result[$ii]["param_name"], 'path_scp') == 0){
                    $path_scp = $result[$ii]["param_value"];
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
            $ssh_cmd_flg = false;
            $scp_cmd_flg = false;
            // Add file copy to contents lab 2010/06/24 A.Suzuki --start--
            $lab_ssh_cmd = '';
            $lab_scp_cmd = '';
            // Add file copy to contents lab 2010/06/24 A.Suzuki --end--
            if(strlen($path_ssh) > 0){
                if( file_exists($path_ssh.'ssh')){
                    $ssh_cmd_flg = true;
                    // make command
                    $lab_ssh_cmd = $path_ssh.'ssh ';
                } else if(file_exists($path_ssh.'plink') || file_exists($path_ssh.'PLINK.EXE')){
                    $ssh_cmd_flg = true;
                    // make command
                    $lab_ssh_cmd = $path_ssh.'plink ';
                }
            }
            if(strlen($path_scp) > 0){
                if( file_exists($path_scp.'scp')){
                    $scp_cmd_flg = true;
                    // make command
                    $lab_scp_cmd = $path_scp.'scp ';
                } else if(file_exists($path_scp.'pscp') || file_exists($path_scp.'PSCP.EXE')){
                    $scp_cmd_flg = true;
                    // make command
                    $lab_scp_cmd = $path_scp.'pscp ';
                }
            }
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
            $now_time = substr($now_time, 0, 15);   // YYYYMMDD_hhmmssの形式にする
            
            //Add Download zip file in tsv 2009/08/24 K.Ito --start--
            //$date = date("YmdHis");
            $query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return 'false';
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
            if($contents_lab_setting == "1" && $els_file_data != null){
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
            
            // ------------------------------------------------
            // SCP ELS file
            // ------------------------------------------------
            if($ssh_cmd_flg && $scp_cmd_flg){
                // Add file copy to contents lab 2010/06/21 A.Suzuki --start--
                // SCP to contents lab server

                if($contents_lab_setting == "1"){
                    if( strlen($lab_login_id) > 0 &&
                        strlen($prv_key_pass) > 0 &&
                        file_exists($prv_key_pass) &&
                        strlen($lab_scp_dir) > 0 &&
                        is_dir($lab_scp_dir) &&
                        strlen($lab_host) > 0 &&
                        strlen($lab_dir) > 0 &&
                        $this->lab_connect == 'true' ){
                        
                        $lab_scp_cmd .= ' -i '.$prv_key_pass.' ';   // private key
                        $lab_scp_cmd .= ' -r '.$lab_scp_dir.' ';    // local directory
                        $lab_scp_cmd .= $lab_login_id.'@'.$lab_host.':'.$lab_dir;   // remote file
                        $output = array();
                        $ret = null;
                        
                        $this->outputLog("SCP : $lab_scp_cmd");
                        
                        exec($lab_scp_cmd, $output, $ret);
                        // SSHでログインし、転送したファイルがあるか確認
                        $lab_ssh_cmd .= ' -i '.$prv_key_pass.' ';       // private key
                        $lab_ssh_cmd .= $lab_login_id.'@'.$lab_host;    // account
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
            if($lab_entry_flg == true){
                $this->entryRegisteredIndex($this->selIndexId);
            }
            // prevent double registration for ELS 2010/10/21 A.Suzuki --end--
            
            // end action
            $result = $this->exitAction();  // COMMIT
            if ( $result == false ){
                // error
                return 'false';
            }
            
            if($lab_entry_flg == true){
                // both success
                return 'true';
            } else if($this->lab_connect == "true" && $lab_entry_flg == false){
                // ELS: OK, lab: NG
                return 'lab_NG';
            } else {
                return 'false';
            }
        } catch ( RepositoryException $Exception) {
            //end action
            $this->exitAction(); // ROLLBACK
            // delete tmp folder
            $this->removeDirectory($tmp_dir);
            $this->removeDirectory($tmp_dir_lab);
            //error
            return 'false';
        }
    }
    
    /**
     * Entry registered index_id to parameter table
     *
     * @param $index_id int indexID
     */
    private function entryRegisteredIndex($index_id)
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
        if($result[0]['param_value']!=""){
            $contentsLabRegisteredIndex = explode(",", $result[0]['param_value']);
        } else {
            $contentsLabRegisteredIndex = array();
        }
        
        // check index_id
        if(!in_array($index_id, $contentsLabRegisteredIndex)){
            array_push($contentsLabRegisteredIndex, $index_id);
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
        $params[] = "contents_lab_registered_index";                   // param_name
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
     * for kill process
     *
     */
    public function killProcess()
    {
        // kill process of executing shelfregistration
        if(file_exists($this->workFile))
        {
            // rewrite work file by void
            $fp = fopen($this->workFile, "w");
            fwrite($fp, "");
            fclose($fp);
            
            // remove work file by status is 'end' in repository_action_common_cinii
            $cnt = 0;
            
            // trial time is 25s
            // for set max_execution_time is 30s in repository_view_edit_cinii_admin
            while($cnt < 5)
            {
                sleep(5);
                
                if(!file_exists($this->workFile))
                {
                    break;
                }
                $cnt++;
            }
        }
        
        // kill process of stopping shelfregistration
        $this->deleteShelfregistrationFiles();
        
    }
    
    /**
     * get ELS convert failed index_id list
     *
     * @return array: index_ids
     */
    public function getConvertFailedIndexList()
    {
        $indexList = array();
        
        if(file_exists($this->convertFailedIndexListFile))
        {
            $fp = fopen($this->convertFailedIndexListFile, "r");
            while(!feof($fp))
            {
                $line = fgets($fp);
                $line = str_replace("\r\n", "\n", $line);
                $line = str_replace("\n", "", $line);
                if(strlen($line) > 0)
                {
                    $idx = explode("\t", $line);
                    if($idx[1] != "no contents")
                    {
                        array_push($indexList, $idx[0]);
                    }
                }
            }
            fclose($fp);
        }
        
        return $indexList;
    }
    
    /**
     * create convert failed index list file
     *
     * @return boolean
     */
    public function createConvertFailedIndexList()
    {
        $fp = fopen($this->convertFailedIndexListFile, "w");
        if($fp != false) {
            fwrite($fp, "");
            fclose($fp);
            
            chmod($this->convertFailedIndexListFile, 0700);
            
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * delete convert failed index list when progress file is not exists
     *
     */
    public function deleteConvertFailedIndexList()
    {
        if(file_exists($this->workFile) == false) {
            unlink($this->convertFailedIndexListFile);
        }
    }
    
    /**
     * ログ出力
     *
     * @param string $addMsg
     */
    public function outputLog($addMsg="")
    {
        $fp = fopen($this->convertFailedIndexListFile, "a");
        if(!$fp)
        {
            return;
        }
        fwrite($fp, $this->selIndexId."\t".$addMsg."\n");
        fclose($fp);
    }
}
?>
