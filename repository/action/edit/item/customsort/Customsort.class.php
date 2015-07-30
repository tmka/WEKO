<?php
// --------------------------------------------------------------------
//
// $Id: Customsort.class.php 33161 2014-03-24 05:51:26Z tomohiro_ichikawa $
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
 * [[アイテム管理actionアクション]]
 * 表示順序設定Action
 * 
 * @package	 [[package名]]
 * @access	  public
 */
class Repository_Action_Edit_Item_Customsort extends RepositoryAction
{
    // コンポーネント受け取り
    var $Session = null;
    var $Db = null;

    //*********************
    //リクエストパラメータ
    //*********************
    // 選択インデックスID
    public $targetIndexId = null;
    // 移動元表示順序インデックス
    public $currentSortOrder = null;
    // 移動先表示順序インデックス
    public $targetSortOrder = null;

    /**
     * 表示順序設定execute
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
                $this->failTrans();                                                 //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // タブ状態の保存 
            $this->Session->setParameter("item_setting_active_tab", 0);
            
            //引数チェック//
            if( $this->targetIndexId == null || $this->targetIndexId == "" ){
                $this->Session->setParameter("error_msg", "Select Index Error.");
                return 'error';
            }

            //1. セッション: targetIndexIdにメンバ変数: targetIndexIdを格納
            $this->Session->setParameter("targetIndexId",$this->targetIndexId);
            $this->Session->setParameter("searchIndexId", $this->targetIndexId);

            //2. 1で取得したアイテム一覧の表示順インデックスを更新
            $ret = $this->updateCustomSortOrder();
            if($ret == "error"){
                $this->Session->setParameter("error_msg", "update CustomSortOrder Error");
                return 'error';
            }

            // アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                $this->failTrans();                                                 //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // エラーメッセージ開放
            $this->Session->removeParameter("error_msg");
            
            return 'success';
        }
        catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "Repository_Action_Edit_Item_Customsort",    //クラス名
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
     * 引数で指定したインデックス以下の表示順インデックスを更新する
     *
     * @return string success成功/error失敗
     * 
     */
    private function updateCustomSortOrder(){
        //1. 表示順序インデックスの振り直し
        //下方に移動する場合
        if($this->currentSortOrder < $this->targetSortOrder){
            // ------------------------------------------------
            // 移動先が削除されたアイテムの場合、さらに下方のアイテムに移動する
            // ------------------------------------------------
            $query = "SELECT custom_sort_order FROM ".DATABASE_PREFIX ."repository_position_index ".
             "WHERE index_id=? ".
             "AND custom_sort_order >= ? ".
             "AND is_delete=?".
             " ORDER BY custom_sort_order ASC;";
            $params = array();
            $params[] = $this->targetIndexId;
            $params[] = $this->targetSortOrder;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                return false;
            }
            if(count($result)>0){
                $this->targetSortOrder = $result[0]['custom_sort_order'];
            }
            
            // ---------------------------------------
            // ダミーインデックスの設定 currentSortIndexId
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = ? ".
                     "WHERE custom_sort_order = ? ".
                     " AND index_id = ? ".
                     " AND custom_sort_order > ?;";
            $params = array();
            $params[] = $this->currentSortOrder * (-1);
            $params[] = $this->currentSortOrder;
            $params[] = $this->targetIndexId;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
            
            // ---------------------------------------
            // 下方に移動する
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = custom_sort_order-1 ".
                     "WHERE index_id=?".
                     " AND custom_sort_order <= ?".
                     " AND custom_sort_order >= ?;";

            $params = array();
            $params[] = $this->targetIndexId;
            $params[] =$this->targetSortOrder;
            $params[] =$this->currentSortOrder+1;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
            // ---------------------------------------
            // マイナスに設定した表示順インデックスのレコードを更新する
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = ? ".
                     "WHERE custom_sort_order=?;";
            $params = array();
            $params[] = $this->targetSortOrder;
            $params[] = $this->currentSortOrder * (-1);
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
        }
        //上方に移動する場合
        else if($this->currentSortOrder > $this->targetSortOrder){
            // ------------------------------------------------
            // 移動先が削除されたアイテムの場合、さらに上方のアイテムに移動する
            // ------------------------------------------------
            $query = "SELECT custom_sort_order FROM ".DATABASE_PREFIX ."repository_position_index ".
             "WHERE index_id=? ".
             "AND custom_sort_order <= ? ".
             "AND is_delete=?".
             " ORDER BY custom_sort_order DESC;";
            $params = array();
            $params[] = $this->targetIndexId;
            $params[] = $this->targetSortOrder;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                return false;
            }
            if(count($result)>0){
                $this->targetSortOrder = $result[0]['custom_sort_order'];
            }
            
            // ---------------------------------------
            // ダミーインデックスの設定 currentSortIndexId
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = ? ".
                     "WHERE custom_sort_order = ? ".
                     " AND index_id = ? ".
                     " AND custom_sort_order > ?;";
            $params = array();
            $params[] = $this->currentSortOrder * (-1);
            $params[] = $this->currentSortOrder;
            $params[] = $this->targetIndexId;
            $params[] = 0;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
            
            // ---------------------------------------
            // 上方に移動する
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = custom_sort_order+1 ".
                     "WHERE index_id=?".
                     " AND custom_sort_order <= ?".
                     " AND custom_sort_order >= ?;";
                 
            $params = array();
            $params[] = $this->targetIndexId;
            $params[] = $this->currentSortOrder-1;
            $params[] = $this->targetSortOrder;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
            // ---------------------------------------
            // マイナスに設定した表示順インデックスのレコードを更新する
            // ---------------------------------------
            $query = "UPDATE ".DATABASE_PREFIX ."repository_position_index ".
                     "SET custom_sort_order = ? ".
                     "WHERE custom_sort_order=? ;";
            $params = array();
            $params[] = $this->targetSortOrder;
            $params[] = $this->currentSortOrder * (-1);
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                return "error";
            }
        }
        
        // ---------------------------------------
        // custom_sort_orderで連番更新する
        // ---------------------------------------
        $query = "UPDATE ".DATABASE_PREFIX."repository_position_index ".
                 "SET custom_sort_order = (".
                    "SELECT @i:=@i+1 ".
                    "FROM (SELECT @i:=0) as dummy".
                 ")".
                 "WHERE index_id = ? ORDER BY custom_sort_order;";
        $params = array();
        $params[] = $this->targetIndexId;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_msg", $errMsg);
            return "error";
        }
        
        //exit;
        return "success";
    }
}
?>
