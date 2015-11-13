<?php
abstract class App_Exception_PreviousNativeAbstract extends Exception
{
    public static $printPrevious = true;
    
    public function __toString() {
        $result   = array();
        $result[] = sprintf("Exception '%s' with message '(%s) %s' in %s:%d", get_class($this), $this->code, $this->message, $this->file, $this->line);
        $result[] = '---[Stack trace]:';
        $result[] = $this->getTraceAsString();
        
        if (self::$printPrevious) {
            $previous = $this->getPrevious();
            if ($previous) {
                do {
                    $result[] = '---[Previous exception]:';
                    $result[] = sprintf("Exception '%s' with message '(%s) %s' in %s:%d", get_class($previous), $previous->getCode(), $previous->getMessage(), $previous->getFile(), $previous->getLine());
                    $result[] = '---[Stack trace]:';
                    $result[] = $previous->getTraceAsString();
                } while(method_exists($previous, 'getPrevious') && ($previous = $previous->getPrevious()));
            }
        }
        
        return implode("\r\n", $result);
    }
}

if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
    abstract class App_Exception_PreviousAbstract extends App_Exception_PreviousNativeAbstract {}
}
else {
    abstract class App_Exception_PreviousLegacyAbstract extends App_Exception_PreviousNativeAbstract {
        protected $previous;
        
        public function __construct($message, $code = 0, Exception $previous = null) {
            $this->previous = $previous;
            
            parent::__construct($message, $code);
        }
        
        public function getPrevious() {
            return $this->previous;
        }
    }
    abstract class App_Exception_PreviousAbstract extends App_Exception_PreviousLegacyAbstract {}
}

/**
 * $Id: AppException.class.php 48455 2015-02-16 10:53:40Z atsushi_suzuki $
 * 
 * 拡張例外クラス
 *   PHP 5.3.0 未満でもインナーエクセプションを扱えるよう対応
 * 
 * @author IVIS
 */
class AppException extends App_Exception_PreviousAbstract
{
    /**
     * エラーメッセージ一覧
     *
     * @var array
     */
    private $errors =array();
    
    /**
     * 例外を再定義し、メッセージをオプションではなくする
     * 
     * @params string $message 例外メッセージ
     * @params int $code エラーコード
     * @params Exception インナーエクセプション
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        // 全てを正しく確実に代入する
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * エラー追加
     *
     * @param string $errorKey
     * @param array $errorParams
     */
    public function addError($errorKey, $errorParams=array())
    {
        array_push($this->errors, array());
        $this->errors[count($this->errors)-1][$errorKey] = $errorParams;
    }
    
    /**
     * エラーキーの存在チェック
     * 
     * @param string $key 検索するエラーキー
     * @return bool ture:存在する/false:存在しない
     */
    public function existsError($key)
    {
        for($ii=0; $ii<count($this->errors); $ii++)
        {
            if(array_key_exists($key, $this->errors[$ii]))
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * エラーメッセージ一覧
     * 
     * @return array:
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
?>