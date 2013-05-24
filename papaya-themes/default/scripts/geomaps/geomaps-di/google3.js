/**
* Geo Maps DI: Google Maps V3
*
* @copyright 2007-2013 by Martin Kelm - All rights reserved.
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
 * Initializes the Google Maps API V3 and triggers the rendering of the map
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
  
    if (document.getElementById) {
      var mapElement = document.getElementById("map_"+uniqueId);
    } else if (document.all) {
      var mapElement = document.all["map_"+uniqueId];
    }

    // store var in global context
    if (typeof window.googleMaps == "undefined") {
      window.googleMaps = [];
    }

    if (typeof mapElement != "undefined") {

      if (showCoor === 1) {
        /*GEvent.addListener(googleMap, "click",
          function(googleMap, point) {
            if (point) {
              coorModeAction(uniqueId, point.x, point.y);
            }
          }
        );*/
      }
      /*switch (basicControl) {
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
      }*/

      if (centerLat > -90 && centerLat < 90
          && centerLng > -180 && centerLng < 180 && centerZoom > 0) {
			  
		var mapOptions = {
		  zoom: centerZoom,
		  center: new google.maps.LatLng(centerLat, centerLng),
		  mapTypeId: mapType
		};
		googleMaps[uniqueId] = new google.maps.Map(
		  document.getElementById('map_' + uniqueId), mapOptions
		);
      }
    }
}

function getMarkerPoint(lat, lng) {
  return new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
}

function getMarkerObject(uniqueId, point, markerIdx,
           customIconImage, customIconWidth, customIconHeight) {
  if (!point) {
    var point = new google.maps.LatLng(
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
		geoMapsIcons[customIconImage] = new google.maps.Icon({
		  url: customIconImage,
		  size: new google.maps.Size(customIconWidth, customIconHeight) 
		});
      }
      var marker = new google.maps.Marker({
        position: point,
        icon: geoMapsIcons[customIconImage],
        flat: true
      });
    } else {
      var marker = new google.maps.Marker({
	    position: point
	  });
    }

    // set description text
    if (geoMarkers[uniqueId][markerIdx][1] &&
        geoMarkers[uniqueId][markerIdx][1].length > 0) {
	  var infoWindow = new google.maps.InfoWindow({
		position: point,
	    content: geoMarkers[uniqueId][markerIdx][1]
	  });
	  google.maps.event.addListener(marker, 'click', function(){
		infoWindow.open(googleMaps[uniqueId], marker);
	  });
    }
    return marker;
  }
}

function setUpMarkers(uniqueId, clusterer) {
  if (clusterer == 1) {
    /*if (typeof geoMapClustererStyles != "undefined") {
      clustererStyles = { styles: geoMapClustererStyles };
    } else {
      clustererStyles = null;
    }
    geoMarkerClusterers[uniqueId] = new MarkerClusterer(
      googleMaps[uniqueId], geoMarkerObjects[uniqueId], clustererStyles
    );*/
  } else {
    if (geoMarkerObjects[uniqueId].length > 0) {
      for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
        geoMarkerObjects[uniqueId][i].setMap(googleMaps[uniqueId]);
      }
    }
  }
}

function removeMarkers(uniqueId) {
  /*if (typeof geoMarkerClusterers[uniqueId] != "undefined") {
    geoMarkerClusterers[uniqueId].clearMarkers();
    geoMarkerClusterers[uniqueId] = null;
  } else {
    if (geoMarkerObjects[uniqueId].length > 0) {
      for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
        googleMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
      }
    }
  }*/
}

function markerZIndexProcessEvent(marker, b) {
  // i.e. inverse z-index
  //return -1 * GOverlay.getZIndex(marker.getPoint().lat());
  return 1; // by order
}

function markerListenerEvent(uniqueId, markerIdx) { }

function rotateMarker(uniqueId, i) {
  /*var point = new GLatLng(parseFloat(geoMarkers[uniqueId][i][2]),
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
  }*/
}

function setPolyline(uniqueId, color, width) {
  /*var points = new Array();
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
  }*/
}

function coorModeAction(uniqueId, lng, lat) {
  /*if (document.getElementById) {
    var coorElement = document.getElementById("coor_"+uniqueId);
  } else if (document.all) {
    var coorElement = document.all["coor_"+uniqueId];
  }
  if (typeof coorElement != "undefined") {
    coorElement.innerHTML = 'Latitude: '+lat+' / '+'Longitude: '+lng;
  }*/
}
