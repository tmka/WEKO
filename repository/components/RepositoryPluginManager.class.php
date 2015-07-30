<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryPluginManager.class.php 42307 2014-09-29 06:18:07Z tomohiro_ichikawa $
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
class RepositoryPluginManager extends RepositoryLogicBase
{
    // search query plugin
    const SEARCH_QUERY_COLUMN = "search_query_plugin";
    
    
    /**
     * Constructor
     *
     * @param var $session
     * @param var $dbAccess
     * @param string $TransStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        if(!isset($transStartDate)) {
            $DATE = new Date();
            $transStartDate = $DATE->getDate().".000";
        }
        parent::__construct($session, $dbAccess, $transStartDate);
    }
    
    /**
     * Register Y handle prefix
     * 
     * @param string $path_name
     * 
     * @return bool
     */
    public function getPlugin($plugin_param)
    {
        // プラグイン
        $plugin = null;
        
        // プラグイン名が入っているカラムの値を取得する
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = $plugin_param;
        $result = $this->dbAccess->executeQuery($query, $params);
        
        // 「repository/files/plugin/xxx」の形式で取得される
        if($result) {
            $plugin_path = $result[0]["param_value"];
            if(strlen($plugin_path) > 0) {
                // ディレクトリの下にはプラグインファイルは1つしかない前提で処理する
                if(file_exists(WEBAPP_DIR. "/modules/". $plugin_path)) {
                    // プラグインファイルを読み込み
                    require_once(WEBAPP_DIR. "/modules/". $plugin_path);
                    
                    // クラス名を作成する
                    $class_name = "";
                    $file_names = explode("/", $plugin_path);
                    // 命名形式に沿ったクラス名を作成する
                    for($ii = 0; $ii < count($file_names); $ii++) {
                        if($ii != 0) {
                            $class_name .= "_";
                        }
                        $class_name .= ucfirst($file_names[$ii]);
                    }
                    // 「.class.php」部分を取り除く
                    $class_name = str_replace(".class.php", "", $class_name);
                    
                    // インスタンスを作成する
                    $plugin = new $class_name(DATABASE_PREFIX);
                }
            }
        }
        // インスタンス or null
        return $plugin;
    }
}
?>