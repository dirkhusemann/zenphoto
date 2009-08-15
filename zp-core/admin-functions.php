<?php
/**
 * support functions for Admin
 * @package admin
 */

// force UTF-8 Ø


if (session_id() == '') session_start();

require_once(dirname(__FILE__).'/functions.php');
$_zp_admin_ordered_taglist = NULL;
$_zp_admin_LC_taglist = NULL;
$_zp_admin_album_list = null;
define('TEXTAREA_COLUMNS', 50);
define('TEXT_INPUT_SIZE', 48);
define('TEXTAREA_COLUMNS_SHORT', 32);
define('TEXT_INPUT_SIZE_SHORT', 30);


$sortby = array(gettext('Filename') => 'filename',
								gettext('Date') => 'date',
								gettext('Title') => 'title',
								gettext('ID') => 'id',
								gettext('Filemtime') => 'mtime'
								);
									
if (OFFSET_PATH) {									
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
								'subtabs'=>array(	gettext('articles')=>PLUGIN_FOLDER.'/zenpage/admin-news-articles.php?page=news&tab=articles', 
																	gettext('categories')=>PLUGIN_FOLDER.'/zenpage/admin-categories.php?page=news&tab=categories'),
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
 							'link'=>WEBPATH."/".ZENFOLDER.'/admin-options.php?page=users&tab=users',
 							'subtabs'=>NULL);
 	
	$subtabs = array();
	$optiondefault='';
	if (!(($_zp_loggedin == ADMIN_RIGHTS) || $_zp_reset_admin)) {
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
			$optiondefault='&tab=general';
			$subtabs[gettext("general")] = 'admin-options.php?page=options&tab=general';
			$subtabs[gettext("gallery")] = 'admin-options.php?page=options&tab=gallery';
			$subtabs[gettext("image")] = 'admin-options.php?page=options&tab=image';
			$subtabs[gettext("comment")] = 'admin-options.php?page=options&tab=comments';
		}
		if ($_zp_loggedin & ADMIN_RIGHTS) {
			if (empty($optiondefault)) $optiondefault='&tab=plugin';
			$subtabs[gettext("plugin")] = 'admin-options.php?page=options&tab=plugin';
		}
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
			$subtabs[gettext("search")] = 'admin-options.php?page=options&tab=search';
		}
		if ($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) {
			if (empty($optiondefault)) $optiondefault='&tab=theme';
			$subtabs[gettext("theme")] = 'admin-options.php?page=options&tab=theme';
		}
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
			$subtabs[gettext("rss")] = 'admin-options.php?page=options&tab=rss';
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
	
}

/**
 * Print the footer <div> for the bottom of all admin pages.
 *
 * @param string $addl additional text to output on the footer.
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminFooter($addl='') {
	?>
	<div id="footer">
	<a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>.">zen<strong>photo</strong></a>
	version 
	<?php echo ZENPHOTO_VERSION.' ['.ZENPHOTO_RELEASE.']';
	if (!empty($addl)) {
		echo ' | '. $addl;
	}
	?>
	 | <a href="http://www.zenphoto.org/support/" title="<?php echo gettext('Forum'); ?>">Forum</a> 
	 | <a href="http://www.zenphoto.org/trac/" title="Trac">Trac</a> 
	 | <a href="http://www.zenphoto.org/category/news/changelog/" title="<?php echo gettext('View Changelog'); ?>"><?php echo gettext('Changelog'); ?></a>
	 <br />
	<?php	printf(gettext('Server date: %s'),date('Y-m-d H:i:s')); 	?>
	</div>
  <?php
}

function datepickerJS($path) {
	?>
	<script src="<?php echo $path;?>js/jqueryui/jquery.ui.zenphoto.js" type="text/javascript"></script>
	<?php
	$lang = str_replace('_', '-',getOption('locale'));
	if (!file_exists($path.'js/jqueryui/i18n/ui.datepicker-'.$lang.'.js')) {
		$lang = substr($lang, 0, 2);
		if (!file_exists($path.'js/jqueryui/i18n/ui.datepicker-'.$lang.'.js')) {
			$lang = '';
		}
	}
	if (!empty($lang)) {
		?>
		<script src="<?php echo $path;?>js/jqueryui/i18n/ui.datepicker-<?php echo $lang; ?>.js" type="text/javascript"></script>
		<?php
	}
	?>
	<link rel="stylesheet" href="<?php echo $path; ?>js/jqueryui/ui.zenphoto.css" type="text/css" />
	<script type="text/javascript">
		$.datepicker.setDefaults({ dateFormat: 'yy-mm-dd 00:00:00' });
	</script>
	<?php
}

/**
 * Print the header for all admin pages. Starts at <DOCTYPE> but does not include the </head> tag,
 * in case there is a need to add something further.
 *
 * @param string $path path to the admin files for use by plugins which are not located in the zp-core
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminHeader($path='', $tinyMCE=true) {
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
	header('Content-Type: text/html; charset=' . getOption('charset'));
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title><?php echo gettext("zenphoto administration") ?></title>
	<link rel="stylesheet" href="<?php echo $path; ?>admin.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $path; ?>js/toggleElements.css" type="text/css" />
	<script src="<?php echo $path; ?>js/jquery.js" type="text/javascript"></script>
	<script src="<?php echo $path; ?>js/zenphoto.js.php" type="text/javascript" ></script>
	<?php datepickerJS($path); ?>
	<script src="<?php echo $path; ?>js/admin.js" type="text/javascript" ></script>
	<script src="<?php echo $path; ?>js/jquery.dimensions.js" type="text/javascript"></script>
	<script src="<?php echo $path; ?>js/jquery.tooltip.js" type="text/javascript"></script>
	<script src="<?php echo $path; ?>js/thickbox.js" type="text/javascript"></script>
	<link rel="stylesheet" href="<?php echo $path; ?>js/thickbox.css" type="text/css" />
	<script language="javascript" type="text/javascript">
		jQuery(function( $ ){
			$("#fade-message").fadeTo(5000, 1).fadeOut(1000);
			$("#fade-message2").fadeTo(5000, 1).fadeOut(1000);
			$('.tooltip').tooltip({
				left: -80
			});
			});
	</script>
	<?php
/* enable for drop-down tabs (not working as yet)
	<script type="text/javascript">
	var timeout         = 500;
	var closetimer		= 0;
	var ddmenuitem      = 0;
	
	function jsddm_open()
	{	jsddm_canceltimer();
		jsddm_close();
		ddmenuitem = $(this).find('ul').eq(0).css('visibility', 'visible');}
	
	function jsddm_close()
	{	if(ddmenuitem) ddmenuitem.css('visibility', 'hidden');}
	
	function jsddm_timer()
	{	closetimer = window.setTimeout(jsddm_close, timeout);}
	
	function jsddm_canceltimer()
	{	if(closetimer)
		{	window.clearTimeout(closetimer);
			closetimer = null;}}
	
	$(document).ready(function()
	{	$('#jsddm > li').bind('mouseover', jsddm_open);
		$('#jsddm > li').bind('mouseout',  jsddm_timer);});
	
	document.onclick = jsddm_close;
	</script>
*/
	?>
	<?php
	if ($tinyMCE && file_exists(dirname(__FILE__).'/js/editor_config.js.php')) require_once(dirname(__FILE__).'/js/editor_config.js.php');	
}

/**
 * Print a link to a particular album edit function.
 *
 * @param $param The album, etc parameters.
 * @param $text	Text for the hyperlink.
 * @param $title  Optional title attribute for the hyperlink. Default is NULL.
 * @param $class  Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id		Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAlbumEditLinks($param, $text, $title=NULL, $class=NULL, $id=NULL) {
	printLink(WEBPATH.'/'.ZENFOLDER."/admin-edit.php?page=edit". $param, $text, $title, $class, $id);
}

/**
 * Print a link to the album sorting page. We will remain within the Edit tab of the admin section.
 *
 * @param $album The album name to sort.
 * @param $text  Text for the hyperlink.
 * @param $title Optional title attribute for the hyperlink. Default is NULL.
 * @param $class Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id	 Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printSortLink($album, $text, $title=NULL, $class=NULL, $id=NULL) {
	printLink(WEBPATH . "/" . ZENFOLDER . "/admin-albumsort.php?page=edit&album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
}

/**
 * Print a link that will take the user to the actual album. E.g. useful for View Album.
 *
 * @param $album The album to view.
 * @param $text  Text for the hyperlink.
 * @param $title Optional title attribute for the hyperlink. Default is NULL.
 * @param $class Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id	 Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printViewLink($album, $text, $title=NULL, $class=NULL, $id=NULL) {
	printLink(WEBPATH . "/index.php?album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
}

/**
 * Print the thumbnail for a particular Image.
 *
 * @param $image object The Image object whose thumbnail we want to display.
 * @param $class string Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id	 string Optional id attribute for the hyperlink.  Default is NULL.
 * @param $bg    
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */

function adminPrintImageThumb($image, $class=NULL, $id=NULL) {
	echo "\n  <img class=\"imagethumb\" id=\"id_". $image->id ."\" src=\"" . $image->getThumb() . "\" alt=\"". html_encode($image->getTitle()) . "\" title=\"". html_encode($image->getTitle()) . " (". html_encode($image->getFileName()) . ")\"" .
	((getOption('thumb_crop')) ? " width=\"".getOption('thumb_crop_width')."\" height=\"".getOption('thumb_crop_height')."\"" : "") .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . " />";
}

/**
 * Print the login form for ZP. This will take into account whether mod_rewrite is enabled or not.
 *
 * @param string $redirect URL to return to after login
 * @param bool $logo set to true to display the ADMIN zenphoto logo.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printLoginForm($redirect=null, $logo=true) {
	global $_zp_login_error, $_zp_captcha;
	if (is_null($redirect)) { $redirect = "/" . ZENFOLDER . "/admin.php"; }
	if (isset($_POST['user'])) {
		$requestor = sanitize($_POST['user'], 3);
	} else {
		$requestor = '';
	}
	if (empty($requestor)) {
		if (isset($_GET['ref'])) {
			$requestor = sanitize($_GET['ref'], 0);
		}
	}
	$star = '';
	$admins = getAdministrators();
	$mails = array();	
	if (!empty($requestor)) {
		$user = null;
		foreach ($admins as $tuser) {
			if ($tuser['user'] == $requestor && !empty($tuser['email'])) {
				$star = '*';
				break;
			}
		}
	}
	$user = array_shift($admins);
	if ($user['email']) {
		$star = '*';
	}
	?>
	<div id="loginform">
	<?php
	if ($logo) echo "<p><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";
	switch ($_zp_login_error) {
		case 1:
			?>
			<div class="errorbox" id="message"><h2><?php echo gettext("There was an error logging in."); ?></h2><?php echo gettext("Check your username and password and try again.");?></div>
			<?php
			break;
		case 2:
			?>
			<div class="messagebox" id="fade-message">
			<h2><?php echo gettext("A reset request has been sent."); ?></h2>
			</div>
			<?php
			break;
		default:
			if (!empty($_zp_login_error)) {
				?>
				<div class="errorbox" id="fade-message">
				<h2><?php echo $_zp_login_error; ?></h2>
				</div>
				<?php
			}
			break;
	}
	?>
	<form name="login" action="#" method="post">
	<input type="hidden" name="login" value="1" />
	<input type="hidden" name="redirect" value="<?php echo $redirect; ?>" />

	<table>
	<tr><td align="left"><h2><?php echo gettext("Login"); ?>&nbsp;</h2></td><td><input class="textfield" name="user" type="text" size="20" value="<?php echo $requestor; ?>" /></td></tr>
	<tr><td align="left"><h2><?php echo gettext("Password").$star; ?></h2></td><td><input class="textfield" name="pass" type="password" size="20" /></td></tr>
	<?php 
	if ($star == '*') {
		$captchaCode = $_zp_captcha->generateCaptcha($img);
		$html = "<input type=\"hidden\" name=\"code_h\" value=\"" . $captchaCode . "\"/><label for=\"code\"><img src=\"" . $img . "\" alt=\"Code\" align=\"bottom\"/></label>";
	?>	
	<tr><td colspan="2">
		<?php echo "\n		".sprintf(gettext("*Enter %s to email a password reset."), $html); ?>
		</td></tr>
	<?php } ?>
	<tr><td></td><td colspan="2">
	<div class="buttons">
	<button type="submit" value="<?php echo gettext("Log in"); ?>" /><img src="images/pass.png" alt="" /><?php echo gettext("Log in"); ?></button>
	<button type="reset" value="<?php echo gettext("Reset"); ?>" /><img src="images/reset.png" alt="" /><?php echo gettext("Reset"); ?></button>
	</div>
	</td></tr>
	</table>
	</form>
	</div>
<?php 
} 


/**
 * Print the html required to display the ZP logo and links in the top section of the admin page.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printLogoAndLinks() {
	global $_zp_current_admin;
	?>
	<span id="administration"><img id="logo" src="<?php echo WEBPATH.'/'.ZENFOLDER; ?>/images/zen-logo.gif" title="<?php echo gettext('Zenphoto Administration'); ?>" align="absbottom" />
	<?php //echo gettext("Administration"); ?>
	</span>
	<?php
	echo "\n<div id=\"links\">";
	echo "\n  ";
	if (!is_null($_zp_current_admin)) {
		printf(gettext("Logged in as %s"), $_zp_current_admin['user']);
		echo " &nbsp; | &nbsp <a href=\"".WEBPATH."/".ZENFOLDER."/admin.php?logout\">".gettext("Log Out")."</a> &nbsp; | &nbsp; ";
	}
	echo "<a href=\"".WEBPATH."/index.php";
	if ($specialpage = getOption('custom_index_page')) {
		if (file_exists(SERVERPATH.'/'.THEMEFOLDER.'/'.getOption('current_theme').'/'.internalToFilesystem($specialpage).'.php')) {
			echo '?p='.$specialpage;
		}
	}
	echo "\">";
	$t = get_language_string(getOption('gallery_title'));
	if (!empty($t))	{
		printf(gettext("View Gallery: %s"), $t);
	} else {
		echo gettext("View Gallery");
	}
	echo "</a>";
	echo "\n</div>";
}

/**
 * Print the nav tabs for the admin section. We determine which tab should be highlighted
 * from the $_GET['page']. If none is set, we default to "home".
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printTabs($currenttab) {
	global $_zp_loggedin, $subtabs, $zenphoto_tabs;
	$zenphoto_tabs = zp_apply_filter('admin_tabs', $zenphoto_tabs, $currenttab);
	?>
	<ul class="nav" id="jsddm">
	<?php
	foreach ($zenphoto_tabs as $key=>$atab) {
		?>
		<li <?php if($currenttab == $key) echo 'class="current"' ?>>
			<a href="<?php echo $atab['link']; ?>"><?php echo $atab['text']; ?></a>
			<?php
/* also enable the javascript in printAdminHeader()
			if (is_array($atab['subtabs']) && $currenttab !== $key) {
				?>
				<ul id="subdropdownnav">
				<?php
				foreach ($atab['subtabs'] as $subtab=>$tablink) {
					?>
					<li><a href="<?php echo $tablink; ?>"><?php echo $subtab; ?></a></li>
					<?php
				}
				?>
				</ul>
			 	<?php		 	
			}
*/			
			?>
		</li>
 		<?php
	}
	?>
	</ul>
	<?php
}

function getSubtabs($tab, $default) {
	global $zenphoto_tabs;
	$tabs = $zenphoto_tabs[$tab]['subtabs'];
	if (!is_array($tabs)) return $default;
	if (isset($_GET['tab'])) {
		$current = sanitize($_GET['tab']);
	} else {
		if (isset($zenphoto_tabs[$tab]['default'])) {
			$current = $zenphoto_tabs[$tab]['default'];
		} else if (empty($default)) {
			$current = $tabs;
			$current = array_shift($current);
			$i = strrpos($current, 'tab=');
			$amp = strrpos($current, '&');
			if ($i===false) {
				$current = $default;
			} else {
				if ($amp > $i) {
					$current = substr($current, 0, $amp);
				}
				$current = substr($current, $i+4);
			}
		} else {
			$current = $default;
		}
	}
	return $current;
}

function printSubtabs($tab, $default=NULL) {
	global $zenphoto_tabs;
	$tabs = $zenphoto_tabs[$tab]['subtabs'];
	
	if (!is_array($tabs)) return $default;
	$current = getSubtabs($tab, $default);
	?>
	<ul class="subnav">
	<?php
	foreach ($tabs as $key=>$link) {
		$i = strrpos($link, 'tab=');
		$amp = strrpos($link, '&');
		if ($i===false) {
			$tab = '';
		} else {
			if ($amp > $i) {
				$source = substr($link, 0, $amp);
			} else {
				$source = $link;
			}
			$tab = substr($source, $i+4);
		}
		echo '<li'.(($current == $tab) ? ' class="current"' : '').'>'.
				 '<a href = "'.WEBPATH.'/'.ZENFOLDER.'/'.$link.'">'.$key.'</a></li>'."\n";
	}
	?>
	</ul>
	<?php
	return $current;
}

function checked($checked, $current) {
	if ( $checked == $current)
	echo ' checked="checked"';
}

function genAlbumUploadList(&$list, $curAlbum=NULL) {
	$gallery = new Gallery();
	$albums = array();
	if (is_null($curAlbum)) {
		$albumsprime = $gallery->getAlbums(0);
		foreach ($albumsprime as $album) { // check for rights
			if (isMyAlbum($album, UPLOAD_RIGHTS)) {
				$albums[] = $album;
			}
		}
	} else {
		$albums = $curAlbum->getSubAlbums(0);
	}
	if (is_array($albums)) {
		foreach ($albums as $folder) {
			$album = new Album($gallery, $folder);
			if (!$album->isDynamic()) {
				$list[$album->getFolder()] = $album->getTitle();
				genAlbumUploadList($list, $album);  /* generate for subalbums */
			}
		}
	}
}

function displayDeleted() {
	/* Display a message if needed. Fade out and hide after 2 seconds. */
	if (isset($_GET['ndeleted'])) {
		$ntdel = sanitize_numeric($_GET['ndeleted']);
		if ($ntdel <= 2) {
			$msg = gettext("Image");
		} else {
			$msg = gettext("Album");
			$ntdel = $ntdel - 2;
		}
		if ($ntdel == 2) {
			$msg = sprintf(gettext("%s failed to delete."),$msg);
			$class = 'errorbox';
		} else {
			$msg = sprintf(gettext("%s deleted successfully."),$msg);
			$class = 'messagebox';
		}
		echo '<div class="' . $class . '" id="fade-message">';
		echo  "<h2>" . $msg . "</h2>";
		echo '</div>';
	}
}

function setThemeOption($album, $key, $value) {
	if (is_null($album)) {
		setOption($key, $value);
	} else {
		$id = $album->id;
		$exists = query_single_row("SELECT `name`, `value`, `id` FROM ".prefix('options')." WHERE `name`='".mysql_real_escape_string($key)."' AND `ownerid`=".$id, true);
		if ($exists) {
			if (is_null($value)) {
				$sql = "UPDATE " . prefix('options') . " SET `value`=NULL WHERE `id`=" . $exists['id'];
			} else {
				$sql = "UPDATE " . prefix('options') . " SET `value`='" . mysql_real_escape_string($value) . "' WHERE `id`=" . $exists['id'];
			}
		} else {
			if (is_null($value)) {
				$sql = "INSERT INTO " . prefix('options') . " (name, value, ownerid) VALUES ('" . mysql_real_escape_string($key) . "',NULL,$id)";
			} else {
				$sql = "INSERT INTO " . prefix('options') . " (name, value, ownerid) VALUES ('" . mysql_real_escape_string($key) . "','" . mysql_real_escape_string($value) . "',$id)";
			}
		}
		$result = query($sql);
	}
}

function setBoolThemeOption($album, $key, $bool) {
	if ($bool) {
		$value = 1;
	} else {
		$value = 0;
	}
	setThemeOption($album, $key, $value);
}

function getThemeOption($album, $option) {
	if (is_null($album)) {
		return getOption($option);
	}
	$alb = 'options';
	$where = ' AND `ownerid`='.$album->id;
	if (empty($alb)) {
		return getOption($option);
	}
	$sql = "SELECT `value` FROM " . prefix($alb) . " WHERE `name`='" . mysql_real_escape_string($option) . "'".$where;
	$db = query_single_row($sql);
	if (!$db) {
		return getOption($option);
	}
	return $db['value'];
}

define ('CUSTOM_OPTION_PREFIX', '_ZP_CUSTOM_');
/**
 * Generates the HTML for custom options (e.g. theme options, plugin options, etc.)
 *
 * @param object $optionHandler the object to handle custom options
 * @param string $indent used to indent the option for nested options
 * @param object $album if not null, the album to which the option belongs
 * @param bool $hide set to true to hide the output (used by the plugin-options folding
 *
 * There are four type of custom options:
 * 		OPTION_TYPE_TEXTBOX:				a textbox
 * 		OPTION_TYPE_CLEAARTEXT:			a textbox, but no sanitization on save
 * 		OPTION_TYPE_CHECKBOX:				a checkbox
 * 		OPTION_TYPE_CUSTOM:					handled by $optionHandler->handleOption()
 * 		OPTION_TYPE_TEXTAREA:				a textarea
 * 		OPTION_TYPE_RADIO:					radio buttons (button names are in the 'buttons' index of the supported options array)
 * 		OPTION_TYPE_SELECTOR:				selector (selection list is in the 'selections' index of the supported options array)
 * 		OPTION_TYPE_CHECKBOX_ARRAY:	checkbox array (checkboxed list is in the 'checkboxes' index of the suppoprted options array.)
 * 		OPTION_TYPE_CHECKBOX_UL:		checkbox UL (checkboxed list is in the 'checkboxes' index of the suppoprted options array.)
 * 		OPTION_TYPE_COLOR_PICKER:		Color picker
 *
 * type 0 and 3 support multi-lingual strings.
 */
define('OPTION_TYPE_TEXTBOX',0);
define('OPTION_TYPE_CHECKBOX',1);
define('OPTION_TYPE_CUSTOM',2);
define('OPTION_TYPE_TEXTAREA',3);
define('OPTION_TYPE_RADIO',4);
define('OPTION_TYPE_SELECTOR',5);
define('OPTION_TYPE_CHECKBOX_ARRAY',6);
define('OPTION_TYPE_CHECKBOX_UL',7);
define('OPTION_TYPE_COLOR_PICKER',8);
define('OPTION_TYPE_CLEARTEXT',9);

function customOptions($optionHandler, $indent="", $album=NULL, $hide=false, $supportedOptions=NULL) {
	$whom = get_class($optionHandler);
	if (is_null($supportedOptions)) $supportedOptions = $optionHandler->getOptionsSupported();
	if (count($supportedOptions) > 0) {
		$options = array_keys($supportedOptions);
		natcasesort($options);
		foreach($options as $option) {
			$optionID = $whom.'_'.$option;
			$row = $supportedOptions[$option];
			$type = $row['type'];
			$desc = $row['desc'];
			if (isset($row['multilingual'])) {
				$multilingual = $row['multilingual'];
			} else {
				$multilingual = $type == OPTION_TYPE_TEXTAREA;
			}
			if (isset($row['texteditor']) && $row['texteditor']) {
				$editor = 'texteditor';
			} else {
				$editor = '';
			}
			if (isset($row['key'])) {
				$key = $row['key'];
			} else { // backward compatibility
				$key = $option;
				$option = str_replace('_', ' ', $option);
			}
			if (is_null($album)) {
				$db = false;
			} else {
				$sql = "SELECT `value` FROM " . prefix('options') . " WHERE `name`='" . mysql_real_escape_string($key) .
										"' AND `ownerid`=".$album->id;

				$db = query_single_row($sql);
			}
			if (!$db) {
				$sql = "SELECT `value` FROM " . prefix('options') . " WHERE `name`='" . mysql_real_escape_string($key) . "';";
				$db = query_single_row($sql);
			}
			if ($db) {
				$v = $db['value'];
			} else {
				$v = 0;
			}

			if ($hide) {
				?>
				<tr id="<?php echo $optionID; ?>_tr" class="<?php echo $hide; ?>extrainfo" style="display:none">
				<?php
			} else {
				?>
				<tr id="<?php echo $optionID; ?>_tr">
				<?php
			}
				?>
				<td width="175"><?php echo $indent . $option; ?></td>
				<?php
				switch ($type) {
					case OPTION_TYPE_CLEARTEXT:
						$multilingual = false;
					case OPTION_TYPE_TEXTBOX:
					case OPTION_TYPE_TEXTAREA:
						if ($type == OPTION_TYPE_CLEARTEXT) {
							$clear = 'clear';
						} else {
							$clear = '';
						}
						?>
						<td width="350px">
							<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.$clear.'text-'.$key; ?>" value=0 />
							<?php
							if ($multilingual) {
								print_language_string_list($v, $key, $type, NULL, $editor);
							} else {
								?>
								<input type="text" size="40" name="<?php echo $key; ?>" style="width: 338px" value="<?php echo html_encode($v); ?>">
								<?php
							}
							?>
						</td>
						<?php
						break;
					case OPTION_TYPE_CHECKBOX:
						?>
						<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'chkbox-'.$key; ?>" value=0 />
						<td width="350px">
							<input type="checkbox" name="<?php echo $key; ?>" value="1" <?php echo checked('1', $v); ?> />
						</td>
						<?php
						break;
					case OPTION_TYPE_CUSTOM:
						?>
						<td width="350px">
							<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'custom-'.$key; ?>" value=0 />
							<?php	$optionHandler->handleOption($key, $v); ?>
						</td>
						<?php
						break;
					case OPTION_TYPE_RADIO:
						?>
						<td width="350px">
							<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'radio-'.$key; ?>" value=0 />
							<?php generateRadiobuttonsFromArray($v,$row['buttons'],$key); ?>
						</td>
						<?php
						break;
					case OPTION_TYPE_SELECTOR:
						?>
						<td width="350px">
							<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'selector-'.$key?>" value=0 />
							<select id="<?php echo $option; ?>" name="<?php echo $key; ?>">
								<?php generateListFromArray(array($v),$row['selections'], false, true); ?>
							</select>
						</td>
						<?php
						break;
					case OPTION_TYPE_CHECKBOX_ARRAY:
						?>
						<td width=\"350px>
							<?php
							foreach ($row['checkboxes'] as $display=>$checkbox) {
								$ck_sql = str_replace($key, $checkbox, $sql);
								$db = query_single_row($ck_sql);
								if ($db) {
									$v = $db['value'];
								} else {
									$v = 0;
								}
								$display = str_replace(' ', '&nbsp;', $display);
								?>
								<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'chkbox-'.$checkbox; ?>" value=0 />
								
								<span style="white-space:nowrap">
									<label>
										<input type="checkbox" name="<?php echo $checkbox; ?>" value="1"<?php echo checked('1', $v); ?> />
										<?php echo($display); ?>
									</label>
								</span>
								<?php
							}
							?>
						</td>
						<?php
						break;
					case OPTION_TYPE_CHECKBOX_UL:
						?>
						<td width=\"350px>
							<?php
							$cvarray = array();
							$c = 0;
							foreach ($row['checkboxes'] as $display=>$checkbox) {
								?>
								<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'chkbox-'.$checkbox; ?>" value=0 />
								<?php
								$ck_sql = str_replace($key, $checkbox, $sql);
								$db = query_single_row($ck_sql);
								if ($db) {
									if ($db['value'])	$cvarray[$c++] = $checkbox;
								}
							}
							?>
							<ul class="customchecklist">
								<?php generateUnorderedListFromArray($cvarray, $row['checkboxes'], '', '', true, true); ?>
							</ul>
						</td>
						<?php
						break;
					case OPTION_TYPE_COLOR_PICKER:
						?>
						<td width="350px" style="margin:0; padding:0">
						<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX.'text-'.$key; ?>" value=0 />
						<script type="text/javascript">
					  	$(document).ready(function() {
					    	$('#<?php echo $key; ?>_colorpicker').farbtastic('#<?php echo $key; ?>_color');
					  	});
						</script>
						<table style="margin:0; padding:0" >
							<tr>
								<td><input type="text" id="<?php echo $key; ?>_color" name="<?php echo $key; ?>"	value="<?php echo $v; ?>"style="height:100px; width:100px; float:right;" /></td>
								<td><div id="<?php echo $key; ?>_colorpicker"></div></td>
							</tr>
						</table>
						</td>
						<?php
						break;
				}
				?>
				<td><?php echo $desc; ?></td>
			</tr>
			<?php
		}
	}
}

/**
 * Encodes for use as a $_POST index
 *
 * @param string $str
 */
function postIndexEncode($str) {
	$str = urlencode($str);
	return str_replace('.','%2E', $str);
}

/**
 * Decodes encoded $_POST index
 *
 * @param string $str
 * @return string
 */
function postIndexDecode($str) {
	$str = str_replace('%2E', '.', sanitize($str,0));
	return urldecode($str);
}


/**
 * Prints radio buttons from an array
 *
 * @param string $currentvalue The current selected value
 * @param string $list the array of the list items form is localtext => buttonvalue
 * @param string $option the name of the option for the input field name
 */
function generateRadiobuttonsFromArray($currentvalue,$list,$option) {
	foreach($list as $text=>$value) {
		$checked ="";
		if($value == $currentvalue) {
			$checked = "checked='checked' "; //the checked() function uses quotes the other way round...
		}
		?>
		<label>
			<span style="white-space:nowrap">
				<input type="radio" name="<?php echo $option; ?>" id="<?php echo $value.'-'.$option; ?>" value="<?php echo $value; ?>"<?php echo $checked; ?> />
				<?php echo $text; ?>
			</span>
		</label>
		<?php
	}
}

/**
 * Creates the body of an unordered list with checkbox label/input fields (scrollable sortables)
 *
 * @param array $currentValue list of items to be flagged as checked
 * @param array $list the elements of the select list
 * @param string $prefix prefix of the input item
 * @param string $alterrights are the items changable.
 */
function generateUnorderedListFromArray($currentValue, $list, $prefix, $alterrights, $sort, $localize) {
	if ($sort) {
		if ($localize) {
			$list = array_flip($list);
			natcasesort($list);
			$list = array_flip($list);
		} else {
			natcasesort($list);
		}
	}
	$cv = array_flip($currentValue);
	foreach($list as $key=>$item) {
		$listitem = postIndexEncode($prefix.$item);
		if ($localize) {
			$display = $key;
		} else {
			$display = $item;
		}
		?>
		<li>
		<span style="white-space:nowrap">
			<label>
				<input id="<?php echo $listitem; ?>" name="<?php echo $listitem; ?>" type="checkbox"
					<?php if (isset($cv[$item])) {echo 'checked="checked"';	} ?> value="<?php echo $item; ?>"
					<?php echo $alterrights; ?> />
				<?php echo $display; ?>
			</label>
		</span>
		</li>
		<?php
		}
}

/**
 * Creates an unordered checklist of the tags
 *
 * @param object $that Object for which to get the tags
 * @param string $postit prefix to prepend for posting
 * @param bool $showCounts set to true to get tag count displayed
 */
function tagSelector($that, $postit, $showCounts=false, $mostused=false) {
	global $_zp_loggedin, $_zp_admin_ordered_taglist, $_zp_admin_LC_taglist, $_zp_UTF8;
	if (is_null($_zp_admin_ordered_taglist)) {
		if ($mostused || $showCounts) {
			$counts = getAllTagsCount();
			if ($mostused) arsort($counts, SORT_NUMERIC);
			$them = array();
			foreach ($counts as $tag=>$count) {
				$them[] = $tag;
			}
		} else {
			$them = getAllTagsUnique();
		}
		$_zp_admin_ordered_taglist = $them;
		$_zp_admin_LC_taglist = array();
		foreach ($them as $tag) {
			$_zp_admin_LC_taglist[] = $_zp_UTF8->strtolower($tag);
		}
	} else {
		$them = $_zp_admin_ordered_taglist;
	}
	
	if (is_null($that)) {
		$tags = array();
	} else {
		$tags = $that->getTags();
	}
	if (count($tags) > 0) {
		foreach ($tags as $tag) {
			$tagLC = 	$_zp_UTF8->strtolower($tag);
			$key = array_search($tagLC, $_zp_admin_LC_taglist);
			if ($key !== false) {
				unset($them[$key]);
			}
		}
	}
	echo '<ul class="tagchecklist">'."\n";
	if ($showCounts) {
		$displaylist = array();
		foreach ($them as $tag) {
			$displaylist[$tag.' ['.$counts[$tag].']'] = $tag;
		}
	} else {
		$displaylist = $them;
	}
	if (count($tags) > 0) {
		generateUnorderedListFromArray($tags, $tags, $postit, false, !$mostused, $showCounts);
		echo '<hr />';
	}
	generateUnorderedListFromArray(array(), $displaylist, $postit, false, !$mostused, $showCounts);
	echo '</ul>';
}

/**
 * emits the html for editing album information
 * called in edit album and mass edit
 * @param string $index the index of the entry in mass edit or '0' if single album
 * @param object $album the album object
 * @param bool $collapse_tags set true to initially hide tab list
 * @since 1.1.3
 */
function printAlbumEditForm($index, $album, $collapse_tags) {
	global $sortby, $gallery, $_zp_loggedin, $mcr_albumlist, $albumdbfields, $imagedbfields;
	$tagsort = getTagOrder();
	if ($index == 0) {
		if (isset($saved)) {
			$album->setSubalbumSortType('manual');
		}
		$suffix = $prefix = '';
	} else {
		$prefix = "$index-";
		$suffix = "_$index";
		echo "<p><em><strong>" . $album->name . "</strong></em></p>";
	}
 ?>
	<input type="hidden" name="<?php echo $prefix; ?>folder" value="<?php echo $album->name; ?>" />
	<input type="hidden" name="tagsort" value="<?php echo $tagsort; ?>" />
	<input	type="hidden" name="<?php echo $prefix; ?>password_enabled" id="<?php echo $prefix; ?>password_enabled" value=0 />
	<table>
		<td width="70%" valign="top">
		<table>
		<tr>
		<td align="left" valign="top" width="150"><?php echo gettext("Album Title"); ?>: </td>
		<td>
		<?php print_language_string_list($album->get('title'), $prefix."albumtitle", false); ?>
  	</td>
  	</tr>
  	 
	<tr>
	<td align="left" valign="top" ><?php echo gettext("Album Description:"); ?> </td> 
	<td>
	<?php	print_language_string_list($album->get('desc'), $prefix."albumdesc", true, NULL, 'texteditor'); ?>
	</td>
	</tr>
	<tr class="<?php echo $prefix; ?>passwordextrashow">
		<td align="left" value="top">
			<p>
				<a href="javascript:toggle_passwords('<?php echo $prefix; ?>',true);">
					<?php echo gettext("Album password:"); ?>
				</a>
			</p>
		</td>
		<td>
		<?php
		$x = $album->getPassword();
		if (!empty($x)) echo "**********";
		?>
		</td>
	</tr> 
	<tr class="<?php echo $prefix; ?>passwordextrahide" style="display:none" >
		<td align="left" value="top">
			<p>
			<a href="javascript:toggle_passwords('<?php echo $prefix; ?>',false);">
				<?php echo gettext("Album guest user:"); ?>
			</a>
			</p>
			<p>
			<?php echo gettext("Album password:");?>
			<br />
			<?php echo gettext("repeat:");?>
			</p>
			<p>
			<?php echo gettext("Password hint:"); ?>
			</p>
		</td>
		<td>
			<p>
				<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $prefix; ?>albumuser" value="<?php echo $album->getUser(); ?>" />
			</p>
			<p>
			<?php
			$x = $album->getPassword();
		
			if (!empty($x)) {
				$x = '			 ';
			}
		  ?>
			<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $prefix; ?>albumpass"  value="<?php echo $x; ?>" />
			<br />
			<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $prefix; ?>albumpass_2" value="<?php echo $x; ?>" />
			</p>
			<p>
			<?php print_language_string_list($album->get('password_hint'), $prefix."albumpass_hint", false); ?>
			</p>
		</td>
	</tr>
	
	<?php
	$d = $album->getDateTime();
	if ($d == "0000-00-00 00:00:00") {
		$d = "";
	}
  ?>

	<script type="text/javascript">
		$(function() {
			$("#datepicker_<?php echo $prefix; ?>").datepicker({
							showOn: 'button',
							buttonImage: 'images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
		});
	</script>

	<tr>
		<td align="left" valign="top"><?php echo gettext("Date:");?> </td> 
		<td width="400">
		<input type="text" id="datepicker_<?php echo $prefix; ?>" size="20em" name="<?php echo $prefix; ?>albumdate" value="<?php echo $d; ?>" /></td>
	</tr>
	<tr>
		<td align="left" valign="top"><?php echo gettext("Location:"); ?> </td> 
		<td>
		<?php print_language_string_list($album->get('place'), $prefix."albumplace", false); ?>
		</td>
	</tr>
	<?php
	$custom = zp_apply_filter('edit_album_custom_data', '', $album, $prefix);
	if (empty($custom)) {
		?>
		<tr>
			<td align="left" valign="top"><?php echo gettext("Custom data:"); ?></td>
			<td><?php print_language_string_list($album->get('custom_data'), $prefix."album_custom_data", true); ?></td>
		</tr>
		<?php
	} else {
		echo $custom;
	}
	$sort = $sortby;
	if (!$album->isDynamic()) {
		$sort[gettext('Manual')] = 'manual';
	}
	$sort[gettext('Custom')] = 'custom';
	?>
	<tr>
	<td align="left" valign="top"><?php echo gettext("Sort subalbums by:");?> </td>
	<td>
	<?php

	// script to test for what is selected
	$javaprefix = 'js_'.preg_replace("/[^a-z0-9_]/","",strtolower($prefix));

	?>
	<table>
		<tr>
			<td>
			<select id="sortselect" name="<?php echo $prefix; ?>subalbumsortby" onchange="update_direction(this,'<?php echo $javaprefix; ?>album_direction_div','<?php echo $javaprefix; ?>album_custom_div')">
			<?php
			if (is_null($album->getParent())) {
				$globalsort = gettext("gallery album sort order");
			} else {
				$globalsort = gettext("parent album subalbum sort order");
			}
			echo "\n<option value =''>$globalsort</option>";
			$cvt = $type = strtolower($album->get('subalbum_sort_type'));
			generateListFromArray(array($type), $sort, false, true);
			?>
			</select>
			</td>
		<td>
	<?php
	if (($type == 'manual') || ($type == '')) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	?>
	<span id="<?php echo $javaprefix; ?>album_direction_div" style="display:<?php echo $dsp; ?>">
		<label>
			<?php echo gettext("Descending"); ?> 
			<input type="checkbox" name="<?php echo $prefix; ?>album_sortdirection" value="1" <?php if ($album->getSortDirection('album')) {	echo "CHECKED";	}; ?>>
		</label>
	</span>
	<?php
	$flip = array_flip($sort);
	if (empty($type) || isset($flip[$type])) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	?>
		</td>
	</tr>
	<script type="text/javascript">
		$(function () {
			$('#<?php echo $javaprefix; ?>customalbumsort').tagSuggest({
				tags: [<?php echo $albumdbfields; ?>]
			});
		});
	</script>
	<tr>
		<td colspan="2">
		<span id="<?php echo $javaprefix; ?>album_custom_div" class="customText" style="display:<?php echo $dsp; ?>">
		<?php echo gettext('custom fields:') ?>
		<input id="<? echo $javaprefix; ?>customalbumsort" name="<? echo $prefix; ?>customalbumsort" type="text" value="<?php echo $cvt; ?>"></input>
		</span>
	
		</td>
	</tr>
</table>
	</td>
	</tr>

  <tr>
	<td align="left" valign="top"><?php echo gettext("Sort images by:"); ?> </td>
	<td>
  <?php 
	// script to test for what is selected
	$javaprefix = 'js_'.preg_replace("/[^a-z0-9_]/","",strtolower($prefix));
	?>
	<table>
		<tr>
			<td>
			<select id="sortselect" name="<?php echo $prefix; ?>sortby" onchange="update_direction(this,'<?php echo $javaprefix; ?>image_direction_div','<?php echo $javaprefix; ?>image_custom_div')">
			<?php
			if (is_null($album->getParent())) {
				$globalsort = gettext("gallery default image sort order");
			} else {
				$globalsort = gettext("parent album image sort order");
			}
			?>
			<option value =""><?php echo $globalsort; ?></option>
			<?php
			$cvt = $type = strtolower($album->get('sort_type'));
			generateListFromArray(array($type), $sort, false, true);
			?>
			</select>
			</td>
		<td>
	<?php
	if (($type == 'manual') || ($type == '')) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	?>
	<span id="<?php echo $javaprefix;?>image_direction_div" style="display:<?php echo $dsp; ?>">
		<label>
			<?php echo gettext("Descending"); ?>
			<input type="checkbox" name="<?php echo $prefix; ?>image_sortdirection" value="1"
				<?php if ($album->getSortDirection('image')) { echo "CHECKED"; }?> >
		</label>
	</span>
	<?php
	$flip = array_flip($sort);
	if (empty($type) || isset($flip[$type])) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	?>
			</td>
		</tr>
		<script type="text/javascript">
			$(function () {
				$('#<?php echo $javaprefix; ?>customimagesort').tagSuggest({
					tags: [<?php echo $imagedbfields; ?>]
				});
			});
		</script>
		<tr>
			<td align="left" colspan="2">
			<span id="<?php echo $javaprefix; ?>image_custom_div" class="customText" style="display:<?php echo $dsp; ?>">
			<?php echo gettext('custom fields:') ?>
			<input id="<?php echo $javaprefix; ?>customimagesort" name="<?php echo $prefix; ?>customimagesort" type="text" value="<?php echo $cvt; ?>"></input>
			</span>
			</td>
		</tr>
	</table>
 </td>
	</tr>

	<?php	if (is_null($album->getParent())) {	?>
		<tr>
		<td align="left" valign="top"><?php echo gettext("Album theme:"); ?> </td>
		<td>
		<select id="album_theme" class="album_theme" name="<?php echo $prefix; ?>album_theme"	<?php if (!($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS))) echo "DISABLED "; ?>	>
		<?php 
		$themes = $gallery->getThemes();
		$oldtheme = $album->getAlbumTheme();
		if (empty($oldtheme)) {
			$selected = 'SELECTED';
		} else {
			$selected = '';;
		}
		?>
		<option value="" <?php echo $selected; ?> > </option>
		<?php
		foreach ($themes as $theme=>$themeinfo) {
			if ($oldtheme == $theme) {
				$selected = 'SELECTED';
			} else {
				$selected = '';;
			}
			?>
			<option value = "<?php echo $theme; ?>" <?php echo $selected; ?> ><?php echo $themeinfo['name']; ?></option>
		<?php
		}
		?>
		</select>
		</td>
		</tr>
	<?php
	}
	$current = $album->get('watermark');
  ?>
  <tr>
  	<td align="left" valign="top" width="150"><?php echo gettext("Album watermark:"); ?> </td>
  	<td>
			<select id="album_watermark" name="album_watermark">
				<option value="" <?php if (empty($current)) echo ' selected="SELECTED"' ?> style="background-color:LightGray">not set</option>
				<?php generateListFromFiles($current, SERVERPATH . "/" . ZENFOLDER . '/watermarks' , '.png'); ?>
			</select>
  	</td>
  </tr>
  
  <tr>
	<td align="left" valign="top" width="150"><?php echo gettext("Thumbnail:"); ?> </td>
	<td>
	<?php
	$showThumb = getOption('thumb_select_images');
	if ($showThumb) echo "\n<script type=\"text/javascript\">updateThumbPreview(document.getElementById('thumbselect'));</script>";
	echo "\n<select style='width:320px' id=\"\"";
	if ($showThumb) echo " class=\"thumbselect\" onChange=\"updateThumbPreview(this)\"";
	echo " name=\"".$prefix."thumb\">";
	$thumb = $album->get('thumb');
	echo "\n<option";
	if ($showThumb) echo " class=\"thumboption\" value=\"\" style=\"background-color:#B1F7B6\"";
	if ($thumb === '1') {
		echo " selected=\"selected\"";
	}
	echo ' value="1">'.gettext('most recent');
	echo '</option>';
	echo "\n<option";
	if ($showThumb) echo " class=\"thumboption\" value=\"\" style=\"background-color:#B1F7B6\"";
	if (empty($thumb) && $thumb !== '1') {
		echo " selected=\"selected\"";
	}
	echo ' value="">'.gettext('randomly selected');
	echo '</option>';
	if ($album->isDynamic()) {
		$params = $album->getSearchParams();
		$search = new SearchEngine();
		$search->setSearchParams($params);
		$images = $search->getImages(0);
		$thumb = $album->get('thumb');
		$imagelist = array();
		foreach ($images as $imagerow) {
			$folder = $imagerow['folder'];
			$filename = $imagerow['filename'];
			$imagelist[] = '/'.$folder.'/'.$filename;
		}
		if (count($imagelist) == 0) {
			$subalbums = $search->getAlbums(0);
			foreach ($subalbums as $folder) {
				$newalbum = new Album($gallery, $folder);
				if (!$newalbum->isDynamic()) {
					$images = $newalbum->getImages(0);
					foreach ($images as $filename) {
						$imagelist[] = '/'.$folder.'/'.$filename;
					}
				}
			}
		}
		foreach ($imagelist as $imagepath) {
			$list = explode('/', $imagepath);
			$filename = $list[count($list)-1];
			unset($list[count($list)-1]);
			$folder = implode('/', $list);
			$albumx = new Album($gallery, $folder);
			$image = newImage($albumx, $filename);
			$selected = ($imagepath == $thumb);
			echo "\n<option";
			if ($showThumb) {
				echo " class=\"thumboption\"";
				echo " style=\"background-image: url(" . $image->getThumb() .	"); background-repeat: no-repeat;\"";
			}
			echo " value=\"".$imagepath."\"";
			if ($selected) {
				echo " selected=\"selected\"";
			}
			echo ">" . $image->getTitle();
			echo  " ($imagepath)";
			echo "</option>";
		}
	} else {
		$images = $album->getImages();
		if (count($images) == 0 && count($album->getSubalbums()) > 0) {
			$imagearray = array();
			$albumnames = array();
			$strip = strlen($album->name) + 1;
			$subIDs = getAllSubAlbumIDs($album->name);
			if(!is_null($subIDs)) {
				foreach ($subIDs as $ID) {
					$albumnames[$ID['id']] = $ID['folder'];
					$query = 'SELECT `id` , `albumid` , `filename` , `title` FROM '.prefix('images').' WHERE `albumid` = "'.
					$ID['id'] .'"';
					$imagearray = array_merge($imagearray, query_full_array($query));
				}
				foreach ($imagearray as $imagerow) {
					$filename = $imagerow['filename'];
					$folder = $albumnames[$imagerow['albumid']];
					$imagepath = substr($folder, $strip).'/'.$filename;
					if (substr($imagepath, 0, 1) == '/') { $imagepath = substr($imagepath, 1); }
					$albumx = new Album($gallery, $folder);
					$image = newImage($albumx, $filename);
					if (is_valid_image($filename)) {
						$selected = ($imagepath == $thumb);
						echo "\n<option";
						if (getOption('thumb_select_images')) {
							echo " class=\"thumboption\"";
							echo " style=\"background-image: url(" . $image->getThumb() . "); background-repeat: no-repeat;\"";
						}
						echo " value=\"".$imagepath."\"";
						if ($selected) {
							echo " selected=\"selected\"";
						}
						echo ">" . $image->getTitle();
						echo  " ($imagepath)";
						echo "</option>";
					}
				}
			}
		} else {
			foreach ($images as $filename) {
				$image = newImage($album, $filename);
				$selected = ($filename == $album->get('thumb'));
				if (is_valid_image($filename)) {
					echo "\n<option";
					if (getOption('thumb_select_images')) {
						echo " class=\"thumboption\"";
						echo " style=\"background-image: url(" . $image->getThumb() . "); background-repeat: no-repeat;\"";
					}
					echo " value=\"" . $filename . "\"";
					if ($selected) {
						echo " selected=\"selected\"";
					}
					echo ">" . $image->getTitle();
					if ($filename != $image->getTitle()) {
						echo  " ($filename)";
					}
					echo "</option>";
				}
			}
		}
	}
	?>
	</select>
	</td>
	</tr>
	</table>
	</td>
	<td valign="top">
		<h2 class="h2_bordered_edit"><?php echo gettext("Publish"); ?></h2>
		<div class="box-edit">
			<?php	$bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');	?>
			<p>
				<label>
					<input type="checkbox" name="<?php	echo $prefix; ?>Published" value="1" <?php if ($album->getShow()) echo "CHECKED";	?> />	
					<?php echo gettext("Published");?>
				</label>
			</p>
		</div>
		
		<h2 class="h2_bordered_edit"><?php echo gettext("General"); ?></h2>
		<div class="box-edit">
			<p>
				<label>
					<input type="checkbox" name="<?php echo $prefix.'allowcomments';?>" value="1" <?php if ($album->getCommentsAllowed()) { echo "CHECKED"; } ?> />
					<?php echo gettext("Allow Comments"); ?>
				</label>
			</p>
			<p>
				<?php
				$hc = $album->get('hitcounter');
				if (empty($hc)) { $hc = '0'; }
				?>
				<label>
					<input type="checkbox" name="reset_hitcounter">
					<?php echo sprintf(gettext("Reset Hitcounter (Hits: %u)"), $hc); ?> 
				</label>
			</p>
			<p>
				<?php
				$tv = $album->get('total_value');
				$tc = $album->get('total_votes');
			
				if ($tc > 0) {
					$hc = $tv/$tc;
					printf(gettext('Rating: <strong>%u</strong>'), $hc);
					?>
					<label>
						<input type="checkbox" id="<?php echo $prefix; ?>reset_rating" name="<?php echo $prefix; ?>reset_rating" value=1 />
						<?php echo gettext("Reset"); ?>
					</label>
					<?php
				} else {
					echo gettext("Rating: Unrated");
				}
				?>
			</p>
		</div>
		<!-- **************** Move/Copy/Rename ****************** -->
		<h2 class="h2_bordered_edit"><?php echo gettext("Utilities"); ?></h2>
		<div class="box-edit">
			
			<span style="white-space:nowrap">
				<label style="padding-right: .5em">
					<input type="radio" id="a-<?php echo $prefix; ?>move" name="a-<?php echo $prefix; ?>MoveCopyRename" value="move"
						onclick="toggleAlbumMoveCopyRename('<?php echo $prefix; ?>', 'movecopy');"/>
					<?php echo gettext("Move");?>
				</label>
			</span>
			
			<span style="white-space:nowrap">
				<label style="padding-right: .5em">
					<input type="radio" id="a-<?php echo $prefix; ?>copy" name="a-<?php echo $prefix; ?>MoveCopyRename" value="copy"
						onclick="toggleAlbumMoveCopyRename('<?php echo $prefix; ?>', 'movecopy');"/>
					<?php echo gettext("Copy");?>
				</label>
			</span>
			
			<span style="white-space:nowrap">
				<label style="padding-right: .5em">
					<input type="radio" id="a-<?php echo $prefix; ?>rename" name="a-<?php echo $prefix; ?>MoveCopyRename" value="rename"
						onclick="toggleAlbumMoveCopyRename('<?php echo $prefix; ?>', 'rename');"/>
					<?php echo gettext("Rename Folder");?>
				</label>
			</span>
			
		
		
			<div id="a-<?php echo $prefix; ?>movecopydiv" style="padding-top: .5em; padding-left: .5em; display: none;">
				<?php echo gettext("to"); ?>: <select id="a-<?php echo $prefix; ?>albumselectmenu" name="a-<?php echo $prefix; ?>albumselect" onChange="">
					<option value="" selected="selected">/</option>
					<?php
						foreach ($mcr_albumlist as $fullfolder => $albumtitle) {
							$singlefolder = $fullfolder;
							$saprefix = "";
							$salevel = 0;
							$selected = "";
							if ($album->name == $fullfolder) {
								continue;
							}
							// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
							while (strstr($singlefolder, '/') !== false) {
								$singlefolder = substr(strstr($singlefolder, '/'), 1);
								$saprefix = "&nbsp; &nbsp;&nbsp;" . $saprefix;
								$salevel++;
							}
							echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].';"' : '')
							. "$selected>". $saprefix . $singlefolder ."</option>\n";
						}
					?>
				</select>
				<br clear: all /><br />
				<p class="buttons">
					<a href="javascript:toggleAlbumMoveCopyRename('<?php echo $prefix; ?>', '');"><img src="images/reset.png" alt="" /><?php echo gettext("Cancel");?></a>
				</p>
			</div>
			<div id="a-<?php echo $prefix; ?>renamediv" style="padding-top: .5em; padding-left: .5em; display: none;">
				<?php echo gettext("to"); ?>: <input name="a-<?php echo $prefix; ?>renameto" type="text" value="<?php echo basename($album->name);?>"/><br />
				<br clear: all />
				<p class="buttons">
				<a href="javascript:toggleAlbumMoveCopyRename('<?php echo $prefix; ?>', '');"><img src="images/reset.png" alt="" /><?php echo gettext("Cancel");?></a>
				</p>
			</div>
			<span style="line-height: 0em;"><br clear=all /></span>
			<?php
			echo zp_apply_filter('edit_album_utilities', '', $album, $prefix);
			?>
			<span style="line-height: 0em;"><br clear=all /></span>
			</div>
		  <h2 class="h2_bordered_edit">
		  	<?php
		  	if ($collapse_tags) {
		  		?>
		  		<a href="javascript:toggle('<?php echo $prefix; ?>taglist_hide');" >
					<?php
		  	}
		  	echo gettext("Tags");
		  	if ($collapse_tags) {
		  		?>
		  		</a>
					<?php
		  	}
		  	?>
	  	</h2>
	  	<div class="box-edit-unpadded">
		  	<div id="<?php echo $prefix; ?>taglist_hide" <?php if ($collapse_tags) echo 'style="display:none"'; ?> >
					<?php
					$tagsort = getTagOrder();
					tagSelector($album, 'tags_'.$prefix, false, $tagsort);
					?>
				</div>
			</div>
	</td>
	</tr>
	</table>
	<table>
	<?php
	if ($album->isDynamic()) {
		?>
		<tr>
			<td align="left" valign="top" width="150"><?php echo gettext("Dynamic album search:"); ?></td>
			<td>
				<table class="noinput">
					<tr>
						<td><?php echo urldecode($album->getSearchParams()); ?></td>
					</tr>
				</table>
			</td>
		</tr>
	<?php } ?>
	
	</table>
	
<br clear:all />
<p class="buttons">
<button type="submit" title="<?php echo gettext("Save"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
<button type="reset" title="<?php echo gettext("Reset"); ?>"><img	src="images/fail.png" alt="" /> <strong><?php echo gettext("Reset"); ?></strong></button>
</p>
<br clear: all />
<?php
}

/**
 * puts out the maintenance buttons for an album
 *
 * @param object $album is the album being emitted
 */
function printAlbumButtons($album) {
	if ($album->getNumImages() > 0) {
		?>
		<form name="clear-cache" action="?action=clear_cache" method="post" style="float: left">
		<input type="hidden" name="action" value="clear_cache">
		<input type="hidden" name="album" value="<?php echo urlencode($album->name); ?>">
		<div class="buttons">
		<button type="submit" class="tooltip" id="edit_hitcounter" title="<?php echo gettext("Clears the album's cached images.");?>"><img src="images/edit-delete.png" style="border: 0px;" /> <?php echo gettext("Clear album cache"); ?></button>
		</div>
		</form>
	
		<form name="cache_images" action="admin-cache-images.php" method="post">
		<input type="hidden" name="album" value="<?php echo urlencode($album->name); ?>">
		<input type="hidden" name="return" value="<?php echo urlencode($album->name); ?>">
		<div class="buttons">
		<button type="submit" class="tooltip" id="edit_cache2" title="<?php echo gettext("Cache newly uploaded images."); ?>"><img src="images/cache1.png" style="border: 0px;" />
		<?php echo gettext("Pre-Cache Images"); ?></button>
		</div>
		</form>
		<form name="refresh_metadata" action="admin-refresh-metadata.php?album="<?php echo urlencode($album->name); ?>" method="post">
		<input type="hidden" name="album" value="<?php echo urlencode($album->name);?>">
		<input type="hidden" name=\return" value="<?php echo urlencode($album->name); ?>">
		<div class="buttons">
		<button type="submit" class="tooltip" id="edit_refresh" title="<?php echo gettext("Forces a refresh of the EXIF and IPTC data for all images in the album."); ?>"><img src="images/redo.png" style="border: 0px;" /> <?php echo gettext("Refresh Metadata"); ?></button>
	  </div>	
		</form>
		<form name="reset_hitcounters" action="?action=reset_hitcounters" method="post">
		<input type="hidden" name="action" value="reset_hitcounters">
		<input type="hidden" name="albumid" value="<?php echo $album->getAlbumID(); ?>">
		<input type="hidden" name="album" value="<?php echo urlencode($album->name); ?>">
		<div class="buttons">
		<button type="submit" class="tooltip" id="edit_hitcounter" title="<?php echo gettext("Resets all hitcounters in the album."); ?>"><img src="images/reset1.png" style="border: 0px;" /> <?php echo gettext("Reset hitcounters"); ?></button>
		</div>
		</form>
		<br /><br />
	<?php		
	}
}
/**
 * puts out a row in the edit album table
 *
 * @param object $album is the album being emitted
 **/
function printAlbumEditRow($album) {
	?>
	<div id="id_<?php echo $album->getAlbumID(); ?>">
	<table cellspacing="0" width="100%">
	<tr>
	<td class="handle"><img src="images/drag_handle.png" style="border: 0px;" alt="Drag the album <?php echo $album->name; ?>" /></td>
	<td style="text-align: left;" width="80">
		<?php
		$thumb = $album->getAlbumThumb();
		if (strpos($thumb, '_%7B') !== false) { // it is the default image
			$thumb = 'images/imageDefault.png';
		}
		if (getOption('thumb_crop')) {
			$w = round(getOption('thumb_crop_width')/2);
			$h = round(getOption('thumb_crop_height')/2);
		} else {
			$w = $h = round(getOption('thumb_size')/2);
		}
		?>
		<a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="<?php echo sprintf(gettext('Edit this album: %s'), $album->name); ?>">
		<img src="<?php echo $thumb; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" /></a>
	</td>
	<td  style="text-align: left;font-size:110%;" width="300">
		<a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="<?php echo sprintf(gettext('Edit this album: %s'), $album->name); ?>"><?php echo $album->getTitle(); ?></a>
	</td>
	<?php
	if ($album->isDynamic()) {
		$si = "Dynamic";
		$sa = '';
	} else {
		$ci = count($album->getImages());
		if ($ci > 0) {
			$si = sprintf(ngettext('%u image','%u images', $ci), $ci);
		} else {
			$si = gettext('no images');
		}
		if ($ci > 0) {
			$si = '<a href="?page=edit&album=' . urlencode($album->name) .'&tab=imageinfo" title="'.gettext('Subalbum List').'">'.$si.'</a>';
		}
		$ca = count($album->getSubalbums());
		if ($ca > 0) {
			$sa = sprintf(ngettext('%u album','%u albums', $ca), $ca);
		} else {
			$sa = '&nbsp;';
		}
		if ($ca > 0) {
			$sa = '<a href="?page=edit&album=' . urlencode($album->name) .'&tab=subalbuminfo" title="'.gettext('Subalbum List').'">'.$sa.'</a>';
		}
	}
	?>
	<td style="text-align: right;" width="80"><?php echo $sa; ?></td>
	<td style="text-align: right;" width="80"><?php echo $si; ?></td>
  <?php	$wide='40px'; ?>
	<td>
		<table width="100%">
		<tr>
		<td>
		<td style="text-align:center;" width='$wide'>
  <?php
	$pwd = $album->getPassword();
	if (!empty($pwd)) {
		echo '<a title="'.gettext('Password protected').'"><img src="images/lock.png" style="border: 0px;" alt="'.gettext('Password protected').'" /></a>';
	}
 ?>
	</td><td style="text-align:center;" width="<?php echo $wide;?>">
	<?php
	if ($album->getShow()) { ?>
		<a class="publish" href="?action=publish&value=0&amp;album=<?php echo urlencode($album->name); ?>" title="<?php echo sprintf(gettext('Unpublish the album %s'), $album->name); ?>">
		<img src="images/pass.png" style="border: 0px;" alt="<?php echo gettext('Published'); ?>" /></a>
		
 <?php	} else { ?>
		<a class="publish" href="?action=publish&amp;value=1&amp;album=<?php echo urlencode($album->name); ?>" title="<?php echo sprintf(gettext('Publish the album %s'), $album->name); ?>">
		<img src="images/action.png" style="border: 0px;" alt="Publish the album <?php echo $album->name; ?>" /></a>
 <?php	} ?>
	</td>
	<td style="text-align:center;" width="<?php echo $wide; ?>">
		<a class="cache" href="admin-cache-images.php?page=edit&amp;album=<?php echo urlencode($album->name); ?>&amp;return=*<?php echo urlencode(dirname($album->name)); ?> " title="<?php echo sprintf(gettext('Pre-cache images in %s'), $album->name); ?>">
		<img src="images/cache1.png" style="border: 0px;" alt="<?php echo sprintf(gettext('Cache the album %s'), $album->name); ?>" /></a>
	</td>
	<td style="text-align:center;" width="<?php echo $wide; ?>";>
		<a class="warn" href="admin-refresh-metadata.php?page=edit&amp;album=<?php echo urlencode($album->name); ?>&amp;eturn=*<?php echo urlencode(dirname($album->name)); ?>" title="<?php echo sprintf(gettext('Refresh metadata for the album %s'), $album->name); ?>">
		<img src="images/redo1.png" style="border: 0px;" alt="<?php echo sprintf(gettext('Refresh image metadata in the album %s'), $album->name); ?>" /></a>
	</td>
	<td style="text-align:center;" width="<?php echo $wide; ?>">
		<a class="reset" href="?action=reset_hitcounters&amp;albumid=<?php echo $album->getAlbumID(); ?>&amp;album=<?php echo urlencode($album->name);?>&amp;subalbum=true" title="<?php echo sprintf(gettext('Reset hitcounters for album %s'), $album->name); ?>">
		<img src="images/reset.png" style="border: 0px;" alt="<?php echo sprintf(gettext('Reset hitcounters for the album %s'), $album->name); ?>" /></a>
	</td>
	<td style="text-align:center;" width="<?php echo $wide; ?>">
		<a class="delete" href="javascript: confirmDeleteAlbum('?page=edit&amp;action=deletealbum&amp;album=<?php echo urlencode(urlencode($album->name)); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this entire album?")); ?>','<?php echo js_encode(gettext("Are you Absolutely Positively sure you want to delete the album? THIS CANNOT BE UNDONE!")); ?>')" title="<?php echo sprintf(gettext("Delete the album %s"), js_encode($album->name)); ?>">
		<img src="images/fail.png" style="border: 0px;" alt="<?php echo sprintf(gettext('Delete the album %s'), js_encode($album->name)); ?>" /></a>
	</td>
	</tr>
	</table>
	</td>

	</tr>
	</table>
	</div>
	<?php
}

/**
 * processes the post from the above
 *@param int param1 the index of the entry in mass edit or 0 if single album
 *@param object param2 the album object
 *@return string error flag if passwords don't match
 *@since 1.1.3
 */
function processAlbumEdit($index, $album) {
	global $gallery;
	if ($index == 0) {
		$prefix = '';
	} else {
		$prefix = "$index-";
	}
	$tagsprefix = 'tags_'.$prefix;
	$notify = '';
	$album->setTitle(process_language_string_save($prefix.'albumtitle', 2));
	$album->setDesc(process_language_string_save($prefix.'albumdesc', 1));
	$tags = array();
	for ($i=0; $i<4; $i++) {
		if (isset($_POST[$tagsprefix.'new_tag_value_'.$i])) {
			$tag = trim(sanitize($_POST[$tagsprefix.'new_tag_value_'.$i]));
			unset($_POST[$tagsprefix.'new_tag_value_'.$i]);
			if (!empty($tag)) {
				$tags[] = $tag;
			}
		}
	}
	$l = strlen($tagsprefix);
	foreach ($_POST as $key => $value) {
		$key = postIndexDecode($key);
		if (substr($key, 0, $l) == $tagsprefix) {
			if ($value) {
				$tags[] = substr($key, $l);
			}
		}
	}
	$tags = array_unique($tags);
	$album->setTags($tags);
	$album->setDateTime(sanitize($_POST[$prefix."albumdate"]));
	$album->setPlace(process_language_string_save($prefix.'albumplace', 3));
	if (isset($_POST[$prefix.'thumb'])) $album->setAlbumThumb(sanitize($_POST[$prefix.'thumb']));
	$album->setShow(isset($_POST[$prefix.'Published']));
	$album->setCommentsAllowed(isset($_POST[$prefix.'allowcomments']));
	$sorttype = strtolower(sanitize($_POST[$prefix.'sortby'], 3));
	if ($sorttype == 'custom') $sorttype = strtolower(sanitize($_POST[$prefix.'customimagesort'],3));
	$album->setSortType($sorttype);
	if ($sorttype == 'manual') {
		$album->setSortDirection('image', 0);
	} else {
		if (empty($sorttype)) {
			$direction = 0;
		} else {
			$direction = isset($_POST[$prefix.'image_sortdirection']);
		}
		$album->setSortDirection('image', $direction);
	}
	$sorttype = strtolower(sanitize($_POST[$prefix.'subalbumsortby'],3));
	if ($sorttype == 'custom') $sorttype = strtolower(sanitize($_POST[$prefix.'customalbumsort'],3));
	$album->setSubalbumSortType($sorttype);
	if ($sorttype == 'manual') {
		$album->setSortDirection('album', 0);
	} else {
		$album->setSortDirection('album', isset($_POST[$prefix.'album_sortdirection']));
	}
	if (isset($_POST[$prefix.'reset_hitcounter'])) {
		$album->set('hitcounter',0);
	}
	if (isset($_POST[$prefix.'reset_rating'])) {
		$album->set('total_value', 0);
		$album->set('total_votes', 0);
		$album->set('used_ips', 0);
	}
	$fail = '';
	if (sanitize($_POST[$prefix.'password_enabled'])) {
		$olduser = $album->getUser();
		$newuser = $_POST[$prefix.'albumuser'];
		$pwd = trim($_POST[$prefix.'albumpass']);
		if (($olduser != $newuser)) {
			if ($pwd != $_POST[$prefix.'albumpass_2']) {
				$pwd2 = trim($_POST[$prefix.'albumpass_2']);
				$_POST[$prefix.'albumpass'] = $pwd; // invalidate password, user changed without password beign set
				if (!empty($newuser) && empty($pwd) && empty($pwd2)) $fail = '&mismatch=user';
			}
		}
		if ($_POST[$prefix.'albumpass'] == $_POST[$prefix.'albumpass_2']) {
			$album->setUser($newuser);
			if (empty($pwd)) {
				if (empty($_POST[$prefix.'albumpass'])) {
					$album->setPassword(NULL);  // clear the gallery password
				}
			} else {
				$album->setPassword($pwd);
			}
		} else {
			if (empty($fail)) {
				$notify = '&mismatch=album';
			} else {
				$notify = $fail;
			}
		}
	}
	$oldtheme = $album->getAlbumTheme();
	if (isset($_POST[$prefix.'album_theme'])) {
		$newtheme = sanitize($_POST[$prefix.'album_theme']);
		if ($oldtheme != $newtheme) {
			$album->setAlbumTheme($newtheme);
		}
	}
	$album->setPasswordHint(process_language_string_save($prefix.'albumpass_hint', 3));
	$old = $album->get('watermark');
	if (isset($_POST['album_watermark'])) {
		$new = sanitize($_POST['album_watermark'], 3);
		$album->set('watermark', $new);
		if ($new != $old) $gallery->clearCache(SERVERCACHE . '/' . $album->name);
	}
	$custom = process_language_string_save($prefix.'album_custom_data', 1);
	$album->setCustomData(zp_apply_filter('save_album_custom_data', $custom, $prefix));
	zp_apply_filter('save_album_utilities_data', $album, $prefix);
	$album->save();
		
	// Move/Copy/Rename the album after saving.
	$movecopyrename_action = '';
	if (isset($_POST['a-'.$prefix.'MoveCopyRename'])) {
		$movecopyrename_action = sanitize($_POST['a-'.$prefix.'MoveCopyRename'],3);
	}

	if ($movecopyrename_action == 'move') {
		$dest = sanitize_path($_POST['a'.$prefix.'-albumselect'],3);
		// Append the album name.
		$dest = ($dest ? $dest . '/' : '') . (strpos($album->name, '/') === FALSE ? $album->name : basename($album->name));
		if ($dest && $dest != $album->name) {
			if ($returnalbum = $album->moveAlbum($dest)) {
				// A slight hack to redirect to the new album after moving.
				$_GET['album'] = $returnalbum;
			} else {
				$notify .= "&mcrerr=1";
			}
		} else {
			// Cannot move album to same album.
		}
	} else if ($movecopyrename_action == 'copy') {
		$dest = sanitize_path($_POST['a'.$prefix.'-albumselect'],3);
		// Append the album name.
		$dest = ($dest ? $dest . '/' : '') . (strpos($album->name, '/') === FALSE ? $album->name : basename($album->name));
		if ($dest && $dest != $album->name) {
			if(!$album->copyAlbum($dest)) {
				$notify .= "&mcrerr=1";
			}
		} else {
			// Cannot copy album to existing album.
			// Or, copy with rename?
		}
	} else if ($movecopyrename_action == 'rename') {
		$renameto = sanitize_path($_POST['a'.$prefix.'-renameto'],3);
		$renameto = str_replace(array('/', '\\'), '', $renameto);
		if (dirname($album->name) != '.') {
			$renameto = dirname($album->name) . '/' . $renameto;
		}
		if ($renameto != $album->name) {
			if ($returnalbum = $album->renameAlbum($renameto)) {
				// A slight hack to redirect to the new album after moving.
				$_GET['album'] = $returnalbum;
			} else {
				$notify .= "&mcrerr=1";
			}
		}
	}

	return $notify;
}

/**
 * Searches the zenphoto.org home page for the current zenphoto download
 * locates the version number of the download and compares it to the version
 * we are running.
 *
 *@rerturn string If there is a more current version on the WEB, returns its version number otherwise returns FALSE
 *@since 1.1.3
 */
function checkForUpdate() {
	global $_zp_WEB_Version;
	if (isset($_zp_WEB_Version)) { return $_zp_WEB_Version; }
	$c = ZENPHOTO_VERSION;
	$v = @file_get_contents('http://www.zenphoto.org/files/LATESTVERSION');	
	if (empty($v)) {
		$_zp_WEB_Version = 'X';
	} else {
		if ($i = strpos($v, 'RC')) {
			$v_candidate = intval(substr($v, $i+2));
		} else {
			$v_candidate = 9999;
		}
		if ($i = strpos($c, 'RC')) {
			$c_candidate = intval(substr($c, $i+2));
		} else {
			$c_candidate = 9999;
		}
		$pot = array(1000000000, 10000000, 100000, 1);
		$wv = explode('.', $v);
		$wvd = 0;
		foreach ($wv as $i => $d) {
			$wvd = $wvd + $d * $pot[$i];
		}
		$cv = explode('.', $c);
		$cvd = 0;
		foreach ($cv as $i => $d) {
			$cvd = $cvd + $d * $pot[$i];
		}
		if ($wvd > $cvd || (($wvd == $cvd) && ($c_candidate < $v_candidate))) {
			$_zp_WEB_Version = $v;
		} else {
			$_zp_WEB_Version = '';
		}
	}
	Return $_zp_WEB_Version;
}

function adminPageNav($pagenum,$totalpages,$adminpage,$parms,$tab='') {
	if (empty($parms)) {
		$url = '?';
	} else {
		$url = $parms.'&amp;';
	}
	echo '<ul class="pagelist"><li class="prev">';
	if ($pagenum > 1) {
		echo '<a href='.$url.'subpage='.($p=$pagenum-1).$tab.' title="'.sprintf(gettext('page %u'),$p).'">'.'&laquo; '.gettext("Previous page").'</a>';
	} else {
		echo '<span class="disabledlink">&laquo; '.gettext("Previous page").'</span>';
	}
	echo "</li>";
	$start = max(1,$pagenum-7);
	$total = min($start+15,$totalpages+1);
	if ($start != 1) { echo "\n <li><a href=".$url.'subpage='.($p=max($start-8, 1)).$tab.' title="'.sprintf(gettext('page %u'),$p).'">. . .</a></li>'; }
	for ($i=$start; $i<$total; $i++) {
		if ($i == $pagenum) {
			echo "<li class=\"current\">".$i.'</li>';
		} else {
			echo '<li><a href='.$url.'subpage='.$i.$tab.' title="'.sprintf(gettext('page %u'),$i).'">'.$i.'</a></li>';
		}
	}
	if ($i < $totalpages) { echo "\n <li><a href=".$url.'subpage='.($p=min($pagenum+22,$totalpages+1)).$tab.' title="'.sprintf(gettext('page %u'),$p).'">. . .</a></li>'; }
	echo "<li class=\"next\">";
	if ($pagenum<$totalpages) {
		echo '<a href='.$url.'subpage='.($p=$pagenum+1).$tab.' title="'.sprintf(gettext('page %u'),$p).'">'.gettext("Next page").' &raquo;'.'</a>';
	} else {
		echo '<span class="disabledlink">'.gettext("Next page").' &raquo;</span>';
	}
	echo '</li></ul>';
}

$_zp_current_locale = NULL;
/**
 * Generates an editable list of language strings
 *
 * @param string $dbstring either a serialized languag string array or a single string
 * @param string $name the prefix for the label, id, and name tags
 * @param bool $textbox set to true for a textbox rather than a text field
 * @param string $locale optional locale of the translation desired
 * @param string $edit optional class
 */
function print_language_string_list($dbstring, $name, $textbox=false, $locale=NULL, $edit='', $short=false, $id='') {
	global $_zp_languages, $_zp_active_languages, $_zp_current_locale;
	if (!empty($edit)) $edit = ' class="'.$edit.'"';
	if (empty($id)) {
		$groupid ='';
	} else {
		$groupid = ' id="'.$id.'"';
	}
	if (is_null($locale)) {
		if (is_null($_zp_current_locale)) {
			$_zp_current_locale = getUserLocale();
			if (empty($_zp_current_locale)) $_zp_current_locale = 'en_US';
		}
		$locale = $_zp_current_locale;
	}
	if (preg_match('/^a:[0-9]+:{/', $dbstring)) {
		$strings = unserialize($dbstring);
	} else {
		$strings = array($locale=>$dbstring);
	}
	if (getOption('multi_lingual')) {
		if (is_null($_zp_active_languages)) {
			$_zp_active_languages = generateLanguageList();
		}
		$emptylang = array_flip($_zp_active_languages);
		unset($emptylang['']);
		natcasesort($emptylang);
		if ($textbox) $class = 'box'; else $class = '';
		echo '<ul'.$groupid.' class="'.($short ? 'language_string_list_short' : 'language_string_list').$class.'"'.">\n";
		$empty = true;
		foreach ($emptylang as $key=>$lang) {
			if (isset($strings[$key])) {
				$string = $strings[$key];
				if (!empty($string)) {
					unset($emptylang[$key]);
					$empty = false;
					?>
					<li>
						<label for="<?php echo $name; ?>_'.$key.'"><?php echo $lang; ?></label>
						<?php
						if ($textbox) {
							echo "\n".'<textarea name="'.$name.'_'.$key.'"'.$edit.' cols="'.($short ? TEXTAREA_COLUMNS_SHORT : TEXTAREA_COLUMNS).'"	style="width: 320px" rows="6">'.htmlentities($string,ENT_COMPAT,getOption("charset")).'</textarea>';
						} else {
							echo '<br /><input id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" type="text" value="'.$string.'" size="'.($short ? TEXT_INPUT_SIZE_SHORT : TEXT_INPUT_SIZE).'" />';
						}
						?>
					</li>
					<?php
				}
			}
		}
		if ($empty) {
			$element = $emptylang[$locale];
			unset($emptylang[$locale]);
			$emptylang = array_merge(array($locale=>$element), $emptylang);
		}
		foreach ($emptylang as $key=>$lang) {
			echo '<li><label for="'.$name.'_'.$key.'"></label>';
			echo $lang;
			if ($textbox) {
				echo "\n".'<textarea name="'.$name.'_'.$key.'"'.$edit.' cols="'.($short ? TEXTAREA_COLUMNS_SHORT : TEXTAREA_COLUMNS).'"	style="width: 320px" rows="6"></textarea>';
			} else {
				echo '<br /><input id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" type="text" value="" size="'.($short ? TEXT_INPUT_SIZE_SHORT : TEXT_INPUT_SIZE).'" />';
			}
			echo "</li>\n";

		}
		echo "</ul>\n";
	} else {
		if (empty($locale)) $locale = 'en_US';
		if (isset($strings[$locale])) {
			$dbstring = $strings[$locale];
		} else {
			$dbstring = array_shift($strings);
		}
		if ($textbox) {
			echo '<textarea'.$groupid.' name="'.$name.'_'.$locale.'"'.$edit.' cols="'.($short ? TEXTAREA_COLUMNS_SHORT : TEXTAREA_COLUMNS).'"	rows="6">'.htmlentities($dbstring,ENT_COMPAT,getOption("charset")).'</textarea>';
		} else {
			echo '<input'.$groupid.' name="'.$name.'_'.$locale.'" type="text" value="'.$dbstring.'" size="'.($short ? TEXT_INPUT_SIZE_SHORT : TEXT_INPUT_SIZE).'" />';
		}
	}
}

/**
 * process the post of a language string form
 *
 * @param string $name the prefix for the label, id, and name tags
 * @return string
 */
function process_language_string_save($name, $sanitize_level=3) {
	global $_zp_active_languages;
	if (is_null($_zp_active_languages)) {
		$_zp_active_languages = generateLanguageList();
	}
	$l = strlen($name)+1;
	$strings = array();
	foreach ($_POST as $key=>$value) {
		if (!empty($value) && preg_match('/^'.$name.'_[a-z]{2}_[A-Z]{2}$/', $key)) {
			$key = substr($key, $l);
			if (in_array($key, $_zp_active_languages)) {
				$strings[$key] = sanitize($value, $sanitize_level);
			}
		}
	}
	switch (count($strings)) {
		case 0:
			if (isset($_POST[$name])) {
				return sanitize($_POST[$name], $sanitize_level);
			} else {
				return '';
			}
		case 1:
			return array_shift($strings);
		default:
			return serialize($strings);
	}
}

/**
 * Returns the desired tagsort order (0 for alphabetic, 1 for most used)
 *
 * @return int
 */
function getTagOrder() {
	if (isset($_REQUEST['tagsort'])) {
		$tagsort = sanitize($_REQUEST['tagsort'], 0);
		setBoolOption('tagsort', $tagsort);
	} else {
		$tagsort = getOption('tagsort');
	}
	return $tagsort;
}

/**
 * Unzips an image archive
 *
 * @param file $file the archive
 * @param string $dir where the images go
 */
function unzip($file, $dir) { //check if zziplib is installed
	if(function_exists('zip_open')) {
		$zip = zip_open($file);
		if ($zip) {
			while ($zip_entry = zip_read($zip)) { // Skip non-images in the zip file.
				$fname = zip_entry_name($zip_entry);
				$soename = internalToFilesystem(seoFriendlyURL($fname));
				if (is_valid_image($soename) || is_valid_other_type($soename)) {
					if (zip_entry_open($zip, $zip_entry, "r")) {
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						$path_file = str_replace("/",DIRECTORY_SEPARATOR, $dir . '/' . $soename);
						$fp = fopen($path_file, "w");
						fwrite($fp, $buf);
						fclose($fp);
						zip_entry_close($zip_entry);
						$albumname = substr($dir, strlen(getAlbumFolder()));
						$album = new Album(new Gallery(), $albumname);
						$image = newImage($album, $soename);
						if ($fname != $soename) {
							$image->setTitle($name);
							$image->save();
						}
					}
				}
			}
			zip_close($zip);
		}
	} else { // Use Zlib http://www.phpconcept.net/pclzip/index.en.php
		require_once(dirname(__FILE__).'/lib-pclzip.php');
		$zip = new PclZip($file);
		if ($zip->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH) == 0) {
			die("Error : ".$zip->errorInfo(true));
		}
	}
}

/**
 * Checks for a zip file
 *
 * @param string $filename name of the file
 * @return bool
 */
function is_zip($filename) {
	$ext = getSuffix($filename);
	return ($ext == "zip");
}

/**
 * Takes a comment and makes the body of an email.
 *
 * @param string $str comment
 * @param string $name author
 * @param string $albumtitle album
 * @param string $imagetitle image
 * @return string
 */
function commentReply($str, $name, $albumtitle, $imagetitle) {
	$str = wordwrap(strip_tags($str), 75, '\n');
	$lines = explode('\n', $str);
	$str = implode('%0D%0A', $lines);
	$str = "$name commented on $imagetitle in the album $albumtitle: %0D%0A%0D%0A" . $str;
	return $str;
}

/**
 * Extracts and returns a 'statement' from a PHP script for so that it may be 'evaled'
 *
 * @param string $target the pattern to match on
 * @param string $str the PHP script
 * @return string
 */
function isolate($target, $str) {
	$i = strpos($str, $target);
	if ($i === false) return false;
	$str = substr($str, $i);
	//$j = strpos($str, ";\n"); // This is wrong - PHP will not treat all newlines as \n.
	$j = strpos($str, ";"); // This is also wrong; it disallows semicolons in strings. We need a regexp.
	$str = substr($str, 0, $j+1);
	return $str;
}

function seoFriendlyURL($source) {
	$string = zp_apply_filter('seoFriendlyURL', $source);	
	if ($source == $string) { // no filter, do basic cleanup
		$string = preg_replace("/&([a-zA-Z])(uml|acute|grave|circ|tilde|ring),/","",$string);
		$string = preg_replace("/[^a-zA-Z0-9_.-]/","",$string);
		$string = str_replace(array('---','--'),'-', $string);
	}
	return $string;
}

/**
 * Return an array of files from a directory and sub directories
 *
 * This is a non recursive function that digs through a directory. More info here:
 * @link http://planetozh.com/blog/2005/12/php-non-recursive-function-through-directories/
 *
 * @param string $dir directory
 * @return array
 * @author Ozh
 * @since 1.3
 */
function listDirectoryFiles( $dir ) {
	$file_list = array();
	$stack[] = $dir;
	while ($stack) {
		$current_dir = array_pop($stack);
		if ($dh = @opendir($current_dir)) {
			while (($file = @readdir($dh)) !== false) {
				if ($file !== '.' AND $file !== '..') {
					$current_file = "{$current_dir}/{$file}";
					if ( is_file($current_file) && is_readable($current_file) ) {
						$file_list[] = "{$current_dir}/{$file}";
					} elseif (is_dir($current_file)) {
						$stack[] = $current_file;
					}
				}
			}
		}
	}
	return $file_list;
}


/**
 * Check if a file is a text file
 *
 * @param string $file 
 * @param array $ok_extensions array of file extensions that are OK to edit (ie text files)
 * @return bool
 * @author Ozh
 * @since 1.3
 */
function isTextFile ( $file, $ok_extensions = array('css','php','js','txt','inc') ) {
	$path_info = pathinfo($file);
	$ext = (isset($path_info['extension']) ? $path_info['extension'] : '');
	return ( !empty ( $ok_extensions ) && (in_array( $ext, $ok_extensions ) ) );
}

/**
 * Check if a theme is editable (ie not a bundled theme)
 *
 * @param $theme theme to check
 * @param $themes array of installed themes (eg result of getThemes())
 * @return bool
 * @author Ozh
 * @since 1.3
 */
function themeIsEditable($theme, $themes) {
	unset($themes['default']);
	unset($themes['effervescence_plus']);
	unset($themes['stopdesign']);
	unset($themes['example']);
	unset($themes['zenpage-default']);
	/* TODO: in case we change the number or names of bundled themes, need to edit this ! */

	return (in_array( $theme , array_keys($themes)));
}


/**
 * Copy a theme directory to create a new custom theme
 *
 * @param $source source directory
 * @param $target target directory
 * @return bool|string either true or an error message
 * @author Ozh
 * @since 1.3
 */
function copyThemeDirectory($source, $target, $newname) {
	global $_zp_current_admin;
	$message = true;
	$source  = SERVERPATH . '/themes/'.internalToFilesystem($source);
	$target  = SERVERPATH . '/themes/'.internalToFilesystem($target);
	
	// If the target theme already exists, nothing to do.
	if ( is_dir($target)) {
		return gettext('Cannot create new theme.') .' '. sprintf(gettext('Directory "%s" already exists!'), basename($target));
	}
	
	// If source dir is missing, exit too
	if ( !is_dir($source)) {
		return gettext('Cannot create new theme.') .' '.sprintf(gettext('Cannot find theme directory "%s" to copy!'), basename($source));
	}

	// We must be able to write to the themes dir.
	if (! is_writable( dirname( $target) )) {
		return gettext('Cannot create new theme.') .' '.gettext('The <tt>/themes</tt> directory is not writable!');
	}

	// We must be able to create the directory
	if (! mkdir($target, CHMOD_VALUE)) {
		return gettext('Cannot create new theme.') .' '.gettext('Could not create directory for the new theme');
	}
	chmod($target, CHMOD_VALUE);
	
	// Get a list of files to copy: get all files from the directory, remove those containing '/.svn/'
	$source_files = array_filter( listDirectoryFiles( $source ), create_function('$str', 'return strpos($str, "/.svn/") === false;') );
	
	// Determine nested (sub)directories structure to create: go through each file, explode path on "/"
	// and collect every unique directory
	$dirs_to_create = array();
	foreach ( $source_files as $path ) {
		$path = dirname ( str_replace( $source . '/', '', $path ) );
		$path = explode ('/', $path);
		$dirs = '';
		foreach ( $path as $subdir ) {
			if ( $subdir == '.svn' or $subdir == '.' ) {
				continue 2;
			}
			$dirs = "$dirs/$subdir";
			$dirs_to_create[$dirs] = $dirs;	
		}
	}
	/*
	Example result for theme 'effervescence_plus': $dirs_to_create = array (
		'/styles' => '/styles',
		'/scripts' => '/scripts',
		'/images' => '/images',
		'/images/smooth' => '/images/smooth',
		'/images/slimbox' => '/images/slimbox',
	);
	*/
	
	// Create new directory structure
	foreach ($dirs_to_create as $dir) {
		mkdir("$target/$dir", CHMOD_VALUE);
		chmod("$target/$dir", CHMOD_VALUE); // Using chmod as PHP doc suggested: "Avoid using umask() in multithreaded webservers. It is better to change the file permissions with chmod() after creating the file."
	}
	
	// Now copy every file
	foreach ( $source_files as $file ) {
		$newfile = str_replace($source, $target, $file);
		if (! copy("$file", "$newfile" ) )
			return sprintf(gettext("An error occured while copying files. Please delete manually the new theme directory '%s' and retry or copy files manually."), basename($target));
		chmod("$newfile", CHMOD_VALUE);	
	}	

	// Rewrite the theme header.
	if ( file_exists($target.'/theme_description.php') ) {		
		$theme_description = array();
		require($target.'/theme_description.php');
		$theme_description['desc'] = sprintf(gettext('Your theme, based on theme %s'), $theme_description['name']);
	} else  {
		$theme_description['desc'] = gettext('Your theme');	
	}
	$theme_description['name'] = $newname;
	$theme_description['author'] = $_zp_current_admin['user'];
	$theme_description['version'] = '1.0';
	$theme_description['date']  = zpFormattedDate(getOption('date_format'), time());
	
	$description = sprintf('<'.'?php
// Zenphoto theme definition file
$theme_description["name"] = "%s";
$theme_description["author"] = "%s";
$theme_description["version"] = "%s";
$theme_description["date"] = "%s";
$theme_description["desc"] = "%s";
?'.'>' , htmlentities($theme_description['name'], ENT_COMPAT),
		htmlentities($theme_description['author'], ENT_COMPAT),
		htmlentities($theme_description['version'], ENT_COMPAT),
		htmlentities($theme_description['date'], ENT_COMPAT),
		htmlentities($theme_description['desc'], ENT_COMPAT));
	
	$f = fopen($target.'/theme_description.php', 'w');
	if ($f !== FALSE) {
		@fwrite($f, $description);
		fclose($f);
		$message = gettext('New custom theme created successfully!');
	} else {
		$message = gettext('New custom theme created, but its description could not be updated');
	}
	
	// Make a slightly custom theme image
	if (file_exists("$target/theme.png")) $themeimage = "$target/theme.png";
	else if (file_exists("$target/theme.gif")) $themeimage = "$target/theme.gif";
	else if (file_exists("$target/theme.jpg")) $themeimage = "$target/theme.jpg";
	else $themeimage = false;
	if ($themeimage) {
		require_once(dirname(__FILE__).'/functions-image.php');
		if ($im = zp_imageGet($themeimage)) {
			$x = zp_imageWidth($im)/2 - 45;
			$y = zp_imageHeight($im)/2 - 10;
			$text = "CUSTOM COPY";

			// create a blueish overlay
			$overlay = zp_createImage(zp_imageWidth($im), zp_imageHeight($im));
			imagefill ($overlay, 0, 0, 0x0606090);
			// Merge theme image and overlay
			zp_imageMerge($im, $overlay, 0, 0, 0, 0, zp_imageWidth($im), zp_imageHeight($im), 45);
			// Add text
			imagestring ( $im,  5,  $x-1,  $y-1, $text,  0x0ffffff );
			imagestring ( $im,  5,  $x+1,  $y+1, $text,  0x0ffffff );
			imagestring ( $im,  5,  $x,  $y,   $text,  0x0ff0000 );
			// Save new theme image
			zp_imageOutput($im, 'png', $themeimage);
		}	
	}

	return $message;
}

function deleteThemeDirectory($source) {
	global $_zp_current_admin;
	if (is_dir($source)) {
		$result = true;
		$handle = opendir($source);
		while (false !== ($filename = readdir($handle))) {
			$fullname = $source . '/' . $filename;
			if (is_dir($fullname) && !(substr($filename, 0, 1) == '.')) {
				if (($filename != '.') && ($filename != '..')) {
					$result = $result && deleteThemeDirectory($fullname);
				}
			} else {
				if (file_exists($fullname) && !(substr($filename, 0, 1) == '.')) {
					$result = $result && @unlink($fullname);
				}
			}

		}
		closedir($handle);
		$result = $result && @rmdir($source);
		return $result;
	}
	return false;
}

/**
 * Return URL of current admin page, encoded for a form, relative to zp-core folder
 *
 * @return string current URL
 * @author Ozh
 * @since 1.3
 * 
 * @param string $source the script file incase REQUEST_URI is not available
 */
function currentRelativeURL($source) {
	if (isset($_SERVER['REQUEST_URI'])) {
		$from = PROTOCOL."://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // full requested URL
		$from = str_replace( FULLWEBPATH , '', $from); // Make relative to zenphoto installation
		return urlencode(stripslashes( $from ));
	} else {
		$source = str_replace(SERVERPATH, '', $source);
		return $source;
	}
}

/**
 * Returns an array of the names of the parents of the current album.
 *
 * @param object $album optional album object to use inseted of the current album
 * @return array
 */
function getParentAlbumsAdmin($album) {
	$parents = array();
	while (!is_null($album = $album->getParent())) {
		array_unshift($parents, $album);
	}
	return $parents;
}

/**
 * prints the album breadcrumb for the album edit page
 *
 * @param object $album Object of the album
 */
function printAlbumBreadcrumbAdmin($album) {
	$parents = getParentAlbumsAdmin($album);
	foreach($parents as $parent) {
		echo "<a href='admin-edit.php?page=edit&amp;album=".pathurlencode($parent->name)."'>".removeParentAlbumNames($parent)."</a>/";
	}
}

/**
 * Removes the parent album name so that we can print a album breadcrumb with them
 *
 * @param object $album Object of the album
 * @return string
 */
function removeParentAlbumNames($album) {
	$slash = stristr($album->name,"/");
	if($slash) {
		$array = explode("/",$album->name);
		$array = array_reverse($array);
		$albumname = $array[0];
	} else {
		$albumname = $album->name;
	}
	return $albumname;
}

/**
 * Outputs the rights checkbox table for admin
 *
 * @param $id int record id for the save
 * @param string $background background color
 * @param string $alterrights are the items changable
 * @param bit $rights rights of the admin
 */
function printAdminRightsTable($id, $background, $alterrights, $rights) {
	global $_admin_rights;
	?>
	<table class="checkboxes" > <!-- checkbox table -->
		<tr>
			<td style="padding-bottom: 3px;<?php echo $background; ?>" colspan="5">
			<strong><?php echo gettext("Rights"); ?></strong>:
			</td>
		</tr>
		<?php
		$element = 3;
		foreach ($_admin_rights as $rightselement=>$rightsvalue) {
			$name = strtolower(str_replace('_', ' ', str_replace('_RIGHTS', '', $rightselement)));
			$name[0] = strtoupper($name[0]);
			if ($element>2) {
				$element = 0;
				?>
				<tr>
				<?php
			}
			?>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
					<span style="white-space:nowrap">
						<label><input type="checkbox" name="<?php echo $id.'-'.$rightselement; ?>" id="<?php echo $id.'-'.$rightselement; ?>"
								value=<?php echo $rightsvalue; if ($rights & $rightsvalue) echo ' checked'; 
								echo $alterrights; ?> /> <?php echo $name; ?></label>
					</span>
				</td>
			<?php
			$element++;
			if ($element > 2) {
				?>
				</tr>
				<?php
			}
		}

		if ($element <= 2) {
			?>
			</tr>
			<?php
		}
		?>
	</table> <!-- end checkbox table -->
	<?php
}

/**
 * Returns a list of album names managed by $id
 *
 * @param int $id admin ID
 * @return array
 */
function populateManagedAlbumList($id) {
	$cv = array();
	$sql = "SELECT ".prefix('albums').".`folder` FROM ".prefix('albums').", ".
					prefix('admintoalbum')." WHERE ".prefix('admintoalbum').".adminid=".
					$id." AND ".prefix('albums').".id=".prefix('admintoalbum').".albumid";
	$currentvalues = query_full_array($sql);
	foreach($currentvalues as $albumitem) {
		$cv[] = $albumitem['folder'];
	}
	return $cv;
}

/**
 * Creates the managed album table for Admin
 *
 * @param array $albumlist list of admin
 * @param string $alterrights are the items changable
 * @param int $adminid ID of the admin
 * @param int $prefix the admin row
 */
function printManagedAlbums($albumlist, $alterrights, $adminid, $prefix) {
	$cv = populateManagedAlbumList($adminid);
	$rest = array_diff($albumlist, $cv);
	$prefix = 'managed_albums_'.$prefix.'_';
	?>
	<h2 class="h2_bordered_albums">
	<a href="javascript:toggle('<?php echo $prefix ?>managed_albums');"><?php echo gettext("Managed albums:"); ?></a>
	</h2>
	<div class="box-albums-unpadded">
		<div id="<?php echo $prefix ?>managed_albums" style="display:none" >
			<ul class="albumchecklist">
				<?php
				generateUnorderedListFromArray($cv, $cv, $prefix, $alterrights, true, false);
				if (empty($alterrights)) {
					generateUnorderedListFromArray(array(), $rest, $prefix, false, true, false);
				}
				?>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * processes the post of administrator rights
 *
 * @param int $i the admin row number
 * @return bit
 */
function processRights($i) {
	global $_admin_rights;
	$rights = 0;
	if (isset($_POST[$i.'-confirmed'])) $rights = $rights | NO_RIGHTS;
	foreach ($_admin_rights as $name=>$right) {
		if (isset($_POST[$i.'-'.$name])) $rights = $rights | $right;
	}
	return $rights;
}

function processManagedAlbums($i) {
	$managedalbums = array();
	$l = strlen($albumsprefix = 'managed_albums_'.$i.'_');
	foreach ($_POST as $key => $value) {
		$key = postIndexDecode($key);
		if (substr($key, 0, $l) == $albumsprefix) {
			if ($value) {
				$managedalbums[] = substr($key, $l);
			}
		}
	}
	if (count($managedalbums > 0)) {
		$albums = array_unique($managedalbums);
	} else {
		$albums = NULL;
	}
	return $albums;
}

/**
 * Returns the value of a checkbox form item
 *
 * @param string $id the $_REQUEST index
 * @return int (0 or 1)
 */
function getCheckboxState($id) {
	if (isset($_REQUEST[$id])) return 1; else return 0;
}

/**
 * Returns an array of "standard" theme scripts. This list is
 * normally used to exclude these scripts form various option seletors.
 *
 * @return array
 */
function standardScripts() {
	$standardlist = array('themeoptions', 'password', 'theme_description', '404', 'slideshow', 'search', 'image', 'index', 'album', 'customfunctions');
	if (getOption('zp_plugin_zenpage')) $standardlist = array_merge($standardlist, array(ZENPAGE_NEWS, ZENPAGE_PAGES));
	return $standardlist;
}
?>