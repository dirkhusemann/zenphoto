<?php  /* Don't put anything before this line! */
session_start();
define('OFFSET_PATH', true);
require_once("sortable.php");
$adm = getOption('adminuser');
$pas = getOption('adminpass');
$rsd = getOption('admin_reset_date');
if (empty($rsd) || empty($adm) || empty($pas)) {
  $_zp_null_account = true;  // require setting admin user/password
} else {
  $_zp_null_account = false;
}

$sortby = array('Filename', 'Date', 'Title', 'Manual', 'ID' );
$standardOptions = array('gallery_title','website_title','website_url','time_offset',
                         'gmaps_apikey','mod_rewrite','mod_rewrite_image_suffix',
                         'admin_email','admin_name','server_protocol','charset','image_quality',
                         'thumb_quality','image_size','image_use_longest_side',
                         'image_allow_upscale','thumb_size','thumb_crop',
                         'thumb_crop_width','thumb_crop_height','thumb_sharpen',
                         'albums_per_page','images_per_page','perform_watermark',
                         'watermark_image','adminuser','adminpass','current_theme', 'spam_filter',
                         'email_new_comments', 'perform_video_watermark', 'video_watermark_image',
                         'gallery_sorttype', 'gallery_sortdirection', 'feed_items', 'search_fields',
                         'gallery_password', 'gallery_hint', 'search_password', 'search_hint',
                         'allowed_tags', 'full_image_download', 'full_image_quality');
$charsets = array("ASMO-708" => "Arabic",
                  "big5" => "Chinese Traditional",
                  "CP1026" => "IBM EBCDIC (Turkish Latin-5)",
                  "cp866" => "Cyrillic (DOS)",
                  "CP870" => "IBM EBCDIC (Multilingual Latin-2)",
                  "csISO2022JP" => "Japanese (JIS-Allow 1 byte Kana)",
                  "DOS-720" => "Arabic (DOS)",
                  "DOS-862" => "Hebrew (DOS)",
                  "ebcdic-cp-us" => "IBM EBCDIC (US-Canada)",
                  "EUC-CN" => "Chinese Simplified (EUC)",
                  "euc-jp" => "Japanese (EUC)",
                  "euc-kr" => "Korean (EUC)",
                  "gb2312" => "Chinese Simplified (GB2312)",
                  "hz-gb-2312" => "Chinese Simplified (HZ)",
                  "IBM437" => "OEM United States",
                  "ibm737" => "Greek (DOS)",
                  "ibm775" => "Baltic (DOS)",
                  "ibm850" => "Western European (DOS)",
                  "ibm852" => "Central European (DOS)",
                  "ibm857" => "Turkish (DOS)",
                  "ibm861" => "Icelandic (DOS)",
                  "ibm869" => "Greek, Modern (DOS)",
                  "iso-2022-jp" => "Japanese (JIS)",
                  "iso-2022-jp" => "Japanese (JIS-Allow 1 byte Kana - SO/SI)",
                  "iso-2022-kr" => "Korean (ISO)",
                  "iso-8859-1" => "Western European (ISO)",
                  "iso-8859-15" => "Latin 9 (ISO)",
                  "iso-8859-2" => "Central European (ISO)",
                  "iso-8859-3" => "Latin 3 (ISO)",
                  "iso-8859-4" => "Baltic (ISO)",
                  "iso-8859-5" => "Cyrillic (ISO)",
                  "iso-8859-6" => "Arabic (ISO)",
                  "iso-8859-7" => "Greek (ISO)",
                  "iso-8859-8" => "Hebrew (ISO-Visual)",
                  "iso-8859-8-i" => "Hebrew (ISO-Logical)",
                  "iso-8859-9" => "Turkish (ISO)",
                  "Johab" => "Korean (Johab)",
                  "koi8-r" => "Cyrillic (KOI8-R)",
                  "koi8-u" => "Cyrillic (KOI8-U)",
                  "ks_c_5601-1987" => "Korean",
                  "macintosh" => "Western European (Mac)",
                  "shift_jis" => "Japanese (Shift-JIS)",
                  "unicode" => "Unicode",                  
                  "unicodeFFFE" => "Unicode (Big-Endian)",
                  "us-ascii" => "US-ASCII",
                  "utf-7" => "Unicode (UTF-7)",
                  "utf-8" => "Unicode (UTF-8)",
                  "windows-1250" => "Central European (Windows)",
                  "windows-1251" => "Cyrillic (Windows)",
                  "Windows-1252" => "Western European (Windows)",
                  "windows-1253" => "Greek (Windows)",
                  "windows-1254" => "Turkish (Windows)",
                  "windows-1255" => "Hebrew (Windows)",
                  "windows-1256" => "Arabic (Windows)",
                  "windows-1257" => "Baltic (Windows)",                  
                  "windows-1258" => "Vietnamese (Windows)",
                  "windows-874" => "Thai (Windows)",
                  "x-Chinese-CNS" => "Chinese Traditional (CNS)",
                  "x-Chinese-Eten" => "Chinese Traditional (Eten)",
                  "x-EBCDIC-Arabic" => "IBM EBCDIC (Arabic)",
                  "x-ebcdic-cp-us-euro" => "IBM EBCDIC (US-Canada-Euro)",
                  "x-EBCDIC-CyrillicRussian" => "IBM EBCDIC (Cyrillic Russian)",
                  "x-EBCDIC-CyrillicSerbianBulgarian" => "IBM EBCDIC (Cyrillic Serbian-Bulgarian)",
                  "x-EBCDIC-DenmarkNorway" => "IBM EBCDIC (Denmark-Norway)",
                  "x-ebcdic-denmarknorway-euro" => "IBM EBCDIC (Denmark-Norway-Euro)",
                  "x-EBCDIC-FinlandSweden" => "IBM EBCDIC (Finland-Sweden)",
                  "x-ebcdic-finlandsweden-euro" => "IBM EBCDIC (Finland-Sweden-Euro)",
                  "x-ebcdic-finlandsweden-euro" => "IBM EBCDIC (Finland-Sweden-Euro)",
                  "x-ebcdic-france-euro" => "IBM EBCDIC (France-Euro)",
                  "x-EBCDIC-Germany" => "IBM EBCDIC (Germany)",
                  "x-ebcdic-germany-euro" => "IBM EBCDIC (Germany-Euro)",
                  "x-EBCDIC-Greek" => "IBM EBCDIC (Greek)",
                  "x-EBCDIC-GreekModern" => "IBM EBCDIC (Greek Modern)",
                  "x-EBCDIC-Hebrew" => "IBM EBCDIC (Hebrew)",
                  "x-EBCDIC-Icelandic" => "IBM EBCDIC (Icelandic)",
                  "x-ebcdic-icelandic-euro" => "IBM EBCDIC (Icelandic-Euro)",
                  "x-ebcdic-international-euro" => "IBM EBCDIC (International-Euro)",
                  "x-EBCDIC-Italy" => "IBM EBCDIC (Italy)",
                  "x-ebcdic-italy-euro" => "IBM EBCDIC (Italy-Euro)",
                  "x-EBCDIC-JapaneseAndJapaneseLatin" => "IBM EBCDIC (Japanese and Japanese-Latin)",
                  "x-EBCDIC-JapaneseAndKana" => "IBM EBCDIC (Japanese and Japanese Katakana)",
                  "x-EBCDIC-JapaneseAndUSCanada" => "IBM EBCDIC (Japanese and US-Canada)",                  
                  "x-EBCDIC-JapaneseKatakana" => "IBM EBCDIC (Japanese katakana)",
                  "x-EBCDIC-KoreanAndKoreanExtended" => "IBM EBCDIC (Korean and Korean Extended)",
                  "x-EBCDIC-KoreanExtended" => "IBM EBCDIC (Korean Extended)",
                  "x-EBCDIC-SimplifiedChinese" => "IBM EBCDIC (Simplified Chinese)",
                  "X-EBCDIC-Spain" => "IBM EBCDIC (Spain)",
                  "x-ebcdic-spain-euro" => "IBM EBCDIC (Spain-Euro)",
                  "x-EBCDIC-Thai" => "IBM EBCDIC (Thai)",
                  "x-EBCDIC-TraditionalChinese" => "IBM EBCDIC (Traditional Chinese)",
                  "x-EBCDIC-Turkish" => "IBM EBCDIC (Turkish)",
                  "x-EBCDIC-UK" => "IBM EBCDIC (UK)",
                  "x-ebcdic-uk-euro" => "IBM EBCDIC (UK-Euro)",
                  "x-Europa" => "Europa",
                  "x-IA5" => "Western European (IA5)",
                  "x-IA5-German" => "German (IA5)",
                  "x-IA5-Norwegian" => "Norwegian (IA5)",
                  "x-IA5-Swedish" => "Swedish (IA5)",
                  "x-iscii-as" => "ISCII Assamese",
                  "x-iscii-be" => "ISCII Bengali",
                  "x-iscii-de" => "ISCII Devanagari",
                  "x-iscii-gu" => "ISCII Gujarathi",
                  "x-iscii-ka" => "ISCII Kannada",
                  "x-iscii-ma" => "ISCII Malayalam",
                  "x-iscii-or" => "ISCII Oriya",
                  "x-iscii-pa" => "ISCII Panjabi",
                  "x-iscii-ta" => "ISCII Tamil",
                  "x-iscii-te" => "ISCII Telugu",
                  "x-mac-arabic" => "Arabic (Mac)",
                  "x-mac-ce" => "Central European (Mac)",
                  "x-mac-chinesesimp" => "Chinese Simplified (Mac)",
                  "x-mac-chinesetrad" => "Chinese Traditional (Mac)",
                  "x-mac-cyrillic" => "Cyrillic (Mac)",
                  "x-mac-greek" => "Greek (Mac)",
                  "x-mac-hebrew" => "Hebrew (Mac)",
                  "x-mac-icelandic" => "Icelandic (Mac)",
                  "x-mac-japanese" => "Japanese (Mac)",
                  "x-mac-korean" => "Korean (Mac)",
                  "x-mac-turkish" => "Turkish (Mac)"
                 );

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

/** Publish album  *************************************************************/
/******************************************************************************/
    if ($action == "publish") {
      $folder = queryDecode(strip($_GET['album']));
      $album = new Album($gallery, $folder);
      $album->setShow($_GET['value']);
      $album->save();
      header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit');
      exit();
    
/** un-moderate comment *********************************************************/
/********************************************************************************/
    } else if ($action == "moderation") {
      $sql = 'UPDATE ' . prefix('comments') . ' SET `inmoderation`=0 WHERE `id`=' . $_GET['id'] . ';';
      query($sql);      
      header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=comments');
      exit();

/** Reset hitcounters ***********************************************************/
/********************************************************************************/
    } else if ($action == "reset_hitcounters") {
      if (isset($_GET['albumid'])) $id = $_GET['albumid'];
      if (isset($_POST['albumid'])) $id = $_POST['albumid'];
      if(isset($id)) {
        $where = ' WHERE `id`='.$id;
        $imgwhere = ' WHERE `albumid`='.$id;
        $return = '?page=edit';
        if (isset($_GET['return'])) $rt = $_GET['return'];
        if (isset($_POST['return'])) $rt = $_POST['return'];
        if (isset($rt)) {
          $return .= '&album=' . $rt;
        }
      } else {
        $where = '';
        $imgwhere = '';
        $return = '';
      }
      query("UPDATE " . prefix('albums') . " SET `hitcounter`= 0" . $where);
      query("UPDATE " . prefix('images') . " SET `hitcounter`= 0" . $imgwhere);
      header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php' . $return);
      exit();

/** SAVE **********************************************************************/
/******************************************************************************/
    } else if ($action == "save") {

/** SAVE A SINGLE ALBUM *******************************************************/
      if ($_POST['album']) {

        $folder = queryDecode(strip($_POST['album']));
        $album = new Album($gallery, $folder);
        $notify = '';
        if (isset($_POST['savealbuminfo'])) {
          $notify = processAlbumEdit(0, $album);
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
              $image->setCredit(strip($_POST["$i-credit"]));
              $image->setCopyright(strip($_POST["$i-copyright"]));
              $image->setTags(strip($_POST["$i-tags"]));
              $image->setDateTime(strip($_POST["$i-date"]));
              $image->setShow(strip($_POST["$i-Visible"]));
              $image->setCommentsAllowed(strip($_POST["$i-allowcomments"]));
              if (isset($_POST["$i-reset_hitcounter"])) {
                $id = $image->id;
                query("UPDATE " . prefix('images') . " SET `hitcounter`= 0 WHERE `id` = $id");
              }
              $image->save();
            }
          }
        }

/** SAVE MULTIPLE ALBUMS ******************************************************/
      } else if ($_POST['totalalbums']) {
        for ($i = 1; $i <= $_POST['totalalbums']; $i++) {
          $folder = queryDecode(strip($_POST["$i-folder"]));
          $album = new Album($gallery, $folder);
          $rslt = processAlbumEdit($i, $album);
          if (!empty($rslt)) { $notify = $rslt; }
        }
      }
      // Redirect to the same album we saved.
      $qs_albumsuffix = "&massedit";
      if ($_GET['album']) {
        $folder = queryDecode(strip($_GET['album']));
        $qs_albumsuffix = '&album='.urlencode($folder);
      }
      header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit' . $qs_albumsuffix . $notify . '&saved');
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

        $reject = array ("'", '"', "%"); // any characters in file names that will give us problems
        $error = false;
        foreach ($_FILES['files']['error'] as $key => $error) {
          if ($_FILES['files']['name'][$key] == "") continue;
          if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['files']['tmp_name'][$key];
            $name = $_FILES['files']['name'][$key];
            foreach ($reject as $chr) {
              $name = str_replace($chr, "", $name); // get rid of problem characters
            }
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
      $captcha = getOption('Use_Captcha');
      $notify = '';
      $returntab = "";
      /*** admin options ***/
      if (isset($_POST['saveadminoptions'])) {
        $olduser = getOption('adminuser');
        $pwd = trim($_POST['adminpass']);
        if (empty($pwd) && !empty($_POST['adminpass']) && ($olduser != $_POST['adminuser'])) {
          $notify = '&mismatch=newuser';
        } else if ($pwd == $_POST['adminpass_2']) {
          setOption('adminuser', $_POST['adminuser']);
          if (empty($pwd)) {
            if (empty($_POST['adminpass'])) {
              setOption('adminpass', NULL);
            }
          } else {
            setOption('adminpass', md5($_POST['adminuser'] . $pwd));
          }
          setOption('admin_reset_date', '1');
        } else {
          $notify = '&mismatch=password';
        }
        setOption('admin_email', $_POST['admin_email']);
        setOption('admin_name', $_POST['admin_name']);
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
        setOption('gallery_sorttype', $_POST['gallery_sorttype']);
        if ($_POST['gallery_sorttype'] == 'Manual') {
          setBoolOption('gallery_sortdirection', 0);
        } else {
          setBoolOption('gallery_sortdirection', $_POST['gallery_sortdirection']);
        }
        setOption('image_sorttype', $_POST['image_sorttype']);
        setBoolOption('image_sortdirection', $_POST['image_sortdirection']);
        setOption('feed_items', $_POST['feed_items']);
        $search = new SearchEngine();
        setOption('search_fields', 32767); // make SearchEngine allow all options so getQueryFields() will gives back what was choosen this time
        setOption('search_fields', $search->getQueryFields());
        if ($_POST['gallerypass'] == $_POST['gallerypass_2']) {
          $pwd = trim($_POST['gallerypass']);
          if (empty($pwd)) {
            if (empty($_POST['gallerypass'])) {
              setOption('gallery_password', NULL);  // clear the gallery password
            }
          } else {
            setOption('gallery_password', md5($pwd));
          }
        } else {
          $notify = '&mismatch=gallery';
        }
        if ($_POST['searchpass'] == $_POST['searchpass_2']) {
          $pwd = trim($_POST['searchpass']);
          if (empty($pwd)) {
            if (empty($_POST['searchpass'])) {
              setOption('search_password', NULL);  // clear the gallery password
            }
          } else {
            setOption('search_password', md5($pwd));
          }
        } else {
          $notify = '&mismatch=search';
        }
        setOption('gallery_hint', $_POST['gallery_hint']);
        setOption('search_hint', $_POST['search_hint']);
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
        setBoolOption('full_image_download', $_POST['full_image_download']);
        setOption('full_image_quality', $_POST['full_image_quality']);
        $returntab = "#tab_image";
      }
      /*** Comment options ***/
      if (isset($_POST['savecommentoptions'])) {
        setOption('spam_filter', $_POST['spam_filter']);
        setBoolOption('email_new_comments', $_POST['email_new_comments']);
        $tags = $_POST['allowed_tags'];
        $test = "(".$tags.")";
        $a = parseAllowedTags($test);
        if ($a !== false) {
          setOption('allowed_tags', $tags);
          $notify = '';
        } else {
          $notify = '&tag_parse_error';
        }
        setBoolOption('comment_name_required', $_POST['comment_name_required']);
        setBoolOption('comment_email_required', $_POST['comment_email_required']);
        setBoolOption('comment_web_required', $_POST['comment_web_required']);
        $returntab = "#tab_comments";

      }
      /*** Theme options ***/
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
      if (($wm != getOption('perform_watermark')) || ($vwm != getOption('perform_video_watermark'))) {
        $gallery->clearCache(); // watermarks (or lack there of) are cached, need to start fresh if the option has changed
      }
      if ($captcha && !getOption('Use_Captcha')) { // No longer using captcha, clean up the images
        chdir(SERVERCACHE . "/");
        $filelist = glob('code_*.png');
        foreach ($filelist as $file) {
          $file = SERVERCACHE . "/" . $file;
            unlink($file);
        }
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
  if (isset($_GET['emailreset'])) {
    $adm = getOption('adminuser');
    $pas = getOption('adminpass');
    setOption('admin_reset_date', time());
    $req = getOption('admin_reset_date');
    $ref = md5($req . $adm . $pas);
    $msg .= "\nYou are receiving this e-mail becauseof a password reset request on your Zenphoto gallery." .
            "\nTo reset your Zenphoto Admin password visit: ".FULLWEBPATH."/".ZENFOLDER."/admin.php?ticket=$ref" .
            "\nIf you do not wish to reset your password just ignore this message. This ticket will automatically expire in 3 days.";
    zp_mail('The Zenphoto information you requested',  $msg);
  }
}

/* NO Admin-only content between this and the next check. */

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
    echo "<h2>You need to set your admin username and password.</h2>";
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
        <h1>Edit Album: <em><?php echo $album->name; ?></em></h1>
        <p>
        <?php printAdminLinks("edit" . $albumdir, "&laquo; Back", "Back to the list of albums (go up one level)");?> |
        <?php printSortLink($album, "Sort Album", "Sort Album"); ?> |
        <?php printViewLink($album, "View Album", "View Album"); ?>
        </p>

        <?php displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */ ?>
        <?php
        if (isset($_GET['saved'])) {
          if (isset($_GET['mismatch'])) {
        ?>
          <div class="errorbox" id="message1">
            <h2>Your passwords did not match</h2>
          </div>
        <?php
          } else {
        ?>
          <div class="messagebox" id="message1">
            <h2>Save Successful</h2>
          </div>
        <?php } ?>
          <script type="text/javascript">
            window.setTimeout('Effect.Fade($(\'message1\'))', 2500);
          </script>
        <?php } ?>

        <!-- Album info box -->

        <form name="albumedit1" action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>" method="post">
        <input type="hidden" name="album" value="<?php echo $album->name; ?>" />
        <input type="hidden" name="savealbuminfo" value="1" />
        <?php printAlbumEditForm(0, $album); ?>
        </form>
        <?php printAlbumButtons($album) ?>
        <!-- Subalbum list goes here -->

        <?php

        if (isset($_GET['subalbumsaved'])) {
          $album->setSubalbumSortType('Manual');
          $album->setSortDirection('album', 0);
          $album->save();
        }
        $subalbums = $album->getSubAlbums();
        if (count($subalbums) > 0) {
        if ($album->getNumImages() > 0)  { ?>
            <p><a name="subalbumList"></a><a href="#imageList" title="Scroll down to the image list.">Image List &raquo;</a></p>
          <?php } ?>

          <table class="bordered" width="100%">
            <input type="hidden" name="subalbumsortby" value="Manual" />
            <tr>
              <th colspan="8"><h1>Albums</h1></th>
            </tr>
            <tr>
              <td colspan="8">
                Drag the albums into the order you wish them displayed. Select an album to edit its description and data, or <a href="?page=edit&album=<?php echo urlencode($album->name)?>&massedit">mass-edit all album data</a>.
              </td>
            </tr>
            <tr>
            <td style="padding: 0px 0px;" colspan="8">
            <div id="albumList" class="albumList">
              <?php
                foreach ($subalbums as $folder) {
                  $subalbum = new Album($album, $folder);
                  printAlbumEditRow($subalbum);
                }
              ?>

            </div>
            </tr>
            <tr>
              <td colspan="8">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <img src="images/lock.png" style="border: 0px;" alt="Protected" />Has Password&nbsp;
      <img src="images/pass.png" style="border: 0px;" alt="Published" />Published&nbsp;
      <img src="images/action.png" style="border: 0px;" alt="Unpublished" />Unpublished&nbsp;
      <img src="images/cache.png" style="border: 0px;" alt="Cache the album" />Cache the album&nbsp;
      <img src="images/warn.png" style="border: 0px;" alt="Refresh image metadata" />Refresh image metadata&nbsp;
      <img src="images/reset.png" style="border: 0px;" alt="Reset hitcounters" />Reset hitcounters&nbsp;
      <img src="images/fail.png" style="border: 0px;" alt="Delete" /></a>Delete&nbsp;
                <?php
                zenSortablesSaveButton("?page=edit&album=" . urlencode($album->name) . "&subalbumsaved", "Save Order");
                ?>
              </td>
            </tr>
          </table>

          <?php
            if (isset($_GET['subalbumsaved'])) {
              echo "<p>Subalbum order saved.</p>";
            }
          } ?>

     <!-- Images List -->

      <?php if (count($album->getSubalbums()) > 10) { ?>
        <p><a name="imageList"></a><a href="#subalbumList" title="Scroll up to the sub-album list">&laquo; Subalbum List</a></p>
      <?php } 
      if (count($album->getImages())) {
      ?>

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
                  <?php
                    $id = $image->id;
                    $result = query_single_row("SELECT `hitcounter` FROM " . prefix('images') . " WHERE `id` = $id");
                    $hc = $result['hitcounter'];
                    if (empty($hc)) { $hc = '0'; }
                    echo "<td></td><td>Hit counter: ". $hc . " <input type=\"checkbox\" name=\"reset_hitcounter\"> Reset</td>";
                  ?>
                  </tr>
                  <tr><td align="right" valign="top">Description: </td> <td><textarea name="<?php echo $currentimage; ?>-desc" cols="60" rows="4" style="width: 360px"><?php echo $image->getDesc(); ?></textarea></td></tr>
                  <tr><td align="right" valign="top">Location: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-location" value="<?php echo $image->getLocation(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">City: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-city" value="<?php echo $image->getCity(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">State: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-state" value="<?php echo $image->getState(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Country: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-country" value="<?php echo $image->getCountry(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Credit: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-credit" value="<?php echo $image->getCredit(); ?>" /></td></tr>
                  <tr><td align="right" valign="top">Copyright: </td> <td><input type="text" size="56" style="width: 360px" name="<?php echo $currentimage; ?>-copyright" value="<?php echo $image->getCopyright(); ?>" /></td></tr>
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
                <img src="images/fail.png" style="border: 0px;" alt="Delete the image <?php echo $image->filename; ?>" /></a>
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
      <?php 
        } 
      } ?>

      <!-- page trailer -->
      <p><a href="?page=edit<?php echo $albumdir ?>" title="Back to the list of albums (go up one level)">&laquo; Back</a></p>


<?php /*** MULTI-ALBUM ***************************************************************************/ ?>

      <?php } else if (isset($_GET['massedit'])) {
        if (isset($_GET['saved'])) {
          if (isset($_GET['mismatch'])) {
            echo "\n<div class=\"errorbox\" id=\"message1\">";
            echo "\n<h2>Your passwords did not match</h2>";
            echo "\n</div>";
          } else {
            echo "\n<div class=\"messagebox\" id=\"message1\">";
            echo "\n<h2>Save Successful</h2>";
            echo "\n</div>";
          }
        }
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
      <h1>Edit All Albums in <?php if (!isset($_GET['album'])) {echo "Gallery";} else {echo "<em>" . $album->name . "</em>";}?></h1>
      <p><a href="?page=edit<?php echo $albumdir ?>" title="Back to the list of albums (go up a level)">&laquo; Back</a></p>
      <div class="box" style="padding: 15px;">

      <form name="albumedit" action="?page=edit&action=save<?php echo $albumdir ?>" method="POST">
        <input type="hidden" name="totalalbums" value="<?php echo sizeof($albums); ?>" />
        <?php
        $currentalbum = 0;
        foreach ($albums as $folder) {
          $currentalbum++;
          $album = new Album($gallery, $folder);
          $images = $album->getImages();
          echo "\n<!-- " . $album->name . " -->\n";
          printAlbumEditForm($currentalbum, $album);
        }
      ?>
      </form>
      <?php printAlbumButtons($album) ?>
      </div>
<?php /*** EDIT ALBUM SELECTION *********************************************************************/ ?>
      <?php } else { /* Display a list of albums to edit. */ ?>
        <h1>Edit Gallery</h1>
        <?php displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */ ?>

        <?php
          if (isset($saved)) {
            setOption('gallery_sorttype', 'Manual');
            setOption('gallery_sortdirection', 0);
          }
        ?>
        <p>Drag the albums into the order you wish them displayed. Select an album to edit its description and data, or <a href="?page=edit&massedit">mass-edit all album data</a>.</p>

        <table class="bordered" width="100%">
          <tr>
            <th style="text-align: left;" width="345">Edit this album</th>
          </tr>
          <tr>
          <td style="padding: 0px 0px;" colspan="2">
          <div id="albumList" class="albumList">
            <?php
            $albums = $gallery->getAlbums();
            foreach ($albums as $folder) {
              $album = new Album($gallery, $folder);
              printAlbumEditRow($album);
            } 
            ?>

          </div>
          </td>
          </tr>
        </table>
        <div>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <img src="images/lock.png" style="border: 0px;" alt="Protected" />Has Password&nbsp;
      <img src="images/pass.png" style="border: 0px;" alt="Published" />Published&nbsp;
      <img src="images/action.png" style="border: 0px;" alt="Unpublished" />Unpublished&nbsp;
      <img src="images/cache.png" style="border: 0px;" alt="Cache the album" />Cache the album&nbsp;
      <img src="images/warn.png" style="border: 0px;" alt="Refresh image metadata" />Refresh image metadata&nbsp;
      <img src="images/reset.png" style="border: 0px;" alt="Reset hitcounters" />Reset hitcounters&nbsp;
      <img src="images/fail.png" style="border: 0px;" alt="Delete" /></a>Delete&nbsp;
      <?php
        zenSortablesSaveButton("?page=edit&saved", "Save Order");
      ?>
      </div>

        <?php
        if (isset($_GET['saved'])) {
          echo "<p>Gallery order saved.</p>";
        }
        ?>


      <?php } ?>


<?php /**** UPLOAD ************************************************************************/
      /************************************************************************************/ ?>

<?php } else if ($page == "upload") {
      $albumlist = array();
      genAlbumList($albumlist);
    ?>

      <script type="text/javascript">
        window.totalinputs = 5;
        // Array of album names for javascript functions.
        var albumArray = new Array (
          <?php
          $separator = '';
          foreach($albumlist as $key => $value) {
            echo $separator . "'" . addslashes($key) . "'";
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
              foreach ($albumlist as $fullfolder => $albumtitle) {
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
                echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].'; border-bottom: 1px dotted #ccc;"' : '')
                  . "$selected>" . $saprefix . $singlefolder . " (" . $albumtitle . ')' . "</option>\n";
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

      $comments = query_full_array("SELECT `id`, `name`, `website`, `type`, `imageid`,"
        . " (date + 0) AS date, comment, email, inmoderation FROM ".prefix('comments')
        . " ORDER BY id DESC " . ($viewall ? "" : "LIMIT 20") );
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
          <th>Album/Image</th>
          <th>Author/Link</th>
          <th>Date/Time</th>
          <th>Comment <?php if(!$fulltext) { ?>(<a href="?page=comments&fulltext<?php echo $viewall ? "&viewall":""; ?>">View full text</a>)
            <?php } else { ?>(<a href="?page=comments<?php echo $viewall ? "&viewall":""; ?>">View truncated</a>)<?php } ?></th>
          <th>E-Mail</th>
          <th>Spam</th>
          <th>Edit</th>
          <th>Delete</th>        </tr>

  <?php
    foreach ($comments as $comment) {
      $id = $comment['id'];
      $author = $comment['name'];
      $email = $comment['email'];
      if ($comment['type']=='images') {
        $imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
                     " WHERE `id`=" . $comment['imageid']);
        if ($imagedata) {
          $imgdata = $imagedata[0];
          $image = $imgdata['filename'];
          if ($imgdata['title'] == "") $title = $image; else $title = $imgdata['title'];
          $title = '/ ' . $title;
          $albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
                       " WHERE `id`=" . $imgdata['albumid']);
          if ($albmdata) {
            $albumdata = $albmdata[0];
            $album = $albumdata['folder'];
            $albumtitle = $albumdata['albumtitle'];
            if (empty($albumtitle)) $albumtitle = $album;
          } else {
            $title = 'database error';
          }
        } else {
          $title = 'database error';
        }
      } else {
        $image = '';
        $title = '';
        $albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
                     " WHERE `id`=" . $comment['imageid']);
        if ($albmdata) {
          $albumdata = $albmdata[0];
          $album = $albumdata['folder'];
          $albumtitle = $albumdata['albumtitle'];
          if (empty($albumtitle)) $albumtitle = $album;
        } else {
          $title = 'database error';
        }
      }
      $date  = myts_date("n/j/Y, g:i a", $comment['date']);
      $website = $comment['website'];
      $shortcomment = truncate_string($comment['comment'], 123);
      $fullcomment = $comment['comment'];
      $inmoderation = $comment['inmoderation'];
  ?>

          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo $id; ?>" onClick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" /></td>
            <td style="font-size: 7pt;"><?php echo "<a href=\"" . (getOption("mod_rewrite") ? "../$album/$image" : "../index.php?album=".urlencode($album).
                      "&image=".urlencode($image)) . "\">$albumtitle $title</a>"; ?></td>
            <td><?php echo $website ? "<a href=\"$website\">$author</a>" : $author; ?></td>
            <td style="font-size: 7pt;"><?php echo $date; ?></td>
            <td><?php echo ($fulltext) ? $fullcomment : $shortcomment; ?></td>
            <td align="center"><a href="mailto:<?php echo $email; ?>?body=<?php echo commentReply($fullcomment, $author, $image, $albumtitle); ?>">
                <img src="images/envelope.png" style="border: 0px;" alt="Reply" /></a></td>
            <td align="center">
              <?php
                if ($inmoderation) {
                  echo "<a href=\"?action=moderation&id=" . $id . "\">";
                  echo '<img src="images/warn.png" style="border: 0px;" alt="remove from moderation" /></a>';
                }
              ?>
            </td>
            <td align="center"><a href="?page=editcomment&id=<?php echo $id; ?>" title="Edit this comment.">
               <img src="images/pencil.png" style="border: 0px;" alt="Edit" /></a></td>
            <td align="center"><a href="javascript: if(confirm('Are you sure you want to delete this comment?')) { window.location='?page=comments&action=deletecomments&id=<?php echo $id; ?>'; }" title="Delete this comment." style="color: #c33;">
                <img src="images/fail.png" style="border: 0px;" alt="Delete" /></a></td>
          </tr>
  <?php } ?>
        <tr>
            <td colspan="9" class="subhead"><label><input type="checkbox" name="allbox" onClick="checkAll(this.form, 'ids[]', this.checked);" /> Check All</label></td>
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
      /**************************************************************************************/
    } else if ($page == "options") {
?>
      <div id="container">
        <div id="mainmenu">
          <ul id="tabs">
            <li><a href="#tab_admin">admin information</a></li>
            <?php if (!$_zp_null_account) { ?>
            <li><a href="#tab_gallery">gallery configuration</a></li>
            <li><a href="#tab_image">image display</a></li>
            <li><a href="#tab_comments">comment configuration</a></li>
            <li><a href="#tab_theme">theme options</a></li>
            <?php } ?>
          </ul>
        </div>
            <div class="panel" id="tab_admin">
                <form action="?page=options&action=saveoptions" method="post">
                <input type="hidden" name="saveadminoptions" value="yes" />
                <?php
                  if (isset($_GET['mismatch'])) {
                    if ($_GET['mismatch'] == 'newuser') {
                      $msg = 'You must supply a password';
                    } else {
                      $msg = 'Your passwords did not match';
                    }
                      echo '<div class="errorbox" id="message">';
                    echo  "<h2>$msg</h2>";
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
                            <?php $x = getOption('adminpass'); if (!empty($x)) { $x = '          '; } ?>
                            <input type="password" size="40" name="adminpass"
                            value="<?php echo $x; ?>" /><br/>
                            <input type="password" size="40" name="adminpass_2"
                            value="<?php echo $x; ?>" />
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Admin full name:</td>
                        <td><input type="text" size="40" name="admin_name" value="<?php echo getOption('admin_name');?>" /></td>
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
                          <strong><?php echo getOption('mysql_database'); ?></strong>: Tables are prefixed by
                          <strong><?php echo getOption('mysql_prefix'); ?></strong>
                          </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" value="save" /></td>
                        <td></td>
                    </tr>
                  </table>
                </form>
            </div> <!-- end of tab_admin div -->
            <div class="panel" id="tab_gallery">
                <form action="?page=options&action=saveoptions" method="post">
                <input type="hidden" name="savegalleryoptions" value="yes" />
                <?php
                  if (isset($_GET['mismatch'])) {
                    echo '<div class="errorbox" id="message">';
                    echo  "<h2>Your " . $_GET['mismatch'] . " passwords did not match</h2>";
                    echo '</div>';
                    echo '<script type="text/javascript">';
                    echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);";
                    echo "</script>\n";
                  }
                  ?>
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
                        <td>Gallery password:<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(repeat) </p></td>
                        <td>
                            <?php $x = getOption('gallery_password'); if (!empty($x)) { $x = '          '; } ?>
                            <input type="password" size="40" name="gallerypass"
                            value="<?php echo $x; ?>" /><br/>
                            <input type="password" size="40" name="gallerypass_2"
                            value="<?php echo $x; ?>" />
                        </td>
                        <td>Master password for the gallery. If this is set, visitors must know this password to view the gallery.</td>
                    </tr>
                       <tr>
                        <td>Gallery password hint:</td>
                        <td><input type="text" size="40" name="gallery_hint" value="<?php echo getOption('gallery_hint');?>" /></td>
                        <td>A reminder hint for the password.</td>
                    </tr>
                    <tr>
                        <td>Search password:<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(repeat) </p></td>
                        <td>
                            <?php $x = getOption('search_password'); if (!empty($x)) { $x = '          '; } ?>
                            <input type="password" size="40" name="searchpass"
                            value="<?php echo $x; ?>" /><br/>
                            <input type="password" size="40" name="searchpass_2"
                            value="<?php echo $x; ?>" />
                        </td>
                        <td>Password for the searching. If this is set, visitors must know this password to view search results.</td>
                    </tr>
                       <tr>
                        <td>Search password hint:</td>
                        <td><input type="text" size="40" name="search_hint" value="<?php echo getOption('search_hint');?>" /></td>
                        <td>A reminder hint for the password.</td>
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
                        <td>Server protocol:</td>
                        <td><input type="text" size="40" name="server_protocol" value="<?php echo getOption('server_protocol');?>" /></td>
                        <td>If you're running a secure server, change this to <em>https</em> (Most people will leave this alone.)</td>
                    </tr>
                    <tr>
                        <td>Time offset (hours):</td>
                        <td><input type="text" size="40" name="time_offset" value="<?php echo getOption('time_offset');?>" /></td>
                        <td>If you're in a different time zone from your server, set the offset in hours.</td>
                    </tr>
                    <tr>
                        <td>Enable mod_rewrite:</td>
                        <td><input type="checkbox" name="mod_rewrite" value="1" <?php echo checked('1', getOption('mod_rewrite')); ?> /></td>
                        <td>If you have Apache <em>mod_rewrite</em>, put a checkmark here, and you'll get nice cruft-free URLs.</td>
                    </tr>
                        <tr>
                        <td>Mod_rewrite Image suffix:</td>
                        <td><input type="text" size="40" name="mod_rewrite_image_suffix" value="<?php echo getOption('mod_rewrite_image_suffix');?>" /></td>
                        <td>If <em>mod_rewrite</em> is checked above, zenphoto will appended this to the end (helps search engines). Examples: <em>.html, .php, /view</em>, etc.</td>
                    </tr>
                    <tr>
                        <td>Charset:</td>
                        <td>
                           <select id="charset" name="charset">
                           <?php foreach ($charsets as $key => $charset) { 
                             $key = strtoupper($key);
                           ?>
                             <option value="<?php echo $key; ?>"<?php if ($key == getOption('charset')) echo ' selected="selected"'; ?>><?php echo $charset; ?></option>
                           <?php } ?>
                           </select>
                        </td>
                        <td>The character encoding to use internally. Leave at <em>Unicode (UTF-8)</em> if you're unsure.</td>
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
                             <input type="checkbox" name="gallery_sortdirection" value="1" <?php echo checked('1', getOption('gallery_sortdirection')); ?> /> Descending
                        </td>
                         <td>Sort order for the albums on the index of the gallery</td>
                    </tr>
                    <tr>
                          <td>Search fields:</td>
                        <td>
                            <?php $fields = getOption('search_fields'); ?>
                            <table class="checkboxes">
                            <tr>
                              <td><input type="checkbox" name="sf_title" value=1 <?php if ($fields & SEARCH_TITLE) echo ' checked'; ?>> Title</td>
                              <td><input type="checkbox" name="sf_desc" value=1 <?php if ($fields & SEARCH_DESC) echo ' checked'; ?>> Description</td>
                              <td><input type="checkbox" name="sf_tags" value=1 <?php if ($fields & SEARCH_TAGS) echo ' checked'; ?>> Tags</td>
                            </tr>
                            <tr>
                              <td><input type="checkbox" name="sf_filename" value=1 <?php if ($fields & SEARCH_FILENAME) echo ' checked'; ?>> File/Folder name</td>
                              <td><input type="checkbox" name="sf_location" value=1 <?php if ($fields & SEARCH_LOCATION) echo ' checked'; ?>> Location</td>
                              <td><input type="checkbox" name="sf_city" value=1 <?php if ($fields & SEARCH_CITY) echo ' checked'; ?>> City</td>
                            </tr>
                            <tr>
                              <td><input type="checkbox" name="sf_state" value=1 <?php if ($fields & SEARCH_STATE) echo ' checked'; ?>> State</td>
                              <td><input type="checkbox" name="sf_country" value=1 <?php if ($fields & SEARCH_COUNTRY) echo ' checked'; ?>> Country</td>
                            </tr>
                            </table>
                        </td>
                        <td>The set of fields on which searches may be performed.</td>
                    </tr>
                    <tr>
                        <td>Google Maps API key:</td>
                        <td><input type="text" size="40" name="gmaps_apikey" value="<?php echo getOption('gmaps_apikey');?>" /></td>
                        <td>If you're going to be using Google Maps, <a href="http://www.google.com/apis/maps/signup.html" target="_blank">get an API key</a> and enter it here.</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" value="save" /></td>
                        <td></td>
                    </tr>
                </table>
                </form>
            </div>  <!-- end of tab-gallery div -->
            <div class="panel" id="tab_image">
                <form action="?page=options&action=saveoptions" method="post">
                <input type="hidden" name="saveimageoptions" value="yes" />
                <?php
                  if (isset($_GET['mismatch'])) {
                    echo '<div class="errorbox" id="message">';
                    echo  "<h2>Your " . $_GET['mismatch'] . " passwords did not match</h2>";
                    echo '</div>';
                    echo '<script type="text/javascript">';
                    echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);";
                    echo "</script>\n";
                  }
                  ?>
                <table class="bordered">
                       <tr>
                          <th colspan="3"><h2>Image Display</h2></th>
                    </tr>
                    <tr>
                        <td>Sort images by:</td>
                        <td>
                           <select id="imagesortselect" name="image_sorttype">
                           <?php foreach ($sortby as $sorttype) { ?>
                              <option value="<?php echo $sorttype; ?>"<?php if ($sorttype == getOption('image_sorttype')) echo ' selected="selected"'; ?>><?php echo $sorttype; ?></option>
                           <?php } ?>
                           </select>
                           <input type="checkbox" name="image_sortdirection" value="1" <?php echo checked('1', getOption('image_sortdirection')); ?> /> Descending
                           </td>
                        <td>Default sort order for images</td>
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
                        <td>If this is set to true, then the longest side of the image will be <em>image size</em>. Otherwise, the <em>width</em> of the image will be <em>image size</em>.</td>
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
                        <td>If set to true the thumbnail will be a centered portion of the image with the given width and height after being resized to <em>thumb size</em> (by shortest side). Otherwise, it will be the full image resized to <em>thumb size</em> (by shortest side).</td>
                    </tr>
                    <tr>
                        <td>Crop thumbnail width:</td>
                        <td><input type="text" size="40" name="thumb_crop_width" value="<?php echo getOption('thumb_crop_width');?>" /></td>
                        <td>The <em>thumb crop width</em> should always be less than or equal to <em>thumb size</em></td>
                    </tr>
                    <tr>
                        <td>Crop thumbnail height:</td>
                        <td><input type="text" size="40" name="thumb_crop_height" value="<?php echo getOption('thumb_crop_height');?>" /></td>
                        <td>The <em>thumb crop height</em> should always be less than or equal to <em>thumb size</em></td>
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
                        <td>Image for image watermark:</td>
                        <td>
                            <?php
                                $v = explode("/", getOption('watermark_image'));
                                $v = str_replace('.png', "", $v[count($v)-1]);
                                echo "<select id=\"watermark_image\" name=\"watermark_image\">\n";
                                generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/images' , '.png');
                                echo "</select>\n";
                            ?>
                          <input type="checkbox" name="perform_watermark" value="1"
                          <?php echo checked('1', getOption('perform_watermark')); ?> />&nbsp;Enabled
                        </td>
                        <td>The watermark image (png-24). (Place the image in the <?php echo ZENFOLDER; ?>/images/ directory.)</td>
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
                           <input type="checkbox" name="perform_video_watermark" value="1"
                           <?php echo checked('1', getOption('perform_video_watermark')); ?> />&nbsp;Enabled
                        </td>
                        <td>The watermark image (png-24). (Place the image in the <?php echo ZENFOLDER; ?>/images/ directory.)</td>
                    </tr>
                    <tr>
                        <td>Full image quality:</td>
                        <td><input type="text" size="40" name="full_image_quality" value="<?php echo getOption('full_image_quality');?>" /></td>
                        <td>Controls compression on full images.</td>
                    </tr>
                    <tr>
                        <td>Full image download:</td>
                        <td><input type="checkbox" name="full_image_download" value="1" <?php echo checked('1', getOption('full_image_download')); ?> /></td>
                        <td>Causes a download dialog to be displayed when clicking on a full-image link.</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" value="save" /></td>
                        <td></td>
                    </tr>
                </table>
                </form>
            </div> <!-- end of tab_image div -->
            <div class="panel" id="tab_comments">
                <form action="?page=options&action=saveoptions" method="post">
                <input type="hidden" name="savecommentoptions" value="yes" />
                <?php
                  if (isset($_GET['tag_parse_error'])) {
                    echo '<div class="errorbox" id="message">';
                  echo  "<h2>Your Allowed tags change did not parse successfully.</h2>";
                  echo '</div>';
                  echo '<script type="text/javascript">';
                  echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);";
                  echo "</script>\n";
                  }
                  ?>
                  <table class="bordered">
                    <tr>
                           <th colspan="3"><h2>Comments options</h2></th>
                      </tr>
                    <tr>
                        <td>Enable comment notification:</td>
                        <td><input type="checkbox" name="email_new_comments" value="1" <?php echo checked('1', getOption('email_new_comments')); ?> /></td>
                        <td>Email the Admin when new comments are posted</td>
                    </tr>
                    <tr>
                        <td>Allowed tags:</td>
                        <td>
                         <textarea name="allowed_tags" cols="40" rows="10"><?php echo getOption('allowed_tags'); ?></textarea>
                        </td>
                           <td>
                          Tags and attributes allowed in comments<br/>
                          Follow the form <em>tag</em> => (<em>attribute</em> => (<em>attribute</em> => (), <em>attribute</em> => ()...)))
                        </td>
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
                      <td>Require fields:</td>
                      <td>
                      <input type="checkbox" name="comment_name_required" value=1 <?php checked('1', getOption('comment_name_required')); ?>>&nbsp;Name
                      <input type="checkbox" name="comment_email_required" value=1 <?php checked('1', getOption('comment_email_required')); ?>>&nbsp;Email
                      <input type="checkbox" name="comment_web_required" value=1 <?php checked('1', getOption('comment_web_required')); ?>>&nbsp;Website
                      </td>
                      <td>Checked fields must be valid in a comment posting.</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" value="save" /></td>
                        <td></td>
                    </tr>
                  </table>
                </form>
            </div> <!-- end of tab_comments div -->
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
                      echo "<tr><th colspan='3'><h2>Theme Options for <em>".$gallery->getCurrentTheme()."</em></h2></th></tr>\n";
                      customOptions($optionHandler);
                      echo "\n<tr>\n";
                      echo "<td></td>\n";
                      echo  '<td><input type="submit" value="save" /></td>' . "\n";
                      echo "<td></td>\n";
                      echo "</tr>\n";
                      echo "</table>\n";
                    }
                  }
                ?>
            </form>
            </div>  <!-- end of tab_themne div -->
        </div> <!-- container -->
<?php /*** THEMES (Theme Switcher) *******************************************************/
      /************************************************************************************/ ?>

<?php } else if ($page == "themes") { ?>

      <h1>General Options</h1>

      <h2>Themes (current theme is <em><?php echo $current_theme = $gallery->getCurrentTheme();?></em>)</h2>
      <p>Themes allow you to visually change the entire look and feel of your gallery. All themes are located in your <code>zenphoto/themes</code> folder, and you can download more themes at the <a href="http://www.zenphoto.org/support/">zenphoto forum</a> and <a href="http://www.zenphoto.org/zp/theme/">zenphoto themes page</a>.</p>
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
      <?php 
      if (isset($_GET['check_for_update'])) {
        $v = checkForUpdate();
        if (!empty($v)) {
          if ($v == 'X') {
            echo "\n<div style=\"font-size:150%;color:#ff0000;text-align:right;\">Could not connect to <a href=\"http://www.zenphoto.org\">zenphoto.org</a>.</div>\n"; 
          } else {
            echo "\n<div style=\"font-size:150%;text-align:right;\"><a href=\"http://www.zenphoto.org\">zenphoto version $v is available.</a></div>\n"; 
          }
        } else {
          echo "\n<div style=\"font-size:150%;color:#33cc33;text-align:right;\">You are running the latest zenphoto version.</div>\n"; 
        }
      } else {
        echo "\n<div style=\"text-align:right;color:#0000ff;\"><a href=\"?check_for_update\">Check for zenphoto update.</a></div>\n"; 
      }
      ?>
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
        $comments = query_full_array("SELECT `id`, `name`, `website`, `type`, `imageid`,"
        . " (date + 0) AS date, comment, email, inmoderation FROM ".prefix('comments')
        . " ORDER BY id DESC " . ($viewall ? "" : "LIMIT 20") );
        foreach ($comments as $comment) {
          $id = $comment['id'];
          $author = $comment['name'];
          $email = $comment['email'];
          if ($comment['type']=='images') {
            $imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
                     " WHERE `id`=" . $comment['imageid']);
            if ($imagedata) {
              $imgdata = $imagedata[0];
              $image = $imgdata['filename'];
              if ($imgdata['title'] == "") $title = $image; else $title = $imgdata['title'];
              $title = '/ ' . $title;
              $albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
                       " WHERE `id`=" . $imgdata['albumid']);
              if ($albmdata) {
                $albumdata = $albmdata[0];
                $album = $albumdata['folder'];
                $albumtitle = $albumdata['albumtitle'];
                if (empty($albumtitle)) $albumtitle = $album;
              } else {
                $title = 'database error';
              }
            } else {
              $title = 'database error';
            }
          } else {
            $image = '';
            $title = '';
            $albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
                     " WHERE `id`=" . $comment['imageid']);
            if ($albmdata) {
              $albumdata = $albmdata[0];
              $album = $albumdata['folder'];
              $albumtitle = $albumdata['albumtitle'];
              if (empty($albumtitle)) $albumtitle = $album;
            } else {
              $title = 'database error';
            }
          }
          $website = $comment['website'];
          $comment = truncate_string($comment['comment'], 123);
          echo "<li><div class=\"commentmeta\">$author commented on <a href=\""
          . (getOption("mod_rewrite") ? "../$album/$image" : "../index.php?album=".urlencode($album)."&image=".urlencode($image))
          . "\">$albumtitle $title</a>:</div><div class=\"commentbody\">$comment</div></li>";
        }
        ?>
        </ul>
      </div>


        <div class="box" id="overview-stats">
          <h2 class="boxtitle">Gallery Maintenance</h2>
          <p>Your database is <strong><?php echo getOption('mysql_database'); ?>
          </strong>: Tables are prefixed by <strong>'<?php echo getOption('mysql_prefix'); ?>'</strong></p>
          <form name="prune_gallery" action="admin.php?prune=true">
            <input type="hidden" name="prune" value="true">
            <button Type="submit">Refresh the Database</button>
            <br/>This cleans the database, removes any orphan entries for comments, images, and albums.
          </form>
          <form name="cache_images" action="cache-images.php">
            <button type="submit"><img src="images/cache.png" style="border: 0px;" /> Pre-Cache Images</button>
            <input type="checkbox" name="clear" checked="true" /> Clear
            Finds newly uploaded images that have not been cached and creates the cached version. It also refreshes the numbers above. If you have a large number of images in your gallery you might consider using the <em>pre-cache image</em> link for each album to avoid swamping your browser.
          </form>
          <form name= "refresh_metadata" action="refresh-metadata.php">
            <button type="submit"><img src="images/warn.png" style="border: 0px;" /> Refresh Metadata</button>
            <br/>Forces a refresh of the EXIF and IPTC data for all images.
          </form>
          <form name="reset_hitcounters" action="admin.php?action=reset_hitcounters=true">
            <input type="hidden" name="action" value="reset_hitcounters">
            <p/><button type="submit"><img src="images/reset.png" style="border: 0px;" /> Reset hitcounters</button>
            <br/>Sets all album and image hitcounters to zero.
          </form>
        </div>


      <div class="box" id="overview-suggest">
        <h2 class="boxtitle">Gallery Stats</h2>
          <p><strong><?php echo $gallery->getNumImages(); ?></strong> images.</p>
          <p><strong><?php echo $gallery->getNumAlbums(true); ?></strong> albums.</p>
          <p><strong><?php echo $t = $gallery->getNumComments(true); ?></strong> comments.
          <?php  
             $c = $gallery->getNumComments(false);
             if ($c != $t) { 
               $m = $t - $c;
               if ($m > 1) $verb = 'are'; else $verb = 'is';
               echo " ($m $verb in moderation.)";
             }
          ?></p>
      </div>
      <p style="clear: both; "></p>
    <?php } ?>
    </div> <!-- content -->
<?php
  printAdminFooter();
  if (issetPage('edit')) {
    zenSortablesFooter();
  }
} /* No admin-only content allowed after this bracket! */ ?>
</div> <!-- main -->

</body>
</html>
