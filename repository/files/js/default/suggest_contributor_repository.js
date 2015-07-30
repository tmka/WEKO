/*
--------------------------------------------------------
suggest.js - Input Suggest
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



if (!SuggestContributor) {
  var SuggestContributor = {};
}
/*-- KeyCodes -----------------------------------------*/
SuggestContributor.Key = {
  TAB:     9,
  RETURN: 13,
  ESC:    27,
  UP:     38,
  DOWN:   40
};

/*-- Utils --------------------------------------------*/
SuggestContributor.copyProperties = function(dest, src) {
  for (var property in src) {
    dest[property] = src[property];
  }
  return dest;
};

/*-- SuggestContributor.Local ------------------------------------*/
SuggestContributor.Local = function() {
  this.initialize.apply(this, arguments);
};
SuggestContributor.Local.prototype = {
  initialize: function(input, suggestArea, candidateList) {

    this.input = this._getElement(input);
    this.suggestArea = this._getElement(suggestArea);
    this.candidateList = candidateList;
    //this.oldText = this.getInputText();
    this.oldText = ''; // 2010/12/24 A.Suzuki

    if (arguments[3]) this.setOptions(arguments[3]);

    // reg event
    this._addEvent(this.input, 'focus', this._bind(this.checkLoop));
    this._addEvent(this.input, 'blur', this._bind(this.inputBlur));

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
  contributorList: new Array(),
  wekoBlockId: '',
  hookBeforeSearch: function(){},
  oldCandidateStr: '',
  candidateStr: '',

  setOptions: function(options) {
    SuggestContributor.copyProperties(this, options);
  },

  inputBlur: function() {

    this.changeUnactive();
    //this.oldText = this.getInputText();
    this.oldText = ''; // 2010/12/24 A.Suzuki

    if (this.timerId) clearTimeout(this.timerId);
    this.timerId = null;

    setTimeout(this._bind(this.clearSuggestArea), 500);
  },

  checkLoop: function() {
    var text = this.getInputText();
    if (text != this.oldText) {
      this.hookBeforeSearch();
      this.oldText = text;
      this.search();
    }
    if(this.candidateStr != this.oldCandidateStr){
        this.oldCandidateStr = this.candidateStr;
        this.search();
    }
    this.oldText = text; // 2010/12/24 A.Suzuki
    if (this.timerId) clearTimeout(this.timerId);
    this.timerId = setTimeout(this._bind(this.checkLoop), this.interval);
  },

  search: function() {

    // init
    this.clearSuggestArea();

    var text = this.getInputText();

    if (text == '' || text == null) return;

    var resultList = this._search(text);
    if (resultList.length != 0) this.createSuggestArea(resultList);
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
    this.suggestList = null;
    this.suggestIndexList = null;
    this.activePosition = null;
    this.candidateStr = null;
    this.oldCandidateStr = null;
  },

  createSuggestArea: function(resultList) {
  
  this.suggestArea.style.innerHTML = '<div style="text-align: center"><img src="' + _nc_core_base_url + '/images/common/indicator.gif"/></div>';	// Modify Directory specification BASE_URL K.Matsuo 2011/9/2
  

    this.suggestList = [];
    this.inputValueBackup = this.input.value;

    for (var i = 0, length = resultList.length; i < length; i++) {
      var element = document.createElement(this.listTagName);
      element.innerHTML = resultList[i];
      this.suggestArea.appendChild(element);
      this.suggestArea.style.cursor = "default";
      this._addEvent(element, 'click', this._bindEvent(this.listClick, i));
      this._addEvent(element, 'mouseover', this._bindEvent(this.listMouseOver, i));
      this._addEvent(element, 'mouseout', this._bindEvent(this.listMouseOut, i));

      this.suggestList.push(element);
    }

    this.suggestArea.style.display = '';
    this.suggestArea.scrollTop = 0;
  },

  getInputText: function() {
    return this.input.value;
  },

  setInputText: function(text) {
    this.tmp = this.contributorList[this.activePosition];
  },

  // key event
  keyEvent: function(event) {

    if (!this.timerId) {
      this.timerId = setTimeout(this._bind(this.checkLoop), this.interval);
    }

    if (this.dispAllKey && event.ctrlKey 
        && this.getInputText() == ''
        && !this.suggestList
        && event.keyCode == SuggestContributor.Key.DOWN) {
      // dispAll
      this._stopEvent(event);
      this.keyEventDispAll();
    } else if (event.keyCode == SuggestContributor.Key.UP ||
               event.keyCode == SuggestContributor.Key.DOWN) {
      // key move
      if (this.suggestList && this.suggestList.length != 0) {
        this._stopEvent(event);
        this.keyEventMove(event.keyCode);
      }
    } else if (event.keyCode == SuggestContributor.Key.RETURN) {
      // fix
      if (this.suggestList && this.suggestList.length != 0) {
        this._stopEvent(event);
        this.keyEventReturn();
      }
      this.search();
    } else if (event.keyCode == SuggestContributor.Key.ESC) {
      // cancel
      if (this.suggestList && this.suggestList.length != 0) {
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
    //this.oldText = ''; // 2010/12/24 A.Suzuki

    this.suggestIndexList = [];
    for (var i = 0, length = this.candidateList.length; i < length; i++) {
      this.suggestIndexList.push(i);
    }

    this.createSuggestArea(this.candidateList);
  },

  keyEventMove: function(keyCode) {

    this.changeUnactive();

    if (keyCode == SuggestContributor.Key.UP) {
      // up
      if (this.activePosition == null) {
        this.activePosition = this.suggestList.length -1;
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

      if (this.activePosition >= this.suggestList.length) {
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
    //this.oldText = this.getInputText();
    this.oldText = ''; // 2010/12/24 A.Suzuki

    if (window.opera) setTimeout(this._bind(this.moveEnd), 5);
  },

  keyEventOther: function(event) {},

  changeActive: function(index) {

    this.setStyleActive(this.suggestList[index]);

    this.setInputText(this.candidateList[this.suggestIndexList[index]]);

    this.oldText = this.getInputText();
    this.input.focus();
  },

  changeUnactive: function() {

    if (this.suggestList != null 
        && this.suggestList.length > 0
        && this.activePosition != null) {
      this.setStyleUnactive(this.suggestList[this.activePosition]);
    }
  },

  listClick: function(event, index) {

    this.changeUnactive();
    this.activePosition = index;
    this.changeActive(index);

    this.moveEnd();
  },

  listMouseOver: function(event, index) {
    this.setStyleMouseOver(this._getEventElement(event));
  },

  listMouseOut: function(event, index) {

    if (!this.suggestList) return;

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
    
    // last action
    if(this.wekoBlockId.length>0){
      if(this.tmp != null){
        if(this.tmp.handle != null || this.tmp.name != null || this.tmp.email != null){
            repositoryCls['_'+this.wekoBlockId].repositoryItemEditFillContributor(this.tmp.handle, this.tmp.name, this.tmp.email);
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
SuggestContributor.LocalMulti = function() {
  this.initialize.apply(this, arguments);
};
SuggestContributor.copyProperties(SuggestContributor.LocalMulti.prototype, SuggestContributor.Local.prototype);

SuggestContributor.LocalMulti.prototype.delim = ' '; // delimiter

SuggestContributor.LocalMulti.prototype.keyEventReturn = function() {

  this.clearSuggestArea();
  this.input.value += this.delim;
  this.moveEnd();
};

SuggestContributor.LocalMulti.prototype.keyEventOther = function(event) {

  if (event.keyCode == SuggestContributor.Key.TAB) {
    // fix
    if (this.suggestList && this.suggestList.length != 0) {
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

SuggestContributor.LocalMulti.prototype.listClick = function(event, index) {

  this.changeUnactive();
  this.activePosition = index;
  this.changeActive(index);

  this.input.value += this.delim;
  this.moveEnd();
};

SuggestContributor.LocalMulti.prototype.getInputText = function() {

  var pos = this.getLastTokenPos();

  if (pos == -1) {
    return this.input.value;
  } else {
    return this.input.value.substr(pos + 1);
  }
};

SuggestContributor.LocalMulti.prototype.setInputText = function(text) {

  var pos = this.getLastTokenPos();

  if (pos == -1) {
    this.input.value = text;
  } else {
    this.input.value = this.input.value.substr(0 , pos + 1) + text;
  }
};

SuggestContributor.LocalMulti.prototype.getLastTokenPos = function() {
  return this.input.value.lastIndexOf(this.delim);
};

