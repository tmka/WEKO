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
        $this->setBaseData($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]);

        //2. マッピング情報設定処理
        $this->setMappingInfo($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]);

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

        //URI
        $uri = new Repository_Oaipmh_SPASE_Identifier($this->RepositoryAction,
                                                    $itemData[RepositoryConst::DBCOL_REPOSITORY_ITEM_URI],
                                                    RepositoryConst::SPASE_URI);

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
          $spaseMap = $mapping[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];

          if(preg_match('/^Catalog/', $spaseMap)==1){
            $this->setCatalog($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^DisplayData/', $spaseMap)==1){
              $this->setDisplaydata($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Granule/', $spaseMap)==1){
              $this->setGranule($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^Instrument/', $spaseMap)==1){
              $this->setInstrument($mapping[$ii], $metadata[$ii]);
            }else if(preg_match('/^NumericalData/', $spaseMap)==1){
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
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
                case RepositoryConst::SPASE_CATALOG_INSTRUMENTID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addInstrumentID($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_CATALOG_KEYWORD:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addKeyword($tmp);
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
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
                case RepositoryConst::SPASE_DISPLAYDATA_INSTRUMENTID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addInstrumentID($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_DISPLAYDATA_KEYWORD:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->catalog->addKeyword($tmp);
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
                case RepositoryConst::SPASE_GRANULE_SOURCE_DATAEXTENT_QUANTITY:
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
                    $this->instrument->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_TYPE:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction, $value, $language);
                    $this->instrument->addInstrumenttype($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
                    $this->instrument->addInvestigationname($tmp);
                    break;
                case RepositoryConst::SPASE_INSTRUMENT_OBSERVATORYID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->instrument->addObservatoryID($tmp);
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
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_PERSONID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addResourceheader_Contact_PersonID($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_RESOURCEHEADER_CONTACT_ROLE:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
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
                case RepositoryConst::SPASE_NUMERICALDATA_INSTRUMENTID:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addInstrumentID($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_PHENOMENONTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addPhenomenontype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_MEASUREMENTTYPE:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addMeasurementtype($tmp);
                    break;
                case RepositoryConst::SPASE_NUMERICALDATA_KEYWORD:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->numericaldata->addKeyword($tmp);
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
                    $this->observatory->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION:
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                              $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                              $tmp->addArray($tmp2);
                              $this->person->addEmail($tmp);
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
                    $tmp = new Repository_Oaipmh_SPASE_Array($this->RepositoryAction);
                    $tmp2 = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $tmp->addArray($tmp2);
                    $this->repository->addResourceheader_Contact_Role($tmp);
                    break;
                case RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL:
                    $tmp = new Repository_Oaipmh_SPASE_LangString($this->RepositoryAction, $value, $language);
                    $this->repository->addAccessurl_url($tmp);
                    break;
                default :
                    break;
            }
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

  private $InstrumentID = null;
  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Keyword = null;
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

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
  $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
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

  public function addInstrumentID(Repository_Oaipmh_SPASE_LangString $InstrumentID){
    if($this->InstrumentID == null){
        $this->InstrumentID = $InstrumentID;
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

  public function addKeyword(Repository_Oaipmh_SPASE_LangString $Keyword){
    if($this->Keyword == null){
        $this->Keyword = $Keyword;
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

    /*
    $xmlStr .= '<Version>';
    $xmlStr .= RepositoryConst::SPASE_VERSION;
    $xmlStr .= '</Version>'. "\n";
    */
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


    if($this->InstrumentID != null)
    {
        $xml = $this->InstrumentID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_INSTRUMENTID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
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

    if($this->Keyword != null)
    {
        $xml = $this->Keyword->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_C_KEYWORD);
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

  private $InstrumentID = null;
  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Keyword = null;
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

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
    if(strlen($tmp)>0){
    array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
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

  public function addInstrumentID(Repository_Oaipmh_SPASE_LangString $InstrumentID){
    if($this->InstrumentID == null){
        $this->InstrumentID = $InstrumentID;
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

  public function addKeyword(Repository_Oaipmh_SPASE_LangString $Keyword){
    if($this->Keyword == null){
        $this->Keyword = $Keyword;
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

    if($this->Resourceheader_Contact_Role != null){
    for($ii=0;$ii<count($this->Resourceheader_Contact_Role);$ii++){
          $xml = $this->Resourceheader_Contact_Role[$ii]->output();
          if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_RESOURCEHEADER_CONTACT_ROLE);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
          }
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

  if($this->InstrumentID != null)
  {
      $xml = $this->InstrumentID->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_DD_INSTRUMENTID);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
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

    if($this->Keyword != null)
    {
        $xml = $this->Keyword->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_DD_KEYWORD);
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

  private $InstrumentID = null;
  private $Phenomenontype = null;
  private $Measurementtype = null;
  private $Keyword = null;
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

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
    if(strlen($tmp)>0){
    array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
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

  public function addInstrumentID(Repository_Oaipmh_SPASE_LangString $InstrumentID){
    if($this->InstrumentID == null){
        $this->InstrumentID = $InstrumentID;
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

  public function addKeyword(Repository_Oaipmh_SPASE_LangString $Keyword){
    if($this->Keyword == null){
        $this->Keyword = $Keyword;
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

    if($this->InstrumentID != null)
    {
        $xml = $this->InstrumentID->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_INSTRUMENTID);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
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

    if($this->Keyword != null)
    {
        $xml = $this->Keyword->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_ND_KEYWORD);
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

    return $xmlStr; // $xmlStrに何も入ってない...
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
  private $ObservatoryID = null;

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
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
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
    $tmp = RepositoryOutputFilterSPASE::retValue($Investigationname->getArrayCount());
    if(strlen($tmp)>0){
      array_push($this->Investigationname, $Investigationname);
    }
  }

  public function addObservatoryID(Repository_Oaipmh_SPASE_LangString $ObservatoryID){
    if($this->ObservatoryID == null){
        $this->ObservatoryID = $ObservatoryID;
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

  if($this->ObservatoryID != null)
  {
    $xmlStr .= '<ObservatoryID>'."\n";
      $xml = $this->ObservatoryID->output();
      if(strlen($xml)>0){
          $value = explode(".",RepositoryConst::SPASE_I_OBSERVATORYID);
          $xmlStr .= '<'.$value[count($value)-1].'>';
          $xmlStr .= $xml;
          $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
      }
    $xmlStr .= '</ObservatoryID>'."\n";

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
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
  }


  public function addLocation_Observatoryregion(Repository_Oaipmh_SPASE_Array $Location_Observatoryregion){
    $tmp = RepositoryOutputFilterSPASE::retValue($Location_Observatoryregion->getArrayCount());
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
    $tmp = RepositoryOutputFilterSPASE::retValue($Email->getArrayCount());
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

/*
  for($ii=0;$ii<count($this->Email);$ii++){
    $xml = $this->Email[$ii]->output();
    if(strlen($xml)>0){
        $xmlStr .= '<'.RepositoryConst::SPASE_P_EMAIL.'>';
        $xmlStr .= $xml;
        $xmlStr .= '</'.RepositoryConst::SPASE_P_EMAIL.'>'."\n";
    }
  }
  */

  if($this->Email != null)
  {
      for($ii=0;$ii<count($this->Email);$ii++){
        $xml = $this->Email[$ii]->output();
        if(strlen($xml)>0){
            $value = explode(".",RepositoryConst::SPASE_P_EMAIL);
            $xmlStr .= '<'.$value[count($value)-1].'>';
            $xmlStr .= $xml;
            $xmlStr .= '</'.$value[count($value)-1].'>'."\n";
        }
      }
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

  public function addResourceheader_Contact_Role(Repository_Oaipmh_SPASE_Array $Resourceheader_Contact_Role){
    $tmp = RepositoryOutputFilterSPASE::retValue($Resourceheader_Contact_Role->getArrayCount());
    if(strlen($tmp)>0){
      array_push($this->Resourceheader_Contact_Role, $Resourceheader_Contact_Role);
    }
  }

  public function addAccessurl_url(Repository_Oaipmh_SPASE_LangString $Accessurl_url){
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
          $value = explode(".",RepositoryConst::SPSAE_G_SOUCE_DATAEXTENT_QUANTITY);
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
       if($this->ar == null){
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
            $xmlStr .= $this->source->output();
        }
        //ar
        for($ii=0;$ii<count($this->ar);$ii++){
            $xml = $this->ar[$ii]->output();
            if(strlen($xml)>0){
                $xmlStr.=$xml;
            }
        }
        return $xmlStr;
    }
}

?>
