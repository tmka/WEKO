<?php
// --------------------------------------------------------------------
//
// $Id: Ranking.class.php 44305 2014-11-21 08:22:24Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Ranking extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    var $Db = null;
    var $Session = null;
    var $request = null;
    
    var $refer_ranking = Array();
    var $download_ranking = Array();
    var $user_ranking = Array();
    var $keyword_ranking = Array();
    var $newitem_ranking = Array();

    var $ranking_is_disp_browse_item = "1";
    var $ranking_is_disp_download_item = "1";
    var $ranking_is_disp_item_creator = "1";
    var $ranking_is_disp_keyword = "1";
    var $ranking_is_disp_recent_item = "1";

    // ランキング集計期間（新着アイテム以外）
    var $rank_term = 365;
    // ランキング数（新着アイテム以外）
    var $rank_num = 5;
    // 新着アイテム扱いの期間（過去Ｘ日）
    var $newitem_term = 14;

    // Add log exception from ip address 2008.11.10 Y.Nakao --start--
    var $log_exception = "";
    // Add log exception from ip address 2008.11.10 Y.Nakao --end--
    
    var $count_refer = 0;            // 閲覧回数ランキング表示数
    var $count_download = 0;            // ダウンロード数ランキング表示数
    var $count_user = 0;                // ユーザーランキング表示数
    var $count_keyword = 0;            // キーワードランキング表示数
    var $count_recent = 0;            // 新着アイテム表示数
    
    var $thumbnail = array();        // サムネイルの有無を格納 
//    var $IsDownLoadFile = array();        // ファイルダウンロード可否

    // Add child index display more 2009/01/20 Y.Nakao --start--
    // ランキング表示かどうか判定(アコーディオン表示用JavaScriptをhtmlに組み込むかの判定に使用)
    // display ranking or snippet
    var $display_ranking = "false";
    // Add child index display more 2009/01/20 Y.Nakao --end--
    
    // Add title_english 2009/07/22 A.Suzuki --start--
    var $select_lang = "";    // 選択中の言語
    // Add title_english 2009/07/22 A.Suzuki --end--
    
    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
    var $uri_export = "";
    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    var $oaiore_icon_display = "";
    // Set help icon setting 2010/02/10 K.Ando --end--

    // Add log reset ranking refer 2010/02/18 K.Ando --start--
    var $ranking_reset_last_date = "";
    var $ranking_term_date = "";
    // Add log reset ranking refer 2010/02/18 K.Ando --end--
    
    // Add came from flash 2011/01/04 H.Goto --start--
    var $version_flg = null;
    // Add came from flash 2011/01/04 H.Goto --end--
    
    // Add index list 2010/04/13 S.Abe --start--
    var $select_index_list_display = "";
    var $select_index_list = array();
    // Add index list 2010/04/13 S.Abe --end--

    // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --start--
    public $active_search_flag = null;  // flag for detail search or simple search
    public $detail_search_usable_item = array();   // detail search usable item
    public $all_search_type = null;     // all search type
    public $detail_search_item_type = array();     // search itemtype
    public $detail_search_select_item = array();   // search itemtype
    public $default_detail_search = array();       // default detail search items
    // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --end--

    function SetData($Session, $Db, $log_exception, $ranking_term_date, $TransStartDate) {
        $this->Session = $Session;
        $this->Db = $Db;
        $this->log_exception = $log_exception;
        $this->ranking_term_date = $ranking_term_date;
        $this->TransStartDate = $TransStartDate;
        
        // Add Bug-fix WEKO-2014-005 2014/04/25 T.Koyasu --start--
        $this->dbAccess = new RepositoryDbAccess($this->Db);
        // Add Bug-fix WEKO-2014-005 2014/04/25 T.Koyasu --end--
        
        // Add Advanced Search 2013/11/26 R.Matsuura --start--
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        // Add Advanced Search 2013/11/26 R.Matsuura --end--        // Add tree access control list 2012/03/07 T.Koyasu -start-
        $role_auth_id = $this->Session->getParameter('_role_auth_id');
        $user_auth_id = $this->Session->getParameter('_user_auth_id');
        $user_id = $this->Session->getParameter('_user_id');
        $this->Session->removeParameter('_role_auth_id');
        $this->Session->removeParameter('_user_auth_id');
        $this->Session->setParameter('_user_id', '0');
        $this->setConfigAuthority();
        $this->Session->setParameter('_role_auth_id', $role_auth_id);
        $this->Session->setParameter('_user_auth_id', $user_auth_id);
        $this->Session->setParameter('_user_id', $user_id);
        // Add tree access control list 2012/03/07 T.Koyasu -end-
    }
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {    
           try {
               
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );    //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                $user_error_msg = 'initで既に・・・';
                throw $exception;
            }
            
            // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
            $user_id = $this->Session->getParameter("_user_id");
            $this->uri_export = $this->Session->getParameter("uri_export");
            $this->Session->removeParameter("uri_export");
               if(!is_array($this->uri_export) || count($this->uri_export) != 4){
                $this->uri_export = null;
            } else {
                $tmp_status = $this->uri_export["status"];
                $this->uri_export["status"] = $this->checkExportFileDownload($this->uri_export["status"]);
                if($tmp_status == "login"){
                    // ログイン処理を読みだす
                    // sessionにはreloadを保存、表示にはloginを使用
                    $this->Session->setParameter("uri_export", $this->uri_export);
                    $this->uri_export["status"] = "login";
                } else if($tmp_status == "download" && $this->uri_export["status"] == "true"){
                    // ログイン処理実行
                    // sessionは解放、表示にはdownloadを使用
                    $this->uri_export["status"] = "download";
                } else {
                    // ログインキャンセルとかエラーとか予期せぬ事態
                    $this->uri_export["status"] = "";
                    $tmp = false;
                }
            }
            // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --end--
            
            // 未ログイン時のファイルダウンロード対応 2008/09/02 Y.Nakao --end--
            
            // Add title_english 2009/07/22 A.Suzuki --start--
            $this->select_lang = $this->Session->getParameter("_lang");
            // Add title_english 2009/07/22 A.Suzuki --end--    
            // Add index list 2010/04/13 S.Abe --start--
            $this->select_index_list_display = $this->getSelectIndexListDisplay();
            if($this->select_index_list_display == 1) {
                // Add Advanced Search 2013/11/26 R.Matsuura --start--
                $indexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
                // Add Advanced Search 2013/11/26 R.Matsuura --end--
                $this->select_index_list = $indexManager->getDisplayIndexList($this->repository_admin_base, $this->repository_admin_room);    
            }
            // Add index list 2010/04/13 S.Abe --end--
            
            
            // ランキング数（新着アイテム以外）
            $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_term_stats'");
               if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
                $this->rank_term = $items[0]['param_value'];
            }
            // ランキング数（新着アイテム以外）
            $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_disp_num'");
               if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
                $this->rank_num = $items[0]['param_value'];
            }
            // 新着アイテム扱いの期間（過去Ｘ日）
            $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_term_recent_regist'");
               if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
                $this->newitem_term = $items[0]['param_value'];
            }
            // Add log reset ranking refer 2010/02/18 K.Ando --start--
            // last reset-ranking date
            $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_last_reset_date';");
               if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
                $this->ranking_reset_last_date = $items[0]['param_value'];
            }
            if($this->ranking_reset_last_date != "" )
            {
                // Fix date calculate 2010/07/29 A.Suzuki --start--
                //$logjikan = time()- 60 * 60 * 24 * $this->rank_term;
                //$jikan = strtotime($this->ranking_reset_last_date);
                //$this->ranking_term_sec = $jikan <= $logjikan  ? $logjikan : $jikan;
                $this->ranking_reset_last_date = str_replace("/","-",$this->ranking_reset_last_date);
                $query = "SELECT DATE_SUB(NOW(), INTERVAL ? DAY) AS rank_date;";
                $params = array();
                $params[] = $this->rank_term;
                $result = $this->Db->execute($query, $params);
                $rank_term_date = $result[0]['rank_date'];
                $query = "SELECT DATEDIFF(?, ?) AS date_diff;";
                $params = array();
                $params[] = $rank_term_date;
                $params[] = $this->ranking_reset_last_date;
                $result = $this->Db->execute($query, $params);
                if($result[0]['date_diff'] >= 0){
                    $this->ranking_term_date = $rank_term_date;
                } else {
                    $this->ranking_term_date = $this->ranking_reset_last_date;
                }
                // Fix date calculate 2010/07/29 A.Suzuki --end--
            }else
            {
                // Fix date calculate 2010/07/29 A.Suzuki --start--
                //$this->ranking_term_sec = time() - 60 * 60 * 24 * $this->rank_term;
                $query = "SELECT DATE_SUB(NOW(), INTERVAL ? DAY) AS rank_date;";
                $params = array();
                $params[] = $this->rank_term;
                $result = $this->Db->execute($query, $params);
                $this->ranking_term_date = $result[0]['rank_date'];
                // Fix date calculate 2010/07/29 A.Suzuki --end--
            }
            // Add log reset ranking refer 2010/02/18 K.Ando --end--
            
            // Add ranking update setting 2008/12/1 A.Suzuki --start--
                $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                     "WHERE param_name = 'ranking_disp_setting'; ";
            $result = $this->Db->execute($query);
            if($result === false){
                return 'error';
            }
            
            if($result[0]["param_value"] == 1) {
                // get ranking data from repository_ranking
                $query = "SELECT * ".
                         "FROM " .DATABASE_PREFIX ."repository_ranking ".
                         "WHERE is_delete = 0 ".
                         "ORDER BY rank;";
                $result = $this->Db->execute($query);
                if($result === false){
                    return 'error';
                }
    
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_browse_item'");
                $this->ranking_is_disp_browse_item = $items[0]['param_value'];
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_download_item'");
                $this->ranking_is_disp_download_item = $items[0]['param_value'];
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_item_creator'");
                $this->ranking_is_disp_item_creator = $items[0]['param_value'];
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_keyword'");
                $this->ranking_is_disp_keyword = $items[0]['param_value'];
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_recent_item'");
                $this->ranking_is_disp_recent_item = $items[0]['param_value'];
                
                for ( $ii=0; $ii<count($result); $ii++ ){
                    switch($result[$ii]['rank_type']){
                        case "referRanking":
                            if($this->ranking_is_disp_browse_item == "1"){
                                if(count($this->refer_ranking) < $this->rank_num) {
                                    $disp_flg = false;
                                      if(count($this->refer_ranking) < 3){
                                        // check thumbnail
                                        // modify show thumbnail all rank 2011/10/20 K.Matsuo --start--
                                          $disp_flg = true;
                                        $this->checkDownload($result[$ii]['item_id'], $result[$ii]['item_no'], "", "refer", count($this->refer_ranking));
                                        // modify show thumbnail all rank 2011/10/20 K.Matsuo --end--
                                      }
                                      
                                      // Add title_english 2009/07/22 A.Suzuki --start--
                                      if($this->select_lang == "japanese"){
                                          if($result[$ii]['disp_name'] != "" && $result[$ii]['disp_name'] != null){
                                              $display_title = $result[$ii]['disp_name'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name_english'];
                                          }
                                      } else {
                                          if($result[$ii]['disp_name_english'] != "" && $result[$ii]['disp_name_english'] != null){
                                              $display_title = $result[$ii]['disp_name_english'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name'];
                                          }
                                      }
                                    // Add title_english 2009/07/22 A.Suzuki --end--
                                      
                                    array_push($this->refer_ranking,Array($result[$ii]['rank'],$display_title,$result[$ii]['disp_value'],$result[$ii]['item_id'],$result[$ii]['item_no'], $disp_flg));
                                }
                            }
                            break;
                        case "downloadRanking":
                            if($this->ranking_is_disp_download_item == "1"){
                                if(count($this->download_ranking) < $this->rank_num) {
                                    $disp_flg = false;
                                    // check thumbnail
                                    // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
                                    if(count($this->download_ranking) < 3){
                                          $disp_flg = true;
                                          $this->checkDownload($result[$ii]['item_id'], $result[$ii]['item_no'], $result[$ii]['file_no'], "download", count($this->download_ranking));
                                      }
                                    // modify show thubnail all rank 2011/10/20 K.Matsuo --end--
                                                                            
                                      // Add title_english 2009/07/22 A.Suzuki --start--
                                      if($this->select_lang == "japanese"){
                                          if($result[$ii]['disp_name'] != "" && $result[$ii]['disp_name'] != null){
                                              $display_title = $result[$ii]['disp_name'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name_english'];
                                          }
                                      } else {
                                          if($result[$ii]['disp_name_english'] != "" && $result[$ii]['disp_name_english'] != null){
                                              $display_title = $result[$ii]['disp_name_english'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name'];
                                          }
                                      }
                                    // Add title_english 2009/07/22 A.Suzuki --end--
                                      
                                    array_push($this->download_ranking,Array($result[$ii]['rank'],$display_title,$result[$ii]['disp_value'],$result[$ii]['item_id'],$result[$ii]['item_no'], $disp_flg));
                                }
                            }
                            break;
                        case "userRanking":
                            if($this->ranking_is_disp_item_creator == "1"){
                                if(count($this->user_ranking) < $this->rank_num) {
                                    $hlink = "";
                                    $disp_flg = false;
                                      if(count($this->user_ranking) < 3){
                                          $disp_flg = true;
                                      }
                                    // check thumbnail
                                    array_push($this->user_ranking,Array($result[$ii]['rank'],$result[$ii]['disp_name'],$result[$ii]['disp_value'],$hlink, $disp_flg));
                                }
                            }
                            break;
                        case "keywordRanking":
                            if($this->ranking_is_disp_keyword == "1"){
                                if(count($this->keyword_ranking) < $this->rank_num) {
                                    $hlink = BASE_URL. "/?action=repository_opensearch&keyword=". urlencode($result[$ii]['disp_name']);
                                    $disp_flg = false;
                                      if(count($this->keyword_ranking) < 3){
                                          $disp_flg = true;
                                      }
                                    array_push($this->keyword_ranking,Array($result[$ii]['rank'],$result[$ii]['disp_name'],$result[$ii]['disp_value'],$hlink, $disp_flg));
                                }
                            }
                            break;
                        case "recentRanking":
                            if($this->ranking_is_disp_recent_item == "1"){
                                if(count($this->newitem_ranking) < $this->rank_num) {
                                    $disp_flg = false;
                                      if(count($this->newitem_ranking) < 3){
                                          $disp_flg = true;
                                        // check thumbnail
                                        // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
                                        $this->checkDownload($result[$ii]['item_id'], $result[$ii]['item_no'], "", "recent", count($this->newitem_ranking));
                                        // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
                                      }
                                      
                                      // Add title_english 2009/07/22 A.Suzuki --start--
                                      if($this->select_lang == "japanese"){
                                          if($result[$ii]['disp_name'] != "" && $result[$ii]['disp_name'] != null){
                                              $display_title = $result[$ii]['disp_name'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name_english'];
                                          }
                                      } else {
                                          if($result[$ii]['disp_name_english'] != "" && $result[$ii]['disp_name_english'] != null){
                                              $display_title = $result[$ii]['disp_name_english'];
                                          } else {
                                              $display_title = $result[$ii]['disp_name'];
                                          }
                                      }
                                    // Add title_english 2009/07/22 A.Suzuki --end--
                                      
                                    array_push($this->newitem_ranking,Array($result[$ii]['rank'],$display_title,$result[$ii]['disp_value'],$result[$ii]['item_id'],$result[$ii]['item_no'], $disp_flg));
                                }
                            }
                            break;
                        default:
                    }                    
                  }
                  
                  $this->count_refer = count($this->refer_ranking);
                $this->count_download = count($this->download_ranking);
                $this->count_user = count($this->user_ranking);
                $this->count_keyword = count($this->keyword_ranking);
                $this->count_recent = count($this->newitem_ranking);
                
            } else {
            // Add ranking update setting 2008/12/1 A.Suzuki --end--
            
                // Add log exclusion from user-agaent 2011/04/28 H.Ito --start--
                // Call Common function ->logExclusion()
                $this->log_exception = $this->createLogExclusion(0,false);
                // Add log exclusion from user-agaent 2011/04/28 H.Ito --end--
                
                // Add tree access control list 2012/03/07 T.Koyasu -start-
                $role_auth_id = $this->Session->getParameter('_role_auth_id');
                $user_auth_id = $this->Session->getParameter('_user_auth_id');
                $user_id = $this->Session->getParameter('_user_id');
                $this->Session->removeParameter('_role_auth_id');
                $this->Session->removeParameter('_user_auth_id');
                $this->Session->setParameter('_user_id', '0');
                $this->Session->setParameter('_role_auth_id', $role_auth_id);
                $this->Session->setParameter('_user_auth_id', $user_auth_id);
                $this->Session->setParameter('_user_id', $user_id);
                // Add tree access control list 2012/03/07 T.Koyasu -end-

                // 閲覧ランキングを表示するか
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_browse_item'");
                $this->ranking_is_disp_browse_item = $items[0]['param_value'];
                if ( $this->ranking_is_disp_browse_item=="1" ){
                    $this->referRanking();
                }
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_download_item'");
                $this->ranking_is_disp_download_item = $items[0]['param_value'];
                if ( $this->ranking_is_disp_download_item=="1" ){
                    $this->downloadRanking();
                }
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_item_creator'");
                $this->ranking_is_disp_item_creator = $items[0]['param_value'];
                if ( $this->ranking_is_disp_item_creator=="1" ){
                    $this->userRanking();
                }
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_keyword'");
                $this->ranking_is_disp_keyword = $items[0]['param_value'];
                if ( $this->ranking_is_disp_keyword=="1" ){
                    $this->keywordRanking();
                }
                
                $items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_is_disp_recent_item'");
                $this->ranking_is_disp_recent_item = $items[0]['param_value'];
                if ( $this->ranking_is_disp_recent_item=="1" ){
                    $this->recentRanking();
                }
            }
            
            if($this->count_refer !=  0 && $this->count_refer < 3){
                for($ii=$this->count_refer;$ii < 3; $ii++){
                    array_push($this->refer_ranking,Array($ii+1,"","","","","true"));
                    $this->count_refer++;
                }
            }
            if($this->count_download != 0 && $this->count_download < 3){
                for($ii=$this->count_download;$ii < 3; $ii++){
                    array_push($this->download_ranking,Array($ii+1,"","","","","true"));
                    $this->count_download++;
                }
            }
               if($this->count_user != 0 && $this->count_user < 3){
                for($ii=$this->count_user;$ii < 3; $ii++){
                    array_push($this->user_ranking,Array($ii+1,"","","","true"));
                    $this->count_user++;
                }
            }
               if($this->count_keyword != 0 && $this->count_keyword < 3){
                for($ii=$this->count_keyword;$ii < 3; $ii++){
                    array_push($this->keyword_ranking,Array($ii+1,"","","","true"));
                    $this->count_keyword++;
                }
            }
            if($this->count_recent != 0 && $this->count_recent < 3){
                for($ii=$this->count_recent;$ii < 3; $ii++){
                    array_push($this->newitem_ranking,Array($ii+1,"","","","","true"));
                    $this->count_recent++;
                }
            }

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
            $result = $this->getAdminParam('oaiore_icon_display', $this->oaiore_icon_display, $Error_Msg);
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            // Set help icon setting 2010/02/10 K.Ando --end--

            
               //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );    //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                throw $exception;
            }
            
            if($this->Session->getParameter("serach_screen") == 2 && $this->Session->getParameter("ranking_flg") == 1)
            {
                // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --start--
                require_once WEBAPP_DIR."/modules/repository/components/RepositorySearchRequestParameter.class.php";
                $searchParam = new RepositorySearchRequestParameter();
                $searchParam->setActionParameter();
                $this->search_type = $searchParam->all_search_type;
                $this->active_search_flag = $searchParam->active_search_flag;
                $this->detail_search_usable_item = $searchParam->detail_search_usable_item;
                $this->detail_search_item_type = $searchParam->detail_search_item_type;
                $this->detail_search_select_item = $searchParam->detail_search_select_item;
                for($ii = 0; $ii < count($this->detail_search_select_item); $ii++) {
                    if(array_key_exists("value", $this->detail_search_select_item[$ii])) {
                        $this->detail_search_select_item[$ii]["value"] = str_replace("\\", "\\\\", $this->detail_search_select_item[$ii]["value"]);
                    }
                }
                $this->default_detail_search = $searchParam->default_detail_search;
                // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --end--
                $this->Session->setParameter("ranking_flg", 0);
                // Add smart phone support T.Koyasu 2012/04/03 -start-
                if($this->smartphoneFlg){
                    return 'snippet_sp';
                } else {
                    return 'snippet';
                }
                // Add smart phone support T.Koyasu 2012/04/03 -end-
            } else {
                // Add child index display more 2009/01/20 Y.Nakao --start--
                // ランキングのみの表示であるフラグを立てる this view action is dispay ranking
                $this->display_ranking = "true";
                // Add child index display more 2009/01/20 Y.Nakao --end-- 
                
                // Add smart phone support T.Koyasu 2012/04/02 -start-
                if($this->smartphoneFlg){
                    return 'success_sp';
                } else {
                    return 'success';
                }
                // Add smart phone support T.Koyasu 2012/04/02 -end-
            }
            
         } catch ( RepositoryException $Exception) {
            //エラーログ出力
            /*
            logFile(
                "SampleAction",                    //クラス名
                "execute",                        //メソッド名
                $Exception->getCode(),            //ログID
                $Exception->getMessage(),        //主メッセージ
                $Exception->getDetailMsg() );    //詳細メッセージ            
            */
            //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            return "error";
        }        
    }

    /**
     * 閲覧回数ランキング計算
     *
     */
    function referRanking()
    {
        $items = $this->getReferRankingData();   // ranking acquisition portion is made into a function K.Matsuo 2011/11/18
          
          $len = count($items);
        for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
              $disp_flg = false;
              if($ii < 3){
                  $disp_flg = true;
              }
                      
              // check thumbnail
              // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
//              if($ii == 0){
                  $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], "", "refer", $ii);
//              }              
            // modify show thubnail all rank 2011/10/20 K.Matsuo --end--
              // Add title_english 2009/07/22 A.Suzuki --start--
              if($this->select_lang == "japanese"){
                  if($items[$ii]['title'] != "" && $items[$ii]['title'] != null){
                      $display_title = $items[$ii]['title'];
                  } else {
                      $display_title = $items[$ii]['title_english'];
                  }
              } else {
                  if($items[$ii]['title_english'] != "" && $items[$ii]['title_english'] != null){
                      $display_title = $items[$ii]['title_english'];
                  } else {
                      $display_title = $items[$ii]['title'];
                  }
              }
            // Add title_english 2009/07/22 A.Suzuki --end--
              
            array_push($this->refer_ranking,Array(($ii+1),$display_title,$items[$ii]['count(*)'],$items[$ii]['item_id'],$items[$ii]['item_no'], $disp_flg));
        }
          $this->count_refer = count($this->refer_ranking);          
    }
    
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --start--
    /**
     * ログDBから閲覧回数を取得
     *
     * @param unknown_type $log_exception
     * @param unknown_type $ranking_term_date
     * @return unknown
     */
    public function getReferRankingData()
    {
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $public_index_query = $repositoryIndexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
        // Make TmpTable 2014/11/07 T.Ichikawa --start--
        $now = date("YmdHis", strtotime($this->TransStartDate));
        $public_index_query = $this->replaceQueryForTemporaryTable($public_index_query, $now);
        
        $sqlCmd=" 
            SELECT item.item_id, item.item_no, item.title, item.title_english,count(*)
              FROM ". DATABASE_PREFIX ."repository_log LEFT JOIN ". DATABASE_PREFIX ."repository_item item
              ON ". DATABASE_PREFIX ."repository_log.item_id = item.item_id
              INNER JOIN ". DATABASE_PREFIX ."repository_position_index pos ON item.item_id = pos.item_id AND item.item_no = pos.item_no AND pos.is_delete = 0
              INNER JOIN (".$public_index_query.") pub ON pos.index_id = pub.index_id
              WHERE ". DATABASE_PREFIX ."repository_log.operation_id='3' 
                AND item.shown_date<=NOW()
                AND ". DATABASE_PREFIX ."repository_log.record_date>='".$this->ranking_term_date."' ".
                $this->log_exception.// Add log exception from ip address 2008.11.10 Y.Nakao
                " AND item.shown_status = 1 ".
                " AND item.is_delete = 0 ".
              "  GROUP BY ". DATABASE_PREFIX ."repository_log.item_id 
              ORDER BY count(*) desc; ";
        $result = $this->Db->execute($sqlCmd);
        
        $this->dropTemporaryTable($now);
        // Make TmpTable 2014/11/07 T.Ichikawa --end--
        return $result;
            }
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --end--

    /**
     * ダウンロードランキング計算
     *
     */
    function downloadRanking()
    {
        $items = $this->getDownloadRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
          $len = count($items);
          for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
              $disp_flg = false;
              if($ii < 3){
                  $disp_flg = true;
              }
          
              // check thumbnail
            // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
//              if($ii == 0){
                  $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], $items[$ii]['file_no'], "download", $ii);
//              }
            // modify show thubnail all rank 2011/10/20 K.Matsuo --end--
              
              // Add title_english 2009/07/22 A.Suzuki --start--
              if($this->select_lang == "japanese"){
                  if($items[$ii]['title'] != "" && $items[$ii]['title'] != null){
                      $display_title = $items[$ii]['title'];
                  } else {
                      $display_title = $items[$ii]['title_english'];
                  }
              } else {
                  if($items[$ii]['title_english'] != "" && $items[$ii]['title_english'] != null){
                      $display_title = $items[$ii]['title_english'];
                  } else {
                      $display_title = $items[$ii]['title'];
                  }
              }
            // Add title_english 2009/07/22 A.Suzuki --end--
              
              array_push($this->download_ranking,Array(($ii+1),$display_title."(".$items[$ii]['file_name'].")",$items[$ii]['count(*)'],$items[$ii]['item_id'],$items[$ii]['item_no'],$disp_flg));
          }
          $this->count_download = count($this->download_ranking);
    }
        
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --start--
    /**
     * ログDBから閲覧回数を取得
     *
     * @param unknown_type $log_exception
     * @param unknown_type $ranking_term_date
     * @return unknown
     */
    public function getDownloadRankingData()
    {
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $public_index_query = $repositoryIndexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
        // Make TmpTable 2014/11/07 T.Ichikawa --start--
        $now = date("YmdHis", strtotime($this->TransStartDate));
        $public_index_query = $this->replaceQueryForTemporaryTable($public_index_query, $now);
        // Modify for remove IE Continuation log K.Matsuo 2011/11/17 --start-- 
        $sqlCmd=" 
            SELECT item.item_id,item.item_no,item.title,item.title_english,". DATABASE_PREFIX ."repository_file.file_name,". DATABASE_PREFIX ."repository_file.file_no,count(*)
            FROM (
                SELECT DISTINCT DATE_FORMAT( record_date, '%Y-%m-%d %H:%i' ) AS record_date,
                    ip_address, item_id, item_no, attribute_id, file_no, user_id, operation_id, user_agent
                    FROM ". DATABASE_PREFIX ."repository_log
                    WHERE operation_id='2' ) AS ". DATABASE_PREFIX ."repository_log, 
            ". DATABASE_PREFIX ."repository_item item 
            INNER JOIN ". DATABASE_PREFIX ."repository_position_index pos ON item.item_id = pos.item_id AND item.item_no = pos.item_no AND pos.is_delete = 0
            INNER JOIN (".$public_index_query.") pub ON pos.index_id = pub.index_id,
            ". DATABASE_PREFIX ."repository_file
            WHERE ". DATABASE_PREFIX ."repository_log.operation_id='2' 
              AND ". DATABASE_PREFIX ."repository_log.item_id = ". DATABASE_PREFIX ."repository_file.item_id 
              AND ". DATABASE_PREFIX ."repository_log.file_no = ". DATABASE_PREFIX ."repository_file.file_no 
              AND item.item_id = ". DATABASE_PREFIX ."repository_file.item_id
              AND ". DATABASE_PREFIX ."repository_log.attribute_id = ". DATABASE_PREFIX ."repository_file.attribute_id 
              AND item.shown_date<=NOW()
              AND ". DATABASE_PREFIX ."repository_log.record_date>='".$this->ranking_term_date."' ".
              $this->log_exception.// Add log exception from ip address 2008.11.10 Y.Nakao
              " AND item.shown_status = 1 ".
              " AND item.is_delete = 0 ".
              " GROUP BY ". DATABASE_PREFIX ."repository_log.item_id, ". DATABASE_PREFIX ."repository_log.attribute_id, ". DATABASE_PREFIX ."repository_log.file_no 
            ORDER BY count(*) desc;     ";
        $result = $this->Db->execute($sqlCmd);
        $this->dropTemporaryTable($now);
        // Make TmpTable 2014/11/07 T.Ichikawa --end--
        return $result;
        // Mod OpenDepo 2014/01/31 S.Arata --end--
            }
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --end--
    

    /**
     * ユーザランキング計算
     *
     */
    function userRanking()
    {
        $items = $this->getUserRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
        
          $len = count($items);
          for ( $ii=0; $ii<$len && $ii<$this->rank_num; $ii++ ){
              $hlink="";
              $disp_flg = false;
              if($ii < 3){
                  $disp_flg = true;
              }
            array_push($this->user_ranking,Array(($ii+1),$items[$ii]['handle'],$items[$ii]['count(*)'],$hlink,$disp_flg));
          }
          $this->count_user = count($this->user_ranking);
    }
    
        
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --start--
    /**
     * ログDBからユーザランキングを取得
     *
     * @param unknown_type $log_exception
     * @param unknown_type $ranking_term_date
     * @return unknown
     */
    public function getUserRankingData(){
        $sqlCmd="SELECT ". DATABASE_PREFIX ."users.handle,count(*)
              FROM ". DATABASE_PREFIX ."repository_item
                LEFT JOIN ". DATABASE_PREFIX ."users 
                ON ". DATABASE_PREFIX ."users.user_id = ". DATABASE_PREFIX ."repository_item.ins_user_id 
                LEFT JOIN ". DATABASE_PREFIX ."repository_log 
                ON ". DATABASE_PREFIX ."repository_log.item_id = ". DATABASE_PREFIX ."repository_item.item_id 
                AND ". DATABASE_PREFIX ."repository_log.item_no = ". DATABASE_PREFIX ."repository_item.item_no 
              WHERE ". DATABASE_PREFIX ."repository_item.shown_status='1' 
                AND ". DATABASE_PREFIX ."repository_item.shown_date<=NOW() 
                AND ". DATABASE_PREFIX ."repository_item.shown_date>='".$this->ranking_term_date."' 
                AND ". DATABASE_PREFIX ."repository_log.operation_id = '1' ".
                $this->log_exception.// Add log exception from ip address 2008.11.10 Y.Nakao 
            "   GROUP BY ". DATABASE_PREFIX ."repository_item.ins_user_id 
                ORDER BY count(*) desc; ";
        $items = $this->Db->execute($sqlCmd);
        // Bug fix exclude unpublic index's item 2010/04/16 A.Suzuki --end--        
        return $items;
    }    
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --end--
    
    /**
     * 検索ワードランキング計算
     *
     */
    function keywordRanking()
    {
        $items = $this->getKeywordRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
        
          $len = count($items);
          for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
            $hlink = BASE_URL. "/?action=repository_opensearch&keyword=". urlencode($items[$ii]['search_keyword']);
              $disp_flg = false;
              if($ii < 3){
                  $disp_flg = true;
              }
            array_push($this->keyword_ranking,Array(($ii+1),$items[$ii]['search_keyword'],$items[$ii]['count(*)'],$hlink,$disp_flg));
          }
          $this->count_keyword = count($this->keyword_ranking);
    }

        
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --start--
    /**
     * ログDBからキーワード検索回数を取得
     *
     * @param unknown_type $log_exception
     * @param unknown_type $ranking_term_date
     * @return unknown
     */
    public function getKeywordRankingData(){
        $sqlCmd=" 
            SELECT ". DATABASE_PREFIX ."repository_log.search_keyword,count(*) 
              FROM ". DATABASE_PREFIX ."repository_log 
              LEFT JOIN ". DATABASE_PREFIX ."repository_item 
                ON ". DATABASE_PREFIX ."repository_log.item_id = ". DATABASE_PREFIX ."repository_item.item_id 
              WHERE ". DATABASE_PREFIX ."repository_log.operation_id='4' 
                AND NOT(". DATABASE_PREFIX ."repository_log.search_keyword='') 
                AND ". DATABASE_PREFIX ."repository_log.record_date<=NOW() 
                AND ". DATABASE_PREFIX ."repository_log.record_date>='".$this->ranking_term_date."' ".
                $this->log_exception.// Add log exception from ip address 2008.11.10 Y.Nakao
              "  GROUP BY ". DATABASE_PREFIX ."repository_log.search_keyword 
              ORDER BY count(*) desc; 
        ";
        $items = $this->Db->execute($sqlCmd);
        // Bug fix exclude unpublic index's item 2010/04/16 A.Suzuki --end--        
        return $items;
    }    
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --end--
    
    /**
     * 新着アイテム計算
     *
     */
    function recentRanking()
    {
        $items = $this->getRecentRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
          
          $len = count($items);
          for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
              $date = substr($items[$ii]['shown_date'],0,10);
              $disp_flg = false;
              if($ii < 3){
                  $disp_flg = true;
              }
              
              // check thumbnail
//              if($ii == 0){
                  $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], "", "recent", $ii);
//              }
              
              // Add title_english 2009/07/22 A.Suzuki --start--
              if($this->select_lang == "japanese"){
                  if($items[$ii]['title'] != "" && $items[$ii]['title'] != null){
                      $display_title = $items[$ii]['title'];
                  } else {
                      $display_title = $items[$ii]['title_english'];
                  }
              } else {
                  if($items[$ii]['title_english'] != "" && $items[$ii]['title_english'] != null){
                      $display_title = $items[$ii]['title_english'];
                  } else {
                      $display_title = $items[$ii]['title'];
                  }
              }
            // Add title_english 2009/07/22 A.Suzuki --end--
              
            array_push($this->newitem_ranking,Array(($ii+1),$display_title,$date,$items[$ii]['item_id'],$items[$ii]['item_no'],$disp_flg));
           }
           $this->count_recent = count($this->newitem_ranking);
    }
    
        
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --start--
    /**
     * アイテムDBから新着アイテムを取得
     *
     * @param unknown_type $log_exception
     * @param unknown_type $ranking_term_date
     * @return unknown
     */
    public function getRecentRankingData(){
        // Mod OpenDepo 2014/01/31 S.Arata --start--
        $repositoryIndexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $public_index_query = $repositoryIndexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
        // Make TmpTable 2014/11/07 T.Ichikawa --start--
        $now = date("YmdHis", strtotime($this->TransStartDate));
        $public_index_query = $this->replaceQueryForTemporaryTable($public_index_query, $now);
        
        $sqlCmd=" 
            SELECT DISTINCT item.item_id,item.item_no,item.title,item.title_english,item.shown_date
              FROM ". DATABASE_PREFIX ."repository_item item
              INNER JOIN ".DATABASE_PREFIX."repository_position_index pos
              ON item.item_id = pos.item_id AND item.item_no = pos.item_no AND pos.is_delete = 0
              INNER JOIN (".$public_index_query.") pub
              ON pos.index_id = pub.index_id
              WHERE item.shown_date >= '".$this->ranking_term_date."'
                AND item.shown_date<=NOW() ".
               " AND item.shown_status = 1 ".
               " AND item.is_delete = 0 ".
             " ORDER BY item.shown_date desc, item.item_id desc;
        ";
        $result = $this->Db->execute($sqlCmd);
        $this->dropTemporaryTable($now);
        // Make TmpTable 2014/11/07 T.Ichikawa --end--
        return $result;
        // Mod OpenDepo 2014/01/31 S.Arata --end--
            }
    // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18 --end--
    
    // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
    /**
     * ファイルダウンロード可否チェック
     * 
     * @param item_id
     * @param item_no
     * @param file_no
     * @param type
     */
    function checkDownload($item_id, $item_no, $file_no, $type, $ranking_no){

        // ファイルテーブルから検索
        $query = "  SELECT * ".
                 "    FROM ".DATABASE_PREFIX."repository_file ".
                 "   WHERE item_id = ? ".
                 "     AND item_no = ? ".
                 "       AND is_delete = 0 ".
                 "ORDER BY file_no;";
        $params = null;
        $params[] = $item_id;
        $params[] = $item_no;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        
        $file_data = null;
        
        // cheak file
        if(count($result) < 0){
            // no file
            $this->thumbnail[$type][$ranking_no] = array();
            return false;
        } else {
            // cheak thumbnail
            if($file_no == "" || $file_no == null){
                for($ii = 0; $ii < count($result); $ii++){
                    if($result[$ii]['file_prev_name'] != ""){
                        $file_data = $result[$ii];
                        break;
                    }
                }
            } else {
                // search specified file no
                for($ii = 0; $ii < count($result); $ii++){
                    if($result[$ii]['file_no'] == $file_no){
                        if($result[$ii]['file_prev_name'] != ""){
                            $file_data = $result[$ii];
                            break;
                        }
                    }
                }
            }
            if($file_data == null){
                // no thumbnail
                $this->thumbnail[$type][$ranking_no] = array();
                return false;
            }
            
            $this->thumbnail[$type][$ranking_no] = array($file_data['item_id'], $file_data['item_no'], $file_data['attribute_id'], $file_data['file_no']);
        }
    }
    // modify show thubnail all rank 2011/10/20 K.Matsuo --end--
    
    /**
     * ランキング処理用に一時テーブルを使用する
     * 
     * @param string $query
     * @param string $date
     */
     private function replaceQueryForTemporaryTable($mod_query, $date) {
        // 一時テーブル作成
        $query = "CREATE TEMPORARY TABLE ". DATABASE_PREFIX. "repository_index_browsing_authority_".$date." ".
                 "( PRIMARY KEY (`index_id`), ".
                   "KEY `index_browsing_authority` (`exclusive_acl_role_id`,`exclusive_acl_room_auth`,`public_state`,`pub_date`,`is_delete`), ".
                   "KEY `index_public_state` (`public_state`,`pub_date`,`is_delete`) ) ".
                 "SELECT * FROM ". DATABASE_PREFIX. "repository_index_browsing_authority ;";
        $result = $this->Db->execute($query);
        
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --start--
        // repository_index_browsing_groups is multiple exist in $mod_query
        // temporary table can read only once in query
        // therefore, create temporary table more than once
        // 一時テーブルを一つのクエリ内で複数回参照するとエラーとなる
        // $mod_query内にはrepository_index_browsing_groupsの記述が複数回(1~2)含まれているため、
        // $mod_query内の出現回数を調べ、その分ユニークな一時テーブルを作成している
        $word_num = mb_substr_count($mod_query, DATABASE_PREFIX. "repository_index_browsing_groups");
        for($temp_table_num = 0; $temp_table_num < $word_num; $temp_table_num++){
            $query = "CREATE TEMPORARY TABLE ". DATABASE_PREFIX. "repository_index_browsing_groups_".$date."_". $temp_table_num. " ".
                     "( PRIMARY KEY (`index_id`,`exclusive_acl_group_id`) ) ".
                     "SELECT * FROM ". DATABASE_PREFIX. "repository_index_browsing_groups ;";
            $result = $this->Db->execute($query);
        }
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --end--
        
        $query = "CREATE TEMPORARY TABLE ". DATABASE_PREFIX. "pages_users_link_".$date." ".
                 "( PRIMARY KEY (`room_id`,`user_id`), ".
                   "KEY `user_id` (`user_id`) ) ".
                 "SELECT * FROM ". DATABASE_PREFIX. "pages_users_link ;";
        $result = $this->Db->execute($query);
        // クエリを一時テーブルを参照するよう修正
        $mod_query = str_replace(DATABASE_PREFIX."repository_index_browsing_authority", 
                                 DATABASE_PREFIX."repository_index_browsing_authority_".$date, 
                                 $mod_query);
        
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --start--
        // replace repository_index_browsing_groups to repository_index_browsing_groups
        $pattern = "/". DATABASE_PREFIX. "repository_index_browsing_groups[^_]/";
        $limit = 1;
        for($temp_table_num = 0; $temp_table_num < $word_num; $temp_table_num++){
            $replacement = DATABASE_PREFIX."repository_index_browsing_groups_".$date. "_". $temp_table_num. " ";
            $mod_query = preg_replace($pattern, $replacement, $mod_query, $limit);
        }
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --end--
        
        $mod_query = str_replace(DATABASE_PREFIX."pages_users_link", 
                                 DATABASE_PREFIX."pages_users_link_".$date, 
                                 $mod_query);
        
        return $mod_query;
     }
     
    /**
     * ランキング処理用の一時テーブルを削除する
     * 
     * @param string $date
     */
     private function dropTemporaryTable($date) {
        $this->Db->execute("DROP TABLE IF EXISTS ".DATABASE_PREFIX ."repository_index_browsing_authority_".$date.";");
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --start--
        // drop table to all temporary table "repository_index_browsing_groups_YYYYMMDD_?" by wild card
        $this->Db->execute("DROP TABLE IF EXISTS ".DATABASE_PREFIX ."repository_index_browsing_groups_".$date."_%;");
        // Bug Fix temporary table can read only once 2014/11/20 T.Koyasu --end--
        $this->Db->execute("DROP TABLE IF EXISTS ".DATABASE_PREFIX ."pages_users_link_".$date.";");
     }
}
?>
