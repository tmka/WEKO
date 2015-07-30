<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryImportXmlValidator.class.php 43911 2014-11-13 04:28:03Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class RepositoryImportXmlValidator
{
    
    // Error Type
    const ERROR_NO_XML = "XML is not found";
    const ERROR_PARSE_XML = "Failed to parse XML";
    const ERROR_TAG_NUM = "The number of tags is invalid";
    const ERROR_ITEM_TITLE = "Both title is not input";
    const ERROR_ITEM_SHOWN_DATE = "Shown date format is invalid";
    const ERROR_ITEM_NO_SHOWN_DATE = "Shown date is not exist";
    const ERROR_ITEM_ATTR_IS_REQUIRED = "Required metadata is not input";
    const ERROR_ITEM_ATTR_TYPE = "This attribute does not exist";
    const ERROR_ITEM_ATTR_LINK = "Link name is not input";
    const ERROR_ITEM_ATTR_DATE = "Date format is invalid";
    const ERROR_ITEM_ATTR_CANDIDATE = "This candidate does not exist";
    const ERROR_BIBLIO_PUB_DATE = "Biblio issue date format is invalid";
    const ERROR_THUMBNAIL_NOT_EXIST = "Thumbnail file does not exist";
    const ERROR_FILE_NOT_EXIST = "Content file does not exist";
    const ERROR_FILE_PUB_DATE = "File publication date format is invalid";
    const ERROR_FILE_FLASH_PUB_DATE = "Flash publication date format is invalid";
    const ERROR_ITEM_TYPE = "This itemtype does not exist";
    const ERROR_EDIT_ITEM_URL = "Domain name is not match";
    const ERROR_EDIT_ITEM_NUM = "Edit tags there are more than 1";
    
    // Add for import error list 2014/11/04 T.Koyasu --start--
    const ERROR_ATTR_NAME_TITLE = "title";
    const ERROR_ATTR_NAME_SHOWN_DATE = "shown_date";
    
    // language resorce id for show error message in import select
    const ERROR_NUM_NO_XML = 1;
    const ERROR_NUM_PARSE_XML = 2;
    const ERROR_NUM_TAG_NUM = 3;
    const ERROR_NUM_ITEM_TITLE = 4;
    const ERROR_NUM_ITEM_SHOWN_DATE = 5;
    const ERROR_NUM_ITEM_NO_SHOWN_DATE = 6;
    const ERROR_NUM_ITEM_ATTR_IS_REQUIRED = 7;
    const ERROR_NUM_ITEM_ATTR_TYPE = 8;
    const ERROR_NUM_ITEM_ATTR_LINK = 9;
    const ERROR_NUM_ITEM_ATTR_DATE = 10;
    const ERROR_NUM_ITEM_ATTR_CANDIDATE = 11;
    const ERROR_NUM_BIBLIO_PUB_DATE = 12;
    const ERROR_NUM_THUMBNAIL_NOT_EXIST = 13;
    const ERROR_NUM_FILE_NOT_EXIST = 14;
    const ERROR_NUM_FILE_PUB_DATE = 15;
    const ERROR_NUM_FILE_FLASH_PUB_DATE = 16;
    const ERROR_NUM_ITEM_TYPE = 17;
    const ERROR_NUM_EDIT_ITEM_URL = 18;
    const ERROR_NUM_EDIT_ITEM_NUM = 19;
    // Add for import error list 2014/11/04 T.Koyasu --end--
    
    /**
     * execute check XML
     *
     * @param string $tmp_dir    xml path
     * @param Array  $error_list class array
     */
    public function validateXml($tmp_dir, &$error_list)
    {
        // import.xmlの存在をチェックする
        $result = file_exists($tmp_dir."/import.xml");
        if($result === false) {
            // エラー「import.xmlが存在しない」
                        $error_list[] = new DetailErrorInfo(0, "", self::ERROR_NO_XML, "", "", "", self::ERROR_NUM_NO_XML);
            return;
        }
        
        // XMLファイルを読み込む
        $dom = new DOMDocument('1.0', 'UTF^8');
        $result = $dom->load($tmp_dir."/import.xml");
        if($result === false) {
            // エラー「xmlの構文不正」
            $error_list[] = new DetailErrorInfo(0, "", self::ERROR_PARSE_XML, "", "", "", self::ERROR_NUM_PARSE_XML);
            return;
        }
        // XPath
        $xpath = new DOMXPath($dom);
        
        // アイテム情報を読み込む
        $item_nodes = $dom->getElementsByTagName("repository_item");
        // アイテムタイプ情報を読み込む
        $item_type_nodes = $dom->getElementsByTagName("repository_item_type");
        // アイテムとアイテムタイプの個数が同じであるかチェックする
        $this->compareNodeNum($item_nodes, $item_type_nodes, $error_list);
        
        // アイテム情報のチェック
        foreach($item_nodes as $item) {
            $this->checkItem($xpath, $item, $tmp_dir, $error_list);
        }
    }
    
    /**
     * compare nodes
     *
     * @param Object $item_nodes       item node list
     * @param Object $item_type_nodes  item type node list
     */
    private function compareNodeNum($item_nodes, $item_type_nodes, &$error_list) {
        // アイテムタブとアイテムタイプタグの長さが一致しない場合、エラー
        if($item_nodes->length != $item_type_nodes->length) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo(0, "", self::ERROR_TAG_NUM, "", "", "", self::ERROR_NUM_TAG_NUM);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * execute check XML
     *
     * @param Object $xpath             xml element
     * @param Object $item              item node
     * @param string $xml_file_path     xml path
     * @param Array  &$error_list       class array
     */
    private function checkItem($xpath, $item, $xml_file_path, &$error_list) {
        // アイテムID
        $item_id = $item->getAttribute("item_id");
        // タイトル
        $title_japanese = $item->getAttribute("title");
        // 英語タイトル
        $title_english = $item->getAttribute("title_english");
        // "タイトル / 英語タイトル"の形式
        $title = "";
        if(strlen($title_japanese) > 0){
            $title = $title_japanese;
        } else {
            $title = $title_english;
        }
        
        // タイトルの入力チェック
        $result = $this->checkItemTitle($title_japanese, $title_english);
        if($result === false) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, "", self::ERROR_ITEM_TITLE, self::ERROR_ATTR_NAME_TITLE, "", "", self::ERROR_NUM_ITEM_TITLE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // 公開日の入力チェック
        if($item->hasAttribute("shown_date")) {
            if(strlen($item->getAttribute("shown_date")) > 0) {
                $result = $this->checkShownDate($item->getAttribute("shown_date"));
                if($result === false) {
                    // Add for import error list 2014/11/04 T.Koyasu --start--
                    $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_SHOWN_DATE, self::ERROR_ATTR_NAME_SHOWN_DATE, $item->getAttribute("shown_date"), "", self::ERROR_NUM_ITEM_SHOWN_DATE);
                    // Add for import error list 2014/11/04 T.Koyasu --end--
                }
            }
        } else {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            // shown_dateの項目が存在しない
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_NO_SHOWN_DATE, self::ERROR_ATTR_NAME_SHOWN_DATE, "", "", self::ERROR_NUM_ITEM_NO_SHOWN_DATE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // アイテムタイプIDのチェック
        $item_type_id = $item->getAttribute("item_type_id");
        $result = $this->checkItemType($xpath, $item_type_id);
        if($result === false) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_TYPE, "", "", "", self::ERROR_NUM_ITEM_TYPE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // 必須属性のチェック
        $item_attr_type_list = $xpath->query("/export/repository_item_attr_type[@item_type_id = ".$item_type_id
            ." and @is_required = '1']");
        foreach($item_attr_type_list as $item_attr_type) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $this->checkItemAttrTypeIsRequired($xpath, $item_id, $item_type_id, 
                $item_attr_type->getAttribute("attribute_id"), $title, $error_list, $item_attr_type->getAttribute('attribute_name'));
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // アイテム属性のチェック
        $item_attr_list = $xpath->query("/export/repository_item_attr[@item_id = ".$item_id."]");
        foreach($item_attr_list as $item_attr) {
            $this->checkItemAttr($xpath, $item_id, $title, $item_attr, $error_list);
        }
        
        // 書誌情報のチェック
        $biblio_info_list = $xpath->query("/export/repository_biblio_info[@item_id = ".$item_id."]");
        foreach($biblio_info_list as $biblio_info) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $this->checkBiblioInfo($xpath, $item_id, $title, $biblio_info, $error_list, $item_type_id);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // サムネイルのチェック
        $thumbnail_list = $xpath->query("/export/repository_thumbnail[@item_id = ".$item_id."]");
        foreach($thumbnail_list as $thumbnail) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $this->checkThumbnail($xpath, $item_id, $title, $thumbnail, $xml_file_path, $error_list, $item_type_id);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // ファイルのチェック
        $file_list = $xpath->query("/export/repository_file[@item_id = ".$item_id."]");
        foreach($file_list as $file) {
            $this->checkFile($xpath, $item_id, $title, $file, $xml_file_path, $error_list, $item_type_id);
        }
        
        // サーバドメインのチェック（更新時のみ）
        $edit_item = $xpath->query("/export/repository_edit[@item_id = ".$item_id."]");
        if($edit_item->length == 1) {
            $this->checkEditItemUrl($edit_item->item(0)->nodeValue, $item_id, $title, $error_list);
        } else if($edit_item->length > 1) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_EDIT_ITEM_NUM, "", "", "", self::ERROR_NUM_EDIT_ITEM_NUM);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * execute check item attribute type is required
     *
     * @param Object $xpath         xml element
     * @param string $item_id       item id
     * @param string $item_type_id  item type id
     * @param string $attribute_id  attribute_id
     * @param string $title         title
     * @param Array  &$error_list   class array
     * @param string $attr_name     attribute_name
     */
    private function checkItemAttrTypeIsRequired($xpath, $item_id, $item_type_id, $attribute_id, $title, &$error_list, $attr_name) {
        // 必須属性のチェック
        // 通常属性
        $result1 = $xpath->query("/export/repository_item_attr[@item_id = ".$item_id
            ." and @item_type_id = ".$item_type_id." and @attribute_id = ".$attribute_id."]");
        // 書誌情報
        $result2 = $xpath->query("/export/repository_biblio_info[@item_id = ".$item_id
            ." and @item_type_id = ".$item_type_id." and @attribute_id = ".$attribute_id."]");
        // サムネイル
        $result3 = $xpath->query("/export/repository_thumbnail[@item_id = ".$item_id
            ." and @item_type_id = ".$item_type_id." and @attribute_id = ".$attribute_id."]");
        // ファイル
        $result4 = $xpath->query("/export/repository_file[@item_id = ".$item_id
            ." and @item_type_id = ".$item_type_id." and @attribute_id = ".$attribute_id."]");
        // 著者
        $result5 = $xpath->query("/export/repository_personal_name[@item_id = ".$item_id
            ." and @item_type_id = ".$item_type_id." and @attribute_id = ".$attribute_id."]");
        if($result1->length == 0 &&
           $result2->length == 0 &&
           $result3->length == 0 &&
           $result4->length == 0 &&
           $result5->length == 0) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_ATTR_IS_REQUIRED, $attr_name, "", "", self::ERROR_NUM_ITEM_ATTR_IS_REQUIRED);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * execute check item attribute
     *
     * @param Object $xpath         xml element
     * @param string $item_id       item id
     * @param string $title         title
     * @param Object $item_attr     item attribute node
     * @param Array  &$error_list   class array
     */
    private function checkItemAttr($xpath, $item_id, $title, $item_attr, &$error_list) {
        // アイテム属性ノードの入力タイプを検索
        $attr_type_path = $xpath->query(
            "/export/repository_item_attr_type[@item_type_id = ".$item_attr->getAttribute("item_type_id")
            ." and @attribute_id = ".$item_attr->getAttribute("attribute_id")."]");
        if($attr_type_path->length == 0) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_ATTR_TYPE, "", "", "", self::ERROR_NUM_ITEM_ATTR_TYPE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
            return;
        }
        
        // アイテム属性ノードの入力タイプによって場合分け
        switch($attr_type_path->item(0)->getAttribute("input_type")) {
            
            // リンクの場合
            case "link":
                $result = $this->checkItemAttrLink($item_attr->getAttribute("attribute_value"));
                if($result === false) {
                    // Add for import error list 2014/11/04 T.Koyasu --start--
                    $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_ATTR_LINK, $attr_type_path->item(0)->getAttribute('attribute_name'), $item_attr->getAttribute("attribute_value"), "", self::ERROR_NUM_ITEM_ATTR_LINK);
                    // Add for import error list 2014/11/04 T.Koyasu --end--
                }
                break;
            
            // 日付の場合
            case "date":
                $result = $this->checkItemAttrDate($item_attr->getAttribute("attribute_value"));
                if($result === false) {
                    // Add for import error list 2014/11/04 T.Koyasu --start--
                    $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_ATTR_DATE, $attr_type_path->item(0)->getAttribute('attribute_name'), $item_attr->getAttribute("attribute_value"), "", self::ERROR_NUM_ITEM_ATTR_DATE);
                    // Add for import error list 2014/11/04 T.Koyasu --end--
                }
                break;
            
            // チェックボックス、ラジオボタン、プルダウンの場合
            case "checkbox":
            case "radio":
            case "select":
                // Add for import error list 2014/11/04 T.Koyasu --start--
                $this->checkItemAttrCandidate($xpath, $item_id, $title, $item_attr, $error_list, $attr_type_path->item(0)->getAttribute('attribute_name'));
                // Add for import error list 2014/11/04 T.Koyasu --end--
                break;
            
            // それ以外は何もしない
            default:
                break;
        }
    }
    
    /**
     * check biblio information
     *
     * @param Object $xpath         xml element
     * @param string $item_id       item id
     * @param string $title         title
     * @param Object $biblio_info   biblio information node
     * @param Array  &$error_list   class array
     * @param string $item_type_id  item_type_id
     */
    private function checkBiblioInfo($xpath, $item_id, $title, $biblio_info, &$error_list, $item_type_id) {
        // 書誌情報の発行年月日フォーマットチェック(YYYY-MM-DD, YYYY-MM, YYYY)
        $result = $this->checkItemAttrDate($biblio_info->getAttribute("date_of_issued"));
        if($result === false) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $attr_name = $this->getAttributeName($xpath, $biblio_info, $item_type_id);
            
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_BIBLIO_PUB_DATE, $attr_name, $biblio_info->getAttribute("date_of_issued"), "", self::ERROR_NUM_BIBLIO_PUB_DATE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * check thumbnail
     *
     * @param Object $xpath             xml element
     * @param string $item_id       item id
     * @param string $title         title
     * @param Object $thumbnail         thumbnail node
     * @param string $xml_file_path     xml path
     * @param Array  &$error_list       class array
     * @param string $item_type_id      item_type_id
     */
    private function checkThumbnail($xpath, $item_id, $title, $thumbnail, $xml_file_path, &$error_list, $item_type_id) {
        // サムネイル存在チェック
        $result = $this->checkExistFile($thumbnail->getAttribute("file_name"), $xml_file_path);
        if($result === false) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $attr_name = $this->getAttributeName($xpath, $thumbnail, $item_type_id);
            
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_THUMBNAIL_NOT_EXIST, $attr_name, $thumbnail->getAttribute("file_name"), "", self::ERROR_NUM_THUMBNAIL_NOT_EXIST);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * check file
     *
     * @param Object $xpath             xml element
     * @param string $item_id       item id
     * @param string $title         title
     * @param Object $file              file node
     * @param string $xml_file_path     xml path
     * @param Array  &$error_list       class array
     * @param string $item_type_id      item_type_id
     */
    private function checkFile($xpath, $item_id, $title, $file, $xml_file_path, &$error_list, $item_type_id) {
        // Add for import error list 2014/11/04 T.Koyasu --start--
        // get attribute_name
        $attr_name = $this->getAttributeName($xpath, $file, $item_type_id);
        // Add for import error list 2014/11/04 T.Koyasu --end--
        
        // ファイル存在チェック
        $result = $this->checkExistFile($file->getAttribute("file_name"), $xml_file_path);
        if($result === false) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_FILE_NOT_EXIST, $attr_name, $file->getAttribute("file_name"), "", self::ERROR_NUM_FILE_NOT_EXIST);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        
        // ファイル公開日フォーマットチェック(YYYY-MM-DD HH:ii:ss.mmm, YYYY-MM-DD)
        if(strlen($file->getAttribute("pub_date")) > 0) {
            $result = $this->checkShownDate($file->getAttribute("pub_date"));
            if($result === false) {
                // Add for import error list 2014/11/04 T.Koyasu --start--
                $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_FILE_PUB_DATE, $attr_name, $file->getAttribute("pub_date"), "", self::ERROR_NUM_FILE_PUB_DATE);
                // Add for import error list 2014/11/04 T.Koyasu --end--
            }
        }
        
        // Flash公開日フォーマットチェック(YYYY-MM-DD HH:ii:ss.mmm, YYYY-MM-DD)
        if(strlen($file->getAttribute("flash_pub_date")) > 0) {
            $result = $this->checkShownDate($file->getAttribute("flash_pub_date"));
            if($result === false) {
                // Add for import error list 2014/11/04 T.Koyasu --start--
                $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_FILE_FLASH_PUB_DATE, $attr_name, $file->getAttribute("flash_pub_date"), "", self::ERROR_NUM_FILE_FLASH_PUB_DATE);
                // Add for import error list 2014/11/04 T.Koyasu --end--
            }
        }
    }
    
    /**
     * check item title
     *
     * @param string $title         title
     * @param string $title_english english title 
     *
     * return bool
     */
    private function checkItemTitle($title, $title_english) {
        // タイトルの入力チェック
        if(strlen($title) == 0 && strlen($title_english) == 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * check item attribute link
     *
     * @param string $link      link
     *
     * return bool
     */
    private function checkItemAttrLink($link) {
        // リンクのフォーマットチェック
        $link_exploded = explode('|', $link);
        if(strlen($link_exploded[0]) == 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * check item attribute date
     *
     * @param string $date      date
     *
     * return bool
     */
    private function checkItemAttrDate($date) {
        // 公開日のフォーマットチェック
        if(isset($date) && strlen($date) > 0) {
            $result = preg_match('/^\d{4}-\d{2}-\d{2}$|^\d{4}-\d{2}$|^\d{4}$/', $date);
            if($result == 0 || $result === false) {
                return false;
            } else {
                $date_exploded = explode('-', $date);
                switch(count($date_exploded)) {
                    case 1:
                        if(!checkdate(01, 01, $date_exploded[0])) {
                            return false;
                        }
                        break;
                    
                    case 2:
                        if(!checkdate($date_exploded[1], 01, $date_exploded[0])) {
                            return false;
                        }
                        break;
                    
                    case 3:
                        if(!checkdate($date_exploded[1], $date_exploded[2], $date_exploded[0])) {
                            return false;
                        }
                        break;
                    
                    default:
                        return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * execute check item attribute candidate
     *
     * @param Object $xpath         xml element
     * @param string $item_id       item id
     * @param string $title         title
     * @param Object $item_attr     item attribute node
     * @param Array  &$error_list   class array
     * @param string $attr_name      attribute name
     */
    private function checkItemAttrCandidate($xpath, $item_id, $title, $item_attr, &$error_list, $attr_name) {
        // アイテム属性選択候補タグを検索
        // PHPのXPathはエスケープの仕組みが用意されていないらしいので、IDから選択肢一覧を取得した後に比較を行う
        $result = $xpath->query("/export/repository_item_attr_candidate[@item_type_id = ".$item_attr->getAttribute("item_type_id")
                               ." and @attribute_id = ".$item_attr->getAttribute("attribute_id")."]");
        $exist_flag = false;
        $candidate_list = "";
        for($ii = 0; $ii < $result->length; $ii++) {
            if($result->item($ii)->getAttribute("candidate_value") == $item_attr->getAttribute("attribute_value")) {
                $exist_flag = true;
            }
            // Add for import error list 2014/11/04 T.Koyasu --start--
            if(strlen($candidate_list) > 0){
                $candidate_list .= "|";
            }
            $candidate_list .= $result->item($ii)->getAttribute("candidate_value");
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
        if(!$exist_flag) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_ITEM_ATTR_CANDIDATE, $attr_name, $item_attr->getAttribute("attribute_value"), $candidate_list, self::ERROR_NUM_ITEM_ATTR_CANDIDATE);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * check shown date
     *
     * @param string $date      date
     *
     * return bool
     */
    private function checkShownDate($date) {
        // アイテム公開日のフォーマットチェック(YYYY-MM-DD HH:ii:ss.mmm, YYYY-MM-DD)
        $result = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{3}$|^\d{4}-\d{2}-\d{2}/', $date);
        if($result == 0 || $result === false) {
            return false;
        } else {
            $date_ymd = explode(" ", $date);
            // 年月日の範囲チェック
            $date_exploded = explode("-", $date_ymd[0]);
            if(!checkdate($date_exploded[1], $date_exploded[2], $date_exploded[0])) {
                return false;
            }
            // 時刻の範囲チェック
            if(isset($date_ymd[1])) {
                $time_exploded = explode(":", $date_ymd[1]);
                // 時
                $hour = intval($time_exploded[0]);
                // 分
                $minute = intval($time_exploded[1]);
                // 秒
                $sec_exploded = explode(".", $time_exploded[2]);
                $sec = intval($sec_exploded[0]);
                
                // 0-23時
                if(!($hour >= 0 && $hour < 24)) {
                    return false;
                }
                // 0-59分
                if(!($minute >= 0 && $minute < 60)) {
                    return false;
                }
                // 0-59秒
                if(!($sec >= 0 && $sec < 60)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * check exist file
     *
     * @param string $file_name     file name
     * @param string $file_path     xml path
     *
     * return bool
     */
    private function checkExistFile($file_name, $file_path) {
        // WEKO-2014-103 暫定対応
        // ファイル存在チェック
        //$result = file_exists($file_path."/".$file_name);
        //if($result === false) {
        //    return false;
        //}
        
        return true;
    }
    
    /**
     * execute check edit item url
     *
     * @param string $url           url
     * @param string $item_id       item id
     * @param string $title         title
     * @param Array  &$error_list   class array
     */
    private function checkEditItemUrl($url, $item_id, $title, &$error_list) {
        $server_domain = $_SERVER["SERVER_NAME"];
        // 文字列からドメイン部分を取得
        $domain_name = parse_url($url);
        if($server_domain != $domain_name["host"]) {
            // Add for import error list 2014/11/04 T.Koyasu --start--
            $base_url = 0;
            $error_list[] = new DetailErrorInfo($item_id, $title, self::ERROR_EDIT_ITEM_URL, $domain_name["host"], "", "", self::ERROR_NUM_EDIT_ITEM_URL);
            // Add for import error list 2014/11/04 T.Koyasu --end--
        }
    }
    
    /**
     * execute check item attribute candidate
     *
     * @param Object $xpath         xml element
     * @param string $item_type_id       item type id
     */
    private function checkItemType($xpath, $item_type_id) {
        // アイテム属性選択候補タグを検索
        $result = $xpath->query("/export/repository_item_type[@item_type_id = ".$item_type_id."]");
        if($result->length == 0) {
            return false;
        }
        
        return true;
    }
    
    // Add for import error list 2014/11/04 T.Koyasu --start--
    /**
     * get attibute_name by node_list(thumbnail, biblio, file, etc.)
     *
     * @param Object $xpath         xml element
     * @param Object $xml_node_list DomNode(thumbnail, biblio, file, etc.)
     * @param string $item_type_id  item_type_id
     * @return unknown
     */
    private function getAttributeName($xpath, $xml_node_list, $item_type_id)
    {
        $attr_name = "";
        
        $attr_id = $xml_node_list->getAttribute("attribute_id");
        $item_attr_list = $xpath->query("/export/repository_item_attr_type[@attribute_id = ". $attr_id. " and @item_type_id = ". $item_type_id. "]");
        foreach($item_attr_list as $item_attr){
            $attr_name = $item_attr->getAttribute("attribute_name");
        }
        
        return $attr_name;
    }
    // Add for import error list 2014/11/04 T.Koyasu --end--
}

class DetailErrorInfo
{
    public $item_id = null;
    public $title = null;
    public $error = null;
    // Add for import error list 2014/11/04 T.Koyasu --start--
    public $attr_name = null;
    public $input_value = null;
    public $regist_value = null;
    public $error_no = 0;
    // Add for import error list 2014/11/04 T.Koyasu --end--
    
    /**
     * construct
     *
     * @param int $item_id
     * @param string $title
     * @param string $error
     * @param string $attr_name
     * @param string $input_value
     * @param string $regist_value
     * @param int    $error_no
     */
    function __construct($item_id, $title, $error, $attr_name, $input_value, $regist_value, $error_no) {
        $this->item_id = $item_id;
        $this->title = $title;
        $this->error = $error;
        // Add for import error list 2014/11/04 T.Koyasu --start--
        $this->attr_name = $attr_name;
        $this->input_value = $input_value;
        $this->regist_value = $regist_value;
        $this->error_no = $error_no;
        // Add for import error list 2014/11/04 T.Koyasu --end--
    }
}
?>
