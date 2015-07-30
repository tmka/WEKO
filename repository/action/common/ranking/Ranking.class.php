<?php
// --------------------------------------------------------------------
//
// $Id: Ranking.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/view/main/ranking/Ranking.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Common_Ranking extends RepositoryAction
{
	// リクエストパラメータを受け取るため
	var $login_id = null;
	var $password = null;
	
	// ユーザの権限レベル
	var $user_authority_id = "";
	
	// ランキング集計期間
	var $rank_term = 365;
	// ランキング数（新着アイテム以外）
	var $rank_num = 5;
	// 新着アイテム扱いの期間（過去Ｘ日）
	var $newitem_term = 14;
	var $log_exception = "";

	// Add log reset ranking refer 2010/02/18 K.Ando --start--
	var $ranking_reset_last_date = "";
	var $ranking_term_date = "";
	// Add log reset ranking refer 2010/02/18 K.Ando --end--
	
	// Add config management authority 2010/02/23 Y.Nakao --start--
	var $authority_id = "";
	// Add config management authority 2010/02/23 Y.Nakao --end--
	
    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
    	try {
	        //アクション初期化処理
	        $result = $this->initAction();
    		if ( $result === false ) {
	            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
	        }
	        
	        // check login
	        $result = null;
	        $error_msg = null;
	        $return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
	        if($return == false){
	        	print("Incorrect Login!\n");
	        	return false;
	        }
	        
	        // check user authority id
	        // Add config management authority 2010/02/23 Y.Nakao --start--
			//if($this->user_authority_id != 5){
			if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
			// Add config management authority 2010/02/23 Y.Nakao --end--
	        	print("You do not have permission to update.\n");
	        	return false;
	        }
	        
	        // ランキング集計期間
			$items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_term_stats'");
			if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
				$this->rank_term = $items[0]['param_value'];
			}
			// ランキング数（新着アイテム以外）
			$items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_disp_num';");
			if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
				$this->rank_num = $items[0]['param_value'];
			}
			// 新着アイテム扱いの期間（過去Ｘ日）
			$items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_term_recent_regist';");
			if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
				$this->newitem_term = $items[0]['param_value'];
			}
			// Add log reset ranking refer 2010/02/18 K.Ando --start--
			// last reset-ranking date
			$items = $this->Db->execute("SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter WHERE param_name='ranking_last_reset_date';");
			if($items[0]['param_value'] != "" && $items[0]['param_value'] != null){
				$this->ranking_reset_last_date = $items[0]['param_value'];
			}
			if($this->ranking_reset_last_date != "" )
			{
				// Fix date calculate 2010/07/29 A.Suzuki --start--
				//$logjikan = time()- 60 * 60 * 24 * $this->rank_term;
				//$jikan = strtotime($this->ranking_reset_last_date);
				//$this->ranking_term_sec = $jikan <= $logjikan ? $logjikan : $jikan;
				$this->ranking_reset_last_date = str_replace("/","-",$this->ranking_reset_last_date);
				$query = "SELECT DATE_SUB(NOW(), INTERVAL ".$this->rank_term." DAY) AS rank_date;";
				$result = $this->Db->execute($query);
				$rank_term_date = $result[0]['rank_date'];
				$query = "SELECT DATEDIFF('".$rank_term_date."', '".$this->ranking_reset_last_date."') AS date_diff;";
				$result = $this->Db->execute($query);
				if($result[0]['date_diff'] >= 0){
					$this->ranking_term_date = $rank_term_date;
				} else {
					$this->ranking_term_date = $this->ranking_reset_last_date;
				}
				// Fix date calculate 2010/07/29 A.Suzuki --end--
			}else
			{
				// Fix date calculate 2010/07/29 A.Suzuki --start--
				//$this->ranking_term_sec = time() - 60 * 60 * 24 * $this->rank_term;
				$query = "SELECT DATE_SUB(NOW(), INTERVAL ".$this->rank_term." DAY) AS rank_date;";
				$result = $this->Db->execute($query);
				$this->ranking_term_date = $result[0]['rank_date'];
				// Fix date calculate 2010/07/29 A.Suzuki --end--
			}
			// Add log reset ranking refer 2010/02/18 K.Ando --end--
	
			// Add log exclusion from user-agaent 2011/04/28 H.Ito --start--
			// Call Common function ->logExclusion()
			$this->log_exception = $this->createLogExclusion(0,false);
			// Add log exclusion from user-agaent 2011/04/28 H.Ito --end--
	
			$viewRanking = new Repository_View_Main_Ranking();
            // Add tree access control list 2012/03/07 T.Koyasu -start-
            $viewRanking->setConfigAuthority();
            // Add tree access control list 2012/03/07 T.Koyasu -end-
			$viewRanking->SetData($this->Session, $this->Db, $this->log_exception, $this->ranking_term_date, $this->TransStartDate);
			
			// update ranking
			$this->referRanking($viewRanking);
			$this->downloadRanking($viewRanking);
			$this->userRanking($viewRanking);
			$this->keywordRanking($viewRanking);
			$this->recentRanking($viewRanking);
			
	    	// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				//print "終了処理失敗";
			}
			
			print("Successfully updated.\n");
	    	return 'success';
	    	
    	} catch ( RepositoryException $Exception) {
    	    //エラーログ出力
        	$this->logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
	        	$Exception->getCode(),			//ログID
	        	$Exception->getMessage(),		//主メッセージ
	        	$Exception->getDetailMsg() );	//詳細メッセージ	        
        	//アクション終了処理
      		$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
	        //異常終了
	        $this->Session->setParameter("error_msg", $user_error_msg);
    	    return "error";
		}
    }

    /**
     * 閲覧回数ランキング計算
     *
     */
    function referRanking($viewRanking)
    {
		$items = $viewRanking->getReferRankingData();   // ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		
  		$len = count($items);

      	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'referRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
  			return 'error';
  		}
  		$rank_len = count($result);
  		
  		// all delete 'referRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'referRanking'; ".
		$params = null;
		$params[] = $this->TransStartDate;
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
						 "	  	file_no = '', ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'referRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['title'];
				$params[] = $items[$ii]['title_english'];
				$params[] = $items[$ii]['count(*)'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $this->TransStartDate;
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
				$params[] = $items[$ii]['count(*)'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = "";
				$params[] = $this->TransStartDate;
				$params[] = $this->TransStartDate;
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
    function downloadRanking($viewRanking)
    {
		$items = $viewRanking->getDownloadRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
  		$len = count($items);
  		
  		$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'downloadRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
  			return 'error';
  		}
  		$rank_len = count($result);
  		
  		// all delete 'downloadRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'downloadRanking'; ".
		$params = null;
		$params[] = $this->TransStartDate;
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
				$params[] = $items[$ii]['count(*)'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $items[$ii]['file_no'];
				$params[] = $this->TransStartDate;
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
				$params[] = $items[$ii]['count(*)'];
				$params[] = $items[$ii]['item_id'];
				$params[] = $items[$ii]['item_no'];
				$params[] = $items[$ii]['file_no'];
				$params[] = $this->TransStartDate;
				$params[] = $this->TransStartDate;
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
    function userRanking($viewRanking)
    {
		$items = $viewRanking->getUserRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		
  		$len = count($items);

      	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'userRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
  			return 'error';
  		}
  		$rank_len = count($result);
  		
  		// all delete 'userRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'userRanking'; ".
		$params = null;
		$params[] = $this->TransStartDate;
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
						 "	  	file_no = '', ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'userRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['handle'];
				$params[] = $items[$ii]['count(*)'];
				$params[] = "";
				$params[] = "";
				$params[] = $this->TransStartDate;
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
				$params[] = $items[$ii]['count(*)'];
				$params[] = "";
				$params[] = "";
				$params[] = "";
				$params[] = $this->TransStartDate;
				$params[] = $this->TransStartDate;
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
		$items = $viewRanking->getKeywordRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
		$len = count($items);
  		
  		$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'keywordRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
  			return 'error';
  		}
  		$rank_len = count($result);
  		
  		// all delete 'keywordRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'keywordRanking'; ".
		$params = null;
		$params[] = $this->TransStartDate;
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
						 "	  	file_no = '', ".
						 "	  	mod_date = ?, ".
						 "	  	del_date = '', ".
						 "	  	is_delete = 0 ".
						 "WHERE	rank_type = 'keywordRanking' ".
						 "AND	rank = ?;";
				$params = null;
				$params[] = $items[$ii]['search_keyword'];
				$params[] = $items[$ii]['count(*)'];
				$params[] = "";
				$params[] = "";
				$params[] = $this->TransStartDate;
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
				$params[] = $items[$ii]['count(*)'];
				$params[] = "";
				$params[] = "";
				$params[] = "";
				$params[] = $this->TransStartDate;
				$params[] = $this->TransStartDate;
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
		$items = $viewRanking->getRecentRankingData(); // Add ranking acquisition portion is made into a function K.Matsuo 2011/11/18
  		$len = count($items);
   		
    	$query = "SELECT * ".
  				 "FROM ".DATABASE_PREFIX."repository_ranking ".
  				 "WHERE rank_type = 'recentRanking';";
  		$result = $this->Db->execute($query);
  		if($result === false){
  			return 'error';
  		}
  		$rank_len = count($result);
  		
  		// all delete 'recentRanking'
  		$query = "UPDATE ".DATABASE_PREFIX."repository_ranking ".
				 "SET 	del_date = ?, ".
				 "	  	is_delete = ? ".
				 "WHERE	rank_type = 'recentRanking'; ".
		$params = null;
		$params[] = $this->TransStartDate;
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
						 "	  	file_no = '', ".
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
				$params[] = $this->TransStartDate;
				$params[] = $ii+1;
				$this->Db->execute($query, $params);
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
				$params[] = "";
				$params[] = $this->TransStartDate;
				$params[] = $this->TransStartDate;
				$params[] = "";
				$params[] = 0;
				$this->Db->execute($query, $params);
			}
  		}
    }
}
?>
