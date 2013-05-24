<?php
/**
* Geo maps box with Google Maps / Yahoo Maps for guestbooks
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
* Standard gmaps box class
*/
require_once('actbox_gmaps.php');

/**
* Basic class action box
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
* Geo maps box with Google Maps / Yahoo Maps for guestbooks
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class actionbox_gmaps_gb extends actionbox_gmaps {
  
  /**
  * Callback function to get folders
  */
  function callbackFoldersList($name, $element, $data) {    
  	// load guestbook folders and convert entries to markers
    include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
    $gmapsOutput->paramName = $this->paramName;
    $gmapsOutput->gbPluginInitialization($this);
    $gmapsOutput->gbPluginLoadBooks();
    return $gmapsOutput->getGbFoldersComboBox($name, 
      @$this->data['marker_folder']);
  }
  
  function getParsedData() {
  	// use another param name for guestbook folders
  	return parent::getParsedData('gb_id');
  }
  
}
?>
