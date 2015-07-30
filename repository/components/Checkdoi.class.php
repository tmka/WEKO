<?php
// --------------------------------------------------------------------
//
// $Id: Checkdoi.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class Repository_Components_Checkdoi extends RepositoryLogicBase
{
    const JUNII2_MAPPING_ISBN = RepositoryConst::JUNII2_ISBN;
    const JUNII2_MAPPING_ISSN = RepositoryConst::JUNII2_ISSN;
    const JUNII2_MAPPING_NCID = RepositoryConst::JUNII2_NCID;
    const JUNII2_MAPPING_TITLE = RepositoryConst::JUNII2_TITLE;
    const JUNII2_MAPPING_JTITLE = RepositoryConst::JUNII2_JTITLE;
    const JUNII2_MAPPING_VOLUME = RepositoryConst::JUNII2_VOLUME;
    const JUNII2_MAPPING_ISSUE = RepositoryConst::JUNII2_ISSUE;
    const JUNII2_MAPPING_SPAGE = RepositoryConst::JUNII2_SPAGE;
    const JUNII2_MAPPING_EPAGE = RepositoryConst::JUNII2_EPAGE;
    const JUNII2_MAPPING_DATE_OF_ISSUED = RepositoryConst::JUNII2_DATE_OF_ISSUED;
    const JUNII2_MAPPING_PUBLISHER = RepositoryConst::JUNII2_PUBLISHER;
    const JUNII2_MAPPING_LANGUAGE = RepositoryConst::JUNII2_LANGUAGE;
    const JUNII2_MAPPING_FULL_TEXT_URL = RepositoryConst::JUNII2_FULL_TEXT_URL;
    const JUNII2_MAPPING_BIBLIO = 'jtitle,volume,issue,spage,epage,dateofissued';
    const PARAMETER_REVIEW_FLG = 'review_flg';
    const PARAMETER_ITEM_AUTO_PUBLIC = 'item_auto_public';
    
    const STATUS_FOR_REGISTRATION = 0;
    const STATUS_FOR_MANAGEMENT = 1;
    
    const CHECKING_STATUS_ITEM_REGISTRATION = 0;
    const CHECKING_STATUS_ITEM_MANAGEMENT = 1;
    
    const TYPE_JALC_DOI = 0;
    const TYPE_CROSS_REF = 1;
    const TYPE_LIBRARY_JALC_DOI = 2;
    
    const CAN_GRANT_DOI = 0;
    const CANNOT_GRANT_DOI = 1;
    
    /**
     * call superclass' __construct
     *
     * @param var $session Session
     * @param var $Db Db
     * @param string $transStartDate TransStartDate
     */
    public function __construct($session, $Db, $transStartDate)
    {
        parent::__construct($session, $Db, $transStartDate);
    }
    
    /**
     * check being able to give the item JaLC DOI for Departmental Bulletin Paper
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    public function checkDoiGrant($item_id, $item_no, $type, $status=self::STATUS_FOR_REGISTRATION)
    {
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        $nii_type = $this->getNiiType($item_type_id);
        switch($nii_type)
        {
            case RepositoryConst::NIITYPE_JOURNAL_ARTICLE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_journal_article");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForJournalArticle($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_ARTICLE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_article");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForArticle($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_PREPRINT:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_preprint");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForPreprint($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_departmental_bulletin_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForDepartmentalBulletinPaper($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_thesis_or_dissertation");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForThesisOrDissertation($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_CONFERENCE_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_conference_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForConferencePaper($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_BOOK:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_book");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForBook($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_TECHNICAL_REPORT:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_technical_report");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForTechnicalReport($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_RESEARCH_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_research_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForResearchPaper($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_LEARNING_MATERIAL:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_learning_material");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForLearningMaterial($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_DATA_OR_DATASET:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_data_or_dataset");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForDataOrDataset($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_SOFTWARE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_software");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForSoftware($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_PRESENTATION:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_presentation");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForPresentation($item_id, $item_no, $type, $status);
                break;
            
            case RepositoryConst::NIITYPE_OTHERS:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_others");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiForOthers($item_id, $item_no, $type, $status);
                break;
            
            default:
                return false;
        }
    }
    
    /**
     * check being able to give the item JaLC DOI for Journal Article
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForJournalArticle($item_id, $item_no, $type, $status=0)
    {
        $can_grant = false;
        // アイテムタイプID取得
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        
        // JaLC DOI
        if($type == self::TYPE_JALC_DOI)
        {
            // JaLC DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, false) &&
               $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_SPAGE, false, false) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existJalcdoiPrefix() ) 
            {
                $can_grant = true;
            }
        }
        // Cross Ref
        else if($type == self::TYPE_CROSS_REF)
        {
            // Cross Ref DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, true) &&
               $this->isJournalArticleCrossRefDoiJunii2Required($item_id, $item_no) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existCrossrefPrefix() ) 
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }
    
    /**
     * check being able to give the item JaLC DOI for Article
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForArticle($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForJournalArticle($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Preprint
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForPreprint($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForJournalArticle($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Departmental Bulletin Paper
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForDepartmentalBulletinPaper($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForJournalArticle($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Thesis or Dissertation
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForThesisOrDissertation($item_id, $item_no, $type, $status=0)
    {
        $can_grant = false;
        // アイテムタイプID取得
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // JaLC DOI
        if($type == self::TYPE_JALC_DOI)
        {
           // JaLC DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, false) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existJalcdoiPrefix() ) 
            {
                $can_grant = true;
            }
        }
        // Library JaLC DOI
        else if($type == self::TYPE_LIBRARY_JALC_DOI)
        {
            // JaLC DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, false) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no)) 
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }
    
    /**
     * check being able to give the item JaLC DOI for Conference Paper
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForConferencePaper($item_id, $item_no, $type, $status=0)
    {
        $can_grant = false;
        // アイテムタイプID取得
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // JaLC DOI
        if($type == self::TYPE_JALC_DOI)
        {
            // JaLC DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, false) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existJalcdoiPrefix() ) 
            {
                $can_grant = true;
            }
        }
        // Cross Ref
        else if($type == self::TYPE_CROSS_REF)
        {
            // Cross Ref DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, true) &&
               $this->isBookCrossRefDoiJunii2Required($item_id, $item_no) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existCrossrefPrefix() ) 
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }
    
    /**
     * check being able to give the item JaLC DOI for Book
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForBook($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForConferencePaper($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Technical Report
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForTechnicalReport($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForConferencePaper($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Research Paper
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForResearchPaper($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForConferencePaper($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Learning Material
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForLearningMaterial($item_id, $item_no, $type, $status=0)
    {
        $can_grant = false;
        // アイテムタイプID取得
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // JaLC DOI
        if($type == self::TYPE_JALC_DOI)
        {
            // JaLC DOI付与可能アイテムである条件を満たす
            if($this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, 
                   self::JUNII2_MAPPING_FULL_TEXT_URL, false, false) &&
               $this->existJunii2TitleMetadata($item_id, $item_no, false) &&
               $this->canOutputOaipmh($item_id, $item_no, $status) &&
               $this->isNotEnteredDoi($item_id, $item_no) &&
               $this->existYHandleAndUri($item_id, $item_no) &&
               $this->existJalcdoiPrefix() ) 
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }
    
    /**
     * check being able to give the item JaLC DOI for Data or Dataset
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForDataOrDataset($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForLearningMaterial($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Software
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForSoftware($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForLearningMaterial($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Presentation
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForPresentation($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForLearningMaterial($item_id, $item_no, $type, $status);
    }
    
    /**
     * check being able to give the item JaLC DOI for Others
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $type   0:JaLC DOI, 1:Cross Ref, 2:国会図書館JaLC DOI
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function checkDoiForOthers($item_id, $item_no, $type, $status=0)
    {
        return $this->checkDoiForLearningMaterial($item_id, $item_no, $type, $status);
    }
    
    /**
     * アイテムのNii Typeを取得する
     *
     * @param int $item_type_id
     * @return string
     */
    private function getNiiType($item_type_id)
    {
        $nii_type = '';
        $query = "SELECT mapping_info ".
                 "FROM ".DATABASE_PREFIX."repository_item_type ".
                 "WHERE item_type_id = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_type_id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(isset($result[0]['mapping_info']))
        {
            $nii_type = $result[0]['mapping_info'];
        }
        return $nii_type;
    }
    
    /**
     * アイテムのitem_type_idを取得する
     *
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    private function getItemTypeId($item_id, $item_no)
    {
        $query = "SELECT item_type_id ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        $item_type_id = 0;
        if(isset($result[0]['item_type_id']))
        {
            $item_type_id = $result[0]['item_type_id'];
        }
        return $item_type_id;
    }
    
    /**
     * ファイル付アイテムであるか
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function hasFiles($item_id, $item_no)
    {
        $query = "SELECT item_id, item_no, attribute_id ".
                 "FROM ".DATABASE_PREFIX."repository_file ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        $is_having_file = false;
        if(count($result) > 0)
        {
            $is_having_file = true;
        }
        return $is_having_file;
    }
    
    /**
     * JuNii2メタデータ項目の<NCID>, <jtitle>, <publisher>が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isDoiJunii2Required($item_id, $item_no)
    {
        $is_having = false;
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // <NCID>に値が入力されているか
        $existNcid = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_NCID);
        // <jtitle>に値が入力されているか
        $existJtitle = $this->existJunii2JtitleMetadata($item_id, $item_no, $item_type_id);
        // <publisher>に値が入力されているか
        $existPublisher = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_PUBLISHER);
        // JuNii2メタデータ項目の<NCID>, <jtitle>, <publisher>が入力されている
        if($existNcid && $existJtitle && $existPublisher)
        {
            $is_having = true;
        }
        return $is_having;
    }
    
    /**
     * ジャーナルアーティクルのCrossRef付与に必要なJuNii2メタデータ項目が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isJournalArticleCrossRefDoiJunii2Required($item_id, $item_no)
    {
        $is_having = false;
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // <publisher>に値が入力されているか
        $existPublisher = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_PUBLISHER, true, true);
        // <jtitle>に値が入力されているか
        $existJtitle = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_JTITLE, true, true);
        // <ISSN>に値が入力されているか
        $existIssn = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_ISSN, true, false);
        // <spage>に値が入力されているか
        $existSpage = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_SPAGE, false, false);
        // <language>に値が入力されているか
        $existLanguage = $this->existJunii2LanguageMetadata($item_id, $item_no, $item_type_id, true);
        // JuNii2メタデータ項目の<publisher>, <jtitle>, <ISSN>, <spage>, <language>が入力されている
        if($existPublisher && $existJtitle && $existIssn && $existSpage && $existLanguage)
        {
            $is_having = true;
        }
        return $is_having;
    }
    
    /**
     * 書籍のCrossRef付与に必要なJuNii2メタデータ項目が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isBookCrossRefDoiJunii2Required($item_id, $item_no)
    {
        $is_having = false;
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        // <publisher>に値が入力されているか
        $existPublisher = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_PUBLISHER, true, true);
        // <ISBN>に値が入力されているか
        $existIsbn = $this->existJunii2MappingMetadata($item_id, $item_no, $item_type_id, self::JUNII2_MAPPING_ISBN, true, false);
        // <language>に値が入力されているか
        $existLanguage = $this->existJunii2LanguageMetadata($item_id, $item_no, $item_type_id, true);
        // JuNii2メタデータ項目の<publisher>, <ISBN>, <language>が入力されている
        if($existPublisher && $existIsbn && $existLanguage)
        {
            $is_having = true;
        }
        return $is_having;
    }
    
    /**
     * 指定したJuNii2マッピングの属性IDを取得する
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $item_type_id
     * @param string $mapping
     * @param bool $check_lang
     * @return array
     */
    private function getAttributeIdFromJuNii2Mapping($item_type_id, $mapping)
    {
        // アイテムタイプ中の指定したマッピングの属性IDを取得する
        $query = "SELECT attribute_id, display_lang_type ".
                 "FROM ".DATABASE_PREFIX."repository_item_attr_type ".
                 "WHERE item_type_id = ? ".
                 "AND junii2_mapping = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_type_id;
        $params[] = $mapping;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        return $result;
    }
    
    /**
     * JuNii2メタデータ項目の書誌情報の雑誌名が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $item_type_id
     * @param string $mapping
     * @return bool
     */
    private function existJunii2JtitleMetadata($item_id, $item_no, $item_type_id)
    {
        $exist_data = false;
        // 指定した属性IDの属性値を取得する
        $query = "SELECT biblio_name, biblio_name_english ".
                 "FROM ".DATABASE_PREFIX."repository_biblio_info ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        // 指定した属性IDのデータがある
        for($cnt = 0; $cnt < count($result); $cnt++)
        {
            // 属性値に値がある
            if((isset($result[$cnt]['biblio_name']) && count($result[$cnt]['biblio_name']) > 0) ||
               (isset($result[$cnt]['biblio_name_english']) && count($result[$cnt]['biblio_name_english']) > 0))
            {
                $exist_data = true;
                break;
            }
        }
        return $exist_data;
    }
    
    /**
     * JuNii2メタデータ項目のタイトルが入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function existJunii2TitleMetadata($item_id, $item_no, $check_lang)
    {
        $exist_data = false;
        // 指定した属性IDの属性値を取得する
        $query = "SELECT title, title_english, language ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        // タイトル、またはタイトル(英)に値がある
        if(count($result) != 0 &&
           (strlen($result[0]['title']) !== 0 || strlen($result[0]['title_english']) !== 0))
        {
            $exist_data = true;
        }
        
        // 言語判定をする場合、言語がenである時true
        if($check_lang && $result[0]['language'] !== "en")
        {
            $exist_data = false;
        }
        
        return $exist_data;
    }
    
    /**
     * JuNii2メタデータ項目の言語に値"eng"が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $item_type_id
     * @param string $mapping
     * @return bool
     */
    private function existJunii2LanguageMetadata($item_id, $item_no, $item_type_id, $check_num)
    {
        $num = 0;
        $exist_data = false;
        // アイテムタイプ中の指定したマッピングの属性IDを取得する
        $attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, self::JUNII2_MAPPING_LANGUAGE);
        
        // アイテムタイプ中に指定したマッピングのデータがある
        if(count($attr_id_array) > 0)
        {
            // 指定した属性IDの属性値を取得する
            $query = "SELECT attribute_value ".
                     "FROM ".DATABASE_PREFIX."repository_item_attr ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                // 属性値に値がある
                if(isset($result[$cnt]['attribute_value']) && 
                   strlen($result[$cnt]['attribute_value']) > 0)
                {
                    if($result[$cnt]['attribute_value'] === "eng")
                    {
                        $exist_data = true;
                    }
                    $num++;
                }
            }
        }
        
        // 登録数を判定する場合、1の時のみtrue
        if($check_num && $num != 1)
        {
            $exist_data = false;
        }
        
        return $exist_data;
    }
    
    /**
     * JuNii2メタデータ項目のマッピングが第4引数である値が入力されているか
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $item_type_id
     * @param string $mapping
     * @return bool
     */
    private function existJunii2MappingMetadata($item_id, $item_no, $item_type_id, $mapping, $check_num, $check_lang)
    {
        $num = 0;
        $exist_data = false;
        // アイテムタイプ中の指定したマッピングの属性IDを取得する
        $attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, $mapping);
        // アイテムタイプ中に指定したマッピングのデータがある
        if(count($attr_id_array) > 0)
        {
            // 指定した属性IDの属性値を取得する
            $query = "SELECT attribute_value, attribute_id ".
                     "FROM ".DATABASE_PREFIX."repository_item_attr ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                // 属性値に値がある
                if(isset($result[$cnt]['attribute_value']) && 
                   strlen($result[$cnt]['attribute_value']) > 0 && 
                   $result[$cnt]['attribute_value'] !== "|||" && 
                   $result[$cnt]['attribute_value'] !== "&EMPTY&")
                {
                    for($cnt_attr_id_array = 0; $cnt_attr_id_array < count($attr_id_array); $cnt_attr_id_array++)
                    {
                        if($attr_id_array[$cnt_attr_id_array]['attribute_id'] == $result[$cnt]['attribute_id'])
                        {
                            if(!$check_lang || $attr_id_array[$cnt_attr_id_array]['display_lang_type'] === "english")
                            {
                                $exist_data = true;
                            }
                        }
                    }
                    $num++;
                }
            }
            
            // 指定した属性IDの氏名情報を取得する
            $query = "SELECT family, name, attribute_id ".
                     "FROM ".DATABASE_PREFIX."repository_personal_name ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                // 属性値に値がある
                if((isset($result[$cnt]['family']) && 
                   strlen($result[$cnt]['family']) > 0) || 
                   (isset($result[$cnt]['name']) && 
                   strlen($result[$cnt]['name']) > 0))
                {
                    for($cnt_attr_id_array = 0; $cnt_attr_id_array < count($attr_id_array); $cnt_attr_id_array++)
                    {
                        if($attr_id_array[$cnt_attr_id_array]['attribute_id'] == $result[$cnt]['attribute_id'])
                        {
                            if(!$check_lang || $attr_id_array[$cnt_attr_id_array]['display_lang_type'] === "english")
                            {
                                $exist_data = true;
                            }
                        }
                    }
                    $num++;
                }
            }
            
            // 指定した属性IDのファイル情報を取得する
            $query = "SELECT file_no ".
                     "FROM ".DATABASE_PREFIX."repository_file ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                if(isset($result[$cnt]['file_no']) && strlen($result[$cnt]['file_no']) > 0)
                {
                    if(!$check_lang)
                    {
                        $exist_data = true;
                    }
                    $num++;
                }
            }
            
             // 指定した属性IDのサムネイル情報を取得する
            $query = "SELECT file_no ".
                     "FROM ".DATABASE_PREFIX."repository_thumbnail ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                if(isset($result[$cnt]['file_no']) && strlen($result[$cnt]['file_no']) > 0)
                {
                    if(!$check_lang)
                    {
                        $exist_data = true;
                    }
                    $num++;
                }
            }
            
       }
       
        // アイテムタイプ中の書誌情報の属性IDを取得する
        $attr_id_biblio_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
            self::JUNII2_MAPPING_BIBLIO);
        
        // アイテムタイプ中に書誌情報のデータがある
        if(count($attr_id_biblio_array) > 0)
        {
            // 指定した属性IDの書誌情報を取得する
            $query = "SELECT biblio_name, biblio_name_english, volume, issue, start_page, end_page, date_of_issued ".
                     "FROM ".DATABASE_PREFIX."repository_biblio_info ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND attribute_id IN (";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            for($cnt = 0; $cnt < count($attr_id_biblio_array); $cnt++)
            {
                if($cnt > 0)
                {
                    $query .= ", ";
                }
                $query .= "?";
                $params[] = $attr_id_biblio_array[$cnt]['attribute_id'];
            }
            $query .= ") ".
                      "AND is_delete = ? ;";
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            // 指定した属性IDのデータがある
            for($cnt = 0; $cnt < count($result); $cnt++)
            {
                switch($mapping)
                {
                    case self::JUNII2_MAPPING_JTITLE:
                        // 属性値に値がある
                        if(isset($result[$cnt]['biblio_name']) && 
                           strlen($result[$cnt]['biblio_name']) > 0)
                        {
                            if(!$check_lang)
                            {
                                $exist_data = true;
                            }
                            $num++;
                        }
                        if(isset($result[$cnt]['biblio_name_english']) && 
                           strlen($result[$cnt]['biblio_name_english']) > 0)
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                    case self::JUNII2_MAPPING_VOLUME:
                        // 属性値に値がある
                        if((isset($result[$cnt]['volume']) && 
                           strlen($result[$cnt]['volume']) > 0))
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                    case self::JUNII2_MAPPING_ISSUE:
                        // 属性値に値がある
                        if((isset($result[$cnt]['issue']) && 
                           strlen($result[$cnt]['issue']) > 0))
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                    case self::JUNII2_MAPPING_SPAGE:
                        // 属性値に値がある
                        if((isset($result[$cnt]['start_page']) && 
                           strlen($result[$cnt]['start_page']) > 0))
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                    case self::JUNII2_MAPPING_EPAGE:
                        // 属性値に値がある
                        if((isset($result[$cnt]['end_page']) && 
                           strlen($result[$cnt]['end_page']) > 0))
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                    case self::JUNII2_MAPPING_DATE_OF_ISSUED:
                        // 属性値に値がある
                        if((isset($result[$cnt]['date_of_issued']) && 
                           strlen($result[$cnt]['date_of_issued']) > 0))
                        {
                            $exist_data = true;
                            $num++;
                        }
                        break;
                }
            }
       }
       
       // 登録数を調べる場合、登録数が1の時のみtrue
       if($check_num && $num != 1)
       {
            $exist_data = false;
       }
       
        return $exist_data;
    }
    
    /**
     * OAI-PMH出力可能であるか(公開アイテムであるかつハーベスト公開がONであるかつJuNii2の必須項目がそろっているか)
     *
     * @param int $item_id
     * @param int $item_no
     * @param int $status 0:登録・編集, 1:アイテム管理
     * @return bool
     */
    private function canOutputOaipmh($item_id, $item_no, $status=0)
    {
        // ハーベスト公開がONであり、JuNii2形式で出力することができ、
        // 公開アイテムでありかつ削除されていないこととする
        $exist_nii_type = $this->checksNiiType($item_id, $item_no);
        $is_public_item = false;
        if($status == self::CHECKING_STATUS_ITEM_MANAGEMENT)
        {
            // アイテム管理
            $is_public_item = $this->isPublicItemForManagement($item_id, $item_no);
        }
        else
        {
            // 登録・編集
            $is_public_item = $this->isPublicItemForRegistration($item_id, $item_no);
        }
        
        // 所属インデックスを取得
        $result = $this->getPositionIndexId($item_id, $item_no);
        // 所属インデックスの内少なくとも一つが、公開インデックスかつハーベスト公開ONであるか
        $index_pulic_flag = false;
        for($cnt = 0; $cnt < count($result); $cnt++)
        {
            $index_pulic_flag = $this->isHarvestPublicIndex($result[$cnt]['index_id']);
            if($index_pulic_flag)
            {
                break;
            }
        }
        
        // NII typeがある、かつ公開アイテムである、かつ公開インデックス(ハーベスト公開)である
        if($exist_nii_type && $is_public_item && $index_pulic_flag)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * 公開アイテムである、かつ削除されていないことを知らべる
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isPublicItemForManagement($item_id, $item_no)
    {
        $query = "SELECT item_id, title, title_english ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND review_status = ? ".
                 "AND shown_status = ? ".
                 "AND shown_date <= ? ".
                 "AND reject_status <= ? ".
                 "AND uri = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 1;
        $params[] = 1;
        $params[] = $this->transStartDate;
        $params[] = 0;
        $params[] = BASE_URL."/?action=repository_uri&item_id=".$item_id;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) > 0 && (strlen($result[0]['title']) > 0 || strlen($result[0]['title_english']) > 0) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * 公開アイテムである、かつ削除されていないことを知らべる
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isPublicItemForRegistration($item_id, $item_no)
    {
        $is_public_item = false;
        $query = "SELECT item_id, title, title_english ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND shown_date <= ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = $this->transStartDate;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        $review_flg = "";
        $item_auto_public = "";
        $error_msg = "";
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->dbAccess->getDb();
        $repositoryAction->getAdminParam(self::PARAMETER_REVIEW_FLG, $review_flg, $error_msg);
        $repositoryAction->getAdminParam(self::PARAMETER_ITEM_AUTO_PUBLIC, $item_auto_public, $error_msg);
        
        if(count($result) === 1 && $review_flg == '0' && $item_auto_public == '1' &&
           (strlen($result[0]['title']) > 0 || strlen($result[0]['title_english']) > 0) )
        {
            $is_public_item = true;
        }
        return $is_public_item;
    }
    
    /**
     * 公開インデックスであるかつハーベスト公開であるインデックスであるかどうか調べる
     * 
     * @param int $index_id
     * @return bool
     */
    public function isHarvestPublicIndex($index_id)
    {
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
        $repositoryAction = new RepositoryAction();
        $repositoryAction->setConfigAuthority();
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        $publicIndex = $indexAuthorityManager->getPublicIndex(true, $repositoryAction->repository_admin_base, $repositoryAction->repository_admin_room, $index_id);
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
        
        if(count($publicIndex) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Nii typeに正しい値が入力されているかをチェックする
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function checksNiiType($item_id, $item_no)
    {
        // NII typeを取得
        $item_type_id = $this->getItemTypeId($item_id, $item_no);
        $nii_type = $this->getNiiType($item_type_id);
        // NII typeの値をチェック
        if($nii_type === RepositoryConst::NIITYPE_JOURNAL_ARTICLE ||
           $nii_type === RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION ||
           $nii_type === RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER ||
           $nii_type === RepositoryConst::NIITYPE_CONFERENCE_PAPER ||
           $nii_type === RepositoryConst::NIITYPE_PRESENTATION ||
           $nii_type === RepositoryConst::NIITYPE_BOOK ||
           $nii_type === RepositoryConst::NIITYPE_TECHNICAL_REPORT ||
           $nii_type === RepositoryConst::NIITYPE_RESEARCH_PAPER ||
           $nii_type === RepositoryConst::NIITYPE_ARTICLE ||
           $nii_type === RepositoryConst::NIITYPE_PREPRINT ||
           $nii_type === RepositoryConst::NIITYPE_LEARNING_MATERIAL ||
           $nii_type === RepositoryConst::NIITYPE_DATA_OR_DATASET ||
           $nii_type === RepositoryConst::NIITYPE_SOFTWARE ||
           $nii_type === RepositoryConst::NIITYPE_OTHERS)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * JaLC DOIが登録されていない(未設定)か
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function isNotEnteredDoi($item_id, $item_no)
    {
        $is_not_entered = false;
        $status = $this->getDoiStatus($item_id, $item_no);
        if($status <= 0)
        {
            $is_not_entered = true;
        }
        return $is_not_entered;
    }
    
    /**
     * JaLC DOI付与状態取得
     *
     * @param int $item_id
     * @param int $item_no
     * @return string
     */
    public function getDoiStatus($item_id, $item_no)
    {
        $query = "SELECT status ".
                 "FROM ".DATABASE_PREFIX."repository_doi_status ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        $doi_status = 0;
        if(count($result) === 0 || (isset($result[0]) && $result[0]['status'] == 0))
        {
            $doi_status = 0;
        }
        else
        {
            $doi_status = $result[0]['status'];
        }
        return $doi_status;
    }
    
    /**
     * 所属するインデックスのインデックスIDを取得する
     *
     * @param int $item_id
     * @param int $item_no
     * @return array
     */
    private function getPositionIndexId($item_id, $item_no)
    {
        // 所属インデックスを取得
        $query = "SELECT index_id ".
                 "FROM ".DATABASE_PREFIX."repository_position_index ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) < 1)
        {
            $result = array();
        }
        return $result;
    }
    
    /**
     * アイテムにYハンドルのサフィックスが登録されているかを調べる
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    private function existYHandleAndUri($item_id, $item_no)
    {
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->transStartDate);
        $suffix = $repositoryHandleManager->getYHandleSuffix($item_id, $item_no);
        
        if(!isset($suffix) || strlen($suffix) < 1)
        {
            return false;
        }
        
        $uri = $repositoryHandleManager->createUriForJuNii2($item_id, $item_no);
        if(!isset($uri) || strlen($uri) < 1)
        {
            return false;
        }
        return true;
    }
    
    /**
     * JaLC DOIのプレフィックスが登録されているかをチェックする
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    public function existJalcdoiPrefix()
    {
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->transStartDate);
        $prefix = $repositoryHandleManager->getJalcDoiPrefix();
        if(isset($prefix) && strlen($prefix) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Cross Refのプレフィックスが登録されているかをチェックする
     *
     * @param int $item_id
     * @param int $item_no
     * @return bool
     */
    public function existCrossrefPrefix()
    {
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->transStartDate);
        $prefix = $repositoryHandleManager->getCrossRefPrefix();
        if(isset($prefix) && strlen($prefix) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * DOI付与可能になり得るアイテムタイプであるかを調べる
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    public function checkDoiGrantItemtype($item_type_id, $type)
    {
        $nii_type = $this->getNiiType($item_type_id);
        switch($nii_type)
        {
            case RepositoryConst::NIITYPE_JOURNAL_ARTICLE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_journal_article");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForJournalArticle($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_ARTICLE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_article");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForArticle($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_PREPRINT:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_preprint");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForPreprint($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_DEPARTMENTAL_BULLETIN_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_departmental_bulletin_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForDepartmentalBulletinPaper($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_THESIS_OR_DISSERTATION:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_thesis_or_dissertation");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_CONFERENCE_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_conference_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForConferencePaper($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_BOOK:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_book");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForBook($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_TECHNICAL_REPORT:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_technical_report");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForTechnicalReport($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_RESEARCH_PAPER:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_research_paper");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForResearchPaper($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_LEARNING_MATERIAL:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_learning_material");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForLearningMaterial($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_DATA_OR_DATASET:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_data_or_dataset");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForDataOrDataset($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_SOFTWARE:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_software");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForSoftware($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_PRESENTATION:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_presentation");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForPresentation($item_type_id, $type);
                break;
            
            case RepositoryConst::NIITYPE_OTHERS:
                $editdoi_flag = $this->getNiiTypeFlag("edit_doi_flag_others");
                if($editdoi_flag == 0)
                {
                    return false;
                }
                return $this->checkDoiGrantItemtypeForOthers($item_type_id, $type);
                break;
            
            default:
                return false;
        }
    }
    
    /**
     * アイテムタイプのNII typeがJournal Articleである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForJournalArticle($item_type_id, $type)
    {
        $can_grant = false;
        if($type == self::TYPE_JALC_DOI)
        {
            $isExistJalcdoiPrefix = $this->existJalcdoiPrefix();
            $spage_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_SPAGE);
            $biblio_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_BIBLIO);
            
            if($isExistJalcdoiPrefix &&
               (count($spage_attr_id_array) > 0 || 
               count($biblio_attr_id_array) > 0))
            {
                $can_grant = true;
            }
        }
        else if($type == self::TYPE_CROSS_REF)
        {
            $isExistCrossrefPrefix = $this->existCrossrefPrefix();
            $publisher_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_PUBLISHER);
            $jtitle_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_JTITLE);
            $issn_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_ISSN);
            $spage_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_SPAGE);
            $language_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_LANGUAGE);
            $biblio_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_BIBLIO);
            
            if($isExistCrossrefPrefix && 
               count($publisher_attr_id_array) > 0 && 
               (count($jtitle_attr_id_array) > 0 || 
               count($biblio_attr_id_array) > 0) && 
               count($issn_attr_id_array) > 0 && 
               (count($spage_attr_id_array) > 0 || 
               count($biblio_attr_id_array) > 0) && 
               count($language_attr_id_array) > 0)
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }

    /**
     * アイテムタイプのNII typeがArticleである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForArticle($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForJournalArticle($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがPreprintである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForPreprint($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForJournalArticle($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがDepartmental Bulletin Paperである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForDepartmentalBulletinPaper($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForJournalArticle($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがThesis or Dissertationである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type)
    {
        $can_grant = false;
        if($type == self::TYPE_JALC_DOI)
        {
            $isExistJalcdoiPrefix = $this->existJalcdoiPrefix();
            
            if($isExistJalcdoiPrefix)
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }

    /**
     * アイテムタイプのNII typeがConference Paperである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForConferencePaper($item_type_id, $type)
    {
        $can_grant = false;
        if($type == self::TYPE_JALC_DOI)
        {
            $isExistJalcdoiPrefix = $this->existJalcdoiPrefix();
            
            if($isExistJalcdoiPrefix)
            {
                $can_grant = true;
            }
        }
        else if($type == self::TYPE_CROSS_REF)
        {
            $isExistCrossrefPrefix = $this->existCrossrefPrefix();
            $publisher_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_PUBLISHER);
            $isbn_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_ISBN);
            $language_attr_id_array = $this->getAttributeIdFromJuNii2Mapping($item_type_id, 
                self::JUNII2_MAPPING_LANGUAGE);
            
            if($isExistCrossrefPrefix && 
               count($publisher_attr_id_array) > 0 && 
               count($isbn_attr_id_array) > 0 && 
               count($language_attr_id_array) > 0)
            {
                $can_grant = true;
            }
        }
        return $can_grant;
    }

    /**
     * アイテムタイプのNII typeがBookである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForBook($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForConferencePaper($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがTechnical Reportである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForTechnicalReport($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForConferencePaper($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがResearch Paperである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForResearchPaper($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForConferencePaper($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがLearning Materialである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForLearningMaterial($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがData or Datasetである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForDataOrDataset($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがSoftwareである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForSoftware($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがPresentationである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForPresentation($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
    }

    /**
     * アイテムタイプのNII typeがOthersである時、必要なマッピングのメタデータが存在するか
     *
     * @param int $item_type_id
     * @param int $type   0:JaLC DOI, 1:Cross Ref
     * @return bool
     */
    private function checkDoiGrantItemtypeForOthers($item_type_id, $type)
    {
        return $this->checkDoiGrantItemtypeForThesisOrDissertation($item_type_id, $type);
    }
    
    /**
     * 指定したNII typeのDOI付与フラグを取得する
     *
     * @param string $record_name
     * @return int
     */
    private function getNiiTypeFlag($record_name)
    {
        $editdoi_flag = 0;
        
        // DOI付与フラグを取得
        $query = "SELECT param_value ".
                 "FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $record_name;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        if(count($result) > 0)
        {
            $editdoi_flag = $result[0]["param_value"];
        }
        
        return $editdoi_flag;
    }
}

?>
