<?php

require_once("classes.php");

function printAdminFooter() {
  echo "<div id=\"footer\">";
  echo "\n  <a href=\"http://www.zenphoto.org\" title=\"A simpler web photo album\">zen<strong>photo</strong></a>";
  echo "version ". zp_conf('version');
  echo "\n</div>";
}

function printAdminHeader() {
  
  echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
  echo "\n<html>";
  echo "\n<head>";
	echo "\n  <title>zenphoto administration</title>";
	echo "\n  <link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\" />";
	echo "\n  <script type=\"text/javascript\" src=\"admin.js\"></script>";
}

function printAdminLink($action, $text, $title=NULL, $class=NULL, $id=NULL) {
  
  printLink("admin.php?page=". $action, $text, $title, $class, $id);
}

// TODO: make this take an Image as an argument, not the alt and thumb text
function printImageThumb($image, $class=NULL, $id=NULL) { 
	echo "\n  <img class=\"imagethumb\" id=\"id_". $image->getImageID() ."\" src=\"" . $image->getThumb() . "\" alt=\"". $image->getTitle() . "\"" .
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


function printLoginForm() {
  
  echo "<p><img src=\"../zen/images/zen-logo.gif\" title=\"Zen Photo\" /></p>";
  echo "\n  <div id=\"loginform\">";
  echo "\n  <form name=\"login\" action=\"#\" method=\"POST\">";
  echo "\n    <input type=\"hidden\" name=\"login\" value=\"1\" />";
  
  // Is rewrite enabled?
  if (zp_conf('mod_rewrite')) {
    echo "\n    <input type=\"hidden\" name=\"redirect\" value=\"/admin/\" />";
  } else {
    echo "\n    <input type=\"hidden\" name=\"redirect\" value=\"/admin.php\" />";
  }
  
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


function printLogoAndLinks() {
  
  echo "\n\n<a href=\"".WEBPATH."/zen/admin.php\" id=\"logo\"><img src=\"../zen/images/zen-logo.gif\" title=\"Zen Photo\" /></a>";
  echo "\n<div id=\"links\">";
  echo "\n  <a href=\"../\">View Gallery</a> &nbsp; | &nbsp; <a href=\"?logout\">Log Out</a>";
  echo "\n</div>";
}

function printTabs($page) {
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