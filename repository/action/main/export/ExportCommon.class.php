<?php
// --------------------------------------------------------------------
//
// $Id: ExportCommon.class.php 57287 2015-08-28 07:15:27Z keiya_sugimoto $
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
include_once MAPLE_DIR.'/validator/Validator.interface.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';

// Add xml escape string 2008/10/15 Y.Nakao --start-- 
class ExportCommon extends RepositoryAction
{
    var $Db = null;
    var $Session = null;
    
    public $RepositoryValidator = null;
    
    var $encode = null; // Add encode charset 2009/11/27 A.Suzuki
    
    function ExportCommon($db, $session, $tranceStartDate){
        if($db != null){
            $this->Db = $db;
            $this->dbAccess = new RepositoryDbAccess($this->Db);
        } else {
            return null;
        }
        if($session != null){
            $this->Session = $session;
        } else {
            return null;
        }
        // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
        if($tranceStartDate != null)
        {
            $this->TransStartDate = $tranceStartDate;
        }
        else
        {
            return null;
        }
        // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
        
        // Add encode charset 2009/11/27 A.Suzuki --start--
        if (stristr($_SERVER['HTTP_USER_AGENT'], "Mac")) {
            // Macの場合
            $this->encode = "UTF-8";
        } else if (stristr($_SERVER['HTTP_USER_AGENT'], "Windows")) {
            // Windowsの場合
            $this->encode = "SJIS";
        } else {
            $this->encode = _CHARSET;
        }
        // Add encode charset 2009/11/27 A.Suzuki --end--
        
        // Add config management authority 2010/02/22 Y.Nakao --start--
        $this->setConfigAuthority();
        // Add config management authority 2010/02/22 Y.Nakao --end--
        
        // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
        $this->RepositoryValidator = new Repository_Validator_DownloadCheck();
        $initResult = $this->RepositoryValidator->setComponents($this->Session, $this->Db);
        if($initResult === 'error'){
            return;
        }
        // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
        
    }
    
    /**
     * create Item export file
     *
     * @param $item_infos item_id and item_no
     * @param $tmp_dir work dir
     * @param $has_license file had lisense
     * @param $where_clause
     * @param $snipet_flg list or detail
     * @return xml string and export file(realities)
     */
    function createExportFile($item_infos, $tmp_dir, $has_license, $where_clause, $snipet_flg = false){

        // init
        $buf = "";
        $output_files = array(); // out put file name list
        $export_info = array();
        // Add Advanced Search 2013/11/26 R.Matsuura --start--
        $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->Db, $this->TransStartDate);
        // Add Advanced Search 2013/11/26 R.Matsuura --end--
        
        // get item data 
        $result = $this->getItemData($item_infos["item_id"],$item_infos["item_no"],$Result_List,$Error_Msg,true);
        if($result === false){
            print $Error_Msg;
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        $this->getItemXMLData($buf, $Result_List['item']);
        
        // Fix file download check Y.Nakao 2013/04/15 --start--
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");
        
        // check admin user
        // access user is admin user?
        $adminUser = false;
        if( $user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
        {
            $adminUser = true;
        }
        // access user is ins user?
        $insUser = false;
        if($user_id == $Result_List['item'][0]['ins_user_id'])
        {
            $insUser = true;
        }
        // Fix file download check Y.Nakao 2013/04/15 --end--
        
        // Fix check item public 2013/05/21 Y.Nakao --start--
        if(!$adminUser && !$insUser && !($itemAuthorityManager->checkItemPublicFlg($item_infos["item_id"],$item_infos["item_no"], $this->repository_admin_base, $this->repository_admin_room))){
            // close item.
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        // Fix check item public 2013/05/21 Y.Nakao --end--
        
        $result = $this->getItemTypeXMLData($buf, $Result_List['item_type'], $tmp_dir, $output_files, false);
        if($result === false){
            // Error
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        
        // item attr data (text,textarea,file, etc) XML 
        for ( $index = 0; $index < count($Result_List['item_attr_type']); $index++){
            // item type data
            $result = $this->getItemAttrTypeXMLData($buf, $Result_List['item_attr_type'][$index]);
            if($result === false){
                // Don't export because already item type delete 
                $export_info = array( 'buf' => "", 'output_files' => array() );
                return $export_info;
            }
            
            // Fix file download check Y.Nakao 2013/04/15 --start--
            if($Result_List['item_attr_type'][$index]['hidden'] == 1 && !($adminUser || $insUser))
            {
                // Can't export hidden metadata.
                continue;
            }
            // Fix file download check Y.Nakao 2013/04/15 --end--
            
            switch($Result_List['item_attr_type'][$index]['input_type']){
                case "name":
                    $this->getNameXMLData($buf, $Result_List['item_attr'][$index]);
                    break;
                case "file":
                case "file_price":
                    $result = $this->getFileXMLData($buf, $Result_List['item_attr'][$index], $tmp_dir, $output_files, $has_license, $where_clause, $snipet_flg, $index);
                    if($result === false){
                        // Error
                        $export_info = array( 'buf' => "", 'output_files' => $output_files );
                        return $export_info;
                    }
                    break;
                case "thumbnail":
                    $result = $this->getThumbnailXMLData($buf, $Result_List['item_attr'][$index], $tmp_dir, $output_files);
                    if($result === false){
                        // Error
                        $export_info = array( 'buf' => "", 'output_files' => $output_files );
                        return $export_info;
                    }
                    break;
                case "biblio_info":
                    $this->getBiblioInfoXMLData($buf, $Result_List['item_attr'][$index]);
                    break;
                case "text":
                case "textarea":
                case "link":
                case "checkbox":
                case "radio":
                case "select":
                case "date":
                case "heading":
                    $this->getItemAttrXMLData($buf, $Result_List['item_attr'][$index]);
                    break;
                case "supple":
                    break;
                default:
                    break;
            }
        }
        // Add e-person 2013/10/23 R.Matsuura --start--
        $result = $this->getFeedbackMailaddress($buf, $item_infos["item_id"], $item_infos["item_no"]);
        if($result === false){
            // Error
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        // Add e-person 2013/10/23 R.Matsuura --end--

        // return XML string data and out put file name list
        $export_info = array( 'buf' => $buf, 'output_files' => $output_files );
    
        return $export_info;
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
     * create Item type export file
     *
     * @param $tmp_dir work dir
     * @param $item_type_id
     * @return xml string and export file(realities)
     */
    function createItemTypeExportFile($tmp_dir, $item_type_id){
        // init
        $buf = "";
        $output_files = array();
        $export_info = array();
        
        // get item type data
        $result = $this->getItemTypeTableData($item_type_id, $Result_List, $Error_Msg, true);
        if($result === false){
            // Error
            print $Error_Msg;
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        
        // get item attr type data
        $result = $this->getItemAttrTypeTableData($item_type_id, $Result_List, $Error_Msg);
        if($result === false){
            // Error
            print $Error_Msg;
            $export_info = array( 'buf' => "", 'output_files' => $output_files );
            return $export_info;
        }
        
        // get item type xml data
        $result = $this->getItemTypeXMLData($buf, $Result_List['item_type'], $tmp_dir, $output_files, true);
        if($result === false){
            // Don't export because already item type delete 
            $export_info = array( 'buf' => "", 'output_files' => array() );
            return $export_info;
        }
        // get item type attr xml data
        for ( $ii = 0; $ii < count($Result_List['item_attr_type']); $ii++){
            $result = $this->getItemAttrTypeXMLData($buf, $Result_List['item_attr_type'][$ii]);
            if($result === false){
                // Don't export because already item type delete 
                $export_info = array( 'buf' => "", 'output_files' => array() );
                return $export_info;
            }
        }
        // return XML string and output file name list
        $export_info = array( 'buf' => $buf, 'output_files' => $output_files );
        return $export_info;
    }
    
    /**
     * get item xml data
     *
     * @param $buf xml string fro return
     * @param $Result_List item data
     * @return true or false
     */
    function getItemXMLData(&$buf, $items){
        // extraction item table data
        // join item XML data
        $buf .= "       <repository_item " .
                "item_id=\"" . $items[0]["item_id"] . "\" " .                   // item_id
                "item_no=\"" .$items[0]["item_no"] . "\" " .                    // item_no
                "revision_no=\"" .$items[0]["revision_no"] . "\" " .            // revision_no
                "item_type_id=\"" .$items[0]["item_type_id"] . "\" " .          // item_type_id
                "prev_revision_no=\"" .$items[0]["prev_revision_no"] . "\" " .  // prev_revision_no
                "title=\"" .htmlspecialchars($items[0]["title"], ENT_QUOTES, 'UTF-8') . "\" " . // title
                "title_english=\"" .htmlspecialchars($items[0]["title_english"], ENT_QUOTES, 'UTF-8') . "\" " . // title_english  add 2009/07/23 A.Suzuki
                "language=\"" .$items[0]["language"] . "\" " .                  // language
                "review_status=\"" .$items[0]["review_status"] . "\" " .        // review_status
                "review_date=\"" .$items[0]["review_date"] . "\" " .            // review_date
                "shown_status=\"" .$items[0]["shown_status"] . "\" " .          // shown_status
                "shown_date=\"" .$items[0]["shown_date"] . "\" " .              // shown_date
                "reject_status=\"" .$items[0]["reject_status"] . "\" " .        // reject_status
                "reject_date=\"" .$items[0]["reject_date"] . "\" " .            // reject_date
                "reject_reason=\"" .$items[0]["reject_reason"] . "\" " .        // reject_reason
                "serch_key=\"" .htmlspecialchars($items[0]["serch_key"], ENT_QUOTES, 'UTF-8') . "\" " .                // serch_key
                "serch_key_english=\"" .htmlspecialchars($items[0]["serch_key_english"], ENT_QUOTES, 'UTF-8') . "\" " .// serch_key_english    add 2009/07/23 A.Suzuki
                "remark=\"" .$items[0]["remark"] . "\"" .                       // remark
                " />\n";
        return true;
    }
    
    /**
     * get item type export data
     *
     * @param $buf xml string for return
     * @param $item_types item type data
     * @param $tmp_dir work dir pass
     * @param $output_files output files info
     * @param $icon_export item type export is true
     * @return true or false
     */
    function getItemTypeXMLData(&$buf, $item_types, $tmp_dir, &$output_files, $icon_export){
        // check delete item type
        if($item_types[0]["is_delete"] == 1){
            // If item type delete then not export this item
            return false;
        }
        // join item type XML data
        $buf .= "       <repository_item_type ".
                "item_type_id=\"" . $item_types[0]["item_type_id"] . "\" " .                    // item_type_id
                "item_type_name=\"" .htmlspecialchars($item_types[0]["item_type_name"], ENT_QUOTES, 'UTF-8') . "\" " .                // item_type_name
                "item_type_short_name=\"" .$item_types[0]["item_type_short_name"] . "\" " . // item_type_short_name
                "explanation=\"" .$item_types[0]["explanation"] . "\" " .   // explanation
                "mapping_info=\"" .$item_types[0]["mapping_info"] . "\" ";  // mapping_info
        // If is file and icon export is true
        if (strlen($item_types[0]["icon_name"]) > 0 && $icon_export){
            // join icon XML data
            $buf .= "icon_name=\"". htmlspecialchars($item_types[0]["icon_name"], ENT_QUOTES, 'UTF-8') ."\" " . // icon_name
                    "icon_mime_type=\"". $item_types[0]["icon_mime_type"] ."\" ".   // icon_mime_type
                    "icon_extension=\"". $item_types[0]["icon_extension"] ."\" ";   // icon_extension
            // icon file out put
            // set file name encoding
            $icon_name = $tmp_dir . "/" . mb_convert_encoding($item_types[0]["icon_name"], $this->encode, "auto");
            $result = $this->createFile($icon_name, $item_types[0]["icon"] );
            array_push($output_files, $icon_name);
        }
        $buf .= "/>\n";
        
        return true;
    }
    
    /**
     * Get item type attribute and candidata
     * 
     * @param $buf xml string for return
     * @param $item_attr_types item attr type table data
     * @return true or false
     */
    function getItemAttrTypeXMLData(&$buf, $item_attr_types){
        $buf .= "       <repository_item_attr_type " .
                "item_type_id=\"" . $item_attr_types["item_type_id"] . "\" " .                  // item_type_id
                "attribute_id=\"" .$item_attr_types["attribute_id"] . "\" " .                   // attribute_id
                "show_order=\"" .$item_attr_types["show_order"] . "\" " .                       // show_order
                "attribute_name=\"" .htmlspecialchars($item_attr_types["attribute_name"], ENT_QUOTES, 'UTF-8') . "\" " .              // attribute_name
                "attribute_short_name=\"" .htmlspecialchars($item_attr_types["attribute_short_name"], ENT_QUOTES, 'UTF-8') . "\" " .  // attribute_short_name
                "input_type=\"" .$item_attr_types["input_type"] . "\" " .                       // input_type
                "is_required=\"" .$item_attr_types["is_required"] . "\" " .                     // is_required
                "plural_enable=\"" .$item_attr_types["plural_enable"] . "\" " .                 // plural_enable
                "line_feed_enable=\"" .$item_attr_types["line_feed_enable"] . "\" " .           // line_feed_enable
                "list_view_enable=\"" .$item_attr_types["list_view_enable"] . "\" " .           // list_view_enable
                "hidden=\"" .$item_attr_types["hidden"] . "\" " .                               // hidden   add 2009/02/18 A.Suzuki
                "junii2_mapping=\"" .$item_attr_types["junii2_mapping"] . "\" " .               // junii2_mapping
                "dublin_core_mapping=\"" .$item_attr_types["dublin_core_mapping"] . "\" " .     // dublin_core_mapping
                "lom_mapping=\"" .$item_attr_types["lom_mapping"] . "\" " .                     // lom_mapping
                "display_lang_type=\"" .$item_attr_types["display_lang_type"] . "\"/>\n";       // display_lang_type    add 2009/07/23 A.Suzuki
        
        // get item attr candidate
        $query = "SELECT * " .
                 "FROM ". DATABASE_PREFIX ."repository_item_attr_candidate " .
                 "WHERE item_type_id = ? " .
                 "AND attribute_id = ? " .
                 "AND is_delete = 0 ". 
                 "order by candidate_no ;";
        $params_item_attr_candidate = null;
        $params_item_attr_candidate[0] = intval($item_attr_types["item_type_id"]);  // item_type_id
        $params_item_attr_candidate[1] = intval($item_attr_types["attribute_id"]);  // attribute_id
        // Run query
        $result_candi = $this->Db->execute( $query, $params_item_attr_candidate );
        if($result_candi === false){
            print $this->Db->ErrorMsg();
            return false;
        }
        for ( $jj = 0; $jj < count($result_candi); $jj++){
            $buf .= "       <repository_item_attr_candidate item_type_id=\"" . $result_candi[$jj]["item_type_id"] . "\" " . // item_type_id
                    "attribute_id=\"" .$result_candi[$jj]["attribute_id"] . "\" " .                                         // attribute_id
                    "candidate_no=\"" .$result_candi[$jj]["candidate_no"] . "\" " .                                         // candidate_no
                    "candidate_value=\"" .htmlspecialchars($result_candi[$jj]["candidate_value"], ENT_QUOTES, 'UTF-8'). "\" " .                                   // candidate_value
                    "candidate_short_value=\"" .htmlspecialchars($result_candi[$jj]["candidate_short_value"], ENT_QUOTES, 'UTF-8') . "\" />\n";                       // candidate_short_value
        }
        return true;
    }
    
    /**
     * get item attr XML data
     *
     * @param $buf xml string for return
     * @param $item_attrs item attr table data
     */
    function getItemAttrXMLData(&$buf, $item_attrs){
        for ( $cnt_attr = 0; $cnt_attr < count($item_attrs); $cnt_attr++){
            $buf .= "       <repository_item_attr item_id=\"" . $item_attrs[$cnt_attr]["item_id"] . "\" " . // item_id
                    "item_no=\"" .$item_attrs[$cnt_attr]["item_no"] . "\" " .                               // attribute_id
                    "attribute_id=\"" .$item_attrs[$cnt_attr]["attribute_id"] . "\" " .                     // item_no
                    "attribute_no=\"" .$item_attrs[$cnt_attr]["attribute_no"] . "\" " .                     // attribute_id
                    "attribute_value=\"" .htmlspecialchars(str_replace("\n", "\\n", $item_attrs[$cnt_attr]["attribute_value"]), ENT_QUOTES, 'UTF-8') . "\" " .                // attribute_value
                    "item_type_id=\"" .$item_attrs[$cnt_attr]["item_type_id"] . "\" />\n";                      // item_type_id
        }
    }
    
    /**
     * get Name XML data
     *
     * @param $buf xml string for return
     * @param $personal_names name table data
     */
    function getNameXMLData(&$buf, $personal_names){
        for ( $cnt_name = 0; $cnt_name < count($personal_names); $cnt_name++){
            $author_id_data = $this->getAuthorIdXMLData($personal_names[$cnt_name]["author_id"]);
            $buf .= "       <repository_personal_name " .
                    "item_id=\"" . $personal_names[$cnt_name]["item_id"] . "\" " .                  // item_id
                    "item_no=\"" .$personal_names[$cnt_name]["item_no"] . "\" " .                   // attribute_id
                    "attribute_id=\"" .$personal_names[$cnt_name]["attribute_id"] . "\" " .         // item_no
                    "personal_name_no=\"" .$personal_names[$cnt_name]["personal_name_no"] . "\" " . // personal_name_no
                    "family=\"" .htmlspecialchars($personal_names[$cnt_name]["family"], ENT_QUOTES, 'UTF-8') . "\" " .                        // family
                    "name=\"" .htmlspecialchars($personal_names[$cnt_name]["name"], ENT_QUOTES, 'UTF-8') . "\" " .                            // name
                    "family_ruby=\"" .htmlspecialchars($personal_names[$cnt_name]["family_ruby"], ENT_QUOTES, 'UTF-8') . "\" " .              // family_ruby
                    "name_ruby=\"" .htmlspecialchars($personal_names[$cnt_name]["name_ruby"], ENT_QUOTES, 'UTF-8') . "\" " .                  // name_ruby
                    "item_type_id=\"" .$personal_names[$cnt_name]["item_type_id"] . "\" " .         // item_type_id
                    "e_mail_address=\"" .htmlspecialchars($personal_names[$cnt_name]["e_mail_address"], ENT_QUOTES, 'UTF-8') . "\" ".    // e_mail_address
                    "author_id=\"" .htmlspecialchars($personal_names[$cnt_name]["author_id"], ENT_QUOTES, 'UTF-8') . "\" ".   // author_id
                    //"prefix_id=\"" .htmlspecialchars($author_id_data["prefix_id"], ENT_QUOTES, 'UTF-8') . "\" ".      // prefix_id
                    "prefix_name=\"" .htmlspecialchars($author_id_data["prefix_name"], ENT_QUOTES, 'UTF-8') . "\" ".  // prefix_name
                    "suffix=\"" .htmlspecialchars($author_id_data["suffix"], ENT_QUOTES, 'UTF-8') . "\" />\n";        // suffix
        }
    }
    
    /**
     * get file XML data
     * 
     * @param $buf xml string for return
     * @param $files file table data
     * @param $tmp_dir work dir pass
     * @param $output_files out put file name list
     * @param $has_license file had lisense
     * @param $where_clause
     * @param $snipet_flg list or detail
     */
    function getFileXMLData(&$buf, $files, $tmp_dir, &$output_files, $has_license, $where_clause, $snipet_flg, $attribute_id)
    {
        // Add separate file from DB 2009/04/21 Y.Nakao --start--
        // get contents save path
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // check directory exists 
        if( !(file_exists($contents_path)) ){
            //$this->Session->setParameter("error_msg", $error_msg);
            return false;
        }
        
        // Fix file download check Y.Nakao 2013/04/11 --start--
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");
        
        // check admin user
        // access user is admin user?
        $adminUser = false;
        if( $user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
        {
            $adminUser = true;
        }
        // access user is ins user?
        $insUser = false;
        if(isset($files[0]['ins_user_id']))
        {
            if($user_id == $files[0]['ins_user_id'])
            {
                $insUser = true;
            }
        }
        
        // when not file at export, not file download.
        $this->getAdminParam("export_is_include_files", $export_is_include_files, $error);
        // 全てのファイルについて同意しない場合でも、管理者、登録ユーザはファイルをダウンロードできる
        if( ($has_license != "true" || $export_is_include_files == 0) && !$adminUser && !$insUser)
        {
            // not license OR repository_parameter is not file export => can't file download
            // admin OR insuser => can file download
            return;
        }
        // Fix file download check Y.Nakao 2013/04/11 --end--
        
        // Add separate file from DB 2009/04/21 Y.Nakao --end--
        // If agreement license then download OK 
        
        // get realities file 
        // call from list
        $params_file = null;
        if ($snipet_flg === true) {
            $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_file " .
                     "WHERE item_id = ? " .
                     "AND item_no = ? " .
                     "AND attribute_id = ? " .
                     $where_clause .
                     "AND is_delete = 0 ".
                     "ORDER BY show_order ASC;";
            // バインド変数設定
            $params_file[] = intval($files[0]["item_id"]);      // item_id
            $params_file[] = intval($files[0]["item_no"]);      // item_no
            $params_file[] = intval($files[0]["attribute_id"]); // attribute_id
        }
        // call from detail
        else {
            $query = "SELECT * FROM ". DATABASE_PREFIX ."repository_file " .
                     "WHERE item_id = ? " .
                     "AND item_no = ? " .
                     "AND attribute_id = ? " .
                     $where_clause.
                     "AND is_delete = 0 ".
                     "ORDER BY show_order ASC;";
            // バインド変数設定
            $params_file[] = intval($files[0]["item_id"]);  // item_id
            $params_file[] = intval($files[0]["item_no"]);  // item_no
            $params_file[] = intval($files[0]["attribute_id"]); // attribute_id
        }
        // クエリ実行
        $result = $this->Db->execute( $query, $params_file );
        if($result === false){
            print $this->Db->ErrorMsg();
            return false;
        }
        // ファイルが複数不可属性だった場合2つ目以降のファイルを削除 T.Ichikawa 2013/9/17 --start--
        $search_result = $this->getItemTableData($files[0]["item_id"],$files[0]["item_no"],$Result_List,$Error_Msg);
        if($search_result === false){
            return false;
        }
        $item_type_id = $Result_List['item'][0]['item_type_id'];
        $search_result = $this->getItemAttrTypeTableData($item_type_id,$Result_List,$Error_Msg);
        if($search_result === false){
            return false;
        }
        //DBから取得したファイルの複数可否の属性を調べ、複数不可なら2つ目以降を削除する
        if($Result_List["item_attr_type"][$attribute_id]["plural_enable"] != 1) {
            array_splice($result, 1);
        }
        
        // ファイルが複数不可属性だった場合２つ目以降を削除 T.Ichikawa 2013/9/17 --end--
        // Modify Price method move validator K.Matsuo 2011/10/18 --start--
        for($ii=0; $ii<count($result);$ii++)
        {
            // if is file OK
            
            // Fix file download check Y.Nakao 2013/04/11 --start--
            $status = $this->RepositoryValidator->checkFileAccessStatus($result[$ii]);
            if( $status == "free" || $status == "already" || $status == "admin" || $status == "license" )
            {
                // this file use can download
                // thid file use can download
                // join file XML data
                /* Mod for lisence special char and linefeed code 2012/08/20 Tatsuya.Koyasu -start- */
                $buf .= "       <repository_file " .
                        "item_id=\"" . $result[$ii]["item_id"] . "\" " .                    // item_id
                        "item_no=\"" .$result[$ii]["item_no"] . "\" " .                 // item_no
                        "attribute_id=\"" .$result[$ii]["attribute_id"] . "\" " .           // attribute_id
                        "file_no=\"" . ($ii+1) . "\" " .                 // file_no
                        "file_name=\"" .htmlspecialchars($result[$ii]["file_name"], ENT_QUOTES, 'UTF-8') . "\" " .    // file_name
                        "display_name=\"" .htmlspecialchars($result[$ii]["display_name"], ENT_QUOTES, 'UTF-8') . "\" " .  // display_name
                        "display_type=\"" .$result[$ii]["display_type"]. "\" " .    // display_type
                        "mime_type=\"" .$result[$ii]["mime_type"] . "\" " .             // mime_type
                        "extension=\"" .$result[$ii]["extension"] . "\" " .             // extension
                        "license_id=\"" .$result[$ii]["license_id"] . "\" " .               // license_id
                        "license_notation=\"".htmlspecialchars(str_replace("\n", "\\n", $result[$ii]["license_notation"]), ENT_QUOTES, 'UTF-8') . "\" " . // license_notation
                        "pub_date=\"" .$result[$ii]["pub_date"] . "\" ";                    // pub_date
                /* Mod for lisence special char and linefeed code 2012/08/20 Tatsuya.Koyasu -end- */
                // Add multimedia support 2012/08/27 T.Koyasu -start-
                // Modify displayType=flashView->flash contents is exists or not
                if($this->RepositoryValidator->existsFlashContents($result[$ii]["item_id"], $result[$ii]["item_no"], $result[$ii]["attribute_id"], $result[$ii]["file_no"])){
                    $buf .= "flash_pub_date=\"" .$result[$ii]["flash_pub_date"] . "\" ";    // flash_pub_date
                }
                // Add multimedia support 2012/08/27 T.Koyasu -end-
                // Add browsing_flag 2011/02/16 A.Suzuki --start--
                $buf .= "item_type_id=\"" .$result[$ii]["item_type_id"] . "\" ".        // item_type_id
                        "browsing_flag=\"" .$result[$ii]["browsing_flag"] . "\" ".     // browsing_flag
                        "cover_created_flag=\"" .$result[$ii]["cover_created_flag"] . "\"/>\n";     // cover_created_flag
                // Add browsing_flag 2011/02/16 A.Suzuki --end--
                $result_file_price_Table = $this->RepositoryValidator->getFilePriceTable($result[$ii]["item_id"], $result[$ii]["item_no"], $result[$ii]["attribute_id"], $result[$ii]["file_no"]);
                if(count($result_file_price_Table)!=0){
                    // If file price then join file price XML data
                    $buf .= "       <repository_file_price " .
                            "item_id=\"" . $result_file_price_Table[0]["item_id"] . "\" " .         // item_id
                            "item_no=\"" .$result_file_price_Table[0]["item_no"] . "\" " .          // item_no
                            "attribute_id=\"" .$result_file_price_Table[0]["attribute_id"] . "\" " .    // attribute_id
                            "file_no=\"" . $result_file_price_Table[0]["file_no"] . "\" " ;          // file_no
                    // Add the management value is changed from room_id to page_name. Y.Nakao 2008/10/06 --start--
                    $price_export = "";
                    $price = explode("|", $result_file_price_Table[0]["price"]);
                    for($jj=0; $jj<count($price); $jj++){
                        $info = explode(",", $price[$jj]);
                        if($info[0] != ''){
                            if($info[0] != '0'){
                                // get page_name from room_id
                                $query = "SELECT page_name FROM ". DATABASE_PREFIX ."pages ".
                                         "WHERE room_id = ?; ";
                                $params = array();
                                $params = $info[0]; 
                                $pages = $this->Db->execute( $query, $params );
                                if($pages === false){
                                    print $this->Db->ErrorMsg();
                                    return false;
                                }
                                if(count($pages) == 1){
                                    // group is not delete
                                    if($jj != 0){
                                        $price_export .= "|";
                                    }
                                    $price_export .= $pages[0]["page_name"] .",". $info[1];
                                }
                            } else {
                                if($jj != 0){
                                    $price_export .= "|";
                                }
                                $price_export .= $price[$jj];
                            }
                        }
                    }
                    // Add the management value is changed from room_id to page_name. Y.Nakao 2008/10/06 --end--
                    $buf .= "price=\"" . htmlspecialchars_decode($price_export, ENT_QUOTES) . "\" />\n";            // price
                }
                // join license XML data
                /* Mod for lisence special char and linefeed code 2012/08/20 Tatsuya.Koyasu -start- */
                $buf .= "       <repository_license_master license_id=\"" . $result[$ii]["license_id"] . 
                        "\" license_notation=\"" . htmlspecialchars(str_replace("\n", "\\n", $result[$ii]["license_notation"]), ENT_QUOTES, 'UTF-8') . "\" />\n"; // license_id
                /* Mod for lisence special char and linefeed code 2012/08/20 Tatsuya.Koyasu -end- */
                
                $file_path = $contents_path.DIRECTORY_SEPARATOR.
                            $result[$ii]["item_id"].'_'.
                            $result[$ii]["attribute_id"].'_'.
                            $result[$ii]["file_no"].'.'.
                            $result[$ii]["extension"];
                $output_file = $tmp_dir;
                
                // Add encode charset 2009/11/27 A.Suzuki --start--
                $output_file .= mb_convert_encoding($result[$ii]["file_name"], $this->encode, "auto");
                // Add encode charset 2009/11/27 A.Suzuki --end--
                copy($file_path, $output_file);
                // Add separate file from DB 2009/04/22 Y.Nakao --end--
                if($result[$ii]["extension"] == "pdf") {
                    $this->addPdfCover($output_file, 
                                       $result[$ii]["item_id"], 
                                       $result[$ii]["item_no"], 
                                       $result[$ii]["attribute_id"], 
                                       $result[$ii]["file_no"]);
                }
                
                // Mod entryLog T.Koyasu 2015/03/06 --start--
                $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
                BusinessFactory::initialize($this->Session, $this->Db, $this->TransStartDate);
                $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
                $logManager->entryLogForDownload($result[$ii]["item_id"], $result[$ii]["item_no"], $result[$ii]["attribute_id"], $result[$ii]["file_no"]);
                // Mod entryLog T.Koyasu 2015/03/06 --end--
                
                array_push($output_files, $output_file);
            }
            // Fix file download check Y.Nakao 2013/04/11 --end--
            
            // Add Export auth limit 2008/11/26 Y.Nakao --end--
            // get attached file data
            $query = "SELECT * " .
                     "FROM ". DATABASE_PREFIX ."repository_attached_file " .
                     "WHERE item_id = ? " .
                     "AND item_no = ? " .
                     "AND attribute_id = ? " .
                     "AND file_no = ? " .
                    "AND is_delete = 0 ;";
            $params_attached_file = null;
            $params_attached_file[] = intval($result[$ii]["item_id"]);          // item_id
            $params_attached_file[] = intval($result[$ii]["item_no"]);          // item_no
            $params_attached_file[] = intval($result[$ii]["attribute_id"]); // attribute_id
            $params_attached_file[] = intval($result[$ii]["file_no"]);          // file_no
            // Run query
            $attached_files = $this->Db->execute( $query, $params_attached_file );
            for ( $cnt_attached_file = 0; $cnt_attached_file < count($attached_files); $cnt_attached_file++){
                $buf .= "       <repository_attached_file " .
                        "item_id=\"" . $attached_files[$cnt_attached_file]["item_id"] . "\" " .                         // item_id
                        "item_no=\"" .$attached_files[$cnt_attached_file]["item_no"] . "\" " .                          // item_no
                        "attribute_id=\"" .$attached_files[$cnt_attached_file]["attribute_id"] . "\" " .                // attribute_id
                        "file_no=\"" .$attached_files[$cnt_attached_file]["file_no"] . "\" " .                          // file_no
                        "attached_file_no=\"" .$attached_files[$cnt_attached_file]["attached_file_no"] . "\" " .        // attached_file_no
                        "attached_file_name=\"" .htmlspecialchars($attached_files[$cnt_attached_file]["attached_file_name"], ENT_QUOTES, 'UTF-8') . "\" " .   // attached_file_name
                        "mime_type=\"" .$attached_files[$cnt_attached_file]["mime_type"] . "\" " .                      // mime_type
                        "extension=\"" .$attached_files[$cnt_attached_file]["extension"] . "\" />\n";                   // attached_file_name
                // If is attache file OK
                if ( 0 < strlen($attached_files[$cnt_attached_file]["attached_file_name"]) ){
                    // out put attached file
                    $output_file = $tmp_dir . "/" . mb_convert_encoding($attached_files[$cnt_attached_file]["attached_file_name"], $this->encode, "auto");
                    $this->createFile($output_file, $attached_files[$cnt_attached_file]["attached_file"]);
                    array_push($output_files, $output_file);
                }
            }
        }
    }
    
    /**
     * get thumbnail XML data
     * 
     * @param $buf xml string for return
     * @param $personal_names name table data
     * @param $tmp_dir work dir pass
     * @param $output_files out put file name list 
     */
    function getThumbnailXMLData(&$buf, $thumbnails, $tmp_dir, &$output_files)
    {
        // Fix file download check Y.Nakao 2013/04/11 --end--
        // when not file at export, not file download.
        $this->getAdminParam("export_is_include_files", $export_is_include_files, $error);
        if($export_is_include_files == 0)
        {
            return;
        }
        // Fix file download check Y.Nakao 2013/04/11 --end--
        
        for ( $cnt_thumbnails = 0; $cnt_thumbnails < count($thumbnails); $cnt_thumbnails++)
        {
            $buf .= "       <repository_thumbnail ".
                    "item_id=\"" . $thumbnails[$cnt_thumbnails]["item_id"] . "\" " .                // item_id
                    "item_no=\"" .$thumbnails[$cnt_thumbnails]["item_no"] . "\" " .                 // item_no
                    "attribute_id=\"" .$thumbnails[$cnt_thumbnails]["attribute_id"] . "\" " .       // attribute_id
                    "file_no=\"" .($cnt_thumbnails + 1) . "\" " .                 // file_no
                    "file_name=\"" .htmlspecialchars($thumbnails[$cnt_thumbnails]["file_name"], ENT_QUOTES, 'UTF-8') . "\" " .                // file_name
                    "mime_type=\"" .$thumbnails[$cnt_thumbnails]["mime_type"] . "\" " .             // mime_type
                    "extension=\"" .$thumbnails[$cnt_thumbnails]["extension"] . "\" " .             // extension
                    "item_type_id=\"" .$thumbnails[$cnt_thumbnails]["item_type_id"] . "\" />\n";    // item_type_id

            // ファイルが設定されている場合
            if ( 0 < strlen($thumbnails[$cnt_thumbnails]["file_name"]) ){
                // サムネイルのファイルを出力する
                // Add encode charset 2009/11/27 A.Suzuki --start--
                $output_file = $tmp_dir . "/" . mb_convert_encoding($thumbnails[$cnt_thumbnails]["file_name"], $this->encode, "auto");
                // Add encode charset 2009/11/27 A.Suzuki --end--
                $this->createFile($output_file, $thumbnails[$cnt_thumbnails]["file"] );
                array_push($output_files, $output_file);
            }
        }
    }
    
    /**
     * @param $buf xml string for return
     * @param $personal_names name table data
     */
    function getBiblioInfoXMLData(&$buf, $biblio_info){
        for ( $cnt_biblio = 0; $cnt_biblio < count($biblio_info); $cnt_biblio++){
            $buf .= "       <repository_biblio_info " .
                    "item_id=\"" . $biblio_info[$cnt_biblio]["item_id"] . "\" " .                   // item_id
                    "item_no=\"" .$biblio_info[$cnt_biblio]["item_no"] . "\" " .                    // attribute_id
                    "attribute_id=\"" .$biblio_info[$cnt_biblio]["attribute_id"] . "\" " .          // item_no
                    "biblio_no=\"" .$biblio_info[$cnt_biblio]["biblio_no"] . "\" " .                // biblio_no
                    "biblio_name=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["biblio_name"], ENT_QUOTES, 'UTF-8') . "\" " .           // biblio_name
                    "biblio_name_english=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["biblio_name_english"], ENT_QUOTES, 'UTF-8') . "\" " .   // biblio_name_english  add 2009/07/23 A.Suzuki
                    "volume=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["volume"], ENT_QUOTES, 'UTF-8') . "\" " .                     // volume 
                    "issue=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["issue"], ENT_QUOTES, 'UTF-8') . "\" " .                       // issue
                    "start_page=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["start_page"], ENT_QUOTES, 'UTF-8') . "\" " .              // start_page
                    "end_page=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["end_page"], ENT_QUOTES, 'UTF-8') . "\" " .                 // end_page
                    "date_of_issued=\"" .htmlspecialchars($biblio_info[$cnt_biblio]["date_of_issued"], ENT_QUOTES, 'UTF-8') . "\" " .     // date_of_issued
                    "item_type_id=\"" .$biblio_info[$cnt_biblio]["item_type_id"] . "\" />\n";       // item_type_id
        }
    }
    
    /**
     * get AuthorID XML data
     *
     * @param int $author_id
     */
    function getAuthorIdXMLData($author_id){
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        $block_id = str_replace("_", "", $this->Session->getParameter("repository_module_id"));
        $room_id = $this->Session->getParameter("_main_room_id");
        $author_id_data = $NameAuthority->getExternalAuthorIdData($author_id);
        if($author_id_data===false){
            return false;
        }
        $prefix_id = "";
        $prefix_name = "";
        $suffix = "";
        for ($ii=0;$ii<count($author_id_data);$ii++){
            if($author_id_data[$ii]["prefix_id"]!="" && $author_id_data[$ii]["prefix_name"]!="" && $author_id_data[$ii]["suffix"]!=""){
                if($prefix_id!="" && $prefix_name!="" && $suffix!=""){
                    $prefix_id .= "|";
                    $prefix_name .= "|";
                    $suffix .= "|";
                }
                $prefix_id .= $author_id_data[$ii]["prefix_id"];
                $prefix_name .= $author_id_data[$ii]["prefix_name"];
                $suffix .= $author_id_data[$ii]["suffix"];
            }
        }
        return array("prefix_id"=>$prefix_id, "prefix_name"=>$prefix_name, "suffix"=>$suffix);
    }
    
    // Add e-person 2013/10/23 R.Matsuura --start--
    /**
     * get Feedback Mailaddress
     *
     * @param string $buf
     * @param int $itemId
     * @param int $itemNo
     */
    function getFeedbackMailaddress(&$buf, $itemId, $itemNo){
        // get mail address
        $result = $this->getFeedbackMailFromDb($itemId, $itemNo);
        if($result === false){
            print $this->Db->ErrorMsg();
            return false;
        }
        
        for($nCnt = 0; $nCnt < count($result); $nCnt++) {
            $buf .= "       <repository_feedback_mailaddress " .
                    "item_id=\"" . $itemId . "\" " .            // item_id
                    "item_no=\"" . $itemNo . "\" >" .           // item_no
                    $result[$nCnt]["suffix"] .                // suffix(e mail address)
                    "</repository_feedback_mailaddress>\n";
        }
        return ;
    }
    
    /**
     * get Feedback Mailaddress from Database
     *
     * @param int $itemId
     * @param int $itemNo
     */
    public function getFeedbackMailFromDb($itemId, $itemNo) {
        // get author id
        $query = "SELECT author_id " .
                 "FROM ". DATABASE_PREFIX ."repository_send_feedbackmail_author_id " .
                 "WHERE item_id = ? " .
                 "AND item_no = ? " .
                 "order by author_id_no ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        // Run query
        $result_get_author = $this->Db->execute( $query, $params );
        if($result_get_author === false){
            return false;
        }
        
        // get mail address
        $query = "SELECT suffix " .
                 "FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix " .
                 "WHERE prefix_id = 0 " .
                 "AND author_id IN ( " ;
        $params = array();
        for($ii = 0; $ii < count($result_get_author); $ii++) {
            $params[] = $result_get_author[$ii]["author_id"];
            if($ii == 0) {
                $query .= "?";
            }
            else
            {
                $query .= ", ?";
            }
        }
        $query .= " ) AND is_delete = 0 ;";
        // Run query
        if(count($result_get_author) > 0) {
            $result = $this->Db->execute( $query, $params );
        }
        if($result === false){
            return false;
        }
        return $result;
    }
    // Add e-person 2013/10/23 R.Matsuura --end--
    
    /**
     * PDFカバーページを付与する
     *
     * @param  string $file_path
     */
    private function addPdfCover($file_path, $itemId, $itemNo, $attributeId, $fileNo) {
        // 一時ファイル作成先ディレクトリ
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
        $tmpDirPath = $businessWorkdirectory->create();
        
        // PDFカバーページ作成クラス
        $this->infoLog("businessPdfcover", __FILE__, __CLASS__, __LINE__);
        $pdfCover = BusinessFactory::getFactory()->getBusiness("businessPdfcover");
        $newFile = $pdfCover->grantPdfCover($itemId, $itemNo, $attributeId, $fileNo, $tmpDirPath);
        copy($newFile, $file_path);
    }
}
// Add xml escape string 2008/10/15 Y.Nakao --end--
?>
