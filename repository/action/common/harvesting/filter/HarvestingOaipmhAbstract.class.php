<?php
// --------------------------------------------------------------------
//
// $Id: HarvestingOaipmhAbstract.class.php 35791 2014-05-16 04:10:29Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * Repository module OAI-PMH oai_lom harvesting class
 *
 * @package repository
 * @access  public
 */
class HarvestingOaipmhAbstract extends RepositoryAction
{
    // ---------------------------------------------
    // Const
    // ---------------------------------------------
    // Itemtype data
    const INPUT_TYPE_LINK = RepositoryConst::ITEM_ATTR_TYPE_LINK;
    const INPUT_TYPE_TEXT = RepositoryConst::ITEM_ATTR_TYPE_TEXT;
    const INPUT_TYPE_BIBLIOINFO = RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO;
    const INPUT_TYPE_SELECT = RepositoryConst::ITEM_ATTR_TYPE_SELECT;
    const INPUT_TYPE_TEXTAREA = RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA;
    const INPUT_TYPE_NAME = RepositoryConst::ITEM_ATTR_TYPE_NAME;
    const INPUT_TYPE_DATE = RepositoryConst::ITEM_ATTR_TYPE_DATE;
    const INPUT_TYPE_CHECKBOX = RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX;
    
    // Error / Warning message
    const MSG_ER_GET_TITLE = "repository_harvesting_error_get_title";
    const MSG_WN_MISS_LANGAGE = "repository_harvesting_warning_miss_language";
    
    // Log status
    const LOG_STATUS_OK = RepositoryConst::HARVESTING_LOG_STATUS_OK;
    const LOG_STATUS_WARNING = RepositoryConst::HARVESTING_LOG_STATUS_WARNING;
    const LOG_STATUS_ERROR = RepositoryConst::HARVESTING_LOG_STATUS_ERROR;
    
    // Metadata array for ItemRegister
    const KEY_IR_BASIC = "irBasic";
    const KEY_IR_METADATA = "irMetadata";
    const KEY_ITEM_ID = "item_id";
    const KEY_ITEM_NO = "item_no";
    const KEY_ITEM_TYPE_ID = "item_type_id";
    const KEY_TITLE = "title";
    const KEY_TITLE_EN = "title_english";
    const KEY_LANGUAGE = "language";
    const KEY_PUB_YEAR = "pub_year";
    const KEY_PUB_MONTH = "pub_month";
    const KEY_PUB_DAY = "pub_day";
    const KEY_SEARCH_KEY = "serch_key";
    const KEY_SEARCH_KEY_EN = "serch_key_english";
    const KEY_ATTR_ID = "attribute_id";
    const KEY_ATTR_NO = "attribute_no";
    const KEY_INPUT_TYPE = "input_type";
    const KEY_ATTR_VALUE = "attribute_value";
    const KEY_FAMILY = "family";
    const KEY_NAME = "name";
    const KEY_FAMILY_RUBY = "family_ruby";
    const KEY_NAME_RUBY = "name_ruby";
    const KEY_EMAIL = "e_mail_address";
    const KEY_AUTHOR_ID = "author_id";
    const KEY_NAME_NO = "personal_name_no";
    const KEY_BIBLIO_NAME = "biblio_name";
    const KEY_BIBLIO_NAME_EN = "biblio_name_english";
    const KEY_VOLUME = "volume";
    const KEY_ISSUE = "issue";
    const KEY_SPAGE = "start_page";
    const KEY_EPAGE = "end_page";
    const KEY_DATE_OF_ISSUED = "date_of_issued";
    
    // Others
    const IDENTIFIER_DELIMITER = ":";
    const TAXON_DELIMITER = ":";
    const NAME_DELIMITER = " ";
    const ITEM_LANG_JA = RepositoryConst::ITEM_LANG_JA;
    const ITEM_LANG_EN = RepositoryConst::ITEM_LANG_EN;
    const DEFAULT_LANGUAGE = RepositoryConst::ITEM_LANG_JA;
    
    // Database
    const DB_ITEM = RepositoryConst::DBTABLE_REPOSITORY_ITEM;
    const DB_ITEM_ITEM_ID = RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID;
    const DB_ITEM_ITEM_NO = RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO;
    const DB_ITEM_IS_DELETE = RepositoryConst::DBCOL_COMMON_IS_DELETE;
    const DB_ITEM_ATTR = RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR;
    const DB_ITEM_ATTR_ITEM_ID = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_ID;
    const DB_ITEM_ATTR_ITEM_NO = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_NO;
    const DB_ITEM_ATTR_ATTR_ID = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_ID;
    const DB_ITEM_ATTR_ATTR_NO = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_NO;
    const DB_ITEM_ATTR_ATTR_VAL = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE;
    const DB_ITEM_ATTR_ITEM_TYPE_ID = RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ITEM_TYPE_ID;
    const DB_PERSONAL_NAME = RepositoryConst::DBTABLE_REPOSITORY_PERSONAL_NAME;
    const DB_PERSONAL_NAME_ITEM_ID = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_ID;
    const DB_PERSONAL_NAME_ITEM_NO = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_NO;
    const DB_PERSONAL_NAME_ATTR_ID = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ATTRIBUTE_ID;
    const DB_PERSONAL_NAME_NAME_NO = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_PERSONAL_NAME_NO;
    const DB_PERSONAL_NAME_FAMILY = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY;
    const DB_PERSONAL_NAME_NAME = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME;
    const DB_PERSONAL_NAME_FAMILY_RUBY = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY_RUBY;
    const DB_PERSONAL_NAME_NAME_RUBY = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME_RUBY;
    const DB_PERSONAL_NAME_EMAIL_ADDRES = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_E_MAIL_ADDRESS;
    const DB_PERSONAL_NAME_ITEM_TYPE_ID = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_ITEM_TYPE_ID;
    const DB_PERSONAL_NAME_AUTHOR_ID = RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_AUTHOR_ID;
    
    private $itemtype_id = 0;
    private $attr_id_repository_id = 0;
    private $attr_id_identifier = 0;
    private $attr_id_datestamp = 0;
    private $max_attr_id = 0;
    
    // ---------------------------------------------
    // Constructor
    // ---------------------------------------------
    /**
     * Constructor
     *
     * @return HarvestingOaipmh
     */
    public function __construct($Session, $Db){
        $this->Session = $Session;
        $this->Db = $Db;
    }
    
    /**
     * set itemtype id
     *
     * @return HarvestingOaipmh
     */
    protected function setItemtypeId($itemtypeId){
        $this->itemtype_id = $itemtypeId;
    }
    
    /**
     * set param
     *
     * @return HarvestingOaipmh
     */
    protected function setRequiredParam($attr_repositoryId, $attr_identifier, $attr_datestamp, $max_attr_id){
        $this->attr_id_repository_id = $attr_repositoryId;
        $this->attr_id_identifier = $attr_identifier;
        $this->attr_id_datestamp = $attr_datestamp;
        $this->max_attr_id = $max_attr_id;
    }
    
    // ---------------------------------------------
    // Private method
    // ---------------------------------------------
    
    /**
     * Explode name string
     *
     * @param $str
     * @return array
     */
    protected function explodeNameStr($str)
    {
        $family = "";
        $name = "";
        
        $str = str_replace("　", " ", $str);
        $str = preg_replace("/ +/", " ", $str);
        $str = preg_replace("/ +/", " ", $str);
        
        $nameArray = explode(self::NAME_DELIMITER, $str, 2);
        $family = $nameArray[0];
        if(isset($nameArray[1]))
        {
            $name = $nameArray[1];
        }
        
        return array(self::KEY_FAMILY => $family, self::KEY_NAME => $name);
    }
    
    /**
     * Init irBasic array
     *
     * @return array
     */
    protected function initIrBasic()
    {
        $irBasic = array(
                self::KEY_ITEM_ID => "",
                self::KEY_ITEM_NO => "",
                self::KEY_ITEM_TYPE_ID => $this->itemtype_id,
                self::KEY_TITLE => "",
                self::KEY_TITLE_EN => "",
                self::KEY_LANGUAGE => "",
                self::KEY_PUB_YEAR => "",
                self::KEY_PUB_MONTH => "",
                self::KEY_PUB_DAY => "",
                self::KEY_SEARCH_KEY => "",
                self::KEY_SEARCH_KEY_EN => ""
            );
        return $irBasic;
    }
    
    /**
     * Create irMetadata array
     *
     * @return array
     */
    protected function createIrMetadata($attrId, $inputType, $data)
    {
        $irMetadata = array();
        switch($inputType)
        {
            case self::INPUT_TYPE_LINK:
            case self::INPUT_TYPE_TEXT:
            case self::INPUT_TYPE_SELECT:
            case self::INPUT_TYPE_TEXTAREA:
            case self::INPUT_TYPE_CHECKBOX:
            case self::INPUT_TYPE_DATE:
                $irMetadata = $this->initIrMetadata();
                $irMetadata[self::KEY_ATTR_ID] = $attrId;
                $irMetadata[self::KEY_ATTR_NO] = $this->cntMetadata[$attrId];
                $irMetadata[self::KEY_INPUT_TYPE] = $inputType;
                if(isset($data[self::KEY_ATTR_VALUE]))
                    $irMetadata[self::KEY_ATTR_VALUE] = $data[self::KEY_ATTR_VALUE];
                break;
            case self::INPUT_TYPE_BIBLIOINFO:
                $irMetadata = $this->initIrBiblioMetadata();
                $irMetadata[self::KEY_ATTR_ID] = $attrId;
                $irMetadata[self::KEY_BIBLIO_NO] = $this->cntMetadata[$attrId];
                if(isset($data[self::KEY_BIBLIO_NAME]))
                    $irMetadata[self::KEY_BIBLIO_NAME] = $data[self::KEY_BIBLIO_NAME];
                if(isset($data[self::KEY_BIBLIO_NAME_EN]))
                    $irMetadata[self::KEY_BIBLIO_NAME_EN] = $data[self::KEY_BIBLIO_NAME_EN];
                if(isset($data[self::KEY_VOLUME]))
                    $irMetadata[self::KEY_VOLUME] = $data[self::KEY_VOLUME];
                if(isset($data[self::KEY_ISSUE]))
                    $irMetadata[self::KEY_ISSUE] = $data[self::KEY_ISSUE];
                if(isset($data[self::KEY_SPAGE]))
                    $irMetadata[self::KEY_SPAGE] = $data[self::KEY_SPAGE];
                if(isset($data[self::KEY_EPAGE]))
                    $irMetadata[self::KEY_EPAGE] = $data[self::KEY_EPAGE];
                if(isset($data[self::KEY_DATE_OF_ISSUED]))
                    $irMetadata[self::KEY_DATE_OF_ISSUED] = $data[self::KEY_DATE_OF_ISSUED];
                break;
            case self::INPUT_TYPE_NAME:
                $irMetadata = $this->initIrNameMetadata();
                $irMetadata[self::KEY_ATTR_ID] = $attrId;
                $irMetadata[self::KEY_NAME_NO] = $this->cntMetadata[$attrId];
                if(isset($data[self::KEY_FAMILY]))
                    $irMetadata[self::KEY_FAMILY] = $data[self::KEY_FAMILY];
                if(isset($data[self::KEY_NAME]))
                    $irMetadata[self::KEY_NAME] = $data[self::KEY_NAME];
                if(isset($data[self::KEY_FAMILY_RUBY]))
                    $irMetadata[self::KEY_FAMILY_RUBY] = $data[self::KEY_FAMILY_RUBY];
                if(isset($data[self::KEY_NAME_RUBY]))
                    $irMetadata[self::KEY_NAME_RUBY] = $data[self::KEY_NAME_RUBY];
                if(isset($data[self::KEY_EMAIL]))
                    $irMetadata[self::KEY_EMAIL] = $data[self::KEY_EMAIL];
                if(isset($data[self::KEY_AUTHOR_ID]))
                    $irMetadata[self::KEY_AUTHOR_ID] = $data[self::KEY_AUTHOR_ID];
                if(isset($data[self::KEY_LANGUAGE]))
                    $irMetadata[self::KEY_LANGUAGE] = $data[self::KEY_LANGUAGE];
                break;
            default:
                break;
        }
        return $irMetadata;
    }
    
    /**
     * Init irMetadata array
     *
     * @return array
     */
    private function initIrMetadata()
    {
        $irMetadata = array(
                self::KEY_ITEM_ID => "",
                self::KEY_ITEM_NO => "",
                self::KEY_ITEM_TYPE_ID => $this->itemtype_id,
                self::KEY_ATTR_ID => "",
                self::KEY_ATTR_NO => "",
                self::KEY_INPUT_TYPE => "",
                self::KEY_ATTR_VALUE => ""
            );
        return $irMetadata;
    }
    
    /**
     * Init irMetadata array for name
     *
     * @return array
     */
    private function initIrNameMetadata()
    {
        $irMetadata = array(
                self::KEY_ITEM_ID => "",
                self::KEY_ITEM_NO => "",
                self::KEY_ITEM_TYPE_ID => $this->itemtype_id,
                self::KEY_ATTR_ID => "",
                self::KEY_FAMILY => "",
                self::KEY_NAME => "",
                self::KEY_FAMILY_RUBY => "",
                self::KEY_NAME_RUBY => "",
                self::KEY_EMAIL => "",
                self::KEY_AUTHOR_ID => "",
                self::KEY_LANGUAGE => "",
                self::KEY_INPUT_TYPE => self::INPUT_TYPE_NAME,
                self::KEY_NAME_NO => ""
            );
        return $irMetadata;
    }
    
    /**
     * Init irMetadata array for biblio_info
     *
     * @return array
     */
    private function initIrBiblioMetadata()
    {
        $irMetadata = array(
                self::KEY_ITEM_ID => "",
                self::KEY_ITEM_NO => "",
                self::KEY_ITEM_TYPE_ID => $this->itemtype_id,
                self::KEY_ATTR_ID => "",
                self::KEY_BIBLIO_NAME => "",
                self::KEY_BIBLIO_NAME_EN => "",
                self::KEY_VOLUME => "",
                self::KEY_ISSUE => "",
                self::KEY_SPAGE => "",
                self::KEY_EPAGE => "",
                self::KEY_DATE_OF_ISSUED => "",
                self::KEY_INPUT_TYPE => self::INPUT_TYPE_BIBLIOINFO,
                self::KEY_BIBLIO_NO => ""
            );
        return $irMetadata;
    }
    
    /**
     * Set require metadata to array
     *
     * @param int $repositoryId
     * @param string $metadata
     * @return array
     */
    protected function setRequireMetadataToArray($repositoryId, &$metadata)
    {
        // repositoryId
        $attrId = $this->attr_id_repository_id;
        $this->cntMetadata[$attrId]++;
        $data = array(self::KEY_ATTR_VALUE => $repositoryId);
        $irMetadata = $this->createIrMetadata($attrId, self::INPUT_TYPE_TEXT, $data);
        if(count($irMetadata)>0)
        {
            array_push($metadata[self::KEY_IR_METADATA], $irMetadata);
        }
        
        // identifier
        $attrId = $this->attr_id_identifier;
        $this->cntMetadata[$attrId]++;
        $data = array(self::KEY_ATTR_VALUE => $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"]);
        $irMetadata = $this->createIrMetadata($attrId, self::INPUT_TYPE_TEXT, $data);
        if(count($irMetadata)>0)
        {
            array_push($metadata[self::KEY_IR_METADATA], $irMetadata);
        }
        
        // datestamp
        $attrId = $this->attr_id_datestamp;
        $this->cntMetadata[$attrId]++;
        $data = array(self::KEY_ATTR_VALUE => $metadata[RepositoryConst::HARVESTING_COL_DATESTAMP][0]["value"]);
        $irMetadata = $this->createIrMetadata($attrId, self::INPUT_TYPE_TEXT, $data);
        if(count($irMetadata)>0)
        {
            array_push($metadata[self::KEY_IR_METADATA], $irMetadata);
        }
    }
    
    // ---------------------------------------------
    // Public method
    // ---------------------------------------------
    /**
     * Set TransStartDate
     *
     * @param string $transStartDate
     */
    public function setTransStartDate($transStartDate)
    {
        $this->TransStartDate = $transStartDate;
    }
    
    /**
     * Get metadata array from ListRecords(record)
     *
     * @param string $metadataXml
     * @param int $repositoryId
     * @param array $metadata metadata[TAGNAME][NUM]["value"]
     *                                              ["attribute"][KEY]
     * @return bool
     */
    public function setMetadataFromListRecords($metadataXml, $repositoryId, &$metadata)
    {
        // over ride
    }
    
    /**
     * Check metadata
     *
     * @param array $metadata
     * @param int $logStatus
     * @param array $logMsg
     * @return bool
     */
    public function checkMetadata($metadata, &$logStatus, &$logMsg)
    {
        // title
        $title = $metadata[self::KEY_IR_BASIC][self::KEY_TITLE];
        $titleEn = $metadata[self::KEY_IR_BASIC][self::KEY_TITLE_EN];
        if(strlen($title)==0 && strlen($titleEn)==0)
        {
            array_push($logMsg, self::MSG_ER_GET_TITLE);
            $logStatus = self::LOG_STATUS_ERROR;
            return false;
        }
        
        // language
        $language = RepositoryOutputFilter::language($metadata[self::KEY_IR_BASIC][self::KEY_LANGUAGE]);
        if(strlen($language)==0)
        {
            array_push($logMsg, self::MSG_WN_MISS_LANGAGE);
            $logStatus = self::LOG_STATUS_WARNING;
        }
        
        return true;
    }
    
    /**
     * Check item exists
     *
     * @param array $metadata
     * @param int $repositoryId
     * @param int $itemId
     * @param int $itemNo
     * @param string $datestamp
     * @param string $isDelete
     * @return bool true: Exists / false: No exists
     */
    public function isItemExists($metadata, $repositoryId, &$itemId, &$itemNo, &$datestamp, &$isDelete)
    {
        // Init
        $itemId = "";
        $itemNo = "";
        $datestamp = "";
        $isDelete = "";
        $query = "SELECT DISTINCT ".self::DB_ITEM_ATTR_ITEM_ID.", ".self::DB_ITEM_ATTR_ITEM_NO." ".
                 "FROM ".DATABASE_PREFIX.self::DB_ITEM_ATTR." ".
                 "WHERE ".self::DB_ITEM_ATTR_ATTR_ID." = ? ".
                 "AND ".self::DB_ITEM_ATTR_ATTR_NO." = 1 ".
                 "AND ".self::DB_ITEM_ATTR_ATTR_VAL." = ? ".
                 "AND ".self::DB_ITEM_ATTR_ITEM_ID." IN (".
                 "  SELECT DISTINCT ".self::DB_ITEM_ATTR_ITEM_ID." ".
                 "  FROM ".DATABASE_PREFIX.self::DB_ITEM_ATTR." ".
                 "  WHERE ".self::DB_ITEM_ATTR_ATTR_ID." = ? ".
                 "  AND ".self::DB_ITEM_ATTR_ATTR_NO." = 1 ".
                 "  AND ".self::DB_ITEM_ATTR_ATTR_VAL." = ? ".
                 "  AND ".self::DB_ITEM_ATTR_ITEM_TYPE_ID." = ?);";
        $params = array();
        $params[] = $this->attr_id_identifier; //attribute_id / Identifier
        $params[] = $metadata[RepositoryConst::HARVESTING_COL_HEADERIDENTIFIER][0]["value"];  //attribute_value / Itentifier
        $params[] = $this->attr_id_repository_id;    //attribute_id / repositoryId
        $params[] = $repositoryId;    //attribute_value / repositoryId
        $params[] = $this->itemtype_id;  //item_type_id
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
            $itemId = $result[0][self::DB_ITEM_ATTR_ITEM_ID];
            $itemNo = $result[0][self::DB_ITEM_ATTR_ITEM_NO];
            
            // Exists
            $query = "SELECT ".self::DB_ITEM_ATTR_ATTR_VAL." ".
                     "FROM ".DATABASE_PREFIX.self::DB_ITEM_ATTR." ".
                     "WHERE ".self::DB_ITEM_ATTR_ITEM_ID." = ? ".
                     "AND ".self::DB_ITEM_ATTR_ITEM_NO." = ? ".
                     "AND ".self::DB_ITEM_ATTR_ATTR_ID." = ? ".
                     "AND ".self::DB_ITEM_ATTR_ITEM_NO." = 1 ;";
            $params = array();
            $params[] = $itemId;
            $params[] = $itemNo;
            $params[] = $this->attr_id_datestamp; //attribute_id / datestamp
            $result = $this->Db->execute($query, $params);
            if(count($result) > 0)
            {
                $datestamp = $result[0][self::DB_ITEM_ATTR_ATTR_VAL];
            }
            
            // Get repository_item table's is_delete
            $query = "SELECT ".self::DB_ITEM_IS_DELETE." ".
                     "FROM ".DATABASE_PREFIX.self::DB_ITEM." ".
                     "WHERE ".self::DB_ITEM_ITEM_ID." = ? ".
                     "AND ".self::DB_ITEM_ITEM_NO." = ? ;";
            $params = array();
            $params[] = $itemId;
            $params[] = $itemNo;
            $result = $this->Db->execute($query, $params);
            if(count($result) > 0)
            {
                $isDelete = intval($result[0][self::DB_ITEM_IS_DELETE]);
            }
            
            return true;
        }
    }
    
    /**
     * Set item_id and item_no to irBasic and irMetadata
     *
     * @param int $itemId
     * @param int $itemNo
     * @param array $metadata
     * @return bool
     */
    public function setItemIdForIrData($itemId, $itemNo, &$metadata, &$irBasic, &$irMetadataArray)
    {
        // Check param
        $itemId = intval($itemId);
        $itemNo = intval($itemNo);
        if($itemId<1 || $itemNo<1)
        {
            return false;
        }
        
        // Set item_id and item_no
        $metadata[self::KEY_IR_BASIC][self::KEY_ITEM_ID] = $itemId;
        $metadata[self::KEY_IR_BASIC][self::KEY_ITEM_NO] = $itemNo;
        if(strlen($metadata[self::KEY_IR_BASIC][self::KEY_LANGUAGE])==0)
        {
            $metadata[self::KEY_IR_BASIC][self::KEY_LANGUAGE] = self::DEFAULT_LANGUAGE;
        }
        
        for($ii=0; $ii<count($metadata[self::KEY_IR_METADATA]); $ii++)
        {
            $metadata[self::KEY_IR_METADATA][$ii][self::KEY_ITEM_ID] = $itemId;
            $metadata[self::KEY_IR_METADATA][$ii][self::KEY_ITEM_NO] = $itemNo;
            
            if($metadata[self::KEY_IR_METADATA][$ii][self::KEY_INPUT_TYPE] == self::INPUT_TYPE_NAME)
            {
                // Set author ID
                $attrId = $metadata[self::KEY_IR_METADATA][$ii][self::KEY_ATTR_ID];
                $nameNo = $metadata[self::KEY_IR_METADATA][$ii][self::KEY_NAME_NO];
                $family = $metadata[self::KEY_IR_METADATA][$ii][self::KEY_FAMILY];
                $name = $metadata[self::KEY_IR_METADATA][$ii][self::KEY_NAME];
                $authorId = $this->getAuthorIdForIrMetadata($itemId, $itemNo, $attrId, $nameNo, $family, $name);
                $metadata[self::KEY_IR_METADATA][$ii][self::KEY_AUTHOR_ID] = $authorId;
            }
        }
        
        $irBasic = $metadata[self::KEY_IR_BASIC];
        $irMetadataArray = $metadata[self::KEY_IR_METADATA];
        
        // Set additional metadata
        $this->setAdditionalMetadata($itemId, $itemNo, $irMetadataArray);
        
        return true;
    }
    
    /**
     * makeNameMetadataArray
     * 
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $nameNo
     * @return int
     */
    private function getAuthorIdForIrMetadata($itemId, $itemNo, $attrId, $nameNo, $family, $name)
    {
        // Get author ID from database
        $query = "SELECT ".self::DB_PERSONAL_NAME_FAMILY.", ".self::DB_PERSONAL_NAME_NAME.", ".self::DB_PERSONAL_NAME_AUTHOR_ID." ".
                 "FROM ".DATABASE_PREFIX.self::DB_PERSONAL_NAME." ".
                 "WHERE ".self::DB_PERSONAL_NAME_ITEM_ID." = ? ".
                 "AND ".self::DB_PERSONAL_NAME_ITEM_NO." = ? ".
                 "AND ".self::DB_PERSONAL_NAME_ATTR_ID." = ? ".
                 "AND ".self::DB_PERSONAL_NAME_NAME_NO." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $params[] = $nameNo;
        $result = $this->Db->execute($query, $params);
        if($result===false || count($result)==0)
        {
            return 0;
        }
        
        // Check author ID
        $authorId = 0;
        if($result[0][self::DB_PERSONAL_NAME_FAMILY] == $family && $result[0][self::DB_PERSONAL_NAME_NAME] == $name)
        {
            $authorId = intval($result[0][self::DB_PERSONAL_NAME_AUTHOR_ID]);
        }
        
        return $authorId;
    }
    
    /**
     * Set additional metadata
     * 
     * @param int $itemId
     * @param int $itemNo
     * @param array &$metadataArray
     * @return int
     */
    private function setAdditionalMetadata($itemId, $itemNo, &$metadataArray)
    {
        $itemTypeId = $this->itemtype_id;
        $startAddAttrId = $this->max_attr_id;
        
        // Get itemAttrType
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." >= ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." ASC;";
        $params = array();
        $params[] = $itemTypeId;
        $params[] = $startAddAttrId;
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
}

?>
