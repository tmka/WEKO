<?php
// --------------------------------------------------------------------
//
// $Id: Mapping.class.php 20917 2013-01-16 07:38:08Z atsushi_suzuki $
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
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Mapping extends RepositoryAction
{
	// リクエストパラメータを受け取るため
	var $item_type_id = null;		//前画面で選択したアイテムタイプID(編集時)
	
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
	            $exception = new RepositoryException( "ERR_MSG_xxx-xxx1", 001 );	//主メッセージとログIDを指定して例外を作成
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
		    }
	    	//
	    	// 初期設定    	
	    	//
			$this->Session->setParameter("item_type_edit_flag", -1);
		   	// アイテムタイプIDが送信された場合 => そのアイテムタイプ情報をDBから選択し、セッションに設定
		   	// 送信されない場合 => セッションにアイテムタイプIDが保存されていれば、OK。無ければ矛盾するため、アウト。
		   	if( $this->item_type_id != null ) {
		 		// アイテムタイプを検索してセッション情報に保存
		 		// BLOBはセッションに保存できないので取得しないように修正 2008/07/29 Y.Nakao --start--
		 		$query = "SELECT item_type_id, item_type_name, item_type_short_name, explanation, mapping_info, ".
		 				 " ins_user_id, mod_user_id, del_user_id, ins_date, mod_date, del_date, is_delete ".
		 				 "FROM ". DATABASE_PREFIX ."repository_item_type ".
		 				 "WHERE item_type_id = ? AND is_delete = 0; ";
		 		$params = array();
		 		$params[] = $this->item_type_id;
		 		$result = $this->Db->execute($query, $params);
		 		// BLOBはセッションに保存できないので取得しないように修正 2008/07/29 Y.Nakao --end--
				if($result === false) {
					return 'error';
			   	}
			   	$this->Session->setParameter("itemtype_id", $this->item_type_id );
			   	$this->Session->setParameter("itemtype", $result[0]);
			   	// 更新日時を取得
			   	$this->Session->setParameter("item_type_update",$result[0]['mod_date']);
			   	$params = array( "item_type_id" => $this->item_type_id,
		    					 "is_delete" => 0 );			   	
		 		$result = $this->Db->selectExecute("repository_item_attr_type", $params, array("show_order" => "ASC"));
		        if($result === false) {
		    		return 'error';
		    	}
		    	//echo count($result);
		    	// 書誌情報追加 2008/08/22 --start--
		    	if(count($result) > 0) {
		    		for($ii=0;$ii<count($result);$ii++){
		    			if($result[$ii]['input_type']=="biblio_info"){
		    				// 属性値取得
		    				$bib_jn2 = $result[$ii]['junii2_mapping'];
		    				$bib_lang = $result[$ii]['display_lang_type'];
		    				// ","で分解
		    				$bib_jn2 = split(",", $bib_jn2);
		    				$bib_lang = split(",", $bib_lang);
			    			$result[$ii]['junii2_mapping'] = $bib_jn2;
			    			$result[$ii]['display_lang_type'] = $bib_lang;
		    			}
		    		}
		    		//$this->Session->setParameter("metadata_table",$result);
		    	}
		    	// 書誌情報追加 2008/08/22 --end--
		    	
		    	// 2012/2/15 jin add
		    	$this->Session->setParameter("metadata_table",$result);
		   	}
    		// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				//print "終了処理失敗";
			}
	        return 'success';
	    }
	    catch ( RepositoryException $Exception) {
	        //アクション終了処理
	      	$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる
	        
	        //異常終了
	        return "error";
	    }
    }
}
?>
