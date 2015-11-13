<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 56999 2015-08-24 12:32:56Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryCheckFileTypeUtility.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/OperateFileSystem.class.php';

/**
 * アイテム登録：リンク設定画面からの入力処理アクション
 * 
 * @access      public
 */
class Repository_Action_Main_Item_Confirm extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    public $mailMain = null;
    
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
    
    // メンバ変数
    private $warningMsg = array();  // 警告メッセージ
    
    /**
     * 実行処理
     * @see RepositoryAction::executeApp()
     */
    protected function executeApp()
    {
        // ------------------------------------------------------------------
        // 準備処理, セッション情報の取得
        // ------------------------------------------------------------------
        // セッション情報取得
        $item_type_all = $this->Session->getParameter("item_type_all");                 // 1.アイテムタイプ,  アイテムタイプのレコードをそのまま保存したものである。
        $item_attr_type = $this->Session->getParameter("item_attr_type");               // 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        $item_num_cand = $this->Session->getParameter("item_num_cand");                 // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        $option_data = $this->Session->getParameter("option_data");                     // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～                
        // ユーザ入力値＆変数
        $item_num_attr = $this->Session->getParameter("item_num_attr");                 // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        $item_attr_session = $this->Session->getParameter("item_attr");                 // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～        
        $base_attr = $this->Session->getParameter("base_attr");                         // 7.アイテム基本属性(今のところタイトルと言語のユーザー入力値)
        $license_master = $this->Session->getParameter("license_master");               // ライセンスマスタ
        $indice = $this->Session->getParameter("indice");                               // X.関連インデックス
        $link = $this->Session->getParameter("link");                                   // X.リンクリスト
        $edit_flag = $this->Session->getParameter("edit_flag");                         // X.処理モード(0:新規作成, 1:既存編集)
        $edit_flag = $this->Session->getParameter("edit_flag");                         // X.処理モード(0:新規作成, 1:既存編集)
        $delete_file_list = $this->Session->getParameter("delete_file_list");           // X.DB登録済み削除ファイルリスト : delete_file_list (既存編集時のみ)
        $item_pub_date = $this->Session->getParameter('item_pub_date');                 // X.アイテム公開日
        $item_keyword = $this->Session->getParameter('item_keyword');                   // X.アイテムキーワード
        $item_keyword_english = $this->Session->getParameter('item_keyword_english');   // X.アイテムキーワード(英)
        $contributorUserId = $this->Session->getParameter("contributorUserId");         // Contributor user_id
        
        // 登録情報
        $user_id = $this->Session->getParameter("_user_id");    // ユーザID
        $edit_start_date = $this->Session->getParameter("edit_start_date");
        
        // ------------------------------------------------------------------
        // 準備処理, アイテムID, NO決定
        // ------------------------------------------------------------------
        $item_id = intval($this->Session->getParameter("edit_item_id"));
        $item_no = intval($this->Session->getParameter("edit_item_no"));
        
        // 指定遷移先へ遷移可能かチェック＆遷移先の決定
        $this->infoLog("Get instance: businessItemedittranscheck", __FILE__, __CLASS__, __LINE__);
        $transCheck = BusinessFactory::getFactory()->getBusiness("businessItemedittranscheck");
        $transCheck->setData(   "confirm",
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
        $returnString = $transCheck->getDestination();
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
        
        // 登録処理以外の画面へ遷移する場合はここで終了
        if($returnString!="redirect"){
            return $returnString;
        }
        
        // ------------------------------------------------------------------
        // 準備処理, アイテム管理パラメータ取得
        // 査読対象が登録された場合メールを送信するかどうかのパラメータ取得追加 2009/09/16 
        // ------------------------------------------------------------------
        // 査読・承認を行うか否か
        $query = "SELECT `param_value` ".
                 "FROM `{repository_parameter}` ".
                 "WHERE `param_name` = ?;";
        $params = array();
        $params[] = 'review_flg';
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            $tmpErrorMsg = $this->Db->ErrorMsg();
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $review_flg = $ret[0]['param_value'];
        // 承認済みアイテムを自動公開するか否か
        $query = "SELECT `param_value` ".
                 "FROM `{repository_parameter}` ".
                 "WHERE `param_name` = ?;";
        $params = array();
        $params[] = 'item_auto_public';
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            $tmpErrorMsg = $this->Db->ErrorMsg();
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $shown_flg = $ret[0]['param_value'];
        
        // Add review mail setting 2009/09/24 Y.Nakao --start--
        // 新規査読アイテム登録メール送信処理
        // 査読・承認を行うか否か
        $query = "SELECT `param_value` ".
                 "FROM `{repository_parameter}` ".
                 "WHERE `param_name` = ?;";
        $params = array();
        $params[] = 'review_mail_flg';
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            $tmpErrorMsg = $this->Db->ErrorMsg();
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        $review_mail_flg = $ret[0]['param_value'];
        // Add review mail setting 2009/09/24 Y.Nakao --end--
        
        if($this->Session->getParameter('item_entry_flg') == 'true'){
            // Add new prefix 2013/12/24 T.Ichiakwa --start--
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->accessDate);
            $prefix_id = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
            $uri = $repositoryHandleManager->getSubstanceUri($item_id, $item_no);
            $detail_uri = BASE_URL."/?action=repository_uri&item_id=".$item_id;
            // Add new prefix 2013/12/24 T.Ichiakwa --end--
            if(strlen($uri) == 0){
                // when uri is null, maybe this is new item
                // check log exist
                $query = "SELECT count(*) FROM {repository_log} ".
                         "WHERE operation_id = 1 AND ".
                         "item_id = ". $item_id ." AND ".
                         "item_no = ". $item_no ."; ";
                $result = $this->Db->execute($query);
                if ($result === false) {
                    $tmpErrorMsg = $this->Db->ErrorMsg();
                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($tmpErrorMsg);
                    $exception->addError($tmpErrorMsg);
                    throw $exception;
                }
                if(count($result) == 0 || $result[0]['count(*)'] == 0){
                    // ------------------------------------------------------------------
                    // insert item entry log
                    // ------------------------------------------------------------------
                    // Mod entryLog T.Koyasu 2015/03/06 --start--
                    $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
                    $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
                    $logManager->entryLogForRegistItem($item_id, $item_no);
                    // Mod entryLog T.Koyasu 2015/03/06 --end--
                }
            }
            
            // Check shown_status
            $query = "SELECT shown_status ".
                     "FROM {repository_item} ".
                     "WHERE item_id = ? ".
                     "AND item_no = ?;";
            $params = array();
            $params[] = $item_id;   // item_id
            $params[] = $item_no;   // item_no
            $result = $this->Db->execute($query, $params);
            if ($result === false) {
                $tmpErrorMsg = $this->Db->ErrorMsg();
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            $old_shown_status = $result[0]["shown_status"];
            
            // アイテムの査読、公開状態を変更する
            $query = "UPDATE {repository_item} ".
                     "SET uri = ?, ".
                     "review_status = ?, ".
                     "review_date = ?, ".
                     "shown_status = ?, ".
                     "shown_date = ?, ".
                     "reject_status = ?, ".
                     "reject_date = ?, ".
                     "reject_reason = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ? ";
            $params = array();
            $params[] = $detail_uri;
            if($review_flg == 1){
                // 査読を行う(承認しない)
                $params[] = 0;      // review_status
                $params[] = "";     // review_date
                $params[] = 0;      // shown_status
            } else {
                // 査読を行わない(承認する)
                $params[] = "1";                    // "review_status"
                $params[] = $this->accessDate;      // review_date
                $params[] = $shown_flg;             // shown_status
            }
            $params[] = $this->generateDateStr($item_pub_date['year'], $item_pub_date['month'], $item_pub_date['day']); // "shown_date"
            $params[] = 0;                  // reject_status
            $params[] = "";                 // reject_date
            $params[] = "";                 // reject_reason
            $params[] = $user_id;           // mod_user_id
            $params[] = $edit_start_date;   // mod_date
            if($contributorUserId != $user_id)
            {
                $query .= ", ins_user_id = ? ";
                $params[] = $contributorUserId;
            }
            $query .= "WHERE item_id = ? AND ".
                      "item_no = ?; ";
            $params[] = $item_id;       // item_id
            $params[] = $item_no;       // item_no
            //UPDATE実行
            $result = $this->Db->execute($query, $params);
            if ($result === false) {
                $tmpErrorMsg = $this->Db->ErrorMsg();
                $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($tmpErrorMsg);
                $exception->addError($tmpErrorMsg);
                throw $exception;
            }
            
            // PDF Cover page 2012/06/15 A.Suzuki --start--
            // Mod PDF Cover page timing 2015/01/26 K.Matsushita --start--
            $cover_error_flg = "";
            $cover_error = "";
            // Mod PDF Cover page timing 2015/01/26 K.Matsushita --end--
            // Add PDF Cover page 2012/06/15 A.Suzuki --end--
            
            // Add PDF flash 2010/02/04 A.Suzuki --start--
            $ItemRegister = new ItemRegister($this->Session, $this->Db);
            $ItemRegister->setInsUserId($contributorUserId);
            $flash_error_flg = "";
            for($ii=0; $ii<count($item_attr_type); $ii++) {
                for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
                    // アップロード済みファイルのライセンス設定
                    if( ($item_attr_type[$ii]['input_type']=='file' || 
                        $item_attr_type[$ii]['input_type']=='file_price') 
                         && isset($item_attr_session[$ii][$jj]["upload"]))
                    {
                        // Add PDF Cover page 2012/06/15 A.Suzuki --start--
                        // Mod PDF Cover page timing 2015/01/26 K.Matsushita --start--
                        if(strtolower($item_attr_session[$ii][$jj]["upload"]["extension"])=="pdf")
                        {
                            // 作業用ディレクトリ作成
                            $tmpDirPath = "";
                            $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
                            $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
                            $tmpDirPath = $businessWorkdirectory->create();
                            
                            // PDFカバーページ削除
                            $this->infoLog("businessPdfcover", __FILE__, __CLASS__, __LINE__);
                            $pdfCover = BusinessFactory::getFactory()->getBusiness("businessPdfcover");
                            $deletedFile = "";
                            $ret = $pdfCover->deleteCoverPage($item_attr_session[$ii][$jj]["item_id"], 
                                                              $item_attr_session[$ii][$jj]["item_no"], 
                                                              $item_attr_session[$ii][$jj]["attribute_id"],
                                                              $item_attr_session[$ii][$jj]["file_no"],
                                                              $tmpDirPath,
                                                              $deletedFile);
                            if(strlen($deletedFile) > 0) {
                                // Delete this file's flash
                                $flashFolder = $this->getFlashFolder($item_attr_session[$ii][$jj]["item_id"],
                                                                     $item_attr_session[$ii][$jj]["attribute_id"],
                                                                     $item_attr_session[$ii][$jj]["file_no"]);
                                if(strlen($flashFolder) > 0) {
                                    Repository_Components_Util_OperateFileSystem::removeDirectory($flashFolder);
                                }
                            } else {
                                $cover_error_flg = "true";
                                $cover_error = $pdfCover->getErrorMsg();
                            }
                        }
                        // Mod PDF Cover page timing 2015/01/26 K.Matsushita --end--
                        // Add PDF Cover page 2012/06/15 A.Suzuki --end--
                        
                        // check flash save directory exists
                        $flashDir = $this->getFlashFolder(  $item_attr_session[$ii][$jj]["item_id"],
                                                            $item_attr_session[$ii][$jj]["attribute_id"],
                                                            $item_attr_session[$ii][$jj]["file_no"]);
                        // check flash file exists
                        // すでに該当するフラッシュファイルがある場合は作成しない。
                        if( $item_attr_session[$ii][$jj]['display_type'] == 2 &&
                            (strlen($flashDir) == 0 || 
                                (!(file_exists($flashDir)) || 
                                    (
                                        !(file_exists($flashDir.'/weko.swf')) && 
                                        !(file_exists($flashDir.'/weko1.swf')) && 
                                        // Mod multimedia support 2012/10/09 T.Koyasu -start-
                                        // if exists multimedia file, is not execute pdf convert to flash
                                        !(file_exists($flashDir.'/weko.flv'))
                                        // Mod multimedia support 2012/10/09 T.Koyasu -end-
                                    )
                                )
                            )
                        ){
                            if($this->isMultimediaFile(
                                    $item_attr_session[$ii][$jj]["upload"]["mimetype"],
                                    strtolower($item_attr_session[$ii][$jj]["upload"]["extension"])))
                            {
                                if(strtolower($item_attr_session[$ii][$jj]["upload"]["extension"])=="swf" ||
                                   strtolower($item_attr_session[$ii][$jj]["upload"]["extension"])=="flv")
                                {
                                    // swf, flv のファイルはそのままコピー
                                    // check flash save directory exists
                                    $flashDir = $this->makeFlashFolder( $item_attr_session[$ii][$jj]["item_id"],
                                                                        $item_attr_session[$ii][$jj]["attribute_id"],
                                                                        $item_attr_session[$ii][$jj]["file_no"]);
                                    if(strlen($flashDir) > 0){
                                        $flashContentsPath = $flashDir."/weko.".strtolower($item_attr_session[$ii][$jj]["upload"]["extension"]);
                                        
                                        // コピー元ファイル取得
                                        $fileContentsPath = $this->getFileSavePath("file");
                                        if(strlen($fileContentsPath) == 0){
                                            // default directory
                                            $fileContentsPath = BASE_DIR.'/webapp/uploads/repository/files';
                                        }
                                        $fileContentsPath .= "/".$item_attr_session[$ii][$jj]["item_id"];
                                        $fileContentsPath .= "_".$item_attr_session[$ii][$jj]["attribute_id"];
                                        $fileContentsPath .= "_".$item_attr_session[$ii][$jj]["file_no"];
                                        $fileContentsPath .= ".".$item_attr_session[$ii][$jj]['upload']['extension'];
                                        if( file_exists($fileContentsPath) ){
                                            // file copy
                                            copy($fileContentsPath, $flashContentsPath);
                                        } else {
                                            // Not found file
                                            $item_attr_session[$ii][$jj]['display_type'] = 0;
                                            $result = $ItemRegister->updateFileLicense($item_attr_session[$ii][$jj], $error);
                                        }
                                    }
                                }
                                else
                                {
                                    // マルチメディアファイルを flv へ変換
                                    // 変換元ファイル取得
                                    $fileContentsPath = $this->getFileSavePath("file");
                                    if(strlen($fileContentsPath) == 0){
                                    // default directory
                                        $fileContentsPath = BASE_DIR.'/webapp/uploads/repository/files';
                                    }
                                    $fileContentsPath .= "/".$item_attr_session[$ii][$jj]["item_id"];
                                    $fileContentsPath .= "_".$item_attr_session[$ii][$jj]["attribute_id"];
                                    $fileContentsPath .= "_".$item_attr_session[$ii][$jj]["file_no"];
                                    $fileContentsPath .= ".".$item_attr_session[$ii][$jj]['upload']['extension'];
                                    $result = $ItemRegister->convertFileToFlv($item_attr_session[$ii][$jj], $error, $fileContentsPath);
                                    if($result == false)
                                    {
                                        // Convert failed
                                        $item_attr_session[$ii][$jj]['display_type'] = 0;
                                        $result = $ItemRegister->updateFileLicense($item_attr_session[$ii][$jj], $error);
                                    }
                                }
                            }
                            else if(!RepositoryCheckFileTypeUtility::isImageFile(
                                    $item_attr_session[$ii][$jj]["upload"]["mimetype"],
                                    strtolower($item_attr_session[$ii][$jj]["upload"]["extension"])))
                            {
                                if(strlen($prefix_id) > 0){
                                    // IDサーバと連携している
                                    // PDFのフラッシュ化処理
                                    $flash_error = "";
                                    $url = BASE_URL . "/?action=repository_uri&item_id=".$item_id;
                                    $id_server = new IDServer($this->Session, $this->Db);
                                    $result = $id_server->convertToFlash($item_attr_session[$ii][$jj], $url, $flash_error);
                                    if($result === "true"){
                                        // フラッシュ化成功
                                        //$item_attr_session[$ii][$jj]['display_type'] = 2;
                                    } else {
                                        // フラッシュ化失敗
                                        $item_attr_session[$ii][$jj]['display_type'] = 0;
                                        $result = $ItemRegister->updateFileLicense($item_attr_session[$ii][$jj], $error);
                                        $flash_error_flg = "true";
                                        $flash_error = "\"".$item_attr_session[$ii][$jj]['upload']['file_name']."\"";
                                    }
                                } else {
                                    // IDサーバと連携していないためフラッシュ作成不可
                                    $item_attr_session[$ii][$jj]['display_type'] = 0;
                                    $result = $ItemRegister->updateFileLicense($item_attr_session[$ii][$jj], $error);
                                    $flash_error_flg = "true";
                                    $flash_error = "\"".$item_attr_session[$ii][$jj]['upload']['file_name']."\"";
                                }
                            }
                        }
                    }
                }
            }
            // Add PDF flash 2010/02/04 A.Suzuki --end--
            
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
            $ItemRegister->updateInsertUserIdForContributor(
                    intval($this->Session->getParameter("edit_item_id")),
                    $this->Session->getParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID));
            // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
            
            // update search table S.Suzuki 2013/11/29 --start--
            $repositorySearchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            
            $repositorySearchTableProcessing->updateSearchTableForItem($item_id, $item_no);
            // update search table S.Suzuki 2013/11/29 --end--
            
            // Add count contents and add whatsnew 2009/02/17 A.Suzuki --start--
            $whatsnew_flag = false;
            for($ii=0; $ii<count($indice); $ii++) {
                // 査読を行わない
                if($review_flg == 0){
                    // 自動公開する
                    if($shown_flg == 1 && $old_shown_status != 1){
                        // 登録先インデックスの公開状態を取得
                        // Add tree access control list 2012/02/29 T.Koyasu -start-
                        // get index to regist is not set access control
                        $query = "SELECT public_state ".
                                 "FROM {repository_index} ".
                                 "WHERE index_id = ? ".
                                 "AND is_delete = 0 ".
                                 "AND exclusive_acl_role = ? ". 
                                 "AND exclusive_acl_group = ? ; ";
                        $param = array();
                        $param[] = $indice[$ii]['index_id'];
                        $param[] = '|-1';
                        $param[] = '';
                        // Add tree access control list 2012/02/29 T.Koyasu -end-
                        $result = $this->Db->execute($query, $param);
                        if($result === false){
                            $tmpErrorMsg = $this->Db->ErrorMsg();
                            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                            $exception = new AppException($tmpErrorMsg);
                            $exception->addError($tmpErrorMsg);
                            throw $exception;
                        }
                        
                        // インデックスが公開中であるか
                        if(count($result) == 1 && $result[0]['public_state'] == "1"){
                            // 親インデックスが公開されているか
                            if($this->checkParentPublicState($indice[$ii]['index_id'])){
                                // 所属する公開中インデックスのコンテンツ数を増やす
                                $whatsnew_flag = true;
                                $result = $this->addContents($indice[$ii]['index_id']);
                                if($result === false){
                                    $tmpErrorMsg = $this->Db->ErrorMsg();
                                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                                    $exception = new AppException($tmpErrorMsg);
                                    $exception->addError($tmpErrorMsg);
                                    throw $exception;
                                }
                                // Add private_contents count K.Matsuo 2013/05/07 --start--
                                // 編集中に非公開コンテンツ数を増やしているので公開コンテンツの場合非公開コンテンツ数を減らす
                                $result = $this->deletePrivateContents($indice[$ii]['index_id']);
                                if($result === false){
                                    $tmpErrorMsg = $this->Db->ErrorMsg();
                                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                                    $exception = new AppException($tmpErrorMsg);
                                    $exception->addError($tmpErrorMsg);
                                    throw $exception;
                                }
                                // Add private_contents count K.Matsuo 2013/05/07 --end--
                            }
                        }
                    }
                }
            }
            
            // 新着情報に追加
            if($whatsnew_flag){
                $result = $this->addWhatsnew($item_id, $item_no);
                if($result === false){
                    $tmpErrorMsg = $this->Db->ErrorMsg();
                    $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
                    $exception = new AppException($tmpErrorMsg);
                    $exception->addError($tmpErrorMsg);
                    throw $exception;
                }
            }
            // Add count contents and add whatsnew 2009/02/17 A.Suzuki --end--
            
            // Add review mail setting 2009/09/24 Y.Nakao --start--
            // 新規査読アイテム登録メール送信処理
            if($review_flg == 1){
                // 査読を行う
                if($review_mail_flg == 1){
                    // 言語リソース取得
                    $smartyAssign = $this->Session->getParameter("smartyAssign");
                    // send review mail
                    // 査読通知メールを送信する
                    // 件名
                    // set subject
                    $subj = $smartyAssign->getLang("repository_mail_review_subject");
                    $this->mailMain->setSubject($subj);
                    
                    // page_idおよびblock_idを取得
                    $block_info = $this->getBlockPageId();
                    // メール本文をリソースから読み込む
                    // set Mail body
                    $body = '';
                    $body .= $smartyAssign->getLang("repository_mail_review_body")."\n\n";
                    $body .= $smartyAssign->getLang("repository_mail_review_contents")."\n";
                    $body .= $smartyAssign->getLang("repository_mail_review_title");
                    if($this->Session->getParameter("_lang") == "japanese"){
                        if(strlen($base_attr['title']) > 0){
                            $body .= $base_attr['title'];
                        } else if(strlen($base_attr['title_english']) > 0){
                            $body .= $base_attr['title_english'];
                        } else {
                            $body .= "no title";
                        }
                    } else {
                        if(strlen($base_attr['title_english']) > 0){
                            $body .= $base_attr['title_english'];
                        } else if(strlen($base_attr['title']) > 0){
                            $body .= $base_attr['title'];
                        } else {
                            $body .= "no title";
                        }
                    }
                    
                    $body .= "\n";
                    $body .= $smartyAssign->getLang("repository_mail_review_detailurl").$detail_uri."\n";
                    $body .= "\n";
                    $body .= $smartyAssign->getLang("repository_mail_review_reviewurl")."\n";
                    $body .= BASE_URL;
                    if(substr(BASE_URL,-1,1) != "/"){
                        $body .= "/";
                    }
                    $body .= "?active_action=repository_view_edit_review&page_id=".$block_info["page_id"]."&block_id=".$block_info["block_id"];
                    $body .= "\n\n".$smartyAssign->getLang("repository_mail_review_close");
                    $this->mailMain->setBody($body);
                    // ---------------------------------------------
                    // 送信メール情報取得
                    //   送信者のメールアドレス
                    //   送り主の名前
                    //   送信先ユーザを取得
                    // create mail body
                    //   get send from user mail address
                    //   get send from user name
                    //   get send to user
                    // ---------------------------------------------
                    $users = array();
                    $this->getReviewMailInfo($users);
                    // ---------------------------------------------
                    // 送信先を設定
                    // set send to user
                    // ---------------------------------------------
                    // 送信ユーザを設定
                    // $usersの中身
                    // $users["email"] : 送信先メールアドレス
                    // $user["handle"] : ハンドルネーム
                    //                   なければ空白が自動設定される
                    // $user["type"]   : type (html(email) or text(mobile_email))
                    //                   なければhtmlが自動設定される
                    // $user["lang_dirname"] : 言語
                    //                         なければ現在の選択言語が自動設定される
                    $this->mailMain->setToUsers($users);
                    
                    // ---------------------------------------------
                    // メール送信
                    // send confirm mail
                    // ---------------------------------------------
                    if(count($users) > 0){
                        // 送信者がいる場合は送信
                        $return = $this->mailMain->send();
                    }
                     
                    // 言語リソース開放
                    $this->Session->removeParameter("smartyAssign");
                    
                }
            }
            // Add review mail setting 2009/09/24 Y.Nakao --end--
        }
        $this->Session->removeParameter('item_entry_flg');
        
        // Add new supple item regist 2009/09/01 A.Suzuki --start--
        // このアイテムのWEKOIDを取得
        if($this->Session->getParameter("add_supple_flag") == "true"){
            // Bug Fix WEKO-2014-063 2014/08/07 T.Koyasu --start--
            if(!isset($repositoryHandleManager))
            {
                $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->accessDate);
            }
            $weko_id = $repositoryHandleManager->getSuffix($item_id, $item_no, RepositoryHandleManager::ID_Y_HANDLE);
            if(strlen($weko_id) == 0)
            {
                $this->Session->setParameter("add_supple_flag", "false");
            }
            // Bug Fix WEKO-2014-063 2014/08/07 T.Koyasu --end--
        }
        // Add new supple item regist 2009/09/01 A.Suzuki --end--
        // Add detail search 2013/11/21 K.Matsuo --start--
        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->updateSearchTableForItem($item_id, $item_no);
        // Add detail search 2013/11/21 K.Matsuo --end--
        //セッションの初期化
        $this->Session->removeParameter("edit_flag");
        $this->Session->removeParameter("edit_item_id");
        $this->Session->removeParameter("edit_item_no");
        $this->Session->removeParameter("edit_start_date");
        $this->Session->removeParameter("delete_file_list");
        $this->Session->removeParameter('item_pub_date');
        $this->Session->removeParameter('item_keyword');
        $this->Session->removeParameter('item_keyword_english');
        $this->Session->removeParameter("item_type_all");
        $this->Session->removeParameter("item_attr_type");
        $this->Session->removeParameter("item_num_cand");
        $this->Session->removeParameter("option_data");
        $this->Session->removeParameter("isfile");
        $this->Session->removeParameter("item_num_attr");
        $this->Session->removeParameter("item_attr");
        $this->Session->removeParameter("base_attr");
        $this->Session->removeParameter("indice");
        $this->Session->removeParameter("link");
        $this->Session->removeParameter("link_search");
        $this->Session->removeParameter("link_searchkeyword");
        $this->Session->removeParameter("link_searchtype");
        $this->Session->removeParameter("open_node_index_id_link");
        $this->Session->removeParameter("open_node_index_id_index");
        $this->Session->removeParameter("license_master");
        $this->Session->removeParameter("error_msg");
        $this->Session->removeParameter("warning");
        
        // Add e-person 2013/11/20 R.Matsuura --start--
        $this->Session->removeParameter("feedback_mailaddress_str");
        $this->Session->removeParameter("feedback_mailaddress_author_str");
        $this->Session->removeParameter("feedback_mailaddress_array");
        $this->Session->removeParameter("feedback_mailaddress_author_array");
        // Add e-person 2013/11/20 R.Matsuura --end--
        
        $this->Session->removeParameter("all_group");
        $this->Session->removeParameter("user_group");
        
        // change index tree 2008/12/03 Y.Nakao --start--
        $this->Session->removeParameter("view_open_node_index_id_insert_item");
        $this->Session->removeParameter("view_open_node_index_id_item_link");
        // change index tree 2008/12/03 Y.Nakao --end--
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --start--
        $this->Session->removeParameter(RepositoryConst::SESSION_PARAM_ORG_CONTRIBUTOR_USER_ID);
        $this->Session->removeParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_USER_ID);
        $this->Session->removeParameter(RepositoryConst::SESSION_PARAM_ITEM_CONTRIBUTOR);
        $this->Session->removeParameter(RepositoryConst::SESSION_PARAM_CONTRIBUTOR_ERROR_MSG);
        // Add Contributor(Posted agency) A.Suzuki 2011/12/13 --end--
        
        $this->Session->removeParameter("edit_jalc_flag");
        
        if($cover_error_flg === "true" || $flash_error_flg === "true"){
            // 詳細画面を表示させるための設定
            $this->Session->setParameter("item_id_for_detail", $this->Session->getParameter("edit_item_id"));
            $this->Session->setParameter("item_no_for_detail", $this->Session->getParameter("edit_item_no"));
            $this->Session->setParameter("search_flg","true");
            $this->Session->setParameter("workflow_flg", "false");
            $this->Session->setParameter("serach_screen", "1");
            $this->Session->setParameter("redirect_flg", "detail");
            $this->Session->setParameter("redirect_item_id", $item_id);
            
            // Add PDF cover page 2012/06/15 A.Suzuki --start--
            // PDF Cover create failed flag
            if($cover_error_flg == "true"){
                $this->Session->setParameter("cover_error", $cover_error);
            }
            // Add PDF cover page 2012/06/15 A.Suzuki --end--
            
            // Flash変換失敗を示すフラグ
            if($flash_error_flg == "true"){
                $this->Session->setParameter("flash_error", $flash_error);
            }
            
            // Session情報開放
            $this->Session->removeParameter("edit_flag");
            $this->Session->removeParameter("edit_item_id");
            $this->Session->removeParameter("edit_item_no");
            $this->Session->removeParameter("return_screen");
            $this->Session->removeParameter("add_supple_flag");
            $this->Session->removeParameter("item_id_for_detail");
            $this->Session->removeParameter("item_no_for_detail");
        } else {
            if($this->Session->getParameter("return_screen") != null){
                // 詳細画面を表示させるための設定
                $this->Session->setParameter("item_id_for_detail", $this->Session->getParameter("edit_item_id"));
                $this->Session->setParameter("item_no_for_detail", $this->Session->getParameter("edit_item_no"));
                $this->Session->setParameter("search_flg","true");
                // Session情報開放
                $this->Session->removeParameter("edit_flag");
                $this->Session->removeParameter("edit_item_id");
                $this->Session->removeParameter("edit_item_no");
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
                $this->Session->removeParameter("edit_flag");
                $this->Session->removeParameter("edit_item_id");
                $this->Session->removeParameter("edit_item_no");
                $this->Session->removeParameter("error_msg");
                $this->Session->setParameter("redirect_flg", "selecttype");
            }
            
            // Add new supple item regist 2009/09/01 A.Suzuki --start--
            if($this->Session->getParameter("add_supple_flag") == "true"){
                $this->Session->setParameter("supple_weko_id", $weko_id);
                $this->Session->setParameter("redirect_flg", "supple");
                $this->Session->removeParameter("item_id_for_detail");
                $this->Session->removeParameter("item_no_for_detail");
                $this->Session->removeParameter("search_flg","true");
            }
            // Add new supple item regist 2009/09/01 A.Suzuki --end--
        }
        
        return $returnString;
    }
    
    /**
     * [[機能説明]]
     * 年月日のデータ(int)を日付文字列にして返す
     */
    private function generateDateStr($year, $month, $day){
        $str_year = strval($year);
        $str_month = strval($month);
        $str_day = strval($day);
        // 0付加
        if(intval($month)<10){ $str_month = '0' . $str_month; }
        if(intval($day)<10){ $str_day = '0' . $str_day; }
        // 結合
        return $str_year . '-' . $str_month . '-' . $str_day . ' ' . '00:00:00.000';
    }
}
?>
