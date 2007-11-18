<?php  /* Don't put anything before this line! */
session_start();
define('OFFSET_PATH', true);
require_once("sortable.php");

$sortby = array('Filename', 'Date', 'Title', 'Manual' ); 
$standardOptions = array('gallery_title','website_title','website_url','time_offset', 
                         'gmaps_apikey','mod_rewrite','mod_rewrite_image_suffix',  
                         'admin_email','server_protocol','charset','image_quality', 
                         'thumb_quality','image_size','image_use_longest_side', 
                         'image_allow_upscale','thumb_size','thumb_crop', 
                         'thumb_crop_width','thumb_crop_height','thumb_sharpen', 
                         'albums_per_page','images_per_page','perform_watermark', 
                         'watermark_image','adminuser','adminpass','current_theme', 'spam_filter',
                         'email_new_comments', 'perform_video_watermark', 'video_watermark_image',
                         'gallery_sorttype', 'gallery_sortdirection', 'feed_items', 'search_fields');
          
global $_zp_null_account;
if (zp_loggedin() || $_zp_null_account) { /* Display the admin pages. Do action handling first. */
  
  $gallery = new Gallery();
  if (isset($_GET['prune'])) {
    if ($_GET['prune'] != 'done') {
      if ($gallery->garbageCollect(true, true)) {
        $param = '?prune=continue';
      } else {
        $param = '?prune=done';
      }
      header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php" . $param);
    }
  } else {
    $gallery->garbageCollect();
  }
  
  if (isset($_GET['action'])) {
    $action = $_GET['action'];

/** SAVE **********************************************************************/
/*****************************************************************************/
    if ($action == "save") {
/** SAVE A SINGLE ALBUM *******************************************************/
      if ($_POST['album']) {
      
        $folder = queryDecode(strip($_POST['album']));  
        $album = new Album($gallery, $folder);
        
        if (isset($_POST['savealbuminfo'])) {
          $album->setTitle(strip($_POST['albumtitle']));
          $album->setDesc(strip($_POST['albumdesc']));
          $album->setTags(strip($_POST['albumtags']));
          $album->setDateTime(strip($_POST["albumdate"]));
          $album->setPlace(strip($_POST['albumplace']));
          $album->setAlbumThumb(strip($_POST['thumb']));
          $album->setShow(strip($_POST['Published']));
          $album->setCommentsAllowed(strip($_POST['allowcomments']));
          $album->setSortType(strip($_POST['sortby']));
          $album->setSortDirection('image', strip($_POST['image_sortdirection']));   
          $album->setSubalbumSortType(strip($_POST['subalbumsortby']));   
          $album->setSortDirection('album', strip($_POST['album_sortdirection']));   
          $album->save();
        }

        if (isset($_POST['totalimages'])) {
          for ($i = 0; $i < $_POST['totalimages']; $i++) {
            $filename = strip($_POST["$i-filename"]);
            
            // The file might no longer exist
            $image = new Image($album, $filename);
            if ($image->exists) {
              $image->setTitle(strip($_POST["$i-title"]));
              $image->setDesc(strip($_POST["$i-desc"]));  
              $image->setLocation(strip($_POST["$i-location"])); 
              $image->setCity(strip($_POST["$i-city"])); 
              $image->setState(strip($_POST["$i-state"])); 
              $image->setCountry(strip($_POST["$i-country"])); 
              $image->setTags(strip($_POST["$i-tags"])); 
              $image->setDateTime(strip($_POST["$i-date"]));  
              $image->setShow(strip($_POST["$i-Visible"]));  
              $image->setCommentsAllowed(strip($_POST["$i-allowcomments"]));  
              $image->save();
            }
          }
        }
        
/** SAVE MULTIPLE ALBUMS ******************************************************/
      } else if ($_POST['totalalbums']) {
        
        for ($i = 0; $i < $_POST['totalalbums']; $i++) {
          $folder = queryDecode(strip($_POST["$i-folder"]));  
          $album = new Album($gallery, $folder);
          $album->setTitle(strip($_POST["$i-title"]));
          $album->setDesc(strip($_POST["$i-desc"]));
          $album->setTags(strip($_POST["$i-tags"]));
          // FIXME: Date entry isn't ready yet...
          // $album->setDate(strip($_POST["$i-date"]));
          $album->setPlace(strip($_POST["$i-place"]));
          $album->save();
        }
      }
      // Redirect to the same album we saved.
      $qs_albumsuffix = ""; 
      if ($_GET['album']) {
        $folder = queryDecode(strip($_GET['album']));
        $qs_albumsuffix = '&album='.urlencode($folder);
      }
      header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit' . $qs_albumsuffix . '&saved');  
      exit();

/** DELETION ******************************************************************/
/*****************************************************************************/
    } else if ($action == "deletealbum") {
      $albumdir = ""; 
      if ($_GET['album']) {
        $folder = queryDecode(strip($_GET['album'])); 
        $album = new Album($gallery, $folder);
        if ($album->deleteAlbum()) { 
          $nd = 3; 
        } else { 
          $nd = 4; 
        }
        $pieces = explode('/', $folder);   
        if (($i = count($pieces)) > 1) { 
          unset($pieces[$i-1]); 
          $albumdir = "&album=" . urlencode(implode('/', $pieces)); 
        } 
      }
      header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit" . $albumdir . "&ndeleted=" . $nd); 
      exit();
      
    } else if ($action == "deleteimage") {
      if ($_GET['album'] && $_GET['image']) {
        $folder = queryDecode(strip($_GET['album']));  
        $file = queryDecode(strip($_GET['image'])); 
        $album = new Album($gallery, $folder);
        $image = new Image($album, $file);
        if ($image->deleteImage()) { 
          $nd = 1; 
        } else { 
          $nd = 2; 
        }
      }
      header("Location: ". FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit&album=" . urlencode($folder) . "&ndeleted=" . $nd); 
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
        $uploaddir = $gallery->albumdir . $folder;
        if (!is_dir($uploaddir)) {
          mkdir ($uploaddir, 0777);
        }
        @chmod($uploaddir, 0777);
        
        $error = false;
        foreach ($_FILES['files']['error'] as $key => $error) {
          if ($_FILES['files']['name'][$key] == "") continue;
          if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['files']['tmp_name'][$key];
            $name = $_FILES['files']['name'][$key];		
			$name = str_replace("%", "", $name); // the percents cause bad problems
            if (is_image($name)) {
              $uploadfile = $uploaddir . '/' . $name;
              move_uploaded_file($tmp_name, $uploadfile);
              @chmod($uploadfile, 0666);
            } else if (is_zip($name)) {
              unzip($tmp_name, $uploaddir);
            }
          }
        }


        $album = new Album($gallery, $folder);
        if ($album->exists) {
          if (!isset($_POST['publishalbum'])) {
            $album->setShow(false);
          }
          $title = strip($_POST['albumtitle']);       
          if (!(false === ($pos = strpos($title, ' (')))) {
            $title = substr($title, 0, $pos);
          } 
          if (!empty($title)  && isset($_POST['newalbum'])) {
            $album->setTitle($title);
          }
          $album->save();
        } else { 
          $AlbumDirName = str_replace(SERVERPATH, '', $gallery->albumdir);
          zp_error("The album couldn't be created in the 'albums' folder. This is usually "
            . "a permissions problem. Try setting the permissions on the albums and cache folders to be world-writable "
            . "using a shell: <code>chmod 777 " . $AlbumDirName . CACHEFOLDER ."</code>, or use your FTP program to give everyone write "
            . "permissions to those folders.");
        }

        
        header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit&album=" . urlencode($folder)); 
        exit();
        
      } else {
        // Handle the error and return to the upload page.
        $page = "upload";
        $error = true;
        if ($files_empty) {
          $errormsg = "You must upload at least one file.";
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
    if (isset($_POST['moderated'])) {
      $moderated = $_POST['moderated'];
	  if (isset($_POST['notreleased'])) {
        $notreleased = $_POST['notreleased'];
	  } else {
	    $notreleased = array();
	  }
      $idlist = '';
      $release = array_diff($moderated, $notreleased);
      foreach($release as $id) {
        if (!empty($idlist)) { 
          $idlist .= "OR "; 
        }
        $idlist .= "id='$id' ";
      }
      if (!empty($idlist)) {
        $sql = 'UPDATE ' . prefix('comments') . ' SET `inmoderation`=0 WHERE ' . $idlist . ';';    
        query($sql);
      }
    }

    
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
        header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments&ndeleted=$n");
        exit();
      } else {
        header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments&ndeleted=0");
        exit();
      }
      
      
    } else if ($action == 'savecomment') {
      if (!isset($_POST['id'])) {
        header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments");
        exit();
      }
      $id = $_POST['id'];
      $name = escape($_POST['name']);
      $email = escape($_POST['email']);
      $website = escape($_POST['website']);
      $date = escape($_POST['date']);
      $comment = escape($_POST['comment']);
      
      // TODO: Update date as well; no good input yet, so leaving out.
      $sql = "UPDATE ".prefix('comments')." SET name = '$name', email = '$email', website = '$website', comment = '$comment' WHERE id = $id";
      query($sql);
      
      header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments&sedit");
      exit();
 
/** OPTIONS ******************************************************************/
/*****************************************************************************/
    } else if ($action == 'saveoptions') {
	  $wm = getOption('perform_watermark');
	  $vwm = getOption('perform_video_watermark');
      $returntab = "";
	  /*** admin options ***/
	  if (isset($_POST['saveadminoptions'])) {
	    if ($_POST['adminpass'] == $_POST['adminpass_2']) {
          setOption('adminuser', $_POST['adminuser']);
          setOption('adminpass', $_POST['adminpass']);
	      $notify = '';
	    } else {
	      $notify = '&mismatch';
	    }
        setOption('admin_email', $_POST['admin_email']);
		$returntab = "#tab_admin";
	  }
	  
	  /*** Gallery options ***/
	  if (isset($_POST['savegalleryoptions'])) {
        setOption('gallery_title', $_POST['gallery_title']);
        setOption('website_title', $_POST['website_title']);
        setOption('website_url', $_POST['website_url']);
        setOption('time_offset', $_POST['time_offset']);
        setOption('gmaps_apikey', $_POST['gmaps_apikey']);
        setBoolOption('mod_rewrite', $_POST['mod_rewrite']);
        setOption('mod_rewrite_image_suffix', $_POST['mod_rewrite_image_suffix']);
        setOption('server_protocol', $_POST['server_protocol']);
        setOption('charset', $_POST['charset']);
        setOption('spam_filter', $_POST['spam_filter']);         
        setBoolOption('email_new_comments', $_POST['email_new_comments']);         
        setOption('gallery_sorttype', $_POST['gallery_sorttype']);         
        setBoolOption('gallery_sortdirection', $_POST['gallery_sortdirection']);   
	    setOption('feed_items', $_POST['feed_items']);  
        $search = new SearchEngine();
	    setOption('search_fields', 32767); // make SearchEngine allow all options so getQueryFields() will gives back what was choosen this time
        setOption('search_fields', $search->getQueryFields());	
		$returntab = "#tab_gallery";
	  }
	  /*** Image options ***/
	  if (isset($_POST['saveimageoptions'])) {
        setOption('image_quality', $_POST['image_quality']);
        setOption('thumb_quality', $_POST['thumb_quality']);
        setOption('image_size', $_POST['image_size']);
        setBoolOption('image_use_longest_side', $_POST['image_use_longest_side']);
        setBoolOption('image_allow_upscale', $_POST['image_allow_upscale']);
        setOption('thumb_size', $_POST['thumb_size']);
        setBoolOption('thumb_crop', $_POST['thumb_crop']);
        setOption('thumb_crop_width', $_POST['thumb_crop_width']);
        setOption('thumb_crop_height', $_POST['thumb_crop_height']);
        setBoolOption('thumb_sharpen', $_POST['thumb_sharpen']);
        setOption('albums_per_page', $_POST['albums_per_page']);
        setOption('images_per_page', $_POST['images_per_page']);
        setBoolOption('perform_watermark', $_POST['perform_watermark']);
        setOption('watermark_image', 'images/' . $_POST['watermark_image'] . '.png');
        setBoolOption('perform_video_watermark', $_POST['perform_video_watermark']);
        setOption('video_watermark_image', 'images/' . $_POST['video_watermark_image'] . '.png');
		$returntab = "#tab_image";
      }	  
	  if (isset($_POST['savethemeoptions'])) {
	    // all theme options are custom options, handled below
	    $returntab = "#tab_theme";
	  } 
      /*** custom options ***/     
      $templateOptions = GetOptionList();
	  
      foreach($standardOptions as $option) {
        unset($templateOptions[$option]);
      }
      unset($templateOptions['saveoptions']);
      $keys = array_keys($templateOptions);
      $i = 0;
      while ($i < count($keys)) { 
        if (isset($_POST[$keys[$i]])) { 
          setOption($keys[$i], $_POST[$keys[$i]]);
        } else {
          if (isset($_POST['chkbox-' . $keys[$i]])) {
            setOption($keys[$i], 0);
          }
        }
        $i++;
       }  
	  if(($wm != getOption('perform_watermark')) || ($vwm != getOption('perform_video_watermark'))) {
	    $gallery->clearCache(); // watermarks (or lack there of) are cached, need to start fresh if the option has changed
	  }
      header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=options".$notify.$returntab);
      exit();
    
/** THEMES ******************************************************************/
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
  
} else {
  if (isset($_GET['emailpassword'])) {
    $user = getOption('adminuser');
	$pass = getOption('adminpass');
	if (empty($user)) {
	  $msg = "\nThe Admin user id has not been set. ";
	} else {
	  $msg = "\nYour user id is `$user`. ";
	}
	if (empty($pass)) {
	  $msg .= "\nThe Admin password has not been set. ";
	} else {
	  $msg .= "\nYour password is `$pass`. ";
	}
	$msg .= "\nThis information was requested from the Admin Logon screen at ".FULLWEBPATH."/".ZENFOLDER."/admin.php.";

	zp_mail('The Zenphoto information you requested',  $msg); 
  }
}/* NO Admin-only content between this and the next check. */
  
/************************************************************************************/
/** End Action Handling *************************************************************/
/************************************************************************************/

if (issetPage('edit')) {
  zenSortablesPostHandler('albumOrder', 'albumList', 'albums');
}

// Print our header
printAdminHeader();

if (issetPage('edit')) {
  zenSortablesHeader('albumList','albumOrder','div');
}
?>

</head>
<body>

<?php
// If they are not logged in, display the login form and exit

if (!zp_loggedin()  && !$_zp_null_account) {
  
  printLoginForm();
  exit(); 
    
} else { /* Admin-only content safe from here on. */ 
  if ($_zp_null_account) { $page = 'options'; } // strongly urge him to set his admin username and password
  printLogoAndLinks();
 
?>

  <div id="main">
  
<?php printTabs(); ?>  
  
  <div id="content">
  
  <?php if ($_zp_null_account) {
    echo "<div class=\"errorbox space\">";
    echo "<h2>You need to set your admin user and password.</h2>";
	echo "</div>";
  }
  ?>
  
  
<?php /** EDIT ****************************************************************************/
      /************************************************************************************/ 
      
  if ($page == "edit") { ?>
      
      
<?php /** SINGLE ALBUM ********************************************************************/ ?>
      <?php if (isset($_GET['album']) && !isset($_GET['massedit'])) { 
        $folder = strip($_GET['album']); 
        $album = new Album($gallery, $folder); 
        $images = $album->getImages(); 
        $totalimages = sizeof($images); 
        // TODO: Perhaps we can build this from the meta array of Album? Moreover, they should be a set of constants!
        $albumdir = ""; 
        $pieces = explode('/', $folder);   
        if (($i = count($pieces)) > 1) { 
          unset($pieces[$i-1]); 
          $albumdir = "&album=" . urlencode(implode('/', $pieces));
        } 
      ?>
        <h1>Edit Album: <i><?php echo $album->name; ?></i></h1> 
        <p>
        <?php printAdminLinks("edit" . $albumdir, "&laquo; Back", "Back to the list of albums (go up one level)");?> |  
        <?php printSortLink($album, "Sort Album", "Sort Album"); ?> |
        <?php printViewLink($album, "View Album", "View Album"); ?>
        </p>
        
        <?php displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */ ?>
        <?php if (isset($_GET['saved'])) { ?>
          <div class="messagebox" id="message1"> 
            <h2>Save Successful</h2>
          </div>
          <script type="text/javascript">
            window.setTimeout('Effect.Fade($(\'message1\'))', 2500);
          </script>
        <?php } ?>
    
    <!-- Album info box --> 
        <?php
          if (isset($saved)) {
            $album->setSubalbumSortType('Manual');
          }
        ?>

        <form name="albumedit1" action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>" method="post">
          <input type="hidden" name="album" value="<?php echo $album->name; ?>" />
          <input type="hidden" name="savealbuminfo" value="1" />
        
          <div class="box" style="padding: 15px;">
            <table>
              <tr><td align="right" valign="top">Album Title: </td> <td><input type="text" name="albumtitle" value="<?php echo $album->getTitle(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Album Description: </td> <td><textarea name="albumdesc" cols="60" rows="6"><?php echo $album->getDesc(); ?></textarea></td></tr>
              <tr><td align="right" valign="top">Tags: </td> <td><input type="text" name="albumtags" class="tags" value="<?php echo $album->getTags(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="albumdate" value="<?php $d=$album->getDateTime(); if ($d!='0000-00-00 00:00:00') { echo $d; }?>" /></td></tr>
              <tr><td align="right" valign="top">Location: </td> <td><input type="text" name="albumplace" value="<?php echo $album->getPlace(); ?>" /></td></tr>
              <tr><td align="right" valign="top">Thumbnail: </td> 
                <td>
                  <select id="thumbselect" class="thumbselect" name="thumb" onChange="updateThumbPreview(this)">
<?php foreach ($images as $filename) { 
  $image = new Image($album, $filename);
  $selected = ($filename == $album->get('thumb')); ?>
                    <option class="thumboption" style="background-image: url(<?php echo $image->getThumb(); ?>); background-repeat: no-repeat;" value="<?php echo $filename; ?>"<?php if ($selected) echo ' selected="selected"'; ?>><?php echo $image->get('title'); ?><?php echo ($filename != $image->get('title')) ? " ($filename)" : ""; ?></option>
<?php } ?>
                  </select>
                  <script type="text/javascript">updateThumbPreview(document.getElementById('thumbselect'));</script>
                </td>
              </tr>
              <tr><td align="right" valign="top">Allow Comments: </td><td><input type="checkbox" name="allowcomments" value="1" <?php if ($album->getCommentsAllowed()) {echo "CHECKED";} ?>></td></tr>
              <tr><td align="right" valign="top">Published: </td><td><input type="checkbox" name="Published" value="1" <?php if ($album->getShow()) {echo "CHECKED";} ?>></td></tr>
             <tr>
                <td align="right" valign="top">Sort subalbums by: </td>
                <td>
                  <select id="sortselect" name="subalbumsortby">
                  <?php foreach ($sortby as $sorttype) { ?>
                    <option value="<?php echo $sorttype; ?>"<?php if ($sorttype == $album->getSubalbumSortType()) echo ' selected="selected"'; ?>><?php echo $sorttype; ?></option>
                  <?php } ?>
                  </select>
				&nbsp;Descending <input type="checkbox" name="album_sortdirection" value="1"
				     <?php if ($album->getSortDirection('image')) {echo "CHECKED";} ?>>  
                </td>
              </tr>
             <tr>
                <td align="right" valign="top">Sort images by: </td>
                <td>
                  <select id="sortselect" name="sortby">
                  <?php foreach ($sortby as $sorttype) { ?>
                    <option value="<?php echo $sorttype; ?>"<?php if ($sorttype == $album->getSortType()) echo ' selected="selected"'; ?>><?php echo $sorttype; ?></option>
                  <?php } ?>
                  </select>
				&nbsp;Descending <input type="checkbox" name="image_sortdirection" value="1" 
				     <?php if ($album->getSortDirection('image')) {echo "CHECKED";} ?>>  
                </td>
              </tr>
               <tr><td></td><td valign="top"><a href="cache-images.php?album=<?php echo $album->name; ?>">Pre-Cache Images</a></strong> - Cache newly uploaded images.</td></tr>
               <?php         
                 if ($album->getNumImages() > 0) { ?> 
			       <tr><td></td><td valign="top"><a href="refresh-metadata.php?album=<?php echo $album->name; ?>">Refresh Image Metadata</a> - Forces a refresh of the EXIF and IPTC data for all images in the album.</td></tr>
			   <?php } ?>
            </table>
              
            <input type="submit" value="save" />
          </div>
        </form>
          
        <!-- Subalbum list goes here -->
    
        <?php  
        $subalbums = $album->getSubAlbums(); 
        if (count($subalbums) > 0) { 
	  if ($album->getNumImages() > 0)  { ?>
            <p><a name="subalbumList"></a><a href="#imageList" title="Scroll down to the image list.">Image List &raquo;</a></p>
          <?php } ?> 
          
          <table class="bordered"> 
            <input type="hidden" name="subalbumsortby" value="Manual" />
            <tr> 
              <th colspan="3"><h1>Albums</h1></th>            
            </tr> 
            <tr>
              <td colspan="3">
                Drag the albums into the order you wish them displayed. Select an album to edit its description and data, or <a href="?page=edit&album=<?php echo urlencode($album->name)?>&massedit">mass-edit all album data</a>.  
              </td>
            </tr>
            <tr> 
            <td colspan="2" style="padding: 0px 0px;"> 
            <div id="albumList" class="albumList">
              <?php
                foreach ($subalbums as $folder) {  
                  $subalbum = new Album($album, $folder); 
              ?> 
                  <div id="id_<?php echo $subalbum->getAlbumID(); ?>"> 
                  <table cellspacing="0" width="100%">  
                    <tr>
                      <td align="left" width="20"> 
                        <a href="?page=edit&album=<?php echo urlencode($subalbum->name); ?>" title="Edit this album: <?php echo $subalbum->name; ?>">
                          <img height="40" width="40" src="<?php echo $subalbum->getAlbumThumb(); ?>" /></a> 
                      </td>
                      <td align="left" width="400"> 
                        <a href="?page=edit&album=<?php echo urlencode($subalbum->name); ?>" title="Edit this album: <?php echo $subalbum->name; ?>">
                          <?php echo $subalbum->getTitle(); ?></a> 
                      </td>
                      
                      <td>
                        <a class="delete" href="javascript: confirmDeleteAlbum('?page=edit&action=deletealbum&album=<?php echo queryEncode($subalbum->name); ?>
                          ');" title="Delete the album <?php echo $subalbum->name; ?>"><img src="images/delete.gif" style="border: 0px;" alt="x" /></a> 
                      </td> 
                      
                    </tr> 
                  </table> 
                  </div>
              <?php } ?> 
            </div>
            </tr>
            <tr>
              <td colspan="2">
                <?php
                zenSortablesSaveButton("?page=edit&album=" . urlencode($album->name) . "&saved", "Save Order");  
                ?>
              </td>
            </tr>
          </table>
          
          <?php 
            if (isset($_GET['saved'])) { 
              echo "<p>Subalbum order saved.</p>"; 
            }
          ?>
        <?php } ?>
          
          
        
     
     <!-- Images List -->
     
      <?php if (count($album->getSubalbums())) { ?>
        <p><a name="imageList"></a><a href="#subalbumList" title="Scroll up to the sub-album list">&laquo; Subalbum List</a></p>
      <?php } ?>
      
      <form name="albumedit2" action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>" method="post">
        <input type="hidden" name="album" value="<?php echo $album->name; ?>" />
        <input type="hidden" name="totalimages" value="<?php echo $totalimages; ?>" />
        
        <table class="bordered">
          <tr> 
            <th colspan="3"><h1>Images</h1></th> 
          </tr> 
          <tr>
            <td>
              <input type="submit" value="save" />
            </td>
            <td colspan="2">
              Click the images for a larger version
            </td>
          </tr>
           
            <?php
            $currentimage = 0;
            foreach ($images as $filename) {
              $image = new Image($album, $filename);
            ?>
            
            <tr id=""<?php echo ($currentimage % 2 == 0) ?  "class=\"alt\"" : ""; ?>>
              <td valign="top" width="100">
                <img id="thumb-<?php echo $currentimage; ?>" src="<?php echo $image->getThumb();?>" alt="<?php echo $image->filename;?>" 
                  onclick="toggleBigImage('thumb-<?php echo $currentimage; ?>', '<?php echo $image->getSizedImage(getOption('image_size')); ?>');" />
              </td>
  
              <td width="240">
                <input type="hidden" name="<?php echo $currentimage; ?>-filename" value="<?php echo $image->filename; ?>" />
                <table border="0" class="formlayout">
                  <tr><td align="right" valign="top">Title: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-title" value="<?php echo $image->getTitle(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Description: </td> <td><textarea name="<?php echo $currentimage; ?>-desc" cols="60" rows="4" style="width: 360px"><?php echo $image->getDesc(); ?></textarea></td></tr>
                  <tr><td align="right" valign="top">Location: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-location" value="<?php echo $image->getLocation(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">City: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-city" value="<?php echo $image->getCity(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">State: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-state" value="<?php echo $image->getState(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Country: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-country" value="<?php echo $image->getCountry(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Tags: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-tags" value="<?php echo $image->getTags(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Date: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-date" value="<?php $d=$image->getDateTime(); if ($d!='0000-00-00 00:00:00') { echo $d; } ?>" /></td></tr>
                  <tr><td align="right" valign="top" colspan="2">
                    <label for="<?php echo $currentimage; ?>-allowcomments"><input type="checkbox" id="<?php echo $currentimage; ?>-allowcomments" name="<?php echo $currentimage; ?>-allowcomments" value="1" <?php if ($image->getCommentsAllowed()) { echo "checked=\"checked\""; } ?> /> Allow Comments</label>
                    &nbsp; &nbsp;
                    <label for="<?php echo $currentimage; ?>-Visible"><input type="checkbox" id="<?php echo $currentimage; ?>-Visible" name="<?php echo $currentimage; ?>-Visible" value="1" <?php if ($image->getShow()) { echo "checked=\"checked\""; } ?> /> Visible</label>
                  </td></tr>
                </table>
              </td>

              <td style="padding-left: 1em;">
                <a href="javascript: confirmDeleteImage('?page=edit&action=deleteimage&album=<?php echo queryEncode($album->name); ?>&image=<?php echo queryEncode($image->filename); ?>');" title="Delete the image <?php echo $image->filename; ?>">  
                <img src="images/delete.gif" style="border: 0px;" alt="x" /></a> 
              </td>

                
            </tr>
            
            <?php 
              $currentimage++;
            }
            ?>
            <tr>
              <td colspan="3">
                <input type="submit" value="save" />
              </td>
            </tr>
            
          </table>        
          
          
        </form>

      <?php if (count($album->getSubalbums())) { ?>
        <p><a href="#subalbumList" title="Scroll up to the sub-album list">&nbsp; &nbsp; &nbsp;^ Subalbum List</a></p>
      <?php } ?> 
      
      <!-- page trailer -->  
      <p><a href="?page=edit<?php echo $albumdir ?>" title="Back to the list of albums (go up one level)">&laquo; Back</a></p> 
        
        
        
        

<?php /*** MULTI-ALBUM ***************************************************************************/ ?>
        
      <?php } else if (isset($_GET['massedit'])) { 
        $albumdir = ""; 
        if (isset($_GET['album'])) {
          $folder = strip($_GET['album']); 
          $album = new Album($gallery, $folder);
          $albums = $album->getSubAlbums(); 
          $pieces = explode('/', $folder); 
          if (($i = count($pieces)) > 1) { 
            unset($pieces[$i-1]); 
            $albumdir = "&album=" . urlencode(implode('/', $pieces)); 
          } else { 
            $albumdir = ""; 
          } 
        } else { 
          $albums = $gallery->getAlbums(); 
        }
      ?>
      <h1>Edit All Albums in <?php if (!isset($_GET['album'])) {echo "Gallery";} else {echo "<i>" . $album->name . "</i>";}?></h1> 
      <p><a href="?page=edit<?php echo $albumdir ?>" title="Back to the list of albums (go up a level)">&laquo; Back</a></p> 
      <div class="box" style="padding: 15px;">
      
      <form name="albumedit" action="?page=edit&action=save<?php echo $albumdir ?>" method="POST"> 
      <?php
        
        // Two albums will probably require a scroll bar
        if (sizeof($albums) > 2) {
          echo "<p><input type=\"submit\" value=\"save\" /> &nbsp; <input type=\"reset\" value=\"reset\" /></p>";
          echo "<hr />";
        }
        ?> 
        <input type="hidden" name="totalalbums" value="<?php echo sizeof($albums); ?>" /> <?php
        $currentalbum = 0;
        foreach ($albums as $folder) { 
          $album = new Album($gallery, $folder);
      ?>
        <input type="hidden" name="<?php echo $currentalbum; ?>-folder" value="<?php echo $album->name; ?>" />
        <table>
          <tr><td rowspan="4" valign="top"><a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="Edit this album: <?php echo $album->name; ?>"><img src="<?php echo $album->getAlbumThumb(); ?>" /></a></td> 
            <td align="right" valign="top">Album Title: </td> <td><input type="text" name="<?php echo $currentalbum; ?>-title" value="<?php echo $album->getTitle(); ?>" /></td></tr>
          <tr><td align="right" valign="top">Album Description: </td> <td><textarea name="<?php echo $currentalbum; ?>-desc" cols="60" rows="6"><?php echo $album->getDesc(); ?></textarea></td></tr>
          
          <?php /* Removing date entry for now... */ 
                /* <tr><td align="right" valign="top">Date: </td> <td><input type="text" name="<?php echo $currentalbum; ?>-date" value="<?php echo $album->getDateTime(); ?>" /></td></tr> */ ?>
          
          <tr><td align="right" valign="top">Place: </td> <td><input type="text" name="<?php echo $currentalbum; ?>-place" value="<?php echo $album->getPlace(); ?>" /></td></tr>
        </table>
        <hr />
        
      <?php 
          $currentalbum++;
        } 
      ?>
      
        <p><input type="submit" value="save" /> &nbsp; <input type="reset" value="reset" /></p>
      
      </form>
        
      </div>
      
      
<?php /*** EDIT ALBUM SELECTION *********************************************************************/ ?> 

       
      <?php } else { /* Display a list of albums to edit. */ ?>
        <h1>Edit Gallery</h1>
        
        <?php displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */ ?> 
        
        <?php
          if (isset($saved)) {
            setOption('gallery_sorttype', 'Manual');
          }
        ?>

        <p>Drag the albums into the order you wish them displayed. Select an album to edit its description and data, or <a href="?page=edit&massedit">mass-edit all album data</a>.</p>

        <table class="bordered">
          <tr>
            <th>Edit this album</th>
          </tr>
          <tr>
          <td colspan="2" style="padding: 0px 0px;">
          <div id="albumList" class="albumList">
            <?php 
            $albums = $gallery->getAlbums();
            foreach ($albums as $folder) { 
              $album = new Album($gallery, $folder);
           ?>
            <div id="id_<?php echo $album->getAlbumID(); ?>">
            <table cellspacing="0" width="100%">
              <tr>
                <td align="left" width="20">
                <a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="Edit this album: <?php echo $album->name; ?>"><img height="40" width="40" src="<?php echo $album->getAlbumThumb(); ?>" /></a> 
                </td>
                <td align="left" width="400"> <a href="?page=edit&album=<?php echo urlencode($album->name); ?>" title="Edit this album: <?php echo $album->name; ?>"><?php echo $album->getTitle(); ?></a>  
                </td>

                <td align="left"> 
                  <a class="delete" href="javascript: confirmDeleteAlbum('?page=edit&action=deletealbum&album=<?php echo queryEncode($album->name); ?>');" title="Delete the album <?php echo $album->name; ?>"> 
                  <img src="images/delete.gif" style="border: 0px;" alt="x" /></a>
				  <a class="cache" href="cache-images.php?album=<?php echo $album->name; ?>" title="Pre-Cache the album '<?php echo $album->name; ?>'">
                  <img src="images/cache.gif" style="border: 0px;" alt="cache" /></a>
                </td>

              </tr>
            </table>
            </div>
            <?php } ?>
          </div>  
          </td>
          </tr>
        </table>
        
        <?php
        if (isset($_GET['saved'])) {
          echo "<p>Gallery order saved.</p>";
        }
        ?>
        
        <div>
      <?php
        zenSortablesSaveButton("?page=edit&saved", "Save Order"); 
      ?>
      </div>

      <?php } ?>
      
      
      
      
      
      
<?php /**** UPLOAD ************************************************************************/ 
      /************************************************************************************/ ?> 

    <?php } else if ($page == "upload") { 
      
      $folderlist = array();
      genAlbumList($folderlist);
      natcasesort($folderlist);
      $albumlist = array_flip($folderlist);
      
    ?>
    
      
      <script type="text/javascript">
        window.totalinputs = 5;
        // Array of album names for javascript functions.
        var albumArray = new Array ( 
          <?php 
          $separator = '';
          foreach($folderlist as $folder) {
            echo $separator . "'" . addslashes($folder) . "'";
            $separator = ", ";
          } 
          ?> );
      </script>
      
      <h1>Upload Photos</h1>
      <p>This web-based upload accepts image formats: <acronym title="Joint Picture Expert's Group">JPEG</acronym>, 
      <acronym title="Portable Network Graphics">PNG</acronym> and <acronym title="Graphics Interchange Format">GIF</acronym>.
          You can also upload a <strong>ZIP</strong> archive containing any of those file types.</p>
        <!--<p><em>Note:</em> When uploading archives, <strong>all</strong> images in the archive are added to the album, regardles of directory structure.</p>-->
      <p>The maximum size for any one file is <strong><?php echo ini_get('upload_max_filesize'); ?>B</strong>. Don't forget, you can also use <acronym title="File Transfer Protocol">FTP</acronym> to upload folders of images into the albums directory!</p>
      
      <?php if (isset($error) && $error) { ?>
        <div class="errorbox">
          <h2>Something went wrong...</h2>
          <?php echo (empty($errormsg) ? "There was an error submitting the form. Please try again." : $errormsg); ?>
        </div>
      <?php } ?>
      
      <form name="uploadform" enctype="multipart/form-data" action="?action=upload" method="POST" onSubmit="return validateFolder(document.uploadform.folder);">
        <input type="hidden" name="processed" value="1" />
        <input type="hidden" name="existingfolder" value="false" />
        
        <div id="albumselect">
          Upload to: 
          <select id="albumselectmenu" name="albumselect" onChange="albumSwitch(this)">
            <option value="" selected="true" style="font-weight: bold;">/</option>
            <?php 
              $bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');
			  $checked = "checked=\"false\"";
              foreach ($albumlist as $album) {
                $fullfolder = $folderlist[$album];
                $singlefolder = $fullfolder;
                $saprefix = "";
                $salevel = 0;
				if ($_GET['album'] == $fullfolder) {
				  $selected = " SELECTED=\"true\" ";
				  if (!isset($_GET['new'])) { $checked = ""; }
				} else {
				  $selected = "";
				}
                // Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
                while (strstr($singlefolder, '/') !== false) {
                  $singlefolder = substr(strstr($singlefolder, '/'), 1);
                  $saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
                  $salevel++;
                }
                echo '<option value="' . $folderlist[$album] . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].'; border-bottom: 1px dotted #ccc;"' : '')
                  . "$selected>" . $saprefix . $album . " (" . $singlefolder . ')' . "</option>\n";
              }
            ?>
          </select>
          
          <div id="newalbumbox" style="margin-top: 5px;">
            <div><label><input type="checkbox" name="newalbum" <?php echo $checked; ?> onClick="albumSwitch(this.form.albumselect)"> Make a new Album</label></div>
            <div id="publishtext">and <label><input type="checkbox" name="publishalbum" id="publishalbum" value="1" checked="true" />
              Publish the album so everyone can see it.</label></div>
          </div>
          
          <div id="albumtext" style="margin-top: 5px;"> 
            called: <input id="albumtitle" size="42" type="text" name="albumtitle" value="" onKeyUp="updateFolder(this, 'folderdisplay', 'autogen');" />
            
            <div style="position: relative; margin-top: 4px;">
              with the folder name: 
              <div id="foldererror" style="display: none; color: #D66; position: absolute; z-index: 100; top: 2.5em; left: 0px;"></div>
              <input id="folderdisplay" size="18" type="text" name="folderdisplay" disabled="true" onKeyUp="validateFolder(this);"/> 
              <label><input type="checkbox" name="autogenfolder" id="autogen" checked="true" onClick="toggleAutogen('folderdisplay', 'albumtitle', this);" /> Auto-generate</label>
              <br /><br />
            </div>
            
            <input type="hidden" name="folder" value="" />
          </div>
          
        </div>
        
        <div id="uploadboxes" style="display: none;">
        
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
          
          
          <p><input type="submit" value="Upload" onClick="this.form.folder.value = this.form.folderdisplay.value;" class="button" /></p>
          
        </div>
        
      </form>
      
      <script type="text/javascript">albumSwitch(document.uploadform.albumselect);</script>
      
      
      
      
      
      
<?php /*** COMMENTS ***********************************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else if ($page == "comments") { 
      // Set up some view option variables.
      if (isset($_GET['n'])) $pagenum = max(intval($_GET['n']), 1); else $pagenum = 1;
      if (isset($_GET['fulltext'])) $fulltext = true; else $fulltext = false;
      if (isset($_GET['viewall'])) $viewall = true; else $viewall = false;

      $comments = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
        . " (c.date + 0) AS date, c.comment, c.email, c.inmoderation FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a "
        . " WHERE c.imageid = i.id AND i.albumid = a.id ORDER BY c.id DESC " . ($viewall ? "" : "LIMIT 20") );
    ?>
      <h1>Comments</h1>
      
      <?php /* Display a message if needed. Fade out and hide after 2 seconds. */ 
        if ((isset($_GET['ndeleted']) && $_GET['ndeleted'] > 0) || isset($_GET['sedit'])) { ?>
        <div class="messagebox" id="message"> 
          <?php if (isset($_GET['ndeleted'])) { ?> <h2><?php echo $_GET['ndeleted']; ?> Comments deleted successfully.</h2> <?php } ?>
          <?php if (isset($_GET['sedit'])) { ?> <h2>Comment saved successfully.</h2> <?php } ?>
        </div>
        <script type="text/javascript">
          Fat.fade_and_hide_element('message', 30, 1000, 2000, Fat.get_bgcolor('message'), '#FFFFFF')
        </script>
      <?php } ?>
      
      <p>You can edit or delete comments on your photos.</p>
    <?php if($viewall) { ?>
      <p>Showing <strong>all</strong> comments. <a href="?page=comments<?php echo ($fulltext ? "&fulltext":""); ?>"><strong>Just show 20.</strong></a></p>
    <?php } else { ?>
      <p>Showing the latest <strong>20</strong> comments. <a href="?page=comments&viewall<?php echo ($fulltext ? "&fulltext":""); ?>"><strong>View All</strong></a></p>
    <?php } ?>
      <form name="comments" action="?page=comments&action=deletecomments" method="post" onSubmit="return confirm('Are you sure you want to delete these comments?');">
      <table class="bordered">
        <tr>
          <th>&nbsp;</th>
          <th>Image</th>
          <th>Author/Link</th>
          <th>Date/Time</th>
          <th>Comment <?php if(!$fulltext) { ?>(<a href="?page=comments&fulltext<?php echo $viewall ? "&viewall":""; ?>">View full text</a>)
            <?php } else { ?>(<a href="?page=comments<?php echo $viewall ? "&viewall":""; ?>">View truncated</a>)<?php } ?></th>
          <th>E-Mail</th>
          <th>Moderation</th>
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
      $inmoderation = $comment['inmoderation'];
  ?>

          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo $id; ?>" onClick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" /></td>
            <td style="font-size: 7pt;"><?php echo "<a href=\"" . (getOption("mod_rewrite") ? "../$album/$image" : "../index.php?album=".urlencode($album). 
                      "&image=".urlencode($image)) . "\">$albumtitle / $title</a>"; ?></td> 
            <td><?php echo $website ? "<a href=\"$website\">$author</a>" : $author; ?></td>
            <td style="font-size: 7pt;"><?php echo $date; ?></td>
            <td><?php echo ($fulltext) ? $fullcomment : $shortcomment; ?></td>
            <td><a href="mailto:<?php echo $email; ?>?body=<?php echo commentReply($fullcomment, $author, $image, $albumtitle); ?>">Reply</a></td>
            <td>
              <?php 
                if ($inmoderation) {
                  echo '<input type="hidden" name = "moderated[]" value="' . $id . '">';
                  echo '<input type="checkbox" name="notreleased[]" value="' . $id . '" CHECKED=true >';
                }
              ?>
            </td>
            <td><a href="?page=editcomment&id=<?php echo $id; ?>" title="Edit this comment.">Edit</a></td>
            <td><a href="javascript: if(confirm('Are you sure you want to delete this comment?')) { window.location='?page=comments&action=deletecomments&id=<?php echo $id; ?>'; }" title="Delete this comment." style="color: #c33;">Delete</a></td>
          </tr>
  <?php } ?>
        <tr>
            <td colspan="9" class="subhead"><label><input type="checkbox" name="allbox" onClick="checkAll(this.form, 'ids[]', this.checked);" /> Check All</label></td>
        </tr>

      
      </table>
      
      <input type="submit" value="Delete Selected Comments and update moderated status" class="button" />
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
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <table>
        
          <tr><td width="100">Author:</td>    <td><input type="text" size="40" name="name" value="<?php echo $name; ?>" /></td></tr>
          <tr><td>Web Site:</td>              <td><input type="text" size="40" name="website" value="<?php echo $website; ?>" /></td></tr>
          <tr><td>E-Mail:</td>                <td><input type="text" size="40" name="email" value="<?php echo $email; ?>" /></td></tr>
          <tr><td>Date/Time:</td>             <td><input type="text" size="18" name="date" value="<?php echo $date; ?>" /></td></tr>
          <tr><td valign="top">Comment:</td>  <td><textarea rows="8" cols="60" name="comment" /><?php echo $comment; ?></textarea></td></tr>
          <tr><td></td>                       <td><input type="submit" value="save" /> <input type="button" value="cancel" onClick="window.location = '?page=comments';"/>

        </table>
      </form>
      
<?php /*** OPTIONS ************************************************************************/ 
       /************************************************************************************/ ?> 
       
      <?php } else if ($page == "options") { ?> 
             
      <div id="container">
		<div id="mainmenu">
		  <ul id="tabs">
			<li><a href="#tab_admin">admin information</a></li>
			<?php if (!$_zp_null_account) { ?>
			<li><a href="#tab_gallery">gallery configuration</a></li>
			<li><a href="#tab_image">image display</a></li>
			<li><a href="#tab_theme">theme options</a></li>
			<?php } ?>
		  </ul>
		</div>
			<div class="panel" id="tab_admin">
				<form action="?page=options&action=saveoptions" method="post">
				<input type="hidden" name="saveadminoptions" value="yes" />
				<?php
	  			if (isset($_GET['mismatch'])) {
	  			  echo '<div class="errorbox" id="message">'; 
	    		  echo  "<h2>Your passwords did not match</h2>";  
	    		  echo '</div>'; 
	    		  echo '<script type="text/javascript">'; 
	    		  echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);"; 
	    		  echo "</script>\n"; 
      			}
	  			?>
      			<table class="bordered">
        			<tr> 
               			<th colspan="3"><h2>Admin login information</h2></th>
          			</tr>    
        			<tr>
            			<td width="175">Admin username:</td>
            			<td width="200"><input type="text" size="40" name="adminuser" value="<?php echo getOption('adminuser');?>" /></td>
            			<td></td>
        			</tr>
        			<tr>
            			<td>Admin password:<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(repeat) </p></td>
            			<td>
							<input type="password" size="40" name="adminpass"
            				value="<?php echo getOption('adminpass');?>" /><br/>
							<input type="password" size="40" name="adminpass_2"
            				value="<?php echo getOption('adminpass');?>" />
						</td>
            			<td></td>
        			</tr>
        			<tr>
            			<td>Admin email:</td>
            			<td><input type="text" size="40" name="admin_email" value="<?php echo getOption('admin_email');?>" /></td>
            			<td></td>
        			</tr>
        			<tr>
            			<td>Database:</td>
            			<td>
						  <?php 
						  $pre = getOption('mysql_prefix');
						  echo getOption('mysql_database')." prefixed by '$pre'"; 
						  ?></td>
            			<td></td>
        			</tr>
        			<tr>
            			<td></td>
            			<td><input type="submit" value="save" /></td>
            			<td></td>
        			</tr>
      			</table>
				</form>
			</div>
			<div class="panel" id="tab_gallery">
				<form action="?page=options&action=saveoptions" method="post">
				<input type="hidden" name="savegalleryoptions" value="yes" />
				<table class="bordered">
         			<tr> 
               			<th colspan="3"><h2>General Gallery Configuration</h2></th>
          			</tr>    
        			<tr>
            			<td width="175">Gallery title:</td>
            			<td width="200"><input type="text" size="40" name="gallery_title" value="<?php echo getOption('gallery_title');?>" /></td>
            			<td>What you want to call your photo gallery.</td>
        			</tr>
        			<tr>
            			<td>Website title:</td>
            			<td><input type="text" size="40" name="website_title" value="<?php echo getOption('website_title');?>" /></td>
            			<td>Your web site title.</td>
        			</tr>
        			<tr>
            		<td>Website url:</td>
            			<td><input type="text" size="40" name="website_url" value="<?php echo getOption('website_url');?>" /></td>
            			<td>This is used to link back to your main site, but your theme must support it.</td>
        				</tr>
        			<tr>
            			<td>Time offset (hours):</td>
            			<td><input type="text" size="40" name="time_offset" value="<?php echo getOption('time_offset');?>" /></td>
            			<td>If you're in a different time zone from your server, set the offset in hours.</td>
        			</tr>
        			<tr>
            			<td>Google Maps API key:</td>
            			<td><input type="text" size="40" name="gmaps_apikey" value="<?php echo getOption('gmaps_apikey');?>" /></td>
            			<td>If you're going to be using Google Maps, <a href="http://www.google.com/apis/maps/signup.html" target="_blank">get an API key</a> and enter it here.</td>
        			</tr>
        			<tr>
            			<td>Enable mod_rewrite:</td>
            			<td><input type="checkbox" name="mod_rewrite" value="1" <?php echo checked('1', getOption('mod_rewrite')); ?> /></td>
            			<td>If you have Apache <i>mod_rewrite</i>, put a checkmark here, and you'll get nice cruft-free URLs.</td>
        			</tr>
       			 	<tr>
            			<td>Mod_rewrite Image suffix:</td>
            			<td><input type="text" size="40" name="mod_rewrite_image_suffix" value="<?php echo getOption('mod_rewrite_image_suffix');?>" /></td>
            			<td>If <i>mod_rewrite</i> is checked above, zenphoto's image page URL's usually end in .jpg. Set this if you want something else appended to the end (helps search engines). Examples: <i>.html, .php, /view</i>, etc.</td>
        			</tr>
        			<tr>
            			<td>Server protocol:</td>
            			<td><input type="text" size="40" name="server_protocol" value="<?php echo getOption('server_protocol');?>" /></td>
            			<td>If you're running a secure server, change this to <i>https</i> (Most people will leave this alone.)</td>
        			<tr/>
        			<tr>
            			<td>Charset:</td>
            			<td><input type="text" size="40" name="charset" value="<?php echo getOption('charset');?>" /></td>
            			<td>The character encoding to use internally. Leave at <i>UTF-8</i> if you're unsure.</td>
        			</tr>
					<!-- SPAM filter options -->
        			<tr>
          				<td>Spam filter:</td>
            			<td>
                        	<select id="spam_filter" name="spam_filter">          
        					<?php
          					  $currentValue = getOption('spam_filter');
          					  $pluginroot = SERVERPATH . "/" . ZENFOLDER . "/plugins/spamfilters";
          					  generateListFromFiles($currentValue, $pluginroot , '.php');
        					?>
        					</select>
            			</td>
            			<td>The SPAM filter plug-in you wish to use to check comments for SPAM</td>
        			</tr>
        			<?php 
        			  /* procss filter based options here */
      				  if (!(false === ($requirePath = getPlugin('spamfilters/'.getOption('spam_filter').'.php', false)))) {       
        			    require_once($requirePath);
        			    $optionHandler = new SpamFilter();
        				customOptions($optionHandler, "&nbsp;&nbsp;&nbsp;-&nbsp;");
      				  } 

    				?>   
					<!-- end of SPAM filter options -->
        			<tr>
            			<td>Enable comment notification:</td>
            			<td><input type="checkbox" name="email_new_comments" value="1" <?php echo checked('1', getOption('email_new_comments')); ?> /></td>
            			<td>Email the Admin when new comments are posted</td>
        			</tr>
        			<tr>
            			<td>Number of RSS feed items:</td>
            			<td><input type="text" size="40" name="feed_items" value="<?php echo getOption('feed_items');?>" /></td>
            			<td>The number of new images/albums/comments you want to appear in your site's RSS feed.</td>
        			</tr>
        			<tr>
            			<td>Sort gallery by: </td>
            			<td>
              				<select id="sortselect" name="gallery_sorttype">
              				<?php foreach ($sortby as $sorttype) { ?>
                				<option value="<?php echo $sorttype; ?>"<?php if ($sorttype == getOption('gallery_sorttype')) echo ' selected="selected"'; ?>><?php echo $sorttype; ?></option>
              				<?php } ?>
              				</select>
            			</td>
             			<td>Sort order for the albums on the index of the gallery</td>
        			</tr>
        			<tr>
            			<td>Sort decending:</td>
            			<td><input type="checkbox" name="gallery_sortdirection" value="1" <?php echo checked('1', getOption('gallery_sortdirection')); ?> /></td>
            			<td>Gallery sort direction will be decending if this option is checked</td>
        			</tr>
					<tr>
		  				<td>Search fields:</td>
		    			<td>
		    				<?php $fields = getOption('search_fields'); ?>
							<input type="checkbox" name="sf_title" value=1 <?php if ($fields & SEARCH_TITLE) echo ' checked'; ?>> Title<br/>
            				<input type="checkbox" name="sf_desc" value=1 <?php if ($fields & SEARCH_DESC) echo ' checked'; ?>> Description<br/>
            				<input type="checkbox" name="sf_tags" value=1 <?php if ($fields & SEARCH_TAGS) echo ' checked'; ?>> Tags<br/>
            				<input type="checkbox" name="sf_filename" value=1 <?php if ($fields & SEARCH_FILENAME) echo ' checked'; ?>> File/Folder name<br/>
            				<input type="checkbox" name="sf_location" value=1 <?php if ($fields & SEARCH_LOCATION) echo ' checked'; ?>> Location<br/>
            				<input type="checkbox" name="sf_city" value=1 <?php if ($fields & SEARCH_CITY) echo ' checked'; ?>> City<br/>
            				<input type="checkbox" name="sf_state" value=1 <?php if ($fields & SEARCH_STATE) echo ' checked'; ?>> State<br/>
            				<input type="checkbox" name="sf_country" value=1 <?php if ($fields & SEARCH_COUNTRY) echo ' checked'; ?>> Country<br/>
	        			</td>
                        <td>The set of fields on which searches may be performed.</td>
					</tr>
        			<tr>
            			<td></td>
            			<td><input type="submit" value="save" /></td>
            			<td></td>
        			</tr>
    			</table>
				</form>
			</div>

			<div class="panel" id="tab_image">
				<form action="?page=options&action=saveoptions" method="post">
				<input type="hidden" name="saveimageoptions" value="yes" />
				<table class="bordered">
   					<tr> 
      					<th colspan="3"><h2>Image Display</h2></th>
    				</tr>    
        			<tr>
            			<td width="175">Image quality:</td>
            			<td width="200"><input type="text" size="40" name="image_quality" value="<?php echo getOption('image_quality');?>" /></td>
            			<td>JPEG Compression quality for all images.</td>
        			</tr>
                    <tr>
            			<td>Thumb quality:</td>
            			<td><input type="text" size="40" name="thumb_quality" value="<?php echo getOption('thumb_quality');?>" /></td>
            			<td>JPEG Compression quality for all thumbnails.</td>
        			</tr>
        			<tr>
            			<td>Image size:</td>
            			<td><input type="text" size="40" name="image_size" value="<?php echo getOption('image_size');?>" /></td>
            			<td>Default image display width.</td>
        			</tr>
        			<tr>
            			<td>Images size is longest size:</td>
            			<td><input type="checkbox" size="40" name="image_use_longest_side" value="1" <?php echo checked('1', getOption('image_use_longest_side')); ?> /></td>
            			<td>If this is set to true, then the longest side of the image will be <i>image size</i>. Otherwise, the <i>width</i> of the image will be <i>image size</i>.</td>
        			</tr>
        			<tr>
            			<td>Allow upscale:</td>
            			<td><input type="checkbox" size="40" name="image_allow_upscale" value="1" <?php echo checked('1', getOption('image_allow_upscale')); ?> /></td>
            			<td>Allow images to be scaled up to the requested size. This could result in loss of quality, so it's off by default.</td>
                    </tr>
        			<tr>
            			<td>Thumb size:</td>
            			<td><input type="text" size="40" name="thumb_size" value="<?php echo getOption('thumb_size');?>" /></td>
            			<td>Default thumbnail size and scale.</td>
        			</tr>
        			<tr>
           				<td>Crop thumbnails:</td>
            			<td><input type="checkbox" size="40" name="thumb_crop" value="1" <?php echo checked('1', getOption('thumb_crop')); ?> /></td>
            			<td>If set to true the thumbnail will be a centered portion of the image with the given width and height after being resized to <i>thumb size</i> (by shortest side). Otherwise, it will be the full image resized to <i>thumb size</i> (by shortest side).</td>
        			</tr>
        			<tr>
            			<td>Crop thumbnail width:</td>
            			<td><input type="text" size="40" name="thumb_crop_width" value="<?php echo getOption('thumb_crop_width');?>" /></td>
            			<td>The <i>thumb crop width</i> should always be less than or equal to <i>thumb size</i></td>
        			</tr>
        			<tr>
            			<td>Crop thumbnail height:</td>
            			<td><input type="text" size="40" name="thumb_crop_height" value="<?php echo getOption('thumb_crop_height');?>" /></td>
            			<td>The <i>thumb crop height</i> should always be less than or equal to <i>thumb size</i></td>
        			</tr>
        			<tr>
            			<td>Sharpen thumbnails:</td>
            			<td><input type="checkbox" name="thumb_sharpen" value="1" <?php echo checked('1', getOption('thumb_sharpen')); ?> /></td>
            			<td>Add a small amount of unsharp mask to thumbnails. Slows thumbnail generation on slow servers.</td>
        			</tr>
        			<tr>
            			<td>Albums per page:</td>
            			<td><input type="text" size="40" name="albums_per_page" value="<?php echo getOption('albums_per_page');?>" /></td>
            			<td>Controls the number of albums on a page. You might need to change this after switching themes to make it look better.</td>
        			</tr>
        			<tr>
            			<td>Images per page:</td>
            			<td><input type="text" size="40" name="images_per_page" value="<?php echo getOption('images_per_page');?>" /></td>
            			<td>Controls the number of images on a page. You might need to change this after switching themes to make it look better.</td>
        			</tr>
        			<tr>
            			<td>Watermark image:</td>
            			<td><input type="checkbox" name="perform_watermark" value="1" <?php echo checked('1', getOption('perform_watermark')); ?> /></td>
            			<td>Controls watermarking of an image</td>
        			</tr>
        			<tr>
            			<td>Image for image watermark:</td>
            			<td>
							<?php
			  				  $v = explode("/", getOption('watermark_image'));	  
              				  $v = str_replace('.png', "", $v[count($v)-1]);			  
			  				  echo "<select id=\"watermark_image\" name=\"watermark_image\">\n";
              				  generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/images' , '.png');
              				  echo "</select>\n";
	        				?>
						</td>
            			<td>The watermark image (png-24). (Place the image in the <?php echo ZENFOLDER; ?>/images/ directory.)</td>
        			</tr>
        			<tr>
            			<td>Watermark video:</td>
            			<td><input type="checkbox" name="perform_video_watermark" value="1" <?php echo checked('1', getOption('perform_video_watermark')); ?> /></td>
            			<td>Controls watermarking of a video</td>
        			</tr>
        			<tr>
            			<td>Image for video watermark:</td>
						<td>
						<?php
			  			  $v = explode("/", getOption('video_watermark_image'));	
              			  $v = str_replace('.png', "", $v[count($v)-1]);			  
			  			  echo "<select id=\"videowatermarkimage\" name=\"video_watermark_image\">\n";
              			  generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/images' , '.png');
              			  echo "</select>\n";
	        			?>
						</td>
            			<td>The watermark image (png-24). (Place the image in the <?php echo ZENFOLDER; ?>/images/ directory.)</td>
        			</tr>
        			<tr>
            			<td></td>
            			<td><input type="submit" value="save" /></td>
            			<td></td>
        			</tr>
    			</table>   
                </form>				
			</div>
			<div class="panel" id="tab_theme">
				<form action="?page=options&action=saveoptions" method="post">
				<input type="hidden" name="savethemeoptions" value="yes" />
				<?php
				  /* handle theme options */
				  if (!(false === ($requirePath = getPlugin('themeoptions.php', true)))) {
        		    require_once($requirePath);
        		    $optionHandler = new ThemeOptions();
      
        		    $supportedOptions = $optionHandler->getOptionsSupported();
       			    if (count($supportedOptions) > 0) { 
          		  	  echo "<table class='bordered'>\n";
             	      echo "<tr><th colspan='3'><h2>Theme Options for <i>".$gallery->getCurrentTheme()."</i></h2></th></tr>\n";
             
          		  	  customOptions($optionHandler);

          		  	  echo "\n<tr>\n";
          		  	  echo "<td></td>\n";
          		  	  echo  '<td><input type="submit" value="save" /></td>' . "\n";
          		  	  echo "<td></td>\n";
          		  	  echo "</tr>\n";
          		  	  echo "<table/>\n";
        			}     
      			  }     
				?>
			</form>
			</div>
		</div>
      
      
<?php /*** THEMES (Theme Switcher) *******************************************************/ 
      /************************************************************************************/ ?> 
      
    <?php } else if ($page == "themes") { ?>
      
      <h1>General Options</h1>
      
      <h2>Themes (current theme is <i><?php echo $current_theme = $gallery->getCurrentTheme();?></i>)</h2>
      <p>Themes allow you to visually change the entire look and feel of your gallery. All themes are located in your <code>zenphoto/themes</code> folder, and you can download more themes at the <a href="http://www.zenphoto.org/support/">zenphoto forum</a> and <a href="http://www.zenphoto.org/trac/wiki/ZenphotoThemes">trac themes page</a>.</p>
      <table class="bordered">
        <?php 
        $themes = $gallery->getThemes();
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
                <img height="150" width="150" src="<?php echo $themeimage; ?>" alt="Theme Screenshot" />
                
            <?php } ?>
          </td>
          <td<?php echo $style; ?>>
            <strong><?php echo $themeinfo['name']; ?></strong><br />
            <?php echo $themeinfo['author']; ?><br />
            Version <?php echo $themeinfo['version']; ?>, <?php echo $themeinfo['date']; ?><br />
            <?php echo $themeinfo['desc']; ?>
          </td>
          <td width="100"<?php echo $style; ?>>
            <?php if (!($theme == $current_theme)) { ?>
              <a href="?page=themes&action=settheme&theme=<?php echo $theme; ?>" title="Set this as your theme">Use this Theme</a>
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
              . (getOption("mod_rewrite") ? "../$album/$image" : "../index.php?album=".urlencode($album)."&image=".urlencode($image))
              . "\">$albumtitle / $title</a>:</div><div class=\"commentbody\">$comment</div></li>";
          }
        ?>
        </ul>
      </div>
      
      
        <div class="box" id="overview-stats">
            <h2 class="boxtitle">Gallery Maintenance</h2>
            <p>Your database is <b><?php echo getOption('mysql_database'); ?></b></p>
            <p><strong><a href="?prune=true">Refresh the Database</a></strong> - This cleans the database, removes any orphan entries for comments, images, and albums.</p>
            <p><strong><a href="cache-images.php">Pre-Cache Images</a></strong> - Finds newly uploaded images that have not been cached and creates the cached version. It also refreshes the numbers above. If you have a large number of images in your gallery you might consider using the <em>pre-cache image</em> link for each album to avoid swamping your browser.</p>
            <p><strong><a href="refresh-metadata.php">Refresh Image Metadata</a></strong> - Forces a refresh of the EXIF and IPTC data for all images.</p>
        </div>
      
      
      <div class="box" id="overview-suggest">
        <h2 class="boxtitle">Gallery Stats</h2>
          <p><strong><?php echo $gallery->getNumImages(); ?></strong> images.</p>
          <p><strong><?php echo $gallery->getNumAlbums(true); ?></strong> albums.</p>
          <p><strong><?php echo $gallery->getNumComments(); ?></strong> comments.</p>
          <?php
              // These disk operations are too slow...
              /*
              <p>Total size of album images: <strong><?php echo size_readable($gallery->sizeOfImages(), "MB"); ?></strong></p>
              <p>Total size of image cache: <strong><?php echo size_readable($gallery->sizeOfCache(), "MB"); ?></strong></p>
              */
          ?>
      </div>
      
      <p style="clear: both; "></p>
      
    <?php } ?>
    
    </div>
    
<?php printAdminFooter(); ?>

  <?php zenSortablesFooter(); ?>
  
<?php } /* No admin-only content allowed after this bracket! */ ?>
    
</body>
</html>
