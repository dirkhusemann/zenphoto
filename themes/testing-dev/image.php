<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
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
  <div id="gallerytitle">
    <h2><span>
				<a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> | 
				<a href="<?=getAlbumLinkURL();?>" title="Gallery Index"><?=getAlbumTitle();?></a> |</span>
			  <?php printImageTitle(true); ?></h2>
  </div>
  
  <hr />
  <p> </p>
    <div class="image">
			<div class="imgnav">
				<?php if (hasPrevImage()) { ?> <a href="<?=getPrevImageURL();?>" title="Previous Image">&laquo; prev</a>
				<?php if (hasNextImage()) echo " | "; } if (hasNextImage()) { ?> <a href="<?=getNextImageURL();?>" title="Next Image">next &raquo;</a><?php } ?>
			</div>
      
      <a href="<?=getFullImageURL();?>" title="<?=getImageTitle();?>">
      <?php printDefaultSizedImage(getImageTitle()); ?></a>
      
      <?php printImageDesc(true); ?>

      
      <div id="comments" style="clear: both; padding-top: 10px;">
          <div class="commentcount"><?php $num = getCommentCount(); echo ($num == 0) ? "No comments" : (($num == 1) ? "<strong>One</strong> comment" : "<strong>$num</strong> comments"); ?> on this image:</div>
          
          <?php while (next_comment()):  ?>
            <div class="comment">
              <div class="commentmeta">
                <span class="commentauthor"><?php printCommentAuthorLink(); ?></span>
                | <span class="commentdate"><?=getCommentDate();?>, <?=getCommentTime();?> PST</span>
              </div>
              <div class="commentbody"><?=getCommentBody();?></div>            
            </div>
          <?php endwhile; ?>
          
          <div class="imgcommentform">
          <!-- If comments are on for this image AND album... -->
            <h3>Add a comment:</h3>
            <form name="commentform" id="commentform" action="#comments" method="post">
              <input type="hidden" name="comment" value="1" />
              <input type="hidden" name="remember" value="1" />
              <?php if (isset($error)) { ?><tr><td><div class="error">There was an error submitting your comment. Name, a valid e-mail address, and a comment are required.</div></td></tr><?php } ?>
              <table border="0">
                <tr><td><label for="name">Name:</label></td>    <td><input type="text" name="name" size="20" value="<?=$stored[0];?>" />  </td></tr>
                <tr><td><label for="email">E-Mail (won't be public):</label></td> <td><input type="text" name="email" size="20" value="<?=$stored[1];?>" /> </td></tr>
                <tr><td><label for="website">Site:</label></td> <td><input type="text" name="website" size="40" value="<?=$stored[2];?>" /></td></tr>
                <!--<tr><td colspan="2"><label><input type="checkbox" name="remember" <?=($stored[3]) ? "checked=\"1\"" : ""; ?>> Save my information</label></td></tr>-->
              </table>
              <textarea name="comment" rows="6" cols="40"></textarea><br />
              <input type="submit" value="Add Comment" />
            
            </form>
          </div>

      </div>
      
    </div>
</div>

</body>

<?php /* printPreloadScript(); */ ?>

</html>
