<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?> | <?php echo getImageTitle();?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
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

	<div class="imgnav">
		<?php if (hasPrevImage()) { ?>
		<div class="imgprevious"><a href="<?php echo getPrevImageURL();?>" title="Previous Image">&laquo; prev</a></div>
		<?php } if (hasNextImage()) { ?>
		<div class="imgnext"><a href="<?php echo getNextImageURL();?>" title="Next Image">next &raquo;</a></div>
		<?php } ?>
	</div>

		<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a>
		  | <?php printParentBreadcrumb(); ?>
		  | </span> <?php printImageTitle(true); ?></h2>
	</div>

<div id="padbox">

	<div id="image">
		<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>"> <?php printDefaultSizedImage(getImageTitle()); ?></a> 
	</div>
	
	<div id="narrow">
	
		<p class="imgdesc"><?php printImageDesc(true); ?></p>
		
		<div id="comments">
		<?php $num = getCommentCount(); echo ($num == 0) ? "" : ("<h3>Comments ($num)</h3>"); ?>
			<?php while (next_comment()):  ?>
			<div class="comment">
				<div class="commentmeta">
					<h4><?php printCommentAuthorLink(); ?> says:</h4> 
					<?php echo getCommentDate();?>, <?php echo getCommentTime();?><?php printEditCommentLink('Edit', ' | ', ''); ?>
				</div>
				<div class="commentbody">
					<?php echo getCommentBody();?>
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
            
					</table>
					<textarea name="comment" rows="6" cols="40"></textarea>
					<br />
					<input type="submit" value="Add Comment" class="pushbutton" /></div>
				</form>
			</div>
		</div>
	</div>
</div>
</div>

<div id="credit"><?php printAdminLink('Admin', '', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | 'Sterile' Theme by <a href="http://www.levibuzolic.com" traget="_blank">Levi Buzolic</a></div>
</body>
</html>
