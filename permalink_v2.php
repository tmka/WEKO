<?php

//You have to change the following variable when you use this code for other sites.
$URL = "hoge.com";
$BASE_URL = "https://" .$URL."/";
$opensearch_URL = $BASE_URL . "index.php?action=repository_opensearch&format=atom&keyword=";
$oaipmh_URL = $BASE_URL . "?action=repository_oaipmh&verb=GetRecord&medatadaPrefix=oai_dc&identifier=oai:".$URL.":";
$download_URL = $BASE_URL. "index.php?action=repository_action_common_download&item_id=";

function get_xml($keyword){
	global $opensearch_URL;
	$open_URL = $opensearch_URL;
	$open_URL .= $keyword;
	$XML = simplexml_load_file("$open_URL");
	return $XML;
}

//return contents URL or thumbnail URL
function contents_url($keyword){
	$XML = get_xml($keyword);
	foreach ($XML->entry as $item) {
		$link = (string)$item->link->attributes()->href;
	}
	parse_str($link); //extract only $item_id
	return $item_id;
}

function get_date($keyword){
	$XML = get_xml($keyword);
	foreach ($XML->entry as $item) {
		$link = (string)$item->updated;
	}
	return $link;
}

global $BASE_URL,$download_URL;
if(isset($_GET['keyword'])){
	//thumbnail mode
	if(isset($_GET['thumb'])){
		$keyword = $_GET['keyword'];
		$item = contents_url($keyword);
		if(is_null($item)){
			header("HTTP/1.1 301 Moved Permanently");
	     		header("Location: ".$BASE_URL);
		}else{
			$download_URL .= "&item_id=". $item_id . "&item_no=1&file_id=1&attribute_id=2&file_no=1&img=true";
			$date = date(DATE_RFC850,strtotime(get_date($keyword)));
			header('Last-Modified: ' + $date);
			if($_SERVER['HTTP_IF_MODIFIED_SINCE']){
				//header('HTTP/1.1 304 Not Modified');
				echo 'not changed';
			}else{
				header("HTTP/1.1 301 Moved Permanently");
	     			header("Location: ".$download_URL);
			}
		}
	}
	//CDF mode
	else{
		$keyword = $_GET['keyword'];
		$item_id = contents_url($keyword);
		if(is_null($item_id)){
			header("HTTP/1.1 301 Moved Permanently");
	     		header("Location: ".$BASE_URL);
		}else{
			$download_URL .= "&item_id=". $item_id . "&item_no=1&file_id=1&attribute_id=1&file_no=1";
			$now_date = date(DATE_RFC850,time());
			$date = date(DATE_RFC850,strtotime(get_date($keyword)));

			$If_Header = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
			header("Last-Modified: $date");
			//echo $now_date;
			//header('Last-Modified: Fri Jan 01 2010 00:00:00 GMT');
			//echo $header;
			//client send IF_MODIFIED HEADER
			if($If_Header){
				//echo "If_header =". strtotime($If_Header) . ".";
				//echo "date =". strtotime($date) . ".";
				if(strtotime($If_Header) == strtotime($date)){
					//echo "no need update";
					// not modified wo ireruto kousin sarenai?
					header('HTTP/1.1 304 Not Modified');
					exit();
				}
				else{
					//echo "need update";
					header("HTTP/1.1 301 Moved Permanently");
	     				header("Location: ".$download_URL);
				}
			//client not send IF_MODIFIED HEADER			
			}else{
				//echo 'changed';
				//var_dump($_SERVER);
				header("HTTP/1.1 301 Moved Permanently");
	     			header("Location: ".$download_URL);
			}
		}
	}
}
?>
