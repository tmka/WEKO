// --------------------------------------------------------------------
//
// $Id: bargraph_repository.js 3131 2011-01-28 11:36:33Z haruka_goto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
//	BarGraph library
//	2008/03/05 Tatsuki Taniguchi
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/


//*************************************************************
// GraphElement・ｽI・ｽu・ｽW・ｽF・ｽN・ｽg
var clsGraphElement = Class.create();
clsGraphElement.prototype = {
	initialize: function(id,label,value) {
		this.id = id;
		this.label = label;
		this.value = value;
	}
}

//*************************************************************
// clsBarGraph・ｽI・ｽu・ｽW・ｽF・ｽN・ｽg
var clsBargraph = Class.create();
clsBargraph.prototype = {
	initialize: function(name,scale) {
		this.config = {
			autoSort : false
		}
		this.obj = name;
		this.XLabel = 'X';
		this.YLabel = 'Y';
		this.aElements = [];
		this.scale = scale || 1.0;
	},

	//*************************************************************
	// Adds a new element
	add: function(id,label,value) {
		var element = new	 clsGraphElement(id,label,value);
		this.aElements[this.aElements.length] = element;

		if ( $(this.obj+'_graph') ) {
		}
	},

	toString: function(){
		var str = '';
		str += this.obj;
		str += '<table class="graph" cellspacing=1>';
		var len=this.aElements.length;
		for ( var i=0; i<len; i++ ) {
			var element = this.aElements[i];
			str += '<tr>';
			str += '<td width=1 class="graph_cell" align="right">'+element.label+' </td>';
			str += '<td width=1 class="graph_cell" align="center">'+element.value+'</td>';
			str += '<td class="graph_cell"><table celspan=0 border=0><tr><td class="bar" style="width : '+(element.value*this.scale)+'px;" ></td></tr></table></td>';
			str += '</tr>';
		}
		str += '</td></tr></table>';
		return str;
	}
}
