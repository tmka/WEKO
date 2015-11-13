<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/IO/Stream.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/IO/IOException.class.php';

/**
 * $Id: FileStream.class.php 48455 2015-04-08 10:53:40Z yuuya_yamazawa $
 *
 * ファイルストリームクラス
 *
 * @author IVIS
 *
 */
class FileStream extends Stream
{
    /**
     * ファイルパス
     * @var string ファイルパス名
     */
    private $filePath = "";

    /**
     * fopenした後のファイルポインタ
     * @var resource
     */
    private $fp = null;

    /**
     * ファイルパスの取得
     * @return string ファイルパス
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * コンストラクタ
     * @param string $filePath ファイルパス
     */
    protected function __construct($fp,$filePath)
    {
        $this->fp = $fp;
        $this->filePath = $filePath;
    }

    /**
     * ファイルまたは URL をオープンする
     * @param string $filePath ファイルパス
     * @param string $mode アクセス形式 fopen参照
     * @throws InvalidArgumentException
     * @throws IOException
     * @return FileStream FileStreamクラス
     */
    public static function open($filePath ,$mode)
    {
        if(!isset($filePath) || strlen($filePath) == 0){
            throw new InvalidArgumentException("Repository_Components_FW_IO_FileStream::open filePath null");
        }
        else if(!isset($mode) || strlen($mode) == 0)
        {
            throw new InvalidArgumentException("Repository_Components_FW_IO_FileStream::open mode null");
        }

        $openResult = fopen($filePath, $mode);
        $fileStream = new FileStream($openResult,$filePath);
        if($openResult === false)
        {
            throw new IOException($fileStream,"open");
        }

        return $fileStream;
    }

    /**
     * Streamの読み込み
     * @param int $length 何バイト読み込むか
     * IOException 読み込み失敗時
     */
    public function read($length)
    {
        if($this->fp === false)
        {
            return false;
        }

        $readResult = fread($this->fp, $length);
        if($readResult === false)
        {
            $this->close();

            throw new IOException($this,"read");
        }

        return $readResult;
    }

    /**
     * Streamへの書き込み
     * @param string $string 書き込む文字列
     * @param int $length  一度に書き込む最大バイト数
     * IOException 書き込み失敗時
     * @return string|boolean 書き込んだバイト数、または、fopenした時のファイルポインタがfalseの場合はfalse
     */
    public function write($string,$length = null)
    {
        if($this->fp === false)
        {
            return false;
        }

        // lengthがnullの場合、0バイトと認識されてファイルに空文字が入力されてしまうため判定処理を行う
        if(isset($length))
        {
            $writeResult = fwrite($this->fp,$string,$length);
        }
        else
        {
            $writeResult = fwrite($this->fp,$string);
        }

        if($writeResult === false)
        {
            $this->close();

            throw new IOException($this,"write");
        }

        return $writeResult;
    }

    /**
     * fopenで開いたファイルポインタがファイル終端に達しているかどうか調べる
     * @return boolean EOF に達している場合はtrue その他はfalse
     */
    public function eof()
    {
        if($this->fp === false)
        {
            return false;
        }

        return feof($this->fp);
    }

    /**
     * Streamをクローズする
    */
    public function close()
    {
        if($this->fp === false)
        {
            return;
        }

        fclose($this->fp);

        $this->fp = false;
    }
}
?>