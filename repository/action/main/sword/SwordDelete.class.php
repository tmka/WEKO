<?php
// --------------------------------------------------------------------
//
// $Id: SwordDelete.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/main/item/detail/Detail.class.php';

/**
 * Sword delete class
 *
 */
class SwordDelete extends RepositoryAction
{
    // member
    private $itemId = null;     // item_id
    private $itemNo = null;     // item_no
    private $deleteUser = null; // login_id of delete user
    private $loginId = null;    // login_id
    private $password = null;   // password
    
    //-----------------------------------------------
    // Public method
    //-----------------------------------------------
    /**
     * Constructor
     *
     * @param Session $session
     * @param Db $db
     * @param string $transStartDate
     * @access public
     */
    public function SwordDelete($session, $db, $transStartDate)
    {
        if(!isset($session) || !isset($db) || !isset($transStartDate))
        {
            return null;
        }
        $this->Session = $session;
        $this->Db = $db;
        $this->dbAccess = new RepositoryDbAccess($this->Db);
        $this->TransStartDate = $transStartDate;
        $this->setConfigAuthority();
    }
    
    /**
     * Init
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $loginId
     * @param string $password
     * @param string $deleteUser
     */
    public function init($itemId, $itemNo, $loginId, $password, $deleteUser="")
    {
        $this->itemId = $itemId;
        $this->itemNo = $itemNo;
        $this->loginId = $loginId;
        $this->password = $password;
        if(strlen($deleteUser)>0)
        {
            $this->deleteUser = $deleteUser;
        }
        else
        {
            $this->deleteUser = $loginId;
        }
    }
    
    /**
     * Execute sword delete
     *
     * @param int $statusCode
     * @return bool
     */
    public function executeSwordDelete(&$statusCode)
    {
        try
        {
            // Init status code
            $statusCode = 204;
            
            // Check user authority
            if(!$this->checkSwordLogin($statusCode, $userId))
            {
                // Error
                return false;
            }
            
            // Check delete
            $result = $this->checkDelete($this->itemId, $this->itemNo, $userId);
            if($result == false)
            {
                // Error
                $statusCode = 500;
                return false;
            }
            
            // Get delete item's mod_date
            $modDate = $this->getDeleteItemModDate($this->itemId, $this->itemNo);
            
            // Set user id
            $orgUserId = $this->Session->getParameter("_user_id");
            $this->Session->setParameter("_user_id", $userId);
            
            // Repository_Action_Main_Item_Detailクラスをインスタンス化
            $detailAction = new Repository_Action_Main_Item_Detail();
            $detailAction->Session = $this->Session;
            $detailAction->Db = $this->Db;
            $detailAction->TransStartDate = $this->TransStartDate;
            $detailAction->item_id = $this->itemId;
            $detailAction->item_no = $this->itemNo;
            $detailAction->item_update_date = $modDate;
            $result = $detailAction->execute();
            $this->Session->setParameter("_user_id", $orgUserId);
            if($result != "delete_success")
            {
                // Error
                $statusCode = 500;
                return false;
            }
            
            // Success
            $statusCode = 204;
            return true;
        }
        catch(Exception $ex)
        {
            // Error
            $statusCode = 500;
            return false;
        }
    }
    
    /**
     * Check sword login
     *
     * @param int $statusCode
     * @param string $userId
     * @return bool
     */
    public function checkSwordLogin(&$statusCode, &$userId)
    {
        $userId = "";
        if(strlen($this->loginId)==0 || strlen($this->password)==0)
        {
            $statusCode = 401;
            return false;
        }
        
        // send http request
        $option = array( 
            "timeout" => "10",
            "allowRedirects" => true, 
            "maxRedirects" => 3, 
        );
        $proxy = $this->getProxySetting();
        if($proxy['proxy_mode'] == 1)
        {
            $option = array( 
                    "timeout" => "10",
                    "allowRedirects" => true, 
                    "maxRedirects" => 3,
                    "proxy_host"=>$proxy['proxy_host'],
                    "proxy_port"=>$proxy['proxy_port'],
                    "proxy_user"=>$proxy['proxy_user'],
                    "proxy_pass"=>$proxy['proxy_pass']
                );
        }
        
        $url = BASE_URL.'/?action=repository_action_main_sword_login'.
                '&login_id='.$this->loginId.'&password='.$this->password;
        $http = new HTTP_Request($url, $option);
        $response = $http->sendRequest(); 
        if (!PEAR::isError($response))
        {
            $body = $http->getResponseBody();
            if(strpos($body, 'success') === false)
            {
                $statusCode = 401;
                return false;
            }
            
            $query = "SELECT user_id FROM ".DATABASE_PREFIX."users ".
                     "WHERE login_id = ?; ";
            $params = array();
            $params[] = $this->deleteUser;
            $result = $this->Db->execute($query, $params);
            if($result === false || count($result)!=1){
                // is not user
                $statusCode = 401;
                return false;
            }
            $userId = $result[0]["user_id"];
        }
        else
        {
            $statusCode = 401;
            return false;
        }
        
        return true;
    }
    
    /**
     * Set http header
     * 
     * @param int $code
     */
    public function setHeader($code)
    {
        // protocol
        if(isset($_SERVER['SERVER_PROTOCOL']))
        {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        }
        else
        {
            $protocol = 'HTTP/1.0';
        }
        
        $text = "";
        switch ($code)
        {
            case 200:
                $text = 'OK';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            default:
                $code = 500;
                $text = 'Internal Server Error';
                break;
        }
        
        // header
        header($protocol . ' ' . $code . ' ' . $text);
        if($code == 401)
        {
            header("WWW-Authenticate: Basic realm=\"SWORD\"");
        }
        if($code != 200 && $code != 204)
        {
            header("X-Error-Code: ". $text);
        }
        return;
    }
    
    //-----------------------------------------------
    // Private method
    //-----------------------------------------------
    /**
     * Check delete
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @return bool
     */
    private function checkDelete($itemId, $itemNo, $userId)
    {
        // -----------------------
        // パラメーターチェック
        // -----------------------
        // リクエストパラメータのitem_id, item_noをチェック
        if(intval($this->itemId)==0 || intval($this->itemNo)==0)
        {
            // 不正なitem_id or item_no
            return false;
        }
        
        // -----------------------
        // アイテム削除権限チェック
        // -----------------------
        // Check delete authority
        if(!$this->checkDeleteAuthority($itemId, $itemNo, $userId))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check delete authority
     *
     * @param int $itemId
     * @param int $itemNo
     * @param string $userId
     * @return bool
     */
    private function checkDeleteAuthority($itemId, $itemNo, $userId)
    {
        if(strlen($userId)==0)
        {
            return false;
        }
        
        // user_id から user_auth_id, auth_id を取得
        $query = "SELECT role_authority_id FROM ".DATABASE_PREFIX."users ".
                 "WHERE user_id = ?; ";
        $params = array();
        $params[] = $userId;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)!=1){
            return false;
        }
        $query = "SELECT user_authority_id FROM ".DATABASE_PREFIX."authorities ".
                 "WHERE role_authority_id = '".$result[0]["role_authority_id"]."' ";
        $result = $this->Db->execute($query);
        if($result === false || count($result) != 1){
            return false;
        }
        $user_auth_id = $result[0]["user_authority_id"];
        $auth_id = $this->getRoomAuthorityID($userId);
        
        // このユーザーが管理者か否か
        if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
        {
            // WEKO管理者
            return true;
        }
        else
        {
            // 一般ユーザーならばアイテム登録者と一致するかどうか
            $query = "SELECT ".RepositoryConst::DBCOL_COMMON_INS_USER_ID." ".
                     "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." ".
                     "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = ? ".
                     "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = ? ;";
            $params = array();
            $params[] = $itemId;
            $params[] = $itemNo;
            $result = $this->Db->execute($query, $params);
            if($result === false)
            {
                return false;
            }
            if($result[0][RepositoryConst::DBCOL_COMMON_INS_USER_ID] == $userId)
            {
                // アイテム登録者のuser_idと一致
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get delete item's mod_date
     *
     * @param int $itemId
     * @param int $itemNo
     * @return string
     */
    private function getDeleteItemModDate($itemId, $itemNo)
    {
        $query = "SELECT ".RepositoryConst::DBCOL_COMMON_MOD_DATE." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return "";
        }
        
        return $result[0][RepositoryConst::DBCOL_COMMON_MOD_DATE];
    }
}
?>
