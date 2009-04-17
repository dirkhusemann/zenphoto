<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()-3600*24*30) . " GMT"); // Date in the past
header("Expires: " . gmdate("D, d M Y H:i:s", time()+3600*24*60) . " GMT"); // Don't expire for 60 days
header("Cache-Control: max-age=86400, s-maxage=86400, proxy-revalidate, must-revalidate");
header("Content-Type: application/x-javascript");

if (!defined('WEBPATH')) { 
	$const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
	$const_webpath = str_replace("\\", '/', $const_webpath);
	if ($const_webpath == '/') $const_webpath = '';
	define('WEBPATH', $const_webpath);
 }
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }

if(!function_exists("gettext")) {
	// load the drop-in replacement library
	require_once(dirname(dirname(__FILE__)).'/lib-gettext/gettext.inc');
}
?>

/* Common javascripts and localized strings for Zenphoto */

var zppath =  "<?php echo WEBPATH.'/'.ZENFOLDER; ?>";

var zpstrings = {
	/* Used in jquery.editinplace.js */
	'Save' : "<?php echo gettext('Save'); ?>",
	'Cancel' : "<?php echo gettext('Cancel'); ?>",
	'Saving' : "<?php echo gettext('Saving'); ?>",
	'ClickToEdit' : "<?php echo gettext('Click to edit...'); ?>",
	/* Used in thickbox.js */
	'Test' : "<?php echo gettext('Test'); ?>",
	'Close' : "<?php echo gettext('Close'); ?>",
	'close' : "<?php echo gettext('close'); ?>",
	'orEscKey' : "<?php echo gettext('or Esc Key'); ?>",
	'Next' : "<?php echo gettext('Next'); ?>",
	'Prev' : "<?php echo gettext('Prev'); ?>",
	'Image' : "<?php echo gettext('Image'); ?>",
	'of' : "<?php echo gettext('of'); ?>"
};

// Toggle element display
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

