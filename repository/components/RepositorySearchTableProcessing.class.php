<?php
// --------------------------------------------------------------------
//
// $Id: RepositorySearchTableProcessing.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryProcessUtility.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/QueryGenerator.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryPluginManager.class.php';

/**
 * class of input data to search table
 */
class RepositorySearchTableProcessing extends RepositoryAction
{
    // all metadata key
    const ALLMETADATA = "allMetaData";
    // all metadata table name
    const ALLMETADATA_TABLE = "repository_search_allmetadata";
    // filedata key
    const FILEDATA = "fileData";
    // filedata table name
    const FILEDATA_TABLE = "repository_search_filedata";
    // title key
    const TITLE = "title";
    // title key
    const ALTER_TITLE = "alternative";
    // title table name
    const TITLE_TABLE = "repository_search_title";
    // author key
    const AUTHOR = "creator";
    // author table name
    const AUTHOR_TABLE = "repository_search_author";
    // keyword key
    const KEYWORD = "subject";
    // keyword table name
    const KEYWORD_TABLE = "repository_search_keyword";
    // NIIsubject key
    const NIISUBJECT = "NIIsubject";
    // NIIsubject table name
    const NIISUBJECT_TABLE = "repository_search_niisubject";
    // NDC key
    const NDC = "NDC";
    // NDC table name
    const NDC_TABLE = "repository_search_ndc";
    // NDLC key
    const NDLC = "NDLC";
    // NDLC table name
    const NDLC_TABLE = "repository_search_ndlc";
    // BSH key
    const BSH = "BSH";
    // BSH table name
    const BSH_TABLE = "repository_search_bsh";
    // NDLSH key
    const NDLSH = "NDLSH";
    // NDLSH table name
    const NDLSH_TABLE = "repository_search_ndlsh";
    // MeSH key
    const MESH = "MeSH";
    // MeSH table name
    const MESH_TABLE = "repository_search_mesh";
    // DDC key
    const DDC = "DDC";
    // DDC table name
    const DDC_TABLE = "repository_search_ddc";
    // LCC key
    const LCC = "LCC";
    // LCC table name
    const LCC_TABLE = "repository_search_lcc";
    // UDC key
    const UDC = "UDC";
    // UDC table name
    const UDC_TABLE = "repository_search_udc";
    // LCSH key
    const LCSH = "LCSH";
    // LCSH table name
    const LCSH_TABLE = "repository_search_lcsh";
    // description key
    const DESCTIPTION = "description";
    // description table name
    const DESCTIPTION_TABLE = "repository_search_description";
    // publisher key
    const PUBLISHER = "publisher";
    // publisher table name
    const PUBLISHER_TABLE = "repository_search_publisher";
    // contributor key
    const CONTRIBUTOR = "contributor";
    // contributor table name
    const CONTRIBUTOR_TABLE = "repository_search_contributor";
    // date key
    const DATE = "date";
    // date table name
    const DATE_TABLE = "repository_search_date";
    // type key
    const TYPE = "type";
    // type table name
    const TYPE_TABLE = "repository_search_type";
    // format key
    const FORMAT = "format";
    // format table name
    const FORMAT_TABLE = "repository_search_format";
    // identifer key
    const IDENTIFER = "identifier";
    // identifer table name
    const IDENTIFER_TABLE = "repository_search_identifier";
    // URI key
    const URI = "URI";
    // URI table name
    const URI_TABLE = "repository_search_uri";
    // fulltextURL key
    const FULLTEXTURL = "fullTextURL";
    // fulltextURL table name
    const FULLTEXTURL_TABLE = "repository_search_fulltexturl";
    // selfDOI key
    const SELFDOI = "selfDOI";
    // selfDOI table name
    const SELFDOI_TABLE = "repository_search_selfdoi";
    // ISBN key
    const ISBN = "isbn";
    // ISBN table name
    const ISBN_TABLE = "repository_search_isbn";
    // ISSN key
    const ISSN = "issn";
    // ISSN table name
    const ISSN_TABLE = "repository_search_issn";
    // NCID key
    const NCID = "NCID";
    // NCID table name
    const NCID_TABLE = "repository_search_ncid";
    // pmid key
    const PMID = "pmid";
    // pmid table name
    const PMID_TABLE = "repository_search_pmid";
    // doi key
    const DOI = "doi";
    // doi table name
    const DOI_TABLE = "repository_search_doi";
    // NAID key
    const NAID = "NAID";
    // NAID table name
    const NAID_TABLE = "repository_search_naid";
    // ichushi key
    const ICHUSHI = "ichushi";
    // ichushi table name
    const ICHUSHI_TABLE = "repository_search_ichushi";
    // jtitle key
    const JTITLE = "jtitle";
    // jtitle table name
    const JTITLE_TABLE = "repository_search_jtitle";
    // dateofissued key
    const DATAODISSUED = "dateofissued";
    // dateofissued table name
    const DATAODISSUED_TABLE = "repository_search_dateofissued";
    // language key
    const LANGUAGE = "language";
    // language table name
    const LANGUAGE_TABLE = "repository_search_language";
    // relation key
    const SPATIAL = "spatial";
    // relation key
    const NIISPATIAL = "NIIspatial";
    // relation table name
    const RELATION_TABLE = "repository_search_relation";
    // coverage key
    const TEMPORAL = "temporal";
    // coverage key
    const NIITEMPORAL = "NIItemporal";
    // coverage table name
    const COVERAGE_TABLE = "repository_search_coverage";
    // rights key
    const RIGHTS = "rights";
    // rights table name
    const RIGHTS_TABLE = "repository_search_rights";
    // textversion key
    const TEXTVERSION = "textversion";
    // textversion table name
    const TEXTVERSION_TABLE = "repository_search_textversion";
    // grantid key
    const GRANTID = "grantid";
    // grantid table name
    const GRANTID_TABLE = "repository_search_grantid";
    // dateofgranted key
    const DATEOFGRANTED = "dateofgranted";
    // dateofgranted table name
    const DATEOFGRANTED_TABLE = "repository_search_dateofgranted";
    // degreename key
    const DEGREENAME = "degreename";
    // degreename table name
    const DEGREENAME_TABLE = "repository_search_degreename";
    // grantor key
    const GRANTOR = "grantor";
    // grantor table name
    const GRANTOR_TABLE = "repository_search_grantor";
    // dateofissued key
    const DATAODISSUED_YMD = "shown_date";
    // dateofissued table name
    const DATAODISSUED_YMD_TABLE = "repository_search_dateofissued_ymd";
    // plugin parameter name
    const SEARCH_QUERY_COLUMN = "search_query_plugin";

    /**
     * Relation between mapping and a table
     *
     * @var array
     */
    private $mappingTableRelation = null;
    
    /**
     * Instance of RepositoryHandleManager
     * 
     * @var classObject
     */
    private $repositoryHandleManager = null;
    
    /**
     * Instance of query generator
     * 
     * @var classObject
     */
    private $queryGenerator = null;
    
    /**
     * plugin exist flag
     * 
     * @var bool
     */
    private $pluginFlag = false;
    
    
    /**
     * constructer
     */
    public function __construct($Session, $db)
    {
        $this->Session = $Session;
        $this->Db = $db;
        $this->dbAccess = new RepositoryDbAccess($db);
        $this->setMappingTableRelation();
        // Add query manager 2014/08/21 T.Ichikawa --start--
        $pluginManager = new RepositoryPluginmanager($this->Session, $this->dbAccess, $this->TransStartDate);
        $this->queryGenerator = $pluginManager->getPlugin(RepositoryPluginManager::SEARCH_QUERY_COLUMN);
        if(isset($this->queryGenerator)) {
            $this->pluginFlag = true;
        } else {
            $this->queryGenerator = new Repository_Components_Querygenerator(DATABASE_PREFIX);
        }
        // Add query manager 2014/08/21 T.Ichikawa --end--
    }

    /**
     * update and insert searchtables by all item
     */
    public function updateSearchTableForAllItem()
    {
        if($this->pluginFlag) {
            // プラグインが存在するなら新テーブルの作成処理を行う
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
            $queryList = $this->queryGenerator->createPluginTableQuery($isMroongaExist);
            for($ii = 0; $ii < count($queryList); $ii++) {
                $this->dbAccess->executeQuery($queryList[$ii]["query"]);
            }
        }
        // insert no delete item to update item table
        $query = "INSERT IGNORE INTO ". DATABASE_PREFIX. "repository_search_update_item ".
                 " ( SELECT item_id, item_no ".
                 "   FROM ". DATABASE_PREFIX. "repository_item ".
                 "   WHERE is_delete = ? ) ;";

        $params = array();
        $params[] = 0;
        $this->dbAccess->executeQuery($query, $params);
        // execute background
        $this->callAsyncProcess();
    }

    /**
     * update and insert searchtables the item by which itemtype was changed.
     *
     * @param itemtype_id chenged itemtype id
     */
    public function updateSearchTableForItemtype($itemtype_id)
    {
        // insert no delete item to update item table
        $query = "INSERT IGNORE INTO ". DATABASE_PREFIX. "repository_search_update_item ".
                 " ( SELECT item_id, item_no ".
                 "   FROM ". DATABASE_PREFIX. "repository_item ".
                 "   WHERE item_type_id = ? ".
                 "   AND is_delete = ? ) ;";
        $params = array();
        $params[] = $itemtype_id;
        $params[] = 0;
        $this->dbAccess->executeQuery($query, $params);
        // execute background
        $this->callAsyncProcess();
    }

    /**
     * update and insert searchtables the item by which item was changed.
     *
     * @param item_id chenged item id
     * @param item_no chenged item no
     */
    public function updateSearchTableForItem($item_id, $item_no)
    {
        // create update data
        $itemData = array();
        $itemData["item_id"] = $item_id;
        $itemData["item_no"] = $item_no;
        $param = array();
        array_push($param, $itemData);
        // execute update
        $this->setDataToSearchTable($param);
    }

    /**
     * delete records from search table
     */
    public function deleteDataFromSearchTable()
    {
        if($this->pluginFlag) {
            // プラグインが存在するならそちらの処理を行う
            $queryList = $this->queryGenerator->createDeletedDataFromSearchTableQuery();
            for($ii = 0; $ii < count($queryList); $ii++) {
                $this->dbAccess->executeQuery($queryList[$ii]["query"], $queryList[$ii]["params"]);
            }
        } else {
            // insert no delete item to update item table
            foreach($this->mappingTableRelation as $key => $value){
                $query = "DELETE FROM ".DATABASE_PREFIX.$key." ".
                         "WHERE (item_id, item_no) IN ( ".
                         " SELECT item_id, item_no ".
                         " FROM ". DATABASE_PREFIX. "repository_item ".
                         " WHERE is_delete = ? );";

                $params = array();
                $params[] = 1;
                $this->dbAccess->executeQuery($query, $params);
            }

            $query = "DELETE FROM ".DATABASE_PREFIX."repository_search_sort ".
                     "WHERE (item_id, item_no) IN ( ".
                     " SELECT item_id, item_no ".
                     " FROM ". DATABASE_PREFIX. "repository_item ".
                     " WHERE is_delete = ? );";

            $params = array();
            $params[] = 1;
            $this->dbAccess->executeQuery($query, $params);
        }
    }

    /**
     * delete records from search table
     */
    private function deleteDataFromSearchTableByItemList($itemList)
    {
        if($this->pluginFlag) {
            // プラグインが存在するならそちらの処理を行う
            $queryList = $this->queryGenerator->createDeletedDataFromSearchTableByItemListQuery($itemList);
            for($ii = 0; $ii < count($queryList); $ii++) {
                $this->dbAccess->executeQuery($queryList[$ii]["query"], $queryList[$ii]["params"]);
            }
        } else {
            $inQuery = "";
            $params = array();
            for($ii = 0; $ii < count($itemList); $ii++){
                $inQuery .= "(?,?)";
                if($ii < count($itemList) - 1){
                    $inQuery .= ",";
                }
                $params[] = $itemList[$ii]["item_id"];
                $params[] = $itemList[$ii]["item_no"];
            }
            // insert no delete item to update item table
            foreach($this->mappingTableRelation as $key => $value){
                $query = "DELETE FROM ".DATABASE_PREFIX.$key." ".
                         "WHERE (item_id, item_no) IN (".$inQuery.");";
                $this->dbAccess->executeQuery($query, $params);
            }
            
            $query = "DELETE FROM ".DATABASE_PREFIX."repository_search_sort ".
                     "WHERE (item_id, item_no) IN (".$inQuery.");";
            
            $this->dbAccess->executeQuery($query, $params);
        }
    }
    
    // Add for OpenDepo R.Matsuura 2014/03/28 --start--
    /**
     * delete one record from search table
     */
    public function deleteDataFromSearchTableByItemId($item_id, $item_no)
    {
        $itemList = array();
        $itemList[0]["item_id"] = $item_id;
        $itemList[0]["item_no"] = $item_no;
        
        $this->deleteDataFromSearchTableByItemList($itemList);
    }
    // Add for OpenDepo R.Matsuura 2014/03/28 --end--
    
    /**
     * unsert search table in repository_search_update_item item
     *
     * @param itemList (item_id, item_no) List
     */
    public function setDataToSearchTable($itemList)
    {
        $this->deleteDataFromSearchTableByItemList($itemList);
        
        // input update item data
        $searchAllInfo = array();
        $sortAllInfo = array();
        for($ii = 0; $ii < count($itemList); $ii++){
            // get item data
            $searchItemInfo = array();
            $searchItemInfo["item_id"] = $itemList[$ii]["item_id"];
            $searchItemInfo["item_no"] = $itemList[$ii]["item_no"];
            $sortItemInfo = array();
            $sortItemInfo["item_id"] = $itemList[$ii]["item_id"];
            $sortItemInfo["item_no"] = $itemList[$ii]["item_no"];
            $result = $this->getItemData($itemList[$ii]["item_id"], $itemList[$ii]["item_no"], $itemInfo, $errMsg);
            if($result === false){
                $this->failTrans();
                $exception = new RepositoryException( "ERR_MSG_Failed", 00001 );
                throw $exception;
            } else if(count($itemInfo["item"]) == 0) {
                // item deleted
                continue;
            }
            // input item base data
            $this->addBaseData($itemInfo["item"][0], $searchItemInfo, $sortItemInfo);
            // input item meta data
            for($jj = 0; $jj < count($itemInfo["item_attr_type"]); $jj++){
                $this->addInputData($itemInfo["item_attr"][$jj], $itemInfo["item_attr_type"][$jj],
                                    $searchItemInfo, $sortItemInfo);
            }
            // item shown status
            $searchItemInfo["shown_status"] = $itemInfo["item"][0]["shown_status"];
            
            array_push($searchAllInfo, $searchItemInfo);
            array_push($sortAllInfo, $sortItemInfo);
        }
        // add records to search tables
        if (count($searchAllInfo) > 0 || count($sortAllInfo) > 0 ) {
            $this->insertSearchTable($searchAllInfo, $sortAllInfo);
        }
        return true;
    }

    /**
     * Item basic information is set as search information
     *
     * @param itemBaseInfo item base infomation
     * @param searchItemInfo search data
     * @param sortItemInfo sort data
     */
    private function addBaseData($itemBaseInfo, &$searchItemInfo, &$sortItemInfo)
    {
        // set title data
        $this->setTextData($searchItemInfo, self::TITLE, $itemBaseInfo["title"]);
        $this->setTextData($searchItemInfo, self::TITLE, $itemBaseInfo["title_english"]);
        $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemBaseInfo["title"]);
        $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemBaseInfo["title_english"]);
        $sortItemInfo["title"] = $itemBaseInfo["title"];
        $sortItemInfo["title_en"] = $itemBaseInfo["title_english"];
        // set shown date data
        $this->setTextData($searchItemInfo, self::DATE, $itemBaseInfo["shown_date"]);
        $this->setTextData($searchItemInfo, self::DATAODISSUED_YMD, $itemBaseInfo["shown_date"]);
        // set keyword data
        $this->setTextData($searchItemInfo, self::KEYWORD, $itemBaseInfo["serch_key"]);
        $this->setTextData($searchItemInfo, self::KEYWORD, $itemBaseInfo["serch_key_english"]);
        $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemBaseInfo["serch_key"]);
        $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemBaseInfo["serch_key_english"]);
        // set language data
        $this->setTextData($searchItemInfo, self::LANGUAGE, $itemBaseInfo["language"]);
        // set itemtype data
        $sortItemInfo["item_type_id"] = $itemBaseInfo["item_type_id"];
        // set uri data
        if(strpos($itemBaseInfo["uri"], "repository_uri")){
            $sortItemInfo["uri"] = $itemBaseInfo["uri"];
            $sortItemInfo["weko_id"] = "1".sprintf("%08d", $itemBaseInfo["item_id"]);
        } else {
            $sortItemInfo["uri"] = $itemBaseInfo["uri"];
            $sortItemInfo["weko_id"] = "0".sprintf("%08d", $itemBaseInfo["item_id"]);
        }
        // set review_date data
        $sortItemInfo["review_date"] = $itemBaseInfo["review_date"];
        // set ins_user data
        $sortItemInfo["ins_user_id"] = $itemBaseInfo["ins_user_id"];
        // set mod_date data
        $sortItemInfo["mod_date"] = $itemBaseInfo["mod_date"];
        // set ins_date data
        $sortItemInfo["ins_date"] = $itemBaseInfo["ins_date"];
    }

    /**
     * Item metadata information is set as search information
     *
     * @param itemMetaData item metadata infomation
     * @param itemTypeMetaData itemtype metadata infomation
     * @param searchItemInfo search data
     * @param sortItemInfo sort data
     */
    private function addInputData($itemMetaData, $itemTypeMetaData, &$searchItemInfo, &$sortItemInfo )
    {
        // set item metadata
        for($ii = 0; $ii < count($itemMetaData); $ii++){
            switch($itemTypeMetaData["input_type"])
            {
                case "name":
                    $addText = $itemMetaData[$ii]["family"].",".$itemMetaData[$ii]["name"].
                               ",".$itemMetaData[$ii]["family_ruby"].",".$itemMetaData[$ii]["name_ruby"].
                               ",".$itemMetaData[$ii]["e_mail_address"];
                    $idList = $this->getSuffixId($itemMetaData[$ii]["author_id"]);
                    for($jj = 0; $jj < count($idList); $jj++){
                        $addText .= ",".$idList[$jj]["suffix"];
                    }
                    if(isset($itemTypeMetaData["junii2_mapping"]) && strlen($itemTypeMetaData["junii2_mapping"]) != 0){
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $addText);
                    }
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $addText);
                    break;
                case "thumbnail":
                    if(isset($itemTypeMetaData["junii2_mapping"]) && strlen($itemTypeMetaData["junii2_mapping"]) != 0){
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $itemMetaData[$ii]["file_name"]);
                    }
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["file_name"]);
                    break;
                case "file":
                case "file_price":
                    if(isset($itemTypeMetaData["junii2_mapping"]) && strlen($itemTypeMetaData["junii2_mapping"]) != 0){
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $itemMetaData[$ii]["file_name"]);
                    }
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["file_name"]);
                    $this->addFileData($itemMetaData[$ii], $searchItemInfo);
                    
                    // Add free style license to search_rights table T.Koyasu 2014/06/10 --start--
                    if($itemMetaData[$ii]["license_id"] === "0"){
                        $this->setTextData($searchItemInfo, self::RIGHTS, $itemMetaData[$ii]["license_notation"]);
                        $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["license_notation"]);
                    }
                    // Add free style license to search_rights table T.Koyasu 2014/06/10 --end--
                    
                    break;
                case "biblio_info":
                    $addText = $itemMetaData[$ii]["biblio_name"].",".$itemMetaData[$ii]["biblio_name_english"].",".$itemMetaData[$ii]["date_of_issued"];
                    $this->setTextData($searchItemInfo, self::JTITLE, $itemMetaData[$ii]["biblio_name"]);
                    $this->setTextData($searchItemInfo, self::JTITLE, $itemMetaData[$ii]["biblio_name_english"]);
                    $this->setTextData($searchItemInfo, self::DATAODISSUED, $itemMetaData[$ii]["date_of_issued"]);
                    // Fix Don't fill biblio_date at sort table. Y.Nakao 2014/03/26 --start--
                    if(!isset($sortItemInfo["biblio_date"]))
                    {
                        $this->setTextData($sortItemInfo, "biblio_date", $itemMetaData[$ii]["date_of_issued"]);
                    }
                    // Fix Don't fill biblio_date at sort table. Y.Nakao 2014/03/26 --end--
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $addText);
                    break;
                case "supple":
                    if(isset($itemTypeMetaData["junii2_mapping"]) && strlen($itemTypeMetaData["junii2_mapping"]) != 0){
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $itemMetaData[$ii]["supple_title"]);
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $itemMetaData[$ii]["supple_title_en"]);
                    }
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["supple_title"]);
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["supple_title_en"]);
                    break;
                default:
                    if(isset($itemTypeMetaData["junii2_mapping"]) && strlen($itemTypeMetaData["junii2_mapping"]) != 0){
                        $this->setTextData($searchItemInfo, $itemTypeMetaData["junii2_mapping"], $itemMetaData[$ii]["attribute_value"]);
                        // Fix Don't fill biblio_date at sort table. Y.Nakao 2014/03/26 --start--
                        if(!isset($sortItemInfo["biblio_date"]) && $itemTypeMetaData["junii2_mapping"] == self::DATAODISSUED)
                        {
                            $this->setTextData($sortItemInfo, "biblio_date", $itemMetaData[$ii]["attribute_value"]);
                        }
                        // Fix Don't fill biblio_date at sort table. Y.Nakao 2014/03/26 --end--
                    }
                    $this->setTextData($searchItemInfo, self::ALLMETADATA, $itemMetaData[$ii]["attribute_value"]);
                    break;
            }
        }
    }

    /**
     * The contents of a file are set as search information
     *
     * @param itemMetaData item metadata infomation
     * @param searchItemInfo search data
     */
    private function addFileData($itemMetaData, &$searchItemInfo)
    {
        $strFullText = "";
        ////////////////////////////////////////////////////////////
        // ファイルから文字列を抽出し、検索用文字列を作成する
        ////////////////////////////////////////////////////////////
        // 作業dir作成
        //$date = date('YmdHis') . substr(microtime(), 1, 4);
        $query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
        $result = $this->dbAccess->executeQuery($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $date = $result[0]['now_date'];
        $dir_path = WEBAPP_DIR."/uploads/repository/_".$date;
        mkdir($dir_path, 0777 );
        
        // Fix processing order correcting. 2014/03/15 Y.Nakao --start--
        // ファイルをコピーする
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // check directory exists
        if( !(file_exists($contents_path)) ){
            // delete tmp directory
            $this->removeDirectory($dir_path);
            return false;
        }
        $file_name = $itemMetaData['item_id'].'_'.
                    $itemMetaData['attribute_id'].'_'.
                    $itemMetaData['file_no'].'.'.
                    $itemMetaData['extension'];
        $copyResult = copy( $contents_path.DIRECTORY_SEPARATOR.$file_name,
                            $dir_path. DIRECTORY_SEPARATOR.$file_name);
        if(!$copyResult){
            // delete tmp directory
            $this->removeDirectory($dir_path);
            return false;
        }
        // Fix processing order correcting. 2014/03/15 Y.Nakao --end--
        
        $txt = "";
        // 外部コマンドパス設定読込み追加 2008/08/07 Y.Nakao --start--
        // wvWare
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'path_wvWare';";
        $ret = $this->dbAccess->executeQuery($query);
        if(count($ret) > 0){
            $path_wvWare = $ret[0]['param_value'];
        } else {
            $path_wvWare = "";
        }
        // xlhtml
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'path_xlhtml';";
        $ret = $this->dbAccess->executeQuery($query);
        if(count($ret) > 0){
            $path_xlhtml = $ret[0]['param_value'];
        } else {
            $path_xlhtml = "";
        }
        // poppler
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'path_poppler';";
        $ret = $this->dbAccess->executeQuery($query);
        if(count($ret) > 0){
            $path_poppler = $ret[0]['param_value'];
        } else {
            $path_poppler = "";
        }
        // Fix processing order correcting. 2014/03/15 Y.Nakao
        // Add separate file from DB 2009/04/21 Y.Nakao --start--

        $txt = "";
        // ファイルがpdf・xls・docの場合
        if ( ( $itemMetaData['mime_type'] == 'application/pdf' ||
               $itemMetaData['mime_type'] == 'application/vnd.ms-excel' ||
               $itemMetaData['mime_type'] == 'application/msword' ||
               $itemMetaData['mime_type'] == 'text/pdf')) {
            // 生成ファイルからTEXT抽出
            $cmd = null;
            // pdfの場合
            if ($itemMetaData['mime_type'] == 'application/pdf' || $itemMetaData['mime_type'] == 'text/pdf') {
                $cmd = "\"". $path_poppler. "pdftotext\" -enc UTF-8 ". $dir_path. DIRECTORY_SEPARATOR.$file_name. " ". $dir_path. DIRECTORY_SEPARATOR. "pdf.txt";
                //print($cmd. "<br>");
                exec($cmd);
                if (file_exists($dir_path. DIRECTORY_SEPARATOR. "pdf.txt")) {
                    $txt = file($dir_path. DIRECTORY_SEPARATOR. "pdf.txt");
                    //$txt = implode("", $txt);
                    $strFullText = implode("", $txt);
                    unlink($dir_path. DIRECTORY_SEPARATOR. "pdf.txt");
                }
            }
            // xlsの場合
            else if ($itemMetaData['mime_type'] == 'application/vnd.ms-excel') {
                $cmd = "\"". $path_xlhtml. "xlhtml\" ". $dir_path. DIRECTORY_SEPARATOR.$file_name. " > ". $dir_path. DIRECTORY_SEPARATOR. "xls.html";
                //print($cmd. "<br>");
                exec($cmd);
                if (file_exists($dir_path. DIRECTORY_SEPARATOR. "xls.html")) {
                    $txt = file($dir_path. DIRECTORY_SEPARATOR. "xls.html");
                    $txt = implode("", $txt);
                    //$txt = strip_tags($txt);
                    $strFullText = strip_tags($txt);
                    unlink($dir_path. DIRECTORY_SEPARATOR. "xls.html");
                }
            }
            // docの場合
            else if ($itemMetaData['mime_type'] == 'application/msword') {
                $cmd = "\"". $path_wvWare. "wvHtml\" ". $dir_path. DIRECTORY_SEPARATOR.$file_name. " ". $dir_path. DIRECTORY_SEPARATOR. "doc.html";
                //print($cmd. "<br>");
                exec($cmd);
                if (file_exists($dir_path. DIRECTORY_SEPARATOR. "doc.html")) {
                    $txt = file($dir_path. DIRECTORY_SEPARATOR. "doc.html");
                    $txt = implode("", $txt);
                    //$txt = strip_tags($txt);
                    $strFullText = strip_tags($txt);
                    unlink($dir_path. DIRECTORY_SEPARATOR. "doc.html");
                }
            }
        }
        // ファイルがテキスト類の場合
        else if ( is_numeric(strpos($itemMetaData['mime_type'], "text")) ) {
            $fp = fopen($dir_path. DIRECTORY_SEPARATOR.$file_name, "r");
            if($fp == null){
                return;
            }
            while( ! feof( $fp ) ){
                $line = fgets( $fp );
                $mojicode = mb_detect_encoding($line);
                if(strtoupper($mojicode) != 'UTF-8')
                {
                    $line= mb_convert_encoding($line, "UTF-8", $mojicode);
                }
                $txt .= $line;
            }
            fclose($fp);
            $strFullText = $txt;
            unlink($dir_path. DIRECTORY_SEPARATOR.$file_name);
        }
        // ppt
        else if($itemMetaData['mime_type'] == 'application/vnd.ms-powerpoint'){
            $cmd = "\"". $path_xlhtml. "ppthtml\" ". $dir_path. DIRECTORY_SEPARATOR.$file_name. " > ". $dir_path. DIRECTORY_SEPARATOR. "ppt.html";
            exec($cmd);
            if (file_exists($dir_path. DIRECTORY_SEPARATOR. "ppt.html")) {
                $txt = file($dir_path. DIRECTORY_SEPARATOR. "ppt.html");
                $txt = implode("", $txt);
                $strFullText = strip_tags($txt);
                unlink($dir_path. DIRECTORY_SEPARATOR. "ppt.html");
            }
        }
        // docx
        else if($itemMetaData['mime_type'] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
            // docx to zip rename
            copy( $dir_path. DIRECTORY_SEPARATOR.$file_name, $dir_path. DIRECTORY_SEPARATOR. "docx.zip" );
            $tag_val_list = array();
            if (file_exists($dir_path. DIRECTORY_SEPARATOR. "docx.zip")) {
                $this->zipDecompress($dir_path, "docx.zip");
                // document.xml get value
                $xml_path = $dir_path. DIRECTORY_SEPARATOR."docx". DIRECTORY_SEPARATOR."word";
                $getTagResult = $this->getOfficeXMLText($xml_path, "document.xml");
                if($getTagResult !== false){
                    $strFullText = $getTagResult;
                }
                $this->removeDirectory($dir_path. DIRECTORY_SEPARATOR."docx");
            }
        }
        // xlsx
        else if($itemMetaData['mime_type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
            // xlsx to zip
            copy( $dir_path. DIRECTORY_SEPARATOR.$file_name, $dir_path. DIRECTORY_SEPARATOR. "xlsx.zip" );
            $tag_val_list = array();
            if (file_exists($dir_path. DIRECTORY_SEPARATOR. "xlsx.zip")) {

                $this->zipDecompress($dir_path, "xlsx.zip");
                // workbook.xml
                $xml_path = $dir_path. DIRECTORY_SEPARATOR."xlsx". DIRECTORY_SEPARATOR."xl";
                $getTagResult = $this->getOfficeXMLAttributes($xml_path, "workbook.xml");
                if($getTagResult !== false){
                    $strFullText = $getTagResult;
                }

                // sharedStrings.xml
                $getTagResult = $this->getOfficeXMLText($xml_path, "sharedStrings.xml");
                if($getTagResult !== false){
                    $strFullText = $strFullText.",". $getTagResult;
                }
                $this->removeDirectory($dir_path. DIRECTORY_SEPARATOR."xlsx");
            }
        }
        // pptx
        else if($itemMetaData['mime_type'] == 'application/vnd.openxmlformats-officedocument.presentationml.presentation'){
            // pptx to zip
            copy( $dir_path. DIRECTORY_SEPARATOR.$file_name, $dir_path. DIRECTORY_SEPARATOR. "pptx.zip" );
            $tag_val_list = array();
            if (file_exists($dir_path. DIRECTORY_SEPARATOR. "pptx.zip")) {
                $this->zipDecompress($dir_path, "pptx.zip");
                $xml_path = $dir_path. DIRECTORY_SEPARATOR."pptx". DIRECTORY_SEPARATOR."ppt". DIRECTORY_SEPARATOR."slides";

                $noCnt = 1;
                foreach (glob($xml_path. DIRECTORY_SEPARATOR.'slide*.xml') as $sheet) {
                    $getTagResult = $this->getOfficeXMLText($xml_path, "slide".$noCnt.".xml");
                    if($getTagResult !== false){
                        $strFullText = $getTagResult;
                        $noCnt++;
                    }
                }
                $this->removeDirectory($dir_path. DIRECTORY_SEPARATOR."pptx");
            }
        }
        // MySQLは4バイト文字に対応していないため4バイト文字を削除
        $strFullText = preg_replace("/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/", "", $strFullText);
        
        $this->setTextData($searchItemInfo, self::FILEDATA, $strFullText);
        //一時dir削除
        $this->removeDirectory($dir_path);
    }

    /**
     * execute
     *
     * @param searchAllItemInfo all item search data
     * @param sortAllItemInfo all item sort data
     */
    private function insertSearchTable( $searchAllItemInfo, $sortAllItemInfo )
    {
        if($this->pluginFlag) {
            // プラグインが存在するならそちらの処理を行う
            $queryList = $this->queryGenerator->createInsertSearchTableQuery($searchAllItemInfo, $sortAllItemInfo);
            for($ii = 0; $ii < count($queryList); $ii++) {
                $this->dbAccess->executeQuery($queryList[$ii]["query"], $queryList[$ii]["params"]);
            }
        } else {
            // insert no delete item to update item table
            foreach($this->mappingTableRelation as $key => $value){
                $insertItemList = array();
                for($ii = 0; $ii < count($searchAllItemInfo); $ii++){
                    $insertString = "";
                    for($jj = 0; $jj < count($value); $jj++){
                        if(!isset($searchAllItemInfo[$ii][$value[$jj]]) || strlen($searchAllItemInfo[$ii][$value[$jj]]) == 0){
                            continue;
                        }
                        if(strlen($insertString) == 0){
                            $insertString = $searchAllItemInfo[$ii][$value[$jj]];
                        } else {
                            $insertString .= ",".$searchAllItemInfo[$ii][$value[$jj]];
                        }
                    }
                    // Fix When $key == self::FILEDATA_TABLE and $insertString is empty, insert data at search table. 2014/03/26 Y.Nakao --start--
                    if(strlen($insertString) == 0){
                        continue;
                    }
                    // Fix When $key == self::FILEDATA_TABLE and $insertString is empty, insert data at search table. 2014/03/26 Y.Nakao --end--
                    $insertData = array("item_id" => $searchAllItemInfo[$ii]["item_id"],
                                        "item_no" => $searchAllItemInfo[$ii]["item_no"],
                                        "meta_data" => $insertString);
                    array_push($insertItemList, $insertData);
                }
                $params = array();
                $count = 0;
                
                // Add new prefix 2014/01/15 T.Ichikawa --start--                    
                if(strcmp($key, self::SELFDOI_TABLE) == 0)
                {
                    for($ii = 0; $ii < count($searchAllItemInfo); $ii++){
                        $this->updateSelfDoiSearchTable($searchAllItemInfo[$ii]["item_id"], $searchAllItemInfo[$ii]["item_no"]);
                    }
                }
                // Add new prefix 2014/01/15 T.Ichikawa --end--
                
                if(count($insertItemList) == 0){
                    continue;
                }
                $inQuery = "";
                switch($key){
                    case self::DATE_TABLE:
                    case self::DATAODISSUED_TABLE:
                    case self::DATEOFGRANTED_TABLE:
                    case self::DATAODISSUED_YMD_TABLE:
                        $query = "INSERT INTO ".DATABASE_PREFIX.$key." VALUES ";
                        for($ii = 0; $ii < count($insertItemList); $ii++){
                            $tmpDate = explode(",", $insertItemList[$ii]["meta_data"]);
                            $data_no = 1;
                            for($kk = 0; $kk < count($tmpDate); $kk++){
                                if(strlen($tmpDate[$kk]) == 0){
                                    continue;
                                }
                                if($count != 0){
                                    $inQuery .= ",";
                                }
                                $inQuery .= "(?,?,?,?)";
                                $params[] = $insertItemList[$ii]["item_id"];
                                $params[] = $insertItemList[$ii]["item_no"];
                                $params[] = $data_no;
                                if($key == self::DATAODISSUED_TABLE){
                                    $dateList = explode("-", $tmpDate[$kk]);
                                    $params[] = str_pad(intval($dateList[0]), 4, '0', STR_PAD_LEFT);
                                } else {
                                    $dateList = explode(" ", $tmpDate[$kk]);
                                    $params[] = str_replace("-", "", $dateList[0]);
                                }
                                $count++;
                                $data_no++;
                            }
                        }
                        break;
                        
                    default:
                        $query = "INSERT INTO ".DATABASE_PREFIX.$key." VALUES ";
                        for($ii = 0; $ii < count($insertItemList); $ii++){
                            if($count != 0){
                                $inQuery .= ",";
                            }
                            $inQuery .= "(?,?,?)";
                            $params[] = $insertItemList[$ii]["item_id"];
                            $params[] = $insertItemList[$ii]["item_no"];
                            $params[] = $insertItemList[$ii]["meta_data"];
                            $count++;
                        }
                        break;
                }
                if($count == 0){
                    continue;
                }
                $query .= $inQuery;
                $this->dbAccess->executeQuery($query, $params);
            }
            
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_search_sort ".
                     "(item_id, item_no, item_type_id, weko_id, title, title_en, ".
                     "uri, review_date, ins_user_id, mod_date, ins_date, biblio_date) VALUES ";
            $count = 0;
            $params = array();
            $inQuery = "";
            for($ii = 0; $ii < count($sortAllItemInfo); $ii++)
            {
                if($count != 0){
                    $inQuery .= ",";
                }
                $inQuery .= "(?,?,?,?,?,?,?,?,?,?,?,?)";
                $params[] = $sortAllItemInfo[$ii]["item_id"];
                $params[] = $sortAllItemInfo[$ii]["item_no"];
                $params[] = $sortAllItemInfo[$ii]["item_type_id"];
                if(isset($sortAllItemInfo[$ii]["weko_id"])){
                    $params[] = $sortAllItemInfo[$ii]["weko_id"];
                } else {
                    $params[] = 0;
                }
                if(strlen($sortAllItemInfo[$ii]["title"]) != 0){
                    $params[] = $sortAllItemInfo[$ii]["title"];
                } else {
                    $params[] = $sortAllItemInfo[$ii]["title_en"];
                }
                if(strlen($sortAllItemInfo[$ii]["title_en"]) != 0){
                    $params[] = $sortAllItemInfo[$ii]["title_en"];
                } else {
                    $params[] = $sortAllItemInfo[$ii]["title"];
                }
                $params[] = $sortAllItemInfo[$ii]["uri"];
                $params[] = $sortAllItemInfo[$ii]["review_date"];
                $params[] = $sortAllItemInfo[$ii]["ins_user_id"];
                $params[] = $sortAllItemInfo[$ii]["mod_date"];
                $params[] = $sortAllItemInfo[$ii]["ins_date"];
                if(isset($sortAllItemInfo[$ii]["biblio_date"])){
                    $params[] = $sortAllItemInfo[$ii]["biblio_date"];
                } else {
                    $params[] = "";
                }
                $count++;
            }
            if($count == 0){
                return;
            }
            $query .= $inQuery;
            $this->dbAccess->executeQuery($query, $params);
        }
    }

    /**
     * execute
     */
    private function callAsyncProcess()
    {

        // Request parameter for next URL
        $nextRequest = BASE_URL."/?action=repository_action_common_search_update";

        $result = RepositoryProcessUtility::callAsyncProcess($nextRequest);
        return $result;
    }

    /**
     * execute
     */
    private function setMappingTableRelation()
    {
        $this->mappingTableRelation = array();
        $this->mappingTableRelation[self::ALLMETADATA_TABLE][0] =  self::ALLMETADATA;
        $this->mappingTableRelation[self::FILEDATA_TABLE][0] =  self::FILEDATA;
        $this->mappingTableRelation[self::TITLE_TABLE][0] =  self::TITLE;
        $this->mappingTableRelation[self::TITLE_TABLE][1] =  self::ALTER_TITLE;
        $this->mappingTableRelation[self::AUTHOR_TABLE][0] =  self::AUTHOR;
        $this->mappingTableRelation[self::KEYWORD_TABLE][0] =  self::KEYWORD;
        $this->mappingTableRelation[self::NIISUBJECT_TABLE][0] =  self::NIISUBJECT;
        $this->mappingTableRelation[self::NDC_TABLE][0] =  self::NDC;
        $this->mappingTableRelation[self::NDLC_TABLE][0] =  self::NDLC;
        $this->mappingTableRelation[self::BSH_TABLE][0] =  self::BSH;
        $this->mappingTableRelation[self::NDLSH_TABLE][0] =  self::NDLSH;
        $this->mappingTableRelation[self::MESH_TABLE][0] =  self::MESH;
        $this->mappingTableRelation[self::DDC_TABLE][0] =  self::DDC;
        $this->mappingTableRelation[self::LCC_TABLE][0] =  self::LCC;
        $this->mappingTableRelation[self::UDC_TABLE][0] =  self::UDC;
        $this->mappingTableRelation[self::LCSH_TABLE][0] =  self::LCSH;
        $this->mappingTableRelation[self::DESCTIPTION_TABLE][0] =  self::DESCTIPTION;
        $this->mappingTableRelation[self::PUBLISHER_TABLE][0] =  self::PUBLISHER;
        $this->mappingTableRelation[self::CONTRIBUTOR_TABLE][0] =  self::CONTRIBUTOR;
        $this->mappingTableRelation[self::DATE_TABLE][0] =  self::DATE;
        $this->mappingTableRelation[self::TYPE_TABLE][0] =  self::TYPE;
        $this->mappingTableRelation[self::FORMAT_TABLE][0] =  self::FORMAT;
        $this->mappingTableRelation[self::IDENTIFER_TABLE][0] =  self::IDENTIFER;
        $this->mappingTableRelation[self::URI_TABLE][0] =  self::URI;
        $this->mappingTableRelation[self::FULLTEXTURL_TABLE][0] =  self::FULLTEXTURL;
        $this->mappingTableRelation[self::SELFDOI_TABLE][0] =  self::SELFDOI;
        $this->mappingTableRelation[self::ISBN_TABLE][0] =  self::ISBN;
        $this->mappingTableRelation[self::ISSN_TABLE][0] =  self::ISSN;
        $this->mappingTableRelation[self::NCID_TABLE][0] =  self::NCID;
        $this->mappingTableRelation[self::PMID_TABLE][0] =  self::PMID;
        $this->mappingTableRelation[self::DOI_TABLE][0] =  self::DOI;
        $this->mappingTableRelation[self::NAID_TABLE][0] =  self::NAID;
        $this->mappingTableRelation[self::ICHUSHI_TABLE][0] =  self::ICHUSHI;
        $this->mappingTableRelation[self::JTITLE_TABLE][0] =  self::JTITLE;
        $this->mappingTableRelation[self::DATAODISSUED_TABLE][0] =  self::DATAODISSUED;
        $this->mappingTableRelation[self::LANGUAGE_TABLE][0] =  self::LANGUAGE;
        $this->mappingTableRelation[self::RELATION_TABLE][0] =  self::SPATIAL;
        $this->mappingTableRelation[self::RELATION_TABLE][1] =  self::NIISPATIAL;
        $this->mappingTableRelation[self::COVERAGE_TABLE][0] =  self::TEMPORAL;
        $this->mappingTableRelation[self::COVERAGE_TABLE][1] =  self::NIITEMPORAL;
        $this->mappingTableRelation[self::RIGHTS_TABLE][0] =  self::RIGHTS;
        $this->mappingTableRelation[self::TEXTVERSION_TABLE][0] =  self::TEXTVERSION;
        $this->mappingTableRelation[self::GRANTID_TABLE][0] =  self::GRANTID;
        $this->mappingTableRelation[self::DATEOFGRANTED_TABLE][0] =  self::DATEOFGRANTED;
        $this->mappingTableRelation[self::DEGREENAME_TABLE][0] =  self::DEGREENAME;
        $this->mappingTableRelation[self::GRANTOR_TABLE][0] =  self::GRANTOR;
        $this->mappingTableRelation[self::DATAODISSUED_YMD_TABLE][0] =  self::DATAODISSUED_YMD;
    }

    /**
     * regist data
     *
     * @param registArray
     * @param registKey
     * @param registValue
     */
    private function setTextData(&$registArray, $registKey, $registValue)
    {
        if(strlen($registValue) == 0){
            return;
        }
        if(isset($registArray[$registKey]) && strlen($registArray[$registKey]) != 0 )
        {
            $registArray[$registKey] .= ",".$registValue;
        } else {
            $registArray[$registKey] = $registValue;
        }
    }
    /**
     * get suffix id for author
     *
     * @param auth_id search suffix id
     */
    private function getSuffixId($auth_id)
    {
        $query = "SELECT suffix ".
                 "FROM ".DATABASE_PREFIX."repository_external_author_id_suffix ".
                 "WHERE author_id = ? ;";
        $params = array();
        $params[] = $auth_id;
        $result = $this->dbAccess->executeQuery($query, $params);
        return $result;
    }
    /**
     * Update SelfDoi searchtable
     * @param int $item_id
     * @param int $item_no
     */
    public function updateSelfDoiSearchTable($item_id, $item_no)
    {
        $this->getRepositoryHandleManager();
        
        $uri = "";
        $uri_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_JALC_DOI);
        $uri_crossref = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_CROSS_REF_DOI);
        $uri_library_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_LIBRARY_JALC_DOI);
        if(strlen($uri_jalcdoi) > 0 && strlen($uri_crossref) < 1 && strlen($uri_library_jalcdoi) < 1)
        {
            $uri = $uri_jalcdoi;
        }
        else if(strlen($uri_crossref) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_library_jalcdoi) < 1)
        {
            $uri = $uri_crossref;
        }
        else if(strlen($uri_library_jalcdoi) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_crossref) < 1)
        {
            $uri = $uri_library_jalcdoi;
        }
        
        if(strlen($uri) > 0)
        {
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_search_selfdoi ".
                     "(item_id, item_no, metadata) VALUES ".
                     "(?, ?, ?) ".
                     "ON DUPLICATE KEY UPDATE metadata=? ;";
            
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = $uri;
            $params[] = $uri;
            $this->dbAccess->executeQuery($query, $params);
        } else {
            $query = "DELETE FROM ".DATABASE_PREFIX."repository_search_selfdoi ".
                     "WHERE item_id=? AND item_no=? ;";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $this->dbAccess->executeQuery($query, $params);
        }
    }
    /**
     * Get epositoryHandleManager
     */
    private function getRepositoryHandleManager()
    {
        if(!isset($this->repositoryHandleManager)){
            if(!isset($this->TransStartDate) || strlen($this->TransStartDate) == 0)
            {
                $DATE = new Date();
                $this->TransStartDate = $DATE->getDate(). ".000";
            }
            
            $rhm = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            $this->repositoryHandleManager = $rhm;
        }
    }
    /**
     * add external search word
     * @param int $item_id
     * @param int $item_no
     * @param string $search_word
     */
    public function addExternalSearchWord($item_id, $item_no, $search_word) {
        $query = "INSERT INTO ". DATABASE_PREFIX. "repository_search_external_searchword ".
                 "(item_id, item_no, metadata) ".
                 "VALUES (?, ?, ?) ".
                 "ON DUPLICATE KEY UPDATE ".
                 "item_id=VALUES(`item_id`), ".
                 "item_no=VALUES(`item_no`), ".
                 "metadata=CONCAT(metadata, VALUES(`metadata`)) ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = ",".$search_word;
        $this->dbAccess->executeQuery($query, $params);
    }
}

?>
