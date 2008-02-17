<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?> | <?php echo getImageTitle();?></title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/plugins/rating/rating.js"></script>
		<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/plugins/rating/rating.css" type="text/css" />
		<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/jquery.js" type="text/javascript"></script>
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
		<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<div class="imgnav">
			<?php if (hasPrevImage()) { ?>
			<div class="imgprevious"><a href="<?php echo getPrevImageURL();?>" title="Previous Image">&laquo; prev</a></div>
			<?php } if (hasNextImage()) { ?>
			<div class="imgnext"><a href="<?php echo getNextImageURL();?>" title="Next Image">next &raquo;</a></div>
			<?php } ?>
		</div>
		<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?>
			</a> | <?php printParentBreadcrumb("", " | ", " | "); printAlbumBreadcrumb("", " | "); ?> 
			</span> <?php printImageTitle(true); ?>
		</h2>
		
	</div>

	<!-- The Image -->
	<?php if (!checkForPassword()) { ?>
	<div id="image">
		<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>"><strong><?php printDefaultSizedImage(getImageTitle()); ?></strong></a> 
		</div>
	<?php if (getOption('Allow_ratings')) { printImageRating(); }?>
	
	<div id="narrow">
		
		<?php printImageDesc(true); ?>
		<hr />
		<?php 
			if (getImageEXIFData()) {echo "<div id=\"exif_link\"><a href=\"#TB_inline?height=345&width=300&inlineId=imagemetadata\" title=\"Image Info\" class=\"thickbox\">Image Info</a></div>";
				printImageMetadata('', false); 
			} 
		?>
		<?php printTags('links', '<strong>Tags:</strong> ', 'taglist', ''); ?>
		
		<?php printImageMap(); ?>
		
		<?php if (getOption('Allow_comments')) { ?>
				<div id="comments">
		<?php $num = getCommentCount(); echo ($num == 0) ? "" : ("<h3>Comments ($num)</h3><hr />"); ?>
			<?php while (next_comment()){  ?>
			<div class="comment">
				<div class="commentmeta">
					<span class="commentauthor"><?php printCommentAuthorLink(); ?></span> says: 
				</div>
				<div class="commentbody">
					<?php echo getCommentBody();?>
				</div>
				<div class="commentdate">
					<?php echo getCommentDate();?>
					,
					<?php echo getCommentTime();?>
								<?php printEditCommentLink('Edit', ' | ', ''); ?>
				</div>
			</div>
			<?php }; ?>
						
			<?php if (OpenedForComments()) { ?>
			<div class="imgcommentform">
							<!-- If comments are on for this image AND album... -->
				<h3>Add a comment:</h3>
				<form id="commentform" action="#" method="post">
				<div><input type="hidden" name="comment" value="1" />
							<input type="hidden" name="remember" value="1" />
								<?php printCommentErrors(); ?>
					<table border="0">
						<tr>
							<td><label for="name">Name:</label></td>
							<td><input type="text" id="name" name="name" size="20" value="<?php echo $stored[0];?>" class="inputbox" />
							</td>
						</tr>
						<tr>
							<td><label for="email">E-Mail:</label></td>
							<td><input type="text" id="email" name="email" size="20" value="<?php echo $stored[1];?>" class="inputbox" />
							</td>
						</tr>
						<tr>
							<td><label for="website">Site:</label></td>
							<td><input type="text" id="website" name="website" size="40" value="<?php echo $stored[2];?>" class="inputbox" /></td>
						</tr>
												<?php if (getOption('Use_Captcha')) { 
 													$captchaCode=generateCaptcha($img); ?>
 													<tr>
 													<td><label for="code">Enter Captcha:<img src=<?php echo "\"$img\"";?> alt="Code" align="absbottom"/></label></td>
 													<td><input type="text" id="code" name="code" size="20" class="inputbox" /><input type="hidden" name="code_h" value="<?php echo $captchaCode;?>"/></td>
 													</tr>
												<?php } ?>
					</table>
					<textarea name="comment" rows="6" cols="40"></textarea>
					<br />
					<input type="submit" value="Add Comment" class="pushbutton" /></div>
				</form>
			</div>
		</div>
				<?php } else { echo 'Comments are closed.'; } ?>
				<?php } ?>
	</div>
		<?php } ?>
</div>

<div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?> <a href="<?php echo getGalleryIndexURL();?>?p=archive">Archive View</a> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

<?php printAdminToolbox(); ?>

</body>
</html>
