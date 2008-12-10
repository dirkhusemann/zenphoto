<?php
/**
 * Detailed Gallery Statistics
 * 
 * This plugin shows statistical graphs and info about your gallery\'s images and albums
 * 
 * @package admin
 */

$button_text = gettext('Gallery Statistics');
$button_hint = gettext('Shows statistical graphs and info about your gallery\'s images and albums.');
$button_icon = 'images/bar_graph.png';

define('OFFSET_PATH', 3);
define('RECORD_SEPARATOR', ':****:');
define('TABLE_SEPARATOR', '::');
define('RESPOND_COUNTER', 1000);
chdir(dirname(dirname(__FILE__)));

require_once(dirname(dirname(__FILE__)).'/template-functions.php');
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');


$gallery = new Gallery();
$webpath = WEBPATH.'/'.ZENFOLDER.'/';

printAdminHeader($webpath);
?>
<link rel="stylesheet" href="gallery_statistics.css" type="text/css" />
<?php
/**
 * Prints a table with a bar graph of the values.
 *
 * @param string $sortorder "popular", "mostrated","toprated","mostcommented" or "mostimages" (only if $type = "albums"!)
 * @param string_type $type "albums" or "images"
 * @param int $limit Number of entries to show
 */
function printBarGraph($sortorder="mostimages",$type="albums",$limit=10) {
	global $gallery, $webpath;
	switch ($type) {
		case "albums":
		$typename = gettext("Albums");
		$dbquery = "SELECT id, title, folder, hitcounter, total_votes, total_value FROM ".prefix('albums');
		break;
		case "images":
		$typename = gettext("Images");
		$dbquery = "SELECT id, title, filename, albumid, hitcounter, total_votes, total_value FROM ".prefix('images');
		break;
	}
	switch($sortorder) {
		case "popular":
			$itemssorted = query_full_array($dbquery." ORDER BY hitcounter DESC LIMIT ".$limit);
			$maxvalue = $itemssorted[0]['hitcounter'];
			$headline = $typename." - ".gettext("most viewed");
			break;
		case "mostrated":
			$itemssorted = query_full_array($dbquery." ORDER BY total_votes DESC LIMIT ".$limit);
			$maxvalue = $itemssorted[0]['total_votes'];
			$headline = $typename." - ".gettext("most rated");
			break;
		case "toprated":
			switch($type) {
				case "albums":
					$itemssorted = query_full_array("SELECT * FROM " . prefix('albums') ." ORDER BY (total_value/total_votes) DESC LIMIT $limit");
					break;
				case "images":
					$itemssorted = query_full_array("SELECT * FROM " . prefix('images') ." ORDER BY (total_value/total_votes) DESC LIMIT $limit");
					break;
			}
			if($itemssorted[0]['total_votes'] != 0) {
				$maxvalue = ($itemssorted[0]['total_value'] / $itemssorted[0]['total_votes']);
			} else {
				$maxvalue = 0;
			}
			$headline = $typename." - ".gettext("top rated");
			break;
		case "mostcommented":
			switch($type) {
				case "albums":
					$itemssorted = query_full_array("SELECT comments.ownerid, count(*) as commentcount, albums.* FROM ".prefix('comments')." AS comments, ".prefix('albums')." AS albums WHERE albums.id=comments.ownerid AND type = 'albums' GROUP BY comments.ownerid ORDER BY commentcount DESC LIMIT ".$limit); 
					break;
				case "images":
					$itemssorted = query_full_array("SELECT comments.ownerid, count(*) as commentcount, images.* FROM ".prefix('comments')." AS comments, ".prefix('images')." AS images WHERE images.id=comments.ownerid AND type = 'images' GROUP BY comments.ownerid ORDER BY commentcount DESC LIMIT ".$limit); 
					break;
			}
			$maxvalue = $itemssorted[0]['commentcount'];
			$headline = $typename." - ".gettext("most commented");
			break;
		case "mostimages":
			$itemssorted = query_full_array("SELECT images.albumid, count(*) as imagenumber, albums.* FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums WHERE albums.id=images.albumid GROUP BY images.albumid ORDER BY imagenumber DESC LIMIT ".$limit); 
			$maxvalue = $itemssorted[0]['imagenumber'];
			$headline = $typename." - ".gettext("most images");
			break;
		case "latest":
			switch($type) {
				case "albums":
					$allalbums = query_full_array($dbquery." ORDER BY id DESC LIMIT ".$limit);
					$albums = array();
					foreach ($allalbums as $album) {
						$albumobj = new Album($gallery,$album['folder']);
						$albumentry = array("id" => $albumobj->get('id'), "title" => $albumobj->getTitle(), "folder" => $albumobj->name,"imagenumber" => $albumobj->getNumImages());
						array_unshift($albums,$albumentry);
					}
					$itemssorted = sortMultiArray($albums,"imagenumber","desc",false,false); // just to get the one with the most images
					$maxvalue = $itemssorted[0]['imagenumber'];
					$itemssorted = $albums; // The items are originally sorted by id;
					$headline = $typename." - ".gettext("latest");
					break;
				case "images":
					$itemssorted = query_full_array($dbquery." ORDER BY id DESC LIMIT ".$limit);
					$barsize = 0;
					$maxvalue = 1;
					$headline = $typename." - ".gettext("latest");
					break;
			}
			break;
	}
	if($maxvalue == 0) {
		$maxvalue = 1;
		$no_statistic_message = "<tr><td><em>".gettext("No statistic available")."</em></td></tr>";
	} else {
		$no_statistic_message = "";
	}
	$count = 0;
	$countlines = 0;
	echo "<table class='bordered'>";
	echo "<tr'><th colspan='4' ><strong>".$headline."</strong>";
	if(isset($_GET['stats'])) {
		echo "<a href='gallery_statistics.php'> | ".gettext("Back to the top 10 lists")."</a>";
	} else {
		echo "<a href='gallery_statistics.php?stats=".$sortorder."&amp;type=".$type."'> | ".gettext("View complete list")."</a>";
		echo "<a href='#top'> | ".gettext("top")."</a>";
	}
	
	echo "</th></tr>";
	echo $no_statistic_message;
	foreach ($itemssorted as $item) {
		if(array_key_exists("filename",$item)) {
			$name = $item['filename'];
		} else if(array_key_exists("folder",$item)){
			$name = $item['folder'];
		}
		switch($sortorder) {
			case "popular":
				$barsize = round($item['hitcounter'] / $maxvalue * 400);
				$value = $item['hitcounter'];
				break;
			case "mostrated":
				if($item['total_votes'] != 0) {
					$barsize = round($item['total_votes'] / $maxvalue * 400);
				} else {
					$barsize = 0;
				}
				$value = $item['total_votes'];
				break;
			case "toprated":
				if($item['total_votes'] != 0) {
					$barsize = round(($item['total_value'] / $item['total_votes']) / $maxvalue * 400);
					$value = round($item['total_value'] / $item['total_votes']);
				} else {
					$barsize = 0;
					$value = 0;
				}
				break;
			case "mostcommented":
				if($maxvalue != 0) {
					$barsize = round($item['commentcount'] / $maxvalue * 400);
				} else {
					$barsize = 0;
				}
				$value = $item['commentcount'];
				break;
			case "mostimages":
				$barsize = round($item['imagenumber'] / $maxvalue * 400);
				$value = $item['imagenumber'];
				break;
			case "latest":
				switch($type) {
					case "albums":
						$barsize = round($item['imagenumber'] / $maxvalue * 400);
						$value = $item['imagenumber']; 
						break;
					case "images":
						$barsize = 0;
						$value = "";
						break;
				}
				break;		
		}
		// counter to have a gray background of every second line
		if($countlines === 1) {
			$style = "style='background-color: #f4f4f4'";	// a little ugly but the already attached class for the table is so easiest overriden...	
			$countlines = 0;
		} else {
			$style = "";
			$countlines++;
		}
		switch($type) {
			case "albums":
				$editurl=  $webpath."/admin.php?page=edit&amp;album=".$name;
				$viewurl = WEBPATH."/index.php?album=".$name;
				break;
			case "images":
				$getalbumfolder = query_single_row("SELECT title, folder from ".prefix("albums"). " WHERE id = ".$item['albumid']);
				if($sortorder === "latest") {
					$value = get_language_string($getalbumfolder['title'])." (".$getalbumfolder['folder'].")"; 
				} 
				$editurl=  $webpath."/admin.php?page=edit&amp;album=".$getalbumfolder['folder']."&amp;image=".$item['filename']."&amp;tab=imageinfo#IT";
				$viewurl = WEBPATH."/index.php?album=".$getalbumfolder['folder']."&amp;image=".$name;
				break;
		}
		if($value != 0 OR $sortorder === "latest") {
		?>
		<tr class="statistic_wrapper">
		<td class="statistic_counter" <?php echo $style; ?>><?php echo ($count+1); ?></td>
		<td class="statistic_title" <?php echo $style; ?>><strong><?php echo get_language_string($item['title']); ?></strong> (<?php echo $name; ?>)</td>
		<td class="statistic_graphwrap" <?php echo $style; ?>><div class="statistic_bargraph" style="width: <?php echo $barsize; ?>px"></div><div class="statistic_value"><?php echo $value; ?></div></td>
		<td class="statistic_link" <?php echo $style; ?>>
		<?php
		echo "<a href='".$editurl."' title='".$name."'>Edit</a> | <a href='".$viewurl."' title='".$name."'>View</a></td";
		echo "</tr>";
		$count++; 	
		if($count === $limit) { break; }
		} 
	} // foreach end
	echo "</table><br />";
}
echo '</head>';
?>

<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<a name="top"></a>
<?php printTabs('database'); 

// getting arrays of all image and albums
$albumcount = $gallery->getNumAlbums(true);
$albumscount_unpub = $albumcount-$gallery->getNumAlbums(true,true);
$imagecount = $gallery->getNumImages();
$imagecount_unpub = $imagecount-$gallery->getNumImages(true);
?>
<div id="content">
<h1><?php echo gettext("Gallery Statistics"); ?></h1>
<ul class="statistics_general"><li>
<?php 
if ($imagecount_unpub > 0) {
	printf(gettext('<strong>%1$u</strong> images (%2$u not visible)'),$imagecount, $imagecount_unpub);
} else {
	printf(gettext('<strong>%u</strong> images'),$imagecount);
}
?>
</li><li>
<?php
if ($albumscount_unpub > 0) {
	printf(gettext('<strong>%1$u</strong> albums (%2$u not published)'),$albumcount, $albumscount_unpub);
} else {
	printf(gettext('<strong>%u</strong> albums'),$albumcount);
}
?>
</li>
<li>
<?php
$commentcount = $gallery->getNumComments(true);
$commentcount_mod = $commentcount - $gallery->getNumComments(false);
if ($commentcount_mod > 0) {
	if ($commentcount != 1) {
		printf(gettext('<strong>%1$u</strong> comments (%2$u in moderation)'),$commentcount, $commentcount_mod);
	} else {
		printf(gettext('<strong>1</strong> comment (%u in moderation)'), $commentcount_mod);
	}
} else {
	if ($commentcount != 1) {
		printf(gettext('<strong>%u</strong> comments'),$commentcount);
	} else {
		echo gettext('<strong>1</strong> comment');
	}
}
?>
</li></ul>
<?php
if(!isset($_GET['stats']) AND !isset($_GET['fulllist'])) {
	?>
	<ul class="statistic_navlist">
		<li><strong>Images</strong>
		<ul>
				<li><a href="#images-latest"><?php echo gettext("latest"); ?></a> | </li>
				<li><a href="#images-popular"><?php echo gettext("most viewed"); ?></a> | </li>
				<li><a href="#images-mostrated"><?php echo gettext("most rated"); ?></a> | </li>
				<li><a href="#images-toprated"><?php echo gettext("top rated"); ?></a> | </li>
				<li><a href="#images-mostcommented"><?php echo gettext("most commented"); ?></a></li>
		</ul>
		</li>
		<li><strong>Albums</strong>
		<ul>
				<li><a href="#albums-latest"><?php echo gettext("latest"); ?></a> | </li>
				<li><a href="#albums-mostimages"><?php echo gettext("most images"); ?></a> | </li>
				<li><a href="#albums-popular"><?php echo gettext("most viewed"); ?></a> | </li>
				<li><a href="#albums-mostrated"><?php echo gettext("most rated"); ?></a> | </li>
				<li><a href="#albums-toprated"><?php echo gettext("top rated"); ?></a> | </li>
				<li><a href="#albums-mostcommented"><?php echo gettext("most commented"); ?></a></li>
		</ul>
		</li>
</ul>
<br style="clear:both" />

<a name="images-latest"></a>
<?php printBarGraph("latest","images"); ?>
	
<a name="images-popular"></a>
<?php printBarGraph("popular","images"); ?>
  
<a name="images-mostrated"></a>
<?php printBarGraph("mostrated","images"); ?>
  
<a name="images-toprated"></a>
<?php printBarGraph("toprated","images"); ?>
  
<a name="images-mostcommented"></a>
<?php printBarGraph("mostcommented","images"); ?>

<a name="albums-latest"></a>
<?php printBarGraph("latest","albums"); ?>

<a name="albums-mostimages"></a>
<?php printBarGraph("mostimages","albums"); ?>

<a name="albums-popular"></a>
<?php printBarGraph("popular","albums"); ?>
	
<a name="albums-mostrated"></a>
<?php printBarGraph("mostrated","albums"); ?>
	
<a name="albums-toprated"></a>
<?php printBarGraph("toprated","albums"); ?>

<a name="albums-mostcommented"></a>
<?php printBarGraph("mostcommented","albums"); ?>
	
<?php }

//Default limit for full list to not overload
if(!isset($_GET['number'])) {
	$limit = 50;
} else {
	$limit = sanitize_numeric($_GET['number']);
}

// if a full list is requested
if(isset($_GET['type'])) {
	?>
		<form name="limit" id="limit" action="gallery_statistics.php"><label
				for="number"><?php echo gettext("Type in the number of items to show: "); ?></label><input
				type="text" size="10" id="number" name="number" value="<?php echo $limit; ?>" /> <input
				type="hidden" name="stats"
				value="<?php echo sanitize($_GET['stats']); ?>" /> <input
				type="hidden" name="type"
				value="<?php echo sanitize($_GET['type']); ?>" /> <input
				type="submit" value="<?php echo gettext("Show"); ?>" /></form>
		<br />
		<?php
		switch ($_GET['type']) {
			case "albums":
				switch ($_GET['stats']) {
					case "latest":
						printBarGraph("latest","albums",$limit);
						break;
					case "popular":
						printBarGraph("popular","albums",$limit);
						break;
					case "mostrated":
						printBarGraph("mostrated","albums",$limit);
						break;
					case "toprated":
						printBarGraph("toprated","albums",$limit);
						break;
					case "mostcommented":
						printBarGraph("mostcommented","albums",$limit);
						break;
					case "mostimages":
						printBarGraph("mostimages","albums",$limit);
						break;
				}
				break;
					case "images":

						switch ($_GET['stats']) {
							case "latest":
								printBarGraph("latest","images",$limit);
								break;
							case "popular":
								printBarGraph("popular","images",$limit);
								break;
							case "mostrated":
								printBarGraph("mostrated","images",$limit);
								break;
							case "toprated":
								printBarGraph("toprated","images",$limit);
								break;
							case "mostcommented":
								printBarGraph("mostcommented","images",$limit);
								break;
						}
						break;
		} // main switch end
		echo "<a href='#top'>".gettext("Back to top")."</a>";
} // main if end

?>
</div><!-- content -->
</div><!-- main -->
<?php printAdminFooter(); ?>
</body>
<?php echo "</html>"; ?>