<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';

if(getOption('mod_rewrite')) {
  $albumpath = "/"; $imagepath = "/"; $modrewritesuffix = getOption('mod_rewrite_image_suffix');
} else {
  $albumpath = "/index.php?album="; $imagepath = "&image="; $modrewritesuffix = "";
}
$items = getOption('feed_items'); // # of Items displayed on the feed
?>
<rss version="2.0">
<channel>
<title><?php echo getOption('gallery_title')." - latest comments"; ?></title>
<link>
<?php echo "http://".$_SERVER["HTTP_HOST"].WEBPATH; ?>
</link>
<description>
<?php echo getOption('gallery_title'); ?>
</description>
<language>
en-us
</language>
<pubDate>
<?php echo date("r", time()); ?>
</pubDate>
<lastBuildDate>
<?php echo date("r", time()); ?>
</lastBuildDate>
<docs>
http://blogs.law.harvard.edu/tech/rss
</docs>
<generator>
Acrylian's ZenPhoto Comment RSS Generator based on Tris's Latest
Comments function from zenphoto admin.php
</generator>
<managingEditor>
<?php echo getOption('admin_name'); ?>
</managingEditor>
<webMaster>
<?php echo getOption('admin_name'); ?>
</webMaster>
<?php
db_connect();
$comments = query_full_array("SELECT `id`, `name`, `website`, `type`, `imageid`,"
                           . " date, comment, email, inmoderation FROM ".prefix('comments')
                           . " ORDER BY id DESC LIMIT $items" );
foreach ($comments as $comment) {
  $id = $comment['id'];
  $author = $comment['name'];
  $email = $comment['email'];
  if ($comment['type']=='images') {
    $imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
                     " WHERE `id`=" . $comment['imageid']);
    if ($imagedata) {
      $imgdata = $imagedata[0];
      $image = $imgdata['filename'];
      if ($imgdata['title'] == "") $title = $image; else $title = $imgdata['title'];
      $title = '/ ' . $title;
      $imagetag = $imagepath.$image.$modrewritesuffix;
      $albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
                       " WHERE `id`=" . $imgdata['albumid']);
      if ($albmdata) {
        $albumdata = $albmdata[0];
        $album = $albumdata['folder'];
        $albumtitle = $albumdata['albumtitle'];
        if (empty($albumtitle)) $albumtitle = $album;
      } else {
        $title = 'database error';
      }
    } else {
      $title = 'database error';
    }
  } else {
    $image = '';
    $imagetag= '';
    $title = '';
    $albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
                     " WHERE `id`=" . $comment['imageid']);
    if ($albmdata) {
      $albumdata = $albmdata[0];
      $album = $albumdata['folder'];
      $albumtitle = $albumdata['albumtitle'];
      if (empty($albumtitle)) $albumtitle = $album;
    } else {
      $title = 'database error';
    }
  }
$date = $comment['date'];
  $website = $comment['website'];
  $shortcomment = truncate_string($comment['comment'], 123);
  $fullcomment = $comment['comment'];
  $inmoderation = $comment['inmoderation'];
  ?>
<item>
<title><?php echo $albumtitle.": ".$title." by ".$author; ?></title>
<link>
  <?php echo '<![CDATA[http://'.$_SERVER['HTTP_HOST'].WEBPATH.$albumpath.$album.$imagetag.']]>';?>
</link>
<dc:creator>
<?php echo $author; ?>
</dc:creator>
<description>
<?php echo $shortcomment; ?>
</description>
<category>
<?php echo $albumtitle; ?>
</category>
<guid>
<?php echo '<![CDATA[http://'.$_SERVER['HTTP_HOST'].WEBPATH.$albumpath.$album.$imagetag.']]>';?>
</guid>
<pubDate>
<?php echo $date; ?>
</pubDate>
</item>
<?php } ?>
</channel>
</rss>
