<?php
/**
 * $Id: Stream.class.php 48455 2015-04-08 10:53:40Z yuuya_yamazawa $
 *
 * ストリーム基底クラス
 *
 * @author IVIS
 *
 */
abstract class Stream
{
    /**
     * Streamの読み込み
     * @param int $length 何バイト読み込むか
     * IOException 読み込み失敗時
     */
    abstract public function read($length);

    /**
     * Streamへの書き込み
     * @param string $string 書き込む文字列
     * @param int $length  何バイト書き込むか
     * IOException 書き込み失敗時
     */
    abstract public function write($string,$length = null);

    /**
     * Streamをクローズする
     * IOException クローズ失敗時
     */
    abstract public function close();
}
?>