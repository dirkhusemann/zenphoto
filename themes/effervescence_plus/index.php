<?php
if (getOption('custom_index_page') === 'gallery') {
	require('indexpage.php');
} else {
	require('gallery.php');
}
?>