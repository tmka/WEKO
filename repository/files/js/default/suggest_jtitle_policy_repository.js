/*
--------------------------------------------------------
suggest_jtitle_policy_repository.js - Input Suggest
Version 2.2 (Update 2010/09/14)

Copyright (c) 2006-2010 onozaty (http://www.enjoyxstudy.com)

Released under an MIT-style license.

For details, see the web site:
 http://www.enjoyxstudy.com/javascript/suggest/

--------------------------------------------------------
*/
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
//
// customise for WEKO
//   at "setInputText", "option", "createSuggestArea", "moveEnd"
//
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/



if (!SuggestJtitlePolicy) {
  var SuggestJtitlePolicy = {};
}
/*-- KeyCodes -----------------------------------------*/
SuggestJtitlePolicy.Key = {
  TAB:     9,
  RETURN: 13,
  ESC:    27,
  UP:     38,
  DOWN:   40
};

/*-- Utils --------------------------------------------*/
SuggestJtitlePolicy.copyProperties = function(dest, src) {
  for (var property in src) {
    dest[property] = src[property];
  }
  return dest;
};

/*-- SuggestContributor.Local ------------------------------------*/
SuggestJtitlePolicy.Local = function() {
  this.initialize.apply(this, arguments);
};
SuggestJtitlePolicy.Local.prototype = {
  initialize: function(input, suggestArea, candidateList) {

    this.input = this._getElement(input);
    this.suggestArea = this._getElement(suggestArea);
    this.candidateList = candidateList;
    //this.oldText = this.getInputText();
    this.oldText = '';

    if (arguments[3]) this.setOptions(arguments[3]);

    // reg event
    this._addEvent(this.input, 'focus', this._bind(this.checkLoop));
    this._addEvent(this.input, 'blur', this._bind(this.inputBlur));
    //2013/4/15 Add 
    this._addEvent(this.suggestArea, 'blur', this._bind(this.inputBlur));

    var keyevent = 'keydown';
    if (window.opera || (navigator.userAgent.indexOf('Gecko') >= 0 && navigator.userAgent.indexOf('KHTML') == -1)) {
      keyevent = 'keypress';
    }
    this._addEvent(this.input, keyevent, this._bindEvent(this.keyEvent));

    // init
    this.clearSuggestArea();
  },

  // options
  interval: 500,
  dispMax: 20,
  listTagName: 'div',
  prefix: false,
  ignoreCase: true,
  highlight: false,
  dispAllKey: false,
  classMouseOver: 'over',
  classSelect: 'select',
  jtitleCandidateList : new Array(),
  wekoBlockId: '',
  hookBeforeSearch: function(){},
  oldCandidateStr: '',
  candidateStr: '',
  iiCnt: '',
  jjCnt: '',
  parentElementId: '',
  class_id: '',
  page_id: '',
  lang: '',
  isSearchStart: false,
  isCandidateExist: true,
  
  setOptions: function(options) {
    SuggestJtitlePolicy.copyProperties(this, options);
  },

  inputBlur: function() {

    this.changeUnactive();
    this.oldText = '';

    if (this.timerId) clearTimeout(this.timerId);
    this.timerId = null;

    setTimeout(this._bind(this.clearSuggestArea), this.interval);
  },

  checkLoop: function() {
    var text = this.getInputText();
    
    //byte数取得
    var byte = this.getInputTextByte(text);
    //2バイトより小さい場合、サジェストをクリアする
    if(byte < 2){
        this.oldText = text;
        
        //init
        this.clearSuggestArea();
        
        if (this.timerId) clearTimeout(this.timerId);
        this.timerId = setTimeout(this._bind(this.checkLoop), this.interval);
        return;
    }

    if (text != this.oldText) {
       
        //検索中問い合わせない
        if(this.isSearchStart == false){
            this.hookBeforeSearch(this.iiCnt, this.jjCnt);
            this.oldText = text;
            this.search();
        }else{
            //検索中の場合現在入力された値を入れておく
            this.oldText = this.getInputText();
        }
    }
    
    if(this.candidateStr != this.oldCandidateStr){
        this.oldCandidateStr = this.candidateStr;
        
        //検索中問い合わせない
        if(this.isSearchStart == false){
           this.search();
        }
    }
    
    this.oldText = text;
    if (this.timerId) clearTimeout(this.timerId);
    this.timerId = setTimeout(this._bind(this.checkLoop), this.interval);
  },

  search: function() {
    
    // init
    this.clearSuggestArea();

    var text = this.getInputText();

    if (text == '' || text == null) {
        //入力文字列が無かった場合,くるくる消す hidden loading img Add jin
        var unloadingfuncName = 'return '+'hideLoadingImage'+this.class_id+'(arg1, arg2)';
        var unloadingfunc = new Function('arg1', 'arg2', unloadingfuncName) ;
        unloadingfunc(this.iiCnt, this.jjCnt);
        
        //init
        this.clearSuggestArea();
        
        //検索終了
        this.isSearchStart = false;
    
        return;
    }
    
     //くるくる表示
     var loadingfuncName = 'return '+'displayLoadingImage'+this.class_id+'(arg1, arg2)';
     var loadingfunc = new Function('arg1', 'arg2', loadingfuncName) ;
     loadingfunc(this.iiCnt, this.jjCnt);
    
    //検索結果がない
    if(this.isCandidateExist == false){
        //くるくる消す
        var unloadingfuncName = 'return '+'hideLoadingImage'+this.class_id+'(arg1, arg2)';
        var unloadingfunc = new Function('arg1', 'arg2', unloadingfuncName) ;
        unloadingfunc(this.iiCnt, this.jjCnt);
    }
    

    var resultList = this._search(text);
    
    if (resultList.length != 0){
       this.createSuggestArea(resultList);
    }
    
    //検索終了
    this.isSearchStart = false;
    
  },

  _search: function(text) {
  
    var resultList = [];
    var temp; 
    this.suggestIndexList = [];
    
    if(this.candidateList == null){
        this.candidateList = new Array();
    }
    
    var length = this.candidateList.length;
    
    for (var i = 0; i < length; i++) {
      if ((temp = this.isMatch(this.candidateList[i], text)) != null) {
        resultList.push(temp);
        this.suggestIndexList.push(i);

        if (this.dispMax != 0 && resultList.length >= this.dispMax) break;
      }
    }
    return resultList;
  },

  isMatch: function(value, pattern) {

    if (value == null) return null;

    var pos = (this.ignoreCase) ?
      value.toLowerCase().indexOf(pattern.toLowerCase())
      : value.indexOf(pattern);

    if ((pos == -1) || (this.prefix && pos != 0)) return null;

    if (this.highlight) {
      return (this._escapeHTML(value.substr(0, pos)) + '<strong>' 
             + this._escapeHTML(value.substr(pos, pattern.length)) 
               + '</strong>' + this._escapeHTML(value.substr(pos + pattern.length)));
    } else {
      return this._escapeHTML(value);
    }
  },

  clearSuggestArea: function() {
    this.suggestArea.innerHTML = '';
    this.suggestArea.style.display = 'none';
    this.suggestJournalTitleList = null;
    this.suggestIndexList = null;
    this.activePosition = null;
    this.candidateStr = null;
    this.oldCandidateStr = null;
    //2013/4/15 Add
    this.oldText = '';
  },

  createSuggestArea: function(resultList) {
  
    this.suggestJournalTitleList = [];
    this.inputValueBackup = this.input.value;

    for (var i = 0, length = resultList.length; i < length; i++) {
      var element = document.createElement(this.listTagName);
      element.innerHTML = resultList[i];
      this.suggestArea.appendChild(element);
      this.suggestArea.style.cursor = "default";
      this._addEvent(element, 'click', this._bindEvent(this.listClick, i));
      this._addEvent(element, 'mouseover', this._bindEvent(this.listMouseOver, i));
      this._addEvent(element, 'mouseout', this._bindEvent(this.listMouseOut, i));

      this.suggestJournalTitleList.push(element);
    }
    
    this.suggestArea.style.display = '';
    this.suggestArea.scrollTop = 0;
    
    //くるくる消す hidden loading img Add jin
    var unloadingfuncName = 'return '+'hideLoadingImage'+this.class_id+'(arg1, arg2)';
    var unloadingfunc = new Function('arg1', 'arg2', unloadingfuncName) ;
    unloadingfunc(this.iiCnt, this.jjCnt);
  },

  getInputText: function() {
    return this.input.value;
  },

  setInputText: function(text) {
    this.tmp = this.jtitleCandidateList[this.activePosition];
  },
  
  getInputTextByte: function(text) {
    var byte = 0;
    for (var i = 0; i < text.length; i++) {
        var c = text.charCodeAt(i);
        //Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
        //Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
        if ( (c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
            byte += 1;
        }else {
            byte += 2;
        }
    }
    return byte;
  },

  // key event
  keyEvent: function(event) {

    if (!this.timerId) {
      this.timerId = setTimeout(this._bind(this.checkLoop), this.interval);
    }

    if (this.dispAllKey && event.ctrlKey 
        && this.getInputText() == ''
        && !this.suggestJournalTitleList
        && event.keyCode == SuggestJtitlePolicy.Key.DOWN) {
      // dispAll
      this._stopEvent(event);
      this.keyEventDispAll();
    } else if (event.keyCode == SuggestJtitlePolicy.Key.UP ||
               event.keyCode == SuggestJtitlePolicy.Key.DOWN) {
      // key move
      if (this.suggestJournalTitleList && this.suggestJournalTitleList.length != 0) {
        this._stopEvent(event);
        this.keyEventMove(event.keyCode);
      }
    } else if (event.keyCode == SuggestJtitlePolicy.Key.RETURN) {
      // fix
      if (this.suggestJournalTitleList && this.suggestJournalTitleList.length != 0) {
        this._stopEvent(event);
        this.keyEventReturn();
      }
      this.search();
    } else if (event.keyCode == SuggestJtitlePolicy.Key.ESC) {
      // cancel
      if (this.suggestJournalTitleList && this.suggestJournalTitleList.length != 0) {
        this._stopEvent(event);
        this.keyEventEsc();
      }
    } else {
      this.keyEventOther(event);
    }
  },

  keyEventDispAll: function() {

    // init
    this.clearSuggestArea();

    this.oldText = this.getInputText();

    this.suggestIndexList = [];
    for (var i = 0, length = this.candidateList.length; i < length; i++) {
      this.suggestIndexList.push(i);
    }

    this.createSuggestArea(this.candidateList);
  },

  keyEventMove: function(keyCode) {

    this.changeUnactive();

    if (keyCode == SuggestJtitlePolicy.Key.UP) {
      // up
      if (this.activePosition == null) {
        this.activePosition = this.suggestJournalTitleList.length -1;
      }else{
        this.activePosition--;
        if (this.activePosition < 0) {
          this.activePosition = null;
          this.input.value = this.inputValueBackup;
          this.suggestArea.scrollTop = 0;
          return;
        }
      }
    }else{
      // down
      if (this.activePosition == null) {
        this.activePosition = 0;
      }else{
        this.activePosition++;
      }

      if (this.activePosition >= this.suggestJournalTitleList.length) {
        this.activePosition = null;
        this.input.value = this.inputValueBackup;
        this.suggestArea.scrollTop = 0;
        return;
      }
    }

    this.changeActive(this.activePosition);
  },

  keyEventReturn: function() {

    this.clearSuggestArea();
    this.moveEnd();
  },

  keyEventEsc: function() {

    this.clearSuggestArea();
    this.input.value = this.inputValueBackup;
    this.oldText = ''; 

    if (window.opera) setTimeout(this._bind(this.moveEnd), 5);
  },

  keyEventOther: function(event) {},

  changeActive: function(index) {

    this.setStyleActive(this.suggestJournalTitleList[index]);

    this.setInputText(this.candidateList[this.suggestIndexList[index]]);

    this.oldText = this.getInputText();
    this.input.focus();
  },

  changeUnactive: function() {

    if (this.suggestJournalTitleList != null 
        && this.suggestJournalTitleList.length > 0
        && this.activePosition != null) {
      this.setStyleUnactive(this.suggestJournalTitleList[this.activePosition]);
    }
  },

  listClick: function(event, index) {

    this.changeUnactive();
    this.activePosition = index;
    this.changeActive(index);

    //2013/4/15 Add 
    this.clearSuggestArea();
    this.moveEnd();
  },

  listMouseOver: function(event, index) {
    this.setStyleMouseOver(this._getEventElement(event));
  },

  listMouseOut: function(event, index) {

    if (!this.suggestJournalTitleList) return;

    var element = this._getEventElement(event);

    if (index == this.activePosition) {
      this.setStyleActive(element);
    }else{
      this.setStyleUnactive(element);
    }
  },

  setStyleActive: function(element) {
    element.className = this.classSelect;

    // auto scroll
    var offset = element.offsetTop;
    var offsetWithHeight = offset + element.clientHeight;

    if (this.suggestArea.scrollTop > offset) {
      this.suggestArea.scrollTop = offset
    } else if (this.suggestArea.scrollTop + this.suggestArea.clientHeight < offsetWithHeight) {
      this.suggestArea.scrollTop = offsetWithHeight - this.suggestArea.clientHeight;
    }
  },

  setStyleUnactive: function(element) {
    element.className = '';
  },

  setStyleMouseOver: function(element) {
    element.className = this.classMouseOver;
  },

  moveEnd: function() {

    if (this.input.createTextRange) {
      this.input.focus(); // Opera
      var range = this.input.createTextRange();
      range.move('character', this.input.value.length);
      range.select();
    } else if (this.input.setSelectionRange) {
      this.input.setSelectionRange(this.input.value.length, this.input.value.length);
    }
    
    //くるくる消す hidden loading img Add jin
    var loadingfuncName = 'return '+'hideLoadingImage'+this.class_id+'(arg1, arg2)';
    var loadingfunc = new Function('arg1', 'arg2', loadingfuncName) ;
    loadingfunc(this.iiCnt, this.jjCnt);
    
    // last action
    if(this.wekoBlockId.length>0){
      if(this.tmp != null){
        if(this.tmp.jtitle != null && this.tmp.acquiring != null){
            if(this.lang == 'ja'){
                $('item_attr_biblio_name_'+this.iiCnt+'_'+this.jjCnt).value = this.tmp.jtitle;
            }else if(this.lang == 'en'){
                $('item_attr_biblio_name_english_'+this.iiCnt+'_'+this.jjCnt).value = this.tmp.jtitle;
            }
            
            var funcName = 'return '+'repositoryPolicyPopupWindow_'+this.class_id+'(arg1, arg2, arg3, arg4, arg5, arg6)';
            var func = new Function('arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'arg6', funcName) ;
            func(this.page_id, this.wekoBlockId, this.parentElementId, this.tmp.journalId, this.tmp.issn, this.tmp.acquiring);
            
        }
      }
    }
    this.tmp = null;
    
  },

  // Utils
  _getElement: function(element) {
    return (typeof element == 'string') ? document.getElementById(element) : element;
  },
  _addEvent: (window.addEventListener ?
    function(element, type, func) {
      element.addEventListener(type, func, false);
    } :
    function(element, type, func) {
      element.attachEvent('on' + type, func);
    }),
  _stopEvent: function(event) {
    if (event.preventDefault) {
      event.preventDefault();
      event.stopPropagation();
    } else {
      event.returnValue = false;
      event.cancelBubble = true;
    }
  },
  _getEventElement: function(event) {
    return event.target || event.srcElement;
  },
  _bind: function(func) {
    var self = this;
    var args = Array.prototype.slice.call(arguments, 1);
    return function(){ func.apply(self, args); };
  },
  _bindEvent: function(func) {
    var self = this;
    var args = Array.prototype.slice.call(arguments, 1);
    return function(event){ event = event || window.event; func.apply(self, [event].concat(args)); };
  },
  _escapeHTML: function(value) {
    return value.replace(/\&/g, '&amp;').replace( /</g, '&lt;').replace(/>/g, '&gt;')
             .replace(/\"/g, '&quot;').replace(/\'/g, '&#39;');
  }
};

/*-- SuggestContributor.LocalMulti ---------------------------------*/
SuggestJtitlePolicy.LocalMulti = function() {
  this.initialize.apply(this, arguments);
};
SuggestJtitlePolicy.copyProperties(SuggestJtitlePolicy.LocalMulti.prototype, SuggestJtitlePolicy.Local.prototype);

SuggestJtitlePolicy.LocalMulti.prototype.delim = ' '; // delimiter

SuggestJtitlePolicy.LocalMulti.prototype.keyEventReturn = function() {

  this.clearSuggestArea();
  this.input.value += this.delim;
  this.moveEnd();
};

SuggestJtitlePolicy.LocalMulti.prototype.keyEventOther = function(event) {

  if (event.keyCode == SuggestJtitlePolicy.Key.TAB) {
    // fix
    if (this.suggestJournalTitleList && this.suggestJournalTitleList.length != 0) {
      this._stopEvent(event);

      if (!this.activePosition) {
        this.activePosition = 0;
        this.changeActive(this.activePosition);
      }

      this.clearSuggestArea();
      this.input.value += this.delim;
      if (window.opera) {
        setTimeout(this._bind(this.moveEnd), 5);
      } else {
        this.moveEnd();
      }
    }
  }
};

SuggestJtitlePolicy.LocalMulti.prototype.listClick = function(event, index) {

  this.changeUnactive();
  this.activePosition = index;
  this.changeActive(index);

  this.input.value += this.delim;
  this.moveEnd();
};

SuggestJtitlePolicy.LocalMulti.prototype.getInputText = function() {

  var pos = this.getLastTokenPos();

  if (pos == -1) {
    return this.input.value;
  } else {
    return this.input.value.substr(pos + 1);
  }
};

SuggestJtitlePolicy.LocalMulti.prototype.setInputText = function(text) {

  var pos = this.getLastTokenPos();

  if (pos == -1) {
    this.input.value = text;
  } else {
    this.input.value = this.input.value.substr(0 , pos + 1) + text;
  }
};

SuggestJtitlePolicy.LocalMulti.prototype.getLastTokenPos = function() {
  return this.input.value.lastIndexOf(this.delim);
};

