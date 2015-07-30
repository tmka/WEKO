<?php
// --------------------------------------------------------------------
//
// $Id: Lido.class.php 36348 2014-05-28 01:34:51Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/FormatAbstract.class.php';

class Repository_Oaipmh_Lido extends Repository_Oaipmh_FormatAbstract
{
    /*
     * 各タグ変数
     */
    private $domDocument = null;
    private $numObjectWorkTypeConceptID = 0;
    private $numObjectWorkTypeTerm = 0;
    private $numClassificationConceptID = 0;
    private $numClassificationTerm = 0;
    private $numReposotorySetName = 0;
    private $numReposotorySetLink = 0;
    private $numReposotorySetID = 0;
    private $numEventSetDisplayEvent = 0;
    private $numEventSetTypeTerm = 0;
    private $numEventSetActor = 0;
    private $numEventSetDisplayDate = 0;
    private $numEventSetEarliestDate = 0;
    private $numEventSetLatestDate = 0;
    private $numEventSetPeriodName = 0;
    private $numEventSetDisplayPlace = 0;
    private $numEventSetPlaceGml = 0;
    private $numEventSetMaterisalsTech = 0;
    private $numRecodInfoSetLink = 0;
    private $numRecodInfoSetDate = 0;
    
    private $item_language = 'ja';
    /*
     * construct
     */
    public function __construct($Session, $Db)
    {
        parent::Repository_Oaipmh_FormatAbstract($Session, $Db);
    }
    
    
    /**
     * init member for count
     */
    private function initMember()
    {
        $this->numObjectWorkTypeConceptID = 0;
        $this->numObjectWorkTypeTerm = 0;
        $this->numClassificationConceptID = 0;
        $this->numClassificationTerm = 0;
        $this->numReposotorySetName = 0;
        $this->numReposotorySetLink = 0;
        $this->numReposotorySetID = 0;
        $this->numEventSetDisplayEvent = 0;
        $this->numEventSetTypeTerm = 0;
        $this->numEventSetActor = 0;
        $this->numEventSetDisplayDate = 0;
        $this->numEventSetEarliestDate = 0;
        $this->numEventSetLatestDate = 0;
        $this->numEventSetPeriodName = 0;
        $this->numEventSetDisplayPlace = 0;
        $this->numEventSetPlaceGml = 0;
        $this->numEventSetMaterisalsTech = 0;
        $this->numRecodInfoSetLink = 0;
        $this->numRecodInfoSetDate = 0;
    }

    /**
     * output OAI-PMH metadata Tag format LIDO
     *
     * @param array $itemData $this->getItemData return
     * @return string xml
     */
    public function outputRecord($itemData)
    {
        $this->initMember();
        
        // confirm input item data
        if( !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]) || 
            !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE]) )
        {
            return '';
        }
        
        // initialize DOM Document
        $this->domDocument = new DOMDocument('1.0', 'UTF-8');
        
        // output header
        $domElement = $this->outputHeader();
        
        // output item Metadata
        $this->outputMetadata($itemData, $domElement);
        
        // comfirm required item
        $lidoRecId = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_SOURCE);
        $objectWorkTypes = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE);
        $existObjectWorkType = false;
        foreach($objectWorkTypes as $objectWorkType)
        {
            $objectWorkTypeChildren = $objectWorkType->childNodes;
            $item_one = $objectWorkTypeChildren->item(0);
            $item_two = $objectWorkTypeChildren->item(1);
            if($objectWorkTypeChildren->length === 2 && strlen($item_one->nodeValue) > 0 && strlen($item_two->nodeValue) > 0)
            {
                $existObjectWorkType = true;
                break;
            }
        }
        $titleSet = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_TITLE_SET);
        $recordId = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_ID);
        $recordType = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_TYPE);
        $recordSource = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_SOURCE);
        if( ($lidoRecId->length === 0) || 
            ($objectWorkTypes->length === 0) || 
            ($existObjectWorkType === false) || 
            ($titleSet->length === 0) || 
            ($recordId->length === 0) || 
            ($recordType->length === 0) || 
            ($recordSource->length === 0) )
        {
            return '';
        }
        
        // convert DOMDocument to XML string
        $xml = $this->domDocument->saveXML();
        
        // delete '<\?xml version="1.0" encoding="UTF-8"\?\>\n'
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml);
        
        // return
        return $xml;
    }
    
    
    /**
     * output header
     */
    private function outputHeader()
    {
        // create <lido:lidoWrap> tag
        // add attribute xmlns:lido="http://www.lido-schema.org"
        $domElement = $this->domDocument->createElementNS(RepositoryConst::LIDO_SCHEMA_ORG, RepositoryConst::LIDO_TAG_LIDO_WRAP);
        // add attribute xmlns:gml="http://www.opengis.net/gml"
        //$domElement = $this->domDocument->createElementNS(RepositoryConst::GML_SCHEMA, RepositoryConst::LIDO_TAG_LIDO_WRAP);
        // add attribute xmlns:xsi="http://www.w3.org/2001/XMLSchemainstance"
        $domElement->setAttribute('xmlns:xsi', RepositoryConst::LIDO_XML_SCHEMAINSTANCE);
        // add attribute xsi:schemaLocation="http://www.lido-schema.org http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd"
        $domElement->setAttribute('xsi:schemaLocation', RepositoryConst::LIDO_SCHEMA_ORG.' '.RepositoryConst::LIDO_SCHEMA_XSD.' '.RepositoryConst::GML_SCHEMA.' '.RepositoryConst::GML_SCHEMA_XSD);
        
        // add DOM Element to member DOM Document
        $this->domDocument->appendChild($domElement);
        
        // add element <lido:lido>
        $lidoTagElement = $this->domDocument->createElement(RepositoryConst::LIDO_TAG_LIDO_LIDO);
        $lidoElement = $domElement->appendChild($lidoTagElement);
        
        // return
        return $lidoElement;
    }
    
    /**
     * output metadata
     * 
     * @param array $itemData 
     * @param DOMElement $domElement
     */
    private function outputMetadata($itemData, $domElement)
    {
        // set language
        $this->item_language = $itemData['item'][0]['language'];
        if($this->item_language === RepositoryConst::ITEM_LANG_JA)
        {
            $this->item_language = RepositoryConst::LIDO_LANG_JAPANESE;
        }
        
        // set basic metadata
        // weko id
        $id_array = array('value' => $itemData['item'][0]['item_id'], 'language' => '');
        $this->outputTag(RepositoryConst::LIDO_TAG_LIDO_REC_ID, $id_array, $domElement);
        // title
        if(strlen($itemData['item'][0]['title']) >0)
        {
            $title_array = array('value' => $itemData['item'][0]['title'], 'language' => RepositoryConst::LIDO_LANG_JAPANESE);
            $this->outputTag(RepositoryConst::LIDO_FULLNAME_TITLESET, $title_array, $domElement);
        }
        // title_english
        if(strlen($itemData['item'][0]['title_english']) >0)
        {
            $title_english_array = array('value' => $itemData['item'][0]['title_english'], 'language' => RepositoryConst::LIDO_LANG_ENGLISH);
            $this->outputTag(RepositoryConst::LIDO_FULLNAME_TITLESET, $title_english_array, $domElement);
        }
        // search_key
        if(strlen($itemData['item'][0]['serch_key']) >0)
        {
            $search_key_array = array('value' => $itemData['item'][0]['serch_key'], 'language' => RepositoryConst::LIDO_LANG_JAPANESE);
            $this->outputTag(RepositoryConst::LIDO_FULLNAME_DISPLAYSUBJECT, $search_key_array, $domElement);
        }
        // search_key_english
        if(strlen($itemData['item'][0]['serch_key_english']) >0)
        {
            $search_key_english_array = array('value' => $itemData['item'][0]['serch_key_english'], 'language' => RepositoryConst::LIDO_LANG_ENGLISH);
            $this->outputTag(RepositoryConst::LIDO_FULLNAME_DISPLAYSUBJECT, $search_key_english_array, $domElement);
        }
        
        // uri
        $uri_array = array('value' => $itemData['item'][0]['uri'], 'language' => '');
        $this->outputTag(RepositoryConst::LIDO_FULLNAME_RECOURDINFOLINK, $uri_array, $domElement);
        // mod_date
        $mod_date_array = array('value' => $itemData['item'][0]['mod_date'], 'language' => '');
        $this->outputTag(RepositoryConst::LIDO_FULLNAME_RECORDMETADATADATE, $mod_date_array, $domElement);
        // set mapping info
        for($cnt = 0; $cnt < count($itemData['item_attr_type']); $cnt++)
        {
            $this->setAttributeValue($itemData['item_attr_type'][$cnt], $itemData['item_attr'][$cnt], $domElement);
        }
        
        $this->createGml();
    }
    
    /**
     * set attribute value
     * 
     * @param array $itemAttrType
     * @param array $itemAttr 
     * @param DOMElement $domElement
     */
    private function setAttributeValue($itemAttrType, $itemAttr, $domElement)
    {
        // get lido tag full name
        $tag_full_name = $itemAttrType['lido_mapping'];
        if(strlen($tag_full_name) < 1)
        {
            return;
        }
        
        foreach($itemAttr as $attribute)
        {
            $output_value = RepositoryOutputFilter::attributeValue($itemAttrType, $attribute);
            if(strlen($output_value) > 0)
            {
                $metadata_array = array();
                $metadata_array['value'] = $output_value;
                // check language
                if(isset($itemAttrType['display_lang_type']) && strlen($itemAttrType['display_lang_type']) > 0)
                {
                    if($itemAttrType['display_lang_type'] == 'japanese')
                    {
                        $metadata_array['language'] = RepositoryConst::LIDO_LANG_JAPANESE;
                    }
                    else
                    {
                        $metadata_array['language'] = RepositoryConst::LIDO_LANG_ENGLISH;
                    }
                }
                // output
                $this->outputTag($tag_full_name, $metadata_array, $domElement);
            }
        }
    }
    
    /**
     * output Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputTag($tag_name, $metadata_array, $domElement)
    {
        // when value or tag is empty , exit
        if(strlen($metadata_array['value']) < 1 || strlen($tag_name) < 1)
        {
            return;
        }
        $metadata_array['value'] = $this->RepositoryAction->forXmlChange($metadata_array['value']);
        
        // output tag 
        switch($tag_name)
        {
            case RepositoryConst::LIDO_FULLNAME_OBJECTWORKTYPE_CONCEPTID:
                $metadata_array['type'] = RepositoryConst::LIDO_ATTRIBUTE_TYPE_URI;
            case RepositoryConst::LIDO_FULLNAME_OBJECTWORKTYPE_TERM:
                $this->outputObjectWorkTypeTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_CLASSIFICATION_CONCEPTID:
                $metadata_array['type'] = RepositoryConst::LIDO_ATTRIBUTE_TYPE_URI;
            case RepositoryConst::LIDO_FULLNAME_CLASSIFICATION_TERM:
                $this->outputClassificationTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_REPOSITORYNAME_LEGALBODYNAME:
            case RepositoryConst::LIDO_FULLNAME_REPOSITORYNAME_LEGALBODYWEBLINK:
            case RepositoryConst::LIDO_FULLNAME_WORKID:
                $this->outputRepositorySetTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_DISPLAYEVENT:
            case RepositoryConst::LIDO_FULLNAME_EVENTTYPE:
            case RepositoryConst::LIDO_FULLNAME_DISPLAYACTORINROLE:
            case RepositoryConst::LIDO_FULLNAME_DISPLAYDATE:
            case RepositoryConst::LIDO_FULLNAME_EARLIESTDATE:
            case RepositoryConst::LIDO_FULLNAME_LATESTDATE:
            case RepositoryConst::LIDO_FULLNAME_PERIODNAME:
            case RepositoryConst::LIDO_FULLNAME_DISPLAYPLACE:
            case RepositoryConst::LIDO_FULLNAME_WORKID:
            case RepositoryConst::LIDO_FULLNAME_DISPLAYMATERIALSTECH:
                $this->outputEventSetTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_PLACE_GML:
                $this->outputNormalTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_RECOURDINFOLINK:
            case RepositoryConst::LIDO_FULLNAME_RECORDMETADATADATE:
                $this->outputRecordInfoSetTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_FULLNAME_LINKRESOURCE:
                $this->outputLinkResourceTag($tag_name, $metadata_array, $domElement);
                break;
            case RepositoryConst::LIDO_TAG_LIDO_REC_ID:
            case RepositoryConst::LIDO_FULLNAME_RECORDID:
                $metadata_array['type'] = RepositoryConst::LIDO_ATTRIBUTE_TYPE_URI;
                $this->outputNormalTag($tag_name, $metadata_array, $domElement);
                break;
            default:
                $this->outputNormalTag($tag_name, $metadata_array, $domElement);
                break;
            
        }
    }
    
    /**
     * output Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputNormalTag($tag_name, $metadata_array, $domElement)
    {
        // get tag names 
        $tags = explode('.', $tag_name);
        $domElem = $domElement;
        $count = 0;
        foreach($tags as $tag)
        {
            if($count == (count($tags) - 1))
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                // create new tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        
        // create tag
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output Object Work Type Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputObjectWorkTypeTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === (count($tags) - 2))
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        // create input tag
        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE);
        $createdTagNum = 0;
        if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_CONCEPT_ID)
        {
            $this->numObjectWorkTypeConceptID++;
            $createdTagNum = $this->numObjectWorkTypeConceptID;
        }
        else
        {
            $this->numObjectWorkTypeTerm++;
            $createdTagNum = $this->numObjectWorkTypeTerm;
        }
        if($elements->length < $createdTagNum)
        {
            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE, $domElem);
        }
        else
        {
            $domElem = $elements->item($createdTagNum - 1);
        }
        // create tag
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    
    /**
     * output Classification Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputClassificationTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === (count($tags) - 2))
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        // create input tag
        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_CLASSIFICATION);
        $createdTagNum = 0;
        if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_CONCEPT_ID)
        {
            $this->numClassificationConceptID++;
            $createdTagNum = $this->numClassificationConceptID;
        }
        else
        {
            $this->numClassificationTerm++;
            $createdTagNum = $this->numClassificationTerm;
        }
        
        if($elements->length < $createdTagNum)
        {
            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_CLASSIFICATION, $domElem);
        }
        else
        {
            $domElem = $elements->item($createdTagNum - 1);
        }
        // create tag
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output Repository Set Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputRepositorySetTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === 3)
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                // create new tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        // create input tag
        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_REPOSITORY_SET);
        $createdTagNum = 0;
        // count created tag's num
        if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_WORK_ID)
        {
            $this->numReposotorySetID++;
            $createdTagNum = $this->numReposotorySetID;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LEGAL_BODY_WEB_LINK)
        {
            $this->numReposotorySetLink++;
            $createdTagNum = $this->numReposotorySetLink;
        }
        else
        {
            $this->numReposotorySetName++;
            $createdTagNum = $this->numReposotorySetName;
        }
        
        if($elements->length < $createdTagNum)
        {
            // create <lido:repoisitorySet> tag
            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_REPOSITORY_SET, $domElem);
            
            if($tags[(count($tags)-1)] !== RepositoryConst::LIDO_TAG_WORK_ID)
            {
                // create <lido:repoisitoryName> tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_REPOSITORY_NAME, $domElem);
                
                if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_APPELLATION_VALUE)
                {
                    // create <lido:legalBodyName> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME, $domElem);
                }
            }
        }
        else
        {
            $domElem = $elements->item($createdTagNum - 1);
            if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LEGAL_BODY_WEB_LINK)
            {
                $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_REPOSITORY_NAME);
                $domElem = $elements->item(0);
            }
            else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_APPELLATION_VALUE)
            {
                $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME);
                $domElem = $elements->item(0);
            }
        }
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output Event Set Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputEventSetTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        // create <lido:eventWrap>
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === 2)
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                $domElem = $elements->item(0);
            }
            $count++;
        }
        
        // get created num of <lido:event> tag
        $max_created_num_of_event = max($this->numEventSetTypeTerm, $this->numEventSetActor, $this->numEventSetDisplayDate, 
                                        $this->numEventSetEarliestDate, $this->numEventSetLatestDate, $this->numEventSetPeriodName, 
                                        $this->numEventSetDisplayPlace, $this->numEventSetPlaceGml, $this->numEventSetMaterisalsTech);
        // get created num of <lido:date> tag
        $max_created_num_of_date = max($this->numEventSetEarliestDate, $this->numEventSetLatestDate);
        // get created num of <lido:eventDate> tag
        $max_created_num_of_eventdate = max($this->numEventSetDisplayDate, $this->numEventSetEarliestDate, $this->numEventSetLatestDate);
        // get created num of <lido:eventPlace> tag
        $max_created_num_of_eventplace = max($this->numEventSetDisplayPlace, $this->numEventSetPlaceGml);
        
        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_SET);
        $createdTagNum = 0;
        
        // count created tag's num
        if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_EVENT)
        {
            $this->numEventSetDisplayEvent++;
            $createdTagNum = $this->numEventSetDisplayEvent;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_TERM)
        {
            if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_EVENT_TYPE)
            {
                $this->numEventSetTypeTerm++;
                $createdTagNum = $this->numEventSetTypeTerm;
            }
            else
            {
                $this->numEventSetPeriodName++;
                $createdTagNum = $this->numEventSetPeriodName;
            }
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_ACTOR_IN_ROLE)
        {
            $this->numEventSetActor++;
            $createdTagNum = $this->numEventSetActor;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_DATE)
        {
            $this->numEventSetDisplayDate++;
            $createdTagNum = $this->numEventSetDisplayDate;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_EARLIEST_DATE)
        {
            $this->numEventSetEarliestDate++;
            $createdTagNum = $this->numEventSetEarliestDate;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LATEST_DATE)
        {
            $this->numEventSetLatestDate++;
            $createdTagNum = $this->numEventSetLatestDate;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_PLACE)
        {
            $this->numEventSetDisplayPlace++;
            $createdTagNum = $this->numEventSetDisplayPlace;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_GML)
        {
            $this->numEventSetPlaceGml++;
            $createdTagNum = $this->numEventSetPlaceGml;
        }
        else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_MATERIALS_TECH)
        {
            $this->numEventSetMaterisalsTech++;
            $createdTagNum = $this->numEventSetMaterisalsTech;
        }
        
        // create new eventSet
        if($elements->length < $createdTagNum)
        {
            // create <lido:eventSet> tag
            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_SET, $domElem);
            
            if($tags[(count($tags)-1)] !== RepositoryConst::LIDO_TAG_DISPLAY_EVENT)
            {
                // create <lido:event> tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT, $domElem);
                
                if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_EVENT_TYPE)
                {
                    // create <lido:evetType> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_TYPE, $domElem);
                }
                else if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_EVENT_ACTOR)
                {
                    // create <lido:eventActor> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_ACTOR, $domElem);
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_DATE ||
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_EARLIEST_DATE || 
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LATEST_DATE)
                {
                    // create <lido:eventDate> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_DATE, $domElem);
                    
                    if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_EARLIEST_DATE || 
                            $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LATEST_DATE)
                    {
                        // create <lido:date> tag
                        $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_DATE, $domElem);
                    }
                }
                else if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_PERIOD_NAME)
                {
                    // create <lido:periodName> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_PERIOD_NAME, $domElem);
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_PLACE || 
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_GML )
                {
                    // create <lido:eventPlace> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_PLACE, $domElem);
                    
                    if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_GML)
                    {
                        // create <lido:place> tag
                        $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_PLACE, $domElem);
                    }
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_MATERIALS_TECH)
                {
                    // create <lido:eventMaterialsTech> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_MATERIALS_TECH, $domElem);
                }
            }
        }
        else
        {
            $domElem = $elements->item($createdTagNum - 1);
        
            if($tags[(count($tags)-1)] !== RepositoryConst::LIDO_TAG_DISPLAY_EVENT)
            {

                if($max_created_num_of_event < $createdTagNum)
                {
                    // create <lido:event> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT, $domElem);
                }
                else
                {
                    $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT);
                    foreach($elements as $element)
                    {
                        $domElem = $element;
                        if($domElem !== null)
                        {
                            break;
                        }
                    }
                }
                
                if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_EVENT_TYPE)
                {
                    // create <lido:evetType> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_TYPE, $domElem);
                }
                else if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_EVENT_ACTOR)
                {
                    // create <lido:eventActor> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_ACTOR, $domElem);
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_DATE ||
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_EARLIEST_DATE || 
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LATEST_DATE)
                {
                    
                    if($max_created_num_of_eventdate < $createdTagNum)
                    {
                        // create <lido:eventDate> tag
                        $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_DATE, $domElem);
                    }
                    else
                    {
                        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_DATE);
                        foreach($elements as $element)
                        {
                            $domElem = $element;
                            if($domElem !== null)
                            {
                                break;
                            }
                        }
                    }
                    
                    if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_EARLIEST_DATE || 
                            $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_LATEST_DATE)
                    {
                        
                        if($max_created_num_of_date < $createdTagNum)
                        {
                            // create <lido:date> tag
                            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_DATE, $domElem);
                        }
                        else
                        {
                            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_DATE);
                            foreach($elements as $element)
                            {
                                $domElem = $element;
                                if($domElem !== null)
                                {
                                    break;
                                }
                            }
                        }
                    }
                }
                else if($tags[(count($tags)-2)] === RepositoryConst::LIDO_TAG_PERIOD_NAME)
                {
                    // create <lido:periodName> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_PERIOD_NAME, $domElem);
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_PLACE || 
                        $tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_GML )
                {
                    
                    if($max_created_num_of_eventplace < $createdTagNum)
                    {
                        // create <lido:eventPlace> tag
                        $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_PLACE, $domElem);
                    }
                    else
                    {
                        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_PLACE);
                        foreach($elements as $element)
                        {
                            $domElem = $element;
                            if($domElem !== null)
                            {
                                break;
                            }
                        }
                    }
                    
                    if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_GML)
                    {
                        // create <lido:place> tag
                        $node = $this->domDocument->createElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_PLACE);
                        $domElem->appendChild($node);
                        $domElem = $node;
                    }
                }
                else if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_DISPLAY_MATERIALS_TECH)
                {
                    // create <lido:eventMaterialsTech> tag
                    $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_EVENT_MATERIALS_TECH, $domElem);
                }
            }
        }
        
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output RecordInfoSet Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputRecordInfoSetTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === 2)
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                // create new tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        // create input tag
        $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_INFO_SET);
        $createdTagNum = 0;
        if($tags[(count($tags)-1)] === RepositoryConst::LIDO_TAG_RECORD_INFO_LINK)
        {
            $this->numRecodInfoSetLink++;
            $createdTagNum = $this->numRecodInfoSetLink;
        }
        else
        {
            $this->numRecodInfoSetDate++;
            $createdTagNum = $this->numRecodInfoSetDate;
        }
        if($elements->length < $createdTagNum)
        {
            // create new tag
            $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RECORD_INFO_SET, $domElem);
        }
        else
        {
            $domElem = $elements->item($createdTagNum - 1);
        }
        // create tag
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output LinkResource Tag
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputLinkResourceTag($tag_name, $metadata_array, $domElement)
    {
        // divide by dot(.)
        $tags = explode('.', $tag_name);
        // set root element
        $domElem = $domElement;
        
        $count = 0;
        foreach($tags as $tag)
        {
            if($count === (count($tags) - 2))
            {
                break;
            }
            $elements = $domElem->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.$tag);
            if($elements->length === 0)
            {
                // create new tag
                $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag, $domElem);
            }
            else
            {
                foreach($elements as $element)
                {
                    $domElem = $element;
                    if($domElem !== null)
                    {
                        break;
                    }
                }
            }
            $count++;
        }
        // create input tag
        $domElem = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_RESOURCE_REPRESENTATION, $domElem);
        
        $this->outputValue($tags[(count($tags)-1)], $metadata_array, $domElem);
    }
    
    /**
     * output value
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputValue($tag_name, $metadata_array, $domElement)
    {
        // when value is BLANK WORD
        if($metadata_array['value'] == $this->RepositoryAction->forXmlChange(RepositoryConst::BLANK_WORD))
        {
            return;
        }
        // if exist language value
        else
        {
            $node = $this->domDocument->createElement(RepositoryConst::LIDO_TAG_NAMESPACE.$tag_name, $metadata_array['value']);
            $newnode = $domElement->appendChild($node);
            if(isset($metadata_array['language']) && strlen($metadata_array['language']) > 0)
            {
                $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, $metadata_array['language']);
            }
            if(isset($metadata_array['type']) && strlen($metadata_array['type']) > 0)
            {
                $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_TYPE, $metadata_array['type']);
            }
        }
    }
    
    /**
     * create element and return new element
     * 
     * @param string $tag_name
     * @return DOMElement $new_element
     */
    private function createNodeElement($tag_name, $domElement)
    {
        $child = $this->domDocument->createElement($tag_name);
        $newnode = $domElement->appendChild($child);
        
        if($tag_name === RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA || 
           $tag_name === RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA )
        {
            $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, $this->item_language);
            
            // その他の言語対応語は以下を使用
            /*
            if($this->item_language === RepositoryConst::ITEM_LANG_OTHER)
            {
                $lang = $this->Session->getParameter("_lang");
                if($lang === 'japanese')
                {
                    $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, RepositoryConst::LIDO_LANG_JAPANESE);
                }
                else
                {
                    $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, RepositoryConst::LIDO_LANG_ENGLISH);
                }
            }
            else
            {
                $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, $this->item_language);
            }
            */
        }
        
        $domElement = $child;
        
        return $domElement;
    }
    
    /**
     * create gml tags
     * 
     * @param string $tag_name
     * @return DOMElement $new_element
     */
    private function createGml()
    {
        $places = $this->domDocument->getElementsByTagName(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_PLACE);
        
        foreach($places as $place)
        {
            $gmls = $place->childNodes;
            if($gmls->length > 1)
            {
                $gml_values = '';
                $length = $gmls->length;
                for($cnt = 0; $cnt < $length; $cnt++)
                {
                    $gml = $gmls->item(0);
                    $gml_values .= $gml->nodeValue.RepositoryConst::XML_LF;
                    $place->removeChild($gml);
                }
                $gmlElement = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_GML, $place);
                
                $node = $this->domDocument->createElementNS(RepositoryConst::GML_SCHEMA, RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_POLYGON);
                $newnode = $gmlElement->appendChild($node);
                $polygonElement = $node;
                
                //$polygonElement = $this->createNodeElement(RepositoryConst::GML_TAG_POLYGON, $gmlElement);
                $exteriorElement = $this->createNodeElement(RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_EXTERIOR, $polygonElement);
                $linearRingElement = $this->createNodeElement(RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_LINEAR_RING, $exteriorElement);
                $metadata_array = array('value' => $gml_values, 'language' => '');
                $this->outputGmlValue(RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_COORDINATES, $metadata_array, $linearRingElement);
            }
            else if($gmls->length === 1)
            {
                $gml = $gmls->item(0);
                $value = $gml->nodeValue;
                $place->removeChild($gml);
                $gmlElement = $this->createNodeElement(RepositoryConst::LIDO_TAG_NAMESPACE.RepositoryConst::LIDO_TAG_GML, $place);
                
                $node = $this->domDocument->createElementNS(RepositoryConst::GML_SCHEMA, RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_POINT);
                $newnode = $gmlElement->appendChild($node);
                $pointElement = $node;
                
                //$polygonElement = $this->createNodeElement(RepositoryConst::GML_TAG_POINT, $gmlElement);
                $metadata_array = array('value' => $value, 'language' => '');
                $this->outputGmlValue(RepositoryConst::GML_TAG_NAMESPACE.RepositoryConst::GML_TAG_POS, $metadata_array, $pointElement);
            }
        }
    }
    
    /**
     * output gml value
     * 
     * @param string $tag_name
     * @param array $metadata_array 
     * @param DOMElement $domElement
     */
    private function outputGmlValue($tag_name, $metadata_array, $domElement)
    {
        // when value is BLANK WORD
        if($metadata_array['value'] == $this->RepositoryAction->forXmlChange(RepositoryConst::BLANK_WORD))
        {
            return;
        }
        // if exist language value
        else
        {
            $node = $this->domDocument->createElement($tag_name, $metadata_array['value']);
            $newnode = $domElement->appendChild($node);
            if(isset($metadata_array['language']) && strlen($metadata_array['language']) > 0)
            {
                $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_LANG, $metadata_array['language']);
            }
            if(isset($metadata_array['type']) && strlen($metadata_array['type']) > 0)
            {
                $newnode->setAttribute(RepositoryConst::LIDO_ATTR_XML_TYPE, $metadata_array['type']);
            }
        }
    }
}
?>