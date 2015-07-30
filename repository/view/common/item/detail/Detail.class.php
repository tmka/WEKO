<?php
// --------------------------------------------------------------------
//
// $Id: Detail.class.php 27738 2013-10-29 10:01:04Z shota_suzuki $
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
require_once WEBAPP_DIR. '/modules/repository/components/NameAuthority.class.php';
// Add smartPhone support T.Koyasu 2012/04/10 -start-
require_once WEBAPP_DIR. '/modules/repository/view/main/item/detail/Detail.class.php';
// Add smartPhone support T.Koyasu 2012/04/10 -end-

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Common_Item_Detail extends RepositoryAction
{
	// リクエストパラメタ
	var $item_id = null;			// アイテムID
	var $item_no = null;			// アイテム通番
	var $file_flg = null;			// ワークフローならnull,査読ならtrue
	
	var $IsFile = "false";				// ファイルを保持しているか否か(2009/02/17 A.Suzuki)
	var $IsFileView = "false";			// 詳細表示で表示するファイルがあるか否か(2009/11/24 A.Suzuki)
	var $IsFileSimpleView = array();	// 簡易表示で表示するファイルがあるか否か(2009/12/14 A.Suzuki)
	var $IsFlashView = "false";		// FLASH表示で表示するファイルがあるか否か(2010/01/19 A.Suzuki)
	
	// For flash annotation
    var $encode_baseurl = "";
    var $annoteaUser = "";
    
    // Bugfix close contents data shown 2011/06/14 Y.Nakao --start--
    // components
    public $Session = null;
    public $Db = null;
    public $workflow_error = "";
    // Bugfix close contents data shown 2011/06/14 Y.Nakao --end--
    
    // Add smartPhone support T.Koyasu 2012/04/10 -start-
    public $iPhoneFlg = false;
    public $getData = null;
    public $block_id = null;
    public $commonMain = null;
    public $languagesView = null;
    public $review_status = "";
    public $reject_status = "";
    public $shown_status = "";
    // Add smartPhone support T.Koyasu 2012/04/10 -end-
    
    // Add multimedia support 2012/08/27 T.Koyasu -start-
    public $IsMultimediaView = false;
    public $contentsType = "";
    // Add multimedia support 2012/08/27 T.Koyasu -end-
    
    public $errMsg = array();
    
    // Get reference data 2013/10/21 S.Suzuki --start--
    public $fileName = array();
    public $fileURL = array();
    // Get reference data 2013/10/21 S.Suzuki --end--
    
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
	            $exception->setDetailMsg( $DetailMsg );             //詳細メッセージ設定
	            $this->failTrans();                                        //トランザクション失敗を設定(ROLLBACK)
	            $user_error_msg = 'initで既に・・・';
	            throw $exception;
	        }
	        
	        $NameAuthority = new NameAuthority($this->Session, $this->Db);
	        
	        // For flash annotation
	        $this->encode_baseurl = urlencode(BASE_URL);
            if($this->Session->getParameter("_handle") != null){
                $this->annoteaUser = $this->Session->getParameter("_handle");
            }

	    	// 初期設定
	    	$this->IsSelectPublish = "false";
			$this->IsSelectDelete = "false";
			$this->IsSelectEdit = "false";
			
	        // Bugfix close contents data shown 2011/06/14 Y.Nakao --start--
	        $smartyAssign = $this->Session->getParameter("smartyAssign");
	        if($smartyAssign == null){
	             $container =& DIContainerFactory::getContainer();
                $filterChain =& $container->getComponent("FilterChain");
                $smartyAssign =& $filterChain->getFilterByName("SmartyAssign");
	        }
	        // initialize display data
            $this->Session->removeParameter("item_info");
            $this->Session->removeParameter("position_index");
            $this->Session->removeParameter("oaipmh_uri");
            $this->Session->removeParameter("bibtex_uri");
            $this->Session->removeParameter("swrc_uri");
            
            // Add smartPhone support T.Koyasu 2012/04/09 -start-
            $mainDetail = new Repository_View_Main_Item_Detail($this->Session, $this->Db, $this->item_id, $this->item_no, $this->getData, $this->languagesView, $this->block_id, $this->commonMain, true);

            $result = $mainDetail->execute();
            
            if(is_numeric(strpos($result, 'error'))){
                if($this->smartphoneFlg)
                {
                    return 'error_sp';
                } else {
                    return 'error';
                }
            }
            
            // ワークフローによる詳細表示を示す
            $this->Session->setParameter("workflow_flg", true);
            
            // check state
            $detailInfo = $mainDetail->detail_info;
            
            if(preg_match('/not_access|login/', $detailInfo) > 0){
                $this->workflow_error = "close";
                
                if($this->smartphoneFlg){
                    return 'error_sp';
                } else {
                    return 'error';
                }
            } else if(is_numeric(strpos($detailInfo, 'del_item'))){
                $this->workflow_error = "delete";
                
                if($this->smartphoneFlg){
                    return 'error_sp';
                } else {
                    return 'error';
                }
            }
            
            // hidden supple add button
            // can not add supple mental contents in workflow
            $this->Session->setParameter("IsSuppleAdd", "false");
            
            $this->IsFile = $mainDetail->IsFile;
            $this->IsFileView = $mainDetail->IsFileView;
            $this->IsFileSimpleView = $mainDetail->IsFileSimpleView;
            $this->IsFlashView = $mainDetail->IsFlashView;
            $this->reject_status = $mainDetail->reject_status;
            $this->review_status = $mainDetail->review_status;
            $this->shown_status = $mainDetail->shown_status;
            $this->iPhoneFlg = $mainDetail->iPhoneFlg;
            // Add smartPhone support T.Koyasu 2012/04/09 -end-
            // Add multimedia support 2012/08/27 T.Koyasu -start-
            $this->IsMultimediaView = $mainDetail->IsMultimediaView;
            $this->contentsTypeList = $mainDetail->contentsTypeList;
            // Add multimedia support 2012/08/27 T.Koyasu -start-
	        
	        $this->Session->removeParameter("error_msg");
	        $this->Session->setParameter("error_msg", null);

			//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる

            // Add smartPhone support T.Koyasu 2012/04/10 -start-
            if($this->smartphoneFlg){
                return 'success_sp';
            } else {
                return 'success';
            }
            // Add smartPhone support T.Koyasu 2012/04/10 -end-
    	 } catch ( RepositoryException $Exception) {
    	    //エラーログ出力
        	/*
        	logFile(
	        	"SampleAction",					//クラス名
	        	"execute",						//メソッド名
	        	$Exception->getCode(),			//ログID
	        	$Exception->getMessage(),		//主メッセージ
	        	$Exception->getDetailMsg() );	//詳細メッセージ	        
        	*/

        	//アクション終了処理
			$result = $this->exitAction();     //トランザクションが成功していればCOMMITされる
    	    
            // Add smartPhone support T.Koyasu 2012/04/10 -start-
            if($this->smartphoneFlg){
                return "error_sp";
            } else {
                return "error";
            }
            // Add smartPhone support T.Koyasu 2012/04/10 -end-
		}
    }
}
?>
