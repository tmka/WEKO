<?php
/**
 * $Id: ZipUtility.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
 *
 * ZIPファイルの操作を行う
 *
 * @author IVIS
 */
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';

class Repository_Components_Util_ZipUtility
{
    /**
     * zipファイルに圧縮する
     *
     * ※ファイルを圧縮する場合は末尾にスラッシュを付けない(フォルダとして認識される)
     *
     * @params string $cmpFrom   圧縮元ファイル/フォルダパス
     * @params string $cmpTo     圧縮先ファイルパス
     */
    public static function compress($cmpFrom, $cmpTo)
    {
        // Update SuppleContentsEntry Y.Yamazawa --start--
        if ( file_exists($cmpFrom) ) {

            $zip = new ZipArchive();
            // ZIPファイルをオープン
            $res = $zip->open($cmpTo, ZipArchive::CREATE);

            // zipファイルのオープンに成功した場合
            if ($res === true) {

                // 圧縮するファイルを指定する
                $zip->addFile($cmpFrom);

                // ZIPファイルをクローズ
                $zip->close();
            }
            return true;
        }
        else {
            return false;
        }
        // Update SuppleContentsEntry Y.Yamazawa --end--
    }

    /**
     * zipファイルを解凍する
     *
     * @params string $extFrom   圧縮ファイルパス
     * @params string $extTo     解凍先フォルダパス
     * @return boolean zip解凍の成功失敗
     */
    public static function extract($extFrom, $extTo)
    {
        // Update SuppleContentsEntry Y.Yamazawa --start--
        if ( file_exists($extFrom) && file_exists($extTo) ) {

            // ZipArchiveを利用して解凍時の処理を低減しようとしたが、
            // 日本語のファイル名を解凍する際、EUC、UTF8、SJIS以外の不明な文字コードで
            // 解凍してしまい、文字化けしないようにすることができなかったので、
            // File_Archiveを利用する
            File_Archive::extract(
                File_Archive::read($extFrom . "/"),
                File_Archive::appender($extTo)
            );
//            $zip = new ZipArchive();
//            $res = $zip->open($extFrom);
//            // zipファイルのオープンに成功した場合
//            if ($res === true) {
//                // 圧縮ファイル内の全てのファイルを指定した解凍先に展開する
//                $rsult = $zip->extractTo(self::_addSlash($extTo));
//                if($rsult === false){
//                    return false;
//                }
//
//                // ZIPファイルをクローズ
//                $zip->close();
//            }else{
//                return false;
//            }

            return true;
        }
        else {
            return false;
        }
        // Update SuppleContentsEntry Y.Yamazawa --end--
    }

    /**
     * パスの最後に/を付ける
     * @param パス $str
     * @return Ambigous <string, unknown>
     */
    private static function _addSlash($str)
    {
        if (substr($str, -1) == '/') {
            $ret = $str;
        } else {
            $ret = $str.'/';
        }
        return $ret;
    }
}
?>
