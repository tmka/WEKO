<?php
// --------------------------------------------------------------------
//
// $Id: HarvestingOaipmh.class.php 58676 2015-10-10 12:33:17Z tatsuya_koyasu $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/tree/Tree.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/FW/AppException.class.php';

/**
 * Repository module OAI-PMH harvesting class
 *
 * @package repository
 * @access  public
 */
class HarvestingOaipmh extends RepositoryAction
{
    // ---------------------------------------------
    // Member
    // ---------------------------------------------
    /**
     * repository ID
     *
     * @var int
     */
    private $repositoryId = 0;
    /**
     * repository's baseUrl
     *
     * @var string
     */
    private $baseUrl = "";
    /**
     * join url and request parameter
     *
     * @var string
     */
    private $join = "?";
    /**
     * metadataPrefix
     *
     * @var string metadataPrefix:oai_dc/junii2/oai_lom/lido
     */
    private $metadataPrefix = "";
    /**
     * postIndexId
     *
     * @var int
     */
    private $postIndexId = 0;
    /**
     * isAutoSoting
     *
     * @var int 0:not exec/1:execute sorting
     */
    private $isAutoSoting = 0;
    /**
     * requestUrl
     *
     * @var string
     */
    private $requestUrl = "";
    /**
     * response xml file path
     *
     * @var string
     */
    private $xmlFile = "";
    /**
     * harvesting log message
     * in "error message" or "warning message"
     *
     * @var array
     */
    private $logMsg = array();
    /**
     * harvesting status for log
     *
     * @var int
     */
    private $harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_OK;
    /**
     * get XML date (responseDate)
     *
     * @var string
     */
    private $responseDate = '';
    /**
     * itemRegister.class.php
     *
     * @var classObject
     */
    private $itemRegister = array();
    /**
     * edit/Tree.class.php
     *
     * @var classObject
     */
    private $editTree = array();
    /**
     * OAI-PMH filter class
     *
     * @var classObject
     */
    private $filter = null;

    // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
    /**
     * from date for selective harvesting
     *
     * @var int
     */
    private $from_date = null;
    /**
     * until date for selective harvesting
     *
     * @var int
     */
    private $until_date = null;
    /**
     * set parameter for selective harvesting
     *
     * @var string
     */
    private $set_param = null;
    // Add Selective Harvesting 2013/09/04 R.Matsuura --end--

    // ---------------------------------------------
    // Const
    // ---------------------------------------------
    // metadataPrefix
    const METADATAPREFIX_OAIDC = RepositoryConst::OAIPMH_METADATA_PREFIX_DC;
    const METADATAPREFIX_JUNII2 = RepositoryConst::OAIPMH_METADATA_PREFIX_JUNII2;
    const METADATAPREFIX_OAILOM = RepositoryConst::OAIPMH_METADATA_PREFIX_LOM;
    const METADATAPREFIX_LIDO = RepositoryConst::OAIPMH_METADATA_PREFIX_LIDO;
    const METADATAPREFIX_SPASE = RepositoryConst::OAIPMH_METADATA_PREFIX_SPASE;

    // tag
    const OAIPMH_TAG_IDENTIFIER = RepositoryConst::OAIPMH_TAG_IDENTIFIER;
    const OAIPMH_TAG_DATESTAMP = RepositoryConst::OAIPMH_TAG_DATESTAMP;
    const OAIPMH_TAG_SETSPEC = RepositoryConst::OAIPMH_TAG_SET_SPEC;

    // ---------------------------------------------
    // Method
    // ---------------------------------------------
    /**
     * Constructor
     *
     * @return HarvestingOaipmh
     */
    public function HarvestingOaipmh($Session, $Db){
        $this->Session = $Session;
        $this->Db = $Db;
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

    /**
     * Set member: repositoryId
     *
     * @param int $repositoryId
     */
    public function setRepositoryId($repositoryId)
    {
        $this->repositoryId = $repositoryId;
    }

    /**
     * Set member: baseUrl
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        // set join
        $this->join = "?";
        if(is_numeric(strpos($this->baseUrl, "?action=repository_oaipmh")))
        {
            $this->join = "&";
        }
    }

    /**
     * Set member: metadataPrefix
     *
     * @param string $metadataPrefix
     */
    public function setMetadataPrefix($metadataPrefix)
    {
        $this->metadataPrefix = $metadataPrefix;
        $this->setFilter();
    }

    /**
     * Set member: postIndexId
     *
     * @param int $postIndexId
     */
    public function setPostIndexId($postIndexId)
    {
        $this->postIndexId = $postIndexId;
    }

    /**
     * Set member: isAutoSoting
     *
     * @param int $isAutoSoting
     */
    public function setIsAutoSoting($isAutoSoting)
    {
        $this->isAutoSoting = $isAutoSoting;
    }

    /**
     * Set member: requestUrl
     *
     * @param string $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * Set member: xmlFile
     *
     * @param string $xmlFile
     */
    public function setXmlFile($xmlFile)
    {
        $this->xmlFile = $xmlFile;
    }

    /**
     * Set member: responseDate
     *
     * @param string $responseDate
     */
    public function setResponseDate($responseDate)
    {
        $this->responseDate = $responseDate;
    }

    /**
     * Get Identify URL
     *
     * @return string
     */
    public function getIdentifyUrl()
    {
        $url = "";
        if(strlen($this->baseUrl) > 0)
        {
            $url = $this->baseUrl.$this->join."verb=Identify";
        }
        return $url;
    }

    /**
     * Get ListSets URL
     *
     * @return string
     */
    public function getListSetsUrl()
    {
        $url = "";
        if(strlen($this->baseUrl) > 0)
        {
            $url = $this->baseUrl.$this->join."verb=ListSets";
        }
        return $url;
    }

    /**
     * Get ListRecords URL
     *
     * @return string
     */
    public function getListRecordsUrl()
    {
        $url = "";
        if(strlen($this->baseUrl) > 0 && strlen($this->metadataPrefix) > 0)
        {
            $url = $this->baseUrl.$this->join."verb=ListRecords&metadataPrefix=".$this->metadataPrefix;
            // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
            $url .= $this->getHarvestingParam();
            // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
        }
        return $url;
    }

    /**
     * Set OAI-PMH filter class
     *
     */
    public function setFilter()
    {
        if($this->metadataPrefix == self::METADATAPREFIX_OAILOM)
        {
            require_once WEBAPP_DIR.'/modules/repository/action/common/harvesting/filter/HarvestingOaipmhLom.class.php';
            $this->filter = new HarvestingOaipmhLom($this->Session, $this->Db);
        }
        else if($this->metadataPrefix == self::METADATAPREFIX_LIDO)
        {
            require_once WEBAPP_DIR.'/modules/repository/action/common/harvesting/filter/HarvestingOaipmhLido.class.php';
            $this->filter = new HarvestingOaipmhLido($this->Session, $this->Db);
        }
        else if($this->metadataPrefix == self::METADATAPREFIX_SPASE)
        {
        	require_once WEBAPP_DIR.'/modules/repository/action/common/harvesting/filter/HarvestingOaipmhSpase.class.php';
        	$this->filter = new HarvestingOaipmhSpase($this->Session, $this->Db);
        }
    }

    /**
     * Get harvesting start date
     *
     * @param string &$startDate
     * @return bool
     */
    public function getHarvestingStartDate(&$startDate)
    {
        $startDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "harvesting_start_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(strlen($result[0]["param_value"]) > 0)
        {
            $startDate = $result[0]["param_value"];
        }

        return true;
    }

    /**
     * Get harvesting end date
     *
     * @param string &$endDate
     * @return bool
     */
    public function getHarvestingEndDate(&$endDate)
    {
        $endDate = "";
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = ?;";
        $params = array();
        $params[] = "harvesting_end_date";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(strlen($result[0]["param_value"]) > 0)
        {
            $endDate = $result[0]["param_value"];
        }

        return true;
    }

    /**
     * Parse ListSets
     *
     * @param string $nextUrl
     * @return bool
     */
    public function parseListSets(&$nextUrl)
    {
        // set edit tree
        // for update and insert
        $this->editTree = new Repository_Action_Edit_Tree();

        // set default access role and TransStartDate
        $this->editTree->Session = $this->Session;
        $this->editTree->Db = $this->Db;
        $this->editTree->setDefaultAccessControlList();

        $this->setLangResource();

        $setXml = "";
        $resTokenXml = "";
        $setOpenFlag = false;
        $resTokenOpenFlag = false;
        $nextUrl = "";

        // read file
        $fp = fopen($this->xmlFile, "r");
        while(!feof($fp)){
            // Read line
            $line = fgets($fp);
            $line = str_replace("\r\n", "", $line);
            $line = str_replace("\n", "", $line);
            $line = trim($line);

            if($setOpenFlag)
            {
                $setXml .= $line;

                // "set" tag close
                if(preg_match("/^<\/set>$/", $line) > 0)
                {
                    // set registed index time
                    $this->TransStartDate = $this->getNowDate();
                    $this->editTree->TransStartDate = $this->TransStartDate;

                    // init
                    $setOpenFlag = false;
                    // init log status
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_OK;
                    $this->makeIndexFromListSets($setXml);
                    $setXml = '';
                }

            }
            else if($resTokenOpenFlag)
            {
                $resTokenXml .= $line;

                // "resumptionToken" tag close
                if(preg_match("/^<\/resumptionToken>$/", $line) > 0)
                {
                    $resTokenOpenFlag = false;
                    $resumptionToken = "";

                    $resTokenXml = str_replace("\r\n", "\n", $resTokenXml);
                    $resTokenXml = str_replace("\r", "\n", $resTokenXml);
                    $resTokenXml = str_replace("\n", "", $resTokenXml);

                    $resumptionToken = preg_replace("/\<resumptionToken ?.*\>(.*)\<\/resumptionToken\>/", "$1", $resTokenXml);

                    if(strlen($resumptionToken) > 0)
                    {
                        // Create next URL (Get ListSets continue)
                        $nextUrl = $this->baseUrl.$this->join."verb=ListSets&resumptionToken=".$resumptionToken;
                    }
                }
            }
            else if($resResponseDateFlag)
            {
                $resResponseDateXml .= $line;

                // "responseDate" tag close
                if(preg_match("/^<\/responseDate>$/", $line) > 0)
                {
                    $resResponseDateFlag = false;
                    $this->responseDate = "";

                    $resResponseDateXml = str_replace("\r\n", "\n", $resResponseDateXml);
                    $resResponseDateXml = str_replace("\r", "\n", $resResponseDateXml);
                    $resResponseDateXml = str_replace("\n", "", $resResponseDateXml);

                    $resResponseDateXml = preg_replace("/^<responseDate ?.*>(.*)<\/responseDate>$/", "$1", $resResponseDateXml);

                    // private number <- responseDate
                    $this->responseDate = $resResponseDateXml;
                }
            }
            else
            {
                // "set" tag open
                if(preg_match("/^<set +.*>|<set>$/", $line) > 0)
                {
                    $setOpenFlag = true;
                    $setXml = $line;
                    $this->logMsg = array();
                }

                // "resumtionToken" tag open
                else if(preg_match("/^<resumptionToken +.*>|<resumptionToken>$/", $line) > 0)
                {
                    $resTokenOpenFlag = true;
                    $resTokenXml = $line;
                }

                // "responseDate" tag open
                else if(preg_match("/^<responseDate +.*>|<responseDate>$/", $line) > 0){
                    $resResponseDateFlag = true;
                    $resResponseDateXml = $line;
                }
            }
        }
        fclose($fp);

        // Create next URL (Get ListRecords)
        if(strlen($nextUrl) < 1)
        {
            // delete old index
            // select old indexes
            $this->getHarvestingStartDate($startDate);
            $query = " SELECT * ".
                     " FROM ".DATABASE_PREFIX."repository_index ".
                     " WHERE repository_id = ? ".
                     " AND LENGTH(set_spec) > ? ".
                     " AND is_delete != ? ".
                     " AND mod_date < ? ";
            $params = array();
            $params[] = $this->repositoryId;
            $params[] = 0;
            $params[] = 1;
            $params[] = $startDate;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                // output log
                array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_DELETE, '', '', '', '', '', '', '');
                return false;
            } else if(count($result) > 0){
                // search child indexes of each delete index
                $deleteIndexIds = array();
                for($ii=0;$ii<count($result);$ii++){
                    $getChildIndexResult = $this->editTree->getAllChildIndexID($result[$ii]['index_id'], $deleteIndexIds);
                    if($getChildIndexResult === false){
                        // output log
                        array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_DELETE, '', '', $result[$ii]['index_id'], '', '', '', $this->editTree->TransStartDate);
                        return false;
                    }
                    array_push($deleteIndexIds, $result[$ii]['index_id']);
                }
                // delete indexes
                $result = $this->editTree->deleteIndexItem($deleteIndexIds);
                if($result === false){
                    // output harvesting log
                    array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_DELETE, '', '', '', '', '', '', $this->editTree->TransStartDate);
                    return false;
                }
            }

            $nextUrl = $this->baseUrl.$this->join."verb=ListRecords&metadataPrefix=".$this->metadataPrefix;

            // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
            $nextUrl .= $this->getHarvestingParam();
            // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
        }

        return true;
    }

    /**
     * Parse Records
     *
     * @param string $nextUrl
     * @return bool
     */
    public function parseListRecords(&$nextUrl)
    {
        $headerXml = "";
        $metadataXml = "";
        $resTokenXml = "";
        $resResponseDateXml = "";
        $recordOpenFlag = false;
        $headerOpenFlag = false;
        $metadatarOpenFlag = false;
        $resTokenOpenFlag = false;
        $resResponseDateFlag = false;
        $nextUrl = "";
        $metadata = array();

        $this->itemRegister = new ItemRegister($this->Session, $this->Db);

        // read file
        $fp = fopen($this->xmlFile, "r");
        while(!feof($fp))
        {
            // Read line
            $line = fgets($fp);
            if($this->metadataPrefix == self::METADATAPREFIX_LIDO)
            {
                $line = str_replace("\r\n", RepositoryConst::XML_LF, $line);
                $line = str_replace("\n", RepositoryConst::XML_LF, $line);
                $line = str_replace(">".RepositoryConst::XML_LF, ">", $line);
                $line = str_replace(RepositoryConst::XML_LF."<", "<", $line);
            }
            else
            {
                $line = str_replace("\r\n", "", $line);
                $line = str_replace("\n", "", $line);
            }
            $line = trim($line);

            if($recordOpenFlag)
            {
                if($headerOpenFlag)
                {
                    $headerXml .= $line;

                    // "header" tag close
                    if(preg_match("/^<\/header>$/", $line) > 0)
                    {
                        $headerOpenFlag = false;

                        // Get header data
                        if(!$this->getHeaderDataFromListRecords($headerXml, $metadata))
                        {
                            // Error
                            $recordOpenFlag = false;
                        }
                        $headerXml ='';
                    }
                }
                else if($metadatarOpenFlag)
                {
                    $metadataXml .= $line;

                    // "metadata" tag close
                    if(preg_match("/^<\/metadata>$/", $line) > 0)
                    {
                        $metadatarOpenFlag = false;

                        // Get metadata
                        if(!$this->getMetadataFromListRecords($metadataXml, $metadata))
                        {
                            // Error
                            $recordOpenFlag = false;
                        }
                        $metadataXml = '';
                    }
                }
                else
                {
                    // "header" tag open
                    if(preg_match("/^<header +.*>|<header>$/", $line) > 0)
                    {
                        $headerOpenFlag = true;
                        $headerXml = $line;
                    }

                    // "metadata" tag open
                    else if(preg_match("/^<metadata +.*>|<metadata>$/", $line) > 0)
                    {
                        $metadatarOpenFlag = true;
                        $metadataXml = $line;
                    }

                    // "record" tag close
                    else if(preg_match("/^<\/record>$/", $line) > 0)
                    {
                        $recordOpenFlag = false;
                        $headerStatus = "";
                        $headerIdentifier = "";
                        $headerDatastump = "";
                        $setSpecArray = array();

                        // Call check metadata
                        if($this->checkMetadata($metadata))
                        {
                            $this->makeItemDataFromListRecords($metadata);
                        }
                    }
                }
            }
            else if($resTokenOpenFlag)
            {
                $resTokenXml .= $line;

                // "resumptionToken" tag close
                if(preg_match("/^<\/resumptionToken>$/", $line) > 0)
                {
                    $resTokenOpenFlag = false;
                    $resumptionToken = "";

                    $resTokenXml = str_replace("\r\n", "\n", $resTokenXml);
                    $resTokenXml = str_replace("\r", "\n", $resTokenXml);
                    $resTokenXml = str_replace("\n", "", $resTokenXml);
                    $resTokenXml = str_replace(RepositoryConst::XML_LF, "", $resTokenXml);

                    $resumptionToken = preg_replace("/\<resumptionToken ?.*\>(.*)\<\/resumptionToken\>/", "$1", $resTokenXml);

                    if(strlen($resumptionToken) > 0)
                    {
                        // Create next URL (Get ListRecords continue)
                        $nextUrl = $this->baseUrl.$this->join."verb=ListRecords&resumptionToken=".$resumptionToken;
                    }
                }
            }
            else if($resResponseDateFlag)
            {
                $resResponseDateXml .= $line;

                // "responseDate" tag close
                if(preg_match("/^<\/responseDate>$/", $line) > 0)
                {
                    $resResponseDateFlag = false;
                    $this->responseDate = "";

                    $resResponseDateXml = str_replace("\r\n", "\n", $resResponseDateXml);
                    $resResponseDateXml = str_replace("\r", "\n", $resResponseDateXml);
                    $resResponseDateXml = str_replace("\n", "", $resResponseDateXml);
                    $resResponseDateXml = str_replace(RepositoryConst::XML_LF, "", $resResponseDateXml);

                    $resResponseDateXml = preg_replace("/^<responseDate ?.*>(.*)<\/responseDate>$/", "$1", $resResponseDateXml);

                    // private number <- responseDate
                    $this->responseDate = $resResponseDateXml;
                }
            }
            else
            {
                // "record" tag open
                if(preg_match("/^<record +.*>|<record>$/", $line) > 0)
                {
                    $recordOpenFlag = true;
                    $headerXml = "";
                    $metadataXml = "";
                    $metadata = array();
                    $this->TransStartDate = $this->getNowDate();
                    if(($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE) && $this->filter != null)
                    {
                        $this->filter->setTransStartDate($this->TransStartDate);
                    }
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_OK;
                    $this->logMsg = array();
                }

                // "resumtionToken" tag open
                else if(preg_match("/^<resumptionToken +.*>|<resumptionToken>$/", $line) > 0)
                {
                    $resTokenOpenFlag = true;
                    $resTokenXml = $line;
                }

                // "responseDate" tag open
                else if(preg_match("/^<responseDate +.*>|<responseDate>$/", $line) > 0){
                    $resResponseDateFlag = true;
                    $resResponseDateXml = $line;
                }
            }
        }
        fclose($fp);

        if(strlen($nextUrl) == 0)
        {
            // Recount index contents
            $this->editTree = new Repository_Action_Edit_Tree();
            $this->editTree->Session = $this->Session;
            $this->editTree->Db = $this->Db;
            $topParentIndexId = $this->getTopParentIndexId($this->postIndexId);
            $this->editTree->recountContents($topParentIndexId);
            $this->editTree->recountPrivateContents($topParentIndexId);		// Add private_contents count K.Matsuo 2013/05/07
        }

        return true;
    }

    /**
     * Regist index by ListSets and regist items by ListRecords
     *
     * @param string $nextUrl
     * @param bool $indexError
     * @return bool
     */
    public function registIndexAndItems(&$nextUrl, $indexError)
    {
        // ListSets
        if(preg_match("/^.*verb=ListSets.*$/", $this->requestUrl) == 1)
        {
            if(!$indexError)
            {
                // Parse XML and regist index
                $ret = $this->parseListSets($nextUrl);
            }
            else
            {
                // Index is deleted
                $nextUrl = "";
                array_push($this->logMsg, "repository_harvesting_error_miss_index");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, "", "", "", "", "", "", $this->requestUrl, "");
                return false;
            }
        }

        // ListRecords
        else if(preg_match("/^.*verb=ListRecords.*$/", $this->requestUrl) == 1)
        {
            if(!$indexError)
            {
                // Parse XML and regist items
                $ret = $this->parseListRecords($nextUrl);
            }
            else
            {
                // Index is deleted
                $nextUrl = "";
                array_push($this->logMsg, "repository_harvesting_error_miss_index");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
                return false;
            }
        }

        return true;
    }

    /**
     * Create progress text
     *
     * @return string
     */
    public function createProgressText()
    {
        $progressText = "";
        $progressText .= $this->repositoryId."\t";
        $progressText .= $this->baseUrl."\t";
        // Add Selective Harvesting 2013/09/09 R.Matsuura --start--
        $progressText .= $this->from_date."\t";
        $progressText .= $this->until_date."\t";
        $progressText .= $this->set_param."\t";
        // Add Selective Harvesting 2013/09/09 R.Matsuura --end--
        $progressText .= $this->metadataPrefix."\t";
        $progressText .= $this->postIndexId."\t";
        $progressText .= $this->isAutoSoting."\t";
        if($this->isAutoSoting == "1")
        {
            $progressText .= $this->getListSetsUrl()."\n";
        }
        else
        {
            $progressText .= $this->getListRecordsUrl()."\n";
        }

        return $progressText;
    }

    /**
     * set harvesting log.
     *
     * @param string $logMsg log message
     */
    public function setHarvestingLogMsg($logMsg)
    {
        if(strlen($logMsg) > 0)
        {
            array_push($this->logMsg, $logMsg);
        }
    }

    /**
     * set harvesting log status
     *
     * @param int $status
     */
    public function setHarvestingLogStatus($status)
    {
        $this->harvestingLogStatus = $status;
    }

    /**
     * make index from ListSets
     *
     * @param string $listSetsXml "<set><setSpec>xxx</setSpec><setName>xxx</setName></set>"
     */
    private function makeIndexFromListSets($listSetsXml)
    {
        $setSpec = "";
        $setName = "";

        // parse xml
        try
        {
            $xml_parser = xml_parser_create();
            $ret = xml_parse_into_struct($xml_parser, $listSetsXml, $vals);
            if($ret == 0)
            {
                throw new Exception();
            }
            xml_parser_free($xml_parser);
        }
        catch(Exception $ex)
        {
            array_push($this->logMsg, "repository_harvesting_error_listsets_xml");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, '', '', '', '', '', '', '', '');
            return false;
        }

        // Get elements
        foreach($vals as $val)
        {
            switch ($val["tag"])
            {
                case RepositoryConst::HARVESTING_COL_SETSPEC:
                    $setSpec = $this->forXmlChangeDecode($val["value"]);
                    break;
                case RepositoryConst::HARVESTING_COL_SETNAME:
                    $setName = $this->forXmlChangeDecode($val["value"]);
                    break;
                default:
                    break;
            }
        }

        // check value
        if(strlen($setSpec) == 0)
        {
            array_push($this->logMsg, "repository_harvesting_error_get_setspec");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, '', $setSpec, '', '', '', '', '', '');
            return false;
        }

        if(strlen($setName) == 0){
            $setName = $setSpec;
        }

        // insert index tree or update tree
        $ret = false;
        // index id that updates
        $indexId = $this->postIndexId;

        // Add harvesting 2012/03/13 T.Koyasu -start-
        // split setSpec
        // array[]=string
        $setSpecArray = explode(":", $setSpec);

        // parent index id
        $parentIndexId = $this->postIndexId;

        $groupData = array();
        $this->editTree->getAccessGroupData($this->editTree->getDefaultAccessRoleRoom(), $this->editTree->getDefaultExclusiveAclGroups(), $groupData);
        $authData = array();
        $this->editTree->getAccessAuthData($this->editTree->getDefaultAccessRoleIds(), $this->editTree->getDefaultExclusiveRoleIds(), $authData);

        $updateValue = 0;

        for($ii=0;$ii<count($setSpecArray);$ii++){
            if($this->indexExists($setSpecArray[$ii], $indexId))
            {
                // setSpecのインデックスが実在するなら、$indexIdはそのインデックスのIDになっている
                // get index data(array)
                $indexData = $this->editTree->getIndexEditData($indexId);

                // change update data
                if($indexData['parent_index_id'] != $parentIndexId){
                    // インデックスの親インデックスIDとsetspecの値が違う場合、ソートする
                    $this->editTree->changeParentIndex($indexId, $parentIndexId, 'last');
                    $indexData = $this->editTree->getIndexEditData($indexId);
                }

                if($ii == (count($setSpecArray) - 1)){
                    if(strcmp($indexData['index_name'], $indexData['index_name_english']) == 0 || strlen($indexData['index_name']) == 0){
                        $indexData['index_name'] = $setName;
                    }
                    $indexData['index_name_english'] = $setName;
                    $indexData['public_state'] = "true";
                    $indexData['pub_date'] = substr($this->editTree->TransStartDate, 0, 10). ' 00:00:00.000';

                    // update index
                    $result = $this->editTree->updateIndex($indexData);
                    if($result === false){
                        array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE, $setSpec, '', $indexData['index_id'], '', '', '', $this->editTree->TransStartDate);
                        return false;
                    }
                }

                $parentIndexId = $indexId;
                $updateValue = RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE;
            }
            else
            {
                $newIndexData = array();

                // create insert index data
                // create new index id
                $newIndexId = $this->editTree->getNewIndexId();

                // set showOrder(this process for the index is not exists from D&D)
                $showOrder = 1;
                $query = " SELECT show_order ".
                         " FROM ". DATABASE_PREFIX. "repository_index ".
                         " WHERE parent_index_id = ? ".
                         " ORDER BY show_order DESC";
                $params = array();
                $params[] = $parentIndexId;
                $result = $this->Db->execute($query, $params);
                if($result === false){
                    array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_INSERT, $setSpec, '', $newIndexId, '', '', '', '');
                    return false;
                } else if (count($result) === 0){
                    $showOrder = 1;
                } else {
                    $showOrder = 1 + $result[0]['show_order'];
                }

                $newIndexData['index_id'] = $newIndexId;
                if($ii == (count($setSpecArray) - 1)){
                    $newIndexData["index_name"] = $setName;
                    $newIndexData["index_name_english"] = $setName;
                } else {
                    $newIndexData["index_name"] = "New Node";
                    $newIndexData["index_name_english"] = "New Node For English";
                }
                $newIndexData["parent_index_id"] = $parentIndexId;
                $newIndexData["show_order"] = $showOrder;
                $newIndexData["public_state"] = "true";
                $newIndexData["pub_year"] = substr($this->editTree->TransStartDate, 0, 4);
                $newIndexData["pub_month"] = substr($this->editTree->TransStartDate, 5, 2);
                $newIndexData["pub_day"] = substr($this->editTree->TransStartDate, 7, 2);
                $newIndexData["pub_date"] = substr($this->editTree->TransStartDate, 0, 10). ' 00:00:00.000';
                $newIndexData["access_group_id"] = $groupData["access_group_id"];
                $newIndexData["access_role_id"] = $authData["access_role_id"];
                $newIndexData["comment"] = "";
                $newIndexData["display_more"] = "";
                $newIndexData["rss_display"] = "";
                $newIndexData["access_role_room"] = $this->editTree->getDefaultAccessRoleRoom();
                $newIndexData["display_type"] = "";
                $newIndexData["select_index_list_display"] = "";
                $newIndexData["select_index_list_name"] = "";
                $newIndexData["select_index_list_name_english"] = "";
                $newIndexData["exclusive_acl_role_id"] = $this->editTree->getDefaultExclusiveRoleIds();
                $newIndexData["exclusive_acl_room_auth"] = $this->editTree->getDefaultExclusiveAclRoleRoom();
                $newIndexData["exclusive_acl_group_id"] = $this->editTree->getDefaultExclusiveAclGroups();
                $newIndexData["repository_id"] = $this->repositoryId;
                $newIndexData["set_spec"] = $setSpecArray[$ii];
                $newIndexData["harvest_public_state"] = 0;

                // insert index
                $result = $this->editTree->insertIndex($newIndexData);
                if($result === false){
                    // warning
                    array_push($this->logMsg, "repository_harvesting_error_insert_subindex");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, RepositoryConst::HARVESTING_LOG_UPDATE_INSERT, $setSpec, '', $newIndexId, '', '', '', $this->editTree->TransStartDate);
                }

                $parentIndexId = $newIndexId;
                $updateValue = RepositoryConst::HARVESTING_LOG_UPDATE_INSERT;
            }
        }
        // Add harvesting 2012/03/13 T.Koyasu -end-

        if($this->harvestingLogStatus == RepositoryConst::HARVESTING_LOG_STATUS_OK)
        {
            array_push($this->logMsg, "repository_harvesting_success_setspec");
        }
        $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTSETS, $updateValue, $setSpec, '', $parentIndexId, '', '', '', $this->editTree->TransStartDate);

        return true;
    }

    /**
     * Get metadata array from ListRecords(header)
     *
     * @param string $headerXml
     * @param array $metadata
     * @return bool
     */
    private function getHeaderDataFromListRecords($headerXml, &$metadata)
    {
        // parse header xml
        try
        {
            $xml_parser = xml_parser_create();
            $ret = xml_parse_into_struct($xml_parser, $headerXml, $vals);
            xml_parser_free($xml_parser);
            if($ret == 0)
            {
                array_push($this->logMsg, "repository_harvesting_error_get_xml");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
                return false;
            }
        }
        catch(Exception $ex)
        {
            array_push($this->logMsg, "repository_harvesting_error_get_xml");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
            return false;
        }

        // Get elements
        foreach($vals as $val)
        {
            if($val["type"] == "complete" || ($val["tag"] == RepositoryConst::HARVESTING_COL_HEADER && $val["type"] == "open"))
            {
                $tagName = $val["tag"];
                if($tagName == RepositoryConst::HARVESTING_COL_IDENTIFIER)
                {
                    $tagName = RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER;
                }

                if(!array_key_exists($tagName, $metadata))
                {
                    $metadata[$tagName] = array();
                }

                $tagData = array("value" => "", "attributes" => array());
                $tagData["value"] = $this->forXmlChangeDecode($val["value"]);
                $tagData["attributes"] = $val["attributes"];

                array_push($metadata[$tagName], $tagData);
            }
        }

        $identifier = "";
        $datestamp = "";
        $setSpecStr = "";
        if(array_key_exists(RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER, $metadata)
            || strlen($metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"]) > 0)
        {
            $identifier = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];
        }
        if(array_key_exists(RepositoryConst::HARVESTING_COL_DATESTAMP, $metadata)
            || strlen($metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"]) > 0)
        {
            $datestamp = $metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"];
        }
        if(array_key_exists(RepositoryConst::HARVESTING_COL_SETSPEC, $metadata))
        {
            $setSpecStr = $this->getSetSpecStr($metadata[RepositoryConst::HARVESTING_COL_SETSPEC]);
        }

        // Error check
        if(strlen($identifier) == 0)
        {
            array_push($this->logMsg, "repository_harvesting_error_get_identifier");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(  RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "",
                                        $setSpecStr, "", $identifier, "", $this->requestUrl, $datestamp);
            return false;
        }
        if(strlen($datestamp) == 0)
        {
            array_push($this->logMsg, "repository_harvesting_error_get_datestamp");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(  RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "",
                                        $setSpecStr, "", $identifier, "", $this->requestUrl, $datestamp);
            return false;
        }

        // Warning check
        if($this->isAutoSoting == 1 && strlen($setSpecStr) == 0)
        {
            if($metadata[RepositoryConst::HARVESTING_COL_HEADER][0]["attributes"][RepositoryConst::HARVESTING_COL_STATUS] != "deleted")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_setspec");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        return true;
    }

    /**
     * Get metadata array from ListRecords(record)
     *
     * @param string $metadataXml
     * @param array $metadata metadata[TAGNAME][NUM]["value"]
     *                                              ["attribute"][KEY]
     * @return bool
     */
    private function getMetadataFromListRecords($metadataXml, &$metadata)
    {
        if($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE)
        {
            if(!$this->filter->setMetadataFromListRecords($metadataXml, $this->repositoryId, $metadata))
            {
                array_push($this->logMsg, "repository_harvesting_error_get_xml");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
                return false;
            }
        }
        else
        {
            // parse metadata xml
            try
            {
                $xml_parser = xml_parser_create();
                $ret = xml_parse_into_struct($xml_parser, $metadataXml, $vals);
                xml_parser_free($xml_parser);
                if($ret == 0)
                {
                    array_push($this->logMsg, "repository_harvesting_error_get_xml");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
                    return false;
                }
            }
            catch(Exception $ex)
            {
                array_push($this->logMsg, "repository_harvesting_error_get_xml");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "", "", "", "", "", $this->requestUrl, "");
                return false;
            }

            // Get elements
            foreach($vals as $val)
            {
                if($val["type"] == "complete")
                {
                    if(!array_key_exists($val["tag"], $metadata))
                    {
                        $metadata[$val["tag"]] = array();
                    }

                    $tagData = array("value" => "", "attributes" => array());
                    $tagData["value"] = $this->forXmlChangeDecode($val["value"]);
                    $tagData["attributes"] = $val["attributes"];

                    array_push($metadata[$val["tag"]], $tagData);
                }
            }
        }

        return true;
    }

    /**
     * check index exists
     *
     * @param string $setSpec
     * @param int $indexId
     * @return true = index exists / false = index no exists
     */
    private function indexExists($setSpec, &$indexId)
    {
        // get index data
        $query = "SELECT index_id ".
                " FROM ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX.
                " WHERE is_delete = ? ".
                " AND repository_id = ? ";
        $params = array();
        $params[] = 0;
        $params[] = $this->repositoryId;

        if(isset($setSpec) && strlen($setSpec) > 0)
        {
            $query .= " AND set_spec = ? ";
            $params[] = $setSpec;
        }
        $query .= " ORDER BY index_id ";
        $result = $this->Db->execute($query, $params);

        if($result === false || count($result) == 0)
        {
            // not exists
            return false;
        } else if(count($result) > 1){
            // warning: 2 or more index_id
            array_push($this->logMsg, "repository_harvesting_warning_over_index");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
        }
        $indexId = $result[0]['index_id'];

        return true;
    }

    /**
     * Check metadata
     *
     * @param array $metadata
     * @return bool
     */
    private function checkMetadata($metadata)
    {
        // If status is 'deleted', no check and return 'true'.
        if($metadata[RepositoryConst::HARVESTING_COL_HEADER][0]["attributes"][RepositoryConst::HARVESTING_COL_STATUS] == "deleted")
        {
            return true;
        }

        $identifier = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];
        $datestamp = $metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"];
        $setSpecStr = $this->getSetSpecStr($metadata[RepositoryConst::HARVESTING_COL_SETSPEC]);

        if($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE)
        {
            $logStatus = $this->harvestingLogStatus;
            $logMsg = $this->logMsg;
            $return = $this->filter->checkMetadata($metadata, $logStatus, $logMsg);
            $this->harvestingLogStatus = $logStatus;
            $this->logMsg = $logMsg;
            if(!$return)
            {
                $this->entryHarvestingLog(  RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "",
                                            $setSpecStr, "", $identifier, "", $this->requestUrl, $datestamp);
                return false;
            }
        }
        else
        {
            // If not exist required tags, make key as value is empty
            if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
            {
                if( !array_key_exists(strtoupper(RepositoryConst::DUBLIN_CORE_TITLE), $metadata) &&
                    !array_key_exists(strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_TITLE), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::DUBLIN_CORE_TITLE)] = array();
                }

                if( !array_key_exists(strtoupper(RepositoryConst::DUBLIN_CORE_LANGUAGE), $metadata) &&
                    !array_key_exists(strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_LANGUAGE), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::DUBLIN_CORE_LANGUAGE)] = array();
                }
            }
            else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
            {
                // title
                if(!array_key_exists(strtoupper(RepositoryConst::JUNII2_TITLE), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::JUNII2_TITLE)] = array();
                }

                // language
                if(!array_key_exists(strtoupper(RepositoryConst::JUNII2_LANGUAGE), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::JUNII2_LANGUAGE)] = array();
                }

                // NIItype
                if(!array_key_exists(strtoupper(RepositoryConst::JUNII2_NIITYPE), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::JUNII2_NIITYPE)] = array();
                }

                // URI
                if(!array_key_exists(strtoupper(RepositoryConst::JUNII2_URI), $metadata))
                {
                    $metadata[strtoupper(RepositoryConst::JUNII2_URI)] = array();
                }
            }

            foreach ($metadata as $tagName => $tagValues)
            {
                if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
                {
                    $result = $this->checkOaidcMetadata($tagName, $tagValues, $identifier, $datestamp, $setSpecStr);
                    if($result === false)
                    {
                        return false;
                    }
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
                {
                    $result = $this->checkJuNii2Metadata($tagName, $tagValues, $identifier, $datestamp, $setSpecStr);
                    if($result === false)
                    {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Upsert item data from ListRecords
     *
     * @param array $metadata
     * @return bool
     */
    private function makeItemDataFromListRecords($metadata)
    {
        $ret = false;

        // Set date for ItemRegister
        $this->itemRegister->setEditStartDate($this->TransStartDate);
        $this->itemRegister->TransStartDate = $this->TransStartDate;

        $updateStatus = RepositoryConst::HARVESTING_LOG_UPDATE_NO_UPDATE;
        $setSpecStr = $this->getSetSpecStr($metadata[RepositoryConst::HARVESTING_COL_SETSPEC]);
        $indexIdStr = $this->getSetSpecIndexIdStr($metadata[RepositoryConst::HARVESTING_COL_SETSPEC]);
        $identifier = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];
        $datestamp = $metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"];
        $itemId = "";
        $itemNo = "";
        $lastModDate = "";
        $isDelete = "";
        $errMsg = "";
        $whatsNewFlag = false;

        // check item exists
        if($this->isItemExists($metadata, $itemId, $itemNo, $lastModDate, $isDelete))
        {
            // Exists
            // Set update status
            if($lastModDate != $datestamp)
            {
                if($metadata[RepositoryConst::HARVESTING_COL_HEADER][0]["attributes"][RepositoryConst::HARVESTING_COL_STATUS] != "deleted")
                {
                    // to Update
                    $updateStatus = RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE;

                    // If delete -> update
                    if($isDelete == "1")
                    {
                        // Set flag for add to what's new module
                        $whatsNewFlag = true;
                    }
                }
                else if($isDelete != "1")
                {
                    // to Delete
                    $updateStatus = RepositoryConst::HARVESTING_LOG_UPDATE_DELETE;
                }
            }
            else if($isDelete == "1")
            {
                // Restoration
                if($metadata[RepositoryConst::HARVESTING_COL_HEADER][0]["attributes"][RepositoryConst::HARVESTING_COL_STATUS] != "deleted")
                {
                    // to Update
                    $updateStatus = RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE;

                    // Set flag for add to what's new module
                    $whatsNewFlag = true;
                }
            }

            if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE)
            {
                // Update
                // Convert metadata for ItemRegister
                $irBasic = null;
                $irMetadata = null;
                if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
                {
                    $this->convertMetadataForDublinCore($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
                {
                    $this->convertMetadataForJunii2($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE)
                {
                    $this->filter->setItemIdForIrData($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }

                try
                {
                    // Update item data
                    $result = $this->itemRegister->updateItem($irBasic, $errMsg);
                    if(!$result)
                    {
                        array_push($this->logMsg, "repository_harvesting_error_insert_item");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(
                                RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                        return false;
                    }
                    $result = $this->updateIsDeleteForItemTable($itemId, $itemNo, 0);
                    if(!$result)
                    {
                        array_push($this->logMsg, "repository_harvesting_error_insert_item");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(
                                RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                        return false;
                    }

                    // Update metadata
                    $result = $this->deleteItemAttrData($itemId, $itemNo, $this->Session->getParameter("_user_id"), $errMsg);
                    if($result === false)
                    {
                        array_push($this->logMsg, "repository_harvesting_error_insert_item");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(
                                RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                        return false;
                    }

                    foreach($irMetadataArray as $irMetadata)
                    {
                        $result = $this->itemRegister->entryMetadata($irMetadata, $errMsg);
                        if(!$result)
                        {
                            array_push($this->logMsg, "repository_harvesting_error_insert_item");
                            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                            $this->entryHarvestingLog(
                                    RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                    "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                            return false;
                        }
                    }

                    // BugFix when before and after update, assignment doi is failed T.Koyasu 2015/03/09 --start--
                    // must check self_doi when after update item metadatas
                    try{
                        $this->itemRegister->updateSelfDoi($irBasic);
                    } catch(AppException $ex){
                        array_push($this->logMsg, $ex->getMessage());
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                    }

                    // BugFix when before and after update, assignment doi is failed T.Koyasu 2015/03/09 --end--
                }
                catch (Exception $ex)
                {
                    array_push($this->logMsg, "repository_harvesting_error_insert_item");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(
                            RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                            "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                    return false;
                }
            }
            else if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_DELETE)
            {
                try
                {
                    // Delete item
                    $result = $this->deleteItemData($itemId, $itemNo, $this->Session->getParameter("_user_id"), $errMsg);
                    if($result === false)
                    {
                        array_push($this->logMsg, "repository_harvesting_error_insert_item");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(
                                RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                        return false;
                    }
                }
                catch (Exception $ex)
                {
                    array_push($this->logMsg, "repository_harvesting_error_insert_item");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(
                            RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                            "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                    return false;
                }
            }
        }
        else
        {
            // No Exists
            if($metadata[RepositoryConst::HARVESTING_COL_HEADER][0]["attributes"][RepositoryConst::HARVESTING_COL_STATUS] != "deleted")
            {
                // Insert item
                $updateStatus = RepositoryConst::HARVESTING_LOG_UPDATE_INSERT;
                $itemId = intval($this->Db->nextSeq("repository_item"));
                $itemNo = 1;

                // Convert metadata for ItemRegister
                $irBasic = null;
                $irMetadata = null;
                if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
                {
                    $this->convertMetadataForDublinCore($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
                {
                    $this->convertMetadataForJunii2($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE)
                {
                    $this->filter->setItemIdForIrData($itemId, $itemNo, $metadata, $irBasic, $irMetadataArray);
                }

                try
                {
                    // Insert item data
                    $result = $this->itemRegister->entryItem($irBasic, $errMsg, true);
                    if(!$result)
                    {
                        array_push($this->logMsg, "repository_harvesting_error_insert_item");
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                        $this->entryHarvestingLog(
                                RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                        return false;
                    }

                    // Insert metadata
                    foreach($irMetadataArray as $irMetadata)
                    {
                        $result = $this->itemRegister->entryMetadata($irMetadata, $errMsg);
                        if(!$result)
                        {
                            array_push($this->logMsg, "repository_harvesting_error_insert_item");
                            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                            $this->entryHarvestingLog(
                                    RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                                    "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                            return false;
                        }
                    }

                    // Set flag for add to what's new module
                    $whatsNewFlag = true;
                }
                catch (Exception $ex)
                {
                    array_push($this->logMsg, "repository_harvesting_error_insert_item");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                    $this->entryHarvestingLog(
                            RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                            "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                    return false;
                }
            }
        }

        if($updateStatus != RepositoryConst::HARVESTING_LOG_UPDATE_NO_UPDATE)
        {
            // Entry position index
            if(!$this->makePositionIndex($itemId, $itemNo, $metadata[RepositoryConst::HARVESTING_COL_SETSPEC], $updateStatus))
            {
                $this->entryHarvestingLog(
                        RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                        "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                return false;
            }
        }

        if(strlen($itemId) > 0 && strlen($itemNo) > 0 && $updateStatus != RepositoryConst::HARVESTING_LOG_UPDATE_DELETE)
        {
            // Set item's status to public
            if(!$this->setItemStatusToPublic($itemId, $itemNo, $whatsNewFlag))
            {
                $this->entryHarvestingLog(
                        RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                        "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);
                return false;
            }
        }

        // success
        if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_INSERT)
        {
            array_push($this->logMsg, "repository_harvesting_success_regist_item");
        }
        else if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_UPDATE)
        {
            array_push($this->logMsg, "repository_harvesting_success_update_item");
        }
        else if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_DELETE)
        {
            array_push($this->logMsg, "repository_harvesting_success_delete_item");
        }
        else if($updateStatus == RepositoryConst::HARVESTING_LOG_UPDATE_NO_UPDATE)
        {
            array_push($this->logMsg, "repository_harvesting_success_no_update");
        }

        // Library JaLC DOI Regist Check

        if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
        {
            if($metadata[strtoupper(RepositoryConst::JUNII2_SELFDOI)][0]["attributes"]["RA"] === RepositoryConst::JUNII2_SELFDOI_RA_JALC)
            {
                $checkdoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);

                $checkRegist = $checkdoi->checkDoiGrant($itemId, $itemNo, 2, 0);
                if($checkRegist)
                {
                    $handleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
                    try{
                        $handleManager->registLibraryJalcdoiSuffix($itemId, $itemNo, $metadata[strtoupper(RepositoryConst::JUNII2_SELFDOI)][0]["value"]);
                    } catch(AppException $ex){
                        $error = $ex->getMessage();
                        array_push($this->logMsg, $error);
                        $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                        $this->debugLog($error. "::itemId=". $itemId. "::selfDoi=". $metadata[strtoupper(RepositoryConst::JUNII2_SELFDOI)][0]["value"], __FILE__, __CLASS__, __LINE__);
                    }
                }
            }
        }

        $this->entryHarvestingLog(RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, $updateStatus,
                    "", $setSpecStr, $indexIdStr, $identifier, $itemId, $this->requestUrl, $datestamp);

        return true;
    }

    /**
     * Check item exists
     *
     * @param array $metadata
     * @param int $itemId
     * @param int $itemNo
     * @param string &$datestamp
     * @param string &$isDelete
     * @return bool true: Exists / false: No exists
     */
    private function isItemExists($metadata, &$itemId, &$itemNo, &$datestamp, &$isDelete)
    {
        // Init
        $itemId = "";
        $itemNo = "";
        $datestamp = "";
        $isDelete = "";

        if($this->metadataPrefix == self::METADATAPREFIX_OAILOM || $this->metadataPrefix == self::METADATAPREFIX_LIDO || $this->metadataPrefix == self::METADATAPREFIX_SPASE)
        {
            $result = $this->filter->isItemExists($metadata, $this->repositoryId, $itemId, $itemNo, $datestamp, $isDelete);
            return $result;
        }
        else
        {
            $query = "SELECT DISTINCT item_id, item_no ".
                     "FROM ".DATABASE_PREFIX."repository_item_attr ".
                     "WHERE attribute_id = ? ".
                     "AND attribute_no = 1 ".
                     "AND attribute_value = ? ".
                     "AND item_id IN (".
                     "  SELECT DISTINCT item_id ".
                     "  FROM ".DATABASE_PREFIX."repository_item_attr ".
                     "  WHERE attribute_id = ? ".
                     "  AND attribute_no = 1 ".
                     "  AND attribute_value = ? ";
            $params = array();
            if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
            {
                $query .= "  AND item_type_id = 20001);";   //item_type_id

                $params[] = 16; //attribute_id / Identifier
                $params[] = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];  //attribute_value / Itentifier
                $params[] = 15; //attribute_id / repositoryId
                $params[] = $this->repositoryId;  //attribute_value / repositoryId
            }
            else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
            {
                $strItemTypeId = "";
                for($ii=20002; $ii<=20015; $ii++)
                {
                    if(strlen($strItemTypeId)>0)
                    {
                        $strItemTypeId .= ",";
                    }
                    $strItemTypeId .= $ii;
                }

                $query .= "  AND item_type_id IN (".$strItemTypeId."));";   //item_type_id (20002~20015)

                $params[] = 53; //attribute_id / Identifier
                $params[] = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];  //attribute_value / Itentifier
                $params[] = 52; //attribute_id / repositoryId
                $params[] = $this->repositoryId;  //attribute_value / repositoryId
            }

            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                return false;
            }

            if(count($result) == 0)
            {
                // Not exists
                return false;
            }
            else
            {
                $itemId = $result[0]["item_id"];
                $itemNo = $result[0]["item_no"];

                // Exists
                $query = "SELECT attribute_value ".
                         "FROM ".DATABASE_PREFIX."repository_item_attr ".
                         "WHERE item_id = ? ".
                         "AND item_no = ? ".
                         "AND attribute_id = ? ".
                         "AND attribute_no = 1 ;";
                $params = array();
                $params[] = $itemId;
                $params[] = $itemNo;
                if($this->metadataPrefix == self::METADATAPREFIX_OAIDC)
                {
                    $params[] = 17; //attribute_id / datestamp
                }
                else if($this->metadataPrefix == self::METADATAPREFIX_JUNII2)
                {
                    $params[] = 54; //attribute_id / datestamp
                }
                $result = $this->Db->execute($query, $params);
                if(count($result) > 0)
                {
                    $datestamp = $result[0]["attribute_value"];
                }

                // Get repository_item table's is_delete
                $query = "SELECT is_delete ".
                         "FROM ".DATABASE_PREFIX."repository_item ".
                         "WHERE item_id = ? ".
                         "AND item_no = ? ;";
                $params = array();
                $params[] = $itemId;
                $params[] = $itemNo;
                $result = $this->Db->execute($query, $params);
                if(count($result) > 0)
                {
                    $isDelete = intval($result[0]["is_delete"]);
                }

                return true;
            }
        }
    }

    /**
     * Processing which acquires index_id of setSpec
     * and updates "item_id" of "repository_position_index"TBL,
     * and "item_no"
     *
     * @param int $itemId
     * @param int $itemNo
     * @param array $setSpec
     * @param int $updateStatus
     * @return bool $ret true・・・makeOK false・・・makeNG
     */
    private function makePositionIndex($itemId, $itemNo, $setSpec, $updateStatus)
    {
        // init
        $indexInfo = array("item_id" => $itemId, "item_no" => $itemNo);
        $indexList = array();

        // check itemId,itemNo
        if($itemId < 1 || $itemNo < 1){
            //Error
            array_push($this->logMsg,"repository_harvesting_error_miss_index");
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            return false;
        }

        // is auto sort?? ON
        if($this->isAutoSoting == 1){
            // SetSpec
            $isWarning = false;
            // setSpec null check
            if(isset($setSpec) && count($setSpec) > 0){
                for($ii=0; $ii<count($setSpec); $ii++){
                    if(isset($setSpec[$ii]) && strlen($setSpec[$ii]["value"])>0 ){
                        // init
                        $indexId = "";

                        // explode by ":"
                        $setSpecEx = explode(":", $setSpec[$ii]["value"]);
                        $setSpecValue = end($setSpecEx);

                        // exist index?
                        if($this->indexExists($setSpecValue, $indexId)){
                            // $indexId >1 ??
                            if($indexId > 1){
                                $indexArray = array("index_id" => $indexId);
                                $sameIndexExist = false;
                                for($jj = 0; $jj < count($indexList); $jj++){
                                    if($indexArray["index_id"] == $indexList[$jj]["index_id"]){
                                        $sameIndexExist = true;
                                        break;
                                    }
                                }
                                if(!$sameIndexExist)
                                {
                                    array_push($indexList, $indexArray);
                                }
                            }else{
                                $isWarning = true;
                                continue; //next setSpec
                            }
                        }else{
                            $isWarning = true;
                        }
                    }
                }
            }

            if($isWarning == true || count($indexList) == 0){
                // Warning
                array_push($this->logMsg, "repository_harvesting_warning_miss_index");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;

                if(count($indexList) == 0)
                {
                    $indexArray = array("index_id" => $this->postIndexId);
                    $sameIndexExist = false;
                    for($jj = 0; $jj < count($indexList); $jj++){
                        if($indexArray["index_id"] == $indexList[$jj]["index_id"]){
                            $sameIndexExist = true;
                            break;
                        }
                    }
                    if(!$sameIndexExist)
                    {
                        array_push($indexList, $indexArray);
                    }
                }
            }
        }
        // is auto sort OFF
        else{
            $indexArray = array("index_id" => $this->postIndexId);
            $sameIndexExist = false;
            for($jj = 0; $jj < count($indexList); $jj++){
                if($indexArray["index_id"] == $indexList[$jj]["index_id"]){
                    $sameIndexExist = true;
                    break;
                }
            }
            if(!$sameIndexExist)
            {
                array_push($indexList, $indexArray);
            }
        }

        // Get registered index
        $this->getItemIndexData($itemId, $itemNo, $ResultList, $errorMsg);
        for($ii=0; $ii<count($ResultList["position_index"]); $ii++)
        {
            $indexArray = array("index_id" => $ResultList["position_index"][$ii]["index_id"]);
            $sameIndexExist = false;
            for($jj = 0; $jj < count($indexList); $jj++){
                if($indexArray["index_id"] == $indexList[$jj]["index_id"]){
                    $sameIndexExist = true;
                    break;
                }
            }
            if(!$sameIndexExist)
            {
                // Mod no insert position index under post_index 2014/03/15 T.Koyasu --start--
                // get all parent index_id
                $parentIndexIdArray = array();
                $this->getParentIndex($indexArray["index_id"], $parentIndexIdArray);

                // search postIndexId(harvest root index_id) in $parentIndexIdArray
                $addFlg = true;
                for($cnt = 0; $cnt < count($parentIndexIdArray); $cnt++)
                {
                    if($parentIndexIdArray[$cnt]['index_id'] == $this->postIndexId)
                    {
                        $addFlg = false;
                        break;
                    }
                }

                // exists: no add
                // no exist: add
                if($addFlg)
                {
                    array_push($indexList, $indexArray);
                }
                // Mod no insert position index under post_index 2014/03/15 T.Koyasu --end--
            }
        }

        //Update Or Insert
        // Mod if status is update or insert, position index is redefined 2014/03/13 T.Koyasu -start-
        if($updateStatus != RepositoryConst::HARVESTING_LOG_UPDATE_DELETE)
        {
            $ret = $this->itemRegister->entryPositionIndex($indexInfo,$indexList,$errorMsg);

            //Update Or Insert NG!
            if($ret === false){
                array_push($this->logMsg,"repository_harvesting_error_insert_item");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                return false;
            }
        }
        // Mod if status is update or insert, position index is redefined 2014/03/13 T.Koyasu -end-

        return true;
    }

    /**
     * harvesting log
     *
     * @param int $oparationId
     * @param int $status
     * @param int $update
     * @param string $listSets
     * @param string $setSpec
     * @param string $indexId
     * @param string $identifier
     * @param int $itemId
     * @param string $uri
     * @param string $lastModDate
     */
    public function entryHarvestingLog(   $oparationId, $update=0, $listSets='', $setSpec='',
                                            $indexId='', $identifier='', $itemId=0, $uri='', $lastModDate='')
    {
        $query = "INSERT INTO ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_HARVESTING_LOG.
                " ( ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_REPOSITORY_ID.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_OPERATION_ID.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_METADATA_PREFIX .", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_LIST_SETS.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_SET_SPEC.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_INDEX_ID.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_IDENTIFIER.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_ITEM_ID.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_URI.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_STATUS.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_UPDATE.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_ERROR_MSG.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_RESPONSE_DATE.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_LAST_MOD_DATE.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_INS_USER_ID.", ".
                    RepositoryConst::DBCOL_REPOSITORY_HARVESTING_LOG_INS_DATE. " ".
                " ) ".
                " VALUES ".
                " (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        $params = array();
        // repository_id
        $params[] = $this->repositoryId;
        // oparation_id
        $params[] = $oparationId;
        // metadata_prefix
        $params[] = $this->metadataPrefix;
        // list_sets
        $params[] = $listSets;
        // set_spec
        $params[] = $setSpec;
        // index_id
        $params[] = $indexId;
        // identifier
        $params[] = $identifier;
        // item_id
        $params[] = $itemId;
        // uri
        $params[] = $uri;
        // status
        $params[] = $this->harvestingLogStatus;
        // update
        $params[] = $update;
        // error_msg
        $params[] = implode(",", $this->logMsg);
        // response_date
        $params[] = $this->responseDate;
        // last_mod_date
        $params[] = $lastModDate;
        // ins_user_id
        $params[] = $this->Session->getParameter("_user_id");
        // ins_date
        $params[] = $this->TransStartDate;

        $result = $this->Db->execute($query, $params);
        if($result=== false)
        {
            return false;
        }
        return true;
    }

    /**
     * Convert metadata for DublinCore
     *
     * @param int $itemId
     * @param int $itemNo
     * @param array $metadata already checked
     * @param array &$irBasic
     * @param array &$irMetadata
     * @return bool
     */
    private function convertMetadataForDublinCore($itemId, $itemNo, $metadata, &$irBasic, &$irMetadataArray)
    {
        $itemTypeId = 20001;

        // Divide date
        $tmpDate = explode(" ", $this->TransStartDate);
        $tmpDate = explode("-", $tmpDate[0]);

        $irBasic = array(   "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId,
                            "title" => "", "title_english" => "", "language" => "",
                            "pub_year" => intval($tmpDate[0]), "pub_month" => intval($tmpDate[1]), "pub_day" => intval($tmpDate[2]),
                            "serch_key" => "", "serch_key_english" => "");
        $irMetadataArray = array();

        $titleArray = array();
        $languageArray = array();
        $creatorArray = array();
        $descriptionArray = array();
        $publisherArray = array();
        $contributorArray = array();
        $dateArray = array();
        $typeArray = array();
        $formatArray = array();
        $identifierUrlArray = array();
        $identifierArray = array();
        $sourceArray = array();
        $relationArray = array();
        $coverageArray = array();
        $rightsArray = array();

        // For tags
        foreach ($metadata as $tagNeme => $tagArray)
        {
            $upperName = strtoupper($tagNeme);

            // For tag data
            foreach ($tagArray as $tagData)
            {
                if(strlen($tagData["value"]) > 0)
                {
                    switch($upperName)
                    {
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_TITLE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_TITLE):
                            // For attributes
                            $addFlag = false;
                            if(array_key_exists("LANG", $tagData["attributes"]) && strlen($tagData["attributes"]["LANG"]) > 0)
                            {
                                $tmpVal = $this->checkLanguage($tagData["attributes"]["LANG"]);
                                if($tmpVal == "ja")
                                {
                                    if(strlen($irBasic["title"]) == 0)
                                    {
                                        $irBasic["title"] = $tagData["value"];
                                        $addFlag = true;
                                    }
                                }
                                else if($tmpVal == "en")
                                {
                                    if(strlen($irBasic["title_english"]) == 0)
                                    {
                                        $irBasic["title_english"] = $tagData["value"];
                                        $addFlag = true;
                                    }
                                }
                            }

                            if(!$addFlag)
                            {
                                array_push($titleArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_LANGUAGE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_LANGUAGE):
                            $language = $this->checkLanguage($tagData["value"]);
                            if(strlen($language) > 0 && strlen($irBasic["language"]) == 0)
                            {
                                $irBasic["language"] = $language;
                            }
                            else
                            {
                                array_push($languageArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_SUBJECT):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_SUBJECT):
                            if(strlen($irBasic["serch_key"]) > 0)
                            {
                                $irBasic["serch_key"] .= "|";
                            }
                            $irBasic["serch_key"] .= $tagData["value"];
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_CREATOR):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_CREATOR):
                            array_push($creatorArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_DESCRIPTION):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_DESCRIPTION):
                            array_push($descriptionArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_PUBLISHER):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PUBLISHER):
                            array_push($publisherArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_CONTRIBUTOR):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_CONTRIBUTOR):
                            array_push($contributorArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_DATE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_DATE):
                            array_push($dateArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_TYPE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_TYPE):
                            array_push($typeArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_FORMAT):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_FORMAT):
                            array_push($formatArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_IDENTIFIER):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_IDENTIFIER):
                            if(preg_match("/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/", $tagData["value"]))
                            {
                                // Is URL
                                array_push($identifierUrlArray, $tagData["value"]);
                            }
                            else
                            {
                                // Not URL
                                array_push($identifierArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_SOURCE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_SOURCE):
                            array_push($sourceArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_RELATION):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_RELATION):
                            array_push($relationArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_COVERAGE):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_COVERAGE):
                            array_push($coverageArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_RIGHTS):
                        case strtoupper(RepositoryConst::DUBLIN_CORE_RIGHTS):
                            array_push($rightsArray, $tagData["value"]);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        // Create metadata array
        // title
        if(strlen($irBasic["title"]) == 0 && strlen($irBasic["title_english"]) == 0)
        {
            $irBasic["title"] = $titleArray[0];
            array_splice($titleArray, 0, 1);
        }
        $this->makeMetadataArray($titleArray, $itemId, $itemNo, $itemTypeId, 1, "text", $irMetadataArray);

        // language
        if(strlen($irBasic["language"]) == 0)
        {
            $irBasic["language"] = "ja";
        }

        // creator
        $this->makeNameMetadataArrayForOaiDc($creatorArray, $itemId, $itemNo, $itemTypeId, 2, "", $irMetadataArray);

        // description
        $this->makeMetadataArray($descriptionArray, $itemId, $itemNo, $itemTypeId, 3, "textarea", $irMetadataArray);

        // publisher
        $this->makeNameMetadataArrayForOaiDc($publisherArray, $itemId, $itemNo, $itemTypeId, 4, "", $irMetadataArray);

        // contributor
        $this->makeNameMetadataArrayForOaiDc($contributorArray, $itemId, $itemNo, $itemTypeId, 5, "", $irMetadataArray);

        // date
        $this->makeMetadataArray($dateArray, $itemId, $itemNo, $itemTypeId, 6, "date", $irMetadataArray);

        // type
        $this->makeMetadataArray($typeArray, $itemId, $itemNo, $itemTypeId, 7, "textarea", $irMetadataArray);

        // format
        $this->makeMetadataArray($formatArray, $itemId, $itemNo, $itemTypeId, 8, "text", $irMetadataArray);

        // identifier(URL)
        $this->makeMetadataArray($identifierUrlArray, $itemId, $itemNo, $itemTypeId, 9, "link", $irMetadataArray);

        // identifier
        $this->makeMetadataArray($identifierArray, $itemId, $itemNo, $itemTypeId, 10, "text", $irMetadataArray);

        // source
        $this->makeMetadataArray($sourceArray, $itemId, $itemNo, $itemTypeId, 11, "text", $irMetadataArray);

        // relation
        $this->makeMetadataArray($relationArray, $itemId, $itemNo, $itemTypeId, 12, "text", $irMetadataArray);

        // coverage
        $this->makeMetadataArray($coverageArray, $itemId, $itemNo, $itemTypeId, 13, "text", $irMetadataArray);

        // rights
        $this->makeMetadataArray($rightsArray, $itemId, $itemNo, $itemTypeId, 14, "text", $irMetadataArray);

        // repository_id
        $this->makeMetadataArray(array($this->repositoryId), $itemId, $itemNo, $itemTypeId, 15, "text", $irMetadataArray);

        // contents_id
        $this->makeMetadataArray(   array($metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"]),
                                    $itemId, $itemNo, $itemTypeId, 16, "text", $irMetadataArray);

        // contents mod_date
        $this->makeMetadataArray(   array($metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"]),
                                    $itemId, $itemNo, $itemTypeId, 17, "text", $irMetadataArray);

        // Set additional metadata
        $this->setAdditionalMetadata($itemId, $itemNo, $itemTypeId, $irMetadataArray);

        return true;
    }

    /**
     * Convert metadata for JuNii2
     *
     * @param int $itemId
     * @param int $itemNo
     * @param array $metadata already checked
     * @param array &$irBasic
     * @param array &$irMetadataArray
     * @return bool
     */
    private function convertMetadataForJunii2($itemId, $itemNo, $metadata, &$irBasic, &$irMetadataArray)
    {
        // Get item_type_id
        $itemTypeId = 20015;
        switch($metadata["NIITYPE"][0]["value"])
        {
            case RepositoryConst::NIITYPE_JOURNAL_ARTICLE:
                $itemTypeId = 20002;
                break;
            case RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION:
                $itemTypeId = 20003;
                break;
            case RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER:
                $itemTypeId = 20004;
                break;
            case RepositoryConst::NIITYPE_CONFERENCE_PAPER:
                $itemTypeId = 20005;
                break;
            case RepositoryConst::NIITYPE_PRESENTATION:
                $itemTypeId = 20006;
                break;
            case RepositoryConst::NIITYPE_BOOK:
                $itemTypeId = 20007;
                break;
            case RepositoryConst::NIITYPE_TECHNICAL_REPORT:
                $itemTypeId = 20008;
                break;
            case RepositoryConst::NIITYPE_RESEARCH_PAPER:
                $itemTypeId = 20009;
                break;
            case RepositoryConst::NIITYPE_ARTICLE:
                $itemTypeId = 20010;
                break;
            case RepositoryConst::NIITYPE_PREPRINT:
                $itemTypeId = 20011;
                break;
            case RepositoryConst::NIITYPE_LEARNING_MATERIAL:
                $itemTypeId = 20012;
                break;
            case RepositoryConst::NIITYPE_DATA_OR_DATASET:
                $itemTypeId = 20013;
                break;
            case RepositoryConst::NIITYPE_SOFTWARE:
                $itemTypeId = 20014;
                break;
            case RepositoryConst::NIITYPE_OTHERS:
                $itemTypeId = 20015;
                break;
            default:
                break;
        }

        // Divide date
        $tmpDate = explode(" ", $this->TransStartDate);
        $tmpDate = explode("-", $tmpDate[0]);

        $irBasic = array(   "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId,
                            "title" => "", "title_english" => "", "language" => "",
                            "pub_year" => intval($tmpDate[0]), "pub_month" => intval($tmpDate[1]), "pub_day" => intval($tmpDate[2]),
                            "serch_key" => "", "serch_key_english" => "");
        $irMetadataArray = array();

        $titleArray = array();
        $languageArray = array();
        $alternativeArray = array();
        $creatorJapaneseArray = array();
        $creatorJapaneseIdArray = array();
        $creatorJapaneseLangArray = array();
        $creatorArray = array();
        $creatorIdArray = array();
        $creatorLangArray = array();
        $niiSubjectArray = array();
        $ndcArray = array();
        $ndlcArray = array();
        $bshArray = array();
        $ndlshArray = array();
        $meshArray = array();
        $ddcArray = array();
        $lccArray = array();
        $udcArray = array();
        $lcshArray = array();
        $descriptionArray = array();
        $publisherJapaneseArray = array();
        $publisherJapaneseIdArray = array();
        $publisherJapaneseLangArray = array();
        $publisherArray = array();
        $publisherIdArray = array();
        $publisherLangArray = array();
        $contributorJapaneseArray = array();
        $contributorJapaneseIdArray = array();
        $contributorJapaneseLangArray = array();
        $contributorArray = array();
        $contributorIdArray = array();
        $contributorLangArray = array();
        $dateArray = array();
        $typeArray = array();
        $formatArray = array();
        $identifierArray = array();
        $uriArray = array();
        $fulltextArray = array();
        $issnArray = array();
        $ncidArray = array();
        $jtitleStr = "";
        $volumeStr = "";
        $issueStr = "";
        $spageStr = "";
        $epageStr = "";
        $deteofissuedStr = "";
        $sourceArray = array();
        $relationArray = array();
        $pmidArray = array();
        $doiArray = array();
        $isVersionOfArray = array();
        $hasVersionArray = array();
        $isReplacedByArray = array();
        $replacesArray = array();
        $isRequiredByArray = array();
        $requiresArray = array();
        $isPartOfArray = array();
        $hasPartArray = array();
        $isReferencedByArray = array();
        $referencesArray = array();
        $isFormatOfArray = array();
        $hasFormatArray = array();
        $coverageArray = array();
        $spatialArray = array();
        $niiSpatialArray = array();
        $temporalArray = array();
        $rightsArray = array();
        $niiTemporalArray = array();
        $textversionArray = array();
        $isbnArray = array();
        $naidArray = array();
        $ichushiArray = array();
        $grantidArray = array();
        $dateofgrantedArray = array();
        $degreenameArray = array();
        $grantorArray = array();

        // For tags
        foreach ($metadata as $tagNeme => $tagArray)
        {
            $upperName = strtoupper($tagNeme);

            // For tag data
            foreach ($tagArray as $tagData)
            {
                if(strlen($tagData["value"]) > 0)
                {
                    switch($upperName)
                    {
                        case strtoupper(RepositoryConst::JUNII2_TITLE):
                            $addFlag = false;
                            if(array_key_exists("LANG", $tagData["attributes"]) && strlen($tagData["attributes"]["LANG"]) > 0)
                            {
                                $tmpVal = $this->checkLanguage($tagData["attributes"]["LANG"]);
                                if($tmpVal == "ja")
                                {
                                    if(strlen($irBasic["title"]) == 0)
                                    {
                                        $irBasic["title"] = $tagData["value"];
                                        $addFlag = true;
                                    }
                                }
                                else if($tmpVal == "en")
                                {
                                    if(strlen($irBasic["title_english"]) == 0)
                                    {
                                        $irBasic["title_english"] = $tagData["value"];
                                        $addFlag = true;
                                    }
                                }
                            }

                            if(!$addFlag)
                            {
                                array_push($titleArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_LANGUAGE):
                            $language = RepositoryOutputFilterJuNii2::langISOForWEKO($tagData["value"]);
                            if(strlen($language) > 0 && strlen($irBasic["language"]) == 0)
                            {
                                $irBasic["language"] = $language;
                            }
                            else if(strlen($language) > 0)
                            {
                                array_push($languageArray, $language);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_SUBJECT):
                            if(strlen($irBasic["serch_key"]) > 0)
                            {
                                $irBasic["serch_key"] .= "|";
                            }
                            $irBasic["serch_key"] .= $tagData["value"];
                            break;
                        case strtoupper(RepositoryConst::JUNII2_ALTERNATIVE):
                            array_push($alternativeArray, $tagData["value"]);
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --start--
                        case strtoupper(RepositoryConst::JUNII2_CREATOR):
                            $this->putNameMetadataToArrays($tagData, $creatorArray, $creatorIdArray, $creatorLangArray, $creatorJapaneseArray, $creatorJapaneseIdArray, $creatorJapaneseLangArray);
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --end--
                        case strtoupper(RepositoryConst::JUNII2_NII_SUBJECT):
                            array_push($niiSubjectArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NDC):
                            array_push($ndcArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NDLC):
                            array_push($ndlcArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_BSH):
                            array_push($bshArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NDLSH):
                            array_push($ndlshArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_MESH):
                            array_push($meshArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DDC):
                            array_push($ddcArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_LCC):
                            array_push($lccArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_UDC):
                            array_push($udcArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_LCSH):
                            array_push($lcshArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DESCRIPTION):
                            array_push($descriptionArray, $tagData["value"]);
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --start--
                        case strtoupper(RepositoryConst::JUNII2_PUBLISHER):
                            $this->putNameMetadataToArrays($tagData, $publisherArray, $publisherIdArray, $publisherLangArray, $publisherJapaneseArray, $publisherJapaneseIdArray, $publisherJapaneseLangArray);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_CONTRIBUTOR):
                            $this->putNameMetadataToArrays($tagData, $contributorArray, $contributorIdArray, $contributorLangArray, $contributorJapaneseArray, $contributorJapaneseIdArray, $contributorJapaneseLangArray);
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --end--
                        case strtoupper(RepositoryConst::JUNII2_DATE):
                            array_push($dateArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_TYPE):
                            array_push($typeArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_FORMAT):
                            array_push($formatArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IDENTIFIER):
                            array_push($identifierArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_URI):
                            array_push($uriArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_FULL_TEXT_URL):
                            array_push($fulltextArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_ISSN):
                            array_push($issnArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NCID):
                            array_push($ncidArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_JTITLE):
                            if(strlen($jtitleStr) == 0)
                            {
                                $jtitleStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_VOLUME):
                            if(strlen($volumeStr) == 0)
                            {
                                $volumeStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_ISSUE):
                            if(strlen($issueStr) == 0)
                            {
                                $issueStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_SPAGE):
                            if(strlen($spageStr) == 0)
                            {
                                $spageStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_EPAGE):
                            if(strlen($epageStr) == 0)
                            {
                                $epageStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DATE_OF_ISSUED):
                            if(strlen($deteofissuedStr) == 0)
                            {
                                $deteofissuedStr = $tagData["value"];
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_SOURCE):
                            array_push($sourceArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_RELATION):
                            array_push($relationArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_PMID):
                            if(count($pmidArray) == 0 && strlen($tagData["value"]) > 0)
                            {
                                array_push($pmidArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DOI):
                            if(count($doiArray) == 0 && strlen($tagData["value"]) > 0)
                            {
                                array_push($doiArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_VERSION_OF):
                            array_push($isVersionOfArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_HAS_VERSION):
                            array_push($hasVersionArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_REPLACED_BY):
                            array_push($isReplacedByArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_REPLACES):
                            array_push($replacesArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_REQUIRESD_BY):
                            array_push($isRequiredByArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_REQUIRES):
                            array_push($requiresArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_PART_OF):
                            array_push($isPartOfArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_HAS_PART):
                            array_push($hasPartArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_REFERENCED_BY):
                            array_push($isReferencedByArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_REFERENCES):
                            array_push($referencesArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_IS_FORMAT_OF):
                            array_push($isFormatOfArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_HAS_FORMAT):
                            array_push($hasFormatArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_COVERAGE):
                            array_push($coverageArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_SPATIAL):
                            array_push($spatialArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NII_SPATIAL):
                            array_push($niiSpatialArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_TEMPORAL):
                            array_push($temporalArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NII_TEMPORAL):
                            array_push($niiTemporalArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_RIGHTS):
                            array_push($rightsArray, $tagData["value"]);
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --start--
                        case strtoupper(RepositoryConst::JUNII2_TEXTVERSION):
                            if($tagData["value"] == "author" || $tagData["value"] == "publisher" || $tagData["value"] == "none" || $tagData["value"] == "ETD")
                            {
                                array_push($textversionArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_ISBN):
                            array_push($isbnArray, $tagData["value"]);
                            break;
                        case strtoupper(RepositoryConst::JUNII2_NAID):
                            $checkStr = "";
                            if(count($naidArray) == 0)
                            {
                                $checkStr = RepositoryOutputFilterJuNii2::naid($tagData["value"]);
                                if($checkStr != "")
                                {
                                    array_push($naidArray, $checkStr);
                                }
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_ICHUSHI):
                            $checkStr = "";
                            if(count($ichushiArray) == 0)
                            {
                                $checkStr = RepositoryOutputFilterJuNii2::ichushi($tagData["value"]);
                                if($checkStr != "")
                                {
                                    array_push($ichushiArray, $checkStr);
                                }
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_GRANTID):
                            $checkStr = "";
                            if(count($grantidArray) == 0)
                            {
                                $checkStr = RepositoryOutputFilterJuNii2::grantid($tagData["value"]);
                                if($checkStr != "")
                                {
                                    array_push($grantidArray, $checkStr);
                                }
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DATEOFGRANTED):
                            $checkStr = "";
                            if(count($dateofgrantedArray) == 0)
                            {
                                $checkStr = RepositoryOutputFilter::date($tagData["value"]);
                                if($checkStr != "")
                                {
                                    array_push($dateofgrantedArray, $checkStr);
                                }
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_DEGREENAME):
                            if(count($degreenameArray) == 0)
                            {
                                array_push($degreenameArray, $tagData["value"]);
                            }
                            break;
                        case strtoupper(RepositoryConst::JUNII2_GRANTOR):
                            if(count($grantorArray) == 0)
                            {
                                array_push($grantorArray, $tagData["value"]);
                            }
                            break;
                        // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --end--
                        default:
                            break;
                    }
                }
            }
        }

        // Create metadata array
        // title
        if(strlen($irBasic["title"]) == 0 && strlen($irBasic["title_english"]) == 0)
        {
            $irBasic["title"] = $titleArray[0];
            array_splice($titleArray, 0, 1);
        }

        // alternative
        $this->makeMetadataArray($alternativeArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("alternative", $itemTypeId), "text", $irMetadataArray);

        // creator(japanese)
        $this->makeNameMetadataArrayForJuNii2($creatorJapaneseArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("creator", $itemTypeId, "japanese"), $creatorJapaneseLangArray, $creatorJapaneseIdArray, $irMetadataArray);

        // creator
        $this->makeNameMetadataArrayForJuNii2($creatorArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("creator", $itemTypeId), $creatorLangArray, $creatorIdArray, $irMetadataArray);

        // NIIsubject
        $this->makeMetadataArray($niiSubjectArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NIIsubject", $itemTypeId), "text", $irMetadataArray);

        // NDC
        $this->makeMetadataArray($ndcArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NDC", $itemTypeId), "text", $irMetadataArray);

        // NDLC
        $this->makeMetadataArray($ndlcArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NDLC", $itemTypeId), "text", $irMetadataArray);

        // BSH
        $this->makeMetadataArray($bshArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("BSH", $itemTypeId), "text", $irMetadataArray);

        // NDLSH
        $this->makeMetadataArray($ndlshArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NDLSH", $itemTypeId), "text", $irMetadataArray);

        // MeSH
        $this->makeMetadataArray($meshArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("MeSH", $itemTypeId), "text", $irMetadataArray);

        // DDC
        $this->makeMetadataArray($ddcArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("DDC", $itemTypeId), "text", $irMetadataArray);

        // LCC
        $this->makeMetadataArray($lccArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("LCC", $itemTypeId), "text", $irMetadataArray);

        // UDC
        $this->makeMetadataArray($udcArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("UDC", $itemTypeId), "text", $irMetadataArray);

        // LCSH
        $this->makeMetadataArray($lcshArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("LCSH", $itemTypeId), "text", $irMetadataArray);

        // description
        $this->makeMetadataArray($descriptionArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("description", $itemTypeId), "textarea", $irMetadataArray);

        // publisher(japanese)
        $this->makeNameMetadataArrayForJuNii2($publisherJapaneseArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("publisher", $itemTypeId, "japanese"), $publisherJapaneseLangArray, $publisherJapaneseIdArray, $irMetadataArray);

        // publisher
        $this->makeNameMetadataArrayForJuNii2($publisherArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("publisher", $itemTypeId), $publisherLangArray, $publisherIdArray, $irMetadataArray);

        // contributor(japanese)
        $this->makeNameMetadataArrayForJuNii2($contributorJapaneseArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("contributor", $itemTypeId, "japanese"), $contributorJapaneseLangArray, $contributorJapaneseIdArray, $irMetadataArray);

        // contributor
        $this->makeNameMetadataArrayForJuNii2($contributorArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("contributor", $itemTypeId), $contributorLangArray, $contributorIdArray, $irMetadataArray);

        // date
        $this->makeMetadataArray($dateArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("date", $itemTypeId), "date", $irMetadataArray);

        // type
        $this->makeMetadataArray($typeArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("type", $itemTypeId), "textarea", $irMetadataArray);

        // format
        $this->makeMetadataArray($formatArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("format", $itemTypeId), "text", $irMetadataArray);

        // identifier
        $this->makeMetadataArray($identifierArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("identifier", $itemTypeId), "text", $irMetadataArray);

        // URI
        $this->makeMetadataArray($uriArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("URI", $itemTypeId), "link", $irMetadataArray);

        // fulltextURL
        $this->makeMetadataArray($fulltextArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("fulltextURL", $itemTypeId), "link", $irMetadataArray);

        // issn
        $this->makeMetadataArray($issnArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("issn", $itemTypeId), "text", $irMetadataArray);

        // NCID
        $this->makeMetadataArray($ncidArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NCID", $itemTypeId), "text", $irMetadataArray);

        // biblio_info(jtitle, volume, issue, spage, epage, dateofissued)
        if( strlen($jtitleStr) > 0 || strlen($volumeStr) > 0 || strlen($issueStr) > 0
            || strlen($spageStr) > 0 || strlen($epageStr) > 0 || strlen($deteofissuedStr) > 0)
        {
            $this->makeBiblioInfoMetadataArray( $jtitleStr, $volumeStr, $issueStr, $spageStr, $epageStr,
                                                $deteofissuedStr,$itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("jtitle,volume,issue,spage,epage,dateofissued", $itemTypeId), $irMetadataArray);
        }

        // source
        $this->makeMetadataArray($sourceArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("source", $itemTypeId), "text", $irMetadataArray);

        // language
        if(strlen($irBasic["language"]) == 0)
        {
            $irBasic["language"] = "ja";
        }
        $this->makeMetadataArray($languageArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("language", $itemTypeId), "text", $irMetadataArray);

        // relation
        $this->makeMetadataArray($relationArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("relation", $itemTypeId), "text", $irMetadataArray);

        // pmid
        $this->makeMetadataArray($pmidArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("pmid", $itemTypeId), "text", $irMetadataArray);

        // doi
        $this->makeMetadataArray($doiArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("doi", $itemTypeId), "text", $irMetadataArray);

        // isVersionOf
        $this->makeMetadataArray($isVersionOfArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isVersionOf", $itemTypeId), "link", $irMetadataArray);

        // hasVersion
        $this->makeMetadataArray($hasVersionArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("hasVersion", $itemTypeId), "link", $irMetadataArray);

        // isReplacedBy
        $this->makeMetadataArray($isReplacedByArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isReplacedBy", $itemTypeId), "link", $irMetadataArray);

        // replaces
        $this->makeMetadataArray($replacesArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("replaces", $itemTypeId), "link", $irMetadataArray);

        // isRequiredBy
        $this->makeMetadataArray($isRequiredByArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isRequiredBy", $itemTypeId), "link", $irMetadataArray);

        // requires
        $this->makeMetadataArray($requiresArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("requires", $itemTypeId), "link", $irMetadataArray);

        // isPartOf
        $this->makeMetadataArray($isPartOfArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isPartOf", $itemTypeId), "link", $irMetadataArray);

        // hasPart
        $this->makeMetadataArray($hasPartArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("hasPart", $itemTypeId), "link", $irMetadataArray);

        // isReferencedBy
        $this->makeMetadataArray($isReferencedByArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isReferencedBy", $itemTypeId), "link", $irMetadataArray);

        // references
        $this->makeMetadataArray($referencesArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("references", $itemTypeId), "link", $irMetadataArray);

        // isFormatOf
        $this->makeMetadataArray($isFormatOfArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isFormatOf", $itemTypeId), "link", $irMetadataArray);

        // hasFormat
        $this->makeMetadataArray($hasFormatArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("hasFormat", $itemTypeId), "link", $irMetadataArray);

        // coverage
        $this->makeMetadataArray($coverageArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("coverage", $itemTypeId), "text", $irMetadataArray);

        // spatial
        $this->makeMetadataArray($spatialArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("spatial", $itemTypeId), "text", $irMetadataArray);

        // NIIspatial
        $this->makeMetadataArray($niiSpatialArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NIIspatial", $itemTypeId), "text", $irMetadataArray);

        // temporal
        $this->makeMetadataArray($temporalArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("temporal", $itemTypeId), "text", $irMetadataArray);

        // NIItemporal
        $this->makeMetadataArray($niiTemporalArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("NIItemporal", $itemTypeId), "text", $irMetadataArray);

        // rights
        $this->makeMetadataArray($rightsArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("rights", $itemTypeId), "text", $irMetadataArray);

        // textversion
        $this->makeMetadataArray($textversionArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("textversion", $itemTypeId), "select", $irMetadataArray);

        // Add JuNii2 ver3 2013/09/25 R.Matsuura --start--
        // isbn
        $this->makeMetadataArray($isbnArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("isbn", $itemTypeId), "text", $irMetadataArray);

        // naid
        $this->makeMetadataArray($naidArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("naid", $itemTypeId), "text", $irMetadataArray);

        // ichushi
        $this->makeMetadataArray($ichushiArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("ichushi", $itemTypeId), "text", $irMetadataArray);

        // grantid
        $this->makeMetadataArray($grantidArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("grantid", $itemTypeId), "text", $irMetadataArray);

        // dateofgranted
        $this->makeMetadataArray($dateofgrantedArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("dateofgranted", $itemTypeId), "text", $irMetadataArray);

        // degreename
        $this->makeMetadataArray($degreenameArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("degreename", $itemTypeId), "text", $irMetadataArray);

        // grantor
        $this->makeMetadataArray($grantorArray, $itemId, $itemNo, $itemTypeId, $this->getAttrIdFromDbByMapping("grantor", $itemTypeId), "text", $irMetadataArray);
        // Add JuNii2 ver3 2013/09/25 R.Matsuura --end--

        // repository_id
        $this->makeMetadataArray(array($this->repositoryId), $itemId, $itemNo, $itemTypeId, 52, "text", $irMetadataArray);

        // contents_id
        $this->makeMetadataArray(   array($metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"]),
                                    $itemId, $itemNo, $itemTypeId, 53, "text", $irMetadataArray);

        // contents mod_date
        $this->makeMetadataArray(   array($metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"]),
                                    $itemId, $itemNo, $itemTypeId, 54, "text", $irMetadataArray);

        // Set additional metadata
        $this->setAdditionalMetadata($itemId, $itemNo, $itemTypeId, $irMetadataArray);

        return true;
    }

    /**
     * makeMetadataArray
     *
     * @param array $attributeArray
     * @param int $itemId
     * @param int $itemNo
     * @param int $itemTypeId
     * @param int $attributeId
     * @param string $inputType
     * @param array &$metadataArray
     * @return bool
     */
    private function makeMetadataArray($attributeArray, $itemId, $itemNo, $itemTypeId, $attributeId, $inputType, &$metadataArray)
    {
        $cnt = 1;
        for($ii=0; $ii<count($attributeArray); $ii++)
        {
            if(strlen($attributeArray[$ii]) > 0)
            {
                $metadata = array(  "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId,
                                    "attribute_id" => $attributeId, "attribute_no" => $cnt,
                                    "input_type" => $inputType, "attribute_value" => $attributeArray[$ii]);
                array_push($metadataArray, $metadata);
                $cnt++;
            }
        }

        return true;
    }

    /**
     * makeNameMetadataArrayOaiDc
     *
     * @param array $attributeArray
     * @param int $itemId
     * @param int $itemNo
     * @param int $itemTypeId
     * @param int $attributeId
     * @param string $language
     * @param array &$metadataArray
     * @return bool
     */
    private function makeNameMetadataArrayForOaiDc($attributeArray, $itemId, $itemNo, $itemTypeId, $attributeId, $language, &$metadataArray)
    {
        // Get author ID from database
        $query = "SELECT personal_name_no, family, name, author_id ".
                 "FROM ".DATABASE_PREFIX."repository_personal_name ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ".
                 "ORDER BY personal_name_no ASC;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        $cnt = 1;
        for($ii=0; $ii<count($attributeArray); $ii++)
        {
            if(strlen($attributeArray[$ii]) > 0)
            {
                $family = "";
                $name = "";
                $authorId = 0;
                $exp = explode(",", $attributeArray[$ii], 2);
                if(count($exp) == 2)
                {
                    // Divided
                    $family = trim($exp[0]);
                    $name = trim($exp[1]);
                }
                else
                {
                    // Try to divide by white space
                    $exp = explode(" ", $attributeArray[$ii], 2);
                    if(count($exp) == 2)
                    {
                        // Divided
                        $family = trim($exp[0]);
                        $name = trim($exp[1]);
                    }
                    else
                    {
                        // Cannot divided
                        $family = $attributeArray[$ii];
                    }
                }

                // Check author ID
                if(isset($result[$cnt-1]))
                {
                    if($result[$cnt-1]["personal_name_no"] == $cnt && $result[$cnt-1]["family"] == $family && $result[$cnt-1]["name"] == $name)
                    {
                        $authorId = intval($result[$cnt-1]["author_id"]);
                    }
                }

                $metadata = array(  "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId, "attribute_id" => $attributeId,
                                    "family" => $family, "name" => $name, "family_ruby" => "", "name_ruby" => "", "e_mail_address" => "",
                                    "author_id" => $authorId, "language" => $language, "input_type" => "name", "personal_name_no" => $cnt);
                array_push($metadataArray, $metadata);
                $cnt++;
            }
        }

        return true;
    }

    /**
     * makeBiblioInfoMetadataArray
     *
     * @param string $jtitle
     * @param string $volume
     * @param string $issue
     * @param string $spage
     * @param string $epage
     * @param string $dateofissued
     * @param int $itemId
     * @param int $itemNo
     * @param int $itemTypeId
     * @param int $attributeId
     * @param array &$metadataArray
     * @return bool
     */
    private function makeBiblioInfoMetadataArray(   $jtitle, $volume, $issue, $spage, $epage, $dateofissued,
                                                    $itemId, $itemNo, $itemTypeId, $attributeId, &$metadataArray)
    {
        $jtitleJpn = $jtitle;
        $jtitleEng = "";
        $exp = explode("=", $jtitle, 2);
        if(count($exp) == 2)
        {
            // Divided
            $jtitleJpn = trim($exp[0]);
            $jtitleEng = trim($exp[1]);
        }

        $metadata = array(  "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId, "attribute_id" => $attributeId,
                            "biblio_name" => $jtitleJpn, "biblio_name_english" => $jtitleEng, "volume" => $volume, "issue" => $issue,
                            "start_page" => $spage, "end_page" => $epage, "date_of_issued" => $dateofissued, "input_type" => "biblio_info", "biblio_no" => 1);
        array_push($metadataArray, $metadata);

        return true;
    }

    /**
     * Check and convert language
     *
     * @param string $lang
     * @return string
     */
    function checkLanguage($lang)
    {
        $lang = strtolower($lang);
        if(isset($lang) && strlen($lang) > 0)
        {
            switch($lang)
            {
                case 'ja':
                case 'jpn':
                case 'japanese':
                    $lang = 'ja';
                    break;
                case 'en':
                case 'eng':
                case 'english':
                    $lang = 'en';
                    break;
                case 'it':
                case 'ita':
                    $lang = 'it';
                    break;
                case 'de':
                case 'ger':
                case 'deu':
                    $lang = 'de';
                    break;
                case 'es':
                case 'spa':
                    $lang = 'es';
                    break;
                case 'zh':
                case 'zho':
                    $lang = 'zh';
                    break;
                case 'ru':
                case 'rus':
                    $lang = 'ru';
                    break;
                case 'la':
                case 'lat':
                    $lang = 'la';
                    break;
                case 'ms':
                case 'may':
                case 'msa':
                    $lang = 'ms';
                    break;
                case 'eo':
                case 'epo':
                    $lang = 'eo';
                    break;
                case 'ar':
                case 'ara':
                    $lang = 'ar';
                    break;
                case 'el':
                case 'gre':
                case 'ell':
                    $lang = 'el';
                    break;
                case 'ko':
                case 'kor':
                    $lang = 'ko';
                    break;
                default:
                    $lang = '';
                    break;
            }
        }
        else
        {
            $lang = '';
        }
        return $lang;
    }

    /**
     * Get setSpec string for harvesting log
     *
     * @param array $setSpecArray
     * @return string
     */
    private function getSetSpecStr($setSpecArray)
    {
        $setSpecStr = "";
        foreach($setSpecArray as $setSpecVal)
        {
            if(strlen($setSpecVal["value"]) > 0)
            {
                if(strlen($setSpecStr) > 0)
                {
                    $setSpecStr .= "|";
                }
                $setSpecStr .= $setSpecVal["value"];
            }
        }

        return $setSpecStr;
    }

    /**
     * Get index_id sting for harvesting log
     *
     * @param array $setSpecArray
     * @return string
     */
    private function getSetSpecIndexIdStr($setSpecArray)
    {
        $indexIdStr = "";
        foreach($setSpecArray as $setSpecVal)
        {
            if(strlen($setSpecVal["value"]) > 0)
            {
                $setSpecValEx = explode(":", $setSpecVal["value"]);
                $setSpecValExValue = end($setSpecValEx);

                $indexId = "";

                // get index data
                $query = "SELECT index_id ".
                        " FROM ".DATABASE_PREFIX. RepositoryConst::DBTABLE_REPOSITORY_INDEX.
                        " WHERE is_delete = ? ".
                        " AND repository_id = ? ".
                        " AND set_spec = ? ;";
                $params = array();
                $params[] = 0;
                $params[] = $this->repositoryId;
                $params[] = $setSpecValExValue;
                $result = $this->Db->execute($query, $params);
                if($result === false || count($result) == 0)
                {
                    // not exists
                    continue;
                }

                if(strlen($result[0]["index_id"]) > 0)
                {
                    if(strlen($indexIdStr) > 0)
                    {
                        $indexIdStr .= "|";
                    }
                    $indexIdStr .= $result[0]["index_id"];
                }
            }
        }

        return $indexIdStr;
    }

    /**
     * setItemStatusToPublic
     *
     * @param int $itemId
     * @param int $itemNo
     * @param bool $whatsNewFlag
     * @return bool
     */
    private function setItemStatusToPublic($itemId, $itemNo, $whatsNewFlag=false)
    {
        $query = "SELECT title, uri FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ?;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        $uri = $result[0]["uri"];

        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);

        $suffix = $repositoryHandleManager->getYHandleSuffix($itemId, $itemNo);
        if(strlen($suffix) === 0){
            try{
                $result = $repositoryHandleManager->registerYHandleSuffix($result[0]["title"], $itemId, $itemNo);
            } catch(AppException $ex){
                $error = $ex->getMessage();
                array_push($this->logMsg, $error);
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                $this->debugLog($error. "::itemId=". $itemId, __FILE__, __CLASS__, __LINE__);
            }
        }

        $uri = $repositoryHandleManager->getSubstanceUri($itemId, $itemNo);

        // Update item table
        $query = "UPDATE ".DATABASE_PREFIX."repository_item ".
                 "SET review_status = 1, ".
                 "review_date = shown_date, ".
                 "shown_status = 1, ".
                 "uri = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ?;";
        $params = array();
        $params[] = $uri;
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        // Add detail search 2013/11/25 K.Matsuo --start--
        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->updateSearchTableForItem($itemId, $itemNo);
        // Add detail search 2013/11/25 K.Matsuo --end--

        // Add to what's new module
        if($whatsNewFlag){
            $result = $this->addWhatsnew($itemId, $itemNo);
            if($result === false){
                return false;
            }
        }

        return true;
    }

    /**
     * Update item delete flag
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int &$isDelFlag
     * @return bool
     */
    private function updateIsDeleteForItemTable($itemId, $itemNo, $isDelFlag = 0)
    {
        if(intval($isDelFlag) != 0)
        {
            $isDelFlag = 1;
        }

        $query = "UPDATE ".DATABASE_PREFIX."repository_item ".
                 "SET is_delete = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ?;";
        $params = array();
        $params[] = $isDelFlag;
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        return true;
    }

    /**
     * Get top parent index id
     *
     * @param int $indexId
     * @return int
     */
    private function getTopParentIndexId($indexId)
    {
        $retIndexId = $indexId;

        if($retIndexId != 0)
        {
            $query = "SELECT parent_index_id ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE index_id = ? ".
                     "AND is_delete = ?; ";
            $params = array();
            $params[] = $retIndexId;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false || count($result)==0)
            {
                return $retIndexId;
            }

            if($result[0]['parent_index_id'] > 0)
            {
                $retIndexId = $this->getTopParentIndexId($result[0]['parent_index_id']);
            }
        }

        return $retIndexId;
    }

    /**
     * Set additional metadata
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $itemTypeId
     * @param array &$metadataArray
     * @return int
     */
    private function setAdditionalMetadata($itemId, $itemNo, $itemTypeId, &$metadataArray)
    {
        // Add JuNii2 ver3 2013/09/18 R.Matsuura --start--
        $startAddShowOrder = "";
        if($itemTypeId == 20001)
        {
            // Dublin core
            // Mod Number to constant 2014/03/15 T.Koyasu --start--
            $startAddShowOrder = RepositoryConst::HARVESTING_DC_ADD_ATTR_SHOW_ORDER;
            // Mod Number to constant 2014/03/15 T.Koyasu --end--
        }
        else if($itemTypeId >= 20002 && $itemTypeId <= 20015)
        {
            // JuNii2
            // Mod Number to constant 2014/03/15 T.Koyasu --start--
            $startAddShowOrder =RepositoryConst::HARVESTING_JUNII2_ADD_ATTR_SHOW_ORDER;
            // Mod Number to constant 2014/03/15 T.Koyasu --end--
        }
        else
        {
            return false;
        }

        // Get itemAttrType
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER." >= ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." ASC;";
        // Add JuNii2 ver3 2013/09/18 R.Matsuura --end--
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $startAddShowOrder;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        foreach($result as $itemAttrType)
        {
            $inputType = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
            $attrId = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID];
            if($inputType == RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO)
            {
                $this->setAdditionalBiblioInfo($itemId, $itemNo, $attrId, $itemTypeId, $metadataArray);
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_FILE || $inputType == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE)
            {
                // no update
                continue;
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_NAME)
            {
                $language = $itemAttrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
                $this->setAdditionalName($itemId, $itemNo, $attrId, $itemTypeId, $language, $metadataArray);
            }
            else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_THUMBNAIL)
            {
                // no update
                continue;
            }
            else
            {
                $this->setAdditionalAttribute($itemId, $itemNo, $attrId, $itemTypeId, $inputType, $metadataArray);
            }
        }

        return true;
    }

    /**
     * Set additional biblioInfo
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $itemTypeId
     * @param array &$metadataArray
     * @return int
     */
    private function setAdditionalBiblioInfo($itemId, $itemNo, $attrId, $itemTypeId, &$metadataArray)
    {
        // Get BiblioInfo
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_BIBLIO_INFO." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NO." ASC;";
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        foreach($result as $biblioInfo)
        {
            $biblioInfo["input_type"] = RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO;
            array_push($metadataArray, $biblioInfo);
        }
    }

    /**
     * Set additional name
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $itemTypeId
     * @param string $language
     * @param array &$metadataArray
     * @return int
     */
    private function setAdditionalName($itemId, $itemNo, $attrId, $itemTypeId, $language, &$metadataArray)
    {
        // Get personalName
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PERSONAL_NAME." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_PERSONAL_NAME_NO." ASC;";
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        foreach($result as $personalName)
        {
            // Add JuNii2 ver3 2013/09/18 R.Matsuura --start--
            // Get authorId
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_EXTERNAL_AUTHOR_ID_SUFFIX." ".
                     "WHERE author_id = ? ".
                     "AND prifix_id = 2 ".
                     "AND is_delete = 0;";
            $params = array();
            $params[] = $personalName["author_id"];
            $result = $this->Db->execute($query, $params);
            if(count($result) == 1)
            {
                $personalName["external_author_id"] = array("prefix_id" => 2, "suffix" => $result[0]["suffix"]);
            }
            // Add JuNii2 ver3 2013/09/18 R.Matsuura --end--
            $personalName["language"] = $language;
            $personalName["input_type"] = RepositoryConst::ITEM_ATTR_TYPE_NAME;
            array_push($metadataArray, $personalName);
        }
    }

    /**
     * Set additional attribute
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $itemTypeId
     * @param array &$metadataArray
     * @return int
     */
    private function setAdditionalAttribute($itemId, $itemNo, $attrId, $itemTypeId, $inputType, &$metadataArray)
    {
        // Get attribute
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_NO." ASC;";
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        foreach($result as $itemAttr)
        {
            $itemAttr["input_type"] = $inputType;
            array_push($metadataArray, $itemAttr);
        }
    }

    // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
    /**
     * Set Harvesting From Date
     *
     * @param string $fromDate
     */
    public function setFromDate($fromDate)
    {
        $this->from_date = $fromDate;
    }

    /**
     * Set Harvesting Until Date
     *
     * @param string $untilDate
     */
    public function setUntilDate($untilDate)
    {
        $this->until_date = $untilDate;
    }

    /**
     * Set Harvesting Set Param
     *
     * @param string $setParam
     */
    public function setSetParam($setParam)
    {
        $this->set_param = $setParam;
    }

    /**
     * Get Harvesting Param
     *
     * @return string $harvestParam
     */
    private function getHarvestingParam()
    {
        $harvestParam = "";
        if($this->from_date != null && $this->from_date != "")
        {
            $harvestParam .= "&from=" . $this->from_date;
        }
        if($this->until_date != null && $this->until_date != "")
        {
            $harvestParam .= "&until=" . $this->until_date;
        }
        if($this->set_param != null && $this->set_param != "")
        {
            $harvestParam .= "&set=" . $this->set_param;
        }
        return $harvestParam;
    }
    // Add Selective Harvesting 2013/09/04 R.Matsuura --end--

    // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --start--
    /**
     * Check Oaidc Metadata
     *
     * @param string $tagName
     * @param array  $tagValues
     */
    private function checkOaidcMetadata($tagName, $tagValues, $identifier, $datestamp, $setSpecStr)
    {
        // title
        //if(strtoupper($tagName) == strtoupper(RepositoryConst::DUBLIN_CORE_TITLE))
        $patternTitle = strtoupper("/^(".RepositoryConst::DUBLIN_CORE_PREFIX.")?".RepositoryConst::DUBLIN_CORE_TITLE."$/");
        $patternLang = strtoupper("/^(".RepositoryConst::DUBLIN_CORE_PREFIX.")?".RepositoryConst::DUBLIN_CORE_LANGUAGE."$/");
        if(preg_match($patternTitle, strtoupper($tagName)) == 1)
        {
            $noTitleFlag = true;
            foreach ($tagValues as $tagVal)
            {
                if(strlen($tagVal["value"]) > 0)
                {
                    $noTitleFlag = false;
                }
            }

            // Error log
            if($noTitleFlag)
            {
                array_push($this->logMsg, "repository_harvesting_error_get_title");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
                $this->entryHarvestingLog(  RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "",
                                            $setSpecStr, "", $identifier, "", $this->requestUrl, $datestamp);
                return false;
            }
        }

        // language
        //else if(strtoupper($tagName) == strtoupper(RepositoryConst::DUBLIN_CORE_LANGUAGE))
        else if(preg_match($patternLang, strtoupper($tagName)) == 1)
        {
            $langWarnFlag = true;
            foreach ($tagValues as $tagVal) {
                $lang = $this->checkLanguage($tagVal["value"]);
                if(strlen($lang) > 0)
                {
                    $langWarnFlag = false;
                }
            }
            if($langWarnFlag)
            {
                array_push($this->logMsg, "repository_harvesting_warning_miss_language");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }
        return true;
    }

    /**
     * Check JuNii2 Metadata
     *
     * @param string $tagName
     * @param array  $tagValues
     */
    private function checkJuNii2Metadata($tagName, $tagValues, $identifier, $datestamp, $setSpecStr)
    {
        $err_msg = $this->checkJuNii2MetadataInner($tagName, $tagValues);

        // When error is there
        if($err_msg != "")
        {
            array_push($this->logMsg, $err_msg);
            $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
            $this->entryHarvestingLog(  RepositoryConst::HARVESTING_OPERATION_ID_LISTRECORD, "", "",
                                        $setSpecStr, "", $identifier, "", $this->requestUrl, $datestamp);
            return false;
        }
        return true;
    }

    /**
     * Check JuNii2 Metadata Inner
     *
     * @param string $tagName
     * @param array  $tagValues
     */
    private function checkJuNii2MetadataInner($tagName, $tagValues)
    {
        // title
        if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_TITLE))
        {
            if(count($tagValues) > 1)
            {
                return "repository_harvesting_error_over_title";
            }
            else if(count($tagValues) == 0 || strlen($tagValues[0]["value"]) == 0)
            {
                return "repository_harvesting_error_get_title";
            }
        }

        // language
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_LANGUAGE))
        {
            $langWarnFlag = true;
            foreach ($tagValues as $tagVal) {
                $lang = RepositoryOutputFilterJuNii2::langISOForWEKO($tagVal["value"]);
                if(strlen($lang) > 0)
                {
                    $langWarnFlag = false;
                }
            }
            if($langWarnFlag)
            {
                array_push($this->logMsg, "repository_harvesting_warning_miss_language");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // NIIType
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_NIITYPE))
        {
            if(count($tagValues) > 1)
            {
                return "repository_harvesting_error_over_NIIType";
            }
            else if(count($tagValues) == 0)
            {
                return "repository_harvesting_error_get_NIIType";
            }
            else
            {
                if($tagValues[0]["value"] != RepositoryConst::NIITYPE_JOURNAL_ARTICLE
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_CONFERENCE_PAPER
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_PRESENTATION
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_BOOK
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_TECHNICAL_REPORT
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_RESEARCH_PAPER
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_ARTICLE
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_PREPRINT
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_LEARNING_MATERIAL
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_DATA_OR_DATASET
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_SOFTWARE
                    && $tagValues[0]["value"] != RepositoryConst::NIITYPE_OTHERS)
                {
                    return "repository_harvesting_error_miss_NIIType";
                }
            }
        }

        // URI
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_URI))
        {
            if(count($tagValues) > 1)
            {
                return "repository_harvesting_error_over_uri";
            }
            else if(count($tagValues) == 0)
            {
                return "repository_harvesting_error_get_uri";
            }
            else
            {
                $accessFlag = false;

                /////////////////////////////
                // HTTP_Request init
                /////////////////////////////
                // send http request
                $proxy = $this->getProxySetting();
                $option = null;
                if($proxy['proxy_mode'] == 1)
                {
                    $option = array(
                                    "timeout" => 10,
                                    "allowRedirects" => true,
                                    "maxRedirects" => 3,
                                    "proxy_host"=>$proxy['proxy_host'],
                                    "proxy_port"=>$proxy['proxy_port'],
                                    "proxy_user"=>$proxy['proxy_user'],
                                    "proxy_pass"=>$proxy['proxy_pass']
                                );
                }
                else
                {
                    $option = array(
                                    "timeout" => 10,
                                    "allowRedirects" => true,
                                    "maxRedirects" => 3
                                );
                }

                // HTTP Request
                $http = new HTTP_Request($tagValues[0]["value"], $option);

                // setting HTTP header
                $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
                $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);

                /////////////////////////////
                // run HTTP request
                /////////////////////////////
                $response = $http->sendRequest();
                if (!PEAR::isError($response)) {
                    if($http->getResponseCode() == 200)
                    {
                        $accessFlag = true;
                    }
                }

                if(!$accessFlag)
                {
                    array_push($this->logMsg, "repository_harvesting_warning_access_uri");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                }
            }
        }

        // jtitle
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_JTITLE))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_jtitle");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // volume
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_VOLUME))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_volume");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // issue
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_ISSUE))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_issue");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // spage
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_SPAGE))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_spage");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // epage
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_EPAGE))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_epage");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // deteofissued
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_DATE_OF_ISSUED))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_dateofissued");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // pmid
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_PMID))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_pmid");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // doi
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_DOI))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_doi");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        // textversion
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_TEXTVERSION))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_textversion");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            if($tagValues[0]["value"] != "author" && $tagValues[0]["value"] != "publisher" && $tagValues[0]["value"] != "ETD" && $tagValues[0]["value"] != "none")
            {
                array_push($this->logMsg, "repository_harvesting_warning_miss_textversion");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //self DOI
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_SELFDOI))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_selfDOI");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }


            if(array_key_exists("RA", $tagValues["attributes"]) &&
               strlen($tagValues["attributes"]["RA"]) > 0 &&
               $tagValues["attributes"]["RA"] != RepositoryConst::JUNII2_SELFDOI_RA_JALC)
            {
                array_push($this->logMsg, "repository_harvesting_warning_miss_selfDOI");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //NAID
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_NAID))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_naid");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            $str_uri = RepositoryOutputFilterJuNii2::naid($tagValues[0]["value"]);
            if($str_uri == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_naid_uri");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //ichushi
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_ICHUSHI))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_ichushi");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            $str_uri = RepositoryOutputFilterJuNii2::ichushi($tagValues[0]["value"]);
            if($str_uri == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_ichushi_uri");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //grantid
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_GRANTID))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_grantid");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            $str_id = RepositoryOutputFilterJuNii2::grantid($tagValues[0]["value"]);
            if($str_id == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_grantid");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //deteofgranted
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_DATEOFGRANTED))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_dateofgranted");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            $str_date = RepositoryOutputFilter::date($tagValues[0]["value"]);
            if($str_date == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_dateofgranted");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //degreename
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_DEGREENAME))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_degreename");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            if($tagValues[0]["value"] == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_degreename");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //grantor
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_GRANTOR))
        {
            if(count($tagValues) > 1)
            {
                array_push($this->logMsg, "repository_harvesting_warning_over_grantor");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }

            if($tagValues[0]["value"] == "")
            {
                array_push($this->logMsg, "repository_harvesting_warning_get_grantor");
                $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
            }
        }

        //creator
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_CREATOR))
        {
            if(array_key_exists("LANG", $tagValues[0]["attributes"]))
            {
                $str_lang = RepositoryOutputFilterJuNii2::langRFCForWEKO($tagValues[0]["attributes"]["LANG"]);
                if($str_lang == "")
                {
                    array_push($this->logMsg, "repository_harvesting_warning_get_creator_lang");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                }
            }
        }

        //publisher
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_PUBLISHER))
        {
            if(array_key_exists("LANG", $tagValues[0]["attributes"]))
            {
                $str_lang = RepositoryOutputFilterJuNii2::langRFCForWEKO($tagValues[0]["attributes"]["LANG"]);
                if($str_lang == "")
                {
                    array_push($this->logMsg, "repository_harvesting_warning_get_publisher_lang");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                }
            }
        }

        //contributor
        else if(strtoupper($tagName) == strtoupper(RepositoryConst::JUNII2_CONTRIBUTOR))
        {
            if(array_key_exists("LANG", $tagValues[0]["attributes"]))
            {
                $str_lang = RepositoryOutputFilterJuNii2::langRFCForWEKO($tagValues[0]["attributes"]["LANG"]);
                if($str_lang == "")
                {
                    array_push($this->logMsg, "repository_harvesting_warning_get_contributor_lang");
                    $this->harvestingLogStatus = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
                }
            }
        }
    }

    /**
     * get attribute ID from database by mapping
     *
     * @param string $strMapping
     * @param int $itemTypeId
     * @param string $lang
     */
    private function getAttrIdFromDbByMapping($strMapping, $itemTypeId, $lang="")
    {
        $query = "SELECT attribute_id ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_JUNII2_MAPPING." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER." >= 0 ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER." <= 58 ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE." = ? ;";
        $params = array();
        $params[] = $strMapping;
        $params[] = $itemTypeId;
        $params[] = $lang;

        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) != 1)
        {
            return -1;
        }

        return $result[0]['attribute_id'];
    }

    /**
     * put name metadata to arrays
     *
     * @param array $inputTypeNameMetadata
     * @param array $nameArray
     * @param array $idArray
     * @param array $japaneseNameArray
     * @param array $japaneseIdArray
     */
    private function putNameMetadataToArrays($inputTypeNameMetadata, &$nameArray, &$idArray, &$langArray, &$japaneseNameArray, &$japaneseIdArray, &$japaneseLangArray)
    {
        $uri = "";
        if(array_key_exists("ID", $inputTypeNameMetadata["attributes"]) && strlen($inputTypeNameMetadata["attributes"]["ID"]) > 0)
        {
            $uri = RepositoryOutputFilterJuNii2::convertId($inputTypeNameMetadata["attributes"]["ID"]);
        }

        if(array_key_exists("LANG", $inputTypeNameMetadata["attributes"]) && strlen($inputTypeNameMetadata["attributes"]["LANG"]) > 0)
        {
            $checkStr = RepositoryOutputFilterJuNii2::langRFCForWEKO($inputTypeNameMetadata["attributes"]["LANG"]);
        }
        else
        {
            $checkStr = "";
        }

        if($checkStr == "japanese")
        {
            array_push($japaneseNameArray, $inputTypeNameMetadata["value"]);
            array_push($japaneseLangArray, "japanese");
            if(array_key_exists("ID", $inputTypeNameMetadata["attributes"]) && strlen($inputTypeNameMetadata["attributes"]["ID"]) > 0)
            {
                array_push($japaneseIdArray, $uri);
            }
            else
            {
                array_push($japaneseIdArray, "");
            }
        }
        else
        {
            array_push($nameArray, $inputTypeNameMetadata["value"]);
            if(array_key_exists("ID", $inputTypeNameMetadata["attributes"]) && strlen($inputTypeNameMetadata["attributes"]["ID"]) > 0)
            {
                array_push($idArray, $uri);
            }
            else
            {
                array_push($idArray, "");
            }
            array_push($langArray, $checkStr);
        }
    }

    /**
     * makeNameMetadataArrayForJuNii2
     *
     * @param array $attributeArray
     * @param int $itemId
     * @param int $itemNo
     * @param int $itemTypeId
     * @param int $attributeId
     * @param array $langArray
     * @param array $idArray
     * @param array &$metadataArray
     * @return bool
     */
    private function makeNameMetadataArrayForJuNii2($attributeArray, $itemId, $itemNo, $itemTypeId, $attributeId, $langArray, $idArray, &$metadataArray)
    {
        // Get author ID from database
        $query = "SELECT personal_name_no, family, name, author_id ".
                 "FROM ".DATABASE_PREFIX."repository_personal_name ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }

        $cnt = 1;
        for($ii=0; $ii<count($attributeArray); $ii++)
        {
            if(strlen($attributeArray[$ii]) > 0)
            {
                $family = "";
                $name = "";
                $authorId = 0;
                $exp = explode(",", $attributeArray[$ii], 2);
                if(count($exp) == 2)
                {
                    // Divided
                    $family = trim($exp[0]);
                    $name = trim($exp[1]);
                }
                else
                {
                    // Try to divide by white space
                    $exp = explode(" ", $attributeArray[$ii], 2);
                    if(count($exp) == 2)
                    {
                        // Divided
                        $family = trim($exp[0]);
                        $name = trim($exp[1]);
                    }
                    else
                    {
                        // Cannot divided
                        $family = $attributeArray[$ii];
                    }
                }

                // Check author ID
                if(isset($result[$cnt-1]))
                {
                    if($result[$cnt-1]["personal_name_no"] == $cnt && $result[$cnt-1]["family"] == $family && $result[$cnt-1]["name"] == $name)
                    {
                        $authorId = intval($result[$cnt-1]["author_id"]);
                    }
                }

                $metadata = array(  "item_id" => $itemId, "item_no" => $itemNo, "item_type_id" => $itemTypeId, "attribute_id" => $attributeId,
                                    "family" => $family, "name" => $name, "family_ruby" => "", "name_ruby" => "", "e_mail_address" => "",
                                    "author_id" => $authorId, "language" => $langArray[$ii], "input_type" => "name", "personal_name_no" => $cnt);
                if($idArray[$ii] != null && $idArray[$ii] != "")
                {
                    $metadata["external_author_id"] = array();
                    array_push($metadata["external_author_id"], array("prefix_id" => 2, "suffix" => $idArray[$ii]));
                }
                array_push($metadataArray, $metadata);
                $cnt++;
            }
        }

        return true;
    }
    // Add for JuNii2 Redaction 2013/09/16 R.Matsuura --end--
}

?>
