<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';


$albumnr = $_GET[albumnr];
$albumname = $_GET[albumname];

if ($albumname != "") { $albumname = " - for album: ".$_GET[albumname]; }
if(getOption('mod_rewrite'))
 { $albumpath = "/"; $imagepath = "/"; $modrewritesuffix = getOption('mod_rewrite_image_suffix'); }
else
 { $albumpath = "/index.php?album="; $imagepath = "&image="; $modrewritesuffix = ""; }

?>
<rss version="2.0">
<channel>
<title><?php echo getOption('gallery_title'); ?><?php echo $albumname; ?></title>
<link><?php echo "http://".$_SERVER["HTTP_HOST"].WEBPATH; ?></link>
<description><?php echo getOption('gallery_title'); ?></description>
<language>en-us</language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>Acrylian's ZenPhoto Album RSS Generator based on Alenônimo's ZenPhoto RSS Generator which is based on ThinkDreams' Generator</generator>
<managingEditor><?php echo getOption('admin_name'); ?></managingEditor>
<webMaster><?php echo getOption('admin_name'); ?></webMaster>
<?php 
$iw = $cw = 400; // Image Width
$ih = $ch = 300; // Image Height
$items = getOption('feed_items'); // # of Items displayed on the feed

db_connect();


if (is_numeric($albumnr) && $albumnr != "") { 
	$albumWhere = "images.albumid = $albumnr AND";
} else {
	$albumWhere = "";
}


$result = query_full_array("SELECT images.albumid, images.date AS date, images.filename AS filename, images.title AS title, " .
                             "albums.folder AS folder, albums.title AS albumtitle, images.show, albums.show, albums.password FROM " . 
                              prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
                              " WHERE ".$albumWhere." images.albumid = albums.id AND images.show=1 AND albums.show=1 AND albums.password=''".
                              " AND albums.folder != ''".
                              " ORDER BY images.id DESC LIMIT ".$items);

foreach ($result as $images) {

?>
<item>
	<title><?php echo $images['title']; ?></title>
	<link><?php echo '<![CDATA[http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></link>
	<description><?php echo '<![CDATA[<a title="'.$images['title'].' in '.$images['albumtitle'].'" href="http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix.'"><img border="0" src="http://'.$_SERVER["HTTP_HOST"].WEBPATH.'/'.ZENFOLDER.'/i.php?a='.$images['folder'].'&i='.$images['filename'].'&w='.$iw.'&h='.$ih.'&cw='.$cw.'&ch='.$ch.'" alt="'. $images['title'] .'"></a>' . $images['desc'] . ']]>';?> <?php if($exif['datetime']) { echo '<![CDATA[Date: ' . $exif['datetime'] . ']]>'; } ?></description>
    <category><?php echo $images['title']; ?></category>
	<guid><?php echo '<![CDATA[http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></guid>
	<pubDate><?php echo $images['date']; ?></pubDate> 
</item>
<?php } ?>
</channel>
</rss>