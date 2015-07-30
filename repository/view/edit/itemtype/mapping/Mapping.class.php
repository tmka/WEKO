<?php
// --------------------------------------------------------------------
//
// $Id: Mapping.class.php 36229 2014-05-26 05:49:55Z satoshi_arata $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// Set help icon setting 2010/02/10 K.Ando --start--
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
// Set help icon setting 2010/02/10 K.Ando --end--

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Itemtype_Mapping extends RepositoryAction 
{
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    // メンバ変数
    var $typeArray = null;				// type選択肢
    var $dublinCoreArray = null;		// 1.Dublin Core
    var $junii2Array = null;			// 2.JuNii2
//  var $junii2ChildArray = null;		// 3.JuNii2(子)=>廃止
    var $lomArray = null;				// 3.LOM
    var $spaseArray = null;			//SPASE
    public $lidoArray = null;   // 4.LIDO
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        
        // Add theme_name for image file Y.Nakao 2011/08/03 --start--
        $this->setThemeName();
        // Add theme_name for image file Y.Nakao 2011/08/03 --end--
        
        //$this->setLangResource();
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        
        // マッピング選択肢を設定メンバに保存
        // ※項目一覧をDBから参照することもあるかもしれないのでDOMではやらない(・・・としておく。)	。
        
        // 0.アイテムタイプ名(type, NIItype)
        $this->setNiitype($this->typeArray);
        
        // 1.Dublin Core
        $this->setDublinCore($this->dublinCoreArray);
        
        // 2.JuNii2
        $this->setJunii2($this->junii2Array);
        
        // Add learning Object Material A.Jin -- start --
        // 3.LOM
        $this->setLom($this->lomArray);
        // Add learning Object Material A.Jin -- end --
        
        // Add LIDO R.Matsuura -- start --
        $this->setLido($this->lidoArray);
        // Add LIDO R.Matsuura -- end --
         
         //SPASE
        $this->setSpase($this->spaseArray);
        
        // 4.表示言語
        $this->disp_lang_array = array(
            // '未設定', 'japanese', 'english'
            // languageリソースから項目を取得する
            array(" ", $smartyAssign->getLang("repository_language_no_mapping")),
            array("japanese", $smartyAssign->getLang("repository_language_ja")),
            array("english", $smartyAssign->getLang("repository_language_en"))
        );
        // Set help icon setting 2010/02/10 K.Ando --start--
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
        if ( $result == false ){
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
        // Set help icon setting 2010/02/10 K.Ando --end--
        
        return 'success';
    }
    
    /**
     * set Nii type
     *
     * @param array $niiTypeCandidateArray
     */
    private function setNiitype(&$niiTypeCandidateArray)
    {
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        
        $niiTypeCandidateArray = array(
        //  '未設定',
        //  'Journal Article','Thesis or Dissertation','Departmental',
        //  'Bulletin Paper','Conference Paper','Presentation','Book',
        //  'Technical Report','Research Paper','Article','Preprint',
        //  'Learning Material','Data or Dataset','Software','Others',
        
            //languageリソースから項目を取得する
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -start-
            "0",
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -end-
            $smartyAssign->getLang("repository_niitype_journal_article"),
            $smartyAssign->getLang("repository_niitype_thesis_or_dissertation"),
            $smartyAssign->getLang("repository_niitype_departmental_bulletin_paper"),
            $smartyAssign->getLang("repository_niitype_conference_paper"),
            $smartyAssign->getLang("repository_niitype_presentation"),
            $smartyAssign->getLang("repository_niitype_book"),
            $smartyAssign->getLang("repository_niitype_technical_report"),
            $smartyAssign->getLang("repository_niitype_research_paper"),
            $smartyAssign->getLang("repository_niitype_article"),
            $smartyAssign->getLang("repository_niitype_preprint"),
            $smartyAssign->getLang("repository_niitype_learning_material"),
            $smartyAssign->getLang("repository_niitype_data_or_dataset"),
            $smartyAssign->getLang("repository_niitype_software"),
            $smartyAssign->getLang("repository_niitype_others")
        );
        return "success";
    }
    
    /**
     * set Dublin Core type
     *
     * @param array $dublinCoreCandidateArray
     */
    private function setDublinCore(&$dublinCoreCandidateArray)
    {
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        
        $dublinCoreCandidateArray = array(
        //  '未設定',
        //  'title', 'creator', 'subject', 'description', 'publisher', 'contributor',
        //  'date', 'format', 'identifier', 'source', 'language',
        //  'Date', 'Type', 'Format', 'Identifier', 'Source', 'Language',   // 2008.02.22 typeはアイテムタイプ名にマッピングされるため、選択肢から削除
        //  'relation', 'coverage', 'rights'
            
            //languageリソースから項目を取得する
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -start-
            "0",
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -end-
            $smartyAssign->getLang("repository_dublin_core_title"),
            $smartyAssign->getLang("repository_dublin_core_creator"),
            $smartyAssign->getLang("repository_dublin_core_subject"),
            $smartyAssign->getLang("repository_dublin_core_description"),
            $smartyAssign->getLang("repository_dublin_core_publisher"),
            $smartyAssign->getLang("repository_dublin_core_contributor"),
            $smartyAssign->getLang("repository_dublin_core_date"),
            $smartyAssign->getLang("repository_dublin_core_type"),
            $smartyAssign->getLang("repository_dublin_core_format"),
            $smartyAssign->getLang("repository_dublin_core_identifier"),
            $smartyAssign->getLang("repository_dublin_core_source"),
            $smartyAssign->getLang("repository_dublin_core_language"),
            $smartyAssign->getLang("repository_dublin_core_relation"),
            $smartyAssign->getLang("repository_dublin_core_coverage"),
            $smartyAssign->getLang("repository_dublin_core_rights")
        );
        return "success";
    }
    
    /**
     * set JuNii2 type
     *
     * @param array $junii2CandidateArray
     */
    private function setJunii2(&$junii2CandidateArray)
    {
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        
        $junii2CandidateArray = array(
        //  '未設定',
        //  'title', 'alternative', 'creator', 'subject', 'NIIsubject', 
        //  'NDC', 'NDLC', 'BSH', 'NDLSH', 'MeSH', 'DDC', 'LCC', 
        //  'UDC', 'LCSH', 'description', 'publisher', 'contributor', 
        //'date', 'type', 'NIItype', 'format', 'identifier',            // 2008.02.22 NIItypeはアイテムタイプ名にマッピングされるため、選択肢から削除
        //  'date', 'type', 'format', 'identifier', 
        //  'URI', 'fullTextURL', 'issn', 'NCID', 'jtitle', 
        //  'volume', 'issue', 'spage', 'epage', 'dateofissued', 
        //  'source', 'language', 'relation', 'pmid', 'doi', 'isVersionOf', 
        //  'hasVersion', 'isReplacedBy', 'replaces', 'isRequiredBy', 
        //  'requires', 'isPartOf', 'hasPart', 'isReferencedBy', 
        //  'references', 'isFormatOf', 'hasFormat', 'coverage', 
        //  'spatial', 'NIIspatial', 'temporal', 'NIItemporal', 
        //  'rights', 'textversion'
            
            //languageリソースから項目を取得する
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -start-
            "0",
            // Mod insert 'undefine' to database in English 2012/02/14 T.Koyasu -end-
            $smartyAssign->getLang("repository_junii2_title"),
            $smartyAssign->getLang("repository_junii2_alternative"),
            $smartyAssign->getLang("repository_junii2_creator"),
            $smartyAssign->getLang("repository_junii2_subject"),
            $smartyAssign->getLang("repository_junii2_nii_subject"),
            $smartyAssign->getLang("repository_junii2_ndc"),
            $smartyAssign->getLang("repository_junii2_ndlc"),
            $smartyAssign->getLang("repository_junii2_bsh"),
            $smartyAssign->getLang("repository_junii2_ndlsh"),
            $smartyAssign->getLang("repository_junii2_mesh"),
            $smartyAssign->getLang("repository_junii2_ddc"),
            $smartyAssign->getLang("repository_junii2_lcc"),
            $smartyAssign->getLang("repository_junii2_udc"),
            $smartyAssign->getLang("repository_junii2_lcsh"),
            $smartyAssign->getLang("repository_junii2_description"),
            $smartyAssign->getLang("repository_junii2_publisher"),
            $smartyAssign->getLang("repository_junii2_contributor"),
            $smartyAssign->getLang("repository_junii2_date"),
            $smartyAssign->getLang("repository_junii2_type"),
            $smartyAssign->getLang("repository_junii2_format"),
            $smartyAssign->getLang("repository_junii2_identifier"),
            $smartyAssign->getLang("repository_junii2_uri"),
            $smartyAssign->getLang("repository_junii2_full_text_url"),
            RepositoryConst::JUNII2_ISBN,
            $smartyAssign->getLang("repository_junii2_issn"),
            $smartyAssign->getLang("repository_junii2_ncid"),
            $smartyAssign->getLang("repository_junii2_jtitle"),
            $smartyAssign->getLang("repository_junii2_volume"),
            $smartyAssign->getLang("repository_junii2_issue"),
            $smartyAssign->getLang("repository_junii2_spage"),
            $smartyAssign->getLang("repository_junii2_epage"),
            $smartyAssign->getLang("repository_junii2_date_of_issued"),
            $smartyAssign->getLang("repository_junii2_source"),
            $smartyAssign->getLang("repository_junii2_language"),
            $smartyAssign->getLang("repository_junii2_relation"),
            $smartyAssign->getLang("repository_junii2_pmid"),
            $smartyAssign->getLang("repository_junii2_doi"),
            RepositoryConst::JUNII2_NAID,
            RepositoryConst::JUNII2_ICHUSHI,
            $smartyAssign->getLang("repository_junii2_is_version_of"),
            $smartyAssign->getLang("repository_junii2_has_version"),
            $smartyAssign->getLang("repository_junii2_is_replaced_by"),
            $smartyAssign->getLang("repository_junii2_replaces"),
            $smartyAssign->getLang("repository_junii2_is_required_by"),
            $smartyAssign->getLang("repository_junii2_requires"),
            $smartyAssign->getLang("repository_junii2_is_part_of"),
            $smartyAssign->getLang("repository_junii2_has_part"),
            $smartyAssign->getLang("repository_junii2_is_referenced_by"),
            $smartyAssign->getLang("repository_junii2_references"),
            $smartyAssign->getLang("repository_junii2_is_format_of"),
            $smartyAssign->getLang("repository_junii2_has_format"),
            $smartyAssign->getLang("repository_junii2_coverage"),
            $smartyAssign->getLang("repository_junii2_spatial"),
            $smartyAssign->getLang("repository_junii2_nii_spatial"),
            $smartyAssign->getLang("repository_junii2_temporal"),
            $smartyAssign->getLang("repository_junii2_nii_temporal"),
            $smartyAssign->getLang("repository_junii2_rights"),
            $smartyAssign->getLang("repository_junii2_textversion"),
            RepositoryConst::JUNII2_GRANTID,
            RepositoryConst::JUNII2_DATEOFGRANTED,
            RepositoryConst::JUNII2_DEGREENAME,
            RepositoryConst::JUNII2_GRANTOR
        );
        return "success";
    }
    
    /**
     * set Learning Object Material
     *
     * @param array $lomCandidateArray
     */
    private function setLom(&$lomCandidateArray)
    {
        $lomCandidateArray = array(
            //  '未設定',
            //languageリソースから項目を取得する
            "0",
            RepositoryConst::LOM_MAP_GNRL_IDENTIFER,
            RepositoryConst::LOM_MAP_GNRL_TITLE,
            RepositoryConst::LOM_MAP_GNRL_LANGUAGE,
            RepositoryConst::LOM_MAP_GNRL_DESCRIPTION,
            RepositoryConst::LOM_MAP_GNRL_KEYWORD,
            RepositoryConst::LOM_MAP_GNRL_COVERAGE,
            RepositoryConst::LOM_MAP_GNRL_STRUCTURE,
            RepositoryConst::LOM_MAP_GNRL_AGGREGATION_LEVEL,
            RepositoryConst::LOM_MAP_LFCYCL_VERSION,
            RepositoryConst::LOM_MAP_LFCYCL_STATUS,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_AUTHOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISHER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_PUBLISH_DATE,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_UNKNOWN,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_INITIATOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_TERMINATOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_VALIDATOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_EDITOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_GRAPHICAL_DESIGNER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_TECHNICAL_IMPLEMENTER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_CONTENT_PROVIDER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_TECHNICAL_VALIDATOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_EDUCATIONAL_VALIDATOR,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_SCRIPT_WRITER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_INSTRUCTIONAL_DESIGNER,
            RepositoryConst::LOM_MAP_LFCYCL_CONTRIBUTE_SUBJECT_MATTER_EXPERT,
            RepositoryConst::LOM_MAP_MTMTDT_IDENTIFER,
            RepositoryConst::LOM_MAP_MTMTDT_CONTRIBUTE,
            RepositoryConst::LOM_MAP_MTMTDT_CONTRIBUTE_CREATOR,
            RepositoryConst::LOM_MAP_MTMTDT_CONTRIBUTE_VALIDATOR,
            RepositoryConst::LOM_MAP_MTMTDT_METADATA_SCHEMA,
            RepositoryConst::LOM_MAP_MTMTDT_LANGUAGE,
            RepositoryConst::LOM_MAP_TCHNCL_FORMAT,
            RepositoryConst::LOM_MAP_TCHNCL_SIZE,
            RepositoryConst::LOM_MAP_TCHNCL_LOCATION,
            RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_TYPE,
            RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_NAME,
            RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MINIMUM_VERSION,
            RepositoryConst::LOM_MAP_TCHNCL_REQIREMENT_ORCOMPOSITE_MAXIMUM_VERSION,
            RepositoryConst::LOM_MAP_TCHNCL_INSTALLATION_REMARKS,
            RepositoryConst::LOM_MAP_TCHNCL_OTHER_PLATFORM_REQUIREMENTS,
            RepositoryConst::LOM_MAP_TCHNCL_DURATION,
            RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_TYPE,
            RepositoryConst::LOM_MAP_EDUCTNL_LEARNING_RESOURCE_TYPE,
            RepositoryConst::LOM_MAP_EDUCTNL_INTERACTIVITY_LEVEL,
            RepositoryConst::LOM_MAP_EDUCTNL_SEMANTIC_DENSITY,
            RepositoryConst::LOM_MAP_EDUCTNL_INTENDED_END_USER_ROLE,
            RepositoryConst::LOM_MAP_EDUCTNL_CONTEXT,
            RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_AGE_RANGE,
            RepositoryConst::LOM_MAP_EDUCTNL_DIFFICULTY,
            RepositoryConst::LOM_MAP_EDUCTNL_TYPICAL_LEARNING_TIME,
            RepositoryConst::LOM_MAP_EDUCTNL_DESCRIPTION,
            RepositoryConst::LOM_MAP_EDUCTNL_LANGUAGE,
            RepositoryConst::LOM_MAP_RLTN,
            RepositoryConst::LOM_MAP_RLTN_IS_PART_OF,
            RepositoryConst::LOM_MAP_RLTN_HAS_PART_OF,
            RepositoryConst::LOM_MAP_RLTN_IS_VERSION_OF,
            RepositoryConst::LOM_MAP_RLTN_HAS_VERSION,
            RepositoryConst::LOM_MAP_RLTN_IS_FORMAT_OF,
            RepositoryConst::LOM_MAP_RLTN_HAS_FORMAT,
            RepositoryConst::LOM_MAP_RLTN_REFERENCES,
            RepositoryConst::LOM_MAP_RLTN_IS_REFERENCED_BY,
            RepositoryConst::LOM_MAP_RLTN_IS_BASED_ON,
            RepositoryConst::LOM_MAP_RLTN_IS_BASIS_FOR,
            RepositoryConst::LOM_MAP_RLTN_REQUIRES,
            RepositoryConst::LOM_MAP_RLTN_IS_REQUIRED_BY,
            RepositoryConst::LOM_MAP_RGHTS_COST,
            RepositoryConst::LOM_MAP_RGHTS_COPYRIGHT_AND_OTHER_RESTRICTIONS,
            RepositoryConst::LOM_MAP_RGHTS_DESCRIPTION,
            RepositoryConst::LOM_MAP_ANNTTN_ENTITY,
            RepositoryConst::LOM_MAP_ANNTTN_DATE,
            RepositoryConst::LOM_MAP_ANNTTN_DESCRIPTION,
            RepositoryConst::LOM_MAP_CLSSFCTN_PURPOSE,
            RepositoryConst::LOM_MAP_CLSSFCTN_DESCRIPTION,
            RepositoryConst::LOM_MAP_CLSSFCTN_KEYWORD,
            RepositoryConst::LOM_MAP_CLSSFCTN_TAXON_PATH_SOURCE,
            RepositoryConst::LOM_MAP_CLSSFCTN_TAXON
        );
        return "success";
    }
    
    /**
     * set LIDO
     *
     * @param array $lomCandidateArray
     */
    private function setLido(&$lidoCandidateArray)
    {
        $lidoCandidateArray = array(
                "0", // 未設定
                array('displayName' => RepositoryConst::LIDO_TAG_LIDO_REC_ID, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_CLASSIFICATION_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE.".".RepositoryConst::LIDO_TAG_CONCEPT_ID, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_WORK_TYPE.".".RepositoryConst::LIDO_TAG_TERM, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION.".".RepositoryConst::LIDO_TAG_CONCEPT_ID, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_CLASSIFICATION_WRAP.".".RepositoryConst::LIDO_TAG_CLASSIFICATION.".".RepositoryConst::LIDO_TAG_TERM, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_IDENTIFICATION_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_TITLE_WRAP.".".RepositoryConst::LIDO_TAG_TITLE_SET.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_INSCRIPTIONS_WRAP.".".RepositoryConst::LIDO_TAG_INSCRIPTIONS.".".RepositoryConst::LIDO_TAG_INSCRIPTION_TRANSCRIPTION, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_REPOSITORY_NAME.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_REPOSITORY_NAME.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_WEB_LINK, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_REPOSITORY_WRAP.".".RepositoryConst::LIDO_TAG_REPOSITORY_SET.".".RepositoryConst::LIDO_TAG_WORK_ID, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_DISPLAY_STATE_EDITION_WRAP.".".RepositoryConst::LIDO_TAG_DISPLAY_STATE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_OBJECT_DESCRIPTION_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_DESCRIPTION_SET.".".RepositoryConst::LIDO_TAG_DESCRIPTIVE_NOTE_VALUE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_OBJECT_MEASUREMENTS_WRAP.".".RepositoryConst::LIDO_TAG_OBJECT_MEASUREMENTS_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_OBJECT_MEASUREMENTS, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_EVENT_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_EVENT, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_TYPE.".".RepositoryConst::LIDO_TAG_TERM, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_ACTOR.".".RepositoryConst::LIDO_TAG_DISPLAY_ACTOR_IN_ROLE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DISPLAY_DATE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DATE.".".RepositoryConst::LIDO_TAG_EARLIEST_DATE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_DATE.".".RepositoryConst::LIDO_TAG_DATE.".".RepositoryConst::LIDO_TAG_LATEST_DATE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_PERIOD_NAME.".".RepositoryConst::LIDO_TAG_TERM, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_PLACE.".".RepositoryConst::LIDO_TAG_DISPLAY_PLACE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_PLACE.".".RepositoryConst::LIDO_TAG_PLACE.".".RepositoryConst::LIDO_TAG_GML, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_EVENT_SET.".".RepositoryConst::LIDO_TAG_EVENT.".".RepositoryConst::LIDO_TAG_EVENT_MATERIALS_TECH.".".RepositoryConst::LIDO_TAG_DISPLAY_MATERIALS_TECH, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_DESCRIPTIVE_METADATA.".".RepositoryConst::LIDO_TAG_OBJECT_RELATION_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_SUBJECT_WRAP.".".RepositoryConst::LIDO_TAG_SUBJECT_SET.".".RepositoryConst::LIDO_TAG_DISPLAY_SUBJECT, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RELATED_WORKS_WRAP.".".RepositoryConst::LIDO_TAG_RELATED_WORK_SET.".".RepositoryConst::LIDO_TAG_RELATED_WORK.".".RepositoryConst::LIDO_TAG_DISPLAY_OBJECT, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RECORD_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_RECORD_ID, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RECORD_TYPE.".".RepositoryConst::LIDO_TAG_TERM, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RECORD_SOURCE.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RECORD_INFO_SET.".".RepositoryConst::LIDO_TAG_RECORD_INFO_LINK, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RECORD_INFO_SET.".".RepositoryConst::LIDO_TAG_RECORD_METADATA_DATE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_ADMINISTRATIVE_METADATA.".".RepositoryConst::LIDO_TAG_RESOURCE_WRAP, 'selectFlag' => 'false'),
                array('displayName' => RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_REPRESENTATION.".".RepositoryConst::LIDO_TAG_LINK_RESOURCE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_DESCRIPTION, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RESOURCE_SOURCE.".".RepositoryConst::LIDO_TAG_LEGAL_BODY_NAME.".".RepositoryConst::LIDO_TAG_APPELLATION_VALUE, 'selectFlag' => 'true'),
                array('displayName' => RepositoryConst::LIDO_TAG_RESOURCE_SET.".".RepositoryConst::LIDO_TAG_RIGHT_RESOURCE.".".RepositoryConst::LIDO_TAG_CREDIT_LINE, 'selectFlag' => 'true')
        );
        
    }
    private function setSpase(&$spaseCandidateArray)
    {
    	$spaseCandidateArray = array(
    			//  '未設定',
    			//languageリソースから項目を取得する
    			"0",
    			  RepositoryConst::SPASE_LANGUAGE,
                RepositoryConst::SPASE_CATALOG_RESOURCEID,
                RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RESOURCENAME,
                RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_RELEASEDATA  ,
                RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_DESCRIPTION   ,
                RepositoryConst::SPASE_CATALOG_RESOURCEHEADER_ACKNOWLEDGEMENT,
                RepositoryConst::SPASE_CATALOG_CONTACT_PERSONID,
                RepositoryConst::SPASE_CATALOG_CONTACT_ROLE,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_REPOSITORYID,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_AVAILABILITY,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSRIGHTS,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_NAME,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_URL,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_ACCESSURL_DESCRIPTION,
                RepositoryConst::SPASE_CATALOG_ACCESSINFORMATION_FORMAT,
                RepositoryConst::SPASE_CATALOG_PHENOMENONTYPE,
                RepositoryConst::SPASE_CATALOG_MEASUREMENTTYPE,
                RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STARTDATE,
                RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_STOPDATE,
                RepositoryConst::SPASE_CATALOG_TEMPORALDESCRIPTION_RELATIVESTOPDATE,
                RepositoryConst::SPASE_CATALOG_OBSERVEDREGION,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_SOUTHERNMOSTLATITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_EASTERNMOSTLATITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_WESTERNMOSTLATITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_UNIT,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MINIMUMALTITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_MAXIMUMALTITUDE,
                RepositoryConst::SPASE_CATALOG_SPATIALCOVERAGE_REFERENCE,
                RepositoryConst::SPASE_CATALOG_PARAMETER_NAME,
                RepositoryConst::SPASE_CATALOG_PARAMETER_DESCRIPTION,
                RepositoryConst::SPASE_CATALOG_PARAMETER_FIELD_FIELDQUANTITY,
                RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLETYPE,
                RepositoryConst::SPASE_CATALOG_PARAMETER_PARTICLE_PARTICLEQUANTITY,
                RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVETYPE,
                RepositoryConst::SPASE_CATALOG_PARAMETER_WAVE_WAVEQUANTITY,
                RepositoryConst::SPASE_CATALOG_PARAMETER_MIXED_MIXEDQUANTITY,
                RepositoryConst::SPASE_CATALOG_PARAMETER_SUPPORT_SUPPORTQUANTITY,
                
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEID,
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RESOURCENAME,
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_RELEASEDATA,
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_DESCRIPTION,
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_PERSONID,
                RepositoryConst::SPASE_INSTRUMENT_RESOURCEHEADER_CONTACT_ROLE,
                RepositoryConst::SPASE_INSTRUMENT_INSTRUMENTTYPE,
                RepositoryConst::SPASE_INSTRUMENT_INVESTIGATIONNAME,
                RepositoryConst::SPASE_INSTRUMENT_OBSEVATORYID,
                
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEID,
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RESOURCENAME,
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_RELEASEDATA,
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_DESCRIPTION,
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_PERSONID,
                RepositoryConst::SPASE_OBSERVATORY_RESOURCEHEADER_CONTACT_ROLE,
                RepositoryConst::SPASE_OBSERVATORY_LOCATION_OBSERVATORYREGION,
                RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LATITUDE,
                RepositoryConst::SPASE_OBSERVATORY_LOCATION_COORDINATESYSTEMNAME_LONGITUDE,
                
                RepositoryConst::SPASE_PERSON_RESOURCEID,
                RepositoryConst::SPASE_PERSON_RELEASEDATE,
                RepositoryConst::SPASE_PERSON_PERSONNAME,
                RepositoryConst::SPASE_PERSON_ORGANIZATIONNAME,
                RepositoryConst::SPASE_PERSON_EMAIL,
                
                RepositoryConst::SPASE_REPOSITORY_RESOURCEID,
                RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RESOURCENAME,
                RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_RELEASEDATA,
                RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_DESCRIPTION,
                RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_PERSONID,
                RepositoryConst::SPASE_REPOSITORY_RESOURCEHEADER_CONTACT_ROLE,
                RepositoryConst::SPASE_REPOSITORY_ACCESSURL_URL,
                
                RepositoryConst::SPASE_GRANULE_RESOURCEID,
                RepositoryConst::SPASE_GRANULE_RELEASEDATA,
                RepositoryConst::SPASE_GRANULE_PARENTID,
                RepositoryConst::SPASE_GRANULE_STARTDATE,
                RepositoryConst::SPASE_GRANULE_STOPDATE,
                RepositoryConst::SPASE_GRANULE_SOURCE_SOURCETYPE,
                RepositoryConst::SPASE_GRANULE_SOURCE_URL,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATESYSTEMNAME,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_COORDINATESYSTEM_COORDINATEREPRESENTATION,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_NORTHERNMOSTLATITUDE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_SOUTHERNMOSTLATITUTE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_EASTERNMOSTLONGITUDE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_WESTERNMOSTLONGTITUDE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_UNIT,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MINIMUMALTITUDE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_MAXIMUMALTITUDE,
                RepositoryConst::SPASE_GRANULE_SPATIALCOVERAGE_REFERENCE
    	);
    	return "success";
    }
}
?>