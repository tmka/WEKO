<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryDownload.class.php 24001 2013-07-10 01:33:59Z yuko_nakao $
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
require_once WEBAPP_DIR. '/modules/repository/action/main/export/ExportCommon.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class RepositoryOutputTSV extends RepositoryAction
{
    /**
     * ユーザーIDのプレフィックス数
     *
     * @var int
     */
    private $authorPrefixNum = null;
    
    /**
     * ファイルポインタ
     */
    private $fp = null;
    /**
     * ヘッダー配列
     */
    private $headerArray = null;
    /**
     * ヘッダ数
     */
    private $headerNum = null;
    /**
     * SettionのsmartyAssignを格納するオブジェクト
     *
     * @var Object
     */
    private $smartyAssign = null;
    
    /**
     * ユーザーがAdminかのフラグ
     *
     * @var bool
     */
    private $isAdminUser = null;
    
    
    /**
     * ファイルダウンロードのvalidator
     *
     */
    private $RepositoryValidator = null;
    
    const INPUT_TYPE = "input_type";
    const ATTR = "attr";
    const AUTHOR_ID = "ID";
    const NAME = "name";
    const THUMBNAIL = "thumnail";
    const BIBLIO = "biblio";
    const FILE = "file";
    const FILE_PRICE = "file_price";
    const HEADER = "header";
    const IS_INSUSER = "is_insuser";
    const FEEDBACK_MAIL = "feedback_mail";
    const SELF_DOI_RA = "self_doi_ra";
    const SELF_DOI = "self_doi";
    
    // TSV 出力の名称
    private $header_weko_url = null;
    private $header_item_type = null;
    private $header_title = null;
    private $header_title_english = null;
    private $header_language = null;
    private $header_keyword = null;
    private $header_keyword_english = null;
    private $header_shown_date = null;
    private $header_author_name = null;
    private $header_author_ruby = null;
    private $header_author_email = null;
    private $header_author_id = null;
    private $header_biblio_name = null;
    private $header_biblio_name_english = null;
    private $header_biblio_volume = null;
    private $header_biblio_issue = null;
    private $header_biblio_startpage = null;
    private $header_biblio_endpage = null;
    private $header_biblio_date = null;
    private $header_link_name = null;
    private $header_link_url = null;
    private $header_file_name = null;
    private $header_file_display_name = null;
    private $header_file_date = null;
    private $header_file_flash_pubdate = null;
    private $header_file_cc_license = null;
    private $header_file_notation = null;
    private $header_file_price_non = null;
    private $header_file_price_member = null;
    private $header_heading = null;
    private $header_heading_english = null;
    private $header_heading_small = null;
    private $header_heading_small_english = null;
    // Add e-person 2013/10/23 R.Matsura
    private $header_heading_feedback_mail = null;
    // bug fix 2013/10/25 R.Matsuura
    private $header_heading_self_doi_ra = null;
    private $header_heading_self_doi = null;
    private $textareaCounter = 0;
    private $authorPrefixCounter = 0;
    /**
     * INIT
     */
    public function RepositoryOutputTSV($Db, $Session) {
    
        $this->Db = $Db;
        $this->Session = $Session;        
        // 共通の初期処理
        $result = $this->initAction();
        if ( $result == false ){
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
            
        $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        if($this->smartyAssign == null){
            // A resource tidy because it is not a call from view action is not obtained. 
            // However, it doesn't shutdown. 
            $this->setLangResource();
            $this->smartyAssign = $this->Session->getParameter("smartyAssign");
        }
        $this->setLang();
        require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
        $this->RepositoryValidator = new Repository_Validator_DownloadCheck();
        $initResult = $this->RepositoryValidator->setComponents($this->Session, $this->Db);
        if($initResult === 'error'){
            return;
        }
    }
    
    /**
     * smartyAssignから言語を取得
     */
    private function setLang(){
        $this->header_weko_url = $this->smartyAssign->getLang("repository_export_list_weko_url");
        $this->header_item_type = $this->smartyAssign->getLang("repository_itemtype");
        $this->header_title = $this->smartyAssign->getLang("repository_title_japanese");
        $this->header_title_english = $this->smartyAssign->getLang("repository_title_english");
        $this->header_language = $this->smartyAssign->getLang("repository_language");
        $this->header_keyword = $this->smartyAssign->getLang("repository_keyword_japanese");
        $this->header_keyword_english = $this->smartyAssign->getLang("repository_keyword_english");
        $this->header_shown_date = $this->smartyAssign->getLang("repository_pub_date");
        $this->header_author_name = "(".$this->smartyAssign->getLang("repository_family")."+".$this->smartyAssign->getLang("repository_name").")";
        $this->header_author_ruby = "(".$this->smartyAssign->getLang("repository_ruby").")";
        $this->header_author_email = "(".$this->smartyAssign->getLang("repository_email").")";
        $this->header_author_id = "(".$this->smartyAssign->getLang("repository_author_id").")";
        $this->header_biblio_name = "(".$this->smartyAssign->getLang("repository_item_biblio_name").")";
        $this->header_biblio_name_english = "(".$this->smartyAssign->getLang("repository_item_biblio_name_english").")";
        $this->header_biblio_volume = "(".$this->smartyAssign->getLang("repository_item_biblio_volume").")";
        $this->header_biblio_issue = "(".$this->smartyAssign->getLang("repository_item_biblio_issue").")";
        $this->header_biblio_startpage = "(".$this->smartyAssign->getLang("repository_item_biblio_spage").")";
        $this->header_biblio_endpage = "(".$this->smartyAssign->getLang("repository_item_biblio_epage").")";
        $this->header_biblio_date = "(".$this->smartyAssign->getLang("repository_item_biblio_dateofissued").")";
        $this->header_link_name = "(".$this->smartyAssign->getLang("repository_item_attr_link_name").")";
        $this->header_link_url = "(".$this->smartyAssign->getLang("repository_item_attr_link_url").")";
        $this->header_file_name = "(".$this->smartyAssign->getLang("repository_export_filename").")";
        $this->header_file_display_name = "(".$this->smartyAssign->getLang("repository_export_list_file_display_name").")";
        $this->header_file_date = "(".$this->smartyAssign->getLang("repository_pub_date").")";
        $this->header_file_flash_pubdate = "(".$this->smartyAssign->getLang("repository_export_list_file_flash_pub_date").")";
        $this->header_file_cc_license = "(".$this->smartyAssign->getLang("repository_export_list_file_cc_licence").")";        
        $this->header_file_notation = "(".$this->smartyAssign->getLang("repository_export_list_file_notation_license").")";
        $this->header_file_price_non = "(".$this->smartyAssign->getLang("repository_export_list_file_price_nonmember").")";
        $this->header_file_price_member = "(".$this->smartyAssign->getLang("repository_export_list_file_price_member").")";
        $this->header_heading = "(".$this->smartyAssign->getLang("repository_item_heading").")";
        $this->header_heading_english = "(".$this->smartyAssign->getLang("repository_item_heading_en").")";
        $this->header_heading_small = "(".$this->smartyAssign->getLang("repository_item_heading_sub").")";
        $this->header_heading_small_english = "(".$this->smartyAssign->getLang("repository_item_heading_sub_en").")";
        $this->header_heading_feedback_mail = $this->smartyAssign->getLang("repository_item_feedback_mail");
        $this->header_heading_self_doi_ra = $this->smartyAssign->getLang("repository_item_self_doi_ra");
        $this->header_heading_self_doi = $this->smartyAssign->getLang("repository_item_self_doi");
    }
    
    /**
     * TSV出力処理
     * @param filepath I TSVファイルの保存パス
     * @param itemDataArray I アイテムIDとアイテムNOと登録ユーザーを要素に持つ配列
     * @param rowCount
     * @param row
     */
    public function outputTsv($filepath, $itemDataArray=array(), $rowCount=0, $row=0){

        if(count($itemDataArray) == 0)
        {
            $query = "SELECT item_id, item_no ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE is_delete = ? ".
                     "ORDER BY item_id ASC ";
            $params = array();
            $params[] = 0;
            if($rowCount > 0 && $row > 0)
            {
                $query .= " LIMIT ?, ? ;";
                $params[] = $rowCount;
                $params[] = $row;
            }
            
            $result = $this->Db->execute($query, $params);
            if ( $result == false ){
                return false;
            }
            $itemDataArray = $result;
        }
        $exportDataArray = $this->checkAuthority($itemDataArray);
        if(count($exportDataArray) == 0)
        {
            return false;
        }
        
        $this->fp = fopen( $filepath, "w" );
        if (!$this->fp){
            // ファイルのオープンに失敗した場合
            echo "ファイルオープンエラー<br>";
            return false;
        }
        fwrite($this->fp, pack('C*',0xEF,0xBB,0xBF));//BOM書き込み
        // TSVのヘッダーの出力
        if(!$this->outputTsvHeader($exportDataArray)){
            // ヘッダ情報がないとき
            fclose($this->fp);
            unlink($filepath);
            return false;
        } else {
            // TSVのデータの出力
            for($ii = 0; $ii < count($exportDataArray); $ii++){
                $this->outputTsvData( $exportDataArray[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID]
                    , $exportDataArray[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO], $exportDataArray[$ii][self::IS_INSUSER]);
            }
            fclose($this->fp);
        }
        return true;
    }
    
    /**
     * TSVヘッダー情報出力処理
     * 
     * @param itemDataArray
     * @return bool
     */
    private function outputTsvHeader($itemDataArray=array()){
        if(count($itemDataArray) == 0)
        {
            $query = "SELECT DISTINCT item_type_id ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE is_delete = 0 ".
                     "ORDER BY item_type_id ASC ;";
            $result = $this->Db->execute($query);
            if ( $result == false ){
                return false;
            }
        }
        else
        {
            // アイテムタイプ取得
            $query = "SELECT DISTINCT item_type_id ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE (`item_id`, `item_no`) IN ( ";
            for($ii = 0; $ii < count($itemDataArray); $ii++){
                $query .= "( ". $itemDataArray[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID] .", ". $itemDataArray[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO]. " )";
                if($ii != count($itemDataArray) - 1){
                    $query .= ", ";
                }
            }
            $query .= " ) AND is_delete = 0 ".
                      " ORDER BY item_type_id ASC;";
            $result = $this->Db->execute( $query );
            if ( $result == false ){
                return false;
            }
        }
        $itemTypeArray = $result;
        
        $Header = array();
        $HeaderStr = "";
        // ヘッダーの作成
        $this->createTsvHeader($itemTypeArray, $HeaderStr);
        if($this->fp){
            fputs($this->fp, $HeaderStr . "\r\n");
        }
        //出力データが無い場合
        if(strlen($HeaderStr) < 1){
            return false;
        }
        return true;
    }
    
    /**
     * TSV登録データ情報出力処理
     */
    private function outputTsvData($item_id, $item_no, $is_insUser){
        $tsvDataArray = array();
        for($ii = 0; $ii < $this->headerNum; $ii++){
            $tsvDataArray[$ii] = "";
        }
        $itemData = array();
        $this->getItemDataForTsv($item_id, 
                                 $item_no, 
                                 $itemData, 
                                 $errMsg);
        // デフォルト部分の設定
        $this->setTsvDefaultData($this->headerArray, $itemData, $tsvDataArray);
        // repository_item_attrテーブルの項目の設定
        $this->setTsvAttrTypeData($this->headerArray, $itemData, $is_insUser, $tsvDataArray);
        // repository_personal_nameテーブルの項目の設定
        $this->setTsvNameData($this->headerArray, $itemData, $is_insUser, $tsvDataArray);
        // repository_biblio_infoテーブルの項目の設定
        $this->setTsvBiblioData($this->headerArray, $itemData, $is_insUser, $tsvDataArray);
        // repository__fileテーブルの項目の設定
        $this->setTsvFileData($this->headerArray, $itemData, $is_insUser, $tsvDataArray);
        // repository_file_priceテーブルの項目の設定
        $this->setTsvFilePriceData($this->headerArray, $itemData, $is_insUser, $tsvDataArray);
        // repository_send_feedback_author_id
        $this->setTsvFeedbackMail($this->headerArray, $itemData, $tsvDataArray);
         // repository_self_DOI_RA
        $this->setTsvSelfDoiRa($this->headerArray, $itemData, $tsvDataArray);
        // repository_self_DOI
        $this->setTsvSelfDoi($this->headerArray, $itemData, $tsvDataArray);
        if($this->fp){
            for($jj = 0; $jj < count($tsvDataArray); $jj++){
                fputs($this->fp, $tsvDataArray[$jj] . "\t");
            }
            fputs($this->fp, "\r\n");
        }
    }
    
    /**
     * TSVヘッダー情報作成処理
     *
     * @param itemTypeArray I アイテムタイプの配列
     * @param headerArray O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     */
    private function createTsvHeader($itemTypeArray, &$headerStr){
        $columnNo = 0;
        $baseHeaderNameArray = array();
        $baseHeaderNameArray[self::HEADER] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_NAME] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_LINK] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_FILE] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_HEADING] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE] = array();
        $baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA] = array();
        $columnNo = 0;
        
        $this->createTsvDefaultHeader($this->headerArray, $headerStr, $baseHeaderNameArray, $columnNo);
        // アイテムタイプのメタデータ登録
        $inputHeaderNameArray = $baseHeaderNameArray;
        for($ii =0; $ii < count($itemTypeArray); $ii++){
            // このアイテムタイプ内に同名・同型のアイテムタイプがないか確認用
            $tmpHeaderNameArray = $baseHeaderNameArray;
            $query = "SELECT attribute_name, input_type ".
                     "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
                     "WHERE item_type_id = ? ".
                     "AND is_delete = ? ".
                     "ORDER BY show_order ASC ;";
            $params = array();
            $params[] = $itemTypeArray[$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_ID];
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if ( $result == false ){
                return;
            }
            for($jj = 0; $jj < count($result); $jj++){
                if($result[$jj][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_SELECT || $result[$jj][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_DATE 
                    || $result[$jj][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX || $result[$jj][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_RADIO 
                    || $result[$jj][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_THUMBNAIL ){
                    $result[$jj][self::INPUT_TYPE] = RepositoryConst::ITEM_ATTR_TYPE_TEXT;
                }
                // 一つのアイテムタイプで同名・同型のメタデータがないかを確認
                $attribute_name = $this->checkArrayInput($result[$jj][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME], $tmpHeaderNameArray[$result[$jj][self::INPUT_TYPE]]);
                array_push($tmpHeaderNameArray[$result[$jj][self::INPUT_TYPE]], $attribute_name);
                $this->createTsvMetaDataHeader($attribute_name, $result[$jj][self::INPUT_TYPE], $result[$jj][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                                                , $this->headerArray, $headerStr, $inputHeaderNameArray, $columnNo);
            }
        }
        // Add e-person 2013/10/24 R.Matsuura --start--
        $this->headerArray[$this->header_heading_feedback_mail] = array();
        $this->headerArray[$this->header_heading_feedback_mail][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_heading_feedback_mail."\t";
        $columnNo++;
        array_push($inputHeaderNameArray[self::HEADER], $this->header_heading_feedback_mail);
        array_push($inputHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_heading_feedback_mail);
        // Add e-person 2013/10/24 R.Matsuura --end--
        
        $this->headerArray[$this->header_heading_self_doi_ra] = array();
        $this->headerArray[$this->header_heading_self_doi_ra][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_heading_self_doi_ra."\t";
        $columnNo++;
        array_push($inputHeaderNameArray[self::HEADER], $this->header_heading_self_doi_ra);
        array_push($inputHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_heading_self_doi_ra);
        
        $this->headerArray[$this->header_heading_self_doi] = array();
        $this->headerArray[$this->header_heading_self_doi][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_heading_self_doi."\t";
        $columnNo++;
        array_push($inputHeaderNameArray[self::HEADER], $this->header_heading_self_doi);
        array_push($inputHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_heading_self_doi);
        
        $this->headerNum = count($inputHeaderNameArray[self::HEADER]) + $this->textareaCounter + $this->authorPrefixCounter;
            
    }
    
    /**
     * 配列に入ってない要素にヘッダー名を修正
     *
     * @param name I ヘッダー名
     * @param array I ヘッダー名配列
     */
    private function checkArrayDataInput($attribute_name, $attribute_id, &$useNameArray, &$isSetData)
    {
        $setName = $attribute_name;
        $isSetData = true;
        if(!isset($useNameArray[$attribute_name])){
            $useNameArray[$setName] = $attribute_id;
            $isSetData = false;
        } else if($useNameArray[$attribute_name] != $attribute_id){
            $kk = 1;
            while(1){
                if(!isset($useNameArray[$attribute_name.'_'.$kk])){
                    $isSetData = false;
                    $setName = $attribute_name.'_'.$kk;
                    $useNameArray[$setName] = $attribute_id;
                    break;
                } else if($useNameArray[$attribute_name.'_'.$kk] == $attribute_id){
                    $setName = $attribute_name.'_'.$kk;
                    $useNameArray[$setName] = $attribute_id;
                    break;
                }
                $kk++;
            }
        }
        return $this->prepareforTSV($setName);
    }
    /**
     * 配列に入ってない要素にヘッダー名を修正
     *
     * @param name I ヘッダー名
     * @param array I ヘッダー名配列
     */
    private function checkArrayInput($name, &$array)
    {
        $setName = $this->prepareforTSV($name);
        if(in_array($setName, $array)){
            $kk = 1;
            while(1){
                if(!in_array($setName.'_'.$kk, $array)){
                    $setName = $setName.'_'.$kk;
//                    array_push($array, $setName);
                    break;
                }
                $kk++;
            }
        }
        return $setName;
    }
    
    /**
     * TSVに出力する形式に整形
     *
     * @param name I ヘッダー名
     * @param array I ヘッダー名配列
     */
    private function prepareforTSV($value)
    {
        //タブを空文字・改行を改行文字でTSVに出力されるように置換(SCfWのtsvの出力と同様)
        $value = str_replace("\t","",$value);
        $value = str_replace("\r\n","\\n",$value);
        $value = str_replace("\r","\\n",$value);
        $value = str_replace("\n","\\n",$value);
        return $value;
    }
    /**
     * TSVヘッダー情報デフォルト部分作成処理
     *
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param baseHeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvDefaultHeader(&$headerArray, &$headerStr, &$baseHeaderNameArray, &$columnNo)
    {
        // WEKO_URLを設定
        $headerArray[$this->header_weko_url] = array();
        $headerArray[$this->header_weko_url][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_weko_url."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_weko_url);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_weko_url);
        // アイテムタイプを設定
        $headerArray[$this->header_item_type] = array();
        $headerArray[$this->header_item_type][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_item_type."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_item_type);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_item_type);
        // タイトルを設定
        $headerArray[$this->header_title] = array();
        $headerArray[$this->header_title][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_title."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_title);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_title);
        // タイトル(英)を設定
        $headerArray[$this->header_title_english] = array();
        $headerArray[$this->header_title_english][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_title_english."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_title_english);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_title_english);
        // 言語を設定
        $headerArray[$this->header_language] = array();
        $headerArray[$this->header_language][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_language."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_language);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_language);
        // キーワードを設定
        $headerArray[$this->header_keyword] = array();
        $headerArray[$this->header_keyword][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_keyword."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_keyword);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_keyword);
        // キーワード(英)を設定
        $headerArray[$this->header_keyword_english] = array();
        $headerArray[$this->header_keyword_english][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_keyword_english."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_keyword_english);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_keyword_english);
        // 公開日を設定
        $headerArray[$this->header_shown_date] = array();
        $headerArray[$this->header_shown_date][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        $headerStr .= $this->header_shown_date."\t";
        $columnNo++;
        array_push($baseHeaderNameArray[self::HEADER], $this->header_shown_date);
        array_push($baseHeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $this->header_shown_date);
    }
    
    /**
     * TSVヘッダー情報メタデータ部分作成処理
     *
     * @param attribute_name I/O ヘッダー名  
     * @param input_type I/O メタデータタイプ  
     * @param baseAttributeName I メタデータ項目名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvMetaDataHeader($attribute_name, $input_type, $baseAttributeName, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(in_array($attribute_name, $HeaderNameArray[$input_type])){
            return;
        }
        switch($input_type){
        case RepositoryConst::ITEM_ATTR_TYPE_TEXT:
            $this->createTsvTextHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_NAME:
            $this->createTsvNameHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO:
            $this->createTsvBiblioHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_LINK:
            $this->createTsvLinkHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_FILE:
            $this->createTsvFileHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_HEADING:
            $this->createTsvHeadingHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE:
            $this->createTsvFilePriceHeader($attribute_name, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        case RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA:
            $this->createTsvTextareaHeader($attribute_name, $baseAttributeName, $headerArray, $headerStr, $HeaderNameArray, $columnNo);
            break;
        default:
            break;
        }
    }
    /**
     * TSVヘッダーテキスト情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvTextHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXT] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $attribute_name);
        $columnNo++;
        $attribute_name = $this->checkArrayInput($attribute_name, $HeaderNameArray[self::HEADER]);
        $headerStr .= $attribute_name."\t";
        array_push($HeaderNameArray[self::HEADER], $attribute_name);
    }
    /**
     * TSVヘッダー氏名情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvNameHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        if($this->authorPrefixNum == null){
            $query = "SELECT count(prefix_id) ".
                     "FROM ".DATABASE_PREFIX."repository_external_author_id_prefix ".
                     "WHERE prefix_id > 0 ".
                     "AND is_delete = 0;";
            $result = $this->Db->execute($query);
            if ( $result == false ){
                return;
            }
            $this->authorPrefixNum = $result[0]['count(prefix_id)'];
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_NAME] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_NAME], $attribute_name);
        $columnNo += 3 + $this->authorPrefixNum;
        $setName = $attribute_name.$this->header_author_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_author_ruby;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_author_email;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_author_id;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        for($ii = 0; $ii < $this->authorPrefixNum; $ii++){
            $headerStr .= $setName."\t";
            $this->authorPrefixCounter++;
        }
        $this->authorPrefixCounter--;
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダー書誌情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvBiblioHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO], $attribute_name);
        $columnNo += 7;
        $setName = $attribute_name.$this->header_biblio_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_name_english;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_volume;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_issue;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_startpage;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_endpage;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_biblio_date;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダーリンク情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvLinkHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_LINK] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_LINK], $attribute_name);
        $columnNo += 2;
        $setName = $attribute_name.$this->header_link_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_link_url;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダーファイル情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvFileHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_FILE] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_FILE], $attribute_name);
        $columnNo += 6;
        $setName = $attribute_name.$this->header_file_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_display_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_date;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_flash_pubdate;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_cc_license;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_notation;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダー見出し情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvHeadingHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_HEADING] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_HEADING], $attribute_name);
        $columnNo += 4;
        $setName = $attribute_name.$this->header_heading;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_heading_english;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_heading_small;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_heading_small_english;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダー課金ファイル情報作成処理
     *
     * @param attribute_name I/O ヘッダー名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvFilePriceHeader($attribute_name, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE], $attribute_name);
        $columnNo += 8;
        $setName = $attribute_name.$this->header_file_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_display_name;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_date;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_flash_pubdate;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_cc_license;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_notation;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_price_non;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
        $setName = $attribute_name.$this->header_file_price_member;
        $setName = $this->checkArrayInput($setName, $HeaderNameArray[self::HEADER]);
        $headerStr .= $setName."\t";
        array_push($HeaderNameArray[self::HEADER], $setName);
    }
    /**
     * TSVヘッダーテキストエリア情報作成処理
     *
     * @param attribute_name I ヘッダー名 
     * @param baseAttributeName I メタデータ項目名 
     * @param headerArray I/O ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param headerStr I/O ヘッダー文字列
     * @param HeaderNameArray 使用されているヘッダー名リスト
     * @param columnNo I/O 列番号
     */
    private function createTsvTextareaHeader($attribute_name, $baseAttributeName, &$headerArray, &$headerStr, &$HeaderNameArray, &$columnNo)
    {
        $num = 1;
        $query = "SELECT MAX(attr.count) ".
                 "FROM ( ".
                 " SELECT item_id, item_no, attribute_id, count(attribute_no) as count ".
                 " FROM " . DATABASE_PREFIX ."repository_item_attr ".
                 " WHERE (`item_type_id`, `attribute_id`) IN ".
                 " ( ".
                 "  SELECT item_type_id, attribute_id ".
                 "  FROM " . DATABASE_PREFIX ."repository_item_attr_type ".
                 "  WHERE attribute_name = ? ".
                 "  AND input_type = '". RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA ."' ".
                 " ) ".
                 " GROUP BY item_id, item_no, attribute_id ".
                 ") AS attr;";
        $params = array();
        $params[] = $baseAttributeName;
        $result = $this->Db->execute($query, $params);
        if ( $result == false ){
            return;
        }
        if(count($result) > 0){
            $num = $result[0]['MAX(attr.count)'];
        }
        if(!array_key_exists($attribute_name, $headerArray)){
            $headerArray[$attribute_name] = array();
        }
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA] = array();
        $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA] = $columnNo;
        array_push($HeaderNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA], $attribute_name);
        $attribute_name = $this->checkArrayInput($attribute_name, $HeaderNameArray[self::HEADER]);
        for($ii = 0; $ii < $num; $ii++){
            $headerStr .= $attribute_name."\t";
            $this->textareaCounter++;
        }
        $columnNo += $num;
        array_push($HeaderNameArray[self::HEADER], $attribute_name);
        $this->textareaCounter--;
    }
    
    /**
     * TSVヘッダー情報デフォルト部分作成処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param tsvStr I/O 出力文字列
     */
    private function setTsvDefaultData($headerArray, $itemData, &$tsvDataArray){
        // WEKO_URLを設定
        $tsvDataArray[$headerArray[$this->header_weko_url][RepositoryConst::ITEM_ATTR_TYPE_TEXT]]
            = BASE_URL."/?action=repository_uri&item_id=".$itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID];
        // アイテムタイプを設定    
        $value = $this->prepareforTSV($itemData['item_type'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_NAME]);
        $tsvDataArray[$headerArray[$this->header_item_type][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // タイトルを設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]);
        $tsvDataArray[$headerArray[$this->header_title][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // タイトル(英)を設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]);
        $tsvDataArray[$headerArray[$this->header_title_english][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // 言語を設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_LANGUAGE]);
        $tsvDataArray[$headerArray[$this->header_language][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // キーワードを設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY]);
        $tsvDataArray[$headerArray[$this->header_keyword][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // キーワード(英)を設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_SEARCH_KEY_ENGLISH]);
        $tsvDataArray[$headerArray[$this->header_keyword_english][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = $value;
        // 公開日を設定
        $value = $this->prepareforTSV($itemData['item'][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_SHOWN_DATE]);
        $tsvDataArray[$headerArray[$this->header_shown_date][RepositoryConst::ITEM_ATTR_TYPE_TEXT]] = substr($value, 0, 10);
    }
    /**
     * TSVテキスト情報登録処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param itemData I 登録アイテムデータ
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setTsvAttrTypeData($headerArray, $itemData, $is_insUser, &$tsvDataArray)
    {
        $useNameArray = array();
        $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT] = array($this->header_weko_url => -1, $this->header_item_type => -1, $this->header_title => -1, $this->header_title_english => -1
                                    , $this->header_language => -1, $this->header_keyword => -1, $this->header_keyword_english => -1, $this->header_shown_date => -1);

        $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_LINK] = array();
        $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_HEADING] = array();
        $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA] = array();
        for($ii = 0; $ii < count($itemData[self::ATTR]); $ii++){
            if($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            // textとなる項目
            if($itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_SELECT || $itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_DATE 
                    || $itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_CHECKBOX || $itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_RADIO
                    || $itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_TEXT){                
                $attribute_name = $this->checkArrayDataInput($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                    , $itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $isSetData);
                $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXT];
                $value = $this->prepareforTSV($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE]);
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = $value;
                } else {
                    $tsvDataArray[$columnNo] .= "|".$value;
                }
            // link項目
            } else if($itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_LINK){
                $attribute_name = $this->checkArrayDataInput($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                    , $itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_LINK], $isSetData);
                $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_LINK];
                $value = $this->prepareforTSV($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE]);
                $valueArray = preg_split('/[|]/', $value, 3);
                if(count($valueArray) == 2){
                    if(!$isSetData){
                        $tsvDataArray[$columnNo] = $valueArray[1];
                        $tsvDataArray[$columnNo+1] = $valueArray[0];
                    } else {
                        $tsvDataArray[$columnNo] .= "|".$valueArray[1];
                        $tsvDataArray[$columnNo+1] .= "|".$valueArray[0];
                    }
                }
            // heading項目
            } else if($itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_HEADING){
                $attribute_name = $this->checkArrayDataInput($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                    , $itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_HEADING], $isSetData);
                $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_HEADING];
                $value = $this->prepareforTSV($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE]);
                $valueArray = preg_split('/[|]/', $value, 5);
                if(count($valueArray) == 4){
                    if(!$isSetData){
                        $tsvDataArray[$columnNo] = $valueArray[0];
                        $tsvDataArray[$columnNo+1] = $valueArray[1];
                        $tsvDataArray[$columnNo+2] = $valueArray[2];
                        $tsvDataArray[$columnNo+3] = $valueArray[3];
                    } else {
                        $tsvDataArray[$columnNo] .= "|".$valueArray[0];
                        $tsvDataArray[$columnNo+1] .= "|".$valueArray[1];
                        $tsvDataArray[$columnNo+2] .= "|".$valueArray[2];
                        $tsvDataArray[$columnNo+3] .= "|".$valueArray[3];
                    }
                }
            // textarea項目
            } else if($itemData[self::ATTR][$ii][self::INPUT_TYPE] == RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA){
                $attribute_name = $this->checkArrayDataInput($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                    , $itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA], $isSetData);
                $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXTAREA];
                $value = $this->prepareforTSV($itemData[self::ATTR][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_ATTRIBUTE_VALUE]);
                while(1){
                    if($tsvDataArray[$columnNo] == ""){
                        $tsvDataArray[$columnNo] = $value;
                        break;
                    } else {
                        $columnNo++;
                    }
                }
            }
        }
        for($ii = 0; $ii < count($itemData[self::THUMBNAIL]); $ii++){
            if($itemData[self::THUMBNAIL][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            $attribute_name = $this->checkArrayDataInput($itemData[self::THUMBNAIL][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
                , $itemData[self::THUMBNAIL][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray[RepositoryConst::ITEM_ATTR_TYPE_TEXT], $isSetData);
            $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_TEXT];
            $value = $itemData[self::THUMBNAIL][$ii][RepositoryConst::DBCOL_REPOSITORY_THUMB_FILE_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
        }
    }
    /**
     * TSV氏名情報登録処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param itemData I 登録アイテムデータ
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setTsvNameData($headerArray, $itemData, $is_insUser, &$tsvDataArray)
    {
        $useNameArray = array();
        for($ii = 0; $ii < count($itemData[self::NAME]); $ii++){
            if($itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            $attribute_name = $this->checkArrayDataInput($itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
            , $itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray, $isSetData);
            $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_NAME];
            $value = $itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY].",".$itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY_RUBY].",".$itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME_RUBY];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::NAME][$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_E_MAIL_ADDRESS];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            // 著者IDの設定
            $IDArray = $itemData[self::NAME][$ii][self::AUTHOR_ID];
            for($jj = 0; $jj < $this->authorPrefixNum; $jj++){
                $value = "";
                if($jj < count($IDArray)){
                    $value = $IDArray[$jj]['prefix_name'].":".$IDArray[$jj]['suffix'];
                    $value = $this->prepareforTSV($value);
                }
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = $value;
                } else {
                    $tsvDataArray[$columnNo] .= "|".$value;
                }
                $columnNo++;
            }
        }
    }
    /**
     * TSV書誌情報登録処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param itemData I 登録アイテムデータ
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setTsvBiblioData($headerArray, $itemData, $is_insUser, &$tsvDataArray)
    {
        $useNameArray = array();
        for($ii = 0; $ii < count($itemData[self::BIBLIO]); $ii++){
            if($itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            $attribute_name = $this->checkArrayDataInput($itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
            , $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray, $isSetData);
            $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_BIBLIOINFO];
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME_ENGLISH];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_VOLUME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ISSUE];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_START_PAGE];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_END_PAGE];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::BIBLIO][$ii][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_DATE_OF_ISSUED];
            $value = $this->prepareforTSV($value);
            $value = substr($value,0,10);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
        }
    }
    
    /**
     * TSVファイルライセンス情報登録処理
     *
     * @param itemData I 登録アイテムデータ
     * @param isSetData I 既にデータがセットされているかのフラグ
     * @param columnNo I/O カラム番号
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setLicense($fileData, $isSetData, &$columnNo, &$tsvDataArray)
    {
        switch($fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID]){
            case 101:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            case 102:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY-SA";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY-SA";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            case 103:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY-ND";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY-ND";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            case 104:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY-NC";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY-NC";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            case 105:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY-NC-SA";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY-NC-SA";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            case 106:
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "BY-NC-ND";
                    $tsvDataArray[$columnNo+1] = "";
                } else {
                    $tsvDataArray[$columnNo] .= "|BY-NC-ND";
                    $tsvDataArray[$columnNo+1] .= "|";
                }
                break;
            default:
                $value = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION];
                $value = $this->prepareforTSV($value);
                if(!$isSetData){
                    $tsvDataArray[$columnNo] = "";
                    $tsvDataArray[$columnNo+1] = $value;
                } else {
                    $tsvDataArray[$columnNo] .= "|";
                    $tsvDataArray[$columnNo+1] .= "|".$value;
                }
                break;
        }
        $columnNo += 2;
    }
    /**
     * TSVファイル情報登録処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param itemData I 登録アイテムデータ
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setTsvFileData($headerArray, $itemData, $is_insUser, &$tsvDataArray)
    {
        $useNameArray = array();
        for($ii = 0; $ii < count($itemData[self::FILE]); $ii++){
            if($itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            $status = $this->RepositoryValidator->checkFileAccessStatus($itemData[self::FILE][$ii]);
            if( $status != "free" && $status != "already" && $status != "admin" && $status != "license" )
            {
                continue;
            }
            $attribute_name = $this->checkArrayDataInput($itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
            , $itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray, $isSetData);
            $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_FILE];
            $value = $itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE];
            $value = $this->prepareforTSV($value);
            $value = substr($value,0,10);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::FILE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE];
            $value = $this->prepareforTSV($value);
            $value = substr($value,0,10);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $this->setLicense($itemData[self::FILE][$ii], $isSetData, $columnNo, $tsvDataArray);
        }
    }
        
    /**
     * TSV課金ファイル情報登録処理
     *
     * @param headerArray I ヘッダー情報配列  1次キー：項目名、2次キー：input_type
     * @param itemData I 登録アイテムデータ
     * @param tsvDataArray I/O TSV出力情報
     */
    private function setTsvFilePriceData($headerArray, $itemData, $is_insUser, &$tsvDataArray)
    {
        $useNameArray = array();
        for($ii = 0; $ii < count($itemData[self::FILE_PRICE]); $ii++){
            if($itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 1){
                if(!$this->isAdminUser && !$is_insUser){
                    continue;
                }
            }
            $status = $this->RepositoryValidator->checkFileAccessStatus($itemData[self::FILE_PRICE][$ii]);
            if( $status != "free" && $status != "already" && $status != "admin" && $status != "license" )
            {
                continue;
            }
            $attribute_name = $this->checkArrayDataInput($itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_NAME]
            , $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID], $useNameArray, $isSetData);
            $columnNo = $headerArray[$attribute_name][RepositoryConst::ITEM_ATTR_TYPE_FILEPRICE];
            $value = $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_NAME];
            $value = $this->prepareforTSV($value);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;
            $value = $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE];
            $value = $this->prepareforTSV($value);
            $value = substr($value,0,10);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;            
            $value = $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_FLASH_PUB_DATE];
            $value = $this->prepareforTSV($value);
            $value = substr($value,0,10);
            if(!$isSetData){
                $tsvDataArray[$columnNo] = $value;
            } else {
                $tsvDataArray[$columnNo] .= "|".$value;
            }
            $columnNo++;            
            $this->setLicense($itemData[self::FILE_PRICE][$ii], $isSetData, $columnNo, $tsvDataArray);
            $value = $itemData[self::FILE_PRICE][$ii][RepositoryConst::DBCOL_REPOSITORY_FILE_PRICE_PRICE];
            $value = $this->prepareforTSV($value);
            $valueArray = preg_split('/[|]/', $value);
            for($jj = 0; $jj < count($valueArray); $jj++){
                $priceArray = preg_split('/[,]/', $valueArray[$jj]);
                if($priceArray[0] == 0){
                    $tsvDataArray[$columnNo] = $priceArray[1];
                } else {
                    $query = "SELECT page_name FROM ". DATABASE_PREFIX ."pages ".
                             "WHERE room_id = ?; ";
                    $params = array();
                    $params = $priceArray[0]; 
                    $pages = $this->Db->execute( $query, $params );
                    // Bug Fix WEKO-2014-071 T.Koyasu 2014/08/19 --start--
                    // miss condition, member price is not null and string length more than 0 -> right condition
                    if(!isset($tsvDataArray[$columnNo+1]) || strlen($tsvDataArray[$columnNo+1]) == 0){
                        $tsvDataArray[$columnNo+1] = $pages[0]['page_name'].",".$priceArray[1];
                    } else {
                        $tsvDataArray[$columnNo+1] .= "|".$pages[0]['page_name'].",".$priceArray[1];
                    }
                    // Bug Fix WEKO-2014-071 T.Koyasu 2014/08/19 --start--
                }
            }
        }
    }
       
    /**
     * [[アイテムID,アイテム通番で指定されるアイテムのデータをすべて取得する]]
     * @access public
     * @return true:正常終了
     *          →$Result_Listにレコード
     *         false:異常終了
     *          →$Error_MsgにエラーメッセージorSessionにエラーコード
     *          →途中で落ちた場合、$Result_Listにはそれまでのデータが入っている
     */
    private function getItemDataForTsv(
        $Item_ID,         // アイテムID
        $Item_No,         // アイテム通番
        &$Result_List,    // DBから取得したレコードの集合
        &$Error_Msg      // エラーメッセージ
        )
    {
        // アイテムIDとアイテム通番からアイテムテーブルのデータを取得 $Result_List["item"]
        $search_result = $this->getItemTableData($Item_ID,$Item_No,$Result_List,$Error_Msg);
        if($search_result === false){
            return false;
        }
        // アイテムテーブルのデータからアイテムタイプIDを取得 
        $item_type_id = $Result_List['item'][0]['item_type_id'];
        
        // アイテムタイプIDからアイテムタイプテーブルのデータを取得 $Result_List["item_type"]
        $search_result = $this->getItemTypeTableData($item_type_id,$Result_List,$Error_Msg,$blob_flag);
        if($search_result === false){
            return false;
        }

        // アイテムタイプIDからアイテム属性タイプテーブルのデータを取得 $Result_List["item_attr_type"]
        $search_result = $this->getItemAttrTypeTableData($item_type_id,$Result_List,$Error_Msg);
        if($search_result === false){
            return false;
        }
        
        // テキストデータ(item_attr)を取得
        $query = "SELECT attribute_no, attribute_value, TYPE.attribute_id AS attribute_id, attribute_name, input_type, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_item_attr AS ATTR ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE ATTR.item_id = ? AND ".
                 "ATTR.item_no = ? AND ".
                 "ATTR.item_type_id = TYPE.item_type_id AND ".
                 "ATTR.attribute_id = TYPE.attribute_id AND ".
                 "ATTR.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_Attr = $this->Db->execute($query, $params);
        if($result_Attr === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List[self::ATTR] = $result_Attr;
        
        // 氏名を取得
        $query = "SELECT family, name, family_ruby, name_ruby, e_mail_address, author_id, TYPE.attribute_id, attribute_name, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_personal_name AS NAME ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE NAME.item_id = ? AND ".
                 "NAME.item_no = ? AND ".
                 "NAME.item_type_id = TYPE.item_type_id AND ".
                 "NAME.attribute_id = TYPE.attribute_id AND ".
                 "NAME.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_Personal_Name_Table = $this->Db->execute($query, $params);
        if($result_Personal_Name_Table === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        for($ii = 0; $ii < count($result_Personal_Name_Table); $ii++){
            // 氏名を取得
            $query = "SELECT prefix_name, suffix ". 
                     "FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix AS SUFFIX ".
                     ", ". DATABASE_PREFIX ."repository_external_author_id_prefix AS PREFIX ".
                     "WHERE SUFFIX.author_id = ? AND ".
                     "SUFFIX.prefix_id = PREFIX.prefix_id AND ".
                     "PREFIX.prefix_id > 0 AND ".
                     "SUFFIX.is_delete = ? AND ".
                     "PREFIX.is_delete = ? ;";     // 氏名通番順にソート        
            // $queryの?を置き換える配列
            $params = null;
            $params[] = $result_Personal_Name_Table[$ii][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_AUTHOR_ID];
            $params[] = 0;
            $params[] = 0;
            // SELECT実行
            $result_Author_ID = $this->Db->execute($query, $params);
            if($result_Author_ID === false){
                $Error_Msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                return false;
            }
            $result_Personal_Name_Table[$ii][self::AUTHOR_ID] = $result_Author_ID;
        }
        // レコード格納
        $Result_List[self::NAME] = $result_Personal_Name_Table;
        
        // サムネイルを取得
        $query = "SELECT file_name, THUM.attribute_id, attribute_name, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail AS THUM ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE THUM.item_id = ? AND ".
                 "THUM.item_no = ? AND ".
                 "THUM.item_type_id = TYPE.item_type_id AND ".
                 "THUM.attribute_id = TYPE.attribute_id AND ".
                 "THUM.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id, THUM.show_order ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_Thumbnail_Table = $this->Db->execute($query, $params);
        if($result_Thumbnail_Table === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        $Result_List[self::THUMBNAIL] = $result_Thumbnail_Table;
        
        // 書誌情報を取得
        $query = "SELECT biblio_name, biblio_name_english, volume, issue, start_page, end_page, date_of_issued, TYPE.attribute_id, attribute_name, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_biblio_info AS BIB ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE BIB.item_id = ? AND ".
                 "BIB.item_no = ? AND ".
                 "BIB.item_type_id = TYPE.item_type_id AND ".
                 "BIB.attribute_id = TYPE.attribute_id AND ".
                 "BIB.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_Biblio_Table = $this->Db->execute($query, $params);
        if($result_Biblio_Table === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List[self::BIBLIO] = $result_Biblio_Table;
        
        // ファイル情報を取得
        $query = "SELECT FILE.* , attribute_name, input_type, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_file AS FILE ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE FILE.item_id = ? AND ".
                 "FILE.item_no = ? AND ".
                 "FILE.item_type_id = TYPE.item_type_id AND ".
                 "FILE.attribute_id = TYPE.attribute_id AND ".
                 "TYPE.input_type = ? AND ".
                 "FILE.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id, FILE.show_order ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = self::FILE;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_File_Table = $this->Db->execute($query, $params);
        if($result_File_Table === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List[self::FILE] = $result_File_Table;
        // 課金ファイル情報を取得
        $query = "SELECT FILE.*, price, TYPE.attribute_id AS attribute_id, attribute_name, input_type, TYPE.hidden AS hidden ". 
                 "FROM ". DATABASE_PREFIX ."repository_file AS FILE ".
                 ", ". DATABASE_PREFIX ."repository_file_price AS PRICE ".
                 ", ". DATABASE_PREFIX ."repository_item_attr_type AS TYPE ".
                 "WHERE FILE.item_id = ? AND ".
                 "FILE.item_no = ? AND ".
                 "FILE.item_type_id = TYPE.item_type_id AND ".
                 "FILE.attribute_id = TYPE.attribute_id AND ".
                 "FILE.item_id = PRICE.item_id AND ".
                 "FILE.item_no = PRICE.item_no AND ".
                 "FILE.attribute_id = PRICE.attribute_id AND ".
                 "FILE.file_no = PRICE.file_no AND ".
                 "TYPE.input_type = ? AND ".
                 "FILE.is_delete = ? AND ".
                 "PRICE.is_delete = ? AND ".
                 "TYPE.is_delete = ? ".
                 "ORDER BY TYPE.attribute_id, FILE.show_order ASC;";
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = self::FILE_PRICE;
        $params[] = 0;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result_File_Price_Table = $this->Db->execute($query, $params);
        if($result_File_Price_Table === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List[self::FILE_PRICE] = $result_File_Price_Table;
        
        // Add e-person R.Matsuura  2013/10/24 --start--
        // feedback mail address 
        $exportCommon = new ExportCommon($this->Db, $this->Session, $this->TransStartDate);
        $result_Mail_Address = $exportCommon->getFeedbackMailFromDb($Item_ID, $Item_No);
        if($result_Mail_Address === false){
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        $Result_List[self::FEEDBACK_MAIL] = $result_Mail_Address;
        // Add e-person R.Matsuura  2013/10/24 --end--
        
        // self DOI
        $handleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        $jalcdoiSuffix = $handleManager->getJalcdoiSuffix($Item_ID, $Item_No);
        if(isset($jalcdoiSuffix) && strlen($jalcdoiSuffix) > 0)
        {
            $Result_List[self::SELF_DOI_RA][0]["RA"] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
            $Result_List[self::SELF_DOI][0]["SELFDOI"] = "";
        }
        else
        {
            $crossrefSuffix = $handleManager->getCrossrefSuffix($Item_ID, $Item_No);
            if(isset($crossrefSuffix) && strlen($crossrefSuffix) > 0)
            {
                $Result_List[self::SELF_DOI_RA][0]["RA"] = RepositoryConst::JUNII2_SELFDOI_RA_CROSSREF;
                $Result_List[self::SELF_DOI][0]["SELFDOI"] = "";
            }
            else
            {
                $libraryJalcdoiSuffix = $handleManager->getLibraryJalcdoiSuffix($Item_ID, $Item_No);
                if(isset($libraryJalcdoiSuffix) && strlen($libraryJalcdoiSuffix) > 0)
                {
                    $Result_List[self::SELF_DOI_RA][0]["RA"] = RepositoryConst::JUNII2_SELFDOI_RA_JALC;
                    $libraryJalcdoiPrefix = $handleManager->getLibraryJalcDoiPrefix();
                    $Result_List[self::SELF_DOI][0]["SELFDOI"] = $libraryJalcdoiPrefix."/".$libraryJalcdoiSuffix;
                }
        		// Add DataCite 2015/02/10 K.Sugimoto --start--
                else
                {
	                $dataciteSuffix = $handleManager->getDataciteSuffix($Item_ID, $Item_No);
	                if(isset($dataciteSuffix) && strlen($dataciteSuffix) > 0)
	                {
	                    $Result_List[self::SELF_DOI_RA][0]["RA"] = RepositoryConst::JUNII2_SELFDOI_RA_DATACITE;
	                    $Result_List[self::SELF_DOI][0]["SELFDOI"] = "";
	                }
                }
        		// Add DataCite 2015/02/10 K.Sugimoto --end--
            }
        }
        if($jalcdoiSuffix === false)
        {
            $Error_Msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }

    }
    
    /**
     * TSV feedbackmail Information Regist
     *
     * @param headerArray
     * @param itemData
     * @param tsvdataArray
     */
    private function setTsvFeedbackMail($headerArray, $itemData, &$tsvDataArray){
        $columnNo = $headerArray[$this->header_heading_feedback_mail][RepositoryConst::ITEM_ATTR_TYPE_TEXT];
        $tmpValue = "";
        for($ii = 0; $ii < count($itemData[self::FEEDBACK_MAIL]); $ii++){
            $value = $itemData[self::FEEDBACK_MAIL][$ii]['suffix'];
            $value = $this->prepareforTSV($value);
            if($ii > 0 && strlen($value) > 0){
                $tmpValue .= "|";
            }
            $tmpValue .= $value;
        }
        $tsvDataArray[$columnNo] = $tmpValue;
    }
    
    /**
     * TSV self DOI RA Information Regist
     *
     * @param headerArray
     * @param itemData
     * @param tsvdataArray
     */
    private function setTsvSelfDoiRa($headerArray, $itemData, &$tsvDataArray){
        $columnNo = $headerArray[$this->header_heading_self_doi_ra][RepositoryConst::ITEM_ATTR_TYPE_TEXT];
        $tmpValue = "";
        $value = $itemData[self::SELF_DOI_RA][0]["RA"];
        $value = $this->prepareforTSV($value);
        $tmpValue = $value;
        $tsvDataArray[$columnNo] = $tmpValue;
    }
    
    /**
     * TSV self DOI Information Regist
     *
     * @param headerArray
     * @param itemData
     * @param tsvdataArray
     */
    private function setTsvSelfDoi($headerArray, $itemData, &$tsvDataArray){
        $columnNo = $headerArray[$this->header_heading_self_doi][RepositoryConst::ITEM_ATTR_TYPE_TEXT];
        $tmpValue = "";
        $value = $itemData[self::SELF_DOI][0]["SELFDOI"];
        $value = $this->prepareforTSV($value);
        $tmpValue = $value;
        $tsvDataArray[$columnNo] = $tmpValue;
    }
    
    /**
     * check authority
     *
     * @param itemDataArray
     * @return exportDataArray
     */
     private function checkAuthority($itemDataArray)
     {
        // check admin user
        // access user is admin user?
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");
        $this->isAdminUser = false;
        if( $user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
        {
            $this->isAdminUser = true;
        }
        $exportDataArray = array();
        
        // Add Advanced Search 2013/11/26 R.Matsuura --start--
        $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Add Advanced Search 2013/11/26 R.Matsuura --end--
        
        for($ii = 0; $ii < count($itemDataArray); $ii++){
            // access user is ins user?
            $insUser = false;
            if($user_id == $itemDataArray[$ii][RepositoryConst::DBCOL_COMMON_INS_USER_ID])
            {
                $insUser = true;
            }
            if($this->isAdminUser || $insUser ||
                $itemAuthorityManager->checkItemPublicFlg($itemDataArray[$ii]["item_id"], $itemDataArray[$ii]["item_no"], $this->repository_admin_base, $this->repository_admin_room)
            ){
                // close item.
                $export_info = $itemDataArray[$ii];
                $export_info[self::IS_INSUSER] = $insUser;
                array_push($exportDataArray, $export_info);
            }
        }
        return $exportDataArray;
     }
     
     // add filter update 2014/02/18 R.Matsuura --start--
     /**
     * output tsv header
     *
     * @param itemTypeId
     * @return tsv_header_info
     */
     public function getTsvHeader($itemTypeId)
     {
        $item_type_id_array = array();
        $item_type_id_array[0]['item_type_id'] = $itemTypeId;
        
        $tsv_header_info = '';
        
        $this->createTsvHeader($item_type_id_array, $tsv_header_info);
        
        return $tsv_header_info;
     }
     // add filter update 2014/02/18 R.Matsuura --end--
}

?>
