<?php
/**
 * class transientimage
 * @package classes
 */
class Transientimage extends image {
	
	//TODO: someday we should make it so that the image does not have to be copied to the albums folder.
	
	/**
	 * creates a transient image (that is, one that is not stored in the database)
	 *
	 * @param object $gallery
	 * @param string $image the full path to the image
	 * @return transientimage
	 */
	function Transientimage(&$gallery, $image) {
		$album = new Album($gallery, '');
		
		$folder = basename(dirname($image));
		if ($folder == 'images') {
			$folder = basename(dirname(dirname($image)));
		}
		$filename = $folder.'_'.basename($image);
		if (!copy($image,  getAlbumFolder() . $filename)) {
			return NULL;
		}
		@chmod($filename, CHMOD_VALUE & 0666);
		parent::Image($album, $filename);
		
	}
}
?>