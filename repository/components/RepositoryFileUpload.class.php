<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryFileUpload.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

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
    public function RepositoryFileUpload()
    {
        $this->init();
        $this->uploadDir = WEBAPP_DIR.DIRECTORY_SEPARATOR.
                           "uploads".DIRECTORY_SEPARATOR.
                           "repository";
        
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
    public function getUploadData()
    {
        $this->writeLog("-- Start getUploadData (".date("Y/m/d H:i:s").") --\n");
        
        $this->getFileByInput();
        
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
            if( strlen($this->uploadDir)>0 && strlen($this->physicalFileName)>0 &&
                file_exists($this->uploadDir.DIRECTORY_SEPARATOR.$this->physicalFileName))
            {
                unlink($this->uploadDir.DIRECTORY_SEPARATOR.$this->physicalFileName);
            }
        }
        
        $this->writeLog("-- End getUploadData (".date("Y/m/d H:i:s").") --\n\n");
        
        return $fileData;
    }
    
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
    
    private function getFileByInput()
    {
        $this->writeLog("-- Start getFileByInput (".date("Y/m/d H:i:s").") --\n");
        
        $this->init();
        
        // Set insertTime
        $now = new Date();
        $this->insertTime = $now->getDate(DATE_FORMAT_TIMESTAMP);
        
        // Set mimetype
        if(isset($_SERVER['CONTENT_TYPE'])){
            $this->mimetype = $_SERVER['CONTENT_TYPE'];
        } else if(isset($_SERVER['HTTP_CONTENT_TYPE'])){
            $this->mimetype = $_SERVER['HTTP_CONTENT_TYPE'];
        }
        
        // Set fileName
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
        
        // Set extension
        if(strlen($this->fileName) > 0)
        {
            if(preg_match("/^application\/zip/", $this->mimetype) ||
                    preg_match("/^application\/x-zip/",$mimetype) ||
                    preg_match("/^application\/x-compress/", $this->mimetype))
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
        
        // Set physicalName
        $this->physicalFileName = $this->insertTime.".".$this->extension;
        if(strlen($this->fileName) == 0)
        {
            $this->fileName = $this->physicalFileName;
        }
        
        $putdata = fopen("php://input", "rb");
        $fp = fopen($this->uploadDir.DIRECTORY_SEPARATOR.$this->physicalFileName, "w");
        while($data = fread($putdata, 1024)){   // 1MBずつ追記する
            fwrite($fp, $data);
        }
        fclose($fp);
        fclose($putdata);
        
        $this->fileSize = filesize($this->uploadDir.DIRECTORY_SEPARATOR.$this->physicalFileName);
        
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
