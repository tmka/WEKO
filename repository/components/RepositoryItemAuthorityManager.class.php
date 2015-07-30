<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryItemAuthorityManager.class.php 41628 2014-09-17 02:01:48Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

class RepositoryItemAuthorityManager extends RepositoryLogicBase
{
    /**
     * check item public flg
     *
     * @param int $item_id item_id
     * @param int $item_no item_no
     * @param bool $harvest_flg harvest_flg
     * @return bool when true is public.
     *              when false is close. 
     */
    public function checkItemPublicFlg($item_id, $item_no, $adminBaseAuth, $adminRoomAuth, $harvest_flg=null)
    {
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->transStartDate);
        
        // check item public
        $query = "SELECT ins_user_id ".
                " FROM ".DATABASE_PREFIX."repository_item ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                " AND shown_status = ? ".
                " AND shown_date <= '".$this->transStartDate."' ".
                 "AND is_delete = ? ";
        $param = array();
        $param[] = $item_id;
        $param[] = $item_no;
        $param[] = 1;
        $param[] = 0;
        $result = $this->dbAccess->executeQuery($query, $param);
        
        if($result === false){
            return false;
        } else if(count($result) == 0){
            return false;
        } else if(count($result) > 1){
            return false;
        }
        
        // check position index public
        $query = "SELECT index_id ".
                " FROM ".DATABASE_PREFIX."repository_position_index ".
                " WHERE item_id = ? ".
                " AND item_no = ? ".
                " AND is_delete = 0 ; ";
        $param = array();
        $param[] = $item_id;
        $param[] = $item_no;
        $result = $this->dbAccess->executeQuery($query, $param);
        if($result === false){
            echo $this->Db->mysqlError();
            return false;
        } else if(count($result) == 0){
            return false;
        }else if(count($result) > 0){
            for($ii=0; $ii<count($result); $ii++){
                $public_index = $repositoryIndexAuthorityManager->getPublicIndex($harvest_flg, $adminBaseAuth, $adminRoomAuth, $result[$ii]["index_id"]);
                if(count($public_index) > 0){
                    // index is public
                    // and item public
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * call superclass' __construct
     *
     * @param var $session Session
     * @param var $dbAccess Db
     * @param string $transStartDate TransStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        parent::__construct($session, $dbAccess, $transStartDate);
    }
}

?>
