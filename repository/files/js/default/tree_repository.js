// --------------------------------------------------------------------
//
// $Id: tree_repository.js 3131 2011-01-28 11:36:33Z haruka_goto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
//	IndexTree library
//	2008/02/26 Kawasaki
//	2008/03/05 Tatsuki Taniguchi
//	2008/03/19 Tatsuki Taniguchi
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/

var repsDrugTree=null; //ドラッグイベント発生中のclsReposTreeオブジェクト
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
// tree node object
var clsReposTreeNode = Class.create();
clsReposTreeNode.prototype = {
	initialize: function(id, pid, order, name, open, url, action, pub, pubdate, moddate, accessLv, isDelete, tree) {
		this.id = id;
		this.pid = pid;
		this.order = order;
		this.name = name;
		this.action = action || "";
		this.url = url;
		this.pub = pub || 0;
		this.pubdate = pubdate || "";
		this.moddate = moddate || "";
		this.accessLv = accessLv || "2";
		this._io = open || false; // _io : 表示状態(false=閉じた状態)
		this._is = false; // _is : 選択状態
		if ( isDelete=="1" ){
		this._id = true;}
		else{this._id = false;} // _id : 削除フラグ
		this._p; // _p : 親ノードのインスタンス
		this.click=false;
		this.change=false;
		this.insert=false;
		this.labelBound=[]; // ラベル矩形 updateNodeした直後有効
		this.slBound=[]; // 後番兵矩形 updateNodeした直後有効
		this.spBound=[]; // 前番兵矩形 updateNodeした直後有効
		this.tree=tree || null;
		// フォルダごとの引越し対応 2008/06/05 Y.Nakao add -^start--
		this.del_type = 0; // 0:削除しない 1:対象INDEX以下全削除 2:対象INDEX以下親INDEXへ移動
		// 2008/06/05 Y.Nakao --end--
	},
	toString: function(){
	 	 return this.id+', '+this.pid+', '+this.order+', '+this.name+' ';
	},

	isChecked: function(){
		if ( this.tree.config.useCheckBox ){
			return $(this.tree.obj+'_check'+this.id).checked;
		}
		else return false;
	}

}

//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
//	node用関数群

//--------------------------------------------------------------
//	Tree表示順
function getOrderVal(a) {
	var ret=0;
	var cur = a;
	while(cur){
		ret++;
		cur = cur._p;
	}
	return parseInt(ret);
}

//--------------------------------------------------------------
// sort用比較関数(ノード)
//	key 1：何階層目か(getOrderVal(a))
//	key 2：同じノード下でのorder
function hikaku(a, b) {
	var valA=getOrderVal(a);
	var valB=getOrderVal(b);
	if(valA<valB)return-1;
	else if(valA>valB)return 1;
	else{
		if(a.order>b.order)return 1;
		if(a.order<b.order)return -1;
		return 0;
	}
}

//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
// tree
// @params editmsg JavaScriptから直接吐かれるHTMLに使用する文字列(ツリー編集のみ使用)
//         [0]：新規 [1]：削除
// @params style 現在のブロックのスタイル名(classic_defaultなど)2008/08/07時点ではsnipetTreeでのみ使用
var clsReposTree = Class.create();
clsReposTree.prototype = {
	//--------------------------------------------------------------
	initialize: function(name,root,title,width,height,color,editmsg,style) {
		this.config = {
			useCheckBox : false,
			allowEdit : false,
			allowSelect : true,
			debug : false,
			msg : editmsg
		}
		 // icon画像
		this.icon = {
			root        : 'images/repository/tree/base.gif',
			folder      : 'images/repository/tree/folder.gif',
			folderOpen  : 'images/repository/tree/folderopen.gif',
			node        : 'images/repository/tree/folderopen.gif',
			empty       : 'images/repository/tree/empty.png',
			line        : 'images/repository/tree/line.gif',
			join        : 'images/repository/tree/join.gif',
			joinBottom  : 'images/repository/tree/joinbottom.gif',
			plus        : 'images/repository/tree/plus.gif',
			plusBottom  : 'images/repository/tree/plusbottom.gif',
			minus       : 'images/repository/tree/close.png',
			minusBottom : 'images/repository/tree/open.png',
			nlPlus      : 'images/repository/tree/close.png',
			nlMinus     : 'images/repository/tree/open.png',
			nlPlusH     : 'images/repository/tree/close.png',
			nlMinusH    : 'images/repository/tree/open.png',
			nlLeaf      : 'images/repository/tree/leaf.png',
			nlSpace     : 'images/repository/tree/space.png',
			nlFolClose  : 'images/repository/tree/folderclose.png',
			nlFolOpen   : 'images/repository/tree/folderopen.png',
			del         : 'images/repository/tree/delete.png',
			insert      : 'images/repository/tree/insert.png'
		};
		this.obj = name;
		this.width = width || "";
		this.height = height || "";
		
		this.title = title || null;
		
		this.color = color || null;
		
		this.style = style || "";

		// rootノードの初期化
		this.root = new clsReposTreeNode(0,-1,1,root,true,null,null,true,"0000-00-00 00:00:00.000","0000-00-00 00:00:00.000","2","0",this);
		
		this.root.name = root;
		this.root._io=true;

		this.aNodes = [];
		this.aNodes[0] = this.root;
		this.aIndent = [];
		this.selectedNode = null;
		this.selectedFound = false;
		this.completed = false;
		this.drugObj = null;
		
	},

	//--------------------------------------------------------------
	// Adds a new node to the node array
	add: function(id, pid, order, name, open, url, action, pub, pubdate, moddate, accessLv, isDelete) {
		var node = new	 clsReposTreeNode(id, pid, order, name, open, url, action, pub, pubdate, moddate, accessLv, isDelete, this);

		var nodes = this.aNodes;
		var len = nodes.length;
		var order_=1;
		for(var i=0;i<len;i++){
			var tmp = nodes[i]
			// 親が既にいれば登録
			if ( tmp.id == pid ) {
				node._p = tmp;
			}
			// 子が既にいれば登録
			if ( tmp.pid == id ) {
				tmp._p = node; 
			}
			
			if ( (tmp.pid==pid) && (order<0) && (tmp.order>=order_) ){
				order_=tmp.order+1;
			}
		}
		if ( order<0 ){
			node.order=order_;
		}
		// 末尾に追加
		nodes[len] = node;
		this.updateOrder();

		node._p = this.getNodeById(pid);
		if ( $(this.obj+'_node0') )
		{
			var obj = this.obj;
			var panel = $(obj+"panel");
			panel.innerHTML = this.toString();
			this.updateView();
		}
	},

	//--------------------------------------------------------------
	// HTML string
	toString: function(){
		var str = '';
		// str += '<div onmousemove="'+this.obj+'.onMouseMove();">';
		if ( this.title ){
			// get block style 2008/08/07 Y.Nakao --start--
			if(this.obj == "snipetTree"){
			} else {
				str += '<div class="th_repos text_color">';
				str += '<table cellspacing="0"';
				str += ' style="';
				if ( this.width>0 ) {
					str += 'width:'+this.width+'px; ';
				}
				str += ' "';
				if (this.color) str += ' bgcolor="'+this.color+'"';
				str += '>';
				str += '<tr><th colspan="2">'+this.title+'</th></tr></table>'
				str += '</div>';
			}
			// get block style 2008/08/07 Y.Nakao --end--
		}
		if ( this.config.allowEdit ) {
			str += '<div class="line_edit_tree padding">';
			str += '<table cellspacing="0" ';
			if ( this.width>0 ) {
				str += 'style="width:'+this.width+'px;" ';
			}
			str += '>';
			str += '<tr><th>';
			str += '<a href="javascript: '+this.obj+'.insertNode()"><img class="branch_repos" src="'+this.icon.insert+'">'+this.config.msg[0]+'</a>&nbsp;&nbsp;&nbsp;';
			str += ' <a href="#" onclick="javascript: '+this.obj+'.deleteNodeConfirm(event, '+this.obj+')"><img class="branch_repos" src="'+this.icon.del+'">'+this.config.msg[1]+'</a>';
			str += '</th>';
			str += '</th></tr></table></div>';
		}
		str += '<table class="tree_paging" ';
		str += ' style="';
		if ( this.width>0 ) {
			str += 'width:'+this.width+'px; ';
		}
		if ( this.height>0 ) {
			str += 'height:'+this.height+'px;';
		}
		str += '">';
		str += '<tr><td id="'+this.obj+'_td" colspan="2" style="vertical-align: top;">';
		str += '<div class="tree_repos" id="'+this.obj+'"';
		str += ' style="width:'+this.width+'px; height:'+this.height+'px"'
		// str += ' onmousedown="'+this.obj+'.onMouseDown();"';
		str += '>';
		str += this.addHtmlNode(-1);
		str += '</div>';
		str += '</td></tr></table>';
		// ドラッグ用ノードの作成
		str += '<div class="drgimg_repos" id="'+this.obj+'_drugimage"></div>';
		
		return str;
	},

	//--------------------------------------------------------------
	//	親のinnerHTML下に自Nodeを追加
	addHtmlNode: function(pid,parent){
		var str = '';
			for ( var i=0; i<this.aNodes.length; i++ ) {
				var node = this.aNodes[i];
				if ( node.pid == pid ) {
					var id = node.id;
					var obj = this.obj;
					// ツリーレイアウト改善 2008/07/08 Y.Nakao --start--
					if(i==0){
						str += '<span class="node_repos0" id="'+obj+'_node'+id+'">';
					} else {
						str += '<span class="node_repos" id="'+obj+'_node'+id+'">';
					}
					// ツリーレイアウト改善 2008/07/08 Y.Nakao --end--
	//				str += '<span class="nodeline_repos">';
					str += '<span class="nodeline_repos" id="'+obj+'_nodeline'+id+'">';
					if (this.hasChild(id)) {
						str += '<img class="branch_repos" id="'+obj+'_branch'+id+'" onclick="javascript: ' + obj + '.clickBranch('+id+',true)"';
						if(node._io==true) { str+=' src="'+this.icon.nlMinus+'" '; }
						else{ str+=' src="'+this.icon.nlPlus+'" '; }
					}
					else{
						str += '<img class="branch_repos" style="cursor: default;" id="'+obj+'_branch'+id+'" onclick="javascript: ' + obj + '.clickBranch('+id+',true)"';
						str+=' src="'+this.icon.nlSpace+'" ';
					}
					str += '/>';
					// ツリーにフォルダ画像追加 2008/07/08 Y.Nakao --start--
					str += '<img class="folder_repos" id="'+obj+'_folderimg'+id+'"';
					str += ' unselectable="on"';
					str += ' onmousedown="'+obj+'.onMouseDownNodelabel('+id+')"';
					str += ' onclick="' + obj + '.clickNode('+id+')"';
					str+=' src="'+this.icon.nlFolClose+'" ';
					str += '/>';
					// ツリーにフォルダ画像追加 2008/07/08 Y.Nakao --end--
					
					//if ( this.config.useCheckBox ){str += '<input type=checkbox class="chk_repos" id="'+obj+'_check'+node.id+'" onclick="'+obj+'.clickCheck('+id+')"; />';}
					if ( this.config.useCheckBox ){if(id!=0){str += '<input type=checkbox class="chk_repos" id="'+obj+'_check'+node.id+'" onclick="'+obj+'.clickCheck('+id+')"; />';}}	//				str += '<a class="nodelabel_repos" id="'+obj+'_nodelabel'+id+'" unselectable="on" onmousedown="'+obj+'.onMouseDownNodelabel('+id+');" onclick="' + obj + '.clickNode('+id+')">';
					if ( this.config.debug ){
						str += '<a class="debug_repos" id="'+obj+'_debugnode'+id+'">'+node.order+'</a>';
					}
					str += '<a class="nodelabel_repos"';
					str += ' id="'+obj+'_nodelabel'+id+'"';
					str += ' unselectable="on"';
					str += ' onmousedown="'+obj+'.onMouseDownNodelabel('+id+')"';
					str += ' onclick="' + obj + '.clickNode('+id+')"';
					if (node.url && node.url!=""){
						str += ' href="'+node.url+'"';
					}
					str += '>';
					str += node.name;
					str += '</a>'; // nodelabel閉じ
					if ( this.config.allowEdit==true ){
					str += '<input type=text class="txt_repos" id="'+obj+'_txt'+id+'" value="'+node.name+'" onchange="'+obj+'.onChangeEdit('+id+')" onblur="'+obj+'.onChangeEdit('+id+')"/>';
				}
				str += '</span>'; // nodeline閉じ
				if ( this.config.allowEdit==true ){
					str += '<div id="'+obj+'_sentryP'+id+'" class="sentryP_repos"></div>';
				}
				str += this.addHtmlNode(id,node);
				str += '<div id="'+obj+'_sentry'+id+'" class="sentry_repos"></div>';
				str += '</span>'; // node閉じ
			}
		}
		return str;
	},
		
	getNewId: function() {
		var id=1;
		var len=this.aNodes.length;
		for ( var i=0; i<len+2; i++ ) {
			var flag=0;
			for ( var j=0; j<len; j++ ) {
				var node=this.aNodes[j];
				if ( node.id == id )
				{
					flag=1;
					break;
				}
			}
			if (flag==0) return id;
			id=id+1;
		}
		return -1;
	},

	//--------------------------------------------------------------
	getSelectedNode: function() {
		var len=this.aNodes.length;
		for (var i=0; i<len; i++ ) {
			var node = this.aNodes[i];
			if(node._is == true){ return node; }
		}
		return null;
	},

	//--------------------------------------------------------------
	getNodeById: function(id){
		var len=this.aNodes.length;
		for (var i=0; i<len; i++ ) {
			var node = this.aNodes[i];
			if(node.id == id){ return node; }
		}
	},

	//--------------------------------------------------------------
	hasChild: function(id){
		var nodes=this.aNodes;
		var len=this.aNodes.length;
		for (var i=0; i<len; i++ ) {
			var node = nodes[i];
			if(!(node._id) && node.pid == id){ return true; }
		}
		return false;
	},

	getCheckedNodes: function(){
		var nodes=this.aNodes;
		var len=this.aNodes.length;
		var ret=[];
		for (var i=0; i<len; i++ ) {
			if (nodes[i].id != 0) {
				if ( nodes[i].isChecked() )
					ret[ret.length]=nodes[i];
			}
		}
		return ret;
	},

	//--------------------------------------------------------------
	//	親のinnerHTML下に自Nodeを追加
	updateOrder: function() {

		var nodes = this.aNodes;
		var len = nodes.length;

		// ルート以下表示順に並び替え
		nodes.sort(hikaku);
	},

	//--------------------------------------------------------------
	// nodelabel、sentry、sentryPのboundsを更新
	updateBounds: function() {
		var len=this.aNodes.length;
		var nodes = this.aNodes;
		for(var i=0;i<len;i++){
			var node = nodes[i];
			var labelDiv = $(this.obj+'_nodelabel'+node.id);
			node.labelBound=getElementBounds(labelDiv);
			var slDiv = $(this.obj+'_sentry'+node.id);
			if(slDiv)node.slBound=getElementBounds(slDiv);
			var spDiv = $(this.obj+'_sentryP'+node.id);
			if(spDiv)node.spBound=getElementBounds(spDiv);
		}
	},

	//--------------------------------------------------------------
	updateView: function() {
		// オブジェクト名と同じ名前のdivブロックを取得し、
		// MouseMoveイベントをObserve
		var len=this.aNodes.length;
		var nodes = this.aNodes;
		for (var i=0;i<len;i++) {
			var node = nodes[i];
			this.updateNode(node);
		}
		// boundsを更新
		this.updateBounds();

		var div=$(this.obj+"_td");
 
		div.object = this;
		Event.observe(div, "mousemove", clsReposTreeMouseMove, false);
		Event.observe(div, "mouseup", clsReposTreeMouseUp, false);
		Event.observe(div, "mouseout", clsReposTreeMouseOut, false);
		Event.observe(div, "mousedown", clsReposTreeMouseDown, false);
		var divDrug=$(this.obj+'_drugimage');
		Event.observe(divDrug, "mousemove", clsReposTreeMouseMove, false);
		Event.observe(divDrug, "mouseup", clsReposTreeMouseUp, false);
		Event.observe(divDrug, "mouseout", clsReposTreeMouseOut, false);
		
		
		//Event.observe(window.document, "mousemove", clsReposTreeMouseMove, false);
		//Event.observe(window.document, "mouseup", clsReposTreeMouseUp, false);
		//Event.observe(window.document, "mouseout", clsReposTreeMouseOut, false);
		
	},

	//--------------------------------------------------------------
	updateNode: function(node) {
		var nodeDiv = $(this.obj+'_node'+node.id);
		// ルートじゃなかったら
		// 親が閉じていたら非表示
		if ( node.id>0 ) {
			nodeDiv.style.display = (node._p._io) ? 'block': 'none';
		}
		
		
		// 削除フラグがONの場合非表示
		if ( node._id ) {
			nodeDiv.style.display = 'none';
		}

		// (deleteじゃない)子がいるか、OpenかCloseか
		// アイコンの切り替え
		var branchDiv = $(this.obj+'_branch'+node.id);
		if (this.hasChild(node.id) ) {
			if (node._io){
				branchDiv.src=this.icon.nlMinus;
			} else {
				branchDiv.src=this.icon.nlPlus;
			}
		} else {
			//branchDiv.src=this.icon.nlLeaf;
			branchDiv.src=this.icon.nlSpace;
		}
		// ツリーにフォルダ画像追加 2007/07/08 Y.Nakao --start--
		var folderImg = $(this.obj+'_folderimg'+node.id);
		folderImg.src=this.icon.nlFolClose;
		// ツリーにフォルダ画像追加 2007/07/08 Y.Nakao --end--

		// 編集モードだったら
		if ( this.config.allowEdit==true )
		{
			// テキストエリア初期表示は隠す
			var txtDiv = $(this.obj+'_txt'+node.id);
			Element.hide(txtDiv);

		
			// 子持ちかつ開いたノードの場合は先頭番兵を表示
			var sentryDiv = $(this.obj+'_sentryP'+node.id);
			if (node._io && this.hasChild(node.id) ) {Element.show(sentryDiv);}
			else {Element.hide(sentryDiv);}
		
			// rootの場合は後番兵を非表示
			if( node.pid<0 ){Element.hide($(this.obj+'_sentry'+node.id));}
		}
		// 編集モード以外だったら番兵を非表示
		else
		{
			var sentryDiv = $(this.obj+'_sentry'+node.id);
			Element.hide(sentryDiv);
		}


		// 選択状態だったら
		var nodelabelDiv = $(this.obj+'_nodelabel'+node.id);
		if ( node._is ) {
			nodelabelDiv.className = "nodelabel_s_repos";
			// ツリーにフォルダ画像追加 2008/07/08 Y.Nakao --start--
			var folderImg = $(this.obj+'_folderimg'+node.id);
			folderImg.src=this.icon.nlFolOpen;
			// ツリーにフォルダ画像追加 2008/07/08 Y.Nakao --end--
		}else{
			nodelabelDiv.className = "nodelabel_repos";
		}
		
		// 表示文字列更新
		nodelabelDiv.innerHTML = node.name;
		if ( node.change ) {
			// 赤'*'表記
			nodelabelDiv.innerHTML += '<span style="color: #f00">*</span>';
		}

		// デバッグモード
		if ( this.config.debug )
		{
			var debugDiv = $(this.obj+'_debugnode'+node.id);
			debugDiv.innerHTML = ' '+node.order + ', '+getOrderVal(node)+', '+node.change;
		}
	},

	//--------------------------------------------------------------
	isRoot: function(node,anc){
		if ( node==null ) return false;
		if ( anc==null ) return false;
		if ( node==anc ) return true;
		if ( node.id==0 ) return false;
		var ret = this.isRoot(node._p,anc);
		return ret;
	},

	//--------------------------------------------------------------
	changeParent: function(node,parent){
		// 親子関係で矛盾が無いかチェック
		if ( !repsDrugTree.isRoot(parent,node) ) {
		// 元のnodeを削除
		var oldParentDiv = $(repsDrugTree.obj+'_node'+node.pid);
		var nodeDiv = $(repsDrugTree.obj+'_node'+node.id);
		//
		var oldOrder = node.order;
		node.order=1;

		var oldPid = node.pid;
		var newPid = parent.id;
		node.pid = newPid;
		node._p = parent;
		node.change = true;

		var nodes=this.aNodes;
		var len=nodes.length;
		for(var i=0;i<len;i++){
			if(nodes[i].pid==oldPid && nodes[i].order>oldOrder){
				nodes[i].order-=1;
				nodes[i].change=true;
			}
			if(nodes[i].pid==newPid && nodes[i].order>=node.order && nodes[i]!=node){
				node.order=nodes[i].order+1;
				nodes[i].change=true;
			}
		}

		// parent下の番兵ノードの手前にnodeを作成
//		Element.remove(nodeDiv);
//		new Insertion.Before($(repsDrugTree.obj+'_sentry'+parent.id), nodeDiv);

//		var newParentDiv = $(repsDrugTree.obj+'_node'+node.pid);
		node._p._io=true;
		this.updateOrder();
$(this.obj+"panel").innerHTML = this.toString();
		//	this.updateNode(node._p);
		this.updateView();
		}
	},

	//--------------------------------------------------------------
	insertNext: function(node,prev){
		// 親子関係で矛盾が無いかチェック
		if ( !repsDrugTree.isRoot(prev,node) ) {
			var nodeDiv = $(repsDrugTree.obj+'_node'+node.id);

			var oldPid = node.pid;
			var newPid = prev.pid;
			node.pid = newPid;
			node._p = prev._p;
			node.change = true;

			// orderを入れ替え
			var oldOrder = node.order;
			var newOrder = prev.order+1;
			node.order = newOrder;
			var nodes=this.aNodes;
			var len=nodes.length;
			for(var i=0;i<len;i++){
				if ( nodes[i].pid==oldPid ){
					if(nodes[i].order>oldOrder){
						nodes[i].order-=1;
						nodes[i].change = true;
					}
				}
				if ( nodes[i].pid==newPid &&nodes[i].id!=node.id){
					if(nodes[i].order>=newOrder){
						nodes[i].order+=1;
						nodes[i].change = true;
					}
				}
			}

			// nextの手前にnodeを作成
//			Element.remove(nodeDiv);
//			new Insertion.After($(repsDrugTree.obj+'_node'+prev.id), nodeDiv);
			this.updateOrder();
$(this.obj+"panel").innerHTML = this.toString();
			this.updateView();
		}
	},

	//--------------------------------------------------------------
	// insertBeforeと言いながら
	// 前番兵の後ろに挿入(nextは親ノードになる)
	insertBefore: function(node,next){
		// 親子関係で矛盾が無いかチェック
		if ( !repsDrugTree.isRoot(next,node) ) {
			var nodeDiv = $(repsDrugTree.obj+'_node'+node.id);

			var oldPid = node.pid;
			var newPid = next.id;
			node.pid = newPid;
			node._p = next;
			node.change = true;
			
			// orderを入れ替え
			var oldOrder = node.order;
			var newOrder = 1;
			node.order = newOrder;
			var nodes=this.aNodes;
			var len=nodes.length;
			for(var i=0;i<len;i++){
				if ( nodes[i].pid==oldPid ){
					if(nodes[i].order>oldOrder){
						nodes[i].order-=1;
						nodes[i].change = true;
					}
				}
				if ( nodes[i].pid==newPid ){
					if(nodes[i].order>=newOrder && nodes[i].id!=node.id){
						nodes[i].order+=1;
						nodes[i].change = true;
					}
				}
			}

			// parent下の前番兵ノードの後ろにnodeを作成
//			Element.remove(nodeDiv);
//			new Insertion.After($(repsDrugTree.obj+'_sentryP'+next.id), nodeDiv);
			this.updateOrder();
$(this.obj+"panel").innerHTML = this.toString();
			this.updateView();
		}
	},

	//--------------------------------------------------------------
	onMouseDownNodelabel: function(id){
		//dump("mousedown");
		var node = this.getNodeById(id);
		var divNodelabel = $(this.obj+'_nodelabel'+id);
		if ( this.config.allowEdit )
		{
			this.drugObj = node;
			repsDrugTree = this;
			var img = $(this.obj+'_drugimage');
			img.innerHTML = divNodelabel.innerHTML;
		}
		return true;
	},

	//--------------------------------------------------------------
	editNodeLabel: function(id){
	return ;
		var txtFieldDiv=$(this.obj+'_txt'+id);
		var nodeLabelDiv=$(this.obj+'_nodelabel'+id);
		// nodeLabelDiv.innerHTML = txtFieldDiv.value;
		txtFieldDiv.value = this.getNodeById(id).name;
		Element.show(txtFieldDiv);
		txtFieldDiv.focus();
		txtFieldDiv.select();
		Element.hide(nodeLabelDiv);
	},

	//--------------------------------------------------------------
	onChangeEdit: function(id){
		var txtFieldDiv=$(this.obj+'_txt'+id);
		var nodeLabelDiv=$(this.obj+'_nodelabel'+id);
		
		var node=this.getNodeById(id);
		//dump($(this.obj+'_txt'+id).value);
		if ( txtFieldDiv.value != nodeLabelDiv.innerHTML ){
			node.name = txtFieldDiv.value;
			node.change=true;
			this.updateView();
		}
		Element.hide(txtFieldDiv);
		Element.show(nodeLabelDiv);
		this.updateNode(node);
	},

	//--------------------------------------------------------------
	// ※スタイルシート使用により未使用
	onBranchOver: function(id){
		var txtBranchDiv=$(this.obj+'_branch'+id);
		if ( this.getNodeById(id)._io==true )
			txtBranchDiv.src = this.icon.nlMinusH;
		else
			txtBranchDiv.src = this.icon.nlPlusH;
	},

	//--------------------------------------------------------------
	// スタイルシート使用により未使用
	onBranchOut: function(id){
		var txtBranchDiv=$(this.obj+'_branch'+id);
		if ( this.getNodeById(id)._io==true )
			txtBranchDiv.src = this.icon.nlMinus;
		else
			txtBranchDiv.src = this.icon.nlPlus;
	},

	//--------------------------------------------------------------
	//clickImg : 画像をクリックしたのか
	clickBranch: function(id,clickImg) {
		var node = this.getNodeById(id);
		node._io = !node._io;
		this.updateView();
		if ( clickImg ) {
	//		this.onBranchOver(id);	// スタイルシート使用により未使用
		}
	},

	unselectNodes: function() {
		var nodes = this.aNodes;
		var len = nodes.length;
		var ret = true;
		for (var i=0;i<len;i++) {
			if(nodes[i]._is==true) {
				if ( nodes[i].unselect ) {
					ret = eval(nodes[i].unselect);
				}
				if ( ret ) {
					nodes[i]._is=false;
					this.updateNode(nodes[i]);
				}
			}
		}
		return ret;
	},
	
	selectNode: function(id) {
		var node = this.getNodeById(id);
		var unselectSuccess=true;
		var success=true;
		if ( node._is==false ) {
			unselectSuccess=this.unselectNodes();
		}

		if ( unselectSuccess==true ) {
			node._is=true;
			if ( node.action ){ success=eval(node.action); }
		}
		else { success=false; }
		return success;
	},

	//--------------------------------------------------------------
	clickNode: function(id) {
		var node = this.getNodeById(id);
		// 編集モードかつ選択状態だったら
		if ( this.config.allowEdit==true && node._is ){
			this.editNodeLabel(id);
			return false;
		}
		// 選択許可だったら
		else if ( this.config.allowSelect ){
			var nodes = this.aNodes;
			var len = nodes.length;
			this.selectNode(node.id);
			$(this.obj+'_branch'+id).focus();
			this.updateView();
			var divNode = $(this.obj+'_nodelabel'+id);
		}
		else {this.clickBranch(id);}
		
		// エンバーゴ機能追加 2008/07/10 Y.Nakao --start--
		if($('select_embargo_index')){
			$('select_embargo_index').innerHTML = node.name;
			$('select_embargo_index_id').value = node.id;
			$('select_index').style.display = 'none';
		}
		// エンバーゴ機能追加 2008/07/10 Y.Nakao --end--
	},

	//--------------------------------------------------------------
	clickCheck: function(id){
	},

	//--------------------------------------------------------------
	focusOut: function(id) {
		var node = this.getNodeById(id);
	},

	//--------------------------------------------------------------
	deleteNode_: function(node) {
		node._id=true;
		node.change=true;
		var len=this.aNodes.length;
		for (var i=0; i<len; i++ ) {
			var n = this.aNodes[i];
			if(n.pid == node.id){ this.deleteNode_(n); }
		}

	},

	//--------------------------------------------------------------
	deleteNode: function() {
		var node = this.getSelectedNode();
		if ( node && node.pid>=0 )
		{
			this.deleteNode_(node);
			this.updateNode(node);
			
		}
	},
	// this function move to "repository.js in repositoryIndexDeleteConfirm()" 2008/12/10 Y.Nakao --start--
	// フォルダごとの引越し対応 2008/06/09 Y.Nakao add --start--
	//---------------------------------------------------------------
	/* 
	deleteNodeConfirm: function(event, edit_tree_obj){
	
		var node = this.getSelectedNode();
		if ( node && node.pid>=0 )
		{
			// 削除対象に紐付くインデックス、アイテムの対処確認をNC2用ポップアップで表示させる…
			var top_el = $(this.id);
			this.params = new Object();
			//prefix_id_nameは任意。
			this.params["prefix_id_name"] = "popup_repository_edit_tree_confirm";
			this.params["action"] = "repository_view_common_edit_tree_confirm";
			// リクエストパラメタの設定
			this.params["key"] = "value";	// この形でリクエストパラメタが設定可能
			this.params["edit_tree_obj"] = edit_tree_obj.obj;
			this.params["sel_node_pid"] = node.pid;
			this.params["sel_node_name"] = node.name;
			this.sendparams = new Object();
			this.sendparams["top_el"] = top_el;
			if (!event) {
				//場所指定が可能。表示したい位置のタグのクラス名を以下で指定すること
				var date_el = Element.getChildElementByClassName(top_el, "th_classic_content content");
				var offset = Position.positionedOffset(date_el);
				this.sendparams['x'] = offset[0];
				this.sendparams['y'] = offset[1];
			}
			commonCls.sendPopupView(event, this.params, this.sendparams);
		}
	},
	*/
	// this function move to "repository.js" 2008/12/10 Y.Nakao --end--
	
	// 削除対象INDEXに紐付くすべてのINDEXおよびアイテムを削除
	deleteIndexFolder: function(popup_id){
		// 確認用ポップアップ除去
		commonCls.removeBlock(popup_id);
		// 削除パターンを設定
		var node = this.getSelectedNode();
		if ( node && node.pid>=0 )
		{
			node.del_type = 1;
		}
		// 表示変更
		this.deleteNode();
		// 全体更新
		this.updateView();
	},
	// 削除対象INDEXに紐付くすべてのINDEXおよびアイテムを親INDEXに紐付ける
	moveParentFolder: function(popup_id){
		// 確認用ポップアップ除去
		commonCls.removeBlock(popup_id);
		// 削除パターンを設定
		var node = this.getSelectedNode();
		// 表示変更
		// 親インデックスに移動したように表示する
		var node = this.getSelectedNode();
		if ( node && node.pid>=0 )
		{
			// 選択されたノードは処理対象
			node._id=true;
			node.change=true;
			node.del_type = 2;
			
			// 親を繋ぎ換えて移動させる
			var len=this.aNodes.length;
			for (var i=0; i<len; i++ ) {
				var n = this.aNodes[i];
				if(n.pid == node.id){
					// nをnode._pの下へ移動
					repsDrugTree = this;
					this.changeParent(n, node._p);
				}
			}
			this.updateNode(node);
		}
		// 全体更新
		this.updateView();
	},	
	// 2008/06/09 Y.Nakao update --end--

	//--------------------------------------------------------------
	insertNode: function() {
		// 選択ノードを取得
		var node = this.getSelectedNode();
		var pid = 0; // 選択ノードが無かった場合、rootを親に
		var id = this.getNewId();
		if ( node ) {pid=node.id;}
		// 選択ノードオブジェクトの下にノードを追加
		this.add(id,pid,-1,"New Node",false,"","",0,"","");

		node=this.getNodeById(pid); // 改めて親ノード取得
		var new_node=this.getNodeById(id);
		this.onNewNode(new_node);
		node._io=true;
		new_node.change=true;
		new_node.insert=true;
		if ( this.selectNode(id) ) {
			this.editNodeLabel(id);
		}
		this.updateView();
	}

}

//--------------------------------------------------------------
clsReposTreeMouseMove = function(e){
	if (repsDrugTree==null)return true;
	if (repsDrugTree.drugObj==null)return true;
	
	var cx=Event.pointerX(e);
	var cy=Event.pointerY(e);
	var img = $(repsDrugTree.obj+'_drugimage');
	img.style.left = (cx-20);
	img.style.top = (cy-10);
	// dump(cx+','+cy);

	// ドラッグ中のオブジェクトがツリーノードまたは番兵の上に来た。
	var len = repsDrugTree.aNodes.length;
	for ( i=0; i<len; i++ ){
		var node = repsDrugTree.aNodes[i];
		var boundL = node.labelBound;
		if ( boundL[0]<cy && cy<boundL[2] && boundL[1]<cx && cx<boundL[3] ) {
			if ( !repsDrugTree.isRoot(node,repsDrugTree.drugObj) ) {
				var divNodelabel = $(repsDrugTree.obj+'_nodelabel'+node.id);
				divNodelabel.style.backgroundColor="#00f";
				divNodelabel.style.color="#fff";
			}
		} else {
			var divNodelabel = $(repsDrugTree.obj+'_nodelabel'+node.id);
			divNodelabel.style.backgroundColor="";
			divNodelabel.style.color="";
		}

		// 後番兵の上かどうか
		var divSentry = $(repsDrugTree.obj+'_sentry'+node.id);
//		if ( isObjOver(divSentry, cx, cy) ) {
		var boundSl = node.slBound;
		if ( boundSl[0]<cy && cy<boundSl[2] && boundSl[1]<cx && cx<boundSl[3] ) {
			if ( !repsDrugTree.isRoot(node,repsDrugTree.drugObj) ) {
				divSentry.className = "sentryH_repos";
			}
		} else {
			divSentry.className = "sentry_repos";
		}

		// 前番兵の上かどうか
		var divSentryP = $(repsDrugTree.obj+'_sentryP'+node.id);
//		if ( isObjOver(divSentryP, cx, cy) ) {
		var boundSp = node.spBound;
		if ( boundSp[0]<cy && cy<boundSp[2] && boundSp[1]<cx && cx<boundSp[3] ) {
			if ( !repsDrugTree.isRoot(node,repsDrugTree.drugObj) ) {
				divSentryP.className = "sentryPH_repos";
			}
		} else {
			divSentryP.className = "sentryP_repos";
		}

	}
	return true;
}

//--------------------------------------------------------------
clsReposTreeMouseUp = function(e){
	if ( repsDrugTree==null ){return;}

	if ( repsDrugTree.drugObj==null ){return;}

	// ドロップ可能なノードの上でマウスアップ
	var cx=Event.pointerX(e);
	var cy=Event.pointerY(e);
	var len = repsDrugTree.aNodes.length;
	
	for ( i=0; i<len; i++ ) {
		var node = repsDrugTree.aNodes[i];
//			if ( node == repsDrugTree.drugObj ) continue;

		// ノードの上ならこの末尾に追加
		var divNodelabel = $(repsDrugTree.obj+'_nodelabel'+node.id);
/*		var tl = getElementTopLeft(divNodelabel);
		var top = tl[0];
		var left = tl[1];
		var bottom = top+divNodelabel.offsetHeight;
		var right = left+divNodelabel.offsetWidth;
*/		//  dump(cx+","+cy);
		var boundL = node.labelBound;
		if ( boundL[0]<cy && cy<boundL[2] && boundL[1]<cx && cx<boundL[3] ) {
//		if ( isObjOver($(repsDrugTree.obj+'_nodeline'+node.id), cx, cy) ) {
			if ( !repsDrugTree.isRoot(node,repsDrugTree.drugObj) ) {
				// repsDrugTree.drugObjをnodeの直下に移動
				repsDrugTree.changeParent(repsDrugTree.drugObj,node);
				var divNodelabel = $(repsDrugTree.obj+'_nodelabel'+node.id);
				divNodelabel.style.backgroundColor="";
				divNodelabel.style.color="";
				break;
			}
		}

		// 後番兵の上なら順序入れ替え
		var divSentry = $(repsDrugTree.obj+'_sentry'+node.id);
		var boundSl = node.slBound;
		if ( boundSl[0]<cy && cy<boundSl[2] && boundSl[1]<cx && cx<boundSl[3] ) {
//		if ( isObjOver(divSentry, cx, cy) ) {
			repsDrugTree.insertNext(repsDrugTree.drugObj,node);
			divSentry.className = "sentry_repos";
			break;
		}

		// 前番兵の上なら順序入れ替え
		// 自分の下の先頭に配置
		var divSentryP = $(repsDrugTree.obj+'_sentryP'+node.id);
		var boundSp = node.spBound;
		if ( boundSp[0]<cy && cy<boundSp[2] && boundSp[1]<cx && cx<boundSp[3] ) {
//		if ( isObjOver(divSentryP, cx, cy) ) {
			repsDrugTree.insertBefore(repsDrugTree.drugObj,node);
			divSentryP.className = "sentryP_repos";
			break;
		}
	}
	//dump("style:end");
	var img = $(repsDrugTree.obj+'_drugimage');
	img.style.left = -50;
	img.style.top = -50;
	repsDrugTree.drugObj=null;
	repsDrugTree=null;
	//dump("mouseup");
	return true;
}

//--------------------------------------------------------------
clsReposTreeMouseOut = function(e){
	if ( repsDrugTree==null ){return;}
	if ( repsDrugTree.drugObj==null ){return;}

	var cx=Event.pointerX(e);
	var cy=Event.pointerY(e);
	divTreepanel = $(repsDrugTree.obj+"panel");

	// Tree範囲外だったら
	var tree_tl = getElementTopLeft(divTreepanel);
	var tree_top = tree_tl[0];
	var tree_left = tree_tl[1];
	var tree_bottom = tree_top+divTreepanel.offsetHeight;
	var tree_right = tree_left+divTreepanel.offsetWidth;
	if ( cx<tree_left || cy<tree_top || tree_right<cx || tree_bottom<cy ) {
		// ドロップ可能なノードの上でマウスアップ
		var img = $(repsDrugTree.obj+'_drugimage');
		img.style.left = -50;
		img.style.top = -50;
		repsDrugTree.drugObj=null;
		repsDrugTree=null;
		//dump("mouseup");
	}
	return true;
}


//--------------------------------------------------------------
clsReposTreeMouseDown = function(e) {
	if ( this.object ) {
		var obj = this.object.obj;
		this.object.updateBounds();
	
		var nodes = this.object.aNodes;
		var len = nodes.length;
		var cx=Event.pointerX(e);
		var cy=Event.pointerY(e);
		var flag = false;
		for(var i=0;i<len;i++){
			var node=nodes[i];
			var divNodelabel=$(obj+'_nodelabel'+node.id);
			var divNodetxt=$(obj+'_txt'+node.id);
			if ( isObjOver(divNodelabel,cx,cy) ){
				flag=true;
				break;
			}
			if (divNodetxt!=null && isObjOver(divNodetxt,cx,cy)){
				flag=true;
				break;
			}
		}
	
		if ( flag==false )
		{
	//		for(var i=0;i<len;i++){
	//			var node=nodes[i];
	//			node._is=false;
	//		}
			this.object.unselectNodes();
		}
	}
	else if ( Event.element(e).object ) {
		var object = Event.element(e).object;
		var obj = object.obj;
		object.updateBounds();
	
		var nodes = object.aNodes;
		var len = nodes.length;
		var cx=Event.pointerX(e);
		var cy=Event.pointerY(e);
		var flag = false;
		for(var i=0;i<len;i++){
			var node=nodes[i];
			var divNodelabel=$(obj+'_nodelabel'+node.id);
			var divNodetxt=$(obj+'_txt'+node.id);
			if ( isObjOver(divNodelabel,cx,cy) ){
				flag=true;
				break;
			}
			if (divNodetxt!=null && isObjOver(divNodetxt,cx,cy)){
				flag=true;
				break;
			}
		}
	
		if ( flag==false )
		{
	//		for(var i=0;i<len;i++){
	//			var node=nodes[i];
	//			node._is=false;
	//		}
			object.unselectNodes();
		}
	}
}


