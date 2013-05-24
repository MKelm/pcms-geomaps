<?php
/**
* Geo maps box with Yahoo Maps for guestbooks
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/

/**
* Basic class action box
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
* Geo maps box with Yahoo Maps for guestbooks
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class actionbox_gmaps_yahoo_gb extends base_actionbox {

  var $preview = TRUE;

  var $editFields = array(
    'api_id' => array('API id', 'isAlphaNum', TRUE, 'input', 200, 
      'Get a Yahoo Maps API id from http://search.yahooapis.com/webservices/register_application',
      'YahooDemo'),
    'noscript_text' => array('No script', 'isSomeText', FALSE, 
      'textarea', 2, '', 'Please activate JavaScript!'),
    'css_class' =>  array('CSS class', 'isAlpha', TRUE, 'input', 20, 
      'Each box has unique css ids', 'geoMap'),
    'Center',
    'center_lat' => array('Latitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 
      'input', 50, '', 0),
    'center_lng' => array('Longitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 
      'input', 50, '', 0),
    'center_zoom' => array('Zoom level', 'isNum', TRUE, 'input',
      2, 'Range: 17-0', 17),
    'map_type' => array('Default map type', 'isAlpha', TRUE, 'combo',
      array('YAHOO_MAP_REG' => 'Map', 'YAHOO_MAP_SAT' => 'Satellite', 
        'YAHOO_MAP_HYB' => 'Hybrid'), '', 'G_MAP_TYPE'),
    'Controls',
    'ctrl_pan' => array('Pan', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'), '', 'true'),
    'ctrl_zoom' => array('Zoom', 'isNum', TRUE, 'combo',
      array(0 => 'No',  1 => 'Small', 2 => 'Long'), '', 2),
    'ctrl_type' => array('Map type', 'isAlpha', TRUE, 'combo',
      array('true' => 'Yes', 'false' => 'No'),
      'Set default map type above', 'true'),
    'Markers',
    'marker_url' => array('Page url', 'isAlphaNumChar', FALSE, 'input', 200, 
      'Needs markers page as relative url, i.e.: markers.45.yahoo (optional, leave blank to disable markers)'),
    'marker_gb_folder' => array('Guestbook folder by plugin', 'isNum', TRUE, 
      'function', 'callbackGbFoldersList', 'Guestbook entries -> Markers'),
    'marker_mode' => array('Mode', 'isAlpha', TRUE, 'combo',
      array('static' => 'Static', 'rotation' => 'Rotation'), '', 'click'),
    'marker_action' => array('Mouse action', 'isAlpha', TRUE, 'combo',
      array('click' => 'Click', 'mouseover' => 'Mouse over'), '', 'click'),
    'marker_rotation' => array('Rotation time', 'isNum', TRUE, 'input',
      5, 'In seconds, for rotation mode', 5000),
  );
  
  /**
  * Callback function to get folders
  */
  function callbackGbFoldersList($name, $element, $data) {    
    include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
    $gmapsOutput->paramName = $this->paramName;
    $gmapsOutput->gbPluginInitialization($this);
    $gmapsOutput->gbPluginLoadBooks();
    return $gmapsOutput->getGbFoldersComboBox($name, 
      @$this->data['marker_gb_folder']);
  }

  function getParsedData() {
  	$result = '';
  	$uniqueId = md5($this->paramName.microtime());
  	include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
  	$result .= sprintf('<div id="%s" class="%s">', 'map_'.$uniqueId, 
		  papaya_strings::escapeHTMLChars($this->data['css_class']));
  	$result .= $gmapsOutput->getYahooMapsScript($this->data, $uniqueId,
  	  'gb_id', @$this->data['marker_gb_folder']);
		$result .= '</div>';
		if ($this->data['mode_coor'] == 1) {
			$result .= sprintf('<div id="%s"> </div>', 'coor_'.$uniqueId);
		}
		return $result;
  }
  
}
?>
