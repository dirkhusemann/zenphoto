<?php
$plugin_is_filter = 5;
$plugin_description = gettext("An image crop tool. Places an image crop button in the image utilities box of the images tab.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---zenphoto_news.php.html";
$plugin_disable = version_compare(PHP_VERSION, '5.0.0') != 1;

if (!$plugin_disable) {
	zp_register_filter('admin_overview_left', 'printNews');
	require_once(dirname(__FILE__).'/zenphoto_news/rsslib.php'); 
}

function printNews($discard) {
?>
	<div class="box" id="overview-news">
		<h2 class="h2_bordered"><?php echo gettext("News from Zenphoto.org"); ?></h2>
		<?php echo RSS_Display("http://www.zenphoto.org/category/News/feed", 3); ?>
	</div>
<?php 
}
?>