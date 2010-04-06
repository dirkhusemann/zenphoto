<?php // an additional utilites for the sitemap-extended.php plugin
//if(getOption('zp_plugin_sitemap-extended')) {
	define('OFFSET_PATH', 3);
	require_once(dirname(dirname(__FILE__)).'/folder-definitions.php');
	require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
	require_once(dirname(dirname(__FILE__)).'/admin-globals.php');
	require_once(dirname(dirname(__FILE__)).'/'.PLUGIN_FOLDER.'/sitemap-extended.php');
	$button_text = gettext('Purge sitemap cache HALLO');
	$button_hint = gettext('Clear the static sitemap cache. It will be recached if requested.');
	$button_icon = 'images/edit-delete.png';
	$button_rights = MANAGE_ALL_ALBUM_RIGHTS;
	clearSitemapCache();
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=external&msg='.gettext('sitemap cache cleared.'));
	exit();
//}
?>