<?php
/* zenphoto configuration variables */
// Show debugging <!--comments--> in the code.
define("DEBUG", true);

//// The following three only matter if your template uses them.
//   The default template does.
// What you want to call your photo gallery.
$gallery_title = "trisweb.com photos";
// For a link back to your main site
$main_site_name = "trisweb.com";
$main_site_url  = "http://www.trisweb.com/";

// The path to zenphoto on the server - e.g. /user/public_html/zenphoto. No trailing slash/
$serverpath = "D:/My Projects/zenphoto";

// The URL of zenphoto from the domain root. - e.g. 'http://www.yoursite.com/zenphoto'
// (or '/zenphoto' in that case). No trailing slash/
$webpath = '/zp'; 

// If you have Apache mod_rewrite, put true here, and you'll get nice cruft-free URLs.
$mod_rewrite = true;

$adminuser = "admin"; // Admin interface username.
$adminpass = "1234"; // Obviously insecure, don't put anything here you don't want anyone to see...

$mysql_user = "user";
$mysql_pass = "67wp184c";
$mysql_host = "localhost";
$mysql_database = "trisweb_zenphoto";
$mysql_prefix = ""; // If you're sharing the database with other tables, use a prefix to be safe.


// Image Processing Configuration

// Maximum size in Megabytes of the cache folder (where processed images are stored).
// After this is hit, a cleanup function will run and delete some least recently used resized images.
$maxcache = 100;

// JPEG Compression quality for all images and thumbnails (respectively):
$image_quality = 85;
$thumb_quality = 75;

// Default image display width:
$image_size = 595;
// If this is set to true, then the longest side of the image will be $image_size.
// Otherwise, the *width* of the image will be $image_size.
$image_use_longest_side = true;

// Options $image_constant_width, $image_constant_height?

// Default thumbnail size and scale:
// If $thumb_crop is set to true, then the thumbnail will be a centered portion of the image
// with the given width and height after being resized to $thumb_size. 
// Otherwise, it will be the full image resized to $thumb_size (on the shortest side).
$thumb_crop        = true;
$thumb_crop_width  = 85;
$thumb_crop_height = 85;
$thumb_size        = 100;

// Paging options.
// Control of their display is done by the templates, so check there for row and column settings.
$albums_per_page = 5;
$images_per_page = 14;

// Class names to assist you in styling the HTML produced by the "html*" Template functions.
// You can choose not to use HTML output for your template; just use the "get*" functions instead.
// You can use these names in your CSS, or change them however you see fit.
$default_image_class = "zpimage";
$default_thumb_class = "zpthumb";
$default_link_class  = "zplink";
$default_album_title_class = "zpalbumtitle";
$default_album_desc_class  = "zpalbumdesc";
$default_image_title_class = "zpimagetitle";
$default_image_desc_class  = "zpimagedesc";
// // More?

/** Do not edit below this line. **/

// TODO: get these from the environment paths at setup (or in general)?
define('SERVERPATH', $serverpath);
define('WEBPATH', $webpath);
define('SERVERCACHE', $serverpath."/cache");
define('WEBCACHE', $webpath."/cache");
define('VERSION', "0.2.2");

?>
