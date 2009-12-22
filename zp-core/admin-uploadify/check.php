<?php
require_once(dirname(dirname(__FILE__)).'/functions.php');
if(!function_exists("json_encode")) {
	// load the drop-in replacement library
	require_once('../lib-json.php');
}
$fileArray = array();
foreach ($_POST as $key => $value) {
	if ($key != 'folder') {
		$folder = str_replace('/'.ZENFOLDER.'/','',sanitize($_POST['folder'])); // hack to remove the 
		$name = seoFriendly($value);
		if (strpos($name,'.')===0) $name = md5($value).$name; // soe stripped out all the name.
		$targetPath = getAlbumFolder().internalToFilesystem($folder.'/'.$name);
		if (file_exists($targetPath)) {
			$fileArray[$key] = $name;
		}
	}
}
echo json_encode($fileArray);
?>