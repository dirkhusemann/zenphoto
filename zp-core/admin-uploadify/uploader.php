<?php
define('OFFSET_PATH', 3);
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
if (!zp_loggedin()) {
	if (isset($_POST['auth'])) {
		$auth = $_POST['auth'];
		$admins = getAdministrators();
		foreach ($admins as $admin) {
			if (md5(serialize($admin)) == $auth && $admin['rights'] & UPLOAD_RIGHTS) {
				$_zp_loggedin = checkAuthorization($admin['pass']);
				break;
			}
		}
	}
}
if (!empty($_FILES)) {
	$name = basename(sanitize($_FILES['Filedata']['name'],3));
	if (isset($_FILES['Filedata']['error']) && $_FILES['Filedata']['error']) {
		debugLogArray('Uploadify error:', $_FILES);
		trigger_error(sprintf(gettext('Uploadify error on %1$s. Review your debug log.'),$name));
	} else {
		$tempFile = sanitize($_FILES['Filedata']['tmp_name'],3);
		$folder = sanitize($_POST['folder'],3);
		if (substr($folder,0,1) == '/') {
			$folder = substr($folder,1);
		}
		if (substr($folder,-1) == '/') {
			$folder = substr($folder,0,-1);
		}
		$targetPath = getAlbumFolder().internalToFilesystem($folder);
		if (!empty($folder) && isMyAlbum($folder, UPLOAD_RIGHTS)) {
			if (!is_dir($targetPath)) {
				mkdir_recursive($targetPath, CHMOD_VALUE);
			}
			@chmod($targetPath, CHMOD_VALUE);
			if (is_valid_image($name) || is_valid_other_type($name)) {
				$soename = seoFriendlyURL($name);
				$targetFile =  $targetPath.'/'.internalToFilesystem($soename);
				$rslt = move_uploaded_file($tempFile,$targetFile);
				@chmod($targetFile, 0666 & CHMOD_VALUE);
				$album = new Album(New Gallery(), $folder);
				$image = newImage($album, $name);
				if ($name != $soename) {
					$image->setTitle(substr($name, 0, strrpos($name, '.')));
					$image->save();
				}
			} else if (is_zip($name)) {
				unzip($tempFile, $targetPath);
			}
		}
	}
}

echo '1';

?>