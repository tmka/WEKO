<?php
// --------------------------------------------------------------------
//
// $Id: Edittexts.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Edittexts extends RepositoryAction
{   
    // リクエストパラメタ
    // ファイル以外のパラメタは本コールバック関数で処理する。
    var $licence = null;                // ファイルのライセンス配列, アップロード済みアイテムの数に対応
    var $freeword = null;               // ファイルのライセンス自由記述欄配列, " "の場合、未入力
    var $embargo_year = null;           // エンバーゴ年
    var $embargo_month = null;          // エンバーゴ月
    var $embargo_day = null;            // エンバーゴ日
    var $embargo_flag = null;           // エンバーゴフラグ(0:公開日をアイテム公開日に合わせる, 1:公開日を独自に設定する)
    
    // Add file price Y.Nakao 2008/08/28 --start--
    var $room_ids = null;               // 選択されたグループのID配列
    var $price_value = null;            // 設定された価格配列
    var $save_mode = null;              // 保存か次へか
    // Add file price Y.Nakao 2008/08/28 --end--
    
    // Extend file type A.Suzuki 2009/12/15 --start--
    var $display_type = null;
    var $display_name = null;
    var $flash_embargo_year = null;         // フラッシュファイルエンバーゴ年
    var $flash_embargo_month = null;        // フラッシュファイルエンバーゴ月
    var $flash_embargo_day = null;          // フラッシュファイルエンバーゴ日
    var $flash_embargo_flag = null;         // フラッシュファイルエンバーゴフラグ(0:公開日をアイテム公開日に合わせる, 1:公開日を独自に設定する)
    // Extend file type A.Suzuki 2009/12/15 --end--

    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {   
        $istest = true;             // テスト用フラグ
        // Add registered info save action 2009/02/03 Y.Nakao --start--
        $smarty_assign = $this->Session->getParameter("smartyAssign");
        $err_msg = array();
        $ItemRegister = new ItemRegister($this->Session, $this->Db);
        $license_master = $this->Session->getParameter("license_master");
        // Add registered info save action 2009/02/03 Y.Nakao --end--
        // Add PDF flash 2010/02/04 A.Suzuki --start--
        $IDServer = new IDServer($this->Session, $this->Db);
        // Add PDF flash 2010/02/04 A.Suzuki --end--
        
        try {           
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // セッション情報取得
            $item_attr_type = $this->Session->getParameter("item_attr_type");       // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
            // ユーザ入力値＆変数
            $item_num_attr = $this->Session->getParameter("item_num_attr");         // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
            $item_attr = $this->Session->getParameter("item_attr");                 // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
            $license_master = $this->Session->getParameter("license_master");       // ライセンスマスタ
            $item_pub_date = $this->Session->getParameter("item_pub_date");
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            
            // アップロード済みファイルのライセンスを取得
            $cntUpd = 0;            // アップロード済みアイテムカウンタ
            // 価格用リクエストパラメタのカウンタ
            $price_Cnt = 0;     // Add file price Y.Nakao 2008/08/29
            // ii-thメタデータのリクエストを保存
            for($ii=0; $ii<count($item_attr_type); $ii++) {
                // 1ファイルに付くルームIDごとの価格
                $price = array();   // Add file price Y.Nakao 2008/08/29
                // ii-thメタデータのjj-th属性値のリクエストを保存
                for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                    // アップロード済みファイルのライセンス設定
                    if( ($item_attr_type[$ii]['input_type']=='file' || 
                        $item_attr_type[$ii]['input_type']=='file_price') // Add file price Y.Nakao 2008/08/28
                         && array_key_exists("upload", $item_attr[$ii][$jj]) == true
                         && $item_attr[$ii][$jj]["upload"] != null){
                         $item_attr[$ii][$jj]["item_id"] = $this->Session->getParameter("edit_item_id");
                         $item_attr[$ii][$jj]["item_no"] = $this->Session->getParameter("edit_item_no");
                         $item_attr[$ii][$jj]["attribute_id"] = $item_attr_type[$ii]['attribute_id']; 
                        // ライセンス保存 : CreativeCommonsの場合はライセンスマスタ配列のインデックス
                        // 自由記述は"licence_free"
                        $item_attr[$ii][$jj]['embargo_flag'] = $this->embargo_flag[$cntUpd];
                        
                        // Fix check invalid date 2011/06/17 A.Suzuki --start--
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
                                array_push($err_msg, $smartyAssign->getLang("repository_item_file_pub_date_illegal"));
                            }
                        }
                        // Fix check invalid date 2011/06/17 A.Suzuki --end--
                        
//                      echo 'embargo : ' .$item_attr[$ii][$jj]['embargo_flag'].'<br>';
                        // 月が未定義の場合、日も未定義にする ('00' = 未定義)
                        if($item_attr[$ii][$jj]['embargo_month'] == '00') {     
                            $item_attr[$ii][$jj]['embargo_day'] == '00';        
                        }
                        // Add flash file embargo 2010/02/08 A.Suzuki --start--
                        $item_attr[$ii][$jj]['flash_embargo_flag'] = $this->flash_embargo_flag[$cntUpd];
                        
                        // Fix check invalid date 2011/06/17 A.Suzuki --start--
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
                                array_push($err_msg, $smartyAssign->getLang("repository_item_flash_pub_date_illegal"));
                            }
                        }
                        // Fix check invalid date 2011/06/17 A.Suzuki --end--
                        
                        // 月が未定義の場合、日も未定義にする ('00' = 未定義)
                        if($item_attr[$ii][$jj]['flash_embargo_month'] == '00') {       
                            $item_attr[$ii][$jj]['flash_embargo_day'] == '00';      
                        }
                        // Add flash file embargo 2010/02/08 A.Suzuki --start--
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
                        // Extend file type A.Suzuki 2009/12/15 --start--
                        $item_attr[$ii][$jj]['display_name'] = ($this->display_name[$cntUpd]==' ')?'':$this->display_name[$cntUpd];
                        if($item_attr[$ii][$jj]['display_name'] == ""){
                            // if display_name is null, put in filename without extension
                            $item_attr[$ii][$jj]['display_name'] = str_ireplace(".".$item_attr[$ii][$jj]['upload']['extension'], "", $item_attr[$ii][$jj]['upload']['file_name']);
                        }
                        if($this->display_type[$cntUpd] == 'simple'){
                            $item_attr[$ii][$jj]['display_type'] = 1;
                        } else if($this->display_type[$cntUpd] == 'flash'){
                            // Add PDF flash 2010/02/04 A.Suzuki --start--
                            $item_attr[$ii][$jj]['display_type'] = 2;
                        } else {
                            $item_attr[$ii][$jj]['display_type'] = 0;
                        }
                        // Extend file type A.Suzuki 2009/12/15 --end--
                        // Add registered info save action 2009/02/03 Y.Nakao --start--
                        $result = $ItemRegister->updateFileLicense($item_attr[$ii][$jj], $error);
                        if($result === false){
                            array_push($err_msg, $error);
                            $this->Session->setParameter("error_msg", $err_msg);
                            return 'error';
                        }
                        // Add registered info save action 2009/02/03 Y.Nakao --end--
                        // Add file price Y.Nakao 2008/08/29 --start--
                        if($item_attr_type[$ii]['input_type']=='file_price'){
                            // 初期化
                            $price_array = array();
                            $room_id_array = array();
                            // 個数保持
                            $loop_num = $item_attr[$ii][$jj]['price_num'];
                            // 設定されている価格情報を格納する
                            for($price_num=0;$price_num<$loop_num;$price_num++){
                                // ルームIDと価格を保持する
                                // Bugfix price input scrutiny 2011/06/27 Y.Nakao --start--
                                if(!is_numeric($this->price_value[$price_Cnt])){
                                    array_push($room_id_array, $this->room_ids[$price_Cnt]);
                                    array_push($price_array, "0");
                                    array_push($err_msg, $smarty_assign->getLang("repository_item_error_price"));
                                } else {
                                    array_push($room_id_array, $this->room_ids[$price_Cnt]);
                                    array_push($price_array, $this->price_value[$price_Cnt]);
                                }
                                // Bugfix price input scrutiny 2011/06/27 Y.Nakao --end--
                                // カウンタを次へ
                                $price_Cnt++;
                            }
                            $item_attr[$ii][$jj]['price_value'] = $price_array;
                            $item_attr[$ii][$jj]['room_id'] = $room_id_array;
                            // Add registered info save action 2009/02/04 Y.Nakao --start--
                            $result = $ItemRegister->updatePrice($item_attr[$ii][$jj], $error);
                            if($result === false){
                                array_push($err_msg, $error);
                                $this->Session->setParameter("error_msg", $err_msg);
                                return 'error';
                            }
                            // Add registered info save action 2009/02/04 Y.Nakao --end--
                        }
                        // Add file price Y.Nakao 2008/08/29 --end--
                        $cntUpd++;
                    }
                }
            }
            
            $ItemRegister->checkEntryInfo($item_attr_type, $item_num_attr, $item_attr, 'license', $err_msg, $warning);
            
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
            $ItemRegister->updateInsertUserIdForContributor(
                    intval($this->Session->getParameter("edit_item_id")),
                    $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
            
            // 次の画面へ
            $this->Session->setParameter("item_attr", $item_attr);  // 属性を更新
            
            $this->Session->removeParameter("error_msg");
            $this->Session->setParameter("error_msg", $err_msg);
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // Add error input Screen changes  K.Matsuo 2013/5/30 --start--
            if(count($err_msg)>0){
                // 入力にエラーがあるときは画面遷移しない
                $this->save_mode = "stay";
            }
            // Add error input Screen changes  K.Matsuo 2013/5/30 --end--
            // Add registered info save action 2009/02/02 Y.Nakao --start--
            if($this->save_mode == 'stay'){
                return 'stay';
            }
            // Add registered info save action 2009/02/02 Y.Nakao --end--
            
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
            $this->Session->setParameter("error_msg", $user_error_msg);
            return "error";
        }
    }
}
?>
