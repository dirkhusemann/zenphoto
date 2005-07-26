<?php require_once("classes.php"); /* Don't put anything before this line! */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>zenphoto administration</title>
    <link rel="stylesheet" href="admin.css" type="text/css" />
  </head>
  
  <body>

<?php if (!zp_loggedin()) {  /* Display the login form and exit. */ ?>
  
  <div id="loginform">
  
  <form name="login" action="#" method="POST">
    <input type="hidden" name="login" value="1" />
    <table>
      <tr><td>Login</td><td><input class="textfield" name="user" type="text" size="20" /></td></tr>
      <tr><td>Password</td><td><input class="textfield" name="pass" type="password" size="20" /></td></tr>
      <tr><td colspan="2"><input class="button" type="submit" value="Log in" /></td></tr>
    </table>
  </form>
  
  </div>

<?php } else { /* Display the admin pages. Do action handling first. */
  
  $gallery = new Gallery();
  $gallery->garbageCollect();
  // Full garbage collection is too slow... only perform when needed.
  // $gallery->garbageCollect(true, true);
  
  if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == "save") {
      if ($_POST['album'] && $_POST['totalimages']) {
        $folder = strip($_POST['album']);
        $album = new Album($gallery, $folder);
        $album->setTitle(strip($_POST['albumtitle']));
        $album->setDesc(strip($_POST['albumdesc']));
        // FIXME: Date entry isn't ready yet...
        // $album->setDate(strip($_POST["albumdate"]));
        $album->setPlace(strip($_POST['albumplace']));
        
        for ($i = 0; $i < $_POST['totalimages']; $i++) {
          $filename = strip($_POST["$i-filename"]);
          $image = new Image($album, $filename);
          $image->setTitle(strip($_POST["$i-title"]));
          $image->setDesc(strip($_POST["$i-desc"]));          
        }
      } else if ($_POST['totalalbums']) {
        
        for ($i = 0; $i < $_POST['totalalbums']; $i++) {
          $folder = strip($_POST["$i-folder"]);
          $album = new Album($gallery, $folder);
          $album->setTitle(strip($_POST["$i-title"]));
          $album->setDesc(strip($_POST["$i-desc"]));
          // FIXME: Date entry isn't ready yet...
          // $album->setDate(strip($_POST["$i-date"]));
          $album->setPlace(strip($_POST["$i-place"]));
        }
      }
      // header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/admin/?page=edit");
      
      
    } else if ($action == "upload") {
      
    }
  }
  
  if (isset($_GET['page'])) { $page = $_GET['page']; } else { $page = "home"; }
?>
<div id="main">
  <div id="links"><a href="../">view gallery</a> &nbsp; <a href="?logout">logout</a></div>
  <ul id="nav">
    <li<?= $page == "home" ? " class=\"current\"" : "" ?>><a href="?page=home">overview</a></li>
    <li<?= $page == "comments" ? " class=\"current\"" : "" ?>><a href="?page=comments">comments</a></li>
    <li<?= $page == "upload" ? " class=\"current\"" : "" ?>><a href="?page=upload">upload</a></li>
    <li<?= $page == "edit" ? " class=\"current\"" : "" ?>><a href="?page=edit">edit</a></li>
  </ul>
  
  
  <div id="content">
  
<?php /************************************************************************************/ ?>
  
    <?php if ($page == "edit") { ?>
      
      <?php if (isset($_GET['album'])) {
        $folder = strip($_GET['album']);
        $album = new Album($gallery, $folder);
        ?>
        <h1>edit photos</h1>
        <p><a href="?page=edit" title="Back to the list of albums">&laquo; back to the list</a></p>
        <form name="albumedit" action="?page=edit&action=save" method="post">
          <input type="hidden" name="album" value="<?= $album->name; ?>" />
        
          <div class="box" style="padding: 15px;">
            <h2>editing <em><?=$album->getTitle(); ?></em></h2>
            <table>
              <tr><td align="right" valign="top">Album Title: </td> <td><input type="text" name="albumtitle" value="<?=$album->getTitle(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Album Description: </td> <td><textarea name="albumdesc" cols="60" rows="6"><?=$album->getDesc(); ?></textarea></td></tr>
              <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="albumdate" value="<?=$album->getDateTime(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Place: </td> <td><input type="text" name="albumplace" value="<?=$album->getPlace(); ?>" /></td></tr>
            </table>
          </div>

          <?php $images = $album->getImages();
          $totalimages = sizeof($images); ?> 
          <input type="hidden" name="totalimages" value="<?= $totalimages; ?>" />
          
          <p><input type="submit" value="save" /></p>
          <hr />
          
          <table id="edittable">
           
            <?php
            $currentimage = 0;
            foreach ($images as $filename) {
              $image = new Image($album, $filename);
            ?>
            
            <tr id=""<?= ($currentimage % 2 == 0) ?  "class=\"alt\"" : "" ?>>
              <td valign="top">
                <img src="<?=$image->getThumb();?>" alt="<?=$image->filename;?>" />
              </td>
  
              <td>
                <input type="hidden" name="<?= $currentimage; ?>-filename" value="<?= $image->filename; ?>" />
                Title: <input type="text" size="57" name="<?= $currentimage; ?>-title" value="<?= $image->getTitle(); ?>" /><br />
                Description: <br />
                <textarea name="<?= $currentimage; ?>-desc" cols="60" rows="4"><?= $image->getDesc(); ?></textarea>
                <br /><br />
                
              </td>
                
            </tr>
            
            <?php 
              $currentimage++;
            } 
            ?>
            <tr><td> </td> <td><input type="submit" value="save" /></td></tr>
          </table>
          
          
          <p><a href="?page=edit" title="Back to the list of albums">&laquo; back to the list</a></p>
        </form>
        
<?php /************************************************************************************/ ?>
        
      <?php } else if (isset($_GET['massedit'])) { 
      ?>
      <h1>edit albums</h1>
      <p><a href="?page=edit" title="Back to the list of albums">&laquo; back to the list</a></p>
      <form name="albumedit" action="?page=edit&action=save" method="POST">
        <p><input type="submit" value="save" /> &nbsp; <input type="reset" value="reset" /></p>
      <?php
        $albums = $gallery->getAlbums();
        ?> <input type="hidden" name="totalalbums" value="<?= sizeof($albums); ?>" /> <?php
        $currentalbum = 0;
        foreach ($albums as $folder) { 
          $album = new Album($gallery, $folder);
      ?>
        <input type="hidden" name="<?= $currentalbum; ?>-folder" value="<?= $album->name; ?>" />
        <table>
          <tr><td rowspan="4" valign="top"><a href="?page=edit&album=<?= $album->name; ?>" title="Edit this album: <?= $album->name; ?>"><img src="<?= $album->getAlbumThumb(); ?>" /></a></td>
            <td align="right" valign="top">Album Title: </td> <td><input type="text" name="<?= $currentalbum; ?>-title" value="<?=$album->getTitle(); ?>" /></td></tr>
          <tr><td align="right" valign="top">Album Description: </td> <td><textarea name="<?= $currentalbum; ?>-desc" cols="60" rows="6"><?=$album->getDesc(); ?></textarea></td></tr>
          <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="<?= $currentalbum; ?>-date" value="<?=$album->getDateTime(); ?>" /></td></tr>
          <tr><td align="right" valign="top">Place: </td> <td><input type="text" name="<?= $currentalbum; ?>-place" value="<?=$album->getPlace(); ?>" /></td></tr>
        </table>
        <hr />
        
      <?php 
          $currentalbum++;
        } 
      ?>
      
        <p><input type="submit" value="save" /> &nbsp; <input type="reset" value="reset" /></p>
      
      </form>
        
<?php /************************************************************************************/ ?> 
        
      <?php } else { /* Display a list of albums to edit. */ ?>
        <h1>edit</h1>
        <h2>Choose an album, or <a href="?page=edit&massedit">mass-edit album data</a>.</h2>
        
        <table>

        <?php 
          $albums = $gallery->getAlbums();
          foreach ($albums as $folder) { 
            $album = new Album($gallery, $folder);
        ?>
            <tr>
              <td><a href="?page=edit&album=<?= $album->name; ?>" title="Edit this album: <?= $album->name; ?>"><img height="40" width="40" src="<?= $album->getAlbumThumb(); ?>" /></a></td> 
              <td><a href="?page=edit&album=<?= $album->name; ?>" title="Edit this album: <?= $album->name; ?>"><?= $album->getTitle(); ?></td>
            </tr>

          <?php } ?>
        
        </table>

<?php /************************************************************************************/ ?>


      <?php } ?>
      
    <?php } else if ($page == "upload") { ?>
      <h1>upload photos</h1>
      
      
      
    <?php } else if ($page == "comments") { ?>
      <h1>comments</h1>
      
      
      
    <?php } else { $page = "home"; ?>
      <h1>zenphoto administration</h1>
      
      <ul id="home-actions">
        <li><a href="?page=upload"> &raquo; <strong>Upload</strong> pictures.</a></li>
        <li><a href="?page=edit"> &raquo; <strong>Edit</strong> titles, descriptions, and other metadata.</a></li>
        <li><a href="?page=comments"> &raquo; Edit or delete <strong>comments</strong>.</a></li>
        <li><a href="../"> &raquo; Browse your <strong>gallery</strong> and edit on the go.</a></li>
      </ul>
      
      <hr />
      
      <div class="box" id="overview-comments">
        <h2>10 Most Recent Comments</h2>
        <ul>
        <?php
          $comments = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
            . " c.date, c.comment FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a "
            . " WHERE c.imageid = i.id AND i.albumid = a.id ORDER BY c.id DESC LIMIT 10");
          foreach ($comments as $comment) {
            $author = $comment['name'];
            $album = $comment['folder'];
            $image = $comment['filename'];
            $albumtitle = $comment['albumtitle'];
            if ($comment['title'] == "") $title = $image; else $title = $comment['title'];
            $website = $comment['website'];
            $comment = truncate_string($comment['comment'], 123);
            echo "<li><div class=\"commentmeta\">$author commented on <a href=\""
              . (zp_conf("mod_rewrite") ? "../$album/$image" : "../image.php?album=".urlencode($album)."&image=".urlencode($image))
              . "\">$albumtitle / $title</a>:</div><div class=\"commentbody\">$comment</div></li>";
          }
        ?>
        </ul>
      </div>
      
      
      <div class="box" id="overview-stats">
        <h2 class="boxtitle">Gallery Stats</h2>
        <ul>
          <li><strong><?= $gallery->getNumImages(); ?></strong> images in <strong><?= $gallery->getNumAlbums(); ?></strong> albums.</li>
          <li><strong><?= $gallery->getNumComments(); ?></strong> total comments.</li>
        </ul>
      </div>
      
      
      <div class="box" id="overview-suggest">
        <h2 class="boxtitle">Suggestions</h2>
          <h3>Add titles to...</h3>
          
          <h3>Add descriptions to...</h3>
          
      </div>
      
    <?php } ?>
    
    <div id="footer"></div>
  </div>
</div>

  
<?php } /* No admin-only content allowed after this bracket! */ ?>
    
  </body>
</html>
