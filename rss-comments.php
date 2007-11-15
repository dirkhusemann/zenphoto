<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';

if(getOption('mod_rewrite')) { 
  $albumpath = "/"; $imagepath = "/"; 
} else { 
  $albumpath = "/index.php?album="; $imagepath = "&image="; 
}
$items = getOption('feed_items'); // # of Items displayed on the feed
?>
<rss version="2.0">
<channel>
<title><?php echo getOption('gallery_title')." - latest comments"; ?></title>
<link><?php echo "http://".$_SERVER["HTTP_HOST"].WEBPATH; ?></link>
<description><?php echo getOption('gallery_title'); ?></description>
<language>en-us</language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>Acrylian's ZenPhoto Comment RSS Generator based on Tris's Latest Comments function from zenphoto admin.php</generator>
<managingEditor><?php echo getOption('admin_email'); ?></managingEditor>
<webMaster><?php echo getOption('admin_email'); ?></webMaster>
<?php
db_connect();
$comments = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website," . " c.date, c.comment FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a " . " WHERE c.imageid = i.id AND i.albumid = a.id ORDER BY c.id DESC LIMIT ".$items;);
foreach ($comments as $comment)
{
$author = $comment['name'];
$album = $comment['folder'];
$image = $comment['filename'];
$albumtitle = $comment['albumtitle'];
if ($comment['title'] == "") $title = $image; else $title = $comment['title'];
$website = $comment['website'];
$comment = truncate_string($comment['comment'], 123);
?>
<item>
<title><?php echo $albumtitle.": ".$title." by ".$author; ?></title>
<link><?php echo '<![CDATA[http://'.$_SERVER['HTTP_HOST'].WEBPATH.$albumpath.$album.$imagepath.$image.']]>';?></link>
<dc:creator><?php echo $author; ?></dc:creator>
<description><?php echo $comment; ?></description>
<guid><?php echo '<![CDATA[http://'.$_SERVER['HTTP_HOST'].WEBPATH.$albumpath.$album.$imagepath.$image.']]>';?></guid>
</item>
<?php } ?>
</channel>
</rss>