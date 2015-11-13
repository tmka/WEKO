<?php
// --------------------------------------------------------------------
//
// $Id: Setting.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearch.class.php';

/**
 * [[アイテム管理viewアクション]]
 * 表示順序設定画面
 * 一括エンバーゴ設定画面
 * 一括削除画面
 * 
 * @package  [[package名]]
 * @access    public
 */
class Repository_View_Edit_Item_Setting extends RepositoryAction
{
    const MAX_ITEM_DISPLAY = 100;
    
    // 使用コンポーネントを受け取るため
    var $Session = null;
    var $Db = null;
    
    // エラーメッセージ
    public $errMsg = null;
    
    /*
     * 選択タブ
     */
    public $item_setting_active_tab = null;
    
    /*
     * エンバーゴ設定用
     */
    // ライセンスマスタ情報
    public $licence_master = array();
    // 現在時刻格納
    public $date = array();
    // 初期値はオープンアクセス
    public $embargo_flag_chk = null;
    //public $embargo_flag_chk = "1";
    
    // ライセンスマスタの数
    public $licence_num = 0;
    public $help_icon_display =  null;
    
    //自由入力欄
    public $licence_free_text = "";
    //サブインデックスに適用するかのフラグ
    public $embargo_recursion = null;
    //ライセンスタイプの選択インデックス
    public $license_id = null;
    
    /*
     * 並び替え表示用
     */
    public $sortIndexId = null;
    public $sortIndexName = null;
    public $sortData = array();
    public $authCheckFlg = false;
    
    /*
     * JaLC DOI一括設定表示用
     */
    public $doiData = array();
    public $exist_jalcdoi_prefix = false;
    public $exist_crossref_prefix = false;
    public $exist_datacite_prefix = false;
    
    /*
     * DOI付与アイテム数表示用
     */
    public $doi_count_msg = null;
    
    /*
     * 検索削除表示用
     */
    public $searchkeywordForDelete = null; // 検索欄に表示するキーワード
    public $meta = null;                   // キーワード検索で入力された検索キーワード
    public $all = null;                    // 全文検索で入力された検索キーワード
    public $search_type = null;            // キーワード検索なのか、全文検索なのかを保存するフラグ
    public $titleData = array();           // 検索結果が入った配列
    public $delete_success_num = 0;        // 削除に成功したアイテム数
    public $page_no = null;                // 表示しているページ番号
    public $page_num = 0;                  // 全体のページ数
    public $list_view_num = 100;           // 1ページに表示するアイテム数
    public $item_num = null;               // 検索結果の総アイテム数
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    protected  function executeApp()
    {
        //言語設定
        $this->setLangResource();
        
        // 権限チェック
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->Session->getParameter("_auth_id");
        if($user_auth_id >= $this->repository_admin_base 
         && $auth_id >= $this->repository_admin_room)
        {
            $this->authCheckFlg = true;
        }else{
            $this->authCheckFlg = false;
            $this->errorLog("viewing rights error", __FILE__, __CLASS__, __LINE__);//エラーログ
            return "error";
        }
        
        // セッション削除  選択インデックス
        $this->Session->removeParameter("searchIndexId");
         
        // 保存された選択タブを変数に設定
        if($this->Session->getParameter("item_setting_active_tab") == ""){
            if(isset($this->item_setting_active_tab)){
                //Viewからの遷移の場合そのまま
            }
            else{
                $this->item_setting_active_tab = 0;
            }
        } else {
            $this->item_setting_active_tab = $this->Session->getParameter("item_setting_active_tab");
            
        }
        
        //Actionから描画時
        if($this->Session->getParameter("targetIndexId") != null){
            $this->sortIndexId = $this->Session->getParameter("targetIndexId");
            $this->Session->setParameter("searchIndexId", $this->sortIndexId);
            $this->sortIndexName = $this->getSortIdxName($this->sortIndexId);
        }
        
        // ======================================================================
        // 検索削除 アイテムから描画時
        // ======================================================================
        // 削除成功数
        if( $this->Session->getParameter("delete_success_num") != null ){
            $this->delete_success_num = $this->Session->getParameter("delete_success_num");
        }
        
        // 検索欄のキーワード
        if( $this->Session->getParameter("searchkeywordForDelete") != null ){
            $searchkeyword = $this->Session->getParameter("searchkeywordForDelete");
            $this->searchkeywordForDelete = urldecode($searchkeyword);
        }
            
        // 検索タイプ
        if( $this->Session->getParameter("search_type") != null ){
            $search_type = $this->Session->getParameter("search_type");
            // キーワード検索か全文検索か判定
            if( $search_type == "simple" ){
                $this->search_type = "simple";
            }
            else 
            {
                $this->search_type = "detail";
            }
        }
        // ======================================================================
        
        // アイテム管理画面を表示する
        $this->displayCustomsort();
        $this->displayEmbago();
        $this->displayListdelete();
        $this->displayDoi();
        $this->displaySearchDelete();
        
        // セッションクリア
        $this->Session->removeParameter("targetIndexId");
        $this->Session->removeParameter("targetIndexName");
        $this->Session->removeParameter("isDeleteSubIndexItem");
        $this->Session->removeParameter("item_setting_active_tab");
        // セッションクリア
        $this->Session->removeParameter("embargo_flag");
        $this->Session->removeParameter("embargo_year");
        $this->Session->removeParameter("embargo_month");
        $this->Session->removeParameter("embargo_day");
        $this->Session->removeParameter("license_id");
        $this->Session->removeParameter("licence_free_text");
        $this->Session->removeParameter("embargo_recursion");
        // 検索削除関連セッションクリア
        $this->Session->removeParameter("delete_success_num");
        $this->Session->removeParameter("searchkeywordForDelete");
        $this->Session->removeParameter("search_type");
        
        return 'success';
    }
    
    /**
     * 並び替え表示データ設定
     * 
     * @return bool 成否
     */
    private function displayCustomsort()
    {
        //Viewから再描画時
        if($this->sortIndexId == null){
            if($this->sortIndexId == null){
                $this->sortIndexId = 0;
            }
            if($this->sortIndexName == null){
                $this->sortIndexName = "";
            }
        }else{
            $this->Session->setParameter("searchIndexId", $this->sortIndexId);
        }
        
        if($this->sortIndexId != null){
            //1 データベース[PREFIX]_repository_position_indexテーブルからインデックスの情報を1～100件まで取得する。
            //  以下のカラム条件に一致するインデックスの情報を1件～100件まで取得する。
            //  カラム:index_id=セッション:targetIndexId
            //  カラム:custom_sort_order昇順
            $query = "SELECT item_id, item_no FROM ".DATABASE_PREFIX."repository_position_index ".
                     "WHERE index_id=".$this->sortIndexId.
                     " AND is_delete=0".
                     " ORDER BY custom_sort_order ASC ".
                     "LIMIT 0, 100;";
            // SELECT実行
            $index_result = $this->Db->execute($query);
            if($index_result === false) {
                return false;
            }
    
            //2 データベース[PREFIX]_repository_itemテーブルから1で取得したインデックスの情報からアイテム情報を取得する。
            //  条件：取得するアイテムデータはitem_id, item_no, title, title_englishのみ
            //  カラム:index_id=1で取得したインデックス情報のindex_id
            //  カラム:index_no=1で取得したインデックス情報のindex_no
            for($ii=0;$ii<count($index_result);$ii++){
                // Bug Fix WEKO-2014-041 take no though about 2 or more position index T.Koyasu 2014/07/11 --start--
                $query = "SELECT ITEM.item_id, ITEM.item_no, ITEM.title, ITEM.title_english, POS.custom_sort_order ".
                         "FROM ".DATABASE_PREFIX."repository_item AS ITEM ".
                         " INNER JOIN ".DATABASE_PREFIX."repository_position_index AS POS ".
                         " ON ITEM.item_id=POS.item_id ".
                         " AND ITEM.item_no=POS.item_no ".
                         "WHERE ITEM.item_id=? ".
                         " AND ITEM.item_no=? ".
                         " AND ITEM.is_delete=? ".
                         " AND POS.index_id=? ".
                         " AND POS.is_delete=?".
                         " ORDER BY POS.custom_sort_order ASC;";
                $params = array();
                $params[] = $index_result[$ii]['item_id'];
                $params[] = $index_result[$ii]['item_no'];
                $params[] = 0;
                $params[] = $this->sortIndexId;
                $params[] = 0;
                // SELECT実行
                $result = $this->Db->execute($query, $params);
                if($result === false) {
                    return false;
                }
                // Bug Fix WEKO-2014-041 take no though about 2 or more position index T.Koyasu 2014/07/11 --end--
                //3 リクエストパラメータ:sortDataに2で取得したアイテム情報を格納する。
                array_push($this->sortData,$result[0]);
            }
        }
        
        return true;
    }
    
    /**
     * エンバーゴ設定用画面表示データ設定
     *
     * @return string errormsg
     */
    private function displayEmbago()
    {
        // ライセンスマスタ情報取得
        $quety = "SELECT * ".
                 "FROM ". DATABASE_PREFIX ."repository_license_master ".
                 "WHERE is_delete = 0; "; 
        $this->licence_master = $this->Db->Execute($quety);
        if ($this->licence_master == false) {
            return false;
        }
        
        $this->licence_num = count($this->licence_master);
        
        
        //再描画か判定する 2013/01/22 Jin Add --start--
        if($this->Session->getParameter("embargo_flag") != null)
        {
            //再描画時
            //セッション情報をリクエストパラメータに設定する
            $this->embargo_flag_chk = $this->Session->getParameter("embargo_flag");
            $this->date["year"] = $this->Session->getParameter("embargo_year");
            $this->date["month"] = $this->Session->getParameter("embargo_month");
            $this->date["day"] = $this->Session->getParameter("embargo_day");
            $this->license_id = $this->Session->getParameter("license_id");
            if($this->license_id == 0){
                $this->licence_free_text = $this->Session->getParameter("licence_free_text");
            }
            
            if($this->Session->getParameter("embargo_recursion") == "true"){
                $this->embargo_recursion = true;
            }else{
                $this->embargo_recursion = false;
            }
        }
        //再描画か判定する 2013/01/22 Jin Add --end-- 
        else{
            //初期表示
            $this->embargo_flag_chk = "1";
            $this->license_id = 0;
            $this->embargo_recursion = true;
            // 現在時刻取得
            $DATE = new Date();
            $this->date["year"] = $DATE->getYear();
            $this->date["month"] = sprintf("%02d", $DATE->getMonth());
            $this->date["day"] = sprintf("%02d", $DATE->getDay());
            
        }
        
        // Set help icon setting 2010/02/10 K.Ando --start--
        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
        if ( $result == false ){
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                                                  //詳細メッセージ文字列作成
            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );                             //詳細メッセージ設定
            $this->failTrans();                                                  //トランザクション失敗を設定(ROLLBACK)
            throw $exception;
        }
        // Set help icon setting 2010/02/10 K.Ando --end--
        
        return true;
    }
    
    /**
     * 一括削除画面表示用データ設定
     */
    private function displayListdelete()
    {
        //処理なし
        return true;
    }
    
    /*
     * 選択インデックス名を取得する
     * @param int 対象インデックス
     * @return string 対象インデックス名
     */
    private function getSortIdxName($sortIndexid){
        //戻り値
        $sortIndexName = '';
        $query = "SELECT index_name, index_name_english ".
                 "FROM ".DATABASE_PREFIX."repository_index ".
                 "WHERE index_id=? AND is_delete=?;";
        $params = array();
        $params[] = $sortIndexid;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result === false){
            $errmsg = $this->Db->ErrorMsg();
            $this->Session->setParameter("error_msg", $errmsg);
            return $errMsg;
        }
        if(count($result) == 1){
            if($this->Session->getParameter("_lang") == "japanese"){
                $sortIndexName = $result[0]['index_name'];
            }else{
                $sortIndexName = $result[0]['index_name_english'];
            }
        }
        
        return $sortIndexName;
    }
    
    /**
     * DOI一括付与
     * 
     * @return bool 成否
     */
    private function displayDoi()
    {
        //Viewから再描画時
        if($this->sortIndexId == null)
        {
            $this->sortIndexId = 0;
            if($this->sortIndexName == null)
            {
                $this->sortIndexName = "";
            }
        }
        else
        {
            $this->Session->setParameter("searchIndexId", $this->sortIndexId);
        }
        
        // DOI付与アイテム数を表示
        $doi_count = $this->Session->getParameter("doi_count");
        $this->Session->removeParameter("doi_count");
        if(isset($doi_count) && $doi_count > 0)
        {
	        $smartyAssign = $this->Session->getParameter("smartyAssign");
	        if($smartyAssign == null || !isset($smartyAssign))
	        {
	            $this->setLangResource();
	            $smartyAssign = $this->Session->getParameter("smartyAssign");
	        }
            $this->doi_count_msg = sprintf($smartyAssign->getLang("repository_item_doi_count_msg"), $doi_count);
        }
        
        if(isset($this->sortIndexId) && $this->sortIndexId > 0)
        {
            $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
            $this->exist_jalcdoi_prefix = $CheckDoi->existJalcdoiPrefix();
            $this->exist_crossref_prefix = $CheckDoi->existCrossrefPrefix();
            $this->exist_datacite_prefix = $CheckDoi->existDatacitePrefix();
            if($CheckDoi->isHarvestPublicIndex($this->sortIndexId) && ($this->exist_jalcdoi_prefix || $this->exist_crossref_prefix || $this->exist_datacite_prefix))
            {
                //1 データベース[PREFIX]_repository_position_indexテーブルからインデックスの情報を1～100件まで取得する。
                //  以下のカラム条件に一致するインデックスの情報を1件～100件まで取得する。
                $count = 0;
                $item_result = array();
                while(count($this->doiData) < 100)
                {
                    $query = "SELECT item_id, item_no FROM ".DATABASE_PREFIX."repository_position_index ".
                             "WHERE index_id = ? ".
                             "AND is_delete = ? ".
                             "ORDER BY custom_sort_order ASC ".
                             "LIMIT ?, ?;";
                    $params = array();
                    $params[] = $this->sortIndexId;
                    $params[] = 0;
                    $params[] = $count * self::MAX_ITEM_DISPLAY;
                    $params[] = ($count+1) * self::MAX_ITEM_DISPLAY;
                    // SELECT実行
                    $item_result = $this->dbAccess->executeQuery($query, $params);
                    
                    //2 データベース[PREFIX]_repository_itemテーブルから1で取得したインデックスの情報からアイテム情報を取得する。
                    //  条件：取得するアイテムデータはitem_id, item_no, title, title_englishのみ
                    //  カラム:index_id=1で取得したインデックス情報のindex_id
                    //  カラム:index_no=1で取得したインデックス情報のindex_no
                    for($ii=0;$ii<count($item_result);$ii++)
                    {
                        $jalc_doi_grant_flag = $CheckDoi->checkDoiGrant($item_result[$ii]['item_id'], $item_result[$ii]['item_no'], 
                                               Repository_Components_Checkdoi::TYPE_JALC_DOI, 
                                               Repository_Components_Checkdoi::CHECKING_STATUS_ITEM_MANAGEMENT);
                        $cross_ref_grant_flag = $CheckDoi->checkDoiGrant($item_result[$ii]['item_id'], $item_result[$ii]['item_no'], 
                                               Repository_Components_Checkdoi::TYPE_CROSS_REF, 
                                               Repository_Components_Checkdoi::CHECKING_STATUS_ITEM_MANAGEMENT);
                        $datacite_grant_flag = $CheckDoi->checkDoiGrant($item_result[$ii]['item_id'], $item_result[$ii]['item_no'], 
                                               Repository_Components_Checkdoi::TYPE_DATACITE, 
                                               Repository_Components_Checkdoi::CHECKING_STATUS_ITEM_MANAGEMENT);
                        if($jalc_doi_grant_flag && $cross_ref_grant_flag && $datacite_grant_flag)
                        {
                            // 全て付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI);
                        }
                        else if($jalc_doi_grant_flag && !$cross_ref_grant_flag && !$datacite_grant_flag)
                        {
                            // JaLC DOIのみ付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI);
                        }
                        else if(!$jalc_doi_grant_flag && $cross_ref_grant_flag && !$datacite_grant_flag)
                        {
                            // Cross Refのみ付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI);
                        }
                        else if(!$jalc_doi_grant_flag && !$cross_ref_grant_flag && $datacite_grant_flag)
                        {
                            // DataCiteのみ付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI);
                        }
                        else if($jalc_doi_grant_flag && $cross_ref_grant_flag && !$datacite_grant_flag)
                        {
                            // JaLC DOI、Cross Ref付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI);
                        }
                        else if($jalc_doi_grant_flag && !$cross_ref_grant_flag && $datacite_grant_flag)
                        {
                            // JaLCDOI、DataCite付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI);
                        }
                    }
                    if(count($item_result) < 100)
                    {
                        break;
                    }
                    
                    $count++;
                }
            }
        }
    }
    
    /**
     * JaLC DOI一括付与
     * 
     * @param doi_data
     * @param jalc_doi_flag  0:can grant 1:cannot grant
     * @param cross_ref_flag 0:can grant 1:cannot grant
     * @param datacite_flag 0:can grant 1:cannot grant
     */
    private function setDoiData($item_id, $item_no, &$doi_data, $jalc_doi_flag, $cross_ref_flag, $datacite_flag)
    {
        $query = "SELECT item_id, item_no, title, title_english ".
                 "FROM ".DATABASE_PREFIX."repository_item ".
                 "WHERE item_id = ? ".
                 "AND item_no = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $item_id;
        $params[] = $item_no;
        $params[] = 0;
        // SELECT実行
        $result = $this->dbAccess->executeQuery($query, $params);
        
        if(isset($result[0]))
        {
            $result[0]['jalc_doi'] = $jalc_doi_flag;
            $result[0]['cross_ref'] = $cross_ref_flag;
            $result[0]['datacite'] = $datacite_flag;
            //3 リクエストパラメータ:doiDataに2で取得したアイテム情報を格納する。
            array_push($doi_data,$result[0]);
        }
    }
    
    /**
     * 検索削除
     */
    private function displaySearchDelete(){
        
        // 検索ボタンが押下されたまたはactionから戻ってきて検索キーワードがある
        if( $this->meta != null || $this->all != null || $this->searchkeywordForDelete != null ){
            
            $repositorySearch = new RepositorySearch();
            
            $repositorySearch->list_view_num = $this->list_view_num;
            
            // アイテムの順序はタイトル順で固定
            $repositorySearch->sort_order = Repository_Components_Querygenerator::ORDER_TITLE_ASC;
            
            $this->page_no = $repositorySearch->page_no;
            
            // キーワードを検索欄に表示するために設定
            if( $this->meta != null ){
                $this->meta = urldecode($this->meta);
                $this->searchkeywordForDelete = $this->meta;
                $repositorySearch->search_term[repositorySearch::REQUEST_META] = $this->searchkeywordForDelete;
                $this->search_type = "simple";
            }
            else if( $this->all != null )
            {
                $this->all = urldecode($this->all);
                $this->searchkeywordForDelete = $this->all;
                $repositorySearch->search_term[repositorySearch::REQUEST_ALL] = $this->searchkeywordForDelete;
                $this->search_type = "detail";
            }
            else{
                // actionから戻ってきた場合、検索実行のための処理
                // actionからの場合リクエストパラメータから自動で取得してくれないので検索キーワードを設定する
                if( $this->search_type === "simple" ){
                    $repositorySearch->search_term[repositorySearch::REQUEST_META] = $this->searchkeywordForDelete;
                }
                else if( $this->search_type === "detail" )
                {
                    $repositorySearch->search_term[repositorySearch::REQUEST_ALL] = $this->searchkeywordForDelete;
                }
            }
            
            $this->infoLog("repositorySearch search_term:".print_r($repositorySearch->search_term, true), __FILE__, __CLASS__, __LINE__);
            
            // 検索実行
            $result = $repositorySearch->search();
            
            // アイテム総数は検索した後に分かる
            $this->item_num = $repositorySearch->getTotal();
            
            // 表示されるページ数計算
            $this->page_num = (int)($this->item_num / $this->list_view_num);
            if(($this->item_num%$this->list_view_num) != 0){
                $this->page_num++;
            }
            
            // 検索結果がある場合表示のために整形する
            if(count($result) > 0){
                $this->displaySearchResult($result);
            }
        }
        else{
            
            return true;
        }
    }
    
    /**
     * 検索削除 検索結果整形
     * @param searchResult search()の戻り値 Array([0] => Array ( [item_id] => 5 [item_no] => 1 [uri] => http://localhost/netcommons2.4.2.0/htdocs/?action=repository_uri&item_id=5 ))
     */
    private function displaySearchResult($searchResult){
        
        // タイトルを取得して配列に加える
        for( $ii=0; $ii<count($searchResult); $ii++ ){
            
            $item_id = $searchResult[$ii]['item_id'];
            $item_no = $searchResult[$ii]['item_no'];
            
            $query = " SELECT title, title_english ".
                     " FROM ".DATABASE_PREFIX."repository_item ".
                     " WHERE item_id = ? ".
                     " AND item_no = ? ".
                     " AND is_delete = ? ;";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = 0;
            // SELECT実行
            $selectTitle = $this->dbAccess->executeQuery($query, $params);
            
            if(isset($selectTitle[0]))
            {
                // タイトルを追加
                $arr_title = array( 'title' => $selectTitle[0]['title'] );
                $searchResult[$ii] += $arr_title;
                
                $arr_title_english = array( 'title_english' => $selectTitle[0]['title_english'] );
                $searchResult[$ii] += $arr_title_english;
            }
        }
        
        // メンバ変数に整形後の配列を代入
        $this->titleData = $searchResult;
        
        // セッションに検索結果を保存
        $this->Session->setParameter("search_delete_item_data", $searchResult);
    }
    
}
?>
