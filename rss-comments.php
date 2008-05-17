<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';

$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');

function fixRSSDate($bad_date) {
	$rval = FALSE;
	$parts = explode(" ", $bad_date);
	$date = $parts[0];
	$time = $parts[1];
	$date_parts = explode("-", $date);
	$year = $date_parts[0];
	$month = $date_parts[2];
	$day = $date_parts[1];
	$rval = date("r",strtotime("$day/$month/$year $time"));
	return $rval;
}

// check passwords
$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
foreach($albumscheck as $albumcheck) {
	if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
		$albumpasswordcheck1= " AND i.albumid != ".$albumcheck['id'];
		$albumpasswordcheck2= " AND a.id != ".$albumcheck['id'];
		$passwordcheck1 = $passwordcheck1.$albumpasswordcheck1;
		$passwordcheck2 = $passwordcheck2.$albumpasswordcheck2;
	}
}

if(getOption('mod_rewrite')) {
	$albumpath = "/"; $imagepath = "/"; $modrewritesuffix = getOption('mod_rewrite_image_suffix');
} else {
	$albumpath = "/index.php?album="; $imagepath = "&image="; $modrewritesuffix = "";
}
$items = getOption('feed_items'); // # of Items displayed on the feed
?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo getOption('gallery_title')." - latest comments"; ?></title>
<link><?php echo "http://".$host.WEBPATH; ?></link>
<atom:link href="http://<?php echo $host.WEBPATH; ?>/rss.php" rel="self" type="application/rss+xml" />
<description><?php echo getOption('gallery_title'); ?></description>
<language>en-us</language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPhoto Comment RSS Generator</generator>
<?php
	$admin = array_shift(getAdministrators());
	$adminname = $admin['name'];
	$adminemail = $admin['email'];
?>
<managingEditor><?php echo "$adminemail ($adminname)"; ?></managingEditor>
<webMaster><?php echo "$adminemail ($adminname)"; ?></webMaster>

<?php
db_connect();
$comments_images = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.type, c.website," 
. " c.date, c.comment FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a " 
. " WHERE c.ownerid = i.id AND i.albumid = a.id AND c.type = 'images'".$passwordcheck1
. " ORDER BY c.id DESC LIMIT $items");

$comments_albums = query_full_array("SELECT c.id, a.folder, a.title AS albumtitle, c.name, c.type, c.website," 
. " c.date, c.comment FROM ".prefix('comments')." AS c, ".prefix('albums')." AS a " 
. " WHERE c.ownerid = a.id AND c.type = 'albums'".$passwordcheck2
. " ORDER BY c.id DESC LIMIT $items"); 

$comments = array();
foreach ($comments_albums as $comment) {
	$comments[$comment['id']] = $comment;
}
foreach ($comments_images as $comment) {
	$comments[$comment['id']] = $comment;
}
krsort($comments);
$comments = array_slice($comments, 0, $items);
$count = 0;
foreach ($comments as $comment) {
	$author = $comment['name'];
	$album = $comment['folder'];
	if($comment['type'] === "images") {
		$imagetag = $imagepath.$comment['filename'].$modrewritesuffix;
	} else {
		$imagetag = "";
	}
	$date = $comment['date'];
	$albumtitle = htmlspecialchars($comment['albumtitle']);
	if ($comment['title'] == "") $title = $image; else $title = $comment['title'];
	$website = $comment['website'];
	$shortcomment = truncate_string($comment['comment'], 123);
	if(!empty($title)) {
		$title = ": ".$title;
	} 
	$count++;
?>

<item>
<title><?php echo htmlspecialchars($albumtitle.$title." by ".$author); ?></title>
<link><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$album.$imagetag.']]>';?></link>
<dc:creator><?php echo htmlspecialchars($author); ?></dc:creator>
<description><?php echo htmlspecialchars($shortcomment); ?></description>
<category><?php echo htmlspecialchars($albumtitle); ?></category>
<guid><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$album.$imagetag.']]>';?></guid>
<pubDate><?php echo fixRSSDate($date); ?></pubDate>
</item>
<?php } ?>
</channel>
</rss>

