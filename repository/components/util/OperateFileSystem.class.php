<?php
/**
 * $Id: OperateFileSystem.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
 *
 * ファイルシステムの操作を行う
 *
 * @author IVIS
 */

class Repository_Components_Util_OperateFileSystem
{
    /**
     * ディレクトリを再帰的に削除する
     *
     * @params string $dir ディレクトリパス
     */
    public static function removeDirectory($dir)
    {
        chmod ($dir, 0777);
        if (!($handle = opendir($dir))) {
            return false;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($dir.$file)) {
                    self::removeDirectory($dir.$file);
                    if(file_exists($dir.$file)) {
                        rmdir($dir.$file);
                    }
                } else {
                    chmod ($dir.$file, 0777);
                    unlink($dir.$file);
                }
            }
        }
        closedir($handle);
        rmdir($dir);
    }
}
?>
