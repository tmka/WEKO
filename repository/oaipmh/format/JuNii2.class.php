<?php
// --------------------------------------------------------------------
//
// $Id: JuNii2.class.php 49321 2015-03-03 12:15:32Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/FormatAbstract.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class Repository_Oaipmh_JuNii2 extends Repository_Oaipmh_FormatAbstract
{
    /**
     * miinOccurs, maxOccurs check array
     *
     * @var array
     */
    private $occurs = array();
    /**
     * item language string
     * 
     * @var string
     */
    private $strItemLanguage = null;
    /**
     * for instance of RepositoryHandleManager class
     * 
     * @var object
     */
    private $repositoryHandleManager = null;
    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        parent::__construct($session, $db);
    }
    
    private function initialize()
    {
        $this->occurs = array(  RepositoryConst::JUNII2_TITLE=>1,
                                RepositoryConst::JUNII2_DATE=>1,
                                RepositoryConst::JUNII2_NIITYPE=>1,
                                RepositoryConst::JUNII2_URI=>1,
                                RepositoryConst::JUNII2_JTITLE=>1,
                                RepositoryConst::JUNII2_VOLUME=>1,
                                RepositoryConst::JUNII2_ISSUE=>1,
                                RepositoryConst::JUNII2_SPAGE=>1,
                                RepositoryConst::JUNII2_EPAGE=>1,
                                RepositoryConst::JUNII2_DATE_OF_ISSUED=>1,
                                RepositoryConst::JUNII2_PMID=>1,
                                RepositoryConst::JUNII2_DOI=>1,
                                RepositoryConst::JUNII2_TEXTVERSION=>1,
                                // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
                                RepositoryConst::JUNII2_SELFDOI=>1,
                                RepositoryConst::JUNII2_SELFDOI_JALC=>1,
                                RepositoryConst::JUNII2_SELFDOI_CROSSREF=>1,
                                RepositoryConst::JUNII2_SELFDOI_DATACITE=>1,
                                RepositoryConst::JUNII2_NAID=>1,
                                RepositoryConst::JUNII2_ICHUSHI=>1,
                                RepositoryConst::JUNII2_GRANTID=>1,
                                RepositoryConst::JUNII2_DATEOFGRANTED=>1,
                                RepositoryConst::JUNII2_DEGREENAME=>1,
                                RepositoryConst::JUNII2_GRANTOR=>1);
                                // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
    }
    
    /**
     * output OAI-PMH metadata Tag format DublinCore
     *
     * @param array $itemData $this->getItemData return
     * @return string xml
     */
    public function outputRecord($itemData)
    {
        if( !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]) || 
            !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE]) )
          //  基本情報以外のメタデータが存在しない場合に判定に入ってしまうことを防ぐためコメントアウト
          //  !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE]) || 
          //  !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]))
        {
            return '';
        }
        
        // initialize
        $this->initialize();
        $xml = '';
        
        // header output
        $xml .= $this->outputHeader();
        // base info output
        $xml .= $this->outputBasicData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0]);

        // Add new prefix 2013/12/26 T.Ichikawa --start--
        $result = $this->outputSelfDOI($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID],
                                       $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO]);
        if($result === false) {
            return '';
        }
        $xml .= $result;
        // Add new prefix 2013/12/26 T.Ichikawa --end--
        
        // NIIType output
        $niiType = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_MAPPING_INFO];
        
        if( is_null($niiType) )
        {
            return '';
        }
        
        $xml .= $this->outputNiiType($niiType);
        
        // metadata output
        if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]))
        {
            $xml .= $this->outputMetadta($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]);
        }
        
        // item link output
        if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]))
        {
            $xml .= $this->outputReference($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]);
        }
        
        // date tag check
        if($this->occurs[RepositoryConst::JUNII2_DATE] > 0)
        {
            // YYYY-MM-DD or YYYY-MM or YYYY only
            $insDate = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_COMMON_INS_DATE];
            $value = explode(" ", $insDate);
            $xml .= $this->outputDate($value[0]);
        }
        
        // when return false, metadata occurs failed.
        if(!$this->occursCheck())
        {
            return '';
        }
        
        // footer output
        $xml .= $this->outputFooter();
        
        return $xml;
    }
    
    /**
     * header ooutput
     *
     * @return string
     */
    private function outputHeader()
    {
        $xml = '';
        $xml .= '<'.RepositoryConst::JUNII2_START;
        $xml .= ' xsi:schemaLocation="http://irdb.nii.ac.jp/oai ';
        $xml .= ' http://irdb.nii.ac.jp/oai/junii2-3-1.xsd">'.self::LF;
        return $xml;
    }
    
    /**
     * item basic data output
     *
     * @param array $baseData
     * @return string
     */
    private function outputBasicData($baseData)
    {
        $xml = '';
        // language. 言語
        $language = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_LANGUAGE];
        $language = RepositoryOutputFilter::language($language);
        
        // title. タイトル
        $title = '';
        $alternative = '';
        
        // タイトル言語
        $title_lang = "";
        $alternative_lang = "";
        
        if($language == RepositoryConst::ITEM_LANG_JA)
        {
            // japanese. 日本語
            $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
            if(strlen($title) == 0)
            {
                $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                $title_lang = RepositoryConst::ITEM_LANG_EN;
            }
            else
            {
                $alternative = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                $title_lang = RepositoryConst::ITEM_LANG_JA;
                $alternative_lang = RepositoryConst::ITEM_LANG_EN;
            }
        }
        else
        {
            // not japanese. 洋語
            $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
            if(strlen($title) == 0)
            {
                $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                $title_lang = RepositoryConst::ITEM_LANG_JA;
            }
            else
            {
                $alternative = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                $title_lang = RepositoryConst::ITEM_LANG_EN;
                $alternative_lang = RepositoryConst::ITEM_LANG_JA;
            }
        }
        $xml .= $this->outputTitle($title, $title_lang);
        $xml .= $this->outputAlternative($alternative, $alternative_lang);
        
        // language. 言語
        $xml .= $this->outputLanguage($language);
        
        // keyword. キーワード
        $keyword = explode("|", $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY]."|".$baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
        for($ii=0; $ii<count($keyword); $ii++)
        {
            $xml .= $this->outputSubject($keyword[$ii]);
        }
        
        // Add new prefix 2013/12/26 T.Ichikawa --start--
        $this->getRepositoryHandleManager();
        // URL
        $url = $this->repositoryHandleManager->createUriForJuNii2($baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID],
                                                                  $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO]);
        $xml .= $this->outputURI($url);
        // Add new prefix 2013/12/26 T.Ichikawa --end--
        
        return $xml;
    }
    
    /**
     * output metadata
     *
     * @param array $itemAttrType mapping info. マッピング情報
     * @param array $itemAttr metadata ingo. メタデータ情報
     * @return string
     */
    private function outputMetadta($itemAttrType, $itemAttr)
    {
        $xml = '';
        
        $value = '';
        for($ii=0; $ii<count($itemAttrType); $ii++)
        {
            if($itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1)
            {
                // hidden metadata
                continue;
            }
            
            // Add data filter parameter Y.Nakao 2013/05/17 --start--
            if($this->dataFilter == self::DATA_FILTER_SIMPLE && $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LIST_VIEW_ENABLE] == 0)
            {
                // when data fileter is "simple", output list_view_enable=1 metadata.
                continue;
            }
            // Add data filter parameter Y.Nakao 2013/05/17 --end--
            
            // get value par input type. 入力タイプ別に出力値を求める
            $inputType = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
            $lang = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
            $lang = RepositoryOutputFilter::language($lang);
            
            // get mapping info. マッピング情報取得
            $junii2Map = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_JUNII2_MAPPING];
            if(strlen($junii2Map)==0 && $inputType != "file" && $inputType != "file_price")
            {
                // when is not mapping info, not output. マッピング情報がなければスルー
                continue;
            }
            
            for($jj=0; $jj<count($itemAttr[$ii]); $jj++)
            {
                $value = RepositoryOutputFilter::attributeValue($itemAttrType[$ii], $itemAttr[$ii][$jj], 2, 2);
                if(strlen($value) > 0)
                {
                    if($inputType == RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO)
                    {
                        // jtitle,volume,issue,spage,epage,dateofissued
                        $mapping = explode(",", $junii2Map);
                        // $jtitle = $jtitle_en||$volume||$issue||$spage||$epage||$dateofissued
                        $biblio  = explode("||", $value);
                        // when output biblioinfo for junii2.
                        if(count($mapping) == 6 && count($biblio) == 6)
                        {
                            $xml .= $this->outputAttributeValue($mapping[0], $biblio[0]);
                            $xml .= $this->outputAttributeValue($mapping[1], $biblio[1]);
                            $xml .= $this->outputAttributeValue($mapping[2], $biblio[2]);
                            $xml .= $this->outputAttributeValue($mapping[3], $biblio[3]);
                            $xml .= $this->outputAttributeValue($mapping[4], $biblio[4]);
                            $xml .= $this->outputAttributeValue($mapping[5], $biblio[5]);
                        }
                    }
                    // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
                    else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_NAME)
                    {
                        $nameAuthority = new NameAuthority($this->Session, $this->Db);
                        $nameAuthorityInfo = $nameAuthority->getExternalAuthorIdData($itemAttr[$ii][$jj]["author_id"]);
                        
                        $reseacherResolverArray = array();
                        if(count($nameAuthorityInfo) > 0 && $nameAuthorityInfo[0]["prefix_id"] == 2)
                        {
                            $reseacherResolverId = $nameAuthorityInfo[0]["suffix"];
                            $reseacherResolverArray = array("prefix_id" => 2, "suffix" => $reseacherResolverId);
                        }
                        $xml .= $this->outputAttributeValue($junii2Map, $value, $lang, $reseacherResolverArray);
                    }
                    // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
                    // Add for Bug No.1 Fixes R.Matsuura 2013/09/24 --start--
                    else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_FILE || $inputType == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE)
                    {
                        $xml .= $this->outputattributeValue($junii2Map, $value);
                        $licenceNotation = RepositoryOutputFilter::fileLicence($itemAttr[$ii][$jj]);
                        $xml .= $this->outputRights($licenceNotation);
                    }
                    // Add for Bug No.1 Fixes R.Matsuura 2013/09/24 --end--
                    else
                    {
                        // when is value, output. 値があれば出力
                        $xml .= $this->outputAttributeValue($junii2Map, $value, $lang);
                    }
                }
            }
        }
        
        return $xml;
    }
    
    /**
     * output xml for mapping info.
     *
     * @param string $mapping
     * @param string $value
     * @param string $lang
     * @param array $authorIdArray
     * @return string
     */
    private function outputAttributeValue($mapping, $value, $lang="", $authorIdArray=array())
    {
        $xml = '';
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        $lang = RepositoryOutputFilterJuNii2::languageToRFC($lang);
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        
        switch ($mapping)
        {
            case RepositoryConst::JUNII2_TITLE:
                $xml = $this->outputTitle($value, $lang);
                break;
            case RepositoryConst::JUNII2_ALTERNATIVE:
                $xml = $this->outputAlternative($value, $lang);
                break;
            case RepositoryConst::JUNII2_CREATOR:
                // Update JuNii2 ver3 R.Matsuura 2013/09/24 --start--
                $xml = $this->outputCreator($value, $lang, $authorIdArray);
                // Update JuNii2 ver3 R.Matsuura 2013/09/24 --end--
                break;
            case RepositoryConst::JUNII2_SUBJECT:
                $xml = $this->outputSubject($value);
                break;
            case RepositoryConst::JUNII2_NII_SUBJECT:
                $xml = $this->outputNIISubject($value);
                break;
            case RepositoryConst::JUNII2_NDC:
                $xml = $this->outputNDC($value);
                break;
            case RepositoryConst::JUNII2_NDLC:
                $xml = $this->outputNDLC($value);
                break;
            case RepositoryConst::JUNII2_BSH:
                $xml = $this->outputBSH($value);
                break;
            case RepositoryConst::JUNII2_NDLSH:
                $xml = $this->outputNDLSH($value);
                break;
            case RepositoryConst::JUNII2_MESH:
                $xml = $this->outputMeSH($value);
                break;
            case RepositoryConst::JUNII2_DDC:
                $xml = $this->outputDDC($value);
                break;
            case RepositoryConst::JUNII2_LCC:
                $xml = $this->outputLCC($value);
                break;
            case RepositoryConst::JUNII2_UDC:
                $xml = $this->outputUDC($value);
                break;
            case RepositoryConst::JUNII2_LCSH:
                $xml = $this->outputLCSH($value);
                break;
            case RepositoryConst::JUNII2_DESCRIPTION:
                $xml = $this->outputDescription($value);
                break;
                // Update JuNii2 ver3 R.Matsuura 2013/09/24 --start--
            case RepositoryConst::JUNII2_PUBLISHER:
                $xml = $this->outputPublisher($value, $lang, $authorIdArray);
                break;
            case RepositoryConst::JUNII2_CONTRIBUTOR:
                $xml = $this->outputContributor($value, $lang, $authorIdArray);
                break;
                // Update JuNii2 ver3 R.Matsuura 2013/09/24 --end--
            case RepositoryConst::JUNII2_DATE:
                $xml = $this->outputDate($value);
                break;
            case RepositoryConst::JUNII2_TYPE:
                $xml = $this->outputType($value);
                break;
            case RepositoryConst::JUNII2_NIITYPE:
                $xml = $this->outputNIIType($value);
                break;
            case RepositoryConst::JUNII2_FORMAT:
                $xml = $this->outputFormat($value);
                break;
            case RepositoryConst::JUNII2_IDENTIFIER:
                $xml = $this->outputIdentifier($value);
                break;
            case RepositoryConst::JUNII2_URI:
                $xml = $this->outputURI($value);
                break;
            case RepositoryConst::JUNII2_FULL_TEXT_URL:
                $xml = $this->outputFullTextURL($value);
                break;
            case RepositoryConst::JUNII2_ISSN:
                $xml = $this->outputISSN($value);
                break;
            case RepositoryConst::JUNII2_NCID:
                $xml = $this->outputNCID($value);
                break;
            case RepositoryConst::JUNII2_JTITLE:
                $xml = $this->outputJtitle($value, $lang);
                break;
            case RepositoryConst::JUNII2_VOLUME:
                $xml = $this->outputVolume($value);
                break;
            case RepositoryConst::JUNII2_ISSUE:
                $xml = $this->outputIssue($value);
                break;
            case RepositoryConst::JUNII2_SPAGE:
                $xml = $this->outputSpage($value);
                break;
            case RepositoryConst::JUNII2_EPAGE:
                $xml = $this->outputEpage($value);
                break;
            case RepositoryConst::JUNII2_DATE_OF_ISSUED:
                $xml = $this->outputDateofissued($value);
                break;
            case RepositoryConst::JUNII2_SOURCE:
                $xml = $this->outputSource($value);
                break;
            case RepositoryConst::JUNII2_LANGUAGE:
                $xml = $this->outputLanguage($value);
                break;
            case RepositoryConst::JUNII2_RELATION:
                $xml = $this->outputRelation($value);
                break;
            case RepositoryConst::JUNII2_PMID:
                $xml = $this->outputPmid($value);
                break;
            case RepositoryConst::JUNII2_DOI:
                $xml = $this->outputDoi($value);
                break;
            case RepositoryConst::JUNII2_IS_VERSION_OF:
                $xml = $this->outputIsVersionOf($value);
                break;
            case RepositoryConst::JUNII2_HAS_VERSION:
                $xml = $this->outputHasVersion($value);
                break;
            case RepositoryConst::JUNII2_IS_REPLACED_BY:
                $xml = $this->outputIsReplacedBy($value);
                break;
            case RepositoryConst::JUNII2_REPLACES:
                $xml = $this->outputReplaces($value);
                break;
            case RepositoryConst::JUNII2_IS_REQUIRESD_BY:
                $xml = $this->outputIsRequiredBy($value);
                break;
            case RepositoryConst::JUNII2_REQUIRES:
                $xml = $this->outputRequires($value);
                break;
            case RepositoryConst::JUNII2_IS_PART_OF:
                $xml = $this->outputIsPartOf($value);
                break;
            case RepositoryConst::JUNII2_HAS_PART:
                $xml = $this->outputHasPart($value);
                break;
            case RepositoryConst::JUNII2_IS_REFERENCED_BY:
                $xml = $this->outputIsReferencedBy($value);
                break;
            case RepositoryConst::JUNII2_REFERENCES:
                $xml = $this->outputReferences($value);
                break;
            case RepositoryConst::JUNII2_IS_FORMAT_OF:
                $xml = $this->outputIsFormatOf($value);
                break;
            case RepositoryConst::JUNII2_HAS_FORMAT:
                $xml = $this->outputHasFormat($value);
                break;
            case RepositoryConst::JUNII2_COVERAGE:
                $xml = $this->outputCoverage($value);
                break;
            case RepositoryConst::JUNII2_SPATIAL:
                $xml = $this->outputSpatial($value);
                break;
            case RepositoryConst::JUNII2_NII_SPATIAL:
                $xml = $this->outputNIISpatial($value);
                break;
            case RepositoryConst::JUNII2_TEMPORAL:
                $xml = $this->outputTemporal($value);
                break;
            case RepositoryConst::JUNII2_NII_TEMPORAL:
                $xml = $this->outputNIITemporal($value);
                break;
            case RepositoryConst::JUNII2_RIGHTS:
                $xml = $this->outputRights($value);
                break;
            case RepositoryConst::JUNII2_TEXTVERSION:
                $xml = $this->outputTextversion($value);
                break;
            // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
            case RepositoryConst::JUNII2_ISBN:
                $xml = $this->outputISBN($value);
                break;
            case RepositoryConst::JUNII2_NAID:
                $xml = $this->outputNAID($value);
                break;
            case RepositoryConst::JUNII2_ICHUSHI:
                $xml = $this->outputIchushi($value);
                break;
            case RepositoryConst::JUNII2_GRANTID:
                $xml = $this->outputGrantid($value);
                break;
            case RepositoryConst::JUNII2_DATEOFGRANTED:
                $xml = $this->outputDateofgranted($value);
                break;
            case RepositoryConst::JUNII2_DEGREENAME:
                $xml = $this->outputDegreename($value);
                break;
            case RepositoryConst::JUNII2_GRANTOR:
                $xml = $this->outputGrantor($value);
                break;
            // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
            default:
                break;
        }
        return $xml;
    }
    
    /**
     * title output
     *   is necessary.
     *   minOccurs = 1, maxOccurs = 1
     *   option = lang
     * 
     * @param string $title
     * @param string $lang
     * @return string
     */
    private function outputTitle($title, $lang="")
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_TITLE] < 1)
        {
            // when over maxOccurs, output alternative.
            return $this->outputAlternative($title, $lang);
        }
        
        // output title
        $tag = RepositoryConst::JUNII2_TITLE;
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        $xml = $this->outputElement($tag, $title, $option);
        if(strlen($xml)>0)
        {
            $this->occurs[RepositoryConst::JUNII2_TITLE]--;
        }
        
        return $xml;
        
    }
    
    /**
     * alternative output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = lang
     * 
     * @param string $alternative
     * @param string $lang
     * @return string
     */
    private function outputAlternative($alternative, $lang="")
    {
        $tag = RepositoryConst::JUNII2_ALTERNATIVE;
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        return $this->outputElement($tag, $alternative, $option);
    }
    
    /**
     * creator output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = lang
     * 
     * @param string $creator
     * @param string $lang
     * // Add JuNii2 ver3 R.Matsuura 2013/09/24
     * @param array $authorIdArray
     * @return string
     */
    private function outputCreator($creator, $lang="", $authorIdArray=array())
    {
        $tag = RepositoryConst::JUNII2_CREATOR;
        
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        $uri = RepositoryOutputFilter::creatorId($authorIdArray);
        if(strlen($uri) > 0)
        {
            $option["id"] = $uri;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        return $this->outputElement($tag, $creator, $option);
    }
    
    /**
     * subject output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = null
     * 
     * @param string $subject
     * @return string
     */
    private function outputSubject($subject)
    {
        $tag = RepositoryConst::JUNII2_SUBJECT;
        return $this->outputElement($tag, $subject);
    }
    
    /**
     * NIIsubject output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $niiSubject
     * @param string $version
     * @return string
     */
    private function outputNIISubject($niiSubject, $version="")
    {
        $tag = RepositoryConst::JUNII2_NII_SUBJECT;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $niiSubject, $option);
    }
    
    /**
     * NDC output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $NDC
     * @param string $version
     * @return string
     */
    private function outputNDC($NDC, $version="")
    {
        $tag = RepositoryConst::JUNII2_NDC;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $NDC, $option);
    }
    
    /**
     * NDLC output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $NDLC
     * @param string $version
     * @return string
     */
    private function outputNDLC($NDLC, $version="")
    {
        $tag = RepositoryConst::JUNII2_NDLC;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $NDLC, $option);
    }
    
    /**
     * BSH output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $BSH
     * @param string $version
     * @return string
     */
    private function outputBSH($BSH, $version="")
    {
        $tag = RepositoryConst::JUNII2_BSH;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $BSH, $option);
    }
    
    /**
     * NDLSH output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $NDLSH
     * @param string $version
     * @return string
     */
    private function outputNDLSH($NDLSH, $version="")
    {
        $tag = RepositoryConst::JUNII2_NDLSH;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $NDLSH, $option);
    }
    
    /**
     * MeSH output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $MeSH
     * @param string $version
     * @return string
     */
    private function outputMeSH($MeSH, $version="")
    {
        $tag = RepositoryConst::JUNII2_MESH;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $MeSH, $option);
    }
    
    /**
     * DDC output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $DDC
     * @param string $version
     * @return string
     */
    private function outputDDC($DDC, $version="")
    {
        $tag = RepositoryConst::JUNII2_MESH;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $DDC, $option);
    }
    
    /**
     * LCC output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $LCC
     * @param string $version
     * @return string
     */
    private function outputLCC($LCC, $version="")
    {
        $tag = RepositoryConst::JUNII2_DDC;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $LCC, $option);
    }
    
    /**
     * UDC output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $UDC
     * @param string $version
     * @return string
     */
    private function outputUDC($UDC, $version="")
    {
        $tag = RepositoryConst::JUNII2_UDC;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $UDC, $option);
    }
    
    /**
     * LCSH output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = version
     * 
     * @param string $LCSH
     * @param string $version
     * @return string
     */
    private function outputLCSH($LCSH, $version="")
    {
        $tag = RepositoryConst::JUNII2_LCSH;
        $option = array();
        if(strlen($version) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
        }
        return $this->outputElement($tag, $LCSH, $option);
    }
    
    /**
     * description output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $description
     * @return string
     */
    private function outputDescription($description)
    {
        $tag = RepositoryConst::JUNII2_DESCRIPTION;
        return $this->outputElement($tag, $description);
    }
    
    /**
     * publisher output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = lang
     * 
     * @param string $publisher
     * @param string $lang
     * // Add JuNii2 ver3 R.Matsuura 2013/09/24
     * @param array $authorIdArray
     * @return string
     */
    private function outputPublisher($publisher, $lang="", $authorIdArray=array())
    {
        $tag = RepositoryConst::JUNII2_PUBLISHER;
        
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        $uri = RepositoryOutputFilter::creatorId($authorIdArray);
        if(strlen($uri) > 0)
        {
            $option["id"] = $uri;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        return $this->outputElement($tag, $publisher, $option);
    }
    
    /**
     * contributor output
     *   minOccurs = 0, maxOccurs = unbounded
     *   option = lang
     * 
     * @param string $contributor
     * @param string $lang
     * // Add JuNii2 ver3 R.Matsuura 2013/09/24
     * @param array $authorIdArray
     * @return string
     */
    private function outputContributor($contributor, $lang="", $authorIdArray=array())
    {
        $tag = RepositoryConst::JUNII2_CONTRIBUTOR;
        
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        $uri = RepositoryOutputFilter::creatorId($authorIdArray);
        if(strlen($uri) > 0)
        {
            $option["id"] = $uri;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        return $this->outputElement($tag, $contributor, $option);
    }
    
    /**
     * date output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $date
     * @return string
     */
    private function outputDate($date)
    {
        $tag = RepositoryConst::JUNII2_DATE;
        $date = RepositoryOutputFilter::date($date);
        $xml = $this->outputElement($tag, $date);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_DATE]--;
        }
        return $xml;
    }
    
    /**
     * type output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $type
     * @return string
     */
    private function outputType($type)
    {
        $tag = RepositoryConst::JUNII2_TYPE;
        return $this->outputElement($tag, $type);
    }
    
    /**
     * output NIIType
     *   is necessary.
     *   minOccurs = 1, maxOccurs = 1
     * 
     * @param string $niiType
     * @return string
     */
    private function outputNIIType($niiType)
    {
        $xml = '';
        if( $niiType!=RepositoryConst::NIITYPE_JOURNAL_ARTICLE &&
            $niiType!=RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION &&
            $niiType!=RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER &&
            $niiType!=RepositoryConst::NIITYPE_CONFERENCE_PAPER &&
            $niiType!=RepositoryConst::NIITYPE_PRESENTATION &&
            $niiType!=RepositoryConst::NIITYPE_BOOK &&
            $niiType!=RepositoryConst::NIITYPE_TECHNICAL_REPORT &&
            $niiType!=RepositoryConst::NIITYPE_RESEARCH_PAPER &&
            $niiType!=RepositoryConst::NIITYPE_ARTICLE &&
            $niiType!=RepositoryConst::NIITYPE_PREPRINT &&
            $niiType!=RepositoryConst::NIITYPE_LEARNING_MATERIAL &&
            $niiType!=RepositoryConst::NIITYPE_DATA_OR_DATASET &&
            $niiType!=RepositoryConst::NIITYPE_SOFTWARE &&
            $niiType!=RepositoryConst::NIITYPE_OTHERS)
        {
            return '';
        }
        
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_NIITYPE] < 1)
        {
            // when over maxOccurs, output type.
            return $this->outputType($niiType);
        }
        
        $tag = RepositoryConst::JUNII2_NIITYPE;
        $xml = $this->outputElement($tag, $niiType);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_NIITYPE]--;
        }
        return $xml;
    }
    
    /**
     * format output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $type
     * @return string
     */
    private function outputFormat($format)
    {
        $tag = RepositoryConst::JUNII2_FORMAT;
        return $this->outputElement($tag, $format);
    }
    
    /**
     * identifier output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $type
     * @return string
     */
    private function outputIdentifier($identifier)
    {
        $tag = RepositoryConst::JUNII2_IDENTIFIER;
        return $this->outputElement($tag, $identifier);
    }
    
    /**
     * URI output
     *   is necessary.
     *   minOccurs = 1, maxOccurs = 1
     * 
     * @param string $URI
     * @return string
     */
    private function outputURI($URI)
    {
        if(strlen($URI)==0)
        {
            return '';
        }
        
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_URI] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($URI);
        }
        
        // output title
        $tag = RepositoryConst::JUNII2_URI;
        $xml = $this->outputElement($tag, $URI);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_URI]--;
        }
        return $xml;
    }
    
    /**
     * fullTextURL output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $fullTextURL
     * @return string
     */
    private function outputFullTextURL($fullTextURL)
    {
        $tag = RepositoryConst::JUNII2_FULL_TEXT_URL;
        return $this->outputElement($tag, $fullTextURL);
    }
    
    /**
     * issn output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $issn
     * @return string
     */
    private function outputISSN($issn)
    {
        $tag = RepositoryConst::JUNII2_ISSN;
        $issn = RepositoryOutputFilterJuNii2::issn($issn);
        return $this->outputElement($tag, $issn);
    }
    
    /**
     * ncid output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $ncid
     * @return string
     */
    private function outputNCID($ncid)
    {
        $tag = RepositoryConst::JUNII2_NCID;
        return $this->outputElement($tag, $ncid);
    }
    
    /**
     * jtitle output
     *   minOccurs = 0, maxOccurs = 1
     *   option = lang
     * 
     * @param string $jtitle
     * @param string $lang
     * @return string
     */
    private function outputJtitle($jtitle, $lang="")
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_JTITLE] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($jtitle, $lang);
        }
        
        // output jtitle
        $tag = RepositoryConst::JUNII2_JTITLE;
        $option = array();
        if(strlen($lang) > 0)
        {
            $option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
        }
        $xml = $this->outputElement($tag, $jtitle, $option);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_JTITLE]--;
        }
        return $xml;
    }
    
    /**
     * volume output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $volume
     * @return string
     */
    private function outputVolume($volume)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_VOLUME] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($volume);
        }
        
        // output volume
        $tag = RepositoryConst::JUNII2_VOLUME;
        $xml = $this->outputElement($tag, $volume);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_VOLUME]--;
        }
        return $xml;
    }
    
    /**
     * issue output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $issue
     * @return string
     */
    private function outputIssue($issue)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_ISSUE] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($issue);
        }
        
        // output issue
        $tag = RepositoryConst::JUNII2_ISSUE;
        $xml = $this->outputElement($tag, $issue);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_ISSUE]--;
        }
        return $xml;
    }
    
    /**
     * spage output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $issue
     * @return string
     */
    private function outputSpage($spage)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_SPAGE] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($spage);
        }
        
        // output spage
        $tag = RepositoryConst::JUNII2_SPAGE;
        $xml = $this->outputElement($tag, $spage);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_SPAGE]--;
        }
        return $xml;
    }
    
    /**
     * epage output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $epage
     * @return string
     */
    private function outputEpage($epage)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_EPAGE] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($epage);
        }
        
        // output spage
        $tag = RepositoryConst::JUNII2_EPAGE;
        $xml = $this->outputElement($tag, $epage);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_EPAGE]--;
        }
        return $xml;
    }
    
    /**
     * dateofissued output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $epage
     * @return string
     */
    private function outputDateofissued($dateofissued)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($dateofissued);
        }
        
        // output spage
        $tag = RepositoryConst::JUNII2_DATE_OF_ISSUED;
        $dateofissued = RepositoryOutputFilter::date($dateofissued);
        $xml = $this->outputElement($tag, $dateofissued);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED]--;
        }
        return $xml;
    }
    
    /**
     * source output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $source
     * @return string
     */
    private function outputSource($source)
    {
        $tag = RepositoryConst::JUNII2_SOURCE;
        return $this->outputElement($tag, $source);
    }
    
    /**
     * language output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $language
     * @return string
     */
    private function outputLanguage($language)
    {
        $tag = RepositoryConst::JUNII2_LANGUAGE;
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        $language = RepositoryOutputFilterJuNii2::languageToISO($language);
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        return $this->outputElement($tag, $language);
    }
    
    /**
     * relation output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $language
     * @return string
     */
    private function outputRelation($relation)
    {
        $tag = RepositoryConst::JUNII2_RELATION;
        return $this->outputElement($tag, $relation);
    }
    
    /**
     * pmid output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $pmid
     * @return string
     */
    private function outputPmid($pmid)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_PMID] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($pmid);
        }
        
        // output issue
        $tag = RepositoryConst::JUNII2_PMID;
        $xml = $this->outputElement($tag, $pmid);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_PMID]--;
        }
        return $xml;
    }
    
    /**
     * doi output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $doi
     * @return string
     */
    private function outputDoi($doi)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_DOI] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($doi);
        }
        
        // output issue
        $tag = RepositoryConst::JUNII2_DOI;
        $xml = $this->outputElement($tag, $doi);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_DOI]--;
        }
        return $xml;
    }
    
    /**
     * isVersionOf output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isVersionOf
     * @return string
     */
    private function outputIsVersionOf($isVersionOf)
    {
        $tag = RepositoryConst::JUNII2_IS_VERSION_OF;
        return $this->outputElement($tag, $isVersionOf);
    }
    
    /**
     * hasVersion output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $hasVersion
     * @return string
     */
    private function outputHasVersion($hasVersion)
    {
        $tag = RepositoryConst::JUNII2_HAS_VERSION;
        return $this->outputElement($tag, $hasVersion);
    }
    
    /**
     * isReplacedBy output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isReplacedBy
     * @return string
     */
    private function outputIsReplacedBy($isReplacedBy)
    {
        $tag = RepositoryConst::JUNII2_IS_REPLACED_BY;
        return $this->outputElement($tag, $isReplacedBy);
    }
    
    /**
     * replaces output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isReplacedBy
     * @return string
     */
    private function outputReplaces($isReplaces)
    {
        $tag = RepositoryConst::JUNII2_REPLACES;
        return $this->outputElement($tag, $replaces);
    }
    
    /**
     * isRequiredBy output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isRequiredBy
     * @return string
     */
    private function outputIsRequiredBy($isRequiredBy)
    {
        $tag = RepositoryConst::JUNII2_IS_REQUIRESD_BY;
        return $this->outputElement($tag, $isRequiredBy);
    }
    
    /**
     * requires output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $requires
     * @return string
     */
    private function outputRequires($requires)
    {
        $tag = RepositoryConst::JUNII2_REQUIRES;
        return $this->outputElement($tag, $requires);
    }
    
    /**
     * isPartOf output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isPartOf
     * @return string
     */
    private function outputIsPartOf($isPartOf)
    {
        $tag = RepositoryConst::JUNII2_IS_PART_OF;
        return $this->outputElement($tag, $isPartOf);
    }
    
    /**
     * hasPart output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $hasPart
     * @return string
     */
    private function outputHasPart($hasPart)
    {
        $tag = RepositoryConst::JUNII2_HAS_PART;
        return $this->outputElement($tag, $hasPart);
    }
    
    /**
     * isReferencedBy output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isReferencedBy
     * @return string
     */
    private function outputIsReferencedBy($isReferencedBy)
    {
        $tag = RepositoryConst::JUNII2_IS_REFERENCED_BY;
        return $this->outputElement($tag, $isReferencedBy);
    }
    
    /**
     * references output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $references
     * @return string
     */
    private function outputReferences($references)
    {
        $tag = RepositoryConst::JUNII2_REFERENCES;
        return $this->outputElement($tag, $references);
    }
    
    /**
     * isFormatOf output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $isFormatOf
     * @return string
     */
    private function outputIsFormatOf($isFormatOf)
    {
        $tag = RepositoryConst::JUNII2_IS_FORMAT_OF;
        return $this->outputElement($tag, $isFormatOf);
    }
    
    /**
     * hasFormat output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $hasFormat
     * @return string
     */
    private function outputHasFormat($hasFormat)
    {
        $tag = RepositoryConst::JUNII2_HAS_FORMAT;
        return $this->outputElement($tag, $hasFormat);
    }
    
    /**
     * coverage output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $coverage
     * @return string
     */
    private function outputCoverage($coverage)
    {
        $tag = RepositoryConst::JUNII2_COVERAGE;
        return $this->outputElement($tag, $coverage);
    }
    
    /**
     * spatial output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $spatial
     * @return string
     */
    private function outputSpatial($spatial)
    {
        $tag = RepositoryConst::JUNII2_SPATIAL;
        return $this->outputElement($tag, $spatial);
    }
    
    /**
     * NIIspatial output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $NIIspatial
     * @return string
     */
    private function outputNIISpatial($NIIspatial)
    {
        $tag = RepositoryConst::JUNII2_NII_SPATIAL;
        return $this->outputElement($tag, $NIIspatial);
    }
    
    /**
     * temporal output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $temporal
     * @return string
     */
    private function outputTemporal($temporal)
    {
        $tag = RepositoryConst::JUNII2_TEMPORAL;
        return $this->outputElement($tag, $temporal);
    }
    
    /**
     * NIItemporal output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $NIItemporal
     * @return string
     */
    private function outputNIITemporal($NIItemporal)
    {
        $tag = RepositoryConst::JUNII2_NII_TEMPORAL;
        return $this->outputElement($tag, $NIItemporal);
    }
    
    /**
     * rights output
     *   minOccurs = 0, maxOccurs = unbounded
     * 
     * @param string $rights
     * @return string
     */
    private function outputRights($rights)
    {
        $tag = RepositoryConst::JUNII2_RIGHTS;
        return $this->outputElement($tag, $rights);
    }
    
    /**
     * textversion output
     *   minOccurs = 0, maxOccurs = 1
     * 
     * @param string $textversion
     * @return string
     */
    private function outputTextversion($textversion)
    {
        // occursCheck
        if($this->occurs[RepositoryConst::JUNII2_TEXTVERSION] < 1)
        {
            // when over maxOccurs, output identifier.
            return $this->outputIdentifier($textversion);
        }
        
        // output issue
        $tag = RepositoryConst::JUNII2_TEXTVERSION;
        $textversion = RepositoryOutputFilterJuNii2::textversion($textversion);
        $xml = $this->outputElement($tag, $textversion);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_TEXTVERSION]--;
        }
        return $xml;
    }
    
    /**
     * return XML element.
     *
     * @param string $tag
     * @param string $value
     * @param array $oution array($key=>$value, $key=>$value, ... )
     * @return string
     */
    private function outputElement($tag, $value, $option=array())
    {
        $value = $this->RepositoryAction->forXmlChange($value);
        if(strlen($tag) == 0 || strlen($value) == 0)
        {
            return '';
        }
        
        $strOption = '';
        foreach ($option as $key => $val)
        {
            if(strlen($key) > 0 && strlen($val) > 0)
            {
                $val = $this->RepositoryAction->forXmlChange($val);
                $strOption .= "$key=\"$val\" ";
            }
        }
        
        if(strlen($strOption) > 0)
        {
            $xml = "<$tag $strOption>$value</$tag>";
        }
        else
        {
            $xml = "<$tag>$value</$tag>";
        }
        return $xml;
    }
    
    /**
     * output item reference link
     *
     * @param array $reference
     * @return string
     */
    private function outputReference($reference)
    {
        $xml = '';
        
        for ($ii=0; $ii<count($reference); $ii++)
        {
            $destItemId = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_ID];
            $destItemNo = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_NO];
            $refKey     = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_REFERENCE];
            // get detail url
            $refUrl = $this->RepositoryAction->getDetailUri($destItemId, $destItemNo);
            // mapping
            if(strlen($refKey) > 0)
            {
                $xml .= $this->outputAttributeValue($refKey, $refUrl);
            }
            else
            {
                $xml .= $this->outputRelation($refUrl);
            }
        }
        return $xml;
    }
    
    /**
     * occurs check
     * 
     * return bool true:OK, false:failed
     *
     */
    private function occursCheck()
    {
        if($this->occurs[RepositoryConst::JUNII2_TITLE] != 0)
        {
            // title is necessary.
            // minOccurs=1, maxOccurs=1
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_DATE] == 1)
        {
            // date is min occurs = 1, maxOccurs=unbounded.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_NIITYPE] != 0)
        {
            // NIIType is necessary.
            // minOccurs=1, maxOccurs=1
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_URI] != 0)
        {
            // URI is necessary.
            // minOccurs=1, maxOccurs=1
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_JTITLE] < 0)
        {
            // jtitle is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_VOLUME] < 0)
        {
            // volume is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_ISSUE] < 0)
        {
            // issue is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_SPAGE] < 0)
        {
            // spage is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_EPAGE] < 0)
        {
            // epage is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED] < 0)
        {
            // dateofissued is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_PMID] < 0)
        {
            // pmid is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_DOI] < 0)
        {
            // doi is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_TEXTVERSION] < 0)
        {
            // textversion is min occurs = 0, maxOccurs=1.
            return false;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
        if($this->occurs[RepositoryConst::JUNII2_SELFDOI] < 0)
        {
            // selfdoi is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_SELFDOI_JALC] < 0)
        {
            // selfdoi(jalc) is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_SELFDOI_CROSSREF] < 0)
        {
            // selfdoi(crossref) is min occurs = 0, maxOccurs=1.
            return false;
        }
        // Add DataCite 2015/02/10 K.Sugimoto --start--
        if($this->occurs[RepositoryConst::JUNII2_SELFDOI_DATACITE] < 0)
        {
            // selfdoi(datacite) is min occurs = 0, maxOccurs=1.
            return false;
        }
        // Add DataCite 2015/02/10 K.Sugimoto --end--
        if($this->occurs[RepositoryConst::JUNII2_NAID] < 0)
        {
            // NAID is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_ICHUSHI] < 0)
        {
            // ichushi is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_GRANTID] < 0)
        {
            // grantid is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED] < 0)
        {
            // dateofgranted is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_DEGREENAME] < 0)
        {
            // degreename is min occurs = 0, maxOccurs=1.
            return false;
        }
        if($this->occurs[RepositoryConst::JUNII2_GRANTOR] < 0)
        {
            // grantor is min occurs = 0, maxOccurs=1.
            return false;
        }
        // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
        return true;
    }
    
    /**
     * output footer
     *
     * @return string
     */
    private function outputFooter()
    {
        $xml = '';
        $xml .= '</'.RepositoryConst::JUNII2_START.'>'.self::LF;
        return $xml;
    }
    
    // Add new prefix 2013/12/24 T.Ichikawa --start--
    /**
     * SelfDOI output
     *
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    private function outputSelfDOI($item_id, $item_no)
    {
        $tag = RepositoryConst::JUNII2_SELFDOI;
        $xml = "";
        
        $this->getRepositoryHandleManager();
        $uri_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_JALC_DOI);
        $uri_crossref = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_CROSS_REF_DOI);
        // Add DataCite 2015/02/10 K.Sugimoto --start--
        $uri_datacite = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_DATACITE_DOI);
        $uri_library_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_LIBRARY_JALC_DOI);
        
        if(strlen($uri_jalcdoi) > 0 && strlen($uri_crossref) < 1 && strlen($uri_datacite) < 1 && strlen($uri_library_jalcdoi) < 1)
        {
            $option = array();
            $option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
            $xml .= $this->outputElement($tag, $uri_jalcdoi, $option);
            
        }
        else if(strlen($uri_crossref) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_datacite) < 1 && strlen($uri_library_jalcdoi) < 1)
        {
            $option = array();
            $option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_CROSSREF;
            $xml = $this->outputElement($tag, $uri_crossref, $option);
            
        }
        else if(strlen($uri_datacite) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_crossref) < 1 && strlen($uri_library_jalcdoi) < 1)
        {
            $option = array();
            $option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_DATACITE;
            $xml = $this->outputElement($tag, $uri_datacite, $option);
            
        }
        // Add DataCite 2015/02/10 K.Sugimoto --end--
        else if(strlen($uri_library_jalcdoi) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_crossref) < 1 && strlen($uri_datacite) < 1)
        {
            $option = array();
            $option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
            $xml = $this->outputElement($tag, $uri_library_jalcdoi, $option);
            
        }
        
        return $xml;
    }
    
    
    private function getRepositoryHandleManager()
    {
        if(!isset($this->repositoryHandleManager)){
            if(strlen($this->RepositoryAction->TransStartDate) == 0){
                $date = new Date();
                $this->RepositoryAction->TransStartDate = $date->getDate().".000";
            }
            $this->repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->RepositoryAction->TransStartDate);
        }
        
    }
    
    // Add new prefix 2013/12/24 T.Ichikawa --end--
    
    // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
    /**
     * ISBN output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strIsbn
     * @return string
     */
    private function outputISBN($strIsbn)
    {
        $tag = RepositoryConst::JUNII2_ISBN;
        $xml = $this->outputElement($tag, $strIsbn);
        return $xml;
    }
    
    /**
     * NAID output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strNaid
     * @return string
     */
    private function outputNAID($strNaid)
    {
        if($this->occurs[RepositoryConst::JUNII2_NAID] < 1)
        {
            return $this->outputRelation($strNaid);
        }
        $tag = RepositoryConst::JUNII2_NAID;
        $naid = RepositoryOutputFilterJuNii2::naid($strNaid);
        $xml = $this->outputElement($tag, $naid);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_NAID]--;
        }
        return $xml;
    }
    
    /**
     * Ichushi output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strIchushi
     * @return string
     */
    private function outputIchushi($strIchushi)
    {
        if($this->occurs[RepositoryConst::JUNII2_ICHUSHI] < 1)
        {
            return $this->outputRelation($strIchushi);
        }
        $tag = RepositoryConst::JUNII2_ICHUSHI;
        $ichushi = RepositoryOutputFilterJuNii2::ichushi($strIchushi);
        $xml = $this->outputElement($tag, $ichushi);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_ICHUSHI]--;
        }
        return $xml;
    }
    
    /**
     * grantid output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strGrantId
     * @return string
     */
    private function outputGrantid($strGrantId)
    {
        if($this->occurs[RepositoryConst::JUNII2_GRANTID] < 1)
        {
            return $this->outputIdentifier($strGrantId);
        }
        $tag = RepositoryConst::JUNII2_GRANTID;
        $grantid = RepositoryOutputFilterJuNii2::grantid($strGrantId);
        $xml = $this->outputElement($tag, $grantid);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_GRANTID]--;
        }
        return $xml;
    }
    
    /**
     * dateofgranted output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strDateofgrant
     * @return string
     */
    private function outputDateofgranted($strDateofgrant)
    {
        if($this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED] < 1)
        {
            return $this->outputDate($strDateofgrant);
        }
        $tag = RepositoryConst::JUNII2_DATEOFGRANTED;
        $date = RepositoryOutputFilter::date($strDateofgrant);
        $xml = $this->outputElement($tag, $date);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED]--;
        }
        return $xml;
    }
    
    /**
     * degreename output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strDegreename
     * @return string
     */
    private function outputDegreename($strDegreename)
    {
        if($this->occurs[RepositoryConst::JUNII2_DEGREENAME] < 1)
        {
            return $this->outputDescription($strDegreename);
        }
        $tag = RepositoryConst::JUNII2_DEGREENAME;
        $xml = $this->outputElement($tag, $strDegreename);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_DEGREENAME]--;
        }
        return $xml;
    }
    
    /**
     * grantor output
     *   minOccurs = 0, maxOccurs = 1
     *
     * @param string $strGrantor
     * @return string
     */
    private function outputGrantor($strGrantor)
    {
        if($this->occurs[RepositoryConst::JUNII2_GRANTOR] < 1)
        {
            return $this->outputDescription($strGrantor);
        }
        $tag = RepositoryConst::JUNII2_GRANTOR;
        $xml = $this->outputElement($tag, $strGrantor);
        if(strlen($xml) > 0)
        {
            $this->occurs[RepositoryConst::JUNII2_GRANTOR]--;
        }
        return $xml;
    }
    // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
}
?>
