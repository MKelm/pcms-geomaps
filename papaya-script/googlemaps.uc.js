/* 
*  Geo maps for papaya CMS 5: Google Maps script 
*  Author: Martin Kelm, 31.05.2007
*/

var googleMap = null;
var googleGeocoder = null;

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

function getAddressPoint(address, text) {
	if (googleGeocoder == null) {
		googleGeocoder = new GClientGeocoder();
	}
	googleGeocoder.getLatLng(
		address,
		function(point) {
			setMarker(point, text);
		}
	);
}

function getMarkerPoint(lat, long) {
	return new YGeoPoint(parseFloat(lat), parseFloat(long));
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
	if (markers[i][0] == 2) {
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
	} else {
		if (googleGeocoder == null) {
			googleGeocoder = new GClientGeocoder();
		}
		googleGeocoder.getLatLng(
			markers[i][2],
			function(point) {
				marker = setMarker(point);
				if (typeof marker == "object") {
					googleMap.setCenter(point);
					GEvent.addListener(marker, markerAction, function () {
						marker.openInfoWindowHtml(markers[i][1]);
					});
					marker.openInfoWindowHtml(markers[i][1]);
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
		);
	}
}
