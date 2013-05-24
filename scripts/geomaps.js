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

// Google geocoder object used by getCoordinates
var googleGeocoder = null;

// Get coordinates depends on active api
// Note yahoo maps supports US only
// The api type is set by script call in admin_gmaps::getMarkerDialog
function getCoordinates(dialogId, addressDlgName, apiType) {
  var addrField = document.getElementById(dialogId+'_'+addressDlgName);

  if (addrField.value != "undefined" && addrField.value != "") {
    switch (apiType) {
      case 0:
        getCoordinatesByGoogleMaps(addrField.value,
          dialogId+"_marker_lat", dialogId+"_marker_lng");
        break;
      case 1:
        getCoordinatesByYahooMaps(addrField.value,
          dialogId+"_marker_lat", dialogId+"_marker_lng");
        break;
    }
  }
}

// Get coordinates by google maps api
function getCoordinatesByGoogleMaps(address, latDlgId, lngDlgId) {
  if (googleGeocoder == null) {
    googleGeocoder = new GClientGeocoder();
  }
  googleGeocoder.getLatLng(
    address,
    function(point) {
      var pointY = point.y;
      var pointX = point.x;

      if (pointY != "undefined" && pointY > 0 &&
          pointX != "undefined" && pointX > 0) {
        document.getElementById(latDlgId).value  = point.y;
        document.getElementById(lngDlgId).value = point.x;
      } else {
        alert('Address has not been found.');
      }
    }
  );
}

// Get coordinates by yahoo maps api
function getCoordinatesByYahooMaps(address, latDlgId, lngDlgId) {
  var point = new YGeoPoint(address);
  var pointlat = point.Lat;
  var pointLat = point.Lon;

  if (pointLat != "undefined" && pointLat > 0 &&
      pointLon != "undefined" && pointLat > 0) {
    document.getElementById(latDlgId).value  = point.Lat;
    document.getElementById(lngDlgId).value = point.Lon;
  } else {
    alert('Address has not been found.');
  }
}
