// --------------------------------------------------------------------
//
// $Id: edittree_repository.js 3131 2011-01-28 11:36:33Z haruka_goto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

//------------------------------------------------------
// ノード選択時処理
//  @param tree 今取り扱っているインデックスツリーオブジェクト
//  @param nodeId 選択中ノードID
//  @param blockId 表示されているページのブロックID
// 
onSelect_repos = function(tree,nodeId,blockId)
{
	$("index_name_repos"+blockId).disabled = false;
	$("publish_repos"+blockId).disabled = false;
	$("access_level_repos"+blockId).disabled = false;
	var node=tree.getNodeById(nodeId);
	var checked= (node.pub=="1" ? true : false);
	$("index_name_repos"+blockId).value = node.name;
	$("publish_repos"+blockId).checked = checked;
	$("access_level_repos"+blockId).selectedIndex = node.accessLv-1;
	// ツリー公開日対応 2008/07/09 Y.Nakao --start--
	// 公開日を編集画面に設定
	if ( node.pubdate ){
		var tmp1 = node.pubdate.split(" ");// YYYY-mm:ddを抽出
		var tmp2 = tmp1[0].split("-");
		$('pubyear_repos'+blockId).value = tmp2[0];
		$('pubmonth_repos'+blockId).selectedIndex = tmp2[1]-1;
		$('pubday_repos'+blockId).selectedIndex = tmp2[2]-1;
	}
	else {
		// 公開日の指定がない場合、現在日時を公開日に指定
		var d = new Date();
		var yy = d.getYear();
		yy = (yy < 2000) ? yy+1900 : yy 
		var mm = d.getMonth();
		var dd = d.getDate();
		$('pubyear_repos'+blockId).value = yy;
		$('pubmonth_repos'+blockId).selectedIndex = mm;
		$('pubday_repos'+blockId).selectedIndex = dd-1;
	}
	if ( checked ) { // 公開フラグON
		$("input_date_repos").style.display="block";
	} else { // 公開フラグOFF
		$("input_date_repos").style.display="none";
	}
	// ツリー公開日対応 2008/07/09 Y.Nakao --end--
	return true;
}

/**
	ツリー編集にて公開日、ノード名称をチェックする
	@param tree 今取り扱っているインデックスツリーオブジェクト
	@param nodeId 選択中ノードID
	@param blockId 表示されているページのブロックID
	@param treeErrorMsg エラーメッセージ
			[0]：公開日が不正である旨のエラーメッセージ
			[1]：ノード名が不正である旨のエラーメッセージ
	@return 成功フラグ(成功：true、失敗：false)
*/
onUnselect_repos = function(tree, nodeId, blockId, treeErrorMsg)
{
	var node=tree.getNodeById(nodeId);
	if ( $("publish_repos"+blockId).checked == true ) {
		// 公開日取得
		var pubyear = $("pubyear_repos"+blockId).value;
		var pubmonth = $("pubmonth_repos"+blockId).options[$("pubmonth_repos"+blockId).selectedIndex].value;
		var pubday = $("pubday_repos"+blockId).options[$("pubday_repos"+blockId).selectedIndex].value;
		// 公開日チェック
		if ( pubyear=="" || checkDate_repos(pubyear,pubmonth,pubday) == false )
		{
			// 公開日不正
			alert(treeErrorMsg[0]);
			return false;
		}
		var pubdate = pubyear+"-"+pubmonth+"-"+pubday+" 00:00:00.000";
		if ( node.pubdate != pubdate ) {
			node.pubdate=pubdate;
			node.change=true;
		}
	}
	// ツリー公開日対応 2008/07/09 Y.Nakao --start--
	else
	{
		// 公開日取得
		var pubyear = $("pubyear_repos"+blockId).value;
		var pubmonth = $("pubmonth_repos"+blockId).options[$("pubmonth_repos"+blockId).selectedIndex].value;
		var pubday = $("pubday_repos"+blockId).options[$("pubday_repos"+blockId).selectedIndex].value;
		// 公開日をチェックして正しければ登録
		if ( pubyear!="" || checkDate_repos(pubyear,pubmonth,pubday) )
		{
			// 公開日整合
			var pubdate = pubyear+"-"+pubmonth+"-"+pubday+" 00:00:00.000";
			if ( node.pubdate != pubdate ) {
				node.pubdate=pubdate;
				node.change=true;
			}
		} else {
			if ( node.pubdate.length>0 ) {
				node.change=true;
			}
			node.pubdate="";
		}
	}
	if ( $("index_name_repos"+blockId).value != node.name ) {
		// ノード名チェック
		if ( $("index_name_repos"+blockId).value.length==0 ) {
			//	ノード名不正
			alert(treeErrorMsg[1]);
			return false;
		}
		node.name=$("index_name_repos"+blockId).value;
		node.change=true;
	}
	var pub = node.pub=="1" ? true : false;
	if ( $("publish_repos"+blockId).checked != pub ) {
		node.pub = $("publish_repos"+blockId).checked ? 1 : 0;
		node.change=true;
	}
	if ( $("access_level_repos"+blockId).selectedIndex != (node.accessLv-1) )
	{
		node.accessLv = $("access_level_repos"+blockId).selectedIndex+1;
		node.change = true;
	}
	// ツリー公開日対応 2008/07/09 Y.Nakao --end--
	
	// 値を初期化かつdisableに
	$("index_name_repos"+blockId).value = "";
	$("publish_repos"+blockId).checked = false;
	$('pubyear_repos'+blockId).value = "";
	$('pubmonth_repos'+blockId).selectedIndex = 0;
	$('pubday_repos'+blockId).selectedIndex = 0;
	$("index_name_repos"+blockId).disabled = true;
	$("publish_repos"+blockId).disabled = true;
	$("access_level_repos"+blockId).disabled = true;
	$("input_date_repos").style.display="none";
	
	return true;
}

//------------------------------------------------------
// 「送信」押下時処理
// 更新インデックス(node.change==true)のメタデータを'-'(ハイフン)区切りで連結し、
// AjaxでDBに登録
//  @param tree 今取り扱っているインデックスツリーオブジェクト
//  @param blockId 表示されているページのブロックID
//  @param errormsg 送信時におけるメッセージ
//         [0]：変更がない旨を示すメッセージ
//         [1]：DB登録に失敗した旨を示すメッセージ
function onSubmitEdittree_repos(tree, blockId, errormsg)
{
	if ( !tree.unselectNodes() )
	{
		return;
	}
	var nodes = tree.aNodes;
	var len = nodes.length;
	
	var updateStr="";
	
	// 更新されたindex情報をカンマ(,)区切りで連結
	// index情報＝index_id:create:parent_index_id:show_order:index_short_name:public_state-pub_date:mod_date
	for ( var i=0; i<len; i++ ) {
		var node = nodes[i];
		if ( node.change==true ) {
			if ( node._id ) {
				if ( node.insert ) {
					// ローカルで挿入して削除したノードに関しては何もしない
					continue;
				}
			}
			if ( updateStr.length>0 ){
				updateStr+=",";
			}
			updateStr+=node.id+":";
			if ( node.insert==true ){
				updateStr+="1:";
			} else if ( node._id ) {
				updateStr += "2:";
			} else {
				updateStr+="0:";
			}
			var name = node.name;
			name = name.replace(/:/g,"__2C;"); // インデックスに','(カンマ)がある場合とりあえず置き換え（応急処置）
			name = name.replace(/:/g,"__3A;"); // インデックスに':'(コロン)がある場合とりあえず置き換え（応急処置）
			updateStr += node.pid+":"+node.order+":"+name+":";
			var pubdate = node.pubdate.replace(/:/g,'__3A;');
			var pub = node.pub=="1" ? true : false;
			updateStr += node.pub+":"+pubdate+":";
			updateStr += node.accessLv+":";
			var moddate = node.moddate.replace(/:/g,"__3A;");
			updateStr += moddate;
		}
	}
	if (updateStr.length<1) {
		$("submit_result_repos").innerHTML = errormsg[0];
		return;
	}
	

	var url="index.php";
	var pars="action=repository_action_edit_tree";
	pars += "&update_index="+updateStr;
	$("submit_result_repos").innerHTML = "";
//	$("submit_result_repos").innerHTML += pars+"<br/>";
	var myAjax = new Ajax.Request(
		url,
		{
			method: 'get',
			parameters: pars,
			onSuccess: function(req) {
				var res = req.responseText;
				// res : "YY-mm-dd hh:ii:ss.000/........"
				var arr = res.split("/");
				// arr[0] : "YY-mm-dd hh:ii:ss.000"
				var modDate = arr[0];
				for ( var i=0; i<len; i++ ) {
					var node = nodes[i];
					if ( node.change==true ) {
						node.moddate = modDate;
					}
					node.change = false;
					node.insert = false;
				}
				tree.updateView();
			},
			onFailure: function(req) {
				$("submit_result_repos").innerHTML += '<span style="color: #f00; border: 1px #fa0;">'+errormsg[1]+'</span>';
			},
			onComplete: function(req) {
			}
		}
	);
}
