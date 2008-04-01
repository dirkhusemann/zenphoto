<?php
if($apiKey = getOption('gmaps_apikey')){ 
	require_once(WEBPATH.ZENFOLDER.'/extensions/phooglelite.php');
	$_zp_phoogle = new PhoogleMapLite();
	$_zp_phoogle->setAPIkey($apiKey);
}
/**
 * Returns true if the curent image has EXIF location data
 *
 * @return bool
 */
function hasMapData() {
	if(getOption('gmaps_apikey') != ''){
		$exif = getImageEXIFData();
		if(!empty($exif['EXIFGPSLatitude']) && !empty($exif['EXIFGPSLongitude'])){
			return true;
		}
	}
	return false;
}

/**
 * Causes a Google map to be printed based on the gps data in the current image
 * @param  string $zoomlevel the zoom in for the map
 * @param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @since 1.1.3
 */
function printImageMap($zoomlevel='6', $type=NULL, $width=NULL, $height=NULL){
	global $_zp_phoogle;
	if(getOption('gmaps_apikey') != ''){
		$exif = getImageEXIFData();
		if(!empty($exif['EXIFGPSLatitude']) &&
		!empty($exif['EXIFGPSLongitude'])){

			$_zp_phoogle->setZoomLevel($zoomlevel);
			if (!is_null($width)) { $_zp_phoogle->setWidth($width); }
			if (!is_null($height)) { $_zp_phoogle->setHeight($height); }
			if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
			$lat = $exif['EXIFGPSLatitude'];
			$long = $exif['EXIFGPSLongitude'];
			if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
			if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
			$_zp_phoogle->addGeoPoint($lat, $long);
			$_zp_phoogle->showMap();
		}
	}
}

/**
 * Causes a Google map to be printed based on the gps data in all the images in the album
 * @param  string $zoomlevel the zoom in for the map. NULL will use the default (auto-zoom based on points)
 * @param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @since 1.1.3
 */
function printAlbumMap($zoomlevel=NULL, $type=NULL, $width=NULL, $height=NULL){
	global $_zp_phoogle;
	if(getOption('gmaps_apikey') != ''){
		$foundLocation = false;
		if($zoomlevel){ $_zp_phoogle->setZoomLevel($zoomlevel); }
		if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
		if (!is_null($width)) { $_zp_phoogle->setWidth($width); }
		if (!is_null($height)) { $_zp_phoogle->setHeight($height); }
		while (next_image(false)) {
			$exif = getImageEXIFData();
			if(!empty($exif['EXIFGPSLatitude']) &&
			!empty($exif['EXIFGPSLongitude'])){
				$foundLocation = true;
				$lat = $exif['EXIFGPSLatitude'];
				$long = $exif['EXIFGPSLongitude'];
				if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
				if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
				$infoHTML = '<a href="' . getImageLinkURL() . '"><img src="' .
				getImageThumb() . '" alt="' . getImageDesc() . '" ' .
					'style=" margin-left: 30%; margin-right: 10%; border: 0px; "/></a>' .
					'<p>' . getImageDesc() . '</p>';
				$_zp_phoogle->addGeoPoint($lat, $long, $infoHTML);
			}
		}
		if($foundLocation){ $_zp_phoogle->showMap(); }
	}
}

?>