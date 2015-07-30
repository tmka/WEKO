<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 43857 2014-11-11 08:54:28Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/action/edit/import/ImportCommon.class.php';;

/**
 * [[import終了時、確認画面表示用action]]
 *
 * @package     [[package名]]
 * @access      public
 * @version 1.0 新規作成
 */
class Repository_Action_Edit_Import_Confirm extends RepositoryAction
{
    var $index_id = null;   // 画面で指定されたインデックスIDリスト
    var $CheckedIds = null; // チェックされているインデックスのID列(|区切り)
    
    var $Session = null;
    
    // Add review mail setting 2009/09/30 Y.Nakao --start--
    var $mailMain = null;
    // Add review mail setting 2009/09/30 Y.Nakao --end--
    
    /**
     * [[インポート処理大元]]
     *
     * Zipファイルから解凍
     * ->アイテムをZipにあるぶん登録
     * ->すでに登録済みは登録しない(他に登録済みがなくても)
     * ->アイテムタイプはかぶることがある
     * 
     * @access  public
     */
    function execute()
    {
        try {
            // セッション情報が設定されていない場合は、異常終了とする
            if ($this->Session != null) {
                // セッション情報に設定されているログイン情報を取得する
                $user_id = $this->Session->getParameter("_user_id");
            } else {
                // エラー処理を記述する。（未実装）
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                $this->Session->setParameter("error_msg", "Not setting Session.");
                return 'error';
            }

            // init action
            $result = $this->initAction();
            if ( $result == false ){
                $this->Session->setParameter("error_msg", "init action error.");
                return 'error';
            }
            
            // error msg remove
            $this->Session->removeParameter("error_msg");
            $this->Session->removeParameter("importmode");
            
            // 2008.03.24 S.Kawasaki チェックされたインデックス情報を分解
            //echo $this->CheckedIds . '<br>'; 
            $this->index_id = array();
            if( $this->CheckedIds != null && $this->CheckedIds != '' ){
                $this->index_id = explode('|', $this->CheckedIds);
            }
            // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --start--
            $indice = array();
            $indice = $this->addPrivateTreeInPositionIndex($indice, $this->Session->getParameter("_user_id"));
            for($ii=0; $ii<count($indice); $ii++)
            {
                if(!is_numeric(array_search($indice[$ii]['index_id'], $this->index_id)))
                {
                    array_push($this->index_id, $indice[$ii]['index_id']);
                }
            }
            // Add specialized support for open.repo "private tree public" Y.Nakao 2013/06/21 --end--
            if( count($this->index_id) < 1 ){               
                $this->Session->setParameter("error_msg", "Not select index.");
                return 'error';
            }
            
            // upload zip file extract folder pass
            $tmp_dir = $this->extraction();
            if($tmp_dir == false){
                // not zip error
                $this->Session->setParameter("error_msg", "Import file is not the 'ZIP' format.");
                return 'error';
            }
            
            // Add review mail setting 2009/09/30 Y.Nakao --start--
            /////////////////////////////////////////
            // check send review mail
            /////////////////////////////////////////
            // 新規査読アイテム登録メール送信処理
            // 査読・承認を行うか否か
            $query = "SELECT `param_value` ".
                     "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                     "WHERE `param_name` = 'review_mail_flg';";
            $ret = $this->Db->execute($query);
            if ($ret === false) {
                array_push($error_msg, $this->Db->ErrorMsg());
                // roll back
                $this->failTrans();
                return 'error';
            }
            $review_mail_flg = $ret[0]['param_value'];
            // 査読対象コンテンツ有無
            $review_flg = false;
            
            /////////////////////////////////////////
            // create review mail
            /////////////////////////////////////////
            // 言語リソース取得
            // get lang resource
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            // send review mail
            // 査読通知メールを送信する
            // 件名
            // set subject
            $subj = $smartyAssign->getLang("repository_mail_review_subject");
            $this->mailMain->setSubject($subj);
            
            // page_idおよびblock_idを取得
            $block_info = $this->getBlockPageId();
            // メール本文をリソースから読み込む
            // set Mail body
            $body = '';
            $body .= $smartyAssign->getLang("repository_mail_review_body")."\n\n";
            $body .= $smartyAssign->getLang("repository_mail_review_contents")."\n";
            $body .= $smartyAssign->getLang("repository_mail_review_title");
            // Add review mail setting 2009/09/30 Y.Nakao --end--
            
            /////////////////////////////////////////
            // import item
            /////////////////////////////////////////
            // import common class new
            $import_common = new ImportCommon($this->Session, $this->Db, $this->TransStartDate);
            // get XML data
            $error_list = array();
            $result = $import_common->XMLAnalysis($tmp_dir, $array_item_data, $error_list);
            if($result === false){
                if(count($error_list) > 0) {
                    // Add for import error list 2014/11/04 T.Koyasu --start--
                    // remove error message in ImportCommon
                    $this->Session->removeParameter("error_msg");
                    
                    $error_info = array();
                    
                    for($ii = 0; $ii < count($error_list); $ii++) {
                        $error_info[$ii] = array();
                        $error_info[$ii]["error"] = $error_list[$ii]->error; 
                        $error_info[$ii]["title"] = $error_list[$ii]->title; 
                        $error_info[$ii]["item_id"] = $error_list[$ii]->item_id; 
                        $error_info[$ii]["attr_name"] = $error_list[$ii]->attr_name; 
                        $error_info[$ii]["input_value"] = $error_list[$ii]->input_value; 
                        $error_info[$ii]["regist_value"] = $error_list[$ii]->regist_value;
                        $error_info[$ii]["error_no"] = $error_list[$ii]->error_no;
                    }
                    
                    $this->Session->setParameter("error_info", $error_info);
                    // Add for import error list 2014/11/04 T.Koyasu --end--
                }
                // error action
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
                // ROLLBACK
                $this->failTrans();
                throw $exception;
            }
            //////////////////////////////
            // Insert item type
            //////////////////////////////
            $result = $import_common->itemtypeEntry($array_item_data['item_type'], $tmp_dir, $item_type_info, $error_msg);
            if($result === false){
                // error action
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
                // ROLLBACK
                $this->failTrans();
                throw $exception;
            }
            // check itemtype num item num
            if(count($item_type_info) != count($array_item_data['item'])){
                $this->Session->setParameter("error_msg", "XML不備");
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
                // ROLLBACK
                $this->failTrans();
                throw $exception;
            }
            
            ////////////////////////////////////////
            // insert item
            ////////////////////////////////////////
            $tmp_array = array();
            $array_item = array();
            for($nCnt=0;$nCnt<count($array_item_data['item']);$nCnt++){
                $error_msg = "";
                // insert 1 item
                $ret = $import_common->itemEntry($array_item_data['item'][$nCnt], $tmp_dir, $array_item, $this->index_id, $item_type_info[$nCnt], $array_item_data['item_type'][$nCnt], $error_msg, $item_id, $detail_uri);
                if($ret === false){
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
                    // ROLLBACK
                    $this->failTrans();
                    throw $exception;
                }
                $array_item[$nCnt]["error_msg"] = $error_msg;
                
                // Add review mail setting 2009/09/30 Y.Nakao --start--
                // 査読対象のコンテンツ情報をメール本文に記載する
                // write mail body to revire contents information
                if($array_item[$nCnt]["review_status"] == 0){
                    $review_flg = true;
                    if($this->Session->getParameter("_lang") == "japanese"){
                        if(strlen($array_item[$nCnt]["title"]) > 0){
                            $body .= $array_item[$nCnt]["title"];
                        } else if(strlen($array_item[$nCnt]["title_english"]) > 0){
                            $body .= $array_item[$nCnt]["title_english"];
                        } else {
                            $body .= "no title";
                        }
                    } else {
                        if(strlen($array_item[$nCnt]["title_english"]) > 0){
                            $body .= $array_item[$nCnt]["title_english"];
                        } else if(strlen($array_item[$nCnt]["title"]) > 0){
                            $body .= $array_item[$nCnt]["title"];
                        } else {
                            $body .= "no title";
                        }
                    }
                    $body .= "\n";
                    $body .= $smartyAssign->getLang("repository_mail_review_detailurl").$detail_uri."\n";
                    $body .= "\n\n";
                }
                // Add review mail setting 2009/09/30 Y.Nakao --end--
            }
            
            // 登録したインデックス情報をセッションに保存
            $array_index = array();
            for ($index_count = 0; $index_count < count($this->index_id); $index_count++){
                // 所属インデックスの登録
                $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_index ".
                         "WHERE index_id = ?; ";
                // パラメータ設定
                $param_index = array();
                $param_index[] = intval( $this->index_id[$index_count] );
                // 実行
                $result = $this->Db->execute($query, $param_index);
                array_push($array_index, array($result[0]['index_name'], $result[0]['index_name_english']));
            }
            $this->Session->setParameter("index",$array_index);
            // get item names
            $this->Session->setParameter("items", $array_item);
            // comfirm item view all item name 2008/06/27 Y.Nakao --end--
            // Add e-person 2013/12/04 R.Matsuura --start--
            $this->Session->setParameter("importmode", "import");
            // Add e-person 2013/12/04 R.Matsuura --end--
            // end action
            // COMMIT
            $result = $this->exitAction();
            if ( $result == false ){
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_msg", $errMsg);
                // error
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );    //主メッセージとログIDを指定して例外を作成
                // ROLLBACK
                $this->failTrans();
                throw $exception;
            }
            
            // del work dir
            $this->removeDirectory($tmp_dir);
            
            // Add review mail setting 2009/09/30 Y.Nakao --start--
            // set Mail body
            $body .= $smartyAssign->getLang("repository_mail_review_reviewurl");
            $body .= BASE_URL;
            if(substr(BASE_URL,-1,1) != "/"){
                $body .= "/";
            }
            $body .= "?active_action=repository_view_edit_review&page_id=".$block_info["page_id"]."&block_id=".$block_info["block_id"];
            $body .= "\n";
            $body .= "\n\n".$smartyAssign->getLang("repository_mail_review_close");
            $this->mailMain->setBody($body);
            // ---------------------------------------------
            // 送信メール情報取得
            //   送信者のメールアドレス
            //   送り主の名前
            //   送信先ユーザを取得
            // create mail body
            //   get send from user mail address
            //   get send from user name
            //   get send to user
            // ---------------------------------------------
            $users = array();
            $this->getReviewMailInfo($users);
            if($review_flg && $review_mail_flg==1 && count($users) > 0){
                // ---------------------------------------------
                // 送信先を設定
                // set send to user
                // ---------------------------------------------
                // 送信ユーザを設定
                // $usersの中身
                // $users["email"] : 送信先メールアドレス
                // $user["handle"] : ハンドルネーム
                //                   なければ空白が自動設定される
                // $user["type"]   : type (html(email) or text(mobile_email))
                //                   なければhtmlが自動設定される
                // $user["lang_dirname"] : 言語
                //                         なければ現在の選択言語が自動設定される
                $this->mailMain->setToUsers($users);
                // ---------------------------------------------
                // メール送信
                // send confirm mail
                // ---------------------------------------------
                // 送信ユーザがいる場合送信
                $return = $this->mailMain->send();
            }
            // Add review mail setting 2009/09/30 Y.Nakao --end--
            
            
            return 'success';
            
        } catch (Exception $ex){

            // error log
            /*
            $this->logFile(
                "SampleAction",                 //class name
                "execute",                      //method name
                $Exception->getCode(),          //log id
                $Exception->getMessage(),       //msg
                $Exception->getDetailMsg() );   //msg
            */
            // end action
            $this->exitAction(); // ROLLBACK
            
            // return error
            return "error";
            
        }
    }

    /*
     * zip file extract
     */
    function extraction(){

        // get upload file
        $tmp_file = $this->Session->getParameter("filelist");

        //$dir_path = WEBAPP_DIR. "\\uploads\\repository\\";
        $dir_path = WEBAPP_DIR. "/uploads/repository/";
        $file_path = $dir_path . $tmp_file[0]['physical_file_name'];
        
        if($tmp_file[0]['extension'] != "zip"){
            unlink($file_path);
            return false;
        }
        
        // make dir for extract
        $dir = $dir_path . $tmp_file[0]['upload_id'];
        if (!mkdir($dir, 0777)){
            // error
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
            $this->failTrans(); // ROLLBACK
            throw $exception;
        }

        // extract zip file
        File_Archive::extract(
        File_Archive::read($file_path . "/"),
        File_Archive::appender($dir)
        );
        
        // delete upload xip file
        unlink($file_path);
        
        return $dir;
    }
}
?>
