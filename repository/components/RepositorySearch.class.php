<?php
// --------------------------------------------------------------------
//
// $Id: RepositorySearch.class.php 43337 2014-10-29 04:59:45Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
require_once WEBAPP_DIR."/modules/repository/components/RepositorySearchRequestParameter.class.php";
require_once WEBAPP_DIR."/modules/repository/components/QueryGenerator.class.php";
require_once WEBAPP_DIR."/modules/repository/components/RepositoryPluginManager.class.php";

/**
 * for search class object
 * 
 */
class RepositorySearchQueryParameter
{
    public $search_term = null;
    public $index_id = null;
    public $user_id = null;
    public $user_auth_id = null;
    public $auth_id = null;
    public $adminUser = null;
    public $searchEngine = null;
    public $countFlag = null;
    public $sort_order = null;
    public $groupList = null;
    public $total = null;
    public $lang = null;
    
    function __construct($search_term, 
                         $index_id, 
                         $user_id, 
                         $user_auth_id, 
                         $auth_id, 
                         $adminUser, 
                         $searchEngine, 
                         $countFlag, 
                         $sort_order, 
                         $groupList, 
                         $lang)
    {
        $this->search_term = $search_term;
        $this->index_id = $index_id;
        $this->user_id = $user_id;
        $this->user_auth_id = $user_auth_id;
        $this->auth_id = $auth_id;
        $this->adminUser = $adminUser;
        $this->searchEngine = $searchEngine;
        $this->countFlag = $countFlag;
        $this->sort_order = $sort_order;
        $this->groupList = $groupList;
        $this->lang = $lang;
    }
}

/**
 * repository search class
 * 
 */
class RepositorySearch extends RepositorySearchRequestParameter
{
    const OUTPUT_PROC_TIME = false;
    // search table name
    const SORT_TABLE = "repository_search_sort";
    const ITEM_TABLE = "repository_item";
    const POS_INDEX_TABLE = "repository_position_index";
    // search table short name
    const SORT_TABLE_SHORT_NAME = "sort";
    const ITEM_TABLE_SHORT_NAME = "item";
    const POS_INDEX_TABLE_SHORT_NAME = "pos";
    
    const CONNECT_INNERJOIN_LIMIT = 1000;
    
    const INNER_JOIN = "innerJoin";
    const WHERE_IN = "wherIn";
    
    /**
     * search total
     *
     * @var int
     */
    private $total = 0;
    
    /**
     * start index ( 1 origine )
     *
     * @var int
     */
    private $startIndex = 0;
    
    /**
     * construct
     *
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * ヒット件数
     *
     */
    public function getTotal()
    {
        return $this->total;
    }
    
    /**
     * 開始件数
     */
    public function getStartIndex()
    {
        return $this->startIndex;
    }
    
    /**
     * 検索実行
     * 
     * ★使い方★
     * 下記を記述すると、検索条件に応じた検索結果を取得する。
     * require_once WEBAPP_DIR."/modules/repository/components/RepositorySearch.class.php";
     * $RepositorySearch = new RepositorySearch();
     * $RepositorySearch->Session = $this->Session;
     * $RepositorySearch->Db = $this->Db;
     * $RepositorySearch->search();
     * 
     * ◆検索条件はリクエストパラメータから自動取得する
     * 下記を記述したクラスのリクエストパラメータから自動で取得する ⇒ RepositorySearchRequestParameter::setRequestParameter
     * $RepositorySearch = new RepositorySearch();
     * 
     * ただし、リクエストパラメータの変数名がsetRequestParameterメソッドのものと違う場合は個別に指定する必要がある
     * なお、個別指定の場合validatorは実施されないので適宜対応すること
     * ⇒ $RepositorySearch->keyword = $sample;
     * 
     * ◆全件取得
     * 全件取得したい場合、下記を設定する。
     * $RepositorySearch->listResords = "all";
     * $RepositorySearch->search();
     * 
     * 
     * @param bool search result in metadata or not
     * @return search result
     * 
     */
    public function search()
    {
        // clear
        $this->total = 0;
        $this->startIndex = 0;
        $this->itemsPerPage = 0;
        
        // search
        $sTime = microtime(true);
        $result = $this->searchItem();
        
        // output proc time
        $this->outputProcTime("RepositorySearch検索処理",$sTime, microtime(true));
        
        // output log(operation is keyword search)
        $this->outputSearchLog();
        
        return $result;
    }
    
    /**
     * 条件ごとにクエリーを投げて配列を連結していく形にする
     *
     */
    private function searchItem()
    {
        // クエリ作成クラスの選択
        $pluginManager = new RepositoryPluginManager($this->Session, $this->dbAccess, $this->TransStartDate);
        $queryGenerator = $pluginManager->getPlugin(RepositoryPluginManager::SEARCH_QUERY_COLUMN);
        if(!isset($queryGenerator)) {
            $queryGenerator = new Repository_Components_Querygenerator(DATABASE_PREFIX);
        }
        
        // 接続先テーブル設定
        $connectToTable = DATABASE_PREFIX.self::SORT_TABLE;
        
        // 管理者判定
        $adminUser = false;
        $user_auth_id = $this->Session->getParameter("_user_auth_id");
        $auth_id = $this->Session->getParameter("_auth_id");
        if( $user_auth_id >= $this->RepositoryAction->repository_admin_base && $auth_id >= $this->RepositoryAction->repository_admin_room)
        {
            $adminUser = true;
        }
        // 検索エンジン判定
        $searchEngine = "";
        
        $query = "SHOW ENGINES";
        $engines = $this->dbAccess->executeQuery($query);
        for($ii = 0; $ii < count($engines); $ii++) {
            if($engines[$ii]["Engine"] == "Mroonga" || $engines[$ii]["Engine"] == "mroonga") {
                $searchEngine = "mroonga";
            } else {
                // Bug Fix: using LIKE mysql-command in search on centos5 2014/10/27 T.Koyasu --start--
                // check search engine is senna
                try {
                    $sennaStatus = $this->dbAccess->executeQuery("SHOW SENNA STATUS;");
                    $searchEngine = "senna";
                } catch (RepositoryException $Exception){
                    $searchEngine = "";
                }
                // Bug Fix: using LIKE mysql-command in search on centos5 2014/10/27 T.Koyasu --end--
            }
        }
        // グループID取得
        $groupList = null;
        $this->RepositoryAction->getUsersGroupList($groupList, $errMsg);
        $this->RepositoryAction->deleteRoomIdOfMyRoomAndPublicSpace($groupList);
        
        // 検索条件オブジェクト作成
        $countSearchInfo = new RepositorySearchQueryParameter($this->search_term, 
                                                              $this->index_id, 
                                                              $this->Session->getParameter("_user_id"), 
                                                              $user_auth_id, 
                                                              $auth_id, 
                                                              $adminUser, 
                                                              $searchEngine, 
                                                              true, 
                                                              $this->sort_order, 
                                                              $groupList, 
                                                              $this->lang);
        // create query
        $result = $queryGenerator->createDetailSearchQuery($countSearchInfo, $countQuery, $countQueryParam);
        if($result === false)
        {
            $this->validatePageNo(0);
            return array();
        }
        
        // execute count query
        $result = $this->dbAccess->executeQuery($countQuery, $countQueryParam);
        
        if(count($result) != 1)
        {
            return array();
        }
        $this->total = $result[0]['total'];
        
        ///// get max page no /////
        $maxPageNo = (int)($this->total / $this->list_view_num);
        if( ($this->total % $this->list_view_num) > 0)
        {
            $maxPageNo += 1;
        }
        ///// validator page_no /////
        $this->validatePageNo($maxPageNo);
        
        ///// set start index /////
        $this->startIndex = intval($this->page_no - 1) * intval($this->list_view_num) + 1;
        if($this->startIndex < 0)
        {
            $this->startIndex = 0;
        }
        if($this->total == 0){
            return array();
        }
        
        // 検索条件オブジェクトの作成
        $searchInfo = new RepositorySearchQueryParameter($this->search_term, 
                                                         $this->index_id, 
                                                         $this->Session->getParameter("_user_id"), 
                                                         $user_auth_id, 
                                                         $auth_id, 
                                                         $adminUser, 
                                                         $searchEngine, 
                                                         false, 
                                                         $this->sort_order, 
                                                         $groupList, 
                                                         $this->lang);
        
        $result = $queryGenerator->createDetailSearchQuery($searchInfo, $searchQuery, $searchQueryParam);
        if($result === false)
        {
            return array();
        }
        
        ///// set limit entries from searchresult /////
        if($this->listResords != "all")
        {
            $searchQuery .= " LIMIT ".($this->startIndex-1).", ".$this->list_view_num;
        }
        // search
        $result = $this->dbAccess->executeQuery($searchQuery, $searchQueryParam);
        if(_REPOSITORY_HIGH_SPEED) {
            for($ii = 0; $ii < count($result); $ii++) {
                $result[$ii]["uri"] = BASE_URL. "/?action=repository_uri&item_id=".$result[$ii]["item_id"];
            }
        }
        else
        {
            require_once WEBAPP_DIR."/modules/repository/components/RepositoryHandleManager.class.php";
            if(strlen($this->TransStartDate) == 0)
            {
                $date = new Date();
                $this->TransStartDate = $date->getDate().".000";
            }
            $handleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            for($ii = 0; $ii < count($result); $ii++)
            {
                $url = $handleManager->createUri($result[$ii]["item_id"], $result[$ii]["item_no"], RepositoryHandleManager::ID_Y_HANDLE);
                if(strlen($url) > 0)
                {
                    $result[$ii]["uri"] = $url;
                }
            }
        }
        return $result;
    }
    
    /**
     * 検索処理時間計測
     *
     * @param float $sTime
     * @param float $eTime
     */
    public function outputProcTime($message, $sTime, $eTime)
    {
        if(self::OUTPUT_PROC_TIME)
        {
            $time = abs($eTime - $sTime);
            $fp = fopen("searchTime.txt", "a");
            fwrite($fp, "\n");
            fwrite($fp, "---------------------------------\n");
            fwrite($fp, "検索条件\n");
            fwrite($fp, "キーワード：".$this->keyword."\n");
            fwrite($fp, "インデックス：".$this->index_id."\n");
            fwrite($fp, "表示件数:".$this->list_view_num."\n");
            fwrite($fp, "フォーマット：".$this->format."\n");
            fwrite($fp, "$message: ".$time."秒\n");
            fwrite($fp, "---------------------------------\n");
            fclose($fp);
        }
    }
    
    /**
     * 検索キーワードログ出力
     *
     */
    private function outputSearchLog()
    {
        // search parameter is not null, 
        // search parameter elements more than 0,
        // no list export,
        // no print
        if(isset($this->search_term) 
            && count($this->search_term) > 0 
            && (!isset($this->listResords) || strlen($this->listResords) == 0))
        {
            foreach($this->search_term as $key => $value)
            {
                switch($key)
                {
                    case "all":
                    case "meta":
                    case "title":
                    case "creator":
                    case "kw":
                    case "scDes":
                    case "des":
                    case "pub":
                    case "con":
                    case "form":
                    case "idDes":
                    case "jtitle":
                    case "sp":
                    case "era":
                    case "grantid":
                    case "degreename":
                    case "grantor":
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        // ログ出力
                        for($ii = 0; $ii < count($keywordArray); $ii++)
                        {
                            $this->RepositoryAction->entryLog(4, "", "", "", "", $keywordArray[$ii]);
                        }
                        
                        break;
                        
                    default:
                        break;
                }
            }
        }
    }
}
?>
