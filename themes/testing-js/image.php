<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
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

    // Proof of concept javascript image navigation with lo-res display while loading... :-)
    var current = 0;
    var maxloadlevel = 2;
    
    // Fine-tune the dynamic preloading
    var highpreloadbracket = 10; // 10
    var lowpreloadbracket = 25;  // 25
    
    var currentdisplay = null;
    var bgdisplay = null;
    var images = new Array();
    
    // array(id, lowsrc, src, w, h, title, description);
<?php while (next_image(true)): ?>
    images.push(new Array(<?=getImageId(); ?>, "<?=getCustomImageURL(100); ?>","<?=getCustomImageURL(595); ?>", <?php
         $size = getSizeCustomImage(595); echo $size[0] . ', ' . $size[1]; ?>, "<?php echo addslashes(getImageTitle()); ?>", "<?php echo addslashes(getImageDesc()); ?>"));
<?php endwhile; ?>
    
    albumImages = new Array();
    loadOrder = new Array();
    

    // Preload thumbnails: (onLoad).
    function preLoadLores() {
      currentdisplay = document.getElementById('imagemain1');
      albumImages[current] = createImage(current, 2);
      albumImages[current].onload = null;
      albumImages[current].src = currentdisplay.src;
      preloadAround(current);
    }
    
    function getLevelSrc(index, level) {
      if (level > maxloadlevel) level = maxloadlevel;
      return images[index][level];
    }

    function loadImage(index, level) {
      var img = getImage(index);
      if (level > img.loadlevel) {
        img = createImage(index, level);
        img.src = getLevelSrc(index, level);
      }
      return img;
    }
    
    function setImage(index, img) {
      albumImages[index] = img;
    }
    
    function getImage(index) {
      if (albumImages[index] == null) {
        albumImages[index] = createImage(index, 0);
      }
      return albumImages[index];
    }
    
    function createImage(index, level) {
      var img = new Image();
      img.imageindex = index;
      img.loadlevel = level;
      img.imageid = images[index][0];
      img.onload = function() { handleImageLoad(this); }
      return img;
    }
    
    function handleImageLoad(img) {
      if (!img.src) return;
      var index = img.imageindex;
      if (img.loadlevel > getImage(index).loadlevel) {
        setImage(index, img);
      }
      if (current == index) {
        displayImage(current);
      }
    }
    
    function displayImage(index) {
      var img = getImage(index);
      var title = images[index][5];
      var desc = images[index][6];
      setTitle(title);
      setDesc(desc);
      if (img.loadlevel == 0) {
        var loading = document.getElementById('loading')
        hide('imagemain1');
        hide('imagemain2');
        loading.style.display = 'block';
        loading.style.backgroundColor = '#eee';
        loading.style.width  = getImageWidth(img)+'px';
        loading.style.height = getImageHeight(img)+'px';
      } else {
        hide('loading');
        hide('imagemain2');
        show('imagemain1');
        currentdisplay.width = getImageWidth(img);
        currentdisplay.height = getImageHeight(img);
        currentdisplay.src = img.src;
      }
    }
    
    function getImageWidth(img) { return images[img.imageindex][3]; }
    function getImageHeight(img) { return images[img.imageindex][4]; }

    var rapidFireDelay = 200;
    var initRapidFireDelay = 1000;
    var rapidFireTimeout = null;
    var rapidFireInit = true;
    
    function stopRapidFire() {
      clearTimeout(rapidFireTimeout);
      rapidFireInit = true;
    }
    
    function rapidFire(command) {
      var delay = rapidFireDelay;
      if (rapidFireInit) { delay = initRapidFireDelay; rapidFireInit = false; }
      rapidFireTimeout = setTimeout(command, delay);
    }

    function nextImage() {
      var next = current + 1;
      if (next >= images.length) return;
      current = next;
      switchImage(current);
      rapidFire("nextImage();");
    }
    
    function prevImage() {
      var prev = current - 1;
      if (prev < 0) return;
      current = prev;
      switchImage(current);
      rapidFire("prevImage();");
    }
    
    function switchImage(index) {
      displayImage(index);
      loadImage(index, 1);
      loadImage(index, 2);
      preloadAround(index);
    }
    
    function preloadAround(index) {
      var i = 0;
      var img;
      for (i=index+1; i<images.length && i < index+lowpreloadbracket; i++) {
        img = loadImage(i, 1);
      }
      for (i=index+1; i < images.length && i < index+highpreloadbracket; i++) {
        img = loadImage(i, 2);
      }
    }
    
    function setDesc(desc) {
      var descDiv = document.getElementById('imageDesc');
      descDiv.innerHTML = desc;
    }
    
    function setTitle(title) {
      var titleDiv = document.getElementById('imageTitle');
      titleDiv.innerHTML = title;
    }
    
    function show(id) {
      document.getElementById(id).style.display = 'block';
    }
    
    function hide(id) {
      document.getElementById(id).style.display = 'none';
    }
  
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
<body onload="preLoadLores();">

<div id="main">
  <div id="gallerytitle">
    <h2>
      <span><a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a> | 
      <?php printParentBreadcrumb(); ?>
      <a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?> Index"><?php echo getAlbumTitle();?></a> |</span>
      <?php printImageTitle(true); ?>
    </h2>
  </div>
  
  <hr />
  
  <p> </p>
  
  <div class="imgnav">
    <input class="prev" type="button" onmousedown="stopRapidFire(); prevImage();" onmouseup="stopRapidFire();" ondrag="stopRapidFire();" value="&laquo; Previous" />
    <input class="next" type="button" onmousedown="stopRapidFire(); nextImage();" onmouseup="stopRapidFire();" ondrag="stopRapidFire();" value="Next &raquo;" />
    <br style="clear: both;" />
  </div>
      
    <div class="image">

      <div class="imagedisplay">
        <img id="imagemain1" src="<?php echo getDefaultSizedImage() ?>" />
        <img id="imagemain2" src="" style="display: none;" />
        <div id="loading" style="display: none;"><img src="<?php echo $_zp_themeroot ?>/loading.gif" /></div>

        <span class="desc"><?php printImageDesc(true); ?></span>
      </div>
      
      <?php /*
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
              <?php if (isset($error)) { ?><tr><td><div class="error">There was an error submitting your comment. Name, a valid e-mail address, and a comment are required.</div></td></tr><?php } ?>
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
      */ ?>
      
      <p style="text-align: left; color: #ddd;"><?php printAdminLink("Admin"); ?>
        <?php /* Timer */ echo round((array_sum(explode(" ",microtime())) - $startTime),4)." Seconds, $_zp_query_count queries ran."; ?></p>
      
    </div>
</div>

</body>

</html>
