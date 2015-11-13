<?php
// --------------------------------------------------------------------
// 
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR."/modules/repository/opensearch/format/FormatAbstract.class.php";
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * repository search class
 * 
 */
class Repository_OpenSearch_Rss extends Repository_Opensearch_FormatAbstract
{
    const XMLNS_RSS         = "http://purl.org/rss/1.0/";
    const XMLNS_RDF         = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
    const XMLNS_RDFS        = "http://www.w3.org/2000/01/rdf-schema#";
    const XMLNS_DC          = "http://purl.org/dc/elements/1.1/";
    const XMLNS_DCTERMS     = "http://purl.org/dc/terms/";
    const XMLNS_PRISM       = "http://prismstandard.org/namespaces/basic/2.0/";
    const XMLNS_OPENSEARCH  = "http://a9.com/-/spec/opensearch/1.1/";
    const XMLNS_WEKOLOG     = "/wekolog/";
    
    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        parent::__construct($session, $db);
    }
    
    /**
     * make RSS XML for open search 
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
        
        ///// set data /////
        $xml = "";
        
        ///// header /////
        $xml .= $this->outputHeader($request);
        
        // Bug Fix WEKO-2014-063 T.Koyasu 2014/08/07 --start--
        if($request[self::REQUEST_PREFIX])
        {
            ///// prefix ID /////
            $xml .= $this->outputPrefixId();
        }
        else if( (isset($request[self::REQUEST_KEYWORD]) && strlen($request[self::REQUEST_KEYWORD]) > 0) || (isset($request[self::REQUEST_INDEX_ID]) && strlen($request[self::REQUEST_INDEX_ID]) > 0) ||
            (isset($request[self::REQUEST_WEKO_ID]) && strlen($request[self::REQUEST_WEKO_ID]) > 0) || (isset($request[self::REQUEST_ITEM_IDS]) && strlen($request[self::REQUEST_ITEM_IDS]) > 0) ||
            count($searchResult) > 0)
        {
            ///// channel /////
            $xml .= $this->outputChannel($request, $searchResult);
            
            ///// item /////
            $xml .= $this->outputItem($request, $searchResult);
        }
        // Bug Fix WEKO-2014-063 T.Koyasu 2014/08/07 --end--
        
        ///// footer /////
        $xml .= $this->outputFooter();
        
        return $xml;
    }
    
    /**
     * output header
     * 
     * @param array $result RepositorySearch $searchResult
     * @return string
     */
    private function outputHeader($request)
    {
        $lang = RepositoryConst::ITEM_ATTR_TYPE_LANG_JA;
        if($request[self::REQUEST_LANG] == RepositoryConst::ITEM_ATTR_TYPE_LANG_EN)
        {
            $lang = RepositoryConst::ITEM_ATTR_TYPE_LANG_EN;
        }
        
        $xml =  '<?xml version="1.0" encoding="UTF-8" ?>'.self::LF.
                '<rdf:RDF'.self::LF.
                '   xmlns="'.self::XMLNS_RSS.'"'.self::LF.
                '   xmlns:rdf="'.self::XMLNS_RDF.'"'.self::LF.
                '   xmlns:rdfs="'.self::XMLNS_RDFS.'"'.self::LF.
                '   xmlns:dc="'.self::XMLNS_DC.'"'.self::LF.
                '   xmlns:dcterms="'.self::XMLNS_DCTERMS.'"'.self::LF.
                '   xmlns:prism="'.self::XMLNS_PRISM.'"'.self::LF.
                '   xmlns:opensearch="'.self::XMLNS_OPENSEARCH.'"'.self::LF.
                '   xmlns:wekolog="'.BASE_URL.self::XMLNS_WEKOLOG.'"'.self::LF.
                '   xml:lang="'.$lang.'">'.self::LF.self::LF;
        return $xml;
    }
    
    /**
     * output channel
     * 
     * @param $request request parameter array
     * @param $searchResult search Result
     * @return string
     */
    private function outputChannel($request, $searchResult)
    {
        ///// request parameter string /////
        $requesturl = BASE_URL;
        if(substr($requesturl, -1, 1)!="/"){
            $requesturl .= "/";
        }
        $requesturl .= "?".$_SERVER['QUERY_STRING'];
        
        ///// repository name /////
        $errorMsg = "";
        $this->RepositoryAction->getAdminParam("prvd_Identify_repositoryName", $repositoryName, $errorMsg);
        $repositoryName = RepositoryOutputFilter::string($repositoryName);
        if(strlen($repositoryName) == 0)
        {
            $repositoryName = "WEKO";
        }
        
        ///// feed title /////
        $feed_title = $repositoryName." OpenSearch";
        if(isset($request[self::REQUEST_WEKO_ID]) && strlen($request[self::REQUEST_WEKO_ID]) > 0)
        {
            $feed_title .= " - "."WEKOID : ".$request[self::REQUEST_WEKO_ID];
        }
        if(isset($request[self::REQUEST_KEYWORD]) && strlen($request[self::REQUEST_KEYWORD]) > 0)
        {
            $feed_title .= " : ".$request[self::REQUEST_KEYWORD];
        }
        if(isset($request[self::REQUEST_INDEX_ID]) && strlen($request[self::REQUEST_INDEX_ID]) > 0)
        {
            $feed_title .= " : ".$this->getIndexPath($request[self::REQUEST_INDEX_ID], "＞");
        }
        
        ///// search date /////
        $DATE = new Date();
        $search_date = $DATE->format("%Y-%m-%dT%H:%M:%S%O");
        
        ///// output search request /////
        $xml = "";
        // request url
        $xml .= '   <channel rdf:about="'.$this->RepositoryAction->forXmlChange($requesturl).'">'.self::LF;
        
        // feed title
        $xml .= '       <title>'.$this->RepositoryAction->forXmlChange($feed_title).'</title>'.self::LF;
        
        // request url
        $xml .= '       <link>'.$this->RepositoryAction->forXmlChange($requesturl).'</link>'.self::LF;
        // search date
        $xml .= '       <dc:date>'.$this->RepositoryAction->forXmlChange($search_date).'</dc:date>'.self::LF;
        // search total
        $xml .= '       <opensearch:totalResults>'.$this->total.'</opensearch:totalResults>'.self::LF;
        // search result information
        if($this->total > 0)
        {
            // start item number
            $xml .= '       <opensearch:startIndex>'.$this->startIndex.'</opensearch:startIndex>'.self::LF;
            // output item number on this page
            $xml .= '       <opensearch:itemsPerPage>'.count($searchResult).'</opensearch:itemsPerPage>'.self::LF;
            $xml .= '       <items>'.self::LF;
            $xml .= '           <rdf:Seq>'.self::LF;
            for($ii=0;$ii<count($searchResult);$ii++)
            {
                // detail url
                $xml .= '               <rdf:li rdf:resource="'.$this->RepositoryAction->forXmlChange($searchResult[$ii]["uri"]).'" />'.self::LF;
            }
            $xml .= '           </rdf:Seq>'.self::LF;
            $xml .= '       </items>'.self::LF;
        }
        $xml .= '   </channel>'.self::LF.self::LF;
        
        return $xml;
    }
    
    private function outputItem($request, $searchResult)
    {
        $xml = "";
        $now_lang = $request[self::REQUEST_LANG];
        $display_lang = $this->getAlternativeLanguage();
        
        if(strlen($request[self::REQUEST_LOG_TERM]) > 0)
        {
            // add log data
            $this->getItemLogData($request, $searchResult);
        }
        
        ///// create XML /////
        for($ii=0;$ii<count($searchResult);$ii++)
        {
            $itemData = array();
            $itemData = $this->getOutputData($request, $searchResult[$ii]["item_id"], $searchResult[$ii]["item_no"]);
            if(strlen($request[self::REQUEST_LOG_TERM]) > 0)
            {
                // set log result
                $itemData[self::DATA_WEKO_LOG_TERM] = "";
                if(isset($searchResult[$ii][self::DATA_WEKO_LOG_TERM]))
                {
                    $itemData[self::DATA_WEKO_LOG_TERM] = $searchResult[$ii][self::DATA_WEKO_LOG_TERM];
                }
                $itemData[self::DATA_WEKO_LOG_VIEW] = "";
                if(isset($searchResult[$ii][self::DATA_WEKO_LOG_VIEW]))
                {
                    $itemData[self::DATA_WEKO_LOG_VIEW] = $searchResult[$ii][self::DATA_WEKO_LOG_VIEW];
                }
                $itemData[self::DATA_WEKO_LOG_DOWNLOAD] = "";
                if(isset($searchResult[$ii][self::DATA_WEKO_LOG_DOWNLOAD]))
                {
                    $itemData[self::DATA_WEKO_LOG_DOWNLOAD] = $searchResult[$ii][self::DATA_WEKO_LOG_DOWNLOAD];
                }
            }
            
            ///// start ouput item /////
            $xml .= '   <item rdf:about="'.$this->RepositoryAction->forXmlChange($searchResult[$ii]["uri"]).'">'.self::LF;
            
            // title
            $xml .= '       <title>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_TITLE]).'</title>'.self::LF;
            
            // alternative
            if(strlen($itemData[self::DATA_ALTERNATIVE]) > 0)
            {
                $xml .= '       <dcterms:alternative>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_ALTERNATIVE]).'</dcterms:alternative>'.self::LF;
            }
            
            // uri
            $xml .= '       <link>'.$this->RepositoryAction->forXmlChange($searchResult[$ii]["uri"]).'</link>'.self::LF;
            
            // swrc
            if(strlen($itemData[self::DATA_SWRC]) > 0)
            {
                $xml .= '       <rdfs:seeAlso rdf:resource="'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_SWRC]).'" />'.self::LF;
            }
            
            // oai-ore
            for($jj=0; $jj<count($itemData[self::DATA_OAIORE]); $jj++)
            {
                $xml .= '       <rdfs:seeAlso rdf:resource="'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_OAIORE][$jj]).'" />'.self::LF;
            }
            
            /// weko id
            if(strlen($itemData[self::DATA_WEKO_ID]) > 0)
            {
                $xml .= '       <dc:identifier>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_WEKO_ID]).'</dc:identifier>'.self::LF;
            }
            
            // mapping info
            $xml .= '       <prism:aggregationType>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_MAPPING_INFO]).'</prism:aggregationType>'.self::LF;
            
            // item type name
            $xml .= '       <dc:type>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_ITEM_TYPE_NAME]).'</dc:type>'.self::LF;
            
            // mime type
            for($jj=0; $jj<count($itemData[self::DATA_MIME_TYPE]); $jj++)
            {
                $xml .= '       <dc:format>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_MIME_TYPE][$jj]).'</dc:format>'.self::LF;
            }
            
            // attribute id(file id)
            for($jj=0; $jj<count($itemData[self::DATA_FILE_URI]); $jj++)
            {
                $xml .= '       <dc:identifier>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_FILE_URI][$jj]).'</dc:identifier>'.self::LF;
            }
            
            // creator
            for($jj=0; $jj<count($itemData[self::DATA_CREATOR]); $jj++)
            {
                if(strlen($itemData["creator_lang"][$jj]) == 0) {
                    $xml .= '       <dc:creator>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_CREATOR][$jj]).'</dc:creator>'.self::LF;
                } else if($display_lang[$now_lang] == 1 || $itemData["creator_lang"][$jj] == $now_lang) {
                    $xml .= '       <dc:creator xml:lang="'.RepositoryOutputFilter::language($itemData["creator_lang"][$jj]).'">'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_CREATOR][$jj]).'</dc:creator>'.self::LF;
                } else {
                    continue;
                }
            }
            
            // publisher
            for($jj=0; $jj<count($itemData[self::DATA_PUBLISHER]); $jj++)
            {
                if(strlen($itemData["publisher_lang"][$jj]) == 0) {
                    $xml .= '       <dc:publisher>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_PUBLISHER][$jj]).'</dc:publisher>'.self::LF;
                } else if($display_lang[$now_lang] == 1 || $itemData["publisher_lang"][$jj] == $now_lang) {
                    $xml .= '       <dc:publisher xml:lang="'.RepositoryOutputFilter::language($itemData["publisher_lang"][$jj]).'">'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_PUBLISHER][$jj]).'</dc:publisher>'.self::LF;
                } else {
                    continue;
                }
            }
            
            // index name
            for($jj=0; $jj<count($itemData[self::DATA_INDEX_PATH]); $jj++)
            {
                $xml .= '       <dc:subject>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_INDEX_PATH][$jj]).'</dc:subject>'.self::LF;
            }
            
            // jtitle
            if(strlen($itemData[self::DATA_JTITLE]) > 0)
            {
                $xml .= '       <prism:publicationName>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_JTITLE]).'</prism:publicationName>'.self::LF;
            }
            
            // issn
            if(strlen($itemData[self::DATA_ISSN]) > 0)
            {
                $xml .= '       <prism:issn>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_ISSN]).'</prism:issn>'.self::LF;
            }
            
            // volume
            if(strlen($itemData[self::DATA_VOLUME]) > 0)
            {
                $xml .= '       <prism:volume>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_VOLUME]).'</prism:volume>'.self::LF;
            }
            
            // issue
            if(strlen($itemData[self::DATA_ISSUE]) > 0)
            {
                $xml .= '       <prism:number>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_ISSUE]).'</prism:number>'.self::LF;
            }
            
            // spage
            if(strlen($itemData[self::DATA_SPAGE]) > 0)
            {
                $xml .= '       <prism:startingPage>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_SPAGE]).'</prism:startingPage>'.self::LF;
            }
            
            // epage
            if(strlen($itemData[self::DATA_EPAGE]) > 0)
            {
                $xml .= '       <prism:endingPage>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_EPAGE]).'</prism:endingPage>'.self::LF;
            }
            
            // date of issued
            if(strlen($itemData[self::DATA_DATE_OF_ISSUED]) > 0)
            {
                $dateOfIssued = $this->RepositoryAction->changeDatetimeToW3C($itemData[self::DATA_DATE_OF_ISSUED]);
                $xml .= '       <prism:publicationDate>'.$this->RepositoryAction->forXmlChange($dateOfIssued).'</prism:publicationDate>'.self::LF;   // 発行年月日
            }
            
            // description
            for($jj=0; $jj<count($itemData[self::DATA_DESCRIPTION]); $jj++)
            {
                if(strlen($itemData["description_lang"][$jj]) == 0) {
                    $xml .= '       <description>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_DESCRIPTION][$jj]).'</description>'.self::LF;    // 抄録
                } else if($display_lang[$now_lang] == 1 || $itemData["description_lang"][$jj] == $now_lang) {
                    $xml .= '       <description xml:lang="'.RepositoryOutputFilter::language($itemData["description_lang"][$jj]).'">'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_DESCRIPTION][$jj]).'</description>'.self::LF;    // 抄録
                } else {
                    continue;
                }
            }
            
            // Modify mod_date -> pub_date 2014/08/01 Y.Nakao --start--
            $pubDate = $this->RepositoryAction->changeDatetimeToW3C($itemData[self::DATA_PUB_DATE]);
            $xml .= '       <dc:date>'.$this->RepositoryAction->forXmlChange($pubDate).'</dc:date>'.self::LF;
            // Modify mod_date -> pub_date 2014/08/01 Y.Nakao --end--
            
            // log_term
            if(strlen($request[self::REQUEST_LOG_TERM]) > 0)
            {
                if(strlen($itemData[self::DATA_WEKO_LOG_TERM]) > 0)
                {
                    $xml .= '       <wekolog:terms>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_WEKO_LOG_TERM]).'</wekolog:terms>'.self::LF;   // ログ集計年月
                }
                if(strlen($itemData[self::DATA_WEKO_LOG_VIEW]) > 0)
                {
                    $xml .= '       <wekolog:view>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_WEKO_LOG_VIEW]).'</wekolog:view>'.self::LF; // 閲覧回数
                }
                if(strlen($itemData[self::DATA_WEKO_LOG_DOWNLOAD]) > 0)
                {
                    $xml .= '       <wekolog:download>'.$this->RepositoryAction->forXmlChange($itemData[self::DATA_WEKO_LOG_DOWNLOAD]).'</wekolog:download>'.self::LF; // ダウンロード回数
                }
            }
            
            // ins_date
            $insDate = $this->RepositoryAction->changeDatetimeToW3C($itemData[self::DATA_INS_DATE]);
            $xml .= '       <prism:creationDate>'.$this->RepositoryAction->forXmlChange($insDate).'</prism:creationDate>'.self::LF; // 作成日
            
            // mod_date
            $modDate = $this->RepositoryAction->changeDatetimeToW3C($itemData[self::DATA_MOD_DATE]);
            $xml .= '       <prism:modificationDate>'.$this->RepositoryAction->forXmlChange($modDate).'</prism:modificationDate>'.self::LF; // 更新日
            
            // file pewview link
            for ($jj = 0; $jj < count($itemData[self::DATA_URL]); $jj++)
            {
                $link = BASE_URL . "/index.php?action=repository_action_common_download&" . 
                        RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID . "=" . $itemData[self::DATA_URL][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID] . "&" .
                        RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO . "=" . $itemData[self::DATA_URL][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO] . "&" .
                        RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID . "=" . $itemData[self::DATA_URL][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID] . "&" .
                        RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO . "=" . $itemData[self::DATA_URL][$jj][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO] . "&" .
                        RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_PREV . "=true";
                
                $xml .= '       <prism:url>'.$this->RepositoryAction->forXmlChange($link).'</prism:url>'.self::LF;
            }
            
            $xml .= '   </item>'.self::LF.self::LF;
        }
        
        return $xml;
    }
    
    /**
     * output prefix ID
     * 
     * return string
     */
    public function outputPrefixId()
    {
        $xml = "";
        $prefixID = "";
        $errorMsg = "";
        
        $repositoryDbAccess = new RepositoryDbAccess($this->Db);
        // Bug Fix WEKO-2014-063 T.Koyasu 2014/08/07 --start--
        $DATE = new Date();
        $transStartDate = $DATE->getDate().".000";
        
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $repositoryDbAccess, $transStartDate);
        // Bug Fix WEKO-2014-063 T.Koyasu 2014/08/07 --end--
        
        $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        
        if(strlen($prefixID) > 0)
        {
            $xml = '   <dc:identifier>'.$prefixID.'</dc:identifier>'.self::LF;
        }
        else
        {
            $xml = 'prefix don\'t defined.'.self::LF;
        }
        return $xml;
    }
    
    /**
     * output footer
     *
     * @return string
     */
    private function outputFooter()
    {
        $xml = '</rdf:RDF>';
        return $xml;
    }
}
?>