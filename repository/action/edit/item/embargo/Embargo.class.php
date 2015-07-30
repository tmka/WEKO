<?php
// --------------------------------------------------------------------
//
// $Id: Embargo.class.php 40574 2014-08-28 00:24:04Z tatsuya_koyasu $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';

/**
 * [[アイテム管理actionアクション]]
 * 指定されたエンバーゴ設定
 * 
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_Action_Edit_Item_Embargo extends RepositoryAction
{
    // コンポーネント受け取り
    var $Session = null;
    var $Db = null;
    
    // 選択インデックスID
    var $selindex_id = null;
    
    // 選択されたエンバーゴ設定
    // 1:オープンアクセス
    // 2:オープンアクセス日指定
    // 3:会員のみ
    var $embargo_flag = null;
    
    // オープンアクセス日    
    var $embargo_year = null;
    var $embargo_month = null;
    var $embargo_day = null;
    
    // 選択ライセンスID
    // 0:は自由記述
    var $license_id = null;
    
    // 自由記述の場合のライセンス用文字列
    var $licence_free_text = null;
    
    // 配下のフォルダへ再帰的に設定を行うかどうかのフラグ
    // true:再帰的に設定する
    // false:再帰的に設定しない
    var $embargo_recursion = null;
    
    /**
     * [[公開・エンバーゴ]]
     *
     * @access  public
     */
    function execute()
    {
        try {
            //アクション初期化処理
            $result = $this->initAction();
            
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                                  //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                   //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // 選択タブの保存
            $this->Session->setParameter("item_setting_active_tab", 1);

            ///// 引数チェック /////
            if( $this->selindex_id == null || $this->selindex_id == "" ){
                $this->Session->setParameter("error_msg", "Select Index Error.");
                return 'error';
            }
            
            //// Add インデックスの保存  2013/01/22 A.Jin --start--
            // セッション: targetIndexIdにメンバ変数: selindex_idを格納
            $this->Session->removeParameter("targetIndexId");
            $this->Session->removeParameter("searchIndexId");
            $this->Session->setParameter("targetIndexId",$this->selindex_id);
            $this->Session->setParameter("searchIndexId", $this->selindex_id);
            
            $this->Session->setParameter("embargo_flag", $this->embargo_flag);
            $this->Session->setParameter("embargo_year", $this->embargo_year);
            $this->Session->setParameter("embargo_month", $this->embargo_month);
            $this->Session->setParameter("embargo_day", $this->embargo_day);
            $this->Session->setParameter("license_id", $this->license_id);
            $this->Session->setParameter("licence_free_text", $this->licence_free_text);
            $this->Session->setParameter("embargo_recursion", $this->embargo_recursion);
            //// Add インデックスの保存  2013/01/22 A.Jin --end--
            
            ///// エンバーゴが妥当な入力か検査 /////
            if($this->embargo_flag == 2) {
                $chk_year = intval($this->embargo_year);
                $chk_month = intval($this->embargo_month);
                $chk_day = intval($this->embargo_day);
                // 月または日が未定義 => 1月1日を検査
                if($chk_month == 0 || $chk_day == 0) {
                    $chk_month = 1;
                    $chk_day = 1;
                }
                // 年月日を検査
                if( checkdate($chk_month, $chk_day, $chk_year) == false ) {
                    $this->Session->setParameter("error_msg", "invalid date input. error!");
                    return "error";
                }
            } else if($this->embargo_flag == 1){ // オープンアクセスの時
                // 現在に日時をエンバーゴ日時に設定する。
                $TransStartDateArray = explode(" ", $this->TransStartDate, 2);
                $date = RepositoryOutputFilter::date($TransStartDateArray[0]);
                $exDate = explode("-", $date);
                $this->embargo_year = $exDate[0];
                $this->embargo_month = $exDate[1];
                $this->embargo_day = $exDate[2];
                // 次回表示用のセッションも更新する
                $this->Session->setParameter("embargo_year", $this->embargo_year);
                $this->Session->setParameter("embargo_month", $this->embargo_month);
                $this->Session->setParameter("embargo_day", $this->embargo_day);
            }
            
            // エンバーゴ対象アイテムのアイテムIDとアイテム通番の配列
            $embargo_item_info = array();
            
            //　再帰的に設定を行うのかどうか
            if($this->embargo_recursion == "true"){
                // 再帰的に設定する場合、選択されたインデックス直下のアイテムと選択されたインデックス配下のアイテムがエンバーゴ対象
                // 初期化
                $embargo_index_id = array();
                // 選択されたインデックスidを格納
                array_push($embargo_index_id, array("index_id" => $this->selindex_id));
                // 選択されたインデックス配下のインデックスをすべて取得
                $result = $this->getSubIndexId($this->selindex_id, $embargo_index_id);
                if ($result === false) {
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
                // 対象インデックスに紐づくアイテムのIDと通番を取得
                for($ii=0;$ii<count($embargo_index_id);$ii++){
                    // 指定インデックス直下のアイテムのIDと通番、更新日時を取得。情報は$embargo_item_infoに追加される。
                    $result = $this->getItemInfo($embargo_index_id[$ii]["index_id"], $embargo_item_info);
                    if ($result === false) {
                        //エラー処理を行う
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                        $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                        throw $exception;
                    }
                }
            } else {
                // 再帰的に設定しない場合、選択されたインデックス直下のアイテムのみが対象
                // 選択インデックス直下のアイテムのIDと通番、更新日時を取得。情報は$embargo_item_infoに格納される。
                $this->getItemInfo($this->selindex_id, $embargo_item_info);
            }
            
            // 対象アイテムにファイルがある場合、そのファイルのエンバーゴを設定する。
            for($ii=0;$ii<count($embargo_item_info);$ii++){
                $result = $this->setFileEmbargo($embargo_item_info[$ii]["item_id"], $embargo_item_info[$ii]["item_no"], $embargo_item_info[$ii]["mod_date"]);
                if($result === false){
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    $this->failTrans();        //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
            }
            
            // アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // エラーメッセージ開放
            $this->Session->removeParameter("error_msg");
            
            return 'success';
        }
        catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                    //クラス名
                "execute",                        //メソッド名
                $Exception->getCode(),            //ログID
                $Exception->getMessage(),        //主メッセージ
                $Exception->getDetailMsg() );    //詳細メッセージ
            //アクション終了処理
              $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
        
            //異常終了
            return "error";
        }
    }
    
    /**
     * 引数で指定されるインデックス配下のインデックスIDをすべて取得する
     *
     * @param $index_id インデックスID
     * @param $embargo_index_id 結果を格納
     */
    function getSubIndexId($index_id, &$embargo_index_id){
        // 指定されたインデックスIDの直下にあるインデックスIDを取得
        $query = "SELECT index_id ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE parent_index_id = ? AND ".
                 "is_delete = ?; ";
        $params = array();
        $params[] = $index_id;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_msg", $errMsg);
            return false;
        }
        // 取得したインデックスの配下のインデックスIDを取得する
        for($ii=0;$ii<count($result);$ii++){
            $sub_result = $this->getSubIndexId($result[$ii]["index_id"], $embargo_index_id);
            if($sub_result === false){
                return false;
            }
            array_push($embargo_index_id, array("index_id" => $result[$ii]["index_id"]));
        }
        return true;
    }
    
    /**
     * 引数で指定されるインデックス直下のアイテムのIDと通番を取得
     *
     * @param $index_id インデックスID
     * @param $item_info 結果を格納
     */
    function getItemInfo($index_id, &$item_info){
        $query = "SELECT item_id, item_no ".
                 "FROM ". DATABASE_PREFIX ."repository_position_index ".
                 "WHERE index_id = ? AND ".
                 "is_delete = ?; ";
        $params = null;
        $params[] = $index_id;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_msg", $errMsg);
            return false;
        }
        // 格納
        for($ii=0;$ii<count($result);$ii++){
            // 更新ロック用のため、itemテーブルから更新日時を取得
            $query = "SELECT mod_date ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "is_delete = ?; ";
            $params = null;
            $params[] = $result[$ii]["item_id"];
            $params[] = $result[$ii]["item_no"];
            $params[] = 0;
            $ret = $this->Db->execute($query, $params);
            if($ret === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return false;
            }            
            array_push($item_info, array("item_id" => $result[$ii]["item_id"], 
                                         "item_no" => $result[$ii]["item_no"],
                                         "mod_date" => $ret[0]["mod_date"])
                        );
        }
        return true;
    }
    
    /**
     * アイテムIDと通番から、そのアイテムがファイルを持っているかチェックする。
     * ファイルを持っていた場合、エンバーゴを設定する。
     *
     * @param $item_id 対象アイテムID
     * @param $item_no 対象アイテム通番
     * @param $mod_date 対象アイテム更新日時
     */
    function setFileEmbargo($item_id, $item_no, $mod_date){
        ///// ファイルの存在チェック //////
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_file ".
                 "WHERE item_id = ? AND ".
                 "item_no = ? AND ".
                 "is_delete = ?; ";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_msg", $errMsg);
            return false;
        }
        ///// ファイルの存在チェック /////
        if(count($result) > 0){
            ///// ファイルが存在する場合、エンバーゴを設定する /////
            // データ更新のため、ロック
            $query = "SELECT mod_date, shown_date ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "mod_date = ? AND ".
                     "is_delete = ? ".
                     "FOR UPDATE; ";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = $mod_date;
            $params[] = 0;
            $ret = $this->Db->execute($query, $params);
            if($ret === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return false;
            }
            // 自由記述以外の場合、ライセンスIDからライセンス表記を取得
            if($this->license_id != 0){
                $query = "SELECT license_notation ".
                         "FROM ". DATABASE_PREFIX ."repository_license_master ".
                         "WHERE license_id = ? AND ".
                         "is_delete = ?; ";
                $params = array();
                $params[] = $this->license_id;
                $params[] = 0;
                $license_notation = $this->Db->Execute($query, $params);            
                if($license_notation === false){
                    //必要であればSQLエラー番号・メッセージ取得
                    $errMsg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_msg", $errMsg);
                    return false;
                }
            }
            // エンバーゴ設定(ファイルテーブル更新)
            $query = "UPDATE ". DATABASE_PREFIX ."repository_file ".
                     "SET license_id = ?, ".
                     "license_notation = ?, ".
                     "pub_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "is_delete = ?; ";
            $params = array();
            $params[] = $this->license_id;    // license_id
            if($this->license_id == 0){
                // 自由記述の場合
                $params[] = $this->licence_free_text;    // license_notation
            } else {
                // 自由記述以外
                $params[] = $license_notation[0]["license_notation"];    // license_notation
            }
            // pub_date 選択されたものに従う
            if($this->embargo_flag == 1) {
                // オープンアクセス(親アイテムの公開日に従う)
                // Add エンバーゴファイル公開日判定  2013/01/23 A.Jin -- start --
                // 数値のみを抜き出す
                $shown_date = preg_replace("/[^0-9]+/","",$ret[0]["shown_date"]);
                if(mb_strlen($this->embargo_month) == 1){
                    $this->embargo_month = '0'.$this->embargo_month;
                }
                if(mb_strlen($this->embargo_day) == 1){
                    $this->embargo_day = '0'.$this->embargo_day;
                }
                $request_date = $this->embargo_year.$this->embargo_month.$this->embargo_day.'000000000';
                
                // リクエストパラメータより、過去日の場合、過去日時を設定する。
                if($shown_date < $request_date)
                {
                    $params[] = $ret[0]["shown_date"];
                }
                // リクエストパラメータより、未来日の場合、リクエストパラメータ日時を設定する。
                else{
                    $params[] = $this->generateDateStr($this->embargo_year, $this->embargo_month, $this->embargo_day);
                }
                
                // Add エンバーゴファイル公開日判定 A.Jin 2013/01/23 -- end --
            } elseif($this->embargo_flag == 2) {
                // オープンアクセス日を指定
                $params[] = $this->generateDateStr($this->embargo_year, $this->embargo_month, $this->embargo_day);    
            } else {
                // 会員のみ => 内部的には9999年1月1日として扱う
                $params[] = $this->generateDateStr(9999, 1, 1);
            }
            $params[] = $this->Session->getParameter("_user_id");    // mod_user_id
            $params[] = $this->TransStartDate;    // mod_date
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = 0;
            $ret = $this->Db->execute($query, $params);
            if($ret === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 年月日のデータ(int)を日付文字列にして返す
     * action/main/item/adddbから移植
     */
    function generateDateStr($year, $month, $day){
        $str_year = strval($year);
        $str_month = strval($month);
        $str_day = strval($day);
        // 成形・結合
        return sprintf("%04d-%02d-%02d 00:00:00.000", $str_year, $str_month, $str_day);
    }
}
?>
