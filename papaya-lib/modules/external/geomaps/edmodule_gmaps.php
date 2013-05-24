<?php
/**
* Admin module for geo maps
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
* Basic class modules
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_module.php');

/**
* Admin module for geo maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class edmodule_gmaps extends base_module {
  /**
  * Permissions
  * @var array $permissions
  */
  var $permissions = array(
    1 => 'View',
    2 => 'Manage markers',
    3 => 'Export Markers',
    4 => 'Manage keys'
  );

  /**
  * Execute module
  *
  * @access public
  */
  function execModule() {
    if ($this->hasPerm(1, TRUE)) {
      $path = dirname(__FILE__);
      include_once($path.'/papaya_gmaps.php');
      $gmaps = &new papaya_gmaps();
      $gmaps->module = &$this;
      $gmaps->images = &$this->images;
      $gmaps->msgs = &$this->msgs;
      $gmaps->layout = &$this->layout;
      $gmaps->authUser = &$this->authUser;
      $gmaps->initialize();
      $gmaps->execute();
      $gmaps->getXML();
    }
  }
}

?>
