<?php
if (getOption('custom_index_page')) {
	require('indexpage.php');
} else {
	require('gallery.php');
}
?>