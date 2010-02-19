<?php
/**
 * provides the Themes tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (!($_zp_loggedin & (THEMES_RIGHTS | ADMIN_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

$gallery = new Gallery();
$_GET['page'] = 'themes';

/* handle posts */
$message = null; // will hold error/success message displayed in a fading box
if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'settheme':
			if (isset($_GET['theme'])) {
				$alb = sanitize_path($_GET['themealbum']);
				$newtheme = sanitize($_GET['theme']);
				if (empty($alb)) {
					$gallery->setCurrentTheme($newtheme);
				} else {
					$album = new Album($gallery, $alb);
					$oldtheme = $album->getAlbumTheme();
					$album->setAlbumTheme($newtheme);
					$album->save();
				}
				header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-themes.php?themealbum=".$_GET['themealbum']);
			}
			break;
			// Duplicate a theme
		case 'copytheme':
			if (isset($_GET['source']) && isset($_GET['target']) && isset($_GET['name'])) {
				$message = copyThemeDirectory(sanitize($_GET['source'],3), sanitize($_GET['target'],3), sanitize($_GET['name'],3));
			}
			break;
		case 'deletetheme':
			if (isset($_GET['theme'])) {
				if (deleteThemeDirectory(SERVERPATH . '/themes/'.internalToFilesystem(sanitize($_GET['theme'],3)))) {
					$message = gettext("Theme removed.");
				} else {
					$message = gettext('Error removing theme');
				}
				break;
			}
	}
}


printAdminHeader();

// Script for the "Duplicate theme" feature
?>

<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('li.zp_copy_theme p.buttons a').each(function(){
		var source = jQuery(this).attr('title');
		jQuery(this).click(function(){
			var targetname = prompt('<?php echo gettext('New theme name? (eg. "My Theme")'); ?>', '<?php echo gettext('My Theme'); ?>');
			if (targetname) {
				var targetdir = prompt('<?php echo gettext('New directory name? (eg. "my_theme")'); ?>', targetname.toLowerCase().replace(/ /g,'_').replace(/[^A-Za-z0-9_]/g,'') );
				if (targetdir) {
					launchScript('',['action=copytheme','source='+encodeURIComponent(source),'target='+encodeURIComponent(targetdir),'name='+encodeURIComponent(targetname)]);
					return false;
				}
			}
			return false;
		});

	});
});
</script>

<?php
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs('themes');
echo "\n" . '<div id="content">';

	$galleryTheme = $gallery->getCurrentTheme();
	$themelist = array();
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		$gallery_title = get_language_string(getOption('gallery_title'));
		if ($gallery_title != gettext("Gallery")) {
			$gallery_title .= ' ('.gettext("Gallery").')';
		}
		$themelist[$gallery_title] = '';
	}
	$albums = $gallery->getAlbums(0);
	foreach ($albums as $alb) {
		if (isMyAlbum($alb, THEMES_RIGHTS)) {
			$album = new Album($gallery, $alb);
			$key = $album->getTitle();
			if ($key != $alb) {
				$key .= " ($alb)";
			}
			$themelist[$key] = $alb;
		}
	}
	if (!empty($_REQUEST['themealbum'])) {
		$alb = sanitize_path($_REQUEST['themealbum']);
		$album = new Album($gallery, $alb);
		$albumtitle = $album->getTitle();
		$themename = $album->getAlbumTheme();
		$current_theme = $themename;
	} else {
		$current_theme = $galleryTheme;
		foreach ($themelist as $albumtitle=>$alb) break;
		if (empty($alb)) {
			$themename = $gallery->getCurrentTheme();
		} else {
			$alb = sanitize_path($alb);
			$album = new Album($gallery, $alb);
			$albumtitle = $album->getTitle();
			$themename = $album->getAlbumTheme();
		}
	}
	$themes = $gallery->getThemes();
	if (empty($themename)) {
		$current_theme = $galleryTheme;
		$theme = $themes[$galleryTheme];
		$themenamedisplay = '</em><small>'.gettext("no theme assigned, defaulting to Gallery theme").'</small><em>';
		$gallerydefault = true;
	} else {
		$theme = $themes[$themename];
		$themenamedisplay = $theme['name'];
		$gallerydefault = false;
	}

	if (count($themelist) > 1) {
		echo '<form action="#" method="post">';
		echo gettext("Show theme for: ");
		echo '<select id="themealbum" name="themealbum" onchange="this.form.submit()">';
		generateListFromArray(array(urlencode($alb)), $themelist, false, false);
		echo '</select>';
		echo '</form>';
	}
	if (count($themelist) == 0) {
		echo '<div class="errorbox" id="no_themes">';
		echo  "<h2>".gettext("There are no themes for which you have rights to administer.")."</h2>";
		echo '</div>';
	} else {

	echo "<h1>".sprintf(gettext('Current theme for <code><strong>%1$s</strong></code>: <em>%2$s</em>'),$albumtitle,$themenamedisplay);
	if (!empty($alb) && !empty($themename)) {
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".'<a class="reset" href="?action=settheme&themealbum='.urlencode($album->name).'&theme=" title="'.gettext('Clear theme assignment').$album->name.'">';
		echo '<img src="images/fail.png" style="border: 0px;" alt="'.gettext('Clear theme assignment').'" /></a>';
	}
	echo "</h1>\n";
?>

<?php if ($message) {
	echo '<div class="messagebox" id="fade-message">';
	echo  "<h2>$message</h2>";
	echo '</div>';
} ?>

<p>
	<?php echo gettext('Themes allow you to visually change the entire look and feel of your gallery. Theme files are located in your Zenphoto <code>/themes</code> folder.'); ?>
	<?php echo gettext('You can download more themes from the <a href="http://www.zenphoto.org/zp/theme/">zenphoto themes page</a>.'); ?>
	<?php echo gettext('Place the downloaded themes in the <code>/themes</code> folder and they will be available for your use.') ?>
</p>

<p>
	<?php echo gettext("You can edit files from custom themes. Official themes shipped with Zenphoto are not editable, since your changes would be lost on next update."); ?>
	<?php echo gettext("If you want to customize an official theme, please first <em>duplicate</em> it. This will place a copy in your <code>/themes</code> folder for you to edit."); ?>
</p>
<table class="bordered">
	<tr>
		<th colspan="2"><b><?php echo gettext('Installed themes'); ?></b></th>
		<th><b><?php echo gettext('Action'); ?></b></th>
	</tr>
	<?php
$themes = $gallery->getThemes();
$current_theme_style = "background-color: #ECF1F2;";
foreach($themes as $theme => $themeinfo) {
	$style = ($theme == $current_theme) ? " style=\"$current_theme_style\"" : "";
	$themedir = SERVERPATH . '/themes/'.internalToFilesystem($theme);
	$themeweb = WEBPATH . "/themes/$theme";
	?>
	<tr>
		<td style="margin: 0px; padding: 0px;"><?php
			if (file_exists("$themedir/theme.png")) $themeimage = "$themeweb/theme.png";
			else if (file_exists("$themedir/theme.gif")) $themeimage = "$themeweb/theme.gif";
			else if (file_exists("$themedir/theme.jpg")) $themeimage = "$themeweb/theme.jpg";
			else $themeimage = false;
			if ($themeimage) { ?> <img height="150" width="150"
				src="<?php echo $themeimage; ?>" alt="Theme Screenshot" /> <?php } ?>
		</td>
		<td <?php echo $style; ?>>
			<strong><?php echo $themeinfo['name']; ?></strong>
			<br />
			<?php echo $themeinfo['author']; ?>
			<br />
			Version <?php echo $themeinfo['version']; ?>, <?php echo $themeinfo['date']; ?>
			<br />
			<?php echo $themeinfo['desc']; ?>
			<br /><br />
			<a href="<?php echo WEBPATH.'/'.ZENFOLDER; ?>/admin-options.php?page=options&amp;tab=theme&amp;optiontheme=<?php echo $theme; ?>" ><?php echo sprintf(gettext('Set <em>%s</em> theme options'),$themeinfo['name']); ?></a>
		</td>
		<td width="20%" <?php echo $style; ?>>
			<ul class="theme_links">
			<?php
			if ($theme != $current_theme) {
				?>
				<li>
				<p class="buttons">
				<a href="?action=settheme&amp;themealbum=<?php echo urlencode($alb); ?>&amp;theme=<?php echo $theme; ?>" title="<?php echo gettext("Set this as your theme"); ?>">
				<img src="images/pass.png" alt="" /><?php echo gettext("Activate"); ?></a>
				</p>
				<br />
				</li>
			<?php
			} else {
				if ($gallerydefault) {
					?>
					<li>
					<p class="buttons">
					<a href="?action=settheme&amp;themealbum=<?php echo urlencode($alb); ?>&amp;theme=<?php echo $theme; ?>" title="<?php echo gettext("Assign this as your album theme"); ?>">
					<img src="images/pass.png" alt="" /><?php echo gettext("Assign"); ?></a>
					</p>
					<br />
					</li>
					<?php
				} else {
					echo "<li><strong>".gettext("Current Theme")."</strong></li>";
				}
			}

			if (themeIsEditable($theme, $themes)) {
				?>
				<li>
				<p class="buttons">
				<a href="admin-themes-editor.php?theme=<?php echo $theme; ?>" title="<?php echo gettext("Edit this theme"); ?>">
				<img src="images/pencil.png" alt="" /><?php echo gettext("Edit"); ?></a>
				</p><br />
				</li>
				<?php
				if ($theme != $current_theme) {
					?>
					<li>
					<p class="buttons">
					<a href="?action=deletetheme&amp;themealbum=<?php echo urlencode($alb); ?>&amp;theme=<?php echo $theme; ?>" title="<?php echo gettext("Delete this theme"); ?>">
					<img src="images/edit-delete.png" alt="" /><?php echo gettext("Delete"); ?></a>
					</p>
					</li>
					<?php
				}
			} else {

				?>
				<li class="zp_copy_theme">
				<p class="buttons">
				<a href="?" title="<?php echo $theme; ?>">
				<img src="images/page_white_copy.png" alt="" /><?php echo gettext("Duplicate"); ?></a>
				</p>
				</li>
				<?php
			}
			?>
			</ul>
		</td>
	</tr>

	<?php
	}
	?>
</table>


<?php
}

echo "\n" . '</div>';  //content
printAdminFooter();
echo "\n" . '</div>';  //main

echo "\n</body>";
echo "\n</html>";
?>



