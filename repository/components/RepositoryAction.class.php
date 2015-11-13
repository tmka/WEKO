<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryAction.class.php 56715 2015-08-19 13:48:23Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR.'/modules/repository/components/FW/ActionBase.class.php';
require_once WEBAPP_DIR.'/modules/repository/components/FW/AppException.class.php';
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/JSON.php';
ini_set('include_path', WEBAPP_DIR.'/modules/repository/files/pear'. PATH_SEPARATOR . ini_get('include_path'));
include_once WEBAPP_DIR. '/modules/repository/files/pear/Date.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryConst.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAddinCaller.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryUserAuthorityManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryOutputFilter.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryExternalSearchWordManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryIndexManager.class.php';

class RepositoryException extends AppException
{
    protected   $_DetailMsg = NULL;     //詳細メッセージ

    /**
     * [[詳細メッセージ設定]]
     *
     * @access  public
     */
    function setDetailMsg( $Msg )
    {
        $this->_DetailMsg = $Msg;
    }
    /**
     * [[詳細メッセージ取得]]
     *
     * @access  public
     */
    function getDetailMsg()
    {
        return $this->_DetailMsg;
    }

}

/**
 * [[リポジトリモジュールアクション基本クラス]]
 *
 * @package  [[リポジトリ]]
 * @access    public
 */
class RepositoryAction extends ActionBase
{
    var $Db;
    var $Session;
    var $TransStartDate;

    // Add config management authority 2010/02/22 Y.Nakao --start--
    var $repository_admin_base;
    var $repository_admin_room;
    // Add config management authority 2010/02/22 Y.Nakao --end--

    // Add theme_name for image file Y.Nakao 2011/08/03 --start--
    public $wekoThemeName = 'default';
    // Add theme_name for image file Y.Nakao 2011/08/03 --end--

    // Add smart phone support T.Koyasu 2012/04/02 -start-
    protected $smartphoneFlg = false;
    // Add smart phone support T.Koyasu 2012/04/02 -end-

    // Add addin Y.Nakao 2012/07/27 --start--
    public $addin = null;
    // Add addin Y.Nakao 2012/07/27 --end--

    // Add database access class R.Matsuura 2013/11/12 --start--
    public $dbAccess = null;
    // Add database access class R.Matsuura 2013/11/12 --end--

    /**
     * [[アクション初期化処理]]
     *
     * @access  public
     */
    function initAction($isStartTrans = true)
    {
        try {
            // 基底クラス初期化処理
            $this->initialize();
            
            //トランザクション開始日時の取得
            $this->TransStartDate = $this->accessDate;
            
            // Mod for start trans twice T.Koyasu 2015/03/06 --start--
            if($isStartTrans){
                //トランザクション開始処理
                $this->Db->StartTrans();
            }
            // Mod for start trans twice T.Koyasu 2015/03/06 --end--
            
            // Add fix login data 2009/07/31 A.Suzuki --start--
            // ログイン情報復元処理
            $this->fixSessionData();
            // Add fix login data 2009/07/31 A.Suzuki --end--

            // Add config management authority 2010/02/22 Y.Nakao --start--
            $this->setConfigAuthority();
            // Add config management authority 2010/02/22 Y.Nakao --end--

            // Add theme_name for image file Y.Nakao 2011/08/03 --start--
            $this->setThemeName();
            // Add theme_name for image file Y.Nakao 2011/08/03 --end--
            if(!defined("SHIB_ENABLED")){
                define("SHIB_ENABLED", 0);
            }
            // Add shiboleth login 2009/03/17 Y.Nakao --start--
            $this->shib_login_flg = SHIB_ENABLED;
            // Add shiboleth login 2009/03/17 Y.Nakao --end--

            if(defined("_REPOSITORY_CINII"))
            {
                $this->Session->setParameter("_repository_cinii", _REPOSITORY_CINII);
            }

            // Add smart phone support T.Koyasu 2012/04/02 -start-
            if(isset($_SERVER['HTTP_USER_AGENT']))
            {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                if(preg_match('/Android..*Mobile|iPhone|IEMobile/', $userAgent) > 0){
                    $this->smartphoneFlg = true;
                }
            }
            // Add smart phone support T.Koyasu 2012/04/02 -end-

            // Add Correspond OpenDepo S.Arata 2013/12/20 --start--
            if(_REPOSITORY_SMART_PHONE_DISPLAY)
            {
                $this->smartphoneFlg = true;
            }

            // Add Correspond OpenDepo S.Arata 2013/12/20 --end--

            // Add addin Y.Nakao 2012/07/27 --start--
            if(get_class($this)!='RepositoryAction' && !isset($this->addin))
            {
                $this->addin = new RepositoryAddinCaller($this);
            }
            // Add addin Y.Nakao 2012/07/27 --end--

            // Add database access class R.Matsuura 2013/11/12 --start--
            $this->dbAccess = new RepositoryDbAccess($this->Db);
            // Add database access class R.Matsuura 2013/11/12 --end--
            
            // Add private tree K.Matsuo 2013/04/08
            
            $repositoryIndexManager = new RepositoryIndexManager($this->Session, $this->Db, $this->TransStartDate);
            $repositoryIndexManager->createPrivateTree();
            
            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }

    // Add theme_name for image file Y.Nakao 2011/08/03 --start--
    function setThemeName(){
        $blockIds = $this->getBlockPageId();
        if(!isset($blockIds) || !is_array($blockIds))
        {
            $this->wekoThemeName = 'default';
            return;
        }
        $container =& DIContainerFactory::getContainer();
        $getdata =& $container->getComponent("GetData");
        $blocks =& $getdata->getParameter("blocks");
        // when weko module uninstall, $blocks==false.
        if(!isset($blocks) || !is_array($blocks) || !isset($blocks[$blockIds['block_id']]) )
        {
            $this->wekoThemeName = 'default';
            return;
        }
        $block_obj = $blocks[$blockIds['block_id']];
        $themeName = $block_obj['theme_name'];
        if(strlen($themeName) == 0){
            $pages =& $getdata->getParameter("pages");
            $themeList = $this->Session->getParameter("_theme_list");
            $themeName = "default";
            if(isset($pages[$block_obj['page_id']]) && isset($themeList[$pages[$block_obj['page_id']]['display_position']])){
               $themeName = $themeList[$pages[$block_obj['page_id']]['display_position']];
            }
        }
        if(is_numeric(strpos($themeName, 'blue'))){
            $this->wekoThemeName = 'blue';
        } else if(is_numeric(strpos($themeName, 'green'))){
            $this->wekoThemeName = 'green';
        } else if(is_numeric(strpos($themeName, 'orange'))){
            $this->wekoThemeName = 'orange';
        } else if(is_numeric(strpos($themeName, 'orange2'))){
            $this->wekoThemeName = 'orange';
        } else if(is_numeric(strpos($themeName, 'red'))){
            $this->wekoThemeName = 'red';
        } else if(is_numeric(strpos($themeName, 'red2'))){
            $this->wekoThemeName = 'red';
        } else if(is_numeric(strpos($themeName, 'pink'))){
            $this->wekoThemeName = 'pink';
        } else if(is_numeric(strpos($themeName, 'pink2'))){
            $this->wekoThemeName = 'pink';
        } else {
            $this->wekoThemeName = 'default';
        }
    }
    // Add theme_name for image file Y.Nakao 2011/08/03 --end--


    // Add config management authority 2010/02/22 Y.Nakao --start--
    function setConfigAuthority(){
        // set authority level from config file
        $config = parse_ini_file(BASE_DIR.'/webapp/modules/repository/config/main.ini');
        if( isset($config["define:_REPOSITORY_BASE_AUTH"]) &&
            strlen($config["define:_REPOSITORY_BASE_AUTH"]) > 0 &&
            is_numeric($config["define:_REPOSITORY_BASE_AUTH"])){
            $this->repository_admin_base = intval($config["define:_REPOSITORY_BASE_AUTH"]);
        } else {
            $this->repository_admin_base = _AUTH_CHIEF;
        }
        if( isset($config["define:_REPOSITORY_ROOM_AUTH"]) &&
            strlen($config["define:_REPOSITORY_ROOM_AUTH"]) > 0 &&
            is_numeric($config["define:_REPOSITORY_ROOM_AUTH"])){
            $this->repository_admin_room = $config["define:_REPOSITORY_ROOM_AUTH"];
        } else {
            $this->repository_admin_room = _AUTH_CHIEF;
        }
        // check authority level
        if($this->repository_admin_base < _AUTH_CHIEF){
            $this->repository_admin_base = _AUTH_CHIEF;
        } else if(_AUTH_ADMIN < $this->repository_admin_base){
            $this->repository_admin_base = _AUTH_ADMIN;
        }
        if($this->repository_admin_room < _AUTH_GUEST){
            $this->repository_admin_room = _AUTH_GUEST;
        } else if(_AUTH_CHIEF < $this->repository_admin_base){
            $this->repository_admin_room = _AUTH_CHIEF;
        }
    }
    // Add config management authority 2010/02/22 Y.Nakao --end--

    /**
     * [[アクション終了処理]]
     *
     * @access  public
     */
    function exitAction()
    {
        try {
            // 基底クラス終了処理
            $this->finalize();
            
            //トランザクション終了処理
            $this->Db->CompleteTrans();

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }

    /**
     * [[トランザクション失敗処理]]
     *
     * @access  public
     */
    function failTrans()
    {
        try {
            //トランザクション失敗フラグON設定処理
            $this->Db->FailTrans();

            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }

    /**
     * [[イベントログ出力処理]]
     *
     * @access  public
     */
    function logEvent(
        $EventId,       //イベントID
        $EventMsg )     //イベントメッセージ
    {
        try {
            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }

    /**
     * [[ログファイル出力処理]]
     *
     * @access  public
     */
    function logFile(
        $ClassName,     //クラス名
        $MethodName,    //メソッド名
        $LogId,         //ログID
        $MainMsg,       //主メッセージ
        $DetailMsg )    //詳細メッセージ
    {
        try {
            return "success";
        }
        catch ( RepositoryException $exception ) {
            return "error";
        }
    }

    /**
     * [[インデックスツリー情報をSessionに設定]]
     *
     * @access  public
     */
    function setIndexTreeData2Session($edit_flg = false)
    {
        if ($edit_flg === false) {
            $query = "SELECT `index_id`, `parent_index_id`, `show_order`, `index_name` ".
                     "FROM ". DATABASE_PREFIX ."repository_index ".
                     "WHERE `public_state` = '1' AND ".
                     "`is_delete` = '0' AND ".
                     //"`pub_date` <= '".date('Y-m-d 00:00:00.000',mktime())."'; ";
                     "`pub_date` <= NOW(); ";
        } else {
            $query = "SELECT `index_id`, `parent_index_id`, `show_order`, `index_name`, ".
                     "`public_state`, `pub_date`, `mod_date`, `access_role`, `is_delete`".
                     "FROM ". DATABASE_PREFIX ."repository_index ";
        }
        $ret = $this->Db->execute($query);
        //SQLエラーの場合
        if($ret === false) {
            //SQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $errMsg = $this->Db->ErrorMsg();
            //トランザクション失敗設定
            $this->Db->failTrans();
            return false;
        }
        // Indexの削除にて、INDEXがルートのみを許容するためエラーではなくなる 2008/06/10 Y.Nakao update --start--
        /*
        //取得結果が0件の場合
        if(!(isset($ret[0]))) {
            return false;
        }
        */
        // 2008/06/10 Y.Nakao update --end--

        //結果をSessionに保存
        $this->Session->removeParameter("tree_data");
        $this->Session->setParameter("tree_data", $ret);
        //件数をSessionに保存
        $this->Session->removeParameter("tree_data_num");
        $this->Session->setParameter("tree_data_num", count($ret));
        $buf = $this->Session->getParameter("tree_data");
        $buf = $this->Session->getParameter("tree_data");
        return true;
    }

    /**
     * [[開いているノード情報をSessionに設定]]
     *
     * @access  public
     */
    function getIndexTreeData2Req($openTreeId) {
        $arOpenIndexId = array();
        $arOpenIndexId = explode(",", $openTreeId);
        $this->Session->removeParameter("open_node_index_id");
        $this->Session->setParameter("open_node_index_id", $arOpenIndexId);
    }

    /**
     * [[アイテムID,アイテム通番で指定されるアイテムのデータをすべて取得する]]
     * @access public
     * @return true:正常終了
     *          →$Result_Listにレコード
     *         false:異常終了
     *          →$Error_MsgにエラーメッセージorSessionにエラーコード
     *          →途中で落ちた場合、$Result_Listにはそれまでのデータが入っている
     */
    function getItemData(
        $Item_ID,               // アイテムIDの配列
        $Item_No,               // アイテム通番の配列
        &$Result_List,          // DBから取得したレコードの集合
        &$Error_Msg,            // エラーメッセージ
        $blob_flag=false,       // 画像データを取得するか否か(true: 取得する, false: 取得しない)
        $empty_del_flag=false)  // 空白文字を削除するかのフラグ
    {
        ///////////////////// 指定されたすべてのデータを取得 ///////////////////////////

        // アイテムIDとアイテム通番からアイテムテーブルのデータを取得
        $search_result = $this->getItemTableData($Item_ID,$Item_No,$Result_List,$Error_Msg, $empty_del_flag);
        if($search_result === false){
            return false;
        }
        // アイテムテーブルのデータからアイテムタイプIDを取得
        $item_type_id = $Result_List['item'][0]['item_type_id'];

        // アイテムタイプIDからアイテムタイプテーブルのデータを取得
        $search_result = $this->getItemTypeTableData($item_type_id,$Result_List,$Error_Msg,$blob_flag);
        if($search_result === false){
            return false;
        }

        // アイテムタイプIDからアイテム属性タイプテーブルのデータを取得
        $search_result = $this->getItemAttrTypeTableData($item_type_id,$Result_List,$Error_Msg);
        if($search_result === false){
            return false;
        }

        // 属性IDと入力形式から、属性値を取得
        for($nCnt=0;$nCnt<count($Result_List['item_attr_type']);$nCnt++){
            // アイテム属性タイプテーブルのデータから属性IDを取得
            $attr_id = $Result_List['item_attr_type'][$nCnt]['attribute_id'];
            // 入力形式を判定し、各属性値を取得
            if($Result_List['item_attr_type'][$nCnt]['input_type'] == "name"){
                // 氏名テーブルから氏名を取得
                $search_result = $this->getNameTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg, $empty_del_flag);
                if($search_result === false){
                    return false;
                }
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "thumbnail"){
                // 入力形式がサムネイル
                // サムネイルテーブルから・・・
                $search_result = $this->getThumbnailTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg,$blob_flag);
                if($search_result === false){
                    return false;
                }
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "file"){
                // 入力形式がファイル
                // ファイルテーブルのファイル名を取得
                $search_result = $this->getFileTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg,$blob_flag);
                if($search_result === false){
                    return false;
                }
            // Add biblio info 2008/08/11 Y.Nakao --start--
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "biblio_info"){
                // 入力形式が書誌情報
                // 書誌情報テーブルの情報を取得
                $search_result = $this->getBiblioInfoTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg, $empty_del_flag);
                if($search_result === false){
                    return false;
                }
            // Add biblio info 2008/08/11 Y.Nakao --end--
            // file price Y.Nakao Add 2008/08/28 --start--
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "file_price"){
                // 入力形式が課金ファイル
                // ファイル情報と課金ファイルの情報を取得
                $search_result = $this->getFilePriceTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg,$blob_flag);
                if($search_result === false){
                    return false;
                }
            // file price Y.Nakao Add 2008/08/28 --end--
            // Add input type "supple" 2009/08/24 A.Suzuki --start--
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "supple"){
                // 入力形式がサプリ
                // サプリテーブルの情報を取得
                $search_result = $this->getSuppleTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg);
                if($search_result === false){
                    return false;
                }
            // Add input type "supple" 2009/08/24 A.Suzuki --end--
            // Add input type "heading" 2015/04/28 S.Suzuki --start--
            } else if($Result_List['item_attr_type'][$nCnt]['input_type'] == "heading"){
                // 入力形式が見出し
                // アイテム属性テーブルの属性値を取得
                $search_result = $this->getItemAttrTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg);
                if($search_result === false){
                    return false;
                }
            // Add input type "heading" 2015/04/28 S.Suzuki --end--
            } else {
                // 入力形式がその他
                // アイテム属性テーブルの属性値を取得
                $search_result = $this->getItemAttrTableData($Item_ID,$Item_No,$attr_id,$nCnt,$Result_List,$Error_Msg, $empty_del_flag);
                if($search_result === false){
                    return false;
                }
            }
            // Scrape attrTableData if "plural_enable" state change "enabled" to "disabled" 2013/09/09 T.Ichikawa --start--
            if($Result_List['item_attr_type'][$nCnt]['plural_enable'] != 1 && $Result_List['item_attr_type'][$nCnt]['input_type'] != "checkbox") {
                //属性オプションで複数可→不可になっていた場合、取得する値を先頭の1つだけにする
                $scrape_result = $this->scrapeTableData($Result_List['item_attr'][$nCnt]);
                if($scrape_result !== true) {
                    return false;
                }
            }
            // Scrape attrTableData if "plural_enable" state change "enabled" to "disabled" 2013/09/09 T.Ichikawa --end--
        }

        return true;

    }

    /**
     * [[アイテムIDとアイテム通番から、アイテムテーブルのデータを取得する]]
     * @access  public
     */
    function getItemTableData($Item_ID,$Item_No,&$Result_List,&$error_msg, $empty_del_flag=false)
    {
        // アイテムテーブルにあるデータを取得
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_item ".  // アイテムテーブル
                 "WHERE item_id = ? AND ".  // アイテムID
                 "item_no = ?  AND ".       // アイテム通番
                 "is_delete = ?; ";         // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result_Item_Table = $this->Db->execute($query, $params);
        if($result_Item_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // 2010/04/15 A.Suzuki --start--
        // No record is not error
        // if(count($result_Item_Table) == 0){
        //  $this->Session->setParameter("error_cord",2);
        //  return false;
        // }
        // 2010/04/15 A.Suzuki --end--

        // Add LIDO 2014/05/19 S.Suzuki --start--
        
        if ($empty_del_flag) {
            $skey = explode("|", $result_Item_Table[0]['serch_key']);
            $skey_eng = explode("|", $result_Item_Table[0]['serch_key_english']);
            $temp_key = array();
            $temp_key_eng = array();
            
            for($ii = 0; $ii < count($skey); $ii++) {
                $value = RepositoryOutputFilter::exclusiveReservedWords($skey[$ii]);
                if ($value !== '') {
                    array_push($temp_key, $value);
                }
            }
            
            $result_Item_Table[0]['serch_key'] = implode("|", $temp_key);
            
            for($ii = 0; $ii < count($skey_eng); $ii++) {
                $value_eng = RepositoryOutputFilter::exclusiveReservedWords($skey_eng[$ii]);
                if ($value_eng !== '') {
                    array_push($temp_key_eng, $value_eng);
                }
             }
            
            $result_Item_Table[0]['serch_key_english'] = implode("|", $temp_key_eng);
        }
        // Add LIDO 2014/05/19 S.Suzuki --end--
        
        // レコード格納
        $Result_List["item"] = $result_Item_Table;

        return true;
    }

    /**
     * [[アイテムタイプIDからアイテムタイプテーブルのデータを取得する]]
     * @access  public
     *
     */
    function getItemTypeTableData($item_type_id,&$Result_List,&$error_msg,$blob_flag=false)
    {
        // アイテムIDとアイテム通番が一致するアイテム属性テーブルの値をすべて取得
        $query = "SELECT * ".   // 属性ID,属性名称,入力形式,改行指定を取得
                 "FROM ". DATABASE_PREFIX ."repository_item_type ".     // アイテムタイプテーブル
                 "WHERE item_type_id = ? ".     // アイテムタイプID
                 //" AND is_delete = ? ".                   // 削除されていない
                 "order by item_type_id; ";         // 表示順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $item_type_id;
        //$params[] = 0;
        // SELECT実行
        $result_Item_Type_Table = $this->Db->execute($query, $params);
        if($result_Item_Type_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(!$blob_flag){
            $result_Item_Type_Table[0]["icon"] = "";
        }
        // レコード格納
        $Result_List["item_type"] = $result_Item_Type_Table;

        return true;
    }

    /**
     * [[アイテムタイプIDと属性IDからアイテム属性タイプテーブルのデータを取得する]]
     * @access  public
     *
     */
    function getItemAttrTypeTableData($item_type_id,&$Result_List,&$error_msg)
    {
        // アイテムIDとアイテム通番が一致するアイテム属性テーブルの値をすべて取得
        $query = "SELECT * ".   // 属性ID,属性名称,入力形式,改行指定を取得
                 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".    // アイテム属性テーブル
                 "WHERE item_type_id = ? AND ".     // アイテムタイプID
                 "is_delete = ? ".                  // 削除されていない
                 "order by show_order; ";           // 表示順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $item_type_id;
        $params[] = 0;
        // SELECT実行
        $result_Item_Attr_Type_Table = $this->Db->execute($query, $params);
        if($result_Item_Attr_Type_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List["item_attr_type"] = $result_Item_Attr_Type_Table;

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番から、アイテム属性テーブルのデータを取得する]]
     * @access  public
     *
     */
    function getItemAttrTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg, $empty_del_flag=false)
    {
        // アイテムIDとアイテム通番が一致するアイテム属性テーブルの値をすべて取得
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr ". // アイテム属性テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "is_delete = ? ".              // 削除されていない
                 "order by attribute_no; ";     // 属性通番順にソート
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_Item_Attr_Table = $this->Db->execute($query, $params);
        if($result_Item_Attr_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        
        // Add LIDO 2014/05/15 S.Suzuki --start--
        if ($empty_del_flag) {
            $tmp_item_attr = array();
            for ($ii = 0; $ii < count($result_Item_Attr_Table); $ii++) {
                $tmp = array();
                $values = explode("|", $result_Item_Attr_Table[$ii]['attribute_value']);
                
                for ($jj = 0; $jj < count($values); $jj++) {
                    $value = RepositoryOutputFilter::exclusiveReservedWords($values[$jj]);
                    if ($value !== '') {
                        array_push($tmp, $value);
                    }
                }
                
                $result_Item_Attr_Table[$ii]['attribute_value'] = implode("|", $tmp);
                if ($result_Item_Attr_Table[$ii]['attribute_value'] !== "") {
                    array_push($tmp_item_attr, $result_Item_Attr_Table[$ii]);
                }
            }
            $result_Item_Attr_Table = $tmp_item_attr;
        }
        // Add LIDO 2014/05/15 S.Suzuki --end--
        
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_Item_Attr_Table;

        return true;
    }

    /**
     * [[氏名属性の値を取得]]
     * @access public
     *
     */
    function getNameTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg, $empty_del_flag=false){
        // 氏名を取得
        $query = "SELECT * ".   // 氏名を取得
                 "FROM ". DATABASE_PREFIX ."repository_personal_name ". // 氏名テーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテム通番
                 "attribute_id = ? AND ".           // 属性ID
                 "is_delete = ? ".                  // 削除されていない
                 "order by personal_name_no; ";     // 氏名通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_Personal_Name_Table = $this->Db->execute($query, $params);
        if($result_Personal_Name_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        
        // Add LIDO 2014/05/14 S.Suzuki --start--
        if ($empty_del_flag) {
            $tmp_name = array();
            
            for ($ii = 0; $ii < count($result_Personal_Name_Table); $ii++)  {
                $result_Personal_Name_Table[$ii]['family']         = RepositoryOutputFilter::exclusiveReservedWords($result_Personal_Name_Table[$ii]['family']);
                $result_Personal_Name_Table[$ii]['name']           = RepositoryOutputFilter::exclusiveReservedWords($result_Personal_Name_Table[$ii]['name']);
                $result_Personal_Name_Table[$ii]['family_ruby']    = RepositoryOutputFilter::exclusiveReservedWords($result_Personal_Name_Table[$ii]['family_ruby']);
                $result_Personal_Name_Table[$ii]['name_ruby']      = RepositoryOutputFilter::exclusiveReservedWords($result_Personal_Name_Table[$ii]['name_ruby']);
                $result_Personal_Name_Table[$ii]['e_mail_address'] = RepositoryOutputFilter::exclusiveReservedWords($result_Personal_Name_Table[$ii]['e_mail_address']);
                
                $name = $result_Personal_Name_Table[$ii]['family'] .
                        $result_Personal_Name_Table[$ii]['name'] .
                        $result_Personal_Name_Table[$ii]['family_ruby'] .
                        $result_Personal_Name_Table[$ii]['name_ruby'] .
                        $result_Personal_Name_Table[$ii]['e_mail_address'];
                
                if ( strlen($name) > 0) {
                    array_push($tmp_name, $result_Personal_Name_Table[$ii]);
                }
            }
            $result_Personal_Name_Table = $tmp_name;
        }
        // Add LIDO 2014/05/14 S.Suzuki --end--
        
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_Personal_Name_Table;

        return true;
    }

    /**
     * [[サムネイル属性の値を取得]]
     * @access public
     *
     */
    function getThumbnailTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg,$blob_flag=false){
        // ファイル名を取得
        $query = "SELECT * ".       // ファイル名を取得
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail ". // 氏名テーブル
                 "WHERE item_id = ? AND ".  // アイテムID
                 "item_no = ? AND ".        // アイテム通番
                 "attribute_id = ? AND ".   // 属性ID
                 "is_delete = ? ".         // 削除されていない
                 "order by show_order; ";      // 表示順序でソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_Thumbnail_Table = $this->Db->execute($query, $params);
        if($result_Thumbnail_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // Add scrape thumbnail data if "plural_enable" state is "disabled" T.Ichikawa 2013/9/17 --start--
        if($Result_List['item_attr_type'][$idx]['plural_enable'] != 1) {
            $scrape_result = $this->scrapeTableData($result_Thumbnail_Table);
            if($scrape_result !== true) {
                return false;
            }
        }
        // Add scrape thumbnail data if "plural_enable" state is "disabled" T.Ichikawa 2013/9/17 --end--
        if(!$blob_flag){
            if (!isset($this->attr_list)){
                $this->attr_list = "";
            }
            
            for($nCnt=0;$nCnt<count($result_Thumbnail_Table);$nCnt++){
                $this->attr_list .= $result_Thumbnail_Table[$nCnt]['file_name'];
                $result_Thumbnail_Table[$nCnt]['file'] = "";
            }
        }
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_Thumbnail_Table;

        return true;
    }

    /**
     * [[ファイル属性の値を取得]]
     * @access public
     *
     */
    function getFileTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg,$blob_flag=false){
        // ファイル名を取得
        $query = "SELECT * ".       // ファイル名を取得
                 "FROM ". DATABASE_PREFIX ."repository_file ".  // 氏名テーブル
                 "WHERE item_id = ? AND ".  // アイテムID
                 "item_no = ? AND ".        // アイテム通番
                 "attribute_id = ? AND ".   // 属性ID
                 "is_delete = ? ".          // 削除されていない
                 "order by show_order; ";      // 表示順序でソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_File_Table = $this->Db->execute($query, $params);
        if($result_File_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --start--
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --end--

        // Add get Flash file size 2010/06/08 A.Suzuki --start--
        // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
        $flash_contents_path = $this->getFlashFolder();
        if(strlen($flash_contents_path) == 0){
            // default directory
            $flash_contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
        }
        // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--
        // Add get Flash file size 2010/06/08 A.Suzuki --end--

        // アイテム詳細表示でライセンス情報表示対応 2008/07/02 Y.Nakao --start--
        for($i=0; $i<count($result_File_Table); $i++) {
            $query = "SELECT * ".       // ファイル名を取得
                     "FROM ". DATABASE_PREFIX ."repository_license_master ".    // ライセンスマスタテーブル
                     "WHERE license_id = ?; ";  // ライセンスID
            $params = null;
            $params[] = $result_File_Table[$i]['license_id'];
            $result_License_Table = $this->Db->execute($query, $params);
            if($result_License_Table === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                return false;
            }
            if(count($result_License_Table) == 1){
                $result_File_Table[$i]['img_url'] = $result_License_Table[0]['img_url'];
                $result_File_Table[$i]['text_url'] = $result_License_Table[0]['text_url'];
            }
            // Add separate file from DB 2009/04/21 Y.Nakao --start--
            $file_path = $contents_path.DIRECTORY_SEPARATOR.
                        $result_File_Table[$i]['item_id'].'_'.
                        $result_File_Table[$i]['attribute_id'].'_'.
                        $result_File_Table[$i]['file_no'].'.'.
                        $result_File_Table[$i]['extension'];
            if(file_exists($file_path))
            {
                // get file size
                if(array_key_exists(RepositoryConst::ITEM_DATA_KEY_FILE_SIZE_FULL, $result_File_Table[$i])){
                    $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE_FULL] = $size;
                }
                $size = filesize($file_path);
                if( ($size/1000)>1 ){
                    if( ($size/1000000)>1 ){
                        $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE] = round($size/1000000, 2)."MB";
                    } else {
                        $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE] = round($size/1000, 2)."KB";
                    }
                } else {
                    $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE] = $size."Byte";
                }
            }
            else
            {
                if(array_key_exists(RepositoryConst::ITEM_DATA_KEY_FILE_SIZE_FULL, $result_File_Table[$i])){
                    $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE_FULL] = "0";
                }
                $result_File_Table[$i][RepositoryConst::ITEM_DATA_KEY_FILE_SIZE] = "0Byte";
            }
            // Add separate file from DB 2009/04/21 Y.Nakao --end--

            // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
            // Add get Flash file size 2010/06/08 A.Suzuki --start--
            $flash_contents_path = $this->getFlashFolder($result_File_Table[$i]['item_id'],
                                                          $result_File_Table[$i]['attribute_id'],
                                                          $result_File_Table[$i]['file_no']);
            if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.swf')){
                // get file size
                $flash_size = filesize($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.swf');
                if($flash_size === false){
                    $result_File_Table[$i]['flash_size'] = 0;
                } else {
                    $result_File_Table[$i]['flash_size'] = $flash_size;
                }
                // Add get page count 2011/02/07 Y.Nakao --start--
                $result_File_Table[$i]['division'] = 0;
                // Add get page count 2011/02/07 Y.Nakao --end--
            } else if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko1.swf')){
                // get file size
                $flash_size = filesize($flash_contents_path.DIRECTORY_SEPARATOR.'/weko1.swf');
                if($flash_size === false){
                    $result_File_Table[$i]['flash_size'] = 0;
                } else {
                    $result_File_Table[$i]['flash_size'] = $flash_size;
                }
                // Add get page count 2011/02/07 Y.Nakao --start--
                $result_File_Table[$i]['division'] = $this->getFlashPagecount(
                                                        $result_File_Table[$i]['item_id'],
                                                        $result_File_Table[$i]['attribute_id'],
                                                        $result_File_Table[$i]['file_no']);
                // Add get page count 2011/02/07 Y.Nakao --end--
            } else if(file_exists($flash_contents_path. '/weko.flv')){
                // Mod multimedia support 2012/10/09 T.Koyasu -start-
                // get multimedia file's size
                $flash_size = filesize($flash_contents_path. '/weko.flv');

                if($flash_size === false){
                    $result_File_Table[$i]['flash_size'] = 0;
                } else {
                    $result_File_Table[$i]['flash_size'] = $flash_size;
                }
                // Mod multimedia support 2012/10/09 T.Koyasu -end-
            } else {
                $result_File_Table[$i]['flash_size'] = 0;
            }
            // Add get Flash file size 2010/06/08 A.Suzuki --end--
            // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--

            if(!$blob_flag){
                $result_File_Table[$i]['file_prev'] = "";
            }
        }
        // アイテム詳細表示でライセンス情報表示対応 2008/07/02 Y.Nakao --end--

        // レコード格納
        $Result_List['item_attr'][$idx] = $result_File_Table;

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番から、アイテムの所属インデックスのデータを取得する]]
     * @access  public
     */
    function getItemIndexData($Item_ID,$Item_No,&$Result_List,&$error_msg)
    {
        // アイテムテーブルにあるデータを取得
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_position_index ".    // 所属インデックステーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ?  AND ".               // アイテム通番
                 "is_delete = ?; ";                 // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result_Item_Table = $this->Db->execute($query, $params);
        if($result_Item_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード数0でもOK
//      if(count($result_Item_Table) == 0){
//          $this->Session->setParameter("error_cord",2);
//          return false;
//      }
        // レコード格納
        $Result_List["position_index"] = $result_Item_Table;

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番から、アイテムの参照データを取得する]]
     * @access  public
     */
    function getItemReference($Item_ID,$Item_No,&$Result_List,&$error_msg, $empty_del_flag=false)
    {
        // アイテムテーブルにあるデータを取得
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_reference ".             // 参照テーブル
                 "WHERE org_reference_item_id = ? AND ".    // アイテムID(参照する側)
                 "org_reference_item_no = ?  AND ".         // アイテム通番(参照する側)
                 "is_delete = ?; ";                         // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result_Item_Table = $this->Db->execute($query, $params);
        if($result_Item_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード数0でもOK
 //     if(count($result_Item_Table) == 0){
 //         $this->Session->setParameter("error_cord",2);
 //         return false;
 //     }
        
        // Add LIDO 2014/05/14 S.Suzuki --start--
        if ($empty_del_flag) {
            for ($ii = 0; $ii < count($result_Item_Table); $ii++) {
                $result_Item_Table[$ii]['reference'] = RepositoryOutputFilter::exclusiveReservedWords($result_Item_Table[$ii]['reference']);
            }
        }
        // Add LIDO 2014/05/14 S.Suzuki --end--
        
        // レコード格納
        $Result_List["reference"] = $result_Item_Table;

        return true;
    }

    /**
     * [[アイテムタイプIDで指定されるアイテムを削除]]
     */
    function deleteItemOfItemType($item_type_id, $user_ID, &$Error_Msg){
        // アイテムテーブルにレコードがあるか判定
        $query = "SELECT * ".               // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item ".  // アイテムテーブル
                 "WHERE item_type_id = ? AND ".     // アイテムタイプID
                 "is_delete = ?; ";         // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $item_type_id;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        for( $ii=0; $ii<count($result); $ii++){
            $result2 = $this->deleteItemData(
                            $result[$ii]['item_id'],
                            $result[$ii]['item_no'],
                            $user_ID,
                            $Error_Msg);
            if($result2 === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();      //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムタイプIDで指定されるアイテムタイプを削除]]
     */
    function deleteItemType($item_type_id, $user_ID, &$Error_Msg){
        // アイテムタイプテーブル削除
        $result = $this->deleteItemTypeTable($item_type_id, $user_ID, $Error_Msg);
        if($result === false){
            return false;
        }
        // アイテム属性タイプ削除
        $result = $this->deleteItemAttrTypeTable($item_type_id, $user_ID, $Error_Msg);
        if($result === false){
            return false;
        }
    }

    /**
     * [[アイテムタイプIDで指定されるアイテムタイプテーブルデータを論理削除]]
     *
     */
    function deleteItemTypeTable($item_type_id, $user_ID, &$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_type ". // アイテムタイプテーブル
                 "WHERE item_type_id = ? AND ".     // アイテムタイプID
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $item_type_id;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item_type ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?,".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_type_id = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $item_type_id;          // item_type_id
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテム属性テーブルデータを削除]]
     */
    function deleteItemAttrTypeTable($item_type_id, $user_ID, &$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".    // アイテム属性タイプテーブル
                 "WHERE item_type_id = ? AND ".     // アイテムタイプID
                 "is_delete = ? ".                  // 削除されていない
                 "order by attribute_id; ";         // 属性通番順にソート
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $item_type_id;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // select,  radio, checkの場合は選択肢を削除
        for($ii=0; $ii<count($result); $ii++) {
            if( $result[$ii]['input_type'] == 'checkbox' ||
                $result[$ii]['input_type'] == 'select' ||
                $result[$ii]['input_type'] == 'radio' ){
                $query2 = "SELECT * ".      // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr_candidate ".   // アイテム属性タイプテーブル
                 "WHERE item_type_id = ? AND ".     // アイテムタイプID
                 "attribute_id = ? AND ".           // 属性ID
                 "is_delete = ? ".                  // 削除されていない
                 "order by candidate_no; ";         // 属性通番順にソート
                $params2 = null;
                // $queryの?を置き換える配列
                $params2[] = $item_type_id;
                $params2[] = $result[$ii]['attribute_id'];
                $params2[] = 0;
                // SELECT実行
                $result2 = $this->Db->execute($query2, $params2);
                if($result2 === false){
                    $error_msg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_cord",-1);
                    return false;
                }
                if(count($result2) > 0){
                    //レコードがあるため、削除実行
                    $query2 = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_candidate ".
                             "SET del_user_id = ?, ".
                             "del_date = ?, ".
                             "mod_user_id = ?, ".
                             "mod_date = ?, ".
                             "is_delete = ? ".
                             "WHERE item_type_id = ? AND ".
                             "attribute_id = ?; ";
                    $params2 = null;
                    $params2[] = $user_ID;              // del_user_id
                    $params2[] = $this->TransStartDate; // del_date
                    $params2[] = $user_ID;              // mod_user_id
                    $params2[] = $this->TransStartDate; // mod_date
                    $params2[] = 1;                     // is_delete
                    $params2[] = $item_type_id;         // item_type_id
                    $params2[] = $result[$ii]['attribute_id'];      // attribute_id
                    //UPDATE実行
                    $result2 = $this->Db->execute($query2,$params2);
                    if($result2 === false){
                        //必要であればSQLエラー番号・メッセージ取得
                        $errNo = $this->Db->ErrorNo();
                        $Error_Msg = $this->Db->ErrorMsg();
                        $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                        return false;
                    }
                }
            }
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr_type ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_type_id = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $item_type_id;          // item_type_id
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテムを削除]]
     */
    function deleteItemData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // アイテムテーブル削除
        $result = $this->deleteItemTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // アイテム属性削除
        $result = $this->deleteItemAttrTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // 氏名削除
        $result = $this->deletePersonalNameTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // サムネイル削除
        $result = $this->deleteThumbnailTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // ファイル削除
        $result = $this->deleteFileTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // Add biblio info 2008/08/11 Y.Nakao --start--
        // 書誌情報削除
        $result = $this->deleteBiblioInfoTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        // Add biblio info 2008/08/11 Y.Nakao --end--

        // 添付ファイル削除
        $result = $this->deleteAttachedFileTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // 所属インデックステーブルデータ削除
        $result = $this->deletePositionIndexTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // 参照テーブルデータ削除
        $result = $this->deleteReference($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }

        // 新着情報削除
        $result = $this->deleteWhatsnew($Item_ID);
        if($result === false){
            return false;
        }

        // サプリテーブルデータ削除
        $result = $this->deleteSuppleInfoTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        
        // サフィックステーブルデータ削除
        $result = $this->deleteItemSuffix($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        
        // 検索テーブルデータ削除
        require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
        $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
        $searchTableProcessing->deleteDataFromSearchTableByItemId($Item_ID, $Item_No);
        
        // DOI付与状態削除
        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db, $this->TransStartDate);
        $repositoryHandleManager->deleteDoiStatus($Item_ID, $Item_No);
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテムの属性のみを削除]]
     */
    function deleteItemAttrData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // アイテム属性削除
        $result = $this->deleteItemAttrTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        // 氏名削除
        $result = $this->deletePersonalNameTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
//      // サムネイル削除
//      $result = $this->deleteThumbnailTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
//      if($result === false){
//          return false;
//      }
//      // ファイル削除
//      $result = $this->deleteFileTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
//          if($result === false){
//          return false;
//      }
//      // 添付ファイル削除
//      $result = $this->deleteAttachedFileTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
//          if($result === false){
//          return false;
//      }
        // delete biblio_info
        $result = $this->deleteBiblioInfoTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテムの属性をファイル＆サムネイル以外削除]]
     */
    function deleteItemAttrDataWithoutFile($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // アイテム属性削除
        $result = $this->deleteItemAttrTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
        // 氏名削除
        $result = $this->deletePersonalNameTableData($Item_ID,$Item_No,$user_ID,$Error_Msg);
        if($result === false){
            return false;
        }
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテムテーブルデータを削除]]
     *
     */
    function deleteItemTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item ".  // アイテム属性テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ? AND ".            // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?,".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるアイテム属性テーブルデータを削除]]
     */
    function deleteItemAttrTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr ". // アイテム属性テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ? AND ".            // アイテム通番
                 "is_delete = ? ".              // 削除されていない
                 "order by attribute_no; ";     // 属性通番順にソート
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;

    }

    /**
     * [[アイテムIDとアイテム通番と属性IDにて指定されるアイテム属性テーブルデータを削除]]
     */
    function AttrTableDataWithAttrId($Item_ID,$Item_No,$Attribute_ID,$user_ID,&$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr ". // アイテム属性テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ? AND ".            // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "is_delete = ? ".              // 削除されていない
                 "order by attribute_no; ";     // 属性通番順にソート
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND".
                     "attribute_id = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $params[] = $Attribute_ID;
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番と属性IDと属性通番にて指定されるアイテム属性テーブルデータを削除]]
     */
    function AttrTableDataWithAttrNo($Item_ID,$Item_No,$Attribute_ID,$Attribute_No,$user_ID,&$Error_Msg){
        // アイテム属性テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_item_attr ". // アイテム属性テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ? AND ".            // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "attribute_no = ? AND ".       // 属性通番
                 "is_delete = ? ".              // 削除されていない
                 "order by attribute_no; ";     // 属性通番順にソート
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        $params[] = $Attribute_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND".
                     "attribute_id = ? AND ".
                     "attribute_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $params[] = $Attribute_ID;
            $params[] = $Attribute_No;
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定される氏名テーブルデータ削除]]
     */
    function deletePersonalNameTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 氏名テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_personal_name ". //氏名テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_personal_name ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;

    }

    /**
     * [[アイテムIDとアイテム通番と属性IDと氏名通番にて指定される氏名テーブルデータ削除]]
     */
    function deletePersonalNameTableDataWithAttrNo($Item_ID,$Item_No,$Attribute_ID,$Name_No,$user_ID,&$Error_Msg){
        // 氏名テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_personal_name ". //氏名テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "personal_name_no = ? AND ".   // 氏名通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        $params[] = $Name_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_personal_name ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "personal_name_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $params[] = $Attribute_ID;
            $params[] = $Name_No;
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるサムネイルテーブルデータ削除]]
     */
    function deleteThumbnailTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // サムネイルテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail ". //サムネイルテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_thumbnail ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;

    }

    /**
     * [[アイテムIDとアイテム通番と属性IDとファイル通番にて指定されるサムネイルテーブルデータ削除]]
     */
    function deleteThumbnailTableDataWithAttrNo($Item_ID,$Item_No,$Attribute_ID,$File_No,$user_ID,&$Error_Msg){
        // サムネイルテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail ". //サムネイルテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "file_no = ? AND ".            // ファイル通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        $params[] = $File_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_thumbnail ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "file_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $params[] = $Attribute_ID;
            $params[] = $File_No;
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();                          //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるファイルテーブルデータ削除]]
     *
     */
    function deleteFileTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // ファイルテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_file ".  //ファイルテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $select_result = $this->Db->execute($query, $params);
        if($select_result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }


        if(count($select_result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_file ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
            // 課金情報があるか検索
            $query = "SELECT * ".       // 属性値
                     "FROM ". DATABASE_PREFIX ."repository_file_price ".    //ファイルテーブル
                     "WHERE item_id = ? AND ".      // アイテムID
                     "item_no = ?  AND ".           // アイテム通番
                     "is_delete = ?; ";             // 削除されていない
            $params = null;
            // $queryの?を置き換える配列
            $params[] = $Item_ID;
            $params[] = $Item_No;
            $params[] = 0;
            // SELECT実行
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                return false;
            }
            if(count($result) > 0){
                $query = "UPDATE ". DATABASE_PREFIX ."repository_file_price ".
                         "SET del_user_id = ?, ".
                         "del_date = ?, ".
                         "mod_user_id = ?, ".
                         "mod_date = ?, ".
                         "is_delete = ? ".
                         "WHERE item_id = ? AND ".
                         "item_no = ?; ";
                $params = null;
                $params[] = $user_ID;               // del_user_id
                $params[] = $this->TransStartDate;  // del_date
                $params[] = $user_ID;               // mod_user_id
                $params[] = $this->TransStartDate;  // mod_date
                $params[] = 1;                      // is_delete
                $params[] = $Item_ID;               // item_id
                $params[] = $Item_No;               // item_no
                //UPDATE実行
                $result = $this->Db->execute($query,$params);
                if($result === false){
                    //必要であればSQLエラー番号・メッセージ取得
                    $errNo = $this->Db->ErrorNo();
                    $Error_Msg = $this->Db->ErrorMsg();
                    //エラー処理を行う
                    //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                    //$DetailMsg = null;                              //詳細メッセージ文字列作成
                    //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                    //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                    $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                    //throw $exception;
                    return false;
                }
            }

            //実ファイルを削除する A.Jin --start--
            for($index=0; $index<count($select_result);$index++){
                $this->removePhysicalFileAndFlashDirectory($select_result[$index][RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],       //item_id
                                                           $select_result[$index][RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],  //attribute_id
                                                           $select_result[$index][RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO]);      //file_no
            }
            //実ファイルを削除する A.Jin --end--
        }

        return true;

    }

    /**
     * [[アイテムIDとアイテム通番と属性IDとファイル通番にて指定されるファイルテーブルデータ削除]]
     *
     */
    function deleteFileTableDataWithAttrNo($Item_ID,$Item_No,$Attribute_ID,$File_No,$user_ID,&$Error_Msg){
        // ファイルテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_file ".  //ファイルテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "attribute_id = ? AND ".       // 属性ID
                 "file_no = ? AND ".            // ファイル通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        $params[] = $File_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_file ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "file_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $params[] = $Attribute_ID;
            $params[] = $File_No;
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
        }
        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定される添付ファイルデータ削除]]
     */
    function deleteAttachedFileTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 添付ファイルテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_attached_file ". // 添付ファイルテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_attached_file ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定される所属インデックスデータ削除]]
     */
    function deletePositionIndexTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 所属インデックステーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_position_index ".    // 所属インデックステーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_position_index ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定される参照テーブルデータ削除]]
     */
    function deleteReference($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 参照テーブルにレコードがあるか判定
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_reference ". // reference table
                 "WHERE org_reference_item_id = ? AND ".
                 "org_reference_item_no = ?  AND ".
                 "is_delete = ?; ";
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // sql execute
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            // delete action
            $query = "UPDATE ". DATABASE_PREFIX ."repository_reference ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE org_reference_item_id = ? AND ".
                     "org_reference_item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            $result = $this->Db->execute($query,$params);
            if($result === false){
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans();
                return false;
            }
        }
        return true;
    }
    
    /**
     * [[アイテムIDとアイテム通番にて指定されるサフィックスを削除]]
     */
    private function deleteItemSuffix($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 指定されたアイテムのサフィックスを削除
        $query = "UPDATE ". DATABASE_PREFIX ."repository_suffix ".
                 "SET mod_user_id = ?, ".
                 "del_user_id = ?, ".
                 "mod_date = ?, ".
                 "del_date = ?, ".
                 "is_delete = ? ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ;";
        $params = array();
        $params[] = $user_ID;               //mod_user_id
        $params[] = $user_ID;               //del_user_id
        $params[] = $this->TransStartDate;  //mod_date
        $params[] = $this->TransStartDate;  //del_date
        $params[] = 1;                      //is_delete
        $params[] = $Item_ID;               //item_id
        $params[] = $Item_No;               //item_no

        $result = $this->Db->execute($query, $params);
        if($result === false){
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans();
            return false;
        }        
    }

    // 2008/03/17 川崎追加分 アイテム属性関連挿入＆更新用共通メソッド

    /**
     * [[アイテム属性レコードを挿入]]
     */
    function insertItemAttr($params,&$Error_Msg){
        // 通常のアイテム属性入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr ".
                     "(item_id, item_no, attribute_id, attribute_no, ".
                     "attribute_value, item_type_id, ins_user_id, ".
                     "mod_user_id, del_user_id, ins_date, mod_date, ".
                     "del_date, is_delete) ".
                        "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[氏名レコードを挿入]]
     */
    function insertPersonalName($params,&$Error_Msg){
        // 氏名属性入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_personal_name ".
//                   "(item_id, item_no, attribute_id, personal_name_no, ".
//                   "family, name, family_ruby, name_ruby, e_mail_address, item_type_id, author_id, ".
//                   "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, ".
//                   "del_date, is_delete) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[ファイルレコードを挿入]]
     */
    function insertFile($params,&$Error_Msg){
        // ファイル入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_file ".
//                  "(item_id, item_no, attribute_id, file_no, ".
//                  "file_name, mime_type, extension, file, license_id, license_notation, pub_date, item_type_id, ".
//                  "browsing_flag, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, ".
//                  "del_date, is_delete) ".
                    "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    // Add file INSERT ON DUPLICATE KEY UPDATE K.Matsuo 2013/10/09 --start--
    /**
     * [[ファイルレコードを挿入]]
     */
    function insertOrUpdateFile($params,&$Error_Msg){
        // ファイル入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_file ".
                 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
        $query .= " ON DUPLICATE KEY UPDATE  ".
                  " display_name=VALUES(`display_name`), ".
                  " display_type=VALUES(`display_type`), show_order=VALUES(`show_order`), ".
                  " prev_id=VALUES(`prev_id`), file_prev=VALUES(`file_prev`), ".
                  " file_prev_name=VALUES(`file_prev_name`), ".
                  " license_id=VALUES(`license_id`), license_notation=VALUES(`license_notation`), ".
                  " pub_date=VALUES(`pub_date`), item_type_id=VALUES(`item_type_id`), ".
                  " browsing_flag=VALUES(`browsing_flag`), cover_created_flag=0, mod_user_id=VALUES(`mod_user_id`), ".
                  " del_user_id=VALUES(`del_user_id`), mod_date=VALUES(`mod_date`), ".
                  " del_date=VALUES(`del_date`), is_delete=VALUES(`is_delete`); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }
    // Add file INSERT ON DUPLICATE KEY UPDATE K.Matsuo 2013/10/09 --end--

    /**
     * [[サムネイルレコードを挿入]]
     */
    function insertThumbnail($params,&$Error_Msg){
        // サムネイル用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_thumbnail ".
                     "(item_id, item_no, attribute_id, file_no, ".
                     "file_name, show_order, mime_type, extension, file, item_type_id, ".
                     "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, ".
                     "del_date, is_delete) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[サムネイルレコードを挿入]]
     */
    function insertOrUpdateThumbnail($params,&$Error_Msg){
        // サムネイル用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_thumbnail ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
        $query .= " ON DUPLICATE KEY UPDATE show_order=VALUES(`show_order`), ".
                  " mime_type=VALUES(`mime_type`), item_type_id=VALUES(`item_type_id`), ".
                  " file=VALUES(`file`), item_type_id=VALUES(`item_type_id`), ".
                  " mod_user_id=VALUES(`mod_user_id`), ".
                  " del_user_id=VALUES(`del_user_id`), mod_date=VALUES(`mod_date`), ".
                  " del_date=VALUES(`del_date`), is_delete=VALUES(`is_delete`); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            print_r($Error_Msg);
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }
    // Add biblio info 2008/08/11 Y.Nakao --start--
    /**
     * [[書誌情報レコードを挿入]]
     */
    function insertBiblioInfo($params,&$Error_Msg){
        // 書誌情報属性入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_biblio_info ".
//                   "(item_id, item_no, attribute_id, biblio_no, ".
//                   "biblio_name, biblio_name_english, volume, issue, start_page, end_page, date_of_issued, ".
//                   "item_type_id, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, ".
//                   "del_date, is_delete) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }
    // Add biblio info 2008/08/11 Y.Nakao --end--

    /**
     * [[アイテム属性レコードを更新]]
     */
    function updateItemAttr($params,&$Error_Msg){
        // 通常のアイテム属性入力用クエリー (更新)
        $query = "UPDATE ". DATABASE_PREFIX ."repository_item_attr ".
                     "SET attribute_value = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "attribute_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[氏名レコードを更新]]
     */
    function updatePersonalName($params,&$Error_Msg){
        // 氏名属性入力用クエリー (更新)
        $query = "UPDATE ". DATABASE_PREFIX ."repository_personal_name ".
                     "SET family = ?, ".
                     "name = ?, ".
                     "family_ruby = ?, ".
                     "name_ruby = ?, ".
                     "e_mail_address = ?, ".
                     "author_id = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "personal_name_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[ファイルレコードを更新]]
     */
    function updateFile($params,&$Error_Msg){
        // ファイル入力用クエリー (更新)
        // ※更新対象はライセンスとエンバーゴ、更新者、更新日
        $query = "UPDATE ". DATABASE_PREFIX ."repository_file ".
                     "SET file_no = ?, ".
                     "license_id = ?, ".
                     "license_notation = ?, ".
                     "pub_date = ?, ".
                     "flash_pub_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "file_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    /**
     * [[サムネイルレコードを更新]]
     */
    function updateThumbnail($params,&$Error_Msg){
        // サムネイル用クエリー (挿入)
        // ※更新対象は・・・なし？
        $query = "UPDATE ". DATABASE_PREFIX ."repository_thumbnail ".
                     "SET file_no = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "file_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    // Add biblio info 2008/08/11 Y.Nakao --start--
    /**
     * [[書誌情報レコードを更新]]
     */
    function updateBilioInfo($params,&$Error_Msg){
        // 書誌情報属性入力用クエリー (更新)
        $query = "UPDATE ". DATABASE_PREFIX ."repository_biblio_info ".
                     "SET biblio_name = ?, ".
                     "biblio_name_english = ?, ".
                     "volume = ?, ".
                     "issue = ?, ".
                     "start_page = ?, ".
                     "end_page = ?, ".
                     "date_of_issued = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "del_user_id = '', ".
                     "del_date = '', ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "biblio_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }
    // Add biblio info 2008/08/11 Y.Nakao --end--

    /**
     * [[アイテム属性のアイテム通番をカウント]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function countItemAttrNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $params = array(
                'item_id' => $Item_ID,
                'item_no' => $Item_No,
                'attribute_id' => $Attribute_ID
        );
        return $this->Db->countExecute('repository_item_attr', $params);
    }

    /**
     * [[氏名レコード通番をカウント]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function countPersonalNameNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $params = array(
                'item_id' => $Item_ID,
                'item_no' => $Item_No,
                'attribute_id' => $Attribute_ID
        );
        return $this->Db->countExecute('repository_personal_name', $params);
    }

    /**
     * [[ファイルレコード通番をカウント]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function countFileNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $params = array(
                'item_id' => $Item_ID,
                'item_no' => $Item_No,
                'attribute_id' => $Attribute_ID
        );
        return $this->Db->countExecute('repository_file', $params);
    }

    /**
     * [[サムネイルレコード通番をカウント]]
     * 注 : 論理削除済みの属性もカウントする]
     */
    function countThumbnailNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $params = array(
                'item_id' => $Item_ID,
                'item_no' => $Item_No,
                'attribute_id' => $Attribute_ID
        );
        return $this->Db->countExecute('repository_thumbnail', $params);
    }

    /**
     * [[アイテム属性の最大アイテム通番計算]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function calcMaxItemAttrNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_item_attr ".     // アイテム属性テーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテムNo
                 "attribute_id = ? ".               // 属性ID
                 "order by attribute_no; ";         // 属性通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // 属性が無ければ0を返す
        $cnt = count($result);
        if($cnt == 0){
            return 0;
        // 属性があれば最大の属性Noを返す
        } else {
            return intval($result[$cnt-1]['attribute_no']);
        }
    }

    /**
     * [[氏名レコード最大通番計算]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function calcMaxPersonalNameNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_personal_name ". // 氏名テーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテムNo
                 "attribute_id = ? ".               // 属性ID
                 "order by personal_name_no; ";     // 氏名通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // 属性が無ければ0を返す
        $cnt = count($result);
        if($cnt == 0){
            return 0;
        // 属性があれば最大の氏名Noを返す
        } else {
            return intval($result[$cnt-1]['personal_name_no']);
        }
    }

    /**
     * [[ファイルレコード最大通番計算]]
     * 注 : 論理削除済みの属性もカウントする
     */
    function getFileNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $query = "SELECT MAX(file_no) ".
                 "FROM ". DATABASE_PREFIX ."repository_file ".          // ファイルテーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテムNo
                 "attribute_id = ? ".               // 属性ID
                 "order by file_no; ";              // ファイル通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // 属性が無ければ0を返す
        $cnt = count($result);
        if($cnt == 0){
            return 1;
        // 属性があれば最大のファイル氏名No+1を返す
        } else {
            return intval($result[0]['MAX(file_no)'])+1;
        }
    }

    /**
     * [[サムネイルレコード最大通番計算]]
     * 注 : 論理削除済みの属性もカウントする]
     */
    function calcMaxThumbnailNo($Item_ID,$Item_No,$Attribute_ID,&$Error_Msg){
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_thumbnail ".     // サムネイルテーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテムNo
                 "attribute_id = ? ".               // 属性ID
                 "order by file_no; ";              // ファイル通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $Attribute_ID;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // 属性が無ければ0を返す
        $cnt = count($result);
        if($cnt == 0){
            return 0;
        // 属性があれば最大のファイル氏名Noを返す
        } else {
            return intval($result[$cnt-1]['file_no']);
        }
    }

    /**
     * [[パラメタテーブルの値を全て取得]]
     * パラメタが増えたら追記すること
     */
    function getParamTableData(&$admin_params, &$Error_Msg){
        $admin_params = array();                    // 管理パラメタ列
        // パラメタテーブルの全属性を取得
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".     // パラメタテーブル
                 "WHERE is_delete = ?; ";           // 削除フラグ
        $params = null;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        for( $ii=0; $ii<count($result); $ii++ ) {
            // パラメタ名ごとに値を取得
            // Mod set parameter 2010/02/09 K.Ando ---start---
            $admin_params[$result[$ii]['param_name']] = $result[$ii]['param_value'];
            // Mod set parameter 2010/02/09 K.Ando ---end---
        }   // End of for( $ii=0; $ii<count($result); $ii++ )
        return true;
    }

    /**
     * [[パラメタテーブルのレコードを全て取得]]
     * パラメタが増えたら追記すること
     */
    function getParamTableRecord(&$admin_params, &$Error_Msg){
        $admin_params = array();                    // 管理パラメタ列
        // パラメタテーブルの全属性を取得
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".     // パラメタテーブル
                 "WHERE is_delete = ?; ";           // 削除フラグ
        $params = null;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        for( $ii=0; $ii<count($result); $ii++ ) {
            // パラメタ名の連想配列にレコードを保存
            $admin_params[strval($result[$ii]['param_name'])] = $result[$ii];
        }   // End of for( $ii=0; $ii<count($result); $ii++ )
        return true;
    }

    /**
     * [[パラメタテーブルのレコードを更新]]
     */
    function updateParamTableData($params, &$Error_Msg){
         $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".  // パラメタテーブル
                     "SET param_value = ?, ".       // パラメタ値
                     "mod_user_id = ?, ".           // 更新ユーザID
                     "mod_date = ? ".               // 更新日
                     "WHERE param_name = ?; ";      // パラメタ名(PK)
        // UPDATE実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        return true;
    }

    /**
     * [[nc2が動作しているマシンのOSを取得する]]
     */
    function getOsVer($Db = null){
        $query = "SELECT `param_value` ".
                 "FROM `". DATABASE_PREFIX ."repository_parameter` ".
                 "WHERE `param_name` = 'OS_type';";
        if ($Db == null) {
            $result = $this->Db->execute($query);
        } else {
            $result = $Db->execute($query);
        }
        if($result === false) {
            //print($this->Db->ErrorMsg());
            return false;
        }
        if( count($result) == 0 ) {
            //print("OSが0件");
            return "Windows";
        }
        //print($result[0]['param_value']);
        return $result[0]['param_value'];
    }

    // Add 2011/04/08 H.Ito --start--
    /**
     * zip file extract
     *
     * @param $dir_path target dir path
     * @param $file_name target file name
     */
    function zipDecompress($dir_path, $file_name){
        $file_path = $dir_path . DIRECTORY_SEPARATOR. $file_name;
        $tmp = preg_replace("/\.zip*$/", "", $file_name);
        // make dir for extract
        $dir = $dir_path .DIRECTORY_SEPARATOR. $tmp;
        $result = mkdir($dir, 0777);
        if (!$result){
            $this->failTrans(); // ROLLBACK
            throw $exception;
        }
        // extract zip file
        File_Archive::extract(
        File_Archive::read($file_path . "/"),
        File_Archive::appender($dir)
        );
        // delete upload zip file
        unlink($file_path);
    }

    /**
     * get Office XML value
     *
     * @param $dir_path target dir path
     * @param $file_name target file name
     * @return $outText get Text
     */
    function getOfficeXMLText($dir_path, $file_name){
        $outText = "";
        $ret_info = array();
        // set XML data to array
        $content = file_get_contents($dir_path .DIRECTORY_SEPARATOR. $file_name);
        if( !$content ){
            $this->Session->setParameter("error_msg", "Not Found XML file. file name : $file_name");
            return false;
        }
        $xml_parser = xml_parser_create();
        $rtn = xml_parse_into_struct( $xml_parser, $content, $vals );
        if($rtn == 0){
            $this->Session->setParameter("error_msg", "Invalid XML Format");
            return false;
        }
        xml_parser_free($xml_parser);

        $key = "";
        for ( $row_cnt = 0; $row_cnt < count($vals); $row_cnt++ ) {
            /* modify read xml file(line break->' ') 2011/10/04 T.Koyasu -start- */
            $key = "";
            if(isset($vals[$row_cnt]['value']))
            {
                $key = $vals[$row_cnt]['value'];
            }

            if ($vals[$row_cnt]['tag'] == 'A:T')
            {// pptx
                $outText = $outText. $key. ' ';
            }
            else if ($vals[$row_cnt]['tag'] == 'T')
            {// xlsx
                $outText = $outText. $key. ' ';
            }
            else if($vals[$row_cnt]['tag'] == 'W:T')
            {// docx
                $outText = $outText. $key;
            }
            else if($vals[$row_cnt]['tag'] == 'W:P'
            && $vals[$row_cnt]['type'] == 'close')
            {// docx(when line break)
                $outText = $outText. ' ';
            }
            /* modify read xml file 2011/10/03 T.Koyasu -end- */
        }
        return $outText;
    }

    /**
     * get Office XML Attributes
     *
     * @param $dir_path target dir path
     * @param $file_name target file name
     * @return $outText get Text
     */
    function getOfficeXMLAttributes($dir_path, $file_name){
        $outText = "";
        $ret_info = array();
        // set XML data to array
        $content = file_get_contents($dir_path .DIRECTORY_SEPARATOR. $file_name);
        if( !$content ){
            $this->Session->setParameter("error_msg", "Not Found XML file. file name : $file_name");
            return false;
        }
        $xml_parser = xml_parser_create();
        $rtn = xml_parse_into_struct( $xml_parser, $content, $vals );
        if($rtn == 0){
            $this->Session->setParameter("error_msg", "Invalid XML Format");
            return false;
        }
        xml_parser_free($xml_parser);
        $key = "";
        for ( $row_cnt = 0; $row_cnt < count($vals); $row_cnt++ ) {
            if ($vals[$row_cnt]['tag'] == 'SHEET') {
                $key = $vals[$row_cnt]['attributes'];
                /* modify input ',' between sheet_name T.Koyasu 2011/10/04 -start- */
                // $outText length > 0 -> add ',' to $outText
                if (mb_strlen($outText, 'utf-8') !== 0) {
                    $outText = $outText. ',';
                }
                /* modify input ',' between sheet_name T.Koyasu 2011/10/04 -end- */
                $outText = $outText. $key['NAME'];
            }
        }
        return $outText;
    }

    // Add 2011/04/08 H.Ito --end--

    function outFile($file_name, $data){
        $fp = @fopen( $file_name, "w" );
        if ( !$fp ) {
            //print("オープン失敗<br>");
            return false;
        }

        if ( fwrite($fp, $data) === false ) {
            //print("書き込み失敗<br>");
            return false;
        }

        if (isset($fp)){
            //print("クローズ<br>");
            fclose($fp);
        }
    }

    /*
     * 指定したディレクトリ以下を削除
     */
    function removeDirectory($dir) {
        if(strlen($dir) > 0)
        {
            if (file_exists($dir)) {
                chmod ($dir, 0777 );
            }
            else
            {
                return;
            }
            if ($handle = opendir("$dir")) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir("$dir/$file")) {
                            $this->removeDirectory("$dir/$file");
                            if(file_exists("$dir/$file")) {
                                rmdir("$dir/$file");
                            }
                        } else {
                            chmod ("$dir/$file", 0777 );
                            unlink("$dir/$file");
                        }
                    }
                }
                closedir($handle);
                rmdir($dir);
            }
        }
    }

    //--実ファイルを削除する 2013/6/10 A.Jin Add--start--
    /**
     * 実ファイルを削除
     *
     * @param int $item_id アイテムID
     * @param int $attribute_id 属性ID
     * @param int $file_no ファイルNO
     * @return bool 処理成功失敗フラグ
     */
    function removePhysicalFileAndFlashDirectory($item_id, $attribute_id, $file_no){
        //1   Filesのファイル削除
        //Filesのディレクトリを取得する。
        $dir_path = $this->getFileSavePath("file");
        if(strlen($dir_path) == 0){
            // default directory
            $dir_path = BASE_DIR.'/webapp/uploads/repository/files';
        }

        //ディレクトリが存在する場合
        if(file_exists($dir_path)){
            $pattern = $dir_path.'/'.$item_id.'_'.$attribute_id.'_'.$file_no.'.*';
            //ディレクトリ以下のFilesファイルを削除する
            foreach (glob($pattern) as $file_path) {
                //ディレクトリでなかった場合実ファイルを削除する
                if(!is_dir($file_path)){
                    unlink($file_path);
                }
            }
        }

        //2   Flashのファイル&ディレクトリ削除
        $flash_contents_path = $this->getFlashFolder($item_id,$attribute_id,$file_no);
        //ディレクトリが存在する場合
        if(strlen($flash_contents_path)>0){
            //ディレクトリ以下のFlashファイル&ディレクトリを削除する
            $this->removeDirectory($flash_contents_path);
        }

        return true;
    }


    //--実ファイルを削除する 2013/6/10 A.Jin Add--end--


    /**
     * mimetypeから簡略表記を逆引きする
     *
     * @param mimetype 逆引きするmimetype
     * @return mimetype簡略表記
     */
    function mimetypeSimpleName( $mimetype ){
        // Mod mime type 2010/02/17 K.Ando --start--
        // mimetypeと拡張子の連想配列を作成(NC共通componentsより)
        $mimeinfo = array (
            "application/g-zip" => "gz",
            "application/mac-binhex40" => "hqx",
            "application/msword" => "doc",
            "application/octet-stream" => "ics",
            "application/pdf" => "pdf",
            "application/postscript" => "ps",
            "application/smil" => "smi",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.ms-excel.sheet.binary.macroEnabled.12" => "xlsm",
            "application/vnd.ms-excel.template.macroEnabled.12" => "xltm",
            "application/vnd.ms-excel.addin.macroEnabled.12" => "xlam",
            "application/vnd.ms-powerpoint" => "ppt",
            "application/vnd.ms-powerpoint.addin.macroEnabled.12" => "ppam",
            "application/vnd.ms-powerpoint.presentation.macroEnabled.12" => "pptm",
            "application/vnd.ms-powerpoint.slideshow.macroEnabled.12" => "ppsm",
            "application/vnd.ms-powerpoint.slide.macroEnabled.12" => "sldm",
            "application/vnd.ms-powerpoint.template.macroEnabled.12" => "potm",
            "application/vnd.ms-word.document.macroEnabled.12" => "docm",
            "application/vnd.ms-word.template.macroEnabled.12" => "dotm",
            "application/vnd.openxmlformats-officedocument.presentationml.slideshow" => "ppsx",
            "application/vnd.openxmlformats-officedocument.presentationml.template" => "potx",
            "application/vnd.openxmlformats-officedocument.presentationml.slide" => "sldx",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.template" => "xltx",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.template" => "dotx",
            "application/x-csh" => "cs",
            "application/x-dvi" => "dvi",
            "application/x-gtar" => "gtar",
            "application/x-javascript" => "js",
            "application/x-latex" => "latex",
            "application/x-sh" => "sh",
            "application/x-shockwave-flash" => "swf",
            "application/x-stuffit" => "sit",
            "application/x-tar" => "tar",
            "application/x-tex" => "tex",
            "application/x-texinfo" => "texinfo",
            "application/zip" => "zip",
            "audio/au" => "au",
            "audio/mp3" => "mp3",
            "audio/wav" => "wav",
            "audio/x-aiff" => "aiff",
            "audio/x-mpegurl" => "m3u",
            "audio/x-pn-realaudio" => "rm",
            "audio/x-realaudio" => "ra",
            "document/unknown" => "xxx",
            "image/bmp" => "bmp",
            "image/jpeg" => "jpg",
            "image/gif" => "gif",
            "image/pict" => "pict",
            "image/png" => "png",
            "image/x-png" => "png",
            "image/tiff" => "tiff",
            "text/css" => "css",
            "text/html" => "html",
            "text/plain" => "txt",
            //"text/plain" => "applescript",
            //"text/plain" => "h",
            //"text/plain" => "m",
            //"text/plain" => "php",
            //"text/plain" => "csv",
            "text/rtf" => "rtf",
            "text/richtext" => "rtx",
            "text/tab-separated-values" => "tsv",
            "text/xml" => "xml",
            "video/mp4" => "mp4",
            "video/mpeg" => "mpeg",
            "video/quicktime" => "qt",
            "video/quicktime" => "3gp",
            "video/x-dv" => "dv",
            "video/x-ms-asf" => "asf",
            "video/x-ms-wm" => "avi",
            "video/x-ms-wmv" => "wmv",
            "video/x-sgi-movie" => "movie"
        );
        // Mod mime type 2010/02/17 K.Ando --end--

        // keyとvalue反転
        //$mimeinfo = array_flip($mimeinfo);

        // Add mime type 2009/07/30 K.Ito --start--
        //圧縮ファイルのマイムタイプを追加
        $mimeinfo["application/x-gzip"] = "gz";
        $mimeinfo["application/gzip"] = "gz";
        $mimeinfo["application/x-gzip-compressed"] = "gz";
        $mimeinfo["application/x-zip-compressed"] = "zip";
        $mimeinfo["application/x-compress"] = "zip";
        $mimeinfo["application/x-gzip-compressed"] = "gzip";
        $mimeinfo["application/x-gzip"] = "gzip";
        $mimeinfo["video/avi"] = "avi";
        $mimeinfo["video/x-flv"] = "flv";
        $mimeinfo["application/octet-stream"] = "";
        // Add mime type 2009/07/30 K.Ito --end--
        
        // Add mimetype 2014/09/10 T.Ichikawa --start--
        $mimeinfo["text/pdf"] = "pdf";
        // Add mimetype 2014/09/10 T.Ichikawa --end--

        // mime-typeから名称取得
        return $mimeinfo[$mimetype];
    }

    // Add biblio info 2008/08/11 Y.Nakao --start--
    /**
     * [[書誌情報属性の値を取得]]
     * @access public
     *
     */
    function getBiblioInfoTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg, $empty_del_flag=false){
        // 氏名を取得
        $query = "SELECT * ".   // 氏名を取得
                 "FROM ". DATABASE_PREFIX ."repository_biblio_info ".   // 氏名テーブル
                 "WHERE item_id = ? AND ".          // アイテムID
                 "item_no = ? AND ".                // アイテム通番
                 "attribute_id = ? AND ".           // 属性ID
                 "is_delete = ? ".                  // 削除されていない
                 "order by biblio_no ; ";       // 書誌情報通番順にソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_Biblio_Info_Table = $this->Db->execute($query, $params);
        if($result_Biblio_Info_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        
        // Add LIDO 2014/05/14 S.Suzuki --start--
        if ($empty_del_flag) {
            $tmp_biblio = array();
            
            for ($ii = 0; $ii < count($result_Biblio_Info_Table); $ii++) {
                $result_Biblio_Info_Table[$ii]['biblio_name']         = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['biblio_name']);
                $result_Biblio_Info_Table[$ii]['biblio_name_english'] = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['biblio_name_english']);
                $result_Biblio_Info_Table[$ii]['volume']              = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['volume']);
                $result_Biblio_Info_Table[$ii]['issue']               = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['issue']);
                $result_Biblio_Info_Table[$ii]['start_page']          = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['start_page']);
                $result_Biblio_Info_Table[$ii]['end_page']            = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['end_page']);
                $result_Biblio_Info_Table[$ii]['date_of_issued']      = RepositoryOutputFilter::exclusiveReservedWords($result_Biblio_Info_Table[$ii]['date_of_issued']);
                
                $biblio = $result_Biblio_Info_Table[$ii]['biblio_name'] .
                          $result_Biblio_Info_Table[$ii]['biblio_name_english'] .
                          $result_Biblio_Info_Table[$ii]['volume'] .
                          $result_Biblio_Info_Table[$ii]['issue'] .
                          $result_Biblio_Info_Table[$ii]['start_page'] .
                          $result_Biblio_Info_Table[$ii]['end_page'] .
                          $result_Biblio_Info_Table[$ii]['date_of_issued'];
                
                if ( strlen($biblio) > 0) {
                    array_push($tmp_biblio, $result_Biblio_Info_Table[$ii]);
                }
            }
            $result_Biblio_Info_Table = $tmp_biblio;
        }
        // Add LIDO 2014/05/14 S.Suzuki --end--
        
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_Biblio_Info_Table;

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定される書誌情報テーブルデータ削除]]
     */
    function deleteBiblioInfoTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // 書誌情報テーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_biblio_info ".   //氏名テーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_biblio_info ".
                     "SET del_user_id = ?, ".
                     "del_date = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;

    }
    // Add biblio info 2008/08/11 Y.Nakao --end--

    // Add file price 2008/08/28 Y.Nakao --start--
    /**
     * NC上に存在するグループの一覧を取得
     * @return $Result_List['groupe_list'][ii]['～']形式で値が帰る
     */
    function getGroupList(&$all_group, &$error_msg){
        // get List from pages Table
        $query = "SELECT * FROM ". DATABASE_PREFIX ."pages ".
                 "WHERE space_type = ? AND ".
                 "private_flag = ? AND ".
                 "NOT thread_num = ? AND ".
        // Fix select group list 2009/02/03 Y.Nakao --start--
                 "room_id = page_id; ";
        // Fix select group list 2009/02/03 Y.Nakao --end--
        $params = null;
        $params[] = _SPACE_TYPE_GROUP;
        $params[] = 0;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            return false;
        }
        // 結果を格納
        $all_group = $result;
        return true;
    }

    /**
     * ユーザの登録グループ一覧を取得する
     */
    function getUsersGroupList(&$user_group, &$error_msg){
        $userAuthorityManager = new RepositoryUserAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        return $userAuthorityManager->getUsersGroupList($user_group, $error_msg);
    }

    /**
     * ファイルの価格情報を取得する
     */
    function getFilePriceTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg,$blob_flag=false){
        ///// ファイル情報取得 /////
        // ファイル名を取得
        $query = "SELECT * ".       // ファイル名を取得
                 "FROM ". DATABASE_PREFIX ."repository_file ".  // 氏名テーブル
                 "WHERE item_id = ? AND ".  // アイテムID
                 "item_no = ? AND ".        // アイテム通番
                 "attribute_id = ? AND ".   // 属性ID
                 "is_delete = ? ".          // 削除されていない
                 "order by show_order; ";      // 表示順序でソート
        // $queryの?を置き換える配列
        $params = null;
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = $attr_id;
        $params[] = 0;
        // SELECT実行
        $result_File_Table = $this->Db->execute($query, $params);
        if($result_File_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --start--
        $contents_path = $this->getFileSavePath("file");
        if(strlen($contents_path) == 0){
            // default directory
            $contents_path = BASE_DIR.'/webapp/uploads/repository/files';
        }
        // Add separate file from DB 2009/04/21 Y.Nakao --end--

        // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
        // Add get Flash file size 2010/06/08 A.Suzuki --start--
        $flash_contents_path = $this->getFlashFolder();
        if(strlen($flash_contents_path) == 0){
            // default directory
            $flash_contents_path = BASE_DIR.'/webapp/uploads/repository/flash';
        }
        // Add get Flash file size 2010/06/08 A.Suzuki --end--
        // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--

        // アイテム詳細表示でライセンス情報表示対応 2008/07/02 Y.Nakao --start--
        for($i=0; $i<count($result_File_Table); $i++) {
            $query = "SELECT * ".       // ファイル名を取得
                     "FROM ". DATABASE_PREFIX ."repository_license_master ".    // ライセンスマスタテーブル
                     "WHERE license_id = ?; ";  // ライセンスID
            $params = null;
            $params[] = $result_File_Table[$i]['license_id'];
            $result_License_Table = $this->Db->execute($query, $params);
            if($result_License_Table === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                return false;
            }
            if(count($result_License_Table) == 1){
                $result_File_Table[$i]['img_url'] = $result_License_Table[0]['img_url'];
                $result_File_Table[$i]['text_url'] = $result_License_Table[0]['text_url'];
                $result_File_Table[$i]['license_notation'] = $result_License_Table[0]['license_notation'];
            }
            // Add separate file from DB 2009/04/21 Y.Nakao --start--
            $file_path = $contents_path.DIRECTORY_SEPARATOR.
                        $result_File_Table[$i]['item_id'].'_'.
                        $result_File_Table[$i]['attribute_id'].'_'.
                        $result_File_Table[$i]['file_no'].'.'.
                        $result_File_Table[$i]['extension'];
            // get file size
            if(file_exists($file_path))
            {
                $size = filesize($file_path);
                if( ($size/1000)>1 ){
                    if( ($size/1000000)>1 ){
                        $result_File_Table[$i]['file_size'] = round($size/1000000, 2)."MB";
                    } else {
                        $result_File_Table[$i]['file_size'] = round($size/1000, 2)."KB";
                    }
                } else {
                    $result_File_Table[$i]['file_size'] = $size."Byte";
                }
            }
            else
            {
                $result_File_Table[$i]['file_size'] = "0Byte";
            }
            // Add separate file from DB 2009/04/21 Y.Nakao --end--

            // Add get Flash file size 2010/06/08 A.Suzuki --start--
            // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
            $flash_contents_path = $this->getFlashFolder($result_File_Table[$i]['item_id'],
                                                          $result_File_Table[$i]['attribute_id'],
                                                          $result_File_Table[$i]['file_no']);
            if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.swf')){
                // get file size
                $flash_size = filesize($flash_contents_path.DIRECTORY_SEPARATOR.'/weko.swf');
                if($flash_size === false){
                    $result_File_Table[$i]['flash_size'] = 0;
                } else {
                    $result_File_Table[$i]['flash_size'] = $flash_size;
                }
            } else if(file_exists($flash_contents_path.DIRECTORY_SEPARATOR.'/weko1.swf')){
                // get file size
                $flash_size = filesize($flash_contents_path.DIRECTORY_SEPARATOR.'/weko1.swf');
                if($flash_size === false){
                    $result_File_Table[$i]['flash_size'] = 0;
                } else {
                    $result_File_Table[$i]['flash_size'] = $flash_size;
                }
            } else {
                $result_File_Table[$i]['flash_size'] = 0;
            }
            // Add get Flash file size 2010/06/08 A.Suzuki --end--

            if(!$blob_flag){
                $result_File_Table[$i]['file_prev'] = "";
            }

            // Add file price Y.Nakao 2008/08/29 --start--
            ///// 課金情報を取得 /////
            $query = "SELECT * ".       // ファイル名を取得
                     "FROM ". DATABASE_PREFIX ."repository_file_price ".    // 課金テーブル
                     "WHERE item_id = ? AND ".  // アイテムID
                     "item_no = ? AND ".        // アイテム通番
                     "attribute_id = ? AND ".   // 属性ID
                     "file_no = ? AND ".        // ファイル通番
                     "is_delete = ?; ";         // 削除されていない
            // $queryの?を置き換える配列
            $params = null;
            $params[] = $Item_ID;
            $params[] = $Item_No;
            $params[] = $attr_id;
            $params[] = $result_File_Table[$i]['file_no'];
            $params[] = 0;
            // SELECT実行
            $result_file_price_Table = $this->Db->execute($query, $params);
            if($result_file_price_Table === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                return false;
            }
            $result_File_Table[$i]['price'] = '';
            if(count($result_file_price_Table) == 1)
            {
                $result_File_Table[$i]['price'] = $result_file_price_Table[0]['price'];
            }
            // Add file price Y.Nakao 2008/08/29 --end--
        }
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_File_Table;
        return true;
    }

    function insertFilePrice($params,&$Error_Msg){
        // 課金ファイル入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_file_price ".
//                   "(item_id, item_no, attribute_id, file_no, price".
//                   "ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, ".
//                   "del_date, is_delete) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    function insertOrUpdatePrice($params,&$Error_Msg){
        // 課金ファイル入力用クエリー (挿入)
        $query = "INSERT INTO ". DATABASE_PREFIX ."repository_file_price ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
        $query .= " ON DUPLICATE KEY UPDATE ".
                  " price=VALUES(`price`), mod_user_id=VALUES(`mod_user_id`), ".
                  " del_user_id=VALUES(`del_user_id`), mod_date=VALUES(`mod_date`), ".
                  " del_date=VALUES(`del_date`), is_delete=VALUES(`is_delete`); ";
        //INSERT実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }

    function updateFilePrice($params,&$Error_Msg){
        // 課金ファイル入力用クエリー (更新)
        // ※更新対象はライセンスとエンバーゴ、更新者、更新日
        $query = "UPDATE ". DATABASE_PREFIX ."repository_file_price ".
                     "SET file_no = ?, ".
                     "price = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ? AND ".
                     "attribute_id = ? AND ".
                     "file_no = ?; ";
        //UPDATE実行
        $result = $this->Db->execute($query,$params);
        if($result === false){
            //必要であればSQLエラー番号・メッセージ取得
            $errNo = $this->Db->ErrorNo();
            $Error_Msg = $this->Db->ErrorMsg();
            $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        return true;
    }
    // Add file price 2008/08/28 Y.Nakao --start--

    // Add get repository block_id and page_id 2008/09/19 Y.Nakao --start--
    function getBlockPageId(){
        // check NC version 2010/06/07 A.Suzuki --start--
        // get NC version
        $version = $this->getNCVersion();
        $return_array = null;

        if(str_replace(".", "", $version) < 2300){
            // Before NetCommons2.3.0.0
            // change sql to get id for WEKO in public space 2008/11/20 Y.Nakao --start--
            // get repository block_id and page_id
            $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type ".
                     "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                              DATABASE_PREFIX ."pages AS pages ".
                     "WHERE blocks.action_name = ? ".
                     "AND blocks.page_id = pages.page_id ".
                     "ORDER BY blocks.insert_time ASC; ";
            $params = array();
            $params[] = "repository_view_main_item_snippet";
            // change sql to get id for WEKO in public space 2008/11/20 Y.Nakao --start--
            $container =& DIContainerFactory::getContainer();
            $filterChain =& $container->getComponent("FilterChain");
            $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
            if(count($result)==1){
                // WEKOがNC上に一つしかない
                $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id']);
            }else{
                // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
                for($ii=0; $ii<count($result); $ii++){
                    if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC){
                        $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                        break;
                    }
                }
            }
        } else {
            // On and after NetCommons2.3.0.0
            // get repository block_id and page_id
            $query = "SELECT blocks.block_id, blocks.page_id, pages.room_id, pages.private_flag, pages.space_type, pages.lang_dirname ".
                     "FROM ". DATABASE_PREFIX ."blocks AS blocks, ".
                              DATABASE_PREFIX ."pages AS pages ".
                     "WHERE blocks.action_name = ? ".
                     "AND blocks.page_id = pages.page_id ".
                     "ORDER BY blocks.insert_time ASC; ";
            $params = array();
            $params[] = "repository_view_main_item_snippet";
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                $this->failTrans(); //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
            $lang = $this->Session->getParameter("_lang");
            if(count($result)==1){
                // WEKOがNC上に一つしかない
                $return_array = array('block_id'=>$result[0]['block_id'],'page_id'=>$result[0]['page_id'],'room_id'=>$result[0]['room_id'],'space_type'=>$result[0]['space_type']);
            }else{
                // WEKOがNC上に複数ある場合はパブリックに配置されているもののみ有効
                for($ii=0; $ii<count($result); $ii++){
                    if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==$lang){
                        $return_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                    } else if($result[$ii]['private_flag']==0 && $result[$ii]['space_type']==_SPACE_TYPE_PUBLIC && $result[$ii]['lang_dirname']==""){
                        // lang_dirname is empty
                        $tmp_array = array('block_id'=>$result[$ii]['block_id'],'page_id'=>$result[$ii]['page_id'],'room_id'=>$result[$ii]['room_id'],'space_type'=>$result[$ii]['space_type']);
                    }
                }
                if(empty($return_array) && isset($tmp_array)){
                    $return_array = $tmp_array;
                }
            }
        }
        // check NC version 2010/06/07 A.Suzuki --end--
        return $return_array;
    }
    // Add get repository block_id and page_id 2008/09/19 Y.Nakao --end--

    // Add set lang resource Add set lang resource 2008/10/06 Y.Nakao --start--
    // ********** this function must call repository/view action **********
    // if repository/action action call this function then don't get lang resource
    function setLangResource(){
        $container =& DIContainerFactory::getContainer();
        $filterChain =& $container->getComponent("FilterChain");
        $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
        $this->Session->setParameter("smartyAssign", $smartyAssign);
    }
    // Add set lang resource 2008/10/06 Y.Nakao --end--

    // Add get all setting price 2008/10/30 Y.Nakao --start--
    function getGroupPrice($info){
        // get price table data
        $query = "SELECT price FROM ". DATABASE_PREFIX ."repository_file_price ".
                 "WHERE item_id = ? AND ".
                 "item_no = ? AND ".
                 "attribute_id = ? AND ".
                 "file_no = ? AND ".
                 "is_delete = 0; ";
        $params = array();
        $params[] = $info["item_id"];
        $params[] = $info["item_no"];
        $params[] = $info["attribute_id"];
        $params[] = $info["file_no"];
        $group_price = $this->Db->execute( $query, $params );
        if($group_price === false){
            return array();
        }
        $price_export = array();
        if(isset($group_price[0]["price"])){
            $price = explode("|", $group_price[0]["price"]);
            for($ii=0; $ii<count($price); $ii++){
                $info = explode(",", $price[$ii]);
                if($info[0] != '0'){
                    // get page_name from room_id
                    $query = "SELECT page_name FROM ". DATABASE_PREFIX ."pages ".
                             "WHERE room_id = ?; ";
                    $params = array();
                    $params[] = $info[0];
                    $pages = $this->Db->execute( $query, $params );
                    if($pages === false){
                        //print $this->Db->ErrorMsg();
                        return array();
                    }
                    if(count($pages) == 1){
                        // group is not delete
                        array_push($price_export, array("name" => $pages[0]["page_name"],
                                                        "price" => $info[1],
                                                        "id" => $info[0])
                                    );
                    }
                } else {
                    // group is not delete
                    array_push($price_export, array("name" => "0",
                                                    "price" => $info[1],
                                                    "id" => 0)
                                );
                }
            }
        }
        return $price_export;
    }
    // Add get all setting price 2008/10/30 Y.Nakao --end--

    // Add detail uri 2008/11/13 --start--
    function getDetailUri($item_id, $item_no){
        // init
        $detail_uri = "";
        // get DB data
        $query = "SELECT uri FROM ". DATABASE_PREFIX ."repository_item ".
                 "WHERE item_id = ".$item_id." ".
                 "AND item_no = ".$item_no." ; ";
        $return = $this->Db->execute( $query );
        if($return === false || count($detail_uri)!=1){
            return $detail_uri;
        }
        // get detail uri
        $detail_uri = $return[0]["uri"];
        if($detail_uri == ""){
            $detail_uri = BASE_URL. "/?action=repository_uri&item_id=".$item_id;
        }

        return $detail_uri;

    }
    // Add detail uri 2008/11/13 --end--

    // Add component Login action 2008/12/08 Y.Nakao --start--
    /**
     * login check
     *
     * @param $login_id login ID
     * @param $password password (this word is not md5 encoding)
     * @param $Result_List users table data
     * @param $error_msg error message
     * @return true:LoginOK false:LoginNG
     */
    function checkLogin($login_id, $password, &$Result_List, &$error_msg){
        if($this->Db == null){
            $container =& DIContainerFactory::getContainer();
            $this->Db =& $container->getComponent("DbObject");
        }
        if($this->Session == null){
            $container =& DIContainerFactory::getContainer();
            $this->Session =& $container->getComponent("Session");
        }
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."users ".
                 "WHERE login_id=? ".
                 " AND password=?; ";
        $params = array();
        $params[] = $login_id;
        $params[] = md5($password);
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            return false;
        }
        if(count($result)==1) {
            // set result
            $Result_List = $result;
            // set login data
            $container =& DIContainerFactory::getContainer();
            $configView =& $container->getComponent("configView");
            $authoritiesView =& $container->getComponent("authoritiesView");
            $actionChain =& $container->getComponent("ActionChain");
            $action =& $actionChain->getCurAction();
            $authorities =& $authoritiesView->getAuthorityById($result[0]["role_authority_id"]);
            if($authorities === false || !isset($authorities['user_authority_id'])) return $errStr;

            $config = $configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
            if($config['closesite']['conf_value'] == _ON && $authorities['user_authority_id'] < $config['closesite_okgrp']['conf_value']) {
                return LOGIN_ACTION_CLOSESITE;
            }

            BeanUtils::setAttributes($action, array("user_id"=>$result[0]["user_id"]));
            BeanUtils::setAttributes($action, array("handle"=>$result[0]["handle"]));
            BeanUtils::setAttributes($action, array("role_authority_id"=>$result[0]["role_authority_id"]));
            BeanUtils::setAttributes($action, array("timezone_offset"=>$result[0]["timezone_offset"]));
            BeanUtils::setAttributes($action, array("last_login_time"=>$result[0]["last_login_time"]));
            BeanUtils::setAttributes($action, array("system_flag"=>$result[0]["system_flag"]));
            BeanUtils::setAttributes($action, array("lang_dirname"=>$result[0]["lang_dirname"]));

            BeanUtils::setAttributes($action, array("role_authority_name"=>$authorities['role_authority_name']));
            BeanUtils::setAttributes($action, array("user_authority_id"=>$authorities['user_authority_id']));
            BeanUtils::setAttributes($action, array("allow_attachment"=>$authorities['allow_attachment']));
            BeanUtils::setAttributes($action, array("allow_htmltag_flag"=>$authorities['allow_htmltag_flag']));
            BeanUtils::setAttributes($action, array("allow_layout_flag"=>$authorities['allow_layout_flag']));
            BeanUtils::setAttributes($action, array("max_size"=>$authorities['max_size']));

            // Add config management authority 2010/02/23 Y.Nakao --start--
            // get room authority
            BeanUtils::setAttributes($action, array("authority_id"=>$this->getRoomAuthorityID($result[0]["user_id"])));
            // Add config management authority 2010/02/23 Y.Nakao --end--
            return true;
        } else {
            return false;
        }
    }
    // Add component Login action 2008/12/08 Y.Nakao --end--


    function getRoomAuthorityID($user_id = ""){
        $userAuthorityManager = new RepositoryUserAuthorityManager($this->Session, $this->dbAccess, $this->TransStartDate);
        return $userAuthorityManager->getRoomAuthorityID($user_id);
    }

    // Add count contents of index 2008/12/22 A.Suzuki --start--
    /**
     * add contents
     *
     * @param $index_id index ID
     */
    function addContents($index_id){
        // increment contents
        $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                 "   SET contents = contents + 1 ".
                 " WHERE index_id = ?;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }

        // update perent contents
        $query = "SELECT parent_index_id ".
                 "  FROM ".DATABASE_PREFIX."repository_index ".
                 " WHERE index_id = ? ;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        if(count($result)>0){
            if($result[0]['parent_index_id'] != 0){
                $this->addContents($result[0]['parent_index_id']);
            }
        }
    }

    /**
     * delete contents
     *
     * @param $index_id index ID
     */
    function deleteContents($index_id){
        // decrement contents
        $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                 "   SET contents = contents - 1 ".
                 " WHERE index_id = ? ".
                 " AND contents > 0; ";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }

        // update perent contents
        $query = "SELECT parent_index_id ".
                 "  FROM ".DATABASE_PREFIX."repository_index ".
                 " WHERE index_id = ? ;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        if(count($result)>0){
            if($result[0]['parent_index_id'] != 0){
                $this->deleteContents($result[0]['parent_index_id']);
            }
        }
    }
    // Add count contents of index 2008/12/22 A.Suzuki --end--

    // Add count private_contents of index 2013/05/07 K.Matsuo --start--
    /**
     * add private_contents
     *
     * @param $index_id index ID
     */
    function addPrivateContents($index_id){
        // increment contents
        $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                 "   SET private_contents = private_contents + 1 ".
                 " WHERE index_id = ?;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }

        // update perent contents
        $query = "SELECT parent_index_id ".
                 "  FROM ".DATABASE_PREFIX."repository_index ".
                 " WHERE index_id = ? ;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        if(count($result)>0){
            if($result[0]['parent_index_id'] != 0){
                $this->addPrivateContents($result[0]['parent_index_id']);
            }
        }
    }

    /**
     * delete private_contents
     *
     * @param $index_id index ID
     */
    function deletePrivateContents($index_id){
        // decrement contents
        $query = "UPDATE ".DATABASE_PREFIX."repository_index ".
                 "   SET private_contents = private_contents - 1 ".
                 " WHERE index_id = ? ".
                 " AND private_contents > 0; ";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }

        // update perent contents
        $query = "SELECT parent_index_id ".
                 "  FROM ".DATABASE_PREFIX."repository_index ".
                 " WHERE index_id = ? ;";
        $params = null;
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
        if(count($result)>0){
            if($result[0]['parent_index_id'] != 0){
                $this->deletePrivateContents($result[0]['parent_index_id']);
            }
        }
    }
    // Add count private_contents of index 2013/05/07 K.Matsuo --end--

    // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki --start--
    /**
     * addWhatsnew
     *
     * @param $item_id
     * @param $item_no
     */
    function addWhatsnew($item_id, $item_no, $noblock_id=0){
        $container =& DIContainerFactory::getContainer();
        $whatsnewAction =& $container->getComponent("whatsnewAction");

        // get item data
        $this->getItemData($item_id, $item_no, $Result_List, $error_msg, false, true);
        $description = "";

        for($ii=0; $ii<count($Result_List['item_attr_type']); $ii++){
            if($Result_List['item_attr_type'][$ii]['list_view_enable'] == 1){
                for($jj=0; $jj<count($Result_List['item_attr'][$ii]); $jj++){

                    if($description != ""){
                        $description .= ", ";
                    }

                    if($Result_List['item_attr_type'][$ii]['input_type'] == "name"){
                        $description .= $Result_List['item_attr'][$ii][$jj]['family'];
                        if($Result_List['item_attr'][$ii][$jj]['name'] != ""){
                            $description .= " ".$Result_List['item_attr'][$ii][$jj]['name'];
                        }

                    } else if($Result_List['item_attr_type'][$ii]['input_type'] == "biblio_info"){
                        $biblio_info_data = "";
                        if($Result_List['item_attr'][$ii][$jj]['biblio_name'] != ""){
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['biblio_name'];
                        }
                        if($Result_List['item_attr'][$ii][$jj]['volume'] != ""){
                            if($biblio_info_data != ""){
                                $biblio_info_data .= ", ";
                            }
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['volume'];
                        }
                        if($Result_List['item_attr'][$ii][$jj]['issue'] != ""){
                            if($biblio_info_data != ""){
                                $biblio_info_data .= ", ";
                            }
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['issue'];
                        }
                        if($Result_List['item_attr'][$ii][$jj]['start_page'] != ""){
                            if($biblio_info_data != ""){
                                $biblio_info_data .= ", ";
                            }
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['start_page'];
                        }
                        if($Result_List['item_attr'][$ii][$jj]['end_page'] != ""){
                            if($Result_List['item_attr'][$ii][$jj]['start_page'] != ""){
                                $biblio_info_data .= "-";
                            }
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['end_page'];
                        }
                        if($Result_List['item_attr'][$ii][$jj]['date_of_issued'] != ""){
                            if($biblio_info_data != ""){
                                $biblio_info_data .= ", ";
                            }
                            $biblio_info_data .= $Result_List['item_attr'][$ii][$jj]['date_of_issued'];
                        }
                        $description .= $biblio_info_data;
                    } else if($Result_List['item_attr_type'][$ii]['input_type'] == "thumbnail" ||
                              $Result_List['item_attr_type'][$ii]['input_type'] == "file" ||
                              $Result_List['item_attr_type'][$ii]['input_type'] == "file_price"){
                        $description .= $Result_List['item_attr'][$ii][$jj]['file_name'];
                    } else {
                        $description .= $Result_List['item_attr'][$ii][$jj]['attribute_value'];
                    }
                }
            }
        }

        // 新着情報に送信
        if($noblock_id == 0){
            $whatsnew = array(
                "unique_id" => $item_id,
                "title" => $Result_List['item'][0]['title'],
                "description" => $description,
                "action_name" => "repository_uri",
                "parameters" => "item_id=".$item_id
            );
        } else {
            // block_id取得
            $blockPageId = $this->getBlockPageId();
            $block_id = $blockPageId['block_id'];
            $room_id = $blockPageId['room_id'];

            // ハンドル名がセッションになければ取得
            if($this->Session->getParameter("_handle") == null || $this->Session->getParameter("_handle") == ""){
                $query = "SELECT handle ".
                         "FROM ".DATABASE_PREFIX."users ".
                         "WHERE user_id = ?;";
                $param = array();
                $param[] = $this->Session->getParameter("_user_id");
                $result = $this->Db->execute($query, $param);
                if($result === false){
                    $error_msg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_cord",-1);
                    if($istest) { echo $error_msg . "<br>"; }
                    $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
                    return false;
                }
                $this->Session->setParameter("_handle", $result[0]['handle']);
            }

            $whatsnew = array(
                "unique_id" => $item_id,
                "title" => $Result_List['item'][0]['title'],
                "description" => $description,
                "action_name" => "repository_uri",
                "parameters" => "item_id=".$item_id."&block_id=".$block_id."#_".$block_id,
                "room_id" => $room_id
            );
        }
        $result = $whatsnewAction->auto($whatsnew, $noblock_id);
        if ($result === false) {
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
    }

    /**
     * deleteWhatsnew
     *
     * @param $item_id
     */
    function deleteWhatsnew($item_id){
        $container =& DIContainerFactory::getContainer();
        $whatsnewAction =& $container->getComponent("whatsnewAction");
        $result = $whatsnewAction->delete($item_id);
        if ($result === false) {
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }
    }
    // Add send item infomation to whatsnew module 2009/01/27 A.Suzuki --end--

    // Add check public_state 2009/02/05 A.Suzuki --start--
    /**
     * checkParentPublicState
     * 上位インデックスが非公開でないか調べる。
     *
     * @param  $index_id
     * @return true:公開中
     *         false:非公開である
     */
    function checkParentPublicState($index_id){
        // 親インデックスのIDを取得
        $query = "SELECT parent_index_id ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE index_id = ? ".
                 "AND is_delete = 0;";
        $params = array();
        $params[] = $index_id;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) != 1){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            if($istest) { echo $error_msg . "<br>"; }
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return false;
        }

        // 親がルートインデックスではない場合
        if($result[0]['parent_index_id'] != "0"){
            // 親インデックスの公開状況を取得
            $query = "SELECT public_state ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE index_id = ? ".
                     "AND is_delete = 0;";
            $params = array();
            $params[] = $result[0]['parent_index_id'];
            $parent = $this->Db->execute($query, $params);
            if($parent === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                if($istest) { echo $error_msg . "<br>"; }
                $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
                return false;
            }

            if($parent[0]['public_state'] == "0"){
                // 親が非公開の場合
                return false;
            } else {
                // 親が公開の場合、その親を調べる
                if($this->checkParentPublicState($result[0]['parent_index_id']) == false){
                    // 親に非公開があった場合
                    return false;
                }
            }
        }

        // 上位のインデックスが非公開でない場合
        return true;
    }
    // Add check public_state 2009/02/05 A.Suzuki --end--

    // add set default sort setting 2009/03/10 A.Suzuki --start--
    /**
     * getSortName
     * ソートIDに該当するソート条件名を取得する
     *
     * @param $sort_id
     */
    function getSortName($sort_id){
        $this->setLangResource();
        $sort_name= "";

        if($sort_id == 1){
            // title ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_title_asc");
        } else if($sort_id == 2){
            // title DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_title_desc");
        } else if($sort_id == 3){
            // author ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_author_asc");
        } else if($sort_id == 4){
            // author DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_author_desc");
        } else if($sort_id == 5){
            // itemtype_id ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_content_type_asc");
        } else if($sort_id == 6){
            // itemtype_id DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_content_type_desc");
        } else if($sort_id == 7){
            // WEKOID ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_weko_id_asc");
        } else if($sort_id == 8){
            // WEKOID DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_weko_id_desc");
        } else if($sort_id == 9){
            // modify_date ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_modify_date_asc");
        } else if($sort_id == 10){
            // modify_date DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_modify_date_desc");
        } else if($sort_id == 11){
            // contribute_date ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_contribute_date_asc");
        } else if($sort_id == 12){
            // contribute_date DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_contribute_date_desc");
        } else if($sort_id == 13){
            // review_date ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_review_date_asc");
        } else if($sort_id == 14){
            // review_date DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_review_date_desc");
        }
        // Add sort order "publication year" 2009/06/25 A.Suzuki --start--
        else if($sort_id == 15){
            // review_date ASC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_publication_year_asc");
        } else if($sort_id == 16){
            // review_date DESC
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_publication_year_desc");
        }
        // Add sort order "publication year" 2009/06/25 A.Suzuki --end--
        // Add sort order "Custom" 2012/12/21 A.Jin --start--
        else if($sort_id == 17){
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_custom_asc");
        } else if($sort_id == 18){
            $sort_name = $this->Session->getParameter("smartyAssign")->getLang("repository_search_custom_desc");
        }
        // Add sort order "Custom" 2012/12/21 A.Jin --end--
        return $sort_name;
    }
    // add set default sort setting 2009/03/10 A.Suzuki --start--

    // Add show parent index 2009/04/15 A.Suzuki --start--
    /**
     * getParentIndex
     * 上位インデックスのデータを得る
     *
     * @param int   $index_id
     *        array &$parent_index
     */
    function getParentIndex($index_id, &$parent_index){
        if($index_id != "0"){
            // インデックスの情報を取得
            $query = "SELECT * ".
                     "FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE index_id = ? ".
                     "AND is_delete = 0;";
            $params = array();
            $params[] = $index_id;
            $result = $this->Db->execute($query, $params);
            if($result === false){
                $error_msg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_cord",-1);
                if($istest) { echo $error_msg . "<br>"; }
                $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
                return false;
            }
            if(strlen($result[0]['thumbnail'])!=0){
                $result[0]['thumbnail'] = "";
            }
            // 親がルートインデックスではない場合
            if($result[0]['parent_index_id'] != "0"){
                $this->getParentIndex($result[0]['parent_index_id'], $parent_index);
            }
            array_push($parent_index, $result[0]);
        }
    }
    // Add show parent index 2009/04/15 A.Suzuki --end--

    // Add output OpenSearch description document 2009/06/02 A.Suzuki --start--
    /**
     * forXmlChange
     * XML出力時に特殊文字を HTML エンティティに変換する
     *
     * @param $tmp
     * @return $enc_tmp
     */
    function forXmlChange ($tmp) {
        $tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $tmp);
        $enc_tmp = htmlspecialchars($tmp, ENT_QUOTES, "UTF-8");
        return $enc_tmp;
    }

    /**
     * forXmlChangeDecode
     * XML出力時に変換した特殊文字を元に戻す
     *
     * @param $tmp
     * @return $enc_tmp
     */
    function forXmlChangeDecode ($tmp) {
        $enc_tmp = htmlspecialchars_decode($tmp, ENT_QUOTES);
        return $enc_tmp;
    }

    // Add output OpenSearch description document 2009/06/02 A.Suzuki --end--

    /**
     * changeDatetimeToW3C
     * Datetime型をW3CDTFに変換する
     *
     * @param $datetime
     * @return $w3c
     */
    function changeDatetimeToW3C($datetime) {
        $w3c = null;
        $time = null;
        if($datetime == ""){
            return "";
        }
        $tmp = explode(" ",$datetime);
        if(isset($tmp[1]) && $tmp[1]!=""){
            $tmp_time = explode(".", $tmp[1]);
            $time = $tmp_time[0];
        }
        $w3c = $tmp[0];
        if($time != ""){
            $w3c .= "T".$time."+09:00";
        }
        return $w3c;
    }

    /**
     * getMoneyUnit
     * 設定された通貨単位を取得しHTML表示用とJavascript表示用を配列にして返す
     *
     * @return $money_units 通貨単位文字列の配列
     */
    function getMoneyUnit() {
        // パラメタテーブルの値を取得
        $admin_params = null;
        $error_msg = null;
        $return = $this->getParamTableData($admin_params, $error_msg);
        if($return == false){
            return false;
        }

        $money_units = null;

        if($admin_params['currency_setting'] != null){
            if($admin_params['currency_setting'] == "0"){
                $money_units['money_unit'] = "\\";
                $money_units['money_unit_conf'] = "\\\\";
            } else {
                $money_units['money_unit'] = "$";
                $money_units['money_unit_conf'] = "$";
            }

            return $money_units;

        } else {
            return false;
        }
    }

    // Add fix login data 2009/07/31 A.Suzuki --start--
    // セッションのログイン情報の一部が消えてしまうバグ対応
    /**
     * [[ログイン情報復元処理]]
     *
     * @access  public
     */
    function fixSessionData() {
        // コンポーネント
        $container =& DIContainerFactory::getContainer();
        $authoritiesView =& $container->getComponent("authoritiesView");

        $user_id = $this->Session->getParameter("_user_id");

        // ログイン状態を判別
        if($user_id != '0'){
            $login_id = $this->Session->getParameter("_login_id");
            $handle = $this->Session->getParameter("_handle");
            $role_auth_id = $this->Session->getParameter("_role_auth_id");
            $role_authority_name = $this->Session->getParameter("_role_authority_name");
            $user_auth_id = $this->Session->getParameter("_user_auth_id");

            // ログイン情報の何れかが消えてしまっている場合
            if($login_id == null || $handle == null || $role_auth_id == null || $role_authority_name == null || $user_auth_id == null){
                $query = "SELECT * FROM ".DATABASE_PREFIX."users ".
                         " WHERE user_id = ? ".
                         " AND active_flag = ?;";
                $params = array();
                $params[] = $user_id;
                $params[] = _USER_ACTIVE_FLAG_ON;
                $result = $this->Db->execute($query, $params);
                if($result === false){
                    return false;
                }
                $authorities =& $authoritiesView->getAuthorityById($result[0]["role_authority_id"]);

                // 値を詰めなおす
                $this->Session->setParameter("_login_id", $result[0]["login_id"]);
                $this->Session->setParameter("_handle", $result[0]["handle"]);
                $this->Session->setParameter("_role_auth_id", $result[0]["role_authority_id"]);
                $this->Session->setParameter("_role_authority_name", $authorities['role_authority_name']);
                $this->Session->setParameter("_user_auth_id", $authorities['user_authority_id']);
            }
        }
    }
    // Add fix login data 2009/07/31 A.Suzuki --end--

    // Add input type "supple" 2009/08/24 A.Suzuki --start--
    /**
     * [[サプリ属性の値を取得]]
     * @access public
     *
     */
    function getSuppleTableData($Item_ID,$Item_No,$attr_id,$idx,&$Result_List,&$error_msg){
        // サプリテーブルからitem_id, item_noに紐付くサプリデータを取得
        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_supple ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND attribute_id = ? ".
                 "AND is_delete = ? ".
                 "ORDER BY supple_no asc;";
        $params = array();
        $params[] = $Item_ID;   // item_id
        $params[] = $Item_No;   // item_no
        $params[] = $attr_id;   // attribute_id
        $params[] = 0;          // is_delete
        // SELECT実行
        $result_Supple_Info_Table = $this->Db->execute($query, $params);
        if($result_Supple_Info_Table === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        // レコード格納
        $Result_List['item_attr'][$idx] = $result_Supple_Info_Table;

        return true;
    }

    /**
     * [[アイテムIDとアイテム通番にて指定されるサプリテーブルデータ削除]]
     */
    function deleteSuppleInfoTableData($Item_ID,$Item_No,$user_ID,&$Error_Msg){
        // サプリテーブルにレコードがあるか判定
        $query = "SELECT * ".       // 属性値
                 "FROM ". DATABASE_PREFIX ."repository_supple ".    //サプリテーブル
                 "WHERE item_id = ? AND ".      // アイテムID
                 "item_no = ?  AND ".           // アイテム通番
                 "is_delete = ?; ";             // 削除されていない
        $params = null;
        // $queryの?を置き換える配列
        $params[] = $Item_ID;
        $params[] = $Item_No;
        $params[] = 0;
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result) > 0){
            //レコードがあるため、削除実行
            $query = "UPDATE ". DATABASE_PREFIX ."repository_supple ".
                     "SET mod_user_id = ?, ".
                     "mod_date = ?, ".
                     "del_user_id = ?, ".
                     "del_date = ?, ".
                     "is_delete = ? ".
                     "WHERE item_id = ? AND ".
                     "item_no = ?; ";
            $params = null;
            $params[] = $user_ID;               // mod_user_id
            $params[] = $this->TransStartDate;  // mod_date
            $params[] = $user_ID;               // del_user_id
            $params[] = $this->TransStartDate;  // del_date
            $params[] = 1;                      // is_delete
            $params[] = $Item_ID;               // item_id
            $params[] = $Item_No;               // item_no
            //UPDATE実行
            $result = $this->Db->execute($query,$params);
            if($result === false){
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $Error_Msg = $this->Db->ErrorMsg();
                //エラー処理を行う
                //$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );   //主メッセージとログIDを指定して例外を作成
                //$DetailMsg = null;                              //詳細メッセージ文字列作成
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                //$exception->setDetailMsg( $DetailMsg );            //詳細メッセージ設定
                $this->failTrans();                                     //トランザクション失敗を設定(ROLLBACK)
                //throw $exception;
                return false;
            }
        }

        return true;

    }
    // Add input type "supple" 2009/08/24 A.Suzuki --end--

    // Add get edit item data for supple 2009/09/28 A.Suzuki --start--
    /**
     * 編集するアイテム情報をセッションに保存する
     * サプリコンテンツワークフローからの編集時に呼ばれる
     *
     */
    function getEditItemDataForSupple($item_id, $item_no){
        //-----------------------------------------------------------------------
        // セッション情報一覧
        // 【★新規作成･編集中で普遍な情報】 : 本アクションで初期設定して以降、DB登録もしくは更新まで普遍な情報である。
        // 1.アイテムタイプ (1レコード) : "item_type_all"[''], アイテムタイプのレコードをそのまま保存したものである。
        // 2.アイテム属性タイプ (Nレコード, Order順) : "item_attr_type"[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
        // 3.アイテム属性選択肢数 (N) : "item_num_cand"[N], 選択肢のない属性タイプは0を設定
        // 4.アイテム属性選択肢 (N): "option_data"[N][M], N属性タイプごとの選択肢。Mはアイテム属性選択肢数に対応。0～
        // X.ファイル属性フラグ : "isfile", 0:file, thumbnailなし, 1:thumbnailのみあり, 2:"file"あり
        // 【★アイテム編集で変化するメタデータ情報】 : 編集過程で逐次変化していく情報である。
        // 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
        // 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～
        // 【★アイテム編集で変化するアイテム基本情報】 : 編集過程で逐次変化していく情報である。
        // X.現在のアイテム基本情報のデータ : "base_attr"[2], (今のところタイトルと言語のユーザー入力値)
        // 【★インデックス選択とリンク設定情報】 : アイテムファミリー外の設定に必要
        // X.現在選択中のインデックス配列    : "indice"[L], L : 関連付けるインデックスの数, キーワードとインデックスIDをおさえておけばよいか。
        // X.現在選択中のリンクアイテム配列 : "link"[L], L : リンクを張るアイテムの数, アイテム名とアイテムIDをおさえておけばよいか。(いや、どうせDB検索と絡むのでレコード全部)
        // 【★その他】
        // X.ライセンスマスタ  : license_master
        // X.処理モード : edit_flag (0:新規作成, 1:既存編集)
        // X.編集中のアイテムID : edit_item_id (既存編集時のみ)
        // X.編集中のアイテムNO : edit_item_no (既存編集時のみ)
        // X.DB登録済み削除ファイルリスト : delete_file_list (既存編集時のみ)
        // X.アイテム公開日 : item_pub_date
        // X.アイテムキーワード : item_keyword
        // X.インデックス選択画面のツリー開閉情報 : open_node_index_id_link
        // X.アイテム間リンク設定画面のツリー開閉情報 : open_node_index_id_index
        // ----------------------------------------------------------------------
        // リクエストパラメタから処理モード設定
        // ※ただし、セッションに処理フラグがある場合は読み込まない
        $item_type = null;
        $item_attr_type = null;
        $Result_List;       // DBから取得したレコードの集合
        $Error_Msg;         // エラーメッセージ
        $edit_flag = 1;     // 処理モード (0:新規作成, 1:既存編集)

        // DB登録後に戻る画面情報
        $this->Session->setParameter("return_screen", 1);
        // アイテム情報を取得
        $result = $this->getItemData($item_id, $item_no, $Result_List, $Error_Msg);
        if($result === false){
            if($istest) { echo $Error_Msg . "<br>"; }
            return 'error';
        }
        // 所属インデックス取得
        $result = $this->getItemIndexData($item_id, $item_no, $Result_List, $Error_Msg);
        if($result === false){
            if($istest) { echo $Error_Msg . "<br>"; }
            return 'error';
        }
        // 参照取得
        $result = $this->getItemReference($item_id, $item_no, $Result_List, $Error_Msg);
        if($result === false){
            if($istest) { echo $Error_Msg . "<br>"; }
            return 'error';
        }
        // アイテム更新日精査
        if( $this->TransStartDate < $Result_List['item'][0]['mod_date'] ) {
            $this->Session->setParameter("error_msg", "error : probably this item was updated by other admin.");
            $this->failTrans();                //トランザクション失敗を設定(ROLLBACK)
            return 'error';
        }
        // 値のコピー
        $itemtype_id = $Result_List['item_type'][0]['item_type_id'];
        $item_type_info = array('item_type_name' => $Result_List['item_type'][0]['item_type_name'],
                                'item_type_id' => $Result_List['item_type'][0]['item_type_id']
                          );
        $this->Session->setParameter("item_type_all", $item_type_info);
        $item_attr_type = $Result_List['item_attr_type'];

        // 編集中のアイテムID, NOを保存
        $this->Session->setParameter("edit_item_id", $item_id);
        $this->Session->setParameter("edit_item_no", $item_no);
        $this->Session->setParameter("delete_file_list", array());
        $this->Session->setParameter("edit_start_date", $this->TransStartDate); // 編集開始時間
        $this->Session->setParameter("edit_flag", $edit_flag);

        // ライセンスマスタを保存
        $license_master = $this->Db->selectExecute("repository_license_master", array('is_delete'=>0));
        if ($license_master === false) {
            return 'error';
        }
        $this->Session->setParameter("license_master",$license_master);
        // 現在のアイテム基本情報のデータ : "base_attr"[2(今のところタイトルと言語)](kawa)
        $lang = 'en';
        if($this->Session->getParameter("_lang") == "japanese"){
            $lang = 'ja';
        }
        $base_attr = array(
            "title" => '',
            "title_english" => '',
            "language" => $lang
        );
        //$now = mktime();          // 現在時刻(タイムスタンプ)
        //$item_pub_date = array(
        //              'year' => intval(date('Y',$now)),
        //              'month' => intval(date('m',$now)),
        //              'day' => intval(date('d',$now)));
        $DATE = new Date();
        $item_pub_date = array(
                        'year' => intval($DATE->getYear()),
                        'month' => intval($DATE->getMonth()),
                        'day' => intval($DATE->getDay()));

        $item_keyword = '';         // アイテムキーワード
        $item_keyword_english = '';         // アイテムキーワード
        $item_element = array();    // アイテム属性タイプ
        $item_num_cand = array();   // アイテム属性選択肢数
        $option_data = array();     // アイテム属性選択肢
        $show_no = 1;
        // アイテム属性タイプ情報を表示順に並び換える。
        // またselect, radioの場合は選択肢の数と
        for ($ii = 0; $ii < count($item_attr_type); $ii++) {
            for ($ii2 = 0; $ii2 < count($item_attr_type); $ii2++) {
                if ($item_attr_type[$ii2]['show_order'] == $show_no) {
                    $num_cand = 0;      // 選択肢の数
                    $options = array(); // 選択肢
                    // アイテム属性タイプの共通項目の設定
                    $params_attr = array(
                            'attribute_id' => $item_attr_type[$ii2]['attribute_id'],
                            'attribute_name' => $item_attr_type[$ii2]['attribute_name'],
                            'input_type' => $item_attr_type[$ii2]['input_type'],
                            'is_required' => $item_attr_type[$ii2]['is_required'],
                            'plural_enable' => $item_attr_type[$ii2]['plural_enable'],
                            'line_feed_enable' => $item_attr_type[$ii2]['line_feed_enable'],
                            'plural_enable' => $item_attr_type[$ii2]['plural_enable'],
                            'dublin_core_mapping' => $item_attr_type[$ii2]['dublin_core_mapping'],
                            'junii2_mapping' => $item_attr_type[$ii2]['junii2_mapping'],
                            'lom_mapping' => $item_attr_type[$ii2]['lom_mapping'],
                            'display_lang_type' => $item_attr_type[$ii2]['display_lang_type']
                    );
                    // check, radioは選択肢とその数を取得
                    if($item_attr_type[$ii2]['input_type'] == "select" ||
                       $item_attr_type[$ii2]['input_type'] == "checkbox" ||
                       $item_attr_type[$ii2]['input_type'] == "radio") {
                        $params_cand = array(
                                'item_type_id'=>$itemtype_id,
                                'attribute_id'=>$item_attr_type[$ii2]['attribute_id'],
                                'is_delete'=>0);
                        $option = $this->Db->selectExecute("repository_item_attr_candidate",$params_cand);
                        if ($option === false) {
                            return 'error';
                        }
                        $num_cand = count($option);
                        for($ii3=0; $ii3<$num_cand ; $ii3++) {
                            array_push($options, $option[$ii3]['candidate_value']);
                        }
                    } else {
                    }
                    array_push( $item_num_cand, $num_cand);     // アイテム属性タイプごとの選択支の数を設定
                    array_push( $option_data, $options);
                    array_push( $item_element, $params_attr);
                    $show_no++;
                    break;
                }
            }
        }
        // アイテム属性タイプ情報をセッションに保存
        $this->Session->setParameter("item_attr_type",$item_element);   // アイテム属性タイプ
        $this->Session->setParameter("item_num_cand",$item_num_cand);   // アイテム属性選択肢数
        $this->Session->setParameter("option_data",$option_data);       // アイテム属性選択肢

        // アイテム属性数／アイテム属性を初期設定
        $item_num_attr = array_fill(0, count($item_attr_type), 1);      // アイテム属性数 [N]
        $item_attr = array_fill(0, count($item_attr_type), array(''));  // アイテム属性 [N][L]
        // 現在のアイテム属性を初期設定 (kawa)
        // 新規作成 ⇒ 空文字列 or ON/OFF
        $isfile = 0;        // file存在フラグ
        $file_num = 0;      // 'file'メタデータ数
        $thumbnail_num = 0; // 'thumbnail'メタデータ数
        for ($ii = 0; $ii < count($item_attr); $ii++) {
            // チェックボックス : 初期値OFF
            if( $item_element[$ii]['input_type'] == "checkbox" ) {
                $check_init = array();
                for ($jj = 0; $jj < count($option_data[$ii]); $jj++) {
                    array_push($check_init, "0");   // "0":OFF
                }
                $item_attr[$ii] = $check_init;
            }
            // ラジオボタン : 初期値は最初の選択肢
            if( $item_element[$ii]['input_type'] == "radio" ) {
                $item_attr[$ii][0] = 0;     // 0-th candidate is checked.
            }
            // 氏名 : 姓、名、Emailの初期値をセット
            if( $item_element[$ii]['input_type'] == "name" ) {
                $name_init = array(array(
                        'family' => '',
                        'given' => '',
                        'email' => ''
                    )
                );
                $item_attr[$ii] = $name_init;
            }
            // ファイル : null = 未登録
            if( $item_element[$ii]['input_type'] == "file"){
                $item_attr[$ii][0] = array('file_no' => 1);
                $file_num++;
            }
            // 課金ファイル : null = 未登録
            if($item_element[$ii]['input_type'] == "file_price") {
                $item_attr[$ii][0] = array('file_no' => 1);
                $file_num++;
            }
            // サムネイル : null = 未登録
            if( $item_element[$ii]['input_type'] == "thumbnail" ) {
                $item_attr[$ii][0] = array('file_no' => 1);
                $thumbnail_num++;
            }
        }
        // isfile設定
        if($file_num>0){
            $isfile = 2;
        } elseif($thumbnail_num>0){
            $isfile = 1;
        }
        // インデックス設定／リンク設定用変数の設定
        $indice = array();
        $link = array();

        // 基本情報
        $base_attr = array(
            "title" => $Result_List['item'][0]['title'],
            "title_english" => $Result_List['item'][0]['title_english'],
            "language" => $Result_List['item'][0]['language']
        );
        // アイテムの公開日が設定されている場合、年月日に分解
        if( $Result_List['item'][0]['shown_date'] != null &&
            $Result_List['item'][0]['shown_date'] != '' ) {
            $pub_date = split('[ ]', $Result_List['item'][0]['shown_date']);
            list($pub_year, $pub_month, $pub_day) = split('[/.-]', $pub_date[0]);
            $item_pub_date = array(
                        'year' => intval($pub_year),
                        'month' => intval($pub_month),
                        'day' => intval($pub_day));
        }
        // アイテムキーワード
        $item_keyword = $Result_List['item'][0]['serch_key'];
        $item_keyword_english = $Result_List['item'][0]['serch_key_english'];
        // メタデータ
        for ($ii = 0; $ii < count($item_element); $ii++) {
            // 属性未登録のメタデータは初期設定でOK
            if(count($Result_List['item_attr'][$ii]) <= 0) {
                continue;
            }
            $edit_attr = array();

            // チェックボックス : 属性にはチェックの入った選択肢の文字列が入っている
            // 選択肢と同数の配列を確保。チェックの入った選択肢に1を、入ってない選択肢に0を設定。
            if( $item_element[$ii]['input_type'] == "checkbox" ) {
                for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                    $isGot = false;
                    for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                        if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                            array_push($edit_attr, 1);      // kk-th選択肢は選択済み
                            $isGot = true;
                            break;
                        }
                    }
                    if(!$isGot) {
                        array_push($edit_attr, 0);      // kk-th選択肢は未選択
                    }
                }
                // 初期属性の再セット
                $item_attr[$ii] = $edit_attr;
                continue;
            }

            // ii-thメタデータのjj-th属性をコピー
            for ($jj = 0; $jj < count($Result_List['item_attr'][$ii]); $jj++) {
                // ラジオボタン :
                if( $item_element[$ii]['input_type'] == "radio" ) {
                    // 値の一致したオプションのインデックスをセット
                    for ($kk = 0; $kk < count($option_data[$ii]); $kk++) {
                        if($option_data[$ii][$kk] == $Result_List['item_attr'][$ii][$jj]['attribute_value']) {
                            array_push($edit_attr, $kk);        // 0-th candidate is checked.
                            break;
                        }
                    }
                }
                // 氏名 : 姓、名、Emailの初期値をセット
                elseif( $item_element[$ii]['input_type'] == "name" ) {
                    $name_init = array(
                        'family' => $Result_List['item_attr'][$ii][$jj]['family'],
                        'given' => $Result_List['item_attr'][$ii][$jj]['name'],
                        'email' => $Result_List['item_attr'][$ii][$jj]['e_mail_address']
                    );
                    array_push($edit_attr, $name_init);
                }
                // 書誌情報：雑誌名、巻、号、ページ、発行年の初期値をセット
                elseif( $item_element[$ii]['input_type'] == "biblio_info" ) {
                    $date = explode("-", $Result_List['item_attr'][$ii][$jj]['date_of_issued']);
                    if(count($date) == 2){
                        array_push($date, "");
                    } else if(count($date) == 1){
                        array_push($date, "");
                        array_push($date, "");
                    }

                    if(strlen($date[1]) == 2){
                        $temp_month = str_split($date[1]);
                        if($temp_month[0] == "0"){
                            $date[1] = $temp_month[1];
                        }
                    }
                    if(strlen($date[2]) == 2){
                        $temp_day = str_split($date[2]);
                        if($temp_day[0] == "0"){
                            $date[2] = $temp_day[1];
                        }
                    }

                    $biblio_init = array(
                        'biblio_name' => $Result_List['item_attr'][$ii][$jj]['biblio_name'],
                        'biblio_name_english' => $Result_List['item_attr'][$ii][$jj]['biblio_name_english'],
                        'volume' => $Result_List['item_attr'][$ii][$jj]['volume'],
                        'issue' => $Result_List['item_attr'][$ii][$jj]['issue'],
                        'spage' => $Result_List['item_attr'][$ii][$jj]['start_page'],
                        'epage' => $Result_List['item_attr'][$ii][$jj]['end_page'],
                        'date_of_issued' => $Result_List['item_attr'][$ii][$jj]['date_of_issued'],
                        'year' =>$date[0],
                        'month' =>$date[1],
                        'day' =>$date[2]
                    );
                    array_push($edit_attr, $biblio_init);
                }

                // 日付：年月日の初期値をセット
                elseif( $item_element[$ii]['input_type'] == "date" ) {
                    $date = explode("-", $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                    if(count($date) == 2){
                        array_push($date, "");
                    } else if(count($date) == 1){
                        array_push($date, "");
                        array_push($date, "");
                    }

                    if(strlen($date[1]) == 2){
                        $temp_month = str_split($date[1]);
                        if($temp_month[0] == "0"){
                            $date[1] = $temp_month[1];
                        }
                    }
                    if(strlen($date[2]) == 2){
                        $temp_day = str_split($date[2]);
                        if($temp_day[0] == "0"){
                            $date[2] = $temp_day[1];
                        }
                    }

                    $date_init = array(
                        'date' => $Result_List['item_attr'][$ii][$jj]['attribute_value'],
                        'date_year' =>$date[0],
                        'date_month' =>$date[1],
                        'date_day' =>$date[2],
                    );
                    array_push($edit_attr, $date_init);
                }

                // ファイル : エンバーゴ、ライセンスなどセット
                elseif( $item_element[$ii]['input_type'] == "file" ||
                    $item_element[$ii]['input_type'] == "file_price") { // Add file price Y.Nakao 2008/08/28
                    // upload情報を作成
                    $upload = array(
                        'file_name' => $Result_List['item_attr'][$ii][$jj]['file_name'],
                        'mimetype' => $Result_List['item_attr'][$ii][$jj]['mime_type'],
                        'extension' => $Result_List['item_attr'][$ii][$jj]['extension']
                    );
                    // 2008/03/21 エンバーゴフラグ設定
                    // 既存編集の場合は既にファイルの公開日は一意に決まっているため、"オープンアクセス日を指定する"扱いとする
                    // file情報作成
                    if($item_element[$ii]['input_type'] == "file"){
                        $file_init = array(
                            'upload' => $upload,
                            'licence' => 'licence_free',
                            'freeword' => '',
                            'embargo_flag' => 2,    // オープンアクセス日を指定する
                            'embargo_year' => '',
                            'embargo_month' => '',
                            'embargo_day' => '',
                            'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                            // プライマリキー保存(削除用)
                            'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                            'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                            'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                            'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                            'input_type' => 'file',
                        );
                    } else if($item_element[$ii]['input_type'] == "file_price"){
                        // room_idとpriceを分解する
                        // room_id,price|room_id,price|room_id,price|...で格納されている
                        $room_price = explode("|",$Result_List['item_attr'][$ii][$jj]['price']);
                        $price_num = 0;
                        $price_value = array();
                        $room_id = array();
                        for($price_Cnt=0;$price_Cnt<count($room_price);$price_Cnt++){
                            $price = split(",", $room_price[$price_Cnt]);
                            // room_idと価格のペアがあり、価格が数字であるか判定
                            if($price!=null && count($price)==2)
                            {
                                array_push($room_id, $price[0]);
                                array_push($price_value, $price[1]);
                                $price_num++;
                            }
                        }
                        // ファイル情報格納
                        $file_init = array(
                            'upload' => $upload,
                            'licence' => 'licence_free',
                            'freeword' => '',
                            'embargo_flag' => 2,    // オープンアクセス日を指定する
                            'embargo_year' => '',
                            'embargo_month' => '',
                            'embargo_day' => '',
                            'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                            // プライマリキー保存(削除用)
                            'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                            'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                            'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                            'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                            'input_type' => 'file_price',
                            'room_id' => $room_id,
                            'price_value' => $price_value,
                            'price_num' => $price_num
                        );
                    }
                    // ライセンス設定
                    if($Result_List['item_attr'][$ii][$jj]['license_id'] == 0) {
                        $file_init['licence'] = 'licence_free';
                        $file_init['freeword'] = $Result_List['item_attr'][$ii][$jj]['license_notation'];
                    } else {
                        // CreativeCommonsの場合, ライセンスIDの一致したライセンスマスタインデックス
                        for ($kk = 0; $kk < count($license_master); $kk++) {
                            if($license_master[$kk]['license_id'] == $Result_List['item_attr'][$ii][$jj]['license_id']) {
                                $file_init['licence'] = $kk;
                                break;
                            }
                        }
                    }
                    // エンバーゴ設定
                    // XXXX-XX-XX 形式の日付を年月日に分解
                    $year = '';
                    $month = '';
                    $day = '';
                    $embargo = split(' ', $Result_List['item_attr'][$ii][$jj]['pub_date']);
                    $embargo = split('[/.-]', $embargo[0]);
                    if(count($embargo) > 0) { $year = $embargo[0]; }
                    if(count($embargo) > 1) { $month = $embargo[1]; }
                    if(count($embargo) > 2) { $day = $embargo[2]; }
                    $file_init['embargo_year'] = $year;
                    $file_init['embargo_month'] = $month;
                    $file_init['embargo_day'] = $day;
                    $now_date = explode( " ", $this->TransStartDate );
                    $now_date_array = explode( "-", $now_date[0] );
                    //$pub_time = mktime( 0, 0, 0, intval( $month ), intval( $day ), intval( $year ) );
                    //$now_time = mktime( 0, 0, 0, intval( $now_date_array[1] ), intval( $now_date_array[2] ), intval( $now_date_array[0] ) );
                    $diff = Date_Calc::compareDates(intval($now_date_array[2]),intval($now_date_array[1]),intval($now_date_array[0]),intval($day),intval($month),intval($year));
                    if($year == "9999" && $month == "1" && $day == "1"){
                        // 会員のみ
                        $file_init['embargo_flag'] = 3;
                        // 表示されるオープンアクセス日指定日時は表示日時
                        $file_init['embargo_year'] = $item_pub_date["year"];
                        $file_init['embargo_month'] = $item_pub_date["month"];
                        $file_init['embargo_day'] = $item_pub_date["day"];
                    }
                    // when today after file's pub_date, this file is open access
                    // get file's pub date
                    //else if ( $now_time >= $pub_time ) {
                    else if ( $diff == 0 || $diff == 1 ) {
                        // check file is pub auther item insert user
                        $file_init['embargo_flag'] = 1;
                        // display date is today
                        $file_init['embargo_year'] = $item_pub_date["year"];
                        $file_init['embargo_month'] = $item_pub_date["month"];
                        $file_init['embargo_day'] = $item_pub_date["day"];
                    } else {
                        $file_init['embargo_flag'] = 2;
                    }

                    // ファイル通番の穴埋め
                    if(count($edit_attr) != $file_init['file_no']){
                        for($cnt=count($edit_attr);$cnt<$file_init['file_no']-1;$cnt++){
                            array_push($edit_attr, array('file_no' => $cnt+1));
                        }
                        array_push($edit_attr, $file_init);
                    }
                }
                // サムネイル : null = 未登録
                elseif( $item_element[$ii]['input_type'] == "thumbnail" ) {
                    // file情報作成
                    $file_init = array(
                        'upload' => array(
                            'file_name' => $Result_List['item_attr'][$ii][$jj]['file_name'],
                            'mimetype' => $Result_List['item_attr'][$ii][$jj]['mime_type'],
                            'extension' => $Result_List['item_attr'][$ii][$jj]['extension']
                        ),
                        'is_db_exist' => 1,     // DBにこのレコードが登録されている証
                        // プライマリキー保存(削除用)
                        'item_id' => $Result_List['item_attr'][$ii][$jj]['item_id'],
                        'item_no' => $Result_List['item_attr'][$ii][$jj]['item_no'],
                        'attribute_id' => $Result_List['item_attr'][$ii][$jj]['attribute_id'],
                        'file_no' => $Result_List['item_attr'][$ii][$jj]['file_no'],
                        'input_type' => 'thumbnail'
                    );
                    // ファイル通番の穴埋め
                    if(count($edit_attr) != $file_init['file_no']){
                        for($cnt=count($edit_attr);$cnt<$file_init['file_no']-1;$cnt++){
                            array_push($edit_attr, array('file_no' => $cnt+1));
                        }
                        array_push($edit_attr, $file_init);
                    }
                }
                // その他テキスト系
                else {
                    array_push($edit_attr, $Result_List['item_attr'][$ii][$jj]['attribute_value']);
                }
            }
            // 初期属性の再セット
            $item_attr[$ii] = $edit_attr;
            $item_num_attr[$ii] = count($edit_attr);
        }
        $indice = $Result_List['position_index'];
        for($ii=0; $ii<count($indice); $ii++){
            $query = "SELECT index_name, index_name_english FROM ".DATABASE_PREFIX."repository_index ".
                     "WHERE index_id = ".$indice[$ii]["index_id"]." ;";
            $ret = $this->Db->execute($query);
            if($ret === false || count($ret)!=1){
                return false;
            }
            if($this->Session->getParameter("_lang") == "japanese"){
                $indice[$ii]["index_name"] = $ret[0]["index_name"];
            } else {
                $indice[$ii]["index_name"] = $ret[0]["index_name_english"];
            }
        }

        // リンクの読み込み(リンク先のアイテムID, アイテムNo, タイトルを保存・・・いや全部持っておけばいいか)
        $link = array();
        for ($ii = 0; $ii < count($Result_List['reference']); $ii++) {
            // リンク先アイテム情報取得
            $dest_item = array();
            $result = $this->getItemTableData(
                            $Result_List['reference'][$ii]['dest_reference_item_id'],
                            $Result_List['reference'][$ii]['dest_reference_item_no'],
                            $dest_item, $Error_Msg);
            if($result === false){
                if($istest) { echo $Error_Msg . "<br>"; }
                return 'error';
            }
            // リンク先アイテムレコード+αを保存
            if($istest) { echo "index_item_cnt ". count($dest_item['item']) . "<br>"; }
            if( count($dest_item['item']) > 0) {
                // リンク先アイテムのアイテムタイプを取得
                $query = "SELECT * ".
                         "FROM ". DATABASE_PREFIX ."repository_item_type ".     // アイテムタイプ
                         "WHERE item_type_id = ? AND ".
                         "is_delete = ?; ";         // 削除フラグ
                $params = null;
                $params[] = $dest_item['item'][0]['item_type_id'];
                $params[] = 0;
                // SELECT実行
                $dest_item_type = $this->Db->execute($query, $params);
                if($dest_item_type === false){
                    $error_msg = $this->Db->ErrorMsg();
                    $this->Session->setParameter("error_cord",-1);
                    return 'error';
                }
                $dest_item['item'][0]['item_type_name'] = $dest_item_type[0]['item_type_name'];
                $dest_item['item'][0]['relation'] = $Result_List['reference'][$ii]['reference'];
                array_push($link , $dest_item['item'][0]);
            }
        }

        // アイテム属性数／アイテム属性をセッションに保存
        $this->Session->setParameter("isfile",$isfile);
        $this->Session->setParameter("item_num_attr",$item_num_attr);
        $this->Session->setParameter("item_attr",$item_attr);
        $this->Session->setParameter("base_attr",$base_attr);
        $this->Session->setParameter("item_pub_date",$item_pub_date);
        $this->Session->setParameter("item_keyword",$item_keyword);
        $this->Session->setParameter("item_keyword_english",$item_keyword_english);
        $this->Session->setParameter("indice",$indice);
        $this->Session->setParameter("link",$link);

        // ファイル属性が無ければメタデータ登録画面に遷移
        if ($isfile==0) {
            $this->Session->setParameter("attr_file_flg", 0);
            return 'texts';
        } else {
            $this->Session->setParameter("attr_file_flg", 1);
            return 'files';
        }
    }
    // Add get edit item data for supple 2009/09/28 A.Suzuki --end--

    // Add review mail setting 2009/09/24 Y.Nakao --start--
    /**
     * 査読通知メール送信に必要なメール情報を取得
     * get review mail info
     *
     * @param string $fromName
     * @param array $users
     * @return bool
     */
    function getReviewMailInfo(&$users){
        $users = array();       // メール送信先
        $query = "SELECT param_name, param_value ".
                " FROM ". DATABASE_PREFIX ."repository_parameter ".     // パラメタテーブル
                " WHERE is_delete = ? ".
                " AND param_name = ? ";
        $params = array();
        $params[] = 0;
        $params[] = 'review_mail';
        // SELECT実行
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }
        if(count($result) == 1){
            if($result[0]['param_name'] == 'review_mail'){
                $result[0]['param_value'] = str_replace("\r\n", "\n", $result[0]['param_value']);
                $email = explode("\n", $result[0]['param_value']);
                for($jj=0; $jj<count($email); $jj++){
                    if(strlen($email[$jj]) > 0){
                        array_push($users, array("email" => $email[$jj]));
                    }
                }

            }
        }

        return true;

    }
    // Add review mail setting 2009/09/24 Y.Nakao --end--

    /**
     * check any file can export status
     *
     */
    function checkExportFileDownload($status){

        // ログイン状態を判定
        $user_id = $this->Session->getParameter("_user_id");
        if($user_id == "0"){
            // ログインしていない
            if($status == "login") {
                // ログイン処理実行
                $status = "download";
            } else {
                // エラー
                $status = "false";
            }
        } else {
            if($status == "download"){
                // ログインOK
                $status = "true";
            } else {
                // エラー
                $status = "false";
            }
        }
        return $status;

    }

    /**
     * Get file save path by config
     *
     * @param string "file" or "flash"
     * @return string FileSavePath
     */
    function getFileSavePath($mode){
        $config = parse_ini_file(BASE_DIR.'/webapp/modules/repository/config/main.ini');
        $path = "";
        if($mode == "file"){
            $path = $config["define:_REPOSITORY_FILE_SAVE_PATH"];
        } else if($mode == "flash"){
            $path = $config["define:_REPOSITORY_FLASH_SAVE_PATH"];
        }
        return $path;
    }


    /**
     *  Get administrator's value that related to parameter name in parameter table
     *
     * @param $param_name parameter name
     * @param $param_value value
     * @param $error_msg error message
     * @return result(true or false)
     */
    function getAdminParam($param_name, &$param_value, &$error_msg)
    {
        $param_value = "";
        // get param_value that correspond to param_name
        $query = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".
                "WHERE param_name = ? ".
                "AND is_delete = ?; ";
        $params = array();
        $params[0] = $param_name;
        $params[1] = 0;
        // execute SQL
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        if(count($result)!= 1)
        {
            $error_msg = "The parameter is illegal. there is a lot of data gotten.";
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        $param_value = $result[0]['param_value'];
        return true;
    }

    // check supple link 2010/03/16 Y.Nakao --start--
    function checkSuppleWEKOlink($supple_table = false){
        // there has supple WEKO url and got prefix ID

        // request URL send for supple weko
        $query = "SELECT param_value FROM ".DATABASE_PREFIX."repository_parameter ".
                 "WHERE param_name = 'supple_weko_url';";
        $result = $this->Db->execute($query);
        if($result === false){
            return false;
        } else if(strlen($result[0]['param_value']) == 0){
            return false;
        }

        require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
        
        $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
        
        if(strlen($prefixID) == 0) {
            return false;
        }
        
        // supple table data check
        if($supple_table){
            // get supple table info
            $query = "SELECT * FROM ".DATABASE_PREFIX."repository_supple ".
                     "WHERE is_delete = 0;";
            $result = $this->Db->execute($query);
            if(count($result) == 0){
                return false;
            }
        }

        return true;

    }
    // check supple link 2010/03/16 Y.Nakao --end--

    /**
     * 1アイテムの全文検索テーブルを作成・更新する。(metadetaのみ)
     *
     * @param   $item_id
     * @param   $item_no
     * @param   $update_flg
     */
    function updateFullTextTable($item_id, $item_no, $update_flg){
        ////////////////////////////////////////////////////////////
        // 追加属性をカンマ区切りにし、検索用文字列を作成する
        ////////////////////////////////////////////////////////////
        // 追加属性を全て取得
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;

        // アイテム情報を取得
        $strMetadata = "";
        $query = "SELECT title, title_english, serch_key, serch_key_english ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_item = $this->Db->execute($query, $params);
        if($ret_item === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_item) != 0){
            $strMetadata .= $ret_item[0]['title'].",". $ret_item[0]['title_english'].",". $ret_item[0]['serch_key'].",". $ret_item[0]['serch_key_english'];
        }

        // アイテム属性を全て取得
        $query = "SELECT attribute_value ".
                 "FROM ".DATABASE_PREFIX."repository_item_attr ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_attr = $this->Db->execute($query, $params);
        if($ret_attr === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_attr) != 0){
            // カンマ区切りで結果配列を連結
            for($nCnt=0;$nCnt<count($ret_attr);$nCnt++){
                $strMetadata .= ",". $ret_attr[$nCnt]['attribute_value'];
            }
        }

        // ファイル名を全て取得
        $query = "SELECT attribute_id, file_name, file_no ".
                 "FROM ".DATABASE_PREFIX."repository_file ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_file_name = $this->Db->execute($query, $params);
        if($ret_file_name === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_file_name) != 0){
            // カンマ区切りで結果配列を連結
            for($nCnt=0;$nCnt<count($ret_file_name);$nCnt++){
                $strMetadata .= ",". $ret_file_name[$nCnt]['file_name'];
            }
        }

        // サムネイルファイル名を全て取得
        $query = "SELECT attribute_id, file_name, file_no ".
                 "FROM ".DATABASE_PREFIX."repository_thumbnail ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_thumbnail_name = $this->Db->execute($query, $params);
        if($ret_thumbnail_name === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_thumbnail_name) != 0){
            // カンマ区切りで結果配列を連結
            for($nCnt=0;$nCnt<count($ret_thumbnail_name);$nCnt++){
                $strMetadata .= ",". $ret_thumbnail_name[$nCnt]['file_name'];
            }
        }

        // 氏名を全て取得
        $query = "SELECT family, name, e_mail_address ".
                 "FROM ".DATABASE_PREFIX."repository_personal_name ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_personal_name = $this->Db->execute($query, $params);
        if($ret_personal_name === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_personal_name) != 0){
            // カンマ区切りで結果配列を連結
            for($nCnt=0;$nCnt<count($ret_personal_name);$nCnt++){
                $strMetadata .= ",". $ret_personal_name[$nCnt]['family'].
                                ",". $ret_personal_name[$nCnt]['name'].
                                ",". $ret_personal_name[$nCnt]['e_mail_address'];
            }
        }

        // 書誌情報を全て取得
        $query = "SELECT biblio_name, biblio_name_english, volume, issue, start_page, end_page, date_of_issued ".
                 "FROM ".DATABASE_PREFIX."repository_biblio_info ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = 0 ;";
        $ret_biblio_info = $this->Db->execute($query, $params);
        if($ret_biblio_info === false){
            // SQLエラー
            $this->failTrans();
            return false;
        } else if(count($ret_biblio_info) != 0){
            // カンマ区切りで結果配列を連結
            for($nCnt=0;$nCnt<count($ret_biblio_info);$nCnt++){
                $strMetadata .= ",". $ret_biblio_info[$nCnt]['biblio_name'].
                                ",". $ret_biblio_info[$nCnt]['biblio_name_english'].
                                ",". $ret_biblio_info[$nCnt]['volume'].
                                ",". $ret_biblio_info[$nCnt]['issue'].
                                ",". $ret_biblio_info[$nCnt]['start_page'].
                                ",". $ret_biblio_info[$nCnt]['end_page'].
                                ",". $ret_biblio_info[$nCnt]['date_of_issued'];
            }
        }

        ////////////////////////////////////////////////////////////
        // 検索用文字列をDBへ登録する
        ////////////////////////////////////////////////////////////
        $params = null;
        //fulltextテーブルへINSERTする
        if ($update_flg === false) {
            $query = "INSERT INTO ".DATABASE_PREFIX."repository_fulltext_data(item_id, item_no, metadata, ins_user_id, ins_date, mod_user_id, mod_date) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?);";
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = $strMetadata;
            $params[] = $this->Session->getParameter("_user_id");
            $params[] = $this->TransStartDate;
            $params[] = $this->Session->getParameter("_user_id");
            $params[] = $this->TransStartDate;
            $ret_item = $this->Db->execute($query, $params);
            if($ret_attr === false){
                // SQLエラー
                $this->failTrans();
                return false;
            }
        }
        else {
            $query = "UPDATE ". DATABASE_PREFIX ."repository_fulltext_data ".
                     "SET metadata = ?, ".
                     "mod_user_id = ?, ".
                     "mod_date = ? ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ;";
            $params[] = $strMetadata;
            $params[] = $this->Session->getParameter("_user_id");
            $params[] = $this->TransStartDate;
            $params[] = $item_id;
            $params[] = $item_no;
            $ret_item = $this->Db->execute($query, $params);
            if($ret_attr === false){
                // SQLエラー
                $this->failTrans();
                return false;
            }
        }
    }

    /**
     * 全文検索テーブルを再構築する。
     */
    function rebuildFullTextTable(){
        $query = 'SELECT full.item_id, full.item_no '.
                 'FROM '.DATABASE_PREFIX.'repository_item AS item, '.DATABASE_PREFIX.'repository_fulltext_data AS full '.
                 'WHERE item.item_id = full.item_id '.
                 'AND item.item_no = full.item_no '.
                 'AND item.is_delete = 0; ';
        $result = $this->Db->execute($query);
        if($result === false){
            $this->failTrans();
            return false;
        }

        for($ii=0; $ii<count($result); $ii++){
            $this->updateFullTextTable($result[$ii]["item_id"], $result[$ii]["item_no"], true);
        }
    }

    /**
     * 現在稼働しているNetCommonsのバージョンを取得する
     *
     * @return string $version
     */
    function getNCVersion(){
        // now version
        $container =& DIContainerFactory::getContainer();
        $configView =& $container->getComponent("configView");
        $config_version = $configView->getConfigByConfname(_SYS_CONF_MODID, "version");
        if(isset($config_version) && isset($config_version['conf_value'])) {
            $version = $config_version['conf_value'];
        } else {
            $version = _NC_VERSION;
        }
        return $version;
    }

    /**
     * Get file icon ID from mimetype or extention.
     *
     * @param string $mimetype
     * @param string $extention
     * @return int icon_id
     */
    function getFileIconID($mimetype, $extention=null){
        $icon_id = 0;
        // Check MIME type
        if(preg_match("/text/",$mimetype)){
            // Text file
            $icon_id = 1;
        }else if(preg_match("/^application\/pdf/",$mimetype)){
            // Pdf file
            $icon_id = 2;
        }else if((preg_match("/^application\/msword/",$mimetype))
            || (preg_match("/^application\/vnd\.openxmlformats-officedocument\.wordprocessingml/",$mimetype))){
            // Word file
            $icon_id = 3;
        }else if((preg_match("/^application\/vnd\.ms-excel/",$mimetype))
            || (preg_match("/^application\/vnd\.openxmlformats-officedocument\.spreadsheetml/",$mimetype))){
            // Excel file
            $icon_id = 4;
        }else if((preg_match("/^application\/vnd\.ms-powerpoint/",$mimetype))
            || (preg_match("/^application\/vnd\.openxmlformats-officedocument\.presentationml/",$mimetype))){
            // Powerpoint file
            $icon_id = 5;
        }else if((preg_match("/^application\/zip/",$mimetype))
            || (preg_match("/^application\/x-zip/",$mimetype))
            || (preg_match("/^application\/x-gzip/",$mimetype))
            || (preg_match("/^application\/gzip/",$mimetype))
            || (preg_match("/^application\/x-compress/",$mimetype))
            || (preg_match("/^application\/g-zip/",$mimetype))
            || (preg_match("/^application\/x-tar/",$mimetype))
            || (preg_match("/^application\/x-gtar/",$mimetype))
            || (preg_match("/^application\/x-stuffit/",$mimetype))){
            // Compressed file
            $icon_id = 6;
        }else if(preg_match("/^image/",$mimetype)){
            // Image file
            $icon_id = 7;
        }else if(preg_match("/^application\/x-shockwave/",$mimetype)){
            // Flash file
            $icon_id = 8;
        }else if(preg_match("/^audio/",$mimetype)){
            // Audio file
            $icon_id = 9;
        }else if(preg_match("/^video/",$mimetype)){
            // Video file
            $icon_id = 10;
        }else{
            // other MIME type
            if($extention != "" && $extention != null){
                $extention = strtolower($extention);
                switch($extention){
                    // Text file
                    case "c":
                    case "css":
                    case "csv":
                    case "htm":
                    case "html":
                    case "xbm":
                    case "shtml":
                    case "shtm":
                    case "txt":
                    case "text":
                    case "php":
                    case "rtf":
                    case "rtx":
                    case "tsv":
                    case "xml":
                    case "m":
                    case "h":
                        $icon_id = 1;
                        break;
                    // Pdf file
                    case "pdf":
                        $icon_id = 2;
                        break;
                    // Word file
                    case "doc":
                    case "docm":
                    case "dotm":
                    case "docx":
                    case "dotx":
                        $icon_id = 3;
                        break;
                    // Excel file
                    case "xls":
                    case "xlsm":
                    case "xltm":
                    case "xlam":
                    case "xlsx":
                    case "xltx":
                        $icon_id = 4;
                        break;
                    // Powerpoint file
                    case "ppt":
                    case "ppam":
                    case "pptm":
                    case "ppsm":
                    case "sldm":
                    case "potm":
                    case "ppsx":
                    case "potx":
                    case "sldx":
                    case "pptx":
                        $icon_id = 5;
                        break;
                    // Compressed file
                    case "gz":
                    case "tar":
                    case "zip":
                    case "gzip":
                    case "gtar":
                    case "lzh":
                    case "rar":
                    case "tgz":
                        $icon_id = 6;
                        break;
                    // Image file
                    case "bmp":
                    case "jpg":
                    case "jpeg":
                    case "gif":
                    case "pict":
                    case "png":
                    case "tiff":
                        $icon_id = 7;
                        break;
                    // Flash file
                    case "swf":
                    case "fla":
                        $icon_id = 8;
                        break;
                    // Audio file
                    case "au":
                    case "mp3":
                    case "wav":
                    case "aiff":
                    case "m3u":
                    case "rm":
                    case "ra":
                    case "midi":
                    case "wma":
                        $icon_id = 9;
                        break;
                    // Video file
                    case "avi":
                    case "mp4":
                    case "mpeg":
                    case "qt":
                    case "3gp":
                    case "dv":
                    case "asf":
                    case "wmv":
                    case "movie":
                    case "flv":
                        $icon_id = 10;
                        break;
                    default:
                        break;
                }
            }
        }
        return $icon_id;
    }

    // Add contents page 2010/08/06 Y.Nakao --start--
    /**
     * check display type for index
     *
     * @param unknown_type $index_id
     * @return int display type
     *          0: list
     *          1: contents
     */
    function checkDisplayTypeForIndex($index_id){
        if(strlen($index_id) == 0){
            return 0;
        }
        $query = "SELECT display_type ".
                " FROM ".DATABASE_PREFIX."repository_index ".
                " WHERE index_id = ? ";
        $param = array();
        $param[] = $index_id;
        $result = $this->Db->execute($query, $param);
        if($result === false){
            $this->failTrans();
            return 0;
        } else if(count($result)==0){
            return 0;
        }
        return $result[0]['display_type'];
    }
    // Add contents page 2010/08/06 Y.Nakao --end--


    // Add check file download type 2010/12/15 H.Goto --end--
    // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
    /**
     * make flash save folder.
     *
     * @param int $itemId item_id
     * @param int $attrId attribute_id
     * @param int $fileNo file_no
     */
    function makeFlashFolder($itemId=0, $attrId=0, $fileNo=0){
        $flashDirPath = $this->getFileSavePath("flash");
        if(strlen($flashDirPath) == 0){
            // default directory
            $flashDirPath = BASE_DIR.'/webapp/uploads/repository/flash';
            if( !(file_exists($flashDirPath)) ){
                mkdir($flashDirPath, 0777);
            }
        }
        chmod($flashDirPath, 0777);
        if(($itemId * $attrId * $fileNo) > 0){
            $flashDirPath .= '/'.$itemId.'_'.$attrId.'_'.$fileNo;
            if( !(file_exists($flashDirPath)) ){
                mkdir ( $flashDirPath, 0777);
            }
            chmod($flashDirPath, 0777);
        } else {
            return $flashDirPath;
        }
        // check this folder write right.
        $ex_file = fopen ($flashDirPath.'/test.txt', "w");
        if( $ex_file === false ){
            return '';
        }
        fclose($ex_file);
        unlink($flashDirPath.'/test.txt');

        return $flashDirPath;
    }

    /**
     * check exists flash save folder.
     *
     * @param int $itemId item_id default 0
     * @param int $attrId attribute_id default 0
     * @param int $fileNo file_no default 0
     * @return string flash save folder path.
     */
    function getFlashFolder($itemId=0, $attrId=0, $fileNo=0){
        $flashDirPath = $this->getFileSavePath("flash");
        if(strlen($flashDirPath) == 0){
            // default directory
            $flashDirPath = BASE_DIR.'/webapp/uploads/repository/flash';
        }
        if(!file_exists($flashDirPath)){
            return '';
        }
        if(($itemId * $attrId * $fileNo) > 0){
            $flashDirPath .= '/'.$itemId.'_'.$attrId.'_'.$fileNo;
            if(!file_exists($flashDirPath)){
                return '';
            }
        }
        return $flashDirPath;
    }

    /**
     * get flash file page count(flash files count)
     *
     * @param int $itemId item_id
     * @param int $attrId attribute_id
     * @param int $fileNo file_no
     * @return int flash pagecount
     */
    function getFlashPagecount($itemId, $attrId, $fileNo){
        $flashDir = $this->getFlashFolder($itemId, $attrId, $fileNo);
        if(strlen($flashDir) == 0){
            // flash save folder not exists
            return 0;
        }
        $flashContents = $flashDir.'/weko1.swf';
        $pageCnt = 0;
        if(strlen($flashDir) > 0 && file_exists($flashContents)){
            if(strlen($flashDir) > 0){
                if (file_exists($flashDir)) {
                    chmod ($flashDir, 0755 );
                }
                if ($handle = opendir("$flashDir")) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            if (!is_dir("$flashDir/$file")) {
                                if(preg_match("/weko[0-9]+.swf/", $file) == 1){
                                    $pageCnt++;
                                }
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        }
        //if($pageCnt == 1){
        //    $pageCnt = 0;
        //}
        return $pageCnt;
    }
    // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--

    /**
     * get flag which decides whether display index list or not
     * @return int index display flg
     */
    function getSelectIndexListDisplay() {
         $query = "SELECT param_value ".
                 "FROM ". DATABASE_PREFIX ."repository_parameter ".
                 "WHERE param_name = 'select_index_list_display' AND is_delete = ?; ";
         $params = array();
         $params[] = 0;
         $result = $this->Db->execute($query,$params);
         return $result[0]["param_value"];
    }
    // Add index list 2011/4/13 S.Abe --end--


    // Add log exclusion from user-agaent 2011/05/09 H.Ito --start--
    /**
     * logExclusion
     * @return  logExclusion sql
     */
    function createLogExclusion($isProcessflg=0,$isFullPathflg=true)
    {
        $user_agent = "";
        $robots_exclusion = "";
        switch ($isProcessflg) {
            case 0:
            case 1:
                $user_agent = "";
                $query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
                         "WHERE param_name = 'log_exclusion'; ";
                $ip_list = $this->Db->execute($query);
                if($ip_list === false){
                    return 'error';
                }

                $ip_list = str_replace("\r\n", "\n", $ip_list[0]["param_value"]);
                $ip_list = str_replace("\r", "\n", $ip_list);
                $ip_list = explode("\n", $ip_list);
                for($ii=0; $ii<count($ip_list); $ii++){
                    if(strlen($ip_list[$ii]) > 0){
                        if(strlen($user_agent) == 0){
                            if($isFullPathflg){
                                $user_agent = " AND log.ip_address NOT IN ( ";
                            } else {
                                $user_agent = " AND ". DATABASE_PREFIX ."repository_log.ip_address NOT IN ( ";
                            }
                        } else {
                            $user_agent .= " , ";
                        }
                        $user_agent .= " '$ip_list[$ii]' ";
                    }
                }
                if(strlen($user_agent) > 0){
                    $user_agent .= " ) ";
                }
                if($isProcessflg == 1){
                    break;
                }
            case 2:
                $robots_exclusion = "";
                if(file_exists(WEBAPP_DIR.'/modules/repository/config/Robots_Exclusion')){
                    $fp = fopen(WEBAPP_DIR.'/modules/repository/config/Robots_Exclusion', "r");
                    while(!feof($fp)){
                        // read line
                        $file_line = fgets($fp);
                        $file_line = str_replace("\r\n", "", $file_line);
                        $file_line = str_replace("\n", "", $file_line);
                        // header '#' is coomment
                        $file_line = preg_replace("/^#.*/", "", $file_line);
                        if(strlen($file_line) == 0){
                            continue;
                        }
                        if(strlen($robots_exclusion) == 0){
                            if($isFullPathflg){
                                $robots_exclusion = " AND ( log.user_agent IS NULL OR log.user_agent NOT IN ( ";
                            } else {
                                $robots_exclusion .= " AND ( ". DATABASE_PREFIX ."repository_log.user_agent IS NULL ".
                                                     " OR ". DATABASE_PREFIX ."repository_log.user_agent NOT IN ( ";
                            }
                        } else {
                            $robots_exclusion .= " , ";
                        }
                        $robots_exclusion .= " '$file_line' ";
                    }
                    fclose($fp);
                    if(strlen($robots_exclusion) > 0){
                        $robots_exclusion .= " ) ) ";
                    }
                }
                break;
            default:
                break;
        }
        return $user_agent.$robots_exclusion;
    }
    // Add log exclusion from user-agaent 2011/05/09 H.Ito --end--

    // Add check edit user auth 2011/06/02 A.Suzuki --start--
    /**
     * Check edit user auth
     *
     * @param string $user_id
     * @param int $item_id
     * @return boolean  true: editable
     *                 false: uneditable
     */
    function checkAuthForEditItem($user_id, $item_id){
        if(strlen($user_id)==0 || strlen($item_id)==0){
            return false;
        }

        // Get auth
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->Session->getParameter("_auth_id");

        // Get Item insert user
        $Result_List = array();
        $error_msg = "";
        $this->getItemTableData($item_id, 1, $Result_List, $error_msg);
        if(count($Result_List["item"])==0){
            return false;
        }
        $ins_user_id = $Result_List["item"][0]["ins_user_id"];

        // Check auth
        if(($user_auth_id >= $this->repository_admin_base && $auth_id >= $this->repository_admin_room) || $ins_user_id === $user_id){
            return true;
        } else {
            return false;
        }
    }
    // Add check edit user auth 2011/06/02 A.Suzuki --end--

    // Add checkDate 2011/06/13 T.Sugimoto --start--
    /**
     * checkDate
     *
     * @param int $year
     * @param int $month
     * @param int $date
     * @return boolean  true: editable
     *                 false: uneditable
     */
    function checkDate($year, $month, $date){
        // check number
        if(!is_numeric($year) || !is_numeric($month) || !is_numeric($date)) {
            return false;
        }

        // change int
        $year = intval($year);
        $month = intval($month);
        $date = intval($date);

        // chech range
        if($year <= 0){
            return false;
        }
        if($month < 1 || 12 < $month){
            // not 1 to 12
            return false;
        }
        if($date < 1 || 31 < $date) {
            // no 1 - 31
            return false;
        }

        // check date
        $query = "";
        if($year < 100){
           // 2 digit under
            $query = "SELECT DATE_FORMAT(DATE(?),'%y-%m-%d') AS pubdate;";

        } else {
           // 3 digit over
            $query = "SELECT DATE_FORMAT(DATE(?),'%Y-%m-%d') AS pubdate;";
        }
        $param = array();
        $param[] = $year.'-'.$month.'-'.$date;
        $result = $this->Db->execute($query, $param);
        if($result===false){
            return false;
        }
        if(count($result)!=1){
            return false;
        }
        // check return pubdate
        if(!isset($result[0]['pubdate'])){
            return false;
        }
        if($result[0]['pubdate'] == null){
            return false;
        }
        if(strlen($result[0]['pubdate'])==0){
            return false;
        }

        return true;

    }
    // Add check edit user auth 2011/06/13 T.Sugimoto --end--

    // Add Create tag 2011/12/02 A.Suzuki --start--
    /**
     * Create tag
     *
     * @param string $elementName
     * @param array $attribute["key"] = "value"
     * @param string $content
     * @return string tag text
     */
    function createTags($elementName, $attribute = null, $content = null){
        $retTag = "";

        // Check Arguments
        if(strlen($elementName) <= 0 || (!is_null($attribute) && !is_array($attribute)))
        {
            return $retTag;
        }

        // Start tag
        $retTag .= "<".$this->forXmlChange($elementName);

        // attribute
        if(is_array($attribute))
        {
            foreach($attribute as $key => $val)
            {
                $retTag .= " ".$this->forXmlChange($key)."=\"".$this->forXmlChange($val)."\"";
            }
        }

        // content
        if(strlen($content) <= 0 )
        {
            $retTag .= "/>";
        }
        else
        {
            // content and End tag
            $retTag .= ">".$this->forXmlChange($content)."</".$this->forXmlChange($elementName).">";
        }

        return $retTag;
    }
    // Add Create tag 2011/12/02 A.Suzuki --end--

    // Modfy proxy 2011/12/06 Y.Nakao --start--
    /**
     * get proxy setting data.
     *
     * @return array proxy setting data.
     *               array('proxy_mode'=>0, 'poxy_host'=>'', 'proxy_port'=>'', 'proxy_user'=>'', 'proxy_pass'=>'')
     */
    public function getProxySetting()
    {
        $proxy = array('proxy_mode'=>0, 'proxy_host'=>'', 'proxy_port'=>'', 'proxy_user'=>'', 'proxy_pass'=>'');
        $query = "SELECT conf_name, conf_value ".
                " FROM ".DATABASE_PREFIX."config ".
                " WHERE conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ".
                " OR conf_name = ? ";
        $param = array();
        $param[] = 'proxy_mode';
        $param[] = 'proxy_host';
        $param[] = 'proxy_port';
        $param[] = 'proxy_user';
        $param[] = 'proxy_pass';
        $result = $this->Db->execute($query, $param);
        if($result === false)
        {
            return $proxy;
        }
        for($ii=0; $ii<count($result); $ii++)
        {
            if($result[$ii]['conf_name'] == 'proxy_mode')
            {
                $proxy['proxy_mode'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_host')
            {
                $proxy['proxy_host'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_port')
            {
                $proxy['proxy_port'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_user')
            {
                $proxy['proxy_user'] = $result[$ii]['conf_value'];
            }
            else if($result[$ii]['conf_name'] == 'proxy_pass')
            {
                $proxy['proxy_pass'] = $result[$ii]['conf_value'];
            }
        }
        return $proxy;
    }
    // Modfy proxy 2011/12/06 Y.Nakao --end--

    /**
     * check all user's group in exclusive_acl_group
     *
     * @param array[$ii][room_id] $usersGroups
     * @param string $exclusiveAclGroup
     * @return boolean: true->all user's group in exclusive_acl_group
     *                 false->is not all user's group in exclusive_acl_group
     */
    public function checkAccessGroup($usersGroups, $exclusiveAclGroup)
    {
        for($ii=0; $ii<count($usersGroups); $ii++){
            if(!is_numeric(strpos(','. $exclusiveAclGroup. ',', ','. $usersGroups[$ii]['room_id']. ','))){
                return false;
            }
        }
        return true;
    }

    /**
     * delete pade_id of private room and public space from $usersGroups
     *
     * @param array[$ii][room_id] $usersGroups
     * @return boolean: false->mysql error
     */
    public function deleteRoomIdOfMyRoomAndPublicSpace(&$usersGroups)
    {
        $retUsersGroups = array();

        $params = array();
        $query = " SELECT room_id ".
                 " FROM ". DATABASE_PREFIX. "pages ".
                 " WHERE private_flag=? ".
                 " AND room_id IN (";
        $params[] = 1;
        for($ii=0; $ii<count($usersGroups); $ii++){
            if($ii != 0){
                $query .= ',';
            }
            $query .= '?';
            $params[] = $usersGroups[$ii]['room_id'];
        }
        $query .= ");";
        $result = $this->Db->execute($query, $params);
        if($result === false){
            return false;
        }

        $roomIds = ',';
        for($ii=0; $ii<count($result); $ii++){
            $roomIds .= $result[$ii]['room_id']. ',';
        }

        for($ii=0; $ii<count($usersGroups); $ii++){
            // remove room_id of private space
            // remove room_id of public space
            if( !is_numeric(strpos($roomIds, ','. $usersGroups[$ii]['room_id']. ',')) &&
                $usersGroups[$ii]['room_id'] != 1){

                array_push($retUsersGroups, $usersGroups[$ii]);
            }
        }

        $usersGroups = $retUsersGroups;
    }
    // Add tree access control list 2012/02/29 T.Koyasu -end-

    /**
     * Get parameter for PDF Cover by param_name
     *
     * @param string $paramName
     * @return array
     */
    public function getPdfCoverParamRecord($paramName)
    {
        // Get record
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PDF_COVER_PARAMETER." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME." = ?; ";
        $params = array();
        $params[] = $paramName;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result) == 0){
            return null;
        }

        return $result[0];
    }

    /**
     * Update pdf cover parameter
     *
     * @param array $params["paramNeme"] required
     *                     ["text"]
     *                     ["image"]
     *                     ["extension"]
     *                     ["mod_user_id"] required
     *                     ["mod_date"] required
     * @return bool
     */
    public function updatePdfCoverParamByParamName($params)
    {
        $query = "UPDATE ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_PDF_COVER_PARAMETER." ".
                 "SET ".RepositoryConst::DBCOL_COMMON_MOD_USER_ID." = ?, ".
                 RepositoryConst::DBCOL_COMMON_MOD_DATE." = ? ";
        $dbParams = array();
        $dbParams[] = $params[RepositoryConst::DBCOL_COMMON_MOD_USER_ID];
        $dbParams[] = $params[RepositoryConst::DBCOL_COMMON_MOD_DATE];

        // text
        if(isset($params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT]))
        {
            $query .= ", ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT." = ? ";
            $dbParams[] = $params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_TEXT];
        }

        // image
        if(isset($params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE]))
        {
            $query .= ", ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE." = ? ";
            $dbParams[] = $params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_IMAGE];
        }

        // extension
        if(isset($params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_EXTENSION]))
        {
            $query .= ", ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_EXTENSION." = ? ";
            $dbParams[] = $params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_EXTENSION];
        }

        // extension
        if(isset($params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE]))
        {
            $query .= ", ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE." = ? ";
            $dbParams[] = $params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_MIMETYPE];
        }

        // param_name
        $query .= "WHERE ".RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME." = ?; ";
        $dbParams[] = $params[RepositoryConst::DBCOL_REPOSITORY_PDF_COVER_PARAMETER_PARAM_NAME];

        // Execute UPDATE
        $result = $this->Db->execute($query, $dbParams);
        if($result === false){
            $error_msg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_cord",-1);
            return false;
        }
        return true;
    }

    /**
     * Get indexes create_cover_flag is on
     *
     * @return array
     */
    public function getIndexIsCoverFlagOn()
    {
        // Get record
        $query = "SELECT ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_NAME.", ".
                 "       ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_NAME_ENGLISH." ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_INDEX." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_INDEX_CREATE_COVER_FLAG." = 1 ".
                 "AND ".RepositoryConst::DBCOL_COMMON_IS_DELETE." = 0 ".
                 "ORDER BY ".RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID." ASC;";
        $result = $this->Db->execute($query);
        if($result === false || count($result) == 0){
            return array();
        }

        return $result;
    }

    /**
     * Resize image file
     *
     * @param int &$width
     * @param int &$height
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    public function resizeImage(&$width, &$height, $maxWidth=100, $maxHeight=100)
    {
        if($width == 0 || $height == 0)
        {
            return false;
        }
        // resize
        if($width >= $height)
        {
            if($width > $maxWidth)
            {
                $height = $maxWidth / $width * $height;
                $width = $maxWidth;
            }
            if($height > $maxHeight)
            {
                $width = $maxHeight / $height * $width;
                $height = $maxHeight;
            }
        }
        else
        {
            if($height > $maxHeight)
            {
                $width = $maxHeight / $height * $width;
                $height = $maxHeight;
            }
            if($width > $maxWidth)
            {
                $height = $maxWidth / $width * $height;
                $width = $maxWidth;
            }
        }

        return true;
    }

    /**
     * Check extension for PDF cover header image
     *
     * @param string $extention
     * @return bool
     */
    public function checkHeaderImageExtension($extention)
    {
        $bReturn = false;
        switch(strtolower($extention))
        {
            case "png":
            case "gif":
            case "jpg":
            case "jpeg":
                $bReturn = true;
                break;
            default:
                break;
        }
        return $bReturn;
    }

    /**
     * Check for indexes match index create cover
     *
     * @param array $indexIds
     * @return bool
     */
    public function checkIndexCreateCover($indexIds)
    {
        $ret = false;
        $createCoverIndexes = $this->getIndexIsCoverFlagOn();
        foreach($createCoverIndexes as $createCoverIndex)
        {
            if(in_array($createCoverIndex[RepositoryConst::DBCOL_REPOSITORY_INDEX_INDEX_ID], $indexIds))
            {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    /**
     * Get user name by mail address
     *
     * @param string $address
     * @return string
     */
    public function getUserNameByAddress($address)
    {
        // Get user_id
        $userId = "";
        $handle = "";
        $query = "SELECT DISTINCT U.user_id, U.handle ".
                 "FROM ".DATABASE_PREFIX."users AS U, ".DATABASE_PREFIX."users_items_link AS UIL ".
                 "WHERE U.user_id = UIL.user_id ".
                 "AND (UIL.item_id = 5 OR (UIL.item_id = 6 AND UIL.email_reception_flag = 1)) ".
                 "AND UIL.content = ?";
        $params = array();
        $params[] = $address;   // content
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)==0 || strlen($result[0]["user_id"])==0)
        {
            return "";
        }
        $userId = $result[0]["user_id"];
        $handle = $result[0]["handle"];

        // Get user name
        $name = "";
        $container =& DIContainerFactory::getContainer();
        $usersView =& $container->getComponent("usersView");
        $userData = $usersView->getUserItemLinkById($userId);
        if(array_key_exists("4", $userData))
        {
            if($userData["4"]["public_flag"] == "1")
            {
                $name = $userData["4"]["content"];
            }
        }

        if(strlen($name) == 0)
        {
            $name = $handle;
        }

        return $name;
    }
    // Mod multimedia support 2012/10/09 T.Koyasu -start-
    /**
     * file is multimedia file or not
     *
     * @param string $mimeType
     * @param string $extension
     * @return boolean
     */
    public function isMultimediaFile($mimeType, $extension)
    {
        if(preg_match('/^(audio|video)\/((x-(m(4v|s-asf|svideo|s-wmv)|flv|matroska|monkeys-audio|mpeg(url|2|2a|)|omg|tta|twinvq(-plugin|)|wav))|(3gpp|avi|mp([3-4]|eg(url|)|g)|ogg)|tta|vnd\.wave|msvideo|quicktime|wavelet)$/', $mimeType) === 1 ||
           preg_match('/^application\/(vnd\.smaf|x-smaf|ogg)$/', $mimeType) === 1 ||
           $extension == "nut" ||
           $extension == "swf"){
            return true;
        } else {
            return false;
        }
    }
    // Mod multimedia support 2012/10/09 T.Koyasu -end-

    //Add ichushi fill 2012/11/21 A.jin --start--
    /**
     * 医中誌へログインするためのログイン情報をデータベースから取得する
     *
     * @param string ログインID
     * @param string ログインパスワード
     */
    public function getLoginInfoIchushi( &$login_id, &$login_passwd)
    {
        $is_connect = true;
        //1 ログイン情報をデータベース([PREFIX]_repository_parameter)から取得する。
        $result = $this->getParamTableRecord($admin_params,$error_msg);
        if($result === false)
        {
            return false;
        }

        // 1.1   医中誌連携有無をparam_name：「ichuushi_is_connect」から取得し、Out引数に設定する。
        $is_connect = $admin_params['ichushi_is_connect']['param_value'];
        // 1.2   ログインIDをparam_name：「ichuushi_login_id 」から取得し、Out引数に設定する。
        $login_id = $admin_params['ichushi_login_id']['param_value'];
        // 1.3   ログインパスワードをparam_name：「ichushi_login_passwd 」から取得し、Out引数に設定する
        $login_passwd = $admin_params['ichushi_login_passwd']['param_value'];

        return true;
    }

    /**
     * 医中誌へログインする
     *
     * @param string ログインID
     * @param string ログインパスワード
     * @param string Cookie
     */
    public function loginIchushi($login_id, $login_passwd, &$cookie)
    {
        $proxy = $this->getProxySetting();

        //1. HTTP POSTで、引数のログインID、ログインパスワードを加える。
        //テスト環境
        //$url = 'http://ts10.jamas.or.jp/api/login';
        //本番環境
        $url = 'http://search.jamas.or.jp/api/login';

        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        // send http request
        $option = array(
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3,
        );
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

        $http = new HTTP_Request($url, $option);
        // setting HTTP header
        $http->setMethod(HTTP_REQUEST_METHOD_POST);
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        $http->addPostData("userid", $login_id);
        $http->addPostData("password", $login_passwd);

        /////////////////////////////
        // run HTTP request
        /////////////////////////////
        $response = $http->sendRequest();
        if (!PEAR::isError($response)) {
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得
            $charge_cookie = $http->getResponseCookies();// クッキーを取得
        } else {
            return false;
        }

        // get response
        $response_xml = $charge_body;

        //error check
        if($response_xml == "login ng"){
            return false;
        }

        //3. Cookieを保存する
        $cookie = $charge_cookie;

        //4. 認証結果を戻り値に設定する

        return true;
    }

    /**
     * 医中誌からログアウトする
     *
     * @param string Cookie
     */
    public function logoutIchushi($cookie)
    {
        $proxy = $this->getProxySetting();

        //1.
        //テスト環境
        //$url = 'http://ts10.jamas.or.jp/api/logout';
        //本番環境
        $url = 'http://search.jamas.or.jp/api/logout';

        /////////////////////////////
        // HTTP_Request init
        /////////////////////////////
        $option = array(
            "timeout" => "10",
            "allowRedirects" => true,
            "maxRedirects" => 3,
        );
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

        $http = new HTTP_Request($url, $option);
        // setting HTTP header
        $http->setMethod(HTTP_REQUEST_METHOD_POST);
        $http->addHeader("User-Agent", $_SERVER['HTTP_USER_AGENT']);
        $http->addHeader("Referer", $_SERVER['HTTP_REFERER']);
        $http->addCookie("JamasSecInfo", $cookie[0]['value']);
        $http->addPostData("JamasSecInfo", $cookie[0]['value']);

        /////////////////////////////
        // run HTTP request
        /////////////////////////////
        $response = $http->sendRequest();
        if (!PEAR::isError($response)) {
            $charge_code = $http->getResponseCode();// ResponseCode(200等)を取得
            $charge_header = $http->getResponseHeader();// ResponseHeader(レスポンスヘッダ)を取得
            $charge_body = $http->getResponseBody();// ResponseBody(レスポンステキスト)を取得
            $charge_cookie = $http->getResponseCookies();// クッキーを取得
        }
        // get response
        $response_xml = $charge_body;

        // error check
        if($response_xml != "logout ok"){
            return false;
        }

        return true;
    }
    //Add ichushi fill 2012/11/21 A.jin --end--

    /**
     * Get file download status for entryLog
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attrId
     * @param int $fileNo
     * @param int $fileStatus
     * @param int $inputType
     * @param int $loginStatus
     * @param int $groupId
     */
    public function getFileDownloadStatusForEntryLog(
        $itemId, $itemNo, $attrId, $fileNo, &$fileStatus,
        &$inputType, &$loginStatus, &$groupId)
    {
        // file_status
        $fileStatus = RepositoryConst::LOG_FILE_STATUS_UNKNOWN;
        // input_type
        $inputType = null;
        // login_status
        $loginStatus = null;
        // group_id
        $groupId = null;
        
        // Get file info
        $query = "SELECT ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE.", FILE.* ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." AS ATTRTYPE, ".
                         DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." AS FILE ".
                 "WHERE ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." = FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_TYPE_ID." ".
                 "AND ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." = FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO." = ? ";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attrId;
        $params[] = $fileNo;
        $fileInfo = $this->Db->execute($query, $params);
        if($fileInfo !== false && count($fileInfo)>0)
        {
            // Set Validator
            require_once WEBAPP_DIR. '/modules/repository/validator/Validator_DownloadCheck.class.php';
            $validator = new Repository_Validator_DownloadCheck();
            $initResult = $validator->setComponents($this->Session, $this->Db);

            // input type
            if($fileInfo[0][RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_IMPUT_TYPE] == "file_price")
            {
                $inputType = RepositoryConst::LOG_INPUT_TYPE_FILE_PRICE;
            }
            else
            {
                $inputType = RepositoryConst::LOG_INPUT_TYPE_FILE;
            }
            
            // login status
            $userId = $this->Session->getParameter("_user_id");
            $userAuthId = $this->Session->getParameter("_user_auth_id");
            $authId = $this->getRoomAuthorityID();
            
            if(strlen($userId)==0 || $userId == "0")
            {
                // No login user
                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_NO_LOGIN;
            }
            else if($userAuthId >= $this->repository_admin_base && $authId >= $this->repository_admin_room)
            {
                // Admin user
                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_ADMIN;
            }
            else if($fileInfo[0][RepositoryConst::DBCOL_COMMON_INS_USER_ID] === $userId)
            {
                // Register
                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_REGISTER;
            }
            else
            {
                // Login user
                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_LOGIN;

                // Check group download
                if($inputType == RepositoryConst::LOG_INPUT_TYPE_FILE_PRICE)
                {
                    $groupId = 0;
                    $groupPrice = $this->getGroupPrice($fileInfo[0]);
                    $accentArray = $validator->checkPriceAccent($fileInfo[0]);
                    for($ii=0; $ii<count($accentArray); $ii++)
                    {
                        if($accentArray[$ii] == "true")
                        {
                            // If download as no group, status is 'LOG_LOGIN_STATUS_LOGIN'
                            // If download as group, status is 'LOG_LOGIN_STATUS_GROUP'
                            if($groupPrice[$ii]["name"] != "0")
                            {
                                // Group user
                                $loginStatus = RepositoryConst::LOG_LOGIN_STATUS_GROUP;
                                $groupId = $groupPrice[$ii]["id"];
                            }
                            break;
                        }
                    }
                }
            }
            
            // download status
            $fileStatus = RepositoryConst::LOG_FILE_STATUS_CLOSE;
            $this->Session->setParameter("_user_id", 0);
            $this->Session->removeParameter("_user_auth_id");
            if($validator->checkCanItemAccess($itemId, $itemNo))
            {
                $downloadFlag = $validator->checkFileDownloadViewFlag($fileInfo[0][RepositoryConst::DBCOL_REPOSITORY_FILE_PUB_DATE], $this->TransStartDate);
                
                if($downloadFlag === Repository_Validator_DownloadCheck::ACCESS_OPEN)
                {
                    $fileStatus = RepositoryConst::LOG_FILE_STATUS_OPEN;
                }
            }
            $this->Session->setParameter("_user_id", $userId);
            $this->Session->setParameter("_user_auth_id", $userAuthId);
        }
    }

    // Add auto create private_tree K.matsuo 2013/4/5 --end--

    // Fix check index_id Y.Nakao 2013/06/07 --start--
    /**
     * check exists index.
     *
     * delete index is not exists.
     *
     * @param int $indexId
     * @return true/false
     */
    public function existsIndex($indexId)
    {
        // check exists index
        $query = " SELECT index_id ".
                " FROM ".DATABASE_PREFIX."repository_index ".
                " WHERE index_id = ? ".
                " AND is_delete = ? ";
        $params = array();
        $params[] = $indexId;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        else if(count($result)!=1)
        {
            return false;
        }
        return true;
    }
    // Fix check index_id Y.Nakao 2013/06/07 --end--

    // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --start--
    /**
     * get private tree index id
     *
     * @param string $userId user id
     * @return int index id
     */
    function getPrivateTreeIndexId($userId="")
    {
        if(strlen($userId) == "")
        {
            $userId = $this->Session->getParameter("_user_id");
        }
        if($userId == "0")
        {
            return -1;
        }

        $errorMsg = "";
        $this->getAdminParam("privatetree_parent_indexid", $parentIndexId, $errorMsg);
        if($parentIndexId < 0)
        {
            return -1;
        }

        $query = "SELECT index_id FROM ". DATABASE_PREFIX ."repository_index ".
                 "WHERE parent_index_id = ? AND ".
                 "owner_user_id = ? AND ".
                 "is_delete = 0 ";
        $params = array();
        $params[] = $parentIndexId;
        $params[] = $userId;
        $ret = $this->Db->execute($query, $params);
        //取得結果が0件の場合
        if(!isset($ret[0]['index_id'])){
            return -1;
        }
        if($ret[0]['index_id'] <= 0)
        {
            return -1;
        }
        return $ret[0]['index_id'];
    }

    // when availability private tree, auto affiliation in private tree / プライベートツリーが有効の場合、プライベートツリーに所属させる
    /**
     * auto affiliation in private tree
     *
     * @param array $indice array(array("index_id" => $index_id), array("index_id" => $index_id), ... )
     * @param string $insUserId insert user id / when set contributor
     */
    function addPrivateTreeInPositionIndex($indice, $insUserId="")
    {
        $errorMsg = "";
        $this->getAdminParam("is_make_privatetree", $isMakePrivateTree, $errorMsg);
        if($isMakePrivateTree=="1" && _REPOSITORY_PRIVATETREE_AUTO_AFFILIATION)
        {
            // check private tree id / プライベートツリーのindex_idを取得
            $privateIndexTreeId = $this->getPrivateTreeIndexId($insUserId);
            if($privateIndexTreeId == -1)
            {
                // when user's private tree not exist, create private index.
                // $insUserIdのプライベートツリーは存在しないため新規作成

                // tmp session data.
                $loginId = $this->Session->getParameter("_login_id");
                $userId = $this->Session->getParameter("_user_id");

                // 代理投稿の場合はセッションに代理投稿者の情報をつめる
                if($userId != $insUserId && strlen($insUserId) > 0)
                {
                    // set contributor
                    $query = " SELECT login_id ".
                            " FROM ".DATABASE_PREFIX."users ".
                            " WHERE user_id = ? ";
                    $params = array();
                    $params[] = $insUserId;
                    $ret = $this->Db->execute($query, $params);
                    if($ret === false || count($ret)!=1)
                    {
                        // error
                        return $indice;
                    }
                    $this->Session->setParameter("_login_id", $ret[0]["login_id"]);
                    $this->Session->setParameter("_user_id", $insUserId);

                }
                // Fix ログインIDがない場合、ユーザーIDから逆引きする Y.Nakao 2013/07/05 --start--
                if(strlen($this->Session->getParameter("_login_id")) == 0)
                {
                    // set contributor
                    $query = " SELECT login_id ".
                            " FROM ".DATABASE_PREFIX."users ".
                            " WHERE user_id = ? ";
                    $params = array();
                    $params[] = $this->Session->getParameter("_user_id");
                    $ret = $this->Db->execute($query, $params);
                    if($ret === false || count($ret)!=1)
                    {
                        // error
                        return $indice;
                    }
                    $this->Session->setParameter("_login_id", $ret[0]["login_id"]);
                }
                // Fix ログインIDがない場合、ユーザーIDから逆引きする Y.Nakao 2013/07/05 --end--

                $this->createPrivateTree();
                // reload session data
                $this->Session->setParameter("_login_id", $loginId);
                $this->Session->setParameter("_user_id", $userId);

                // Reacquisition
                $privateIndexTreeId = $this->getPrivateTreeIndexId($insUserId);
                if($privateIndexTreeId == -1)
                {
                    // error
                    return $indice;
                }
            }

            if($privateIndexTreeId > 0)
            {
                // check position index
                for($ii=0; $ii<count($indice); $ii++)
                {
                    if($indice[$ii]["index_id"] == $privateIndexTreeId)
                    {
                        // already / 既に所属済
                        break;
                    }
                }
                if($ii == count($indice))
                {
                    // add private tree index_id
                    $idx = array("index_id"=>$privateIndexTreeId);
                    $query = "SELECT index_name, index_name_english FROM ".DATABASE_PREFIX."repository_index ".
                             "WHERE index_id = ".$privateIndexTreeId." ;";
                    $ret = $this->Db->execute($query);
                    if($ret === false || count($ret)!=1){
                        return $indice;
                    }
                    if($this->Session->getParameter("_lang") == "japanese"){
                        $idx["index_name"] = $ret[0]["index_name"];
                    } else {
                        $idx["index_name"] = $ret[0]["index_name_english"];
                    }
                    array_push($indice, $idx);
                }
            }
        }
        return $indice;
    }
    // Add specialized support for open.repo "auto affiliation in private tree" Y.Nakao 2013/06/21 --nd--

    // Add get default auth at public room 2013/06/13 A.Suzuki --start--
    /**
     * Get default auth at public room
     *
     */
    function getDefaultEntryAuthPublic()
    {
        $container =& DIContainerFactory::getContainer();
        $authoritiesView =& $container->getComponent("authoritiesView");
        $configView =& $container->getComponent("configView");

        // Get default entry role_auth_id at public room from config
        $config = $configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
        if($config === false)
        {
            return _AUTH_GUEST;
        }
        $defaultEntryRoleAuthPublic = $config['default_entry_role_auth_public']['conf_value'];

        // Get user_auth_id from role_auth_id
        $authorities =& $authoritiesView->getAuthorityById($defaultEntryRoleAuthPublic);
        if($authoritiy === false) {
            return _AUTH_GUEST;
        }

        return $authorities['user_authority_id'];
    }
    // Add get default role auth at public room 2013/06/13 A.Suzuki --start--

    // Add item type multi-language 2013/07/25 K.Matsuo --start--
    /**
     * attribute name change into multilanguage itemtype name
     *
     * @param int $item_type_id itemtype id
     * @param array $array_item_type_attr itemtype attribute array
     */
    public function setItemtypeNameMultiLanguage($item_type_id, &$array_item_type_attr){
        $select_lang = $this->Session->getParameter("_lang");
        $query = " SELECT *  FROM ".DATABASE_PREFIX."repository_item_type_name_multilanguage ".
                 " WHERE item_type_id = ? ".
                 " AND language = ? ".
                 " AND is_delete = ?;";
        $params = array();
        $params[] = $item_type_id;
        $params[] = $select_lang;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        for($ii = 0; $ii < count($result); $ii++){
            for($jj = 0; $jj < count($array_item_type_attr); $jj++){
                if($result[$ii]['attribute_id'] == $array_item_type_attr[$jj]['attribute_id']){
                    $array_item_type_attr[$jj]['attribute_name'] = $result[$ii]['item_type_name'];
                    continue;
                }
            }
        }
    }
    // Add item type multi-language 2013/07/25 K.Matsuo --end--

    //Add scrape attrTableData if "plural_enable" state change "enabled" to "disabled" 2013/09/09 T.Ichikawa --start--
    /**
     * scrape attrTableData if "plural_enable" state changed
     *
     * @param $idx index number
     * @param $Result_List users table data
     */
    private function scrapeTableData(&$Result_List) {
        array_splice($Result_List, 1);
        return true;
    }
    //Add scrape attrTableData if "plural_enable" state change "enabled" to "disabled" 2013/09/09 T.Ichikawa --end--

    // Add DSpace data move T.Koyasu 2013/10/23 --start--
    /**
     * base process of Action and View
     *
     * @return to transition
     */
    public function execute()
    {
        try
        {
            // create flg of exec init
            $nIsInit = false;
            
            // トランザクション外前処理
            $this->beforeTrans();
            
            // init proc
            $nInitResult = $this->initAction();
            
            if($nInitResult === false){
                $this->failTrans();
                $exception = new RepositoryException( "ERR_MSG_InitFailed", 00001 ); //主メッセージとログIDを指定して例外を作成
                throw $exception;
            }
            $nIsInit = true;
            
            // トランザクション内前処理呼び出し
            $this->preExecute();
            
            // call the method(executeApp) of each inheritance class
            $strProcResult = $this->executeApp();
            
            // トランザクション内後処理呼び出し
            $this->postExecute();
            
            // finish proc
            $this->exitAction();
            
            // トランザクション外後処理
            $this->afterTrans();
            
            // return result string
            if($this->exitFlag) {
                if(is_array($this->errMsg) && count($this->errMsg) > 0){
                    echo json_encode($this->errMsg);
                }
                exit();
            }
            else {
                return $strProcResult;
            }
        }
        catch(RepositoryException $Exception)
        {
            // catch database exception
            
            // if start transaction, exec rollback
            if($nIsInit === true){
                $this->failTrans();
            }
            
            // write log, but no proc in this method
            $this->logFile( get_class($this),
                            "execute",
                            $Exception->getCode(),
                            $Exception->getMessage(),
                            $Exception->getDetailMsg());

            // finish proc
            $this->exitAction();

            return "error";
        }
        catch (AppException $e)
        {
            if($nIsInit)
            {
                if($this->failTrans() === false)
                {
                    $this->errorLog("Failed rollback trance.", __FILE__, __CLASS__, __LINE__);
                }
            }
            
            // エラーログをダンプ
            $this->exeptionLog($e, __FILE__, __CLASS__, __LINE__);
            
            // エラーメッセージを設定
            $errors = $e->getErrors();
            for($ii=0; $ii<count($errors); $ii++)
            {
                foreach ($errors[$ii] as $key => $val)
                {
                    $this->addErrMsg($key, $val);
                }
            }
            
            // ビジネスロジック生成クラス終了処理
            BusinessFactory::uninitialize();
            
            if($this->exitFlag) {
                if(is_array($this->errMsg)){
                    echo json_encode($this->errMsg);
                }
                exit();
            }
            else {
                return "error";
            }
        }
        catch (Exception $e)
        {
            if($isInit)
            {
                if($this->failTrans() === false)
                {
                    $this->errorLog("Failed rollback trance.", __FILE__, __CLASS__, __LINE__);
                }
            }
            // エラーログをダンプ
            $this->exeptionLog($e, __FILE__, __CLASS__, __LINE__);
            
            $this->addErrMsg("予期せぬエラーが発生しました");
            
            // ビジネスロジック生成クラス終了処理
            BusinessFactory::uninitialize();
            
            return "error";
        }
    }

    /**
     * abstract function for execute method of child class
     *
     * @return strResult
     */
    protected function executeApp()
    {
        $exception = new RepositoryException( "ERR_MSG_UnpopulatedCode", 00002 ); //主メッセージとログIDを指定して例外を作成
        throw $exception;
    }
    protected function beforeTrans(){}
    protected function afterTrans(){}
    protected function preExecute(){}
    protected function postExecute(){}

    // Add DSpace data move T.Koyasu 2013/10/23 --end--
    
    // Add e-person R.Matsuura 2014/01/06 --start--
    /**
     * get author name by mail address
     * 
     * @return string
     */
    public function getAuthorNameByAddress($address)
    {
        $query = "SELECT author_id FROM ". DATABASE_PREFIX ."repository_external_author_id_suffix ".
                 "WHERE prefix_id = ? ".
                 "AND suffix = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = 0;
        $params[] = $address;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0)
        {
            return "";
        }
        
        $query = "SELECT language, family, name FROM ". DATABASE_PREFIX ."repository_name_authority ".
                 "WHERE author_id = ? ".
                 "AND is_delete = ? ; ";
        $params = array();
        $params[] = $result[0]["author_id"];
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0)
        {
            return "";
        }
        
        $authorName = "";
        if(strlen($result[0]["name"]) > 0 && strlen($result[0]["family"]) > 0)
        {
            if($result[0]["language"] == "english")
            {
                $authorName = $result[0]["name"]. " " . $result[0]["family"];
            }
            else
            {
                $authorName = $result[0]["family"]. " " . $result[0]["name"];
            }
        }
        else
        {
            $authorName = $result[0]["family"] . $result[0]["name"];
        }
        return $authorName;
    }
    // Add e-person R.Matsuura 2014/01/06 --end--
}
?>
