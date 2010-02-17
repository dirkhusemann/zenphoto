<?php
/**
 * Initialize globals for Admin
 * @package admin
 */

// force UTF-8 Ø

if (session_id() == '') {
	// force session cookie to be secure when in https
	if(secureServer()) {
		$CookieInfo=session_get_cookie_params();
		session_set_cookie_params($CookieInfo['lifetime'],$CookieInfo['path'], $CookieInfo['domain'],TRUE);
	}
	session_start();
}

$sortby = array(gettext('Filename') => 'filename',
								gettext('Date') => 'date',
								gettext('Title') => 'title',
								gettext('ID') => 'id',
								gettext('Filemtime') => 'mtime'
								);
									
// setup sub-tab arrays for use in dropdown
$zenphoto_tabs = array();
if (($_zp_loggedin & (OVERVIEW_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['home'] = array('text'=>gettext("overview"),
						'link'=>WEBPATH."/".ZENFOLDER.'/admin.php',
						'subtabs'=>NULL);
}

if (($_zp_loggedin & (UPLOAD_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['upload'] = array('text'=>gettext("upload"),
							'link'=>WEBPATH."/".ZENFOLDER.'/admin-upload.php',
							'subtabs'=>NULL);
}

if (($_zp_loggedin & (ALBUM_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['edit'] = array('text'=>gettext("albums"),
							'link'=>WEBPATH."/".ZENFOLDER.'/admin-edit.php',
							'subtabs'=>NULL,
							'default'=>'albuminfo');
}

if (getOption('zp_plugin_zenpage') && ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
	$zenphoto_tabs['pages'] = array('text'=>gettext("pages"),
							'link'=>WEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/zenpage/admin-pages.php',
							'subtabs'=>NULL);

	$zenphoto_tabs['articles'] = array('text'=>gettext("news"),
							'link'=>WEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/zenpage/admin-news-articles.php',
							'subtabs'=>array(	gettext('articles')=>PLUGIN_FOLDER.'/zenpage/admin-news-articles.php?page=news&amp;tab=articles', 
																gettext('categories')=>PLUGIN_FOLDER.'/zenpage/admin-categories.php?page=news&amp;tab=categories'),
																'default'=>'articles');
}

if (($_zp_loggedin & (TAGS_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['tags'] = array('text'=>gettext("tags"),
							'link'=>WEBPATH."/".ZENFOLDER.'/admin-tags.php',
							'subtabs'=>NULL);
}

if (($_zp_loggedin & (COMMENT_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['comments'] = array('text'=>gettext("comments"),
							'link'=>WEBPATH."/".ZENFOLDER.'/admin-comments.php',
							'subtabs'=>NULL);
}

$zenphoto_tabs['users'] = array('text'=>gettext("admin"),
 						'link'=>WEBPATH."/".ZENFOLDER.'/admin-users.php?page=users',
 						'subtabs'=>NULL);

$subtabs = array();
$optiondefault='';
if (!(($_zp_loggedin == ADMIN_RIGHTS) || $_zp_reset_admin)) {
	if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
		$optiondefault='&amp;tab=general';
		$subtabs[gettext("general")] = 'admin-options.php?page=options&amp;tab=general';
		$subtabs[gettext("gallery")] = 'admin-options.php?page=options&amp;tab=gallery';
		$subtabs[gettext("image")] = 'admin-options.php?page=options&amp;tab=image';
		$subtabs[gettext("comment")] = 'admin-options.php?page=options&amp;tab=comments';
	}
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		if (empty($optiondefault)) $optiondefault='&amp;tab=plugin';
		$subtabs[gettext("plugin")] = 'admin-options.php?page=options&amp;tab=plugin';
	}
	if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
		$subtabs[gettext("search")] = 'admin-options.php?page=options&amp;tab=search';
	}
	if ($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) {
		if (empty($optiondefault)) $optiondefault='&amp;tab=theme';
		$subtabs[gettext("theme")] = 'admin-options.php?page=options&amp;tab=theme';
	}
	if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
		$subtabs[gettext("rss")] = 'admin-options.php?page=options&amp;tab=rss';
	}
}
if (!empty($subtabs)) {
	$zenphoto_tabs['options'] = array('text'=>gettext("options"),
			'link'=>WEBPATH."/".ZENFOLDER.'/admin-options.php?page=options'.$optiondefault, 
			'subtabs'=>$subtabs,
			'default'=>'gallery');
}

if (($_zp_loggedin & (THEMES_RIGHTS | ADMIN_RIGHTS))) {
	$zenphoto_tabs['themes'] = array('text'=>gettext("themes"),
						'link'=>WEBPATH."/".ZENFOLDER.'/admin-themes.php',
						'subtabs'=>NULL);
}

if (($_zp_loggedin & ADMIN_RIGHTS)) {
	$zenphoto_tabs['plugins'] = array('text'=>gettext("plugins"),
							'link'=>WEBPATH."/".ZENFOLDER.'/admin-plugins.php',
							'subtabs'=>NULL);
}

if (($_zp_loggedin & ADMIN_RIGHTS)) {
	$filelist = safe_glob(SERVERPATH . "/" . DATA_FOLDER . '/*.txt');
	if (count($filelist)>0) {
		$zenphoto_tabs['logs'] = array(	'text'=>gettext("logs"),
													'link'=>WEBPATH."/".ZENFOLDER.'/admin-logs.php?page=logs',
													'subtabs'=>NULL);
	}
	unset($filelist);
}
?>
