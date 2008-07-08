<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', 0);
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';

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

$albumnr = sanitize_numeric($_GET['albumnr']);
$albumname = sanitize(urldecode($_GET['albumname']), true);
$albumfolder = sanitize(urldecode($_GET['folder']), true);
$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');

// check passwords
$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
foreach($albumscheck as $albumcheck) {
	if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
		$albumpasswordcheck= " AND albums.id != ".$albumcheck['id'];
		$passwordcheck = $passwordcheck.$albumpasswordcheck;
	} 
}
if ($albumname != "") { 
	$albumname = " (".$albumname.")"; 
} 
if(getOption('mod_rewrite')) {
	$albumpath = "/"; $imagepath = "/"; $modrewritesuffix = getOption('mod_rewrite_image_suffix');
} else  {
	$albumpath = "/index.php?album="; $imagepath = "&image="; $modrewritesuffix = ""; }

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo htmlspecialchars(getOption('gallery_title')).htmlspecialchars($albumname); ?></title>
<link><?php echo "http://".$host.WEBPATH; ?></link>
<atom:link href="http://<?php echo $host.WEBPATH; ?>/rss.php" rel="self" type="application/rss+xml" />
<description><?php echo htmlspecialchars(getOption('gallery_title')); ?></description>
<language>en-us</language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPhoto RSS Generator</generator>
<?php
	$admin = array_shift(getAdministrators());
	$adminname = $admin['user'];
	$adminemail = $admin['email'];
?>
<managingEditor><?php echo "$adminemail ($adminname)"; ?></managingEditor>
<webMaster><?php echo "$adminemail ($adminname)"; ?></webMaster>
<?php 
$size = sanitize_numeric($_GET['size']);
if(is_numeric($size) && $size != "" && $size < getOption('feed_imagesize')) {
  $s = $size;
} else {
	$s = getOption('feed_imagesize'); // uncropped image size
}
$items = getOption('feed_items'); // # of Items displayed on the feed

db_connect();

if (is_numeric($albumnr) && $albumnr != "") { 
	$albumWhere = "images.albumid = $albumnr AND";
} else if (isset($_GET["folder"])) {
	$albumWhere = "folder LIKE '".$albumfolder."/%' AND "; 
} else {
	$albumWhere = "";
}

$result = query_full_array("SELECT images.albumid, images.date AS date, images.filename AS filename, images.desc, images.title AS title, " .
 														"albums.folder AS folder, albums.title AS albumtitle, images.show, albums.show, albums.password FROM " . 
															prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
															" WHERE ".$albumWhere." images.albumid = albums.id AND images.show=1 AND albums.show=1 ".
															" AND albums.folder != ''".$passwordcheck.
															" ORDER BY images.mtime DESC LIMIT ".$items);

foreach ($result as $images) {
	$imagpathnames = explode('/', $images['folder']);
	foreach ($imagpathnames as $key=>$name) {
		$imagpathnames[$key] = rawurlencode($name);
	}
	$images['folder'] = implode('/', $imagpathnames);
	$images['filename'] = rawurlencode($images['filename']);
	$ext = strtolower(strrchr($images['filename'], "."));
	$images['title'] = htmlspecialchars($images['title'], ENT_QUOTES);
	$images['albumtitle'] = htmlspecialchars($images['albumtitle'], ENT_QUOTES);
	$images['desc'] = htmlspecialchars($images['desc'], ENT_QUOTES);
?>
<item>
	<title><?php echo $images['title']." (".$images['albumtitle'].")"; ?></title>
	<link><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></link>
	<description>
<?php
if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4") ||  ($ext == ".3gp") ||  ($ext == ".mov")) {
	echo '<![CDATA[<a title="'.$images['title'].' in '.$images['albumtitle'].'" href="http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix.'">'. $images['title'] .$ext.'</a><p>' . $images['desc'] . '</p>]]>';
} else {
	echo '<![CDATA[<a title="'.$images['title'].' in '.$images['albumtitle'].'" href="http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix.'"><img border="0" src="http://'.$host.WEBPATH.'/'.ZENFOLDER.'/i.php?a='.$images['folder'].'&i='.$images['filename'].'&s='.$s.'" alt="'. $images['title'] .'"></a><p>' . $images['desc'] . '</p>]]>';?>
	<?php if($exif['datetime']) { echo '<![CDATA[Date: ' . $exif['datetime'] . ']]>'; } }?>
</description>
<category><?php echo $images['albumtitle']; ?></category>
	<guid><?php echo '<![CDATA[http://'.$host.WEBPATH.$albumpath.$images['folder'].$imagepath.$images['filename'].$modrewritesuffix. ']]>';?></guid>
	<pubDate><?php echo fixRSSDate($images['date']); ?></pubDate> 
</item>
<?php } ?>
</channel>
</rss>