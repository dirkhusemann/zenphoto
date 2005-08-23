<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | <?=getAlbumTitle();?> | <?=getImageTitle();?></title>
	<link rel="stylesheet" href="<?= $_zp_themeroot ?>/zen.css" type="text/css" />
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

	<div class="imgnav">
		<?php if (hasPrevImage()) { ?>
		<div class="imgprevious"><a href="<?=getPrevImageURL();?>" title="Previous Image">&laquo; prev</a></div>
		<?php /*if (hasNextImage()) echo " | "; */ } if (hasNextImage()) { ?>
		<div class="imgnext"><a href="<?=getNextImageURL();?>" title="Next Image">next &raquo;</a></div>
		<?php } ?>
	</div>
		
	<div id="gallerytitle">
		<h2><span><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a>
		 | <a href="<?=getAlbumLinkURL();?>" title="Gallery Index"><?=getAlbumTitle();?></a>
		  |</span> <?php printImageTitle(true); ?></h2>
	</div>
	
	<div id="image">
		<a href="<?=getFullImageURL();?>" title="<?=getImageTitle();?>"> <?php printDefaultSizedImage(getImageTitle()); ?></a> 
	</div>
	
	<div id="narrow">
	
		<?php printImageDesc(true); ?>
		
		<div id="comments">
		<?php $num = getCommentCount(); echo ($num == 0) ? "" : ("<h3>Comments ($num)</h3>"); ?>
			<?php while (next_comment()):  ?>
			<div class="comment">
				<div class="commentmeta">
					<span class="commentauthor"><?php printCommentAuthorLink(); ?></span> says: 
				</div>
				<div class="commentbody">
					<?=getCommentBody();?>
				</div>
				<div class="commentdate">
					<?=getCommentDate();?>
					,
					<?=getCommentTime();?>
					PST
				</div>
			</div>
			<?php endwhile; ?>
			<div class="imgcommentform">
				<!-- If comments are on for this image AND album... -->
				<h3>Add a comment:</h3>
				<form id="commentform" action="#" method="post">
				<div><input type="hidden" name="comment" value="1" />
          		<input type="hidden" name="remember" value="1" />
          <?php if (isset($error)) { ?><tr><td><div class="error">There was an error submitting your comment. Name, a valid e-mail address, and a comment are required.</div></td></tr><?php } ?>

					<table border="0">
						<tr>
							<td><label for="name">Name:</label></td>
							<td><input type="text" id="name" name="name" size="20" value="<?=$stored[0];?>" class="inputbox" />
							</td>
						</tr>
						<tr>
							<td><label for="email">E-Mail:</label></td>
							<td><input type="text" id="email" name="email" size="20" value="<?=$stored[1];?>" class="inputbox" />
							</td>
						</tr>
						<tr>
							<td><label for="website">Site:</label></td>
							<td><input type="text" id="website" name="website" size="40" value="<?=$stored[2];?>" class="inputbox" /></td>
						</tr>
            
					</table>
					<textarea name="comment" rows="6" cols="40"></textarea>
					<br />
					<input type="submit" value="Add Comment" class="pushbutton" /></div>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="credit"><?php if (zp_loggedin()) { ?><a href="<?=getGalleryIndexURL();?>/admin/">Admin</a> | <?php } ?>Powered by <a href="http://www.trisweb.com" title="A simpler web photo album">Zen Photo</a></div>

</body>
</html>
