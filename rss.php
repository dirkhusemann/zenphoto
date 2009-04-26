<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', 0);
header('Content-Type: application/xml');
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER .PLUGIN_FOLDER . "image_album_statistics.php");

// rssmode to differ between images and albums rss
if(isset($_GET['albumsmode'])) {
	$rssmode = "albums";
} else {
	$rssmode = "";
}

if(isset($_GET['albumname'])) {
	$albumfolder = sanitize_path($_GET['albumname']);
	$collection = FALSE;
} else if(isset($_GET['folder'])) {
	$albumfolder = sanitize_path($_GET['folder']);
	$collection = TRUE;
} else {
	$albumfolder = NULL;
	$collection = FALSE;
}

if(isset($_GET['lang'])) {
	$locale = sanitize($_GET['lang']);
} else {
	$locale = getOption('locale');
}

$validlocale = strtr($locale,"_","-"); // for the <language> tag of the rss
$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');

if(isset($_GET['albumtitle'])) {
	$albumname = " (".sanitize(urldecode($_GET['albumtitle'])).")";
} else if ($rssmode === "albums") {
	$albumname = gettext("Latest Albums");
} else {
	$albumname = "";
}

if(getOption('mod_rewrite')) {
	$albumpath = "/"; $imagepath = "/";
	$modrewritesuffix = getOption('mod_rewrite_image_suffix');
} else  {
	$albumpath = "/index.php?album=";
	$imagepath = "&amp;image=";
	$modrewritesuffix = ""; }

	if(isset($_GET['size'])) {
		$size = sanitize_numeric($_GET['size']);
	} else {
		$size = NULL;
	}
	if(is_numeric($size) && !is_null($size) && $size < getOption('feed_imagesize')) {
		$size = $size;
	} else {
		if($rssmode == "albums") {
			$size = getOption('feed_imagesize_albums'); // uncropped image size
		} else {
			$size = getOption('feed_imagesize'); // uncropped image size
		}
	}
	$items = getOption('feed_items'); // # of Items displayed on the feed

	?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title><?php echo strip_tags(get_language_string(getOption('gallery_title'), $locale)).' '.strip_tags($albumname); ?></title>
<link><?php echo "http://".$host.WEBPATH; ?></link>
<atom:link href="http://<?php echo $host.WEBPATH; ?>/rss.php" rel="self"	type="application/rss+xml" />
<description><?php echo strip_tags(get_language_string(getOption('gallery_title'), $locale)); ?></description>
<language><?php echo $validlocale; ?></language>
<pubDate><?php echo date("r", time()); ?></pubDate>
<lastBuildDate><?php echo date("r", time()); ?></lastBuildDate>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<generator>ZenPhoto RSS Generator</generator>
	<?php
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$adminname = $admin['user'];
	$adminemail = $admin['email'];
	?>
<managingEditor><?php echo "$adminemail ($adminname)"; ?></managingEditor>
<webMaster>	<?php echo "$adminemail ($adminname)"; ?></webMaster>
	<?php
	if ($rssmode == "albums") {
		$result = getAlbumStatistic($items,getOption("feed_sortorder_albums"));
	} else {
		$result = getImageStatistic($items,getOption("feed_sortorder"),$albumfolder,$collection);
	}
	foreach ($result as $item) {
		if($rssmode != "albums") {
			$ext = strtolower(strrchr($item->filename, "."));
			$albumobj = $item->getAlbum();
			$itemlink = $host.WEBPATH.$albumpath.urlencode($albumobj->name).$imagepath.urlencode($item->filename).$modrewritesuffix;
			$fullimagelink = $host.WEBPATH."/albums/".$albumobj->name."/".$item->filename;
			$thumburl = '<img border="0" src="http://'.$host.$item->getCustomImage($size, NULL, NULL, NULL, NULL, NULL, NULL, TRUE).'" alt="'.get_language_string($item->get("title"),$locale) .'" />';
			$itemcontent = '<![CDATA[<a title="'.get_language_string($item->get("title"),$locale).' in '.get_language_string($albumobj->get("title"),$locale).'" href="http://'.$itemlink.'">'.$thumburl.'</a><p>' . get_language_string($item->get("desc"),$locale) . '</p>]]>';
			$videocontent = '<![CDATA[<a title="'.get_language_string($item->get("title"),$locale).' in '.$albumobj->getTitle().'" href="http://'.$itemlink.'">'. $item->filename.'</a><p>' . get_language_string($item->get("desc"),$locale) . '</p>]]>';
			$datecontent = '<![CDATA[Date: '.zpFormattedDate(getOption('date_format'),$item->get('mtime')).']]>';
		} else {
			$galleryobj = new Gallery();
			$albumitem = new Album($galleryobj, $item['folder']);
			$totalimages = $albumitem->getNumImages();
			$itemlink = $host.WEBPATH.$albumpath.$albumitem->name;
			$albumthumb = $albumitem->getAlbumThumbImage();
			$thumb = newImage($albumitem, $albumthumb->filename);
			$thumburl = '<img border="0" src="'.$thumb->getCustomImage($size, NULL, NULL, NULL, NULL, NULL, NULL, TRUE).'" alt="'.get_language_string($albumitem->get("title"),$locale) .'" />';
			$title =  get_language_string($albumitem->get("title"),$locale);
			if(true || getOption("feed_sortorder_albums") === "latestupdated") {
				$filechangedate = filectime(getAlbumFolder().internalToFilesystem($albumitem->name));
				$latestimage = query_single_row("SELECT mtime FROM " . prefix('images'). " WHERE albumid = ".$albumitem->getAlbumID() . " AND `show` = 1 ORDER BY id DESC");
				$lastuploaded = query("SELECT COUNT(*) FROM ".prefix('images')." WHERE albumid = ".$albumitem->getAlbumID() . " AND mtime = ". $latestimage['mtime']);
				$row = mysql_fetch_row($lastuploaded);
				$count = $row[0];
				if($count == 1) {
					$imagenumber = sprintf(gettext('(%s: 1 new image)'),$title);
				} else {
					$imagenumber = sprintf(gettext('(%1$s: %2$s new images)'),$title,$count);
				}
				$itemcontent = '<![CDATA[<a title="'.$title.'" href="http://'.$itemlink.'">'.$thumburl.'</a>'.
						'<p><br />'.$imagenumber.'</p>'.
						'<p>'.get_language_string($albumitem->get("desc"),$locale).'</p>]]>';
				$datecontent = '<![CDATA['.sprintf(gettext("Last update: %s"),zpFormattedDate(getOption('date_format'),$filechangedate)).']]>';
			} else {
				if($totalimages == 1) {
					$imagenumber = sprintf(gettext('(%s: 1 image)'),$title);
				} else {
					$imagenumber = sprintf(gettext('(%1$s: %2$s images)'),$title,$totalimages);
				}
				$itemcontent = '<![CDATA[<a title="'.$title.'" href="http://'.$itemlink.'">'.$thumburl.'</a>'.
						'<p>'.get_language_string($albumitem->get("desc"),$locale).'</p>]]>';
				$datecontent = '<![CDATA['.sprintf(gettext("Date: %s"),zpFormattedDate(getOption('date_format'),$albumitem->get('mtime'))).']]>';
			}
			$ext = strtolower(strrchr($thumb->filename, "."));
		}
		switch($ext) {
			case  ".flv":
				$mimetype = "video/x-flv";
				break;
			case ".mp3":
				$mimetype = "audio/mpeg";
				break;
			case ".mp4":
				$mimetype = "video/mpeg";
				break;
			case ".3gp":
				$mimetype = "video/3gpp";
				break;
			case ".mov":
				$mimetype = "video/quicktime";
				break;
			case ".jpg":
			case ".jpeg":
				$mimetype = "image/jpeg";
				break;
			case ".gif":
				$mimetype = "image/gif";
				break;
			case ".png":
				$mimetype = "image/png";
				break;
			default:
				$mimetype = "image/jpeg";
				break;
		}
		?>
<item>
<title><?php 
if($rssmode != "albums") {
	printf('%1$s (%2$s)', get_language_string($item->get("title"),$locale), get_language_string($albumobj->get("title"),$locale));
} else {
	echo $imagenumber;
}
?></title>
<link>
<?php echo '<![CDATA[http://'.$itemlink. ']]>';?>
</link>
<description>
<?php
if ((($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4") ||  ($ext == ".3gp") ||  ($ext == ".mov")) AND $rssmode != "album") {
	echo $videocontent;
} else {
	echo $itemcontent; 
} ?>
<?php  echo $datecontent; ?>
</description>
<?php // enables download of embeded content like images or movies in some rss clients. just for testing, shall become a real option
if(getOption("feed_enclosure") AND $rssmode != "albums") { ?>
<enclosure url="<?php echo $fullimagelink; ?>" type="<?php echo $mimetype; ?>" />
<?php  } ?>
<category>
	<?php
	if($rssmode != "albums") {
		echo get_language_string($albumobj->get("title"),$locale);
	} else {
		echo get_language_string($albumitem->get("title"),$locale);
	} ?>
</category>
<?php if(getOption("feed_mediarss") AND $rssmode != "albums") { ?>
<media:content url="<?php echo $fullimagelink; ?>" type="image/jpeg" />
<media:thumbnail url="<?php echo $fullimagelink; ?>" width="<?php echo $size; ?>"	height="<?php echo $size; ?>" />
<?php } ?>
<guid><?php echo '<![CDATA[http://'.$itemlink.']]>';?></guid>
<pubDate>
	<?php
	if($rssmode != "albums") {
		echo date("r",strtotime($item->get('date')));
	} else {
		echo date("r",strtotime($albumitem->get('date')));
	}
	?>
</pubDate>
</item>
<?php } ?>
</channel>
</rss>
