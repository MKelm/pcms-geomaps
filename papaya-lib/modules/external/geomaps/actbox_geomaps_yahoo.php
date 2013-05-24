<?php
/**
 * Geo maps box for Yahoo Maps
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
 * Basic class action box
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
 * Geo maps box for Yahoo Maps
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@shrt.ws>
 */
class actionbox_geomaps_yahoo extends base_actionbox {

  /**
   * List of fields to be configurable in the admin backend
   * @public array $editFields
   */
  var $editFields = array(
    'base_coor_mode' => array('Coordinates Mode', 'isNum', TRUE, 'yesno',
       NULL, 'Shows latitude and longitude information on click.', 0),
    'base_links_target' => array('Links Target', 'isAlphaNumChar', TRUE, 'combo',
      array('_self' => 'Default', '_blank' => 'New window'), NULL, '_self'),
    'base_no_script_text' => array('No Script Text', 'isSomeText', FALSE,
      'textarea', 2, NULL, 'Please activate JavaScript!'),

    'Settings',
    'stg_type' => array('Type', 'isAlphaNum', TRUE, 'combo',
      array(
        'YAHOO_MAP_REG' => 'Map',
        'YAHOO_MAP_SAT' => 'Satellite',
        'YAHOO_MAP_HYB' => 'Hybrid'
      ), NULL, 'YAHOO_MAP_REG'
    ),
    'stg_width' => array('Width', 'isNum', TRUE, 'input', 50, NULL, 320),
    'stg_height' => array('Heigth', 'isNum', TRUE, 'input', 50, NULL, 240),
    'stg_zoom' => array('Zoom', 'isNum', TRUE, 'input', 2,
      'Use a value from 1 to 17.', 6),

    'Controls',
    'stg_ctrl_type' => array('Map Type', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'),
      'Set a default map type in settings.', 'true'),
    'stg_ctrl_pan' => array('Pan', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'), NULL, 'true'),
    'stg_ctrl_zoom' => array('Zoom', 'isNum', TRUE, 'combo',
      array(0 => 'No',  1 => 'Small', 2 => 'Long'), NULL, 2),

    'Center',
    'stg_center_mode' => array('Mode', 'isAlpha', TRUE, 'combo',
      array(
        'default' => 'Use settings',
        'first_marker' => 'Use first marker',
        'all_markers' => 'Use markers\' center'
      ), 'Use markers\' center: Check zoom level to get all markers into focus!',
      'default'
    ),
    'stg_center_lat' => array('Latitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input',
      50, NULL, 0),
    'stg_center_lng' => array('Longitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input',
      50, NULL, 0),

    'Markers KML',
    'mrk_page_id' => array(
      'Page Id', 'isNum', FALSE, 'pageid', 10,
      'Set a page id greater than zero to use an extern page to load
      markers\' data by using a xmlhttp request.',
      0
    ),
    'mrk_view_mode' => array('View Mode', 'isAlpha', TRUE, 'function',
      'callbackViewModesList', 'Select a KML view mode.'),
    'mrk_folder_id' => array('Folder', 'isNum', TRUE, 'function',
      'callbackFoldersList'),

    'Markers',
    'mrk_active' => array('Active', 'isNum', TRUE, 'yesno',
      NULL, 'Needs a valid KML data, see above.', 0),
    'mrk_show_description' => array('Show Description', 'isNum', TRUE, 'yesno',
      NULL, NULL, 0),
    'mrk_mouse_desc_action' => array('Description Action', 'isAlpha',
      TRUE, 'combo', array(
        'click' => 'Open by click',
        'mouseover' => 'Open by mouse over'
      ),
      'How do you wanna to open descriptions?', 'click'
    ),
    'mrk_mode' => array('Mode', 'isAlpha', TRUE, 'combo',
      array(
        'hide' => 'Hide',
        'static' => 'Default',
        'rotation' => 'Rotation'
      ), 'Note: The rotation mode opens descriptions automatically.', 'hide'
    ),
    'mrk_rotation' => array('Rotation Interval', 'isNum', TRUE, 'input',
      5, 'In seconds, for rotation mode', 5000),

    'Polyline',
    'mrk_polyline_active' => array('Active', 'isNum', TRUE, 'yesno',
       NULL, 'Needs at least two markers!', 0),
    'mrk_polyline_color' => array('Color', 'isNoHTML', TRUE, 'function',
      'callbackPolylineColors'),
    'mrk_polyline_size' => array('Size', 'isNum', TRUE, 'input', 2, NULL, 5),

    'Trip Planner',
    'stg_trippl_active' => array('Active', 'isNum', TRUE, 'yesno',
      NULL, 'Needs one marker as start point.', 0),
    'stg_trippl_caption' => array('Caption', 'isNoHTML', FALSE, 'input',
      200, NULL, 'Trip planner'),

    'Static map',
    'stc_active' => array('Active', 'isNum', TRUE, 'yesno', NULL, NULL, 0),
    'stc_force' => array('Force', 'isNum', TRUE, 'yesno',
      NULL, 'Show static map image only.', 0),
    'stc_type' => array('Output type', 'isAlpha', TRUE, 'combo',
      array(
        'png' => 'PNG',
        'gif' => 'GIF'
      ), NULL, 'png'
    ),
    'stc_alternative_text' => array('Alternative Text', 'isSomeText', FALSE,
      'input', 200, NULL, '')
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
    $xml = '';

    if ($this->initOutputObject() === TRUE) {
      if (function_exists('setDefaultData')) {
        $this->setDefaultData();
      }

      // set base data
      $baseData = $this->outputObj->setBaseData(
        1, // yahoo api
        $this->data['base_coor_mode'],
        $this->data['base_no_script_text'],
        $this->data['base_links_target']
      );

      // set settings data
      $optionsData = $this->outputObj->setSettingsData(
        $this->data['stg_type'],
        $this->data['stg_width'],
        $this->data['stg_height'],
        array(
          'type' => $this->data['stg_ctrl_type'],
          'pan' => $this->data['stg_ctrl_pan'],
          'zoom' => $this->data['stg_ctrl_zoom']
        ),
        $this->data['stg_trippl_active'],
        $this->data['stg_trippl_caption'],
        $this->data['stg_zoom'],
        $this->data['stg_center_lat'],
        $this->data['stg_center_lng'],
        $this->data['stg_center_mode'],
        $this->data['mrk_folder_id']
      );

      // set markers data
      if ($this->data['mrk_active'] == 1) {
        $markersData = $this->outputObj->setMarkersData(
          $this->data['mrk_page_id'],
          $this->data['mrk_view_mode'],
          'folder',
          $this->data['mrk_folder_id'],
          'red', // TODO dynamic markers color
          $this->data['mrk_mode'],
          $this->data['mrk_rotation'],
          $this->data['mrk_show_description'],
          $this->data['mrk_mouse_desc_action'],
          NULL,
          $this->data['mrk_polyline_active'],
          $this->data['mrk_polyline_color'],
          $this->data['mrk_polyline_size']
        );
      } else {
        $markersData = TRUE;
      }

      // set static data
      if ($this->data['stc_active'] == 1) {
        $staticData = $this->outputObj->setStaticData(
          $this->data['stc_force'],
          $this->data['stc_type'],
          $this->data['stc_alternative_text'],
          $this->data['stc_markers_color'],
          $this->data['stc_markers_size'],
          $this->data['stc_markers_decoration']
        );
      } else {
        $staticData = TRUE;
      }

      if ($baseData && $optionsData && $markersData && $staticData) {
        // get xmls
        $xml = $this->outputObj->getBaseXml();
        $xml .= $this->outputObj->getSettingsXml();
        if ($this->data['mrk_active'] == 1) {
          $xml .= $this->outputObj->getMarkersXml();
        }
        $xml .= $this->outputObj->getPermaLinkXml();
        $xml .= $this->outputObj->getTripPlannerLinkXml();
        if ($this->data['stc_active'] == 1) {
          $xml .= $this->outputObj->getStaticXml();
        }
      }
    }

    return sprintf('<geo-map>%s</geo-map>'.LF, $xml);
  }

}
?>
