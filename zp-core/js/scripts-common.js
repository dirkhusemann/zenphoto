function toggle(x) {
	xTog = document.getElementById(x);
	xState = xTog.style.display;
	// if(xState=='none') xState = 'table';
	if(xState=='none') xState = '';
	else xState = 'none';
	xTog.style.display = xState;
}