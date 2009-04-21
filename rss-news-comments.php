<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', 0);
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER . PLUGIN_FOLDER. "zenpage/zenpage-template-functions.php");

$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');

if(isset($_GET['id'])) {
	$id = sanitize_numeric($_GET['id']);
} else {
	$id = "";
}
if(isset($_GET['title'])) {
	$title = " - ".sanitize($_GET['title']);
} else {
	$title = NULL;
}
if(isset($_GET['type'])) {
	$type = sanitize($_GET['type']);
} else {
	$type = "all";
}

if(isset($_GET['lang'])) {
	$locale = sanitize($_GET['lang']);
} else {
	$locale = getOption('locale');
}
$validlocale = strtr($locale,"_","-"); // for the <language> tag of the rss
$items = getOption("zenpage_rss_items");
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo strip_tags(get_language_string(getOption('gallery_title'), $locale))." - ".gettext("latest news and pages comments"); ?></title>
<link><?php echo "http://".$host.WEBPATH; ?></link>
<atom:link href="http://<?php echo $host.WEBPATH; ?>/rss-news-comments.php" rel="self" type="application/rss+xml" />
<description><?php echo get_language_string(getOption('gallery_title'), $locale); ?></description>
<language><?php echo $validlocale; ?></language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPage - A CMS plugin for Zenphoto</generator>
<?php
  $admin = getAdministrators();
	$admin = array_shift($admin);
	$adminname = $admin['user'];
	$adminemail = $admin['email'];
?>
<managingEditor><?php echo "$adminemail ($adminname)"; ?></managingEditor>
<webMaster><?php echo "$adminemail ($adminname)"; ?></webMaster>

<?php
$items = getOption("zenpage_rss_items");

db_connect();
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
<link><?php echo '<![CDATA[http://'.$host.getNewsURL($titlelink).']]>';?></link>
<description><?php echo $comment['comment']; ?></description>
<category><?php echo ""; ?></category>
<guid><?php echo '<![CDATA[http://'.$host.getNewsURL($titlelink).']]>';?></guid>
<pubDate><?php echo date("r",strtotime($date)); ?></pubDate>
</item>
<?php } ?>
</channel>
</rss>

