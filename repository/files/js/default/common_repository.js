// --------------------------------------------------------------------
//
// $Id: common_repository.js 3131 2011-01-28 11:36:33Z haruka_goto $
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
//	2008/03/05 Tatsuki Taniguchi
//_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/

//--------------------------------------------------------------
//	Get X-coordinates of left of element
//		element : HTMLElement
function getElementLeft(element)
{
  var left=0;
  var parent = element.offsetParent-element.scrollLeft;
  if ( parent ) left=getElementLeft(parent);
  left+=element.offsetLeft;
  return left;
}

//--------------------------------------------------------------
//	Get Y-coordinates of top of element
//		element : HTMLElement
function getElementTop(element)
{
 var top=0;
 var parent = element.offsetParent-element.scrollTop;
 if ( parent ) top=getElementTop(parent);
 top+=element.offsetTop;
 return top;
}

//--------------------------------------------------------------
//	Get coordinates of top of element
//		element : HTMLElement
function getElementTopLeft(element)
{

	var a=[]; var b=[];
	a[0]=element.offsetTop-element.scrollTop;
	a[1]=element.offsetLeft-element.scrollLeft;
	var parent = element.offsetParent;
	if ( parent ) {
		b=getElementTopLeft(parent);
		a[0]+=b[0];a[1]+=b[1];
	}
	else {
		a[0] += window.pageYOffset
			|| document.documentElement.scrollTop
			|| document.body.scrollTop
			|| 0;
		a[1] += window.pageXOffset
			|| document.documentElement.scrollLeft
			|| document.body.scrollLeft
			|| 0;

	}
	return a;
}

//--------------------------------------------------------------
//	Get bounding-box of element
//		element : HTMLElement
function getElementBounds(element){
	var box=[];
	var tl = getElementTopLeft(element);
	
	// cross browser 2008/07/08 Y.Nakao --start--
	// Mozilla
	if(window.scrollX){
		box[0] = tl[0]+window.scrollX;
	}
	// Opera, NN4
	else if(window.pageXOffset){
		box[0] = tl[0]+window.pageXOffset;
	}
	// IE
	else {
		box[0] = tl[0]+document.documentElement.scrollLeft;
	}
	
	// Mozilla
	if(window.scrollY){
		box[1] = tl[1]+window.scrollY;
	}
	// Opera, NN4
	else if(window.pageYOffset){
		box[1] = tl[1]+window.pageYOffset;
	}
	// IE
	else {
		box[1] = tl[1]+document.documentElement.scrollTop;
	}
	// cross browser 2008/07/08 Y.Nakao --end--
	
	box[2] = tl[0]+element.offsetHeight;
	box[3] = tl[1]+element.offsetWidth;
	
	return box;
}
//--------------------------------------------------------------
//	Check whether the point is over the element.
//		element : HTMLElement
//		cx : x
//		cy : y
function isObjOver(element,cx,cy)
{
	var box = getElementBounds(element);
	var top = box[0];
	var left = box[1];
	var bottom = box[2];
	var right = box[3];
	
	// on the element
	if (left<cx && cx<right && top<cy && cy<bottom ) {
		return true;
	}
	else {
		return false;
	}
}

//--------------------------------------------------------------
// Create button
function gpButton(src,href,target){
	var str="";
	str += '<a href="'+href+'" target="'+target+'">';
//	str += '<a class="button1" href="'+href+'" target="'+target+'">';
//	str += '<img class="button1" src="'+src+'" ';
//	str += '<img src="'+src+'" ';
	str += '<img class="button1_out" src="'+src+'" ';
	str += 'onmouseover="this.className=\'button1_over\';" ';
	str += 'onmouseout="this.className=\'button1_out\';" ';
	str += '/>';
	str += '</a>';
	document.write(str);
}

//--------------------------------------------------------------
// Debug
// Replace '$("dumparea").innerHTML' with str
function dump(str){
 var div = $("dumparea");
 if ( div ) {
  div.innerHTML += '<br/>'+str;
 }
}


//--------------------------------------------------------------
// Debug
// Clear '$("dumparea").innerHTML'
function dumpClear(){
 var div = $("dumparea");
 div.innerHTML += "";
}


