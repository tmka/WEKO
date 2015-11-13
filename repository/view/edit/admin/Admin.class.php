<?php
// --------------------------------------------------------------------
//
// $Id: Admin.class.php 57169 2015-08-26 12:01:09Z tatsuya_koyasu $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHarvesting.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchRequestParameter.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Admin extends RepositoryAction
{
    // メンバ変数
    var $index_array = null;    // インデックスレコードを階層構造込みでソートた配列(表示用)
    var $error_msg = null;      // エラーメッセージ
    var $admin_active_tab = null;
    var $lang = null;
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  null;
    // Set help icon setting 2010/02/10 K.Ando --end--
    // Add url_rewrite error 2011/11/17 T.Koyasu --start--
    var $url_rewrite_error = null;
    // Add url_rewrite error 2011/11/17 T.Koyasu --end--
    //Add new prefix2013/12/24T.Ichikawa --start--
    var $prefixJalcDoi = null;
    var $prefixCrossRef = null;
    // Add DataCite 2015/02/10 K.Sugimoto --start--
    var $prefixDataCite = null;
    // Add DataCite 2015/02/10 K.Sugimoto --end--
    var $prefixCnri = null;
    var $prefixYHandle = null;
    //Add new prefix 2013/12/24 T.Ichikawa --end--
    // Add Detail Search 2013/11/20 R.Matsuura --start--
    /**
     * List of detail search information
     * search_setup[N]
     *   type_id:   search contents id
     *   show_name: show name
     *   use_flag:  use flag(not use:0 use:1)
     *   default_flag: default flag(not default:0 default:1)
     *   mapping:   mapping
     */
    public $search_setup = null;
    // Add Detail Search 2013/11/20 R.Matsuura --end--
    public $oaipmh_output_flag = null;
    
    public $institution_name = null;
    
    // Add Default Search Type 2014/12/03 K.Sugimoto --start--
    public $default_search_type = null;
    // Add Default Search Type 2014/12/03 K.Sugimoto --end--

    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --start--
    public $usagestatistics_link_display = null;
    // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --end--

    // Add ranking tab display setting 2014/12/19 K.Matsushita --start--
    public $ranking_tab_display = null;
    // Add ranking tab display setting 2014/12/19 K.Matsushita --end--
    
    // Add DataCite 2015/02/10 K.Sugimoto --start--
    public $prefix_flag = null;
    public $exist_doi_item = null;
    // Add DataCite 2015/02/10 K.Sugimoto --end--
    
    public $logTableStatus = null;
    
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        $istest = true;             // テスト用フラグ
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
        
            // ----------------------------------------------------
            // 準備処理         
            // ----------------------------------------------------
            $this->Session->removeParameter("edit_start_date");  
            $this->Session->removeParameter("admin_params");
            $this->error_msg = $this->Session->getParameter("error_msg");
            $this->Session->removeParameter("error_msg");
            $this->Session->removeParameter("error_flg");
            // Add url_rewrite error 2011/11/17 T.Koyasu --start--
            $this->url_rewrite_error = $this->Session->getParameter("repository_admin_url_rewrite_error");
            $this->Session->removeParameter("repository_admin_url_rewrite_error");
            // Add url_rewrite error 2011/11/17 T.Koyasu --end--
            $this->Session->removeParameter("repositoryAdminFileList");
            // Add ichushi session remove 2012/11/30 A.Jin --start--
            $this->Session->removeParameter("is_display_passwd");
            $this->Session->removeParameter("ichushi_checked");
            $this->Session->removeParameter("login_connect");
            $this->Session->removeParameter("ichushi_login_id");
            $this->Session->removeParameter("ichushi_login_passwd");
            // Add ichushi session remove 2012/11/30 A.Jin --end--
            
            $tmp_dir = $this->Session->getParameter("tmp_dir");
            if($tmp_dir != "" && $tmp_dir != null){
                // ワークディレクトリ削除
                $this->removeDirectory($tmp_dir);
                if(file_exists("./.rnd")){
                    unlink("./.rnd");
                }
                if(file_exists(BASE_DIR."/htdocs/weko/capcha.png")){
                    unlink(BASE_DIR."/htdocs/weko/capcha.png");
                }
                // セッション情報削除
                $this->Session->removeParameter("auth_session_id");
                $this->Session->removeParameter("tmp_dir");
                $this->Session->removeParameter("try_num");
                
            }
            
            // Add for get lang resource 2009/01/20 A.Suzuki --start--
            $this->setLangResource();
            // Add for get lang resource 2009/01/20 A.Suzuki --end--
            
            // set lang
            $this->lang = $this->Session->getParameter("_lang");
            $this->setLangResource();
            
            $NameAuthority = new NameAuthority($this->Session, $this->Db);
            
            // ----------------------------------------------------
            // 管理パラメタの読み込み          
            // ----------------------------------------------------
            $admin_params = array();        // 管理パラメタ列
            $Error_Msg = '';                // エラーメッセージ
            // パラメタテーブルの全属性を取得
//          $result = $this->getParamTableData($admin_params, $Error_Msg);
            $result = $this->getParamTableRecord($admin_params, $Error_Msg);
            if ($result === false) {
                $errMsg = $this->Db->ErrorMsg();
                $tmpstr = sprintf("item_coef_cp update failed : %s", $ii, $jj, $errMsg ); 
                $this->Session->setParameter("error_msg", $tmpstr);
                $this->failTrans();     //トランザクション失敗を設定(ROLLBACK)
                return 'error';
            }
            
            // ----------------------------------------------------
            // Datestamp分解&保存           
            // ----------------------------------------------------
            $dates = split('[TZ]', $admin_params['prvd_Identify_earliestDatestamp']['param_value']);
            if(count($dates)>=2) {
                list($year, $month, $day) = split('-', $dates[0]);
                list($hour, $minute, $second) = split(':', $dates[1]);
                $admin_params['prvd_Identify_earliestDatestamp']['year'] = $year;
                $admin_params['prvd_Identify_earliestDatestamp']['month'] = $month;
                $admin_params['prvd_Identify_earliestDatestamp']['day'] = $day;
                $admin_params['prvd_Identify_earliestDatestamp']['hour'] = $hour;
                $admin_params['prvd_Identify_earliestDatestamp']['minute'] = $minute;
                $admin_params['prvd_Identify_earliestDatestamp']['second'] = $second;
                //echo $year . "-" . $month . "-" . $day . "T" . $hour . ":" . $minute . ":" . $second;         
            }
            
            // ----------------------------------------------------
            // 表示用インデックス情報を作成           
            // ----------------------------------------------------
            // インデックス名を取得
            $query = "SELECT index_name, index_name_english ".
                     "FROM ". DATABASE_PREFIX ."repository_index ".     // インデックス
                     "WHERE index_id = ? ".     // index_id
                     "AND is_delete = ?; ";     // 削除フラグ
            $params = null;
            $params[] = $admin_params["default_disp_index"]["param_value"];
            $params[] = 0;  
            // SELECT実行
            $result = $this->Db->execute($query, $params);
            if ($result === false) {
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code",$errMsg);
                if($istest) { echo $errMsg . "<br>"; }
                return 'error';
            }
            if(count($result)<1){
                $index_name = $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
                $admin_params["default_disp_index"]["param_value"] = 0;
            } else {
                if($this->lang == "japanese"){
                    $index_name = $result[0]['index_name'];
                } else {
                    $index_name = $result[0]['index_name_english'];
                }
            }

            $default_index_data = array($index_name, $admin_params["default_disp_index"]["param_value"]);
            $admin_params["default_disp_index"]["param_value"] = $default_index_data;
            
//          $this->index_array = array();   // インデックスレコードを階層構造込みでソートた配列(表示用)
//          $this->sortIndexTable($index_table, 0, '0', $this->index_array);
            
            // ----------------------------------------------------
            // サーバ環境パスチェック          
            // ----------------------------------------------------
            // wvWareコマンド
            if($admin_params['path_wvWare']['param_value']!=""){ 
                if(file_exists($admin_params['path_wvWare']['param_value']."wvHtml") || 
                    file_exists($admin_params['path_wvWare']['param_value']."wvHtml.exe")){
                    $admin_params['path_wvWare']['path'] = "true";
                } else {
                    $admin_params['path_wvWare']['path'] = "false";
                }
            } else {
                $admin_params['path_wvWare']['path'] = "false";
            }
            // xlhtmlコマンド
            if($admin_params['path_xlhtml']['param_value']!=""){
                if(file_exists($admin_params['path_xlhtml']['param_value']."xlhtml") || 
                    file_exists($admin_params['path_xlhtml']['param_value']."xlhtml.exe")){
                    $admin_params['path_xlhtml']['path'] = "true";
                } else {
                    $admin_params['path_xlhtml']['path'] = "false";
                }
            } else {
                $admin_params['path_xlhtml']['path'] = "false";
            }
            // popplerコマンド
            if($admin_params['path_poppler']['param_value']!=""){
                if(file_exists($admin_params['path_poppler']['param_value']."pdftotext") || 
                    file_exists($admin_params['path_poppler']['param_value']."pdftotext.exe")){
                    $admin_params['path_poppler']['path'] = "true";
                } else {
                    $admin_params['path_poppler']['path'] = "false";
                }
            } else {
                $admin_params['path_poppler']['path'] = "false";
            }
            // ImageMagickコマンド
            if($admin_params['path_ImageMagick']['param_value']!=""){
                if(file_exists($admin_params['path_ImageMagick']['param_value']."convert") || 
                    file_exists($admin_params['path_ImageMagick']['param_value']."convert.exe")){
                    $admin_params['path_ImageMagick']['path'] = "true";
                } else {
                    $admin_params['path_ImageMagick']['path'] = "false";
                }
            } else {
                $admin_params['path_ImageMagick']['path'] = "false";
            }
            // Add pdftk 2012/06/07 A.Suzuki --start--
            // pdftk command
            if($admin_params['path_pdftk']['param_value']!=""){
                if(file_exists($admin_params['path_pdftk']['param_value']."pdftk") || 
                    file_exists($admin_params['path_pdftk']['param_value']."pdftk.exe")){
                    $admin_params['path_pdftk']['path'] = "true";
                } else {
                    $admin_params['path_pdftk']['path'] = "false";
                }
            } else {
                $admin_params['path_pdftk']['path'] = "false";
            }
            // Add pdftk 2012/06/07 A.Suzuki --end--
            // Add multimedia support 2012/08/27 T.Koyasu -start-
            // ffmpeg command
            if($admin_params['path_ffmpeg']['param_value']!=""){
                if(file_exists($admin_params['path_ffmpeg']['param_value']."ffmpeg") || 
                   file_exists($admin_params['path_ffmpeg']['param_value']."ffmpeg.exe")){
                    $admin_params['path_ffmpeg']['path'] = "true";
                } else {
                    $admin_params['path_ffmpeg']['path'] = "false";
                }
            } else {
                $admin_params['path_ffmpeg']['path'] = "false";
            }
            // Add multimedia support 2012/08/27 T.Koyasu -end-
            // Add external search word 2014/05/23 K.Matsuo -start-
            // mecab command
            if($admin_params['path_mecab']['param_value']!=""){
                if(file_exists($admin_params['path_mecab']['param_value']."mecab") || 
                   file_exists($admin_params['path_mecab']['param_value']."mecab.exe")){
                    $admin_params['path_mecab']['path'] = "true";
                } else {
                    $admin_params['path_mecab']['path'] = "false";
                }
            } else {
                $admin_params['path_mecab']['path'] = "false";
            }
            // Add external search word 2014/05/23 K.Matsuo -end-
            
            // Modified display name of fulltext index 2010/05/19 A.Suzuki --start--
            // Whether exists command for extract.
            if($admin_params['path_wvWare']['path'] == "true" ||
               $admin_params['path_xlhtml']['path'] == "true" ||
               $admin_params['path_poppler']['path'] == "true"){
                // Whichever exists command.
                $this->Session->setParameter("extract_command_flag", "true");
            } else {
                $this->Session->setParameter("extract_command_flag", "false");
            }
            
            // Check senna status.
            $chk_senna = $this->Db->execute("SHOW SENNA STATUS;");
            
            if($chk_senna === false){
                $this->Session->setParameter("senna_flag", "false");
            } else {
                $this->Session->setParameter("senna_flag", "true");
            }
            // Modified display name of fulltext index 2010/05/19 A.Suzuki --end--

            // Add site license 2008/10/20 Y.Nakao --start--
            // ----------------------------------------------------
            // site license
            // ----------------------------------------------------
            // サイトライセンス基本情報取得
            $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_sitelicense_info ".
                     "WHERE is_delete = ? ".
                     "ORDER BY show_order ASC ;";
            $params = array();
            $params[] = 0;
            $sl_info = $this->Db->execute($query, $params);
            
            $cnt_ipaddress = 0;
            $sitelicense_id = array();
            $sitelicense_org = array();
            $sitelicense_group = array();
            $ipaddress_from = array();
            $ipaddress_to = array();
            $sitelicense_mail = array();
            for($ii=0; $ii<count($sl_info); $ii++){
                // 機関ID
                $sitelicense_id[$ii] = $sl_info[$ii]["organization_id"];
                // 機関名
                $sitelicense_org[$ii] = $sl_info[$ii]["organization_name"];
                // 組織名
                $sitelicense_group[$ii] = $sl_info[$ii]["group_name"];
                // IPアドレス
                // サイトライセンスIP情報取得
                $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_sitelicense_ip_address ".
                         "WHERE organization_id = ? ".
                         "AND is_delete = ? ;";
                $params = array();
                $params[] = $sl_info[$ii]["organization_id"];
                $params[] = 0;
                $ip_address = $this->Db->execute($query, $params);
                if(isset($ip_address)) {
                    for($jj = 0; $jj < count($ip_address); $jj++) {
                        // 開始IPアドレス
                        if(strlen($ip_address[$jj]["start_ip_address"]) > 0) {
                            $start_ip_address = explode(".", $ip_address[$jj]["start_ip_address"]);
                            $ipaddress_from[$ii][$jj] = array($start_ip_address[0], $start_ip_address[1], $start_ip_address[2], $start_ip_address[3]);
                        } else {
                            $ipaddress_from[$ii][$jj] = array("", "", "", "");
                        }
                        // 終了IPアドレス
                        if(strlen($ip_address[$jj]["finish_ip_address"]) > 0) {
                            $finish_ip_address = explode(".", $ip_address[$jj]["finish_ip_address"]);
                            $ipaddress_to[$ii][$jj] = array($finish_ip_address[0], $finish_ip_address[1], $finish_ip_address[2], $finish_ip_address[3]);
                        } else {
                            $ipaddress_to[$ii][$jj] = array("", "", "", "");
                        }
                    }
                } else {
                        $ipaddress_from[$ii][0] = array("", "", "", "");
                        $ipaddress_to[$ii][0] = array("", "", "", "");
                }
                // メールアドレス
                $sitelicense_mail[$ii] = $sl_info[$ii]["mail_address"];
            }
            
            $admin_params["sitelicense_id"]["param_value"] = $sitelicense_id;
            $admin_params["sitelicense_org"]["param_value"] = $sitelicense_org;
            $admin_params["sitelicense_group"]["param_value"] = $sitelicense_group;
            $admin_params["ip_sitelicense_from"]["param_value"] = $ipaddress_from;
            $admin_params["ip_sitelicense_to"]["param_value"] = $ipaddress_to;
            $admin_params["sitelicense_mail"]["param_value"] = $sitelicense_mail;
            
            // Add item type select for site license 2009/01/06 A.Suzuki --start--
            $site_license_item_id = explode(",", $admin_params['site_license_item_type_id']['param_value']);
            
            // get item_type_id and item_type_name
            $query = "SELECT item_type_id, item_type_name ".
                     "FROM ". DATABASE_PREFIX ."repository_item_type ".
                     "WHERE is_delete = ?; ";
            $params = null;
            $params[] = 0;
            // SELECT実行
            $result = $this->Db->execute($query, $params);
            if ($result === false) {
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code",$errMsg);
                if($istest) { echo $errMsg . "<br>"; }
                return 'error';
            }
            $sitelicense_itemtype_array = array();
            $sitelicense_not_itemtype_array = array();
            for($ii=0; $ii<count($site_license_item_id); $ii++){
                for($jj=0; $jj<count($result); $jj++){
                    if($site_license_item_id[$ii] == $result[$jj]['item_type_id']){
                        array_push($sitelicense_not_itemtype_array, array($result[$jj]['item_type_id'], $result[$jj]['item_type_name']));
                        array_splice($result, $jj, 1);
                        break;
                    }
                }
            }
            for($ii=0; $ii<count($result); $ii++){
                array_push($sitelicense_itemtype_array, array($result[$ii]['item_type_id'], $result[$ii]['item_type_name']));
            }
            $admin_params["sitelicense_itemtype_array"]["param_value"] = $sitelicense_itemtype_array;
            $admin_params["sitelicense_not_itemtype_array"]["param_value"] = $sitelicense_not_itemtype_array;
            // Add item type select for site license 2009/01/06 A.Suzuki --end--
            
            // Add search result setting 2009/03/06 A.Suzuki --start--
            // ----------------------------------------------------
            // search result setting
            // ----------------------------------------------------
            $sort_disp_num = explode("|", $admin_params["sort_disp"]["param_value"]);
            $sort_not_disp_num = explode("|", $admin_params["sort_not_disp"]["param_value"]);
            
            if(count($sort_disp_num) == 1 && $sort_disp_num[0] == ""){
                $sort_disp_num = array();
            }
            if(count($sort_not_disp_num) == 1 && $sort_not_disp_num[0] == ""){
                $sort_not_disp_num = array();
            }
            
            // get sort name
            $sort_disp_name_array = array();
            $sort_not_disp_name_array = array();
            for($ii=0; $ii<count($sort_disp_num); $ii++){
                $sort_disp_name = $this->getSortName($sort_disp_num[$ii]);
                array_push($sort_disp_name_array, $sort_disp_name);
            }
            $admin_params["sort_disp_name"]["param_value"] = $sort_disp_name_array;
            
            for($ii=0; $ii<count($sort_not_disp_num); $ii++){
                $sort_not_disp_name = $this->getSortName($sort_not_disp_num[$ii]);
                array_push($sort_not_disp_name_array, $sort_not_disp_name);
            }
            $admin_params["sort_not_disp_name"]["param_value"] = $sort_not_disp_name_array;
            
            $admin_params["sort_disp_num"]["param_value"] = $sort_disp_num;
            $admin_params["sort_not_disp_num"]["param_value"] = $sort_not_disp_num;
            
            // Add search result setting 2009/03/06 A.Suzuki --end--
            
            // Add sort default for keyword 2009/06/30 A.Suzuki --start--
            $admin_params["sort_disp_default"]["param_value"] = explode("|", $admin_params["sort_disp_default"]["param_value"]);
            // Add sort default for keyword 2009/06/30 A.Suzuki --end--
            
            // Add alternative language setting 2009/08/11 A.Suzuki --start--
            $tmp_alter_lang = explode(",", $admin_params["alternative_language"]["param_value"]);
            $lang_array = array();
            for($ii=0;$ii<count($tmp_alter_lang);$ii++){
                $lang_data = explode(":", $tmp_alter_lang[$ii], 2);
                if($lang_data[0] == "japanese"){
                    $lang_array["japanese_flag"] = $lang_data[1];
                } else if($lang_data[0] == "english"){
                    $lang_array["english_flag"] = $lang_data[1];
                }
            }
            $admin_params["alternative_language"]["param_value"] = $lang_array;
            // Add alternative language setting 2009/08/11 A.Suzuki --end--
            
            // Add sitemap setting 2009/12/14 K.Ando --start--
            $admin_params["sitemap_uri"]["path"] = BASE_URL ."/?action=repository_sitemap&login_id=[login_id]&password=[password]";
            // Add sitemap setting 2009/12/14 K.Ando --end--
            // Add rankingdatabese setting 2010/02/04 K.Ando --start--
            $admin_params["rankingDatabase_uri"]["path"] = BASE_URL ."/?action=repository_action_common_ranking&login_id=[login_id]&password=[password]";
            // Add rankingdatabese setting 2010/02/04 K.Ando --end--
            
            // Add file clean-up setting 2011/02/23 H.Goto --start--
            $admin_params["fileCleanUp_uri"]["path"] = BASE_URL ."/?action=repository_action_common_filecleanup&login_id=[login_id]&password=[password]";
            // Add file clean-up setting 2011/02/23 H.Goto --end--
            
            // Add update usage statistics 2012/07/01 A.Suzuki --start--
            $admin_params["updateUsageStatistics_uri"]["path"] = BASE_URL ."/?action=repository_action_common_usagestatistics&login_id=[login_id]&password=[password]";
            // Add update usage statistics 2012/07/01 A.Suzuki --end--
            
            // Add reconstruct table 2013/12/18 R.Matsuura --start--
            $admin_params["reconstructIndexAuthDatabase_uri"]["path"] = BASE_URL ."/?action=repository_action_common_reconstruction_indexauthority&login_id=[login_id]&password=[password]";
            $admin_params["reconstructSearchDatabase_uri"]["path"] = BASE_URL ."/?action=repository_action_common_reconstruction_search&login_id=[login_id]&password=[password]";
            // Add reconstruct table 2013/12/18 R.Matsuura --end--
            // Add send feedback mail to sitelicense user 2014/04/22 T.Ichikawa --start--
            $admin_params["siteLicenseMail_uri"]["path"] = BASE_URL ."/?action=repository_action_common_sitelicensemail&login_id=[login_id]&password=[password]";
            // Add send feedback mail to sitelicense user 2014/04/22 T.Ichikawa --end--
            
            // Add feedback mail 2012/08/22 A.Suzuki --start--
            // Get send mail status
            $this->infoLog("businessSendusagestatisticsmail", __FILE__, __CLASS__, __LINE__);
            $SendMail = BusinessFactory::getFactory()->getBusiness("businessSendusagestatisticsmail");
            // 引数は一番最後以外ダミー（リファレンス渡しになっているので一応変数を書いている）
            $status = $SendMail->openProgressFile($mailAddress, $orderNum, $isAuhtor, $authorId, false);
            
            $admin_params["feedbackMail_uri"]["path"] = BASE_URL ."/?action=repository_action_common_usagestatisticsmail&login_id=[login_id]&password=[password]";
            $excludeUserDataList = array();
            $excludeUserDataText = "";
            $excludeAddressList = explode(",", $admin_params["exclude_address_for_feedback"]["param_value"]);
            foreach($excludeAddressList as $address)
            {
                if(strlen($address) == 0)
                {
                    continue;
                }
                $userName = $this->getUserNameByAddress($address);
                $authorName = $this->getAuthorNameByAddress($address);
                if(strlen($userName . $authorName) == 0)
                {
                    continue;
                }
                if(strlen($userName) > 0)
                {
                    array_push($excludeUserDataList, array("display" => $userName." ".$address, "value" => $address));
                }
                else
                {
                    array_push($excludeUserDataList, array("display" => $authorName." ".$address, "value" => $address));
                }
                if(strlen($excludeUserDataText) > 0)
                {
                    $excludeUserDataText .= ",";
                }
                $excludeUserDataText .= $address;
            }
            $this->Session->setParameter("excludeUserDataList", $excludeUserDataList);
            $this->Session->setParameter("excludeUserDataText", $excludeUserDataText);
            $this->Session->setParameter("sendFeedbackStatus", $status);
            $this->Session->setParameter("feedbackSendMailActivateFlagOrg", $admin_params["send_feedback_mail_activate_flg"]["param_value"]);
            
            // Add feedback mail 2012/08/22 A.Suzuki --end--
            
            // Add authorID prefix list edit 2010/11/10 A.Suzuki --start--
            $block_id = str_replace("_", "", $this->Session->getParameter("_id"));
            $room_id = $this->Session->getParameter("_main_room_id");
            $admin_params["author_id_prefix"]["list"] = "";
            $admin_params["author_id_prefix"]["text"] = "";
            $admin_params["author_id_prefix"]["list"] = $NameAuthority->getExternalAuthorIdPrefixList();
            if($admin_params["author_id_prefix"]["list"] === false){
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code",$errMsg);
                if($istest) { echo $errMsg . "<br>"; }
                return 'error';
            }
            for($ii=0;$ii<count($admin_params["author_id_prefix"]["list"]);$ii++){
                if($admin_params["author_id_prefix"]["text"] != ""){
                    $admin_params["author_id_prefix"]["text"] .= "|";
                }
                $admin_params["author_id_prefix"]["text"] .= $admin_params["author_id_prefix"]["list"][$ii]["prefix_id"].",".$admin_params["author_id_prefix"]["list"][$ii]["prefix_name"];
            }
            // Add authorID prefix list edit 2010/11/10 A.Suzuki --end--
            // Add setting URL rewrite 2011/11/14 T.Koyasu -start-
            $url_for_confirmation = BASE_URL ."/oai?verb=Identify";
            
            // get response
            $option = array( 
                "timeout" => "10",
                "readTimeout" => array(10, 0),
                "allowRedirects" => true,
                "maxRedirects" => 3, 
            );
            // Modfy proxy 2011/12/06 Y.Nakao --start--
            $proxy = $this->getProxySetting();
            if($proxy['proxy_mode'] == 1)
            {
                $option = array( 
                        "timeout" => "10",
                        "readTimeout" => array(10, 0),
                        "allowRedirects" => true, 
                        "maxRedirects" => 3,
                        "proxy_host"=>$proxy['proxy_host'],
                        "proxy_port"=>$proxy['proxy_port'],
                        "proxy_user"=>$proxy['proxy_user'],
                        "proxy_pass"=>$proxy['proxy_pass']
                    );
            }
            // Modfy proxy 2011/12/06 Y.Nakao --end--
            $http = new HTTP_Request($url_for_confirmation, $option);
            // setting HTTP header
            $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
            $response = $http->sendRequest();
            if (!PEAR::isError($response)) { 
                $resCode = $http->getResponseCode();        // get ResponseCode(200etc.)
                $resHeader = $http->getResponseHeader();    // get ResponseHeader
                $resBody = $http->getResponseBody();        // get ResponseBody
                $resCookies = $http->getResponseCookies();  // get Cookie
            }
            // search string 'OAI-PMH'
            $search_result = strpos($resBody, "OAI-PMH");
            
            if(is_numeric($search_result) && $search_result > 0) {
                // url rewrite is used
                $show_base_url = BASE_URL ."/oai";
                $use_url_rewrite = '1';
            }else {
                // url rewrite is not used
                $show_base_url = BASE_URL ."/?action=repository_oaipmh";
                $use_url_rewrite = '';
            }
            
            // set parameter to session
            $this->Session->setParameter('show_base_url', preg_replace("/^https:/i","http:", $show_base_url));
            $this->Session->setParameter('use_url_rewrite', $use_url_rewrite);
            // Add setting URL rewrite 2011/11/14 T.Koyasu -end-
            
            // Add harvesting 2012/03/05 A.Suzuki --start--
            $Harvesting = new RepositoryHarvesting($this->Session, $this->Db);
            
            // Get harvesting status
            $Harvesting->openProgressFile(false);
            
            $harvestingRepositories = array();
            $result = $Harvesting->getHarvestingTable($harvestingRepositories);
            if($result==false)
            {
                $this->failTrans();
                return "error";
            }
            
            if(count($harvestingRepositories) == 0)
            {
                array_push($harvestingRepositories,
                            array(
                                "repository_id" => "0",
                                "repository_name" => "",
                                "base_url" => "",
                                "metadata_prefix" => "oai_dc",
                                "post_index_id" => "",
                                "post_index_name" => "",
                                "automatic_sorting" => "0",
                                
                                // Add Selective Harvesting 2013/09/04 R.Matsuura --start--
                                "from_date" => RepositoryHarvesting::DEFAULT_FROM_DATE,
                                "until_date" => RepositoryHarvesting::DEFAULT_UNTIL_DATE,
                                "set_param" => "",
                                "execution_date" => ""
                            )
                        );
                // execute dividDatestamp
                $from_date_array = array();
                $until_date_array = array();
                $Harvesting->dividDatestamp(RepositoryHarvesting::DEFAULT_FROM_DATE, $from_date_array);
                $Harvesting->dividDatestamp(RepositoryHarvesting::DEFAULT_UNTIL_DATE, $until_date_array);
                // add an associative array
                $harvestingRepositories[0]["from_date_array"] = $from_date_array;
                $harvestingRepositories[0]["until_date_array"] = $until_date_array;
            }
            else
            {
                for( $ii=0; $ii<count($harvestingRepositories); $ii++)
                {
                    if($harvestingRepositories[$ii]["from_date"] == null || $harvestingRepositories[$ii]["from_date"] == "")
                    {
                        $harvestingRepositories[$ii]["from_date"] = RepositoryHarvesting::DEFAULT_FROM_DATE;
                    }
                    if($harvestingRepositories[$ii]["until_date"] == null || $harvestingRepositories[$ii]["until_date"] == "")
                    {
                        $harvestingRepositories[$ii]["until_date"] = RepositoryHarvesting::DEFAULT_UNTIL_DATE;
                    }
                    // execute dividDatestamp
                    $from_date_array = array();
                    $until_date_array = array();
                    $Harvesting->dividDatestamp($harvestingRepositories[$ii]["from_date"], $from_date_array);
                    $Harvesting->dividDatestamp($harvestingRepositories[$ii]["until_date"], $until_date_array);
                    // add an associative array
                    $harvestingRepositories[$ii]["from_date_array"] = $from_date_array;
                    $harvestingRepositories[$ii]["until_date_array"] = $until_date_array;
                    // Add Selective Harvesting 2013/09/04 R.Matsuura --end--
                }
            }
            $admin_params['harvesting_uri']["path"] = BASE_URL ."/?action=repository_action_common_harvesting&login_id=[login_id]&password=[password]";
            $this->Session->setParameter("harvestingStatus", $Harvesting->getStatus());
            $this->Session->setParameter("harvestingRepositories", $harvestingRepositories);
            $existValidRepos = false;
            foreach($harvestingRepositories as $repos)
            {
                if(strlen($repos["base_url"]) > 0 && strlen($repos["post_index_id"]) > 0 && $repos["post_index_id"] != 0)
                {
                    $existValidRepos = true;
                    break;
                }
            }
            $this->Session->setParameter("harvestingExistValidRepos", $existValidRepos);
            // Add harvesting 2012/03/05 A.Suzuki --end--
            
            // Add PDF cover page 2012/06/07 A.Suzuki --start--
            // Get PDF cover parameter
            $this->setPdfCoverParamsToAdminParams($admin_params);
            // Add PDF cover page 2012/06/07 A.Suzuki --end--
            
            // Add ICHUSHI 2012/11/19 A.jin --start--
            
            //医中誌連携有無チェック
            $is_checked = $admin_params['ichushi_is_connect']["param_value"];
            if($is_checked == 1)
            {
                $this->Session->setParameter("ichushi_checked", 1);
                
                // 医中誌ログインチェック
                $isConnect = $this->checkLoginIchushi();
                
                // 認証結果をセッションに保存
                if($isConnect === true)
                {
                    $this->Session->setParameter("login_connect", 1);
                }else
                {
                    $this->Session->setParameter("login_connect", 0);
                }
                $login_id = $admin_params['ichushi_login_id']["param_value"];
                $this->Session->setParameter("ichushi_login_id", $login_id);
                $login_passwd = $admin_params['ichushi_login_passwd']["param_value"];
                $this->Session->setParameter("ichushi_login_passwd", $login_passwd);
                
            }else
            {
                $this->Session->setParameter("ichushi_checked", 0);
                $this->Session->setParameter("login_connect", 0);
                $login_id = $admin_params['ichushi_login_id']["param_value"];
                $this->Session->setParameter("ichushi_login_id", $login_id);
            }
            
            //パスワード表示保存フラグ --連携チェックがon->off->onの場合、パスワードが消える問題対応 2012/11/29 A.Jin--
            //DBにパスワードが入っている場合表示する
            $login_passwd = $admin_params['ichushi_login_passwd']["param_value"];
            if($login_passwd == ""){
                $this->Session->setParameter("is_display_passwd", 0);
            } else {
                $this->Session->setParameter("is_display_passwd", 1);
            }
            // Add ICHUSHI 2012/11/19 A.jin --end--

            // Add new prefix 2013/12/24 T.ichikawa --start--
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess,  $this->TransStartDate);
            if(!isset($this->prefixJalcDoi)) {
                $this->prefixJalcDoi = $repositoryHandleManager->getJalcDoiPrefix();
            }
            if(!isset($this->prefixCrossRef)) {
                $this->prefixCrossRef = $repositoryHandleManager->getCrossRefPrefix();
            }
        	// Add DataCite 2015/02/10 K.Sugimoto --start--
            if(!isset($this->prefixDataCite)) {
                $this->prefixDataCite = $repositoryHandleManager->getDataCitePrefix();
            }
        	// Add DataCite 2015/02/10 K.Sugimoto --end--
            if(!isset($this->prefixCnri)) {
                $this->prefixCnri = $repositoryHandleManager->getCnriPrefix();
            }
            if(!isset($this->prefixYHandle)) {
                $this->prefixYHandle = $repositoryHandleManager->getYHandlePrefix();
            }
            // Add new prefix 2013/12/24 T.ichikawa --end--
			// Add PrivateTree 2013/04/09 K.Matsuo --start-
			
			$define_inc_file_path = WEBAPP_DIR. '/modules/repository/config/define.inc.php';
			// define.inc.phpに書き込み権限があるかをセッションに保存
			$is_writable_define = is_writable($define_inc_file_path);
			$this->Session->setParameter("is_writable_define_inc", $is_writable_define);
			if($is_writable_define){
				$query = "SELECT index_name, index_name_english ".
						 " FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_INDEX.
						 " WHERE index_id = ? ".
						 " AND is_delete = ? ";
				$params = array();
				$params[] = $admin_params["privatetree_parent_indexid"]["param_value"];
				$params[] = 0;
				$ret = $this->Db->execute($query, $params);
				if($ret != false || count($ret) == 1){
					
					if($this->Session->getParameter("_lang") == "japanese")
					{
						$admin_params["privatetree_parent_indexname"]["param_value"] = $ret[0]['index_name'];
					}
					else
					{
						$admin_params["privatetree_parent_indexname"]["param_value"] = $ret[0]['index_name_english'];
					}
				}
			} else {
				$admin_params["is_make_privatetree"]["param_value"] = 0;
			}
			// Add PrivateTree 2013/04/09 K.Matsuo --end--
            
            // Add Detail Search 2013/11/20 R.Matsuura --start--
            // Detail Search setting
            $query = "SELECT `type_id`, `search_type`, `use_search`, `default_show`, `junii2_mapping` ".
                     "FROM ".DATABASE_PREFIX. "repository_search_item_setup ".
                     "ORDER BY type_id ASC ;";
            $search_condition = $this->dbAccess->executeQuery($query);
            $smartyAssign = $this->Session->getParameter("smartyAssign");
            for($searchCnt = 0; $searchCnt < count($search_condition); $searchCnt++)
            {
                $this->search_setup[$searchCnt]["type_id"] = $search_condition[$searchCnt]["type_id"];
                $this->search_setup[$searchCnt]["show_name"] = $smartyAssign->getLang($search_condition[$searchCnt]["search_type"]);
                $this->search_setup[$searchCnt]["use_flag"] = $search_condition[$searchCnt]["use_search"];
                $this->search_setup[$searchCnt]["default_flag"] = $search_condition[$searchCnt]["default_show"];
                $this->search_setup[$searchCnt]["mapping"] = $search_condition[$searchCnt]["junii2_mapping"];
            }
            // Add Detail Search 2013/11/20 R.Matsuura --end--
            // Add External Search Word 2014/05/22 K.Matsuo --start--
            // Detail Search setting
            $query = "SELECT `stop_word`, `part_of_speech` ".
                     "FROM ".DATABASE_PREFIX. "repository_external_searchword_stopword ".
                     "WHERE is_delete=0 ;";
            $stopword_list = $this->dbAccess->executeQuery($query);
            $stopword = "";
            for($ii = 0; $ii < count($stopword_list); $ii++){
                $stopword .= $stopword_list[$ii]["stop_word"] . "," . $stopword_list[$ii]["part_of_speech"] . "\n";
            }
            
            $query = "SELECT status ".
                     "FROM ".DATABASE_PREFIX. "repository_lock ".
                     "WHERE process_name = ? ;";
            $params = array();
            $params[] = "Repository_Action_Common_Updateexternalsearchword";
            $result = $this->dbAccess->executeQuery($query, $params);
            $updateStopWord = "";
            if($result[0]['status'] == 1){
                // ストップワード更新中
                $updateStopWord = "processing";
            }
            if($admin_params['externalsearchword_stopword']['param_value'] == 0 && 
                    $admin_params['path_mecab']['path'] != "true"){
                $admin_params['externalsearchword_stopword']['param_value'] = 1;
            }
            $this->Session->setParameter("stopwordUpdateStatus", $updateStopWord);     // ストップワード更新フラグを保存
            $this->Session->setParameter("external_searchword_word", $stopword);     // ストップワードを保存
            // Add External Search Word 2014/05/22 K.Matsuo --end--
            // Add Default External Word 2014/06/09 T.Ichikawa --start--
            $defaultStopWord = "";
            $fp = fopen(WEBAPP_DIR. '/modules/repository/config/defaultExternalSearchStopword', "r");
            while($row = fgets($fp)) {
                $defaultStopWord .= str_replace("\r\n", "\n", $row);
            }
            fclose($fp);
            $this->Session->setParameter("default_external_search_word", $defaultStopWord);
            // Add Default External Word 2014/06/09 T.Ichikawa --end--
            
            // OAI-PMH Output Flag
            if(isset($admin_params["oaipmh_output_flag"]["param_value"]) 
                && strlen($admin_params["oaipmh_output_flag"]["param_value"]) > 0)
            {
                $this->oaipmh_output_flag = $admin_params["oaipmh_output_flag"]["param_value"];
            }
            else
            {
                $query = "SELECT param_value ".
                         "FROM ".DATABASE_PREFIX."repository_parameter ".
                         "WHERE param_name = ? ".
                         "AND is_delete = ? ;";
                $params = array();
                $params[] = "output_oaipmh";
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
                    $this->oaipmh_output_flag = $result[0]['param_value'];
                }
            }
            
            // Add Default Search Type 2014/12/03 K.Sugimoto --start--
            // Default Search Type
            if(isset($admin_params["default_search_type"]["param_value"]) 
                && strlen($admin_params["default_search_type"]["param_value"]) > 0)
            {
                $this->default_search_type = $admin_params["default_search_type"]["param_value"];
            }
            else
            {
            	$searchParam = new RepositorySearchRequestParameter();
            	$result = $searchParam->getDefaultSearchType();
                if(count($result) > 0)
                {
                    $this->default_search_type = $result[0]['param_value'];
                }
            }
            // Add Default Search Type 2014/12/03 K.Sugimoto --end--

            // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --start--
            if(isset($admin_params["usagestatistics_link_display"]["param_value"])
            && strlen($admin_params["usagestatistics_link_display"]["param_value"]) > 0)
            {
                $this->usagestatistics_link_display = $admin_params["usagestatistics_link_display"]["param_value"];
            }
            else
            {
                $query = "SELECT param_value ".
                        "FROM ".DATABASE_PREFIX."repository_parameter ".
                        "WHERE param_name = ? ".
                        "AND is_delete = ? ;";
                $params = array();
                $params[] = "usagestatistics_link_display";
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
                    $this->usagestatistics_link_display = $result[0]['param_value'];
                }
            }
            
            // institution name
            if(isset($admin_params["institution_name"]["param_value"]) 
                && strlen($admin_params["institution_name"]["param_value"]) > 0)
            {
                $this->institution_name = $admin_params["institution_name"]["param_value"];
            }
            else
            {
                $query = "SELECT param_value ".
                         "FROM ".DATABASE_PREFIX."repository_parameter ".
                         "WHERE param_name = ? ".
                         "AND is_delete = ? ;";
                $params = array();
                $params[] = "institution_name";
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
                    $this->institution_name = $result[0]['param_value'];
                }
            }
            
            // Add Usage Statistics link display setting 2014/12/16 K.Matsushita --end--

            // Add ranking tab display setting 2014/12/19 K.Matsushita --start--
            if(isset($admin_params["ranking_tab_display"]["param_value"])
            && strlen($admin_params["ranking_tab_display"]["param_value"]) > 0)
            {
                $this->ranking_tab_display = $admin_params["ranking_tab_display"]["param_value"];
            }
            else
            {
                $query = "SELECT param_value ".
                        "FROM ".DATABASE_PREFIX."repository_parameter ".
                        "WHERE param_name = ? ".
                        "AND is_delete = ? ;";
                $params = array();
                $params[] = "ranking_tab_display";
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
                    $this->ranking_tab_display = $result[0]['param_value'];
                }
            }
            // Add ranking tab display setting 2014/12/19 K.Matsushita --end--
            
        	// Add DataCite 2015/02/10 K.Sugimoto --start--
	        $query = "SELECT COUNT(*) ".
	                 "FROM ".DATABASE_PREFIX."repository_doi_status ;";
            $params = array();
	        $result = $this->dbAccess->executeQuery($query, $params);
	        
	        if(count($result) > 0 && $result[0]["COUNT(*)"] != 0){
	        	$this->exist_doi_item = 1;
	        }else{
	        	$this->exist_doi_item = 0;
	        }

            if(isset($admin_params["perfix_flag"]["param_value"]) 
                && strlen($admin_params["prefix_flag"]["param_value"]) > 0)
            {
                $this->prefix_flag = $admin_params["prefix_flag"]["param_value"];
            }
            else
            {
	            $query = "SELECT param_value ".
	                     "FROM ".DATABASE_PREFIX. "repository_parameter ".
				         "WHERE `param_name` = ? ".
                         "AND is_delete = ? ;";
			    $params = array();
			    $params[] = "prefix_flag";
			    $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
                    $this->prefix_flag = $result[0]['param_value'];
                }
            }
        	// Add DataCite 2015/02/10 K.Sugimoto --end--

		    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --start--
            if(!isset($admin_params["CrossRefQueryServicesAccount"]["param_value"]))
            {
                $query = "SELECT param_value ".
                        "FROM ".DATABASE_PREFIX."repository_parameter ".
                        "WHERE param_name = ? ".
                        "AND is_delete = ? ;";
                $params = array();
                $params[] = "crossref_query_service_account";
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) > 0)
                {
			    	$admin_params["CrossRefQueryServicesAccount"]["param_value"] = $result[0]["param_value"];
                }
            }
		    // Auto Input Metadata by CrossRef DOI 2015/03/02 K.Sugimoto --end--
            
            // Add RobotList 2015/04/06 S.Suzuki --start--
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX."repository_robotlist_master " . 
                     "WHERE is_delete = ? ; ";
            $params = array();
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            
            $robotlistInfo = array();
            
            for ($ii = 0; $ii < count($result); $ii++){
                $robotlistInfo[$ii]["robotlist_id"] = $result[$ii]["robotlist_id"];
                $robotlistInfo[$ii]["url"] = $result[$ii]["robotlist_url"];
                $robotlistInfo[$ii]["check"]  = $result[$ii]["is_robotlist_use"];
                $robotlistInfo[$ii]["version"]  = $result[$ii]["robotlist_version"];
                $robotlistInfo[$ii]["date"]  = $result[$ii]["robotlist_date"];
                $robotlistInfo[$ii]["revision"]  = $result[$ii]["robotlist_revision"];
                $robotlistInfo[$ii]["author"]  = $result[$ii]["robotlist_author"];
            }
            
            $admin_params["robotlist"] = $robotlistInfo;
            
            // lock テーブルを見て実行中か確認
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX."repository_lock " . 
                     "WHERE process_name = ? ; ";
            $params = array();
            $params[] = "Repository_Action_Common_Robotlist";
            
            $result = $this->dbAccess->executeQuery($query, $params);
            
            $this->logTableStatus = $result[0]["status"];
            // Add RobotList 2015/04/06 S.Suzuki --start--
            
            // ----------------------------------------------------
            // 終了処理
            // ----------------------------------------------------
            $this->Session->setParameter("edit_start_date", $this->TransStartDate);     // 編集開始時刻を保存
            $this->Session->setParameter("admin_params", $admin_params);
            
            // Add tab 2009/01/14 A.Suzuki --start--
            if($this->Session->getParameter("admin_active_tab") == ""){
                $this->admin_active_tab = 0;
            } else {
                $this->admin_active_tab = $this->Session->getParameter("admin_active_tab");
            }
            $this->Session->removeParameter("admin_active_tab");
            // Add tab 2009/01/14 A.Suzuki --end--
                    
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
            
            // Add review mail setting 2009/09/28 Y.Nakao --start--
            $this->setLangResource();
            // Add review mail setting 2009/09/28 Y.Nakao --end--
            
            return 'success';
        } catch ( RepositoryException $Exception) {
            //エラーログ出力
            $this->logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
                $Exception->getCode(),          //ログID
                $Exception->getMessage(),       //主メッセージ
                $Exception->getDetailMsg() );   //詳細メッセージ           
            //アクション終了処理
            $this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
            //異常終了
            $this->Session->setParameter("error_msg", $user_error_msg);
            return "error";
        }
    }    
   
    /**
     * [[再帰的にインデックステーブルの値をソート]]
     */
    function sortIndexTable($index_table, $nest, $parent_index_id, &$index_array){
        // 現在のnestを親とするインデックスを取得, show_order昇順にソート
        $children = array();
        for( $ii=0; $ii<count($index_table); $ii++ ) {
            if($index_table[$ii]['parent_index_id'] == $parent_index_id) {
                array_push($children, $index_table[$ii]);
            }
        }
        for( $ii=0; $ii<count($children); $ii++ ) {
            for( $jj=0; $jj<count($children)-$ii-1; $jj++ ) {
                if($index_table[$jj]['show_order'] > $index_table[$jj+1]['show_order']) {
                    $buf = $index_table[$jj];
                    $index_table[$jj] = $index_table[$jj+1];
                    $index_table[$jj+1] = $buf;
                }
            }  
        }
        // 自分を詰めた後、子孫ノードを詰めに行く
        for( $ii=0; $ii<count($children); $ii++ ) {
            // 階層分、表示用インデックス名をオフセット
            for( $jj=0; $jj<$nest; $jj++ ) {
                $children[$ii]['index_name'] = '--' . $children[$ii]['index_name'];
                $children[$ii]['index_name_english'] = '--' . $children[$ii]['index_name_english'];
            }
            // 階層情報を追加
            $children[$ii]['nest'] = $nest;
            // 自分を結果に追加
            array_push($index_array, $children[$ii]);
            // 再帰的に子孫を追加
            $this->sortIndexTable($index_table, $nest+1, $children[$ii]['index_id'], $index_array);
        }
        return true;
    }
    
    /**
     * Set index_id create cover to parameters
     *
     * @param array $admin_params
     */
    function setPdfCoverParamsToAdminParams(&$admin_params){
        // Get cover params by param name
        // headerType
        $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE);
        $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE]
            = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
        if(strlen($admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE]) == 0)
        {
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TYPE]
                = RepositoryConst::PDF_COVER_HEADER_TYPE_TEXT;
        }
        
        // headerText
        $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TEXT);
        $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_TEXT]
            = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
        
        // headerImage
        $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE);
        if(strlen($result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE]) == 0)
        {
            // no image
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["exists"] = "false";
        }
        else
        {
            // Exist image
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["exists"] = "true";
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["src"]
                = BASE_URL."/?action=repository_action_common_download&pdf_cover_header=true";
            
            // Create image
            $width = 0;
            $height = 0;
            $maxWidth = 200;
            $maxHeight = 100;
            $img = imagecreatefromstring($result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE]);
            if($img !== false)
            {
                // Setting width
                $width = imagesx($img);
                // Setting height
                $height = imagesy($img);
                // Drop image
                imagedestroy($img);
                
                // Resize
                $this->resizeImage($width, $height, $maxWidth, $maxHeight);
            }
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["width"] = $width;
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_IMAGE]["height"] = $height;
        }
        
        // headerAlign
        $result = $this->getPdfCoverParamRecord(RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN);
        $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN]
            = $result[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
        if(strlen($admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN]) == 0)
        {
            $admin_params["pdfCover"][RepositoryConst::PDF_COVER_PARAM_NAME_HEADER_ALIGN]
                = RepositoryConst::PDF_COVER_HEADER_ALIGN_RIGHT;
        }
    }

    /**
     * 医中誌への連携状態をチェックする
     * 
     * @return bool 医中誌への連携状態
     */
    private function checkLoginIchushi()
    {
        //0. 初期化
        $login_id = "";
        $login_passwd = "";
        $cookie = "";
        
        //1. ログイン情報取得
        $result = $this->getLoginInfoIchushi($login_id,$login_passwd);
        if($result === false)
        {
            return false;
        }
        
        //2. ログイン
        $result = $this->loginIchushi($login_id, $login_passwd, $cookie);
        if($result === false)
        {
            return false;
        } else {
            //3. ログアウト
            $this->logoutIchushi($cookie);
        }
        
        return true;
    }
}
?>
