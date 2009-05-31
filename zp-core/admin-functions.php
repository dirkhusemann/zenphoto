<?php
/**
 * support functions for Admin
 * @package admin
 */

// force UTF-8 Ø

if (session_id() == '') session_start();

$_zp_admin_ordered_taglist = NULL;
$_zp_admin_LC_taglist = NULL;
$_zp_admin_album_list = null;
define('TEXTAREA_COLUMNS', 50);
define('TEXT_INPUT_SIZE', 48);
define('TEXTAREA_COLUMNS_SHORT', 32);
define('TEXT_INPUT_SIZE_SHORT', 30);
require_once(dirname(__FILE__).'/class-load.php');
require_once(dirname(__FILE__).'/functions.php');


$sortby = array(gettext('Filename') => 'filename',
								gettext('Date') => 'date',
								gettext('Title') => 'title',
								gettext('ID') => 'id',
								gettext('Filemtime') => 'mtime'
								);
$charsets = array("ASMO-708" => "Arabic",
									"BIG5" => "Chinese Traditional",
									"CP1026" => "IBM EBCDIC (Turkish Latin-5)",
									"cp866" => "Cyrillic (DOS)",
									"CP870" => "IBM EBCDIC (Multilingual Latin-2)",
									"CISO2022JP" => "Japanese (JIS-Allow 1 byte Kana)",
									"DOS-720" => "Arabic (DOS)",
									"DOS-862" => "Hebrew (DOS)",
									"EBCDIC-CP-US" => "IBM EBCDIC (US-Canada)",
									"EUC-CN" => "Chinese Simplified (EUC)",
									"EUC-JP" => "Japanese (EUC)",
									"EUC-KR" => "Korean (EUC)",
									"GB2312" => "Chinese Simplified (GB2312)",
									"HZ-GB-2312" => "Chinese Simplified (HZ)",
									"IBM437" => "OEM United States",
									"IBM737" => "Greek (DOS)",
									"IBM775" => "Baltic (DOS)",
									"IBM850" => "Western European (DOS)",
									"IBM852" => "Central European (DOS)",
									"IBM857" => "Turkish (DOS)",
									"IBM861" => "Icelandic (DOS)",
									"IBM869" => "Greek, Modern (DOS)",
									"ISO-2022-JP" => "Japanese (JIS)",
									"ISO-2022-JP" => "Japanese (JIS-Allow 1 byte Kana - SO/SI)",
									"ISO-2022-KR" => "Korean (ISO)",
									"ISO-8859-1" => "Western European (ISO)",
									"ISO-8859-15" => "Latin 9 (ISO)",
									"ISO-8859-2" => "Central European (ISO)",
									"ISO-8859-3" => "Latin 3 (ISO)",
									"ISO-8859-4" => "Baltic (ISO)",
									"ISO-8859-5" => "Cyrillic (ISO)",
									"ISO-8859-6" => "Arabic (ISO)",
									"ISO-8859-7" => "Greek (ISO)",
									"ISO-8859-8" => "Hebrew (ISO-Visual)",
									"ISO-8859-8-i" => "Hebrew (ISO-Logical)",
									"ISO-8859-9" => "Turkish (ISO)",
									"JOHAB" => "Korean (Johab)",
									"KOi8-R" => "Cyrillic (KOI8-R)",
									"KOi8-U" => "Cyrillic (KOI8-U)",
									"KS_C_5601-1987" => "Korean",
									"MACINTOSH" => "Western European (MAC)",
									"SHIFT_JIS" => "Japanese (Shift-JIS)",
									"UNICODE" => "Unicode",
									"UNICODEFFFE" => "Unicode (Big-Endian)",
									"US-ASCII" => "US-ASCII",
									"UTF-7" => "Unicode (UTF-7)",
									"UTF-8" => "Unicode (UTF-8)",
									"WINDOWS-1250" => "Central European (Windows)",
									"WINDOWS-1251" => "Cyrillic (Windows)",
									"WINDOWS-1252" => "Western European (Windows)",
									"WINDOWS-1253" => "Greek (Windows)",
									"WINDOWS-1254" => "Turkish (Windows)",
									"WINDOWS-1255" => "Hebrew (Windows)",
									"WINDOWS-1256" => "Arabic (Windows)",
									"WINDOWS-1257" => "Baltic (Windows)",
									"WINDOWS-1258" => "Vietnamese (Windows)",
									"WINDOWS-874" => "Thai (Windows)",
									"X-CHINESE-CNS" => "Chinese Traditional (CNS)",
									"X-CHINESE-ETEN" => "Chinese Traditional (Eten)",
									"X-EBCDIC-Arabic" => "IBM EBCDIC (Arabic)",
									"X-EBCDIC-CP-US-EURO" => "IBM EBCDIC (US-Canada-Euro)",
									"X-EBCDIC-CYRILLICRUSSIAN" => "IBM EBCDIC (Cyrillic Russian)",
									"X-EBCDIC-CYRILLICSERBIANBULGARIAN" => "IBM EBCDIC (Cyrillic Serbian-Bulgarian)",
									"X-EBCDIC-DENMARKNORWAY" => "IBM EBCDIC (Denmark-Norway)",
									"X-EBCDIC-DENMARKNORWAY-euro" => "IBM EBCDIC (Denmark-Norway-Euro)",
									"X-EBCDIC-FINLANDSWEDEN" => "IBM EBCDIC (Finland-Sweden)",
									"X-EBCDIC-FINLANDSWEDEN-EURO" => "IBM EBCDIC (Finland-Sweden-Euro)",
									"X-EBCDIC-FINLANDSWEDEN-EURO" => "IBM EBCDIC (Finland-Sweden-Euro)",
									"X-EBCDIC-FRANCE-EURO" => "IBM EBCDIC (France-Euro)",
									"X-EBCDIC-GERMANY" => "IBM EBCDIC (Germany)",
									"X-EBCDIC-GERMANY-EURO" => "IBM EBCDIC (Germany-Euro)",
									"X-EBCDIC-GREEK" => "IBM EBCDIC (Greek)",
									"X-EBCDIC-GREEKMODERN" => "IBM EBCDIC (Greek Modern)",
									"X-EBCDIC-HEBREW" => "IBM EBCDIC (Hebrew)",
									"X-EBCDIC-ICELANDIC" => "IBM EBCDIC (Icelandic)",
									"X-EBCDIC-ICELANDIC-EURO" => "IBM EBCDIC (Icelandic-Euro)",
									"X-EBCDIC-INTERNATIONAL-EURO" => "IBM EBCDIC (International-Euro)",
									"X-EBCDIC-ITALY" => "IBM EBCDIC (Italy)",
									"X-EBCDIC-ITALY-EURO" => "IBM EBCDIC (Italy-Euro)",
									"X-EBCDIC-JAPANESEANDJAPANESELATIN" => "IBM EBCDIC (Japanese and Japanese-Latin)",
									"X-EBCDIC-JAPANESEANDKANA" => "IBM EBCDIC (Japanese and Japanese Katakana)",
									"X-EBCDIC-JAPANESEANDUSCANADA" => "IBM EBCDIC (Japanese and US-Canada)",
									"X-EBCDIC-JAPANESEKATAKANA" => "IBM EBCDIC (Japanese katakana)",
									"X-EBCDIC-KOREANANDKOREANEXTENDED" => "IBM EBCDIC (Korean and Korean EXtended)",
									"X-EBCDIC-KOREANEXTENDED" => "IBM EBCDIC (Korean EXtended)",
									"X-EBCDIC-SIMPLIFIEDCHINESE" => "IBM EBCDIC (Simplified Chinese)",
									"X-EBCDIC-SPAIN" => "IBM EBCDIC (Spain)",
									"X-ebcdic-SPAIN-EURO" => "IBM EBCDIC (Spain-Euro)",
									"X-EBCDIC-THAI" => "IBM EBCDIC (Thai)",
									"X-EBCDIC-TRADITIONALCHINESE" => "IBM EBCDIC (Traditional Chinese)",
									"X-EBCDIC-TURKISH" => "IBM EBCDIC (Turkish)",
									"X-EBCDIC-UK" => "IBM EBCDIC (UK)",
									"X-EBCDIC-UK-EURO" => "IBM EBCDIC (UK-Euro)",
									"X-EUROPA" => "Europa",
									"X-IA5" => "Western European (IA5)",
									"X-IA5-GERMAN" => "German (IA5)",
									"X-IA5-NORWEGIAN" => "Norwegian (IA5)",
									"X-IA5-SWEDISH" => "Swedish (IA5)",
									"X-ISCII-AS" => "ISCII Assamese",
									"X-ISCII-BE" => "ISCII Bengali",
									"X-ISCII-DE" => "ISCII Devanagari",
									"X-ISCII-GU" => "ISCII Gujarathi",
									"X-ISCII-KA" => "ISCII Kannada",
									"X-ISCII-MA" => "ISCII Malayalam",
									"X-ISCII-OR" => "ISCII Oriya",
									"X-ISCII-PA" => "ISCII Panjabi",
									"X-ISCII-TA" => "ISCII Tamil",
									"X-ISCII-TE" => "ISCII Telugu",
									"X-MAC-ARABIC" => "Arabic (Mac)",
									"X-MAC-CE" => "Central European (Mac)",
									"X-MAC-CHINESESIMP" => "Chinese Simplified (Mac)",
									"X-MAC-CHINESETRAD" => "Chinese Traditional (Mac)",
									"X-MAC-CYRILLIC" => "Cyrillic (Mac)",
									"X-MAC-GREEK" => "Greek (Mac)",
									"X-MAC-HEBREW" => "Hebrew (Mac)",
									"X-MAC-ICELANDIC" => "Icelandic (Mac)",
									"X-MAC-JAPANESE" => "Japanese (Mac)",
									"X-MAC-KOREAN" => "Korean (Mac)",
									"X-MAC-TURKISH" => "Turkish (Mac)"
									);

if (OFFSET_PATH) {									
	// setup sub-tab arrays for use in dropdown
	$optiontabs = array(gettext("admin")=>'admin-options.php?tab=admin');
	if (!(($_zp_loggedin == ADMIN_RIGHTS) || $_zp_reset_admin)) {
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
			$optiontabs[gettext("gallery")] = 'admin-options.php?tab=gallery';
			$optiontabs[gettext("general")] = 'admin-options.php?tab=general';
			$optiontabs[gettext("search")] = 'admin-options.php?tab=search';
			$optiontabs[gettext("rss")] = 'admin-options.php?tab=rss';
			$optiontabs[gettext("image")] = 'admin-options.php?tab=image';
			$optiontabs[gettext("comment")] = 'admin-options.php?tab=comments';
		}
		if ($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) {
			$optiontabs[gettext("theme")] = 'admin-options.php?tab=theme';
		}
		if ($_zp_loggedin & ADMIN_RIGHTS) {
			$optiontabs[gettext("plugin")] = 'admin-options.php?tab=plugin';
		}
	}
	$flipped = array_flip($optiontabs);
	natsort($flipped);
	$optiontabs = array_flip($flipped);
	
	$newstabs = array(gettext('articles')=>substr(PLUGIN_FOLDER,1).'zenpage/admin-news-articles.php?tab=articles', 
										gettext('categories')=>substr(PLUGIN_FOLDER,1).'zenpage/admin-categories.php?tab=categories');
}

/**
 * Test to see whether we should be displaying a particular page.
 *
 * @param $page  The page we for which we are testing.
 *
 * @return True if this is the page, false otherwise.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function issetPage($page) {
	if (isset($_GET['page'])) {
		$pageval = strip($_GET['page']);
		if ($pageval == $page) {
			return true;
		}
	}
	return false;
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
function printAdminHeader($path='') {
	header ('Content-Type: text/html; charset=' . getOption('charset'));
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
	if (file_exists(dirname(__FILE__).'/js/editor_config.js.php')) require_once(dirname(__FILE__).'/js/editor_config.js.php');	
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
	printLink("admin-edit.php?page=edit". $param, $text, $title, $class, $id);
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
	if ($_zp_login_error == 1) {
	?>
		<div class="errorbox" id="message"><h2><?php echo gettext("There was an error logging in."); ?></h2><?php echo gettext("Check your username and password and try again.");?></div>
	<?php
	} else if ($_zp_login_error == 2){
	?>
		<div class="messagebox" id="fade-message">
		<h2><?php echo gettext("A reset request has been sent."); ?></h2>
		</div>
	<?php } ?>
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
	global $_zp_loggedin;
	?>
	<ul class="nav">
	<?php
	if (($_zp_loggedin & (MAIN_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "home") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin.php'; ?>"><?php echo gettext("overview"); ?></a>
		</li>
 		<?php
	}
	if (($_zp_loggedin & (COMMENT_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "comments") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-comments.php'; ?>"><?php echo gettext("comments"); ?></a>
		</li>
 		<?php
	}
	if (($_zp_loggedin & (UPLOAD_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "upload") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-upload.php'; ?>"><?php echo gettext("upload"); ?></a>
		</li>
 		<?php
	}

	if (($_zp_loggedin & (EDIT_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "edit") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-edit.php?page=edit'; ?>"><?php echo gettext("edit"); ?></a>
		</li>
 		<?php
	}
	if (($_zp_loggedin & (TAGS_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "tags") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-tags.php'; ?>"><?php echo gettext("tags"); ?></a>
		</li>
 		<?php
	}
	?>
	<li <?php if($currenttab == "options") echo 'class="current"' ?>>
	<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-options.php'; ?>"><?php echo gettext("options"); ?></a>
	</li>
 	<?php
	if (($_zp_loggedin & (THEMES_RIGHTS | ADMIN_RIGHTS))) {
		?>
		<li <?php if($currenttab == "themes") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-themes.php'; ?>"><?php echo gettext("themes"); ?></a>
		</li>
 		<?php
	}
	if (($_zp_loggedin & ADMIN_RIGHTS)) {
		?>
		<li <?php if($currenttab == "plugins") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.'/admin-plugins.php'; ?>"><?php echo gettext("plugins"); ?></a>
		</li>
 		<?php
	}
	if (getOption('zp_plugin_zenpage') && ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
		?>
		<li <?php if($currenttab == "pages") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER.'zenpage/admin-pages.php'; ?>"><?php echo gettext("pages"); ?></a>
		</li>
 		<?php
		?>
		<li <?php if($currenttab == "articles") echo 'class="current"' ?>>
		<a href="<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER.'zenpage/admin-news-articles.php'; ?>"><?php echo gettext("news"); ?></a>
		</li>
 		<?php
	}
	?>
	</ul>
	<?php
}

function getSubtabs($tabs) {
	if (isset($_GET['tab'])) {
		$current = sanitize($_GET['tab']);
	} else {
		$current = $tabs;
		$current = array_shift($current);
		$i = strrpos($current, '=');
		if ($i===false) {
			$current = '';
		} else {
			$current = substr($current, $i+1);
		}
	}
	return $current;
}

function printSubtabs($tabs) {
	$current = getSubtabs($tabs);
	?>
	<ul class="subnav">
	<?php
	foreach ($tabs as $key=>$link) {
		$tab = substr($link, strrpos($link, '=')+1);
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
	global $gallery;
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
		$ntdel = strip($_GET['ndeleted']);
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
	$sql = "SELECT `value` FROM " . prefix($alb) . " WHERE `name`='" . escape($option) . "'".$where;
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
 * 		0: a textbox
 * 		1: a checkbox
 * 		2: handled by $optionHandler->handleOption()
 * 		3: a textarea
 * 		4: radio buttons (button names are in the 'buttons' index of the supported options array)
 * 		5: selector (selection list is in the 'selections' index of the supported options array)
 * 		6: checkbox array (checkboxed list is in the 'checkboxes' index of the suppoprted options array.)
 * 		7: checkbox UL (checkboxed list is in the 'checkboxes' index of the suppoprted options array.)
 * 		8: Color picker
 *
 * type 0 and 3 support multi-lingual strings.
 */
function customOptions($optionHandler, $indent="", $album=NULL, $hide=false) {
	$supportedOptions = $optionHandler->getOptionsSupported();
	if (count($supportedOptions) > 0) {
		$options = array_keys($supportedOptions);
		natcasesort($options);
		foreach($options as $option) {
			$row = $supportedOptions[$option];
			$type = $row['type'];
			$desc = $row['desc'];
			if (isset($row['multilingual'])) {
				$multilingual = $row['multilingual'];
			} else {
				$multilingual = $type == 3;
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
				$sql = "SELECT `value` FROM " . prefix('options') . " WHERE `name`='" . escape($key) .
										"' AND `ownerid`=".$album->id;

				$db = query_single_row($sql);
			}
			if (!$db) {
				$sql = "SELECT `value` FROM " . prefix('options') . " WHERE `name`='" . escape($key) . "';";
				$db = query_single_row($sql);
			}
			if ($db) {
				$v = $db['value'];
			} else {
				$v = 0;
			}

			if ($hide) echo "\n<tr class='".$hide."extrainfo' style='display:none'>\n";
			echo '<td width="175">' . $indent . $option . ":</td>\n";

			switch ($type) {
				case 0:  // text box
				case 3:  // text area
					echo '<td width="350px">';
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'text-'.$key.'" value=0 />'."\n";
					if ($multilingual) {
						print_language_string_list($v, $key, $type, NULL, $editor);
					} else {
						echo '<input type="text" size="40" name="' . $key . '" style="width: 338px" value="' . html_encode($v) . '">' . "\n";
					}
					echo '</td>' . "\n";
					break;
				case 1:  // check box
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'chkbox-'.$key.'" value=0 />' . "\n";
					echo '<td width="350px"><input type="checkbox" name="'.$key.'" value="1"';
					echo checked('1', $v);
					echo " /></td>\n";
					break;
				case 2:  // custom handling
					echo '<td width="350px">' . "\n";
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'custom-'.$key.'" value=0 />' . "\n";
					$optionHandler->handleOption($key, $v);
					echo "</td>\n";
					break;
				case 4: // radio button
					echo '<td width="350px">' . "\n";
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'radio-'.$key.'" value=0 />' . "\n";
					generateRadiobuttonsFromArray($v,$row['buttons'],$key);
					echo "</td>\n";
					break;
				case 5: // selector
					echo '<td width="350px">' . "\n";
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'selector-'.$key.'" value=0 />' . "\n";
					echo '<select id="'.$option.'" name="'.$key.'">'."\n";
					generateListFromArray(array($v),$row['selections'], false, true);
					echo "</select>\n";
					echo "</td>\n";
					break;
				case 6: // checkbox array
					echo "<td width=\"350px>\"\n";
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
					echo "</td>\n";
					break;
				case 7: // checkbox UL
					echo "<td width=\"350px>\"\n";
					$cvarray = array();
					$c = 0;
					foreach ($row['checkboxes'] as $display=>$checkbox) {
						echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'chkbox-'.$checkbox.'" value=0 />' . "\n";
						$ck_sql = str_replace($key, $checkbox, $sql);
						$db = query_single_row($ck_sql);
						if ($db) {
							if ($db['value'])	$cvarray[$c++] = $checkbox;
						}
					}
					echo '<ul class="customchecklist">'."\n";
					generateUnorderedListFromArray($cvarray, $row['checkboxes'], '', '', true, true);
					echo '</ul>';
					echo "</td>\n";
					break;
				case 8: // Color picker
					echo '<td width="350px" style="margin:0; padding:0">' . "\n";
					echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'text-'.$key.'" value=0 />' . "\n";
					?>
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
					<?php
					echo "</td>\n";
					break;
			}
			echo '<td>' . $desc . "</td>\n";
			echo "</tr>\n";
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
	$str = str_replace('%2E', '.', strip($str));
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
		generateUnorderedListFromArray($tags, $tags, $postit, false, true, false);
		echo '<hr />';
	}
	generateUnorderedListFromArray(array(), $displaylist, $postit, false, true, false);
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
			<p></p>
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
	$custom = apply_filter('edit_album_custom_data', '', $album, $prefix);
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
  ?>
  
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
						<input type=\"checkbox\" id=\"".$prefix."reset_rating\" name=\"".$prefix."reset_rating\" value=1>
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
			echo apply_filter('edit_album_utilities', '', $album, $prefix);
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
	
<br / clear:all>
<p class="buttons">
<button type="submit" title="<?php echo gettext("Save Album"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save Album"); ?></strong></button>
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
	?><hr />
		<form name="clear-cache" action="?action=clear_cache" method="post" style="float: left">
		<input type="hidden" name="action" value="clear_cache">
		<input type="hidden" name="album" value="<?php echo urlencode($album->name); ?> ">
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
		<input type="hidden" name=\return" value="<?php echo urlencode($album->name); ?> ">
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
	<a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="<?php echo sprintf(gettext('Edit this album:%s'), $album->name); ?>">
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
			$tag = trim(strip($_POST[$tagsprefix.'new_tag_value_'.$i]));
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
	$album->setDateTime(strip($_POST[$prefix."albumdate"]));
	$album->setPlace(process_language_string_save($prefix.'albumplace', 3));
	if (isset($_POST[$prefix.'thumb'])) $album->setAlbumThumb(strip($_POST[$prefix.'thumb']));
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
		$newtheme = strip($_POST[$prefix.'album_theme']);
		if ($oldtheme != $newtheme) {
			$album->setAlbumTheme($newtheme);
		}
	}
	$album->setPasswordHint(process_language_string_save($prefix.'albumpass_hint', 3));
	$custom = process_language_string_save($prefix.'album_custom_data', 1);
	$album->setCustomData(apply_filter('save_album_custom_data', $custom, $prefix));
	apply_filter('save_album_utilities_data', $album, $prefix);
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
		if ($wvd > $cvd) {
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
function print_language_string_list($dbstring, $name, $textbox=false, $locale=NULL, $edit='', $short=false) {
	global $_zp_languages, $_zp_active_languages, $_zp_current_locale;
	if (!empty($edit)) $edit = ' class="'.$edit.'"';
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
		natsort($emptylang);
		if ($textbox) $class = 'box'; else $class = '';
		echo '<ul class="'.($short ? 'language_string_list_short' : 'language_string_list').$class.'"'.">\n";
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
			echo '<textarea name="'.$name.'_'.$locale.'"'.$edit.' cols="'.($short ? TEXTAREA_COLUMNS_SHORT : TEXTAREA_COLUMNS).'"	rows="6">'.htmlentities($dbstring,ENT_COMPAT,getOption("charset")).'</textarea>';
		} else {
			echo '<input id="'.$name.'_'.$locale.'" name="'.$name.'_'.$locale.'" type="text" value="'.$dbstring.'" size="'.($short ? TEXT_INPUT_SIZE_SHORT : TEXT_INPUT_SIZE).'" />';
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
	$string = apply_filter('seoFriendlyURL', $source);	
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

?>
