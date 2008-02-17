<?php require_once ('customfunctions.php'); 
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?> | <?php echo getImageTitle();?></title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/jquery.js" type="text/javascript"></script>
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.js" type="text/javascript"></script>
	<?php zenJavascript(); ?>
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
							echo '<a href="' . getPrevImageURL() . '" title="' . $image->getTitle() . '">&laquo; prev</a>';
						} else {
							echo '<div class="imgdisabledlink">&laquo; prev</div>'; 
											}
						?>
					</div>
					<div class="imgnext">
					<?php
					if (hasNextImage()) { 
						$image = $_zp_current_image->getNextImage();
						echo '<a href="' . getNextImageURL() . '" title="' . $image->getTitle() . '">next &raquo;</a>';
					} else {
						echo '<div class="imgdisabledlink">next &raquo;</div>';
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
				<span><?php printHomeLink('', ' | '); ?><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | 
				<?php printParentBreadcrumb(); printAlbumBreadcrumb("", " | "); ?> 
				</span> 
				<?php printImageTitle(true); ?>
			</div>
		</div>
	</div>

	<!-- The Image -->
	<?php  
		$s = getDefaultWidth() + 22;
		$wide = "style=\"width:".$s."px;"; 
		$s = getDefaultHeight() + 22;
		$high = " height:".$s."px;\""; 
	?>
		<div id="image" <?php echo $wide.$high; ?>>
			<?php if ($show = !checkForPassword()) { ?>
			<div id="image_container">
				<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>">
					<?php printDefaultSizedImage(getImageTitle()); ?>
				</a>
			</div>
		<?php 
		printImageMap(6, 'G_HYBRID_MAP');
		if (getImageEXIFData()) {
			echo "<div id=\"exif_link\"><a href=\"#TB_inline?height=400&width=300&inlineId=imagemetadata\" title=\"image details from exif\" class=\"thickbox\">Image Info</a></div>";
			printImageMetadata('', false); 
		}
	} ?>
	</div>
		<br clear="all" />
 	</div>  

	<!-- Image Description -->
	<?php if ($show) { ?><div id="description"><?php printImageDesc(true); ?></div> <?php } ?>

</div>

	<!-- Wrap Bottom Content -->
	<?php if ($show && getOption('Allow_comments')) { ?>
	<div id="content">

	<!-- Headings -->
		<div id="bottomheadings">
			<div class="bottomfull">
				<?php $num = getCommentCount(); echo ($num == 1) ? ("<h3>1 Comment</h3>") : ("<h3>$num Comments</h3>"); ?>
		</div>
					</div>

		<!-- Wrap Comments -->
			<div id="main3">

				<div id="comments">
					<?php while (next_comment()):  ?>
					<div class="comment">
							<div class="commentinfo">
								<h4><?php printCommentAuthorLink(); ?></h4>: on <?=getCommentDate();?>, <?=getCommentTime();?><?php printEditCommentLink('Edit', ', ', ''); ?>
							</div>
							<div class="commenttext"><?=getCommentBody();?></div>
								</div>
					<?php endwhile; ?>
				</div>

			<!-- Comment Box -->
			<?php if (OpenedForComments()) { ?>
					<div id="commentbox">
						<h2>Leave a Reply</h2>
						<form id="commentform" action="#" method="post">
								<div>
										<input type="hidden" name="comment" value="1" />
									<input type="hidden" name="remember" value="1" />
									<?php printCommentErrors(); ?>
									<input type="text" name="name" id="name" class="textinput" value="<?=$stored[0];?>" size="22" tabindex="1" /><label for="name"><small> Name</small></label>
									<br/><input type="text" name="email" id="email" class="textinput" value="<?=$stored[1];?>" size="22" tabindex="2" /><label for="email"><small> Mail</small></label>
												<br/><input type="text" name="website" id="website" class="textinput" value="<?=$stored[2];?>" size="22" tabindex="3" /><label for="website"><small> Website</small></label>
												<?php printCaptcha('<br/>', '', ' <small>Enter Captcha</small>', 8); ?>
									<textarea name="comment" id="comment" rows="5" cols="100%" tabindex="4"></textarea>
									<input type="submit" value="Submit" class="pushbutton" />
								</div>
						</form>
					</div>
				<?php } else {?>
					<div id="commentbox">
						<h3>Closed for comments</h3>
					</div>
				<?php } ?>

			</div>
		</div>
		<?php } ?>

	<!-- Footer -->
	<div class="footlinks">
		<?php 
		$h = hitcounter('image');
		if ($h == 1) {
			$h .= ' hit';
		} else {
			$h .= ' hits';
		}
		echo "<p>$h on this image</p>";
		printThemeInfo(); 
		?>
		<a href="http://www.zenphoto.org" title="A simpler web photo album">Powered by 
		<font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps"><font face="Arial Black" size="1">photo</font></span></a>
	</div>
		
	<?php printAdminToolbox(); ?>
 
</body>
</html>
