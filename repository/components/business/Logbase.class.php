<?php
/**
 * $Id: Logbase.class.php 49950 2015-03-12 12:09:43Z tatsuya_koyasu $
 * 
 * entry log and update log
 * 
 * @author IVIS
 *
 */
require_once WEBAPP_DIR. '/modules/repository/components/FW/BusinessBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/business/Logmanager.class.php';

class Repository_Components_Business_Logbase extends BusinessBase
{
    const APP_EXCEPTION_KEY_REMOVING_LOG = 0;
    const APP_EXCEPTION_KEY_NO_EXECUTE_APP = 1;
    
    /**
     * execute each count log process
     *
     */
    public function execute()
    {
        // check removing log process
        $query = "SELECT status ". 
                 " FROM ". DATABASE_PREFIX. "repository_lock ". 
                 " WHERE process_name = ? ;";
        $params = array();
        $params[] = 'Repository_Action_Common_Robotlist';
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        // when execute removing log, throw exception
        for($cnt = 0; $cnt < count($result); $cnt++)
        {
            if(intval($result[$cnt]['status']) > 0){
                $exception = new AppException("repository_log_excluding", self::APP_EXCEPTION_KEY_REMOVING_LOG);
                $exception->addError("repository_log_excluding");
                throw $exception;
            }
        }
        
        $this->executeApp();
    }
    
    /**
     * abstract each count log process
     *
     */
    protected function executeApp()
    {
        throw new AppException("no AppExecute on extended class", self::APP_EXCEPTION_KEY_NO_EXECUTE_APP);
    }
    
    /**
     * get sitelicense log record
     * remove sitelicense itemtype
     *
     */
    protected function getSubQueryForSiteLicenseLog()
    {
        
    }
}
?>