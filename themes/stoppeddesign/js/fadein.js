/*

Better(?) Image fader (C)2004 Patrick H. Lauke aka redux

Inspired by Richard Rutter / Clagnut http://www.clagnut.com/blog/1299/ 

Original concept and code adapted from Couloir http://www.couloir.org/ 

preInit "Scheduler" idea by Cameron Adams aka The Man in Blue
http://www.themaninblue.com/writing/perspective/2004/09/29/ 

*/

/* general variables */

var fadeTargetId = 'photo_container'; /* change this to the ID of the fadeable object */
var	fadeTarget;
preInit();

/* functions */

function preInit() {
	/* an inspired kludge that - in most cases - manages to initially hide the image
	   before even onload is triggered (at which point it's normally too late, and a nasty flash
	   occurs with non-cached images) */
	if ((document.getElementById)&&(fadeTarget=document.getElementById(fadeTargetId))) {
		fadeTarget.style.visibility = "hidden";
		clearTimeout(preInitTimer);
	} else {
		preInitTimer = setTimeout("preInit()",2);
	}
}

function fadeInit() {
	if (document.getElementById) {
		/* get a handle on the fadeable object, to make code later more manageable */
		preInit(); /* shouldn't be necessary, but IE can sometimes get ahead of itself and trigger fadeInit first */
		/* set the initial opacity in a (hopefully) cross browser way
		   notice that because of the way the image is in front, and not obfuscated
		   by another object we need to "fade out", i don't need a fallback mechanism
		   to show/hide the covering object...the image is just there, full stop */
		if (fadeTarget.style.MozOpacity!=null) {  
			/* Mozilla's pre-CSS3 proprietary rule */
			fadeTarget.style.MozOpacity = 0;
		} else if (fadeTarget.style.opacity!=null) {
			/* CSS3 compatible */
			fadeTarget.style.opacity = 0;
		} else if (fadeTarget.style.filter!=null) {
			/* IE's proprietary filter */
			fadeTarget.style.filter = "alpha(opacity=0)";
		}
		/* make the object visible again */
		fadeTarget.style.visibility = 'visible';
		window.setTimeout("fadeIn(0)", 500);
	}
}

function fadeIn(opacity) {
	if (fadeTarget) {
		if (opacity <= 100) {
			if (fadeTarget.style.MozOpacity!=null) {
				/* Mozilla's pre-CSS3 proprietary rule */
				fadeTarget.style.MozOpacity = (opacity/100)-.001;
				/* the .001 fixes a glitch in the opacity calculation which normally results in a flash when reaching 1 */
			} else if (fadeTarget.style.opacity!=null) {
				/* CSS3 compatible */
				fadeTarget.style.opacity = (opacity/100)-.001;
			} else if (fadeTarget.style.filter!=null) {
				/* IE's proprietary filter */
				fadeTarget.style.filter = "alpha(opacity="+opacity+")";
				/* worth noting: IE's opacity needs values in a range of 0-100, not 0.0 - 1.0 */ 
			}
			opacity += 10;
			window.setTimeout("fadeIn("+opacity+")", 0);
		}
	}
}

/* initialise fader by hiding image object first */
addEvent (window,'load',fadeInit)



/* 3rd party helper functions */

/* addEvent handler for IE and other browsers */
function addEvent(elm, evType, fn, useCapture) 
// addEvent and removeEvent
// cross-browser event handling for IE5+,  NS6 and Mozilla
// By Scott Andrew
{
 if (elm.addEventListener){
   elm.addEventListener(evType, fn, useCapture);
   return true;
 } else if (elm.attachEvent){
   var r = elm.attachEvent("on"+evType, fn);
   return r;
 }
} 
