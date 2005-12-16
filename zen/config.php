<?php /* PUT NOTHING BEFORE THIS LINE, not even a line break! */
$conf = array();
define("DEBUG", true);
/** Do not edit above this line. **/
/**********************************/

///////////   zenPHOTO Configuration Variables   //////////////////////////////
//  After you're done editing this file, load  
//  http://www.yoursite.com/zenphotodir/zen/setup.php
//  to run the setup (of course, replacing the paths where needed).

// NOTE: web_path and server_path are no longer needed! :-)


// Database login information (the most important part).
$conf['mysql_user'] = "lucky";
$conf['mysql_pass'] = "dbpass";
$conf['mysql_host'] = "athena"; // Probably won't need to change this.
$conf['mysql_database'] = "zenphoto";
// If you're sharing the database with other tables, use a prefix to be safe.
$conf['mysql_prefix'] = "zp_";

// What you want to call your photo gallery.
$conf['gallery_title'] = "Development Gallery";

// If you're in a different time zone from your server, set the offset in hours:
$conf['time_offset'] = 0;

// Tags allowed in comments
$conf['allowed_tags'] = "<a><i><em><b><strong><blockquote><abbr><acronym><ul><ol><li>";

// If you have Apache mod_rewrite, put true here, and you'll get nice cruft-free URLs.
// MAKE SURE TO EDIT THE .htaccess FILE with your path information.
$conf['mod_rewrite'] = false;

// Admin interface username (also required for running setup).
$conf['adminuser'] = "admin";
// Obviously insecure, don't put anything here you don't want anyone to see...
// but make sure you change it to something other than 1234...
$conf['adminpass'] = "password";



// Image Processing Configuration

// Maximum size in Megabytes of the cache folder (where processed images are stored).
// After this is hit, a cleanup function will run and delete some least recently used resized images.
$conf['maxcache'] = 100;

// JPEG Compression quality for all images and thumbnails (respectively):
$conf['image_quality'] = 85;
$conf['thumb_quality'] = 75;

// Default image display width (themes will be able to control this 
// to an extent in the future, but for now you may have to adjust it to fit your theme).
$conf['image_size'] = 595;

// If this is set to true, then the longest side of the image will be $image_size.
// Otherwise, the *width* of the image will be $image_size.
$conf['image_use_longest_side'] = true;

// Default thumbnail size and scale:
// If $thumb_crop is set to true, then the thumbnail will be a centered portion of the image
// with the given width and height after being resized to $thumb_size (by shortest side).
// Otherwise, it will be the full image resized to $thumb_size (by shortest side).
// NOTE: thumb_crop_width and height should always be less than or equal to thumb_size
$conf['thumb_crop']        = true;
$conf['thumb_crop_width']  = 85;
$conf['thumb_crop_height'] = 85;
$conf['thumb_size']        = 100;

// Paging options.
// Control of their display is done in the theme, so you might need to change these
// after switching themes to make it look better.
$conf['albums_per_page'] = 5;
$conf['images_per_page'] = 15;



/** Do not edit below this line. **/
/**********************************/

$_zp_conf_vars = $conf;

if (basename($_SERVER['SCRIPT_FILENAME']) == "i.php" || 
    basename($_SERVER['SCRIPT_FILENAME']) == "admin.php" ||
    basename($_SERVER['SCRIPT_FILENAME']) == "albumsort.php") {
  define('SERVERPATH', dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
  define('WEBPATH', dirname(dirname($_SERVER['SCRIPT_NAME'])));
} else {
  define('SERVERPATH', dirname($_SERVER['SCRIPT_FILENAME']));
  define('WEBPATH', dirname($_SERVER['SCRIPT_NAME']));
}
define('SERVERCACHE', SERVERPATH . "/cache");
define('WEBCACHE', WEBPATH . "/cache");

?>
