<?php
// --------------------------------------------------------------------
//
// $Id: Selecttype.class.php 53145 2015-05-13 10:58:06Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * アイテム登録：アイテムタイプ選択画面からの入力処理アクション
 *
 * @package     [[package名]]
 * @access      public
 * @version 1.0 新規作成
 *          2.0 登録フロー表示改善対応 2008/06/26 Y.Nakao  
 */
class Repository_Action_Main_Item_Selecttype extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    /**
     * usersViewコンポーネント
     * @var Users_View
     */
    public $usersView = null;
    
    // リクエストパラメーター
    /**
     * アイテムタイプID(新規作成時)
     * @var int
     */
    public $itemtype_id = null;
    
    /**
     * アイテムID(既存編集時)
     * @var int
     */
    public $item_id = null;
    
    /**
     * アイテムNo(既存編集時)
     * @var int
     */
    public $item_no = null;
    
    /**
     * 呼び出し元画面番号
     *  アイテム詳細画面から来た場合："1"
     *  ワークフロー画面から来た場合："2"
     * @var string
     */
    public $return_screen = null;
    
    /**
     * ワークフロータブ番号
     * @var string
     */
    public $workflow_active_tab = null;
    
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
     * @var string
     */
    public $save_mode = null;
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        //-----------------------------------------------------------------------
        // セッション情報一覧
        // 【★新規作成･編集中で普遍な情報】 : 本アクションで初期設定して以降、DB登録もしくは更新まで普遍な情報である。
        // 1.アイテムタイプ (1レコード) : "item_type_all"[''], アイテムタイプのレコードをそのまま保存したものである。
        // 2.アイテム属性タイプ (Nレコード, Order順) : "item_attr_type"[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～        
        // X.ファイル属性フラグ : "isfile", 0:file, thumbnailなし, 1:thumbnailのみあり, 2:"file"あり     
        // 【★アイテム編集で変化するメタデータ情報】 : 編集過程で逐次変化していく情報である。
        // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        // 【★アイテム編集で変化するアイテム基本情報】 : 編集過程で逐次変化していく情報である。
        // X.現在のアイテム基本情報のデータ : "base_attr"[2], (今のところタイトルと言語のユーザー入力値)
        // 【★インデックス選択とリンク設定情報】 : アイテムファミリー外の設定に必要
        // X.現在選択中のインデックス配列    : "indice"[L], L : 関連付けるインデックスの数, キーワードとインデックスIDをおさえておけばよいか。
        // X.現在選択中のリンクアイテム配列 : "link"[L], L : リンクを張るアイテムの数, アイテム名とアイテムIDをおさえておけばよいか。(いや、どうせDB検索と絡むのでレコード全部)
        // 【★その他】
        // X.ライセンスマスタ  : license_master
        // X.処理モード : edit_flag (0:新規作成, 1:既存編集)
        // X.編集中のアイテムID : edit_item_id (既存編集時のみ)
        // X.編集中のアイテムNO : edit_item_no (既存編集時のみ)
        // X.DB登録済み削除ファイルリスト : delete_file_list (既存編集時のみ)
        // X.アイテム公開日 : item_pub_date
        // X.アイテムキーワード : item_keyword
        // X.インデックス選択画面のツリー開閉情報 : open_node_index_id_link
        // X.アイテム間リンク設定画面のツリー開閉情報 : open_node_index_id_index
        // ---------------------------------------------------------------------- 
        
        // Set NameAuthority Class
        $NameAuthority = new NameAuthority($this->Session, $this->Db);
        
        // リクエストパラメタから処理モード設定
        // ※ただし、セッションに処理フラグがある場合は読み込まない
        $item_type = null;
        $item_attr_type = null;
        $Result_List = array();     // DBから取得したレコードの集合
        $Error_Msg = '';            // エラーメッセージ
        $edit_flag = 0;     // 処理モード (0:新規作成, 1:既存編集)
        $user_id = $this->Session->getParameter("_user_id");
        if($this->itemtype_id != null){
            // 新規登録モード
            $edit_flag = 0;     
            // アイテム修正時のページ遷移改善対応 2008/06/30 Y.Nakao --start--
            // DB登録後に戻る画面情報
            $this->Session->removeParameter("return_screen");
            // アイテム修正時のページ遷移改善対応 2008/06/30 Y.Nakao --end--
            // リクエストパラメタにアイテムタイプIDがあれば、アイテムタイプ情報を取得してセッションに保存
            //アイテムタイプ名をDBから取得
            $params = array("item_type_id" => $this->itemtype_id, 'is_delete'=>0);
            $item_type = $this->Db->selectExecute("repository_item_type",$params);
            // アイテムタイプレコードをセッションに保存 (kawa)
            $item_type_info = array('item_type_name' => $item_type[0]['item_type_name'],
                                    'item_type_id' => $item_type[0]['item_type_id']
                              );
            $this->Session->setParameter("item_type_all", $item_type_info);
            // アイテム属性タイプをDBから取得
            $item_attr_type = $this->Db->selectExecute("repository_item_attr_type",$params);
            if ($item_attr_type === false) {
                $tmpErrorMsg = $this->Db->ErrorMsg();
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            $this->Session->setParameter("edit_start_date", $this->TransStartDate);     // 編集基準時間を保存
        } else if($this->item_id != null && $this->item_no != null) {
            // 既存編集モード
            $edit_flag = 1;
            
            // Add check edit user auth 2011/06/02 A.Suzuki --start--
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            if(!$this->checkAuthForEditItem($user_id, $this->item_id)){
                // uneditable user
                $tmpErrorMsg = "_invalid_auth";
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            // Add check edit user auth 2011/06/02 A.Suzuki --end--
            
            // アイテム修正時のページ遷移改善対応 2008/06/30 Y.Nakao --start--
            // DB登録後に戻る画面情報
            $this->Session->setParameter("return_screen", $this->return_screen);
            // アイテム修正時のページ遷移改善対応 2008/06/30 Y.Nakao --end--
            // ワークフローの表示タブ情報 2009/07/13 A.Suzuki --start--
            $this->Session->setParameter("repository_workflow_active_tab", $this->workflow_active_tab);
            // ワークフローの表示タブ情報 2009/07/13 A.Suzuki --end--
            // アイテム情報を取得
            $result = $this->getItemData($this->item_id, $this->item_no, $Result_List, $Error_Msg, true);
            if($result === false){
                $tmpErrorMsg = $Error_Msg;
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            // Add e-person R.Matsuura 2013/11/21 --start--
            // get author send feedback mail
            $authorData = $this->getFeedbackAuthor($this->item_id, $this->item_no);
            if(count($authorData) > 0)
            {
                $result = $this->setFeedbackAuthor($authorData);
            }
            // Add e-person R.Matsuura 2013/11/21 --end--
            
            // 所属インデックス取得
            $result = $this->getItemIndexData($this->item_id, $this->item_no, $Result_List,$Error_Msg);
            if($result === false){
                $tmpErrorMsg = $Error_Msg;
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            } 
            // 参照取得
            $result = $this->getItemReference($this->item_id, $this->item_no, $Result_List,$Error_Msg);
            if($result === false){
                $tmpErrorMsg = $Error_Msg;
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            // アイテム更新日精査
            if( $this->TransStartDate < $Result_List['item'][0]['mod_date'] ) {
                $tmpErrorMsg = "error : probably this item was updated by other admin.";
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            // 値のコピー
            $this->itemtype_id = $Result_List['item_type'][0]['item_type_id'];
            $item_type_info = array('item_type_name' => $Result_List['item_type'][0]['item_type_name'],
                                    'item_type_id' => $Result_List['item_type'][0]['item_type_id']
                              );
            $this->Session->setParameter("item_type_all", $item_type_info);
            $item_attr_type = $Result_List['item_attr_type'];
            // 編集中のアイテムID, NOを保存
            $this->Session->setParameter("edit_item_id", $this->item_id);
            $this->Session->setParameter("edit_item_no", $this->item_no);
            $this->Session->setParameter("delete_file_list", array());
            //$this->Session->setParameter("edit_start_date", $Result_List['item'][0]['mod_date']);     // 編集基準時間 = 編集開始時のアイテムの最終更新日
            $this->Session->setParameter("edit_start_date", $this->TransStartDate); // 編集開始時間
        } else {
            // イリーガルな遷移
            $tmpErrorMsg = "Illegal parameter.";
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $this->Session->setParameter("edit_flag", $edit_flag);
                    
        // ライセンスマスタを保存
        $license_master = $this->Db->selectExecute("repository_license_master", array('is_delete'=>0));         
        if ($license_master === false) {
            $tmpErrorMsg = $this->Db->ErrorMsg();
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $this->Session->setParameter("license_master",$license_master);
        // 現在のアイテム基本情報のデータ : "base_attr"[2(今のところタイトルと言語)](kawa)
        $lang = 'en';
        if($this->Session->getParameter("_lang") == "japanese"){
            $lang = 'ja';
        }
        $base_attr = array(
            "title" => '',
            "title_english" => '',
            "language" => $lang
        );
        $DATE = new Date();
        $item_pub_date = array(
                        'year' => $DATE->getYear(),
                        'month' => $DATE->getMonth(),
                        'day' => $DATE->getDay());
        
        $item_keyword = '';         // アイテムキーワード
        $item_keyword_english = ''; // アイテムキーワード
        $item_element = array();    // アイテム属性タイプ
        $item_num_cand = array();   // アイテム属性選択肢数
        $option_data = array();     // アイテム属性選択肢
        $item_contributor = null;   // Contributor
        $show_no = 1;
        // アイテム属性タイプ情報を表示順に並び換える。
        // またselect, radioの場合は選択肢の数と
        for ($ii = 0; $ii < count($item_attr_type); $ii++) {
            for ($ii2 = 0; $ii2 < count($item_attr_type); $ii2++) {
                if ($item_attr_type[$ii2]['show_order'] == $show_no) {
                    $num_cand = 0;      // 選択肢の数
                    $options = array(); // 選択肢
                    // アイテム属性タイプの共通項目の設定
                    $params_attr = array(
                            'attribute_id' => $item_attr_type[$ii2]['attribute_id'],
                            'attribute_name' => $item_attr_type[$ii2]['attribute_name'],
                            'input_type' => $item_attr_type[$ii2]['input_type'],
                            'is_required' => $item_attr_type[$ii2]['is_required'],
                            'plural_enable' => $item_attr_type[$ii2]['plural_enable'],
                            'line_feed_enable' => $item_attr_type[$ii2]['line_feed_enable'],
                            'plural_enable' => $item_attr_type[$ii2]['plural_enable'],
                            'dublin_core_mapping' => $item_attr_type[$ii2]['dublin_core_mapping'],
                            'junii2_mapping' => $item_attr_type[$ii2]['junii2_mapping'],
                            'lom_mapping' => $item_attr_type[$ii2]['lom_mapping'],
                            'display_lang_type' => $item_attr_type[$ii2]['display_lang_type']
                    );
                    // check, radioは選択肢とその数を取得
                    if($item_attr_type[$ii2]['input_type'] == "select" ||
                       $item_attr_type[$ii2]['input_type'] == "checkbox" ||
                       $item_attr_type[$ii2]['input_type'] == "radio") {
                        $params_cand = array(
                                'item_type_id'=>$this->itemtype_id,
                                'attribute_id'=>$item_attr_type[$ii2]['attribute_id'],
                                'is_delete'=>0);
                        $option = $this->Db->selectExecute("repository_item_attr_candidate",$params_cand);
                        if ($option === false) {
                            $tmpErrorMsg = $this->Db->ErrorMsg();
                            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($tmpErrorMsg);
                            $exception->addError($tmpErrorMsg);
                            throw $exception;
                        }
                        $num_cand = count($option);
                        for($ii3=0; $ii3<$num_cand ; $ii3++) {
                            array_push($options, $option[$ii3]['candidate_value']);
                        }                       
                    } else {
                    }
                    array_push( $item_num_cand, $num_cand);     // アイテム属性タイプごとの選択支の数を設定
                    array_push( $option_data, $options);    
                    array_push( $item_element, $params_attr);
                    $show_no++;
                    break;
                }
            }
        }
        // Add item type multi-language 2013/07/25 K.Matsuo --start--
        $this->setItemtypeNameMultiLanguage($this->itemtype_id, $item_element);
        // Add item type multi-language 2013/07/25 K.Matsuo --end--
        // アイテム属性タイプ情報をセッションに保存
        $this->Session->setParameter("item_attr_type",$item_element);   // アイテム属性タイプ
        $this->Session->setParameter("item_num_cand",$item_num_cand);   // アイテム属性選択肢数
        $this->Session->setParameter("option_data",$option_data);       // アイテム属性選択肢
    
        // アイテム属性数／アイテム属性を初期設定
        $item_num_attr = array();
        if(count($item_attr_type) > 0)
        {
            $item_num_attr = array_fill(0, count($item_attr_type), 1);      // アイテム属性数 [N]
        }
        $item_attr = array();
        if(count($item_attr_type) > 0)
        {
            $item_attr = array_fill(0, count($item_attr_type), array(''));  // アイテム属性 [N][L]
        }
        // 現在のアイテム属性を初期設定 (kawa)
        // 新規作成 ⇒ 空文字列 or ON/OFF
        $isfile = 0;        // file存在フラグ
        $file_num = 0;      // 'file'メタデータ数
        $thumbnail_num = 0; // 'thumbnail'メタデータ数
        for ($ii = 0; $ii < count($item_attr); $ii++) {
            // チェックボックス : 初期値OFF
            if( $item_element[$ii]['input_type'] == "checkbox" ) {
                $check_init = array();
                for ($jj = 0; $jj < count($option_data[$ii]); $jj++) {
                    array_push($check_init, "0");   // "0":OFF
                }
                $item_attr[$ii] = $check_init;
            }
            // ラジオボタン : 初期値は最初の選択肢
            if( $item_element[$ii]['input_type'] == "radio" ) {
                $item_attr[$ii][0] = 0;     // 0-th candidate is checked.
            }
            // 氏名 : 姓、名、Emailの初期値をセット
            if( $item_element[$ii]['input_type'] == "name" ) {
                $name_init = array(array(
                        'family' => '',
                        'given' => '',
                        'family_ruby' => '',
                        'given_ruby' => '',
                        'email' => '',
                        'author_id' => '',
                        'external_author_id' => array(array('prefix_id'=>'', 'suffix'=>''))
                    )
                );
                $item_attr[$ii] = $name_init;
            }
            // ファイル : null = 未登録
            if( $item_element[$ii]['input_type'] == "file"){
                // Add registered info save action 2009/02/12 Y.Nakao --start--
                //$item_attr[$ii][0] = null;        // 0-th file is not uploaded yet.
                $file_no = 1;
                if($edit_flag == 1){
                    $file_no = $this->getFileNo($this->item_id, $this->item_no, $ii + 1, $err_msg);
                }
                $item_attr[$ii][0] = array('file_no' => $file_no, 'show_order' => $file_no, 'display_name' => "", 'display_type' => "");
                // Add registered info save action 2009/02/12 Y.Nakao --end--
                $file_num++;
            }
            // Add file price Y.Nakao 2008/08/28 --start--
            if($item_element[$ii]['input_type'] == "file_price") {
                // Add registered info save action 2009/02/12 Y.Nakao --start--
                //$item_attr[$ii][0] = null;        // 0-th file is not uploaded yet.
                $file_no = 1;
                if($edit_flag == 1){
                    $file_no = $this->getFileNo($this->item_id, $this->item_no, $ii + 1, $err_msg);
                }
                $item_attr[$ii][0] = array('file_no' => $file_no, 'show_order' => $file_no);
                // Add registered info save action 2009/02/12 Y.Nakao --end--
                $file_num++;
            }
            // Add file price Y.Nakao 2008/08/28 --end--
            // サムネイル : null = 未登録
            if( $item_element[$ii]['input_type'] == "thumbnail" ) {
                // Add registered info save action 2009/02/12 Y.Nakao --start--
                //$item_attr[$ii][0] = null;        // 0-th file is not uploaded yet.
                $file_no = 1;
                if($edit_flag == 1){
                    $file_no = $this->calcMaxThumbnailNo($this->item_id, $this->item_no, $ii + 1, $err_msg)+1;
                }
                $item_attr[$ii][0] = array('file_no' => $file_no, 'show_order' => $file_no);
                // Add registered info save action 2009/02/12 Y.Nakao --end--
                $thumbnail_num++;
            }
            if($item_element[$ii]['input_type'] == "biblio_info"){
                $biblio_init = array(
                    array(
                        'biblio_name' => '',
                        'biblio_name_english' => '',
                        'volume' => '',
                        'issue' => '',
                        'spage' => '',
                        'epage' => '',
                        'date_of_issued' => '',
                        'year' => '',
                        'month' => '',
                        'day' => ''
                    )
                );
                $item_attr[$ii] = $biblio_init;
            }
            if($item_element[$ii]['input_type'] == "date"){
                $date_init = array(
                    array(
                        'date' => '',
                        'date_year' => '',
                        'date_month' => '',
                        'date_day' => ''
                    )
                );
                $item_attr[$ii] = $date_init;
            }
        }
        // isfile設定
        if($file_num>0){
            $isfile = 2;
        } elseif($thumbnail_num>0){
            $isfile = 1;
        }  
        // インデックス設定／リンク設定用変数の設定
        $indice = array();
        $link = array();
        
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_ORG_CONTRIBUTOR_USER_ID, $user_id);
        $this->Session->setParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID, $user_id);
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        
        // 編集の場合は既存属性を検査してセットする必要あり
        if( $edit_flag == 1 ) {
            // 基本情報
            $base_attr = array(
                "title" => $Result_List['item'][0]['title'],
                "title_english" => $Result_List['item'][0]['title_english'],
                "language" => $Result_List['item'][0]['language']
            );
            // アイテムの公開日が設定されている場合、年月日に分解
            if( $Result_List['item'][0]['shown_date'] != null &&
                $Result_List['item'][0]['shown_date'] != '' ) {
                $pub_date = split('[ ]', $Result_List['item'][0]['shown_date']);
                list($pub_year, $pub_month, $pub_day) = split('[/.-]', $pub_date[0]);               
                $item_pub_date = array(
                            'year' => intval($pub_year),
                            'month' => intval($pub_month),
                            'day' => intval($pub_day));
            }
            // アイテムキーワード
            $item_keyword = $Result_List['item'][0]['serch_key'];
            $item_keyword_english = $Result_List['item'][0]['serch_key_english'];
            
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
            $insUserId = $Result_List['item'][0][RepositoryConst::DBCOL_COMMON_INS_USER_ID];
            $this->Session->setParameter(RepositoryConst::SESSION_PARAM_ORG_CONTRIBUTOR_USER_ID, $insUserId);
            $this->Session->setParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID, $insUserId);
            
            // Only admin user
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->getRoomAuthorityID();
            if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // Check insert user
                if(strlen($insUserId) > 0 && strlen($user_id) > 0 && $insUserId != $user_id)
                {
                    // This item's insert user is difference to this user
                    // Get insert user data
                    $user = null;
                    $userItemLink = null;
                    $user =& $this->usersView->getUserById($insUserId);
                    $userItemLink =& $this->usersView->getUserItemLinkById($insUserId);
                    $item_contributor = array();
                    
                    // Handle
                    if(is_array($user) && array_key_exists("handle", $user))
                    {
                        $item_contributor[RepositoryConst::ITEM_CONTRIBUTOR_HANDLE] = $user["handle"];
                    }
                    if(is_array($userItemLink))
                    {
                        // UserName
                        if(array_key_exists("4", $userItemLink))
                        {
                            $item_contributor[RepositoryConst::ITEM_CONTRIBUTOR_NAME] = $userItemLink["4"]["content"];
                        }
                        
                        // Email
                        if(array_key_exists("5", $userItemLink))
                        {
                            $item_contributor[RepositoryConst::ITEM_CONTRIBUTOR_EMAIL] = $userItemLink["5"]["content"];
                        }
                    }
                }
            }
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
            
            // メタデータ
            for ($ii = 0; $ii < count($item_element); $ii++) {
                // 属性未登録のメタデータは初期設定でOK
                if(count($Result_List['item_attr'][$ii]) <= 0) {
                    continue;
                }   
                $edit_attr = array();
                
                // チェックボックス : 属性にはチェックの入った選択肢の文字列が入っている
                // 選択肢と同数の配列を確保。チェックの入った選択肢に1を、入ってない選択肢に0を設定。
                if( $item_element[$ii]['input_type'] == "checkbox" ) {
                    for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                        $isGot = false;
                        for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                            if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                                array_push($edit_attr, 1);      // kk-th選択肢は選択済み
                                $isGot = true;
                                break;
                            }
                        }
                        if(!$isGot) {
                            array_push($edit_attr, 0);      // kk-th選択肢は未選択
                        }
                    }
                    // 初期属性の再セット
                    $item_attr[$ii] = $edit_attr;
                    continue;
                }
                // プルダウン : 選択肢に存在しない値がセットされていた場合、空文字をセットする
                if( $item_element[$ii]['input_type'] == "select" ) {
                    for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                        $isTrue = false;
                        for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                            if($Result_List['item_attr'][$ii][$jj]['attribute_value'] == $option_data[$ii][$kk]) {
                                $isTrue = true;
                                break;
                            }
                        }
                        
                        // 不正な値が入っていた場合の処理
                        $option_value = "";
                        if($isTrue == false) {
                            // 必須項目だった場合は選択肢の最初の項目を設定する
                            if($item_element[$ii]['is_required'] == 1) {
                                $option_value = $option_data[$ii][0];
                            }
                        } else {
                            // 選択肢と一致した値だった場合、その値を再設定する
                            $option_value = $Result_List['item_attr'][$ii][$jj]['attribute_value'];
                        }
                        array_push($edit_attr, $option_value);
                    }
                    // 属性の再セット
                    $item_attr[$ii] = $edit_attr;
                    continue;
                }
                
                // ii-thメタデータのjj-th属性をコピー
                for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                    // ラジオボタン : 
                    if( $item_element[$ii]['input_type'] == "radio" ) {
                        // 値の一致したオプションのインデックスをセット
                        for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                            if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                                array_push($edit_attr, $kk);        // 0-th candidate is checked.
                                break;
                            }
                        }
                    }
                    // 氏名 : 姓、名、Emailの初期値をセット
                    else if( $item_element[$ii]['input_type'] == "name" ) {
                        $external_author_id = $NameAuthority->getExternalAuthorIdPrefixAndSuffix($Result_List['item_attr'][$ii][$jj]['author_id']);
                        if($external_author_id === false){
                            $this->errorLog("Error occurred.", __FILE__, __CLASS__, __LINE__);
                            throw new AppException("Error occurred.");
                        }
                        $external_author_id_without_email = array();
                        for($cnt = 0; $cnt < count($external_author_id); $cnt++)
                        {
                            if($external_author_id[$cnt]["prefix_id"] == 0)
                            {
                                $email = $external_author_id[$cnt]["suffix"];
                            }
                            else
                            {
                                $tmp_array = array("prefix_id" => $external_author_id[$cnt]["prefix_id"], "suffix" => $external_author_id[$cnt]["suffix"]);
                                array_push($external_author_id_without_email, $tmp_array);
                            }
                        }
                        $name_init = array(
                            'family' => $Result_List['item_attr'][$ii][$jj]['family'],
                            'given' => $Result_List['item_attr'][$ii][$jj]['name'],
                            'family_ruby' => $Result_List['item_attr'][$ii][$jj]['family_ruby'],
                            'given_ruby' => $Result_List['item_attr'][$ii][$jj]['name_ruby'],
                            'email' => $Result_List['item_attr'][$ii][$jj]['e_mail_address'],
                            'author_id' => $Result_List['item_attr'][$ii][$jj]['author_id'],
                            'external_author_id' => $external_author_id_without_email
                        );
                        array_push($edit_attr, $name_init);
                    }
                    // Add biblio info 2008/08/11 Y.Nakao --start--
                    // 書誌情報：雑誌名、巻、号、ページ、発行年の初期値をセット
                    else if( $item_element[$ii]['input_type'] == "biblio_info" ) {
                        $date = explode("-", $Result_List['item_attr'][$ii][$jj]['date_of_issued']);
                        if(count($date) == 2){
                            array_push($date, "");
                        } else if(count($date) == 1){
                            array_push($date, "");
                            array_push($date, "");
                        }

                        if(strlen($date[1]) == 2){
                            $temp_month = str_split($date[1]);
                            if($temp_month[0] == "0"){
                                $date[1] = $temp_month[1];
                            }
                        }
                        if(strlen($date[2]) == 2){
                            $temp_day = str_split($date[2]);
                            if($temp_day[0] == "0"){
                                $date[2] = $temp_day[1];
                            }
                        }

                        $biblio_init = array(
                            'biblio_name' => $Result_List['item_attr'][$ii][$jj]['biblio_name'],
                            'biblio_name_english' => $Result_List['item_attr'][$ii][$jj]['biblio_name_english'],
                            'volume' => $Result_List['item_attr'][$ii][$jj]['volume'],
                            'issue' => $Result_List['item_attr'][$ii][$jj]['issue'],
                            'spage' => $Result_List['item_attr'][$ii][$jj]['start_page'],
                            'epage' => $Result_List['item_attr'][$ii][$jj]['end_page'],
                            'date_of_issued' => $Result_List['item_attr'][$ii][$jj]['date_of_issued'],
                            'year' =>$date[0], 
                            'month' =>$date[1],
                            'day' =>$date[2]
                        );
                        array_push($edit_attr, $biblio_init);
                    }
                    // Add biblio info 2008/08/11 Y.Nakao --end--
                    
                    // Add item attribute:date 2008/10/14 A.Suzuki --start--
                    // 日付：年月日の初期値をセット
                    else if( $item_element[$ii]['input_type'] == "date" ) {
                        $date = explode("-", $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                        if(count($date) == 2){
                            array_push($date, "");
                        } else if(count($date) == 1){
                            array_push($date, "");
                            array_push($date, "");
                        }

                        if(strlen($date[1]) == 2){
                            $temp_month = str_split($date[1]);
                            if($temp_month[0] == "0"){
                                $date[1] = $temp_month[1];
                            }
                        }
                        if(strlen($date[2]) == 2){
                            $temp_day = str_split($date[2]);
                            if($temp_day[0] == "0"){
                                $date[2] = $temp_day[1];
                            }
                        }
                        
                        $date_init = array(
                            'date' => $Result_List['item_attr'][$ii][$jj]['attribute_value'],
                            'date_year' =>$date[0],
                            'date_month' =>$date[1],
                            'date_day' =>$date[2],
                        );
                        array_push($edit_attr, $date_init);
                    }
                    // Add item attribute:date 2008/10/14 A.Suzuki --end--
                    
                    // ファイル : エンバーゴ、ライセンスなどセット
                    else if( $item_element[$ii]['input_type'] == "file" || 
                        $item_element[$ii]['input_type'] == "file_price") { // Add file price Y.Nakao 2008/08/28
                        // upload情報を作成
                        $upload = array(
                            'file_name' => $Result_List['item_attr'][$ii][$jj]['file_name'],
                            'mimetype' => $Result_List['item_attr'][$ii][$jj]['mime_type'],
                            'extension' => $Result_List['item_attr'][$ii][$jj]['extension']
                        );
                        // 2008/03/21 エンバーゴフラグ設定
                        // 既存編集の場合は既にファイルの公開日は一意に決まっているため、"オープンアクセス日を指定する"扱いとする
                        // file情報作成
                        if($item_element[$ii]['input_type'] == "file"){
                            // Add file author 2015/03/20 T.Ichikawa --start--
                            // 権限初期値
                            $auth_room_id = array();
                            $auth_num = 0;
                            // 権限情報を課金テーブルから取得する
                            $query = "SELECT price FROM ". DATABASE_PREFIX. "repository_file_price ".
                                     "WHERE item_id = ? ".
                                     "AND item_no = ? ".
                                     "AND attribute_id = ? ".
                                     "AND file_no = ? ".
                                     "AND is_delete = ? ;";
                            $params = array();
                            $params[] = $Result_List['item_attr'][$ii][$jj]['item_id'];
                            $params[] = $Result_List['item_attr'][$ii][$jj]['item_no'];
                            $params[] = $Result_List['item_attr'][$ii][$jj]['attribute_id'];
                            $params[] = $Result_List['item_attr'][$ii][$jj]['file_no'];
                            $params[] = 0;
                            $result = $this->Db->execute($query, $params);
                            if($result === false) {
                                $error_msg = $this->Db->ErrorMsg();
                                return 'error'; 
                            }
                            
                            if(count($result) > 0) {
                                // 権限が複数ある場合はパイプ区切りでレコードに入っている
                                $room_auth = explode("|", $result[0]['price']);
                                for($auth_cnt = 0; $auth_cnt < count($room_auth); $auth_cnt++) {
                                    // "[ルームID],[価格]"の形式で設定されている（価格部分はいらない）
                                    $auth = split(",", $room_auth[$auth_cnt]);
                                    if(isset($auth) && count($auth) == 2) {
                                        array_push($auth_room_id, $auth[0]);
                                        $auth_num++;
                                    }
                                }
                            }
                            // Add file author 2015/03/20 T.Ichikawa --end--
                            // ファイル情報格納
                            $file_init = array(
                                'upload' => $upload,
                                'display_name' => $Result_List['item_attr'][$ii][$jj]['display_name'],
                                'display_type' => $Result_List['item_attr'][$ii][$jj]['display_type'],
                                'licence' => 'licence_free',
                                'freeword' => '',
                                'embargo_flag' => 2,    // オープンアクセス日を指定する
                                'embargo_year' => '',
                                'embargo_month' => '',
                                'embargo_day' => '',
                                'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                                // プライマリキー保存(削除用)
                                'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                                'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                                'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                                'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                                'show_order' => $Result_List['item_attr'][$ii][$jj]['show_order'],
                                'input_type' => 'file',
                                'auth_room_id' => $auth_room_id,
                                'auth_num' => $auth_num,
                                'flash_embargo_flag' => 2,  // オープンアクセス日を指定する
                                'flash_embargo_year' => '',
                                'flash_embargo_month' => '',
                                'flash_embargo_day' => ''
                            );
                        } else if($item_element[$ii]['input_type'] == "file_price"){
                            // room_idとpriceを分解する
                            // room_id,price|room_id,price|room_id,price|...で格納されている
                            $room_price = explode("|",$Result_List['item_attr'][$ii][$jj]['price']);
                            $price_num = 0;
                            $price_value = array();
                            $room_id = array();
                            for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
                                $price = split(",", $room_price[$price_Cnt]);
                                // room_idと価格のペアがあり、価格が数字であるか判定
                                if($price!=null && count($price)==2)
                                {
                                    array_push($room_id, $price[0]);
                                    array_push($price_value, $price[1]);
                                    $price_num++;
                                }
                            }
                            // ファイル情報格納
                            $file_init = array(
                                'upload' => $upload,
                                'display_name' => $Result_List['item_attr'][$ii][$jj]['display_name'],
                                'display_type' => $Result_List['item_attr'][$ii][$jj]['display_type'],
                                'licence' => 'licence_free',
                                'freeword' => '',
                                'embargo_flag' => 2,    // オープンアクセス日を指定する
                                'embargo_year' => '',
                                'embargo_month' => '',
                                'embargo_day' => '',
                                'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                                // プライマリキー保存(削除用)
                                'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                                'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                                'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                                'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                                'show_order' => $Result_List['item_attr'][$ii][$jj]['show_order'],
                                'input_type' => 'file_price',
                                'room_id' => $room_id,
                                'price_value' => $price_value,
                                'price_num' => $price_num,
                                'flash_embargo_flag' => 2,  // オープンアクセス日を指定する
                                'flash_embargo_year' => '',
                                'flash_embargo_month' => '',
                                'flash_embargo_day' => ''
                            );
                        }
                        // ライセンス設定
                        if($Result_List['item_attr'][$ii][$jj]['license_id'] == 0) {
                            $file_init['licence'] = 'licence_free';
                            $file_init['freeword'] = $Result_List['item_attr'][$ii][$jj]['license_notation'];
                            $file_init['license_id'] = '0';
                            $file_init['license_notation'] = $file_init['freeword'];
                        } else {
                            // CreativeCommonsの場合, ライセンスIDの一致したライセンスマスタインデックス
                            for ($kk = 0; $kk < count($license_master); $kk++) {
                                if($license_master[$kk]['license_id'] == $Result_List['item_attr'][$ii][$jj]['license_id']) {
                                    $file_init['licence'] = $kk;
                                    $file_init['license_id'] = $license_master[$kk]['license_id'];
                                    $file_init['license_notation'] = $license_master[$kk]['license_notation'];
                                    break;
                                }
                            }
                        }
                        if($Result_List['item_attr'][$ii][$jj]['display_type'] == ''){
                            $file_init['display_type'] = '0';
                        }
                        // エンバーゴ設定
                        // XXXX-XX-XX 形式の日付を年月日に分解
                        $year = '';
                        $month = '';
                        $day = '';
                        // embargo date 2008/08/18 --start--
                        // Modify file, flash, item pubdate immovable. Y.Nakao 2012/02/13 --start--
                        if(strlen($Result_List['item_attr'][$ii][$jj]['pub_date']) == 0)
                        {
                            $Result_List['item_attr'][$ii][$jj]['pub_date'] = $this->TransStartDate;
                        }
                        // Modify file, flash, item pubdate immovable. Y.Nakao 2012/02/13 --end--
                        $embargo = split(' ', $Result_List['item_attr'][$ii][$jj]['pub_date']); 
                        $embargo = split('[/.-]', $embargo[0]);
                        if(count($embargo) > 0) { $year = $embargo[0]; }
                        if(count($embargo) > 1) { $month = $embargo[1]; }
                        if(count($embargo) > 2) { $day = $embargo[2]; }
                        $file_init['embargo_year'] = $year;
                        $file_init['embargo_month'] = $month;
                        $file_init['embargo_day'] = $day;
                        // Add detail file license 2008/10/23 Y.Nakao --start--
                        $now_date = explode( " ", $this->TransStartDate );
                        $now_date_array = explode( "-", $now_date[0] );
                        // Add detail file license 2008/10/23 Y.Nakao --end--
                        // 会員のみの場合は"会員のみ"とする 2008/08/19 --start--
                        if($year == "9999" && $month == "1" && $day == "1"){
                            // 会員のみ
                            $file_init['embargo_flag'] = 3;
                            // 表示されるオープンアクセス日指定日時は表示日時
                            $file_init['embargo_year'] = $item_pub_date["year"];
                            $file_init['embargo_month'] = $item_pub_date["month"];
                            $file_init['embargo_day'] = $item_pub_date["day"];
                        }
                        else if($year == "9999" && $month == "12" && $day == "31" && $Result_List['item_attr'][$ii][$jj]['display_type'] == 2){
                            // Do not download file.
                            $file_init['embargo_flag'] = 4;
                            // 表示されるオープンアクセス日指定日時は表示日時
                            $file_init['embargo_year'] = $item_pub_date["year"];
                            $file_init['embargo_month'] = $item_pub_date["month"];
                            $file_init['embargo_day'] = $item_pub_date["day"];
                        }
                        // Modify file, flash immovable. Y.Nakao 2012/02/13 --start--
                        else {
                            $file_init['embargo_flag'] = 2;
                        }
                        
                        // Modify file, flash immovable. Y.Nakao 2012/02/13 --end--
                        
                        // Add flash embargo 2010/02/08 A.Suzuki --start--
                        // フラッシュファイルエンバーゴ設定
                        // XXXX-XX-XX 形式の日付を年月日に分解
                        $year = '';
                        $month = '';
                        $day = '';
                        // Modify file, flash, item pubdate immovable. Y.Nakao 2012/02/13 --start--
                        if(strlen($Result_List['item_attr'][$ii][$jj]['flash_pub_date']) == 0)
                        {
                            $Result_List['item_attr'][$ii][$jj]['flash_pub_date'] = $this->TransStartDate;
                        }
                        // Modify file, flash, item pubdate immovable. Y.Nakao 2012/02/13 --end--
                        $flash_embargo = split(' ', $Result_List['item_attr'][$ii][$jj]['flash_pub_date']); 
                        $flash_embargo = split('[/.-]', $flash_embargo[0]);             
                        if(count($flash_embargo) > 0) { $year = $flash_embargo[0]; }
                        if(count($flash_embargo) > 1) { $month = $flash_embargo[1]; }
                        if(count($flash_embargo) > 2) { $day = $flash_embargo[2]; }
                        $file_init['flash_embargo_year'] = $year;
                        $file_init['flash_embargo_month'] = $month;
                        $file_init['flash_embargo_day'] = $day;
                        //$pub_time = mktime( 0, 0, 0, intval( $month ), intval( $day ), intval( $year ) );
                        $diff = Date_Calc::compareDates(intval($now_date_array[2]),intval($now_date_array[1]),intval($now_date_array[0]),intval($day),intval($month),intval($year));
                        if($year == "9999" && $month == "1" && $day == "1"){
                            // 会員のみ
                            $file_init['flash_embargo_flag'] = 3;
                            // 表示されるオープンアクセス日指定日時は表示日時
                            $file_init['flash_embargo_year'] = $item_pub_date["year"];
                            $file_init['flash_embargo_month'] = $item_pub_date["month"];
                            $file_init['flash_embargo_day'] = $item_pub_date["day"];
                        }
                        // Modify file, flash immovable. Y.Nakao 2012/02/13 --start--
                        // when today after file's pub_date, this file is open access
                        // get flash's pub date
                        else
                        {
                            $file_init['flash_embargo_flag'] = 2;
                        }
                        // Modify file, flash immovable. Y.Nakao 2012/02/13 --start--
                        // Add flash embargo 2010/02/08 A.Suzuki --start--
                        
                        // 会員のみの場合は"会員のみ"とする 2008/08/19 --end--
                        array_push($edit_attr, $file_init);
                    }
                    // サムネイル : null = 未登録
                    else if( $item_element[$ii]['input_type'] == "thumbnail" ) {
                        // file情報作成
                        $file_init = array(
                            'upload' => array(
                                'file_name' => $Result_List['item_attr'][$ii][$jj]['file_name'],
                                'mimetype' => $Result_List['item_attr'][$ii][$jj]['mime_type'],
                                'extension' => $Result_List['item_attr'][$ii][$jj]['extension']
                            ),
                            'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                            // プライマリキー保存(削除用)
                            'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                            'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                            'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                            'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                            'show_order' => $Result_List['item_attr'][$ii][$jj]['show_order'],
                            'input_type' => 'thumbnail'
                        );                      
                        array_push($edit_attr, $file_init);
                    }
                    // BugFix add when input type is supple T.Koyasu 2014/09/16 --start--
                    // サプリメンタルコンテンツ(PHP Notice対応)
                    else if( $item_element[$ii]['input_type'] == "supple"){
                        // アイテム編集時には特に修正の必要なし
                    }
                    // BugFix add when input type is supple T.Koyasu 2014/09/16 --end--
                    // その他テキスト系
                    else {
                        array_push($edit_attr, $Result_List['item_attr'][$ii][$jj]['attribute_value']);                         
                    }
                }
                // 初期属性の再セット
                $item_attr[$ii] = $edit_attr;
                $item_num_attr[$ii] = count($edit_attr);
            }
            // 登録INDEXと表示インデックスが異なる不具合対応 2008/07/02 Y.Nakao --start-- 
            $indice = $Result_List['position_index'];
            
            // change index tree 2008/12/03 Y.Nakao --start--
            for($ii=0; $ii<count($indice); $ii++){  
                $query = "SELECT index_name, index_name_english FROM ".DATABASE_PREFIX."repository_index ".
                         "WHERE index_id = ".$indice[$ii]["index_id"].";";
                $ret = $this->Db->execute($query);
                if($ret === false || count($ret)!=1){
                    $tmpErrorMsg = "Select index error.";
                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($tmpErrorMsg);
                    $exception->addError($tmpErrorMsg);
                    throw $exception;
                }
                if($this->Session->getParameter("_lang") == "japanese"){
                    $indice[$ii]["index_name"] = $ret[0]["index_name"];
                } else {
                    $indice[$ii]["index_name"] = $ret[0]["index_name_english"];
                }
            }
            // change index tree 2008/12/03 Y.Nakao --end--
            
            // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --start--
            $indice = $this->addPrivateTreeInPositionIndex($indice, $insUserId);
            // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --end--
            
            // 登録INDEXと表示インデックスが異なる不具合対応 2008/07/02 Y.Nakao --end--
            // リンクの読み込み(リンク先のアイテムID, アイテムNo, タイトルを保存・・・いや全部持っておけばいいか)
            $link = array();
            for ($ii = 0; $ii < count($Result_List['reference']); $ii++) {
                // リンク先アイテム情報取得
                $dest_item = array();
                $result = $this->getItemTableData(
                                $Result_List['reference'][$ii]['dest_reference_item_id'],
                                $Result_List['reference'][$ii]['dest_reference_item_no'],
                                $dest_item, $Error_Msg);
                if($result === false){
                    $tmpErrorMsg = $Error_Msg;
                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($tmpErrorMsg);
                    $exception->addError($tmpErrorMsg);
                    throw $exception;
                }                   
                // リンク先アイテムレコード+αを保存
                if( count($dest_item['item']) > 0) {
                    // リンク先アイテムのアイテムタイプを取得
                    $query = "SELECT * ".
                             "FROM ". DATABASE_PREFIX ."repository_item_type ".     // アイテムタイプ
                             "WHERE item_type_id = ? AND ".
                             "is_delete = ?; ";         // 削除フラグ
                    $params = null;
                    $params[] = $dest_item['item'][0]['item_type_id'];
                    $params[] = 0;
                    // SELECT実行
                    $dest_item_type = $this->Db->execute($query, $params);
                    if($dest_item_type === false){
                        $tmpErrorMsg = $this->Db->ErrorMsg();
                        $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                        $exception = new AppException($tmpErrorMsg);
                        $exception->addError($tmpErrorMsg);
                        throw $exception;
                    }
                    $dest_item['item'][0]['item_type_name'] = $dest_item_type[0]['item_type_name'];
                    $dest_item['item'][0]['relation'] = $Result_List['reference'][$ii]['reference'];
                    array_push($link , $dest_item['item'][0]);
                }
            }
        }
        
        // アイテム属性数／アイテム属性をセッションに保存
        $this->Session->setParameter("isfile",$isfile);
        $this->Session->setParameter("item_num_attr",$item_num_attr);
        $this->Session->setParameter("item_attr",$item_attr);
        $this->Session->setParameter("base_attr",$base_attr);
        $this->Session->setParameter("item_pub_date",$item_pub_date);
        $this->Session->setParameter("item_keyword",$item_keyword);
        $this->Session->setParameter("item_keyword_english",$item_keyword_english);
        $this->Session->setParameter("indice",$indice);
        $this->Session->setParameter("link",$link);
        $this->Session->setParameter("item_contributor", $item_contributor);
        
        // DOI付与可能フラグ
        $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
        $doi_itemtype_flag = false;
        if($CheckDoi->checkDoiGrantItemtype($this->itemtype_id, Repository_Components_Checkdoi::TYPE_JALC_DOI) || 
           $CheckDoi->checkDoiGrantItemtype($this->itemtype_id, Repository_Components_Checkdoi::TYPE_CROSS_REF) ||
           $CheckDoi->checkDoiGrantItemtype($this->itemtype_id, Repository_Components_Checkdoi::TYPE_LIBRARY_JALC_DOI) ||
           $CheckDoi->checkDoiGrantItemtype($this->itemtype_id, Repository_Components_Checkdoi::TYPE_DATACITE) ||
           $CheckDoi->getDoiStatus($this->item_id, $this->item_no) >= 1)
        {
            $doi_itemtype_flag = true;
        }
        $this->Session->setParameter("doi_itemtype_flag", $doi_itemtype_flag);
        
        // ファイル属性が無ければメタデータ登録画面に遷移
        if ($isfile==0) {
            $this->Session->setParameter("attr_file_flg", 0);
        } else {
            $this->Session->setParameter("attr_file_flg", 1);
        }
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "selecttype",
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
    
    /**
     * set redirect screen
     *
     * @param $item_id
     * @param $item_no
     * @access  private
     */
    private function setRedirectScreen($item_id, $item_no){
        if($this->Session->getParameter("return_screen") != null){
            // 詳細画面を表示させるための設定
            $this->Session->setParameter("item_id_for_detail", $item_id);
            $this->Session->setParameter("item_no_for_detail", $item_no);
            $this->Session->setParameter("search_flg","true");
            if($this->Session->getParameter("return_screen") === "1"){
                $this->Session->removeParameter("return_screen");
                // ワークフロー画面でないことを示すフラグ
                $this->Session->setParameter("workflow_flg", "false");
                // 画面右に詳細表示を表示させるフラグ
                $this->Session->setParameter("serach_screen", "1");
                $this->Session->setParameter("redirect_flg", "detail");
                $this->Session->setParameter("redirect_item_id", $item_id);
            } else if($this->Session->getParameter("return_screen") === "2"){
                $this->Session->removeParameter("return_screen");
                // ワークフロー画面であることを示すフラグ
                $this->Session->setParameter("workflow_flg", "true");
                $this->Session->setParameter("redirect_flg", "workflow");
            }
        } else {
            $this->Session->setParameter("redirect_flg", "selecttype");
        }
    }
    
    /**
     * get Feedback author
     *
     * @param $item_id
     * @param $item_no
     * @return  array
     */
    private function getFeedbackAuthor($itemId, $itemNo)
    {
            // get author id
        $query = "SELECT author_id " .
                 "FROM ". DATABASE_PREFIX ."repository_send_feedbackmail_author_id " .
                 "WHERE item_id = ? " .
                 "AND item_no = ? " .
                 "order by author_id_no ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        // Run query
        $result_get_author = $this->dbAccess->executeQuery( $query, $params );
        
        // get mail address
        $query = "SELECT AUTHOR.name, AUTHOR.family, SUFFIX.suffix " .
                 "FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix AS SUFFIX " .
                 "INNER JOIN ". DATABASE_PREFIX ."repository_name_authority AS AUTHOR ".
                 "ON SUFFIX.author_id = AUTHOR.author_id ".
                 "WHERE SUFFIX.prefix_id = 0 " .
                 "AND SUFFIX.author_id IN ( " ;
        $params = array();
        for($ii = 0; $ii < count($result_get_author); $ii++) {
            $params[] = $result_get_author[$ii]["author_id"];
            if($ii == 0) {
                $query .= "?";
            }
            else
            {
                $query .= ", ?";
            }
        }
        $query .= " ) AND SUFFIX.is_delete = 0 ".
                  "AND AUTHOR.is_delete = 0 ;";
        // Run query
        $result = array();
        if(count($result_get_author) > 0) {
            $result = $this->dbAccess->executeQuery( $query, $params );
        }
        return $result;
    }
    
    /**
     * set Session Feedback author
     *
     * @param $authorData
     */
    private function setFeedbackAuthor($authorData)
    {
        $feedback_author_str = "";
        $feedback_mail_address_str = "";
        $feedback_mail_author_array = array();
        $feedback_mail_array = array();
        for($cnt = 0; $cnt < count($authorData); $cnt++)
        {
            if($cnt > 0)
            {
                $feedback_author_str .= ",";
                $feedback_mail_address_str .= ",";
            }
            $feedback_author_str .= $authorData[$cnt]["name"]. " " .$authorData[$cnt]["family"]. " " .$authorData[$cnt]["suffix"];
            $feedback_mail_address_str .= $authorData[$cnt]["suffix"];
            $feedback_mail_author_array[$cnt] = $authorData[$cnt]["name"]. " " .$authorData[$cnt]["family"]. " " .$authorData[$cnt]["suffix"];
            $feedback_mail_array[$cnt] = $authorData[$cnt]["suffix"];
        }
        $this->Session->setParameter("feedback_mailaddress_str", $feedback_mail_address_str);
        $this->Session->setParameter("feedback_mailaddress_author_str", $feedback_author_str);
        $this->Session->setParameter("feedback_mailaddress_array", $feedback_mail_array);
        $this->Session->setParameter("feedback_mailaddress_author_array", $feedback_mail_author_array);
        
        return true;
    }
}
?>
