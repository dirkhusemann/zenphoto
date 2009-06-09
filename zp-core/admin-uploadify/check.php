<?php
if(!function_exists("json_encode")) {
	// load the drop-in replacement library
	require_once('../lib-json.php');
}
// Uploadify v1.6.2
// Copyright (C) 2009 by Ronnie Garcia
// Co-developed by Travis Nickels
$fileArray = array();
foreach ($_POST as $key => $value) {
	if ($key != 'folder') {
		if (file_exists($_POST['folder'] . '/' . $value)) {
			$fileArray[$key] = $value;
		}
	}
}
echo json_encode($fileArray);
?>