<?php
/**
 * read_exif_data_protected
 * @package functions
 * 
 */
/**
 * Provides an error protected read of image EXIF/IPTC data
 *
 * @param string $path image path
 * @return array
 * 
 */
function read_exif_data_protected($path) {
	try {
		$rslt = read_exif_data_raw($path, false);
	} catch (Exception $e) {
		$rslt = array();
	}
	return $rslt;
}


?>