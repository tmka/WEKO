// --------------------------------------------------------------------
//
// $Id: log_repository.js 9472 2011-06-16 07:54:19Z yuko_nakao $
//
// Copyright (c) 2007 - 2008, National Institute of Informatics, 
// Research and Development Center for Scientific Information Resources
//
// This program is licensed under a Creative Commons BSD Licence
// http://creativecommons.org/licenses/BSD/
//
// --------------------------------------------------------------------
/**
	ログ解析期間が正しいか判断し、指定されたtypeのログを集計するactionを呼び出す。
	@param type 1：アイテム登録数
				2：ダウンロード回数
				3：閲覧回数
	@param is_csv cvsファイルダウンロードフラグ
			0：ダウンロードしない
			1：ダウンロードする
	@param logErrorMsg ログ解析エラーメッセージ格納配列
			[0]：開始日が不正である旨のエラーメッセージ
			[1]：終了日が不正である旨のエラーメッセージ
			[2]：開始日が終了日の前である旨のエラーメッセージ
*/
function execLogAjax_repos(type, is_csv, logErrorMsg){
 if(checkPeriod_repos(logErrorMsg)){
  var pars="";
  pars += 'action=repository_action_edit_log_result';
  pars += '&page_id='+$("page_id").value;
  pars += '&block_id='+$("block_id").value;  
  pars += '&sy_log='+$("sy_log").value;
  pars += '&sm_log='+$("sm_log").value;
  pars += '&sd_log='+$("sd_log").value;
  pars += '&ey_log='+$("ey_log").value;
  pars += '&em_log='+$("em_log").value;
  pars += '&ed_log='+$("ed_log").value;
  pars += '&type_log='+type;
  if ( type==1 ) {
  	pars += '&per_log='+$("per_log1").value;
  } else if ( type==2 ) {
  	pars += '&per_log='+$("per_log2").value;
  } else if ( type==3 ) {
  	pars += '&per_log='+$("per_log3").value;
  }
  pars += '&is_csv_log='+is_csv;
  var url = _nc_base_url + "/index.php";		// Modify Directory specification BASE_URL K.Matsuo 2011/9/2

  if ( is_csv )
  {
	// disp loading icon 2010/03/02 Y.Nakao --start--
	//location.href=url+'?'+pars;
	$('loading_'+type+'_'+is_csv).style.display = "";
	var userAgent = window.navigator.userAgent.toLowerCase();
	if(userAgent.indexOf("msie") > -1 || userAgent.indexOf("trident") > -1){
		// IEでのonload代用処理
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'"></iframe>';
		$('log_frame').onreadystatechange = function(){
			if ($('log_frame').readyState == "complete"||($('log_frame').readyState == "interactive")) {
				loadingIconOff();
			}
		}
	} else if(userAgent.indexOf("firefox") > -1){
		//firefoxの場合
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'" onload="javascript: loadingIconOff();"></iframe>';
	} else{
		//その他のブラウザは待ち画像を表示しない
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'"></iframe>';
		loadingIconOff();
	}
	// disp loading icon 2010/03/02 Y.Nakao --end--
  }
  else
  {
	// disp loading icon 2010/03/02 Y.Nakao --start--
  	$('graph_area_repos').innerHTML = '<div style="text-align: center"><img src="' + _nc_core_base_url + '/images/common/indicator.gif" /></div>';	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
  	$("graph_area_repos").style.display = 'block';
	var myAjax = new Ajax.Request(
		url,
		{
			method: 'get',
			parameters: pars, 
			onComplete: function(req) {
				$("graph_area_repos").style.display = 'block';
				$("graph_area_repos").innerHTML="";
				var logHtml = document.createElement("span");
				logHtml.innerHTML = req.responseText;
				$("graph_area_repos").appendChild(logHtml);
			}
		}
	);
	// disp loading icon 2010/03/02 Y.Nakao --end--
  }
 }
}

/**
	ログ解析画面の表示押下によって呼び出される
	@param type 1：アイテム登録数
				2：ダウンロード回数
				3：閲覧回数
	@param logErrorMsg ログ解析エラーメッセージ格納配列
			[0]：開始日が不正である旨のエラーメッセージ
			[1]：終了日が不正である旨のエラーメッセージ
			[2]：開始日が終了日の前である旨のエラーメッセージ
*/
function showGraph_repos(type, logErrorMsg){
 execLogAjax_repos(type, 0, logErrorMsg);
}

/**
	ログ解析画面のDownload(CSV)押下によって呼び出される
	@param type 1：アイテム登録数
				2：ダウンロード回数
				3：閲覧回数
	@param logErrorMsg ログ解析エラーメッセージ格納配列
			[0]：開始日が不正である旨のエラーメッセージ
			[1]：終了日が不正である旨のエラーメッセージ
			[2]：開始日が終了日の前である旨のエラーメッセージ
*/
function downloadCSV_repos(type, logErrorMsg){

 if(type == 1) {
     $('downloadCSV_1_1').style.display = 'none';
 }else if(type == 2) {
     $('downloadCSV_2_1').style.display = 'none';
 }else{
     $('downloadCSV_3_1').style.display = 'none';
 }

 execLogAjax_repos(type, 1, logErrorMsg);
}

/**
    ログ解析画面のDownload(TSV)押下によって呼び出される
    @param type 1：アイテム登録数
                2：ダウンロード回数
                3：閲覧回数
    @param logErrorMsg ログ解析エラーメッセージ格納配列
            [0]：開始日が不正である旨のエラーメッセージ
            [1]：終了日が不正である旨のエラーメッセージ
            [2]：開始日が終了日の前である旨のエラーメッセージ
*/
function downloadTSV_repos(type, logErrorMsg){

 if(type == 1) {
     $('downloadCSV_1_2').style.display = 'none';
 }else if(type == 2) {
     $('downloadCSV_2_2').style.display = 'none';
 }else{
     $('downloadCSV_3_2').style.display = 'none';
 }

 execLogAjax_repos(type, 2, logErrorMsg);
}

/**
        「印刷」ボタン押下時のプレビュー画面(2012/2/10 jin add)
*/
function printGraph_repos(printArea){
 var newWin=window.open();
 var head=document.getElementsByTagName("head");
 newWin.document.open();
 newWin.document.write('<head>'+head[0].innerHTML+'</head>');
 
 // ボタン,リンク除去削除
 var print_str = $(printArea).innerHTML;
 print_str = print_str.replace('href=','name=');
 print_str = print_str.split('onClick=').join('name=');
 print_str = print_str.split('onclick=').join('name=');
 print_str = print_str.replace('<a', '<b');
 print_str = print_str.replace('</a>', '</b>');
 print_str = print_str.replace('<A', '<B');
 print_str = print_str.replace('</A>', '</B>');
 newWin.document.write('<body>'+print_str+'</body>');
 newWin.document.close();
 eval();
}


/**
	ログ解析の開始日、終了日が正しく設定されているかチェックする
	@param logErrorMsg ログ解析エラーメッセージ格納配列
			[0]：開始日が不正である旨のエラーメッセージ
			[1]：終了日が不正である旨のエラーメッセージ
			[2]：開始日が終了日の前である旨のエラーメッセージ
	@return 成功フラグ(成功：true、失敗：false)
*/
function checkPeriod_repos(logErrorMsg){

	// 開始日取得
	var yearS=$("sy_log").value;
	var monthS=$("sm_log").value;
	var dayS=$("sd_log").value;
	var dtS = new Date(yearS, monthS - 1, dayS);
	// 開始日判定
	if(!checkDate_repos(yearS,monthS,dayS)) {
		loadingIconOff();
		alert(logErrorMsg[0]);
		return false;
	}

	// 終了日取得
	var yearE=$("ey_log").value;
	var monthE=$("em_log").value;
	var dayE=$("ed_log").value;
	var dtE = new Date(yearE, monthE - 1, dayE);
	// 終了日判定
	if(!checkDate_repos(yearE,monthE,dayE)) {
		loadingIconOff();
		alert(logErrorMsg[1]);
		return false;
	}

	// 開始日と終了日の関係を判定
	if(dtS.getTime() > dtE.getTime()){
		loadingIconOff();
		alert(logErrorMsg[2]);
		return false;
	}
	return true;
}

/**
	引数で指定された年月日が正しいか判定する
	@param year 年
	@param month 月
	@param day 日
	@return 成功フラグ(成功：true、失敗：false)
*/
function checkDate_repos(year, month, day) {
	var dt = new Date(year, month - 1, day);
	if(dt == null || dt.getFullYear() != year || dt.getMonth() + 1 != month || dt.getDate() != day) {
		return false;
	}
	return true;
}

// Add log report 2008/03/09 Y.Nakao --start--
/**
	log report download action
*/
function downloadCSV_report(mail, error_msg){
	
	// disp loading icon 2010/03/02 Y.Nakao --start--
	if(mail == "true"){
		$('loading_4').style.display = "";
		$('log_report_mail').disabled = true;
		//add jin
		$('log_report_mail').style.display = "none";
	} else {
		//add jin
		$('download_csv_link').style.display = "none";
		$('loading_0').style.display = "";
	}
	// disp loading icon 2010/03/02 Y.Nakao --end--
	
	var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
	var pars="";
	pars += 'action=repository_logreport';
	pars += '&page_id='+$("page_id").value;
	pars += '&block_id='+$("block_id").value;
	pars += '&sy_log='+$("sy_logrep").value;
	pars += '&sm_log='+$("sm_logrep").value;
	pars += '&mail='+mail;
	
	// disp loading icon 2010/03/02 Y.Nakao --start--
	var userAgent = window.navigator.userAgent.toLowerCase();
	if(userAgent.indexOf("msie") > -1 || userAgent.indexOf("trident") > -1){
		// IEでのonload代用処理
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'"></iframe>';
		$('log_frame').onreadystatechange = function(){
            // Mod double alert on IE11 T.Koyasu 2015/04/09 --start--
			if ($('log_frame').readyState == "complete") {
				loadingIconOff(mail, error_msg);
			}
            // Mod double alert on IE11 T.Koyasu 2015/04/09 --end--
		}
	} else if(userAgent.indexOf("firefox") > -1){
        // Mod no alert on FireFox T.Koyasu 2015/04/09 --start--
		//firefoxの場合
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'" onload="javascript: loadingIconOff(\'' + mail + '\', \'' + error_msg + '\');"></iframe>';
        // Mod no alert on FireFox T.Koyasu 2015/04/09 --end--
	} else{
		//その他のブラウザは待ち画像を表示しない
		$('log_download').innerHTML = '<iframe boder="0" id="log_frame" src="'+url+'?'+pars+'"></iframe>';
		loadingIconOff(mail, error_msg);
	}
	// disp loading icon 2010/03/02 Y.Nakao --end--
}

/**
 set send mail address
 */
function setLogMailAddress(error_msg){
	var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
	var pars= "";
	pars += 'action=repository_action_edit_log_report';
	pars += '&address='+encodeURIComponent($("log_mail_address").value);
	pars += '&page_id='+$("page_id").value;
	pars += '&block_id='+$("block_id").value;
	var myAjax = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars, 
			onLoading : function(){},
			onFailure : function(){
				// faild
				alert(error_msg[1]);
			},
			onSuccess : function(res){
				if(res.responseText.length > 0){
					$('log_report_mail').disabled = false;
				} else {
					$('log_report_mail').disabled = true;
				}
				// success
				$('log_mail_address').value = res.responseText;
				alert(error_msg[0]);
			}
		}
	);
}

/**
 loading img hidden
*/
function loadingIconOff(mail, error_msg){
	if($('loading_0') != null){
		$('loading_0').style.display = "none";
		$('download_csv_link').style.display = "";
	}
	if($('loading_1_1') != null){
		$('loading_1_1').style.display = "none";
		$('downloadCSV_1_1').style.display = "";
	}
    if($('loading_1_2') != null){
        $('loading_1_2').style.display = "none";
        $('downloadCSV_1_2').style.display = "";
    }
	if($('loading_2_1') != null){
		$('loading_2_1').style.display = "none";
		$('downloadCSV_2_1').style.display = "";
	}
    if($('loading_2_2') != null){
        $('loading_2_2').style.display = "none";
        $('downloadCSV_2_2').style.display = "";
    }
	if($('loading_3_1') != null){
		$('loading_3_1').style.display = "none";
		$('downloadCSV_3_1').style.display = "";
	}
    if($('loading_3_2') != null){
        $('loading_3_2').style.display = "none";
        $('downloadCSV_3_2').style.display = "";
    }
	if($('loading_4') != null){
		$('loading_4').style.display = "none";
		$('log_report_mail').style.display = "";
	}
	if($('loading_5') != null){
		$('loading_5').style.display = "none";
		$('btn_log_move').style.display = "";
	}
	if(mail == "true"){
		$('log_report_mail').disabled = false;
		alert(error_msg);
	}
}
// Add log report 2008/03/09 Y.Nakao --end--

// Add log moves 2010/04/26 Y.Nakao --start--
function logMoves(id){
	
	// disp loading icon
	$('loading_5').style.display = "";
	// hidden log move btn
	$('btn_log_move').disabled = true;
	$('btn_log_move').style.display = 'none';
	
	// make parameter
	var form = $(id).getElementsByTagName("form")[0];
	var pars="action=repository_action_edit_log_move";
	pars += "&"+ Form.serialize(form);
	pars += '&page_id='+$("page_id").value;
	pars += '&block_id='+$("block_id").value;  
	
	var url = _nc_base_url + "/index.php";	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
	var myAjax = new Ajax.Request(
					url,
					{
						method: 'get',
						parameters: pars, 
						onLoading : function(){},
						onFailure : function(res){
							// faild
							alert(res.responseText);
							// move log move btn
							$('btn_log_move').disabled = false;
							// hidden loading icon
							loadingIconOff();
						},
						onSuccess : function(res){
							alert(res.responseText);
							// move log move btn
							$('btn_log_move').disabled = false;
							// hidden loading icon
							loadingIconOff();
							// reload
							commonCls.sendView(id,'repository_view_edit_log');
						}
					}
				);
}
// Add log moves 2010/04/26 Y.Nakao --end--

// Modify Set Exclude IP Address List by log analyze 2015/04/06 --start--
function setExcludeIpAddress(countNum)
{
    str = "";
    for(ii = 0; ii < countNum; ii++)
    {
        if(str.length > 0)
        {
            str = str + ",";
        }
        checkId = "check_" + ii;
        checkboxObj = document.getElementById(checkId);
        if(checkboxObj.checked == true && checkboxObj.disabled == false)
        {
            str = str + checkboxObj.value;
        }
    }
    
    var pars="action=repository_action_edit_log_exclusion";
    pars += '&page_id='+$("page_id").value;
    pars += '&block_id='+$("block_id").value;  
    pars += '&log_exclusion='+str;
    var url = _nc_base_url + "/index.php";
    
    var myAjax = new Ajax.Request(
                    url,
                    {
                        method: 'post',
                        parameters: pars, 
                        onFailure : function(){
                        },
                        onSuccess : function(res){
                            // popup
                            alert(res.responseText);
                        },
                        onComplete: function(res) {
                        }
                    }
                );
}
// Modify Set Exclude IP Address List by log analyze 2015/04/06 --end--
