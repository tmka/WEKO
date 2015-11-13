<?php
// --------------------------------------------------------------------
//
// $Id: Icon.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
class Repository_Action_Edit_Itemtype_Icon extends RepositoryAction
{
	var $Session = null;
	
	var $del_icon_flg = null;
	/**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {
    	
    	// アイテムタイプの種類を示すアイコン追加 2008/07/18 Y.Nakao --start--
		
    	$itemtype_icon = $this->Session->getParameter("itemtype_icon");
    	//$this->Session->setParameter("icon_edit",0);
    	
        // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -start-
        $uploadIcon = $this->Session->getParameter('upload_icon');
        
    	if($itemtype_icon){
    		$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_UPLOAD_ICON);
    	} else if(isset($uploadIcon)){
            // upload icon is deleted?
            if($this->del_icon_flg == 1){
                // show default icon
                $this->Session->removeParameter("upload_icon");
                $this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DEFAULT_ICON);
            } else {
                // when exists upload icon, show upload icon
                $this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_UPLOAD_ICON);
            }
            return 'success';
    	} else {
    		if($this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_DATABASE_ICON){
    			// 削除が押されているか、押されていないかを確認
    			if($this->del_icon_flg==1){
    				// 削除が押されている
    				// DBの情報を削除(共通リソースに)
    				$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DEFAULT_ICON);
    			} else {
    				// 削除が押されていない
    				// DBのまま。DBに変更は行わない
	    			$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DATABASE_ICON);
    			}
    		} else if($this->Session->getParameter("icon_edit") == RepositoryConst::SESSION_PARAM_DEFAULT_ICON){
    			$this->Session->setParameter("icon_edit", RepositoryConst::SESSION_PARAM_DEFAULT_ICON);
    		}
    		return 'success';
    	}
        // Mod fix a glitch with upload icon is deleted when back from repository_item_type_confirm 2012/02/16 T.Koyasu -end-
        // 新規作成 or 編集
		for($i=0; $i<count($itemtype_icon); $i++){
			if(	$itemtype_icon[$i]['file_name']!='') {
				// 画像が入力されたか検査
				$mimetype = $itemtype_icon[$i]['mimetype'];
				if(strpos($mimetype,"image")===false) {
					// 画像でない場合, ファイルを破棄して警告メッセージ設定
					// (===falseでないとひっかからないため、注意)
					$this->Session->setParameter("Error_Msg", "Uploaded file is not image file. ".$mimetype); 
					return 'error';
				} else {
					$icon = null;
					$icon = array('upload' => $itemtype_icon[$i]);
				}
			} else {
				// ファイルアップロードに失敗
				$this->Session->setParameter("Error_Msg", "Can't Upload File...");
				return 'error';
			}
		}	
		$this->Session->removeParameter("itemtype_icon");
		$this->Session->removeParameter("Error_Msg");
		$this->Session->setParameter("upload_icon", $icon);
		
    	// アイテムタイプの種類を示すアイコン追加 2008/07/18 Y.Nakao --end--
    	return 'success';

    }
}
?>
