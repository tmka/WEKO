<?php
// --------------------------------------------------------------------
//
// $Id: Edittexts.class.php 58457 2015-10-06 02:18:19Z tatsuya_koyasu $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';

/**
 * アイテム登録：メタデータ入力画面からの入力処理アクション
 *
 * @access      public
 */
class Repository_Action_Main_Item_Edittexts extends RepositoryAction
{
    // リクエストパラメーター
    /**
     * 基本情報部分のリクエストパラメタ配列
     * @var array
     */
    public $base_attr = null;
    
    /**
     * アイテム公開日：年
     * @var string
     */
    public $item_pub_date_year = null;
    
    /**
     * アイテム公開日：月
     * @var string
     */
    public $item_pub_date_month = null;
    
    /**
     * アイテム公開日：日
     * @var string
     */
    public $item_pub_date_day = null;
    
    /**
     * キーワード
     * @var string
     */
    public $item_keyword = null;
    
    /**
     * キーワード(英)
     * @var string
     */
    public $item_keyword_english = null;
    
    /**
     * "text"属性入力値配列
     * @var array
     */
    public $item_attr_text = null;
    
    /**
     * "textarea"属性入力値配列
     * @var array
     */
    public $item_attr_textarea = null;
    
    /**
     * "checkbox"属性入力値配列
     * @var array
     */
    public $item_attr_checkbox = null;
    
    /**
     * "name"属性：姓 入力値配列
     * @var array
     */
    public $item_attr_name_family = null;
    
    /**
     * "name"属性：名 入力値配列
     * @var array
     */
    public $item_attr_name_given = null;
    
    /**
     * "name"属性：姓(英語) 入力値配列
     * @var array
     */
    public $item_attr_name_family_ruby = null;
    
    /**
     * "name"属性：名(英語) 入力値配列
     * @var array
     */
    public $item_attr_name_given_ruby = null;
    
    /**
     * "name"属性：E-mail 入力値配列
     * @var array
     */
    public $item_attr_name_email = null;
    
    /**
     * "name"属性：著者ID prefix 入力値配列
     * @var array
     */
    public $item_attr_name_author_id_prefix = null;
    
    /**
     * "name"属性：著者ID suffix 入力値配列
     * @var array
     */
    public $item_attr_name_author_id_suffix = null;
    
    /**
     * "select"属性入力値配列
     * @var array
     */
    public $item_attr_select = null;
    
    /**
     * "link"属性：URL 入力値配列
     * @var array
     */
    public $item_attr_link = null;
    
    /**
     * "link"属性：表示名 入力値配列
     * @var array
     */
    public $item_attr_link_name = null;
    
    /**
     * "radio"属性入力値配列
     * @var array
     */
    public $item_attr_radio = null;
    
    /**
     * 書誌情報：雑誌名 入力値配列
     * @var array
     */
    public $item_attr_biblio_name = null;
    
    /**
     * 書誌情報：雑誌名(英) 入力値配列
     * @var array
     */
    public $item_attr_biblio_name_english = null;
    
    /**
     * 書誌情報：巻 入力値配列
     * @var array
     */
    public $item_attr_biblio_volume = null;
    
    /**
     * 書誌情報：号 入力値配列
     * @var array
     */
    public $item_attr_biblio_issue = null;
    
    /**
     * 書誌情報：開始ページ 入力値配列
     * @var array
     */
    public $item_attr_biblio_spage = null;
    
    /**
     * 書誌情報：終了ページ 入力値配列
     * @var array
     */
    public $item_attr_biblio_epage = null;
    
    /**
     * 書誌情報：発行年月日 入力値配列
     * @var array
     */
    public $item_attr_biblio_dateofissued = null;
    
    /**
     * 書誌情報：発行年 入力値配列
     * @var array
     */
    public $item_attr_biblio_dateofissued_year = null;
    
    /**
     * 書誌情報：発行月 入力値配列
     * @var array
     */
    public $item_attr_biblio_dateofissued_month = null;
    
    /**
     * 書誌情報：発行日 入力値配列
     * @var array
     */
    public $item_attr_biblio_dateofissued_day = null;
    
    /**
     * 日付 入力値配列
     * @var array
     */
    public $item_attr_date = null;
    
    /**
     * 日付：年 入力値配列
     * @var array
     */
    public $item_attr_date_year = null;
    
    /**
     * 日付：月 入力値配列
     * @var array
     */
    public $item_attr_date_month = null;
    
    /**
     * 日付：日 入力値配列
     * @var array
     */
    public $item_attr_date_day = null;
    
    /**
     * 見出し：大見出し 入力値配列
     * @var array
     */
    public $item_attr_heading = null;
    
    /**
     * 見出し：大見出し(英) 入力値配列
     * @var array
     */
    public $item_attr_heading_en = null;
    
    /**
     * 見出し：小見出し 入力値配列
     * @var array
     */
    public $item_attr_heading_sub = null;
    
    /**
     * 見出し：小見出し(英) 入力値配列
     * @var array
     */
    public $item_attr_heading_sub_en = null;
    
    /**
     * Contributor：フラグ
     * @var string
     */
    public $item_contributor = null;
    
    /**
     * Contributor：ハンドル名
     * @var string
     */
    public $item_contributor_handle = null;
    
    /**
     * Contributor：会員氏名
     * @var string
     */
    public $item_contributor_name= null;
    
    /**
     * Contributor：e-mailアドレス
     * @var string
     */
    public $item_contributor_email = null;
    
    /**
     * フィードバックメール送信先メールアドレス
     * @var string
     */
    public $send_feedback_mail_address_mailaddresses = null;
    
    /**
     * フィードバックメール送信先ユーザー名
     * @var string
     */
    public $send_feedback_mail_address_authors = null;
    
    /**
     * 処理モード
     *   'selecttype'   : アイテムタイプ選択画面
     *   'files'        : ファイル選択画面
     *   'texts'        : メタデータ入力画面
     *   'links'        : リンク設定画面
     *   'doi'          : DOI設定画面
     *   'confirm'      : 確認画面
     *   'stay'         : save
     *   'next'         : go next page
     *   'add_row'      : 属性の数を増やす
     *   'up_row'       : 属性を入れ替える (attridx-th属性が上に)
     *   'down_row'     : 属性を入れ替える (attridx-th属性が下に)
     *   'add_author_id': 氏名属性の外部著者IDの入力欄を増やす
     *   'clear_author' : 氏名属性情報を削除する
     * @var string
     */
    public $save_mode = null;
    
    /**
     * 処理対象のメタデータ番号
     * @var string
     */
    public $target = null;
    
    /**
     * 処理対象の属性番号
     * @var string
     */
    public $attridx = null;
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        $saveFlag = false;
        if( $this->save_mode=="selecttype" || $this->save_mode=="files" || $this->save_mode=="texts" || $this->save_mode=="links" ||
            $this->save_mode=="doi" || $this->save_mode=="confirm" || $this->save_mode=="stay" || $this->save_mode=="next")
        {
            $saveFlag = true;
        }
        $errorMsgList = array();
        
        // インスタンス作成
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        
        // セッション情報取得
        $item_type_all = $this->Session->getParameter("item_type_all");         // 1.アイテムタイプ,  アイテムタイプのレコードをそのまま保存したものである。
        $item_attr_type = $this->Session->getParameter("item_attr_type");       // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_cand = $this->Session->getParameter("item_num_cand");         // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        $option_data = $this->Session->getParameter("option_data");             // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        $item_num_attr = $this->Session->getParameter("item_num_attr");         // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr_old = $this->Session->getParameter("item_attr");             // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $item_attr = array();       // 6.アイテム属性 。これはリクエストから全部作り直す
        $edit_flag = $this->Session->getParameter("edit_flag");                 // X.処理モード : edit_flag (0:新規作成, 1:既存編集)
        $sfmam = $this->Session->getParameter("feedback_mailaddress_str");
        $sfmaa = $this->Session->getParameter("feedback_mailaddress_author_str");
        $fma = $this->Session->getParameter("feedback_mailaddress_array");
        $fmaa = $this->Session->getParameter("feedback_mailaddress_author_array");
        
        // カウンタ
        $cnt_text = 0;      // "text"属性カウンタ
        $cnt_textarea = 0;  // "textarea"属性カウンタ
        $cnt_name = 0;      // "name"属性カウンタ
        $cnt_link = 0;      // "link"属性カウンタ
        $cnt_select = 0;    // "select"属性カウンタ
        $cnt_checkbox = 0;  // "checkbox"属性カウンタ
        $cnt_radio = 0;     // "radio"属性カウンタ
        $cnt_biblio = 0;    // "biblio_info"属性カウンタ
        $cnt_date = 0;      // "date"属性カウンタ
        $cnt_author_id = 0; // "name"属性外部著者IDカウンタ
        
        // ------------------------------------------------------------
        // セッション情報保存 (全オプション共通)
        // ------------------------------------------------------------     
        // タイトルに空白文字列が入力されたら空文字に変換
        if ($this->base_attr[0]===RepositoryConst::BLANK_WORD) {
            arra_push($this->warningMsg, $smartyAssign->getLang("repository_item_error_empty_title"));
            $this->base_attr[0] = '';
        }
        if ($this->base_attr[1]===RepositoryConst::BLANK_WORD) {
            arra_push($this->warningMsg, $smartyAssign->getLang("repository_item_error_empty_title"));
            $this->base_attr[1] = '';
        }
        
        // アイテム基本属性をセッションに保存
        $this->Session->setParameter("base_attr",  array( 
                "title" => ($this->base_attr[0]==' ') ? '' : $this->base_attr[0],
                "title_english" => ($this->base_attr[1]==' ') ? '' : $this->base_attr[1],
                "language" => $this->base_attr[2]
            )
        );
        
        // アイテム公開日ををセッションに保存
        $tmp_item_pub_date = $this->Session->getParameter("item_pub_date");
        $this->Session->setParameter("item_pub_date", array(
                "year" => ($this->item_pub_date_year == ' ') ? '' : $this->item_pub_date_year,
                "month" => $this->item_pub_date_month,
                "day" => $this->item_pub_date_day
            )
        );
        
        // アイテムキーワードをセッションに保存
        // ------------------------------------------------------------------
        // キーワード精査 (空白除去)
        // ------------------------------------------------------------------
        $keywords = split("[|]", $this->item_keyword);
        $keywords_en = split("[|]", $this->item_keyword_english);
        $item_keyword_new = '';
        $item_keyword_en_new = '';
        for($ii=0; $ii<count($keywords); $ii++) {
            $keywords[$ii] = trim($keywords[$ii]);
            $item_keyword_new = $item_keyword_new . $keywords[$ii];
            if($ii != count($keywords)-1) {
                $item_keyword_new = $item_keyword_new . '|';
            }
        }
        for($ii=0; $ii<count($keywords_en); $ii++) {
            $keywords_en[$ii] = trim($keywords_en[$ii]);
            $item_keyword_en_new = $item_keyword_en_new . $keywords_en[$ii];
            if($ii != count($keywords_en)-1) {
                $item_keyword_en_new = $item_keyword_en_new . '|';
            }               
        }
        $item_keyword = $item_keyword_new;
        $item_keyword_english = $item_keyword_en_new;
        $this->Session->setParameter("item_keyword", $item_keyword);
        $this->Session->setParameter("item_keyword_english", $item_keyword_english);
        
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        // ------------------------------------------------------------------
        // Contributor
        // ------------------------------------------------------------------
        $contributorUserId = "";
        $contributorErrorMsg = "";
        $item_contributor = null;
        if($this->item_contributor_handle == " ")
        {
            $this->item_contributor_handle = "";
        }
        if($this->item_contributor_name == " ")
        {
            $this->item_contributor_name = "";
        }
        if($this->item_contributor_email == " ")
        {
            $this->item_contributor_email = "";
        }
        
        if(strlen($this->item_contributor) > 0 && $this->item_contributor == "1")
        {
            // Get contributor's user_id
            $contributorFlag = false;
            $retStatus = $this->getUserIdForContributor(
                                    $this->item_contributor_handle,
                                    $this->item_contributor_name,
                                    $this->item_contributor_email,
                                    $contributorUserId);
            if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_SUCCESS)
            {
                $contributorFlag = true;
            }
            else if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST)
            {
                if($saveFlag)
                {
                    $contributorErrorMsg = $smartyAssign->getLang("repository_item_contributor_error_not_exist");
                }
            }
            else if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT)
            {
                if($saveFlag)
                {
                    $contributorErrorMsg = $smartyAssign->getLang("repository_item_contributor_error_conflict");
                }
            }
            else if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOAUTH)
            {
                if($saveFlag)
                {
                    $contributorErrorMsg = $smartyAssign->getLang("repository_item_contributor_error_no_authority");
                }
            }
            $item_contributor = array(
                    RepositoryConst::ITEM_CONTRIBUTOR_HANDLE => $this->item_contributor_handle,
                    RepositoryConst::ITEM_CONTRIBUTOR_NAME => $this->item_contributor_name,
                    RepositoryConst::ITEM_CONTRIBUTOR_EMAIL => $this->item_contributor_email
            );
            if(!$contributorFlag)
            {
                $contributorUserId = $this->Session->getParameter(RepositoryConst::SESSION_PARAM_ORG_CONTRIBUTOR_USER_ID);
            }
        }
        else
        {
            $contributorUserId = $this->Session->getParameter("_user_id");
        }
        
        // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --start--
        $indice = $this->Session->getParameter("indice");
        $indice = $this->addPrivateTreeInPositionIndex($indice, $contributorUserId);
        $this->Session->setParameter("indice", $indice);
        // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --end--
        
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID, $contributorUserId);
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_ITEM_CONTRIBUTOR, $item_contributor);
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_ERROR_MSG, $contributorErrorMsg);
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
        
        // Fix PHP Notice 2014/06/06 Y.Nakao --start--
        $item = array("item_id"=>"", "item_no"=>"");
        // Add registered info save action 2009/02/09 Y.Nakao --start--
        if($saveFlag){
            if($edit_flag == 0){
                // 新規登録時
                $item["item_id"] = intval($this->Db->nextSeq("repository_item"));
                $item["item_no"] = 1;
            } else if($edit_flag == 1){
                //既存編集時
                // 編集中のアイテムIDをセッションから取得
                $item["item_id"] = intval($this->Session->getParameter("edit_item_id"));
                $item["item_no"] = intval($this->Session->getParameter("edit_item_no"));
            }
            if(trim($this->base_attr[0]) == "" && trim($this->base_attr[1]) == ""){
                if($this->base_attr[2] == "ja"){
                    //WEKOの設定言語に依存せず、論文の言語で決めるので仮タイトル文字列はベタ書きです
                    $item["title"] = "タイトル無し";
                    $item["title_english"] = "";
                }else{
                    $item["title"] = "";
                    $item["title_english"] = "no title";
                }
            }else{
                $item["title"] = (trim($this->base_attr[0]));
                $item["title_english"] = (trim($this->base_attr[1]));
            }
            $item["language"] = $this->base_attr[2];
            $item["serch_key"] = $this->item_keyword;
            $item["serch_key_english"] = $this->item_keyword_english;
            if($edit_flag == 0){
                // new item
                $item["revision_no"] = 1;
                $item["prev_revision_no"] = 0;
            } else if($edit_flag == 1){
                // edit item
                $result = $this->getItemTableData($item["item_id"], $item["item_no"], $Result_List, $error);
                if($result === false){
                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($error);
                    $exception->addError($error);
                    throw $exception;
                }
                $item["revision_no"] = $Result_List["item"][0]["revision_no"];
                $item["prev_revision_no"] = $Result_List["item"][0]["revision_no"];
            }
            
            // Fix check invalid date 2011/06/17 A.Suzuki --start--
            $this->item_pub_date_year = trim($this->item_pub_date_year);
            if($this->checkDate($this->item_pub_date_year, $this->item_pub_date_month, $this->item_pub_date_day)){
                $item["pub_year"] = $this->item_pub_date_year;
                $item["pub_month"] = $this->item_pub_date_month;
                $item["pub_day"] = $this->item_pub_date_day;
            } else {
                // invalid date
                // Set item_pub_date
                $item["pub_year"] = $tmp_item_pub_date["year"];
                $item["pub_month"] = $tmp_item_pub_date["month"];
                $item["pub_day"] = $tmp_item_pub_date["day"];
                if($saveFlag){
                    $this->Session->setParameter("item_pub_date", array(
                            "year" => $tmp_item_pub_date["year"],
                            "month" => $tmp_item_pub_date["month"],
                            "day" => $tmp_item_pub_date["day"]
                        )
                    );
                }
            }
            // Fix check invalid date 2011/06/17 A.Suzuki --end--
            
            $item["item_type_id"] = $item_type_all["item_type_id"];
            $item["serch_key"] = $item_keyword;
            $item["serch_key_english"] = $item_keyword_english;
            if($edit_flag == 0){
                $result = $ItemRegister->entryItem($item, $error);
                if($result === false){
                    // update faild
                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($error);
                    $exception->addError($error);
                    throw $exception;
                }
                $this->Session->setParameter("edit_item_id", $item["item_id"]);
                $this->Session->setParameter("edit_item_no", $item["item_no"]);
                $this->Session->setParameter("edit_flag", 1);
                $edit_flag = 1;
            } else if($edit_flag == 1){
                $result = $ItemRegister->editItem($item["item_id"], $item["item_no"], $error);
                if($result === false){
                    // update faild
                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($error);
                    $exception->addError($error);
                    throw $exception;
                }
                $result = $ItemRegister->updateItem($item, $error);
                if($result === false){
                    // update faild
                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($error);
                    $exception->addError($error);
                    throw $exception;
                }
            }
            // ------------------------------------------------------------------
            // ファイル(サムネイル)以外の属性を全て論理削除
            // ------------------------------------------------------------------
            $result = $this->deleteItemAttrData($item["item_id"], $item["item_no"], $this->Session->getParameter("_user_id"), $error);
            if($result === false){
                // update faild
                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($error);
                $exception->addError($error);
                throw $exception;
            }
        }
        
        // アイテム追加属性(メタデータ)をセッションに保存
        // ii-thメタデータのリクエストを保存
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            $attr_elm = array();        // 1メタデータの値列
            $nCnt_attr = 0;             // 空白以外の属性個数
            $nCnt_attr_flg = 0;         // 空白精査用
            // ii-thメタデータのjj-th属性値のリクエストを保存
            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                $metadata = array();
                $metadata["item_id"] = "";
                if(isset($item["item_id"]))
                {
                    $metadata["item_id"] = $item["item_id"];
                }
                $metadata["item_no"] = "";
                if(isset($item["item_no"]))
                {
                    $metadata["item_no"] = $item["item_no"];
                }
                $metadata["attribute_id"] = $item_attr_type[$ii]["attribute_id"];
                $metadata["item_type_id"] = $item_type_all["item_type_id"];
                $metadata["input_type"] = $item_attr_type[$ii]['input_type'];
                // ii-thメタデータの入力形式ごとのリクエストを保存
                switch($item_attr_type[$ii]['input_type']) {
                    case 'text':
                        $metadata["attribute_no"] = $jj+1;
                        // 空白チェック
                        if($this->item_attr_text[$cnt_text]==' ') {
                            array_push($attr_elm, '');
                            $metadata["attribute_value"] = '';
                        } else {
                            if($saveFlag){
                                $nCnt_attr++;
                                $metadata["attribute_no"] = $nCnt_attr;
                            }
                            array_push($attr_elm, $this->item_attr_text[$cnt_text]);
                            $metadata["attribute_value"] = $this->item_attr_text[$cnt_text];
                        }
                        $cnt_text++;
                        break;
                    case 'link':
                        $metadata["attribute_no"] = $jj+1;
                        // 空白チェック
                        // URL
                        if($this->item_attr_link[$cnt_link]==' ') {
                            $link_url = "";
                        } else {
                            $link_url = $this->item_attr_link[$cnt_link];
                            $link_url = str_replace("|", "", $link_url);
                        }
                        // 表示名
                        if($this->item_attr_link_name[$cnt_link]==' ') {
                            $link_name = "";
                        } else {
                            $link_name = $this->item_attr_link_name[$cnt_link];
                            $link_name = str_replace("|", "", $link_name);
                        }
                        
                        if($link_url != ""){
                            if($saveFlag){
                                $nCnt_attr++;
                                $metadata["attribute_no"] = $nCnt_attr;
                            }
                        }
                        
                        if($link_name != ""){
                            array_push($attr_elm, $link_url."|".$link_name);
                            $metadata["attribute_value"] = $link_url."|".$link_name;
                        } else {
                            array_push($attr_elm, $link_url);
                            $metadata["attribute_value"] = $link_url;
                        }

                        $cnt_link++;
                        break;
                    case 'name':
                        $metadata["personal_name_no"] = $jj+1;
                        $family = '';
                        $given = '';
                        $family_ruby = '';
                        $given_ruby = '';
                        $email = '';
                        $author_id = 0;
                        $language = $item_attr_type[$ii]['display_lang_type'];
                        $external_author_id = array();
                        // Bug Fix WEKO-2015-029 2015/07/30 K.Sugimoto --start--
                        $no_mail_external_author_id = array();
                        // Bug Fix WEKO-2015-029 2015/07/30 K.Sugimoto --end--
                        
                        // 空白チェック
                        if($this->item_attr_name_family[$cnt_name]!=' ') {
                            $family = $this->item_attr_name_family[$cnt_name];
                        }
                        if($this->item_attr_name_given[$cnt_name]!=' ') {
                            $given = $this->item_attr_name_given[$cnt_name];
                        }
                        if($language == "japanese"){
                            if($this->item_attr_name_family_ruby[$cnt_name]!=' ') {
                                $family_ruby = $this->item_attr_name_family_ruby[$cnt_name];
                            }
                            if($this->item_attr_name_given_ruby[$cnt_name]!=' ') {
                                $given_ruby = $this->item_attr_name_given_ruby[$cnt_name];
                            }
                        }
                        
                        // 外部著者ID群を作成 --start--
                        if($this->item_attr_name_email[$cnt_name]!=' ') {
                            $email = $this->item_attr_name_email[$cnt_name];
                            // Bug Fix WEKO-2015-029 2015/07/31 K.Sugimoto --start--
                            array_push($external_author_id, array('prefix_id'=>0, 
                                                                  'suffix'=>$this->item_attr_name_email[$cnt_name], 
                                                                  'old_prefix_id'=>0, 
                                                                  'old_suffix'=>$item_attr_old[$ii][$jj]["email"], 
                                                                  'prefix_name'=>$NameAuthority->getExternalAuthorIdPrefixName(0)));
                            // Bug Fix WEKO-2015-029 2015/06/31 K.Sugimoto --end--
                        }
                        
                        for($kk=0; $kk<count($item_attr_old[$ii][$jj]["external_author_id"]); $kk++){
                            $external_author_id_prefix = '';
                            $external_author_id_suffix = '';
                            $external_author_id_prefix_name = '';
                            if($this->item_attr_name_author_id_prefix[$kk+$cnt_author_id]!=0) {
                                $external_author_id_prefix = $this->item_attr_name_author_id_prefix[$kk+$cnt_author_id];
                                $external_author_id_prefix_name = $NameAuthority->getExternalAuthorIdPrefixName($external_author_id_prefix);
                                if($this->item_attr_name_author_id_suffix[$kk+$cnt_author_id]!=' ') {
                                    $external_author_id_suffix = $this->item_attr_name_author_id_suffix[$kk+$cnt_author_id];
                                }
                            }
                            
                            if(strlen($external_author_id_prefix) > 0 && strlen($external_author_id_suffix) > 0)
                            {
                                array_push($external_author_id, array('prefix_id'=>$external_author_id_prefix, 'suffix'=>$external_author_id_suffix, 'old_prefix_id'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["prefix_id"], 'old_suffix'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["suffix"], 'prefix_name'=>$external_author_id_prefix_name));
                                // Bug Fix WEKO-2015-029 2015/07/30 K.Sugimoto --start--
                                array_push($no_mail_external_author_id, array('prefix_id'=>$external_author_id_prefix, 'suffix'=>$external_author_id_suffix, 'old_prefix_id'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["prefix_id"], 'old_suffix'=>$item_attr_old[$ii][$jj]["external_author_id"][$kk]["suffix"], 'prefix_name'=>$external_author_id_prefix_name));
                                // Bug Fix WEKO-2015-029 2015/07/30 K.Sugimoto --end--
                            }
                        }
                        // 外部著者ID群を作成--end--
                        
                        $cnt_author_id = $cnt_author_id + $kk;
                        
                        // Check old author_id 2010/11/02 A.Suzuki --start--
                        // author_id をチェック(author_id == 0 : 新規, author_id > 0: 既存)
                        if(intval($item_attr_old[$ii][$jj]["author_id"])==0 && $saveFlag && (strlen($family)!=0 || strlen($given)!=0 || strlen($email)!=0) )
                        {
                            // 著者IDを仮登録(実際の更新に関してはItemRegister内で実施する)
                            $author_id = 0;
                        } else {
                            // 既存のauthor_id
                            $author_id = intval($item_attr_old[$ii][$jj]["author_id"]);
                        }
                        // Check old author_id 2010/11/02 A.Suzuki --end--
                        // 氏名属性は1属性につき姓、名、E-mailを保存
                        array_push($attr_elm, array(
                                'family' => $family,
                                'given' => $given,
                                'family_ruby' => $family_ruby,
                                'given_ruby' => $given_ruby,
                                'email' => $email,
                                'author_id' => $author_id,
                                'language' => $language,
                                'external_author_id' => $no_mail_external_author_id // こちらはセッションに保存するデータであるので、外部著者ID群にメールアドレスを入れてはいけない
                            )
                        );
                        
                        $metadata["family"] = $family;
                        $metadata["name"] = $given;
                        $metadata["family_ruby"] = $family_ruby;
                        $metadata["name_ruby"] = $given_ruby;
                        $metadata["e_mail_address"] = $email;
                        $metadata["author_id"] = $author_id;
                        $metadata["language"] = $language;
                        if($saveFlag){
                            if($family != "" || $given != "" || $email != ""){
                                $nCnt_attr++;
                                $metadata["personal_name_no"] = $nCnt_attr;
                            }
                        }
                        $metadata["external_author_id"] = $external_author_id;
                        $cnt_name++;
                        break;
                    case 'textarea':
                        $metadata["attribute_no"] = $jj+1;
                        // 空白チェック
                        if($this->item_attr_textarea[$cnt_textarea]==' ') {
                            array_push($attr_elm, '');
                            $metadata["attribute_value"] = '';
                        } else {
                            if($saveFlag){
                                $nCnt_attr++;
                                $metadata["attribute_no"] = $nCnt_attr;
                            }
                            array_push($attr_elm, $this->item_attr_textarea[$cnt_textarea]);
                            $metadata["attribute_value"] = $this->item_attr_textarea[$cnt_textarea];
                        }
                        $cnt_textarea++;
                        break;
                    case 'select':
                        $metadata["attribute_no"] = $jj+1;
                        // S.Nonomura 2010/04/01 --start--
                        // Blank Check
                        if($this->item_attr_select[$cnt_select]==' ') {
                            array_push($attr_elm, '');
                            $metadata["attribute_value"] = '';
                        // S.Nonomura 2010/04/01 --end--
                        } else {
                            array_push($attr_elm, $this->item_attr_select[$cnt_select]);
                            $metadata["attribute_value"] = $this->item_attr_select[$cnt_select];
                        }
                        if($saveFlag){
                            $nCnt_attr++;
                            $metadata["attribute_no"] = $nCnt_attr;
                        }
                        $cnt_select++;
                        break;
                    case 'checkbox':
                        $metadata["attribute_no"] = array();
                        // 0 or 1で還ってくる(チェックボックスの数だけ)
                        $metadata["attribute_value"] = array();
                        for($kk=0; $kk<count($option_data[$ii]); $kk++){
                            array_push($attr_elm, $this->item_attr_checkbox[$cnt_checkbox]);    // チェックON
                            if($this->item_attr_checkbox[$cnt_checkbox] == 1){
                                $metadata["attribute_no"] = $jj + $kk + 1;
                                $metadata["attribute_value"] = $option_data[$ii][$kk];
                                if($saveFlag){
                                    $result = $ItemRegister->entryMetadata($metadata, $error);
                                    if($result === false){
                                        $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                        $exception = new AppException($error);
                                        $exception->addError($error);
                                        throw $exception;
                                    }
                                }
                            }
                            $cnt_checkbox++;
                        }
                        break;
                    case 'radio':
                        $metadata["attribute_no"] = $jj+1;
                        if($saveFlag){
                            $nCnt_attr++;
                            $metadata["attribute_no"] = $nCnt_attr;
                        }
                        // 選択番号が還ってくる。
                        array_push($attr_elm, $this->item_attr_radio[$cnt_radio]);
                        $metadata["attribute_value"] = $option_data[$ii][$this->item_attr_radio[$cnt_radio]];
                        $cnt_radio++;
                        break;
                    case 'biblio_info':
                        $biblio_name = '';
                        $biblio_name_english = '';
                        $volume = '';
                        $issue = '';
                        $spage = '';
                        $epage = '';
                        $year = '';
                        $month = '';
                        $day = '';
                        $dateofissued = '';
                        
                        // 空白チェック
                        if($this->item_attr_biblio_name[$cnt_biblio]!=' ') {
                            $biblio_name = $this->item_attr_biblio_name[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_name_english[$cnt_biblio]!=' ') {
                            $biblio_name_english = $this->item_attr_biblio_name_english[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_volume[$cnt_biblio]!=' ') {
                            $volume = $this->item_attr_biblio_volume[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_issue[$cnt_biblio]!=' ') {
                            $issue = $this->item_attr_biblio_issue[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_spage[$cnt_biblio]!=' ') {
                            $spage = $this->item_attr_biblio_spage[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_epage[$cnt_biblio]!=' ') {
                            $epage = $this->item_attr_biblio_epage[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_dateofissued_year[$cnt_biblio]!=' ') {
                            $year = trim($this->item_attr_biblio_dateofissued_year[$cnt_biblio]);
                        }
                        if($this->item_attr_biblio_dateofissued_month[$cnt_biblio]!=' ') {
                            $month = $this->item_attr_biblio_dateofissued_month[$cnt_biblio];
                        }
                        if($this->item_attr_biblio_dateofissued_day[$cnt_biblio]!=' ') {
                            $day = $this->item_attr_biblio_dateofissued_day[$cnt_biblio];
                        }
                        
                        // 発行年月日を連結する
                        if($year != '') {
                            $dateofissued = $year;
                            if($month != '') {
                                if (strlen($month) == 1) {
                                    $dateofissued = $dateofissued.'-0'.$month;
                                } else {
                                    $dateofissued = $dateofissued.'-'.$month;
                                }
                                if($day != '') {
                                    if (strlen($day) == 1) {
                                        $dateofissued = $dateofissued.'-0'.$day;
                                    } else {
                                        $dateofissued = $dateofissued.'-'.$day;
                                    }
                                }
                            }
                        }
                        
                        // 書誌情報属性は1属性につき雑誌名、巻、号、ページ、発行年を保存
                        array_push($attr_elm, array(
                                'biblio_name' => $biblio_name,
                                'biblio_name_english' => $biblio_name_english,
                                'volume' => $volume,
                                'issue' => $issue,
                                'spage' => $spage,
                                'epage' => $epage,
                                'date_of_issued' => $dateofissued,
                                'year' => $year,
                                'month' => $month,
                                'day' => $day
                            )
                        );
                        $metadata["biblio_no"] = $jj+1;
                        $metadata["biblio_name"] = $biblio_name;
                        $metadata["biblio_name_english"] = $biblio_name_english;
                        $metadata["volume"] = $volume;
                        $metadata["issue"] = $issue;
                        $metadata["start_page"] = $spage;
                        $metadata["end_page"] = $epage;
                        $metadata["date_of_issued"] = $dateofissued;
                        $cnt_biblio++;
                        if($saveFlag){
                            if($biblio_name!="" || $biblio_name_english!="" ||
                                $volume!="" || $issue!="" || $spage!="" ||
                                $epage!="" || $dateofissued!=""){
                                $nCnt_attr++;
                                $metadata["biblio_no"] = $nCnt_attr;
                            }
                        }
                        break;
                    case 'date':
                        $date_year = '';
                        $date_month = '';
                        $date_day = '';
                        $date = '';
                        
                        // 空白チェック
                        if($this->item_attr_date_year[$cnt_date]!=' ') {
                            $date_year = trim($this->item_attr_date_year[$cnt_date]);
                        }
                        if($this->item_attr_date_month[$cnt_date]!=' ') {
                            $date_month = $this->item_attr_date_month[$cnt_date];
                        }
                        if($this->item_attr_date_day[$cnt_date]!=' ') {
                            $date_day = $this->item_attr_date_day[$cnt_date];
                        }

                        // 年月日を連結する
                        if($date_year != '') {
                            $date = $date_year;
                            if($date_month != '') {
                                if (strlen($date_month) == 1) {
                                    $date = $date.'-0'.$date_month;
                                } else {
                                    $date = $date.'-'.$date_month;
                                }
                                if($date_day != '') {
                                    if (strlen($date_day) == 1) {
                                        $date = $date.'-0'.$date_day;
                                    } else {
                                        $date = $date.'-'.$date_day;
                                    }
                                }
                            }
                        }

                        array_push($attr_elm, array(
                                'date' => $date,
                                'date_year' => $date_year,
                                'date_month' => $date_month,
                                'date_day' => $date_day
                            )
                        );
                        $metadata["attribute_no"] = $jj+1;
                        $metadata["attribute_value"] = $date;
                        $cnt_date++;
                        if($saveFlag){
                            if($date!=""){
                                $nCnt_attr++;
                                $metadata["attribute_no"] = $nCnt_attr;
                            }
                        }
                        break;
                    case 'heading':
                        $metadata["attribute_no"] = $jj+1;
                        $heading = "";
                        $heading_en = "";
                        $heading_sub = "";
                        $heading_sub_en = "";
                        // check string empty
                        $this->item_attr_heading = str_replace("|", "", $this->item_attr_heading);
                        if($this->item_attr_heading!=' ') {
                            $heading = $this->item_attr_heading; 
                        }
                        $this->item_attr_heading_en = str_replace("|", "", $this->item_attr_heading_en);
                        if($this->item_attr_heading_en!=' ') {
                            $heading_en = $this->item_attr_heading_en;
                        }
                        $this->item_attr_heading_sub = str_replace("|", "", $this->item_attr_heading_sub);
                        if($this->item_attr_heading_sub!=' ') {
                            $heading_sub = $this->item_attr_heading_sub;
                        }
                        $this->item_attr_heading_sub_en = str_replace("|", "", $this->item_attr_heading_sub_en);
                        if($this->item_attr_heading_sub_en!=' ') {
                            $heading_sub_en = $this->item_attr_heading_sub_en;
                        }
                        if($saveFlag){
                            $nCnt_attr++;
                            $metadata["attribute_no"] = $nCnt_attr;
                        }
                        $metadata["attribute_value"] = $heading."|".$heading_en."|".$heading_sub."|".$heading_sub_en; 
                        array_push($attr_elm, $metadata["attribute_value"]);
                        break;
                    default :
                        // ファイルとサムネイルはセッション情報をそのままコピー
                        array_push($attr_elm, $item_attr_old[$ii][$jj]);
                        break;
                }
                if($item_attr_type[$ii]['input_type'] != 'checkbox'){
                    if($saveFlag){
                        if($nCnt_attr > $nCnt_attr_flg){
                            $result = $ItemRegister->entryMetadata($metadata, $error);
                            if($result === false){
                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                $exception = new AppException($error);
                                $exception->addError($error);
                                throw $exception;
                            }
                            $nCnt_attr_flg = $nCnt_attr;
                            
                            // 著者IDに更新が入ったので、entryMetadataで
                            // 更新したデータから著者IDを持ってくる
                            if($item_attr_type[$ii]['input_type'] === 'name'){
                                $attr_elm[count($attr_elm) - 1]['author_id'] = $metadata['author_id'];
                            }
                        }
                    }
                }
            }
            array_push($item_attr, $attr_elm);      // 1メタデータ分のユーザ入力値をセット
        }
        
        if($saveFlag){
            if(strlen($contributorErrorMsg)==0){
                // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
                $result = $ItemRegister->updateInsertUserIdForContributor(
                            intval($this->Session->getParameter("edit_item_id")),
                            $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
                if($result === false)
                {
                    $tmpErrorMsg = "Cannot update contributor.";
                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($tmpErrorMsg);
                    $exception->addError($tmpErrorMsg);
                    throw $exception;
                }
                // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
            } else {
                $this->save_mode = "stay";
                $this->addErrMsg($contributorErrorMsg);
            }
        }
        // Add registered info save action 2009/02/09 Y.Nakao --end--
        
        // Add e-person R.Matsuura 2013/11/18 --start--
        // feedback mail送信先を設定
        $feedback_mail_array = explode(",", $this->send_feedback_mail_address_mailaddresses);
        $ret = $ItemRegister->deleteFeedbackMailAuthorId($item["item_id"], $item["item_no"]);
        if($ret === false)
        {
            $tmpErrorMsg = "Cannot delete record from send_feedback_author_id table.";
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $authorIdNo = 1;
        for ($cnt = 0; $cnt < count($feedback_mail_array); $cnt++){
            // check mail address
            if ( $feedback_mail_array[$cnt] == "") {
                continue;
            }
            
            // get author_id
            $authorId = $ItemRegister->getAuthorIdByMailAddress($feedback_mail_array[$cnt]);
            if($authorId === false){
                continue;
            }
            
            // insert send feedback mail author id
            $ItemRegister->insertFeedbackMailAuthorId( $item["item_id"], $item["item_no"], $authorIdNo, $authorId);
            if($result === false){
                $tmpErrorMsg = "Cannot insert send feedback mail author id.";
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            
            // set author id no
            $authorIdNo++;
        }
        $feedback_mail_authors_array = explode(",", $this->send_feedback_mail_address_authors);
        $this->Session->setParameter("feedback_mailaddress_str", $this->send_feedback_mail_address_mailaddresses);
        $this->Session->setParameter("feedback_mailaddress_author_str", $this->send_feedback_mail_address_authors);
        $this->Session->setParameter("feedback_mailaddress_array", $feedback_mail_array);
        $this->Session->setParameter("feedback_mailaddress_author_array", $feedback_mail_authors_array);
        // Add e-person R.Matsuura 2013/11/18 --end--
        
        // ------------------------------------------------------------
        // オプション個別処理
        // ------------------------------------------------------------
        $blankCheckFlag = true;
        if ($this->save_mode != null) {
            $idx = (int)($this->target);
            switch($this->save_mode) {
                case "stay":
                    $blankCheckFlag = false;
                    break;
                case "add_row":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // target-thメタデータの属性数を増やす
                    $item_num_attr[$idx] = $item_num_attr[$idx] + 1;
                    $this->Session->setParameter("item_num_attr", $item_num_attr);
                    // 入力形式ごとに分岐
                    switch($item_attr_type[$idx]['input_type']) {
                        case "text":
                        case "textarea":
                        case "link":
                            array_push($item_attr[$idx], '');
                            break;
                        case "name":
                            $new_name = array(
                                'family' => '',
                                'given' => '',
                                'family_ruby' => '',
                                'given_ruby' => '',
                                'email' => '',
                                'author_id' => '',
                                'external_author_id' => array(array('prefix_id'=>'', 'suffix'=>''))
                            );
                            array_push($item_attr[$idx], $new_name);
                            break;
                        case "biblio_info":
                            $new_biblio = array(
                                'biblio_name' => '',
                                'biblio_name_english' => '',
                                'volume' => '',
                                'issue' => '',
                                'spage' => '',
                                'epage' => '',
                                'year' => '',
                                'month' => '',
                                'day' => '',
                                'date_of_issued' => ''
                            );
                            array_push($item_attr[$idx], $new_biblio);
                            break;
                        case "date":
                            $new_date = array(
                                'date_year' => '',
                                'date_month' => '',
                                'date_day' => '',
                                'date' => ''
                            );
                            array_push($item_attr[$idx], $new_date);
                            break;
                        default:
                            break;
                    }
                    break;
                case "up_row":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // attridx-thメタデータとattridx-1-thメタデータの属性を入れ替える
                    // ※target == 0(一番上)の場合は無効
                    $attridx = (int)($this->attridx);
                    if( $attridx == 0) {
                        break;
                    }
                    // 配列の入替
                    $bufarray = $item_attr[$idx][$attridx];
                    $item_attr[$idx][$attridx] = $item_attr[$idx][$attridx-1];
                    $item_attr[$idx][$attridx-1] = $bufarray;
                    break;
                case "down_row":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // attridx-thメタデータとattridx-1-thメタデータの属性を入れ替える
                    // ※target == $item_num_attr[$idx]-1(一番下)の場合は無効
                    $attridx = (int)($this->attridx);
                    if( $attridx >= $item_num_attr[$idx]-1) {
                        break;
                    }
                    // 配列の入替
                    $bufarray = $item_attr[$idx][$attridx];
                    $item_attr[$idx][$attridx] = $item_attr[$idx][$attridx+1];
                    $item_attr[$idx][$attridx+1] = $bufarray;
                    break;
                case "add_author_id":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // Add author_id input space
                    $attridx = (int)($this->attridx);
                    $new_author_id = array('prefix_id'=>'', 'suffix'=>'');
                    array_push($item_attr[$idx][$attridx]["external_author_id"], $new_author_id);
                    break;
                case "clear_author":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // Clear author data
                    $attridx = (int)($this->attridx);
                    $new_name = array(
                                'family' => '',
                                'given' => '',
                                'family_ruby' => '',
                                'given_ruby' => '',
                                'email' => '',
                                'author_id' => '',
                                'external_author_id' => array(array('prefix_id'=>'', 'suffix'=>''))
                            );
                    $item_attr[$idx][$attridx] = $new_name;
                    break;
                default:
                    break;
            }
        }
        
        if($blankCheckFlag){
            // ------------------------------------------------------------
            // 次の画面に進む前に、テキスト入力の空白入力を詰める
            // ※複数可能、かつ空白入力1以上の場合
            // ------------------------------------------------------------
            for($ii=0; $ii<count($item_attr_type); $ii++) {
                // 複数指定不可のメタデータはスルー
                if( $item_attr_type[$ii]['plural_enable']!=1){
                    continue;
                }
                // ファイル／サムネイルはスルー
                // ラジオボックス, セレクトボックスは初期値が設定されているのでスルー
                // チェックボックスはチェックしなくてもＯＫなのでスルー
                if( $item_attr_type[$ii]['input_type']=='file' ||
                    $item_attr_type[$ii]['input_type']=='thumbnail' ||
                    $item_attr_type[$ii]['input_type']=='checkbox' ||
                    $item_attr_type[$ii]['input_type']=='radio' ||
                    $item_attr_type[$ii]['input_type']=='select'){ 
                    continue;
                }
                // ii-thメタデータの空白でない入力数を計算
                $cnt = 0;
                for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                    if($item_attr_type[$ii]['input_type']=='name'){
                        if( $item_attr[$ii][$jj]['family'] != '' ||
                            $item_attr[$ii][$jj]['given'] != '') {
                            $cnt++;
                        }
                    // Add biblio info 2008/08/11 --start--
                    } else if($item_attr_type[$ii]['input_type']=='biblio_info'){
                        // 書誌情報の有効条件は、一応何かしら値が入っていることとする
                        if( $item_attr[$ii][$jj]['biblio_name'] != '' || 
                            $item_attr[$ii][$jj]['biblio_name_english'] != '' || 
                            $item_attr[$ii][$jj]['volume'] != '' ||
                            $item_attr[$ii][$jj]['issue'] != '' || 
                            ($item_attr[$ii][$jj]['spage'] != '' || 
                            $item_attr[$ii][$jj]['epage'] != '') ||
                            $item_attr[$ii][$jj]['date_of_issued'] != '' ) {
                            $cnt++;
                        }
                    // Add biblio info 2008/08/11 --end--
                    // Add link name 2009/03/19 --start--
                    } else if($item_attr_type[$ii]['input_type']=='link'){
                        // リンク有効条件は、URLに何かしら値が入っていることとする
                        $link_data = explode("|", $item_attr[$ii][$jj]);
                        if($link_data[0] != '') {
                            $cnt++;
                        }
                    // Add link name 2009/03/19 --end--
                    } else {
                        if($item_attr[$ii][$jj] != '') {
                            $cnt++; 
                        }
                    }
                }
                $array_effective = array();
                // 有効入力無し=>0-th要素のみ残し、削除
                if( $cnt == 0 ) {
                    // Add php notice T.Koyasu 2014/09/16 --start--
                    if(isset($item_attr[$ii]) && count($item_attr[$ii]) > 0){
                        array_push($array_effective, $item_attr[$ii][0]);
                    }
                    // Add php notice T.Koyasu 2014/09/16 --end--
                    $item_num_attr[$ii] = 1;
                    $item_attr[$ii] = $array_effective;
                // 有効入力あり=>無効な入力を削除 
                } else {
                    for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                        if($item_attr_type[$ii]['input_type']=='name'){
                            if( $item_attr[$ii][$jj]['family'] != '' ||
                                $item_attr[$ii][$jj]['given'] != '') {
                                if(count($item_attr[$ii][$jj]['external_author_id'])>=2){
                                    $external_author_id_array = array();
                                    for($kk=0;$kk<count($item_attr[$ii][$jj]['external_author_id']);$kk++){
                                        $author_id_add_flag = true;
                                        if($kk!=0){
                                            for($ll=0;$ll<count($external_author_id_array);$ll++){
                                                if($item_attr[$ii][$jj]['external_author_id'][$kk]['prefix_id']==$external_author_id_array[$ll]['prefix_id']
                                                    && $item_attr[$ii][$jj]['external_author_id'][$kk]['suffix']==$external_author_id_array[$ll]['suffix']){
                                                    $author_id_add_flag = false;
                                                    break;
                                                }
                                            }
                                        }
                                        if($author_id_add_flag){
                                            array_push($external_author_id_array, $item_attr[$ii][$jj]['external_author_id'][$kk]);
                                        }
                                    }
                                    $item_attr[$ii][$jj]['external_author_id'] = $external_author_id_array;
                                }
                                array_push($array_effective, $item_attr[$ii][$jj]);
                            }
                        } else if($item_attr_type[$ii]['input_type']=='biblio_info'){
                            if( $item_attr[$ii][$jj]['biblio_name'] != '' || 
                                $item_attr[$ii][$jj]['biblio_name_english'] != '' || 
                                $item_attr[$ii][$jj]['volume'] != '' ||
                                $item_attr[$ii][$jj]['issue'] != '' || 
                                ($item_attr[$ii][$jj]['spage'] != '' || 
                                $item_attr[$ii][$jj]['epage'] != '') ||
                                $item_attr[$ii][$jj]['date_of_issued'] != '' ) {
                                array_push($array_effective, $item_attr[$ii][$jj]);
                            }
                        } else if($item_attr_type[$ii]['input_type']=='link'){
                            $link_data = explode("|", $item_attr[$ii][$jj]);
                            if($link_data[0] != '') {
                                array_push($array_effective, $item_attr[$ii][$jj]);
                            }
                        } else {
                            if($item_attr[$ii][$jj] != '') {
                                array_push($array_effective, $item_attr[$ii][$jj]);
                            }
                        }
                    }
                    $item_num_attr[$ii] = count($array_effective);
                    $item_attr[$ii] = $array_effective;
                }
            }
            
        }
        // 属性情報を更新
        $this->Session->setParameter("item_attr", $item_attr);
        $this->Session->setParameter("item_num_attr", $item_num_attr);
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "texts",
                                $this->save_mode,
                                $this->Session->getParameter("isfile"),
                                $this->Session->getParameter("doi_itemtype_flag"),
                                $this->Session->getParameter("base_attr"),
                                $this->Session->getParameter("item_pub_date"),
                                $this->Session->getParameter("item_attr_type"),
                                $this->Session->getParameter("item_attr"),
                                $this->Session->getParameter("item_num_attr"),
                                $this->Session->getParameter("indice"),
                                $this->Session->getParameter("edit_item_id"),
                                $this->Session->getParameter("edit_item_no")
        );
        $ret = $transCheck->getDestination();
        foreach($transCheck->getErrorMsg() as $msg){
            $this->addErrMsg($msg);
        }
        $this->warningMsg = array_merge($this->warningMsg, $transCheck->getWarningMsg());
        
        // warningをViewに渡す処理
        if(count($this->warningMsg) > 0){
            $container =& DIContainerFactory::getContainer();
            $request =& $container->getComponent("Request");
            $request->setParameter("warningMsg", $this->warningMsg);
        }
        
        return $ret;
    }
    
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
    /**
     * Get contributor's user_id by handle, name and email
     *
     * @param string $handle
     * @param string $name
     * @param string $email
     * @param string &$outContributorUserId
     * @return string "Success", "NotExist", "Conflict"
     * @access private
     */
    private function getUserIdForContributor($handle, $name, $email, &$outContributorUserId)
    {
        $userIdByHandle = null;
        $userIdsByName = null;
        $userIdsByEmail = null;
        
        if(strlen($handle) > 0)
        {
            $userIdByHandle = $this->searchContributorByHandle($handle);
            // 見つからなかった場合はNotExistを返却
            if(!isset($userIdByHandle))
            {
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
        }
        if(strlen($name) > 0)
        {
            $userIdsByName = $this->searchContributorByName($name);
            // 見つからなかった場合はNotExistを返却
            if(!isset($userIdsByName))
            {
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
        }
        if(strlen($email) > 0)
        {
            $userIdsByEmail = $this->searchContributorByEmail($email);
            // 見つからなかった場合はNotExistを返却
            if(!isset($userIdsByEmail))
            {
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
        }
        
        return $this->narrowDownContributor($userIdByHandle, $userIdsByName, $userIdsByEmail, $outContributorUserId);
    }
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
    
    /**
     * Search contributor's user_id by handle
     *
     * @param string $handle
     * @return string
     * @access private
     */
    private function searchContributorByHandle($handle)
    {
        // Execute select
        $query = "SELECT user_id ".
                 "FROM ".DATABASE_PREFIX."users ".
                 "WHERE handle = ? ;";
        $params = array();
        $params[] = $handle;
        $result = $this->Db->execute($query, $params);
        if($result == false || count($result) != 1 || !array_key_exists("user_id", $result[0]))
        {
            return null;
        }
        
        return $result[0]["user_id"];
    }

    /**
     * Search contributor's user_id by name
     *
     * @param string $name
     * @return array()
     * @access private
     */
    private function searchContributorByName($name)
    {
        // Execute select
        $query = "SELECT user_id ".
                 "FROM ".DATABASE_PREFIX."users_items_link ".
                 "WHERE item_id = ? ".
                 "AND content = ? ;";
        $params = array();
        $params[] = 4;
        $params[] = $name;
        $result = $this->Db->execute($query, $params);
        if($result == false || count($result) < 1 || !array_key_exists("user_id", $result[0]))
        {
            return null;
        }
        
        $contributors = array();
        
        for($ii = 0; $ii < count($result); $ii++)
        {
            $contributors[] = $result[$ii]["user_id"];
        }
        
        return $contributors;
    }

    /**
     * Search contributor's user_id by email
     *
     * @param string $email
     * @return array()
     * @access private
     */
    private function searchContributorByEmail($email)
    {
        // Execute select
        $query = "SELECT user_id ".
                 "FROM ".DATABASE_PREFIX."users_items_link ".
                 "WHERE item_id = ? ".
                 "AND content = ? ;";
        $params = array();
        $params[] = 5;
        $params[] = $email;
        $result = $this->Db->execute($query, $params);
        if($result == false || count($result) < 1 || !array_key_exists("user_id", $result[0]))
        {
            return null;
        }
        
        $contributors = array();
        
        for($ii = 0; $ii < count($result); $ii++)
        {
            $contributors[] = $result[$ii]["user_id"];
        }
        
        return $contributors;
    }

    /**
     * Find Unique Contributor
     *
     * @param string $userIdsByHandle
     * @param array() $userIdsByName
     * @param array() $userIdsByEmail
     * @param string &$outContributor
     * @return string "Success", "NotExist", "Conflict"
     * @access private
     */
    private function narrowDownContributor($userIdByHandle, $userIdsByName, $userIdsByEmail, &$outContributor)
    {
        // ハンドルが存在する場合
        if(isset($userIdByHandle))
        {
            // 氏名リストの中にハンドルが存在するか調べる
            $resultInName = $this->existContributorInMetaList($userIdByHandle, $userIdsByName);
            // メールリストの中にハンドルが存在するか調べる
            $resultInEmail = $this->existContributorInMetaList($userIdByHandle, $userIdsByEmail);
            
            // 双方のリストいずれかにハンドルが存在しない場合はNotExistを返却
            if(!$resultInName || !$resultInEmail)
            {
                $outContributor = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            $outContributor = $userIdByHandle;
        }
        // ハンドルが存在しない場合
        else
        {
            // 氏名リストとメールリストの重複リストを作成する
            if(isset($userIdsByName) && isset($userIdsByEmail))
            {
                $result = array_intersect($userIdsByName, $userIdsByEmail);
            }
            else if(!isset($userIdsByName) && isset($userIdsByEmail))
            {
                $result = $userIdsByEmail;
            }
            else if(isset($userIdsByName) && !isset($userIdsByEmail))
            {
                $result = $userIdsByName;
            }
            else
            {
                $outContributor = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            
            $hitCount = count($result);
            // 重複リストが0件の場合はNotExistを返却
            if($hitCount == 0)
            {
                $outContributor = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            // 重複リストが2件以上の場合はConflictを返却
            else if($hitCount >= 2)
            {
                $outContributor = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
            }
            
            // 取得されるリストは氏名リストとメールリストを比較し、
            // 重複する要素を取得している
            // 重複要素を取得する際、array_intersectの第一引数から
            // KeyとValueを取得するため、$resultの0番要素に入っている保証がない
            // そのため、一時的に現存するKeyを全て取出し、ContributorのユーザIDとしている
            $retArr = array_values($result);
            
            $outContributor = $retArr[0];
       }
       // Successを返却
       return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_SUCCESS;
    }
    
    private function existContributorInMetaList($searchKey, $candidateList)
    {
        if(isset($candidateList))
        {
            $result = in_array($searchKey, $candidateList);
        }
        else
        {
            // candidateListが未指定の場合はtrue
            $result = true;
        }
        
        return $result;
    }
}
?>
