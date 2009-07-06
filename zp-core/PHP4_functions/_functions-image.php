<?php
/**
 * cacheImage_protected
 * @package functions
 * 
 */
/**
 * Provides an [not] error protected cacheImage for PHP 4
 *
  */
function cacheImage_protected($newfilename, $imgfile, $args, $allow_watermark=false, $force_cache=false, $theme, $album) {
	cacheImage($newfilename, $imgfile, $args, $allow_watermark, $force_cache, $theme, $album);
	return true;
}

?>