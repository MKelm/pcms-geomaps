/**
* Geo maps for papaya CMS 5: Yahoo Maps script
*
* @copyright 2007-2009 by Martin Kelm - All rights reserved.
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
    if (typeof window.yahooMap == "undefined") {
      window.yahooMap = [];
    }
    window.uniqueId = uniqueId;

    yahooMap[uniqueId] = new YMap(mapElement);
    if (showCoor === 1) {
      YEvent.Capture(yahooMap[uniqueId], EventsList.MouseClick,
        function(e, point) {
          if (point.Lat && point.Lon) {
            coorModeAction(point.Lat, point.Lon);
          }
        }
      );
    }
    switch (zoomControl) {
      case 1:
        yahooMap[uniqueId].addZoomShort();
        break;
      case 2:
        yahooMap[uniqueId].addZoomLong();
        break;
    }

    if (panControl) {
      yahooMap[uniqueId].addPanControl();
    }
    if (typeControl) {
      yahooMap[uniqueId].addTypeControl();
    }

    if (centerLat > -90 && centerLat < 90
        && centerLng > -180 && centerLng < 180 && centerZoom > 0) {
      centerMap(centerLat, centerLng, centerZoom, mapType);
    }
  }
}

function centerMap(lat, lng, zoom, mapType) {
  var point = new YGeoPoint(parseFloat(lat), parseFloat(lng));
  if (point && zoom) {
    yahooMap[uniqueId].drawZoomAndCenter(point, zoom);
  } else if (point) {
    yahooMap[uniqueId].drawZoomAndCenter(point);
  }
  if (mapType) {
    yahooMap[uniqueId].setMapType(mapType);
  }
}

function getMarkerPoint(lat, lng) {
  return new YGeoPoint(parseFloat(lat), parseFloat(lng));
}

function setMarker(point, markerIdx) {
  if (!point) {
    var point = new GLatLng(parseFloat(geoMarkers[uniqueId][markerIdx][2]),
                            parseFloat(geoMarkers[uniqueId][markerIdx][3]));
  }

  if (point) {
    var marker = new YMarker(point);

    // set marker with or without description text
    if (geoMarkers[uniqueId][markerIdx] && geoMarkers[uniqueId][markerIdx][1] &&
        geoMarkers[uniqueId][markerIdx][1].length > 0) {

      if (markerAction == 'click') {
        YEvent.Capture(marker, EventsList.MouseClick, function() {
          markerListenerEvent(markerIdx);
          marker.openSmartWindow(geoMarkers[uniqueId][markerIdx][1]);
        });
      } else {
        YEvent.Capture(marker, EventsList.MouseOver, function() {
          markerListenerEvent(markerIdx);
          marker.openSmartWindow(geoMarkers[uniqueId][markerIdx][1]);
        });
      }
    }
    yahooMap[uniqueId].addOverlay(marker);
    return marker;
  }
}

function markerListenerEvent(markerIdx) { }

function rotateMarker(i) {
  var point = new YGeoPoint(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  geoMarkers[uniqueId][i][5] = setMarker(point, i);

  if (typeof geoMarkers[uniqueId][i][5] == "object") {
    yahooMap[uniqueId].drawZoomAndCenter(point, yahooMap[uniqueId].getZoomLevel());
    setTimeout(function() {
      marker.closeSmartWindow();
      yahooMap[uniqueId].removeOverlay(geoMarkers[uniqueId][i][5]);
      geoMarkers[uniqueId][i][5] = null;
      if (i < geoMarkers[uniqueId].length-1) {
        rotateMarker(i+1);
      } else {
        rotateMarker(0);
      }
    }, markerRotationTime);
  }
}

function setPolyline(color, width) {
  var points = new Array();
  for (i = 0; i < geoMarkers[uniqueId].length; i++) {
    points[i] = new YGeoPoint(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  }
  var polyline = new YPolyline(points, color, 5, 0.6);
  yahooMap[uniqueId].addOverlay(polyline);
}

/**
 * dummy, not implemented yet
 *
 * @param array marker geodata of a marker
 */
function correctZoomLevel() {
  // do not zoom higher than the world view ;)
  if (yahooMap[uniqueId].getZoomLevel() == 12) {
    return 0;
  }

  return 1;
}

function coorModeAction(lng, lat) {
  if (document.getElementById) {
    var coorElement = document.getElementById("coor_"+uniqueId);
  } else if (document.all) {
    var coorElement = document.all["coor_"+uniqueId];
  }
  if (typeof coorElement != "undefined") {
    coorElement.innerHTML = 'Latitude: '+lat+' / '+'Longitude: '+lng;
  }
}
