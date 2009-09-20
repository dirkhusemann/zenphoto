<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); normalizeColumns(1, 7);?>
<?php
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header('Content-Type: text/html; charset=' . getOption('charset'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); echo "\n"; ?>
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.js" type="text/javascript"></script>


</head>
<body>

<div id="main">
	<div id="gallerytitle">
			<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a>
      | <?php printParentBreadcrumb(); printAlbumBreadcrumb("", " | "); ?></span> <?php printImageTitle(true); ?></h2>
	</div>

	<hr />
	<!-- The Image -->
	<?php if (!checkForPassword()) { ?>
		<div class="image">
		<div class="imgnav">
			<?php if (hasPrevImage()) { ?> <a class="prev" href="<?php echo htmlspecialchars(getPrevImageURL());?>" title="<?php echo gettext('Previous Image'); ?>">&laquo; <?php echo gettext("prev"); ?></a>
			<?php } if (hasNextImage()) { ?> <a class="next" href="<?php echo htmlspecialchars(getNextImageURL());?>" title="<?php echo gettext('Next Image'); ?>"><?php echo gettext("next");?> &raquo;</a><?php } ?>
		</div>
				<?php printDefaultSizedImage(getImageTitle()); ?></a>

				<div id="image_data">
						<?php
						if (isImagePhoto()) {
							?>
							<div id="fullsize_download_link">
								<em>
								<a href="<?php echo htmlspecialchars(getFullImageURL());?>" title="<?php echo getBareImageTitle();?>"><?php echo gettext("Original Size:"); ?>
									<?php echo getFullWidth() . "x" . getFullHeight(); ?>
								</a>
								</em>
							</div>
							<?
						}
						?>

					<div id="meta_link">
						<?php
							if (getImageEXIFData()) {echo "<a href=\"#TB_inline?height=345&amp;width=400&amp;inlineId=imagemetadata\" title=\"".gettext("Image Info")."\" class=\"thickbox\">".gettext("Image Info")."</a>";
								printImageMetadata('', false);
							}
					?>
					</div>

					<br clear="all" />
					<?php printImageDesc(true); ?>
					<?php printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ''); ?>
					<?php if (function_exists('zenPaypal')) { zenPaypal(NULL, true); } ?>
					<?php if (function_exists('googleCheckout')) {
						printGoogleCartWidget();
						googleCheckout(NULL, true);
					} ?>



					<?php if (function_exists('printShutterfly')) printShutterfly(); ?>
					<?php if (function_exists('printImageMap')) printImageMap(); ?>
          <div class="rating"><?php if (function_exists('printImageRating')) printImageRating(); ?></div>
				</div>

				<?php
				if (function_exists('printCommentForm')) {
					printCommentForm();
				}
				?>

		</div>
		<?php } ?>

		<div id="credit">
			<?php printRSSLink('Gallery','','RSS', ' | '); ?>
			<?php printZenphotoLink(); ?>
			 | <?php printCustomPageURL(gettext("Archive View"),"archive"); ?>
			<?php
			if (function_exists('printUserLogin_out')) {
				printUserLogin_out(" | ");
			}
			?>
			<br />
			<?php printf(gettext("%u seconds"), round((array_sum(explode(" ",microtime())) - $startTime),4)); ?>
		</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
