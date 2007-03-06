<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php if (zp_conf('website_title') != '') { echo zp_conf('website_title') . '&#187; '; } ?><?php printGalleryTitle(); ?> &#187; <?php echo getAlbumTitle();?> &#187; <?php echo getImageTitle();?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/css/master.css" type="text/css" />
	<script type="text/javascript">var blogrelurl = "<?php echo $_zp_themeroot ?>/";</script>
	<script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/rememberMe.js"></script>
	<script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/fadein.js"></script>
	<script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/comments.js"></script>
	<?php zenJavascript(); ?>
</head>

<body class="photosolo">
<div id="content">
	<div id="desc"></div>
	<div id="main">
		<div id="photo_container">
		 <div id="fullc">
		    <p><a href="<?php echo getFullImageURL();?>" title="Full view"><em>Full view</em></a></p>
		  			<a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>"><?php printCustomSizedImage(getImageTitle(), 480); ?></a> 
		 </div>
		 	  <h1 class="nb"><?php printImageTitle(true); ?></h1>
	  <p><?php printImageDesc(true); ?></p>
		<div id="meta">
		    <ul>
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
<?php if (isset($error)) { ?><p><div class="error">There was an error submitting your comment.<br />Your name, a valid e-mail address, and a comment are all required.</div></p><?php } ?>
    			<p id="addcommentbutton"><a href="#addcomment" class="btn">Add a comment</a></p>
    			<div id="addcomment">
				<h2>Add a comment</h2>


						<!-- If comments are on for this image AND album... -->

						<form id="comments-form" action="#" method="post">
						<input type="hidden" name="comment" value="1" />
		          		<input type="hidden" name="remember" value="1" />

							<table border="0">

								<tr valign="top" align="left" id="row-name">
									<th><label for="name">Name:</label></td>
									<td><input type="text" id="name" name="name" class="text" value="<?php echo $stored[0];?>" class="inputbox" />
									</td>
								</tr>
								<tr valign="top" align="left" id="row-email">
									<th><label for="email">E-mail:</label></td>
									<td><input type="text" id="email" name="email" class="text" value="<?php echo $stored[1];?>" class="inputbox" /> <em>(Not displayed)</em>
									</td>
								</tr>
								<tr valign="top" align="left">
									<th><label for="website">URL:</label></td>
									<td><input type="text" id="website" name="website" class="text" value="<?php echo $stored[2];?>" class="inputbox" /></td>
								</tr>
								<tr valign="top" align="left">
									<th><label for="c-text">Comment:</label></th>
									<td><textarea name="comment" rows="10" cols="40"></textarea></td>
								</tr>
								<tr valign="top" align="left">
								    <th class="buttons">&nbsp;</th>
    								<td class="buttons"><input type="submit" value="Add comment" class="pushbutton" id="btn-preview" /><p>Please fill in all fields.</p></td>
    								</tr>
							</table>
						</form>
			</div>
		</div>
	</div>
</div>
</div>
<?php if (hasPrevImage()) { ?>
<div id="prev" class="slides">  <p><a href="<?php echo getPrevImageURL();?>" title="Previous photo"><img src="<?php echo getPrevImageThumb(); ?>" /><em>&#8592; Previous</em></a></p></div>
<?php } if (hasNextImage()) { ?>
<div id="next" class="slides">  <p><a href="<?php echo getNextImageURL();?>" title="Next photo"><img src="<?php echo getNextImageThumb(); ?>" /><em>Next &#8594;</em></a></p></div>
<?php } ?>
</div>

<p id="path"><?php if (zp_conf('website_url') != '') { ?> <a href="<?php echo zp_conf('website_url'); ?>" title="Back"><?php echo zp_conf('website_title'); ?></a> &#187; <?php } ?>
      <a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a> &#187;
			<a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?> Gallery"><?php echo getAlbumTitle();?></a> &#187; <a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>: Full View" class="active"><?php printImageTitle(false); ?></a></p>

<div id="footer">
	<p><?php printAdminLink('Admin'); ?></p>
</div>
</body>
</html>
