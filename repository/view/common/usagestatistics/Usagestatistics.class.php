<?php
// --------------------------------------------------------------------
//
// $Id: Usagestatistics.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';

/**
 * Usage statistics
 *
 * @package     NetCommons
 * @author      A.Suzuki(IVIS)
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Repository_View_Common_Usagestatistics extends RepositoryAction
{
    // -------------------------------------
    // member
    // -------------------------------------
    /**
     * item_id
     *
     * @var int
     */
    public $itemId = null;
    
    /**
     * item_no
     *
     * @var int
     */
    public $itemNo = null;
    
    /**
     * year
     *
     * @var int
     */
    public $year = null;
    
    /**
     * month
     *
     * @var int
     */
    public $month = null;
    
    /**
     * views data
     *
     * @var array $usagesViews["total"] = int
     *                        ["byDomain"][DOMAINNAME]["cnt"] = int
     *                                                ["rate"] = double
     *                                                ["img"] = string
     */
    public $usagesViews = array();
    
    /**
     * downloads data
     *
     * @var array $usagesDownloads[NUM]["item_id"] = int
     *                                 ["item_no"] = int
     *                                 ["attribute_id"] = int
     *                                 ["file_no"] = int
     *                                 ["file_name"] = string
     *                                 ["display_name"] = string
     *                                 ["usagestatistics"]["total"] = int
     *                                                    ["byDomain"][DOMAINNAME]["cnt"] = int
     *                                                                            ["rate"] = double
     *                                                                            ["img"] = string
     */
    public $usagesDownloads = array();
    
    /**
     * date list
     *
     * @var array
     */
    public $dateList = array();
    
    /**
     * title
     *
     * @var string
     */
    public $title = "";
    
    /**
     * display date
     *
     * @var string
     */
    public $displayDate = "";
    
    // -------------------------------------
    // public
    // -------------------------------------
    /**
     * Execute
     * 
     * @return string
     */
    public function executeApp()
    {
        try
        {
            // Init action
            $result = $this->initAction();
            if($result === false)
            {
                $this->failTrans();
                $this->exitAction();
                return "error";
            }
            
            // Set Usage statistics setting 2015/03/24 K.Sugimoto --start--
            $result = $this->getAdminParam('usagestatistics_link_display', $usagestatistics_link_display, $Error_Msg);
            if ( $result == false ){
                $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 ); //主メッセージとログIDを指定して例外を作成
                $DetailMsg = null;                              //詳細メッセージ文字列作成
                sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
                $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
                $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
                throw $exception;
            }
            if ($usagestatistics_link_display == 0)
            {
            	return "invalid";
            }
            // Set Usage statistics setting 2015/03/24 K.Sugimoto --end--
            
            // Set item title
            $this->setItemTitle();
            
            // Get date array for pulldown
            $this->dateList = $this->setDateList();
            
            $this->infoLog("businessUsagestatistics", __FILE__, __CLASS__, __LINE__);
            $RepositoryUsagestatistics = BusinessFactory::getFactory()->getBusiness("businessUsagestatistics");
            
            // Get usages views
            $this->usagesViews = $RepositoryUsagestatistics->getUsagesViews($this->itemId, $this->itemNo, $this->year, $this->month);
            
            // Get usages downloads
			$this->usagesDownloads = $RepositoryUsagestatistics->getUsagesDownloads($this->itemId, $this->itemNo, $this->year, $this->month);
            
            $this->exitAction();
            return "success";
        }
        catch (RepositoryException $exception)
        {
            $this->failTrans();
            $this->exitAction();
            return "error";
        }
    }
    
    // -------------------------------------
    // private
    // -------------------------------------
    /**
     * Set date List for pulldown
     *
     * @return array
     */
    private function setDateList()
    {
        $retArray = array();
        
        // Get the oldest date at usagestatistics table
        $oldestDate = $this->getOldestDateAtUsageStatisticsTable();
        
        // Get previous month
        $prevMonth = $this->getPreviousMonth();
        
        // Create date list for pulldown
        // Date format: japanese => "YYYY年MM月" / english => MM/YYYY
        if(strlen($oldestDate) == 0)
        {
            $oldestDate = $prevMonth;
        }
        
        $this->setLangResource();
        $dateFormat = $this->Session->getParameter("smartyAssign")->getLang("repository_usagestatistics_date_format");
        $oldestDateArray = explode("-", $oldestDate, 2);
        $oldestYear = intval($oldestDateArray[0]);
        $oldestMonth = intval($oldestDateArray[1]);
        $prevMonthArray = explode("-", $prevMonth, 2);
        $nowYear = intval($prevMonthArray[0]);
        $nowMonth = intval($prevMonthArray[1]);
        $validDate = false;
        
        for($tmpYear=$oldestYear; $tmpYear<=$nowYear; $tmpYear++)
        {
            // Set tmpMonth
            $tmpMonth = 1;
            if($tmpYear == $oldestYear)
            {
                $tmpMonth = $oldestMonth;
            }
            
            // Set limitMonth
            $limitMonth = 12;
            if($tmpYear == $nowYear)
            {
                $limitMonth = $nowMonth;
            }
            
            for(; $tmpMonth<=$limitMonth; $tmpMonth++)
            {
                $value = sprintf("%d-%02d", $tmpYear, $tmpMonth);
                $display = sprintf($dateFormat, $tmpYear, $tmpMonth);
                $isSelected = false;
                if($tmpYear == intval($this->year) && $tmpMonth == intval($this->month))
                {
                    $isSelected = true;
                    $validDate = true;
                    $this->displayDate = $display;
                }
                
                $dateArray = array( "value" => $value,
                                    "display" => $display,
                                    "selected" => $isSelected);
                array_push($retArray, $dateArray);
            }
        }
        
        if(!$validDate)
        {
            $this->year = $nowYear;
            $this->month = $nowMonth;
            if(isset($retArray[count($retArray)-1]["selected"]))
            {
                $retArray[count($retArray)-1]["selected"] = true;
            }
            $this->displayDate = sprintf($dateFormat, $this->year, $this->month);
        }
        
        return $retArray;
    }
    
    /**
     * Get the oldest date at usagestatistics table
     *
     * @return string
     */
    private function getOldestDateAtUsageStatisticsTable()
    {
        // Get the oldest date (format: YYYY-MM)
        $query = "SELECT MIN(record_date) AS record_date ".
                 "FROM ".DATABASE_PREFIX."repository_usagestatistics ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1)
        {
            return "";
        }
        
        return $result[0]["record_date"];
    }
    
    /**
     * Get previous month
     *
     * @return string
     */
    private function getPreviousMonth()
    {
        // Get previous month (format: YYYY-MM)
        $query = "SELECT DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m') AS prevMonth ";
        $result = $this->Db->execute($query);
        if($result === false || count($result)!=1)
        {
            return "";
        }
        
        return $result[0]["prevMonth"];
    }
    
    /**
     * Get item title
     *
     */
    private function setItemTitle()
    {
        $result = array();
        $this->getItemTableData($this->itemId, $this->itemNo, $result, $errMsg);
        
        $title = 0;
        $titleEn = 0;
        
        if( isset($result["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE]) ){
            $title = $result["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE];
        }
        
        if( isset($result["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH]) ){
            $titleEn = $result["item"][0][RepositoryConst::DBCOL_REPOSITORY_ITEM_TITLE_ENGLISH];
        }
        
        if($this->Session->getParameter("_lang")=="japanese")
        {
            $this->title = $title;
            if(strlen($this->title) == 0)
            {
                $this->title = $titleEn;
            }
        }
        else
        {
            $this->title = $titleEn;
            if(strlen($this->title) == 0)
            {
                $this->title = $title;
            }
        }
    }
}
?>