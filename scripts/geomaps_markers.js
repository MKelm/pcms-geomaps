/**
* Geo maps for papaya CMS 5: Markers script
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

function addMarkers(url, params) {
  xmlDocument = getMarkersXML(url, params);
  if (xmlDocument) {
    parseMarkersXML(xmlDocument);
  }
}

function getMarkersXML(url, params) {

  params = params.replace(/&amp;/g, '&');

  // xml http request for Mozilla or IE7
  if (window.XMLHttpRequest) {
    xmlRequest = new XMLHttpRequest();
    if (xmlRequest.overrideMimeType) {
      xmlRequest.overrideMimeType('text/xml');
    }
  // xml http request for old IE browsers
  } else if (window.ActiveXObject) {
    try {
      xmlRequest = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlRequest = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {}
    }
  }
  // Request xml data
  var xmlData = '';
  if (typeof xmlRequest != "undefined") {
    xmlRequest.open('POST', url, false);
    xmlRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xmlRequest.send(params);
    if (xmlRequest.readyState == 4) {
      if (xmlRequest.status == 200) {
        xmlData = xmlRequest.responseXML;
      }
    }
  }
  return xmlData;
}

function parseMarkersXML(xmlData) {
  // store var in global context
  window.markers = new Array();
  var placemarkNodes = xmlData.getElementsByTagName("Placemark");
  // go through placemarks nodes and set them into the markers array
  for (i = 0; i < placemarkNodes.length; i++) {
    var coordinates = Array();
    var description = '';
    // parse coordinates
    var tmp = placemarkNodes[i].getElementsByTagName("Point")[0];
    if (tmp) {
      tmp = tmp.getElementsByTagName("coordinates")[0];
      if (tmp) {
        tmp = tmp.firstChild;
        if (tmp) {
          coordinates = tmp.data.split(",");
        }
      }
    }
    // parse description
    tmp = placemarkNodes[i].getElementsByTagName("description")[0];
    if (tmp && tmp.hasChildNodes()) {
      description = tmp.firstChild.data;
    }
    // set marker
    if (coordinates.length >= 2) {
      markers[i] = Array();
      markers[i][0] = 2;
      markers[i][1] = description;
      markers[i][2] = coordinates[1]; // Latitude
      markers[i][3] = coordinates[0]; // Longitude
    }
  }
}

function getMarkers(action, mode, setRotationTime, showDescription, zoomIntoFocus, color) {
  // store var in global context
  window.markerAction = action;
  description = '';
  if (mode == 'rotation') {
    if (typeof setRotationTime != "undefined" &&
        setRotationTime > 0 && setRotationTime != markerRotationTime) {
      // store var in global context
      window.markerRotationTime = setRotationTime;
    }
    rotateMarker(0);
  } else {
    if (typeof markers != "undefined") {
      for (var i = 0; i < markers.length; i++) {

        if (typeof showDescription != "undefined" && showDescription > 0) {
          var description = markers[i][1];
        }

        setMarker(
          getMarkerPoint(markers[i][2], markers[i][3]),
          description,
          color
        );
      }

      if (typeof zoomIntoFocus != "undefined" && zoomIntoFocus > 0 &&
          markers.length > 1) {
        correctZoomLevel();
      }
    }
  }
}

function getPolyline(color, width) {
  if (typeof markers != "undefined" && markers.length > 0) {
    setPolyline(color);
  }
}

/**
 * walks through veuery defined marker and resets the zoom level
 *
 * @see zoomIntoFocus() in Google / Yahoo Maps script
 */
function correctZoomLevel() {
  if (typeof markers != "undefined") {
    var marker = [];

    for (var i = 0; i < markers.length; i++) {
      marker[0]= markers[i][2]; // latitude
      marker[1]= markers[i][3]; // longitude
      zoomIntoFocus(marker);
    }
  }
}
