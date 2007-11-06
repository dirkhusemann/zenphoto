<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';


$albumnr = $_GET[albumnr];
$albumname = $_GET[albumname];

if ($albumname != "")
	{ $albumname = " - for album: ".$_GET[albumname]; }
	
if(getOption('mod_rewrite'))
 { $albumpath = "/"; $imagepath = "/"; }
else
 { $albumpath = "/index.php?album="; $imagepath = "&image="; }

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
<managingEditor><?php echo getOption('admin_email'); ?></managingEditor>
<webMaster><?php echo getOption('admin_email'); ?></webMaster>
<?php 
$iw = $cw = 400; // Image Width
$ih = $ch = 300; // Image Height
$items = 10; // # of Items displayed on the feed

db_connect();

if ($albumnr != "")
	{ $sql = "SELECT * FROM ". prefix("images") ." WHERE albumid = $albumnr AND `show` = 1 ORDER BY id DESC LIMIT ".$items;}
else
 	{ $sql = "SELECT * FROM ". prefix("images") ." WHERE `show` = 1 ORDER BY id DESC LIMIT ".$items; }
 	
$result = mysql_query($sql);

while($r = mysql_fetch_array($result)) {
$id=$r['albumid'];

if ($albumnr != "") 
	{ $sql="SELECT * FROM ". prefix("albums") ." WHERE `show` = 1 AND id = $albumnr"; }
else
	{ $sql="SELECT * FROM ". prefix("albums") ." WHERE `show` = 1 AND id = $id"; }

$album = mysql_query($sql);
$a = mysql_fetch_array($album);
?>
<item>
	<title><?php echo $r['title'] ?></title>
	<link><?php echo '<![CDATA[http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$a['folder'].$imagepath.$r['filename'] . ']]>';?></link>
	<description><?php echo '<![CDATA[<a title="'.$r['title'].' in '.$a['title'].'" href="http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$a['folder'].$imagepath.$r['filename'].'"><img border="0" src="http://'.$_SERVER["HTTP_HOST"].WEBPATH.'/'.ZENFOLDER.'/i.php?a='.$a['folder'].'&i='.$r['filename'].'&w='.$iw.'&h='.$ih.'&cw='.$cw.'&ch='.$ch.'" alt="'. $r['title'] .'"></a>' . $r['desc'] . ']]>';?> <?php if($exif['datetime']) { echo '<![CDATA[Date: ' . $exif['datetime'] . ']]>'; } ?></description>
	<guid><?php echo '<![CDATA[http://'.$_SERVER["HTTP_HOST"].WEBPATH.$albumpath.$a['folder'].$imagepath.$r['filename'] . ']]>';?></guid>
</item>
<?php } ?>
</channel>
</rss>