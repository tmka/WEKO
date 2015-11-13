<?php
/**
 * $Id: LockPhysicalFile.class.php 56711 2015-08-19 13:21:44Z tomohiro_ichikawa $
 *
 * ファイルのロックを行う
 *
 * @author IVIS
 */

class Repository_Components_LockPhysicalFile
{
    /**
     * File to lock
     */
    public function lockFile($itemId, $attributeId, $fileNo) {
        $lockName = $itemId."_".$attributeId."_".$fileNo;
        $lockFile = self::getLockPath($lockName);
        
        return $this->lock($lockFile);
    }
    /**
     * File to unlock
     */
    public function unlockFile($handle, $itemId, $attributeId, $fileNo) {
        if($handle !== null){
            $lockName = $itemId."_".$attributeId."_".$fileNo;
            $lockFile = self::getLockPath($lockName);
            
            $this->unlock($handle, $lockFile);
        }
    }
    
    /**
     *  lock
     */
    private function lock($lockFile) {
        $ret = null;
        $handle = fopen($lockFile, "w");
        if(flock($handle, LOCK_EX)) {
            $ret = $handle;
        } else {
            fclose($handle);
        }
        
        return $ret;
    }
    
    /**
     * unlock
     */
    private function unlock($handle, $lockFile) {
        flock($handle, LOCK_UN);
        fclose($handle);
        unlink($lockFile);
    }
    
    /**
     * Get lock file path
     * @return string
     */
    private function getLockPath($lockName){
        $lockFile = "";
        $dirPath = BASE_DIR.'/webapp/uploads/repository/';
        $lockFile = $dirPath.$lockName.".lock";
        
        return $lockFile;
    }
}
?>
