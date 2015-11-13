<?php
// --------------------------------------------------------------------
//
// $Id: Filecleanup.class.php 56959 2015-08-24 04:53:10Z tomohiro_ichikawa $
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

/**
 * File clean-up
 *
 * @package     NetCommons
 * @author      H.Goto(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_Action_Common_Filecleanup extends RepositoryAction
{
    // 削除実行を許可する更新時間(TimeStanp)
    const TIME_LAG = 86400;
    
    // リクエストパラメータを受け取るため
    var $login_id = null;
    var $password = null;
    
    // ユーザの権限レベル
    var $user_authority_id = "";
    var $authority_id = '';

    var $file_clean_up_last_date = "";
    
    function execute()
    {
        try {
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // check login
            $result = null;
            $error_msg = null;
            $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
            if($return == false){
                print("Incorrect Login!\n");
                return false;
            }
            
            // check user authority id
            if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
                print("You do not have permission to update.\n");
                return false;
            }

            // Delete it anything other than directory 『flash』 and 『files』
            $dirPath = BASE_DIR.'/webapp/uploads/repository';
            // 1時間以内に更新されたファイルは削除しない
            $nowTimeStanp = strtotime($this->TransStartDate);
            if ($handle = opendir("$dirPath")) {
                while (false !== ($item = readdir($handle))) {
                    // "files" "flash" "カレント" "親ディレクトリ" 以外かつ ファイル更新日が1時間以上前だったら削除を実行する
                    if (($item != "flash" && $item != "files" && $item != "." && $item != "..") &&
                        ($nowTimeStanp - filemtime($dirPath."/".$item)) > self::TIME_LAG) {
                          if (is_dir("$dirPath/$item")) {
                            $this->removeDirectory("$dirPath/$item");
                        } else {
                            unlink("$dirPath/$item");
                        }
                    }
                }
                closedir($handle);
            }
            
            // ----------------------------------------------------
            // Add Last Reset Ranking Date To repository_parameter 
            // ----------------------------------------------------
            $DATE = new Date();
            $execute_time = str_replace("-","/",$DATE->getDate());

            $params = null;             // パラメタテーブル更新用クエリ           
            $params[] = '';             // param_value
            $params[] = $this->Session->getParameter("_user_id");// mod_user_id
            $params[] = $this->TransStartDate;              // mod_date
            $params[] = '';                                 // param_name
            // 開始日時
            $params[0] = $execute_time;                     // param_value
            $params[3] = 'file_clean_up_last_date';         // param_name
            $result = $this->updateParamTableData($params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("fulltextindex_starttime update failed : %s", $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            
            // finalize
            $result = $this->exitAction();  // If Transaction is success, it is committed
            print("Successfully updated.\n");
            return 'success';
        }
        catch (RepositoryException $Exception)
        {
            //エラーログ出力
            $this->logFile(
                "Repository_Action_Edit_ResetRanking",              //クラス名
                "execute",                      //メソッド名
                $Exception->getCode(),          //ログID
                $Exception->getMessage(),       //主メッセージ
                $Exception->getDetailMsg() );   //詳細メッセージ           
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
            //異常終了
            return "error";
        }
    }
}
?>