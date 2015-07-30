<?php
// --------------------------------------------------------------------
//
// $Id: ImportCommon.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryPdfCover.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/sword/SwordUpdate.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryCheckFileTypeUtility.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryImportXmlValidator.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * Import common action
 */
class ImportCommon extends RepositoryAction
{
    // component
    var $Session = null;
    var $Db = null;
    var $TransStartDate = null;

    var $encode = null;         // Add encode charset 2009/11/27 A.Suzuki

    private $logFh = null;

    // Const
    const PUBDATE = "pubDate";
    const YEAR = "year";
    const MONTH = "month";
    const DAY = "day";

    private $repositoryHandleManager = null;

    /**
     * Set log file handle
     *
     * @param resource $handle
     */
    public function setLogFileHandle($handle)
    {
        $this->logFh = $handle;
    }

    /**
     * Write log to file
     *
     * @param string $string
     * @param int $length [optional]
     * @return int
     */
    private function writeLog($string, $length=null)
    {
        if(isset($this->logFh))
        {
            if(isset($length))
            {
                return fwrite($this->logFh, $string, $length);
            }
            else
            {
                return fwrite($this->logFh, $string);
            }
        }
        else
        {
            return false;
        }
    }

    function ImportCommon($session, $db, $ins_date){
        if($session!=null){
            $this->Session = $session;
        }
        if($db!=null){
            $this->Db = $db;
            $this->dbAccess = new RepositoryDbAccess($this->Db);
        }
        if($ins_date!=null){
            $this->TransStartDate = $ins_date;
        }

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
    }
    /**
     * XML analysis
     *
     * Decompression from Zip file
     * ->Registration of item in Zip
     * ->すでに登録済みは登録しない(他に登録済みがなくても)
     * ->アイテムタイプはかぶることがある
     *
     * @param $tmp_dir upload file put dir pass
     * @access  public
     */
    function XMLAnalysis($tmp_dir, &$ret_info, &$error_msg)
    {
        $this->writeLog("-- Start XMLAnalysis in ImportCommon class --\n");
        // Add XML Check 2014/09/12 T.Ichikawa --start--
        $xmlValidator = new RepositoryImportXmlValidator();
        $result = $xmlValidator->validateXml($tmp_dir, $error_msg);
        if(count($error_msg) > 0) {
            $this->Session->setParameter("error_msg", "XML format is wrong.");
            $this->writeLog("error_msg: XML format is wrong.");
            $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
            return false;
        }
        // Add XML Check 2014/09/12 T.Ichikawa --end--
        $ret_info = array();
        // set XML data to array
        $content = file_get_contents($tmp_dir . "/import.xml");
        if( !$content ){
            $this->Session->setParameter("error_msg", "Not Found XML file. file name : import.xml");
            $this->writeLog("error_msg: Not Found XML file. file name : import.xml");
            $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
            return false;
        }
        try{
            $this->writeLog("Start XML parse.\n");
            $xml_parser = xml_parser_create();
            $rtn = xml_parse_into_struct( $xml_parser, $content, $vals );
            
            if($rtn == 0){
                $this->Session->setParameter("error_msg", "Not right XML.");
                $this->writeLog("error_msg: Not right XML.");
                $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
                return false;
            }
            xml_parser_free($xml_parser);
            $this->writeLog("End XML parse. Successfully.\n");
        } catch(Exception $ex){
            $this->writeLog("Exception occurred in XML parse.\n");
            $this->writeLog("  ".$ex->getMessage()."\n");
            $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
            return false;
        }

        // init DB column
        $item_column = array( 'ITEM_ID', 'ITEM_NO', 'REVISION_NO', 'ITEM_TYPE_ID', 'PREV_REVISION_NO', 'TITLE', 'TITLE_ENGLISH', 'LANGUAGE', 'REVIEW_STATUS', 'REVIEW_DATE', 'SHOWN_STATUS', 'SHOWN_DATE', 'REJECT_STATUS', 'REJECT_DATE', 'REJECT_REASON', 'SERCH_KEY', 'SERCH_KEY_ENGLISH', 'REMARK' );   // add "title_english" and "serch_key_english" 2009/07/23 A.Suzuki
        $item_type_column = array( 'ITEM_TYPE_ID', 'ITEM_TYPE_NAME', 'ITEM_TYPE_SHORT_NAME', 'EXPLANATION', 'MAPPING_INFO', 'ICON_NAME', 'ICON_MIME_TYPE', 'ICON_EXTENSION' );
        $item_attr_type_column = array( 'ITEM_TYPE_ID', 'ATTRIBUTE_ID', 'SHOW_ORDER', 'ATTRIBUTE_NAME', 'ATTRIBUTE_SHORT_NAME', 'INPUT_TYPE', 'IS_REQUIRED', 'PLURAL_ENABLE', 'LINE_FEED_ENABLE', 'LIST_VIEW_ENABLE', 'HIDDEN', 'JUNII2_MAPPING', 'DUBLIN_CORE_MAPPING','LOM_MAPPING', 'DISPLAY_LANG_TYPE' ); // add hidden 2009/02/18 A.Suzuki // add "display_lang_type" 2009/02/18 A.Suzuki
        $item_attr_candidate_column = array( 'ITEM_TYPE_ID', 'ATTRIBUTE_ID', 'CANDIDATE_NO', 'CANDIDATE_VALUE', 'CANDIDATE_SHORT_VALUE' );
        $item_attr_column = array( 'ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'ATTRIBUTE_NO', 'ATTRIBUTE_VALUE', 'ITEM_TYPE_ID' );
        $personal_name_column = array( 'ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'PERSONAL_NAME_NO', 'FAMILY', 'NAME', 'FAMILY_RUBY', 'NAME_RUBY', 'E_MAIL_ADDRESS', 'ITEM_TYPE_ID', 'AUTHOR_ID', 'PREFIX_ID', 'PREFIX_NAME', 'SUFFIX' );
        $thumbnail_column = array( 'ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'FILE_NO', 'FILE_NAME', 'MIME_TYPE', 'EXTENSION', 'ITEM_TYPE_ID' );
        $file_column = array('ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'FILE_NO', 'FILE_NAME', 'DISPLAY_NAME', 'DISPLAY_TYPE', 'MIME_TYPE', 'EXTENSION', 'LICENSE_ID', 'LICENSE_NOTATION', 'PUB_DATE', 'FLASH_PUB_DATE', 'ITEM_TYPE_ID', 'BROWSING_FLAG', 'COVER_CREATED_FLAG' );
        $attached_file_column = array('ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'FILE_NO', 'ATTACHED_FILE_NO', 'ATTACHED_FILE_NAME', 'MIME_TYPE', 'EXTENSION' );
        // Add biblio info Y.Nakao 2008/08/22 --start--
        $biblio_info_column = array('ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'BIBLIO_NO', 'BIBLIO_NAME', 'BIBLIO_NAME_ENGLISH', 'VOLUME', 'ISSUE', 'START_PAGE', 'END_PAGE', 'DATE_OF_ISSUED', 'ITEM_TYPE_ID' );   // add "biblio_name_english" 2009/02/18 A.Suzuki
        // Add biblio info Y.Nakao 2008/08/22 --end--
        // Add file price Y.Nakao 2008/09/03 --start--
        $file_price_column = array('ITEM_ID', 'ITEM_NO', 'ATTRIBUTE_ID', 'FILE_NO', 'PRICE' );
        // Add file price Y.Nakao 2008/09/03 --end--
        $item_edit_column = array('ITEM_ID', 'ITEM_NO');
        // Add e-person R.Matsuura 2013/10/21 --start--
        $feedback_mailaddress_column = array('ITEM_ID', 'ITEM_NO');
        // Add e-person R.Matsuura 2013/10/21 --end--
        // Add cnri handle T.Ichikawa --start-- 2014/09/17 --start--
        $suffix_column = array('ITEM_ID', 'ITEM_NO', 'CNRI');
        // Add cnri handle T.Ichikawa --start-- 2014/09/17 --end--
        $selfdoi_column = array('ITEM_ID', 'ITEM_NO', 'RA', 'SELFDOI');

        // Object array(Key: The one that main key tied by '_' and value: Array of item (Key = column name: Value = value))
        $item_key_array = array();                  // item
        $item_type_key_array = array();             // item type
        $item_attr_type_key_array = array();        // item attr type
        $item_attr_candidate_key_array = array();   // item attr candidate
        $item_attr_key_array = array();             // item attr
        $personal_name_key_array = array();         // name
        $thumbnail_key_array = array();             // thumbnail
        $file_key_array = array();                  // sile
        $attached_file_key_array = array();         // file price
        //$license_master_key_array = array();      // license
        $biblio_info_key_array = array();           // Add biblio info Y.Nakao 2008/08/22 --Add--
        $file_price_key_array = array();            // Add file price Y.Nakao 2008/09/03 --Add--
        $item_edit_key_array = array();
        $feedback_mailaddress_key_array = array();      // Add  e-person R.Matsuura 2013/10/21 --Add--
        $cnri_key_array = array();                  // Add cnri handle T.Ichikawa 2014/09/17 --Add--
        $selfdoi_key_array = array();
        
        // KeyMap（Key=existing ID、Value=new ID）
        $item_type_key_map = array();
        $item_key_map = array();
        //$license_master_key_map = array();

        // Object of each table
        $item_type_array = array();
        $item_array = array();
        $item_attr_type_array = array();
        $item_attr_candidate_array = array();
        $item_attr_array = array();
        $personal_name_array = array();
        $thumbnail_array = array();
        $file_array = array();
        $attached_file_array = array();
        //$license_master_array = array();
        $biblio_info_array = array();   // Add biblio info 2008/08/22 Y.Nakao --Add--
        $file_price_array = array();    // Add file price 2008/09/03 Y.Nakao --Add--
        $edit_array = array();
        $feedback_mailaddress_array = array();      // Add  e-person R.Matsuura 2013/10/21 --Add--
        $cnri_array = array();                  // Add cnri handle T.Ichikawa 2014/09/17 --Add--
        $selfdoi_array = array();

        $array_item_type_data = array(); // XML data of each item type
        $array_item_data = array(); // XML data of each item

        //$item_type_name_array = array();// html表示に使用

        // 各テーブルごとにレコードを切り出す
        for ( $row_cnt = 0; $row_cnt < count($vals); $row_cnt++ ) {
            if ($this->forXmlChangeDecode($vals[$row_cnt]['tag']) == 'EXPORT') {
                continue;
            }

            // サーフェースで解凍するとタグが大文字になる
            $insert_data = array();
            switch ($this->forXmlChangeDecode($vals[$row_cnt]['tag'])){
                case 'REPOSITORY_ITEM_TYPE':
                    if(count($item_type_array) > 0){
                        // item type info
                        array_push($array_item_type_data, array('item_type_array' => $item_type_array,
                                                    'item_attr_type_array' => $item_attr_type_array,
                                                    'item_attr_candidate_array' => $item_attr_candidate_array
                                                )
                        );
                    }
                    $item_type_array = array();
                    $item_attr_type_array = array();
                    $item_attr_candidate_array = array();
                    $item_attr_candidate_key_array = array();

                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_type_column );
                    $key = $insert_data['ITEM_TYPE_ID'];
                    //array_push($item_type_name_array, $insert_data['ITEM_TYPE_NAME']);
                    // 重複チェック
                    if (in_array($key, $item_type_key_array)) {
                        //echo '指定されたアイテムタイプは、既に存在する。 Key=' . $key . ']<br>';
                        continue;   // 重複するデータはスキップ
                    }
                    $item_type_key_array[] = $key;
                    $item_type_array = array_merge($item_type_array, array($insert_data));
                    break;

                case 'REPOSITORY_ITEM':                 // アイテム

                    // 配列$array_item_dataが空でなければ追加
                    if(count($item_array) > 0){
                        // item info
                        array_push($array_item_data, array( 'item_array' => $item_array,
                                                'item_attr_array' => $item_attr_array,
                                                'personal_name_array' => $personal_name_array,
                                                'thumbnail_array' => $thumbnail_array ,
                                                'file_array' => $file_array,
                                                'attached_file_array' => $attached_file_array,
                                                'item_key_array' => $item_key_array,
                                                'item_type_key_array' => $item_type_key_array,
                                                //'item_type_name_array' => $item_type_name_array,
                                                'item_attr_type_key_array' => $item_attr_type_key_array,
                                                'biblio_info_array' => $biblio_info_array,
                                                'file_price_array' => $file_price_array,
                                                'edit_array' => $edit_array,
                                                'feedback_mailaddress_array' => $feedback_mailaddress_array,
                                                'cnri_array' => $cnri_array,
                                                'selfdoi_array' => $selfdoi_array
                                        )
                        );
                        $item_array = array();
                        $item_attr_array = array();
                        $personal_name_array = array();
                        $thumbnail_array = array();
                        $file_array = array();
                        $attached_file_array = array();
                        $item_key_array = array();
                        $item_type_key_array = array();
                        //$item_type_name_array = array();
                        $item_attr_type_key_array = array();
                        $biblio_info_array = array();
                        $file_price_array = array();
                        $edit_array = array();
                        $feedback_mailaddress_array = array();      // Add  e-person R.Matsuura 2013/10/24 --Add--
                        $cnri_array = array();                  // Add cnri handle T.Ichikawa 2014/09/17 --Add--
                        $selfdoi_array = array();
                    }

                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'];
                    // 重複チェック
                    if (in_array($key, $item_key_array)) {
                        //echo '指定されたアイテムは、既に存在する。 Key=' . $key . ']<br>';
                        continue;
                    }
                    $item_key_array[] = $key;
                    $item_array = array_merge($item_array, array($insert_data));
                    break;

                case 'REPOSITORY_ITEM_ATTR_TYPE':       // アイテム属性タイプ
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_attr_type_column );
                    $key = $insert_data['ITEM_TYPE_ID'] . '_' . $insert_data['ATTRIBUTE_ID'];
                    // 重複チェック
                    if (in_array($key, $item_attr_type_key_array)) {
                        //echo '指定されたアイテム属性タイプは、既に存在するよ。 Key=' . $key . ']<br>';
                        continue;   // 重複するデータはスキップ
                    }
                    $item_attr_type_key_array[] = $key;
                    $item_attr_type_array = array_merge($item_attr_type_array, array($insert_data));
                    break;

                case 'REPOSITORY_ITEM_ATTR_CANDIDATE':  // アイテム属性候補
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_attr_candidate_column );
                    $key = $insert_data['ITEM_TYPE_ID'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['CANDIDATE_NO'];
                    // 重複チェック
                    if (in_array($key, $item_attr_candidate_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $item_attr_candidate_key_array[] = $key;
                    $item_attr_candidate_array = array_merge($item_attr_candidate_array, array($insert_data));
                    break;

                case 'REPOSITORY_ITEM_ATTR':            // アイテム属性
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_attr_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['ATTRIBUTE_NO'];
                    // 重複チェック
                    if (in_array($key, $item_attr_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $item_attr_key_array[] = $key;
                    $item_attr_array = array_merge($item_attr_array, array($insert_data));
                    break;

                case 'REPOSITORY_PERSONAL_NAME':        // 氏名
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $personal_name_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['PERSONAL_NAME_NO'];
                    // 重複チェック
                    if (in_array($key, $personal_name_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $prefix_name_array = explode("|", $insert_data['PREFIX_NAME']);
                    $suffix_array = explode("|", $insert_data['SUFFIX']);
                    if(count($prefix_name_array)!=count($suffix_array)){
                        $this->Session->setParameter("error_msg", "Author ID count is wrong. item_id : ".$insert_data['ITEM_ID'].", item_no : ".$insert_data['ITEM_NO'].", attribute_id : ".$insert_data['ATTRIBUTE_ID'].", personal_name_no : ".$insert_data['PERSONAL_NAME_NO']);
                        return false;
                    }
                    $personal_name_key_array[] = $key;
                    $personal_name_array = array_merge($personal_name_array, array($insert_data));
                    break;

                case 'REPOSITORY_THUMBNAIL':            // サムネイル
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $thumbnail_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['FILE_NO'];
                    // 重複チェック
                    if (in_array($key, $thumbnail_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $thumbnail_key_array[] = $key;
                    $thumbnail_array = array_merge($thumbnail_array, array($insert_data));
                    break;

                case 'REPOSITORY_FILE':                 // ファイル
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $file_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['FILE_NO'];
                    // 重複チェック
                    //if (in_array($key, $file_key_array)) {
                    if (in_array($key, $file_key_array)) {
                        ////print("file_array重複<br>");
                        continue;   // 重複するデータはスキップ
                    }
                    ////print("file_arrayスルー KEY=".$key."<BR>");
                    //$file_key_array[] = $key;
                    $file_key_array[] = $key;
                    $file_array = array_merge($file_array, array($insert_data));
                    break;

                case 'REPOSITORY_ATTACHED_FILE':        // 添付ファイル
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $attached_file_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['FILE_NO'] . '_' . $insert_data['ATTACHED_FILE_NO'];
                    // 重複チェック
                    if (in_array($key, $attached_file_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $attached_file_key_array[] = $key;
                    $attached_file_array = array_merge($attached_file_array, array($insert_data));
                    break;

                case 'REPOSITORY_LICENSE_MASTER':       // ライセンス
                    /*
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $license_master_column );
                    $key = $insert_data['LICENSE_ID'];
                    // 重複チェック
                    if (in_array($key, $license_master_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $license_master_key_array[] = $key;
                    $license_master_array = array_merge($license_master_array, array($insert_data));
                    */
                    break;

                // Add biblio info 2008/08/22 Y.Nakao --start--
                case 'REPOSITORY_BIBLIO_INFO':      // 書誌情報
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $biblio_info_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['BIBLIO_NO'];
                    // 重複チェック
                    if (in_array($key, $biblio_info_key_array)) {
                        continue;   // 重複するデータはスキップ
                    }
                    $biblio_info_key_array[] = $key;
                    $biblio_info_array = array_merge($biblio_info_array, array($insert_data));
                    break;
                // Add biblio info 2008/08/22 Y.Nakao --end--

                case 'REPOSITORY_FILE_PRICE':
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $file_price_column );
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $insert_data['ATTRIBUTE_ID'] . '_' . $insert_data['FILE_NO'];
                    // conflict
                    if (in_array($key, $file_price_key_array)) {
                        continue;   // skip conflict data
                    }
                    $file_price_key_array[] = $key;
                    $file_price_array = array_merge($file_price_array, array($insert_data));
                    break;
                case 'REPOSITORY_EDIT':
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $item_edit_column);
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'];
                    // conflict
                    if (in_array($key, $item_edit_key_array)) {
                        continue;   // skip conflict data
                    }
                    $item_edit_key_array[] = $key;
                    $insert_data["URL"] = $this->forXmlChangeDecode($vals[$row_cnt]['value']);
                    $edit_array = array_merge($edit_array, array($insert_data));
                    break;
                case 'REPOSITORY_FEEDBACK_MAILADDRESS':
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $feedback_mailaddress_column);
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_' . $this->forXmlChangeDecode($vals[$row_cnt]['value']);
                    if (in_array($key, $feedback_mailaddress_key_array)) {
                        continue;   // skip conflict data
                    }
                    $feedback_mailaddress_key_array[] = $key;
                    $insert_data["E_MAIL_ADDRESS"] = $this->forXmlChangeDecode($vals[$row_cnt]['value']);
                    $feedback_mailaddress_array = array_merge($feedback_mailaddress_array, array($insert_data));
                    break;
                case 'REPOSITORY_CNRI':
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $suffix_column);
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'] . '_20';
                    if (in_array($key, $cnri_key_array)) {
                        continue;   // skip conflict data
                    }
                    $cnri_key_array[] = $key;
                    $insert_data["CNRI"] = $this->forXmlChangeDecode($vals[$row_cnt]['value']);
                    $cnri_array = array_merge($cnri_array, array($insert_data));
                    break;
                case 'REPOSITORY_SELFDOI':
                    $insert_data = $this->pickupData($vals[$row_cnt]['attributes'], $selfdoi_column);
                    $key = $insert_data['ITEM_ID'] . '_' . $insert_data['ITEM_NO'];
                    if (in_array($key, $selfdoi_key_array)) {
                        continue;   // skip conflict data
                    }
                    $selfdoi_key_array[] = $key;
                    $selfdoi_array = array_merge($selfdoi_array, array($insert_data));
                    break;
                default:
                    $this->Session->setParameter("error_msg", "Not Found this parameter. Param : ".$this->forXmlChangeDecode($vals[$row_cnt]['tag']));
                    $this->writeLog("Not Found this parameter. Param : ".$this->forXmlChangeDecode($vals[$row_cnt]['tag'])."\n");
                    $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
                    return false;
            }
        }
        // item type info is not null
        if(count($item_type_array) > 0){
            // item type info
            array_push($array_item_type_data, array('item_type_array' => $item_type_array,
                                                    'item_attr_type_array' => $item_attr_type_array,
                                                    'item_attr_candidate_array' => $item_attr_candidate_array
                                                )
                        );
        }
        // Array $array_item_data is not null
        if(count($item_array) > 0){
            // item info
            array_push($array_item_data, array( 'item_array' => $item_array,
                                                'item_attr_array' => $item_attr_array,
                                                'personal_name_array' => $personal_name_array,
                                                'thumbnail_array' => $thumbnail_array ,
                                                'file_array' => $file_array,
                                                'attached_file_array' => $attached_file_array,
                                                'item_key_array' => $item_key_array,
                                                'item_type_key_array' => $item_type_key_array,
                                            //  'item_type_name_array' => $item_type_name_array,
                                                'item_attr_type_key_array' => $item_attr_type_key_array,
                                                'biblio_info_array' => $biblio_info_array,
                                                'file_price_array' => $file_price_array,
                                                'edit_array' => $edit_array,
                                                'feedback_mailaddress_array' => $feedback_mailaddress_array,
                                                'cnri_array' => $cnri_array,
                                                'selfdoi_array' => $selfdoi_array
                                        )
                        );

        }

        $ret_info = array('item_type' => $array_item_type_data, 'item' => $array_item_data);

        $this->writeLog("  XMLAnalysis completed.\n");
        $this->writeLog("-- End XMLAnalysis in ImportCommon class --\n");
        return true;
    }

    /*
     * insert file
     * $table           ：テーブル名
     * $column          ：カラム名
     * $path            ：アップロードするファイルパス
     * $where_clause    ：条件式
     */
    function registFile($table, $column, $path, $where_clause, &$error_msg){
        $this->writeLog("-- Start registFile in ImportCommon class --\n");
        $ret = file_exists($path);
        if($ret == false){
            $error_msg .= "Not found uplodad file.";
            $this->writeLog("  Failed: Not found uplodad file.\n");
            $this->writeLog("-- End registFile in ImportCommon class --\n");
            return false;
        }

        // insert DB
        $this->writeLog("  Update BLOB.\n");
        $this->writeLog("    TableName: ".$table."\n");
        $this->writeLog("    ColumnName: ".$column."\n");
        $this->writeLog("    FilePath: ".$path."\n");
        $this->writeLog("    WhereClause: ".$where_clause."\n");
        $ret = $this->Db->updateBlobFile($table, $column, $path, $where_clause, "LONGBLOB");
        if (!$ret){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );
            $error_msg .= "MySQL ERROR :　UPLOAD BLOB file.";
            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
            $this->writeLog("-- End registFile in ImportCommon class --\n");
            return false;
        }
        $this->writeLog("  registFile completed.\n");
        $this->writeLog("-- End registFile in ImportCommon class --\n");
        return true;
    }

    /*
     * データの切り出し
     */
    function pickupData($value, $column){
        $insert_data = array();
        for ($column_index = 0; $column_index < count($column); $column_index++){
            if(!isset($value[$column[$column_index]]))
            {
                $value[$column[$column_index]] = "";
            }
            $insert_data = array_merge($insert_data, array($column[$column_index] => $this->forXmlChangeDecode($value[$column[$column_index]])));
        }
        return $insert_data;
    }

    /*
     * 新規ID発行
     * $table_name　：　発行対象のテーブル名
     */
    function issueNewID( $table_name ){
        $this->writeLog("-- Start issueNewID in ImportCommon class --\n");
        $this->writeLog("  Get next seq. table name: ".$table_name."\n");
        $newID = $this->Db->nextSeq($table_name);
        // ID発行に失敗した場合
        if (count($newID) == 0){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
            //$DetailMsg = null;                              //詳細メッセージ文字列作成
            //s//printf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
            //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
            $error_msg .= "false issueNewID";
            //echo $error_msg . "<BR>";
            //echo $table_name."新規ID発行ミス";
            $this->writeLog("  Failed: get new ID.\n");
            $this->writeLog("-- End issueNewID in ImportCommon class --\n");
            throw $exception;
        }
        $this->writeLog("  Get new ID: ".$newID."\n");
        $this->writeLog("  issueNewID completed.\n");
        $this->writeLog("-- End issueNewID in ImportCommon class --\n");
        return $newID;
    }

    /*
     * アイテムとの依存関係チェック
     * $item_id ：アイテムID
     * $item_no ：アイテムNo
     */
    function itemDependentCheck( $item_id, $item_no, $key_array ){

        // 依存関係チェック：アイテムが存在することを確認する
        $key = $item_id . "_" . $item_no;
        if (!in_array($key, $key_array)) {
            // 新規アイテムタイプIDが未設定（ファイル内に存在しないアイテムIDを登録している）
            // エラー処理（未実装）
            // "アイテム依存関係チェック：指定されたKeyは存在しません。<br>";
            // 指定されたレコードは登録対象外とする
            return false;
        }
        return true;
    }

    /**
     * insert item type
     *
     */
    function itemtypeEntry($item_type_data, $tmp_dir, &$item_type_info, &$error_msg){
        $this->writeLog("-- Start itemtypeEntry in ImportCommon class --\n");
        // init
        $item_type_info = array();

        /////////////////////////////////////
        // check item type already insert
        /////////////////////////////////////
        for ($cnt = 0; $cnt < count($item_type_data); $cnt++){
            ////////////////////////////////
            // get item type info
            ////////////////////////////////
            $item_type = $item_type_data[$cnt]['item_type_array'];
            $item_attr_type = $item_type_data[$cnt]['item_attr_type_array'];
            $item_attr_candidate = $item_type_data[$cnt]['item_attr_candidate_array'];
            // Retrieval of existing item type(Retrieve it by the item type name)
            $select_query =  "SELECT * " .
                             "FROM ". DATABASE_PREFIX ."repository_item_type " .
                             "WHERE item_type_name LIKE ? ORDER BY item_type_name; ";
            $params_item_type = null;
            $params_item_type[] = $item_type[0]['ITEM_TYPE_NAME']."%";
            $this->writeLog("  Execute query: ".$select_query."\n");
            foreach($params_item_type as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            // Run query
            $ins_item_type = $this->Db->execute( $select_query, $params_item_type );
            if($ins_item_type === false){
                $error_msg .= "MySQL ERROR : For search item type.";
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
            $newID = "";
            // don't get data
            if (count($ins_item_type) == 0 ){
                //////////////////////////////////
                // insert new item type
                //////////////////////////////////
                $newID = $this->issueNewID("repository_item_type");
                // insert new item type
                $result = $this->insertItemtype($newID, $item_type, $item_attr_type, $item_attr_candidate, $error_msg);
                if($result === false){
                    $error_msg .= "false insertItemtype";
                    $this->writeLog("  Failed: insertItemtype.\n");
                    $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                    return false;
                }
                // set item type id and item type name
                array_push($item_type_info, array('item_type_id' => $newID,
                                                'item_type_name' => $item_type[0]['ITEM_TYPE_NAME'],
                                                'XML_item_type_id' => $item_type[0]['ITEM_TYPE_ID']
                                            )
                            );
            }
            else if ( $item_type === false ){
                // sql error
                $error_msg .= "MySQL ERROR : Not found item type. item type name : ".$item_type[0]['ITEM_TYPE_NAME'];
                $this->writeLog("  Failed: Not found item type '".$item_type[0]['ITEM_TYPE_NAME']."'\n");
                $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                return false;
            } else {
                // search same item type from xml
                for($ii=0;$ii<count($item_type_info);$ii++){
                    // confrict new insert item type
                    if($item_type_info[$ii]['XML_item_type_id'] == $item_type[0]['ITEM_TYPE_ID']){
                        array_push($item_type_info, array('item_type_id' => $item_type_info[$ii]['item_type_id'],
                                                        'item_type_name' => $item_type_info[$ii]['item_type_name'],
                                                        'XML_item_type_id' => $item_type_info[$ii]['XML_item_type_id']
                                                    )
                                    );
                        break;
                    }
                }
                if($ii == count($item_type_info)){
                    // item type name conflict.
                    // check metadata
                    $cnt_ins = count($ins_item_type) - 1;
                    $result = false;
                    for($jj=0; $jj<count($ins_item_type); $jj++) {
                        $result = $this->checkMetadata($ins_item_type[$jj]['item_type_id'], $item_attr_type, $item_attr_candidate);
                        if($result){
                            $cnt_ins  = $jj;
                            break;
                        }
                    }

                    if($result){
                        // this item type is already insert DB
                        array_push($item_type_info, array('item_type_id' => $ins_item_type[$cnt_ins]['item_type_id'],
                                                    'item_type_name' => $ins_item_type[$cnt_ins]['item_type_name'],
                                                    'XML_item_type_id' => $item_type[0]['ITEM_TYPE_ID']
                                                )
                                );
                    } else {
                        // item type isn't entry yet and same item type name is entry DB
                        // So insert does the item type as a copy
                        // Make copy name
                        $query = "SELECT item_type_name ".
                                 "FROM ". DATABASE_PREFIX ."repository_item_type ".
                                 "ORDER BY item_type_name; ";
                        // Run select
                        $this->writeLog("  Execute query: ". $query."\n");
                        $result = $this->Db->execute($query);
                        if($result === false) {
                            // DB is not data
                            $error_msg .= "MySQL ERROR : Not found one item type.";
                            // ROLLBACK
                            $this->failTrans();
                            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                            $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                            return false;
                        }
                        $this->writeLog("    Complete execute query.\n");
                        $cnt_copy = 2;
                        $copy_name = $item_type[0]['ITEM_TYPE_NAME'] . "_" . sprintf('%02d', $cnt_copy);
                        for($ii=0;$ii<count($result);$ii++){
                            if($copy_name == $result[$ii]["item_type_name"]){
                                $cnt_copy++;
                                $copy_name = $item_type[0]["ITEM_TYPE_NAME"] . "_" . sprintf('%02d', $cnt_copy);
                            }
                        }
                        $item_type[0]['ITEM_TYPE_NAME'] = $copy_name;
                        $newID = $this->issueNewID("repository_item_type");
                        // insert new item type
                        $result = $this->insertItemtype($newID, $item_type, $item_attr_type, $item_attr_candidate, $error_msg);
                        if($result === false){
                            $error_msg .= "ERROR : insertItemtype.";
                            $this->writeLog("  Failed: insert Itemtype.\n");
                            $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                            return false;
                        }
                        array_push($item_type_info, array('item_type_id' => $newID,
                                                    'item_type_name' => $item_type[0]['ITEM_TYPE_NAME'],
                                                    'XML_item_type_id' => $item_type[0]['ITEM_TYPE_ID']
                                                )
                                );
                    }
                }
            }
        }
        $this->writeLog("  itemtypeEntry completed.\n");
        $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
        return true;
    }

    /**
     * Insert an item type
     */
    function insertItemtype($newID, $item_type, $item_attr_type, $item_attr_candidate, &$error_msg){
        $this->writeLog("-- Start insertItemtype in ImportCommon class --\n");
        // get user id
        $user_id = $this->Session->getParameter("_user_id");

        // insert item type
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type(" .
                "item_type_id, " .
                "item_type_name, " .
                "item_type_short_name, " .
                "explanation, " .
                "mapping_info, " .
                "icon_name, ".
                "icon_mime_type, ".
                "icon_extension, ".
                "icon, ".
                "ins_user_id, " .
                "mod_user_id, " .
                "ins_date, " .
                "mod_date, " .
                "is_delete ) " .
                "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";

        $param_item_type = array();
        $param_item_type[] = intval( $newID );
        $param_item_type[] = $item_type[0]['ITEM_TYPE_NAME'];
        $param_item_type[] = $item_type[0]['ITEM_TYPE_SHORT_NAME'];
        $param_item_type[] = $item_type[0]['EXPLANATION'];
        $param_item_type[] = $item_type[0]['MAPPING_INFO'];
        $param_item_type[] = $item_type[0]['ICON_NAME'];
        $param_item_type[] = $item_type[0]['ICON_MIME_TYPE'];
        $param_item_type[] = $item_type[0]['ICON_EXTENSION'];
        $param_item_type[] = "";    //icon
        $param_item_type[] = $user_id;  // ins_user_id
        $param_item_type[] = $user_id;  // mod_user_id
        $param_item_type[] = $this->TransStartDate; // ins_date
        $param_item_type[] = $this->TransStartDate; // mod_date
        $param_item_type[] = 0;                     // is_delete
        // Run query
        $this->writeLog("  Execute query: ". $query."\n");
        foreach($param_item_type as $key => $val)
        {
            $this->writeLog("  Execute params ".$key.": ". $val."\n");
        }
        $result = $this->Db->execute( $query, $param_item_type );
        if($result === false){
            $error_msg .= "MySQL ERROR : INSERT item type. item_type_name : ".$item_type[0]['ITEM_TYPE_NAME'];
            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
            $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
            return false;
        }
        $this->writeLog("    Complete execute query.\n");

        // insert item type icon
        if( isset($item_type[0]['ICON_NAME'])
            && $item_type[0]['ICON_NAME'] != null
            && $item_type[0]['ICON_NAME'] != ""){
            $path = $tmp_dir . DIRECTORY_SEPARATOR . mb_convert_encoding($item_type[0]['ICON_NAME'], $this->encode, "auto");
            $where_clause = "item_type_id = " . intval( $newID );
            // insert icon BLOB
            $result = $this->registFile("repository_item_type", "icon", $path, $where_clause, $error_msg);
            if($result === false){
                $error_msg .=$error_msg." item type icon name : ".$item_type[0]['ICON_NAME'];
                $this->writeLog("  Failed: Regist itemtype icon.\n");
                return false;
            }
        }

        /////////////////////////////////////
        // insert item attr type
        /////////////////////////////////////
        $chk_file_price = 0;
        for ($ii = 0; $ii < count($item_attr_type); $ii++){
            // check XML item type id
            if($item_type[0]['ITEM_TYPE_ID'] != $item_attr_type[$ii]['ITEM_TYPE_ID']){
                // item type id Disagreement
                continue;
            }
            // check input type
            // when input_type is file_price, check file_price num.
            // when file_price num over 1, this item_type is error.
            if($item_attr_type[$ii]['INPUT_TYPE'] == "file_price"){
                $chk_file_price++;
                if($chk_file_price > 1){
                    $error_msg .="XML DATA ERROR : INSERT item type. item_type_name : ".$item_type[0]['ITEM_TYPE_NAME'].".";
                    $error_msg .= "Setting INPUT_TYPE file_price is not uniq.";
                    $this->writeLog("  Failed: Setting INPUT_TYPE file_price is not unique.\n");
                    $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                    return false;
                }
            }
            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_type(" .
                    "item_type_id, " .
                    "attribute_id, " .
                    "show_order, " .
                    "attribute_name, " .
                    "attribute_short_name, " .
                    "input_type, " .
                    "is_required, " .
                    "plural_enable, " .
                    "line_feed_enable, " .
                    "list_view_enable, " .
                    "hidden, " .    // add hidden 2009/02/18 A.Suzuki
                    "junii2_mapping, " .
                    "dublin_core_mapping, " .
                    "lom_mapping, " .// add "lom_mapping" 2013/01/29 A.Jin
                    "display_lang_type, " . // add "display_lang_type" 2009/07/23 A.Suzuki
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // バインド変数設定
            $param_item_attr_type = array();
            $param_item_attr_type[] = intval( $newID );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['ATTRIBUTE_ID'] );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['SHOW_ORDER'] );
            $param_item_attr_type[] = $item_attr_type[$ii]['ATTRIBUTE_NAME'];
            $param_item_attr_type[] = $item_attr_type[$ii]['ATTRIBUTE_SHORT_NAME'];
            $param_item_attr_type[] = $item_attr_type[$ii]['INPUT_TYPE'];
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['IS_REQUIRED'] );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['PLURAL_ENABLE'] );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['LINE_FEED_ENABLE'] );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['LIST_VIEW_ENABLE'] );
            $param_item_attr_type[] = intval( $item_attr_type[$ii]['HIDDEN'] );     // add hidden 2009/02/18 A.Suzuki
            $param_item_attr_type[] = $item_attr_type[$ii]['JUNII2_MAPPING'];
            $param_item_attr_type[] = $item_attr_type[$ii]['DUBLIN_CORE_MAPPING'];
            $param_item_attr_type[] = $item_attr_type[$ii]['LOM_MAPPING'];
            // add "display_lang_type" 2009/07/23 A.Suzuki --start--
            if($item_attr_type[$ii]['DISPLAY_LANG_TYPE']==null){
                $item_attr_type[$ii]['DISPLAY_LANG_TYPE'] = "";
            }
            $param_item_attr_type[] = $item_attr_type[$ii]['DISPLAY_LANG_TYPE'];
            // add "display_lang_type" 2009/07/23 A.Suzuki --end--
            $param_item_attr_type[] = $user_id; // ins_user_id
            $param_item_attr_type[] = $user_id; // mod_user_id
            $param_item_attr_type[] = $this->TransStartDate;    // ins_date
            $param_item_attr_type[] = $this->TransStartDate;    // mod_date
            $param_item_attr_type[] = 0;                        // is_delete
            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_item_attr_type as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_item_attr_type );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT item type attr. attribute name : ".$item_attr_type[$ii]['ATTRIBUTE_NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
        }

        // insert item attr candidate
        for ($jj = 0; $jj < count($item_attr_candidate); $jj++){
            // check XML item type id
            if($item_type[0]['ITEM_TYPE_ID'] != $item_attr_candidate[$jj]['ITEM_TYPE_ID']){
                // item type id Disagreement
                continue;
            }
            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_candidate(" .
                        "item_type_id, " .
                        "attribute_id, " .
                        "candidate_no, " .
                        "candidate_value, " .
                        "candidate_short_value, " .
                        "ins_user_id, " .
                        "mod_user_id, " .
                        "ins_date, " .
                        "mod_date, " .
                        "is_delete ) " .
                        "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            $param_item_attr_candidate = array();
            $param_item_attr_candidate[] = intval( $newID );
            $param_item_attr_candidate[] = intval( $item_attr_candidate[$jj]['ATTRIBUTE_ID'] );
            $param_item_attr_candidate[] = intval( $item_attr_candidate[$jj]['CANDIDATE_NO'] );
            $param_item_attr_candidate[] = $item_attr_candidate[$jj]['CANDIDATE_VALUE'];
            $param_item_attr_candidate[] = $item_attr_candidate[$jj]['CANDIDATE_SHORT_VALUE'];
            $param_item_attr_candidate[] = $user_id;    // ins_user_id
            $param_item_attr_candidate[] = $user_id;    // mod_user_id
            $param_item_attr_candidate[] = $this->TransStartDate;   // ins_date
            $param_item_attr_candidate[] = $this->TransStartDate;   // mod_date
            $param_item_attr_candidate[] = 0;                       // is_delete
            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_item_attr_candidate as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_item_attr_candidate );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT candidate. candidate value : ".$item_attr_candidate[$jj]['CANDIDATE_VALUE'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemtypeEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
        }
        $this->writeLog("  insertItemtype completed.\n");
        $this->writeLog("-- End insertItemtype in ImportCommon class --\n");
    }

    /**
     * Insert or Update an item
     */
    function itemEntry($array_item_data, $tmp_dir, &$array_item, $index_id, $item_type_info, $array_item_type_data, &$error_msg, &$item_id, &$detail_uri){
        $this->writeLog("-- Start itemEntry in ImportCommon class --\n");

        // get user_id
        $user_id = $this->Session->getParameter("_user_id");

        // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --start--
        $indice = array();
        $indice = $this->addPrivateTreeInPositionIndex($indice, $user_id);
        for($ii=0; $ii<count($indice); $ii++)
        {
            if(!is_numeric(array_search($indice[$ii]['index_id'], $index_id)))
            {
                array_push($index_id, $indice[$ii]['index_id']);
            }
        }
        $this->writeLog("  position index at : ". implode("|", $index_id)."\n");
        // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --end--

        // Check insert or update
        if($this->isUpdate($array_item_data))
        {
            $this->writeLog("  ImportType: UPDATE\n");
            // -------------------------
            // Update
            // -------------------------
            $updateAction = new SwordUpdate(
                                $this->Session, $this->Db, $this->TransStartDate, isset($this->logFh));
            $item_id = $updateAction->getUrlItemId($array_item_data["edit_array"][0]["URL"]);
            $item_no = 1;
            $detail_uri = "";
            // Update 2013/09/09 R.Matsuura (Add argument "item_type_info")
            $result = $updateAction->executeUpdate(
                            $array_item_data, $array_item_type_data, $tmp_dir, $user_id,
                            $item_id, $item_no, $array_item, $index_id, $item_type_info, $detail_uri, $error_msg);

            return $result;
        }

        // -------------------------
        // Insert
        // -------------------------
        $this->writeLog("  ImportType: INSERT\n");

        // Adjust attribute ID to show_order
        $this->adjustAttributeId($array_item_data, $item_type_info, $array_item_type_data["item_attr_type_array"]);
        
        // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --start--
        // validate each metadata attribute id of item type
        $this->validateItemTypeXmlData($item_type_info['item_type_id'], $array_item_type_data);
        // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --end--
        
        //////////////////////////////////
        // init
        //////////////////////////////////
        // set 1 item info
        $item_array = $array_item_data['item_array'];
        $item_attr_array = $array_item_data['item_attr_array'];
        $personal_name_array = $array_item_data['personal_name_array'];
        $thumbnail_array = $array_item_data['thumbnail_array'];
        $file_array = $array_item_data['file_array'];
        $attached_file_array = $array_item_data['attached_file_array'];
        $item_key_array = $array_item_data['item_key_array'];
        $item_type_key_array = $array_item_data['item_type_key_array'];
        //$item_type_name_array = $array_item_data['item_type_name_array'];
        $item_attr_type_key_array = $array_item_data['item_attr_type_key_array'];
        $biblio_info_array = $array_item_data['biblio_info_array'];
        $file_price_array = $array_item_data['file_price_array'];
        $feedback_mailaddress_array = $array_item_data['feedback_mailaddress_array'];
        $cnri_array = $array_item_data['cnri_array'];
        $selfdoi_array = $array_item_data['selfdoi_array'];
        $item_type_key_map = array();
        $item_key_map = array();

        $NameAuthority = new NameAuthority($this->Session, $this->Db);

        // 2008/05/13 FullText (S.Kawasaki) Start
        $item_id_fulltext = array();
        $item_no_fulltext = array();
        // 2008/05/13 FullText (S.Kawasaki) Start

        ///////////////////////////////////////////////////////////
        // insert 1 item
        ///////////////////////////////////////////////////////////
        // get item type id
        $item_type_id = intval($item_type_info['item_type_id']);
        $XML_item_type_id = $item_type_info['XML_item_type_id'];
        $item_id = $this->issueNewID("repository_item");
        $item_no = 1;

        // init status
        $pubDate = $this->validatePubDate($item_array[0]['SHOWN_DATE']);
        $item_array[0]['REVIEW_STATUS'] = -1;
        $item_array[0]['REVIEW_DATE'] = "";
        $item_array[0]['SHOWN_DATE'] = $pubDate[self::PUBDATE];
        $item_array[0]['REJECT_STATUS'] = 0;
        $item_array[0]['REJECT_DATE'] = "";
        $item_array[0]['REJECT_REASON'] = "";

        // Language
        $item_array[0]['LANGUAGE'] = $this->setLanguage($item_array[0]['LANGUAGE']);

        // Title
        $titleArray = $this->validateTitle($item_array[0]["TITLE"], $item_array[0]["TITLE_ENGLISH"], $item_array[0]['LANGUAGE']);
        $item_array[0]["TITLE"] = $titleArray[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
        $item_array[0]["TITLE_ENGLISH"] = $titleArray[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];

        ////////////////////////////
        // insert item table data
        ////////////////////////////
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item(" .
                "item_id, " .
                "item_no, " .
                "revision_no, " .
                "item_type_id, " .
                "prev_revision_no, " .
                "title, " .
                "title_english, " .     // add "title_english" 2009/07/23 A.Suzuki
                "language, " .
                "review_status, " .
                "review_date, " .
                "shown_status, " .
                "shown_date, " .
                "reject_status, " .
                "reject_date, " .
                "reject_reason, " .
                "serch_key, " .
                "serch_key_english, " . // add "serch_key_english" 2009/07/23 A.Suzuki
                "remark, " .
                "uri, ".
                "ins_user_id, " .
                "mod_user_id, " .
                "del_user_id, " .
                "ins_date, " .
                "mod_date, " .
                "del_date, " .
                "is_delete ) " .
                "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
        $param_item = array();
        $param_item[] = intval( $item_id );
        $param_item[] = intval( $item_array[0]['ITEM_NO'] );
        $param_item[] = intval( $item_array[0]['REVISION_NO'] );
        $param_item[] = $item_type_id;
        $param_item[] = intval( $item_array[0]['PREV_REVISION_NO'] );
        $param_item[] = $item_array[0]["TITLE"];
        $param_item[] = $item_array[0]["TITLE_ENGLISH"];
        $param_item[] = $item_array[0]['LANGUAGE'];
        $param_item[] = $item_array[0]['REVIEW_STATUS'];
        $param_item[] = $item_array[0]['REVIEW_DATE'];
        $param_item[] = 0;
        $param_item[] = $item_array[0]['SHOWN_DATE'];
        $param_item[] = $item_array[0]['REJECT_STATUS'];
        $param_item[] = $item_array[0]['REJECT_DATE'];
        $param_item[] = $item_array[0]['REJECT_REASON'];
        $param_item[] = $item_array[0]['SERCH_KEY'];
        // add "serch_key_english" 2009/07/23 A.Suzuki --start--
        if($item_array[0]['SERCH_KEY_ENGLISH']==null){
            $item_array[0]['SERCH_KEY_ENGLISH'] = "";
        }
        $param_item[] = $item_array[0]['SERCH_KEY_ENGLISH'];
        // add "serch_key_english" 2009/07/23 A.Suzuki --end--
        $param_item[] = $item_array[0]['REMARK'];
        // Add detail uri 2008/11/13 Y.Nakao --start--
        $detail_uri = BASE_URL . "/?action=repository_uri&item_id=". $item_id;
        $param_item[] = $detail_uri;    // uri
        // Add detail uri 2008/11/13 Y.Nakao --end--
        // Common Column
        $param_item[] = $user_id;   // ins_user_id
        $param_item[] = $user_id;   // mod_user_id
        $param_item[] = "";         // del_user_id
        $param_item[] = $this->TransStartDate;  // ins_date
        $param_item[] = $this->TransStartDate;  // mod_date
        $param_item[] = "";                     // del_date
        $param_item[] = 0;                      // is_delete
        // Run query
        $this->writeLog("  Execute query: ". $query."\n");
        foreach($param_item as $key => $val)
        {
            $this->writeLog("  Execute params ".$key.": ". $val."\n");
        }
        $result = $this->Db->execute( $query, $param_item );
        if($result === false){
            $error_msg .="MySQL ERROR : INSERT ITEM. Title : ".$item_array[0]["TITLE"];
            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
            $this->writeLog("-- End itemEntry in ImportCommon class --\n");
            return false;
        }
        $this->writeLog("    Complete execute query.\n");

        $this->writeLog("    Set Y-handle suffix.\n");
        $this->getRepositoryHandleManager();
        if(!$this->repositoryHandleManager->setSuffix($item_array[0]["TITLE"], $item_id, $item_no))
        {
            $this->writeLog("    Failed set Y-handle suffix.\n");
        }
        else
        {
            $this->writeLog("    Complete set Y-handle suffix.\n");
        }

        array_push($array_item,
                 array(
                    "title" => $item_array[0]["TITLE"],
                    "title_english" => $item_array[0]["TITLE_ENGLISH"],
                    "item_type" => $item_type_info['item_type_name'],
                    "review_status" => $item_array[0]['REVIEW_STATUS'],
                    "mode" => "insert",
                    "status" => "failed"
                 )
        );

        // 2008/05/13 FullTextセット対応 (S.Kawasaki) Start
        array_push($item_id_fulltext, intval( $item_id ));
        array_push($item_no_fulltext, intval( $item_array[0]['ITEM_NO'] ));
        // 2008/05/13 FullTextセット対応 (S.Kawasaki) End

        // 所属インデックスの登録（画面で指定されたインデックスとアイテムを紐付ける）
        $index_id = array_unique($index_id);
        for ($index_count = 0; $index_count < count($index_id); $index_count++){
            // Fix check index_id Y.Nakao 2013/06/07 --start--
            if(!$this->existsIndex( intval( $index_id[$index_count] ) ))
            {
                continue;
            }
            // Fix check index_id Y.Nakao 2013/06/07 --end--

            //所属インデックスごとの custom_sort_orderMAX値の取得 2012/12/26 A.Jin --start--
            $max_sort_order = 0;
            $query = "SELECT MAX(custom_sort_order) AS MAX_SORT_ORDER ".
                     "FROM ".DATABASE_PREFIX ."repository_position_index ".
                     "WHERE index_id = ? GROUP BY index_id;";
            $param_index = array();
            $param_index[] = intval( $index_id[$index_count] );
            $max_result = $this->Db->execute( $query, $param_index );
            if($max_result === false){
                $error_msg .="MySQL ERROR : SELECT Position index.";
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            } else if(count($max_result) == 1 ){
                //追加の場合
                $max_sort_order = $max_result[0]['MAX_SORT_ORDER'];
            }
            //所属インデックスごとの custom_sort_orderMAX値の取得 2012/12/26 A.Jin --end--

            // 所属インデックスの登録
            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_position_index(" .
                            "item_id, " .
                            "item_no, " .
                            "index_id ," .
                            "custom_sort_order ," .
                            "ins_user_id, " .
                            "mod_user_id, " .
                            "del_user_id, ".
                            "ins_date, " .
                            "mod_date, " .
                            "del_date, " .
                            "is_delete ) " .
                            "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // パラメータ設定
            $param_index = array();
            $param_index[] = intval( $item_id );
            $param_index[] = intval( $item_array[0]['ITEM_NO'] );
            $param_index[] = intval( $index_id[$index_count] );
            $param_index[] = $max_sort_order+1;  // Add custom_sort_order  A.Jin 2012/12/26
            // 共通項目
            // user_id Correspondence to string Y.Nakao --start--
            $param_index[] = $user_id;  // ins_user_id
            $param_index[] = $user_id;  // mod_user_id
            // user_id Correspondence to string Y.Nakao --end--
            $param_index[] = "";                    // del_user_id
            $param_index[] = $this->TransStartDate; // ins_date
            $param_index[] = $this->TransStartDate; // mod_date
            $param_index[] = "";                    // del_date
            $param_index[] = 0;                     // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_index as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_index );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT Position index.";
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
            // 所属する公開中インデックスのコンテンツ数を増やす K.Matsuo 2013/06/04
            $this->addPrivateContents(intval( $index_id[$index_count]));
        }

        // input_type, plural_enable をattribute_idから参照する配列を作る
        $inputTypeList = array();
        $pluralEnableList = array();
        $tmpAttrId = 0;
        $pluralFlag = false;
        foreach($array_item_type_data["item_attr_type_array"] as $itemAttrType)
        {
            $attrId = intval($itemAttrType["ATTRIBUTE_ID"]);
            $inputTypeList[$attrId] = $itemAttrType["INPUT_TYPE"];
            $pluralEnableList[$attrId] = $itemAttrType["PLURAL_ENABLE"];
        }

        // candidate をattribute_idから参照する配列を作る
        $candidateList = array();
        foreach($array_item_type_data["item_attr_candidate_array"] as $itemAttrCandidate)
        {
            $attrId = intval($itemAttrCandidate["ATTRIBUTE_ID"]);
            $candidateValue = $itemAttrCandidate["CANDIDATE_VALUE"];
            if(!array_key_exists($attrId, $candidateList))
            {
                $candidateList[$attrId] = array();
            }
            array_push($candidateList[$attrId], $candidateValue);
        }

        // アイテム属性の登録
        for ($cnt = 0; $cnt < count($item_attr_array); $cnt++)
        {
            // アイテム属性タイプとの依存関係をチェックする
            //
            if ( $item_attr_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id ){
                $error_msg .= "warning:insert item attr[item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $item_attr_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert item attr[item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($item_attr_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // attribute_no
            $attrNo = intval($item_attr_array[$cnt]["ATTRIBUTE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            // input_type
            $inputType = $inputTypeList[$attrId];
            $attrValue = str_replace("\\n", "\n", $item_attr_array[$cnt]["ATTRIBUTE_VALUE"]);

            if($inputType == RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX ||
               $inputType == RepositoryConst::ITEM_ATTR_TYPE_SELECT ||
               $inputType == RepositoryConst::ITEM_ATTR_TYPE_RADIO)
            {
                // 候補チェック: 一致しなければ空にする
                if(array_search($attrValue, $candidateList[$attrId])===false)
                {
                    $attrValue = "";
                }
                if($inputType == RepositoryConst::ITEM_ATTR_TYPE_RADIO && strlen($attrValue)==0)
                {
                    // ラジオボタンなら先頭の選択肢を設定する
                    $attrValue = $candidateList[$attrId][0];
                }
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_DATE)
            {
                $attrValue = RepositoryOutputFilter::date($attrValue);
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_LINK)
            {
                $attrValue = $this->validateLink($attrValue);
            }

            // Check attribute value
            if(strlen(RepositoryOutputFilter::string($attrValue)) == 0)
            {
                continue;
            }

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "attribute_no, " .
                    "attribute_value, " .
                    "item_type_id, " .
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // バインド変数設定
            $param_item_attr = array();
            $param_item_attr[] = intval( $item_id );
            $param_item_attr[] = intval( $item_attr_array[$cnt]['ITEM_NO'] );
            $param_item_attr[] = $attrId;
            $param_item_attr[] = $attrNo;
            $param_item_attr[] = $attrValue;
            $param_item_attr[] = $item_type_id;

            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_item_attr[] = $user_id;  // ins_user_id
            $param_item_attr[] = $user_id;  // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_item_attr[] = $this->TransStartDate;     // ins_date
            $param_item_attr[] = $this->TransStartDate;     // mod_date
            $param_item_attr[] = 0;                         // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_item_attr as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_item_attr );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT Item attr. Attribute value : ".$attrValue;
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
            $pluralFlag = true;
        }
        // 氏名の登録
        for ($cnt = 0; $cnt < count($personal_name_array); $cnt++){

            // アイテム属性タイプとの依存関係をチェックする
            //
            if ( $personal_name_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id){
                $error_msg .= "warning:insert personal name[item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $personal_name_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert personal name[item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($personal_name_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // personal_name_no
            $attrNo = intval($personal_name_array[$cnt]["PERSONAL_NAME_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            $family_ruby = "";
            $name_ruby = "";
            //Add PHP-Notice 対応 2013/01/18 A.Jin --start--
            if(array_key_exists('FAMILY_RUBY', $personal_name_array[$cnt])){
                $family_ruby = $personal_name_array[$cnt]['FAMILY_RUBY'];
            }
            if(array_key_exists('NAME_RUBY', $personal_name_array[$cnt])){
                $name_ruby = $personal_name_array[$cnt]['NAME_RUBY'];
            }
            //Add PHP-Notice 対応 2013/01/18 A.Jin --end--

            // Check value
            if(strlen(RepositoryOutputFilter::string($personal_name_array[$cnt]["FAMILY"])) == 0 &&
               strlen(RepositoryOutputFilter::string($personal_name_array[$cnt]["NAME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($family_ruby)) == 0 &&
               strlen(RepositoryOutputFilter::string($name_ruby)) == 0 &&
               strlen(RepositoryOutputFilter::string($personal_name_array[$cnt]["E_MAIL_ADDRESS"])) == 0)
            {
                continue;
            }

             // get display_lang_type
            $query = "SELECT display_lang_type FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                     "WHERE item_type_id = ? ".
                     "AND attribute_id = ?;";
            $params = array();
            $params[] = $item_type_id;
            $params[] = $attrId;
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($params as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute($query, $params);
            if($result[0]["display_lang_type"]==null){
                $language = "";
            } else {
                $language = $result[0]["display_lang_type"];
            }
            $this->writeLog("    Complete execute query.\n");

            // Regist Name Authority
            $prefix_name_array = explode("|", $personal_name_array[$cnt]['PREFIX_NAME']);
            $suffix_array = explode("|", $personal_name_array[$cnt]['SUFFIX']);
            $external_author_id = array();
            for($ii=0; $ii<count($prefix_name_array); $ii++){
                $prefix_name = $prefix_name_array[$ii];
                if($prefix_name!=""){
                    // Search same prefix_name in DB
                    $suffix = $suffix_array[$ii];
                    $prefix_id = $NameAuthority->getExternalAuthorIdPrefixId($prefix_name);
                    if($prefix_id === false) {
                        $error_msg .="MySQL ERROR : INSERT name authority prefix. Famliy : ".
                                    $personal_name_array[$cnt]['FAMILY']. " Name : ".
                                    $personal_name_array[$cnt]['NAME'];
                        $this->writeLog("  Failed: getExternalAuthorIdPrefixId.\n");
                        $this->writeLog("    Famliy: ".$personal_name_array[$cnt]['FAMILY']."\n");
                        $this->writeLog("    Name: ".$personal_name_array[$cnt]['NAME']."\n");
                        $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                        return false;
                    }
                    if($prefix_id == 0){
                        // No hit -> Regist new prefix
                        $prefix_id = $NameAuthority->addExternalAuthorIdPrefix($prefix_name);
                        if($prefix_id===false){
                            $error_msg .="MySQL ERROR : INSERT name authority prefix. Famliy : ".
                                        $personal_name_array[$cnt]['FAMILY']. " Name : ".
                                        $personal_name_array[$cnt]['NAME'];
                            $this->writeLog("  Failed: addExternalAuthorIdPrefixId.\n");
                            $this->writeLog("    Famliy: ".$personal_name_array[$cnt]['FAMILY']."\n");
                            $this->writeLog("    Name: ".$personal_name_array[$cnt]['NAME']."\n");
                            $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                            return false;
                        }
                    }
                    $extAuthorIds = array(
                                            "prefix_id" => $prefix_id,
                                            "suffix" => $suffix
                                        );
                    array_push($external_author_id, $extAuthorIds);
                }
            }
            // Add e-person 2013/10/24 R.Matsuura --start--
            // when exist mail address
            if(strlen($personal_name_array[$cnt]['E_MAIL_ADDRESS']) > 0) {
                $extAuthorIds = array(
                                        "prefix_id" => '0',
                                        "suffix" => $personal_name_array[$cnt]['E_MAIL_ADDRESS']
                                    );
                array_push($external_author_id, $extAuthorIds);
            }
            // Add e-person 2013/10/24 R.Matsuura --end--

            $metadata = array(
                                "family" => $personal_name_array[$cnt]['FAMILY'],
                                "name" => $personal_name_array[$cnt]['NAME'],
                                "family_ruby" => $family_ruby,
                                "name_ruby" => $name_ruby,
                                "e_mail_address" => $personal_name_array[$cnt]['E_MAIL_ADDRESS'],
                                "author_id" => 0,
                                "language" => $language,
                                "external_author_id" => $external_author_id
                            );
            $author_id = $NameAuthority->entryNameAuthority($metadata, $error_msg);
            if($author_id === false){
                $error_msg .="MySQL ERROR : INSERT name authority. Famliy : ".
                                $personal_name_array[$cnt]['FAMILY']. " Name : ".
                                $personal_name_array[$cnt]['NAME'];
                $this->writeLog("  Failed: entryNameAuthority.\n");
                $this->writeLog("    Famliy: ".$personal_name_array[$cnt]['FAMILY']."\n");
                $this->writeLog("    Name: ".$personal_name_array[$cnt]['NAME']."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_personal_name(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "personal_name_no, " .
                    "family, " .
                    "name, " .
                    "family_ruby, " .
                    "name_ruby, " .
                    "e_mail_address, " .
                    "item_type_id, " .
                    "author_id, " .
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // バインド変数設定
            $param_personal_name = array();
            $param_personal_name[] = intval( $item_id );
            $param_personal_name[] = intval( $personal_name_array[$cnt]['ITEM_NO'] );
            $param_personal_name[] = $attrId;
            $param_personal_name[] = $attrNo;
            $param_personal_name[] = $personal_name_array[$cnt]['FAMILY'];
            $param_personal_name[] = $personal_name_array[$cnt]['NAME'];
            $param_personal_name[] = $family_ruby;
            $param_personal_name[] = $name_ruby;
            $param_personal_name[] = $personal_name_array[$cnt]['E_MAIL_ADDRESS'];
            $param_personal_name[] = $item_type_id;
            $param_personal_name[] = $author_id;  // author_id

            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_personal_name[] = $user_id;  // ins_user_id
            $param_personal_name[] = $user_id;  // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_personal_name[] = $this->TransStartDate; // ins_date
            $param_personal_name[] = $this->TransStartDate; // mod_date
            $param_personal_name[] = 0;                     // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_personal_name as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_personal_name );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT personal name. Famliy : ".
                            $personal_name_array[$cnt]['FAMILY']. " Name : ".
                            $personal_name_array[$cnt]['NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
            $pluralFlag = true;
        }

        // サムネイルの登録
        for ($cnt = 0; $cnt < count($thumbnail_array); $cnt++){

            // アイテム属性タイプとの依存関係をチェックする
            //
            if ( $thumbnail_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id){
                $error_msg .= "warning:insert thumbnal [item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $thumbnail_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert thumbnal [item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($thumbnail_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // file_no
            $attrNo = intval($thumbnail_array[$cnt]["FILE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            // Check file_name
            if(strlen(RepositoryOutputFilter::string($thumbnail_array[$cnt]["FILE_NAME"])) == 0)
            {
                continue;
            }

            // ファイルの存在をチェックする
            $path = "";
            $path = $tmp_dir . DIRECTORY_SEPARATOR . mb_convert_encoding($thumbnail_array[$cnt]['FILE_NAME'], $this->encode, "auto");
            if(!file_exists($path)){
                $error_msg .= "warning:file not exists ".$thumbnail_array[$cnt]['FILE_NAME'];
                continue;
            }
            $path = "";

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_thumbnail(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "file_no, " .
                    "file_name, " .
                    "show_order, " .
                    "mime_type , " .
                    "extension, " .
                    "file, ".
                    "item_type_id, " .
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // DBテーブル接頭語追加 Y.Nakao 2008/06/17 --end--
            // バインド変数設定
            $param_thumbnail = array();
            $param_thumbnail[] = intval( $item_id );
            $param_thumbnail[] = intval( $thumbnail_array[$cnt]['ITEM_NO'] );
            $param_thumbnail[] = $attrId;
            $param_thumbnail[] = $attrNo;
            $param_thumbnail[] = $thumbnail_array[$cnt]['FILE_NAME'];
            $param_thumbnail[] = $attrNo;
            $param_thumbnail[] = $thumbnail_array[$cnt]['MIME_TYPE'];
            $param_thumbnail[] = $thumbnail_array[$cnt]['EXTENSION'];
            $param_thumbnail[] = ""; // file(BLOB)
            $param_thumbnail[] = $item_type_id;

            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_thumbnail[] = $user_id;  // ins_user_id
            $param_thumbnail[] = $user_id;  // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_thumbnail[] = $this->TransStartDate; // ins_date
            $param_thumbnail[] = $this->TransStartDate; // mod_date
            $param_thumbnail[] = 0;                     // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_thumbnail as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_thumbnail );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT thumbnail. file name : ".$thumbnail_array[$cnt]['FILE_NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");

            // 登録するファイルの付加情報
            $path = $tmp_dir . DIRECTORY_SEPARATOR . mb_convert_encoding($thumbnail_array[$cnt]['FILE_NAME'], $this->encode, "auto");
            $where_clause = "item_id = " . intval( $item_id ) .
            " AND item_no = " . intval( $thumbnail_array[$cnt]['ITEM_NO'] ) .
            " AND attribute_id = " . $attrId.
            " AND file_no = " . $attrNo;

            // ファイルの登録
            $ret = $this->registFile("repository_thumbnail", "file", $path, $where_clause, $error_msg);
            if($ret == false){
                $error_msg .= "thumbnail file name : ".$thumbnail_array[$cnt]['FILE_NAME'];
                //$this->Session->setParameter("error_msg", $error_msg." error_msg : ".$this->Db->ErrorMsg());
                $this->writeLog("  Failed: registFile.\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $pluralFlag = true;
        }
        // ファイルの登録
        // Add separate file from DB 2009/04/21 Y.Nakao --start--
        // コンテンツファイル本体リネーム
        // Move upload file and rename.
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
            if( !(file_exists($contents_path)) ){
                mkdir ( $contents_path, 0777);
            }
        }
        // check directory exists
        if( file_exists($contents_path) ){
            // check this folder write right.
            $ex_file = fopen ($contents_path.'/test.txt', "w");
            if( $ex_file === false ){
                // folder is not find, file save at default directory
                $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
                if( !(file_exists($contents_path)) ){
                    mkdir ( $contents_path, 0777);
                }
                chmod($contents_path, 0777 );
            } else {
                fclose($ex_file);
                unlink($contents_path.'/test.txt');
            }
        } else {
            // folder is not find, file save at default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
            if( !(file_exists($contents_path)) ){
                mkdir ( $contents_path, 0777);
            }
            chmod($contents_path, 0777 );
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --end--
        // ファイルをリネーム 2013/03/18 K.Matsuo --start--
        $res_dir = opendir($tmp_dir );
        while( $file_name = readdir( $res_dir ) ){
            if($file_name == "." || $file_name == "..")
            {
                continue;
            }
            $newFileName = mb_convert_encoding($file_name, $this->encode, 'auto');
            rename($tmp_dir.DIRECTORY_SEPARATOR.$file_name, $tmp_dir.DIRECTORY_SEPARATOR.$newFileName);
        }

        closedir( $res_dir );
        // ファイルをリネーム 2013/03/18 K.Matsuo --end--
        // Add multimedia support 2012/08/27 T.Koyasu -end-
        for ($cnt = 0; $cnt < count($file_array); $cnt++){

            // アイテム属性タイプとの依存関係をチェックする
            //
            if ( $file_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id){
                $error_msg .= "warning:insert file [item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $file_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert file [item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($file_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // file_no
            $attrNo = intval($file_array[$cnt]["FILE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            // Check file_name
            if(strlen(RepositoryOutputFilter::string($file_array[$cnt]["FILE_NAME"])) == 0)
            {
                continue;
            }

            // ファイルの存在をチェックする
            $path = "";
            $path = $tmp_dir . DIRECTORY_SEPARATOR. mb_convert_encoding($file_array[$cnt]['FILE_NAME'], $this->encode, "auto");
            if(!file_exists($path)){
                $error_msg .= "warning:file not exists ".$file_array[$cnt]['FILE_NAME'];
                $this->writeLog("  warning:file not exists ".$file_array[$cnt]['FILE_NAME']."\n");
                $this->writeLog("  file path: ".$path."\n");
                continue;
            }

            // ファイルをリネーム 2013/03/15 K.Matsuo --start--
            $now = new Date();
            $rename_file_name = $now->getDate(DATE_FORMAT_TIMESTAMP).".".$file_array[$cnt]['EXTENSION'];
            copy($path, $tmp_dir . DIRECTORY_SEPARATOR . $rename_file_name);
            $file_array[$cnt]['RENAME'] = $rename_file_name;
            // ファイルをリネーム 2013/03/15 K.Matsuo --end--

            $path = "";

            // 依存関係チェック：ライセンス
            /*
            if (!in_array($file_array[$cnt]['LICENSE_ID'], $license_master_key_array)) {
                // ライセンスIDが未設定（ファイル内に存在しないライセンスIDを登録している）
                // エラー処理（未実装）
                //$this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                // "ライセンス依存関係チェック：指定されたライセンスIDは存在しません。<br>";
                // 指定されたアイテムは登録対象外とする
                continue;
            }
            */

            // PDFのプレビュー化処理を追加 2008/07/22 Y.Nakao --start--
            // 外部コマンドをDBから取得する 2008/08/07 Y.Nakao --start--
            // Insert実行前に、uploadファイルがPDFならばサムネイルを作成する
            $prev_flg = sprintf("false"); // サムネイルができたかどうか
            if($file_array[$cnt]['EXTENSION'] == "pdf"){
                // ファイル格納先+ファイル名
                // ファイル名を変更 2013/03/18 K.Matsuo
                $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

                // Fix 2013/10/28 R.Matsuura --start--
                // Thumbnail generate conditions
                $thumbnailGenerateCondition = 0;
                // popplerのパスを取得
                $query = "SELECT `param_value` ".
                         "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                         "WHERE `param_name` = 'path_poppler';";
                $poppler_path = $this->Db->execute($query);
                if ($poppler_path === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    $this->failTrans();             //トランザクション失敗を設定(ROLLBACK)
                    // delete upload file
                    if(file_exists($path)){
                        unlink($path);
                    }
                    return false;
                }
                if(strlen($poppler_path[0]['param_value']) >= 1
                   && (file_exists($poppler_path[0]['param_value']."pdfinfo.exe")
                       || file_exists($poppler_path[0]['param_value']."pdfinfo")))
                {
                    $thumbnailGenerateCondition++;
                }
                // Fix 2013/10/28 R.Matsuura --end--


                // コマンドのパスを取得
                $query = "SELECT `param_value` ".
                         "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                         "WHERE `param_name` = 'path_ImageMagick';";
                $this->writeLog("  Execute query: ". $query."\n");
                $cmd_path = $this->Db->execute($query);
                if ($cmd_path === false) {
                    $error_msg .="MySQL ERROR : Not found ImageMagick command path.";
                    $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                    $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                    return false;
                }
                $this->writeLog("    Complete execute query.\n");
                if(strlen($cmd_path[0]['param_value']) >= 1
                   && (file_exists($cmd_path[0]['param_value']."convert")
                       || file_exists($cmd_path[0]['param_value']."convert.exe"))){
                    // Add Fix 2013/10/28 R.Matsuura --start--
                    $thumbnailGenerateCondition++;
                }
                
                // Add check PDF image format not "" = JPEG2000. Y.Nakao 2014/08/20 --start--
                $catoutput = array();
                exec("cat ".$path." | grep --text /JPXDecode", $catoutput);
                if(count($catoutput) == 0)
                {
                    $thumbnailGenerateCondition++;
                }
                unset($catoutput);
                // Add check PDF image format not "" = JPEG2000. Y.Nakao 2014/08/20 --end--
                
                if($thumbnailGenerateCondition == 3)
                {
                    // コマンド  pfdinfoコマンドパス/pdfinfo PDFファイルパス
                    $cmd_getVertical = sprintf($poppler_path[0]['param_value'] . "pdfinfo " . $path. " | gawk '/Page size/ {print $5}'");
                    $cmd_getHorizontal = sprintf($poppler_path[0]['param_value'] . "pdfinfo " . $path. " | gawk '/Page size/ {print $3}'");
                    // PDFファイルのページサイズを取得
                    $pdfPagesize = array();
                    exec($cmd_getVertical, $pdfPagesize);
                    exec($cmd_getHorizontal, $pdfPagesize);
                    if($pdfPagesize[0] >= 1 && $pdfPagesize[1] >= 1){
                        if($pdfPagesize[1] < $pdfPagesize[0]){
                            // 縦長
                            $cmd = sprintf("\"". $cmd_path[0]['param_value']. "convert\" -quality 100 -density 200x200 -resize 200x ". $path. "[0] ". $path. ".png");
                        } else {
                            // 横長
                            $cmd = sprintf("\"". $cmd_path[0]['param_value']. "convert\" -quality 100 -density 200x200 -resize x280 ". $path. "[0] ". $path. ".png");
                        }
                        exec($cmd);
                        // Fix 2013/10/28 R.Matsuura --end--
                    }
                    if(file_exists($path.".png")){
                        // サムネイル作成OK
                        $prev_flg = sprintf("true");
                    }
                }
            }
            // 外部コマンドをDBから取得する 2008/08/07 Y.Nakao --end--
            // PDFのプレビュー化処理を追加 2008/07/22 Y.Nakao --end--
            // create image-file thumbnail using gd 2010/02/16 K.Ando --start--
            else if(strcmp($file_array[$cnt]['MIME_TYPE'], "image/bmp") == 0)
            {
                // file upload path
                // ファイル名を変更 2013/03/18 K.Matsuo
                $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

                $image = $this->imagecreatefrombmp($path); //read bmp file

                $result = $this->createThumbnailImage($image , $path.".png");
                imagedestroy ($image);

                if(file_exists($path.".png")){
                    // creating thumbnail is succeed
                    $prev_flg = sprintf("true");
                }

            }
            else if(strcmp($file_array[$cnt]['MIME_TYPE'],"image/gif" )== 0)
            {
                // file upload path
                // ファイル名を変更 2013/03/18 K.Matsuo
                $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

                $image = ImageCreateFromGIF($path); //read gif file
                $result = $this->createThumbnailImage($image , $path.".png");
                imagedestroy ($image);

                if(file_exists($path.".png")){
                    // creating thumbnail is succeed
                    $prev_flg = sprintf("true");
                }

            }
            else if(strcmp($file_array[$cnt]['MIME_TYPE'], "image/jpeg") == 0
            || strcmp($file_array[$cnt]['MIME_TYPE'], "image/pjpeg")== 0)
            {
                // file upload path
                // ファイル名を変更 2013/03/18 K.Matsuo
                $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

                $image = ImageCreateFromJPEG($path); //read jpeg file
                $result = $this->createThumbnailImage($image , $path.".png");
                imagedestroy ($image);

                if(file_exists($path.".png")){
                    // creating thumbnail is succeed
                    $prev_flg = sprintf("true");
                }
            }
            else if(strcmp($file_array[$cnt]['MIME_TYPE'],"image/png")== 0|| strcmp($file_array[$cnt]['MIME_TYPE'] , "image/x-png")== 0)
            {
                // file upload path
                // ファイル名を変更 2013/03/18 K.Matsuo
                $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

                $image = ImageCreateFromPNG($path); //read png file
                $result = $this->createThumbnailImage($image , $new_image);

                $new_image = null;
                $result = $this->createThumbnailImage($image , $path.".png");
                imagedestroy ($image);

                if(file_exists($path.".png")){
                    // creating thumbnail is succeed
                    $prev_flg = sprintf("true");
                }
            }
            // create image-file thumbnail using gd 2010/02/16 K.Ando --end--

            // Add separate file from DB 2009/04/21 Y.Nakao --start--
            // ファイル名を変更 2013/03/18 K.Matsuo
            $path = $tmp_dir. DIRECTORY_SEPARATOR. $file_array[$cnt]['RENAME'];

            $file_path = $contents_path.DIRECTORY_SEPARATOR.
                        $item_id.'_'.
                        $attrId.'_'.
                        $attrNo.'.'.
                        $file_array[$cnt]['EXTENSION'];
            if(file_exists($file_path)){
                unlink($file_path);
            }
            copy($path, $file_path);
            // Add separate file from DB 2009/04/21 Y.Nakao --end--

            // display_type
            $displayType = $this->validateFileDisplayType($file_array[$cnt]["DISPLAY_TYPE"]);

            // license
            $license = $this->validateFileLicense($file_array[$cnt]["LICENSE_ID"], $file_array[$cnt]["LICENSE_NOTATION"]);

            // ファイルおよびフラッシュの公開日
            $filePubDateArray = $this->validatePubDate($file_array[$cnt]['PUB_DATE']);
            $filePubDate = $filePubDateArray[self::PUBDATE];
            $flashPubDate = "";
            if($displayType == RepositoryConst::FILE_DISPLAY_TYPE_FLASH)
            {
                $flashPubDateArray = $this->validatePubDate($file_array[$cnt]['FLASH_PUB_DATE']);
                $flashPubDate = $flashPubDateArray[self::PUBDATE];
            }

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_file(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "file_no, " .
                    "file_name , " .
                    "display_name , " .
                    "display_type , " .
                    "show_order , " .
                    "mime_type , " .
                    "extension, " .
                    "license_id, " .
                    "license_notation, " .
                    "pub_date, " .
                    "flash_pub_date, " .
                    "item_type_id, " .
                    "prev_id, ".
                    "file_prev, ".
                    "file_prev_name, ".
                    "browsing_flag, ".
                    "cover_created_flag, ".
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // DBテーブル接頭語追加 Y.Nakao 2008/06/17 --end--
            // バインド変数設定
            $param_file = array();
            $param_file[] = intval( $item_id );
            $param_file[] = intval( $file_array[$cnt]['ITEM_NO'] );
            $param_file[] = $attrId;
            $param_file[] = $attrNo;
            $param_file[] = $file_array[$cnt]['FILE_NAME'];
            $param_file[] = $file_array[$cnt]['DISPLAY_NAME'];
            $param_file[] = $displayType;
            $param_file[] = $attrNo;
            $param_file[] = $file_array[$cnt]['MIME_TYPE'];
            $param_file[] = $file_array[$cnt]['EXTENSION'];
            $param_file[] = $license[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID];
            $param_file[] = str_replace("\\n", "\n", $license[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION]);
            $param_file[] = $filePubDate;
            $param_file[] = $flashPubDate;
            $param_file[] = $item_type_id;
            $param_file[] = ""; // prev_id
            $param_file[] = ""; // file_prev(BLOB)
            if($prev_flg == "true"){
                // PDFの場合、プレビュー用の名前を事前登録する。
                // プレビューの画像をアップロードした後でレコードをアップデートできない(insertがコミットされていないのにupdateはできないため)
                // Mod params (For create image-file thumbnail using gd)  2010/02/16 K.Ando --start--
                //$param_file[] = str_replace("pdf","png", $file_array[$cnt]['FILE_NAME']);
                // ファイル名を変更 2013/03/18 K.Matsuo
                $filename = pathinfo($file_array[$cnt]['RENAME']);
                $param_file[] =  str_replace($filename['extension'], "png", $file_array[$cnt]['RENAME']);

                // Mod params (For create image-file thumbnail using gd)  2010/02/16 K.Ando --end--
            } else {
                $param_file[] = "";             // "file_prev_name"空
            }
            // Add browsing_flag 2011/02/16 A.Suzuki --start--
            $param_file[] = intval( $file_array[$cnt]['BROWSING_FLAG'] );   // "browsing_flag"
            // Add browsing_flag 2011/02/16 A.Suzuki --end--
            $param_file[] = intval( $file_array[$cnt]['COVER_CREATED_FLAG'] );   // "cover_created_flag"
            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_file[] = $user_id;   // ins_user_id
            $param_file[] = $user_id;   // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_file[] = $this->TransStartDate;  // ins_date
            $param_file[] = $this->TransStartDate;  // mod_date
            $param_file[] = 0;                      // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_file as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_file );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT file. file name : ".$file_array[$cnt]['FILE_NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");

            // PDFのプレビュー化処理を追加 2008/07/22 Y.Nakao --start--
            if($prev_flg == "true"){
                // PDFのプレビューファイルをBLOBのカラムへ登録
                $ret = $this->Db->updateBlobFile(
                    'repository_file',
                    'file_prev',
                    $path. ".png",
                    'item_id = '. $item_id. " AND ".
                    'item_no = '. intval($file_array[$cnt]['ITEM_NO']). " AND ".
                    'attribute_id = '. $attrId. " AND ".
                    'file_no = '. $attrNo,
                    'LONGBLOB'
                );
                if ($ret === false) {
                    $error_msg .="MySQL ERROR : UPLOAD BLOB PDF Prev : ".$file_array[$cnt]['FILE_NAME'];
                    $this->writeLog("  Failed: updateBlobFile.\n");
                    $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                    return false;
                }
            }
            // PDFのプレビュー化処理を追加 2008/07/22 Y.Nakao --end--

            // Add file price 2008/09/03 Y.Nakao --start--
            //////////////////////////////////// 価格付きファイル登録 //////////////////////////////////
            for ($cnt2 = 0; $cnt2 < count($file_price_array); $cnt2++){

                // アイテムとの依存関係をチェックする
                if ( $file_price_array[$cnt2]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                    $error_msg .= "warning:insert file price [item_id]";
                    continue;
                }

                if( $file_price_array[$cnt2]["ITEM_NO"] == $file_array[$cnt]['ITEM_NO'] &&
                    $file_price_array[$cnt2]["ATTRIBUTE_ID"] == $file_array[$cnt]['ATTRIBUTE_ID'] &&
                    $file_price_array[$cnt2]["FILE_NO"] == $file_array[$cnt]['FILE_NO'] ) {

                    $file_price_query = "INSERT INTO ". DATABASE_PREFIX ."repository_file_price ".
                                        "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
                    ///// ファイルに紐づく課金情報ならば登録 /////
                    // バインド変数設定
                    $param_file_price = array();
                    $param_file_price[] = intval( $item_id );
                    $param_file_price[] = intval( $file_price_array[$cnt2]["ITEM_NO"] );
                    $param_file_price[] = $attrId;
                    $param_file_price[] = $attrNo;
                    // Add the management value is changed from room_id to page_name. Y.Nakao 2008/10/06 --start--
                    $price = explode("|", $file_price_array[$cnt2]["PRICE"]);
                    $importprice = "";
                    for($ii=0;$ii<count($price);$ii++){
                        $info = explode(",", $price[$ii]);
                        if($info[0] != '0'){
                            $query = "SELECT room_id FROM ". DATABASE_PREFIX ."pages ".
                                     "WHERE page_name = ?; ";
                            $param = array();
                            $param[] = $info[0];
                            $this->writeLog("  Execute query: ". $query."\n");
                            foreach($param as $key => $val)
                            {
                                $this->writeLog("  Execute params ".$key.": ". $val."\n");
                            }
                            $ret = $this->Db->execute( $query, $param );
                            if($ret === false){
                                $error_msg .="MySQL ERROR : SELECT room. page name : ".$info[0];
                                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                                return false;
                            }
                            $this->writeLog("    Complete execute query.\n");
                            if(count($ret)!=1){
                                // $error_msg .= "warning:insert file price. this WEKO isn't group:".$info[0];
                                continue;
                            }
                            if($ii != 0){
                                $importprice .= "|";
                            }
                            $importprice .= $ret[0]["room_id"]. "," . $info[1];
                        } else {
                            if($ii != 0){
                                $importprice .= "|";
                            }
                            $importprice .= $price[$ii];
                        }
                    }
                    $param_file_price[] = $importprice;
                    // Add the management value is changed from room_id to page_name. Y.Nakao 2008/10/06 --end--
                    // common
                    // user_idのString対応 2008/06/03 Y.Nakao --start--
                    $param_file_price[] = $user_id; // ins_user_id
                    $param_file_price[] = $user_id; // mod_user_id
                    $param_file_price[] = "";       // del_user_id
                    // user_idのString対応 2008/06/03 Y.Nakao --end--
                    $param_file_price[] = $this->TransStartDate;    // ins_date
                    $param_file_price[] = $this->TransStartDate;    // mod_date
                    $param_file_price[] = "";                       // del_date
                    $param_file_price[] = 0;                        // is_delete

                    // Run query
                    $this->writeLog("  Execute query: ". $file_price_query."\n");
                    foreach($param_file_price as $key => $val)
                    {
                        $this->writeLog("  Execute params ".$key.": ". $val."\n");
                    }
                    $result = $this->Db->execute( $file_price_query, $param_file_price );
                    if($result === false){
                        $error_msg .="MySQL ERROR : INSERT File price.";
                        $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                        $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                        return false;
                    }
                    $this->writeLog("    Complete execute query.\n");
                }
            }
            // Add file price 2008/09/03 Y.Nakao --end--
            $pluralFlag = true;
        }

        // 添付ファイルの登録
        for ($cnt = 0; $cnt < count($attached_file_array); $cnt++){

            // アイテム属性タイプとの依存関係をチェックする
            //
            if ( $attached_file_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id){
                $error_msg .= "warning:insert attached file [item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $attached_file_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert attached file [item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($attached_file_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // file_no
            $attrNo = intval($attached_file_array[$cnt]["FILE_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            // ファイルの存在をチェックする
            $path = "";
            $path = $tmp_dir . DIRECTORY_SEPARATOR. mb_convert_encoding($attached_file_array[$cnt]['ATTACHED_FILE_NAME'], $this->encode, "auto");
            if(!file_exists($path)){
                $error_msg .= "warning:file not exists ".$attached_file_array[$cnt]['ATTACHED_FILE_NAME'];
                continue;
            }
            $path = "";

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_attached_file(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "file_no, " .
                    "attached_file_no, " .
                    "attached_file_name, " .
                    "mime_type , " .
                    "extension, " .
                    "attached_file, ".
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // DBテーブル接頭語追加 Y.Nakao 2008/06/17 --end--
            // バインド変数設定
            $param_attached_file = array();
            $param_attached_file[] = intval( $item_id );
            $param_attached_file[] = intval( $attached_file_array[$cnt]['ITEM_NO'] );
            $param_attached_file[] = $attrId;
            $param_attached_file[] = $attrNo;
            $param_attached_file[] = intval( $attached_file_array[$cnt]['ATTACHED_FILE_NO'] );
            $param_attached_file[] = $attached_file_array[$cnt]['ATTACHED_FILE_NAME'];
            $param_attached_file[] = $attached_file_array[$cnt]['MIME_TYPE'];
            $param_attached_file[] = $attached_file_array[$cnt]['EXTENSION'];
            $param_attached_file[] = ""; // attached_file

            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_attached_file[] = $user_id;  // ins_user_id
            $param_attached_file[] = $user_id;  // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_attached_file[] = $this->TransStartDate; // ins_date
            $param_attached_file[] = $this->TransStartDate; // mod_date
            $param_attached_file[] = 0;                     // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_attached_file as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_attached_file );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT Attached file. file name : ".$attached_file_array[$cnt]['ATTACHED_FILE_NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");

            // 登録するファイルの付加情報
            //$path = $tmp_dir . "\\" . $attached_file_array[$cnt]['ATTACHED_FILE_NAME'];
            $path = $tmp_dir . DIRECTORY_SEPARATOR. mb_convert_encoding($attached_file_array[$cnt]['ATTACHED_FILE_NAME'], $this->encode, "auto");
            $where_clause = "item_id = " . intval( $item_id ) .
                            " AND item_no = " . intval( $attached_file_array[$cnt]['ITEM_NO'] ) .
                            " AND attribute_id = " . $attrId .
                            " AND file_no = " . $attrNo .
                            " AND attached_file_no = " . intval( $attached_file_array[$cnt]['ATTACHED_FILE_NO'] );

            // ファイルの登録
            $ret = $this->registFile("repository_attached_file", "attached_file", $path, $where_clause, $error_msg);
            if($ret == false){
                $error_msg .= " attached file name : ".$attached_file_array[$cnt]['ATTACHED_FILE_NAME'];
                $this->writeLog("  Failed: registFile.\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $pluralFlag = true;
        }

        // Add biblio info 2008/08/22 Y.Nakao --start--
        //////////////////////////////////// Insert biblio info //////////////////////////////////
        for ($cnt = 0; $cnt < count($biblio_info_array); $cnt++){

            // アイテム属性タイプとの依存関係をチェックする
            if ( $biblio_info_array[$cnt]['ITEM_TYPE_ID'] != $XML_item_type_id){
                $error_msg .= "warning:insert biblio info [item_type_id]";
                continue;
            }
            // アイテムとの依存関係をチェックする
            if ( $biblio_info_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert biblio info [item_id]";
                continue;
            }

            // attribute_id
            $attrId = intval($biblio_info_array[$cnt]["ATTRIBUTE_ID"]);
            if($attrId == 0)
            {
                continue;
            }

            // biblio_no
            $attrNo = intval($biblio_info_array[$cnt]["BIBLIO_NO"]);
            if($attrNo == 0)
            {
                continue;
            }

            // Check plural enable
            if($tmpAttrId != $attrId)
            {
                $tmpAttrId = $attrId;
                $pluralFlag = false;
            }
            else if($pluralEnableList[$attrId] != "1" && $pluralFlag)
            {
                continue;
            }

            // Check value
            if(strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["BIBLIO_NAME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["BIBLIO_NAME_ENGLISH"])) == 0 &&
               strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["VOLUME"])) == 0 &&
               strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["ISSUE"])) == 0 &&
               strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["START_PAGE"])) == 0 &&
               strlen(RepositoryOutputFilter::string($biblio_info_array[$cnt]["END_PAGE"])) == 0 &&
               strlen(RepositoryOutputFilter::date($biblio_info_array[$cnt]["DATE_OF_ISSUED"])) == 0)
            {
                continue;
            }

            $query = "INSERT INTO ". DATABASE_PREFIX ."repository_biblio_info(" .
                    "item_id, " .
                    "item_no, " .
                    "attribute_id, " .
                    "biblio_no, " .
                    "biblio_name, " .
                    "biblio_name_english, " .   // add "biblio_name_english" 2009/07/23 A.Suzuki
                    "volume, " .
                    "issue, " .
                    "start_page, " .
                    "end_page, " .
                    "date_of_issued, " .
                    "item_type_id, " .
                    "ins_user_id, " .
                    "mod_user_id, " .
                    "ins_date, " .
                    "mod_date, " .
                    "is_delete ) " .
                    "VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
            // バインド変数設定
            $param_biblio_info = array();
            $param_biblio_info[] = intval( $item_id );
            $param_biblio_info[] = intval( $biblio_info_array[$cnt]['ITEM_NO'] );
            $param_biblio_info[] = $attrId;
            $param_biblio_info[] = $attrNo;
            $param_biblio_info[] = $biblio_info_array[$cnt]['BIBLIO_NAME'];
            $param_biblio_info[] = $biblio_info_array[$cnt]['BIBLIO_NAME_ENGLISH'];
            $param_biblio_info[] = $biblio_info_array[$cnt]['VOLUME'];
            $param_biblio_info[] = $biblio_info_array[$cnt]['ISSUE'];
            $param_biblio_info[] = $biblio_info_array[$cnt]['START_PAGE'];
            $param_biblio_info[] = $biblio_info_array[$cnt]['END_PAGE'];
            $param_biblio_info[] = RepositoryOutputFilter::date($biblio_info_array[$cnt]["DATE_OF_ISSUED"]);
            $param_biblio_info[] = $item_type_id;

            // 共通項目
            // user_idのString対応 2008/06/03 Y.Nakao --start--
            $param_biblio_info[] = $user_id;    // ins_user_id
            $param_biblio_info[] = $user_id;    // mod_user_id
            // user_idのString対応 2008/06/03 Y.Nakao --end--
            $param_biblio_info[] = $this->TransStartDate;   // ins_date
            $param_biblio_info[] = $this->TransStartDate;   // mod_date
            $param_biblio_info[] = 0;                       // is_delete

            // Run query
            $this->writeLog("  Execute query: ". $query."\n");
            foreach($param_biblio_info as $key => $val)
            {
                $this->writeLog("  Execute params ".$key.": ". $val."\n");
            }
            $result = $this->Db->execute( $query, $param_biblio_info );
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT Biblio info. biblio name : ".$biblio_info_array[$cnt]['BIBLIO_NAME'];
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            $this->writeLog("    Complete execute query.\n");
            $pluralFlag = true;
        }
        // Add biblio info 2008/08/22 Y.Nakao --end--

        // Add e-person 2013/10/22  R.Matsuura --start--
        //////////////////////////////////// Insert feedback mailaddress //////////////////////////////////
        $itemRegister = new ItemRegister($this->Session, $this->Db);
        $authorIdNo = 1;
        for ($cnt = 0; $cnt < count($feedback_mailaddress_array); $cnt++){
            // アイテムとの依存関係をチェックする
            if ( $feedback_mailaddress_array[$cnt]['ITEM_ID'] != $item_array[0]['ITEM_ID'] ){
                $error_msg .= "warning:insert feedback mailaddress [item_id]";
                continue;
            }
            // check mail address
            if ( $feedback_mailaddress_array[$cnt]['E_MAIL_ADDRESS'] == "") {
                continue;
            }

            // get author_id
            $authorId = $itemRegister->getAuthorIdByMailAddress( $feedback_mailaddress_array[$cnt]['E_MAIL_ADDRESS'] );
            if($authorId === false){
                continue;
            }

            // insert send feedback mail author id
            $result = $itemRegister->insertFeedbackMailAuthorId(intval($item_id), intval($feedback_mailaddress_array[$cnt]['ITEM_NO']),
                                                                $authorIdNo, $authorId);
            if($result === false){
                $error_msg .="MySQL ERROR : INSERT send feedback author id ";
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }
            // set author ID No
            $authorIdNo++;
        }
        // Add e-person 2013/10/22  R.Matsuura --end--
        // Add cnri handle 2014/09/17 T.Ichikawa --start--
        //////////////////////////////////// Insert CNRI handle //////////////////////////////////
        // CNRIの値は"http://hdl.handle.net/[PrefixID]/[Suffix]/の形式で送られてくる"
        // CNRIプレフィックス値の設定のチェック
        $cnri_prefix = $this->repositoryHandleManager->getCnriPrefix();
        if(strlen($cnri_prefix) > 0) {
            for ($cnt = 0; $cnt < count($cnri_array); $cnt++){
                // サーバとXMLのプレフィックスIDを照合する
                $params = str_replace("http://hdl.handle.net/", "", $cnri_array[$cnt]['CNRI']);
                $handle = explode("/", $params);
                if($cnri_prefix == $handle[0]) {
                    // CNRIのプレフィックスIDが一致したら処理を行う
                    $this->repositoryHandleManager->registCnriSuffix(intval($item_id), intval($cnri_array[$cnt]['ITEM_NO']), $handle[1]);
                }
            }
        }
        // Add cnri handle 2014/09/17 T.Ichikawa --end--
        
        //////////////////////////////////// Insert selfDOI //////////////////////////////////
        if(isset($selfdoi_array[0]['RA']) && strlen($selfdoi_array[0]['RA']) > 0)
        {
            $checkdoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
            $handleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
            if($selfdoi_array[0]['RA'] === RepositoryConst::JUNII2_SELFDOI_RA_JALC)
            {
                $selfdoiPrefixSuffix = explode("/", $selfdoi_array[0]['SELFDOI']);
                $libraryJalcdoiPrefix = $handleManager->getLibraryJalcDoiPrefix();
                if($selfdoiPrefixSuffix[0] === $libraryJalcdoiPrefix)
                {
                    $checkRegist = $checkdoi->checkDoiGrant($item_id, $item_no, 2, 0);
                    if($checkRegist)
                    {
                        $handleManager->registLibraryJalcdoiSuffix($item_id, $item_no, $selfdoi_array[0]['SELFDOI']);
                    }
                    else
                    {
                        $checkRegist = $checkdoi->checkDoiGrant($item_id, $item_no, 0, 0);
                        if($checkRegist)
                        {
                            $suffix = $handleManager->getYHandleSuffix($item_id, $item_no);
                            $handleManager->registJalcdoiSuffix($item_id, $item_no, $suffix);
                        }
                    }
                }
                else
                {
                    $checkRegist = $checkdoi->checkDoiGrant($item_id, $item_no, 0, 0);
                    if($checkRegist)
                    {
                        $suffix = $handleManager->getYHandleSuffix($item_id, $item_no);
                        $handleManager->registJalcdoiSuffix($item_id, $item_no, $suffix);
                    }
                }
            }
            else if($selfdoi_array[0]['RA'] === RepositoryConst::JUNII2_SELFDOI_RA_CROSSREF)
            {
            
                $checkRegist = $checkdoi->checkDoiGrant($item_id, $item_no, 1, 0);
                if($checkRegist)
                {
                    $suffix = $handleManager->getYHandleSuffix($item_id, $item_no);
                    $handleManager->registCrossrefSuffix($item_id, $item_no, $suffix);
                }
            }
        }

        // Reissue attribute_no
        if(!$this->reissueAttrNo(intval($item_id), intval($item_array[0]['ITEM_NO']), $error_msg))
        {
            $error_msg .="MySQL ERROR : UPDATE attribute_no.";
            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
            $this->writeLog("-- End itemEntry in ImportCommon class --\n");
            return false;
        }

        // requiredCheck
        $this->writeLog("  Call requiredCheck.\n");
        $tmpErrorMsg = "";
        $tmpWarningMsg = "";
        $result = $this->requiredCheck(intval($item_id), intval($item_array[0]['ITEM_NO']),$tmpErrorMsg, $tmpWarningMsg);
        if($result)
        {
            $this->writeLog("  requiredCheck OK.\n");

            // Add PDF cover page 2012/06/18 A.Suzuki --start--
            $this->executeCreatePdfCover($index_id, intval($item_id), intval($item_array[0]['ITEM_NO']), $user_id, $error_msg);
            // Add PDF cover page 2012/06/18 A.Suzuki --end--

            // Convert to flash
            if(!$this->convertToFlash(intval($item_id), intval($item_array[0]['ITEM_NO']), $error_msg))
            {
                $error_msg .="MySQL ERROR : SELECT file data.";
                $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }

            // Set item status and regist to whatsnew
            if(!$this->setItemStatus(intval($item_id), intval($item_array[0]['ITEM_NO']), $user_id, $index_id, $item_array[0]['SHOWN_STATUS'], $item_array[0]['REVIEW_STATUS']))
            {
                $error_msg .="ERROR : Set item status.";
                $this->writeLog("  Failed: setItemStatus\n");
                $this->writeLog("-- End itemEntry in ImportCommon class --\n");
                return false;
            }

            // Update review status
            $array_item[count($array_item)-1]["review_status"] = $item_array[0]['REVIEW_STATUS'];
        }
        else
        {
            // [Warning]
            $this->writeLog("  requiredCheck NG.\n");
            $this->writeLog("  ".$tmpErrorMsg."\n");
            $error_msg .= $tmpErrorMsg;
            return false;
        }

        // 登録したアイテムのFullTextデータ作成
        for($ii=0; $ii<count($item_id_fulltext); $ii++) {
            // Add detail search 2013/11/25 K.Matsuo --start--
            $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            $searchTableProcessing->updateSearchTableForItem($item_id_fulltext[$ii], $item_no_fulltext[$ii]);
            // Add detail search 2013/11/25 K.Matsuo --end--
        }

        // ------------------------------------------------------------------
        // add item insert log
        // ------------------------------------------------------------------
        // Add log common action Y.Nakao 2010/03/05 --start--
        $this->entryLog(1, intval($item_id), intval($item_array[0]['ITEM_NO']), "", "", "");
        // Add log common action Y.Nakao 2010/03/05 --end--

        // Update review status
        $array_item[count($array_item)-1]["status"] = "success";

        $this->writeLog("  itemEntry completed.\n");
        $this->writeLog("-- End itemEntry in ImportCommon class --\n");
        return true;
    }

    // Add check metadata 2008/10/01 Y.Nakao --start--
    /**
     * metadata check
     *
     * return true  : metadata is same
     *        false : metadata is different
     */
    function checkMetadata($item_type_id, $item_attr_type, $item_attr_candidate){
        $this->writeLog("-- Start checkMetadata in ImportCommon class --\n");
        $conflict = true;

        // get metadata
        $result = $this->getItemAttrTypeTableData($item_type_id, $metadata, $error_msg);
        if($result === false){
            $error_msg .="ERROR get item attr type table data.<br/>";
            $this->writeLog("  Failed: get item attr type table data.\n");
            $this->writeLog("-- End checkMetadata in ImportCommon class --\n");
            return false;
        }
        // check metadata conflict
        if(count($metadata['item_attr_type']) != count($item_attr_type)){
            $conflict = false;
        } else {
            for($ii=0;$ii<count($metadata['item_attr_type']);$ii++){
                if($metadata['item_attr_type'][$ii]['attribute_name'] != $item_attr_type[$ii]['ATTRIBUTE_NAME']){
                    // attribute_name is not same
                    $conflict = false;
                    break;
                } else if($metadata['item_attr_type'][$ii]['input_type'] != $item_attr_type[$ii]['INPUT_TYPE']){
                    // input type or show order is not same
                    $conflict = false;
                    break;
                } else {
                    // chack is_required
                    if($metadata['item_attr_type'][$ii]['is_required'] != $item_attr_type[$ii]['IS_REQUIRED']){
                        // is_required is not same
                        $conflict = false;
                        break;
                    }
                    // check plural_enable
                    if($item_attr_type[$ii]['PLURAL_ENABLE'] == '1' &&
                        $metadata['item_attr_type'][$ii]['plural_enable'] != $item_attr_type[$ii]['PLURAL_ENABLE']){
                        // if DB is plural and xml is not plural then this item type is OK
                        // plural_enable is not same
                        $conflict = false;
                        break;
                    }
                    // check candidate
                    if($metadata['item_attr_type'][$ii]['input_type'] == 'checkbox' ||
                        $metadata['item_attr_type'][$ii]['input_type'] == 'radio' ||
                        $metadata['item_attr_type'][$ii]['input_type'] == 'select' ){
                        $query = "SELECT * " .
                                 "FROM ". DATABASE_PREFIX ."repository_item_attr_candidate cand " .
                                 "INNER JOIN ". DATABASE_PREFIX ."repository_item_attr_type type " .
                                 "ON cand.item_type_id = type.item_type_id AND cand.attribute_id = type.attribute_id ".
                                 "WHERE cand.item_type_id = ? " .
                                 "AND cand.is_delete = 0 ORDER BY type.show_order, cand.candidate_no ; ";
                        $params = array();
                        $params[] = $item_type_id;
                        // Run select
                        $this->writeLog("  Execute query: ". $query."\n");
                        foreach($params as $key => $val)
                        {
                            $this->writeLog("  Execute params ".$key.": ". $val."\n");
                        }
                        $result = $this->Db->execute($query, $params);
                        if($result === false) {
                            // Get DB error
                            $error_msg .="ERROR select candidate.<br/>";
                            // ROLLBACK
                            $this->failTrans();
                            $this->writeLog("  Failed: ".$this->Db->ErrorMsg()."\n");
                            $this->writeLog("-- End checkMetadata in ImportCommon class --\n");
                            return false;
                        }
                        $this->writeLog("    Complete execute query.\n");

                        if(count($result) != count($item_attr_candidate)){
                            $conflict = false;
                            break;
                        }
                        for($jj=0;$jj<count($result);$jj++){
                            if($result[$jj]['candidate_value'] != $item_attr_candidate[$jj]['CANDIDATE_VALUE']){
                                $conflict = false;
                                break;
                            }
                        }
                    }
                }
            }
        }
        $this->writeLog("  checkMetadata completed.\n");
        $this->writeLog("-- End checkMetadata in ImportCommon class --\n");
        return $conflict;

    }
    // Add check metadata conflict 2008/10/01 Y.Nakao --end--

    function checkDateFormat($input_date, $full_date=true){
        // If delimiter is "/" or ".", change to "-".
        $input_date = trim($input_date);
        $date = explode(" ", $input_date);

        $date[0] = str_replace("/", "-", $date[0]);
        $date[0] = str_replace(".", "-", $date[0]);
        $tmp_date = explode("-", $date[0]);

        if($tmp_date[1] != "" && strlen($tmp_date[1]) == 1){
            $tmp_date[1] = "0".$tmp_date[1];
        } else if($full_date && $tmp_date[1] == ""){
            // If month data is null, month is "01".
            $tmp_date[1] = "01";
        }

        if($tmp_date[2] != "" && strlen($tmp_date[2]) == 1){
            $tmp_date[2] = "0".$tmp_date[2];
        } else if($full_date && $tmp_date[2] == ""){
            // If day data is null, day is "01".
            $tmp_date[2] = "01";
        }

        $output_date = $tmp_date[0];
        if($tmp_date[1] != ""){
            $output_date .= "-".$tmp_date[1];
            if($tmp_date[2] != ""){
                $output_date .= "-".$tmp_date[2];
                if($date[1] != ""){
                    $output_date .= " ".$date[1];
                }
            }
        }

        return $output_date;
    }
    // create image-file thumbnail using gd 2010/02/16 K.Ando --start--
    /**
     * create thumnail image using GD
     *
     * @param $image image file
     * @param $filepath filepath
     */
    function createThumbnailImage(&$image, $filepath)
    {
        $basewidth  = 280;
        $baseheight = 200;
        $width = ImageSX($image);  //image width (pixel)
        $height = ImageSY($image); //image height (pixel)
        $new_width = ImageSX($image);
        $new_height = ImageSY($image);

        if($baseheight <= $height || $basewidth <= $width )
        {
            // calc resize-image size
            if($width > $height){
                $new_width = 280;
                $rate = $new_width / $width;    //resize-rate
                $new_height = $rate * $height;
            }
            else
            {
                $new_height = 200;
                $rate = $new_height / $height; // resize-rate
                $new_width = $rate * $width;
            }
            // initialize resize image
            $new_image = ImageCreateTrueColor($new_width, $new_height);
            // generate resize image
            $result = ImageCopyResampled($new_image,$image,0,0,0,0,$new_width,$new_height,$width,$height);
            if(!$result)
            {
                return $result;
            }

            // ファイルに保存する
            $result = ImagePNG($new_image, $filepath);
            if($result)
            {
                imagedestroy ($new_image); //サムネイル用イメージIDの破棄 ※3
            }
            return $result;
        }
        else
        {
            // ファイルに保存する
            $result = ImagePNG($image, $filepath);
            return $result;
        }

    }

    /**
     * Convert bmp to GD Object
     *
     * @param $src
     * @param $dest
     * @return GD image
     */
    function ConvertBMP2GD($src, $dest = false) {
        if(!($src_f = fopen($src, "rb"))) {
            return false;
        }
        if(!($dest_f = fopen($dest, "wb"))) {
            return false;
        }
        $header = unpack("vtype/Vsize/v2reserved/Voffset", fread($src_f, 14));
        $info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant",
        fread($src_f, 40));

        extract($info);
        extract($header);

        if($type != 0x4D42) {    // signature "BM"
            return false;
        }

        $palette_size = $offset - 54;
        $ncolor = $palette_size / 4;
        $gd_header = "";
        // true-color vs. palette
        $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
        $gd_header .= pack("n2", $width, $height);
        $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
        if($palette_size) {
            $gd_header .= pack("n", $ncolor);
        }
        // no transparency
        $gd_header .= "\xFF\xFF\xFF\xFF";

        fwrite($dest_f, $gd_header);

        if($palette_size) {
            $palette = fread($src_f, $palette_size);
            $gd_palette = "";
            $j = 0;
            while($j < $palette_size) {
                $b = $palette{$j++};
                $g = $palette{$j++};
                $r = $palette{$j++};
                $a = $palette{$j++};
                $gd_palette .= "$r$g$b$a";
            }
                $gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
                fwrite($dest_f, $gd_palette);
        }

        $scan_line_size = (($bits * $width) + 7) >> 3;
        $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

        for($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
            // BMP stores scan lines starting from bottom
            fseek($src_f, $offset + (($scan_line_size + $scan_line_align) * $l));
            $scan_line = fread($src_f, $scan_line_size);
            if($bits == 24) {
                $gd_scan_line = "";
                $j = 0;
                while($j < $scan_line_size) {
                    $b = $scan_line{$j++};
                    $g = $scan_line{$j++};
                    $r = $scan_line{$j++};
                    $gd_scan_line .= "\x00$r$g$b";
                }
            }
            else if($bits == 8) {
                $gd_scan_line = $scan_line;
            }
            else if($bits == 4) {
                $gd_scan_line = "";
                $j = 0;
                while($j < $scan_line_size) {
                    $byte = ord($scan_line{$j++});
                    $p1 = chr($byte >> 4);
                    $p2 = chr($byte & 0x0F);
                    $gd_scan_line .= "$p1$p2";
                }
                $gd_scan_line = substr($gd_scan_line, 0, $width);
            }
            else if($bits == 1) {
                $gd_scan_line = "";
                $j = 0;
                while($j < $scan_line_size) {
                    $byte = ord($scan_line{$j++});
                    $p1 = chr((int) (($byte & 0x80) != 0));
                    $p2 = chr((int) (($byte & 0x40) != 0));
                    $p3 = chr((int) (($byte & 0x20) != 0));
                    $p4 = chr((int) (($byte & 0x10) != 0));
                    $p5 = chr((int) (($byte & 0x08) != 0));
                    $p6 = chr((int) (($byte & 0x04) != 0));
                    $p7 = chr((int) (($byte & 0x02) != 0));
                    $p8 = chr((int) (($byte & 0x01) != 0));
                    $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
            }
            $gd_scan_line = substr($gd_scan_line, 0, $width);
            }

            fwrite($dest_f, $gd_scan_line);
        }
        fclose($src_f);
        fclose($dest_f);
        return true;
    }

    /**
     * create GD image by bmp
     *
     * @param $filename bmp file path
     * @return GD image
     */
    function imagecreatefrombmp($filename) {
        $tmp_name = $filename."bk";
        if($this->ConvertBMP2GD($filename, $tmp_name))
        {
            $img = imagecreatefromgd($tmp_name);
            unlink($tmp_name);
            return $img;
        }
        return false;
    }
    // create image-file thumbnail using gd 2010/02/16 K.Ando --end--


    // Fix null language 2012/02/21 Y.Nakao --start--
    function setLanguage($lang)
    {
        $lang = RepositoryOutputFilter::language($lang);
        if(strlen($lang) == 0)
        {
            if($this->Session->getParameter("_lang") == 'japanese')
            {
                $lang = RepositoryConst::ITEM_LANG_JA;;
            }
            else
            {
                $lang = RepositoryConst::ITEM_LANG_EN;
            }
        }
        return $lang;
    }
    // Fix null language 2012/02/21 Y.Nakao --end--

    /**
     * Create PDF cover
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @param bool $coverErrorFlag
     * @return bool
     */
    private function createPdfCover($itemId, $itemNo, $userId="", &$coverErrorFlag)
    {
        $coverErrorFlag = false;

        // Set user_id
        if(strlen($userId) > 0)
        {
            $userId = $this->Session->getParameter("_user_id");
        }

        // Get registered files
        $result = $this->getRegistertedFileData($itemId, $itemNo);
        if($result === false)
        {
            $coverErrorFlag = true;
            return false;
        }
        // Loop for files
        for($ii=0; $ii<count($result); $ii++)
        {
            // Check file type
            if(strtolower($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION])!="pdf")
            {
                continue;
            }

            $pdfCover = new RepositoryPdfCover(
                                $this->Session,
                                $this->Db,
                                $this->TransStartDate,
                                $userId,
                                $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],
                                $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO],
                                $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],
                                $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO]
                            );
            if($pdfCover->execute())
            {
                // Success
                // Delete this file's old flash
                $this->removeDirectory(
                    $this->getFlashFolder(
                            $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],
                            $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],
                            $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO]
                        )
                    );
            }
            else
            {
                $coverErrorFlag = true;
            }
        }

        return true;
    }

    /**
     * Get registered files data
     *
     * @param int $itemId
     * @param int $itemNo
     * @return array
     */
    private function getRegistertedFileData($itemId, $itemNo)
    {
        // Get registered files
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_TYPE.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_MIME_TYPE.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ?; ";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);

        return $result;
    }

    /**
     * Adjust attribute_id
     *
     * @param array &$arrayItemData
     * @param array $itemTypeInfo
     * @param array $itemAttrTypeArray
     * @return bool
     */
    public function adjustAttributeId(&$arrayItemData, $itemTypeInfo, $itemAttrTypeArray)
    {
        $replaceAttrIdArray = array();
        $itemTypeId = $itemTypeInfo["item_type_id"];

        // item_attr
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["item_attr_array"], $itemTypeId, $itemAttrTypeArray);

        // personal_name
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["personal_name_array"], $itemTypeId, $itemAttrTypeArray);

        // thumbnail
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["thumbnail_array"], $itemTypeId, $itemAttrTypeArray);

        // biblio_info
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["biblio_info_array"], $itemTypeId, $itemAttrTypeArray);

        // file & file_price
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["file_array"], $itemTypeId, $itemAttrTypeArray);
        for($ii=0; $ii<count($arrayItemData["file_price_array"]); $ii++)
        {
            if(isset($replaceAttrIdArray[$arrayItemData["file_price_array"][$ii]["ATTRIBUTE_ID"]]))
            {
                $replaceAttrId = intval($replaceAttrIdArray[$arrayItemData["file_price_array"][$ii]["ATTRIBUTE_ID"]]);
                $arrayItemData["file_price_array"][$ii]["ATTRIBUTE_ID"] = $replaceAttrId;
            }
        }

        // attached_file
        $replaceAttrIdArray = $this->adjustAttributeIdToShowOrder($arrayItemData["attached_file_array"], $itemTypeId, $itemAttrTypeArray);

        return true;
    }

    /**
     * Adjust attribute_id to show_order
     *
     * @param array &$arrayAttrData
     * @param int $itemTypeId
     * @param array $itemAttrTypeArray
     * @return array
     */
    private function adjustAttributeIdToShowOrder(&$arrayAttrData, $itemTypeId, $itemAttrTypeArray)
    {
        $replaceAttrIdArray = array();
        for($ii=0; $ii<count($arrayAttrData); $ii++)
        {
            // Get XML attribute_id
            $xmlAttrId = intval($arrayAttrData[$ii]["ATTRIBUTE_ID"]);

            if(array_key_exists($xmlAttrId, $replaceAttrIdArray))
            {
                $arrayAttrData[$ii]["ATTRIBUTE_ID"] = intval($replaceAttrIdArray[$xmlAttrId]);
                continue;
            }

            // Get XML show_order
            $xmlShowOrder = $xmlAttrId;
            for($jj=0; $jj<count($itemAttrTypeArray); $jj++)
            {
                if($xmlAttrId == intval($itemAttrTypeArray[$jj]["ATTRIBUTE_ID"]))
                {
                    $xmlShowOrder = intval($itemAttrTypeArray[$jj]["SHOW_ORDER"]);
                    break;
                }
            }

            // Get attribute_id in database
            $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." = ? ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER." = ? ".
                     "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ? ";
            $params = array();
            $params[] = $itemTypeId;
            $params[] = $xmlShowOrder;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false || count($result)==0)
            {
                return false;
            }

            $replaceAttrIdArray[$xmlAttrId] = intval($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID]);
            $arrayAttrData[$ii]["ATTRIBUTE_ID"] = intval($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID]);
        }

        return $replaceAttrIdArray;
    }

    /**
     * Execute create pdf cover
     *
     * @param array $indexIds
     * @param int $itemId
     * @param string $itemNo
     * @param string $user_id
     * @param string $errorMsg
     */
    public function executeCreatePdfCover($indexIds, $itemId, $itemNo, $user_id, &$errorMsg)
    {
        $pdfCoverCreateFlag = $this->checkIndexCreateCover($indexIds);
        if($pdfCoverCreateFlag)
        {
            $this->createPdfCover($itemId, $itemNo, $user_id, $coverErrorFlag);
            if($coverErrorFlag)
            {
                $errorMsg .= "warning: There are same files that failed to create PDF cover.";
            }
        }
    }

    /**
     * Convert to flash
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $errorMsg
     * @return bool
     */
    public function convertToFlash($itemId, $itemNo, &$errorMsg)
    {
        // Create IDServer class
        $idServer = new IDServer($this->Session, $this->Db);
        $itemRegister = new ItemRegister($this->Session, $this->Db);

        $result = $this->getRegistertedFileData($itemId, $itemNo);
        if ($result === false) {
            return false;
        }
        for($ii=0; $ii<count($result); $ii++)
        {
            if($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_TYPE] == RepositoryConst::FILE_DISPLAY_TYPE_FLASH)
            {
                $flashErrorFlag = false;
                if($this->isMultimediaFile($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_MIME_TYPE], $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]))
                {
                    // マルチメディアファイルの場合
                    if( strtolower($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]) == "swf" ||
                        strtolower($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]) == "flv")
                    {
                        // swf, flv の場合はそのままコピー
                        $flashDir = $this->makeFlashFolder( $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],
                                                            $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],
                                                            $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO]);
                        if(strlen($flashDir) > 0)
                        {
                            $flashContentsPath = $flashDir."/weko.".strtolower($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]);

                            // コピー元ファイル取得
                            $fileContentsPath = $this->getFileSavePath("file");
                            if(strlen($fileContentsPath) == 0){
                                // default directory
                                $fileContentsPath = BASE_DIR.'/webapp/uploads/repository/files';
                            }
                            $fileContentsPath .= "/".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
                            $fileContentsPath .= "_".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID];
                            $fileContentsPath .= "_".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
                            $fileContentsPath .= ".".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
                            if( file_exists($fileContentsPath) ){
                                // file copy
                                copy($fileContentsPath, $flashContentsPath);
                            } else {
                                // Not found file
                                $flashErrorFlag = true;
                            }
                        }
                    }
                    else
                    {
                        // マルチメディアファイルは flv へ変換する
                        // create arg for convert
                        $fileInfo = array();
                        $fileInfo['item_id'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
                        $fileInfo['attribute_id'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID];
                        $fileInfo['file_no'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
                        $fileInfo['upload']['extension'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
                        $fileInfo['upload']['mimetype'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_MIME_TYPE];

                        // 変換元ファイル取得
                        $fileContentsPath = $this->getFileSavePath("file");
                        if(strlen($fileContentsPath) == 0){
                            // default directory
                            $fileContentsPath = BASE_DIR.'/webapp/uploads/repository/files';
                        }
                        $fileContentsPath .= "/".$fileInfo['item_id'];
                        $fileContentsPath .= "_".$fileInfo['attribute_id'];
                        $fileContentsPath .= "_".$fileInfo['file_no'];
                        $fileContentsPath .= ".".$fileInfo['upload']['extension'];
                        $result = $itemRegister->convertFileToFlv($fileInfo, $errMsg, $fileContentsPath);
                        if($result === false){
                            // Failef convert
                            $flashErrorFlag = true;
                        }
                    }
                }
                else if(!RepositoryCheckFileTypeUtility::isImageFile($result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_MIME_TYPE], $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]))
                {
                    // マルチメディアファイル以外(pdf, ppt など)
                    $this->getRepositoryHandleManager();

                    if($this->repositoryHandleManager != null){
                        if($idServer != null){
                                $prefixId = $this->repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);

                            if(strlen($prefixId) > 0){
                                $flashError = "";
                                $flashData = array();
                                $flashData['item_id'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
                                $flashData['attribute_id'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID];
                                $flashData['file_no'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
                                $flashData['upload']['file_name'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME];
                                $flashData['upload']['extension'] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
                                $url = BASE_URL . "/?action=repository_uri&item_id=".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
                                // PDF to Flash
                                $flashResult = $idServer->convertToFlash($flashData, $url, $flashError);
                                if($flashResult !== "true"){
                                    $flashErrorFlag = true;
                                }
                            }
                            else
                            {
                                $flashErrorFlag = true;
                            }
                        } else {
                            $flashErrorFlag = true;
                        }
                    }
                }

                // Flash変換エラー時の処理
                if($flashErrorFlag)
                {
                    // Failed convert to Flash
                    $query = "UPDATE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                             "SET ".RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_TYPE." = ?, ".
                                    RepositoryConst::DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE." = ? ".
                             "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                             "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                             "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                             "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ?; ";
                    $params = array();
                    $params[] = 0;
                    $params[] = "";
                    $params[] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID];
                    $params[] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO];
                    $params[] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID];
                    $params[] = $result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
                    $updateResult = $this->Db->execute($query, $params);
                    if($updateResult === false)
                    {
                        return false;
                    }
                    $errorMsg .= "warning:\"".$result[$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME]."\" cannot convert to flash. ";
                }
            }
        }

        return true;
    }

    /**
     * Set item status
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @param array $indexIds
     * @param int $reviewStatus
     * @return bool
     */
    public function setItemStatus($itemId, $itemNo, $userId, $indexIds, $shownStatus, &$reviewStatus)
    {
        // 査読・承認を行うか否か
        $this->getAdminParam("review_flg", $reviewFlag, $errorMsg);
        $reviewFlag = intval($reviewFlag);

        // 承認済みアイテムを自動公開するか否か
        $this->getAdminParam("item_auto_public", $shownFlag, $errorMsg);
        $shownFlag = intval($shownFlag);

        // アイテムの査読、公開状態を変更する
        $query = "UPDATE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." ".
                 "SET ".RepositoryConst::DBCOL_REPOSITORY_ITEM_REVIEW_STATUS." = ?, ".
                        RepositoryConst::DBCOL_REPOSITORY_ITEM_REVIEW_DATE." = ?, ".
                        RepositoryConst::DBCOL_REPOSITORY_ITEM_SHOWN_STATUS." = ?, ".
                        RepositoryConst::DBCOL_REPOSITORY_ITEM_REJECT_STATUS." = ?, ".
                        RepositoryConst::DBCOL_REPOSITORY_ITEM_REJECT_DATE." = ?, ".
                        RepositoryConst::DBCOL_REPOSITORY_ITEM_REJECT_REASON." = ?, ".
                        RepositoryConst::DBCOL_COMMON_MOD_USER_ID." = ?, ".
                        RepositoryConst::DBCOL_COMMON_MOD_DATE." = ? ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = ?;";
        $params = array();

        if($reviewFlag == 1)
        {
            // 査読を行う(承認しない)
            $params[] = 0;  // review_status
            $params[] = ""; // review_date
            $params[] = 0;  // shown_status

            $reviewStatus = 0;
        } else {
            // 査読を行わない(承認する)
            $params[] = 1;  // review_status
            $params[] = $this->TransStartDate;  // review_date
            // modify 公開状態も設定できるようにする K.Matsuo 2014/08/20 --start--
            // 査読後に自動的に公開のとき
            if($shownFlag == 1){
                $params[] = $shownStatus;             // インポートファイルの公開状況
            } else {  // 査読後に自動的に非公開のとき
                $params[] = 0;                        // 自動的に非公開
                $shownStatus = 0;
            }
            // modify 公開状態も設定できるようにする K.Matsuo 2014/08/20 --end--
            $reviewStatus = 1;
        }
        $params[] = 0;                      // reject_status
        $params[] = "";                     // reject_date
        $params[] = "";                     // reject_reason
        $params[] = $userId;                // mod_user_id
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = $itemId;                // item_id
        $params[] = $itemNo;                // item_no
        $result = $this->Db->execute($query, $params);
        if ($result === false) {
            return false;
        }

        // 公開するアイテムならば新着情報に表示する
        $whatsNewFlag = false;
        if($reviewFlag == 0 && $shownStatus == 1)
        {
            for($ii=0; $ii<count($indexIds); $ii++)
            {
                // 登録先インデックスの公開状態を取得
                // get index to regist is not set access control
                $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID.", ".
                                   RepositoryConst::DBCOL_REPOSITORY_INDEX_PUBLIC_STATE." ".
                         "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_INDEX." ".
                         "WHERE ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID." = ? ".
                         "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                         "AND ".RepositoryConst::DBCOL_REPOSITORY_INDEX_EXCLISIVE_ACL_ROLE." = ? ".
                         "AND ".RepositoryConst::DBCOL_REPOSITORY_INDEX_EXCLISIVE_ACL_GROUP." = ?;";
                $params = array();
                $params[] = intval($indexIds[$ii]);
                $params[] = "|-1";
                $params[] = "";
                $result = $this->Db->execute($query, $params);
                if($result === false){
                    return false;
                }
                if(count($result)>0)
                {
                    // 公開中のインデックスがあるか
                    if($result[0][RepositoryConst::DBCOL_REPOSITORY_INDEX_PUBLIC_STATE] == "1"){
                        // 親インデックスが公開されているか
                        if($this->checkParentPublicState($result[0][RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID])){
                            $whatsNewFlag = true;
                            // 所属する公開中インデックスのコンテンツ数を増やす
                            $this->addContents($result[0][RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID]);
                            // 非公開コンテンツ数を減らす
                            $this->deletePrivateContents($result[0][RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID]);
                        }
                    }
                }
            }
            if($whatsNewFlag){
                $tmpUserId = $this->Session->getParameter("_user_id");
                $this->Session->setParameter("_user_id", $userId);
                $result = $this->addWhatsnew($itemId, $itemNo, 1);
                $this->Session->setParameter("_user_id", $tmpUserId);
                if ($result === false) {
                    return false;
                }
            } else {
                $this->deleteWhatsnew($itemId);
            }
        }

        return true;
    }

    /**
     * Check insert or update
     *
     * @param array $xmlItemData
     * @return bool
     */
    private function isUpdate($xmlItemData)
    {
        $ret = false;

        if(isset($xmlItemData["edit_array"][0]["URL"]) && strlen($xmlItemData["edit_array"][0]["URL"])>0)
        {
            $ret = true;
        }

        return $ret;
    }

    /**
     * Validate pub date
     *
     * @param string $pubDate "YYYY-MM-DD"
     * @return array
     */
    public function validatePubDate($pubDate)
    {
        $tmpPubDateArray = explode(" ", $pubDate, 2);
        $tmpPubDate = RepositoryOutputFilter::date($tmpPubDateArray[0]);
        $exPubDate = explode("-", $tmpPubDate);
        if(count($exPubDate) != 3)
        {
            // year, month, day に分割できない場合 = 不正な日付
            // -> 現在の日付を設定する
            $tmpPubDateArray = explode(" ", $this->TransStartDate, 2);
            $tmpPubDate = RepositoryOutputFilter::date($tmpPubDateArray[0]);
            $exPubDate = explode("-", $tmpPubDate);
        }

        $retArray = array(  self::PUBDATE => $tmpPubDate,
                            self::YEAR => $exPubDate[0],
                            self::MONTH => $exPubDate[1],
                            self::DAY => $exPubDate[2]
                        );
        return $retArray;
    }

    /**
     * Validate link
     *
     * @param string $attrValue "[URL]|[表示名]"
     * @return string
     */
    public function validateLink($attrValue)
    {
        $retText = "";
        $exLink = explode("|", $attrValue, 2);
        $url = trim($exLink[0]);
        $dispName = "";
        if(isset($exLink[1]))
        {
            $dispName = trim($exLink[1]);
        }

        if(strlen(RepositoryOutputFilter::string($url))>0)
        {
            $retText = $url;
            if(strlen(RepositoryOutputFilter::string($dispName))>0)
            {
                $retText .= "|".$dispName;
            }
        }

        return $retText;
    }

    /**
     * Validate file display type
     *
     * @param string $displayType
     * @return int
     */
    public function validateFileDisplayType($displayType)
    {
        $retDispType = intval(RepositoryConst::FILE_DISPLAY_TYPE_DETAIL);
        $displayType = intval($displayType);
        switch($displayType)
        {
            case intval(RepositoryConst::FILE_DISPLAY_TYPE_DETAIL):
            case intval(RepositoryConst::FILE_DISPLAY_TYPE_SIMPLE):
            case intval(RepositoryConst::FILE_DISPLAY_TYPE_FLASH):
                $retDispType = $displayType;
                break;
            default:
                break;
        }

        return $retDispType;
    }

    /**
     * Validate file license
     *
     * @param string $licenseId
     * @param string $notation
     * @return array
     */
    public function validateFileLicense($licenseId, $notation)
    {
        // Init
        $retArray = array(  RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID => 0,
                            RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION => ""
                        );

        if($licenseId == 0 && strlen($notation)>0)
        {
            $retArray[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION] = $notation;
        }
        else if(strlen($licenseId)>0)
        {
            // Get license master
            $licenseMasters = $this->Db->selectExecute(
                                            RepositoryConst::DBTABLE_REPOSITORY_LICENSE_MASTER,
                                            array(RepositoryConst::DBCOL_COMMON_IS_DELETE => 0)
                                        );
            if ($licenseMasters !== false) {
                foreach($licenseMasters as $licenseMaster)
                {
                    $masterId = intval($licenseMaster[RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_ID]);
                    $masterNotation = $licenseMaster[RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_NOTATION];
                    if($masterId == intval($licenseId))
                    {
                        $retArray[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID] = $masterId;
                        $retArray[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION] = $masterNotation;
                        break;
                    }
                }
            }
        }

        return $retArray;
    }


    /**
     * Validate title
     *
     * @param string $title
     * @param string $titleEn
     * @param string $language
     * @return array
     */
    public function validateTitle($title, $titleEn, $language)
    {
        if(strlen(RepositoryOutputFilter::string($title)) == 0 &&
           strlen(RepositoryOutputFilter::string($titleEn)) == 0)
        {
           if(RepositoryOutputFilter::language($language) == RepositoryConst::ITEM_LANG_JA)
           {
               $title = "タイトル無し";
               $titleEn = "";
           }
           else
           {
               $title = "";
               $titleEn = "no title";
           }
        }

        $retArray = array(RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE => $title,
                          RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH => $titleEn);
        return $retArray;
    }

    /**
     * Reissue attribute no
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $errorMsg
     * @return bool
     */
    public function reissueAttrNo($itemId, $itemNo, &$errorMsg)
    {
        // item_attr
        $result = $this->reissueAttrNoByTableName($itemId, $itemNo, RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR);
        if($result === false)
        {
            $errorMsg .= "Cannot UPDATE attribute_no at ".RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR.".";
            return false;
        }

        // personal_name
        $result = $this->reissueAttrNoByTableName($itemId, $itemNo, RepositoryConst::DBTABLE_REPOSITORY_PERSONAL_NAME);
        if($result === false)
        {
            $errorMsg .= "Cannot UPDATE attribute_no at ".RepositoryConst::DBTABLE_REPOSITORY_PERSONAL_NAME.".";
            return false;
        }

        // biblio_info
        $result = $this->reissueAttrNoByTableName($itemId, $itemNo, RepositoryConst::DBTABLE_REPOSITORY_BIBLIO_INFO);
        if($result === false)
        {
            $errorMsg .= "Cannot UPDATE attribute_no at ".RepositoryConst::DBTABLE_REPOSITORY_BIBLIO_INFO.".";
            return false;
        }

        // thumbnail
//        $result = $this->reissueAttrNoByTableName($itemId, $itemNo, RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL);
//        if($result === false)
//        {
//            $errorMsg .= "Cannot UPDATE attribute_no at ".RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL.".";
//            return false;
//        }
//
//        // file and file_price
//        $result = $this->reissueAttrNoByTableName($itemId, $itemNo, RepositoryConst::DBTABLE_REPOSITORY_FILE);
//        if($result === false)
//        {
//            $errorMsg .= "Cannot UPDATE attribute_no at ".RepositoryConst::DBTABLE_REPOSITORY_FILE.".";
//            return false;
//        }

        return true;
    }

    /**
     * Reissue attribute no by table name
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $tableName
     * @return bool
     */
    private function reissueAttrNoByTableName($itemId, $itemNo, $tableName)
    {
        // Init
        $fileFlag = false;
        $itemIdColName = "";
        $itemNoColName = "";
        $attrIdColName = "";
        $attrNoColName = "";
        switch($tableName)
        {
            case RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR:
                $itemIdColName = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_ID;
                $itemNoColName = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_NO;
                $attrIdColName = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_ID;
                $attrNoColName = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_NO;
                break;
            case RepositoryConst::DBTABLE_REPOSITORY_PERSONAL_NAME:
                $itemIdColName = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_ID;
                $itemNoColName = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_NO;
                $attrIdColName = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ATTRIBUTE_ID;
                $attrNoColName = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_PERSONAL_NAME_NO;
                break;
            case RepositoryConst::DBTABLE_REPOSITORY_BIBLIO_INFO:
                $itemIdColName = RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_ID;
                $itemNoColName = RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_NO;
                $attrIdColName = RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ATTRIBUTE_ID;
                $attrNoColName = RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NO;
                break;
            case RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL:
                $itemIdColName = RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_ID;
                $itemNoColName = RepositoryConst::DBCOL_REPOSITORY_THUMB_ITEM_NO;
                $attrIdColName = RepositoryConst::DBCOL_REPOSITORY_THUMB_ATTR_ID;
                $attrNoColName = RepositoryConst::DBCOL_REPOSITORY_THUMB_FILE_NO;
                break;
            case RepositoryConst::DBTABLE_REPOSITORY_FILE:
                $fileFlag = true;
                $itemIdColName = RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID;
                $itemNoColName = RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO;
                $attrIdColName = RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID;
                $attrNoColName = RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO;
                break;
            default:
                $tableName = "";
                break;
        }
        if(strlen($tableName) == 0)
        {
            return false;
        }

        // New attribute_no counter
        $prevAttrId = 0;
        $newAttrNo = 1;

        // Set file and flash save path
        $contentsPath = "";
        $flashContentsPath = "";
        if($fileFlag)
        {
            // Get file and flash save path
            $contentsPath = $this->getFileSavePath("file");
            if(strlen($contentsPath) == 0){
                // default directory
                $contentsPath = BASE_DIR.'/webapp/uploads/repository/files';
                if(!(file_exists($contentsPath))){
                    mkdir ( $contentsPath, 0777);
                }
            }
            $flashContentsPath = $this->getFlashFolder();
        }

        // Get table record
        $query = "SELECT ".$attrIdColName.", ".$attrNoColName." ".
                 "FROM ".DATABASE_PREFIX.$tableName." ".
                 "WHERE ".$itemIdColName." = ? ".
                 "AND ".$itemNoColName." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ? ".
                 "ORDER BY ".$attrIdColName." ASC, ".$attrNoColName." ASC;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = 0;
        $records = $this->Db->execute($query, $params);
        if($records === false)
        {
            return false;
        }
        for($ii=0; $ii<count($records); $ii++)
        {
            // Get id and no
            $attrId = $records[$ii][$attrIdColName];
            $oldAttrNo = $records[$ii][$attrNoColName];
            if($attrId == $prevAttrId)
            {
                $newAttrNo++;
            }
            else
            {
                $newAttrNo = 1;
            }
            $prevAttrId = $attrId;

            // Compare attribute_no
            if($oldAttrNo == $newAttrNo)
            {
                // AttrNo is same.
                // No need to update
                continue;
            }

            // For file
            $selfExtension = "";
            $targetExtension = "";
            if($fileFlag)
            {
                // Get self extension
                $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION." ".
                         "FROM ".DATABASE_PREFIX.$tableName." ".
                         "WHERE ".$itemIdColName." = ? ".
                         "AND ".$itemNoColName." = ? ".
                         "AND ".$attrIdColName." = ? ".
                         "AND ".$attrNoColName." = ? ";
                $params = array();
                $params[] = $itemId;
                $params[] = $itemNo;
                $params[] = $attrId;
                $params[] = $oldAttrNo;
                $result = $this->Db->execute($query, $params);
                if($ret === false)
                {
                    return false;
                }
                if(isset($result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]))
                {
                    $selfExtension = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
                }

                // Get target extension
                $params = array();
                $params[] = $itemId;
                $params[] = $itemNo;
                $params[] = $attrId;
                $params[] = $newAttrNo;
                $result = $this->Db->execute($query, $params);
                if($ret === false)
                {
                    return false;
                }
                if(isset($result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION]))
                {
                    $targetExtension = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_EXTENSION];
                }
            }

            // Update attribute_no from newNo to tmpNo(0)
            $result = $this->updateAttrNoAndRenameFile(
                            $tableName, $itemIdColName, $itemNoColName, $attrIdColName, $attrNoColName,
                            $itemId, $itemNo, $attrId, $newAttrNo, 0, $targetExtension, $contentsPath, $flashContentsPath);
            if($result === false)
            {
                return false;
            }

            // Update attribute_no from oldNo to newNo
            $result = $this->updateAttrNoAndRenameFile(
                            $tableName, $itemIdColName, $itemNoColName, $attrIdColName, $attrNoColName,
                            $itemId, $itemNo, $attrId, $oldAttrNo, $newAttrNo, $selfExtension, $contentsPath, $flashContentsPath);
            if($result === false)
            {
                return false;
            }

            // Update attribute_no from tmpNo(0) to oldNo
            $result = $this->updateAttrNoAndRenameFile(
                            $tableName, $itemIdColName, $itemNoColName, $attrIdColName, $attrNoColName,
                            $itemId, $itemNo, $attrId, 0, $oldAttrNo, $targetExtension, $contentsPath, $flashContentsPath);
            if($result === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Update attribute_no and rename physical file
     *
     * @param string $tableName
     * @param string $itemIdColName
     * @param string $itemNoColName
     * @param string $attrIdColName
     * @param string $attrNoColName
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $oldAttrNo
     * @param int $newAttrNo
     * @param string $extension
     * @param string $contentsPath
     * @param string $flashContentsPath
     * @return bool
     */
    private function updateAttrNoAndRenameFile(
            $tableName, $itemIdColName, $itemNoColName, $attrIdColName, $attrNoColName,
            $itemId, $itemNo, $attrId, $oldAttrNo, $newAttrNo,
            $extension="", $contentsPath="", $flashContentsPath="")
    {
        // Update attribute_no from [oldNo] to [newNo]
        $query = "UPDATE ".DATABASE_PREFIX.$tableName." ".
                 "SET ".$attrNoColName." = ? ".
                 "WHERE ".$itemIdColName." = ? ".
                 "AND ".$itemNoColName." = ? ".
                 "AND ".$attrIdColName." = ? ".
                 "AND ".$attrNoColName." = ? ";
        $params = array();
        $params[] = $newAttrNo;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $params[] = $oldAttrNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        // Move physical file
        if($tableName == RepositoryConst::DBTABLE_REPOSITORY_FILE)
        {
            $filePath = $contentsPath.DIRECTORY_SEPARATOR.
                        $itemId.'_'.$attrId.'_'.$newAttrNo.'.'.$extension;
            $newFilePath = $contentsPath.DIRECTORY_SEPARATOR.
                           $itemId.'_'.$attrId.'_'.$newAttrNo.'.'.$extension;
            if(file_exists($filePath)){
                if( file_exists($newFilePath) ){
                    unlink($newFilePath);
                }
                rename($filePath, $newFilePath);
            }
            $flashPath = $flashContentsPath.DIRECTORY_SEPARATOR.
                         $itemId.'_'.$attrId.'_'.$newAttrNo;
            $newFlashPath = $flashContentsPath.DIRECTORY_SEPARATOR.
                            $itemId.'_'.$attrId.'_'.$newAttrNo;
            if(file_exists($flashPath)){
                if( file_exists($newFlashPath) ){
                    $this->removeDirectory($newFlashPath);
                }
                rename($flashPath, $newFlashPath);
            }

            // Set file_price
            $result = $this->updateAttrNoAndRenameFile(
                            RepositoryConst::DBTABLE_REPOSITORY_FILE_PRICE,
                            RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ITEM_ID,
                            RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ITEM_NO,
                            RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_ATTRIBUTE_ID,
                            RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_FILE_NO,
                            $itemId, $itemNo, $attrId, $oldAttrNo, $newAttrNo);
            if($result === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Required check
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $errorMsg
     * @param string $warningMsg
     * @return bool
     */
    public function requiredCheck($itemId, $itemNo, &$errorMsg, &$warningMsg)
    {
        // Init
        $errorMsg = "";
        $warningMsg = "";

        // 現在のアイテム情報取得
        $resultList = array();
        $result = $this->getItemData($itemId, $itemNo, $resultList, $errorMsg);
        if($result == false)
        {
            // Error
            return false;
        }

        // 所属インデックス取得
        $result = $this->getItemIndexData($itemId, $itemNo, $resultList, $errorMsg);
        if($result == false)
        {
            // Error
            return false;
        }

        // ---------------------------
        // アイテム基本情報
        // ---------------------------
        // アイテムの公開日を年月日に分解
        $pubYear = "";
        $pubMonth = "";
        $pubDay = "";
        //$shownDate =
        if( $resultList['item'][0]['shown_date'] != null &&
            $resultList['item'][0]['shown_date'] != '' )
        {
            $pubDate = split('[ ]', $resultList['item'][0]['shown_date']);
            list($pubYear, $pubMonth, $pubDay) = split('[/.-]', $pubDate[0]);
            if(strlen($pubYear)>0)
            {
                $pubYear = intval($pubYear);
            }
            if(strlen($pubMonth)>0)
            {
                $pubMonth = intval($pubMonth);
            }
            if(strlen($pubDay)>0)
            {
                $pubDay = intval($pubDay);
            }
        }

        $baseInfo = array(  "item_id" => $resultList['item'][0]['item_id'],
                            "item_no" => $resultList['item'][0]['item_no'],
                            "title" => $resultList['item'][0]['title'],
                            "title_english" => $resultList['item'][0]['title_english'],
                            "language" => $resultList['item'][0]['language'],
                            "pub_year" => $pubYear,
                            "pub_month" => $pubMonth,
                            "pub_day" => $pubDay);

        // ---------------------------
        // メタデータ情報
        // ---------------------------
        $itemAttrType = $resultList['item_attr_type'];
        $itemNumAttr = array_fill(0, count($itemAttrType), 0);
        $itemAttr = array_fill(0, count($itemAttrType), array());
        for ($ii=0; $ii<count($itemAttrType); $ii++)
        {
            // 必須項目の場合だけ必要なデータがあればいい
            if( $itemAttrType[$ii]['is_required']!=1){
                continue;
            }

            // 属性未登録のメタデータは初期設定でOK
            if(count($resultList['item_attr'][$ii]) <= 0) {
                continue;
            }
            for($attrCnt=0; $attrCnt<count($resultList['item_attr'][$ii]); $attrCnt++)
            {
                $itemNumAttr[$ii]++;
                if( $itemAttrType[$ii]['input_type']=='file' ||
                    $itemAttrType[$ii]['input_type']=='file_price' ||
                    $itemAttrType[$ii]['input_type']=='thumbnail')
                {
                    // upload情報を作成
                    $upload = array(
                        'file_name' => $resultList['item_attr'][$ii][$attrCnt]['file_name'],
                        'mimetype' => $resultList['item_attr'][$ii][$attrCnt]['mime_type'],
                        'extension' => $resultList['item_attr'][$ii][$attrCnt]['extension']
                    );
                    $itemAttr[$ii][$attrCnt]["upload"] = $upload;

                    // ファイル、課金ファイルのみ
                    if( $itemAttrType[$ii]['input_type']=='file' ||
                        $itemAttrType[$ii]['input_type']=='file_price')
                    {
                        // エンバーゴ
                        $pubYear = "";
                        $pubMonth = "";
                        $pubDay = "";
                        $enbargoFlag = 2;
                        if( $resultList['item_attr'][$ii][$attrCnt]["pub_date"] != null &&
                            $resultList['item_attr'][$ii][$attrCnt]["pub_date"] != '')
                        {
                            $pubDate = split('[ ]', $resultList['item_attr'][$ii][$attrCnt]["pub_date"]);
                            list($pubYear, $pubMonth, $pubDay) = split('[/.-]', $pubDate[0]);
                            if(strlen($pubYear)>0)
                            {
                                $pubYear = intval($pubYear);
                            }
                            if(strlen($pubMonth)>0)
                            {
                                $pubMonth = intval($pubMonth);
                            }
                            if(strlen($pubDay)>0)
                            {
                                $pubDay = intval($pubDay);
                            }
                        }

                        if($pubYear == "9999" && $pubMonth == "1" && $pubDay == "1"){
                            // 会員のみの場合
                            $enbargoFlag = 3;
                        }
                        else if($pubYear == "9999" && $pubMonth == "12" && $pubDay == "31"){
                            // ダウンロード不可
                            $enbargoFlag = 4;
                        }
                        $itemAttr[$ii][$attrCnt]["embargo_year"] = $pubYear;
                        $itemAttr[$ii][$attrCnt]["embargo_month"] = $pubMonth;
                        $itemAttr[$ii][$attrCnt]["embargo_day"] = $pubDay;
                        $itemAttr[$ii][$attrCnt]["embargo_flag"] = $enbargoFlag;
                    }
                }
                else if($itemAttrType[$ii]['input_type']=='name')
                {
                    $itemAttr[$ii][$attrCnt]["family"] = $resultList['item_attr'][$ii][$attrCnt]['family'];
                }
                else if($itemAttrType[$ii]['input_type']=='checkbox')
                {
                    $itemAttr[$ii][$attrCnt] = "0";
                    if(strlen($resultList['item_attr'][$ii][$attrCnt]["attribute_value"]) > 0)
                    {
                        $itemAttr[$ii][$attrCnt] = "1";
                    }
                }
                else if($itemAttrType[$ii]['input_type']=='biblio_info')
                {
                    $itemAttr[$ii][$attrCnt]["biblio_name"] = $resultList['item_attr'][$ii][$attrCnt]['biblio_name'];
                    $itemAttr[$ii][$attrCnt]["biblio_name_english"] = $resultList['item_attr'][$ii][$attrCnt]['biblio_name_english'];
                    $itemAttr[$ii][$attrCnt]["volume"] = $resultList['item_attr'][$ii][$attrCnt]['volume'];
                    $itemAttr[$ii][$attrCnt]["issue"] = $resultList['item_attr'][$ii][$attrCnt]['issue'];
                    $itemAttr[$ii][$attrCnt]["spage"] = $resultList['item_attr'][$ii][$attrCnt]['start_page'];
                    $itemAttr[$ii][$attrCnt]["epage"] = $resultList['item_attr'][$ii][$attrCnt]['end_page'];
                    $itemAttr[$ii][$attrCnt]["date_of_issued"] = $resultList['item_attr'][$ii][$attrCnt]['date_of_issued'];

                }
                else if($itemAttrType[$ii]['input_type']=='date')
                {
                    $itemAttr[$ii][$attrCnt]["date"] = $resultList['item_attr'][$ii][$attrCnt]["attribute_value"];
                }
                else
                {
                    $itemAttr[$ii][$attrCnt] = $resultList['item_attr'][$ii][$attrCnt]["attribute_value"];
                }
            }
        }

        // チェック実施
        $itemRegister = new ItemRegister($this->Session, $this->Db);
        $tmpErrorMsg = array();

        // checkBaseInfo
        $result = $itemRegister->checkBaseInfo($baseInfo, $tmpErrorMsg, $warningMsg);
        if(!$result)
        {
            $errorMsg = implode("/", $tmpErrorMsg);
            return false;
        }

        // checkEntryInfo
        $result = $itemRegister->checkEntryInfo($itemAttrType, $itemNumAttr, $itemAttr, 'all', $tmpErrorMsg, $warningMsg);
        if(!$result)
        {
            $errorMsg = implode("/", $tmpErrorMsg);
            return false;
        }
        // checkIndex
        $result = $itemRegister->checkIndex($resultList["position_index"], $tmpErrorMsg, $warningMsg);
        if(!$result)
        {
            $errorMsg = implode("/", $tmpErrorMsg);
            return false;
        }

        if(count($tmpErrorMsg) == 0){
            // entry OK
            return true;
        } else {
            // entry NG
            $errorMsg = implode("/", $tmpErrorMsg);
            return false;
        }
    }

    /**
     * create RepositoryHandleManager instance
     *
     */
    private function getRepositoryHandleManager()
    {
        if(!isset($this->repositoryHandleManager))
        {
            if(!isset($this->TransStartDate) || strlen($this->TransStartDate) == 0)
            {
                $DATE = new Date();
                $this->TransStartDate = $DATE->getDate(). ".000";
            }
            $this->repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        }
    }
    
    // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --start--
    /**
     * correct attribute_id of item_type data array
     *
     * @param int(i ) $itemTypeId
     * @param array(io) $xmlItemTypeData
     */
    public function validateItemTypeXmlData($itemTypeId, &$xmlItemTypeData)
    {
        // 修正用のデータ配列(逐次処理で修正していくと修正済みと修正前の見分けがつかないため)
        $candidateArray = array();
        
        // validate $xmlItemTypeData['item_attr_type_array']
        foreach($xmlItemTypeData['item_attr_type_array'] as $elm => $valArray)
        {
            // XML内に記述された属性IDを保持し、$xmlItemTypeData内のデータも修正する
            $oldAttrId = $valArray['ATTRIBUTE_ID'];
            $inputType = $valArray['INPUT_TYPE'];
            $showOrder = $valArray['SHOW_ORDER'];
            
            // show_orderとアイテムタイプIDで属性IDを特定
            $newAttrId = $this->getAttrIdByShowOrderAndItemTypeId($showOrder, $itemTypeId);
            $xmlItemTypeData['item_attr_type_array'][$elm]['ATTRIBUTE_ID'] = $newAttrId;
            
            // 修正対象の選択肢候補を取得する
            if(strpos($inputType, "select") == 0 || 
               strpos($inputType, "checkbox") == 0 || 
               strpos($inputType, "radio") == 0)
            {
                $candidateArray[] = array('oldAttrId' => $oldAttrId, 'newAttrId' => $newAttrId);
            }
        }
        
        // 選択肢候補の修正
        for($cnt = 0; $cnt < count($xmlItemTypeData['item_attr_candidate_array']); $cnt++)
        {
            for($changeCnt = 0; $changeCnt < count($candidateArray); $changeCnt++)
            {
                if(intval($xmlItemTypeData['item_attr_candidate_array'][$cnt]['ATTRIBUTE_ID']) == intval($candidateArray[$changeCnt]['oldAttrId']))
                {
                    $xmlItemTypeData['item_attr_candidate_array'][$cnt]['ATTRIBUTE_ID'] = $candidateArray[$changeCnt]['newAttrId'];
                    break;
                }
            }
        }
    }
    
    /**
     * get attribute id by show order and item_type_id
     *
     * @param int $showOrder
     * @param int $itemTypeId
     * @return int attribute_id
     */
    private function getAttrIdByShowOrderAndItemTypeId($showOrder, $itemTypeId)
    {
        $query = "SELECT attribute_id ". 
                 " FROM ". DATABASE_PREFIX. "repository_item_attr_type ". 
                 " WHERE item_type_id = ? ". 
                 " AND show_order = ?;";
        
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $showOrder;
        
        $result = $this->dbAccess->executeQuery($query, $params);
        
        return $result[0]["attribute_id"];
    }
    // Bug Fix WEKO-2014-046 T.Koyasu 2014/08/07 --end--
}
?>
