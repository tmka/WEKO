<?php
// --------------------------------------------------------------------
//
// $Id: Setting.class.php 40575 2014-08-28 00:25:12Z tatsuya_koyasu $
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
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
        try{
            //アクション初期化処理
            $result = $this->initAction();
            if ( $result === false ) {
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                                                  //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );                             //詳細メッセージ設定
                $this->failTrans();                                                 //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            
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
                $result = $this->exitAction();
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
            // アイテム管理画面を表示する
            $this->displayCustomsort();
            $this->displayEmbago();
            $this->displayListdelete();
            $this->displayDoi();

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
            
            return 'success';
        }catch(RepositoryException $Exception){
            //エラーログ出力
            $this->logFile(
                "Repository_View_Edit_Item_Setting",                 //クラス名
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
        
        if(isset($this->sortIndexId) && $this->sortIndexId > 0)
        {
            $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->TransStartDate);
            $this->exist_jalcdoi_prefix = $CheckDoi->existJalcdoiPrefix();
            $this->exist_crossref_prefix = $CheckDoi->existCrossrefPrefix();
            if($CheckDoi->isHarvestPublicIndex($this->sortIndexId) && ($this->exist_jalcdoi_prefix || $this->exist_crossref_prefix))
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
                        if($jalc_doi_grant_flag && $cross_ref_grant_flag)
                        {
                            // 両方付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI);
                        }
                        else if($jalc_doi_grant_flag && !$cross_ref_grant_flag)
                        {
                            // JaLC DOIのみ付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI);
                        }
                        else if(!$jalc_doi_grant_flag && $cross_ref_grant_flag)
                        {
                            // Cross Refのみ付与可能
                            $this->setDoiData($item_result[$ii]['item_id'],
                                              $item_result[$ii]['item_no'],
                                              $this->doiData, 
                                              Repository_Components_Checkdoi::CANNOT_GRANT_DOI, 
                                              Repository_Components_Checkdoi::CAN_GRANT_DOI);
                        }
                    }
                    if(count($item_result) < 100)
                    {
                        break;
                    }
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
     */
    private function setDoiData($item_id, $item_no, &$doi_data, $jalc_doi_flag, $cross_ref_flag)
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
            //3 リクエストパラメータ:doiDataに2で取得したアイテム情報を格納する。
            array_push($doi_data,$result[0]);
        }
    }
}
?>
