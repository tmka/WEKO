<?php
// --------------------------------------------------------------------
//
// $Id: DublinCore.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/FormatAbstract.class.php';

class Repository_Oaipmh_DublinCore extends Repository_Oaipmh_FormatAbstract
{
    /**
     * output DATE flag
     *   DATE tag is indispensable.
     *
     */
    private $outputDateFlg = false;
    
    /**
     * コンストラクタ
     */
    public function __construct($session, $db)
    {
        parent::__construct($session, $db);
    }
    
    /**
     * initialize
     *
     */
    private function initialize()
    {
        $this->outputDateFlg = false;
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
        
        // NIIType output
        $niiType = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_MAPPING_INFO];
        $xml .= $this->outputNIIType($niiType);
        
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
        
        if(!$this->outputDateFlg)
        {
            // YYYY-MM-DD or YYYY-MM or YYYY only
            $insDate = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_COMMON_INS_DATE];
            $value = explode(" ", $insDate);
            $xml .= $this->outputDate($value[0]);
        }
        
        // footer output
        $xml .= $this->outputFooter();
        
        return $xml;
    }
    
    /**
     * ヘッダー部分の出力
     *
     * @return string
     */
    private function outputHeader()
    {
        $xml = '';
        $xml .= '<'.RepositoryConst::DUBLIN_CORE_START;
        $xml .= ' xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" ';
        $xml .= ' xmlns:dc="http://purl.org/dc/elements/1.1/" ';
        $xml .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $xml .= ' xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ ';
        $xml .= ' http://www.openarchives.org/OAI/2.0/oai_dc.xsd">'.self::LF;
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
        if($language == RepositoryConst::ITEM_LANG_JA)
        {
            // japanese. 日本語
            $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
            if(strlen($title) == 0)
            {
                $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
            }
        }
        else
        {
            // not japanese. 洋語
            $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
            if(strlen($title) == 0)
            {
                $title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
            }
        }
        $xml .= $this->outputTitle($title);
        
        // language. 言語
        $xml .= $this->outputLanguage($language);
        
        // keyword. キーワード
        $keyword = explode("|", $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY]."|".$baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
        for($ii=0; $ii<count($keyword); $ii++)
        {
            $xml .= $this->outputSubject($keyword[$ii]);
        }
        
        // URL
        $url = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI];
        $xml .= $this->outputIdentifier($url);
        
        return $xml;
    }
    
    /**
     * output NIIType
     *
     * @param string $niiType
     * @return string
     */
    private function outputNIIType($niiType)
    {
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
        $xml = $this->outputType($niiType);
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
            
            // get mapping info. マッピング情報取得
            $dcMap = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DOBLIN_CORE_MAPPING];
            if(strlen($dcMap)==0)
            {
                // when is not mapping info, not output. マッピング情報がなければスルー
                continue;
            }
            
            // get value par input type. 入力タイプ別に出力値を求める
            $inputType = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
            for($jj=0; $jj<count($itemAttr[$ii]); $jj++)
            {
                $value = RepositoryOutputFilter::attributeValue($itemAttrType[$ii], $itemAttr[$ii][$jj]);
                if(strlen($value) > 0)
                {
                    // Add JuNii2 ver3 R.Matsuura --start--
                    // when input type is biblio_info
                    if($inputType == "biblio_info")
                    {
                        $xml .= $this->outputAttributeValue("identifier", $value);
                        $xml .= $this->outputAttributeValue("date", $itemAttr[$ii][$jj]["date_of_issued"]);
                    }
                    else
                    {
                        // when is value, output. 値があれば出力
                        $xml .= $this->outputAttributeValue($dcMap, $value);
                    }
                    // Add JuNii2 ver3 R.Matsuura --end--
                }
            }
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
            // get detail url
            $refUrl = $this->RepositoryAction->getDetailUri($destItemId, $destItemNo);
            $xml .= $this->outputRelation($refUrl);
        }
        return $xml;
    }
    
    /**
     * output xml for mapping info.
     *
     * @param string $mapping
     * @param string $value
     * @return string
     */
    private function outputAttributeValue($mapping, $value)
    {
        $xml = '';
        
        switch ($mapping)
        {
            case RepositoryConst::DUBLIN_CORE_TITLE:
                $xml = $this->outputTitle($value);
                break;
            case RepositoryConst::DUBLIN_CORE_CREATOR:
                $xml = $this->outputCreator($value);
                break;
            case RepositoryConst::DUBLIN_CORE_SUBJECT:
                $xml = $this->outputSubject($value);
                break;
            case RepositoryConst::DUBLIN_CORE_DESCRIPTION:
                $xml = $this->outputDescription($value);
                break;
            case RepositoryConst::DUBLIN_CORE_PUBLISHER:
                $xml = $this->outputPublisher($value);
                break;
            case RepositoryConst::DUBLIN_CORE_CONTRIBUTOR:
                $xml = $this->outputContributor($value);
                break;
            case RepositoryConst::DUBLIN_CORE_DATE:
                $xml = $this->outputDate($value);
                break;
            case RepositoryConst::DUBLIN_CORE_TYPE:
                $xml = $this->outputType($value);
                break;
            case RepositoryConst::DUBLIN_CORE_FORMAT:
                $xml = $this->outputFormat($value);
                break;
            case RepositoryConst::DUBLIN_CORE_IDENTIFIER:
                $xml = $this->outputIdentifier($value);
                break;
            case RepositoryConst::DUBLIN_CORE_SOURCE:
                $xml = $this->outputSource($value);
                break;
            case RepositoryConst::DUBLIN_CORE_LANGUAGE:
                $xml = $this->outputLanguage($value);
                break;
            case RepositoryConst::DUBLIN_CORE_RELATION:
                $xml = $this->outputRelation($value);
                break;
            case RepositoryConst::DUBLIN_CORE_COVERAGE:
                $xml = $this->outputCoverage($value);
                break;
            case RepositoryConst::DUBLIN_CORE_RIGHTS:
                $xml = $this->outputRights($value);
                break;
            default:
                break;
        }
        return $xml;
    }
    
    /**
     * output "title" tag
     *
     * @param string $title
     * @return string
     */
    private function outputTitle($title)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_TITLE;
        return $this->outputElement($tag, $title);
    }
    
    /**
     * output "creator" tag
     *
     * @param string $creator
     * @return string
     */
    private function outputCreator($creator)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_CREATOR;
        return $this->outputElement($tag, $creator);
    }
    
    /**
     * subject "subject" tag
     *
     * @param string $subject
     * @return string
     */
    private function outputSubject($subject)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_SUBJECT;
        return $this->outputElement($tag, $subject);
    }
    
    /**
     * subject "description" tag
     *
     * @param string $description
     * @return string
     */
    private function outputDescription($description)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_DESCRIPTION;
        return $this->outputElement($tag, $description);
    }
    
    /**
     * subject "publisher" tag
     *
     * @param string $publisher
     * @return string
     */
    private function outputPublisher($publisher)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_PUBLISHER;
        return $this->outputElement($tag, $publisher);
    }
    
    /**
     * subject "contributor" tag
     *
     * @param string $contributor
     * @return string
     */
    private function outputContributor($contributor)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_PUBLISHER;
        return $this->outputElement($tag, $contributor);
    }
    
    /**
     * subject "date" tag
     *
     * @param string $date
     * @return string
     */
    private function outputDate($date)
    {
        // outputFlg
        $this->outputDateFlg = true;
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_DATE;
        $date = RepositoryOutputFilter::date($date);
        return $this->outputElement($tag, $date);
    }
    
    /**
     * subject "type" tag
     *
     * @param string $type
     * @return string
     */
    private function outputType($type)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_TYPE;
        return $this->outputElement($tag, $type);
    }
    
    /**
     * subject "format" tag
     *
     * @param string $format
     * @return string
     */
    private function outputFormat($format)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_FORMAT;
        return $this->outputElement($tag, $format);
    }
    
    /**
     * subject "identifier" tag
     *
     * @param string $identifier
     * @return string
     */
    private function outputIdentifier($identifier)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_IDENTIFIER;
        return $this->outputElement($tag, $identifier);
    }
    
    /**
     * subject "source" tag
     *
     * @param string $source
     * @return string
     */
    private function outputSource($source)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_SOURCE;
        return $this->outputElement($tag, $source);
    }
    
    /**
     * subject "language" tag
     *
     * @param string $language
     * @return string
     */
    private function outputLanguage($language)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_LANGUAGE;
        $language = RepositoryOutputFilter::language($language);
        if($language === RepositoryConst::ITEM_LANG_OTHER)
        {
            $language = '';
        }
        return $this->outputElement($tag, $language);
    }
    
    /**
     * subject "relation" tag
     *
     * @param string $relation
     * @return string
     */
    private function outputRelation($relation)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_RELATION;
        return $this->outputElement($tag, $relation);
    }
    
    /**
     * subject "coverage" tag
     *
     * @param string $coverage
     * @return string
     */
    private function outputCoverage($coverage)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_COVERAGE;
        return $this->outputElement($tag, $coverage);
    }
    
    /**
     * subject "rights" tag
     *
     * @param string $rights
     * @return string
     */
    private function outputRights($rights)
    {
        // output
        $tag = RepositoryConst::DUBLIN_CORE_PREFIX.RepositoryConst::DUBLIN_CORE_RIGHTS;
        return $this->outputElement($tag, $rights);
    }
    
    /**
     * return XML element.
     *
     * @param string $tag
     * @param string $value
     * @return string
     */
    private function outputElement($tag, $value)
    {
        $value = $this->RepositoryAction->forXmlChange($value);
        if(strlen($tag) == 0 || strlen($value) == 0)
        {
            return '';
        }
        $xml = "<$tag>$value</$tag>".self::LF;
        return $xml;
    }
    
    /**
     * output footer
     *
     * @return string
     */
    private function outputFooter()
    {
        $xml = '';
        $xml .= '</'.RepositoryConst::DUBLIN_CORE_START.'>'.self::LF;
        return $xml;
    }
}

?>