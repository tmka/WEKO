<?php
// --------------------------------------------------------------------
//
// $Id: LearningObjectMetadata.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
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

class Repository_Oaipmh_Spase extends Repository_Oaipmh_FormatAbstract
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
    /*
     * SPASE variable values
     *
     */
    private $catalog = null;
    private $displaydata = null;
    private $granule = null;
    private $instrument = null;
    private $numericaldata = null;
    private $observatory = null;
    private $person = null;
    private $repository = null;
    //private $service = null;

    // const xml value
    const SPASE_VALUE_SOURCE = RepositoryConst::SPASE_VERSION;


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
        //$this->general = new Repository_Oaipmh_LOM_General($this->RepositoryAction);
        /*
        $this->lifeCycle = new Repository_Oaipmh_LOM_LifeCycle($this->RepositoryAction);
        $this->metaMetadate = new Repository_Oaipmh_LOM_MetaMetadata($this->RepositoryAction);
        $this->technical = new Repository_Oaipmh_LOM_Technical($this->RepositoryAction);
        //$this->educational = new Repository_Oaipmh_LOM_Educational($this->RepositoryAction);
        $this->educational = array();
        $this->rights = new Repository_Oaipmh_LOM_Rights($this->RepositoryAction);
        $this->relation = array();
        $this->annotation = array();
        $this->classification = array();
        */

        /*
         * SPASE class
         */
        $this->catalog = new Repository_Oaipmh_SPASE_Catalog($this->RepositoryAction);
        $this->displaydata = new Repository_Oaipmh_SPASE_Displaydata($this->RepositoryAction);
        $this->granule = new Repository_Oaipmh_SPASE_Granule($this->RepositoryAction);
        $this->instrument = new Repository_Oaipmh_SPASE_Instrument($this->RepositoryAction);
        $this->numericaldata = new Repository_Oaipmh_SPASE_Numericaldata($this->RepositoryAction);
        $this->observatory = new Repository_Oaipmh_SPASE_Observatory($this->RepositoryAction);
        $this->person = new Repository_Oaipmh_SPASE_Person($this->RepositoryAction);
        $this->repository = new Repository_Oaipmh_SPASE_Repository($this->RepositoryAction);
        //$this->service = new Repository_Oaipmh_SPASE_Service($this->RepositoryAction);

        //1.基本情報設定処理
        //$this->setBaseData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0]);
        $this->setBaseData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]);

        //2. マッピング情報設定処理
        $this->setMappingInfo($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]);

        //3. リファレンス設定処理
        /*
        if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]))
        {
            $this->setReference($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]);
        }
        */

        //4. 初期化
        $xml = '';

        //5. header出力処理
        $xml .= $this->outputHeader();
        
        //6. SPASEmetadata出力処理
        $xml .= $this->catalog->output();
        $xml .= $this->displaydata->output();
        $xml .= $this->granule->output();
        $xml .= $this->instrument->output();
        $xml .= $this->numericaldata->output();
        $xml .= $this->observatory->output();
        $xml .= $this->person->output();
        $xml .= $this->repository->output();
        //$xml .= $this->service->output();

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
        //$this->general->addTitle(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $title, $titleLang));
        //language
        //class Repository_Oaipmh_LOM_LangString

        //deleted 2015/10/28
        /*
        $this->catalog->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        $this->displaydata->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        //$this->granule->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        $this->instrument->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        $this->numericaldata->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        $this->observatory->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        $this->repository->addResourceheader_Resourcename(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        */


        //URI
        $uri = new Repository_Oaipmh_SPASE_Identifier($this->RepositoryAction,
                                                    $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI],
                                                    RepositoryConst::SPASE_URI);
        //$this->catalog->addResourceID(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        //$this->general->addIdentifier($uri);
        //$this->repository->addAccessurl_url(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $title, $titleLang));
        //$this->catalog->addResourceheader_ResourceID();

        //キーワード
        /*
        $keyword = explode("|", $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY]."|".$itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
        for($ii=0; $ii<count($keyword); $ii++)
        {
            $this->general->addKeyword(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $keyword[$ii]));
        }
        */

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

          //$lomMap = $mapping[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LOM_MAPPING];
          $spaseMap = $mapping[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];
          /*
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
            //nothing
          }
          */
          if(preg_match('/^Catalog/', $spaseMap)==1){
            $this->setCatalog($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Displaydata/', $spaseMap)==1){
              $this->setDisplaydata($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Granule/', $spaseMap)==1){
              $this->setGranule($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Instrument/', $spaseMap)==1){
              $this->setInstrument($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Numericaldata/', $spaseMap)==1){
              $this->setNumericaldata($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Observatory/', $spaseMap)==1){
              $this->setObservatory($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Person/', $spaseMap)==1){
              $this->setPerson($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Repository/', $spaseMap)==1){
              $this->setRepository($mapping[$ii], $metadata[$ii]);
              /*
            }else if(preg_match('/^Service/', $spaseMap)==1){
              $this->setService($mapping[$ii], $metadata[$ii]);
              */
            }else{
              //何もしない 
            }
      }
    }

    ///SPASE 
    private function setCatalog($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_CATALOG_RESOURCEID:
                    //$this->catalog->addResourceID($value);
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Acknowledgement($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Repositoryid($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Availability($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Accessurl_Name($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Accessurl_Description($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Format($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_DATAEXTENT_QUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Dataextent_Quantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Startdate($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Stopdate($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Relativestopdate($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Name($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Description($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Field_Fieldquantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Particle_Particletype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Particle_Particlequantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Wave_Wavetype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Wave_Wavequantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Mixed_Mixedquantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Support_Supportquantity($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_OBSERVEDREGION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addObservedregion($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Coordinatesystem_Coordinatesystemname($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Coordinatesystem_Coordinaterepresentation($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Northernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Southernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Easternmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Westernmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Unit($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Minimumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Maximumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Reference($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setDisplaydata($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEID:
                    //$this->catalog->addResourceID($value);
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_ACKNOWLEDGEMENT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Acknowledgement($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_REPOSITORYID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Repositoryid($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_AVAILABILITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Availability($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSURL_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Accessurl_Name($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Accessurl_Description($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_FORMAT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Format($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_ACCESSINFORMATION_DATAEXTENT_QUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addAccessinformation_Dataextent_Quantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_STARTDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Startdate($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_STOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Stopdate($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addTemporaldescription_Relativestopdate($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Name($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Description($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_FIELD_FIELDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Field_Fieldquantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_PARTICLE_PARTICLETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Particle_Particletype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Particle_Particlequantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_WAVE_WAVETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Wave_Wavetype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_WAVE_WAVEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Wave_Wavequantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_MIXED_MIXEDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Mixed_Mixedquantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addParameter_Support_Supportquantity($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_OBSERVEDREGION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addObservedregion($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Coordinatesystem_Coordinatesystemname($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Coordinatesystem_Coordinaterepresentation($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Northernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Southernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Easternmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Westernmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_UNIT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Unit($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_MINIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Minimumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Maximumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_SPATIALCOVERAGE_REFERENCE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addSpatialcoverage_Reference($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setGranule($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_GRANULE_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addReleaseDate($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_PARENTID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addParentID($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_STARTDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addStartDate($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_STOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addStopDate($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSource_SourceType($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SOURCE_URL:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSource_URL($tmp);
                    break;
                case RepositoryConst::SPSAE_GRANULE_SOUCE_DATAEXTENT_QUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSouce_Dataextent_Quantity($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Coordinatesystem_Coordinatesystemname($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Coordinatesystem_Coordinaterepresentation($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Northernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Southernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Easternmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Westernmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Unit($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Minimumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Maximumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->granule->addSpatialcoverage_Reference($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setInstrument($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_INSTRUMENT_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->instrument->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_TYPE:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->instrument->addInstrumenttype($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->instrument->addInvestigationname($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addObsevatoryID($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setNumericaldata($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_ACKNOWLEDGEMENT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Acknowledgement($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_REPOSITORYID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Repositoryid($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_AVAILABILITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Availability($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Accessurl_Name($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_ACCESSURL_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Accessurl_Description($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_FORMAT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Format($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_ACCESSINFORMATION_DATAEXTENT_QUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addAccessinformation_Dataextent_Quantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STARTDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addTemporaldescription_Startdate($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_STOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addTemporaldescription_Stopdate($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_TEMPORALDESCRIPTION_RELATIVESTOPDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addTemporaldescription_Relativestopdate($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_NAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Name($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Description($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_FIELD_FIELDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Field_Fieldquantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Particle_Particletype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_PARTICLE_PARTICLEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Particle_Particlequantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVETYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Wave_Wavetype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_WAVE_WAVEQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Wave_Wavequantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_MIXED_MIXEDQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Mixed_Mixedquantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PARAMETER_SUPPORT_SUPPORTQUANTITY:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addParameter_Support_Supportquantity($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_OBSERVEDREGION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addObservedregion($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Coordinatesystem_Coordinatesystemname($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Coordinatesystem_Coordinaterepresentation($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Northernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Southernmostlatitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Easternmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Westernmostlongitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_UNIT:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Unit($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MINIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Minimumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_MAXIMUMALTITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Maximumaltitude($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_SPATIALCOVERAGE_REFERENCE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addSpatialcoverage_Reference($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setObservatory($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->observatory->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->observatory->addLocation_Observatoryregion($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addLocation_CoordinateSystemName_Latitude($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addLocation_CoordinateSystemName_Longitude($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_OPERATINGSPAN_STARTDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->observatory->addOperatingSpan_StartDate($tmp);
                    break;
                default :
                    break;
            }
        }
    }

    private function setPerson($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_PERSON_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->person->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_PERSON_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->person->addReleasedate($tmp);
                    break;
                case RepositoryConst::SPASE_PERSON_PERSONNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->person->addPersonname($tmp);
                    break;
                case RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->person->addOrganizationname($tmp);
                    break;
                case RepositoryConst::SPASE_PERSON_EMAIL:
                    //echo $value;  ... $valueには値が入ってる
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    //$tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value);
                    //$tmp->addSource(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value));
                    $tmp->addArray(new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value));
                    //array_push($this->tmp, $tmp);
                    //$tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    //array_push($this->$tmp, $tmp);
                    //$this->person->addEmail($tmp);
                    //$this->person->addEmail($tmp);
                    
                    break;
                default :
                    break;
            }
        }
    }

    private function setRepository($mapping_item, $metadata_item){
        $language = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
        $spaseMap = $mapping_item[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

        for($ii=0; $ii<count($metadata_item); $ii++){

            $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii], 2);

            switch($spaseMap)
            {
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceID($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Resourcename($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Releasedate($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Description($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addResourceheader_Contact_PersonID($tmp);
                    break;
                default :
                    break;
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
                    $structure = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
                    $this->general->addStructure($structure);
                    break;
                case RepositoryConst::LOM_MAP_GNRL_AGGREGATION_LEVEL:
                    $aggregationLevel = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
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
                $this->lifeCycle->addStatus(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value));
            }

        }
        //publishDateの場合
        else if($lomMap == RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISH_DATE){
            for($ii=0;$ii<count($metadata_item);$ii++){
                $value = RepositoryOutputFilter::attributeValue($mapping_item, $metadata_item[$ii]);

                $contribute = new Repository_Oaipmh_LOM_Contribute($this->RepositoryAction);
                $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_PUBLISH_DATE));
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

        $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $roleValue));

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
            $contribute->addRole(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $roleValue));

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
                        $this->metaMetadate->addMetadataSchema(self::SPASE_VALUE_SOURCE);
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
                    $type = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
                      $this->technical->addOrComposite(RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE, $type);

                    break;
                case RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME:
                    $name = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);

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
                    $cost = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
                    $this->rights->addCost($cost);
                    break;
                case RepositoryConst::LOM_MAP_RGHTS_COPYRIGHT_AND_OTHER_RESTRICTIONS:
                    $copy = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
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

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_PART_OF);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_PART_OF:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_HAS_PART);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_VERSION_OF:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_VERSION_OF);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_VERSION:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_HAS_VERSION);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_FORMAT_OF:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_FORMAT_OF);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_HAS_FORMAT:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_HAS_FORMAT);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_REFERENCES:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_REFERENCES);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_REFERENCED_BY:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_REFERENCED_BY);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_BASED_ON:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_BASED_ON);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value, RepositoryConst::LOM_IS_BASED_ON));

                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_BASIS_FOR:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_BASIS_FOR);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value, RepositoryConst::LOM_IS_BASIS_FOR));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_REQUIRES:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_REQUIRES);
                $relation->addKind($vocabulary);

                $resource = new Repository_Oaipmh_LOM_Resource($this->RepositoryAction);

                for($ii=0; $ii<count($metadata); $ii++){
                    $value = RepositoryOutputFilter::attributeValue($mapping, $metadata[$ii]);
                    $resource->addIdentifier(new Repository_Oaipmh_LOM_Identifier($this->RepositoryAction, $value));
                }

                $relation->addResource($resource);

                break;
            case RepositoryConst::LOM_MAP_RLTN_IS_REQUIRED_BY:

                $vocabulary = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, RepositoryConst::LOM_IS_REQUIRESD_BY);
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
                    $purpose = new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $value);
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
                $this->relation[$target_idx]->addKind(new Repository_Oaipmh_LOM_Vocabulary($this->RepositoryAction, self::SPASE_VALUE_SOURCE, $ref));
            }

            // set discription
            $destItemId = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_ID];
            $destItemNo = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_NO];
            // get detail url
            $refUrl = $this->RepositoryAction->getDetailUri($destItemId, $destItemNo);
            $resource->addDescription(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $refUrl));
            //$repository->addAccessurl_url(new Repository_Oaipmh_LOM_LangString($this->RepositoryAction, $refUrl));
            //6240行目の関数, Repository_Oaipmh_LOM_Relation 
            //$this->relation[$target_idx]->addResource($resource);
            $this->relation[$target_idx]->addResource($resource);
        }
    }

    /*
     * ヘッダ出力処理
     */
    private function outputHeader()
    {
        $xml = '';
        $xml .= '<'.RepositoryConst::SPASE_START;
        $xml .= ' lang="'.RepositoryConst::SPASE_LANG_ENGLISH.'"';
        $xml .= ' xsi:schemaLocation="http://www.spase-group.org/data/schema/';
        $xml .= ' http://www.spase-group.org/data/schema/spase-2_2_3.xsd"';
        $xml .= ' xmlns="http://www.spase-group.org/data/schema/"> '."\n";
        return $xml;
    }

    /*
     * フッダ出力処理
     */
    private function outputFooter()
    {
        $xml = '</'.RepositoryConst::SPASE_START.'>'."\n";
        return $xml;
    }

    /****************************************** datatype *********************************************/
}


/*SPASE classes*/
class Repository_Oaipmh_SPASE_Catalog
{
  /*
   * メンバ変数 LOM
   */
  private $identifier = array();
  private $contribute = array();
  //private $metadataSchema = array();
  //private $language = null;

  //for spase
  //private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Accessinformation_Repositoryid = null;
  private $Accessinformation_Availability = null;
  private $Accessinformation_Accessrights = null;
  private $Accessinformation_Accessurl_Name = null;
  private $Accessinformation_Accessurl_URL = null;
  private $Accessinformation_Accessurl_Description = null;
  private $Accessinformation_Format = null;
  private $Accessinformation_Dataextent_Quantity = null;

  private $Temporaldescription_Startdate = null;
  private $Temporaldescription_Stopdate = null;
  private $Temporaldescription_Relativestopdate = null;

  private $Parameter_Name = null;
  private $Parameter_Description = null;
  private $Parameter_Field_Fieldquantity = null;
  private $Parameter_Particle_Particletype = null;
  private $Parameter_Particle_Particlequantity = null;
  private $Parameter_Wave_Wavetype = null;
  private $Parameter_Wave_Wavequantity = null;
  private $Parameter_Mixed_Mixedquantity = null;
  private $Parameter_Support_Supportquantity = null;

  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Observedregion = null;
  
  private $Spatialcoverage_Coordinatesystem_Coordinatesystemname = null;
  private $Spatialcoverage_Coordinatesystem_Coordinaterepresentation = null;
  private $Spatialcoverage_Northernmostlatitude = null;
  private $Spatialcoverage_Southernmostlatitude = null;
  private $Spatialcoverage_Easternmostlongitude = null;
  private $Spatialcoverage_Westernmostlongitude = null;
  private $Spatialcoverage_Unit = null;
  private $Spatialcoverage_Minimumaltitude = null;
  private $Spatialcoverage_Maximumaltitude = null;
  private $Spatialcoverage_Reference = null;

/*
  private $Phenomenontype = array();
  private $Measurementtype = array();
  private $Temporaldescription_Startdate = array();
  private $Temporaldescription_Stopdate = array();
  private $Temporaldescription_Relativestopdate = array();
  private $Observedregion = array();
*/

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  /*
  public function addSomething(Repository_Oaipmh_SPASE_Catalog $val){
    array_push($this->val, $val);
  }
  */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_Role){
    array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
  }

  public function addAccessinformation_Repositoryid(Repository_Oaipmh_SPASE_LangString $Accessinformation_Repositoryid){
    if($this->Accessinformation_Repositoryid == null){
        $this->Accessinformation_Repositoryid = $Accessinformation_Repositoryid;
    }
  }

  public function addAccessinformation_Availability(Repository_Oaipmh_SPASE_LangString $Accessinformation_Availability){
    if($this->Accessinformation_Availability == null){
        $this->Accessinformation_Availability = $Accessinformation_Availability;
    }
  }

  public function addAccessinformation_Accessrights(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessrights){
    if($this->Accessinformation_Accessrights == null){
        $this->Accessinformation_Accessrights = $Accessinformation_Accessrights;
    }
  }

  public function addAccessinformation_Accessurl_Name(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Name){
    if($this->Accessinformation_Accessurl_Name == null){
        $this->Accessinformation_Accessurl_Name = $Accessinformation_Accessurl_Name;
    }
  }

  public function addAccessinformation_Accessurl_URL(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_URL){
    if($this->Accessinformation_Accessurl_URL == null){
        $this->Accessinformation_Accessurl_URL = $Accessinformation_Accessurl_URL;
    }
  }

  public function addAccessinformation_Accessurl_Description(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Description){
    if($this->Accessinformation_Accessurl_Description == null){
        $this->Accessinformation_Accessurl_Description = $Accessinformation_Accessurl_Description;
    }
  }

  public function addAccessinformation_Format(Repository_Oaipmh_SPASE_LangString $Accessinformation_Format){
    if($this->Accessinformation_Format == null){
        $this->Accessinformation_Format = $Accessinformation_Format;
    }
  }

  public function addAccessinformation_Dataextent_Quantity(Repository_Oaipmh_SPASE_LangString $Accessinformation_Dataextent_Quantity){
    if($this->Accessinformation_Dataextent_Quantity == null){
        $this->Accessinformation_Dataextent_Quantity = $Accessinformation_Dataextent_Quantity;
    }
  }

  public function addTemporaldescription_Startdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Startdate){
    if($this->Temporaldescription_Startdate == null){
        $this->Temporaldescription_Startdate = $Temporaldescription_Startdate;
    }
  }

  public function addTemporaldescription_Stopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Stopdate){
    if($this->Temporaldescription_Stopdate == null){
        $this->Temporaldescription_Stopdate = $Temporaldescription_Stopdate;
    }
  }

  public function addTemporaldescription_Relativestopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Relativestopdate){
    if($this->Temporaldescription_Relativestopdate == null){
        $this->Temporaldescription_Relativestopdate = $Temporaldescription_Relativestopdate;
    }
  }

  public function addParameter_Name(Repository_Oaipmh_SPASE_LangString $Parameter_Name){
    if($this->Parameter_Name == null){
        $this->Parameter_Name = $Parameter_Name;
    }
  }

  public function addParameter_Description(Repository_Oaipmh_SPASE_LangString $Parameter_Description){
    if($this->Parameter_Description == null){
        $this->Parameter_Description = $Parameter_Description;
    }
  }

  public function addParameter_Field_Fieldquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Field_Fieldquantity){
    if($this->Parameter_Field_Fieldquantity == null){
        $this->Parameter_Field_Fieldquantity = $Parameter_Field_Fieldquantity;
    }
  }

  public function addParameter_Particle_Particletype(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particletype){
    if($this->Parameter_Particle_Particletype == null){
        $this->Parameter_Particle_Particletype = $Parameter_Particle_Particletype;
    }
  }

  public function addParameter_Particle_Particlequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particlequantity){
    if($this->Parameter_Particle_Particlequantity == null){
        $this->Parameter_Particle_Particlequantity = $Parameter_Particle_Particlequantity;
    }
  }


  public function addParameter_Wave_Wavetype(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavetype){
    if($this->Parameter_Wave_Wavetype == null){
        $this->Parameter_Wave_Wavetype = $Parameter_Wave_Wavetype;
    }
  }

  public function addParameter_Wave_Wavequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavequantity){
    if($this->Parameter_Wave_Wavequantity == null){
        $this->Parameter_Wave_Wavequantity = $Parameter_Wave_Wavequantity;
    }
  }

  public function addParameter_Mixed_Mixedquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Mixed_Mixedquantity){
    if($this->Parameter_Mixed_Mixedquantity == null){
        $this->Parameter_Mixed_Mixedquantity = $Parameter_Mixed_Mixedquantity;
    }
  }


  public function addParameter_Support_Supportquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Support_Supportquantity){
    if($this->Parameter_Support_Supportquantity == null){
        $this->Parameter_Support_Supportquantity = $Parameter_Support_Supportquantity;
    }
  }

  public function addObservedregion(Repository_Oaipmh_SPASE_LangString $Observedregion){
    if($this->Observedregion == null){
        $this->Observedregion = $Observedregion;
    }
  }

  public function addPhenomenontype(Repository_Oaipmh_SPASE_LangString $Phenomenontype){
    if($this->Phenomenontype == null){
        $this->Phenomenontype = $Phenomenontype;
    }
  }

  public function addMeasurementtype(Repository_Oaipmh_SPASE_LangString $Measurementtype){
    if($this->Measurementtype == null){
        $this->Measurementtype = $Measurementtype;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinatesystemname(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinatesystemname){
    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname = $Spatialcoverage_Coordinatesystem_Coordinatesystemname;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinaterepresentation(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinaterepresentation){
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation = $Spatialcoverage_Coordinatesystem_Coordinaterepresentation;
    }
  }


  public function addSpatialcoverage_Northernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Northernmostlatitude){
    if($this->Spatialcoverage_Northernmostlatitude == null){
        $this->Spatialcoverage_Northernmostlatitude = $Spatialcoverage_Northernmostlatitude;
    }
  }

  public function addSpatialcoverage_Southernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Southernmostlatitude){
    if($this->Spatialcoverage_Southernmostlatitude == null){
        $this->Spatialcoverage_Southernmostlatitude = $Spatialcoverage_Southernmostlatitude;
    }
  }

  public function addSpatialcoverage_Easternmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Easternmostlongitude){
    if($this->Spatialcoverage_Easternmostlongitude == null){
        $this->Spatialcoverage_Easternmostlongitude = $Spatialcoverage_Easternmostlongitude;
    }
  }


  public function addSpatialcoverage_Westernmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Westernmostlongitude){
    if($this->Spatialcoverage_Westernmostlongitude == null){
        $this->Spatialcoverage_Westernmostlongitude = $Spatialcoverage_Westernmostlongitude;
    }
  }

  public function addSpatialcoverage_Unit(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Unit){
    if($this->Spatialcoverage_Unit == null){
        $this->Spatialcoverage_Unit = $Spatialcoverage_Unit;
    }
  }

  public function addSpatialcoverage_Minimumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Minimumaltitude){
    if($this->Spatialcoverage_Minimumaltitude == null){
        $this->Spatialcoverage_Minimumaltitude = $Spatialcoverage_Minimumaltitude;
    }
  }


  public function addSpatialcoverage_Maximumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Maximumaltitude){
    if($this->Spatialcoverage_Maximumaltitude == null){
        $this->Spatialcoverage_Maximumaltitude = $Spatialcoverage_Maximumaltitude;
    }
  }

  public function addSpatialcoverage_Reference(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Reference){
    if($this->Spatialcoverage_Reference == null){
        $this->Spatialcoverage_Reference = $Spatialcoverage_Reference;
    }
  }


  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }

  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output()
  {
    $xmlStr = '';

    $xmlStr .= '<Version>';
    $xmlStr .= RepositoryConst::SPASE_VERSION;
    $xmlStr .= '</Version>'. "\n";

    /*
    for($ii=0;$ii<count($this->identifier);$ii++){
      $xmlStr .= $this->identifier[$ii]->output();
    }
    */
    if($this->ResourceID != null)
    {
      $xmlStr .= '<'.RepositoryConst::SPASE_CATALOG.'>';
        $xml = $this->ResourceID->output();
        if(strlen($xml)>0){
            $xmlStr .= '<'.RepositoryConst::SPASE_C_RESOURCEID.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_C_RESOURCEID.'>'."\n";
        }
    }
  
    
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
            /*
            $xmlStr .= '<'.RepositoryConst::SPASE_C_RESOURCEHEADER_RESOURCENAME.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_C_RESOURCEHEADER_RESOURCENAME.'>'."\n";
            */
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

    if($this->Resourceheader_Resourcename != null)
    {
    $xmlStr .= '</ResourceHeader>'."\n";
    }
  
    
    
    if($this->Accessinformation_Repositoryid != null)
    {
      $xmlStr .= '<AccessInformation>'."\n";
        $xml = $this->Accessinformation_Repositoryid->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_REPOSITORYID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Availability != null)
    {
        $xml = $this->Accessinformation_Availability->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_AVAILABILITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessrights != null)
    {
        $xml = $this->Accessinformation_Accessrights->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_ACCESSRIGHTS);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  

    if($this->Accessinformation_Accessurl_Name != null || $this->Accessinformation_Accessurl_URL != null ||
      $this->Accessinformation_Accessurl_Description != null){
      $xmlStr .= '<AccessURL>'."\n";

    if($this->Accessinformation_Accessurl_Name != null)
    {
        $xml = $this->Accessinformation_Accessurl_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_ACCESSURL_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Accessurl_URL != null)
    {
        $xml = $this->Accessinformation_Accessurl_URL->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_ACCESSURL_URL);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessurl_Description != null)
    {
        $xml = $this->Accessinformation_Accessurl_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_ACCESSURL_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    $xmlStr .= '</AccessURL>'."\n";
    }
  
      if($this->Accessinformation_Format != null)
    {
        $xml = $this->Accessinformation_Format->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_FORMAT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Accessinformation_Dataextent_Quantity != null)
    { 
      $xmlStr .= '<DataExtent>'."\n";
        $xml = $this->Accessinformation_Dataextent_Quantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_ACCESSINFORMATION_DATAEXTENT_QUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</DataExtent>'."\n";
    }

    if($this->Accessinformation_Repositoryid != null)
    {
    $xmlStr .= '</AccessInformation>'."\n";
    }
    

    if($this->Temporaldescription_Startdate != NULL || $this->Temporaldescription_Stopdate != NULL 
      || $this->Temporaldescription_Relativestopdate != NULL){
      $xmlStr .= '<Temporaldescription>'."\n";
  
    if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_TEMPORALDESCRIPTION_STARTDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_TEMPORALDESCRIPTION_STOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_TEMPORALDESCRIPTION_RELATIVESTOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Temporaldescription>'."\n";
    }
  

    
    if($this->Parameter_Name != null)
    {
      $xmlStr .= '<Parameter>'."\n";
        $xml = $this->Parameter_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Description != null)
    {
        $xml = $this->Parameter_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Parameter_Field_Fieldquantity != null)
    {
      $xmlStr .= '<Field>'."\n";
        $xml = $this->Parameter_Field_Fieldquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_FIELD_FIELDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Field>'."\n";
    }
  

    if($this->Parameter_Particle_Particletype != NULL || $this->Parameter_Particle_Particlequantity != NULL){
      $xmlStr .= '<Particle>'."\n";
    if($this->Parameter_Particle_Particletype != null)
    {
        $xml = $this->Parameter_Particle_Particletype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_PARTICLE_PARTICLETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Particle_Particlequantity != null)
    {
        $xml = $this->Parameter_Particle_Particlequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_PARTICLE_PARTICLEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Particle>'."\n";
    }

  
    if($this->Parameter_Wave_Wavetype != NULL || $this->Parameter_Wave_Wavequantity != NULL){
      $xmlStr .= '<Wave>'."\n";
    

    if($this->Parameter_Wave_Wavetype != null)
    {
        $xml = $this->Parameter_Wave_Wavetype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_WAVE_WAVETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Wave_Wavequantity != null)
    {
        $xml = $this->Parameter_Wave_Wavequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_WAVE_WAVEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Wave>'."\n";
    }
  
    if($this->Parameter_Mixed_Mixedquantity != null)
    {
      $xmlStr .= '<Mixed>'."\n";
        $xml = $this->Parameter_Mixed_Mixedquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_MIXED_MIXEDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Mixed>'."\n";
    }
  
    if($this->Parameter_Support_Supportquantity != null)
    {
      $xmlStr .= '<Support>'."\n";
        $xml = $this->Parameter_Support_Supportquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PARAMETER_SUPPORT_SUPPORTQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
        $xmlStr .= '</Support>'."\n";
  
    }

    if($this->Parameter_Name != null)
    {
    $xmlStr .= '</Parameter>'."\n";
    }

    
    if($this->Phenomenontype != null)
    {
        $xml = $this->Phenomenontype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_PHENOMENONTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Measurementtype != null)
    {
        $xml = $this->Measurementtype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Startdate != null || $this->Temporaldescription_Stopdate != null 
      ||$this->Temporaldescription_Relativestopdate != null){

      $xmlStr .= '<Temporaldescription>'."\n";
      if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
    $xmlStr .= '</Temporaldescription>'."\n";
    }
  
    if($this->Observedregion != null)
    {
        $xml = $this->Observedregion->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_OBSERVEDREGION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){

    $xmlStr .= '<SpatialCoverage>'."\n";
    $xmlStr .= '<CoordinateSystem>'."\n";
      if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</CoordinateSystem>'."\n";
    }
  
    if($this->Spatialcoverage_Northernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Northernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Southernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Southernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Easternmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Easternmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Westernmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Westernmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Unit != null)
    {
        $xml = $this->Spatialcoverage_Unit->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_UNIT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Minimumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Minimumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_MINIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Maximumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Maximumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_MAXIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Reference != null)
    {
        $xml = $this->Spatialcoverage_Reference->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_SPATIALCOVERAGE_REFERENCE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){
    $xmlStr .= '</SpatialCoverage>'."\n";
    }


      if($this->ResourceID != null)
    {
    $xmlStr .= '</'.RepositoryConst::SPASE_CATALOG.'>';
  }

    return $xmlStr;
  }
}

//DisplayData Class
class Repository_Oaipmh_SPASE_Displaydata
{

  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Accessinformation_Repositoryid = null;
  private $Accessinformation_Availability = null;
  private $Accessinformation_Accessrights = null;
  private $Accessinformation_Accessurl_Name = null;
  private $Accessinformation_Accessurl_URL = null;
  private $Accessinformation_Accessurl_Description = null;
  private $Accessinformation_Format = null;
  private $Accessinformation_Dataextent_Quantity = null;

  private $Temporaldescription_Startdate = null;
  private $Temporaldescription_Stopdate = null;
  private $Temporaldescription_Relativestopdate = null;

  private $Parameter_Name = null;
  private $Parameter_Description = null;
  private $Parameter_Field_Fieldquantity = null;
  private $Parameter_Particle_Particletype = null;
  private $Parameter_Particle_Particlequantity = null;
  private $Parameter_Wave_Wavetype = null;
  private $Parameter_Wave_Wavequantity = null;
  private $Parameter_Mixed_Mixedquantity = null;
  private $Parameter_Support_Supportquantity = null;

  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Observedregion = null;

  private $Spatialcoverage_Coordinatesystem_Coordinatesystemname = null;
  private $Spatialcoverage_Coordinatesystem_Coordinaterepresentation = null;
  private $Spatialcoverage_Northernmostlatitude = null;
  private $Spatialcoverage_Southernmostlatitude = null;
  private $Spatialcoverage_Easternmostlongitude = null;
  private $Spatialcoverage_Westernmostlongitude = null;
  private $Spatialcoverage_Unit = null;
  private $Spatialcoverage_Minimumaltitude = null;
  private $Spatialcoverage_Maximumaltitude = null;
  private $Spatialcoverage_Reference = null;


  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_Role){
    array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
  }

  public function addAccessinformation_Repositoryid(Repository_Oaipmh_SPASE_LangString $Accessinformation_Repositoryid){
    if($this->Accessinformation_Repositoryid == null){
        $this->Accessinformation_Repositoryid = $Accessinformation_Repositoryid;
    }
  }

  public function addAccessinformation_Availability(Repository_Oaipmh_SPASE_LangString $Accessinformation_Availability){
    if($this->Accessinformation_Availability == null){
        $this->Accessinformation_Availability = $Accessinformation_Availability;
    }
  }

    public function addAccessinformation_Accessrights(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessrights){
    if($this->Accessinformation_Accessrights == null){
        $this->Accessinformation_Accessrights = $Accessinformation_Accessrights;
    }
  }

  public function addAccessinformation_Accessurl_Name(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Name){
    if($this->Accessinformation_Accessurl_Name == null){
        $this->Accessinformation_Accessurl_Name = $Accessinformation_Accessurl_Name;
    }
  }

  public function addAccessinformation_Accessurl_URL(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_URL){
    if($this->Accessinformation_Accessurl_URL == null){
        $this->Accessinformation_Accessurl_URL = $Accessinformation_Accessurl_URL;
    }
  }

  public function addAccessinformation_Accessurl_Description(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Description){
    if($this->Accessinformation_Accessurl_Description == null){
        $this->Accessinformation_Accessurl_Description = $Accessinformation_Accessurl_Description;
    }
  }

  public function addAccessinformation_Format(Repository_Oaipmh_SPASE_LangString $Accessinformation_Format){
    if($this->Accessinformation_Format == null){
        $this->Accessinformation_Format = $Accessinformation_Format;
    }
  }

  public function addAccessinformation_Dataextent_Quantity(Repository_Oaipmh_SPASE_LangString $Accessinformation_Dataextent_Quantity){
    if($this->Accessinformation_Dataextent_Quantity == null){
        $this->Accessinformation_Dataextent_Quantity = $Accessinformation_Dataextent_Quantity;
    }
  }

  public function addTemporaldescription_Startdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Startdate){
    if($this->Temporaldescription_Startdate == null){
        $this->Temporaldescription_Startdate = $Temporaldescription_Startdate;
    }
  }

  public function addTemporaldescription_Stopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Stopdate){
    if($this->Temporaldescription_Stopdate == null){
        $this->Temporaldescription_Stopdate = $Temporaldescription_Stopdate;
    }
  }

  public function addTemporaldescription_Relativestopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Relativestopdate){
    if($this->Temporaldescription_Relativestopdate == null){
        $this->Temporaldescription_Relativestopdate = $Temporaldescription_Relativestopdate;
    }
  }

  public function addParameter_Name(Repository_Oaipmh_SPASE_LangString $Parameter_Name){
    if($this->Parameter_Name == null){
        $this->Parameter_Name = $Parameter_Name;
    }
  }

  public function addParameter_Description(Repository_Oaipmh_SPASE_LangString $Parameter_Description){
    if($this->Parameter_Description == null){
        $this->Parameter_Description = $Parameter_Description;
    }
  }

  public function addParameter_Field_Fieldquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Field_Fieldquantity){
    if($this->Parameter_Field_Fieldquantity == null){
        $this->Parameter_Field_Fieldquantity = $Parameter_Field_Fieldquantity;
    }
  }

  public function addParameter_Particle_Particletype(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particletype){
    if($this->Parameter_Particle_Particletype == null){
        $this->Parameter_Particle_Particletype = $Parameter_Particle_Particletype;
    }
  }

  public function addParameter_Particle_Particlequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particlequantity){
    if($this->Parameter_Particle_Particlequantity == null){
        $this->Parameter_Particle_Particlequantity = $Parameter_Particle_Particlequantity;
    }
  }


  public function addParameter_Wave_Wavetype(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavetype){
    if($this->Parameter_Wave_Wavetype == null){
        $this->Parameter_Wave_Wavetype = $Parameter_Wave_Wavetype;
    }
  }

  public function addParameter_Wave_Wavequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavequantity){
    if($this->Parameter_Wave_Wavequantity == null){
        $this->Parameter_Wave_Wavequantity = $Parameter_Wave_Wavequantity;
    }
  }

  public function addParameter_Mixed_Mixedquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Mixed_Mixedquantity){
    if($this->Parameter_Mixed_Mixedquantity == null){
        $this->Parameter_Mixed_Mixedquantity = $Parameter_Mixed_Mixedquantity;
    }
  }


  public function addParameter_Support_Supportquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Support_Supportquantity){
    if($this->Parameter_Support_Supportquantity == null){
        $this->Parameter_Support_Supportquantity = $Parameter_Support_Supportquantity;
    }
  }

  public function addPhenomenontype(Repository_Oaipmh_SPASE_LangString $Phenomenontype){
    if($this->Phenomenontype == null){
        $this->Phenomenontype = $Phenomenontype;
    }
  }

  public function addMeasurementtype(Repository_Oaipmh_SPASE_LangString $Measurementtype){
    if($this->Measurementtype == null){
        $this->Measurementtype = $Measurementtype;
    }
  }

  public function addObservedregion(Repository_Oaipmh_SPASE_LangString $Observedregion){
    if($this->Observedregion == null){
        $this->Observedregion = $Observedregion;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinatesystemname(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinatesystemname){
    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname = $Spatialcoverage_Coordinatesystem_Coordinatesystemname;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinaterepresentation(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinaterepresentation){
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation = $Spatialcoverage_Coordinatesystem_Coordinaterepresentation;
    }
  }

  public function addSpatialcoverage_Northernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Northernmostlatitude){
    if($this->Spatialcoverage_Northernmostlatitude == null){
        $this->Spatialcoverage_Northernmostlatitude = $Spatialcoverage_Northernmostlatitude;
    }
  }

  public function addSpatialcoverage_Southernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Southernmostlatitude){
    if($this->Spatialcoverage_Southernmostlatitude == null){
        $this->Spatialcoverage_Southernmostlatitude = $Spatialcoverage_Southernmostlatitude;
    }
  }

  public function addSpatialcoverage_Easternmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Easternmostlongitude){
    if($this->Spatialcoverage_Easternmostlongitude == null){
        $this->Spatialcoverage_Easternmostlongitude = $Spatialcoverage_Easternmostlongitude;
    }
  }


  public function addSpatialcoverage_Westernmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Westernmostlongitude){
    if($this->Spatialcoverage_Westernmostlongitude == null){
        $this->Spatialcoverage_Westernmostlongitude = $Spatialcoverage_Westernmostlongitude;
    }
  }

  public function addSpatialcoverage_Unit(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Unit){
    if($this->Spatialcoverage_Unit == null){
        $this->Spatialcoverage_Unit = $Spatialcoverage_Unit;
    }
  }

  public function addSpatialcoverage_Minimumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Minimumaltitude){
    if($this->Spatialcoverage_Minimumaltitude == null){
        $this->Spatialcoverage_Minimumaltitude = $Spatialcoverage_Minimumaltitude;
    }
  }


  public function addSpatialcoverage_Maximumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Maximumaltitude){
    if($this->Spatialcoverage_Maximumaltitude == null){
        $this->Spatialcoverage_Maximumaltitude = $Spatialcoverage_Maximumaltitude;
    }
  }

  public function addSpatialcoverage_Reference(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Reference){
    if($this->Spatialcoverage_Reference == null){
        $this->Spatialcoverage_Reference = $Spatialcoverage_Reference;
    }
  }

  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){
    $xmlStr = '';

    
  
    if($this->ResourceID != null)
    {
      $xmlStr .= '<'.RepositoryConst::SPASE_DISPLAYDATA.'>';
        $xml = $this->ResourceID->output();
        if(strlen($xml)>0){
            $xmlStr .= '<'.RepositoryConst::SPASE_DD_RESOURCEID.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_DD_RESOURCEID.'>'."\n";
        }
    }
  
    
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
            /*
            $xmlStr .= '<'.RepositoryConst::SPASE_DD_RESOURCEHEADER_RESOURCENAME.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_DD_RESOURCEHEADER_RESOURCENAME.'>'."\n";
            */
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

    if($this->Resourceheader_Resourcename != null)
    {
    $xmlStr .= '</ResourceHeader>'."\n";
    }

    
    
    if($this->Accessinformation_Repositoryid != null)
    {
      $xmlStr .= '<AccessInformation>'."\n";
        $xml = $this->Accessinformation_Repositoryid->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_REPOSITORYID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Availability != null)
    {
        $xml = $this->Accessinformation_Availability->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_AVAILABILITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessrights != null)
    {
        $xml = $this->Accessinformation_Accessrights->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_ACCESSRIGHTS);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  

    if($this->Accessinformation_Accessurl_Name != null || $this->Accessinformation_Accessurl_URL != null ||
      $this->Accessinformation_Accessurl_Description != null){
      $xmlStr .= '<AccessURL>'."\n";

    if($this->Accessinformation_Accessurl_Name != null)
    {
        $xml = $this->Accessinformation_Accessurl_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_ACCESSURL_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Accessurl_URL != null)
    {
        $xml = $this->Accessinformation_Accessurl_URL->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_ACCESSURL_URL);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessurl_Description != null)
    {
        $xml = $this->Accessinformation_Accessurl_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_ACCESSURL_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    $xmlStr .= '</AccessURL>'."\n";
    }
  
      if($this->Accessinformation_Format != null)
    {
        $xml = $this->Accessinformation_Format->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_FORMAT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Accessinformation_Dataextent_Quantity != null)
    { 
      $xmlStr .= '<DataExtent>'."\n";
        $xml = $this->Accessinformation_Dataextent_Quantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_ACCESSINFORMATION_DATAEXTENT_QUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</DataExtent>'."\n";
    }

    if($this->Accessinformation_Repositoryid != null)
    {
    $xmlStr .= '</AccessInformation>'."\n";
    }
    

    if($this->Temporaldescription_Startdate != NULL || $this->Temporaldescription_Stopdate != NULL 
      || $this->Temporaldescription_Relativestopdate != NULL){
      $xmlStr .= '<Temporaldescription>'."\n";
  
    if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_TEMPORALDESCRIPTION_STARTDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_TEMPORALDESCRIPTION_STOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_TEMPORALDESCRIPTION_RELATIVESTOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Temporaldescription>'."\n";
    }
  

    

    if($this->Parameter_Name != null)
    {
        $xmlStr .= '<Parameter>'."\n";
        $xml = $this->Parameter_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Description != null)
    {
        $xml = $this->Parameter_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Parameter_Field_Fieldquantity != null)
    {
      $xmlStr .= '<Field>'."\n";
        $xml = $this->Parameter_Field_Fieldquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_FIELD_FIELDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Field>'."\n";
    }
  

    if($this->Parameter_Particle_Particletype != NULL || $this->Parameter_Particle_Particlequantity != NULL){
      $xmlStr .= '<Particle>'."\n";
    if($this->Parameter_Particle_Particletype != null)
    {
        $xml = $this->Parameter_Particle_Particletype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_PARTICLE_PARTICLETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Particle_Particlequantity != null)
    {
        $xml = $this->Parameter_Particle_Particlequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_PARTICLE_PARTICLEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Particle>'."\n";
    }

  
    if($this->Parameter_Wave_Wavetype != NULL || $this->Parameter_Wave_Wavequantity != NULL){
      $xmlStr .= '<Wave>'."\n";
    

    if($this->Parameter_Wave_Wavetype != null)
    {
        $xml = $this->Parameter_Wave_Wavetype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_WAVE_WAVETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Wave_Wavequantity != null)
    {
        $xml = $this->Parameter_Wave_Wavequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_WAVE_WAVEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Wave>'."\n";
    }
  
    if($this->Parameter_Mixed_Mixedquantity != null)
    {
      $xmlStr .= '<Mixed>'."\n";
        $xml = $this->Parameter_Mixed_Mixedquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_MIXED_MIXEDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Mixed>'."\n";
    }
  
    if($this->Parameter_Support_Supportquantity != null)
    {
      $xmlStr .= '<Support>'."\n";
        $xml = $this->Parameter_Support_Supportquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PARAMETER_SUPPORT_SUPPORTQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
        $xmlStr .= '</Support>'."\n";
  
    }

    if($this->Parameter_Name != null)
    {
    $xmlStr .= '</Parameter>'."\n";
  }

    
    if($this->Phenomenontype != null)
    {
        $xml = $this->Phenomenontype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_PHENOMENONTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Measurementtype != null)
    {
        $xml = $this->Measurementtype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Startdate != null || $this->Temporaldescription_Stopdate != null 
      ||$this->Temporaldescription_Relativestopdate != null){

      $xmlStr .= '<Temporaldescription>'."\n";
      if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
    $xmlStr .= '</Temporaldescription>'."\n";
    }
  
    if($this->Observedregion != null)
    {
        $xml = $this->Observedregion->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_OBSERVEDREGION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){

    $xmlStr .= '<SpatialCoverage>'."\n";
    $xmlStr .= '<CoordinateSystem>'."\n";
      if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</CoordinateSystem>'."\n";
    }
  
    if($this->Spatialcoverage_Northernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Northernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Southernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Southernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Easternmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Easternmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Westernmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Westernmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Unit != null)
    {
        $xml = $this->Spatialcoverage_Unit->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_UNIT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Minimumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Minimumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_MINIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Maximumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Maximumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_MAXIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Reference != null)
    {
        $xml = $this->Spatialcoverage_Reference->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_SPATIALCOVERAGE_REFERENCE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){
    $xmlStr .= '</SpatialCoverage>'."\n";
    }

    if($this->ResourceID != null)
    {
    $xmlStr .= '</'.RepositoryConst::SPASE_DISPLAYDATA.'>';
    }

    return $xmlStr;
  }
}

class Repository_Oaipmh_SPASE_NumericalData
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Accessinformation_Repositoryid = null;
  private $Accessinformation_Availability = null;
  private $Accessinformation_Accessrights = null;
  private $Accessinformation_Accessurl_Name = null;
  private $Accessinformation_Accessurl_URL = null;
  private $Accessinformation_Accessurl_Description = null;
  private $Accessinformation_Format = null;
  private $Accessinformation_Dataextent_Quantity = null;

  private $Temporaldescription_Startdate = null;
  private $Temporaldescription_Stopdate = null;
  private $Temporaldescription_Relativestopdate = null;

  private $Parameter_Name = null;
  private $Parameter_Description = null;
  private $Parameter_Field_Fieldquantity = null;
  private $Parameter_Particle_Particletype = null;
  private $Parameter_Particle_Particlequantity = null;
  private $Parameter_Wave_Wavetype = null;
  private $Parameter_Wave_Wavequantity = null;
  private $Parameter_Mixed_Mixedquantity = null;
  private $Parameter_Support_Supportquantity = null;

  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Observedregion = null;
  
  private $Spatialcoverage_Coordinatesystem_Coordinatesystemname = null;
  private $Spatialcoverage_Coordinatesystem_Coordinaterepresentation = null;
  private $Spatialcoverage_Northernmostlatitude = null;
  private $Spatialcoverage_Southernmostlatitude = null;
  private $Spatialcoverage_Easternmostlongitude = null;
  private $Spatialcoverage_Westernmostlongitude = null;
  private $Spatialcoverage_Unit = null;
  private $Spatialcoverage_Minimumaltitude = null;
  private $Spatialcoverage_Maximumaltitude = null;
  private $Spatialcoverage_Reference = null;

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  /*
  public function addSomething(Repository_Oaipmh_SPASE_Catalog $val){
    array_push($this->val, $val);
  }
  */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_Role){
    array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
  }

  public function addAccessinformation_Repositoryid(Repository_Oaipmh_SPASE_LangString $Accessinformation_Repositoryid){
    if($this->Accessinformation_Repositoryid == null){
        $this->Accessinformation_Repositoryid = $Accessinformation_Repositoryid;
    }
  }

  public function addAccessinformation_Availability(Repository_Oaipmh_SPASE_LangString $Accessinformation_Availability){
    if($this->Accessinformation_Availability == null){
        $this->Accessinformation_Availability = $Accessinformation_Availability;
    }
  }

  public function addAccessinformation_Accessrights(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessrights){
    if($this->Accessinformation_Accessrights == null){
        $this->Accessinformation_Accessrights = $Accessinformation_Accessrights;
    }
  }

  public function addAccessinformation_Accessurl_Name(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Name){
    if($this->Accessinformation_Accessurl_Name == null){
        $this->Accessinformation_Accessurl_Name = $Accessinformation_Accessurl_Name;
    }
  }

  public function addAccessinformation_Accessurl_URL(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_URL){
    if($this->Accessinformation_Accessurl_URL == null){
        $this->Accessinformation_Accessurl_URL = $Accessinformation_Accessurl_URL;
    }
  }

  public function addAccessinformation_Accessurl_Description(Repository_Oaipmh_SPASE_LangString $Accessinformation_Accessurl_Description){
    if($this->Accessinformation_Accessurl_Description == null){
        $this->Accessinformation_Accessurl_Description = $Accessinformation_Accessurl_Description;
    }
  }

  public function addAccessinformation_Format(Repository_Oaipmh_SPASE_LangString $Accessinformation_Format){
    if($this->Accessinformation_Format == null){
        $this->Accessinformation_Format = $Accessinformation_Format;
    }
  }

  public function addAccessinformation_Dataextent_Quantity(Repository_Oaipmh_SPASE_LangString $Accessinformation_Dataextent_Quantity){
    if($this->Accessinformation_Dataextent_Quantity == null){
        $this->Accessinformation_Dataextent_Quantity = $Accessinformation_Dataextent_Quantity;
    }
  }

  public function addTemporaldescription_Startdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Startdate){
    if($this->Temporaldescription_Startdate == null){
        $this->Temporaldescription_Startdate = $Temporaldescription_Startdate;
    }
  }

  public function addTemporaldescription_Stopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Stopdate){
    if($this->Temporaldescription_Stopdate == null){
        $this->Temporaldescription_Stopdate = $Temporaldescription_Stopdate;
    }
  }

  public function addTemporaldescription_Relativestopdate(Repository_Oaipmh_SPASE_LangString $Temporaldescription_Relativestopdate){
    if($this->Temporaldescription_Relativestopdate == null){
        $this->Temporaldescription_Relativestopdate = $Temporaldescription_Relativestopdate;
    }
  }

  public function addParameter_Name(Repository_Oaipmh_SPASE_LangString $Parameter_Name){
    if($this->Parameter_Name == null){
        $this->Parameter_Name = $Parameter_Name;
    }
  }

  public function addParameter_Description(Repository_Oaipmh_SPASE_LangString $Parameter_Description){
    if($this->Parameter_Description == null){
        $this->Parameter_Description = $Parameter_Description;
    }
  }

  public function addParameter_Field_Fieldquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Field_Fieldquantity){
    if($this->Parameter_Field_Fieldquantity == null){
        $this->Parameter_Field_Fieldquantity = $Parameter_Field_Fieldquantity;
    }
  }

  public function addParameter_Particle_Particletype(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particletype){
    if($this->Parameter_Particle_Particletype == null){
        $this->Parameter_Particle_Particletype = $Parameter_Particle_Particletype;
    }
  }

  public function addParameter_Particle_Particlequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Particle_Particlequantity){
    if($this->Parameter_Particle_Particlequantity == null){
        $this->Parameter_Particle_Particlequantity = $Parameter_Particle_Particlequantity;
    }
  }


  public function addParameter_Wave_Wavetype(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavetype){
    if($this->Parameter_Wave_Wavetype == null){
        $this->Parameter_Wave_Wavetype = $Parameter_Wave_Wavetype;
    }
  }

  public function addParameter_Wave_Wavequantity(Repository_Oaipmh_SPASE_LangString $Parameter_Wave_Wavequantity){
    if($this->Parameter_Wave_Wavequantity == null){
        $this->Parameter_Wave_Wavequantity = $Parameter_Wave_Wavequantity;
    }
  }

  public function addParameter_Mixed_Mixedquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Mixed_Mixedquantity){
    if($this->Parameter_Mixed_Mixedquantity == null){
        $this->Parameter_Mixed_Mixedquantity = $Parameter_Mixed_Mixedquantity;
    }
  }

  public function addParameter_Support_Supportquantity(Repository_Oaipmh_SPASE_LangString $Parameter_Support_Supportquantity){
    if($this->Parameter_Support_Supportquantity == null){
        $this->Parameter_Support_Supportquantity = $Parameter_Support_Supportquantity;
    }
  }

  public function addPhenomenontype(Repository_Oaipmh_SPASE_LangString $Phenomenontype){
    if($this->Phenomenontype == null){
        $this->Phenomenontype = $Phenomenontype;
    }
  }

  public function addMeasurementtype(Repository_Oaipmh_SPASE_LangString $Measurementtype){
    if($this->Measurementtype == null){
        $this->Measurementtype = $Measurementtype;
    }
  }

  public function addObservedregion(Repository_Oaipmh_SPASE_LangString $Observedregion){
    if($this->Observedregion == null){
        $this->Observedregion = $Observedregion;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinatesystemname(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinatesystemname){
    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname = $Spatialcoverage_Coordinatesystem_Coordinatesystemname;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinaterepresentation(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinaterepresentation){
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation = $Spatialcoverage_Coordinatesystem_Coordinaterepresentation;
    }
  }


  public function addSpatialcoverage_Northernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Northernmostlatitude){
    if($this->Spatialcoverage_Northernmostlatitude == null){
        $this->Spatialcoverage_Northernmostlatitude = $Spatialcoverage_Northernmostlatitude;
    }
  }

  public function addSpatialcoverage_Southernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Southernmostlatitude){
    if($this->Spatialcoverage_Southernmostlatitude == null){
        $this->Spatialcoverage_Southernmostlatitude = $Spatialcoverage_Southernmostlatitude;
    }
  }

  public function addSpatialcoverage_Easternmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Easternmostlongitude){
    if($this->Spatialcoverage_Easternmostlongitude == null){
        $this->Spatialcoverage_Easternmostlongitude = $Spatialcoverage_Easternmostlongitude;
    }
  }


  public function addSpatialcoverage_Westernmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Westernmostlongitude){
    if($this->Spatialcoverage_Westernmostlongitude == null){
        $this->Spatialcoverage_Westernmostlongitude = $Spatialcoverage_Westernmostlongitude;
    }
  }

  public function addSpatialcoverage_Unit(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Unit){
    if($this->Spatialcoverage_Unit == null){
        $this->Spatialcoverage_Unit = $Spatialcoverage_Unit;
    }
  }

  public function addSpatialcoverage_Minimumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Minimumaltitude){
    if($this->Spatialcoverage_Minimumaltitude == null){
        $this->Spatialcoverage_Minimumaltitude = $Spatialcoverage_Minimumaltitude;
    }
  }


  public function addSpatialcoverage_Maximumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Maximumaltitude){
    if($this->Spatialcoverage_Maximumaltitude == null){
        $this->Spatialcoverage_Maximumaltitude = $Spatialcoverage_Maximumaltitude;
    }
  }

  public function addSpatialcoverage_Reference(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Reference){
    if($this->Spatialcoverage_Reference == null){
        $this->Spatialcoverage_Reference = $Spatialcoverage_Reference;
    }
  }
  
  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){
    $xmlStr = '';

    
    
    if($this->ResourceID != null)
    {
      $xmlStr .= '<'.RepositoryConst::SPASE_NUMERICALDATA.'>';
        $xml = $this->ResourceID->output();
        if(strlen($xml)>0){
            $xmlStr .= '<'.RepositoryConst::SPASE_ND_RESOURCEID.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_ND_RESOURCEID.'>'."\n";
        }
    }
  
    
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
            /*
            $xmlStr .= '<'.RepositoryConst::SPASE_ND_RESOURCEHEADER_RESOURCENAME.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_ND_RESOURCEHEADER_RESOURCENAME.'>'."\n";
            */
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

    if($this->Resourceheader_Resourcename != null)
    {
    $xmlStr .= '</ResourceHeader>'."\n";
    }
   
    
    if($this->Accessinformation_Repositoryid != null)
    {
       $xmlStr .= '<AccessInformation>'."\n";
        $xml = $this->Accessinformation_Repositoryid->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_REPOSITORYID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Availability != null)
    {
        $xml = $this->Accessinformation_Availability->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_AVAILABILITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessrights != null)
    {
        $xml = $this->Accessinformation_Accessrights->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_ACCESSRIGHTS);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  

    if($this->Accessinformation_Accessurl_Name != null || $this->Accessinformation_Accessurl_URL != null ||
      $this->Accessinformation_Accessurl_Description != null){
      $xmlStr .= '<AccessURL>'."\n";

    if($this->Accessinformation_Accessurl_Name != null)
    {
        $xml = $this->Accessinformation_Accessurl_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_ACCESSURL_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Accessinformation_Accessurl_URL != null)
    {
        $xml = $this->Accessinformation_Accessurl_URL->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_ACCESSURL_URL);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Accessinformation_Accessurl_Description != null)
    {
        $xml = $this->Accessinformation_Accessurl_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_ACCESSURL_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    $xmlStr .= '</AccessURL>'."\n";
    }
  
      if($this->Accessinformation_Format != null)
    {
        $xml = $this->Accessinformation_Format->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_FORMAT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Accessinformation_Dataextent_Quantity != null)
    { 
      $xmlStr .= '<DataExtent>'."\n";
        $xml = $this->Accessinformation_Dataextent_Quantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_ACCESSINFORMATION_DATAEXTENT_QUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</DataExtent>'."\n";
    }

    if($this->Accessinformation_Repositoryid != null)
    {
    $xmlStr .= '</AccessInformation>'."\n";
    }
    

    if($this->Temporaldescription_Startdate != NULL || $this->Temporaldescription_Stopdate != NULL 
      || $this->Temporaldescription_Relativestopdate != NULL){
      $xmlStr .= '<Temporaldescription>'."\n";
  
    if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_TEMPORALDESCRIPTION_STARTDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_TEMPORALDESCRIPTION_STOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_TEMPORALDESCRIPTION_RELATIVESTOPDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Temporaldescription>'."\n";
    }
  

    

    if($this->Parameter_Name != null)
    {
      $xmlStr .= '<Parameter>'."\n";
        $xml = $this->Parameter_Name->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_NAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Description != null)
    {
        $xml = $this->Parameter_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Parameter_Field_Fieldquantity != null)
    {
      $xmlStr .= '<Field>'."\n";
        $xml = $this->Parameter_Field_Fieldquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_FIELD_FIELDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Field>'."\n";
    }
  

    if($this->Parameter_Particle_Particletype != NULL || $this->Parameter_Particle_Particlequantity != NULL){
      $xmlStr .= '<Particle>'."\n";
    if($this->Parameter_Particle_Particletype != null)
    {
        $xml = $this->Parameter_Particle_Particletype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_PARTICLE_PARTICLETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Particle_Particlequantity != null)
    {
        $xml = $this->Parameter_Particle_Particlequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_PARTICLE_PARTICLEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Particle>'."\n";
    }

  
    if($this->Parameter_Wave_Wavetype != NULL || $this->Parameter_Wave_Wavequantity != NULL){
      $xmlStr .= '<Wave>'."\n";
    

    if($this->Parameter_Wave_Wavetype != null)
    {
        $xml = $this->Parameter_Wave_Wavetype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_WAVE_WAVETYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Parameter_Wave_Wavequantity != null)
    {
        $xml = $this->Parameter_Wave_Wavequantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_WAVE_WAVEQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</Wave>'."\n";
    }
  
    if($this->Parameter_Mixed_Mixedquantity != null)
    {
      $xmlStr .= '<Mixed>'."\n";
        $xml = $this->Parameter_Mixed_Mixedquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_MIXED_MIXEDQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      $xmlStr .= '</Mixed>'."\n";
    }
  
    if($this->Parameter_Support_Supportquantity != null)
    {
      $xmlStr .= '<Support>'."\n";
        $xml = $this->Parameter_Support_Supportquantity->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PARAMETER_SUPPORT_SUPPORTQUANTITY);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
        $xmlStr .= '</Support>'."\n";
  
    }

    if($this->Parameter_Name != null)
    {
    $xmlStr .= '</Parameter>'."\n";
    }

    
    if($this->Phenomenontype != null)
    {
        $xml = $this->Phenomenontype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_PHENOMENONTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Measurementtype != null)
    {
        $xml = $this->Measurementtype->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Startdate != null || $this->Temporaldescription_Stopdate != null 
      ||$this->Temporaldescription_Relativestopdate != null){

      $xmlStr .= '<Temporaldescription>'."\n";
      if($this->Temporaldescription_Startdate != null)
    {
        $xml = $this->Temporaldescription_Startdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Stopdate != null)
    {
        $xml = $this->Temporaldescription_Stopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Temporaldescription_Relativestopdate != null)
    {
        $xml = $this->Temporaldescription_Relativestopdate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_MEASUREMENTTYPE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
    $xmlStr .= '</Temporaldescription>'."\n";
    }
  
    if($this->Observedregion != null)
    {
        $xml = $this->Observedregion->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_OBSERVEDREGION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){

      $xmlStr .= '<SpatialCoverage>'."\n";
    $xmlStr .= '<CoordinateSystem>'."\n";
      if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</CoordinateSystem>'."\n";
    }
  
    if($this->Spatialcoverage_Northernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Northernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Southernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Southernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Easternmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Easternmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Westernmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Westernmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Unit != null)
    {
        $xml = $this->Spatialcoverage_Unit->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_UNIT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Minimumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Minimumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_MINIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Maximumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Maximumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_MAXIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Reference != null)
    {
        $xml = $this->Spatialcoverage_Reference->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_SPATIALCOVERAGE_REFERENCE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){
    $xmlStr .= '</SpatialCoverage>'."\n";
    }

    if($this->ResourceID != null)
    {
    $xmlStr .= '</'.RepositoryConst::SPASE_NUMERICALDATA.'>';
    }

    return $xmlStr;
  }
}


class Repository_Oaipmh_SPASE_Instrument
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Instrumenttype = array();
  private $Investigationname = array();
  private $ObsevatoryID = null;

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getValue());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
  }

  public function addInstrumenttype(Repository_Oaipmh_SPASE_Array $Instrumenttype){
    $tmp = RepositoryOutputFilterSPASE::retValue($Instrumenttype->getValue());
    if(strlen($tmp)>0){
      array_push($this->Instrumenttype, $Instrumenttype);
    }
  }

  public function addInvestigationname(Repository_Oaipmh_SPASE_Array $Investigationname){
    $tmp = RepositoryOutputFilterSPASE::retValue($Investigationname->getValue());
    if(strlen($tmp)>0){
      array_push($this->Investigationname, $Investigationname);
    }
  }

  public function addObsevatoryID(Repository_Oaipmh_SPASE_LangString $ObsevatoryID){
    if($this->ObsevatoryID == null){
        $this->ObsevatoryID = $ObsevatoryID;
    }
  }


  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){

  $xmlStr = '';

  if($this->ResourceID != null)
    {
        $xmlStr .= '<'.RepositoryConst::SPASE_INSTRUMENT.'>';
        $xml = $this->ResourceID->output();
        if(strlen($xml)>0){
            $xmlStr .= '<'.RepositoryConst::SPASE_I_RESOURCEID.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_I_RESOURCEID.'>'."\n";
        }
    }
    
    
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
            /*
            $xmlStr .= '<'.RepositoryConst::SPASE_I_RESOURCEHEADER_RESOURCENAME.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_I_RESOURCEHEADER_RESOURCENAME.'>'."\n";
            */
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_I_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '</ResourceHeader>'."\n";
    }


  if($this->Instrumenttype != null){
  $xmlStr .= '<Instrumenttype>'."\n";

  for($ii=0;$ii<count($this->Instrumenttype);$ii++){
    $value = explode(".",RepositoryConst::SPASE_I_INSTRUMENTTYPE);
    $xmlStr .= '<'.$value[count($value)-1].'>';
    $xmlStr .= $xml;
    $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
  }
  $xmlStr .= '</Instrumenttype>'."\n";
  }

  if($this->Investigationname != null){
  $xmlStr .= '<Investigationname>'."\n";
  for($ii=0;$ii<count($this->Investigationname);$ii++){
    $value = explode(".",RepositoryConst::SPASE_I_INVESTIGATIONNAME);
    $xmlStr .= '<'.$value[count($value)-1].'>';
    $xmlStr .= $xml;
    $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
  }
  $xmlStr .= '</Investigationname>'."\n";
  }

  if($this->ObsevatoryID != null)
  {
    $xmlStr .= '<ObsevatoryID>'."\n";
      $xml = $this->ObsevatoryID->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_I_OBSEVATORYID);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
    $xmlStr .= '</ObsevatoryID>'."\n";

  }

  if($this->ResourceID != null)
    {
    $xmlStr .= '</'.RepositoryConst::SPASE_INSTRUMENT.'>';
  }
    return $xmlStr;
  }
}


class Repository_Oaipmh_SPASE_Observatory
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Location_Observatoryregion = array();
  private $Location_CoordinateSystemName_Latitude = null;
  private $Location_CoordinateSystemName_Longitude = null;
  private $OperatingSpan_StartDate = null;

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  /*
  public function addSomething(Repository_Oaipmh_SPASE_Catalog $val){
    array_push($this->val, $val);
  }
  */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getValue());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }  
  }


  public function addLocation_Observatoryregion(Repository_Oaipmh_SPASE_Array $Location_Observatoryregion){
    $tmp = RepositoryOutputFilterSPASE::retValue($Location_Observatoryregion->getValue());
    if(strlen($tmp)>0){
      array_push($this->Location_Observatoryregion, $Location_Observatoryregion);
    }    
  }

  public function addLocation_CoordinateSystemName_Latitude(Repository_Oaipmh_SPASE_LangString $Location_CoordinateSystemName_Latitude){
    if($this->Location_CoordinateSystemName_Latitude == null){
        $this->Location_CoordinateSystemName_Latitude = $Location_CoordinateSystemName_Latitude;
    }
  }

  public function addLocation_CoordinateSystemName_Longitude(Repository_Oaipmh_SPASE_LangString $Location_CoordinateSystemName_Longitude){
    if($this->Location_CoordinateSystemName_Longitude == null){
        $this->Location_CoordinateSystemName_Longitude = $Location_CoordinateSystemName_Longitude;
    }
  }

  public function addOperatingSpan_StartDate(Repository_Oaipmh_SPASE_LangString $OperatingSpan_StartDate){
    if($this->OperatingSpan_StartDate == null){
        $this->OperatingSpan_StartDate = $OperatingSpan_StartDate;
    }
  }

  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){
    $xmlStr = '';

  if($this->ResourceID != null)
    {
        $xmlStr .= '<'.RepositoryConst::SPASE_OBSERVATORY.'>';
        $xml = $this->ResourceID->output();
        if(strlen($xml)>0){
            $xmlStr .= '<'.RepositoryConst::SPASE_O_RESOURCEID.'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.RepositoryConst::SPASE_O_RESOURCEID.'>'."\n";
        }
    }
  
    
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_O_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

  if($this->Resourceheader_Resourcename != null)
  {
  $xmlStr .= '</ResourceHeader>'."\n";
  }


  if($this->Location_Observatoryregion != null || $this->Location_CoordinateSystemName_Latitude != null 
    || $this->Location_CoordinateSystemName_Longitude != null){
  $xmlStr .= '<Location>'."\n";

  if($this->Location_Observatoryregion != null){
  for($ii=0;$ii<count($this->Location_Observatoryregion);$ii++){
    $value = explode(".",RepositoryConst::SPASE_O_LOCATION_OBSERVATORYREGION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
    }
  }
  
  $xmlStr .= '<CoordinateSystemName>'."\n";
  if($this->Location_CoordinateSystemName_Latitude != null)
  {
      $xml = $this->Location_CoordinateSystemName_Latitude->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_O_LOCATION_COORDINATESYSTEMNAME_LATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->Location_CoordinateSystemName_Longitude != null)
  {
      $xml = $this->Location_CoordinateSystemName_Longitude->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_O_LOCATION_COORDINATESYSTEMNAME_LONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }
  $xmlStr .= '</CoordinateSystemName>'."\n";
  
  $xmlStr .= '</Location>'."\n";
  }



  if($this->OperatingSpan_StartDate != null)
  {
    $xmlStr .= '<OperatingSpan>'."\n";
    //$xmlStr .= '<StartDate>'."\n";
      $xml = $this->OperatingSpan_StartDate->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_O_OPERATINGSPAN_STARTDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
    $xmlStr .= '</OperatingSpan>'."\n";
    //$xmlStr .= '</StartDate>'."\n";
  }

  if($this->ResourceID != null)
    {
    $xmlStr .= '</'.RepositoryConst::SPASE_OBSERVATORY.'>';
    }

    return $xmlStr;
  }
}


class Repository_Oaipmh_SPASE_Person
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Releasedate = null;
  private $Personname = null;
  private $Organizationname = null;
  private $Email = array();

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addReleasedate(Repository_Oaipmh_SPASE_LangString $Releasedate){
    if($this->Releasedate == null){
        $this->Releasedate = $Releasedate;
    }
  }

  public function addPersonname(Repository_Oaipmh_SPASE_LangString $Personname){
    if($this->Personname == null){
        $this->Personname = $Personname;
    }
  }

  public function addOrganizationname(Repository_Oaipmh_SPASE_LangString $Organizationname){
    if($this->Organizationname == null){
        $this->Organizationname = $Organizationname;
    }
  }

  public function addEmail(Repository_Oaipmh_SPASE_Array $Email){
    $tmp = RepositoryOutputFilterSPASE::retValue($Email->getValue());
    if(strlen($tmp)>0){
      array_push($this->Email, $Email);
    }
  }

  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){
    $xmlStr = '';

    

  if($this->ResourceID != null)
  {
    $xmlStr .= '<'.RepositoryConst::SPASE_PERSON.'>';
      $xml = $this->ResourceID->output();
      if(strlen($xml)>0){
          $xmlStr .= '<'.RepositoryConst::SPASE_P_RESOURCEID.'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.RepositoryConst::SPASE_P_RESOURCEID.'>'."\n";
      }
  }


  if($this->Releasedate != null)
  {
        $xml = $this->Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_P_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
  }

  if($this->Personname != null)
  {
        $xml = $this->Personname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_P_PERSONNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
  }

  
  if($this->Organizationname != null)
  {
        $xml = $this->Organizationname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_P_ORGANIZATIONNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
  }

  //print_r($this->Email); // ... 何も入ってない
  for($ii=0;$ii<count($this->Email);$ii++){
    $value = explode(".",RepositoryConst::SPASE_P_EMAIL);
    $xmlStr .= '<'.$value[count($value)-1].'>';
    $xmlStr .= $xml;
    $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
  }

  if($this->ResourceID != null)
  {
    $xmlStr .= '</'. RepositoryConst::SPASE_PERSON.'>';
  }
    return $xmlStr;
  }
}


class Repository_Oaipmh_SPASE_Repository
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $Resourceheader_Resourcename = null;
  private $Resourceheader_Releasedate = null;
  private $Resourceheader_Description = null;
  private $Resourceheader_Acknowledgement = null;
  private $Resourceheader_Contact_PersonID = null;
  private $Resourceheader_Contact_Role = array();

  private $Accessurl_url = null;

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  /*
  public function addSomething(Repository_Oaipmh_SPASE_Catalog $val){
    array_push($this->val, $val);
  }
  */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addResourceheader_Resourcename(Repository_Oaipmh_SPASE_LangString $Resourceheader_Resourcename){
    if($this->Resourceheader_Resourcename == null){
        $this->Resourceheader_Resourcename = $Resourceheader_Resourcename;
    }
  }

  public function addResourceheader_Releasedate(Repository_Oaipmh_SPASE_LangString $Resourceheader_Releasedate){
    if($this->Resourceheader_Releasedate == null){
        $this->Resourceheader_Releasedate = $Resourceheader_Releasedate;
    }
  }

  public function addResourceheader_Description(Repository_Oaipmh_SPASE_LangString $Resourceheader_Description){
    if($this->Resourceheader_Description == null){
        $this->Resourceheader_Description = $Resourceheader_Description;
    }
  }

  public function addResourceheader_Acknowledgement(Repository_Oaipmh_SPASE_LangString $Resourceheader_Acknowledgement){
    if($this->Resourceheader_Acknowledgement == null){
        $this->Resourceheader_Acknowledgement = $Resourceheader_Acknowledgement;
    }
  }

  public function addResourceheader_Contact_PersonID(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_PersonID){
    if($this->Resourceheader_Contact_PersonID == null){
        $this->Resourceheader_Contact_PersonID = $Resourceheader_Contact_PersonID;
    }
  }

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_LangString $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getValue());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
  }

  public function addAccessurl_url(Repository_Oaipmh_SPASE_Identifier $Accessurl_url){
    if($this->Accessurl_url == null){
        $this->Accessurl_url = $Accessurl_url;
    }
  }

  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }

  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }


  public function output(){
  $xmlStr = '';

  if($this->ResourceID != null)
  {
    $xmlStr .= '<'.RepositoryConst::SPASE_REPOSITORY.'>';
      $xml = $this->ResourceID->output();
      if(strlen($xml)>0){
          $xmlStr .= '<'.RepositoryConst::SPASE_R_RESOURCEID.'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.RepositoryConst::SPASE_R_RESOURCEID.'>'."\n";
      }
  }

  
    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '<ResourceHeader>'."\n";
      $xml = $this->Resourceheader_Resourcename->output();
        if(strlen($xml)>0){
            //$value[count($value)-1] = 末尾の文字
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_RESOURCENAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Releasedate != null)
    {
        $xml = $this->Resourceheader_Releasedate->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_RELEASEDATE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Description != null)
    {
        $xml = $this->Resourceheader_Description->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_DESCRIPTION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Acknowledgement != null)
    {
        $xml = $this->Resourceheader_Acknowledgement->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_ACKNOWLEDGEMENT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Resourceheader_Contact_PersonID != null || $this->Resourceheader_Contact_Role != null){
    $xmlStr .= '<Contact>'."\n";
    if($this->Resourceheader_Contact_PersonID != null)
    {
        $xml = $this->Resourceheader_Contact_PersonID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_CONTACT_PERSONID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
      }
    $xmlStr .= '</Contact>'."\n";
    }

    if($this->Resourceheader_Resourcename != null)
    {
      $xmlStr .= '</ResourceHeader>'."\n";
    }

  if($this->Accessurl_url != null)
  {
      $xml = $this->Accessurl_url->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_R_ACCESSURL_URL);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }
  if($this->ResourceID != null)
  {
  $xmlStr .= '</'.RepositoryConst::SPASE_REPOSITORY.'>';
  }

  return $xmlStr;
  }
}

class Repository_Oaipmh_SPASE_Granule
{
    //for spase
  private $metadataSchema = array();
  private $language = null;

  private $ResourceID = null;
  private $ReleaseDate = null;
  private $ParentID = null;
  private $StartDate = null;
  private $StopDate = null;
  private $Source_SourceType = null;
  private $Source_URL = null;
  private $Souce_Dataextent_Quantity = null;

  private $Spatialcoverage_Coordinatesystem_Coordinatesystemname = null;
  private $Spatialcoverage_Coordinatesystem_Coordinaterepresentation = null;
  private $Spatialcoverage_Northernmostlatitude = null;
  private $Spatialcoverage_Southernmostlatitude = null;
  private $Spatialcoverage_Easternmostlongitude = null;
  private $Spatialcoverage_Westernmostlongitude = null;
  private $Spatialcoverage_Unit = null;
  private $Spatialcoverage_Minimumaltitude = null;
  private $Spatialcoverage_Maximumaltitude = null;
  private $Spatialcoverage_Reference = null;

  private $repositoryAction = null;

  /*
   * コンストラクタ
   */
  public function __construct($repositoryAction){
    $this->repositoryAction = $repositoryAction;
  }

/*   SPASE   */
  
  /*
  public function addSomething(Repository_Oaipmh_SPASE_Catalog $val){
    array_push($this->val, $val);
  }
  */
  
  public function addResourceID(Repository_Oaipmh_SPASE_LangString $ResourceID){
    if($this->ResourceID == null){
        $this->ResourceID = $ResourceID;
    }
  }

  public function addReleaseDate(Repository_Oaipmh_SPASE_LangString $ReleaseDate){
    if($this->ReleaseDate == null){
        $this->ReleaseDate = $ReleaseDate;
    }
  }

  public function addParentID(Repository_Oaipmh_SPASE_LangString $ParentID){
    if($this->ParentID == null){
        $this->ParentID = $ParentID;
    }
  }

  public function addStartDate(Repository_Oaipmh_SPASE_LangString $StartDate){
    if($this->StartDate == null){
        $this->StartDate = $StartDate;
    }
  }

  public function addStopDate(Repository_Oaipmh_SPASE_LangString $StopDate){
    if($this->StopDate == null){
        $this->StopDate = $StopDate;
    }
  }

  public function addSource_SourceType(Repository_Oaipmh_SPASE_LangString $Source_SourceType){
    if($this->Source_SourceType == null){
        $this->Source_SourceType = $Source_SourceType;
    }
  }

  public function addSource_URL(Repository_Oaipmh_SPASE_LangString $Source_URL){
    if($this->Source_URL == null){
        $this->Source_URL = $Source_URL;
    }
  }

  public function addSouce_Dataextent_Quantity(Repository_Oaipmh_SPASE_LangString $Souce_Dataextent_Quantity){
    if($this->Souce_Dataextent_Quantity == null){
        $this->Souce_Dataextent_Quantity = $Souce_Dataextent_Quantity;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinatesystemname(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinatesystemname){
    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname = $Spatialcoverage_Coordinatesystem_Coordinatesystemname;
    }
  }

  public function addSpatialcoverage_Coordinatesystem_Coordinaterepresentation(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Coordinatesystem_Coordinaterepresentation){
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation == null){
        $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation = $Spatialcoverage_Coordinatesystem_Coordinaterepresentation;
    }
  }


  public function addSpatialcoverage_Northernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Northernmostlatitude){
    if($this->Spatialcoverage_Northernmostlatitude == null){
        $this->Spatialcoverage_Northernmostlatitude = $Spatialcoverage_Northernmostlatitude;
    }
  }

  public function addSpatialcoverage_Southernmostlatitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Southernmostlatitude){
    if($this->Spatialcoverage_Southernmostlatitude == null){
        $this->Spatialcoverage_Southernmostlatitude = $Spatialcoverage_Southernmostlatitude;
    }
  }

  public function addSpatialcoverage_Easternmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Easternmostlongitude){
    if($this->Spatialcoverage_Easternmostlongitude == null){
        $this->Spatialcoverage_Easternmostlongitude = $Spatialcoverage_Easternmostlongitude;
    }
  }


  public function addSpatialcoverage_Westernmostlongitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Westernmostlongitude){
    if($this->Spatialcoverage_Westernmostlongitude == null){
        $this->Spatialcoverage_Westernmostlongitude = $Spatialcoverage_Westernmostlongitude;
    }
  }

  public function addSpatialcoverage_Unit(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Unit){
    if($this->Spatialcoverage_Unit == null){
        $this->Spatialcoverage_Unit = $Spatialcoverage_Unit;
    }
  }

  public function addSpatialcoverage_Minimumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Minimumaltitude){
    if($this->Spatialcoverage_Minimumaltitude == null){
        $this->Spatialcoverage_Minimumaltitude = $Spatialcoverage_Minimumaltitude;
    }
  }


  public function addSpatialcoverage_Maximumaltitude(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Maximumaltitude){
    if($this->Spatialcoverage_Maximumaltitude == null){
        $this->Spatialcoverage_Maximumaltitude = $Spatialcoverage_Maximumaltitude;
    }
  }

  public function addSpatialcoverage_Reference(Repository_Oaipmh_SPASE_LangString $Spatialcoverage_Reference){
    if($this->Spatialcoverage_Reference == null){
        $this->Spatialcoverage_Reference = $Spatialcoverage_Reference;
    }
  }


  //add metadata schema and language
  public function addMetadataSchema($metadataSchema){
    $metadataSchema = $this->repositoryAction->forXmlChange($metadataSchema);
    if(strlen($metadataSchema)>0){
      array_push($this->metadataSchema, $metadataSchema);
    }
  }
  public function addLanguage($language){
    //encording
    $language = $this->repositoryAction->forXmlChange($language);
    $language = RepositoryOutputFilter::language($language);
     
    if($this->language == null && strlen($language)>0){
      $this->language = $language;
    }
  }

  public function output(){
  $xmlStr = '';

  if($this->ResourceID != null)
  {
    $xmlStr .= '<'.RepositoryConst::SPASE_GRANULE.'>';
      $xml = $this->ResourceID->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_RESOURCEID);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->ReleaseDate != null)
  {
      $xml = $this->ReleaseDate->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_RELEASEDATE);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->ParentID != null)
  {
      $xml = $this->ParentID->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_PARENTID);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->StartDate != null)
  {
      $xml = $this->StartDate->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_STARTDATE);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->StopDate != null)
  {
      $xml = $this->StopDate->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_STOPDATE);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->Source_SourceType != null || $this->Source_URL != null || $this->Souce_Dataextent_Quantity != null){
  $xmlStr .= '<Source>'."\n";
  if($this->Source_SourceType != null)
  {
      $xml = $this->Source_SourceType->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_SOURCE_SOURCETYPE);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->Source_URL != null)
  {
      $xml = $this->Source_URL->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_G_SOURCE_URL);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  if($this->Souce_Dataextent_Quantity != null)
  {
      $xml = $this->Souce_Dataextent_Quantity->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_R_RESOURCEHEADER_CONTACT_ROLE);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
  }

  $xmlStr .= '</Source>'."\n";
  }



  

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){
      $xmlStr .= '<SpatialCoverage>'."\n";
    $xmlStr .= '<CoordinateSystem>'."\n";
      if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinatesystemname->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null)
    {
        $xml = $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
      $xmlStr .= '</CoordinateSystem>'."\n";
    }
  
    if($this->Spatialcoverage_Northernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Northernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Southernmostlatitude != null)
    {
        $xml = $this->Spatialcoverage_Southernmostlatitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Easternmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Easternmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Westernmostlongitude != null)
    {
        $xml = $this->Spatialcoverage_Westernmostlongitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_WESTERNMOSTLONGITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Unit != null)
    {
        $xml = $this->Spatialcoverage_Unit->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_UNIT);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Minimumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Minimumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_MINIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
      if($this->Spatialcoverage_Maximumaltitude != null)
    {
        $xml = $this->Spatialcoverage_Maximumaltitude->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_MAXIMUMALTITUDE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }
  
    if($this->Spatialcoverage_Reference != null)
    {
        $xml = $this->Spatialcoverage_Reference->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_G_SPATIALCOVERAGE_REFERENCE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
    }

    if($this->Spatialcoverage_Coordinatesystem_Coordinatesystemname != null || 
      $this->Spatialcoverage_Coordinatesystem_Coordinaterepresentation != null){
    $xmlStr .= '</SpatialCoverage>'."\n";
    }

    if($this->ResourceID != null)
  {
  $xmlStr .=  '</'.RepositoryConst::SPASE_GRANULE.'>';
  }
  return $xmlStr;
}
}

class Repository_Oaipmh_LOM_General
{
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


class Repository_Oaipmh_SPASE_LangString
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

    public function getValue(){
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
            if($this->language === 'other')
            {
                $this->language = '';
            }
            // set language
            if(strlen($this->language) == 0)
            {
                //$xmlStr .= '<'.RepositoryConst::LOM_TAG_STRING.'>'."\n";
            }
            else
            {
                //$xmlStr .= '<'.RepositoryConst::LOM_TAG_STRING.'>'."\n";
                //$xmlStr .= '<'.RepositoryConst::LOM_TAG_STRING.' '.RepositoryConst::LOM_TAG_LANGUAGE.'="'.$this->language.'">'."\n";
            }
            //$xmlStr .= $this->string.'</'.RepositoryConst::LOM_TAG_STRING.'>'."\n";
            $xmlStr .= $this->string . "\n";
        }
        return $xmlStr;
    }
}

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
            if($this->language === 'other')
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

class Repository_Oaipmh_SPASE_Vocabulary
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
            //$xmlStr .= '<'.RepositoryConst::LOM_TAG_SOURCE.'>'.$this->source.'</'.RepositoryConst::LOM_TAG_SOURCE.'>'."\n";
        }
        if(strlen($this->value)>0){
            //$xmlStr .= '<'.RepositoryConst::LOM_TAG_VALUE.'>'.$this->value.'</'.RepositoryConst::LOM_TAG_VALUE.'>'."\n";
        }

        $xmlStr .= $this->value . "\n";

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

class Repository_Oaipmh_SPASE_Identifier
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

class Repository_Oaipmh_SPASE_Array{
    /*
     * メンバ変数
     */
    private $source = null;
    private $ar = array();
    //private $language = null;
    private $repositoryAction = null;

    /*
     * コンストラクタ
     * @param string $source [single]
     * @param Repository_Oaipmh_LOM_Taxon $taxon [pararel]
     */
    public function __construct($repositoryAction)
    {
        $this->repositoryAction = $repositoryAction;
        //$this->addArray($val);

        //array_push($this->ar, $val);
        //print_r($this->ar);
    }
    /*
     * addSource
     */
    public function addSource(Repository_Oaipmh_SPASE_LangString $source){
        if($this->source == null){
             $this->source = $source;
        }
    }
    /*
     * addTaxon
     */
    
    public function addArray(Repository_Oaipmh_SPASE_LangString $val){
        array_push($this->ar, $val);
    }
    /*
    public function addArray(Repository_Oaipmh_SPASE_Array $ar){
        array_push($this->ar, $ar);
    }
    */

    //getter

    public function getSource(){
        if($this->source == null){
            return '';
        }
        return $this->source->getString();
    }

    public function getValue(){
       if($this->source == null){
            return '';
        }
        return $this->source->getString();
    }

    public function getArrayCount(){
        if($this->ar == null){
            return 0;
        }
        return count($this->ar);
    }


    public function output(){
        $xmlStr = '';

        //source
        if($this->source != null){
            //$xmlStr .= '<'.RepositoryConst::LOM_TAG_SOURCE.'>';
            $xmlStr .= $this->source->output();
            //$xmlStr .= '</'.RepositoryConst::LOM_TAG_SOURCE.'>';
        }
        //ar
        //print_r($ar);
        for($ii=0;$ii<count($this->ar);$ii++){
            $xmlStr .= $this->ar[$ii]->output();
        }

        if(strlen($xmlStr)>0){
            //$xmlStr = '<'.RepositoryConst::LOM_TAG_TAXON_PATH.'>'.$xmlStr.'</'.RepositoryConst::LOM_TAG_TAXON_PATH.'>'."\n";
        }

        $xmlStr .= $this->source . "\n";


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