<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }

header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()-3600*24*30) . " GMT"); // Date in the past
header("Expires: " . gmdate("D, d M Y H:i:s", time()+3600*24*60) . " GMT"); // Don't expire for 60 days
header("Cache-Control: max-age=86400, s-maxage=86400, proxy-revalidate, must-revalidate");
header("Content-Type: application/x-javascript");

require_once('../functions.php');

?>

<?php echo "var zppath = \"/".ZENFOLDER."\";"; ?>

var zpstrings = {
	'Test' : "<?php echo gettext('Test'); ?>",
	'ClickToAddATitle' : "<?php echo gettext('Click to add a title...'); ?>",
	'ClickToEditTitle' : "<?php echo gettext('Click to edit title...'); ?>",
	'ClickToAddTags' : "<?php echo gettext('Click to add tags...'); ?>",
	'ClickToEditTags' : "<?php echo gettext('Click to edit tags...'); ?>",
	'ClickToAddADescription' : "<?php echo gettext('Click to add a description...'); ?>",
	'ClickToEditDescription' : "<?php echo gettext('Click to edit description'); ?>",
	'Save' : "<?php echo gettext('save'); ?>",
	'Saving' : "<?php echo gettext('Saving'); ?>",
	'Cancel' : "<?php echo gettext('cancel'); ?>",
	'CurrentlyEditingSomethingElse' : "<?php echo gettext('Currently editing something else, save or cancel to edit this.'); ?>",
	'Close' : "<?php echo gettext('Close'); ?>",
	'close' : "<?php echo gettext('close'); ?>",
	'orEscKey' : "<?php echo gettext('or Esc Key'); ?>",
	'Next' : "<?php echo gettext('Next'); ?>",
	'Prev' : "<?php echo gettext('Prev'); ?>",
	'Image' : "<?php echo gettext('Image'); ?>",
	'of' : "<?php echo gettext('of'); ?>"
};