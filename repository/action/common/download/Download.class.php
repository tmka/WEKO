<?php
// --------------------------------------------------------------------
//
// $Id: Download.class.php 58644 2015-10-10 08:01:18Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDownload.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Download extends RepositoryAction
{
    // リクエストパラメータ
    var $item_id = null;
    var $item_no = null;
    var $attribute_id = null;
    var $file_no = null;
    var $img = null;    // true設定時にサムネイル表示
    
    var $item_type_id = null; // アイテムタイプアイコン追加　 2008/07/16 Y.Nakao
    var $file_prev = null;    // サムネイル追加 2008/07/22 Y.Nakao
    var $flash = null;        // フラッシュ追加 2010/01/05 A.Suzuki
    
    public $block_id = "";  // ブロックID追加2011/10/13 K.Matsuo
    public $page_id = "";   // ページID追加2011/10/13 K.Matsuo
    
    // オブジェクト類
    var $uploadsView = null;
    var $Session = null;
    
    // Add index thumbnail 2010/08/11 Y.Nakao --start--
    public $index_id = null;
    // Add index thumbnail 2010/08/11 Y.Nakao --end--
    
    public $flashpath = null;
    
    // Add PDF cover page 2012/06/13 A.Suzuki --start--
    public $pdf_cover_header = null;
    // Add PDF cover page 2012/06/13 A.Suzuki --end--
    
    // Add advanced image thubnail 2014/02/13 R.Matsurua --start--
    public $image_slide = null;
    // Add advanced image thubnail 2014/02/13 R.Matsurua --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        // 正常終了フラグを有効にする（異常終了の場合は例外で脱出する）
        $this->exitFlag = true;
        
        /////////////// download and view item type icon ///////////////
        // Add item type icon download 2008/07/16 Y.Nakao --start--
        if($this->item_type_id != null){
            $this->getItemTypeIcom();
            return;
        }
        // Add item type icon download 2008/07/16 Y.Nakao --end--
        
        // Add index thumbnail 2010/08/11 Y.Nakao --start--
        if($this->index_id != null){
            $this->getIndexThumbnail();
            return;
        }
        // Add index thumbnail 2010/08/11 Y.Nakao --start--
        
        // Add PDF cover page 2012/06/13 A.Suzuki --start--
        if($this->pdf_cover_header != null && $this->pdf_cover_header == "true"){
            $this->getPdfCoverImage();
            return;
        }
        // Add PDF cover page 2012/06/13 A.Suzuki --end--
        
        // request param check
        if (!is_numeric($this->item_id) || $this->item_id < 1 || !is_numeric($this->item_no) || $this->item_no < 1 ||
            !is_numeric($this->attribute_id) || $this->attribute_id < 1 || !is_numeric($this->file_no) || $this->file_no < 1) {
            $error_msg = "Invalid request parameter";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        /////////////// view prev ///////////////
        // Add prev 2008/07/22 Y.Nakao --start--
        if($this->file_prev == "true")
        {
            $this->getFilePreview();
            return;
        }       
        // Add prev 20008/07/22 Y.Nakao --end--
        
        /////////////// download thumbnail ///////////////
        if ($this->img == "true")
        {
            $this->getThumbnail();
            return;
        }
        
        
        // Add fileDownload after login 2011/10/12 K.Matsuo  
        $this->Session->removeParameter('repository'.$this->block_id.'FileDownloadKey');
        // ### Add for Test K.Matsuo 2011/11/1
        $this->Session->removeParameter("testPay");
        
        /////////////// view flash ///////////////
        // Add flash 2010/01/05 A.Suzuki --start--
        if($this->flash == "true"){
            // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
            $flashDir = $this->getFlashFolder();
            if(strlen($flashDir) > 0 && file_exists($flashDir)){
                $flashPath = $flashDir.DIRECTORY_SEPARATOR.$this->flashpath;
                if( file_exists($flashPath) ){
                    $mimetype = "";
                    if( preg_match("/*\.flv$/", $this->flashpath) === 1 )
                    {
                        $mimetype = "video/x-flv";
                    }
                    else
                    {
                        $mimetype = "application/x-shockwave-flash";
                    }
                    $repositoryDownload = new RepositoryDownload();
                    $repositoryDownload->downloadFile($flashPath, $this->flashpath, $mimetype);
                } else {
                    header("HTTP/1.0 404 Not Found");
                }
            } else {
                header("HTTP/1.0 404 Not Found");
            }
            // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--
            return;
        }
        // Add flash 2010/01/05 A.Suzuki --end--
        
        ///// file download /////
        $query = "SELECT extension, file_name, mime_type, pub_date, ins_user_id ".
                 "FROM ". DATABASE_PREFIX ."repository_file ".
                 "WHERE item_id = ? ".
                 "  AND item_no = ? ".
                 "  AND attribute_id = ? ".
                 "  AND file_no = ? ".
                 "  AND is_delete = ?; ";
        $params = array();
        $params[] = $this->item_id;
        $params[] = $this->item_no;
        $params[] = $this->attribute_id;
        $params[] = $this->file_no;
        $params[] = 0;
        
        $ret = $this->Db->execute($query, $params);
        
        // SQLエラーの場合 終了
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 検索結果が一件でない場合 終了
        if (count($ret) != 1) {
            $block_info = $this->getBlockPageId();
            $redirect_url = BASE_URL.
                            "/?action=pages_view_main".
                            "&active_action=repository_view_main_item_snippet".
                            "&page_id=". $block_info["page_id"].
                            "&block_id=". $block_info["block_id"];
            // redirect
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: ".$redirect_url);
            
            return;
        }
        // Download check
        
        // Add delete pdf cover 2015/01/27 K.Matsushita -start-
        // get temporary directory path for pdfCover instance
        $tmpDirPath = $this->getDefaltTmpDirPath();
        
        $user_id = $this->Session->getParameter("_user_id");
        
        $this->infoLog("businessPdfcover", __FILE__, __CLASS__, __LINE__);
        $pdfCover = BusinessFactory::getFactory()->getBusiness("businessPdfcover");
        if($ret[0]['extension'] == "pdf"){
            // create pdf cover page
            $download_file_path = "";
            $download_file_path = $pdfCover->grantPdfCover($this->item_id, $this->item_no, $this->attribute_id, $this->file_no, $tmpDirPath);
            if(file_exists($download_file_path) === TRUE){
                $repositoryDownload = new RepositoryDownload();
                $repositoryDownload->downloadFile(
                        $download_file_path,
                        $ret[0]['file_name'],
                        $ret[0]['mime_type']
                );
            }
        }
        else 
        {
            // 一時ディレクトリにfilesからコピー
            $copy_path = $this->getCopyFilePath($ret[0]['extension'], $tmpDirPath);
            if($copy_path === false) {
                $error_msg = "Download file is not exist";
                $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
                throw new AppException($error_msg);
            }
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
            $repositoryDownload = new RepositoryDownload();
            $repositoryDownload->downloadFile($copy_path, $ret[0]['file_name'], $ret[0]['mime_type']);
            // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        }
        // Add delete pdf cover 2015/01/27 K.Matsushita -end-
        
        if($this->image_slide == null)
        {
            // Mod entryLog T.Koyasu 2015/03/06 --start--
            $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
            $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
            $logManager->entryLogForDownload($this->item_id, $this->item_no, $this->attribute_id, $this->file_no);
            // Mod entryLog T.Koyasu 2015/03/06 --end--
        }
        
        // 終了処理
        return;
    }
    
    // アイテムタイプアイコン追加 2008/07/16 Y.Nakao --start--
    /**
     * アイテムタイプ種類を示すアイコン情報をDBからダウンロードする
     *
     */
    private function getItemTypeIcom(){
        $query = "SELECT icon, icon_name, icon_mime_type ".
                 "FROM ". DATABASE_PREFIX ."repository_item_type ".
                 "WHERE item_type_id  = ?; ";
        $params = null;
        $params[] = $this->item_type_id;
        
        $ret = $this->Db->execute($query, $params);
        // SQLエラーの場合 終了
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 検索結果が一件でない場合 終了
        if (count($ret) != 1) {
            $error_msg = "Invalid result : SELECT item type icon num";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download($ret[0]['icon'],$ret[0]['icon_name'],$ret[0]['icon_mime_type']);
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        
        return;
    }
    // アイテムタイプアイコン追加 2008/07/16 Y.Nakao --end--
    
    // Add index thumbnail 2010/08/11 Y.Nakao --start--
    private function getIndexThumbnail(){
        $query = "SELECT thumbnail, thumbnail_name, thumbnail_mime_type  ".
                 "FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE index_id = ? ".
                 " AND is_delete = ?; ";
        $params = null;
        $params[] = $this->index_id;
        $params[] = 0;
        
        $ret = $this->Db->execute($query, $params);
        // SQLエラーの場合 終了
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 検索結果が一件でない場合 終了
        if (count($ret) != 1) {
            $error_msg = "Invalid result : SELECT index thumbnail num";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download($ret[0]['thumbnail'],$ret[0]['thumbnail_name'],$ret[0]['thumbnail_mime_type']);
        
        return;
        
    }
    // Add index thumbnail 2010/08/11 Y.Nakao --end--
    
    // Add PDF cover page 2012/06/13 A.Suzuki --start--
    private function getPdfCoverImage(){
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE.", ".
                 RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT.", ".
                 RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE." ".
                 "FROM ". DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PDF_COVER_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME." = ?; ";
        $params = array();
        $params[] = RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE;
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // 検索結果が一件でない場合 終了
        if(count($ret) != 1) {
            $error_msg = "Invalid result : SELECT pdf cover image num";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download(
            $ret[0][RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE],
            $ret[0][RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT],
            $ret[0][DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE]);
        
        return;
        
    }
    // Add PDF cover page 2012/06/13 A.Suzuki --end--
    
    private function getFilePreview()
    {
        $query = "SELECT file_prev, file_prev_name ";
        $query .= "FROM ". DATABASE_PREFIX ."repository_file ";
        $query .= "WHERE item_id = ? ".
                  "  AND item_no = ? ".
                  "  AND attribute_id = ? ".
                  "  AND file_no = ? ";
        $params[] = $this->item_id;
        $params[] = $this->item_no;
        $params[] = $this->attribute_id;
        $params[] = $this->file_no;
        $ret = $this->Db->execute($query, $params);
        // Error check
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // if select result is 0 then this action end
        if (count($ret) != 1) {
            $error_msg = "Invalid result : SELECT file prev num";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download($ret[0]['file_prev'], $ret[0]['file_prev_name']);
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        
        return;
    }
    
    private function getThumbnail()
    {
        $query = "SELECT file, file_name, mime_type ".
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail ".
                 "WHERE item_id = ? ".
                 "  AND item_no = ? ".
                 "  AND attribute_id = ? ".
                 "  AND file_no = ? ";
        $params = array();
        $params[] = $this->item_id;
        $params[] = $this->item_no;
        $params[] = $this->attribute_id;
        $params[] = $this->file_no;
        $ret = $this->Db->execute($query, $params);
        // Error check
        if ($ret === false) {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        // if select result is 0 then this action end
        if (count($ret) != 1) {
            $error_msg = "Invalid result : SELECT thumbnail num";
            $this->errorLog($error_msg, __FILE__, __CLASS__, __LINE__);
            throw new AppException($error_msg);
        }
        
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --start--
        $repositoryDownload = new RepositoryDownload();
        $repositoryDownload->download($ret[0]['file'],$ret[0]['file_name'],$ret[0]['mime_type']);
        // Add RepositoryDownload action 2010/03/30 A.Suzuki --end--
        
        return;
    }
    
    // Add delete pdf cover 2015/01/27 K.Matsushita -start-
    /**
     * Get defaut temporary directory path
     *
     * @return string
     */
    private function getDefaltTmpDirPath()
    {
        $tmpDirPath = "";
        
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness("businessWorkdirectory");
        
        $tmpDirPath = $businessWorkdirectory->create();
        
        return $tmpDirPath;
    }
    
    /**
     * Get copy path of download file 
     * 
     * @param string $extension
     * @param string $tmpDirPath
     * @return string
     */
    private function getCopyFilePath($extension, $tmpDirPath){
        
        // throw file contents for user
        // Add separate file from DB 2009/04/21 Y.Nakao --start--
        // get contents save path
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // check directory exists
        if( !(file_exists($contents_path)) ){
            return false;
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --end--
        
        $file_path = $contents_path.DIRECTORY_SEPARATOR.
        $this->item_id.'_'.
        $this->attribute_id.'_'.
        $this->file_no.'.'.
        $extension;
        // check file exists
        if( !(file_exists($file_path)) ){
            return false;
        }
        $copy_path = $tmpDirPath.
                $this->item_id.'_'.
                $this->attribute_id.'_'.
                $this->file_no.'.'.
                $extension;
        // コンテンツ本体コピー
        copy($file_path, $copy_path);
        
        return $copy_path;
    }
    
    
    // Add delete pdf cover 2015/01/27 K.Matsushita -end-
}
?>
