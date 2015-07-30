<?php
// --------------------------------------------------------------------
//
// $Id: QueryGenerator.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/QueryGeneratorInterFace.class.php';

/**
 * repository search class
 * 
 */
class Repository_Components_Querygenerator implements Repository_Components_Querygeneratorinterface
{
    // search table name
    const ALLMETADATA_TABLE = "repository_search_allmetadata";
    const FILEDATA_TABLE = "repository_search_filedata";
    const TITLE_TABLE = "repository_search_title";
    const AUTHOR_TABLE = "repository_search_author";
    const KEYWORD_TABLE = "repository_search_keyword";
    const NIISUBJECT_TABLE = "repository_search_niisubject";
    const NDC_TABLE = "repository_search_ndc";
    const NDLC_TABLE = "repository_search_ndlc";
    const BSH_TABLE = "repository_search_bsh";
    const NDLSH_TABLE = "repository_search_ndlsh";
    const MESH_TABLE = "repository_search_mesh";
    const DDC_TABLE = "repository_search_ddc";
    const LCC_TABLE = "repository_search_lcc";
    const UDC_TABLE = "repository_search_udc";
    const LCSH_TABLE = "repository_search_lcsh";
    const DESCTIPTION_TABLE = "repository_search_description";
    const PUBLISHER_TABLE = "repository_search_publisher";
    const CONTRIBUTOR_TABLE = "repository_search_contributor";
    const DATE_TABLE = "repository_search_date";
    const TYPE_TABLE = "repository_search_type";
    const FORMAT_TABLE = "repository_search_format";
    const IDENTIFER_TABLE = "repository_search_identifier";
    const URI_TABLE = "repository_search_uri";
    const FULLTEXTURL_TABLE = "repository_search_fulltexturl";
    const SELFDOI_TABLE = "repository_search_selfdoi";
    const ISBN_TABLE = "repository_search_isbn";
    const ISSN_TABLE = "repository_search_issn";
    const NCID_TABLE = "repository_search_ncid";
    const PMID_TABLE = "repository_search_pmid";
    const DOI_TABLE = "repository_search_doi";
    const NAID_TABLE = "repository_search_naid";
    const ICHUSHI_TABLE = "repository_search_ichushi";
    const JTITLE_TABLE = "repository_search_jtitle";
    const DATAODISSUED_TABLE = "repository_search_dateofissued";
    const LANGUAGE_TABLE = "repository_search_language";
    const RELATION_TABLE = "repository_search_relation";
    const COVERAGE_TABLE = "repository_search_coverage";
    const RIGHTS_TABLE = "repository_search_rights";
    const TEXTVERSION_TABLE = "repository_search_textversion";
    const GRANTID_TABLE = "repository_search_grantid";
    const DATEOFGRANTED_TABLE = "repository_search_dateofgranted";
    const DEGREENAME_TABLE = "repository_search_degreename";
    const GRANTOR_TABLE = "repository_search_grantor";
    const SORT_TABLE = "repository_search_sort";
    const ITEM_TABLE = "repository_item";
    const POS_INDEX_TABLE = "repository_position_index";
    const INDEX_TABLE = "repository_index";
    const INDEX_RIGHT_TABLE = "repository_index_browsing_authority";
    const INDEX_GROUP_TABLE = "repository_index_browsing_groups";
    const ITEMTYPE_TABLE = "repository_item_type";
    const FILE_TABLE = "repository_file";
    const SUFFIX_TABLE = "repository_suffix";
    const DATEOFISSUED_YMD_TABLE = "repository_search_dateofissued_ymd";
    
    const ALL_TABLE_SHORT_NAME = "allmeta";
    const FILEDATA_TABLE_SHORT_NAME = "filedata";
    const TITLE_TABLE_SHORT_NAME = "title";
    const AUTHOR_TABLE_SHORT_NAME = "auth";
    const KEYWORD_TABLE_SHORT_NAME = "kw";
    const NIISUBJECT_TABLE_SHORT_NAME = "niisubj";
    const NDC_TABLE_SHORT_NAME = "ndc";
    const NDLC_TABLE_SHORT_NAME = "ndlc";
    const BSH_TABLE_SHORT_NAME = "bsh";
    const NDLSH_TABLE_SHORT_NAME = "ndlsh";
    const MESH_TABLE_SHORT_NAME = "mesh";
    const DDC_TABLE_SHORT_NAME = "ddc";
    const LCC_TABLE_SHORT_NAME = "lcc";
    const UDC_TABLE_SHORT_NAME = "udc";
    const LCSH_TABLE_SHORT_NAME = "lcsh";
    const DESCTIPTION_TABLE_SHORT_NAME = "descr";
    const PUBLISHER_TABLE_SHORT_NAME = "pub";
    const CONTRIBUTOR_TABLE_SHORT_NAME = "contr";
    const DATE_TABLE_SHORT_NAME = "date";
    const TYPE_TABLE_SHORT_NAME = "type";
    const FORMAT_TABLE_SHORT_NAME = "form";
    const IDENTIFER_TABLE_SHORT_NAME = "id";
    const URI_TABLE_SHORT_NAME = "uri";
    const FULLTEXTURL_TABLE_SHORT_NAME = "fullurl";
    const SELFDOI_TABLE_SHORT_NAME = "selfdoi";
    const ISBN_TABLE_SHORT_NAME = "isbn";
    const ISSN_TABLE_SHORT_NAME = "issn";
    const NCID_TABLE_SHORT_NAME = "ncid";
    const PMID_TABLE_SHORT_NAME = "pmid";
    const DOI_TABLE_SHORT_NAME = "doi";
    const NAID_TABLE_SHORT_NAME = "naid";
    const ICHUSHI_TABLE_SHORT_NAME = "ichushi";
    const JTITLE_TABLE_SHORT_NAME = "jtitle";
    const DATAODISSUED_TABLE_SHORT_NAME = "dtissue";
    const LANGUAGE_TABLE_SHORT_NAME = "lang";
    const RELATION_TABLE_SHORT_NAME = "cove";
    const COVERAGE_TABLE_SHORT_NAME = "relat";
    const RIGHTS_TABLE_SHORT_NAME = "rights";
    const TEXTVERSION_TABLE_SHORT_NAME = "textv";
    const GRANTID_TABLE_SHORT_NAME = "grantid";
    const DATEOFGRANTED_TABLE_SHORT_NAME = "dtgrant";
    const DEGREENAME_TABLE_SHORT_NAME = "dgname";
    const GRANTOR_TABLE_SHORT_NAME = "grantor";
    const SORT_TABLE_SHORT_NAME = "sort";
    const ITEM_TABLE_SHORT_NAME = "item";
    const POS_INDEX_TABLE_SHORT_NAME = "pos";
    const INDEX_TABLE_SHORT_NAME = "idx";
    const INDEX_RIGHT_TABLE_SHORT_NAME = "idxrt";
    const INDEX_GROUP_TABLE_SHORT_NAME = "idxgr";
    const ITEMTYPE_TABLE_SHORT_NAME = "itemtype";
    const FILE_TABLE_SHORT_NAME = "file";
    const DATEOFISSUED_YMD_TABLE_SHORT_NAME = "pubdate";
    const SUFFIX_TABLE_SHORT_NAME = "suf";
    const EXTERNAL_SEARCHWORD_TABLE = "repository_search_external_searchword";
    const EXTERNAL_SEARCHWORD_TABLE_SHORT_NAME = "externalsearch";
    
    const ORDER_TITLE_ASC           =  1;
    const ORDER_TITLE_DESC          =  2;
    const ORDER_INS_USER_ASC        =  3;
    const ORDER_INS_USER_DESC       =  4;
    const ORDER_ITEM_TYPE_ID_ASC    =  5;
    const ORDER_ITEM_TYPE_ID_DESC   =  6;
    const ORDER_WEKO_ID_ASC         =  7;
    const ORDER_WEKO_ID_DESC        =  8;
    const ORDER_MOD_DATE_ASC        =  9;
    const ORDER_MOD_DATE_DESC       = 10;
    const ORDER_INS_DATE_ASC        = 11;
    const ORDER_INS_DATE_DESC       = 12;
    const ORDER_REVIEW_DATE_ASC     = 13;
    const ORDER_REVIEW_DATE_DESC    = 14;
    const ORDER_DATEOFISSUED_ASC    = 15;
    const ORDER_DATEOFISSUED_DESC   = 16;
    const ORDER_CUSTOM_SORT_ASC     = 17;
    const ORDER_CUSTOM_SORT_DESC    = 18;
    
    // request parameter
    const REQUEST_META = "meta";
    const REQUEST_ALL = "all";
    const REQUEST_TITLE = "title";
    const REQUEST_CREATOR = "creator";
    const REQUEST_KEYWORD = "kw";
    const REQUEST_SUBJECT_LIST = "scList";
    const REQUEST_SUBJECT_DESC = "scDes";
    const REQUEST_DESCRIPTION = "des";
    const REQUEST_PUBLISHER = "pub";
    const REQUEST_CONTRIBUTOR = "con";
    const REQUEST_DATE = "date";
    const REQUEST_ITEMTYPE_LIST = "itemTypeList";
    const REQUEST_TYPE_LIST = "typeList";
    const REQUEST_FORMAT = "form";
    const REQUEST_ID_LIST = "idList";
    const REQUEST_ID_DESC = "idDes";
    const REQUEST_JTITLE = "jtitle";
    const REQUEST_PUBYEAR_FROM = "pubYearFrom";
    const REQUEST_PUBYEAR_UNTIL = "pubYearUntil";
    const REQUEST_LANGUAGE = "ln";
    const REQUEST_AREA = "sp";
    const REQUEST_ERA = "era";
    const REQUEST_RIGHT_LIST = "riList";
    const REQUEST_RITHT_DESC = "riDes";
    const REQUEST_TEXTVERSION = "textver";
    const REQUEST_GRANTID = "grantid";
    const REQUEST_GRANTDATE_FROM = "grantDateFrom";
    const REQUEST_GRANTDATE_UNTIL = "grantDateUntil";
    const REQUEST_DEGREENAME = "degreename";
    const REQUEST_GRANTOR = "grantor";
    const REQUEST_IDX = "idx";
    const REQUEST_SHOWORDER = "order";
    const REQUEST_COUNT = "count";
    const REQUEST_PAGENO = "pn";
    const REQUEST_LIST_RECORDS = "listRecords";
    const REQUEST_OUTPUT_TYPE = "format";
    const REQUEST_INDEX_ID = "index_id";
    const REQUEST_PAGE_ID = "page_id";
    const REQUEST_BLOCK_ID = "block_id";
    const REQUEST_WEKO_ID = "weko_id";
    const REQUEST_DISPLAY_LANG = "lang";
    const REQUEST_SEARCH_TYPE = "st";
    const REQUEST_MODULE_ID = "module_id";
    const REQUEST_HEADER = "_header";
    const REQUEST_OLD_SEARCH_TYPE = "search_type";
    const REQUEST_OLD_KEYWORD = "keyword";
    const REQUEST_OLD_PAGENO = "page_no";
    const REQUEST_OLD_COUNT = "list_view_num";
    const REQUEST_OLD_SHOWORDER = "sort_order";
    // Add OpenSearch WekoId K.Matsuo 2014/04/04 --start--
    const REQUEST_PUBDATE_FROM = "pubDateFrom";
    const REQUEST_PUBDATE_UNTIL = "pubDateUntil";
    // Add OpenSearch WekoId K.Matsuo 2014/04/04 --end--
    
    // search type ID
    const NIISUBJECT_ID = 1;
    const NDC_ID = 2;
    const NDLC_ID = 3;
    const BSH_ID = 4;
    const NDLSH_ID = 5;
    const MESH_ID = 6;
    const DDC_ID = 7;
    const LCC_ID = 8;
    const UDC_ID = 9;
    const LCSH_ID = 10;
    const IDENTIFER_ID = 1;
    const URI_ID = 2;
    const FULLTEXTURL_ID = 3;
    const SELFDOI_ID = 4;
    const ISBN_ID = 5;
    const ISSN_ID = 6;
    const NCID_ID = 7;
    const PMID_ID = 8;
    const DOI_ID = 9;
    const NAID_ID = 10;
    const ICHUSHI = 11;
    
    const INNER_JOIN = "innerJoin";
    
    /**
     * set fulltext index flag
     *
     * @var bool
     */
    private $setTableList = false;
    
    public $db_prefix = null;
    public $user_id = null;
    public $searchEngine = null;
    
    
    /**
     * construct
     */
    function __construct($db_prefix)
    {
        $this->db_prefix = $db_prefix;
    }
    
    /**
     * create detail search Query
     *
     * @param SearchQueryParameter I   information for search
     * @param string               I/O search query
     * @param array                I/O search query parameter
     */
    public function createDetailSearchQuery($searchInfo, &$searchQuery, &$connectQueryParam)
    {
        // パラメータの宣言
        $this->user_id = $searchInfo->user_id;
        $this->searchEngine = $searchInfo->searchEngine;
        
        $connectToTableName = self::SORT_TABLE_SHORT_NAME;
        $index_id = $searchInfo->index_id;
        $user_auth_id = $searchInfo->user_auth_id;
        $auth_id = $searchInfo->auth_id;
        $adminUser = $searchInfo->adminUser;
        $sort_order = $searchInfo->sort_order;
        $lang = $searchInfo->lang;
        
        // 設定テーブルリスト
        $this->setTableList = null;
        // 連結タイプ
        $connectType = self::INNER_JOIN;
        // 連結実行フラグ
        $connectFlag = false;
        // 検索クエリの検索条件部分
        $connectQuery = "";
        // 検索クエリ連結の条件文
        $connectTermQuery = "";
        $connectQueryParam = array();
        if(!$adminUser){
            $connectTermQuery .= "WHERE ";
            if(isset($this->user_id) && $this->user_id != '0'){
                $connectTermQuery .= "( ".self::ITEM_TABLE_SHORT_NAME.".ins_user_id = ? OR ";
                $connectQueryParam[] = $this->user_id;
            }
            $connectFlag = true;
            // item rights
            $itemTableName = self::ITEM_TABLE_SHORT_NAME;
            if($connectType == self::INNER_JOIN){
                $connectQuery .= "INNER JOIN ".$this->db_prefix.self::ITEM_TABLE." AS ".$itemTableName." ON ".
                                 $connectToTableName.".item_id = ".$itemTableName.".item_id ";
                $this->setTableList[self::ITEM_TABLE] = $itemTableName;
                $connectTermQuery .= "( ".$itemTableName.".shown_status = 1 AND ".$itemTableName.".shown_date <= NOW() AND ".$itemTableName.".is_delete = 0 ) ";
            } else {
                $connectTermQuery .= "( item_id, item_no) IN ( ".
                                     " SELECT item_id, item_no ".
                                     " FROM ".$this->db_prefix.self::ITEM_TABLE." ".
                                     " WHERE ( shown_status = 1 AND shown_date <= NOW() AND is_delete = 0  )";
            }
        }
        // index rights
        $this->createIndexRightsQuery($connectToTableName, $user_auth_id, $auth_id, $searchInfo->groupList,
                                      $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType, $adminUser);
        $addPub = false;
        $addGrant = false;
        $searchFlag = false;
        $addRights = false;
        $addPubDate = false;
        
        if(isset($this->user_id) && $this->user_id != '0' && !$adminUser){
            $connectTermQuery .= ")";
        }
        
        foreach($searchInfo->search_term as $request => $value){
            switch($request){
                case self::REQUEST_META:
                    $result1 = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::ALLMETADATA_TABLE, self::ALL_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    $result2 = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::EXTERNAL_SEARCHWORD_TABLE, self::EXTERNAL_SEARCHWORD_TABLE_SHORT_NAME, $value, 
                                               "OR", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType, $request);
                    if($result1 || $reuslt2){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_ALL:
                    $tmpTermQuery = "";
                    if($connectFlag){
                        $tmpTermQuery .= "AND (";
                    } else {
                        $tmpTermQuery .= "WHERE (";
                    }
                    $tmpFlag = true;
                    $andor = "";
                    $result1 = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::ALLMETADATA_TABLE, self::ALL_TABLE_SHORT_NAME, $value, 
                                               $andor, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $tmpFlag, $connectType);
                    if($result1){
                        $andor = "OR";
                    }
                    $result2 = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::FILEDATA_TABLE, self::FILEDATA_TABLE_SHORT_NAME, $value, 
                                               $andor, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $tmpFlag, $connectType, $request);
                    $result3 = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::EXTERNAL_SEARCHWORD_TABLE, self::EXTERNAL_SEARCHWORD_TABLE_SHORT_NAME, $value, 
                                               $andor, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $tmpFlag, $connectType, $request);
                    if($result1 || $result2 || $result3){
                        $connectTermQuery .= $tmpTermQuery .") ";
                        $connectFlag = true;
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_TITLE:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::TITLE_TABLE, self::TITLE_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_CREATOR:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::AUTHOR_TABLE, self::AUTHOR_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_KEYWORD:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::KEYWORD_TABLE, self::KEYWORD_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_SUBJECT_LIST:
                    $result = $this->createSubjectQuery(self::SORT_TABLE_SHORT_NAME, $value, $searchInfo->search_term[self::REQUEST_SUBJECT_DESC], 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_DESCRIPTION:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::DESCTIPTION_TABLE, self::DESCTIPTION_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_PUBLISHER:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::PUBLISHER_TABLE, self::PUBLISHER_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_CONTRIBUTOR:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::CONTRIBUTOR_TABLE, self::CONTRIBUTOR_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_DATE:
                    $result = $this->createDateQuery(self::SORT_TABLE_SHORT_NAME, self::DATE_TABLE, self::DATE_TABLE_SHORT_NAME, $value, $value, false, 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_ITEMTYPE_LIST:
                    $result = $this->createINSearchColumnQuery(self::SORT_TABLE_SHORT_NAME, self::ITEM_TABLE, self::ITEM_TABLE_SHORT_NAME, $value, "item_type_id",
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_TYPE_LIST:
                    $tmpValue = str_replace("free_input", "", $value);
                    $result = $this->createTypeQuery(self::SORT_TABLE_SHORT_NAME, self::ITEM_TABLE, self::ITEM_TABLE_SHORT_NAME, $tmpValue,
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_FORMAT:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::FORMAT_TABLE, self::FORMAT_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_ID_LIST:
                    $result = $this->createIDQuery(self::SORT_TABLE_SHORT_NAME, $value, $searchInfo->search_term[self::REQUEST_ID_DESC ], 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_JTITLE:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::JTITLE_TABLE, self::JTITLE_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_PUBYEAR_FROM:
                case self::REQUEST_PUBYEAR_UNTIL:
                    if(!$addPub){
                        $result = $this->createDateQuery(self::SORT_TABLE_SHORT_NAME, self::DATAODISSUED_TABLE, self::DATAODISSUED_TABLE_SHORT_NAME, 
                                               $searchInfo->search_term[self::REQUEST_PUBYEAR_FROM], $searchInfo->search_term[self::REQUEST_PUBYEAR_UNTIL], true, 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                        if($result){
                            $searchFlag = true;
                        }
                    }
                    $addPub = true;
                    break;
                case self::REQUEST_LANGUAGE:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::LANGUAGE_TABLE, self::LANGUAGE_TABLE_SHORT_NAME, $value, 
                                               "AND", "OR", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_AREA:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::RELATION_TABLE, self::RELATION_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_ERA:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::COVERAGE_TABLE, self::COVERAGE_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_RIGHT_LIST:
                    $andor = "AND";
                    if($addRights){
                        $andor = "OR";
                    }
                    $tmpValue = str_replace("free_input", "", $value);
                    $result = $this->createINSearchColumnQuery(self::SORT_TABLE_SHORT_NAME, self::FILE_TABLE, self::FILE_TABLE_SHORT_NAME, $tmpValue, "license_id",
                                               $andor, $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                        $addRights = true;
                    }
                    break;
                case self::REQUEST_RITHT_DESC:
                    $andor = "AND";
                    if($addRights){
                        $andor = "OR";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::RIGHTS_TABLE, self::RIGHTS_TABLE_SHORT_NAME, $value, 
                                               $andor, "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                        $addRights = true;
                    }
                    break;
                case self::REQUEST_TEXTVERSION:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::TEXTVERSION_TABLE, self::TEXTVERSION_TABLE_SHORT_NAME, $value, 
                                               "AND", "OR", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_GRANTID:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::GRANTID_TABLE, self::GRANTID_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_GRANTDATE_FROM:
                case self::REQUEST_GRANTDATE_UNTIL:
                    if(!$addGrant){
                        $result = $this->createDateQuery(self::SORT_TABLE_SHORT_NAME, self::DATEOFGRANTED_TABLE, self::DATEOFGRANTED_TABLE_SHORT_NAME, 
                                               $searchInfo->search_term[self::REQUEST_GRANTDATE_FROM], $searchInfo->search_term[self::REQUEST_GRANTDATE_UNTIL], false, 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                        if($result){
                            $searchFlag = true;
                        }
                    }
                    $addGrant = true;
                    break;
                case self::REQUEST_DEGREENAME:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::DEGREENAME_TABLE, self::DEGREENAME_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_GRANTOR:
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::GRANTOR_TABLE, self::GRANTOR_TABLE_SHORT_NAME, $value, 
                                               "AND", "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                case self::REQUEST_IDX:
                    $result = $this->createINSearchColumnQuery(self::SORT_TABLE_SHORT_NAME, self::POS_INDEX_TABLE, self::POS_INDEX_TABLE_SHORT_NAME, $value, "index_id",
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if($result){
                        $searchFlag = true;
                    }
                    break;
                    
                case self::REQUEST_WEKO_ID:
                    // string length of weko_id is 1-8
                    $result = $this->createINSearchColumnQuery(self::SORT_TABLE_SHORT_NAME, self::SUFFIX_TABLE, self::SUFFIX_TABLE_SHORT_NAME, $value, "suffix", 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    
                    if($result){
                        $searchFlag = true;
                    }
                    
                    break;
                    
                case self::REQUEST_PUBDATE_FROM:
                case self::REQUEST_PUBDATE_UNTIL:
                    if(!$addPubDate){
                        $result = $this->createDateQuery(self::SORT_TABLE_SHORT_NAME, self::DATEOFISSUED_YMD_TABLE, self::DATEOFISSUED_YMD_TABLE_SHORT_NAME, 
                                               $searchInfo->search_term[self::REQUEST_PUBDATE_FROM], $searchInfo->search_term[self::REQUEST_PUBDATE_UNTIL], false, 
                                               "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
                        if($result){
                            $searchFlag = true;
                        }
                    }
                    $addPubDate = true;
                    break;
                default:
                    break;
            }
        }
        if(!$searchFlag && strlen($index_id) > 0){
            $this->createINSearchColumnQuery(self::SORT_TABLE_SHORT_NAME, self::POS_INDEX_TABLE, self::POS_INDEX_TABLE_SHORT_NAME, $index_id, "index_id",
                                       "AND", $connectQuery, $connectTermQuery, $connectQueryParam, $connectFlag, $connectType);
        } else {
            $index_id = "";
        }
        $connectQuery .= $connectTermQuery;
        
        // SELECT対象の選択
        if($searchInfo->countFlag) {
            // 件数検索を行う処理
            $searchQuery = "SELECT COUNT(DISTINCT ".self::SORT_TABLE_SHORT_NAME.".item_id, ".self::SORT_TABLE_SHORT_NAME.".item_no) AS total ".
                           "FROM ".$this->db_prefix.self::SORT_TABLE." AS ".self::SORT_TABLE_SHORT_NAME." ";
            if($connectFlag){
                $searchQuery .= $connectQuery;
            }
        } else {
            // アイテム検索を行う処理
            // sort order
            if($sort_order == self::ORDER_CUSTOM_SORT_ASC || $sort_order == self::ORDER_CUSTOM_SORT_DESC){
                $searchQuery = "SELECT DISTINCT ".self::SORT_TABLE_SHORT_NAME.".item_id, ".self::SORT_TABLE_SHORT_NAME.".item_no, ".self::SORT_TABLE_SHORT_NAME.".uri ".
                               "FROM ".$this->db_prefix.self::SORT_TABLE." AS ".self::SORT_TABLE_SHORT_NAME." ";
                if(!isset($this->setTableList[self::POS_INDEX_TABLE])){
                    $searchQuery .= "INNER JOIN ".$this->db_prefix.self::POS_INDEX_TABLE." AS ".self::POS_INDEX_TABLE_SHORT_NAME." ON ".
                                     self::SORT_TABLE_SHORT_NAME.".item_id = ".$this->db_prefix.self::ITEM_TABLE.".item_id ";
                }
            } else {
                // execute search
                $searchQuery = "SELECT DISTINCT ".self::SORT_TABLE_SHORT_NAME.".item_id, ".self::SORT_TABLE_SHORT_NAME.".item_no, ".self::SORT_TABLE_SHORT_NAME.".uri ".
                               "FROM ".$this->db_prefix.self::SORT_TABLE." AS ".self::SORT_TABLE_SHORT_NAME." ";
            }
            if($connectFlag){
                $searchQuery .= $connectQuery;
            }
        }
        
        // ///// sort order /////
        switch($sort_order)
        {
            case self::ORDER_TITLE_ASC:
            case self::ORDER_TITLE_DESC:
                // sort culum
                $sortTitle = "title";
                if($lang == "japanese")
                {
                    $sortTitle = "title";
                } else {
                    $sortTitle = "title_en";
                }
                // sort order
                if($sort_order == self::ORDER_TITLE_ASC)
                {
                    $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".".$sortTitle." ASC ";
                }
                else
                {
                    $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".".$sortTitle." DESC ";
                }
                break;
            case self::ORDER_INS_USER_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".ins_user_id ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_INS_USER_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".ins_user_id DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_ITEM_TYPE_ID_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".item_type_id ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_ITEM_TYPE_ID_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".item_type_id DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_WEKO_ID_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".weko_id ASC, ".self::SORT_TABLE_SHORT_NAME.".uri ASC ";
                break;
            case self::ORDER_WEKO_ID_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".weko_id DESC, ".self::SORT_TABLE_SHORT_NAME.".uri DESC ";
                break;
            case self::ORDER_MOD_DATE_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".mod_date ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_MOD_DATE_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".mod_date DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_INS_DATE_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".ins_date ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_INS_DATE_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".ins_date DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_REVIEW_DATE_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".review_date ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_REVIEW_DATE_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".review_date DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_DATEOFISSUED_ASC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".biblio_date ASC, ".self::SORT_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_DATEOFISSUED_DESC:
                $searchQuery .= " ORDER BY ".self::SORT_TABLE_SHORT_NAME.".biblio_date DESC, ".self::SORT_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            case self::ORDER_CUSTOM_SORT_ASC:
                $searchQuery .= " ORDER BY ".self::POS_INDEX_TABLE_SHORT_NAME.".custom_sort_order ASC, ".self::POS_INDEX_TABLE_SHORT_NAME.".item_id ASC ";
                break;
            case self::ORDER_CUSTOM_SORT_DESC:
                $searchQuery .= " ORDER BY ".self::POS_INDEX_TABLE_SHORT_NAME.".custom_sort_order DESC, ".self::POS_INDEX_TABLE_SHORT_NAME.".item_id DESC ";
                break;
            default:
                break;
        }
        
        return true;
    }
    
    /**
     * create search index rights Query
     *
     * @param connectToTableName I Connection place table name
     * @param baseRights I Connecting agency table name
     * @param roomRights I Search string 
     * @param groupIDList I The flag which connects only conditions
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createIndexRightsQuery($connectToTableName, $baseRights, $roomRights, $groupIDList,
                                         &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType, $isAdminUser)
    {
        if($connectFlag){
            $connectTermQuery .= "AND ";
        } else {
            $connectTermQuery .= "WHERE ";
            $connectFlag = true;
        }
        
        // connect repository_position_index 
        $posName = self::POS_INDEX_TABLE_SHORT_NAME;
        if($connectType == self::INNER_JOIN){
            $this->setTableList[self::POS_INDEX_TABLE] = $posName;
            $connectQuery .= "INNER JOIN ".$this->db_prefix.self::POS_INDEX_TABLE." AS ".$posName." ON ".
                             $connectToTableName.".item_id = ".$posName.".item_id ";
            $connectTermQuery .= " ".$posName.".is_delete = 0 ";
        } else {
            $connectTermQuery .= "(item_id, item_no ) IN (".
                                 " SELECT item_id, item_no ".
                                 " FROM ".$this->db_prefix.self::POS_INDEX_TABLE." ".
                                 " WHERE is_delete = 0 ";
        }
        if($isAdminUser){
            if($connectType != self::INNER_JOIN){
                $connectTermQuery .= " ) ";
            }
            return true;
        }
        // connect repository_index 
        $indexName = self::INDEX_TABLE_SHORT_NAME;
        if($connectType == self::INNER_JOIN){
            $this->setTableList[self::INDEX_TABLE] = $indexName;
            $connectQuery .= "INNER JOIN ".$this->db_prefix.self::INDEX_TABLE." AS ".$indexName." ON ".
                             $posName.".index_id = ".$indexName.".index_id ";
            $connectTermQuery .= " AND ".$indexName.".is_delete = 0 ";
        }
        
        // connect repository_index_rights 
        $indexRightName = self::INDEX_RIGHT_TABLE_SHORT_NAME;
        if($connectType == self::INNER_JOIN){
            $this->setTableList[self::INDEX_RIGHT_TABLE] = $indexRightName;
            $connectQuery .= "INNER JOIN ".$this->db_prefix.self::INDEX_RIGHT_TABLE." AS ".$indexRightName." ON ".
                             $posName.".index_id = ".$indexRightName.".index_id ";
                         
            $connectTermQuery .= " AND ( ( ".$indexRightName.".public_state = 1 ". 
                                 " AND  ".$indexRightName.".pub_date <= NOW() ) ".
                                 " OR  ".$indexName.".owner_user_id = ? ) ".
                                 " AND  ".$indexRightName.".is_delete = 0 ";
            $connectTermQuery .= " AND ( ".$indexRightName.".exclusive_acl_role_id < ? AND ".$indexRightName.".exclusive_acl_room_auth < ? ) ";
            $connectQueryParam[] = $this->user_id;
            if(!isset($baseRights) || strlen($baseRights) == 0){
                $connectQueryParam[] = 1;
            } else {
                $connectQueryParam[] = $baseRights;
            }
            $connectQueryParam[] = $roomRights;
        } else {
            $connectTermQuery .= " AND ( index_id ) IN (".
                                 " SELECT ".$indexRightName.".index_id ".
                                 " FROM ".$this->db_prefix.self::INDEX_RIGHT_TABLE." AS ".$indexRightName.", ".$this->db_prefix.self::INDEX_TABLE." AS ".$indexName." ".
                                 " WHERE ".$indexRightName.".index_id = ".$indexName.".index_id ".
                                 "  AND ( ( ".$indexRightName.".public_state = 1 ". 
                                 "   AND ".$indexRightName.".pub_date <= NOW() ) ".
                                 "   OR ".$indexName.".owner_user_id = ? ) ".
                                 "  AND ".$indexRightName.".is_delete = 0 ".
                                 "  AND ".$indexName.".is_delete = 0 ".
                                 "  AND ( ".$indexRightName.".exclusive_acl_role_id < ? AND ".$indexRightName.".exclusive_acl_room_auth < ? ) ";
            $connectQueryParam[] = $this->user_id;
            if(isset($baseRights) || strlen($baseRights) == 0){
                $connectQueryParam[] = 1;
            } else {
                $connectQueryParam[] = $baseRights;
            }
            $connectQueryParam[] = $roomRights;
        }
        
        // connect repository_index_groups 
        $indexGroupName = self::INDEX_GROUP_TABLE_SHORT_NAME;
        if(count($groupIDList)>0){
            $connectTermQuery .= "AND ( EXISTS ( ".
                                 " SELECT * ".
                                 " FROM ".$this->db_prefix."pages_users_link AS link ".
                                 " WHERE link.room_id IN ( "; 
            $count = 0;
            for($ii = 0; $ii < count($groupIDList); $ii++){
                if($count > 0){
                    $connectTermQuery .= ",";
                }
                $connectTermQuery .= "?";
                $connectQueryParam[] = $groupIDList[$ii]["room_id"];
                $count++;
            }
            $connectTermQuery .= ") ";    // exclusive_acl_group_id IN ( )
            $connectTermQuery .= " AND link.room_id NOT IN ( ".
                                 "  SELECT ".$indexGroupName.".exclusive_acl_group_id ".
                                 "  FROM ".$this->db_prefix.self::INDEX_GROUP_TABLE." AS ".$indexGroupName." ".
                                 "  WHERE ".$indexGroupName.".is_delete = 0 AND ".$indexGroupName.".index_id = ".$indexName.".index_id ".
                                 " ) ".
                                 ") ";
            $connectTermQuery .= " OR NOT EXISTS ( ".
                                 "  SELECT * ".
                                 "  FROM ".$this->db_prefix.self::INDEX_GROUP_TABLE." AS ".$indexGroupName." ".
                                 "  WHERE ".$indexGroupName.".is_delete = 0 AND ".$indexGroupName.".index_id = ".$indexName.".index_id ".
                                 "  AND ".$indexGroupName.".exclusive_acl_group_id = 0 ".
                                 " ) ".
                                 ") ";
        }
        if($connectType != self::INNER_JOIN){
            $connectTermQuery .= ") ) ";
        }
        return true;
    }
    
    /**
     * create search fulltext Query
     *
     * @param connectToTableName I Connection place table name
     * @param connetFromTableName I Connecting agency table name
     * @param shortName I Connecting agency table short name
     * @param searchValue I Search string 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createFullTextQuery($connectToTableName, $connetFromTableName, $shortName, $searchValue,
                                         $outorAndor, $innerAndor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType, $request='')
    {
        // connect
        if(strlen($searchValue) == 0){
            return false;
        }
        $searchStringList = preg_split("/[\s,']+/", $searchValue);
        if(count($searchStringList) == 0){
            return false;
        }
        
        // search fulltext
        $isFulltext = false;
        if($this->searchEngine == "senna") {
            $isFulltext = true;
        } else if($this->searchEngine == "mroonga") {
            $isFulltext = true;
            $isMroongaExist = true;
        }
        
        $connectString = "";
        if($connectFlag){
            $connectString .= $outorAndor." ";
        } else {
            $connectString .= "WHERE ";
            $connectFlag = true;
        }
        $innerJoinFlag = true;
        if(array_key_exists($connetFromTableName, $this->setTableList)){
            $shortName = $this->setTableList[$connetFromTableName];
            $innerJoinFlag = false;
        } 
        $count = 0;
        $tmpTermQuery = "( ";
        for($ii = 0; $ii < count($searchStringList); $ii++){
            if(strlen($searchStringList[$ii]) == 0){
                continue;
            }
            if($count > 0){
                $tmpTermQuery .= $innerAndor." ";
            }
            if($isFulltext){
                $tmpTermQuery .= "MATCH(".$shortName.".metadata) AGAINST(mroonga_escape(?, '()~><-*`\"\\\') IN BOOLEAN MODE) ";
                $connectQueryParam[] = "+".$searchStringList[$ii];
            } else {
                $tmpTermQuery .= $shortName.".metadata LIKE ? ";
                $connectQueryParam[] = "%".$searchStringList[$ii]."%";
            }
            $count++;
        }
        $tmpTermQuery .= ") ";
        if($count > 0){
            if($connectType == self::INNER_JOIN){
                $connectTermQuery .=  $connectString.$tmpTermQuery;
                $connectFlag = true;
                if($innerJoinFlag){
                    $this->setTableList[$connetFromTableName] = $shortName;
                    if(($connetFromTableName === self::FILEDATA_TABLE && $request === self::REQUEST_ALL) || 
                       ($connetFromTableName === self::EXTERNAL_SEARCHWORD_TABLE && $request === self::REQUEST_ALL) || 
                       ($connetFromTableName === self::EXTERNAL_SEARCHWORD_TABLE && $request === self::REQUEST_META))
                    {
                        $connectQuery .= "LEFT JOIN ".$this->db_prefix.$connetFromTableName." AS $shortName ON ".
                                         $connectToTableName.".item_id = ".$shortName.".item_id ";
                    }
                    else
                    {
                    $connectQuery .= "INNER JOIN ".$this->db_prefix.$connetFromTableName." AS $shortName ON ".
                                         $connectToTableName.".item_id = ".$shortName.".item_id ";
                }
                }
            } else {
                $connectFlag = true;
                $connectTermQuery .=  $connectString ."(". $connectToTableName. ".item_id, ". $connectToTableName. ".item_no) IN (".
                                     " SELECT item_id, item_no ".
                                     " FROM ".$this->db_prefix.$connetFromTableName." AS $shortName ".
                                     " WHERE ".$tmpTermQuery.") ";
            }
            return true;
        }
        return false;
    }
    
    /**
     * create search date Query
     *
     * @param connectToTableName I Connection place table name
     * @param connetFromTableName I Connecting agency table name
     * @param shortName I Connecting agency table short name
     * @param fromDate I Search string 
     * @param untilDate I Search string 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createDateQuery($connectToTableName, $connetFromTableName, $shortName, $fromDate, $untilDate, $onlyYear, 
                                         $andor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType)
    {
        // connect INNER JOIN
        if(strlen($fromDate) == 0 && strlen($untilDate) == 0 ){
            return false;
        }
        $fromDateList = preg_split("/[!-\/:-@\[-`{-~\s]/", $fromDate);
        $untilDateList = preg_split("/[!-\/:-@\[-`{-~\s]/", $untilDate);
        $fromDate = $this->validateDate($fromDateList, $onlyYear, true);
        $untilDate = $this->validateDate($untilDateList, $onlyYear, false);
        if($connectFlag){
            $connectTermQuery .= $andor." ";
        } else {
            $connectTermQuery .= "WHERE ";
            $connectFlag = true;
        }
        if(array_key_exists($connetFromTableName, $this->setTableList)){
            $shortName = $this->setTableList[$connetFromTableName];
        } else {
            $this->setTableList[$connetFromTableName] = $shortName;
            
            if($connectType == self::INNER_JOIN){
                $connectQuery .= "INNER JOIN ".$this->db_prefix.$connetFromTableName." AS $shortName ON ".
                                 $connectToTableName.".item_id = ".$shortName.".item_id ";
            } else {
                $connectTermQuery .= "(item_id, item_no) IN (".
                                     " SELECT item_id, item_no ".
                                     " FROM ".$this->db_prefix.$connetFromTableName." AS $shortName ".
                                     " WHERE ";
            }
        }
        if($fromDate == $untilDate){
            $connectTermQuery .= $shortName.".metadata = ? ";
            $connectQueryParam[] = $fromDate;
        } else {
            if(strlen($fromDate) > 0 && strlen($untilDate) > 0){
                $connectTermQuery .= $shortName.".metadata >= ? AND ".$shortName.".metadata <= ? ";
                $connectQueryParam[] = $fromDate;
                $connectQueryParam[] = $untilDate;
            } else if(strlen($fromDate) == 0 ){
                $connectTermQuery .= $shortName.".metadata <= ? ";
                $connectQueryParam[] = $untilDate;
            } else {
                $connectTermQuery .= $shortName.".metadata >= ? ";
                $connectQueryParam[] = $fromDate;
            }
        }
        
        if($connectType != self::INNER_JOIN){
            $connectTermQuery .= ") ";
        }
        return true;
    }
    
    /**
     * create search date Query
     *
     * @param dateArray I Connection place table name
     * @param onlyYear I Connecting agency table name
     */
    private function validateDate($dateArray, $onlyYear, $isFrom)
    {
        if($onlyYear){
            if(count($dateArray) > 0){
                if(strlen($dateArray[0]) > 4){
                    $date = substr($dateArray[0], 0, 4);
                } else if(strlen($dateArray[0]) != 0){
                    $date =  sprintf('%04d', $dateArray[0]);
                } else {
                    $date = "";
                }
            } else {
                $date = "";
            }
        } else {
            if(count($dateArray) >= 2){
                if(strlen($dateArray[0]) >= 4){
                    $date = substr($dateArray[0], 0, 4);
                } else {
                    $date =  sprintf('%04d', $dateArray[0]);
                }
                if(strlen($dateArray[1]) >= 2){
                    $date .= substr($dateArray[1], 0, 2);
                } else {
                    $date .=  sprintf('%02d', $dateArray[1]);
                }
                if(count($dateArray) >= 3){
                    if(strlen($fromDateList[2]) >= 2){
                        $date .= substr($dateArray[2], 0, 2);
                    } else {
                        $date .=  sprintf('%02d', $dateArray[2]);
                    }
                } else {
                    if($isFrom){
                        $date .= "01";
                    } else {
                        $date .= "31";
                    }
                }
            } else if(count($dateArray) == 1){
                if(strlen($dateArray[0]) == 0){
                    $date = "";
                } else if(strlen($dateArray[0]) <= 4){
                    if($isFrom){
                        $date =  sprintf('%04d', $dateArray[0])."0101";
                    } else {
                        $date =  sprintf('%04d', $dateArray[0])."1231";
                    }
                } else if(strlen($dateArray[0]) <= 6){
                    if($isFrom){
                        $date = substr($dateArray[0], 0, 4).substr($dateArray[0], 4, 2)."01";
                    } else {
                        $date = substr($dateArray[0], 0, 4).substr($dateArray[0], 4, 2)."31";
                    }
                } else {
                    $date = substr($dateArray[0], 0, 4).substr($dateArray[0], 4, 2).substr($dateArray[0], 6, 2);
                }
            } else {
                $date = "";
            }
        }
        return $date;
    }
    
    /**
     * create search subject Query
     *
     * @param connectToTableName I Connection place table name
     * @param connetFromTableName I Connecting agency table name
     * @param shortName I Connecting agency table short name
     * @param searchValue I Search string 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createSubjectQuery($connectToTableName, $idString, $searchValue,
                                         $andor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType)
    {
        if(strlen($idString) == 0 || strlen($searchValue) == 0 ){
            return false;
        }
        $idList = preg_split("/[\s,']+/", $idString);
        if(count($idList) == 0){
            return false;
        }
        
        $tmpTermQuery = "";
        if($connectFlag){
            $tmpTermQuery .= $andor." ( ";
        } else {
            $tmpTermQuery .= "WHERE ( ";
            $connectFlag = true;
        }
        $subjectConnectFlag = false;
        $connectAndOr = "";
        for($ii = 0; $ii < count($idList); $ii++){
            switch($idList[$ii]){
                case self::NIISUBJECT_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NIISUBJECT_TABLE, self::NIISUBJECT_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::NDC_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NDC_TABLE, self::NDC_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::NDLC_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NDLC_TABLE, self::NDLC_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::BSH_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::BSH_TABLE, self::BSH_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::NDLSH_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NDLSH_TABLE, self::NDLSH_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::MESH_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::MESH_TABLE, self::MESH_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::DDC_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::DDC_TABLE, self::DDC_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::LCC_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::LCC_TABLE, self::LCC_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::UDC_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::UDC_TABLE, self::UDC_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                case self::LCSH_ID:
                    if($subjectConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::LCSH_TABLE, self::LCSH_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$subjectConnectFlag){
                        $subjectConnectFlag = $result;
                    }
                    break;
                default:
                    break;
            }
        }
        if($subjectConnectFlag){
            $connectTermQuery .= $tmpTermQuery." ) ";
            return true;
        }
        return false;
    }
        
    /**
     * create NIItype date Query
     *
     * @param connectToTableName I Connection place table name
     * @param connetFromTableName I Connecting agency table name
     * @param shortName I Connecting agency table short name
     * @param searchValue I Search string 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createTypeQuery($connectToTableName, $connetFromTableName, $shortName, $searchValue, 
                                         $andor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType)
    {
        // connect INNER JOIN
        if(strlen($searchValue) == 0){
            return false;
        }
        $searchStringList = preg_split("/[,']+/", $searchValue);
        if(count($searchStringList) == 0){
            return false;
        }
        $connectString  = "";
        if($connectFlag){
            $connectString .= $andor." ";
        } else {
            $connectString .= "WHERE ";
        }
        $tmpTermQuery  = "";
        $innerJoinFlag = true;
        if(array_key_exists($connetFromTableName, $this->setTableList)){
            $shortName = $this->setTableList[$connetFromTableName];
            $innerJoinFlag = false;
        } 
        $itemTypeShortName = self::ITEMTYPE_TABLE_SHORT_NAME;
        $itemTypeTableName = self::ITEMTYPE_TABLE;
        $innerJoinItemTypeFlag = true;
        if(array_key_exists($itemTypeTableName, $this->setTableList)){
            $itemTypeShortName = $this->setTableList[$itemTypeTableName];
            $innerJoinItemTypeFlag = false;
        } 
        $count = 0;
        $tmpTermQuery .= $itemTypeShortName.".mapping_info IN ( ";
        for($ii = 0; $ii < count($searchStringList); $ii++){
            if(strlen($searchStringList[$ii]) == 0){
                continue;
            }
            if($count > 0){
                $tmpTermQuery .= ", ";
            }
            $tmpTermQuery .= "? ";
            switch($searchStringList[$ii]){
                case 0:
                    $connectQueryParam[] = "Journal Article";
                    break;
                case 1:
                    $connectQueryParam[] = "Thesis or Dissertation";
                    break;
                case 2:
                    $connectQueryParam[] = "Departmental Bulletin Paper";
                    break;
                case 3:
                    $connectQueryParam[] = "Conference Paper";
                    break;
                case 4:
                    $connectQueryParam[] = "Presentation";
                    break;
                case 5:
                    $connectQueryParam[] = "Book";
                    break;
                case 6:
                    $connectQueryParam[] = "Technical Report";
                    break;
                case 7:
                    $connectQueryParam[] = "Research Paper";
                    break;
                case 8:
                    $connectQueryParam[] = "Article";
                    break;
                case 9:
                    $connectQueryParam[] = "Preprint";
                    break;
                case 10:
                    $connectQueryParam[] = "Learning Material";
                    break;
                case 11:
                    $connectQueryParam[] = "Data or Dataset";
                    break;
                case 12:
                    $connectQueryParam[] = "Software";
                    break;
                case 13:
                    $connectQueryParam[] = "Others";
                    break;
            }
            $count++;
        }
        if($count > 0){
            if($connectType == self::INNER_JOIN){
                $connectTermQuery .= $connectString.$tmpTermQuery .") ";
                $connectFlag = true;
                if($innerJoinFlag){
                    $this->setTableList[$connetFromTableName] = $shortName;
                    $connectQuery .= "INNER JOIN ".$this->db_prefix.$connetFromTableName." AS $shortName ON ".
                                     $connectToTableName.".item_id = ".$shortName.".item_id ";
                }
                if($innerJoinItemTypeFlag){
                    $this->setTableList[$itemTypeTableName] = $itemTypeShortName;
                    $connectQuery .= "INNER JOIN ".$this->db_prefix.$itemTypeTableName." AS $itemTypeShortName ON ".
                                     $shortName.".item_type_id = ".$itemTypeShortName.".item_type_id ";
                }
            } else {
                $connectFlag = true;
                $connectTermQuery .= $connectString."(item_id, item_no ) IN ( ".
                                     " SELECT item_id, item_no ".
                                     " FROM ".$this->db_prefix.$connetFromTableName." ".
                                     " WHERE (item_type_id) IN ( ".
                                     "  SELECT item_type_id ".
                                     "  FROM ".$this->db_prefix.$itemTypeTableName." AS $itemTypeShortName ".
                                     "  WHERE ".$tmpTermQuery .") ) ) ";
            }
            return true;
        }
        return false;
    }
    
    /**
     * create search ID Query
     *
     * @param connectToTableName I Connection place table name
     * @param idString I IDString
     * @param searchValue I Search string 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createIDQuery($connectToTableName, $idString, $searchValue,
                                         $andor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType)
    {
    
        if(strlen($idString) == 0 || strlen($searchValue) == 0 ){
            return false;
        }
        $idList = preg_split("/[\s,']+/", $idString);
        if(count($idList) == 0){
            return false;
        }
        
        $tmpTermQuery = "";
        if($connectFlag){
            $tmpTermQuery .= $andor." ( ";
        } else {
            $tmpTermQuery .= "WHERE ( ";
            $connectFlag = true;
        }
        $idConnectFlag = false;
        $connectAndOr = "";
        for($ii = 0; $ii < count($idList); $ii++){
            switch($idList[$ii]){
                case self::IDENTIFER_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::IDENTIFER_TABLE, self::IDENTIFER_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::URI_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::URI_TABLE, self::URI_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::FULLTEXTURL_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::FULLTEXTURL_TABLE, self::FULLTEXTURL_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::SELFDOI_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::SELFDOI_TABLE, self::SELFDOI_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::ISBN_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::ISBN_TABLE, self::ISBN_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::ISSN_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::ISSN_TABLE, self::ISSN_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::NCID_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NCID_TABLE, self::NCID_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::PMID_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::PMID_TABLE, self::PMID_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::DOI_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::DOI_TABLE, self::DOI_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::NAID_ID:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::NAID_TABLE, self::NAID_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                case self::ICHUSHI:
                    if($idConnectFlag){
                        $connectAndOr = "OR";
                    } else {
                        $connectAndOr = "";
                    }
                    $result = $this->createFullTextQuery(self::SORT_TABLE_SHORT_NAME, self::ICHUSHI_TABLE, self::ICHUSHI_TABLE_SHORT_NAME, $searchValue, 
                                               $connectAndOr, "AND", $connectQuery, $tmpTermQuery, $connectQueryParam, $connectFlag, $connectType);
                    if(!$idConnectFlag){
                        $idConnectFlag = $result;
                    }
                    break;
                default:
                    break;
            }
        }
        if($idConnectFlag){
            $connectTermQuery .= $tmpTermQuery." ) ";
            
            return true;
        }
        return false;
    }

    /**
     * create Query
     *
     * @param connectToTableName I Connection place table name
     * @param connetFromTableName I Connecting agency table name
     * @param shortName I Connecting agency table short name
     * @param searchValue I search value
     * @param columnName I column name 
     * @param andor I Junction condition
     * @param connectQuery I/O Connection query sentence 
     * @param connectTermQuery I/O Connection query conditional sentence
     * @param connectQueryParam I/O Connection query conditional parameter
     * @param connectFlag I/O The flag of whether to have performed connection 
     */
    private function createINSearchColumnQuery($connectToTableName, $connetFromTableName, $shortName, $searchValue, $columnName,
                                         $andor, &$connectQuery, &$connectTermQuery, &$connectQueryParam, &$connectFlag, $connectType)
    {
        // connect INNER JOIN
        if(strlen($searchValue) == 0){
            return false;
        }
        $searchStringList = preg_split("/[\s,']+/", $searchValue);
        if(count($searchStringList) == 0){
            return false;
        }
        $connectString = "";
        if($connectFlag){
            $connectString .= $andor." ";
        } else {
            $connectString .= "WHERE ";
        }
        $tmpTermQuery = "";
        $innerJoinFlag = true;
        if(array_key_exists($connetFromTableName, $this->setTableList)){
            $shortName = $this->setTableList[$connetFromTableName];
            $innerJoinFlag = false;
        } 
        $count = 0;
        $tmpTermQuery .= $shortName.".".$columnName." IN ( ";
        for($ii = 0; $ii < count($searchStringList); $ii++){
            if(strlen($searchStringList[$ii]) == 0){
                continue;
            }
            if($count > 0){
                $tmpTermQuery .= ", ";
            }
            $tmpTermQuery .= "? ";
            $connectQueryParam[] = $searchStringList[$ii];
            $count++;
        }
        if($count > 0){
        
            if($connectType == self::INNER_JOIN){
                $connectTermQuery .= $connectString.$tmpTermQuery .") ";
                $connectFlag = true;
                if($innerJoinFlag){
                    $this->setTableList[$connetFromTableName] = $shortName;
                    $connectQuery .= "INNER JOIN ".$this->db_prefix.$connetFromTableName." AS $shortName ON ".
                                     $connectToTableName.".item_id = ".$shortName.".item_id ";
                    $connectTermQuery .= " AND ".$shortName.".is_delete = 0 ";
                }
            } else {
                $connectFlag = true;
                $connectTermQuery .= $connectString."(item_id, item_no) IN (".
                                     " SELECT item_id, item_no ".
                                     " FROM ".$this->db_prefix.$connetFromTableName." AS $shortName ".
                                     " WHERE ".$tmpTermQuery." ) AND ".$shortName.".is_delete = 0 ) ";
            }
            return true;
        }
        return false;
    }
}
?>