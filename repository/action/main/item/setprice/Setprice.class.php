<?php
// --------------------------------------------------------------------
//
// $Id: Setprice.class.php 41054 2014-09-05 08:34:02Z tomohiro_ichikawa $
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

/**
 * 
 *
 * @package     [[package名]]
 * @access      public
 */
class Repository_Action_Main_Item_Setprice extends RepositoryAction
{	
	// リクエストパラメタ
	// ファイル以外のパラメタは本コールバック関数で処理する。
	var $licence = null;				// ファイルのライセンス配列, アップロード済みアイテムの数に対応
	var $freeword = null;				// ファイルのライセンス自由記述欄配列, " "の場合、未入力
	var $embargo_year = null;			// エンバーゴ年
	var $embargo_month = null;			// エンバーゴ月
	var $embargo_day = null;			// エンバーゴ日
	var $embargo_flag = null;			// エンバーゴフラグ(0:公開日をアイテム公開日に合わせる, 1:公開日を独自に設定する)
	
	// Add file price Y.Nakao 2008/08/28 --start--
	var $room_ids = null;				// 選択されたグループのID配列
	var $price_value = null;			// 設定された価格配列
	var $setting_flg = null;			// 1:Add 2:Delete 
	var $target_row = null;				// 削除対象
	// Add file price Y.Nakao 2008/08/28 --end--
	
	// Extend file type A.Suzuki 2010/02/04 --start--
	var $display_type = null;
	var $display_name = null;
	var $flash_embargo_year = null;			// フラッシュファイルエンバーゴ年
	var $flash_embargo_month = null;		// フラッシュファイルエンバーゴ月
	var $flash_embargo_day = null;			// フラッシュファイルエンバーゴ日
	var $flash_embargo_flag = null;			// フラッシュファイルエンバーゴフラグ(0:公開日をアイテム公開日に合わせる, 1:公開日を独自に設定する)
	// Extend file type A.Suzuki 2010/02/04 --end--

    /**
     * action/main/item/edittextから流用
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
	    	// セッション情報取得
	    	$item_attr_type = $this->Session->getParameter("item_attr_type");		// 2.アイテム属性タイプ (Nレコード, Order順) : ""[N][''], アイテム属性タイプの必要部分を連想配列で保持したものである。
	    	// ユーザ入力値＆変数
			$item_num_attr = $this->Session->getParameter("item_num_attr");			// 5.アイテム属性数 (N): "item_num_attr"[N], N属性タイプごとの属性数-。複数可な属性タイプのみ>1の値をとる。
	    	$item_attr = $this->Session->getParameter("item_attr");					// 6.アイテム属性 (N) : "item_attr"[N][L], N属性タイプごとの属性。Lはアイテム属性数に対応。1～    	
		    $license_master = $this->Session->getParameter("license_master");		// ライセンスマスタ
		    
		    // Add registered info save action 2009/02/03 Y.Nakao --start--
			$ItemRegister = new ItemRegister($this->Session, $this->Db);
		    // Add registered info save action 2009/02/03 Y.Nakao --end--
		    
	    	// アップロード済みファイルのライセンスを取得
	    	$cntUpd = 0;			// アップロード済みアイテムカウンタ
	    	// 価格用リクエストパラメタのカウンタ
	   		$price_Cnt = 0;
	   		
	   		// エラーメッセージ配列
	   		$err_msg = array();
	   		
		   	// ii-thメタデータのリクエストを保存
		   	for($ii=0; $ii<count($item_attr_type); $ii++) {
		   		// ii-thメタデータのjj-th属性値のリクエストを保存
				for($jj=0; $jj<$item_num_attr[$ii]; $jj++) {
					// アップロード済みファイルのライセンス設定
		   			if(	($item_attr_type[$ii]['input_type']=='file' || 
		   				$item_attr_type[$ii]['input_type']=='file_price') // Add file price Y.Nakao 2008/08/28
		   				 && isset($item_attr[$ii][$jj]["upload"])){ 
		   			    // ライセンス保存 : CreativeCommonsの場合はライセンスマスタ配列のインデックス
		   			    // 自由記述は"licence_free"
		   			    $item_attr[$ii][$jj]['licence'] = $this->licence[$cntUpd];
		   			    $item_attr[$ii][$jj]['embargo_flag'] = $this->embargo_flag[$cntUpd];	
		   				$item_attr[$ii][$jj]['embargo_year'] = ($this->embargo_year[$cntUpd]==' ')?'':$this->embargo_year[$cntUpd];
		   				$item_attr[$ii][$jj]['embargo_month'] = $this->embargo_month[$cntUpd];
		   				$item_attr[$ii][$jj]['embargo_day'] = $this->embargo_day[$cntUpd];	
		   				// 月が未定義の場合、日も未定義にする ('00' = 未定義)
						if($item_attr[$ii][$jj]['embargo_month'] == '00') {		
							$item_attr[$ii][$jj]['embargo_day'] == '00';		
						}
						// Add flash file embargo 2010/02/08 A.Suzuki --start--
		   				$item_attr[$ii][$jj]['flash_embargo_flag'] = $this->flash_embargo_flag[$cntUpd];	
		   				$item_attr[$ii][$jj]['flash_embargo_year'] = ($this->flash_embargo_year[$cntUpd]==' ')?'':$this->flash_embargo_year[$cntUpd];
		   				$item_attr[$ii][$jj]['flash_embargo_month'] = $this->flash_embargo_month[$cntUpd];
		   				$item_attr[$ii][$jj]['flash_embargo_day'] = $this->flash_embargo_day[$cntUpd];	
		   				// 月が未定義の場合、日も未定義にする ('00' = 未定義)
						if($item_attr[$ii][$jj]['flash_embargo_month'] == '00') {		
							$item_attr[$ii][$jj]['flash_embargo_day'] == '00';		
						}
						// Add flash file embargo 2010/02/08 A.Suzuki --end--
		   				if($this->licence[$cntUpd] == 'licence_free') {
                            if(isset($this->freeword[$cntUpd])) {
                                $item_attr[$ii][$jj]['freeword'] = ($this->freeword[$cntUpd]==' ')?'':$this->freeword[$cntUpd];
                            } else {
                                $item_attr[$ii][$jj]['freeword'] = '';
                            }
		   				}
		   				// Extend file type A.Suzuki 2009/12/15 --start--
		   				if(isset($this->display_name[$cntUpd])) {
    		   				$item_attr[$ii][$jj]['display_name'] = ($this->display_name[$cntUpd]==' ')?'':$this->display_name[$cntUpd];
    		   			} else {
    		   			    $item_attr[$ii][$jj]['display_name'] = '';
    		   			}
		   				if($this->display_type[$cntUpd] == 'simple'){
		   					$item_attr[$ii][$jj]['display_type'] = 1;
		   				} else if($this->display_type[$cntUpd] == 'flash'){
		   					$item_attr[$ii][$jj]['display_type'] = 2;
		   				} else {
		   					$item_attr[$ii][$jj]['display_type'] = 0;
		   				}
		   				// Extend file type A.Suzuki 2009/12/15 --end--
		   				// Add file price Y.Nakao 2008/08/28 --start--
		   				if($item_attr_type[$ii]['input_type']=='file_price'){
		   					// 初期化
		   					$price_array = array();
		   					$room_id_array = array();
		   					// 個数保持
		   					$loop_num = $item_attr[$ii][$jj]['price_num'];
		   					// 削除対象の位置を取得する
		   					if($this->setting_flg == "2"){
		   						$del_row = split("_", $this->target_row);
		   					}
		   					// 設定されている価格情報を格納する
		   					for($price_num=0;$price_num<$loop_num;$price_num++){
		   						if($this->setting_flg == "2"){
		   							// 削除対象かどうか判定
		   							if($ii==$del_row[0] && $jj==$del_row[1] && $price_num==$del_row[2]){
		   								// 削除対象のため設定価格の数を-1し、ルームIDなどは保持しない
		   								if($item_attr[$ii][$jj]['price_num'] > 0){
		   									$item_attr[$ii][$jj]['price_num'] = $item_attr[$ii][$jj]['price_num'] - 1;
		   								}
		   								// カウンタを次へ
		   								$price_Cnt++;
		   								continue;
		   							}
		   						}
			   					// ルームIDと価格を保持する
			   					array_push($room_id_array, $this->room_ids[$price_Cnt]);
			   					array_push($price_array, $this->price_value[$price_Cnt]);
			   					// カウンタを次へ
			   					$price_Cnt++;
		   					}
		   					if($this->setting_flg == "1"){
		   						$add_row = split("_", $this->target_row);
		   						if($ii==$add_row[0] && $jj==$add_row[1]){
		   							// 全グループ取得
		   							$all_group = $this->Session->getParameter("all_group");
		   							// 非会員の情報を先頭に追加
		   							array_unshift($all_group,array("room_id" => 0));
		   							// 価格が設定されていないグループを検索し、あれば価格設定用の行を追加する
		   							for($group_cnt=0;$group_cnt<count($all_group);$group_cnt++){
		   								// 価格が設定済みのグループかどうかを判定する
		   								$set_flg = false;		   								
		   								for($price_num=0;$price_num<count($room_id_array);$price_num++){
		   									if($all_group[$group_cnt]["room_id"] == $room_id_array[$price_num]){
		   										// 全てのグループのうち、現在価格が設定されているグループである
		   										$set_flg = true;
		   									}
		   								}
		   								if( !$set_flg ){
		   									// 価格が未設定のグループ
		   									// 追加対象の場合課金用の行を1行追加
		   									// 追加する値は現在選択されていない先頭のグループとする。価格は0。
				   							// なければ追加しない
			   								array_push($price_array, 0);
											array_push($room_id_array, $all_group[$group_cnt]["room_id"]);
			   								// 個数+1
			   								$item_attr[$ii][$jj]['price_num'] = $item_attr[$ii][$jj]['price_num'] + 1;
			   								break;
		   								}
		   							}
		   						}
		   					}
		   					$item_attr[$ii][$jj]['price_value'] = $price_array;
		   					$item_attr[$ii][$jj]['room_id'] = $room_id_array;
		   					if($this->setting_flg == "2"){
		   						// Add registered info save action 2009/02/04 Y.Nakao --start--
				   				$result = $ItemRegister->updatePrice($item_attr[$ii][$jj], $error);
				   				if($result === false){
									array_push($err_msg, $error);
									$this->Session->setParameter("error_msg", $err_msg);
									return 'error';
								}
				   				// Add registered info save action 2009/02/04 Y.Nakao --end--
		   					}
		   				}
		   				// Add file price Y.Nakao 2008/08/28 --end--
		   				$cntUpd++;
		   			}
				}
			}
			
	    	// 次の画面へ
	    	$this->Session->setParameter("item_attr", $item_attr);	// 属性を更新
	    	$this->Session->removeParameter("error_msg");
	    	$this->Session->setParameter("error_msg", $err_msg);
	    	
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
