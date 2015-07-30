<?php
// --------------------------------------------------------------------
//
// $Id: SPASE.class.php 42605 2014-10-03 01:02:01Z Takahiro.M $
// junii2 base
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR . '/modules/repository/oaipmh/format/FormatAbstract.class.php';
require_once WEBAPP_DIR . '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR . '/modules/repository/components/RepositoryHandleManager.class.php';
class Repository_Oaipmh_Spase extends Repository_Oaipmh_FormatAbstract {
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
	public function __construct($session, $db){
		parent::__construct($session, $db);
	}
	private function initialize(){
		$this->occurs = array(
				RepositoryConst::SPASE_LANGUAGE => 1,
				RepositoryConst::SPASE_CATALOG_RESOURCEID => 1,
				RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME => 1,
				RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA   => 1,
				RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION    => 1,
				RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT => 1,
				RepositoryConst::SPASE_CATALOG_CONTACT_PERSONID => 1,
				RepositoryConst::SPASE_CATALOG_CONTACT_ROLE => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION => 1,
				RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT => 1,
				RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE => 1,
				RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE => 1,
				RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE => 1,
				RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE => 1,
				RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE => 1,
				RepositoryConst::SPASE_CATALOG_OBSERVEDREGION => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLATITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLATITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE => 1,
				RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_NAME => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY => 1,
				RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY => 1,
				
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEID => 1,
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME => 1,
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATA => 1,
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION => 1,
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID => 1,
				RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE => 1,
				RepositoryConst::SPASE_INSTRUMENT_INSTRUMENTTYPE => 1,
				RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME => 1,
				RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID => 1,
				
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEID => 1,
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME => 1,
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATA => 1,
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION => 1,
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID => 1,
				RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE => 1,
				RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION => 1,
				RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE => 1,
				RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE => 1,
				
				RepositoryConst::SPASE_PERSON_RESOURCEID => 1,
				RepositoryConst::SPASE_PERSON_RELEASEDATE => 1,
				RepositoryConst::SPASE_PERSON_PERSONNAME => 1,
				RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME => 1,
				RepositoryConst::SPASE_PERSON_EMAIL => 1,
				
				RepositoryConst::SPASE_REPOSITORY_RESOURCEID => 1,
				RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME => 1,
				RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATA => 1,
				RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION => 1,
				RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID => 1,
				RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE => 1,
				RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL => 1,
				
				RepositoryConst::SPASE_GRANULE_RESOURCEID => 1,
				RepositoryConst::SPASE_GRANULE_RELEASEDATA => 1,
				RepositoryConst::SPASE_GRANULE_PARENTID => 1,
				RepositoryConst::SPASE_GRANULE_STARTDATE => 1,
				RepositoryConst::SPASE_GRANULE_STOPDATE => 1,
				RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE => 1,
				RepositoryConst::SPASE_GRANULE_SOURCE_URL => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUTE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGTITUDE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE => 1,
				RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE => 1);
	}
	
	/**
	 * output OAI-PMH metadata Tag format DublinCore
	 *
	 * @param array $itemData
	 *        	$this->getItemData return
	 * @return string xml
	 */
	public function outputRecord($itemData){
		if(!isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM]) || !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE]))
		// 基本情報以外のメタデータが存在しない場合に判定に入ってしまうことを防ぐためコメントアウト
		// !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE]) ||
		// !isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]))
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
		//SPASE is not same to Junii2, not necessary to add DOI..
		//removed by T.Mabuchi
		//$result = $this->outputSelfDOI($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO]);
		
		//if($result === false){
		//	return '';
		//}
		//removed by T.Mabuchi
		//$xml .= 'selfDOI:JaLC';
		// Add new prefix 2013/12/26 T.Ichikawa --end--
		
		// NIIType output
		$niiType = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_MAPPING_INFO];
		
		if(is_null($niiType)){
			return '';
		}
		
		$xml .= $this->outputNiiType($niiType);
		
		// metadata output
		if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR])){
			$xml .= $this->outputMetadta($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE], $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR]);
		}
		
		// item link output
		if(isset($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE])){
			$xml .= $this->outputReference($itemData[RepositoryConst::ITEM_DATA_KEY_ITEM_REFERENCE]);
		}
		
		// date tag check
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA] > 0){
			// YYYY-MM-DD or YYYY-MM or YYYY only
			$insDate = $itemData[RepositoryConst::ITEM_DATA_KEY_ITEM][0][RepositoryConst::DBCOL_COMMON_INS_DATE];
			$value = explode(" ", $insDate);
			$xml .= $this->outputDate($value[0]);
		}
		
		// when return false, metadata occurs failed.
		if(!$this->occursCheck()){
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
	private function outputHeader(){
		$xml = '';
		$xml .= '<' . RepositoryConst::SPASE_START;
		$xml .= ' xsi:schemaLocation="http://www.iugonet.org/data/schema/ ';
		$xml .= ' http://www.iugonet.org/data/schema/iugonet-1_0_4.xsd">' . self::LF;
		return $xml;
	}
	
	/**
	 * item basic data output
	 *
	 * @param array $baseData        	
	 * @return string
	 */
	private function outputBasicData($baseData){
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
		
		if($language == RepositoryConst::ITEM_LANG_JA){
			// japanese. 日本語
			$title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
			if(strlen($title) == 0){
				$title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
				$title_lang = RepositoryConst::ITEM_LANG_EN;
			}else{
				$alternative = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
				$title_lang = RepositoryConst::ITEM_LANG_JA;
				$alternative_lang = RepositoryConst::ITEM_LANG_EN;
			}
		}else{
			// not japanese. 洋語
			$title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
			if(strlen($title) == 0){
				$title = $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
				$title_lang = RepositoryConst::ITEM_LANG_JA;
			}else{
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
		$keyword = explode("|", $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY] . "|" . $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
		for($ii = 0; $ii < count($keyword); $ii++){
			//$xml .= $this->outputSubject($keyword[$ii]);
		}
		
		// Add new prefix 2013/12/26 T.Ichikawa --start--
		$this->getRepositoryHandleManager();
		// URL
		$url = $this->repositoryHandleManager->createUriForSpase($baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID], $baseData[RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO]);
		$xml .= $this->outputURI($url);
		// Add new prefix 2013/12/26 T.Ichikawa --end--
		
		return $xml;
	}
	
	/**
	 * output metadata
	 *
	 * @param array $itemAttrType
	 *        	mapping info. マッピング情報
	 * @param array $itemAttr
	 *        	metadata ingo. メタデータ情報
	 * @return string
	 */
	private function outputMetadta($itemAttrType, $itemAttr){
		$xml = '';
		
		$value = '';
		for($ii = 0; $ii < count($itemAttrType); $ii++){
			if($itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
				// hidden metadata
				continue;
			}
			
			// Add data filter parameter Y.Nakao 2013/05/17 --start--
			if($this->dataFilter == self::DATA_FILTER_SIMPLE && $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_LIST_VIEW_ENABLE] == 0){
				// when data fileter is "simple", output list_view_enable=1 metadata.
				continue;
			}
			// Add data filter parameter Y.Nakao 2013/05/17 --end--
			
			// get value par input type. 入力タイプ別に出力値を求める
			$inputType = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE];
			$lang = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_DISPLAY_LANG_TYPE];
			$lang = RepositoryOutputFilter::language($lang);
			
			// get mapping info. マッピング情報取得
			$spaseMap = $itemAttrType[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SPASE_MAPPING];
			if(strlen($spaseMap) == 0 && $inputType != "file" && $inputType != "file_price"){
				// when is not mapping info, not output. マッピング情報がなければスルー
				continue;
			}
			
			for($jj = 0; $jj < count($itemAttr[$ii]); $jj++){
				$value = RepositoryOutputFilter::attributeValue($itemAttrType[$ii], $itemAttr[$ii][$jj], 2, 2);
				if(strlen($value) > 0){
					if($inputType == RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO){
						// jtitle,volume,issue,spage,epage,dateofissued
						$mapping = explode(",", $spaseMap);
						// $jtitle = $jtitle_en||$volume||$issue||$spage||$epage||$dateofissued
						$biblio = explode("||", $value);
						// when output biblioinfo for junii2.
						if(count($mapping) == 6 && count($biblio) == 6){
							$xml .= $this->outputAttributeValue($mapping[0], $biblio[0]);
							$xml .= $this->outputAttributeValue($mapping[1], $biblio[1]);
							$xml .= $this->outputAttributeValue($mapping[2], $biblio[2]);
							$xml .= $this->outputAttributeValue($mapping[3], $biblio[3]);
							$xml .= $this->outputAttributeValue($mapping[4], $biblio[4]);
							$xml .= $this->outputAttributeValue($mapping[5], $biblio[5]);
						}
					} // Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_NAME){
						$nameAuthority = new NameAuthority($this->Session, $this->Db);
						$nameAuthorityInfo = $nameAuthority->getExternalAuthorIdData($itemAttr[$ii][$jj]["author_id"]);
						
						$reseacherResolverArray = array();
						if(count($nameAuthorityInfo) > 0 && $nameAuthorityInfo[0]["prefix_id"] == 2){
							$reseacherResolverId = $nameAuthorityInfo[0]["suffix"];
							$reseacherResolverArray = array(
									"prefix_id" => 2,
									"suffix" => $reseacherResolverId 
							);
						}
						$xml .= $this->outputAttributeValue($spaseMap, $value, $lang, $reseacherResolverArray);
					} // Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
					  // Add for Bug No.1 Fixes R.Matsuura 2013/09/24 --start--
					else if($inputType == RepositoryConst::ITEM_ATTR_TYPE_FILE || $inputType == RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE){
						$xml .= $this->outputattributeValue($spaseMap, $value);
						$licenceNotation = RepositoryOutputFilter::fileLicence($itemAttr[$ii][$jj]);
						$xml .= $this->outputRights($licenceNotation);
					} // Add for Bug No.1 Fixes R.Matsuura 2013/09/24 --end--
					else{
						// when is value, output. 値があれば出力
						$xml .= $this->outputAttributeValue($spaseMap, $value, $lang);
					}
				}
			}
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
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME] < 1)
		{
			// when over maxOccurs, output alternative.
			return $this->outputAlternative($title, $lang);
		}
	
		// output title
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
		$option = array();
		if(strlen($lang) > 0)
		{
			$option[RepositoryConst::SPASE_LANGUAGE] = 'ja';
		}
		$xml = $this->outputElement($tag, $title, $option);
		if(strlen($xml)>0)
		{
			$this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME]--;
		}
	
		return $xml;
	
	}
	
	private function outputAlternative($alternative, $lang="")
	{
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
		$option = array();
		if(strlen($lang) > 0)
		{
			$option[RepositoryConst::SPASE_LANGUAGE] = 'ja';
		}
		return $this->outputElement($tag, $alternative, $option);
	}
	
	private function outputSubject($subject)
	{
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
		return $this->outputElement($tag, $subject);
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
	private function outputAttributeValue($mapping, $value, $lang = "", $authorIdArray = array()){
		$xml = '';
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
		//$lang = RepositoryOutputFilterJuNii2::languageToRFC($lang);
		$lang = "en";
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
		
		switch($mapping){
			case RepositoryConst::SPASE_LANGUAGE:
				$xml = $this->outputLanguage($value, $lang);
				break;
			case RepositoryConst::SPASE_CATALOG_RESOURCEID:
				$xml = $this->outputCatalogResourceId($value, $lang);
				break;
			case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME:
				$xml = $this->outputCatalogResourceName($value, $lang);
				break;
			case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA:
				$xml = $this->outputCatalogReleaseData($value, $lang, $authorIdArray);
				break;
			case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION:
				$xml = $this->outputCatalogDescription($value);
				break;
			case RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT:
				$xml = $this->outputCatalogAcknowledgement($value);
				break;
			case RepositoryConst::SPASE_CATALOG_CONTACT_PERSONID:
				$xml = $this->outputCatalogPersonId($value);
				break;
			case RepositoryConst::SPASE_CATALOG_CONTACT_ROLE:
				$xml = $this->outputCatalogRole($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID:
				$xml = $this->outputCatalogRepositoryId($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY:
				$xml = $this->outputCatalogAvailability($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS:
				$xml = $this->outputCatalogAccessrights($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME:
				$xml = $this->outputCatalogName($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL:
				$xml = $this->outputCatalogURL($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION:
				$xml = $this->outputCatalogUrlDescription($value);
				break;
			case RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT:
				$xml = $this->outputCatalogFormat($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE:
				$xml = $this->outputCatalogPhenomenontype($value);
				break;
			case RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE:
				$xml = $this->outputCatalogMeasurementtype($value, $lang, $authorIdArray);
				break;
			case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE:
				$xml = $this->outputCatalogStartDate($value, $lang, $authorIdArray);
				break;
			case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE:
				$xml = $this->outputCatalogStopDate($value);
				break;
			case RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE:
				$xml = $this->outputCatalogRelativesStopDate($value);
				break;
			case RepositoryConst::SPASE_CATALOG_OBSERVEDREGION:
				$xml = $this->outputCatalogObservedRegion($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
				$xml = $this->outputCatalogCoordinateSystemName($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
				$xml = $this->outputCatalogCoordinateRepresentation($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
				$xml = $this->outputCatalogNorthernostLatitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE:
				$xml = $this->outputCatalogSouthernmostLatitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLATITUDE:
				$xml = $this->outputCatalogEasternmostLatitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLATITUDE:
				$xml = $this->outputCatalogWesternmostLatitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT:
				$xml = $this->outputCatalogUnit($value, $lang);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE:
				$xml = $this->outputCatalogMinimumAltitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE:
				$xml = $this->outputCatalogMaximumAltitude($value);
				break;
			case RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE:
				$xml = $this->outputCatalogReference($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_NAME:
				$xml = $this->outputCatalogParameterName($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION:
				$xml = $this->outputCatalogParameterDescription($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY:
				$xml = $this->outputCatalogParameterFieldQuantity($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE:
				$xml = $this->outputCatalogParameterParticleType($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY:
				$xml = $this->outputCatalogParameterParticleQuantity($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE:
				$xml = $this->outputCatalogParameterWaveType($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY:
				$xml = $this->outputCatalogParameterWaveQuality($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY:
				$xml = $this->outputCatalogParameterMixedQuantity($value);
				break;
			case RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY:
				$xml = $this->outputCatalogParameterSupportQuantity($value);
				break;
			
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEID:
				$xml = $this->outputInstrumentResourceId($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME:
				$xml = $this->outputInstrumentResourceName($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATA:
				$xml = $this->outputInstrumentReleaseData($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION:
				$xml = $this->outputInstrumentDescription($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID:
				$xml = $this->outputInstrumentPersonId($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE:
				$xml = $this->outputInstrumentContactRole($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_INSTRUMENTTYPE:
				$xml = $this->outputInstrumentInstrumentType($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME:
				$xml = $this->outputInstrumentInvestigationName($value);
				break;
			case RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID:
				$xml = $this->outputInstrumentObsevatoryId($value);
				break;
			
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEID:
				$xml = $this->outputObservatoryResourceId($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME:
				$xml = $this->outputObservatoryResourceName($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATA:
				$xml = $this->outputObservatoryReleaseData($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION:
				$xml = $this->outputObservatoryDescription($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID:
				$xml = $this->outputObservatoryPersonId($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE:
				$xml = $this->outputObservatoryRole($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION:
				$xml = $this->outputObservatoryObservatoryRegion($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE:
				$xml = $this->outputObservatoryLatitude($value);
				break;
			case RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE:
				$xml = $this->outputObservatoryLongitude($value);
				break;
			
			case RepositoryConst::SPASE_PERSON_RESOURCEID:
				$xml = $this->outputPersonResourceId($value);
				break;
			case RepositoryConst::SPASE_PERSON_RELEASEDATE:
				$xml = $this->outputPersonReleaseDate($value);
				break;
			case RepositoryConst::SPASE_PERSON_PERSONNAME:
				$xml = $this->outputPersonPersonName($value);
				break;
			case RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME:
				$xml = $this->outputPersonOrganizatioName($value);
				break;
			case RepositoryConst::SPASE_PERSON_EMAIL:
				$xml = $this->outputPersonEmail($value);
				break;
			
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEID:
				$xml = $this->outputRepositoryResourceId($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME:
				$xml = $this->outputRepositoryResourceName($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATA:
				$xml = $this->outputRepositoryReleaseData($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION:
				$xml = $this->outputRepositoryDescription($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID:
				$xml = $this->outputRepositoryPersonId($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE:
				$xml = $this->outputRepositoryRole($value);
				break;
			case RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL:
				$xml = $this->outputRepositoryUrl($value);
				break;
			
			case RepositoryConst::SPASE_GRANULE_RESOURCEID:
				$xml = $this->outputGranuleResourceId($value);
				break;
			case RepositoryConst::SPASE_GRANULE_RELEASEDATA:
				$xml = $this->outputGranuleReleaseData($value);
				break;
			case RepositoryConst::SPASE_GRANULE_PARENTID:
				$xml = $this->outputGranuleParentId($value);
				break;
			case RepositoryConst::SPASE_GRANULE_STARTDATE:
				$xml = $this->outputGranuleStartDate($value);
				break;
			case RepositoryConst::SPASE_GRANULE_STOPDATE:
				$xml = $this->outputGranuleStopDate($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE:
				$xml = $this->outputGranuleSourceType($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SOURCE_URL:
				$xml = $this->outputGranuleUrl($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME:
				$xml = $this->outputGranuleCoordinateSystemName($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION:
				$xml = $this->outputGranuleCoordinateRepresentation($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE:
				$xml = $this->outputGranuleNorthernmostLatitude($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUTE:
				$xml = $this->outputGranuleSouthernmostLatitute($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE:
				$xml = $this->outputGranuleEasternmostLongitude($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGTITUDE:
				$xml = $this->outputGranuleWesternmostLongtitude($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT:
				$xml = $this->outputGranuleUnit($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE:
				$xml = $this->outputGranuleMinimumAltitude($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE:
				$xml = $this->outputGranuleMaximumAltitude($value);
				break;
			case RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE:
				$xml = $this->outputGranuleReference($value);
				break;
			default:
				break;
		}
		return $xml;
	}
	
	private function outputCatalogResourceId($value, $lang = ""){
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEID;
		return $this->outputElement($tag, $format);
	}
	
	/**
	 * title output
	 * is necessary.
	 * minOccurs = 1, maxOccurs = 1
	 * option = lang
	 *
	 * @param string $title        	
	 * @param string $lang        	
	 * @return string
	 */
	private function outputCatalogResourceName($title, $lang = ""){
		// occursCheck
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME] < 1){
			// when over maxOccurs, output alternative.
			return $this->outputAlternative($title, $lang);
		}
		
		// output title
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
		$option = array();
		if(strlen($lang) > 0){
			$option[RepositoryConst::SPASE_LANGUAGE] = $lang;
		}
		$xml = $this->outputElement($tag, $title, $option);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME]--;
		}
		
		return $xml;
	}
	
	
	
	/**
	 * alternative output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = lang
	 *
	 * @param string $alternative        	
	 * @param string $lang        	
	 * @return string
	 */
	private function outputCatalogReleaseData($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA]--;
		}
		return $xml;
	}

	private function outputCatalogDescription($description){
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION;
		return $this->outputElement($tag, $description);
	}
	
	/**
	 * subject output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = null
	 *
	 * @param string $subject        	
	 * @return string
	 */
	private function outputCatalogAcknowledgement($acknowledgement){
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT;
		return $this->outputElement($tag, $acknowledgement);
	}
	
	private function outputCatalogPersonId($val){
		$tag = RepositoryConst::SPASE_CATALOG_CONTACT_PERSONID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogRole($val){
		$tag = RepositoryConst::SPASE_CATALOG_CONTACT_ROLE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogRepositoryId($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogAvailability($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogAccessrights($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogName($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogURL($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogUrlDescription($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogFormat($val){
		$tag = RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogPhenomenontype($val){
		$tag = RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogMeasurementtype($val){
		$tag = RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogStartDate($val){
		$tag = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogStopDate($val){
		$tag = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogRelativesStopDate($val){
		$tag = RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogObservedRegion($val){
		$tag = RepositoryConst::SPASE_CATALOG_OBSERVEDREGION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogCoordinateSystemName($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogCoordinateRepresentation($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogNorthernostLatitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogSouthernmostLatitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogEasternmostLatitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogWesternmostLatitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogUnit($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogMinimumAltitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogMaximumAltitude($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogReference($val){
		$tag = RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterName($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_NAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterDescription($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterFieldQuantity($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterParticleType($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterParticleQuantity($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterWaveType($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterWaveQuality($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterMixedQuantity($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputCatalogParameterSupportQuantity($val){
		$tag = RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentResourceId($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentResourceName($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentReleaseData($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATA;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATA]--;
		}
		return $xml;
	}
	
	private function outputInstrumentDescription($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentPersonId($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentContactRole($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentInstrumentType($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_INSTRUMENTTYPE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentInvestigationName($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputInstrumentObsevatoryId($val){
		$tag = RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryResourceId($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryResourceName($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryReleaseData($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATA;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATA]--;
		}
		return $xml;
	}
	
	private function outputObservatoryDescription($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryPersonId($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryRole($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryObservatoryRegion($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryLatitude($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputObservatoryLongitude($val){
		$tag = RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputPersonResourceId($val){
		$tag = RepositoryConst::SPASE_PERSON_RESOURCEID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputPersonReleaseDate($val){
		$tag = RepositoryConst::SPASE_PERSON_RELEASEDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputPersonPersonName($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_PERSON_RELEASEDATE;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_PERSON_RELEASEDATE]--;
		}
		return $xml;
	}
	
	private function outputPersonOrganizatioName($val){
		$tag = RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputPersonEmail($val){
		$tag = RepositoryConst::SPASE_PERSON_EMAIL;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryResourceId($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryResourceName($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryReleaseData($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATA;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATA]--;
		}
		return $xml;
	}
	
	private function outputRepositoryDescription($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryPersonId($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryRole($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputRepositoryUrl($val){
		$tag = RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleResourceId($val){
		$tag = RepositoryConst::SPASE_GRANULE_RESOURCEID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleReleaseData($alternative, $lang = ""){
		$tag = RepositoryConst::SPASE_GRANULE_RELEASEDATA;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_GRANULE_RELEASEDATA]--;
		}
		return $xml;
	}
	
	private function outputGranuleParentId($val){
		$tag = RepositoryConst::SPASE_GRANULE_PARENTID;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleStartDate($val){
		$tag = RepositoryConst::SPASE_GRANULE_STARTDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleStopDate($val){
		$tag = RepositoryConst::SPASE_GRANULE_STOPDATE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleSourceType($val){
		$tag = RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE;
		return $this->outputElement($tag, $val);
	}
	private function outputGranuleUrl($val){
		$tag = RepositoryConst::SPASE_GRANULE_SOURCE_URL;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleCoordinateSystemName($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleCoordinateRepresentation($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleNorthernmostLatitude($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleSouthernmostLatitute($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUTE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleEasternmostLongitude($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleWesternmostLongtitude($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGTITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleUnit($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleMinimumAltitude($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleMaximumAltitude($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE;
		return $this->outputElement($tag, $val);
	}
	
	private function outputGranuleReference($val){
		$tag = RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE;
		return $this->outputElement($tag, $val);
	}
	/*
	private function ($val){
		$tag = RepositoryConst::aaaaaaaaa;
		return $this->outputElement($tag, $val);
	}
	*/
	
	
	
	/**
	 * BSH output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $BSH        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputBSH($BSH, $version = ""){
		$tag = RepositoryConst::JUNII2_BSH;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $BSH, $option);
	}
	
	/**
	 * NDLSH output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $NDLSH        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputNDLSH($NDLSH, $version = ""){
		$tag = RepositoryConst::JUNII2_NDLSH;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $NDLSH, $option);
	}
	
	/**
	 * MeSH output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $MeSH        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputMeSH($MeSH, $version = ""){
		$tag = RepositoryConst::JUNII2_MESH;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $MeSH, $option);
	}
	
	/**
	 * DDC output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $DDC        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputDDC($DDC, $version = ""){
		$tag = RepositoryConst::JUNII2_MESH;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $DDC, $option);
	}
	
	/**
	 * LCC output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $LCC        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputLCC($LCC, $version = ""){
		$tag = RepositoryConst::JUNII2_DDC;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $LCC, $option);
	}
	
	/**
	 * UDC output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $UDC        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputUDC($UDC, $version = ""){
		$tag = RepositoryConst::JUNII2_UDC;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $UDC, $option);
	}
	
	/**
	 * LCSH output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = version
	 *
	 * @param string $LCSH        	
	 * @param string $version        	
	 * @return string
	 */
	private function outputLCSH($LCSH, $version = ""){
		$tag = RepositoryConst::JUNII2_LCSH;
		$option = array();
		if(strlen($version) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_VERSION] = $version;
		}
		return $this->outputElement($tag, $LCSH, $option);
	}
	
	/**
	 * description output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $description        	
	 * @return string
	 */
	private function outputDescription($description){
		$tag = RepositoryConst::JUNII2_DESCRIPTION;
		return $this->outputElement($tag, $description);
	}
	
	/**
	 * publisher output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = lang
	 *
	 * @param string $publisher        	
	 * @param string $lang
	 *        	// Add JuNii2 ver3 R.Matsuura 2013/09/24
	 * @param array $authorIdArray        	
	 * @return string
	 */
	private function outputPublisher($publisher, $lang = "", $authorIdArray = array()){
		$tag = RepositoryConst::JUNII2_PUBLISHER;
		
		$option = array();
		if(strlen($lang) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
		}
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
		$uri = RepositoryOutputFilter::creatorId($authorIdArray);
		if(strlen($uri) > 0){
			$option["id"] = $uri;
		}
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
		return $this->outputElement($tag, $publisher, $option);
	}
	
	/**
	 * contributor output
	 * minOccurs = 0, maxOccurs = unbounded
	 * option = lang
	 *
	 * @param string $contributor        	
	 * @param string $lang
	 *        	// Add JuNii2 ver3 R.Matsuura 2013/09/24
	 * @param array $authorIdArray        	
	 * @return string
	 */
	private function outputContributor($contributor, $lang = "", $authorIdArray = array()){
		$tag = RepositoryConst::JUNII2_CONTRIBUTOR;
		
		$option = array();
		if(strlen($lang) > 0){
			$option[RepositoryConst::JUNII2_ATTRIBUTE_LANG] = $lang;
		}
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
		$uri = RepositoryOutputFilter::creatorId($authorIdArray);
		if(strlen($uri) > 0){
			$option["id"] = $uri;
		}
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
		return $this->outputElement($tag, $contributor, $option);
	}
	
	/**
	 * date output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $date        	
	 * @return string
	 */
	private function outputDate($date){
		$tag = RepositoryConst::JUNII2_DATE;
		$date = RepositoryOutputFilter::date($date);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_DATE]--;
		}
		return $xml;
	}
	
	/**
	 * type output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $type        	
	 * @return string
	 */
	private function outputType($type){
		$tag = RepositoryConst::JUNII2_TYPE;
		return $this->outputElement($tag, $type);
	}
	
	/**
	 * output NIIType
	 * is necessary.
	 * minOccurs = 1, maxOccurs = 1
	 *
	 * @param string $niiType        	
	 * @return string
	 */
	private function outputNIIType($niiType){
		$xml = '';
		if($niiType != RepositoryConst::NIITYPE_JOURNAL_ARTICLE && $niiType != RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION && $niiType != RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER && $niiType != RepositoryConst::NIITYPE_CONFERENCE_PAPER && $niiType != RepositoryConst::NIITYPE_PRESENTATION && $niiType != RepositoryConst::NIITYPE_BOOK && $niiType != RepositoryConst::NIITYPE_TECHNICAL_REPORT && $niiType != RepositoryConst::NIITYPE_RESEARCH_PAPER && $niiType != RepositoryConst::NIITYPE_ARTICLE && $niiType != RepositoryConst::NIITYPE_PREPRINT && $niiType != RepositoryConst::NIITYPE_LEARNING_MATERIAL && $niiType != RepositoryConst::NIITYPE_DATA_OR_DATASET && $niiType != RepositoryConst::NIITYPE_SOFTWARE && $niiType != RepositoryConst::NIITYPE_OTHERS){
			return '';
		}
		
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_NIITYPE] < 1){
			// when over maxOccurs, output type.
			return $this->outputType($niiType);
		}
		
		$tag = RepositoryConst::JUNII2_NIITYPE;
		$xml = $this->outputElement($tag, $niiType);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_NIITYPE]--;
		}
		return $xml;
	}
	
	/**
	 * format output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $type        	
	 * @return string
	 */
	private function outputFormat($format){
		$tag = RepositoryConst::JUNII2_FORMAT;
		return $this->outputElement($tag, $format);
	}
	
	/**
	 * identifier output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $type        	
	 * @return string
	 */
	private function outputIdentifier($identifier){
		$tag = RepositoryConst::JUNII2_IDENTIFIER;
		return $this->outputElement($tag, $identifier);
	}
	
	/**
	 * URI output
	 * is necessary.
	 * minOccurs = 1, maxOccurs = 1
	 *
	 * @param string $URI        	
	 * @return string
	 */
	private function outputURI($URI){
		if(strlen($URI) == 0){
			return '';
		}
		
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_URI] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($URI);
		}
		
		// output title
		$tag = RepositoryConst::JUNII2_URI;
		$xml = $this->outputElement($tag, $URI);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_URI]--;
		}
		return $xml;
	}
	
	/**
	 * fullTextURL output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $fullTextURL        	
	 * @return string
	 */
	private function outputFullTextURL($fullTextURL){
		$tag = RepositoryConst::JUNII2_FULL_TEXT_URL;
		return $this->outputElement($tag, $fullTextURL);
	}
	
	/**
	 * issn output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $issn        	
	 * @return string
	 */
	private function outputISSN($issn){
		$tag = RepositoryConst::JUNII2_ISSN;
		$issn = RepositoryOutputFilterJuNii2::issn($issn);
		return $this->outputElement($tag, $issn);
	}
	
	/**
	 * ncid output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $ncid        	
	 * @return string
	 */
	private function outputNCID($ncid){
		$tag = RepositoryConst::JUNII2_NCID;
		return $this->outputElement($tag, $ncid);
	}
	
	/**
	 * jtitle output
	 * minOccurs = 0, maxOccurs = 1
	 * option = lang
	 *
	 * @param string $jtitle        	
	 * @param string $lang        	
	 * @return string
	 */
	private function outputJtitle($jtitle, $lang = ""){
		// occursCheck
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($jtitle, $lang);
		}
		
		// output jtitle
		$tag = RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME;
		$option = array();
		if(strlen($lang) > 0){
			$option[RepositoryConst::SPASE_LANGUAGE] = $lang;
		}
		$xml = $this->outputElement($tag, $jtitle, $option);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME]--;
		}
		return $xml;
	}
	
	/**
	 * volume output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $volume        	
	 * @return string
	 */
	private function outputVolume($volume){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_VOLUME] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($volume);
		}
		
		// output volume
		$tag = RepositoryConst::JUNII2_VOLUME;
		$xml = $this->outputElement($tag, $volume);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_VOLUME]--;
		}
		return $xml;
	}
	
	/**
	 * issue output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $issue        	
	 * @return string
	 */
	private function outputIssue($issue){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_ISSUE] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($issue);
		}
		
		// output issue
		$tag = RepositoryConst::JUNII2_ISSUE;
		$xml = $this->outputElement($tag, $issue);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_ISSUE]--;
		}
		return $xml;
	}
	
	/**
	 * spage output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $issue        	
	 * @return string
	 */
	private function outputSpage($spage){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_SPAGE] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($spage);
		}
		
		// output spage
		$tag = RepositoryConst::JUNII2_SPAGE;
		$xml = $this->outputElement($tag, $spage);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_SPAGE]--;
		}
		return $xml;
	}
	
	/**
	 * epage output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $epage        	
	 * @return string
	 */
	private function outputEpage($epage){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_EPAGE] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($epage);
		}
		
		// output spage
		$tag = RepositoryConst::JUNII2_EPAGE;
		$xml = $this->outputElement($tag, $epage);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_EPAGE]--;
		}
		return $xml;
	}
	
	/**
	 * dateofissued output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $epage        	
	 * @return string
	 */
	private function outputDateofissued($dateofissued){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($dateofissued);
		}
		
		// output spage
		$tag = RepositoryConst::JUNII2_DATE_OF_ISSUED;
		$dateofissued = RepositoryOutputFilter::date($dateofissued);
		$xml = $this->outputElement($tag, $dateofissued);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED]--;
		}
		return $xml;
	}
	
	/**
	 * source output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $source        	
	 * @return string
	 */
	private function outputSource($source){
		$tag = RepositoryConst::JUNII2_SOURCE;
		return $this->outputElement($tag, $source);
	}
	
	/**
	 * language output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $language        	
	 * @return string
	 */
	private function outputLanguage($language){
		$tag = RepositoryConst::SPASE_LANGUAGE;
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
		//$language = RepositoryOutputFilterJuNii2::languageToISO($language);
		$language = 'ja';
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
		return $this->outputElement($tag, $language);
	}
	
	/**
	 * relation output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $language        	
	 * @return string
	 */
	private function outputRelation($relation){
		$tag = RepositoryConst::JUNII2_RELATION;
		return $this->outputElement($tag, $relation);
	}
	
	/**
	 * pmid output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $pmid        	
	 * @return string
	 */
	private function outputPmid($pmid){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_PMID] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($pmid);
		}
		
		// output issue
		$tag = RepositoryConst::JUNII2_PMID;
		$xml = $this->outputElement($tag, $pmid);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_PMID]--;
		}
		return $xml;
	}
	
	/**
	 * doi output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $doi        	
	 * @return string
	 */
	private function outputDoi($doi){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_DOI] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($doi);
		}
		
		// output issue
		$tag = RepositoryConst::JUNII2_DOI;
		$xml = $this->outputElement($tag, $doi);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_DOI]--;
		}
		return $xml;
	}
	
	/**
	 * isVersionOf output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isVersionOf        	
	 * @return string
	 */
	private function outputIsVersionOf($isVersionOf){
		$tag = RepositoryConst::JUNII2_IS_VERSION_OF;
		return $this->outputElement($tag, $isVersionOf);
	}
	
	/**
	 * hasVersion output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $hasVersion        	
	 * @return string
	 */
	private function outputHasVersion($hasVersion){
		$tag = RepositoryConst::JUNII2_HAS_VERSION;
		return $this->outputElement($tag, $hasVersion);
	}
	
	/**
	 * isReplacedBy output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isReplacedBy        	
	 * @return string
	 */
	private function outputIsReplacedBy($isReplacedBy){
		$tag = RepositoryConst::JUNII2_IS_REPLACED_BY;
		return $this->outputElement($tag, $isReplacedBy);
	}
	
	/**
	 * replaces output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isReplacedBy        	
	 * @return string
	 */
	private function outputReplaces($isReplaces){
		$tag = RepositoryConst::JUNII2_REPLACES;
		return $this->outputElement($tag, $replaces);
	}
	
	/**
	 * isRequiredBy output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isRequiredBy        	
	 * @return string
	 */
	private function outputIsRequiredBy($isRequiredBy){
		$tag = RepositoryConst::JUNII2_IS_REQUIRESD_BY;
		return $this->outputElement($tag, $isRequiredBy);
	}
	
	/**
	 * requires output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $requires        	
	 * @return string
	 */
	private function outputRequires($requires){
		$tag = RepositoryConst::JUNII2_REQUIRES;
		return $this->outputElement($tag, $requires);
	}
	
	/**
	 * isPartOf output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isPartOf        	
	 * @return string
	 */
	private function outputIsPartOf($isPartOf){
		$tag = RepositoryConst::JUNII2_IS_PART_OF;
		return $this->outputElement($tag, $isPartOf);
	}
	
	/**
	 * hasPart output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $hasPart        	
	 * @return string
	 */
	private function outputHasPart($hasPart){
		$tag = RepositoryConst::JUNII2_HAS_PART;
		return $this->outputElement($tag, $hasPart);
	}
	
	/**
	 * isReferencedBy output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isReferencedBy        	
	 * @return string
	 */
	private function outputIsReferencedBy($isReferencedBy){
		$tag = RepositoryConst::JUNII2_IS_REFERENCED_BY;
		return $this->outputElement($tag, $isReferencedBy);
	}
	
	/**
	 * references output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $references        	
	 * @return string
	 */
	private function outputReferences($references){
		$tag = RepositoryConst::JUNII2_REFERENCES;
		return $this->outputElement($tag, $references);
	}
	
	/**
	 * isFormatOf output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $isFormatOf        	
	 * @return string
	 */
	private function outputIsFormatOf($isFormatOf){
		$tag = RepositoryConst::JUNII2_IS_FORMAT_OF;
		return $this->outputElement($tag, $isFormatOf);
	}
	
	/**
	 * hasFormat output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $hasFormat        	
	 * @return string
	 */
	private function outputHasFormat($hasFormat){
		$tag = RepositoryConst::JUNII2_HAS_FORMAT;
		return $this->outputElement($tag, $hasFormat);
	}
	
	/**
	 * coverage output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $coverage        	
	 * @return string
	 */
	private function outputCoverage($coverage){
		$tag = RepositoryConst::JUNII2_COVERAGE;
		return $this->outputElement($tag, $coverage);
	}
	
	/**
	 * spatial output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $spatial        	
	 * @return string
	 */
	private function outputSpatial($spatial){
		$tag = RepositoryConst::JUNII2_SPATIAL;
		return $this->outputElement($tag, $spatial);
	}
	
	/**
	 * NIIspatial output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $NIIspatial        	
	 * @return string
	 */
	private function outputNIISpatial($NIIspatial){
		$tag = RepositoryConst::JUNII2_NII_SPATIAL;
		return $this->outputElement($tag, $NIIspatial);
	}
	
	/**
	 * temporal output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $temporal        	
	 * @return string
	 */
	private function outputTemporal($temporal){
		$tag = RepositoryConst::JUNII2_TEMPORAL;
		return $this->outputElement($tag, $temporal);
	}
	
	/**
	 * NIItemporal output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $NIItemporal        	
	 * @return string
	 */
	private function outputNIITemporal($NIItemporal){
		$tag = RepositoryConst::JUNII2_NII_TEMPORAL;
		return $this->outputElement($tag, $NIItemporal);
	}
	
	/**
	 * rights output
	 * minOccurs = 0, maxOccurs = unbounded
	 *
	 * @param string $rights        	
	 * @return string
	 */
	private function outputRights($rights){
		$tag = RepositoryConst::JUNII2_RIGHTS;
		return $this->outputElement($tag, $rights);
	}
	
	/**
	 * textversion output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $textversion        	
	 * @return string
	 */
	private function outputTextversion($textversion){
		// occursCheck
		if($this->occurs[RepositoryConst::JUNII2_TEXTVERSION] < 1){
			// when over maxOccurs, output identifier.
			return $this->outputIdentifier($textversion);
		}
		
		// output issue
		$tag = RepositoryConst::JUNII2_TEXTVERSION;
		$textversion = RepositoryOutputFilterJuNii2::textversion($textversion);
		$xml = $this->outputElement($tag, $textversion);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_TEXTVERSION]--;
		}
		return $xml;
	}
	
	/**
	 * return XML element.
	 *
	 * @param string $tag        	
	 * @param string $value        	
	 * @param array $oution
	 *        	array($key=>$value, $key=>$value, ... )
	 * @return string
	 */
	private function outputElement($tag, $value, $option = array()){
		$value = $this->RepositoryAction->forXmlChange($value);
		if(strlen($tag) == 0 || strlen($value) == 0){
			return '';
		}
		
		$strOption = '';
		foreach($option as $key => $val){
			if(strlen($key) > 0 && strlen($val) > 0){
				$val = $this->RepositoryAction->forXmlChange($val);
				$strOption .= "$key=\"$val\" ";
			}
		}
		
		if(strlen($strOption) > 0){
			$xml = "<$tag $strOption>$value</$tag>";
		}else{
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
	private function outputReference($reference){
		$xml = '';
		
		for($ii = 0; $ii < count($reference); $ii++){
			$destItemId = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_ID];
			$destItemNo = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_DEST_ITEM_NO];
			$refKey = $reference[$ii][RepositoryConst::DBCOL_REPOSITORY_REF_REFERENCE];
			// get detail url
			$refUrl = $this->RepositoryAction->getDetailUri($destItemId, $destItemNo);
			// mapping
			if(strlen($refKey) > 0){
				$xml .= $this->outputAttributeValue($refKey, $refUrl);
			}else{
				$xml .= $this->outputRelation($refUrl);
			}
		}
		return $xml;
	}
	
	/**
	 * occurs check
	 *
	 * return bool true:OK, false:failed
	 */
	private function occursCheck(){
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME] != 0){
			// title is necessary.
			// minOccurs=1, maxOccurs=1
			return false;
		}
		if($this->occurs[RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA] == 1){
			// date is min occurs = 1, maxOccurs=unbounded.
			return false;
		}
		/*
		if($this->occurs[RepositoryConst::JUNII2_NIITYPE] != 0){
			// NIIType is necessary.
			// minOccurs=1, maxOccurs=1
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_URI] != 0){
			// URI is necessary.
			// minOccurs=1, maxOccurs=1
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_JTITLE] < 0){
			// jtitle is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_VOLUME] < 0){
			// volume is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_ISSUE] < 0){
			// issue is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_SPAGE] < 0){
			// spage is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_EPAGE] < 0){
			// epage is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_DATE_OF_ISSUED] < 0){
			// dateofissued is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_PMID] < 0){
			// pmid is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_DOI] < 0){
			// doi is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_TEXTVERSION] < 0){
			// textversion is min occurs = 0, maxOccurs=1.
			return false;
		}
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
		if($this->occurs[RepositoryConst::JUNII2_SELFDOI] < 0){
			// selfdoi is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_SELFDOI_JALC] < 0){
			// selfdoi(jalc) is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_SELFDOI_CROSSREF] < 0){
			// selfdoi(crossref) is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_NAID] < 0){
			// NAID is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_ICHUSHI] < 0){
			// ichushi is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_GRANTID] < 0){
			// grantid is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED] < 0){
			// dateofgranted is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_DEGREENAME] < 0){
			// degreename is min occurs = 0, maxOccurs=1.
			return false;
		}
		if($this->occurs[RepositoryConst::JUNII2_GRANTOR] < 0){
			// grantor is min occurs = 0, maxOccurs=1.
			return false;
		}
		*/
		// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
		return true;
	}
	
	/**
	 * output footer
	 *
	 * @return string
	 */
	private function outputFooter(){
		$xml = '';
		$xml .= '</' . RepositoryConst::SPASE_START . '>' . self::LF;
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
	private function outputSelfDOI($item_id, $item_no){
		$tag = RepositoryConst::JUNII2_SELFDOI;
		$xml = "";
		
		$this->getRepositoryHandleManager();
		$uri_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_JALC_DOI);
		$uri_crossref = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_CROSS_REF_DOI);
		$uri_library_jalcdoi = $this->repositoryHandleManager->createSelfDoiUri($item_id, $item_no, RepositoryHandleManager::ID_LIBRARY_JALC_DOI);
		
		if(strlen($uri_jalcdoi) > 0 && strlen($uri_crossref) < 1 && strlen($uri_library_jalcdoi) < 1){
			$option = array();
			$option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
			$xml .= $this->outputElement($tag, $uri_jalcdoi, $option);
		}else if(strlen($uri_crossref) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_library_jalcdoi) < 1){
			$option = array();
			$option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_CROSSREF;
			$xml = $this->outputElement($tag, $uri_crossref, $option);
		}else if(strlen($uri_library_jalcdoi) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_crossref) < 1){
			$option = array();
			$option[RepositoryConst::JUNII2_SELFDOI_ATTRIBUTE_JALC_DOI] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
			$xml = $this->outputElement($tag, $uri_library_jalcdoi, $option);
		}
		
		return $xml;
	}
	private function getRepositoryHandleManager(){
		if(!isset($this->repositoryHandleManager)){
			if(strlen($this->RepositoryAction->TransStartDate) == 0){
				$date = new Date();
				$this->RepositoryAction->TransStartDate = $date->getDate() . ".000";
			}
			$this->repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->RepositoryAction->TransStartDate);
		}
	}
	
	// Add new prefix 2013/12/24 T.Ichikawa --end--
	
	// Add JuNii2 ver3 R.Matsuura 2013/09/24 --start--
	/**
	 * ISBN output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strIsbn        	
	 * @return string
	 */
	private function outputISBN($strIsbn){
		$tag = RepositoryConst::JUNII2_ISBN;
		$xml = $this->outputElement($tag, $strIsbn);
		return $xml;
	}
	
	/**
	 * NAID output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strNaid        	
	 * @return string
	 */
	private function outputNAID($strNaid){
		if($this->occurs[RepositoryConst::JUNII2_NAID] < 1){
			return $this->outputRelation($strNaid);
		}
		$tag = RepositoryConst::JUNII2_NAID;
		$naid = RepositoryOutputFilterJuNii2::naid($strNaid);
		$xml = $this->outputElement($tag, $naid);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_NAID]--;
		}
		return $xml;
	}
	
	/**
	 * Ichushi output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strIchushi        	
	 * @return string
	 */
	private function outputIchushi($strIchushi){
		if($this->occurs[RepositoryConst::JUNII2_ICHUSHI] < 1){
			return $this->outputRelation($strIchushi);
		}
		$tag = RepositoryConst::JUNII2_ICHUSHI;
		$ichushi = RepositoryOutputFilterJuNii2::ichushi($strIchushi);
		$xml = $this->outputElement($tag, $ichushi);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_ICHUSHI]--;
		}
		return $xml;
	}
	
	/**
	 * grantid output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strGrantId        	
	 * @return string
	 */
	private function outputGrantid($strGrantId){
		if($this->occurs[RepositoryConst::JUNII2_GRANTID] < 1){
			return $this->outputIdentifier($strGrantId);
		}
		$tag = RepositoryConst::JUNII2_GRANTID;
		$grantid = RepositoryOutputFilterJuNii2::grantid($strGrantId);
		$xml = $this->outputElement($tag, $grantid);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_GRANTID]--;
		}
		return $xml;
	}
	
	/**
	 * dateofgranted output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strDateofgrant        	
	 * @return string
	 */
	private function outputDateofgranted($strDateofgrant){
		if($this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED] < 1){
			return $this->outputDate($strDateofgrant);
		}
		$tag = RepositoryConst::JUNII2_DATEOFGRANTED;
		$date = RepositoryOutputFilter::date($strDateofgrant);
		$xml = $this->outputElement($tag, $date);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_DATEOFGRANTED]--;
		}
		return $xml;
	}
	
	/**
	 * degreename output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strDegreename        	
	 * @return string
	 */
	private function outputDegreename($strDegreename){
		if($this->occurs[RepositoryConst::JUNII2_DEGREENAME] < 1){
			return $this->outputDescription($strDegreename);
		}
		$tag = RepositoryConst::JUNII2_DEGREENAME;
		$xml = $this->outputElement($tag, $strDegreename);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_DEGREENAME]--;
		}
		return $xml;
	}
	
	/**
	 * grantor output
	 * minOccurs = 0, maxOccurs = 1
	 *
	 * @param string $strGrantor        	
	 * @return string
	 */
	private function outputGrantor($strGrantor){
		if($this->occurs[RepositoryConst::JUNII2_GRANTOR] < 1){
			return $this->outputDescription($strGrantor);
		}
		$tag = RepositoryConst::JUNII2_GRANTOR;
		$xml = $this->outputElement($tag, $strGrantor);
		if(strlen($xml) > 0){
			$this->occurs[RepositoryConst::JUNII2_GRANTOR]--;
		}
		return $xml;
	}
	// Add JuNii2 ver3 R.Matsuura 2013/09/24 --end--
}
?>
