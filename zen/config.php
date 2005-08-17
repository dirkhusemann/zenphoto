<?php

/* zenphoto configuration variables */


$conf = array(); // Leave this alone. ;-)
// Show debugging <!--comments--> in the code? (There are none)
define("DEBUG", true);

//// The following three only matter if your template uses them.
//   The default template does.
// What you want to call your photo gallery.
$conf['gallery_title'] = "trisweb.com photos";
// For a link back to your main site
$conf['main_site_name'] = "trisweb.com";
$conf['main_site_url']  = "http://www.trisweb.com/";

// Tags allowed in comments
$conf['allowed_tags'] = "<a><i><em><b><strong><blockquote><abbr><acronym><ul><ol><li>";

// The path to zenphoto on the server - e.g. /user/public_html/zenphoto. No trailing slash/
$conf['serverpath']  = "D:/My Projects/zenphoto";

// The URL of zenphoto from the domain root. - e.g. 'http://www.yoursite.com/zenphoto'
// (or '/zenphoto' in that case). No trailing slash/
$conf['webpath'] = '/zp';

// If you have Apache mod_rewrite, put true here, and you'll get nice cruft-free URLs.
$conf['mod_rewrite'] = true;

// Admin interface username (also used for setup).
$conf['adminuser'] = "admin";
// Obviously insecure, don't put anything here you don't want anyone to see...
$conf['adminpass'] = "1234";

// Database login information.
$conf['mysql_user'] = "user";
$conf['mysql_pass'] = "67wp184c";
$conf['mysql_host'] = "localhost";
$conf['mysql_database'] = "trisweb_zenphoto";
// If you're sharing the database with other tables, use a prefix to be safe.
$conf['mysql_prefix'] = "";

// Image Processing Configuration

// Maximum size in Megabytes of the cache folder (where processed images are stored).
// After this is hit, a cleanup function will run and delete some least recently used resized images.
$conf['maxcache'] = 100;

// JPEG Compression quality for all images and thumbnails (respectively):
$conf['image_quality'] = 85;
$conf['thumb_quality'] = 75;

// Default image display width:
$conf['image_size'] = 595;
// If this is set to true, then the longest side of the image will be $image_size.
// Otherwise, the *width* of the image will be $image_size.
$conf['image_use_longest_side'] = true;
// Options $image_constant_width, $image_constant_height?

// Default thumbnail size and scale:
// If $thumb_crop is set to true, then the thumbnail will be a centered portion of the image
// with the given width and height after being resized to $thumb_size. 
// Otherwise, it will be the full image resized to $thumb_size (on the shortest side).
$conf['thumb_crop']        = true;
$conf['thumb_crop_width']  = 85;
$conf['thumb_crop_height'] = 85;
$conf['thumb_size']        = 100;

// Paging options.
// Control of their display is done by the templates, so check there for row and column settings.
$conf['albums_per_page'] = 5;
$conf['images_per_page'] = 14;



/** Do not edit below this line. **/
/**********************************/

$_zp_conf_vars = $conf;


// TODO: get these from the environment paths at setup (or in general)?
/* Use:
$_SERVER["SCRIPT_NAME"]        -> /zp/index.php
$_SERVER["SCRIPT_FILENAME"]    -> /home/trisweb/public_html/zp/index.php
*/
define('SERVERPATH', $_zp_conf_vars['serverpath']);
define('WEBPATH', $_zp_conf_vars['webpath']);
define('SERVERCACHE', $_zp_conf_vars['serverpath']."/cache");
define('WEBCACHE', $_zp_conf_vars['webpath']."/cache");
define('VERSION', "0.2.2");

?>
