<?php  /* Don't put anything before this line! */ 
require_once("classes.php");

if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */
  
  $gallery = new Gallery();
  if (isset($_GET['prune'])) {
    $gallery->garbageCollect(true, true);
    header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php");
  } else {
    $gallery->garbageCollect();
  }
  
  if (isset($_GET['action'])) {
    $action = $_GET['action'];

/** SAVE **********************************************************************/
/*****************************************************************************/
    if ($action == "save") {
/** SAVE A SINGLE ALBUM *******************************************************/
      if ($_POST['album'] && $_POST['totalimages']) {
        $folder = strip($_POST['album']);
        $album = new Album($gallery, $folder);
        $album->setTitle(strip($_POST['albumtitle']));
        $album->setDesc(strip($_POST['albumdesc']));
        // FIXME: Date entry isn't ready yet...
        // $album->setDate(strip($_POST["albumdate"]));
        $album->setPlace(strip($_POST['albumplace']));
        $album->setAlbumThumb(strip($_POST['thumb']));
        
        for ($i = 0; $i < $_POST['totalimages']; $i++) {
          $filename = strip($_POST["$i-filename"]);
          
          // The file might no longer exist
          if (file_exists($filename)) {
            $image = new Image($album, $filename);
            $image->setTitle(strip($_POST["$i-title"]));
            $image->setDesc(strip($_POST["$i-desc"]));          
          }
          // TODO: delete it from the db? This should happen somewhere..
        }
        
/** SAVE MULTIPLE ALBUMS ******************************************************/
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
      header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=edit");
      exit();
      
/** UPLOAD IMAGES *************************************************************/
/*****************************************************************************/
    } else if ($action == "upload") {
      
      // Check for files.
      $files_empty = true;
      if (isset($_FILES['files']))
        foreach($_FILES['files']['name'] as $name) { if (!empty($name)) $files_empty = false; }
      
      // Make sure the folder exists. If not, create it.
      if (isset($_POST['processed']) 
          && !empty($_POST['folder']) 
          && !$files_empty) {
        
        $folder = strip($_POST['folder']);
        $uploaddir = SERVERPATH . '/albums/' . $folder;
        if (!is_dir($uploaddir)) {
          mkdir ($uploaddir, 0777);
        }
        chmod($uploaddir,0777);
        
        $error = false;
        foreach ($_FILES['files']['error'] as $key => $error) {
          if ($_FILES['files']['name'][$key] == "") continue;
          if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['files']['tmp_name'][$key];
            $name = $_FILES['files']['name'][$key];
            if (is_image($name)) {
              $uploadfile = $uploaddir . '/' . $name;
              move_uploaded_file($tmp_name, $uploadfile);
              @chmod($uploadfile, 0755);
            } else if (is_zip($name)) {
              unzip($tmp_name, $uploaddir);
            }
          }
        }
        
        $album = new Album($gallery, $folder);
        $title = strip($_POST['albumtitle']);
        if (!empty($title)) {
          $album->setTitle($title);
        }
        
        header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=edit&album=$folder");
        exit();
        
      } else {
        // Handle the error and return to the upload page.
        $page = "upload";
        $error = true;
        if ($files_empty) {
          $errormsg = "You must upload at least one file.";
        } else if (empty($_POST['albumtitle'])) {
          $errormsg = "You must enter a title for your new album.";
        } else if (empty($_POST['folder'])) {
          $errormsg = "You must enter a folder name for your new album.";
        } else if (empty($_POST['processed'])) {
          $errormsg = "You've most likely exceeded the upload limits. Try uploading fewer files at a time, or use a ZIP file.";
          
        } else {
          $errormsg = "There was an error submitting the form. Please try again. If this keeps happening, check your "
            . "server and PHP configuration (make sure file uploads are enabled, and upload_max_filesize is set high enough). "
            . "If you think this is a bug, file a bug report. Thanks!";
        }
      }
      
/** COMMENTS ******************************************************************/
/*****************************************************************************/

    } else if ($action == 'deletecomments') {      
      if (isset($_POST['ids']) || isset($_GET['id'])) {
        if (isset($_GET['id'])) {
          $ids = array($_GET['id']);
        } else { 
          $ids = $_POST['ids'];
        }
        $total = count($ids);
        if ($total > 0) {
          $n = 0;
          $sql = "DELETE FROM ".prefix('comments')." WHERE ";
          foreach ($ids as $id) {
            $n++;
            $sql .= "id='$id' ";
            if ($n < $total) $sql .= "OR ";
          }
          query($sql);
        }
        header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=comments&ndeleted=$n");
        exit();
      } else {
        header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=comments&ndeleted=0");
        exit();
      }
      
      
    } else if ($action == 'savecomment') {
      if (!isset($_POST['id'])) {
        header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=comments");
        exit();
      }
      $id = $_POST['id'];
      $name = $_POST['name'];
      $email = $_POST['email'];
      $website = $_POST['website'];
      $date = $_POST['date'];
      $comment = $_POST['comment'];
      
      // TODO: Update date as well; no good input yet, so leaving out.
      $sql = "UPDATE ".prefix('comments')." SET name = '$name', email = '$email', website = '$website', comment = '$comment' WHERE id = $id";
      query($sql);
      
      header("Location: http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/zen/admin.php?page=comments&sedit");
      exit();
      
      
/** OPTIONS ******************************************************************/
/*****************************************************************************/

    } else if ($action == 'settheme') {
      if (isset($_GET['theme'])) {
        $gallery->setCurrentTheme($_GET['theme']);
      }
    }
    
  }
  
  // Redirect to a page if it's set 
  // (NOTE: Form POST data will be resent on refresh. Use header(Location...) instead, unless there's an error message.
  if (isset($_GET['page'])) { $page = $_GET['page']; } else if (empty($page)) { $page = "home"; }
  
} /* NO Admin-only content between this and the next check. */
  
/************************************************************************************/
/** End Action Handling *************************************************************/
/************************************************************************************/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>zenphoto administration</title>
	<link rel="stylesheet" href="admin.css" type="text/css" />
	<script type="text/javascript" src="admin.js"></script>
</head>
  
<body>
  
<?php if (!zp_loggedin()) {  /* Display the login form and exit. */ ?>
  
	<p><img src="../zen/images/zen-logo.gif" title="Zen Photo" /></p>

    <div id="loginform">
    <form name="login" action="#" method="POST">
      <input type="hidden" name="login" value="1" />
      
      <? /* TODO: (todd) I think this is where the admin link bug is */ ?>
      <input type="hidden" name="redirect" value="/zen/admin.php" />
      <table>
        <tr><td>Login</td><td><input class="textfield" name="user" type="text" size="20" /></td></tr>
        <tr><td>Password</td><td><input class="textfield" name="pass" type="password" size="20" /></td></tr>
        <tr><td colspan="2"><input class="button" type="submit" value="Log in" /></td></tr>
      </table>
    </form>
    
    </div>
        
  </body>
</html>
    
<?php 
    exit(); 
  
  } else { /* Admin-only content safe from here on. */ ?>

<a href="<?= WEBPATH . "/zen/admin.php" ?>" id="logo"><img src="../zen/images/zen-logo.gif" title="Zen Photo" /></a>
<div id="links"><a href="../">View Gallery</a> &nbsp; | &nbsp; <a href="?logout">Log Out</a></div>

<div id="main">
  <ul id="nav">
    <li<?= $page == "home" ? " class=\"current\"" : "" ?>><a href="?page=home">overview</a></li>
    <li<?= $page == "comments" ? " class=\"current\"" : "" ?>><a href="?page=comments">comments</a></li>
    <li<?= $page == "upload" ? " class=\"current\"" : "" ?>><a href="?page=upload">upload</a></li>
    <li<?= $page == "edit" ? " class=\"current\"" : "" ?>><a href="?page=edit">edit</a></li>
    <li<?= $page == "options" ? " class=\"current\"" : "" ?>><a href="?page=options">options</a></li>
  </ul>
  
  
  <div id="content">
  
  
  
  
  
<?php /** EDIT ****************************************************************************/
      /************************************************************************************/ ?> 
  
    <?php if ($page == "edit") { ?>
      
      
      
<?php /** SINGLE ALBUM ********************************************************************/ ?>
      <?php if (isset($_GET['album'])) {
        $folder = strip($_GET['album']);
        $album = new Album($gallery, $folder);
        $images = $album->getImages();
        $totalimages = sizeof($images);
        ?>
        <h1>Edit Album</h1>
        <p><a href="?page=edit" title="Back to the list of albums">&laquo; back to the list</a> | 
        <a href="<?php echo WEBPATH."/zen/albumsort.php?album=$album->name"; ?>">Sort Album</a>
        </p>
        <form name="albumedit" action="?page=edit&action=save" method="post">
          <input type="hidden" name="album" value="<?= $album->name; ?>" />
        
          <div class="box" style="padding: 15px;">
            <h2>editing <em><?=$album->getTitle(); ?></em></h2>
            <table>
              <tr><td align="right" valign="top">Album Title: </td> <td><input type="text" name="albumtitle" value="<?=$album->getTitle(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Album Description: </td> <td><textarea name="albumdesc" cols="60" rows="6"><?=$album->getDesc(); ?></textarea></td></tr>
              <?php /* Removing date entry for now... */ 
                /* <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="albumdate" value="<?=$album->getDateTime(); ?>" /></td></tr> */ ?>
              <tr><td align="right" valign="top">Place: </td> <td><input type="text" name="albumplace" value="<?=$album->getPlace(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Thumbnail: </td> 
                <td>
                  <select id="thumbselect" class="thumbselect" name="thumb" onchange="updateThumbPreview(this)">
                    <?php foreach ($images as $image) { 
                        $filename = $image->getFileName();
                        $selected = ($filename == $album->meta['thumb']); ?>
                        <option class="thumboption" style="background-image: url(<?= $image->getThumb(); ?>); background-repeat: no-repeat;" 
                          value="<?= $filename ?>" <?php if ($selected) echo ' selected="selected"'; ?>><?= $image->meta['title'] ?><?= ($filename != $image->meta['title']) ? " ($filename)" : "" ?></option>
                    <?php } ?>
                  </select>
                  <script type="text/javascript">updateThumbPreview(document.getElementById('thumbselect'));</script>
                </td>
              </tr>
            </table>
          </div>

          
          <input type="hidden" name="totalimages" value="<?= $totalimages; ?>" />
          
          <p><input type="submit" value="save" /></p>
          <hr />
          <p>Click the images for a larger version</p>
          
          <table id="edittable">
           
            <?php
            $currentimage = 0;
            foreach ($images as $image) {
            ?>
            
            <tr id=""<?= ($currentimage % 2 == 0) ?  "class=\"alt\"" : "" ?>>
              <td valign="top">
                <img id="thumb-<?= $currentimage ?>" src="<?=$image->getThumb();?>" alt="<?=$image->filename;?>" 
                  onclick="toggleBigImage('thumb-<?= $currentimage ?>', '<?= $image->getSizedImage(zp_conf('image_size')) ?>');" />
              </td>
  
              <td>
                <input type="hidden" name="<?= $currentimage; ?>-filename" value="<?= $image->filename; ?>" />
                Title: <input type="text" size="57" name="<?= $currentimage; ?>-title" value="<?= $image->getTitle(); ?>" /><br />
                Description: <br />
                <textarea name="<?= $currentimage; ?>-desc" cols="60" rows="4"><?= $image->getDesc(); ?></textarea>
                <br /><br />
                
              </td>
              <?php /*
              <td style="padding-left: 1em;">
                <a href="?page=edit&action=delete&album=<?= $album->name; ?>&image=<?= $filename ?>" title="Delete the image <?= $filename ?>"><img src="images/delete.gif" style="border: 0px;" alt="x" /></a>
              </td>
              */ ?>
                
            </tr>
            
            <?php 
              $currentimage++;
            } 
            ?>
            <tr><td> </td> <td><input type="submit" value="save" /></td></tr>
          </table>
          
          
          <p><a href="?page=edit" title="Back to the list of albums">&laquo; back to the list</a></p>
        </form>
        
        
        
        
<?php /*** MULTI-ALBUM ***************************************************************************/ ?>
        
      <?php } else if (isset($_GET['massedit'])) { 
      ?>
      <h1>Edit All Albums</h1>
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
          
          <?php /* Removing date entry for now... */ 
                /* <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="<?= $currentalbum; ?>-date" value="<?=$album->getDateTime(); ?>" /></td></tr> */ ?>
          
          <tr><td align="right" valign="top">Place: </td> <td><input type="text" name="<?= $currentalbum; ?>-place" value="<?=$album->getPlace(); ?>" /></td></tr>
        </table>
        <hr />
        
      <?php 
          $currentalbum++;
        } 
      ?>
      
        <p><input type="submit" value="save" /> &nbsp; <input type="reset" value="reset" /></p>
      
      </form>
        
      
      
      
<?php /*** EDIT ALBUM SELECTION *********************************************************************/ ?> 
        
      <?php } else { /* Display a list of albums to edit. */ ?>
        <h1>Edit Albums</h1>
        <p>Select an album to edit it's description and data, or <a href="?page=edit&massedit">mass-edit all album data</a>.</p>
        
        <table class="bordered">
          <tr>
            <th style="width: 60px; ">Thumb</th>
            <th>Edit this album</th>
            <?php /* <th>Delete</th> */ ?>
          
          </tr>

        <?php 
          $albums = $gallery->getAlbums();
          foreach ($albums as $folder) { 
            $album = new Album($gallery, $folder);
        ?>
            <tr>
              <td>
                <a href="?page=edit&album=<?= $album->name; ?>" title="Edit this album: <?= $album->name; ?>"><img height="40" width="40" src="<?= $album->getAlbumThumb(); ?>" /></a>
              </td> 
              <td>
                <a href="?page=edit&album=<?= $album->name; ?>" title="Edit this album: <?= $album->name; ?>"><?= $album->getTitle(); ?></a>
              </td>
              <?php /*
              <td>
                <a class="delete" href="?page=edit&action=delete&album=<?= $album->name; ?>" title="Delete the album <?=$album->name; ?>"><img src="images/delete.gif" style="border: 0px;" alt="x" /></a>
              </td>
              */ ?>
            </tr>

          <?php } ?>
        
        </table>




      <?php } ?>
      
      
      
      
      
      
<?php /**** UPLOAD ************************************************************************/ 
      /************************************************************************************/ ?> 

    <?php } else if ($page == "upload") { ?>
      
      <script type="text/javascript">
        window.totalinputs = 5;
        // Array of album names for javascript functions.
        var albumArray = new Array ( <?php 
          $first = true;
          foreach ($gallery->getAlbums() as $album) {
            echo ($first ? "" : ", ") . "'" . addslashes($album) . "'";
            $first = false;
          }
        ?> );
      
      </script>
      
      <h1>Upload Photos</h1>
      <p>This web-based upload accepts image formats: <acronym title="Joint Picture Expert's Group">JPEG</acronym>, 
      <acronym title="Portable Network Graphics">PNG</acronym> and <acronym title="Graphics Interchange Format">GIF</acronym>.
	  You can also upload a <strong>ZIP</strong> archive containing either of those file types.</p>
	  <p><em>Note:</em> When uploading archives, <strong>all</strong> images in the archive are added to the album, regardles of directory structure.</p>
      <p>The maximum size for any one file is <strong><?php echo ini_get('upload_max_filesize'); ?>B</strong>. Don't forget, you can also use <acronym title="File Transfer Protocol">FTP</acronym>!</p>
      
      <?php if ($error) { ?>
        <div class="errorbox">
          <h2>Something went wrong...</h2>
          <?php echo (empty($errormsg) ? "There was an error submitting the form. Please try again." : $errormsg); ?>
        </div>
      <?php } ?>
      
      <form name="uploadform" enctype="multipart/form-data" action="?action=upload" method="POST">
        <input type="hidden" name="processed" value="1" />
        

        
        <div id="albumselect">
          Upload to: 
          <select id="" name="albumselect" onChange="albumSwitch(this)">
            <option value="" selected="true">a New Album +</option>
          <?php $albums = $gallery->getAlbums(); foreach($albums as $folder) { $album = new Album($gallery, $folder); ?>
            <option value="<?=$album->name;?>"><?=$album->getTitle();?></option>
          <?php } ?>
          </select>
          
          <div id="albumtext" style="margin-top: 5px;"> 
            called: <input id="albumtitle" size="22" type="text" name="albumtitle" value="" onkeyup="updateFolder(this, 'folderdisplay', 'autogen');" /> 
            in the folder named: 
            <div style="position: relative; display: inline;">
              <div id="foldererror" style="display: none; color: #D66; position: absolute; z-index: 100; top: -2em; left: 0px;">That name is already used.</div>
              <input id="folderdisplay" size="18" type="text" name="folderdisplay" disabled="true" onkeyup="validateFolder(this);"/> 
            </div>
            <label><input type="checkbox" name="autogenfolder" id="autogen" checked="true" onClick="toggleAutogen('folderdisplay', 'albumtitle', this);" /> Auto-generate folder names</label>
            <input type="hidden" name="folder" value="" />
          </div>
          
        </div>
        
        <hr />
        
        <!-- This first one is the template that others are copied from -->
        <div class="fileuploadbox" id="filetemplate">
          <input type="file" size="40" name="files[]" />
        </div>
        <div class="fileuploadbox">
          <input type="file" size="40" name="files[]" />
        </div>
        <div class="fileuploadbox">
          <input type="file" size="40" name="files[]" />
        </div>
        <div class="fileuploadbox">
          <input type="file" size="40" name="files[]" />
        </div>
        <div class="fileuploadbox">
          <input type="file" size="40" name="files[]" />
        </div>

        <div id="place" style="display: none;"></div><!-- New boxes get inserted before this -->
        
        <p><a href="javascript:addUploadBoxes('place','filetemplate',5)" title="Doesn't reload!">+ Add more upload boxes</a> <small>(won't reload the page, but remember your upload limits!)</small></p>
        
        
        <p><input type="submit" value="Upload" onclick="this.form.folder.value = this.form.folderdisplay.value;" class="button" /></p>
        
      </form>
      
      
      
      
      
      
<?php /*** COMMENTS ***********************************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else if ($page == "comments") { 
      // Set up some view option variables.
      if ($_GET['n']) $pagenum = $_GET['n']; else $pagenum = 1;
      if (isset($_GET['fulltext'])) $fulltext = true; else $fulltext = false;
      if (isset($_GET['viewall'])) $viewall = true; else $viewall = false;

      $comments = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
        . " (c.date + 0) AS date, c.comment, c.email FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a "
        . " WHERE c.imageid = i.id AND i.albumid = a.id ORDER BY c.id DESC " . ($viewall ? "" : "LIMIT 20") );
      
    ?>
      <h1>Comments</h1>
      
      <?php /* Display a message if needed. Fade out and hide after 2 seconds. */ 
        if ((isset($_GET['ndeleted']) && $_GET['ndeleted'] > 0) || isset($_GET['sedit'])) { ?>
        <div class="errorbox" id="message">
          <?php if (isset($_GET['ndeleted'])) { ?> <h2><?= $_GET['ndeleted'] ?> Comments deleted successfully.</h2> <?php } ?>
          <?php if (isset($_GET['sedit'])) { ?> <h2>Comment saved successfully.</h2> <?php } ?>
        </div>
        <script type="text/javascript">
          Fat.fade_and_hide_element('message', 30, 1000, 2000, Fat.get_bgcolor('message'), '#FFFFFF')
        </script>
      <?php } ?>
	  
	  <p>You can edit or delete comments on your photos.</p>
    <?php if($viewall) { ?>
      <p>Showing <strong>all</strong> comments. <a href="?page=comments<?= ($fulltext ? "&fulltext":""); ?>"><strong>Just show 20.</strong></a></p>
    <?php } else { ?>
      <p>Showing the latest <strong>20</strong> comments. <a href="?page=comments&viewall<?= ($fulltext ? "&fulltext":""); ?>"><strong>View All</strong></a></p>
    <?php } ?>
      <form name="comments" action="?page=comments&action=deletecomments" method="post" onsubmit="return confirm('Are you sure you want to delete these comments?');">
      <table class="bordered">
        <tr>
          <th>&nbsp;</th>
          <th>Image</th>
          <th>Author/Link</th>
          <th>Date/Time</th>
          <th>Comment <?php if(!$fulltext) { ?>(<a href="?page=comments&fulltext<?= $viewall ? "&viewall":"" ?>">View full text</a>)
            <?php } else { ?>(<a href="?page=comments<?= $viewall ? "&viewall":"" ?>">View truncated</a>)<?php } ?></th>
          <th>E-Mail</th>
          <th colspan="2">&nbsp;</th>
        </tr>
        
  <?php
    foreach ($comments as $comment) {
      $id = $comment['id'];
      $author = $comment['name'];
      $email = $comment['email'];
      $album = $comment['folder'];
      $image = $comment['filename'];
      $date  = myts_date("n/j/Y, g:i a", $comment['date']);
      $albumtitle = $comment['albumtitle'];
      if ($comment['title'] == "") $title = $image; else $title = $comment['title'];
      $website = $comment['website'];
      $shortcomment = truncate_string($comment['comment'], 123);
      $fullcomment = $comment['comment'];
  ?>

          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $id ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" /></td>
            <td style="font-size: 7pt;"><?php echo "<a href=\"" . (zp_conf("mod_rewrite") ? "../$album/$image" : "../image.php?album="
              .urlencode($album)."&image=".urlencode($image)) . "\">$albumtitle / $title</a>"; ?></td>
            <td><?= $website ? "<a href=\"$website\">$author</a>" : $author ?></td>
            <td style="font-size: 7pt;"><?= $date ?></td>
            <td><?= ($fulltext) ? $fullcomment : $shortcomment ?></td>
            <td><a href="mailto:<?= $email ?>?body=<?= commentReply($fullcomment, $author, $image, $albumtitle); ?>">Reply</a></td>
            <td><a href="?page=editcomment&id=<?= $id ?>" title="Edit this comment.">Edit</a></td>
            <td><a href="javascript: if(confirm('Are you sure you want to delete this comment?')) { window.location='?page=comments&action=deletecomments&id=<?= $id ?>'; }" title="Delete this comment." style="color: #c33;">Delete</a></td>
          </tr>
  <?php } ?>
		<tr>
			<td colspan="8" class="subhead"><label><input type="checkbox" name="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" /> Check All</label></td>
		</tr>

      
      </table>
      
      <input type="submit" value="Delete Selected Comments" class="button" />
      </select>
      
      </form>
      
<?php /*** EDIT COMMENT *******************************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else if ($page == "editcomment") { ?>
      <h1>edit comment</h1>
      <?php
      if (isset($_GET['id'])) $id = $_GET['id'];
      else echo "<h2>No comment specified. <a href=\"?page=comments\">&laquo Back</a></h2>";
      
      $commentarr = query_single_row("SELECT name, website, date, comment, email FROM ".prefix('comments')." WHERE id = $id LIMIT 1");
      extract($commentarr);
      ?>
      
      <form action="?page=comments&action=savecomment" method="post">
        <input type="hidden" name="id" value="<?= $id ?>" />
        <table>
        
          <tr><td width="100">Author:</td>    <td><input type="text" size="40" name="name" value="<?= $name ?>" /></td></tr>
          <tr><td>Web Site:</td>              <td><input type="text" size="40" name="website" value="<?= $website ?>" /></td></tr>
          <tr><td>E-Mail:</td>                <td><input type="text" size="40" name="email" value="<?= $email ?>" /></td></tr>
          <tr><td>Date/Time:</td>             <td><input type="text" size="18" name="date" value="<?= $date ?>" /></td></tr>
          <tr><td valign="top">Comment:</td>  <td><textarea rows="8" cols="60" name="comment" /><?= $comment ?></textarea></td></tr>
          <tr><td></td>                       <td><input type="submit" value="save" /> <input type="button" value="cancel" onclick="window.location = '?page=comments';"/>

        </table>
      </form>
      
      
      
<?php /*** OPTIONS (Theme Switcher) *******************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else if ($page == "options") { ?>
      
      <h1>General Options</h1>
      
      <h2>Themes</h2>
	  <p>Themes allow you to visually change the entire look and feel of your gallery. All themes are located in your <code>zenphoto/themes</code> folder, and you can download more themes at <a href="http://www.zenphoto.org">ZenPhoto.org</a>.</p>
      <table class="bordered">
        <?php 
        $themes = $gallery->getThemes();
        $current_theme = $gallery->getCurrentTheme();
        $current_theme_style = "background-color: #ECF1F2;";
        foreach($themes as $theme => $themeinfo):
          $style = ($theme == $current_theme) ? " style=\"$current_theme_style\"" : "";
          $themedir = SERVERPATH . "/themes/$theme";
          $themeweb = WEBPATH . "/themes/$theme";
        ?>
        <tr>
          <td style="margin: 0px; padding: 0px;">
            <?php 
              if (file_exists("$themedir/theme.png")) $themeimage = "$themeweb/theme.png";
              else if (file_exists("$themedir/theme.gif")) $themeimage = "$themeweb/theme.gif";
              else if (file_exists("$themedir/theme.jpg")) $themeimage = "$themeweb/theme.jpg";
              else $themeimage = false;
              if ($themeimage) { ?>
                <img height="150" width="150" src="<?= $themeimage ?>" alt="Theme Screenshot" />
                
            <? } ?>
          </td>
          <td<?=$style?>>
            <strong><?= $themeinfo['name'] ?></strong><br />
            <?= $themeinfo['author'] ?><br />
            Version <?= $themeinfo['version'] ?>, <?= $themeinfo['date'] ?><br />
            <?= $themeinfo['desc'] ?>
          </td>
          <td width="100"<?=$style?>>
            <?php if (!($theme == $current_theme)) { ?>
              <a href="?page=options&action=settheme&theme=<?=$theme?>" title="Set this as your theme">Use this Theme</a>
            <?php } else { echo "<strong>Current Theme</strong>"; } ?>
          </td>
        </tr>
        
        <?php endforeach; ?>
      </table>
      
      
<?php /*** HOME ***************************************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else { $page = "home"; ?>
      <h1>zenphoto Administration</h1>
      
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
			<p>There are <strong><?= $gallery->getNumImages(); ?></strong> images in a total of <strong><?= $gallery->getNumAlbums(); ?></strong> albums [<strong><a href="?prune">refresh</a></strong>].</p>
			<p><strong><?= $gallery->getNumComments(); ?></strong> comments have been posted.</p>
			
			<?php
			// These disk operations are too slow...
			/*
      <p>Total size of album images: <strong><?= size_readable($gallery->sizeOfImages(), "MB"); ?></strong></p>
			<p>Total size of image cache: <strong><?= size_readable($gallery->sizeOfCache(), "MB"); ?></strong></p>
      */
			?>
		</div>
      
      
      <div class="box" id="overview-suggest">
        <h2 class="boxtitle">Suggestions</h2>
          <h3>Add titles to...</h3>
          
          <h3>Add descriptions to...</h3>
          
      </div>
	  
	  <p style="clear: both; "></p>
      
    <?php } ?>
    
</div>
	<div id="footer"><a href="http://www.zenphoto.org" title="A simpler web photo album">zen<strong>photo</strong></a> version <?= zp_conf('version'); ?></div>
</div>

  
<?php } /* No admin-only content allowed after this bracket! */ ?>
    
</body>
</html>