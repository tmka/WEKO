<?php
// --------------------------------------------------------------------
//
// $Id: Selecttype.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR. '/modules/repository/components/Checkdoi.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/ItemtypeManager.class.php';

/**
 * アイテム登録：アイテムタイプ選択画面表示
 *
 * @package     [[package名]]
 * @access      public
 * @version 1.0 新規作成
 *          2.0 登録フロー表示改善対応 2008/06/26 Y.Nakao  
 */
class Repository_View_Main_Item_Selecttype extends WekoAction
{
    // 表示用パラメーター
    /**
     * item type data
     *   0: item type id
     *   1: item type name
     *   2: flag thag the item type is able to grant doi or not
     *      can grant:1, cannot grant:0
     * @var array
     */
    public $itemtype_data = array();
    
    /**
     * アイテムタイプの属性にファイルが含まれているか否かを示す配列
     * @var array
     */
    public $itemtype_file = array();
    
    /**
     * ヘルプアイコン表示フラグ
     * @var string
     */
    public $help_icon_display = "";
    
    // リクエストパラメーター
    /**
     * 警告メッセージ配列
     * @var array
     */
    public $warningMsg = null;
    
    /**
     * 実行処理
     * @see ActionBase::executeApp()
     */
    protected function executeApp()
    {
        // タブ切替時にセッションの初期化
        $this->initSessionParams();
        
        // RepositoryActionのインスタンス
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->Db;
        $repositoryAction->dbAccess = $this->Db;
        $repositoryAction->TransStartDate = $this->accessDate;
        
        // Add itemtype authority 2014/12/18 T.Ichikawa --start--
        $item_type = array();
        // 権限IDを取得する
        $query = "SELECT role_authority_id ".
                 "FROM ".DATABASE_PREFIX. "users ".
                 "WHERE user_id = ? ;";
        $params = array();
        $params[] = $this->Session->getParameter("_user_id");
        $role_auth = $this->Db->execute($query, $params);
        if(count($role_auth) > 0) {
            // ユーザー権限レベルを取得する
            $query = "SELECT user_authority_id ".
                     "FROM ".DATABASE_PREFIX. "authorities ".
                     "WHERE role_authority_id = ? ;";
            $params = array();
            $params[] = $role_auth[0]["role_authority_id"];
            $user_auth = $this->Db->execute($query, $params);
            // 権限が一般以上ならアイテムタイプを取得する
            if($user_auth[0]["user_authority_id"]  >= REPOSITORY_ITEM_REGIST_AUTH) {
                // ルーム権限を取得する
                $room_auth = $repositoryAction->getRoomAuthorityID($this->Session->getParameter("_user_id"));
                // 現在のユーザーの権限で使用できるアイテムタイプを取得する
                $itemtypeManager = new Repository_Components_Itemtypemanager($this->Session, $this->Db, $this->accessDate);
                $item_type = $itemtypeManager->getItemtypeDataByUserAuth($role_auth[0]["role_authority_id"], $room_auth);
            }
        }
        // Add itemtype authority 2014/12/18 T.Ichikawa --end--
            
        // 主キーとアイテム名を抜き出し
        // 登録フローに表示されるファイル登録部分の改善対応 2008/06/26 Y.Nakao --start--
        // default item type 2008/09/17 Y.Nakao --start--
        $default_itemtype = array();
        $create_itemtype = array();
        $default_file = array();
        $create_file = array();
        for($ii=0; $ii<count($item_type); $ii++) {
            // 現アイテムタイプの属性を取得
            $query = "SELECT INPUT_TYPE ".
                     "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
                     "WHERE item_type_id = ? ".
                     "AND is_delete = ?; ";
            $params = array();
            $params[] = $item_type[$ii]['item_type_id'];
            $params[] = 0;
            $item_attr = $this->Db->execute($query, $params);
            if($item_attr === false){
                $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
                $exception = new AppException($this->Db->ErrorMsg());
                $exception->addError($this->Db->ErrorMsg());
                throw $exception;
            }
            $attr_file = 0;
            for($jj=0; $jj<count($item_attr); $jj++){
                if( strcmp($item_attr[$jj]['INPUT_TYPE'], "file") == 0 || 
                    strcmp($item_attr[$jj]['INPUT_TYPE'], "file_price") == 0 || // Add file price Y.Nakao 2008/08/28
                    strcmp($item_attr[$jj]['INPUT_TYPE'], "thumbnail") == 0)
                {
                    $attr_file = 1;
                    break;
                }
            }
            
            // default item type show top
            if($item_type[$ii]['item_type_id']>1000){
                if($item_type[$ii]['item_type_id']<20001){
                    array_push($default_itemtype, array($item_type[$ii]['item_type_id'], $item_type[$ii]['item_type_name']));
                    array_push($default_file, $attr_file);
                }
            } else {
                array_push($create_itemtype, array($item_type[$ii]['item_type_id'], $item_type[$ii]['item_type_name']));
                array_push($create_file, $attr_file);
            }
        }
        
        $this->itemtype_data = array_merge($default_itemtype, $create_itemtype);
        $this->itemtype_file = array_merge($default_file, $create_file);
        // default item type 2008/09/17 Y.Nakao --end--
        // 登録フローに表示されるファイル登録部分の改善対応 2008/06/26 Y.Nakao --end--
        
        for($cnt = 0; $cnt < count($this->itemtype_data); $cnt++){
            $CheckDoi = new Repository_Components_Checkdoi($this->Session, $this->Db, $this->accessDate);
            if( $CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_JALC_DOI) ||
                $CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_CROSS_REF) ||
                $CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_LIBRARY_JALC_DOI) ||
                $CheckDoi->checkDoiGrantItemtype($this->itemtype_data[$cnt][0], Repository_Components_Checkdoi::TYPE_DATACITE))
            {
                array_push($this->itemtype_data[$cnt], 1);
            } else {
                array_push($this->itemtype_data[$cnt], 0);
            }
        }
        
        // Set help icon setting
        $tmpErrorMsg = "";
        $result = $repositoryAction->getAdminParam('help_icon_display', $this->help_icon_display, $tmpErrorMsg);
        if ( $result === false ){
            $this->errorLog($tmpErrorMsg, __FILE__, __CLASS__, __LINE__);
            $exception = new AppException($tmpErrorMsg);
            $exception->addError($tmpErrorMsg);
            throw $exception;
        }
        
        return 'success';
    }
    
    /**
     * セッションパラメーターの初期化
     */
    private function initSessionParams(){
        $this->Session->removeParameter("edit_flag");
        $this->Session->removeParameter("edit_item_id");
        $this->Session->removeParameter("edit_item_no");
        $this->Session->removeParameter("edit_start_date");
        $this->Session->removeParameter("delete_file_list");
        $this->Session->removeParameter('item_pub_date');
        $this->Session->removeParameter('item_keyword');
        $this->Session->removeParameter("item_type_all");
        $this->Session->removeParameter("item_attr_type");
        $this->Session->removeParameter("item_num_cand");
        $this->Session->removeParameter("option_data");
        $this->Session->removeParameter("isfile");
        $this->Session->removeParameter("item_num_attr");
        $this->Session->removeParameter("item_attr");
        $this->Session->removeParameter("base_attr");
        $this->Session->removeParameter("indice");
        $this->Session->removeParameter("link");
        $this->Session->removeParameter("link_search");
        $this->Session->removeParameter("link_searchkeyword");
        $this->Session->removeParameter("link_searchtype");
        $this->Session->removeParameter("open_node_index_id_link");
        $this->Session->removeParameter("open_node_index_id_index");
        $this->Session->removeParameter("license_master");
        $this->Session->removeParameter("error_msg");
        $this->Session->removeParameter("filelist");
        $this->Session->removeParameter("attr_file_flg");
        $this->Session->removeParameter("doi_itemtype_flag");
        $this->Session->removeParameter("feedback_mailaddress_str");
        $this->Session->removeParameter("feedback_mailaddress_author_str");
        $this->Session->removeParameter("feedback_mailaddress_array");
        $this->Session->removeParameter("feedback_mailaddress_author_array");
        $this->Session->removeParameter("all_group");
        $this->Session->removeParameter("user_group");
        $this->Session->removeParameter("view_open_node_index_id_insert_item");
        $this->Session->removeParameter("view_open_node_index_id_item_link");
        $this->Session->removeParameter("search_index_id_link");
    }
}
?>
