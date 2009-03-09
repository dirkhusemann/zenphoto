<?php
if (checkForPage(getOption("zenpage_homepage"))) { // switch to a news page
	include ('pages.php');
} else {
	include ('indexpage.php');
}
?>