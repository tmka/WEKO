<?php
// --------------------------------------------------------------------
//
// $Id: Edittexts.class.php 41057 2014-09-05 08:49:54Z tomohiro_ichikawa $
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
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Edittexts extends RepositoryAction
{
    //メンバ変数
//    var $year_option_pub = array();
    var $month_option_pub = array();
    var $day_option_pub = array();
    
    // 発行年月日の表示処理　A.Suzuki 2008/10/07 --Start--
    var $year_of_issued = "";        // 発行年
    var $month_of_issued = array();    // 発行月
    var $day_of_issued = array();    // 発行日
    // 発行年月日の表示処理　A.Suzuki 2008/10/07 --End--
    
    // アイテム属性：date 追加　A.Suzuki 2008/10/14 --Start--
    var $year_of_date = "";        // 年
    var $month_of_date = array();    // 月
    var $day_of_date = array();        // 日
    // アイテム属性：date　追加  A.Suzuki 2008/10/14 --End--
    
    // Add search bar for fill data 2008/11/19 A.Suzuki --start--
    var $biblio_info_check = false;    // 書誌情報の存在チェック(存在する:true, 存在しない:false)
    var $access_key_check = false;    // AWSAccessKeyIdの存在チェック(存在する:true, 存在しない:false)
    // Add search bar for fill data 2008/11/19 A.Suzuki --end--
    
    var $link_data = array();
    
    var $error_msg = array();        // エラーメッセージ
    var $warning = "";        // 警告

    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    // Add contents page Y.Nakao 2010/08/06 --start--
    var $heading = array();
    // Add contents page Y.Nakao 2010/08/06 --end--
    
    // Get author ID prefix list 2010/11/04 A.Suzuki --start--
    var $author_id_prefix_list = array();
    // Get author ID prefix list 2010/11/04 A.Suzuki --end--
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        $istest = true;                // テスト用フラグ
           try {            
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            // Add addin Y.Nakao 2012/07/27 --start--
            $this->addin->preExecute();
            // Add addin Y.Nakao 2012/07/27 --end--
            
            $NameAuthority = new NameAuthority($this->Session, $this->Db);
/*
        // 開始・終了日の選択候補（デフォルトチェック込み）文字列の作成
        // 年
        $now = mktime();
//        for ( $ii=-6; $ii<=0; $ii++ ) {        // 6年前から現在まで (ログ用)
           for ( $ii=-10; $ii<=10; $ii++ ) {    // 10年前から10年後まで
            $str_year = date(
                            "Y",
                            mktime(
                                date('H',$now),
                                date('i',$now),
                                date('s',$now),
                                date('m',$now),
                                date('d',$now),
                                date('Y',$now)+$ii
                            )
            );
            $select_s = $ii==-1 ? 1 : 0;
            $select_e = $ii==0 ? 1 : 0;
            array_push($this->year_option_start,Array($str_year,$select_s));
        }
*/
            // セッションから現在のアイテム公開日を取得
            $item_pub_date = $this->Session->getParameter('item_pub_date');
                        
            // 月
            for ( $ii=1; $ii<=12; $ii++ ) {
                $str_month = ($ii);
//                $select = $str_month==date('m',$now) ? 1 : 0;
                $select = ($ii == $item_pub_date['month']) ? 1 : 0;
                array_push($this->month_option_pub, array($str_month,$select));
            }
            // 日
            for ( $ii=1; $ii<=31; $ii++ ) {
                $str_day = ($ii);
//                $select = $str_day==date('d',$now) ? 1 : 0;
                $select = ($ii == $item_pub_date['day']) ? 1 : 0;
                array_push($this->day_option_pub, array($str_day,$select));
            }
            
            // 発行年月日の表示処理　A.Suzuki 2008/10/07 --Start--
            // セッションから現在の発行年月日を取得
            $temp_item_attr_type = $this->Session->getParameter('item_attr_type');
            $temp_item_attr = $this->Session->getParameter('item_attr');
            
            $this->month_of_issued = array();
            $this->day_of_issued = array();
            $this->month_of_date = array();
            $this->day_of_date = array();
            if(count($temp_item_attr_type) > 0)
            {
                $this->month_of_issued = array_fill(0,count($temp_item_attr_type),array());
                $this->day_of_issued = array_fill(0,count($temp_item_attr_type),array());
                
                $this->month_of_date = array_fill(0,count($temp_item_attr_type),array());
                $this->day_of_date = array_fill(0,count($temp_item_attr_type),array());
            }
            
            for ($ii=0; $ii<count($temp_item_attr_type); $ii++) {
                if ($temp_item_attr_type[$ii]['input_type'] == 'biblio_info') {
                    // Add search bar for fill data 2008/11/19 A.Suzuki --start--
                    // アイテムタイプに書誌情報があるかチェック
                    if($this->biblio_info_check == false){
                        $this->biblio_info_check = true;    
                    }
                    // Add search bar for fill data 2008/11/19 A.Suzuki --end--
                    
                    // 月
                    for ( $jj=1; $jj<=12; $jj++ ) {
                        $str_month = ($jj);
                        $select = 0;
                        if(isset($temp_item_attr[$ii][0]['month']) && $jj == $temp_item_attr[$ii][0]['month'])
                        {
                            $select = 1;
                        }
                        array_push($this->month_of_issued[$ii], array($str_month,$select));
                    }
                    // 日
                    for ( $jj=1; $jj<=31; $jj++ ) {
                        $str_day = ($jj);
                        $select = 0;
                        if(isset($temp_item_attr[$ii][0]['day']) && $jj == $temp_item_attr[$ii][0]['day'])
                        {
                            $select = 1;
                        }
                        array_push($this->day_of_issued[$ii], array($str_day,$select));
                    }
                }
                // Add item attribute:date 2008/10/14 A.Suzuki --Start--
                else if ($temp_item_attr_type[$ii]['input_type'] == 'date') {
                    for($ll=0; $ll<count($temp_item_attr[$ii]); $ll++){
                        $monthArray[$ll] = array();
                        $dayArray[$ll] = array();
                        // 月
                        for ( $jj=1; $jj<=12; $jj++ ) {
                            $str_month = ($jj);
                            // Bug fix undefined key 2014/09/05 T.Ichikawa --start--
                            $select = 0;
                            if(isset($temp_item_attr[$ii][$ll]['date_month']) && $temp_item_attr[$ii][$ll]['date_month'] == $jj) {
                                $select = 1;
                            }
                            //array_push($this->month_of_date[$ii], array($str_month,$select));
                            array_push($monthArray[$ll], array($str_month,$select));
                        }
                        // 日
                        for ( $jj=1; $jj<=31; $jj++ ) {
                            $str_day = ($jj);
                            $select = 0;
                            if(isset($temp_item_attr[$ii][$ll]['date_day']) && $temp_item_attr[$ii][$ll]['date_day'] == $jj) {
                                $select = 1;
                            }
                            // Bug fix undefined key 2014/09/05 T.Ichikawa --end--
                            //array_push($this->day_of_date[$ii], array($str_day,$select));
                            array_push($dayArray[$ll], array($str_day,$select));
                        }
                        array_push($this->month_of_date[$ii], $monthArray[$ll]);
                        array_push($this->day_of_date[$ii], $dayArray[$ll]);
                    }
                }
                // Add item attribute:date 2008/10/14 A.Suzuki --End--
                // Add link name 2009/03/19 A.Suzuki --start--
                else if($temp_item_attr_type[$ii]['input_type'] == 'link'){
                    for($jj=0; $jj<count($temp_item_attr[$ii]); $jj++){
                        $this->link_data[$ii][$jj] = explode("|", $temp_item_attr[$ii][$jj], 2);
                    }
                }
                // Add link name 2009/03/19 A.Suzuki --end--
                // Add contents page Y.Nakao 2010/08/06 --start--
                else if($temp_item_attr_type[$ii]['input_type'] == 'heading'){
                    for($jj=0; $jj<count($temp_item_attr[$ii]); $jj++){
                        $this->heading[$jj] = explode("|", $temp_item_attr[$ii][$jj], 4);
                    }
                }
                // Add contents page Y.Nakao 2010/08/06 --end--
            }
            // 発行年月日の表示処理　A.Suzuki 2008/10/07 --End--

            // Add search bar for fill data 2008/11/20 A.Suzuki --start--
            // AWSAccesskeyIdが登録されているかをチェック
               $query = "SELECT param_value ".
                     "FROM ". DATABASE_PREFIX ."repository_parameter ".    
                     "WHERE param_name = 'AWSAccessKeyId' AND ".
                     "is_delete = 0; ";            // 削除されていない
            $params = null;
            //　SELECT実行
            $result = $this->Db->execute($query, $params);
            if($result === false) {
                return 'error';
            }
            //AWSAccessKeyIdが登録されている場合
            if(trim($result[0]['param_value']) != null) {
                $this->access_key_check = true;
            }
            // Add search bar for fill data 2008/11/20 A.Suzuki --end--
            
            // Add for get lang resource 2008/12/17 A.Suzuki --start--
            $this->setLangResource();
            // Add for get lang resource 2008/12/17 A.Suzuki --end--
            
            // アイテムリンク設定画面から戻ってきた場合
            // リンクアイテム検索で使用したセッションを削除する
            $this->Session->removeParameter("search_index_id_link");
            $this->Session->removeParameter("link_searchkeyword");
            $this->Session->removeParameter("link_search");
            $this->Session->removeParameter("view_open_node_index_id_item_link");

            // Set help icon setting 2010/02/10 K.Ando --start--
            $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // Set help icon setting 2010/02/10 K.Ando --end--
            
            // Get author ID prefix list 2010/11/04 A.Suzuki --start--
            $result = $NameAuthority->getExternalAuthorIdPrefixList();
            if($result === false) {
                $this->failTrans();
                return 'error';
            }
            $this->author_id_prefix_list = $result;
            // Get author ID prefix list 2010/11/04 A.Suzuki --end--
            
            // Check ichushi connect staus 2012/12/05 A.Jin --start--
            //医中誌連携チェックがONか調べる
            $query = "SELECT param_value ".
                     "FROM ". DATABASE_PREFIX ."repository_parameter ". 
                     "WHERE param_name = 'ichushi_is_connect' AND ".
                     "is_delete = 0; ";         // 削除されていない
            $params = null;
            // SELECT実行
               $result = $this->Db->execute($query, $params);
            if($result === false) {
                return 'error';
            }
               // ichushi_is_connectが登録されている場合
            if($result[0]['param_value'] == 1) {
                $login_id = "";
                $login_password = "";
                $cookie = "";
                
                //ログイン情報取得
                $is_get_login_info = $this->getLoginInfoIchushi($login_id, $login_password);
                //ログインチェック
                if($is_get_login_info == true) {
                    $result = $this->loginIchushi($login_id,$login_password,$cookie);
                    if($result === true){
                        $this->Session->setParameter("login_connect", 1);
                        //ログアウト
                        $this->logoutIchushi($cookie);
                    } else {
                        $this->Session->setParameter("login_connect", 0);
                    }
                } else {
                    $this->Session->setParameter("login_connect", 0);
                }
            } else {
                $this->Session->setParameter("login_connect", 0);
            }
            // Check ichushi connect staus 2012/12/05 A.Jin --end--
            
            // セッションからエラーメッセージのコピー
            $this->error_msg = $this->Session->getParameter("error_msg");
                $this->Session->removeParameter("error_msg");
                if(!is_array($this->error_msg))
                {
                    $this->error_msg = array();
                }
                
                if($this->error_msg != "" && !is_array($this->error_msg)){
                    $tmp_error = $this->error_msg;
                    $this->error_msg = array($tmp_error);
                    unset($tmp_error);
                }
                
                $this->warning = $this->Session->getParameter("warning");
            $this->Session->removeParameter("warning");
            
            // Add addin Y.Nakao 2012/07/27 --start--
            $this->addin->postExecute();
            // Add addin Y.Nakao 2012/07/27 --end--
            
            // アクション終了処理
            $result = $this->exitAction();  // トランザクションが成功していればCOMMITされる
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
            return 'success';
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                    //クラス名
                "execute",                        //メソッド名
                $Exception->getCode(),            //ログID
                $Exception->getMessage(),        //主メッセージ
                $Exception->getDetailMsg() );    //詳細メッセージ            
            //アクション終了処理
              $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
            //異常終了
            $this->Session->setParameter("error_msg", $user_error_msg);
            return "error";
        }
    }
}
?>
