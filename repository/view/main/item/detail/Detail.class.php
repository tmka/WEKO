<?php
// --------------------------------------------------------------------
//
// $Id: Detail.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUsagestatistics.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryItemAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchRequestParameter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryCheckFileTypeUtility.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Detail extends RepositoryAction
//class Repository_View_Common_Item_Detail
{
    // リクエストパラメタ
    //var $item_id = null;          // アイテムID
    
    var $Session = null;
//  var $Db = null;
    var $languagesView = null;

    // リクエストパラメタ
    var $item_id = null;            // アイテムID
    var $item_no = null;            // アイテム通番

    // その他
    var $IsSelectPublish = "false"; // 公開／非公開の選択可否
    var $IsSelectDelete = "false";      // 削除の選択可否
    var $IsSelectEdit = "false";        // 編集の選択可否
    var $IsSelectShow = "false";        // 公開変更可否
    var $shown_status = "";     // 公開状況
    
    var $IsFile = "false";              // ファイルを保持しているか否か(2008/07/29 Y.Nakao)
    var $IsFileView = "false";          // 詳細表示で表示するファイルがあるか否か(2009/11/24 A.Suzuki)
    var $IsFileSimpleView = array();    // 簡易表示で表示するファイルがあるか否か(2009/12/14 A.Suzuki)
    var $IsFlashView = "false";     // FLASH表示で表示するファイルがあるか否か(2010/01/19 A.Suzuki)
    
    // Add check pub detail view 2008/10/21 Y.Nakao --start--       
    var $detail_info = "";      // detail view from url
    // Add check pub detail view 2008/10/21 Y.Nakao --end--
    
    // Add output ID in detail page 2009/01/15 A.Suzuki --start--
    var $uri = "";
    // Add output ID in detail page 2009/01/15 A.Suzuki --end--
    
    // Add shiboleth login 2009/03/17 Y.Nakao --start--
    var $shib_login_flg = "";
    // Add shiboleth login 2009/03/17 Y.Nakao --end--
    
    // Add matadata select language 2009/07/31 A.Suzuki --start--
    var $alter_flg = "0";       // 他言語表示フラグ
    // Add matadata select language 2009/07/31 A.Suzuki --start--
    
    // Fix direct link from ID server 2009/08/17 A.Suzuki --start--
    var $getData = null;
    var $block_id = null;
    // Fix direct link from ID server 2009/08/17 A.Suzuki --end--
    
    // Add supple item 2009/08/24 A.Suzuki --start--
    var $IsSupple = "";         // サプリアイテムタイプを保持しているか否か
    var $supple_error = "";     // サプリアイテム登録時のエラーメッセージ
    var $IsSuppleAdd = "false";     // サプリアイテムの登録可否
    // Add supple item 2009/08/24 A.Suzuki --end--
    
    // Add get suffixID button for detail page 2009/09/03 A.Suzuki --start--
    var $IsPrefix = "false";            // IDサーバと連携しているか否か
    // Add get suffixID button for detail page 2009/09/03 A.Suzuki --end--

    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    var $oaiore_icon_display = "";
    // Set help icon setting 2010/02/10 K.Ando --end--
    
    // For flash annotation
    var $encode_baseurl = "";
    var $annoteaUser = "";
    
    var $fileIdx = "";                  // ログイン後のファイルダウンロード情報
    
    // Fix contents update action 2010/07/02 Y.Nakao --start--
    var $review_status = "";
    var $reject_status = "";
    // Fix contents update action 2010/07/02 Y.Nakao --end--
    
    // Add came from flash 2011/01/04 H.Goto --start--
    var $version_flg = "";
    // Add came from flash 2011/01/04 H.Goto --end--
    // Add index list 2010/04/13 S.Abe --start--
    // index flag
    var $select_index_list_display = "";
    // index list which show index
    var $select_index_list = array();
    // Add index list 2010/04/13 S.Abe --end--
    
    // For support to output meta tag 2011/12/06 A.Suzuki --start--
    var $commonMain = null;
    // For support to output meta tag 2011/12/06 A.Suzuki --end--
    
    // Add smartPhone support T.Koyasu 2012/04/09 -start-
    public $iPhoneFlg = false;
    // add for show detail(workflow) of unregistered item
    private $fromWorkflowFlg_ = false;
    // Add smartPhone support T.Koyasu 2012/04/09 -end-
    
    // Add usgastatistics 2012/08/07 A.Suzuki --start--
    public $isDispUsageLink = false;
    // Add usgastatistics 2012/08/07 A.Suzuki --end--
    
    // Add multimedia support 2012/08/27 T.Koyasu -start-
    public $IsMultimediaView = false;          // FLVプレーヤー表示で表示するファイルがあるか否か(2012/08/30 T.Koyasu)
    public $contentsTypeList = array();
    // Add multimedia support 2012/08/27 T.Koyasu -end-
    
    public $errMsg = array();
    
    // Fix file download action 2013/03/12 Y.Nakao --strat--
    private $RepositoryValidator = null;
    private $RepositoryUsagestatistics = null;
    private $addHeaderFlag = false;
    // Fix file download action 2013/03/12 Y.Nakao --end--
    
    // Get reference data 2013/10/11 S.Suzuki --start--
    public $fileName = array();
    public $fileURL = array();
    // Get reference data 2013/10/11 S.Suzuki --end--    
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
    // Add session -> action K.Matsuo 2013/12/09 --end--
    
    // Add for extended thumbnail 2014/01/08 --start--
    public $isShowThumbnail = array();
    // Add for extended thumbnail 2014/01/08 --end--
    
    // Add for extended thumbnail 2014/02/13 --start--
    public $thumbnail_transition_speed = null;
    public $thumbnail_transition_interval = null;
    public $flash_image_num = array();
    // Add for extended thumbnail 2014/02/13 --end--
    // Add referer display 2014/05/22 T.Ichikawa --start--
    public $externalSearchWord = array();
    public $searchWordDisplayFlg = null;
    // Add referer display 2014/05/22 T.Ichikawa --end--
    
    // Add for JaLC DOI R.Matsuura 2014/06/13 --start--
    public $self_doi_uri = null;
    // Add for JaLC DOI R.Matsuura 2014/06/13 --end--
    
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
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
                $user_error_msg = 'initで既に・・・';
                throw $exception;
            }
            
            $searchParam = new RepositorySearchRequestParameter();
            $searchParam->setRequestParameterFromReferrer();
            $searchParam->setActionParameter();
            
            // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --start--
            $this->search_type = $searchParam->all_search_type;
            // Fix advanced search for ranking view at top page. Y.Nakao 2014/01/14 --end--
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
        
            // Set error message
            $this->errMsg = array();
            if($this->Session->getParameter("error_msg")!=null && strlen($this->Session->getParameter("error_msg"))>0)
            {
                array_push($this->errMsg, $this->Session->getParameter("error_msg"));
            }
            $this->Session->removeParameter("error_msg");
            
            // Add smartPhone support T.Koyasu 2012/04/09 -start-
            if(is_numeric(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')) || is_numeric(strpos($_SERVER['HTTP_USER_AGENT'], 'iPad'))) {
                $this->iPhoneFlg = true;
            }
            // Add smartPhone support T.Koyasu 2012/04/09 -end-

            // Create instance
            $NameAuthority = new NameAuthority($this->Session, $this->Db);
            $this->RepositoryUsagestatistics = new RepositoryUsagestatistics($this->Session, $this->Db, $this->TransStartDate);
            
            // For flash annotation
            $this->encode_baseurl = urlencode(BASE_URL);
            if($this->Session->getParameter("_handle") != null){
                $this->annoteaUser = $this->Session->getParameter("_handle");
            }

            // Add select language 2009/07/03 A.Suzuki --start--
            $lang_list = $this->languagesView->getLanguagesList();
            $this->Session->setParameter("lang_list", $lang_list);
            // Add select language 2009/07/03 A.Suzuki --end--
            
            // Add Advanced Search 2013/11/26 R.Matsuura --start--
            $itemAuthorityManager = new RepositoryItemAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
            // Add Advanced Search 2013/11/26 R.Matsuura --end--
            
            // Add for extended thumbnail 2014/02/13 --start--
            $this->thumbnail_transition_speed = _REPOSITORY_THUMBNAIL_TRANSITION_SPEED;
            $this->thumbnail_transition_interval = _REPOSITORY_THUMBNAIL_TRANSITION_INTERVAL;
            // Add for extended thumbnail 2014/02/13 --end--
            
            // Fix redirct prev page 2009/07/17 A.Suzuki --start--
            if($this->Session->getParameter("supple_flag") != "true"){
                $tmp_uri = urldecode($this->Session->getParameter("prev_page_uri"));
                $prev_page_uri = $_SERVER["HTTP_REFERER"];
                if($tmp_uri != "" && $tmp_uri != null && BASE_URL."/?".$_SERVER["QUERY_STRING"] == $prev_page_uri){
                    $this->Session->setParameter("prev_page_uri", urlencode($tmp_uri));
                } else if(strpos($prev_page_uri, BASE_URL) == 0){
                    $this->Session->setParameter("prev_page_uri", urlencode($prev_page_uri));
                } else {
                    $this->Session->removeParameter("prev_page_uri");
                }
            } else {
                $this->Session->removeParameter("supple_flag");
            }
            // Fix redirct prev page 2009/07/17 A.Suzuki --start--
            
            // Fix direct link from ID server 2009/08/17 A.Suzuki --start--
            if($this->Session->getParameter("select_language") == null){
                $query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
                         "WHERE param_name = 'select_language';";
                $result = $this->Db->execute($query);
                if($result === false){
                    $errMsg = $this->Db->ErrorMsg();
                    $tmpstr = sprintf("log : %s", $errMsg );
                    array_push($this->errMsg, $tmpstr);
                    $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
                    // Add smartPhone support T.Koyasu 2012/04/09 -start-
                    if($this->smartphoneFlg) {
                        return 'error_sp';
                    } else {
                        return "error";
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -end-
                }
                // 言語選択の表示設定をセッションに保存
                $this->Session->setParameter("select_language", $result[0]["param_value"]);
            }
            
            $blocks =& $this->getData->getParameter("blocks");
            $block_obj = $blocks[$this->block_id];
            //$this->Session->setParameter("repository_theme", $block_obj['theme_name']);
            if($block_obj['theme_name'] == null || $block_obj['theme_name'] == "") {
                //Auto select from Page theme 
                $themeList = $this->Session->getParameter("_theme_list");
                $pages =& $this->getData->getParameter("pages");
                $this->Session->setParameter("repository_theme",$themeList[$pages[$block_obj['page_id']]['display_position']]);
            } else {
                $this->Session->setParameter("repository_theme", $block_obj['theme_name']); 
            }
            // Fix direct link from ID server 2009/08/17 A.Suzuki --end--
            
            // Add supple error message 2009/08/27 A.Suzuki --start--
            // 言語リソースを設定
            $this->setLangResource();
            if($this->Session->getParameter("supple_error") === 1){
                // no set address error
                $this->supple_error = $this->Session->getParameter("smartyAssign")->getLang("repository_supple_no_address");
            } else if($this->Session->getParameter("supple_error") === 2){
                // get false error
                $this->supple_error = $this->Session->getParameter("smartyAssign")->getLang("repository_supple_get_false");
            } else if($this->Session->getParameter("supple_error") === 3){
                // no item error
                $this->supple_error = $this->Session->getParameter("smartyAssign")->getLang("repository_supple_no_item");
            }

            $this->Session->removeParameter("supple_error");
            // Add supple error message 2009/08/27 A.Suzuki --end--
            
            // Add index list 2010/04/13 S.Abe --start--
            $this->select_index_list_display = $this->getSelectIndexListDisplay();
            if($this->select_index_list_display == 1) {
                // Add Advanced Search 2013/11/26 R.Matsuura --start--
                $indexManager= new RepositoryIndexManager($this->Session, $this->dbAccess, $this->TransStartDate);
                $this->select_index_list = $indexManager->getDisplayIndexList($this->repository_admin_base, $this->repository_admin_room);   
                // Add Advanced Search 2013/11/26 R.Matsuura --end-- 
            }
            // Add index list 2010/04/13 S.Abe --end--
            
            // Modify Price method move validator K.Matsuo 2011/10/18 --start--
            require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
            $this->RepositoryValidator = new Repository_Validator_DownloadCheck();
            $initResult = $this->RepositoryValidator->setComponents($this->Session, $this->Db);
            if($initResult === false)
            {
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg) {
                    return 'error_sp';
                } else {
                    return "error";
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
            // Modify Price method move validator K.Matsuo 2011/10/18 --end--
            
            // Add check version H.Goto 2010/12/22 --start--
            $version = $this->getNCVersion();
            
            if(str_replace(".", "", $version) < 2301){
              // under ver.2.3.0.1
              $this->version_flg = "0";
            }else{
              // over ver.2.3.0.1
              $this->version_flg = "1";
            }
            // Add check version H.Goto 2010/12/22 --end--

            /*
            // Add viewer or DL flag 2010/01/12 H.Goto --end--
            // fix detail view 2008/09/19 Y.Nakao --start--
            $this->_container =& DIContainerFactory::getContainer();
            $this->_session =& $this->_container->getComponent("Session");
            // fix detail view 2008/09/19 Y.Nakao --end--
            */
            
            // Add CiNii connection 2008/08/25 Y.Nakao
            $user_auth_id = $this->Session->getParameter("_user_auth_id"); // 会員の会員権限ID
            $auth_id = $this->Session->getParameter("_auth_id");
            
            
            // Add make tree info 2008/10/20 Y.Nakao --start--
            // change index tree view action 2008/12/04 Y.Nakao --start--
            //$this->setIndexTreeData2Session();
            // change index tree view action 2008/12/04 Y.Nakao --end--
            // Add make treeinfo 2008/10/20 Y.Nakao --end--
            
            // Add Output "OAI-PMH getrecord" on detail page J.Ito --start--
            $this->Session->setParameter("oaipmh_uri", BASE_URL."/?action=repository_oaipmh&verb=GetRecord&metadataPrefix=junii2&identifier=oai:".$_SERVER['HTTP_HOST'].":".sprintf("%08d", $this->item_id));
            // Add Output "OAI-PMH getrecord" on detail page J.Ito --end--
            
            // Add Output bibTeX feed on detail page 2008/10/30 A.Suzuki --start--
            $this->Session->setParameter("bibtex_uri", BASE_URL."/?action=repository_bibtex&itemId=".$this->item_id."&itemNo=".$this->item_no);
            // Add Output bibTeX feed on detail page 2008/10/30 A.Suzuki --end--
            
            // Add Output SWRC feed on detail page 2008/11/12 A.Suzuki --start--
            $this->Session->setParameter("swrc_uri", BASE_URL."/?action=repository_swrc&itemId=".$this->item_id."&itemNo=".$this->item_no);
            // Add Output SWRC feed on detail page 2008/11/12 A.Suzuki --end--
             
            // 通貨単位設定
            $money_units = null;
            $money_units = $this->getMoneyUnit();
            // Mod change yen mark to html special char T.Koyasu 2014/07/31 --start--
            if($money_units != null){
                if(strpos($money_units['money_unit'], "\\") === 0){
                    $money_units['money_unit'] = "&yen;";
                }
                
                $this->Session->setParameter('money_unit', $money_units['money_unit']);
                $this->Session->setParameter('money_unit_conf', $money_units['money_unit_conf']);
            } else {
                $this->Session->removeParameter('money_unit');
                $this->Session->removeParameter('money_unit_conf');
            }
            // Mod change yen mark to html special char T.Koyasu 2014/07/31 --end--
            
            // Add fix detail view not view tree 2008/10/21 Y.Nakao --start--
            
            // Add check pub detail view 2008/10/21 Y.Nakao --start--
            $container = null;
            if($this->Session == null){
                $container =& DIContainerFactory::getContainer();
                $this->Session =& $container->getComponent("Session");
            }
            $login_id = $this->Session->getParameter("_login_id");
            $user_id = $this->Session->getParameter("_user_id");
            if ( !(isset($login_id) && strlen($login_id) != 0) ){
                // not login
                // get DB Object
                if($container == null){
                    $container =& DIContainerFactory::getContainer();
                }
                $actionChain =& $container->getComponent("ActionChain");
                $action =& $actionChain->getCurAction();
                $this->Db =& $container->getComponent("DbObject");
            }
            // Add check pub detail view 2008/10/21 Y.Nakao --end--
            
            // add file download after login K.Matsuo 2011/10/12 --start-- 
            if(strlen($this->fileIdx) == 0 && $user_id != "0" && strlen($login_id) != 0)
            {
                // file download login.
                $this->fileIdx = $this->Session->getParameter('repository'.$this->block_id.'FileDownloadKey');
            }
            else
            {
                // file download login cancel.
                $this->fileIdx = '';
            }
            $this->Session->removeParameter('repository'.$this->block_id.'FileDownloadKey');
            // add file download after login K.Matsuo 2011/10/12 --end--
            
            // For support to output meta tag 2011/12/06 A.Suzuki --start--
            // Check exist method:"addHeader" in CommonMain class
            $this->addHeaderFlag = false;
            if(method_exists($this->commonMain, "addHeader"))
            {
                $this->addHeaderFlag = true;
            }
            // For support to output meta tag 2011/12/06 A.Suzuki --end--

            // 初期設定
            $this->IsSelectPublish = "false";
            $this->IsSelectDelete = "false";
            $this->IsSelectEdit = "false";
            $this->IsSelectShow = "false";
            $this->IsSuppleAdd = "false";
            
            if($this->item_id != null && $this->item_no != null){
                $this->Session->setParameter("search_flg","true");
                // リクエストパラメタ格納
                $this->Session->setParameter("item_id_for_detail", $this->item_id);
                $this->Session->setParameter("item_no_for_detail", $this->item_no);
                // 画面右に詳細表示を表示させるフラグ
                $this->Session->setParameter("serach_screen", "1");
            }
            
            // エラー開放
            $this->Session->removeParameter("error_code");
            //$item = $this->Session->getParameter("item");
            //$item_user = $item['user_id'];
            
            // user_id指定方法変更 2008/03/15 nakao
            $query = "SELECT * ".
                     "FROM ". DATABASE_PREFIX ."repository_item ".  // アイテムテーブル
                     "WHERE item_id = ? AND ".  // Item_ID
                     "item_no = ? AND ".        // Item_No 
                     "is_delete = ?; ";     // かつ、削除されていない
            $params = null;
            // $queryの?を置き換える配列
            $params[] = $this->Session->getParameter("item_id_for_detail");
            $params[] = $this->Session->getParameter("item_no_for_detail");
            $params[] = 0;
            // SELECT実行
            $result_Item_Table = $this->Db->execute($query, $params);
            if($result_Item_Table === false){
                $this->failTrans();                                 //トランザクション失敗を設定(ROLLBACK)
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                // Add smartPhone support T.Koyasu 2012/04/12 -start-
                if($this->smartphoneFlg){
                    return 'error_sp';
                } else {
                    return 'error';
                }
                // Add smartPhone support T.Koyasu 2012/04/12 -end-
            }
            if(count($result_Item_Table) == 0){
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                // Add show info 2008/10/22 Y.Nakao --start--
                // fix when push top tab, show "item is del" alert bug 2008/12/03 Y.Nakao --start--
                if( ($this->item_id==null || $this->item_id=="") && 
                    ($this->item_no==null || $this->item_no=="") ){
                    $this->Session->setParameter("serach_screen", "0");
                    // Add smartPhone support T.Koyasu 2012/04/09 -start-
                    if($this->smartphoneFlg){
                        return 'golistview_sp';
                    } else {
                        return 'golistview';
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -end-
                }
                $this->detail_info = "del_item";
                // fix when push top tab, show "item is del" alert bug 2008/12/03 Y.Nakao --end--
                // Add show info 2008/10/22 Y.Nakao --end--
                // list view
                $this->Session->setParameter("serach_screen", "0");
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg){
                    return 'golistview_sp';
                } else {
                    return 'golistview';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            } else if(count($result_Item_Table) != 1){
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                // list view
                $this->Session->setParameter("serach_screen", "0");
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg){
                    return 'golistview_sp';
                } else {
                    return 'golistview';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
            $item_user = $result_Item_Table[0]['ins_user_id'];
            // アイテムIDと通番を設定 nakao 2008/03/15
            $item_id = $this->Session->getParameter("item_id_for_detail");
            $item_no = $this->Session->getParameter("item_no_for_detail");
            // ここまで 2008/03/15
            
            //
            // ユーザ権限を参照し、"編集","削除","公開／非公開"のフラグを立てる
            $user_id = $this->Session->getParameter("_user_id");                    // ユーザID
            $user_name = $this->Session->getParameter("_handle");                   // ユーザのハンドル名(使わないと思うが)
            $user_auth_id = $this->Session->getParameter("_user_auth_id");          // 会員の会員権限ID
            $role_auth_id = $this->Session->getParameter("_role_auth_id");          // 会員のロール権限ID
            $system_user_flag = $this->Session->getParameter("_system_user_flag");  // システム管理者ならば1
            $timezone_offset = $this->Session->getParameter("_timezone_offset");    // ???
            $auth_id = $this->Session->getParameter("_auth_id");
        
            // Add check pub detail view 2008/10/21 Y.Nakao --start--
            // Add tree access control list 2012/03/23 T.Koyasu -start-
            if($this->Session->getParameter("_mobile_flag") == _ON)
            {
                $this->Session->removeParameter("detail_screen");
            }
            if($this->Session->getParameter("detail_screen") == "login"){
                // Login OK
                $this->Session->setParameter("detail_screen", "check");
                $result = $this->exitAction();
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg) {
                    return 'success_sp';
                } else {
                    return 'success';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
            else if($this->Session->getParameter("detail_screen") == "check" && $user_id == "0")
            {
                $this->Session->removeParameter("detail_screen");
                $this->Session->setParameter("serach_screen", "0");
                // Longin NG
                $this->Session->removeParameter("detail_screen");
                $result = $this->exitAction();

                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg){
                    return 'golistview_sp';
                } else {
                    return 'golistview';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
            // Add tree access control list 2012/03/23 T.Koyasu -end-
            $this->Session->setParameter("search_flg", "false");
            
            // Update check pub index 2009/12/17 Y.Nakao --start--
            // get position index list
            $pos_index_id = array(); 
            // Fix exclude position root index item Y.Nakao 2013/06/20 --start--
            $query = "SELECT `index_id` ".
                     "FROM ". DATABASE_PREFIX ."repository_position_index ".
                     "WHERE `item_id` = ? ".
                     " AND `item_no` = ? ".
                     " AND index_id != ? ".
                     " AND is_delete = ? ";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = 0;
            $params[] = 0;
            // Fix exclude position root index item Y.Nakao 2013/06/20 --end--
            $result = $this->Db->execute($query, $params);
            if($result === false){
                // this user dosen't view this item
                $this->detail_info = "not_access";
                // list view
                $this->Session->setParameter("serach_screen", "0");
                $result = $this->exitAction();
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg){
                    return 'golistview_sp';
                } else {
                    return 'golistview';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
            for($ii=0; $ii<count($result); $ii++){
                array_push($pos_index_id, $result[$ii]["index_id"]);
            }
            
            // Add smartPhone support T.Koyasu 2012/04/12 -start-
            // from common detail, index is not checked public or not
            // for unregistered item
            if(!$this->fromWorkflowFlg_){
                
                // Fix exclude position root index item Y.Nakao 2013/06/20 --start--
                if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room)
                {
                    // 管理者は条件なしで閲覧可能
                } else if($user_id == $item_user)
                {
                    // 登録者は条件なしで閲覧可能
                }
                else 
                {
                    // Add check user_auth_id for unpublic index 2009/02/04 A.Suzuki --start--
                    $query = "SELECT `index_name` ".
                             "FROM ". DATABASE_PREFIX ."repository_index ".
                             "WHERE `index_id` IN (".implode(",", $pos_index_id).") ".
                             "AND `is_delete` = '0' ";
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if($user_auth_id < _AUTH_MODERATE){
                    if($user_auth_id < $this->repository_admin_base || $auth_id < $this->repository_admin_room){
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        //$query .= " AND `pub_date` <= '".date('Y-m-d 00:00:00.000',mktime())."' ";
                        $query .= " AND `pub_date` <= NOW() ";
                    }
                    $query .= ";";
                    $ret = $this->Db->execute($query);
                    if($ret === false){
                        // this user dosen't view this item
                        $this->detail_info = "not_access";
                        // list view
                        $result = $this->exitAction();
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg){
                            return 'golistview_sp';
                        } else {
                            return 'golistview';
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                    if(count($ret) == 0){
                        // no item with pub index 
                        if($user_id == "0"){
                            // not login
                            $this->detail_info = "login";
                            $this->Session->setParameter("detail_screen", "login");
                            // go list view with execute login
                            $result = $this->exitAction(); 
                            // Add smartPhone support T.Koyasu 2012/04/09 -start-
                            if($this->smartphoneFlg){
                                return 'golistview_sp';
                            } else {
                                return 'golistview';
                            }
                            // Add smartPhone support T.Koyasu 2012/04/09 -end-
                        } else {
                            // this user dosen't view this item
                            $this->detail_info = "not_access";
                            // go list view with alert don't view
                            $result = $this->exitAction(); 
                            // Add smartPhone support T.Koyasu 2012/04/09 -start-
                            if($this->smartphoneFlg){
                                return 'golistview_sp';
                            } else {
                                return 'golistview';
                            }
                            // Add smartPhone support T.Koyasu 2012/04/09 -end-
                        }
                    }
                    // Add check closed index 2010/01/06 Y.Nakao --start--
                }
                // Fix exclude position root index item Y.Nakao 2013/06/20 --end--
            }
            // Add smartPhone support T.Koyasu 2012/04/12 -end-
            if($user_auth_id == ""){
                //if($result_Item_Table[0]["shown_status"] != 1 &&  $item_user!=$user_id ){
                if( !$itemAuthorityManager->checkItemPublicFlg($item_id, $item_no, $this->repository_admin_base, $this->repository_admin_room) && $item_user!=$user_id ){
                    if($user_id == "0"){
                        // not login
                        $this->detail_info = "login";
                        $this->Session->setParameter("detail_screen", "login");
                        // delete display data
                        $this->Session->removeParameter("item_info");
                        $this->Session->removeParameter("position_index");
                        $this->Session->removeParameter("oaipmh_uri");
                        $this->Session->removeParameter("bibtex_uri");
                        $this->Session->removeParameter("swrc_uri");
                        // go list view with execute login 
                        $result = $this->exitAction(); 
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg){
                            return 'golistview_sp';
                        } else {
                            return 'golistview';
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    } else {
                        // this user dosen't view this item
                        $this->detail_info = "not_access";
                        // delete display data
                        $this->Session->removeParameter("item_info");
                        $this->Session->removeParameter("position_index");
                        $this->Session->removeParameter("oaipmh_uri");
                        $this->Session->removeParameter("bibtex_uri");
                        $this->Session->removeParameter("swrc_uri");
                        // go list view with alert don't view 
                        $result = $this->exitAction(); 
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg){
                            return 'golistview_sp';
                        } else {
                            return 'golistview';
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                }
            // Add config management authority 2010/02/23 Y.Nakao --start--
            // } else if ($user_auth_id >= _AUTH_MODERATE) {
            } else if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) {
                // if moderater then show all item
            // } else if ($user_auth_id >= _AUTH_GENERAL) {
            } else if ($auth_id >= _AUTH_GENERAL) {
            // Add config management authority 2010/02/23 Y.Nakao --start--
                // if under general
                // not pub and not ins user not view item
                if(!$itemAuthorityManager->checkItemPublicFlg($item_id, $item_no, $this->repository_admin_base, $this->repository_admin_room) && $item_user!=$user_id ){
                    // this user dosen't view this item
                    $this->detail_info = "not_access";
                    // delete display data
                    $this->Session->removeParameter("item_info");
                    // go list view with alert don't view 
                    $result = $this->exitAction(); 
                    // Add smartPhone support T.Koyasu 2012/04/09 -start-
                    if($this->smartphoneFlg){
                        return 'golistview_sp';
                    } else {
                        return 'golistview';
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -end-
                }
            } else {
                if( !$itemAuthorityManager->checkItemPublicFlg($item_id, $item_no, $this->repository_admin_base, $this->repository_admin_room) && $item_user!=$user_id ){
                    if($user_id == "0"){
                        // not login
                        $this->detail_info = "login";
                        $this->Session->setParameter("detail_screen", "login");
                        // delete display data
                        $this->Session->removeParameter("item_info");
                        $this->Session->removeParameter("position_index");
                        $this->Session->removeParameter("oaipmh_uri");
                        $this->Session->removeParameter("bibtex_uri");
                        $this->Session->removeParameter("swrc_uri");
                        // go list view with execute login 
                        $result = $this->exitAction(); 
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg){
                            return 'golistview_sp';
                        } else {
                            return 'golistview';
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    } else {
                        // this user dosen't view this item
                        $this->detail_info = "not_access";
                        // delete display data
                        $this->Session->removeParameter("item_info");
                        $this->Session->removeParameter("position_index");
                        $this->Session->removeParameter("oaipmh_uri");
                        $this->Session->removeParameter("bibtex_uri");
                        $this->Session->removeParameter("swrc_uri");
                        // go list view with alert don't view 
                        $result = $this->exitAction(); 
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg){
                            return 'golistview_sp';
                        } else {
                            return 'golistview';
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                }
            }
            // Add check closed index 2010/01/06 Y.Nakao --end--
            
            // Add config management authority 2010/02/23 Y.Nakao --start--
            // if( $user_auth_id >= _AUTH_MODERATE ||($item_user===$user_id) ){
            if( ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) ||($item_user===$user_id) ){
            // Add config management authority 2010/02/23 Y.Nakao --start--
                // システム管理者 ⇒ 何でもOK
                // アイテム登録者  ⇒ 何でもOK
                $this->IsSelectPublish = "true";
                $this->IsSelectDelete = "true";
                $this->IsSelectEdit = "true";
                $this->IsSelectShow = "true";
                $this->IsSuppleAdd = "true";    // Add supple add flag 2009/08/28 A.Suzuki
            } else {
                // システム管理者、アイテム登録者以外 閲覧のみ
                $this->IsSelectPublish = "false";
                $this->IsSelectDelete = "false";
                $this->IsSelectEdit = "false";
                $this->IsSelectShow = "false";
                
                // Add supple add flag 2009/08/28 A.Suzuki --start--
                // 所属インデックスに投稿権限があるユーザの場合
                for($ii=0; $ii<count($result); $ii++){
                    $query = "SELECT access_role FROM ".DATABASE_PREFIX."repository_index ".
                             "WHERE index_id = ? ".
                             "AND is_delete = 0;";
                    $params = array();
                    $params[] = $result[$ii]["index_id"];
                    $ret = $this->Db->execute($query, $params);
                    if($ret === false ){
                        $this->failTrans();
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg) {
                            return 'error_sp';
                        } else {
                            return "error";
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                    $access_role_ids = explode(",", $ret[0]['access_role']);
                    for($jj=0; $jj<count($access_role_ids); $jj++){
                        if($access_role_ids[$jj] == $role_auth_id){
                            $this->IsSuppleAdd = "true";
                            break;
                        }
                    }
                    if($this->IsSuppleAdd == "true"){
                        break;
                    }
                }
                // Add supple add flag 2009/08/28 A.Suzuki --end--
            }
            
            // Add supple WEKO URL check 2009/08/31 A.Suzuki --start--
            $supple_weko_url = "";
            // パラメタテーブルからサプリWEKOのアドレスを取得する
            $query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
                     "WHERE param_name = 'supple_weko_url';";
            $result = $this->Db->execute($query);
            if($result === false){
                $this->failTrans();
                return false;
            }
            if($result[0]['param_value'] == ""){
                $this->IsSuppleAdd = "false";
            } else {
                $supple_weko_url = $result[0]['param_value'];
            }
            // Add supple WEKO URL check 2009/08/31 A.Suzuki --end--
            
            $this->Session->setParameter("IsSelectPublish", $this->IsSelectPublish);
            $this->Session->setParameter("IsSelectDelete", $this->IsSelectDelete);
            $this->Session->setParameter("IsSelectEdit", $this->IsSelectEdit);
            $this->Session->setParameter("IsSelectShow", $this->IsSelectShow);
            $this->Session->setParameter("IsSuppleAdd", $this->IsSuppleAdd);
            if($item_id != null) {
                //
                // 基本情報設定
                //$result = $this->Db->selectExecute("repository_item", array('item_id' => $item_id ));
                $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_item ".  // アイテムテーブル
                         "WHERE item_id = ? AND ".  // Item_ID
                         "item_no = ? AND ".        // Item_No 
                         "is_delete = ?; ";         // 削除されていない
                $params = null;
                // $queryの?を置き換える配列
                $params[] = $item_id;
                $params[] = $item_no;
                $params[] = 0;
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $Error_Msg = $this->Db->ErrorMsg();
                    array_push($this->errMsg, $Error_Msg);
                }
                
                // 更新日時をSessionに保存
                $this->Session->setParameter("item_update_date", $result[0]['mod_date']);

                // 公開状況
                $this->shown_status = $result[0]['shown_status'];
                
                // Fix contents update action 2010/07/02 Y.Nakao --start--
                // review status
                $this->review_status = $result[0]['review_status'];
                $this->reject_status = $result[0]['reject_status'];
                if(intval($this->review_status) == -1 || intval($this->reject_status) == 1){
                    $this->IsSelectShow = "false";
                }
                // Fix contents update action 2010/07/02 Y.Nakao --end--
                $this->Session->setParameter("IsSelectShow", $this->IsSelectShow);

                // For support to output meta tag 2011/12/06 A.Suzuki --start--
                if($this->addHeaderFlag)
                {
                    $metaTagDataList = null;
                    $itemPubDate = "";
                    if($this->convertDataFormatForGoogleScholar(
                        $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_SHOWN_DATE],
                        $tmpItemPubDate))
                    {
                        $itemPubDate = $tmpItemPubDate;
                    }
                    $tagContent = "";
                    if($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_LANGUAGE] == RepositoryConst::ITEM_LANG_JA)
                    {
                        // Item's language is Japanese
                        if(strlen($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) > 0)
                        {
                            // output Japanese title
                            $tagContent = $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                        } else {
                            // output English title
                            $tagContent = $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                        }
                    } else {
                        // Item's language is English
                        if(strlen($result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) > 0)
                        {
                            // output English title
                            $tagContent = $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
                        } else {
                            // output Japanese title
                            $tagContent = $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
                        }
                    }
                    
                    // Add title to metatag
                    $output = $this->createTags(
                            RepositoryConst::TAG_NAME_META,
                            array( RepositoryConst::TAG_ATTR_KEY_NAME => RepositoryConst::GOOGLESCHOLAR_TITLE,
                                   RepositoryConst::TAG_ATTR_KEY_CONTENT => $tagContent ));
                    $this->commonMain->addHeader($output);
                    
                    // Add online date to metatag
                    $output = $this->createTags(
                        RepositoryConst::TAG_NAME_META,
                        array( RepositoryConst::TAG_ATTR_KEY_NAME => RepositoryConst::GOOGLESCHOLAR_ONLINE_DATE,
                               RepositoryConst::TAG_ATTR_KEY_CONTENT => $itemPubDate ));
                    $this->commonMain->addHeader($output);
                    
                    // Add abstract html url to metatag
                    $output = $this->createTags(
                        RepositoryConst::TAG_NAME_META,
                        array( RepositoryConst::TAG_ATTR_KEY_NAME => RepositoryConst::GOOGLESCHOLAR_ABSTRACT_HTML_URL,
                               RepositoryConst::TAG_ATTR_KEY_CONTENT => $result[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_URI]));
                    $this->commonMain->addHeader($output);
                    
                    // Add to metatag list
                    $this->createMetaTagDataList(
                        $itemPubDate,
                        RepositoryConst::JUNII2_DATE_OF_ISSUED,
                        $metaTagDataList);
                }
                // For support to output meta tag 2011/12/06 A.Suzuki --end--
                // Add output ID in detail page 2009/01/15 A.Suzuki --start--
                // Add get suffixID button for detail page 2009/09/03 A.Suzuki --start--
                // Add new prefix 2013/12/25 T.Ichikawa --start--
                $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
                $pre = $repositoryHandleManager->getYHandlePrefix();
                
                if(strlen($pre) != 0){
                    // IDサーバと連携している
                    $this->IsPrefix = "true";
                }
                // Add get suffixID button for detail page 2009/09/03 A.Suzuki --end--
                
                if($this->IsPrefix == "true"){
                    $this->uri = $repositoryHandleManager->createUriForDetail($item_id, $item_no);
                    // Add get suffixID button for detail page 2009/09/03 A.Suzuki --start--
                    if($this->uri == ""){
                        if($this->Session->getParameter("id_error_flag") == "true"){
                            $this->Session->removeParameter("id_error_flag");
                            array_push($this->errMsg, $this->Session->getParameter("smartyAssign")->getLang("repository_detail_get_suffix_error"));
                        }
                    }
                    // Add get suffixID button for detail page 2009/09/03 A.Suzuki --end--
                    // Add new prefix 2013/12/25 T.Ichikawa --end--
                    // Add flash conver error 2010/02/10 A.Suzuki --start--
                    if($this->Session->getParameter("flash_error") != "" && $this->Session->getParameter("flash_error") != null){
                        if(strpos($this->Session->getParameter("flash_error"), "\"") === false){
                            array_push($this->errMsg, $this->Session->getParameter("flash_error"));
                        } else {
                            array_push($this->errMsg, $this->Session->getParameter("flash_error").$this->Session->getParameter("smartyAssign")->getLang("repository_item_flash_convert_error"));
                        }
                        $this->Session->removeParameter("flash_error");
                    }
                    // Add flash conver error 2010/02/10 A.Suzuki --end--
                }
                // Add output ID in detail page 2009/01/15 A.Suzuki --end--
                
                // Add PDF cover page 2012/06/15 A.Suzuki --start--
                if($this->Session->getParameter("cover_error") != "" && $this->Session->getParameter("cover_error") != null){
                    array_push($this->errMsg, $this->Session->getParameter("cover_error"));
                    $this->Session->removeParameter("cover_error");
                }
                // Add PDF cover page 2012/06/15 A.Suzuki --end--
                
                //
                // 中尾さん共通メソッドでアイテム情報を取得～セッションかメンバに保存
                $Result_List = array(); // DBから取得したレコードの集合
                $Error_Msg;         // エラーメッセージ
                $result = $this->getItemData($item_id, $item_no, $Result_List, $Error_Msg, false, true);
                if($result == true) {
                    
                    // キーワードの"|"を","に置換しセッションに保存 2008/10/06 A.Suzuki add Start
                    $keyword = str_replace("|", ", ", $Result_List["item"][0]["serch_key"]);
                    $keyword_english = str_replace("|", ", ", $Result_List["item"][0]["serch_key_english"]);
                    $this->Session->setParameter("keyword", $keyword);
                    $this->Session->setParameter("keyword_english", $keyword_english);
                    // キーワードの"|"を","に置換しセッションに保存 2008/10/06 A.Suzuki add End
                    
                    // Add keyword to metatag 2014/06/17 T.Ichikawa --start--
                    $keyword_meta = explode(", ", $keyword);
                    for($ii = 0; $ii < count($keyword_meta); $ii++) {
                        $this->createMetaTagDataList(
                            $keyword_meta[$ii],
                            RepositoryConst::JUNII2_SUBJECT,
                            $metaTagDataList);
                    }
                    $keyword_english_meta = explode(", ", $keyword_english);
                    for($ii = 0; $ii < count($keyword_english_meta); $ii++) {
                        $this->createMetaTagDataList(
                            $keyword_english_meta[$ii],
                            RepositoryConst::JUNII2_SUBJECT,
                            $metaTagDataList);
                    }
                    // Add external search word display 2014/06/17 T.Ichikawa --end--
                    
                    // Add matadata select language 2009/07/31 A.Suzuki --start--
                    $lang = $this->Session->getParameter("_lang");
                    
                    // Add alternative language setting 2009/08/11 A.Suzuki --start--
                    $query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
                             "WHERE param_name = 'alternative_language';";
                    $result = $this->Db->execute($query);
                    if($result === false){
                        $errMsg = $this->Db->ErrorMsg();
                        $tmpstr = sprintf("log : %s", $errMsg );
                        array_push($this->errMsg, $tmpstr);
                        $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg) {
                            return 'error_sp';
                        } else {
                            return "error";
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                    $tmp_alter_lang = explode(",", $result[0]["param_value"]);
                    $this->alter_flg = "0";
                    for($ii=0; $ii<count($tmp_alter_lang); $ii++){
                        $lang_data = explode(":", $tmp_alter_lang[$ii], 2);
                        // 現在の選択言語の他言語設定値を取得(0:表示しない, 1:表示する)
                        if($lang_data[0] == $lang){
                            $this->alter_flg = $lang_data[1];
                        }
                    }
                    // Add alternative language setting 2009/08/11 A.Suzuki --end--
                    
                    // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -start-
                    if(strlen($Result_List['item_type'][0]['icon_name']) > 0)
                    {
                        $itemTypeId = $Result_List['item'][0]['item_type_id'];
                        $result = $this->getItemTypeTableData($itemTypeId, $tmpData, $errorMsg, true);
                        if($result === false)
                        {
                            // Add smartPhone support T.Koyasu 2012/04/09 -start-
                            if($this->smartphoneFlg) {
                                return 'error_sp';
                            } else {
                                return "error";
                            }
                            // Add smartPhone support T.Koyasu 2012/04/09 -end-
                        }
                        $icon = $tmpData['item_type'][0]['icon'];
                        // create image
                        $img = imagecreatefromstring($icon);
                        if($img !== false)
                        {
                            // setting icon width
                            $Result_List['item_type'][0]['icon_width'] = imagesx($img);
                            // setting icon height
                            $Result_List['item_type'][0]['icon_height'] = imagesy($img);
                            // drop image
                            imagedestroy($img);
                        }
                    }
                    // Modify invalid javascript of icon onLoad T.Koyasu 2011/12/27 -end-
                    
                    // ファイル属性を検索
                    $this->IsFileView = "false";
                    $this->IsFileSimpleView = array();
                    $this->IsFlashView = "false";
                    // Add check view flash H.Goto 2011/01/14 --start--
                    $this->IsDownLoadFlash = array();
                    // Add check view flash H.Goto 2011/01/14 --end--
                    
                    // To get pdf reference from servers 2013/10/21 S.Suzuki --start--
                    // ベースURL取得
                    $referenceBaseURL = "";
                    $param_name = "referenceURL";
                    $file_name = array();
                    $display_name = array();
                    $extension = array();
                    
                    $result_referenceBaseURL = $this->getAdminParam($param_name, $this->referenceBaseURL, $Error_Msg);
                    
                    if($result_referenceBaseURL === false){
                        $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                        $DetailMsg = null;                              //詳細メッセージ文字列作成
                        sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                        $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                        $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                        throw $exception;
                    } 
                    // To get pdf reference from servers 2013/10/21 S.Suzuki --end--
                    
                    for($ii=0; $ii<count($Result_List['item_attr_type']); $ii++){
                        // 詳細表示用のフラグを追加 (初期値は"true":表示する)
                        if(!isset($Result_List['item_attr_type'][$ii]['display_flag']) ||
                           (isset($Result_List['item_attr_type'][$ii]['display_flag']) && $Result_List['item_attr_type'][$ii]['display_flag']!="false"))
                        {
                            $Result_List['item_attr_type'][$ii]['display_flag'] = "true";
                        }
                        
                        // ファイル、課金ファイルはスルーする
                        if($Result_List['item_attr_type'][$ii]['input_type']!="file" 
                            && $Result_List['item_attr_type'][$ii]['input_type']!="file_price"
                            && $Result_List['item_attr_type'][$ii]['input_type']!="supple")
                        {
                            // テキストエリア内の改行対応2009/02/12 A.Suzuki --start--
                            if($Result_List['item_attr_type'][$ii]['input_type']=="textarea"){
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    if(strlen($Result_List['item_attr'][$ii][$jj]['attribute_value']) > 0){
                                        $value_flg = true;
                                    } else {
                                        continue;
                                    }
                                    $str_array = explode("\n", $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                                    $Result_List['item_attr'][$ii][$jj]['attribute_value'] = $str_array;
                                    
                                    if($this->addHeaderFlag)
                                    {
                                        // Add to metatag list
                                        $this->createMetaTagDataList(
                                            $Result_List['item_attr'][$ii][$jj]['attribute_value'][0],
                                            $Result_List['item_attr_type'][$ii]['junii2_mapping'],
                                            $metaTagDataList);
                                    }
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            }
                            // Add link name 2009/03/19 A.Suzuki --start--
                            else if($Result_List['item_attr_type'][$ii]['input_type']=="link"){
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    if(strlen($Result_List['item_attr'][$ii][$jj]['attribute_value']) > 0){
                                        $value_flg = true;
                                    } else {
                                        continue;
                                    }
                                    $str_array = explode("|", $Result_List['item_attr'][$ii][$jj]['attribute_value'], 2);
                                    $Result_List['item_attr'][$ii][$jj]['attribute_value'] = $str_array;
                                    
                                    if($this->addHeaderFlag)
                                    {
                                        // Add to metatag list
                                        $this->createMetaTagDataList(
                                            $Result_List['item_attr'][$ii][$jj]['attribute_value'][0],
                                            $Result_List['item_attr_type'][$ii]['junii2_mapping'],
                                            $metaTagDataList);
                                    }
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            } else if($Result_List['item_attr_type'][$ii]['input_type']=="biblio_info"){
                                $value_flg = false;
                                if(isset($Result_List['item_attr'][$ii][0])){
                                    if( strlen($Result_List['item_attr'][$ii][0]['biblio_name']) > 0 || 
                                        strlen($Result_List['item_attr'][$ii][0]['biblio_name_english']) > 0 ||
                                        strlen($Result_List['item_attr'][$ii][0]['volume']) > 0 ||
                                        strlen($Result_List['item_attr'][$ii][0]['issue']) > 0 ||
                                        strlen($Result_List['item_attr'][$ii][0]['start_page']) > 0 ||
                                        strlen($Result_List['item_attr'][$ii][0]['end_page']) > 0 ||
                                        strlen($Result_List['item_attr'][$ii][0]['date_of_issued']) > 0)
                                    {
                                        $value_flg = true;
                                        
                                        // For support to output meta tag 2011/12/06 A.Suzuki --start--
                                        if($this->addHeaderFlag)
                                        {
                                            $tagContent = "";
                                            
                                            // Journal title in Japanese
                                            $tagContent .= $Result_List['item_attr'][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME];
                                            
                                            // Journal title in English
                                            if(strlen($Result_List['item_attr'][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME_ENGLISH]) > 0)
                                            {
                                                if(strlen($tagContent) > 0)
                                                {
                                                    $tagContent .= " = ";
                                                }
                                                $tagContent .= $Result_List['item_attr'][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_BIBLIO_NAME_ENGLISH];
                                            }
                                            
                                            // Add journal title to metatag
                                            if(strlen($tagContent) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $tagContent,
                                                    RepositoryConst::JUNII2_JTITLE,
                                                    $metaTagDataList);
                                            }
                                            
                                            // Add volume to metatag
                                            if(strlen($Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_VOLUME]) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_VOLUME],
                                                    RepositoryConst::JUNII2_VOLUME,
                                                    $metaTagDataList);
                                            }
                                            
                                            // Add issue to metatag
                                            if(strlen($Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ISSUE]) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_ISSUE],
                                                    RepositoryConst::JUNII2_ISSUE,
                                                    $metaTagDataList);
                                            }
                                            
                                            // Add first page to metatag
                                            if(strlen($Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_START_PAGE]) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_START_PAGE],
                                                    RepositoryConst::JUNII2_SPAGE,
                                                    $metaTagDataList);
                                            }
                                            
                                            // Add last page to metatag
                                            if(strlen($Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_END_PAGE]) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_END_PAGE],
                                                    RepositoryConst::JUNII2_EPAGE,
                                                    $metaTagDataList);
                                            }
                                            
                                            // Add publication date to metatag
                                            if(strlen($Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_DATE_OF_ISSUED]) > 0)
                                            {
                                                // Add to metatag list
                                                $this->createMetaTagDataList(
                                                    $Result_List["item_attr"][$ii][0][RepositoryConst::DBCOL_REPOSITORY_BIBLIO_INFO_DATE_OF_ISSUED],
                                                    RepositoryConst::JUNII2_DATE_OF_ISSUED,
                                                    $metaTagDataList);
                                            }
                                        }
                                        // For support to output meta tag 2011/12/06 A.Suzuki --end--
                                    }
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            } else if($Result_List['item_attr_type'][$ii]['input_type']=="name"){
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    if(strlen($Result_List['item_attr'][$ii][$jj]['family']) > 0 || 
                                        strlen($Result_List['item_attr'][$ii][$jj]['name']) > 0 || 
                                        strlen($Result_List['item_attr'][$ii][$jj]['e_mail_address']) > 0){
                                        $value_flg = true;
                                    } else {
                                        continue;
                                    }
                                    $author_id_text = "";
                                    $author_id_array = $NameAuthority->getExternalAuthorIdData($Result_List['item_attr'][$ii][$jj]['author_id']);
                                    for($kk=0;$kk<count($author_id_array);$kk++){
                                        if($author_id_text!=""){
                                            $author_id_text .= ", ";
                                        }
                                        $author_id_text .= $author_id_array[$kk]["prefix_name"].":".$author_id_array[$kk]["suffix"];
                                    }
                                    $Result_List['item_attr'][$ii][$jj]['author_id_text'] = $author_id_text;
                                    
                                    // For support to output meta tag 2011/12/06 A.Suzuki --start--
                                    if($this->addHeaderFlag)
                                    {
                                        $tagContent = "";
                                        if(strtolower($Result_List['item_attr_type'][$ii]['display_lang_type']) == "english")
                                        {
                                            $tagContent = $Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME];
                                            if(strlen($Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY]) > 0)
                                            {
                                                if(strlen($tagContent) > 0)
                                                {
                                                    $tagContent .= ",";
                                                }
                                                $tagContent .= $Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY];
                                            }
                                        }
                                        else
                                        {
                                            $tagContent = $Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_FAMILY];
                                            if(strlen($Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME]) > 0)
                                            {
                                                if(strlen($tagContent) > 0)
                                                {
                                                    $tagContent .= ",";
                                                }
                                                $tagContent .= $Result_List['item_attr'][$ii][$jj][RepositoryConst::DBCOL_REPOSITORY_PERSONAL_NAME_NAME];
                                            }
                                        }
                                        
                                        if(strlen($tagContent) > 0)
                                        {
                                            // Add to metatag list
                                            $this->createMetaTagDataList(
                                                $tagContent,
                                                $Result_List['item_attr_type'][$ii]['junii2_mapping'],
                                                $metaTagDataList);
                                        }
                                    }
                                    // For support to output meta tag 2011/12/06 A.Suzuki --end--
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            } else if($Result_List['item_attr_type'][$ii]['input_type']=="thumbnail"){
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    if(strlen($Result_List['item_attr'][$ii][$jj]['file_name']) > 0){
                                        $value_flg = true;
                                        break;
                                    }
                                }
                                // Modify invalid javascript of thumbnail onLoad T.Koyasu 2011/12/26 -start-
                                // get width and height of thumbnail image
                                $attrId = $Result_List['item_attr_type'][$ii]['attribute_id'];
                                //tmpDataの値が空になる場合があるので追加 T.Ichikawa 2013/9/25 --start--
                                $tmpData=$Result_List;
                                //tmpDataの値が空になる場合があるので追加 T.Ichikawa 2013/9/25 --end--
                                $result = $this->getThumbnailTableData($this->item_id, $this->item_no, $attrId, $ii, $tmpData, $errorMsg, true);
                                if($result === true && count($tmpData['item_attr'][$ii]) > 0)
                                {
                                    $thumbnailData = array();
                                    $thumbnailData = $tmpData['item_attr'][$ii];
                                    for($jj=0;$jj<count($thumbnailData);$jj++)
                                    {
                                        // create image
                                        $img = imagecreatefromstring($thumbnailData[$jj]['file']);
                                        if($img !== false)
                                        {
                                            // setting width
                                            $Result_List['item_attr'][$ii][$jj]['width'] = imagesx($img);
                                            // setting height
                                            $Result_List['item_attr'][$ii][$jj]['height'] = imagesy($img);
                                            // drop image
                                            imagedestroy($img);
                                        }
                                    }
                                }
                                // Bug fix unset before set parameter 2014/09/05 T.Ichikawa --start--
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                                // Bug fix unset before set parameter 2014/09/05 T.Ichikawa --end--
                                // Modify invalid javascript of thumbnail onLoad T.Koyasu 2011/12/26 -end-
                            // Add contents page Y.Nakao 2010/08/06 --start--
                            } else if($Result_List['item_attr_type'][$ii]['input_type']=="heading"){
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    $str = str_replace("|", "", $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                                    if(strlen($str) > 0){
                                        $value_flg = true;
                                    } else {
                                        continue;
                                    }
                                    // Bug fix send array data (originally, send string) 2014/09/05 T.Ichikawa --start--
                                    $orgValue = $Result_List['item_attr'][$ii][$jj]['attribute_value'];
                                    // Bug fix send array data (originally, send string) 2014/09/05 T.Ichikawa --end--
                                    $str_array = explode("|", $Result_List['item_attr'][$ii][$jj]['attribute_value'], 4);
                                    $Result_List['item_attr'][$ii][$jj]['attribute_value'] = $str_array;
                                    
                                    if($this->addHeaderFlag)
                                    {
                                        // Add to metatag list
                                        $this->createMetaTagDataList(
                                            $orgValue,
                                            $Result_List['item_attr_type'][$ii]['junii2_mapping'],
                                            $metaTagDataList);
                                    }
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            // Add contents page Y.Nakao 2010/08/06 --end--
                            } else {
                                $value_flg = false;
                                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                    if(strlen($Result_List['item_attr'][$ii][$jj]['attribute_value']) > 0){
                                        $value_flg = true;
                                    } else {
                                        continue;
                                    }
                                    
                                    if($this->addHeaderFlag)
                                    {
                                        // Add to metatag list
                                        $this->createMetaTagDataList(
                                            $Result_List['item_attr'][$ii][$jj]['attribute_value'],
                                            $Result_List['item_attr_type'][$ii]['junii2_mapping'],
                                            $metaTagDataList);
                                    }
                                }
                                if($value_flg === false){
                                    unset($Result_List['item_attr_type'][$ii]);
                                }
                            }
                            // Add link name 2009/03/19 A.Suzuki --end--
                            // テキストエリア内の改行対応 2009/02/12 A.Suzuki --end--
                            
                            // 他言語表示値チェック
                            if($this->alter_flg == "1"){
                                $Result_List['item_attr_type'][$ii]['display_flag'] = "true";
                            }
                            else if(count($Result_List['item_attr'][$ii])>0 
                                     && isset($Result_List['item_attr_type'][$ii]['display_flag'])
                                     && $Result_List['item_attr_type'][$ii]['display_flag'] != "false")
                            {
                                // メタデータの言語設定が現在の選択言語と異なる場合
                                if($Result_List['item_attr_type'][$ii]['display_lang_type'] != "" && $Result_List['item_attr_type'][$ii]['display_lang_type'] != $lang){
                                    // そのデータは表示しない
                                    $Result_List['item_attr_type'][$ii]['display_flag'] = "false";
                                } else if($Result_List['item_attr_type'][$ii]['display_lang_type'] == $lang || $Result_List['item_attr_type'][$ii]['display_lang_type'] == "" ) {
                                    $Result_List['item_attr_type'][$ii]['display_flag'] = "true";
                                }
                            }
                            else
                            {
                                $Result_List['item_attr_type'][$ii]['display_flag'] = "false";
                            }
                        }
                        else if($Result_List['item_attr_type'][$ii]['input_type']=="supple")
                        {
                            $this->IsSupple = true;
                            
                            for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){
                                $Result_List['item_attr'][$ii][$jj]['mime_type'] = $this->getFileIconID($Result_List['item_attr'][$ii][$jj]['mime_type']);
                                if($supple_weko_url != "" && $Result_List['item_attr'][$ii][$jj]['file_id'] != "")
                                {
                                    $Result_List['item_attr'][$ii][$jj]['download_url'] = $supple_weko_url."/?action=repository_uri&item_id=".
                                                                                          $Result_List['item_attr'][$ii][$jj]['supple_weko_item_id'].
                                                                                          "&file_id=".$Result_List['item_attr'][$ii][$jj]['file_id'];
                                }
                                else
                                {
                                    $Result_List['item_attr'][$ii][$jj]['download_url'] = "";
                                }
                            }
                        }
                        else if( $Result_List['item_attr_type'][$ii]['input_type'] == "file" || 
                                 $Result_List['item_attr_type'][$ii]['input_type'] == "file_price")
                        {
                            // TODO
                            // Fix change file download action 2013/5/9 Y.Nakao --start--
                            // exists file
                            $this->IsFile = "true";
                            $this->existImageFile = false;
                            $this->isShowThumbnail[$ii] = 'false';
                            $this->flash_image_num[$ii] = 0;
                            for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++)
                            {
                                $Result_List['item_attr'][$ii][$jj] = $this->setFileData($Result_List['item_attr_type'][$ii], $Result_List['item_attr'][$ii][$jj], $ii, $jj, $metaTagDataList);
                                
                                // To get pdf reference from servers 2013/10/21 S.Suzuki --start--
                                if (strlen($this->referenceBaseURL) > 0 && $this->fromWorkflowFlg_ != "false"){
                                    if ($Result_List['item_attr'][$ii][$jj]['extension'] == "pdf") {
                                        $this->fileURL[]  = $this->referenceBaseURL . basename($Result_List['item_attr'][$ii][$jj]['file_name'], ".pdf");
                                        if (isset($Result_List['item_attr'][$ii][$jj]['display_name']) &&
                                            strlen($Result_List['item_attr'][$ii][$jj]['display_name']) > 0) {
                                            $this->fileName[] = $Result_List['item_attr'][$ii][$jj]['display_name'];
                                        } else {
                                            $this->fileName[] = basename($Result_List['item_attr'][$ii][$jj]['file_name'], ".pdf");
                                        }
                                    }
                                }
                                // To get pdf reference from servers 2013/10/21 S.Suzuki --end--
                            }
                            // Fix change file download action 2013/5/9 Y.Nakao --end--
                        }
                    }
                    // Add matadata select language 2009/07/31 A.Suzuki --end--
                    // Add item type multi-language 2013/07/25 K.Matsuo --start--
                    $this->setItemtypeNameMultiLanguage($Result_List['item'][0]['item_type_id'], $Result_List['item_attr_type']);
                    // Add item type multi-language 2013/07/25 K.Matsuo --end--
                    // Add external search word display 2014/05/23 T.Ichikawa --start--
                    $this->setRefererWordDisplay();
                    // Add to metatag list
                    for($ii = 0; $ii < count($this->externalSearchWord); $ii++) {
                        $this->createMetaTagDataList(
                            $this->externalSearchWord[$ii]["searchValue"],
                            RepositoryConst::JUNII2_SUBJECT,
                            $metaTagDataList);
                    }
                    // Add external search word display 2014/05/23 T.Ichikawa --end--
                                
                    // Add for JaLC DOI R.Matsuura 2014/06/13 --start--
                    $this->getSelfDoiUri();
                    // Add for JaLC DOI R.Matsuura 2014/06/13 --end--
                    
                    $this->Session->setParameter("item_info", $Result_List);
                } else {
                    array_push($this->errMsg, $Error_Msg);
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    if ( $result === false ) {
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        throw $exception;
                    }
                //  return 'error';
                }

                // Add log common action Y.Nakao 2010/03/05 --start--
                $this->entryLog(3, $this->item_id, $this->item_no, "", "", "");
                // Add log common action Y.Nakao 2010/03/05 --end--
                
                // アイテムの所属インデックスを検索
                //$result = $this->Db->selectExecute("repository_position_index", array('item_id' => $item_id ));
                $query = "SELECT * ".
                     "FROM ". DATABASE_PREFIX ."repository_position_index ".    
                     "WHERE item_id = ? AND ".  // Item_ID
                     "item_no = ? AND ".        // Item_No 
                     "is_delete = ?; ";         // 削除されていない
                $params = null;
                // $queryの?を置き換える配列
                $params[] = $item_id;
                $params[] = $item_no;
                $params[] = 0;
                // SELECT実行
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $Error_Msg = $this->Db->ErrorMsg();
                    array_push($this->errMsg, $Error_Msg);
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    if ( $result === false ) {
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        throw $exception;
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -start-
                    if($this->smartphoneFlg) {
                        return 'error_sp';
                    } else {
                        return "error";
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -end-
                }
                $indexAuthorityManager = new RepositoryIndexAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
                $indice = array();
                for($ii=0; $ii<count($result); $ii++) {
                    $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_index ". 
                         "WHERE index_id = ? AND ". // Item_ID
                         "is_delete = ?; ";         // 削除されていない
                    $params = null;
                    // $queryの?を置き換える配列
                    $params[] = $result[$ii]['index_id'];
                    $params[] = 0;
                    // SELECT実行
                    $result_indice = $this->Db->execute($query, $params);
                    if($result_indice === false) {
                        $Error_Msg = $this->Db->ErrorMsg();
                        array_push($this->errMsg, $Error_Msg);
                        //アクション終了処理
                        $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                        if ( $result === false ) {
                            $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                            //$DetailMsg = null;                              //詳細メッセージ文字列作成
                            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                            //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                            throw $exception;
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg) {
                            return 'error_sp';
                        } else {
                            return "error";
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                    if(strlen($result_indice[0]['thumbnail'])!=0){
                        $result_indice[0]['thumbnail'] = "";
                    }
                    // Add show parent index A.Suzuki 2009/04/15 --start--
                    $index_data = null;
                    $parent_index = array();
                    $this->getParentIndex($result_indice[0]['parent_index_id'], $parent_index);
                    // Add check closed index 2009/12/17 Y.Nakao --start--
                    // Add config management authority 2010/02/23 Y.Nakao --start--
                    // if ($user_auth_id >= _AUTH_MODERATE) {
                    if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) {
                    // Add config management authority 2010/02/23 Y.Nakao --end--
                        $result_indice[0]['pub_status'] = "1";
                    } else {
                        $pub_index_id = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $result_indice[0]["index_id"]);
                        if(count($pub_index_id) > 0){
                            // pub index
                            $result_indice[0]['pub_status'] = "1";
                        } else if($Result_List["item"][0]["ins_user_id"] == $user_id){
                            // insert user
                            $result_indice[0]['pub_status'] = "0";
                        } else {
                            // close index
                            continue;
                        }
                    }
                    for($jj=0; $jj<count($parent_index); $jj++){
                        // Add config management authority 2010/02/23 Y.Nakao --start--
                        // if ($user_auth_id >= _AUTH_MODERATE) {
                        $pub_index_id = $indexAuthorityManager->getPublicIndex(false, $this->repository_admin_base, $this->repository_admin_room, $parent_index[$jj]["index_id"]);
                        if ($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) {
                        // Add config management authority 2010/02/23 Y.Nakao --end--
                            $parent_index[$jj]['pub_status'] = "1";
                        } else if(count($pub_index_id) > 0){
                            // pub index
                            $parent_index[$jj]['pub_status'] = "1";
                        } else if($Result_List["item"][0]["ins_user_id"] == $user_id){
                            // insert user
                            $parent_index[$jj]['pub_status'] = "0";
                        } else {
                            // close index
                            continue;
                        }
                    }
                    // Add check closed index 2009/12/17 Y.Nakao --end--
                    $index_data = array($result_indice[0], $parent_index);
                    array_push($indice, $index_data);
                    // Add show parent index A.Suzuki 2009/04/15 --end--
                }
                $this->Session->setParameter("position_index", $indice);
                
                //
                // アイテムのリンク情報取得
                //$result = $this->Db->selectExecute("repository_reference", array('org_reference_item_id' => $item_id ));
                $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_reference ". 
                         "WHERE org_reference_item_id = ? AND ".    // Item_ID
                         "org_reference_item_no = ? AND ".      // Item_No 
                         "is_delete = ?; ";         // 削除されていない
                $params = null;
                // $queryの?を置き換える配列
                $params[] = $item_id;
                $params[] = $item_no;
                $params[] = 0;
                // SELECT実行
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    $Error_Msg = $this->Db->ErrorMsg();
                    array_push($this->errMsg, $Error_Msg);
                    //アクション終了処理
                    $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                    if ( $result === false ) {
                        $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                        //$DetailMsg = null;                              //詳細メッセージ文字列作成
                        //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                        //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                        throw $exception;
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -start-
                    if($this->smartphoneFlg) {
                        return 'error_sp';
                    } else {
                        return "error";
                    }
                    // Add smartPhone support T.Koyasu 2012/04/09 -end-
                }
                $link_items = array();
                for($ii=0; $ii<count($result); $ii++) {
                    //$result_link = $this->Db->selectExecute("repository_item", array('item_id' => $result[$ii]['dest_reference_item_id']));
                    $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_item ".  
                         "WHERE item_id = ? AND ".  // Item_ID
                         "item_no = ? AND ".        // Item_No 
                         "is_delete = ?; ";         // 削除されていない
                    $params = null;
                    // $queryの?を置き換える配列
                    $params[] = $result[$ii]['dest_reference_item_id'];
                    $params[] = $result[$ii]['org_reference_item_no'];
                    $params[] = 0;
                    // SELECT実行
                    $result_link = $this->Db->execute($query, $params);
                    if($result_link === false) {
                        $Error_Msg = $this->Db->ErrorMsg();
                        array_push($this->errMsg, $Error_Msg);
                        //アクション終了処理
                        $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                        if ( $result === false ) {
                            $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                            //$DetailMsg = null;                              //詳細メッセージ文字列作成
                            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                            //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                            throw $exception;
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -start-
                        if($this->smartphoneFlg) {
                            return 'error_sp';
                        } else {
                            return "error";
                        }
                        // Add smartPhone support T.Koyasu 2012/04/09 -end-
                    }
                    // Bug fix item link 2010/04/15 A.Suzuki --start--
                    // Exclude deleted item
                    if(count($result_link)>0){
                        // WEKO admin or referenced item's owner has access right.
                        if(($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) ||($result_link[0]["ins_user_id"]===$user_id)){
                            array_push($link_items, $result_link[0]);
                        }
                        // Check referenced item public status
                        else if($itemAuthorityManager->checkItemPublicFlg($result_link[0]["item_id"], $result_link[0]["item_no"], $this->repository_admin_base, $this->repository_admin_room)){
                            array_push($link_items, $result_link[0]);
                        }
                    }
                    // Bug fix item link 2010/04/15 A.Suzuki --end--
                }
                $this->Session->setParameter("reference", $link_items);
                
                // Add usagestatistics link 2012/08/07 A.Suzuki --start--
                // Check this item's record at usagestatistics table
                $this->isDispUsageLink = $this->RepositoryUsagestatistics->checkUsageStatisticsRecords($item_id, $item_no);
                // Add usagestatistics link 2012/08/07 A.Suzuki --end--
            }
            
            // 画面右に詳細画面を表示させるフラグ
            $this->Session->setParameter("serach_screen", "1");
            
            // 一覧画面から呼ばれた場合
            if($this->Session->getParameter("search_flg")=="true"){
                $this->Session->removeParameter("error_msg");
                //アクション終了処理
                $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
                if ( $result === false ) {
                    $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                    //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                    throw $exception;
                }
                
                // Add smartPhone support T.Koyasu 2012/04/09 -start-
                if($this->smartphoneFlg){
                    return 'golistview_sp';
                } else {
                    return 'golistview';
                }
                // Add smartPhone support T.Koyasu 2012/04/09 -end-
            }
                    
            // 後は・・・保留。案外登録者と管理者だけでOK？
//          elseif() {
//          }
            
            // Set help icon setting 2010/02/10 K.Ando --start--
            $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
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
            
            // For support to output meta tag 2011/12/06 A.Suzuki --start--
            if($this->addHeaderFlag)
            {
                // Create metatag
                $metatagList = $this->createMetaTagByList($metaTagDataList);
                
                // Output metatag list
                foreach($metatagList as $key => $val)
                {
                    $this->commonMain->addHeader($val);
                }
            }
            // For support to output meta tag 2011/12/06 A.Suzuki --end--
            
            // 通常の詳細表示を示す
            $this->Session->setParameter("workflow_flg", "false");
            
            
            //アクション終了処理
            $result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
            if ( $result === false ) {
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );  //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
                //$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
                throw $exception;
            }
            
            // Add smartPhone support T.Koyasu 2012/04/09 -start-
            if($this->smartphoneFlg) {
                return 'success_sp';
            } else {
                return 'success';
            }
            // Add smartPhone support T.Koyasu 2012/04/09 -end-
         } catch ( RepositoryException $Exception) {
            //エラーログ出力
            /*
            logFile(
                "SampleAction",                 //クラス名
                "execute",                      //メソッド名
                $Exception->getCode(),          //ログID
                $Exception->getMessage(),       //主メッセージ
                $Exception->getDetailMsg() );   //詳細メッセージ           
            */
            //アクション終了処理
            $this->failTrans();
            // Add smartPhone support T.Koyasu 2012/04/09 -start-
            if($this->smartphoneFlg) {
                return 'error_sp';
            } else {
                return "error";
            }
            // Add smartPhone support T.Koyasu 2012/04/09 -end-
        }

    }
    
    // Add Convert Date Format for Google Scholar 2011/12/06 A.Suzuki --start--
    /**
     * Convert Date Format for Google Scholar
     *
     * @param string $datetime format(YYYY-MM-DD hh:mm:ss)
     * @param string &$retDate format(MM/DD/YYYY)
     * @return bool  true: Success to convert
     *              false: Failed to convert
     */
    function convertDataFormatForGoogleScholar($dateTime, &$retDate){
        // ----------------------
        // Init ref argument
        // ----------------------
        $retDate = "";
        
        // ----------------------
        // Check argument
        // ----------------------
        if(strlen($dateTime) <= 0)
        {
            return false;
        }
        
        // ----------------------
        // Convert date format
        // ----------------------
        $exDateTime = explode(" ", $dateTime);
        $exDate = explode("-", $exDateTime[0], 3);
        
        // Month
        if(isset($exDate[1]) && strlen($exDate[1]) > 0)
        {
            $retDate .= $exDate[1];
            
            // Day
            if(isset($exDate[2]) && strlen($exDate[2]) > 0)
            {
                $retDate .= "/".$exDate[2];
            }
        }
        
        // Year
        if(strlen($exDate[0]) > 0)
        {
            if(strlen($retDate) > 0)
            {
                $retDate .= "/";
            }
            $retDate .= $exDate[0];
        }
        
        // Check return value
        if(strlen($retDate) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    // Add Convert Date Format for Google Scholar 2011/12/06 A.Suzuki --end--
    
    // Add Create meta tag data list 2011/12/06 A.Suzuki --start--
    /**
     * Create meta tag data list
     *
     * @param string $value
     * @param string $mapping
     * @param string &$retDataList
     * @return bool  true: Success to add value to list
     *              false: Failed to add value to list
     */
    function createMetaTagDataList($value, $mapping, &$retDataList){
        $retStatus = false;
        
        // ----------------------
        // Init ref argument
        // ----------------------
        if(!is_array($retDataList))
        {
            $retDataList = array();
            $retDataList[RepositoryConst::JUNII2_CREATOR] = array();
            $retDataList[RepositoryConst::JUNII2_PUBLISHER] = array();
            $retDataList[RepositoryConst::JUNII2_CONTRIBUTOR] = array();
            $retDataList[RepositoryConst::JUNII2_FULL_TEXT_URL] = array();
            $retDataList[RepositoryConst::JUNII2_SUBJECT] = array();
            $retDataList[RepositoryConst::JUNII2_DATE_OF_ISSUED] = "";
            $retDataList[RepositoryConst::JUNII2_JTITLE] = "";
            $retDataList[RepositoryConst::JUNII2_VOLUME] = "";
            $retDataList[RepositoryConst::JUNII2_ISSUE] = "";
            $retDataList[RepositoryConst::JUNII2_SPAGE] = "";
            $retDataList[RepositoryConst::JUNII2_EPAGE] = "";
            $retDataList[RepositoryConst::JUNII2_DOI] = "";
            $retDataList[RepositoryConst::JUNII2_ISSN] = "";
        }
        
        // ----------------------
        // Check argument
        // ----------------------
        if(strlen($value) <= 0 || strlen($mapping) <= 0)
        {
            return $retStatus;
        }
        
        // ----------------------
        // Add value to list
        // ----------------------
        if(array_key_exists($mapping, $retDataList))
        {
            if($mapping == RepositoryConst::JUNII2_DATE_OF_ISSUED)
            {
                // Convert data format
                if($this->convertDataFormatForGoogleScholar($value, $date))
                {
                    // Success to convert
                    $retDataList[$mapping] = $date;
                    $retStatus = true;
                }
            }
            else
            {
                if(is_array($retDataList[$mapping]))
                {
                    array_push($retDataList[$mapping], $value);
                    $retStatus = true;
                }
                else
                {
                    $retDataList[$mapping] = $value;
                    $retStatus = true;
                }
            }
        }
        
        return $retStatus;
    }
    
    /**
     * Create meta tag by list
     *
     * @param array $metaTagDataList
     * @return array metatag text list
     */
    function createMetaTagByList($metaTagDataList){
        $retMetaTagTextList = array();
        
        // Add metatag for Mendeley 2012/10/10 T.Koyasu -start-
        // for create authors
        $authorsList = array();
        // Add metatag for Mendeley 2012/10/10 T.Koyasu -end-
        
        // Check argument
        if(is_array($metaTagDataList))
        {
            // Loop for metaTagDataList
            foreach($metaTagDataList as $key => $val)
            {
                // Get attribute name's value
                $attrName = "";
                switch($key)
                {
                    case RepositoryConst::JUNII2_CREATOR:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_AUTHOR;
                        // Add metatag for Mendeley 2012/10/10 T.Koyasu -start-
                        // Add citation_authors meta tag for Mendeley
                        if(is_array($val)){
                            $authorsList = $val;
                        } else {
                            array_push($authorsList, $val);
                        }
                        // Add metatag for Mendeley 2012/10/10 T.Koyasu -end-
                        break;
                    case RepositoryConst::JUNII2_PUBLISHER:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_PUBLISHER;
                        break;
                    case RepositoryConst::JUNII2_CONTRIBUTOR:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_DC_CONTRIBUTOR;
                        break;
                    case RepositoryConst::JUNII2_FULL_TEXT_URL:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_FULLTEXT_HTML_URL;
                        break;
                    case RepositoryConst::JUNII2_DATE_OF_ISSUED:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_PUB_DATE;
                        break;
                    case RepositoryConst::JUNII2_JTITLE:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_JOURNAL_TITLE;
                        break;
                    case RepositoryConst::JUNII2_VOLUME:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_VOLUME;
                        break;
                    case RepositoryConst::JUNII2_ISSUE:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_ISSUE;
                        break;
                    case RepositoryConst::JUNII2_SPAGE:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_FIRSTPAGE;
                        break;
                    case RepositoryConst::JUNII2_EPAGE:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_LASTPAGE;
                        break;
                    case RepositoryConst::JUNII2_DOI:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_DOI;
                        break;
                    case RepositoryConst::JUNII2_ISSN:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_ISSN;
                        break;
                    case RepositoryConst::JUNII2_SUBJECT:
                        $attrName = RepositoryConst::GOOGLESCHOLAR_KEYWORD;
                        break;
                    default:
                        $attribute = null;
                        break;
                }
                
                if(strlen($attrName) > 0)
                {
                    if(is_array($val))
                    {
                        foreach($val as $key2 => $val2)
                        {
                            if(strlen($val2) > 0)
                            {
                                // Create metatag
                                array_push(
                                    $retMetaTagTextList,
                                    $this->createTags(
                                        RepositoryConst::TAG_NAME_META,
                                        array( RepositoryConst::TAG_ATTR_KEY_NAME => $attrName,
                                               RepositoryConst::TAG_ATTR_KEY_CONTENT => $val2 )));
                            }
                        }
                    }
                    else
                    {
                        if(strlen($val) > 0)
                        {
                            // Create metatag
                            array_push(
                                $retMetaTagTextList,
                                $this->createTags(
                                    RepositoryConst::TAG_NAME_META,
                                    array( RepositoryConst::TAG_ATTR_KEY_NAME => $attrName,
                                           RepositoryConst::TAG_ATTR_KEY_CONTENT => $val )));
                            // Add metatag for Mendeley 2012/10/10 T.Koyasu -start-
                            // Add citation_date meta tag for Mendeley
                            if($attrName == RepositoryConst::GOOGLESCHOLAR_PUB_DATE){
                                // $val is month/day/year , month/year or year
                                $exDate = explode("/", $val);
                                if(isset($exDate) && count($exDate) > 0){
                                    // last val of $exDate -> $year
                                    $year = $exDate[count($exDate) - 1];
                                    array_push(
                                        $retMetaTagTextList, 
                                        $this->createTags(
                                            RepositoryConst::TAG_NAME_META, 
                                            array(RepositoryConst::TAG_ATTR_KEY_NAME => RepositoryConst::GOOGLESCHOLAR_DATE, 
                                                  RepositoryConst::TAG_ATTR_KEY_CONTENT => $year)));
                                }
                            }
                            // Add metatag for Mendeley 2012/10/10 T.Koyasu -end-
                        }
                    }
                }
            }
            // Add metatag for Mendeley 2012/10/10 T.Koyasu -start-
            // Add citation_authors meta tag for Mendeley
            $authorsStr = "";
            for($ii=0; $ii<count($authorsList); $ii++){
                if(strlen($authorsStr) != 0){
                    $authorsStr .= "; ";
                }
                $authorsStr .= $authorsList[$ii];
            }
            if(strlen($authorsStr) > 0){
                // Create metatag
                array_push(
                    $retMetaTagTextList, 
                    $this->createTags(
                        RepositoryConst::TAG_NAME_META, 
                        array(RepositoryConst::TAG_ATTR_KEY_NAME => RepositoryConst::GOOGLESCHOLAR_AUTHORS, 
                              RepositoryConst::TAG_ATTR_KEY_CONTENT => $authorsStr)));
            }
            // Add metatag for Mendeley 2012/10/10 T.Koyasu -end-
        }
        
        return $retMetaTagTextList;
    }
    // Add Create meta tag data list 2011/12/06 A.Suzuki --end--
    
    /**
     * constructor
     * create for call of repository_common_item_detail
     *
     * @param object $session
     * @param object $db
     * @param int $itemId
     * @param int $itemNo
     * @param object $getData
     * @param object $languagesView
     * @param int $blockId
     * @param object $commonMain
     * @param boolean $workflowFlg
     * @return Repository_View_Main_Item_Detail
     */
    public function Repository_View_Main_Item_Detail($session = null, $db = null, $itemId = null, $itemNo = null, $getData = null, $languagesView = null, $blockId = null, $commonMain = null, $workflowFlg = false)
    {
        if(isset($session)){
            $this->Session = $session;
        }
        if(isset($db)){
            $this->Db = $db;
        }
        if(isset($itemId)){
            $this->item_id = $itemId;
        }
        if(isset($itemNo)){
            $this->item_no = $itemNo;
        }
        if(isset($getData)){
            $this->getData = $getData;
        }
        if(isset($languagesView)){
            $this->languagesView = $languagesView;
        }
        if(isset($blockId)){
            $this->block_id = $blockId;
        }
        if(isset($commonMain)){
            $this->commonMain = $commonMain;
        }
        if($workflowFlg){
            $this->fromWorkflowFlg_ = true;
        }
    }
    
    /**
     * get contents type
     *
     * @param string $flashDirPath
     * @param string $mimeType
     * @return string contents type of file
     */
    private function getContentsType($flashDirPath, $mimeType)
    {
        if(file_exists($flashDirPath. "/weko.swf")){
            return "flash";
        } else if (preg_match('/^audio/', $mimeType) == 1){
            return "music";
        } else {
            return "movie";
        }
    }
    
    // Fix change file download action 2013/5/9 Y.Nakao --start--
    /**
     * get file data
     *
     * @param array $attrType attr type
     * @param array $fileData file table data
     */
    private function setFileData($attrType, $fileData, $ii, $jj, &$metaTagDataList)
    {
        // if $flash_dir_path is not set, set $flash_dir_path
        $flash_dir_path = $this->getFlashFolder( $this->item_id,
                                                 $fileData["attribute_id"],
                                                 $fileData['file_no']);
        // TODO
        $fileData['flash_viewable'] = "false";
        $fileData['multimedia_viewable'] = "false";
        $fileData['price_accent'] = "";
        // Add output setting price for item detail view 2008/10/30 Y.Nakao --start--
        $fileData['group_price'] = $this->getGroupPrice($fileData);
        // Add output setting price for item detail view Y.Nakao --end--
        // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
        $fileData['flash_name'] = '';
        $fileData['cnt'] = 0;
        
        if($this->fromWorkflowFlg_ || $attrType[RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN] == 0)
        {
            // this metadata is not hidden
            $displayType = $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_TYPE];
            if($displayType == RepositoryConst::FILE_DISPLAY_TYPE_SIMPLE)
            {
                // simple list view
                $this->IsFileSimpleView[$ii] = "true";
            }
            else if($displayType == RepositoryConst::FILE_DISPLAY_TYPE_FLASH)
            {
                // flash view
                $status = $this->RepositoryValidator->checkFlashAccessStatus($fileData);
                if($status == "free" || $status == "already" || $status == "admin" || $status == "license")
                {
                    if(strlen($flash_dir_path) > 0)
                    {
                        // add flv name to Session 
                        // Fix SWF File PDF Viewer not run. Y.Nakao 2014/09/12 --start--
                        if( file_exists($flash_dir_path.'/weko1.swf') || file_exists($flash_dir_path.'/weko.swf') )
                        {
                            if( file_exists($flash_dir_path.'/weko.swf') )
                            {
                                $fileData['flash_name'] = 'weko.swf';
                            }
                            else
                            {
                                $fileData['flash_name'] = 'weko1.swf';
                            }
                            $this->IsFlashView = "true";
                            if($fileData['extension'] == "swf")
                            {
                                // upload SWF.
                                $fileData['multimedia_viewable'] = "true";
                                $this->contentsTypeList[$ii][$jj] = $this->getContentsType($flash_dir_path, $fileData['mime_type']);
                            }
                            else
                            {
                                // convert file.
                                $fileData['flash_viewable'] = "true";
                            }
                        }
                        // Fix SWF File PDF Viewer not run. Y.Nakao 2014/09/12 --end--
                        else if( file_exists($flash_dir_path. '/weko.flv') )
                        {
                            $fileData['flash_name'] = 'weko.flv';
                            $this->IsMultimediaView = "true";
                            $fileData['multimedia_viewable'] = "true";
                            $this->contentsTypeList[$ii][$jj] = $this->getContentsType($flash_dir_path, $fileData['mime_type']);
                        }
                    }
                    // Add for extended thumbnail 2014/01/08 --start--
                    else if( RepositoryCheckFileTypeUtility::isImageFile($fileData['mime_type'], $fileData['extension']) )
                    {
                        $fileData['image_viewable'] = "true";
                        $this->isShowThumbnail[$ii] = 'true';
                        $this->IsFlashView = "true";
                        $this->flash_image_num[$ii]++;
                    }
                    // Add for extended thumbnail 2014/01/08 --end--
                    else
                    {
                        // detail file
                        $this->IsFileView = "true";
                    }
                }
                else
                {
                    // detail file
                    $this->IsFileView = "true";
                }
            }
            else
            {
                // detail file
                $this->IsFileView = "true";
            }
        }
        
        // check file download status
        $fileData['access_flag'] = $this->RepositoryValidator->checkFileDownloadViewFlag($fileData['pub_date'], $this->TransStartDate);
        $fileData['openaccess_flag'] = "false";
        if($fileData['access_flag'] == Repository_Validator_DownloadCheck::ACCESS_LOGIN){
            if(strlen($this->RepositoryValidator->openAccessDate) > 0){
                $login_id = $this->Session->getParameter("_login_id");
                $user_id = $this->Session->getParameter("_user_id");
                if($user_id == "0" || strlen($login_id) == 0){
                    $fileData['openaccess_flag'] = "true";
                    $tmpOpenAcceseDate = explode("-", $this->RepositoryValidator->openAccessDate);
                    $fileData['openaccess_date_year'] = $tmpOpenAcceseDate[0];
                    $fileData['openaccess_date_month'] = $tmpOpenAcceseDate[1];
                    $fileData['openaccess_date_day'] = $tmpOpenAcceseDate[2];
                }
            }
        }
        $status = $this->RepositoryValidator->checkFileAccessStatus($fileData);
        if($fileData['access_flag'] == Repository_Validator_DownloadCheck::ACCESS_OPEN && $status == "free")
        {
            $fileData['price_accent'] = "free";
        }
        else if($status == "license")
        {
            $fileData['price_accent'] = "license";
        }
        else if($status == "false")
        {
            // IDServerと連携していないため、ダウンロード権限なし
            $fileData['price_accent'] = "";
        }
        else if($status == "admin" || $status == "login")
        {
            // display all price
            $fileData['price_accent'] = "";
            
            // link ON
            if($fileData['access_flag'] == Repository_Validator_DownloadCheck::ACCESS_CLOSE)
            {
                $fileData['access_flag'] = Repository_Validator_DownloadCheck::ACCESS_LOGIN;
            }
        }
        else if($status == "already")
        {
            // already payed
            $fileData['price_accent'] = $this->RepositoryValidator->checkPriceAccent($fileData, $status);
        }
        else
        {
            // highlight
            $fileData['price_accent'] = $this->RepositoryValidator->checkPriceAccent($fileData);
        }
        
        // set file icon.
        $fileData['mime_type'] = $this->getFileIconID($fileData['mime_type'], $fileData['extension']);
        
        if($this->addHeaderFlag)
        {
            // Add to metatag list
            $fileURL = BASE_URL."/?action=repository_uri&item_id=".$this->item_id.
                       "&file_id=".$fileData["attribute_id"].
                       "&file_no=".$fileData['file_no'];
            $this->createMetaTagDataList(
                $fileURL,
                RepositoryConst::JUNII2_FULL_TEXT_URL,
                $metaTagDataList);
        }
        
        // Add free license input line break 2012/02/02 T.Koyasu -start-
        $license_notation_array = explode("\n", $fileData['license_notation']);
        $fileData['license_notation_array'] = $license_notation_array;
        // Add free license input line break 2012/02/02 T.Koyasu -end-
        
        // Add download count 2012/08/07 A.Suzuki --start--
        // Get download count
        $fileData['cnt'] =
            $this->RepositoryUsagestatistics->getUsagesDownloadsNow(
                $fileData['item_id'],
                $fileData['item_no'],
                $fileData['attribute_id'],
                $fileData['file_no']
            );
        // Add download count 2012/08/07 A.Suzuki --end--
        
        return $fileData;
    }
    // Fix change file download action 2013/5/9 Y.Nakao --end--
    
    // Add external search word display 2014/05/23 T.Ichikawa --start--
    function setRefererWordDisplay() {
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "show_detail_tagcloudflag";
        $result = $this->dbAccess->executeQuery($query, $params);
        $this->searchWordDisplayFlg = $result[0]["param_value"];
        
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "tagcloud_max_value";
        $result = $this->dbAccess->executeQuery($query, $params);
        $max_record = intval($result[0]["param_value"]);
        
        $query = "SELECT word, count FROM ". DATABASE_PREFIX. "repository_item_external_searchword ".
                 "WHERE is_delete = ? ".
                 "AND item_id = ? ".
                 "AND item_no = ? ".
                 "ORDER BY count DESC ".
                 "LIMIT 0, ". $max_record ." ;";
        $params = array();
        $params[] = 0;
        $params[] = $this->item_id;
        $params[] = $this->item_no;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) != 0) {
            // random sort
            $word_length = count($result);
            $word_sort = array();
            for($ii = 0; $ii < $word_length; $ii++) {
                $word_sort[] = $ii;
            }
            shuffle($word_sort);
            // countの値が全部同じだった場合フラグをtrueにする
            $is_different = false;
            $check_count = $result[0]["count"];
            $max_count = 0;
            for($ii = 0; $ii < count($word_sort); $ii++) {
                if($check_count != $result[$ii]["count"]) {
                    $is_different = true;
                    break;
                }
                // 文字サイズに傾斜をつけるためcountの最大値を取得する
                if($result[$ii]["count"] > $max_count) {
                    $max_count = $result[$ii]["count"];
                }
            }
            $word_id = 0;
            for($ii = 0; $ii < count($word_sort); $ii++) {
                $word_id = $word_sort[$ii];
                $this->externalSearchWord[$ii]["searchValue"] = urldecode($result[$word_id]["word"]);
                $this->externalSearchWord[$ii]["url"] = urlencode($result[$word_id]["word"]);
                //フラグがtrueだったらフォントサイズは一定とする
                if($is_different) {
                    $this->externalSearchWord[$ii]["fontsize"] = intval(((150 - 100) * $result[$word_id]["count"] / $max_count + 100))."%";
                } else {
                    $this->externalSearchWord[$ii]["fontsize"] = "100%";
                }
            }
        }
    }
    // Add external search word display 2014/05/23 T.Ichikawa --end--
    
    // Add for JaLC DOI R.Matsuura 2014/06/13 --start--
    private function getSelfDoiUri()
    {
        $this->self_doi_uri = "";
        $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
        if($CheckDoi->getDoiStatus($this->item_id, $this->item_no) == RepositoryHandleManager::DOI_STATUS_GRANTED)
        {
            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
            $uri_jalcdoi = $repositoryHandleManager->createSelfDoiUri($this->item_id, $this->item_no, RepositoryHandleManager::ID_JALC_DOI);
            $uri_crossref = $repositoryHandleManager->createSelfDoiUri($this->item_id, $this->item_no, RepositoryHandleManager::ID_CROSS_REF_DOI);
            $uri_library_jalcdoi = $repositoryHandleManager->createSelfDoiUri($this->item_id, $this->item_no, RepositoryHandleManager::ID_LIBRARY_JALC_DOI);
            if(strlen($uri_jalcdoi) > 0 && strlen($uri_crossref) < 1 && strlen($uri_library_jalcdoi) < 1)
            {
                $this->self_doi_uri = $uri_jalcdoi;
            }
            else if(strlen($uri_crossref) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_library_jalcdoi) < 1)
            {
                $this->self_doi_uri = $uri_crossref;
            }
            else if(strlen($uri_library_jalcdoi) > 0 && strlen($uri_jalcdoi) < 1 && strlen($uri_crossref) < 1)
            {
                $this->self_doi_uri = $uri_library_jalcdoi;
            }
        }
    }
    // Add for JaLC DOI R.Matsuura 2014/06/13 --end--
}
?>