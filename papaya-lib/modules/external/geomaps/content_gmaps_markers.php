<?php
/**
* Marker data for xml http requests as kml
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/

/**
* Basic class page module
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_content.php');

/**
* Marker data for xml http requests as kml
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class content_gmaps_markers extends base_content {

  var $paramName = 'gmaps';
  
  var $cacheable = FALSE;
  
  var $editFields = array(
    'default_folder' => array('Default folder', 'isNum', TRUE, 
      'function', 'callbackFoldersList'),
    'folder_by_parameter' => array('Folder by parameter', 'isNum', 
      TRUE, 'combo', array(0 => 'no', 1 => 'yes'), '', 1), 
    'support_guestbook' => array('Support guestbook', 'isNum', 
      TRUE, 'combo', array(0 => 'no', 1 => 'yes'), '', 1)
  );
  
  
  /**
  * Callback function to get folders
  */
  function callbackFoldersList($name, $element, $data) {    
    include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
    $gmapsOutput->paramName = $this->paramName;
    return $gmapsOutput->getFoldersComboBox($name,
      @$this->data['default_folder']);
  }

  /**
  * Get parsed data
  *
  * @access public
  * @return string
  */
  function getParsedData() {
  	$result = '';
		include_once('output_gmaps.php');
		$gmapsOutput = new output_gmaps();
		$gmapsOutput->initialize();
		// load makers of specified folder
		if ($this->data['folder_by_parameter'] == 1 &&
		    isset($this->params['folder_id']) && $this->params['folder_id'] >= 0) {
			$gmapsOutput->loadMarkers(@$this->params['folder_id']);
		// guestbook plugin support
		} elseif ($this->data['support_guestbook'] == 1 && 
		          $this->params['gb_id'] && $this->params['gb_id'] >= 0) {
			if ($gmapsOutput->gbPluginInitialization($this)) {
				$gmapsOutput->gbPluginLoadEntriesToMarkers(
					$this->params['gb_id']);
			}
		} else {
			$gmapsOutput->loadMarkers(@$this->data['default_folder']);
		}
		// show markers xml if any exists
		if (count($gmapsOutput->markers) > 0) {
			$result = '<markers>'.LF;
			$result .= $gmapsOutput->getMarkersKML();
			$result .= '</markers>'.LF;
		}
    return $result;
  }

}

?>
