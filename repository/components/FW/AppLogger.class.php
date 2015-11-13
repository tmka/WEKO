<?php
/**
 * NetCommonsが提供するロガークラスを呼び出す。
 * 
 * simpleFile:ファイルへ書き出すロガー
 * :画面表示表示するロガー
 * 
 * @author      IVIS
 */
class AppLogger
{
    private static function initialize()
    {
        // もともとnc2/maple/generate/script/generate.phpにLOG_LEVELは宣言されているが
        // NC2上でLogger_SimpleFileを利用するとUse of undefined constant LOG_LEVELとなる
        // このため、未定義の場合のみ本クラスで宣言するようにしている
        // mapleで定義されているログレベルの定数は下記の通り
        // define('LEVEL_SQL', 2048+7);
        // define('LEVEL_FATAL', 2048+6);
        // define('LEVEL_ERROR', 2048+5);
        // define('LEVEL_WARN',  2048+4);
        // define('LEVEL_INFO',  2048+3);
        // define('LEVEL_DEBUG', 2048+2);
        // define('LEVEL_TRACE', 2048+1);
        
        if(!defined('LOG_LEVEL'))
        {
            define('LOG_LEVEL', LEVEL_WARN);
        }
    }
    /**
     * fatalレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function fatalLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->fatal("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog();
        $log->fatal("$message in file $filePath line $lineNo");
    }
    
    /**
     * errorレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function errorLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->error("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog("");
        $log->error("$message in file $filePath line $lineNo");
    }
    
    /**
     * warnレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function warnLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->warn("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog("");
        $log->warn("$message in file $filePath line $lineNo");
    }
    
    /**
     * infoレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function infoLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->info("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog("");
        $log->info("$message in file $filePath line $lineNo");
    }
    
    /**
     * debugレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function debugLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->debug("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog("");
        $log->debug("$message in file $filePath line $lineNo");
    }
    
    /**
     * traceレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    public static function traceLog($message, $filePath, $className, $lineNo)
    {
        self::initialize();
        
        $log =& LogFactory::getLog("simpleFile");
        $log->trace("$className,$lineNo,".session_id().",".$message);
        
        $log =& LogFactory::getLog("");
        $log->trace("$message in file $filePath line $lineNo");
    }
}
?>
