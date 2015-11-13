<?php
// --------------------------------------------------------------------
//
// $Id: LogAnalyzor.class.php 48595 2015-02-18 08:36:51Z tomohiro_ichikawa $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryLogicBase.class.php';

class Repository_Components_Loganalyzor extends RepositoryLogicBase
{
    /**
     * Constructor
     *
     * @param var $session
     * @param var $dbAccess
     * @param string $transStartDate
     */
    public function __construct($session, $dbAccess, $transStartDate)
    {
        parent::__construct($session, $dbAccess, $transStartDate);
    }

    /**
     * exclusive ip address
     * 
     * @param string $abbreviation
     * @return string
     */
    public function execlusiveIpAddressQuery($abbreviation) {
        $query = "SELECT param_value FROM ". DATABASE_PREFIX. "repository_parameter ".
                 "WHERE param_name = ? ;";
        $params = array();
        $params[] = "log_exclusion";
        $result = $this->dbAccess->executeQuery($query, $params);
        
        $ip_address = str_replace(array("\r\n", "\r", "\n"), ",", $result[0]["param_value"]);
        $ip_exclusion = "";
        $colomun_name = "";
        if(strlen($abbreviation) == 0) {
            $column_name = "ip_address";
        } else if(strlen($abbreviation) > 0) {
            $column_name = $abbreviation. ".ip_address";
        }
        if(strlen($ip_address) > 0) {
            $ip_exclusion = " AND ". $column_name. " NOT IN ('". $ip_address. "') ";
        }
        
        return $ip_exclusion;
    }
  
    /**
     * exclusive double access
     * 
     * @param string $abbreviation
     * @return string
     */
    public function execlusiveDoubleAccessSubQuery($operation_id, $abbreviation, $start_date, $finish_date) {
        $sub_query = "";
        if($operation_id == RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE) {
            if(strlen($abbreviation) == 0) {
                $sub_query = "SELECT *,DATE_FORMAT(record_date, '%Y%m%d%k%i') FROM ". DATABASE_PREFIX. "repository_log ".
                             "WHERE record_date >= '". $start_date. "' ".
                             "AND record_date <= '". $finish_date. "' ".
                             "AND operation_id = ". $operation_id. " ".
                             "GROUP BY DATE_FORMAT(record_date, '%Y%m%d%k%i'), item_id, item_no, attribute_id, file_no, ".
                             "user_agent, ip_address, operation_id, search_keyword, host, file_status, site_license, ".
                             "input_type, login_status, group_id ";
            } else {
                $sub_query = "SELECT *,DATE_FORMAT(record_date, '%Y%m%d%k%i') FROM ". DATABASE_PREFIX. "repository_log AS ". $abbreviation. " ".
                             "WHERE record_date >= '". $start_date. "' ".
                             "AND record_date <= '". $finish_date. "' ".
                             "AND operation_id = ". $operation_id. " ".
                             "GROUP BY DATE_FORMAT(record_date, '%Y%m%d%k%i'), item_id, item_no, attribute_id, file_no, ".
                             "user_agent, ip_address, operation_id, search_keyword, host, file_status, site_license, ".
                             "input_type, login_status, group_id ";
            }
        } else {
            $sub_query = DATABASE_PREFIX."repository_log";
        }
        
        return $sub_query;
    }
    
    /**
     * exclusive robot
     * 
     * @param string $abbreviation
     * @return string
     */
    public function execlusiveRobotsQuery($abbreviation) {
        $robots = "";
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
                if(strlen($robots) != 0){
                    $robots .= " , ";
                }
                $robots .= "'". $file_line. "'";
            }
            fclose($fp);
        }
        
        $robots_exclusion = "";
        if(strlen($robots) > 0) {
            if(strlen($abbreviation) == 0) {
                $robots_exclusion = " AND (user_agent IS NULL OR user_agent NOT IN (". $robots. ")) ";
            } else if(strlen($abbreviation) > 0) {
                $robots_exclusion = " AND (".$abbreviation. ".user_agent IS NULL OR ".$abbreviation. ".user_agent NOT IN (". $robots. ")) ";
            }
        }
        
        return $robots_exclusion;
    }
    
    /**
     * format date of year
     * 
     * @param string $abbreviation
     * @return string
     */
    public function dateformatYearQuery($abbreviation) {
        $year = "";
        if(strlen($abbreviation) == 0) {
            $year = " DATE_FORMAT(record_date, '%Y') AS YEAR ";
        } else if(strlen($abbreviation) > 0) {
            $year = " DATE_FORMAT(". $abbreviation. ".record_date, '%Y') AS YEAR ";
        }
        
        return $year;
    }
    
    /**
     * make query parts of year
     * 
     * @param string $abbreviation
     * @return string
     */
    public function perYearQuery() {
        $group_year = " GROUP BY YEAR ";
        
        return $group_year;
    }
    
    /**
     * format date of month
     * 
     * @param string $abbreviation
     * @return string
     */
    public function dateformatMonthlyQuery($abbreviation) {
        $monthly = "";
        if(strlen($abbreviation) == 0) {
            $monthly = " DATE_FORMAT(record_date, '%m') AS MONTHLY ";
        } else if(strlen($abbreviation) > 0) {
            $monthly = " DATE_FORMAT(". $abbreviation. ".record_date, '%m') AS MONTHLY ";
        }
        
        return $monthly;
    }
    
    /**
     * format make query parts of month
     * 
     * @param string $abbreviation
     * @return string
     */
    public function perMonthlyQuery() {
        $group_monthly = " GROUP BY MONTHLY ";
        
        return $group_monthly;
    }
    
    /**
     * format date of week
     * 
     * @param string $abbreviation
     * @return string
     */
    public function dateformatWeeklyQuery($abbreviation) {
        $weekly = "";
        if(strlen($abbreviation) == 0) {
            $weekly = " DATE_FORMAT(record_date, '%U') AS WEEKLY ";
        } else if(strlen($abbreviation) > 0) {
            $weekly = " DATE_FORMAT(". $abbreviation. ".record_date, '%U') AS WEEKLY ";
        }
        
        return $weekly;
    }
    
    /**
     * make query parts of week
     * 
     * @param string $abbreviation
     * @return string
     */
    public function perWeeklyQuery() {
        $group_weekly = " GROUP BY WEEKLY ";
        
        return $group_weekly;
    }
    
    /**
     * format date of day
     * 
     * @param string $abbreviation
     * @return string
     */
    public function dateformatDailyQuery($abbreviation) {
        $daily = "";
        if(strlen($abbreviation) == 0) {
            $daily = " DATE_FORMAT(record_date, '%d') AS DAILY ";
        } else if(strlen($abbreviation) > 0) {
            $daily = " DATE_FORMAT(". $abbreviation. ".record_date, '%d') AS DAILY ";
        }
        
        return $daily;
    }
    
    /**
     * make query parts of day
     * 
     * @param string $abbreviation
     * @return string
     */
    public function perDailyQuery() {
        $group_daily = " GROUP BY DAILY ";
        
        return $group_daily;
    }
    
    /**
     * check site license
     * 
     * @param string $abbreviation
     * @param array $start_ip
     * @param array $finish_ip
     * @param array $user_id
     * @return string
     */
    public function checkSitelicenseQuery($abbreviation, $start_ip, $finish_ip, $user_id) {
        // カラム名の設定
        if(strlen($abbreviation) > 0) {
            $ip_column = $abbreviation.".numeric_ip_address";
            $user_column = $abbreviation.".user_id";
        } else {
            $ip_column = "numeric_ip_address";
            $user_column = "user_id";
        }
        
        // サブクエリ文作成
        // IPアドレス判定
        $sitelicense = "";
        for($ii = 0; $ii < count($start_ip); $ii++) {
            // IPが無い場合はスルー
            if(strlen($start_ip[$ii]) > 0 && strlen($finish_ip[$ii]) > 0) {
                 // IPが複数ある場合はORで繋ぐ
                if(strlen($sitelicense) > 0) {
                    $sitelicense .= " OR ";
                }
                // 終了IPが未設定だった場合は一致検索を行う
                if(strlen($finish_ip[$ii]) == 0) {
                    $sitelicense .= $ip_column. " = ". $start_ip[$ii];
                } else {
                    // IP範囲の設定
                    $start_ip_address = 0;
                    $finish_ip_address = 0;
                    // 開始IPの方が終了IPより大きい設定がされていた場合
                    if($start_ip[$ii] > $finish_ip[$ii]) {
                        // 開始IPと終了IPを入れ替える
                        $start_ip_address = $finish_ip[$ii];
                        $finish_ip_address = $start_ip[$ii];
                    } else {
                        $start_ip_address = $start_ip[$ii];
                        $finish_ip_address = $finish_ip[$ii];
                    }
                    $sitelicense .= "(".
                                    $start_ip_address. " <= ". $ip_column.
                                    " AND ".
                                    $ip_column. " <= ". $finish_ip_address.
                                    ")";
                }
            }
        }
        // 組織所属判定
        if(count($user_id) > 0) {
            if(strlen($sitelicense) > 0) {
                $sitelicense .= " OR ";
            }
            $sitelicense .= $user_column. " IN ( ";
            for($ii = 0; $ii < count($user_id); $ii++) {
                if($ii > 0) {
                    $sitelicense .= ", ";
                }
                $sitelicense .= "'". $user_id[$ii]. "'";
            }
            $sitelicense .= " ) ";
        }
        
        if(strlen($sitelicense) > 0) {
            $sitelicense = " AND ( ". $sitelicense. " ) ";
        }
        
        return $sitelicense;
    }
    
    /**
     * check site license
     * 
     * @param string $abbreviation
     * @param array $item_type_id
     * @return string
     */
    public function exclusiveSitelicenseItemtypeQuery($abbreviation, $item_type_id) {
        // カラム名の設定
        if(strlen($abbreviation) > 0) {
            $item_type_id_column = $abbreviation. ".item_type_id";
        } else {
            $item_type_id_column = "item_type_id";
        }
        
        // サブクエリ文作成
        $exclusive_item_type = "";
        if(count($item_type_id) > 0) {
            $exclusive_item_type .= " AND ". $item_type_id_column. " NOT IN ( ";
            for($ii = 0; $ii < count($item_type_id); $ii++) {
                if($ii > 0) {
                    $exclusive_item_type .= ", ";
                }
                $exclusive_item_type .= $item_type_id[$ii];
            }
            $exclusive_item_type .= " ) ";
        }
        
        return $exclusive_item_type;
    }
}
?>