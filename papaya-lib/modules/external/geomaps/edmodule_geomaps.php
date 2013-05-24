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
 */

/**
 * Basic class modules
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_module.php');

/**
 * Admin module for geo maps
 *
 * @package module_geomaps
 */
class edmodule_geomaps extends base_module {

  var $paramName = 'gmps';

  /**
   * Module options
   *
   * @var array $pluginOptionFields
   */
  var $pluginOptionFields = array(
    'scripts_path' => array(
      'Scripts path', 'isAlphaNumChar', TRUE, 'input', 200, NULL, '/'),
    'default_folder_title' => array(
      'Default folder title', 'isAlphaNumChar', TRUE, 'input', 200, NULL, 'No folder title'),
    'spatial_functions' => array(
      'Spatial Functions', 'isNum', TRUE, 'combo',
      array(0 => 'Disabled', 1 => 'Enabled'), 'Needs MySQL 4.1 or higher', 0)
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
}
?>
