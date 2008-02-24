<?php
require_once(SERVERPATH . "/" . ZENFOLDER . '/functions-image.php');
$_super_gallery_list= null;
$_current_subgallery = null;
$_current_gallery_image = null;
$save_conf = null;

function getGalleries() {
	global $_super_gallery_list;
	$dir = SERVERPATH;
	$reject = array('.'=>1, '..'=>2, 'cache'=>3, 'albums'=>4, 'zp-core'=>5, 'themes'=>6);
	$_super_gallery_list = array();
	if(is_dir($dir)){
		if($dh=opendir($dir)){
			while(($file = readdir($dh)) !== false){
				if (is_dir($dir.'/'.$file) & !(isset($reject[$file]))) {
					if (file_exists($dir.'/'.$file.'/zp-core/')) {
						$_super_gallery_list[]=$file;
					}
				}
			}
		}
	}
}

function setGalleyContext($gallery) {
	global $_zp_conf_vars, $db_connection, $save_conf;
	$save_conf = $_zp_conf_vars;
	require(SERVERPATH . '/' . $gallery .'/' . ZENFOLDER . '/zp-config.php');
	if (!is_null($super_connection)) {
		mysql_close($db_connection);
		$db_connection = null;
	}
	$db_connection = db_connect();
}

function restoreContext() {
	global $_zp_conf_vars, $db_connection, $save_conf;
	$_zp_conf_vars = $save_conf;
}

function next_gallery() {
	global $_super_gallery_list, $_current_subgallery;
	if (is_null($_super_gallery_list)) {
		getGalleries();
	}
	if (count($_super_gallery_list) == 0) {
		$_super_gallery_list = null;
		return false;
	}
	$_current_subgallery = array_shift($_super_gallery_list);
	return true;
}

function getSubgalleryTitle() {
	global $_current_subgallery;
	setGalleyContext($_current_subgallery);
	$sql = "SELECT `value` FROM ".prefix('options')." WHERE `name`='gallery_title'";
	$result = query_single_row($sql);
	$title = $result['value'];
	restoreContext();
	return $title;
}

function getSubgalleryURL() {
	global $_current_subgallery;
	return WEBPATH . '/' . $_current_subgallery . '/';
}

function getSubgalleryDesc() {
	global $_current_subgallery;
	setGalleyContext($_current_subgallery);
	$sql = "SELECT `value` FROM ".prefix('options')." WHERE `name`='Gallery_description'";
	$result = query_single_row($sql);
	$desc = $result['value'];
	restoreContext();
	return $desc;
}

function printSubgalleryTitle() {
	echo getSubgalleryTitle();
}

function printSubgalleryDesc() {
	echo getSubgalleryDesc();
}

function getGalleryImage() {
	global $_current_subgallery, $_current_gallery_image;
	$image = array();
	setGalleyContext($_current_subgallery);
	if (zp_loggedin()) {
		$albumWhere = '';
		$imageWhere = '';
	} else {
		$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
		foreach($albumscheck as $albumcheck) {
			if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
				$albumpasswordcheck= " AND ".prefix('albums').".id != ".$albumcheck['id'];
				$passwordcheck = $passwordcheck.$albumpasswordcheck;
			}
		}
		$albumWhere = " AND ".prefix('albums') . ".show=1".$passwordcheck;
		$imageWhere = " AND " . prefix('images') . ".show=1";
	}
	$c = 0;
	while ($c < 10) {
		$result = query_single_row('SELECT '.prefix('images').'.filename,'.prefix('images').'.title, '.prefix('images').'.desc, '.prefix('albums').
 														'.folder, '.prefix('images').'.show, '.prefix('albums').'.show, '.prefix('albums').'.password '.
 														'FROM '.prefix('images'). ' INNER JOIN '.prefix('albums').
							 ' ON '.prefix('images').'.albumid = '.prefix('albums').'.id WHERE '.prefix('albums').'.folder!=""'.
		$albumWhere . $imageWhere . ' ORDER BY RAND() LIMIT 1');
		$imageName = $result['filename'];
		if (is_valid_image($imageName)) {
			$image['folder'] = getAlbumFolder() . $result['folder']. '/' . $imageName;
			$image['title'] = $result['title'];
			$image['desc'] = $result['desc'];
			$image['gallery'] = $_current_subgallery;
			restoreContext();
			$_current_gallery_image = $image;
			if ($imageName =='') { return NULL; }
			return $image;
		}
	}
	$image['folder'] = getAlbumFolder() . '/zen-logo.jpg';
	return $image;
}

function getCustomGalleryThumb($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=null) {
	global $_current_subgallery;
	$cachefilename = substr(getImageCacheFilename('', $_current_subgallery,
	getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy))), 1);
	if (!file_exists(SERVERCACHE . $cachefilename)) {
		$img = getGalleryImage();
		cacheGalleryImage($cachefilename, $img['folder'], getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy)));
	}
	return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
}

function getGalleryThumb() {
	global $_current_subgallery;
	$cachefilename = substr(getImageCacheFilename('', $_current_subgallery, getImageParameters(array('thumb'))), 1);
	if (!file_exists(SERVERCACHE . $cachefilename)) {
		$img = getGalleryImage();
		cacheGalleryImage($cachefilename, $img['folder'], getImageParameters(array('thumb')));
	}
	return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
}

function printSubgalleryThumbImage($alt, $class=NULL, $id=NULL) {
	$class = trim($class);
	echo "<img src=\"" . htmlspecialchars(getGalleryThumb()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . " />";
}

function printCustomGalleryThumbImage($alt, $size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=null, $class=NULL, $id=NULL) {
	$class = trim($class);
	/* set the HTML image width and height parameters in case this image was "zen-logo.gif" substituted for no thumbnail then the thumb layout is preserved */
	if ($sizeW = max(is_null($width) ? 0: $sizeW, is_null($cropw) ? 0 : $cropw)) {
		$sizing = ' width="' . $sizeW . '"';
	} else {
		$sizing = null;
	}
	if ($sizeH = max(is_null($height) ? 0 : $height, is_null($croph) ? 0 : $croph)) {
		$sizing = $sizing . ' height="' . $sizeH . '"';
	}
	echo "<img src=\"" . htmlspecialchars(getCustomGalleryThumb($size, $width, $height, $cropw, $croph, $cropx, $cropy)). "\"" . $sizing . " alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . " />";
}

function getRandomGalleryImage() {
	global $_super_gallery_list, $_current_subgallery;
	if (is_null($_super_gallery_list)) {
		getGalleries();
	}
	$_current_subgallery = $_super_gallery_list[rand(0, count($_super_gallery_list))-1];
	return getGalleryImage($_current_subgallery);
}

?>