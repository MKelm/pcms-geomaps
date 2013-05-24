/* 
*  Geo maps for papaya CMS 5: Yahoo Maps script 
*  Author: Martin Kelm, 31.05.2007
*/

var yahooMap = null;

function initYahooMaps(showCoor, zoomControl, panControl, typeControl, 
											 centerLat, centerLng, centerZoom, 
											 mapType, uniqueId) {					 	
	yahooMap = new YMap(document.getElementById("map_"+uniqueId));
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
	var point = new YGeoPoint(parseFloat(lat),
												  parseFloat(lng));							  
  if (point) {
		yahooMap.drawZoomAndCenter(point, zoom);
	}
	yahooMap.setMapType(mapType);
}

function getAddressPoint(address, text) {
	var point = new YGeoPoint(address);
	setMarker(point, text);
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
	if (markers[i][0] == 2) {
		var point = new YGeoPoint(markers[i][2], markers[i][3]);
	} else {
		var point = new YGeoPoint(markers[i][2]);
	}
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
