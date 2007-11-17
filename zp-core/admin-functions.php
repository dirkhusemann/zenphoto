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
function printLoginForm($redirect=null) {
  global $error;
  if (is_null($redirect)) { $redirect = "/" . ZENFOLDER . "/admin.php"; }
  
  echo "<p><img src=\"../" . ZENFOLDER . "/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";
  
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
    echo "\n  <a href=\"?emailpassword\">Email my password</a>";
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
  global $_zp_null_account;
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
    $albums = $gallery->getAlbums();
  } else {
    $albums = $curAlbum->getSubAlbums();
  }
  foreach ($albums as $folder) {
    $album = new Album($gallery, $folder);
    $list[$album->getTitle()] = $album->getFolder();
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

?>