<?php
// --------------------------------------------------------------------
//
// $Id: List.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR.'/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/RepositorySearch.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Export_List extends RepositoryAction
{
    // リクエストパラメタ
    var $check_flg = null;      // チェックしたものをExport->true,表示中すべて->false
    var $export_check = null;   // チェックボックスのチェック状態
    var $all_flg = null;        // 全てのアイテムをエクスポート
    
    // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
    const EXPORT_FILE_ON = 1;
    private $RepositoryValidator = null;
    // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        try {
            // 共通の初期処理
            $result = $this->initAction();
            if ( $result == false ){
                // 未実装
                print "初期処理でエラー発生";
            }
            
            // get user authority
            $user_auth_id = $this->Session->getParameter("_user_auth_id");
            $auth_id = $this->getRoomAuthorityID();
            $user_id = $this->Session->getParameter("_user_id");
            
            // Fix admin or insert user export action. 2013/05/22 Y.Nakao --start--
            $adminUser = false;
            if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room){
                $adminUser = true;
            }
            
            // repository_param is ecport file flag.
            $isExportFile = 1;
            $this->getAdminParam('export_is_include_files', $isExportFile, $errorMsg);
            
            // file download validator class
            require_once WEBAPP_DIR.'/modules/repository/validator/Validator_DownloadCheck.class.php';
            $this->RepositoryValidator = new Repository_Validator_DownloadCheck();
            $initResult = $this->RepositoryValidator->setComponents($this->Session, $this->Db);
            if($initResult === 'error'){
                return 'error';
            }
            
            $file_flg = false;
            $item_info = array();
            $file_size_all = 0;
            $size_over = false;
            $count_over = false;
            $max_export_count = 0;
            
            // get search result
            $repositorySearch = new RepositorySearch();
            $repositorySearch->Db = $this->Db;
            $repositorySearch->Session = $this->Session;
            // set search request from referrer
            $repositorySearch->setRequestParameterFromReferrer();
            if((!isset($repositorySearch->index_id) || $repositorySearch->index_id == "" || $repositorySearch->index_id == "0") && count($repositorySearch->search_term) == 0)
            {
                $repositorySearch->setDefaultSearchParameter();
            }
            if($this->all_flg == "true")
            {
                $repositorySearch->listResords = "all";
            }
            $searchResult = $repositorySearch->search();
            
            // Add Advanced Search 2013/11/26 R.Matsuura --start--
            $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
            // Add Advanced Search 2013/11/26 R.Matsuura --end--
            
            $cntCheck = 0;
            for($ii=0; $ii<count($searchResult); $ii++)
            {
                // チェックしたアイテムをエクスポートする場合、対象かどうかをチェックする
                if($this->check_flg == "true" && (!isset($this->export_check[$cntCheck]) || $ii!=$this->export_check[$cntCheck]))
                {
                    // 対象ではないためスルー
                    $this->Session->setParameter("export_check", "true");
                    continue;
                }
                $cntCheck++;
                
                // エクスポート対象である
                
                // get item data
                $itemData = array();
                $this->getItemData($searchResult[$ii]['item_id'],     // change getItemTableData into getItemResult 2013/09/13 K.Matsushita
                                   $searchResult[$ii]['item_no'], 
                                   $itemData, 
                                   $errMsg);
                // check insert user
                $insUser = false;
                if($user_id == $itemData['item'][0]['ins_user_id'])
                {
                    $insUser = true;
                }
                
                if($adminUser || $insUser || $itemAuthorityManager->checkItemPublicFlg($itemData['item'][0]["item_id"], $itemData['item'][0]["item_no"], $this->repository_admin_base, $this->repository_admin_room))
                {
                    // 管理者 または 登録者 または 公開済 のアイテムの場合はエクスポートできる
                    $export_flg = true;
                    
                    // ファイルがエクスポートできるかチェックする
                    if($adminUser || $insUser || $isExportFile == self::EXPORT_FILE_ON)
                    {
                        // 管理者 または 登録者 またはファイルをエクスポートする設定となっている場合、ファイルがエクスポートできる
                        $file_flg = true;
                        
                        // ファイル容量をチェックする
                        // ファイルがエクスポートできるので容量をチェックする
                        $file_size = 0;
                        if(isset($itemData["item_attr"]))
                        {
                            $file_size = $this->getFileSize($itemData["item_attr_type"], $itemData["item_attr"]);    // change $Result_List into $itemData 2013/09/13 K.Matsushita
                        }
                        // 100件 or 100MBの精査も行う。
                        if(($file_size_all+$file_size)>=100000000 && $size_over!=true){
                            $size_over = true;
                            $max_export_count = count($item_info);
                        }
                        $file_size_all = $file_size_all+$file_size;
                        
                    }
                    // エクスポート個数をチェック
                    if((count($item_info)+1)>100 && $count_over!=true){
                        $count_over = true;
                        $max_export_count = count($item_info);
                    }
                    
                    if(!$size_over && !$count_over)
                    {
                        // 容量制限内のため、エクスポートデータとして以下のデータを格納する
                        //   item_id
                        //   item_no
                        //   title
                        //   title_english
                        //   export_flg
                        array_push($item_info, array('item_id' => $itemData['item'][0]["item_id"], 
                                                     'item_no' => $itemData['item'][0]["item_no"], 
                                                     'title' => $itemData['item'][0]['title'], 
                                                     'title_english' => $itemData['item'][0]['title_english'], 
                                                     'export_flg' => true));
                    }
                }
            }
            $this->Session->setParameter("file_flg", $file_flg);
            $this->Session->setParameter("item_info", $item_info);
            $this->Session->setParameter("item_info_count",count($item_info));
            $this->Session->setParameter("size_over", $size_over);
            $this->Session->setParameter("count_over", $count_over);
            $this->Session->setParameter("max_export_count", $max_export_count);
            // Fix admin or insert user export action. 2013/05/22 Y.Nakao --end--
            
            // Export画面の戻り先指定フラグ nakao 2008/03/14
            // Export画面にて戻る処理を指定するSession情報を開放する
            // 開放されない場合、戻ると一覧表示へ飛んでしまう
            $this->Session->removeParameter("export_print");
            $this->Session->setParameter("export_print", null);
            // ここまで 2008/03/14
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                // 未実装
                print "終了処理失敗";
            }
            
            return 'success';
        }
        catch ( RepositoryException $exception ) {
            // 未実装
        }
    }
    
    /**
     * count file size
     *
     * @param array $itemAttrType
     * @param array $itemAttr
     */
    private function getFileSize($itemAttrType, $itemAttr)
    {
        // get file save path
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // check directory exists 
        if( !(file_exists($contents_path)) ){
            $errMsg = 'Not exists file save point.';
            return 0;
        }
        
        $file_size = 0;
        for($jj=0;$jj<count($itemAttrType);$jj++)
        {
            if($itemAttrType[$jj]["input_type"] == "file" || $itemAttrType[$jj]["input_type"] == "file_price")    // Add [$jj] to $itemAttrType  2013/09/13 K.Matsushita
            {
                for($kk=0;$kk<count($itemAttr[$jj]);$kk++)    // Add [$jj] to $itemAttr 2013/09/13 K.Matsushita
                {
                    // ファイルのダウンロード可否チェックもここで。
                    //  ・管理者
                    //   ⇒ ファイルも全てダウンロード可
                    //  ・一般ユーザー
                    //   ⇒ 「$file_flg == true」 かつ ダウンロード可なファイルのみ。
                    
                    // check file access
                    $status = $this->RepositoryValidator->checkFileAccessStatus($itemAttr[$jj][$kk]);    // change $validator into $this->RepositoryValidator 2013/09/13 K.Matsushita
                    if( $status == "free" || $status == "already" || $status == "admin" || $status == "license" )
                    {
                        // this file use can download
                        $file_path = $contents_path.DIRECTORY_SEPARATOR.
                                     $itemAttr[$jj][$kk]['item_id'].'_'.
                                     $itemAttr[$jj][$kk]['attribute_id'].'_'.
                                     $itemAttr[$jj][$kk]['file_no'].'.'.
                                     $itemAttr[$jj][$kk]['extension'];
                        $size = filesize($file_path);
                        if(is_numeric($size)){
                            $file_size += $size;
                        }
                    }
                }
            }
        }
        return $file_size;
    }
}
?>
