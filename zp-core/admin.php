<?php
/**
 * admin.php is the main script for administrative functions.
 * @package admin
 */

// force UTF-8 Ø

/* Don't put anything before this line! */	
define('OFFSET_PATH', 1);

// The login will always occur in this file thanks to printLoginForm() function.
// to implement 'server_protocol' == 'https_admin' we need to redirect to https
// very soon in that file (before auth_zp.php is processed as it might clear 
// the cookies)
// When already logged in, auth_zp.php take care of the redirection.
// To retrive getOption function:
require_once(dirname(__FILE__).'/functions-basic.php');
if (getOption('server_protocol') == 'https_admin') {
	// force https login
	if (!isset($_SERVER["HTTPS"])) {
		$redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		header("Location:$redirect");
		exit();
	}
}

require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');
require_once(dirname(__FILE__).'/functions-rss.php');
if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}
if(getOption('zp_plugin_zenpage')) {
	require_once(dirname(__FILE__).'/'.PLUGIN_FOLDER.'/zenpage/zenpage-admin-functions.php'); 
}
if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */
	if ($_zp_loggedin == ADMIN_RIGHTS || $_zp_reset_admin  || !($_zp_loggedin&(ADMIN_RIGHTS | OVERVIEW_RIGHTS))) { // user/password set required or does not have rights to this page.
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-users.php");
	}
	
	$gallery = new Gallery();
	$gallery->garbageCollect();
	if (isset($_GET['action'])) {
		$rightsneeded = array('external'=>ALL_RIGHTS, 'check_for_update'=>OVERVIEW_RIGHTS);
		$action = $_GET['action'];
		$needs = ADMIN_RIGHTS;
		if (isset($rightsneeded[$action])) {
			$needs = $rightsneeded[$action] | ADMIN_RIGHTS;
		}
		if (zp_loggedin($needs)) {
			switch ($action) {
				/** clear the cache ***********************************************************/
				/******************************************************************************/
				case "clear_cache":
					$gallery->clearCache();
					$class = 'messagebox';
					$msg = gettext('Image cache cleared.');
					break;

					/** clear the RSScache ***********************************************************/
					/******************************************************************************/
				case "clear_rss_cache":
					clearRSScache();
					$class = 'messagebox';
					$msg = gettext('RSS cache cleared.');
					break;

					/** Reset hitcounters ***********************************************************/
					/********************************************************************************/
				case "reset_hitcounters":
					query('UPDATE ' . prefix('albums') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('images') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('zenpage_news') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('zenpage_pages') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('zenpage_news_categories') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('options') . ' SET `value`= 0 WHERE `name` LIKE "Page-Hitcounter-%"');
					$class = 'messagebox';
					$msg = gettext('All hitcounters have been set to zero');
					break;

					/** check for update ***********************************************************/
					/********************************************************************************/
				case 'check_for_update':
					$v = checkForUpdate();
					if (empty($v)) {
						$class = 'messagebox';
						$msg = gettext("You are running the latest zenphoto version.");
					} else {
						$class = 'errorbox';
						if ($v == 'X') {
							$msg = gettext("Could not connect to <a href=\"http://www.zenphoto.org\">zenphoto.org</a>");
						} else {
							$msg =  "<a href=\"http://www.zenphoto.org\">". sprintf(gettext("zenphoto version %s is available."), $v)."</a>";
						}
					}
					break;
					//** external script return
				case 'external':
					if (isset($_GET['error'])) {
						$class = 'errorbox';
					} else {
						$class = 'messagebox';
					}
					if (isset($_GET['msg'])) {
						$msg = sanitize($_GET['msg']);
					} else {
						$msg = '';
					}
					break;
			}
		} else {
			$class = 'errorbox';
			$actions = array(	'clear_cache'=>gettext('purge Image cache'),
												'clear_rss_cache'=>gettext('purge RSS cache'),
												'reset_hitcounters'=>gettext('reset all hitcounters'));
			$msg = sprintf(gettext('You do not have proper rights to %s.'),$actions[$action]);
		}
	} else if (isset($_GET['from'])) {
		$class = 'errorbox';
		$msg = sprintf(gettext('You do not have proper rights to access %s.'),urldecode(sanitize($_GET['from'])));
	}

/************************************************************************************/
/** End Action Handling *************************************************************/
/************************************************************************************/	
	
}


// Print our header
printAdminHeader();

echo "\n</head>";
if (!zp_loggedin()) { ?>
	<body style="background-image: none">
<?php } else { ?>
<body>
<?php }
// If they are not logged in, display the login form and exit

if (!zp_loggedin()) {
	if (isset($_GET['from'])) {
		$from = sanitize($_GET['from']);
		$from = urldecode($from);
	} else {
		$from = urldecode(currentRelativeURL(__FILE__));
	}
	printLoginForm($from);
	echo "\n</body>";
	echo "\n</html>";
	exit();

} else { /* Admin-only content safe from here on. */
	printLogoAndLinks();
	?>
<div id="main">
<?php printTabs('home'); ?>
<div id="content">
<?php


/*** HOME ***************************************************************************/
/************************************************************************************/

if (!empty($msg)) {
	?>
	<div class="<?php echo $class; ?>" id="fade-message">
		<h2><?php echo $msg; ?></h2>
	</div>
	<?php
}
?>
<div id="overview-leftcolumn">
<div class="boxouter">
<div class="box" id="overview-gallerystats">
<h2 class="h2_bordered"><?php echo gettext("Gallery Stats"); ?></h2>
<ul>
<li>
<?php
$t = $gallery->getNumImages();
$c = $t-$gallery->getNumImages(true);
if ($c > 0) {
	printf(ngettext('<strong>%1$u</strong> Image (%2$u not visible)','<strong>%1$u</strong> Images (%2$u not visible)',$t),$t, $c);
} else {
	printf(ngettext('<strong>%u</strong> Image','<strong>%u</strong> Images',$t),$t);
}
?>
</li>
<li>
<?php
$t = $gallery->getNumAlbums(true);
$c = $t-$gallery->getNumAlbums(true,true);
if ($c > 0) {
	printf(ngettext('<strong>%1$u</strong> Album (%2$u not published)','<strong>%1$u</strong> Albums (%2$u not published)',$t),$t, $c);
} else {
	printf(ngettext('<strong>%u</strong> Album', '<strong>%u</strong> Albums',$t),$t);
}
?>
</li>
<li>
<?php 
$t = $gallery->getNumComments(true);
$c = $t - $gallery->getNumComments(false);
if ($c > 0) {
	printf(ngettext('<strong>%1$u</strong> Comment (<strong>%2$u</strong> in moderation)','<strong>%1$u</strong> Comments (<strong>%2$u</strong> in moderation)', $t), $t, $c);
} else {
	printf(ngettext('<strong>%u</strong> Comment','<strong>%u</strong> Comments', $t), $t);
}
?>
</li>
<?php 
if(getOption('zp_plugin_zenpage')) { ?>
	<li>
		<?php
		list($total,$type,$unpub) = getNewsPagesStatistic("pages");
		if (empty($unpub)) {
			printf(ngettext('<strong>%1$u</strong> Page','<strong>%1$u</strong> Pages',$total),$total,$type);
		} else {
			printf(ngettext('<strong>%1$u</strong> Page (<strong>%2$u</strong> unpublished)','<strong>%1$u</strong> Pages (<strong>%2$u</strong> unpublished)',$total),$total,$unpub);
		}
		?>
	</li>
	<li>
		<?php
		list($total,$type,$unpub) = getNewsPagesStatistic("news");
		if (empty($unpub)) {
			printf(ngettext('<strong>%1$u</strong> News','<strong>%1$u</strong> News',$total),$total);
		} else {
			printf(ngettext('<strong>%1$u</strong> News (<strong>%2$u</strong> unpublished)','<strong>%1$u</strong> News (<strong>%2$u</strong> unpublished)',$total),$total,$unpub);
		}
		?>
	</li>
	<li>
		<?php
		list($total,$type,$unpub) = getNewsPagesStatistic("categories");
		printf(ngettext('<strong>%1$u</strong> Category','<strong>%1$u</strong> Categories',$total),$total);
		?>
	</li>
<?php
}
?>
</ul>
</div>
</div>

<div class="box" id="overview-info">
<h2 class="h2_bordered"><?php echo gettext("Installation information"); ?></h2> 
<ul>
<?php

if (defined('RELEASE')) {
		$official = gettext('Official Build');
	} else {
		$official = gettext('SVN');
	}
	$graphics_lib = zp_graphicsLibInfo();
	?>
	<li><?php printf(gettext('Zenphoto version <strong>%1$s [%2$s] (%3$s)</strong>'),ZENPHOTO_VERSION,ZENPHOTO_RELEASE,$official); ?></li>
	<li><?php printf(gettext('Current gallery theme: <strong>%1$s</strong>'),$gallery->getCurrentTheme()); ?></li> 
	<li><?php printf(gettext('PHP version: <strong>%1$s</strong>'),phpversion()); ?></li>
	<li><?php printf(gettext("Graphics support: <strong>%s</strong>"),$graphics_lib['Library']); ?></li>
	<li><?php printf(gettext('PHP memory limit: <strong>%1$s</strong> (Note: Your server might allocate less!)'),INI_GET('memory_limit')); ?></li>
	<li>
		<?php
		$mysqlv = trim(@mysql_get_server_info());
		$i = strpos($mysqlv, "-");
		if ($i !== false) {
			$mysqlv = substr($mysqlv, 0, $i);
		}
		printf(gettext('MySQL version: <strong>%1$s</strong>'),$mysqlv);
		?>
	</li>
	<li><?php printf(gettext('Database name: <strong>%1$s</strong>'),$_zp_conf_vars['mysql_database']); ?></li>
	<li>
	<?php
	if(!empty($_zp_conf_vars['mysql_prefix'])) { 
		echo sprintf(gettext('Table prefix: <strong>%1$s</strong>'),$_zp_conf_vars['mysql_prefix']); 
	}
	?>
	</li>
	<li><?php printf(gettext('Spam filter: <strong>%s</strong>'), getOption('spam_filter')) ?></li>
	<li><?php printf(gettext('Captcha generator: <strong>%s</strong>'), getOption('captcha')) ?></li>
	<?php
	if (!zp_has_filter('sendmail')) {
		?>
		<li style="color:RED"><?php echo gettext('There is no mail handler configured!'); ?></li>
		<?php
	}
	?>
	</ul>

	<?php
	$plugins = array_keys(getEnabledPlugins());
	$c = count($plugins);
	?>
	<h3><a href="javascript:toggle('plugins_hide');toggle('plugins_show');" ><?php printf(ngettext("%u active plugin:", "%u active plugins:", $c), $c); ?></a></h3>
	<div id="plugins_hide" style="display:none">
		<ul class="plugins">
		<?php
		if ($c > 0) {
			natcasesort($plugins);
			foreach ($plugins as $extension) {
				$pluginStream = file_get_contents(getPlugin($extension.'.php'));
				$str =  $pluginStream;
				$i = strpos($str, '$plugin_version');
				if ($i !== false) {
					$str = substr($str, $i);
					//$j = strpos($str, ";\n"); // This is wrong - PHP will not treat all newlines as \n.
					$j = strpos($str, ";"); // This is also wrong; it disallows semicolons in strings. We need a regexp.
					$str = substr($str, 0, $j+1);
					eval($str);
					$version = ' v'.$plugin_version;
				} else {
					$version = '';
				}
				echo "<li>".$extension.$version."</li>";
			}
		} else {
			echo '<li>'.gettext('<em>none</em>').'</li>';
		}
		?>
		</ul>
	</div>
	<div id="plugins_show">
		<br />
	</div>
	<?php
	$filters = $_zp_filters;
	ksort($filters);
	$c = count($filters);
	?>
	<h3><a href="javascript:toggle('filters_hide');toggle('filters_show');" ><?php printf(ngettext("%u active filter:","%u active filters:", $c), $c); ?></a></h3>
	<div id="filters_hide" style="display:none">
		<ul class="plugins">
		<?php
		if ($c > 0) {
			foreach ($filters as $filter=>$array_of_priority) {
				ksort($array_of_priority);
				?>
				<li>
					<em><?php echo $filter; ?></em>
					<ul class="filters">
					<?php
					foreach ($array_of_priority as $priority=>$array_of_filters) {
						foreach ($array_of_filters as $data) {
							?>
							<li><em><?php echo $priority; ?></em>: <?php echo $data['script'] ?> =&gt; <?php echo $data['function'] ?></li>
							<?php
						}
					}
					?>
				</ul>
			</li>
			<?php
			}
		} else {
			?>
			<li><?php echo gettext('<em>none</em>'); ?></li>
			<?php
		}
		?>
		</ul>
	</div>
	<div id="filters_show">
		<br />
	</div>
</div>

<?php zp_apply_filter('admin_overview_left', '');?>

<br clear="all" />
</div><!-- overview leftcolumn end -->

<div id="overview-rightcolumn">

<?php
$buttonlist = array();

if (is_connected()) {
	$buttonlist[] = array(
								'button_text'=>gettext("Check for zenphoto update"),
	//TODO:								'button_text'=>gettext("Check for update"), 
								'formname'=>'check_updates',
								'action'=>'admin.php?action=check_for_update',
								'icon'=>'images/accept.png', 
								'title'=>gettext("Queries the Zenphoto web site for the latest version and compares that with the one that is running."),
								'alt'=>'',
								'hidden'=> '<input type="hidden" name="action" value="check_for_update" />',
								'rights'=> OVERVIEW_RIGHTS
								);
}
$buttonlist[] = array(
							'button_text'=>gettext("Refresh the Database"),
							'formname'=>'prune_gallery',
							'action'=>'admin-refresh-metadata.php?prune',
							'icon'=>'images/refresh.png', 
							'title'=>gettext("Cleans the database and removes any orphan entries for comments, images, and albums."),
							'alt'=>'',
							'hidden'=> '<input type="hidden" name="prune" value="true" />',
							'rights'=> ADMIN_RIGHTS
							);
$buttonlist[] = array(
							'button_text'=>gettext("Purge Image cache"),
							'formname'=>'clear_cache',
							'action'=>'admin.php?action=clear_cache',
							'icon'=>'images/edit-delete.png', 
							'title'=>gettext("Clears the image cache. Images will be re-cached as they are viewed."),
							'alt'=>'',
							'hidden'=> '<input type="hidden" name="action" value="clear_cache" />',
							'rights'=> ADMIN_RIGHTS
							);
$buttonlist[] = array(
							'button_text'=>gettext("Purge RSS cache"),
							'formname'=>'clear_rss_cache',
							'action'=>'admin.php?action=clear_rss_cache',
							'icon'=>'images/edit-delete.png', 
							'title'=>gettext("Clears the RSS cache. RSS files will be re-cached as they are viewed."),
							'alt'=>'',
							'hidden'=> '<input type="hidden" name="action" value="clear_rss_cache" />',
							'rights'=> ADMIN_RIGHTS
							);
$buttonlist[] = array(
							'button_text'=>gettext("Pre-Cache Images"),
							'formname'=>'cache_images',
							'action'=>'admin-cache-images.php',
							'icon'=>'images/cache1.png', 
							'title'=>gettext("Finds newly uploaded images that have not been cached and creates the cached version. It also refreshes the numbers above. If you have a large number of images in your gallery you might consider using the pre-cache image link for each album to avoid swamping your browser."),
							'alt'=>'',
							'hidden'=> '',
							'rights'=> ADMIN_RIGHTS
							);
$buttonlist[] = array(
							'button_text'=>gettext("Refresh Metadata"),
							'formname'=>'refresh_metadata',
							'action'=>'admin-refresh-metadata.php',
							'icon'=>'images/refresh.png', 
							'title'=>gettext("Forces a refresh of the metadata for all images and albums."),
							'alt'=>'',
							'hidden'=> '',
							'rights'=> ADMIN_RIGHTS
							);
$buttonlist[] = array(
							'button_text'=>gettext("Reset all hitcounters"),
							'formname'=>'reset_hitcounters',
							'action'=>'admin.php?action=reset_hitcounters=true',
							'icon'=>'images/reset1.png', 
							'title'=>gettext("Sets all hitcounters to zero."),
							'alt'=>'Reset hitcounters',
							'hidden'=> '<input type="hidden" name="action" value="reset_hitcounters" />',
							'rights'=> ADMIN_RIGHTS
							);
?>
<div class="box" id="overview-utility">
<h2 class="h2_bordered"><?php echo gettext("Utility functions"); ?></h2>
<?php
$curdir = getcwd();
chdir(SERVERPATH . "/" . ZENFOLDER . '/'.UTILITIES_FOLDER.'/');
$filelist = safe_glob('*'.'php');
natcasesort($filelist);
foreach ($filelist as $utility) {
	$button_text = '';
	$button_hint = '';
	$button_icon = '';
	$button_rights = false;
	
	$utilityStream = file_get_contents($utility);
	eval(isolate('$button_text', $utilityStream));
	eval(isolate('$button_hint', $utilityStream));
	eval(isolate('$button_icon', $utilityStream));
	eval(isolate('$button_rights', $utilityStream));
	
	$buttonlist[] = array(
								'button_text'=>$button_text,
								'formname'=>$utility,
								'action'=>UTILITIES_FOLDER.'/'.$utility,
								'icon'=>$button_icon, 
								'title'=>$button_hint,
								'alt'=>'',
								'hidden'=> '',
								'rights'=> $button_rights  | ADMIN_RIGHTS
								);
								
}
$buttonlist = zp_apply_filter('admin_utilities_buttons', $buttonlist);
$buttonlist = sortMultiArray($buttonlist, 'button_text', false);
$count = 0;
foreach ($buttonlist as $key=>$button) {
	if (zp_loggedin($button['rights'])) {
		$count ++;
	} else {
		unset($buttonlist[$key]);
	}
}
$count = round($count/2);
?>
	<div id="overview-maint_l">
	<?php
	foreach ($buttonlist as $button) {
		$button_icon = $button['icon'];
		?>
		<form name="<?php echo $button['formname']; ?>"	action="<?php echo $button['action']; ?>">
			<?php echo $button['hidden']; ?>
			<div class="buttons">
			<button class="tooltip" type="submit"	title="<?php echo $button['title']; ?>">
			<?php
			if(!empty($button_icon)) {
				?>
				<img src="<?php echo $button_icon; ?>" alt="<?php echo $button['alt']; ?>" />
				<?php
			}
			echo htmlspecialchars($button['button_text']);
			?>
			</button>
			</div>
			<br clear="all" />
		</form>&nbsp;
		<?php
		$count --;
		if (!$count) { // half way through
			?>
			</div>
			<div id="overview-maint_r">
			<?php
		}
	}
	?>
	<br clear="all" />
	</div>
</div>
<div class="box" id="overview-comments">
<h2 class="h2_bordered"><?php echo gettext("10 Most Recent Comments"); ?></h2>
<ul>
<?php
$comments = fetchComments(10);
foreach ($comments as $comment) {
	$id = $comment['id'];
	$author = $comment['name'];
	$email = $comment['email'];
	$link = gettext('<strong>database error</strong> '); // incase of such
	
	// ZENPAGE: switch added for zenpage comment support
	switch ($comment['type']) {
		case "albums":
			$image = '';
			$title = '';
			$albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
 										" WHERE `id`=" . $comment['ownerid']);
			if ($albmdata) {
				$albumdata = $albmdata[0];
				$album = $albumdata['folder'];
				$albumtitle = get_language_string($albumdata['title']);
				$link = "<a href=\"".rewrite_path("/$album","/index.php?album=".urlencode($album))."\">".$albumtitle.$title."</a>";
				if (empty($albumtitle)) $albumtitle = $album;
			}
			break;
		case "news": // ZENPAGE: if plugin is installed
			if(getOption("zp_plugin_zenpage")) {
				$titlelink = '';
				$title = '';
				$newsdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_news') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($newsdata) {
					$newsdata = $newsdata[0];
					$titlelink = $newsdata['titlelink'];
					$title = get_language_string($newsdata['title']);
					$link = "<a href=\"".rewrite_path("/".ZENPAGE_NEWS."/".$titlelink,"/index.php?p=".ZENPAGE_NEWS."&amp;title=".urlencode($titlelink))."\">".$title."</a> ".gettext("[news]");
				}
			}
			break;
		case "pages": // ZENPAGE: if plugin is installed
			if(getOption("zp_plugin_zenpage")) {
				$image = '';
				$title = '';
				$pagesdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_pages') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($pagesdata) {
					$pagesdata = $pagesdata[0];
					$titlelink = $pagesdata['titlelink'];
					$title = get_language_string($pagesdata['title']);
					$link = "<a href=\"".rewrite_path("/".ZENPAGE_PAGES."/".$titlelink,"/index.php?p=".ZENPAGE_PAGES."&amp;title=".urlencode($titlelink))."\">".$title."</a> ".gettext("[page]");
				}
			}
			break;
		default: // all of the image types
			$imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
 										" WHERE `id`=" . $comment['ownerid']);
			if ($imagedata) {
				$imgdata = $imagedata[0];
				$image = $imgdata['filename'];
				if ($imgdata['title'] == "") $title = $image; else $title = get_language_string($imgdata['title']);
				$title = '/ ' . $title;
				$albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
 											" WHERE `id`=" . $imgdata['albumid']);
				if ($albmdata) {
					$albumdata = $albmdata[0];
					$album = $albumdata['folder'];
					$albumtitle = get_language_string($albumdata['title']);
					$link = "<a href=\"".rewrite_path("/$album/$image","/index.php?album=".urlencode($album).	"&amp;image=".urlencode($image))."\">".$albumtitle.$title."</a>";
					if (empty($albumtitle)) $albumtitle = $album;
				}
			}
			break;
	}
	$comment = truncate_string($comment['comment'], 123);
	echo "<li><div class=\"commentmeta\">".sprintf(gettext('<em>%1$s</em> commented on %2$s:'),$author,$link)."</div><div class=\"commentbody\">$comment</div></li>";
}
?>
</ul>
</div>

<?php zp_apply_filter('admin_overview_right', '');?>

<br clear="all" />
</div><!-- overview rightcolumn end -->
<br clear="all" />
</div><!-- content -->
<br clear="all" />
<?php
printAdminFooter();
} /* No admin-only content allowed after this bracket! */ ?></div>
<!-- main -->
</body>
<?php // to fool the validator
echo "\n</html>";
?>
