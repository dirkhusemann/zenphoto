// JS support for general Zenphoto use

function toggle(x) {
	jQuery('#'+x).toggle();
}

function confirmDeleteAlbum(url, message1, message2) {
	if (confirm(message1)) {
		if (confirm(message2)) {
			window.location = url;
		}
	}
}

function confirmDeleteImage(url, message) {
	if (confirm(message)) {
		window.location = url;
	}
}

function launchScript(script, params) {
	window.location = script+'?'+params.join('&');
}