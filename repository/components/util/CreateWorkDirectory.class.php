<?php
/**
 * $Id: CreateWorkDirectory.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
 *
 * プロセス制御を行う
 *
 * @author IVIS
 */
class Repository_Components_Util_CreateWorkDirectory
{
    /* 
     * ディレクトリ作成を試行する回数
     */
    const TRY_NUM = 100;
    
    public static function create($parentDir)
    {
        // 作成
        for($ii = 0; $ii < self::TRY_NUM; $ii++) {
            // ディレクトリ名決定
            $tmpDirPath = self::makeWorkDirectoryName($parentDir);
            // 作成実行
            $result = mkdir($tmpDirPath);
            if($result) {
                return $tmpDirPath."/";
            }
            usleep(1000);
        }
        
        return false;
    }
    
    private static function makeWorkDirectoryName($parentDir)
    {
        // 乱数を設定
        $rand = mt_rand(0,99999999);
        $tmpDirPath = $parentDir."tmp.". $rand;
        
        return $tmpDirPath;
    }
}
?>
