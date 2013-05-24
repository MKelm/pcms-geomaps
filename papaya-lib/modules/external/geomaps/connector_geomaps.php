<?php
/**
 * Connector for Geo Maps
 *
 * @copyright 2007-2009 by Martin Kelm - All rights reserved.
 * @link http://www.idxsolutions.de
 * @licence GNU General Public Licence (GPL) 2 http://www.gnu.org/copyleft/gpl.html
 *
 * You can redistribute and/or modify this script under the terms of the GNU General
 * Public License (GPL) version 2, provided that the copyright and license notes,
 * including these lines, remain unmodified. This script is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package module_geomaps
 * @author Martin Kelm <kelm@idxsolutions.de>
 */

/**
 * Basic plugin class
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_plugin.php');

/**
 * Connector for Geo Maps
 *
 * Usage:
 * include_once(PAPAYA_INCLUDE_PATH.'system/base_pluginloader.php');
 * $uidObject = &base_pluginloader::getPluginInstance(
 *   'aa51f106beffab7ed130cc00c28c80f9',
 *   $this
 * );
 *
 * @package module_geomaps
 * @author Martin Kelm <kelm@idxsolutions.de>
 */
class connector_geomaps extends base_plugin {

  /**
   * Constructor (overrides base_plugin's)
   */
  function __construct(&$aOwner, $paramName = 'gmps') {
    parent::__construct($aOwner, $paramName);
  }

  /**
   * Get geo maps base object as static reference.
   *
   * @return static reference $obj base_geomaps
   */
  function &getBaseObject() {
    static $baseGeoMaps;
    if (!isset($baseGeoMaps) && !is_object($baseGeoMaps)) {
      include_once(dirname(__FILE__).'/base_geomaps.php');
      $baseGeoMaps = new base_geomaps();
    }
    return $baseGeoMaps;
  }

  /**
   * Get combo box for geo maps folders.
   *
   * @param string $name content of the node attribute 'name'
   * @param integer $currentFolder identifies the currently selected option
   * @param array $folders if set it will override the folders defined on object level
   * @return string XHTML code representing a XHTML drop down menu
   */
  function getFoldersComboBox($name, $element, $data, $paramName) {
    $baseObj = &$this->getBaseObject();
    return $baseObj->getFoldersComboBox($name, $element, $data, $paramName);
  }

  /**
   * Add a new marker.
   *
   * @param integer $folderId Markers folder
   * @param string $title Title
   * @param integer $lat Latitude
   * @param integer $lng Longitude
   * @param string $description Description (optional)
   * @param string $street Street (optional)
   * @param string $house House number (optional)
   * @param string $zip ZIP code (optional)
   * @param string $city City name (optional)
   * @param string $country Country name (optional)
   * @param boolean $new Set a new marker (optional)
   * @return boolean|integer Error status FALSE or marker id
   */
  function addMarker($folderId, $title, $lat, $lng,
                     $description = NULL, $street = NULL, $house = NULL,
                     $zip = NULL, $city = NULL, $country = NULL) {
    $baseObj = &$this->getBaseObject();
    return $baseObj->setMarker($folderId, $title, $lat, $lng, $description,
      $street, $house, $zip, $city, $country, TRUE, NULL);
  }

  /**
   * Get a single marker by id
   *
   * @param integer $markerId
   * @param integer $folderId Additional folder id condition
   * @return integer|NULL marker id or nothing
   */
  function getMarkerById($markerId, $folderId = TRUE) {
    $baseObj = &$this->getBaseObject();
    if ($baseObj->loadMarker($markerId, TRUE, $folderId)) {
      return $baseObj->markers[$markerId];
    }
    return NULL;
  }

  /**
   * Uses coordinates to validate this position inside of a given polygon.
   *
   * @param float $latitude i.e. 49.488155742041045
   * @param float $longitude i.e. 8.465939998495742
   * @param integer $folderId unique folder id
   * @return boolean point is within polygon
   */
  function checkSpatialPointInPolygon($latitude, $longitude, $folderId) {
    $baseObj = &$this->getBaseObject();
    return $baseObj->checkSpatialPointInPolygon($latitude, $longitude, $folderId);
  }

  /**
   * Get a page link to the markers rpc page for external module packages.
   *
   * @param string $ressourceType
   * @param mixed $ressourceId
   * @return string
   */
  function getRPCPageLink($ressourceType = NULL, $ressourceId = NULL) {
    $baseObj = &$this->getBaseObject();
    $params = array();
    if ($ressourceType !== NULL) {
      $params['ressource_type'] = $ressourceType;
    }
    if ($ressourceId !== NULL) {
      $params['ressource_id'] = $ressourceId;
    }
    $pageId = $baseObj->getOption('rpc_page_id', 0);
    if ($pageId > 0) {
      $viewMode = $baseObj->getOption('rpc_view_mode', 0);
      return $this->getWebLink(
        $pageId, NULL, $viewMode, $params, $this->paramName
      );
    }
    return '';
  }

}
?>
