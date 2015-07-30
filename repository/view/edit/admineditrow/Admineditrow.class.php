<?php
// --------------------------------------------------------------------
//
// $Id: Admineditrow.class.php 42605 2014-10-03 01:02:01Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_Admineditrow extends RepositoryAction
{
	// 表示タブ情報
	var $admin_active_tab = null;
    public $error_msg = null;
	
	public $search_setup = null;
	
    // bug fix return from this class, no set prefix 2014/07/03 T.Koyasu --start--
    public $prefixJalcDoi = null;
    public $prefixCrossRef = null;
    public $prefixCnri = null;
    public $prefixYHandle = null;
    // bug fix return from this class, no set prefix 2014/07/03 T.Koyasu --end--
    
    // OAI-PMH Output Flag
    public $oaipmh_output_flag = null;
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	// 表示タブ情報
    	if($this->admin_active_tab == ""){
			$this->admin_active_tab = 0;
		}
        
        $this->error_msg = $this->Session->getParameter("error_msg");
        $this->Session->removeParameter("error_msg");
        
    	$admin_params = $this->Session->getParameter("admin_params");
    	
        // ----------------------------------------------------
        // 表示用インデックス情報を作成	        
        // ----------------------------------------------------
        // set lang
    	$this->setLangResource();
		$lang = $this->Session->getParameter("_lang");
    	
		if(!is_array($admin_params["default_disp_index"]["param_value"])){
	       	// インデックス名を取得
	       	$query = "SELECT index_name, index_name_english ".
	       	         "FROM ". DATABASE_PREFIX ."repository_index ".		// インデックス
	       			 "WHERE index_id = ? ".		// index_id
	       			 "AND is_delete = ?; ";		// 削除フラグ
	       	$params = null;
	       	$params[] = $admin_params["default_disp_index"]["param_value"];
	       	$params[] = 0;
	    	//　SELECT実行
	        $result = $this->Db->execute($query, $params);
	       	if ($result === false) {
		        $errNo = $this->Db->ErrorNo();
		        $errMsg = $this->Db->ErrorMsg();
		        $this->Session->setParameter("error_code",$errMsg);
	       		if($istest) { echo $errMsg . "<br>"; }
		        return 'error';
	    	}
	    	if(count($result)<1){
	    		$index_name = $this->Session->getParameter("smartyAssign")->getLang("repository_admin_root_index");
	    		$admin_params["default_disp_index"]["param_value"] = 0;
	    	} else {
	    		if($lang == "japanese"){
	    			$index_name = $result[0]['index_name'];
	    		} else {
	    			$index_name = $result[0]['index_name_english'];
	    		}
	    	}
	
	    	$default_index_data = array($index_name, $admin_params["default_disp_index"]["param_value"]);
	    	$admin_params["default_disp_index"]["param_value"] = $default_index_data;
		}
    	
        // get sort name
        $sort_disp_num = $admin_params["sort_disp_num"]["param_value"];
        $sort_not_disp_num = $admin_params["sort_not_disp_num"]["param_value"];
        
    	if(count($sort_disp_num) == 1 && $sort_disp_num[0] == ""){
			$sort_disp_num = array();
		}
		if(count($sort_not_disp_num) == 1 && $sort_not_disp_num[0] == ""){
			$sort_not_disp_num = array();
		}
    	
        $sort_disp_name_array = array();
        $sort_not_disp_name_array = array();
        for($ii=0; $ii<count($sort_disp_num); $ii++){
            $sort_disp_name = $this->getSortName($sort_disp_num[$ii]);
            array_push($sort_disp_name_array, $sort_disp_name);
        }
        $admin_params["sort_disp_name"]["param_value"] = $sort_disp_name_array;
        
        for($ii=0; $ii<count($sort_not_disp_num); $ii++){
        	$sort_not_disp_name = $this->getSortName($sort_not_disp_num[$ii]);
        	array_push($sort_not_disp_name_array, $sort_not_disp_name);
        }
        $admin_params["sort_not_disp_name"]["param_value"] = $sort_not_disp_name_array;
        
        if(!is_array($admin_params["sort_disp_default"]["param_value"])){
        	$admin_params["sort_disp_default"]["param_value"] = explode("|", $admin_params["sort_disp_default"]["param_value"]);
        }
        
        if($admin_params["disp_index_type"]["param_value"] == null){
        	//DBから取得
	       	$query = "SELECT param_value ".
	       	         "FROM ". DATABASE_PREFIX ."repository_parameter ".
	       			 "WHERE param_name = 'disp_index_type'; ";
	    	//　SELECT実行
	        $result = $this->Db->execute($query);
	       	if ($result === false) {
		        $errNo = $this->Db->ErrorNo();
		        $errMsg = $this->Db->ErrorMsg();
		        $this->Session->setParameter("error_code",$errMsg);
	       		if($istest) { echo $errMsg . "<br>"; }
		        return 'error';
	    	}
	    	$admin_params["disp_index_type"]["param_value"] = $result[0]["param_value"];
        }
        
        $this->Session->setParameter("admin_params", $admin_params);
        
        // Add Default External Word 2014/06/09 T.Ichikawa --start--
        $defaultStopWord = "";
        $fp = fopen(WEBAPP_DIR. '/modules/repository/config/defaultExternalSearchStopword', "r");
        while($row = fgets($fp)) {
            $defaultStopWord .= str_replace("\r\n", "\n", $row);
        }
        fclose($fp);
        $this->Session->setParameter("default_external_search_word", $defaultStopWord);
        // Add Default External Word 2014/06/09 T.Ichikawa --end--
    	
        $this->getSearchSetting();
        
        // bug fix return from this class, no set prefix 2014/07/03 T.Koyasu --start--
        $this->getEachPrefix();
        // bug fix return from this class, no set prefix 2014/07/03 T.Koyasu --end--
        
        //OAI-PMH Output Flag
        $this->oaipmh_output_flag = $admin_params["oaipmh_output_flag"]["param_value"];
    	
    	return 'success';
    }
    
    /**
     * get search setup data
     */
    private function getSearchSetting()
    {
        $query = "SELECT `type_id`, `search_type`, `use_search`, `default_show`, `junii2_mapping` ".
                 "FROM ".DATABASE_PREFIX. "repository_search_item_setup ".
                 "ORDER BY type_id ASC ;";
        $search_condition = $this->Db->execute($query);
        $smartyAssign = $this->Session->getParameter("smartyAssign");
        for($searchCnt = 0; $searchCnt < count($search_condition); $searchCnt++)
        {
            $this->search_setup[$searchCnt]["type_id"] = $search_condition[$searchCnt]["type_id"];
            $this->search_setup[$searchCnt]["show_name"] = $smartyAssign->getLang($search_condition[$searchCnt]["search_type"]);
            $this->search_setup[$searchCnt]["use_flag"] = $search_condition[$searchCnt]["use_search"];
            $this->search_setup[$searchCnt]["default_flag"] = $search_condition[$searchCnt]["default_show"];
            $this->search_setup[$searchCnt]["mapping"] = $search_condition[$searchCnt]["junii2_mapping"];
        }
    }
    
    // Bug Fix WEKO-2014-039 no inherit prefix from html 2014/07/11 --start--
    /**
     * get each prefix
     *
     */
    private function getEachPrefix()
    {
        $this->prefixCnri = $this->Session->getParameter("prefixCnri");
        $this->prefixJalcDoi = $this->Session->getParameter("prefixJalcDoi");
        $this->prefixCrossRef = $this->Session->getParameter("prefixCrossRef");
        
        $this->Session->removeParameter("prefixCnri");
        $this->Session->removeParameter("prefixJalcDoi");
        $this->Session->removeParameter("prefixCrossRef");
        
        $DATE = new Date();
        $this->TransStartDate = $DATE->getDate().".000";
        $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->Db,  $this->TransStartDate);
        $this->prefixYHandle = $repositoryHandleManager->getYHandlePrefix();
    }
    // Bug Fix WEKO-2014-039 no inherit prefix from html 2014/07/11 --end--
}
?>
