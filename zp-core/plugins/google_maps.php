<?php
/**
 * google_maps -- provides for placing google maps on image and album pages.
 * Updated to use the standard phoogle class
 *
 * Plugin Option 'gmaps_apikey' is used to supply the sit Google Maps API key.
 *
 * @author Dustin Brewer (mankind), Stephen Billard (sbillard)
 * @version 1.2.0
 * @package plugins
 */

$plugin_description = gettext("Support for providing Google Maps based on EXIF latitude and longitude in the images.");
$plugin_author = 'Dustin Brewer (mankind), Stephen Billard (sbillard)';
$plugin_version = '1.2.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---google_maps.php.html";
$option_interface = new google_mapsOptions();

$mapkey = getOption('gmaps_apikey');
if (isset($_zp_gallery_page) && $_zp_gallery_page != 'index.php' && !empty($mapkey)) {
// NOTE: This is copied from the printGoogleJS function in the phoogle class.
//       If you update the phoogle class be sure this has not changed.
	addPluginScript("\n<script src=\"http://maps.google.com/maps?file=api&v=2&key=".$mapkey."\" type=\"text/javascript\"></script>\n");
	addPluginScript("\n<script type=\"text/javascript\">var map;</script>\n");
}
/**
 * Plugin option handling class
 *
 */
class google_mapsOptions {

	function google_mapsOptions() {
		/* put any setup code needed here */
		setOptionDefault('gmaps_apikey', '');
		setOptionDefault('gmaps_show_all_album_points', 0);
		setOptionDefault('gmaps_width', 595);
		setOptionDefault('gmaps_height', 300);
	}

	function getOptionsSupported() {
		return array(	gettext('Google Maps API key') => array('key' => 'gmaps_apikey', 'type' => 0,
										'desc' => gettext("If you're going to be using Google Maps,").
											' <a	href="http://www.google.com/apis/maps/signup.html" target="_blank"> '.
		gettext("get an API key</a> and enter it here.")),
		gettext('All album points') => array ('key' => 'gmaps_show_all_album_points', 'type' => 1,
										'desc' => gettext('Controls which image points are shown on an album page. Check to show points for all images in the album. If not checked points are shown only for those images whose thumbs are on the page.')),
		gettext('Map width') => array('key' => 'gmaps_width', 'type' => 0,
										'desc' => gettext('The default width of the map.')),
		gettext('Map height') => array('key' => 'gmaps_height', 'type' => 0,
										'desc' => gettext('The default height of the map.'))
		);
	}

	function handleOption($option, $currentValue) {}

}

if($apiKey = getOption('gmaps_apikey')){
	require_once(SERVERPATH.'/'.ZENFOLDER.'/plugins/google_maps/phoogle.php');
	$_zp_phoogle = new PhoogleMap();
	$_zp_phoogle->setAPIkey($apiKey);
} else {
	$_zp_phoogle = NULL;
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
 * Adds a geoPoint after first insuring it uses periods for the decimal separator
 *
 * @param string $lat Latitude of the point
 * @param string $long Longitude of the point
 */
function addPoint($lat, $long, $html) {
	global $_zp_phoogle;
	$_zp_phoogle->addGeoPoint(str_replace(',', '.', $lat), str_replace(',', '.', $long), $html);
}

/**
 * Causes a Google map to be printed based on the gps data in the current image
 * @param string $zoomlevel the zoom in for the map
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @param string $text text for the pop-up link
 * @param bool $toggle set to true to hide initially
 * @param string $id DIV id
 */
function printImageMap($zoomlevel=NULL, $type=NULL, $width=NULL, $height=NULL, $text=NULL, $toggle=true, $id='googlemap') {
	global $_zp_phoogle;
	if(getOption('gmaps_apikey') != ''){

		$exif = getImageEXIFData();
		if(!empty($exif['EXIFGPSLatitude']) && !empty($exif['EXIFGPSLongitude'])){

			if($zoomlevel){
				$_zp_phoogle->zoomLevel = $zoomlevel;
			}
			if (!is_null($width)) {
				$_zp_phoogle->setWidth($width);
			} else {
				$_zp_phoogle->setWidth(getOption('gmaps_width'));
			}
			if (!is_null($height)) {
				$_zp_phoogle->setHeight($height);
			} else {
				$_zp_phoogle->setHeight(getOption('gmaps_height'));
			}
			if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
			$lat = $exif['EXIFGPSLatitude'];
			$long = $exif['EXIFGPSLongitude'];
			if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
			if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
			addPoint($lat, $long, js_encode(getImageDesc()));
			$dataid = $id.'_data';
			if (is_null($text)) $text = gettext('Google Map');
			echo "<a href=\"javascript: vtoggle('$dataid');\" title=\"".gettext('Display or hide the Google Map.')."\">";
			echo "<strong>$text</strong>";
			echo "</a>\n";
			echo "  <div id=\"$dataid\"" . ($toggle ? " style=\"visibility: hidden;position:absolute;left: -3000px;top: -3000px\"" : '') . ">\n";
			$_zp_phoogle->showMap(!$toggle);
						echo "  </div>\n</div>\n\n";

		}
	}
}

/**
 * Causes a Google map to be printed based on the gps data in all the images in the album
 * @param  string $zoomlevel the zoom in for the map. NULL will use the default (auto-zoom based on points)
 * @param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @param string $text text for the pop-up link
 * @param bool $toggle set to true to hide initially
 * @param int $firstPageCount the number of images which can go on the page that transitions between albums and images
 * @param string $id DIV id
 */
function printAlbumMap($zoomlevel=NULL, $type=NULL, $width=NULL, $height=NULL, $text=NULL, $toggle=true, $id='googlemap', $firstPageImages=0){
	global $_zp_phoogle, $_zp_images, $_zp_current_album;
	if(getOption('gmaps_apikey') != ''){
		$foundLocation = false;
		if($zoomlevel){
			$_zp_phoogle->zoomLevel = $zoomlevel;
		}
		$dataid = $id.'_data';
		if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
		if (!is_null($width)) {
			$_zp_phoogle->setWidth($width);
		} else {
			$_zp_phoogle->setWidth(getOption('gmaps_width'));
		}
		if (!is_null($height)) {
			$_zp_phoogle->setHeight($height);
		} else {
			$_zp_phoogle->setHeight(getOption('gmaps_height'));
		}

		resetCurrentAlbum(); // start from scratch
		while (next_image(getOption('gmaps_show_all_album_points'), $firstPageImages)) {
			$exif = getImageEXIFData();
			if(!empty($exif['EXIFGPSLatitude']) && !empty($exif['EXIFGPSLongitude'])){
				$foundLocation = true;
				$lat = $exif['EXIFGPSLatitude'];
				$long = $exif['EXIFGPSLongitude'];
				if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
				if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
				$infoHTML = '<a href="' . getImageLinkURL() . '"><img src="' .
					getImageThumb() . '" alt="' . getImageDesc() . '" ' .
					'style=" margin-left: 30%; margin-right: 10%; border: 0px; "/></a>' .
					'<p>' . getImageDesc() . '</p>';
				addPoint($lat, $long, js_encode($infoHTML));
			}
		}
		resetCurrentAlbum(); // clear out any 'damage'
		
		if($foundLocation){
			echo "<a href=\"javascript: toggle('$dataid');\" title=\"".gettext('Display or hide the Google Map.')."\">";
			if (is_null($text)) $text = gettext('Google Map');
			echo $text;
			echo "</a>\n";
			echo "  <div id=\"$dataid\"" . ($toggle ? " style=\"display: none;\"" : '') . ">\n";
			$_zp_phoogle->showMap(!$toggle);
			echo "  </div>\n\n";
		}
	}
}

?>