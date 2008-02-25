<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';

$albumnr = sanitize_numeric($_GET['albumnr']);
$albumname = sanitize($_GET['albumname'], true);
$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');

// check passwords
$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
foreach($albumscheck as $albumcheck) {
	if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
		$albumpasswordcheck= " AND albums.id != ".$albumcheck['id'];
		$passwordcheck = $passwordcheck.$albumpasswordcheck;
	} 
}

if ($albumname != "") { $albumname = " - for album: ".$albumname; }
if(getOption('mod_rewrite'))
 { $albumpath = "/"; $imagepath = "/"; $modrewritesuffix = getOption('mod_rewrite_image_suffix'); }
else
 { $albumpath = "/index.php?album="; $imagepath = "&image="; $modrewritesuffix = ""; }

?>
<rss version="2.0">
<channel>
<title><?php echo getOption('gallery_title'); ?><?php echo $albumname; ?></title>
<link><?php echo "http://".$host.WEBPATH; ?></link>
<description><?php echo getOption('gallery_title'); ?></description>
<language>en-us</language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>Acrylian's ZenPhoto Album RSS Generator based on Alenônimo's ZenPhoto RSS Generator which is based on ThinkDreams' Generator</generator>
<?php
	$admin = array_shift(getAdministrators());
	$adminname = $admin['name'];
?>

<managingEditor><?php echo $adminname; ?></managingEditor>
<webMaster><?php echo $adminname; ?></webMaster>
<?php 
$s = 240; // uncropped image size
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
															" WHERE ".$albumWhere." images.albumid = albums.id AND images.show=1 AND albums.show=1 ".
															" AND albums.folder != ''".$passwordcheck.
															" ORDER BY images.id DESC LIMIT ".$items);

foreach ($result as $images) {
	$imagpathnames = explode('/', $images['folder']);
	foreach ($imagpathnames as $key=>$name) {
		$imagpathnames[$key] = rawurlencode($name);
	}
	$images['folder'] = implode('/', $imagpathnames);
	$images['filename'] = rawurlencode($images['filename']);

?>
<item>
	<title><?php echo $images['title']; ?></title>
	<link><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></link>
	<description><?php echo '<![CDATA[<a title="'.$images['title'].' in '.$images['albumtitle'].'" href="http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix.'"><img border="0" src="http://'.$host.WEBPATH.'/'.ZENFOLDER.'/i.php?a='.$images['folder'].'&i='.$images['filename'].'&s='.$s.'" alt="'. $images['title'] .'"></a>' . $images['desc'] . ']]>';?> <?php if($exif['datetime']) { echo '<![CDATA[Date: ' . $exif['datetime'] . ']]>'; } ?></description>
		<category><?php echo $images['title']; ?></category>
	<guid><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></guid>
	<pubDate><?php echo $images['date']; ?></pubDate> 
</item>
<?php } ?>
</channel>
</rss>