/**
* Geo maps for papaya CMS 5: Administration script
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

googleGeocoder = null;

var button = document.createElement("input");
button.type = 'button';
button.className = 'dialogPopupButton';
button.value = 'Get';
button.onclick = gmapsGetCoordinates;

var addrField = document.getElementById("dlg_marker_address");
var cssWidth = '';
if (window.getComputedStyle) {
	cssWidth = parseInt(window.getComputedStyle(addrField, null).width);
} else if (this.editField.currentStyle) {
	cssWidth = parseInt(addrField.currentStyle.width);  
}
addrField.style.width = (cssWidth - 65)+'px';
var parentForm = addrField.parentNode;

inputTable = document.createElement('table');
inputTable.cellSpacing = 0;
inputTable.cellPadding = 0;
inputTable.style.width = cssWidth+'px';

var tb = document.createElement('tbody');
inputTable.appendChild(tb);
var tr = document.createElement('tr');
tb.appendChild(tr);

var td = document.createElement('td');
tr.appendChild(td);
td.appendChild(addrField);

var td = document.createElement('td');
tr.appendChild(td);
td.appendChild(button);

parentForm.appendChild(inputTable);

function gmapsGetCoordinates() {
	var apiType = document.getElementById("dlg_api_type").value;
	if (apiType == 0) {
		getCoordinatesByGoogleMaps(addrField.value, 
		  "dlg_marker_lat", "dlg_marker_lng");
	} else if (apiType == 1) {
		getCoordinatesByYahooMaps(addrField.value, 
		  "dlg_marker_lat", "dlg_marker_lng");
	}
}

function getCoordinatesByGoogleMaps(address, latDlgId, lngDlgId) {
	if (googleGeocoder == null) {
		googleGeocoder = new GClientGeocoder();
	}
	googleGeocoder.getLatLng(
		address,
		function(point) {
			document.getElementById(latDlgId).value	= point.y;
			document.getElementById(lngDlgId).value = point.x;
		}
	);
}

function getCoordinatesByYahooMaps(address, latDlgId, lngDlgId) {
	var point = new YGeoPoint(address);
  document.getElementById(latDlgId).value	= point.Lat;
	document.getElementById(lngDlgId).value = point.Lon;
}
