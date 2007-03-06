<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> > <?php echo getAlbumTitle();?> > <?php echo getImageTitle();?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/css/master.css" type="text/css" />
	<script type="text/javascript">var blogrelurl = "<?php echo $_zp_themeroot ?>/";</script>
	<script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/rememberMe.js"></script>
	<script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/comments.js"></script>
	<?php zenJavascript(); ?>
</head>

<body class="photosolo">

<div id="content">

	<div id="desc">
	  <h1><?php printImageTitle(true); ?></h1>
	  <p><?php printImageDesc(true); ?></p>
	</div>

	<div id="main">
		<div id="photo_container"><?php printCustomSizedImage(getImageTitle(), null, 400); ?></div>
		<div id="meta">
		    <ul>
		      <li class="count"></li>
		      <li class="date"></li>
		      <li class="tags"></li>
		    </ul>
  		</div>

		<div id="commentblock">

				<?php $showhide = "<a href=\"#comments\" id=\"showcomments\"><img src=\"".$_zp_themeroot."/img/btn_show.gif\" width=\"35\" height=\"11\" alt=\"SHOW\" /></a> <a href=\"#content\" id=\"hidecomments\"><img src=\"".$_zp_themeroot."/img/btn_hide.gif\" width=\"35\" height=\"11\" alt=\"HIDE\" /></a>"; $num = getCommentCount(); if ($num == 0) echo "<h2>No comments yet</h2>"; if ($num == 1) echo "<h2>1 comment so far $showhide</h2>"; if ($num > 1) echo "<h2>$num comments so far $showhide</h2>"; ?>

				 <div <?php $num = getCommentCount(); if ($num > 0) echo "id=\"comments\""; ?>>
				<dl class="commentlist">
					<?php while (next_comment()):  ?>
					<dt>
					<a class="postno"> </a>
					<em>On <?php echo getCommentDate();?>, <?php printCommentAuthorLink(); ?> wrote:</em>
    				</dt>

    				<dd>
					<p><?php echo getCommentBody();?><?php printEditCommentLink('Edit', ' (', ')'); ?></p>
					</dd>
    				<?php endwhile; ?>
    			</dl>
<?php if (isset($error)) { ?><p><div class="error">There was an error submitting your comment. Name, a valid e-mail address, and a comment are required.</div></p><?php } ?>
    			<p class="mainbutton" id="addcommentbutton"><a href="#addcomment" class="btn"><img src="<?php echo $_zp_themeroot ?>/img/btn_add_a_comment.gif" alt="" /></a></p>

    			<div id="addcomment">
				<h2>Add a comment</h2>


						<!-- If comments are on for this image AND album... -->

						<form id="comments-form" action="#" method="post">
						<input type="hidden" name="comment" value="1" />
		          		<input type="hidden" name="remember" value="1" />

							<table border="0">

								<tr valign="top" align="left" id="row-name">
									<th><label for="name">name:</label></td>
									<td><input type="text" id="name" name="name" class="text" value="<?php echo $stored[0];?>" class="inputbox" />
									</td>
								</tr>
								<tr valign="top" align="left" id="row-email">
									<th><label for="email">email:</label></td>
									<td><input type="text" id="email" name="email" class="text" value="<?php echo $stored[1];?>" class="inputbox" /> <em>(not displayed)</em>
									</td>
								</tr>
								<tr valign="top" align="left">
									<th><label for="website">url:</label></td>
									<td><input type="text" id="website" name="website" class="text" value="<?php echo $stored[2];?>" class="inputbox" /></td>
								</tr>
								<tr valign="top" align="left">
									<th><label for="c-text">comment:</label></th>
									<td><textarea name="comment" rows="10" cols="40"></textarea></td>
								</tr>
								<tr valign="top" align="left">
								    <th class="buttons">&nbsp;</th>
    								<td class="buttons"><input type="submit" value="Add comment" class="pushbutton" id="btn-preview" /><p>Fill in "name", "email" and "comment".</p></td>
    								</tr>
							</table>
						</form>
			</div>
		</div>
	</div>
</div>

<?php if (hasPrevImage()) { ?>
<div id="prev" class="slides">  <p><a href="<?php echo getPrevImageURL();?>" title="Previous photo"><img src="<?php echo getPrevImageThumb(); ?>" /></a></p></div>
<?php } if (hasNextImage()) { ?>
<div id="next" class="slides">  <p><a href="<?php echo getNextImageURL();?>" title="Next photo"><img src="<?php echo getNextImageThumb(); ?>" /></a></p></div>
<?php } ?>

</div>

<p id="path"><a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a> >
			 <a href="<?php echo getAlbumLinkURL();?>" title="Gallery Index"><?php echo getAlbumTitle();?></a> >
		     <?php printImageTitle(false); ?></p>

<div id="footer">
		<hr />
		<p>Design by <a href="http://stopdesign.com/templates/photos/">Stopdesign</a>.
		Powered by <a href="http://www.zenphoto.org">zenphoto</a>.<br />
		Theme by <a href="http://www.bleecken.de/bilder/">Sjard Bleecken</a>.
	    <?php printAdminLink('Admin', '<br />', '.'); ?>
</div>

</body>
</html>
