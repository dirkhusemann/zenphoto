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
							echo '<a href="' . getPrevImageURL() . '" title="' . $image->getTitle() . '">&laquo; '.gettext('prev').'</a>';
						} else {
							echo '<div class="imgdisabledlink">&laquo; '.gettext('prev').'</div>'; 
						}
						?>
					</div>
					<div class="imgnext">
					<?php
					if (hasNextImage()) { 
						$image = $_zp_current_image->getNextImage();
						echo '<a href="' . getNextImageURL() . '" title="' . $image->getTitle() . '">'.gettext('next').' &raquo;</a>';
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
				<span><?php printHomeLink('', ' | '); ?><a href="<?php echo getGalleryIndexURL();?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> | 
				<?php printParentBreadcrumb(); printAlbumBreadcrumb("", " | "); ?> 
				</span> 
				<?php printImageTitle(true); ?>
			</div>
		</div>
	</div>

	<!-- The Image -->
	<?php  
		if (getImageVideo()) {
			$ext = strtolower(strrchr(getUnprotectedImageURL(), "."));
			switch ($ext) {
				case '.flv':
					$w = 400;
					$h = 300;
					break;
				case '.3gp':
					$w = 352;
					$h = 304;
					break;
				case '.mov':
					$w = 640;
					$h = 496;
					break;
			}
			$h += 22;
			$w += 22;
			$wide = "style=\"width:".$w."px;"; 
			$high = " height:".$h."px;\""; 
		} else {
			$s = getDefaultWidth() + 22;
			$wide = "style=\"width:".$s."px;"; 
			$s = getDefaultHeight() + 22;
			$high = " height:".$s."px;\""; 
		}
	?>
		<div id="image" <?php echo $wide.$high; ?>>
			<?php if ($show = !checkForPassword()) { ?>
			<div id="image_container">
				<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>">
					<?php printDefaultSizedImage(getImageTitle()); ?>
				</a>
			</div>
		<?php 
		if (getImageEXIFData()) {
			echo "<div id=\"exif_link\"><a href=\"#TB_inline?height=400&width=300&inlineId=imagemetadata\" title=\"".gettext("image details from exif")."\" class=\"thickbox\">".gettext('Image Info')."</a></div>";
			printImageMetadata('', false); 
		}
		if (function_exists('printImageMap')) { printImageMap(6, 'G_HYBRID_MAP');	}
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
				<?php $num = getCommentCount(); echo ($num == 1) ? ("<h3>1 ".gettext("Comment")."</h3>") : ("<h3>$num ".gettext("Comments")."</h3>"); ?>
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
						<h2><?php echo gettext('Leave a Reply');?></h2>
						<form id="commentform" action="#" method="post">
								<div>
										<input type="hidden" name="comment" value="1" />
									<input type="hidden" name="remember" value="1" />
									<?php printCommentErrors(); ?>
									<input type="text" name="name" id="name" class="textinput" value="<?=$stored[0];?>" size="22" tabindex="1" /><label for="name"><small> <?php echo gettext('Name');?></small></label>
									<br/><input type="text" name="email" id="email" class="textinput" value="<?=$stored[1];?>" size="22" tabindex="2" /><label for="email"><small> <?php echo gettext('Email');?></small></label>
												<br/><input type="text" name="website" id="website" class="textinput" value="<?=$stored[2];?>" size="22" tabindex="3" /><label for="website"><small> <?php echo gettext('Website');?></small></label>
												<?php printCaptcha('<br/>', '', ' <small>'.gettext("Enter Captcha").'</small>', 8); ?>
									<textarea name="comment" id="comment" rows="5" cols="100%" tabindex="4"></textarea>
									<input type="submit" value="<?php echo gettext('Submit');?>" class="pushbutton" />
								</div>
						</form>
					</div>
				<?php } else {?>
					<div id="commentbox">
						<h3><?php echo gettext('Closed for comments.');?></h3>
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
			$h .= ' '.gettext(' hit');
		} else {
			$h .= ' '.gettext(' hits');
		}
		echo "<p>$h ".gettext('on this image')."</p>";
		printThemeInfo(); 
		?>
		<a href="http://www.zenphoto.org" title="A simpler web photo album"><?php echo gettext('Powered by ');?>
		<font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps"><font face="Arial Black" size="1">photo</font></span></a>
	</div>
		
	<?php printAdminToolbox(); ?>
 
</body>
</html>
