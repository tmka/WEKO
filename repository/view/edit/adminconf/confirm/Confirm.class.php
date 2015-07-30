<?php
// --------------------------------------------------------------------
//
// $Id: Confirm.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------


require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHarvesting.class.php';

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_View_Edit_AdminConf_Confirm extends RepositoryAction
{
	// component
	var $Session = null;
	var $Db = null;

	// request parmater
	var $login_id = null;
	var $error_msg = null;
	var $adminconfirm_action = null;	// action Name(sitemap, ranking, filecleanup, harvesting, usagestatistics, feedback, sitelicensemail)
	
	public $is_create_data = null;
    
    public $harvesting_all_item_acquisition = null;
    
    /**
     * Harvesting warning repository
     *
     * @var array
     */
    public $harvestWarningRepos = array();
	
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
			
			$this->Session->setParameter("redirect_flg", "admin");
			
            // Fix active tab check Y.Nakao 2012/03/22 --start--
            if( $this->adminconfirm_action == 'sitemap' || 
                $this->adminconfirm_action == 'ranking' || 
                $this->adminconfirm_action == 'filecleanup' || 
                $this->adminconfirm_action == 'usagestatistics' || 
                $this->adminconfirm_action == 'sitelicensemail' || 
                $this->adminconfirm_action == 'feedback' || 
                $this->adminconfirm_action == 'reconstructindexauth' || 
                $this->adminconfirm_action == 'reconstructsearch' ||
                $this->adminconfirm_action == 'externalsearchstopword' )
            {
                $this->Session->setParameter("admin_active_tab", 1);
            }
            else if($this->adminconfirm_action == 'harvesting')
            {
                $this->Session->setParameter("admin_active_tab", 2);
                
                // set flag executing harvest all item or finite difference
                if($this->harvesting_all_item_acquisition == null)
                {
                    $this->harvesting_all_item_acquisition = $this->Session->getParameter("harvesting_all_item_acquisition_flag");
                }
                if($this->harvesting_all_item_acquisition == null)
                {
                    $this->Session->setParameter("harvesting_all_item_acquisition_flag", false);
                }
                else if($this->harvesting_all_item_acquisition == true)
                {
                    $this->Session->setParameter("harvesting_all_item_acquisition_flag", true);
                }
                
                $this->harvestWarningRepos = array();
                $Harvesting = new RepositoryHarvesting($this->Session, $this->Db);
                $harvestingRepositories = array();
                $result = $Harvesting->getHarvestingTable($harvestingRepositories);
                foreach($harvestingRepositories as $repos)
                {
                    if(strlen($repos["repository_name"])==0 && strlen($repos["base_url"])==0)
                    {
                        continue;
                    }
                    
                    if(strlen($repos["base_url"])==0 || strlen($repos["post_index_id"])==0 || $repos["post_index_id"]==0)
                    {
                        $warningRepos = "";
                        if(strlen($repos["repository_name"])>0)
                        {
                            $warningRepos .= $repos["repository_name"];
                            if(strlen($repos["base_url"])>0)
                            {
                                $warningRepos .= "(".$repos["base_url"].")";
                            }
                        }
                        else if(strlen($repos["base_url"])>0)
                        {
                            $warningRepos .= $repos["base_url"];
                        }
                        if(strlen($warningRepos) > 0)
                        {
                            array_push($this->harvestWarningRepos, $warningRepos);
                        }
                    }
                }
            }
            // Fix active tab check Y.Nakao 2012/03/22 --end--
			
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
			return 'success';
			
		} catch ( RepositoryException $Exception) {
			//エラーログ出力
			$this->logFile(
	        	"Repository_View_Edit_AdminConf_Confirm",		//クラス名
	        	"execute",									//メソッド名
			$Exception->getCode(),							//ログID
			$Exception->getMessage(),						//主メッセージ
			$Exception->getDetailMsg() );					//詳細メッセージ	      
			//アクション終了処理
			$this->exitAction();                   //トランザクションが失敗していればROLLBACKされる        
			//異常終了
			$this->Session->setParameter("error_msg", $user_error_msg);
			return "error";
		}
			return 'success';
	}
}
?>