<?php
// --------------------------------------------------------------------
//
// $Id: LogAnalyzor.class.php 41652 2014-09-17 08:16:26Z tomohiro_ichikawa $
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
}
?>