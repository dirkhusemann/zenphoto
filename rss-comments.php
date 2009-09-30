<?php
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER . "/functions-rss.php");
startRSSCache();
if (!getOption('RSS_comments')) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	include(ZENFOLDER. '/404.php');
	exit();
}
header('Content-Type: application/xml');
$host = getRSSHost();
$serverprotocol = getOption("server_protocol");
$id = getRSSID() ;
$title = getRSSTitle();
$type = getRSSType();
$locale = getRSSLocale();
$validlocale = getRSSLocaleXML();
$albumpath = getRSSImageAndAlbumPaths("albumpath");
$modrewritesuffix = getRSSImageAndAlbumPaths("modrewritesuffix");
$imagepath = getRSSImageAndAlbumPaths("imagepath");
$items = getOption('feed_items'); // # of Items displayed on the feed
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo strip_tags(get_language_string(getOption('gallery_title'), $locale))." - ".gettext("latest comments").$title; ?></title>
<link><?php echo $serverprotocol."://".$host.WEBPATH; ?></link>
<atom:link href="<?php echo $serverprotocol; ?>://<?php echo $host.WEBPATH; ?>/rss-comments.php" rel="self" type="application/rss+xml" />
<description><?php echo get_language_string(getOption('gallery_title'), $locale); ?></description>
<language><?php echo $validlocale; ?></language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPhoto Comment RSS Generator</generator>
<?php
$comments = getLatestComments($items,$type,$id);
foreach ($comments as $comment) {
	if($comment['anon'] === "0") {
		$author = " ".gettext("by")." ".$comment['name'];
	} else {
		$author = "";
	}
	$album = $comment['folder'];
	if($comment['type'] != "albums" AND $comment['type'] != "news" AND $comment['type'] != "pages") { // check if not comments on albums or Zenpage items
		$imagetag = $imagepath.$comment['filename'].$modrewritesuffix;
	} else {
		$imagetag = "";
	}
	$date = $comment['date'];
	$albumtitle = $comment['albumtitle'];
	if ($comment['title'] == "") $title = $image; else $title = get_language_string($comment['title']);
	$website = $comment['website'];
	if(!empty($title)) {
		$title = ": ".$title;
	}
?>
<item>
<title><?php echo strip_tags($albumtitle.$title.$author); ?></title>
<link><?php echo '<![CDATA['.$serverprotocol.'://'.$host.WEBPATH.$albumpath.$album.$imagetag."#".$comment['id'].']]>';?></link>
<description><?php echo $comment['comment']; ?></description>
<category><?php echo strip_tags($albumtitle); ?></category>
<guid><?php echo '<![CDATA['.$serverprotocol.'://'.$host.WEBPATH.$albumpath.$album.$imagetag."#".$comment['id'].']]>';?></guid>
<pubDate><?php echo date("r",strtotime($date)); ?></pubDate>
</item>
<?php } ?>
</channel>
</rss>
<?php endRSSCache();?>
