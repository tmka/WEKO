<?php
// --------------------------------------------------------------------
//
// $Id: Snippet.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearch.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';

/**
 * top page view class
 *
 *   refactoring at search API refactoring. 2013/04/26 Nakao
 *
 * @package     Repository_View_Main_Item_Snippet
 * @access      public
 */
class Repository_View_Main_Item_Snippet extends RepositoryAction
{
    ///// components /////
    public $Session = null;
    public $Db = null;
    public $getData = null;
    public $languagesView = null;

    // htmlでの一覧表示に使用するデータの配列
    var $item_num = 0;      // 表示アイテム数(全件)
    var $page_no = null;        // 表示しているページ番号
    var $page_num = 0;      // 全体のページ数
    var $array_title = array(); // タイトル
    var $array_title_english = array(); // タイトル(英)
    var $array_list = array();  // 1アイテムタイプ分の属性文字列
    var $array_attr = array();
    var $array_attr_list = array();
    var $array_item_attr_list = array();    // 表示する属性文字列
    var $view_start_no = -1;    // 表示開始件数
    var $view_end_no = 0;   // 表示終了件数
    var $array_item = array();      // Export用

    // Add session -> action K.Matsuo 2013/12/09 --start--
    // detail search tab
    public $active_search_flag = null;
    // search keyword
    public $searchkeyword = null;
    // detail search usable item
    public $detail_search_usable_item = null;
    // all search type
    public $all_search_type = null;
    // search item type
    public $detail_search_item_type = null;
    // search item type
    public $detail_search_select_item = null;
    // default detail search items
    public $default_detail_search = null;
    // default detail search items
    public $search_requestparameter_list = null;
    // Add session -> action K.Matsuo 2013/12/09 --end--

    // ファイル用追加 2008/07/23 Y.Nakao
    var $array_list_file = null;    // 1アイテムタイプ分の属性文字列
    var $array_attr_list_file = null;
    var $array_item_attr_list_file = null;  // 表示する属性文字列

    // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
    /**
     * has all thumbnail data
     *
     * @var array : arrayListThumbnail[loop_title][thumbnail_no][item_id]
     *                                                          [item_no]
     *                                                          [attribute_id]
     *                                                          [file_no]
     *                                                          [width]
     *                                                          [height]
     */
    public $arrayListThumbnail = array();

    /**
     * has thumbnail data of each items
     *
     * @var array : arrayThumbnail_[thumbnail_no][item_id]
     *                                          [item_no]
     *                                          [attribute_id]
     *                                          [file_no]
     *                                          [width]
     *                                          [height]
     */
    private $arrayThumbnail_ = array();
    // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-

    // アイテムタイプアイコン表示用 2008/07/24 Y.Nakao
    var $array_item_type_id = null;

    var $trade_id = "";

    // Fix sort order 2013/05/17 Y.Nakao --start--
    public $sortOrderList = array();
    // Fix sort order 2013/05/17 Y.Nakao --end--

    // Add shiboleth login 2009/03/17 Y.Nakao --start--
    var $shib_login_flg = "";
    var $shib_login_url = "";
    // Add shiboleth login 2009/03/17 Y.Nakao --end--

    var $index_id = null;
    var $keyword = null;
    var $sort_order = null;
    var $list_view_num = null;
    var $search_type = null;
    // Add detail search 2013/11/20 T.Ichikawa --end--

    // Add select language 2009/07/03 A.Suzuki --start--
    var $select_lang = null;
    // Add select language 2009/07/03 A.Suzuki --end--

    // Add alternative language setting 2009/08/12 A.Suzuki --start--
    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --start--
    var $alter_flg = RepositoryConst::PARAM_HIDE_ALTERNATIVE_LANG;   // 他言語表示フラグ
    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --end--
    // Add alternative language setting 2009/08/12 A.Suzuki --end--

    // Add hidden link to sitemap file address 2009/12/21 A.Suzuki --start--
    var $sitemap_file_path = "./weko/sitemaps/sitemapindex.xml";
    var $sitemap_flg = false;
    // Add hidden link to sitemap file address 2009/12/21 A.Suzuki --end--

    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
    var $uri_export = "";
    // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--

    // Add variable declaration 2013/09/13 K.Matsushita --start--
    var $block_id = null;
    // Add variable declaration 2013/09/13 K.Matsushita --end--

    // Add contents page Y.Nakao 2010/08/06 --start--
    var $heading = array();
    var $contents = array();
    // Add contents page Y.Nakao 2010/08/06 --end--

    // Add index list 2010/04/6 S.Abe --start--
    // index flag
    var $select_index_list_display = "";
    // index list which show index
    var $select_index_list = array();
    // Add index list 2010/04/6 S.Abe --end--

    // Add download_divergence_flg H.Gotoo 2010/12/21 --start--
    var $version_flg = "";
    var $flash_pub_flg = false;
    // Add download_divergence_flg H.Gotoo 2010/12/21 --end--

    // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -start-
    public $array_icon_width = array();
    public $array_icon_height = array();
    // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -end-


    // search components
    public $RepositoryValidator = null;
    public $RepositorySearch = null;

    private $indexDispType = 0;

    public $detail_info = "";

    public $setSearchParameter = "";

    // add file download after login T.Ichikawa 2015/02/03 --start--
    var $fileIdx = "";                  // ログイン後のファイルダウンロード情報
    // add file download after login T.Ichikawa 2015/02/03 --end--

    /**
     *
     *
     * @access  public
     */
    function executeApp()
    {
        try
        {
            // proc start time
            $sTime = microtime(true);

            // init action
            $this->initAction();

            // remove session
            $this->initParameter();

            //検索特化対応
            if(!_REPOSITORY_NOT_SHOW_TOP_PAGE){
	            // set request parameter
	            $this->setSearchRequest();

	            // set default search parameter
	            //index_id,keywordが未設定の場合、検索条件を再設定する
	            if(strlen($this->keyword) == 0 && strlen($this->index_id) == 0 && !$this->setSearchParameter){
	                // clear else search parameter
	                $this->RepositorySearch->item_ids = "";
	                $this->RepositorySearch->weko_id = "";

	                // Top page access log
                    // Mod entryLog T.Koyasu 2015/03/06 --start--
                    $this->infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
                    $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
                    $logManager->entryLogForTopView();
                    // Mod entryLog T.Koyasu 2015/03/06 --start--
	                
	                //インデックス指定及び最も新しいインデックスのとき、index_idを取得し、設定する。
	                $this->RepositorySearch->setDefaultSearchParameter();
	                $this->setSearchRequest();
	            }

	            // when defferent output view, redirect url.
	            $redirectUrl = $this->redirectView();
	            if(strlen($redirectUrl) > 0)
	            {
	                header("HTTP/1.1 301 Moved Permanently");
	                header("Location: ".$redirectUrl);
	                exit();
	            }

	            /*
	             * ランキング表示
	             */
	            $status = $this->decideScreenChanges();
	            if($status != 'true')
	            {
	                return $status;
	            }

	            // show snippet

	            //A.Jin Add 2013/7/2 --start--
	            /*
	             * インデックス検索かキーワード検索か判定する
	             */
	            $search_type = $this->RepositorySearch->getSearchType();

	            //キーワード検索
	            if($search_type == 'keyword'){
	                //キーワード検索の場合は、ソート順指定リストから「カスタム」を削除する。
	                $this->removeCustomSort();
	            }
	            //インデックス検索
	            if($search_type == 'index'){
	                //一覧表示か目次表示かを判定
	                $this->indexDispType = $this->checkDisplayTypeForIndex($this->RepositorySearch->index_id);
	                //目次形式の場合
	                if($this->indexDispType == 1)
	                {
	                    // get all contents
	                    $this->RepositorySearch->listResords = "all";
	                    //目次形式の場合は「デフォルトソート順(インデックス)」固定
	                   $this->RepositorySearch->sort_order = $this->RepositorySearch->validateSortOrder(array(), $this->RepositorySearch->index_id, "");
	                }
	                // Improve Search Log 2015/03/27 K.Sugimoto --start--
	                if(isset($this->index_id) && $this->index_id > 0)
	                {
		                $this->RepositorySearch->search_term["idx"] = $this->index_id;
	                }
	                // Improve Search Log 2015/03/27 K.Sugimoto --end--
	            }

	            //A.Jin Add 2013/7/2 --end--


	            // search
	            $searchResult = $this->RepositorySearch->search();

	            // set request parameter
	            $this->setRequestParameter();

	            // 検索結果をまとめる処理を移植する
	            $this->setListViewData($searchResult);

	            // index pankuzu
	            $this->setIndexList();

	            // setting session and more parameter
	            $this->setParameterValue();
            }

            $this->RepositorySearch->outputProcTime("snippet検索結果表示",$sTime, microtime(true));

            // exit action
            $this->exitAction();

            if($this->smartphoneFlg === true)
            {
                return "success_sp";
            }
            else
            {
                return "success";
            }

        }
        catch ( RepositoryException $Exception)
        {
            // exit action
            $result = $this->exitAction();

            // Add branch for smartphone 2012/04/04 A.Suzuki --start--
            if($this->smartphoneFlg === true)
            {
                return "error_sp";
            }
            else
            {
                return "error";
            }
            // Add branch for smartphone 2012/04/04 A.Suzuki --end--
        }
    }

    /**
     * initialize session parameter
     *
     */
    private function initParameter()
    {
        $errorMsg = "";

        // remove search condition
        $this->Session->removeParameter("searchkeyword");
        $this->Session->removeParameter("showListVar");
        $this->Session->removeParameter("searchIndexId");
        $this->Session->removeParameter("page_item_num");

        ///// ***** set RepositorySearch class ***** /////
        $this->RepositorySearch = new RepositorySearch();
        $this->RepositorySearch->Db = $this->Db;
        $this->RepositorySearch->Session = $this->Session;

        ///// ***** set validator class ***** /////
        require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
        $this->RepositoryValidator = new Repository_Validator_DownloadCheck();
        $this->RepositoryValidator->setComponents($this->Session, $this->Db);

        ///// ***** remove session parameter ***** /////
        $this->Session->removeParameter("error_cord");
        $this->Session->removeParameter("error_msg");

        // file download key
        // add file download after login T.Ichikawa 2015/02/03 --start-- 
        if(strlen($this->fileIdx) == 0 && $this->Session->getParameter("_user_id") != "0" && strlen($this->Session->getParameter("_login_id")) != 0)
        {
            // file download login.
            $this->fileIdx = $this->Session->getParameter('repository'.$this->block_id.'FileDownloadKey');
        }
        // add file download after login T.Ichikawa 2015/02/03 --end-- 
        $this->Session->removeParameter('repository'.$this->block_id.'FileDownloadKey');
        // TODO ### Add for Test Y.Nakao --start--

        // update user infomation page URL
        $this->Session->removeParameter("user_info_url");

        // remove pankuzu data
        $this->Session->removeParameter("index_data");
        $this->Session->removeParameter("parent_index_data");
        $this->Session->removeParameter("child_index");

        // remove export info
        $this->Session->removeParameter("item_info");

        $this->Session->removeParameter("search_tab_state");

        // 右画面情報
        $this->Session->removeParameter("serach_screen");
        $this->Session->setParameter("serach_screen", null);

        // money units
        $money_units = null;
        $money_units = $this->getMoneyUnit();
        if($money_units != null)
        {
            // Mod change yen mark to html special char T.Koyasu 2014/07/31 --start--
            if(strpos($money_units['money_unit'], "\\") === 0){
                $money_units['money_unit'] = "&yen;";
            }
            // Mod change yen mark to html special char T.Koyasu 2014/07/31 --end--

            $this->Session->setParameter('money_unit', $money_units['money_unit']);
            $this->Session->setParameter('money_unit_conf', $money_units['money_unit_conf']);
        }
        else
        {
            $this->Session->removeParameter('money_unit');
            $this->Session->removeParameter('money_unit_conf');
        }

        ///// ***** set session parameter ***** /////
        // set language select list for change language pulldown menu
        $lang_list = $this->languagesView->getLanguagesList();
        $this->Session->setParameter("lang_list", $lang_list);

        // set theme
        $blocks =& $this->getData->getParameter("blocks");
        $block_obj = $blocks[$this->block_id];
        if(!isset($block_obj['theme_name']) || strlen($block_obj['theme_name']) == 0)
        {
            //Auto select from Page theme
            $themeList = $this->Session->getParameter("_theme_list");
            $pages =& $this->getData->getParameter("pages");
            $this->Session->setParameter("repository_theme",$themeList[$pages[$block_obj['page_id']]['display_position']]);
        } else {
            $this->Session->setParameter("repository_theme", $block_obj['theme_name']);
        }

        // set user info page for download error page.
        $user_id = $this->Session->getParameter("_user_id");
        if($user_id != "0")
        {
            $login_id = $this->Session->getParameter("_login_id");
            $charge_pass = $this->RepositoryValidator->getChargePass();         // Modify Price method move validator K.Matsuo 2011/10/18
            $user_info_url = "https://".$charge_pass["user_fqdn"]."/user/menu/".
                             $charge_pass["sys_id"];//"/".$login_id;
            $this->Session->setParameter("user_info_url", $user_info_url);
        }

        // set now item type num insert num
        $this->Session->setParameter("def_item_type_num", $this->getItemTypeNum());

        // set select language
        $this->getAdminParam('select_language', $select_language, $errorMsg);
        $this->Session->setParameter("select_language", $select_language);

        ///// ***** set base parameter ***** /////
        // set $this->version_flg for netcommons version
        $this->version_flg = $this->getVersionFlg();

        // set shibboleth flg
        $this->shib_login_flg = SHIB_ENABLED;

        // hidden link to sitemap file address
        if(file_exists($this->sitemap_file_path))
        {
            $this->sitemap_flg = true;
        } else {
            $this->sitemap_flg = false;
        }

        ///// ***** set repository_parameter value ***** /////
        $this->getAdminParam('help_icon_display', $this->help_icon_display, $errorMsg);
        $this->getAdminParam('oaiore_icon_display', $this->oaiore_icon_display, $errorMsg);

        // set display index list
        $this->getAdminParam('select_index_list_display', $this->select_index_list_display, $errorMsg);
        if($this->select_index_list_display == 1)
        {
            // Add Advanced Search 2013/11/26 R.Matsuura --start--
            $indexManager = new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
            // Add Advanced Search 2013/11/26 R.Matsuura --end--
            // get index list
            $this->select_index_list = $indexManager->getDisplayIndexList($this->repository_admin_base, $this->repository_admin_room);
        }

        // TODO
        // set display sort_order list
        $this->sortOrderList = array();
        $this->getAdminParam('sort_disp', $sortDisp, $errorMsg);
        $this->sortOrderList = explode("|", $sortDisp);
    }



    /**
     * キーワード検索の場合は、ソート順指定リストから「カスタム」を削除する。
     *
     */
    private function removeCustomSort(){
            $pos = array_search(RepositorySearch::ORDER_CUSTOM_SORT_ASC, $this->sortOrderList);
            if(is_numeric($pos))
            {
                array_splice($this->sortOrderList, $pos, 1);
            }
            $pos = array_search(RepositorySearch::ORDER_CUSTOM_SORT_DESC, $this->sortOrderList);
            if(is_numeric($pos))
            {
                array_splice($this->sortOrderList, $pos, 1);
            }

    }

    /**
     * setting version_flg for netcommons version
     *
     * @return string '0'=>under ver.2.3.0.1
     *                '1'=>over ver.2.3.0.1
     */
    private function getVersionFlg()
    {
        $version = $this->getNCVersion();
        if(str_replace(".", "", $version) < 2301){
          // under ver.2.3.0.1
          return "0";
        }else{
          // over ver.2.3.0.1
          return "1";
        }
    }

    /**
     * check request parameter
     *
     * @return string redirect url
     */
    private function redirectView()
    {
        $redirectUrl = "";
        if( $this->RepositorySearch->format == RepositorySearch::FORMAT_ATOM ||
            $this->RepositorySearch->format == RepositorySearch::FORMAT_RSS)
        {
            $redirectUrl = BASE_URL."/?action=repository_opensearch&".$this->RepositorySearch->getRequestQuery();
        }
        return $redirectUrl;
    }

    /**
     * [移先の画面を決定する
     *
     * @access  private
     */
    private function decideScreenChanges()
    {
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        $user_id = $this->Session->getParameter("_user_id");

        /// add login from workflow 2009/10/07 A.Suzuki --start--
        if($this->Session->getParameter("login_redirect") != "")
        {
            if($user_id == '0'){
                // ワークフロータブから来た かつ ログインしていない
                if($this->Session->getParameter("login_redirect_flag") == ""){
                    // 初回はここを通るはず...
                    $this->Session->setParameter("login_redirect_flag", "true");
                } else {
                    // ログイン要求したがキャンセルされたため、セッション情報削除
                    $this->Session->removeParameter("login_redirect");
                    $this->Session->removeParameter("login_redirect_flag");
                }
            } else {
                $login_redirect = $this->Session->getParameter("login_redirect");
                $this->Session->removeParameter("login_redirect_flag");
                $this->Session->removeParameter("login_redirect");
                if($login_redirect == "workflow"){
                    // ワークフロータブへ
                    return "goWorkflow";
                } else if($login_redirect == "suppleworkflow"){
                    // サプリメンタルコンテンツタブへ
                    return "goSuppleWorkflow";
                } else {
                    // その他 -> snippetの処理続行
                    unset($login_redirect);
                }
            }
        }
        else
        {
            // セッション解放
            $this->Session->removeParameter("login_redirect");
            $this->Session->removeParameter("login_redirect_flag");
        }
        // add login from workflow 2009/10/07 A.Suzuki --end--

        // Add supple login check 2009/09/02 A.Suzuki --start--
        // EJWEKOからサプリコンテンツ新規作成で来た場合
        if($this->Session->getParameter("add_supple_flag") == "true")
        {
            if($this->Session->getParameter("supple_login") == "true" && $user_id == "0")
            {
                // EJWEKOから来た場合 かつ 未ログインの場合ここを通る
                $this->Session->setParameter("supple_login", "login");
            } else if($this->Session->getParameter("supple_login") == "login" && $user_id != "0") {
                // ログインした場合ここを通る
                if($this->Session->getParameter("ej_workflow_flag") == "true")
                {
                    // サプリコンテンツ編集
                    // アイテム編集権限チェック
                    $query = "SELECT * ".
                             "FROM ". DATABASE_PREFIX ."repository_item ".
                             "WHERE item_id = ? ".
                             "AND item_no = ? ".
                             "AND is_delete = ?; ";
                    $params = null;
                    // $queryの?を置き換える配列
                    $params[] = $this->Session->getParameter("supple_edit_item_id");    // item_id
                    $params[] = 1;                    // item_no
                    $params[] = 0;                    // is_delete
                    //　SELECT実行
                    $result_Item_Table = $this->Db->execute($query, $params);
                    if($result_Item_Table === false){
                        // Add branch for smartphone 2012/04/04 A.Suzuki --start--
                        if($this->smartphoneFlg === true)
                        {
                            return "error_sp";
                        }
                        else
                        {
                            return "error";
                        }
                        // Add branch for smartphone 2012/04/04 A.Suzuki --end--
                    }
                    $item_user = $result_Item_Table[0]['ins_user_id'];
                    // 登録ユーザ or モデレータ以上
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if($user_auth_id >= _AUTH_MODERATE || $item_user == $user_id){
                    if(($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) || $item_user == $user_id){
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        $result = $this->getEditItemDataForSupple($this->Session->getParameter("supple_edit_item_id"), 1);
                        if($result == 'texts'){
                            // ファイルなし -> メタデータ編集画面へ
                            $this->Session->removeParameter("supple_edit_item_id");
                            return "goEditTexts";
                        } else if($result == 'files') {
                            $this->Session->removeParameter("supple_edit_item_id");
                            // ファイルあり -> ファイル編集画面へ
                            return "goEditFiles";
                        } else {
                            // エラー
                            // EJWEKO関連のセッション削除
                            $this->Session->removeParameter("ej_item_id");
                            $this->Session->removeParameter("ej_item_no");
                            $this->Session->removeParameter("ej_weko_url");
                            $this->Session->removeParameter("ej_page_id");
                            $this->Session->removeParameter("ej_block_id");
                            $this->Session->removeParameter("add_supple_flag");
                            $this->Session->removeParameter("ej_workflow_flag");
                            $this->Session->removeParameter("ej_workflow_active_tab");
                            $this->Session->removeParameter("ej_attribute_id");
                            $this->Session->removeParameter("ej_supple_no");
                            $this->Session->removeParameter("supple_login");
                            $this->Session->removeParameter("supple_edit_item_id");
                        }
                    }
                    // その他ユーザ
                    else {
                        // アイテム編集不可
                        $this->Session->setParameter("supple_login", "no_regist");
                    }
                } else {
                    // サプリコンテンツ新規登録
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if($user_auth_id >= _AUTH_GENERAL){
                    if($auth_id >= REPOSITORY_ITEM_REGIST_AUTH){
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        // 一般ユーザ以上の場合、アイテムタイプ選択画面に遷移
                        $this->Session->removeParameter("supple_login");
                        return "goSelectType";
                    } else {
                        // アイテム作成不可
                        $this->Session->setParameter("supple_login", "no_regist");
                    }
                }
            } else if($this->Session->getParameter("supple_login") == "guest"){
                // アイテム作成不可
                $this->Session->setParameter("supple_login", "no_regist");

            // Add branch for smartphone 2012/04/04 A.Suzuki --start--
            } else if($this->Session->getParameter("supple_login") == "smartphone"){
                // Cannot regist or edit at smartphone.
                $this->Session->setParameter("supple_login", "no_regist_sp");
            // Add branch for smartphone 2012/04/04 A.Suzuki --start--

            } else {
                // ログインをキャンセルした場合
                // EJWEKO関連のセッション削除
                $this->Session->removeParameter("ej_item_id");
                $this->Session->removeParameter("ej_item_no");
                $this->Session->removeParameter("ej_weko_url");
                $this->Session->removeParameter("ej_page_id");
                $this->Session->removeParameter("ej_block_id");
                $this->Session->removeParameter("add_supple_flag");
                $this->Session->removeParameter("ej_workflow_flag");
                $this->Session->removeParameter("ej_workflow_active_tab");
                $this->Session->removeParameter("ej_attribute_id");
                $this->Session->removeParameter("ej_supple_no");
                $this->Session->removeParameter("supple_login");
                $this->Session->removeParameter("supple_edit_item_id");
            }
        }
        // Add supple login check 2009/09/02 A.Suzuki --end--

        // get admin parameter
        $default_disp_type = "";
        $this->getAdminParam('default_disp_type', $default_disp_type, $errorMsg);

        // fix download any files from repositoy_uri 2010/01/08 Y.Nakao --start--
        $this->uri_export = $this->Session->getParameter("uri_export");
        $this->Session->removeParameter("uri_export");
        if(!is_array($this->uri_export) || count($this->uri_export) != 4)
        {
            $this->uri_export = null;
        }
        else if($default_disp_type == 1)
        {
            $this->Session->setParameter("serach_screen", 2);
            $this->Session->setParameter("ranking_flg", 1);
            $this->Session->removeParameter("searchIndexId");
            $this->Session->setParameter("uri_export", $this->uri_export);
            // Fix download request url 2015/02/03 T.Ichikawa --start--
            if(strlen($this->fileIdx) > 0)
            {
                $this->Session->setParameter('repository'.$this->block_id.'FileDownloadKey', $this->fileIdx);
            }
            // Fix download request url 2015/02/03 T.Ichikawa --end--
            return "goRankingView";
        }
        else
        {
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

        // Add tree access control list 2012/03/23 T.Koyasu -start-
        if($this->Session->getParameter("_mobile_flag") == _ON)
        {
            $this->Session->removeParameter("detail_screen");
        }
        if(strlen($this->Session->getParameter("detail_screen")) > 0  && $user_id != "0"){
            $this->Session->setParameter("serach_screen", "false");
            $this->Session->removeParameter("detail_screen");
            return 'goDetailView';
        }
        else
        {
            $this->Session->removeParameter("detail_screen");
        }
        // Add tree access control list 2012/03/23 T.Koyasu -end-

        if($this->select_lang != null)
        {
            $this->Session->setParameter("_lang",$this->select_lang);
            $this->Session->setParameter("redirect_flg", "select_lang");
            return "redirect";
        }
        // タブ切り替えなどで来た場合か判定
        // こちらの判定の際はRepositorySearchクラスのパラメータを参照しないこと
        // ⇒$this->RepositorySearch->index_idはintValで空文字の場合0が入ってしまうため
        if(strlen($this->keyword) == 0 && strlen($this->index_id) == 0 && !$this->setSearchParameter)
        {
            // Add default display type select 2008/12/8 A.Suzuki --start--
            // default display : "ranking"
            if($default_disp_type == 1){
                $this->Session->setParameter("serach_screen", 2);
                $this->Session->setParameter("ranking_flg", 1);
                $this->Session->removeParameter("searchIndexId");
                return "goRankingView";
            }
        }
         return 'true';
    }

    /**
     * check item type num
     *
     * @return int
     */
    private function getItemTypeNum()
    {
        $query = "SELECT item_type_id ".
                " FROM ". DATABASE_PREFIX .RepositoryConst::DBTABLE_REPOSITORY_ITEM_TYPE.
                " WHERE is_delete = 0; ";
        $ret = $this->Db->execute($query);
        if(!($ret === false)){
            return count($ret);
        }
        return 0;
    }

    /**
     * make list data
     *
     * @param array $searchResult 検索結果
     * @return
     */
    public function setListViewData($searchResult)
    {
        ////////////////////////////////// 一覧表示作成 /////////////////////////////////////
        // 一覧表示は多言語を表示しない
        // 一覧表示に使用するデータの配列を初期化
        $this->item_num = $this->RepositorySearch->getTotal();    // 検索ヒット数
        $this->array_title = array();               // タイトル
        $this->array_title_english = array();       // タイトル(英)
        $this->array_attr = array();                // array(リンクフラグ,属性名:,属性値,属性値・・・/);
        $this->array_list = array();                // array($this->array_attr);<br>
        $this->array_attr_list = array();           // array_attr_list($this->attr_list);//全件数分
        $this->array_item_attr_list = array();      // 表示する属性文字列
        $this->array_item = array();                // Export用格納配列
        $page_item_num = 0;                         // 1ページに表示されるアイテムの個数

        // ファイル用追加 2008/07/23 Y.Nakao
        $this->array_attr_file = array();
        $this->array_list_file = array();           // array($this->array_attr);<br>
        $this->array_attr_list_file = array();      // array_attr_list($this->attr_list);//全件数分
        $this->array_item_attr_list_file = array(); // 表示する属性文字列

        // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
        // initializes by array
        $this->arrayListThumbnail = array();
        $this->arrayThumbnail_ = array();
        // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-

        // アイテムタイプアイコン表示用追加　2008/07/24 Y.Nakao
        $this->array_item_type_id = array(); // アイテムタイプIDを保持する

        // 選択言語
        $lang = $this->Session->getParameter("_lang");

        // check admin
        $admin_flg = "false";

        // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --start--
        $return = $this->getAdminParam('alternative_language', $param_value, $errorMsg);
        $tmp_alter_lang = explode(",", $param_value);
        $this->alter_flg = RepositoryConst::PARAM_HIDE_ALTERNATIVE_LANG;
        for($ii=0; $ii<count($tmp_alter_lang); $ii++){
            $lang_data = explode(":", $tmp_alter_lang[$ii], 2);
            // 現在の選択言語の他言語設定値を取得(0:表示しない, 1:表示する)
            if($lang_data[0] == $lang){
                $this->alter_flg = $lang_data[1];
            }
        }
        // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --end--

        for($nCnt_ID=0; $nCnt_ID<count($searchResult); $nCnt_ID++)
        {
            $Result_List = array();
            $Error_Msg = "";
            $itemId = $searchResult[$nCnt_ID][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_ID];
            $itemNo = $searchResult[$nCnt_ID][RepositoryConst::DBCOL_REPOSITORY_ITEM_ITEM_NO];
            $search_result = $this->getItemData($itemId, $itemNo, $Result_List, $Error_Msg, false, true);
            if($search_result === false)
            {
                continue;
            }

            $resultItem           =& $Result_List[RepositoryConst::ITEM_DATA_KEY_ITEM][0];
            $resultItemType       =& $Result_List[RepositoryConst::ITEM_DATA_KEY_ITEM_TYPE][0];
            $resultItemAttrType   =& $Result_List[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE];
            $resultItemAttr       =& $Result_List[RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR];

            // タイトルを格納
            array_push($this->array_title,$resultItem['title']);
            array_push($this->array_title_english,$resultItem['title_english']);

            // アイテムタイプアイコン表示用追加 2008/07/24 Y.Nakao
            if(strlen($resultItemType[RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_ICON_NAME]) > 0)
            {
                $itemTypeId = $resultItem[RepositoryConst::DBCOL_REPOSITORY_ITEM_TYPE_ID];
                array_push($this->array_item_type_id, $itemTypeId);
                // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -start-
                $result = $this->getItemTypeTableData($itemTypeId, $tmpData, $errorMsg, true);
                if($result === false)
                {
                    continue;
                }
                $icon = $tmpData['item_type'][0]['icon'];
                // create image
                $img = imagecreatefromstring($icon);
                if($img !== false)
                {
                    // get width and height by image
                    array_push($this->array_icon_width, imagesx($img));
                    array_push($this->array_icon_height, imagesy($img));
                    // drop image
                    imagedestroy($img);
                }
                // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -end-
            } else {
                array_push($this->array_item_type_id,"");
                // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -start-
                array_push($this->array_icon_width, "16");
                array_push($this->array_icon_height, "16");
                // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -end-
            }

            // 表示件数+1
            $page_item_num++;

            // set item info
            array_push($this->array_item, array('item_id' => $itemId,'item_no' => $itemNo));

            //////////////////////// 表示する属性一覧をまとめる /////////////////////
            // 1アイテムタイプごとに表示属性文字列を作成する
            $this->array_attr_list = array();
            $this->array_list = array();

            // ファイル追加
            $this->array_attr_list_file = array();
            $this->array_list_file = array();

            // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
            // initializes by array
            $this->arrayThumbnail_ = array();
            // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-

            // Add matadata select language 2009/07/31 A.Suzuki --start--
            for($ii=0; $ii<count($resultItemAttrType); $ii++){
                // Bug fix attribute value display 2010/11/24 A.Suzuki --start--
                if($resultItemAttrType[$ii]['list_view_enable']!=1){
                    $resultItemAttrType[$ii]['display_flag'] = "false";
                    continue;
                }
                // Bug fix attribute value display 2010/11/24 A.Suzuki --end--
                // 一覧表示用のフラグを追加 (初期値は"true":表示する)
                if(isset($resultItemAttrType[$ii]['display_flag']) != true){
                    $resultItemAttrType[$ii]['display_flag'] = "true";
                }

                // ファイル、課金ファイルはスルーする
                if($resultItemAttrType[$ii]['input_type']!="file"
                    && $resultItemAttrType[$ii]['input_type']!="file_price")
                {
                    // 値チェック
                    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --start--
                    if($this->alter_flg === RepositoryConst::PARAM_SHOW_ALTERNATIVE_LANG){
                        // If alternative language is ON, show alternative language metadata in item list
                        $resultItemAttrType[$ii]['display_flag'] = "true";
                    } else if(count($resultItemAttr[$ii])>0
                       && isset($resultItemAttrType[$ii]['display_flag'])
                       && $resultItemAttrType[$ii]['display_flag'] != "false")
                    {
                        // メタデータの言語設定が現在の選択言語と異なる場合
                        if($resultItemAttrType[$ii]['display_lang_type'] != "" && $resultItemAttrType[$ii]['display_lang_type'] != $lang){
                            // そのデータは表示しない
                            $resultItemAttrType[$ii]['display_flag'] = "false";
                        } else if($resultItemAttrType[$ii]['display_lang_type'] == $lang || $resultItemAttrType[$ii]['display_lang_type'] == ""){
                            $resultItemAttrType[$ii]['display_flag'] = "true";
                        }
                    } else {
                        $resultItemAttrType[$ii]['display_flag'] = "false";
                    }
                    // Bug fix WEKO-2014-031 T.Koyasu 2014/06/25 --end--
                }
            }
            // Add matadata select language 2009/07/31 A.Suzuki --end--

            // Add contents page Y.Nakao 2010/08/06 --start--
            if($this->indexDispType == 1)
            {
                array_push($this->contents, array(  RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR_TYPE=>$resultItemAttrType,
                                                    RepositoryConst::ITEM_DATA_KEY_ITEM_ATTR=>$resultItemAttr));
            }
            // Add contents page Y.Nakao 2010/08/06 --start--

            // Modify Price method move validator K.Matsuo 2011/10/18 --start--
            // Fix contents page heading count --start--
            $headingFlg = false;
            // Fix contents page heading count --end--
            for($nCnt_attr_type=0;$nCnt_attr_type<count($resultItemAttrType);$nCnt_attr_type++){
                $this->array_attr = array();
                // ファイル追加
                $this->array_attr_file = array();
                $str = "";

                // Add contents page Y.Nakao 2010/08/06 --start--
                if($this->indexDispType == 1){
                    // for contents page
                    if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "heading"){
                        // Fix contents page heading count --start--
                        $headingFlg = true;
                        // Fix contents page heading count --end--
                        // contents page must display heading
                        // BugFix undefined offset 2014/09/04 T.Ichikawa --start--
                        if(empty($resultItemAttr[$nCnt_attr_type])) {
                            array_push($this->heading, array());
                        } else {
                            array_push($this->heading, explode("|", $resultItemAttr[$nCnt_attr_type][0]['attribute_value'], 4));
                        }
                        // BugFix undefined offset 2014/09/04 T.Ichikawa --end--
                    }
                }
                // Add contents page Y.Nakao 2010/08/06 --end--

                // 一覧表示 ON / OFF チェック
                if($resultItemAttrType[$nCnt_attr_type]['list_view_enable'] == 1 && $resultItemAttrType[$nCnt_attr_type]['display_flag'] == "true"){
                    // 一覧表示ONの場合、表示文字列作成
                    // 属性IDと入力形式から、属性値を取得
                    // 入力形式別に属性値を格納
                    if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "name"){
                        // 属性名格納
                        array_push($this->array_attr, "name"); // リンクフラグ
                        // n項目分氏名を格納
                        for($nCnt=0;$nCnt<count($resultItemAttr[$nCnt_attr_type]);$nCnt++){
                            $str = "";
                            if($resultItemAttrType[$nCnt_attr_type]['display_lang_type'] == "english")
                            {
                                $str = $resultItemAttr[$nCnt_attr_type][$nCnt]['name'];
                                if(strlen($resultItemAttr[$nCnt_attr_type][$nCnt]['family']) > 0)
                                {
                                    if(strlen($str) > 0)
                                    {
                                        $str .= " ";
                                    }
                                    $str .= $resultItemAttr[$nCnt_attr_type][$nCnt]['family'];
                                }
                            }
                            else
                            {
                                $str = $resultItemAttr[$nCnt_attr_type][$nCnt]['family'];
                                if(strlen($resultItemAttr[$nCnt_attr_type][$nCnt]['name']) > 0)
                                {
                                    if(strlen($str) > 0)
                                    {
                                        $str .= " ";
                                    }
                                    $str .= $resultItemAttr[$nCnt_attr_type][$nCnt]['name'];
                                }
                            }
                            // Add author link url 2015/01/28 T.Ichikawa --start--
                            $url = BASE_URL. "/?action=repository_opensearch&creator=". urlencode($str);
                            array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str, "url" => $url));
                            if($this->indexDispType == 1)
                            {
                                $this->contents[count($this->contents)-1]['item_attr'][$nCnt_attr_type][$nCnt]['url'] = $url;
                            }
                            // Add author link url 2015/01/28 T.Ichikawa --end--
                        }
                        // 改行指定があった場合、文末に " , "は表示しない
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }

                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "thumbnail"){
                        // 属性名格納
                        array_push($this->array_attr, "thumbnail"); // リンクフラグ
                        // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
                        // set thumbnail data to $this->arrayThumbnail_
                        $this->setThumbnailInfo($Result_List, $nCnt_attr_type);
                        // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-
                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "file"){
                        $this->SetFileInfo($Result_List, $nCnt_attr_type, 'file');
                        // 改行指定があった場合、文末に " , "は表示しない
                        // Fix search result new line 2011/12/13 Y.Nakao --start--
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1 && count($this->array_attr) > 1)
                        {
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }
                        // Fix search result new line 2011/12/13 Y.Nakao --end--
                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "file_price"){
                        $this->SetFileInfo($Result_List, $nCnt_attr_type, 'file_price');
                        // 改行指定があった場合、文末に " , "は表示しない
                        // Fix search result new line 2011/12/13 Y.Nakao --start--
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1 && count($this->array_attr) > 1)
                        {
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }
                        // Fix search result new line 2011/12/13 Y.Nakao --start--
                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "link"){

                        // 属性名格納
                        array_push($this->array_attr, "link"); // リンクフラグ
                        //$str = $resultItemAttrType[$nCnt_attr_type]['attribute_name'] . " : ";
                        //array_push($this->array_attr, $str); // 属性名 $str); // 属性名
                        // n項目分ファイル名を格納
                        for($nCnt=0;$nCnt<count($resultItemAttr[$nCnt_attr_type]);$nCnt++){
                            $str = explode("|", $resultItemAttr[$nCnt_attr_type][$nCnt]['attribute_value'], 2);
                            array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str));
                        }
                        // 改行指定があった場合、文末に " , "は表示しない
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }
                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "biblio_info"){
                        $this->SetBiblioInfo($Result_List, $nCnt_attr_type);
                        // 改行指定があった場合、文末に " , "は表示しない
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }

                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "textarea"){

                        // 属性名格納
                        array_push($this->array_attr, "textarea"); // リンクフラグ
                        // n項目分ファイル名を格納
                        for($nCnt=0;$nCnt<count($resultItemAttr[$nCnt_attr_type]);$nCnt++){
                            $str = $resultItemAttr[$nCnt_attr_type][$nCnt]['attribute_value'];
                            $str_array = explode("\n", $str);
                            array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str_array));
                        }
                        // 改行指定があった場合、文末に " , "は表示しない
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }
                    // Add contents page Y.Nakao 2010/08/06 --start--
                    } else if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "heading"){
                        // push attr value
                        $str = $resultItemAttr[$nCnt_attr_type][0]['attribute_value'];
                        $str = preg_replace("/\|+/", "|", $str);
                        $str = str_replace("|", ", ", $str);
                        $str = trim($str, ", ");
                        $str = rtrim($str, ", ");
                        if(strlen($str)==0){
                            continue;
                        }
                        // attr name
                        array_push($this->array_attr, "else");
                        array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str));
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }
                        // Add contents page Y.Nakao 2010/08/06 --end--
                    } else {

                        // 属性名格納
                        array_push($this->array_attr, "else"); // リンクフラグ
                        //$str = $resultItemAttrType[$nCnt_attr_type]['attribute_name'] . " : ";
                        //array_push($this->array_attr, $str); // 属性名 $str); // 属性名
                        // n項目分ファイル名を格納
                        for($nCnt=0;$nCnt<count($resultItemAttr[$nCnt_attr_type]);$nCnt++){
                            $str = $resultItemAttr[$nCnt_attr_type][$nCnt]['attribute_value'];
                            array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str));
                        }
                        // 改行指定があった場合、文末に " , "は表示しない
                        if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                            $this->array_attr[count($resultItemAttr[$nCnt_attr_type])]["last_flg"] = 1;
                        }

                    }
                    // 1属性分の配列データ
                    if(count($this->array_attr) > 0){
                        array_push($this->array_list,$this->array_attr);
                        $this->array_attr = array();
                    }
                    // ファイル用追加
                    if(count($this->array_attr_file) > 0){
                        array_push($this->array_list_file,$this->array_attr_file);
                        $this->array_attr_file = array();
                    }

                    // 改行指定を判定
                    if($resultItemAttrType[$nCnt_attr_type]['line_feed_enable'] == 1){
                        // 改行する
                        // ファイル追加　ファイルかどうか判定
                        if($resultItemAttrType[$nCnt_attr_type]['input_type'] == "file" ||
                            $resultItemAttrType[$nCnt_attr_type]['input_type'] == "file_price"){
                            array_push($this->array_attr_list_file,$this->array_list_file);
                            $this->array_list_file = array();
                            array_push($this->array_attr_list,$this->array_list);
                            $this->array_list = array();
                        } else {
                            array_push($this->array_attr_list,$this->array_list);
                            $this->array_list = array();
                        }
                    }
                } // 一覧表示ON/OFF判定
            } // アイテムタイプ属性値を取得するループ
            // Modify Price method move validator K.Matsuo 2011/10/18 --end--
            // 1アイテムの一覧表示用属性値
            if(count($this->array_attr_list)==0 || count($this->array_list)>0){
                array_push($this->array_attr_list,$this->array_list);
            }

            // ファイル用追加
            array_push($this->array_attr_list_file,$this->array_list_file);
            $this->array_list_file = array();

            // 不具合対応#16 ページを超えた一覧表示の内容  2008/06/18 Y.Nakao --END--
            if($this->array_attr_list != null){
                array_push($this->array_item_attr_list,$this->array_attr_list);
                $this->array_attr_list = array();
            }
            // ファイル追加
            if($this->array_attr_list_file != null){
                array_push($this->array_item_attr_list_file,$this->array_attr_list_file);
                $this->array_attr_list_file = array();
            }
            // 不具合対応#16 ページを超えた一覧表示の内容  2008/06/18 Y.Nakao --END--

            // Fix contents page heading count --start--
            if($this->indexDispType == 1 && !$headingFlg)
            {
                array_push($this->heading, array('0'=>''));
            }
            // Fix contents page heading count --end--

            // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
            // set thumbnail data of each items to $this->arrayListThumbnail
            array_push($this->arrayListThumbnail, $this->arrayThumbnail_);
            // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-

        }

        // 一覧表示で表示される属性一覧の最後に","を表示させない対応 改善 2008/08/04 Y.Nakao --start--
        // 表示用にまとめたデータから、その属性値が表示される属性値の最後かどうか調べる
        for($ii=0; $ii<count($this->array_title); $ii++){  // 表示件数(array_item)
            // 最後の属性値の位置を覚えておくための配列
            $last_attr = null;
            // 対象アイテム
            $title = $this->array_item_attr_list[$ii];
            for($jj=0; $jj<count($title); $jj++){ // 改行数
                // 対象行
                $line = $title[$jj];
                for($kk=0; $kk<count($line); $kk++){ // 対象行中の属性分
                    // 対象属性
                    $attr = $line[$kk];
                    for($tt=1; $tt<count($attr); $tt++){ // $tt=0は属性タイプが格納されているので飛ばす
                        // 属性中の値(複数の場合このループを2回以上通る)

                        if(isset($attr[$tt])){
                            $attr_value = $attr[$tt];
                            // 最後かどうか判定
                            if(isset($attr_value["attr_value"]) && $attr_value["attr_value"] != ""){
                                // 表示する属性値がある場合、その場所を覚える(一時記憶)
                                $last_attr = array( "line"=>$jj, "attr"=>$kk, "attr_value"=>$tt );
                            }
                        }
                    }
                }
            }
            // 表示する属性があったかどうか
            if($last_attr!=null){
                // 最後の属性であることを示すフラグを1にする
                $this->array_item_attr_list[$ii][$last_attr["line"]][$last_attr["attr"]][$last_attr["attr_value"]]["last_flg"] = 1;
            }
        }
        // 一覧表示で表示される属性一覧の最後に","を表示させない対応 改善 2008/08/04 Y.Nakao --start--

        // 1ページに表示されるアイテム数
        $this->Session->setParameter("page_item_num",$page_item_num);

    }


    // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
    /**
     * get thumbnail data and set thumbnail data to $arrayThumbnail_
     *
     * @param array $ResultList : has all item data
     * @param int $nCntAttrType : 2nd key of ResultList
     */
    private function setThumbnailInfo($ResultList, $nCntAttrType)
    {
        // get itemId, itemNo and attributeId by ResultList
        $itemId = $ResultList['item'][0]['item_id'];
        $itemNo = $ResultList['item'][0]['item_no'];
        $attrId = $ResultList['item_attr_type'][$nCntAttrType]['attribute_id'];

        // get thumbnail image and data
        $result = $this->getThumbnailTableData($itemId, $itemNo, $attrId, $nCntAttrType, $tmpData, $errorMsg, true);
        if($result === false && count($tmpData['item_attr'][$nCntAttrType]) === 0)
        {
            return;
        }
        $thumbnailData = array();
        $thumbnailData = $tmpData['item_attr'][$nCntAttrType];

        // thumbnail data
        for($nCnt=0;$nCnt<count($thumbnailData);$nCnt++)
        {
            $fileNo = $thumbnailData[$nCnt]['file_no'];

            // create image
            $img = imagecreatefromstring($thumbnailData[$nCnt]['file']);
            if($img !== false)
            {
                // get width
                $width = imagesx($img);
                // get height
                $height = imagesy($img);
                // drop image
                imagedestroy($img);
            }

            // set value
            array_push($this->arrayThumbnail_, array('item_id'=>$itemId,
                                                    'item_no'=>$itemNo,
                                                    'attribute_id'=>$attrId,
                                                    'file_no'=>$fileNo,
                                                    'width'=>$width,
                                                    'height'=>$height));
        }
    }
    // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-

    /**
     * Set item array for input_type=file
     *
     * @param unknown_type $Result_List
     * @param unknown_type $nCnt_attr_type
     * @param unknown_type $input_type
     */
    private function SetFileInfo($Result_List, $nCnt_attr_type, $input_type)
    {
        // 属性名格納
        array_push($this->array_attr_file, $input_type); // リンクフラグ
        array_push($this->array_attr, $input_type); // リンクフラグ

        //$str = $Result_List['item_attr_type'][$nCnt_attr_type]['attribute_name'] . " : ";
        //array_push($this->array_attr, $str); // 属性名 $str); // 属性名
        // n項目分ファイル名を格納
        // 拡張子追加 2008/07/14 Y.Nakao --start--
        for($nCnt=0;$nCnt<count($Result_List['item_attr'][$nCnt_attr_type]);$nCnt++){

            // Fix change file download action 2013/5/9 Y.Nakao --start--
            // ファイルダウンロード動作仕様変更に対応
            $accessFlag = $this->RepositoryValidator->checkFileDownloadViewFlag($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['pub_date'], $this->TransStartDate);
            // Add Charge status is not check by snippet T.Koyasu 2014/09/24 --start--
            $status = $this->RepositoryValidator->checkFileAccessStatus($Result_List['item_attr'][$nCnt_attr_type][$nCnt], false);
            // Add Charge status is not check by snippet T.Koyasu 2014/09/24 --end--
            if( $status != "admin" && $status != "login" && $accessFlag == Repository_Validator_DownloadCheck::ACCESS_CLOSE)
            {
                // When file is 'close', not display link.
                // 管理者、登録者でない かつ ログイン要求ファイルでない かつ ファイルが「公開しない」設定になっている ⇒ リンクを表示しない
                continue;
            }
            $open_access_file = BASE_URL."/?action=repository_uri".
                                "&item_id=".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['item_id'].
                                "&file_id=".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['attribute_id'].
                                "&file_no=".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['file_no'];
            // Fix change file download action 2013/5/9 Y.Nakao --end--

            // 拡張子
            $extension = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['extension'];
            // 簡略表記 (2008.08.04 S.Kawasaki)
            $file_label = $this->mimetypeSimpleName($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['mime_type']);
            if($file_label == "" && $extension != ""){
                $file_label = $extension;
            }
            $adjusted_label = array();
            $this->AdjustLabelWidth($file_label, $adjusted_label );

            // Add flash_pub_flg 2010/12/15 H.Goto --end--
            if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['display_type'] != RepositoryConst::FILE_DISPLAY_TYPE_SIMPLE){
                // 詳細・FLASH表示処理
                $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['file_name'];
                $file_info = array( "item_id" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['item_id'],
                                    "item_no" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['item_no'],
                                    "attr_id" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['attribute_id'],
                                    "file_no" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['file_no'],
                                    "mimetype_name" => $adjusted_label,
                                    "open_access_file" => $open_access_file,
                                   );
                array_push($this->array_attr_file, $file_info);
            } else {
                // 簡易表示用処理
                if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['display_name'] != ""){
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['display_name'];
                } else {
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['file_name'];
                }
                $file_info = array( "last_flg"=>0,
                                    "attr_value" => $str,
                                    "item_id" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['item_id'],
                                    "item_no" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['item_no'],
                                    "attr_id" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['attribute_id'],
                                    "file_no" => $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['file_no'],
                                    "open_access_file" => $open_access_file,
                                );
                array_push($this->array_attr, $file_info);
            }
            // 目次表示の時
            if($this->indexDispType == 1)
            {
                $this->contents[count($this->contents)-1]['item_attr'][$nCnt_attr_type][$nCnt]['mimetype_name'] = $adjusted_label;
                $this->contents[count($this->contents)-1]['item_attr'][$nCnt_attr_type][$nCnt]['open_access_file'] = $open_access_file;
            }
        }
    }

    /**
     * setting parameter
     *
     */
    private function setParameterValue()
    {
        // set right display screentype.
        // 0 -> searchResult.html
        // 1 -> detail.html
        // 2 -> ranking.html
        // 3 -> contens.html(mokuji)
        if( strlen($this->keyword) == 0 && strlen($this->index_id) > 0 && !$this->setSearchParameter && $this->indexDispType == 1)
        {
            // this index disp for contents
            $this->Session->setParameter("serach_screen", 3);
        }
        else
        {
            // this index disp fot list
            $this->Session->setParameter("serach_screen", "0");
        }

        if($this->indexDispType == 1)
        {
            array_push($this->heading, array('0'=>''));
        }

        if((strlen($this->keyword) > 0 || strlen($this->index_id) > 0  && $this->setSearchParameter) && $this->item_num == 0)
        {
            // keyword or index search result is null
            $this->Session->setParameter("error_cord", 2);
        }

        // 通常の詳細表示を示す(編集,Export,印刷可能)
        $this->Session->setParameter("workflow_flg", "false");

        if(count($this->RepositorySearch->search_term) > 0){
            $this->Session->setParameter("showListVar", "true");
        }
        if(strlen($this->RepositorySearch->index_id) > 0)
        {
            $this->Session->setParameter("searchIndexId", $this->RepositorySearch->index_id);
        }
        $this->Session->setParameter("list_view_num", $this->list_view_num);
        $this->Session->setParameter("sort_order", $this->sort_order);
    }

    private function SetBiblioInfo($Result_List, $nCnt_attr_type)
    {
        // 属性名格納
        array_push($this->array_attr, "biblio_info"); // リンクフラグ
        //$str = $Result_List['item_attr_type'][$nCnt_attr_type]['attribute_name'] . " : ";
        //array_push($this->array_attr, $str); // 属性名 $str); // 属性名
        // n項目分書誌情報を格納
        for($nCnt=0;$nCnt<count($Result_List['item_attr'][$nCnt_attr_type]);$nCnt++){
            // 書誌情報の表示は「雑誌名, 巻(号), 開始ページ-終了ページ(発行年)」とする
            // 雑誌名の日英は選択言語で切りかえる
            if($this->Session->getParameter("_lang") == "japanese"){
                if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'] != "" && $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'] != null){
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'];
                } else {
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'];
                }
            } else {
                if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'] != "" && $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'] != null){
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'];
                } else {
                    $str = $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'];
                }
            }

            // Add alternative language setting 2009/08/12 A.Suzuki --start--
            // 他言語フラグが"1"である場合は他言語の雑誌名も表示する
            if($this->alter_flg == "1"
                && $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'] != ""
                && $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'] != "")
            {
                if($this->Session->getParameter("_lang") == "japanese"){
                    $str .= "/".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name_english'];
                } else {
                    $str .= "/".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['biblio_name'];
                }
            }
            // Add alternative language setting 2009/08/12 A.Suzuki --end--

            //巻
            if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['volume'] != ""){
                //雑誌名が空かチェックし、空ではないならカンマを追加する
                if($str != ""){
                    $str .= ",";
                }
                $str .= $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['volume'];
            }

            //号
            if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['issue'] != ""){
                //雑誌名が入っているが、巻が空だった場合はカンマをつける
                if(($str != "") && ($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['volume'] == "")){
                    $str .= ",";
                }
                $str .= "(".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['issue']. ")";
            }

            //スタートページとエンドページ
            //片方だけ入っていた場合は片方のみ表示
            if(($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['start_page'] != "") && ($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['end_page'] != "")){
                //文字列空かチェックし、空ではないならカンマを追加する
                if($str != ""){
                    $str .= ",";
                }
                $str .= sprintf($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['start_page']."-".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['end_page']);
            }else if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['start_page'] != ""){
                //文字列が空かチェックし、空ではないならカンマを追加する
                if($str != ""){
                    $str .= ",";
                }
                $str .= $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['start_page'];
            }else if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['end_page'] != ""){
                //文字列が空かチェックし、空ではないならカンマを追加する
                if($str != ""){
                    $str .= ",";
                }
                $str .= $Result_List['item_attr'][$nCnt_attr_type][$nCnt]['end_page'];
            }

            //発行年
            if($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['date_of_issued'] != ""){
                //文字列が空ではないが、スタート・エンドページが共に空ならカンマを追加する
                if(($str != "") && ($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['start_page'] == "") && ($Result_List['item_attr'][$nCnt_attr_type][$nCnt]['end_page'] == "")){
                    $str .= ",";
                }
                $str .= " (".$Result_List['item_attr'][$nCnt_attr_type][$nCnt]['date_of_issued'].")";
            }
            array_push($this->array_attr, array("last_flg"=>0, "attr_value" => $str));
        }
    }
    // Add for Program arrangement  K.Matsuo 2011/10/25 --end--

    /**
     * [[ラベルの表記を最大文字数/最小文字数に合わせて調整する]]
     *
     * @access  private
     */
    private function AdjustLabelWidth($original, &$adjusted)
    {
        $len_ori = strlen($original);
        $len_adjusted = $len_ori;
        $str = '';
        // 調整後の文字列長を決定
        if( $len_ori < _DOWNLOAD_ICON_WIDTH_MIN  ) {
            // オリジナルが短すぎる場合は半角スペースを追加
            $len_adjusted = _DOWNLOAD_ICON_WIDTH_MIN;
            $str = $original;
            $len_gap = $len_adjusted - $len_ori;
            $len_gap_bef = (int)($len_gap/2);
            $len_gap_aft = (int)($len_gap/2) + (int)($len_gap%2);
            for($ii=0; $ii<$len_gap_bef; $ii++){
                $str = ' '.$str;
            }
            for($ii=0; $ii<$len_gap_aft; $ii++){
                $str = $str . ' ';
            }
        } else if( $len_ori > _DOWNLOAD_ICON_WIDTH_MAX  ) {
            // オリジナルが長すぎる場合はトリム
            $len_adjusted = _DOWNLOAD_ICON_WIDTH_MAX;
            $str = substr($original, 0, $len_adjusted);
        } else {
            // オリジナルをそのままコピー
            $str = $original;
        }

        // "___pdf____"として整形された文字列を配列に詰める
        // htmlの表示の都合上、
        // [0]=" ",[1]=" ",[2]=" ",[3]="p",[4]="d",[5]="f",[6]=" ",[7]=" ",[8]=" ",[9]=" "
        // ではなく、(上記だとhtmlで"___p_d_f____"と半角スペース表示が混じるため。これはhtmlの仕様)
        // [0]=" ",[1]=" ",[2]=" ",[3]="pdf",[4]=" ",[5]=" ",[6]=" "として詰め込む。
        for($ii=0; $ii<strlen($str); $ii++){
            if(substr($str, $ii, 1) == ' '){
                // 空白はそのまま詰め込む
                array_push($adjusted, substr($str, $ii, 1));
            } else {
                // 空白以外の場合、前後の空白を取り除き、表示する文字列を一塊で詰め込む
                array_push($adjusted, trim($str));
                // 詰め込んだ文字列分、ポインタを移動
                $ii += strlen(trim($str)) - 1;
            }
        }

        return;
    }

    /**
     * set request parameter for view display
     *
     */
    private function setSearchRequest()
    {
        if(isset($this->RepositorySearch->search_term["meta"]) && strlen($this->RepositorySearch->search_term["meta"]) > 0) {
            $this->keyword      = $this->RepositorySearch->search_term["meta"];
            $this->setSearchParameter = true;
        } else if(isset($this->RepositorySearch->search_term["all"]) && strlen($this->RepositorySearch->search_term["all"]) > 0) {
            $this->keyword      = $this->RepositorySearch->search_term["all"];
            $this->setSearchParameter = true;
        } else if(count($this->RepositorySearch->search_term) != 0){
            $this->keyword      = "";
            $this->setSearchParameter = true;
        } else {
            $this->keyword      = "";
            $this->setSearchParameter = false;
        }

        if($this->RepositorySearch->active_search_flag == null){
            $this->active_search_flag = 0;
        } else {
            $this->active_search_flag = $this->RepositorySearch->active_search_flag;
        }
        if($this->RepositorySearch->all_search_type == null){
            $this->search_type = "detail";
        } else {
            $this->search_type = $this->RepositorySearch->all_search_type;
        }
        $this->detail_search_usable_item = $this->RepositorySearch->detail_search_usable_item;
        $this->detail_search_item_type = $this->RepositorySearch->detail_search_item_type;
        $this->detail_search_select_item = $this->RepositorySearch->detail_search_select_item;
        for($ii = 0; $ii < count($this->detail_search_select_item); $ii++) {
            if(array_key_exists("value", $this->detail_search_select_item[$ii])) {
                $this->detail_search_select_item[$ii]["value"] = str_replace("\\", "\\\\", $this->detail_search_select_item[$ii]["value"]);
            }
        }
        $this->default_detail_search = $this->RepositorySearch->default_detail_search;
        $this->search_requestparameter_list = $this->RepositorySearch->getRequestParameterList();
    }

    /**
     * set request parameter for public view
     *
     */
    private function setRequestParameter()
    {
        $this->index_id         = $this->RepositorySearch->index_id;
        $this->sort_order       = $this->RepositorySearch->sort_order;

        $this->page_no          = $this->RepositorySearch->page_no;
        $this->list_view_num    = $this->RepositorySearch->list_view_num;
        $this->lang             = $this->RepositorySearch->lang;
        $this->item_num = $this->RepositorySearch->getTotal();

        // 表示されるページ数計算
        $this->page_num = (int)($this->item_num / $this->list_view_num);
        if(($this->item_num%$this->list_view_num) != 0){
            $this->page_num++;
        }
        $this->view_start_no = $this->RepositorySearch->getStartIndex();
        $this->view_end_no = intval($this->page_no) * intval($this->list_view_num);
    }

    /**
     * set index list
     *
     */
    private function setIndexList()
    {
        if(strlen($this->index_id) == 0)
        {
            return;
        }

        // Add Open Depo 2013/12/14 S.Arata --start--
        $user_id = $this->Session->getParameter("_user_id");
        // Add Open Depo 2013/12/14 S.Arata --end--
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->getRoomAuthorityID();
        // Add OpenDepo 2013/12/02 R.Matsuura --start--
        $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $publicIndexQuery = $indexAuthorityManager->getPublicIndexQuery(false, $this->repository_admin_base, $this->repository_admin_room);
        // Add OpenDepo 2013/12/02 R.Matsuura --end--
        $parentPrivateTreeId = null;
        $error_msg = null;
        $return = $this->getAdminParam('privatetree_parent_indexid', $parentPrivateTreeId, $error_msg);
        if($this->index_id != $parentPrivateTreeId){
            // select childen index.
            $query = "";
            if($this->indexDispType == 0)
            {
                $query .= " SELECT * ";
            }
            else
            {
                $query .= "SELECT idx.`index_id`, idx.`index_name`, idx.`index_name_english`, idx.`contents`, idx.`comment`, idx.`rss_display`, idx.`mod_date` ";
            }

            // Mod OpenDepo 2014/01/31 S.Arata --start--
            $query .= " FROM ". DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_INDEX." idx ";

            if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // admin
            }
            else
            {
                $pub_index = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $this->index_id);
                if(count($pub_index) == 0)
                {
                    // Modify Open Depo 2013/12/14 S.Arata --start--
                    if($user_id == "0"){
                        // not login
                        $this->detail_info = "login_index";
                    } else {
                        // not access index
                        $this->detail_info = "not_access";
                    }
                    // Modify Open Depo 2013/12/14 S.Arata --end--
                    // list view
                    $this->index_id = "";
                }

                $query .= " INNER JOIN (".$publicIndexQuery.") pub ON idx.index_id = pub.index_id";
            }
            $query .= " WHERE idx.parent_index_id = ? ".
                      " AND idx.is_delete = ? ".
                      " ORDER BY idx.`show_order` ASC ";
            $generalParams = array();
            $generalParams[] = $this->index_id;
            $generalParams[] = 0;
            // Mod OpenDepo 2014/01/31 S.Arata --end--
        } else {
            $privatetree_sort_order = "";
            $order = " ORDER BY ";
            $this->getAdminParam('privatetree_sort_order', $privatetree_sort_order, $errorMsg);
            if($privatetree_sort_order == 0){
                $order .= "show_order";
            } else {
                $order .= "idxName ";
                if($privatetree_sort_order == 1){
                    $order .= " ASC";
                } else {
                    $order .= " DESC";
                }
            }

            if($this->lang == "japanese")
            {
                $priorityName = "index_name";
            } else {
                $priorityName = "index_name_english";
            }
            // プライベートツリー以外のインデックスを取得
            $query = " SELECT * ".
                     "FROM ( ".
                     " SELECT index_name AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                     " FROM ". DATABASE_PREFIX ."repository_index ".
                     " WHERE parent_index_id = ? ".
                     " AND is_delete = 0 ".
                     " AND LENGTH( owner_user_id ) = 0 ".
                     " AND `owner_user_id` = '' ".    // Add not show privateTree K.Matsuo 2013/04/10
                     " ) AS TABLE1 ";

            if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // admin
            }
            else
            {
                $pub_index = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $this->index_id);
                if(count($pub_index) == 0)
                {
                    // Modify Open Depo 2013/12/14 S.Arata --start--
                    if($user_id == "0"){
                        // not login
                        $this->detail_info = "login_index";
                    } else {
                        // not access index
                        $this->detail_info = "not_access";
                    }
                    // Modify Open Depo 2013/12/14 S.Arata --end--
                    // list view
                    $this->index_id = "";
                }
                $query .= " INNER JOIN (".$publicIndexQuery.") pub ON TABLE1.index_id = pub.index_id ";
            }
            // プライベートツリーを取得指定のソート順で取得
            $privateTreeQuery = "SELECT * ".
                              "FROM ( ( ".
                              "  SELECT ".$priorityName." AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                              "  FROM ". DATABASE_PREFIX ."repository_index ".
                              "  WHERE parent_index_id = ? ".
                              "  AND is_delete = 0 ".
                              "  AND LENGTH( owner_user_id ) > 0 ".
                              "  AND LENGTH( index_name ) > 0 ".
                              "  AND LENGTH( index_name_english ) > 0 ".
                              " ) UNION ( ".
                              "  SELECT index_name_english AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                              "  FROM ". DATABASE_PREFIX ."repository_index ".
                              "  WHERE parent_index_id = ? ".
                              "  AND is_delete = 0 ".
                              "  AND LENGTH( owner_user_id ) > 0 ".
                              "  AND LENGTH( index_name ) = 0 ".
                              " ) UNION ( ".
                              "  SELECT index_name AS idxName, ". DATABASE_PREFIX ."repository_index. * ".
                              "  FROM ". DATABASE_PREFIX ."repository_index ".
                              "  WHERE parent_index_id = ? ".
                              "  AND is_delete = 0 ".
                              "  AND LENGTH( owner_user_id ) > 0 ".
                              " AND LENGTH( index_name_english ) = 0 ".
                              " ) ".
                              ") AS TABLE2 ";

            if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                // admin
            }
            else
            {
                $pub_index = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $this->index_id);
                if(count($pub_index) == 0)
                {
                    // Modify Open Depo 2013/12/14 S.Arata --start--
                    if($user_id == "0"){
                        // not login
                        $this->detail_info = "login_index";
                    } else {
                        // not access index
                        $this->detail_info = "not_access";
                    }
                    // Modify Open Depo 2013/12/14 S.Arata --end--
                    // list view
                    $this->index_id = "";
                }
                $privateTreeQuery .= " INNER JOIN (".$publicIndexQuery.") pub ON TABLE2.index_id = pub.index_id ";
            }
            // Mod OpenDepo 2014/01/31 S.Arata --end--

            $privateTreeQuery .=  $order . ";";
            $query .= " ORDER BY `show_order` ;";
            
            $generalParams = array();
            $generalParams[] = $this->index_id;
            
            $privateParams = array();
            $privateParams[] = $this->index_id;
            $privateParams[] = $this->index_id;
            $privateParams[] = $this->index_id;
        }
        // get children index
        // 通常ツリーとプライベートツリーを合成する配列
        $childIndexList = array();
        
        // 通常ツリーを取得
        $generalList = $this->Db->execute($query, $generalParams);
        if($generalList === false)
        {
            $generalList = array();
        }
        
        // プライベートツリーを取得
        if (isset($privateTreeQuery)) 
        {
            $privateList = $this->Db->execute($privateTreeQuery, $privateParams);
            if($privateList === false)
            {
                $privateList = array();
            }
            
            // プライベートツリーが存在するなら通常ツリーと合成
            $childIndexList = array_merge($generalList, $privateList);
        }
        else {
            // プライベートツリーが存在しないなら通常ツリーだけを代入
            $childIndexList = $generalList;
        }
        
        for($ii=0; $ii<count($childIndexList); $ii++)
        {
            $childIndexList[$ii]["oaiore_uri"] = "";
            $childIndexList[$ii]["permalink"] = BASE_URL."/?action=repository_opensearch".
                                                "&index_id=".$childIndexList[$ii]["index_id"].
                                                "&count=" .$this->list_view_num.
                                                "&order=" .$this->sort_order.
                                                "&pn=1";

            $comment = explode("\n", $childIndexList[$ii]['comment']);
            $childIndexList[$ii]['comment_array'] = $comment;
            $childIndexList[$ii]['comment_row'] = count($comment);
            $childIndexList[$ii]['rss'] = "";
            if($childIndexList[$ii]['rss_display'] == 1)
            {
                $childIndexList[$ii]['rss'] = BASE_URL."/?action=repository_rss&index_id=".$childIndexList[$ii]['index_id'];
            }

            // 孫インデックスが有るか
            $query = " SELECT parent_index_id, count(index_id) AS cnt ".
                     " FROM ". DATABASE_PREFIX ."repository_index ".
                     " WHERE parent_index_id = ? ".
                     " AND is_delete = ? ".
                     " GROUP BY parent_index_id ";
            $params = array();
            $params[] = $childIndexList[$ii]["index_id"];
            $params[] = 0;
            $childIndex = $this->Db->execute($query, $params);
            // 孫インデックスがない場合、孫アイテムは有るか
            if(isset($childIndex[0]["cnt"]) && $childIndex[0]["cnt"]> 0)
            {
                $childIndexList[$ii]["oaiore_uri"] = BASE_URL."/?action=repository_oaiore&indexId=".$childIndexList[$ii]["index_id"];
            }
            else
            {
                // check this index has item
                $query = "SELECT count(item_id) AS cnt ".
                         "FROM ". DATABASE_PREFIX ."repository_position_index ".
                         "WHERE index_id = ? AND ".
                         "is_delete = ? ";
                $childItem = $this->Db->execute($query, $params);
                if($childItem[0]["cnt"] > 0)    // change ["count(*)"] into ["cnt"] 2013/09/13 K.Matsushita
                {
                    $childIndexList[$ii]["oaiore_uri"] = BASE_URL."/?action=repository_oaiore&indexId=".$childIndexList[$ii]["index_id"];
                }
            }
            // Add private_contents count K.Matsuo 2013/05/07 --start--
            if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room)
            {
                $childIndexList[$ii]['show_private_contents'] = true;
            } else if($childIndexList[$ii]['owner_user_id'] == $this->Session->getParameter("_user_id")){
                $childIndexList[$ii]['show_private_contents'] = true;
            } else {
                $childIndexList[$ii]['show_private_contents'] = false;
            }
            // Add private_contents count K.Matsuo 2013/05/07 --end--
        }
        // set child index info
        if(count($childIndexList) > 0)
        {
            $this->Session->setParameter("child_index", $childIndexList);
        }

        // インデックスリストのパンクズ
        $parent_index = array();
        $query = " SELECT * ".
                 " FROM ". DATABASE_PREFIX ."repository_index ".
                 " WHERE index_id = ? AND ".
                 " is_delete = ?; ";
        $params = null;
        $params[] = $this->index_id;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return;
        }
        if(count($result) == 1)
        {
            if(strlen($result[0]['thumbnail']) > 0 && $this->indexDispType == 1)
            {
                // Modify #268 index that has not item show thumbnail 2012/01/18 T.Koyasu -start
                $thumbnail = $result[0]['thumbnail'];
                // create image
                $img = imagecreatefromstring($thumbnail);
                if($img !== false)
                {
                    // get width and height by image
                    $result[0]['thumbnail_width'] = imagesx($img);
                    $result[0]['thumbnail_height'] = imagesy($img);
                    // drop image
                    imagedestroy($img);
                }
                // Modify #268 index that has not item show thumbnail 2012/01/18 T.Koyasu -en
            }
            $result[0]['thumbnail'] = "";
            // Add contents page Y.Nakao 2010/08/06 --start--
            if(strlen($result[0]["comment"]) > 0)
            {
                $result[0]["comment"] = explode("\n", $result[0]["comment"]);
            }
            else
            {
                $result[0]["comment"] = array();
            }
            // Add contents page Y.Nakao 2010/08/06 --end--

            $this->Session->setParameter("index_data", $result[0]);
            $this->getParentIndex($result[0]['parent_index_id'], $parent_index);
            if(count($parent_index)!= 0)
            {
                // Add check closed index 2009/12/17 Y.Nakao --start--
                for($ii=0; $ii<count($parent_index); $ii++)
                {
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // Mod OpenDepo 2014/01/31 S.Arata --start--
                    // if ($user_auth_id >= _AUTH_MODERATE) {
                    $pub_index = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $parent_index[$ii]["index_id"]);
                    if($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) {
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        $parent_index[$ii]["pub_status"] = "1";
                    } else if(count($pub_index) > 0){
                        $parent_index[$ii]["pub_status"] = "1";
                    } else {
                        $parent_index[$ii]["pub_status"] = "0";
                    }
                }
                // Mod OpenDepo 2014/01/31 S.Arata --end--
                // Add check closed index 2009/12/17 Y.Nakao --end--
                // set pankuzu
                $this->Session->setParameter("parent_index_data", $parent_index);
            }
        }
    }
}
?>