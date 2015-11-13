<?php
// --------------------------------------------------------------------
//
// $Id: Supple.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics,
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR.'/modules/repository/components/common/WekoAction.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryHandleManager.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositorySearchTableProcessing.class.php';

/**
 * サプリアイテム既存登録＆削除用アクション
 *
 @author IVIS
 */
class Repository_Action_Main_Item_Supple extends WekoAction
{
	// 使用コンポーネントを受け取るため
	/**
	 * mailMainのコンポーネント
	 * @var Mail_Main
	 */
	public $mailMain = null;

	// リクエストパラメータ
	/**
	 * アイテムID
	 * @var number
	 */
	public $item_id = null;

	/**
	 * アイテムNo
	 * @var number
	 */
	public $item_no = null;

	/**
	 * サプリコンテンツのモード(登録or削除)
	 * @var string
	 */
	public $mode = null;

	/**
	 * サプリコンテンツURL
	 * @var string
	 */
	public $weko_key = null;

	/**
	 * サプリNo
	 * @var unknown
	 */
	public $supple_no = null;

	/**
	 * ワークフローフラグ
	 * @var unknown
	 */
	public $workflow_flag = null;

	/**
	 * ワークフローアクティブタブ
	 * @var string
	 */
	public $workflow_active_tab = null;

    /**
     * 1.既存登録ボタン押下の場合
     * 1-1.サプリコンテンツの登録を行う
     * 2.削除ボタン押下の場合
     * 2-2.サプリコンテンツの削除を行う
     * @see ActionBase::executeApp()
     */
    protected function executeApp()
    {
        // Update suppleContentsEntry Y.Yamazawa --start-- 2015/03/17 --start--
        try{
            // デコード
            $this->weko_key = rawurldecode($this->weko_key);// Add suppleContentsEntry Y.Yamazawa --start-- 2015/03/23 --start--

        	// サプリアクションからの遷移を示すフラグ
            $this->Session->setParameter("supple_flag", "true");

            $this->infoLog("businessSupple", __FILE__, __CLASS__, __LINE__);
            $businessSupple = BusinessFactory::getFactory()->getBusiness("businessSupple");
        	if($this->mode == "add_existing"){
            	// 既存サプリアイテム登録
    	        $businessSupple->entrySuppleContents($this->item_id,$this->item_no,$this->weko_key);

        	}else if($this->mode == "delete"){
        	    // サプリアイテム削除
        	    $businessSupple->deleteSuppleContents($this->item_id,$this->item_no,$this->supple_no);
        	    if($this->workflow_flag == "true"){
        	        $this->Session->setParameter("supple_workflow_active_tab", $this->workflow_active_tab);
        	    }
        	}
            
            // TODO：検索テーブル再作成のビジネスロジックを作る
            $searchTableProcessing = new RepositorySearchTableProcessing($this->Session, $this->Db);
            $searchTableProcessing->updateSearchTableForItem($this->item_id, $this->item_no);
            
    		if($this->workflow_flag == "true"){
        		return "workflow";
        	} else {
        		return "success";
        	}
        }
        catch(AppException $e){
            $msg = $e->getMessage();
            $this->Session->setParameter("supple_error",$msg);
            return "error";
    	}
        // Update suppleContentsEntry Y.Yamazawa --end-- 2015/03/17 --end--
    }
}
?>
