<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
  <?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
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
    <h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?>
          </a> | <?php printParentBreadcrumb(); ?><a href="<?php echo getAlbumLinkURL();?>" title="Album Thumbnails"><?php echo getAlbumTitle();?></a> | 
          </span> <?php printImageTitle(true); ?></h2>
  </div>
  
  <hr />
  <p> </p>
    <div class="image">
			<div class="imgnav">
				<?php if (hasPrevImage()) { ?> <a class="prev" href="<?php echo getPrevImageURL();?>" title="Previous Image">&laquo; prev</a>
				<?php } if (hasNextImage()) { ?> <a class="next" href="<?php echo getNextImageURL();?>" title="Next Image">next &raquo;</a><?php } ?>
			</div>
      
      <a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>">
      <?php printDefaultSizedImage(getImageTitle()); ?></a>

      <div style="font-size: 8pt; text-align: right;"><em>
        <a href="<?php echo getFullImageURL();?>" title="<?php echo getImageTitle();?>">Original Size: 
          <?php echo getFullWidth() . "x" . getFullHeight(); ?>
        </a></em>
      </div>

      
      <?php printImageDesc(true); ?>
      <?php printImageEXIFData(); ?>
<?php printImageMap(); ?>

      
      <?php if (getOption('Allow_comments')) { ?>
      <div id="comments" style="clear: both; padding-top: 10px;">
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
          <!-- If comments are on for this image AND album... -->
            <h3>Add a comment:</h3>
            <form name="commentform" id="commentform" action="#comments" method="post">
              <input type="hidden" name="comment" value="1" />
              <input type="hidden" name="remember" value="1" />
              <?php 
              if (isset($error)) { 
                echo "<tr>";
                  echo "<td>";
                    echo '<div class="error">';
                    if ($error == 1) {
                      echo "There was an error submitting your comment. Name, a valid e-mail address, and a spam-free comment are required.";
                    } else {
                      echo "Your comment has been marked for moderation.";
                    }
                    echo "</div>";
                  echo "</td>";
                echo  "</tr>";
              } 
              ?>
              <table border="0">
                <tr><td><label for="name">Name:</label></td>    <td><input type="text" name="name" size="20" value="<?php echo $stored[0];?>" />  </td></tr>
                <tr><td><label for="email">E-Mail (won't be public):</label></td> <td><input type="text" name="email" size="20" value="<?php echo $stored[1];?>" /> </td></tr>
                <tr><td><label for="website">Site:</label></td> <td><input type="text" name="website" size="40" value="<?php echo $stored[2];?>" /></td></tr>
                <!--<tr><td colspan="2"><label><input type="checkbox" name="remember" <?php echo ($stored[3]) ? "checked=\"1\"" : ""; ?>> Save my information</label></td></tr>-->
              </table>
              <textarea name="comment" rows="6" cols="40"></textarea><br />
              <input type="submit" value="Add Comment" />
            
            </form>
          </div>

      </div>
      <?php } ?>
      
    </div>
    
    <div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a><br />
    <?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?></div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
