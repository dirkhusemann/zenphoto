function toggle(x) {
	var xTog = document.getElementById(x);
	var xState = xTog.style.display;
	if (xState == 'none') { 
		xState = ''; 
	} else { 
		xState = 'none'; 
	}
	xTog.style.display = xState;
}
function vtoggle(x) {
	var xTog = document.getElementById(x);
	var xIndex = xTog.style.visibility;
	if (xIndex == 'hidden') { 
		xIndex = 'visible'; 
		xTog.style.position='relative';
		xTog.style.left='auto';
		xTog.style.top='auto';
		if(!map) {
			showmap();
		}
		map.checkResize();
	} else { 
		xIndex = 'hidden'; 
		xTog.style.position='absolute';
		xTog.style.left='-3000px';
		xTog.style.top='-3000px';
	}
	xTog.style.visibility = xIndex;
}
