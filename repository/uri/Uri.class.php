<?php
// --------------------------------------------------------------------
//
// $Id: Uri.class.php 42307 2014-09-29 06:18:07Z tomohiro_ichikawa $
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
 * ********************************************************
 * this action is called by ID Server redirect.
 * When request parameter dosen't have "attribute_id", 
 * this action redirect to item detail action.
 * When request parameter has "attribute_id", 
 * this action redirect to item download.
 * ********************************************************
 */
class Repository_Uri extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;
	
	// Request param
	var $item_id = null;
	//var $attribute_id = null;
	var $file_id = null; // this parameter is equal attribute_id 
	var $file_no = null; // file no
	
	/**
	 * 
	 */
	function execute()
	{
		// check Session and Db Object
		if($this->Session == null){
			$container =& DIContainerFactory::getContainer();
	        $this->Session =& $container->getComponent("Session");
		}
		if($this->Db== null){
			$container =& DIContainerFactory::getContainer();
			$this->Db =& $container->getComponent("DbObject");
		}		
		//アクション初期化処理
        $result = $this->initAction();
    	if ( $result === false ) {
            $exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
            $DetailMsg = null;                              //詳細メッセージ文字列作成
            //sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
            $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
            $user_error_msg = 'initで既に・・・';
            throw $exception;
        }
		
		// get block_id and page_id
		$block_info = $this->getBlockPageId();
		
		// make redirect URL
		$redirect_url = BASE_URL;
        if($_SERVER["REQUEST_METHOD"] == HTTP_REQUEST_METHOD_PUT){
            // Execute sword update
            $this->executeSwordUpdate($redirect_url, $block_info);
            $this->exitAction();
            exit();
        } else if($_SERVER["REQUEST_METHOD"] == HTTP_REQUEST_METHOD_DELETE){
            // Execute sword update
            $this->executeSwordDelete($redirect_url, $block_info);
            $this->exitAction();
            exit();
        } else if(strlen($this->file_id) == 0){
			// go to item detail
			$redirect_url .= "/?action=pages_view_main".
							 "&active_action=repository_view_main_item_detail".
							 "&item_id=". $this->item_id .
							 "&item_no=1";
			
		} else if(strlen($this->file_no) == 0){
			$query = "SELECT file_no ".
					" FROM ".DATABASE_PREFIX."repository_file ".
					" WHERE item_id = '".$this->item_id."' ".
					" AND attribute_id = '".$this->file_id."'; ";
			$result = $this->Db->execute($query);
			if($result === false || count($result) == 0) {
				// go to item detail
				$redirect_url .= "/?action=pages_view_main".
								 "&active_action=repository_view_main_item_detail".
								 "&item_id=". $this->item_id .
								 "&item_no=1";
				// remove download info
			} else if(count($result) > 1){
				$redirect_url .= "/?action=pages_view_main".
								 "&active_action=repository_action_main_export_filedownload".
								 "&item_id=". $this->item_id.
								 "&item_no=1".
								 "&attribute_id=". $this->file_id.
								 "&file_only=true";
			} else {
				$redirect_url .= "/index.php?action=pages_view_main".
							 "&active_action=repository_action_common_download".
							 "&item_id=". $this->item_id .
							 "&item_no=1".
							 "&attribute_id=". $this->file_id .
							 "&file_no=". $result[0]['file_no'];
			}
		} else {
			// go to file download
			$redirect_url .= "/index.php?action=pages_view_main".
							 "&active_action=repository_action_common_download".
							 "&item_id=". $this->item_id .
							 "&item_no=1".
							 "&attribute_id=". $this->file_id .
							 "&file_no=".$this->file_no; 
		}
       
		$redirect_url .= "&page_id=". $block_info["page_id"] .
						 "&block_id=". $block_info["block_id"];
		//アクション終了処理
		$result = $this->exitAction();     // トランザクションが成功していればCOMMITされる
		
		// redirect
		header("HTTP/1.1 301 Moved Permanently");
  		header("Location: ".$redirect_url);
		
		return;
	}
    
    /**
     * Execute sword update
     *
     */
    private function executeSwordUpdate($redirect_url, $block_info)
    {
        // Add for error check 2014/09/16 T.Ichikawa --start--
        $error_list = array();
        // Create sword update class
        require_once(WEBAPP_DIR. "/modules/repository/action/main/sword/SwordUpdate.class.php");
        $swordUpdate = new SwordUpdate($this->Session, $this->Db, $this->TransStartDate, true);
        // Add for error check 2014/09/16 T.Ichikawa --end--
        // Get authorize information.
        $authUser = $_SERVER["PHP_AUTH_USER"];
        $authPw = $_SERVER["PHP_AUTH_PW"];
        if(isset($_SERVER['HTTP_X_ON_BEHALF_OF']) && strlen($_SERVER['HTTP_X_ON_BEHALF_OF'])>0 )
        {
            $owner = $_SERVER['HTTP_X_ON_BEHALF_OF'];
        }
        else
        {
            $owner = $authUser;
        }
        
        // Get index infomation
        $insertIndex = "";
        $newIndex = "";
        if(isset($_SERVER['HTTP_INSERT_INDEX']) && strlen($_SERVER['HTTP_INSERT_INDEX'])>0 )
        {
            $insertIndex = $_SERVER['HTTP_INSERT_INDEX'];
        }
        // Add for error check 2014/09/16 T.Ichikawa --start--
        if(!isset($insertIndex)) {
            // エラーで終了してheaderに値詰めて返す
            $error_list[] = new DetailErrorInfo(0, "", "Update index is not set");
            $swordUpdate->setHeader(500, $error_list);
            return;
        }
        // Add for error check 2014/09/16 T.Ichikawa --end--
        
        if(isset($_SERVER['HTTP_NEW_INDEX']) && strlen($_SERVER['HTTP_NEW_INDEX'])>0 )
        {
            $newIndex = urldecode($_SERVER['HTTP_NEW_INDEX']);
            $newIndex = mb_convert_encoding($newIndex, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS");
        }
        
        // Fix check index_id Y.Nakao 2013/06/07
        
        // Init
        $swordUpdate->init($this->item_id, 1, $authUser, $authPw, $insertIndex, $newIndex, $owner);
        
        // Login check
        $result = $swordUpdate->checkSwordLogin($statusCode, $userId);
        if(!$result)
        {
            $swordUpdate->setHeader($statusCode);
            return;
        }
        
        // Get upload file data
        require_once(WEBAPP_DIR. "/modules/repository/components/RepositoryFileUpload.class.php");
        $fileUpload = new RepositoryFileUpload();
        $fileData = $fileUpload->getUploadData();
        if(empty($fileData))
        {
            $swordUpdate->setHeader(400);
            return;
        }
        $this->Session->setParameter("swordFileData", $fileData);
        
        // Execute update
        $swordUpdate->executeSwordUpdate($statusCode, $error_list);
        $swordUpdate->setHeader($statusCode, $error_list);
        return;
    }
    
    /**
     * Execute sword delete
     *
     */
    private function executeSwordDelete($redirect_url, $block_info)
    {
        // Get authorize information.
        $authUser = $_SERVER["PHP_AUTH_USER"];
        $authPw = $_SERVER["PHP_AUTH_PW"];
        if(isset($_SERVER['HTTP_X_ON_BEHALF_OF']) && strlen($_SERVER['HTTP_X_ON_BEHALF_OF'])>0)
        {
            $owner = $_SERVER['HTTP_X_ON_BEHALF_OF'];
        }
        else
        {
            $owner = $authUser;
        }
        
        // Create sword delete class
        require_once(WEBAPP_DIR. "/modules/repository/action/main/sword/SwordDelete.class.php");
        $swordDelete = new SwordDelete($this->Session, $this->Db, $this->TransStartDate);
        
        // Init
        $swordDelete->init($this->item_id, 1, $authUser, $authPw, $owner);
        
        // Login check
        $result = $swordDelete->checkSwordLogin($statusCode, $userId);
        if(!$result)
        {
            $swordDelete->setHeader($statusCode);
            return;
        }
        
        // Execute delete
        $swordDelete->executeSwordDelete($statusCode);
        $swordDelete->setHeader($statusCode);
        return;
    }
}
?>
