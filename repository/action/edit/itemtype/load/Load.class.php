<?php
// --------------------------------------------------------------------
//
// $Id: Load.class.php 3 2010-02-02 05:07:44Z atsushi_suzuki $
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
class Repository_Action_Edit_Itemtype_Load extends RepositoryAction
{
	// Get component
	var $Session = null;
	var $Db = null;
	
	// Request parameter
	var $item_type_name = null;
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
			
			// item type edit
			if($this->Session->getParameter("item_type_edit_flag") == 1) {
				$del_attribute_id = $this->Session->getParameter("del_attribute_id");
				if($del_attribute_id == null){
					$del_attribute_id = array();
				}
				// setting item type
				$array_attr_id = $this->Session->getParameter("attribute_id");
				for($ii=0;$ii<count($array_attr_id);$ii++){
					if($array_attr_id[$ii] != -1){
						array_push($del_attribute_id, $array_attr_id[$ii]);
					}
				}
				// all item type id is del 
	 			$this->Session->setParameter("del_attribute_id", $del_attribute_id);
	 		}
			
			////////// Set load data to Session //////////
			// Remove Session data
			$this->Session->removeParameter("metadata_num");
			$this->Session->removeParameter("metadata_title");
	   		$this->Session->removeParameter("metadata_type");
			$this->Session->removeParameter("metadata_required");
	   		$this->Session->removeParameter("metadata_disp");
	   		$this->Session->removeParameter("metadata_candidate");
	   		$this->Session->removeParameter("metadata_plural");
	   		$this->Session->removeParameter("metadata_newline");
	   		$this->Session->removeParameter("attribute_id");
	   		$this->Session->removeParameter("metadata_hidden");
		   	
			// Select load item type
			$query = "SELECT * ".
					 "FROM ". DATABASE_PREFIX ."repository_item_type ".
					 "WHERE item_type_id = ? AND ".
				  	 "is_delete = 0;" ;
		   	$params = null;
			$params[] = $this->item_type_id;
			// Run select
			$result = $this->Db->execute($query, $params);	
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
			
			// Set item type name
			$this->Session->setParameter("itemtype_name", $this->item_type_name);

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
			$result = $this->Db->execute($query, $params);
			if($result === false) {
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

			if(count($result) > 0) {
				$array_attr_name = array();
				$array_input_type = array();
				$array_attr_candidate = array();
				$array_required = array();
				$array_list_enable = array();
				$array_attr_id = array();
				$array_plural = array();
				$array_newline = array();
				$array_hidden = array();	// 2009/01/29 A.Suzuki
				
				for($ii=0; $ii<count($result); $ii++) {
					array_push($array_attr_name, $result[$ii]['attribute_name']);
					array_push($array_input_type, $result[$ii]['input_type']);
					if($result[$ii]['is_required'] == 0)
					{
						array_push($array_required, 0);
					} else {
						array_push($array_required, 1);
					}
					if($result[$ii]['list_view_enable'] == 0)
					{
						array_push($array_list_enable, 0);
					} else {
						array_push($array_list_enable, 1);
					}
					if($result[$ii]['plural_enable'] == 0){
						array_push($array_plural, 0);
					} else {
						array_push($array_plural, 1);
					}
					if($result[$ii]['line_feed_enable'] == 0)
					{
						array_push($array_newline, 0);
					} else {
						array_push($array_newline, 1);
					}
					// Add hidden 2009/01/29 A.Suzuki --start--
					if($result[$ii]['hidden'] == 0)
					{
						array_push($array_hidden, 0);
					} else {
						array_push($array_hidden, 1);
					}
					// Add hidden 2009/01/29 A.Suzuki --end--
					if($result[$ii]['input_type'] == "checkbox" || 
					   $result[$ii]['input_type'] == "radio" || $result[$ii]['input_type'] == "select"){
						$query = "SELECT * ".
					 		 	"FROM ". DATABASE_PREFIX ."repository_item_attr_candidate ".
					 			"WHERE item_type_id = ? AND ".
					 			"attribute_id = ?  AND ".
					 			"is_delete = ? ".
								"order by candidate_no; ";
						$params = null;
						$params[] = $this->item_type_id;
						$params[] = $result[$ii]['attribute_id'];
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
						$str_candidata = "";
						for($nCnt=0;$nCnt<count($res_candidata);$nCnt++){
							if($nCnt != 0){
								$str_candidata .= "|";
							}
							$str_candidata .= $res_candidata[$nCnt]['candidate_value'];
						}
						array_push($array_attr_candidate,$str_candidata);
					}
					else 
					{
						array_push($array_attr_candidate, "");
					}
					// item type edit
			 		if($this->Session->getParameter("item_type_edit_flag") == 1) {
 						array_push($array_attr_id,-1);
 						// 一行増えた場合、その分sessionにも反映
 						$this->Session->setParameter("attribute_id", $array_attr_id);
 					}
				}
				
				$this->Session->setParameter("metadata_title",$array_attr_name);
		   		$this->Session->setParameter("metadata_type", $array_input_type);
				$this->Session->setParameter("metadata_required", $array_required);
		   		$this->Session->setParameter("metadata_disp", $array_list_enable);
		   		$this->Session->setParameter("metadata_candidate", $array_attr_candidate);
		   		$this->Session->setParameter("metadata_plural", $array_plural);
		   		$this->Session->setParameter("metadata_newline", $array_newline);
		   		$this->Session->setParameter("metadata_hidden", $array_hidden);
			}
	 		
			// Set metqadata num
	 		$this->Session->setParameter("metadata_num", count($result));
			
			// succsess
			$this->Session->setParameter("error_code",0);
		
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
