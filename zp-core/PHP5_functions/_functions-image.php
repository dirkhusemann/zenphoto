<?php
/**
 * cacheImage_protected
 * @package functions
 * 
 */
/**
 * Provides an error protected cacheImage for PHP 5
 *
 */
function cacheImage_protected($newfilename, $imgfile, $args, $allow_watermark=false, $force_cache=false, $theme) {
	try {
		cacheImage($newfilename, $imgfile, $args, $allow_watermark, $force_cache, $theme);
		return true;
	} catch (Exception $e) {
		return false;
	}
}
?>