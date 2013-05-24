<?php
/**
* Geo maps box with Google Maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/

/**
* Basic class action box
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
* Geo maps box with Google Maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class actionbox_gmaps_google extends base_actionbox {

  var $preview = TRUE;

  var $editFields = array(
    'api_key' => array('API key', 'isAlphaNum', TRUE, 'input', 200, 
      'Get a Google Maps API key from http://www.google.com/apis/maps'),
    'noscript_text' => array('No script', 'isSomeText', FALSE, 
      'textarea', 2, '', 'Please activate JavaScript!'),
    'css_class' =>  array('CSS class', 'isAlpha', TRUE, 'input', 20, 
      'Each box has unique css ids', 'geoMap'),
    'mode_coor' => array('Coordinates mode', 'isNum', TRUE, 'combo',
      array(0 => 'Off', 1 => 'On'), 
      'Shows latitude and longitude information on click', 0),
    'Center',
    'center_lat' => array('Latitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 
      'input', 50, '', 0),
    'center_lng' => array('Longitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 
      'input', 50, '', 0),
    'center_zoom' => array('Zoom level', 'isNum', TRUE, 'input',
      2, '', 0),
    'map_type' => array('Default map type', 'isAlpha', TRUE, 'combo',
      array('G_MAP_TYPE' => 'Map', 'G_SATELLITE_TYPE' => 'Satellite', 
        'G_HYBRID_TYPE' => 'Hybrid'), '', 'G_MAP_TYPE'),
    'Controls',
    'ctrl_basic' => array('Navigation and zoom', 'isNum', TRUE, 'combo',
      array(0 => 'Navigation control and zoom bar', 
        1 => 'Navigation control and zoom buttons', 2 => 'Zoom buttons'), 
        '', 0),
    'ctrl_type' => array('Map type', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'),
      'Set default map type above', 'true'),
    'ctrl_overview' => array('Overview map', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'), '', 'true'),
    'ctrl_scale' => array('Scale information', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'), '', 'true'),
    'Markers',
    'marker_url' => array('Page url', 'isAlphaNumChar', FALSE, 'input', 200, 
      'Needs markers page as relative url, i.e.: markers.45.google (optional, leave blank to disable markers)'),
    'marker_folder' => array('Folder', 'isNum', TRUE, 'function',
      'callbackFoldersList'),
    'marker_mode' => array('Mode', 'isAlpha', TRUE, 'combo',
      array('static' => 'Static', 'rotation' => 'Rotation'), 
      'Rotation mode: Markers by coordinates are recommended', 'click'),
    'marker_action' => array('Mouse action', 'isAlpha', TRUE, 'combo',
      array('click' => 'Click', 'mouseover' => 'Mouse over'), '', 'click'),
    'marker_rotation' => array('Rotation time', 'isNum', TRUE, 'input',
      5, 'In seconds, for rotation mode', 5000),
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
      @$this->data['marker_folder']);
  }

  function getParsedData() {
  	$result = '';
  	$uniqueId = md5($this->paramName.microtime());
  	include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
  	$result .= sprintf('<div id="%s" class="%s">', 'map_'.$uniqueId, 
		  papaya_strings::escapeHTMLChars($this->data['css_class']));
  	$result .= $gmapsOutput->getGoogleMapsScript($this->data, $uniqueId,
  	  'folder_id', @$this->data['marker_folder']);
		$result .= '</div>';
		if ($this->data['mode_coor'] == 1) {
			$result .= sprintf('<div id="%s"> </div>', 'coor_'.$uniqueId);
		}
		return $result;
  }
  
}
?>
