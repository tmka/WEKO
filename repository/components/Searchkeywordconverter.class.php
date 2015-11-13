<?php
// --------------------------------------------------------------------
//
// $Id: Searchkeywordconverter.class.php 44575 2014-12-01 12:09:36Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR."/modules/repository/components/FW/BusinessBase.class.php";

/**
 * search keyword converter class
 * 
 */
abstract class Repository_Components_Searchkeywordconverter extends BusinessBase
{
    
    /**
     * メタデータ項目情報から渡されたメタデータ値に関して変換
     *
     * @param string $metadata アイテムのメタデータ入力値
     * @param ToSearchKey $metadataInfo メタデータ項目情報
     */
    abstract public function toSearchKey($metadata, $metadataInfo);
    
    /**
     * 検索時に入力されたキーワードを検索条件情報を参照して変換
     *
     * @param string $searchKeyword 検索時に入力されたワード
     * @param ToSearchCondition $searchCondition 検索条件情報
     */
    abstract public function toSearchCondition($searchKeyword, $searchCondition);
}

/**
 * to search key class
 * 
 */
class ToSearchKey
{
    /**
     * metadata attribute
     *
     * @var string
     */
    public $itemAttr = null;
    
    /**
     * mapping
     *
     * @var array
     */
    public $mapping = array();
    
    /**
     * metadata option
     *
     * @var array
     */
    public $option = array();
    
    /**
     * metadata language
     *
     * @var string
     */
    public $language = null;
}

/**
 * to search condition class
 * 
 */
class ToSearchCondition
{
    const ALLMETADATA = 1;
    const TITLE = 2;
    const CREATOR = 3;
    const KEYWORD = 4;
    const SUBJECT = 5;
    const DESCRIPTION = 6;
    const PUBLISHER = 7;
    const CONTRIBUTOR = 8;
    const DATE = 9;
    const ITEMTYPE = 10;
    const TYPE = 11;
    const FORMAT = 12;
    const ID = 13;
    const JTITLE = 14;
    const DATEOFISSUED = 15;
    const LANGUAGE = 16;
    const SPATIAL = 17;
    const TEMPORAL = 18;
    const RIGHTS = 19;
    const TEXTVERSION = 20;
    const GRANTID = 21;
    const DATEOFGRANTED = 22;
    const DEGREENAME = 23;
    const GRANTOR = 24;
    
    /**
     * search keyword
     *
     * @var int
     */
    public $detailSearchCondition = null;
    
    /**
     * Junii2 mapping array
     *
     * @var array
     */
    public $Junii2Mapping = array();
}

?>
