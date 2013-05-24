<?php
/**
* Admin module for geo maps
*
* @package module_geomaps
* @author Martin Kelm <kelm@papaya-cms.com>
* @version $Id: edmodule_googemaps.php
*/

/**
* Basic class modules
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_module.php');

/**
* Admin module for geo maps
*
* @package module_geomaps
* @author Martin Kelm <kelm@papaya-cms.com>
*/
class edmodule_gmaps extends base_module {
  /**
  * Permissions
  * @var array $permissions
  */
  var $permissions = array(
    1 => 'View',
    2 => 'Manage markers',
    3 => 'Export Markers'
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
