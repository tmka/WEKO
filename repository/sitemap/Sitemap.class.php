<?php
// --------------------------------------------------------------------
//
// $Id: Sitemap.class.php 38124 2014-07-01 06:56:02Z rei_matsuura $
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
class Repository_Sitemap extends RepositoryAction
{
	// コンポーネント
	var $Db;
	var $Session;

	// リクエストパラメータ
	var $login_id = null;
	var $password = null;

	var $user_authority_id = "";	// ユーザの権限レベル
	// currentdir is nc2/htdocs
	var $sitemap_dir = "./weko/sitemaps/";
	
	// Add config management authority 2010/02/23 Y.Nakao --start--
	var $authority_id = "";
	// Add config management authority 2010/02/23 Y.Nakao --end--

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
			
			// Add config management authority 2010/02/23 Y.Nakao --start--
			$this->setConfigAuthority();
			// Add config management authority 2010/02/23 Y.Nakao --end--
			 
			// check login
			$result = null;
			$error_msg = null;
			$return = $this->checkLogin($this->login_id, $this->password, $result, $error_msg);
			if($return == false){
				print("Incorrect Login!\n");
				return false;
			}
			 
			// check user authority id
			// ログインチェック時に取得される
			// Add config management authority 2010/02/23 Y.Nakao --start--
			//if($this->user_authority_id != 5){
			if($this->user_authority_id < $this->repository_admin_base || $this->authority_id < $this->repository_admin_room){
			// Add config management authority 2010/02/23 Y.Nakao --end--
				print("You are not authorized update.\n");
				return false;
			}

			// サイトマップディレクトリの読み込み権限追加（権限がないと内容取得不可）
			chmod($this->sitemap_dir, 0700);
			 
			// 既存のサイトマップ削除
			if ($handle = opendir("$this->sitemap_dir")) {
				while (false !== ($item = readdir($handle))) {
					if ($item != "." && $item != "..") {
						if (is_dir("$this->sitemap_dir/$item")) {
							$this->removeDirectory("$this->sitemap_dir/$item");
						} else {
							unlink("$this->sitemap_dir/$item");
						}
					}
				}
				closedir($handle);
			}
				
			// サイトマップディレクトリの権限を戻す
			chmod($this->sitemap_dir, 0300);
			 
			//////////////////////////////////////////////
			// アイテム10000件ずつサイトマップ作成
			// item_id順でURLを出力
			// 削除されたアイテムは含めない
			//////////////////////////////////////////////
			$item_num = 0;
			$count = 1;
			// Add 2011/04/04 H.Ito --start--
			$time = array();
			// Add 2011/04/04 H.Ito --end--
			while($count <= 10000){
				// Mod $query for binary-file uri put sitemaps 2009/12/14 K.Ando --start--
				//$query = "SELECT uri, mod_date FROM ".DATABASE_PREFIX. "repository_item".
				$query = "SELECT uri, mod_date, item_id, item_no FROM ".DATABASE_PREFIX. "repository_item".
	        			 " WHERE uri != '' ".
	        			 " AND is_delete = 0".
	        			 " ORDER BY item_id ".
	        			 " LIMIT ". $item_num. ", 10000;";
				// Mod $query for binary-file uri put sitemaps 2009/12/14 K.Ando --end--

				$result = $this->Db->execute($query);
				if($result === false) {
					$errMsg = $this->Db->ErrorMsg();
					return false;
				}
				if(count($result) != 0){
					$this->createSitemap($count, $result);
					// Add 2011/04/04 H.Ito --start--
					// 更新日時取得用
					$time[] = $this->checkTime($result);
					// Add 2011/04/04 H.Ito --end--
				}

				// アイテムなし or 10000件に満たない or 1000番目のサイトマップの場合終了
				if(count($result) < 10000 || count($result) == 0 || $count == 1000){
					break;
				} else {
					$item_num += 10000;
					$count++;
				}
			}
			 // Mod 2011/04/04 H.Ito --start--
			// sitemap_indexファイル作成
            $this->createSitemapIndex($time);
            // Mod 2011/04/04 H.Ito --end--
				
			// アクション終了処理
			$result = $this->exitAction();	// トランザクションが成功していればCOMMITされる
			if ( $result == false ){
				//print "終了処理失敗";
			}

			print("Successfully updated.\n");
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

	/**
	 * サイトマップファイルの作成
	 * @param $num	サイトマップファイルの番号
	 * @param $item_data	アイテム情報の配列
	 */
	function createSitemap($num, $item_data){
		// 改行
		$LF = $this->forXmlChange("\n");
		 
		// $numを0詰めの4桁にフォーマットする
		$fnum = sprintf("%04d", $num);
		 
		// sitemapファイル作成し, gzip形式に圧縮 (ファイル名：sitemap_xxxx.xml.gz)

		$xml = '';
		$xml .= '<?xml version="1.0" encoding="utf-8" ?>'.$LF;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$LF;
		 
		// アイテムURL記述
		for($ii=0;$ii<count($item_data);$ii++){
			$xml .= '	<url>'.$LF;
			
			// Bug fix item URL 2010/04/16 A.Suzuki --start--
			// item URL is local URL. (No use IDServer URL.)
			// $xml .= '		<loc>'.$this->forXmlChange($item_data[$ii]["uri"]).'</loc>'.$LF;
			$detail_uri = BASE_URL."/?action=repository_uri&item_id=".$item_data[$ii]["item_id"];
			$xml .= '		<loc>'.$this->forXmlChange($detail_uri).'</loc>'.$LF;
			// Bug fix item URL 2010/04/16 A.Suzuki --end--
			
			$xml .= '		<lastmod>'.$this->forXmlChange($this->changeDatetimeToW3C($item_data[$ii]["mod_date"])).'</lastmod>'.$LF;
			$xml .= '	</url>'.$LF;
			// Add binary-file sitemaps 2009/12/14 K.Ando --start--
			// No output binary-file 2010/04/16 A.Suzuki --start--
			// $xml .= $this->createFileSitemap($item_data[$ii]['item_id'], 1);
			// No output binary-file 2010/04/16 A.Suzuki --end--
			// Add binary-file sitemaps 2009/12/14 K.Ando --end--
		}
		 
		$xml .= '</urlset>';

		$file_name = $this->sitemap_dir."sitemap_".$fnum.".xml.gz";
		$gz = gzopen($file_name, "w9");
		if($gz === false){
			return false;
		}
		gzwrite($gz, $xml);
		gzclose($gz);
	}
	
    // Add 2011/04/04 H.Ito --start--
	/**
	 * 日付検索
	 * @param $item_data	アイテム情報の配列
	 */
	function checkTime($item_data){
	    
		$checktime = $this->convTimetoInteger($item_data[0]["mod_date"]);
		$outTime = $item_data[0]["mod_date"];
		for($ii=0;$ii<count($item_data);$ii++){
		    $newtime = $this->convTimetoInteger($item_data[$ii]["mod_date"]);
			if ($checktime < $newtime) {
				$checktime = $newtime;
				$outTime = $item_data[$ii]["mod_date"];
			}
		}
		return $outTime;
	}
	
	/**
     * タイムスタンプ数値型変換
     * @param $time_data  時刻文字列
     */
	function convTimetoInteger($time_data){
	    
	    $tmp = explode(" ",$time_data);
	    $tmp_day = explode("-", $tmp[0]);
	    
	    // year
	    $outTime = $tmp_day[0];
	    // month
	    if(isset($tmp_day[1])){
	       $outTime .= sprintf("%02d", intVal($tmp_day[1]));
	    } else {
	       $outTime .= "00";
	    }
	    // day
	    if(isset($tmp_day[2])){
	       $outTime .= sprintf("%02d", intVal($tmp_day[2]));
	    } else {
	       $outTime .= "00";
	    }
	    
	    if(isset($tmp[1])){
            $tmp_time = preg_replace("/\..*$/", "", $tmp[1]); 
            $time_times = explode(":", $tmp_time); 
            // hour
            if(isset($time_times[0])){
                $outTime .= sprintf("%02d", intVal($time_times[0]));
            } else {
                $outTime .= "00";
            }
            // minits
            if(isset($time_times[1])){
                $outTime .= sprintf("%02d", intVal($time_times[1]));
            } else {
                $outTime .= "00";
            }
            // second
            if(isset($time_times[2])){
                $outTime .= sprintf("%02d", intVal($time_times[2]));
            } else {
                $outTime .= "00";
            }
        } else{
            $outTime .= "000000";
        }
	    
        return $outTime;
	}
	
	// Add 2011/04/04 H.Ito --end--

	// Mod 2011/04/04 H.Ito --start--
	/**
	 * サイトマップインデックスの作成
	 *
	 */
	function createSitemapIndex($time){
	    // Mod 2011/04/04 H.Ito --end--

		// 改行
		$LF = $this->forXmlChange("\n");
		 
		// サイトマップディレクトリの読み込み権限追加（権限がないと内容取得不可）
		chmod($this->sitemap_dir, 0700);
		 
		// サイトマップディレクトリの内容取得
		$files = scandir($this->sitemap_dir);
		 
		$xml = '';
		$xml .= '<?xml version="1.0" encoding="UTF-8"?>'.$LF;
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$LF;
		 
		// Add 2011/04/04 H.Ito --start--
		// 更新日時取得用カウンタ
		$timeCnt = 0;
		// Add 2011/04/04 H.Ito --end--
		for($ii=0;$ii<count($files);$ii++){
			// サイトマップファイルを検索
			if(strpos($files[$ii], ".gz") != false){
				// ファイルパス
				$file_path = $this->sitemap_dir. $files[$ii];
				// 更新日取得
				$mtime = filemtime($file_path);
				// Mod 2011/04/04 H.Ito --start--
				$mod_date = $this->changeDatetimeToW3C($time[$timeCnt]);
                $timeCnt++;
                // Mod 2011/04/04 H.Ito --end--
				// XMLに記述
				$xml .= '	<sitemap>'.$LF;
				$xml .= '		<loc>'.$this->forXmlChange(BASE_URL.substr($file_path, 1)).'</loc>'.$LF;
				$xml .= '		<lastmod>'.$this->forXmlChange($mod_date).'</lastmod>'.$LF;
				$xml .= '	</sitemap>'.$LF;
			}
		}
		 
		$xml .= '</sitemapindex>';
		 
		$file_name = $this->sitemap_dir."sitemapindex.xml";
		$handle = fopen($file_name, "w");
		fwrite($handle, $xml);
		fclose($handle);
		 
		// サイトマップディレクトリの権限を戻す
		chmod($this->sitemap_dir, 0300);
	}

	/*
	 * Add binary-file sitemaps 2009/12/14 K.Ando
	 * create sitemap for files
	 */
	function createFileSitemap($item_id, $item_no)
	{
		// 改行
		$LF = $this->forXmlChange("\n");
		
		// DBよりitem_id にひもつくファイルを取得
		// ファイル名を全て取得
		$query = "SELECT `attribute_id`, `file_name`, `file_no`, `mod_date`".
				 "FROM `". DATABASE_PREFIX ."repository_file` ".
				 "WHERE `item_id` = ? AND ".
				 "	  `item_no` = ? AND ".
				 "	  `is_delete` = 0 ;";
		$xml = NULL;
		$params = array();
		$params[] = $item_id;
		$params[] = $item_no;
		$file_info = $this->Db->execute($query, $params);
		if($file_info === false){
			// SQLエラー
			$this->failTrans();
			return false;
		} else if(count($file_info) != 0){
			for($ii=0;$ii<count($file_info);$ii++){
				// fileのURI作成
				$detail_uri = BASE_URL . "/?action=repository_uri&item_id=".$item_id;
				$detail_uri .= "&file_id=" .$file_info[$ii]['attribute_id'];
				$detail_uri .= "&file_no=" .$file_info[$ii]['file_no'];
				// XML 作成
				$xml .= '	<url>'.$LF;
				$xml .= '		<loc>'.$this->forXmlChange($detail_uri).'</loc>'.$LF;
				$xml .= '		<lastmod>'.$this->forXmlChange($this->changeDatetimeToW3C($file_info[$ii]["mod_date"])).'</lastmod>'.$LF;
				$xml .= '	</url>'.$LF;
			}
		}

		// 作成したURIを返す
		return $xml;
	}
}
?>
