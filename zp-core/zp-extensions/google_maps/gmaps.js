// Javascript functions for the Google Maps plugin
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

function createMarker(point, myHtml) {
	var marker = new GMarker(point);
	GEvent.addListener(marker, "click", function() {
		map.openInfoWindowHtml(point, myHtml);
		});
	return marker;
}
