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
    if ($action == "save_edits") {
      
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
  
    <?php if ($page == "edit") { ?>
      <h1>edit photos</h1>
      
      
      
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
