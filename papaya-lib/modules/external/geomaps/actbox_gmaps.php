<?php
/**
* Geo maps box with Google Maps / Yahoo Maps
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
class actionbox_gmaps extends base_actionbox {

  var $preview = TRUE;
  
  var $gmapsTypeChanged = FALSE;

  var $editFields = array('gmaps_type' => array('Geo maps type', 
    'isNum', TRUE, 'combo', array(0 => 'Google Maps', 1 => 'Yahoo Maps'), 
    'Select first to get edit fields', 0)
  );
  
  function setData($xmlData) {
  	// set data by parent method
  	parent::setData($xmlData);
  	// check if gmaps type changed or new one is set
  	if ((isset($this->params['gmaps_type']) && isset($this->data['gmaps_type']) &&
				$this->params['gmaps_type'] != $this->data['gmaps_type']) || 
				(isset($this->params['gmaps_type']) && !isset($this->data['gmaps_type']))) {
		  $this->gmapsTypeChanged = TRUE;
		}
		// additional edit fields
  	if (isset($this->data['gmaps_type'])) {
  		      
      // basic things
      $this->editFields['noscript_text'] = array('No script', 
        'isSomeText', FALSE, 
			  'textarea', 2, '', 'Please activate JavaScript!');
      $this->editFields['css_class'] = array('CSS class', 'isAlpha', 
        TRUE, 'input', 20, 'Each box has unique css ids', 'geoMap');
			$this->editFields['mode_coor'] = array('Coordinates mode', 
				'isNum', TRUE, 'combo', array(0 => 'Off', 1 => 'On'),
				'Shows latitude and longitude information on click', 0);
      
      // center
  		$this->editFields[] = 'Center';
  		$this->editFields['center_lat'] = array('Latitude', 
  		  '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input', 50, '', 0);
  		$this->editFields['center_lng'] = array('Longitude', 
  		  '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input', 50, '', 0);
  		$this->editFields['center_zoom'] = array('Zoom level', 'isNum', 
  		  TRUE, 'input', 2, '', 0);
  		  
  		// map type and controls
  		// google maps
		  if ($this->data['gmaps_type'] == 0) {
		  	$this->editFields['map_type'] = array('Default map type', 
		  	  'isAlpha', TRUE, 'combo', array('G_MAP_TYPE' => 'Map', 
		  	    'G_SATELLITE_TYPE' => 'Satellite', 
						'G_HYBRID_TYPE' => 'Hybrid'), 
				  '', 'G_MAP_TYPE');
			  $this->editFields[] = 'Controls';
				$this->editFields['ctrl_basic'] = array('Navigation and zoom', 
				  'isNum', TRUE, 'combo', array(0 => 'Navigation control and zoom bar', 
						  1 => 'Navigation control and zoom buttons', 2 => 'Zoom buttons'), 
						'', 0);
				$this->editFields['ctrl_type'] = array('Map type', 'isAlpha', 
				  TRUE, 'combo', array('true' => 'Yes', 'false' => 'No'),
					'Set default map type above', 'true');
				$this->editFields['ctrl_overview'] = array('Overview map', 
				  'isAlpha', TRUE, 'combo', array('true' => 'Yes', 'false' => 'No'), 
				  '', 'true');
				$this->editFields['ctrl_scale'] = array('Scale information', 
				  'isAlpha', TRUE, 'combo', array('true' => 'Yes', 'false' => 'No'), 
				  '', 'true');
		  // yahoo maps
		  } else {
		  	$this->editFields['map_type'] = array('Default map type', 
		  	  'isAlpha', TRUE, 'combo', array('YAHOO_MAP_REG' => 'Map', 
		  	    'YAHOO_MAP_SAT' => 'Satellite', 'YAHOO_MAP_HYB' => 'Hybrid'), 
		  	   '', 'G_MAP_TYPE');
				$this->editFields[] = 'Controls';
				$this->editFields['ctrl_pan'] = array('Pan', 'isAlpha',
				  TRUE, 'combo', array('true' => 'Yes', 'false' => 'No'), 
				  '', 'true');
				$this->editFields['ctrl_zoom'] = array('Zoom', 'isNum', 
				  TRUE, 'combo', array(0 => 'No',  1 => 'Small', 2 => 'Long'), 
				  '', 2);
				$this->editFields['ctrl_type'] = array('Map type', 'isAlpha', 
				  TRUE, 'combo', array('true' => 'Yes', 'false' => 'No'),
					'Set default map type above', 'true');
		  }
		  
		  // markers
			$this->editFields[] = 'Markers';
			$this->editFields['marker_url'] = array('Page url', 
        'isAlphaNumChar', FALSE, 'input', 200, 
				'Needs markers page as relative url, i.e.: markers.45.data (optional, leave blank to disable markers)');
			$this->editFields['marker_folder'] = array('Folder', 'isNum', 
			  TRUE, 'function', 'callbackFoldersList');
			$this->editFields['marker_mode'] = array('Mode', 'isAlpha', 
			  TRUE, 'combo', array('static' => 'Static', 'rotation' => 'Rotation'), 
				'Rotation mode: Markers by coordinates are recommended', 'click');
			$this->editFields['marker_action'] = array('Mouse action', 'isAlpha', 
			  TRUE, 'combo', array('click' => 'Click', 'mouseover' => 'Mouse over'), 
				'', 'click');
			$this->editFields['marker_rotation'] = array('Rotation time', 
			  'isNum', TRUE, 'input', 5, 'In seconds, for rotation mode', 5000);
  	}
  }
  
  function getForm() {
  	// reload page on type switch
		if ($this->gmapsTypeChanged) {
			$protocol = (isset($_SERVER['HTTPS']) &&
				$_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
			$toUrl = $protocol."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			header("Location: $toUrl");
			exit;
		}	
		// get form by parent method	
		return parent::getForm();
  }
  
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

  function getParsedData($paramFolderId = 'folder_id') {
  	$result = '';
  	$uniqueId = md5($this->paramName.microtime());
  	include_once('output_gmaps.php');
    $gmapsOutput = new output_gmaps();
    $gmapsOutput->initialize();
    
    // geo maps element
  	$result .= sprintf('<div id="%s" class="%s">', 'map_'.$uniqueId, 
		  papaya_strings::escapeHTMLChars($this->data['css_class']));
		// load maps script
		// google maps
		if ($this->data['gmaps_type'] == 0) {
			$result .= $gmapsOutput->getGoogleMapsScript($this->data, $uniqueId,
  	  	$paramFolderId, @$this->data['marker_folder']);
  	// yahoo maps
		} else {
			$result .= $gmapsOutput->getYahooMapsScript($this->data, $uniqueId,
  	  	$paramFolderId, @$this->data['marker_folder']);
		}
		$result .= '</div>';
		
		// coordinates mode element
		if ($this->data['mode_coor'] == 1) {
			$result .= sprintf('<div id="%s"> </div>', 'coor_'.$uniqueId);
		}
		return $result;
  }
  
}
?>
