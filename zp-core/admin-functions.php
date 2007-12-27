<?php

require_once("classes.php");

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
  echo " version ". getOption('version');
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
  echo "\n<html>";
  echo "\n<head>";
	echo "\n  <title>zenphoto administration</title>";
	echo "\n  <link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\" />";
	echo "\n  <script type=\"text/javascript\" src=\"js/prototype.js\"></script>";
	echo "\n  <script type=\"text/javascript\" src=\"js/admin.js\"></script>";
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
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printLoginForm($redirect=null, $logo=true) {
  global $error;
  if (is_null($redirect)) { $redirect = "/" . ZENFOLDER . "/admin.php"; }
  
  if ($logo) echo "<p><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";
  
  echo "\n  <div id=\"loginform\">";
  if ($error) {
    echo "<div class=\"errorbox\" id=\"message\"><h2>There was an error logging in.</h2> Check your username and password and try again.</div>";
  }
  echo "\n  <form name=\"login\" action=\"#\" method=\"POST\">";
  echo "\n    <input type=\"hidden\" name=\"login\" value=\"1\" />";
  echo "\n    <input type=\"hidden\" name=\"redirect\" value=\"$redirect\" />";
  
  echo "\n    <table>";
  echo "\n      <tr><td>Login</td><td><input class=\"textfield\" name=\"user\" type=\"text\" size=\"20\" /></td></tr>";
  echo "\n      <tr><td>Password</td><td><input class=\"textfield\" name=\"pass\" type=\"password\" size=\"20\" /></td></tr>";
  echo "\n      <tr><td colspan=\"2\"><input class=\"button\" type=\"submit\" value=\"Log in\" /></td></tr>";
  echo "\n    </table>";
  echo "\n  </form>";
  $email = getOption('admin_email');
  if (!empty($email)) {
    echo "\n  <a href=\"?emailreset\">Email password reset link</a>";
  }
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
  
  echo "\n\n<a href=\"".WEBPATH."/" . ZENFOLDER . "/admin.php\" id=\"logo\"><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></a>";
  echo "\n<div id=\"links\">";
  echo "\n  <a href=\"../\">View Gallery</a> &nbsp; | &nbsp; <a href=\"?logout\">Log Out</a>";
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
  // Which page should we highlight? Default is home.
  if (isset($_GET['page'])) {
    $page= $_GET['page'];
  } else {
    $page= "home";
  }
    
  echo "\n  <ul id=\"nav\">";
  echo "\n    <li". ($page == "home" ? " class=\"current\""     : "") . 
    "> <a href=\"admin.php?page=home\">overview</a></li>";
  echo "\n    <li". ($page == "comments" ? " class=\"current\"" : "") . 
    "> <a href=\"admin.php?page=comments\">comments</a></li>";
  echo "\n    <li". ($page == "upload" ? " class=\"current\""   : "") . 
    "> <a href=\"admin.php?page=upload\">upload</a></li>";
  echo "\n    <li". ($page == "edit" ? " class=\"current\""     : "") . 
    "> <a href=\"admin.php?page=edit\">edit</a></li>";
  echo "\n    <li". ($page == "options" ? " class=\"current\""  : "") . 
    "> <a href=\"admin.php?page=options\">options</a></li>";
  echo "\n    <li". ($page == "themes" ? " class=\"current\""  : "") . 
    "> <a href=\"admin.php?page=themes\">themes</a></li>";
  echo "\n  </ul>";
 
}

function checked($checked, $current) {
	if ( $checked == $current)
		echo ' checked="checked"';
}

function bool($param) {
  if ($param) {
    return true;
  } else {
    return false;
  }
}

function genAlbumList(&$list, $curAlbum=NULL) {
  global $gallery;
  if (is_null($curAlbum)) {
    $albums = $gallery->getAlbums(0);
  } else {
    $albums = $curAlbum->getSubAlbums(0);
  }
  foreach ($albums as $folder) {
    $album = new Album($gallery, $folder);
    $list[$album->getFolder()] = $album->getTitle();
    genAlbumList($list, $album);  /* generate for subalbums */
  }
}

function displayDeleted() { 
  /* Display a message if needed. Fade out and hide after 2 seconds. */  
  if (isset($_GET['ndeleted'])) {      
    $ntdel = strip($_GET['ndeleted']);      
    if ($ntdel <= 2) { 
	  $msg = "Image "; 
	} else { 
	  $msg = "Album "; 
	  $ntdel = $ntdel - 2; 
	}
	if ($ntdel == 2) { 
	  $msg = $msg . "failed to delete."; 
	  $class = 'errorbox'; 
	} else { 
	  $msg = $msg . "deleted successfully."; 
	  $class = 'messagebox'; 
	}
	echo '<div class="' . $class . '" id="message">'; 
	echo  "<h2>" . $msg . "</h2>";  
	echo '</div>'; 
	echo '<script type="text/javascript">'; 
	echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);"; 
	echo '</script>'; 
	}
}

function customOptions($optionHandler, $indent="") {
  $supportedOptions = $optionHandler->getOptionsSupported();
  if (count($supportedOptions) > 0) {
    $keys = array_keys($supportedOptions); 
    natcasesort($keys);
    foreach($keys as $key) {
      $row = $supportedOptions[$key];
      $type = $row['type'];
      $desc = $row['desc'];
      $sql = "SELECT `value` FROM " . prefix('options') . " Where `name`='" . escape($key) . "';";
      $db = query_single_row($sql);
      $v = $db['value'];

      echo "\n<tr>\n"; 
      echo '<td width="175">' . $indent . str_replace('_', ' ', $key) . ":</td>\n";
      
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

function generateListFromArray($currentValue, $list) {
  sort($list);
  foreach($list as $item) {
    echo '<option value="' . $item . '"';
    if ($currentValue == $item) { 
      echo ' selected="selected"'; 
    }
    echo '>' . $item . "</option>\n";
  }
}

function generateListFromFiles($currentValue, $root, $suffix) {
  chdir($root);
  $filelist = glob('*'.$suffix);
  sort($filelist);
  $list = array();
  foreach($filelist as $file) {
    $list[] = str_replace($suffix, '', $file); 
  }
  generateListFromArray($currentValue, $list);
}
/**
 * emits the html for editing album information
 * called in edit album and mass edit
 *@param string param1 the index of the entry in mass edit or '0' if single album
 *@param object param2 the album object
*@return nothing
 *@since 1.1.3
 */
function printAlbumEditForm($index, $album) {
  global $sortby, $images;
  if ($index == 0) {
    if (isset($saved)) {
      $album->setSubalbumSortType('Manual');
    }
    $prefix = '';
  } else {
    $prefix = "$index-";
	echo "<p><em><strong>" . $album->name . "</strong></em></p>";
  }
       
  echo "\n<input type=\"hidden\" name=\"" . $prefix . "folder\" value=\"" . $album->name . "\" />";
  echo "\n<div class=\"box\" style=\"padding: 15px;\">";
  echo "\n<table>";
  echo "\n<tr>";
  echo "<td align=\"right\" valign=\"top\">Album Title: </td> <td><input type=\"text\" name=\"".$prefix."albumtitle\" value=\"" . 
       $album->getTitle() . '" />';
  $id = $album->getAlbumId();
  $result = query_single_row("SELECT `hitcounter` FROM " . prefix('albums') . " WHERE id = $id");
  $hc = $result['hitcounter'];
  if (empty($hc)) { $hc = '0'; }
  echo " Hit counter: ". $hc . " <input type=\"checkbox\" name=\"reset_hitcounter\"> Reset</td>";
  echo '</tr>';
  echo "\n<tr><td align=\"right\" valign=\"top\">Album Description: </td> <td><textarea name=\"".$prefix."albumdesc\" cols=\"60\" rows=\"6\">" .
       $album->getDesc() . "</textarea></td></tr>";
  echo "\n<tr>";		
  echo "\n<td>Album password</td>";
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
  echo "\n<tr><td align=\"right\" valign=\"top\">Password hint: </td> <td><input type=\"text\" name=\"".$prefix."albumpass_hint\" class=\"tags\" value=\"" . 
       $album->getPasswordHint() . '" /></td></tr>';
  echo "\n<tr><td align=\"right\" valign=\"top\">Tags: </td> <td><input type=\"text\" name=\"".$prefix."albumtags\" class=\"tags\" value=\"" . 
       $album->getTags() . '" /></td></tr>';

  $d = $album->getDateTime();
  if ($d == "0000-00-00 00:00:00") { 
    $d = ""; 
  }

  echo "\n<tr><td align=\"right\" valign=\"top\">Date: </td> <td><input type=\"text\" name=\"".$prefix."albumdate\" value=\"" . $d . '" /></td></tr>';
  echo "\n<tr><td align=\"right\" valign=\"top\">Location: </td> <td><input type=\"text\" name=\"".$prefix."albumplace\" value=\"" .
       $album->getPlace() . "\" /></td></tr>";
  echo "\n<tr><td align=\"right\" valign=\"top\">Thumbnail: </td> ";
  echo "\n<td>";
  echo "\n<select id=\"thumbselect\" class=\"thumbselect\" name=\"".$prefix."thumb\" onChange=\"updateThumbPreview(this)\">";

  foreach ($images as $filename) { 
    $image = new Image($album, $filename);
    $selected = ($filename == $album->get('thumb')); 
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

  echo "\n</select>";
  echo "\n<script type=\"text/javascript\">updateThumbPreview(document.getElementById('thumbselect'));</script>";
  echo "\n</td>";
  echo "\n</tr>";
  echo "\n<tr><td align=\"right\" valign=\"top\">Allow Comments: </td><td><input type=\"checkbox\" name=\"" .
       $prefix."allowcomments\" value=\"1\"";
  if ($album->getCommentsAllowed()) {
    echo "CHECKED";
  } 

  echo "\n>";
  echo "\n</td></tr>";
  echo "\n<tr><td align=\"right\" valign=\"top\">Published: </td><td><input type=\"checkbox\" name=\"" . 
        $prefix."Published\" value=\"1\"";
  if ($album->getShow()) {
    echo "CHECKED";
  } 
  echo ">";
  echo "\n</td></tr>";
  echo "\n<tr>";
  echo "\n<td align=\"right\" valign=\"top\">Sort subalbums by: </td>";
  echo "\n<td>";
  echo "\n<select id=\"sortselect\" name=\"".$prefix."subalbumsortby\">";

  foreach ($sortby as $sorttype) { 
    echo "\n<option value=\"" . $sorttype . "\"";
	if ($sorttype == $album->getSubalbumSortType()) {
	  echo ' selected="selected"';
    }
	echo ">$sorttype </option>";
  } 

  echo "\n</select>";
  echo "&nbsp;Descending <input type=\"checkbox\" name=\"".$prefix."album_sortdirection\" value=\"1\"";

  if ($album->getSortDirection('image')) {
    echo "CHECKED";
  } 

  echo ">";  
  echo "\n</td>";
  echo "\n</tr>";
  echo "\n<tr>";
  echo "\n<td align=\"right\" valign=\"top\">Sort images by: </td>";
  echo "\n<td>";
  echo "\n<select id=\"sortselect\" name=\"".$prefix."sortby\">";

  foreach ($sortby as $sorttype) { 
    echo "\n<option value=\"$sorttype\"";
	if ($sorttype == $album->getSortType()) {
	  echo ' selected="selected"'; 
	}
	echo ">$sorttype</option>";
  }

  echo "\n</select>";
  echo "&nbsp;Descending <input type=\"checkbox\" name=\"".$prefix."image_sortdirection\" value=\"1\""; 

  if ($album->getSortDirection('image')) {
    echo "CHECKED";
  } 

  echo ">"; 
  echo "\n</td>";
  echo "\n</tr>";
  if ($album->getNumImages() > 0) { 
    echo "\n<tr><td></td><td valign=\"top\"><a href=\"cache-images.php?album=" . 
         queryencode($album->name) . "\"><img src=\"images/cache.png\" style=\"border: 0px;\" />Pre-Cache Images</a></strong> - Cache newly uploaded images.</td></tr>";

	echo "\n<tr><td></td><td valign=\"top\"><a href=\"refresh-metadata.php?album=" . 
	     queryencode($album->name) . "\"><img src=\"images/warn.png\" style=\"border: 0px;\" />Refresh Image Metadata</a> - Forces a refresh of the EXIF and IPTC data for all images in the album.</td></tr>";
  }
  echo "\n<tr><td></td><td valign=\"top\"><a href=\"?action=reset_hitcounters&albumid=" . 
       $album->getAlbumID() . "&return=" . $album->name . "\"><img src=\"images/reset.png\" style=\"border: 0px;\" />Reset hitcounters</a></strong> - Resets all hitcounters in the album.</td></tr>";

  echo "\n</table>";  
  echo "\n<input type=\"submit\" value=\"save\" />";
  echo "\n</div>";

}
/**
* puts out a row in the edit album table
* @param object $album is the album being emitted
**/
function printAlbumEditRow($album) {
  echo "\n<div id=\"id_" . $album->getAlbumID() . '">';
  echo '<table cellspacing="0" width="100%">';
  echo "\n<tr>";
  echo '<td style="text-align: left;" width="45">';
  echo '<a href="?page=edit&album=' . urlencode($album->name) .'" title="Edit this album: ' . $album->name . 
       '"><img height="40" width="40" src="' . $album->getAlbumThumb() . '" /></a>';
  echo "</td>\n";
  echo '<td  style="text-align: left;font-size:110%;" width="300"> <a href="?page=edit&album=' . urlencode($album->name) . 
       '" title="Edit this album: ' . $album->name . '">' . $album->getTitle() . '</a>';
  echo "</td>\n";

  echo "\n<td style=\"text-align: left;\">";

  $pwd = $album->getPassword();
  if (!empty($pwd)) {
    echo '<img src="images/lock.png" style="border: 0px;" alt="Protected" /></a>';
  } else {
    echo '<img src="images/blank.png" style="border: 0px;" alt="No password" /></a>';
  }
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp\n";
  if ($album->getShow()) {
    echo '<a class="publish" href="?action=publish&value=0&album=' . queryencode($album->name) . 
         '" title="Publish the album <em>' . $album->name . '</em>">';
    echo '<img src="images/pass.png" style="border: 0px;" alt="Published" /></a>';
  } else {
    echo '<a class="publish" href="?action=publish&value=1&album=' . queryencode($album->name) . 
         '" title="Publish the album <em>' . $album->name . '</em>">';
    echo '<img src="images/action.png" style="border: 0px;" alt="Publish the album ' . $album->name . '" /></a>';
  }
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp\n";
  echo '<img src="images/cache.png" style="border: 0px;" alt="Cache the album ' . $album->name . '" /></a>';
  echo '<a class="warn" href="refresh-metadata.php?album=' . queryencode($album->name) . 
       '" title="Refresh metadata for the album <em>' . $album->name . '</em>">';
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp\n";
  echo '<img src="images/warn.png" style="border: 0px;" alt="Refresh image metadata in the album ' . $album->name . '>" /></a>';
  echo '<a class="reset" href="?action=reset_hitcounters&albumid=' . $album->getAlbumID() . '" title="Reset hitcounters for album <em>' . $album->name . '</em>">';
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp\n";
  echo '<img src="images/reset.png" style="border: 0px;" alt="Reset hitcounters for the album ' . $album->name . '" /></a>';
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp\n";
  echo "<a class=\"delete\" href=\"javascript: confirmDeleteAlbum('?page=edit&action=deletealbum&album=" . queryEncode($album->name) . "');\" title=\"Delete the album " . $album->name . "\">";
  echo '<img src="images/fail.png" style="border: 0px;" alt="Delete the album ' . $album->name . '" /></a>';
  echo '<a class="cache" href="cache-images.php?album=' . queryencode($album->name) . 
       '&return=edit" title="Pre-Cache the album <em>' . $album->name . '</em>">';
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
  $notify = '';      
  $album->setTitle(strip($_POST[$prefix.'albumtitle']));
  $album->setDesc(strip($_POST[$prefix.'albumdesc']));
  $album->setTags(strip($_POST[$prefix.'albumtags']));
  $album->setDateTime(strip($_POST[$prefix."albumdate"]));
  $album->setPlace(strip($_POST[$prefix.'albumplace']));
  $album->setAlbumThumb(strip($_POST[$prefix.'thumb']));
  $album->setShow(strip($_POST[$prefix.'Published']));
  $album->setCommentsAllowed(strip($_POST[$prefix.'allowcomments']));
  $album->setSortType(strip($_POST[$prefix.'sortby']));
  if ($_POST[$prefix.'sortby'] == 'Manual') {
    $album->setSortDirection('image', 0);   
  } else {
    $album->setSortDirection('image', strip($_POST[$prefix.'image_sortdirection']));   
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

  $album->setPasswordHint(strip($_POST[$prefix.'albumpass_hint']));   
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
  $c = getOption('version');
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

?>