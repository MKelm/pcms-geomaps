/**
* Geo Maps DI: Google Maps
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

/**
 * Initializes the Google Maps API and triggers the rendering of the map
 *
 * It depends on the fact that the div-container containing the map will have an id attribute.
 * The id-attribute must contain the string 'map_' + the string represented by the unique
 * parameter. There has to be another DOM element which can be identifies via an id-attribute
 * containing 'coor_' and the previous named unique id
 */
function initGoogleMaps(showCoor, basicControl, scaleControl,
                        typeControl, overviewControl,
                        centerLat, centerLng, centerZoom,
                        mapType, uniqueId, width, height) {
  if (GBrowserIsCompatible()) {
    if (document.getElementById) {
      var mapElement = document.getElementById("map_"+uniqueId);
    } else if (document.all) {
      var mapElement = document.all["map_"+uniqueId];
    }

    // adds google maps unload event
    window.onunload = function() {
      if (typeof GUnload != "undefined") {
        GUnload();
      }
    }

    // store var in global context
    if (typeof window.googleMaps == "undefined") {
      window.googleMaps = [];
    }

    if (typeof mapElement != "undefined") {

      /*
       * GMap2 loads the Google Maps  base scripts
       * GSize initiatea an Object defining width and size passed to GMaps object
       *
       * heigth and width are defined by the backend
       */
      googleMaps[uniqueId] = new GMap2(mapElement, {'size' : new GSize(width, height)});
      var googleMap = googleMaps[uniqueId];

      if (showCoor === 1) {
        GEvent.addListener(googleMap, "click",
          function(googleMap, point) {
            if (point) {
              coorModeAction(uniqueId, point.x, point.y);
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

      // observe mouse scrolling
      GMap2.prototype.wheelZoom = function(event) {
        if (event.cancelable) {
          event.preventDefault();
        }
        if ((event.detail || -event.wheelDelta) < 0) {
          googleMap.zoomIn();
        } else {
          googleMap.zoomOut();
        }
        return false;
      }
      GEvent.addDomListener(mapElement, "DOMMouseScroll", googleMap.wheelZoom);
      GEvent.addDomListener(mapElement, "mousewheel", googleMap.wheelZoom);

      if (centerLat > -90 && centerLat < 90
          && centerLng > -180 && centerLng < 180 && centerZoom > 0) {
        centerMap(uniqueId, centerLat, centerLng, centerZoom, mapType);
      }
    }
  }
}

/**
 * Sets the center point of the map to be shown
 *
 * @param float lat latitude of the center point
 * @param float lng longitude of the center point
 * @param integer zoom zoom level of the map
 * @param object mapType which type of map shall be displayed
 * @param boolean pan use pan / smooth animation to center
 */
function centerMap(uniqueId, lat, lng, zoom, mapType, pan) {
  var point = new GLatLng(parseFloat(lat), parseFloat(lng));
  if (point && zoom) {
    googleMaps[uniqueId].setCenter(point, zoom);
  } else if (point && pan) {
    googleMaps[uniqueId].panTo(point);
  } else if (point) {
    googleMaps[uniqueId].setCenter(point);
  }
  if (mapType) {
    googleMaps[uniqueId].setMapType(mapType);
  }
}

function getMarkerPoint(lat, lng) {
  return new GLatLng(parseFloat(lat), parseFloat(lng));
}

function getMarkerObject(uniqueId, point, markerIdx,
           customIconImage, customIconWidth, customIconHeight) {
  if (!point) {
    var point = new GLatLng(
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
        var new_icon = new GIcon();
        new_icon.image = customIconImage;
        var iw = customIconWidth;
        var ih = customIconHeight;
        new_icon.size = new GSize(iw, ih);
        new_icon.iconAnchor = new GPoint(iw/2,ih/2);
        new_icon.infoWindowAnchor = new GPoint(iw/2,ih/2);
        geoMapsIcons[customIconImage] = new_icon;
      }
      var marker = new GMarker( point,
        { icon:geoMapsIcons[customIconImage],
          zIndexProcess:markerZIndexProcessEvent }
      );
    } else {
      var marker = new GMarker(
        point, { zIndexProcess:markerZIndexProcessEvent }
      );
    }

    // set description text
    if (geoMarkers[uniqueId][markerIdx][1] &&
        geoMarkers[uniqueId][markerIdx][1].length > 0) {
      GEvent.addListener(marker, markerAction, function () {
        markerListenerEvent(uniqueId, markerIdx);
        marker.openInfoWindowHtml(geoMarkers[uniqueId][markerIdx][1]);
      });
    }
    return marker;
  }
}

function setUpMarkers(uniqueId, clusterer) {
  if (clusterer == 1) {
    if (typeof geoMapClustererStyles != "undefined") {
      clustererStyles = { styles: geoMapClustererStyles };
    } else {
      clustererStyles = null;
    }
    geoMarkerClusterers[uniqueId] = new MarkerClusterer(
      googleMaps[uniqueId], geoMarkerObjects[uniqueId], clustererStyles
    );
  } else {
    if (geoMarkerObjects[uniqueId].length > 0) {
      for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
        googleMaps[uniqueId].addOverlay(geoMarkerObjects[uniqueId][i]);
      }
    }
  }
}

function removeMarkers(uniqueId) {
  if (typeof geoMarkerClusterers[uniqueId] != "undefined") {
    geoMarkerClusterers[uniqueId].clearMarkers();
    geoMarkerClusterers[uniqueId] = null;
  } else {
    if (geoMarkerObjects[uniqueId].length > 0) {
      for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
        googleMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
      }
    }
  }
}

function markerZIndexProcessEvent(marker, b) {
  // i.e. inverse z-index
  //return -1 * GOverlay.getZIndex(marker.getPoint().lat());
  return 1; // by order
}

function markerListenerEvent(uniqueId, markerIdx) { }

function rotateMarker(uniqueId, i) {
  var point = new GLatLng(parseFloat(geoMarkers[uniqueId][i][2]),
                          parseFloat(geoMarkers[uniqueId][i][3]));
  if (typeof geoMarkerObjects[uniqueId][i] != "object") {
    geoMarkerObjects[uniqueId][i] = getMarkerObject(uniqueId, point, i);
  }
  if (typeof geoMarkerObjects[uniqueId][i] == "object") {
    googleMaps[uniqueId].addOverlay(geoMarkerObjects[uniqueId][i]);
    googleMaps[uniqueId].setCenter(point);
    setTimeout(function() {
      googleMaps[uniqueId].closeInfoWindow();
      googleMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
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
    points[i] = new GLatLng(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  }
  var colorValues = new Object;
  colorValues['orange'] = '#FF7D00';
  colorValues['blue'] = '#0000FF';
  colorValues['lightblue'] = '#8080FF';
  colorValues['brown'] = '#912D00';
  colorValues['green'] = '#00FF00';
  colorValues['lightgreen'] = '#80FF80';
  colorValues['grey'] = '#808080';
  colorValues['black'] = '#000000';
  colorValues['maroon'] = '#A51B00';
  colorValues['purple'] = '#800080';

  if (typeof colorValues[color] != "undefined" && points.length > 0) {
    var polyline = new GPolyline(points, color, 5);
    googleMaps[uniqueId].addOverlay(polyline);
  }
}

/**
 * Sets the zoom level of displayed google map until every defined marker is in viewport
 *
 * @param array marker geodata of a marker
 */
function zoomIntoFocus(uniqueId, marker) {
  // do not zoom higher than the world view ;)
  if (googleMaps[uniqueId].getZoom() == 0) {
    return 0;
  }

  // get map boundaries
  var s = googleMaps[uniqueId].getBounds().getSouthWest().lng();
  var w = googleMaps[uniqueId].getBounds().getSouthWest().lat();
  var n = googleMaps[uniqueId].getBounds().getNorthEast().lng();
  var o = googleMaps[uniqueId].getBounds().getNorthEast().lat();

  // determine collisions
  var calS = marker[1] - s;
  var calW = w - marker[0];
  var calN = marker[1] - n;
  var calO = o - marker[0];

  // collision(s) found
  if (calS < 0 || calW > 0) {
    // zoom out
    googleMaps[uniqueId].zoomOut();

    if (!zoomIntoFocus(uniqueId, marker)) {
      return 0;
    }
  }
  if (calN > 0 || calO < 0) {
    // zoom out
    googleMaps[uniqueId].zoomOut();

    if (!zoomIntoFocus(uniqueId, marker)) {
      return 0;
    }
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
