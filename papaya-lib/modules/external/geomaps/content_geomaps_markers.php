<?php
/**
 * Marker data KML for xml http requests or downloads
 *
 * @copyright 2007-2013 by Martin Kelm - All rights reserved.
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
 * @author Martin Kelm <martinkelm@shrt.ws>
 */

/**
 * Basic class page module
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_content.php');

/**
 * Marker data KML for xml http requests or downloads
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@shrt.ws>
 */
class content_geomaps_markers extends base_content {

  var $paramName = 'gmps';

  var $cacheable = TRUE;

  var $editFields = array(
    'ressource_by_parameter' => array('Ressource By Parameter', 'isNum',
      TRUE, 'combo', array(0 => 'no', 1 => 'yes'),
      'The default ressource type is a folder.', 1),
    'default_folder_id' => array('Default folder', 'isNum', TRUE, 'function',
      'callbackFoldersList'),
    'Icon Decoration',
    'icon_decoration_image' => array('Dynamic Image', 'isNum', FALSE, 'function',
      'callbackDynamicImages', '', NULL),
    'icon_thumb_width' => array('Icon Thumb Width', 'isNoHTML', TRUE, 'input', 100, '', 0),
    'icon_thumb_height' => array('Icon Thumb Height', 'isNoHTML', TRUE, 'input', 100, '', 0)
  );

  /**
   * Get cache identifier related to given parameters.
   */
  function getCacheId() {
    return md5('_'.
      ((isset($this->params['ressource_type'])) ? $this->params['ressource_type'] : '').'_'.
      ((isset($this->params['ressource_id'])) ? $this->params['ressource_id'] : '').'_'.
      ((isset($this->params['ressource_lat'])) ? $this->params['ressource_lat'] : '').'_'.
      ((isset($this->params['ressource_lng'])) ? $this->params['ressource_lng'] : '').'_'.
      ((isset($this->params['base_kml'])) ? $this->params['base_kml'] : '')
    );
  }

  /**
   * Geo maps output object contains output class
   * @protected object $outputObj output_geomaps
   */
  var $outputObj = NULL;

  /**
   * Initialize output object once.
   * @return boolean status
   */
  function initOutputObject() {
    if (!is_object($this->outputObj) || !is_a($this->outputObj, 'output_geomaps')) {
      include_once(dirname(__FILE__).'/output_geomaps.php');
      $this->outputObj = &new output_geomaps();
      if (is_object($this->outputObj) && is_a($this->outputObj, 'output_geomaps')) {
        return TRUE;
      }
    } else {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Callback function to get combo box with folders.
   *
   * @param string $name element name
   * @param $element
   * @param integer $data folder id
   * @return string xml
   */
  function callbackFoldersList($name, $element, $data) {
    if ($this->initOutputObject() === TRUE) {
      return $this->outputObj->getFoldersComboBox($name, $element, $data,
        $this->paramName);
    }
    return '';
  }

  /**
   * Callback function to get combo box with dynamic image modules.
   *
   * @param string $name element name
   * @param $element
   * @param integer $data folder id
   * @return string xml
   */
  function callbackDynamicImages($name, $element, $data) {
    if ($this->initOutputObject() === TRUE) {
      return $this->outputObj->getDynamicImagesComboBox($name, $element, $data,
        $this->paramName);
    }
    return '';
  }

  /**
  * Get parsed data
  *
  * @access public
  * @return string xml
  */
  function getParsedData() {
    $xml = '';

    if ($this->initOutputObject() === TRUE) {
      if (function_exists('setDefaultData')) {
        $this->setDefaultData();
      }

      // load makers of specified folder or use other ressources to get markers data
       // you can implement more ressources (i.e. surfergroup) here
      $ressourceType = isset($this->params['ressource_type'])
        ? $this->params['ressource_type'] : '';
      $ressourceId = isset($this->params['ressource_id'])
        ? $this->params['ressource_id'] : '';
      switch ($ressourceType) {
      case 'spatial_polygon':
        if (!empty($ressourceId) &&
            !empty($this->params['ressource_lat']) &&
            !empty($this->params['ressource_lng'])) {
          $this->loadMarkerInPolygon(
            $ressourceId, // folder id
            $this->params['ressource_lat'],
            $this->params['ressource_lng']
          );
        }
        break;
      case 'folder':
      default:
        if (!($this->data['ressource_by_parameter'] == 1 && $ressourceId >= 0)) {
          $ressourceId = $this->data['default_folder_id'];
        }
        $this->outputObj->loadMarkers($ressourceId);
        if (!empty($this->data['icon_decoration_image'])) {
          $this->outputObj->decorateMarkerIcons(
            $this->data['icon_decoration_image'],
            (!empty($this->data['icon_thumb_width'])) ?
              $this->data['icon_thumb_width'] : NULL,
            (!empty($this->data['icon_thumb_height'])) ?
              $this->data['icon_thumb_height'] : NULL
          );
        }
      }

      // show markers xml if any exists
      if (count($this->outputObj->markers) > 0) {
        $xml = sprintf(
          '<markers base-kml="%d">'.LF,
          !empty($this->params['base_kml']) ? $this->params['base_kml'] : ''
        );

        // get base kml for internal ajax communication
        if (isset($this->params['base_kml']) && $this->params['base_kml'] == 1) {
          $xml .= $this->outputObj->getMarkersBaseKML(NULL);

        } else {
          // get full kml for regular outputs
          $xml .= $this->outputObj->getMarkersKML(NULL);
        }
        $xml .= '</markers>'.LF;
      }
    }

    return $xml;
  }

  /**
   * Loads a marker position if it is the point is within a spatial polygon id.
   *
   * @param folder id
   * @param float $latitude
   * @param float $longitude
   * @return boolean loaded?
   */
  function loadMarkerInPolygon($folderId, $latitude, $longitude) {

    if ($this->outputObj->checkSpatialPointInPolygon($folderId, $latitude, $longitude)) {

      $this->outputObj->markers[0] = array(
        'marker_id' => 0,
        'marker_folder' => 0,
        'marker_title' => 'Selection',
        'marker_desc' => NULL,
        'marker_icon' => NULL,
        'marker_lat' => $latitude,
        'marker_lng' => $longitude
      );
      $this->outputObj->markersCount = 1;
      return TRUE;
    }
    return FALSE;
  }

}
?>
