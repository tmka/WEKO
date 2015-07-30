<?php
// --------------------------------------------------------------------
//
// $Id: Createfulltext.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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

/**
 * repositoryモジュール フルテキストインデックス作成
 *
 * @package     NetCommons
 * @author      S.Kawasaki(IVIS)
 * @copyright   2006-2008 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Edit_Createfulltext extends RepositoryAction
{
    // リクエストパラメタ (配列ではなく個別で渡す)  
    var $OS_type = null;                        // リポジトリ全般 : OS種類
    var $disp_index_type = null;                // リポジトリ全般 : 初期表示インデックス表示方法
    var $default_disp_index = null;             // リポジトリ全般 : 初期表示インデックス
    var $ranking_term_recent_regist = null;     // ランキング管理 : 新規登録期間
    var $ranking_term_stats = null;             // ランキング管理 : 統計期間
    var $ranking_disp_num = null;               // ランキング管理 : 表示順位
    var $ranking_is_disp_browse_item = null;    // ランキング表示可否, 最も閲覧されたアイテム
    var $ranking_is_disp_download_item = null;  // ランキング表示可否, 最もダウンロードされたアイテム
    var $ranking_is_disp_item_creator = null;   // ランキング表示可否, 最もアイテムを作成したユーザ
    var $ranking_is_disp_keyword = null;        // ランキング表示可否, 最も検索されたキーワード
    var $ranking_is_disp_recent_item = null;    // ランキング表示可否, 新着アイテム
    var $item_coef_cp = null;                   // アイテム管理 : 係数Cp
    var $item_coef_ci = null;                   // アイテム管理 : 係数Ci
    var $file_coef_cp = null;                   // アイテム管理 : 係数Cpf
    var $file_coef_ci = null;                   // アイテム管理 : 係数Cif
    var $export_is_include_files = null;        // アイテム管理 : Export ファイル出力の可否
    
    var $admin_active_tab = null;
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $istest = true;             // テスト用フラグ
        try {
            //アクション初期化処理
            $result = $this->initAction();          
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // 開始日時を取得 Add create fulltextindex info 2008/09/04 Y.Nakao
            //$start_time = date( "Y/m/d H:i:s", time() );
            $DATE = new Date();
            $start_time = str_replace("-","/", $DATE->getDate());
            
            // ----------------------------------------------------
            // インデックステーブル削除
            // ----------------------------------------------------
            $query = "SHOW INDEX FROM ". DATABASE_PREFIX ."repository_fulltext_data";
            $index_result = $this->Db->execute($query);
            if ($index_result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("DROP INDEX failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            $drop_flag_fulltext = false;
            $drop_flag_metadata = false;
            for($ii=0;$ii<count($index_result);$ii++){
                if($index_result[$ii]["Key_name"]=="extracted_text" && $index_result[$ii]["Index_type"]=="FULLTEXT"){
                    $drop_flag_fulltext = true;
                }
                if($index_result[$ii]["Key_name"]=="metadata" && $index_result[$ii]["Index_type"]=="FULLTEXT"){
                    $drop_flag_metadata = true;
                }
                if($drop_flag_fulltext && $drop_flag_metadata){
                    break;
                }
            }
            if($drop_flag_fulltext){
                $query = "DROP INDEX extracted_text ON ". DATABASE_PREFIX ."repository_fulltext_data";
                $result = $this->Db->execute($query);
                if ($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    $tmpstr = sprintf("DROP INDEX failed : %s", $errMsg ); 
                    $this->Session->setParameter("error_msg", $tmpstr);
                    $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                    return 'error';
                }
            }
            if($drop_flag_metadata){
                $query = "DROP INDEX metadata ON ". DATABASE_PREFIX ."repository_fulltext_data";
                $result = $this->Db->execute($query);
                if ($result === false) {
                    $errMsg = $this->Db->ErrorMsg();
                    $tmpstr = sprintf("DROP INDEX failed : %s", $errMsg ); 
                    $this->Session->setParameter("error_msg", $tmpstr);
                    $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                    return 'error';
                }
            }
            
            // ----------------------------------------------------
            // インデックステーブル構築
            // ----------------------------------------------------
            $query = "CREATE FULLTEXT INDEX extracted_text ON ". DATABASE_PREFIX ."repository_fulltext_data(extracted_text)";
            $result = $this->Db->execute($query);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("CREATE FULLTEXT INDEX failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            
            $query = "CREATE FULLTEXT INDEX metadata ON ". DATABASE_PREFIX ."repository_fulltext_data(metadata)";
            $result = $this->Db->execute($query);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("CREATE FULLTEXT INDEX failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            
            // 終了日時を取得
            //$end_time = date( "Y/m/d H:i:s", time() );
            $DATE = new Date();
            $end_time = str_replace("-","/", $DATE->getDate());
            // コンテンツ数を取得
            $query = "SELECT item_id, item_no FROM ". DATABASE_PREFIX ."repository_fulltext_data ";
            $result = $this->Db->execute($query);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("CREATE FULLTEXT INDEX failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            $contents = count($result);

            // Add create fulltextindex info 2008/09/04 Y.Nakao --start--
            // ----------------------------------------------------
            // フルテキストインデックス作成結果格納
            // ----------------------------------------------------
            $params = null;             // パラメタテーブル更新用クエリ           
            $params[] = '';             // param_value
            $params[] = $this->Session->getParameter("_user_id");// mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = '';             // param_name
            // 開始日時
            $params[0] = $start_time;   // param_value
            $params[3] = 'fulltextindex_starttime';         // param_name
            $result = $this->updateParamTableData($params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("fulltextindex_starttime update failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            // コンテンツ数
            $params[0] = $contents; // param_value
            $params[3] = 'fulltextindex_contents';          // param_name
            $result = $this->updateParamTableData($params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("fulltextindex_contents update failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            // 終了日時
            $params[0] = $end_time; // param_value
            $params[3] = 'fulltextindex_endtime';           // param_name
            $result = $this->updateParamTableData($params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("fulltextindex_endtime update failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }   
            // Add create fulltextindex info 2008/09/04 Y.Nakao --end--
            
            // Add tab 2009/01/19 A.Suzuki --start--
            $this->Session->setParameter("admin_active_tab", $this->admin_active_tab);
            // Add tab 2009/01/19 A.Suzuki --end--
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            return 'success';
        }
        catch ( RepositoryException $Exception) {
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
            return "error";
        }
    }
}
?>
