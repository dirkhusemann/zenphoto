// Esta es para llamado de datos remotos via xmlHttpRequest

function datosServidor() {
};
datosServidor.prototype.iniciar = function() {
	try {
		// Mozilla / Safari
		this._xh = new XMLHttpRequest();
	} catch (e) {
		// Explorer
		var _ieModelos = new Array(
		'MSXML2.XMLHTTP.5.0',
		'MSXML2.XMLHTTP.4.0',
		'MSXML2.XMLHTTP.3.0',
		'MSXML2.XMLHTTP',
		'Microsoft.XMLHTTP'
		);
		var success = false;
		for (var i=0;i < _ieModelos.length && !success; i++) {
			try {
				this._xh = new ActiveXObject(_ieModelos[i]);
				success = true;
			} catch (e) {
				// Implementar manejo de excepciones
			}
		}
		if ( !success ) {
			// Implementar manejo de excepciones, mientras alerta.
			return false;
		}
		return true;
	}
}

datosServidor.prototype.ocupado = function() {
	estadoActual = this._xh.readyState;
	return (estadoActual && (estadoActual < 4));
}

datosServidor.prototype.procesa = function() {
	if (this._xh.readyState == 4 && this._xh.status == 200) {
		this.procesado = true;
	}
}

datosServidor.prototype.enviar = function(urlget,datos) {
	if (!this._xh) {
		this.iniciar();
	}
	if (!this.ocupado()) {
		this._xh.open("GET",urlget,false);
		this._xh.send(datos);
		if (this._xh.readyState == 4 && this._xh.status == 200) {
			return this._xh.responseText;
		}
		
	}
	return false;
}


// Este es un acceso rapido, le paso la url y el div a cambiar
function _gr(reqseccion,divcont) {
	remotos = new datosServidor;
	nt = remotos.enviar(reqseccion,"");
	document.getElementById(divcont).innerHTML = nt;
}



//Estas dos son para guardar

var urlBase = "/update.php?";

function rateImg(rating,imgId,votes,values,path)  {
		votes = votes + 1;
		values = (values+rating)/votes;
		values = values*10;
		values = Math.round(values);
		values = values/10;	
		remotos = new datosServidor;
		nt = remotos.enviar(path+'/imagerating/update.php?rating='+rating+'&imgId='+imgId);
		document.getElementById('vote').innerHTML="Rating: "+values+" (Total votes: "+votes+")<br />Thanks for voting!";
		rating = rating * 25;
		document.getElementById('currentrating').innerHTML=" ";
		document.getElementById('current-rating').style.width = rating+'px';
}