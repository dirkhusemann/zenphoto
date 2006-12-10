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
  echo " version ". zp_conf('version');
  echo " | <a href=\"changelog.html\" title=\"View Changelog\">Changelog</a>\n</div>";
}

/**
 * Print the header for all admin pages. Starts at <DOCTYPE> but does not include the </head> tag, 
 * in case there is a need to add something further.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printAdminHeader() {
  
  header ('Content-Type: text/html; charset=' . zp_conf('charset'));
  
  echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
  echo "\n<html>";
  echo "\n<head>";
	echo "\n  <title>zenphoto administration</title>";
	echo "\n  <link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\" />";
	echo "\n  <script type=\"text/javascript\" src=\"admin.js\"></script>";
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
function printAdminLink($action, $text, $title=NULL, $class=NULL, $id=NULL) {
  
  printLink("admin.php?page=". $action, $text, $title, $class, $id);
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
  printLink(WEBPATH . "/zen/albumsort.php?page=edit&album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
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
  printLink(WEBPATH . "/index.php?album=". urlencode( ($album->getFolder()) ), $text, $title, $class, $id);
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
function printImageThumb($image, $class=NULL, $id=NULL) {
	echo "\n  <img class=\"imagethumb\" id=\"id_". $image->id ."\" src=\"" . $image->getThumb() . "\" alt=\"". $image->getTitle() . "\"" .
    ((zp_conf('thumb_crop')) ? " width=\"".zp_conf('thumb_crop_width')."\" height=\"".zp_conf('thumb_crop_height')."\"" : "") .
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
}

// TODO: This is a copy of the function in template-functions. Refactor at some point
function printLink($url, $text, $title=NULL, $class=NULL, $id=NULL) {
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
function printLoginForm($redirect="/zen/admin.php") {
  global $error;
  
  echo "<p><img src=\"../zen/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";
  
  echo "\n  <div id=\"loginform\">";
  if ($error) {
    echo "<div class=\"errorbox\" id=\"message\"><h2>There was an error logging in.</h2> Check your username and password and try again.</h2></div>";
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
  
  echo "\n\n<a href=\"".WEBPATH."/zen/admin.php\" id=\"logo\"><img src=\"../zen/images/zen-logo.gif\" title=\"Zen Photo\" /></a>";
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
  echo "\n  </ul>";
}

?>
