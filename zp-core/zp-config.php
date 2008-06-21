<?php /* PUT NOTHING BEFORE THIS LINE, not even a line break! */
$conf = array();

/** Do not edit above this line. **/
/**********************************/

///////////   zenPHOTO Configuration Variables   //////////////////////////////
//  After you're done editing this file, load  
//  http://www.yoursite.com/zenphotodir/zp-core/setup.php
//  to run the setup (of course, replacing the paths where needed).

// NOTE: web_path and server_path are no longer needed! If you're having problems
// with the auto-detected paths, you can override them at the bottom of this file.
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Database Information (the most important part!)
////////////////////////////////////////////////////////////////////////////////

$conf['mysql_user'] = "root";
$conf['mysql_pass'] = "root";
$conf['mysql_host'] = "localhost";
$conf['mysql_database'] = "zptrunk";

// If you're sharing the database with other tables, use a prefix to be safe.
$conf['mysql_prefix'] = "zp_";

////////////////////////////////////////////////////////////////////////////////
// zp-config.php required options
////////////////////////////////////////////////////////////////////////////////

// location of album folder. 
// change 'album_folder' to rename the album folder inside the zenphotos installation
// change 'external_album_folder' to locate your album folder outside the zenphoto folders
$conf['album_folder'] = '/albums/';
$conf['external_album_folder'] = NULL;

// Change this to "https" if you use an HTTPS server (a "https://..." url)
// Otherwise you should leave it at "http"
$conf['server_protocol'] = "http";


////////////////////////////////////////////////////////////////////////////////
// Path Overrides
////////////////////////////////////////////////////////////////////////////////
// Uncomment the following two lines ONLY IF YOU'RE HAVING PROBLEMS,
// like "file not found" or "not readable" errors.
// These allow you to override Zenphoto's detection of the correct paths
// on your server, which might work better on some setups.
////////////////////////////////////////////////////////////////////////////////

// define('WEBPATH', '/zenphoto');
// define('SERVERPATH', '/full/server/path/to/zenphoto');



/** Do not edit below this line. **/
/**********************************/

$_zp_conf_vars = $conf;
unset($conf);

?>
