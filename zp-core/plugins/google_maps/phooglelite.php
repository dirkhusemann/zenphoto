<?php

/**
 * PhoogleMap Lite
 * ATTENTION: This is a modified version of Phoogle Maps
 * The class has been slimmed, trimmed, and reformatted 
 * for performance, maintenance, and readability purposes.
 * 
 * The original Copyright notice is below
 * @class PhoogleMapLite
 * @author Mannkind
 * @copyright 2007 The Null Pointer
 */

/**
 * Phoogle Maps 2.0 | Uses Google Maps API to create customizable maps
 * that can be embedded on your website
 *    Copyright (C) 2005  Justin Johnson
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
 *
 *
 * Phoogle Maps 2.0
 * Uses Google Maps Mapping API to create customizable
 * Google Maps that can be embedded on your website
 *
 * @class        Phoogle Maps 2.0
 * @author       Justin Johnson <justinjohnson@system7designs.com>
 * @copyright    2005 system7designs
 */


class PhoogleMapLite {
		var $validPoints = array();
		var $mapWidth = 595;
		var $mapHeight = 300;
		var $apiKey = "";
		var $showControl = true;
		var $showType = true;
		var $controlType = 'small';
		var $zoomLevel = null;
		var $defaultType = 'G_NORMAL_MAP'; // allowed types: G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
		var $mouseZoom	= true;
		var $showScale	= false;

		/**
 		* @function addGeoPoint
 		* @description Add's an address to be displayed on the Google Map using
 		* latitude/longitude early version of this function, 
 		* considered experimental
 		*/
		function addGeoPoint($lat, $long, $infoHTML = null){
				$pointer = count($this->validPoints);
				$this->validPoints[$pointer]['lat'] = $lat;
				$this->validPoints[$pointer]['long'] = $long;
				$this->validPoints[$pointer]['htmlMessage'] = $infoHTML;
		}

		/**
 		* @function centerMap
 		* @description center's Google Map on a specific point
 		* (thus eliminating the need for two different show methods 
 		* from version 1.0)
 		*/
		function centerMap($lat,$long){
				if (!is_numeric($this->zoomLevel)){
						$this->zoomLevel = 4; 
				}
				$this->centerMap = 'map.centerAndZoom(new GPoint(' .
						$long . ',' . $lat . '), ' . $this->zoomLevel .');' . "\n";
		}
		
		
		/**
 		* @function addAddress
 		* @param $address:string
 		* @returnsBoolean True:False 
 		* (True if address has long/lat, false if it doesn't)
 		* @description  Add's an address to be displayed on the Google Map
 		* (thus eliminating the need for two different show methods 
 		* from version 1.0)
 		*/
		function addAddress($address, $htmlMessage = null){
				if (!is_string($address)){
						die("All Addresses must be passed as a string");
				}
				
				$apiURL = 'http://maps.google.com/maps/geo?&output=xml&key=' . 
						$this->apiKey . 'q=';

				/**
 				* Many hosts do not have allow_url_fopen enabled
 				* Thus you cannot use file_get_contents on a URL
 				* $addressData = file_get_contents($apiURL.urlencode($address));
 				*/
				$ch = curl_init();
				curl_setopt ($ch, CURLOPT_URL, $apiURL.urlencode($address));
				curl_setopt ($ch, CURLOPT_HEADER, 0);

				ob_start();
				curl_exec($ch);
				curl_close($ch);
				$addressData = ob_get_contents();
				ob_end_clean();
				
				/* The original code used xml2array -- but we are simply pulling out
 				* latitude and longitude. It doesn't make sense to parse the entire
 				* xml string for 2 numbers. preg_match it is!
 				*/
				preg_match('<coordinates>(.+), ?(.+), ?.+<\/coordinates>', $addressData, $matches);
				if(count($matches) > 1){
						$pointer = count($this->validPoints);
						$this->validPoints[$pointer]['lat'] = $matches[1];
						$this->validPoints[$pointer]['long'] = $matches[2];
						$this->validPoints[$pointer]['passedAddress'] = $address;
						$this->validPoints[$pointer]['htmlMessage'] = $htmlMessage;
				}
		}

		/**
 		* @function     setWidth
 		* @param        $width:int
 		* @returns      nothing
 		* @description  Sets the width of the map to be displayed
 		*/
		function setWidth($width){ $this->mapWidth = $width; }

		/**
 		* @function     setHeight
 		* @param        $height:int
 		* @returns      nothing
 		* @description  Sets the height of the map to be displayed
 		*/
		function setHeight($height){ $this->mapHeight = $height; }

		/**
 		* @function     setAPIkey
 		* @param        $key:string
 		* @returns      nothing
 		* @description  Stores the API Key acquired from Google
 		*/
		function setAPIkey($key){ $this->apiKey = $key; }

		/**
 		* @param        $lvl :string
 		* @returns      nothing
 		* @description  Sets the zoom level
 		*/
		function setZoomLevel($lvl) { $this->zoomLevel = $lvl; }

/**
 		* @param        $type:string
 		* @returns      nothing
 		* @description  Stores maptype in $defaultType
 		*/
function setMapType($type) { $this->defaultType = $type; }

		/**
 		* @function     printGoogleJS
 		* @returns      nothing
 		* @description  Adds the necessary Javascript for the Google Map to 
 		* function should be called in between the html <head></head> tags
 		*/
		function printGoogleJS(){
				echo "\n";
				echo '<script src="http://maps.google.com/maps?file=api&v=2&key=' .
						$this->apiKey .'" type="text/javascript"></script>';
				echo "\n";
		}
		
		/**
 		* @function     showMap
 		* @description  Displays the Google Map on the page
 		*/
		function showMap(){
				/* Output the Map div */
				echo "\n" .
						'<div id="map" ' .
 								'style="width: ' . $this->mapWidth .'px; ' . 
 								'height: ' . $this->mapHeight .'px">' . "\n" .
						'</div>' . "\n";

				/* Output the javascript */
				echo "\n" .
						'<script type="text/javascript">' . "\n" .
						'function showmap() { //<![CDATA[ ' . "\n" .
								'if (GBrowserIsCompatible()) { ' . 
										'var map = new GMap2(document.getElementById("map")); ';

				if (empty($this->centerMap)){
						echo 'map.setCenter(new GLatLng(0,0), 0); ' . "\n";
				} else { echo   $this->centerMap; }
				
				echo "\n" . 
						'}' . "\n" .
						'var icon = new GIcon(); ' .
						'icon.image = "http://labs.google.com/' . 
								'ridefinder/images/mm_20_red.png"; ' .
						'icon.shadow = "http://labs.google.com/' . 
								'ridefinder/images/mm_20_shadow.png"; ' .
						'icon.iconSize = new GSize(12, 20); ' .
						'icon.shadowSize = new GSize(22, 20); ' .
						'icon.iconAnchor = new GPoint(6, 20); ' .
						'icon.infoWindowAnchor = new GPoint(5, 1); ' .
						"\n";
								
				if ($this->showControl){
						if ($this->controlType == 'small'){
								echo 'map.addControl(new GSmallMapControl());';
						}
						if ($this->controlType == 'large'){
								echo 'map.addControl(new GLargeMapControl());';
						}
				}
				if ($this->showType){
						echo 'map.addControl(new GMapTypeControl());';
						echo 'map.setMapType('. $this->defaultType .');';
				}
				if ($this->mouseZoom){
					echo 'map.enableScrollWheelZoom();';
				} else {
					echo 'map.disableScrollWheelZoom();';
				}
				if ($this->showScale) {
						echo 'map.addControl(new GMapTypeControl());' . "\n";
				}

				$numPoints = count($this->validPoints);
				echo 'var bounds = new GLatLngBounds(); ' . "\n";
				for ($g = 0; $g<$numPoints; $g++){
						echo "\n" .
								'var point' . $g . ' = new GLatLng(' . 
										$this->validPoints[$g]['lat'] . ',' . 
										$this->validPoints[$g]['long'] .');' . "\n" .
								'var marker' . $g . ' = new GMarker(point' . $g . ');' . "\n" .
								'map.addOverlay(marker' . $g .'); ' . "\n" .
								'bounds.extend(point' . $g . '); ' . "\n" . 
								'GEvent.addListener(marker' . $g . ', "click", function() { ';
						if ($this->validPoints[$g]['htmlMessage'] != null){
								echo "\n" .
								'marker' . $g . '.openInfoWindowHtml(\'' . 
										$this->validPoints[$g]['htmlMessage'] .'\');';
						}
						echo '});' . "\n";
				}
				echo 'map.setCenter(bounds.getCenter()); ' . "\n";
				if (!is_numeric($this->zoomLevel)){
						echo 'map.setZoom(map.getBoundsZoomLevel(bounds)); ' . "\n";
				} else {
						echo 'map.setZoom(' . $this->zoomLevel . '); ' . "\n";
				}
				echo "\n" .
				'//]]> ' . "\n" .
				'} window.onload = showmap; ' . "\n" .
				'</script>';
		}

}
?>
