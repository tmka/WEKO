<?php
// --------------------------------------------------------------------
//
// $Id: Twobytechartohalfsizechar.class.php 44575 2014-12-01 12:09:36Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR."/modules/repository/components/Searchkeywordconverter.class.php";

/**
 * Convert Metadata to Half Size Char
 * 
 */
class Repository_Files_Plugin_Searchkeywordconverter_Twobytechartohalfsizechar extends Repository_Components_Searchkeywordconverter
{
    
    /**
     * メタデータ項目情報から渡されたメタデータ値を半角に変換
     *
     * @param string $metadata アイテムのメタデータ入力値
     * @param ToSearchKey $metadataInfo メタデータ項目情報
     */
    public function toSearchKey($metadata, $metadataInfo)
    {
    	return $this->convertToHalfByte($metadata);
    }
    
    /**
     * 検索時に入力されたキーワードを半角に変換
     *
     * @param string $searchKeyword 検索時に入力されたワード
     * @param ToSearchCondition $searchCondition 検索条件情報
     */
    public function toSearchCondition($searchKeyword, $searchCondition)
    {
    	return $this->convertToHalfByte($searchKeyword);
    }
    
    /**
     * 文字列を半角に変換
     *
     * @param string $keyword 文字列
     */
    private function convertToHalfByte($keyword)
    {
    	$keyword = mb_convert_kana($keyword, "ask", "UTF-8");
    	
		// yen mark
		$yen = chr(hexdec('EF')).chr(hexdec('BF')).chr(hexdec('A5'));
		$keyword = str_replace($yen, "\\", $keyword);
		
		// double quotation
		$double_quo_st = chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('9C'));
		$double_quo_en = chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('9D'));
		$keyword = str_replace($double_quo_st, "\"", $keyword);
		$keyword = str_replace($double_quo_en, "\"", $keyword);
		
		// single quotation
		$single_quo_st = chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('98'));
		$single_quo_en = chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('99'));
		$keyword = str_replace($single_quo_st, "'", $keyword);
		$keyword = str_replace($single_quo_en, "'", $keyword);
    	
		// tilde
		$tilde = chr(hexdec('EF')).chr(hexdec('BD')).chr(hexdec('9E'));
		$keyword = str_replace($tilde, "~", $keyword);

    	return $keyword;
    }
}

?>
