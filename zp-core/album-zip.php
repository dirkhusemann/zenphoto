<?php
if (!defined('OFFSET_PATH')) define('OFFSET_PATH', 2);
require_once(dirname(__FILE__).'/functions.php');
if(isset($_GET['album']) && is_dir(realpath(getAlbumFolder() . internalToFilesystem($_GET['album'])))){
	createAlbumZip(sanitize_path($_GET['album']));
}

/**
 * Adds a subalbum to the zipfile being created
 *
 * @param string $base the directory of the base album
 * @param string $offset the from $base to the subalbum
 * @param string $subalbum the subalbum file name
 * @param object $zip zipfile object to store the albums
 */
function zipAddSubalbum($base, $offset, $subalbum, $zip) {
	global $_zp_zip_list;
	$leadin = str_replace(getAlbumFolder(), '', $base);
	if (checkAlbumPassword($leadin.$offset.$subalbum, $hint)) {
		$new_offset = $offset.$subalbum.'/';
		$rp = $base.$new_offset;
		$cwd = getcwd();
		chdir($rp);
		if ($dh = opendir($rp)) {
			$_zp_zip_list[] = "./".$new_offset.'*.*';
			while (($file = readdir($dh)) !== false) {
				if($file != '.' && $file != '..'){
					if (is_dir($rp.$file)) {
						zipAddSubalbum($base, $new_offset, $file, $zip);
					}
				}
			}
			closedir($dh);
		}
		chdir($cwd);
	}
}

/**
 * Creates a zip file of the album
 *
 * @param string $album album folder
 */
function createAlbumZip($album){
	global $_zp_zip_list;
	if (!checkAlbumPassword($album, $hint)) {
		pageError(403, gettext("Forbidden"));
		exit();
	}
	$album = internalToFilesystem($album);
	$rp = realpath(getAlbumFolder() . $album) . '/';
	$p = $album . '/';
	include_once('archive.php');
	$dest = realpath(getAlbumFolder()) . '/' . $album . ".zip";
	$persist = getOption('persistent_archive');
	if (!$persist  || !file_exists($dest)) {
		if (file_exists($dest)) unlink($dest);
		$z = new zip_file($dest);
		$z->set_options(array('basedir' => $rp, 'inmemory' => 0, 'recurse' => 0, 'storepaths' => 1));
		if ($dh = opendir($rp)) {
			$_zp_zip_list[] = '*.*';

			while (($subalbum = readdir($dh)) !== false) {
				if($subalbum != '.' && $subalbum != '..'){
					if (is_dir($rp.$subalbum)) {
						$offset = dirname($album);
						if ($offset=='/' || $offset=='.') $offset = '';
						zipAddSubalbum($rp, $offset, $subalbum, $z);
					}
				}
			}
			closedir($dh);
		}
		$z->add_files($_zp_zip_list);
		$z->create_archive();
	}
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . urlencode($album) . '.zip"');
	header("Content-Length: " . filesize($dest));
	printLargeFileContents($dest);
	if (!$persist) { unlink($dest); }
}
?>