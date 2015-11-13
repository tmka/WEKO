<?php
// --------------------------------------------------------------------
//
// $Id: IDServer.class.php 57182 2015-08-26 12:57:40Z tatsuya_koyasu $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once WEBAPP_DIR. '/modules/repository/components/Snoopy.class.php';
require_once WEBAPP_DIR. '/modules/repository/components/JSON.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryAction.class.php';
include_once MAPLE_DIR.'/includes/pear/File/Archive.php';
require_once WEBAPP_DIR. '/modules/repository/components/RepositoryDbAccess.class.php';

class IDServer extends RepositoryAction
{
	// member
	var $Session = null;
	var $Db = null;
	// currentdir is nc2/htdocs
	var $id_dir = "";	//'../webapp/modules/repository/files/id/';	// Modify Directory specification K.Matsuo 2011/9/1
	private $maxFlashConvertSize_ = 104857600;  // Default: 104857600 = 1024 * 1024 * 100 = 100(MB)

	/**
	 * INIT
	 */
	function IDServer($Session, $Db){
		if($Session){
			$this->Session = $Session;
		} else {
			return null;
		}
		if($Db != null){
			$this->Db = $Db;
		} else {
			return null;
		}
		$this->id_dir = WEBAPP_DIR.'/modules/repository/files/id/';  // Modify Directory specification BASE_DIR K.Matsuo 2011/9/1
	}

	/**
	 * entry prefix
	 * this function call from repository install action 
	 */
	function entryPrefix(){
		// clearing seacret key 
		// entry prefix
		
		/////////////////////
		// insertPrefixID
		/////////////////////
		return "";
	}
	
	/**
	 * get prefix ID
	 * 
	 * $id_flg = true -> get fromIDServer
	 * $id_flg = false -> get from parameter table
	 */
	function getPrefixID($id_flg=false){
		if($id_flg){
			/////////////////////////////////
			// get PrefixID from IDServer
			/////////////////////////////////
			// get URL
            if(_DEBUG_FLG){
                // when this site url is localhost, use test url
                $repos = "weko.ivis.co.jp";
            } else {
                // Addition of HTTPS check 2010/02/03 S.Nonomura --start--
                // bug fix 2010/02/19 Y.Nakao --start--
                $repos = str_replace("https://", "", BASE_URL);
                $repos = str_replace("http://", "", $repos);
                // bug fix 2010/02/19 Y.Nakao --end--
                // Addition of HTTPS check 2010/02/03 S.Nonomura --end--
				$repos = join(".", array_reverse(explode("/", $repos)));
            }
			$message = "a random message";
			// get private key file
			$prv_key_pass = "";
			//$tmp = getcwd();
			if ($handle = opendir($this->id_dir)) {
				while (false !== ($filename = readdir($handle))) {
					if(!is_dir($filename)){
						$elm = explode(".", $filename);
						if($elm[count($elm)-1] == "pem"){
							$prv_key_pass = $this->id_dir.$filename;
						}
					}
				}
                closedir($handle);
			}
			if($prv_key_pass == ""){
				return "";
			}
			$private_key = file_get_contents($prv_key_pass);
			openssl_sign($message, $signature, $private_key, OPENSSL_ALGO_SHA1);
			
			$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
					 "WHERE param_name = 'prefix'; ";
			$result = $this->Db->execute($query);
			if($result === false){
				return "";
			}
			if(count($result) != 1){
				return "";
			}
			$url = $result[0]["param_value"];
			$formvars = array();
			$formvars['ir_host'] = $repos;
			$formvars['message'] = $message;
			$formvars['signature'] = base64_encode($signature);
			
			try
			{
				$snoopy = new Snoopy;
				$snoopy->set_submit_normal();
				$snoopy->agent = "TX3sy68wT7bM";
                // Modfy proxy 2011/12/06 Y.Nakao --start--
                $proxy = $this->getProxySetting();
                if($proxy['proxy_mode'] == 1)
                {
                    $snoopy->_isproxy = true;
                    $snoopy->proxy_host = $proxy['proxy_host'];
                    $snoopy->proxy_port = $proxy['proxy_port'];
                    $snoopy->proxy_user = $proxy['proxy_user'];
                    $snoopy->proxy_pass = $proxy['proxy_pass'];
                }
                // Modfy proxy 2011/12/06 Y.Nakao --end--
				$res = $snoopy->submit($url, $formvars);
				$res_json = $snoopy->results;			
			} catch (Exception $ex) {
				return "";
			}
			
			// get prefix from return JSON
			$json = new Services_JSON();
			$decoded = $json->decode($res_json);
			if($decoded->status == "OK"){
				// prefixが発行された
				$prefix = $decoded->repo_no;
				return $prefix;
			} else {
				return "";
			}
	
		} else {
			/////////////////////////////////
			// get PrefixID from DB
			/////////////////////////////////

            // Mod Item handle management T.Koyasu 2014/01/28 --start--
            $dbAccess = new RepositoryDbAccess($this->Db);
            
            $query = "SELECT prefix_id FROM ". DATABASE_PREFIX. "repository_prefix ".
                     " WHERE id = ? ". 
                     " AND is_delete = ?;";
            $params = array();
            $params[] = 10;
            $params[] = 0;
            $result = $dbAccess->executeQuery($query, $params);

			if(count($result) != 1){
				return "";
			}
			return $result[0]["prefix_id"];
            // Mod Item handle management T.Koyasu 2014/01/28 --end--
		}
	}
	
	/**
	 * get Suffix
	 * return item detail uri
	 */
	function getSuffix($title, $item_id, $transStartDate){
		//////////////////////////////////
		// get prefixID
		//////////////////////////////////
		$prefix_id = $this->getPrefixID();
		if($prefix_id == ""){
			return "";
		}
				
		//////////////////////////////////
		// get id server url
		//////////////////////////////////
		$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
				 "WHERE param_name = 'IDServer'; ";
		$result = $this->Db->execute($query);
		if($result === false){
			return "";
		}
		if(count($result) != 1){
			return "";
		}
		$url = $result[0]["param_value"];
		
		// suffix取得リクエストを最大3回行う 2009/09/03 A.Suzuki --start--
		for($ii=0; $ii<3; $ii++){
			//////////////////////////////////
			// entry suffix
			//////////////////////////////////
			$ret_xml = $this->entrySuffix($title, $item_id, $prefix_id, $transStartDate);
			if($ret_xml==null || $ret_xml==""){
				continue;
			}
			
			//////////////////////////////////
			// analy return xml
			//////////////////////////////////
			try{
				// parse xml
				$xml_parser = xml_parser_create();
				$rtn = xml_parse_into_struct( $xml_parser, $ret_xml, $vals );
				if($rtn == 0){
					continue;
				}
				xml_parser_free($xml_parser);
			} catch(Exception $ex){
				continue;
			}
			$continue_flag = false;
			foreach($vals as $val){
				if($val['tag'] == "ENTRY" && $val['type'] == "open"){
					$entry_flg = true;
				} else if($val['tag'] == "ENTRY" && $val['type'] == "close"){
					$entry_flg = false;
				}
				switch ($val['tag']){
					case 'STATUS':
						if($val['value'] != "0"){
							$continue_flag = true;
							break;
						}
						break;
					case 'REQUEST':
						if($val['value'] != "suffix"){
							$continue_flag = true;
							break;
						}
						break;
					case 'ID':
						if($entry_flg){
							$tmp = explode(":", $val['value']);
							if(count($tmp)==3){
								$url .= $tmp[2]."/";
								return $url;
							}
						}
						break;
					default:
						break;
				}
				if($continue_flag == true){
					break;
				}
			}
		}
		return "";
		// suffix取得リクエストを最大3回行う 2009/09/03 A.Suzuki --end--
	}
	
	/**
	 * get Suffix stub
	 * return item detail uri
	 */
/*
	function getSuffix($title, $item_id, $transStartDate){
		//////////////////////////////////
		// get prefixID
		//////////////////////////////////
		$prefix_id = $this->getPrefixID();
		if($prefix_id == ""){
			return "";
		}
		
		$prefixYHandle = "http://id.nii.ac.jp/";
		$suffix = str_pad($item_id, 8, "0", STR_PAD_LEFT);
		$url = $prefixYHandle.$prefix_id."/".$suffix."/";
		return $url;
	}
*/
	
	function entrySuffix($title, $item_id, $prefix_id, $transStartDate){
		////////////////////////////////
		// check BASE_URL
		////////////////////////////////
        if(_DEBUG_FLG){
            // when this site url is localhost, use test url
            $repos = "weko.ivis.co.jp";
        } else {
			// Addition of HTTPS check 2010/02/03 S.Nonomura --start--
    		// bug fix 2010/02/19 Y.Nakao --start--
    		$repos = str_replace("https://", "", BASE_URL);
			$repos = str_replace("http://", "", $repos);
			// bug fix 2010/02/19 Y.Nakao --end--
			// Addition of HTTPS check 2010/02/03 S.Nonomura --end--
			$repos = join(".", array_reverse(explode("/", $repos)));
        }
		////////////////////////////////
		// make entry item XML
		////////////////////////////////
		$date = explode(" ", $transStartDate);
		$time = explode(".", $date[1]);
		// header
		$entry_item_xml = "";
		$entry_item_xml .= 	'<?xml version="1.0" encoding="utf-8"?>'.
							'<feed xmlns="http://www.w3.org/2005/Atom">'.
							//'<title>'. $title .'</title>'.
  							//'<link href="'. BASE_URL .'"/>'.
							//'<updated>'. $date[0].'T'.$time[0].'Z' .'</updated>'.
							// '<author><name>'.  .'</name></author>'.
							'<id>urn:ni3d:'. $prefix_id .'</id>';
		// body
		$entry_item_xml .=	'<entry>'.
							'<link href="'.BASE_URL.'/?action=repository_uri'.
							'&amp;item_id='.$item_id.'"/>'.
							'<id>ni3d:'. $item_id .'</id>'.
							'<updated>'. $date[0].'T'.$time[0].'Z' .'</updated>'.
							'</entry>';
		// end
		$entry_item_xml .= '</feed>';
		
		///////////////////////////////////
		// make send reqest
		///////////////////////////////////
		$message = "a random message";
		$prv_key_pass = "";
		if ($handle = opendir($this->id_dir)) {
			while (false !== ($filename = readdir($handle))) {
				if(!is_dir($filename)){
					$elm = explode(".", $filename);
					if($elm[1] == "pem"){
						$prv_key_pass = $this->id_dir.$filename;
					}
				}
			}
            closedir($handle);
		}
		if($prv_key_pass == ""){
			return "";
		}
		$private_key = file_get_contents($prv_key_pass);
		openssl_sign($message, $signature, $private_key, OPENSSL_ALGO_SHA1);
	
		$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
				 "WHERE param_name = 'suffix'; ";
		$result = $this->Db->execute($query);
		if($result === false){
			return "";
		}
		if(count($result) != 1){
			return "";
		}
		$url = $result[0]["param_value"];
		$formvars = array();
		$formvars['ir_host'] = $repos;
		$formvars['message'] = $message;
		$formvars['signature'] = base64_encode($signature);
		$formvars['data'] = $entry_item_xml;
		//$formvars['data'] = file_get_contents("D:/NII/xampp/htdocs/idtest/atom.xml");

		try
		{
			$snoopy = new Snoopy;
			$snoopy->set_submit_normal();
			$snoopy->agent = "TX3sy68wT7bM";
            // Modfy proxy 2011/12/06 Y.Nakao --start--
            $proxy = $this->getProxySetting();
            if($proxy['proxy_mode'] == 1)
            {
                $snoopy->_isproxy = true;
                $snoopy->proxy_host = $proxy['proxy_host'];
                $snoopy->proxy_port = $proxy['proxy_port'];
                $snoopy->proxy_user = $proxy['proxy_user'];
                $snoopy->proxy_pass = $proxy['proxy_pass'];
            }
            // Modfy proxy 2011/12/06 Y.Nakao --end--
			$res = $snoopy->submit($url, $formvars);
			return $snoopy->results;
		} catch (Exception $ex) {
			echo($ex->getMessage());
		}
		
	}
	// Add cooperation with ID server 2008/10/31 Y.Nakao --end--
	
	// Add prefix auto entry 2009/04/10 A.Suzuki --start--
	/**
	 * createPemFile
	 * pemファイルを生成する
	 * 
	 * @param	$tmp_dir	作業用ディレクトリ名
	 * @param	$cmdPath	OpenSSLコマンドへの絶対パス
	 * @return	$create_flg	true	生成成功
	 * 						false	生成失敗
	 */
	function createPemFile($tmp_dir, $cmdPath){
		$prv_key = "ids-weko-key.pem";
		
        if(_DEBUG_FLG){
            // when this site url is localhost, use test url
            $repos = "weko.ivis.co.jp";
        } else {
			// Addition of HTTPS check 2010/02/03 S.Nonomura --start--
    		// bug fix 2010/02/19 Y.Nakao --start--
    		$repos = str_replace("https://", "", BASE_URL);
			$repos = str_replace("http://", "", $repos);
			// bug fix 2010/02/19 Y.Nakao --end--
			// Addition of HTTPS check 2010/02/03 S.Nonomura --end--
			$repos = join(".", array_reverse(explode("/", $repos)));
        }

		$pub_key = $repos.".pem";
		$pub_key_backup = $repos.".pub";
		
		// pemファイル作成コマンド
		$create = "";
		$create .= $cmdPath;
		$create .= 'openssl req -x509 -nodes -days 36500'.
				   ' -subj "/C=JP" -newkey rsa:1024'.
				   ' -keyout '.$tmp_dir."/".$prv_key.' -out '.$tmp_dir."/".$pub_key;
		
		// コマンド実行
		exec($create);
		
		// ファイルが生成されたかを確認
		$create_flg = false;
		if(file_exists($tmp_dir.DIRECTORY_SEPARATOR.$prv_key)){
			if(file_exists($tmp_dir.DIRECTORY_SEPARATOR.$pub_key)){
				// 秘密鍵を既定の場所にコピーする
				if(!file_exists($this->id_dir)){
					mkdir( $this->id_dir, 0777 );
				} else {
					chmod ( $this->id_dir, 0777 );
				}
				$result_prv = copy($tmp_dir.DIRECTORY_SEPARATOR.$prv_key, $this->id_dir.$prv_key);
				chmod ( $this->id_dir.$prv_key, 0600 );
				
				// copy pub_key (xxx.pub)
				$result_pub = copy($tmp_dir.DIRECTORY_SEPARATOR.$pub_key, $this->id_dir.$pub_key_backup);
				if($result_prv && $result_pub){
					$create_flg = true;
				}
				chmod ( $this->id_dir, 0700 );
			}
		}
		
		return $create_flg;
	}
	
	/**
	 * prefixAutoEntry
	 * prefix自動取得処理
	 * 
	 * @param	$cmdPath	OpenSSLコマンドへの絶対パス
	 * @return	$create_flg	true	生成成功
	 * 						false	生成失敗
	 */
	function prefixAutoEntry($cmdPath){
		// ワークディレクトリ作成
        $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
        $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness('businessWorkdirectory');
        $tmp_dir = $businessWorkdirectory->create();
        $tmp_dir = substr($tmp_dir, 0, -1);
		
		// ディレクトリのパスをセッションに保存
		$this->Session->setParameter("tmp_dir", $tmp_dir);
		
		// 鍵作成
		$result = $this->createPemFile($tmp_dir, $cmdPath);
		if($result === false){
			// ワークディレクトリ削除
			$this->removeDirectory($tmp_dir);
            if(file_exists("./.rnd")){
                unlink("./.rnd");
            }
			$this->Session->removeParameter("tmp_dir");
			return false;
		}

		$result = $this->postPublicKey($tmp_dir);
		if($result === false){
			// ワークディレクトリ削除
			$this->removeDirectory($tmp_dir);
            if(file_exists("./.rnd")){
                unlink("./.rnd");
            }
			
			// 鍵ファイル削除
			if ($handle = opendir($this->id_dir)) {
				while (false !== ($filename = readdir($handle))) {
					if(!is_dir($filename)){
						$elm = explode(".", $filename);
						if(array_pop($elm) == "pem" || array_pop($elm) == "pub"){
							unlink($this->id_dir.$filename);
						}
					}
				}
                closedir($handle);
			}
			$this->Session->removeParameter("tmp_dir");
			return false;
		}

		return true;
	}
	
	/**
	 * postPubliceKey
	 * pemファイルを送信する
	 * 
	 */
	function postPublicKey($tmp_dir){
        if(_DEBUG_FLG){
            // when this site url is localhost, use test url
            $repos = "weko.ivis.co.jp";
        } else {
			// Addition of HTTPS check 2010/02/03 S.Nonomura --start--
    		// bug fix 2010/02/19 Y.Nakao --start--
    		$repos = str_replace("https://", "", BASE_URL);
			$repos = str_replace("http://", "", $repos);
			// bug fix 2010/02/19 Y.Nakao --end--
			// Addition of HTTPS check 2010/02/03 S.Nonomura --end--
			$repos = join(".", array_reverse(explode("/", $repos)));
        }
		
		$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
				 "WHERE param_name = 'prefix'; ";
		$result = $this->Db->execute($query);
		if($result === false){
			return false;
		}
		if(count($result) != 1){
			return false;
		} 
		
		$url = $result[0]["param_value"];
		
		$formvars = array();
		$formfiles = array();
		$formvars['ir_host'] = $repos;
		$formfiles['pkey'] = $tmp_dir."/".$repos.".pem";	// 公開鍵ファイル
		
		try
		{
			$snoopy = new Snoopy;
			$snoopy->set_submit_multipart();
			$snoopy->agent = "TX3sy68wT7bM";
            // Modfy proxy 2011/12/06 Y.Nakao --start--
            $proxy = $this->getProxySetting();
            if($proxy['proxy_mode'] == 1)
            {
                $snoopy->_isproxy = true;
                $snoopy->proxy_host = $proxy['proxy_host'];
                $snoopy->proxy_port = $proxy['proxy_port'];
                $snoopy->proxy_user = $proxy['proxy_user'];
                $snoopy->proxy_pass = $proxy['proxy_pass'];
            }
            // Modfy proxy 2011/12/06 Y.Nakao --end--
			$res = $snoopy->submit($url, $formvars, $formfiles);
			
			$header = $snoopy->headers;
			for($ii=0; $ii<count($header); $ii++){
				if(substr_count($header[$ii],"Content-Type: ") == 1){
					if(substr_count($header[$ii],"application/json") == 1){
						// 失敗
						return false;
					}
				}
				if(substr_count($header[$ii],"auth_session_id: ") == 1){
					// 認証セッションのID
					$auth_session_id = $header[$ii];
					$auth_session_id = str_replace("auth_session_id: ", "", $auth_session_id);
					$auth_session_id = str_replace("\r\n", "", $auth_session_id);
					$this->Session->setParameter("auth_session_id", $auth_session_id);
				}
			}
			
			$filename = $tmp_dir."/"."capcha.png";
			$handle = fopen($filename, "w");
			$size = fwrite($handle, $snoopy->results);
			fclose($handle);
			copy($filename, BASE_DIR."/htdocs/weko/capcha.png");

		} catch (Exception $ex) {
			return false;
		}
		
		// 成功
		return true;
	}
	
	/**
	 * postCaptchaString
	 * 入力された画像ファイルの文字列を送信する
	 * 
	 * @param  $captcha_string	入力された文字列
	 * @param  $auth_session_id	認証セッションID
	 * @return $prefix
	 */
	function postCaptchaString($captcha_string, $auth_session_id){
        if(_DEBUG_FLG){
            // when this site url is localhost, use test url
            $repos = "weko.ivis.co.jp";
        } else {
			// Addition of HTTPS check 2010/02/03 S.Nonomura --start--
			// bug fix 2010/02/19 Y.Nakao --start--
    		$repos = str_replace("https://", "", BASE_URL);
			$repos = str_replace("http://", "", $repos);
			// bug fix 2010/02/19 Y.Nakao --end--
			// Addition of HTTPS check 2010/02/03 S.Nonomura --end--
			$repos = join(".", array_reverse(explode("/", $repos)));
        }

		if($captcha_string == ""){
			return "false";
		}
			
		$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
				 "WHERE param_name = 'prefix'; ";
		$result = $this->Db->execute($query);
		if($result === false){
			return "false";
		}
		if(count($result) != 1){
			return "false";
		} 
		
		$url = $result[0]["param_value"]. "/auth/";
		
		$formvars = array();
		$formvars['ir_host'] = $repos;
		$formvars['auth_session_id'] = $auth_session_id;	// 認証セッションのID
		$formvars['captcha_string'] = $captcha_string;		// 画像にあった文字列
		
		try
		{
			$snoopy = new Snoopy;
			$snoopy->set_submit_normal();
			$snoopy->agent = "TX3sy68wT7bM";
            // Modfy proxy 2011/12/06 Y.Nakao --start--
            $proxy = $this->getProxySetting();
            if($proxy['proxy_mode'] == 1)
            {
                $snoopy->_isproxy = true;
                $snoopy->proxy_host = $proxy['proxy_host'];
                $snoopy->proxy_port = $proxy['proxy_port'];
                $snoopy->proxy_user = $proxy['proxy_user'];
                $snoopy->proxy_pass = $proxy['proxy_pass'];
            }
            // Modfy proxy 2011/12/06 Y.Nakao --end--
		$res = $snoopy->submit($url, $formvars);
			
			$res_json = $snoopy->results;

			$json = new Services_JSON();
			$decoded = $json->decode($res_json);
			if($decoded->status == "OK"){
				// prefixが発行された
				$prefix = $decoded->repo_no;
				return $prefix;
			} else {
				return "false";
			}
		} catch (Exception $ex) {
			return "false";
		}
	}
	// Add prefix auto entry 2009/04/10 A.Suzuki --end--
	
	// Add PDF flash 2010/02/04 A.Suzuki --start--
	/**
	 * PDF convert to Flash
	 * 
	 * @return "true" or "false"
	 */
	function convertToFlash($item_attr, $detail_url, &$errMsg){
		try {
            // Add file convert to SWF for all. Y.Nakao 2011/1/19 --start--
            $convertFlashFlg = false;
            $extension = strtolower($item_attr['upload']['extension']);
            switch($extension){
                case "doc":
                case "docx":
                case "xls":
                case "xlsx":
                case "ppt":
                case "pptx":
                case "pdf":
                case "swf":
                    $convertFlashFlg = true;
                    break;
                default:
                    break;
            }
            if(!$convertFlashFlg){
                return "false";
            }
            // Add file convert to SWF for all. Y.Nakao 2011/1/19 --end--
			// check file save directory exists 
			$contents_path = $this->getFileSavePath("file");
			if(strlen($contents_path) == 0){
				// default directory
				$contents_path = BASE_DIR.'/webapp/uploads/repository/files';
			}
			// check directory exists 
			if( !(file_exists($contents_path)) ){
				$errMsg = 'Not exists file save point.';
				return "false";
			}
			// Get file path
			$contents_path .= '/'.$item_attr['item_id'].'_'.$item_attr['attribute_id'].'_'.$item_attr['file_no'].'.'.$item_attr['upload']['extension'];
			// check file exists 
			if( !(file_exists($contents_path)) ){
				$errMsg = 'Not exists \"'.$item_attr['upload']['file_name'].'\".';
				return "false";
			}
			
			// Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
			// check flash save directory exists
			$flash_contents_path = $this->makeFlashFolder(  $item_attr['item_id'],
                                                            $item_attr['attribute_id'],
                                                            $item_attr['file_no']);
			if(strlen($flash_contents_path) == 0 || !(file_exists($flash_contents_path)) ){
				$errMsg = 'Not exists flash save point.';
				return "false";
			}
            
            // Add file convert to SWF for all. Y.Nakao 2011/1/19 --start--
            if($extension == "swf"){
                $swf_path = $flash_contents_path.'/weko.swf';
                if(file_exists($swf_path)){
                    unlink($swf_path);
                }
                copy($contents_path, $swf_path);
                return "true";
            }
            // Add file convert to SWF for all. Y.Nakao 2011/1/19 --end--
            // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--
            
            // If file size over 100MB, do not convert to flash. 2012/11/19 A.Suzuki --start--
            if(filesize($contents_path) > $this->maxFlashConvertSize_)
            {
                return "false";
            }
            // If file size over 100MB, do not convert to flash. 2012/11/19 A.Suzuki --end--
			
			// ワークディレクトリ作成
            $this->infoLog("businessWorkdirectory", __FILE__, __CLASS__, __LINE__);
            $businessWorkdirectory = BusinessFactory::getFactory()->getBusiness('businessWorkdirectory');
            $tmp_dir = $businessWorkdirectory->create();
            $tmp_dir = substr($tmp_dir, 0, -1);
			
			// IDサーバのアドレスを取得
			$query = "SELECT param_value FROM ". DATABASE_PREFIX ."repository_parameter ".
					 "WHERE param_name = 'IDServer'; ";
			$result = $this->Db->execute($query);
			if($result === false){
				$this->removeDirectory($tmp_dir);
				$errMsg = 'Cannot get IDServer URL.';
				return "false";
			}
			if(count($result) != 1){
				$this->removeDirectory($tmp_dir);
				$errMsg = 'Cannot get IDServer URL.';
				return "false";
			}
			
			// IDサーバに変換するファイルを送信する
			$url = $result[0]['param_value']."cgi-bin/office_conv/index.cgi";
            
            // Modify: use PEAR HTTPRequest 2011/02/07 A.Suzuki --end--
            /////////////////////////////
            // HTTP_Request init
            /////////////////////////////
            // send http request
            $option = array( 
                "timeout" => "300"
            );
            $http = new HTTP_Request($url, $option);
            
//          if(preg_match("/localhost/", BASE_URL)){
//              // when this site url is localhost, use test url
//              $repos = "weko.ivis.co.jp";
//          } else {
                // Addition of HTTPS check 2010/02/03 S.Nonomura --start--
                // bug fix 2010/02/19 Y.Nakao --start--
                $repos = str_replace("https://", "", BASE_URL);
                $repos = str_replace("http://", "", $repos);
                // bug fix 2010/02/19 Y.Nakao --end--
                // Addition of HTTPS check 2010/02/03 S.Nonomura --end--
                
                $repos = join(".", array_reverse(explode("/", $repos)));
//          }
            $message = "a random message";
            // get private key file
            $prv_key_pass = "";
            if ($handle = opendir($this->id_dir)) {
                while (false !== ($filename = readdir($handle))) {
                    if(!is_dir($filename)){
                        $elm = explode(".", $filename);
                        if($elm[count($elm)-1] == "pem"){
                            $prv_key_pass = $this->id_dir.$filename;
                        }
                    }
                }
                closedir($handle);
            }
            if($prv_key_pass == ""){
                $this->removeDirectory($tmp_dir);
                $errMsg = 'Not found prvate key.';
                return "false";
            }
            $private_key = file_get_contents($prv_key_pass);
            openssl_sign($message, $signature, $private_key, OPENSSL_ALGO_SHA1);
            
            // setting HTTP header
            $http->setMethod(HTTP_REQUEST_METHOD_POST);
            $http->addHeader("Content-Type", "multi-part/form-data");
            $http->addHeader("User-Agent", "TX3sy68wT7bM");
            $http->addPostData("ir_host", $repos);
            $http->addPostData("message", $message);
            $http->addPostData("signature", base64_encode($signature));
            $http->addPostData("url", $detail_url);
            $http->addPostData("split", "true");
            $http->addFile("document", $contents_path);
            //ini_set('memory_limit', -1);
            
            /////////////////////////////
            // run HTTP request 
            /////////////////////////////
            $response = $http->sendRequest(); 
            if (!PEAR::isError($response)) { 
                $header = $http->getResponseHeader();   // ResponseHeader
                $res_body = $http->getResponseBody();   // ResponseBody
            }
            
            if(substr_count($header["content-type"], "application/json") == 1){
                // convet NG
                $this->removeDirectory($tmp_dir);
                $errMsg = "\"".$item_attr['upload']['file_name']."\"";
                return "false";
            }
            if(substr_count($header["content-type"], "application/octet-stream") == 1
                || substr_count($header["content-type"], "application/x-tar") == 1 )
            {
                // convert OK
                // Add multiple FLASH files download 2011/02/04 Y.Nakao --start--
                $tmp_tar = $tmp_dir.'/flashArchive.tar.gz';
                $handle = fopen($tmp_tar, "w");
                $size = fwrite($handle, $res_body);
                fclose($handle);
                
                // check tmp_tar
                if( !(file_exists($tmp_tar)) ){
                    $this->removeDirectory($tmp_dir);
                    $errMsg = "\"".$item_attr['upload']['file_name']."\"";
                    return "false";
                }
                // setting flash directory.
                $flashDir = $this->makeFlashFolder($item_attr['item_id'],
                                                   $item_attr['attribute_id'],
                                                   $item_attr['file_no']);
                
                // バージョン違いで解凍できない場合の対応
                if (version_compare(PHP_VERSION, '5.3.0', '>='))
                {
                    $phar = new PharData($tmp_tar);
                    $phar->extractTo($flashDir);
                }
                else {
                    // decompress flash data.
                    File_Archive::extract(
                        File_Archive::read($tmp_tar.'/'),       // 末尾は'/'
                        File_Archive::appender($flashDir.'/')   // 解凍先
                    );
                }
                
                $swf_path = $flashDir.'/weko1.swf';
                $this->removeDirectory($tmp_dir);
                if( !(file_exists($swf_path)) ){
                    $errMsg = "\"".$item_attr['upload']['file_name']."\"";
                    return "false";
                }
                return "true";
                // Add multiple FLASH files download 2011/02/04 Y.Nakao --end--
            }
            // Modify: use PEAR HTTPRequest 2011/02/07 A.Suzuki --end--
			
			// convet NG
			$this->removeDirectory($tmp_dir);
			$errMsg = "\"".$item_attr['upload']['file_name']."\"";
    	    return "false";
    	    
		} catch ( RepositoryException $Exception) {
			// error
			$this->removeDirectory($tmp_dir);
			$errMsg = "\"".$item_attr['upload']['file_name']."\"";
    	    return "false";
		}
	}
	// Add PDF flash 2010/02/04 A.Suzuki --end--
}

?>
