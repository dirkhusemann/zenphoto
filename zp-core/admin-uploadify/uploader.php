<?php
define('OFFSET_PATH', 3);
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
if (!zp_loggedin()) {
	$session = getOption('admin_session');
	if (!empty($session)) {
		$session_a = unserialize($session);
		$expiry = $session_a['timestamp']+7200;
		$now = time();
		if ($session_a['admin_page']=='upload' && $expiry>$now) {
			$_zp_loggedin = checkAuthorization($session_a['credentials']);
		}
	}
}
if (!empty($_FILES)) {
	$tempFile = sanitize($_FILES['Filedata']['tmp_name'],3);
	$folder = sanitize($_GET['folder'],3);
	if (substr($folder,0,1) == '/') {
		$folder = substr($folder,1);
	}
	if (substr($folder,-1) == '/') {
		$folder = substr($folder,0,-1);
	}
	$targetPath = getAlbumFolder().internalToFilesystem($folder);
	$name = sanitize($_FILES['Filedata']['name'],3);
	if (!empty($folder) && isMyAlbum($folder, UPLOAD_RIGHTS)) {
		if (is_valid_image($name) || is_valid_other_type($name)) {
			$soename = seoFriendlyURL($name);
			$targetFile =  $targetPath.'/'.internalToFilesystem($soename);
			@mkdir($targetPath, 0777 & CHMOD_VALUE, true);
			move_uploaded_file($tempFile,$targetFile);
			@chmod($targetFile, 0666 & CHMOD_VALUE);
			$album = new Album(New Gallery(), $folder);
			$image = newImage($album, $soename);
			if ($name != $soename) {
				$image->setTitle($name);
				$image->save();
			}
		} else if (is_zip($name)) {
			unzip($tmp_name, $targetPath);
		}
	}
}
	
echo '1'; // MAC os kludge

?>