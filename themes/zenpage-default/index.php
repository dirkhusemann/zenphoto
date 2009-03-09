<?php
if (checkForHomePage()) { // switch to a news page
	include ('pages.php');
} else {
	include ('indexpage.php');
}
?>