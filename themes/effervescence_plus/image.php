<?php
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo getBareAlbumTitle();?> | <?php echo getBareImageTitle();?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/colorbox/colorbox.css" type="text/css" />
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/colorbox/jquery.colorbox-min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$(".colorbox").colorbox({inline:true, href:"#imagemetadata"});
		});
	</script>
</head>

<body onload="blurAnchors()">

	<!-- Wrap Everything -->
	<div id="main4">
		<div id="main2">

			<!-- Wrap Header -->
			<div id="galleryheader">
				<div id="gallerytitle">

					<!-- Image Navigation -->
					<div class="imgnav">
						<div class="imgprevious">
							<?php
								global $_zp_current_image;
								if (hasPrevImage()) {
									$image = $_zp_current_image->getPrevImage();
									echo '<a href="' . htmlspecialchars(getPrevImageURL()) . '" title="' . html_encode($image->getTitle()) . '">&laquo; '.gettext('prev').'</a>';
								} else {
									echo '<div class="imgdisabledlink">&laquo; '.gettext('prev').'</div>';
								}
							?>
						</div>
						<div class="imgnext">
							<?php
								if (hasNextImage()) {
									$image = $_zp_current_image->getNextImage();
									echo '<a href="' . htmlspecialchars(getNextImageURL()) . '" title="' . html_encode($image->getTitle()) . '">'.gettext('next').' &raquo;</a>';
								} else {
									echo '<div class="imgdisabledlink">'.gettext('next').' &raquo;</div>';
								}
							?>
						</div>
					</div>

					<!-- Logo -->
					<div id="logo2">
						<?php printLogo(); ?>
					</div>
				</div>

				<!-- Crumb Trail Navigation -->
				<div id="wrapnav">
					<div id="navbar">
						<span>
							<?php printHomeLink('', ' | '); ?>
							<?php
							if (getOption('custom_index_page') === 'gallery') {
							?>
							<a href="<?php echo htmlspecialchars(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> | 
							<?php	
							}					
							?>
							<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> |
							<?php printParentBreadcrumb(); printAlbumBreadcrumb("", " | "); ?>
						</span>
						<?php printImageTitle(true); ?>
					</div>
				</div>
			</div>

			<!-- The Image -->
			<?php
				$s = getDefaultWidth() + 22;
				$wide = " style=\"width:".$s."px;";
				$s = getDefaultHeight() + 22;
				$high = " height:".$s."px;\"";
			?>
			<div id="image" <?php echo $wide.$high; ?>>

					<div id="image_container">
						<?php
						$fullimage = getFullImageURL();
						if (!empty($fullimage)) {
							?>
							<a href="<?php echo htmlspecialchars($fullimage);?>" title="<?php echo getBareImageTitle();?>">
							<?php
						} 
						printDefaultSizedImage(getImageTitle());
						if (!empty($fullimage)) {
							?>
							</a>
							<?php
						}
						?>
					</div>
					<?php
					if (getImageMetaData()) {echo "<div id=\"exif_link\"><a href=\"#\" title=\"".gettext("Image Info")."\" class=\"colorbox\">".gettext("Image Info")."</a></div>";
						echo "<div style='display:none'>"; printImageMetadata('', false); echo "</div>";
					}
					?>
			</div>
			<br clear="all" />
		</div>

		<!-- Image Description -->

			<div id="description">
				<p><?php	printImageDesc(true); ?></p>
				<?php if (function_exists('printRating')) printRating(); ?>
			</div>
			<?php
				if (function_exists('printImageMap')) {
					echo '<div id="map_link">';
					printImageMap();
					echo '</div>';
				}

		if (function_exists('printShutterfly')) printShutterfly();
		?>
	</div>

	<!-- Wrap Bottom Content -->
	<?php
	if (function_exists('printCommentForm')) {
		?>
		<div id="commentbox">
			<?php printCommentForm(); ?>
		</div>
		<?php
	}
	printFooter();
	?>

</body>
</html>
