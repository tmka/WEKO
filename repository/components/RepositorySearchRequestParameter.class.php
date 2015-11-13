<?php
// --------------------------------------------------------------------
//
// $Id: RepositorySearchRequestParameter.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR.'/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/RepositoryConst.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/RepositoryDbAccess.class.php';
/**
 * repository search request parameter validate class
 * 
 */
class RepositorySearchRequestParameter
{
    const FORMAT_DESCRIPTION = "description";
    const FORMAT_RSS = "rss";
    const FORMAT_ATOM = "atom";
    const FORMAT_DUBLIN_CORE = "oai_dc";
    const FORMAT_JUNII2 = "junii2";
    const FORMAT_LOM = "oai_lom";
    
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
    const REQUEST_ITEM_IDS = "item_ids";
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
    /***** components *****/
    public $_container = null;
    public $_request = null;
    public $Session = null;
    public $dbAccess = null;
    public $Db = null;
    public $TransStartDate = null;
    
    /**
     * RepositoryAction class
     *
     * @var Object
     */
    public $RepositoryAction = null;
    
    /***** search key *****/
    /**
     * search keyword
     *
     * @var string
     */
    public $keyword = null;
    /**
     * search index
     *
     * @var string (int is exclude 0)
     */
    public $index_id = null;
    
    /***** view status *****/
    /**
     * 1 <= page no
     *
     * @var int
     */
    public $page_no = null;
    /**
     * list view, 20, 50, 75, 100
     *
     * @var int
     */
    public $list_view_num = null;
    /**
     * sort_order
     *
     * @var int  1:title ASC,
     *           2：title DESC,
     *           3：ins_user_id ASC,
     *           4：ins_user_id DESC, 
     *           5：item_type_id ADC,
     *           6：item_type_id DESC,
     *           7：WEKOID ASC,
     *           8：WEKOID DESC,
     *           9：mod_date ASC,
     *          10：mod_date DESC,
     *          11：ins_date ASC,
     *          12：ins_date DESC,
     *          13：review_date ASC,
     *          14：review_date DESC,
     *          15：dateofissued ASC,
     *          16：dateofissued DESC,
     *          17：custom sort ASC, at index search only
     *          18：custom sort DESC, at index search only
     *          --:default => WEKO管理画面で指定されたデフォルトソート条件に従う
     */
    public $sort_order = null;
    /**
     * output format
     *
     * @var string  description：OpenSearch description
     *              rss：output RSS
     *              atom：output atom
     *              oai_dc:output dublin core
     *              junii2:output junii2
     *              oai_lom:output lom
     *              else:HTML for repository_view_main_item_snippet
     * 
     */
    public $format = null;
    /**
     * language
     *
     * @var string
     */
    public $lang = null;
    /**
     * when this parameter is 'all', output all search result.
     * 
     * @var string
     */
    public $listResords = "";
    
    
    /**
     * search request parameter.
     * 
     * @var array
     */
    public $search_term = array();
    
    /**
     * search request parameter.
     * 
     * @var array
     */
    public $search_type = null;
    
    /**
     * search request parameter.
     * 
     * @var string (simple or detail)
     */
    public $all_search_type = null;
    
    /**
     * construct
     *
     */
    public function __construct()
    {
        $this->_container =& DIContainerFactory::getContainer();
        $this->_request = $_GET;
        
        $this->RepositoryAction = new RepositoryAction();
        $_db =& $this->_container->getComponent("DbObject");
        if($_db == null)
        {
            return null;
        }
        $this->Db = $_db;
        $this->dbAccess = new RepositoryDbAccess($_db);
        $this->RepositoryAction->Db = $_db;
        $this->RepositoryAction->dbAccess = $this->dbAccess;
        
        $_session =& $this->_container->getComponent("Session");
        if($_session == null)
        {
            return null;
        }
        $this->Session = $_session;
        $this->RepositoryAction->Session = $_session;
        
        $DATE = new Date();
        $this->TransStartDate = $DATE->getDate().".000";
        
        $this->RepositoryAction->TransStartDate = $this->TransStartDate;
        $this->RepositoryAction->setConfigAuthority();
        
        $this->setRequestParameter();
    }
    
    /**
     * set requestparameter
     * $リファラからリクエストパラメータ(検索条件)を設定する
     *
     */
    public function setRequestParameterFromReferrer()
    {
        $this->search_term = array();
        $this->sort_order = null;
        $this->page_no = null;
        $this->list_view_num = null;
        $this->lang = null;
        $this->listResords = null;
        $this->search_type = null;
        $this->format = null;
        $this->index_id = null;
        if(isset($_SERVER["HTTP_REFERER"]))
        {
        	parse_str($_SERVER["HTTP_REFERER"],$refererRequest);
        }
        else
        {
        	$refererRequest = array();
        }
        $this->_request = $refererRequest;
        $this->setRequestParameter();
    }
    /**
     * set requestparameter
     * $this->_requestコンポーネントからリクエストパラメータ(検索条件)を設定する
     *
     */
    public function setRequestParameter()
    {
        // ソート条件、検索範囲
        if(isset($this->_request[self::REQUEST_SHOWORDER]))
        { 
            $this->sort_order       = $this->_request[self::REQUEST_SHOWORDER];
        }
        
        // 表示条件
        if(isset($this->_request[self::REQUEST_PAGENO]))
        {
            $this->page_no          = $this->_request[self::REQUEST_PAGENO];
        }
        
        if(isset($this->_request[self::REQUEST_COUNT]))
        {
            $this->list_view_num    = $this->_request[self::REQUEST_COUNT];
        }
        
        if(isset($this->_request[self::REQUEST_DISPLAY_LANG]))
        {
            $this->lang             = $this->_request[self::REQUEST_DISPLAY_LANG];
        }
        
        if(isset($this->_request[self::REQUEST_LIST_RECORDS]))
        {
            $this->listResords      = $this->_request[self::REQUEST_LIST_RECORDS];
        }
        
        if(isset($this->_request[self::REQUEST_SEARCH_TYPE])){
            $this->search_type      = $this->_request[self::REQUEST_SEARCH_TYPE];
        }
        // 出力形式
        if(isset($this->_request[self::REQUEST_OUTPUT_TYPE]))
        {
            $this->format           = $this->_request[self::REQUEST_OUTPUT_TYPE];
        }
        
        $this->index_id = "";
        if(isset($this->_request[self::REQUEST_INDEX_ID]))
        {
            $this->index_id         = $this->_request[self::REQUEST_INDEX_ID];
        }
        // Fix When set search parameter weko_id, other search parameter are invalidity. 2014/05/08 Y.nakao --start--
        // 検索条件に「weko_id」がある場合、weko_id以外のパラメータは無効とする。
        $detailSearchFlag = false;
        if(isset($this->_request[self::REQUEST_WEKO_ID]))
        {
            $this->search_term[self::REQUEST_WEKO_ID] = $this->_request[self::REQUEST_WEKO_ID];;
        }
        // Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/20 --start--
        else if(isset($this->_request[self::REQUEST_ITEM_IDS]))
        {
            $this->search_term[self::REQUEST_ITEM_IDS] = $this->_request[self::REQUEST_ITEM_IDS];
        }
        // Add suppleContentsEntry Y.Yamazawa --end-- 2015/03/20 --end--
        else
        {
        foreach($this->_request as $requestParam => $requestValue){
            if($requestParam == self::REQUEST_META || $requestParam == self::REQUEST_ALL
                || $requestParam == self::REQUEST_TITLE || $requestParam == self::REQUEST_CREATOR
                || $requestParam == self::REQUEST_KEYWORD || $requestParam == self::REQUEST_SUBJECT_LIST
                || $requestParam == self::REQUEST_SUBJECT_DESC || $requestParam == self::REQUEST_DESCRIPTION
                || $requestParam == self::REQUEST_PUBLISHER || $requestParam == self::REQUEST_CONTRIBUTOR
                || $requestParam == self::REQUEST_DATE || $requestParam == self::REQUEST_ITEMTYPE_LIST
                || $requestParam == self::REQUEST_TYPE_LIST
                || $requestParam == self::REQUEST_FORMAT || $requestParam == self::REQUEST_ID_LIST
                || $requestParam == self::REQUEST_ID_DESC || $requestParam == self::REQUEST_JTITLE
                || $requestParam == self::REQUEST_PUBYEAR_FROM || $requestParam == self::REQUEST_PUBYEAR_UNTIL
                || $requestParam == self::REQUEST_LANGUAGE || $requestParam == self::REQUEST_AREA
                || $requestParam == self::REQUEST_ERA || $requestParam == self::REQUEST_RIGHT_LIST
                || $requestParam == self::REQUEST_RITHT_DESC || $requestParam == self::REQUEST_TEXTVERSION
                || $requestParam == self::REQUEST_GRANTID || $requestParam == self::REQUEST_GRANTDATE_FROM
                || $requestParam == self::REQUEST_GRANTDATE_UNTIL || $requestParam == self::REQUEST_DEGREENAME
                || $requestParam == self::REQUEST_GRANTOR || $requestParam == self::REQUEST_IDX
                || $requestParam == self::REQUEST_PUBDATE_FROM || $requestParam == self::REQUEST_PUBDATE_UNTIL
                || $requestParam == self::REQUEST_WEKO_ID){                if(!isset($requestValue) || strlen($requestValue) == 0){
                    continue;
                }
                $this->search_term[$requestParam] = $requestValue;
                $detailSearchFlag = true;
            }
        }
        }
        // Fix When set search parameter weko_id, other search parameter are invalidity. 2014/05/08 Y.nakao --end--
        
        // Fix subject, id search 2013.12.16 Y.Nakao --start--
        // チェック＋自由記述形式の場合、チェックなし=全チェックと同じ扱いにする
        if(!isset($this->search_term[self::REQUEST_SUBJECT_LIST]) && isset($this->search_term[self::REQUEST_SUBJECT_DESC]))
        {
            $this->search_term[self::REQUEST_SUBJECT_LIST] = "";
            for($ii=1; $ii<11; $ii++)
            {
                // set 1-10
                if($ii>1)
                {
                    $this->search_term[self::REQUEST_SUBJECT_LIST] .= ",";
                }
                $this->search_term[self::REQUEST_SUBJECT_LIST] .= $ii;
            }
        }
        
        if(!isset($this->search_term[self::REQUEST_ID_LIST]) && isset($this->search_term[self::REQUEST_ID_DESC]))
        {
            $this->search_term[self::REQUEST_ID_LIST] = "";
            for($ii=1; $ii<12; $ii++)
            {
                // set 1-11
                if($ii>1)
                {
                    $this->search_term[self::REQUEST_ID_LIST] .= ",";
                }
                $this->search_term[self::REQUEST_ID_LIST] .= $ii;
            }
        }
        // Fix subject, id search 2013.12.16 Y.Nakao --end--
        
        // Comment 2014/-8/25 Y.Nakao --start--
        // $detailSearchFlagは下記の理由で利用されているため削除しないでください
        //  * NC2は「***_id」という変数を自動的にintでキャストします
        //  * このため、TOPページ表示時などで「index_id=""」のリクエストが「index_id=0」となる場合があります
        //  * 本現象の対処として、index_id以外の検索条件がない場合はindex_idを無効化する措置を
        //  * $detailSearchFlagで行っています
        // Comment 2014/-8/25 Y.Nakao --end--
        if($detailSearchFlag && strlen($this->index_id) > 0){
            if(isset($this->search_term[self::REQUEST_IDX]) && strlen($this->search_term[self::REQUEST_IDX]) > 0){
                $this->search_term[self::REQUEST_IDX] .= ",".$this->index_id;
            } else {
                $this->search_term[self::REQUEST_IDX] = $this->index_id;
            }
            $this->index_id = "";
        }
        // validate
        $this->validate();
        
        $this->setActionParameter();
    }
    
    /**
     * request query
     *
     * @return string
     */
    public function getRequestQuery()
    {
        $req = array();
        if(strlen($this->index_id) > 0)
        {
            array_push($req, self::REQUEST_INDEX_ID."=".$this->index_id);
        }
        
        if(strlen($this->page_no) > 0)
        {
            array_push($req, self::REQUEST_PAGENO."=".$this->page_no);
        }
        
        if(strlen($this->list_view_num) > 0)
        {
            array_push($req, self::REQUEST_COUNT."=".$this->list_view_num);
        }
        
        if(strlen($this->sort_order) > 0)
        {
            array_push($req, self::REQUEST_SHOWORDER."=".$this->sort_order);
        }
        
        if(strlen($this->format) > 0)
        {
            array_push($req, self::REQUEST_OUTPUT_TYPE."=".$this->format);
        }
        
        if(strlen($this->lang) > 0)
        {
            array_push($req, self::REQUEST_DISPLAY_LANG."=".$this->lang);
        }
        
        if(strlen($this->listResords) > 0)
        {
            array_push($req, self::REQUEST_LIST_RECORDS."=".$this->listResords);
        }
        
        foreach($this->search_term as $requestParam => $requestValue)
        {
            array_push($req, $requestParam."=".urlencode($requestValue));
        }
        return implode("&", $req);
    }
    
    /**
     * get request parameter array
     *
     * @return array
     */
    public function getRequestParameter()
    {
        $req = $this->search_term;
        $req[self::REQUEST_INDEX_ID]        = $this->index_id;
        $req[self::REQUEST_PAGENO]         = $this->page_no;
        $req[self::REQUEST_COUNT]   = $this->list_view_num;
        $req[self::REQUEST_SHOWORDER]      = $this->sort_order;
        $req[self::REQUEST_OUTPUT_TYPE]          = $this->format;
        $req[self::REQUEST_DISPLAY_LANG]            = $this->lang;
        $req[self::REQUEST_LIST_RECORDS]     = $this->listResords;
        if($this->search_type != null){
            $req[self::REQUEST_SEARCH_TYPE]     = $this->search_type;
        }
        return $req;
    }
    
    /**
     * get request parameter array
     *
     * @return array
     */
    public function getRequestParameterList()
    {
        $req = array();
        foreach($this->search_term as $requestParam => $requestValue)
        {
            $param = array();
            $param["param"]=$requestParam;
            $param["value"]=rawurlencode($requestValue);
            array_push($req, $param);
        }
        if($this->index_id != null){
            $param = array();
            $param["param"]="index_id";
            $param["value"]=$this->index_id;
            array_push($req, $param);
        }
        return $req;
    }
    
    /**
     * validate page no for over max page no
     *
     * @param unknown_type $maxPageNo
     */
    public function validatePageNo($maxPageNo)
    {
        if($maxPageNo < $this->page_no)
        {
            $this->page_no = $maxPageNo;
        }
    }
    
    /**
     * validate sort order
     *
     * @param int $sortOrder
     */
    public function validateSortOrder($searchTerm, $indexId, $sortOrder)
    {
        // validate int
        $sortOrder = intval($sortOrder);
        
        // 表示可能なソート条件のみ指定可能 /out of display sort order
        $this->RepositoryAction->getAdminParam("sort_disp", $order, $errorMsg);
        $availableSortOrder = explode("|", $order);
        if(!is_numeric(array_search($sortOrder, $availableSortOrder)))
        {
            $sortOrder = 0;
        }
        
        // インデックス特化ソート / when sort_order is custom sort order, index_id indispensable.
        if(strlen($indexId) == 0)
        {
            if($sortOrder == self::ORDER_CUSTOM_SORT_ASC || $sortOrder == self::ORDER_CUSTOM_SORT_DESC)
            {
                $sortOrder = 0;
            }
        }
        
        if($sortOrder > 0)
        {
            return $sortOrder;
        }
        
        $this->RepositoryAction->getAdminParam("sort_disp_default", $order, $errorMsg);
        $orderArray = explode("|", $order, 2);
        // first keyword search
        if(isset($orderArray[1]) && count($searchTerm) > 0)
        {
            $sortOrder = $orderArray[1];
        }
        else if(isset($orderArray[0]) && strlen($indexId) > 0)
        {
            $sortOrder = $orderArray[0];
        }
        else 
        {
            $sortOrder = self::ORDER_WEKO_ID_ASC;
        }
        
        return $sortOrder;
    }
    
    /**
     * インデックス検索かキーワード検索か判定する
     *
     * @return 検索タイプ
     */
    public function getSearchType(){
        $type = '';
        
        //TODO:phase13.0の分岐はここに追加
        //if(インデックス&&アイテムタイプ && Junii2マッピング && ){retun 'detail';}
        
        //インデックス検索
        if(strlen($this->index_id) > 0){
            $type = 'index';
        }
        if(count($this->search_term) > 0){
            $type = 'keyword';
        }
        
        return $type;
    }
    
    /**
     * validate request parameter
     * リクエストパラメータを精査し、範囲外の場合は適切な値を代入する
     *
     */
    private function validate()
    {
        $this->validateBackwordCompatible();
        
        ///// search keys  検索条件 /////
        foreach($this->search_term as $requestParam => $requestValue){
            if($requestParam == self::REQUEST_WEKO_ID){
                $tmpValue = RepositoryOutputFilter::string($requestValue);
                if($tmpValue != 0){
                    if(strlen($tmpValue) <= 8){
                        $this->search_term[$requestParam] = sprintf("%08d", $tmpValue);
                    } else {
                        // error
                        $this->search_term[$requestParam] = 0;
                    }
                }
            }
            else if($requestParam == self::REQUEST_SUBJECT_LIST || $requestParam == self::REQUEST_ITEMTYPE_LIST
                       || $requestParam == self::REQUEST_TYPE_LIST || $requestParam == self::REQUEST_ID_LIST
                       || $requestParam == self::REQUEST_IDX
                       || $requestParam == self::REQUEST_ITEM_IDS){// Add suppleContentsEntry Y.Yamazawa 2015/03/20
                $tmpValue = RepositoryOutputFilter::string($requestValue);
                $divideValue = explode(",", $tmpValue);
                $tmpValue = "";
                $count = 0;
                for($ii = 0; $ii < count($divideValue); $ii++){
                    if(strlen($divideValue[$ii]) == 0){
                        continue;
                    }
                    $divideValue[$ii] = (string)intval($divideValue[$ii]);
                    if($count > 0){
                        $tmpValue .= ",".$divideValue[$ii];
                    } else {
                        $tmpValue = $divideValue[$ii];
                    }
                    $count++;
                }
                $this->search_term[$requestParam] = $tmpValue;
            } else if($requestParam == self::REQUEST_TEXTVERSION || $requestParam == self::REQUEST_LANGUAGE
                   || $requestParam == self::REQUEST_RIGHT_LIST){
                $tmpValue = $requestValue;
                $tmpValue = trim(mb_convert_encoding($tmpValue, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
                $tmpValue = RepositoryOutputFilter::string($tmpValue);
                $this->search_term[$requestParam] = preg_replace("/[\s,]+|　/", ",", $tmpValue);
            } else {
                $tmpValue = $requestValue;
                $tmpValue = trim(mb_convert_encoding($tmpValue, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS"));
                $tmpValue = RepositoryOutputFilter::string($tmpValue);
                $this->search_term[$requestParam] = preg_replace("/[\s]+|　|\+/", " ", $tmpValue);
            }
            
            // Bug fix WEKO-2014-012 T.Koyasu 2014/06/10 --start--
            $this->validateList($requestParam);
            // Bug fix WEKO-2014-012 T.Koyasu 2014/06/10 --end--
        }
        
        $this->index_id = RepositoryOutputFilter::string($this->index_id);
        // $this->index_idの指定がある または 詳細検索条件がない かつ $this->index_id==nullの場合
        if(strlen($this->index_id) > 0 || (count($this->search_term) == 0 && strlen($this->index_id) == 0))
        {
            $this->index_id = (string)intval($this->index_id);
        }
        
        ///// view keys  表示条件 /////
        
        $this->page_no = RepositoryOutputFilter::string($this->page_no);
        if(strlen($this->page_no) == 0)
        {
            $this->page_no = 1;
        }
        else if(!is_numeric($this->page_no))
        {
            $this->page_no = 1;
        }
        else
        {
            $this->page_no = intval($this->page_no);
            if($this->page_no < 1)
            {
                $this->page_no = 1;
            }
        }
        
        $this->list_view_num = RepositoryOutputFilter::string($this->list_view_num);
        $this->list_view_num = intval($this->list_view_num);
        
        if(strlen($this->list_view_num) == 0 || $this->list_view_num == 0)
        {
            $this->RepositoryAction->getAdminParam("default_list_view_num", $this->list_view_num, $errorMsg);
        }
        else if($this->list_view_num <= 20)
        {
            $this->list_view_num = 20;
        }
        else if($this->list_view_num <= 50)
        {
            $this->list_view_num = 50;
        }
        else if($this->list_view_num <= 75)
        {
            $this->list_view_num = 75;
        }
        else if($this->list_view_num <= 100 || $this->list_view_num > 100)
        {
            $this->list_view_num = 100;
        }
        
        $this->sort_order = RepositoryOutputFilter::string($this->sort_order);
        $this->sort_order = $this->validateSortOrder($this->search_term, $this->index_id, $this->sort_order);
        // 言語指定あり
        $this->lang = RepositoryOutputFilter::string($this->lang);
        $this->lang = strtolower($this->lang);
        if($this->Session != null && strlen($this->lang) == 0)
        {
            $this->lang = $this->Session->getParameter("_lang");
        }
        // 設定されていない言語が選択された場合、日本語で表示する
        $query = "SELECT lang_dirname FROM ".DATABASE_PREFIX ."language ".
                 "WHERE lang_dirname = ? ;";
        $params = array();
        $params[] = $this->lang;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        if(count($result) == 0)
        {
            $this->lang = RepositoryConst::ITEM_ATTR_TYPE_LANG_JA;
        }
        
        if(strlen($this->listResords) > 0)
        {
            if($this->listResords != "all")
            {
                $this->listResords = "";
            }
        }
        
        ///// output  出力形式 /////
        $this->format = RepositoryOutputFilter::string($this->format);
        $this->format = strtolower($this->format);
    }
    
    
    /**
     * set request parameter
     * リクエストパラメータを精査し、範囲外の場合は適切な値を代入する
     *
     */
    private function validateBackwordCompatible()
    {
        if(!isset($this->search_term[self::REQUEST_META]) && !isset($this->search_term[self::REQUEST_ALL])){
            if(isset($this->_request[self::REQUEST_OLD_KEYWORD])){
                if(!isset($this->_request[self::REQUEST_OLD_SEARCH_TYPE])){
                    $this->search_term[self::REQUEST_ALL] = $this->_request[self::REQUEST_OLD_KEYWORD];
                } else if($this->_request[self::REQUEST_OLD_SEARCH_TYPE] == "simple"){
                    $this->search_term[self::REQUEST_META] = $this->_request[self::REQUEST_OLD_KEYWORD];
                } else {
                    $this->search_term[self::REQUEST_ALL] = $this->_request[self::REQUEST_OLD_KEYWORD];
                }
            }
        }
        if(!isset($this->_request[self::REQUEST_PAGENO]) && isset($this->_request[self::REQUEST_OLD_PAGENO])){
            $this->page_no = $this->_request[self::REQUEST_OLD_PAGENO];
        }
        if(!isset($this->_request[self::REQUEST_COUNT]) && isset($this->_request[self::REQUEST_OLD_COUNT])){
            $this->list_view_num = $this->_request[self::REQUEST_OLD_COUNT];
        }
        if(!isset($this->_request[self::REQUEST_SHOWORDER]) && isset($this->_request[self::REQUEST_OLD_SHOWORDER])){
            $this->sort_order = $this->_request[self::REQUEST_OLD_SHOWORDER];
        }
    }
    
    public function setActionParameter(){
        $this->initSearchList();
        if($this->search_type != null || count($this->search_term) == 0){
            if(isset($this->search_term[self::REQUEST_META])){
                $keyword = $this->search_term[self::REQUEST_META];
                $this->all_search_type = "simple";
            } else if(isset($this->search_term[self::REQUEST_ALL])){
                $keyword = $this->search_term[self::REQUEST_ALL];
                $this->all_search_type = "detail";
            } else {
            	
            	// Add Default Search Type 2014/12/03 K.Sugimoto --start--
        		$result = $this->getDefaultSearchType();
        		if(isset($result[0]['param_value'])){
        			if($result[0]['param_value'] == 0){
        				$this->all_search_type = "detail";
        			} else if($result[0]['param_value'] == 1){
        				$this->all_search_type = "simple";
        			}
        		}
            	// Add Default Search Type 2014/12/03 K.Sugimoto --end--
            	
                $keyword = "";
            }
            $this->Session->setParameter("searchkeyword", $keyword);
            $this->active_search_flag = 0;   // simple search
        } else {
            $this->setShowSearchParameter($this->search_term);
            $this->active_search_flag = 1;   // detail search
        }
        // 検索選択肢を設定する
        $query = "SELECT type_id ".
                 "FROM ". DATABASE_PREFIX ."repository_search_item_setup ".
                 "WHERE use_search = ?;";
        $params = null;
        $params[] = 1;
        $result = $this->dbAccess->executeQuery($query, $params);
        $this->detail_search_usable_item = $result;
        // 全文検索タイプを設定する
        if(is_null($this->all_search_type))
        {
            $this->all_search_type = "detail";
        }
        
        //アイテムタイプ項目を取得する
        $query = "SELECT item_type_id,item_type_name ".
                 "FROM ". DATABASE_PREFIX ."repository_item_type ";
        $result = $this->dbAccess->executeQuery($query);
        $this->detail_search_item_type = $result;
        
        $this->active_search_flag = intval($this->active_search_flag);
        if($this->active_search_flag < 0){
            $this->active_search_flag = 0;
        } else if($this->active_search_flag > 1){
            $this->active_search_flag = 1;
        }
    }
    
    // Add Default Search Type 2014/12/09 K.Sugimoto --start--
    /**
     * get default_search_type
     *
     * @access public
     */
    public function getDefaultSearchType()
    {
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = "default_search_type";
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        return $result;
    }
    // Add Default Search Type 2014/12/09 K.Sugimoto --end--
    
    /**
     * init session detail_search_select_item
     *
     * @access private
     */
    private function initSearchList()
    {
        $query = "SELECT type_id ".
                 "FROM ". DATABASE_PREFIX ."repository_search_item_setup ".
                 "WHERE default_show = ?;";
        $params = null;
        $params[] = 1;
        $result = $this->dbAccess->executeQuery($query, $params);
        $this->detail_search_select_item = $result;
        $this->default_detail_search = $result;
    }
    
    /**
     * set requestparameter
     *
     * @access private
     */
    private function setShowSearchParameter($reqParam)
    {
        $count = 0;
        $selectInfo = array();
        $subectFlag = false;
        $typeFlag = false;
        $idFlag = false;
        $pubYearFlag = false;
        $rightFlag = false;
        $grantDateFlag = false;
        foreach($reqParam as $key => $value){
            switch($key)
            {
                case self::REQUEST_META:
                    $this->Session->setParameter("searchkeyword", $value);
                    $this->all_search_type = "simple";
                    continue;
                    break;
                case self::REQUEST_ALL:
                    $this->Session->setParameter("searchkeyword", $value);
                    $this->all_search_type = "detail";
                    continue;
                    break;
                case self::REQUEST_TITLE:
                    $selectInfo[$count]["type_id"] = "1";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_CREATOR:
                    $selectInfo[$count]["type_id"] = "2";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_KEYWORD:
                    $selectInfo[$count]["type_id"] = "3";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_SUBJECT_DESC:
                case self::REQUEST_SUBJECT_LIST:
                    if($subectFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "4";
                    if(isset($reqParam[self::REQUEST_SUBJECT_DESC])){
                        $selectInfo[$count]["value"] = $reqParam[self::REQUEST_SUBJECT_DESC];
                    }
                    if(isset($reqParam[self::REQUEST_SUBJECT_LIST])){
                        $selectInfo[$count]["checkList"] = $reqParam[self::REQUEST_SUBJECT_LIST];
                    }
                    $subectFlag = true;
                    $count++;
                    break;
                case self::REQUEST_DESCRIPTION:
                    $selectInfo[$count]["type_id"] = "5";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_PUBLISHER:
                    $selectInfo[$count]["type_id"] = "6";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_CONTRIBUTOR:
                    $selectInfo[$count]["type_id"] = "7";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_DATE:
                    $selectInfo[$count]["type_id"] = "8";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_ITEMTYPE_LIST:
                    $selectInfo[$count]["type_id"] = "9";
                    $selectInfo[$count]["checkList"] = $value;
                    $count++;
                    break;
                case self::REQUEST_TYPE_LIST:
                    if($typeFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "10";
                    if(isset($reqParam[self::REQUEST_TYPE_LIST])){
                        $selectInfo[$count]["checkList"] = $reqParam[self::REQUEST_TYPE_LIST];
                    }
                    $typeFlag = true;
                    $count++;
                    break;
                case self::REQUEST_FORMAT:
                    $selectInfo[$count]["type_id"] = "11";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_ID_LIST:
                case self::REQUEST_ID_DESC:
                    if($idFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "12";
                    if(isset($reqParam[self::REQUEST_ID_DESC])){
                        $selectInfo[$count]["value"] = $reqParam[self::REQUEST_ID_DESC];
                    }
                    if(isset($reqParam[self::REQUEST_ID_LIST])){
                        $selectInfo[$count]["checkList"] = $reqParam[self::REQUEST_ID_LIST];
                    }
                    $idFlag = true;
                    $count++;
                    break;
                case self::REQUEST_JTITLE:
                    $selectInfo[$count]["type_id"] = "13";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_PUBYEAR_FROM:
                case self::REQUEST_PUBYEAR_UNTIL:
                    if($pubYearFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "14";
                    $selectInfo[$count]["value"] = "";
                    if(isset($reqParam[self::REQUEST_PUBYEAR_FROM])){
                        $selectInfo[$count]["value"] .= $reqParam[self::REQUEST_PUBYEAR_FROM];
                    }
                    $selectInfo[$count]["value"] .= "|";
                    if(isset($reqParam[self::REQUEST_PUBYEAR_UNTIL])){
                        $selectInfo[$count]["value"] .= $reqParam[self::REQUEST_PUBYEAR_UNTIL];
                    }
                    $pubYearFlag = true;
                    $count++;
                    break;
                case self::REQUEST_LANGUAGE:
                    $selectInfo[$count]["type_id"] = "15";
                    $selectInfo[$count]["checkList"] = $value;
                    $count++;
                    break;
                case self::REQUEST_AREA:
                    $selectInfo[$count]["type_id"] = "16";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_ERA:
                    $selectInfo[$count]["type_id"] = "17";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_RITHT_DESC:
                case self::REQUEST_RIGHT_LIST:
                    if($rightFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "18";
                    if(isset($reqParam[self::REQUEST_RIGHT_LIST])){
                        $selectInfo[$count]["checkList"] = $reqParam[self::REQUEST_RIGHT_LIST];
                    }
                    if(isset($reqParam[self::REQUEST_RITHT_DESC])){
                        $selectInfo[$count]["value"] = $reqParam[self::REQUEST_RITHT_DESC];
                        $selectInfo[$count]["checkList"] .= ",free_input";
                    }
                    $rightFlag = true;
                    $count++;
                    break;
                case self::REQUEST_TEXTVERSION:
                    $selectInfo[$count]["type_id"] = "19";
                    if(strtolower($value) == "etd")
                    {
                        $value = strtoupper($value);
                    }
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_GRANTID:
                    $selectInfo[$count]["type_id"] = "20";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_GRANTDATE_FROM:
                case self::REQUEST_GRANTDATE_UNTIL:
                    if($grantDateFlag){
                        break;
                    }
                    $selectInfo[$count]["type_id"] = "21";
                    $selectInfo[$count]["value"] = "";
                    if(isset($reqParam[self::REQUEST_GRANTDATE_FROM])){
                        $selectInfo[$count]["value"] .= $reqParam[self::REQUEST_GRANTDATE_FROM];
                    }
                    $selectInfo[$count]["value"] .= "|";
                    if(isset($reqParam[self::REQUEST_GRANTDATE_UNTIL])){
                        $selectInfo[$count]["value"] .= $reqParam[self::REQUEST_GRANTDATE_UNTIL];
                    }
                    $grantDateFlag = true;
                    $count++;
                    break;
                case self::REQUEST_DEGREENAME:
                    $selectInfo[$count]["type_id"] = "22";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_GRANTOR:
                    $selectInfo[$count]["type_id"] = "23";
                    $selectInfo[$count]["value"] = $value;
                    $count++;
                    break;
                case self::REQUEST_IDX:
                    $selectInfo[$count]["type_id"] = "24";
                    $selectInfo[$count]["checkList"] = $value;
                    $count++;
                    break;
                default:
                    break;
            }
        }
        if(count($selectInfo) == 0){
            $this->initSearchList();
        } else {
            $this->detail_search_select_item = $selectInfo;
        }
    }
    
    /**
     * set default search parameter
     *
     */
    public function setDefaultSearchParameter()
    {
        // set search index
        $errorMsg = "";
        $this->RepositoryAction->getAdminParam('disp_index_type', $disp_index_type, $errorMsg);
        $this->RepositoryAction->getAdminParam('default_disp_index', $default_disp_index, $errorMsg);

        // 最も新しいアイテムのインデックス
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Add OpenDepo 2013/12/02 R.Matsuura --end--
        if($disp_index_type == 1)
        {
            // インデックス指定
            $this->index_id = $default_disp_index;
            $public_index = $indexAuthorityManager->getPublicIndexQuery(false, $this->RepositoryAction->repository_admin_base, $this->RepositoryAction->repository_admin_room, $this->index_id);
            if(count($public_index) == 0){
                // Index closed.
                // Set root index for default search index.
                $this->index_id = 0;
            }
        }
        else
        {
            $publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(false, $this->RepositoryAction->repository_admin_base, $this->RepositoryAction->repository_admin_room);
            $sqlCmd = "SELECT idx.index_id ".
                      "FROM ". DATABASE_PREFIX ."repository_item AS item, ".
                      "     ". DATABASE_PREFIX ."repository_index AS idx, ".
                      "     ". DATABASE_PREFIX ."repository_position_index AS pidx ".
                      "INNER JOIN (".$publicIndexQuery.") pub ON pidx.index_id = pub.index_id ".
                      "WHERE item.shown_date<=NOW() ".
                      "AND item.item_id = pidx.item_id ".
                      "AND item.item_no = pidx.item_no ".
                      "AND item.shown_status = 1 ".
                      "AND pidx.index_id = idx.index_id ".
                      "AND idx.pub_date<=NOW() ".
                      "AND idx.public_state = 1 ".
                      "AND item.is_delete = 0 ".
                      "AND pidx.is_delete = 0 ".
                      "AND idx.is_delete = 0 ".
                      " ORDER BY item.shown_date desc, item.item_id desc; ";
            $items = $this->dbAccess->executeQuery($sqlCmd);
            if($items === false)
            {
                $this->index_id = 0;
            }
            if(count($items)==0){
                // if no items, shows root index
                $this->index_id = 0;
            } else {
                $this->index_id = $items[0]['index_id'];
            }
        }
        
        // validate
        $this->validate();
        
        $this->setActionParameter();
    }
    
    // Bug fix WEKO-2014-012 T.Koyasu 2014/06/10 --start--
    /**
     * Validate request parameters(search_term[$key]) for remove bad strings
     *
     * @param string $key
     */
    private function validateList($key)
    {
        $value = $this->search_term[$key];
        if(strlen($value) === 0){
            return;
        }
        
        // check min occur and max occur of each param
        switch($key)
        {
            case self::REQUEST_SUBJECT_LIST:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                $searchArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
                $validateArray = array_intersect($searchArray, $tmpArray);
                
                $this->search_term[$key] = implode(",", $validateArray);
                
                break;
            case self::REQUEST_ITEMTYPE_LIST:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                
                $query = "SELECT item_type_id ". 
                         " FROM ". DATABASE_PREFIX. "repository_item_type ".
                         " WHERE is_delete = ? ". 
                         " AND item_type_id IN (";
                $params = array();
                $params[] = 0;
                
                $tmpStr = "";
                for($ii = 0; $ii < count($tmpArray); $ii++)
                {
                    if(strlen($tmpStr) > 0){
                        $tmpStr .= ",";
                    }
                    $tmpStr .= "?";
                    $params[] = $tmpArray[$ii];
                }
                $query .= $tmpStr. ");";
                $result = $this->dbAccess->executeQuery($query, $params);
                
                $this->search_term[$key] = "";
                for($ii = 0; $ii < count($result); $ii++)
                {
                    if(strlen($this->search_term[$key]) > 0){
                        $this->search_term[$key] .= ",";
                    }
                    $this->search_term[$key] .= $result[$ii]["item_type_id"];
                }
                
                break;
            case self::REQUEST_TYPE_LIST:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                $searchArray = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13);
                $validateArray = array_intersect($searchArray, $tmpArray);
                
                $this->search_term[$key] = implode(",", $validateArray);
                
                break;
            case self::REQUEST_ID_LIST:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                $searchArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
                $validateArray = array_intersect($searchArray, $tmpArray);
                
                $this->search_term[$key] = implode(",", $validateArray);
                
                break;
            case self::REQUEST_LANGUAGE:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                $searchArray = array("ja", "en", "fr", "it", "de", "es", "zh", "ru", "la", "ms", "eo", "ar", "el", "ko", "other");
                $validateArray = array_intersect($searchArray, $tmpArray);
                
                $this->search_term[$key] = implode(",", $validateArray);
                
                break;
            case self::REQUEST_RIGHT_LIST:
                $tmpArray = explode(",", $value);
                if(count($tmpArray) === 0){
                    return;
                }
                $searchArray = array(101, 102, 103, 104, 105, 106, "free_input");
                $validateArray = array_intersect($searchArray, $tmpArray);
                
                $this->search_term[$key] = implode(",", $validateArray);
                
                break;
            default:
                break;
        }
    }
    // Bug fix WEKO-2014-012 T.Koyasu 2014/06/10 --end--
}
?>