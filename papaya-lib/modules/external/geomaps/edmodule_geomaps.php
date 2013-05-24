<?php
/**
 * Admin module for geo maps
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
 * @author Martin Kelm <martinkelm@idxsolutions.de>
 */

/**
 * Basic class modules
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_module.php');

/**
 * Admin module for geo maps
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@idxsolutions.de>
 */
class edmodule_geomaps extends base_module {

  /**
   * Module options
   *
   * @var array $pluginOptionFields
   */
  var $pluginOptionFields = array(
    'rpc_page_id' => array('RPC Page Id', 'isNum', TRUE, 'pageid', 200,
      'Set a RPC page id to enable callback links for external module packages.', 0),
    /*'rpc_view_mode' => array('RPC View Mode', 'isAlphaNum', TRUE, 'function',
      'callbackViewMode',
      'Set a RPC view mode corresponding to your view mode settings.', 'kml'),
      // @todo
      */
    'rpc_view_mode' => array('RPC View Mode', 'isAlphaNum', TRUE, 'input', 200,
      'Set a RPC view mode corresponding to your view mode settings.', 'kml'),
    'scripts_path' => array(
      'Scripts path', 'isAlphaNumChar', TRUE, 'input', 200, NULL, '/'),
    'default_folder_title' => array(
      'Default folder title', 'isAlphaNumChar', TRUE, 'input', 200, NULL, 'No folder title'),
  );

  /**
   * Permissions
   *
   * @var array $permissions
   */
  var $permissions = array(
    1 => 'View',
    2 => 'Manage markers',
    3 => 'Export Markers',
    4 => 'Manage keys',
    5 => 'Generate spatial locations'
  );

  /**
   * Execute module
   */
  function execModule() {
    if ($this->hasPerm(1, TRUE)) {

      // Get admin object
      include_once(dirname(__FILE__).'/admin_geomaps.php');
      $geoMaps = &new admin_geomaps();
      $geoMaps->paramName = $this->paramName;

      // Initialize, perform and get xml output
      $geoMaps->initialize(
        $this, $this->images, $this->msgs, $this->layout, $this->authUser
      );
      $geoMaps->execute();
      $geoMaps->getXML();
    }
  }

  /**
   * Callback to select an output filter for markers' rpc page
   *
   * @param string $name field name
   * @param string $field
   * @param mixed $data field data
   * @return string xml
   */
  function callbackViewMode($name, $element, $data) {
    if (!(isset($this->aliasObj) && is_object($this->aliasObj) &&
          is_a('papaya_alias_tree', $this->aliasObj))) {
      include_once(PAPAYA_INCLUDE_PATH.'system/papaya_alias_tree.php');
      $this->aliasObj = &new papaya_alias_tree($this);
      $this->aliasObj->loadViewModeList();
    }
    if (isset($this->aliasObj->viewModes) && is_array($this->aliasObj->viewModes) &&
        count($this->aliasObj->viewModes) > 0) {
      if (isset($this->aliasObj->viewModes) && is_array($this->aliasObj->viewModes) &&
          count($this->aliasObj->viewModes) > 0) {
        $result = sprintf('<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
          $this->paramName, $name);
        foreach ($this->aliasObj->viewModes as $filter) {
          $selected = ($filter['viewmode_ext'] == $data) ? ' selected="selected"' : '';
          $result .= sprintf('<option value="%s"%s>%s</option>'.LF,
            papaya_strings::escapeHTMLChars($filter['viewmode_ext']), $selected,
            papaya_strings::escapeHTMLChars($filter['viewmode_ext']));
        }
        $result .= '</select>'.LF;
        return $result;
      }
    }
    return '';
  }
}
?>
