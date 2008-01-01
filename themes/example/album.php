<?php 
if (!defined('WEBPATH')) die(); 
$startTime = array_sum(explode(" ",microtime())); 
$themeResult = getTheme($zenCSS, $themeColor, 'light');
$firstPageImages = normalizeColumns(1, 7);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
	<?php zenJavascript(); ?>
</head>
<body>

<div id="main">
	<div id="gallerytitle">
    	<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
  	</div>
  
  	( <?php printLink(getPrevAlbumURL(), "&laquo; Prev Album"); ?> | <?php printLink(getNextAlbumURL(), "Next Album &raquo;"); ?> )
  
  	<hr />
  	<?php printTags('links', 'Tags: ', 'taglist', ''); ?>
	<?php printAlbumDesc(true); ?>
  	<br />

  
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

	<!-- Sub-Albums -->
  	<div id="albums">
  		<?php while (next_album()): ?>
    	<div class="album">
      		<div class="albumthumb"><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        		<?php printAlbumThumbImage(getAlbumTitle()); ?></a>
            </div>
      		<div class="albumtitle">
                	<h3><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        			<?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?>
                </div>
      			<div class="albumdesc"><?php printAlbumDesc(); ?></div>
    		</div>
    		<hr />
 		<?php endwhile; ?>
  	</div>
  
  	<br />
  
  	<div id="images">
		<?php while (next_image(false, $firstPageImages)): ?>
    	<div class="image">
      		<div class="imagethumb">
            	<a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>">
        		<?php printImageThumb(getImageTitle()); ?></a>
            </div>
    	</div>

		<?php endwhile; ?>
		<br clear="all" />
    	<?php printAlbumMap(); ?>
  	</div>
 
 <!-- begin comment block -->     
      	<?php if (getOption('Allow_comments')  && getCurrentPage() == 1) { ?>
      	<div id="comments">
        	<div class="commentcount"><?php $num = getCommentCount(); echo ($num == 0) ? "No comments" : (($num == 1) ? "<strong>One</strong> comment" : "<strong>$num</strong> comments"); ?> on this alblum:</div>
          
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
			  <?php if (OpenedForComments(ALBUM)) { ?>
          		<!-- If comments are on for this album... -->
            	<h3>Add a comment:</h3>
            	<form name="commentform" id="commentform" action="#comments" method="post">
              		<input type="hidden" name="comment" value="1" />
              		<input type="hidden" name="remember" value="1" />
                    <?php printCommentErrors(); ?>
              		<table border="0">
                		<tr><td><label for="name">Name:</label></td>    <td><input type="text" name="name" size="20" value="<?php echo $stored[0];?>" />  </td></tr>
                		<tr><td><label for="email">E-Mail (won't be public):</label></td> <td><input type="text" name="email" size="20" value="<?php echo $stored[1];?>" /> </td></tr>
                		<tr><td><label for="website">Site:</label></td> <td><input type="text" name="website" size="30" value="<?php echo $stored[2];?>" /></td></tr>
                        <?php if (getOption('Use_Captcha')) { 
                          printCaptcha('<tr><td>Enter ', ':</td><td>', '</td></tr>'); 
                        } ?>
              		</table>
              		<textarea name="comment" rows="6" cols="40"></textarea><br />
              		<input type="submit" value="Add Comment" />
            	</form>
          	</div>
        <?php } else { echo 'Comments are closed.'; } ?>
      	</div>
      	<?php } ?>
<!--  end comment block -->
 
   	<?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>

  	<div id="credit">
		<?php printRSSLink('Album', '', 'Album RSS', ''); ?> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | <a href="?p=archive">Archive View</a><br />
  		<?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?>
  	</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
