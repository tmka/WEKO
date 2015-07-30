<?php
// --------------------------------------------------------------------
//
// $Id: Editlinks.class.php 41628 2014-09-17 02:01:48Z tatsuya_koyasu $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Editlinks extends RepositoryAction
{
    var $Session = null;
    var $Db = null;
    
    // リクエストパラメタ, 入力形式別(2008/03/06)
    var $base_attr = null;                  // 基本情報部分のリクエストパラメタ配列
    var $item_attr_text = null;             // "text"属性
    var $item_attr_textarea = null;         // "textarea"属性
    var $item_attr_checkbox = null;         // "checkbox"属性
    var $item_attr_name_family = null;      // "name"属性, 姓
    var $item_attr_name_given = null;       // "name"属性, 名
    var $item_attr_name_email = null;       // "name"属性, E-mail
    var $item_attr_select = null;           // "select"属性
    var $item_attr_link = null;             // "link"属性
    var $item_attr_link_name = null;        // "link"属性 Add link display name 2009/03/19 A.Suzuki
    var $item_attr_radio = null;            // "radio"属性
//  var $item_index_ids = null;             // インデックスid列
//  var $item_index_names = null;           // インデックス名列
    
    var $item_pub_date_year = null;         // アイテム公開日 : 年
    var $item_pub_date_month = null;        // アイテム公開日 : 月
    var $item_pub_date_day = null;          // アイテム公開日 : 日  
    var $item_keyword = null;               // アイテムキーワード
    var $item_keyword_english = null;       // アイテムキーワード(英)
    //var $OpendIds = null;                 // 開いているインデックスのID列(,区切り)
    //var $CheckedIds = null;                   // チェックされているインデックスのID列(|区切り)
    //var $CheckedNames = null;             // チェックされているインデックスの名前列(|区切り)
    
    // オプション用
    var $save_mode = null;      // 処理モード
                                // "next" : リンク設定画面へ (デフォルト)
                                // "add_row" : 属性の数を増やす
                                // "up_row" : 属性を入れ替える (attridx-th属性が上に)
                                // "down_row" : 属性を入れ替える (attridx-th属性が下に)
                                // "add_author_id" : 氏名属性の外部著者IDの入力欄を増やす
                                // "clear_author" : 氏名属性情報を削除する
                                // "stay" : 保存 Add registered info save action 2009/02/09 Y.Nakao
    var $target = null;         // 処理対象のメタデータ番号
    var $attridx = null;        // 処理対象の属性番号
    
    // Add biblio info 2008/08/11 Y.Nakao --start--
    var $item_attr_biblio_name = null;      // 書誌情報 : 雑誌名
    var $item_attr_biblio_name_english = null;  // 書誌情報 : 雑誌名(英)
    var $item_attr_biblio_volume = null;    // 書誌情報 : 巻
    var $item_attr_biblio_issue = null;     // 書誌情報 : 号
    var $item_attr_biblio_spage = null;     // 書誌情報 : 開始ページ
    var $item_attr_biblio_epage = null;     // 書誌情報 : 終了ページ
    // 2008/10/06 A.Suzuki --start--
    var $item_attr_biblio_dateofissued = null;          // 書誌情報 : 発行年月日
    var $item_attr_biblio_dateofissued_year = null;     // 書誌情報 : 発行年
    var $item_attr_biblio_dateofissued_month = null;    // 書誌情報 : 発行月
    var $item_attr_biblio_dateofissued_day = null;      // 書誌情報 : 発行日
    // 2008/10/06 A.Suzuki --end--
    // Add biblio info 2008/08/11 Y.Nakao --end--
    
    // Add item attribute:date 2008/10/14 A.Suzuki --start--
    var $item_attr_date = null;         // 日付
    var $item_attr_date_year = null;    // 日付 : 年
    var $item_attr_date_month = null;   // 日付 : 月
    var $item_attr_date_day = null;     // 日付 : 日
    // Add item attribute:date 2008/10/14 A.Suzuki --end--
    
    // Add contents page 2010/08/06 Y.Nakao --start--
    var $item_attr_heading = null;          // heading
    var $item_attr_heading_en = null;       // heading(english)
    var $item_attr_heading_sub = null;      // subheading
    var $item_attr_heading_sub_en = null;   // subheading(english)
    // Add contents page 2010/08/06 Y.Nakao --end--
    
    // Add author ruby 2010/11/01 A.Suzuki --start--
    var $item_attr_name_family_ruby = null; // "name", surname ruby
    var $item_attr_name_given_ruby = null;  // "name", given name ruby
    var $item_attr_name_author_id_prefix = null; // "name", authorID prefix
    var $item_attr_name_author_id_suffix = null; // "name", authorID suffix
    // Add author ruby 2010/11/01 A.Suzuki --end--
    
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
    public $item_contributor = null;
    public $item_contributor_handle = null;
    public $item_contributor_name= null;
    public $item_contributor_email = null;
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
    
    // Add Send Feedback Mail R.Matsuura 2013/11/12 --start--
    public $send_feedback_mail_address_mailaddresses = null;
    public $send_feedback_mail_address_authors = null;
    // Add Send Feedback Mail R.Matsuura 2013/11/12 --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $user_error_msg = '';       // GUI表示エラーメッセージ
        try {
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                $user_error_msg = 'initで既に・・・';
                throw $exception;
            }      
            
            // セッション情報取得
            $item_type_all = $this->Session->getParameter("item_type_all");         // 1.アイテムタイプ,  アイテムタイプのレコードをそのまま保存したものである。
            $item_attr_type = $this->Session->getParameter("item_attr_type");       // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
            $item_num_cand = $this->Session->getParameter("item_num_cand");         // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
            $option_data = $this->Session->getParameter("option_data");             // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            // ユーザ入力値＆変数
            $item_num_attr = $this->Session->getParameter("item_num_attr");         // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
            $item_attr_old = $this->Session->getParameter("item_attr");             // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
            $item_attr = array();       // 6.アイテム属性 。これはリクエストから全部作り直す
            $edit_flag = $this->Session->getParameter("edit_flag");                 // X.処理モード : edit_flag (0:新規作成, 1:既存編集)
            
            $sfmam = $this->Session->getParameter("feedback_mailaddress_str");
            $sfmaa = $this->Session->getParameter("feedback_mailaddress_author_str");
            $fma = $this->Session->getParameter("feedback_mailaddress_array");
            $fmaa = $this->Session->getParameter("feedback_mailaddress_author_array");

            // Add registered info save action 2009/02/03 Y.Nakao --start--
            $smarty_assign = $this->Session->getParameter("smartyAssign");
            $err_msg = array();
            $ItemRegister = new ItemRegister($this->Session, $this->Db);
            // Add registered info save action 2009/02/03 Y.Nakao --end--
            
            $NameAuthority = new NameAuthority($this->Session, $this->Db);
            
            // カウンタ
            $cnt_text = 0;      // "text"属性カウンタ
            $cnt_textarea = 0;  // "textarea"属性カウンタ
            $cnt_name = 0;      // "name"属性カウンタ
            $cnt_link = 0;      // "link"属性カウンタ
            $cnt_select = 0;    // "select"属性カウンタ
            $cnt_checkbox = 0;  // "checkbox"属性カウンタ
            $cnt_radio = 0;     // "radio"属性カウンタ
            $cnt_biblio = 0;    // "biblio_info"属性カウンタ 2008/08/11
            $cnt_date = 0;      // "date"属性カウンタ 2008/10/14
            $cnt_author_id = 0; // "name"属性外部著者IDカウンタ
            
            // ------------------------------------------------------------
            // セッション情報保存 (全オプション共通)
            // ------------------------------------------------------------     
            
            // Add LIDO 2014/05/20 S.Suzuki --start--
            $warning = "";
            // タイトルに空白文字列が入力されたら空文字に変換
            if ($this->base_attr[0]===RepositoryConst::BLANK_WORD) {
                $warning = $smarty_assign->getLang("repository_item_error_empty_title");
                $this->base_attr[0] = '';
            }
            
            if ($this->base_attr[1]===RepositoryConst::BLANK_WORD) {
                $warning = $smarty_assign->getLang("repository_item_error_empty_title");
                $this->base_attr[1] = '';
            }
            // Add LIDO 2014/05/20 S.Suzuki --end--
            
            // アイテム基本属性をセッションに保存        
            $this->Session->setParameter("base_attr", array( 
                "title" => ($this->base_attr[0]==' ') ? '' : $this->base_attr[0],   // ?3項演算子OK.
                "title_english" => ($this->base_attr[1]==' ') ? '' : $this->base_attr[1],
                "language" => $this->base_attr[2])
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
                    if($this->save_mode == "next" || $this->save_mode == "stay")
                    {
                        $contributorErrorMsg = $smarty_assign->getLang("repository_item_contributor_error_not_exist");
                        array_push($err_msg, $contributorErrorMsg);
                    }
                }
                else if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT)
                {
                    if($this->save_mode == "next" || $this->save_mode == "stay")
                    {
                        $contributorErrorMsg = $smarty_assign->getLang("repository_item_contributor_error_conflict");
                        array_push($err_msg, $contributorErrorMsg);
                    }
                }
                else if($retStatus == RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOAUTH)
                {
                    if($this->save_mode == "next" || $this->save_mode == "stay")
                    {
                        $contributorErrorMsg = $smarty_assign->getLang("repository_item_contributor_error_no_authority");
                        array_push($err_msg, $contributorErrorMsg);
                    }
                }
                
                $item_contributor = array(
                    RepositoryConst::ITEM_CONTRIBUTOR_HANDLE => $this->item_contributor_handle,
                    RepositoryConst::ITEM_CONTRIBUTOR_NAME => $this->item_contributor_name,
                    RepositoryConst::ITEM_CONTRIBUTOR_EMAIL => $this->item_contributor_email);
                    
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
            if($this->save_mode == "stay" || $this->save_mode == "next"){
                if($edit_flag == 0){
                    // 新規登録時
                    $item["item_id"] = intval($this->Db->nextSeq("repository_item"));
                    $item["item_no"] = 1;
                } elseif($edit_flag == 1){
                    //既存編集時
                    // 編集中のアイテムIDをセッションから取得
                    $item["item_id"] = intval($this->Session->getParameter("edit_item_id"));
                    $item["item_no"] = intval($this->Session->getParameter("edit_item_no"));
                }
                //タイトルの空チェックと設定 2009/08/26 K.Ito --start--
                if((trim($this->base_attr[0]) == "") && (trim($this->base_attr[1]) == "") || (trim($this->base_attr[0]) == "タイトル無し") && (trim($this->base_attr[1]) != "no titile")){
                    if($this->base_attr[2] == "ja"){
                        //WEKOの設定言語に依存せず、論文の言語で決めるので仮タイトル文字列はベタ書きです
                        $item["title"] = "タイトル無し";  
                        $item["title_english"] = "";
                    }else{
                        $item["title"] = "";
                        $item["title_english"] = "no title";
                    }
                //片方がタイトル無し、もう一方が入力があったら、タイトル無しを空に戻す
                }else if((trim($this->base_attr[0]) == "タイトル無し") && (trim($this->base_attr[1]) != "")){
                    $item["title"] = "";    
                    $item["title_english"] = (trim($this->base_attr[1]));
                //片方がno title、もう一方が入力があったら、タイトル無しを空に戻す
                }else if((trim($this->base_attr[0]) != "") && (trim($this->base_attr[1]) == "no title")){
                    $item["title"] = (trim($this->base_attr[0]));
                    $item["title_english"] = "";
                //タイトル無しのままで、言語だけ変更した場合
                }else if((trim($this->base_attr[0]) == "タイトル無し") && (trim($this->base_attr[1]) == "")){
                    if($this->base_attr[2] == "ja"){
                        $item["title"] = (trim($this->base_attr[0]));
                        $item["title_english"] = (trim($this->base_attr[1]));
                    }else{
                        $item["title"] = "";
                        $item["title_english"] = "no title";
                    }
                //no titleのままで、言語だけ変更した場合
                }else if((trim($this->base_attr[0]) == "") && (trim($this->base_attr[1]) == "no title")){
                    if($this->base_attr[2] == "ja"){
                        $item["title"] = "タイトル無し";
                        $item["title_english"] = "";
                    }else{
                        $item["title"] = (trim($this->base_attr[0]));
                        $item["title_english"] = (trim($this->base_attr[1]));
                    }
                }else{
                    $item["title"] = (trim($this->base_attr[0]));
                    $item["title_english"] = (trim($this->base_attr[1]));
                }
                /*
                $item["title"] = ($this->base_attr[0]==' ') ? '' : $this->base_attr[0];
                $item["title_english"] = ($this->base_attr[1]==' ') ? '' : $this->base_attr[1];
                */
                //タイトルの空チェックと設定 2009/08/26 K.Ito --end--
                
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
                        array_push($err_msg, $error);
                        $this->Session->setParameter("error_msg", $err_msg);
                        $this->failTrans(); // rollback
                        return 'error';
                    }
                    // Add add ["item"] to $Result_List[0]["revision_no"] 2013/09/11 K.Matsushita --start--
                    $item["revision_no"] = $Result_List["item"][0]["revision_no"];
                    $item["prev_revision_no"] = $Result_List["item"][0]["revision_no"];
                    // Add add ["item"] to $Result_List[0]["revision_no"] 2013/09/11 K.Matsushita --end--
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
                    if($this->save_mode == "next" || $this->save_mode == "stay"){
                        $this->Session->setParameter("item_pub_date", array(
                                "year" => $tmp_item_pub_date["year"],
                                "month" => $tmp_item_pub_date["month"],
                                "day" => $tmp_item_pub_date["day"]
                            )
                        );
                        $msg = $smarty_assign->getLang("repository_item_error_date");
                        $tmp = $smarty_assign->getLang("repository_search_item");
                        array_push($err_msg, sprintf($msg, $tmp));
                    }
                }
                // Fix check invalid date 2011/06/17 A.Suzuki --end--
                
                $item["item_type_id"] = $item_type_all["item_type_id"];
                $item["serch_key"] = $item_keyword;
                $item["serch_key_english"] = $item_keyword_english;
                if($edit_flag == 0){
                    $result = $ItemRegister->entryItem($item, $error);
                    if($result === false){
                        // upload faild
                        array_push($err_msg, $error);
                        $this->Session->setParameter("error_msg", $err_msg);
                        $this->failTrans(); // rollback
                        return 'error';
                    }
                    $this->Session->setParameter("edit_item_id", $item["item_id"]);
                    $this->Session->setParameter("edit_item_no", $item["item_no"]);
                    $this->Session->setParameter("edit_flag", 1);
                    $edit_flag = 1;
                } else if($edit_flag == 1){
                    $result = $ItemRegister->editItem($item["item_id"], $item["item_no"], $error);
                    if($result === false){
                        // upload faild
                        array_push($err_msg, $error);
                        $this->Session->setParameter("error_msg", $err_msg);
                        return 'error';
                    }
                    $result = $ItemRegister->updateItem($item, $error);
                    if($result === false){
                        // upload faild
                        array_push($err_msg, $error);
                        $this->Session->setParameter("error_msg", $err_msg);
                        $this->failTrans(); // rollback
                        return 'error';
                    }
                }
                // ------------------------------------------------------------------
                // ファイル(サムネイル)以外の属性を全て論理削除
                // ------------------------------------------------------------------
                $result = $this->deleteItemAttrData($item["item_id"], $item["item_no"], $this->Session->getParameter("_user_id"), $error);
                if($result === false){
                    array_push($err_msg, $error);
                    $this->Session->setParameter("error_msg", $err_msg);
                    return 'error';
                }
            }
            // Add join set insert index and set item links 2008/12/17 Y.Nakao
            
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
                            if($this->save_mode == "next" || $this->save_mode == "stay"){
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
                            if($this->save_mode == "next" || $this->save_mode == "stay"){
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
                        
                        if($this->item_attr_name_email[$cnt_name]!=' ') {
                            $email = $this->item_attr_name_email[$cnt_name];
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
                            }
                        }
                        
                        // Add external_author_id merge 2011/02/24 A.Suzuki --start--
                        if(count($external_author_id)!=0
                           && ($this->save_mode=="next" || $this->save_mode=="stay")
                           && (strlen($family)!=0 || strlen($given)!=0 || strlen($email)!=0) )
                        {
                            $result = $NameAuthority->mergeExtAuthorId($external_author_id, $item_attr_old[$ii][$jj]["author_id"], true);
                        }
                        // Add external_author_id merge 2011/02/24 A.Suzuki --end--
                        
                        $cnt_author_id = $cnt_author_id + $kk;
                        
                        // Check old author_id 2010/11/02 A.Suzuki --start--
                        // author_id をチェック(author_id == 0 : 新規, author_id > 0: 既存)
                        if(intval($item_attr_old[$ii][$jj]["author_id"])==0
                           && ($this->save_mode=="next" || $this->save_mode=="stay")
                           && (strlen($family)!=0 || strlen($given)!=0 || strlen($email)!=0) )
                        {
                            // author_id 発番
                            $author_id = $NameAuthority->getNewAuthorId();
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
                                'external_author_id' => $external_author_id
                            )
                        );
                        
                        // Add e-person 2013/11/07 R.Matsuura --start--
                        if(strlen($email)>0)
                        {
                            array_push($external_author_id, array('prefix_id'=>'0', 'suffix'=>$this->item_attr_name_email[$cnt_name], 'old_prefix_id'=>'0', 'old_suffix'=>$item_attr_old[$ii][$jj]["email"], 'prefix_name'=>'e_mail_address'));
                        }
                        // Add e-person 2013/11/07 R.Matsuura --end--
                        
                        $metadata["family"] = $family;
                        $metadata["name"] = $given;
                        $metadata["family_ruby"] = $family_ruby;
                        $metadata["name_ruby"] = $given_ruby;
                        $metadata["e_mail_address"] = $email;
                        $metadata["author_id"] = $author_id;
                        $metadata["language"] = $language;
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
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
                            if($this->save_mode == "next" || $this->save_mode == "stay"){
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
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
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
                                if($this->save_mode == "stay" || $this->save_mode == "next"){
                                    $result = $ItemRegister->entryMetadata($metadata, $error);
                                    if($result === false){
                                        array_push($err_msg, $error);
                                        $this->Session->setParameter("error_msg", $err_msg);
                                        $this->failTrans(); // rollback
                                        return 'error';
                                    }
                                }
                            }
                            $cnt_checkbox++;
                        }
                        break;
                    case 'radio':
                        $metadata["attribute_no"] = $jj+1;
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
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

                        // A.Suzuki 2008/10/06 --start--
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
                        // A.Suzuki 2008/10/06 --end--
                        
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
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
                            if($biblio_name!="" || $biblio_name_english!="" ||
                                $volume!="" || $issue!="" || $spage!="" ||
                                $epage!="" || $dateofissued!=""){
                                $nCnt_attr++;
                                $metadata["biblio_no"] = $nCnt_attr;
                            }
                        }
                        break;
                        
                    // Add item attribute:date A.Suzuki 2008/10/14 --start--
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
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
                            if($date!=""){
                                $nCnt_attr++;
                                $metadata["attribute_no"] = $nCnt_attr;
                            }
                        }
                        break;
                    // Add item attribute:date A.Suzuki 2008/10/14 --start--
                    // Add contents page Y.Nakao 2010/08/06 --start--
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
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
                            $nCnt_attr++;
                            $metadata["attribute_no"] = $nCnt_attr;
                        }
                        $metadata["attribute_value"] = $heading."|".$heading_en."|".$heading_sub."|".$heading_sub_en; 
                        array_push($attr_elm, $metadata["attribute_value"]);
                        break;
                    // Add contents page Y.Nakao 2010/08/06 --end--
                    default :
                        // ファイルとサムネイルはセッション情報をそのままコピー
                        array_push($attr_elm, $item_attr_old[$ii][$jj]);                    
                        break;
                    }
                    if($item_attr_type[$ii]['input_type'] != 'checkbox'){
                        if($this->save_mode == "next" || $this->save_mode == "stay"){
                            if($nCnt_attr > $nCnt_attr_flg){
                                $result = $ItemRegister->entryMetadata($metadata, $error);
                                if($result === false){
                                    array_push($err_msg, $error);
                                    $this->Session->setParameter("error_msg", $err_msg);
                                    $this->failTrans(); // rollback
                                    return 'error';
                                }
                                $nCnt_attr_flg = $nCnt_attr;
                            }
                        }
                    }
                }
                array_push($item_attr, $attr_elm);      // 1メタデータ分のユーザ入力値をセット
            }
            
            if($this->save_mode == "stay" || $this->save_mode == "next"){
                // 基本情報チェック
                $ItemRegister->checkBaseInfo($item, $err_msg, $warning);
                // 必須入力検査
                $ItemRegister->checkEntryInfo($item_attr_type, $item_num_attr, $item_attr, 'meta', $err_msg, $warning);
                
                // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
                $ItemRegister->updateInsertUserIdForContributor(
                        intval($this->Session->getParameter("edit_item_id")),
                        $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
                // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
            }
            // Add registered info save action 2009/02/09 Y.Nakao --end--
            
            $this->Session->setParameter("error_msg", $err_msg);
            $this->Session->setParameter("warning", $warning);
            
            // Fix PHP Notice 2014/06/06 Y.Nakao --end--
            
            // Add error input Screen changes  K.Matsuo 2013/5/30 --start--
            if(count($err_msg)>0){
                // 入力にエラーがあるときは画面遷移しない
                $this->save_mode = "stay";
            }
            // Add error input Screen changes  K.Matsuo 2013/5/30 --end--
            
            // Add e-person R.Matsuura 2013/11/18 --start--
            // feedback mail送信先を設定
            $feedback_mail_array = explode(",", $this->send_feedback_mail_address_mailaddresses[0]);
            $ret = $ItemRegister->deleteFeedbackMailAuthorId($item["item_id"], $item["item_no"]);
            if($ret === false)
            {
                return "error";
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
                    return "error";
                }
                
                // set author id no
                $authorIdNo++;
            }
            $feedback_mail_authors_array = explode(",", $this->send_feedback_mail_address_authors[0]);
            $this->Session->setParameter("feedback_mailaddress_str", $this->send_feedback_mail_address_mailaddresses[0]);
            $this->Session->setParameter("feedback_mailaddress_author_str", $this->send_feedback_mail_address_authors[0]);
            $this->Session->setParameter("feedback_mailaddress_array", $feedback_mail_array);
            $this->Session->setParameter("feedback_mailaddress_author_array", $feedback_mail_authors_array);
            // Add e-person R.Matsuura 2013/11/18 --end--
            
            // ------------------------------------------------------------
            // オプション個別処理
            // ------------------------------------------------------------    
            //
            // オプション処理用
            if ($this->save_mode != "next") {
                $idx = (int)($this->target);
                switch($this->save_mode) {
                    case "add_row":
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
                            // Add biblio info 2008/08/11 Y.Nakao --start--
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
                            // Add biblio info 2008/08/11 Y.Nakao --end--
                            // Add item attribute:date 2008/10/14 A.Suzuki --start--
                            case "date":
                                $new_date = array(
                                    'date_year' => '',
                                    'date_month' => '',
                                    'date_day' => '',
                                    'date' => ''
                                );
                                array_push($item_attr[$idx], $new_date);
                                break;
                            // Add item attribute:date 2008/10/14 A.Suzuki --end--
                            default:
                                break;
                        }
                    case "up_row":
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
                        // Add author_id input space
                        $attridx = (int)($this->attridx);
                        $new_author_id = array('prefix_id'=>'', 'suffix'=>'');
                        array_push($item_attr[$idx][$attridx]["external_author_id"], $new_author_id);
                        break;
                    case "clear_author":
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
                $this->Session->setParameter("item_attr", $item_attr);
                // end action
                $result = $this->exitAction(); //COMMIT
                if ( $result == false ){
                    $this->failTrans(); // rollback
                    return 'error';
                }
                
                return "stay";
            }
            
            // ------------------------------------------------------------
            // 属性情報を更新 (この時点では歯抜け状態。)
            // ------------------------------------------------------------   
            $this->Session->setParameter("item_attr", $item_attr);
            
            // Add registered info save action 2009/02/09 Y.Nakao --start--
            
            // Add registered info save action 2009/02/09 Y.Nakao --end--
            
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
                        // Add biblio info 2008/08/11 --start--
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
                        // Add biblio info 2008/08/11 --end--
                        // Add link name 2009/03/19 --start--
                        } else if($item_attr_type[$ii]['input_type']=='link'){
                            $link_data = explode("|", $item_attr[$ii][$jj]);
                            if($link_data[0] != '') {
                                array_push($array_effective, $item_attr[$ii][$jj]);
                            }
                        // Add link name 2009/03/19 --end--
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
            
            // ------------------------------------------------------------
            // 属性情報を更新 (この時点では空入力は間引かれている)
            // ------------------------------------------------------------   
            $this->Session->setParameter("item_attr", $item_attr);
            $this->Session->setParameter("item_num_attr", $item_num_attr);          
            
            // 次の画面へ
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                //print "終了処理失敗";
            }
            
            return 'success';
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
                $Exception->getCode(),          //ログID
                $Exception->getMessage(),       //主メッセージ
                $Exception->getDetailMsg() );   //詳細メッセージ           
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
            //異常終了
            $this->Session->setParameter("error_msg", $user_);
            return "error";
        }    
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
        $outContributorUserId = "";
        
        // Check handle
        if(strlen($handle) > 0)
        {
            // Execute select
            $query = "SELECT user_id ".
                     "FROM ".DATABASE_PREFIX."users ".
                     "WHERE handle = ? ;";
            $params = array();
            $params[] = $handle;
            $result = $this->Db->execute($query, $params);
            if($result == false || count($result) < 1 || !array_key_exists("user_id", $result[0]))
            {
                // Return "NotExist"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            else if(count($result) != 1)
            {
                // Return "Conflict"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
            }
            
            // Get user_id
            $outContributorUserId = $result[0]["user_id"];
        }
        
        // Check name
        if(strlen($name) > 0)
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
                // Return "NotExist"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            else if(count($result) != 1 && strlen($outContributorUserId) <= 0)
            {
                // Return "Conflict"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
            }
            else if(count($result) != 1 && strlen($outContributorUserId) > 0)
            {
                $isMatch = false;
                for($ii=0; $ii<count($result); $ii++)
                {
                    if($result[$ii]["user_id"] == $outContributorUserId)
                    {
                        $isMatch = true;
                        break;
                    }
                }
                
                if(!$isMatch)
                {
                    // Return "Conflict"
                    $outContributorUserId = "";
                    return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
                }
            }
            else if(count($result) == 1)
            {
                $tmpUserId = $result[0]["user_id"];
                if($outContributorUserId > 0 && $outContributorUserId != $tmpUserId)
                {
                    // Return "Conflict"
                    $outContributorUserId = "";
                    return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
                }
                else
                {
                    // Get user_id
                    $outContributorUserId = $tmpUserId;
                }
            }
        }
        
        // Check email
        if(strlen($email) > 0)
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
                // Return "NotExist"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
            }
            else if(count($result) != 1 && strlen($outContributorUserId) <= 0)
            {
                // Return "Conflict"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
            }
            
            // Get user_id
            $tmpUserId = $result[0]["user_id"];
            if($outContributorUserId != $tmpUserId)
            {
                // Return "Conflict"
                $outContributorUserId = "";
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_CONFLICT;
            }
        }
        
        if(strlen($outContributorUserId) > 0)
        {
            $authId = $this->getRoomAuthorityID($outContributorUserId);
            if($authId >= REPOSITORY_ITEM_REGIST_AUTH)
            {
                // Return "Success"
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_SUCCESS;
            }
            else
            {
                // Return "NoAuth"
                return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOAUTH;
            }
        }
        else
        {
            // Return "NotExist"
            return RepositoryConst::ITEM_CONTRIBUTOR_STATUS_NOTEXIST;
        }
    }
    // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
}
?>
