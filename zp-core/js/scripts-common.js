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
