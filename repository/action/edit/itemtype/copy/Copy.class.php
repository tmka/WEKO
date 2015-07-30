<?php
// --------------------------------------------------------------------
//
// $Id: Copy.class.php 21101 2013-02-04 23:45:34Z ayumi_jin $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/*vim:setexpandtabtabstop=4shiftwidth=4softtabstop=4:*/
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
/**
*Load item type metadata
*
*@package	NetCommons
*@author	S.Kawasaki(IVIS)
*@copyright2006-2008NetCommonsProject
*@license	http://www.netcommons.org/license.txtNetCommonsLicense
*@project	NetCommonsProject,supportedbyNationalInstituteofInformatics
*@access	public
*/
class Repository_Action_Edit_Itemtype_Copy extends RepositoryAction
{
	// Get component
	var $Session = null;
	var $Db = null;
	
	// Request parameter
	var $item_type_id = null;
	
	/**
	*[[機能説明]]
	*
	*@access public
	*/
	function execute()
	{
		try{
			// Init action
			$result=$this->initAction();
			if($result===false){
				$exception=newRepositoryException(ERR_MSG_xxx-xxx1,xxx-xxx1);	//主メッセージとログIDを指定して例外を作成
				$DetailMsg=null;							//詳細メッセージ文字列作成
				sprintf($DetailMsg,ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg($DetailMsg);			//詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw$exception;
			}
			
			///////////////////////////////////////////////////////////
			//
			// Get user ID
			//
			///////////////////////////////////////////////////////////
			$user_id = $this->Session->getParameter("_user_id");
			
			///////////////////////////////////////////////////////////
			// 
			// Set item type data
			//
			///////////////////////////////////////////////////////////
			
			// Select load item type
			$query = "SELECT * ".
					 "FROM ". DATABASE_PREFIX ."repository_item_type ".
					 "WHERE item_type_id = ? AND ".
				  	 "is_delete = 0;" ;
		   	$params = null;
			$params[] = $this->item_type_id;
			// Run select
			$item_type_table = $this->Db->execute($query, $params);	
			if($item_type_table === false) {
				// Get DB error
				$errMsg = $this->Db->ErrorMsg();
				// Error
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				// DB is not data
				$this->Session->setParameter("error_code", 2);
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			
			// Make copy name
			$query = "SELECT item_type_name ".
					 "FROM ". DATABASE_PREFIX ."repository_item_type ".
					 "ORDER BY item_type_name; ";
			// Run select
			$result = $this->Db->execute($query);	
			if($result === false) {
				// Get DB error
				$errMsg = $this->Db->ErrorMsg();
				// Error
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				// DB is not data
				$this->Session->setParameter("error_code", 2);
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}
			$cnt_copy = 2;
			$copy_name = $item_type_table[0]["item_type_name"] . "_" . sprintf('%02d', $cnt_copy);
			for($ii=0;$ii<count($result);$ii++){
				if($copy_name == $result[$ii]["item_type_name"]){
					$cnt_copy++;
					$copy_name = $item_type_table[0]["item_type_name"] . "_" . sprintf('%02d', $cnt_copy);
				}
			}
			// Set item type name
			$this->Session->setParameter("item_type_name", $copy_name);

			// Set item attribute data
			$query = "SELECT * ".
					 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
					 "WHERE item_type_id = ? AND ".
					 "is_delete = ? ".
					 "order by show_order; ";
			$params = null;
			$params[] = $this->item_type_id;
			$params[] = 0;
			// Run SQL
			$item_attr_type = $this->Db->execute($query, $params);
			if($item_attr_type === false) {
				// Get DB error
				$errNo = $this->Db->ErrorNo();
				$errMsg = $this->Db->ErrorMsg();
				// Error
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
				//$DetailMsg = null;
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				//$exception->setDetailMsg( $DetailMsg );
				// ROLLBACK
				$this->failTrans();
				throw $exception;
			}

			// Get candidate
			if(count($item_attr_type) > 0) {
				$array_attr_candidate = array();
				for($ii=0; $ii<count($item_attr_type); $ii++) {
					if($item_attr_type[$ii]['input_type'] == "checkbox" || 
					   $item_attr_type[$ii]['input_type'] == "radio" || 
					   $item_attr_type[$ii]['input_type'] == "select"){
						$query = "SELECT * ".
					 		 	 "FROM ". DATABASE_PREFIX ."repository_item_attr_candidate ".
					 			 "WHERE item_type_id = ? AND ".
					 			 "attribute_id = ?  AND ".
					 			 "is_delete = ? ".
								 "order by candidate_no; ";
						$params = null;
						$params[] = $this->item_type_id;
						$params[] = $item_attr_type[$ii]['attribute_id'];
						$params[] = 0;
						$res_candidata = $this->Db->execute($query, $params);
						if($res_candidata === false){
							// get DB error
							$errNo = $this->Db->ErrorNo();
							$errMsg = $this->Db->ErrorMsg();
							$exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
							// rollback
							$this->failTrans();
							$exception = null;
							throw $exception;
						}
					  	for($nCnt=0;$nCnt<count($res_candidata);$nCnt++){
		    				array_push($array_attr_candidate,$res_candidata[$nCnt]);
		    			}
						
					}
				}
			}
	 		
	 		////////////////////////////////////////////////////////////////
	 		// 
			// Add item type data to DB
			//
			////////////////////////////////////////////////////////////////
			// Get new item type ID
		    $item_type_id = $this->Db->nextSeq("repository_item_type");
			// Insert item type table
		    $query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_type ".
		    		 "(item_type_id, item_type_name, item_type_short_name, ".
		    		 "explanation, mapping_info, icon_name , icon_mime_type, icon_extension, icon, ".
		    		 "ins_user_id, mod_user_id, ".
		    		 "del_user_id, ins_date, mod_date, del_date, is_delete) ".
                     "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
			$params = null;
            $params[] = $item_type_id;		// item_type_id
            $params[] = $copy_name;			// item_type_name
            $params[] = $copy_name;			// item_type_short_name
            $params[] = $item_type_table[0]["explanation"];	// explanation
            $params[] = $item_type_table[0]["mapping_info"];// mapping_info
            $params[] = "";					// icon_name
            $params[] = "";					// icon_mine_type
            $params[] = "";					// icon_extension
            $params[] = "";					// icon(BLOB) first = ""
            $params[] = $user_id;			// ins_user_id
            $params[] = $user_id;			// mod_user_id  
            $params[] = "";					// del_user_id
            $params[] = $this->TransStartDate;	// ins_date
            $params[] = $this->TransStartDate;	// mod_date
            $params[] = "";					// del_date
            $params[] = 0;					// is_delete
            //Run INSERT
            $result = $this->Db->execute($query, $params);
			if ($result === false) {
                //必要であればSQLエラー番号・メッセージ取得
                $errNo = $this->Db->ErrorNo();
                $errMsg = $this->Db->ErrorMsg();
                $this->Session->setParameter("error_code", $errMsg);
                //エラー処理を行う
                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
                //$DetailMsg = null;
                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $str1, $str2 );
                //$exception->setDetailMsg( $DetailMsg );
                $this->failTrans();
                throw $exception;
	    	}
	    	// Insert item attr type *n
			for($ii=0; $ii<count($item_attr_type); $ii++) {
				// Get new item type attr ID
				$item_type_attr_id = 1;
		    	while(1) {
		    		$query = "SELECT * ".
                     		 "FROM ". DATABASE_PREFIX ."repository_item_attr_type ".
                     		 "WHERE item_type_id = ? AND ".
                     		 "attribute_id = ?; ";
		    		$params = null;
		            $params[] = $item_type_id;
            		$params[] = $item_type_attr_id;
            		//SELECT実行
            		$result = $this->Db->execute($query, $params);
            		if($result === false){
		                //必要であればSQLエラー番号・メッセージ取得
		                $errNo = $this->Db->ErrorNo();
		                $errMsg = $this->Db->ErrorMsg();
		                $this->Session->setParameter("error_code", $errMsg);
		                //エラー処理を行う
		                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
		                //$DetailMsg = null;
		                //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1, $str1, $str2 );
		                //$exception->setDetailMsg( $DetailMsg );
		                $this->failTrans();
		                throw $exception;
            		}
            		if(!(isset($result[0]))){
            			break;
            		}
            		
		    		$item_type_attr_id++;
		    	}
		    	// Insert item attr type
		    	$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_type ". 
		    			 "(item_type_id, attribute_id, show_order, ".
		    			 " attribute_name, attribute_short_name, input_type, is_required, ".
		    			 " plural_enable, line_feed_enable, list_view_enable, hidden, junii2_mapping, lom_mapping, ".
		    			 " dublin_core_mapping, ins_user_id, mod_user_id, del_user_id, ".
		    			 " ins_date, mod_date, del_date, is_delete) ".
                		 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
	            $params[] = $item_type_id;	// item_type_id
	            $params[] = $item_attr_type[$ii]['attribute_id'];			// attribute_id
	            $params[] = $item_attr_type[$ii]['show_order'];				// show_order
	            $params[] = $item_attr_type[$ii]['attribute_name'];			// attribute_name
	            $params[] = $item_attr_type[$ii]['attribute_short_name'];	// attribute_short_name
	            $params[] = $item_attr_type[$ii]['input_type'];				// input_type
	            $params[] = $item_attr_type[$ii]['is_required'];			// is_required
	            $params[] = $item_attr_type[$ii]['plural_enable'];			// plural_enable
	            $params[] = $item_attr_type[$ii]['line_feed_enable'];		// line_feed_enable
	            $params[] = $item_attr_type[$ii]['list_view_enable'];		// list_view_enable
	            $params[] = $item_attr_type[$ii]['hidden'];					// hidden
	            $params[] = $item_attr_type[$ii]['junii2_mapping'];			// junii2_mapping
	            $params[] = $item_attr_type[$ii]['lom_mapping'];           // lom_mapping
	            $params[] = $item_attr_type[$ii]['dublin_core_mapping'];	// dublin_core_mapping
	            $params[] = $user_id;						// ins_user_id
	            $params[] = $user_id;						// mod_user_id
	            $params[] = "";								// del_user_id
	            $params[] = $this->TransStartDate;			// ins_date
	            $params[] = $this->TransStartDate;			// mod_date
	            $params[] = "";								// del_date
	            $params[] = 0;								// is_delete
	            // Run insert
	            $result = $this->Db->execute($query, $params);
			   	if ($result === false) {
	                //Ge tError Msg
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                $this->Session->setParameter("error_code", $errMsg);
	                // error
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
	                // ROLLBACK
	                $this->failTrans();
	                throw $exception;
		    	}
			}
			// Insert item attr candidate *n
			for($ii=0;$ii<count($array_attr_candidate);$ii++){
				$query = "INSERT INTO ". DATABASE_PREFIX ."repository_item_attr_candidate ".
						 "(item_type_id, attribute_id, candidate_no, candidate_value, ".
						 " candidate_short_value, ins_user_id, mod_user_id, del_user_id, ".
						 " ins_date, mod_date, del_date, is_delete) ".
	            		 "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?); ";
				$params = null;
				$params[] = $item_type_id;		// item_type_id
				$params[] = $array_attr_candidate[$ii]["attribute_id"];			// attribute_id
	            $params[] = $array_attr_candidate[$ii]["candidate_no"];			// candidate_no
	            $params[] = $array_attr_candidate[$ii]["candidate_value"];		// candidate_value
	            $params[] = $array_attr_candidate[$ii]["candidate_short_value"];// candidate_short_value
	            $params[] = $user_id;			// ins_user_id
				$params[] = $user_id;			// mod_user_id
	            $params[] = "";					// del_user_id
	            $params[] = $this->TransStartDate;	// ins_date
	            $params[] = $this->TransStartDate;	// mod_date
	            $params[] = "";					// del_date
	            $params[] = 0;					// is_delete
	            // Run INSERT
	            $result = $this->Db->execute($query, $params);
			   	if ($result === false) {
	                //Ge tError Msg
	                $errNo = $this->Db->ErrorNo();
	                $errMsg = $this->Db->ErrorMsg();
	                $this->Session->setParameter("error_code", $errMsg);
	                // error
	                $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );
	                // ROLLBACK
	                $this->failTrans();
	                throw $exception;
		    	}
			}
	    	// エラーコード解除
			$this->Session->removeParameter("error_code");
			
			//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
			if ( $result === false ) {
				$exception = new RepositoryException( "ERR_MSG_xxx-xxx3", 1 );	//主メッセージとログIDを指定して例外を作成
				//$DetailMsg = null;                              //詳細メッセージ文字列作成
				//sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx3, $埋込み文字1, $埋込み文字2 );
				//$exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
				throw $exception;
			}
			
			// succsess
			$this->Session->removeParameter("error_code");
			
			return'success';
			
		}
		catch(RepositoryException$Exception){
			// Erro log
			/*
			$this->logFile(
				"SampleAction",					//クラス名
				"execute",						//メソッド名
				$Exception->getCode(),			//ログID
				$Exception->getMessage(),		//主メッセージ
				$Exception->getDetailMsg());	//詳細メッセージ
			*/
			
			//アクション終了処理
			$result=$this->exitAction();	//トランザクションが成功していればCOMMITされる
		
			//異常終了
			return"error";
		}
	}
}
?>
