<?php
if (checkForPage(getOption("zenpage_homepage"))) { // switch to a news page
	$ishomepage = true;
	include ('pages.php');
} else {
	include ('gallery.php');
}
?>