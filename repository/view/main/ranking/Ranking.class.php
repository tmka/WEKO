<?php
// --------------------------------------------------------------------
//
// $Id: Ranking.class.php 57108 2015-08-26 01:03:29Z keiya_sugimoto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';

/**
 * Show Ranking
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Ranking extends WekoAction 
{
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
    
    var $count_refer = 0;                   // 閲覧回数ランキング表示数
    var $count_download = 0;                // ダウンロード数ランキング表示数
    var $count_user = 0;                    // ユーザーランキング表示数
    var $count_keyword = 0;                 // キーワードランキング表示数
    var $count_recent = 0;                  // 新着アイテム表示数
    
    var $thumbnail = array();               // サムネイルの有無を格納 

    // Add child index display more 2009/01/20 Y.Nakao --start--
    // ランキング表示かどうか判定(アコーディオン表示用JavaScriptをhtmlに組み込むかの判定に使用)
    // display ranking or snippet
    var $display_ranking = "false";
    // Add child index display more 2009/01/20 Y.Nakao --end--
    
    // Add title_english 2009/07/22 A.Suzuki --start--
    var $select_lang = "";                  // 選択中の言語
    // Add title_english 2009/07/22 A.Suzuki --end--
    
    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
    var $uri_export = "";
    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
    
    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    var $oaiore_icon_display = "";
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    // Add index list 2010/04/13 S.Abe --start--
    var $select_index_list_display = "";
    var $select_index_list = array();
    // Add index list 2010/04/13 S.Abe --end--

    // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --start--
    public $active_search_flag = null;              // flag for detail search or simple search
    public $detail_search_usable_item = array();    // detail search usable item
    public $detail_search_item_type = array();      // search itemtype
    public $detail_search_select_item = array();    // search itemtype
    public $default_detail_search = array();        // default detail search items
    // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --end--

    // Fix download request url 2015/02/03 T.Ichikawa --start--
    var $fileIdx = "";                              // ログイン後のファイルダウンロード情報
    var $block_id = null;
    // Fix download request url 2015/02/03 T.Ichikawa --end--
    
    private $rank_num = 5;
    var $search_type = null;

    /**
     * create ranking data
     *
     * @access  public
     */
    protected function executeApp()
    {
        $this->select_lang = $this->Session->getParameter("_lang");
        $this->showTopPageProcess();
        
        // Add ranking tab display setting 2015/03/24 K.Sugimoto --start--
        $result = $this->getAdminParamByName('ranking_tab_display');
        if ($result == 0)
        {
            return "invalid";
        }
        // Add ranking tab display setting 2015/03/24 K.Sugimoto --end--

        // ランキング数（新着アイテム以外）
        $items = $this->getAdminParamByName('ranking_disp_num');
        if($items != "" && $items != null){
            $this->rank_num = $items;
        }
        
        // Add ranking update setting 2008/12/1 A.Suzuki --start--
        // is ranking realtime?
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'ranking_disp_setting'; ";
        $result = $this->Db->execute($query);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        // get each ranking show flag
        $this->ranking_is_disp_browse_item = $this->getAdminParamByName('ranking_is_disp_browse_item');
        $this->ranking_is_disp_download_item = $this->getAdminParamByName('ranking_is_disp_download_item');
        $this->ranking_is_disp_item_creator = $this->getAdminParamByName('ranking_is_disp_item_creator');
        $this->ranking_is_disp_keyword = $this->getAdminParamByName('ranking_is_disp_keyword');
        $this->ranking_is_disp_recent_item = $this->getAdminParamByName('ranking_is_disp_recent_item');
        
        if($result[0]["param_value"] == 1) {
            // no realtime, show ranking from database
            $this->setRankingDataFromDatabase();
            
        } else {
            // Add ranking update setting 2008/12/1 A.Suzuki --end--
            // real time ranking
            $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
            $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
            
            // set OFF create ranking that is not show
            if ( $this->ranking_is_disp_browse_item != "1" ){
                $ranking->toOffReferRanking();
            }
            if ( $this->ranking_is_disp_download_item != "1" ){
                $ranking->toOffDownloadRanking();
            }
            if ( $this->ranking_is_disp_item_creator != "1" ){
                $ranking->toOffUserRanking();
            }
            if ( $this->ranking_is_disp_keyword != "1" ){
                $ranking->toOffKeywordRanking();
            }
            if ( $this->ranking_is_disp_recent_item != "1" ){
                $ranking->toOffNewItemRanking();
            }
            
            // create ranking data
            $ranking->execute();

            // is show each ranking
            if ( $this->ranking_is_disp_browse_item == "1" ){
                $this->referRanking($ranking);
            }
            if ( $this->ranking_is_disp_download_item == "1" ){
                $this->downloadRanking($ranking);
            }
            if ( $this->ranking_is_disp_item_creator == "1" ){
                $this->userRanking($ranking);
            }
            if ( $this->ranking_is_disp_keyword == "1" ){
                $this->keywordRanking($ranking);
            }
            if ( $this->ranking_is_disp_recent_item == "1" ){
                $this->recentRanking($ranking);
            }
        }
        
        $this->setEmptyRanking();
        
        $this->help_icon_display = $this->getAdminParamByName('help_icon_display');
        
        return $this->getViewResult();
    }

    /**
     * create view detail ranking data
     *
     */
    private function referRanking($ranking)
    {
        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $items = $ranking->getReferRanking();
        
        $len = count($items);
        for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
            $disp_flg = false;
            if($ii < 3){
                $disp_flg = true;
            }
            
            // check thumbnail
            // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
            $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], "", "refer", $ii);
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
            
            array_push($this->refer_ranking,Array(($ii+1),$display_title,$items[$ii]['CNT'],$items[$ii]['item_id'],$items[$ii]['item_no'], $disp_flg));
        }
        $this->count_refer = count($this->refer_ranking);          
    }
    
    /**
     * create download ranking data
     *
     */
    private function downloadRanking()
    {
        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $items = $ranking->getDownloadRanking();
        
        $len = count($items);
        for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
            $disp_flg = false;
            if($ii < 3){
                $disp_flg = true;
            }
          
            // check thumbnail
            // modify show thubnail all rank 2011/10/20 K.Matsuo --start--
            $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], $items[$ii]['file_no'], "download", $ii);
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
            array_push($this->download_ranking,Array(($ii+1),$display_title."(".$items[$ii]['file_name'].")",$items[$ii]['CNT'],$items[$ii]['item_id'],$items[$ii]['item_no'],$disp_flg));
        }
        $this->count_download = count($this->download_ranking);
    }

    /**
     * create regist users ranking data
     *
     */
    private function userRanking()
    {
        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $items = $ranking->getUserRanking();
        
        $len = count($items);
        for ( $ii=0; $ii<$len && $ii<$this->rank_num; $ii++ ){
            $hlink="";
            $disp_flg = false;
            if($ii < 3){
                $disp_flg = true;
            }
            array_push($this->user_ranking,Array(($ii+1),$items[$ii]['handle'],$items[$ii]['CNT'],$hlink,$disp_flg));
        }
        $this->count_user = count($this->user_ranking);
    }
    
    /**
     * create keyword ranking data
     *
     */
    private function keywordRanking()
    {
        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $items = $ranking->getKeywordRanking();
        
        $len = count($items);
        for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
            $hlink = BASE_URL. "/?action=repository_opensearch&keyword=". urlencode($items[$ii]['search_keyword']);
            $disp_flg = false;
            if($ii < 3){
                $disp_flg = true;
            }
            array_push($this->keyword_ranking,Array(($ii+1),$items[$ii]['search_keyword'],$items[$ii]['CNT'],$hlink,$disp_flg));
        }
        $this->count_keyword = count($this->keyword_ranking);
    }
    
    /**
     * create new item's data
     *
     */
    private function recentRanking()
    {
        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $ranking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $items = $ranking->getNewItemRanking(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
          
        $len = count($items);
        for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
            $date = substr($items[$ii]['shown_date'],0,10);
            $disp_flg = false;
            if($ii < 3){
                $disp_flg = true;
            }
            $this->checkDownload($items[$ii]['item_id'], $items[$ii]['item_no'], "", "recent", $ii);
            
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
                 " ORDER BY file_no;";
        $params = null;
        $params[] = $item_id;
        $params[] = $item_no;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
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
     * getter admin param
     *
     * @param string $paramName
     * @return param_value
     */
    private function getAdminParamByName($paramName)
    {
        $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name=?";
        $params = array();
        $params[] = $paramName;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) === 0){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        
        return $result[0]['param_value'];
    }
    
    /**
     * create ranking data from database
     *
     * @return unknown
     */
    private function setRankingDataFromDatabase()
    {
       // get ranking data from repository_ranking
       $query = "SELECT * ".
                "FROM " .DATABASE_PREFIX ."repository_ranking ".
                "WHERE is_delete = 0 ".
                "ORDER BY rank;";
       $result = $this->Db->execute($query);
       if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
       }
       
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
    }
    
    /**
     * if ranking data less 3, push empty data
     *
     */
    private function setEmptyRanking()
    {
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
    }
    
    private function getViewResult()
    {
        $smartphoneFlg = false;
        if(_REPOSITORY_SMART_PHONE_DISPLAY)
        {
            $smartphoneFlg = true;
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
            if($smartphoneFlg){
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
            if($smartphoneFlg){
                return 'success_sp';
            } else {
                return 'success';
            }
            // Add smart phone support T.Koyasu 2012/04/02 -end-
        }
    }
    
    /** 
     * get download status from session
     * when download export-file from repository_uri access
     *
     */
    private function downloadExportFileFromRepositoryUri()
    {
        $this->uri_export = $this->Session->getParameter("uri_export");
        $this->Session->removeParameter("uri_export");
           if(!is_array($this->uri_export) || count($this->uri_export) != 4){
            $this->uri_export = null;
        } else {
            $tmp_status = $this->uri_export["status"];
            $repositoryAction = new RepositoryAction();
            $repositoryAction->Db = $this->Db;
            $repositoryAction->Session = $this->Session;
            $repositoryAction->TransStartDate = $this->accessDate;
            $this->uri_export["status"] = $repositoryAction->checkExportFileDownload($this->uri_export["status"]);
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
    }
    
    /**
     * get item_id, item_no, attribute_id and file_no of download file when download file after login
     *
     */
    private function isDownloadFileAfterLogin()
    {
        // add file download after login T.Ichikawa 2015/02/03 --start-- 
        $user_id = $this->Session->getParameter("_user_id");
        if(strlen($this->fileIdx) == 0 && $user_id != "0" && strlen($this->Session->getParameter("_login_id")) != 0)
        {
            // file download login.
            $this->fileIdx = $this->Session->getParameter('repository'.$this->block_id.'FileDownloadKey');
        }
        $this->Session->removeParameter('repository'.$this->block_id.'FileDownloadKey');
        // add file download after login T.Ichikawa 2015/02/03 --end--
    }
    
    /**
     * get index list when show index list
     *
     */
    private function showIndexListForTopPage()
    {
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Db = $this->Db;
        $repositoryAction->Session = $this->Session;
        $repositoryAction->TransStartDate = $this->accessDate;
        $this->select_index_list_display = $repositoryAction->getSelectIndexListDisplay();
        if($this->select_index_list_display == 1) {
            // Add Advanced Search 2013/11/26 R.Matsuura --start--
            $indexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->accessDate);
            // Add Advanced Search 2013/11/26 R.Matsuura --end--
            $this->select_index_list = $indexManager->getDisplayIndexList($this->repository_admin_base, $this->repository_admin_room);    
        }
        
        $this->oaiore_icon_display = $this->getAdminParamByName('oaiore_icon_display');
    }
    
    /**
     * setting and get parameter by session when top page is ranking
     *
     */
    private function showTopPageProcess()
    {
        $this->downloadExportFileFromRepositoryUri();
        
        $this->isDownloadFileAfterLogin();
        
        $this->showIndexListForTopPage();
    }
}
?>
