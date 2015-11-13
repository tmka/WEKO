<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppLogger.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppException.class.php';
/**
 * $Id: BusinessBase.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
 * 
 * ビジネスロジック基底クラス
 * 
 * @author IVIS
 */
abstract class BusinessBase
{
    /**
     * Dbコンポーネントを受け取る
     * @var DbObjectAdodb
     */
    public $Db = null;
    
    /**
     * アクセス日時
     *
     * @var string
     */
    public $accessDate = null;
    
    /**
     * アクセスユーザーのユーザーID
     * 未ログインの場合は"0"
     * 
     * @var string
     */
    protected $user_id = "";
    
    /**
     * ログインユーザーのハンドル名
     * 
     * @var string
     */
    protected $handle = "";
    
    /**
     * アクセスユーザーのベース権限
     *   管理者 => _ROLE_AUTH_ADMIN
     *   主担 => _ROLE_AUTH_CHIEF
     *   モデレータ => _ROLE_AUTH_MODERATE
     *   一般 => _ROLE_AUTH_GENERAL
     *   ゲスト => _ROLE_AUTH_GUEST
     *   事務局 => _ROLE_AUTH_CLERK
     *   その他 => _ROLE_AUTH_OTHER
     *   未ログイン => 
     * 
     * @var int
     */
    protected $auth_id = null;
    
    /**
     * 初期化
     *
     * @param DbObjectAdodb $db DBObject
     * @param string $accessDate 
     * @param unknown $user_id
     * @param unknown $handle
     * @param unknown $auth_id
     */
    final function initializeBusiness($db, $accessDate, $user_id, $handle, $auth_id)
    {
        $this->Db = $db;
        $this->accessDate = $accessDate;
        $this->user_id = $user_id;
        $this->handle = $handle;
        $this->auth_id = $auth_id;
        $this->onInitialize();
    }
    
    /**
     * 終了処理
     *
     */
    final function finalizeBusiness()
    {
        $this->onFinalize();
    }
    
    /**
     * インスタンス生成時に実行する処理
     * 
     */
    protected function onInitialize(){}
    
    /**
     * インスタンス破棄時に実行する処理
     * 
     */
    protected function onFinalize(){}
    
    /**
     * Exeption時のログ出力
     *
     * @param Exception $e エクセプションクラス
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function exeptionLog(Exception $e, $filePath, $className, $lineNo)
    {
        AppLogger::errorLog($e->__toString(), $filePath, $className, $lineNo);
    }
    
    /**
     * fatalレベル以上のログを出力
     * 
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function fatalLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::fatalLog($message, $filePath, $className, $lineNo);
    }
    
    /**
     * errorレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function errorLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::errorLog($message, $filePath, $className, $lineNo);
    }
    
    /**
     * warnレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function warnLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::warnLog($message, $filePath, $className, $lineNo);
    }
    
    /**
     * infoレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function infoLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::infoLog($message, $filePath, $className, $lineNo);
    }
    
    /**
     * debugレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function debugLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::debugLog($message, $filePath, $className, $lineNo);
    }
    
    /**
     * traceレベル以上のログを出力
     *
     * @param string $message エラーメッセージ
     * @param string $filePath ファイルパス
     * @param string $className クラス名
     * @param string $lineNo 行数
     */
    final function traceLog($message, $filePath, $className, $lineNo)
    {
        AppLogger::traceLog($message, $filePath, $className, $lineNo);
    }
}
?>