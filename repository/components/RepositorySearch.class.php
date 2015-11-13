<?php
// --------------------------------------------------------------------
//
// $Id: RepositorySearch.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
require_once WEBAPP_DIR."/modules/repository/files/plugin/searchkeywordconverter/Twobytechartohalfsizechar.class.php";

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
        for($ii = 0; $ii < count($engines); $ii++)
        {
            // check search engine is Mroonga
            if($engines[$ii]["Engine"] == "Mroonga" || $engines[$ii]["Engine"] == "mroonga")
            {
                $searchEngine = "mroonga";
                break;
            }
        }
        // Bug Fix: using LIKE mysql-command in search on centos5 2014/10/27 T.Koyasu --start--
        if(strlen($searchEngine) == 0)
        {
            // check search engine is senna
            try
            {
                $sennaStatus = $this->dbAccess->executeQuery("SHOW SENNA STATUS;");
                $searchEngine = "senna";
            } catch (RepositoryException $Exception){
                $searchEngine = "";
            }
        }
        // Bug Fix: using LIKE mysql-command in search on centos5 2014/10/27 T.Koyasu --end--
        // グループID取得
        $groupList = null;
        $this->RepositoryAction->getUsersGroupList($groupList, $errMsg);
        $this->RepositoryAction->deleteRoomIdOfMyRoomAndPublicSpace($groupList);
        
	    // Extend Search Keyword 2015/02/23 K.Sugimoto --start--
        $search_term_half = $this->search_term;
        $converterHalf = new Repository_Files_Plugin_Searchkeywordconverter_Twobytechartohalfsizechar();
        $exceptArray = array("itemTypeList", "language", "textversion", "scList", "idList", "typeList", "riList");
        $toSearchCondition = new ToSearchCondition();
        foreach($search_term_half as $key => $value)
        {
	        if(!in_array($key, $exceptArray))
	        {
	        	$search_term_half[$key] = $converterHalf->toSearchCondition($value, $toSearchCondition);
	        }
        }
	    // Extend Search Keyword 2015/02/23 K.Sugimoto --end--
        
        // 検索条件オブジェクト作成
        $countSearchInfo = new RepositorySearchQueryParameter($search_term_half, 
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
        $searchInfo = new RepositorySearchQueryParameter($search_term_half, 
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
                // Improve Search Log 2015/03/19 K.Sugimoto --start--
                switch($key)
                {
                    // 簡易検索
                    case "all":
                    case "meta":
                    	$detailSearchItemId = 0;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // タイトル
                    case "title":
                    	$detailSearchItemId = 1;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 著者名 OR 著者ID
                    case "creator":
                    	$detailSearchItemId = 2;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // キーワード
                    case "kw":
                    	$detailSearchItemId = 3;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 件名・分類
                    case "scDes":
                    	$detailSearchItemId = 4;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 抄録・内容記述
                    case "des":
                    	$detailSearchItemId = 5;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 公開者 OR 著者ID
                    case "pub":
                    	$detailSearchItemId = 6;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 寄与者 OR 著者ID
                    case "con":
                    	$detailSearchItemId = 7;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // コンテンツ作成日
                    case "date":
                    	$detailSearchItemId = 8;
                    	$keywordArray = array();
                        $keywordArray[0] = $value;
                        
                        break;
                    // アイテムタイプ
                    case "itemTypeList":
                    	$detailSearchItemId = 9;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                        
                        break;
                    // 資源タイプ
                    case "typeList":
                    	$detailSearchItemId = 10;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                    	$keywordArray = $this->transformNiiTypeIdToNiiTypeName($keywordArray);
                        
                        break;
                    // フォーマット
                    case "form":
                    	$detailSearchItemId = 11;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // ID
                    case "idDes":
                    	$detailSearchItemId = 12;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 雑誌名
                    case "jtitle":
                    	$detailSearchItemId = 13;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 出版年
                    case "pubYearFrom":
                    case "pubYearUntil":
                    	$detailSearchItemId = 14;
                    	$keywordArray = array();
                        $keywordArray[0] = $value;
                        
                        break;
                    // 言語
                    case "ln":
                    	$detailSearchItemId = 15;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                        
                        break;
                    // 地域
                    case "sp":
                    	$detailSearchItemId = 16;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 時代
                    case "era":
                    	$detailSearchItemId = 17;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 権利
                    case "riDes":
                    	$detailSearchItemId = 18;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 権利
                    case "riList":
                    	$detailSearchItemId = 18;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                    	$keywordArray = $this->transformRightIdToRightName($keywordArray);
                        
                        break;
                    // 著者版フラグ
                    case "textver":
                    	$detailSearchItemId = 19;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                        
                        break;
                    // 学位授与番号
                    case "grantid":
                    	$detailSearchItemId = 20;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 学位授与年月日
                    case "grantDateFrom":
                    case "grantDateUntil":
                    	$detailSearchItemId = 21;
                    	$keywordArray = array();
                        $keywordArray[0] = $value;
                        
                        break;
                    // 学位名
                    case "degreename":
                    	$detailSearchItemId = 22;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // 学位授与機関
                    case "grantor":
                    	$detailSearchItemId = 23;
                        // スペース区切りで1単語ずつに分割
                        $keywordArray = explode(" ", $value);
                        
                        break;
                    // インデックス
                    case "idx":
                    	$detailSearchItemId = 24;
                        // カンマ区切りで1単語ずつに分割
                        $keywordArray = explode(",", $value);
                        
                        break;
                    // 公開日
                    case "pubDateFrom":
                    case "pubDateUntil":
                    	$detailSearchItemId = 25;
                    	$keywordArray = array();
                        $keywordArray[0] = $value;
                        
                        break;
                    
                    default:
                        $keywordArray = array();
                        break;
                }
                
                // ログ出力
                for($ii = 0; $ii < count($keywordArray); $ii++)
                {
                    if(strlen($this->TransStartDate) == 0)
                    {
                        $date = new Date();
                        $this->TransStartDate = $date->getDate().".000";
                    }
                    // Mod entryLog T.Koyasu 2015/03/06 --start--
                    require_once WEBAPP_DIR. '/modules/repository/components/FW/AppLogger.class.php';
                    AppLogger::infoLog("businessLogmanager", __FILE__, __CLASS__, __LINE__);
                    BusinessFactory::initialize($this->Session, $this->Db, $this->TransStartDate);
                    $logManager = BusinessFactory::getFactory()->getBusiness("businessLogmanager");
                    
                    $logManager->entryLogForKeywordSearch($keywordArray[$ii], $detailSearchItemId);
                    // Mod entryLog T.Koyasu 2015/03/06 --end--
                }
                // Improve Search Log 2015/03/19 K.Sugimoto --end--
            }
        }
    }
    
    // Improve Search Log 2015/03/19 K.Sugimoto --start--
    /**
     * 資源タイプ変換
     *
     * @param Array $niiTypeArray
     */
    private function transformNiiTypeIdToNiiTypeName($niiTypeArray)
    {
        for($ii = 0; $ii < count($niiTypeArray); $ii++)
        {
            switch($niiTypeArray[$ii])
            {
            	case 0:
            		$niiTypeArray[$ii] = "Journal Article";
            		break;
            	case 1:
            		$niiTypeArray[$ii] = "Thesis or Dissertation";
            		break;
            	case 2:
            		$niiTypeArray[$ii] = "Departmental Bulletin Paper";
            		break;
            	case 3:
            		$niiTypeArray[$ii] = "Conference Paper";
            		break;
            	case 4:
            		$niiTypeArray[$ii] = "Presentation";
            		break;
            	case 5:
            		$niiTypeArray[$ii] = "Book";
            		break;
            	case 6:
            		$niiTypeArray[$ii] = "Technical Report";
            		break;
            	case 7:
            		$niiTypeArray[$ii] = "Research Paper";
            		break;
            	case 8:
            		$niiTypeArray[$ii] = "Article";
            		break;
            	case 9:
            		$niiTypeArray[$ii] = "Preprint";
            		break;
            	case 10:
            		$niiTypeArray[$ii] = "Learning Material";
            		break;
            	case 11:
            		$niiTypeArray[$ii] = "Data or Dataset";
            		break;
            	case 12:
            		$niiTypeArray[$ii] = "Software";
            		break;
            	case 13:
            		$niiTypeArray[$ii] = "Others";
            		break;
            	default:
            		break;
            }
        }
        return $niiTypeArray;
    }
    
    /**
     * 権利変換
     *
     * @param Array $niiTypeArray
     */
    private function transformRightIdToRightName($rightArray)
    {
        $returnArray = array();
        
        for($ii = 0; $ii < count($rightArray); $ii++)
        {
            switch($rightArray[$ii])
            {
            	case 101:
            		$returnArray[] = "CC BY";
            		break;
            	case 102:
            		$returnArray[] = "CC BY-SA";
            		break;
            	case 103:
            		$returnArray[] = "CC BY-ND";
            		break;
            	case 104:
            		$returnArray[] = "CC BY-NC";
            		break;
            	case 105:
            		$returnArray[] = "CC BY-NC-SA";
            		break;
            	case 106:
            		$returnArray[] = "CC BY-NC-ND";
            		break;
            	default:
            		break;
            }
        }
        return $returnArray;
    }
    // Improve Search Log 2015/03/19 K.Sugimoto --end--
}
?>
