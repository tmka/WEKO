<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryFileUpload.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/util/MultipartStreamDecoder.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/util/CreateWorkDirectory.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppException.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/IO/IOException.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/IO/FileStream.class.php';

/**
 * output format filter class
 * 
 * return format string. when not allow format, return '';
 * 
 */
class RepositoryFileUpload
{
    // member
    private $fileName = null;
    private $physicalFileName = null;
    private $extension = null;
    private $fileSize = null;
    private $mimetype = null;
    private $insertTime = null;
    private $uploadDir = null;
    
    private $logFile = "";
    private $isCreateLog = true;
    
    // Const
    const KEY_FILE_NAME = "file_name";
    const KEY_PHYSICAL_FILE_NAME = "physical_file_name";
    const KEY_FILE_SIZE = "file_size";
    const KEY_MIMETYPE = "mimetype";
    const KEY_EXTENSION = "extension";
    const KEY_INSERT_TIME = "insert_time";
    const KEY_UPLOAD_DIR = "upload_dir";
    
    
    /**
     * Constructor
     *
     * @return RepositoryFileUpload
     */
    public function __construct()
    {
        $this->init();
        
        $this->uploadDir = Repository_Components_Util_CreateWorkDirectory::create(WEBAPP_DIR.DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR."repository".DIRECTORY_SEPARATOR);
        if($this->uploadDir === false) {
            $this->uploadDir = WEBAPP_DIR.DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR."repository".DIRECTORY_SEPARATOR;
        }
        
        // Create log file
        if($this->isCreateLog)
        {
            $this->logFile = WEBAPP_DIR."/logs/weko/sword/file_upload_log.txt";
            $logFh = fopen($this->logFile, "w");
            chmod($this->logFile, 0600);
            fwrite($logFh, "Start RepositoryFileUpload. (".date("Y/m/d H:i:s").")\n");
            fwrite($logFh, "\n");
            fclose($logFh);
        }
    }
    
    /**
     * Get upload data
     *
     * @return array
     */
    public function getUploadData(&$statusCode)
    {
        $this->writeLog("-- Start getUploadData (".date("Y/m/d H:i:s").") --\n");

        $this->getFileByInput($statusCode);

        $fileData = array();
        if( isset($this->fileName) && isset($this->physicalFileName) && isset($this->fileSize) &&
            isset($this->mimetype) && isset($this->extension) && isset($this->insertTime) && isset($this->uploadDir))
        {
            $fileData[self::KEY_FILE_NAME] = $this->fileName;
            $fileData[self::KEY_PHYSICAL_FILE_NAME] = $this->physicalFileName;
            $fileData[self::KEY_FILE_SIZE] = $this->fileSize;
            $fileData[self::KEY_MIMETYPE] = $this->mimetype;
            $fileData[self::KEY_EXTENSION] = $this->extension;
            $fileData[self::KEY_INSERT_TIME] = $this->insertTime;
            $fileData[self::KEY_UPLOAD_DIR] = $this->uploadDir;
        }
        else
        {
            if(!isset($statusCode) || strlen($statusCode) == 0)
            {
                $statusCode = 400;
            }

            if( strlen($this->uploadDir)>0 && strlen($this->physicalFileName)>0 &&
                file_exists($this->uploadDir.$this->physicalFileName))
            {
                unlink($this->uploadDir.$this->physicalFileName);
            }
        }
        
        $this->writeLog("-- End getUploadData (".date("Y/m/d H:i:s").") --\n\n");
        
        return $fileData;
    }

    /**
     * 初期化処理
     */
    private function init()
    {
        $this->fileName = null;
        $this->physicalFileName = null;
        $this->extension = null;
        $this->fileSize = null;
        $this->mimetype = null;
        $this->insertTime = null;
        
        $this->writeLog("  Init data.\n");
    }

    /**
     * ファイル情報の取得
     * @param エラーメッセージ $errorMsg
     */
    private function getFileByInput(&$statusCode)
    {
        $this->writeLog("-- Start getFileByInput (".date("Y/m/d H:i:s").") --\n");

        $this->init();
        // Update SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
        // ファイル情報を決める
        $this->decideInsertTime();
        $this->decideMimeType();
        $this->decideFileName();
        $this->decideExtension();
        $this->decidePhysicalName();

        // ファイルのアップロード
        $this->decodeFile($statusCode);

        // ログの出力
        $this->outPutLogOfFileInfo();
        $this->writeLog("-- End getFileByInput (".date("Y/m/d H:i:s").") --\n\n");
        // Update SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--
    }

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * InsertTimeを決める
     */
    private function decideInsertTime()
    {
        // Set insertTime
        $now = new Date();
        $this->insertTime = $now->getDate(DATE_FORMAT_TIMESTAMP);
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * MimeTypeを決める
     */
    private function decideMimeType()
    {
        // Set mimetype
        if(isset($_SERVER['CONTENT_TYPE'])){
            $this->mimetype = $_SERVER['CONTENT_TYPE'];
        } else if(isset($_SERVER['HTTP_CONTENT_TYPE'])){
            $this->mimetype = $_SERVER['HTTP_CONTENT_TYPE'];
        }
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * ファイル名を決める
     */
    private function decideFileName()
    {
        if(isset($_SERVER["CONTENT_DISPOSITION"])){
            $contentDisposition = urldecode($_SERVER["CONTENT_DISPOSITION"]);
            $contentDisposition = mb_convert_encoding($contentDisposition, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS");
        } else if(isset($_SERVER["HTTP_CONTENT_DISPOSITION"])){
            $contentDisposition = urldecode($_SERVER["HTTP_CONTENT_DISPOSITION"]);
            $contentDisposition = mb_convert_encoding($contentDisposition, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS");
        }
        $pattern = "/filename=[\"|\']([^\"\']+)[\"|\' ]/";
        $result = preg_match($pattern, $contentDisposition, $matches);
        if($result > 0)
        {
            if(isset($matches) && is_array($matches) && count($matches) > 1)
            {
                $this->fileName = trim($matches[1]);
            }
        }
        else
        {
            $pattern = "/filename=([^ ]+)/";
            $result = preg_match($pattern, $contentDisposition, $matches);
            if($result > 0)
            {
                if(isset($matches) && is_array($matches) && count($matches) > 1)
                {
                    $this->fileName = trim($matches[1]);
                }
            }
        }
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * Extensionを決める
     */
    private function  decideExtension()
    {
        // Set extension
        if(strlen($this->fileName) > 0)
        {
            if(preg_match("/^application\/zip/", $this->mimetype) ||
                    preg_match("/^application\/x-zip/",$this->mimetype) ||
                    preg_match("/^application\/x-compress/", $this->mimetype) ||
                    preg_match("/^multipart\/form-data/", $this->mimetype))
            {
                $this->extension = "zip";
                $this->fileName .= ".".$this->extension;
            }
            else
            {
                $pos = strrpos($this->fileName, '.');
                if($pos !== false)
                {
                    $this->extension = strtolower(substr($this->fileName, $pos+1));
                }
            }
        }
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * PhysicalNameを決める
     */
    private function decidePhysicalName()
    {
        // Set physicalName
        $this->physicalFileName = $this->insertTime.".".$this->extension;
        if(strlen($this->fileName) == 0)
        {
            $this->fileName = $this->physicalFileName;
        }
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * ファイルアップロード処理
     * @param string $statusCode ステータスコード
     * @return boolean デコード処理の成功失敗
     */
    private function decodeFile(&$statusCode)
    {
        try {
            $readFileStream = FileStream::open("php://input", "rb");
            $this->writeLog("[decode]:");
            $fileList = Repository_Components_Util_MultipartStreamDecoder::decodeMultiPartFile($readFileStream, $this->uploadDir.$this->physicalFileName);
            $this->writeLog("Success\n");
            $this->writeLog("[UPLODE FILE]"."\n");
            foreach ($fileList as $fileName){
                $this->writeLog($fileName."\n");
            }
        }
        catch(AppException $e){
            $errorMsg = $e->getMessage();
            $this->writeLog("ERROR\n".$errorMsg."\n");
            $readFileStream->close();

            // マルチパートでない場合のzipファイル出力処理
            $readFileStream = FileStream::open("php://input", "rb");
            $outputFileStream = FileStream::open($this->uploadDir.$this->physicalFileName, "w");
            while ($data = $readFileStream->read(1024))
            {
                $outputFileStream->write($data);
            }
            $outputFileStream->close();
            $readFileStream->close();
        }
        catch(IOException $e)
        {
            $errorMsg = $e->getMessage();
            $this->writeLog("ERROR\n".$errorMsg."\n");
            $statusCode = 500;

            return false;
        }

        $this->fileSize = filesize($this->uploadDir.$this->physicalFileName);

        return true;
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    // Add SuppleContentsEntry Y.Yamazawa --start-- 2015/04/08 --start--
    /**
     * ファイル情報ログの出力
     */
    private function outPutLogOfFileInfo()
    {
        $this->writeLog(" [Session data]\n");
        if(isset($_SERVER['CONTENT_TYPE']))
        {
            $this->writeLog("  CONTENT_TYPE: ".$_SERVER['CONTENT_TYPE']."\n");
        }
        if(isset($_SERVER['HTTP_CONTENT_TYPE']))
        {
            $this->writeLog("  HTTP_CONTENT_TYPE: ".$_SERVER['HTTP_CONTENT_TYPE']."\n");
        }
        if(isset($_SERVER['CONTENT_DISPOSITION']))
        {
            $this->writeLog("  CONTENT_DISPOSITION: ".$_SERVER['CONTENT_DISPOSITION']."\n");
        }
        $this->writeLog("  HTTP_CONTENT_DISPOSITION: ".$_SERVER['HTTP_CONTENT_DISPOSITION']."\n");
        $this->writeLog("\n");
        
        $this->writeLog(" [Acquired file data]\n");
        $this->writeLog("  insertTime: ".$this->insertTime."\n");
        $this->writeLog("  mimetype: ".$this->mimetype."\n");
        $this->writeLog("  extension: ".$this->extension."\n");
        $this->writeLog("  fileName: ".$this->fileName."\n");
        $this->writeLog("  physicalFileName: ".$this->physicalFileName."\n");
        $this->writeLog("  fileSize: ".$this->fileSize."\n");
        $this->writeLog("-- End getFileByInput (".date("Y/m/d H:i:s").") --\n\n");
    }
    // Add SuppleContentsEntry Y.Yamazawa --end-- 2015/04/08 --end--

    /**
     * Write log to file
     *
     * @param string $string
     * @param int $length [optional]
     * @return int
     */
    private function writeLog($string, $length=null)
    {
        if($this->isCreateLog && strlen($this->logFile)>0)
        {
            $ret = "";
            $fp = fopen($this->logFile, "a");
            if(isset($length))
            {
                $ret = fwrite($fp, $string, $length);
            }
            else
            {
                $ret = fwrite($fp, $string);
            }
            fclose($fp);
            
            return $ret;
        }
        else
        {
            return false;
        }
    }
}
?>
