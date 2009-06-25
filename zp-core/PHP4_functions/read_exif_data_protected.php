<?php
/**
 * read_exif_data_protected
 * @package functions
 * 
 */
/**
 * Provides an [not] error protected read of image EXIF/IPTC data for PHP 4
 *
 * @param string $path image path
 * @return array
 */
function read_exif_data_protected($path) {
	return read_exif_data_raw($path, false);
}


?>