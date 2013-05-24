/**
* Geo Maps DI: Yahoo Maps
*
* @copyright 2007-2009 by Martin Kelm - All rights reserved.
* @link http://www.idxsolutions.de
* @licence GNU General Public Licence (GPL) 3 http://www.gnu.org/copyleft/gpl.html
*
* You can redistribute and/or modify this script under the terms of the GNU General Public
* License (GPL) version 3, provided that the copyright and license notes, including these
* lines, remain unmodified. This script is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
*
* @package geomaps_di
*/

function initYahooMaps(showCoor, zoomControl, panControl, typeControl,
                       centerLat, centerLng, centerZoom,
                       mapType, uniqueId) {
  if (document.getElementById) {
    var mapElement = document.getElementById("map_"+uniqueId);
  } else if (document.all) {
    var mapElement = document.all["map_"+uniqueId];
  }
  if (typeof mapElement != "undefined") {

    // store var in global context
    if (typeof window.yahooMaps == "undefined") {
      window.yahooMaps = [];
    }
    yahooMaps[uniqueId] = new YMap(mapElement);
    
    if (showCoor === 1) {
      YEvent.Capture(yahooMaps[uniqueId], EventsList.MouseClick,
        function(e, point) {
          if (point.Lat && point.Lon) {
            coorModeAction(uniqueId, point.Lat, point.Lon);
          }
        }
      );
    }
    switch (zoomControl) {
      case 1:
        yahooMaps[uniqueId].addZoomShort();
        break;
      case 2:
        yahooMaps[uniqueId].addZoomLong();
        break;
    }

    if (panControl) {
      yahooMaps[uniqueId].addPanControl();
    }
    if (typeControl) {
      yahooMaps[uniqueId].addTypeControl();
    }

    if (centerLat > -90 && centerLat < 90
        && centerLng > -180 && centerLng < 180 && centerZoom > 0) {
      centerMap(uniqueId, centerLat, centerLng, centerZoom, mapType);
    }
  }
}

function centerMap(uniqueId, lat, lng, zoom, mapType) {
  var point = new YGeoPoint(parseFloat(lat), parseFloat(lng));
  if (point && zoom) {
    yahooMaps[uniqueId].drawZoomAndCenter(point, zoom);
  } else if (point) {
    yahooMaps[uniqueId].drawZoomAndCenter(point);
  }
  if (mapType) {
    yahooMaps[uniqueId].setMapType(mapType);
  }
}

function getMarkerPoint(lat, lng) {
  return new YGeoPoint(parseFloat(lat), parseFloat(lng));
}

function getMarkerObject(uniqueId, point, markerIdx,
           customIconImage, customIconWidth, customIconHeight) {
  if (!point) {
    var point = new YGeoPoint(
      parseFloat(geoMarkers[uniqueId][markerIdx][2]),
      parseFloat(geoMarkers[uniqueId][markerIdx][3])
    );
  }

  if (point) {
    // set marker with or without icon image
    if (geoMarkers[uniqueId][markerIdx][4] &&
        geoMarkers[uniqueId][markerIdx][4].length == 3) {
      customIconImage = geoMarkers[uniqueId][markerIdx][4][0];
      customIconWidth = geoMarkers[uniqueId][markerIdx][4][1];
      customIconHeight = geoMarkers[uniqueId][markerIdx][4][2];
    }
    if (customIconImage && customIconWidth && customIconHeight) {
      if (typeof window.geoMapsIcons == "undefined") {
        window.geoMapsIcons = [];
      }
      if (typeof geoMapsIcons[customIconImage] == "undefined") {
        var new_icon = new YImage();
        new_icon.src = customIconImage;
        var iw = customIconWidth;
        var ih = customIconHeight;
        new_icon.size = new YSize(iw, ih); 
        new_icon.offset = new YCoordPoint(
          -parseInt(iw/2), parseInt(ih/2)
        );         
        geoMapsIcons[customIconImage] = new_icon;
      }
      var marker = new YMarker(point, geoMapsIcons[customIconImage]);
    } else {
      var marker = new YMarker(point);
    }

    // set marker with or without description text
    if (geoMarkers[uniqueId][markerIdx] && geoMarkers[uniqueId][markerIdx][1] &&
        geoMarkers[uniqueId][markerIdx][1].length > 0) {

      if (markerAction == 'click') {
        var actionEvent = EventsList.MouseClick;
      } else {
        var actionEvent = EventsList.MouseOver;
      }
      YEvent.Capture(marker, actionEvent, function() {
        markerListenerEvent(uniqueId, markerIdx);
        marker.openSmartWindow(geoMarkers[uniqueId][markerIdx][1]);
      });
    }
    return marker;
  }
}

function setUpMarkers(uniqueId, clusterer) {
  // no clusterer support yet
  if (geoMarkerObjects[uniqueId].length > 0) {
    for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
      yahooMaps[uniqueId].addOverlay(geoMarkerObjects[uniqueId][i]);
    }
  }
}

function removeMarkers(uniqueId) {
  // no clusterer support yet
  if (geoMarkerObjects[uniqueId].length > 0) {
    for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
      yahooMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
    }
  }
}

function markerListenerEvent(uniqueId, markerIdx) { }

function rotateMarker(uniqueId, i) {  
  var point = YGeoPoint(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  if (typeof geoMarkerObjects[uniqueId][i] != "object") {
    geoMarkerObjects[uniqueId][i] = getMarkerObject(uniqueId, point, i);
  }
  if (typeof geoMarkerObjects[uniqueId][i] == "object") {
    yahooMaps[uniqueId].addOverlay(geoMarkerObjects[uniqueId][i]);
    yahooMaps[uniqueId].drawZoomAndCenter(point, yahooMaps[uniqueId].getZoomLevel());
    setTimeout(function() {
      marker.closeSmartWindow();
      yahooMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
      if (i < geoMarkers[uniqueId].length-1) {
        rotateMarker(uniqueId, i+1);
      } else {
        rotateMarker(uniqueId, 0);
      }
    }, markerRotationTime);
  }
}

function setPolyline(uniqueId, color, width) {
  var points = new Array();
  for (i = 0; i < geoMarkers[uniqueId].length; i++) {
    points[i] = new YGeoPoint(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  }
  var polyline = new YPolyline(points, color, 5, 0.6);
  yahooMaps[uniqueId].addOverlay(polyline);
}

/**
 * dummy, not implemented yet
 *
 * @param array marker geodata of a marker
 */
function correctZoomLevel(uniqueId) {
  // do not zoom higher than the world view ;)
  if (yahooMaps[uniqueId].getZoomLevel() == 12) {
    return 0;
  }
  return 1;
}

function coorModeAction(uniqueId, lng, lat) {
  if (document.getElementById) {
    var coorElement = document.getElementById("coor_"+uniqueId);
  } else if (document.all) {
    var coorElement = document.all["coor_"+uniqueId];
  }
  if (typeof coorElement != "undefined") {
    coorElement.innerHTML = 'Latitude: '+lat+' / '+'Longitude: '+lng;
  }
}
