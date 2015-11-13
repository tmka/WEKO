<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryExternalSearchWordManager.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';

/**
 * repository handle IDs management class
 * 
 */
class Repository_Components_RepositoryExternalSearchWordManager extends RepositoryLogicBase
{
    
    var $db = null;
    
    /**
     * Constructor
     *
     * @param var $session
     * @param var $db
     * @param string $transStartDate
     */
    public function __construct($session, $db, $transStartDate)
    {
        $this->db = $db;
        parent::__construct($session, $this->db, $transStartDate);
    }
    
    /**
     * insert external search word from url
     *
     * @param int $item_id    item id
     * @param int $item_no    item no
     * @param string $referer referer url
     */
    public function insertExternalSearchWordFromURL($item_id, $item_no, $referer)
    {
        // decide engine
        $split_external_search_word = $this->getSearchWord($referer);
        // analyze search word
        $external_search_words = array();
        $external_search_words = $this->checkSearchWord($split_external_search_word);
        // deposit external search word
        for($ii = 0; $ii < count($external_search_words); $ii++) {
            // bug fix external searchword empty 2015/04/10 K.Sugimoto --start--
            if(strlen($external_search_words[$ii]) == 0)
            {
                continue;
            }
            // bug fix external searchword empty 2015/04/10 K.Sugimoto --end--
            $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_item_external_searchword ".
                     "WHERE item_id = ? ".
                     "AND item_no = ? ".
                     "AND word = ? ;";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = $external_search_words[$ii];
            $result = $this->dbAccess->executeQuery($query, $params);
            if(count($result) == 0) {
                require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';
                $searchTable = new RepositorySearchTableProcessing($this->Session, $this->db);
                $searchTable->addExternalSearchWord($item_id, $item_no, $external_search_words[$ii]);
            }
            $query = "INSERT INTO ". DATABASE_PREFIX. "repository_item_external_searchword ".
                     "(item_id, item_no, word, count, ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete) ".
                     "VALUES (?, ?, ?, ?, ?, ?, '0', ?, ?, '', 0) ".
                     "ON DUPLICATE KEY UPDATE ".
                     "count = (count + 1) ;";
            $params = array();
            $params[] = $item_id;
            $params[] = $item_no;
            $params[] = $external_search_words[$ii];
            $params[] = 1;
            $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
            $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
            $params[] = $this->transStartDate;  // ins_date
            $params[] = $this->transStartDate;  // mod_date
            $this->dbAccess->executeQuery($query, $params);
        }
    }
    
    /**
     * get search word
     *
     * @param string referer referer url
     */
    private function getSearchWord($referer)
    {
        $external_search_word = "";
        $domain = "";
        $query_word = "";
        
        $url = parse_url($referer);
        // get domain
        if(isset($url["host"])) {
            $domain = $url["host"];
        }
        // get query word
        if(isset($url["query"])) {
            $query_word = $url["query"];
        } else if(isset($url["fragment"])) {
            $query_word = $url["fragment"];
        }
        
        $query = "SELECT search_word, delimiter FROM ". DATABASE_PREFIX. "repository_external_searchengine_analyticalrule ".
                 "WHERE domain = ? ".
                 "AND is_delete = ? ;";
        $params = array();
        $params[] = $domain;
        $params[] = 0;
        $result = $this->dbAccess->executeQuery($query, $params);
        if(count($result) == 0) {
            return array();
        }
        
        // get external search word
        $external_search_words = array();
        parse_str($query_word, $devRef);
        for($ii = 0; $ii < count($result); $ii++) {
            if(isset($devRef[$result[$ii]['search_word']])){
                $divWord = preg_split($result[$ii]['delimiter'], $devRef[$result[$ii]['search_word']]);
                for($jj = 0; $jj < count($divWord); $jj++){
                    // bug fix external searchword empty 2015/04/09 K.Sugimoto --start--
                    if(strlen($divWord[$jj]) > 0)
                    {
                        $external_search_words[] = $divWord[$jj];
                    }
                    // bug fix external searchword empty 2015/04/09 K.Sugimoto --end--
                }
            }
        }
        return $external_search_words;
    }
    
    /**
     * check search word
     *
     * @param Array $splitExternalSearchWord      splited external search word
     * 
     * return Array
     */
    private function checkSearchWord($splitExternalSearchWord)
    {
        for($ii = 0; $ii < count($splitExternalSearchWord); $ii++) {
            $splitExternalSearchWord[$ii] = urldecode($splitExternalSearchWord[$ii]);
        }
        
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "externalsearchword_stopword";
        $result = $this->dbAccess->executeQuery($query, $params);
        
        $register_external_search_words = array();
        if($result[0]["param_value"] != 2) {
            for($ii = 0; $ii < count($splitExternalSearchWord); $ii++) {
                $analyze_stop_word = $this->checkStopWord($splitExternalSearchWord[$ii], $result[0]["param_value"]);
                for($jj = 0; $jj < count($analyze_stop_word); $jj++) {
                    $register_external_search_words[] = $analyze_stop_word[$jj];
                }
            }
        } else {
            return $splitExternalSearchWord;
        }
        return $register_external_search_words;
    }
    
    /**
     * check stop word
     *
     * @param string $externalSearchWord external search word
     * @param int $stopWordFlg           stop word flag
     * 
     * return Array
     */
    private function checkStopWord($externalSearchWord, $stopWordFlg)
    {
        $external_search_words = array();
        if(strlen($externalSearchWord) == mb_strlen($externalSearchWord)) {
            $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                     "WHERE stop_word = ? ".
                     "AND is_delete = ? ;";
            $params = array();
            $params[] = $externalSearchWord;
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            if(count($result) == 0) {
                $external_search_words[] = $externalSearchWord;
            }
        } else {
            $mecab_path = "";
            if($stopWordFlg == 0) {
                $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                         "WHERE param_name = ? ;";
                $params = array();
                $params[] = 'path_mecab';
                $result = $this->dbAccess->executeQuery($query, $params);
                
                if(count($result) > 0){
                    if(file_exists($result[0]['param_value']."mecab") || 
                       file_exists($result[0]['param_value']."mecab.exe")){
                        $mecab_path = $result[0]['param_value'];
                    } else {
                        $stopWordFlg = 1;
                    }
                } else {
                        $stopWordFlg = 1;
                }
             }
             if($stopWordFlg == 0) {
                require_once WEBAPP_DIR. '/modules/repository/components/LibMecab.php';
                $analyze_search_words = libmecab::mecab($externalSearchWord, $mecab_path);
                
                $tmp_search_word = "";
                for($ii = 0; $ii < count($analyze_search_words); $ii++) {
                    foreach($analyze_search_words[$ii] as $key => $value) {
                        
                        $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                                 "WHERE stop_word = ? ".
                                 "AND part_of_speech = ? ".
                                 "AND is_delete = ? ;";
                        $params = array();
                        $params[] = $key;
                        $params[] = $this->checkPartOfSpeech($value['hinshi']);
                        $params[] = 0;
                        $result = $this->dbAccess->executeQuery($query, $params);
                        if(count($result) == 0) {
                            $tmp_search_word .= $key;
                        } else {
                            if(strlen($tmp_search_word) != 0) {
                                $external_search_words[] = $tmp_search_word;
                                $tmp_search_word = "";
                            }
                        }
                    }
                }
                if(strlen($tmp_search_word) != 0) {
                    $external_search_words[] = $tmp_search_word;
                    $tmp_search_word = "";
                }
            } else {
                $query = "SELECT * FROM ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                         "WHERE stop_word = ? ".
                         "AND is_delete = ? ;";
                $params = array();
                $params[] = $externalSearchWord;
                $params[] = 0;
                $result = $this->dbAccess->executeQuery($query, $params);
                if(count($result) == 0) {
                    $external_search_words[] = $externalSearchWord;
                }
            }
        }
        
        return $external_search_words;
    }
    
    /**
     * update external search stop word
     *
     * @param string $allStopWord stop word strings
     * @param int $stopWordStatus stop word use flag
     * @param int $tagCloudFlg    tag cloud display flag
     */
    public function updateExternalSearchStopWord(&$allStopWord, &$stopWordStatus, &$tagCloudFlg)
    {
        // ストップワードを全て削除状態にする
        $query = "UPDATE ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                 "SET del_user_id = ?, del_date = ?, is_delete = ? ".
                 "WHERE is_delete = ? ;";
        $params = array();
        $params[] = $this->Session->getParameter("_user_id");
        $params[] = $this->transStartDate;
        $params[] = 1;
        $params[] = 0;
        $this->dbAccess->executeQuery($query, $params);
        //ストップワードの追加
        $tmp_all_stop_word = str_replace("\r\n", "\n", $allStopWord);
        $tmp_all_stop_word = str_replace("\r", "\n", $allStopWord);
        $line_stop_word = split("\n", $tmp_all_stop_word);
        for($ii =0; $ii < count($line_stop_word); $ii++) {
            $stop_word = "";
            $part_of_speech = 0;
            $split_stop_word = split(",", $line_stop_word[$ii]);
            $stop_word =  html_entity_decode($split_stop_word[0]);
            if(strlen($stop_word) != 0) {
                if(isset($split_stop_word[1]) && ($split_stop_word[1] < 0 || $split_stop_word[1] > 10)) {
                    continue;
                } else {
                    if(isset($split_stop_word[1])) {
                        $part_of_speech = $split_stop_word[1];
                    }
                    $query = "INSERT INTO ". DATABASE_PREFIX. "repository_external_searchword_stopword ".
                             "(stop_word, part_of_speech, ins_user_id, mod_user_id, del_user_id, ".
                             "ins_date, mod_date, del_date, is_delete) ".
                             "VALUES (?, ?, ?, ?, '0', ?, ?, '', 0) ".
                             "ON DUPLICATE KEY UPDATE ".
                             "stop_word=VALUES(`stop_word`), ".
                             "part_of_speech=VALUES(`part_of_speech`), ".
                             "mod_user_id=VALUES(`mod_user_id`), ".
                             "del_user_id=VALUES(`del_user_id`), ".
                             "mod_date=VALUES(`mod_date`), ".
                             "del_date=VALUES(`del_date`), ".
                             "is_delete=VALUES(`is_delete`) ;";
                    $params = array();
                    $params[] = $stop_word;
                    $params[] = $part_of_speech;
                    $params[] = $this->Session->getParameter("_user_id");   // ins_user_id
                    $params[] = $this->Session->getParameter("_user_id");   // mod_user_id
                    $params[] = $this->transStartDate;  // ins_date
                    $params[] = $this->transStartDate;  // mod_date
                    $this->dbAccess->executeQuery($query, $params);
                }
            }
        }
        
        // ストップワード使用状態更新
        $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".
                     "SET param_value = ?, ".       // パラメタ値
                     "mod_user_id = ?, ".           // 更新ユーザID
                     "mod_date = ? ".               // 更新日
                     "WHERE param_name = ?; ";      // パラメタ名(PK)
        $params = null;                                        // パラメタテーブル更新用クエリ
        $params[] = $stopWordStatus;                           // param_value
        $params[] = $this->Session->getParameter("_user_id");  // mod_user_id
        $params[] = $this->transStartDate;                     // mod_date
        $params[] = 'externalsearchword_stopword';             // param_name
        // UPDATE実行
        $this->dbAccess->executeQuery($query, $params);
        // タグクラウド表示フラグ更新
        // ストップワード使用状態更新
        $query = "UPDATE ". DATABASE_PREFIX ."repository_parameter ".
                     "SET param_value = ?, ".       // パラメタ値
                     "mod_user_id = ?, ".           // 更新ユーザID
                     "mod_date = ? ".               // 更新日
                     "WHERE param_name = ?; ";      // パラメタ名(PK)
        $params = null;                                        // パラメタテーブル更新用クエリ
        $params[] = $tagCloudFlg;                              // param_value
        $params[] = $this->Session->getParameter("_user_id");  // mod_user_id
        $params[] = $this->transStartDate;                     // mod_date
        $params[] = 'show_detail_tagcloudflag';                // param_name
        // UPDATE実行
        $this->dbAccess->executeQuery($query, $params);
    }
    /**
     * update external search stop word
     *
     * @param string $allStopWord stop word strings
     * @param int $stopWordStatus stop word use flag
     * @param int $tagCloudFlg    tag cloud display flag
     */
    private function checkPartOfSpeech($speech)
    {
        $speechValue = 0;
        switch($speech){
            case '動詞':
                $speechValue = 1;
                break;
            case '形容詞':
                $speechValue = 2;
                break;
            case '形容動詞':
                $speechValue = 3;
                break;
            case '名詞':
                $speechValue = 4;
                break;
            case '連体詞':
                $speechValue = 5;
                break;
            case '副詞':
                $speechValue = 6;
                break;
            case '接続詞':
                $speechValue = 7;
                break;
            case '感動詞':
                $speechValue = 8;
                break;
            case '助動詞':
                $speechValue = 9;
                break;
            case '助詞':
                $speechValue = 10;
                break;
            default:
                $speechValue = 0;
                break;
            
        }
        return $speechValue;
    }
}
?>