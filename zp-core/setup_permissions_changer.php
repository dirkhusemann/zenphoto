<?php
/**
 * Used to set the mod_rewrite option. 
 * This script is accessed via a /page/setup_set-mod_rewrite?z.
 * It will not be found unless mod_rewrite is working.
 * 
 * @package setup
 * 
 */
require_once(dirname(__FILE__).'/functions.php');
if (!isset($_POST['folder'])) exit();
$folder = sanitize($_POST['folder'],3);
if (substr($folder,-1,1) == '/') $folder = substr($folder,0,-1);
if (isset($_POST['strict'])) {
	$chmod = 0755;
} else {
	$chmod = 0777;
}
$f = fopen(dirname(dirname(__FILE__)).'/'.DATA_FOLDER . '/setup_log.txt', 'a');
if (!folderPermissions($folder)) {
	fwrite($f, sprintf(gettext('Notice: failed setting permissions for %s.'), basename($folder)) . "\n");
}
fclose($f);

function folderPermissions($folder) {
	global $chmod, $f;
	$curdir = getcwd();
	chdir($folder);
	$files = safe_glob('*.*');
	chdir($curdir);
	foreach ($files as $file) {
		$path = $folder.'/'.$file;
		if (is_dir($path)) {
				if($file != '.' && $file != '..') {
				@chmod($path,$chmod);
				if((fileperms($path)&0777)==$chmod) {
					if (!folderPermissions($path)) {
						return false;
					}
				} else {
					return false;
				}
			}
		} else {
			@chmod($path,0666&$chmod);
			if ((fileperms($path)&0777)!=(0666&$chmod)) {
				return false;
			}
		}
	}
	return true;
}
	
?>