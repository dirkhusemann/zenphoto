<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); normalizeColumns(1, 7);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<script type="text/javascript" src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/plugins/rating/rating.js"></script>
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/plugins/rating/rating.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); echo "\n"; ?>
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
	<?php zenJavascript(); ?>
	
</head>
<body>



<div id="main">
	<div id="gallerytitle">
			<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?>
					</a> | <?php printParentBreadcrumb(); printAlbumBreadcrumb("", " | "); ?>
					</span> <?php printImageTitle(true); ?></h2>
		</div>
	
		<hr />
	
	<!-- The Image -->
	<?php if (!checkForPassword()) { ?>
		<div class="image">
		<div class="imgnav">
			<?php if (hasPrevImage()) { ?> <a class="prev" href="<?php echo getPrevImageURL();?>" title="Previous Image">&laquo; prev</a>
			<?php } if (hasNextImage()) { ?> <a class="next" href="<?php echo getNextImageURL();?>" title="Next Image">next &raquo;</a><?php } ?>
		</div>
			
				<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>">
				<?php printDefaultSizedImage(getImageTitle()); ?></a>

				<div id="image_data">
					<div id="fullsize_download_link">
						<em>
						<a href="<?php echo getProtectedImageURL();?>" title="<?php echo getImageTitle();?>">Original Size: 
							<?php echo getFullWidth() . "x" . getFullHeight(); ?>
						</a>
								</em>
					</div>
 			
					 	<div class="rating"><?php printImageRating(); ?></div> 
					
					<div id="meta_link">
						<?php 
							if (getImageEXIFData()) {echo "<a href=\"#TB_inline?height=345&width=300&inlineId=imagemetadata\" title=\"Image Info\" class=\"thickbox\">Image Info</a>";
								printImageMetadata('', false); 
							} 
					?>
					</div>
					
						<br clear="all" />
					<?php printImageDesc(true); ?>
					<?php printTags('links', '<strong>Tags:</strong> ', 'taglist', ''); ?>
				<?php printImageMap(); ?>
				</div>
			
				<?php if (getOption('Allow_comments')) { ?>
				<div id="comments">
					<div class="commentcount"><?php $num = getCommentCount(); echo ($num == 0) ? "No comments" : (($num == 1) ? "<strong>One</strong> comment" : "<strong>$num</strong> comments"); ?> on this image:</div>
					
						<?php while (next_comment()):  ?>
						<div class="comment">
							<div class="commentmeta">
									<span class="commentauthor"><?php printCommentAuthorLink(); ?></span>
									| <span class="commentdate"><?php echo getCommentDate();?>, <?php echo getCommentTime();?> PST</span>
 							</div>
								<div class="commentbody"><?php echo getCommentBody();?></div>            
						</div>
						<?php endwhile; ?>
					
						<div class="imgcommentform">
				<?php if (OpenedForComments()) { ?>
							<!-- If comments are on for this image AND album... -->
							<h3>Add a comment:</h3>
							<form id="commentform" action="#comments" method="post">
									<input type="hidden" name="comment" value="1" />
									<input type="hidden" name="remember" value="1" />
										<?php printCommentErrors(); ?>
									<table border="0">
										<tr><td><label for="name">Name:</label></td>    <td><input type="text" name="name" size="20" value="<?php echo $stored[0];?>" />  </td></tr>
										<tr><td><label for="email">E-Mail (won't be public):</label></td> <td><input type="text" name="email" size="20" value="<?php echo $stored[1];?>" /> </td></tr>
										<tr><td><label for="website">Site:</label></td> <td><input type="text" name="website" size="30" value="<?php echo $stored[2];?>" /></td></tr>
												<?php printCaptcha('<tr><td>Enter ', ':</td><td>', '</td></tr>'); ?>
									</table>
									<textarea name="comment" rows="6" cols="40"></textarea><br />
									<input type="submit" value="Add Comment" />
							</form>
						</div>
				<?php } else { echo 'Comments are closed.'; } ?>
				</div>
				<?php } ?>
			
		</div>
		<?php } ?>
		
		<div id="credit">
		<?php printRSSLink('Gallery','','RSS', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | <a href="?p=archive">Archive View</a><br />
			<?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?>
		</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
