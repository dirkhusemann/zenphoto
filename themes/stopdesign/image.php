<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo getImageTitle();?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
  <script type="text/javascript">var blogrelurl = "/zp/";</script>
  <script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/rememberMe.js"></script>
  <script type="text/javascript" src="<?php echo $_zp_themeroot ?>/js/comments.js"></script>
  <?php 
    printRSSHeaderLink('Gallery','Gallery RSS');
    zenJavascript(); 
    setOption('thumb_crop_width', 85, false);
    setOption('thumb_crop_height', 85, false);
    global $_zp_current_image; 
  ?>
</head>

<body class="photosolo">
    <?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

    <div id="content" class="v">

        <div id="desc">
          <h1><?php printImageTitle(true); ?></h1>
          <p><em><?php printImageDesc(true); ?></em></p>
        </div>

        <div class="main">
          <p id="photo"><strong><?php printCustomSizedImage(getImageTitle(), null, 480); ?></strong></p>
        </div>

        <div id="meta">
          <ul>
            <li class="count"><?php if (($num = getNumImages()) > 1) { echo imageNumber() . " of " . getNumImages() . " photos"; }?></li>
            <li class="date"><?php printImageDate(); ?></li>
            <li class="tags"><?php echo getAlbumPlace(); ?></li>
          </ul>
        </div>

        <div class="main">
            <?php if (getOption('Allow_comments')) { ?>
            <!-- BEGIN #commentblock -->
            <div id="commentblock">

                  <h2><?php $showhide = "<a href=\"#comments\" id=\"showcomments\"><img src=\"" . $_zp_themeroot . "/img/btn_show.gif\" width=\"35\" height=\"11\" alt=\"SHOW\" /></a> <a href=\"#content\" id=\"hidecomments\"><img src=\"".$_zp_themeroot."/img/btn_hide.gif\" width=\"35\" height=\"11\" alt=\"HIDE\" /></a>"; $num = getCommentCount(); if ($num == 0) echo "<h2>No comments yet</h2>"; if ($num == 1) echo "<h2>1 comment so far $showhide</h2>"; if ($num > 1) echo "<h2>$num comments so far $showhide</h2>"; ?></h2>
                  
                <!-- BEGIN #comments -->
                <div id="comments">
                    <dl class="commentlist">
                        <?php 
                          $autonumber = 0;
                          while (next_comment()):  
                          $autonumber++;
                          ?>
                        <dt id="comment<?php echo $autonmuber; ?>">
                              <a href="#comment<?php echo $autonumber; ?>" class="postno" title="Link to Comment <?php echo $autonumber; ?>"><?php echo $autonumber; ?>.</a>
                              <em>On <?php echo getCommentDate();?>, <?php printCommentAuthorLink(); ?> wrote:</em>

                        </dt>
                        <dd><p><?php echo getCommentBody();?><?php printEditCommentLink('Edit', ' | ', ''); ?></p></dd>
                        <?php endwhile; ?>
                    </dl>
                
                    <p class="mainbutton" id="addcommentbutton"><a href="#addcomment" class="btn"><img src="<?php echo $_zp_themeroot ?>/img/btn_add_a_comment.gif" alt="" width="116" height="21" /></a></p>

                    <!-- BEGIN #addcomment -->
                    <div id="addcomment" style="display: none;">
                        <h2>Add a comment</h2>
                        <form method="post" action="#" id="comments-form">
                            <input type="hidden" name="comment" value="1" />
                              <input type="hidden" name="remember" value="1" />
                            <?php 
                                    if (isset($error)) { 
                                    echo "<tr>\n<td>\n<div class=\"error\">";
                                    if ($error == 1) {
                                            echo "There was an error submitting your comment. Name, a valid e-mail address, and a spam-free comment are required.";
                                    } else {
                                            echo "Your comment has been marked for moderation.";
                                    }
                                    echo "</div>\n</td>\n</tr>";
                                    } 
                              ?>
                            <table cellspacing="0">
                                <tr valign="top" align="left" id="row-name">
                                    <th><label for="name">Name:</label></th>
                                    <td><input tabindex="1" id="name" name="name" class="text" value="<?php echo $stored[0];?>" /></td>
                                </tr>

                                  <tr valign="top" align="left" id="row-email">
                                    <th><label for="email">Email:</label></th>
                                    <td><input tabindex="2" id="email" name="email" class="text" value="<?php echo $stored[1];?>" /> <em>(not displayed)</em></td>
                                  </tr>

                                  <tr valign="top" align="left">
                                    <th><label for="website">URL:</label></th>
                                    <td><input tabindex="3" type="text" name="website" id="website" class="text" value="<?php echo $stored[2];?>" /></td>
                                  </tr>

                                  <tr valign="top" align="left">
                                    <th><label for="comment">Comment:</label></th>
                                    <td><textarea tabindex="4" id="comment" name="comment" rows="10" cols="40"></textarea></td>
                                  </tr>

                                  <tr valign="top" align="left">
                                    <th class="buttons">&nbsp;</th>
                                    <td class="buttons">
                                        <!--<input type="submit" name="preview" tabindex="5" value="Preview" id="btn-preview" />--> <input type="submit" name="post" tabindex="6" value="Post" id="btn-post" />
                                        <p>Avoid clicking &ldquo;Post&rdquo; more than once.</p>
                                    </td>
                                  </tr>

                              </table>
                        </form>

                    </div>
                    <!-- END #addcomment -->
                    
                </div>
                <!-- END #comments -->

            </div>
            <!-- END #commentblock -->
            <?php } ?>

        </div>

        <div id="prevnext">
            <?php if (hasPrevImage()) { ?>
              <div id="prev"><span class="thumb"><span>
                <em style="background-image:url('<?php echo getPrevImageThumb(); ?>')"><a href="<?php echo getPrevImageURL();?>" accesskey="z" style="background:#fff;"><strong style="width:190px; height:300px;">Previous: </strong>Crescent</a></em></span></span></div>
            <?php } if (hasNextImage()) { ?>
              <div id="next"><span class="thumb"><span>
            <em style="background-image:url('<?php echo getNextImageThumb(); ?>')"><a href="<?php echo getNextImageURL();?>" accesskey="x" style="background:#fff;"><strong style="width:190px; height:300px;">Next: </strong>Sagamor</a></em></span></span></div>
            <?php } ?>
        </div>

    </div>

    <p id="path"><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> > <?php printParentBreadcrumb("", " > ", " > "); ?> <a href="<?php echo getAlbumLinkURL();?>" title="Album Thumbnails"><?php echo getAlbumTitle();?></a> > <?php echo getImageTitle(); ?></p>

    <div id="footer">
          <hr />
          <p>
            <a href="http://stopdesign.com/templates/photos/">Photo Templates</a> from Stopdesign.
            Powered by <a href="http://www.zenphoto.org">ZenPhoto</a>.
          </p>
    </div>
    <?php printAdminToolbox(); ?>
</body>
</html>
