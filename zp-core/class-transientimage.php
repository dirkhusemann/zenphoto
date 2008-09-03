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
		$this->album = new Album($gallery, '');
		$this->localpath = $image;
		
		$folder = basename(dirname($image));
		if ($folder == 'images') {
			$folder = basename(dirname(dirname($image)));
		}
		$filename = $folder.'_'.basename($image);
		$this->filename = $filename;
		$this->filemtime = filemtime($this->localpath);
		$this->name = $filename;
		$this->comments = null;
		if (is_valid_video($filename)) {
			$this->video = true;
		}
		
		if (!copy($image,  getAlbumFolder() . $filename)) {
			return NULL;
		}
		@chmod($filename, CHMOD_VALUE & 0666);
		
		parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', false, true);
		
	}
}
?>