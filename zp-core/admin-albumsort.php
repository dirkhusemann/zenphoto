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

<div id="main"><?php printTabs('edit'); ?>


<div id="content">

<h1>Sort Album: <?php echo $album->getTitle(); ?></h1>
	<?php
	if (isset($_GET['saved'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Image order saved");
		echo '</h2></div>';
	}
	?>

<div class="tabbox">
	<p class="buttons">
		<?php printAlbumEditLinks('&album='.$album->name, "&laquo; ".gettext("Back"), gettext("Back to the album"));?>
		<button type="submit" title="<?php echo gettext("Save order"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
		<button type="button" title="<?php echo gettext('View Album'); ?>" onclick="javascript:window.location='<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>'" ><img src="images/view.png" alt="" /><strong><?php echo gettext('View Album'); ?></strong></button>
	</p>
	<br clear="all"/>
<p><?php echo gettext("Sort the images by dragging them..."); ?></p>

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
			<button type="button" title="<?php echo gettext('View Album'); ?>" onclick="javascript:window.location='<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>'" ><img src="images/view.png" alt="" /><strong><?php echo gettext('View Album'); ?></strong></button>
			<?php printAlbumEditLinks('&album='.$album->name, "&laquo; ".gettext("Back"), gettext("Back to the album"));?>
			<button type="submit" title="<?php echo gettext("Save order"); ?>"><img	src="images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
			<button type="button" title="<?php echo gettext('View Album'); ?>" onclick="javascript:window.location='<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>'" ><img src="images/view.png" alt="" /><strong><?php echo gettext('View Album'); ?></strong></button>
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
