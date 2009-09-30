<?php
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER . "/functions-rss.php");
startRSSCache();
if (!getOption('RSS_articles')) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	include(ZENFOLDER. '/404.php');
	exit();
}
require_once(ZENFOLDER . '/'.PLUGIN_FOLDER . "/image_album_statistics.php");
require_once(ZENFOLDER . '/'.PLUGIN_FOLDER . "/zenpage/zenpage-functions.php");
require_once(ZENFOLDER . '/'.PLUGIN_FOLDER . "/zenpage/zenpage-template-functions.php");
header('Content-Type: application/xml');
$themepath = THEMEFOLDER;
$catlink = getRSSNewsCatOptions("catlink");
$cattitle = getRSSNewsCatOptions("cattitle");
$option = getRSSNewsCatOptions("option");
if (isset($_GET['withimages'])) {
	$option = "withimages";
}
$host = getRSSHost();
$serverprotocol = getOption("server_protocol");
$uri = getRSSURI();
$s = getOption('feed_imagesize'); // uncropped image size
$locale = getRSSLocale();
$validlocale = getRSSLocaleXML();
$items = getOption("zenpage_rss_items"); // # of Items displayed on the feed
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo get_language_string(getOption('gallery_title'), $locale)." - News "; ?><?php if(!empty($cattitle)) { echo $cattitle ; } ?></title>
<link><?php echo $serverprotocol."://".$host.WEBPATH; ?></link>
<atom:link href="<?php echo $serverprotocol; ?>://<?php echo $uri; ?>" rel="self" type="application/rss+xml" />
<description><?php echo get_language_string(getOption('gallery_title'), $locale); ?></description>
<language><?php echo $validlocale; ?></language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>Zenpage - A CMS plugin for ZenPhoto</generator>
<?php 
switch ($option) {
	case "category":
		$latest = getLatestNews($items,"none",$catlink); 	
		break;
	case "news":
		$latest = getLatestNews($items,"none");
		break;
	case "withimages":
		$latest = getLatestNews($items,"with_latest_images_date");
		break;
} 
$count = "";
foreach($latest as $item) {
	$count++;
	$category = "";
	$categories = "";
	//get the type of the news item
	if(empty($item['thumb'])) {
		$title = htmlspecialchars(get_language_string($item['title'],$locale), ENT_QUOTES);
		$link = getNewsURL($item['titlelink']);
		$count2 = 0;
		$newsobj = new ZenpageNews($item['titlelink']);
		$category = $newsobj->getCategories();
		foreach($category as $cat){
			$count2++;
			if($count2 != 1) {
				$categories = $categories.", ";
			}
			$categories = $categories.get_language_string($cat['cat_name'], $locale);
		}
		$thumb = "";
		$filename = "";
		$content = get_language_string($item['content'],$locale);
		$type = "news";
		$ext = "";
		$album = "";
	} else {
		$categories = $item['category']->getTitle();
		$title = strip_tags(htmlspecialchars($item['title'], ENT_QUOTES));
		$link = $item['titlelink'];
		$content = $item['content'];
		$thumb = "<a href=\"".$link."\" title=\"".htmlspecialchars($title, ENT_QUOTES)."\"><img src=\"".$item['thumb']."\" alt=\"".htmlspecialchars($title,ENT_QUOTES)."\" /></a>\n";
		$filename = $item['filename'];
	
		$type = "image";
		$ext = strtolower(strrchr($filename, "."));
		$album = $item['category']->getFolder();
		$fullimagelink = $host.WEBPATH."/albums/".$item['category']->getFolder()."/".$item['filename'];
		$imagefile = "albums/".$item['category']->getFolder()."/".$item['filename'];
		$mimetype = getMimeType($ext);
	}
	$categories = htmlspecialchars($categories);
?>
<item>
	<title><?php echo $title." (".$categories.")"; ?></title>
	<link><?php echo '<![CDATA['.$serverprotocol.'://'.$host.$link.']]>';?></link>
	<description>
	<?php 
	if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4") ||  ($ext == ".3gp") ||  ($ext == ".mov")) {
		echo '<![CDATA[<a title="'.$title.' in '.$categories.'" href="'.$serverprotocol.'://'.$host.$link.'">'. $title.$ext.'</a><p>' . $content . '</p>]]>';
	}
	if (($ext == ".jpeg") || ($ext == ".jpg") || ($ext == ".gif") ||  ($ext == ".png")) {
		echo '<![CDATA[<a title="'.$title.' in '.$categories.'" href="'.$serverprotocol.'://'.$host.$link.'"><img border="0" src="'.$serverprotocol.'://'.$host.WEBPATH.'/'.ZENFOLDER.'/i.php?a='.$album.'&i='.$filename.'&s='.$s.'" alt="'. $title .'"></a><p>' . $content . '</p>]]>';
	}
	if (empty($ext)) {
		echo '<![CDATA[<p>'.$content.'</p>]]>';
	}
	?>
</description>
<?php if(getOption("feed_enclosure") AND !empty($item['thumb'])) { ?>
	<enclosure url="<?php echo $serverprotocol; ?>://<?php echo $fullimagelink; ?>" type="<?php echo $mimetype; ?>" length="<?php echo filesize($imagefile); ?>" />
<?php } ?>
    <category><?php echo $categories; ?>
    </category>
	<guid><?php echo '<![CDATA['.$serverprotocol.'://'.$host.$link.']]>';?></guid>
	<pubDate><?php echo date("r",strtotime($item['date'])); ?></pubDate> 
</item>
<?php
if($count === $items) {
	break;
}
} ?>
</channel>
</rss>
<?php endRSSCache();?>