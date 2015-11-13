// --------------------------------------------------------------------
//
// $Id: repository.js 3131 2011-01-28 11:36:33Z haruka_goto $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------

var clsRepository = Class.create();
var repositoryCls = Array();

// 2008.02.15 Child Tags of JuNii2 Parent Tags (S.Kawasaki)
// [0] : hasChildren(0:No, 0>:ChildIndex)
var junii2Child = [ [0, 1,0,2,0,3,0,4,0,5,0,6,0,7,0,8,0,9,0,10,0,11,0,12,0,13,0,14,0,15,0,16,0,17,0,18,0,19,0,20,0,21,0,22,0,23,0,24,0,25,0,26,0,27,0,28],
			["JuNii2child_1-1","JuNii2child_1-2","JuNii2child_1-3"],
			["JuNii2child_2-1","JuNii2child_2-2","JuNii2child_2-3"],
			["JuNii2child_3-1","JuNii2child_3-2","JuNii2child_3-3"],
			["JuNii2child_4-1","JuNii2child_4-2","JuNii2child_4-3"],
			["JuNii2child_5-1","JuNii2child_5-2","JuNii2child_5-3"],
			["JuNii2child_6-1","JuNii2child_6-2","JuNii2child_6-3"],
			["JuNii2child_7-1","JuNii2child_7-2","JuNii2child_7-3"],
			["JuNii2child_8-1","JuNii2child_8-2","JuNii2child_8-3"],
			["JuNii2child_9-1","JuNii2child_9-2","JuNii2child_9-3"],
			["JuNii2child_10-1","JuNii2child_10-2","JuNii2child_10-3"],
			["JuNii2child_11-1","JuNii2child_11-2","JuNii2child_11-3"],
			["JuNii2child_12-1","JuNii2child_12-2","JuNii2child_12-3"],
			["JuNii2child_13-1","JuNii2child_13-2","JuNii2child_13-3"],
			["JuNii2child_14-1","JuNii2child_14-2","JuNii2child_14-3"],
			["JuNii2child_15-1","JuNii2child_15-2","JuNii2child_15-3"],
			["JuNii2child_16-1","JuNii2child_16-2","JuNii2child_16-3"],
			["JuNii2child_17-1","JuNii2child_17-2","JuNii2child_17-3"],
			["JuNii2child_18-1","JuNii2child_18-2","JuNii2child_18-3"],
			["JuNii2child_19-1","JuNii2child_19-2","JuNii2child_19-3"],
			["JuNii2child_20-1","JuNii2child_20-2","JuNii2child_20-3"],
			["JuNii2child_21-1","JuNii2child_21-2","JuNii2child_21-3"],
			["JuNii2child_22-1","JuNii2child_22-2","JuNii2child_22-3"],
			["JuNii2child_23-1","JuNii2child_23-2","JuNii2child_23-3"],
			["JuNii2child_24-1","JuNii2child_24-2","JuNii2child_24-3"],
			["JuNii2child_25-1","JuNii2child_25-2","JuNii2child_25-3"],
			["JuNii2child_26-1","JuNii2child_26-2","JuNii2child_26-3"],
			["JuNii2child_27-1","JuNii2child_27-2","JuNii2child_27-3"],
			["JuNii2child_28-1","JuNii2child_28-2","JuNii2child_28-3"]];

clsRepository.prototype = {
	initialize: function(id) {
		this.id = id;
		// grid test
		var visibleRows = 10;
		var totalRows = 100;
		var opts = {
			onscroll : this.updateHeader.bind(this)
		};
		// compLiveGrid test
		// if this codes are comment outed, table will be normal table
// 		this.compLiveGrid = new parent.compLiveGrid(
// 		new compLiveGrid(
//								this.id,
// 								visibleRows,
// 								totalRows,
// 								"repository_view_edit_gridaction",	// action name when any events occurred in the liveGrid
// 								opts);
	},
	//
	initRepositoryGrid: function(id) {
		this.id = id;
		var visibleRows = 10;
		var totalRows = 100;
		var opts = {
			onscroll : this.updateHeader.bind(this)
		};
		new compLiveGrid (this.id, visibleRows,	totalRows, "repository_view_edit_gridaction", opts);
	},	
	//
	initRepository: function(edit_flag, count, visible_row, id) {
		var opts = {
			requestParameters : new Array("repository_id="+id, "nazo_param"+0),
			prefetchBuffer : true,
			sort : true
		};
		//
		new compLiveGrid (this.id, visible_row, count, "repository_view_edit_gridaction", opts);
	},	
	updateHeader: function ( liveGrid, offset ) {
       var top_el = $(this.id);
       var repository_record_num = Element.getChildElementByClassName(top_el,"repository_record_num");		
       repository_record_num.innerHTML = (offset+1) + " - " +
         (offset+liveGrid.metaData.getPageSize()) + " of " +
         liveGrid.metaData.getTotalRows();
    },
	repositoryRegist: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_edit_init" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoSub01: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		//?p?????[?^?Y?e
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_init" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoSubzero: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		//?p?????[?^?Y?e
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_edit_subzero" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoMain: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		//?p?????[?^?Y?e
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_init" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoEdit: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		//?p?????[?^?Y?e
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_edit_display" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// 2008/1/29 
	//

	// Goto ItemTypeCreate (S.Kawasaki)
	//repositoryItemTypeCreate: function() {
	//	var top_el = $(this.id);
	//	var form = top_el.getElementsByTagName("form")[0];
	//	var params = new Object();
	//	params["method"] = "post";
	//	params["param"] = "action=repository_action_edit_itemtype_create" + "&"+ Form.serialize(form);
	//	params["top_el"] = top_el;
	//	params["loading_el"] = top_el;
	//	params["target_el"] = top_el;
	//	commonCls.send(params);
	//},
	// Goto AdminAdmit (S.Kawasaki)
	// Add site License 2008/10/20 Y.Nakao --start--
	repositoryAdminAdmit: function() {
	
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
            // choose all form controls
            var children = forms[i].elements;
            var len2 = children.length;
            // choose "input (text, textarea, chkbox)" and
            for (j=0; j<len2; j++) {
                if(
                    (
                        (
                            forms[i].id == "id_admin_site_license"
                            && children[j].tagName == "INPUT"
                            && (children[j].type == "text" || children[j].type == "textarea" )
                        )
                        || children[j].name == "harvesting_repositoryName[]"
                        || children[j].name == "harvesting_baseUrl[]"
                        || children[j].name == "harvesting_post_index[]"
                        || children[j].name == "harvesting_post_name[]"
                        // Add 2013/09/06 R.Matsuura --start--
                        || children[j].name == "harvesting_from_date_year[]"
                        || children[j].name == "harvesting_from_date_month[]"
                        || children[j].name == "harvesting_from_date_day[]"
                        || children[j].name == "harvesting_from_date_hour[]"
                        || children[j].name == "harvesting_from_date_minute[]"
                        || children[j].name == "harvesting_from_date_second[]"
                        || children[j].name == "harvesting_set_param[]"
                        || children[j].name == "harvesting_execution_date[]"
                        // Add 2013/09/06 R.Matsuura --end--
                    )
                    && children[j].value == ""
                  )
                {
                    // hankaku space.
                    children[j].value = " ";
                }
            }
            form_data += "&" + Form.serialize(forms[i]);
		}
	
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_adminadmit" + form_data;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add site License 2008/10/20 Y.Nakao --end--
	// Goto CreateFulltext (S.Kawasaki)
	repositoryCreateFullText: function() {
		var top_el = $(this.id);
		var forms = top_el.getElementsByTagName("form");
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
			form_data += "&" + Form.serialize(forms[i]);
		}
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_createfulltext" + form_data;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryItemTypeCreate: function() {
		var top_el = $(this.id);
		var form = document.getElementById('itemtype_create');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_edit" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeEdit (S.Kawasaki)
	repositoryItemTypeEdit: function() {
		var top_el = $(this.id);
		var form = document.getElementById('itemtype_setting');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_edit" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	
	// 2008.04.30 N.Tomoda(IVIS) Start
	repositoryTest: function( test_id ) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
//		params["param"] = "action=repository_action_edit_test" + "&"+ Form.serialize(form)+ "&test_id="+test_id;
		params["param"] = "action=repository_action_edit_test" + "&test_id=" + test_id + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// 2008.04.30 N.Tomoda(IVIS) End
		
	// ItemTypeEdit, AddMetadata  (S.Kawasaki)
	repositoryItemTypeEditAddmetadata: function() {
		// 2008/02/28
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_addmetadata" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// 2008/02/26 DeleteMetaData nakao
	repositoryItemTypeEditDellmetadata: function(click_delete_btn_num) {
		// 2008/02/28
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// Send FormData & argument
		params["param"] = "action=repository_action_edit_itemtype_dellmetadata" + "&"+ Form.serialize(form) + "&dell_metadata_number=" + click_delete_btn_num;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeConfirm (S.Kawasaki)
	repositoryItemTypeConfirm: function() {
		// 2008/02/28
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_confirm" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Push Cancel Button -> DelSessionData -> GotoEdit 2008/02/27 nakao
	repositoryDelSessionData: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_dellsessiondata";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Push Shuffle Up Button 2008/03/04 nakao
	repositoryShuffleUp :function(shuffle_up_idx){
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// Send FormData & argument
		params["param"] = "action=repository_action_edit_itemtype_shufflemetadata" + "&"+ Form.serialize(form) + "&shuffle_idx=" + shuffle_up_idx + "&shuffle_flg=true";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryDoiSet: function(targetIndexId) {
		var top_el = $(this.id);
		// var form = top_el.getElementsByTagName("form")[0];
		var form = document.getElementById('enter_doi_form');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_item_doi" + 
		                  "&targetIndexId=" + targetIndexId + 
		                  "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Push Shuffle Down Button 2008/03/04 nakao
	repositoryShuffleDown :function(shuffle_down_idx){
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// Send FormData & argument
		params["param"] = "action=repository_action_edit_itemtype_shufflemetadata" + "&"+ Form.serialize(form) + "&shuffle_idx=" + shuffle_down_idx + "&shuffle_flg=false";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeSetting (S.Kawasaki)
	repositoryItemTypeSetting: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_setting" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeMapping (S.Kawasaki)
	repositoryItemTypeMapping: function() {
		var top_el = $(this.id);
		var form = document.getElementById('itemtype_setting');
		var params = new Object();
		params["method"] = "post";
		var str = "action=repository_action_edit_itemtype_mapping" + "&"+ Form.serialize(form);
		params["param"] = str;
//		params["param"] = "action=repository_action_edit_itemtype_mapping" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeMappingConfirm (S.Kawasaki)
	repositoryItemTypeMappingConfirm: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_mappingconfirm" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemTypeMappingAdddb (S.Kawasaki)
	repositoryItemTypeMappingAdddb: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_mappingadddb" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add item type import export 2008/09/09 Y.Nakao --start--
	// Goto ItemTypeImport 
	repositoryImportItemtypeFileUpload: function() {
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		
		attachment_params['param'] = {"action":"repository_action_edit_import_upload"};

		attachment_params['callbackfunc'] = this.repositoryItemTypeImport.bind(this);
		commonCls.sendAttachment(attachment_params);
	},
	repositoryItemTypeImport: function() {
		var top_el = $(this.id);
		var params = new Object();
		params["param"] = "action=repository_action_edit_itemtype_import";
		params["method"] = "post";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryItemTypeExport: function() {
		var form = document.getElementById('itemtype_setting');
		pars = '';
		pars += _nc_base_url + '/index.php?action=repository_action_edit_itemtype_export&' + Form.serialize(form);		// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
		pars += '&page_id='+$("page_id").value;
		pars += '&block_id='+$("block_id").value;  
		location.href = pars;
	},
	// Add item type import export 2008/09/09 Y.Nakao --end--
	// Goto ItemTypeAdddb(S.Kawasaki)
	repositoryItemTypeAdddb: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_adddb" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryItemTypeLoad: function(){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_load" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryItemTypeCopy: function(){
		var top_el = $(this.id);
		var form = document.getElementById('itemtype_setting');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_copy" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemSelectEdit (S.Kawasaki)
	repositoryItemSelectType: function(mode) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_selecttype" + "&"+ Form.serialize(form) + "&save_mode=" + mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditFiles (S.Kawasaki)
	repositoryItemEditFiles: function(mode) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_editfiles" + "&"+ Form.serialize(form) + "&save_mode=" + mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditFiles
	repositoryItemEditFilesFromDetailView: function() {
		var top_el = $(this.id);
		var form = document.getElementById('send_date');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_selecttype" + "&"+ Form.serialize(form) + "&return_screen=1&save_mode=next";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditFiles
	repositoryItemEditFilesFromWorkFlow: function(Item_Id, Item_No, active_tab) {
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_selecttype" + "&item_id=" + Item_Id + "&item_no=" + Item_No + "&workflow_active_tab=" + active_tab + "&return_screen=2&save_mode=next";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditFilesLicense (S.Kawasaki)
	repositoryItemEditFilesLicense: function(mode) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		var add_params = new Object();
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		this.repositoryItemAddSpaceToEmptyText(form);
//		var childelms = form.elements;
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_editfileslicense" + "&"+ Form.serialize(form) + "&save_mode=" + mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditTexts (S.Kawasaki)
	repositoryItemEditTexts: function(save_mode) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		var add_params = new Object();
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		this.repositoryItemAddSpaceToEmptyText(form);
		var childelms = form.elements;		// nakami kensa		
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_edittexts" + "&"+ Form.serialize(form) + "&save_mode=" + save_mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Reload Test (S.Kawasaki)
	repositoryReloadPages: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		var add_params = new Object();
		params["method"] = "post";
		var str = "active_action=repository_action_edit_itemtype_setting";
		params["param"] = str + "&"+ Form.serialize(form);
//		params["param"] = "action=pages_view_main" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositorySendView: function(id, parameter, params, headermenu_flag) {
		//Event.unloadCache(id);
		var top_el = $(id);		
		if(params == undefined) {
			var params = new Object();
		}		
		params["focus_flag"] = 1;
		if(typeof parameter == 'string') {
			var re_action = new RegExp("^action=", 'i');
			if(parameter.match(re_action)) {
				params["param"] = parameter;
			} else {
//				params["param"] = {"action":parameter};
				params["param"] = {"action":parameter,
									"active_action":"repository_view_main_item_selecttype"};
			}
		} else {
			params["param"] = parameter;
		}
		
		params["top_el"] = top_el;
		var content = "";
		if(headermenu_flag != null && headermenu_flag != undefined) {
			var headermenu = Element.getChildElementByClassName(top_el,"_headermenu");
			if(headermenu) {
				var div_headermenu = document.createElement("DIV");
				div_headermenu.className = headermenu.className;
				div_headermenu.innerHTML = headermenu.innerHTML;
				params["headermenu"] = div_headermenu;
			}
		}
		if(!params["target_el"]) params["target_el"] = top_el.parentNode;
		if(!params["loading_el"]) params["loading_el"] = top_el;
		commonCls.send(params);

	},
	// Goto ItemUploadFiles (S.Kawasaki)
    // Modify version up for entry file UI 2012/03/09 Y.Nakao --start--
	repositoryItemUploadFiles: function(mode,target,attridx) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var cnt = this.repositoryItemCountUpload(form);
		if(cnt == 0 || mode=='delete_file')
		{
			this.repositoryItemEditSaveSessionFiles(mode,target,attridx);
			return;
		}
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		// check action
		attachment_params['param'] = {"action":"repository_action_main_item_uploadfiles"};
		attachment_params['callbackfunc'] = function(){this.repositoryItemEditSaveSessionFiles(mode,target,attridx);}.bind(this);
		commonCls.sendAttachment(attachment_params);		
	},
    // Modify version up for entry file UI 2012/03/09 Y.Nakao --end--
	// Goto SaveSession(file)(S.Kawasaki)
	repositoryItemEditSaveSessionFiles: function(mode,target,attridx) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// option params
		var opt = "save_mode=" + mode + "&" + "target=" + target + "&" + "attridx=" + attridx;
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		this.repositoryItemAddSpaceToEmptyText(form);
		
		params["param"] = "action=repository_action_main_item_editfiles" + "&"+ Form.serialize(form) + "&" + opt;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemEditLinks (S.Kawasaki)
	repositoryItemEditLinks: function(save_mode) {
		var top_el = $(this.id);
		var forms = top_el.getElementsByTagName("form");
		var params = new Object();
		params["method"] = "post";
		var OpendIds = "";
		var CheckedIds = $('check_insert_idx').value;
		var CheckedNames = $('check_insert_idx_name').value;
		
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
			form_data += "&" + Form.serialize(forms[i]);
		}
		params["param"] =   "action=repository_action_main_item_editlinks" + form_data + "&" +
							"OpendIds=" + OpendIds + "&" +
							"CheckedIds=" + CheckedIds + "&" +
							"CheckedNames=" + CheckedNames + "&" + 
							"save_mode=" + save_mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto SaveSession (S.Kawasaki)
	repositoryItemEditSaveSession: function(mode,target,attridx) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// option params
		var opt = "save_mode=" + mode + "&" + "target=" + target + "&" + "attridx=" + attridx;
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		this.repositoryItemAddSpaceToEmptyText(form);
		params["param"] = "action=repository_action_main_item_edittexts" + "&" +
						  Form.serialize(form) + "&" + opt;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// input " " to empty text element.
	repositoryItemAddSpaceToEmptyText: function(formEl) {
		// choose all form controls
		var children = formEl.elements;
//		var children = formEl.getElementsByTagName("input");	// OK
//		var children = formEl.ChildNodes;						// NG
		var len = children.length;
		// choose "input (text, textarea, chkbox)" and
		for (j=0; j<len; j++) {
			if(children[j].tagName == "INPUT" &&
			   children[j].type == "text" &&
			   children[j].value == "" ){
			   children[j].value = " ";		// hankaku space.								
			} else if(children[j].tagName == "TEXTAREA" &&
			   children[j].value == "" ){
			   children[j].value = " ";		// hankaku space.						
			}
		}
	},
	// count upload input (any value set).
	repositoryItemCountUpload: function(formEl) {
		// choose all form controls
		var children = formEl.elements;
		var len = children.length;
		var cnt = 0;
		// choose "input (text, textarea, chkbox)" and
		for (j=0; j<len; j++) {
			if(children[j].tagName == "INPUT" &&
			   children[j].type == "file" &&
			   children[j].value != "" ){
			   cnt++;					
			}
		}
		return cnt;
	},
	// change next "hidden" value
	changeNextHiddenVal: function(elm, IsChecked) {
		if(IsChecked) {
		   	elm.value = 1;
		} else {
			elm.value = 0;
		}
//		var parentEl = elm.parentNode;			// get parent element.
//		var children = parentEl.getElementsByTagName("input");
//		var len = children.length;
//		// choose "input (text, textarea, chkbox)" and
//		for (j=0; j<len; j++) {
//			if(children[j].type == "hidden") {
//				if(IsChecked) {
//				   	children[j].value = 1;
//				} else {
//					children[j].value = 0;
//				}
//			}
//		}
	},
	// change next "hidden" value
	changeNextHiddenValRad: function(elm, Val) {
		var parentEl = elm.parentNode;			// get parent element.
		var children = parentEl.getElementsByTagName("input");
		var len = children.length;
		// choose "input (text, textarea, chkbox)" and
		for (j=0; j<len; j++) {
			if(children[j].type == "hidden") {
			   	children[j].value = Val;
			}
		}
	},
	repositoryItemEditDoi: function(save_mode) {
		var top_el = $(this.id);
		var forms = top_el.getElementsByTagName("form");
		var params = new Object();
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
			form_data += "&" + Form.serialize(forms[i]);
		}
		params["method"] = "post";
		params["param"] =   "action=repository_action_main_item_editdoi" + form_data + "&save_mode=" + save_mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto ItemConfirm (S.Kawasaki)
	repositoryItemConfirm: function(save_mode) {
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] =   "action=repository_action_main_item_confirm&save_mode=" + save_mode;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// go to detail settiog(F.Arisaka)
	repositoryItemDetailsetting: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_item_detailsetting" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// 2008/03/17
	repositoryWorkflowItemDetail: function(event, Item_ID, Item_No, file_flg, page_id, block_id){
		var top_el = $(this.id);
		this.params = new Object();
		this.params["prefix_id_name"] = "popup_repository_" + 0;
		//this.params["action"] = "action=repository_action_edit_item_detail" + "&"+ Form.serialize(form);
		//this.params["action"] = "repository_view_main_item_detail";
		this.params["action"] = "repository_view_common_item_detail";
		this.params["key"] = "value";
		//var sel = Element.getChildElementByID(top_el, "item_selector");
		//this.params["item_id"] = sel.options[sel.selectedIndex].value;
		this.params["item_id"] = Item_ID;
		this.params["item_no"] = Item_No;
		this.params["file_flg"] = file_flg;
		//this.params["page_id"] = page_id;
		//this.params["block_id"] = block_id;
						
		this.sendparams = new Object();		
		this.sendparams["top_el"] = top_el;
		if (!event) {
			var date_el = Element.getChildElementByClassName(top_el, "th_classic_content content");
			var offset = Position.positionedOffset(date_el);
			this.sendparams['x'] = offset[0];
			this.sendparams['y'] = offset[1];
		}
		commonCls.sendPopupView(event, this.params, this.sendparams);
	},
	// nakao 2008/03/15
	repositoryItemDelete: function(){
		var top_el = $(this.id);
		var params = new Object();
		var form = document.getElementById('send_date');
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_detail" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// delete ItemType
	repositoryItemTypeDelete: function(){
		var top_el = $(this.id);
		var form = document.getElementById('itemtype_setting');
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_delete" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
		},
	repositoryChangeShownStatus: function(shown_status){
		var top_el = $(this.id);
		var params = new Object();
		var form = document.getElementById('send_date');
		var status = 0;
		if(shown_status){
			status = 1;
		}
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_detail" + "&"+ Form.serialize(form) + "&shown_status=" + status;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Goto Repository Setting Top (Webmaster Only)(S.Kawasaki)
	repositorySettingTop: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// to init repository setting. (2008/03/02, S.Kawasaki)
		params["param"] = "action=repository_action_edit_setting_params" + "&"+ Form.serialize(form);
//		params["param"] = "action=repository_action_edit_setting_init" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Change Style of "JuNii2_child" select box
	repositorySettingTop: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_setting_top" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// 2008/02/27 input_type explanation visible nakao
	// 2008/08/11 Fix element Y.Nakao --start--
	setSelectItemVisible: function(selIdx,rowNo, error_msg) {
		// 2008/03/04 plural_check insert ... ?
		var required = "required"+rowNo;
		var plural = "plural"+rowNo;
		var plural_display = "plural_display"+rowNo;
		var plural_dummy = "plural_dummy"+rowNo;
		var disp = "disp"+rowNo;
		var newline = "newline"+rowNo;
		var newline_display = "newline_display"+rowNo;
		var newline_dummy = "newline_dummy"+rowNo;
		var hidden = "hidden"+rowNo;
		
		var elem_required = document.getElementById(required);
		var elem_plural = document.getElementById(plural);
		var elem_plural_display = document.getElementById(plural_display);
		var elem_plural_dummy = document.getElementById(plural_dummy);
		var elem_disp = document.getElementById(disp);
		var elem_newline = document.getElementById(newline);
		var elem_newline_display = document.getElementById(newline_display);
		var elem_newline_dummy = document.getElementById(newline_dummy);
		var elem_hidden = document.getElementById(hidden);
		
		var chk_fileprice = document.getElementById("chk_fileprice");
		if(chk_fileprice.value == rowNo){
			chk_fileprice.value = "";
		}
		
		var chk_supple = document.getElementById("chk_supple");
		if(chk_supple.value == rowNo){
			chk_supple.value = "";
		}
		
		var chk_heading = document.getElementById("chk_heading");
		if(chk_heading.value == rowNo){
			chk_heading.value = "";
		}
		
		// candidata 
		if( 3<=selIdx && selIdx<=5)
		{
			$('candidate_'+rowNo).style.display = "";
			elem_required.disabled = false;
			elem_hidden.disabled = false;
			// plural disabled to true
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			if(elem_hidden.checked != true){
				elem_disp.disabled = false;
				elem_newline.disabled = false;
			}
		}
        // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
        // thumbnail
        else if( selIdx == 7 ){
            $('candidate_'+rowNo).style.display = "none";
            elem_required.disabled = false;
            elem_hidden.disabled = false;
            elem_plural.disabled = false;
            elem_plural_display.style.display = "";
            elem_plural_dummy.style.display = "none";
            if(elem_hidden.checked == true){
                elem_newline.checked = false;
                elem_newline_display.style.display = "";
                elem_newline_dummy.style.display = "none";
            } else {
                elem_newline.checked = true;
                elem_newline_display.style.display = "none";
                elem_newline_dummy.style.display = "";
                elem_disp.disabled = false;
            }
        }
        // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-
		// file
		else if( selIdx == 8 ){
			$('candidate_'+rowNo).style.display = "none";
			elem_required.disabled = false;
			elem_hidden.disabled = false;
			elem_plural.disabled = false;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			// newline disabled
			if(elem_hidden.checked == true){
				elem_newline.checked = false;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			} else {
				elem_newline.checked = true;
				elem_newline_display.style.display = "none";
				elem_newline_dummy.style.display = "";
				elem_disp.disabled = false;
			}
		}
		// biblio_info
		else if( selIdx == 9 ){
			$('candidate_'+rowNo).style.display = "none";
			elem_required.disabled = false;
			elem_hidden.disabled = false;
			// plural disabled
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			if(elem_hidden.checked != true){
				elem_disp.disabled = false;
				elem_newline.disabled = false;
			}
		}
		// Add contents page 2010/07/02 Y.Nakao --start--
		// heading
		else if( selIdx == 11 ){
			var pul_box = document.getElementById("type"+rowNo);
			if(chk_heading.value!=""){
				alert(error_msg[2]);
				pul_box.selectedIndex = 0;
				this.setSelectItemVisible(1, rowNo);
				return false;
			}
			$('candidate_'+rowNo).style.display = "none";
			chk_heading.value = rowNo;
			elem_required.disabled = false;
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_disp.disabled = false;
			elem_newline.disabled = false;
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			elem_hidden.disabled = false;
		}
		// Add contents page 2010/07/02 Y.Nakao --end--
		// 2009/08/12 add supple K.Ito --start--
		//supple
		else if (selIdx == 12){
			var pul_box = document.getElementById("type"+rowNo);
			if(chk_supple.value!=""){
				alert(error_msg[1]);
				pul_box.selectedIndex = 0;
				this.setSelectItemVisible(1, rowNo);
				return false;
			}
			$('candidate_'+rowNo).style.display = "none";
			chk_supple.value = rowNo;

			elem_required.checked = false;
			elem_required.disabled = true;
			elem_plural.checked = true;
			elem_plural.disabled = false;
			elem_plural_display.style.display = "none";
			elem_plural_dummy.style.display = "";
			elem_disp.checked = false;
			elem_disp.disabled = true;
			elem_newline.checked = false;
			elem_newline.disabled = true;
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			elem_hidden.disabled = false;
		}
		// 2009/08/12 add supple K.Ito --end--
		
		// file_price
		else if(selIdx == 13){
			var pul_box = document.getElementById("type"+rowNo);
			if(chk_fileprice.value!=""){
				alert(error_msg[0]);
				pul_box.selectedIndex = 0;
				this.setSelectItemVisible(1, rowNo);
				return false;
			}
			$('candidate_'+rowNo).style.display = "none";
			chk_fileprice.value = rowNo;
			// newline disabled
			elem_required.disabled = false;
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			// newline disabled
			if(elem_hidden.checked == true){
				elem_newline.checked = false;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			} else {
				elem_newline.checked = true;
				elem_newline_display.style.display = "none";
				elem_newline_dummy.style.display = "";
				elem_disp.disabled = false;
			}
		}
		else
		{
			elem_hidden.disabled = false;
			elem_required.disabled = false;
			$('candidate_'+rowNo).style.display = "none";
			// plural and newline disabled to false
			elem_plural.disabled = false;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			if(elem_hidden.checked != true){
				elem_disp.disabled = false;
				elem_newline.disabled = false;
			}
		}
	},
	// 2008/08/11 Fix element Y.Nakao --start--
	setLicenceVisible: function(idx1,idx2,licence) {
		var span = document.getElementById("span_" + idx1 + "_" + idx2);
		var text = document.getElementById("licence_free_" + idx1 + "_" + idx2);
		if( licence == "licence_free"){
			span.style.display = "block";
			text.style.display = "block";
		} else {
			span.style.display = "none";
			text.style.display = "none";
		}
	},
	setDefaultIndexVisible: function(elm,choice) {
		if( choice == "0"){
			elm.style.display = "none";
		} else {
			elm.style.display = "";
		}
	},
	setEmbargoVisible: function(span, IsChecked) {
		if( IsChecked ){
			span.style.display = "block";
		} else {
			span.style.display = "none";
		}
	},	
	setSelectItemVisible2: function(elem,selIdx) {
		if( selIdx == 6)
		{
			elem.nextSibling.style.display = "block";
			elem.nextSibling.nextSibling.style.display = "block";
		}
		else
		{
			elem.nextSibling.style.display = "none";
			elem.nextSibling.nextSibling.style.display = "none";
		}
	},	
	// set and display JuNii2 children. 
	// elem : top document
	setJunii2Child: function(elem_sel, n) {
		var len = elem_sel.options.length;
		// cleanup
		for (i=0; i<len; i++){
			elem_sel.options[i] = null;
		}
		if(junii2Child[0][n] == 0){
			// when child option menu is nothing.
			elem_sel.options[0] = new Option("-1","-1");	// Dummy Option (kazuawase)
			elem_sel.selectedIndex = 0;
			elem_sel.style.visibility = "hidden";		// if has no child, hide the select box.
		} else {			
			// when several child options are exists.
			var index = junii2Child[0][n];
			len = junii2Child[index].length;
			// set chilldren of parent select box.
			for (i=0; i<len; i++) {
				elem_sel.options[i] = new Option(junii2Child[index][i],junii2Child[index][i]);
			}
			elem_sel.style.visibility = "visible";	// if has any child, visible the select box.
		} 
	},
	// set and display a child select box of JuNii2. 
	// elem : parent select box
	// m : m-th row
	// n : select-index of parent select box 
	setJunii2ChildItem: function(elem, m, n) {
		// find parent table of parent select box.(very dorokusai) 
		var elem_table = elem.parentNode.parentNode.parentNode.parentNode;
		var elem_sels = elem_table.getElementsByTagName("select");
		var elem_sel = elem_sels[3*m+2];			// yes, stump the select node!
		// check index (has child or not.)
		this.setJunii2Child(elem_sel, n);
	},
	// set and display child select boxis of JuNii2. 
	// elem : top document
	setJunii2ChildItemAll: function(doc) {
		// find all "junii2_parent[]" objects(=select)
		var elem_selects = doc.getElementsByTagName("select");
		var elem_parents = new Array();
		var elem_children = new Array();
		var len = elem_selects.length;
		for(i=0; i<len; i++) {
			var name = elem_selects[i].getAttributeNode("name").nodeValue;
			switch(name){
			case "junii2_parent[]":
				elem_parents.push(elem_selects[i]);
				break;
			case "junii2_child[]":
				elem_children.push(elem_selects[i]);
				break;
			}		
		}		
		// select box loop
		var len_sel = elem_parents.length;
		for (j=0; j<len_sel; j++){
			var selectedIndex = elem_parents[j].selectedIndex
			var child = elem_children[j];
			// why disabled? Oh,"this" is required, maybe. 
			this.setJunii2Child(child, selectedIndex);
		}
	},
	// Add show other menu when edit item_type 2013/09/12 T.Ichikawa --start--
	// When be selected "text", "textarea", "link", "checkbox", "radio", "select", "date", or "heading"
	setSelectItemEditVisible: function(selIdx,rowNo, error_msg) {
		var required = "required"+rowNo;
		var plural = "plural"+rowNo;
		var plural_display = "plural_display"+rowNo;
		var plural_dummy = "plural_dummy"+rowNo;
		var disp = "disp"+rowNo;
		var newline = "newline"+rowNo;
		var newline_display = "newline_display"+rowNo;
		var newline_dummy = "newline_dummy"+rowNo;
		var hidden = "hidden"+rowNo;
		
		var elem_required = document.getElementById(required);
		var elem_plural = document.getElementById(plural);
		var elem_plural_display = document.getElementById(plural_display);
		var elem_plural_dummy = document.getElementById(plural_dummy);
		var elem_disp = document.getElementById(disp);
		var elem_newline = document.getElementById(newline);
		var elem_newline_display = document.getElementById(newline_display);
		var elem_newline_dummy = document.getElementById(newline_dummy);
		var elem_hidden = document.getElementById(hidden);
		
		var chk_heading = document.getElementById("chk_heading");
		if(chk_heading.value == rowNo){
			chk_heading.value = "";
		}
		
		// candidata 
		if( 3<=selIdx && selIdx<=5)
		{
			$('candidate_'+rowNo).style.display = "";
			elem_required.disabled = false;
			elem_hidden.disabled = false;
			// plural disabled to true
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			if(elem_hidden.checked != true){
				elem_disp.disabled = false;
				elem_newline.disabled = false;
			}
		}
		// heading
		else if( selIdx == 7 ){
			var pul_box = document.getElementById("type"+rowNo);
			if(chk_heading.value!=""){
				alert(error_msg[2]);
				pul_box.selectedIndex = 0;
				this.setSelectItemVisible(1, rowNo);
				return false;
			}
			$('candidate_'+rowNo).style.display = "none";
			chk_heading.value = rowNo;
			elem_required.disabled = false;
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_disp.disabled = false;
			elem_newline.disabled = false;
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			elem_hidden.disabled = false;
		}
		else
		{
			elem_hidden.disabled = false;
			elem_required.disabled = false;
			$('candidate_'+rowNo).style.display = "none";
			// plural and newline disabled to false
			elem_plural.disabled = false;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
			if(elem_hidden.checked != true){
				elem_disp.disabled = false;
				elem_newline.disabled = false;
			}
		}
	},
	// When be selected "file" or "file_price" 
	setSelectFileVisible: function(selIdx,rowNo, error_msg) {
		var required = "required"+rowNo;
		var plural = "plural"+rowNo;
		var plural_display = "plural_display"+rowNo;
		var plural_dummy = "plural_dummy"+rowNo;
		var disp = "disp"+rowNo;
		var newline = "newline"+rowNo;
		var newline_display = "newline_display"+rowNo;
		var newline_dummy = "newline_dummy"+rowNo;
		var hidden = "hidden"+rowNo;
		
		var elem_required = document.getElementById(required);
		var elem_plural = document.getElementById(plural);
		var elem_plural_display = document.getElementById(plural_display);
		var elem_plural_dummy = document.getElementById(plural_dummy);
		var elem_disp = document.getElementById(disp);
		var elem_newline = document.getElementById(newline);
		var elem_newline_display = document.getElementById(newline_display);
		var elem_newline_dummy = document.getElementById(newline_dummy);
		var elem_hidden = document.getElementById(hidden);
		
		var chk_fileprice = document.getElementById("chk_fileprice");
		if(chk_fileprice.value == rowNo){
			chk_fileprice.value = "";
		}
		
		// file
		if( selIdx == 0 ){
			$('candidate_'+rowNo).style.display = "none";
			elem_required.disabled = false;
			elem_hidden.disabled = false;
			elem_plural.disabled = false;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			// newline disabled
			if(elem_hidden.checked == true){
				elem_newline.checked = false;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			} else {
				elem_newline.checked = true;
				elem_newline_display.style.display = "none";
				elem_newline_dummy.style.display = "";
				elem_disp.disabled = false;
			}
		}
		// file_price
		else if(selIdx == 1){
			var pul_box = document.getElementById("type"+rowNo);
			if(chk_fileprice.value!=""){
				alert(error_msg[0]);
				pul_box.selectedIndex = 0;
				this.setSelectItemVisible(1, rowNo);
				return false;
			}
			$('candidate_'+rowNo).style.display = "none";
			chk_fileprice.value = rowNo;
			// newline disabled
			elem_required.disabled = false;
			elem_plural.checked = false;
			elem_plural.disabled = true;
			elem_plural_display.style.display = "";
			elem_plural_dummy.style.display = "none";
			// newline disabled
			if(elem_hidden.checked == true){
				elem_newline.checked = false;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			} else {
				elem_newline.checked = true;
				elem_newline_display.style.display = "none";
				elem_newline_dummy.style.display = "";
				elem_disp.disabled = false;
			}
		}
	},
	// Add show other pulldown menu when edit itemtype 2013/09/12 T.Ichikawa --end--
	// change index tree view action 2008/11/28 Y.Nakao --start--
	searchOpenNode: function(opening_id) {
		return "";
		/*
		var str;
		var node;
		var firstFlg = true;
		var len = snipetTree.aNodes.length;
		for (var i=0; i<len; i++ ) {
			node = snipetTree.aNodes[i];
			if (node._io == true || node.id == opening_id) {
				if (firstFlg == true) {
					str = node.id;
					firstFlg = false;
				} else {
					str = str + "," + node.id;
				}
			}
		}
		return str;
		*/
	},
	// Generate Opening Index IDs, for ItemEditText.
	searchOpenNodeItemIndex: function() {
	 	var str = "";
	 	var node;
	 	var firstFlg = true;
	 	var len = itemIndexTree.aNodes.length;
	 	for (var i=0; i<len; i++ ) {
	 		node = itemIndexTree.aNodes[i];
	 		if (node._io == true) {
	 			if (firstFlg == true) {
	 				str = node.id;
	 				firstFlg = false;
	 			} else {
	 				str = str + "," + node.id;
	 			}
	 		}
	 	}
		return str;
	},
	// Generate Checked Index IDs, for ItemEditText.
	searchCheckedNodeItemIndex: function() {
		var selected = itemIndexTree.getCheckedNodes();    	
    	var str = "";
    	var len=selected.length;
    	var firstFlg = true;
    	for(var i=0;i<len;i++){
   			if (firstFlg == true) {
 				str = selected[i].id;
 				firstFlg = false;
 			} else {
 				str = str + "|" + selected[i].id;
 			}
    	}
    	return str;
	},
	// Generate Checked Index Names, for ItemEditText.
	searchCheckedNodeNameItemIndex: function() {
		var selected = itemIndexTree.getCheckedNodes();    	
    	var str = "";
    	var len=selected.length;
    	var firstFlg = true;
    	for(var i=0;i<len;i++){
   			if (firstFlg == true) {
 				str = selected[i].name;
 				firstFlg = false;
 			} else {
 				str = str + "|" + selected[i].name;
 			}
    	}
    	return str;
	},
	// SearchKeyword & Goto ItemLink (S.Kawasaki)
	sendSearchKeywordItemLink: function(mode,target,keyword) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		var opening_ids = 0;
		// if "directory_search", get opening index sata
		if( mode == "index_search" ) {
			// change index tree view action 2008/12/03 Y.Nakao --start--
			//opening_ids = this.searchOpenNodeItemLink(target);
			// change index tree view action 2008/12/03 Y.Nakao --end--
		}
		// Send FormData & argument
		params["param"] = 	"action=repository_action_main_item_linkact" + "&" +
							Form.serialize(form) + "&" + 
							"keychange_Flg=true" + "&" + 
							"index_search_Flg=false" + "&" + 
							"save_mode=" + mode + "&" + 
							"target=" + target + "&" + 
							"keyword=" + keyword + "&" + 
							"CheckedIds=" + $('check_insert_idx').value + "&" + 
							"CheckedNames=" + $('check_insert_idx_name').value;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		// callback : action => view => template => callback...
		commonCls.send(params);
	},
	// Generate Opening Index IDs, for ItemEditLink.
	searchOpenNodeItemLink: function(opening_id) {
	 	var str = "";
	 	var node;
	 	var firstFlg = true;
	 	var len = linkTree.aNodes.length;
	 	for (var i=0; i<len; i++ ) {
	 		node = linkTree.aNodes[i];
	 		if (node._io == true || node.id == opening_id) {
	 			if (firstFlg == true) {
	 				str = node.id;
	 				firstFlg = false;
	 			} else {
	 				str = str + "," + node.id;
	 			}
	 		}
	 	}
		return str;
	},
	// Go Export or Print Field nakao 2008/03/13
	goExportORPrint: function(selValue, item_num, url, page_id, block_id){
		
		var top_el = $(this.id);
		var form = document.getElementById('export_print');
		var params = new Object();
		var open_node_id = this.searchOpenNode(null);
		params["method"] = "post";
		// Send FormData & argument
		if( selValue == "check_Export" ){	
		
			params["param"] = "action=repository_action_main_export_list" + 
							  "&"+ Form.serialize(form) + 
							  "&check_flg=true";
			params["top_el"] = top_el;
			params["loading_el"] = top_el;
			params["target_el"] = top_el;
			commonCls.send(params);
		} else if ( selValue == "view_Export" ){
			params["param"] = "action=repository_action_main_export_list" + 
							  "&check_flg=false";
			params["top_el"] = top_el;
			params["loading_el"] = top_el;
			params["target_el"] = top_el;
			commonCls.send(params);
		} else if( selValue == "check_print" ){
			//Add html style correction 2009/09/09 K.Ito --start--
			/*
			//選択チェック
			var nCount = 0;
			for(ii=0;ii<item_num;ii++){
				checkbox_id = "check_" + ii;
				var checkelem1 = document.getElementById(checkbox_id);
				if(checkelem1.checked == true){
					nCount = nCount + 1;
				}
			}
			if(nCount == 0){
				alert("選択なし");
				quit();
			}
			*/
			
			var newWin=window.open(url);
			
			var head=document.getElementsByTagName('head');
			newWin.document.open();
			newWin.document.write('<head>');
			newWin.document.write(head[0].innerHTML);
			newWin.document.write('</head>');
			newWin.document.write('<body style="margin-top: 50px; margin-bottom: 50px;" onload="javascript:window.print();">');
			newWin.document.write('<center>');
			newWin.document.write('<table class="list_line_repos2" style="text-align: center;" summary=""><tr><td>');
			newWin.document.write('<center>');
			//var print_str_start = $('print_area_list_start').innerHTML;
			//newWin.document.write(print_str_start);
			
			var d_Now = new Date();
			var d_Day = d_Now.getDate();
			var d_Year = d_Now.getFullYear();
			var d_Mon = d_Now.getMonth();
			var Mon = new Array(12);
			Mon[0] = 'Jan';
			Mon[1] = 'Feb';
			Mon[2] = 'Mar';
			Mon[3] = 'Apr';
			Mon[4] = 'May';
			Mon[5] = 'Jun';
			Mon[6] = 'Jul';
			Mon[7] = 'Aug';
			Mon[8] = 'Sep';
			Mon[9] = 'Oct';
			Mon[10] = 'Nov';
			Mon[11] = 'Dec';
			var d_Day = d_Now.getDate();
			var d_Hour = d_Now.getHours();
			var d_Min = d_Now.getMinutes();
			var d_MSec = d_Now.getMilliseconds();
			str_date = Mon[d_Mon] + " " + d_Day + ", " + d_Year + " " + d_Hour + ":"+d_Min + ":" + d_MSec;
			
			//print_str_start = $('search').innerHTML;
			//newWin.document.write(print_str_start);
			newWin.document.write('<div style="margin-left: 40px; margin-right: 40px;">');
			newWin.document.write('<table border="0"><tbody><tr><td>');
			newWin.document.write('<div style="text-align: left; margin-bottom: 10px; margin-top: 20px;">');
			newWin.document.write("<font size='1'>" + str_date + "</font>");
			newWin.document.write("</div>");
			
			newWin.document.write("<br>");
			
			//テキスト背景色
            // Add design adjustment 2012/02/06 T.Koyasu -start-
            // set gradation of title bar
			newWin.document.write('<div class="th_repos_title_bar text_color">');
			
			print_str_start = $('search_print_order').innerHTML;
			//50pxを修正
            print_str_start = print_str_start.replace(/width="50px"/ig, 'width="100px"');
            print_str_start = print_str_start.replace(/width=50px/ig, 'width="100px"');
			//テキスト色をクロに ・横幅修正
            print_str_start = print_str_start.replace(/width="590px"/ig, 'width="610px" class="text_color"');
            print_str_start = print_str_start.replace(/width=590px/ig, 'width="610px" class="text_color"');
			//リンク除去
            // use replace + regexp
			print_str_start = print_str_start.replace(/href/ig, 'name');
			print_str_start = print_str_start.replace(/<a/ig, '<meta class="text_color"');
			print_str_start = print_str_start.replace(/<\/a>/ig, '</meta>');

            // set table width = 95%
            print_str_start = print_str_start.replace(/cellspacing="0"/i,'cellspacing="0" style="width:95%"');
            print_str_start = print_str_start.replace(/cellspacing=0/i,'cellspacing="0" style="width:95%"');
            
            // remove item number
            print_str_start = print_str_start.replace(/class="item_num c"/ig, 'style="display:none"')

			newWin.document.write(print_str_start);
			newWin.document.write("</div><br>");
            // Add design adjustment 2012/02/06 T.Koyasu -end-
			
			//テーブルでアイテムを囲む
			newWin.document.write('<table class="text_color"><tbody>');
			
			//色判定用
			var nNum = 0;
			//チェックしたアイテム
			for(ii=0;ii<item_num;ii++){
				checkbox_id = "check_" + ii;
				var checkelem = document.getElementById(checkbox_id);
				if(checkelem.checked == true){
                    // Add design adjustment 2012/02/06 T.Koyasu -start-
                    // remove background-color and item type icon in left side
					//背景色分けの判定
					newWin.document.write('<tr>');

					newWin.document.write('<td style="width: 590px;">');
					newWin.document.write('<div class="paging2">');
					
					var id_str = "print_area_list_" + ii;
					var print_str = $(id_str).innerHTML;
					print_str = print_str.replace(/href/ig,'name');
					print_str = print_str.replace(/onmouseover/ig,'');
					print_str = print_str.replace(/onclick/ig,'');
					print_str = print_str.replace(/onmouseout/ig,'');
					
                    // remove check box
                    print_str = print_str.replace(/type="checkbox"/ig,'style="display:none;"');
                    print_str = print_str.replace(/class="list_chk_repos"/ig, 'style="display:none"');
                    print_str = print_str.replace(/type=checkbox/ig,'style="display:none;"');
                    print_str = print_str.replace(/class=list_chk_repos/ig, 'style="display:none"');
                    
                    // change padding of item title
                    print_str = print_str.replace(/pl55/ig, 'bold pl40');
                    
                    // remove link and set border
                    print_str = print_str.replace(/<a/ig, '<span');
                    print_str = print_str.replace(/<\/a>/ig, '</span>');
                    print_str = print_str.replace(/class=list_mimetype_icon/ig, 'class="brdl01 brdt01 brdb01 brdr01 mr05 ptb02"');
                    print_str = print_str.replace(/class="list_mimetype_icon"/ig, 'class="brdl01 brdt01 brdb01 brdr01 mr05 ptb02"');
                    
                    // metadata go left
                    print_str = print_str.replace(/class="list_attr_repos pl10"/ig, 'class="list_attr_repos pl40"');
                    // Add design adjustment 2012/02/06 T.Koyasu -end-

                    print_str = print_str.replace(/cursor : pointer/ig, '');
                    print_str = print_str.replace(/cursor: pointer/ig, '');
					newWin.document.write(print_str);
					nNum++;
					
					newWin.document.write("</div></td></tr>");
				}
			}
			//フッター取得とリンク除去
			var footer1 = $('send_footer').value;
			footer1 = footer1.split('<a').join('<font size="1"');
			footer1 = footer1.split('</a>').join('</font>');
			footer1 = footer1.split('href=').join('name=');
			footer1 = footer1.split('class="link"').join('');
			footer1 = footer1.split('target="_blank"').join('');
			
			//テーブルを閉じる
			newWin.document.write("</tbody></table>");
			newWin.document.write("</td></tr></tbody></table>");
			newWin.document.write('<div style="text-align: right; margin-top: 20px; margin-bottom: 20px;">');
			//フッター書き込み
			newWin.document.write('<div class="copyright"><font size="1">');
			newWin.document.write(footer1);
			newWin.document.write('</font></div>');
			//newWin.document.write('<br /><P><font size="1">Powered by NetCommons2.0 The The NetCommons Project,</font></P><br />');
			newWin.document.write("</div>");
			newWin.document.write("</div>");
			newWin.document.write('</center>');
			newWin.document.write("</td></tr></tbody></table>");
			newWin.document.write('</center>');
			newWin.document.write('</body>');
			//Add html style correction 2009/09/09 K.Ito --end--
			newWin.document.close();
			eval();
				
		} else if( selValue == "view_print" ){
			var newWin=window.open(url);
			
			var head=document.getElementsByTagName('head');
			newWin.document.open();
			newWin.document.write('<head>');
			newWin.document.write(head[0].innerHTML);
			newWin.document.write('</head>');
			//Add html style correction 2009/09/09 K.Ito --start--
			newWin.document.write('<body style="margin-top: 50px; margin-bottom: 50px;" onload="javascript:window.print();">');
			//newWin.document.write('<br><br>');
			newWin.document.write('<center>');
			newWin.document.write('<table class="list_line_repos2" style="text-align: center;" summary=""><tr><td>');
			newWin.document.write('<center>');
			
			var d_Now = new Date();
			var d_Day = d_Now.getDate();
			var d_Year = d_Now.getFullYear();
			var d_Mon = d_Now.getMonth();
			var Mon = new Array(12);
			Mon[0] = 'Jan';
			Mon[1] = 'Feb';
			Mon[2] = 'Mar';
			Mon[3] = 'Apr';
			Mon[4] = 'May';
			Mon[5] = 'Jun';
			Mon[6] = 'Jul';
			Mon[7] = 'Aug';
			Mon[8] = 'Sep';
			Mon[9] = 'Oct';
			Mon[10] = 'Nov';
			Mon[11] = 'Dec';
			var d_Day = d_Now.getDate();
			var d_Hour = d_Now.getHours();
			var d_Min = d_Now.getMinutes();
			var d_MSec = d_Now.getMilliseconds();
			str_date = Mon[d_Mon] + " " + d_Day + ", " + d_Year + " " + d_Hour + ":"+d_Min + ":" + d_MSec;
			
			//var print_str_start = $('print_area_list_start').innerHTML;
			//余白 40px
			newWin.document.write('<div style="margin-left: 40px; margin-right: 40px;">');
			newWin.document.write('<table border="0"><tbody><tr><td>');
			newWin.document.write('<div style="text-align: left; margin-bottom: 10px; margin-top: 20px;">');
			newWin.document.write("<font size='1'>" + str_date + "</font>");
			newWin.document.write("</div>");
			
			//print_str_start = $('search').innerHTML;
			//newWin.document.write(print_str_start);
			
			newWin.document.write("<br>");
			
            // Add design adjustment 2012/02/06 T.Koyasu -start-
            // set gradation image to title bar
			//テキスト背景色
			newWin.document.write('<div class="th_repos_title_bar text_color">');
			print_str_start = $('search_print_order').innerHTML;
			
			//テキスト色をクロに ・横幅修正
            print_str_start = print_str_start.replace(/width="590px"/ig, 'width="610px" class="text_color"');
            print_str_start = print_str_start.replace(/width=590px/ig, 'width="610px" class="text_color"');
			//50pxを修正
            print_str_start = print_str_start.replace(/width="50px"/ig, 'width="100px"');
            print_str_start = print_str_start.replace(/width=50px/ig, 'width="100px"');
			//リンク除去
            print_str_start = print_str_start.replace(/href/ig, 'name');
            print_str_start = print_str_start.replace(/<a/ig, '<meta class="text_color"');
            print_str_start = print_str_start.replace(/<\/a>/ig, '</meta>');
			
            // set table width = 95%
            print_str_start = print_str_start.replace(/cellspacing="0"/i,'cellspacing="0" style="width:95%"');
            print_str_start = print_str_start.replace(/cellspacing=0/i,'cellspacing="0" style="width:95%"');
			
			newWin.document.write(print_str_start);	
			newWin.document.write("</div><br>");
			
			//テーブルでアイテムを囲む
			newWin.document.write('<table class="text_color"><tbody>');
			//全部アイテム
			for(ii=0;ii<item_num;ii++){
                // Add design adjustment 2012/02/06 T.Koyasu -start-
                // remove background color and item type icon in left side
                newWin.document.write('<tr>');
                
				//newWin.document.write('<td class="list_itemtype_icon" style="width: 35px; margin-left: 10px;"><div style="text-align: right;"><img height="16px" width="16px" src="images/repository/tree/item.png"/></div></td>')
				newWin.document.write('<td style="width: 590px;">');
				newWin.document.write('<div class="paging2">');
				var id_str = "print_area_list_" + ii;
				var print_str = $(id_str).innerHTML;
				//alert(print_str);
                print_str = print_str.replace(/href/ig,'name');
                print_str = print_str.replace(/onmouseover/ig,'');
                print_str = print_str.replace(/onclick/ig,'');
                print_str = print_str.replace(/onmouseout/ig,'');
				//print_str = print_str.replace(/<a/ig, '<div');
				//print_str = print_str.replace(/<\/a>/ig, '</div>');
				//print_str = print_str.replace(/target="_blank"/ig, '');
				
                // remove link cursor
                print_str = print_str.replace(/cursor : pointer; text-decoration: underline;/ig, '');
                print_str = print_str.replace(/cursor: pointer; text-decoration: underline;/ig, '');
                print_str = print_str.replace(/cursor : pointer;/ig, '');
                print_str = print_str.replace(/cursor: pointer;/ig, '');
                
                // remove check box
                print_str = print_str.replace(/type="checkbox"/ig,'style="display:none;"');
                print_str = print_str.replace(/class="list_chk_repos"/ig, 'style="display:none"');
                print_str = print_str.replace(/type=checkbox/ig,'style="display:none;"');
                print_str = print_str.replace(/class=list_chk_repos/ig, 'style="display:none"');
                
                // change padding of item title
                print_str = print_str.replace(/pl55/ig, 'bold pl40');
                
                // remove link and set border
                print_str = print_str.replace(/<a/ig, '<span');
                print_str = print_str.replace(/<\/a>/ig, '</span>');
                print_str = print_str.replace(/class=list_mimetype_icon/ig, 'class="brdl01 brdt01 brdb01 brdr01 mr05 ptb02"');
                print_str = print_str.replace(/class="list_mimetype_icon"/ig, 'class="brdl01 brdt01 brdb01 brdr01 mr05 ptb02"');
                
                // metadata go left
                print_str = print_str.replace(/class="list_attr_repos pl10"/ig, 'class="list_attr_repos pl40"');
                
                print_str = print_str.replace(/cursor : pointer/ig, '');
                print_str = print_str.replace(/cursor: pointer/ig, '');
                // Add design adjustment 2012/02/06 T.Koyasu -end-
				
				newWin.document.write(print_str);

				newWin.document.write("</div></td></tr>");	
			}
			
			//フッター取得とリンク除去
			var footer1 = $('send_footer').value;
			footer1 = footer1.split('<a').join('<font size="1"');
			footer1 = footer1.split('</a>').join('</font>');
			footer1 = footer1.split('href=').join('name=');
			footer1 = footer1.split('class="link"').join('');
			footer1 = footer1.split('target="_blank"').join('');

			//テーブルを閉じる
			newWin.document.write("</tbody></table>");
			newWin.document.write("</td></tr></tbody></table>");
			//newWin.document.write('<br /><P style="font-size: 8px;">Powered by NetCommons2.0 The The NetCommons Project</P><br />');
			newWin.document.write('<div style="text-align: right; margin-top: 20px; margin-bottom: 20px;">');
			//フッター書き込み
			newWin.document.write('<div class="copyright"><font size="1">');
			newWin.document.write(footer1);
			newWin.document.write('</font></div>');
			//newWin.document.write('<br /><P><font size="1">Powered by NetCommons2.0 The The NetCommons Project,</font></P><br />');
			newWin.document.write("</div>");
			newWin.document.write("</div>");
			newWin.document.write('</center>');
			newWin.document.write("</td></tr></tbody></table>");
			newWin.document.write('</center>');
			newWin.document.write('</body>');
			//Add html style correction 2009/09/09 K.Ito --end--
			newWin.document.close();
			eval();
		// Add all export and all print 2010/07/21 A.Suzuki --start--
		} else if( selValue == "all_Export" ){
			// all Export
			params["param"] = "action=repository_action_main_export_list" + 
							  "&all_flg=true";
			params["top_el"] = top_el;
			params["loading_el"] = top_el;
			params["target_el"] = top_el;
			commonCls.send(params);
		} else if( selValue == "all_print" ){
			// all print
			var newWin=window.open(url);
            var head=document.getElementsByTagName('head');
            newWin.document.open();
            newWin.document.write('<head>');
            newWin.document.write(head[0].innerHTML);
            newWin.document.write('</head>');
            newWin.document.write('<body class="text_color_in_print" style="margin-top: 50px; margin-bottom: 50px;" onload="javascript:window.print();">');
            newWin.document.write('<div class="pt20">');
            newWin.document.write('<center>');
            
            selectedLang = document.getElementById('header_menu').summary;
            if(selectedLang.match(/header/ig))
            {
                newWin.document.write('Please wait.');
                newWin.document.write('<br>');
            }
            else
            {
                newWin.document.write('印刷の準備中です。');
                newWin.document.write('<br>');
                newWin.document.write('しばらくお待ちください');
            }
            newWin.document.write('</center>');
            newWin.document.write('</div>');
            newWin.document.write('</body>');

			var pars="action=repository_action_main_print";
			pars += '&all_print=true';
			pars += '&' + Form.serialize(form);
			pars += '&page_id='+page_id;
	  		pars += '&block_id='+block_id;
			var tmpurl = _nc_base_url + "/index.php";		// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
			
			var myAjax = new Ajax.Request(
							tmpurl,
							{
								method: 'get',
								parameters: pars, 
								onFailure : function(){
                                    newWin.document.close();
								},
								onSuccess : function(res){
									// set input html text data
									str = '<center>';
									str += '<table class="list_line_repos2" style="text-align: center;" summary=""><tr><td>';
									str += '<center>';

									var d_Now = new Date();
									var d_Day = d_Now.getDate();
									var d_Year = d_Now.getFullYear();
									var d_Mon = d_Now.getMonth();
									var Mon = new Array(12);
									Mon[0] = 'Jan';
									Mon[1] = 'Feb';
									Mon[2] = 'Mar';
									Mon[3] = 'Apr';
									Mon[4] = 'May';
									Mon[5] = 'Jun';
									Mon[6] = 'Jul';
									Mon[7] = 'Aug';
									Mon[8] = 'Sep';
									Mon[9] = 'Oct';
									Mon[10] = 'Nov';
									Mon[11] = 'Dec';
									var d_Day = d_Now.getDate();
									var d_Hour = d_Now.getHours();
									var d_Min = d_Now.getMinutes();
									var d_MSec = d_Now.getMilliseconds();
									str_date = Mon[d_Mon] + " " + d_Day + ", " + d_Year + " " + d_Hour + ":"+d_Min + ":" + d_MSec;
									
									//余白 40px
                                    str += '<div style="margin-left: 40px; margin-right: 40px;">';
                                    str += '<table border="0"><tbody><tr><td>';
                                    str += '<div style="text-align: left; margin-bottom: 10px; margin-top: 20px; color: #494949;">';
                                    str += "<font size='1'>" + str_date + "</font>";
                                    str += "</div>";
                                    str += "<br>";
                                    
									//テキスト背景色
									//newWin.document.write('<div class="th_repos text_color">');
									
									// アイテムリストバーとテーブル生成
                                    str += res.responseText;
									
									//テーブルを閉じる
                                    str += "</td></tr></tbody></table>";
									
                                    str += '<div style="text-align: right; margin-top: 20px; margin-bottom: 20px;">';
									//フッター書き込み
                                    str += '<div class="copyright"><font size="1">';
                                    
									//フッター取得とリンク除去
									var footer1 = $('send_footer').value;
									footer1 = footer1.split('<a').join('<font size="1"');
									footer1 = footer1.split('</a>').join('</font>');
									footer1 = footer1.split('href=').join('name=');
									footer1 = footer1.split('class="link"').join('');
									footer1 = footer1.split('target="_blank"').join('');
									
                                    str += footer1;
                                    str += '</font></div>';
                                    str += "</div>";
                                    str += "</div>";
                                    str += '</center>';
                                    str += "</td></tr></tbody></table>";
                                    str += '</center>';

                                    newWin.document.body.innerHTML = str;
									
									newWin.document.close();
									eval();
								},
								onComplete: function(res) {
								}
							}
						);
		} else if( selValue == "contens_all_print" ){
			// all print for contets page
			var newWin=window.open(url);
			
			var head=document.getElementsByTagName('head');
			newWin.document.open();
			newWin.document.write('<head>');
			newWin.document.write(head[0].innerHTML);
			newWin.document.write('</head>');
			newWin.document.write('<body style="margin-top: 50px; margin-bottom: 50px; background-color: #ffffff;" class="text_color" onload="javascript:window.print();">');
			
			var d_Now = new Date();
			var d_Day = d_Now.getDate();
			var d_Year = d_Now.getFullYear();
			var d_Mon = d_Now.getMonth();
			var Mon = new Array(12);
			Mon[0] = 'Jan';
			Mon[1] = 'Feb';
			Mon[2] = 'Mar';
			Mon[3] = 'Apr';
			Mon[4] = 'May';
			Mon[5] = 'Jun';
			Mon[6] = 'Jul';
			Mon[7] = 'Aug';
			Mon[8] = 'Sep';
			Mon[9] = 'Oct';
			Mon[10] = 'Nov';
			Mon[11] = 'Dec';
			var d_Day = d_Now.getDate();
			var d_Hour = d_Now.getHours();
			var d_Min = d_Now.getMinutes();
			var d_MSec = d_Now.getMilliseconds();
			str_date = Mon[d_Mon] + " " + d_Day + ", " + d_Year + " " + d_Hour + ":"+d_Min + ":" + d_MSec;
			str_date = '<div style="width:610px;text-align:left;">'+"<font size='1'>" + str_date + "</font>"+'</div>'

            // Add design adjustment 2012/02/07 T.Koyasu -start-
			// setting html
			print_str_start = $('search_print_order').innerHTML;
			// delete link
			print_str_start = print_str_start.replace(/\<a /ig, '<span ');
			print_str_start = print_str_start.replace(/\<\/a\>/ig, '</span>');
			// wide width
			print_str_start = print_str_start.replace(/width="50px"/ig, 'width="100px"');
			// text color is black
			print_str_start = print_str_start.replace(/width="590px"/ig, 'width="610px" class="text_color"');
			print_str_start = print_str_start.replace(/repository_contents_table/ig, 'repository_contents_table');
            print_str_start = print_str_start.replace(/id="print_icon"/ig, 'style="display:none;"');
            print_str_start = print_str_start.replace(/id=print_icon/ig, 'style="display:none;"');
            
            // remove show index
            print_str_start = print_str_start.replace(/class="ws050 ptb20"/ig, 'style="display:none;"')
            print_str_start = print_str_start.replace(/class=ws050 ptb20/ig, 'style="display:none;"')
            
            // set border and color to file icon
            print_str_start = print_str_start.replace(/class="list_mimetype_icon"/ig, 'class="brd01"');
            print_str_start = print_str_start.replace(/class=list_mimetype_icon/ig, 'class="brd01"');
            
            // insert metadata bottom padding(5px)
            print_str_start = print_str_start.replace(/class="repository_contents_metadata pl02em"/ig, 'class="repository_contents_metadata list_attr_repos pt00 pr00 pl02em"');
            print_str_start = print_str_start.replace(/class=repository_contents_metadata pl02em/ig, 'class="repository_contents_metadata list_attr_repos pt00 pr00 pl02em"');
            
            // remove cursor pointer
            print_str_start = print_str_start.replace(/cursor : pointer/ig, '');
            print_str_start = print_str_start.replace(/cursor: pointer/ig, '');
            
            // insert under line in heading
            print_str_start = print_str_start.replace(/class="panelbar large text_color mt10"/ig, 'class="th_repos_title_bar large text_color mt10 brdb03"');
            
			// setting body
            newWin.document.write('<center>');

            // get footer and remove link
            var footer1 = $('send_footer').value;
            footer1 = footer1.replace(/<a/ig, '<font size="1"');
            footer1 = footer1.replace(/<\/a>/ig, '</font>');
            footer1 = footer1.replace(/href=/ig, 'name=');
            footer1 = footer1.replace(/class="link"/ig, '');
            footer1 = footer1.replace(/target="_blank"/ig, '');
            
            // set div of foter
            footer1 = '<div class="ar mt20 mb20"><div class="copyright"><font size="1">' + footer1 + '</font></div></div>';
            
            // insert print date
            newWin.document.write(str_date);

            // set print width
            newWin.document.write('<div style="width:610px; margin-top: 20px; margin-bottom:10px">');
			newWin.document.write(print_str_start);
			newWin.document.write(footer1);
			newWin.document.write('</div></center>');
            // Add design adjustment 2012/02/07 T.Koyasu -end-
			
			newWin.document.write('</body>');
			
			// close
			newWin.document.close();
			eval();
		}
		// Add all export and all print 2010/07/21 A.Suzuki --end--
	},
	/*
	//Add replaceAll for print 2009/09/09 K.Ito --start--
	replaceAll: function( _targetStr_, _searchStr_, _replaceStr_ ){ 
		return _targetStr_.split(_searchStr_).join(_replaceStr_);
	},
	//Add replaceAll for print 2009/09/09 K.Ito --end--
	*/
	// Show Detail Print
	repositoryGoDetailPrint: function(url){
		var newWin=window.open(url);
		
		var head=document.getElementsByTagName('head');
		//newWin.document.open();
		newWin.document.write('<head>');
		newWin.document.write(head[0].innerHTML);
		
		//Add html style correction 2009/09/09 K.Ito --start--
		newWin.document.write('</head>');
		newWin.document.write('<body style="margin-top: 50px; margin-bottom: 50px;" onload="javascript:window.print();">');
		//newWin.document.write('<br><br>');
		newWin.document.write('<CENTER>');
		newWin.document.write('<table class="list_line_repos2" style="text-align: center; width: 600px;" summary=""><tr><td>');
		newWin.document.write('<CENTER>');
		newWin.document.write('<div style="margin-left: 15px; margin-right: 15px; width: 580px;">');
		var print_str = $('print_area_detail_start').innerHTML;
		newWin.document.write(print_str);
		
		var d_Now = new Date();
		var d_Day = d_Now.getDate();
		var d_Year = d_Now.getFullYear();
		var d_Mon = d_Now.getMonth();
		var Mon = new Array(12);
		Mon[0] = 'Jan';
		Mon[1] = 'Feb';
		Mon[2] = 'Mar';
		Mon[3] = 'Apr';
		Mon[4] = 'May';
		Mon[5] = 'Jun';
		Mon[6] = 'Jul';
		Mon[7] = 'Aug';
		Mon[8] = 'Sep';
		Mon[9] = 'Oct';
		Mon[10] = 'Nov';
		Mon[11] = 'Dec';
		var d_Day = d_Now.getDate();
		var d_Hour = d_Now.getHours();
		var d_Min = d_Now.getMinutes();
		var d_MSec = d_Now.getMilliseconds();
		str_date = Mon[d_Mon] + " " + d_Day + ", " + d_Year + " " + d_Hour + ":"+d_Min + ":" + d_MSec;
		
		newWin.document.write('<div style="text-align: left; margin-left: 5px;">');
		newWin.document.write("<br><font size='1'>" + str_date + "</font><br>");
		newWin.document.write("</div>");
		
		//アイテムの情報とリンク除去
		var print_str = $('print_area_detail').innerHTML;
		print_str = print_str.replace('style="padding: 5px;','class="text_color" style="padding: 5px;');
		print_str = print_str.replace('href=','name=');
		print_str = print_str.replace('<input type="button".*">', '');
		print_str = print_str.split('onClick=').join('name=');
		print_str = print_str.split('onclick=').join('name=');
		print_str = print_str.split('"center"').join('');
        print_str = print_str.replace(/cursor : pointer;/ig, '');
        print_str = print_str.replace(/cursor: pointer;/ig, '');
        print_str = print_str.replace(/cursor : pointer/ig, '');
        print_str = print_str.replace(/cursor: pointer/ig, '');
		print_str = print_str.replace(/<a/ig, '<span');
		print_str = print_str.replace(/<\/a>/ig, '</span>');
        print_str = print_str.replace(/type="button"/ig, 'type="hidden"');
        print_str = print_str.replace(/type=button/ig, 'type="hidden"');
		
		print_str = print_str.split('<tr style="bor').join('<tr class="text_color" style="bor');
		print_str = print_str.split('tb_detail_repos').join('tb_detail_repos text_color');

        // Add design adjustment 2012/02/06 T.Koyasu -start-
        // remove button of "add supplemental contents"
        print_str = print_str.replace(/<div class="pt05 pb05"/ig, '<div style="display:none"');
        
        // remove each output botton
        print_str = print_str.replace(/class="ar narrow fr mb10"/ig, 'style="display:none;"');
        
        // remove title show
        print_str = print_str.split('title=').join('dir=');
        
        print_str = print_str.replace(/<div class="ac"/ig, '<div class="mb10"');
        print_str = print_str.replace(/<div class=ac/ig, '<div class="mb10"');
        // Add design adjustment 2012/02/06 T.Koyasu -end-

		newWin.document.write(print_str);
		
		//フッター取得とリンク除去
		var footer1 = $('send_footer').value;
		footer1 = footer1.split('<a').join('<font size="1"');
		footer1 = footer1.split('</a>').join('</font>');
		footer1 = footer1.split('href=').join('name=');
		footer1 = footer1.split('class="link"').join('');
		footer1 = footer1.split('target="_blank"').join('');

		newWin.document.write('</div>');
		newWin.document.write('<div style="text-align: right; margin: 15px;">');
		newWin.document.write('<div class="copyright"><font size="1">');
		newWin.document.write(footer1);
		newWin.document.write('</font></div>');
		//newWin.document.write('<br /><P><font size="1">Powered by NetCommons2.0 The The NetCommons Project,</font></P><br />');
		
		newWin.document.write("</div>");
		newWin.document.write('</CENTER>');
		newWin.document.write('</td></tr></table>');
		newWin.document.write('</CENTER>');
		newWin.document.write('</body>');
		//Add html style correction 2009/09/09 K.Ito --end--
		newWin.document.close();
		eval();
		
	},
	// Review Result Relect nakao 2008/03/19
	repositoryGoReview: function(type, active_tab) {
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var topEl = $(this.id);				// get "modeule box" element.
		if(type == "item"){
			var forms = topEl.getElementsByTagName("form")[0];
		} else {
			var forms = topEl.getElementsByTagName("form")[1];
		}
		// choose target form "item_edit_text"
		var len = forms.length;
		for (i=0; i<len; i++) {
			if(forms[i].id == "item_edit_text") {
				// choose all form controls
				var children = forms[i].elements;
				var len2 = children.length;
				// choose "input (text, textarea, chkbox)" and
				for (j=0; j<len2; j++) {
					if(children[j].tagName == "INPUT" &&
					   (children[j].type == "text" || children[j].type == "textarea" )&&
					   children[j].value == "" ){
					   children[j].value = " ";		// hankaku space.
					}
				}
			}
		}
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		if(type == "item"){
			var form = top_el.getElementsByTagName("form")[0];
		} else {
			var form = top_el.getElementsByTagName("form")[1];
		}
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_review&type=" + type + "&review_active_tab=" + active_tab + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// add-s by okamoto 2008.03.04 
	repositoryGoMainExportList: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_export_list" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoMainExportDetailConfirm: function(prev_url) {
		var top_el = $(this.id);
		var form = document.getElementById('send_date');
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_export_detailconfirm&prev_url=" + prev_url + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryGoMainExportDetail: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_export_detail" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryImportSelect: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_edit_import_select" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryImportConfirm: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		// 2008/03/24 S.Kawasaki Get Checked Index IDs.
		// change index tree view action 2008/12/03 Y.Nakao --start--
		//var CheckedIds = this.searchCheckedNodeItemIndex(); 
		var CheckedIds =$('check_insert_idx').value;
		// change index tree view action 2008/12/03 Y.Nakao --end--
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_import_confirm" + "&"+
						  Form.serialize(form) + "&" +
						  "CheckedIds=" + CheckedIds;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryImportFileUpload: function() {
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		
		// call file upload action
		attachment_params['param'] = {"action":"repository_action_edit_import_upload"};

		// set call back function
		attachment_params['callbackfunc'] = this.repositoryImportConfirm.bind(this);
		
		attachment_params['timeout_flag'] = 0;
		commonCls.sendAttachment(attachment_params);
	},
	// e-person 2013/11/25 R.Matsuura --start--
	repositoryImportauthorityConfirm: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_importauthority_confirm" + "&"+
						  Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryImportauthorityFileUpload: function() {
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		
		// call file upload action
		attachment_params['param'] = {"action":"repository_action_edit_importauthority_upload"};
		// set call back function
		attachment_params['callbackfunc'] = this.repositoryImportauthorityConfirm.bind(this);
		
		attachment_params['timeout_flag'] = 0;
		commonCls.sendAttachment(attachment_params);
	},
	// e-person 2013/11/25 R.Matsuura --end--
	// change index tree for edit tree 2008/12/11 Y.Nakao --start--
	// Modify for use privatetree 2013/04/15 K.Matsuo --start--
	onSubmitEdittree_repos: function(param, returnHtml)
	{
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "get";
		// Send FormData & argument
		if(returnHtml == 'privateTree'){
			params["param"] = 'action=repository_action_main_privatetree'+param;
		} else {
			params["param"] = 'action=repository_action_edit_tree'+param;
		}
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Modify for use privatetree 2013/04/15 K.Matsuo --end--
	// change index tree for edit tree 2008/12/11 Y.Nakao --end--
	
	// Add set embargo action 2008/07/10 Y.Nakao --start--
	repositorySetEmbargo: function(selindex_id){
		var top_el = $(this.id);
		var params = new Object();
		var license_el = "licence_id_" + $('licence_id').selectedIndex;
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_item_embargo" + 
						  "&selindex_id=" + selindex_id + 
						  "&embargo_flag=" + $('embargo_flag_chk').value +
						  "&embargo_year=" + encodeURIComponent($('embargo_year').value) + 
						  "&embargo_month=" + ($('embargo_month').selectedIndex+1) + 
						  "&embargo_day=" + ($('embargo_day').selectedIndex+1) + 
						  "&license_id=" + $(license_el).value + 
						  "&licence_free_text=" + encodeURIComponent($('licence_free_text').value) + 
						  "&embargo_recursion=" + $('embargo_recursion').checked;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add set embargo action 2008/07/10 Y.Nakao --end--
	
	// Add set item type icon action 2008/07/16 Y.Nakao --start--
	repositorySetItemtypeIcon: function(){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var cnt = this.repositoryItemCountUpload(form);
		if(cnt == 0) {
			if($('del_icon_flg')){
				this.repositoryViewItemtypeConfirm($('del_icon_flg').value);
			} else {
				this.repositoryViewItemtypeConfirm(0);
			}
			return;
		}
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		attachment_params['param'] = {"action":"repository_action_edit_itemtype_uploadIcon"};

		if($('del_icon_flg')){
			attachment_params['callbackfunc'] = function(){this.repositoryViewItemtypeConfirm($('del_icon_flg').value);}.bind(this);
		} else {
			attachment_params['callbackfunc'] =  function(){this.repositoryViewItemtypeConfirm(0);}.bind(this);
		}
		commonCls.sendAttachment(attachment_params);
	},
	repositoryViewItemtypeConfirm: function(del_icon_flg){
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_itemtype_icon&del_icon_flg="+del_icon_flg;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add set item type icon action 2008/07/16 Y.Nakao --end--
	
	// set auto command path 2008/08/27 Y.Nakao --start--
	repositorySetcmdpath: function(){
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
            // choose all form controls
            var children = forms[i].elements;
            var len2 = children.length;
            // choose "input (text, textarea, chkbox)" and
            for (j=0; j<len2; j++) {
                if(
                    (
                        (
                            forms[i].id == "id_admin_site_license"
                            && children[j].tagName == "INPUT"
                            && (children[j].type == "text" || children[j].type == "textarea" )
                        )
                        || children[j].name == "harvesting_repositoryName[]"
                        || children[j].name == "harvesting_baseUrl[]"
                        || children[j].name == "harvesting_post_index[]"
                        || children[j].name == "harvesting_post_name[]"
                    )
                    && children[j].value == ""
                  )
                {
                    // hankaku space.
                    children[j].value = " ";
                }
            }
            form_data += "&" + Form.serialize(forms[i]);
		}
	
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_setcmdpath" + form_data;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
		
	},
	// set auto command path 2008/08/27 Y.Nakao --end--
	repositoryElsMapping: function(){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_cinii_els_mapping" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryElsMappingConfirm: function(){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_cinii_els_mappingconfirm" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryElsMappingAdddb: function(){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_cinii_els_mappingadddb" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// ELS Download & ELS Mapping Add 2008/08/25 Y.Nakao --end--
	
	// Add price file Y.Nakao 2008/08/29 --start--
	repositorySetPrice: function(setting_flg, i, j, p){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_setprice" + "&"+ Form.serialize(form) +
						  "&setting_flg=" + setting_flg +
						  "&target_row=" + i + "_" + j + "_" + p;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	}
	// Add price file Y.Nakao 2008/08/29 --end--
	,
	
	// Add download file from url 2008/10/08 Y.Nakao --start--
	repositoryDownloadLogin: function(event,download_file_info,page_no,shib_flg,version_flg,download_divergence_flg){
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		//params["method"] = "post";
		params["param"] = "action=repository_view_main_item_snippet" + "&download_file_info=" + download_file_info + "&page_no=" + page_no + "&download_divergence_flg=" + download_divergence_flg;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
        // Add version_flg and download_divergence_flg 2010/12/22 H.Goto --start--
		if(version_flg =='0'){
            params['callbackfunc'] = function(){
                                    if(shib_flg == '1'){
                                        location.href=_nc_base_url+'/index.php?action=login_view_main_init';	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
                                    } else {
                                        commonCls.sendPopupView(event,{'action':'login_view_main_init'}, {'center_flag':true,'modal_flag':true});
                                    }
                                }.bind(this);
        }else{
			if(shib_flg == '1'){
				params['callbackfunc'] = function(){location.href=_nc_base_url +'/index.php?action=login_view_main_init';}.bind(this);	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
			} else {
				params['callbackfunc'] = function(){
											commonCls.displayVisible($('login_popup'));
											commonCls.sendPopupView(event, {'action':'login_view_main_init'}, {'center_flag':true});
										}.bind(this);
			}
		}
		commonCls.send(params);
        // Add version_flg and download_divergence_flg 2010/12/22 H.Goto --end--
	},
	// Add dwonload file from url 2008/10/08 Y.Nakao --end--
	
	// Add els download for show error msg 2008/10/10 Y.Nakao --start--
	repositoryElsDownload: function(selIdx_id, selIdx_name, entry_type){
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_cinii_admin&selIdx_id=" + selIdx_id + 
							"&selIdx_name=" + selIdx_name + "&entry_type=" + entry_type;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add els download for show error msg 2008/10/10 Y.Nakao --end--
	
	// Add site License 2008/10/20 Y.Nakao --start--
	repositoryAdminEditRow: function(edit_type, edit_idx) {
	
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
            // choose all form controls
            var children = forms[i].elements;
            var len2 = children.length;
            // choose "input (text, textarea, chkbox)" and
            for (j=0; j<len2; j++) {
                if(
                    (
                        (
                            forms[i].id == "id_admin_site_license"
                            && children[j].tagName == "INPUT"
                            && (children[j].type == "text" || children[j].type == "textarea" )
                        )
                        || children[j].name == "harvesting_repositoryName[]"
                        || children[j].name == "harvesting_baseUrl[]"
                        || children[j].name == "harvesting_post_index[]"
                        || children[j].name == "harvesting_post_name[]"
                    )
                    && children[j].value == ""
                  )
                {
                    // hankaku space.
                    children[j].value = " ";
                }
            }
            form_data += "&" + Form.serialize(forms[i]);
		}
	
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_admineditrow" + form_data + 
						  "&edit_type="+edit_type + "&edit_idx=" + edit_idx;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add site License 2008/10/20 Y.Nakao --end--
	
	// Add check pub detail view 2008/10/21 Y.Nakao --start--
	repositoryDetailLogin: function(event, shib_flg, version_flg) {
		var top_el = $(this.id);
		var params = new Object();
		params["param"] = "action=repository_view_main_item_detail";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
        // Add version_flg 2011/02/22 A.Suzuki --start--
        if(version_flg =='0'){
            params['callbackfunc'] = function(){
                                    if(shib_flg == '1'){
                                        location.href=_nc_base_url + '/index.php?action=login_view_main_init';	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
                                    } else {
                                        commonCls.sendPopupView(event,{'action':'login_view_main_init'}, {'center_flag':true,'modal_flag':true});
                                    }
                                }.bind(this);
        }else{
            if(shib_flg == '1'){
				params['callbackfunc'] = function(){location.href=_nc_base_url + '/index.php?action=login_view_main_init';}.bind(this);	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
            } else {
                params['callbackfunc'] = function(){
                                            commonCls.displayVisible($('login_popup'));
                                            commonCls.sendPopupView(event, {'action':'login_view_main_init'}, {'center_flag':true});
                                        }.bind(this);
            }
        }
        // Add version_flg 2011/02/22 A.Suzuki --end--
        commonCls.send(params);
	},
	// Add check pub detail view 2008/10/21 Y.Nakao --end--
	
	// Add easy fill biblio info 2008/11/14 A.Suzuki --start--
	repositoryFillData: function(fill_type, fill_id) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		this.repositoryItemAddSpaceToEmptyText(form);
		params["param"] = "action=repository_action_main_item_filldata" + "&"+ Form.serialize(form) +
						  "&type_fill="+ fill_type + "&id_fill="+ fill_id;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add easy fill biblio info 2008/11/14 A.Suzuki --end--
	
	// Add get PrefixId 2008/11/19 --start--
	repositoryGetPrefixId: function() {
		var topEl = $(this.id);				// get "modeule box" element.
		var forms = topEl.getElementsByTagName("form");
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
            // choose all form controls
            var children = forms[i].elements;
            var len2 = children.length;
            // choose "input (text, textarea, chkbox)" and
            for (j=0; j<len2; j++) {
                if(
                    (
                        (
                            forms[i].id == "id_admin_site_license"
                            && children[j].tagName == "INPUT"
                            && (children[j].type == "text" || children[j].type == "textarea" )
                        )
                        || children[j].name == "harvesting_repositoryName[]"
                        || children[j].name == "harvesting_baseUrl[]"
                        || children[j].name == "harvesting_post_index[]"
                        || children[j].name == "harvesting_post_name[]"
                    )
                    && children[j].value == ""
                  )
                {
                    // hankaku space.
                    children[j].value = " ";
                }
            }
            form_data += "&" + Form.serialize(forms[i]);
		}
	
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_adminadmit" + form_data +"&prefix=true";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);

	},
	// Add get PrefixId 2008/11/19 --end--
	// change index tree view action 2008/11/28 Y.Nakao --end--
	
	// change index tree for edit tree 2008/12/10 Y.Nakao --start--
	// show delete confirm popup
	repositoryIndexDeleteConfirm: function(pid, id, name, event){
		var params = new Object();
		var top_el = $(this.id);
		params["top_el"] = top_el;
		params["target_el"] = top_el;
		params["center_flag"] = true;
		params["modal_flag"] = true;
				
		var popupParams = new Object();
		popupParams["action"] = "repository_view_common_edit_tree_confirm";
		popupParams["sel_node_pid"] = pid;
		popupParams["sel_node_id"] = id;
		popupParams["sel_node_name"] = name;
		popupParams["prefix_id_name"] = "edit_tree_confirm";
		
		commonCls.sendPopupView(event, popupParams, params);
	},
	
	//show html help popup 2009/09/28 K.Ito --start--
	repositoryHelp: function(event, helpID){
		var top_el = $(this.id);
		
		this.params = new Object();
		this.params["prefix_id_name"] = "popup_repository_" + 0;
		this.params["action"] = "repository_view_common_help";
		//this.params["key"] = "value";
		this.params["helpID"] = helpID;
		
		this.sendparams = new Object();
		this.sendparams["top_el"] = top_el;
		this.sendparams["target_el"] = top_el;
		this.sendparams["center_flag"] = true;
		this.sendparams["modal_flag"] = true;
		

		commonCls.sendPopupView(event, this.params, this.sendparams);
	},
	
	//show html help popup 2009/09/28 K.Ito --end--
	
	
	// run delete this index and bottom index and item are delete with
	repositoryDeleteIndex: function(pid, id, del_mod, page_id, block_id, returnHtml){
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "get";
		// Send FormData & argument
		if(returnHtml == 'editPrivatetree'){
			params["param"] = 'action=repository_action_main_privatetree';
		} else {
			params["param"] = 'action=repository_action_edit_tree';
		}
		params["param"] +=	'&edit_id='+id+
							'&pid='+pid+
							'&edit_mode='+del_mod+
							'&page_id='+page_id+
							'&block_id='+block_id;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	
	// change index tree for edit tree 2008/12/10 Y.Nakao --end--
	
	// Add hidden metadata 2009/01/28 A.Suzuki --start--
	checkSelectHidden: function(rowNo){
		var disp = "disp"+rowNo;
		var newline = "newline"+rowNo;
		var newline_display = "newline_display"+rowNo;
		var newline_dummy = "newline_dummy"+rowNo;
		var hidden = "hidden"+rowNo;
		var type = "type"+rowNo;
		var elem_disp = document.getElementById(disp);
		var elem_newline = document.getElementById(newline);
		var elem_newline_display = document.getElementById(newline_display);
		var elem_newline_dummy = document.getElementById(newline_dummy);
		var elem_hidden = document.getElementById(hidden);
		var elem_type = document.getElementById(type);
		
		if( elem_hidden.checked == true){
			elem_disp.checked = false;
			elem_newline.checked = false;
			elem_hidden.checked = true;
			elem_disp.disabled = true;
			elem_newline.disabled = true;
			elem_newline_display.style.display = "";
			elem_newline_dummy.style.display = "none";
		} else {
			elem_hidden.checked = false;
            // Add show thumbnail in search result 2012/02/10 T.Koyasu -start-
            if(elem_type.selectedIndex == 7){
                // thumbnail
                elem_newline.checked = true;
                elem_disp.disabled = false;
                elem_newline.disabled = false;
                elem_newline_display.style.display = "none";
                elem_newline_dummy.style.display = "";
            // Add show thumbnail in search result 2012/02/10 T.Koyasu -end-
            }else if(elem_type.selectedIndex == 8 || elem_type.selectedIndex == 13){
				// file or file_price
				elem_newline.checked = true;
				elem_disp.disabled = false;
				elem_newline.disabled = false;
				elem_newline_display.style.display = "none";
				elem_newline_dummy.style.display = "";
			} else if(elem_type.selectedIndex == 12){
				// supple
				elem_disp.checked = false;
				elem_newline.checked = false;
				elem_disp.disabled = true;
				elem_newline.disabled = true;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			} else {
				elem_disp.disabled = false;
				elem_newline.disabled = false;
				elem_newline_display.style.display = "";
				elem_newline_dummy.style.display = "none";
			}
		}
	},
	// Add hidden metadata 2009/01/28 A.Suzuki --end--
	
	// Add prefix auto entry 2009/04/30 A.Suzuki --start--
	repositoryGoPrefixConfirm: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_prefix_confirm" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	
	repositoryGoPrefixImage: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_prefix_image" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add prefix auto entry 2009/04/30 A.Suzuki --end--
	
	repositorySwitchLanguage: function() {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["loading_el"] = top_el;
		params["top_el"] = top_el;
		params["method"] = "get";
		params["target_el"] = top_el;
		params["param"] = "action=repository_view_main_item_snippet" + "&"+ Form.serialize($('form_select_lang'));
		commonCls.send(params);
	},
	
	// Add item delete from workflow 2009/07/13 A.Suzuki --start--
	repositoryItemDeleteFromWorkFlow: function(item_id, item_no, mod_date, active_tab) {
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_detail" + "&item_id=" + item_id + "&item_no=" + item_no + "&item_update_date=" + mod_date + "&workflow_flag=true" + "&workflow_active_tab=" + active_tab;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add item delete from workflow 2009/07/13 A.Suzuki --end--
	
	// Add change item status from workflow 2009/07/13 A.Suzuki --start--
	repositoryChangeShownStatusFromWorkFlow: function(item_id, item_no, mod_date, shown_status, active_tab) {
		var top_el = $(this.id);
		var params = new Object();
		var status = 0;
		if(shown_status){
			status = 1;
		}
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_detail" + "&item_id="+ item_id + "&item_no="+ item_no + "&item_update_date=" + mod_date + "&shown_status=" + status + "&workflow_flag=true" + "&workflow_active_tab=" + active_tab;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add change item status from workflow 2009/07/13 A.Suzuki --end--
	
	// Add els setting 2009/09/01 Y.Nakao --start--
	repositoryElsSetting: function(){
		var top_el = $(this.id);
		var params = new Object();
		var form = top_el.getElementsByTagName("form")[0];
		params["method"] = "get";
		params["param"] = "action=repository_action_edit_cinii_els_setting" + "&"+ Form.serialize(form);
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	repositoryElsEntry: function(page_id, block_id, sel_index_id, els_connect, lab_connect, success, error, success_els, success_lab, error_els, error_lab, id_module){
		var pars="action=repository_action_edit_cinii_els_entry";
		pars += '&sel_index_id='+sel_index_id;
		pars += '&els_connect='+els_connect;
		pars += '&lab_connect='+lab_connect;
		pars += '&page_id='+page_id;
  		pars += '&block_id='+block_id;
		var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
		
		var myAjax = new Ajax.Request(
						url,
						{
							method: 'get',
							parameters: pars, 
							onFailure : function(){
								alert(error);
							},
							onSuccess : function(res){
								if(res.responseText == "true"){
									alert(success_els+'\n'+success_lab);
								} else if(res.responseText == "lab_NG"){
									alert(success_els+'\n'+error_lab);
								} else if(res.responseText == "ELS_NG"){
									alert(error_els+'\n'+success_lab);
								} else if(res.responseText == "NG"){
									alert(error_els+'\n'+error_lab);
								} else if(res.responseText == "ELS_OK"){
									alert(success_els);
								} else if(res.responseText == "lab_OK"){
									alert(success_lab);
								} else {
									alert(error);
								}
                                initTree('elsTree'+id_module+'panel',id_module, 'els');
							},
							onComplete: function(res) {
							}
						}
					);
	},
	// Add els setting 2009/09/01 Y.Nakao --end--
	
	// Add supple contents add button 2009/08/18 A.Suzuki --start--
	repositorySendPopupForSupple: function(event, Item_ID, Item_No){
		var top_el = $(this.id);
		this.params = new Object();
		this.params["prefix_id_name"] = "popup_repository_" + 0;
		this.params["action"] = "repository_view_common_item_supple_popup";
		this.params["key"] = "value";
		this.params["item_id"] = Item_ID;
		this.params["item_no"] = Item_No;
		
		this.sendparams = new Object();		
		this.sendparams["top_el"] = top_el;
		if (!event) {
			var date_el = Element.getChildElementByClassName(top_el, "th_classic_content content");
			var offset = Position.positionedOffset(date_el);
			this.sendparams['x'] = offset[0];
			this.sendparams['y'] = offset[1];
		}
		commonCls.sendPopupView(event, this.params, this.sendparams);
	},
	// Add supple contents add button 2009/08/18 A.Suzuki --end--
	// Add supple contents add button 2009/08/18 A.Suzuki --start--
	repositoryAddSupple: function(item_id, item_no, mode, weko_key){
		var top_el = $(this.id);
		var params = new Object();
 		var tmp_weko_key = encodeURIComponent(weko_key); // Add suppleContentsEntry 2015/03/23 Y.Yamazawa

		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_supple" + "&item_id="+ item_id + "&item_no="+ item_no + "&mode=" + mode + "&weko_key=" + tmp_weko_key; // Update suppleContentsEntry 2015/03/23 Y.Yamazawa
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add supple contents add button 2009/08/18 A.Suzuki --end--
	// Add supple contents delete button 2009/08/27 A.Suzuki --start--
	repositoryDeleteSupple: function(item_id, item_no, supple_no){
		var top_el = $(this.id);
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_supple" + "&item_id="+ item_id + "&item_no="+ item_no + "&supple_no=" + supple_no + "&mode=delete";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add supple contents delete button 2009/08/27 A.Suzuki --end--
	// Add get suffixID button for detail page 2009/09/03 A.Suzuki --start--
	repositoryGetSuffixId: function(){
		var top_el = $(this.id);
		var params = new Object();
		var form = document.getElementById('send_date');
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_detail" + "&"+ Form.serialize(form) + "&get_id_flag=true";
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add get suffixID button for detail page 2009/09/03 A.Suzuki --end--
	
	// Add item delete from suppleworkflow 2009/09/24 A.Suzuki --start--
	repositorySuppleDeleteFromWorkFlow: function(item_id, item_no, supple_no, active_tab) {
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_item_supple" + "&item_id=" + item_id + "&item_no=" + item_no + "&supple_no=" + supple_no + "&mode=delete" + "&workflow_flag=true" + "&workflow_active_tab=" + active_tab;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	}, 
	// Add item delete from suppleworkflow 2009/09/24 A.Suzuki --end--
	
	// 
	repositorySendTestMail: function(page_id, block_id, success, error){
		var pars="action=repository_action_edit_mailtest";
		pars += '&review_mail='+encodeURIComponent(document.getElementById("id_review_mail").value);
		pars += '&page_id='+page_id;
  		pars += '&block_id='+block_id;
		var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
		var myAjax = new Ajax.Request(
						url,
						{
							method: 'post',
							parameters: pars, 
							onFailure : function(){
								alert(error);
							},
							onSuccess : function(res){
								if(res.responseText.length > 0){
									document.getElementById("id_review_mail").value = res.responseText;
								}
								alert(success);
							},
							onComplete: function(res) {
							}
						}
					);
	},
	
	// Add review mail setting 2009/09/30 Y.Nakao --start--
	repositoryReviewResultSetting: function(check, setting, tab){
		if(check){
			check = 1;
		} else {
			check = 0;
		}
		
		if(setting == 2){
			// supple
			var tab_el = document.getElementById("supple_workflow_active_tab_id");
		} else {
			// contents
			var tab_el = document.getElementById("workflow_active_tab_id");
		}
		
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_main_user"+
						'&check='+check+
						'&setting='+setting+
						'&tab='+tab_el.value;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add review mail setting 2009/09/30 Y.Nakao --end--
	// Add supple contents delete form 2009/10/09 A.Suzuki --start--
	repositorySendDeletePopupForSupple: function(event, item_id, item_no, supple_no, url, tab){
		var top_el = $(this.id);
		this.params = new Object();
		this.params["prefix_id_name"] = "popup_repository_" + 0;
		this.params["action"] = "repository_view_common_item_supple_deletepopup";
		this.params["key"] = "value";
		this.params["item_id"] = item_id;
		this.params["item_no"] = item_no;
		this.params["supple_no"] = supple_no;
		this.params["supple_url"] = url;
		this.params["supple_workflow_active_tab"] = tab;
		
		this.sendparams = new Object();		
		this.sendparams["top_el"] = top_el;
		this.sendparams["center_flag"] = true;
		this.sendparams["modal_flag"] = false;
		commonCls.sendPopupView(event, this.params, this.sendparams);
	},
	// Add supple contents delete form 2009/10/09 A.Suzuki --end--
	
	// Add get sitemap 2009/12/14 K.Ando --start--
	// Take a measures to create ranking database  2010/02/04 K.Ando --start--
	repositoryAdminConf: function( page_id, block_id, action){
		var login_id = document.getElementById('adminconf_login_id').value;
		var pars = "action=repository_sitemap";
		if(action == 'ranking'){
			pars = "action=repository_action_common_ranking";
        }else if(action == 'filecleanup'){
            pars = "action=repository_action_common_filecleanup";
        }
        else if(action == 'harvesting')
        {
            pars = "action=repository_action_common_harvesting";
        }
        else if(action == 'usagestatistics')
        {
            pars = "action=repository_action_common_usagestatistics";
        }
        else if(action == 'feedback')
        {
            pars = "action=repository_action_common_usagestatisticsmail";
        }
        else if(action == 'sitelicensemail')
        {
            pars = "action=repository_action_common_sitelicensemail";
        }
        else if(action == 'reconstructindexauth')
        {
            pars = "action=repository_action_common_reconstruction_indexauthority";
        }
        else if(action == 'reconstructsearch')
        {
            pars = "action=repository_action_common_reconstruction_search";
        }
        else if(action == 'externalsearchstopword')
        {
            pars = "action=repository_action_common_updateexternalsearchword";
        }
		pars += '&login_id='+ encodeURIComponent(login_id);
  		pars += '&password='+ encodeURIComponent(document.getElementById('adminconf_password').value);
		pars += '&page_id=' + page_id;
  		pars += '&block_id=' + block_id;
		var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
	    document.body.style.cursor = 'wait';
	    
		var myAjax = new Ajax.Request(
						url,
						{
							method: 'get',
							parameters: pars, 
							onFailure : function(){
								alert('error');
							},
							onSuccess : function(res){
								document.body.style.cursor = 'default';
								if( res.responseText == "Successfully updated.\n" ||
									res.responseText == "Successfully updated." || 
									res.responseText == "Start harvesting." || 
									res.responseText == "Start harvesting.\n" ||
                                    res.responseText == "Start send feedback mail." || 
                                    res.responseText == "Start send feedback mail.\n" || 
                                    res.responseText == "Start Update Stopwordl." || 
                                    res.responseText == "Start Update Stopword.\n" || 
                                    res.responseText == "Start send site license mail." || 
                                    res.responseText == "Start send site license mail.\n"
                                    )
								{
									document.location.href =
										_nc_base_url + "/?action=pages_view_main&active_action=repository_view_common_redirect&page_id="	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
									 + page_id + "&block_id=" + block_id;
								} else {
									document.location.href=_nc_base_url+'/?action=pages_view_main&active_action=repository_view_edit_adminconf_confirm'		// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
									 + '&is_create_data=false&adminconfirm_action=' + action
									 + '&page_id=' + page_id + "&block_id=" + block_id;
								}
							},
							onComplete: function(res) {
									return;
							}
						}
					);
	
		
	},
	// Take a measures to create ranking database  2010/02/04 K.Ando --end--
	// Add get sitemap 2009/12/14 K.Ando --end--
	
	// Go To Reset Ranking 2010/02/08 K.Ando --start--
	repositoryResetRanking: function() {
		var top_el = $(this.id);
		var forms = top_el.getElementsByTagName("form");
		var len = forms.length;
		var form_data = "";
		for (i=0; i<len; i++) {
			form_data += "&" + Form.serialize(forms[i]);
		}
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_resetranking" + form_data;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Go To Reset Ranking 2010/02/08 K.Ando --end--
	
	// Add index thumbnail 2010/08/11 Y.Nakao --start--
	// Modify for use privatetree 2013/04/15 K.Matsuo --start--
	repositoryIndexThumbnailFileUpload: function(pars, returnHtml)
	{
		if($('thumbnail_file').value.length == 0) {
			this.onSubmitEdittree_repos(pars, returnHtml);
			return;
		}
		var attachment_params = new Object();
		attachment_params['top_el'] = this.id;
		
		// call file upload action
		attachment_params['param'] = {"action":"repository_action_edit_treethumbnail"};

		// set call back function
		attachment_params['callbackfunc'] = function(){this.onSubmitEdittree_repos(pars, returnHtml);}.bind(this);
		
		commonCls.sendAttachment(attachment_params);
	},
	// Modify for use privatetree 2013/04/15 K.Matsuo --end--
	// Add index thumbnail 2010/08/11 Y.Nakao --end--
	
	repositoryItemEditFillAuthor: function(mode, fillStr, prefix, suffix, attrId, attrNo) {
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		var params = new Object();
		params["method"] = "post";
		// OK, check all text(textarea) element and if IsEmpty, set " ".
		this.repositoryItemAddSpaceToEmptyText(form);
		// Fix input suffix vulnerability. 2011/07/04 Y.Nakao --start--
		suffix = encodeURIComponent(suffix);
		fillStr = encodeURIComponent(fillStr);
		// Fix input suffix vulnerability. 2011/07/04 Y.Nakao --end--
		// Get Opend Node IDs & Checked Node Ids.
		params["param"] = "action=repository_action_main_item_fillauthor" + "&" +
						  Form.serialize(form) + "&fillStr="+fillStr + 
						  "&prefixId="+prefix+"&suffixId="+suffix + "&mode="+mode +
						  "&attrId="+attrId+"&attrNo="+attrNo;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	
    // Add File clean-up 2011/02/23 H.Goto --start--
    repositoryFileCleanUp: function() {
        var top_el = $(this.id);
        var forms = top_el.getElementsByTagName("form");
        var len = forms.length;
        var form_data = "";
        for (i=0; i<len; i++) {
            form_data += "&" + Form.serialize(forms[i]);
        }
        var params = new Object();
        params["method"] = "post";
        params["param"] = "action=repository_action_common_filecleanup" + form_data;
        params["top_el"] = top_el;
        params["loading_el"] = top_el;
        params["target_el"] = top_el;
        commonCls.send(params);
    },
    // Add File clean-up 2011/02/23 H.Goto --end--
    
    repositoryItemEditFillContributor: function(handle, name, email) {
        var top_el = $(this.id);
        var form = top_el.getElementsByTagName("form")[0];
        var params = new Object();
        this.repositoryItemAddSpaceToEmptyText(form);
        params["method"] = "post";
        params["param"] = "action=repository_action_main_item_fillcontributor&" + Form.serialize(form) + 
                          "&handle=" + handle + "&name=" + name + "&email=" + email + "&mode=suggest";
        params["top_el"] = top_el;
        params["loading_el"] = top_el;
        params["target_el"] = top_el;
        commonCls.send(params);
    },
    
    repositoryAdminFileUpload: function() {
        var top_el = $(this.id);
        var forms = top_el.getElementsByTagName("form");
        var len = forms.length;
        var cnt = 0;
        for (i=0; i<len; i++)
        {
            cnt += this.repositoryItemCountUpload(forms[i]);
        }
        if(cnt == 0)
        {
            this.repositoryAdminAdmit();
            return;
        }
        
        var attachment_params = new Object();
        attachment_params['top_el'] = this.id;
        attachment_params['param'] = {"action":"repository_action_edit_adminupload"};
        attachment_params['callbackfunc'] = this.repositoryAdminAdmit.bind(this);
        commonCls.sendAttachment(attachment_params);
    },
    
    repositoryAdminEditRowFileUpload: function(edit_type, edit_idx) {
        var top_el = $(this.id);
        var forms = top_el.getElementsByTagName("form");
        var len = forms.length;
        var cnt = 0;
        for (i=0; i<len; i++)
        {
            cnt += this.repositoryItemCountUpload(forms[i]);
        }
        if(cnt == 0)
        {
            this.repositoryAdminEditRow(edit_type, edit_idx);
            return;
        }
        
        var attachment_params = new Object();
        attachment_params['top_el'] = this.id;
        attachment_params['param'] = {"action":"repository_action_edit_adminupload"};
        attachment_params['callbackfunc'] = function(){this.repositoryAdminEditRow(edit_type, edit_idx);}.bind(this);
        commonCls.sendAttachment(attachment_params);
    },
    
    // Add Custom Sort Order A.Jin --start--
    repositoryCustomSortOrder: function(sortIndexId, currentSortOrder, targetSortOrder) {
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "action=repository_action_edit_item_customsort" + 
		                  "&targetIndexId=" + sortIndexId + 
		                  "&currentSortOrder=" + currentSortOrder + 
		                  "&targetSortOrder=" + targetSortOrder;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
    },
    // Add Custom Sort Order A.Jin --end--

    // Add ListDelete A.Jin --start--
    repositoryListDelete: function(sortIndexId, isAllDel) {
		var top_el = $(this.id);
		var params = new Object();

		params["method"] = "post";
		params["param"] = "action=repository_action_edit_item_listdelete" + 
		                  "&targetIndexId=" + sortIndexId + 
		                  "&isDeleteSubIndexItem=" + isAllDel;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
    },
    // Add ListDelete A.Jin --end--
	

	
	/**
	 * 
	 */
	repositoryFadeOut: function( class_id, element )
	{
		// sub opacity range
		var speed = 0.05;
		
		// set timmer [msec]
		var timmer = 500;
		
		if( element.style.opacity <= 0.0 )
		{
			element.innerHTML = '';
			element.style.display = 'none';
			return;
		}
		
		element.style.opacity -= speed;
		setTimeout(function(){ repositoryCls[class_id].repositoryFadeOut( class_id, element ) }, timmer);
	},
	
	repositoryPopupClose: function( element )
	{
		element.innerHTML = '';
		element.style.display = 'none';
		return;
	},
	
	// Add els setting 2009/09/01 Y.Nakao --end--
		
	// Add drag and drop file upload 2013/03/19 K.Matsuo --start--
	// ドラッグオーバー時、ブラウザが実施するファイルオープン処理を抑制
	repositoryDragOver: function( event ){
		event.preventDefault();
	},
	
	repositoryDropFiles: function(event, page_id, block_id, target, type, add_row){ 
	
		event.preventDefault(); 
		var flag = this.repositoryFileUploadBrowserCheck();
		if(flag != true){
			return;
		}
		var files = event.dataTransfer.files;
		
		// FormData オブジェクトを用意
		var top_el = $(this.id);
		var form = top_el.getElementsByTagName("form")[0];
		
		var formData = document.createElement("form");
		var formData2 = document.createElement("form");
		var ids_file_no = 0;
		var ids_thumb_no = 0;
		var formFileLength = 0;
		var inputFileList = form['input_ids_file[]'];
		var inputThumbList = form['input_ids_thumbnail[]'];
		if(inputFileList != null){
			if( inputFileList[0] != null){
				formFileLength = inputFileList.length;
			} else {
				formFileLength = 1;
			}
		}
		var formThumbLength = 0;
		if(inputThumbList != null){
			if(inputThumbList[0] != null){
				formThumbLength = inputThumbList.length;
			} else {
				formThumbLength = 1;
			}
		}
		var formLength = formFileLength + formThumbLength;
		var dropColumnYesNum = 0;
		var dropColumnInputNum = 0;
		var dropColumnFirstIdsNo = -1;
		// ドロップされた領域までフォームデータ用フォームに追加
		for(var ii = 0; ii < formLength; ii++){
			var input_ids = null;
			// input_ids_file[]のデータを取得する(フォームにある"input_ids_file[]"の個数によって取得仕方が異なる)
			var input_ids_file = null;
			if(formFileLength != ids_file_no){
				if(formFileLength == 1){
					input_ids_file = inputFileList;
				} else if(formFileLength > 1){
					input_ids_file = inputFileList[ids_file_no];
				}
			}
			// input_ids_thumb[]のデータを取得する(フォームにある"input_ids_thumb[]"の個数によって取得仕方が異なる)
			var input_ids_thumb = null;
			if(formThumbLength != ids_thumb_no){
				if(formThumbLength == 1){
					input_ids_thumb = inputThumbList;
				} else if(formThumbLength > 1){
					input_ids_thumb = inputThumbList[ids_thumb_no];
				}
			}
			// 次の項目がinput_ids_fileかinput_ids_thumbか判断する
			if(input_ids_file != null && input_ids_thumb != null){
				var strFile = input_ids_file.id.split('_');
				var Fileid = parseInt(strFile[1], 10);
				var strThumb = input_ids_thumb.id.split('_');
				var Thumbid = parseInt(strThumb[1], 10);
				if(Fileid < Thumbid){
					checkFormid = Fileid;
					checkType = 'file';
					input_ids = input_ids_file;
				} else {
					checkFormid = Thumbid;
					checkType = 'thumbnail';
					input_ids = input_ids_thumb;
				}
			} else if(input_ids_file != null){
				var strFile = input_ids_file.id.split('_');
				var Fileid = parseInt(strFile[1], 10);
				checkFormid = Fileid;
				checkType = 'file';
				input_ids = input_ids_file;
			} else if(input_ids_thumb != null){
				var strThumb = input_ids_thumb.id.split('_');
				var Thumbid = parseInt(strThumb[1], 10);
				checkFormid = Thumbid;
				checkType = 'thumbnail';
				input_ids = input_ids_thumb;
			}
			if(input_ids != null){
				var upload = null;
				// upload[]のデータを取得する(フォームにある"upload[]"の個数によって取得仕方が異なる)
				if( form['upload[]'] != null){
					if(form['upload[]'][0] == null){
						upload = form['upload[]']
					} else {
						upload = form['upload[]'][0]
					}
				}
				if(checkFormid > parseInt(target, 10)){
					break;
				}
				if(checkFormid == parseInt(target, 10)){
					// 行が追加されないとき
					if(add_row != true){
						if(input_ids.value=="YES"){
							formData.appendChild(upload);
						} else {
							formData2.appendChild(upload);
						}
						break;
					// 行が追加されるとき
					} else {
						dropColumnInputNum++;
						if(dropColumnFirstIdsNo < 0){
							if(type == 'file'){
								dropColumnFirstIdsNo = ids_file_no;
							} else {
								dropColumnFirstIdsNo = ids_thumb_no;
							}
						}
						if(input_ids.value != 'TILL'){
							if(input_ids.value=="YES"){
								formData.appendChild(upload);
								dropColumnYesNum++;
							} else {
								formData2.appendChild(upload);
							}
						}
					}
				} else {
					if(input_ids.value != 'TILL'){
						formData.appendChild(upload);
					}
				}
				if(checkType == 'file'){
					ids_file_no++;
				} else {
					ids_thumb_no++;
				}
			}
		}
		var fd = new FormData(formData);
		dropColumnYesNum += event.dataTransfer.files.length;
		for(var i = dropColumnFirstIdsNo; i < dropColumnFirstIdsNo + dropColumnInputNum; i++)
		{
			if(dropColumnYesNum <= 0){
				break;
			}
			if(type == 'file'){
				if(formFileLength != i){
					if(formFileLength == 1){
						input_ids = inputFileList;
					} else if(formFileLength > 1){
						input_ids = inputFileList[i];
					}
				}
			} else {
				// input_ids_thumb[]のデータを取得する(フォームにある"input_ids_thumb[]"の個数によって取得仕方が異なる)
				var input_ids_thumb = null;
				if(formThumbLength != i){
					if(formThumbLength == 1){
						input_ids = inputThumbList;
					} else if(formThumbLength > 1){
						input_ids = inputThumbList[i];
					}
				}
			}
			if(input_ids.value != 'TILL'){
				input_ids.value = 'YES';
				dropColumnYesNum--;
			}
		}
		
		var checkFormNo = 0;
		var hiddenArea = document.getElementById("hidden_area_"+target);
		// ドラッグ＆ドロップされたファイル情報を追加する
		for (var i = 0; i < event.dataTransfer.files.length; i++) {
			// 行追加が出来るとき
			if(add_row == true){
				fd.append("upload[]", event.dataTransfer.files[i]);
				var ipt1 = document.createElement("input");
				ipt1.type = "hidden"
				if(dropColumnYesNum > 0){
					ipt1.value = "YES";
					dropColumnYesNum--;
				} else {
					ipt1.value = "TILL";
				}
				if(type == 'file'){
					ipt1.name = "input_ids_file[]";
					ids_file_no++;
					formLength++;
					formFileLength++;
				} else {
					ipt1.name = "input_ids_thumbnail[]";
					ids_thumb_no++;
					formLength++;
					formThumbLength++;
				}
				hiddenArea.appendChild(ipt1);
			} else {
			// 行追加出来ないとき
				var hidden;
				if(type == 'file'){
					hidden = document.getElementById("file_"+target+"_0");
				} else {
					hidden = document.getElementById("thumbnail_"+target+"_0");
				}
				if(hidden.value == 'NO'){
					hidden.value="YES";
					fd.append("upload[]", event.dataTransfer.files[i]);
					checkFormNo++;
				}
				break;
			}
		}
		for(var ii = ids_file_no + ids_thumb_no; ii < formLength; ii++){
			// input_ids_file[]のデータを取得する(フォームにある"input_ids_file[]"の個数によって取得仕方が異なる)
			var input_ids_file = null;
			if(formFileLength != ids_file_no){
				if(formFileLength == 1){
					input_ids_file = inputFileList;
				} else if(formFileLength > 1){
					input_ids_file = inputFileList[ids_file_no];
				}
			}
			// input_ids_thumb[]のデータを取得する(フォームにある"input_ids_thumb[]"の個数によって取得仕方が異なる)
			var input_ids_thumb = null;
			if(formThumbLength != ids_thumb_no){
				if(formThumbLength == 1){
					input_ids_thumb = inputThumbList;
				} else if(formThumbLength > 1){
					input_ids_thumb = inputThumbList[ids_thumb_no];
				}
			}
			// 次の項目がinput_ids_fileかinput_ids_thumbか判断する
			if(input_ids_file != null && input_ids_thumb != null){
				var strFile = input_ids_file.id.split('_');
				var Fileid = parseInt(strFile[1], 10);
				var strThumb = input_ids_thumb.id.split('_');
				var Thumbid = parseInt(strThumb[1], 10);
				if(Fileid < Thumbid){
					checkFormid = Fileid;
					checkType = 'file';
					input_ids = input_ids_file;
					ids_file_no++;
				} else {
					checkFormid = Thumbid;
					checkType = 'thumbnail';
					ids_thumb_no++;
					input_ids = input_ids_thumb;
				}
			} else if(input_ids_file != null){
				var strFile = input_ids_file.id.split('_');
				var Fileid = parseInt(strFile[1], 10);
				checkFormid = Fileid;
				checkType = 'file';
				ids_file_no++;
				input_ids = input_ids_file;
			} else if(input_ids_thumb != null){
				var strThumb = input_ids_thumb.id.split('_');
				var Thumbid = parseInt(strThumb[1], 10);
				checkFormid = Thumbid;
				checkType = 'thumbnail';
				ids_thumb_no++;
				input_ids = input_ids_thumb;
			}
			if(input_ids != null){
				var upload = null;
				// upload[]のデータを取得する(フォームにある"upload[]"の個数によって取得仕方が異なる)
				if( form['upload[]'] != null){
					if(form['upload[]'][0] == null){
						upload = form['upload[]'];
					} else {
						upload = form['upload[]'][checkFormNo];
					}
				}
				if(input_ids.value == 'YES'){
					if(upload != null){
						fd.append("upload[]", upload.files[0]);
					}
					checkFormNo++;
				} else if(input_ids.value == 'NO'){
					input_ids.value = "TILL";
					checkFormNo++;
				}
			}
		}

		// クロスブラウザ対応
		var xhr = null;
		if (window.XMLHttpRequest) {
			// Firefox、Safari、Internet Explorer 7.0など向け
			xhr = new XMLHttpRequest();
		} else {
			// Internet Explorer 6.0以前向け
			try {
				xhr = new ActiveXObject("Msxml2.XMLHTTP"); // 6.0用
			} catch(e) {
				try {
					xhr = new ActiveXObject("Microsoft.XMLHTTP"); // 5.5以前用
				} catch(e) {
					return null;
				}
			}
		}
		
		// ファイルをアップデートする
		var postData = "?action=repository_action_main_item_uploadfiles";
		postData += "&page_id="+page_id;
		postData += "&block_id="+block_id;
		if(add_row == true){
			postData += "&mode=drop";
			postData += "&drop_num="+event.dataTransfer.files.length;
			postData += "&target="+target;
		}
		xhr.open("POST", _nc_base_url+_nc_index_file_name+postData, false);
		xhr.setRequestHeader("Cookie",document.cookie);
		xhr.send(fd);

		// アップデート情報を保存する
		this.repositoryItemEditSaveSessionFiles('stay');
	},
	// Add drag and drop file upload 2013/03/19 K.Matsuo --end--
	
	// Add drag and drop file upload browser check 2013/04/01 K.Matsuo --start--
	repositoryShowDropText: function(){
		var OKText = "該当欄にファイルをドラッグ＆ドロップすることで複数ファイルを登録できます";
		var flag = this.repositoryFileUploadBrowserCheck();
		var textArea = document.getElementById('can_drop');
		if(flag == true){
			textArea.style.display="block";
		} else {
			textArea.style.display="none";
			
		}
	},
	
	repositoryFileUploadBrowserCheck: function(){
		var userAgent = navigator.userAgent; 
		var array;
		// 小文字に正規化
		userAgent = userAgent.toLowerCase();
		// ブラウザ、バージョン判定
		if (userAgent.indexOf('opera' ) >= 0) {
			// Opera

			// Opera 12.0以上でDrag＆Dropのファイル追加可能
			array = /opera[\s\/]+([\d\.]+)/.exec(userAgent);
			var Version = array[1].split('.')[0];
			if(Version > 12){
				return true;
			}
		} else if (userAgent.indexOf('msie') >= 0) {
			// Internet Explorer, Windows Phone, Sleipnir, Adobe Bridge

			// MSIE 10.0以上でDrag＆Dropのファイル追加可能
			array = /msie ([\d\.]+)/.exec(userAgent);
			var Version = array[1].split('.')[0];
			if(Version > 10){
				return true;
			}
		} else if (userAgent.indexOf('trident') >= 0) {
			//IE11
			
			return true;
		} else if (userAgent.indexOf('firefox') >= 0) {
			// Firefox(Mozillaを含まない)

			// Firefox 4.0以上でDrag＆Dropのファイル追加可能
			array = /firefox\/([\d\.]+)/.exec(userAgent);
			var Version = array[1].split('.')[0];
			if(Version > 4){
				return true;
			}
		} else if (userAgent.indexOf('chrome') >= 0) {
			// Chrome, Android default browser

			// Chrome7以上でDrag＆Dropのファイル追加可能
			array = /chrome\/([\d\.]+)/.exec(userAgent);
			var Version = array[1].split('.')[0];
			if(Version > 7){
				return true;
			}
		} else if (userAgent.indexOf('safari') >= 0) {
			// Safari

			// Safari5以上でDrag＆Dropのファイル追加可能
			array = /version\/([\d\.]+)/.exec(userAgent);
			var Version = array[1].split('.')[0];
			if(Version > 5){
				return true;
			}
		}
		 
		return false;
	},
	// Add drag and drop file upload browser check 2013/04/01 K.Matsuo --end--
	
	//show multi language for item type popup 2013/07/17 K.Matsuo --start--
	repositoryShowItemTypeMultiLanguagePopup: function(event, edit_id, defaultEditFlag, page_id, block_id){
		var id = this.id;
		var top_el = $(this.id);
		var form = document.getElementById("item_edit_text");
		// choose target form "item_edit_text"
		// choose all form controls
		var children = form.elements;
		var len2 = children.length;
		// choose "input (text, textarea, chkbox)" and
		for (j=0; j<len2; j++) {
			if(children[j].tagName == "INPUT" &&
			   (children[j].type == "text" || children[j].type == "textarea" )&&
			   children[j].value == "" ){
			   children[j].value = " ";		// hankaku space.
			}
		}
		// 内容をセッションに保存した後ポップアップを表示する
		var pars="action=repository_action_edit_itemtype_confirm" + "&"+ Form.serialize(form);
		pars += '&page_id=' + page_id;
  		pars += '&block_id=' + block_id;
		var url = _nc_base_url + "/index.php";
		var myAjax = new Ajax.Request(
						url,
						{
							method: 'post',
							parameters: pars, 
							onFailure : function(){
							},
							onSuccess : function(res){
								commonCls.sendPopupView(event, {'action':'repository_view_common_itemtype_multilang', 'edit_id':edit_id, 'default_edit':defaultEditFlag, 'sel_node_pid':id},
								 {'modal_flag':true});
							},
							onComplete: function(res) {
							}
						}
					);

	},
	// run delete this index and bottom index and item are delete with
	repositorySetItemTypeMultiLang: function(edit_id, formText){
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = "repository_action_edit_itemtype_multilang" + "&"+ formText;
		params["param"] += "&edit_id="+ edit_id;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	//show multi language for item type popup 2013/07/17 K.Matsuo --end--
	// Modify for itemtype authority 2014/12/15 T.Ichikawa --start--
	repositoryItemTypeAuthorityAdd: function(param)
	{
		var top_el = $(this.id);
		var params = new Object();
		params["method"] = "post";
		params["param"] = 'action=repository_action_edit_itemtype_auth_adddb'+param;
		params["top_el"] = top_el;
		params["loading_el"] = top_el;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Modify for itemtype authority 2014/12/15 T.Ichikawa --end--
	// Add for search item delete function 2015/04/06 K.Matsushita --start--
	repositoryDeleteSearchedItem: function(searchkeyword, search_type, elm)
	{
		var top_el = $(this.id);
		
		var form = document.getElementById('enter_search_delete_form' + this.id);
		var params = new Object();
		params["method"] = "post";
		
		params["param"] = "action=repository_action_edit_item_searchdelete" + 
						  "&"+ Form.serialize(form) + 
						  "&searchkeyword=" + searchkeyword + 
						  "&search_type=" + search_type;
		params["top_el"] = top_el;
		params["loading_el"] = elm;
		params["target_el"] = top_el;
		commonCls.send(params);
	},
	// Add for search item delete function 2015/04/06 K.Matsushita --end--
	// input " " to empty text element.
	
	// Other test logics
	
	// notice : we can see DOM object from this js code. also see "search.js" 
	
    repositoryRobotlistRun: function() {
        var top_el = $(this.id);
        var params = new Object();
        var form = document.getElementsByName("robotlistValid[]");
        
        var forms = "";
        for (ii = 0; ii < form.length; ii++){
            forms += "&robotlistValid[]=" + form[ii].checked;
        }
        
        params["method"] = "get";
        params["param"] = "action=repository_action_common_robotlist" + forms;
        params["top_el"] = top_el;
        params["loading_el"] = top_el;
        params["target_el"] = top_el;
        commonCls.send(params);
    },
    repositoryRobotlistCancel: function() {
        var top_el = $(this.id);
        var params = new Object();
        params["method"] = "get";
        params["param"] = "action=repository_action_edit_logdeletecancel";
        params["top_el"] = top_el;
        params["loading_el"] = top_el;
        params["target_el"] = top_el;
        commonCls.send(params);
    },

}
