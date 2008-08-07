<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */
define('OFFSET_PATH', 1);
require_once('admin-functions.php');
require_once("admin-sortable.php");
if (!zp_loggedin()) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

// Insert the POST operation handler
zenSortablesPostHandler('imageOrder', 'images', 'images');
// Print the admin header
printAdminHeader();
// Print the sortable stuff
zenSortablesHeader('images','imageOrder','img',"overlap:'horizontal',constraint:false");

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
	$folder = strip($_GET['album']);
	if (!isMyAlbum($folder, EDIT_RIGHTS)) {
		die(gettext("You do not have rights to sort this album"));
	}
	$album = new Album($gallery, $folder);
	if (isset($_GET['saved'])) {
		$album->setSortType("Manual");
		$album->setSortDirection('image', 0);
		$album->save();
	}

	// Layout the page
	printLogoAndLinks();
	?>

<div id="main"><?php printTabs('edit'); ?>


<div id="content">

<h1>Sort Album: <?php echo $album->getTitle(); ?></h1>
<p><?php printAdminLinks('edit', "&laquo; ".gettext("back to the album list"), gettext("Back to the list of albums"));?> 
| <?php printAdminLinks("edit&album=". urlencode( ($album->getFolder()) ), gettext("edit album"), gettext("Edit Album")); ?> 
| <?php printViewLink($album, gettext("view album"), gettext("View Album")); ?>
</p>
	<?php
	if (isset($_GET['saved'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Image order saved");
		echo '</h2></div>';
	}
	?>

<div class="box" style="padding: 15px;">

<p><?php echo gettext("Sort the images by dragging them..."); ?></p>

<div id="images"><?php
$images = $album->getImages();
foreach ($images as $image) {
	adminPrintImageThumb(new Image($album, $image));
}
?></div>
<br>

<div><?php
zenSortablesSaveButton("?page=edit&album=". $album->getFolder() . "&saved");
?></div>

</div>

</div>

<?php printAdminFooter(); ?>
</div>

<?php 
	zenSortablesFooter(); 
}
?>

</body>

<?php
echo "\n</html>";


?>