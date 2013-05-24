<?php
/**
* Marker data for xml http requests
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

/**
* Basic class page module
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_content.php');

/**
* Marker data for xml http requests
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class content_geomaps_markers extends base_content {

  var $paramName = 'gmaps';

  var $cacheable = TRUE;

  var $editFields = array(
    'default_folder' => array('Default folder', 'isNum', TRUE,
      'function', 'callbackFoldersList'),
    'folder_by_parameter' => array('Folder by parameter', 'isNum',
      TRUE, 'combo', array(0 => 'no', 1 => 'yes'), '', 1)
  );

  /**
   * Get cache identifier related to given folder id.
   */
  function getCacheId() {
    return md5('_'.$this->params['folder_id']);
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
  * Callback function to get folders
  */
  function callbackViewModesList($name, $element, $data) {
    if ($this->initOutputObject() === TRUE) {
      return $this->outputObj->getViewModesComboBox($name, $element, $data,
        $this->paramName);
    }
    return '';
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
  * Get parsed data
  *
  * @access public
  * @return string
  */
  function getParsedData() {
    $result = '';

    if ($this->initOutputObject() === TRUE) {

      $uniqueId = (isset($this->params['unique_id'])) ? $this->params['unique_id'] : NULL;

      // load makers of specified folder
      if ($this->data['folder_by_parameter'] == 1 &&
          isset($this->params['folder_id']) && $this->params['folder_id'] >= 0) {
        $this->outputObj->loadMarkers(@$this->params['folder_id']);
      } else {
        $this->outputObj->loadMarkers(@$this->data['default_folder']);
      }

      // show markers xml if any exists
      if (count($this->outputObj->markers) > 0) {
        $result = '<markers >'.LF;
        $result .= $this->outputObj->getMarkersKML(NULL, $uniqueId);
        $result .= '</markers>'.LF;
      }

    }

    return $result;
  }

}
?>
