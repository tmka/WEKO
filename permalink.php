<?php

//probably you need to change following variable.
$BASE_URL = "https://akebono-vlf.db.kanazawa-u.ac.jp/";
$opensearch_URL = $BASE_URL . "index.php?action=repository_opensearch&format=atom&keyword=";
$oaipmh_URL = $BASE_URL . "?action=repository_oaipmh&verb=GetRecord&medatadaPrefix=oai_dc&identifier=oai:akebono-vlf.db.kanazawa-u.ac.jp:";
$download_URL = $BASE_URL. "index.php?action=repository_action_common_download&item_id=";


if(isset($_GET['keyword'])){
	$keyword = $_GET['keyword'];
	$opensearch_URL .= $keyword;
	echo "<p>$opensearch_URL</p>\n";
	$XML = simplexml_load_file("$opensearch_URL");
	#iterator for XML variable
	foreach ($XML->entry as $item) {
		$link = (string)$item->link->attributes()->href;
	}
	parse_str($link); //$item_id 
	if(is_null($item_id)){
		header("HTTP/1.1 301 Moved Permanently");
     	header("Location: ".$BASE_URL);
	}else{
		$download_URL .= $item_id . "&item_no=1&attribute_id=1&file_no=1";
		header("HTTP/1.1 301 Moved Permanently");
     	header("Location: ".$download_URL);
	}
}
?>
