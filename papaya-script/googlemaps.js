/**
* Geo maps for papaya CMS 5: Google Maps script 
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

var googleMap = null;

function initGoogleMaps(showCoor, basicControl, scaleControl, 
											 typeControl, overviewControl, 
											 centerLat, centerLng, centerZoom, 
											 mapType, uniqueId) {
	if (GBrowserIsCompatible()) {
		googleMap = new GMap2(document.getElementById("map_"+uniqueId));
		if (showCoor) {
			GEvent.addListener(googleMap, "click",
				function(googleMap, point) {
					if (point) {
						document.getElementById("coor_"+uniqueId).innerHTML	= 
						  'Latitude: ' + point.y + ' / ' + 'Longitude: ' + point.x;
					}
				}
			);
		}
		switch (basicControl) {
			case 0: 
			  googleMap.addControl(new GLargeMapControl()); 
			  break;
			case 1: 
			  googleMap.addControl(new GSmallMapControl()); 
			  break;
			case 2: 
			  googleMap.addControl(new GSmallZoomControl()); 
			  break;
		}
		if (scaleControl) {
			googleMap.addControl(new GScaleControl()); 
		}
		if (typeControl) {
			googleMap.addControl(new GMapTypeControl());
		}
		if (overviewControl) {
			googleMap.addControl(new GOverviewMapControl());
		}
		GEvent.addDomListener(document.getElementById("map_"+uniqueId), 
		  "DOMMouseScroll", mouseWheelZoom);
		GEvent.addDomListener(document.getElementById("map_"+uniqueId), 
		  "mousewheel", mouseWheelZoom);
		centerMap(centerLat, centerLng, centerZoom, mapType);
	}
}

function mouseWheelZoom(obj) { 
	(obj.detail || -obj.wheelDelta) < 0 ? googleMap.zoomIn() :
		googleMap.zoomOut();
}

function centerMap(lat, lng, zoom, mapType) {
	var point = new GLatLng(parseFloat(lat),
												  parseFloat(lng));
  if (point) {
		googleMap.setCenter(point, zoom, mapType);
	}
}

function getMarkerPoint(lat, long) {
	return new GLatLng(parseFloat(lat), parseFloat(long));
}

function setMarker(point, text) {
	if (typeof point != "undefined") {
		var marker = new GMarker(point);
		if (typeof text != "undefined" && text.length > 0) {
			GEvent.addListener(marker, markerAction, function () {
				marker.openInfoWindowHtml(text);
			});
		}
		googleMap.addOverlay(marker);
		return marker;
	}
}

function rotateMarker(i) {
	var point = new GLatLng(parseFloat(markers[i][2]),
													parseFloat(markers[i][3]));
	marker = setMarker(point);
	if (typeof marker == "object") {
		googleMap.setCenter(point);
		marker.openInfoWindowHtml(markers[i][1]);
		GEvent.addListener(marker, markerAction, function () {
			marker.openInfoWindowHtml(markers[i][1]);
		});
		setTimeout(function() {
			googleMap.closeInfoWindow();
			googleMap.removeOverlay(marker);
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
		points[i] = new GLatLng(markers[i][2], markers[i][3]);
	}
	var polyline = new GPolyline(points, color, 5);
	googleMap.addOverlay(polyline);
}
