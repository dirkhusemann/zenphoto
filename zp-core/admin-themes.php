<?php
/**
 * provides the Themes tab of admin
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once("admin-functions.php");

if (!($_zp_loggedin & (THEMES_RIGHTS | ADMIN_RIGHTS))) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}
$gallery = new Gallery();
$_GET['page'] = 'themes';

/* handle posts */
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'settheme') {
			if (isset($_GET['theme'])) {
				$alb = urldecode($_GET['themealbum']);
				$newtheme = strip($_GET['theme']);
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
		}
}

printAdminHeader();
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
			$themelist[$key] = urlencode($alb);
		}
	}
	if (!empty($_REQUEST['themealbum'])) {
		$alb = urldecode($_REQUEST['themealbum']);
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
			$alb = urldecode($alb);
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
		echo gettext("Show theme for"). ': ';
		echo '<select id="themealbum" name="themealbum" onchange="this.form.submit()">';
		generateListFromArray(array(urlencode($alb)), $themelist);
		echo '</select>';
		echo '</form>';
	}
	if (count($themelist) == 0) {
		echo '<div class="errorbox" id="no_themes">';
		echo  "<h2>".gettext("There are no themes for which you have rights to administer.")."</h2>";
		echo '</div>';
	} else {

	echo "<h1>".gettext("Current theme for")." <code><strong>$albumtitle</strong></code>: <em>".$themenamedisplay."</em>";
	if (!empty($alb) && !empty($themename)) {
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".'<a class="reset" href="?action=settheme&themealbum='.urlencode($album->name).'&theme=" title="'.gettext('Clear theme assignment').$album->name.'">';
		echo '<img src="images/fail.png" style="border: 0px;" alt="'.gettext('Clear theme assignment').'" /></a>';
	}
	echo "</h1>\n";
?>

<p><?php echo gettext("Themes allow you to visually change the entire look and feel of your gallery. Theme files are located in your"); ?>
	Zenphoto <code>/themes</code>
	<?php echo gettext("folder. You can download more themes from the"); ?>
	<a href="http://www.zenphoto.org/zp/theme/"><?php echo gettext("zenphoto themes page"); ?></a>.
	<?php echo gettext("Place the downloaded themes in the"); ?>	<code>/themes</code> <?php echo gettext("folder and they will be available for your use.") ?>

	</p>
<table class="bordered">
	<?php
$themes = $gallery->getThemes();
$current_theme_style = "background-color: #ECF1F2;";
foreach($themes as $theme => $themeinfo):
	$style = ($theme == $current_theme) ? " style=\"$current_theme_style\"" : "";
	$themedir = SERVERPATH . "/themes/$theme";
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
		<td <?php echo $style; ?>><strong><?php echo $themeinfo['name']; ?></strong><br />
		<?php echo $themeinfo['author']; ?><br />
		Version <?php echo $themeinfo['version']; ?>, <?php echo $themeinfo['date']; ?><br />
		<?php echo $themeinfo['desc']; ?></td>
		<td width="100" <?php echo $style; ?>>
		<?php
		if ($theme != $current_theme) {
			echo '<a href="?action=settheme&themealbum='.urlencode($alb).'&theme='.$theme.'" title=';
			 echo gettext("Set this as your theme").'>'.gettext("Use this Theme");
			echo '</a>';
		} else {
			if ($gallerydefault) {
				echo '<a href="?action=settheme&themealbum='.urlencode($alb).'&theme='.$theme.'" title=';
			  echo gettext("Assign this as your album theme").'>'.gettext("Assign Theme");
				echo '</a>';
			} else {
				echo "<strong>".gettext("Current Theme")."</strong>";
			}
		} ?>
		</td>
	</tr>

	<?php endforeach; ?>
</table>


<?php
}

echo "\n" . '</div>';  //content
echo "\n" . '</div>';  //main

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>



