<?php
// --------------------------------------------------------------------
//
// $Id: Update.class.php 58278 2015-09-30 09:33:47Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryProcessUtility.class.php';

class Repository_Action_Main_Update extends RepositoryAction
{
    var $Db = null;

    /**
     * recursive proccessing flg list
     *
     * @var array(Key => Value)
     */
    private $recursiveProcessingFlgList = array();

    // key of recursive processing
    const KEY_REPOSITORY_SEARCH_TABLE_PROCESSING = "keyRepositorySearchTableProccessing";
    const KEY_REPOSITORY_INDEX_MANAGER = "KeyRepositoryIndexManager";
    const KEY_REPOSITORY_CLEANUP_DELETED_FILE = "KeyRepositoryCleanupDeletedFile";
    const KEY_REPOSITORY_SEARCH_LOG = "KeyRepositorySearchLog";
    const KEY_REPOSITORY_ELAPSEDTIME_LOG = "KeyRepositoryElapsedtimeLog";
    const KEY_REPOSITORY_EXCLUDE_LOG = "KeyRepositoryExcludeLog";

    function execute()
    {
        try {
            // init action
            $this->initAction();

            // Add JuNii2 revision 2013/09/17 R.Matsuura --start--
            // remove parameter from session
            $this->Session->removeParameter("view_tree_html");
            // Add JuNii2 revision 2013/09/17 R.Matsuura --end--

            $this->recursiveProcessingFlgList = array();

            ///////////////////////////////
            // weko directry
            ///////////////////////////////
            // Modify Directory specification K.Matsuo 2011/9/1 -----start------
            if( !file_exists(BASE_DIR."/htdocs/weko/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/", 0300 );
            // Modify Directory specification K.Matsuo 2011/9/1 -----end------

            ///////////////////////////////
            // SWORD
            ///////////////////////////////
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
            // Modify Directory specification K.Matsuo 2011/9/1 -----start------
            if( !file_exists(BASE_DIR."/htdocs/weko/help/") ){
                mkdir ( BASE_DIR."/htdocs/weko/help/", 0300);
            }
            // help copy
            $this->copyDirectory(WEBAPP_DIR."/modules/repository/files/help", BASE_DIR."/htdocs/weko/help");
            // Modify Directory specification K.Matsuo 2011/9/1 -----end------

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
            // Modify Directory specification K.Matsuo 2011/9/1 -----start------
            if( !file_exists(BASE_DIR."/htdocs/weko/sitemaps/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/sitemaps/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/sitemaps/", 0300 );

            ///////////////////////////////
            // opensearch
            ///////////////////////////////
            // check directry
            if( !file_exists(BASE_DIR."/htdocs/weko/opensearch/") ){
                // make dir
                mkdir ( BASE_DIR."/htdocs/weko/opensearch/", 0300);
            }
            chmod ( BASE_DIR."/htdocs/weko/opensearch/", 0300 );

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
            // Modify Directory specification K.Matsuo 2011/9/1 -----start------
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
            // Modify Directory specification K.Matsuo 2011/9/1 -----end------

            ///////////////////////////////
            // e-book viewer
            ///////////////////////////////
            // Fix file download check Y.Nakao 2013/04/19 --start--
            // delete for Ebook viewer action
            // delete ebook viewer template
            $ebookTemplate = WEBAPP_DIR.'/modules/repository/templates/default/repository_ebook_viewer.html';
            if(file_exists($ebookTemplate))
            {
                unlink($ebookTemplate);
            }
            // delete ebook viewer (FLASH) folder
            $this->removeDirectory(HTDOCS_DIR.'/weko/ebook');
            $this->removeDirectory(WEBAPP_DIR.'/modules/repository/files/ebook');
            // Fix file download check Y.Nakao 2013/04/19 --end--

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

            /////////////////////////////////////
            // check WEKO version
            ////////////////////////////////////
            /*
             ・例えば，バージョンは，小数点2ケタまでしか書かないようにする．
             ・バージョン1.3x100=130から1.43x100=143へのバージョンアップの場合
            switch($old_version){
                //case 140:
                    //$sql = "ALTER TABLE …";
                    //$result = SQL実行;
                //case 141:
                    //$sql = "ALTER TABLE …";
                    //$result = SQL実行;
                //case 142:
                    //$sql = "ALTER TABLE …";
                    //$result = SQL実行;
                default :
                    return true;
            }
            */
            // get user id
            $user_id = $this->Session->getParameter("_user_id");

            // get old WEKO version
            $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = 'WEKO_version'; ";
            $retRef = $this->Db->execute($query);
            if($retRef === false || count($retRef)!=1){
                $errMsg = $this->Db->ErrorMsg();
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
            $old_version = str_replace(".", "", $retRef[0]["param_value"]);
            switch($old_version){
                case 133:
                    // Add for workearea 2008/01/29 Y.Nakao --start--
                    // アイテムの「登録中」ステータスのためにカラムの属性を変更
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item ".
                             "CHANGE `review_status` `review_status` VARCHAR( 2 ) NOT NULL DEFAULT '0' ";
                    // Add for workearea 2008/01/29 Y.Nakao --end--
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add Column "hidden" to repository_item_attr_type 2009/02/18 A.Suzuki --start--
                    // メタデータオプション"hidden"追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item_attr_type ".
                             "ADD `hidden` INT NOT NULL default 0 AFTER `list_view_enable`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add Column "hidden" to repository_item_attr_type 2009/02/18 A.Suzuki --end--
                case 134:
                    // Add sort setting 2009/03/18 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('sort_disp', '1|2|3|4|5|6|7|8|9|10|11|12|13|14', '検索結果表示で表示するソート条件', ?, ?, '0', ?, ?, '', 0), ".
                             "('sort_not_disp', '', '検索結果表示で表示しないソート条件', ?, ?, '0', ?, ?, '', 0), ".
                             "('sort_disp_default', '7', '検索結果表示のデフォルトソート条件', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    // sort_disp
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    // sort_not_disp
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    // sort_disp_default
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add sort setting 2009/03/18 A.Suzuki --end--

                case 135:
                case 136:
                    // Add default_list_view_num 2009/03/27 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('default_list_view_num', '20', '検索結果表示のデフォルト表示件数', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add default_list_view_num 2009/03/27 A.Suzuki --end--
                case 137:
                    // set timeout for 48 hours
                    ini_set('max_execution_time', '172800');

                    // Add separate file from DB 2009/04/17 Y.Nakao --start--
                    $pointer=fopen(WEBAPP_DIR.'/logs/weko/update_start_log.txt', "w");
                    // All BLOB file write to file server
                    $contents_path = $this->getFileSavePath("file");
                    if(strlen($contents_path) == 0){
                        // default directory
                        $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
                        if( !(file_exists($contents_path)) ){
                            mkdir ( $contents_path, 0777);
                        }
                    }
                    fputs($pointer, 'config : '.$contents_path."\n");
                    // check directory exists
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
                    // make file from BLOB
                    $query = "SELECT count(*) ".
                            " FROM ".DATABASE_PREFIX."repository_file; ";
                    //      " WHERE is_delete = 0; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        fclose($pointer);
                        $errMsg = $this->Db->ErrorMsg();
                        $pointer2=fopen(WEBAPP_DIR.'/logs/weko/update_error_log.txt', "w");
                        fputs($pointer2, 'select file record count error : '.$contents_path."\n");
                        fputs($pointer2, 'query : '.$query."\n");
                        fputs($pointer2, $errMsg."\n");
                        fclose($pointer2);
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $total_file = $retRef[0]['count(*)'];
                    //fputs($pointer, 'total file num : '.$total_file."\n");
                    $cnt_file = 0;
                    while($total_file >= $cnt_file){
                        $pointer=fopen(WEBAPP_DIR.'/logs/weko/update_log.txt', "w");
                        $query = "SELECT item_id, attribute_id, file_no, extension, file ".
                                " FROM ".DATABASE_PREFIX."repository_file ".
                        //      " WHERE is_delete = 0 ".
                                " ORDER BY item_id ".
                                " LIMIT ".$cnt_file.", 10; ";
                        fputs($pointer, 'query : '.$query."\n");
                        $retRef = $this->Db->execute($query);
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                            fclose($pointer);
                            fputs($pointer2, 'select file record error : '.$contents_path."\n");
                            fputs($pointer2, 'query : '.$query."\n");
                            fputs($pointer2, $errMsg."\n");
                            fclose($pointer2);
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                        fputs($pointer, 'count : '.count($retRef)."\n");
                        for($ii=0; $ii<count($retRef); $ii++){
                            $file_name = $retRef[$ii]['item_id'].'_'.
                                        $retRef[$ii]['attribute_id'].'_'.
                                        $retRef[$ii]['file_no'].'.'.
                                        $retRef[$ii]['extension'];
                            $output_file = $contents_path.'/'.$file_name;
                            fputs($pointer, 'make file : '.$output_file);
                            $result = $this->createFile($output_file, $retRef[$ii]['file']);
                            if($result === false){
                                fclose($pointer);
                                fputs($pointer2, 'count : '.count($retRef)."\n");
                                fputs($pointer2, 'make file : '.$output_file);
                                fputs($pointer2, " : NG \n");
                                fclose($pointer2);
                                // Rollback
                                $this->failTrans();
                                throw $exception;
                            }
                            fputs($pointer, " : success\n");
                        }
                        $cnt_file += 10;
                        fputs($pointer, "SUCCESS\n");
                        fclose($pointer);
                    }
                    // 一時退避
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_file ".
                            " DROP file ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add pdf prev id
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                             "ADD `prev_id` INT NOT NULL default 0 AFTER `extension`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add separate file from DB 2009/04/17 Y.Nakao --end--
                case 138:
                    // Add sort order "publication year" 2009/06/25 A.Suzuki --start--
                    $query = "SELECT param_value ".
                             "FROM ". DATABASE_PREFIX. "repository_parameter ".
                             "WHERE param_name = 'sort_disp' ".
                             "AND is_delete = 0;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    if($retRef[0]['param_value'] != ""){
                        $sort_disp = $retRef[0]['param_value']."|15|16";
                    } else {
                        $sort_disp = "15|16";
                    }

                    $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".   // パラメタテーブル
                             "SET param_value = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ? ".
                             "WHERE param_name = ?; ";
                    $params = array();
                    $params[] = $sort_disp;
                    $params[] = $user_id;
                    $params[] = $this->TransStartDate;
                    $params[] = 'sort_disp';
                    // UPDATE実行
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add sort order "publication year" 2009/06/25 A.Suzuki --end--

                    // Add sort order default for keyword 2009/06/29 A.Suzuki --start--
                    $query = "SELECT param_value, explanation ".
                             "FROM ". DATABASE_PREFIX. "repository_parameter ".
                             "WHERE param_name = 'sort_disp_default' ".
                             "AND is_delete = 0;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // keyword検索のデフォルトは"出版年(降順)"に設定
                    $sort_default = $retRef[0]['param_value']."|16";
                    $explanation = $retRef[0]['explanation']."(index検索|keyword検索)";

                    $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".   // パラメタテーブル
                             "SET param_value = ?, ".
                             "explanation = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ? ".
                             "WHERE param_name = ?; ";
                    $params = array();
                    $params[] = $sort_default;
                    $params[] = $explanation;
                    $params[] = $user_id;
                    $params[] = $this->TransStartDate;
                    $params[] = 'sort_disp_default';
                    // UPDATE実行
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add sort order default for keyword 2009/06/29 A.Suzuki --end--

                    // Add currency_setting 2009/06/26 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('currency_setting', '0', '通貨単位設定(0:\\\, 1:$)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add currency_setting 2009/06/26 A.Suzuki --end--

                    // Add select_language 2009/07/01 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('select_language', '0', 'WEKOモジュール内に言語選択を表示するか否か(0:表示しない, 1:表示する)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add select_language 2009/07/01 A.Suzuki --end--

                case 139:
                    // Add RSS icon display select 2009/07/06 A.Suzuki --start--
                    // "rss_display" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                             "ADD `rss_display` INT NOT NULL default 0 AFTER `display_more`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add RSS icon display select 2009/07/06 A.Suzuki --end--

                case 1310:
                    // Add alternative language data 2009/07/17 A.Suzuki --start--
                    // repository_item : "title_english" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item ".
                             "ADD `title_english` TEXT NOT NULL AFTER `title`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_item : "title" 属性変更 [TEXT NOT NULL => TEXT default '']
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item ".
                             "MODIFY `title` TEXT NOT NULL ;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_item : "serch_key_english" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item ".
                             "ADD `serch_key_english` TEXT NOT NULL AFTER `serch_key`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_item_attr_type : "display_lang_type" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_item_attr_type ".
                             "ADD `display_lang_type` TEXT NOT NULL AFTER `dublin_core_mapping`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_biblio_info : "biblio_name_english" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_biblio_info ".
                             "ADD `biblio_name_english` TEXT NOT NULL AFTER `biblio_name`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_ranking : "disp_name_english" 追加
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_ranking ".
                             "ADD `disp_name_english` TEXT NOT NULL AFTER `disp_name`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add alternative language data 2009/07/17 A.Suzuki --end--

                case 1311:
                    // Add alternative language setting 2009/08/11 A.Suzuki --start--
                    // "alternative_language" 追加
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('alternative_language', 'japanese:0,english:0', '他言語表示をするか否か(0:表示しない, 1:表示する)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add alternative language setting 2009/08/11 A.Suzuki --end--

                case 1312:
                    // Add parameter for ELS auto entry 2009/08/31 Y.Nakao --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter VALUE ".
                             " ('path_ssh', '', 'SSHコマンドの実行パス', ?, ?, '0', ?, ?, '', 0), ".
                             " ('path_scp', '', 'SCPコマンドの実行パス', ?, ?, '0', ?, ?, '', 0), ".
                             " ('els_auto', 'true', 'true:ELSの自動登録を行う false:ELSの自動登録を行わない', ?, ?, '0', ?, ?, '', 0), ".
                             " ('els_login_id', '', 'ELS用ログインID', ?, ?, '0', ?, ?, '', 0), ".
                             " ('els_host', 'www.ixsq.nii.ac.jp', 'ELS自動登録用ホスト名', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add parameter for ELS auto entry 2009/08/31 Y.Nakao --end--

                case 1313:
                    // Add table "repository_supple" 2009/07/16 A.Suzuki --start--
                    $query = "CREATE TABLE ". DATABASE_PREFIX ."repository_supple (".
                             " `item_id` INT, ".
                             " `item_no` INT, ".
                             " `attribute_id` INT, ".
                             " `supple_no` INT, ".
                             " `item_type_id` INT, ".
                             " `supple_weko_item_id` INT, ".
                             " `supple_title` TEXT, ".
                             " `supple_title_en` TEXT, ".
                             " `uri` TEXT, ".
                             " `supple_item_type_name` TEXT, ".
                             " `mime_type` TEXT, ".
                             " `file_id` INT, ".
                             " `supple_review_status` INT(1) NOT NULL default 0, ".
                             " `supple_review_date` VARCHAR(23), ".
                             " `supple_reject_status` INT(1) NOT NULL default 0, ".
                             " `supple_reject_date` VARCHAR(23), ".
                             " `supple_reject_reason` TEXT, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                             " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`item_id`, `item_no`, `attribute_id`, `supple_no`), ".
                             " KEY `item_id` (`item_id`, `item_no`, `supple_no`), ".
                             " KEY `attribute_id` (`attribute_id`, `item_type_id`) ".
                             ") ENGINE=innodb;";
                    $result = $this->Db->execute($query);
                    if($result === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add table "repository_supple" 2009/07/16 A.Suzuki --end--

                    // Add supple WEKO url 2009/08/28 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('supple_weko_url', '', 'サプリWEKOのURL', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add supple WEKO url 2009/08/28 A.Suzuki --end--

                    // Add supple WEKO review flag 2009/09/24 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('review_flg_supple', '1', 'サプリコンテンツの査読・承認を行うか否か (1:行う, 0:行わない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add supple WEKO review flag 2009/09/24 A.Suzuki --end--

                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('review_mail_flg', '0', '査読通知メールを送信するか否か(1:送信する, 0:送信しない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('review_mail', '', '査読通知メール送信先', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_users ( ".
                            " `user_id` VARCHAR(40), ".
                            " `contents_mail_flg` INT NOT NULL default 0, ".
                            " `supple_mail_flg` INT NOT NULL default 0, ".
                            " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                            " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                            " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                            " `ins_date` VARCHAR(23), ".
                            " `mod_date` VARCHAR(23), ".
                            " `del_date` VARCHAR(23), ".
                            " `is_delete` INT(1), ".
                            " PRIMARY KEY(`user_id`) ".
                            " ) ENGINE=innodb; ";
                    $params = array();
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                case 140:
                    // Add DC mapping for default itemtype 2009/11/10 A.Suzuki --start--
                    // fix mapping miss
                    $query = "UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                             "SET junii2_mapping = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ? ".
                             "WHERE item_type_id = ? ".
                             "AND attribute_id = ? ".
                             "AND junii2_mapping = ? ".
                             "AND is_delete = ?;";
                    $params = array();
                    $params[] = "NCID";
                    $params[] = $user_id;
                    $params[] = $this->TransStartDate;
                    $params[] = 10009;
                    $params[] = 14;
                    $params[] = "issn";
                    $params[] = 0;
                    // UPDATE実行
                    $result = $this->Db->execute($query, $params);
                    if($result === false || count($result)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // get default itemtype
                    // item_type_id : 10001~10009
                    $query = "SELECT item_type_id, attribute_id, junii2_mapping, dublin_core_mapping ".
                             "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                             "WHERE item_type_id IN (?, ?, ?, ?, ?, ?, ?, ?, ?) ".
                             "AND is_delete = ? ;";
                    $params = array();
                    $params[] = 10001;  // item_type_id
                    $params[] = 10002;  // item_type_id
                    $params[] = 10003;  // item_type_id
                    $params[] = 10004;  // item_type_id
                    $params[] = 10005;  // item_type_id
                    $params[] = 10006;  // item_type_id
                    $params[] = 10007;  // item_type_id
                    $params[] = 10008;  // item_type_id
                    $params[] = 10009;  // item_type_id
                    $params[] = 0;  // is_delete
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Update to DC mapping if junii2 mapping had changed from default.
                    foreach($retRef as $attr){
                        switch($attr["item_type_id"]){
                            case 10001: // 学術雑誌論文 / Journal Article
                            case 10002: // 紀要論文 / Departmental Bulletin Paper
                            case 10003: // 会議発表論文 / Conference Paper
                            case 10004: // 一般雑誌記事 / Article
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if(($attr["attribute_id"] == "4" || $attr["attribute_id"] == "10" || $attr["attribute_id"] == "12") && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "5" || $attr["attribute_id"] == "6") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                else if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "publisher"){
                                    $attr["dublin_core_mapping"] = "publisher";
                                }
                                else if($attr["attribute_id"] == "9" && $attr["junii2_mapping"] == "issn"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "11" && $attr["junii2_mapping"] == "NCID"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "13" && $attr["junii2_mapping"] == "pmid"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "14" && $attr["junii2_mapping"] == "doi"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "15" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "16" || $attr["attribute_id"] == "17") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "18" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "19" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "20" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "21" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "22" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            case 10005: // 会議発表用資料 / Presentation
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if($attr["attribute_id"] == "4" && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "5" || $attr["attribute_id"] == "6") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                else if($attr["attribute_id"] == "7" && $attr["junii2_mapping"] == "dateofissued"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "publisher"){
                                    $attr["dublin_core_mapping"] = "publisher";
                                }
                                else if($attr["attribute_id"] == "9" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "10" || $attr["attribute_id"] == "11") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "12" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "13" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "14" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "15" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "16" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            case 10006: // 学位論文 / Thesis or Dissertation
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if(($attr["attribute_id"] == "4" || $attr["attribute_id"] == "12") && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "5" && $attr["junii2_mapping"] == "publisher"){
                                    $attr["dublin_core_mapping"] = "publisher";
                                }
                                else if($attr["attribute_id"] == "6" && $attr["junii2_mapping"] == "contributor"){
                                    $attr["dublin_core_mapping"] = "contributor";
                                }
                                else if(($attr["attribute_id"] == "7" || $attr["attribute_id"] == "9" || $attr["attribute_id"] == "10") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                else if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "type"){
                                    $attr["dublin_core_mapping"] = "type";
                                }
                                else if($attr["attribute_id"] == "11" && $attr["junii2_mapping"] == "dateofissued"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "13" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "14" || $attr["attribute_id"] == "15") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "16" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "17" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "18" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "19" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "20" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            case 10007: // 報告書 / Research Paper
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if(($attr["attribute_id"] == "4" || $attr["attribute_id"] == "7" || $attr["attribute_id"] == "9" || $attr["attribute_id"] == "10") && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "5" || $attr["attribute_id"] == "6" || $attr["attribute_id"] == "12") && $attr["junii2_mapping"] == "contributor"){
                                    $attr["dublin_core_mapping"] = "contributor";
                                }
                                else if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "dateofissued"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "11" || $attr["attribute_id"] == "13" || $attr["attribute_id"] == "14") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                else if($attr["attribute_id"] == "15" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "16" || $attr["attribute_id"] == "17") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "18" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "19" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "20" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "21" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "22" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            case 10008: // 図書 / Book
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if(($attr["attribute_id"] == "4" || $attr["attribute_id"] == "10") && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "5" || $attr["attribute_id"] == "6") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                if($attr["attribute_id"] == "7" && $attr["junii2_mapping"] == "publisher"){
                                    $attr["dublin_core_mapping"] = "publisher";
                                }
                                if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "dateofissued"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "9" && $attr["junii2_mapping"] == "issn"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "11" && $attr["junii2_mapping"] == "NCID"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "12" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "13" || $attr["attribute_id"] == "14") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "15" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "16" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "17" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "18" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "19" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            case 10009: // 図書の一部 / Book
                                if($attr["attribute_id"] == "1" && $attr["junii2_mapping"] == "alternative"){
                                    $attr["dublin_core_mapping"] = "title";
                                }
                                else if(($attr["attribute_id"] == "2" || $attr["attribute_id"] == "3") && $attr["junii2_mapping"] == "creator"){
                                    $attr["dublin_core_mapping"] = "creator";
                                }
                                else if(($attr["attribute_id"] == "4" || $attr["attribute_id"] == "13") && $attr["junii2_mapping"] == "identifier"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if(($attr["attribute_id"] == "5" || $attr["attribute_id"] == "6") && $attr["junii2_mapping"] == "description"){
                                    $attr["dublin_core_mapping"] = "description";
                                }
                                if($attr["attribute_id"] == "7" && $attr["junii2_mapping"] == "jtitle"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "8" && $attr["junii2_mapping"] == "publisher"){
                                    $attr["dublin_core_mapping"] = "publisher";
                                }
                                if($attr["attribute_id"] == "9" && $attr["junii2_mapping"] == "spage"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "10" && $attr["junii2_mapping"] == "epage"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "11" && $attr["junii2_mapping"] == "dateofissued"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "12" && $attr["junii2_mapping"] == "issn"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                if($attr["attribute_id"] == "14" && $attr["junii2_mapping"] == "NCID"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                else if($attr["attribute_id"] == "15" && $attr["junii2_mapping"] == "rights"){
                                    $attr["dublin_core_mapping"] = "rights";
                                }
                                else if(($attr["attribute_id"] == "16" || $attr["attribute_id"] == "17") && $attr["junii2_mapping"] == "source"){
                                    $attr["dublin_core_mapping"] = "source";
                                }
                                else if($attr["attribute_id"] == "18" && $attr["junii2_mapping"] == "relation"){
                                    $attr["dublin_core_mapping"] = "relation";
                                }
                                else if($attr["attribute_id"] == "19" && $attr["junii2_mapping"] == "format"){
                                    $attr["dublin_core_mapping"] = "format";
                                }
                                else if($attr["attribute_id"] == "20" && $attr["junii2_mapping"] == "textversion"){
                                    $attr["dublin_core_mapping"] = "";
                                }
                                else if($attr["attribute_id"] == "21" && $attr["junii2_mapping"] == "NDC"){
                                    $attr["dublin_core_mapping"] = "subject";
                                }
                                else if($attr["attribute_id"] == "22" && $attr["junii2_mapping"] == "fullTextURL"){
                                    $attr["dublin_core_mapping"] = "identifier";
                                }
                                break;
                            default:
                                break;
                        }

                        // UPDATE DC mapping
                        $query = "UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                                 "SET dublin_core_mapping = ?, ".
                                 "mod_user_id = ?, ".
                                 "mod_date = ? ".
                                 "WHERE item_type_id = ? ".
                                 "AND attribute_id = ?; ";
                        $params = array();
                        $params[] = $attr["dublin_core_mapping"];
                        $params[] = $user_id;
                        $params[] = $this->TransStartDate;
                        $params[] = $attr["item_type_id"];
                        $params[] = $attr["attribute_id"];
                        // UPDATE実行
                        $result = $this->Db->execute($query, $params);
                        if($result === false || count($result)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    // Add DC mapping for default itemtype 2009/11/10 A.Suzuki --end--
                case 141:
                    // Extend file type 2009/12/10 A.Suzuki --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                             "ADD `display_name` TEXT NOT NULL AFTER `file_name`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                             "ADD `display_type` INT NOT NULL default 0 AFTER `display_name`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                             "ADD `flash_pub_date` VARCHAR(23) AFTER `pub_date`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Extend file type 2009/12/10 A.Suzuki --end--

                    // Add flash save directory 2010/01/06 A.Suzuki --start--
                    $pointer=fopen(WEBAPP_DIR.'/logs/weko/update_start_log.txt', "w");
                    $contents_path = $this->getFileSavePath("flash");
                    if(strlen($contents_path) == 0){
                        // default directory
                        $contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
                        if( !(file_exists($contents_path)) ){
                            mkdir ( $contents_path, 0777);
                        }
                    }
                    // check directory exists
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

                    // Bug fix biblio_info junii2 mapping 2010/01/29 A.Suzuki --start--
                    $query = "UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                             "SET junii2_mapping = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ? ".
                             "WHERE input_type = ? ; ";
                    $params = array();
                    $params[] = "jtitle,volume,issue,spage,epage,dateofissued";
                    $params[] = $user_id;
                    $params[] = $this->TransStartDate;
                    $params[] = "biblio_info";
                    // UPDATE実行
                    $result = $this->Db->execute($query, $params);
                    if($result === false || count($result)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Bug fix biblio_info junii2 mapping 2010/01/29 A.Suzuki --end--
                    // Add Last reset date of Ranking log  2010/02/08 K.Ando --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('ranking_last_reset_date', '', 'ランキングデータを最後に消去した日付', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add Last reset date of Ranking log 2010/02/08 K.Ando --start--
                case 142:
                    // Add Help icon and OAI-ORE icon display setting 2010/02/08 K.Ando --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('help_icon_display', '1', 'ヘルプアイコン表示設定(1:表示する、0:表示しない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('oaiore_icon_display', '1', 'OAI-OREアイコン表示設定(1:表示する、0:表示しない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add Help icon and OAI-ORE icon display setting 2010/02/08 K.Ando --end--
                case 143:
                    // Add AWSSecretAccessKey setting 2010/03/31 S.Nonomura --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('AWSSecretAccessKey', '3DhXSBYKMnma7PR3fP5i3fk/LHZzeqyKHXfg8E90', 'Amazon AWSSecretAccessKey', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add AWSSecretAccessKey setting 2010/03/31 S.Nonomura --end--
                case 144:
                    // Add host in log table Y.Nakao 2010/03/05 --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_log ".
                            " ADD `host` TEXT NOT NULL AFTER `ip_address`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "UPDATE ".DATABASE_PREFIX."repository_log ".
                            " SET host = ip_address; ";
                    $ret = $this->Db->execute($query);
                    if($ret === false){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add host in log table Y.Nakao 2010/03/05 --end--
                    // Add send mail for log report 2010/03/10 Y.Nakao --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('log_report_mail', '', '定型レポートメール送信先', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add send mail for log report 2010/03/10 Y.Nakao --end--

                    // Add simple keyword search 2010/04/12 A.Suzuki --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_fulltext_data ".
                            " ADD `metadata` LONGTEXT NOT NULL AFTER `extracted_text`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_fulltext_data ".
                             "ADD FULLTEXT (`metadata`);";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "UPDATE ".DATABASE_PREFIX."repository_fulltext_data ".
                            " SET metadata = ''; ";
                    $ret = $this->Db->execute($query);
                    if($ret === false){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $this->rebuildFullTextTable();
                    // Add simple keyword search 2010/04/12 A.Suzuki --end--
                case 145:
                    // Add file copy to contents lab 2010/06/28 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('lab_login_id', '', 'コンテンツラボ用ログインID', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('lab_host', '', 'コンテンツラボホスト名', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('lab_dir', '', 'コンテンツラボ転送先ディレクトリ', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add file copy to contents lab 2010/06/28 A.Suzuki --end--
                    // Add log move 2010/07/02 Y.Nakao --start--
                    $query = " ALTER TABLE ".DATABASE_PREFIX."repository_log ENGINE = MYISAM; ";
                    $ret = $this->Db->execute($query);
                    if($ret === false){
                        $errMsg = "Error case 145<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_log ".
                            " ADD `user_agent` TEXT NOT NULL AFTER `host`; ";
                    $ret = $this->Db->execute($query);
                    if($ret === false){
                        $errMsg = "Error case 145<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add log move 2010/07/02 Y.Nakao --end--
                case 146:
                    // 146 to 147 is no change DB

                case 147:
                    // Add contents page 2010/07/30 Y.Nakao --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                            " ADD `display_type` INT default 0 AFTER `display_more`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add contents page 2010/07/30 Y.Nakao --end--
                    // Add index thumbnail --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                            " ADD `thumbnail` LONGBLOB NOT NULL AFTER rss_display; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                            " ADD `thumbnail_name` TEXT NOT NULL AFTER thumbnail; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                            " ADD `thumbnail_mime_type` TEXT NOT NULL AFTER thumbnail_name; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add index thumbnail --end--
                case 150:
                    // Add ELS data transfer index --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('els_registered_index', '', 'ELSに登録済みのインデックス', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add ELS data transfer index --end--
                case 151:
                case 152:
                case 153:   // update from branch(1.5.0) -> update from branch(1.5.3)
                case 154:
                    // Add name_ authority table 2010/10/26 A.Suzuki --start--
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_name_authority ( ".
                             " `author_id` INT NOT NULL, ".
                             " `language` VARCHAR(255) NOT NULL, ".
                             " `family` VARCHAR(255), ".
                             " `name` VARCHAR(255), ".
                             " `family_ruby` VARCHAR(255), ".
                             " `name_ruby` VARCHAR(255), ".
                             " `e_mail_address` TEXT NOT NULL, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`author_id`, `language`) ".
                             " ) ENGINE=innodb; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add name_authority table 2010/10/26 A.Suzuki --end--

                    // Extend personal_name table 2010/10/26 A.Suzuki --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_personal_name ".
                             " ADD `family_ruby` TEXT NOT NULL AFTER `name_en`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_personal_name ".
                             " ADD `name_ruby` TEXT NOT NULL AFTER `family_ruby`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_personal_name ".
                             " ADD `author_id` INT NOT NULL AFTER `item_type_id`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_personal_name DROP `family_en`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_personal_name DROP `name_en`;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Extend personal_name table 2010/10/26 A.Suzuki --end--

                    // Add external_author table 2010/10/29 A.Suzuki --start--
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_external_author_id_prefix ( ".
                             " `prefix_id` INT NOT NULL, ".
                             " `prefix_name` VARCHAR(255), ".
                             " `block_id` INT NOT NULL, ".
                             " `room_id` INT NOT NULL, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`prefix_id`) ".
                             " ) ENGINE=innodb; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_external_author_id_prefix_seq_id (`id` INT NOT NULL); ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_author_id_prefix_seq_id ".
                             " (`id`) VALUES (3); ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_author_id_prefix ".
                             " (prefix_id, prefix_name, block_id, room_id, ".
                             " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES".
                             " (1, 'CINII ID', 0, 0, ?, ?, '0', ?, ?, '', 0), ".
                             " (2, '研究者リゾルバーID', 0, 0, ?, ?, '0', ?, ?, '', 0), ".
                             " (3, '科研費研究者番号', 0, 0, ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_external_author_id_suffix ( ".
                             " `author_id` INT NOT NULL, ".
                             " `prefix_id` INT NOT NULL, ".
                             " `suffix` VARCHAR(255), ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`author_id`, `prefix_id`) ".
                             " ) ENGINE=innodb; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add external_author table 2010/10/29 A.Suzuki --end--
                case 160:
                    // Add download_file_type 2010/12/10 H.Goto --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('file_download_type', '1', 'ファイルダウンロード方式(1:ダウンロード,0：ビューアー,2：どちらも可)', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add download_file_type 2010/12/10 H.Goto --end--
                    // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
                    $this->moveFlashToFolder();
                    // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--
                    // Add browsing_flag 2011/02/15 A.Suzuki --start--
                    $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                             "ADD `browsing_flag` INT NOT NULL default 0 AFTER `item_type_id`; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add browsing_flag 2011/02/15 A.Suzuki --end--
                    // Delete table : repository_position_group 2011/02/17 A.Suzuki --start--
                    $query = "DROP TABLE IF EXISTS ". DATABASE_PREFIX ."repository_position_group; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        echo $errMsg."<br/>";
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Delete table : repository_position_group 2011/02/17 A.Suzuki --end--

                    // out version up log. Y.Nakao 2012/03/05 --start--
                    $this->versionUp('1.6.1');
                    // out version up log. Y.Nakao 2012/03/05 --end--
                case 161:
                    // Add file_clean_up_last_date 2011/02/23 H.Goto --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('file_clean_up_last_date', '', 'ファイルクリーンアップを最後に実行した日付', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add file_clean_up_last_date 2011/02/23 H.Goto --end--
                    // Fix "CiNii" notation 2011/03/15 A.Suzuki --start--
                    $query = "UPDATE ".DATABASE_PREFIX."repository_external_author_id_prefix ".
                             "SET prefix_name = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ? ".
                             "WHERE prefix_id = 1 ; ";
                    $params = array();
                    $params[] = "CiNii ID";
                    $params[] = $user_id;
                    $params[] = $this->TransStartDate;
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Fix "CiNii" notation 2011/03/15 A.Suzuki --end--

                    // out version up log. Y.Nakao 2012/03/05 --start--
                    $this->versionUp('1.6.2');
                    // out version up log. Y.Nakao 2012/03/05 --end--
                case 162:
                    // Add index list 2011/4/6 S.Abe --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES ('select_index_list_display', '0', 'インデックス一覧表示切り替え条件', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD select_index_list_name_english text NOT NULL ".
                             "AFTER rss_display;";
                    $refRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD select_index_list_name text NOT NULL ".
                             "AFTER rss_display;";
                    $refRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD select_index_list_display int(1) default 0 ".
                             "AFTER rss_display;";
                    $refRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add index list 2011/4/6 S.Abe --end--
                    // out version up log. Y.Nakao 2012/03/05 --start--
                    $this->versionUp('1.6.3');
                    // out version up log. Y.Nakao 2012/03/05 --end--
                case 163:
                    // Add AssociateTag for modify API Y.Nakao 2011/10/19 --start--

                    $query = "SELECT count(*) ".
                            " FROM ".DATABASE_PREFIX."repository_parameter ".
                            " WHERE param_name = ? ".
                            " AND is_delete = ? ";
                    $params = array();
                    $params[] = "AssociateTag";
                    $params[] = 0;
                    $refRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    if(isset($refRef[0]['count(*)']) && $refRef[0]['count(*)'] == 0)
                    {
                        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                                 "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                                 " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                                 "VALUES('AssociateTag', 'w04d-22', 'Amazon Associate Tag', ?, ?, '0', ?, ?, '', 0); ";
                        $params = array();
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                        $retRef = $this->Db->execute($query, $params);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    // Add AssociateTag for modify API Y.Nakao 2011/10/19 --end--
                    // out version up log. Y.Nakao 2012/03/05 --start--
                    $this->versionUp('1.6.4');
                    // out version up log. Y.Nakao 2012/03/05 --end--
                case 164:
                    // Add tree access control list 2011/12/28 Y.Nakao --start--
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD `exclusive_acl_group` TEXT NOT NULL ".
                             "AFTER access_group;";
                    $refRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD `exclusive_acl_role` TEXT NOT NULL ".
                             "AFTER access_group;";
                    $refRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add tree access control list 2011/12/28 Y.Nakao --end--
                    // Add tree access control list 2012/02/22 T.Koyasu -start-
                    $query = "UPDATE ". DATABASE_PREFIX. "repository_index ".
                             "SET exclusive_acl_role=? ".
                             "WHERE exclusive_acl_role=?;";
                    $params = array();
                    $params[] = '|'. RepositoryConst::TREE_DEFAULT_EXCLUSIVE_ACL_ROLE_ROOM;
                    $params[] = '';
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add tree access control list 2012/02/22 T.Koyasu -end-
                    // set_spec
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD set_spec VARCHAR(255) ".
                             "AFTER thumbnail_mime_type;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // repository_id
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD repository_id INT ".
                             "AFTER thumbnail_mime_type;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add harvesting 2012/03/02 A.Suzuki --start--
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_harvesting ( ".
                             " `repository_id` INT NOT NULL AUTO_INCREMENT, ".
                             " `repository_name` TEXT, ".
                             " `base_url` TEXT, ".
                             " `metadata_prefix` VARCHAR(10), ".
                             " `post_index_id` INT, ".
                             " `automatic_sorting` INT, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`repository_id`) ".
                             " ) ENGINE=innodb; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_harvesting_log ( ".
                             " `log_id` INT NOT NULL AUTO_INCREMENT, ".
                             " `repository_id` INT, ".
                             " `oparation_id` INT, ".
                             " `metadata_prefix` VARCHAR(10), ".
                             " `list_sets` TEXT, ".
                             " `set_spec` TEXT, ".
                             " `index_id` TEXT, ".
                             " `identifier` TEXT, ".
                             " `item_id` INT, ".
                             " `uri` TEXT, ".
                             " `status` INT, ".
                             " `update` INT, ".
                             " `error_msg` TEXT, ".
                             " `response_date` VARCHAR(23), ".
                             " `last_mod_date` VARCHAR(23), ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " PRIMARY KEY(`log_id`) ".
                             " ) ENGINE=MYISAM; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('harvesting_start_date', '', 'Start date of the last harvesting', ?, ?, '0', ?, ?, '', 0), ".
                             "('harvesting_end_date', '', 'End date of the last harvesting', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // itemtype for harvesting
                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type VALUE ".
                             "(20001, 'DublinCore', 'DublinCore', 'harvesting item type', 'Journal Article', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20002, 'Journal Article', 'Journal Article', 'harvesting item type', 'Journal Article', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20003, 'Thesis or Dissertation', 'Thesis or Dissertation', 'harvesting item type', 'Thesis or Dissertation', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20004, 'Departmental Bulletin Paper', 'Departmental Bulletin Paper', 'harvesting item type', 'Departmental Bulletin Paper', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20005, 'Conference Paper', 'Conference Paper', 'harvesting item type', 'Conference Paper', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20006, 'Presentation', 'Presentation', 'harvesting item type', 'Presentation', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20007, 'Book', 'Book', 'harvesting item type', 'Book', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20008, 'Technical Report', 'Technical Report', 'harvesting item type', 'Technical Report', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20009, 'Research Paper', 'Research Paper', 'harvesting item type', 'Research Paper', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20010, 'Article', 'Article', 'harvesting item type', 'Article', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20011, 'Preprint', 'Preprint', 'harvesting item type', 'Preprint', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20012, 'Learning Material', 'Learning Material', 'harvesting item type', 'Learning Material', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20013, 'Data or Dataset', 'Data or Dataset', 'harvesting item type', 'Data or Dataset', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20014, 'Software', 'Software', 'harvesting item type', 'Software', '', '', '', '', ?, ?, '0', ?, ?, '', 0), ".
                             "(20015, 'Others', 'Others', 'harvesting item type', 'Others', '', '', '', '', ?, ?, '0', ?, ?, '', 0)";
                    $params = array();
                    for($ii=20001; $ii<=20015; $ii++)
                    {
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                             "(`item_type_id`, `attribute_id`, `show_order`, `attribute_name`, ".
                             "`attribute_short_name`, `input_type`, `is_required`, `plural_enable`, ".
                             "`line_feed_enable`, `list_view_enable`, `hidden`, `junii2_mapping`, ".
                             "`dublin_core_mapping`, `display_lang_type`, `ins_user_id`, `mod_user_id`, ".
                             "`del_user_id`, `ins_date`, `mod_date`, `del_date`, `is_delete`) VALUES ";
                    $params = array();
                    $query .= "(20001, 1, 1, 'その他(別言語等)のタイトル', 'その他(別言語等)のタイトル', 'text', 0, 1, 0, 0, 0, 'alternative', 'title', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 2, 2, '著者名', '著者名', 'name', 0, 1, 0, 0, 0, 'creator', 'creator', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 3, 3, '抄録', '抄録', 'textarea', 0, 1, 0, 0, 0, 'description', 'description', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 4, 4, '公開者', '公開者', 'name', 0, 1, 0, 0, 0, 'publisher', 'publisher', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 5, 5, '寄与者', '寄与者', 'name', 0, 1, 0, 0, 0, 'contributor', 'contributor', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 6, 6, '日付', '日付', 'date', 0, 1, 0, 0, 0, 'date', 'date', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 7, 7, '資源タイプ', '資源タイプ', 'textarea', 0, 1, 0, 0, 0, 'type', 'type', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 8, 8, 'フォーマット', 'フォーマット', 'text', 0, 1, 0, 0, 0, 'format', 'format', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 9, 9, '資源識別子', '資源識別子', 'link', 0, 1, 0, 0, 0, 'URI', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 10, 10, 'その他の資源識別子', 'その他の資源識別子', 'text', 0, 1, 0, 0, 0, 'identifier', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 11, 11, '情報源', '情報源', 'text', 0, 1, 0, 0, 0, 'source', 'source', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 12, 12, '他の資源との関係', '他の資源との関係', 'text', 0, 1, 0, 0, 0, 'relation', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 13, 13, '範囲', '範囲', 'text', 0, 1, 0, 0, 0, 'coverage', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 14, 14, '権利', '権利', 'text', 0, 1, 0, 0, 0, 'rights', 'rights', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 15, 15, '機関ID', '機関ID', 'text', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 16, 16, 'コンテンツID', 'コンテンツID', 'text', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0), ".
                              "(20001, 17, 17, 'コンテンツ更新日時', 'コンテンツ更新日時', 'date', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0)";
                    for($ii=1; $ii<=17; $ii++)
                    {
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                    }
                    for($ii=20002; $ii<=20015; $ii++)
                    {
                        $query .= ", ";
                        $query .= "(?, 1, 1, 'その他(別言語等)のタイトル', 'その他(別言語等)のタイトル', 'text', 0, 1, 0, 0, 0, 'alternative', 'title', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 2, 2, '作成者', '作成者', 'name', 0, 1, 0, 0, 0, 'creator', 'creator', 'japanese', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 3, 3, '作成者(その他言語)', '作成者(その他言語)', 'name', 0, 1, 0, 0, 0, 'creator', 'creator', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 4, 4, '国立情報学研究所 メタデータ主題語彙集', '国立情報学研究所 メタデータ主題語彙集', 'text', 0, 1, 0, 0, 0, 'NIIsubject', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 5, 5, '日本十進分類法', '日本十進分類法', 'text', 0, 1, 0, 0, 0, 'NDC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 6, 6, '国立国会図書館分類表', '国立国会図書館分類表', 'text', 0, 1, 0, 0, 0, 'NDLC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 7, 7, '日本件名標目', '日本件名標目', 'text', 0, 1, 0, 0, 0, 'BSH', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 8, 8, '国立国会図書館件名標目表', '国立国会図書館件名標目表', 'text', 0, 1, 0, 0, 0, 'NDLSH', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 9, 9, '医学件名標目表', '医学件名標目表', 'text', 0, 1, 0, 0, 0, 'MeSH', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 10, 10, 'デューイ十進分類法', 'デューイ十進分類法', 'text', 0, 1, 0, 0, 0, 'DDC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 11, 11, '米国議会図書館分類法', '米国議会図書館分類法', 'text', 0, 1, 0, 0, 0, 'LCC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 12, 12, '国際十進分類法', '国際十進分類法', 'text', 0, 1, 0, 0, 0, 'UDC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 13, 13, '米国議会図書館件名標目表', '米国議会図書館件名標目表', 'text', 0, 1, 0, 0, 0, 'LCSH', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 14, 14, '内容記述', '内容記述', 'textarea', 0, 1, 0, 0, 0, 'description', 'description', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 15, 15, '公開者', '公開者', 'name', 0, 1, 0, 0, 0, 'publisher', 'publisher', 'japanese', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 16, 16, '公開者(その他言語)', '公開者(その他言語)', 'name', 0, 1, 0, 0, 0, 'publisher', 'publisher', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 17, 17, '寄与者', '寄与者', 'name', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'japanese', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 18, 18, '寄与者(その他言語)', '寄与者(その他言語)', 'name', 0, 1, 0, 0, 0, 'contributor', 'contributor', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 19, 19, '日付', '日付', 'date', 0, 1, 0, 0, 0, 'date', 'date', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 20, 20, '資源タイプ', '資源タイプ', 'textarea', 0, 1, 0, 0, 0, 'type', 'type', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 21, 21, 'フォーマット', 'フォーマット', 'text', 0, 1, 0, 0, 0, 'format', 'format', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 22, 22, 'その他の資源識別子', 'その他の資源識別子', 'text', 0, 1, 0, 0, 0, 'identifier', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 23, 23, '資源識別子URI', '資源識別子URI', 'link', 1, 0, 0, 0, 0, 'URI', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 24, 24, '本文フルテキストへのリンク', '本文フルテキストへのリンク', 'link', 0, 1, 0, 0, 0, 'fullTextURL', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 25, 25, 'ISSN', 'ISSN', 'text', 0, 1, 0, 0, 0, 'issn', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 26, 26, '処理レコードID(総合目録DB)', '処理レコードID(総合目録DB)', 'text', 0, 1, 0, 0, 0, 'NCID', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 27, 27, '書誌情報', '書誌情報', 'biblio_info', 0, 0, 0, 0, 0, 'jtitle,volume,issue,spage,epage,dateofissued', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 28, 28, '情報源', '情報源', 'text', 0, 1, 0, 0, 0, 'source', 'source', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 29, 29, '言語', '言語', 'text', 0, 1, 0, 0, 0, 'language', 'language', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 30, 30, '他の資源との関係', '他の資源との関係', 'text', 0, 1, 0, 0, 0, 'relation', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 31, 31, 'PubMed番号', 'PubMed番号', 'text', 0, 0, 0, 0, 0, 'pmid', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 32, 32, 'DOI', 'DOI', 'text', 0, 0, 0, 0, 0, 'doi', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 33, 33, '異版である', '異版である', 'link', 0, 1, 0, 0, 0, 'isVersionOf', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 34, 34, '異版あり', '異版あり', 'link', 0, 1, 0, 0, 0, 'hasVersion', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 35, 35, '置換される', '置換される', 'link', 0, 1, 0, 0, 0, 'isReplacedBy', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 36, 36, '置換する', '置換する', 'link', 0, 1, 0, 0, 0, 'replaces', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 37, 37, '要件とされる', '要件とされる', 'link', 0, 1, 0, 0, 0, 'isRequiredBy', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 38, 38, '要件とする', '要件とする', 'link', 0, 1, 0, 0, 0, 'requires', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 39, 39, '部分である', '部分である', 'link', 0, 1, 0, 0, 0, 'isPartOf', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 40, 40, '部分を持つ', '部分を持つ', 'link', 0, 1, 0, 0, 0, 'hasPart', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 41, 41, '参照される', '参照される', 'link', 0, 1, 0, 0, 0, 'isReferencedBy', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 42, 42, '参照する', '参照する', 'link', 0, 1, 0, 0, 0, 'references', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 43, 43, '別フォーマットである', '別フォーマットである', 'link', 0, 1, 0, 0, 0, 'isFormatOf', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 44, 44, '別フォーマットあり', '別フォーマットあり', 'link', 0, 1, 0, 0, 0, 'hasFormat', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 45, 45, '範囲', '範囲', 'text', 0, 1, 0, 0, 0, 'coverage', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 46, 46, '空間的', '空間的', 'text', 0, 1, 0, 0, 0, 'spatial', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 47, 47, '国立情報学研究所 メタデータ主題語彙集(地域)', '国立情報学研究所 メタデータ主題語彙集(地域)', 'text', 0, 1, 0, 0, 0, 'NIIspatial', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 48, 48, '時間的', '時間的', 'text', 0, 1, 0, 0, 0, 'temporal', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 49, 49, '国立情報学研究所 メタデータ主題語彙集(時代)', '国立情報学研究所 メタデータ主題語彙集(時代)', 'text', 0, 1, 0, 0, 0, 'NIItemporal', 'coverage', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 50, 50, '権利', '権利', 'text', 0, 1, 0, 0, 0, 'rights', 'rights', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 51, 51, '著者版フラグ', '著者版フラグ', 'select', 0, 0, 0, 0, 0, 'textversion', '', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 52, 52, '機関ID', '機関ID', 'text', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 53, 53, 'コンテンツID', 'コンテンツID', 'text', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 54, 54, 'コンテンツ更新日時', 'コンテンツ更新日時', 'date', 1, 0, 0, 0, 1, '', '', '', ?, ?, '', ?, ?, '', 0)";
                        if($ii==20015)
                        {
                            $query .= ";";
                        }
                        for($jj=1; $jj<=54; $jj++)
                        {
                            $params[] = $ii;   // item_type_id
                            $params[] = $user_id;   // ins_user_id
                            $params[] = $user_id;   // mod_user_id
                            $params[] = $this->TransStartDate;  // ins_date
                            $params[] = $this->TransStartDate;  // mod_date
                        }
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_candidate VALUE ";
                    $params = array();
                    for($ii=20002; $ii<=20015; $ii++)
                    {
                        $query .= "(?, 51, 1, 'author', 'author', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 51, 2, 'publisher', 'publisher', ?, ?, '', ?, ?, '', 0), ".
                                  "(?, 51, 3, 'none', 'none', ?, ?, '', ?, ?, '', 0)";
                        if($ii==20015)
                        {
                            $query .= ";";
                        }
                        else
                        {
                            $query .= ", ";
                        }

                        for($jj=1; $jj<=3; $jj++)
                        {
                            $params[] = $ii;   // item_type_id
                            $params[] = $user_id;   // ins_user_id
                            $params[] = $user_id;   // mod_user_id
                            $params[] = $this->TransStartDate;  // ins_date
                            $params[] = $this->TransStartDate;  // mod_date
                        }
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add harvesting 2012/03/02 A.Suzuki --end--

                    // Heading attribute add to default itemtypes 2012/03/07 A.Suzuki --start--
                    // Get default itemtype / item_type_id : 10001~10009
                    $query = "SELECT item_type_id, ins_user_id, ins_date ".
                             "FROM ".DATABASE_PREFIX."repository_item_type ".
                             "WHERE item_type_id >= 10001 ".
                             "AND item_type_id <= 10009 ".
                             "AND ins_date = mod_date ;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Update to DC mapping if junii2 mapping had changed from default.
                    foreach($retRef as $attr){
                        // Add heading attribute
                        $attribute_id = "";
                        switch($attr["item_type_id"])
                        {
                            case 10001:
                            case 10002:
                            case 10003:
                            case 10004:
                            case 10007:
                            case 10009:
                                $attribute_id = 23;
                                break;
                            case 10005:
                                $attribute_id = 17;
                                break;
                            case 10006:
                                $attribute_id = 21;
                                break;
                            case 10008:
                                $attribute_id = 20;
                                break;
                            default:
                                break;
                        }

                        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_item_attr_type ".
                                 "WHERE attribute_id = ? ";
                        $params = array();
                        $params[] = $attribute_id;
                        $recCnt = $this->Db->execute($query, $params);
                        if($recCnt === false)
                        {
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                        if(count($recCnt) == 0)
                        {
                            $query = "INSERT INTO ". DATABASE_PREFIX. "repository_item_attr_type ".
                                     "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name,".
                                     " input_type, is_required, plural_enable, line_feed_enable, list_view_enable,".
                                     " hidden, junii2_mapping, dublin_core_mapping, display_lang_type, ins_user_id,".
                                     " mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
                            $params = array();
                            $params[] = $attr["item_type_id"]; // item_type_id
                            $params[] = $attribute_id; // attribute_id
                            $params[] = $attribute_id; // show_order
                            $params[] = "見出し"; // attribute_name
                            $params[] = "見出し"; // attribute_short_name
                            $params[] = "heading"; // input_type
                            $params[] = 0; // is_required
                            $params[] = 0; // plural_enable
                            $params[] = 0; // line_feed_enable
                            $params[] = 0; // list_view_enable
                            $params[] = 0; // hidden
                            $params[] = ""; // junii2_mapping
                            $params[] = ""; // dublin_core_mapping
                            $params[] = ""; // display_lang_type
                            $params[] = $attr["ins_user_id"]; // ins_user_id
                            $params[] = $attr["ins_user_id"]; // mod_user_id
                            $params[] = 0; // del_user_id
                            $params[] = $attr["ins_date"]; // ins_date
                            $params[] = $attr["ins_date"]; // mod_date
                            $params[] = ""; // del_date
                            $params[] = 0; // is_delete
                            $result = $this->Db->execute($query, $params);
                            if($result === false){
                                $errMsg = $this->Db->ErrorMsg();
                                // Rollback
                                $this->failTrans();
                                throw $exception;
                            }
                        }

                        // Fix attribute_name
                        if($attr["item_type_id"] == 10008 || $attr["item_type_id"] == 10009)
                        {
                            $query = "UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                                     "SET attribute_name = ?, ".
                                     "attribute_short_name = ? ".
                                     "WHERE item_type_id = ? ".
                                     "AND attribute_id = ?; ";
                            $params = array();
                            $params[] = "著者別名";
                            $params[] = "著者別名";
                            $params[] = $attr["item_type_id"];
                            $params[] = 3;
                            $result = $this->Db->execute($query, $params);
                            if($result === false){
                                $errMsg = $this->Db->ErrorMsg();
                                // Rollback
                                $this->failTrans();
                                throw $exception;
                            }
                        }
                    }
                    // Heading attribute add to default itemtypes 2012/03/07 A.Suzuki --end--

                    // out version up log. Y.Nakao 2012/03/05 --start--
                    $this->versionUp('1.6.5');
                    // out version up log. Y.Nakao 2012/03/05 --end--
                case 165:
                    $this->versionUp('2.0.0');
                case 200:
                    // Add default item type 'Others' --start--
                    // Insert to repository_item_type table
                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type VALUE ".
                             "(10010, 'その他 / Others', 'その他 / Others', 'default item type', 'Others', '', '', '', '', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Insert to repository_item_attr_type table
                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                             "(`item_type_id`, `attribute_id`, `show_order`, `attribute_name`, ".
                             "`attribute_short_name`, `input_type`, `is_required`, `plural_enable`, ".
                             "`line_feed_enable`, `list_view_enable`, `hidden`, `junii2_mapping`, ".
                             "`dublin_core_mapping`, `display_lang_type`, `ins_user_id`, `mod_user_id`, ".
                             "`del_user_id`, `ins_date`, `mod_date`, `del_date`, `is_delete`) VALUES ";
                    $params = array();
                    $query .= "(10010, 1, 1, 'その他（別言語等）のタイトル', 'その他（別言語等）のタイトル', 'text', 0, 1, 0, 0, 0, 'alternative', 'title', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 2, 2, '著者', '著者', 'name', 1, 1, 1, 1, 0, 'creator', 'creator', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 3, 3, '著者別名', '著者別名', 'name', 0, 1, 0, 0, 0, '', '', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 4, 4, '著者ID', '著者ID', 'text', 0, 1, 0, 0, 0, 'identifier', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 5, 5, '抄録', '抄録', 'textarea', 0, 1, 0, 0, 0, 'description', 'description', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 6, 6, '内容記述', '内容記述', 'textarea', 0, 1, 0, 0, 0, 'description', 'description', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 7, 7, '書誌情報', '書誌情報', 'biblio_info', 0, 0, 0, 1, 0, 'jtitle,volume,issue,spage,epage,dateofissued', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 8, 8, '出版者', '出版者', 'text', 0, 1, 0, 0, 0, 'publisher', 'publisher', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 9, 9, 'ISSN', 'ISSN', 'text', 0, 0, 0, 0, 0, 'issn', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 10, 10, 'ISBN', 'ISBN', 'text', 0, 1, 0, 0, 0, 'identifier', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 11, 11, '書誌レコードID', '書誌レコードID', 'text', 0, 0, 0, 0, 0, 'NCID', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 12, 12, '論文ID（NAID）', '論文ID（NAID）', 'text', 0, 0, 0, 0, 0, 'identifier', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 13, 13, 'PubMed番号', 'PubMed番号', 'text', 0, 0, 0, 0, 0, 'pmid', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 14, 14, 'DOI', 'DOI', 'text', 0, 0, 0, 0, 0, 'doi', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 15, 15, '権利', '権利', 'text', 0, 1, 0, 0, 0, 'rights', 'rights', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 16, 16, '情報源', '情報源', 'text', 0, 1, 0, 0, 0, 'source', 'source', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 17, 17, '関連サイト', '関連サイト', 'link', 0, 1, 0, 0, 0, 'source', 'source', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 18, 18, '他の資源との関係', '他の資源との関係', 'text', 0, 1, 0, 0, 0, 'relation', 'relation', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 19, 19, 'フォーマット', 'フォーマット', 'text', 0, 1, 0, 0, 0, 'format', 'format', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 20, 20, '著者版フラグ', '著者版フラグ', 'select', 0, 0, 0, 0, 0, 'textversion', '', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 21, 21, '日本十進分類法', '日本十進分類法', 'text', 0, 1, 0, 0, 0, 'NDC', 'subject', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 22, 22, 'コンテンツ本体', 'コンテンツ本体', 'file', 0, 1, 1, 1, 0, 'fullTextURL', 'identifier', '', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 23, 23, '見出し', '見出し', 'heading', 0, 0, 0, 0, 0, '', '', '', ?, ?, '', ?, ?, '', 0)";
                    for($ii=1; $ii<=23; $ii++)
                    {
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_candidate ".
                             "(`item_type_id`, `attribute_id`, `candidate_no`, `candidate_value`, ".
                             "`candidate_short_value`, `ins_user_id`, `mod_user_id`, ".
                             "`del_user_id`, `ins_date`, `mod_date`, `del_date`, `is_delete`) VALUES ";
                    $params = array();
                    $query .= "(10010, 20, 1, 'author', 'author', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 20, 2, 'publisher', 'publisher', ?, ?, '', ?, ?, '', 0), ".
                              "(10010, 20, 3, 'none', 'none', ?, ?, '', ?, ?, '', 0);";
                    for($ii=1; $ii<=3; $ii++)
                    {
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add default item type 'Others' --end--
                    $this->versionUp('2.0.1');
                case 201:
                    // For create PDF cover page
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('path_pdftk', '', 'pdftkコマンドまでの絶対パス', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_pdf_cover_parameter ( ".
                             " `param_name` TEXT(255), ".
                             " `text` TEXT NOT NULL, ".
                             " `image` LONGBLOB NOT NULL, ".
                             " `extension` TEXT NOT NULL, ".
                             " `mimetype` TEXT NOT NULL, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " PRIMARY KEY(`param_name`(255)) ".
                             " ) ENGINE=innodb; ";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_pdf_cover_parameter ".
                             " (param_name, text, image, extension, ".
                             " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date) VALUES".
                             " ('headerType', 'text', '', '', ?, ?, '0', ?, ?, ''), ".
                             " ('headerText', '', '', '', ?, ?, '0', ?, ?, ''), ".
                             " ('headerImage', '', '', '', ?, ?, '0', ?, ?, ''), ".
                             " ('headerAlign', 'right', '', '', ?, ?, '0', ?, ?, ''); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add create_cover_flag to repository_index
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD create_cover_flag INT NOT NULL default 0 ".
                             "AFTER set_spec;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add cover_created_flag to repository_file
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_file ".
                             "ADD cover_created_flag INT NOT NULL default 0 ".
                             "AFTER browsing_flag;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add update_usage_statistics_last_date parameter
                    $query = "INSERT INTO ".DATABASE_PREFIX."repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES ('update_usage_statistics_last_date', '', '利用統計ログ集計を最後に実行した日付', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add usage statistics table
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_usagestatistics ( ".
                             "  record_date VARCHAR(7) NOT NULL, ".
                             "  item_id INT NOT NULL, ".
                             "  item_no INT NOT NULL, ".
                             "  attribute_id INT default NULL, ".
                             "  file_no INT default NULL, ".
                             "  operation_id INT NOT NULL, ".
                             "  domain VARCHAR(255) default '', ".
                             "  cnt INT default 0 NOT NULL, ".
                             "  ins_user_id VARCHAR(40) NOT NULL default 0, ".
                             "  ins_date VARCHAR(23), ".
                             "  PRIMARY KEY(record_date, item_id, item_no, attribute_id, file_no, operation_id, domain) ".
                             ") ENGINE=MyISAM;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add send_feedback_mail_start_date, send_feedback_mail_end_date and exclude_address_for_feedback
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('send_feedback_mail_start_date', '', 'Start date of feedback mail sending', ?, ?, '0', ?, ?, '', 0), ".
                             "('send_feedback_mail_end_date', '', 'End date of feedback mail sending', ?, ?, '0', ?, ?, '', 0), ".
                             "('exclude_address_for_feedback', '', 'Exclude mail address for feedback mail', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add multimedia support 2012/08/27 T.Koyasu -start-
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES ('path_ffmpeg', '', 'ffmpegコマンドまでの絶対パス', ?, ?, '0', ?, ?, '', 0); ";
                    $params = array();
                    $params[] = $user_id;               // ins_user_id
                    $params[] = $user_id;               // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add multimedia support 2012/08/27 T.Koyasu -end-

                    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -start-
                    $query = "SELECT * FROM ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_PARAMETER.
                             " WHERE param_name=? ".
                             " AND is_delete=?; ";
                    $params = array();
                    $params[] = 'contents_lab_registered_index';
                    $params[] = 0;
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false)
                    {
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    if(count($retRef) == 0)
                    {
                        $query = "INSERT INTO ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_PARAMETER.
                                 " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                                 " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                                 " VALUES ('contents_lab_registered_index', '', 'コンテンツラボに登録済みのインデックス', ?, ?, '0', ?, ?, '', 0);";
                        $params = array();
                        $params[] = $user_id;               // ins_user_id
                        $params[] = $user_id;               // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                        $retRef = $this->Db->execute($query, $params);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    // Add Shelf registration to contents lab 2012/10/21 T.Koyasu -end-
                    $this->versionUp('2.0.2');
                case 202:
                    // Add 'ichushi' parameter --start--
                    // Insert to repository_parameter table
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                            " (param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                            " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                            " VALUES".
                            " ('ichushi_is_connect', '0', '医中誌への連携チェック状態(1:連携,0:連携しない)', ?, ?, '0', ?, ?, '', 0),".
                            " ('ichushi_login_id', '', '医中誌ログインID', ?, ?, '0', ?, ?, '', 0),".
                            " ('ichushi_login_passwd', '', '医中誌ログインパスワード', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    for($ii=1; $ii<=3; $ii++)
                    {
                        $params[] = $user_id;   // ins_user_id
                        $params[] = $user_id;   // mod_user_id
                        $params[] = $this->TransStartDate;  // ins_date
                        $params[] = $this->TransStartDate;  // mod_date
                    }
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add 'ichushi' parameter --end--

                    // Add feedback mail setting 2012/12/25 A.Suzuki --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_PARAMETER.
                             " (".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_NAME.", ".RepositoryConst::DBCOL_REPOSITORY_PARAMETER_PARAM_VALUE.", ".
                             RepositoryConst::DBCOL_REPOSITORY_PARAMETER_EXPLANATION.", ".RepositoryConst::DBCOL_COMMON_INS_USER_ID.", ".
                             RepositoryConst::DBCOL_COMMON_MOD_USER_ID.", ".RepositoryConst::DBCOL_COMMON_DEL_USER_ID.", ".
                             RepositoryConst::DBCOL_COMMON_INS_DATE.", ".RepositoryConst::DBCOL_COMMON_MOD_DATE.", ".
                             RepositoryConst::DBCOL_COMMON_DEL_DATE.", ".RepositoryConst::DBCOL_COMMON_IS_DELETE.") ".
                             " VALUES ('send_feedback_mail_activate_flg', '0', 'フィードバックメール送信機能利用設定(1:利用する,0:利用しない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;               // ins_user_id
                    $params[] = $user_id;               // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add feedback mail setting 2012/12/25 A.Suzuki --end--

                    $this->versionUp('2.0.3');
                case 203:
                    // Add file download status to log 2012/11/09 A.Suzuki --start--
                    // Set column 'file_status'
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG, RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                                 "ADD ".RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS." INT(2) default 0 ".
                                 "AFTER ".RepositoryConst::DBCOL_REPOSITORY_LOG_USER_AGENT.";";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    // Set column 'site_license'
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG, RepositoryConst::DBCOL_REPOSITORY_LOG_SITE_LICENSE))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                                 "ADD ".RepositoryConst::DBCOL_REPOSITORY_LOG_SITE_LICENSE." INT(2) default NULL ".
                                 "AFTER ".RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_STATUS.";";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    // Set column 'input_type'
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG, RepositoryConst::DBCOL_REPOSITORY_LOG_INPUT_TYPE))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                                 "ADD ".RepositoryConst::DBCOL_REPOSITORY_LOG_INPUT_TYPE." INT(2) default NULL ".
                                 "AFTER ".RepositoryConst::DBCOL_REPOSITORY_LOG_SITE_LICENSE.";";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    // Set column 'login_status'
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG, RepositoryConst::DBCOL_REPOSITORY_LOG_LOGIN_STATUS))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                                 "ADD ".RepositoryConst::DBCOL_REPOSITORY_LOG_LOGIN_STATUS." INT(2) default NULL ".
                                 "AFTER ".RepositoryConst::DBCOL_REPOSITORY_LOG_INPUT_TYPE.";";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    // Set column 'group_id'
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG, RepositoryConst::DBCOL_REPOSITORY_LOG_GROUP_ID))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                                 "ADD ".RepositoryConst::DBCOL_REPOSITORY_LOG_GROUP_ID." INT(11) default NULL ".
                                 "AFTER ".RepositoryConst::DBCOL_REPOSITORY_LOG_LOGIN_STATUS.";";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false || count($retRef)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    // Add file download status to log 2012/11/09 A.Suzuki --end--

                    // Add display custom order 2013/01/08 A.Jin --start--
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_position_index".
                            " ADD custom_sort_order INT NOT NULL DEFAULT 0 AFTER index_id;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Update custom_sort_order all
                    $query = "SELECT index_id, item_id, item_no ".
                             " FROM ".DATABASE_PREFIX."repository_position_index ".
                             " ORDER BY index_id, item_id, item_no;";
                    $custom_sort_list = $this->Db->execute($query);
                    if($custom_sort_list === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    for($ii=0; $ii<count($custom_sort_list); $ii++)
                    {
                        $query = "UPDATE ".DATABASE_PREFIX."repository_position_index ".
                                 "SET custom_sort_order=? ".
                                 "WHERE index_id = ? ".
                                 "AND item_id = ? ".
                                 "AND item_no = ? ".
                                 "ORDER BY item_id, item_no;";
                        $params = array();
                        $params[] = $cnt+1;
                        $params[] = $custom_sort_list[$ii]['index_id'];
                        $params[] = $custom_sort_list[$ii]['item_id'];
                        $params[] = $custom_sort_list[$ii]['item_no'];

                        $retRef = $this->Db->execute($query, $params);
                        if($ii>0 && isset($custom_sort_list[$ii+1]['index_id'])
                           && $custom_sort_list[$ii]['index_id'] != $custom_sort_list[$ii+1]['index_id'])
                        {
                            $cnt=0;
                        }else{
                            $cnt = $cnt+1;
                        }
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                             $this->failTrans();
                            throw $exception;
                        }
                    }

                    $query = "UPDATE ".DATABASE_PREFIX ."repository_parameter ".
                             "SET param_value = concat(param_value, ?) ".
                             "WHERE param_name = 'sort_disp';";
                    $params = array();
                    //$params[] = $tmp_sort_disp;
                    $params[] = '|17|18';
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // Add display custom order 2013/01/08 A.Jin --end--

                    $this->versionUp('2.0.4');
                case 204:
                    // Add LOM mapping column 2013/1/28 A.Jin --start--
                    if(!$this->isExistColumn(DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE, RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING))
                    {
                        $query = "ALTER TABLE ".DATABASE_PREFIX."repository_item_attr_type".
                            " ADD lom_mapping TEXT NOT NULL AFTER dublin_core_mapping;";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    //Update repository_item_attr_type TABLE
                    // item_type_id : ALL
                    $query = "SELECT ATTRTYPE.item_type_id, ATTRTYPE.attribute_id, ATTRTYPE.junii2_mapping, ".
                             "ATTRTYPE.dublin_core_mapping, ATTRTYPE.ins_date, ATTRTYPE.mod_date ".
                             " FROM ".DATABASE_PREFIX."repository_item_attr_type AS ATTRTYPE, ".
                                      DATABASE_PREFIX."repository_item_type AS ITEMTYPE ".
                             " WHERE ATTRTYPE.is_delete=?".
                             " AND ATTRTYPE.item_type_id>?".
                             " AND ATTRTYPE.item_type_id=ITEMTYPE.item_type_id".
                             " AND ITEMTYPE.ins_date=ITEMTYPE.mod_date;";
                    $params = array();
                    $params[] = 0;  // is_delete
                    $params[] = 10000;  // item_type_id
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Update to LOM mapping if junii2 mapping had changed from default.
                    foreach($retRef as $attr){
                        $lom_mapping = '';
                        switch($attr['dublin_core_mapping']){
                            case "identifier":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_IDENTIFER;
                                break;
                            case "title":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_TITLE;
                                break;
                            case "language":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_LANGUAGE;
                                break;
                            case "description":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_DESCRIPTION;
                                break;
                            case "subject":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_KEYWORD;
                                break;
                            case "coverge":
                                $lom_mapping = RepositoryConst::LOM_MAP_GNRL_COVERAGE;
                                break;
                            case "creator":
                                $lom_mapping = RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_AUTHOR;
                                break;
                            case "publisher":
                            case "date":
                                $lom_mapping = RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISHER;
                                break;
                            case "contributor":
                                $lom_mapping = RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE;
                                break;
                            case "format":
                                $lom_mapping = RepositoryConst::LOM_MAP_TCHNCL_FORMAT;
                                break;
                            case "type":
                                $lom_mapping = RepositoryConst::LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE;
                                break;
                            case "rights":
                                $lom_mapping = RepositoryConst::LOM_MAP_RGHTS_DESCRIPTION;
                                break;
                            case "relation":
                                if($attr['junii2_mapping'] == "isVersionOf"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_VERSION_OF;

                                }else if($attr['junii2_mapping'] == "hasVersion"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_HAS_VERSION;

                                }else if($attr['junii2_mapping'] == "isRequiredBy"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_REQUIRED_BY;

                                }else if($attr['junii2_mapping'] == "requires"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_REQUIRES;

                                }else if($attr['junii2_mapping'] == "isPartOf"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_PART_OF;

                                }else if($attr['junii2_mapping'] == "hasPart"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_HAS_PART_OF;

                                }else if($attr['junii2_mapping'] == "isReferencedBy"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_REFERENCED_BY;

                                }else if($attr['junii2_mapping'] == "references"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_REFERENCES;

                                }else if($attr['junii2_mapping'] == "isFormatOf"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_FORMAT_OF;

                                }else if($attr['junii2_mapping'] == "hasFormat"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN_HAS_FORMAT;

                                }else if($attr['junii2_mapping'] == "relation"){

                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN;

                                }else {
                                    $lom_mapping = RepositoryConst::LOM_MAP_RLTN;
                                }
                                break;
                            case "source":
                                $lom_mapping = RepositoryConst::LOM_MAP_RLTN_IS_BASED_ON;
                                break;
                            //空白の場合、junii2で判断
                            case "":
                                //textversion
                                if($attr['junii2_mapping'] == "textversion"){
                                    $lom_mapping = RepositoryConst::LOM_MAP_GNRL_IDENTIFER;
                                    break;
                                }
                                if($attr['junii2_mapping'] == ""){
                                    $lom_mapping = "";
                                }
                                break;
                            default:
                                break;
                        }

                        // UPDATE LOM mapping
                        $query = "UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                                "SET lom_mapping = ? ".
                                "WHERE item_type_id = ? ".
                                "AND attribute_id = ?; ";
                        $params = array();
                        $params[] = $lom_mapping;
                        $params[] = $attr["item_type_id"];
                        $params[] = $attr["attribute_id"];
                        // UPDATE実行
                        $result = $this->Db->execute($query, $params);
                        if($result === false || count($result)!=1){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    //もし20016がなかったら、repository_item_typeとrepository_item_attr_typeに追加する
                    $query = "SELECT * ".
                             "FROM ".DATABASE_PREFIX."repository_item_type".
                             " WHERE is_delete=? AND item_type_id=? ;";
                    $params = array();
                    $params[] = 0;  // is_delete
                    $params[] = 20016;  // item_type_id
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    if(count($retRef) == 0){
                        $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                                 "VALUE".
                                "(20016, 'Learning Object Matadata', 'Learning Object Matadata', 'harvesting item type', 'Learning Material', '', '', '', '', '1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    $query = "SELECT * ".
                             "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                             "WHERE is_delete = ? AND item_type_id = ? ;";
                    $params = array();
                    $params[] = 0;  // is_delete
                    $params[] = 20016;  // item_type_id
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    if(count($retRef) == 0){
                        $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                                "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                                "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                                "junii2_mapping, dublin_core_mapping, lom_mapping, display_lang_type, ".
                                "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
                                "(20016, 1, 1, 'URI', 'URI', 'link', 0, 1, 0, 0, 0, 'URI', 'identifier', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 2, 2, 'ISSN', 'ISSN', 'text', 0, 1, 0, 0, 0, 'issn', 'identifier', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 3, 3, 'NCID', 'NCID', 'text', 0, 1, 0, 0, 0, 'NCID', 'identifier', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 4, 4, '書誌情報', '書誌情報', 'biblio_info', 0, 0, 0, 1, 0, 'jtitle,volume,issue,spage,epage,dateofissued', 'identifier', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 5, 5, '著者版フラグ', '著者版フラグ', 'select', 0, 0, 0, 0, 0, 'textversion', '', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 6, 6, 'その他の識別子', 'その他の識別子', 'text', 0, 1, 0, 0, 0, 'identifier', 'identifier', 'generalIdentifier', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 7, 7, '言語', '言語', 'text', 0, 1, 0, 0, 0, 'language', 'language', 'generalLanguage', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 8, 8, '内容記述', '内容記述', 'textarea', 0, 1, 0, 0, 0, 'description', 'description', 'generalDescription', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 9, 9, '範囲', '範囲', 'text', 0, 1, 0, 0, 0, 'coverage', 'coverage', 'generalCoverage', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 10, '構成', '構成', 'select', 0, 0, 0, 0, 0, '', '', 'generalStructure', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 11, 11, '粒度', '粒度', 'select', 0, 0, 0, 0, 0, '', '', 'generalAggregationLevel', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 12, 12, 'エディション', 'エディション', 'text', 0, 0, 0, 0, 0, '', '', 'lifeCycleVersion', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 13, 13, 'ステータス', 'ステータス', 'select', 0, 0, 0, 0, 0, '', '', 'lifeCycleStatus', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 14, 14, '著者(creator)', '著者(creator)', 'name', 0, 1, 0, 0, 0, 'creator', 'creator', 'lifeCycleContributeAuthor', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 15, 15, '公開者(publisher)', '公開者(publisher)', 'name', 0, 1, 0, 0, 0, 'publisher', 'publisher', 'lifeCycleContributePublisher', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 16, 16, '日付', '日付', 'date', 0, 1, 0, 0, 0, 'date', 'date', 'lifeCycleContributePublishDate', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 17, 17, '作成者(initiator)', '作成者(initiator)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeInitiator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 18, 18, '更新者(terminator)', '更新者(terminator)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeTerminator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 19, 19, '校閲者(validator)', '校閲者(validator)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeValidator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 20, 20, '編集者(editor)', '編集者(editor)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeEditor', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 21, 21, '装丁(graphical designer)', '装丁(graphical designer)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeGraphicalDesigner', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 22, 22, '技術(technical implementer)', '技術(technical implementer)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeTechnicalImplementer', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 23, 23, 'コンテンツプロバイダー(content provider)', 'コンテンツプロバイダー(content provider)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeContentProvider', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 24, 24, '技術監修(technical validator)', '技術監修(technical validator)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeTechnicalValidator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 25, 25, '教育監修(educational validator)', '教育監修(educational validator)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeEducationalValidator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 26, 26, '台本(script writer)', '台本(script writer)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeScriptWriter', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 27, 27, '教育デザイン(instructional designer)', '教育デザイン(instructional designer)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeInstructionalDesigner', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 28, 28, '専門家(subject matter expert)', '専門家(subject matter expert)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeSubjectMatterExpert', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 29, 29, 'その他の関係者(unknown)', 'その他の関係者(unknown)', 'text', 0, 1, 0, 0, 0, 'contributor', 'contributor', 'lifeCycleContributeUnknown', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 30, 30, 'その他の関係者', 'その他の関係者', 'text', 0, 1, 0, 0, 0, '', '', 'lifeCycleContribute', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 31, 31, 'メタデータ識別子', 'メタデータ識別子', 'text', 0, 1, 0, 0, 0, '', '', 'metaMetadataIdentifer', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 32, 32, 'メタデータ作成者(creator)', 'メタデータ作成者(creator)', 'text', 0, 1, 0, 0, 0, '', '', 'metaMetadataContributeCreator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 33, 33, 'メタデータ校閲者(validator)', 'メタデータ校閲者(validator)', 'text', 0, 1, 0, 0, 0, '', '', 'metaMetadataContributeValidator', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 34, 34, 'その他のメタデータ関係者', 'その他のメタデータ関係者', 'text', 0, 1, 0, 0, 0, '', '', 'metaMetadataContribute', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 35, 35, 'メタデータスキーマ', 'メタデータスキーマ', 'text', 0, 1, 0, 0, 0, '', '', 'metaMetadataMetadataSchema', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 36, 36, 'メタデータ言語', 'メタデータ言語', 'text', 0, 0, 0, 0, 0, '', '', 'metaMetadataLanguage', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 37, 37, 'ファイルフォーマット', 'ファイルフォーマット', 'text', 0, 1, 0, 0, 0, 'format', 'format', 'technicalFormat', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 38, 38, 'ファイルサイズ', 'ファイルサイズ', 'text', 0, 0, 0, 0, 0, '', '', 'technicalSize', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 39, 39, 'ファイルリンク', 'ファイルリンク', 'text', 0, 1, 0, 0, 0, '', '', 'technicalLocation', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 40, 40, '動作環境', '動作環境', 'text', 0, 1, 0, 0, 0, '', '', 'technicalRequirementOrCompositeType', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 41, 41, '動作条件', '動作条件', 'text', 0, 1, 0, 0, 0, '', '', 'technicalRequirementOrCompositeName', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 42, 42, '推奨バージョン(下限)', '推奨バージョン(下限)', 'text', 0, 1, 0, 0, 0, '', '', 'technicalRequirementOrCompositeMinimumVersion', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 43, 43, '推奨バージョン(上限)', '推奨バージョン(上限)', 'text', 0, 1, 0, 0, 0, '', '', 'technicalRequirementOrCompositeMaximumVersion', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 44, 44, 'インストール手順', 'インストール手順', 'text', 0, 0, 0, 0, 0, '', '', 'technicalInstallationRemarks', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 45, 45, 'その他の技術要件', 'その他の技術要件', 'text', 0, 0, 0, 0, 0, '', '', 'technicalOtherPlatformRequirements', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 46, 46, '再生時間', '再生時間', 'text', 0, 0, 0, 0, 0, '', '', 'technicalDuration', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 47, 47, '授業形態', '授業形態', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalInteractivityType', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 48, 'リソースタイプ', 'リソースタイプ', 'checkbox', 0, 0, 0, 0, 0, 'type', 'type', 'educationalLearningResourceType', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 49, '双方向性度合(interactivity level)', '双方向性度合(interactivity level)', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalInteractivityLevel', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 50, '情報量(sematic density)', '情報量(sematic density)', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalSemanticDensity', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 51, 51, '利用者', '利用者', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalIntendedEndUserRole', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 52, 52, '利用環境', '利用環境', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalContext', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 53, 53, '対象年齢', '対象年齢', 'text', 0, 1, 0, 0, 0, '', '', 'educationalTypicalAgeRange', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 54, '難易度', '難易度', 'checkbox', 0, 0, 0, 0, 0, '', '', 'educationalDifficulty', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 55, 55, '授業時間', '授業時間', 'text', 0, 1, 0, 0, 0, '', '', 'educationalTypicalLearningTime', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 56, 56, '備考', '備考', 'textarea', 0, 1, 0, 0, 0, '', '', 'educationalDescription', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 57, 57, '言語', '言語', 'text', 0, 1, 0, 0, 0, '', '', 'educationalLanguage', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 58, 58, '有料', '有料', 'select', 0, 0, 0, 0, 0, '', '', 'rightsCost', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 59, 59, '利用形態', '利用形態', 'select', 0, 0, 0, 0, 0, '', '', 'rightsCopyrightAndOtherRestrictions', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 60, 60, '権利', '権利', 'textarea', 0, 0, 0, 0, 0, 'rights', 'rights', 'rightsDescription', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 61, 61, 'PubMed番号', 'PubMed番号', 'text', 0, 1, 0, 0, 0, 'pmid', 'relation', 'relation', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 62, 62, 'DOI', 'DOI', 'text', 0, 1, 0, 0, 0, 'doi', 'relation', 'relation', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 63, 63, '異版である', '異版である', 'text', 0, 1, 0, 0, 0, 'isVersionOf', 'relation', 'relationIsVersionOf', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 64, 64, '異版あり', '異版あり', 'text', 0, 1, 0, 0, 0, 'hasVersion', 'relation', 'relationHasVersion', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 65, 65, '要件とされる', '要件とされる', 'text', 0, 1, 0, 0, 0, 'isRequiredBy', 'relation', 'relationIsRequiredBy', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 66, 66, '要件とする', '要件とする', 'text', 0, 1, 0, 0, 0, 'requires', 'relation', 'relationRequires', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 67, 67, '部分である', '部分である', 'text', 0, 1, 0, 0, 0, 'isPartOf', 'relation', 'relationIsPartOf', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 68, 68, '部分を持つ', '部分を持つ', 'text', 0, 1, 0, 0, 0, 'hasPart', 'relation', 'relationHasPart', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 69, 69, '参照される', '参照される', 'text', 0, 1, 0, 0, 0, 'isReferencedBy', 'relation', 'relationIsReferencedBy', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 70, 70, '参照する', '参照する', 'text', 0, 1, 0, 0, 0, 'references', 'relation', 'relationRreferences', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 71, 71, '別フォーマットである', '別フォーマットである', 'text', 0, 1, 0, 0, 0, 'isFormatOf', 'relation', 'relationIsFormatOf', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 72, 72, '別フォーマットあり', '別フォーマットあり', 'text', 0, 1, 0, 0, 0, 'hasFormat', 'relation', 'relationHasFormat', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 73, 73, '基になる', '基になる', 'text', 0, 1, 0, 0, 0, 'relation', 'relation', 'relationIsBasisFor', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 74, 74, '基づいている', '基づいている', 'text', 0, 1, 0, 0, 0, 'relation', 'relation', 'relationIsBasedOn', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 75, 75, '他の資源との関係', '他の資源との関係', 'textarea', 0, 1, 0, 0, 0, 'relation', 'relation', 'relation', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 76, 76, 'コメント(件名)', 'コメント(件名)', 'text', 0, 1, 0, 0, 0, '', '', 'annotationEntity', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 77, 77, 'コメント(日付)', 'コメント(日付)', 'date', 0, 1, 0, 0, 0, '', '', 'annotationDate', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 78, 78, 'コメント', 'コメント', 'textarea', 0, 1, 0, 0, 0, '', '', 'annotationDescription', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 79, '分類目的', '分類目的', 'checkbox', 0, 0, 0, 0, 0, '', '', 'classificationPurpose', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 80, 80, '分類ソース', '分類ソース', 'text', 0, 1, 0, 0, 0, '', '', 'classificationTaxonPathSource', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 81, 81, '分類', '分類', 'text', 0, 1, 0, 0, 0, '', '', 'classificationTaxon', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 82, 82, '分類説明', '分類説明', 'textarea', 0, 1, 0, 0, 0, '', '', 'classificationDescription', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 83, 83, '分類キーワード', '分類キーワード', 'text', 0, 1, 0, 0, 0, '', '', 'classificationKeyword', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 84, 84, '機関ID', '機関ID', 'text', 1, 0, 0, 0, 1, '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 85, 85, 'コンテンツID', 'コンテンツID', 'text', 1, 0, 0, 0, 1, '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 86, 86, 'コンテンツ更新日時', 'コンテンツ更新日時', 'date', 1, 0, 0, 0, 1, '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0);";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }

                    }

                    $query = "SELECT * ".
                             "FROM ".DATABASE_PREFIX."repository_item_attr_candidate ".
                             "WHERE is_delete = ? AND item_type_id = ? ;";
                    $params = array();
                    $params[] = 0;  // is_delete
                    $params[] = 20016;  // item_type_id
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    if(count($retRef) == 0){

                        $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                "VALUE ".
                                "(20016, 5, 1, 'author', 'author', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 5, 2, 'publisher', 'publisher', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 5, 3, 'none', 'none', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 1, 'atomic', 'atomic', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 2, 'collection', 'collection', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 3, 'networked', 'networked', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 4, 'hierarchical', 'hierarchical', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 10, 5, 'linear', 'linear', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 11, 1, 1, 1, 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 11, 2, 2, 2, 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 11, 3, 3, 3, 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 11, 4, 4, 4, 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 13, 1, 'draft', 'draft', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 13, 2, 'final', 'final', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 13, 3, 'revised', 'revised', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 13, 4, 'unavailable', 'unavailable', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 47, 1, 'active', 'active', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 47, 2, 'expositive', 'expositive', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 47, 3, 'mixed', 'mixed', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 1, 'exercise', 'exercise', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 2, 'simulation', 'simulation', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 3, 'questionnaire', 'questionnaire', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 4, 'diagram', 'diagram', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 5, 'figure', 'figure', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 6, 'graph', 'graph', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 7, 'index', 'index', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 8, 'slide', 'slide', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 9, 'table', 'table', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 10, 'narrative text', 'narrative text', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 11, 'exam', 'exam', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 12, 'experiment', 'experiment', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 13, 'problem statement', 'problem statement', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 14, 'self assessment', 'self assessment', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 48, 15, 'lecture', 'lecture', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 1, 'very low', 'very low', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 2, 'low', 'low', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 3, 'medium', 'medium', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 4, 'high', 'high', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 49, 5, 'very high', 'very high', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 1, 'very low', 'very low', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 2, 'low', 'low', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 3, 'medium', 'medium', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 4, 'high', 'high', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 50, 5, 'very high', 'very high', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 51, 1, 'teacher', 'teacher', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 51, 2, 'author', 'author', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 51, 3, 'learner', 'learner', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 51, 4, 'manager', 'manager', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 52, 1, 'school', 'school', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 52, 2, 'higher education', 'higher education', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 52, 3, 'training', 'training', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 52, 4, 'other', 'other', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 1, 'very easy', 'very easy', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 2, 'easy', 'easy', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 3, 'medium', 'medium', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 4, 'difficult', 'difficult', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 54, 5, 'very difficult', 'very difficult', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 58, 1, 'yes', 'yes', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 58, 2, 'no', 'no', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 59, 1, 'yes', 'yes', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 59, 2, 'no', 'no', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 1, 'discipline', 'discipline', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 2, 'idea', 'idea', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 3, 'prerequisite', 'prerequisite', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 4, 'educational objective', 'educational objective', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 5, 'accessibility restrictions', 'accessibility restrictions', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 6, 'educational level', 'educational level', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 7, 'skill level', 'skill level', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 8, 'security level', 'security level', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                                "(20016, 79, 9, 'competency', 'competency', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0);";
                        $retRef = $this->Db->execute($query);
                        if($retRef === false){
                            $errMsg = $this->Db->ErrorMsg();
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }

                    // Add LOM mapping column 2013/1/28 A.Jin --end--

                    // Fix default item type for JuNii2 ver3.0 Y.Nakao 2013/05/23 --start--
                    // SELECT not update default itemtype
                    $query = " SELECT item_type_id, attribute_id, ins_user_id, ins_date ".
                            " FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                            " WHERE is_delete = ? ".
                            " AND item_type_id > ? ".
                            " AND ins_date = mod_date ".
                            " AND junii2_mapping = ? ".
                            " ORDER BY item_type_id ASC, attribute_id ASC; ";
                    $params = array();
                    $params[] = 0;
                    $params[] = 10000;
                    $params[] = 'textversion';
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // INSERT JuNii2 data for textversion, add candidate 'ETD'.
                    $indertQuery = " INSERT ".DATABASE_PREFIX."repository_item_attr_candidate VALUE ";
                    $insertParam = array();
                    $prevAttributeId = 0;
                    for($ii=0; $ii<count($result); $ii++)
                    {
                        $query = " DELETE FROM ".DATABASE_PREFIX."repository_item_attr_candidate ".
                                " WHERE item_type_id = ? ".
                                " AND attribute_id = ?; ";
                        $params = array();
                        $params[] = $result[$ii]['item_type_id'];
                        $params[] = $result[$ii]['attribute_id'];
                        $ret = $this->Db->execute($query, $params);
                        if($ret === false)
                        {
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                        if($ii > 0)
                        {
                            $indertQuery .= ", ";
                        }
                        $indertQuery .= " (?, ?, 1, 'author', 'author', ?, ?, '', ?, ?, '', 0), ";
                        $indertQuery .= " (?, ?, 2, 'publisher', 'publisher', ?, ?, '', ?, ?, '', 0), ";
                        $indertQuery .= " (?, ?, 3, 'ETD', 'ETD', ?, ?, '', ?, ?, '', 0), ";
                        $indertQuery .= " (?, ?, 4, 'none', 'none', ?, ?, '', ?, ?, '', 0) ";
                        for($jj=0; $jj<4; $jj++)
                        {
                            $insertParam[] = $result[$ii]['item_type_id'];
                            $insertParam[] = $result[$ii]['attribute_id'];
                            $insertParam[] = $result[$ii]['ins_user_id'];
                            $insertParam[] = $result[$ii]['ins_user_id'];
                            $insertParam[] = $result[$ii]['ins_date'];
                            $insertParam[] = $result[$ii]['ins_date'];
                        }
                    }
                    $result = $this->Db->execute($indertQuery, $insertParam);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // UPDATE 学位論文 metadata name.
                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                            " SET attribute_name = ?, attribute_short_name = ? ".
                            " WHERE item_type_id = ? ".
                            " AND attribute_id = ? ".
                            " AND input_type = ? ".
                            " AND ins_date = mod_date ".
                            " AND is_delete = ? ";
                    $params = array();
                    $params[] = '学位授与番号';
                    $params[] = '学位授与番号';
                    $params[] = 10006;
                    $params[] = 12;
                    $params[] = 'text';
                    $params[] = 0;
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                            " SET attribute_name = ?, attribute_short_name = ? ".
                            " WHERE item_type_id = ? ".
                            " AND attribute_id = ? ".
                            " AND input_type = ? ".
                            " AND ins_date = mod_date ".
                            " AND is_delete = ? ";
                    $params = array();
                    $params[] = '学位名';
                    $params[] = '学位名';
                    $params[] = 10006;
                    $params[] = 8;
                    $params[] = 'select';
                    $params[] = 0;
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                            " SET attribute_name = ?, attribute_short_name = ? ".
                            " WHERE item_type_id = ? ".
                            " AND attribute_id = ? ".
                            " AND input_type = ? ".
                            " AND ins_date = mod_date ".
                            " AND is_delete = ? ";
                    $params = array();
                    $params[] = '学位授与機関';
                    $params[] = '学位授与機関';
                    $params[] = 10006;
                    $params[] = 9;
                    $params[] = 'text';
                    $params[] = 0;
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // release is_required option for creator.
                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                            " SET is_required = ? ".
                            " WHERE item_type_id > ? ".
                            " AND is_delete = ? ".
                            " AND ins_date = mod_date ".
                            " AND input_type = ? ";
                    $params = array();
                    $params[] = 0;
                    $params[] = 10000;
                    $params[] = 0;
                    $params[] = 'name';
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Fix default item type for JuNii2 ver3.0 Y.Nakao 2013/05/23 --end--

                    $this->versionUp('2.0.5');
                case 205:
                    // Add owner_user_id to repository_index
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD owner_user_id VARCHAR(40) NOT NULL default '' ".
                             "AFTER create_cover_flag;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add private_contents to repository_index
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD private_contents INT NOT NULL default 0 ".
                             "AFTER contents;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    // set edit tree
                    // for update and insert
                    require_once WEBAPP_DIR. '/modules/repository/action/edit/tree/Tree.class.php';
                    $this->editTree = new Repository_Action_Edit_Tree();

                    // set default access role and TransStartDate
                    $this->editTree->Session = $this->Session;
                    $this->editTree->Db = $this->Db;
                    $this->editTree->setDefaultAccessControlList();

                    $this->editTree->recountPrivateContents();

                    // Add privatetree Parameter 2013/04/04 K.Matsuo --start--
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('is_make_privatetree', '0', 'プライベートツリーの自動作成を行うか否か(1:自動作成する, 0:自動作成しない)', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('privatetree_sort_order', '0', 'プライベートツリーのデフォルト表示順序(0:カスタムソート, 1:インデックス名順(昇順), 2:インデックス名順(降順))', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }

                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                             "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                             " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                             "VALUES('privatetree_parent_indexid', '0', 'プライベートツリーが作成される親インデックスのID', ?, ?, '0', ?, ?, '', 0);";
                    $params = array();
                    $params[] = $user_id;   // ins_user_id
                    $params[] = $user_id;   // mod_user_id
                    $params[] = $this->TransStartDate;  // ins_date
                    $params[] = $this->TransStartDate;  // mod_date

                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add privatetree Parameter 2013/04/04 K.Matsuo --end--

                    // delete file_download_type 2013/05/08 --start--
                    $query = "DELETE FROM ".DATABASE_PREFIX."repository_parameter ".
                            " WHERE param_name = ? ";
                    $params = array();
                    $params[] = "file_download_type";
                    $retRef = $this->Db->execute($query, $params);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // delete file_download_type 2013/05/08 --end--

                    $this->versionUp('2.0.6');

                case 206:
                    // Add harvest public parameter 2013/07/03 K.Matsuo --start--
                    // Add owner_user_id to repository_index
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_index ".
                             "ADD harvest_public_state INT(1) NOT NULL default 1 ".
                             "AFTER create_cover_flag;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add harvest public parameter 2013/07/03 K.Matsuo --end--
                    // Add itemtype multi language 2013/07/22 K.Matsuo --start--
                    $query = "CREATE TABLE ".DATABASE_PREFIX."repository_item_type_name_multilanguage ( ".
                             " `item_type_id` INT NOT NULL default 0, ".
                             " `attribute_id` INT NOT NULL default 0, ".
                             " `language` VARCHAR(64) NOT NULL default '', ".
                             " `item_type_name` TEXT NOT NULL, ".
                             " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                             " `ins_date` VARCHAR(23), ".
                             " `mod_date` VARCHAR(23), ".
                             " `del_date` VARCHAR(23), ".
                             " `is_delete` INT(1), ".
                             " PRIMARY KEY(`item_type_id`, `attribute_id`, `language`) ".
                             " ) ENGINE=innodb; ";
                    $result = $this->Db->execute($query);
                    if($result === false){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add itemtype multi language 2013/07/22 K.Matsuo --end--
                    // Add LOM Mapping Name Change 2013/08/22 K.Matsuo --start--
                    $preLomMapping = array('lifeCycleContributeAuthor', 'lifeCycleContributePublisher', 'lifeCycleContributePublishDate', 'lifeCycleContributeInitiator',
                                           'lifeCycleContributeTerminator', 'lifeCycleContributeValidator', 'lifeCycleContributeEditor', 'lifeCycleContributeGraphicalDesigner',
                                           'lifeCycleContributeTechnicalImplementer', 'lifeCycleContributeContentProvider', 'lifeCycleContributeTechnicalValidator', 'lifeCycleContributeEducationalValidator',
                                           'lifeCycleContributeScriptWriter', 'lifeCycleContributeInstructionalDesigner', 'lifeCycleContributeSubjectMatterExpert', 'lifeCycleContributeUnknown',
                                           'metaMetadataContributeCreator', 'metaMetadataContributeValidator', 'relation', 'classificationTaxon',
                                           'relationRreferences');
                    $newLomMapping = array('lifeCycleContributeRoleAuthor', 'lifeCycleContributeRolePublisher', 'lifeCycleContributeDate', 'lifeCycleContributeRoleInitiator',
                                           'lifeCycleContributeRoleTerminator', 'lifeCycleContributeRoleValidator', 'lifeCycleContributeRoleEditor', 'lifeCycleContributeRoleGraphicalDesigner',
                                           'lifeCycleContributeRoleTechnicalImplementer', 'lifeCycleContributeRoleContentProvider', 'lifeCycleContributeRoleTechnicalValidator', 'lifeCycleContributeRoleEducationalValidator',
                                           'lifeCycleContributeRoleScriptWriter', 'lifeCycleContributeRoleInstructionalDesigner', 'lifeCycleContributeRoleSubjectMatterExpert', 'lifeCycleContributeRoleUnknown',
                                           'metaMetadataContributeRoleCreator', 'metaMetadataContributeRoleValidator', 'relationResource', 'classificationTaxonPathTaxon',
                                           'relationReferences');
                    for($ii = 0; $ii < count($preLomMapping); $ii++){
                        $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                                " SET lom_mapping = ? ".
                                " WHERE lom_mapping = ? ";
                        $params = array();
                        $params[] = $newLomMapping[$ii];
                        $params[] = $preLomMapping[$ii];
                        $result = $this->Db->execute($query, $params);
                        if($result === false)
                        {
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    $changeAttributeNameArray = array('9' => '適用範囲', '12' => 'エディション（版）', '13' => 'ステータス（現状）', '14' => '著者',
                                                      '15' => '発行者', '16' => '寄与の日付', '17' => '原作者', '18' => '終了者（廃止者）', '19' => '校閲者',
                                                      '20' => '編集者', '21' => 'グラフィックデザイナー', '22' => '技術', '23' => 'コンテンツプロバイダー',
                                                      '24' => '技術監修', '25' => '教育監修', '26' => '台本作者', '27' => 'インストラクショナルデザイナー',
                                                      '28' => '教科・専門家', '29' => 'その他の関係者(未知)', '30' => '寄与者', '32' => 'メタデータ作成者',
                                                      '33' => 'メタデータ校閲者', '34' => 'メタデータへの寄与', '36' => 'メタデータの記述言語', '47' => '双方向性・種別',
                                                      '48' => '学習資源・種別', '49' => '双方向性・レベル', '50' => '意味論的密度', '51' => '想定利用者',
                                                      '52' => '想定利用環境', '53' => '想定利用者の対象年齢', '54' => '想定利用者における難易度', '55' => '推定学習時間',
                                                      '56' => '教育情報・備考', '57' => '想定利用者の使用言語', '58' => '使用料', '59' => '著作権・利用制限条件',
                                                      '60' => '権利関係・備考', '76' => '教育利用コメント・作成者', '77' => '教育利用コメント・作成日', '78' => '教育利用コメント・備考',
                                                      '80' => '分類体系の所在・名称', '81' => '分類体系における分類項目', '82' => '分類・備考', '83' => '分類・キーワード');
                    foreach($changeAttributeNameArray as $key => $value){
                        $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type ".
                                " SET attribute_name  = ? , attribute_short_name = ? ".
                                " WHERE item_type_id = ? ".
                                " AND attribute_id = ? ".
                                " AND is_delete = ? ;";
                        $params = array();
                        $params[] = $value;
                        $params[] = $value;
                        $params[] = 20016;
                        $params[] = $key;
                        $params[] = 0;
                        $result = $this->Db->execute($query, $params);
                        if($result === false)
                        {
                            // Rollback
                            $this->failTrans();
                            throw $exception;
                        }
                    }
                    // Add LOM Mapping Name Change 2013/08/22 K.Matsuo --end--

                    // Add harvest parameter 2013/08/14 K.Matsuo --start--
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_harvesting ".
                             "ADD from_date VARCHAR(20) default NULL ".
                             "AFTER base_url;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_harvesting ".
                             "ADD until_date VARCHAR(20) default NULL ".
                             "AFTER from_date;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_harvesting ".
                             "ADD set_param TEXT ".
                             "AFTER until_date;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    $query = "ALTER TABLE ".DATABASE_PREFIX."repository_harvesting ".
                             "ADD execution_date VARCHAR(20) default NULL ".
                             "AFTER automatic_sorting;";
                    $retRef = $this->Db->execute($query);
                    if($retRef === false || count($retRef)!=1){
                        $errMsg = $this->Db->ErrorMsg();
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add harvest parameter 2013/08/14 K.Matsuo --end--

                    // Add 学位名を選択肢から自由入力に変更 2013/8/20 A.jin --start--
                    $query = " UPDATE ".DATABASE_PREFIX."repository_item_attr_type AS ATTR_TYPE, ".DATABASE_PREFIX."repository_item_attr_candidate AS ATTR_CANDIDATE ".
                        " SET ATTR_TYPE.input_type = ? , ATTR_CANDIDATE.is_delete= ? ".
                        " WHERE ATTR_TYPE.item_type_id = ? ".
                        " AND ATTR_TYPE.attribute_id = ? ".
                        " AND ATTR_TYPE.input_type = ? ".
                        " AND ATTR_CANDIDATE.item_type_id = ? ".
                        " AND ATTR_CANDIDATE.attribute_id = ? ".
                        " AND ATTR_TYPE.ins_date = ATTR_TYPE.mod_date ".
                        " AND ATTR_TYPE.is_delete = ? ".
                        " AND ATTR_CANDIDATE.is_delete = ? ;";
                    $params = array();
                    $params[] = 'text';
                    $params[] = 1;
                    $params[] = 10006;
                    $params[] = 8;
                    $params[] = 'select';
                    $params[] = 10006;
                    $params[] = 8;
                    $params[] = 0;
                    $params[] = 0;
                    $result = $this->Db->execute($query, $params);
                    if($result === false)
                    {
                        // Rollback
                        $this->failTrans();
                        throw $exception;
                    }
                    // Add 学位名を選択肢から自由入力に変更 2013/8/20 A.jin --end--


                    // Add set LOM Multi-Language 2013/08/22 K.Matsuo --start--
                    $this->setLOMMultiLang($user_id);
                    // Add set LOM Multi-Language 2013/08/22 K.Matsuo --end--

                    $this->versionUp('2.0.7');

                // Add JuNii2 revision 2013/09/17 R.Matsuura --start--
                case 207:
                    $this->updateWekoVersion207To208();
                // Add JuNii2 revision 2013/09/17 R.Matsuura --end--

                // Add e-person 2013/10/21 R.Matsuura --start--
                case 208:
                    $this->updateWekoVersion208To209();
                // Add e-person 2013/10/21 R.Matsuura --end--
                // Add advanced search 2013/11/20 R.Matsuura --start--
                case 209:
                    $this->updateWekoVersion209To2010();
                // Add advanced search 2013/11/20 R.Matsuura --end--
                // Add OpenDepo 2013/12/02 R.Matsuura --start--
                case 2010:
                    $this->updateWekoVersion2010To2011();
                // Add OpenDepo 2013/12/02 R.Matsuura --end--
                // Add New Prefix 2013/12/20 T.Ichikawa --start--
                case 2011:
                    $this->updateWekoVersion2011To2012();
                // Add New Prefix 2013/12/20 T.Ichikawa --end--
                // Add Filter Update 2014/02/20 R.Matsuura --start--
                case 2012:
                    $this->updateWekoVersion2012To210();
                    $this->versionUp('2.1.0');
                // Add Filter Update 2014/02/20 R.Matsuura --end--
                // Add Update 2014/04/04 K.Matsuo --start--
                case 210:
                    $this->updateWekoVersion210To211();
                    $this->versionUp('2.1.1');
                // Add Update 2014/04/04 K.Matsuo --end--
                case 211:
                    $this->updateWekoVersion211To212();
                    $this->versionUp('2.1.2');
                case 212:
                    $this->updateWekoVersion212To213();
                case 213:
                    $this->updateWekoVersion213To214();
                case 214:
                    $this->updateWekoVersion214To215();
                case 215:
                    $this->updateWekoVersion215To216();
                case 216:
                    $this->updateWekoVersion216To217();
                // Add Default Search Type 2014/12/03 K.Sugimoto --start--
                case 217:
					          //$this->updateWekoVersionSpase();
                    $this->updateWekoVersion217To218();
                // Add Default Search Type 2014/12/03 K.Sugimoto --end--
                // Add Gakunin 2015/01/16 T.Ichikawa --start--
                case 218:
                    $this->updateWekoVersion218To220();
                // Add Gakunin 2015/01/16 T.Ichikawa --end--
                case 220:
                    $this->updateWekoVersion220To221();
                case 221:
                    $this->updateWekoVersion221To222();
                case 222:
                    $this->updateWekoVersion222To223();
                case 223:
                    $this->updateWekoVersionSpase();
                default :
                    #$this->updateWekoVersionSpase();
                    break;
            }
            $this->executeRecursiveProcessing();

            // make OpenSearch description document
            require_once WEBAPP_DIR."/modules/repository/opensearch/Opensearch.class.php";
            $openSearch = new Repository_Opensearch();
            $openSearch->Db = $this->Db;
            $handle = fopen(BASE_DIR."/htdocs/weko/opensearch/description.xml","w");
            $xml = $openSearch->getDescription();
            fwrite($handle, $xml);
            fclose($handle);
            // Modify Directory specification K.Matsuo 2011/9/1 -----end------

            // COMMIT
            $result = $this->exitAction();

            return "true";

        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",
                "execute",
                $Exception->getCode(),
                $Exception->getMessage(),
                $Exception->getDetailMsg() );
            $this->failTrans();
            return false;
        }
    }

    /**
     * make real file from DB(Blob)
     *
     * @param unknown_type $path
     * @param unknown_type $file
     */
    function createFile($path, $file ){
        // file open
        $fp = @fopen( $path, "w" );
        if ( !$fp ) {
            //error
            return false;
        }
        // file output
        if ( fwrite($fp, $file) == FALSE ) {
            // error
            return false;
        }
        if (isset($fp)){
            // file close
            fclose($fp);
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

    // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
    function moveFlashToFolder(){
        $flashDir = $this->makeFlashFolder();
        if( file_exists($flashDir) ){
            if ($handle = opendir("$flashDir")) {
                while (false !== ($file = readdir($handle))) {
                    if (strpos($file, "svn")===false && $file != "." && $file != "..") {
                        if (!is_dir("$flashDir/$file")) {
                            // case file
                            $name = basename($file, '.swf');
                            if(file_exists("$flashDir/$name.swf")){
                                if(!file_exists("$flashDir/$name")){
                                    mkdir("$flashDir/$name", 0777);
                                }
                                rename("$flashDir/$file", "$flashDir/$name/weko.swf");
                            }
                        }
                    }
                }
                closedir($handle);
            }
        }
    }
    // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--

    /**
     * log for WEKO_version
     *
     * @param unknown_type $ver
     */
    function versionUp($ver)
    {
        $query = "UPDATE ".DATABASE_PREFIX ."repository_parameter ".
                " SET param_value = ?, ".
                " mod_user_id = ?, ".
                " mod_date = ? ".
                " WHERE param_name = ?;";
        $params = array();
        $params[] = $ver;
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->TransStartDate;
        $params[] = 'WEKO_version';
        $retRef = $this->Db->execute($query, $params);
        if($retRef === false){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            // Rollback
            $this->failTrans();
            throw $exception;
        }
    }

    /**
     * Check exist column
     *
     * @param string $table
     * @param string $column
     * @return bool true: exist / false: not exist
     */
    private function isExistColumn($table, $column)
    {
        $query = "DESCRIBE ".$table." ".$column.";";
        $retRef = $this->Db->execute($query);
        if($retRef === false){
            $this->failTrans();
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            throw $exception;
        }
        if(count($retRef)>0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    // Add set LOM Multi-Language 2013/08/22 K.Matsuo --start--
    /**
     * set multi-language data to Leargnin Object Metadata
     *
     */
    private function setLOMMultiLang($user_id)
    {
       $japaneseArray =
           array('1' => 'URI' , '2' => 'ISSN' , '3' => 'NCID' , '4' => '書誌情報' , '5' => '著者版フラグ' ,
                 '6' => 'その他の識別子' , '7' => '言語' , '8' => '内容記述' , '9' => '適用範囲' , '10' => '構成' ,
                 '11' => '粒度' , '12' => 'エディション（版）' , '13' => 'ステータス（現状）' , '14' => '著者' , '15' => '発行者' ,
                 '16' => '寄与の日付' , '17' => '原作者' , '18' => '更新者' , '19' => '校閲者' , '20' => '編集者' ,
                 '21' => 'グラフィックデザイナー' , '22' => '技術' , '23' => 'コンテンツプロバイダー' , '24' => '技術監修' , '25' => '教育監修' ,
                 '26' => '台本作者' , '27' => 'インストラクショナルデザイナー' , '28' => '教科・専門家' , '29' => 'その他の関係者(未知)' , '30' => '寄与者' ,
                 '31' => 'メタデータ識別子' , '32' => 'メタデータ作成者' , '33' => 'メタデータ校閲者' , '34' => 'メタデータの寄与者' , '35' => 'メタデータスキーマ' ,
                 '36' => 'メタデータの記述言語' , '37' => 'ファイルフォーマット' , '38' => 'ファイルサイズ' , '39' => 'ファイルリンク（URL/URI）' , '40' => '動作環境' ,
                 '41' => '動作条件' , '42' => '推奨バージョン(下限)' , '43' => '推奨バージョン(上限)' , '44' => 'インストール手順' , '45' => 'その他の技術要件' ,
                 '46' => '再生時間' , '47' => '双方向性・種別' , '48' => '学習資源・種別' , '49' => '双方向性・レベル' , '50' => '意味論的密度' ,
                 '51' => '想定利用者' , '52' => '想定利用環境' , '53' => '想定利用者の対象年齢' , '54' => '想定利用者における難易度' , '55' => '推定学習時間' ,
                 '56' => '教育情報・備考' , '57' => '想定利用者の使用言語' , '58' => '使用料' , '59' => '著作権・利用制限条件' , '60' => '権利関係・備考' ,
                 '61' => 'PubMed番号' , '62' => 'DOI' , '63' => '異版である' , '64' => '異版あり' , '65' => '要件とされる' ,
                 '66' => '要件とする' , '67' => '部分である' , '68' => '部分を持つ' , '69' => '参照される' , '70' => '参照する' ,
                 '71' => '別フォーマットである' , '72' => '別フォーマットあり' , '73' => '基になる' , '74' => '基づいている' , '75' => '他の資源との関係' ,
                 '76' => '教育利用コメント・作成者' , '77' => '教育利用コメント・作成日' , '78' => '教育利用コメント・備考' , '79' => '分類・目的' , '80' => '分類体系の所在・名称' ,
                 '81' => '分類体系における分類項目' , '82' => '分類・備考' , '83' => '分類・キーワード' , '84' => '機関ID' , '85' => 'コンテンツID' , '86' => 'コンテンツ更新日時' );
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type_name_multilanguage VALUE ";
        $params = array();
        $addComma = false;
        foreach($japaneseArray as $key => $value){
            if($addComma){
                $query .= ", ";
            }
            $query .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = 20016;
            $params[] = intval($key);
            $params[] = "japanese";
            $params[] = $value;
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = "";
            $params[] = $this->TransStartDate;
            $params[] = $this->TransStartDate;
            $params[] = "";
            $params[] = 0;
            $addComma = true;
        }
        $query .= ";";
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            // Rollback
            $this->failTrans();
            throw $exception;
        }
        $englishArray =
            array('1' => 'URI' , '2' => 'ISSN' , '3' => 'NCID' , '4' => 'Bibliographic information' , '5' => 'Author version flag' ,
                  '6' => 'Other identifiers' , '7' => 'Language' , '8' => 'Description' , '9' => 'Coverage' , '10' => 'Structure' ,
                  '11' => 'Aggregation Level' , '12' => 'Life cycle-version' , '13' => 'Status' , '14' => 'Author' , '15' => 'Publisher' ,
                  '16' => 'Contribute-Date' , '17' => 'Initiator' , '18' => 'Terminator' , '19' => 'Validator' , '20' => 'Editor' ,
                  '21' => 'Graphical Designer' , '22' => 'Technical Implementer' , '23' => 'Content Provider' , '24' => 'Technical Validator' , '25' => 'Educational Validator' ,
                  '26' => 'Script Writer' , '27' => 'Instructional Designer' , '28' => 'Subject Matter Expert' , '29' => 'Unknown' , '30' => 'Contribute' ,
                  '31' => 'Metadata-Identifier' , '32' => 'Metadeta-Creator' , '33' => 'Metadata-Validator' , '34' => 'Metadata-Contribute' , '35' => 'Metadata Schema' ,
                  '36' => 'Metadata-Language' , '37' => 'Format' , '38' => 'Size' , '39' => 'Location' , '40' => 'Requirement-OrComposite-Type' ,
                  '41' => 'Requirement-OrComposite-Name' , '42' => 'Minimum Version' , '43' => 'Maximum Version' , '44' => 'Installation Remarks' , '45' => 'Other Platform Requirements' ,
                  '46' => 'Duration' , '47' => 'Interactivity Type' , '48' => 'Learning Resource Type' , '49' => 'Interactivity Level' , '50' => 'Semantic Density' ,
                  '51' => 'Intended End User Role' , '52' => 'Context' , '53' => 'Typical Age Range' , '54' => 'Difficulty' , '55' => 'Typical Learning Time' ,
                  '56' => 'Educational-Description' , '57' => 'Educational-Language' , '58' => 'Cost' , '59' => 'Copyright and Other Restrictions' , '60' => 'Rights-Description' ,
                  '61' => 'PubMed number' , '62' => 'DOI' , '63' => 'isversionof: is version of' , '64' => 'hasversion: has version' , '65' => 'isrequiredby: is required by' ,
                  '66' => 'requires: requires' , '67' => 'ispartof: is part of' , '68' => 'haspart: has part' , '69' => 'isreferencedby: is referenced by' , '70' => 'references: references' ,
                  '71' => 'isformatof: is format of' , '72' => 'hasformat: has format' , '73' => 'isbasisfor: is basis for' , '74' => 'isbasedon: is based on' , '75' => 'Relation' ,
                  '76' => 'Annotation-Entity' , '77' => 'Annotation-Date' , '78' => 'Annotation-Description' , '79' => 'Purpose' , '80' => 'Taxon Path-Source' , '81' => 'Taxon Path-Taxon' ,
                  '82' => 'Classification-Description' , '83' => 'Classification-keyword' , '84' => 'Authority Identifier' , '85' => 'Content Identifier' , '86' => 'Modified content' );

        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type_name_multilanguage VALUE ";
        $params = array();
        $addComma = false;
        foreach($englishArray as $key => $value){
            if($addComma){
                $query .= ", ";
            }
            $query .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = 20016;
            $params[] = intval($key);
            $params[] = "english";
            $params[] = $value;
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = "";
            $params[] = $this->TransStartDate;
            $params[] = $this->TransStartDate;
            $params[] = "";
            $params[] = 0;
            $addComma = true;
        }
        $query .= ";";
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            // Rollback
            $this->failTrans();
            throw $exception;
        }

        $chineseArray =
            array('1' => 'URI' , '2' => 'ISSN' , '3' => 'NCID' , '4' => '书目信息' , '5' => '作者版本标志' , '6' => '其他标识符' , '7' => '语言' ,
                  '8' => '内容' , '9' => '范围' , '10' => '结构' , '11' => '聚合程度' , '12' => '版本' , '13' => '现况' , '14' => '作者' ,
                  '15' => '发表者' , '16' => '发表日期' , '17' => '原始作者' , '18' => '最近作者' , '19' => '校阅者' , '20' => '编辑' , '21' => '绘图员' ,
                  '22' => '技术员' , '23' => '内容提供者' , '24' => '技术监察' , '25' => '教育审阅' , '26' => '剧本作者' , '27' => '教学设计' , '28' => '科目专家' ,
                  '29' => '提供者未明' , '30' => '提供者' , '31' => '标记' , '32' => '作者' , '33' => '审阅者' , '34' => '提供者' , '35' => '诠释资料结构定义档' ,
                  '36' => '诠释资料语言' , '37' => '格式' , '38' => '大小' , '39' => '地点' , '40' => '要求或组合类型' , '41' => '要求或组合名称' , '42' => '要求或组合最低版本' ,
                  '43' => '要求或组合最高版本' , '44' => '安装指示' , '45' => '其他系统要求' , '46' => '时限' , '47' => '互动类型' , '48' => '学习资源类型' , '49' => '互动程度' ,
                  '50' => '语义密度' , '51' => '意属使用者' , '52' => '环境' , '53' => '通常年龄范围' , '54' => '难易度' , '55' => '通常学习时间' , '56' => '描述' ,
                  '57' => '语言' , '58' => '价格' , '59' => '版权及其他限制' , '60' => '描述' , '61' => '考研人数' , '62' => 'DOI' , '63' => '本版本为' ,
                  '64' => '其他版本' , '65' => '要求者' , '66' => '有下列要求' , '67' => '本版本为下列物件的一部分' , '68' => '其他组合' , '69' => '参照提供者' , '70' => '参照' ,
                  '71' => '格式' , '72' => '其他格式' , '73' => '为下列物件的基础' , '74' => '物件建基于' , '75' => '其他关系' , '76' => '物件' , '77' => '日期' ,
                  '78' => '描述' , '79' => '目的' , '80' => '分类（来源）' , '81' => '分类' , '82' => '描述' , '83' => '关键字' , '84' => '机构标记' , '85' => '内容标记' , '86' => '修改后的内容' );
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type_name_multilanguage VALUE ";
        $params = array();
        $addComma = false;
        foreach($chineseArray as $key => $value){
            if($addComma){
                $query .= ", ";
            }
            $query .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = 20016;
            $params[] = intval($key);
            $params[] = "chinese";
            $params[] = $value;
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = "";
            $params[] = $this->TransStartDate;
            $params[] = $this->TransStartDate;
            $params[] = "";
            $params[] = 0;
            $addComma = true;
        }
        $query .= ";";
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            // Rollback
            $this->failTrans();
            throw $exception;
        }
    }
    // Add set LOM Multi-Language 2013/08/22 K.Matsuo --end--

    // Add JuNii2 revision 2013/09/17 R.Matsuura --start--
    /**
     * update WEKO version 207 to version 208
     *
     */
    private function updateWekoVersion207To208()
    {
        // update JuNii2 version 2 to version 3
        $this->updateJuNii2Version2To3();
        // add Private Tree Composition K.Matsuo 2013/10/01
        $this->addPrivateTreeCompositionToParameter();
        // add File maintenance specification change K.Matsuo 2013/10/02
        // insert show_order to repository_file and repository_thumbnail
        $this->addShorOrderToFileAndThumbnail();
        // add reference url record to repository_parameter S.Suzuki 2013/10/09
        $this->insertCitedReferenceUrlToParameter();
        // version up to 2.0.8
        $this->versionUp('2.0.8');
    }

    /**
     * update JuNii2 version 2 to version 3
     *
     */
    private function updateJuNii2Version2To3()
    {
        // add new attr that added revision of JuNii2Ver3 to harvest default item type
        for($itemTypeId = 20002; $itemTypeId <= 20015; $itemTypeId++)
        {
            $this->insertNewAttrForJuNii2Ver3($itemTypeId);
        }
        // add new element for JuNii2Ver3
        $query = "UPDATE ".DATABASE_PREFIX ."repository_element_cd ".
                " SET id = id + ?, ".
                " seq = seq + ? ".
                " WHERE id = ?;";
        for($elementId = 560; $elementId >= 250; $elementId -= 10)
        {
            $params = array();
            if($elementId >= 380)
            {
                $params[] = 30;
                $params[] = 3;
            }
            else
            {
                $params[] = 10;
                $params[] = 1;
            }
            $params[] = $elementId;
            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
        }

        $params_array = array();
        $params_array[] = array('id' => 250, 'seq' => 25, 'data' => 'isbn', 'minoccur' => 0,
                                'maxoccur' => 0, 'shoshiki' => '', 'oai_dc_element' => 'identifier',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 390, 'seq' => 39, 'data' => 'NAID', 'minoccur' => 0,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'relation',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 400, 'seq' => 40, 'data' => 'ichushi', 'minoccur' => 0,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'relation',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 600, 'seq' => 60, 'data' => 'grandid', 'minoccur' => -1,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'identifier',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 610, 'seq' => 61, 'data' => 'dateofgranted', 'minoccur' => -1,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'data',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 620, 'seq' => 62, 'data' => 'degreename', 'minoccur' => -1,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'description',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');
        $params_array[] = array('id' => 630, 'seq' => 63, 'data' => 'grantor', 'minoccur' => -1,
                                'maxoccur' => 1, 'shoshiki' => '', 'oai_dc_element' => 'description',
                                'oai_dc_qualifier' => '', 'oai_dc_option' => '');

        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_element_cd ".
                 "(id, seq, data, minoccurs, maxoccurs, ".
                 " shoshiki, oai_dc_element, oai_dc_qualifier, oai_dc_option) ".
                 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);";
        for($nCnt = 0; $nCnt < count($params_array); $nCnt++)
        {
            $params = array();
            $params[] = $params_array[$nCnt]['id'];   // id
            $params[] = $params_array[$nCnt]['seq'];   // seq
            $params[] = $params_array[$nCnt]['data'];  // data
            $params[] = $params_array[$nCnt]['minoccur'];  // minoccur
            $params[] = $params_array[$nCnt]['maxoccur'];  // maxoccur
            $params[] = $params_array[$nCnt]['shoshiki'];  // shoshiki
            $params[] = $params_array[$nCnt]['oai_dc_element'];  // oai_dc_element
            $params[] = $params_array[$nCnt]['oai_dc_qualifier'];  // oai_dc_qualifier
            $params[] = $params_array[$nCnt]['oai_dc_option'];  // oai_dc_option

            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
        }

        // Add if default itemtype is not changed, changes metadata junii2 mapping T.Koyasu -start-
        $query = "UPDATE ". DATABASE_PREFIX. "repository_item_attr_type ".
                " SET junii2_mapping = ?".
                " WHERE ins_date = mod_date ".
                " AND item_type_id = ? ".
                " AND attribute_id = ? ".
                " AND is_delete = ?;";

        $paramsList = array();

        $params = array();
        $params[] = 'degreename';
        $params[] = 10006;
        $params[] = 8;
        $params[] = 0;
        array_push($paramsList, $params);

        $params = array();
        $params[] = 'grantor';
        $params[] = 10006;
        $params[] = 9;
        $params[] = 0;
        array_push($paramsList, $params);

        $params = array();
        $params[] = 'dateofgranted';
        $params[] = 10006;
        $params[] = 11;
        $params[] = 0;
        array_push($paramsList, $params);

        $params = array();
        $params[] = 'grantid';
        $params[] = 10006;
        $params[] = 12;
        $params[] = 0;
        array_push($paramsList, $params);

        for($cnt = 0; $cnt < count($paramsList); $cnt++)
        {
            $result = $this->Db->execute($query, $paramsList[$cnt]);

            if($result === false)
            {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                $this->failTrans();
                throw $exception;
            }
        }
        // Add if default itemtype is not changed, changes metadata junii2 mapping T.Koyasu -end-

        // fix No.63 2014/04/04 R.Matsuura --start--
        $this->updateNAIDandISBNJunii2Mapping();
        // fix No.63 2014/04/04 R.Matsuura --end--
    }

    /**
     * insert new atrr for JuNii2ver3
     *
     * @param int $itemTypeId
     */
    private function insertNewAttrForJuNii2Ver3($itemTypeId)
    {
        // get user ID from Session
        $userId = $this->Session->getParameter("_user_id");
        // get max attribute ID
        $query = " SELECT MAX(attribute_id) ".
                " FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                " WHERE item_type_id = ?; ";
        $params = array();
        $params[] = $itemTypeId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) != 1)
        {
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            // Rollback
            $this->failTrans();
            throw $exception;
        }
        $maxAttrId = $result[0]['MAX(attribute_id)'];
        // update show order
        for($attrId = 25; $attrId <= $maxAttrId; $attrId++)
        {
            $query = "UPDATE ".DATABASE_PREFIX ."repository_item_attr_type ".
                    " SET show_order =  show_order + ? ".
                    " WHERE attribute_id = ? ".
                    " AND item_type_id = ?; ";
            $params = array();
            if($attrId <= 32)
            {
                $params[] = 1;
            }
            else if($attrId <= 51)
            {
                $params[] = 3;
            }
            else
            {
                $params[] = 7;
            }
            $params[] = $attrId;
            $params[] = $itemTypeId;
            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
        }

        // insert new attribute for JuNii2Ver3
        $params_array = array();
        // grantid
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+1, 'show_order' => 55, 'attribute_name' => '学位授与番号',
                                'attribute_short_name' => '学位授与番号', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'grantid',
                                'dublin_core_mapping' => 'identifier', 'lom_mapping' => '', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // dateofgranted
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+2, 'show_order' => 56, 'attribute_name' => '学位授与年月日',
                                'attribute_short_name' => '学位授与年月日', 'input_type' => 'date', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'dateofgranted',
                                'dublin_core_mapping' => 'date', 'lom_mapping' => '', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // degreename
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+3, 'show_order' => 57, 'attribute_name' => '学位名',
                                'attribute_short_name' => '学位名', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'degreename',
                                'dublin_core_mapping' => 'description', 'lom_mapping' => '', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // grantor
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+4, 'show_order' => 58, 'attribute_name' => '学位授与機関',
                                'attribute_short_name' => '学位授与機関', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'grantor',
                                'dublin_core_mapping' => 'description', 'lom_mapping' => '', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // NAID
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+5, 'show_order' => 34, 'attribute_name' => 'NII論文ID',
                                'attribute_short_name' => 'NII論文ID', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'NAID',
                                'dublin_core_mapping' => 'relation', 'lom_mapping' => 'relation', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // ICHUSHI
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+6, 'show_order' => 35, 'attribute_name' => '医中誌ID',
                                'attribute_short_name' => '医中誌ID', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 0,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'ichushi',
                                'dublin_core_mapping' => 'relation', 'lom_mapping' => 'relation', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);
        // ISBN
        $params_array[] = array('item_type_id' => $itemTypeId, 'attribute_id' => $maxAttrId+7, 'show_order' => 25, 'attribute_name' => 'ISBN',
                                'attribute_short_name' => 'ISBN', 'input_type' => 'text', 'is_required' => 0, 'plural_enable' => 1,
                                'line_feed_enable' => 0, 'list_view_enable' => 0, 'hidden' => 0, 'junii2_mapping' => 'isbn',
                                'dublin_core_mapping' => 'identifier', 'lom_mapping' => '', 'display_lang_type' => '', 'ins_user_id' => $userId,
                                'mod_user_id' => $userId, 'ins_date' => $this->TransStartDate, 'mod_date' => $this->TransStartDate);

        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_item_attr_type ".
                 "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                 " input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                 " junii2_mapping, dublin_core_mapping, lom_mapping, spase_mapping, display_lang_type, ins_user_id, ".
                 " mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, '', 0);";
        for($nCnt = 0; $nCnt < count($params_array); $nCnt++)
        {
            $params = array();
            $params[] = $params_array[$nCnt]['item_type_id'];   // item_type_id
            $params[] = $params_array[$nCnt]['attribute_id'];   // attribute_id
            $params[] = $params_array[$nCnt]['show_order'];  // show_order
            $params[] = $params_array[$nCnt]['attribute_name'];  // attribute_name
            $params[] = $params_array[$nCnt]['attribute_short_name'];  // attribute_short_name
            $params[] = $params_array[$nCnt]['input_type'];  // input_type
            $params[] = $params_array[$nCnt]['is_required'];  // is_required
            $params[] = $params_array[$nCnt]['plural_enable'];  // plural_enable
            $params[] = $params_array[$nCnt]['line_feed_enable'];  // line_feed_enable
            $params[] = $params_array[$nCnt]['list_view_enable'];   // list_view_enable
            $params[] = $params_array[$nCnt]['hidden'];   // hidden
            $params[] = $params_array[$nCnt]['junii2_mapping'];   // junii2_mapping
            $params[] = $params_array[$nCnt]['dublin_core_mapping'];  // dublin_core_mapping
            $params[] = $params_array[$nCnt]['lom_mapping'];  // lom_mapping
			$params[] = $params_array[$nCnt]['spase_mapping'];  // lom_mapping
            $params[] = $params_array[$nCnt]['display_lang_type'];  // display_lang_type
            $params[] = $params_array[$nCnt]['ins_user_id'];  // ins_user_id
            $params[] = $params_array[$nCnt]['mod_user_id'];  // mod_user_id
            $params[] = $params_array[$nCnt]['ins_date'];  // ins_date
            $params[] = $params_array[$nCnt]['mod_date'];  // mod_date

            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
                // Rollback
                $this->failTrans();
                throw $exception;
            }
        }
    }
    // Add JuNii2 revision 2013/09/17 R.Matsuura --end--

    // Add Private Tree index composition K.Matsuo 2013/10/01 --start--
    /**
     * Add Private Tree index composition to repository_parameter table
     *
     */
    private function addPrivateTreeCompositionToParameter()
    {
        // get user ID from Session
        $user_id = $this->Session->getParameter("_user_id");
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                 "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                 " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES('private_tree_composition', ?, 'プライベートツリー以下に作成されるインデックス構成', ?, ?, '0', ?, ?, '', 0);";
        $params = array();
        $params[] = "";    // param_value
        $params[] = $user_id;   // ins_user_id
        $params[] = $user_id;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $retRef = $this->Db->execute($query, $params);
        if($retRef === false || count($retRef)!=1){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }

    }
    // Add Private Tree index composition K.Matsuo 2013/10/01 --end--
    // Add File maintenance specification change K.Matsuo 2013/10/02 --start--
    /**
     * Add show_order to repository_file and repository_thumbnail
     *
     */
    private function addShorOrderToFileAndThumbnail()
    {
        //The item of ShowOrder is added to a repository_file
        // "show_order" 追加
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_file ".
                 "ADD `show_order` INT default 0 AFTER `display_type`;";

        $retRef = $this->Db->execute($query);
        if($retRef === false || count($retRef)!=1){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }
        // 既存のデータのデータのshow_orderはfile_noと同じ
        // show_order of the data of the existing data is the same as file_no.
        $query = "UPDATE ".DATABASE_PREFIX ."repository_file ".
                " SET show_order = file_no; ";
        $retRef = $this->Db->execute($query);
        if($retRef === false){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            // Rollback
            $this->failTrans();
            throw $exception;
        }

        //The item of ShowOrder is added to a repository_thumbnail
        // "show_order" 追加
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_thumbnail ".
                 "ADD `show_order` INT default 0 AFTER `file_name`;";

        $retRef = $this->Db->execute($query);
        if($retRef === false || count($retRef)!=1){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }
        // 既存のデータのデータのshow_orderはfile_noと同じ
        // show_order of the data of the existing data is the same as file_no.
        $query = "UPDATE ".DATABASE_PREFIX ."repository_thumbnail ".
                " SET show_order = file_no; ";
        $retRef = $this->Db->execute($query);
        if($retRef === false){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            // Rollback
            $this->failTrans();
            throw $exception;
        }

    }
    // Add File maintenance specification change K.Matsuo 2013/10/02 --end--

    // add set reference URL to repository_parameter 2013/10/10 S.Suzuki --start--
    /**
     * insert reference URL to repository_parameter
     */
    private function insertCitedReferenceUrlToParameter() {
        $referenceURL_param_name  = "referenceURL";
        $referenceURL_param_value = "";
        $referenceURL_explanation = "引用情報のベースURL";
        $user_id = $this->Session->getParameter("_user_id");
        $query = "INSERT INTO " . DATABASE_PREFIX . "repository_parameter " .
                 " (param_name, param_value, explanation, ins_user_id, mod_user_id, " .
                 "del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $params   = array();
        $params[] = $referenceURL_param_name;   // param_name
        $params[] = $referenceURL_param_value;  // param_value
        $params[] = $referenceURL_explanation;  // explanation
        $params[] = $user_id;                   // ins_user_id
        $params[] = $user_id;                   // mod_user_id
        $params[] = 0;                          // del_user_id
        $params[] = $this->TransStartDate;      // ins_date
        $params[] = $this->TransStartDate;      // mod_date
        $params[] = "";                         // del_date
        $params[] = 0;                          // is_delete

        $retRef = $this->Db->execute($query, $params);
        if($retRef === false || count($retRef)!=1){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );
            // Rollback
            $this->failTrans();
            throw $exception;
        }
    }
    // add set reference URL to repository_parameter 2013/10/10 S.Suzuki --end--

    // Add e-person 2013/10/21 R.Matsuura --start--
    /**
     * update WEKO version 208 to version 209
     *
     */
    private function updateWekoVersion208To209()
    {
        // insert record 'e_mail_address' into external_author_id_prefix table
        $query = "INSERT INTO ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_EXTERNAL_AUTHOR_ID_PREFIX.
                             " (".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_PREFIX_ID.", ".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_PREFIX_NAME.", ".
                             RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_BLOCK_ID.", ".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_ROOM_ID.", ".
                             RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_INS_USER_ID.", ".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_MOD_USER_ID.", ".
                             RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_DEL_USER_ID.", ".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_INS_DATE.", ".
                             RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_MOD_DATE.", ".RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_DELDATE.", ".
                             RepositoryConst::DBCOL_EXTERNAL_AUTHOR_ID_PREFIX_IS_DELETE.") ".
                             " VALUES (0, 'e_mail_address', 0, 0, '1', '1', '0', '2008-03-12 00:00:00.000', '2008-03-12 00:00:00.000', '', 0);";
        $result = $this->Db->execute($query);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }

        // get E-Mail Address from name_authority table and insert into external_author_id_suffix table
        $query = "INSERT INTO ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_EXTERNAL_AUTHOR_ID_SUFFIX.
                 " ( author_id, prefix_id, suffix, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "SELECT author_id, 0, e_mail_address, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete ".
                 "FROM ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_NAME_AUTHORITY." ".
                 "WHERE e_mail_address != ? AND e_mail_address IS NOT NULL GROUP BY author_id ;";
        $params = array();
        $params[] = "";
        $result = $this->dbAccess->executeQuery($query, $params);

        // delete 'e_mail_address' column from name_authority table
        $query = "ALTER TABLE ". DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_NAME_AUTHORITY." DROP e_mail_address;";
        $result = $this->Db->execute($query);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }

        // create 'repository_send_feedbackmail_author_id' table
        $query = "CREATE TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_SEND_FEEDBACKMAIL_AUTHOR_ID." ( ".
                " `item_id` int(11) NOT NULL default 0, ".
                " `item_no` int(11) NOT NULL default 0, ".
                " `author_id_no` int(11) NOT NULL default 0, ".
                " `author_id` int(11) NOT NULL default 0, ".
                " PRIMARY KEY  (`item_id`,`item_no`,`author_id_no`) ".
                " ) ENGINE=MyISAM; ";
        $params = array();
        $retRef = $this->Db->execute($query, $params);
        if($retRef === false || count($retRef)!=1){
            $errMsg = $this->Db->ErrorMsg();
            // Rollback
            $this->failTrans();
            throw $exception;
        }

        // version up to 2.0.9
        $this->versionUp('2.0.9');
    }
    // Add e-person 2013/10/21 R.Matsuura --end--

    // Add advanced search 2013/11/20 R.Matsuura --start--
    /**
     * create search tables
     *
     */
    private function createSearchTables()
    {
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
        // create table
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_search_update_item ( ".
                " `item_id` int(11) NOT NULL default 0, ".
                " `item_no` int(11) NOT NULL default 0, ".
                " PRIMARY KEY  (`item_id`,`item_no`) ".
                " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);

        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_lock ( ".
                " `process_name` varchar(60) NOT NULL default '', ".
                " `status` int NOT NULL default 0, ".
                " `comment` text NOT NULL, ".
                " PRIMARY KEY  (`process_name`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_search_item_setup ( ".
                " `type_id` int NOT NULL default 0, ".
                " `search_type` text NOT NULL, ".
                " `use_search` int(1) NOT NULL default 0, ".
                " `default_show` int(1) NOT NULL default 0, ".
                " `junii2_mapping` text NOT NULL, ".
                " PRIMARY KEY  (`type_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_search_sort ( ".
                " `item_id` int NOT NULL default 0, ".
                " `item_no` int NOT NULL default 0, ".
                " `item_type_id` int NOT NULL default 0, ".
                " `weko_id` varchar(9) NOT NULL default '0', ".
                " `title` text NOT NULL, ".
                " `title_en` text NOT NULL, ".
                " `uri` text NOT NULL, ".
                " `review_date` varchar(23) NOT NULL default '', ".
                " `ins_user_id` varchar(40) NOT NULL default '', ".
                " `mod_date` varchar(23) NOT NULL default '', ".
                " `ins_date` varchar(23) NOT NULL default '', ".
                " `biblio_date` varchar(23) NOT NULL default '', ".
                " PRIMARY KEY  (`item_id`,`item_no`), ".
                " INDEX `title` (`title`(255)), ".
                " INDEX `title_en` (`title_en`(255)), ".
                " INDEX `user_itemid` (`ins_user_id`, `item_id`), ".
                " INDEX `type_itemid` (`item_type_id`, `item_id`), ".
                " INDEX `weko_uri` (`weko_id`, `uri`(255)), ".
                " INDEX `moddate_itemid` (`mod_date`, `item_id`), ".
                " INDEX `insdate_itemid` (`ins_date`, `item_id`), ".
                " INDEX `revdate_itemid` (`review_date`, `item_id`), ".
                " INDEX `bibdate_itemid` (`biblio_date`, `item_id`) ".
                " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);

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
                                "repository_search_grantor");

        $dateTableNameArray = array("repository_search_date",
                                    "repository_search_dateofissued",
                                    "repository_search_dateofgranted");

        for($tableCnt = 0; $tableCnt < count($tableNameArray); $tableCnt++)
        {
            $this->createFullTextSearchTable($tableNameArray[$tableCnt], $isMroongaExist);
        }

        for($tableCnt = 0; $tableCnt < count($dateTableNameArray); $tableCnt++)
        {
            $this->createDateSearchTable($dateTableNameArray[$tableCnt]);
        }

        return true;
    }

    /**
     * create fulltext search table
     *
     * @param string $tableName
     * @param bool $isMroongaExist
     */
    private function createFullTextSearchTable($tableName, $isMroongaExist)
    {
        $query = "CREATE TABLE ".DATABASE_PREFIX. $tableName. " ( ".
                " `item_id` int NOT NULL default 0, ".
                " `item_no` int NOT NULL default 0, ".
                " `metadata` longtext NOT NULL, ".
                " PRIMARY KEY  (`item_id`,`item_no`), ".
                " FULLTEXT KEY `metadata` (`metadata`) ";
        if($isMroongaExist)
        {
            $query .= " ) ENGINE=mroonga COMMENT='engine \"MyISAM\"'; ";
        }
        else
        {
            $query .= " ) ENGINE=MyISAM; ";
        }
        $this->dbAccess->executeQuery($query);
    }

    /**
     * create date search table
     *
     * @param string $tableName
     */
    private function createDateSearchTable($tableName)
    {
        $query = "CREATE TABLE ".DATABASE_PREFIX. $tableName. " ( ".
                " `item_id` int NOT NULL default 0, ".
                " `item_no` int NOT NULL default 0, ".
                " `data_no` int NOT NULL default 0, ".
                " `metadata` varchar(23) NOT NULL default '', ".
                " PRIMARY KEY  (`item_id`,`item_no`,`data_no`), ".
                " INDEX `metadata` (`metadata`) ".
                " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);
    }

    /**
     * update WEKO version 209 to version 2010
     *
     */
    private function updateWekoVersion209To2010()
    {
        $this->createSearchTables();
        $this->insertSearchItemSetup();
        $this->insertLockTable('Repository_Action_Common_Search_Update', 0);

        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_SEARCH_TABLE_PROCESSING] = true;

        // delete fulltext_data table
        $query = "DROP TABLE IF EXISTS ". DATABASE_PREFIX. "repository_fulltext_data ;";
        $this->dbAccess->executeQuery($query);

        // version up to 2.0.10
        $this->versionUp('2.0.10');
    }

    /**
     * insert record into repository_search_item_setup
     *
     */
    private function insertSearchItemSetup()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_search_item_setup ".
                 "(`type_id`, `search_type`, `use_search`, `default_show`, `junii2_mapping` ) ".
                 "VALUES (1, 'repository_detail_search_show_name_title', 1, 1, 'title,alternative'), ".
                 "(2, 'repository_detail_search_show_name_creator', 1, 1, 'creator'), ".
                 "(3, 'repository_detail_search_show_name_subject', 0, 0, 'subject'), ".
                 "(4, 'repository_detail_search_show_name_niisubject', 0, 0, 'NIIsubject,NDC,NDLC,BSH,NDLSH,MeSH,DDC,LCC,UDC,LCSH'), ".
                 "(5, 'repository_detail_search_show_name_description', 0, 0, 'description'), ".
                 "(6, 'repository_detail_search_show_name_publisher', 0, 0, 'publisher'), ".
                 "(7, 'repository_detail_search_show_name_contributor', 0, 0, 'contributor'), ".
                 "(8, 'repository_detail_search_show_name_date', 0, 0, 'date'), ".
                 "(9, 'repository_detail_search_show_name_item_type', 0, 0, ''), ".
                 "(10, 'repository_detail_search_show_name_type', 1, 1, 'type'), ".
                 "(11, 'repository_detail_search_show_name_format', 0, 0, 'format'), ".
                 "(12, 'repository_detail_search_show_name_id', 0, 0, 'identifier,URI,fullTextURL,selfDOI,ISBN,ISSN,NCID,pmid,doi,NAID,ichushi'), ".
                 "(13, 'repository_detail_search_show_name_jtitle', 0, 0, 'jtitle'), ".
                 "(14, 'repository_detail_search_show_name_dateofissued', 1, 1, 'dateofissued'), ".
                 "(15, 'repository_detail_search_show_name_language', 0, 0, 'language'), ".
                 "(16, 'repository_detail_search_show_name_spatial', 0, 0, 'spatial,NIIspatial'), ".
                 "(17, 'repository_detail_search_show_name_temporal', 0, 0, 'temporal,NIItemporal'), ".
                 "(18, 'repository_detail_search_show_name_rights', 0, 0, 'rights'), ".
                 "(19, 'repository_detail_search_show_name_textversion', 0, 0, 'textversion'), ".
                 "(20, 'repository_detail_search_show_name_grantid', 0, 0, 'grantid'), ".
                 "(21, 'repository_detail_search_show_name_dateofgranted', 0, 0, 'dateofgranted'), ".
                 "(22, 'repository_detail_search_show_name_degreename', 0, 0, 'degreename'), ".
                 "(23, 'repository_detail_search_show_name_grantor', 0, 0, 'grantor'), ".
                 "(24, 'repository_detail_search_show_name_index', 1, 1, ''); ";
        $this->dbAccess->executeQuery($query);
        return true;
    }

    /**
     * update Weko Version 2.0.10 To 2.0.11
     *
     */
    private function updateWekoVersion2010To2011()
    {
        // Add index
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. " ".
                 "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_INDEX_OWNER_USER_ID. " (".
                 RepositoryConst::DBCOL_REPOSITORY_INDEX_OWNER_USER_ID. "); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. " ".
                 "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_INDEX_PUB_DATE. " (".
                 RepositoryConst::DBCOL_REPOSITORY_INDEX_PUB_DATE. "); ";
        $this->dbAccess->executeQuery($query);

        // create new table
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_index_browsing_authority ( ".
                 "`index_id` int NOT NULL default 0, ".
                 "`exclusive_acl_role_id` int NOT NULL default 0, ".
                 "`exclusive_acl_room_auth` int NOT NULL default 0, ".
                 "`public_state` INT(1) NOT NULL default 0, ".
                 "`pub_date` VARCHAR(23), ".
                 "`harvest_public_state` INT(1) NOT NULL default 1, ".
                 "`ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`del_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`ins_date` VARCHAR(23), ".
                 "`mod_date` VARCHAR(23), ".
                 "`del_date` VARCHAR(23), ".
                 "`is_delete` INT(1), ".
                 "PRIMARY KEY  (`index_id`), ".
                 "INDEX `index_browsing_authority` (`index_id`, `exclusive_acl_role_id`, `exclusive_acl_room_auth`) ".
                 " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_index_browsing_groups ( ".
                 "`index_id` int NOT NULL default 0, ".
                 "`exclusive_acl_group_id` int NOT NULL default 0, ".
                 "`ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`del_user_id` VARCHAR(40) NOT NULL default 0, ".
                 "`ins_date` VARCHAR(23), ".
                 "`mod_date` VARCHAR(23), ".
                 "`del_date` VARCHAR(23), ".
                 "`is_delete` INT(1), ".
                 "PRIMARY KEY  (`index_id`, `exclusive_acl_group_id`) ".
                 " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);

        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_INDEX_MANAGER] = true;

        // version up to 2.0.11
        $this->versionUp('2.0.11');
    }

    /**
     * update Weko Version 2.0.11 To 2.0.12
     *
     */
    private function updateWekoVersion2011To2012()
    {
        // create new table
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_prefix ( ".
                 "`id` int NOT NULL default 0, ".
                 "`prefix_name` text NOT NULL, ".
                 "`prefix_id` text NOT NULL, ".
                 "`ins_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`mod_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`del_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`ins_date` VARCHAR(23), ".
                 "`mod_date` VARCHAR(23), ".
                 "`del_date` VARCHAR(23), ".
                 "`is_delete` INT(1), ".
                 "PRIMARY KEY  (`id`) ".
                 " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_suffix ( ".
                 "`item_id` int NOT NULL default 0, ".
                 "`item_no` int NOT NULL default 0, ".
                 "`id` int NOT NULL default 0, ".
                 "`suffix` text NOT NULL, ".
                 "`ins_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`mod_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`del_user_id` VARCHAR(40) NOT NULL default '', ".
                 "`ins_date` VARCHAR(23), ".
                 "`mod_date` VARCHAR(23), ".
                 "`del_date` VARCHAR(23), ".
                 "`is_delete` INT(1), ".
                 "PRIMARY KEY  (`item_id`, `item_no`, `id`) ".
                 " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        $user_id = $this->Session->getParameter("_user_id");
        $date = $this->TransStartDate;

        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_prefix".
                 " (id, prefix_name, prefix_id, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete)".
                 " VALUES".
                 " (10, 'Yハンドル', '', ?, ?, '', ?, ?, '', 0),".
                 " (20, 'CNRIハンドル', '', ?, ?, '', ?, ?, '', 0),".
                 " (30, 'CrossRef DOI', '', ?, ?, '', ?, ?, '', 0),".
                 " (40, 'JaLC DOI', '', ?, ?, '', ?, ?, '', 0) ;";
        $params = array();
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $date;
        $params[] = $date;
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $date;
        $params[] = $date;
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $date;
        $params[] = $date;
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $date;
        $params[] = $date;
        $this->dbAccess->executeQuery($query, $params);

        $query = "SELECT param_value".
                 " FROM ". DATABASE_PREFIX. "repository_parameter".
                 " WHERE param_name = 'prefixID'".
                 " AND is_delete = 0 ;";
        $YHandlePrefixParam = $this->dbAccess->executeQuery($query);
        $prefix = $YHandlePrefixParam[0]["param_value"];

        $query = "UPDATE ". DATABASE_PREFIX. "repository_prefix".
                 " SET prefix_id = '". $YHandlePrefixParam[0]["param_value"]."'".
                 " WHERE id = 10 ;";
        $this->dbAccess->executeQuery($query);

        //JaLC DOI設定値をインデックスに追加する処理
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                 "ADD `jalc_doi` INT NOT NULL default 0 AFTER `harvest_public_state`;";
        $this->dbAccess->executeQuery($query);

        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_suffix".
                 " (item_id, item_no, id, suffix, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "  SELECT ITEM.item_id, ITEM.item_no, PREFIX.id, REPLACE(".
                 "   REPLACE(ITEM.uri, 'http://id.nii.ac.jp/". $prefix. "/', ''), ".
                 "   '/', ''".
                 "   ), ?, ?, '', ?, ?, '', 0".
                 "  FROM ". DATABASE_PREFIX. "repository_item ITEM, ".DATABASE_PREFIX. "repository_prefix PREFIX".
                 "  WHERE PREFIX.id=10 AND ITEM.uri LIKE 'http://id.nii.ac.jp/%';";
        $params = array();
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $date;
        $params[] = $date;
        $this->dbAccess->executeQuery($query, $params);

        $query = "UPDATE ". DATABASE_PREFIX. "repository_item".
                 " SET uri=concat('". BASE_URL. "/?action=repository_uri&item_id=', item_id)".
                 "  WHERE uri LIKE 'http://id.nii.ac.jp/". $prefix. "/%';";
        $this->dbAccess->executeQuery($query);

        $query = "DELETE FROM ". DATABASE_PREFIX. "repository_parameter".
                 " WHERE param_name = 'prefixID' AND explanation = 'prefixID' ;";
        $this->dbAccess->executeQuery($query);

        // version up to 2.0.12
        $this->versionUp('2.0.12');
    }

    /**
     * execute recursive proccessing of RepositorySearchTableProccessing
     *
     */
    private function updateSearchTableForAllItem()
    {
        require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';

        if (!isset($_SERVER["SERVER_PORT"])) // NULLの時
        {
            //BASE_URLの先頭をチェックしてhttps://なら443を$_SERVER["SERVER_PORT"]に入力
            if ( preg_match("/^https\:\/\//", BASE_URL) ) {
                $_SERVER["SERVER_PORT"] = 443;
            }
            //BASE_URLの先頭をチェックしてhttp://なら80を$_SERVER["SERVER_PORT"]に入力
            else if ( preg_match("/^http\:\/\//", BASE_URL) ){
                $_SERVER["SERVER_PORT"] = 80;
            }
        }

        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->updateSearchTableForAllItem();
    }

    /**
     * update recursive proccessing of RepositoryIndexManager
     *
     */
    private function updateDependentIndex()
    {
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
        $indexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $indexManager->createIndexBrowsingAuthority();
    }

    /**
     * for recursive processing
     * after update, when execute recursive processing,
     * add key and boolean(true) to $this->recursiveProccesingFlgList,
     * write recursive processing to this metho
     */
    private function executeRecursiveProcessing()
    {
        // execute recursive proccessing
        foreach($this->recursiveProcessingFlgList as $key => $value)
        {
            switch($key)
            {
                case self::KEY_REPOSITORY_SEARCH_TABLE_PROCESSING:
                    if($value === true)
                    {
                        $this->updateSearchTableForAllItem();
                    }
                    break;
                case self::KEY_REPOSITORY_INDEX_MANAGER:
                    if($value === true)
                    {
                        $this->updateDependentIndex();
                    }
                    break;
                case self::KEY_REPOSITORY_CLEANUP_DELETED_FILE:
                    if($value === true)
                    {
                        $this->cleanupDeletedFiles();
                    }
                    break;
                 // Improve Search Log 2015/03/19 K.Sugimoto --start--
                case self::KEY_REPOSITORY_SEARCH_LOG:
                    if($value === true)
                    {
                        $this->addDetailSearchItemForExistSearchLog();
                    }
                    break;
                 // Improve Search Log 2015/03/19 K.Sugimoto --end--
                case self::KEY_REPOSITORY_ELAPSEDTIME_LOG:
                    if($value === true)
                    {
                        $this->insertElapsedLogs();
                    }
                    break;
                 // Improve Log 2015/06/17 K.Sugimoto --start--
                case self::KEY_REPOSITORY_EXCLUDE_LOG:
                    if($value === true)
                    {
                        $this->excludeLogOfRobotList();
                    }
                    break;
                 // Improve Log 2015/06/17 K.Sugimoto --end--
                default:
                    break;
            }
        }
    }

    /**
     * execute recursive proccessing of RepositorySearchTableProccessing
     *
     */
    private function cleanupDeletedFiles()
    {
        $query = "CREATE TABLE IF NOT EXISTS ".DATABASE_PREFIX. "repository_filecleanup_deleted_file ( ".
                " `item_id` int(11) NOT NULL default 0, ".
                " `attribute_id` int(11) NOT NULL default 0, ".
                " `file_no` int(11) NOT NULL default 0, ".
                " `extension` TEXT NOT NULL, ".
                " PRIMARY KEY  (`item_id`,`attribute_id`,`file_no`) ".
                " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);

        if (!isset($_SERVER["SERVER_PORT"])) // NULLの時
        {
            //BASE_URLの先頭をチェックしてhttps://なら443を$_SERVER["SERVER_PORT"]に入力
            if ( preg_match("/^https\:\/\//", BASE_URL) ) {
                $_SERVER["SERVER_PORT"] = 443;
            }
            //BASE_URLの先頭をチェックしてhttp://なら80を$_SERVER["SERVER_PORT"]に入力
            else if ( preg_match("/^http\:\/\//", BASE_URL) ){
                $_SERVER["SERVER_PORT"] = 80;
            }
        }

        $query = "INSERT IGNORE INTO ".DATABASE_PREFIX. "repository_filecleanup_deleted_file ".
                 "(`item_id`, `attribute_id`, `file_no`, `extension`) ".
                 "SELECT item_id, attribute_id, file_no, extension ".
                 "FROM ".DATABASE_PREFIX. "repository_file ".
                 "WHERE is_delete = ? ;";
        $params = array();
        $params[] = 1;
        $this->dbAccess->executeQuery($query, $params);

        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_filecleanup_update";
        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }

    /**
     * update Weko Version 2.0.12 To 2.1.0
     *
     */
    private function updateWekoVersion2012To210()
    {
        $this->insertLockTable('Repository_Action_Common_Filecleanup_Update', 0);
        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_CLEANUP_DELETED_FILE] = true;
    }

    /**
     * update Weko Version 2.1.0 To 2.1.1
     *
     */
    private function updateWekoVersion210To211()
    {
        $this->createDateSearchTable("repository_search_dateofissued_ymd");
        $this->remakeIndexForAcceleration();
        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_SEARCH_TABLE_PROCESSING] = true;
    }

    /**
     * update Weko Version 2.1.1 To 2.1.2
     *
     */
    private function updateWekoVersion211To212()
    {
        $this->addBiblioFlgToIndex();
        $this->addIpAddressToLog();
        $this->addParamSendSitelicenseMail();
        $this->updateSiteLicenseForAddMail();
        $this->createSiteLicenseMailTable();
        $this->createIssnTable();
        $this->insertLockTable('Repository_Action_Common_Background_Sitelicensemail', 0);
        $this->insertLockTable('Repository_Action_Common_Background_Log_Update', 0);
    }

    /**
     * insert record into repository_lock
     *
     */
    private function insertLockTable($process_name, $status)
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_lock ".
                "(`process_name`, `status`, `comment`) ".
                "VALUES (?, ?, ?); ";
        $params = array();
        $params[] = $process_name;
        $params[] = $status;
        $params[] = "";
        $this->dbAccess->executeQuery($query, $params);
        return true;
    }

    // fix No.63 2014/04/04 R.Matsuura --start--
    /**
     * update mapping 'NAID' and 'ISBN'
     *
     */
    private function updateNAIDandISBNJunii2Mapping()
    {
        $query = "UPDATE ". DATABASE_PREFIX. "repository_item_attr_type ".
                 "SET `junii2_mapping` = ? ".
                 "WHERE item_type_id BETWEEN ? AND ? ".
                 "AND `attribute_name` LIKE ? ".
                 "AND ins_date = mod_date ; ";
        // update naid junii2 mapping
        $params = array();
        $params[] = 'NAID';
        $params[] = 10001;
        $params[] = 10010;
        $params[] = '%NAID%';
        $this->dbAccess->executeQuery($query, $params);

        // update sibn junii2 mapping
        $params = array();
        $params[] = 'isbn';
        $params[] = 10001;
        $params[] = 10010;
        $params[] = 'ISBN';
        $this->dbAccess->executeQuery($query, $params);
        return true;
    }
    // fix No.63 2014/04/04 R.Matsuura --end--

    // add biblio flag to index 2014/04/15 T.Ichikawa --start--
    /**
     * Add new column "biblio_flag" and "online_issn" to index table
     *
     */
    private function addBiblioFlgToIndex()
    {
        $query = "ALTER TABLE ". DATABASE_PREFIX. "repository_index ".
                 "ADD biblio_flag INT(1) NOT NULL DEFAULT 0 ".
                 "AFTER owner_user_id; ";
        $this->dbAccess->executeQuery($query);

        $query = "ALTER TABLE ". DATABASE_PREFIX. "repository_index ".
                 "ADD online_issn VARCHAR(9) DEFAULT '' ".
                 "AFTER biblio_flag; ";
        $this->dbAccess->executeQuery($query);
        return true;
    }

    /**
     * create site license mail address table
     *
     */
     private function createSiteLicenseMailTable() {
         $query = "CREATE TABLE ". DATABASE_PREFIX ."repository_send_mail_sitelicense (".
                  " `no` INT NOT NULL default 0, ".
                  " `organization_name` TEXT NOT NULL, ".
                  " `start_ip_address` BIGINT default 0, ".
                  " `finish_ip_address` BIGINT default 0, ".
                  " `mail_address` TEXT NOT NULL, ".
                  " PRIMARY KEY(`no`) ".
                  ") ENGINE=innodb;";
         $result = $this->dbAccess->executeQuery($query);
     }

    /**
     * Create JTitle table
     *
     */
     private function createIssnTable() {
         $query = "CREATE TABLE ". DATABASE_PREFIX ."repository_issn (".
                  " `issn` VARCHAR(9) NOT NULL default '', ".
                  " `jtitle` TEXT NOT NULL, ".
                  " `jtitle_en` TEXT NOT NULL, ".
                  " `set_spec` TEXT NOT NULL, ".
                  " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                  " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                  " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                  " `ins_date` VARCHAR(23), ".
                  " `mod_date` VARCHAR(23), ".
                  " `del_date` VARCHAR(23), ".
                  " `is_delete` INT(1), ".
                  " PRIMARY KEY(`issn`) ".
                  ") ENGINE=innodb;";
         $result = $this->dbAccess->executeQuery($query);
     }

    private function remakeIndexForAcceleration()
    {
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. " ".
                "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_INDEX_SELECT_INDEX_LIST_DISPLAY. " (".
                RepositoryConst::DBCOL_REPOSITORY_INDEX_SELECT_INDEX_LIST_DISPLAY. "); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. " ".
                "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_INDEX_PARENT_INDEX_ID. " (".
                RepositoryConst::DBCOL_REPOSITORY_INDEX_PARENT_INDEX_ID. "); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX. " ".
                "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_INDEX_CREATE_COVER_FLAG. " (".
                RepositoryConst::DBCOL_REPOSITORY_INDEX_CREATE_COVER_FLAG. "); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_ITEM. " ".
                "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE. " (".
                RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE. "(10), ".RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH. "(10)); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_ITEM. " ".
                "ADD INDEX ". RepositoryConst::DBCOL_COMMON_IS_DELETE. " (".
                RepositoryConst::DBCOL_COMMON_IS_DELETE. "); ";
        $this->dbAccess->executeQuery($query);

        $query = "ALTER TABLE ".DATABASE_PREFIX. "repository_index_browsing_authority ".
                "DROP INDEX `index_browsing_authority` ; ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. "repository_index_browsing_authority ".
                "ADD INDEX `index_browsing_authority` (".
                "`exclusive_acl_role_id`, `exclusive_acl_room_auth`, `public_state`, `pub_date`, `is_delete`); ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ".DATABASE_PREFIX. "repository_index_browsing_authority ".
                "ADD INDEX `index_public_state` (".
                "`public_state`, `pub_date`, `is_delete`); ";
        $this->dbAccess->executeQuery($query);
    }
    // Add OpenDepo 2014/01/31 S.Arata --end--

    /**
     * update parameter sitelicense
     *
     */
     private function updateSiteLicenseForAddMail()
     {
         // get site license value
         $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                  "WHERE param_name = ? ;";
         $params = array();
         $params[] = "site_license";
         $retRef = $this->dbAccess->executeQuery($query, $params);

         // fix site license value
         $site_license = "";
         if(strlen($retRef[0]["param_value"]) != 0) {
             $site_license_elements = explode("|", $retRef[0]["param_value"]);
             for($ii=0; $ii<count($site_license_elements); $ii++) {
                 if(strlen($site_license) != 0) {
                     $site_license .= "|";
                 }
                 $site_license .= $site_license_elements[$ii];
                 if(strlen($site_license_elements[$ii]) != 0) {
                     $site_license .= ",";
                 }
             }
             $query = "UPDATE ". DATABASE_PREFIX. "repository_parameter ".
                      "SET param_value = ? ".
                      "WHERE param_name = ? ;";
             $params = array();
             $params[] = $site_license;
             $params[] = "site_license";
             $this->dbAccess->executeQuery($query, $params);
         }
     }

    /**
     * Add new column "numeric_ip_address" and "referer" to log table
     *
     */
     private function addIpAddressToLog() {
        $query = "ALTER TABLE ". DATABASE_PREFIX. "repository_log ".
                 "ADD numeric_ip_address BIGINT NOT NULL DEFAULT -1 ".
                 "AFTER ip_address; ";
        $this->dbAccess->executeQuery($query);
        // Add referer
        $query = "ALTER TABLE ". DATABASE_PREFIX. "repository_log ".
                 "ADD referer TEXT NOT NULL ".
                 "AFTER user_agent; ";
        $this->dbAccess->executeQuery($query);
     }
    // add biblio flag to index 2014/04/15 T.Ichikawa --end--

    /**
     * Add parameter send sitelicense feedback mail flag
     *
     */
     private function addParamSendSitelicenseMail() {
         $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                  "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                  " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                  "VALUES('send_sitelicense_mail_start_date', '', 'Start date of sitelicense mail sending', ?, ?, '0', ?, ?, '', 0), ".
                  "('send_sitelicense_mail_end_date', '', 'End date of sitelicense mail sending', ?, ?, '0', ?, ?, '', 0), ".
                  "('send_sitelicense_mail_activate_flg', '0', 'サイトライセンスメール送信機能利用設定(1:利用する,0:利用しない)', ?, ?, '0', ?, ?, '', 0);";

         $params = array();
         $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
         $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
         $params[] = $this->TransStartDate;  // ins_date
         $params[] = $this->TransStartDate;  // mod_date
         $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
         $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
         $params[] = $this->TransStartDate;  // ins_date
         $params[] = $this->TransStartDate;  // mod_date
         $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
         $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
         $params[] = $this->TransStartDate;  // ins_date
         $params[] = $this->TransStartDate;  // mod_date
         $this->dbAccess->executeQuery($query, $params);
     }

    /**
     * update Weko Version 2.1.2 To 2.1.3
     *
     */
    private function updateWekoVersion212To213()
    {
        // add LIDO mapping
        $this->addLidoMapping();
        // create item type LIDO
        $this->createItemTypeLido();
        // add parameter for referer word
        $this->addExternalSearchWordParameter();
        // create table for referer word
        $this->createExternalSearchWordTable();
        // add process for referer word
        $this->insertLockTable('Repository_Action_Common_Updateexternalsearchword', 0);

        // version up to 2.1.3
        $this->versionUp('2.1.3');
    }

    private function updateWekoVersionSpase()
    {
        // add SPASE mapping
        $this->addSpaseMapping();
        // create item type SPASE
        $this->createItemTypeSpase();
        // add parameter for referer word
        $this->addExternalSearchWordParameter();
        // create table for referer word
        $this->createExternalSearchWordTable();
        // add process for referer word
        $this->insertLockTable('Repository_Action_Common_Updateexternalsearchword', 0);

        // version up to 2.1.8
        $this->versionUp('2.2.4');
    }

    /**
     * add lido_mapping column to repository_item_attr_type table
     *
     */
    private function addLidoMapping()
    {
        $query = "ALTER TABLE ".DATABASE_PREFIX."repository_item_attr_type ".
                 "ADD `lido_mapping` TEXT NOT NULL ".
                 "AFTER `lom_mapping` ;";
        $this->dbAccess->executeQuery($query);
    }

    private function addSpaseMapping()
    {
        $query = "ALTER TABLE ".DATABASE_PREFIX."repository_item_attr_type ".
                "ADD `spase_mapping` TEXT NOT NULL default '' ".
                "AFTER `lido_mapping` ;";
        $this->dbAccess->executeQuery($query);
    }
    /**
     * create new item type LIDO
     *
     */
    private function createItemTypeLido()
    {
        //もし20017がなかったら、repository_item_typeとrepository_item_attr_typeに追加する
        $query = "SELECT item_type_id ".
                 "FROM ".DATABASE_PREFIX."repository_item_type".
                 " WHERE is_delete=? AND item_type_id=? ;";
        $params = array();
        $params[] = 0;  // is_delete
        $params[] = 20017;  // item_type_id
        $retRef = $this->dbAccess->executeQuery($query, $params);

        if(count($retRef) == 0)
        {
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                     "VALUE".
                    "(20017, 'LIDO', 'LIDO', 'harvesting item type', 'Others', '', '', '', '', '1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
            $retRef = $this->dbAccess->executeQuery($query);
        }

        $query = "SELECT item_type_id ".
                 "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                 "WHERE is_delete = ? AND item_type_id = ? ;";
        $params = array();
        $params[] = 0;  // is_delete
        $params[] = 20017;  // item_type_id
        $retRef = $this->dbAccess->executeQuery($query, $params);

        if(count($retRef) == 0)
        {
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                    "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                    "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                    "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, display_lang_type, ".
                    "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
                    "(20017, 1, 1, 'lidoレコードID', 'lidoレコードID', 'text', 1, 1, 0, 0, 0, 'identifier', 'identifier', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 2, 2, '作品タイプID', '作品タイプID', 'text', 1, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 3, 3, '作品タイプ（語）', '作品タイプ（語）', 'text', 1, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 4, 4, '分類ID', '分類ID', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 5, 5, '分類（語）', '分類（語）', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 6, 6, '資料/作品名（値）', '資料/作品名（値）', 'text', 0, 1, 0, 0, 0, 'title', 'title', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 7, 7, '銘/題辞写', '銘/題辞写', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 8, 8, '所蔵者名（値）', '所蔵者名（値）', 'name', 0, 1, 0, 0, 0, 'rights', 'rights', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 9, 9, '所蔵者ウェブリンク', '所蔵者ウェブリンク', 'link', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 10, 10, '所蔵者資料/作品ID', '所蔵者資料/作品ID', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 11, 11, '資料/作品状態（状態）', '資料/作品状態（状態）', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 12, 12, '記述ノート（値）', '記述ノート（値）', 'text', 0, 1, 0, 0, 0, 'description', 'description', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 13, 13, '測定（表示用）', '測定（表示用）', 'text', 0, 1, 0, 0, 0, 'description', 'description', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 14, 14, 'イベント内容（表示用）', 'イベント内容（表示用）', 'textarea', 0, 1, 0, 0, 0, 'coverage', 'coverage', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 15, 15, 'イベントタイプ（語）', 'イベントタイプ（語）', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 16, 16, 'イベント行為者／役割（表示用）', 'イベント行為者／役割（表示用）', 'text', 0, 1, 0, 0, 0, 'description', 'description', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 17, 17, 'イベント発生期間(表示用)', 'イベント発生期間(表示用)', 'text', 0, 1, 0, 0, 0, 'coverage', 'coverage', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 18, 18, 'イベント発生期間（西暦）（開始日）', 'イベント発生期間（西暦）（開始日）', 'date', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 19, 19, 'イベント発生期間（西暦）（終了日）', 'イベント発生期間（西暦）（終了日）', 'date', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 20, 20, 'イベント発生時代・年代（語）', 'イベント発生時代・年代（語）', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 21, 21, 'イベント発生地（表示用）', 'イベント発生地（表示用）', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 22, 22, 'イベント発生地緯度・経度', 'イベント発生地緯度・経度', 'text', 0, 1, 0, 0, 0, 'spatial', 'coverage', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 23, 23, 'イベント素材/技術(表示用)', 'イベント素材/技術(表示用)', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 24, 24, '主題(表示用)', '主題(表示用)', 'text', 0, 1, 0, 0, 0, 'subject', 'subject', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 25, 25, '関連作品(表示用)', '関連作品(表示用)', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 26, 26, '記録ID', '記録ID', 'text', 1, 1, 0, 0, 0, 'source', 'source', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 27, 27, '記録タイプ（語）', '記録タイプ（語）', 'text', 1, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 28, 28, '記録者名（値）', '記録者名（値）', 'name', 1, 1, 0, 0, 0, 'creator', 'creator', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 29, 29, '記録情報リンク', '記録情報リンク', 'link', 0, 1, 0, 0, 0, 'URI', 'identifier', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 30, 30, '記録情報メタデータ記録日', '記録情報メタデータ記録日', 'date', 0, 1, 0, 0, 0, 'date', 'date', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 31, 31, 'リソースURL', 'リソースURL', 'link', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 32, 32, 'リソース説明/記述', 'リソース説明/記述', 'textarea', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 33, 33, 'リソース所有者名（値）', 'リソース所有者名（値）', 'name', 0, 1, 0, 0, 0, 'contributor', 'contributor', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 34, 34, 'リソース権利クレジットライン', 'メタデータ校閲者(validator)リソース権利クレジットライン', 'text', 0, 1, 0, 0, 0, '', '', '', ?, '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 35, 35, '機関ID', '機関ID', 'text', 1, 0, 0, 0, 1, '', '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 36, 36, 'コンテンツID', 'コンテンツID', 'text', 1, 0, 0, 0, 1, '', '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0),".
                    "(20017, 37, 37, 'コンテンツ更新日時', 'コンテンツ更新日時', 'text', 1, 0, 0, 0, 1, '', '', '', '', '', 1, 1, '', 2008-03-18 00:00:00.000, 2008-03-18 00:00:00.000, '', 0) ; ";

            $params = array();
            $params[] = RepositoryConst::LIDO_TAG_LIDO_REC_ID;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE.".".RepositoryConst::LIDO_TAG_CONCEPT_ID;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE.".".RepositoryConst::LIDO_TAG_TERM;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION.".".RepositoryConst::LIDO_TAG_CONCEPT_ID;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION.".".RepositoryConst::LIDO_TAG_TERM;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_TITLE_WRAP.".".RepositoryConst::LIDO_TAG_TITLE_SET.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_INSCRIPTIONS_WRAP.".".RepositoryConst::LIDO_TAG_INSCRIPTIONS.".".RepositoryConst::LIDO_TAG_INSCRIPTION_TRANSCRIPTION;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_REPOSITORY_NAME.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_REPOSITORY_NAME.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_WEB_LINK;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_WORK_ID;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_DISPLAY_STATE_EDITION_WRAP.".".RepositoryConst::LIDO_TAG_DISPLAY_STATE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_DESCRIPTION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_DESCRIPTION_SET.".".RepositoryConst::LIDO_TAG_DESCRIPTIVE_NOTE_VALUE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_MEASUREMENTS_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_MEASUREMENTS_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_OBJECT_MEASUREMENTS;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_EVENT;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_TYPE.".".RepositoryConst::LIDO_TAG_TERM;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_ACTOR.".".RepositoryConst::LIDO_TAG_DISPLAY_ACTOR_IN_ROLE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DISPLAY_DATE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DATE.".".RepositoryConst::LIDO_TAG_EARLIEST_DATE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DATE.".".RepositoryConst::LIDO_TAG_LATEST_DATE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_PERIOD_NAME.".".RepositoryConst::LIDO_TAG_TERM;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_PLACE.".".RepositoryConst::LIDO_TAG_DISPLAY_PLACE;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_PLACE.".".RepositoryConst::LIDO_TAG_PLACE.".".RepositoryConst::LIDO_TAG_GML;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP.".".RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_MATERIALS_TECH.".".RepositoryConst::LIDO_TAG_DISPLAY_MATERIALS_TECH;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_RELATION_WRAP.".".RepositoryConst::LIDO_TAG_SUBJECT_WRAP.".".RepositoryConst::LIDO_TAG_SUBJECT_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_SUBJECT;
            $params[] = RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_RELATION_WRAP.".".RepositoryConst::LIDO_TAG_RELATED_WORKS_WRAP.".".RepositoryConst::LIDO_TAG_RELATED_WORK_SET.".".RepositoryConst::LIDO_TAG_RELATED_WORK.".".RepositoryConst::LIDO_TAG_DISPLAY_OBJECT;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP.".".RepositoryConst::LIDO_TAG_RECORD_ID;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP.".".RepositoryConst::LIDO_TAG_RECORD_TYPE.".".RepositoryConst::LIDO_TAG_TERM;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP.".".RepositoryConst::LIDO_TAG_RECORD_SOURCE.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP.".".RepositoryConst::LIDO_TAG_RECORD_INFO_SET.".".RepositoryConst::LIDO_TAG_RECORD_INFO_LINK;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP.".".RepositoryConst::LIDO_TAG_RECORD_INFO_SET.".".RepositoryConst::LIDO_TAG_RECORD_METADATA_DATE;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RESOURCE_WRAP.".".RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_REPRESENTATION.".".RepositoryConst::LIDO_TAG_LINK_RESOURCE;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RESOURCE_WRAP.".".RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_DESCRIPTION;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RESOURCE_WRAP.".".RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_SOURCE.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE;
            $params[] = RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RESOURCE_WRAP.".".RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RIGHT_RESOURCE.".".RepositoryConst::LIDO_TAG_CREDIT_LINE;

            $retRef = $this->dbAccess->executeQuery($query, $params);
        }
    }

    private function createItemTypeSpase()
    {
        $ItemNo = 0;
        for($ItemNo = 20018; $ItemNo <= 20025; $ItemNo++){
        //add 20018 item_id for SPASE
        $query = "SELECT item_type_id ".
                "FROM ".DATABASE_PREFIX."repository_item_type".
                " WHERE is_delete=? AND item_type_id=? ;";
        $params = array();
        $params[] = 0;  // is_delete
        $params[] = $ItemNo;  // item_type_id
        $retRef = $this->dbAccess->executeQuery($query, $params);

        if(count($retRef) == 0)
        {
          switch ($ItemNo) {
            case 20018:
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                      "VALUE".
                      "('{$ItemNo}', 'SPASE.Catalog', 'SPASE.Catalog', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
              $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20019:
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                      "VALUE".
                      "('{$ItemNo}', 'SPASE.NumericalData', 'SPASE.NumericalData', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
              $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20020:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.DisplayData', 'SPASE.DisplayData', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20021:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.Instrument', 'SPASE.Instrument', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20022:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.Observatory', 'SPASE.Observatory', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20023:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.Person', 'SPASE.Person', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20024:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.Repository', 'SPASE.Repository', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;

            case 20025:
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_type ".
                    "VALUE".
                    "('{$ItemNo}', 'SPASE.Granule', 'SPASE.Granule', 'harvesting item type', 'Others', '', '', '', '', '', 1', '1', '0', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', '0');";
            $retRef = $this->dbAccess->executeQuery($query);
              break;
          }
        }

        $query = "SELECT item_type_id ".
                "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                "WHERE is_delete = ? AND item_type_id = ? ;";
        $params = array();
        $params[] = 0;  // is_delete
        $params[] = $ItemNo;  // item_type_id
        $retRef = $this->dbAccess->executeQuery($query, $params);

        switch ($ItemNo) {
          case 20018:
            if(count($retRef) == 0)
            {
                $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
                "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
                "(20018, 1 , 1, 'Catalog.ResourceID', 'Catalog.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 2 , 2, 'Catalog.ResourceHeader.ResourceName', 'Catalog.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 3 , 3, 'Catalog.ResourceHeader.ReleaseDate', 'Catalog.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 4 , 4, 'Catalog.ResourceHeader.Description', 'Catalog.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 5 , 5, 'Catalog.ResourceHeader.Acknowledgement', 'Catalog.ResourceHeader.Acknowledgement','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.Acknowledgement', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 6 , 6, 'Catalog.ResourceHeader.Contact.PersonID', 'Catalog.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 7 , 7, 'Catalog.ResourceHeader.Contact.Role', 'Catalog.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 8 , 8, 'Catalog.AccessInformation.RepositoryID', 'Catalog.AccessInformation.RepositoryID','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.RepositoryID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 9 , 9, 'Catalog.AccessInformation.Availability', 'Catalog.AccessInformation.Availability','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.Availability', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 10 , 10, 'Catalog.AccessInformation.AccessRights', 'Catalog.AccessInformation.AccessRights','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.AccessRights', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 11 , 11, 'Catalog.AccessInformation.AccessURL.Name', 'Catalog.AccessInformation.AccessURL.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.AccessURL.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 12 , 12, 'Catalog.AccessInformation.AccessURL.URL', 'Catalog.AccessInformation.AccessURL.URL','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.AccessURL.URL', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 13 , 13, 'Catalog.AccessInformation.AccessURL.Description', 'Catalog.AccessInformation.AccessURL.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.AccessURL.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 14 , 14, 'Catalog.AccessInformation.Format', 'Catalog.AccessInformation.Format','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.Format', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 15 , 15, 'Catalog.AccessInformation.DataExtent.Quantity', 'Catalog.AccessInformation.DataExtent.Quantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.AccessInformation.DataExtent.Quantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 16 , 16, 'Catalog.InstrumentID', 'Catalog.InstrumentID','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.InstrumentID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 17 , 17, 'Catalog.PhenomenonType', 'Catalog.PhenomenonType','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.PhenomenonType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 18 , 18, 'Catalog.MeasurementType', 'Catalog.MeasurementType','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.MeasurementType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 19 , 19, 'Catalog.Keyword', 'Catalog.Keyword','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Keyword', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 20 , 20, 'Catalog.TemporalDescription.StartDate', 'Catalog.TemporalDescription.StartDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.TemporalDescription.StartDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 21 , 21, 'Catalog.TemporalDescription.StopDate', 'Catalog.TemporalDescription.StopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.TemporalDescription.StopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 22 , 22, 'Catalog.TemporalDescription.RelativeStopDate', 'Catalog.TemporalDescription.RelativeStopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.TemporalDescription.RelativeStopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 23 , 23, 'Catalog.ObservedRegion', 'Catalog.ObservedRegion','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.ObservedRegion', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 24 , 24, 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateSystemName', 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateSystemName','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateSystemName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 25 , 25, 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateRepresentation','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 26 , 26, 'Catalog.SpatialCoverage.NorthernmostLatitude', 'Catalog.SpatialCoverage.NorthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.NorthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 27 , 27, 'Catalog.SpatialCoverage.SouthernmostLatitude', 'Catalog.SpatialCoverage.SouthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.SouthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 28 , 28, 'Catalog.SpatialCoverage.EasternmostLongitude', 'Catalog.SpatialCoverage.EasternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.EasternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 29 , 29, 'Catalog.SpatialCoverage.esternmostLongitude', 'Catalog.SpatialCoverage.esternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.esternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 30 , 30, 'Catalog.SpatialCoverage.Unit', 'Catalog.SpatialCoverage.Unit','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.Unit', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 31 , 31, 'Catalog.SpatialCoverage.MinimumAltitude', 'Catalog.SpatialCoverage.MinimumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.MinimumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 32 , 32, 'Catalog.SpatialCoverage.MaximumAltitude', 'Catalog.SpatialCoverage.MaximumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.MaximumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 33 , 33, 'Catalog.SpatialCoverage.Reference', 'Catalog.SpatialCoverage.Reference','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.SpatialCoverage.Reference', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 34 , 34, 'Catalog.Parameter.Name', 'Catalog.Parameter.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 35 , 35, 'Catalog.Parameter.Description', 'Catalog.Parameter.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 36 , 36, 'Catalog.Parameter.Field.FieldQuantity', 'Catalog.Parameter.Field.FieldQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Field.FieldQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 37 , 37, 'Catalog.Parameter.Particle.ParticleType', 'Catalog.Parameter.Particle.ParticleType','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Particle.ParticleType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 38 , 38, 'Catalog.Parameter.Particle.ParticleQuantity', 'Catalog.Parameter.Particle.ParticleQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Particle.ParticleQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 39 , 39, 'Catalog.Parameter.Parameter.Wave.WaveType', 'Catalog.Parameter.Parameter.Wave.WaveType','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Parameter.Wave.WaveType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 40 , 40, 'Catalog.Parameter.Wave.WaveQuantity', 'Catalog.Parameter.Wave.WaveQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Wave.WaveQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 41 , 41, 'Catalog.Parameter.Mixed.MixedQuantity', 'Catalog.Parameter.Mixed.MixedQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Mixed.MixedQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20018, 42 , 42, 'Catalog.Parameter.Support.SupportQuantity', 'Catalog.Parameter.Support.SupportQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Catalog.Parameter.Support.SupportQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
                $params = array();
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEID;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATE;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_CONTACT_PERSONID;
                $params[] = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_CONTACT_ROLE;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT;
                $params[] = RepositoryConst::SPASE_CATALOG_INSTRUMENTID;
                $params[] = RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE;
                $params[] = RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE;
                $params[] = RepositoryConst::SPASE_CATALOG_KEYWORD;
                $params[] = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE;
                $params[] = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE;
                $params[] = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE;
                $params[] = RepositoryConst::SPASE_CATALOG_OBSERVEDREGION;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE;
                $params[] = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_NAME;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY;
                $params[] = RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY;
                $retRef = $this->dbAccess->executeQuery($query, $params);
              }
            break;

          case 20019:
            if(count($retRef) == 0)
            {
                $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
                "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
                "(20019, 1 , 1, 'NumericalData.ResourceID', 'NumericalData.ResourceID','text',1, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 1 , 1, 'NumericalData.ResourceID', 'NumericalData.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 2 , 2, 'NumericalData.ResourceHeader.ResourceName', 'NumericalData.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 3 , 3, 'NumericalData.ResourceHeader.ReleaseDate', 'NumericalData.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 4 , 4, 'NumericalData.ResourceHeader.Description', 'NumericalData.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 5 , 5, 'NumericalData.ResourceHeader.Acknowledgement', 'NumericalData.ResourceHeader.Acknowledgement','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Acknowledgement', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 6 , 6, 'NumericalData.ResourceHeader.Contact.PersonID', 'NumericalData.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 7 , 7, 'NumericalData.ResourceHeader.Contact.Role', 'NumericalData.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 8 , 8, 'NumericalData.AccessInformation.RepositoryID', 'NumericalData.AccessInformation.RepositoryID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.RepositoryID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 9 , 9, 'NumericalData.AccessInformation.Availability', 'NumericalData.AccessInformation.Availability','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.Availability', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 10 , 10, 'NumericalData.AccessInformation.AccessRights', 'NumericalData.AccessInformation.AccessRights','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessRights', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 11 , 11, 'NumericalData.AccessInformation.AccessURL.Name', 'NumericalData.AccessInformation.AccessURL.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 12 , 12, 'NumericalData.AccessInformation.AccessURL.URL', 'NumericalData.AccessInformation.AccessURL.URL','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.URL', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 13 , 13, 'NumericalData.AccessInformation.AccessURL.Description', 'NumericalData.AccessInformation.AccessURL.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 14 , 14, 'NumericalData.AccessInformation.Format', 'NumericalData.AccessInformation.Format','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.Format', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 15 , 15, 'NumericalData.AccessInformation.DataExtent.Quantity', 'NumericalData.AccessInformation.DataExtent.Quantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.DataExtent.Quantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 16 , 16, 'NumericalData.InstrumentID', 'NumericalData.InstrumentID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.InstrumentID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 17 , 17, 'NumericalData.PhenomenonType', 'NumericalData.PhenomenonType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.PhenomenonType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 18 , 18, 'NumericalData.MeasurementType', 'NumericalData.MeasurementType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.MeasurementType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 19 , 19, 'NumericalData.Keyword', 'NumericalData.Keyword','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Keyword', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 20 , 20, 'NumericalData.TemporalDescription.StartDate', 'NumericalData.TemporalDescription.StartDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.StartDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 21 , 21, 'NumericalData.TemporalDescription.StopDate', 'NumericalData.TemporalDescription.StopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.StopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 22 , 22, 'NumericalData.TemporalDescription.RelativeStopDate', 'NumericalData.TemporalDescription.RelativeStopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.RelativeStopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 23 , 23, 'NumericalData.ObservedRegion', 'NumericalData.ObservedRegion','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ObservedRegion', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 24 , 24, 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 25 , 25, 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 26 , 26, 'NumericalData.SpatialCoverage.NorthernmostLatitude', 'NumericalData.SpatialCoverage.NorthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.NorthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 27 , 27, 'NumericalData.SpatialCoverage.SouthernmostLatitude', 'NumericalData.SpatialCoverage.SouthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.SouthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 28 , 28, 'NumericalData.SpatialCoverage.EasternmostLongitude', 'NumericalData.SpatialCoverage.EasternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.EasternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 29 , 29, 'NumericalData.SpatialCoverage.esternmostLongitude', 'NumericalData.SpatialCoverage.esternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.esternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 30 , 30, 'NumericalData.SpatialCoverage.Unit', 'NumericalData.SpatialCoverage.Unit','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.Unit', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 31 , 31, 'NumericalData.SpatialCoverage.MinimumAltitude', 'NumericalData.SpatialCoverage.MinimumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.MinimumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 32 , 32, 'NumericalData.SpatialCoverage.MaximumAltitude', 'NumericalData.SpatialCoverage.MaximumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.MaximumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 33 , 33, 'NumericalData.SpatialCoverage.Reference', 'NumericalData.SpatialCoverage.Reference','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.Reference', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 34 , 34, 'NumericalData.Parameter.Name', 'NumericalData.Parameter.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 35 , 35, 'NumericalData.Parameter.Description', 'NumericalData.Parameter.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 36 , 36, 'NumericalData.Parameter.Field.FieldQuantity', 'NumericalData.Parameter.Field.FieldQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Field.FieldQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 37 , 37, 'NumericalData.Parameter.Particle.ParticleType', 'NumericalData.Parameter.Particle.ParticleType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Particle.ParticleType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 38 , 38, 'NumericalData.Parameter.Particle.ParticleQuantity', 'NumericalData.Parameter.Particle.ParticleQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Particle.ParticleQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 39 , 39, 'NumericalData.Parameter.Parameter.Wave.WaveType', 'NumericalData.Parameter.Parameter.Wave.WaveType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Parameter.Wave.WaveType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 40 , 40, 'NumericalData.Parameter.Wave.WaveQuantity', 'NumericalData.Parameter.Wave.WaveQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Wave.WaveQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 41 , 41, 'NumericalData.Parameter.Mixed.MixedQuantity', 'NumericalData.Parameter.Mixed.MixedQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Mixed.MixedQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 42 , 42, 'NumericalData.Parameter.Support.SupportQuantity', 'NumericalData.Parameter.Support.SupportQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Support.SupportQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
                $params = array();
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEID;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RESOURCENAME;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RELEASEDATE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_DESCRIPTION;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_ACKNOWLEDGEMENT;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_PERSONID;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_ROLE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_REPOSITORYID;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_AVAILABILITY;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSRIGHTS;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_NAME;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_URL;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_FORMAT;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_INSTRUMENTID;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PHENOMENONTYPE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_MEASUREMENTTYPE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_KEYWORD;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STARTDATE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STOPDATE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_OBSERVEDREGION;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_UNIT;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MINIMUMALTITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_REFERENCE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_NAME;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_DESCRIPTION;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_FIELD_FIELDQUANTITY;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLETYPE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVETYPE;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVEQUANTITY;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_MIXED_MIXEDQUANTITY;
            $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY;
                $retRef = $this->dbAccess->executeQuery($query, $params);
              }
            break;

          case 20020:
            if(count($retRef) == 0)
            {
                $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
                "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
                "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
                "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
                "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
                "(20019, 1 , 1, 'NumericalData.ResourceID', 'NumericalData.ResourceID','text',1, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 1 , 1, 'NumericalData.ResourceID', 'NumericalData.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 2 , 2, 'NumericalData.ResourceHeader.ResourceName', 'NumericalData.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 3 , 3, 'NumericalData.ResourceHeader.ReleaseDate', 'NumericalData.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 4 , 4, 'NumericalData.ResourceHeader.Description', 'NumericalData.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 5 , 5, 'NumericalData.ResourceHeader.Acknowledgement', 'NumericalData.ResourceHeader.Acknowledgement','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Acknowledgement', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 6 , 6, 'NumericalData.ResourceHeader.Contact.PersonID', 'NumericalData.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 7 , 7, 'NumericalData.ResourceHeader.Contact.Role', 'NumericalData.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 8 , 8, 'NumericalData.AccessInformation.RepositoryID', 'NumericalData.AccessInformation.RepositoryID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.RepositoryID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 9 , 9, 'NumericalData.AccessInformation.Availability', 'NumericalData.AccessInformation.Availability','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.Availability', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 10 , 10, 'NumericalData.AccessInformation.AccessRights', 'NumericalData.AccessInformation.AccessRights','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessRights', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 11 , 11, 'NumericalData.AccessInformation.AccessURL.Name', 'NumericalData.AccessInformation.AccessURL.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 12 , 12, 'NumericalData.AccessInformation.AccessURL.URL', 'NumericalData.AccessInformation.AccessURL.URL','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.URL', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 13 , 13, 'NumericalData.AccessInformation.AccessURL.Description', 'NumericalData.AccessInformation.AccessURL.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.AccessURL.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 14 , 14, 'NumericalData.AccessInformation.Format', 'NumericalData.AccessInformation.Format','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.Format', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 15 , 15, 'NumericalData.AccessInformation.DataExtent.Quantity', 'NumericalData.AccessInformation.DataExtent.Quantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.AccessInformation.DataExtent.Quantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 16 , 16, 'NumericalData.InstrumentID', 'NumericalData.InstrumentID','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.InstrumentID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 17 , 17, 'NumericalData.PhenomenonType', 'NumericalData.PhenomenonType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.PhenomenonType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 18 , 18, 'NumericalData.MeasurementType', 'NumericalData.MeasurementType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.MeasurementType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 19 , 19, 'NumericalData.Keyword', 'NumericalData.Keyword','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Keyword', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 20 , 20, 'NumericalData.TemporalDescription.StartDate', 'NumericalData.TemporalDescription.StartDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.StartDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 21 , 21, 'NumericalData.TemporalDescription.StopDate', 'NumericalData.TemporalDescription.StopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.StopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 22 , 22, 'NumericalData.TemporalDescription.RelativeStopDate', 'NumericalData.TemporalDescription.RelativeStopDate','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.TemporalDescription.RelativeStopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 23 , 23, 'NumericalData.ObservedRegion', 'NumericalData.ObservedRegion','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.ObservedRegion', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 24 , 24, 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateSystemName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 25 , 25, 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 26 , 26, 'NumericalData.SpatialCoverage.NorthernmostLatitude', 'NumericalData.SpatialCoverage.NorthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.NorthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 27 , 27, 'NumericalData.SpatialCoverage.SouthernmostLatitude', 'NumericalData.SpatialCoverage.SouthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.SouthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 28 , 28, 'NumericalData.SpatialCoverage.EasternmostLongitude', 'NumericalData.SpatialCoverage.EasternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.EasternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 29 , 29, 'NumericalData.SpatialCoverage.esternmostLongitude', 'NumericalData.SpatialCoverage.esternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.esternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 30 , 30, 'NumericalData.SpatialCoverage.Unit', 'NumericalData.SpatialCoverage.Unit','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.Unit', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 31 , 31, 'NumericalData.SpatialCoverage.MinimumAltitude', 'NumericalData.SpatialCoverage.MinimumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.MinimumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 32 , 32, 'NumericalData.SpatialCoverage.MaximumAltitude', 'NumericalData.SpatialCoverage.MaximumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.MaximumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 33 , 33, 'NumericalData.SpatialCoverage.Reference', 'NumericalData.SpatialCoverage.Reference','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.SpatialCoverage.Reference', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 34 , 34, 'NumericalData.Parameter.Name', 'NumericalData.Parameter.Name','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Name', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 35 , 35, 'NumericalData.Parameter.Description', 'NumericalData.Parameter.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 36 , 36, 'NumericalData.Parameter.Field.FieldQuantity', 'NumericalData.Parameter.Field.FieldQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Field.FieldQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 37 , 37, 'NumericalData.Parameter.Particle.ParticleType', 'NumericalData.Parameter.Particle.ParticleType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Particle.ParticleType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 38 , 38, 'NumericalData.Parameter.Particle.ParticleQuantity', 'NumericalData.Parameter.Particle.ParticleQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Particle.ParticleQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 39 , 39, 'NumericalData.Parameter.Parameter.Wave.WaveType', 'NumericalData.Parameter.Parameter.Wave.WaveType','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Parameter.Wave.WaveType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 40 , 40, 'NumericalData.Parameter.Wave.WaveQuantity', 'NumericalData.Parameter.Wave.WaveQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Wave.WaveQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 41 , 41, 'NumericalData.Parameter.Mixed.MixedQuantity', 'NumericalData.Parameter.Mixed.MixedQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Mixed.MixedQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
                "(20019, 42 , 42, 'NumericalData.Parameter.Support.SupportQuantity', 'NumericalData.Parameter.Support.SupportQuantity','text',0, 0, 0, 0, 0, '', '', '', '', 'NumericalData.Parameter.Support.SupportQuantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
                $params = array();
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEID;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RESOURCENAME;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RELEASEDATE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_ACKNOWLEDGEMENT;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_PERSONID;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_ROLE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_REPOSITORYID;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_AVAILABILITY;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSRIGHTS;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_NAME;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_URL;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_FORMAT;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_INSTRUMENTID;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PHENOMENONTYPE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_MEASUREMENTTYPE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_KEYWORD;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STARTDATE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STOPDATE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_OBSERVEDREGION;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_UNIT;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MINIMUMALTITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_REFERENCE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_NAME;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_DESCRIPTION;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_FIELD_FIELDQUANTITY;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLETYPE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVETYPE;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVEQUANTITY;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_MIXED_MIXEDQUANTITY;
                $params[] = RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY;
                $retRef = $this->dbAccess->executeQuery($query, $params);
              }
            break;

          case 20021:
          if(count($retRef) == 0)
          {
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
              "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
              "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
              "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
              "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
              "(20021, 1 , 1, 'Instrument.Version', 'Instrument.Version','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.Version', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 2 , 2, 'Instrument.ResourceID', 'Instrument.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 3 , 3, 'Instrument.ResourceHeader.ResourceName', 'Instrument.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 4 , 4, 'Instrument.ResourceHeader.ReleaseDate', 'Instrument.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 5 , 5, 'Instrument.ResourceHeader.Description', 'Instrument.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 6 , 6, 'Instrument.ResourceHeader.Contact.PersonID', 'Instrument.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 7 , 7, 'Instrument.ResourceHeader.Contact.Role', 'Instrument.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 8 , 8, 'Instrument.Type', 'Instrument.Type','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.Type', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 9 , 9, 'Instrument.InstrumentType', 'Instrument.InstrumentType','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.InstrumentType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 10 , 10, 'Instrument.InvestigationName', 'Instrument.InvestigationName','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.InvestigationName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20021, 11 , 11, 'Instrument.ObsevatoryID', 'Instrument.ObsevatoryID','text',0, 0, 0, 0, 0, '', '', '', '', 'Instrument.ObsevatoryID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
              $params = array();
              $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEID;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATE;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_INSTRUMENTTYPE;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME;
            $params[] = RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID;
              $retRef = $this->dbAccess->executeQuery($query, $params);
            }
            break;

          case 20022:
          if(count($retRef) == 0)
          {
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
              "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
              "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
              "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
              "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
              "(20022, 1 , 1, 'Observatory.ResourceID', 'Observatory.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 2 , 2, 'Observatory.ResourceHeader.ResourceName', 'Observatory.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 3 , 3, 'Observatory.ResourceHeader.ReleaseDate', 'Observatory.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 4 , 4, 'Observatory.ResourceHeader.Description', 'Observatory.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 5 , 5, 'Observatory.ResourceHeader.Contact.PersonID', 'Observatory.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 6 , 6, 'Observatory.ResourceHeader.Contact.Role', 'Observatory.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 7 , 7, 'Observatory.Location.ObservatoryRegion', 'Observatory.Location.ObservatoryRegion','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.Location.ObservatoryRegion', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 8 , 8, 'Observatory.Location.CoordinateSystemname.Latitude', 'Observatory.Location.CoordinateSystemname.Latitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.Location.CoordinateSystemname.Latitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 9 , 9, 'Observatory.Location.CoordinateSystemname.Longitude', 'Observatory.Location.CoordinateSystemname.Longitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.Location.CoordinateSystemname.Longitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20022, 10 , 10, 'Observatory.OperatingSpan.StartDate', 'Observatory.OperatingSpan.StartDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Observatory.OperatingSpan.StartDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
              $params = array();
              $params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEID;
$params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME;
$params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATE;
$params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION;
$params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID;
$params[] = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE;
$params[] = RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION;
$params[] = RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE;
$params[] = RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE;
$params[] = RepositoryConst::SPASE_OBSERVATORY_OPERATINGSPAN_STARTDATE;

              $retRef = $this->dbAccess->executeQuery($query, $params);
            }
            break;

          case 20023:
          if(count($retRef) == 0)
          {
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
              "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
              "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
              "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
              "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
              "(20023, 1 , 1, 'Person.ResourceID', 'Person.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'Person.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20023, 2 , 2, 'Person.ReleaseDate', 'Person.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Person.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20023, 3 , 3, 'Person.PersonName', 'Person.PersonName','text',0, 0, 0, 0, 0, '', '', '', '', 'Person.PersonName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20023, 4 , 4, 'Person.OrganizationName', 'Person.OrganizationName','text',0, 0, 0, 0, 0, '', '', '', '', 'Person.OrganizationName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20023, 5 , 5, 'Person.Email', 'Person.Email','text',0, 0, 0, 0, 0, '', '', '', '', 'Person.Email', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
              $params = array();
              $params[] = RepositoryConst::SPASE_PERSON_RESOURCEID;
$params[] = RepositoryConst::SPASE_PERSON_RELEASEDATE;
$params[] = RepositoryConst::SPASE_PERSON_PERSONNAME;
$params[] = RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME;
$params[] = RepositoryConst::SPASE_PERSON_EMAIL;
              $retRef = $this->dbAccess->executeQuery($query, $params);
            }
            break;

          case 20024:
          if(count($retRef) == 0)
          {
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
              "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
              "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
              "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
              "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
              "(20024, 1 , 1, 'Repository.ResourceID', 'Repository.ResourceID','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 2 , 2, 'Repository.ResourceHeader.ResourceName', 'Repository.ResourceHeader.ResourceName','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceHeader.ResourceName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 3 , 3, 'Repository.ResourceHeader.ReleaseDate', 'Repository.ResourceHeader.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceHeader.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 4 , 4, 'Repository.ResourceHeader.Description', 'Repository.ResourceHeader.Description','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceHeader.Description', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 5 , 5, 'Repository.ResourceHeader.Contact.PersonID', 'Repository.ResourceHeader.Contact.PersonID','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceHeader.Contact.PersonID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 6 , 6, 'Repository.ResourceHeader.Contact.Role', 'Repository.ResourceHeader.Contact.Role','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.ResourceHeader.Contact.Role', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20024, 7 , 7, 'Repository.AccessURL.URL', 'Repository.AccessURL.URL','text',0, 0, 0, 0, 0, '', '', '', '', 'Repository.AccessURL.URL', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
              $params = array();
              $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEID;
            $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME;
            $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATE;
            $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION;
            $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID;
            $params[] = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE;
            $params[] = RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL;
              $retRef = $this->dbAccess->executeQuery($query, $params);
            }
            break;

          case 20025:
          if(count($retRef) == 0)
          {
              $query = "INSERT INTO ".DATABASE_PREFIX."repository_item_attr_type ".
              "(item_type_id, attribute_id, show_order, attribute_name, attribute_short_name, ".
              "input_type, is_required, plural_enable, line_feed_enable, list_view_enable, hidden, ".
              "junii2_mapping, dublin_core_mapping, lom_mapping, lido_mapping, spase_mapping, display_lang_type, ".
              "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) VALUES ".
              "(20025, 1 , 1, 'File', 'File', 'file', 1, 1, 0, 0, 0, '', '', '', '', 'File', '','1','1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 2 , 2, 'Thumbnail', 'Thumbnail', 'thumbnail', 0, 1, 0, 0, 0, '', '', '', '', 'Thumbnail', '', '1','1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 3 , 3, 'Granule.ResourceID', 'Granule.ResourceID','text',1, 0, 0, 0, 0, '', '', '', '', 'Granule.ResourceID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 4 , 4, 'Granule.ReleaseDate', 'Granule.ReleaseDate','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.ReleaseDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 5 , 5, 'Granule.ParentID', 'Granule.ParentID','text',1, 0, 0, 0, 0, '', '', '', '', 'Granule.ParentID', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 6 , 6, 'Granule.StartDate', 'Granule.StartDate','text',1, 0, 0, 0, 0, '', '', '', '', 'Granule.StartDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 7 , 7, 'Granule.StopDate', 'Granule.StopDate','text',1, 0, 0, 0, 0, '', '', '', '', 'Granule.StopDate', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 8 , 8, 'Granule.Source.SourceType', 'Granule.Source.SourceType','text',1, 1, 0, 0, 0, '', '', '', '', 'Granule.Source.SourceType', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 9 , 9, 'Granule.Source.URL', 'Granule.Source.URL','text',1, 1, 0, 0, 0, '', '', '', '', 'Granule.Source.URL', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 10 , 10, 'Granule.Source.DataExtent.Quantity', 'Granule.Source.DataExtent.Quantity','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.Source.DataExtent.Quantity', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 11 , 11, 'Granule.SpatialCoverage.CoordinateSystem.CoordinateSystemName', 'Granule.SpatialCoverage.CoordinateSystem.CoordinateSystemName','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.CoordinateSystem.CoordinateSystemName', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 12 , 12, 'Granule.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', 'Granule.SpatialCoverage.CoordinateSystem.CoordinateRepresentation','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.CoordinateSystem.CoordinateRepresentation', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 13 , 13, 'Granule.SpatialCoverage.NorthernmostLatitude', 'Granule.SpatialCoverage.NorthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.NorthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 14 , 14, 'Granule.SpatialCoverage.SouthernmostLatitude', 'Granule.SpatialCoverage.SouthernmostLatitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.SouthernmostLatitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 15 , 15, 'Granule.SpatialCoverage.EasternmostLongitude', 'Granule.SpatialCoverage.EasternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.EasternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 16 , 16, 'Granule.SpatialCoverage.WesternmostLongitude', 'Granule.SpatialCoverage.WesternmostLongitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.WesternmostLongitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 17 , 17, 'Granule.SpatialCoverage.Unit', 'Granule.SpatialCoverage.Unit','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.Unit', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 18 , 18, 'Granule.SpatialCoverage.MinimumAltitude', 'Granule.SpatialCoverage.MinimumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.MinimumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 19 , 19, 'Granule.SpatialCoverage.MaximumAltitude', 'Granule.SpatialCoverage.MaximumAltitude','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.MaximumAltitude', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0),".
"(20025, 20 , 20, 'Granule.SpatialCoverage.Reference', 'Granule.SpatialCoverage.Reference','text',0, 0, 0, 0, 0, '', '', '', '', 'Granule.SpatialCoverage.Reference', '', '1', '1', '', '2008-03-18 00:00:00.000', '2008-03-18 00:00:00.000', '', 0);";
              $params = array();
              $params[] = "File";
$params[] = "Thumbnail";
$params[] = RepositoryConst::SPASE_GRANULE_RESOURCEID;
$params[] = RepositoryConst::SPASE_GRANULE_RELEASEDATE;
$params[] = RepositoryConst::SPASE_GRANULE_PARENTID;
$params[] = RepositoryConst::SPASE_GRANULE_STARTDATE;
$params[] = RepositoryConst::SPASE_GRANULE_STOPDATE;
$params[] = RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE;
$params[] = RepositoryConst::SPASE_GRANULE_SOURCE_URL;
$params[] = RepositoryConst::SPSAE_GRANULE_SOURCE_DATAEXTENT_QUANTITY;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE;
$params[] = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE;
              $retRef = $this->dbAccess->executeQuery($query, $params);
            }
            break;

          default:
            break;
        }

      }
    }

    /**
     * add external search word parameter and referer
     *
     */
    private function addExternalSearchWordParameter() {
        $user_id = $this->Session->getParameter("_user_id");
        // Add new record to parameter table
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                 "(param_name, param_value, explanation, ins_user_id, mod_user_id, ".
                 " del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES('externalsearchword_stopword', '0', 'リファラ解析時のストップワードの使用状態(0:使用し形態素解析は行う、1:使用し形態素解析は行わない、2:使用しない)', ?, ?, '0', ?, ?, '', 0), ".
                 "('show_detail_tagcloudflag', '1', 'リフ詳細画面でタグクラウド表示フラグ(0:表示しない、1:表示する)', ?, ?, '0', ?, ?, '', 0), ".
                 "('tagcloud_max_value', '10', '詳細画面でのタグクラウドの最大数', ?, ?, '0', ?, ?, '', 0), ".
                 "('path_mecab', '', 'mecabコマンドまでの絶対パス', ?, ?, '0', ?, ?, '', 0) ;";
        $params = array();
        $params[] = $user_id;   // ins_user_id
        $params[] = $user_id;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = $user_id;   // ins_user_id
        $params[] = $user_id;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = $user_id;   // ins_user_id
        $params[] = $user_id;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = $user_id;   // ins_user_id
        $params[] = $user_id;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * Create external search word tables
     *
     */
    private function createExternalSearchWordTable()
    {
        // create external search word table
        $query = "CREATE TABLE ". DATABASE_PREFIX ."repository_item_external_searchword (".
                 " `item_id` INT NOT NULL default 0, ".
                 " `item_no` INT NOT NULL default 0, ".
                 " `word` VARCHAR(255) NOT NULL default '', ".
                 " `count` INT NOT NULL default 0, ".
                 " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `ins_date` VARCHAR(23), ".
                 " `mod_date` VARCHAR(23), ".
                 " `del_date` VARCHAR(23), ".
                 " `is_delete` INT(1), ".
                 " PRIMARY KEY(`item_id`, `item_no`, `word`) ".
                 ") ENGINE=MYISAM;";
        $this->dbAccess->executeQuery($query);

        // create external searchword for search table
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
        $this->createFullTextSearchTable("repository_search_external_searchword", $isMroongaExist);
        // create search engines url analyze rule table
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_external_searchengine_analyticalrule ( ".
                 " `engine_id` INT NOT NULL default 0, ".
                 " `engine_name` TEXT NOT NULL, ".
                 " `domain` TEXT NOT NULL, ".
                 " `search_word` TEXT NOT NULL, ".
                 " `delimiter` TEXT NOT NULL, ".
                 " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `ins_date` VARCHAR(23), ".
                 " `mod_date` VARCHAR(23), ".
                 " `del_date` VARCHAR(23), ".
                 " `is_delete` INT(1), ".
                 " PRIMARY KEY(`engine_id`) ".
                 ") ENGINE=MYISAM;";
        $this->dbAccess->executeQuery($query);
        // add default search engines
        $engine_data = array();
        $engine_data['YAHOO! JAPAN'] = array('domain' => 'search.yahoo.co.jp', 'search_word' => 'p', 'delimiter' => '/[+\\s]+/');
        $engine_data['Google'] = array('domain' => 'www.google.co.jp', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $engine_data['msn'] = array('domain' => 'www.bing.com', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $engine_data['goo'] = array('domain' => 'search.goo.ne.jp', 'search_word' => 'MT', 'delimiter' => '/[+\\s]+/');
        $engine_data['infoseek'] = array('domain' => 'websearch.rakuten.co.jp', 'search_word' => 'qt', 'delimiter' => '/[+\\s]+/');
        $engine_data['excite'] = array('domain' => 'websearch.excite.co.jp', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $engine_data['Fresh'] = array('domain' => 'search.fresheye.com', 'search_word' => 'kw', 'delimiter' => '/[+\\s]+/');
        $engine_data['OCN'] = array('domain' => 'wsearch.ocn.ne.jp', 'search_word' => 'MT', 'delimiter' => '/[+\\s]+/');
        $engine_data['BIGLOBE'] = array('domain' => 'cgi.search.biglobe.ne.jp', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $engine_data['nifty'] = array('domain' => 'search.nifty.com', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $engine_data['livedoor'] = array('domain' => 'search.livedoor.com', 'search_word' => 'q', 'delimiter' => '/[+\\s]+/');
        $this->addSearchEngineData($engine_data);
        // create search engines url analyze rule table
        $query = "CREATE TABLE ". DATABASE_PREFIX. "repository_external_searchword_stopword ( ".
                 " `stop_word` VARCHAR(255) NOT NULL default '', ".
                 " `part_of_speech` INT NOT NULL default 0, ".
                 " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `ins_date` VARCHAR(23), ".
                 " `mod_date` VARCHAR(23), ".
                 " `del_date` VARCHAR(23), ".
                 " `is_delete` INT(1), ".
                 " PRIMARY KEY(`stop_word`, `part_of_speech`) ".
                 ") ENGINE=MYISAM;";
        $this->dbAccess->executeQuery($query);
        // add default stop words
        // 日本語
        // 接続詞(順接, 逆説, 並列・追加, 対比・選択, 説明・補足, 転換)
        $stopword = array("したがって", "そこで", "だから", "ゆえに", "すると", "よって", "で",
                       "だって", "ならば", "たとえば", "それならば", "ですから", "それで",
                       "しからば", "なぜなら", "なぜならば", "なんとなれば");                //順接
        $addStopword = array("しかし", "だが", "ところが", "でも", "が", "けれども", "けれど",
                       "それなのに", "なのに", "だのに", "一方", "それでも", "それでいて",
                       "だけれども", "だけども", "だけど");                                  // 逆説
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("および", "そして", "また", "加えて", "ならびに", "その上", "なお",
                       "しかも", "それから", "それに", "さらに");                            // 並列・追加
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("または", "あるいは", "それとも", "もしくは");                        // 対比・選択
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("つまり", "すなわち", "ただし", "ただ");                                    // 説明・補足
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("さて", "では", "ところで", "ときに", "それでは");            // 転換
        $stopword = array_merge($stopword, $addStopword);
        $this->addStopWord(7, $stopword);
        // 助詞
        $stopword = array("の", "が", "を", "に", "へ", "と", "より", "から", "にて", "して",
                       "とも", "ど", "ども", "て", "で", "つつ", "ながら", "すら", "さへ", "のみ",
                       "ばかり", "など", "なんど", "し", "も", "ぞ", "なむ", "なん", "や", "やは",
                       "か", "かは", "こそ", "そ", "ばや", "なも", "もが", "もがな", "もがも",
                       "かな", "かも", "しが", "しがな", "てしが", "てしがな", "にしが",
                       "にしがな", "な", "かし");
        $this->addStopWord(10, $stopword);
        // 英語
        // 冠詞, 接続詞, be動詞, 主語
        $stopword = array("a", "an", "the");                                                   // 冠詞
        $addStopword = array("after", "also", "although", "and", "as", "because", "before", "but",
                       "except", "for", "however", "if", "like", "nor", "now", "once",
                       "only", "or", "save", "since", "so", "than", "that", "though",
                       "till", "until", "when", "where", "whether", "while", "without",
                       "yet", "directly", "immediately", "plus", "unless", "whenever",
                       "wherever", "considering", "lest", "notwithstanding",
                       "whereas", "providing");                                             // 接続詞
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("be", "is", "am", "are", "was", "were", "been");                     // be動詞
        $stopword = array_merge($stopword, $addStopword);
        $addStopword = array("I", "we", "you", "he", "she", "it", "they");                        //主語
        $stopword = array_merge($stopword, $addStopword);
        $this->addStopWord(0, $stopword);
    }

    /**
     * Add stop word
     *
     * @param int $partOfSpeech part of speech number
     * @param Object $stopWord stop word array
     */
    private function addStopWord($partOfSpeech, $stopWord) {
        // partOfSpeech
        // 0：なし、1：動詞、2：形容詞、3：形容動詞、4：名詞、5：連体詞、6：副詞、7：接続詞、8：感動詞、9：助動詞、10：助詞
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                 "(stop_word, part_of_speech, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        for($ii = 0; $ii < count($stopWord); $ii++) {
            if($ii != 0) {
                $query.= ", ";
            }
            $query .= "(?, ?, ?, ?, '0', ?, ?, '', 0)";
            $params[] = $stopWord[$ii]; // stop word
            $params[] = $partOfSpeech; // part of speech
            $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
            $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
            $params[] = $this->TransStartDate;  // ins_date
            $params[] = $this->TransStartDate;  // mod_date
        }
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * Add search engine data
     *
     * @param Object $searchEngine
     */
    private function addSearchEngineData($searchEngine) {
        $engine_id = 1;
        // for compare id
        $standard_id = $engine_id;
        // Hash Table $searchEngine
        // key = "engine_name"
        // value = "domain"
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_searchengine_analyticalrule ".
                 "(engine_id, engine_name, domain, search_word, delimiter, ".
                 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        foreach($searchEngine as $engine_name => $engine_data) {
            if($engine_id != $standard_id) {
                $query.= ", ";
            }
            $query .= "(?, ?, ?, ?, ?, ?, ?, '0', ?, ?, '', 0)";
            $params[] = $engine_id; // engine id
            $params[] = $engine_name; // engine name
            $params[] = $engine_data['domain']; // domain
            $params[] = $engine_data['search_word']; // domain
            $params[] = $engine_data['delimiter']; // domain
            $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
            $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
            $params[] = $this->TransStartDate;  // ins_date
            $params[] = $this->TransStartDate;  // mod_date
            $engine_id++;
        }
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * update Weko Version 2.1.3 To 2.1.4
     *
     */
    private function updateWekoVersion213To214()
    {
        // create JaLC DOI and Cross Ref status table
        $this->createDoiStatusTable();

        // drop jalc_doi from index table
        $this->dropJalcdoiFromIndexTable();

        // version up to 2.1.4
        $this->versionUp('2.1.4');
    }

    /**
     * create JaLC DOI and Cross Ref status table
     *
     */
    private function createDoiStatusTable()
    {
        // create external search word table
        $query = "CREATE TABLE ". DATABASE_PREFIX ."repository_doi_status (".
                 " `item_id` INT NOT NULL default 0, ".
                 " `item_no` INT NOT NULL default 0, ".
                 " `status` INT NOT NULL default 0, ".
                 " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                 " `ins_date` VARCHAR(23), ".
                 " `mod_date` VARCHAR(23), ".
                 " `del_date` VARCHAR(23), ".
                 " `is_delete` INT(1), ".
                 " PRIMARY KEY(`item_id`, `item_no`) ".
                 ") ENGINE=innodb;";
        $this->dbAccess->executeQuery($query);
    }

    /**
     * drop jalc_doi from index table
     *
     */
    private function dropJalcdoiFromIndexTable()
    {
        // create external search word table
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_index ".
                 "DROP `jalc_doi` ; ";
        $this->dbAccess->executeQuery($query);
    }

    /**
     * update Weko Version 2.1.4 To 2.1.5
     *
     */
    private function updateWekoVersion214To215()
    {
        // version up to 2.1.5
        $this->versionUp('2.1.5');
    }

    /**
     * update Weko Version 2.1.5 To 2.1.6
     *
     */
    private function updateWekoVersion215To216()
    {
        // version up to 2.1.6
        $this->versionUp('2.1.6');
    }


    /**
     * update Weko Version 2.1.6 To 2.1.7
     *
     */
    private function updateWekoVersion216To217()
    {
        // add Library JaLC DOI Prefix
        $this->addLibraryJalcDoiPrefix();

        // add OAI-PMH Output Flag
        $this->addOutputOaipmhParameter();

        // add Editdoi Output Flag
        $this->addOutputEditdoiParameter();

        // mod LOM itemtype name
        $this->modifyLomItemtypeName();

        // mod Update Time of Content
        $this->modifyUpdateTimeOfContent();

        // version up to 2.1.7
        $this->versionUp('2.1.7');
    }

    /**
     * modify Update Time of Content
     * (date->text)
     *
     */
    private function modifyUpdateTimeOfContent()
    {
        $query = "UPDATE ". DATABASE_PREFIX. "repository_item_attr_type ".
                 " SET input_type = ? ".
                 " WHERE item_type_id >= ? ".
                 " AND item_type_id <= ? ".
                 " AND attribute_name = ?;";
        $params = array();
        $params[] = "text";
        $params[] = 20001;
        $params[] = 20016;
        $params[] = "コンテンツ更新日時";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * modify LOM item type name
     * ('Learning Object Matadata'->'Learning Object Metadata')
     *
     */
    private function modifyLomItemtypeName()
    {
        $query = "UPDATE ". DATABASE_PREFIX. "repository_item_type ".
                 " SET item_type_name = ?, ".
                 "     item_type_short_name = ? ".
                 " WHERE item_type_id = ?;";
        $params = array();
        $params[] = 'Learning Object Metadata';
        $params[] = 'Learning Object Metadata';
        $params[] = 20016;
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * add Library JaLC DOI Prefix
     *
     */
    private function addLibraryJalcDoiPrefix()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_prefix ".
                 "(id, prefix_name, prefix_id, ".
                 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = 50; // id
        $params[] = "国会図書館JaLC DOIプレフィックス"; // prefix name
        $params[] = "10.11501"; // prefix id
        $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * add OAI-PMH Output Flag
     *
     */
    private function addOutputOaipmhParameter()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                 "(param_name, param_value, explanation, ".
                 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "output_oaipmh"; // param_name
        $params[] = 1; // param_value
        $params[] = "OAI-PMH出力フラグ 0:出力しない 1:出力する"; // explanation
        $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * add Editdoi Output Flag
     *
     */
    private function addOutputEditdoiParameter()
    {
        $niitype_name = array();
        $niitype_name[] = "journal_article";
        $niitype_name[] = "article";
        $niitype_name[] = "preprint";
        $niitype_name[] = "departmental_bulletin_paper";
        $niitype_name[] = "thesis_or_dissertation";
        $niitype_name[] = "conference_paper";
        $niitype_name[] = "book";
        $niitype_name[] = "technical_report";
        $niitype_name[] = "research_paper";
        $niitype_name[] = "learning_material";
        $niitype_name[] = "data_or_dataset";
        $niitype_name[] = "software";
        $niitype_name[] = "presentation";
        $niitype_name[] = "others";
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                 "(param_name, param_value, explanation, ".
                 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        for($editdoi_num = 0; $editdoi_num < count($niitype_name); $editdoi_num++)
        {
            if($editdoi_num != 0)
            {
                $query .= ", ";
            }
            $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
            $params[] = "edit_doi_flag_".$niitype_name[$editdoi_num]; // param_name
            $params[] = 0; // param_value
            $params[] = $niitype_name[$editdoi_num]." DOI付与フラグ 0:付与しない 1:付与する"; // explanation
            $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
            $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
            $params[] = $this->TransStartDate;  // ins_date
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 0; // is delete
        }
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    // Add Default Search Type 2014/12/03 K.Sugimoto --start--
    /**
     * update Weko Version 2.1.7 To 2.1.8
     *
     */
    private function updateWekoVersion217To218()
    {
    	// add SPASE mapping
    	$this->addSpaseMapping();
    	// create item type SPASE
    	$this->createItemTypeSpase();

        // add Default Search Type
        $this->addDefaultSearchTypeParameter();

        // add Usage Statistics link display setting
        $this->addUsageStatisticsLinkDisplayParameter();

        // add ranking tab display
        $this->addRankingTabDisplayParameter();

        // add Institution Name
        $this->addInstitutionName();

        // add Itemtype Exclusive Authority Tables
        $this->createItemtypeAuthorityTable();

        // version up to 2.1.8
        $this->versionUp('2.1.8');
    }

    /**
     * add Default Search Type
     *
     */
    private function addDefaultSearchTypeParameter()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                 "(param_name, param_value, explanation, ".
                 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "default_search_type"; // param_name
        $params[] = 0; // param_value
        $params[] = "検索設定のデフォルト値設定 0:全文検索 1:キーワード検索"; // explanation
        $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }
    // Add Default Search Type 2014/12/03 K.Sugimoto --end--

    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --start--
    /**
     * add Usage Statistics link display setting
     */
    private function addUsageStatisticsLinkDisplayParameter()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                "(param_name, param_value, explanation, ".
                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "usagestatistics_link_display"; // param_name
        $params[] = 1; // param_value
        $params[] = "利用統計リンク表示設定 0:表示しない 1:表示する"; // explanation
        $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }
    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --end--

    // Add ranking tab display setting 2014/12/19 K.Matsushita --start--
    /**
     * add ranking tab display
     *
     */
    private function addRankingTabDisplayParameter()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                "(param_name, param_value, explanation, ".
                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "ranking_tab_display"; // param_name
        $params[] = 1; // param_value
        $params[] = "ランキングタブ表示値設定 0:表示しない 1:表示する"; // explanation
        $params[] = $this->Session->getParameter("_user_id");;   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");;   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }
    // Add ranking tab display setting 2014/12/19 K.Matsushita --end--

    /**
     * add Institution Name
     *
     */
    private function addInstitutionName()
    {
        $user_id = $this->Session->getParameter("_user_id");
        // Add new record to parameter table
        $query = "INSERT INTO " . DATABASE_PREFIX. "repository_parameter ".
        "(param_name, param_value, explanation, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) " .
        "VALUES (?, '', ?, ?, ?, ?, ?, ?, '', ?)";

        $params = array();
        $params[] = "institution_name";
        $params[] = "Google Scholar メタタグ出力用機関名称設定値";
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = "0";
        $params[] = $this->TransStartDate;
        $params[] = $this->TransStartDate;
        $params[] = 0;

        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * create itemtype authority tables
     *
     */
    private function createItemtypeAuthorityTable()
    {
        // create exclusive base authority table
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_item_type_exclusive_base_auth ( ".
                " `item_type_id` int(11) NOT NULL default 0, ".
                " `exclusive_base_auth_id` int(11) NOT NULL default 0, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`item_type_id`,`exclusive_base_auth_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        // create exclusive room authority table
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_item_type_exclusive_room_auth ( ".
                " `item_type_id` int(11) NOT NULL default 0, ".
                " `exclusive_room_auth_id` int(11) NOT NULL default 0, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `mod_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `del_user_id` VARCHAR(40) NOT NULL default '0', ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`item_type_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);
    }

    /**
     * update Weko Version 2.1.8 To 2.2.0
     *
     */
    private function updateWekoVersion218To220()
    {

        // add Sitelicense Information Tables
        $this->createSitelicenseInfoTable();

        // Add CrossRef Metadata Auto Input and Add DataCite 2015/02/03 K.Sugimoto --start--
        // add CrossRef Metadata Auto Input
        $this->addCrossrefQueryService();

        // add DataCite
        $this->addDataCite();
        // Add CrossRef Metadata Auto Input and Add DataCite 2015/02/03 K.Sugimoto --end--

	    // Extend Search Keyword 2015/02/20 K.Sugimoto --start--
        // convert search table hankaku
        $this->convertSearchTableKana();
	    // Extend Search Keyword 2015/02/20 K.Sugimoto --end--

        // add operation_log table
        $this->addOperationlog();

        // add elapsed time log table
        $this->addElapsedTimeLog();

        // create exclusion list tables
        $this->createLogExclusionListTables();

        // Improve Search Log 2015/03/19 K.Sugimoto --start--
        // add detail search item
        $this->addDetailSearchItem();
        // Improve Search Log 2015/03/19 K.Sugimoto --end--

        // bug fix external searchword empty 2015/04/09 K.Sugimoto --start--
        // delete external searchword empty
        $this->deleteExternalSearchwordEmpty();
        // bug fix external searchword empty 2015/04/09 K.Sugimoto --end--

        // update licence master creative common's link
        $this->updateLicenseMasterLinks();

        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_ELAPSEDTIME_LOG] = true;

        // version up to 2.2.0
        $this->versionUp('2.2.0');
    }

    /**
     * update licence master creative common's link
     *
     */
    private function updateLicenseMasterLinks()
    {
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX. "repository_license_master ";

        $result = $this->dbAccess->executeQuery($query);

        for ($ii = 0; $ii < count($result); $ii++)
        {
            $query = "UPDATE ".DATABASE_PREFIX. "repository_license_master ".
                     "SET text_url = ?".
                     "WHERE license_id = ?";

            $params = array();
            if ($result[$ii]["license_id"] == 101) {
                $params[] = "http://creativecommons.org/licenses/by/3.0/deed.ja";
            }
            else if ($result[$ii]["license_id"] == 102) {
                $params[] = "http://creativecommons.org/licenses/by-sa/3.0/deed.ja";
            }
            else if ($result[$ii]["license_id"] == 103) {
                $params[] = "http://creativecommons.org/licenses/by-nd/3.0/deed.ja";
            }
            else if ($result[$ii]["license_id"] == 104) {
                $params[] = "http://creativecommons.org/licenses/by-nc/3.0/deed.ja";
            }
            else if ($result[$ii]["license_id"] == 105) {
                $params[] = "http://creativecommons.org/licenses/by-nc-sa/3.0/deed.ja";
            }
            else {
                $params[] = "http://creativecommons.org/licenses/by-nc-nd/3.0/deed.ja";
            }

            $params[] = $result[$ii]["license_id"];

            $this->dbAccess->executeQuery($query, $params);
        }
    }

    /**
     * create log exclusion list tables of ip_address and user_agent
     *
     */
    private function createLogExclusionListTables()
    {
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_robotlist_master (".
                 "robotlist_id int(4) NOT NULL, ".
                 "robotlist_url text, ".
                 "is_robotlist_use int(1) NOT NULL, ".
                 "del_column varchar(40) NOT NULL, ".
                 "robotlist_version varchar(40) NOT NULL, ".
                 "robotlist_date varchar(100) NOT NULL, ".
                 "robotlist_revision varchar(40) NOT NULL, ".
                 "robotlist_author varchar(100) NOT NULL, ".
                 "ins_user_id varchar(40) NOT NULL default '0', ".
                 "mod_user_id varchar(40) NOT NULL default '0', ".
                 "del_user_id varchar(40) NOT NULL default '0', ".
                 "ins_date varchar(23) default NULL, ".
                 "mod_date varchar(23) default NULL, ".
                 "del_date varchar(23) default NULL, ".
                 "is_delete int(1) default NULL, ".
                 "PRIMARY KEY  (`robotlist_id`) ".
                 ") ENGINE=InnoDb DEFAULT CHARSET=utf8; ";
        $this->dbAccess->executeQuery($query);

        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_robotlist_data (".
                 "list_id int(10) NOT NULL AUTO_INCREMENT, ".
                 "robotlist_id int(4) NOT NULL, ".
                 "word text, ".
                 "status varchar(2) NOT NULL default '0', ".
                 "ins_user_id varchar(40) NOT NULL default '0', ".
                 "mod_user_id varchar(40) NOT NULL default '0', ".
                 "del_user_id varchar(40) NOT NULL default '0', ".
                 "ins_date varchar(23) default NULL, ".
                 "mod_date varchar(23) default NULL, ".
                 "del_date varchar(23) default NULL, ".
                 "is_delete int(1) default NULL, ".
                 "PRIMARY KEY  (`list_id`) ".
                 ") ENGINE=InnoDb DEFAULT CHARSET=utf8; ";
        $this->dbAccess->executeQuery($query);

        // Improve Log 2015/06/17 K.Sugimoto --start--
        $ipAddressCrawlerList = 'https://bitbucket.org/niijp/jairo-crawler-list/raw/master/JAIRO_Crawler-List_ip_blacklist.txt';
        $userAgentCrawlerList = 'https://bitbucket.org/niijp/jairo-crawler-list/raw/master/JAIRO_Crawler-List_useragent.txt';
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_robotlist_master ".
                 "(robotlist_id, robotlist_url, is_robotlist_use, del_column, robotlist_version, robotlist_date, robotlist_revision, robotlist_author, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                 "VALUES (?, ?, ?, ?, '', '', '', '', ?, ?, ?, ?, ?, ?, ?),".
                 "(?, ?, ?, ?, '', '', '', '', ?, ?, ?, ?, ?, ?, ?) ;";
        $params = array();
        $params[] = 1;                                          // robotlist_id
        $params[] = $ipAddressCrawlerList;                      // robotlist_url
        $params[] = 1;                                          // is_robotlist_use
        $params[] = 'ip_address';                               // del_column
        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = "";                                         // del_user_id
        $params[] = $this->TransStartDate;                      // ins_date
        $params[] = $this->TransStartDate;                      // mod_date
        $params[] = "";                                         // del_date
        $params[] = 0;                                          // is_delete

        $params[] = 2;                                          // robotlist_id
        $params[] = $userAgentCrawlerList;                      // robotlist_url
        $params[] = 1;                                          // is_robotlist_use
        $params[] = 'user_agent';                               // del_column
        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = "";                                         // del_user_id
        $params[] = $this->TransStartDate;                      // ins_date
        $params[] = $this->TransStartDate;                      // mod_date
        $params[] = "";                                         // del_date
        $params[] = 0;                                          // is_delete
        $this->dbAccess->executeQuery($query, $params);

        $this->insertLockTable('Repository_Action_Common_Robotlist', 0);
        $this->insertLockTable('Repository_Action_Common_Background_Deleterobotlist', 0);

        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_EXCLUDE_LOG] = true;
        // Improve Log 2015/06/17 K.Sugimoto --end--
    }

    /**
     * create elapsed log table
     *
     */
    private function addElapsedTimeLog()
    {
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_log_elapsed_time (".
                     "`log_no` INT, ".
                     "`elapsed_time` INT, ".
                     "`ins_user_id` VARCHAR(40) NOT NULL default '0',".
                     "`mod_user_id` VARCHAR(40) NOT NULL default '0',".
                     "`del_user_id` VARCHAR(40) NOT NULL default '0',".
                     "`ins_date` VARCHAR(23),".
                     "`mod_date` VARCHAR(23),".
                     "`del_date` VARCHAR(23),".
                     "`is_delete` INT(1),".
                     "PRIMARY KEY(`log_no`)".
                 ") ENGINE=MyISAM;";
         $this->dbAccess->executeQuery($query);

        $this->insertLockTable('Repository_Action_Common_Background_Elapsedtime', 0);

        // Improve Log 2015/06/17 K.Sugimoto --start--
        $query = "ALTER TABLE ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_LOG. " ".
                 "ADD INDEX ". RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE. " (".
                 RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE. "); ";
        $this->dbAccess->executeQuery($query);
        // Improve Log 2015/06/17 K.Sugimoto --end--
    }

    /**
     * create sitelicense tables
     *
     */
    private function createSitelicenseInfoTable()
    {
        // create info table
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_sitelicense_info ( ".
                " `organization_id` int(11) NOT NULL default 0, ".
                " `show_order` int default 0, ".
                " `organization_name` text NOT NULL, ".
                " `group_name` text NOT NULL, ".
                " `mail_address` VARCHAR(255) NOT NULL, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`organization_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        // create ip address table
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_sitelicense_ip_address ( ".
                " `organization_id` int(11) NOT NULL default 0, ".
                " `organization_no` int(11) NOT NULL default 0, ".
                " `start_ip_address` VARCHAR(16) NOT NULL, ".
                " `finish_ip_address` VARCHAR(16) NOT NULL, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`organization_id`, `organization_no`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        // insert data
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "site_license";
        $result = $this->dbAccess->executeQuery($query, $params);
        if(strlen($result[0]["param_value"]) > 0) {
            // 機関毎に分割する
            $sitelicenses = explode("|", $result[0]["param_value"]);
            // データを「機関名」「開始IP」「終了IP」「メールアドレス」に分割する
            $organization_id = 0; // 機関ID
            $show_order = 0;      // ソート順番
            for($ii = 0; $ii < count($sitelicenses); $ii++) {
                $sitelicense_data = explode(",", $sitelicenses[$ii]);
                // 機関ID
                $organization_id++;
                $show_order++;
                // 機関名
                $organization_name = $sitelicense_data[0];
                $organization_name = str_replace("&#124;", "|", $organization_name);
                $organization_name = str_replace("&#44;", ",", $organization_name);
                $organization_name = str_replace("&#46;", ".", $organization_name);
                // 開始IPアドレス
                $start_ip_address = $sitelicense_data[1];
                // 終了IPアドレス
                $finish_ip_address = $sitelicense_data[2];
                // メールアドレス
                $mail_address = $sitelicense_data[3];
                $mail_address = str_replace("&#124;", "|", $mail_address);
                $mail_address = str_replace("&#44;", ",", $mail_address);
                $mail_address = str_replace("&#46;", ".", $mail_address);

                // insert base info
                $query = "INSERT INTO ". DATABASE_PREFIX. "repository_sitelicense_info ".
                         "(organization_id, show_order, organization_name, group_name, mail_address, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ;";
                $params = array();
                $params[] = $organization_id;                           // organization_id
                $params[] = $show_order;                                // show order
                $params[] = $organization_name;                         // organization_name
                $params[] = "";                                         // group_name
                $params[] = $mail_address;                              // mail_address
                $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
                $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
                $params[] = "";                                         // del_user_id
                $params[] = $this->TransStartDate;                      // ins_date
                $params[] = $this->TransStartDate;                      // mod_date
                $params[] = "";                                         // del_date
                $params[] = 0;                                          // is_delete
                $this->dbAccess->executeQuery($query, $params);

                // insert ip address
                $query = "INSERT INTO ". DATABASE_PREFIX. "repository_sitelicense_ip_address ".
                         "(organization_id, organization_no, start_ip_address, finish_ip_address, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ;";
                $params = array();
                $params[] = $organization_id;                           // organization_id
                $params[] = 1;                                          // organization_no
                $params[] = $start_ip_address;                          // start_ip_address
                $params[] = $finish_ip_address;                         // finish_ip_address
                $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
                $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
                $params[] = "";                                         // del_user_id
                $params[] = $this->TransStartDate;                      // ins_date
                $params[] = $this->TransStartDate;                      // mod_date
                $params[] = "";                                         // del_date
                $params[] = 0;                                          // is_delete
                $this->dbAccess->executeQuery($query, $params);
            }

            // delete sitelicense data from parameter table
            $query = "DELETE FROM ". DATABASE_PREFIX. "repository_parameter ".
                     "WHERE param_name = ? ;";
            $params = array();
            $params[] = "site_license";
            $this->dbAccess->executeQuery($query, $params);
        }
        // サイトライセンスメール送信先テーブル
        // IPアドレス情報カラムを削除し、組織名カラムを追加する
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_send_mail_sitelicense ".
                 "DROP `start_ip_address` ; ";
        $this->dbAccess->executeQuery($query);
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_send_mail_sitelicense ".
                 "DROP `finish_ip_address` ; ";
        $this->dbAccess->executeQuery($query);

        // ログテーブルにサイトライセンス機関情報保存用カラムを作成する
        $query = "ALTER TABLE ". DATABASE_PREFIX ."repository_log ".
                 "ADD `site_license_id` INT(11) default 0 ".
                 "AFTER `site_license` ;";
        $this->dbAccess->executeQuery($query);

    }

    // Add CrossRef Metadata Auto Input and Add DataCite 2015/02/03 K.Sugimoto --start--
    /**
     * add CrossRef metadata auto input
     *
     */
    private function addCrossrefQueryService()
    {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                "(param_name, param_value, explanation, ".
                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "crossref_query_service_account"; // param_name
        $params[] = ""; // param_value
        $params[] = "CrossRef APIにアクセスするための設定値(メールアドレス)"; // explanation
        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);
    }

    /**
     * add DataCite
     *
     */
    private function addDataCite()
    {
    	// プレフィックステーブルにDataCite DOIプレフィックスレコードを追加
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_prefix ".
                "(id, prefix_name, prefix_id, ".
                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = 25; // id
        $params[] = "DataCite"; // prefix_name
        $params[] = ""; // prefix_id
        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);

        // パラメータテーブルにYハンドルPrefixフラグレコードを追加
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_parameter ".
                "(param_name, param_value, explanation, ".
                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                "VALUES ";
        $params = array();
        $query .= "(?, ?, ?, ?, ?, '', ?, ?, '', ?)";
        $params[] = "prefix_flag"; // param_name
        $params[] = 0; // param_value
        $params[] = "DOI付与時、YハンドルのPrefixIDをSuffixに付与するか否か(0:付与しない,1:付与する) すでにDOIを付与されている場合は変更できない"; // explanation
        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        $params[] = $this->TransStartDate;  // ins_date
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = 0; // is delete
        $query .= ";";
        $this->dbAccess->executeQuery($query, $params);

        // DOI付与フラグを取得
        $query = "SELECT param_name ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name LIKE ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = "edit_doi_flag_%";
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);

        // パラメータテーブルからDOI付与フラグを削除
        $query = "DELETE FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name LIKE ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = "edit_doi_flag_%";
        $params[] = 0;
        $this->dbAccess->executeQuery($query, $params);

        // DOI付与判定テーブルを作成
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_doi_flag ( ".
                " `doi_flag_id` INT NOT NULL, ".
                " `nii_type` text NOT NULL, ".
                " `jalc` INT(2) NOT NULL default '0', ".
                " `multiple_resolution` INT(2) NOT NULL default '0', ".
                " `crossref` INT(2) NOT NULL default '0', ".
                " `datacite` INT(2) NOT NULL default '0', ".
                " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`doi_flag_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        // DOI付与判定フラグを追加
        for($i = 0; $i < count($result); $i++)
        {
	        if(isset($result[$i]["param_name"]))
	        {
		        $niiType = str_replace("edit_doi_flag_", "", $result[$i]["param_name"]);
		        $niiType = str_replace("_", " ", $niiType);

		        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_doi_flag ".
		                "(doi_flag_id, nii_type, jalc, multiple_resolution, crossref, datacite, ".
		                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
		                "VALUES ";
		        $params = array();
		        $query .= "(?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, '', ?)";
		        $params[] = $i + 1; // doi_flag_id
		        $params[] = $niiType; // nii_type
		        if($niiType === "others")
		        {
			        $params[] = 0; // jalc
		        }
		        else
		        {
			        $params[] = 1; // jalc
		        }
		        if($niiType === "thesis or dissertation")
		        {
		        	$params[] = 1; // multiple_resolution
		        }
		        else
		        {
		        	$params[] = 0; // multiple_resolution
		        }
		        if(($niiType === "data or dataset")
		           || ($niiType === "learning material")
		           || ($niiType === "others")
		           || ($niiType === "presentation")
		           || ($niiType === "software")
		           || ($niiType === "thesis or dissertation"))
		        {
			        $params[] = 0; // crossref
		        }
		        else
		        {
			        $params[] = 1; // crossref
		        }
		        if(($niiType === "data or dataset") || ($niiType === "software"))
		        {
			        $params[] = 1; // datacite
		        }
		        else
		        {
			        $params[] = 0; // datacite
		        }
		        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
		        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
		        $params[] = $this->TransStartDate;  // ins_date
		        $params[] = $this->TransStartDate;  // mod_date
		        $params[] = 0; // is delete
		        $query .= ";";
		        $this->dbAccess->executeQuery($query, $params);
	        }
        }
    }
    // Add CrossRef Metadata Auto Input and Add DataCite 2015/02/03 K.Sugimoto --end--

    // Improve Search Log 2015/03/19 K.Sugimoto --start--
    /**
     * improve search log
     *
     */
    private function addDetailSearchItem()
    {
        // 詳細検索項目テーブルを作成
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_log_detail_search ( ".
                " `log_no` INT NOT NULL, ".
                " `advanced_search_id` INT(2) NOT NULL, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`log_no`,`advanced_search_id`) ".
                " ) ENGINE=MyISAM; ";
        $this->dbAccess->executeQuery($query);

        // 集計対象詳細検索項目テーブルを作成
        $query = "CREATE TABLE ".DATABASE_PREFIX. "repository_target_search_item ( ".
                " `search_item_id` INT(2) NOT NULL, ".
                " `ranking_flag` INT NOT NULL, ".
                " `ins_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `mod_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `del_user_id` VARCHAR(40) NOT NULL default 0, ".
                " `ins_date` VARCHAR(23), ".
                " `mod_date` VARCHAR(23), ".
                " `del_date` VARCHAR(23), ".
                " `is_delete` INT(1), ".
                " PRIMARY KEY  (`search_item_id`) ".
                " ) ENGINE=innodb; ";
        $this->dbAccess->executeQuery($query);

        // 既存キーワード検索ログ検索項目追加
        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_SEARCH_LOG] = true;

    	// 集計対象の詳細検索項目を追加
    	for($ii = 0; $ii < 26; $ii++)
    	{
	        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_target_search_item ".
	                "(search_item_id, ranking_flag, ".
	                " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
	                "VALUES ";
	        $params = array();
	        $query .= "(?, ?, ?, ?, '', ?, ?, '', ?)";
	        $params[] = $ii; // search_item_id
	        if($ii == 0 || $ii == 1 || $ii == 2 || $ii == 3 || $ii == 5 || $ii == 6 || $ii == 7 || $ii == 13)
	        {
		        $params[] = 1; // ranking_flag
	        }
	        else
	        {
		        $params[] = 0; // ranking_flag
	        }
	        $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
	        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
	        $params[] = $this->TransStartDate;  // ins_date
	        $params[] = $this->TransStartDate;  // mod_date
	        $params[] = 0; // is delete
	        $query .= ";";
	        $this->dbAccess->executeQuery($query, $params);
        }

        // ロックテーブルに既存検索ログ検索項目追加クラスを追加
        $this->insertLockTable('Repository_Action_Common_Background_Detailsearchitem', 0);
    }

    /**
     * execute recursive proccessing of DetailSearchItem
     *
     */
    private function addDetailSearchItemForExistSearchLog()
    {
        if (!isset($_SERVER["SERVER_PORT"])) // NULLの時
        {
            //BASE_URLの先頭をチェックしてhttps://なら443を$_SERVER["SERVER_PORT"]に入力
            if ( preg_match("/^https\:\/\//", BASE_URL) ) {
                $_SERVER["SERVER_PORT"] = 443;
            }
            //BASE_URLの先頭をチェックしてhttp://なら80を$_SERVER["SERVER_PORT"]に入力
            else if ( preg_match("/^http\:\/\//", BASE_URL) ){
                $_SERVER["SERVER_PORT"] = 80;
            }
        }

        $startLogNo = 1;

        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_background_detailsearchitem&log_no=".$startLogNo;
        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }
    // Improve Search Log 2015/03/19 K.Sugimoto --end--

    /**
     * execute recursive proccessing of ElapsedtimeLog
     *
     */
    private function insertElapsedLogs()
    {
        if (!isset($_SERVER["SERVER_PORT"])) // NULLの時
        {
            //BASE_URLの先頭をチェックしてhttps://なら443を$_SERVER["SERVER_PORT"]に入力
            if ( preg_match("/^https\:\/\//", BASE_URL) ) {
                $_SERVER["SERVER_PORT"] = 443;
            }
            //BASE_URLの先頭をチェックしてhttp://なら80を$_SERVER["SERVER_PORT"]に入力
            else if ( preg_match("/^http\:\/\//", BASE_URL) ){
                $_SERVER["SERVER_PORT"] = 80;
            }
        }

        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_background_elapsedtime";
        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }

    /**
     * add operation_log table
     */
    private function addOperationlog()
    {
        // create repository_operation_log table
        $query = "CREATE TABLE {repository_operation_log} ( ".
            " `log_id` INT, ".
            " `record_date` VARCHAR(23) NOT NULL, ".
            " `user_id` VARCHAR(40) NOT NULL default 0, ".
            " `request_parameter` TEXT, ".
            " `start_log_id` INT default 0, ".
            " PRIMARY KEY(`log_id`) ".
            ") ENGINE=MyISAM;";
        $this->dbAccess->executeQuery($query);
    }

    // Extend Search Keyword 2015/02/20 K.Sugimoto --start--
    /**
     * Convert Search Table Hankaku
     *
     */
    private function convertSearchTableKana()
    {
        $this->recursiveProcessingFlgList[self::KEY_REPOSITORY_SEARCH_TABLE_PROCESSING] = true;
    }
    // Extend Search Keyword 2015/02/20 K.Sugimoto --end--

    // bug fix external searchword empty 2015/04/09 K.Sugimoto --start--
    /**
     * delete external searchword empty
     */
    private function deleteExternalSearchwordEmpty()
    {
        $query = "DELETE FROM ". DATABASE_PREFIX. "repository_item_external_searchword ".
                 "WHERE word = ? ;";
        $params = array();
        $params[] = '';
        $this->dbAccess->executeQuery($query, $params);
    }
    // bug fix external searchword empty 2015/04/09 K.Sugimoto --end--

    // ImproveLog 2015/06/17 K.Sugimoto --start--
    /**
     * execute recursive proccessing of RobotList
     *
     */
    private function excludeLogOfRobotList()
    {
        if (!isset($_SERVER["SERVER_PORT"])) // NULLの時
        {
            //BASE_URLの先頭をチェックしてhttps://なら443を$_SERVER["SERVER_PORT"]に入力
            if ( preg_match("/^https\:\/\//", BASE_URL) ) {
                $_SERVER["SERVER_PORT"] = 443;
            }
            //BASE_URLの先頭をチェックしてhttp://なら80を$_SERVER["SERVER_PORT"]に入力
            else if ( preg_match("/^http\:\/\//", BASE_URL) ){
                $_SERVER["SERVER_PORT"] = 80;
            }
        }

        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_robotlist";
        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }
    // ImproveLog 2015/06/17 K.Sugimoto --end--

    /**
     * update Weko Version 2.2.0 To 2.2.1
     *
     */
    private function updateWekoVersion220To221()
    {
        // version up to 2.2.1
        $this->versionUp('2.2.1');
    }

    /**
     * update Weko Version 2.2.1 To 2.2.2
     *
     */
    private function updateWekoVersion221To222()
    {
        // Add CoverDeleteStatusTable
        $this->addCoverDeleteStatusTable();

        // version up to 2.2.2
        $this->versionUp('2.2.2');
    }

    private function addCoverDeleteStatusTable()
    {
        $query = "CREATE TABLE {repository_cover_delete_status} (".
            "`item_id` INT, ".
            "`item_no` INT, ".
            "`attribute_id` INT, ".
            "`file_no` INT, ".
            "`status` INT(1), ".
            "PRIMARY KEY(`item_id`, `item_no`, `attribute_id`, `file_no`)".
            ") ENGINE=MyISAM;";
        $this->dbAccess->executeQuery($query);
    }

    private function updateWekoVersion222To223()
    {
        // version up to 2.2.3
        $this->versionUp('2.2.3');
    }
}
?>
