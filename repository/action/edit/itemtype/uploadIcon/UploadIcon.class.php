<?php
// --------------------------------------------------------------------
//
// $Id: UploadIcon.class.php 3 2010-02-02 05:07:44Z atsushi_suzuki $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [[機能説明]]
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Edit_Itemtype_Uploadicon
{
	// 使用コンポーネントを受け取るため
	var $Session = null;
	var $Db = null;
	var $uploadsAction = null;
	/**
     * [[機能説明]]
     *
     * @access  public
     */
    function execute()
    {
		//ガーベージフラグが"1"の場合、いつかファイル・DB共にクリアしてくれる。
		// ただし、詳細なタイミングは不明。
		$garbage_flag = 1;
		//アップロードしたファイルの情報を取得する。
		//形式はuploadテーブルをSELECT *した結果と同等。
		$itemtype_icon = $this->uploadsAction->uploads($garbage_flag);
		for ($ii = 0; $ii < count($itemtype_icon); $ii++){
			if ($itemtype_icon[$ii]['upload_id'] === 0) {
				return false;
			}
		}
		//sessionにアップロードしたファイルの情報を設定
		$this->Session->removeParameter("itemtype_icon");
		$this->Session->setParameter("itemtype_icon",$itemtype_icon);

		//'success'ではなく、trueを返す
		return true;
    }
}
?>
