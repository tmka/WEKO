<?php
// --------------------------------------------------------------------
//
// $Id: Detail.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Json_Detail extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    // 添付種別のみ特殊対応
    const ATTACHMENT_TYPE = "rm_attachment_type";
    const ATTACHMENT_TYPE_SYNC = "rm_attachment_type_sync";
    
    // アイテムID
    public $item_id = null;
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $pubflg = $itemAuthorityManager->checkItemPublicFlg($this->item_id, 1, $this->repository_admin_base, $this->repository_admin_room);
        
        if($pubflg){
            // アイテムのデータを取得する
            $itemList = null;
            $error_msg = "";
            $result = $this->getItemData($this->item_id, 1, $itemList, $error_msg);
            if($result){
                // アイテム情報をJSON形式で出力する
                $json_text = $this->createJSONItemData($itemList);
                echo $json_text;
            }
        }
        
        return true;
    }
    
    // アイテムデータをJSON形式で出力する
    private function createJSONItemData($itemList)
    {
        // 初期化
        $outputJSONArray = array();
        $outputFileJSONArray = array();
        $outputTypeArray = array();
        
        // アイテムの基本情報をJSON配列に登録する
        $this->setJSONItemBaseData($itemList["item"], $itemList["item_type"], $outputJSONArray);
        
        $this->setJSONItemMetaData($itemList["item_attr_type"], $itemList["item_attr"], 
                                   $outputJSONArray, $outputFileJSONArray, $outputTypeArray);
        // JSON文字列を作成する
        $json_text = $this->createJSONString($outputJSONArray, $outputFileJSONArray, $outputTypeArray);
        return $json_text;
    }    
    
    // アイテム基本情報をJSON出力配列に登録する
    private function setJSONItemBaseData($itemInfo, $itemTypeInfo, &$outputJSONArray)
    {
        // アイテムの基本情報をJSON出力配列に登録する
        $this->addDataToJSONArray("wekoid", $itemInfo[0]["item_id"], $outputJSONArray);
        $this->addDataToJSONArray("title", $itemInfo[0]["title"], $outputJSONArray);
        $this->addDataToJSONArray("title_sync", $itemInfo[0]["title_english"], $outputJSONArray);
        $this->addDataToJSONArray("lang_dirname", $itemInfo[0]["language"], $outputJSONArray);
        
        // アイテムタイプの情報をJSON出力配列に登録する
        $this->addDataToJSONArray("item_type", $itemTypeInfo[0]["item_type_name"], $outputJSONArray);
    }
    
    // アイテム基本情報をJSON出力配列に登録する
    private function setJSONItemMetaData($itemAttrTypeInfo, $itemAttrInfo, &$outputJSONArray, 
                                         &$outputFileJSONArray, &$outputTypeJSONArray)
    {
        // アイテムメタデータを登録
        for($ii = 0; $ii < count($itemAttrTypeInfo); $ii++){
            if($itemAttrTypeInfo[$ii]["hidden"] == 1){
                continue;
            }
            if(!isset($itemAttrInfo[$ii])){
                continue;
            }
            $metadataName = $itemAttrTypeInfo[$ii]["attribute_name"];
            
            if(strcmp($metadataName, self::ATTACHMENT_TYPE) == 0 || strcmp($metadataName, self::ATTACHMENT_TYPE_SYNC) == 0){
                $this->setAttachmentType($metadataName, $itemAttrInfo[$ii], $outputTypeJSONArray);
                continue;
            }
            switch($itemAttrTypeInfo[$ii]["input_type"]){
                case "biblio_info":
                    $this->setJSONItemBiblioInfo($metadataName, $itemAttrInfo[$ii], $outputJSONArray);
                    break;
                case "file":
                case "file_price":
                    $this->setJSONItemFile($metadataName, $itemAttrInfo[$ii], $outputFileJSONArray);
                    break;
                default:
                    $this->setJSONItemAttr($metadataName, $itemAttrInfo[$ii], $itemAttrTypeInfo[$ii], $outputJSONArray);
                    break;
            }
        }
    }
    
    // JSON配列からJSON文字列を作成する
    private function createJSONString($outputJSONArray, $outputFileJSONArray, $outputTypeArray)
    {
        // JSON出力配列をJSON文字列として出力する
        $json_text = "{";
        $count = 0;
        foreach($outputJSONArray as $key => $data)
        {
            if($count != 0){
                $json_text .= ",";
            }
            $text = "";
            for($ii = 0; $ii < count($data); $ii++){
                if($ii != 0){
                    $text .= " ";
                }
                $text .= RepositoryOutputFilter::escapeJSON($data[$ii], true);
            }
            // "キー"："値"で追加
            $json_text .= "\"". RepositoryOutputFilter::escapeJSON($key, true). "\":\"" . $text."\"";
            $count++;
        }
        if(count($outputFileJSONArray) != 0){
            $json_text .= ",";
        }
        $count = 0;
        // "キー":{"0":",{"name":"ファイル名","link":"ファイルリンク"}}で追加
        foreach($outputFileJSONArray as $key => $data)
        {
            if($count != 0){
                $json_text .= ",";
            }
            $text = "";
            // Mod back key of file metadata is not exists coron T.Koyasu 2014/09/12 --start--
            // "キー":{
            $json_text .= "\"" . RepositoryOutputFilter::escapeJSON($key, true) ."\":{";
            // Mod back key of file metadata is not exists coron T.Koyasu 2014/09/12 --end--
            for($ii = 0; $ii < count($data); $ii++){
                if($ii != 0){
                    $json_text .= ",";
                }
                // "0":{
                $json_text .= "\"" . $ii . "\":{";
                // "name":"ファイル名",
                $json_text .= "\"name\":\"" . RepositoryOutputFilter::escapeJSON($data[$ii]["name"], true). "\",";
                // "link":"ファイルリンク",
                $json_text .= "\"link\":\"" . $data[$ii]["link"]. "\"";
                // "0":{ を閉じる
                $json_text .= "}";
            }
            // "キー":{ を閉じる
            $json_text .= "}";
            $count++;
        }
        
        if(count($outputTypeArray) > 0){
            $json_text .= ",";
        }
        // "キー": {"0":"プレプリント"}, {"1":"発表資料"}
        $count = 0;
        foreach($outputTypeArray as $key => $data)
        {
            if($count > 0){
                $json_text .= ",";
            }
            $json_text .= "\"". RepositoryOutputFilter::escapeJSON($key, true). "\":{";
            for($ii = 0; $ii < count($data); $ii++)
            {
                if($ii != 0){
                    $json_text .= ",";
                }
                // "No.":"attribute_value"
                $json_text .= "\"". $ii. "\":\"". RepositoryOutputFilter::escapeJSON($data[$ii]["type"], true). "\"";
            }
            $json_text .= "}";
            $count++;
        }
        
        $json_text .= "}";
        return $json_text;
    }
    
    // メタデータ属性がbiblio_infoのメタデータをJSON出力配列に登録する
    private function setJSONItemBiblioInfo($metadataName, $metadata, &$outputJSONArray)
    {
        // メタデータを登録
        foreach($metadata as $data)
        {
            // 雑誌名
            $this->addDataToJSONArray($metadataName."_jounal", $data["biblio_name"], $outputJSONArray);
            // 雑誌名(英)
            $this->addDataToJSONArray($metadataName."_jounal_sync", $data["biblio_name_english"], $outputJSONArray);
            // 刊
            $this->addDataToJSONArray($metadataName."_volume", $data["volume"], $outputJSONArray);
            // 号
            $this->addDataToJSONArray($metadataName."_number", $data["issue"], $outputJSONArray);
            // 開始ページ
            $this->addDataToJSONArray($metadataName."_startingPage", $data["start_page"], $outputJSONArray);
            // 終了ページ
            $this->addDataToJSONArray($metadataName."_endingPage", $data["end_page"], $outputJSONArray);
            // 発行日
            $this->addDataToJSONArray($metadataName."_publicationDate", $data["date_of_issued"], $outputJSONArray);
        }
    }
    
    // メタデータ属性がfileまたはfile_priceのメタデータをJSON出力配列に登録する
    private function setJSONItemFile($metadataName, $metadata, &$outputFileJSONArray)
    {
        // メタデータを登録
        foreach($metadata as $data)
        {
            $fileArray = array();
            $fileArray["name"] = $data["display_name"];
            $fileArray["link"] = BASE_URL . "/?action=repository_uri&item_id=" . $this->item_id.
                                 "&file_id=" . $data["attribute_id"] . "&file_no=" . $data["file_no"];
            if(isset($outputFileJSONArray[$metadataName])){
                array_push($outputFileJSONArray[$metadataName], $fileArray);
            } else {
                $outputFileJSONArray[$metadataName] = array($fileArray);
            }
        }
    }
    
    private function setAttachmentType($metadataName, $metadata, &$outputTypeJSONArray)
    {
        // regist attachment_type
        foreach($metadata as $data)
        {
            $typeArray = array();
            $typeArray["type"] = $data["attribute_value"];
            
            if(isset($outputTypeJSONArray[$metadataName])){
                array_push($outputTypeJSONArray[$metadataName], $typeArray);
            } else {
                $outputTypeJSONArray[$metadataName] = array($typeArray);
            }
        }
    }
    
    // メタデータ属性がその他のメタデータをJSON出力配列に登録する
    private function setJSONItemAttr($metadataName, $metadata, $metadataInfo, &$outputJSONArray)
    {
        // メタデータを登録
        foreach($metadata as $data)
        {
            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --start--
            $value = RepositoryOutputFilter::attributeValue($metadataInfo, $data, 1, RepositoryOutputFilter::NAME_DELIMITER_IS_COMMA);
            // Mod name delimiter changes to comma T.Koyasu 2014/09/12 --end--
            
            // Add remove blank word T.Koyasu 2014/09/16 --start--
            $value = RepositoryOutputFilter::exclusiveReservedWords($value);
            // Add remove blank word T.Koyasu 2014/09/16 --end--
            
            $this->addDataToJSONArray($metadataName, $value, $outputJSONArray);
        }
    }
    
    // JSON出力配列にデータを登録する
    private function addDataToJSONArray($key, $value, &$outputJSONArray)
    {
        if(isset($outputJSONArray[$key])){
            array_push($outputJSONArray[$key], $value);
        } else {
            $outputJSONArray[$key] =  array($value);
        }
    }
    
    
}
?>
