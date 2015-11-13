<?php
// --------------------------------------------------------------------
//
// $Id: LearningObjectMetadata.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/oaipmh/format/FormatAbstract.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

class Repository_Oaipmh_LearningObjectMetadata extends Repository_Oaipmh_FormatAbstract
{
    /*
     * 各タグ変数
     */
    private $general = null;
    private $lifeCycle = null;
    private $metaMetadate = null;
    private $technical = null;
    private $educational = array();
    private $rights = null;
    private $relation = array();
    private $annotation = array();
    private $classification = array();

    // const xml value
    const LOM_VALUE_SOURCE = 'LOMv1.0';
    
    
    /*
     * コンストラクタ
     */
    public function __construct($Session, $Db){
        parent::Repository_Oaipmh_FormatAbstract($Session, $Db);
    }

    /**
     * output OAI-PMH metadata Tag format LOM
     *
     * @param array $itemData $this->getItemData return
     * @return string xml
     */
    public function outputRecord($itemData)
    {
        if( !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]) || 
            !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE]) )
         //   基本情報以外のメタデータが存在しない場合に判定に入ってしまうことを防ぐためコメントアウト
         //   !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE]) || 
         //   !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]))
        {
            return '';
        }
        
        // new data class.
        $this->general = new Repository_Oaipmh_LOM_General($this->RepositoryAction);
        $this->lifeCycle = new Repository_Oaipmh_LOM_LifeCycle($this->RepositoryAction);
        $this->metaMetadate = new Repository_Oaipmh_LOM_MetaMetadata($this->RepositoryAction);
        $this->technical = new Repository_Oaipmh_LOM_Technical($this->RepositoryAction);
        //$this->educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
        $this->educational = array();
        $this->rights = new Repository_Oaipmh_LOM_Rights($this->RepositoryAction);
        $this->relation = array();
        $this->annotation = array();
        $this->classification = array();
        
        
        //1.基本情報設定処理
        //$this->setBaseData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0]);
        $this->setBaseData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]);
        
        //2. マッピング情報設定処理
        $this->setMappingInfo($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]);
        
        //3. リファレンス設定処理
        if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]))
        {
            $this->setReference($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]);
        }
        
        //4. 初期化
        $xml = '';
        
        
        //5. header出力処理
        $xml .= $this->outputHeader();
        
        //6. metadata出力処理
        $xml .= $this->general->output();
        $xml .= $this->lifeCycle->output();
        $xml .= $this->metaMetadate->output();
        $xml .= $this->technical->output();
        
        for($ii=0;$ii<count($this->educational);$ii++){
            $xml .= $this->educational[$ii]->output();
        }
        
        $xml .= $this->rights->output();
        
        for($ii=0;$ii<count($this->relation);$ii++){
            $xml .= $this->relation[$ii]->output();
        }
        
        for($ii=0;$ii<count($this->annotation);$ii++){
            $xml .= $this->annotation[$ii]->output();
        }
        
        for($ii=0;$ii<count($this->classification);$ii++){
            $xml .= $this->classification[$ii]->output();
        }
        
        //7. footer出力処理
        $xml .= $this->outputFooter();
        
        return $xml;
        
    }
    
    /*
     * 基本情報設定処理
     * @param array $item
     */
    private function setBaseData($item){
        //1レコードのみ
        if(count($item) != 1){
            return false;
        }
        $itemData = $item[0];
        
        //言語チェック
        $language = RepositoryOutputFilter::language($itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_LANGUAGE]);

        //タイトル
        $title = '';
        $titleLang = $language;
        //日本語
        if($language == RepositoryConst::ITEM_LANG_JA){
            if(strlen($itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0)
            {
                $title = $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
            }
            else
            {
                $title = $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                $titleLang = '';
            }
        }
        //英語
        else {
            if(strlen($itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0)
            {
                $title = $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
            }
            else
            {
                $title = $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                $titleLang = '';
            }
        }
        $this->general->addTitle(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $title, $titleLang));
        
        //language
        $this->general->addLanguage("$language");
        
        //URI
        $uri = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, 
                                                    $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI],
                                                    RepositoryConst::LOM_URI);
        $this->general->addIdentifier($uri);
        
        //キーワード
        $keyword = explode("|", $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY]."|".$itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
        for($ii=0; $ii<count($keyword); $ii++)
        {
            $this->general->addKeyword(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $keyword[$ii]));
        }
        
        return true;
    }
    
    /*
     * マッピング情報設定処理
     * @param array $mapping
     * @param array $metadata
     */
    private function setMappingInfo($mapping, $metadata){
      for($ii=0;$ii<count($mapping);$ii++){
          if($mapping[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1)
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
          
          $lomMap = $mapping[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
          
          if(preg_match('/^general/', $lomMap)==1){
              $this->setGeneral($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^lifeCycle/', $lomMap)==1){
              $this->setLifeCycle($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^metaMetadata/', $lomMap)==1){
              $this->setMetaMetadata($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^technical/', $lomMap)==1){
              $this->setTechnical($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^educational/', $lomMap)==1){
              $this->setEducational($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^rights/', $lomMap)==1){
              $this->setRights($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^relation/', $lomMap)==1){
              $this->setRelation($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^annotation/', $lomMap)==1){
              $this->setAnnotation($mapping[$ii], $metadata[$ii]);
          }else if(preg_match('/^classification/', $lomMap)==1){
              $this->setClassification($mapping[$ii], $metadata[$ii]);
          }else{
              //何もしない
          }
      }
    }
    
    /*
     * setGeneral
     * 
     */
    private function setGeneral($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $lomMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        
        for($ii=0; $ii<count($metadata_item); $ii++){
            
            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);
            
            switch($lomMap)
            {
                case RepositoryConst::LOM_MAP_GNRL_IDENTIFER:
                    $this->setGeneralIdentifier($mapping_item, $value);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_TITLE:
                    break;
                case RepositoryConst::LOM_MAP_GNRL_LANGUAGE:
                    $this->general->addLanguage($value);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_DESCRIPTION:
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $this->general->addDescription($description);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_KEYWORD:
                    $this->general->addKeyword(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value));
                    break;
                case RepositoryConst::LOM_MAP_GNRL_COVERAGE:
                    $coverage = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $this->general->addCoverage($coverage);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_STRUCTURE:
                    $structure = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $this->general->addStructure($structure);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_AGGREGATION_LEVEL:
                    $aggregationLevel = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $this->general->addAggregationLevel($aggregationLevel);
                    break;
                default :
                    break;
            }
        }
    }
    
    /*
     * GeneralIdentifierの個別設定処理
     * @param array $mapping_item
     * @param string $value
     * 
     */
    private function setGeneralIdentifier($mapping_item, $value){
        $attri_name = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME];
        $input_type = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
        $language  = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        if($input_type == RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO)
        {
            $biblio = explode("||", $value);
            // jtitle
            $identifier_jtitle = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[0], RepositoryConst::LOM_JTITLE);
            $this->general->addIdentifier($identifier_jtitle);
            // volume
            $identifier_volume = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[1], RepositoryConst::LOM_VOLUME);
            $this->general->addIdentifier($identifier_volume);
            // issue
            $identifier_issue = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[2], RepositoryConst::LOM_ISSUE);
            $this->general->addIdentifier($identifier_issue);
            // spage
            $identifier_spage = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[3], RepositoryConst::LOM_SPAGE);
            $this->general->addIdentifier($identifier_spage);
            // epage
            $identifier_epage = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[4], RepositoryConst::LOM_EPAGE);
            $this->general->addIdentifier($identifier_epage);
            // dateofissued
            $identifier_dateofissued = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $biblio[5], RepositoryConst::LOM_DATE_OF_ISSUED);
            $this->general->addIdentifier($identifier_dateofissued);
        }
        else if($attri_name == RepositoryConst::LOM_URI || $attri_name == RepositoryConst::LOM_ISSN 
           || $attri_name == RepositoryConst::LOM_NCID || $attri_name == RepositoryConst::LOM_TEXTVERSION )
        {
            $identifier = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value, $attri_name);
            $this->general->addIdentifier($identifier);
        }else{
            $identifier = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value);
            $this->general->addIdentifier($identifier);
        }
    }
    
    /* 
     * setLifeCycle
     * 
     * @param array $mapping_item
     * @param array $metadata_item
     */
    private function setLifeCycle($mapping_item, $metadata_item){
        $lomMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        
        //version
        if($lomMap == RepositoryConst::LOM_MAP_LFCYCL_VERSION){
            for($ii=0;$ii<count($metadata_item);$ii++)
            {
                $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii]);
                $this->lifeCycle->addVersion(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value));
            }
        }
        //status
        else if($lomMap == RepositoryConst::LOM_MAP_LFCYCL_STATUS)
        {
            for($ii=0;$ii<count($metadata_item);$ii++)
            {
                $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii]);
                $this->lifeCycle->addStatus(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value));
            }
            
        }
        //publishDateの場合
        else if($lomMap == RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISH_DATE){
            for($ii=0;$ii<count($metadata_item);$ii++){
                $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii]);
                
                $contribute = new Repository_Oaipmh_LOM_Contribute($this->RepositoryAction);
                $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_PUBLISH_DATE));
                $date = new Repository_Oaipmh_LOM_DateTime($this->RepositoryAction);
                $date->setDateTime($value);
                
                $contribute->addDate($date);
                $this->lifeCycle->addContribute($contribute, 1);
            }
        }
        else if($lomMap == RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE)
        {
            $roleValue = str_replace("lifecyclecontribute", "", strtolower($lomMap));
            $this->setLifeCycleContribute($mapping_item, $metadata_item, 0, $roleValue);
        }
        //author/publicher/initiator...
        else if(preg_match("/^lifeCycleContribute/", $lomMap)==1)
        {
            $roleValue = str_replace("lifecyclecontributerole", "", strtolower($lomMap));
            $roleValue = RepositoryOutputFilterLOM::lyfeCycleContributeRole($roleValue);
            
            $this->setLifeCycleContribute($mapping_item, $metadata_item, 1, $roleValue);
        }
    }
    
    /*
     * setLifeCycleContribute
     * 
     * @param array $mapping
     * @param array $metadata
     * @param string $roleValue
     */
    private function setLifeCycleContribute($mapping, $metadata, $flag, $roleValue=''){
        
        $contribute = new Repository_Oaipmh_LOM_Contribute($this->RepositoryAction);
        
        $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $roleValue));
        
        //entryは複数出力する
        for($ii=0;$ii<count($metadata);$ii++){
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            $contribute->addEntry($value);
        }
        $this->lifeCycle->addContribute($contribute, $flag);
    }
    
    
    /*
     * setMetaMetadata
     * @param array $mapping
     * @param string $roleValue
     */
    private function setMetaMetadata($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        
        // metaMetadataContributeで始まるタグの場合
        if(preg_match('/^metaMetadataContribute/', $lomMap)==1){
            $contribute = new Repository_Oaipmh_LOM_Contribute($this->RepositoryAction);
            
            //roleはcreator/validatorのみ
            if($lomMap == RepositoryConst::LOM_MAP_MTMTDT_CONTRIBUTE)
            {
                $roleValue = "";
                $flag = 0;
            } else {
                $roleValue = str_replace("metametadatacontributerole", "", strtolower($lomMap));
                $flag = 1;
            }
            $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $roleValue));
            
            //entryは複数表示する
            for($ii=0; $ii<count($metadata); $ii++)
            {
                $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                $contribute->addEntry($value);
            }
            
            $this->metaMetadate->addContribute($contribute, $flag);
        }
        // それ以外
        else{
            for($ii=0; $ii<count($metadata); $ii++)
            {
                $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                switch($lomMap)
                {
                    case RepositoryConst::LOM_MAP_MTMTDT_IDENTIFER:
                        $identifier = new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value);
                        $this->metaMetadate->addIdentifier($identifier);
                        break;
                    case RepositoryConst::LOM_MAP_MTMTDT_METADATA_SCHEMA:
                        $this->metaMetadate->addMetadataSchema(self::LOM_VALUE_SOURCE);
                        break;
                    case RepositoryConst::LOM_MAP_MTMTDT_LANGUAGE:
                        $this->metaMetadate->addLanguage($value);
                        break;
                    default :
                        break;
                }
            }
        }
    }
    
    /*
     * setTechnical
     * @param array $mapping
     * @param array $metadata
     */
    private function setTechnical($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];

        for($ii=0; $ii<count($metadata); $ii++){
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            switch($lomMap){
                case RepositoryConst::LOM_MAP_TCHNCL_FORMAT:
                    $this->technical->addFormat($value);
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_SIZE:
                    $this->technical->addSize($value);
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_LOCATION:
                    $this->technical->addLocation($value);
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_INSTALLATION_REMARKS:
                    $langstring = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $this->technical->addInstallationRemarks($langstring);
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_OTHER_PLATFORM_REQUIREMENTS:
                    $langstring = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $this->technical->addOtherPlatformRequirements($langstring);
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_DURATION:
                    $lang = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, '');
                    //$duration = new Repository_Oaipmh_LOM_Duration($this->RepositoryAction, $value, $lang);
                    $duration = new Repository_Oaipmh_LOM_Duration($this->RepositoryAction);
                    $duration->setDescription($lang);
                    $duration->setDuration($value);
                    
                    $this->technical->addDuration($duration);
                    break;
                // ----- technicalRequirement ----- 
                case RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE:
                    $type = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                      $this->technical->addOrComposite(RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE, $type);
                      
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME:
                    $name = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $this->technical->addOrComposite(RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME, $name);
                      
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION:
                      $this->technical->addOrComposite(RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION, $value);
                      
                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION:
                      $this->technical->addOrComposite(RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION, $value);
                      
                    break;
                default:break;
            }
            
        }
    }
    
    /*
     * setEducational
     * @param array $mapping
     * @param string $roleValue
     */
    private function setEducational($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        $index = -1;
        if($lomMap == RepositoryConst::LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE
         || $lomMap == RepositoryConst::LOM_MAP_EDUCTNL_INTENDED_END_USER_ROLE
         || $lomMap == RepositoryConst::LOM_MAP_EDUCTNL_CONTEXT
         || $lomMap == RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_AGE_RANGE
         || $lomMap == RepositoryConst::LOM_MAP_EDUCTNL_DESCRIPTION
         || $lomMap == RepositoryConst::LOM_MAP_EDUCTNL_LANGUAGE)
        {
            $index = $this->getInsertIndexEducational($lomMap);
        }
        
        for($ii=0; $ii<count($metadata); $ii++)
        {
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            switch ($lomMap){
                //Repository_Oaipmh_LOM_Vocabulary型
                case RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_TYPE:
                    $index = $this->getInsertIndexEducational(RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_TYPE);
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addInteractivityType($vocabulary);
                        array_push($this->educational, $educational);
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addInteractivityType($vocabulary);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE:
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addLearningResourceType($vocabulary);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addLearningResourceType($vocabulary);
                    }
                    
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_LEVEL:
                    $index = $this->getInsertIndexEducational(RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_LEVEL);
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addInteractivityLevel($vocabulary);
                        array_push($this->educational, $educational);
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addInteractivityLevel($vocabulary);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_SEMANTIC_DENSITY:
                    $index = $this->getInsertIndexEducational(RepositoryConst::LOM_MAP_EDUCTNL_SEMANTIC_DENSITY);
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addSemanticDensity($vocabulary);
                        array_push($this->educational, $educational);
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addSemanticDensity($vocabulary);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_INTENDED_END_USER_ROLE:
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addIntendedEndUserRole($vocabulary);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addIntendedEndUserRole($vocabulary);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_CONTEXT:
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addContext($vocabulary);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addContext($vocabulary);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_DIFFICULTY:
                    $index = $this->getInsertIndexEducational(RepositoryConst::LOM_MAP_EDUCTNL_DIFFICULTY);
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addDifficulty($vocabulary);
                        array_push($this->educational, $educational);
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addDifficulty($vocabulary);
                    }
                    
                    break;
                    
                //Repository_Oaipmh_LOM_LangString型
                case RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_AGE_RANGE:
                    $typical = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addTypicalAgeRange($typical);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addTypicalAgeRange($typical);
                    }
                    break;
                case RepositoryConst::LOM_MAP_EDUCTNL_DESCRIPTION:
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addDescription($description);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addDescription($description);
                    }
                    break;
                    
                //Repository_Oaipmh_LOM_Duration型
                case RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_LEARNING_TIME:
                    $index = $this->getInsertIndexEducational(RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_LEARNING_TIME);
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, '', $language);
                    $typicalLearningTime = new Repository_Oaipmh_LOM_Duration($this->RepositoryAction);
                    $typicalLearningTime->setDescription($description);
                    $typicalLearningTime->setDuration($value);
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addTypicalLearningTime($typicalLearningTime);
                        array_push($this->educational, $educational);
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addTypicalLearningTime($typicalLearningTime);
                    }
                    break;
                    
                //直接追加
                case RepositoryConst::LOM_MAP_EDUCTNL_LANGUAGE:
                    //新規のとき
                    if($index == -1){
                        $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
                        $educational->addLanguage($value);
                        array_push($this->educational, $educational);
                        $index = count($this->educational) - 1;
                    }
                    //更新のとき
                    else{
                        $this->educational[$index]->addLanguage($value);
                    }
                    break;
                default:
                    break;
            }
        }
        
    }
    /*
     * getInsertIndexEducational
     * 配列Educationalに格納すべきインデックスを取得する
     */
    public function getInsertIndexEducational($element){
        $index = 0;
        
        //はじめていれる場合
        if(count($this->educational) == 0){
            
            return -1;
        }
        
        $ii=0;
        //すでにはいっている場合
        for($ii=0;$ii<count($this->educational);$ii++)
        {
            if($element == RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_TYPE){
                
                $inter_type = $this->educational[$ii]->getInteractivityType();
                //入っていなかったらここに入れる
                if($inter_type == null || strlen($inter_type->getValue())){
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE){
                $learning_resource_type = $this->educational[$ii]->getLearningResourceType();
                //入っていなかったらここに入れる
                if(count($learning_resource_type)==0){
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_LEVEL){
                $inter_level = $this->educational[$ii]->getInteractivityLevel();
                //入っていなかったらここに入れる
                if($inter_level == null || strlen($inter_level->getValue())){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_SEMANTIC_DENSITY){
                $semantic = $this->educational[$ii]->getSemanticDensity();
                //入っていなかったらここに入れる
                if($semantic == null || strlen($semantic->getValue())){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_INTENDED_END_USER_ROLE){
                $endUserRole = $this->educational[$ii]->getIntendedEndUserRole();
                //入っていなかったらここに入れる
                if(count($endUserRole)==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_CONTEXT){
                $context = $this->educational[$ii]->getContext();
                //入っていなかったらここに入れる
                if(count($context)==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_DIFFICULTY){
                $difficulty = $this->educational[$ii]->getDifficulty();
                //入っていなかったらここに入れる
                if($difficulty == null || strlen($difficulty->getValue())){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_AGE_RANGE){
                $typicalAgeRange = $this->educational[$ii]->getTypicalAgeRange();
                //入っていなかったらここに入れる
                if(count($typicalAgeRange)==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_DESCRIPTION){
                $description = $this->educational[$ii]->getDescription();
                //入っていなかったらここに入れる
                if(count($description)==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_LEARNING_TIME){
                $rypical_learning_time = $this->educational[$ii]->getTypicalLearningTime();
                //入っていなかったらここに入れる
                if($rypical_learning_time == null || strlen($rypical_learning_time->getDuration())==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_EDUCTNL_LANGUAGE){
                $lang = $this->educational[$ii]->getLanguage();
                //入っていなかったらここに入れる
                if(count($lang)==0 ){
                    $index = $ii;
                    break;
                }
            }
            
            
            else {
                return;
            }
        }
        
        //どこにも入るところがない場合
        if($ii!=0 && $ii == count($this->educational)){
            //新しく作成する
            $educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
            array_push($this->educational, $educational);
            return $ii;
        }
        
        return $index;
    }
    
    /*
     * setRights
     * @param array $mapping
     * @param array $metadata
     */
    private function setRights($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        for($ii=0; $ii<count($metadata); $ii++)
        {
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            switch ($lomMap){
                //Repository_Oaipmh_LOM_Vocabulary型
                case RepositoryConst::LOM_MAP_RGHTS_COST:
                    $cost = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $this->rights->addCost($cost);
                    break;
                case RepositoryConst::LOM_MAP_RGHTS_COPYRIGHT_AND_OTHER_RESTRICTIONS:
                    $copy = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $this->rights->addCopyrightAndOtherRestrictions($copy);
                    break;
                    
                //Repository_Oaipmh_LOM_LangString型
                case RepositoryConst::LOM_MAP_RGHTS_DESCRIPTION:
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $this->rights->addDescription($description);
                    break;
                    
                default:
                    break;
            }
        }
        
    }
    /*
     * setRelation
     * @param array $mapping
     * @param array $metadata
     */
    private function setRelation($mapping, $metadata)
    {
        $relation = new Repository_Oaipmh_LOM_Relation($this->RepositoryAction);
        
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        switch ($lomMap){
            case RepositoryConst::LOM_MAP_RLTN:
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                //pmid,doiのcatalogはデータ落ちする
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_PART_OF:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_PART_OF);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_PART_OF:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_HAS_PART);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_VERSION_OF:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_VERSION_OF);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_VERSION:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_HAS_VERSION);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_FORMAT_OF:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_FORMAT_OF);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_FORMAT:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_HAS_FORMAT);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_REFERENCES:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_REFERENCES);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_REFERENCED_BY:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_REFERENCED_BY);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_BASED_ON:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_BASED_ON);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value, RepositoryConst::LOM_IS_BASED_ON));
                    
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_BASIS_FOR:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_BASIS_FOR);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value, RepositoryConst::LOM_IS_BASIS_FOR));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_REQUIRES:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_REQUIRES);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_REQUIRED_BY:
            
                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, RepositoryConst::LOM_IS_REQUIRESD_BY);
                $relation->addKind($vocabulary);
                
                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
                
                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }
                
                $relation->addResource($resource);
                
            default:
                break;
        }
        
        array_push($this->relation, $relation);
        
    }
    /*
     * setAnnotation
     */
    private function setAnnotation($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        for($ii=0; $ii<count($metadata); $ii++)
        {
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            
            switch ($lomMap){
                case RepositoryConst::LOM_MAP_ANNTTN_ENTITY:
                    
                    $index = $this->getInsertIndexAnnotation(RepositoryConst::LOM_MAP_ANNTTN_ENTITY);
                    //新規
                    if($index == -1){
                        $annotation = new Repository_Oaipmh_LOM_Annotation($this->RepositoryAction);
                        $annotation->addEntity($value);
                        array_push($this->annotation, $annotation);
                    }
                    //上書き
                    else{
                        $this->annotation[$index]->addEntity($value);
                    }
                    break;
                case RepositoryConst::LOM_MAP_ANNTTN_DATE:
                    $langstring = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, '', $language);
                    //$date = new Repository_Oaipmh_LOM_DateTime($this->RepositoryAction, $value, $langstring);
                    $date = new Repository_Oaipmh_LOM_DateTime($this->RepositoryAction);
                    $date->setDateTime($value);
                    $date->setDescription($langstring);
                    
                    $index = $this->getInsertIndexAnnotation(RepositoryConst::LOM_MAP_ANNTTN_DATE);
                    //新規
                    if($index == -1){
                        $annotation = new Repository_Oaipmh_LOM_Annotation($this->RepositoryAction);
                        $annotation->addDate($date);
                        array_push($this->annotation, $annotation);
                    }
                    //上書き
                    else{
                        $this->annotation[$index]->addDate($date);
                    }
                    break;
                case RepositoryConst::LOM_MAP_ANNTTN_DESCRIPTION:
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    
                    $index = $this->getInsertIndexAnnotation(RepositoryConst::LOM_MAP_ANNTTN_DESCRIPTION);
                    //新規
                    if($index == -1){
                        $annotation = new Repository_Oaipmh_LOM_Annotation($this->RepositoryAction);
                        $annotation->addDescription($description);
                        array_push($this->annotation, $annotation);
                    }
                    //上書き
                    else{
                        $this->annotation[$index]->addDescription($description);
                    }
                    
                    break;
                default:
                    break;
            }
        }
        
    }
    
    /*
     * getInsertIndexAnnotation
     * 配列Annotationに格納すべきインデックスを取得する
     * 
     */
    private function getInsertIndexAnnotation($element){
        $index = 0;
        
        //はじめていれる場合
        if(count($this->annotation) == 0){
            
            return -1;
        }
        
        $ii=0;
        //すでにはいっている場合
        for($ii=0;$ii<count($this->annotation);$ii++)
        {
            if($element == RepositoryConst::LOM_MAP_ANNTTN_ENTITY){
                $entity = $this->annotation[$ii]->getEntity();
                //入っていなかったらここに入れる
                if($entity == null || strlen($entity) == 0){
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_ANNTTN_DATE){
                $date = $this->annotation[$ii]->getDate();
                //入っていなかったらここに入れる
                if($date == null){
                    $index = $ii;
                    break;
                }
                else if(strlen($date->getDateTime())==0)
                {
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_ANNTTN_DESCRIPTION){
                $description = $this->annotation[$ii]->getDescription();
                //入っていなかったらここに入れる
                if($description == null){
                    $index = $ii;
                    break;
                }
                else if(strlen($description->getString())==0)
                {
                    $index = $ii;
                    break;
                }
            }else {
                return;
            }
        }
        
        //どこにも入るところがない場合
        if($ii!=0 && $ii == count($this->annotation)){
            //新しく作成する
            $annotation = new Repository_Oaipmh_LOM_Annotation($this->RepositoryAction);
            array_push($this->annotation, $annotation);
            return $ii;
        }
        
        return $index;
    }
    
    
    
    /*
     * setClassification
     */
    private function setClassification($mapping, $metadata)
    {
        $lomMap = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
        $language = $mapping[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        
        $index = -1;
        if($lomMap == RepositoryConst::LOM_MAP_CLSSFCTN_KEYWORD || RepositoryConst::LOM_MAP_CLSSFCTN_TAXON)
        {
            $index = $this->getInsertIndexClassification($lomMap);
        }
        
        for($ii=0; $ii<count($metadata); $ii++)
        {
            $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
            
            switch ($lomMap){
                case RepositoryConst::LOM_MAP_CLSSFCTN_PURPOSE:
                    $purpose = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $value);
                    $index = $this->getInsertIndexClassification(RepositoryConst::LOM_MAP_CLSSFCTN_PURPOSE);
                    //新規
                    if($index == -1){
                        $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
                        $classification->addPurpose($purpose);
                        array_push($this->classification, $classification);
                    }
                    //更新
                    else{
                        $this->classification[$index]->addPurpose($purpose);
                    }
                    
                    break;
                    
                case RepositoryConst::LOM_MAP_CLSSFCTN_DESCRIPTION:
                    $description = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    $index = $this->getInsertIndexClassification(RepositoryConst::LOM_MAP_CLSSFCTN_DESCRIPTION);
                    //新規
                    if($index == -1){
                        $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
                        $classification->addDescription($description);
                        array_push($this->classification, $classification);
                    }
                    //更新
                    else{
                        $this->classification[$index]->addDescription($description);
                    }
                    break;
                    
                case RepositoryConst::LOM_MAP_CLSSFCTN_KEYWORD:
                    $keyword = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value, $language);
                    //新規
                    if($index == -1){
                        $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
                        $classification->addKeyword($keyword);
                        array_push($this->classification, $classification);
                        $index = count($this->classification) - 1;
                    }
                    //更新
                    else{
                        $this->classification[$index]->addKeyword($keyword);
                    }
                    break;
                    
                case RepositoryConst::LOM_MAP_CLSSFCTN_TAXON_PATH_SOURCE:
                    $index = $this->getInsertIndexClassification(RepositoryConst::LOM_MAP_CLSSFCTN_TAXON_PATH_SOURCE);
                    //新規
                    if($index == -1){
                        $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
                        $taxonPath = new Repository_Oaipmh_LOM_TaxonPath($this->RepositoryAction);
                        $taxonPath->addSource(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value));
                        $classification->addTaxonPath($taxonPath);
                        array_push($this->classification, $classification);
                    }
                    //更新
                    else{
                        $source = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value);
                        $this->classification[$index]->setTaxonPathSource($source);
                    }
                    break;
                    
                case RepositoryConst::LOM_MAP_CLSSFCTN_TAXON:
                    //新規
                    if($index == -1){
                        $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
                        $taxon = new Repository_Oaipmh_LOM_TaxonPath($this->RepositoryAction);
                        $entry = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value);
                        
                        $child_taxon = new Repository_Oaipmh_LOM_Taxon($this->RepositoryAction);
                        $child_taxon->setEntry($entry);
                        $child_taxon->setId('');
                        $taxon->addTaxon($child_taxon);
                        $classification->addTaxonPath($taxon);
                        array_push($this->classification, $classification);
                        $index = count($this->classification) - 1;
                    }
                    //更新
                    else{
                        $entry = new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $value);
                        $this->classification[$index]->setTaxonPathEntry($entry);
                    }
                    break;
                    
                default:
                    break;
            }
        }
    }
    /*
     * getInsertIndexClassification
     * 配列Classificationに格納すべきインデックスを取得する
     */
    private function getInsertIndexClassification($element){
        $index = 0;
        
        if(count($this->classification) == 0){
            return -1;
        }
        
        $ii = 0;
        
        for($ii=0; $ii<count($this->classification); $ii++)
        {
            if($element == RepositoryConst::LOM_MAP_CLSSFCTN_PURPOSE){
                $purposeVal = $this->classification[$ii]->getPurposeValue();
                if(strlen($purposeVal)==0){
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_CLSSFCTN_DESCRIPTION){
                $description = $this->classification[$ii]->getDescriptionString();
                if(strlen($description)==0){
                    $index = $ii;
                    break;
                }
                
            }else if($element == RepositoryConst::LOM_MAP_CLSSFCTN_KEYWORD){
                $keyword = $this->classification[$ii]->getKeyword();
                if(count($keyword)==0){
                    $index = $ii;
                    break;
                }
            }else if($element == RepositoryConst::LOM_MAP_CLSSFCTN_TAXON_PATH_SOURCE){
                
                $taxonPath = $this->classification[$ii]->getTaxonPathSource();
                if($taxonPath == null || strlen($taxonPath)==0){
                    $index = $ii;
                    break;
                }
               
            }else if($element == RepositoryConst::LOM_MAP_CLSSFCTN_TAXON){
                $taxonPath = $this->classification[$ii]->getTaxonPathCount();
                if($taxonPath == null || $taxonPath == 0){
                    $index = $ii;
                    break;
                }
                
            }
            
        }
        
        //値をいれる場所がないので新規作成
        if($ii!=0 && $ii == count($this->classification)){
            //作成
            $classification = new Repository_Oaipmh_LOM_Classification($this->RepositoryAction);
            array_push($this->classification, $classification);
        }
        
        return $index;
    }
    
    
    /* 
     * setReference
     */
    private function setReference($reference)
    {
        
        for ($ii=0; $ii<count($reference); $ii++)
        {
            // relationを一行追加
            array_push($this->relation, new Repository_Oaipmh_LOM_Relation($this->RepositoryAction));
            $target_idx = count($this->relation)-1;
            
            $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);
            
            // set Kind
            $ref = strtolower($reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_REFERENCE]);
            $ref = RepositoryOutputFilterLOM::relation($ref);
            if(strlen($ref) > 0)
            {
                $this->relation[$target_idx]->addKind(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::LOM_VALUE_SOURCE, $ref));
            }
            
            // set discription
            $destItemId = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_ID];
            $destItemNo = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_NO];
            // get detail url
            $refUrl = $this->RepositoryAction->getDetailUri($destItemId, $destItemNo);
            $resource->addDescription(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $refUrl));
            
            $this->relation[$target_idx]->addResource($resource);
        }
    }
    
    /* 
     * ヘッダ出力処理
     */
    private function outputHeader()
    {
        $xml = '';
        $xml .= '<'.RepositoryConst::LOM_START;
        $xml .= ' xsi:schemaLocation="http://ltsc.ieee.org/xsd/LOM';
        $xml .= ' http://ltsc.ieee.org/xsd/lomv1.0/lom.xsd"';
        $xml .= ' xmlns="http://ltsc.ieee.org/xsd/LOM">'."\n";
        return $xml;
    }
    
    /* 
     * フッダ出力処理
     */
    private function outputFooter()
    {
        $xml = '</'.RepositoryConst::LOM_START.'>'."\n";
        return $xml;
    }
    
    /****************************************** datatype *********************************************/
}

/************************************************ A wooden trunk **********************************************/
/**
 * General型
 * <general> 
 *     <identifier>  ⇒Identifier型 (※複数存在する可能性あり)
 *        <catalog></catalog>
 *        <entry></entry>
 *     </identifier>
 *     <title>
 *         <string language=""></string>  ⇒LangString型
 *     </title>
 *     <language> </language> (※複数存在する可能性あり)
 *     <description> (※複数存在する可能性あり)
 *         <string language=""></language>  ⇒LangString型
 *     </description>
 *     <keyword> (※複数存在する可能性あり)
 *         <string language=""></string>  ⇒LangString型
 *     </keyword>
 *     <coverage> (※複数存在する可能性あり)
 *         <string language=""></string>  ⇒LangString型
 *     </coverage>
 *     <structure>
 *         [Vocabulary型参照]  =>Vocabulary型
 *     </structure>
 *     <aggregationLevel>
 *         [Vocabulary型参照]  =>Vocabulary型
 *     </aggregationLevel>
 * </general>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_General
{
    /*
     * メンバ変数
     */
    private $identifier = array();
    private $title = null;
    private $language = array();
    private $description = array();
    private $keyword = array();
    private $coverage = array();
    private $structure = null;
    private $aggregationLevel = null;
    
    var $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * Identifierセット関数 
     * @param Repository_Oaipmh_LOM_Identifier $identifier 
     */
    public function addIdentifier(Repository_Oaipmh_LOM_Identifier $identifier){
        array_push($this->identifier,$identifier);
    }
    /*
     * titleセット関数 
     * @param Repository_Oaipmh_LOM_LangString $title 
     */
    public function addTitle(Repository_Oaipmh_LOM_LangString $title){
        if($this->title == null){
            $this->title = $title;
        }
    }
    /*
     * languageセット関数 
     * @param string $language 
     */
    public function addLanguage($language){
        //encording
        $language = $this->repositoryAction->forXmlChange($language);
        // format language.
        $language = RepositoryOutputFilter::language($language);
        if($language === RepositoryConst::ITEM_LANG_OTHER)
        {
            $language = '';
        }
        if(strlen($language)>0){
            array_push($this->language, $language);
        }
    }
    /*
     * descriptionセット関数 
     * @param Repository_Oaipmh_LOM_LangString $description 
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        array_push($this->description, $description);
    }
    /*
     * keywordセット関数 
     * @param Repository_Oaipmh_LOM_LangString $keyword 
     */
    public function addKeyword(Repository_Oaipmh_LOM_LangString $keyword){
        array_push($this->keyword, $keyword);
    }
    /*
     * coverageセット関数 
     * @param Repository_Oaipmh_LOM_LangString $coverage 
     */
    public function addCoverage(Repository_Oaipmh_LOM_LangString $coverage){
        array_push($this->coverage, $coverage);
    }
    /*
     * structureセット関数 
     * @param Repository_Oaipmh_LOM_Vocabulary $structure 
     */
    public function addStructure(Repository_Oaipmh_LOM_Vocabulary $structure){
        
        //check
        $structure_value = RepositoryOutputFilterLOM::generalStructureValue($structure->getValue());
        if($this->structure == null && strlen($structure_value)>0){
            $this->structure = $structure;
        }
    }
    /*
     * aggregationLevelセット関数 
     * @param Repository_Oaipmh_LOM_Vocabulary $aggregationLevel 
     */
    public function addAggregationLevel(Repository_Oaipmh_LOM_Vocabulary $aggregationLevel){
        
        //check
        $aggregationLevel_value = RepositoryOutputFilterLOM::generalAggregationLevelValue($aggregationLevel->getValue());
        if($this->aggregationLevel == null && strlen($aggregationLevel_value)>0){
            $this->aggregationLevel = $aggregationLevel;
        }
    }
    
    /*
     * General型の出力処理
     * ※メンバ変数に値を設定後に本メソッドを呼び出すこと
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';

        //identifier
        for($ii=0;$ii<count($this->identifier);$ii++)
        {
            $xmlStr .= $this->identifier[$ii]->output();
        }
        //title
        if($this->title != null)
        {
            $xml = $this->title->output();
            if(strlen($xml)>0){
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_TITLE.'>';
                $xmlStr .= $xml;
                $xmlStr .= '</'.RepositoryConst::LOM_TAG_TITLE.'>'."\n";
            }
        }
        //language
        for($ii=0;$ii<count($this->language);$ii++)
        {
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_LANGUAGE.'>'.$this->language[$ii].'</'.RepositoryConst::LOM_TAG_LANGUAGE.'>'."\n";
        }
        //description
        for($ii=0;$ii<count($this->description);$ii++){
            $xml = $this->description[$ii]->output();
            if(strlen($xml)>0){
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
                $xmlStr .= $xml;
                $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        //keyword
        for($ii=0;$ii<count($this->keyword);$ii++){
            $xml = $this->keyword[$ii]->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_KEYWORD.'>'."\n";
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_KEYWORD.'>'."\n";
            }
        }
        //coverage
        for($ii=0;$ii<count($this->coverage);$ii++){
            $xml = $this->coverage[$ii]->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_COVERAGE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_COVERAGE.'>'."\n";
            }
        }
        //structure
        if($this->structure != null)
        {
            $xml = $this->structure->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_STRUCTURE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_STRUCTURE.'>'."\n";
            }
        }
        //aggregationLevel
        if($this->aggregationLevel != null)
        {
            $xml = $this->aggregationLevel->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_AGGREGATION_LEVEL.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_AGGREGATION_LEVEL.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0)
        {
            $xmlStr = '<'.RepositoryConst::LOM_TAG_GENERAL.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_GENERAL.'>'."\n";
        }
        
        return $xmlStr;
    }
}
    
/**
 * Contribute型
 * <contribute>
 *     <role> 
 *         [Vocabulary型参照]  =>Vocabulary型
 *     </role>
 *     <entity> メタデータ1 </entity>(※複数存在する可能性アリ)
 *     <entity> メタデータ2 </entity>(※複数存在する可能性アリ)
 *     <date>
 *         <dateTime></dateTime>=>DateTime型 (※2013年後期時点では未使用)
 *         <description></description>
 *     </date>
 * <contribute>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Contribute
{
    /*
     * メンバ変数
     */
    private $role = null;
    private $entry= array();
    private $date= null;

    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }

    //setter
    /*
     * roleセット関数 
     * @param Repository_Oaipmh_LOM_Vocabulary $role
     */
    public function addRole(Repository_Oaipmh_LOM_Vocabulary $role){
        if($this->role == null){
            $this->role = $role;
        }
        
    }
    /*
     * entryセット関数
     * @param string $entry
     */
    public function addEntry($entry){
        $entry = $this->repositoryAction->forXmlChange($entry);
        if(strlen($entry)>0){
            array_push($this->entry, $entry);
        }
    }
    /*
     * dateセット関数
     * @param string $date
     */
    public function addDate(Repository_Oaipmh_LOM_DateTime $date){
        if($this->date == null){
            $this->date = $date;
        }
    }
    
    //getter
    /*
     * Roleタグ内Value値取得
     */
    public function getRoleValue(){
        if($this->role == null){
            return '';
        }
        return $this->role->getValue();
    }
    
    /*
     * Contribute型の出力処理
     * ※メンバ変数に値を設定後に本メソッドを呼び出すこと
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        if(count($this->entry) == 0 && $this->date == null){
            return '';
        }
        
        //role
        if($this->role != null)
        {
            $xml = $this->role->output();
            if(strlen($xml)>0){
            	$xmlStr .= '<'.RepositoryConst::LOM_TAG_ROLE.'>'.$xml.'</'.RepositoryConst::LOM_TAG_ROLE.'>'."\n";
            }
        }
        //entry
        for($ii=0; $ii<count($this->entry); $ii++)
        {
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_ENTITY.'>';
            $xmlStr .= $this->entry[$ii];
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_ENTITY.'>'."\n";
        }
        //date
        if($this->date != null)
        {
            $xml = $this->date->output();
            if(strlen($xml)>0){
            	$xmlStr .= '<'.RepositoryConst::LOM_TAG_DATE.'>'.$xml.'</'.RepositoryConst::LOM_TAG_DATE.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_CONTRIBUTE.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_CONTRIBUTE.'>'."\n";
        }
        
        return $xmlStr;
    }

}

/*
 * LifeCycle
 * <lifeCycle>
 *     <version>
 *        <string language=""></string> =>LangString型 
 *     </version>
 *     <status>
 *         [Vocabulary型参照]  =>Vocabulary型
 *     </status>
 *     <contribute>(※複数存在する可能性アリ)  =>Contribute型 
 *         [Contribute型の内容参照]
 *     </contribute>
 * </lifeCycle>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_LifeCycle
{
    /*
     * メンバ変数
     */
    private $version = null;
    private $status = null;
    private $contribute = array();
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * versionセット関数
     * @param Repository_Oaipmh_LOM_LangString $version
     */
    public function addVersion(Repository_Oaipmh_LOM_LangString $version){
        if($this->version == null){
            $this->version = $version;
        }
    }
    /*
     * statusセット関数
     * @param Repository_Oaipmh_LOM_Vocabulary $status
     */
    public function addStatus(Repository_Oaipmh_LOM_Vocabulary $status){
        
        //check
        $status_value = RepositoryOutputFilterLOM::lifeCycleStatusValue($status->getValue());
        if(strlen($status_value)>0){
            $this->status = $status;
        }
    }
    /*
     * contributeセット関数
     * @param Repository_Oaipmh_LOM_Contribute $contribute
     */
    public function addContribute(Repository_Oaipmh_LOM_Contribute $contribute, $flag){
        
        //check
        //lifeCycleContributeXXの場合
        if($flag == 1){
            $contribute_value = RepositoryOutputFilterLOM::lyfeCycleContributeRole($contribute->getRoleValue());
            if(strlen($contribute_value)>0){
                array_push($this->contribute, $contribute);
            }
        }
        //lifeCycleContributeの場合
        else{
            array_push($this->contribute, $contribute);
        }
    }
    
    /*
     * LifeCycleタグの出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        
        //version
        if($this->version != null){
            $xml = $this->version->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_VERSION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_VERSION.'>'."\n";
            }
        }
        //status
        if($this->status != null){
            $xml = $this->status->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_STATUS.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_STATUS.'>'."\n";
            }
        }
        //contribute
        for($ii=0;$ii<count($this->contribute);$ii++){
            $xmlStr .= $this->contribute[$ii]->output();
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_LIFE_CYCLE.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_LIFE_CYCLE.'>'."\n";
        }
        
        return $xmlStr;
    }
}

/*
 * MetaMetadata
 * <metaMetadata>
 *     <identifier>  ⇒Identifier型 (※複数存在する可能性アリ)
 *          [Identifier型参照]
 *     </identifier>
 *     <contribute>  =>Contribute型 (※複数存在する可能性アリ)
 *          [Contribute型参照]
 *     </contribute>
 *     <metadataSchema></metadataSchema>(※複数存在する可能性アリ)
 *     <language></language>
 * </metaMetadata>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_MetaMetadata
{
    /*
     * メンバ変数
     */
    private $identifier = array();
    private $contribute = array();
    private $metadataSchema = array();
    private $language = null;
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * identifierセット関数
     * @param Repository_Oaipmh_LOM_Identifier $identifier
     */
    public function addIdentifier(Repository_Oaipmh_LOM_Identifier $identifier){
        array_push($this->identifier, $identifier);
    }
    /*
     * contributeセット関数
     * @param Repository_Oaipmh_LOM_Contribute $contribute
     */
    public function addContribute(Repository_Oaipmh_LOM_Contribute $contribute, $flag){
        
        //check
        //mataMatadataContributeXXの場合
        if($flag == 1){
            $contribute_value = RepositoryOutputFilterLOM::metaMetadataContributeRole($contribute->getRoleValue());
            if(strlen($contribute_value)>0){
                array_push($this->contribute, $contribute);
            }
        }
        //mataMatadataContributeの場合
        else{
            array_push($this->contribute, $contribute);
        }
    }
    /*
     * metadataSchemaセット関数
     * @param string $metadataSchema
     */
    public function addMetadataSchema($metadataSchema){
        //encording
        $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
        if(strlen($metadataSchema)>0){
            array_push($this->metadataSchema, $metadataSchema);
        }
    }
    /*
     * languageセット関数
     * @param string $language
     */
    public function addLanguage($language){
        //encording
        $language = $this->repositoryAction->forXmlChange($language);
        $language = RepositoryOutputFilter::language($language);
        
        if($this->language == null && strlen($language)>0){
            $this->language = $language;
        }
        
    }
    
    /*
     * MetaMetadataタグの出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';

        for($ii=0;$ii<count($this->identifier);$ii++){
            $xmlStr .= $this->identifier[$ii]->output();
        }
        for($ii=0;$ii<count($this->contribute);$ii++){
            $xml = $this->contribute[$ii]->output();
            if(strlen($xml)>0){
                //$xmlStr .= '<'.RepositoryConst::LOM_TAG_CONTRIBUTE.'>';
                $xmlStr .= $xml;
                //$xmlStr .= '</'.RepositoryConst::LOM_TAG_CONTRIBUTE.'>';
            }
        }
        for($ii=0;$ii<count($this->metadataSchema);$ii++){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_METADATA_SCHEMA.'>';
            $xmlStr .= $this->metadataSchema[$ii];
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_METADATA_SCHEMA.'>'."\n";
        }
        if($this->language != null && strlen($this->language) > 0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_LANGUAGE.'>'.$this->language.'</'.RepositoryConst::LOM_TAG_LANGUAGE.'>'."\n";
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_META_METADATA.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_META_METADATA.'>'."\n";
        }
        
        return $xmlStr;
    }
}

/*
 * Technical
 * <technical>
 *      <format></format>(※複数存在する可能性アリ)
 *      <size></size>
 *      <location></location>(※複数存在する可能性アリ)
 *      <requirement>(※複数存在する可能性アリ)
 *          <orComposite>(※複数存在する可能性アリ)
 *              <type>
 *                  [Vocabulary型参照] =>Vocabulary型
 *              </type>
 *              <name>
 *                  [Vocabulary型参照] =>Vocabulary型
 *              </name>
 *              <minimumVersion></minimumVersion>
 *              <maximumVersion></maximumVersion>
 *          </orComposite>
 *      </requirement>
 *      <installationRemarks>
 *          <string language=""></string>  =>LangString型
 *      </installationRemarks>
 *      <otherPlatformRequirements>
 *          <string language=""></string>  =>LangString型
 *      </otherPlatformRequirements>
 *      <duration>  =>Duration型
 *          [Duration型参照]
 *      </duration>
 * </technical>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Technical
{
    /*
     * 定数
     */
    const OPERATING_SYSTEM = 'operating system';
    const BROWSER = 'browser';
    
    /*
     * メンバ変数
     */
    private $format = array();
    private $size = null;
    private $location = array();
    private $requirement = array();
    private $installationRemarks = null;
    private $otherPlatformRequirements = null;
    private $duration = null;
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    /*
     * Format設定
     * @param string $format
     */
    public function addFormat($format){
        //encoding
        $format = $this->repositoryAction->forXmlChange($format);
        if(strlen($format)>0){
            array_push($this->format, $format);
        }
    }
    /*
     * Size設定
     * @param string $size
     */
    public function addSize($size){
        $size = $this->repositoryAction->forXmlChange($size);
        $size = RepositoryOutputFilterLOM::technicalSize($size);
        if($this->size == null && strlen($size)>0){
            $this->size = $size;
        }
    }
    /*
     * Location設定
     * @param string $location
     */
    public function addLocation($location){
        $location = $this->repositoryAction->forXmlChange($location);
        if(strlen($location)>0){
            array_push($this->location, $location);
        }
    }
    /*
     * Requirement設定
     * @param Repository_Oaipmh_LOM_OrComposite $requirement
     */
    public function addRequirement(Repository_Oaipmh_LOM_OrComposite $orComposite){
        //check
        array_push($this->requirement, $orComposite);
    }
    /*
     * InstallationRemarks設定
     * @param Repository_Oaipmh_LOM_LangString $installationRemarks
     */
    public function addInstallationRemarks(Repository_Oaipmh_LOM_LangString $installationRemarks){
        if($this->installationRemarks == null){
            $this->installationRemarks = $installationRemarks;
        }
    }
    /*
     * OtherPlatformRequirements設定
     * @param Repository_Oaipmh_LOM_LangString $otherPlatformRequirements
     */
    public function addOtherPlatformRequirements(Repository_Oaipmh_LOM_LangString $otherPlatformRequirements){
        if($this->otherPlatformRequirements == null){
            $this->otherPlatformRequirements = $otherPlatformRequirements;
        }
    }
    /*
     * Duration設定
     * @param Repository_Oaipmh_LOM_Duration $duration
     */
    public function addDuration(Repository_Oaipmh_LOM_Duration $duration){
        //check
        $duration_value = RepositoryOutputFilterLOM::duration($duration->getDuration());
        if(strlen($duration_value)>0){
            $this->duration = $duration;
        }
    }
    
    private function checkOrComposite($element, &$value)
    {
        $checkVal = $value;
        if(is_a($value, 'Repository_Oaipmh_LOM_Vocabulary'))
        {
            // type or name
            if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE)
            {
                $checkVal = RepositoryOutputFilterLOM::technicalRequirementOrCompositeTypeValue($value->getValue());
                if(strlen($checkVal) == 0)
                {
                    return false;
                }
                $value->setValue($checkVal);
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME)
            {
                $checkVal = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForOperatingSystem($value->getValue());
                if(strlen($checkVal) == 0)
                {
                    $checkVal = RepositoryOutputFilterLOM::technicalRequirementOrCompositeNameValueForBrowser($value->getValue());
                    if(strlen($checkVal) == 0)
                    {
                        return false;
                    }
                }
                $value->setValue($checkVal);
            }
            else
            {
                return false;
            }
        }
        
        if(strlen($checkVal) == 0)
        {
            return false;
        }
        
        return true;
    }
    
    //setter
    /*
     * addOrComposite
     */
    public function addOrComposite($element, $value){
        
        if(!$this->checkOrComposite($element, $value))
        {
            return;
        }
        /*
        if(count($this->requirement) == 0)
        {
            $this->addRequirement(new Repository_Oaipmh_LOM_OrComposite($this->repositoryAction, null, null, '', ''));
        }
        */
        $ii = 0;
        for($ii=0; $ii<count($this->requirement); $ii++)
        {
            $orComp = $this->requirement[$ii];
            if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE)
            {
                // check type
                if(strlen($this->requirement[$ii]->getTypeValue()) == 0)
                {
                    $name = $this->requirement[$ii]->getNameValue();
                    if(strlen($name) > 0)
                    {
                        if(RepositoryOutputFilterLOM::technicalRequirementOrCompositeCombination($value->getValue(), $name))
                        {
                            // typeに入れようとしている$valueと既に入っているnameの組み合わせはOKな組み合わせ
                            $this->requirement[$ii]->setTypeString($value);
                            break;
                        }
                    }
                    else
                    {
                        // typeもnameも空
                       $this->requirement[$ii]->setTypeString($value);
                        break;
                    }
                }
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME)
            {
                // check name
                if(strlen($this->requirement[$ii]->getNameValue()) == 0)
                {
                    $type = $this->requirement[$ii]->getTypeValue();
                    if(strlen($type) > 0)
                    {
                        if(RepositoryOutputFilterLOM::technicalRequirementOrCompositeCombination($type, $value->getValue()))
                        {
                            // typeに入れようとしている$valueと既に入っているnameの組み合わせはOKな組み合わせ
                            $this->requirement[$ii]->setName($value);
                            break;
                        }
                    }
                    else
                    {
                        // typeもnameも空
                        $this->requirement[$ii]->setName($value);
                        break;
                    }
                }
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION)
            {
                if(strlen($this->requirement[$ii]->getMinimumVersion()) == 0)
                {
                    $this->requirement[$ii]->setMinimumVersion($value);
                    break;
                }
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION)
            {
                if(strlen($this->requirement[$ii]->getMaximumVersion()) == 0)
                {
                    $this->requirement[$ii]->setMaximumVersion($value);
                    break;
                }
            }
        }
        
        if($ii == count($this->requirement))
        {
            // 値を入れる場所がないので、新規作成
            //$orComp = new Repository_Oaipmh_LOM_OrComposite($this->repositoryAction, null, null, '', '');
            $orComp = new Repository_Oaipmh_LOM_OrComposite($this->repositoryAction);
            $orComp->setName(new Repository_Oaipmh_LOM_Vocabulary($this->repositoryAction, '', ''));
            $orComp->setType(new Repository_Oaipmh_LOM_Vocabulary($this->repositoryAction, '', ''));
            $orComp->setMaximumVersion('');
            $orComp->setMinimumVersion('');
            
            if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE)
            {
                $orComp->setTypeString($value);
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME)
            {
                $orComp->setNameString($value);
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION)
            {
                $orComp->setMinimumVersion($value);
            }
            else if($element == RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION)
            {
                $orComp->setMaximumVersion($value);
            }
            
            $this->addRequirement($orComp);
        }
    }
    
    
    /*
     * Technicalタグの出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        //format
        for($ii=0;$ii<count($this->format);$ii++){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_FORMAT.'>';
            $xmlStr .= $this->format[$ii];
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_FORMAT.'>'."\n";
        }
        //size
        if($this->size != null){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_SIZE.'>';
            $xmlStr .= $this->size;
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_SIZE.'>'."\n";
        }
        //location
        for($ii=0;$ii<count($this->location);$ii++){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_LOCATION.'>';
            $xmlStr .= $this->location[$ii];
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_LOCATION.'>'."\n";
        }
        //requirement
        
        for($ii=0;$ii<count($this->requirement);$ii++){
            $xml = $this->requirement[$ii]->output();
            if(strlen($xml)>0){
                if($ii == 0){
                    $xmlStr .= '<'.RepositoryConst::LOM_TAG_REQUIREMENT.'>';
                }
                
                $xmlStr .= $xml;
                
                if($ii == count($this->requirement)-1){
                    $xmlStr .= '</'.RepositoryConst::LOM_TAG_REQUIREMENT.'>'."\n";
                }
            }
        }
        
        //installationRemarks
        if($this->installationRemarks != null){
            $xml = $this->installationRemarks->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_INSTALLATION_REMARKS.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_INSTALLATION_REMARKS.'>'."\n";
            }
        }
        //otherPlatformRequirements
        if($this->otherPlatformRequirements != null){
            $xml = $this->otherPlatformRequirements->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_OTHER_PLATFORM_REQIREMENTS.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_OTHER_PLATFORM_REQIREMENTS.'>';
            }
        }
        //duration
        if($this->duration != null){
            $xml = $this->duration->output();
            if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DURATION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DURATION.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_TECHNICAL.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_TECHNICAL.'>'."\n";
        }
        
        return $xmlStr;
    }
}

/*
 * Educational
 * <educational>
 *      <interactivityType>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </interactivityType>
 *      <learningResourceType>(※複数存在する可能性アリ)
 *          [Vocabulary型参照] =>Vocabulary型
 *      </learningResourceType>
 *      <interactivityLevel>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </interactivityLevel>
 *      <semanticDensity>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </semanticDensity>
 *      <intendedEndUserRole>(※複数存在する可能性アリ)
 *          [Vocabulary型参照] =>Vocabulary型
 *      </intendedEndUserRole>
 *      <context>(※複数存在する可能性アリ)
 *          [Vocabulary型参照] =>Vocabulary型
 *      </context>
 *      <typicalAgeRange>(※複数存在する可能性アリ)
 *          <string language=""></string>  =>LangString型
 *      </typicalAgeRange>
 *      <difficulty>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </difficulty>
 *      <typicalLearningTime>  =>Duration型
 *          [Duration型参照]
 *      </typicalLearningTime>
 *      <description>(※複数存在する可能性アリ)
 *          <string language=""></string>  =>LangString型
 *      </description>
 *      <language></language>(※複数存在する可能性アリ)
 * </educational>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Educational
{
    /*
     * メンバ
     */
    private $interactivityType = null;
    private $learningResourceType = array();
    private $interactivityLevel = null;
    private $semanticDensity = null;
    private $intendedEndUserRole = array();
    private $context = array();
    private $typicalAgeRange = array();
    private $difficulty = null;
    private $typicalLearningTime = null;
    private $description = array();
    private $language = array();
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * interactivityType設定
     * @param Repository_Oaipmh_LOM_Vocabulary $interactivityType
     */
    public function addInteractivityType(Repository_Oaipmh_LOM_Vocabulary $interactivityType){
        //check
        $interactivityType_value = RepositoryOutputFilterLOM::educationalInteractivityType($interactivityType->getValue());
        if($this->interactivityType == null && strlen($interactivityType_value)>0){
            $this->interactivityType = $interactivityType;
        }
    }
    /*
     * learningResourceType設定
     * @param Repository_Oaipmh_LOM_Vocabulary $learningResourceType
     */
    public function addLearningResourceType(Repository_Oaipmh_LOM_Vocabulary $learningResourceType){
        //check
        $learningResourceType_value = RepositoryOutputFilterLOM::educationalLearningResourceType($learningResourceType->getValue());
        if(strlen($learningResourceType_value)>0){
            array_push($this->learningResourceType,$learningResourceType);
        }
    }
    /*
     * InteractivityLevel設定
     * @param Repository_Oaipmh_LOM_Vocabulary $interactivityLevel
     */
    public function addInteractivityLevel(Repository_Oaipmh_LOM_Vocabulary $interactivityLevel){
        //check
        $interactivityLevel_value = RepositoryOutputFilterLOM::educationalInteractivityLevel($interactivityLevel->getValue());
        if($this->interactivityLevel == null && strlen($interactivityLevel_value)>0){
            $this->interactivityLevel = $interactivityLevel;
        }
    }
    /*
     * SemanticDensity設定
     * @param Repository_Oaipmh_LOM_Vocabulary $semanticDensity
     */
    public function addSemanticDensity(Repository_Oaipmh_LOM_Vocabulary $semanticDensity){
        //check
        $semanticDensity_value = RepositoryOutputFilterLOM::educationalSemanticDensity($semanticDensity->getValue());
        if($this->semanticDensity == null && strlen($semanticDensity_value)>0){
            $this->semanticDensity = $semanticDensity;
        }
    }
    /*
     * IntendedEndUserRole設定
     * @param Repository_Oaipmh_LOM_Vocabulary $intendedEndUserRole
     */
    public function addIntendedEndUserRole(Repository_Oaipmh_LOM_Vocabulary $intendedEndUserRole){
        $intendedEndUserRole_value = RepositoryOutputFilterLOM::educationalIntendedEndUserRole($intendedEndUserRole->getValue());
        if(strlen($intendedEndUserRole_value)>0){
            array_push($this->intendedEndUserRole, $intendedEndUserRole);
        }
    }
    /*
     * Context設定
     * @param Repository_Oaipmh_LOM_Vocabulary $context
     */
    public function addContext(Repository_Oaipmh_LOM_Vocabulary $context){
        $context_value = RepositoryOutputFilterLOM::educationalContext($context->getValue());
        if(strlen($context_value)>0){
            array_push($this->context, $context);
        }
    }
    /*
     * TypicalAgeRange設定
     * @param Repository_Oaipmh_LOM_LangString $typicalAgeRange
     */
    public function addTypicalAgeRange(Repository_Oaipmh_LOM_LangString $typicalAgeRange){
        array_push($this->typicalAgeRange,$typicalAgeRange);
    }
    /*
     * Difficulty設定
     * @param Repository_Oaipmh_LOM_Vocabulary $difficulty
     */
    public function addDifficulty(Repository_Oaipmh_LOM_Vocabulary $difficulty){
        $difficulty_value = RepositoryOutputFilterLOM::educationalDifficulty($difficulty->getValue());
        if($this->difficulty == null && strlen($difficulty_value)>0){
            $this->difficulty = $difficulty;
        }
    }
    /*
     * TypicalLearningTime設定
     * @param Repository_Oaipmh_LOM_LangString $typicalLearningTime
     */
    public function addTypicalLearningTime(Repository_Oaipmh_LOM_Duration $typicalLearningTime){
        //check
        $typicalLearningTime_value = RepositoryOutputFilterLOM::duration($typicalLearningTime->getDuration());
        if($this->typicalLearningTime == null && strlen($typicalLearningTime_value)>0){
            $this->typicalLearningTime = $typicalLearningTime;
        }
    }
    /*
     * Description設定
     * @param Repository_Oaipmh_LOM_LangString $description
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        array_push($this->description,$description);
    }
    /*
     * Language設定
     * @param string $language
     */
    public function addLanguage($language){
        //check
        //encoding
        $language = $this->repositoryAction->forXmlChange($language);
        //format language
        $language = RepositoryOutputFilter::language($language);
        if(strlen($language)>0){
            array_push($this->language,$language);
        }
    }
    
    //getter
    public function getInteractivityType(){
        return $this->interactivityType;
    }

    public function getLearningResourceType(){
        return $this->learningResourceType;
    }

    public function getInteractivityLevel(){
        return $this->interactivityLevel;
    }

    public function getSemanticDensity(){
        return $this->semanticDensity;
    }

    public function getIntendedEndUserRole(){
        return $this->intendedEndUserRole;
    }

    public function getContext(){
        return $this->context;
    }

    public function getTypicalAgeRange(){
        return $this->typicalAgeRange;
    }

    public function getDifficulty(){
        return $this->difficulty;
    }

    public function getTypicalLearningTime(){
        return $this->typicalLearningTime;
    }

    public function getDescription(){
        return $this->description;
    }

    public function getLanguage($language){
        return $this->language;
    }
    
    
    /*
     * educationalタグの出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        //interactivityType
        if($this->interactivityType != null){
        	$xml = $this->interactivityType->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_INTERACTIVITY_TYPE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_INTERACTIVITY_TYPE.'>'."\n";
            }
        }
        //learningResourceType
        for($ii=0;$ii<count($this->learningResourceType);$ii++){
        	$xml = $this->learningResourceType[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_LEARNING_RESOURCE_TYPE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_LEARNING_RESOURCE_TYPE.'>'."\n";
            }
        }
        //interactivityLevel
        if($this->interactivityLevel != null){
        	$xml = $this->interactivityLevel->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_INTERACTIVITY_LEVEL.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_INTERACTIVITY_LEVEL.'>'."\n";
            }
        }
        //semanticDensity
        if($this->semanticDensity != null){
        	$xml = $this->semanticDensity->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_SEMANTIC_DENSITY.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_SEMANTIC_DENSITY.'>'."\n";
            }
        }
        //intendedEndUserRole
        for($ii=0;$ii<count($this->intendedEndUserRole);$ii++){
        	$xml = $this->intendedEndUserRole[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_INTENDED_END_USER_ROLE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_INTENDED_END_USER_ROLE.'>'."\n";
            }
        }
        //context
        for($ii=0;$ii<count($this->context);$ii++){
        	$xml = $this->context[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_CONTEXT.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_CONTEXT.'>'."\n";
            }
        }
        //typicalAgeRange
        for($ii=0;$ii<count($this->typicalAgeRange);$ii++){
        	$xml = $this->typicalAgeRange[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_TYPICAL_AGE_RANGE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_TYPICAL_AGE_RANGE.'>'."\n";
            }
        }
        //difficulty
        if($this->difficulty != null){
        	$xml = $this->difficulty->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DIFFICULTY.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DIFFICULTY.'>'."\n";
            }
        }
        //typicalLearningTime
        if($this->typicalLearningTime != null){
        	$xml = $this->typicalLearningTime->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_TYPICAL_LEARNING_TIME.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_TYPICAL_LEARNING_TIME.'>'."\n";
            }
        }
        //description
        for($ii=0;$ii<count($this->description);$ii++){
        	$xml = $this->description[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        //language
        for($ii=0;$ii<count($this->language);$ii++){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_LANGUAGE.'>';
            $xmlStr .= $this->language[$ii];
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_LANGUAGE.'>'."\n";
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_EDUCATIONAL.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_EDUCATIONAL.'>'."\n";
        }
        return $xmlStr;
    }
    
}

/*
 * Rights
 * <rights>
 *      <cost>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </cost>
 *      <copyrightAndOtherRestrictions>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </copyrightAndOtherRestrictions>
 *      <description>
 *          <string language=""></string>  =>LangString型
 *      </description>
 * </rights>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Rights
{
    /*
     * メンバ変数
     */
    private $cost = null;
    private $copyrightAndOtherRestrictions = null;
    private $description = null;
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * Cost設定
     * @param Repository_Oaipmh_LOM_Vocabulary $cost
     */
    public function addCost(Repository_Oaipmh_LOM_Vocabulary $cost){
        $cost_value = RepositoryOutputFilterLOM::yesno($cost->getValue());
        if($this->cost == null && strlen($cost_value)>0){
            $this->cost = $cost;
        }
    }
    /*
     * CopyrightAndOtherRestrictions設定
     * @param Repository_Oaipmh_LOM_Vocabulary $copyrightAndOtherRestrictions
     */
    public function addCopyrightAndOtherRestrictions(Repository_Oaipmh_LOM_Vocabulary $copyrightAndOtherRestrictions){
        $copyright_value = RepositoryOutputFilterLOM::yesno($copyrightAndOtherRestrictions->getValue());
        if($this->copyrightAndOtherRestrictions == null && strlen($copyright_value)>0){
            $this->copyrightAndOtherRestrictions = $copyrightAndOtherRestrictions;
        }
    }
    /*
     * Description設定
     * @param Repository_Oaipmh_LOM_LangString $description
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        if($this->description == null){
            $this->description = $description;
        }
    }
    /*
     * rightsタグ出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        
        //cost
        if($this->cost != null){
        	$xml = $this->cost->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_COST.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_COST.'>'."\n";
            }
        }
        //copyrightAndOtherRestrictions
        if($this->copyrightAndOtherRestrictions != null){
        	$xml = $this->copyrightAndOtherRestrictions->output();
        	
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_COPYRIGHT_AND_OTHER_RESTRICTIONS.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_COPYRIGHT_AND_OTHER_RESTRICTIONS.'>'."\n";
            }
        }
        //description
        if($this->description != null){
        	$xml = $this->description->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_RIGHTS.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_RIGHTS.'>'."\n";
        }
        
        return $xmlStr;
    }
}


/*
 * Relation
 * <relation>
 *      <kind>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </kind>
 *      <resource>
 *          <identifier>(※複数存在する可能性アリ)  =>Identifier型 
 *              [Identifier型参照]
 *          </identifier>
 *          <description>(※複数存在する可能性アリ)
 *              <string language=""></string>  =>LangString型
 *          </description>
 *      </resource>
 * </relation>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Relation
{
    /*
     * メンバ変数
     */
    private $kind = null;
    private $resource = null;
    
    private $repositoryAction = null;

    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    /*
     * Kind設定
     * @param Repository_Oaipmh_LOM_Vocabulary $kind
     */
    public function addKind(Repository_Oaipmh_LOM_Vocabulary $kind){
        //check
        $kind_value = RepositoryOutputFilterLOM::relation($kind->getValue());
        if($this->kind == null && strlen($kind_value)>0){
            $this->kind = $kind;
        }
    }
    /*
     * Resource設定
     * @param Repository_Oaipmh_LOM_Resource $resource
     */
    public function addResource(Repository_Oaipmh_LOM_Resource $resource){
        if($this->resource == null){
            $this->resource = $resource;
        }
    }
    /*
     * Relationタグ出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        
        if($this->resource == null){
            return '';
        }
        
        //kind
        $resource = '';
        if($this->resource != null){
            $resource = $this->resource->output();
        }
        
        if($this->kind != null && strlen($resource)>0){
        	$xml = $this->kind->output();
        	if(strlen($xml)>0){
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_KIND.'>';
                $xmlStr .= $xml;
                $xmlStr .= '</'.RepositoryConst::LOM_TAG_KIND.'>'."\n";
            }
        }
        //resource
        $xmlStr .= $resource;
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_RELATION.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_RELATION.'>'."\n";
        }
        
        return $xmlStr;
    }
    
}

/*
 * Annotation
 * <annotation>
 *      <entity></entity>
 *      <date>
 *          [DateTime型参照]  =>DateTime型参照
 *      </date>
 *      <description>
 *          <string language=""></string>  =>LangString型
 *      </description>
 * </annotation>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Annotation
{
    /*
     * メンバ変数
     */
    private $entity = null;
    private $date = null;
    private $description = null;
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * Entity設定
     * @param string $entity
     */
    public function addEntity($entity){
        //encording
        $entity = $this->repositoryAction->forXmlChange($entity);
        if($this->entity == null || strlen($entity)==0){
            $this->entity = $entity;
        }
    }
    /*
     * Date設定
     * @param Repository_Oaipmh_LOM_DateTime $date
     */
    public function addDate(Repository_Oaipmh_LOM_DateTime $date){
        if($this->date == null){
            $this->date = $date;
        }
    }
    /*
     * Description設定
     * @param Repository_Oaipmh_LOM_LangString $date
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        if($this->description == null){
            $this->description = $description;
        }
    }
    
    //getter
    public function getEntity(){
        return $this->entity;
    }
    public function getDate(){
        return $this->date;
    }
    public function getDescription(){
        return $this->description;
    }
    
    /*
     * Annotationタグ出力処理
     */
    public function output(){
        $xmlStr = '';
        
        //entity
        if($this->entity != null && strlen($this->entity)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_ENTITY.'>'.$this->entity.'</'.RepositoryConst::LOM_TAG_ENTITY.'>'."\n";
        }
        //date
        if($this->date != null){
        	$xml = $this->date->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DATE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DATE.'>'."\n";
            }
        }
        //description
        if($this->description != null){
        	$xml = $this->description->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_ANNOTAION.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_ANNOTAION.'>'."\n";
        }
        
        return $xmlStr;
    }
}

/*
 * Classification
 * <classification>
 *      <purpose>
 *          [Vocabulary型参照] =>Vocabulary型
 *      </purpose>
 *      <taxonPath>(※複数存在する可能性アリ)
 *          <source>
 *              <string language=""></string>  =>LangString型
 *          </source>
 *          <taxon>(※複数存在する可能性アリ)
 *              <id></id>
 *              <entry>
 *                  <string language=""></string>  =>LangString型
 *              </entry>
 *          </taxon>
 *      </taxonPath>
 *      <description>
 *          <string language=""></string>  =>LangString型
 *      </description>
 *      <keyword>(※複数存在する可能性アリ)
 *          <string language=""></string>  =>LangString型
 *      </keyword>
 * </classification>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Classification
{
    /*
     * メンバ変数
     */
    private $purpose = null;
    private $taxonPath = array();
    private $description = null;
    private $keyword = array();
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * Purpose設定
     * @param Repository_Oaipmh_LOM_Vocabulary $purpose
     */
    public function addPurpose(Repository_Oaipmh_LOM_Vocabulary $purpose){
        //check
        $purpose_value = RepositoryOutputFilterLOM::classificationPurpose($purpose->getValue());
        if($this->purpose == null && strlen($purpose_value)>0){
            $this->purpose = $purpose;
        }
        
    }
    /*
     * TaxonPath設定
     * @param Repository_Oaipmh_LOM_TaxonPath $taxonPath
     */
    public function addTaxonPath(Repository_Oaipmh_LOM_TaxonPath $taxonPath){
        array_push($this->taxonPath, $taxonPath);
    }
    
    /*
     * Description設定
     * @param Repository_Oaipmh_LOM_LangString $description
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        if($this->description == null){
            $this->description = $description;
        }
    }
    /*
     * Keyword設定
     * @param Repository_Oaipmh_LOM_LangString $keyword
     */
    public function addKeyword(Repository_Oaipmh_LOM_LangString $keyword){
        array_push($this->keyword, $keyword);
    }
    
    //getter
    public function getPurposeValue(){
        if($this->purpose == null){
           return '';
        }
        return $this->purpose->getValue();
    }
    
    public function getTaxonPathSource(){
        if($this->taxonPath == null){
            return null;
        }
        
        $ret_source = '';
        
        for($ii=0;$ii<count($this->taxonPath);$ii++){
            $source = $this->taxonPath[$ii]->getSource();
            if(strlen($source)>0){
                $ret_source = $source;
                break;
            }
        }
        return $ret_source;
    }
    public function getTaxonPathCount(){
        if($this->taxonPath == null){
            return null;
        }
        $count = 0;
        
        for($ii=0;$ii<count($this->taxonPath);$ii++){
            $taxCnt = $this->taxonPath[$ii]->getTaxonCount();
            if($taxCnt != 0){
                $count = $taxCnt;
                break;
            }
        }
        return $count;
    }
    
    public function getDescriptionString(){
        if($this->description == null){
            return '';
        }
        return $this->description->getString();
    }
    public function getKeyword(){
        return $this->keyword;
    }
    
    public function setTaxonPathSource($taxonPathString){
        if($this->taxonPath == null){
            $taxonPath = new Repository_Oaipmh_LOM_TaxonPath($this->repositoryAction);
            $taxonPath->addSource($taxonPathString);
            array_push($this->taxonPath, $taxonPath);
        }else{
            for($ii=0;$ii<count($this->taxonPath);$ii++){
                if(strlen($this->taxonPath[$ii]->getSource()) == 0){
                    //いれる
                    $this->taxonPath[$ii]->addSource($taxonPathString);
                    break;
                }
            }
        }
    }
    
    public function setTaxonPathEntry($taxonPathEntry){
        if($this->taxonPath == null){
            $taxonPath = new Repository_Oaipmh_LOM_TaxonPath($this->repositoryAction);
            //$taxon = new Repository_Oaipmh_LOM_Taxon($this->repositoryAction, '', $taxonPathEntry);
            $taxon = new Repository_Oaipmh_LOM_Taxon($this->repositoryAction);
            $taxon->setId('');
            $taxon->setEntry($taxonPathEntry);
            
            $taxonPath->addTaxon($taxon);
            array_push($this->taxonPath, $taxonPath);
        }else{
            for($ii=0;$ii<count($this->taxonPath);$ii++){
                if($this->taxonPath[$ii]->getTaxonCount() == 0){
                    //いれる
                    $taxonPath = new Repository_Oaipmh_LOM_TaxonPath($this->repositoryAction);
                    //$taxon = new Repository_Oaipmh_LOM_Taxon($this->repositoryAction, '', $taxonPathEntry);
                    $taxon = new Repository_Oaipmh_LOM_Taxon($this->repositoryAction);
                    $taxon->setId('');
                    $taxon->setEntry($taxonPathEntry);
                    
                    $this->taxonPath[$ii]->addTaxon($taxon);
                    break;
                }
            }
        }
    }
    
    
    /*
     * classificationタグ生成処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        
        //purpose
        if($this->purpose != null){
        	$xml = $this->purpose->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_PURPOSE.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_PURPOSE.'>'."\n";
            }
        }
        //taxonPath
        for($ii=0;$ii<count($this->taxonPath);$ii++){
        	$xml = $this->taxonPath[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= $xml;
            }
        }
        //description
        if($this->description != null){
        	$xml = $this->description->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        //keyword
        for($ii=0;$ii<count($this->keyword);$ii++){
        	$xml = $this->keyword[$ii]->output();
        	if(strlen($xml)>0){
	            $xmlStr .= '<'.RepositoryConst::LOM_TAG_KEYWORD.'>'."\n";
	            $xmlStr .= $xml;
	            $xmlStr .= '</'.RepositoryConst::LOM_TAG_KEYWORD.'>'."\n";
            }
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_CLASSIFICATION.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_CLASSIFICATION.'>'."\n";
        }
        
        return $xmlStr;
    }
    
}

/************************************************ The point of a branch  **********************************************/

/*
 * LangString型 
 * <string languege=""> </string>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_LangString
{

    /*
     * メンバ変数
     */
    private $string = '';
    private $language= '';
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     * @param string $str 
     * @param string $lang 
     */
    public function __construct($repositoryAction, $str, $lang='')
    {
        $this->repositoryAction = $repositoryAction;
        $this->string = $str;
        $this->language = $lang;
    }
    
    //getter
    public function getString(){
        return $this->string;
    }
    //setter
    public function setString($string){
        $this->string = $string;
    }
    
    /*
     * LangString型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording language
        $this->language = $this->repositoryAction->forXmlChange($this->language);
        //encording string
        $this->string = $this->repositoryAction->forXmlChange($this->string);
        
        if(strlen($this->string) > 0){
            // format language.
            $this->language = RepositoryOutputFilter::language($this->language);
            if($this->language === RepositoryConst::ITEM_LANG_OTHER)
            {
                $this->language = '';
            }
            // set language
            if(strlen($this->language) == 0)
            {
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_STRING.'>'."\n";
            }
            else
            {
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_STRING.' '.RepositoryConst::LOM_TAG_LANGUAGE.'="'.$this->language.'">'."\n";
            }
            $xmlStr .= $this->string.'</'.RepositoryConst::LOM_TAG_STRING.'>'."\n";
        }
        return $xmlStr;
    }

}

/**
 * DateTime型
 * <dateTime> </dateTime>
 * <description> </description>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_DateTime
{
    /*
     * メンバ変数
     */
    private $dateTime = '';
    private $description = '';
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     * @param string $dateTime [single]
     * @param LOM_TYPE_LANGSTRING $description [single]
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
        
    }
    
    //getter
    public function getDateTime(){
        return $this->dateTime;
        
    }
    //setter
    public function setDescription(Repository_Oaipmh_LOM_LangString $description){
        $this->description = $description;
    }
    
    public function setDateTime($dateTime){
        $this->dateTime = $dateTime;
        
    }
    
    /*
     * DateTime型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording
        $this->dateTime = $this->repositoryAction->forXmlChange($this->dateTime);
        
        // format date
        $this->dateTime = RepositoryOutputFilter::date($this->dateTime);
        
        if(strlen($this->dateTime)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DATE_TIME.'>'.$this->dateTime.'</'.RepositoryConst::LOM_TAG_DATE_TIME.'>'."\n";
        }
        
        if($this->description != null){
            $xml = $this->description->output();
            if(strlen($xml)>0){
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'.$this->description->output().'</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
            }
        }
        return $xmlStr;
    }
}
    
/**
 * Duration型
 * <duration> </duration>
 * <description> </description>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Duration
{
    /*
     * メンバ変数
     */
    private $duration = '';
    private $description = '';
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }
    
    //getter
    /*
     * Duration取得
     */
    public function getDuration(){
        return $this->duration;
    }
    
    //setter
    public function setDuration($duration){
        $this->duration = $duration;
    }
    public function setDescription(Repository_Oaipmh_LOM_LangString $description){
        $this->description = $description;
    }
    
    /*
     * Duration型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording
        $this->duration = $this->repositoryAction->forXmlChange($this->duration);
        
        // format duration.
        $this->duration = RepositoryOutputFilterLOM::duration($this->duration);
        if(strlen($this->duration)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DURATION.'>'.$this->duration.'</'.RepositoryConst::LOM_TAG_DURATION.'>'."\n";
        }
        
        $xml_description = $this->description->output();
        if(strlen($xml_description)>0){
        	$xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'.$xml_description.'</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
        }
        
        return $xmlStr;
    }
}
    
/*
 * Vocabulary型 
 * <source> </source>
 * <value> </value>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Vocabulary
{
    /*
     * メンバ変数
     */
    private $source = '';
    private $value = '';
    private $repositoryAction = null;

    /*
     * コンストラクタ
     * @param string $source
     * @param string $value
     */
    public function __construct($repositoryAction, $source ,$value)
    {
        $this->repositoryAction = $repositoryAction;
        $this->source = $source;
        $this->value = $value;
    }

    //getter
    /*
     * valueを取得する
     */
    public function getValue(){
        return $this->value;
    }
    //setter
    /*
     * valueを設定する
     */
    public function setValue($value){
        $this->value = $value;
    }
    
    /*
     * Vocabulary型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording
        $this->source = $this->repositoryAction->forXmlChange($this->source);
        $this->value = $this->repositoryAction->forXmlChange($this->value);
        
        if(strlen($this->source)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_SOURCE.'>'.$this->source.'</'.RepositoryConst::LOM_TAG_SOURCE.'>'."\n";
        }
        if(strlen($this->value)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_VALUE.'>'.$this->value.'</'.RepositoryConst::LOM_TAG_VALUE.'>'."\n";
        }
        
        return $xmlStr;
    }
}
    
/****************************************** tag *********************************************/
    
/**
 * Identifier型 
 * <identifier>
 *     <catalog> </catalog>
 *     <entity> </entity>
 * </identifier>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Identifier
{
    /*
     * メンバ変数
     */
    private $entry = '';
    private $catalog = '';
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     * @param string $entry [single]
     * @param string $catalog [single] default -> 'identifier'
     */
    public function __construct($repositoryAction, $entry, $catalog=RepositoryConst::LOM_TAG_IDENTIFIER)
    {
        $this->repositoryAction = $repositoryAction;
        $this->entry = $entry;
        $this->catalog = $catalog;
    }
    
    /*
     * Identifier型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording
        $this->catalog = $this->repositoryAction->forXmlChange($this->catalog);
        $this->entry = $this->repositoryAction->forXmlChange($this->entry);
        if(strlen($this->entry) > 0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_IDENTIFIER.'>';
            if(strlen($this->catalog) > 0)
            {
                $xmlStr .= '<'.RepositoryConst::LOM_TAG_CATALOG.'>'.$this->catalog.'</'.RepositoryConst::LOM_TAG_CATALOG.'>'."\n";
            }
            
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_ENTRY.'>'.$this->entry.'</'.RepositoryConst::LOM_TAG_ENTRY.'>'."\n";
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_IDENTIFIER.'>';
        }
        
        return $xmlStr;
    }
}

/*
 * Taxon型(カスタム) 
 * <taxon>
 *      <id> </id>
 *      <entry>
 *          <string language=""></string>  =>LangString型 
 *      </entry>
 * </taxon>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Taxon
{
    /*
     * メンバ変数
     */
    private $id = '';
    private $entry = null;
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }
    
    //getter
    public function getTaxonEntry(){
        if($this->entry == null){
            return '';
        }
        
        return $this->entry->getString();
    }
    //setter
    public function setId($id){
        $this->id = $id;
    }
    public function setEntry(Repository_Oaipmh_LOM_LangString $entry){
        $this->entry = $entry;
    }
    
    public function setTaxonEntry($entry){
        if($this->entry == null){
            return;
        }
        $this->entry->setString($entry);
    }
    
    /*
     * Taxon型の出力処理
     * @return string xml str
     */
    public function output()
    {
        $xmlStr = '';
        
        //encording
        $this->id = $this->repositoryAction->forXmlChange($this->id);
        
        $xmlStr .= '<'.RepositoryConst::LOM_TAG_TAXON.'>'."\n";
        if(strlen($this->id)>0){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_ID.'>'.$this->id.'</'.RepositoryConst::LOM_TAG_ID.'>'."\n";
        }
        $xmlStr .= '<'.RepositoryConst::LOM_TAG_ENTRY.'>'.$this->entry->output().'</'.RepositoryConst::LOM_TAG_ENTRY.'>'."\n";
        $xmlStr .= '</'.RepositoryConst::LOM_TAG_TAXON.'>'."\n";
        
        return $xmlStr;
    }
    
}

/*
 * TaxonPath型(カスタム) 
 * <taxonPath>
 *   <source>
 *      <string language="XX"></string>
 *   </source>
 *   <taxon>(※複数入力可能性アリ)
 *      <id> </id>
 *      <entry>
 *          <string language=""></string>  =>LangString型 
 *      </entry>
 *   </taxon>
 *   
 * </taxonPath>
 */
class Repository_Oaipmh_LOM_TaxonPath{
    /*
     * メンバ変数
     */
    private $source = null;
    private $taxon = array();
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     * @param string $source [single]
     * @param Repository_Oaipmh_LOM_Taxon $taxon [pararel]
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }
    /*
     * addSource
     */
    public function addSource(Repository_Oaipmh_LOM_LangString $source){
        if($this->source == null){
             $this->source = $source;
        }
    }
    /*
     * addTaxon
     */
    public function addTaxon(Repository_Oaipmh_LOM_Taxon $taxon){
        array_push($this->taxon, $taxon);
    }
    
    //getter
    public function getSource(){
        if($this->source == null){
            return '';
        }
        return $this->source->getString();
    }
    public function getTaxonCount(){
        if($this->taxon == null){
            return 0;
        }
        return count($this->taxon);
    }
    
    /*
     * TaxonPathタグ出力処理
     */
    public function output(){
        $xmlStr = '';
        
        //source
        if($this->source != null){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_SOURCE.'>';
            $xmlStr .= $this->source->output();
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_SOURCE.'>';
        }
        //taxon
        for($ii=0;$ii<count($this->taxon);$ii++){
            $xmlStr .= $this->taxon[$ii]->output();
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_TAXON_PATH.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_TAXON_PATH.'>'."\n";
        }
        return $xmlStr;
    }
    
    
}

/*
 * OrComposite型(カスタム) 
 * <orComposite>
 *      <type>
 *          [Vocabulary参照]  =>Vocabulary型
 *      </type>
 *      <name>
 *          [Vocabulary参照]  =>Vocabulary型
 *      </name>
 *      <minimumVersion></minimumVersion>
 *      <maximumVersion></maximumVersion>
 * </orComposite>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_OrComposite
{
    /*
     * メンバ変数
     */
    private $type = null;
    private $name = null;
    private $minimumVersion = null;
    private $maximumVersion = null;
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     * 
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
    }
    
    //getter
    /*
     * typeタグのValueを取得
     */
    public function getTypeValue(){
        $type = '';
        if($this->type != null){
            $type = $this->type->getValue();
        }
        return $type;
    }
    /*
     * nameタグのValueを取得
     */
    public function getNameValue(){
        $name = '';
        if($this->name != null){
            $name = $this->name->getValue();
        }
        return $name;
    }
    /*
     * MinimumVersionタグを取得
     */
    public function getMinimumVersion(){
        return $this->minimumVersion;
    }
    /*
     * MaximumVersionタグを取得
     */
    public function getMaximumVersion(){
        return $this->maximumVersion;
    }
    
    //setter
    /*
     * typeを設定
     */
    public function setTypeString($value){
        $this->type = $value;
    }
    /*
     * nameを設定
     */
    public function setNameString($value){
        $this->name = $value;
    }
    
    public function setType(Repository_Oaipmh_LOM_Vocabulary $value){
        $this->type = $value;
    }
    
    public function setName(Repository_Oaipmh_LOM_Vocabulary $value){
        $this->name = $value;
    }
    
    public function setMinimumVersion($value){
        $this->minimumVersion = $value;
    }
    
    public function setMaximumVersion($value){
        $this->maximumVersion = $value;
    }
    
    /*
     * OnComposite型の出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        //type
        if($this->type != null){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_TYPE.'>';
            $xmlStr .= $this->type->output();
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_TYPE.'>'."\n";
        }
        //name
        if($this->name != null){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_NAME.'>';
            $xmlStr .= $this->name->output();
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_NAME.'>'."\n";
        }
        //minimumVersion
        if($this->minimumVersion != null){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_MINIMUM_VERSION.'>'.$this->minimumVersion.'</'.RepositoryConst::LOM_TAG_MINIMUM_VERSION.'>'."\n";
        }
        //maximumVersion
        if($this->maximumVersion != null){
           $xmlStr .= '<'.RepositoryConst::LOM_TAG_MAXIMUM_VERSION.'>'.$this->maximumVersion.'</'.RepositoryConst::LOM_TAG_MAXIMUM_VERSION.'>'."\n";
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_OR_COMPOSITE.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_OR_COMPOSITE.'>'."\n";
        }
        return $xmlStr;
    }
}

/*
 * Resource型(カスタム) 
 * <resource>
 *      <identifier> (※複数存在する可能性アリ)  =>Identifier型
 *          [Identifier参照]
 *      </identifier>
 *      <description> (※複数存在する可能性アリ)
 *          <string language=""></string>  =>LangString型
 *      </description>
 * </resource>
 * タグ生成クラス
 */
class Repository_Oaipmh_LOM_Resource
{
    /*
     * メンバ変数
     */
    private $identifier = array();
    private $description = array();
    
    private $repositoryAction = null;
    
    /*
     * コンストラクタ
     */
    public function __construct($repositoryAction){
        $this->repositoryAction = $repositoryAction;
    }
    
    /*
     * Identifier設定
     * @param Repository_Oaipmh_LOM_Identifier $identifier
     */
    public function addIdentifier(Repository_Oaipmh_LOM_Identifier $identifier){
        array_push($this->identifier, $identifier);
    }
    /*
     * Description設定
     * @param Repository_Oaipmh_LOM_LangString $description
     */
    public function addDescription(Repository_Oaipmh_LOM_LangString $description){
        array_push($this->description, $description);
    }
    
    /*
     * Resource型の出力処理
     * @return string xml str
     */
    public function output(){
        $xmlStr = '';
        
        //identifier
        for($ii=0;$ii<count($this->identifier);$ii++){
            $xmlStr .= $this->identifier[$ii]->output();
        }
        //description
        for($ii=0;$ii<count($this->description);$ii++){
            $xmlStr .= '<'.RepositoryConst::LOM_TAG_DESCRIPTION.'>';
            $xmlStr .= $this->description[$ii]->output();
            $xmlStr .= '</'.RepositoryConst::LOM_TAG_DESCRIPTION.'>'."\n";
        }
        
        if(strlen($xmlStr)>0){
            $xmlStr = '<'.RepositoryConst::LOM_TAG_RESOURCE.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_RESOURCE.'>'."\n";
        }
        
        return $xmlStr;
    }
    
}
?>