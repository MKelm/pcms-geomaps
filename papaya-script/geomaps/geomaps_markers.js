/**
* Geo maps for papaya CMS 5: Markers script
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

function initMarkers(uniqueid) {
  // store var in global context
  if (typeof window.geoMarkers == "undefined") {
    window.geoMarkers = [];
  }
  geoMarkers[uniqueId] = new Array();
}

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

    if (1 == 2) { // for debugging purposes
      xmlRequest.open('GET', url+'?'+params, false);
      xmlRequest.send(null);
    } else { // default
      xmlRequest.open('POST', url, false);
      xmlRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xmlRequest.send(params);
    }

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
  var placemarkNodes = xmlData.getElementsByTagName("Placemark");

  // tmp vars
  var coordinates = null;
  var description = null;
  var iconImage = null;
  var iconWidth = null;
  var iconHeight = null;
  var tmp = null;

  // go through placemarks nodes and set them into the markers array
  for (i = 0; i < placemarkNodes.length; i++) {
    coordinates = Array();
    description = '';
    iconImage = '';

    // parse coordinates
    tmp = placemarkNodes[i].getElementsByTagName("Point")[0];
    if (tmp) {
      tmp = tmp.getElementsByTagName("coordinates")[0];
      if (tmp) {
        tmp = tmp.firstChild;
        if (tmp) {
          coordinates = tmp.data.split(",");
        }
      }
    }

    // set marker data
    if (coordinates.length >= 2) {
      geoMarkers[uniqueId][i] = Array();
      geoMarkers[uniqueId][i][0] = 2;

      // parse description
      tmp = placemarkNodes[i].getElementsByTagName("description")[0];
      if (tmp && tmp.hasChildNodes()) {
        description = tmp.firstChild.data;
      }
      if (description && description.length > 0) {
        geoMarkers[uniqueId][i][1] = description;
      }

      geoMarkers[uniqueId][i][2] = coordinates[1]; // Latitude
      geoMarkers[uniqueId][i][3] = coordinates[0]; // Longitude


      // parse style / custom icon
      tmp = placemarkNodes[i].getElementsByTagName("href")[0];
      if (tmp && tmp.hasChildNodes() && tmp.childNodes[0].data != '') {
        iconImage = tmp.childNodes[0].data;
      }
      tmp = placemarkNodes[i].getElementsByTagName("size")[0];
      if (tmp) {
        iconWidth = tmp.getAttribute('x');
        iconHeight = tmp.getAttribute('y');
      }
      if (iconImage && iconImage.length > 0 && iconWidth > 0 && iconHeight > 0) {
        geoMarkers[uniqueId][i][4] = new Array (iconImage, iconWidth, iconHeight); // icon
      } else {
        geoMarkers[uniqueId][i][4] = null;
      }

      geoMarkers[uniqueId][i][5] = null; // marker object
    }
  }
}

function getMarkers(action, mode, setRotationTime, showDescription, zoomIntoFocus, color) {
  // store var in global context
  window.markerAction = action;
  description = '';

  if (mode == 'rotation') {
    if (typeof setRotationTime != "undefined" && setRotationTime > 0) {
      if (typeof markerRotationTime == "undefined") {
        // store var in global context
        window.markerRotationTime = setRotationTime;
      } else if (setRotationTime != markerRotationTime) {
        // store var in global context
        window.markerRotationTime = setRotationTime;
      }
      rotateMarker(0);
    }
  } else {
    if (typeof geoMarkers[uniqueId] != "undefined") {
      for (var i = 0; i < geoMarkers[uniqueId].length; i++) {


        if (!(typeof showDescription != "undefined" && showDescription > 0)) {
          geoMarkers[uniqueId][i][1] = null;
        }
        geoMarkers[uniqueId][i][5] = setMarker(false, i);
      }

      if (typeof zoomIntoFocus != "undefined" && zoomIntoFocus > 0 &&
          geoMarkers[uniqueId].length > 0) {
        correctZoomLevel();
      }
    }
  }
}

function getPolyline(color, width) {
  if (typeof geoMarkers[uniqueId] != "undefined" && geoMarkers[uniqueId].length > 0) {
    setPolyline(color);
  }
}

/**
 * walks through veuery defined marker and resets the zoom level
 *
 * @see zoomIntoFocus() in Google / Yahoo Maps script
 */
function correctZoomLevel() {
  if (typeof geoMarkers[uniqueId] != "undefined") {
    var marker = [];

    for (var i = 0; i < geoMarkers[uniqueId].length; i++) {
      marker[0] = geoMarkers[uniqueId][i][2]; // latitude
      marker[1] = geoMarkers[uniqueId][i][3]; // longitude
      zoomIntoFocus(marker);
    }
  }
}
