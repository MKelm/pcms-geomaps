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
* @author Bastian Feder <info@papaya-cms.com>
*/

/**
 * initializes the Google Maps API and triggers the rendering of the map
 *
 * It depends on the fact that the div-container containing the map will have an id attribute.
 * The id-attribute must contain the string 'map_' + the string represented by the unique
 * parameter. There has to be another DOM element which can be identifies via an id-attribute
 * containing 'coor_' and the previous named unique id
 *
 * @param
 */
function initGoogleMaps(showCoor, basicControl, scaleControl,
                        typeControl, overviewControl,
                        centerLat, centerLng, centerZoom,
                        mapType, uniqueId, width, height) {
  if (GBrowserIsCompatible()) {
    if (document.getElementById) {
      var mapElement = document.getElementById("map_"+uniqueId);
      var coorElement = document.getElementById("coor_"+uniqueId);
    } else if (document.all) {
      var mapElement = document.all["map_"+uniqueId];
      var coorElement = document.all["coor_"+uniqueId];
    }

    // store var in global context
    if (typeof window.googleMaps == "undefined") {
      window.googleMaps = [];
    }
    window.uniqueId = uniqueId;

    if (typeof mapElement != "undefined") {

      /*
       * GMap2 loads the Google Maps  base scripts
       * GSize initiatea an Object defining width and size passed to GMaps object
       *
       * heigth and width are defined by the backend
       */
      googleMaps[uniqueId] = new GMap2(mapElement, {'size' : new GSize(width, height)});
      var googleMap = googleMaps[uniqueId];

      if (showCoor === 1 && typeof coorElement != "undefined") {
        GEvent.addListener(googleMap, "click",
          function(googleMap, point) {
            if (point) {
              coorElement.innerHTML  = 'Latitude: ' + point.y +
                ' / ' + 'Longitude: ' + point.x;
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
        if(event.cancelable) event.preventDefault();
        if((event.detail || -event.wheelDelta) < 0)
          googleMap.zoomIn();
        else
          googleMap.zoomOut();
        return false;
      }
      GEvent.addDomListener(mapElement, "DOMMouseScroll", googleMap.wheelZoom);
      GEvent.addDomListener(mapElement, "mousewheel", googleMap.wheelZoom);

      if (centerLat > 0 && centerLng > 0 && centerZoom > 0) {
        centerMap(centerLat, centerLng, centerZoom, mapType);
      }
    }
  }
}

/**
 * sets the center point of the map to be shown
 *
 * @param float lat latitude of the center point
 * @param float lng longitude of the center point
 * @param integer zoom zoom level of the map
 * @param object mapType which type of map shall be displayed
 */
function centerMap(lat, lng, zoom, mapType) {
  var point = new GLatLng(parseFloat(lat),
                          parseFloat(lng));
  if (point) {
    googleMaps[uniqueId].setCenter(point, zoom);
  }
  googleMaps[uniqueId].setMapType(mapType);
}

function getMarkerPoint(lat, lng) {
  return new GLatLng(parseFloat(lat), parseFloat(lng));
}

function setMarker(point, text, color) {
  if (typeof point != "undefined") {

    //var marker = new GMarker(point, {icon:icon?});
    var marker = new GMarker(point);

    if (typeof text != "undefined" && text.length > 0) {
      GEvent.addListener(marker, markerAction, function () {
        marker.openInfoWindowHtml(text);
      });
    }
    googleMaps[uniqueId].addOverlay(marker);
    return marker;
  }
}

function rotateMarker(i) {
  var point = new GLatLng(parseFloat(markers[i][2]),
                          parseFloat(markers[i][3]));
  var marker = null;
  var uId = obj.currentTarget.id.replace(/map_/, '');
  if (typeof markers[i][1] != "undefined") {
    marker = setMarker(point, markers[i][1]);
  } else {
    marker = setMarker(point);
  }
  if (typeof marker  == "object") {
    googleMaps[uniqueId].setCenter(point);
    setTimeout(function() {
      googleMaps[uniqueId].closeInfoWindow();
      googleMaps[uniqueId].removeOverlay(marker);
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
  var colorValues = new Object;
  colorValues['orange']     = '#FF7D00';
  colorValues['blue']       = '#0000FF';
  colorValues['lightblue']  = '#8080FF';
  colorValues['brown']      = '#912D00';
  colorValues['green']      = '#00FF00';
  colorValues['lightgreen'] = '#80FF80';
  colorValues['grey']       = '#808080';
  colorValues['black']      = '#000000';
  colorValues['maroon']     = '#A51B00';
  colorValues['purple']     = '#800080';

  if (typeof colorValues[color] != "undefined" && points.length > 0) {
    var polyline = new GPolyline(points, color, 5);
    googleMaps[uniqueId].addOverlay(polyline);
  }
}

/**
 * restes the zoom level of displayed google map until every defined marker is in viewport
 *
 * @param array marker geodata of a marker
 */
function zoomIntoFocus(marker) {
  // do not zoom higher than the world view ;)
  if (googleMaps[uniqueId].getZoom() == 0) {
    return 0;
  }

  //var uId = obj.currentTarget.id.replace(/map_/, '');

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

    if (!zoomIntoFocus(marker)) {
      return 0;
    }
  }
  if (calN > 0 || calO < 0) {
    // zoom out
    googleMaps[uniqueId].zoomOut();

    if (!zoomIntoFocus(marker)) {
      return 0;
    }
  }
  return 1;
}
