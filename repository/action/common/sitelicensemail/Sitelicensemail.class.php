<?php
// --------------------------------------------------------------------
//
// $Id: Sitelicensemail.class.php 35484 2014-05-09 10:54:58Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * Sitelicensemail
 *
 * @package     NetCommons
 * @author      T.Ichikawa(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Sitelicensemail extends RepositoryAction
{
    //----------------------------
    // Request parameters
    //----------------------------
    /**
     * login_id
     *
     * @var string
     */
    public $login_id = null;
    /**
     * password to login
     *
     * @var string
     */
    public $password = null;
    /**
     * authority_id
     *
     * @var string
     */    
    public $user_authority_id = "";
    /**
     * authority_id
     *
     * @var int
     */
    public $authority_id = "";
    /**
     * user_id
     *
     * @var string
     */
    public $user_id = "";
    
    public function executeForWeko() {
        $this->user_authority_id = "";
        $this->authority_id = "";
        $this->user_id = "";
        // check login
        $result = null;
        $error_msg = null;
        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false){
            print("Incorrect Login!\n");
            $this->failTrans();
            $this->exitAction();
            return false;
        }
        // check user authority id
        if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
            print("You do not have permission to send　sitelicense mail.\n");
            $this->failTrans();
            $this->exitAction();
            return false;
        }
        
        // サイトライセンスメールの送信フラグのチェック
        $query = "SELECT param_value FROM ".DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "send_sitelicense_mail_activate_flg";
        $result = $this->dbAccess->executeQuery($query, $params);
        if($result[0]["param_value"] == 0) {
            return "error";
        }
        
        $query = "SELECT no FROM ".DATABASE_PREFIX. "repository_send_mail_sitelicense ;";
        $result = $this->dbAccess->executeQuery($query);
        if(count($result) == 0) {
            // パラメータテーブルからサイトラインセンス情報を取得
            $query = "SELECT param_value FROM ".DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = ? ;";
            $params = array();
            $params[] = "site_license";
            $result = $this->dbAccess->executeQuery($query, $params);
            
            // 取得したサイトライセンス情報をサイトライセンスメール情報テーブルに挿入
            $sl_users = explode("|", $result[0]["param_value"]);
            $insert_sl = "";
            for($ii = 0; $ii < count($sl_users); $ii++) {
                $start_ip = "";
                $finish_ip = "";
                if($ii != 0) {
                    $insert_sl .= ", ";
                }
                $insert_sl .= "(". $ii;
                $sl_info = explode(",", $sl_users[$ii]);
                // 機関名
                if(strlen($sl_info[0]) == 0) {
                    $insert_sl .= ", ''";
                } else {
                    $insert_sl .= ", '".$sl_info[0]. "'";
                }
                // 開始IPアドレス
                if(strlen($sl_info[1]) != 0) {
                    $tmp_ip = explode(".", $sl_info[1]);
                    $start_ip = $tmp_ip[0].
                                sprintf("%03d", $tmp_ip[1]).
                                sprintf("%03d", $tmp_ip[2]).
                                sprintf("%03d", $tmp_ip[3]);
                }
                $insert_sl .= ", '".floatval($start_ip). "' ";
                // 終了IPアドレス
                if(strlen($sl_info[2]) == 0) {
                    $finish_ip = $start_ip;
                } else {
                    $tmp_ip = explode(".", $sl_info[2]);
                    $finish_ip = $tmp_ip[0].
                                 sprintf("%03d", $tmp_ip[1]).
                                 sprintf("%03d", $tmp_ip[2]).
                                 sprintf("%03d", $tmp_ip[3]);
                }
                $insert_sl .= ", '".floatval($finish_ip). "' ";
                // 機関メールアドレス
                if(strlen($sl_info[3]) == 0) {
                    $insert_sl .= ", ''";
                } else {
                    $insert_sl .= ", '".$sl_info[3]. "'";
                }
                $insert_sl .= ")";
            }
            $query = "INSERT INTO ".DATABASE_PREFIX ."repository_send_mail_sitelicense ".
                     "(no, organization_name, start_ip_address, finish_ip_address, mail_address) ".
                     "VALUES ".$insert_sl;
            $this->dbAccess->executeQuery($query);
            
            // サイトライセンス送信のバックグラウンド処理のリクエストを送信する
            // Call oneself by async
            
            if(!$this->callAnotherProcessByAsync())
            {
                // Print error message.
                print("Failed to send site license mail.\n");
                $this->failTrans();
                $this->exitAction();
                exit();
            } 
            
            // Print message.
            print("Start send site license mail.\n");
            
            return "success";
        }
        
        return "error";
    }
    
    /**
     * Call another process by async
     *
     * @return bool
     */
    public function callAnotherProcessByAsync()
    {
        // Request parameter for next URL
        $lang = $this->Session->getParameter("_lang");
        $nextRequest = BASE_URL."/?action=repository_action_common_background_sitelicensemail".
                       "&login_id=".$this->login_id."&password=".$this->password. "&lang=". $lang;
        
        // Call oneself by async
        $host = array();
        preg_match("/^https?:\/\/(([^\/]+)).*$/", BASE_URL, $host);
        $hostName = $host[1];
        if($hostName == "localhost"){
            $hostName = gethostbyname($_SERVER['SERVER_NAME']);
        }
        if($_SERVER["SERVER_PORT"] == 443)
        {
            $hostName = "ssl://".$hostName;
        }
        $handle = fsockopen($hostName, $_SERVER["SERVER_PORT"]);
        if (!$handle)
        {
            return false;
        }
        stream_set_blocking($handle, false);
        fwrite($handle, "GET ".$nextRequest." HTTP/1.0\r\n\r\n");
        fclose ($handle);
        
        return true;
    }
}

?>