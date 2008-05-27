<?php
require_once("classes.php");
require_once("functions.php");
require_once("lib-seo.php"); // keep the function separate for easy modification by site admins

$_zp_admin_album_list = null;

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
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminFooter() {
	echo "<div id=\"footer\">";
	echo "\n  <a href=\"http://www.zenphoto.org\" title=\"A simpler web photo album\">zen<strong>photo</strong></a>";
	echo " version ". ZENPHOTO_VERSION.' ['.ZENPHOTO_RELEASE.']';
	echo " | <a href=\"http://www.zenphoto.org/support/\" title=\"Forum\">Forum</a> | <a href=\"http://www.zenphoto.org/trac/\" title=\"Trac\">Trac</a> | <a href=\"changelog.html\" title=\"View Changelog\">Changelog</a>\n</div>";
}

/**
 * Print the header for all admin pages. Starts at <DOCTYPE> but does not include the </head> tag,
 * in case there is a need to add something further.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminHeader() {

	header ('Content-Type: text/html; charset=' . getOption('charset'));

	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	echo "\n<html xmlns=\"http://www.w3.org/1999/xhtml\">";
	echo "\n<head>";
	echo "\n  <title>zenphoto administration</title>";
	echo "\n  <link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\" />";
	echo "\n  <script type=\"text/javascript\" src=\"js/prototype.js\"></script>";
	echo "\n  <script type=\"text/javascript\" src=\"js/admin.js\"></script>";
	echo "\n  <script src=\"js/scriptaculous/scriptaculous.js\" type=\"text/javascript\"></script>";
	echo "\n  <script src=\"js/jquery.js\" type=\"text/javascript\"></script>";
	echo "\n  <script src=\"js/jquery.dimensions.js\" type=\"text/javascript\"></script>";
	echo "\n  <script src=\"js/jquery.tooltip.js\" type=\"text/javascript\"></script>";
	echo "\n  <script src=\"js/jquery.tabs.js\" type=\"text/javascript\"></script>";
	echo "\n  <script type=\"text/javascript\">";
	echo "\n  \tjQuery(function( $ ){";
	echo "\n  \t\t $(\"#fade-message\").fadeTo(5000, 1).fadeOut(1000);";
	echo "\n  \t\t $('.tooltip').tooltip();";
	echo "\n  \t\t $('#mainmenu > ul').tabs();";
	echo "\n  \t});";
	echo "\n  </script>";
	?>
<script type="text/javascript">
		/*-----------------------------------------------------------+
		 | addLoadEvent: Add event handler to body when window loads |
		 +-----------------------------------------------------------*/
		function addLoadEvent(func) {
			var oldonload = window.onload;
			
			if (typeof window.onload != "function") {
				window.onload = func;
			} else {
				window.onload = function () {
					oldonload();
					func();
				}
			}
		}
		
		/*------------------------------------+
		 | Functions to run when window loads |
		 +------------------------------------*/
		addLoadEvent(function () {
			initChecklist();
		});
		
		/*----------------------------------------------------------+
		 | initChecklist: Add :hover functionality on labels for IE |
		 +----------------------------------------------------------*/
		function initChecklist() {
			if (document.all && document.getElementById) {
				// Get all unordered lists
				var lists = document.getElementsByTagName("ul");
				
				for (i = 0; i < lists.length; i++) {
					var theList = lists[i];
					
					// Only work with those having the class "checklist"
					if (theList.className.indexOf("checklist") > -1) {
						var labels = theList.getElementsByTagName("label");
						
						// Assign event handlers to labels within
						for (var j = 0; j < labels.length; j++) {
							var theLabel = labels[j];
							theLabel.onmouseover = function() { this.className += " hover"; };
							theLabel.onmouseout = function() { this.className = this.className.replace(" hover", ""); };
						}
					}
				}
			}
		}
	</script>
<?php
}

/**
 * Print a link to a particular admin page.
 *
 * @param $action The action page that to which this link will point. E.g. edit, comment, etc.
 * @param $text   Text for the hyperlink.
 * @param $title  Optional title attribute for the hyperlink. Default is NULL.
 * @param $class  Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id     Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminLinks($action, $text, $title=NULL, $class=NULL, $id=NULL) {

	adminPrintLink("admin.php?page=". $action, $text, $title, $class, $id);
}

/**
 * Print a link to the album sorting page. We will remain within the Edit tab of the admin section.
 *
 * @param $album The album name to sort.
 * @param $text  Text for the hyperlink.
 * @param $title Optional title attribute for the hyperlink. Default is NULL.
 * @param $class Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id    Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printSortLink($album, $text, $title=NULL, $class=NULL, $id=NULL) {
	adminPrintLink(WEBPATH . "/" . ZENFOLDER . "/albumsort.php?page=edit&album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
}

/**
 * Print a link that will take the user to the actual album. E.g. useful for View Album.
 *
 * @param $album The album to view.
 * @param $text  Text for the hyperlink.
 * @param $title Optional title attribute for the hyperlink. Default is NULL.
 * @param $class Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id    Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printViewLink($album, $text, $title=NULL, $class=NULL, $id=NULL) {
	adminPrintLink(WEBPATH . "/index.php?album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
}

/**
 * Print the thumbnail for a particular Image.
 *
 * @param $image The Image object whose thumbnail we want to display.
 * @param $class Optional class attribute for the hyperlink.  Default is NULL.
 * @param $id    Optional id attribute for the hyperlink.  Default is NULL.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */

function adminPrintImageThumb($image, $class=NULL, $id=NULL) {
	echo "\n  <img class=\"imagethumb\" id=\"id_". $image->id ."\" src=\"" . $image->getThumb() . "\" alt=\"". $image->getTitle() . "\"" .
	((getOption('thumb_crop')) ? " width=\"".getOption('thumb_crop_width')."\" height=\"".getOption('thumb_crop_height')."\"" : "") .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . " />";
}

// TODO: This is a copy of the function in template-functions. Refactor at some point
function adminPrintLink($url, $text, $title=NULL, $class=NULL, $id=NULL) {
	echo "<a href=\"" . $url . "\"" .
	(($title) ? " title=\"$title\"" : "") .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . ">" .
	$text . "</a>";
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
	global $_zp_login_error, $_zp_current_admin;
	if (is_null($redirect)) { $redirect = "/" . ZENFOLDER . "/admin.php"; }
	$requestor = sanitize($_POST['user']);
	if (empty($requestor)) { $requestor = sanitize($_GET['ref']); }

	if ($logo) echo "<p><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";

	echo "\n  <div id=\"loginform\">";
	if ($_zp_login_error == 1) {
		echo "<div class=\"errorbox\" id=\"message\"><h2>".gettext("There was an error logging in.</h2> Check your username and password and try again.")."</div>";
	} else if ($_zp_login_error == 2){
		echo '<div class="messagebox" id="fade-message">'; 
		echo  "<h2>".gettext("A reset request has been sent.")."</h2>"; 
		echo '</div>'; 
	}
	echo "\n  <form name=\"login\" action=\"#\" method=\"POST\">";
	echo "\n    <input type=\"hidden\" name=\"login\" value=\"1\" />";
	echo "\n    <input type=\"hidden\" name=\"redirect\" value=\"$redirect\" />";

	echo "\n    <table>";
	echo "\n      <tr><td>".gettext("Login")."</td><td><input class=\"textfield\" name=\"user\" type=\"text\" size=\"20\" value=\"$requestor\" /></td></tr>";
	echo "\n      <tr><td>".gettext("Password")."</td><td><input class=\"textfield\" name=\"pass\" type=\"password\" size=\"20\" /></td></tr>";

	if (count(getAdminEmail()) > 0) {
		$captchaCode = generateCaptcha($img);
		echo "\n      <tr><td></td><td>";
		echo "\n      ".gettext("Enter").' ';
		echo "<input type=\"hidden\" name=\"code_h\" value=\"" . $captchaCode . "\"/>" .
 								"<label for=\"code\"><img src=\"" . $img . "\" alt=\"Code\" align=\"absbottom\"/></label> ";
		echo gettext(" to request a reset.");
		//		echo "      <input type=\"text\" id=\"code\" name=\"code\" size=\"4\" class=\"inputbox\" />";
		echo "      </td></tr>";
	}
	echo "\n      <tr><td colspan=\"2\"><input class=\"button\" type=\"submit\" value=\"".gettext("Log in")."\" /></td></tr>";
	echo "\n    </table>";
	echo "\n  </form>";
	echo "\n  </div>";
	echo "\n</body>";
	echo "\n</html>";
}


/**
 * Print the html required to display the ZP logo and links in the top section of the admin page.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printLogoAndLinks() {
	global $_zp_current_admin;
	echo "\n\n<a href=\"".WEBPATH."/" . ZENFOLDER . "/admin.php\" id=\"logo\"><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></a>";
	echo "\n<div id=\"links\">";
	echo "\n  ";
	if (!is_null($_zp_current_admin)) {
		echo gettext("Logged in as")." ".$_zp_current_admin['user']." &nbsp; | &nbsp <a href=\"?logout\">".gettext("Log Out")."</a> &nbsp; | &nbsp; ";
	}
	echo "<a href=\"../\">".gettext("View Gallery");
	$t = getOption('gallery_title');
	if (!empty($t))	echo ': ' . $t;
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
function printTabs() {
	global $_zp_loggedin;
	// Which page should we highlight? Default is home.
	if (isset($_GET['page'])) {
		$page= $_GET['page'];
	} else {
		$page= "home";
	}

	echo "\n  <ul id=\"nav\">";
	if (($_zp_loggedin & MAIN_RIGHTS)) {
		echo "\n    <li". ($page == "home" ? " class=\"current\""     : "") .
 				"> <a href=\"admin.php?page=home\">".gettext("overview")."</a></li>";
	}
	if (($_zp_loggedin & COMMENT_RIGHTS)) {
		echo "\n    <li". ($page == "comments" ? " class=\"current\"" : "") .
 				"> <a href=\"admin.php?page=comments\">".gettext("comments")."</a></li>";
	}
	if (($_zp_loggedin & UPLOAD_RIGHTS)) {
		echo "\n    <li". ($page == "upload" ? " class=\"current\""   : "") .
 				"> <a href=\"admin.php?page=upload\">".gettext("upload")."</a></li>";
	}
	if (($_zp_loggedin & EDIT_RIGHTS)) {
		echo "\n    <li". ($page == "edit" ? " class=\"current\""     : "") .
 				"> <a href=\"admin.php?page=edit\">".gettext("edit")."</a></li>";
	}
	if (($_zp_loggedin & ADMIN_RIGHTS)) {
		echo "\n    <li". ($page == "tags" ? " class=\"current\""     : "") .
				"><a href=\"admin-tags.php?page=tags\">".gettext('tags')."</a></li>";
	}	
	echo "\n    <li". ($page == "options" ? " class=\"current\""  : "") .
 			"> <a href=\"admin.php?page=options\">".gettext("options")."</a></li>";
	if (($_zp_loggedin & THEMES_RIGHTS)) {
		echo "\n    <li". ($page == "themes" ? " class=\"current\""  : "") .
 				"> <a href=\"admin.php?page=themes\">".gettext("themes")."</a></li>";
	}
	if (($_zp_loggedin & ADMIN_RIGHTS)) {
		echo "\n    <li". ($page == "plugins" ? " class=\"current\""  : "") .
 				"> <a href=\"admin.php?page=plugins\">".gettext("plugins")."</a></li>";
	}
	if (($_zp_loggedin & ADMIN_RIGHTS) && getoption('zp_plugin_zenpage')) {
		echo "\n    <li". ($page == "zenpage" ? " class=\"current\""     : "") .
 				"><a href=\"plugins/zenpage/page-admin.php?page=zenpage\">zenPage</a></li>";
	}	
	echo "\n  </ul>";

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
			$msg = gettext("Image ");
		} else {
			$msg = gettext("Album ");
			$ntdel = $ntdel - 2;
		}
		if ($ntdel == 2) {
			$msg = $msg . gettext("failed to delete.");
			$class = 'errorbox';
		} else {
			$msg = $msg . gettext("deleted successfully.");
			$class = 'messagebox';
		}
		echo '<div class="' . $class . '" id="fade-message">';
		echo  "<h2>" . $msg . "</h2>";
		echo '</div>';
	}
}

function setThemeOption($table, $key, $value) {
	$exists = query_single_row("SELECT `name`, `value` FROM ".prefix($table)." WHERE `name`='".escape($key)."'", true);
	if ($exists) {
		$sql = "UPDATE " . prefix($table) . " SET `value`='" . escape($value) . "' WHERE `name`='" . escape($key) ."'";
	} else {
		$sql = "INSERT INTO " . prefix($table) . " (name, value) VALUES ('" . escape($key) . "','" . escape($value) . "')";
	}
	$result = query($sql);
}

function setBoolThemeOption($table, $key, $bool) {
	if ($bool) {
		$value = 1;
	} else {
		$value = 0;
	}
	setThemeOption($table, $key, $value);
}

function getThemeOption($alb, $option) {
	if (!empty($alb)) $alb = $alb.'_';
	$sql = "SELECT `value` FROM " . prefix($alb.'options') . " Where `name`='" . escape($option) . "';";
	$db = query_single_row($sql);
	if (!$db && !empty($alb)) {
		$sql = "SELECT `value` FROM " . prefix('options') . " Where `name`='" . escape($option) . "';";
		$db = query_single_row($sql);
	}
	if ($db) {
		$v = $db['value'];
	} else {
		$v = 0;
	}
	return $v;
}

function customOptions($optionHandler, $indent="", $alb="") {
	if (!empty($alb)) $alb = $alb.'_';
	$supportedOptions = $optionHandler->getOptionsSupported();
	if (count($supportedOptions) > 0) {
		$options = array_keys($supportedOptions);
		natcasesort($options);
		foreach($options as $option) {
			$row = $supportedOptions[$option];
			$type = $row['type'];
			$desc = $row['desc'];
			if (isset($row['key'])) {
				$key = $row['key'];
			} else { // backward compatibility
				$key = $option;  
				$option = str_replace('_', ' ', $option);
			}
			$sql = "SELECT `value` FROM " . prefix($alb.'options') . " Where `name`='" . escape($key) . "';";
			$db = query_single_row($sql);
			if (!$db && !empty($alb)) {
				$sql = "SELECT `value` FROM " . prefix('options') . " Where `name`='" . escape($key) . "';";
				$db = query_single_row($sql);
			}
			if ($db) {
				$v = $db['value'];
			} else {
				$v = 0;
			}
			
			echo "\n<tr>\n";
			echo '<td width="175">' . $indent . $option . ":</td>\n";

			switch ($type) {
				case 0:  // text box
					echo '<td width="200"><input type="text" size="40" name="' . $key . '" value="' . $v . '"></td>' . "\n";
					break;
				case 1:  // check box
					echo '<input type="hidden" name="chkbox-' . $key . '" value=0 />' . "\n";
					echo '<td width="200"><input type="checkbox" name="' . $key . '" value="1"';
					echo checked('1', $v);
					echo " /></td>\n";
					break;
				case 2:  // custom handling
					echo '<td width="200">' . "\n";
					$optionHandler->handleOption($key, $v);
					echo "</td>\n";
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
	$str = str_replace('%2E', '.', $str);
	return urldecode($str);
}

/**
 * Creates the body of an unordered list with checkbox label/input fields (scrollable sortables)
 *
 * @param array $currentValue list of items to be flagged as checked
 * @param array $list the elements of the select list
 */
function generateUnorderedListFromArray($currentValue, $list, $prefix) {
	$localize = !is_numeric(array_shift(array_keys($list)));
	if ($localize) {
		ksort($list);
	} else {
		sort($list);
	}
	$cv = array_flip($currentValue);
	foreach($list as $key=>$item) {
		$listitem = postIndexEncode($prefix.$item);
		echo '<li><label for="'.$listitem.'"><input id="'.$listitem.'" name="'.$listitem.'" type="checkbox"';
		if (isset($cv[$item])) {
			echo ' checked="checked"';
		}
		if ($localize) $display = $key; else $display = $item;
		echo ' />' . $display . "</label></li>"."\n";
	}
}


function tagSelector($that, $postit) {
	$them = array_unique(getAllTags());
	if (is_null($that)) {
		$tags = array();
	} else {
		$tags = $that->getTags();
		foreach ($tags as $key=>$tag) {
			$tags[$key] = strtolower(trim($tag));
		}
	}
	$them = array_diff($them, $tags);
	echo '<ul class="tagchecklist">'."\n";
	generateUnorderedListFromArray($tags, $tags, $postit);
	if (!is_null($that)) {
		for ($i=0; $i<4; $i++) {
			echo '<li>'.gettext("new tag").' <input type="text" size="15" name="'.$postit.'new_tag_value_'.$i.'" value="" /></li>'."\n";
		}
	}
	generateUnorderedListFromArray(array(), $them, $postit);
	echo '</ul>';
}

/**
 * emits the html for editing album information
 * called in edit album and mass edit
 *@param string param1 the index of the entry in mass edit or '0' if single album
 *@param object param2 the album object
 *@since 1.1.3
 */
function printAlbumEditForm($index, $album) {
	global $sortby, $images, $gallery, $_zp_loggedin;
	if ($index == 0) {
		if (isset($saved)) {
			$album->setSubalbumSortType('Manual');
		}
		$prefix = '';
	} else {
		$prefix = "$index-";
		echo "<p><em><strong>" . $album->name . "</strong></em></p>";
	}
	if (isset($_GET['counters_reset'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Hitcounters have been reset")."</h2>";
		echo '</div>';
	}

	echo "\n<input type=\"hidden\" name=\"" . $prefix . "folder\" value=\"" . $album->name . "\" />";
	echo "\n<div class=\"box\" style=\"padding: 15px;\">";
	echo "\n<table>";
	echo "\n<td width = \"60%\">\n<table>\n<tr>";
	echo "\n<tr>";
	echo "<td align=\"right\" valign=\"top\" width=\"150\">Album Title: </td> <td><input type=\"text\" name=\"".$prefix."albumtitle\" value=\"" .
	$album->getTitle() . '" />';
	$id = $album->getAlbumId();
	$result = query_single_row("SELECT `hitcounter` FROM " . prefix('albums') . " WHERE id = $id");
	$hc = $result['hitcounter'];
	if (empty($hc)) { $hc = '0'; }
	echo" ".gettext("Hit counter: "). $hc . " <input type=\"checkbox\" name=\"".gettext("reset_hitcounter")."\"> Reset</td>";
	echo '</tr>';
	echo "\n<tr><td align=\"right\" valign=\"top\">".gettext("Album Description:")." </td> <td><textarea name=\"".$prefix."albumdesc\" cols=\"60\" rows=\"6\" style=\"width: 360px\">" .
	$album->getDesc() . "</textarea></td></tr>";
	echo "\n<tr>";
	echo "\n<td align=\"right\">".gettext("Album password:")." <br/>repeat: </td>";
	echo "\n<td>";
	$x = $album->getPassword();

	if (!empty($x)) {
		$x = '          ';
	}

	echo "\n<input type=\"password\" size=\"40\" name=\"".$prefix."albumpass\"";
	echo "\nvalue=\"" . $x . '" /><br/>';
	echo "\n<input type=\"password\" size=\"40\" name=\"".$prefix."albumpass_2\"";
	echo "\nvalue=\"" . $x . '" />';
	echo "\n</td>";
	echo "\n</tr>";
	echo "\n<tr><td align=\"right\" valign=\"top\">".gettext("Password hint:")." </td> <td><input type=\"text\" name=\"".$prefix."albumpass_hint\" style=\"width: 360px\" value=\"" .
	$album->getPasswordHint() . '" /></td></tr>';

	$d = $album->getDateTime();
	if ($d == "0000-00-00 00:00:00") {
		$d = "";
	}

	echo "\n<tr><td align=\"right\" valign=\"top\">".gettext("Date:")." </td> <td width = \"400\"><input type=\"text\" name=\"".$prefix."albumdate\" value=\"" . $d . '" /></td></tr>';
	echo "\n<tr><td align=\"right\" valign=\"top\">".gettext("Location:")." </td> <td><input type=\"text\" name=\"".$prefix."albumplace\" style=\"width: 360px\" value=\"" .
	$album->getPlace() . "\" /></td></tr>";
	echo "\n<tr><td align=\"right\" valign=\"top\">".gettext("Custom data:").
	"</td><td><textarea cols=\"60\" rows=\"3\" style=\"width: 360px\" name=\"".
	$prefix."album_custom_data\" >" .
	trim($album->getCustomData()) . "</textarea></td></tr>";
	$sort = $sortby;
	if (!$album->isDynamic()) {
		$sort[gettext('Manual')] = 'Manual';
	}
	echo "\n<tr>";
	echo "\n<td align=\"right\" valign=\"top\">".gettext("Sort subalbums by:")." </td>";
	echo "\n<td>";

	// script to test for what is selected 
	$javaprefix = 'js_'.preg_replace("/[^a-z0-9_]/","",strtolower($prefix));
	echo '<script type="text/javascript">'."\n";
	echo '  function '.$javaprefix.'album_direction(obj) {'."\n";
	echo "		if((obj.options[obj.selectedIndex].value == 'Manual') || (obj.options[obj.selectedIndex].value == '')) {\n";
	echo "			document.getElementById('$javaprefix"."album_direction_div').style.display = 'none';\n";
	echo '			}'."\n";
	echo '		else {'."\n";
	echo "			document.getElementById('$javaprefix"."album_direction_div').style.display = 'block';\n";
	echo ' 		}'."\n";
	echo '	}'."\n";
	echo '</script>'."\n";
	
	echo "\n<table>\n<tr>\n<td>";
	echo "\n<select id=\"sortselect\" name=\"".$prefix."subalbumsortby\" onchange=\"".$javaprefix."album_direction(this)\">";
	if (is_null($album->getParent())) {
		$globalsort = gettext("gallery album sort order");
	} else {
		$globalsort = gettext("parent album subalbum sort order");
	}
	echo "\n<option value =''>$globalsort</option>"; 
	generateListFromArray(array($type = $album->get('subalbum_sort_type')), $sort);
	echo "\n</select>";
	echo "\n</td>\n<td>";
	if (($type == 'Manual') || ($type == '')) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	echo "\n<div id=\"".$javaprefix."album_direction_div\" style=\"display:".$dsp."\">";
	echo "&nbsp;".gettext("Descending")." <input type=\"checkbox\" name=\"".$prefix."album_sortdirection\" value=\"1\"";

	if ($album->getSortDirection('album')) {
		echo "CHECKED";
	}
	echo ">";
	echo '</div>';
	echo "\n</td>\n</tr>\n</table>";
	echo "\n</td>";
	echo "\n</tr>";

	echo "\n<tr>";
	echo "\n<td align=\"right\" valign=\"top\">".gettext("Sort images by:")." </td>";
	echo "\n<td>";
	
	// script to test for what is selected 
	$javaprefix = 'js_'.preg_replace("/[^a-z0-9_]/","",strtolower($prefix));
	echo '<script type="text/javascript">'."\n";
	echo '  function '.$javaprefix.'image_direction(obj) {'."\n";
	echo "		if((obj.options[obj.selectedIndex].value == 'Manual') || (obj.options[obj.selectedIndex].value == '')) {\n";
	echo "			document.getElementById('$javaprefix"."image_direction_div').style.display = 'none';\n";
	echo '			}'."\n";
	echo '		else {'."\n";
	echo "			document.getElementById('$javaprefix"."image_direction_div').style.display = 'block';\n";
	echo ' 		}'."\n";
	echo '	}'."\n";
	echo '</script>'."\n";
	
	echo "\n<table>\n<tr>\n<td>";
	echo "\n<select id=\"sortselect\" name=\"".$prefix."sortby\" onchange=\"".$javaprefix."image_direction(this)\">";
	if (is_null($album->getParent())) {
		$globalsort = gettext("gallery default image sort order");
	} else {
		$globalsort = gettext("parent album image sort order");
	}
	echo "\n<option value =''>$globalsort</option>"; 
	generateListFromArray(array($type = $album->get('sort_type')), $sort);
	echo "\n</select>";
	echo "\n</td>\n<td>";
	if (($type == 'Manual') || ($type == '')) {
		$dsp = 'none';
	} else {
		$dsp = 'block';
	}
	echo "\n<div id=\"".$javaprefix."image_direction_div\" style=\"display:".$dsp."\">";
	echo "&nbsp;".gettext("Descending")." <input type=\"checkbox\" name=\"".$prefix."image_sortdirection\" value=\"1\"";
	if ($album->getSortDirection('image')) {
		echo "CHECKED";
	}
	echo ">";
	echo '</div>';
	echo "\n</td>\n</tr>\n</table>";
	echo "\n</td>";
	echo "\n</tr>";

	echo "\n<tr>";
	echo "\n<td align=\"right\" valign=\"top\"></td><td><input type=\"checkbox\" name=\"" .
	$prefix."allowcomments\" value=\"1\"";
	if ($album->getCommentsAllowed()) {
		echo "CHECKED";
	}
	echo "> ".gettext("Allow Comments")." ";
	echo "<input type=\"checkbox\" name=\"" .
	$prefix."Published\" value=\"1\"";
	if ($album->getShow()) {
		echo "CHECKED";
	}
	echo "> ".gettext("Published")." ";
	echo "</td>\n</tr>";
	if (is_null($album->getParent())) {
		echo "\n<tr>";
		echo "\n<td align=\"right\" valign=\"top\">".gettext("Album theme:")." </td> ";
		echo "\n<td>";
		echo "\n<select id=\"album_theme\" class=\"album_theme\" name=\"".$prefix."album_theme\" ";
		if (!($_zp_loggedin & THEMES_RIGHTS)) echo "DISABLED ";
		echo ">";
		$themes = $gallery->getThemes();
		$oldtheme = $album->getAlbumTheme();
		if (empty($oldtheme)) {
			echo "<option value = \"\" selected=\"SELECTED\" />";
		} else {
			echo "<option value = \"\" />";
		}
		echo "</option>";

		foreach ($themes as $theme=>$themeinfo) {
			echo "<option value = \"$theme\"";
			if ($oldtheme == $theme) {
				echo "selected = \"SELECTED\"";
			}
			echo "	/>";
			echo $themeinfo['name'];
			echo "</option>";
		}
		echo "\n</select>";
		echo "\n</td>";
		echo "\n</tr>";
	}
	
	echo "\n</table>\n</td>";
	echo "\n<td valign=\"top\">";
	echo gettext("Tags:");
	tagSelector($album, 'tags_'.$prefix);
	echo "\n</td>\n</tr>";
	
	echo "\n</table>";

	echo  "\n<table>";
	if ($album->isDynamic()) {
		echo "\n<tr>";
		echo "\n<td> </td>";
		echo "\n<td align=\"right\" valign=\"top\" width=\"150\">".gettext("Dynamic album search:")."</td>";
		echo "\n<td>";
		echo "\n<table class=\"noinput\">";
		echo "\n<tr><td >" .	urldecode($album->getSearchParams()) . "</td></tr>";
		echo "\n</table>";
		echo "\n</td>";
		echo "\n</tr>";
	} 
	echo "\n<tr>";
	echo "\n<td> </td>";
	echo "\n<td align=\"right\" valign=\"top\" width=\"150\">".gettext("Thumbnail:")." </td> ";
	echo "\n<td>";
	echo "\n<script type=\"text/javascript\">updateThumbPreview(document.getElementById('thumbselect'));</script>";
	echo "\n<select id=\"thumbselect\" class=\"thumbselect\" name=\"".$prefix."thumb\" onChange=\"updateThumbPreview(this)\">";
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
		foreach ($imagelist as $imagepath) {
			$list = explode('/', $imagepath);
			$filename = $list[count($list)-1];
			unset($list[count($list)-1]);
			$folder = implode('/', $list);
			$albumx = new Album($gallery, $folder);
			$image = new Image($albumx, $filename);
			$selected = ($imagepath == $thumb);
			echo "\n<option class=\"thumboption\" style=\"background-image: url(" . $image->getThumb() .
						"); background-repeat: no-repeat;\" value=\"".$imagepath."\"";
			if ($selected) {
				echo " selected=\"selected\"";
			}
			echo ">" . $image->get('title');
			echo  " ($imagepath)";
			echo "</option>";
		}
	} else {
		$thumb = $album->get('thumb');
		echo "\n<option class=\"thumboption\" value=\"\" style=\"background-color:#B1F7B6\"";
		if (empty($thumb)) {
			echo " selected=\"selected\"";
		}
		echo '> '.gettext('randomly selected');
		echo '</option>';
		if (count($album->getSubalbums()) > 0) {
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
					$image = new Image($albumx, $filename);
					if (is_valid_image($filename)) {
						$selected = ($imagepath == $thumb);
						echo "\n<option class=\"thumboption\" style=\"background-image: url(" . $image->getThumb() .
									"); background-repeat: no-repeat;\" value=\"".$imagepath."\"";
						if ($selected) {
							echo " selected=\"selected\"";
						}
						echo ">" . $image->get('title');
						echo  " ($imagepath)";
						echo "</option>";
					}
				}
			}
		} else {
			foreach ($images as $filename) {
				$image = new Image($album, $filename);
				$selected = ($filename == $album->get('thumb'));
				if (is_valid_image($filename)) {
					echo "\n<option class=\"thumboption\" style=\"background-image: url(" . $image->getThumb() .
						"); background-repeat: no-repeat;\" value=\"" . $filename . "\"";
					if ($selected) {
						echo " selected=\"selected\"";
					}
					echo ">" . $image->get('title');
					if ($filename != $image->get('title')) {
						echo  " ($filename)";
					}
					echo "</option>";
				}
			}
		}
	}
	echo "\n</select>";
	echo "\n</td>";
	echo "\n</tr>";
	echo "\n</table>";
	
	echo "\n<input type=\"submit\" value=\"".gettext("save")."\" />";

	echo "\n</div>";

}
/**
 * puts out the maintenance buttons for an album
 *
 * @param object $album is the album being emitted
 */
function printAlbumButtons($album) {
	if ($album->getNumImages() > 0) {
		echo "\n<table class=\"buttons\"><tr>";
		echo "\n<td valign=\"top\" width=30% style=\"padding: 0px 30px 0px 30px;\">";
		echo "<form name=\"cache_images\" action=\"cache-images.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"album\" value=" . urlencode($album->name) . ">";
		echo "<input type=\"hidden\" name=\"return\" value=" . urlencode($album->name) . ">";
		echo "<button type=\"submit\" class=\"tooltip\" id='edit_cache' title=\"".gettext("Cache newly uploaded images.")."\"><img src=\"images/cache.png\" style=\"border: 0px;\" />";
		echo " ".gettext("Pre-Cache Images")."</Button>";
		echo "<input type=\"checkbox\" name=\"clear\" checked=\"checked\" /> ".gettext("Clear");
		echo "</form>\n</td>";

		echo "\n<td valign=\"top\" width = 30% style=\"padding: 0px 30px 0px 30px;\">";
		echo "<form name=\"refresh_metadata\" action=\"refresh-metadata.php\"?album=" . urlencode($album->name) . "\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"album\" value=" . urlencode($album->name) . ">";
		echo "<input type=\"hidden\" name=\"return\" value=" . urlencode($album->name) . ">";
		echo "<button type=\"submit\" class=\"tooltip\" id='edit_refresh' title=\"".gettext("Forces a refresh of the EXIF and IPTC data for all images in the album.")."\"><img src=\"images/warn.png\" style=\"border: 0px;\" /> ".gettext("Refresh Metadata")."</button>";
		echo "</form>";
		echo "\n</td>";
			
		echo "\n<td valign=\"top\" width = 30% style=\"padding: 0px 30px 0px 30px;\">";
		echo "</form>";
		echo "<form name=\"reset_hitcounters\" action=\"?action=reset_hitcounters\"" . " method=\"post\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"reset_hitcounters\">";
		echo "<input type=\"hidden\" name=\"albumid\" value=" . $album->getAlbumID() . ">";
		echo "<input type=\"hidden\" name=\"return\" value=" . urlencode($album->name) . ">";
		echo "<button type=\"submit\" class=\"tooltip\" id='edit_hitcounter' title=\"".gettext("Resets all hitcounters in the album.")."\"><img src=\"images/reset.png\" style=\"border: 0px;\" /> ".gettext("Reset hitcounters")."</button>";
		echo "</form>";
		echo "\n</tr>\n</table>";
	}
}
/**
 * puts out a row in the edit album table
 *
 * @param object $album is the album being emitted
 **/
function printAlbumEditRow($album) {
	echo "\n<div id=\"id_" . $album->getAlbumID() . '">';
	echo '<table cellspacing="0" width="100%">';
	echo "\n<tr>";
	echo '<td class="handle"><img src="images/drag_handle.png" style="border: 0px;" alt="Drag the album '."'".$album->name."'".'" /></td>';
	echo '<td style="text-align: left;" width="80">';
	echo '<a href="?page=edit&album=' . urlencode($album->name) .'" title="'.gettext('Edit this album:').' ' . $album->name .
 			'"><img height="40" width="40" src="' . $album->getAlbumThumb() . '" /></a>';
	echo "</td>\n";
	echo '<td  style="text-align: left;font-size:110%;" width="300"> <a href="?page=edit&album=' . urlencode($album->name) .
 			'" title="'.gettext('Edit this album:').' ' . $album->name . '">' . $album->getTitle() . '</a>';
	echo "</td>\n";

	if ($album->isDynamic()) {
		$si = "Dynamic";
	} else {
		$ci = count($album->getImages());
		if ($ci > 0) $si = "$ci image" . $si; else $si = "no image";
		if ($ci != 1) {	$si .= "s"; } else  {	$si .= "&nbsp;"; }
		if ($ci > 0) {
			$si = '<a href="?page=edit&album=' . urlencode($album->name) .'#imageList" title="'.gettext('Subalbum List').'">'.$si.'</a>';
		}
		$ca = count($album->getSubalbums());
		if ($ca > 0) $sa = $ca . " album" . $sa;  else $ca = "&nbsp;";
		if ($ca > 1) $sa .= "s";
		if ($ca > 0) {
			$sa = '<a href="?page=edit&album=' . urlencode($album->name) .'#subalbumList" title="'.gettext('Subalbum List').'">'.$sa.'</a>';
		}
	}
	echo "<td style=\"text-align: right;\" width=\"80\">" . $sa . "</td>";
	echo "<td style=\"text-align: right;\" width=\"80\">" . $si . "</td>";

	$wide='40px';
	echo "\n<td><table width='100%'><tr>\n<td>";
	echo "\n<td style=\"text-align:center;\" width='$wide';>";

	$pwd = $album->getPassword();
	if (!empty($pwd)) {
		echo '<img src="images/lock.png" style="border: 0px;" alt="'.gettext('Protected').'" /></a>';
	}

	echo "</td>\n<td style=\"text-align:center;\" width='$wide';>";
	if ($album->getShow()) {
		echo '<a class="publish" href="?action=publish&value=0&album=' . urlencode($album->name) .
 				'" title="'.gettext('Publish the album').' ' . $album->name . '">';
		echo '<img src="images/pass.png" style="border: 0px;" alt="'.gettext('Published').'" /></a>';
	} else {
		echo '<a class="publish" href="?action=publish&value=1&album=' . urlencode($album->name) .
 				'" title="'.gettext('Publish the album').' ' . $album->name . '">';
		echo '<img src="images/action.png" style="border: 0px;" alt="Publish the album ' . $album->name . '" /></a>';
	}

	echo "</td>\n<td style=\"text-align:center;\" width='$wide';>";
	echo '<a class="cache" href="cache-images.php?page=edit&album=' . urlencode($album->name) . "&return=*" .
 			'" title="'.gettext('Pre-cache images in').' ' . $album->name . '">';
	echo '<img src="images/cache.png" style="border: 0px;" alt="'.gettext('Cache the album').' ' . $album->name . '" /></a>';

	echo "</td>\n<td style=\"text-align:center;\" width='$wide';>";
	echo '<a class="warn" href="refresh-metadata.php?page=edit&album=' . urlencode($album->name) . "&return=*" .
 			'" title="'.gettext('Refresh metadata for the album').' ' . $album->name . '">';
	echo '<img src="images/warn.png" style="border: 0px;" alt="'.gettext('Refresh image metadata in the album').' ' . $album->name . '" /></a>';

	echo "</td>\n<td style=\"text-align:center;\" width='$wide';>";
	echo '<a class="reset" href="?action=reset_hitcounters&albumid=' . $album->getAlbumID() . '" title="'.gettext('Reset hitcounters for album').' ' . $album->name . '">';
	echo '<img src="images/reset.png" style="border: 0px;" alt="'.gettext('Reset hitcounters for the album').' ' . $album->name . '" /></a>';

	echo "</td>\n<td style=\"text-align:center;\" width='$wide';>";
	echo "<a class=\"delete\" href=\"javascript: confirmDeleteAlbum('?page=edit&action=deletealbum&album=" . urlencode($album->name) . "','".gettext("Are you sure you want to delete this entire album?")."','".gettext("Are you Absolutely Positively sure you want to delete the album? THIS CANNOT BE UNDONE!")."');\" title=\"".gettext("Delete the album")." " . xmlspecialchars($album->name) . "\">";
	echo '<img src="images/fail.png" style="border: 0px;" alt="'.gettext('Delete the album').' ' . xmlspecialchars($album->name) . '" /></a>';
	echo "</td>\n</tr></table>\n</td>";

	echo '</tr>';
	echo '</table>';
	echo "</div>\n";
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
	$album->setTitle(strip($_POST[$prefix.'albumtitle']));
	$album->setDesc(strip($_POST[$prefix.'albumdesc']));
	
	$tags = array();
	for ($i=0; $i<4; $i++) {
		$tag = trim(strip($_POST[$tagsprefix.'new_tag_value_'.$i]));
		unset($_POST[$tagsprefix.'new_tag_value_'.$i]);
		if (!empty($tag)) {
			$tags[] = $tag;
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
	$album->setPlace(strip($_POST[$prefix.'albumplace']));
	$album->setAlbumThumb(strip($_POST[$prefix.'thumb']));
	$album->setShow(strip($_POST[$prefix.'Published']));
	$album->setCommentsAllowed(strip($_POST[$prefix.'allowcomments']));
	$sorttype = strip($_POST[$prefix.'sortby']);
	$album->setSortType($sorttype);
	if ($sorttype == 'Manual') {
		$album->setSortDirection('image', 0);
	} else {
		if (empty($sorttype)) {
			$direction = '';
		} else {
			$direction = strip($_POST[$prefix.'image_sortdirection']);
		}
		$album->setSortDirection('image', $direction);
	}
	$album->setSubalbumSortType(strip($_POST[$prefix.'subalbumsortby']));
	$album->setSortDirection('album', strip($_POST[$prefix.'album_sortdirection']));
	if (isset($_POST['reset_hitcounter'])) {
		$id = $album->getAlbumID();
		query("UPDATE " . prefix('albums') . " SET `hitcounter`= 0 WHERE `id` = $id");
	}

	if ($_POST[$prefix.'albumpass'] == $_POST[$prefix.'albumpass_2']) {
		$pwd = trim($_POST[$prefix.'albumpass']);
		if (empty($pwd)) {
			if (empty($_POST[$prefix.'albumpass'])) {
				$album->setPassword(NULL);  // clear the gallery password
			}
		} else {
			$album->setPassword($pwd);
		}
	} else {
		$notify = '&mismatch=album';
	}
	$oldtheme = $album->getAlbumTheme();
	$newtheme = strip($_POST[$prefix.'album_theme']);
	if ($oldtheme != $newtheme) {
		$album->setAlbumTheme($newtheme);
		if (!empty($oldtheme) && empty($newtheme)) {
			// clean out old theme option table
			$tbl_options = prefix($album->name.'_options');
			$sql = "DROP TABLE $tbl_options";
			query($sql);
		}
		if (!empty($newtheme)) {
			// setup new theme option table
			$tbl_options = prefix($album->name.'_options');
			$sql = "CREATE TABLE IF NOT EXISTS $tbl_options (
							`id` int(11) unsigned NOT NULL auto_increment,
							`name` varchar(64) NOT NULL,
							`value` text NOT NULL,
							PRIMARY KEY  (`id`),
							UNIQUE (`name`)
						);";
			query($sql);
		}
	}
	$album->setPasswordHint(strip($_POST[$prefix.'albumpass_hint']));
	$album->setCustomData(strip($_POST[$prefix.'album_custom_data']));
	$album->save();
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
		$wv = explode('.', $v);
		$cv = explode('.', $c);
		$wvd = $wv[0] * 1000000000 + $wv[1] * 10000000 + $wv[2] * 100000 + $wv[3];
		$cvd = $cv[0] * 1000000000 + $cv[1] * 10000000 + $cv[2] * 100000 + $cv[3];
		if ($wvd > $cvd) {
			$_zp_WEB_Version = $v;
		} else {
			$_zp_WEB_Version = '';
		}
	}
	Return $_zp_WEB_Version;
}

/**
 * Gets an array of comments for the current admin
 *
 * @param int $number how many comments desired
 * @return array
 */
function fetchComments($number) {
	if ($number) {
		$limit = " LIMIT $number";
	}

	global $_zp_loggedin;
	$comments = array();
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		$sql = "SELECT `id`, `name`, `website`, `type`, `ownerid`,"
		. " (date + 0) AS date, `comment`, `email`, `inmoderation`, `ip` FROM ".prefix('comments')
		. " ORDER BY id DESC$limit";
		$comments = query_full_array($sql);
	} else  if ($_zp_loggedin & COMMENT_RIGHTS) {
		$albumlist = getManagedAlbumList();
		$albumIDs = array();
		foreach ($albumlist as $albumname) {
			$subalbums = getAllSubAlbumIDs($albumname);
			foreach($subalbums as $ID) {
				$albumIDs[] = $ID['id'];
			}
		}
		if (count($albumIDs) > 0) {
			$sql = "SELECT  `id`, `name`, `website`, `type`, `ownerid`,"
			." (`date` + 0) AS date, `comment`, `email`, `inmoderation`, `ip` "
			." FROM ".prefix('comments')." WHERE ";

			$sql .= " (`type`='albums' AND (";
			$i = 0;
			foreach ($albumIDs as $ID) {
				if ($i>0) { $sql .= " OR "; }
				$sql .= "(".prefix('comments').".ownerid=$ID)";
				$i++;
			}
			$sql .= ")) ";
			$sql .= " ORDER BY id DESC$limit";
			$albumcomments = query_full_array($sql);
			foreach ($albumcomments as $comment) {
				$comments[$comment['id']] = $comment;
			}
			$sql = "SELECT .".prefix('comments').".id as id, ".prefix('comments').".name as name, `website`, `type`, `ownerid`,"
			." (".prefix('comments').".date + 0) AS date, `comment`, `email`, `inmoderation`, `ip`, ".prefix('images').".`albumid` as albumid"
			." FROM ".prefix('comments').",".prefix('images')." WHERE ";
				
			$sql .= "(`type`='images' AND(";
			$i = 0;
			foreach ($albumIDs as $ID) {
				if ($i>0) { $sql .= " OR "; }
				$sql .= "(".prefix('comments').".ownerid=".prefix('images').".id AND ".prefix('images')
				.".albumid=$ID)";
				$i++;
			}
			$sql .= "))";
			$sql .= " ORDER BY id DESC$limit";
			$imagecomments = query_full_array($sql);
			foreach ($imagecomments as $comment) {
				$comments[$comment['id']] = $comment;
			}
			krsort($comments);
			if ($number) {
				if ($number < count($comments)) {
					$comments = array_slice($comments, 0, $number);
				}
			}
		}
	}
	return $comments;
}
?>
