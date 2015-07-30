<?php
// --------------------------------------------------------------------
//
// $Id: Install.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class Repository_Action_main_Install extends RepositoryAction
{
    function execute()
    {
        try { 
            // init action
            $this->initAction();
            
            ///////////////////////////////
            // weko directry
            ///////////////////////////////
            if( !file_exists(BASE_DIR."/htdocs/weko/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/", 0300 );
            
            //////////////////////////////
            // SWORD
            //////////////////////////////
            // SWORD CGI copy to "netcommons2.0[root]/htdocs/sword/" action
            // current directory is "netcommons2.0[root]/htdocs/"
            // Add filter update 2014/02/14 R.Matsuura --start--
            $this->copyDirectory(WEBAPP_DIR."/modules/repository/files/sword", BASE_DIR."/htdocs/weko/sword");
            chmod(BASE_DIR."/htdocs/weko/sword/servicedocument.php", 0600);
            chmod(BASE_DIR."/htdocs/weko/sword/deposit.php", 0600);
            chmod(BASE_DIR."/htdocs/weko/sword/utils.php", 0600);
            chmod(BASE_DIR."/htdocs/weko/sword/serviceitemtype.php", 0600);
            // Add filter update 2014/02/14 R.Matsuura --end--
            
            // Add make upload folder for repository 2008/11/26 Y.Nakao --start--
            if( !file_exists(WEBAPP_DIR."/uploads/repository/") ){
                // make dir
                mkdir ( WEBAPP_DIR."/uploads/repository/", 0777 );
            }
            chmod( WEBAPP_DIR."/uploads/repository/", 0777 );
            // Add make upload folder for repository 2008/11/26 Y.Nakao --end--
            
            
            //////////////////////////////
            // HTML HELP
            //////////////////////////////
            // Add copy HTML HELP file  2009/10/28 Y.Nakao --start--
            // check directry
            if( !file_exists(BASE_DIR."/htdocs/weko/help/") ){
                mkdir ( BASE_DIR."/htdocs/weko/help/", 0300);
            }
            // help copy
            $this->copyDirectory(WEBAPP_DIR."/modules/repository/files/help", BASE_DIR."/htdocs/weko/help");

            // Add copy HTML HELP file  2009/10/28 Y.Nakao --end--
            
            //////////////////////////////
            // Extended Thumbnail 
            //////////////////////////////
            // Add copy Gallery View file  2013/01/07 R.Matsuura --start--
            // check directry
            if( !file_exists(BASE_DIR."/htdocs/weko/galleryview/") ){
                mkdir ( BASE_DIR."/htdocs/weko/galleryview/", 0300);
            }
            // galleryview copy
            $this->copyDirectory(WEBAPP_DIR."/modules/repository/files/galleryview", BASE_DIR."/htdocs/weko/galleryview");
            // Add copy Gallery View file  2013/01/07 R.Matsuura --start--
            
            ///////////////////////////////
            // sitemap
            ///////////////////////////////
            // check directry
            if( !file_exists(BASE_DIR."/htdocs/weko/sitemaps/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/sitemaps/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/sitemaps/", 0300 );
            
            // Modify Directory specification K.Matsuo 2011/9/1 -----end-----
            ///////////////////////////////
            // weko logs directry
            ///////////////////////////////
            if( !file_exists(WEBAPP_DIR."/logs/weko/") ){
                // make dir
                mkdir ( WEBAPP_DIR."/logs/weko/", 0300 );
            }
            chmod ( WEBAPP_DIR."/logs/weko/", 0300 );
            // for sword logs directry
            if( !file_exists(WEBAPP_DIR."/logs/weko/sword/") ){
                // make dir
                mkdir ( WEBAPP_DIR."/logs/weko/sword/", 0300 );
            }
            chmod ( WEBAPP_DIR."/logs/weko/sword/", 0300 );
            
            ///////////////////////////////
            // log
            ///////////////////////////////
            // check log folder exist
            if( !file_exists(WEBAPP_DIR ."/logs/weko/logdump" ) ){
                // make log folder
                mkdir(WEBAPP_DIR ."/logs/weko/logdump");
            }
            chmod ( WEBAPP_DIR."/logs/weko/logdump", 0300 );
            // check log folder exist
            if( !file_exists(WEBAPP_DIR ."/logs/weko/logreport" ) ){
                // make log folder
                mkdir(WEBAPP_DIR ."/logs/weko/logreport");
            }
            chmod ( WEBAPP_DIR."/logs/weko/logreport", 0300 );
            
            ///////////////////////////////
            // Harvesting
            ///////////////////////////////
            // check harvesting folder exist
            if( !file_exists(WEBAPP_DIR ."/logs/weko/harvesting" ) ){
                // make harvesting folder
                mkdir(WEBAPP_DIR ."/logs/weko/harvesting");
            }
            chmod ( WEBAPP_DIR."/logs/weko/harvesting", 0300 );
            
            ///////////////////////////////
            // Feedback mail
            ///////////////////////////////
            // check feedback folder exist
            if( !file_exists(WEBAPP_DIR ."/logs/weko/feedback" ) ){
                // make feedback folder
                mkdir(WEBAPP_DIR ."/logs/weko/feedback");
            }
            chmod ( WEBAPP_DIR."/logs/weko/feedback", 0300 );
            
            ///////////////////////////////
            // FLASH
            ///////////////////////////////
            // Flash frame and option file copy to "netcommons2[root]/htdocs/weko/flash" action
            // current directory is "netcommons2/htdocs/"
            // check directry
            // Modify Directory specification K.Matsuo 2011/9/1 -----start-----
            if( !file_exists(BASE_DIR."/htdocs/weko/flash/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/flash/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/flash/", 0300 );
            // Flash frame copy
            copy ( WEBAPP_DIR."/modules/repository/files/flash/WekoFrame.swf", BASE_DIR."/htdocs/weko/flash/WekoFrame.swf" );
            chmod(BASE_DIR."/htdocs/weko/flash/WekoFrame.swf", 0600);
            // option file copy
            copy ( WEBAPP_DIR."/modules/repository/files/flash/OptionSetting.xml", BASE_DIR."/htdocs/weko/flash/OptionSetting.xml" );
            chmod(BASE_DIR."/htdocs/weko/flash/OptionSetting.xml", 0600);   
            // Modify Directory specification K.Matsuo 2011/9/1 -----end-----
            
            ///////////////////////////////
            // e-book viewer
            ///////////////////////////////
            // Fix file download check. delete ebook viewer action. Y.Nakao 2013/04/19
            
            ///////////////////////////////
            // multimedia viewer
            ///////////////////////////////
            // WekoViewerForMultimedia copy to "netcommons2[root]/htdocs/weko/multimedia" action
            // current directory is "netcommons2/htdocs/"
            // check directry
            // Add multimedia support 2012/08/27 T.Koyasu -start-
            if( !file_exists(BASE_DIR."/htdocs/weko/multimedia/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/multimedia/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/multimedia/", 0300 );
            // multimedia viewer copy
            copy ( WEBAPP_DIR."/modules/repository/files/multimedia/WekoViewerForMultimedia.swf", BASE_DIR."/htdocs/weko/multimedia/WekoViewerForMultimedia.swf" );
            chmod(BASE_DIR."/htdocs/weko/multimedia/WekoViewerForMultimedia.swf", 0600);
            // Add multimedia support 2012/08/27 T.Koyasu -end-
            
            //////////////////////////////
            // DB update
            //////////////////////////////
            $user_id = $this->Session->getParameter("_user_id");
            if($user_id == null || $user_id == ""){
                // NC2 Install now
                $query = "SELECT user_id FROM ". DATABASE_PREFIX . "users ";
                $user = $this->Db->execute($query);
                $user_id = $user[0]["user_id"];
            }
            // update DB ins_user mod_user ins_date mod_date
            $set_query = "SET ins_user_id = ?, ".
                         "mod_user_id = ?, ".
                         "ins_date = ?, ".
                         "mod_date = ?; ";
            $params = array();
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = $this->TransStartDate;
            $params[] = $this->TransStartDate;
    
            // update index table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_index ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            // update license_master table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_license_master ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            // update parameter table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_parameter ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            // update item_type table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_item_type ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            $retRef = $this->Db->execute($query, $params);
            // update item_attr_type table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_item_attr_type ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            // update item_attr_candidate table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_item_attr_candidate ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            // update repository_prefix table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_prefix ".$set_query;
            $retRef = $this->Db->execute($query, $params);
            if($retRef === false){
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            
            // Add advanced search 2013/11/27 R.Matsuura --start--
            // search mroonga
            $query = "SHOW ENGINES";
            $engines = $this->dbAccess->executeQuery($query);
            $isMroongaExist = false;
            for($cnt = 0; $cnt < count($engines); $cnt++)
            {
                if($engines[$cnt]["Engine"] == "Mroonga" || $engines[$cnt]["Engine"] == "mroonga")
                {
                    $isMroongaExist = true;
                    break;
                }
            }
            if($isMroongaExist)
            {
                $tableNameArray = array(
                                        "repository_search_allmetadata",
                                        "repository_search_filedata",
                                        "repository_search_title",
                                        "repository_search_author",
                                        "repository_search_keyword",
                                        "repository_search_niisubject",
                                        "repository_search_ndc",
                                        "repository_search_ndlc",
                                        "repository_search_bsh",
                                        "repository_search_ndlsh",
                                        "repository_search_mesh",
                                        "repository_search_ddc",
                                        "repository_search_lcc",
                                        "repository_search_udc",
                                        "repository_search_lcsh",
                                        "repository_search_description",
                                        "repository_search_publisher",
                                        "repository_search_contributor",
                                        "repository_search_type",
                                        "repository_search_format",
                                        "repository_search_identifier",
                                        "repository_search_uri",
                                        "repository_search_fulltexturl",
                                        "repository_search_selfdoi",
                                        "repository_search_isbn",
                                        "repository_search_issn",
                                        "repository_search_ncid",
                                        "repository_search_pmid",
                                        "repository_search_doi",
                                        "repository_search_naid",
                                        "repository_search_ichushi",
                                        "repository_search_jtitle",
                                        "repository_search_language",
                                        "repository_search_relation",
                                        "repository_search_coverage",
                                        "repository_search_rights",
                                        "repository_search_textversion",
                                        "repository_search_grantid",
                                        "repository_search_degreename",
                                        "repository_search_grantor",
                                        "repository_search_external_searchword");
                for($tableCnt = 0; $tableCnt < count($tableNameArray); $tableCnt++)
                {
                    $this->setMroongaEngine($tableNameArray[$tableCnt]);
                }
            }
            // Add advanced search 2013/11/27 R.Matsuura --end--
            
            //////////////////////////////
            // Prefix
            //////////////////////////////
            // get prefix
            // Add new prefix 2014/01/15 T.Ichikawa --start--
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            $retRef = $repositoryHandleManager->registerYHandlePrefixByPriKey();
            // Fix when install, IDServer key is empty. so this time was not error. 2014/03/15 Y.Nakao
            // Add new prefix 2014/01/15 T.Ichikawa --end--
            //////////////////////////////
            // make file contents save directory
            //////////////////////////////
            // Add separate file from DB 2009/04/17 Y.Nakao --start--
            // All BLOB file write to file server
            $contents_path = $this->getFileSavePath("file");
            if(strlen($contents_path) == 0){
                // default directory
                $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
                if( !(file_exists($contents_path)) ){
                    mkdir ( $contents_path, 0777);
                }
                chmod($contents_path, 0777 );
            }
            // check directory exists 
            $pointer = fopen(WEBAPP_DIR.'/logs/weko/install_log.txt', "w");
            fputs($pointer, 'config : '.$contents_path."\n");
            if( file_exists($contents_path) ){
                // check this folder write right.
                $ex_file = fopen ($contents_path.'/test.txt', "w");
                if( $ex_file === false ){
                    // folder is not find, file save at default directory
                    $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
                    fputs($pointer, 'default directory1 : '.$contents_path."\n");
                    if( !(file_exists($contents_path)) ){
                        mkdir ( $contents_path, 0777);
                    }
                    chmod($contents_path, 0777 );
                } else {
                    fclose($ex_file);
                    unlink($contents_path.'/test.txt');
                }
            } else {
                
                if( !(file_exists($contents_path)) ){
                    mkdir ( $contents_path, 0777);
                }
                chmod($contents_path, 0777 );
                
                if(!(file_exists($contents_path))){
                    // folder is not find, file save at default directory
                    $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
                    fputs($pointer, 'default directory : '.$contents_path."\n");
                    if( !(file_exists($contents_path)) ){
                        mkdir ( $contents_path, 0777);
                    }
                    chmod($contents_path, 0777 );
                }
            }
            fputs($pointer, 'file save : '.$contents_path."\n");
            fclose($pointer);
            
            //////////////////////////////
            // make flash contents save directory
            //////////////////////////////
            // Add flash save directory 2010/01/06 A.Suzuki --start--
            $contents_path = $this->getFileSavePath("flash");
            if(strlen($contents_path) == 0){
                // default directory
                $contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
                if( !(file_exists($contents_path)) ){
                    mkdir ( $contents_path, 0777);
                }
                chmod($contents_path, 0777 );
            }
            // check directory exists 
            $pointer = fopen(WEBAPP_DIR.'/logs/weko/install_log.txt', "w");
            fputs($pointer, 'config : '.$contents_path."\n");
            if( file_exists($contents_path) ){
                // check this folder write right.
                $ex_file = fopen ($contents_path.'/test.txt', "w");
                if( $ex_file === false ){
                    // folder is not find, file save at default directory
                    $contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
                    fputs($pointer, 'default directory1 : '.$contents_path."\n");
                    if( !(file_exists($contents_path)) ){
                        mkdir ( $contents_path, 0777);
                    }
                    chmod($contents_path, 0777 );
                } else {
                    fclose($ex_file);
                    unlink($contents_path.'/test.txt');
                }
            } else {
                if( !(file_exists($contents_path)) ){
                    mkdir ( $contents_path, 0777);
                }
                chmod($contents_path, 0777 );
                
                if(!(file_exists($contents_path))){
                    // folder is not find, file save at default directory
                    $contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
                    fputs($pointer, 'default directory : '.$contents_path."\n");
                    if( !(file_exists($contents_path)) ){
                        mkdir ( $contents_path, 0777);
                    }
                    chmod($contents_path, 0777 );
                }
            }
            fputs($pointer, 'flash save : '.$contents_path."\n");
            fclose($pointer);
            // Add flash save directory 2010/01/06 A.Suzuki --end--
            
            //////////////////////////////
            // set install WEKO version
            //////////////////////////////
            // get now installing WEKO version
            $query = "SELECT version FROM ". DATABASE_PREFIX ."modules ".
                     "WHERE action_name = 'repository_view_main_item_snippet' AND ".
                     "edit_action_name = 'repository_view_edit_itemtype_setting'; ";
            $retRef = $this->Db->execute($query);
            if($retRef === false || count($retRef)!=1){
                $errMsg = $this->Db->ErrorMsg();
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            $weko_version = $retRef[0]["version"];
            // set weko version to parameter table
            $query = "UPDATE ".DATABASE_PREFIX ."repository_parameter ".
                     "SET param_value = '". $weko_version ."' ".
                     "WHERE param_name = 'WEKO_version';";
            $retRef = $this->Db->execute($query);
            if($retRef === false){
                $errMsg = $this->Db->ErrorMsg();
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            
            ///////////////////////////////
            // OpenSearch
            ///////////////////////////////
            // check directry
            // Modify Directory specification K.Matsuo 2011/8/25 -----start-----
            if( !file_exists(BASE_DIR."/htdocs/weko/opensearch/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/opensearch/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/opensearch/", 0300 );
            
            // make OpenSearch description document
            require_once WEBAPP_DIR."/modules/repository/opensearch/Opensearch.class.php";
            $openSearch = new Repository_Opensearch();
            $openSearch->Db = $this->Db;
            $handle = fopen(BASE_DIR."/htdocs/weko/opensearch/description.xml","w");
            $xml = $openSearch->getDescription();
            fwrite($handle, $xml);
            fclose($handle);
            // Modify Directory specification K.Matsuo 2011/8/25 -----end-----
            
            // end action
            $this->exitAction();
            
            return true;
        
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",
                "execute",
                $Exception->getCode(),
                $Exception->getMessage(),
                $Exception->getDetailMsg() );
            $this->exitAction();
            return false;
        }
    }
    
    /**
     * copy directory
     *
     * @param string $copy_dir to copy
     * @param string $org_dir from copy
     */
    function copyDirectory($copy_dir, $org_dir){
        if( file_exists($copy_dir) ){
            if ($handle = opendir("$copy_dir")) {
                while (false !== ($file = readdir($handle))) {
                    if (strpos($file, "svn")===false && $file != "." && $file != "..") {
                        if (is_dir("$copy_dir/$file")) {
                            // directory
                            if( !file_exists("$org_dir/$file") ){
                                mkdir ( "$org_dir/$file", 0300);
                            }
                            $this->copyDirectory("$copy_dir/$file", "$org_dir/$file");
                        } else {
                            // file
                            if(!file_exists(dirname("$org_dir/$file"))){
                                // make directory
                                mkdir ( dirname("$org_dir/$file"), 0300);
                            }
                            copy("$copy_dir/$file", "$org_dir/$file");
                            chmod("$org_dir/$file", 0644);
                        }
                    }
                }
                closedir($handle);
            }
        }
    
    }
    
    /**
     * set mroonga engine
     *
     * @param string $tableName
     */
    private function setMroongaEngine($tableName)
    {
        $query = "ALTER TABLE ".DATABASE_PREFIX. $tableName. " ".
                 "ENGINE = mroonga ".
                 "COMMENT = 'engine \"MyISAM\"' ;";
        $this->dbAccess->executeQuery($query);
        return true;
    }
}
?>
