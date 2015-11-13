<?php
// --------------------------------------------------------------------
//
// $Id: Editfileslicense.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';

/**
 * アイテム登録：ファイルライセンス設定画面からの入力処理アクション
 *
 * @access      public
 */
class Repository_Action_Main_Item_Editfileslicense extends RepositoryAction
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
     * @var string
     */
    public $save_mode = null;
    
    /**
     * ファイルのライセンス配列
     *   アップロード済みアイテムの数に対応
     * @var array
     */
    public $licence = null;
    
    /**
     * ファイルのライセンス自由記述欄配列
     *   " "の場合、未入力
     * @var array
     */
    public $freeword = null;
    
    /**
     * エンバーゴ年配列
     * @var array
     */
    public $embargo_year = null;
    
    /**
     * エンバーゴ月配列
     * @var array
     */
    public $embargo_month = null;
    
    /**
     * エンバーゴ日配列
     * @var array
     */
    public $embargo_day = null;
    
    /**
     * エンバーゴフラグ配列
     *   0:公開日をアイテム公開日に合わせる
     *   1:公開日を独自に設定する
     * @var array
     */
    public $embargo_flag = null;
    
    /**
     * 選択されたグループのID配列
     * @var array
     */
    public $room_ids = null;
    
    /**
     * 設定された価格配列
     * @var array
     */
    public $price_value = null;
    
    /**
     * 表示形式配列
     * @var array
     */
    public $display_type = null;
    
    /**
     * 表示名配列
     * @var array
     */
    public $display_name = null;
    
    /**
     * ダウンロード許可グループID配列
     * @var array
     */
    public $auth_room_ids = null;
    
    /**
     * フラッシュファイルエンバーゴ年配列
     * @var array
     */
    public $flash_embargo_year = null;
    
    /**
     * フラッシュファイルエンバーゴ月配列
     * @var array
     */
    public $flash_embargo_month = null;
    
    /**
     * フラッシュファイルエンバーゴ日配列
     * @var array
     */
    public $flash_embargo_day = null;
    
    /**
     * フラッシュファイルエンバーゴフラ配列
     *   0:公開日をアイテム公開日に合わせる
     *   1:公開日を独自に設定する
     * @var array
     */
    public $flash_embargo_flag = null;
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ

    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        // インスタンス作成
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        $IDServer = new IDServer($this->Session, $this->Db);
        
        // セッション情報取得
        $item_attr_type = $this->Session->getParameter("item_attr_type");       // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_attr = $this->Session->getParameter("item_num_attr");         // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr = $this->Session->getParameter("item_attr");                 // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $license_master = $this->Session->getParameter("license_master");       // ライセンスマスタ
        $item_pub_date = $this->Session->getParameter("item_pub_date");
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        
        // アップロード済みファイルのライセンスを取得
        $cntUpd = 0;    // アップロード済みアイテムカウンタ
        $price_Cnt = 0; // 価格用リクエストパラメタのカウンタ
        $auth_Cnt = 0;  // ファイル権限リクエストパラメータ用のカウンタ Add file authority T.Ichikawa 2015/03/20
        // ii-thメタデータのリクエストを保存
        for($ii=0; $ii<count($item_attr_type); $ii++) {
            // 1ファイルに付くルームIDごとの価格
            $price = array();
            // ii-thメタデータのjj-th属性値のリクエストを保存
            for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                // アップロード済みファイルのライセンス設定
                if( ($item_attr_type[$ii]['input_type']=='file' || $item_attr_type[$ii]['input_type']=='file_price') &&
                    array_key_exists("upload", $item_attr[$ii][$jj]) == true &&
                    $item_attr[$ii][$jj]["upload"] != null)
                {
                    $item_attr[$ii][$jj]["item_id"] = $this->Session->getParameter("edit_item_id");
                    $item_attr[$ii][$jj]["item_no"] = $this->Session->getParameter("edit_item_no");
                    $item_attr[$ii][$jj]["attribute_id"] = $item_attr_type[$ii]['attribute_id']; 
                    // ライセンス保存 : CreativeCommonsの場合はライセンスマスタ配列のインデックス
                    // 自由記述は"licence_free"
                    $item_attr[$ii][$jj]['embargo_flag'] = $this->embargo_flag[$cntUpd];
                    
                    // ファイル公開日
                    $this->embargo_year[$cntUpd] = trim($this->embargo_year[$cntUpd]);
                    if($this->checkDate($this->embargo_year[$cntUpd], $this->embargo_month[$cntUpd], $this->embargo_day[$cntUpd])){
                        $item_attr[$ii][$jj]['embargo_year'] = $this->embargo_year[$cntUpd];
                        $item_attr[$ii][$jj]['embargo_month'] = $this->embargo_month[$cntUpd];
                        $item_attr[$ii][$jj]['embargo_day'] = $this->embargo_day[$cntUpd];
                    } else {
                        // invalid date
                        // Set item_pub_date
                        $item_attr[$ii][$jj]['embargo_year'] = $item_pub_date["year"];
                        $item_attr[$ii][$jj]['embargo_month'] = $item_pub_date["month"];
                        $item_attr[$ii][$jj]['embargo_day'] = $item_pub_date["day"];
                        if($this->embargo_flag[$cntUpd] == "2"){
                            $this->addErrMsg("repository_item_file_pub_date_illegal");
                        }
                    }
                    // 月が未定義の場合、日も未定義にする ('00' = 未定義)
                    if($item_attr[$ii][$jj]['embargo_month'] == '00') {
                        $item_attr[$ii][$jj]['embargo_day'] == '00';
                    }
                    
                    // フラッシュ公開日
                    $item_attr[$ii][$jj]['flash_embargo_flag'] = $this->flash_embargo_flag[$cntUpd];
                    $this->flash_embargo_year[$cntUpd] = trim($this->flash_embargo_year[$cntUpd]);
                    if($this->checkDate($this->flash_embargo_year[$cntUpd], $this->flash_embargo_month[$cntUpd], $this->flash_embargo_day[$cntUpd])){
                        $item_attr[$ii][$jj]['flash_embargo_year'] = $this->flash_embargo_year[$cntUpd];
                        $item_attr[$ii][$jj]['flash_embargo_month'] = $this->flash_embargo_month[$cntUpd];
                        $item_attr[$ii][$jj]['flash_embargo_day'] = $this->flash_embargo_day[$cntUpd];
                    } else {
                        // invalid date
                        // Set item_pub_date
                        $item_attr[$ii][$jj]['flash_embargo_year'] = $item_pub_date["year"];
                        $item_attr[$ii][$jj]['flash_embargo_month'] = $item_pub_date["month"];
                        $item_attr[$ii][$jj]['flash_embargo_day'] = $item_pub_date["day"];
                        if($this->flash_embargo_flag[$cntUpd] == "2"){
                            $this->addErrMsg("repository_item_flash_pub_date_illegal");
                        }
                    }
                    // 月が未定義の場合、日も未定義にする ('00' = 未定義)
                    if($item_attr[$ii][$jj]['flash_embargo_month'] == '00') {
                        $item_attr[$ii][$jj]['flash_embargo_day'] == '00';
                    }
                    
                    if($this->licence[$cntUpd] == 'licence_free') {
                        $item_attr[$ii][$jj]['licence'] = 'licence_free';
                        $item_attr[$ii][$jj]['freeword'] = ($this->freeword[$cntUpd]==' ')?'':$this->freeword[$cntUpd];
                        $item_attr[$ii][$jj]['license_id'] = '0';
                        $item_attr[$ii][$jj]['license_notation'] = $item_attr[$ii][$jj]['freeword'];
                    } else {
                        $item_attr[$ii][$jj]['licence'] = $this->licence[$cntUpd];
                        $item_attr[$ii][$jj]['license_id'] = $license_master[$this->licence[$cntUpd]]['license_id'];
                        $item_attr[$ii][$jj]['license_notation'] = $license_master[$this->licence[$cntUpd]]['license_notation'];
                    }
                    
                    $item_attr[$ii][$jj]['display_name'] = ($this->display_name[$cntUpd]==' ')?'':$this->display_name[$cntUpd];
                    if($item_attr[$ii][$jj]['display_name'] == ""){
                        // if display_name is null, put in filename without extension
                        $item_attr[$ii][$jj]['display_name'] = str_ireplace(".".$item_attr[$ii][$jj]['upload']['extension'], "", $item_attr[$ii][$jj]['upload']['file_name']);
                    }
                    if($this->display_type[$cntUpd] == 'simple'){
                        $item_attr[$ii][$jj]['display_type'] = 1;
                    } else if($this->display_type[$cntUpd] == 'flash'){
                        $item_attr[$ii][$jj]['display_type'] = 2;
                    } else {
                        $item_attr[$ii][$jj]['display_type'] = 0;
                    }
                    
                    // ライセンス情報をDBに保存
                    $result = $ItemRegister->updateFileLicense($item_attr[$ii][$jj], $error);
                    if($result === false){
                        // update faild
                        $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                        $exception = new AppException($error);
                        $exception->addError($error);
                        throw $exception;
                    }
                    
                    // Add file authority 2015/03/18 T.Ichikawa --start--
                    if($item_attr_type[$ii]['input_type']=='file'){
                        // 一度権限情報を削除する
                        $params = array();
                        $params[] = $item_attr[$ii][$jj]["file_no"];          // ファイルNo
                        $params[] = "0,0";                                    // 価格
                        $params[] = $this->Session->getParameter("_user_id"); // 更新者
                        $params[] = $this->TransStartDate;                    // 更新日
                        $params[] = 1;                                        // 削除フラグ
                        $params[] = $item_attr[$ii][$jj]["item_id"];          // アイテムID
                        $params[] = $item_attr[$ii][$jj]["item_no"];          // アイテムNo
                        $params[] = $item_attr[$ii][$jj]["attribute_id"];     // アイテム属性ID
                        $params[] = $item_attr[$ii][$jj]["file_no"];          // ファイルNo
                        $result = $this->updateFilePrice($params, $error);
                        if($result === false) {
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
                        
                        // 「オープンアクセス日を指定」か「ログインユーザーのみ」の時だけ権限情報を更新する
                        if($item_attr[$ii][$jj]['embargo_flag'] == 2 || $item_attr[$ii][$jj]['embargo_flag'] == 3) {
                            // 維持用の配列の更新
                            $auth_room_id_array = array();
                            
                            $loop_num = $item_attr[$ii][$jj]['auth_num'];
                            // 設定されている権限情報を格納する
                            for($auth_num = 0; $auth_num < $loop_num; $auth_num++){
                                // ルームIDを保持する
                                    array_push($auth_room_id_array, $this->auth_room_ids[$auth_Cnt]);
                                // カウンタを次へ
                                $auth_Cnt++;
                            }
                            $item_attr[$ii][$jj]['auth_room_id'] = $auth_room_id_array;
                            
                            // 権限文字列を作成する
                            // 権限が無い場合は空文字がテーブルに登録される
                            $auth_param = "";
                            if($loop_num > 0) {
                                for($loop_auth = 0; $loop_auth < $loop_num; $loop_auth++) {
                                    if($loop_auth > 0) {
                                        $auth_param .= "|";
                                    }
                                    $auth_param .= $item_attr[$ii][$jj]['auth_room_id'][$loop_auth]. ",0";
                                }
                            }
                            
                            // 権限(≒課金)情報をテーブルに登録・更新
                            $params = array();
                            $params[] = $item_attr[$ii][$jj]["item_id"];          // アイテムID
                            $params[] = $item_attr[$ii][$jj]["item_no"];          // アイテムNo
                            $params[] = $item_attr[$ii][$jj]["attribute_id"];     // アイテム属性ID
                            $params[] = $item_attr[$ii][$jj]["file_no"];          // ファイルNo
                            $params[] = $auth_param;                              // 価格
                            $params[] = $this->Session->getParameter("_user_id"); // 登録者
                            $params[] = $this->Session->getParameter("_user_id"); // 更新者
                            $params[] = "";                                       // 削除者
                            $params[] = $this->TransStartDate;                    // 登録日時
                            $params[] = $this->TransStartDate;                    // 更新日時
                            $params[] = "";                                       // 削除日時
                            $params[] = 0;                                        // 削除フラグ
                            $result = $this->insertOrUpdatePrice($params, $error);
                            if($result === false){
                                $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                                $exception = new AppException($error);
                                $exception->addError($error);
                                throw $exception;
                            }
                        }
                    }
                    // Add file authority 2015/03/18 T.Ichikawa --end--
                    
                    // 価格をDBに保存
                    if($item_attr_type[$ii]['input_type']=='file_price'){
                        // 初期化
                        $price_array = array();
                        $room_id_array = array();
                        // 個数保持
                        $loop_num = $item_attr[$ii][$jj]['price_num'];
                        // 設定されている価格情報を格納する
                        for($price_num=0;$price_num<$loop_num;$price_num++){
                            // ルームIDと価格を保持する
                            array_push($room_id_array, $this->room_ids[$price_Cnt]);
                            array_push($price_array, $this->price_value[$price_Cnt]);
                            // カウンタを次へ
                            $price_Cnt++;
                        }
                        $item_attr[$ii][$jj]['price_value'] = $price_array;
                        $item_attr[$ii][$jj]['room_id'] = $room_id_array;
                        // Add registered info save action 2009/02/04 Y.Nakao --start--
                        $result = $ItemRegister->updatePrice($item_attr[$ii][$jj], $error);
                        if($result === false){
                            // update faild
                            $this->errorLog($error, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($error);
                            $exception->addError($error);
                            throw $exception;
                        }
                        // Add registered info save action 2009/02/04 Y.Nakao --end--
                    }
                    $cntUpd++;
                }
            }
        }
        
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        $ItemRegister->updateInsertUserIdForContributor(
            intval($this->Session->getParameter("edit_item_id")),
            $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
        
        // セッションのアイテム情報を更新
        $this->Session->setParameter("item_attr", $item_attr);
        
        if(!is_null($this->errMsg) && count($this->errMsg)>0){
            // 入力にエラーがあるときは画面遷移しない
            $this->save_mode = "stay";
        }
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "license",
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
