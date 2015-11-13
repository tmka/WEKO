<?php
// --------------------------------------------------------------------
//
// $Id: Ranking.class.php 57108 2015-08-26 01:03:29Z keiya_sugimoto $
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
require_once WEBAPP_DIR. '/modules/repository/components/common/WekoAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/view/main/ranking/Ranking.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Ranking extends WekoAction
{
	// リクエストパラメータを受け取るため
	var $login_id = null;
	var $password = null;
	
	// ユーザの権限レベル
	var $user_authority_id = "";
	
	// Add log reset ranking refer 2010/02/18 K.Ando --start--
	private $rank_num = 5;
	// Add log reset ranking refer 2010/02/18 K.Ando --end--
	
	// Add config management authority 2010/02/23 Y.Nakao --start--
	var $authority_id = "";
	// Add config management authority 2010/02/23 Y.Nakao --end--
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    protected function executeApp()
    {
        $this->isLoginAdministrator();
        
        $this->rank_num = 5;
        $query = "SELECT param_value ". 
                 " FROM ". DATABASE_PREFIX. "repository_parameter ". 
                 " WHERE param_name = ?;";
        $params = array();
        $params[] = 'ranking_disp_num';
        $result = $this->Db->execute($query, $params);
        if($result === false)
        {
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
        }
        if($result[0]['param_value'] != "" && $result[0]['param_value'] != null){
            $this->rank_num = $result[0]['param_value'];
        }

        $this->infoLog("businessRanking", __FILE__, __CLASS__, __LINE__);
        $viewRanking = BusinessFactory::getFactory()->getBusiness("businessRanking");
        $viewRanking->execute();
        
        // update ranking
        $this->debugLog("start insert refer ranking", __FILE__, __CLASS__, __LINE__);
        $this->referRanking($viewRanking);
        
        $this->debugLog("start insert download ranking", __FILE__, __CLASS__, __LINE__);
        $this->downloadRanking($viewRanking);
        
        $this->debugLog("start insert user ranking", __FILE__, __CLASS__, __LINE__);
        $this->userRanking($viewRanking);
        
        $this->debugLog("start insert keyword ranking", __FILE__, __CLASS__, __LINE__);
        $this->keywordRanking($viewRanking);
        
        $this->debugLog("start insert recent ranking", __FILE__, __CLASS__, __LINE__);
        $this->recentRanking($viewRanking);
        
        $this->debugLog("successfully update", __FILE__, __CLASS__, __LINE__);
        print("Successfully updated.\n");
        
        return 'success';
    }

    /**
     * 閲覧回数ランキング計算
     *
     */
    private function referRanking($viewRanking)
    {
		$items = $viewRanking->getReferRanking();   // ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		
  		$len = count($items);

      	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'referRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
  		}
  		$rank_len = count($result);
  		
  		// all delete 'referRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'referRanking'; ".
		$params = null;
		$params[] = $this->accessDate;
		$params[] = 1;
		$this->Db->execute($query, $params);
  		
  		for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
			if($ii < $rank_len){
  			// update
				$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
						 "SET 	disp_name = ?, ".
						 "	  	disp_name_english = ?, ".
						 "	  	disp_value = ?, ".
						 "	  	item_id = ?, ".
						 "	  	item_no = ?, ".
						 "	  	file_no = 0, ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'referRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['title'];
				$params[] = $items[$ii]['title_english'];
				$params[] = $items[$ii]['CNT'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $this->accessDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
			} else {
				// insert
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_ranking ".
    				 	 "(rank_type, rank, disp_name, disp_name_english, disp_value, item_id, ".
						 "item_no, file_no, ins_date, mod_date, del_date, is_delete) ".
	                 	 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$params[] = "referRanking";
				$params[] = $ii+1;
				$params[] = $items[$ii]['title'];
				$params[] = $items[$ii]['title_english'];
				$params[] = $items[$ii]['CNT'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $this->accessDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
			}
  		}
    }

    /**
     * ダウンロードランキング計算
     *
     */
    private function downloadRanking($viewRanking)
    {
		$items = $viewRanking->getDownloadRanking(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
  		$len = count($items);
  		
  		$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'downloadRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
  		}
  		$rank_len = count($result);
  		
  		// all delete 'downloadRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'downloadRanking'; ".
		$params = null;
		$params[] = $this->accessDate;
		$params[] = 1;
		$this->Db->execute($query, $params);
  		
  		for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
			if($ii < $rank_len){
  			// update
				$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
						 "SET 	disp_name = ?, ".
						 "	  	disp_name_english = ?, ".
						 "	  	disp_value = ?, ".
						 "	  	item_id = ?, ".
						 "	  	item_no = ?, ".
						 "	  	file_no = ?, ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'downloadRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$disp_name = null;
				$disp_name_english = null;
				if($items[$ii]['title'] != "" && $items[$ii]['title'] != null){
					$disp_name = $items[$ii]['title']."(".$items[$ii]['file_name'].")";
				}
				if($items[$ii]['title_english'] != "" && $items[$ii]['title_english'] != null){
					$disp_name_english = $items[$ii]['title_english']."(".$items[$ii]['file_name'].")";
				}
				$params[] = $disp_name;
				$params[] = $disp_name_english;
				$params[] = $items[$ii]['CNT'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $items[$ii]['file_no'];
				$params[] = $this->accessDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
			} else {
				// insert
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_ranking ".
    				 	 "(rank_type, rank, disp_name, disp_name_english, disp_value, item_id, ".
						 "item_no, file_no, ins_date, mod_date, del_date, is_delete) ".
	                 	 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$disp_name = null;
				$disp_name_english = null;
				if($items[$ii]['title'] != "" && $items[$ii]['title'] != null){
					$disp_name = $items[$ii]['title']."(".$items[$ii]['file_name'].")";
				}
				if($items[$ii]['title_english'] != "" && $items[$ii]['title_english'] != null){
					$disp_name_english = $items[$ii]['title_english']."(".$items[$ii]['file_name'].")";
				}
				$params[] = "downloadRanking";
				$params[] = $ii+1;
				$params[] = $disp_name;
				$params[] = $disp_name_english;
				$params[] = $items[$ii]['CNT'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $items[$ii]['file_no'];
				$params[] = $this->accessDate;
				$params[] = $this->accessDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
			}
  		}
    }

    /**
     * ユーザランキング計算
     *
     */
    private function userRanking($viewRanking)
    {
		$items = $viewRanking->getUserRanking(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		
  		$len = count($items);

      	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'userRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
  		}
  		$rank_len = count($result);
  		
  		// all delete 'userRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'userRanking'; ".
		$params = null;
		$params[] = $this->accessDate;
		$params[] = 1;
		$this->Db->execute($query, $params);
  		
  		for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
			if($ii < $rank_len){
  			// update
				$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
						 "SET 	disp_name = ?, ".
						 "	  	disp_value = ?, ".
						 "	  	item_id = ?, ".
						 "	  	item_no = ?, ".
						 "	  	file_no = 0, ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'userRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['handle'];
				$params[] = $items[$ii]['CNT'];
				$params[] = 0;
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
			} else {
				// insert
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_ranking ".
    				 	 "(rank_type, rank, disp_name, disp_value, item_id, ".
						 "item_no, file_no, ins_date, mod_date, del_date, is_delete) ".
	                 	 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$params[] = "userRanking";
				$params[] = $ii+1;
				$params[] = $items[$ii]['handle'];
				$params[] = $items[$ii]['CNT'];
				$params[] = 0;
				$params[] = 0;
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $this->accessDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
			}
  		}
    }
    
    /**
     * 検索ワードランキング計算
     *
     */
    function keywordRanking($viewRanking)
    {
		$items = $viewRanking->getKeywordRanking(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		$len = count($items);
  		
  		$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'keywordRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
  		}
  		$rank_len = count($result);
  		
  		// all delete 'keywordRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'keywordRanking'; ".
		$params = null;
		$params[] = $this->accessDate;
		$params[] = 1;
		$this->Db->execute($query, $params);
  		
  		for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
			if($ii < $rank_len){
  			// update
				$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
						 "SET 	disp_name = ?, ".
						 "	  	disp_value = ?, ".
						 "	  	item_id = ?, ".
						 "	  	item_no = ?, ".
						 "	  	file_no = 0, ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'keywordRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['search_keyword'];
				$params[] = $items[$ii]['CNT'];
				$params[] = 0;
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
			} else {
				// insert
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_ranking ".
    				 	 "(rank_type, rank, disp_name, disp_value, item_id, ".
						 "item_no, file_no, ins_date, mod_date, del_date, is_delete) ".
	                 	 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$params[] = "keywordRanking";
				$params[] = $ii+1;
				$params[] = $items[$ii]['search_keyword'];
				$params[] = $items[$ii]['CNT'];
				$params[] = 0;
				$params[] = 0;
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $this->accessDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
			}
  		}
    }

    /**
     * 新着アイテム計算
     *
     */
    function recentRanking($viewRanking)
    {
        $this->debugLog("recentRanking:start", __FILE__, __CLASS__, __LINE__);
		$items = $viewRanking->getNewItemRanking(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
        $this->debugLog("recentRanking:got recent ranking data", __FILE__, __CLASS__, __LINE__);
        
  		$len = count($items);
   		
    	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'recentRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
            $this->errorLog($this->Db->ErrorMsg(), __FILE__, __CLASS__, __LINE__);
            throw new AppException($this->Db->ErrorMsg());
  		}
  		$rank_len = count($result);
  		
  		// all delete 'recentRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'recentRanking'; ".
		$params = null;
		$params[] = $this->accessDate;
		$params[] = 1;
		$this->Db->execute($query, $params);
        $this->debugLog("recentRanking: updated ranking data deleted", __FILE__, __CLASS__, __LINE__);
  		
  		for ( $ii=0; $ii<$len&&$ii<$this->rank_num; $ii++ ){
			if($ii < $rank_len){
  			// update
				$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
						 "SET 	disp_name = ?, ".
						 "	  	disp_name_english = ?, ".
						 "	  	disp_value = ?, ".
						 "	  	item_id = ?, ".
						 "	  	item_no = ?, ".
						 "	  	file_no = 0, ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'recentRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['title'];
				$params[] = $items[$ii]['title_english'];
				$params[] = substr($items[$ii]['shown_date'],0,10);
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $this->accessDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
                $this->debugLog("recentRanking: update no = ". $ii + 1, __FILE__, __CLASS__, __LINE__);
			} else {
				// insert
				$query = "INSERT INTO ".DATABASE_PREFIX."repository_ranking ".
    				 	 "(rank_type, rank, disp_name, disp_name_english, disp_value, item_id, ".
						 "item_no, file_no, ins_date, mod_date, del_date, is_delete) ".
	                 	 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$params[] = "recentRanking";
				$params[] = $ii+1;
				$params[] = $items[$ii]['title'];
				$params[] = $items[$ii]['title_english'];
				$params[] = substr($items[$ii]['shown_date'],0,10);
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = 0;
				$params[] = $this->accessDate;
				$params[] = $this->accessDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
                $this->debugLog("recentRanking: inserted new record no = ". $ii + 1, __FILE__, __CLASS__, __LINE__);
			}
  		}
        $this->debugLog("recentRanking: finish", __FILE__, __CLASS__, __LINE__);
    }
    
    /**
     * check be able to login or not and login user has authority
     * 
     */
    private function isLoginAdministrator()
    {
        // check login
        $result = null;
        $error_msg = null;
        
        $repositoryAction = new RepositoryAction();
        $repositoryAction->Session = $this->Session;
        $repositoryAction->Db = $this->Db;
        $repositoryAction->TransStartDate = $this->accessDate;
        $repositoryAction->setConfigAuthority();
        $repositoryAction->dbAccess = $this->Db;
        
        $return = $repositoryAction->checkLogin($this->login_id, $this->password, $result, $error_msg);
        if($return == false){
            print("Incorrect Login!\n");
            throw new AppException("Incorrect Login!");
        }
        
        // check user authority id
        // Add config management authority 2010/02/23 Y.Nakao --start--
        //if($this->user_authority_id != 5){
        if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
        // Add config management authority 2010/02/23 Y.Nakao --end--
            print("You do not have permission to update.\n");
            throw new AppException("You do not have permission to update.");
        }
    }
}
?>