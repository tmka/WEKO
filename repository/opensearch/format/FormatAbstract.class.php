<?php
// --------------------------------------------------------------------
//
// $Id: FormatAbstract.class.php 43084 2014-10-20 06:53:41Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatistics.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class Repository_Opensearch_FormatAbstract
{
    // new line
    const LF = "\n";
    
    // get item data key
    const DATA_TITLE = "title";
    const DATA_ALTERNATIVE = "alternative";
    const DATA_URI = "uri";
    const DATA_SWRC = "swrc";
    const DATA_OAIORE = "oai-ore";
    const DATA_WEKO_ID = "weko_id";
    const DATA_MAPPING_INFO = "mapping_info";
    const DATA_ITEM_TYPE_NAME = "item_type_name";
    const DATA_MIME_TYPE = "mime_type";
    const DATA_FILE_URI = "file_uri";
    const DATA_CREATOR = "creator";
    const DATA_PUBLISHER = "publisher";
    const DATA_INDEX_PATH = "index_path";
    const DATA_JTITLE = "jtitle";
    const DATA_ISSN = "issn";
    const DATA_VOLUME = "volume";
    const DATA_ISSUE = "issue";
    const DATA_SPAGE = "spage";
    const DATA_EPAGE = "epage";
    const DATA_DATE_OF_ISSUED = "date_of_issued";
    const DATA_DESCRIPTION = "description";
    const DATA_PUB_DATE = "pub_date";
    const DATA_INS_DATE = "ins_date";
    const DATA_MOD_DATE = "mod_date";
    const DATA_WEKO_LOG_TERM = "log_term";
    const DATA_WEKO_LOG_VIEW = "log_view";
    const DATA_WEKO_LOG_DOWNLOAD = "log_download";
    
    // request parameter key for RepositorySearch
    const REQUEST_KEYWORD = "keyword";
    const REQUEST_INDEX_ID = "index_id";
    const REQUEST_WEKO_ID = "weko_id";
    const REQUEST_PAGE_NO = "page_no";
    const REQUEST_LIST_VIEW_NUM = "list_view_num";
    const REQUEST_SORT_ORDER = "sort_order";
    const REQUEST_SEARCH_TYPE = "search_type";
    const REQUEST_ANDOR = "andor";
    const REQUEST_FORMAT = "format";
    const REQUEST_ITEM_IDS = "item_ids";
    const REQUEST_LANG = "lang";
    
    // request parameter key for Repository_Opensearch
    const REQUEST_LOG_TERM = "log_term";
    const REQUEST_DATA_FILTER = "dataFilter";
    const REQUEST_PREFIX = "prefix";
    
    const DATA_FILTER_SIMPLE = "simple";
    
    // mapping language value
    const DATA_CREATOR_LANG = "creator_lang";
    const DATA_PUBLISHER_LANG = "publisher_lang";
    const DATA_DESCRIPTION_LANG = "description_lang";
    
    /**
     * Session object
     *
     * @var object
     */
    protected $Session = null;
    
    /**
     * Database object
     *
     * @var object
     */
    protected $Db = null;
    
    /**
     * repository action class object
     *
     * @var onject
     */
    protected $RepositoryAction = null;
    
    /**
     * search result total
     *
     * @var int
     */
    private $total = 0;
    
    /**
     * start page 
     *
     * @var int
     */
    private $startIndex = 0;
    
    /**
     * RepositoryHandleManager Object
     * 
     * @var RepositoryHandleManager
     */
    protected $repositoryHandleManager = null;
    
    /**
     * Const
     *
     * @param object $sesssion
     * @param object $db
     * @return Repository_Oaipmh_LearningObjectMetadata
     */
    public function __construct($session, $db)
    {
        if(isset($session) && $session!=null)
        {
            $this->Session = $session;
        }
        else
        {
            return null;
        }
        
        // set database object
        if(isset($db) && $db!=null)
        {
            $this->Db = $db;
        }
        else
        {
            return null;
        }
        
        // set Repository Action class
        $this->RepositoryAction = new RepositoryAction();
        $this->RepositoryAction->Session = $this->Session;
        $this->RepositoryAction->Db = $this->Db;
        
        // individual initialize
        $this->initialize();
    }
    
    /**
     * individual initialize
     */
    private function initialize()
    {
    }
    
    /**
     * make XML for open search 
     * 
     * @param array $result RepositorySearch $searchResult
     * @param int $total total hit num
     * @param int sIdx start index num
     * @param array $searchResult search result
     * @return string
     */
    public function outputXml($request, $total, $sIdx, $searchResult)
    {
        $this->total = $total;
        $this->startIndex = $sIdx;
        
        $xml = "";
        return $xml;
    }
    
    /**
     * get index pankuzu list
     *
     * @param int $indexId
     */
    protected function getIndexPath($indexId, $del="/", $lang=RepositoryConst::ITEM_ATTR_TYPE_LANG_JA)
    {
        // get parents index names
        $index_data = array();
        $this->RepositoryAction->getParentIndex($indexId, $index_data);
        $idx_names = "";
        for($ii=0; $ii<count($index_data); $ii++)
        {
            if($idx_names != "")
            {
                $idx_names .= " $del ";
            }
            if($lang == RepositoryConst::ITEM_ATTR_TYPE_LANG_JA)
            {
                if(strlen($index_data[$ii]["index_name"]) > 0)
                {
                    $idx_names .= $index_data[$ii]["index_name"];
                }
                else
                {
                    $idx_names .= $index_data[$ii]["index_name_english"];
                }
            }
            else
            {
                if(strlen($index_data[$ii]["index_name_english"]) > 0)
                {
                    $idx_names .= $index_data[$ii]["index_name_english"];
                }
                else
                {
                    $idx_names .= $index_data[$ii]["index_name"];
                }
            }
        }
        
        return $idx_names;
    }
    
    /**
     * get item data.
     *
     * @param int $itemId
     * @param int $itemNo
     * @return array
     */
    protected function getOutputData($request, $itemId, $itemNo)
    {
        $itemData = array(self::DATA_TITLE => "",
                        self::DATA_ALTERNATIVE => "",
                        self::DATA_URI => "",
                        self::DATA_SWRC => "",
                        self::DATA_OAIORE => array(),
                        self::DATA_WEKO_ID => "",
                        self::DATA_MAPPING_INFO => "",
                        self::DATA_ITEM_TYPE_NAME => "",
                        self::DATA_MIME_TYPE => array(),
                        self::DATA_FILE_URI => array(),
                        self::DATA_CREATOR => array(),
                        self::DATA_CREATOR_LANG => array(),
                        self::DATA_PUBLISHER => array(),
                        self::DATA_PUBLISHER_LANG => array(),
                        self::DATA_INDEX_PATH => array(),
                        self::DATA_JTITLE => "",
                        self::DATA_ISSN => "",
                        self::DATA_VOLUME => "",
                        self::DATA_ISSUE => "",
                        self::DATA_SPAGE => "",
                        self::DATA_EPAGE => "",
                        self::DATA_DATE_OF_ISSUED => "",
                        self::DATA_DESCRIPTION => array(),
                        self::DATA_DESCRIPTION_LANG => array(),
                        self::DATA_PUB_DATE => "",
                        self::DATA_INS_DATE => "",
                        self::DATA_MOD_DATE => "",
                        self::DATA_WEKO_LOG_TERM => "",
                        self::DATA_WEKO_LOG_VIEW => "",
                        self::DATA_WEKO_LOG_DOWNLOAD => "");
        
        $data = array();
        $errorMsg = "";
        $status = $this->RepositoryAction->getItemData($itemId, $itemNo, $data, $errorMsg, false, true);
        $status = $this->RepositoryAction->getItemIndexData($itemId, $itemNo, $data, $errorMsg);
        
        $item           = $data[RepositoryConst::ITEM_DATA_KEY_ITEM][0];
        $itemType       = $data[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE][0];
        $itemAttrType   = $data[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE];
        $itemAttr       = $data[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR];
        $posIndex       = $data[RepositoryConst::ITEM_DATA_KEY_POSITION_INDEX];
        
        ///// setting title and alternative /////
        if($request[self::REQUEST_LANG] == RepositoryConst::ITEM_ATTR_TYPE_LANG_EN)
        {
            if(strlen($item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0)
            {
                $itemData[self::DATA_TITLE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                if(strlen($item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0)
                {
                    $itemData[self::DATA_ALTERNATIVE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                }
            }
            else
            {
                $itemData[self::DATA_TITLE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
            }
        }
        else
        {
            if(strlen($item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0)
            {
                $itemData[self::DATA_TITLE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                if(strlen($item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0)
                {
                    $itemData[self::DATA_ALTERNATIVE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                }
            }
            else
            {
                $itemData[self::DATA_TITLE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
            }
        }
        
        ///// setting uri /////
        //if(strlen($item[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI]) > 0)
        //{
        //    $itemData[self::DATA_URI] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI];
        //}
        
        ///// setting swrc /////
        $itemData[self::DATA_SWRC] = BASE_URL."/?action=repository_swrc&itemId=$itemId&itemNo=$itemNo";        
        ///// setting oai-ore /////
        // for item
        array_push($itemData[self::DATA_OAIORE], BASE_URL."/?action=repository_oaiore&itemId=$itemId&itemNo=$itemNo");
        for($ii=0; $ii<count($posIndex); $ii++)
        {
            // for position index
            array_push($itemData[self::DATA_OAIORE], BASE_URL."/?action=repository_oaiore&indexId=".$posIndex[$ii]["index_id"]);
            $path = $this->getIndexPath($posIndex[$ii]["index_id"]);
            array_push($itemData[self::DATA_INDEX_PATH], $path);
            $idx = strrpos($path, "/");
            if(is_null($idx))
            {
                $path = substr($path, 0, $idx);
                array_push($itemData[self::DATA_INDEX_PATH], $path);
            }
        }
        
        // suffix(weko id)
        $this->getRepositoryHandleManager();
        $suffix = $this->repositoryHandleManager->getSuffix($itemId, $itemNo, RepositoryHandleManager::ID_Y_HANDLE);
        
        $wekoId = $suffix;
        
        ///// setting item type id /////
        $itemData[self::DATA_ITEM_TYPE_NAME] = $itemType[RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_NAME];
        
        ///// setting mapping info /////
        $itemData[self::DATA_MAPPING_INFO] = $itemType[RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_MAPPING_INFO];
        
        ///// setting meatdata /////
        for($ii=0; $ii<count($itemAttrType); $ii++)
        {
            $inputType  = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
            $mapping    = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_JUNII2_MAPPING];
            
            // Add data filter parameter Y.Nakao 2013/05/17 --start--
            // not output hidden metadata
            if($itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1)
            {
                continue;
            }
            
            if($request[self::REQUEST_DATA_FILTER] == self::DATA_FILTER_SIMPLE && $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LIST_VIEW_ENABLE] == 0)
            {
                // when data fileter is "simple", output list_view_enable=1 metadata.
                continue;
            }
            // Add data filter parameter Y.Nakao 2013/05/17 --end--
            
            for($jj=0; $jj<count($itemAttr[$ii]); $jj++)
            {
                /// set file information
                if($inputType == RepositoryConst::ITEM_ATTR_TYPE_FILE)
                {
                    // set file info
                    if(strlen($itemData[self::DATA_MIME_TYPE]) > 0)
                    {
                        array_push($itemData[self::DATA_MIME_TYPE], $itemAttr[$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_MIME_TYPE]);
                        $fileUri = BASE_URL."/?action=repository_uri".
                                   "&item_id=".$itemAttr[$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID].
                                   "&file_id=".$itemAttr[$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID].
                                   "&file_no=".$itemAttr[$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO];
                        array_push($itemData[self::DATA_FILE_URI], $fileUri);
                    }
                }
                
                /// check value
                $value = RepositoryOutputFilter::attributeValue($itemAttrType[$ii], $itemAttr[$ii][$jj], 2);
                if(strlen($value) == 0)
                {
                    continue;
                }
                
                if($mapping == RepositoryConst::JUNII2_CREATOR)
                {
                    array_push($itemData[self::DATA_CREATOR], $value);
                    array_push($itemData[self::DATA_CREATOR_LANG], $itemAttrType[$ii]["display_lang_type"]);
                }
                else if($mapping == RepositoryConst::JUNII2_PUBLISHER)
                {
                    array_push($itemData[self::DATA_PUBLISHER], $value);
                    array_push($itemData[self::DATA_PUBLISHER_LANG], $itemAttrType[$ii]["display_lang_type"]);
                }
                else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO)
                {
                    $biblio = explode("||", $value);
                    if(strlen($itemData[self::DATA_JTITLE]) == 0 && strlen($biblio[0]) > 0)
                    {
                        $itemData[self::DATA_JTITLE] = $biblio[0];
                    }
                    if(strlen($itemData[self::DATA_VOLUME]) == 0 && strlen($biblio[1]) > 0)
                    {
                        $itemData[self::DATA_VOLUME] = $biblio[1];
                    }
                    if(strlen($itemData[self::DATA_ISSUE]) == 0 && strlen($biblio[2]) > 0)
                    {
                        $itemData[self::DATA_ISSUE] = $biblio[2];
                    }
                    if(strlen($itemData[self::DATA_SPAGE]) == 0 && strlen($biblio[3]) > 0)
                    {
                        $itemData[self::DATA_SPAGE] = $biblio[3];
                    }
                    if(strlen($itemData[self::DATA_EPAGE]) == 0 && strlen($biblio[4]) > 0)
                    {
                        $itemData[self::DATA_EPAGE] = $biblio[4];
                    }
                    if(strlen($itemData[self::DATA_DATE_OF_ISSUED]) == 0 && strlen($biblio[5]) > 0)
                    {
                        $itemData[self::DATA_DATE_OF_ISSUED] = $biblio[5];
                    }
                }
                else if(strlen($itemData[self::DATA_JTITLE]) == 0 && $mapping == RepositoryConst::JUNII2_JTITLE)
                {
                    $itemData[self::DATA_JTITLE] = $value;
                }
                else if(strlen($itemData[self::DATA_VOLUME]) == 0  && $mapping == RepositoryConst::JUNII2_VOLUME)
                {
                    $itemData[self::DATA_VOLUME] = $value;
                }
                else if(strlen($itemData[self::DATA_ISSUE]) == 0 && $mapping == RepositoryConst::JUNII2_ISSUE)
                {
                    $itemData[self::DATA_ISSUE] = $value;
                }
                else if(strlen($itemData[self::DATA_SPAGE]) == 0 && $mapping == RepositoryConst::JUNII2_SPAGE)
                {
                    $itemData[self::DATA_SPAGE] = $value;
                }
                else if(strlen($itemData[self::DATA_EPAGE]) == 0 && $mapping == RepositoryConst::JUNII2_EPAGE)
                {
                    $itemData[self::DATA_EPAGE] = $value;
                }
                else if(strlen($itemData[self::DATA_DATE_OF_ISSUED]) == 0 && $mapping == RepositoryConst::JUNII2_DATE_OF_ISSUED)
                {
                    $itemData[self::DATA_DATE_OF_ISSUED] = $value;
                }
                else if(strlen($itemData[self::DATA_ISSN]) == 0 && $mapping == RepositoryConst::JUNII2_ISSN)
                {
                    $itemData[self::DATA_ISSN] = $value;
                }
                else if($mapping == RepositoryConst::JUNII2_DESCRIPTION)
                {
                    array_push($itemData[self::DATA_DESCRIPTION], $value);
                    array_push($itemData[self::DATA_DESCRIPTION_LANG], $itemAttrType[$ii]["display_lang_type"]);
                }
            }
        }

        // Add pubdate 2014/08/01 Y.Nakao --start--
        ///// setting pub_date /////
        $itemData[self::DATA_PUB_DATE] = $item[RepositoryConst::DBCOL_REPOSITORY_ITEM_SHOWN_DATE];
        // Add pubdate 2014/08/01 Y.Nakao --end--
        
        ///// setting ins_date and mod_date /////
        $itemData[self::DATA_INS_DATE] = $item[RepositoryConst::DBCOL_COMMON_INS_DATE];
        $itemData[self::DATA_MOD_DATE] = $item[RepositoryConst::DBCOL_COMMON_MOD_DATE];
        
        return $itemData;
    }
    
    /**
     * アイテムログから閲覧情報とダウンロード回数を取得
     *
     * @param array $item_data
     * @return array
     */
    protected function getItemLogData($request, &$item_data)
    {
        $terms = explode("-", $request[self::REQUEST_LOG_TERM]);
        if(count($terms) == 2 && (int)$terms[0] != 0 && (int)$terms[1] != 0){
            $year = $terms[0];
            $month = $terms[1];
            // ログ解析除外IPアドレス(log exception)
            $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = 'log_exclusion'; ";
            $ip_list = $this->Db->execute($query);
            if(ip_list === false){
                return false;
            }
            $log_exception = "";
            $ip_list = str_replace("\r\n", "\n", $ip_list[0]["param_value"]);
            $ip_list = str_replace("\r", "\n", $ip_list);
            if($ip_list != ""){
                $ip_list = explode("\n", $ip_list);
                for($ii=0; $ii<count($ip_list); $ii++){
                    $log_exception .= " AND ". DATABASE_PREFIX ."repository_log.ip_address <> '$ip_list[$ii]' ";
                }
            }
            
            $transStartDate = $this->getNowDate();
            
            $usagestatistics = new RepositoryUsagestatistics($this->Session, $this->Db, $transStartDate);
            for($ii=0; $ii<count($item_data); $ii++){
                if($item_data[$ii]['item_id'] != "" && $item_data[$ii]['item_no'] != ""){
                    $item_data[$ii][self::DATA_WEKO_LOG_TERM] = $request[self::REQUEST_LOG_TERM];
                    
                    // 閲覧回数取得
                    $usageViews = $usagestatistics->getUsagesViews($item_data[$ii]['item_id'], $item_data[$ii]['item_no'], $year, $month);
                    $item_data[$ii][self::DATA_WEKO_LOG_VIEW] = (string)$usageViews["total"];
                    
                    // ダウンロード回数取得
                    $usagesDownloads = $usagestatistics->getUsagesdownloads($item_data[$ii]['item_id'], $item_data[$ii]['item_no'], $year, $month);
                    $totalDownloadNum = 0;
                    for($cnt = 0; $cnt < count($usagesDownloads); $cnt++)
                    {
                        $totalDownloadNum += $usagesDownloads[$cnt]["usagestatistics"]["total"];
                    }
                    $item_data[$ii][self::DATA_WEKO_LOG_DOWNLOAD] = (string)$totalDownloadNum;
                }
            }
        }
    }
    
    /**
     * Get now date
     * 
     * @return string
     */
    private function getNowDate()
    {
        $DATE = new Date();
        return $DATE->getDate().".000";
    }
    
    protected function getRepositoryHandleManager()
    {
        if(!isset($this->repositoryHandleManager))
        {
            if(!isset($this->RepositoryAction->dbAccess))
            {
                $this->RepositoryAction->dbAccess = new RepositoryDbAccess($this->Db);
            }
            if(!isset($this->RepositoryAction->TransStartDate))
            {
                $DATE = new Date();
                $this->RepositoryAction->TransStartDate = $DATE->getDate(). ".000";
            }
            $this->repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->RepositoryAction->dbAccess, $this->RepositoryAction->TransStartDate);
        }
    }
    
    /**
     * Get other language display setting
     * 
     * @return array
     */
    protected function getAlternativeLanguage()
    {
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "alternative_language";
        $result = $this->Db->execute($query, $params);
        
        $lang_display_params = array();
        $language = explode(",", $result[0]["param_value"]);
        $japanese = explode(":", $language[0]);
        $english = explode(":", $language[1]);
        $lang_display_params["japanese"] = $japanese[1];
        $lang_display_params["english"] = $english[1];
        
        return $lang_display_params;
    }
}

?>