<?php
// --------------------------------------------------------------------
//
// $Id: Editfiles.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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

/**
 * アイテム登録：ファイル選択画面からの入力処理アクション
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Editfiles extends RepositoryAction
{
    // リクエストパラメーター
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
     *   'delete_file'  : アップロード済みのファイルを削除
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
    
    /**
     * ファイルのid配列
     *   ("TILL", "NO", "YES"), ファイル入力欄の数と対応
     * @var array
     */
    public $input_ids_file = null;
    
    /**
     * サムネイルのid配列
     *   ("TILL", "NO", "YES"), サムネイル入力欄の数と対応
     * @var array
     */
    public $input_ids_thumbnail = null; 
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        // Uploadアクションでサーバに上げたファイルのUploadレコードを取得
        // これは前ページのファイル入力項の数だけ渡る(空白、エラー分を含む)
        $filelist = $this->Session->getParameter("filelist");
        
        // セッション情報取得
        $item_attr_type = $this->Session->getParameter("item_attr_type");   // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_attr = $this->Session->getParameter("item_num_attr");     // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr = $this->Session->getParameter("item_attr");             // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $edit_start_date = $this->Session->getParameter("edit_start_date"); // X.アイテム公開日 : item_pub_date
        $edit_flag = $this->Session->getParameter("edit_flag");
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        $base_attr = $this->Session->getParameter("base_attr");
        $item_type_all = $this->Session->getParameter("item_type_all");
        $smarty_assign = $this->Session->getParameter("smartyAssign");
        $license_master = $this->Session->getParameter("license_master");
        $delete_file_list = $this->Session->getParameter("delete_file_list");
        if(!isset($delete_file_list) || !is_array($delete_file_list))
        {
            $delete_file_list = array();
        }
        
        // delete file recorde
        $error_flg = false;
        // sort file no
        //2013/6/17 A.Jin --実ファイルファイル削除処理は「保存」「次へ」ボタン押下時に行うように修正
        if($this->save_mode != 'add_row' && $this->save_mode != 'up_row' && $this->save_mode != 'down_row' && $this->save_mode != 'delete_file'){
            for($ii=0; $ii<count($delete_file_list); $ii++){
                if($delete_file_list[$ii]["input_type"] == "file"){
                    $result = $ItemRegister->deleteFile($delete_file_list[$ii], false, $error);
                } else if($delete_file_list[$ii]["input_type"] == "file_price"){
                    $result = $ItemRegister->deleteFile($delete_file_list[$ii], true, $error);
                } else if($delete_file_list[$ii]["input_type"] == "thumbnail"){
                    $result = $ItemRegister->deleteThumbnail($delete_file_list[$ii], $error);
                }
                if($result === false){
                    $this->addErrMsg($error);
                    $error_flg = true;
                }
            }
            $this->Session->removeParameter("delete_file_list");
            $delete_file_list = array();
        }
        
        // ファイルのデフォルト公開日計算 (編集開始日)
        $def_pub_year = '';
        $def_pub_month = '';
        $def_pub_day = '';
        $list = split(' ', $edit_start_date);
        $list = split('[/.-]', $list[0]);
        $def_pub_year = $list[0];
        $def_pub_month = $list[1];
        $def_pub_day = $list[2];
        // 今回新たなファイル入力があったか否か検査
        if($filelist != null &&
           (($this->input_ids_file != null && count($this->input_ids_file)>0 ) ||
           ($this->input_ids_thumbnail != null && count($this->input_ids_thumbnail)>0)))
        {
            // カウンタ
            $cntUpdFileThumb = 0;
            $cntTotalFile = 0;
            $cntTotalThumb = 0;
            $cntTotalFreeword = 0;
            // アイテム追加属性(メタデータ)をセッションに保存
            // ii-thメタデータのリクエストを保存
            for($ii=0; $ii<count($item_attr_type); $ii++) {
                // ii-thメタデータのjj-th属性値のリクエストを保存
                for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                    // 新規登録ファイルのID照合
                    // ii-thメタデータの入力形式ごとのリクエストを保存
                    if(    $item_attr_type[$ii]['input_type']=='file' || 
                        $item_attr_type[$ii]['input_type']=='file_price'){ // Add file price Y.Nakao 2008/08/28
                        // input_ids[] : [i][j]個送信。入力済みは"TILL", 未入力は"NO", 入力は"YES"
                        if(    $this->input_ids_file[$cntTotalFile] == 'YES' ||
                            $this->input_ids_file[$cntTotalFile] == 'NO'){
                            // アップロード失敗=falseでなく、空レコードで戻る。
                            // Bug Fix use null key 2014/09/05 T.Ichiakwa --start--
                            if(isset($filelist[$cntUpdFileThumb])) {
                                // Bug Fix use null key 2014/09/05 T.Ichiakwa --end--
                                if($filelist[$cntUpdFileThumb]['file_name']!='' ) {
                                    // Add file price Y.Nakao 2008/08/28 --start--
                                    // Add registered info save action 2009/01/29 Y.Nakao --start--
                                    // registered new file
                                    $file = array();
                                    $item = array();
                                    if($edit_flag == 0){
                                        // 新規登録時
                                        $file["item_id"] = intval($this->Db->nextSeq("repository_item"));
                                        $file["item_no"] = 1;
                                        $item["item_id"] = $file["item_id"];
                                        $item["item_no"] = 1;
                                        $item["title"] = $filelist[$cntUpdFileThumb]["file_name"];
                                        $item["title_english"] = "";
                                        $item["language"] = $base_attr["language"];
                                        $item["item_type_id"] = $item_type_all["item_type_id"];
                                        $item["serch_key"] = "";
                                        $item["serch_key_english"] = "";
                                        $item["pub_year"] = "";
                                        $item["pub_month"] = "";
                                        $item["pub_day"] = "";
                                        $result = $ItemRegister->entryItem($item, $error);
                                        if($result === false){
                                            // upload faild
                                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                            $exception = new AppException($error);
                                            $exception->addError($error);
                                            throw $exception;
                                        }
                                        $this->Session->setParameter("edit_item_id", $file["item_id"]);
                                        $this->Session->setParameter("edit_item_no", $file["item_no"]);
                                        $this->Session->setParameter("edit_flag", 1);
                                        $edit_flag = 1;
                                    } else if($edit_flag == 1){
                                        //既存編集時
                                        // 編集中のアイテムIDをセッションから取得
                                        $file["item_id"] = intval($this->Session->getParameter("edit_item_id"));
                                        $file["item_no"] = intval($this->Session->getParameter("edit_item_no"));
                                        $result = $ItemRegister->editItem($file["item_id"], $file["item_no"], $error);
                                        if($result === false){
                                            // upload faild
                                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                            $exception = new AppException($error);
                                            $exception->addError($error);
                                            throw $exception;
                                        }
                                    }
                                    $file["item_type_id"] = $item_type_all["item_type_id"];
                                    $file["attribute_id"] = $item_attr_type[$ii]['attribute_id'];
                                    $file["file_no"] = $item_attr[$ii][$jj]['file_no'];
                                    $file["show_order"] = $item_attr[$ii][$jj]['show_order'];
                                    $file["upload"] = $filelist[$cntUpdFileThumb];
                                    
                                    // Add confirm that array's index exists 2013/09/11 K.Matsushita --start--
                                    if (!isset($item_attr[$ii][$jj]['display_name'] )) {
                                        $item_attr[$ii][$jj]['display_name'] = "";
                                    }
                                    $file["display_name"] = $item_attr[$ii][$jj]['display_name'];
                                    
                                    if (!isset($item_attr[$ii][$jj]['display_type'])){
                                        $item_attr[$ii][$jj]['display_type'] = "";
                                    }
                                    $file["display_type"] = $item_attr[$ii][$jj]['display_type'];
                                    // Add confirm that array's index exists 2013/09/11 K.Matsushita --end--
                                    
                                    $file["pub_year"] = $def_pub_year;
                                    $file["pub_month"] = $def_pub_month;
                                    $file["pub_day"] = $def_pub_day;
                                    $file["license_id"] = '0';
                                    $file["license_notation"] = '';
                                    $result = $ItemRegister->entryFile($file, $error);
                                    if($result === false){
                                        // upload faild
                                        $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                        $exception = new AppException($error);
                                        $exception->addError($error);
                                        throw $exception;
                                    }
                                    if($item_attr_type[$ii]['input_type']=='file'){
                                        $newfile = array(
                                            'item_id'  => $file["item_id"],
                                            'item_no'  => $file["item_no"],
                                            'attribute_id'  => $file["attribute_id"],
                                            'file_no'  => $file["file_no"],
                                            'upload'   => $filelist[$cntUpdFileThumb],
                                            'display_name'  => $file["display_name"],
                                            'show_order' => $file["show_order"],
                                            'display_type'  => $file["display_type"],
                                            'licence'  => 'licence_free',
                                            'freeword' => '',
                                            'embargo_flag' => '1',        // 1.オープンアクセス
                                            'embargo_year' => $def_pub_year,
                                            'embargo_month' => $def_pub_month,
                                            'embargo_day' => $def_pub_day,
                                            'is_db_exist' => 0,        // DBにこのレコードが登録されていないという証
                                            // Add file authority 2015/03/18 T.Ichikawa --start--
                                            'auth_room_id' => array(0 => 0),
                                            'auth_num' => 1,
                                            // Add file authority 2015/03/18 T.Ichikawa --end--
                                            'flash_embargo_flag' => '1',        // 1.オープンアクセス
                                            'flash_embargo_year' => $def_pub_year,
                                            'flash_embargo_month' => $def_pub_month,
                                            'flash_embargo_day' => $def_pub_day
                                        );
                                    } else if($item_attr_type[$ii]['input_type']=='file_price'){
                                        // Add registered info save action 2009/01/29 Y.Nakao --start--
                                        $newfile = array(
                                            'item_id'  => $file["item_id"],
                                            'item_no'  => $file["item_no"],
                                            'attribute_id'  => $file["attribute_id"],
                                            'file_no'  => $file["file_no"],
                                            'upload'   => $filelist[$cntUpdFileThumb],
                                            'display_name'  => $file["display_name"],
                                            'show_order' => $file["show_order"],
                                            'display_type'  => $file["display_type"],
                                            'licence'  => 'licence_free',
                                            'freeword' => '',
                                            'embargo_flag' => '3',        // 3.login user only
                                            'embargo_year' => $def_pub_year,
                                            'embargo_month' => $def_pub_month,
                                            'embargo_day' => $def_pub_day,
                                            'is_db_exist' => 0,        // DBにこのレコードが登録されていないという証
                                            'price_value' => array(0 => 0),
                                            'room_id' => array(0 => 0),
                                            'price_num' => 1,
                                            'flash_embargo_flag' => '1',        // 1.オープンアクセス
                                            'flash_embargo_year' => $def_pub_year,
                                            'flash_embargo_month' => $def_pub_month,
                                            'flash_embargo_day' => $def_pub_day
                                        );
                                        // registered new file
                                        // Add registered info save action 2009/01/29 Y.Nakao --end--
                                        // Add registered info save action 2009/02/04 Y.Nakao --start--
                                        $result = $ItemRegister->entryFilePrice($newfile, $error);
                                        if($result === false){
                                            // set file price error
                                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                            $exception = new AppException($error);
                                            $exception->addError($error);
                                            throw $exception;
                                        }
                                        // Add registered info save action 2009/02/04 Y.Nakao --end--
                                    }
                                // Add file price Y.Nakao 2008/08/28 --end--
                                // uploadレコード を保存
                                $item_attr[$ii][$jj] = $newfile;
                                } else {
                                    // ファイルアップロードに失敗もしくは未入力の分
                                    //$item_attr[$ii][$jj] = null;
                                }
                            }
                            $cntUpdFileThumb++;
                        }
                        $cntTotalFile++;
                    // サムネイルの場合
                    } else if( $item_attr_type[$ii]['input_type']=='thumbnail'){ 
                        // input_ids[] : [i][j]個送信。入力済みは"TILL", 未入力は"NO", 入力は"YES"
                        if( $this->input_ids_thumbnail[$cntTotalThumb] == 'YES' ||
                            $this->input_ids_thumbnail[$cntTotalThumb] == 'NO')
                        {
                            if(isset($filelist[$cntUpdFileThumb])) {
                                if($filelist[$cntUpdFileThumb]['file_name']!='') {
                                    // 画像が入力されたか検査
                                    $mimetype = $filelist[$cntUpdFileThumb]['mimetype'];
                                       if(strpos($mimetype,"image")===false) {
                                        // 画像でない場合, ファイルを破棄して警告メッセージ設定
                                        // (===falseでないとひっかからないため、注意)
                                        $msg = $smarty_assign->getLang("repository_item_not_img");
                                        $this->addErrMsg(sprintf($msg, $item_attr_type[$ii]['attribute_name'])." : ".$mimetype);
                                        $item_attr[$ii][$jj] = null;
                                    } else {
                                        // Add registered info save action 2009/01/29 Y.Nakao --start--
                                        // registered new thumbnail
                                        $file = array();
                                        $item = array();
                                        if($edit_flag == 0){
                                            // 新規登録時
                                            $file["item_id"] = intval($this->Db->nextSeq("repository_item"));
                                            $file["item_no"] = 1;
                                            $item["item_id"] = $file["item_id"];
                                            $item["item_no"] = 1;
                                            $item["title"] = $filelist[$cntUpdFileThumb]["file_name"];
                                            $item["title_english"] = "";
                                            $item["language"] = $base_attr["language"];
                                            $item["item_type_id"] = $item_type_all["item_type_id"];
                                            $item["serch_key"] ="";
                                            $item["serch_key_english"] = "";
                                            $item["pub_year"] = "";
                                            $result = $ItemRegister->entryItem($item, $error);
                                            if($result === false){
                                                // upload faild
                                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                                $exception = new AppException($error);
                                                $exception->addError($error);
                                                throw $exception;
                                            }
                                            $this->Session->setParameter("edit_item_id", $file["item_id"]);
                                            $this->Session->setParameter("edit_item_no", $file["item_no"]);
                                            $this->Session->setParameter("edit_flag", 1);
                                            $edit_flag = 1;
                                        } else if($edit_flag == 1){
                                            //既存編集時
                                            // 編集中のアイテムIDをセッションから取得
                                            $file["item_id"] = intval($this->Session->getParameter("edit_item_id"));
                                            $file["item_no"] = intval($this->Session->getParameter("edit_item_no"));
                                            $result = $ItemRegister->editItem($file["item_id"], $file["item_no"], $error);
                                            if($result === false){
                                                // upload faild
                                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                                $exception = new AppException($error);
                                                $exception->addError($error);
                                                throw $exception;
                                            }
                                        }
                                        $file["item_type_id"] = $item_type_all["item_type_id"];
                                        $file["attribute_id"] = $item_attr_type[$ii]['attribute_id'];
                                        $file["file_no"] = $item_attr[$ii][$jj]['file_no'];
                                        $file["show_order"] = $item_attr[$ii][$jj]['show_order'];
                                        $file["upload"] = $filelist[$cntUpdFileThumb];
                                        $file["pub_year"] = $def_pub_year;
                                        $file["pub_month"] = $def_pub_month;
                                        $file["pub_day"] = $def_pub_day;
                                        $file["width"] = 0;
                                        $file["height"] = 0;
                                        $result = $ItemRegister->entryThumbnail($file, $error);
                                        if($result === false){
                                            // upload faild
                                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                            $exception = new AppException($error);
                                            $exception->addError($error);
                                            throw $exception;
                                        }
                                        // Add registered info save action 2009/01/29 Y.Nakao --end--
                                        // 画像の場合, ファイル情報を保存
                                        $newfile = array(
                                            'item_id'  => $file["item_id"],
                                            'item_no'  => $file["item_no"],
                                            'attribute_id'  => $file["attribute_id"],
                                            'file_no'  => $file["file_no"],
                                            'upload'   => $filelist[$cntUpdFileThumb],
                                            'show_order' => $file["show_order"],
                                            'is_db_exist' => 0,        // DBにこのレコードが登録されていないという証
                                            'width'=>$file["width"],
                                            'height'=>$file["height"]
                                        );
                                        // uploadレコードを保存
                                        $item_attr[$ii][$jj] = $newfile;
                                    }
                                } else {
                                    // ファイルアップロードに失敗もしくは未入力の分
                                    //$item_attr[$ii][$jj] = null;
                                }
                            }
                            $cntUpdFileThumb++;
                        }
                        $cntTotalThumb++;
                    }
                }
            }
        }
        // item_attrに移し変えたら、もう不要
        $this->Session->removeParameter("filelist");
        
        // Add toll file 2008/08/12 Y.Nakao --start--
        // ------------------------------------------------------------
        // 課金ファイル設定のため、すべてのグループを取得する
        // ------------------------------------------------------------
        // get group list
        $all_group = array();
        $user_error_msg = "";
        $result = $this->getGroupList($all_group, $user_error_msg);
        if($result === false){
            $this->errorLog($user_error_msg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($user_error_msg);
            $exception->addError($user_error_msg);
            throw $exception;
        }
        // Sessionに保持
        $this->Session->setParameter("all_group", $all_group);
        // Add toll file 2008/08/12 Y.Nakao --end--
        
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        if($this->save_mode == 'stay' || $this->save_mode == 'next')
        {
            $ItemRegister->updateInsertUserIdForContributor(
                intval($this->Session->getParameter("edit_item_id")),
                $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        }
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
        
        // Add error input Screen changes  K.Matsuo 2013/5/30 --start--
        if(!is_null($this->errMsg) && count($this->errMsg)>0){
            // 入力にエラーがあるときは画面遷移しない
            $this->save_mode = "stay";
        }
        // Add error input Screen changes  K.Matsuo 2013/5/30 --end--
        // ------------------------------------------------------------
        // オプション個別処理
        // ------------------------------------------------------------
        $blankCheckFlag = true;
        if ($this->save_mode != null) {
            $idx = (int)($this->target);
            switch($this->save_mode) {
                case "add_row":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // Add registered info save action 2009/02/05 Y.Nakao --start--
                    $attridx = intval($item_num_attr[$idx])-1;
                    $item_id = '';
                    $item_no = '';
                    $attribute_id = '';
                    $file_no = 1;
                    for($ii=0; $ii<count($item_attr[$idx]); $ii++){
                        if(isset($item_attr[$idx][$ii]['item_id']))
                        {
                            $item_id = $item_attr[$idx][$ii]['item_id'];
                        }
                        if(isset($item_attr[$idx][$ii]['item_no']))
                        {
                            $item_no = $item_attr[$idx][$ii]['item_no'];
                        }
                        if(isset($item_attr[$idx][$ii]['attribute_id']))
                        {
                            $attribute_id = $item_attr[$idx][$ii]['attribute_id'];
                        }
                        if($file_no <= $item_attr[$idx][$ii]["file_no"]){
                            $tmp1_file_no = $item_attr[$idx][$ii]["file_no"] + 1;
                            if(isset($item_attr[$idx][$attridx]['item_id']))
                            {
                                $tmp2_file_no = $this->getFileNo($item_attr[$idx][$attridx]['item_id'], 
                                                                 $item_attr[$idx][$attridx]['item_no'], 
                                                                 $item_attr[$idx][$attridx]['attribute_id'], 
                                                                 $error);
                                if($tmp2_file_no===false){
                                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                    $exception = new AppException($error);
                                    $exception->addError($error);
                                    throw $exception;
                                }
                                if($tmp1_file_no > $tmp2_file_no)
                                {
                                    $file_no = $tmp1_file_no;
                                }
                                else
                                {
                                    $file_no = $tmp2_file_no;
                                }
                            }
                            else 
                            {
                                $file_no = $tmp1_file_no;
                            }
                        }
                    }
                    array_push($item_attr[$idx], array( 'item_id' => $item_id,
                                                        'item_no' => $item_no,
                                                        'attribute_id' => $attribute_id,
                                                        'file_no' => $file_no,
                                                        'show_order' => $file_no));
                    // Add registered info save action 2009/02/05 Y.Nakao --end--
                    // target-thメタデータの属性数を増やす    
                    $item_num_attr[$idx] = $item_num_attr[$idx] + 1;
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
                    // Add registered info save action 2009/02/05 Y.Nakao --start--
                    $tmpShowOrder = $item_attr[$idx][$attridx-1]['show_order'];
                    $item_attr[$idx][$attridx-1]['show_order'] = $item_attr[$idx][$attridx]['show_order'];
                    $item_attr[$idx][$attridx]['show_order'] = $tmpShowOrder;
                    // Add registered info save action 2009/02/05 Y.Nakao --end--
                    if($item_attr_type[$idx]['input_type'] == 'file' || $item_attr_type[$idx]['input_type'] == 'file_price'){
                        $result = $ItemRegister->swapFileShowOrder($item_attr[$idx][$attridx-1], $item_attr[$idx][$attridx],
                                                        RepositoryConst::DBTABLE_REPOSITORY_FILE, 
                                                        $error);
                        if($result===false){
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
                    } else if($item_attr_type[$idx]['input_type'] == 'thumbnail'){
                        $result = $ItemRegister->swapFileShowOrder($item_attr[$idx][$attridx-1], $item_attr[$idx][$attridx],
                                                        RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL, 
                                                        $error);
                        if($result===false){
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
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
                    // Add registered info save action 2009/02/05 Y.Nakao --start--
                    $tmpShowOrder = $item_attr[$idx][$attridx]['show_order'];
                    $item_attr[$idx][$attridx]['show_order'] = $item_attr[$idx][$attridx+1]['show_order'];
                    $item_attr[$idx][$attridx+1]['show_order'] = $tmpShowOrder;
                    // Add registered info save action 2009/02/05 Y.Nakao --end--
                    // Modify registered underrow null 2013/04/17 K,Matsuo --start--
                    // sort file no
                    if($item_attr_type[$idx]['input_type'] == 'file' || $item_attr_type[$idx]['input_type'] == 'file_price'){
                        $result = $ItemRegister->swapFileShowOrder($item_attr[$idx][$attridx+1], $item_attr[$idx][$attridx],
                                                        RepositoryConst::DBTABLE_REPOSITORY_FILE, 
                                                        $error);
                        if($result===false){
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
                    } else if($item_attr_type[$idx]['input_type'] == 'thumbnail'){
                        $result = $ItemRegister->swapFileShowOrder($item_attr[$idx][$attridx+1], $item_attr[$idx][$attridx],
                                                        RepositoryConst::DBTABLE_REPOSITORY_THUMBNAIL, 
                                                        $error);
                        if($result===false){
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
                    }
                    // 配列の入替
                    $bufarray = $item_attr[$idx][$attridx];
                    $item_attr[$idx][$attridx] = $item_attr[$idx][$attridx+1];
                    $item_attr[$idx][$attridx+1] = $bufarray;
                    // Modify registered underrow null 2013/04/17 K,Matsuo --end--
                    break;
                case "delete_file":
                    $blankCheckFlag = false;
                    $this->save_mode = "stay";
                    // 20136/12 A.Jin $delete_file_listに削除対象のファイルを設定し、実ファイルを削除している。
                    // 登録済みファイルを削除 
                    $attridx = (int)($this->attridx);
                    // Add registered info save action 2009/02/05 Y.Nakao --start--
                    $item_attr[$idx][$attridx]['input_type'] = $item_attr_type[$idx]['input_type'];
                    array_push($delete_file_list, $item_attr[$idx][$attridx]);
                    $this->Session->setParameter("delete_file_list", $delete_file_list);
                    // Add registered info save action 2009/02/05 Y.Nakao --end--
                    $item_num_attr[$idx] = $item_num_attr[$idx] - 1;
                    if($item_num_attr[$idx] == 0){
                        $file_no = 1;
                        if($item_attr_type[$idx]['input_type']=='thumbnail'){
                            $file_no = $this->calcMaxThumbnailNo( $item_attr[$idx][$attridx]['item_id'], $item_attr[$idx][$attridx]['item_no']
                                                                 , $item_attr[$idx][$attridx]['attribute_id'], $error)+1;
                            if($file_no===false){
                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                $exception = new AppException($error);
                                $exception->addError($error);
                                throw $exception;
                            }
                        } else {
                            $file_no = $this->getFileNo( $item_attr[$idx][$attridx]['item_id'], $item_attr[$idx][$attridx]['item_no']
                                                                 , $item_attr[$idx][$attridx]['attribute_id'], $error);
                            if($file_no===false){
                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                $exception = new AppException($error);
                                $exception->addError($error);
                                throw $exception;
                            }
                        }
                        $item_num_attr[$idx] = 1;
                        $item_attr[$idx][$attridx] = array( 'item_id' => $item_attr[$idx][$attridx]['item_id'],
                                                            'item_no' => $item_attr[$idx][$attridx]['item_no'],
                                                            'attribute_id' => $item_attr[$idx][$attridx]['attribute_id'],
                                                            'file_no' => $file_no,
                                                            'show_order' => $file_no);
                    } else {
                        array_splice($item_attr[$idx], $attridx, 1);
                    }
                    for($jj=0; $jj<count($item_attr[$idx]); $jj++){
                        if(isset($item_attr[$idx][$jj]['new_file_no']) && 
                            $item_attr[$idx][$jj]['new_file_no'] != ''){
                            if($item_attr[$idx][$jj]['new_file_no'] > $attridx){
                                $item_attr[$idx][$jj]['new_file_no'] -= 1;
                            }
                        } else {
                            if($item_attr[$idx][$jj]['file_no'] > $attridx){
                                $item_attr[$idx][$jj]['new_file_no'] = $item_attr[$idx][$jj]['file_no']-1;
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        
        if($blankCheckFlag){
            // ------------------------------------------------------------
            // 次の画面に行く前に、未入力欄を削除して整理
            // ------------------------------------------------------------ 
            for($ii=0; $ii<count($item_attr_type); $ii++) {
                // ファイル／サムネイル以外はスルー
                if( $item_attr_type[$ii]['input_type']=='file' ||
                    $item_attr_type[$ii]['input_type']=='file_price' || // Add file price Y.Nakao 2008/08/28
                    $item_attr_type[$ii]['input_type']=='thumbnail')
                { 
                    $array_cleaned = array();
                    // 未入力欄を除外
                    for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                        if( $item_attr[$ii][$jj] != null &&
                            array_key_exists('upload', $item_attr[$ii][$jj]) == true &&
                            $item_attr[$ii][$jj]['upload'] != null)
                        {
                            array_push($array_cleaned, $item_attr[$ii][$jj]);
                        }
                    }
                    $item_num_attr[$ii] = count($array_cleaned);
                    // 0になってしまった場合は戻ったときのことを考えて救済措置
                    if($item_num_attr[$ii]<=0) {
                        $item_id = $this->Session->getParameter("edit_item_id");
                        $item_no = $this->Session->getParameter("edit_item_no");
                        $file_no = 1;
                        if($item_id != null && $item_no != null){
                            if($item_attr_type[$ii]['input_type']=='thumbnail'){
                                $file_no = $this->calcMaxThumbnailNo($item_id, $item_no, $ii + 1, $error)+1;
                                if($file_no===false){
                                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                    $exception = new AppException($error);
                                    $exception->addError($error);
                                    throw $exception;
                                }
                            } else {
                                $file_no = $this->getFileNo($item_id, $item_no, $ii + 1, $error);
                                if($file_no===false){
                                    $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                    $exception = new AppException($error);
                                    $exception->addError($error);
                                    throw $exception;
                                }
                            }
                        }
                        $item_num_attr[$ii] = 1;
                        array_push($array_cleaned, array('file_no' => $file_no, 'show_order' => $file_no));
                    }
                    // クリンナップした配列を保存
                    $item_attr[$ii] = $array_cleaned;
                }
            }
            $delete_file_list = array();
            $this->Session->setParameter("delete_file_list", $delete_file_list);
        }
        $this->Session->setParameter("item_num_attr", $item_num_attr);  // 属性の数を更新
        $this->Session->setParameter("item_attr", $item_attr);          // 属性を更新
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "files",
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
}
?>
