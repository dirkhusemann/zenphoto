<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!zp_loggedin()) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

// Print the admin header
printAdminHeader();

?>
<script language="javascript" type="text/javascript">
	$(function() {
		$('#images').sortable();
	});
</script>
<?php
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
		parse_str($_POST['sortableList'],$inputArray);
		if (isset($inputArray['id'])) {
			$orderArray = $inputArray['id'];
			foreach($orderArray as $key=>$id) {
				$sql = 'UPDATE '.prefix('images').' SET `sort_order`="'.sprintf('%03u',$key).'" WHERE `id`='.$id;
				query($sql);
			}

		}
		
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
			$parent = '&amp;album='.$parent.'&amp;tab=subalbuminfo';
		}
		?>
		
		<div class="tabbox">
			<form action="?page=edit&amp;album=<?php echo $album->getFolder(); ?>&amp;saved&amp;tab=sort" method="post" name="sortableListForm" id="sortableListForm">
				<script language="javascript" type="text/javascript">
					function postSort(form) {
						$('#sortableList').val($('#images').sortable('serialize'));
						form.submit();
					}
				</script>
			
				<p class="buttons">
					<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
					<button type="button" title="<?php echo gettext("Save order"); ?>" onclick="postSort(this.form);" >
					<img	src="images/pass.png" alt="" />
					<strong><?php echo gettext("Save"); ?></strong>
					</button>
					<button type="button" title="<?php echo gettext('View Album'); ?>" onclick="window.location='<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>'" >
					<img src="images/view.png" alt="" />
					<strong><?php echo gettext('View Album'); ?></strong>
					</button>
				</p>
				<br clear="all"/><br />
				<p><?php echo gettext("Set the image order by dragging them to the positions you desire."); ?></p>
			
				<div id="images">
					<?php
					$images = $album->getImages();
					foreach ($images as $image) {
						adminPrintImageThumb(newImage($album, $image));
					}
					?>
				</div>
				<br />
			
				<div>
					<script language="javascript" type="text/javascript">
						function saveOrder() {
						}
					</script>
					<input type="hidden" id="sortableList" name="sortableList" value="" />
					<p class="buttons">
						<button type="button" title="<?php echo gettext('Back to the album list'); ?>" onclick="window.location='<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>'" >
						<img	src="images/arrow_left_blue_round.png" alt="" />
						<strong><?php echo gettext("Back"); ?></strong>
						</button>
						<button type="button" title="<?php echo gettext("Save order"); ?>" onclick="postSort(this.form);" >
						<img	src="images/pass.png" alt="" />
						<strong><?php echo gettext("Save"); ?></strong>
						</button>
						<button type="button" title="<?php echo gettext('View Album'); ?>" onclick="window.location='<?php echo WEBPATH . "/index.php?album=". urlencode($album->getFolder()); ?>'" >
						<img src="images/view.png" alt="" />
						<strong><?php echo gettext('View Album'); ?></strong>
						</button>
					</p>
					</div>
			</form>
			<br clear="all"/>
		
		</div>
		
		</div>
	
	</div>
	
	<?php
	printAdminFooter();
}
?>

</body>

<?php
echo "\n</html>";


?>
