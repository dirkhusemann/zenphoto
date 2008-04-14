<?php
/**
 * google_maps -- provides for placing google maps on image and album pages.
 *
 * Plugin Option 'gmaps_apikey' is used to supply the sit Google Maps API key.
 */

$plugin_description = gettext("Support for providing Google Maps based on EXIF latitude and longitude in the images.");
$plugin_author = 'Dustin Brewer (mankind)';
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---google_maps.php.html";
$option_interface = new google_mapsOptions();

/**
 * Plugin option handling class
 *
 */
class google_mapsOptions {

	function google_mapsOptions() {
		/* put any setup code needed here */
		setOptionDefault('gmaps_apikey', '');
	}
	
	function getOptionsSupported() {
		return array(	gettext('Google Maps API key') => array('key' => 'gmaps_apikey', 'type' => 0, 
										'desc' => gettext("If you're going to be using Google Maps,").
											' <a	href="http://www.google.com/apis/maps/signup.html" target="_blank"> '.
											gettext("get an API key</a> and enter it here."))
		);
	}
	function handleOption($option, $currentValue) {}
}

if($apiKey = getOption('gmaps_apikey')){ 
	require_once(SERVERPATH.'/'.ZENFOLDER.'/plugins/google_maps/phooglelite.php');
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
				$infoHTML = '<a href="' . htmlspecialchars(getImageLinkURL()) . '"><img src="' .
				getImageThumb() . '" alt="' . htmlspecialchars(getImageDesc()) . '" ' .
					'style=" margin-left: 30%; margin-right: 10%; border: 0px; "/></a>' .
					'<p>' . htmlspecialchars(getImageDesc()) . '</p>';
				$_zp_phoogle->addGeoPoint($lat, $long, $infoHTML);
			}
		}
		if($foundLocation){ $_zp_phoogle->showMap(); }
	}
}

?>