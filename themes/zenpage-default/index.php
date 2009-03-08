<?php
$titlelink = getOption("zenpage_homepage");
$_zp_current_zenpage_page = NULL;
if($titlelink != "none" AND $_zp_gallery_page == "index.php") {
	$sql = 'SELECT `id` FROM '.prefix('zenpage_pages').' WHERE `titlelink`="'.$titlelink.'"';
	$result = query_single_row($sql);
	if (is_array($result)) {
		$_zp_current_zenpage_page = new ZenpagePage($titlelink);
	}
}
if (is_null($_zp_current_zenpage_page)) {
	include ('indexpage.php');
} else {  // switch to a news page
	$_zp_gallery_page = 'pages.php';
	// TODO: zenpage really should not rely on these
	$_GET['p'] = 'pages';
	$_GET['title'] = $titlelink;
	$ishomepage = true;
	include ($_zp_gallery_page);
}
?>