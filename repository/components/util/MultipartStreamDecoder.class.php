<?php
// --------------------------------------------------------------------
//
// $Id: MultipartStreamDecoder.class.php 42605 2015-04-02 01:02:01Z yuya_yamazawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR.'/modules/repository/components/FW/IO/FileStream.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppException.class.php';

/**
 * マルチパートでアップロードされたファイルを読み取り
 * 指定したパスに吐き出す。
 *
 * @author IVIS
 *
 */
class Repository_Components_Util_MultipartStreamDecoder
{
    /**
     * ヘッダー,フッター部の境界読み込み最大バイト値
     * @var number
     */
    const READ_BOUNDARY_MAX_SIZE = 1024;

    /**
     * ファイル内容最大読み込みバイト数
     * @var number
     */
    const READ_FILE_MAX_SIZE = 4096;

    /**
     * マルチパートでアップロードされたファイルをデコードする
     * 1.引数チェック
     * 2.ファイルの最初の行の読み込み。Boundaryの取得
     * 3.ファイルのデコード
     * 4.不正データの場合の削除
     * @param FileStream $readFileStream php://inputのFileStreamクラス
     * @param string $oututFile 出力先パス
     * @throws AppException
     * @return array ファイル名リスト
     */
    public static function decodeMultiPartFile($readFileStream,$oututFile)
    {
        // ファイルの読み込みに失敗していた場合または出力先パスが指定されていなかった場合false
        if($readFileStream === false){
            throw new AppException("There is no streamData");
        }else if(!isset($oututFile)){
            throw new AppException("isset false outputFilePath");
        }

        // streamからboundaryを取得
        $boundary = Repository_Components_Util_MultipartStreamDecoder::readHeadBoundary($readFileStream,$tmp_buffer_data);

        // ファイルをデコードする
        $readContinueFlag = true;
        $deleteFlag = false;
        $fileList = array();
        while ($readContinueFlag){
            $readContinueFlag = Repository_Components_Util_MultipartStreamDecoder::decodePartFile($readFileStream,$oututFile,$boundary,$tmp_buffer_data,$decodedFile,$deleteFlag);
            if(isset($decodedFile)){
                array_push($fileList, $decodedFile);
            }
        }
        $readFileStream->close();

        // 不正データがある場合はファイルをすべて削除
        if($deleteFlag === true)
        {
            $path_parts = pathinfo($oututFile);
            $dir = $path_parts['dirname'];
            foreach ($fileList as $fileName)
            {
                unlink($dir."/".$fileName);
            }
        }

        return $fileList;
    }

    /**
     * ヘッダーの境界値までを読み込む
     * @param FileStream $readFileStream php://inputのFileStreamクラス
     * @param string $tmp_buffer_data 一時保存用buffer
     * @throws AppException
     * @return string Boundary
     */
    private static function readHeadBoundary($readFileStream,&$tmp_buffer_data)
    {
        // READ_BOUNDARY_MAX_SIZE分読み込む
        $readStreamData = $readFileStream->read(self::READ_BOUNDARY_MAX_SIZE);

        // READ_BOUNDARY_MAX_SIZE分読み込んだデータから\r\nの開始位置を取得
        $result = preg_match("/--[0-9]+\\r\\n/", $readStreamData,$match);
        if($result == 0){
            throw new AppException("There is no Boundary");
        }

        // \r\nで分割
        $partData = explode("\r\n", $readStreamData, 2);

        // \r\nの後のデータをすべてメンバのbuffer(一時保持用)に移動
        $tmp_buffer_data = $partData[1];

        // Boundaryを返す
        return $partData[0];
    }

    /**
     * ヘッダー部分を読み込む
     * @param FileStream $readFileStream php://inputのFileStreamクラス
     * @param string $oututFilePath 出力先パス
     * @param string $tmp_buffer_data 一時保存用buffer
     * @param string $fileName ファイル名
     * @throws AppException
     */
    private static function readHeader($readFileStream,$oututFilePath,&$tmp_buffer_data,&$fileName)
    {
        while (!$readFileStream->eof())
        {
            // READ_BOUNDARY_MAX_SIZE分読み込む
            $readStreamData = $readFileStream->read(self::READ_FILE_MAX_SIZE);

            // メンバのbufferと結合
            $joinData = $tmp_buffer_data.$readStreamData;

            // \r\n\r\nがあるか確認
            $boundaryStartPoint = strpos($joinData, "\r\n\r\n");
            if($boundaryStartPoint === false){
                // 読み込んだデータをbuffer(一時保持用)に保持
                $tmp_buffer_data = $joinData;

                // buffer(一時保持用)が4Kを超えた場合異常なデータとして例外を投げる
                $size = strlen($tmp_buffer_data);
                if($size > Repository_Components_Util_MultipartStreamDecoder::READ_FILE_MAX_SIZE)
                {
                    throw new AppException("There is no Header Info");
                }
            }
            else{
                // ヘッダー部分後のデータをメンバー(buffer(一時保持用))にセットする
                Repository_Components_Util_MultipartStreamDecoder::keepstreamDataAfterHeader($joinData, $oututFilePath,$tmp_buffer_data,$fileName);

                return;
            }
        }

        // \r\n\r\nがあるか確認
        $headerEndPoint = strpos($tmp_buffer_data, "\r\n\r\n");
        if($headerEndPoint === false){
            throw new AppException("There is no Header Info");
        }

        // ヘッダー部分後のデータをメンバー(buffer(一時保持用))にセットする
        Repository_Components_Util_MultipartStreamDecoder::keepstreamDataAfterHeader($tmp_buffer_data, $oututFilePath,$tmp_buffer_data,$fileName);
    }

    /**
     * ヘッダー部分後のデータをbuffer(一時保持用)に保持する
     * @param string $readStreamData
     * @param string $oututFilePath 出力先パス
     * @param string $tmp_buffer_data buffer(一時保持用)
     * @param string $fileName ファイル名
     * @throws AppException
     */
    private static function keepstreamDataAfterHeader($readStreamData,$oututFilePath,&$tmp_buffer_data,&$fileName)
    {
        // \r\n\r\nを区切りとしてデータを分ける
        $partData = explode("\r\n\r\n", $readStreamData, 2);

        // \r\nの後のデータをすべてメンバのbuffer(一時保持用)に移動
        $tmp_buffer_data = $partData[1];

        // ヘッダーからファイル名を取得
        $result = preg_match('/filename="([^"]+)"/', $partData[0],$match);
        if($result === false || $result == 0){
            throw new AppException("There is no Header Info");
        }
        // 出力先パスがディレクトリ名の時はヘッダー情報のファイル名をリストに詰める
        if(is_dir($oututFilePath))
        {
            $fileName = $match[1];
        }
        else{
            // 引数の出力先パスのファイル名をリストに詰める
            $path_parts = pathinfo($oututFilePath);
            $fileName = $path_parts["basename"];
        }
    }

    /**
     * ファイルをデコードする
     * @param FileStream $readFileStream php:inputのファイルポインタを持つFileStreamクラス
     * @param string $oututFilePath 出力先パス
     * @param string $boundary Boundary
     * @param string $tmp_buffer_data 一時保存用buffer
     * @param string $decodedFile デコードするファイル名
     * @param boolean $deleteFlag デコードしたファイルの削除フラグ
     * @return boolean ファイルのデコード再開フラグ
     */
    private static function decodePartFile($readFileStream,$oututFilePath,$boundary,&$tmp_buffer_data,&$decodedFile,&$deleteFlag)
    {
        // ヘッダーの読み込み
        Repository_Components_Util_MultipartStreamDecoder::readHeader($readFileStream,$oututFilePath,$tmp_buffer_data,$decodedFile);

        // ファイルの出力先パスの取得
        $outputPath = Repository_Components_Util_MultipartStreamDecoder::outputFilePath($oututFilePath,$decodedFile);

        $outputFileStream = FileStream::open($outputPath, "w");

        // ファイルのデコード
        $result = Repository_Components_Util_MultipartStreamDecoder::decodeFile($readFileStream,$outputFileStream,$boundary,$tmp_buffer_data,$deleteFlag);

        $outputFileStream->close();

        return $result;
    }

    /**
     * 出力先パスの取得
     * @param 出力先パス $path
     * @param ファイル名 $decodedFile
     * @return 出力先パス
     */
    private static function outputFilePath($path,&$decodedFile)
    {
        $outputPath = $path;
        // 引数で渡されたパスがディレクトリパスならファイル名をくっつける
        if(is_dir($path))
        {
            $lastStr = mb_substr($path,-1);
            if($lastStr != "/"){
                $outputPath = $path."/".$decodedFile;
            }
            else{
                $outputPath = $path.$decodedFile;
            }
        }
        else
        {
            // ファイルが既に存在している場合はファイル名を入れ替える
            $path_parts = pathinfo($path);
            if(file_exists($path)){
                $dir = $path_parts['dirname'];
                $outputPath = $dir."/".$decodedFile;
            }
            else{
                $decodedFile = $path_parts['basename'];
            }
        }

        return $outputPath;
    }

    /**
     * ファイル内容をデコードする
     * @param FileStream $readFileStream php:inputのファイルポインタを持つFileStreamクラス
     * @param FileStream $outputFileStream 出力先のファイルポインタを持つFileStreamクラス
     * @param string $boundary Boundary
     * @param string $tmp_buffer_data 一時保存用buffer
     * @param boolean $deleteFlag デコードしたファイルの削除フラグ
     * @return boolean デコードを再度行う場合はtrue 行わない場合はfalse
     */
    private static function decodeFile($readFileStream,$outputFileStream,$boundary,&$tmp_buffer_data,&$deleteFlag)
    {
        // StreamDataを読み込んだ際、StreamデータのBoudanryサイズ分は信用できないためBoundaryサイズ分保持する
        $tmpStreamData = "";

        while (!$readFileStream->eof())
        {
            // READ_FILE_MAX_SIZE分読み込む
            $readStreamData = $readFileStream->read(self::READ_FILE_MAX_SIZE);

            // 読み込んだデータとbuffer(一時保持用),Boundaryサイズ分の保持データを結合する
            $joinData = $tmp_buffer_data.$tmpStreamData.$readStreamData;

            // Boundaryが存在するか確認
            $result = Repository_Components_Util_MultipartStreamDecoder::existBoundary($joinData,$outputFileStream,$boundary,$tmp_buffer_data,$tmpStreamData,$partDataAfterBoundary);
            if($result === false){
                // 再度読み込みを行う
                continue;
            }

            if(isset($partDataAfterBoundary)){
                // Boundary後のデータをbuffer(一時保持用)に保持する
                $tmp_buffer_data = $partDataAfterBoundary;

                // 読み込みを続ける
                return true;
            }
            else{
                // 読み込みを続けない
                return false;
            }
        }

        // EOFの場合に残っていたデータを出力する
        $result = Repository_Components_Util_MultipartStreamDecoder::outputFileRestData($outputFileStream,$boundary,$tmp_buffer_data);
        if($result === false){
            // 不正データの場合は削除を行うようにする
            $deleteFlag = true;
        }
        else{
            $deleteFlag = false;
        }

        // 読み込みを続けない
        return false;
    }

    /**
     * Boundaryが存在するか
     * @param string $streamReadData Streamから読み込んだデータ
     * @param FileStream $outputFileStream 出力先のファイルポインタ
     * @param string $boundary Boundary値
     * @param string $tmp_buffer_data 一時保存用buffer
     * @param string $tmpStreamData Streamから読み込んだデータから最後の文字列をBoudaryサイズ分切り取ったデータ
     * @param string $partDataAfterBoundary Boundary文字列後に存在するデータ
     * @return boolean true:Boundaryが存在 false:Boundaryが存在しない
     */
    private static function existBoundary($streamReadData,$outputFileStream,$boundary,&$tmp_buffer_data,&$tmpStreamData,&$partDataAfterBoundary)
    {
        // \r\n後のデータにboundaryがあるか確認
        $boundaryPoint = strpos($streamReadData, $boundary);
        if($boundaryPoint === false){
            $boundaryByteSize = strlen($boundary."\r\n");
            $streamReadDataSize = strlen($streamReadData);
            $outputReadDataSize = $streamReadDataSize - $boundaryByteSize;

            // Boundaryサイズ分後ろのデータを切り出しておく
            $tmpStreamData = substr($streamReadData, -$boundaryByteSize);

            // 確定データを出力
            $outputData = substr($streamReadData, 0 , $outputReadDataSize);

            // データを出力する
            $outputFileStream->write($outputData);

            // buffer(一時保持用)を空にする
            $tmp_buffer_data = "";

            // 再度読み込みを行う
            return false;
        }

        // Boundaryを区切りとしてデータを分ける
        $partData = explode($boundary, $streamReadData, 2);

        // Boudaryの後に文字列が続いている場合は次のデータ情報
        if(!isset($partData[1])){
            $partDataAfterBoundary = $partData[1];
        }

        // データを出力する
        $outputFileStream->write($partData[0]);

        return true;
    }

    /**
     * EOFの場合に残っていたデータを出力する
     * @param FileStream $outputFileStream 出力先のファイルポインタ
     * @param string $boundary Boundary値
     * @param string $tmp_buffer_data 一時保存用buffer
     * @throws AppException
     * @return boolean データの出力成功はtrue 失敗の場合はfalse
     */
    private static function outputFileRestData($outputFileStream,$boundary,&$tmp_buffer_data)
    {
        $boundarybeforePoint = strpos($tmp_buffer_data, "\r\n");
        if($boundarybeforePoint === false){
            throw new AppException("There is no Boundary");
        }

        // \r\nを区切りとしてデータを分ける
        $partData = explode("\r\n", $tmp_buffer_data, 2);

        // \r\n前のデータを出力する
        $outputFileStream->write($partData[0]);

        // \r\n後のデータにboundaryがあるか確認
        $boundaryPoint = strpos($partData[1], $boundary);
        if($boundaryPoint === false){
            return false;
        }

        return true;
    }
}
?>