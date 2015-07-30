<?php
// --------------------------------------------------------------------
//
// $Id: Oaipmh.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

/**
 * Output metadata format OAI-PMH(DublinCore, JuNii2, Learning Object Metadata).
 *
 */
class Repository_Oaipmh extends RepositoryAction
{
    /**
     * Session components
     *
     * @var Object
     */
    public $Session = null;
    /**
     * database components
     *
     * @var Object
     */
    public $Db = null;


    /*******************************************************************/
    /**
     * requestParameter
     *
     * @var string GetRecord, Identify, ListIdentifiers,
     *             ListMetadataFormats, ListRecords, ListSets
     */
    public $verb = null;
    /**
     * requestParameter
     *
     * @var string
     */
    public $resumptionToken = null;
    /**
     * requestParameter
     *
     * @var string
     */
    public $identifier = null;
    /**
     * requestParameter
     *
     * @var string
     */
    public $set = null;
    /**
     * requestParameter
     *
     * @var strnig
     */
    public $metadataPrefix = null;
    /**
     * requestParameter
     *
     * @var string
     */
    public $from = null;
    /**
     * requestParameter
     *
     * @var string
     */
    public $until = null;

    /**
     * set resumptionToken start position
     *
     * @var int
     */
    private $position = 0;

    /*******************************************************************/
    /**
     * error code
     *
     * @var string
     */
    private $errorCode = '';
    /**
     * error message
     *
     * @var string
     */
    private $errorMessage = array();

    /**
     * output metadata tag class
     *   - DublinCore
     *   - JuNii2
     *   - LearningObjectMetadata
     *
     * @var Object
     */
    private $metadataClass = null;

    // Mod OpenDepo 2014/01/31 S.Arata --start--
    /**
     * public index query
     *
     * @var String
     */
    private $publicIndexQuery = "";
    // Mod OpenDepo 2014/01/31 S.Arata --end--

    /**
     * max item list for resumptionToken.
     *
     * @var int
     */
    private $maxItemList = 0;

    /**
     * harvest public index list
     *
     * @var array
     */
    private $harvestPublicIndex = array();

    /*******************************************************************/
    /**
     * verb list
     */
    const VERB_GETRECORD = 'GetRecord';
    const VERB_IDENTIFY  = 'Identify';
    const VERB_LISTIDENTIFIERS = 'ListIdentifiers';
    const VERB_LISTMETADATAFORMATS = 'ListMetadataFormats';
    const VERB_LISTRECORDS= 'ListRecords';
    const VERB_LISTSETS = 'ListSets';

    /**
     * error code
     *
     */
    const ERRORCODE_BAD_ARGUMENT = 'badArgument';
    const ERRORCODE_BAD_RESUMPTION_TOKEN = 'badResumptionToken';
    const ERRORCODE_BAD_VERB = 'badVerb';
    const ERRORCODE_CAN_NOT_DISSEMINATE_FORMAT = 'cannotDisseminateFormat';
    const ERRORCODE_ID_DOES_NOT_EXIST = 'idDoesNotExist';
    const ERRORCODE_NO_RECORDS_MATCH = 'noRecordsMatch';
    const ERRORCODE_NO_METADATA_FORMATS = 'noMetadataFormats';
    const ERRORCODE_NO_SET_HIERARCHY = 'noSetHierarchy';
    /**
     * max item
     *
     */
    const MAX_ITEM = 100;

    const MIN_DATE = '0001-01-01T00:00:00Z';
    const MAX_DATE = '9999-12-31T23:59:59Z';

    /*******************************************************************/

    public function execute()
    {
        // start action
        $this->initAction();

        // init member variable
        $this->initialize();
        $xml = '';

        // header
        $xml .= $this->outputHeader();

        $output = '';
        // body
        switch ($this->verb)
        {
            case self::VERB_IDENTIFY:
                // identify -> not must
                $output = $this->outputIdentify();
                break;
            case self::VERB_LISTMETADATAFORMATS:
                $output = $this->outputListMetadataFormats();
                break;
            case self::VERB_LISTSETS:
                $output = $this->outputListSets();
                break;
            case self::VERB_LISTIDENTIFIERS:
                $output = $this->outputMetadata(false);
                break;
            case self::VERB_LISTRECORDS:
                $output = $this->outputMetadata();
                break;
            case self::VERB_GETRECORD:
                $this->checkIdentifier(true);
                if(strlen($this->errorCode) == 0)
                {
                    $output = $this->outputMetadata();
                }
                break;
            default:
                // bad request
                $this->errorCode = self::ERRORCODE_BAD_VERB;
                break;
        }

        // error
        if(strlen($this->errorCode) > 0)
        {
            $xml .= $this->outputError();
        }
        else
        {
            $xml .= $output;
        }

        // footer
        $xml .= $this->outputFooter();

        // output
        header("Content-Type: text/xml; charset=utf-8");
        echo $xml;

        // exit
        $this->exitAction();
        exit();
    }

    /**
     * initialize
     *
     */
    private function initialize()
    {
        // init output string
        $this->errorCode = '';

        // set error message
        $this->errorMessage = array();
        $this->errorMessage[self::ERRORCODE_BAD_ARGUMENT] = 'The request includes illegal arguments, is missing required arguments, includes a repeated argument, or values for arguments have an illegal syntax.';
        $this->errorMessage[self::ERRORCODE_BAD_RESUMPTION_TOKEN] = 'The value of the resumptionToken argument is invalid or expired.';
        $this->errorMessage[self::ERRORCODE_BAD_VERB] = 'Value of the verb argument is not a legal OAI-PMH verb, the verb argument is missing, or the verb argument is repeated.';
        $this->errorMessage[self::ERRORCODE_CAN_NOT_DISSEMINATE_FORMAT] = 'The metadata format identified by the value given for the metadataPrefix argument is not supported by the item or by the repository.';
        $this->errorMessage[self::ERRORCODE_ID_DOES_NOT_EXIST] = 'The value of the identifier argument is unknown or illegal in this repository.';
        $this->errorMessage[self::ERRORCODE_NO_RECORDS_MATCH] = 'The combination of the values of the from, until, set and metadataPrefix arguments results in an empty list.';
        $this->errorMessage[self::ERRORCODE_NO_METADATA_FORMATS] = 'There are no metadata formats available for the specified item.';
        $this->errorMessage[self::ERRORCODE_NO_SET_HIERARCHY] = 'The repository does not support sets.';
    }

    /**
     * check request parameter 'identifier'
     *
     * @param bool $required must
     */
    private function checkIdentifier($must=false)
    {
        $this->identifier = preg_replace("/\"|\'|\\|\$|\(|\)|\[|\]/", "", $this->identifier);
        if(strlen($this->identifier)==0)
        {
            if($must)
            {
                $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            }
            return;
        }

        $tmp_identifier = explode(":", $this->identifier);
        if(count($tmp_identifier) != 3)
        {
            $this->errorCode = self::ERRORCODE_ID_DOES_NOT_EXIST;
            return;
        }
        if ($tmp_identifier[0]!='oai' || $tmp_identifier[1]!=$_SERVER['HTTP_HOST'] || preg_match("/^[0-9]+$/", $tmp_identifier[2])==0) {
            $tmp_identifier[2] = 0;
        }
        $query = "SELECT count(item_id) FROM `". DATABASE_PREFIX ."repository_item` WHERE item_id = ".$tmp_identifier[2];
        $ret = $this->Db->execute($query);
        if ($ret === false || $ret[0]['count(item_id)'] < 1)
        {
            $this->errorCode = self::ERRORCODE_ID_DOES_NOT_EXIST;
        }
    }

    /**
     * check resumptionToken
     *
     * @param bool $required must
     */
    private function checkResumptionToken($must=false)
    {
        //0001-01-01T00:00:00Z/9999-12-31T23:59:59Z/123/junii2/100
        //$this->request_resumptionToken = $_GET['resumptionToken'];
        if(strlen($this->resumptionToken) == 0)
        {
            if($must)
            {
                $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            }
            return;
        }

        $prefixPattern = RepositoryConst::OAIPMH_METADATA_PREFIX_DC."|".
                         RepositoryConst::OAIPMH_METADATA_PREFIX_JUNII2."|".
                         RepositoryConst::OAIPMH_METADATA_PREFIX_LOM."|".
                         RepositoryConst::OAIPMH_METADATA_PREFIX_LIDO;
        if (preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z\/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z\/[0-9]*\/($prefixPattern)\/[0-9]+$/", $this->resumptionToken))
        {
            // split
            $tmp_resumptionToken = explode("/", $this->resumptionToken);

            // from
            $this->from  = $tmp_resumptionToken[0];
            if ($this->from == '0001-01-01T00:00:00Z')
            {
                $this->from = '';
            }

            // until
            $this->until = $tmp_resumptionToken[1];
            if ($this->until == '9999-12-31T23:59:59Z') {
                $this->until = '';
            }

            $this->set = $tmp_resumptionToken[2];
            $this->metadataPrefix = $tmp_resumptionToken[3];
            $this->position = $tmp_resumptionToken[4];
        }
        else if ($this->verb==self::VERB_LISTSETS && preg_match("/^\/\/\/\/[0-9]+$/", $this->resumptionToken))
        {
            $tmp_resumptionToken = explode("/", $this->resumptionToken);
            $this->position = $tmp_resumptionToken[4];
        }
        else
        {
            $this->errorCode = self::ERRORCODE_BAD_RESUMPTION_TOKEN;
            $this->request_set = '';
            $this->request_from = '';
            $this->request_until = '';
        }
    }

    /**
     * check request parameter from
     *
     * @param bool $required must
     */
    private function checkFromUntil($must=false)
    {
        // set define
        if($this->from == self::MIN_DATE)
        {
            $this->from = '';
        }
        if($this->until == self::MAX_DATE)
        {
            $this->until = '';
        }
        // not check
        if( !$must && strlen($this->from) == 0 && strlen($this->until) == 0 )
        {
            return;
        }
        else if($must && (strlen($this->from) == 0 || strlen($this->until) == 0) )
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            return;
        }

        // Modify modify oaipmh responce
        if((preg_match("/^(19|20)[0-9][0-9]-[0-1][0-9]-[0-3][0-9]$/", $this->from) == 1 &&
            preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z$/", $this->until) == 1
            ) ||
           (preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z$/", $this->from) == 1 &&
            preg_match("/^(19|20)[0-9][0-9]-[0-1][0-9]-[0-3][0-9]$/", $this->until) == 1
            )
        )
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            return;
        }
        // set request parameter from
        if (preg_match("/^(19|20)[0-9][0-9]-[0-1][0-9]-[0-3][0-9]$/", $this->from) == 1)
        {
            $this->from .= 'T00:00:00Z';
        }
        $this->from = $this->getLocalTime($this->from);

        // set request parameter until
        if (preg_match("/^(19|20)[0-9][0-9]-[0-1][0-9]-[0-3][0-9]$/", $this->until)) {
            $this->until .= 'T23:59:59Z';
        }
        $this->until = $this->getLocalTime($this->until);

        if( (strlen($this->from) > 0 && preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z$/", $this->from) == 0) ||
            (strlen($this->until) > 0 && preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]Z$/", $this->until) == 0) )
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
        }
    }

    /**
     * check request parameter set
     *
     * @param bool $required must
     */
    private function checkSet($must=false)
    {
        if($must && strlen($this->set) == 0)
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            return;
        }
        else if(preg_match("/^[0-9]+$/", $this->set) == 1)
        {
            // 'set' is index_id
            $query = "SELECT index_id ".
                     "FROM ". DATABASE_PREFIX ."repository_index ".
                     "WHERE index_id = ? ;";
            $params = array();
            $params[] = intval($this->set);
            $ret = $this->Db->execute($query, $params);
            if ($ret === false || count($ret) != 1)
            {
                $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            }
        }
        else
        {
            $this->set = '';
        }
    }

    /**
     * check request parameter metadataPrefix
     *
     * @param bool $required must
     */
    private function checkMetadataPrefix($must=false)
    {
        if($must && strlen($this->metadataPrefix) == 0)
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
        }
        else if($this->metadataPrefix == RepositoryConst::OAIPMH_METADATA_PREFIX_DC)
        {
            require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/DublinCore.class.php';
            $this->metadataClass = new Repository_Oaipmh_DublinCore($this->Session, $this->Db);
            if($this->metadataClass == null)
            {
                $this->errorCode = self::ERRORCODE_NO_METADATA_FORMATS;
            }
        }
        else if($this->metadataPrefix == RepositoryConst::OAIPMH_METADATA_PREFIX_JUNII2)
        {
            require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/JuNii2.class.php';
            $this->metadataClass = new Repository_Oaipmh_JuNii2($this->Session, $this->Db);
            if($this->metadataClass == null)
            {
                $this->errorCode = self::ERRORCODE_NO_METADATA_FORMATS;
            }
        }
        else if($this->metadataPrefix == RepositoryConst::OAIPMH_METADATA_PREFIX_LOM)
        {
            require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/LearningObjectMetadata.class.php';
            $this->metadataClass = new Repository_Oaipmh_LearningObjectMetadata($this->Session, $this->Db);
            if($this->metadataClass == null)
            {
                $this->errorCode = self::ERRORCODE_NO_METADATA_FORMATS;
            }
        }
        else if($this->metadataPrefix == RepositoryConst::OAIPMH_METADATA_PREFIX_LIDO)
        {
            require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/Lido.class.php';
            $this->metadataClass = new Repository_Oaipmh_Lido($this->Session, $this->Db);
            if($this->metadataClass == null)
            {
                $this->errorCode = self::ERRORCODE_NO_METADATA_FORMATS;
            }
        }
        else
        {
            $this->errorCode = self::ERRORCODE_NO_METADATA_FORMATS;
        }
    }

    /**
     * output header
     *
     */
    private function outputHeader()
    {
        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8" ?>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_OAIPMH;
        $xml .= ' xmlns="http://www.openarchives.org/OAI/2.0/"';
        $xml .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $xml .= ' xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/';
        $xml .= ' http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">';

        // responce date
        $responseDate = $this->TransStartDate;
        $responseDate = $this->convertDateForISO($responseDate);
        $responseDate = $this->getLocalTime($responseDate);
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_RES_DATE.'>';
        $xml .= $responseDate;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_RES_DATE.'>';

        // request url
        $requestParameter = '';
        $req = $_REQUEST;

        foreach ($req as $key => $value)
        {
            if( $key == 'verb' || $key == 'resumptionToken' || $key == 'metadataPrefix' ||
                $key == 'set' || $key == 'from' || $key == 'until' || $key == 'identifier')
            {
                $key = $this->forXmlChange($key);
                $value = $this->forXmlChange($value);
                $requestParameter .= ' '.$key.'="'.$value.'"';
            }
        }

        $requestUrl = BASE_URL."/?action=repository_oaipmh";
        if(isset($_SERVER["REDIRECT_URL"]) && strlen($_SERVER["REDIRECT_URL"]) > 0 && preg_match("/\/oai$/", $_SERVER["REDIRECT_URL"]) == 1)
        {
            $requestUrl = BASE_URL."/oai";
        }

        $xml .= '<'.RepositoryConst::OAIPMH_TAG_REQUEST.' '.$requestParameter.'>';
        $xml .= $requestUrl;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_REQUEST.'>';
        return $xml;
    }

    /**
     * output Identify
     *
     */
    private function outputIdentify()
    {
        // 以下すべて必須
        $repositoryName = $this->getParamValue('prvd_Identify_repositoryName');
        $baseUrl = $this->getOaiPmhBaseUrl();
        $protocolVersion = '2.0';
        $earliestDatestamp = $this->getParamValue('prvd_Identify_earliestDatestamp');

        // 以下が１つ以上あること
        $adminEmail = $this->getParamValue('prvd_Identify_adminEmail');

        // check required rule
        if(strlen($repositoryName) == 0 || strlen($earliestDatestamp) == 0 || strlen($adminEmail)==0)
        {
            $this->errorCode = self::ERRORCODE_BAD_ARGUMENT;
            return '';
        }

        // set output Identify
        $xml = '';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_IDENTIFY.'>';

        // repositoryName
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_REPO_NAME.'>';
        $xml .= $repositoryName;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_REPO_NAME.'>';
        // baseURL
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_BASE_URL.'>';
        $xml .= $baseUrl;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_BASE_URL.'>';
        // protocolVersion
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_PROT_VER.'>';
        $xml .= $protocolVersion;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_PROT_VER.'>';
        // adminEmail
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_ADMIN_EMAIL.'>';
        $xml .= $adminEmail;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_ADMIN_EMAIL.'>';
        // earliestDatestamp
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_EARLIEST_DATESTAMP.'>';
        $xml .= $earliestDatestamp;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_EARLIEST_DATESTAMP.'>';
        // deletedRecord
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_DEL_REC.'>';
        $xml .= RepositoryConst::OAIPMH_VAL_DEL_REC_TRN;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_DEL_REC.'>';
        // granularity
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_GRANULARITY.'>';
        $xml .= RepositoryConst::OAIPMH_VAL_GRANULARITY;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_GRANULARITY.'>';

        $xml .= '</'.RepositoryConst::OAIPMH_TAG_IDENTIFY.'>';

        return $xml;
    }

    /**
     * output list metadata formats
     * IN DublinCore(oai_dc), JuNii2(junii2), Learning Object Metadata(oai_lom)
     *
     */
    private function outputListMetadataFormats()
    {
        // identify -> not must
        $this->checkIdentifier();
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }

        $xml = '';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_LIST_MATA_FORMT.'>';
        // JuNii2
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= RepositoryConst::OAIPMH_METADATA_PREFIX_JUNII2;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= 'http://irdb.nii.ac.jp/oai/junii2-3-1.xsd';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= '<'.repositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= 'http://irdb.nii.ac.jp/oai';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        // DublinCore
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= RepositoryConst::OAIPMH_METADATA_PREFIX_DC;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= 'http://www.openarchives.org/OAI/2.0/oai_dc/';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        // LOM
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= RepositoryConst::OAIPMH_METADATA_PREFIX_LOM;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= 'http://ltsc.ieee.org/xsd/lomv1.0/lom.xsd';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= 'http://ltsc.ieee.org/xsd/LOM';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        // LIDO
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= RepositoryConst::OAIPMH_METADATA_PREFIX_LIDO;
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_PREFIX.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= 'http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_SCHEMA.'>';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= 'http://www.lido-schema.org';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_META_NAMESP.'>';
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_MATA_FORMT.'>';

        $xml .= '</'.RepositoryConst::OAIPMH_TAG_LIST_MATA_FORMT.'>';

        return $xml;
    }

    /**
     * output list sets
     *
     * sets = index information
     *
     */
    private function outputListSets()
    {
        // resumptionToken -> not must
        $this->checkResumptionToken();
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }

        $this->setPublicIndexQuery();

        // get index data
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $query = "SELECT idx.`index_id`, idx.`index_name`, idx.`index_name_english`, idx.`parent_index_id` ".
                 " FROM `". DATABASE_PREFIX ."repository_index` idx ".
                 " INNER JOIN (".$this->publicIndexQuery.") pub ON idx.index_id = pub.index_id ".
                 " WHERE idx.`is_delete` = ? AND idx.`public_state` = ?".
                 " AND idx.`pub_date` <= NOW()".
                 " ORDER BY idx.show_order, idx.index_id ASC;";
        $params = array();
        $params[] = 0;
        $params[] = 1;
        $result = $this->Db->execute($query, $params);
        if ($result === false)
        {
            $this->outputError();
            return false;
        }

        $index = array();
        $name = "";
        for($ii=0; $ii<count($result); $ii++)
        {
            $name = $result[$ii]['index_name_english'];
            if(strlen($name)==0)
            {
                $name = $result[$ii]['index_name'];
            }
            $node = array(  'id'=>$result[$ii]['index_id'],
                            'pid'=>$result[$ii]['parent_index_id'],
                            'name'=>$name);
            if(!isset($index[$node['pid']]))
            {
                $index[$node['pid']] = array();
            }
            array_push($index[$node['pid']], $node);
        }

        $xml = '';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_LIST_SETS.'>';
        $displayNum = 0;
        $skipNum = 0;
        $nextToken = 0;
        $xml .= $this->outputListSetsSet($index, '0', "", $skipNum, $displayNum);
        if ($displayNum >= self::MAX_ITEM)
        {
            $nextToken = $this->position + self::MAX_ITEM;
        }
        if ($nextToken > 0)
        {
            $resumptionToken = "////$nextToken";

            // get time at 1 hour later
            $expirationDate = $this->calcDate(1);
            // get time at next date
            $expirationDate = $this->getNextDay($expirationDate);
            $xml .= '<'.RepositoryConst::OAIPMH_TAG_RESUMP_TOKEN.' ';
            $xml .= repositoryConst::OAIPMH_ATTR_EXPRIRATION_DATE.'="'.$expirationDate.'">';
            $xml .= $resumptionToken;
            $xml .= '</'.RepositoryConst::OAIPMH_TAG_RESUMP_TOKEN.'>';
        }
        else
        {
            $xml .= '<'.RepositoryConst::OAIPMH_TAG_RESUMP_TOKEN.' />';
        }
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_LIST_SETS.'>';

        return $xml;
    }

    /**
     * Enter description here...
     *
     * @param array $index
     * @param string $indexKey
     * @param string $parentSpec
     * @param int $skipNum
     * @param int $displayNum
     */
    public function outputListSetsSet($index, $indexKey, $parentSpec, &$skipNum, &$displayNum)
    {
        $xml = '';
        for($nCnt=0; $nCnt<count($index[$indexKey]); $nCnt++){
            $setSpec = $this->forXmlChange(sprintf('%05d', $index[$indexKey][$nCnt]['id']));
            if($parentSpec != NULL && $parentSpec != ""){
                $setSpec = $parentSpec.":".$setSpec;
            }
            if($skipNum < $this->position){
                $skipNum++;
            } else {
                if ($displayNum >= self::MAX_ITEM) {
                    return $xml;
                }
                $setName = $index[$indexKey][$nCnt]['name'];
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_SET.'>';
                // setSpec
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_SET_SPEC.'>';
                $xml .= $this->forXmlChange($setSpec);
                $xml .= '</'.RepositoryConst::OAIPMH_TAG_SET_SPEC.'>';
                // setName
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_SET_NAME.'>';
                $xml .= $this->forXmlChange($setName);
                $xml .= '</'.RepositoryConst::OAIPMH_TAG_SET_NAME.'>';

                $xml .= '</'.RepositoryConst::OAIPMH_TAG_SET.'>';
                ++$displayNum;
            }
            if(isset($index[$index[$indexKey][$nCnt]['id']]))
            {
                $xml .= $this->outputListSetsSet($index, $index[$indexKey][$nCnt]['id'], $setSpec, $skipNum, $displayNum);
            }
        }
        return $xml;
    }

    /**
     * output list identifiers
     *
     * @param bool $metadata true:output header and metadata, false:output header only
     */
    private function outputMetadata($metadata=true)
    {
        // resumptionToken -> not must
        $this->checkResumptionToken();
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }
        // from, until -> not must
        $this->checkFromUntil();
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }

        // set -> not must
        $this->checkSet();
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }

        // metadataPrefix -> must
        $this->checkMetadataPrefix(true);
        if(strlen($this->errorCode) > 0)
        {
            return '';
        }

        // get item_id, item_no, datestamp list
        $itemList = $this->getItemList();
        if(count($itemList) == 0)
        {
            $this->errorCode = self::ERRORCODE_NO_RECORDS_MATCH;
            return '';
        }

        $xml = '';
        $xml .= '<'.$this->verb.'>';

        for($ii=0; $ii<count($itemList); $ii++)
        {
            $xmlMetadata = '';

            $itemId = $itemList[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID];
            $itemNo = $itemList[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO];
            $datestamp = $itemList[$ii][RepositoryConst::OAIPMH_TAG_DATESTAMP];
            // MOD OpenDepo 2014/06/02 S.arata --start--
            $pubflg = $this->getItemPublicFlg($itemId, $itemNo);
            // MOD OpenDepo 2014/06/02 S.arata --end--
            $xmlHead = $this->outputIdentifier($itemId, $itemNo, $datestamp, $pubflg);
            if($pubflg)
            {
                $log = '';
                $itemData = array();
                // if lido , get blank word
                if($this->metadataPrefix == RepositoryConst::OAIPMH_METADATA_PREFIX_LIDO)
                {
                    $ret = $this->getItemData($itemId, $itemNo, $itemData, $log, false, false);
                }
                else
                {
                    $ret = $this->getItemData($itemId, $itemNo, $itemData, $log, false, true);
                }
                if($ret)
                {
                    $ret = $this->getItemReference($itemId, $itemNo, $itemData, $log);
                }
                if($ret)
                {
                    $itemData['item'][0]['mod_date'] = $datestamp;
                    $xmlMetadata = $this->metadataClass->outputRecord($itemData);
                }
            }
            
            if($pubflg && strlen($xmlMetadata) == 0) {
                if(count($itemList) == 1){
                    $this->errorCode = self::ERRORCODE_ID_DOES_NOT_EXIST;
                }
                else {
                    continue;
                }
            }
            else if(!$pubflg && strlen($xmlHead) > 0)
            {
                // Deleted item.
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_RECORD.'>';
                $xml .= $xmlHead;
                $xml .= '</'.RepositoryConst::OAIPMH_TAG_RECORD.'>';
            }
            else if($pubflg && strlen($xmlHead) > 0 && strlen($xmlMetadata) > 0)
            {
                // ListIdentifiers
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_RECORD.'>';
                $xml .= $xmlHead;
                if($metadata)
                {
                    // ListMetadata & GetRecord
                    $xml .= '<'.RepositoryConst::OAIPMH_TAG_Metadata.'>';
                    $xml .= $xmlMetadata;
                    $xml .= '</'.RepositoryConst::OAIPMH_TAG_Metadata.'>';
                }
                $xml .= '</'.RepositoryConst::OAIPMH_TAG_RECORD.'>';
            }
        }

        // next contents...
        if($this->verb != self::VERB_GETRECORD)
        {
            $resumption = intval($this->position) + intval(self::MAX_ITEM);
            if($resumption < $this->maxItemList)
            {
                $xml .= $this->outputResumptionToken($resumption);
            }
        }
        $xml .= '</'.$this->verb.'>';

        return $xml;

    }

    /**
     * output resumption tooken.
     *
     * @param unknown_type $nextToken
     */
    private function outputResumptionToken($nextToken)
    {
        $xml = '';
        if ($nextToken > 0) {
            if ($this->from == null || strlen($this->from) == 0) {
                $this->from = '0001-01-01T00:00:00Z';
            }
            else
            {
                $this->from = $this->getStandardTime($this->from);
            }
            if ($this->until == null || strlen($this->until) == 0) {
                $this->until = '9999-12-31T23:59:59Z';
            }
            else
            {
                $this->until = $this->getStandardTime($this->until);
            }

            //$resumptionToken = "${request_from}/${request_until}/${request_set}/${request_metadataPrefix}/${next_resumptionToken}";
            $resumptionToken = $this->from.'/'.
                               $this->until.'/'.
                               $this->set.'/'.
                               $this->metadataPrefix.'/'.
                               $nextToken;
            // 1時間後となるようにする
            $expirationDate = $this->calcDate(1);
            $expirationDate = $this->getNextDay($expirationDate);
            $xml .= '<'.RepositoryConst::OAIPMH_TAG_RESUMP_TOKEN;
            $xml .= ' '.RepositoryConst::OAIPMH_ATTR_EXPRIRATION_DATE.'="'.$expirationDate.'">';
            $xml .= $resumptionToken;
            $xml .= '</'.RepositoryConst::OAIPMH_TAG_RESUMP_TOKEN.'>';
        }
        return $xml;
    }

    /**
     * output item information
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $datestamp
     * @param bool $pubflg
     */
    private function outputIdentifier($itemId, $itemNo, $datestamp, $pubflg)
    {
        // cehck item public status
        $xml = '';
        if($pubflg)
        {
            $xml .= '<'.RepositoryConst::OAIPMH_TAG_HEADER.'>';
        }
        else
        {
            $xml .= '<'.RepositoryConst::OAIPMH_TAG_HEADER_DEL.'>';
        }
        // identirfy
        $xml .= '<'.strtolower(RepositoryConst::OAIPMH_TAG_IDENTIFIER).'>';
        $xml .= 'oai:'.$_SERVER['HTTP_HOST'].':'.sprintf('%08d', $itemId);
        $xml .= '</'.strtolower(RepositoryConst::OAIPMH_TAG_IDENTIFIER).'>';
        //datestamp
        $xml .= '<'.strtolower(RepositoryConst::OAIPMH_TAG_DATESTAMP).'>';
        $xml .= $this->convertDateForISO($datestamp);
        $xml .= '</'.strtolower(RepositoryConst::OAIPMH_TAG_DATESTAMP).'>';

        //検索速度特化対応
        if(!_REPOSITORY_HIGH_SPEED && $pubflg)
        {
            $setSpec = $this->getSetSpec($itemId, $itemNo);
            for($ii=0; $ii<count($setSpec); $ii++)
            {
                $xml .= '<'.RepositoryConst::OAIPMH_TAG_SET_SPEC.'>';
                $xml .= sprintf("%05d", $setSpec[$ii]);
                $xml .= '</'.RepositoryConst::OAIPMH_TAG_SET_SPEC.'>';
            }
        }
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_HEADER.'>';

        return $xml;
    }

    /**
     * output error
     *
     */
    private function outputError()
    {
        $xml = '';
        $xml .= '<'.RepositoryConst::OAIPMH_TAG_ERROR.' code="'.$this->errorCode.'">';
        $xml .= $this->errorMessage[$this->errorCode];
        $xml .= '</'.RepositoryConst::OAIPMH_TAG_ERROR.'>';
        return $xml;
    }

    /**
     * output footer
     *
     */
    private function outputFooter()
    {
        $xml = '</'.RepositoryConst::OAIPMH_TAG_OAIPMH.'>';
        return $xml;
    }

    /**
     * get parameter value
     *
     * @param string $key
     */
    private function getParamValue($key)
    {
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = ?;";
        $param = array();
        $param[] = $key;
        $ret = $this->Db->execute($query, $param);
        if ($ret === false)
        {
            return '';
        }
        return $this->forXmlChange( $ret[0]['param_value'] );
    }

    /**
     * get OAI-PMH base url
     *
     * @return unknown
     */
    private function getOaiPmhBaseUrl()
    {
        $urlForConfirmation = BASE_URL."/oai?verb=".self::VERB_LISTMETADATAFORMATS;
        // get response
        $option = array(
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3,
        );
        $proxy = $this->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array(
                    "timeout" => "10",
                    "allowRedirects" => true,
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        $http = new HTTP_Request($urlForConfirmation, $option);
        // setting HTTP header
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        $response = $http->sendRequest();
        if (!PEAR::isError($response))
        {
            $resCode = $http->getResponseCode();        // get ResponseCode(200etc.)
            $resHeader = $http->getResponseHeader();    // get ResponseHeader
            $resBody = $http->getResponseBody();        // get ResponseBody
            $resCookies = $http->getResponseCookies();  // get Cookie
        }

        $oaiBaseUri = BASE_URL;
        if(substr($oaiBaseUri, -1, 1)!="/"){
            $oaiBaseUri .= "/";
        }
        $oaiBaseUri = preg_replace("/^https:/i","http:", $oaiBaseUri);

        $searchResult = preg_match("/\<OAI-PMH/", $resBody);
        if($searchResult === 1)
        {
            $oaiBaseUri .= 'oai';
        }
        else
        {
            $oaiBaseUri .= '?action=repository_oaipmh';
        }
        return $oaiBaseUri;
    }

    /**
     * get Local time to Greenwich Mean Time
     * Add care Greenwich Mean Time T.Koyasu
     *
     * @param $time ListRecords -> YYYY-MM-DDThh:mm:ssZ
     *              ListIdentifiers -> null
     * @return $time + $diff
     */
    private function getLocalTime($time)
    {
        // care verb=ListIdentifiers
        if(!isset($time))
        {
            return '';
        }

        // get difference from Greenwich Mean Time to Local time
        $param = array();
        $query = " SELECT conf_value ".
               " FROM `". DATABASE_PREFIX. "config` ".
               " WHERE conf_name=? ";
        $param[] = 'server_TZ';
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return '';
        }
        $diff = $result[0]['conf_value'];

        // to format(YYYY-MM-DD hh:mm:ss)
        $time = str_replace("T", " ", $time);
        $time = str_replace("Z", "", $time);

        // when diff < 0 or diff > 0 or diff == 0
        $minusFlg = 0;
        $operator = '+';
        if($diff < 0)
        {
            $diff = -1 * $diff;
            $operator = '-';
        }

        // add diff hour
        $param = array();
        $query = " SELECT ? ". $operator.
                " INTERVAL ? HOUR AS time ;";
        $param[] = $time;
        $param[] = $diff;
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return '';
        }
        // to format(YYYY-MM-DDThh:mm:ssZ)
        $treatedTime = str_replace(" ", "T", $result[0]['time']). "Z";

        return $treatedTime;
    }

    /**
     * get standard time
     *
     * @param string $time
     * @return string
     */
    function getStandardTime($time)
    {
        // care verb=ListIdentifiers
        if(!isset($time))
        {
            return;
        }

        // get difference from Greenwich Mean Time to Local time
        $param = array();
        $query = " SELECT conf_value ".
               " FROM `". DATABASE_PREFIX. "config` ".
               " WHERE conf_name=? ";
        $param[] = 'server_TZ';
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return;
        }
        $diff = $result[0]['conf_value'];

        // to format(YYYY-MM-DD hh:mm:ss)
        $time = str_replace("T", " ", $time);
        $time = str_replace("Z", "", $time);

        // when diff < 0 or diff > 0 or diff == 0
        $minusFlg = 0;
        $operator = '-';
        if($diff < 0)
        {
            $diff = -1 * $diff;
            $operator = '+';
        }

        // add diff hour
        $param = array();
        $query = " SELECT ? ". $operator.
                " INTERVAL ? HOUR AS time ;";
        $param[] = $time;
        $param[] = $diff;
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return;
        }
        // to format(YYYY-MM-DDThh:mm:ssZ)
        $treatedTime = str_replace(" ", "T", $result[0]['time']). "Z";
        return $treatedTime;
    }

    /**
     * date calc
     *
     * @param int $time calc date / hour
     */
    private function calcDate($time = 0)
    {
        $date = $this->TransStartDate;

        $date = preg_replace("/\.0+/", "", $date);
        $date = preg_replace("/[^0-9]+/", "", $date);

        $time = $time * 10000;
        $date = $date + $time;

        $date = $this->convertDateForISO($date);

        return $date;
    }

    /**
     * get access time + 1 day
     * for get expirationDate
     *
     * @param date $time
     * @return date(yy-mm-ddThh:mm:ssZ)
     */
    private function getNextDay($time)
    {
        // to format(YYYY-MM-DD hh:mm:ss)
        $time = str_replace("T", " ", $time);
        $time = str_replace("Z", "", $time);

        $param = array();
        $query = " SELECT ? + ".
                " INTERVAL ? DAY AS time ;";
        $param[] = $time;
        $param[] = 1;
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return '';
        }
        // to format(YYYY-MM-DDThh:mm:ssZ)
        $nextDay = str_replace(" ", "T", $result[0]['time']). "Z";
        return $nextDay;
    }

    /**
     * change date format
     *
     * @param string $time date
     * @return string
     */
    private function convertDateForISO($time)
    {
        if (preg_match("/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/", $time) == 1) {
            $tmp_date = $time;
        }
        else if(preg_match("/^[0-9]+$/", $time) == 1)
        {
            $yy = substr($time, 0, 4);
            $mm = substr($time, 4, 2);
            $dd = substr($time, 6, 2);
            $hh = substr($time, 8, 2);
            $ii = substr($time, 10, 2);
            $ss = substr($time, 12, 2);

            $DATE = new Date($yy.'-'.$mm.'-'.$dd.' '.$hh.':'.$ii.':'.$ss);
            $DATE->toUTC();
            $tmp_date = $DATE->getDate(DATE_FORMAT_ISO_EXTENDED);
        }
        else
        {
            $time = str_replace('-', ' ', $time);
            $time = str_replace(':', ' ', $time);
            $time = str_replace('.', ' ', $time);
            $time = str_replace('+', ' ', $time);
            $dateArray = explode(" ", $time, 7);
            $DATE = new Date($dateArray[0].'-'.$dateArray[1].'-'.$dateArray[2].' '.$dateArray[3].':'.$dateArray[4].':'.$dateArray[5]);
            $DATE->toUTC();
            $tmp_date = $DATE->getDate(DATE_FORMAT_ISO_EXTENDED);
        }
        return $tmp_date;
    }

    /**
     * set public index
     *
     */
    private function setPublicIndexQuery()
    {
        $this->publicIndexQuery = "";
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        // Add Open Depo 2013/12/03 R.Matsuura --start--
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $this->setConfigAuthority();
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $this->publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(true, $this->repository_admin_base, $this->repository_admin_room);
        // Add Open Depo 2013/12/03 R.Matsuura --end--
        // Mod OpenDepo 2014/01/31 S.Arata --end--
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
    }
    
    // ADD OpenDepo 2014/06/02 S.arata --start--
    /**
     * get public item flg
     *
     */
    private function getItemPublicFlg($itemId, $itemNo)
    {
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        $this->setConfigAuthority();
        $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $pubflg = $itemAuthorityManager->checkItemPublicFlg($itemId, $itemNo, $this->repository_admin_base, $this->repository_admin_room, true);
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
        return $pubflg;
    }
    // ADD OpenDepo 2014/06/02 S.arata --end--

    /**
     * get item list , where from, until, set
     *
     * @return array
     */
    private function getItemList()
    {
    	//検索特化対応
    	if(_REPOSITORY_HIGH_SPEED){
    		$cntQuery = "SELECT COUNT(item_id) AS cnt ";
    		$selQuery = "SELECT item_id, item_no, ".
    				"DATE_FORMAT( GREATEST( IF( DATE_FORMAT( shown_date, '%Y-%m-%d %H:%i:%s' ) < DATE_FORMAT(CURRENT_TIMESTAMP , '%Y-%m-%d %H:%i:%s' ) , ".
    				"shown_date, '0001-01-01T00:00:00Z' ) , mod_date ) , '%Y-%m-%d %H:%i:%s' ) AS datestamp ";
    		$query = "FROM ".DATABASE_PREFIX."repository_item ";
    	}else{
	        $list = array();
	        $params = array();

            // Check OAI-PMH Output Flag
            $query = "SELECT param_value ".
                     "FROM ".DATABASE_PREFIX."repository_parameter ".
                     "WHERE param_name = ? ".
                     "AND is_delete = ? ;";
            $params = array();
            $params[] = "output_oaipmh";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            if($result[0]['param_value'] == 0)
            {
                return array();
            }
            $params = array();
        
	        // Fix OAI-PMH datestamp. Bug fix 2013,No.64 Y.Nakao 2014/04/03 --start--
	        // アイテムの更新日、アイテムの公開日、アイテムタイプの更新日、インデックスの更新日、インデックスの公開日（親まで含める）のうち、
	        // 現在時刻より過去の日付かつ最も新しい日付を「datestamp」とする
	        $cntQuery = "SELECT COUNT(DISTINCT item_id) AS cnt ";
	        $selQuery = "SELECT item_id, item_no, datestamp ";
	        $query = " FROM ( ".
	                "   SELECT item.item_id, item.item_no, ".
	                "   MAX(".
	                "       DATE_FORMAT( ".
	                "           GREATEST( ".
	                "               IF( ".
	                "                   DATE_FORMAT(item.shown_date, '%Y-%m-%d %H:%i:%s') <  DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d %H:%i:%s'), ".
	                "                   item.shown_date, ".
	                "                   '".self::MIN_DATE."'".
	                "               ), ".
	                "               item.mod_date, ".
	                "               itemtype.mod_date, ".
	                "               IF( ".
	                "                   DATE_FORMAT(idxBrowAuth.pub_date, '%Y-%m-%d %H:%i:%s') <  DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d %H:%i:%s'), ".
	                "                   idxBrowAuth.pub_date, ".
	                "                   '".self::MIN_DATE."'".
	                "               ), ".
	                "               idxBrowAuth.mod_date ".
	                "           ), ".
	                "       '%Y-%m-%d %H:%i:%s' ".
	                "       )".
	                "   ) AS datestamp ".
	                "   FROM ".DATABASE_PREFIX."repository_item AS item, ".
	                "        ".DATABASE_PREFIX."repository_item_type AS itemtype, ".
	                "        ".DATABASE_PREFIX."repository_position_index AS pos, ".
	                "        {repository_index_browsing_authority} AS idxBrowAuth ".
	                "   WHERE item.item_type_id = itemtype.item_type_id ".
	                "   AND item.item_id = pos.item_id ".
	                "   AND item.item_no = pos.item_no ".
	                "   AND pos.index_id = idxBrowAuth.index_id ".
	                "   AND idxBrowAuth.harvest_public_state = 1 ".
	                "   GROUP BY item.item_id ".
	                ") AS t1 ";
	        // Fix OAI-PMH datestamp. Bug fix 2013,No.64 Y.Nakao 2014/04/03 --end--
    	}
        $addFilter = '';
        if(strlen($this->identifier) > 0)
        {
            $id = explode(":", $this->identifier);
            if(count($id) == 3)
            {
                if(strlen($addFilter) == 0)
                {
                    $addFilter .= 'WHERE ';
                }
                else
                {
                    $addFilter .= ' AND ';
                }

                $addFilter .= " item_id = ? ";
                $params[] = intval($id[2]);
            }
        }

        //検索特化対応
        if(_REPOSITORY_HIGH_SPEED){
        	if(strlen($this->from) > 0)
        	{
        		if(strlen($addFilter) == 0)
        		{
        			$addFilter .= 'WHERE ';
        		}
        		else
        		{
        			$addFilter .= ' AND ';
        		}
        		$addFilter .= " (mod_date >= ? OR ( shown_date >= ? AND shown_date < DATE_FORMAT(CURRENT_TIMESTAMP , '%Y-%m-%d %H:%i:%s' ) ) ) ";
        		$from = str_replace("T", " ", $this->from);
        		$from = str_replace("Z", "", $from);
        		$params[] = $from;
        	}

        	if(strlen($this->until) > 0)
        	{
        		if(strlen($addFilter) == 0)
        		{
        			$addFilter .= 'WHERE ';
        		}
        		else
        		{
        			$addFilter .= ' AND ';
        		}
        		$addFilter .= " (mod_date <= ? OR ( shown_date <= ? AND shown_date < DATE_FORMAT(CURRENT_TIMESTAMP , '%Y-%m-%d %H:%i:%s' ) ) ) ";
        		$until = str_replace("T", " ", $this->until);
        		$until = str_replace("Z", "", $until);
        		$params[] = $until;
        	}
        }else{
	        if(strlen($this->from) > 0)
	        {
	            if(strlen($addFilter) == 0)
	            {
	                $addFilter .= 'WHERE ';
	            }
	            else
	            {
	                $addFilter .= ' AND ';
	            }
	            $addFilter .= " datestamp >= ? ";
	            $from = str_replace("T", " ", $this->from);
	            $from = str_replace("Z", "", $from);
	            $params[] = $from;
	        }

	        if(strlen($this->until) > 0)
	        {
	            if(strlen($addFilter) == 0)
	            {
	                $addFilter .= 'WHERE ';
	            }
	            else
	            {
	                $addFilter .= ' AND ';
	            }
	            $addFilter .= " datestamp <= ? ";
	            $until = str_replace("T", " ", $this->until);
	            $until = str_replace("Z", "", $until);
	            $params[] = $until;
	        }
        }

        if(strlen($this->set) > 0)
        {
            if(strlen($addFilter) == 0)
            {
                $addFilter .= 'WHERE ';
            }
            else
            {
                $addFilter .= ' AND ';
            }
            // Mod Bug fix No.56 2014/03/24 T.Koyasu --start--
            $addFilter .= " item_id IN ( ".
                          "     SELECT item_id ".
                          "     FROM ". DATABASE_PREFIX. "repository_position_index ".
                          "     WHERE index_id = ? ".
                          " ) ";
            // Mod Bug fix No.56 2014/03/24 T.Koyasu --end--
            $params[] = preg_replace("/^0+/", "", $this->set);
        }

        if(strlen($addFilter) > 0)
        {
            $query .= $addFilter;
        }

        // Mod Bug fix No.56 2014/03/24 T.Koyasu --start--
        $query .= " ORDER BY item_id ASC ";
        // Mod Bug fix No.56 2014/03/24 T.Koyasu --end--

        // get total count
        $result = $this->Db->execute($cntQuery.$query, $params);
        if(!is_array($result) && !isset($result[0]['cnt']))
        {
            return array();
        }
        $this->maxItemList = $result[0]['cnt'];

        // get output item data list
        $query .= " LIMIT ?, ? ";
        $params[] = intval($this->position);
        $params[] = self::MAX_ITEM;

        // Mod Bug fix No.56 2014/03/24 T.Koyasu --start--
        $exeQuery = $selQuery. $query;

        $list = $this->Db->execute($exeQuery, $params);
        // Mod Bug fix No.56 2014/03/24 T.Koyasu --end--
        if(!is_array($list))
        {
            $list = array();
        }

        return $list;
    }

    /**
     * return setSpec
     * setSpec is item position index_id, implode ':'
     * index_id format => %05d
     *
     * @param int item_id
     * @param int item_no
     * @return string setSpec
     */
    private function getSetSpec($item_id, $item_no)
    {
        // set public index list
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        if(strlen($this->publicIndexQuery) == 0)
        {
            $this->setPublicIndexQuery();
        }
        $query = "SELECT pos.`index_id` ".
                 "FROM `". DATABASE_PREFIX ."repository_position_index` pos ".
                 "INNER JOIN (".$this->publicIndexQuery.") pub ON pos.index_id = pub.index_id ".
                 "WHERE pos.`is_delete` = ? AND pos.`item_id` = ? AND pos.`item_no` = ?";
        $params = array();
        $params[] = 0;  // 削除されていない
        $params[] = $item_id;
        $params[] = $item_no;
        $ret = $this->Db->execute($query, $params);
        if ($ret === false)
        {
            // not blong index
            return '';
        }

        $belong_index = array();
        for ($nCnt=0; $nCnt<count($ret); $nCnt++) {
                array_push($belong_index, $ret[$nCnt]['index_id']);
            }
        return $belong_index;
    }

}
?>