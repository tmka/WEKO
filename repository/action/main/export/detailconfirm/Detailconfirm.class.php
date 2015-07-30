<?php
// --------------------------------------------------------------------
//
// $Id: Detailconfirm.class.php 30197 2013-12-19 09:55:45Z rei_matsuura $
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
class Repository_Action_Main_Export_DetailConfirm extends RepositoryAction
{
    var $Session = null;
    var $Db = null;
    
    var $item_id = null;
    var $item_no = null;
    
    // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
    const EXPORT_FILE_ON = 1;
    // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
    
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        try{
            // 初期処理
            $result = $this->initAction();
            if ( $result == false ){
                // 未実装
            }
            // リクエストパラメータ確認
            if($this->item_id==null || $this->item_no==null){
                // エラー
                print "アイテムの情報がありません";
                return 'error';
            }
            // 現在表示されている詳細情報のレコードの集合
            $result = $this->getItemData($this->item_id,$this->item_no,$item_infos,$Error_Msg);
            if($result == false){
                $this->Session->setParameter("error_msg",$Error_Msg);
                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                return 'error';
            }
            if(count($item_infos) == 0){
                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                $Error_Msg = "対称が存在しません";
                return 'error';
            }
            // 初期化&セッションに保存
            $this->Session->removeParameter("no_export");
            $this->Session->removeParameter("item_info");
            $this->Session->removeParameter("license_num");
            $this->Session->removeParameter("files");
            
            // Bugfix close item download 2011/06/09 --start--
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->Session->getParameter("_auth_id");
            $user_id = $this->Session->getParameter("_user_id");
            
            // check admin
            $adminUser = false;
            $insUser = false;
            // Add Advanced Search 2013/11/26 R.Matsuura --start--
            $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
            // Add Advanced Search 2013/11/26 R.Matsuura --end--
            
            if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room){
                // is admin
                $adminUser = true;
            }
            // check insert user
            else if($user_id != "0" && $item_infos['item'][0]['ins_user_id'] == $user_id){
                // is insert user
                $insUser = true;
            }
            // check item public
            else if(!$itemAuthorityManager->checkItemPublicFlg($this->item_id, $this->item_no, $this->repository_admin_base, $this->repository_admin_room)){
                // is close item
                $this->Session->setParameter("license_num", 0);
                $this->Session->setParameter("files", array());
                return 'success';
            } else {
                // is public item
            }
            // Bugfix close item download 2011/06/09 --start--
            $this->Session->setParameter("item_info", $item_infos);
            $this->Session->setParameter("item_id_for_export", $this->item_id);
            $this->Session->setParameter("item_no_for_export", $this->item_no);

            // ファイル名をユニークにするため日付（年月日時分秒）を取得する
            //$date = date("YmdHis");
            $query = "SELECT DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') AS now_date;";
            $result = $this->Db->execute($query);
            if($result === false || count($result) != 1){
                return false;
            }
            $date = $result[0]['now_date'];
            $filename="./export_" . $date . ".txt";

            // 確認画面にて表示するファイル情報配列初期化
            $file_infos = array();

            // 指定されているアイテムからライセンス情報を取得する（アイテム属性タイプ・ファイル情報が必要）　2008/03/18 修正
            
            // アイテム属性タイプ情報を抜き出す
            $item_attr_types = $item_infos['item_attr_type'];

            $cnt_file_num = 0;  // 表示するファイル総数
            // ファイルの総数をセッションに保存(初期化)
            $this->Session->removeParameter("license_num");
            
            // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
            // ファイルのエクスポート設定状態を取得
            $this->getAdminParam('export_is_include_files', $isExportFile, $errorMsg);
            // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
            
            // user authority
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->getRoomAuthorityID();

            // Modify Price method move validator K.Matsuo 2011/10/18 --start--
            require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
            $validator = new Repository_Validator_DownloadCheck();
            $initResult = $validator->setComponents($this->Session, $this->Db);
            if($initResult === 'error'){
                return 'error';
            }
            // アイテム属性タイプからファイルを取得する
            for ( $index = 0; $index < count($item_attr_types); $index++){
                if($item_attr_types[$index]["input_type"] == "file" || $item_attr_types[$index]["input_type"] == "file_price"){
                    // ファイルを取得する
                    $query = "SELECT * " .
                             "FROM ". DATABASE_PREFIX ."repository_file " .
                             "WHERE item_id = ? " .
                             "AND item_no = ? " .
                             "AND attribute_id = ? " .
                             "AND is_delete = 0  ".
                             "order by show_order; ";      // 表示順序でソート
                    
                    // バインド変数設定
                    $params_file = null;
                    $params_file[0] = intval($item_infos['item'][0]["item_id"]);                        // item_id
                    $params_file[1] = intval($item_infos['item'][0]["item_no"]);                        // item_no
                    $params_file[2] = intval($item_attr_types[$index]["attribute_id"]); // attribute_id
                    
                    // クエリ実行
                    $files = $this->Db->execute( $query, $params_file );
                    // 複数不可だった場合、2つ目以降のファイルを削除する T.Ichikawa 2013/9/17 --start--
                    if($item_attr_types[$index]["plural_enable"] != 1) {
                        array_splice($files, 1);
                    }
                    // 複数不可だった場合、2つ目以降のファイルを削除する T.Ichikawa 2013/9/17 --end--
                    for ( $cnt_file = 0; $cnt_file < count($files); $cnt_file++)
                    {
                        // Fix file download check Y.Nakao 2013/04/11 --start--
                        $file_flag = "close";
                        $group_price = array();
                        $status = $validator->checkFileAccessStatus($files[$cnt_file]);
                        if( $status == "free" || $status == "already" || $status == "admin" || $status == "license" )
                        {
                            // this file use can download
                            $file_flag = "free";
                        }
                        else if($status == "login")
                        {
                            $file_flag = "login";
                        }
                        else if(preg_match("/:[0-9]+$/", $status) == 1)
                        {
                            // file_price
                            $file_flag = "paid";
                            $group_price = $this->getGroupPrice($files[$cnt_file]);
                        }
                        else
                        {
                            $file_flag = "close";
                        }
                        
                        if($files[$cnt_file]['license_id'] != 0)
                        {
                            // ファイルに紐付く、ライセンスマスタを取得する
                            $query = "SELECT * " .
                                     "FROM ". DATABASE_PREFIX ."repository_license_master " .
                                     "WHERE license_id = ? " .
                                     "AND is_delete = 0 ;";
                            
                            // バインド変数設定
                            $params_license_master = null;
                            $params_license_master[0] = intval($files[$cnt_file]["license_id"]);    // license_id
                            
                            // クエリ実行
                            $license_masters = $this->Db->execute( $query, $params_license_master );
                            array_push($file_infos, 
                                    array('file_name' => $files[$cnt_file]["file_name"],
                                          'file_no' => $files[$cnt_file]["file_no"],
                                          'attribute_id' => $item_attr_types[$index]["attribute_id"],
                                          'license_id' => $license_masters[0]["license_id"],
                                          'license_notation' => $license_masters[0]["license_notation"],
                                          'img_url' => $license_masters[0]["img_url"],
                                          'text_url' => $license_masters[0]["text_url"],
                                          'file_flag' => $file_flag,
                                          'group_price' => $group_price
                                    )
                            );
                        } else {
                            array_push($file_infos, 
                                    array('file_name' => $files[$cnt_file]["file_name"],
                                          'file_no' => $files[$cnt_file]["file_no"],
                                          'attribute_id' => $item_attr_types[$index]["attribute_id"],
                                          'license_id' => $files[$cnt_file]["license_id"],
                                          'license_notation' => $files[$cnt_file]["license_notation"],
                                          'file_flag' => $file_flag,
                                          'group_price' => $group_price
                                    )
                            );
                        }
                        $cnt_file_num++;
                    } // ファイルのループ
                }
            } // アイテム属性タイプのループ
            // Modify Price method move validator K.Matsuo 2011/10/18 --end--
            if($cnt_file_num != 0){
                // ファイルをエクスポートしない設定 かつ 管理者でない
                // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
                // Add config management authority 2010/02/23 Y.Nakao --start--
                if(!$adminUser && !$insUser && $isExportFile!=self::EXPORT_FILE_ON)
                {
                // Add config management authority 2010/02/23 Y.Nakao --end--
                // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
                    // ファイルはあるがをエクスポートできないことを示すフラグ
                    $this->Session->setParameter("no_export", "true");
                    // ファイル数を0にする
                    $cnt_file_num = 0;
                    $file_infos = null;
                }
            }
            
            // ファイルの総数をセッションに保存
            $this->Session->setParameter("license_num", $cnt_file_num);
            // セッション情報にファイル情報を設定する
            $this->Session->setParameter("files", $file_infos);

            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                // 未実装
                print "終了処理失敗";
            }
            
            return 'success';
        } catch ( RepositoryException $exception){
            // 未実装
        }
    }

    /*
     * DBから指定された条件でExport情報を取得する
     * $query   ：クエリ
     * $param   ：パラメータ
     */
    function getExportInfo($query, $param){

        // クエリ実行
        $export_infos = $this->Db->execute( $query, $param );
        // データが取得できなかった場合
        if (!(isset($export_infos[0]))){
            // エラー処理を記述（未実装）
            // Exception をThrow
        }

        // 実行結果がエラーの場合
        if ( $export_infos == false ){
            // エラー処理を記述（未実装）
            // Exception をThrow
        }
        return $export_infos;
    }
}
?>
