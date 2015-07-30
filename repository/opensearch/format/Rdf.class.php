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

/**
 * repository search class
 * 
 */
class Repository_OpenSearch_Rdf extends Repository_Opensearch_FormatAbstract
{
    const XMLNS_RDF         = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
    const XMLNS_OPENSEARCH  = "http://a9.com/-/spec/opensearch/1.1/";
    
    const FORMAT_DUBLIN_CORE = "oai_dc";
    const FORMAT_JUNII2 = "junii2";
    const FORMAT_LOM = "oai_lom";
    
    /**
     * output metadata format class
     *   Repository_Oaipmh_DublinCore
     *   Repository_Oaipmh_JuNii2
     *   Repository_Oaipmh_LearningObjectMetadata
     *
     * @var Object
     */
    private $metadataClass = null;
    
    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        parent::__construct($session, $db);
    }
    
    public function setFormat($format)
    {
        switch ($format)
        {
            case self::FORMAT_DUBLIN_CORE:
                require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/DublinCore.class.php';
                $this->metadataClass = new Repository_Oaipmh_DublinCore($this->Session, $this->Db);
                break;
            case self::FORMAT_JUNII2:
                require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/JuNii2.class.php';
                $this->metadataClass = new Repository_Oaipmh_JuNii2($this->Session, $this->Db);
                break;
            case self::FORMAT_LOM:
                require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/LearningObjectMetadata.class.php';
                $this->metadataClass = new Repository_Oaipmh_LearningObjectMetadata($this->Session, $this->Db);
                break;
            case self::FORMAT_SPASE:
	            	require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/SPASE.class.php';
	            	$this->metadataClass = new Repository_Oaipmh_Spase($this->Session, $this->Db);
	            	break;
            default:
                break;
        }
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
        
        ///// set output data filter /////
        if(isset($request[self::REQUEST_DATA_FILTER]))
        {
            $this->metadataClass->setDataFilter($request[self::REQUEST_DATA_FILTER]);
        }
        
        ///// set data /////
        $xml = "";
        
        ///// header /////
        $xml .= $this->outputHeader();
        
        ///// header /////
        $xml .= $this->outputOpenSearchHeader($searchResult);
        
        ///// items /////
        $xml .= $this->outputItem($searchResult);
        
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
    private function outputHeader()
    {
        $xml =  '<?xml version="1.0" encoding="UTF-8" ?>'.self::LF.
                '<rdf:RDF xmlns:opensearch="'.self::XMLNS_OPENSEARCH.'" '.
                '         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
                '         xmlns:rdf="'.self::XMLNS_RDF.'">'.self::LF;
        return $xml;
    }
    
    /**
     * output rdf header for opensearch
     *
     * @param array $searchResult search result
     * @return string
     */
    private function outputOpenSearchHeader($searchResult)
    {
        $xml =  '<header>'.self::LF.
                '<opensearch:totalResults>'.$this->total.'</opensearch:totalResults>'.self::LF.
                '<opensearch:startIndex>'.$this->startIndex.'</opensearch:startIndex>'.self::LF.
                '<opensearch:itemsPerPage>'.count($searchResult).'</opensearch:itemsPerPage>'.self::LF.
                '</header>'.self::LF;
        return $xml;
    }
    
    /**
     * output items
     *
     * @param array $searchResult
     * @return string
     */
    private function outputItem($searchResult)
    {
        $xml = '';
        $xml .= '<items>'.self::LF;
        
        for($ii=0; $ii<count($searchResult); $ii++)
        {
            $itemData = array();
            $log = "";
            $ret = $this->RepositoryAction->getItemData($searchResult[$ii]["item_id"], 
                                                        $searchResult[$ii]["item_no"], 
                                                        $itemData, 
                                                        $log,
                                                        false,
                                                        true);
            if($ret)
            {
                $xml .= '<rdf:Description rdf:about="'.$this->RepositoryAction->forXmlChange($searchResult[$ii]["uri"]).'">'.self::LF;
                $xml .= $this->metadataClass->outputRecord($itemData);
                $xml .= '</rdf:Description>'.self::LF;
            }
        }
        
        $xml .= '</items>'.self::LF;
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