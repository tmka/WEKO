<?php
// --------------------------------------------------------------------
//
// $Id: Setcmdpath.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
 */
class Repository_Action_Edit_Setcmdpath extends RepositoryAction
{
	//コンポーネント取得
	var $Session = null;
	var $Db = null;
	
	// リクエストパラメタ (配列ではなく個別で渡す)
	var $admin_active_tab = null;
	
	/**
	 * [[機能説明]]
	 *
	 * @access  public
	 */
	function execute()
	{
		$istest = true;				// テスト用フラグ
		try {
			//アクション初期化処理
			$result = $this->initAction();			
			if ( $result === false ) {
				$exception = new RepositoryException( ERR_MSG_xxx-xxx1, xxx-xxx1 );	//主メッセージとログIDを指定して例外を作成
				$DetailMsg = null;							  //詳細メッセージ文字列作成
				sprintf( $DetailMsg, ERR_DETAIL_xxx-xxx1);
				$exception->setDetailMsg( $DetailMsg );			 //詳細メッセージ設定
				$this->failTrans();										//トランザクション失敗を設定(ROLLBACK)
				throw $exception;
			}
			
			// 表示中タブ情報
			$this->Session->setParameter("admin_active_tab", $this->admin_active_tab);

			// ----------------------------------------------------
			// 準備処理
			// ----------------------------------------------------
 			$edit_start_date = $this->Session->getParameter("edit_start_date");
			$Error_Msg = '';			// エラーメッセージ
			$user_id = $this->Session->getParameter("_user_id");	// ユーザID
			$params = null;				// パラメタテーブル更新用クエリ			
			$params[] = '';				// param_value
			$params[] = $user_id;				// mod_user_id
			$params[] = $this->TransStartDate;	// mod_date
			$params[] = '';				// param_name
			
		 	// ----------------------------------------------------
			// 更新時の管理パラメタの読み込み			
			// ----------------------------------------------------
			$admin_records = array();		// 管理パラメタレコード列
			$Error_Msg = '';				// エラーメッセージ
			// パラメタテーブルの全属性を取得
		   	$result = $this->getParamTableRecord($admin_records, $Error_Msg);
	   		if ($result === false) {
				$errMsg = $this->Db->ErrorMsg();
				$tmpstr = sprintf("item_coef_cp update failed : %s", $ii, $jj, $errMsg ); 
				$this->Session->setParameter("error_msg", $tmpstr);
				$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
				return 'error';
			}

			// ----------------------------------------------------
			// 更新開始時刻 > 最終更新日になっているか検査
			// ※１つでも編集中に他の管理者に変更されたパラメタがあればアウト			
			// ----------------------------------------------------
			$admin_records_old = $this->Session->getParameter("admin_params");
			foreach( $admin_records as $key => $value ){
//				if($istest){echo $key . " : " . $edit_start_date . " : " . $value['mod_date'] .  "<br>";}
				// 編集開始時の更新日時が変わっている場合、更新を許さない
				if( $admin_records_old[$key]['mod_date'] != $value['mod_date'] ) {	
					$this->Session->setParameter("error_msg", "error : probably " . $key . " was updated by other admin.");
					$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
					return 'error';
				}	
			}
			// ------------------------------------------------
			// コマンドのパスが通っていない場合、anonymousが取得できる
			// 環境変数に指定されるフォルダ以下にコマンドがあれば登録する
			// ------------------------------------------------
			// Session情報から、パスの現状を取得
			$admin_params = $this->Session->getParameter("admin_params");
			// 環境変数取得
			// OSを自動で判別する
			if(PHP_OS == "Linux" || PHP_OS == "MacOS"){
				exec("printenv PATH", $path);
			} else if(PHP_OS == "WIN32" || PHP_OS == "WINNT"){
				exec("PATH", $path);
				$path = str_replace("PATH=", "", $path);
			} else {
				$path = null;
			}
			$path = split(PATH_SEPARATOR,$path[0]);
			for($ii=0;$ii<count($path);$ii++){	
				// 取得した環境変数には最後にディレクトリセパレータがない場合は追加
				if(strlen($path[$ii]) > 0 && $path[$ii][strlen($path[$ii])-1] != DIRECTORY_SEPARATOR){
					$path[$ii] .= DIRECTORY_SEPARATOR;
				}
				// wvWareコマンドまでの絶対パス
				if( $admin_params['path_wvWare']['path']=="false"){
					// 環境変数に指定されているフォルダ内を検索
					if(file_exists($path[$ii]."wvHtml")){
						// パスが通ったので更新
						$params[0] = $path[$ii];		// param_value
		    			$params[3] = 'path_wvWare';		// param_name
		    			$result = $this->updateParamTableData($params, $Error_Msg);
		    			if ($result === false) {
							$errMsg = $this->Db->ErrorMsg();
							$tmpstr = sprintf("path_wvWare update failed : %s", $ii, $jj, $errMsg ); 
				            $this->Session->setParameter("error_msg", $tmpstr);
				            $this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
							return 'error';
				    	}
	       			}
				}
				// xlhtmlコマンドまでの絶対パス
				if( $admin_params['path_xlhtml']['path']=="false"){
					// 環境変数に指定されているフォルダ内を検索
					if(file_exists($path[$ii]."xlhtml") || file_exists($path[$ii]."xlhtml.exe")){
						// パスが通ったので更新
						$params[0] = $path[$ii];		// param_value
						$params[3] = 'path_xlhtml';		// param_name
						$result = $this->updateParamTableData($params, $Error_Msg);
						if ($result === false) {
							$errMsg = $this->Db->ErrorMsg();
							$tmpstr = sprintf("path_xlhtml update failed : %s", $ii, $jj, $errMsg ); 
							$this->Session->setParameter("error_msg", $tmpstr);
							$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
							return 'error';
						}
					}
				}
				// popplerコマンドまでの絶対パス
				if( $admin_params['path_poppler']['path']=="false"){
					// 環境変数に指定されているフォルダ内を検索
					if(file_exists($path[$ii]."pdftotext") || file_exists($path[$ii]."pdftotext.exe")){
						// パスが通ったので更新
						$params[0] = $path[$ii];			// param_value
						$params[3] = 'path_poppler';		// param_name
						$result = $this->updateParamTableData($params, $Error_Msg);
						if ($result === false) {
							$errMsg = $this->Db->ErrorMsg();
							$tmpstr = sprintf("path_poppler update failed : %s", $ii, $jj, $errMsg ); 
							$this->Session->setParameter("error_msg", $tmpstr);
							$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
							return 'error';
						}
					}
				}
				// ImageMagickコマンドまでの絶対パス
				if( $admin_params['path_ImageMagick']['path']=="false"){
					// 環境変数に指定されているフォルダ内を検索
					if(file_exists($path[$ii]."convert") || file_exists($path[$ii]."convert.exe")){
						// パスが通ったので更新
						$params[0] = $path[$ii];				// param_value
						$params[3] = 'path_ImageMagick';		// param_name
						$result = $this->updateParamTableData($params, $Error_Msg);
						if ($result === false) {
							$errMsg = $this->Db->ErrorMsg();
							$tmpstr = sprintf("path_ImageMagick update failed : %s", $ii, $jj, $errMsg ); 
							$this->Session->setParameter("error_msg", $tmpstr);
							$this->failTrans();		//トランザクション失敗を設定(ROLLBACK)
							return 'error';
						}
					}
				}
                // Get PATH for PDFTK
                if( $admin_params['path_pdftk']['path']=="false"){
                    // Search command
                    if(file_exists($path[$ii]."pdftk") || file_exists($path[$ii]."pdftk.exe")){
                        // Update
                        $params[0] = $path[$ii];    // param_value
                        $params[3] = 'path_pdftk';  // param_name
                        $result = $this->updateParamTableData($params, $Error_Msg);
                        if ($result === false) {
                            $errMsg = $this->Db->ErrorMsg();
                            $tmpstr = sprintf("path_pdftk update failed : %s", $ii, $jj, $errMsg ); 
                            $this->Session->setParameter("error_msg", $tmpstr);
                            $this->failTrans();
                            return 'error';
                        }
                    }
                }
                // Add multimedia support 2012/08/27 T.Koyasu -start-
                // get path for ffmpeg
                if( $admin_params['path_ffmpeg']['path'] == "false"){
                    // search command
                    if(file_exists($path[$ii]."ffmpeg") || file_exists($path[$ii]."ffmpeg.exe")){
                        // update
                        $params[0] = $path[$ii];
                        $params[3] = "path_ffmpeg";
                        $result = $this->updateParamTableData($params, $Error_Msg);
                        if ($result === false) {
                            $errMsg = $this->Db->ErrorMsg();
                            $tmpstr = sprintf("path_ffmpeg update failed : %s", $ii, $jj, $errMsg ); 
                            $this->Session->setParameter("error_msg", $tmpstr);
                            $this->failTrans();
                            return 'error';
                        }
                    }
                }
                // Add multimedia support 2012/08/27 T.Koyasu -end-
                // Add external search word 2014/05/23 K.Matsuo -start-
                // get path for mecab
                if( $admin_params['path_mecab']['path'] == "false"){
                    // search command
                    if(file_exists($path[$ii]."mecab") || file_exists($path[$ii]."mecab.exe")){
                        // update
                        $params[0] = $path[$ii];
                        $params[3] = "path_mecab";
                        $result = $this->updateParamTableData($params, $Error_Msg);
                        if ($result === false) {
                            $errMsg = $this->Db->ErrorMsg();
                            $tmpstr = sprintf("path_mecab update failed : %s", $ii, $jj, $errMsg ); 
                            $this->Session->setParameter("error_msg", $tmpstr);
                            $this->failTrans();
                            return 'error';
                        }
                    }
                }
                // Add external search word 2014/05/23 K.Matsuo -end-
			}
			
			//セッションの初期化
			$this->Session->removeParameter("admin_params");
			$this->Session->removeParameter("edit_start_date");  
			$this->Session->removeParameter("error_msg");
			 	
			// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			return 'success';
		}
		catch ( RepositoryException $Exception) {
			//エラーログ出力
			$this->logFile(
				"SampleAction",					//クラス名
				"execute",						//メソッド名
				$Exception->getCode(),			//ログID
				$Exception->getMessage(),		//主メッセージ
				$Exception->getDetailMsg() );	//詳細メッセージ			
			//アクション終了処理
	  		$this->exitAction();				   //トランザクションが失敗していればROLLBACKされる		
			//異常終了
			return "error";
		}
	}
}
?>
