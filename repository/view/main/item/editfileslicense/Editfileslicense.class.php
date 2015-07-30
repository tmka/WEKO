<?php
// --------------------------------------------------------------------
//
// $Id: Editfileslicense.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
require_once WEBAPP_DIR. '/modules/repository/components/ItemRegister.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Main_Item_Editfileslicense extends RepositoryAction
{
	//メンバ変数
	var $row_num = array();
	var $error_msg = "";		// エラーメッセージ
	
	// Add convert to flash 2010/02/10 A.Suzuki --start--
	var $flash_convertible = array();
	// Add convert to flash 2010/02/10 A.Suzuki --end--

    // Set help icon setting 2010/02/10 K.Ando --start--
    var $help_icon_display =  "";
    // Set help icon setting 2010/02/10 K.Ando --end--
	
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
	        
            // ファイル(not multimedia)のFLASH変換はIDサーバと連携している場合のみ
            $fileConvertFlag = false;
            // prefixIDをDBから取得

            $repositoryHandleManager = new RepositoryHandleManager($this->Session, $this->dbAccess, $this->TransStartDate);
            
            $prefixID = $repositoryHandleManager->getPrefix(RepositoryHandleManager::ID_Y_HANDLE);
            
            if(strlen($prefixID) > 0){
                $fileConvertFlag = true;
            }
            
            // マルチメディアファイルのFLVへの変換はffmpegが使用可能な場合のみ
            $itemRegister = new ItemRegister($this->Session, $this->Db);
            $multimediaConvertFlag = $itemRegister->getIsValidFfmpeg();
	        
	        // テーブル描画用Row数情報を作成
	        $item_attr = $this->Session->getParameter("item_attr");	
	        $item_attr_type = $this->Session->getParameter("item_attr_type");
	        $this->row_num = array_fill(0, count($item_attr), 0);
	        for ($ii = 0; $ii < count($item_attr_type); $ii++) {
	        	if($item_attr_type[$ii]['input_type'] == "file" || $item_attr_type[$ii]['input_type']=='file_price'){
	        		for ($jj = 0; $jj < count($item_attr[$ii]); $jj++) {
	        			if ($item_attr[$ii][$jj] != null) {
       						// ファイルが存在している個数分、Row数を増やしていく
                            if($item_attr_type[$ii]['input_type']=='file_price')
                            {
                                $this->row_num[$ii] += 2;
                            }
                            else
                            {
                                $this->row_num[$ii]++;
                            }
	        				
	        				// Add convert to flash 2010/02/10 A.Suzuki --start--
	        				$this->flash_convertible[$ii][$jj] = null;
	        				if (array_key_exists('upload', $item_attr[$ii][$jj]) && $item_attr[$ii][$jj]['upload'] != null)
	        				{
	        					$extension = strtolower($item_attr[$ii][$jj]['upload']['extension']);
	        					switch($extension){
                                    case "swf":
                                    case "flv":
                                        // swf, flv の場合はFlash表示を常に選択可能
                                        $this->flash_convertible[$ii][$jj] = "true";
                                        break;
	        						case "doc":
	        						case "docx":
	        						case "xls":
	        						case "xlsx":
	        						case "ppt":
	        						case "pptx":
	        						case "pdf":
                                        if($fileConvertFlag)
                                        {
                                            // ファイルのFLASH変換はIDサーバと連携している場合のみ
                                            $this->flash_convertible[$ii][$jj] = "true";
                                        }
                                        break;
	        						case "emf":
	        						case "wmf":
	        						case "bmp":
	        						case "png":
	        						case "gif":
	        						case "tiff":
	        						case "jpg":
	        						case "jp2":
                                        $this->flash_convertible[$ii][$jj] = "true";
                                        break;
                                    
                                    default :
                                        if( $multimediaConvertFlag &&
                                            $this->isMultimediaFile(
                                                strtolower($item_attr[$ii][$jj]['upload']['mimetype']),
                                                strtolower($item_attr[$ii][$jj]['upload']['extension'])))
                                        {
                                            // マルチメディアファイルのFLVへの変換はffmpegが使用可能な場合のみ
                                            $this->flash_convertible[$ii][$jj] = "true";
                                        }
	        							break;
	        					}
	        				}
	        				// Add convert to flash 2010/02/10 A.Suzuki --end--
	        			}
	        		}
	        	}
			}
	        
	        // セッションからエラーメッセージのコピー
    		$this->error_msg = $this->Session->getParameter("error_msg");
    		$this->Session->removeParameter("error_msg"); 
            
       		// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
			}
			
	        // Set help icon setting 2010/02/10 K.Ando --start--
	        $result = $this->getAdminParam('help_icon_display', $this->help_icon_display, $Error_Msg);
			if ( $result == false ){
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
	            $DetailMsg = null;                              //詳細メッセージ文字列作成
	            sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
	            $exception->setDetailMsg( $DetailMsg );         //詳細メッセージ設定
	            $this->failTrans();                             //トランザクション失敗を設定(ROLLBACK)
	            throw $exception;
			}
	        // Set help icon setting 2010/02/10 K.Ando --end--

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
}
?>
