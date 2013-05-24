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
*/

var markers = null;
var markerAction = null;
var markerRotationTime = 5000;

function addMarkers(url, params) {
	xmlDocument = getMarkersXML(url, params);
	if (xmlDocument) {
	  parseMarkersXML(xmlDocument);
	}
}

function getMarkersXML(url, params) {
	var xmlRequest = false;
	var xmlDocument = false;
	if (window.XMLHttpRequest) { 
			xmlRequest = new XMLHttpRequest();
			xmlRequest.overrideMimeType('text/xml');
	} else if (window.ActiveXObject) { 
		try {
			xmlRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				xmlRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {}
		}
	}
  xmlRequest.open('POST', url, false);
  xmlRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xmlRequest.send(params);
	if (xmlRequest) {
		xmlDocument  = xmlRequest.responseXML;
	}
  return xmlDocument;
}

function parseMarkersXML(xmlDocument) {
	markers = new Array();
	var placemarkNodes = xmlDocument.getElementsByTagName('Placemark');
	for (i = 0; i < placemarkNodes.length; i++) {
		markers[i] = Array();
		markers[i][0] = 2;
		markers[i][1] = placemarkNodes[i].getElementsByTagName('description')[0].firstChild.data;
		var coordinates = placemarkNodes[i].getElementsByTagName('Point')[0].getElementsByTagName('coordinates')[0].firstChild.data.split(",");
		markers[i][2] = coordinates[0];
		markers[i][3] = coordinates[1];
	}
}

function getMarkers(action, mode, setRotationTime) {
	markerAction = action;
	if (mode == 'rotation') {
		if (typeof setRotationTime != "undefined" && 
		    setRotationTime > 0 && setRotationTime != markerRotationTime) {
			markerRotationTime = setRotationTime;
		}
		rotateMarker(0);
	} else {
		for (var i = 0; i < markers.length; i++) {
			setMarker(getMarkerPoint(markers[i][2], markers[i][3]), markers[i][1]);
		}
	}
}

function getPolyline(color, width) {
	if (markers.length > 0) {
		setPolyline(color);
	}
}
