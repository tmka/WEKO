<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppLogger.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppException.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/BusinessFactory.class.php';
/**
 * $Id: ActionBase.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
 * 
 * NetCommons用アクション基底クラス
 * 
 * @author IVIS
 */
abstract class ActionBase extends Action
{
    /**
     * Sessionコンポーネントを受け取る
     * @var SessionExtra
     */
    public $Session = null;
    /**
     * Dbコンポーネントを受け取る
     * @var DbObjectAdodb
     */
    public $Db = null;
    
    /**
     * アクセス日時
     * 
     * @var 
     */
    protected $accessDate = null;
    
    /**
     * リクエストパラメーター：エラーメッセージ
     * @var array
     */
    public $errMsg = null;
    
    /**
     * 処理を exit() で終了させるフラグ
     * @var bool
     */
    protected $exitFlag = false;
    
    /**
     * 初期化処理
     *
     */
    protected function initialize()
    {
        // アクセス日時
        if(class_exists("DateTime")){
            $date = new DateTime();
            $this->accessDate = $date->format('Y-m-d H:i:s').".000";
        } else {
            $this->accessDate = date('Y-m-d H:i:s').".000";
        }
        
        // ビジネスロジック生成クラス初期化
        BusinessFactory::initialize($this->Session, $this->Db, $this->accessDate);
    }
    
    /**
     * 終了処理
     *
     */
    protected function finalize()
    {
        // ビジネスロジック生成クラス終了処理
        BusinessFactory::uninitialize();
    }
    
    /**
     * 実行処理
     * 
     * @return string
     */
    public function execute()
    {
        try
        {
            // 初期化処理
            $this->initialize();
            
            // トランザクション外前処理
            $this->beforeTrans();
            
            $isTransStared = false;
            if($this->Db->StartTrans() === false)
            {
                $this->infoLog("Failed start trance.", __FILE__, __CLASS__, __LINE__);
                throw new AppException("Failed start trance.");
            }
            $isTransStared = true;
            
            // Actionからエラーメッセージが渡っていない場合に限り初期化する
            if(is_null($this->errMsg))
            {
                $this->errMsg = array();
            }
            
            // トランザクション内前処理呼び出し
            $this->preExecute();
            
            // ロジック呼び出し
            $ret = $this->executeApp();
            
            // トランザクション内後処理呼び出し
            $this->postExecute();
            
            $this->infoLog("Commit SQL.", __FILE__, __CLASS__, __LINE__);
            if($this->Db->CompleteTrans() === false)
            {
                $this->infoLog("Failed commit trance.", __FILE__, __CLASS__, __LINE__);
                throw new AppException("Failed commit trance.");
            }
            
            // トランザクション外後処理
            $this->afterTrans();
            
            // 終了処理
            $this->finalize();
            
            if($this->exitFlag) {
                if(is_array($this->errMsg) && count($this->errMsg) > 0){
                    echo json_encode($this->errMsg);
                }
                exit();
            }
            else {
                return $ret;
            }
        }
        catch (AppException $e)
        {
            if($isTransStared)
            {
                if($this->Db->FailTrans() === false)
                {
                    $this->errorLog("Failed rollback trance.", __FILE__, __CLASS__, __LINE__);
                }
            }
            
            // エラーログをダンプ
            $this->exeptionLog($e, __FILE__, __CLASS__, __LINE__);
            
            // エラーメッセージを設定
            $errors = $e->getErrors();
            for($ii=0; $ii<count($errors); $ii++)
            {
                foreach ($errors[$ii] as $key => $val)
                {
                    $this->addErrMsg($key, $val);
                }
            }
            
            // ビジネスロジック生成クラス終了処理
            BusinessFactory::uninitialize();
            
            if($this->exitFlag) {
                if(is_array($this->errMsg)){
                    echo json_encode($this->errMsg);
                }
                exit();
            }
            else {
                return "error";
            }
        }
        catch (Exception $e)
        {
            if($isTransStared)
            {
                if($this->Db->FailTrans() === false)
                {
                    $this->errorLog("Failed rollback trance.", __FILE__, __CLASS__, __LINE__);
                }
            }
            // エラーログをダンプ
            $this->exeptionLog($e, __FILE__, __CLASS__, __LINE__);
            
            $this->addErrMsg("予期せぬエラーが発生しました");
            
            // ビジネスロジック生成クラス終了処理
            BusinessFactory::uninitialize();
            
            return "error";
        }
    }
    
    /**
     * エラーメッセージを追加
     * 
     * @param string $langKey
     * @param array $params
     */
    final protected function addErrMsg($key, $params=array())
    {
        // 初期化
        if(is_null($this->errMsg))
        {
            $this->errMsg = array();
        }
        
        // 言語リソース取得
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent('FilterChain');
        $smartyAssign =& $filterChain->getFilterByName('SmartyAssign');
        
        // 補間する
        array_push($this->errMsg, vsprintf($smartyAssign->getLang($key), $params));
        
        // Viewに渡す処理
        $container =& DIContainerFactory::getContainer();
        $request =& $container->getComponent("Request");
        $request->setParameter("errMsg", $this->errMsg);
    }
    
    /**
     * 各アクションは下記メソッドをオーバーライドすること
     */
    abstract protected function executeApp();
    
    /**
     * トランザクション外前処理：各アクションは下記メソッドをオーバーライドすること
     */
    abstract protected function beforeTrans();
    
    /**
     * トランザクション外後処理：各アクションは下記メソッドをオーバーライドすること
     */
    abstract protected function afterTrans();
    
    /**
     * トランザクション内前処理：各アクションは下記メソッドをオーバーライドすること
     */
    abstract protected function preExecute();
    
    /**
     * トランザクション内後処理：各アクションは下記メソッドをオーバーライドすること
     */
    abstract protected function postExecute();
    
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