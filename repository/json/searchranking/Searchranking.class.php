<?php
// --------------------------------------------------------------------
//
// $Id: Detail.class.php 36217 2014-05-26 04:22:11Z satoshi_arata $
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

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Json_Searchranking extends RepositoryAction
{
    // 使用コンポーネントを受け取るため
    
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
        // ランキング設定を取得する
        $rankingDisplay = 0;
        $rankingNum = 0;
        $error_msg = "";
        // ランキング取得方式
        $result = $this->getAdminParam('ranking_disp_setting', $rankingDisplay, $error_msg);
        if ( $result == false ){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );    //主メッセージとログIDを指定して例外を作成
            $exception->setDetailMsg( $error_msg );         //詳細メッセージ設定
            throw $exception;
        }
        // ランキング表示数
        $result = $this->getAdminParam('ranking_disp_num', $rankingNum, $error_msg);
        if ( $result == false ){
            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", "xxx-xxx1" );    //主メッセージとログIDを指定して例外を作成
            $exception->setDetailMsg( $error_msg );         //詳細メッセージ設定
            throw $exception;
        }
        
        // 検索ワードランキングを取得する
        if($rankingDisplay == 0){
            // リアルタイムに更新するの時
            require_once WEBAPP_DIR. '/modules/repository/view/main/ranking/Ranking.class.php';
            $log_exception = $this->createLogExclusion(0,false);
            $viewRanking = new Repository_View_Main_Ranking();
            $viewRanking->SetData($this->Session, $this->Db, $log_exception, "", $this->TransStartDate);
            $result = $viewRanking->getKeywordRankingData();
        } else {
            // DB保存情報を表示するの時
            // get ranking data from repository_ranking
            $query = "SELECT * ".
                     "FROM " .DATABASE_PREFIX ."repository_ranking ".
                     "WHERE rank_type = ? ".
                     " AND is_delete = ? ".
                     "ORDER BY rank;";
                     
            $params = array();
            $params[] = 'keywordRanking';
            $params[] = 0;
            $result = $this->dbAccess->executeQuery($query, $params);
            if($result === false){
                return 'error';
            }
        }
        $outputJSON = "{";
        for($ii = 0; $ii < $rankingNum && $ii < count($result); $ii++){
            if($ii != 0){
                $outputJSON .= ",";
            }
            $rank = $ii+1;
            $word = "";
            $num = "";
            if($rankingDisplay == 0){
                $word = $result[$ii]["search_keyword"];
                $num = $result[$ii]["count(*)"];
            }else {
                $rank = $result[$ii]["rank"];
                $word = $result[$ii]["disp_name"];
                $num = $result[$ii]["disp_value"];
            }
            // "0":{"rank":1,"word":"test","count":25} のような形で出力
            $outputJSON .= "\"" . $ii . "\":".
                           "{\"rank\":\"" . $rank ."\",".
                           "\"word\":\"".$word ."\",".
                           "\"count\":\"".$num ."\"}";
        }
        $outputJSON .= "}";
        echo $outputJSON;
        return true;
    }
}
?>
