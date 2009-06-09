<?php if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo getBareAlbumTitle();?> | <?php echo getBareImageTitle();?></title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.js" type="text/javascript"></script>
	<script type="text/javascript">
		function toggleComments() {
			var commentDiv = document.getElementById("comments");
			if (commentDiv.style.display == "block") {
				commentDiv.style.display = "none";
			} else {
				commentDiv.style.display = "block";
			}
		}
	</script>
		<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
</head>
<body>

<div style="margin-top: 16px;"><!-- somehow the thickbox.css kills the top margin here that all other pages have... -->
</div>
<div id="main">
<div id="header">
		<h1><?php echo getGalleryTitle();?></h1>
	<div class="imgnav">
			<?php if (hasPrevImage()) { ?>
			<div class="imgprevious"><a href="<?php echo htmlspecialchars(getPrevImageURL());?>" title="<?php echo gettext("Previous Image"); ?>">&laquo; <?php echo gettext("prev"); ?></a></div>
			<?php } if (hasNextImage()) { ?>
			<div class="imgnext"><a href="<?php echo htmlspecialchars(getNextImageURL());?>" title="<?php echo gettext("Next Image"); ?>"><?php echo gettext("next"); ?> &raquo;</a></div>
			<?php } ?>
		</div> 
	</div>
	
	<div id="breadcrumb">
	<h2><a href="<?php echo getGalleryIndexURL();?>" title="<?php gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> &raquo; <?php echo gettext("Gallery"); ?><?php printParentBreadcrumb(" &raquo; "," &raquo; "," &raquo; "); printAlbumBreadcrumb(" ", " &raquo; "); ?>
			 <strong><?php printImageTitle(true); ?></strong> (<?php echo imageNumber()."/".getNumImages(); ?>)
			</h2>
		</div>
		
<div id="content">
	<div id="content-left">
		
	<!-- The Image -->
	<?php if (!checkForPassword()) { ?>
	
 <?php 
 if(function_exists("printPagedThumbsNav")) { 
 		printPagedThumbsNav(6, FALSE, gettext('&laquo; prev thumbs'), gettext('next thumbs &raquo;'), 40, 40); 
 } ?>
	
	<div id="image">
		<?php if(getOption("Use_thickbox")) {
			$thickboxclass = " class=\"thickbox\"";
		} else {
			$thickboxclass = "";
		}
		?>
		<a href="<?php echo htmlspecialchars(getUnprotectedImageURL()); ?>"<?php echo $thickboxclass; ?> title="<?php echo getBareImageTitle();?>">
		<?php printCustomSizedImageMaxSpace(getBareImageTitle(),580,580); ?>
   </a>
	</div>

	<div id="narrow">
		<?php printImageDesc(true); ?>
		<?php if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow')); ?>
		<br />
		<?php
			if (getImageEXIFData()) { echo "<div id=\"exif_link\"><a href=\"#TB_inline?height=345&amp;width=300&amp;inlineId=imagemetadata\" title=\"".gettext("Image Info")."\" class=\"thickbox\">".gettext("Image Info")."</a></div>";
				printImageMetadata('', false);
			}
		?>
		<?php printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ''); ?>

		<?php if (function_exists('printImageMap')) printImageMap(); ?>

    <?php if (function_exists('printRating')) { printRating(); }?>

		<?php if (function_exists('printShutterfly')) printShutterfly(); ?>

</div>
		<?php if (function_exists('printCommentForm')) { ?>
			<div id="comments">
			<?php printCommentForm(); ?>
			</div>
		<?php } ?>
	
		<?php } ?>
</div>
</div><!-- content-left -->
					
<div id="sidebar">
<?php include("sidebar.php"); ?>
</div>
	
	<div id="footer">
	<?php include("footer.php"); ?>
	</div>
	

	</div><!-- content -->

</div><!-- main -->
<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>
</body>
</html>