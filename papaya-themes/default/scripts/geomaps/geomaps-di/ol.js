/**
* Geo Maps DI: Open Layers
*
* @copyright 2007-2010 by Martin Kelm - All rights reserved.
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

function initOpenLayersMap(centerLat, centerLng, centerZoom, mapType, uniqueId) {
  if (typeof uniqueId != "undefined") {

    // store var in global context
    if (typeof window.openLayersMaps == "undefined") {
      window.openLayersMaps = [];
      window.openLayersMarkers = [];
    }
    openLayersMaps[uniqueId] = new OpenLayers.Map(
      "map_"+uniqueId, {
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326")
      }
    );

    //if (typeControl) {
      openLayersMaps[uniqueId].addControl(new OpenLayers.Control.LayerSwitcher());
      var osm = new OpenLayers.Layer.OSM("Open Street Map");
      openLayersMaps[uniqueId].addLayers([osm]);
    //}

    openLayersMarkers[uniqueId] = new OpenLayers.Layer.Markers("Markers");
    openLayersMaps[uniqueId].addLayer(openLayersMarkers[uniqueId]);


    if (centerLat > -90 && centerLat < 90
        && centerLng > -180 && centerLng < 180 && centerZoom > 0) {
      centerMap(uniqueId, centerLat, centerLng, centerZoom, mapType);
    }

   /*
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
    */
  }
}

function centerMap(uniqueId, lat, lng, zoom, mapType) {
  var point = new OpenLayers.LonLat(parseFloat(lng), parseFloat(lat))
   .transform(
     new OpenLayers.Projection("EPSG:4326"),
     openLayersMaps[uniqueId].getProjectionObject()
   );

  if (point && zoom) {
    openLayersMaps[uniqueId].setCenter(point, zoom);
  } else if (point) {
    openLayersMaps[uniqueId].setCenter(point);
  }
  /*
  if (mapType) {
    yahooMaps[uniqueId].setMapType(mapType);
  }*/
}

function getMarkerPoint(lat, lng) {
  //return new YGeoPoint(parseFloat(lat), parseFloat(lng));
}

function getMarkerObject(uniqueId, point, markerIdx,
           customIconImage, customIconWidth, customIconHeight) {
  if (!point) {
    var point = new OpenLayers.LonLat(
      parseFloat(geoMarkers[uniqueId][markerIdx][3]),
      parseFloat(geoMarkers[uniqueId][markerIdx][2])
    ).transform(
      new OpenLayers.Projection("EPSG:4326"),
      openLayersMaps[uniqueId].getProjectionObject()
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

        var size = new OpenLayers.Size(customIconWidth, customIconHeight);
        var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
        geoMapsIcons[customIconImage] =
           new OpenLayers.Icon(customIconImage, size, offset);
      }

      var marker = new OpenLayers.Marker(point, geoMapsIcons[customIconImage]);
    } else {
      var marker = new OpenLayers.Marker(point);
    }

    // set marker with or without description text
    /*if (geoMarkers[uniqueId][markerIdx] && geoMarkers[uniqueId][markerIdx][1] &&
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
    }*/
    return marker;
  }
}

function setUpMarkers(uniqueId, clusterer) {
  // no clusterer support yet
  if (geoMarkerObjects[uniqueId].length > 0) {
    for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
      openLayersMarkers[uniqueId].addMarker(geoMarkerObjects[uniqueId][i]);
    }
  }
}

function removeMarkers(uniqueId) {
  // no clusterer support yet
  if (geoMarkerObjects[uniqueId].length > 0) {
    for (var i = 0; i < geoMarkerObjects[uniqueId].length; i++) {
      openLayersMarkers[uniqueId].removeMarker(geoMarkerObjects[uniqueId][i]);
    }
  }
}

function markerListenerEvent(uniqueId, markerIdx) { }

function rotateMarker(uniqueId, i) {
  var point = new YGeoPoint(
    geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]
  );
  if (typeof geoMarkerObjects[uniqueId][i] != "object") {
    geoMarkerObjects[uniqueId][i] = getMarkerObject(uniqueId, point, i);
  }
  if (typeof geoMarkerObjects[uniqueId][i] == "object") {
    /*
    yahooMaps[uniqueId].addOverlay(geoMarkerObjects[uniqueId][i]);
    yahooMaps[uniqueId].drawZoomAndCenter(point, yahooMaps[uniqueId].getZoomLevel());
    setTimeout(function() {
      geoMarkerObjects[uniqueId][i].closeSmartWindow();
      yahooMaps[uniqueId].removeOverlay(geoMarkerObjects[uniqueId][i]);
      if (i < geoMarkers[uniqueId].length-1) {
        rotateMarker(uniqueId, i+1);
      } else {
        rotateMarker(uniqueId, 0);
      }
    }, markerRotationTime);
    */
  }
}

function setPolyline(uniqueId, color, width) {
  var points = new Array();
  for (i = 0; i < geoMarkers[uniqueId].length; i++) {
    //points[i] = new YGeoPoint(geoMarkers[uniqueId][i][2], geoMarkers[uniqueId][i][3]);
  }
  //var polyline = new YPolyline(points, color, 5, 0.6);
  //yahooMaps[uniqueId].addOverlay(polyline);
}

/**
 * dummy, not implemented yet
 *
 * @param array marker geodata of a marker
 */
function correctZoomLevel(uniqueId) {
  // do not zoom higher than the world view ;)
  /*
  if (yahooMaps[uniqueId].getZoomLevel() == 12) {
    return 0;
  }
  return 1;
  */
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
