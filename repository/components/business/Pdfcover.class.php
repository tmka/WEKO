<?php
// --------------------------------------------------------------------
//
// $Id: Pdfcover.class.php 57131 2015-08-26 05:16:29Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/Fileprocess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Fpdf.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/LockPhysicalFile.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDatabaseConst.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

class Repository_Components_business_Pdfcover extends BusinessBase
{
    /**
     * RepositoryAction class instance
     *
     * @var RepositoryAction
     * @access private
     */
    private $repositoryAction = null;
    
    private $errormsg = "";
    
    // -------------------------------------------------------
    // Const
    // -------------------------------------------------------
    const PDF_NAME_TARGET = "target.pdf";
    const PDF_NAME_COVER = "cover.pdf";
    const PDF_NAME_COMBINED = "combined.pdf";
    const PDF_NAME_ORG_TARGET = "org_target.pdf";
    
    // Error message
    const ERR_CANNOT_CREATE = "Could not create PDF cover page.";
    const ERR_CANNOT_DELETE = "Could not delete PDF cover page.";
    const RESET_CREATED_FLG = 0;
    
    // PDF cover page delete status
    const DELETE_COVER_SUCCESS = 0;
    const DELETE_COVER_FAILED = 1;
    const DELETE_COVER_CANNCEL = 2;
    
    
    
    // -------------------------------------------------------
    // Constructor
    // -------------------------------------------------------
    /**
     * Constructor
     *
     * @param Session $Session
     * @param Db $Db
     * @param string $TransStartDate
     */
    protected function onInitialize()
    {
        // Create RepositoryAction class instance
        $this->infoLog("Session", __FILE__, __CLASS__, __LINE__);
        $container = & DIContainerFactory::getContainer();
        $Session = $container->getComponent("Session");
        
        $this->repositoryAction = new RepositoryAction();
        $this->repositoryAction->Session = $Session;
        $this->repositoryAction->Db = $this->Db;
        $this->repositoryAction->TransStartDate = $this->accessDate;
    }
    
    // -------------------------------------------------------
    // Setter And Getter
    // -------------------------------------------------------
    /**
     * Getter for errorMsg
     *
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errormsg;
    }
    
    // -------------------------------------------------------
    // PUBLIC
    // -------------------------------------------------------
    /**
     * Execute create PDF cover page
     *
     * @return bool
     * @access public
     */
    public function grantPdfCover($itemId, $itemNo, $attributeId, $fileNo, $workDir)
    {
        $this->debugLog("businessPdfover", __FILE__, __CLASS__, __LINE__);
        
        // Delete created cover page
        $workFile = "";
        $result = $this->deleteCoverPage($itemId, $itemNo, $attributeId, $fileNo, $workDir, $workFile);
        if($result == self::DELETE_COVER_FAILED) {
            $this->debugLog("[".__FUNCTION__."]"." do not execute create cover page", __FILE__, __CLASS__, __LINE__);
            return $workFile;
        }
        
        // check create flag
        if(!$this->checkCreatePdfCover($itemId, $itemNo)) {
            $this->debugLog("[".__FUNCTION__."]"." do not create cover page", __FILE__, __CLASS__, __LINE__);
            return $workFile;
        }
        $this->debugLog("[".__FUNCTION__."]"." Start create cover page", __FILE__, __CLASS__, __LINE__);
        
        // Make cover page
        $coverFile = $workDir.self::PDF_NAME_COVER;
        if(!$this->createCoverPage($coverFile, $itemId, $itemNo, $attributeId, $fileNo, $workDir)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed create cover page", __FILE__, __CLASS__, __LINE__);
            $this->errormsg = self::ERR_CANNOT_CREATE;
            return $workFile;
        }
        $this->debugLog("[".__FUNCTION__."]"." Success make cover page", __FILE__, __CLASS__, __LINE__);
        
        // Combine PDF pages
        $combineFile = $workDir.self::PDF_NAME_COMBINED;
        if(!$this->combinePdf($workFile, $coverFile, $combineFile)) {
            $this->errorLog("[".__FUNCTION__."]"."Failed combine cover page", __FILE__, __CLASS__, __LINE__);
            $this->errormsg = self::ERR_CANNOT_CREATE;
            return $workFile;
        }
        $this->debugLog("[".__FUNCTION__."]"." Success grant cover page", __FILE__, __CLASS__, __LINE__);
        
        return $combineFile;
    }
    
    /**
     * delete cover page
     * @return string
     */
    public function deleteCoverPage($itemId, $itemNo, $attributeId, $fileNo, $workDir, &$workFile)
    {
        // 論文ファイル名
        $targetFilePath = $this->getTargetPdfFilePath($itemId, $attributeId, $fileNo);
        // 作業用一時ファイル名
        $workFile = $workDir.self::PDF_NAME_TARGET;
        
        // 削除処理が実行されない場合はfilesからそのままコピーして終了する
        $result = $this->deleteCoverPageInternal($itemId, $itemNo, $attributeId, $fileNo, $workDir, $workFile, $targetFilePath);
        if($result == self::DELETE_COVER_CANNCEL) {
            // files内の論文ファイルをコピーしてそのパスを返す
            // 失敗した場合例外を投げる
            if(!copy($targetFilePath, $workFile)) {
                $this->errorLog("[".__FUNCTION__."]"." Failed copy from files/", __FILE__, __CLASS__, __LINE__);
                throw new AppException($targetFilePath."copy failed.");
            }
            $this->debugLog("[".__FUNCTION__."]"." ".$itemId."_".$attributeId."_".$fileNo.".pdf copy to work directory.", __FILE__, __CLASS__, __LINE__);
        }
        
        return $result;
    }
    
    /**
     * delete cover page
     * @return string
     */
    private function deleteCoverPageInternal($itemId, $itemNo, $attributeId, $fileNo, $workDir, $workFile, $targetFilePath)
    {
        // Check PDFTK
        $pdftkCmd = $this->getCmdPdftk();
        if(!$pdftkCmd) {
            $this->debugLog("[".__FUNCTION__."]"." Not exist PDFTK", __FILE__, __CLASS__, __LINE__);
            return self::DELETE_COVER_CANNCEL;
        }
        $this->debugLog("[".__FUNCTION__."]"." find PDFTK", __FILE__, __CLASS__, __LINE__);
        
        // 削除するカバーページの状態を取得
        if($this->getCoverDeleteStatus($itemId, $itemNo, $attributeId, $fileNo) == RepositoryDatabaseConst::COVER_DELETE_STATUS_DONE || 
           $this->getPdfCoverPageNum($itemId, $itemNo, $attributeId, $fileNo) == 0) {
            $this->debugLog("[".__FUNCTION__."]"." Not exist PDF cover page", __FILE__, __CLASS__, __LINE__);
            return self::DELETE_COVER_CANNCEL;
        }
        $this->debugLog("[".__FUNCTION__."]"." Start deletecover page ".$itemId."_".$attributeId."_".$fileNo.".pdf", __FILE__, __CLASS__, __LINE__);
        
        // ロックを取得する
        $lockPhysicalFile = new Repository_Components_LockPhysicalFile();
        
        $this->debugLog("[".__FUNCTION__."]"." [Lock] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        $lockHandle = $lockPhysicalFile->lockFile($itemId, $attributeId, $fileNo);
        if($lockHandle === null) {
            // ロックの取得に失敗した場合終了する
            $this->errorLog("[".__FUNCTION__."]"." [Failed file lock] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            return self::DELETE_COVER_CANNCEL;
        }
        $this->debugLog("[".__FUNCTION__."]"." [Lock got] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        $result = $this->deleteCoverExecute($pdftkCmd, $itemId, $itemNo, $attributeId, $fileNo, $workDir, $workFile, $targetFilePath);
        $lockPhysicalFile->unlockFile($lockHandle, $itemId, $attributeId, $fileNo);
        $this->debugLog("[".__FUNCTION__."]"." [Unlock] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        
        if(!$result) {
            return self::DELETE_COVER_FAILED;
        }
        
        return self::DELETE_COVER_SUCCESS;
    }
    
    private function deleteCoverExecute($pdftkCmd, $itemId, $itemNo, $attributeId, $fileNo, $workDir, $workFile, $targetFilePath){
        // 作業ファイルの作成
        // 失敗した場合例外を投げる
        if(!copy($targetFilePath, $workFile)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed copy from files/", __FILE__, __CLASS__, __LINE__);
            throw new AppException($targetFilePath."copy failed.");
        }
        $this->debugLog("[".__FUNCTION__."]"." ".$itemId."_".$attributeId."_".$fileNo.".pdf copy to work directory.", __FILE__, __CLASS__, __LINE__);
        
        // データベースから削除済みかの情報を取得する
        $pdfCoverNum = $this->getPdfCoverPageNum($itemId, $itemNo, $attributeId, $fileNo);
        $status = $this->getCoverDeleteStatus($itemId, $itemNo, $attributeId, $fileNo);
        if($status === RepositoryDatabaseConst::COVER_DELETE_STATUS_NONE) {
            // 未削除の場合フラグレコードを追加する（未削除フラグ）
            if(!$this->insertCoverDeleteStatus($itemId, $itemNo, $attributeId, $fileNo)){
                $this->errorLog("[".__FUNCTION__."]"." Failed insert PDF cover delete working flag", __FILE__, __CLASS__, __LINE__);
                $this->errormsg = self::ERR_CANNOT_DELETE;
                return false;
            }
            // statusを「未削除」に変更
            $status = RepositoryDatabaseConst::COVER_DELETE_STATUS_NOTYET;
            $this->debugLog("[".__FUNCTION__."]"." Insert record (delete cover page working).", __FILE__, __CLASS__, __LINE__);
        }
        $this->debugLog("[".__FUNCTION__."]"." [Number of CoverPage] : ".$pdfCoverNum."  [CoverPage Delete Status] : ". $status, __FILE__, __CLASS__, __LINE__);
        
        // 未削除のフラグが存在しており、カバーページが存在している
        if($status === RepositoryDatabaseConst::COVER_DELETE_STATUS_NOTYET && $pdfCoverNum > 0) {
            $this->debugLog("[".__FUNCTION__."]"." [Start delete cover] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            // カバーページ分離処理
            if(!$this->dividePdf($pdftkCmd, $workFile, $workDir.self::PDF_NAME_ORG_TARGET, sprintf(($pdfCoverNum+1)."-end"))) {
                $this->errorLog("[".__FUNCTION__."]"." Failed divide PDF file", __FILE__, __CLASS__, __LINE__);
                $this->errormsg = self::ERR_CANNOT_DELETE;
                // ファイル破損の可能性あるのでfilesから取得し直してそれを返す
                if(!copy($targetFilePath, $workFile)) {
                    $this->errorLog("[".__FUNCTION__."]"." Failed copy from files/", __FILE__, __CLASS__, __LINE__);
                    throw new AppException($targetFilePath."copy failed.");
                }
                return false;
            }
            $this->debugLog("[".__FUNCTION__."]"." [Success divide] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            
            // ファイル差替え
            $backupFile = $this->safeReplace($targetFilePath, $workFile);
            if(!$backupFile) {
                $this->errorLog("[".__FUNCTION__."]"." Failed replace cover deleted file", __FILE__, __CLASS__, __LINE__);
                $this->errormsg = self::ERR_CANNOT_DELETE;
                // ファイル破損の可能性あるのでfilesから取得し直してそれを返す
                if(!copy($targetFilePath, $workFile)) {
                    $this->errorLog("[".__FUNCTION__."]"." Failed copy from files/", __FILE__, __CLASS__, __LINE__);
                    throw new AppException($targetFilePath."copy failed.");
                }
                return false;
            }
            $this->debugLog("[".__FUNCTION__."]"." [Replace new file] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            
            // カバーページを削除したPDFをfilesに反映する
            // フラグを削除済みに更新
            if(!$this->updateCoverDeleteStatus(RepositoryDatabaseConst::COVER_DELETE_STATUS_DONE, $itemId, $itemNo, $attributeId, $fileNo)) {
                $this->errorLog("[".__FUNCTION__."]"." Failed update cover deleted status", __FILE__, __CLASS__, __LINE__);
                $this->errormsg = self::ERR_CANNOT_DELETE;
                // DBと実ファイルでカバーページの状態の整合性が取れなくなるため、削除前のファイルに差し戻す
                if(!copy($backupFile, $targetFilePath)) {
                    $this->fatalLog("[".__FUNCTION__."]"." PDF replaced by cover deleted file But DB Update failed. Please ".$targetFilePath."replace by backup(".$targetFilePath.".orgTmp).", __FILE__, __CLASS__, __LINE__);
                    throw new AppException($targetFilePath."copy failed.");
                }
                unlink($backupFile);
                // ダウンロードに使用するファイルを取得し直す（削除前のものがダウンロードされる）
                if(!copy($targetFilePath, $workFile)) {
                    $this->errorLog("[".__FUNCTION__."]"." Failed copy from files/", __FILE__, __CLASS__, __LINE__);
                    throw new AppException($targetFilePath."copy failed.");
                }
                
                return false;
            }
            unlink($backupFile);
            $this->debugLog("[".__FUNCTION__."]"." [Update delete flag] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            
            // ファイルテーブルの枚数情報を更新
            if(!$this->resetCoverCreatedFlag($itemId, $itemNo, $attributeId, $fileNo)){
                $this->errorLog("[".__FUNCTION__."]"." Failed update cover deleted flag", __FILE__, __CLASS__, __LINE__);
            }
            $this->debugLog("[".__FUNCTION__."]"." [Update cover number] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            
            
            // PDFサムネイルイメージを更新する
            if(!$this->makeThumbnail($itemId, $itemNo, $attributeId, $fileNo, $workFile)) {
                $this->errorLog("[".__FUNCTION__."]"." Failed make PDF thumbnail", __FILE__, __CLASS__, __LINE__);
            }
            $this->debugLog("[".__FUNCTION__."]"." [Update Thumbnail] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
            
            $this->debugLog("[".__FUNCTION__."]"." [End delete cover] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        } else if($status === RepositoryDatabaseConst::COVER_DELETE_STATUS_DONE){
            // Already deleted
            $this->debugLog("[".__FUNCTION__."]"." [Already deleted] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        } else {
            $this->errorLog("[".__FUNCTION__."]"." [Failed deleted] target:".$targetFilePath.", workdir:".$workDir, __FILE__, __CLASS__, __LINE__);
        }
        
        return true;
    }
    
    /**
     * PDFカバーページを付与するか判定する
     *
     * @param  int $itemId
     * @param  int $itemNo
     * @return bool
     */
    public function checkCreatePdfCover($itemId, $itemNo) {
        // 付与フラグ
        $pdfCoverFlag = false;
        
        // アイテムの所属インデックスを取得する
        $query = "SELECT index_id FROM {repository_position_index} ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = 0;
        $posIndex = $this->Db->execute($query, $params);
        if($posIndex === false || count($posIndex) == 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed get pos index ", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        if(count($posIndex) > 0) {
            // カバーページ付与を行っているインデックスを全て取得する
            $query = "SELECT index_id FROM {repository_index} ".
                     "WHERE create_cover_flag = ? ".
                     "AND is_delete = ? ;";
            $params = array();
            $params[] = 1;
            $params[] = 0;
            $pdfIndex = $this->Db->execute($query, $params);
            if($pdfIndex === false) {
                $this->errorLog("[".__FUNCTION__."]"." Failed check pdf covered index:".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                return false;
            }
            
            for($ii = 0; $ii < count($posIndex); $ii++) {
                for($jj = 0; $jj < count($pdfIndex); $jj++) {
                    if($posIndex[$ii]["index_id"] == $pdfIndex[$jj]["index_id"]) {
                        $this->debugLog("[".__FUNCTION__."]"." Enable create PDF cover on Index ID:".$pdfIndex[$jj]["index_id"], __FILE__, __CLASS__, __LINE__);
                        $pdfCoverFlag = true;
                        break 2;
                    }
                }
            }
        }
        
        return $pdfCoverFlag;
    }
    
    /**
     * Get parameter for PDF Cover by param_name
     *
     * @param string $paramName
     * @return array
     */
    public function getPdfCoverParamRecord($paramName)
    {
        // Get record
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PDF_COVER_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME." = ?; ";
        $params = array();
        $params[] = $paramName;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) == 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed get pdf cover parameter", __FILE__, __CLASS__, __LINE__);
            return null;
        }
        
        return $result[0];
    }
    
    // -------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------
    /**
     * Check PDFTK command
     *
     * @return bool
     * @access private
     */
    private function getCmdPdftk()
    {
        // Get PDFTK command path
        $query = "SELECT param_value FROM {repository_parameter} ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "path_pdftk";
        $ret = $this->Db->execute($query, $params);
        if ($ret === false || count($ret) == 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed get PDFTK path parameter", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        $cmd_path = $ret[0]['param_value'];
        if(strlen($cmd_path) == 0) {
            $this->debugLog("[".__FUNCTION__."]"." Not set PDFTK path", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        if(!file_exists($cmd_path."pdftk") && !file_exists($cmd_path."pdftk.exe")){
            $this->debugLog("[".__FUNCTION__."]"." Not Exist PDFTK command on server", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return $cmd_path."pdftk";
    }
    
    /**
     * Check ImageMagick command
     *
     * @return bool
     * @access private
     */
    private function getCmdImageMagick()
    {
        // Get ImageMagick command path
        $query = "SELECT param_value FROM {repository_parameter} ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "path_ImageMagick";
        $ret = $this->Db->execute($query, $params);
        if ($ret === false || count($ret) == 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed get ImageMagick path parameter", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        $cmd_path = $ret[0]['param_value'];
        if(strlen($cmd_path) == 0) {
            $this->debugLog("[".__FUNCTION__."]"." Not set ImageMagick path", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        if(!file_exists($cmd_path."convert") && !file_exists($cmd_path."convert.exe")){
            $this->debugLog("[".__FUNCTION__."]"." Not Exist ImageMagick command on server", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return $cmd_path."convert";
    }
    
    /**
     * Create cover page
     *
     * @return bool
     * @access private
     */
    private function createCoverPage($outputFile, $itemId, $itemNo, $attributeId, $fileNo, $workDir)
    {
        // Get item data array
        $itemData = array();
        if(!$this->repositoryAction->getItemData($itemId, $itemNo, $itemData, $errMsg, false, true)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed get item data", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        $this->debugLog("[".__FUNCTION__."]"." Get item data", __FILE__, __CLASS__, __LINE__);
        
        // Add PDF file URL
        $container = & DIContainerFactory::getContainer();
        $Session = $container->getComponent("Session");
        
        $uri = "";
        $handleManager = new RepositoryHandleManager($Session, $this->Db, $this->accessDate);
        $uri = $handleManager->createUriForDetail($itemId, $itemNo);
        if(strlen($uri) <= 0) {
            $this->debugLog("[".__FUNCTION__."]"." Get item's URL instead of PermaLink", __FILE__, __CLASS__, __LINE__);
            $uri = $handleManager->getSubstanceUri($itemId, $itemNo);
        }
        $this->debugLog("[".__FUNCTION__."]"." PDF cover page [URL] : ".$uri, __FILE__, __CLASS__, __LINE__);
        
        // FPDF
        $fpdf = new Repository_Components_Fpdf($workDir);
        // Set parameter
        $headerAlign = $this->getPdfCoverHeaderAlign();
        $headerType = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE);
        if($headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_TEXT) {
            $this->debugLog("[".__FUNCTION__."]"." PDF cover page [HeaderType] : ".$headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT], __FILE__, __CLASS__, __LINE__);
            // Header type : text
            $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TEXT);
            $headerText = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
            $fpdf->setHeaderTextParam($headerAlign, $headerType, $headerText);
            $this->debugLog("[".__FUNCTION__."]"." PDF cover page [HeaderText] : ".$headerText, __FILE__, __CLASS__, __LINE__);
        } else if($headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT] == RepositoryConst::PDF_COVER_HEADER_TYPE_IMAGE) {
            $this->debugLog("[".__FUNCTION__."]"." PDF cover page [HeaderType] : ".$headerType[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT], __FILE__, __CLASS__, __LINE__);
            // Header type : image
            $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE);
            $imageBlob = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE];
            $imageName = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
            $fpdf->setHeaderImageParam($headerAlign, $headerType, $imageBlob, $imageName);
            $this->debugLog("[".__FUNCTION__."]"." PDF cover page [ImageBlob] : ".$imageBlob." [ImageName] : ".$imageName, __FILE__, __CLASS__, __LINE__);
        }
        
        // Get license
        $licenseId = "";
        $notation = "";
        $licenseImagePath = "";
        $licenseTextUrl = "";
        $this->getPdfCoverFooterLicense($itemId, $itemNo, $attributeId, $fileNo, $licenseId, $notation, $licenseImagePath, $licenseTextUrl);
        $fpdf->setFooterLicenseParam($licenseId, $notation, $licenseImagePath, $licenseTextUrl);
        $this->debugLog("[".__FUNCTION__."]"." PDF cover page [LicenseID] : ".$licenseId." [Notation] : ".$notation." [ImagePath] : ".$licenseImagePath." [LicenseURL] : ".$licenseTextUrl, __FILE__, __CLASS__, __LINE__);
        
        // make pdf cover page
        $result = $fpdf->makeCoverpage($itemData, $uri, $outputFile);
        
        return $result;
    }
    
    /**
     * Check existing cover page
     *
     * @return bool false: Not exist / true: Existing
     * @access private
     */
    public function getPdfCoverPageNum($itemId, $itemNo, $attributeId, $fileNo)
    {
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ?;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) == 0) {
            $this->debugLog("[".__FUNCTION__."]"." Not Find PDF cover page number", __FILE__, __CLASS__, __LINE__);
            return 0;
        }
        
        return intval($result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG]);
    }
    
    /**
     * Divide PDF pages
     *
     * @param string $pdftkCmd
     * @param string $target
     * @param string $tmpTarget
     * @param string $range
     * @return bool
     * @access private
     */
    private function dividePdf($pdftkCmd, $target, $tmpTarget, $range)
    {
        if(!rename($target, $tmpTarget)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed rename to tmpfile for divide", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        // # pdftk [target_path] cat [page_range] output [output_path]
        $cmd = "\"".$pdftkCmd."\" ". "\"".$tmpTarget."\" ". "cat ".$range." output ". "\"".$target."\"";
        // 実行(最大処理時間60秒)
        $result = Repository_Components_Util_Fileprocess::exec($cmd, 180000);
        // 成功/0 削除失敗/1 PDFTK実行時例外発生/2 例外エラー/-1 タイムアウト/false
        if($result !== 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed divide pdf cover [status : ". $result."]", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    
    /**
     * Combine PDF pages
     *
     * @return bool
     * @access private
     */
    private function combinePdf($workFile, $coverFile, $combineFile)
    {
        // Check PDFTK
        $pdftkCmd = $this->getCmdPdftk();
        if(!$pdftkCmd) {
            $this->debugLog("[".__FUNCTION__."]"." Not exist PDFTK", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        $this->debugLog("[".__FUNCTION__."]"." find PDFTK", __FILE__, __CLASS__, __LINE__);
        
        // #pdftk [cover_path] [target_path] cat output [output_path]
        $cmd = "\"".$pdftkCmd."\" ".
               "\"".$coverFile."\" ".
               "\"".$workFile."\" ".
               "cat output ".
               "\"".$combineFile."\"";
        // 実行(最大処理時間60秒)
        $result = Repository_Components_Util_Fileprocess::exec($cmd, 180000);
        // 成功/0 付与失敗/1 ソフトウェア実行時例外発生/2 例外エラー/-1 タイムアウト/false
        if($result !== 0) {
            $this->errorLog("[".__FUNCTION__."]"." Failed combine pdf cover [status : ". $result."]", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    
    /**
     * Make PDF thumbnail
     *
     * @return bool
     * @access private
     */
    public function makeThumbnail($itemId, $itemNo, $attributeId, $fileNo, $workFile)
    {
        
        // Check ImageMagick command
        $convertCmd = $this->getCmdImageMagick();
        if(!$convertCmd) {
            $this->debugLog("[".__FUNCTION__."]"." Not Exist ImageMagick", __FILE__, __CLASS__, __LINE__);
            return true;
        }
        $this->debugLog("[".__FUNCTION__."]"." Get ImageMagick", __FILE__, __CLASS__, __LINE__);
        
        // PDF -> PNG
        $pngImage = $workFile.".png";
        // # convert -quality 100 [$workFile] [$pngImage]
        $cmd = "\"".$convertCmd."\" "."-quality 100 "."\"".$workFile."\"[0] "."\"".$pngImage."\"";
        // 実行(最大処理時間60秒)
        $result = Repository_Components_Util_Fileprocess::exec($cmd, 180000);
        if(!file_exists($pngImage)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed create pdf image for getting size", __FILE__, __CLASS__, __LINE__);
            $this->deleteThumbnail($itemId, $itemNo, $attributeId, $fileNo);
            return false;
        }
        $this->debugLog("[".__FUNCTION__."]"." convert to test thumbnail", __FILE__, __CLASS__, __LINE__);
        
        // Get image size
        $imgSize = array();
        $imgSize = getimagesize($pngImage);
        $width = $imgSize[0];
        $height = $imgSize[1];
        if(!unlink($pngImage)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed remove tmp image file", __FILE__, __CLASS__, __LINE__);
            $this->deleteThumbnail($itemId, $itemNo, $attributeId, $fileNo);
            return false;
        }
        
        // Resize
        if($height > $width) {
            // Height is longer than width
            $this->debugLog("[".__FUNCTION__."]"." Resize png Height > Width", __FILE__, __CLASS__, __LINE__);
            // # convert -quality 100 -density 200x200 -resize 200x [$workFile][0] [$pngImage]
            $cmd = "\"".$convertCmd."\" ".
                   "-quality 100 -density 200x200 -resize 200x ".
                   "\"".$workFile."\"[0] ".
                   "\"".$pngImage."\"";
        } else {
            // Width is longer than height
            $this->debugLog("[".__FUNCTION__."]"." Resize png Width > Height", __FILE__, __CLASS__, __LINE__);
            // # convert -quality 100 -density 200x200 -resize x280 [$workFile][0] [$pngImage]
            $cmd = "\"".$convertCmd."\" ".
                   "-quality 100 -density 200x200 -resize x280 ".
                   "\"".$workFile."\"[0] ".
                   "\"".$pngImage."\"";
        }
        // 実行(最大処理時間60秒)
        $result = Repository_Components_Util_Fileprocess::exec($cmd, 180000);
        if(!file_exists($pngImage)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed create pdf image for combine file", __FILE__, __CLASS__, __LINE__);
            $this->deleteThumbnail($itemId, $itemNo, $attributeId, $fileNo);
            return false;
        }
        $this->debugLog("[".__FUNCTION__."]"." Convert PDF to ThumbnailImage", __FILE__, __CLASS__, __LINE__);
        
        // Update DB
        if(!$this->updateThumbnail($pngImage, $itemId, $itemNo, $attributeId, $fileNo)) {
            $this->errorLog("[".__FUNCTION__."]"." Failed update BLOB", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    
    /**
     * サムネイル情報削除
     * 
     */
    private function deleteThumbnail($itemId, $itemNo, $attributeId, $fileNo)
    {
        $query = " UPDATE {repository_file} ".
                 " SET file_prev = NULL, file_prev_name = NULL ".
                 " WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 " AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 " AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 " AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $ret = $this->Db->execute($query, $params);
        if ($ret === false) {
            $this->errorLog("[".__FUNCTION__."]"." Failed delete BLOB image by DB : ".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        }
        $this->debugLog("[".__FUNCTION__."]"." delete Blob", __FILE__, __CLASS__, __LINE__);
        
        return true;
    }
    
    /**
     * Update file thumbnail
     *
     * @param string $filePath
     * @return bool
     */
    private function updateThumbnail($filePath, $itemId, $itemNo, $attributeId, $fileNo)
    {
        $whereParams = RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ".$itemId." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ".$itemNo." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ".$attributeId." ".
                       "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ".$fileNo;
        $ret = $this->Db->updateBlobFile(
                    RepositoryConst::DBTABLE_REPOSITORY_FILE,
                    RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_PREV,
                    $filePath,
                    $whereParams,
                    "LONGBLOB"
                );
        if ($ret === false) {
            $this->errorLog("[".__FUNCTION__."]"." Failed update BLOB image by DB: ".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get target PDF file Path
     *
     * @return string
     * @access private
     */
    private function getTargetPdfFilePath($itemId, $attributeId, $fileNo)
    {
        $fileName = $itemId."_".$attributeId."_".$fileNo.".pdf";
        
        $dirPath = $this->repositoryAction->getFileSavePath("file");
        if(strlen($dirPath) == 0) {
            // default directory
            $dirPath = BASE_DIR.'/webapp/uploads/repository/files/';
        }
        
        if(substr($dirPath, -1, 1) != "/"){
            $dirPath .= "/";
        }
        
        $targetFilePath = $dirPath.$fileName;
        $this->debugLog("[".__FUNCTION__."]"." Get target file : ". $targetFilePath, __FILE__, __CLASS__, __LINE__);
        
        return $targetFilePath;
    }
    
    
    // Mod delete pdf cover 2015/01/26 K.Matsushita -start-
    /**
     * Reset cover_created_flag
     * 
     * @return bool
     * @access private
     */
    private function resetCoverCreatedFlag($itemId, $itemNo, $attributeId, $fileNo)
    {
        $query = "UPDATE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                "SET ".RepositoryConst::DBCOL_REPOSITORY_FILE_COVER_CREATED_FLAG." = ?, ".
                RepositoryConst::DBCOL_COMMON_MOD_USER_ID." = ?, ".
                RepositoryConst::DBCOL_COMMON_MOD_DATE." = ? ".
                "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ?; ";
        $params = array();
        $params[] = self::RESET_CREATED_FLG;
        $params[] = $this->user_id;
        $params[] = $this->accessDate;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $result = $this->Db->execute($query, $params);
        if($result === false) {
            $this->errorLog("[".__FUNCTION__."]"." Failed reset cover flag by DB : ".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    // Mod delete pdf cover 2015/01/26 K.Matsushita -end-
    
    /**
     * Get cover delete status
     * @return boolean|string
     */
    private function getCoverDeleteStatus($itemId, $itemNo, $attributeId, $fileNo){
        $params = array();
        $params["item_id"] = $itemId;
        $params["item_no"] = $itemNo;
        $params["attribute_id"] = $attributeId;
        $params["file_no"] = $fileNo;
        $result = $this->Db->selectExecute("repository_cover_delete_status", $params);
        if($result===false){
            $this->errorLog("[".__FUNCTION__."]"." Failed get cover deleted status by DB : ".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        } else if(count($result)==0){
            $this->debugLog("[".__FUNCTION__."]"." File is not deleted cover page", __FILE__, __CLASS__, __LINE__);
            return RepositoryDatabaseConst::COVER_DELETE_STATUS_NONE;
        } else {
            $this->debugLog("[".__FUNCTION__."]"." File cover page status : ".$result[0]["status"], __FILE__, __CLASS__, __LINE__);
            return $result[0]["status"];
        }
    }
    /**
     * Insert cover delete status
     * @return boolean
     */
    private function insertCoverDeleteStatus($itemId, $itemNo, $attributeId, $fileNo){
        $query = "INSERT INTO {repository_cover_delete_status} ".
                 "(item_id, item_no, attribute_id, file_no, status) ".
                 "VALUES(?, ?, ?, ?, ?);";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = RepositoryDatabaseConst::COVER_DELETE_STATUS_NOTYET;
        $result = $this->Db->execute($query, $params);
        if($result===false){
            $this->errorLog("[".__FUNCTION__."]"." Failed insert cover deleted status : ".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    /**
     * Update cover delete status
     * @return boolean
     */
    private function updateCoverDeleteStatus($status, $itemId, $itemNo, $attributeId, $fileNo){
        $query = "UPDATE {repository_cover_delete_status} ".
                 "SET status = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ".
                 "AND file_no = ? ;";
        $params = array();
        $params[] = $status;
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $result = $this->Db->execute($query, $params);
        if($result===false){
            $this->errorLog("[".__FUNCTION__."]"." Failed update cover deleted status".$this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        return true;
    }
    
    /**
     * get pdf cover header align
     *
     * @return string
     */
     private function getPdfCoverHeaderAlign() {
        $result = $this->repositoryAction->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN);
        switch($result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT]) {
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_RIGHT:
                $headerAlign = RepositoryConst::ALIGN_RIGHT;
                break;
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_CENTER:
                $headerAlign = RepositoryConst::ALIGN_CENTER;
                break;
            case RepositoryConst::PDF_COVER_HEADER_ALIGN_LEFT:
                $headerAlign = RepositoryConst::ALIGN_LEFT;
                break;
            default:
                $headerAlign = RepositoryConst::ALIGN_RIGHT;
                break;
        }
        
        return $headerAlign;
     }
    
    /**
     * get pdf cover footer license
     *
     * @return string
     */
     private function getPdfCoverFooterLicense($itemId, $itemNo, $attributeId, $fileNo, &$licenseId, &$notation, &$licenseImagePath, &$licenseTextUrl) {
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ?; ";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false || (isset($result) && count($result) == 0)){
            $this->errorLog("[".__FUNCTION__."]"." Not Exist File License", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        
        $licenseId = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_ID];
        $notation = $result[0][RepositoryConst::DBCOL_REPOSITORY_FILE_LICENSE_NOTATION];
        
        if(strlen($licenseId) > 0 && $licenseId > 0) {
            // Creative Commons
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LICENSE_MASTER." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_LICENSE_ID." = ?; ";
            $params = array();
            $params[] = $licenseId;
            $result = $this->Db->execute($query, $params);
            if($result === false || (isset($result) && count($result) == 0)) {
                $this->errorLog("[".__FUNCTION__."]"." Not Exist CreativeCommons License", __FILE__, __CLASS__, __LINE__);
                return false;
            }
            $licenseImagePath = $result[0][RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_IMG_URL];
            $licenseTextUrl = $result[0][RepositoryConst::DBCOL_REPOSITORY_LICENSE_MASTAER_TEXT_URL];
        }
        
        return true;
     }
     
    /**
     * safe copy
     *
     * @return string
     */
     private function safeReplace($orgFile, $newFile) {
        // 対象ディレクトリに新ファイルを複製
        if(!copy($newFile, $orgFile.".newTmp")){
            $this->errorLog("[".__FUNCTION__."]"." Failed make backup for coverDeletedPdfFile", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        // 実行
        if(!rename($orgFile, $orgFile.".orgTmp")) {
            // 元ファイルの操作に失敗した場合、差替えを中止する
            $this->errorLog("[".__FUNCTION__."]"." Failed move original PDF", __FILE__, __CLASS__, __LINE__);
            return false;
        }
        if(!rename($orgFile.".newTmp", $orgFile)) {
            // 新ファイルの配置に失敗した場合、元ファイルを再配置する
            $this->errorLog("[".__FUNCTION__."]"." Failed move new cover deleted PDF file", __FILE__, __CLASS__, __LINE__);
            $this->errormsg = self::ERR_CANNOT_DELETE;
            rename($orgFile.".orgTemp", $orgFile);
            return false;
        }
        
        return $orgFile.".orgTmp";
     }
}
?>