<?php
require_once(dirname(dirname(__FILE__)).'/functions-basic.php');
if(!function_exists("json_encode")) {
	// load the drop-in replacement library
	require_once('../lib-json.php');
}
$fileArray = array();
foreach ($_POST as $key => $value) {
	if ($key != 'folder') {
		$folder = str_replace('/'.ZENFOLDER.'/','',sanitize($_POST['folder'])); // hack to remove the 
		$targetPath = getAlbumFolder().internalToFilesystem($folder.'/'.$value);
		if (file_exists($targetPath)) {
			$fileArray[$key] = $value;
		}
	}
}
echo json_encode($fileArray);
?>