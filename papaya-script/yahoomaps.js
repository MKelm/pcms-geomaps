/**
* Geo maps for papaya CMS 5: Yahoo Maps script 
*
* @copyright 2007 by Martin Kelm - All rights reserved.
* @link http://www.idxsolutions.de
* @licence GNU General Public Licence (GPL) 2 http://www.gnu.org/copyleft/gpl.html
*
* You can redistribute and/or modify this script under the terms of the GNU General Public
* License (GPL) version 2, provided that the copyright and license notes, including these
* lines, remain unmodified. This script is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
* 
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/

var yahooMap = null;

function initYahooMaps(showCoor, zoomControl, panControl, typeControl, 
											 centerLat, centerLng, centerZoom, 
											 mapType, uniqueId) {					 	
	yahooMap = new YMap(document.getElementById("map_"+uniqueId));
	
	if (showCoor) {
		YEvent.Capture(yahooMap, EventsList.MouseClick,
			function(point) {
				if (point) {
					document.getElementById("coor_"+uniqueId).innerHTML	= 
						'Latitude: ' + point.Lat + ' / ' + 'Longitude: ' + point.Lon;
				}
			}
		);
	}
		
	switch (zoomControl) {
		case 1: 
			yahooMap.addZoomShort();
			break;
		case 2: 
			yahooMap.addZoomLong();
			break;
	}
	if (panControl) {
		yahooMap.addPanControl();
	}
	if (typeControl) {
		yahooMap.addTypeControl();
	}
	centerMap(centerLat, centerLng, centerZoom, mapType);
}

function centerMap(lat, lng, zoom, mapType) {
	var point = new YGeoPoint(parseFloat(lat), parseFloat(lng));							  
  if (point) {
		yahooMap.drawZoomAndCenter(point, zoom);
	}
	yahooMap.setMapType(mapType);
}

function getMarkerPoint(lat, long) {	
	return new YGeoPoint(parseFloat(lat), parseFloat(long));
}

function setMarker(point, text) {
	if (typeof point != "undefined") {
		var marker = new YMarker(point);
    if (typeof text != "undefined" && text.length > 0) {
    	if (markerAction == 'click') {
				YEvent.Capture(marker, EventsList.MouseClick, function() {
					marker.openSmartWindow(text);
				});
			} else {
				YEvent.Capture(marker, EventsList.MouseOver, function() {
					marker.openSmartWindow(text);
				});
			}
		}
		yahooMap.addOverlay(marker);
		return marker;
	}
}

function rotateMarker(i) {
	var point = new YGeoPoint(markers[i][2], markers[i][3]);
	
	marker = setMarker(point);
	if (typeof marker == "object") {
		yahooMap.drawZoomAndCenter(point, yahooMap.getZoomLevel());
		marker.openSmartWindow(markers[i][1]);
		if (markerAction == 'click') {
			YEvent.Capture(marker, EventsList.MouseClick, function() {
				marker.openSmartWindow(markers[i][1]);
			});
		} else {
			YEvent.Capture(marker, EventsList.MouseOver, function() {
				marker.openSmartWindow(markers[i][1]);
			});
		}
		setTimeout(function() {
			marker.closeSmartWindow();
			yahooMap.removeOverlay(marker);
			if (i < markers.length-1) {
				rotateMarker(i+1);
			} else {
				rotateMarker(0);
			}
		}, markerRotationTime);
	}
}

function setPolyline(color, width) {
	var points = new Array();
	for (i = 0; i < markers.length; i++) {
		points[i] = new YGeoPoint(markers[i][2], markers[i][3]);
	}
	var polyline = new YPolyline(points, color, 5, 0.6);
	yahooMap.addOverlay(polyline);
}
