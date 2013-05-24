<?php
/**
 * Geo maps box for Yahoo Maps
 *
 * @copyright 2007-2008 by Martin Kelm - All rights reserved.
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

/**
 * Basic class action box
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
 * Geo maps box for Yahoo Maps
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@idxsolutions.de>
 */
class actionbox_geomaps_yahoo extends base_actionbox {

  /**
   * List of fields to be configurable in the admin backend
   * @public array $editFields
   */
  var $editFields = array(
    'coor_mode' => array('Coordinates Mode', 'isNum', TRUE, 'yesno',
       NULL, 'Shows latitude and longitude information on click.', 0),
    'map_type' => array('Map Type', 'isAlpha', TRUE, 'combo',
      array(
        'YAHOO_MAP_REG' => 'Map',
        'YAHOO_MAP_SAT' => 'Satellite',
        'YAHOO_MAP_HYB' => 'Hybrid'
      ), NULL, 'G_MAP_TYPE'),
    'map_width' => array('Width', 'isNum', TRUE, 'input', 50, NULL, 640),
    'map_height' => array('Heigth', 'isNum', TRUE, 'input', 50, NULL, 480),
    'noscript_text' => array('No Script', 'isSomeText', FALSE, 'textarea', 2,
      NULL, 'Please activate JavaScript!'),
    'trip_planner' => array('Trip planner', 'isNoHTML', FALSE, 'input',
      200, 'Uses first marker to start at.', 'Trip planner'),

    'Static map',
    'static_map' => array('Active', 'isNum', TRUE, 'yesno',
      NULL, NULL, 0),
    'static_map_force' => array('Force', 'isNum', TRUE, 'yesno',
      NULL, NULL, 0),
    'static_map_type' => array('Output Type', 'isAlpha', TRUE, 'combo',
      array('png' => 'PNG', 'gif' => 'GIF'),  NULL, 'png'),
    'static_link_target' => array('Link Target', 'isAlphaNumChar', TRUE, 'combo',
      array('_self' => 'Default', '_blank' => 'New window'), NULL, '_self'),
    'static_img_alt_text' => array('Image Text', 'isSomeText', FALSE, 'input',
      200, NULL, 'Static Yahoo Map'),

    'Controls',
    'ctrl_pan' => array('Pan', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'), NULL, 'true'),
    'ctrl_zoom' => array('Zoom', 'isNum', TRUE, 'combo',
      array(0 => 'No',  1 => 'Small', 2 => 'Long'), NULL, 2),
    'ctrl_type' => array('Map Type', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'),
      'Set default map type above', 'true'),

    'Markers',
    'marker_description' => array('Show Description', 'isNum', TRUE, 'yesno',
      NULL, NULL, 1),
    'marker_pageid' => array('Page Id', 'isNum', FALSE, 'pageid',
      10, 'Optional, leave blank to show map without markers.', 0),
    'marker_viewmode' => array('View Mode', 'isAlpha', TRUE, 'function',
      'callbackViewModesList'),
    'marker_folder' => array('Folder', 'isNum', TRUE, 'function',
      'callbackFoldersList'),
    'marker_mode' => array('Mode', 'isAlpha', TRUE, 'combo',
      array('hide' => 'Hide', 'static' => 'Static', 'rotation' => 'Rotation'),
      'Note: Rotation mode opens description popups automatically', 'hide'),
    'marker_action' => array('Mouse Action', 'isAlpha', TRUE, 'combo',
      array('click' => 'Click', 'mouseover' => 'Mouse over'), NULL, 'click'),
    'marker_rotation' => array('Rotation Interval', 'isNum', TRUE, 'input',
      5, 'In seconds, for rotation mode', 5000),

    'Markers Polyline',
    'marker_polyline' => array('Active', 'isNum', TRUE, 'yesno',
       NULL, 'Needs at least two markers!', 0),
    'marker_polyline_color' => array('Color', 'isNoHTML', TRUE, 'function',
      'callbackPolylineColors'),
    'marker_polyline_size' => array('Size', 'isNum', TRUE, 'input', 2, NULL, 5),

    'Center',
    'center_first_marker' => array('Use First Marker', 'isNum', TRUE, 'yesno',
      NULL, NULL, 0),
    'center_lat' => array('Latitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input',
      50, NULL, 0),
    'center_lng' => array('Longitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input',
      50, NULL, 0),
    'center_zoom' => array('Zoom Level', 'isNum', TRUE, 'input',
      2, 'Use a value from 1 to 12.', 6),

  );

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
   * Callback function to get combo box with view modes.
   *
   * @param string $name element name
   * @param $element
   * @param integer $data folder id
   * @return string xml
   */
  function callbackViewModesList($name, $element, $data) {
    if ($this->initOutputObject() === TRUE) {
      return $this->outputObj->getViewModesComboBox($name, $element, $data,
        $this->paramName);
    }
    return '';
  }

  /**
   * Callback function to get combo box with polyline colors.
   *
   * @param string $name element name
   * @param $element
   * @param integer $data folder id
   * @return string xml
   */
  function callbackPolylineColors($name, $element, $data) {
    if ($this->initOutputObject() === TRUE) {
      return $this->outputObj->getPolylineColorsComboBox($name, $element, $data,
        $this->paramName);
    }
    return '';
  }

  /**
   * Get parsed data with map / script data.
   *
   * @return string xml
   */
  function getParsedData() {
    $result = '';

    if ($this->initOutputObject() === TRUE) {
      $this->setDefaultData();

      $result = $this->outputObj->getGeoMapXML($this->data, 1,
        array('folder_id' => $this->data['marker_folder']));
    }

    return $result;
  }

}
?>
