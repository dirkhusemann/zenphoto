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
<style type="text/css">
.statistic_headline {
	font-weight: bold;
}

.statistic_wrapper tr {
	width: 950px;
	height: 15px;
	padding: 2px;
	border-bottom: 1px solid gray;
}

.statistic_wrapper_gray {
	width: 950px;
	height: 15px;
	padding: 2px;
	background-color: gray;
}

.statistic_counter {
	width: 30px;
	text-align: right;
}

.statistic_title {
	width: 350px;
	height: 15px;
}

.statistic_bargraph {
	height: 15px;
	background-color: darkred;
	margin-right: 0px;
	margin-right: 5px;
	float: left;
}

.statistic_graphwrap {
	width: 450px;
	height: 15px;
}

.statistic_value {
	height: 15px;
	text-align: left;
	float: left;
}

.statistic_link {
	width: 100px;
	height: 15px;
}

.statistic_navlist ul {
	margin-bottom: 10px;
}

.statistic_navlist li {
	display: inline;
}

.statistics_general {
margin-bottom: 15px;
}
.statistics_general li {
display: inline; margin-right: 10px;
}
</style>
<?php
/**
 * Prints a table with a bar graph of the values.
 *
 * @param string $sortorder "popular", "mostrated","toprated","mostcommented" or "mostimages" (only if $type = "albums"!)
 * @param string_type $type "albums" or "images"
 * @param int $limit Number of entries to show
 */
function printBarGraph($sortorder="mostimages",$type="albums",$limit=10) {
	global $gallery, $webpath, $allalbums, $allimages;
	switch ($type) {
		case "albums":
		$typename = gettext("Albums");
		$array = $allalbums;
		break;
		case "images":
		$typename = gettext("Images");
		$array = $allimages;
		break;
	}
	switch($sortorder) {
		case "popular":
			$itemssorted = sortMultiArray($array,"hitcounter","desc",false,false);
			$maxvalue = $itemssorted[0]['hitcounter'];
			$headline = $typename." - ".gettext("most viewed");
			break;
		case "mostrated":
			$itemssorted = sortMultiArray($array,"total_votes","desc",false,false);
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
					$allitems = array();
					foreach ($array as $single) {
						$albumobj = new Album($gallery,$single['folder']);
						$entry = array("title" => $albumobj->getTitle(), "folder" => $albumobj->name,"commentcount" => $albumobj->getCommentCount());
						array_unshift($allitems,$entry);
					}
					break;
				case "images":
					$allitems = array();
					foreach ($array as $image) {
						foreach($allalbums as $album) {
							if($album['id'] === $image['albumid']) {
								$albumobj = new Album($gallery,$album['folder']);
								break;
							}
						}
						$imageobj = new Image($albumobj,$image['filename']);
						$entry = array("albumid" => $imageobj->get("albumid"), "title" => $imageobj->getTitle(), "filename" => $imageobj->filename, "commentcount" => $imageobj->getCommentCount());
						array_unshift($allitems,$entry);
					}
					break;
			}
			$itemssorted = sortMultiArray($allitems,"commentcount","desc",false,false);
			$maxvalue = $itemssorted[0]['commentcount'];
			$headline = $typename." - ".gettext("most commented");
			break;
		case "mostimages":
			$albums = array();
			foreach ($array as $album) {
				$albumobj = new Album($gallery,$album['folder']);
				$albumentry = array("title" => $albumobj->getTitle(), "folder" => $albumobj->name,"imagenumber" => $albumobj->getNumImages());
				array_unshift($albums,$albumentry);
			}
			$itemssorted = sortMultiArray($albums,"imagenumber","desc",false,false);
			$maxvalue = $itemssorted[0]['imagenumber'];
			$headline = $typename." - ".gettext("most images");
			break;
		case "latest":
			switch($type) {
				case "albums":
					$albums = array();
					foreach ($array as $album) {
						$albumobj = new Album($gallery,$album['folder']);
						$albumentry = array("id" => $albumobj->get('id'), "title" => $albumobj->getTitle(), "folder" => $albumobj->name,"imagenumber" => $albumobj->getNumImages());
						array_unshift($albums,$albumentry);
					}
					$itemssorted = sortMultiArray($albums,"imagenumber","desc",false,false); // just to get the one with the most images
					$maxvalue = $itemssorted[0]['imagenumber'];
					$itemssorted = sortMultiArray($albums,"id","desc",false,false); // sort again to get the latest
					$headline = $typename." - ".gettext("latest");
					break;
				case "images":
					$itemssorted = sortMultiArray($array,"id","desc",false,false);
					$barsize = 0;
					$headline = $typename." - ".gettext("latest");
					break;
			}
			break;
	}
	$count = 0;
	$countlines = 0;
	echo "<table class='bordered'>";
	echo "<tr'><th colspan='4' ><strong>".$headline."</strong>";
	if(isset($_GET['stats'])) {
		echo "<a href='gallery_statistics.php'> - ".gettext("Back to the top 10 lists")."</a>";
	} else {
		echo "<a href='gallery_statistics.php?stats=".$sortorder."&amp;type=".$type."'> - ".gettext("View complete list")."</a>";
		echo "<a href='#top'> - ".gettext("top")."</a>";
	}
	
	echo "</th></tr>";
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
				foreach($allalbums as $album) {
					if($item['albumid'] === $album['id']) {
						$albumfolder = $album['folder'];
						break;
					}
				}
				$editurl=  $webpath."/admin.php?page=edit&amp;album=".$albumfolder."&amp;tab=imageinfo";
				$viewurl = WEBPATH."/index.php?album=".$albumfolder."&amp;image=".$name;
				break;
		}
		
		if($sortorder === "latest" AND $type === "images") {
			$value = get_language_string($album['title'])." (".$albumfolder.")"; 
		} 
	
		?>
		<tr class="statistic_wrapper">
		<td class="statistic_counter" <?php echo $style; ?>><?php echo ($count+1); ?></td>
		<td class="statistic_title" <?php echo $style; ?>><strong><?php echo get_language_string($item['title']); ?></strong> (<?php echo truncate_string($name,25); ?>)</td>
		<td class="statistic_graphwrap" <?php echo $style; ?>><div class="statistic_bargraph" style="width: <?php echo $barsize; ?>px"></div><div class="statistic_value"><?php echo $value; ?></div></td>
		<td class="statistic_link" <?php echo $style; ?>>
		<?php
		echo "<a href='".$editurl."' title='".$name."'>Edit</a> | <a href='".$viewurl."' title='".$name."'>View</a></td";
		echo "</tr>";
		$count++; 	
		if($count === $limit) { break; }
	}
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
$allalbums = query_full_array("SELECT * FROM " . prefix('albums'));
$albumcount = $gallery->getNumAlbums(true);
$albumscount_unpub = $albumcount-$gallery->getNumAlbums(true,true);
$allimages = query_full_array("SELECT * FROM " . prefix('images'));
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
<?php 	printBarGraph("popular","albums"); ?>
	
<a name="albums-mostrated"></a>
<?php printBarGraph("mostrated","albums"); ?>
	
<a name="albums-toprated"></a>
<?php printBarGraph("toprated","albums"); ?>

<a name="albums-mostcommented"></a>
<?php printBarGraph("mostcommented","albums"); ?>
	
<?php }

if(isset($_GET['type']) AND $_GET['type'] === "albums") {
	switch ($_GET['stats']) {
		
		case "latest":
			printBarGraph("latest","albums",$albumcount);
			break;
		case "popular":
			printBarGraph("popular","albums",$albumcount);
			break;
		case "mostrated":
			printBarGraph("mostrated","albums",$albumcount);
			break;
		case "toprated":
			printBarGraph("toprated","albums",$albumcount);
			break;
		case "mostcommented":
			printBarGraph("mostcommented","albums",$albumcount);
			break;
		case "mostimages":
			printBarGraph("mostimages","albums",$albumcount);
			break;
	}
}

if(isset($_GET['type']) AND $_GET['type'] === "images") {
	switch ($_GET['stats']) {
		case "popular":
			printBarGraph("popular","images",$imagecount);
			break;
		case "mostrated":
			printBarGraph("mostrated","images",$imagecount);
			break;
		case "toprated":
			printBarGraph("toprated","images",$imagecount);
			break;
		case "mostcommented":
			printBarGraph("mostcommented","images",$imagecount);
			break;
	}
}


?>
</div><!-- content -->
</div><!-- main -->
<?php printAdminFooter(); ?>
</body>
<?php echo "</html>"; ?>