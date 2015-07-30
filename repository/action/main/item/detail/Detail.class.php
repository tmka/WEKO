<?php
// --------------------------------------------------------------------
//
// $Id: Detail.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
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
require_once WEBAPP_DIR. '/modules/repository/components/IDServer.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Detail extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    // リクエストパラメタ
    var $item_id_no = null;             // 詳細表示するアイテムID
    var $shown_status = null;           // 表示非表示切り替え
    var $item_id = null;                // 削除対象のアイテムタイプID
    var $item_no = null;                // 削除対象のアイテムタイプID
    var $item_update_date = null;       // DBの更新時間
    var $workflow_flag = null;          // ワークフローからの遷移を示す
    var $workflow_active_tab = null;    // ワークフローの選択中のタブ
    var $get_id_flag = null;            // suffixID取得用フラグ
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
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
            // 一覧表示画面ではないことを示すフラグ
            $this->Session->setParameter("search_flg","false");
            
            if($this->item_id_no != null){
                // アイテム詳細画面に遷移する前のaction
                // commonCls.send()によるAJAX遷移ではなく、普通の遷移になる予定。
                // 一覧ページの詳細リンクを押すと、formのhiddenパラメタで本アクション名を指定してsubmitということになるか。
                // ・・・
                // いづれにせよ、下記の処理は何処のタイミングで行うはずであろう。
                // 1.アイテムIDから全メタデータを取得し、表示
                // 2.ユーザ権限を参照し、"編集","削除","公開／非公開"のフラグを立てる
                $id_and_no = explode("_", $this->item_id_no);
                if($id_and_no === false){
                    $this->Session->setParameter("error_msg","idと通番なし");
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    return 'error';
                }
                
                // アイテムID,アイテム通番をSessionに保存
                $this->Session->setParameter("item_id_for_detail", $id_and_no[0]);
                $this->Session->setParameter("item_no_for_detail", $id_and_no[1]);      
                
                // アイテムID,アイテム通番,titleをSessionに保存
                // 送信情報にタイトル追加 2008/03/18
                $item_info = array('item_id' => $id_and_no[0], 'item_no' => $id_and_no[1], 'title' => $id_and_no[2]);
                $this->Session->setParameter("item_info", $item_info);
                
                // エラーメッセージ解除
                $this->Session->removeParameter("error_msg");
                
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                
                return 'success';
                
            } else {
                // 削除実行時呼び出し処理
                // アイテムID,通番をセット
                $item_id = $this->item_id;
                $item_no = $this->item_no;
                
                if($item_id==null || $item_no==null){
                    $this->Session->setParameter("error_msg","対象が存在しません");
                    return 'error';
                }
                
                // ユーザIDゲット
                $user_id = $this->Session->getParameter("_user_id");
                
                // 削除の前に更新されていないかチェック
                $query = "SELECT mod_date ".
                         "FROM ". DATABASE_PREFIX ."repository_item ".
                         "WHERE item_id = ? AND ".
                         "item_no = ? AND ".
                         "is_delete = ? AND ".
                         "mod_date = ? ".
                         "FOR UPDATE;";
                $params = null;
                $params[] = $item_id;   // item_id
                $params[] = $item_no;   // item_no
                $params[] = 0;
                $params[] = $this->item_update_date;
                $ret = $this->Db->execute($query, $params);             
                // 削除しない場合もあるので開放!
                $this->Session->getParameter("item_update_date");
                //SQLエラーの場合
                if($ret === false) {
                    //必要であればSQLエラー番号・メッセージ取得
                    $errNo = $this->Db->ErrorNo();
                    $errMsg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_code", $errMsg);
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
                //取得結果が0件の場合
                //この場合、UPDATE対象のレコードは存在しないこととなる。
                //以降のUPDATE処理は行わないこと。
                if(count($ret)==0) {
                    $this->Session->setParameter("error_cord", 7);
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                    //throw $exception;
                    //アクション終了処理
                    $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
                    //異常終了 この場合アイテムタイプ選択に戻る
                    
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    
                    return "error_update";
                }
                // 公開、非公開切り替え処理追加 2008/03/24
                if($this->shown_status!=null && ($this->shown_status == 0 || $this->shown_status == 1)) {
                    $result = $this->change_Show_Flg();
                    if($result === false){
                        //エラー処理を行う
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                        throw $exception;
                    }
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    if($this->workflow_flag === "true"){
                        return 'workflow';
                    } else {
                        return 'change_status';
                    }
                }
                
                // Add get suffixID button for detail page 2009/09/03 A.Suzuki --start--
                $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
                if($this->get_id_flag == "true"){
                    // Mod Item Handle Management T.Koyasu 2014/01/28 --start--
                    // get suffix
                    $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
                    
                    // register y handle suffix and insert to database
                    $repositoryHandleManager->registerYhandleSuffix("", $item_id, $item_no);
                    // insert new selfdoi metadata to selfdoi index table
                    $searchTableProcessing->updateSelfDoiSearchTable($item_id, $item_no);
                    // get suffix from database
                    $suffix = $repositoryHandleManager->getSuffix($item_id, $item_no, RepositoryHandleManager::ID_Y_HANDLE);
                    
                    if(strlen($suffix) == 0) {
                        // ID取得失敗
                        // エラーメッセージ設定
                        $this->Session->setParameter("id_error_flag", "true");
                    }
                    // Mod Item Handle Management T.Koyasu 2014/01/28 --end--
                    
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    return 'change_status';
                }
                // Add get suffixID button for detail page 2009/09/03 A.Suzuki --end--
                
                // Add count contents 2008/12/22 A.Suzuki --start--
                // check shown_status
                $query = "SELECT shown_status ".
                         "FROM ". DATABASE_PREFIX ."repository_item ".
                         "WHERE item_id = ? AND ".
                         "item_no = ? AND ".
                         "is_delete = 0; ";
                $params = null;
                $params[] = $item_id;   // item_id
                $params[] = $item_no;   // item_no
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    $errNo = $this->Db->ErrorNo();
                    $errMsg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_code", $errMsg);
                    return false;
                }
                $shown_status = $result[0]['shown_status'];
                // 公開中である場合コンテンツ数を減らす
                $query = "SELECT pos.index_id, idx.public_state ".
                         "FROM ". DATABASE_PREFIX ."repository_position_index AS pos, ".
                         DATABASE_PREFIX."repository_index AS idx ".
                         "WHERE pos.item_id = ? ".
                         "AND pos.item_no = ? ".
                         "AND pos.is_delete = ? ".
                         "AND pos.index_id = idx.index_id ".
                         "AND idx.is_delete = ? ";
                $params = null;
                $params[] = $item_id;   // item_id
                $params[] = $item_no;   // item_no
                $params[] = 0;
                $params[] = 0;
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    $errNo = $this->Db->ErrorNo();
                    $errMsg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_code", $errMsg);
                    return false;
                }
                if($shown_status == 1){
                    // Fix contents update action 2010/07/02 Y.Nakao --start--
                    for($ii=0; $ii<count($result); $ii++){
                        // check public status
                        if($result[$ii]['public_state'] == "1" && $this->checkParentPublicState($result[$ii]['index_id'])){
                            $this->deleteContents($result[$ii]['index_id']);
                        } else {
                            $this->deletePrivateContents($result[$ii]['index_id']);
                        }
                    }
                    // Fix contents update action 2010/07/02 Y.Nakao --end--
                    
                    // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki --start--
                    // 新着情報から削除する
                    $result = $this->deleteWhatsnew($this->item_id);
                    // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki --end--
                    
                    if($result === false){
                        $this->Session->setParameter("error_msg",$error_msg);
                        //エラー処理を行う
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                        throw $exception;
                    }
                } else {
                    for($ii=0; $ii<count($result); $ii++){
                    	$this->deletePrivateContents($result[$ii]['index_id']);
                    }
                }
                // Add count contents 2008/12/22 A.Suzuki --end--
                
                // 削除実行
                $result = $this->deleteItemData($item_id,$item_no,$user_id,$error_msg);
                if($result === false){
                    $this->Session->setParameter("error_msg",$error_msg);
                    //エラー処理を行う
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                    throw $exception;
                }
                // Session開放
                //$this->Session->removeParameter("item_id_for_detail");
                //$this->Session->removeParameter("item_no_for_detail");
                
                // エラーメッセージ解除
                $this->Session->removeParameter("error_msg");
                $this->Session->removeParameter("error_code");
                                
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                
                // ワークフローに戻る
                if($this->workflow_flag === "true"){
                    return 'workflow';
                }
                
                // 検索画面を詳細から一覧表示へ
                $this->Session->removeParameter("serach_screen", "ture");
                
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                return 'delete_success';
            }
            
        }
        catch ( RepositoryException $Exception) {
            //エラーログ出力
            /*
            logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
                $Exception->getCode(),          //ログID
                $Exception->getMessage(),       //主メッセージ
                $Exception->getDetailMsg() );   //詳細メッセージ
            */
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
                                    
            //異常終了
            return "error";
        }
    }
    
    function change_Show_Flg(){
        // 公開／非公開切り替え
        $query = "UPDATE ". DATABASE_PREFIX ."repository_item ".
                 "SET shown_status = ?, ".
                 "mod_date = ?, ".
                 "mod_user_id = ? ".
                 "WHERE item_id = ? AND ".
                 "item_no = ? AND ".
                 "is_delete = ?; ";
        $params = null;
        $params[] = $this->shown_status;    // shown_status
        $params[] = $this->TransStartDate;  // mod_date
        $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
        //　公開、非公開の切り替え不具合修正 2008/06/20 Y.Nakao --start--
        $params[] = $this->item_id; // item_id
        $params[] = $this->item_no; // item_no
        // 公開、非公開の切り替え不具合修正　2008/06/20 Y.Nakao --end--
        $params[] = 0;                      // is_delete
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errNo = $this->Db->ErrorNo();
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_code", $errMsg);
            return false;
        }
        
        // Add count contents 2008/12/22 A.Suzuki --start--
        // 所属するインデックスのindex_idとpublic_stateを取得
        $query = "SELECT ".DATABASE_PREFIX."repository_index.index_id, ".DATABASE_PREFIX."repository_index.public_state ".
                 "FROM ".DATABASE_PREFIX."repository_index, ".DATABASE_PREFIX."repository_position_index ".
                 "WHERE ".DATABASE_PREFIX."repository_position_index.item_id = ? ".
                 "AND ".DATABASE_PREFIX."repository_position_index.item_no = ? ".
                 "AND ".DATABASE_PREFIX."repository_position_index.is_delete = 0 ".
                 "AND ".DATABASE_PREFIX."repository_position_index.index_id = ".DATABASE_PREFIX."repository_index.index_id ".
                 "AND ".DATABASE_PREFIX."repository_index.is_delete = 0 ;";
        $params = null;
        $params[] = $this->item_id; // item_id
        $params[] = $this->item_no; // item_no
        $result = $this->Db->execute($query,$params);
        if($result === false){
            $errNo = $this->Db->ErrorNo();
            $errMsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_code", $errMsg);
            return false;
        }
        
        if($this->shown_status == 1){
            // Add check unpublic index 2009/02/05 A.Suzuki --start--
            $pub_index_flag = false;
            for($ii=0; $ii<count($result); $ii++){
                // 公開中のインデックスがあるか
                if($result[$ii]['public_state'] == "1"){
                    // 親インデックスが公開されているか
                    if($this->checkParentPublicState($result[$ii]['index_id'])){
                        $pub_index_flag = true;
                        $this->addContents($result[$ii]['index_id']);
                        $this->deletePrivateContents($result[$ii]['index_id']);		// Add private_contents count K.Matsuo 2013/05/07
                    }
                }
            }
            
            if($pub_index_flag){
                // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki
                $this->addWhatsnew($this->item_id, $this->item_no);
            } else {
                $this->deleteWhatsnew($this->item_id);
            }
            // Add check unpublic index 2009/02/05 A.Suzuki --end--
        }else{
            for($ii=0; $ii<count($result); $ii++){
                // 公開中のインデックスがあるか
                if($result[$ii]['public_state'] == "1"){
                    // 親インデックスが公開されているか
                    if($this->checkParentPublicState($result[$ii]['index_id'])){
                        $this->deleteContents($result[$ii]['index_id']);
                        $this->addPrivateContents($result[$ii]['index_id']);		// Add private_contents count K.Matsuo 2013/05/07
                    }
                }
            }
            // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki
            $this->deleteWhatsnew($this->item_id);
        }
        // Add count contents 2008/12/22 A.Suzuki --end--
        
        return true;
    }
}
?>
