<?php if (!defined('WEBPATH')) die(); 
require_once('normalizer.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareImageTitle();?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script type="text/javascript">var blogrelurl = "<?php echo $_zp_themeroot ?>";</script>
	<script type="text/javascript" src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.js"></script>
	<?php
		printRSSHeaderLink('Gallery','Gallery RSS');
		setOption('thumb_crop_width', 85, false);
		setOption('thumb_crop_height', 85, false);
		setOption('images_per_page', getOption('images_per_page') - 1, false);
		if (!isImagePhoto($_zp_current_image)) echo '<style type="text/css"> #prevnext a strong {display:none;}</style>';
	?>
</head>

<body class="photosolo">
		<?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

		<div id="content" class="v">

			<div id="desc">
				<?php if (!checkForPassword(true)) { ?>
					<h1><?php printImageTitle(true); ?></h1>
					<div id="descText"><?php printImageDesc(true); ?></div>
				<?php } ?>
			</div>

			<?php
				$ls = isLandscape();
				setOption('image_size', 480, false);
				$w = getDefaultWidth();
				$h = getDefaultHeight();
				if ($ls) {
					$wide = '';
				} else {
					$wide = "style=\"width:".($w+22)."px;\"";
				}
			?>
			<div class="main" <?php echo $wide; ?>>
				<?php if ($show = !checkForPassword()) { ?>
					<p id="photo">
					<strong>
						<?php printCustomSizedImage(getImageTitle(), null, $ls?480:null, $ls?null:480); ?>
					</strong>
					</p>
				<?php } ?>
			</div>
			<?php if ($show) { ?>
			<div id="meta">
				<ul>
					<li class="count"><?php
					 if (($num = getNumImages()) > 1) { printf(gettext('%1$u of %2$u photos'), imageNumber(), getNumImages()); }
					 ?></li>
					<li class="date"><?php printImageDate(); ?></li>
					<li class="tags"><?php echo getAlbumPlace(); ?></li>
					<li class="exif">
				<?php
					if (getImageEXIFData()) {echo "<a href=\"#TB_inline?height=345&amp;width=300&amp;inlineId=imagemetadata\" title=\"".gettext("image details")."\" class=\"thickbox\">".gettext('Image Info')."</a>";
						printImageMetadata('', false);
						echo "&nbsp;/&nbsp;";
					}
				?><a href="<?php echo htmlspecialchars(getFullImageURL());?>" title="<?php echo getBareImageTitle();?>"><?php echo gettext('Full Size'); ?></a>
 					</li>
				</ul>
			</div>
			<?php if (function_exists('printShutterfly')) printShutterfly(); ?>

			<div class="main">
				<?php
				if (function_exists('printCommentForm')) { 
					require_once('comment.php');
				}
			}
			?>

			</div>

				<div id="prevnext">
					<?php
					$img = $_zp_current_image->getPrevImage();
					if ($img) { 
						if ($img->getWidth() >= $img->getHeight()) {
							$iw = 89;
							$ih = NULL;
							$cw = 89;
							$ch = 67;
						} else {
							$iw = NULL;
							$ih = 89;
							$ch = 89;
							$cw = 67;
						}
					 	?>
						<div id="prev"><span class="thumb"><span>
							<em style="background-image:url('<?php echo htmlspecialchars($img->getCustomImage(NULL, $iw, $ih, $cw, $ch, NULL, NULL, true)); ?>')">
							<a href="<?php echo getPrevImageURL();?>" accesskey="z" style="background:#fff;">
							<strong style="width:<?php echo round(($w+20)/2); ?>px; height:<?php echo $h+20; ?>px;"><?php echo gettext('Previous'); ?>: </strong>Crescent</a>
							</em></span></span></div>
					<?php 
					} 
					$img = $_zp_current_image->getNextImage();
					if ($img) { 
						if ($img->getWidth() >= $img->getHeight()) {
							$iw = 89;
							$ih = NULL;
							$cw = 89;
							$ch = 67;
						} else {
							$iw = NULL;
							$ih = 89;
							$ch = 89;
							$cw = 67;
						}?>
						<div id="next"><span class="thumb"><span>
						<em style="background-image:url('<?php echo htmlspecialchars($img->getCustomImage(NULL, $iw, $ih, $cw, $ch, NULL, NULL, true)); ?>')">
						<a href="<?php echo getNextImageURL();?>" accesskey="x" style="background:#fff;">
						<strong style="width:<?php echo round(($w+20)/2); ?>px; height:<?php echo $h+20; ?>px;"><?php echo gettext('Next'); ?>: </strong>Sagamor</a>
						</em></span></span></div>
					<?php } ?>
				</div>

		</div>

		<p id="path">
			<?php printHomeLink('', ' > '); ?>
			<a href="<?php echo htmlspecialchars(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> &gt;
			<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> &gt; <?php printParentBreadcrumb("", " > ", " > "); printAlbumBreadcrumb("", " > "); echo getImageTitle(); ?>
		</p>

		<div id="footer">
			<hr />
			<p>
				<?php echo gettext('<a href="http://stopdesign.com/templates/photos/">Photo Templates</a> from Stopdesign.'); ?>
				<?php printZenphotoLink(); ?>
			</p>
		</div>
		<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>
</body>
</html>
