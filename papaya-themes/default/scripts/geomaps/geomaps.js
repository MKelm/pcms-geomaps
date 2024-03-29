/**
* Geo maps for papaya CMS 5: Administration script
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

// Get coordinates depends on active api
// Note yahoo maps supports US only
// The api type is set by script call in admin_gmaps::getMarkerDialog
function getCoordinates(apiType, dialogId) {

  // required fields
  var addrFieldStreet = document.getElementById(dialogId+'_marker_addr_street');
  var addrFieldCity = document.getElementById(dialogId+'_marker_addr_city');

  if (typeof addrFieldStreet.value != "undefined" && addrFieldStreet.value != ""
      && typeof addrFieldCity.value != "undefined" && addrFieldCity.value != "") {
    var address = '';

    // additional fields
    var addrFieldHouse = document.getElementById(dialogId+'_marker_addr_house');
    if (typeof addrFieldHouse.value != "undefined" && addrFieldHouse.value != '') {
      address = address + addrFieldStreet.value + ' ' + addrFieldHouse.value + ', ';
    } else {
      address = address + addrFieldStreet.value + ', ';
    }
    var addrFieldZIP = document.getElementById(dialogId+'_marker_addr_zip');
    if (typeof addrFieldZIP.value != "undefined" && addrFieldZIP.value != '') {
      address = address + addrFieldZIP.value + ' ' + addrFieldCity.value + ', ';
    } else {
      address = address + addrFieldCity.value + ', ';
    }
    var addrFieldCountry = document.getElementById(dialogId+'_marker_addr_country');
    if (typeof addrFieldCountry.value != "undefined" && addrFieldCountry.value != '') {
      address = address + addrFieldCountry.value;
    }

    switch (apiType) {
      case 0:
        getCoordinatesByGoogleMaps(address, dialogId+"_marker_location");
        break;
      case 1:
        getCoordinatesByYahooMaps(address, dialogId+"_marker_location");
        break;
    }
  } else {
    alert("Required fields to get coordinates:\n-> Street, City\n\n"
      + "Optional fields:\n-> House number, ZIP code, Country");
  }
}

// Get coordinates by google maps api
function getCoordinatesByGoogleMaps(address, locationDlgFieldId) {
  // Google geocoder object used by getCoordinates
  if (typeof window.googleGeocoder == "undefined") {
    window.googleGeocoder = new GClientGeocoder();
  }
  googleGeocoder.getLatLng(
    address,
    function(point) {
      if (typeof point != "undefined" && point != null &&
          typeof point.y != "undefined" && point.y > 0 &&
          typeof point.x != "undefined" && point.x > 0) {
        document.getElementById(locationDlgFieldId).value  = point.y + "," + point.x;
      } else {
        alert('Address has not been found.');
      }
    }
  );
}

// Get coordinates by yahoo maps api
function getCoordinatesByYahooMaps(address, locationDlgFieldId) {
  var point = new YGeoPoint(address);
  if (typeof point.Lat != "undefined" && point.Lat > 0 &&
      typeof point.Lon != "undefined" && point.Lon > 0) {
    document.getElementById(locationDlgFieldId).value = point.Lat + "," + point.Lon;
  } else {
    alert('Address has not been found.');
  }
}
