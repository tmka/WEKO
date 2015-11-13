<?php
/**
 * $Id: Stream.class.php 48455 2015-04-08 10:53:40Z yuuya_yamazawa $
 *
 * 入出力例外クラス
 *
 * @author IVIS
 *
 */
class IOException extends Exception
{
    /**
     * エラーの発生したStream
     */
    private $errorStream = null;

    /**
     * コンストラクタ
     * @param Stream $errorStream エラーの発生したStream
     * @param string $message 例外メッセージ
     * @param string $code 例外コード
     * @param String $previous Exception
     */
    public function __construct ($errorStream ,$message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code,$previous);

        $this->errorStream = $errorStream;
    }

    /**
     * Streamクラス
     * @return Stream
     */
    public function getErrorStream()
    {
        return $this->errorStream;
    }
}
?>