<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/class-sortable.php');
$_zp_sortable_list = new jQuerySortable('js');
// $_zp_sortable_list->debug(); // Uncomment this line to display serialized object

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!zp_loggedin()) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

// Insert the POST operation handler
zenSortablesPostHandler($_zp_sortable_list, 'imageOrder', 'images', 'images');
// Print the admin header
printAdminHeader();
// Print the sortable stuff
zenSortablesHeader($_zp_sortable_list, 'images','imageOrder','img', "placeholder:'zensortable_img'");
echo "\n</head>";
?>


<body>

<?php

// Create our gallery
$gallery = new Gallery();
// Create our album
if (!isset($_GET['album'])) {
	die(gettext("No album provided to sort."));
} else {
	$folder = sanitize($_GET['album']);
	if (!isMyAlbum($folder, ALBUM_RIGHTS)) {
		die(gettext("You do not have rights to sort this album"));
	}
	$album = new Album($gallery, $folder);
	if (isset($_GET['saved'])) {
		$album->setSortType("manual");
		$album->setSortDirection('image', 0);
		$album->save();
	}

	// Layout the page
	printLogoAndLinks();
	?>

<div id="main">
<?php printTabs('edit'); ?>


<div id="content">
<?php
if($album->getParent()) {
	$link = getAlbumBreadcrumbAdmin($album);
} else {
	$link = '';
}
$alb = removeParentAlbumNames($album);
?>
<h1><?php printf(gettext('Edit Album: <em>%1$s%2$s</em>'),  $link, $alb); ?></h1>
<?php
if (isset($_GET['saved'])) {
	echo '<div class="messagebox" id="fade-message">';
	echo  "<h2>".gettext("Image order saved");
	echo '</h2></div>';
}
$images = $album->getImages();
setAlbumSubtabs($album);
$subtab = printSubtabs('edit', 'sort');

$parent = dirname($album->name);
if ($parent == '/' || $parent == '.' || empty($parent)) {
	$parent = '';
} else {
	$parent = '&album='.$parent.'&tab=subalbuminfo';
}
?>

<div class="tabbox">
	<p class="buttons">
		<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
		<button type="submit" title="<?php echo gettext("Save order"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
		<a href="<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>" title="<?php echo gettext('View Album'); ?>" ><img src="images/view.png" alt="" /><strong><?php echo gettext('View Album'); ?></strong></a>
	</p>
	<br clear="all"/>
<p><?php echo gettext("Set the image order by dragging them to the positions you desire."); ?></p>

<div id="images"><?php
$images = $album->getImages();
foreach ($images as $image) {
	adminPrintImageThumb(newImage($album, $image));
}
?></div>
<br>

<div>
	<form action="?page=edit&album=<?php echo $album->getFolder(); ?>&saved" method="post" name="sortableListForm" id="sortableListForm">
		<?php $_zp_sortable_list->printHiddenInputs();?>
		<input type="hidden" name="sortableListsSubmitted" value="true">
		<p class="buttons">
			<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
			<button type="submit" title="<?php echo gettext("Save order"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
			<a href="<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>" title="<?php echo gettext('View Album'); ?>" ><img src="images/view.png" alt="" /><strong><?php echo gettext('View Album'); ?></strong></a>
		</p>
	</form>
	<br clear="all"/>

</div>

</div>

</div>

<?php printAdminFooter(); ?>
</div>

<?php 
}
?>

</body>

<?php
echo "\n</html>";


?>
