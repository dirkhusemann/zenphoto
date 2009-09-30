<?php
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER . "/functions-rss.php");
startRSSCache();
if (!getOption('RSS_article_comments')) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	include(ZENFOLDER. '/404.php');
	exit();
}
require_once(ZENFOLDER . '/'.PLUGIN_FOLDER. "/zenpage/zenpage-template-functions.php");
header('Content-Type: application/xml');
$host = getRSSHost();
$serverprotocol = getOption("server_protocol");
$id = getRSSID();
$title = getRSSTitle();
$type = getRSSType();
$locale = getRSSLocale();
$validlocale = getRSSLocaleXML();
$items = getOption("zenpage_rss_items");
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo strip_tags(get_language_string(getOption('gallery_title'), $locale))." - ".gettext("latest news and pages comments"); ?></title>
<link><?php echo $serverprotocol."://".$host.WEBPATH; ?></link>
<atom:link href="<?php echo $serverprotocol; ?>://<?php echo $host.WEBPATH; ?>/rss-news-comments.php" rel="self" type="application/rss+xml" />
<description><?php echo get_language_string(getOption('gallery_title'), $locale); ?></description>
<language><?php echo $validlocale; ?></language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPage - A CMS plugin for Zenphoto</generator>
<?php
$comments = getLatestZenpageComments($items,$type,$id);
$count = 0;
foreach ($comments as $comment) {
	if($comment['anon'] === "0") {
		$author = " ".gettext("by")." ".$comment['name'];
	} else {
		$author = "";
	}
	$date = $comment['date'];
	$title = get_language_string($comment['title']);
	$titlelink = $comment['titlelink'];
	$website = $comment['website'];
?>
<item>
<title><?php echo strip_tags($title.$author); ?></title>
<link><?php echo '<![CDATA['.$serverprotocol.'://'.$host.getNewsURL($titlelink)."#".$comment['id'].']]>';?></link>
<description><?php echo $comment['comment']; ?></description>
<category><?php echo ""; ?></category>
<guid><?php echo '<![CDATA['.$serverprotocol.'://'.$host.getNewsURL($titlelink)."#".$comment['id'].']]>';?></guid>
<pubDate><?php echo date("r",strtotime($date)); ?></pubDate>
</item>
<?php } ?>
</channel>
</rss>
<?php endRSSCache();?>
