<?php
// --------------------------------------------------------------------
//
// $Id: Uploadfiles.class.php 53594 2015-05-28 05:25:53Z kaede_matsushita $
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
class Repository_Action_Main_Item_Uploadfiles extends RepositoryAction
{	
	// 使用コンポーネントを受け取るため
	var $uploadsAction = null;

	// リクエスト
	var $mode = null;	// mode 
	var $target = null;
	var $drop_num = null;

    /**
     * [[機能説明]]
     *
     * @access  public
     */
    function executeApp()
    {	
//   	return true;
		//ガーベージフラグが"1"の場合、いつかファイル・DB共にクリアしてくれる。
		// ただし、詳細なタイミングは不明。
		
		
		if($this->mode == "drop"){
			$item_num_attr = $this->Session->getParameter("item_num_attr");			// 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
			$item_attr = $this->Session->getParameter("item_attr");					// 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～		
			
			$idx = (int)($this->target);
			$attridx = intval($item_num_attr[$idx])-1;
			$item_id = '';
			$item_no = '';
			$attribute_id = '';
			$file_no = 1;
			$show_order = 1;
			for($ii=0; $ii<count($item_attr[$idx]); $ii++){
				if(isset($item_attr[$idx][$ii]['item_id']))
				{
					$item_id = $item_attr[$idx][$ii]['item_id'];
				}
				if(isset($item_attr[$idx][$ii]['item_no']))
				{
					$item_no = $item_attr[$idx][$ii]['item_no'];
				}
				if(isset($item_attr[$idx][$ii]['attribute_id']))
				{
					$attribute_id = $item_attr[$idx][$ii]['attribute_id'];
				}
				if($file_no <= $item_attr[$idx][$ii]["file_no"]){
					$file_no = $item_attr[$idx][$ii]["file_no"] + 1;
				}
				if($show_order <= $item_attr[$idx][$ii]["show_order"]){
					$show_order = $item_attr[$idx][$ii]["show_order"] + 1;
				}
			}
			for($ii=0; $ii<$this->drop_num;$ii++){
				array_push($item_attr[$idx], array(	'item_id' => $item_id,
													'item_no' => $item_no,
													'attribute_id' => $attribute_id,
													'file_no' => $file_no,
													'show_order' => $show_order));
				$file_no = $file_no + 1;
				$show_order = $show_order + 1;
			}
			// target-thメタデータの属性数を増やす	
			$item_num_attr[$idx] = $item_num_attr[$idx] + $this->drop_num;
			$this->Session->setParameter("item_num_attr", $item_num_attr);
			$this->Session->setParameter("item_attr", $item_attr);
		}
		$garbage_flag = 1;
		//アップロードしたファイルの情報を取得する。
		//形式はuploadテーブルをSELECT *した結果と同等。
		$filelist = $this->uploadsAction->uploads($garbage_flag);
		// Bug Fix null key 2014/09/05 T.Ichikawa --start--
		// $filelistにはキー値が歯抜けで入っている為、チェック用の配列を作成する
		$checkFileList = array_merge($filelist);
		for ($ii = 0; $ii < count($checkFileList); $ii++){
			if ($checkFileList[$ii]['upload_id'] === 0) {
				return false;
			}
		}
		// Bug Fix null key 2014/09/05 T.Ichikawa --end--
		//sessionにアップロードしたファイルの情報を設定
		$this->Session->removeParameter("filelist");
		$this->Session->setParameter("filelist",$filelist);

		//'success'ではなく、trueを返す
		return true;
    }
}
?>
