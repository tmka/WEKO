<?php
// --------------------------------------------------------------------
//
// $Id: RepositoryUsagestatistics.class.php 19124 2012-08-20 01:18:33Z atsushi_suzuki $
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
 * Repository module usage statistics common class
 *
 * @package repository
 * @access  public
 */
class RepositoryUsagestatistics extends RepositoryAction
{
    // -------------------------------------------------------
    // Member
    // -------------------------------------------------------
    /**
     * user_id
     *
     * @var string
     */
    private $user_id = "";
    
    // -------------------------------------------------------
    // Constructor
    // -------------------------------------------------------
    /**
     * Constructor
     *
     * @param Session $Session
     * @param Db $Db
     * @param string $TransStartDate
     * @param string user_id
     * @return RepositoryPdfCover
     * @access public
     */
    public function RepositoryUsagestatistics($Session, $Db, $TransStartDate, $user_id="")
    {
        // Set member of RepositoryAction
        $this->Session = $Session;
        $this->Db = $Db;
        $this->TransStartDate = $TransStartDate;
        if(strlen($user_id) > 0)
        {
            $this->user_id = $user_id;
        }
        else
        {
            $this->user_id = $this->Session->getParameter("_user_id");
        }
        
    }
    
    // -------------------------------------------------------
    // PUBLIC
    // -------------------------------------------------------
    /**
     * Aggregate usage statistics
     *
     * @return bool
     */
    public function aggregateUsagestatistics()
    {
        // Get the oldest date at log table
        $oldestDate = $this->getOldestDateAtLogTable();
        
        // Delete usagestatistics record from the oldest log date
        if(!$this->deleteUsageStatisticsRecords($oldestDate))
        {
            return false;
        }
        
        // Insert records to usagestatistics table
        if(!$this->insertUsageStatistics())
        {
            return false;
        }
        
        // Update paremeter 'update_usage_statistics_last_date'
        if(!$this->updateParameterUsageStatisticsLastDate())
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get views
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $year
     * @param int $month
     * @return array $retArray["total"] = int
     *                        ["byDomain"][DOMAINNAME]["cnt"] = int
     *                                                ["rate"] = double
     *                                                ["img"] = string
     */
    public function getUsagesViews($itemId, $itemNo, $year, $month)
    {
        $retArray = array();
        
        // Get views
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_USAGESTATISTICS." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_OPERATION_ID." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = sprintf("%d-%02d", $year, $month);
        $params[] = 3;
        $result = $this->Db->execute($query, $params);
        if($result===false)
        {
            return $retArray;
        }
        
        $total = 0;
        $byDomain = array();
        foreach($result as $record)
        {
            $domain = $record[RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_DOMAIN];
            $count = $record[RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_CNT];
            
            $total += intval($count);
            $this->setArrayByDomain($byDomain, $domain, $count);
        }
        arsort($byDomain);
        
        $this->setUsagestatisticsArray($retArray, $total, $byDomain);
        
        return $retArray;
    }
    
    /**
     * Get downloads by file
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attributeId
     * @param int $fileNo
     * @param int $year
     * @param int $month
     * @return array $retArray["total"] = int
     *                        ["byDomain"][DOMAINNAME]["cnt"] = int
     *                                                ["rate"] = double
     *                                                ["img"] = string
     */
    public function getUsagesDownloadsByFile($itemId, $itemNo, $attributeId, $fileNo, $year, $month)
    {
        $retArray = array();
        
        // Get downloads by file
        $query = "SELECT * ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_USAGESTATISTICS." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_OPERATION_ID." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = sprintf("%d-%02d", $year, $month);
        $params[] = 2;
        $result = $this->Db->execute($query, $params);
        if($result===false)
        {
            return $retArray;
        }
        
        $total = 0;
        $byDomain = array();
        foreach($result as $record)
        {
            $domain = $record[RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_DOMAIN];
            $count = $record[RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_CNT];
            
            $total += intval($count);
            $this->setArrayByDomain($byDomain, $domain, $count);
        }
        
        arsort($byDomain);
        
        $this->setUsagestatisticsArray($retArray, $total, $byDomain);
        
        return $retArray;
    }
    
    /**
     * Get downloads by item
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $year
     * @param int $month
     * @return array $retArray[NUM]["item_id"] = int
     *                             ["item_no"] = int
     *                             ["attribute_id"] = int
     *                             ["file_no"] = int
     *                             ["file_name"] = string
     *                             ["display_name"] = string
     *                             ["usagestatistics"]["total"] = int
     *                                                ["byDomain"][DOMAINNAME]["cnt"] = int
     *                                                                        ["rate"] = double
     *                                                                        ["img"] = string
     */
    public function getUsagesDownloads($itemId, $itemNo, $year, $month)
    {
        $retArray = array();
        
        // Get files data
        $query = "SELECT FILE.* ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_FILE." AS FILE, ".
                 "     ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_ITEM_ATTR_TYPE." AS ATTRTYPE ".
                 "WHERE FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_COMMON_IS_DELETE." = ? ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_TYPE_ID." = ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ITEM_TYPE_ID." ".
                 "AND FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID." = ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_ATTRIBUTE_ID." ".
                 "AND ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_HIDDEN." = 0 ".
                 "ORDER BY ATTRTYPE.".RepositoryConst::DBCOL_REPOSITORY_ITEM_ATTR_TYPE_SHOW_ORDER.", FILE.".RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO.";";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = 0;
        $result = $this->Db->execute($query, $params);
        if($result===false)
        {
            return $retArray;
        }
        
        foreach($result as $fileData)
        {
            $usagestatistics = $this->getUsagesDownloadsByFile(
                                    $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],
                                    $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO],
                                    $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],
                                    $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO],
                                    $year, $month);
            if(count($usagestatistics)==0)
            {
                continue;
            }
            
            array_push( $retArray,
                        array(  RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_ID],
                                RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ITEM_NO],
                                RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_ATTRIBUTE_ID],
                                RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NO],
                                RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_FILE_NAME],
                                RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_NAME => $fileData[RepositoryConst::DBCOL_REPOSITORY_FILE_DISPLAY_NAME],
                                "usagestatistics" => $usagestatistics
                        )
            );
        }
        
        return $retArray;
    }
    
    /**
     * Get downloads by file in real time
     *
     * @param int $itemId
     * @param int $itemNo
     * @param int $attributeId
     * @param int $fileNo
     * @return int
     */
    public function getUsagesDownloadsNow($itemId, $itemNo, $attributeId, $fileNo)
    {
        $retCnt = 0;
        
        // Get latest month in usagestatistics table
        $latestDate = $this->getLatestDateAtUsagestatisticsTable();
        
        // Get download count from usagestatistics table
        $retCnt += $this->getDownloadCountFromUsagestatistics(
                            $itemId, $itemNo, $attributeId, $fileNo, $latestDate);
        
        // Get download count from log table
        $retCnt += $this->getDownloadCountFromLog(
                            $itemId, $itemNo, $attributeId, $fileNo, $latestDate);
        
        return $retCnt;
    }
    
    /**
     * Check exist record in usagestatistics table
     *
     * @param int $itemId
     * @param int $itemNo
     * @return bool true: exist records / false: not exist record
     */
    public function checkUsageStatisticsRecords($itemId, $itemNo)
    {
        $ret = false;
        
        $query = "SELECT COUNT(*) ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_USAGESTATISTICS." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE." < DATE_FORMAT(NOW(), '%Y-%m');";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $result = $this->Db->execute($query, $params);
        if($result !== false && intval($result[0]['COUNT(*)']) > 0)
        {
            $ret = true;
        }
        
        return $ret;
    }
    
    // -------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------
    /**
     * Get the oldest date at log table
     *
     * @return string
     */
    private function getOldestDateAtLogTable()
    {
        // Get the oldest date (format: YYYY-MM)
        $query = "SELECT MIN(DATE_FORMAT(".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE.", '%Y-%m')) AS oldestDate ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1)
        {
            return "";
        }
        
        return $result[0]["oldestDate"];
    }
    
    /**
     * Delete usagestatistics records from the oldest log date
     *
     * @return bool
     */
    private function deleteUsageStatisticsRecords($oldestDate)
    {
        if(strlen($oldestDate) == 0)
        {
            return false;
        }
        $query = "DELETE FROM ".DATABASE_PREFIX."repository_usagestatistics ".
                 "WHERE record_date >= ?; ";
        $params = array();
        $params[] = $oldestDate;
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Insert usagestatistics records
     *
     * @return bool
     */
    private function insertUsageStatistics()
    {
        // Get log exclusion
        $logExclusion = $this->createLogExclusion(0, false);
        
        // Aggregate views
        if(!$this->insertUsageStatisticsRecords($this->TransStartDate, RepositoryConst::LOG_OPERATION_DETAIL_VIEW, $logExclusion))
        {
            return false;
        }
        
        // Aggregate downloads
        if(!$this->insertUsageStatisticsRecords($this->TransStartDate, RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE, $logExclusion))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Insert usagestatistics records 
     *
     * @param string $insDate
     * @param int $operationId
     * @param string $logExclusion
     * @return bool
     */
    private function insertUsageStatisticsRecords($insDate, $operationId, $logExclusion)
    {
        $query = "INSERT INTO ".DATABASE_PREFIX."repository_usagestatistics (record_date, item_id, item_no, attribute_id, file_no, operation_id, domain, cnt, ins_user_id, ins_date) ".
                 "SELECT DATE_FORMAT(record_date, '%Y-%m') AS yearMonth, item_id, item_no, attribute_id, file_no, operation_id, SUBSTRING_INDEX(host, '.', -2)AS domain, COUNT(*) AS cnt, ?, ? ".
                 "FROM ".DATABASE_PREFIX."repository_log ".
                 "WHERE operation_id = ? ".
                 "AND item_id >= 1 ".
                 "AND item_no >= 1 ".
                 $logExclusion.
                 "GROUP BY item_id, attribute_id, file_no, yearMonth, domain;";
        $params = array();
        $params[] = $this->user_id; // ins_user_id
        $params[] = $insDate;       // ins_date
        $params[] = $operationId;   // operation_id
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Update paremeter 'update_usage_statistics_last_date'
     *
     * @return bool
     */
    private function updateParameterUsageStatisticsLastDate()
    {
        $DATE = new Date();
        $execute_time = str_replace("-","/",$DATE->getDate());
        
        // Set parameter
        $params = array();
        $params[] = $execute_time;                          // param_value
        $params[] = $this->user_id;                         // mod_user_id
        $params[] = $this->TransStartDate;                  // mod_date
        $params[] = 'update_usage_statistics_last_date';    // param_name
        $result = $this->updateParamTableData($params, $Error_Msg);
        if ($result === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * set array by domain
     * 
     * @param array &$domainArray
     * @param string $domain
     * @param int $count
     * @return bool
     */
    private function setArrayByDomain(&$domainArray, $domain, $count)
    {
        $pattern = "/.*(".RepositoryConst::USAGESTATISTICS_DOMAIN_COM.
                   "|".RepositoryConst::USAGESTATISTICS_DOMAIN_ORG.
                   "|".RepositoryConst::USAGESTATISTICS_DOMAIN_AC_JP.
                   "|".RepositoryConst::USAGESTATISTICS_DOMAIN_CO_JP.
                   "|".RepositoryConst::USAGESTATISTICS_DOMAIN_GO_JP.
                   "|".RepositoryConst::USAGESTATISTICS_DOMAIN_EDU.")$/";
        $matches = array();
        if(preg_match($pattern, $domain, $matches))
        {
            if(key_exists($matches[1], $domainArray))
            {
                $domainArray[$matches[1]]["cnt"] += $count;
            }
            else
            {
                $domainArray[$matches[1]]["cnt"] = $count;
                $domainArray[$matches[1]]["img"] = $this->getFlagImagePath($matches[1]);
            }
        }
        else
        {
            // Other Country or unknown
            // Get top domain
            $topDomain = RepositoryConst::USAGESTATISTICS_DOMAIN_UNKNOWN;
            $imgPath = "";
            $matches = array();
            $pattern = "/\.?([^.]*)$/";
            preg_match($pattern, $domain, $matches);
            if(isset($matches[1]) && strlen($matches[1]) > 0)
            {
                // Check flag image for top domain is exist.
                // if no exist image, this domain is 'unknown'.
                $imgPath = $this->getFlagImagePath($matches[1]);
                if(strlen($imgPath) > 0)
                {
                    $topDomain = $matches[1];
                }
            }
            if(key_exists($topDomain, $domainArray))
            {
                $domainArray[$topDomain]["cnt"] += $count;
            }
            else
            {
                $domainArray[$topDomain]["cnt"] = $count;
                $domainArray[$topDomain]["img"] = $imgPath;
            }
        }
        return true;
    }
    
    /**
     * Set array for usagestatistics data
     * 
     * @param array &$retArray
     * @param int $total
     * @param array $byDomain
     * @return bool
     */
    private function setUsagestatisticsArray(&$retArray, $total, $byDomain)
    {
        foreach($byDomain as $key => $val)
        {
            if($total > 0)
            {
                $byDomain[$key]["rate"] = $val["cnt"]/$total*100;
            }
            else
            {
                $byDomain[$key]["rate"] = 0;
            }
        }
        
        $retArray["total"] = $total;
        $retArray["byDomain"] = $byDomain;
        
        return true;
    }
    
    /**
     * Get flag image path
     * 
     * @param string $domain
     * @return string
     */
    private function getFlagImagePath($domain)
    {
        $imgPath = "";
        $flagDir = "/images/repository/flags/";
        $topDomain = "";
        
        if(preg_match("/.*".RepositoryConst::USAGESTATISTICS_DOMAIN_AC_JP."$/", $domain)
            || preg_match("/.*".RepositoryConst::USAGESTATISTICS_DOMAIN_CO_JP."$/", $domain)
            || preg_match("/.*".RepositoryConst::USAGESTATISTICS_DOMAIN_GO_JP."$/", $domain))
        {
            $topDomain = RepositoryConst::USAGESTATISTICS_DOMAIN_JP;
        }
        else if(preg_match("/.*".RepositoryConst::USAGESTATISTICS_DOMAIN_EDU."$/", $domain))
        {
            $topDomain = RepositoryConst::USAGESTATISTICS_DOMAIN_US;
        }
        else
        {
            $matches = array();
            $pattern = "/\.?([^.]*)$/";
            preg_match($pattern, $domain, $matches);
            if(isset($matches[1]) && strlen($matches[1]) > 0)
            {
                $topDomain = $matches[1];
            }
        }
        
        if(strlen($topDomain) > 0 && file_exists(HTDOCS_DIR.$flagDir.$topDomain.".png"))
        {
            $imgPath = ".".$flagDir.$topDomain.".png";
        }
        return $imgPath;
    }
    
    /**
     * Get the latest date at usagestatistics table
     *
     * @return string
     */
    private function getLatestDateAtUsagestatisticsTable()
    {
        // Get the latest date (format: YYYY-MM)
        $query = "SELECT MAX(".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE.") AS latestDate ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_USAGESTATISTICS." ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1)
        {
            return "";
        }
        
        return $result[0]["latestDate"];
    }
    
    /**
     * Get download count from usagestatistics table
     *
     * @return int
     */
    private function getDownloadCountFromUsagestatistics(
                        $itemId, $itemNo, $attributeId, $fileNo, $latestDate)
    {
        if(strlen($latestDate) == 0)
        {
            return 0;
        }
        
        // Get download count
        $query = "SELECT SUM(".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_CNT.") AS cnt ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_USAGESTATISTICS." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_RECORD_DATE." < ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_USAGESTATISTICS_OPERATION_ID." = ? ;";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = $latestDate;
        $params[] = RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE;
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)!=1)
        {
            return 0;
        }
        
        return intval($result[0]['cnt']);
    }
    
    /**
     * Get download count from log table
     *
     * @return int
     */
    private function getDownloadCountFromLog(
                        $itemId, $itemNo, $attributeId, $fileNo, $latestDate)
    {
        // Get log exclusion
        $logExclusion = $this->createLogExclusion(0, false);
        
        // Get download count
        $query = "SELECT COUNT(*) ".
                 "FROM ".DATABASE_PREFIX.RepositoryConst::DBTABLE_REPOSITORY_LOG." ".
                 "WHERE ".RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_LOG_ITEM_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_LOG_ATTRIBUTE_ID." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_LOG_FILE_NO." = ? ".
                 "AND ".RepositoryConst::DBCOL_REPOSITORY_LOG_OPERATION_ID." = ? ".
                 $logExclusion." ";
        $params = array();
        $params[] = $itemId;
        $params[] = $itemNo;
        $params[] = $attributeId;
        $params[] = $fileNo;
        $params[] = RepositoryConst::LOG_OPERATION_DOWNLOAD_FILE;
        
        if(strlen($latestDate) > 0)
        {
            $query .= "AND ".RepositoryConst::DBCOL_REPOSITORY_LOG_RECORD_DATE." >= ? ;";
            $params[] = $latestDate;
        }
        
        $result = $this->Db->execute($query, $params);
        if($result === false || count($result)!=1)
        {
            return 0;
        }
        
        return intval($result[0]['COUNT(*)']);
    }
}

?>
