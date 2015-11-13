<?php
require_once WEBAPP_DIR.'/modules/repository/components/FW/BusinessBase.class.php';

/**
 * $Id: Operationlog.class.php 49498 2015-03-06 06:07:18Z atsushi_suzuki $
 * 
 * 操作ログを挿入する
 * 
 * @author IVIS
 *
 */
class Repository_Components_Business_Operationlog extends BusinessBase{
    
    /**
     * 開始ログを挿入する
     * @param string $userId NCユーザーID
     * @param string $requestPrams リクエストパラメータ
     * @return int
     */
    public function startLog($userId,$requestPrams){
        return $this->SetLog(0, $userId, $requestPrams);
    }
    
    /**
     * 終了ログを挿入する
     * @param string $startLogId 対応する開始操作のログID
     * @param string $userId NCユーザーID
     * @param string $requestPrams リクエストパラメータ
     * @return int
     */
    public function endLog($startLogId,$userId,$requestPrams){
        return $this->SetLog($startLogId, $userId, $requestPrams);
    }
    
    /**
     * 操作ログをDBへ挿入する
     * @param string $startLogId 開始ログID
     * @param string $userId NCユーザーID
     * @param string $requestPrams リクエストパラメータ
     * @return int
     */
    private function SetLog($startLogId,$userId,$requestPrams){
        $tableName = "repository_operation_log";
        $logId = $this->Db->nextSeq($tableName);
        $query = "INSERT INTO {".$tableName."} ".
                 "(log_id, record_date, user_id, request_parameter, start_log_id) ".
                 "VALUES(?, NOW(), ?, ?, ?)";
        $params = array();
        $params[] = $logId;
        $params[] = $userId;
        $params[] = $requestPrams;
        $params[] = $startLogId;
        $result = $this->Db->execute($query, $params);
        if (!$result) {
            $this->Db->addError();
            return false;
        }
        
        return $logId;
    }
}
?>