<?php
/**
* Output for geo maps content modules
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/

/**
* Basic markers class
*/
require_once('base_gmaps.php');

/**
* Output for geo maps content modules
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class output_gmaps extends base_gmaps {
	
	var $gbPlugin = NULL;
	var $geoIpDataFile = 'GeoLiteCity.dat';

  function getGoogleMapsScript($data, $uniqueId, $mfParamName, $mfParamValue) {
  	$result = sprintf(
			'<script type="text/javascript" '.
			'src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=%s"> </script>'.LF, 
			papaya_strings::escapeHTMLChars($data['api_key'])
		);
		$result .= '<script type="text/javascript" src="papaya-script/geomarkers.js"> </script>';
		$result .= '<script type="text/javascript" src="papaya-script/googlemaps.js"> </script>';
		$result .= '<script type="text/javascript">';
		$result .= '<!--'.LF;
		$result .= sprintf("initGoogleMaps(%s, %d, %s, %s, %s, %f, %f, %d, %s, '%s'); ".LF,
			papaya_strings::escapeHTMLChars($data['mode_coor']), 
			(int)$data['ctrl_basic'], 
			papaya_strings::escapeHTMLChars($data['ctrl_scale']), 
			papaya_strings::escapeHTMLChars($data['ctrl_type']), 
			papaya_strings::escapeHTMLChars($data['ctrl_overview']), 
			papaya_strings::escapeHTMLChars($data['center_lat']), 
			papaya_strings::escapeHTMLChars($data['center_lng']), 
			papaya_strings::escapeHTMLChars($data['center_zoom']),
			papaya_strings::escapeHTMLChars($data['map_type']), $uniqueId
		);
		if (isset($data['marker_url']) && trim($data['marker_url']) != '') {
			$result .= sprintf("addMarkers('%s', '%s');".LF, 
				papaya_strings::escapeHTMLChars($data['marker_url']),
				$this->paramName.'['.$mfParamName.']='.$mfParamValue);
			$result .= sprintf("getMarkers('%s', '%s', %d);".LF, 
				$data['marker_action'], $data['marker_mode'], 
				$data['marker_rotation']);
		}
		$result .= '//-->'.LF;
		$result .= '</script>'.LF;
		$result .= '<noscript>'.
		  papaya_strings::escapeHTMLChars($data['noscript_text']).
		  ' </noscript>';
		return $result;
  }
  
  function getYahooMapsScript($data, $uniqueId, $mfParamName, $mfParamValue) {
  	$result = sprintf(
			'<script type="text/javascript" '.
			'src="http://api.maps.yahoo.com/ajaxymap?v=3.0&amp;appid=%s"> </script>'.LF, 
			papaya_strings::escapeHTMLChars($data['api_id'])
		);
		$result .= '<script type="text/javascript" src="papaya-script/geomarkers.js"> </script>';
		$result .= '<script type="text/javascript" src="papaya-script/yahoomaps.js"> </script>';
		$result .= '<script type="text/javascript">';
		$result .= '<!--'.LF;	
		$result .= sprintf("initYahooMaps(%s, %d, %s, %s, %f, %f, %d, %s, '%s'); ".LF,
			'false', (int)$data['ctrl_zoom'], 
			papaya_strings::escapeHTMLChars($data['ctrl_pan']), 
			papaya_strings::escapeHTMLChars($data['ctrl_type']), 
			papaya_strings::escapeHTMLChars($data['center_lat']), 
			papaya_strings::escapeHTMLChars($data['center_lng']), 
			papaya_strings::escapeHTMLChars($data['center_zoom']),
			papaya_strings::escapeHTMLChars($data['map_type']), $uniqueId
		);
		if (isset($data['marker_url']) && trim($data['marker_url']) != '') {
			$result .= sprintf("addMarkers('%s', '%s');".LF, 
				papaya_strings::escapeHTMLChars($data['marker_url']),
				$this->paramName.'['.$mfParamName.']='.$mfParamValue);
			$result .= sprintf("getMarkers('%s', '%s', %d);".LF, 
				$data['marker_action'], $data['marker_mode'], 
				$data['marker_rotation']);
		}
		$result .= '//-->'.LF;
		$result .= '</script>'.LF;
		$result .= '<noscript>'.
		  papaya_strings::escapeHTMLChars($data['noscript_text']).
		  ' </noscript>';
		return $result;
  }

  function getFoldersComboBox($name, $currentFolder, $folders = NULL) {
  	$result = '';
  	$selected = '';
  	$optionalFolders = TRUE;
  	if (!$folders) {
  		$folders = @$this->folders;
  		$optionalFolders = FALSE;
  	}
  	$result .= sprintf('<select name="%s[%s]" '.
      'class="dialogInput dialogScale">'.LF, $this->paramName, $name);
  	if (!$optionalFolders) {
  		if (!isset($currentFolder) || !($currentFolder >= 0)) {
				$currentFolder = 0;
				$selected = ' selected="selected"';
			}
			$result .= sprintf('<option value="%d"%s>%s</option>', 0,
				$selected, $this->_gt('Root'));
  	}
    if (isset($folders) && is_array($folders) && count($folders) > 0) {
    	foreach ($folders as $folderId => $folder) {
    		$selected = '';
				if ($currentFolder == $folderId) {
					$selected = ' selected="selected"';
				}
				$result .= sprintf('<option value="%d"%s>%s</option>', $folderId,
      		$selected, papaya_strings::escapeHTMLChars($folder['folder_title']));
    	}
    }
    $result .= '</select>';
    return $result;
  }
  
  function getGbFoldersComboBox($name, $currentFolder) {
  	$folders = array();
  	if (isset($this->gbPlugin->books) && is_array($this->gbPlugin->books) 
  	    && count($this->gbPlugin->books) > 0) {
  	  foreach ($this->gbPlugin->books as $key => $book) {
  	  	if (isset($book['gb_id']) && isset($book['title'])) {
  	  		$folders[$book['gb_id']] = array(
  	  		  'folder_title' => $book['title'],
  	  		  'folder_id' => $book['gb_id']
  	  		);
  	  	}
  	  }
  	}
  	return $this->getFoldersComboBox($name, $currentFolder, $folders);
  }

  function gbPluginInitialization(&$contentObj) {
  	$gbData = $contentObj->getPluginData('f1b18c4b71fb8e7a60f2a54e35f1b701');
  	if (is_array($gbData) && count($gbData) > 0) {
  		include_once(PAPAYA_MODULES_PATH.$gbData['path'].'base_guestbook.php');
  		if ($this->gbPlugin = new base_guestbook($contentObj)) {
  			return TRUE;
  		}
  	}
  	return FALSE;
  }

  function gbPluginLoadBooks() {
  	if (isset($this->gbPlugin) && is_object($this->gbPlugin) && 
  	    is_a($this->gbPlugin, 'base_guestbook')) {
  		$this->gbPlugin->loadBooks($bookId);
  	}
  }
   
  function gbPluginLoadEntriesToMarkers($bookId) {
    $this->markers = array();
    // initialize guestbook as plugin and load entries
  	if (isset($this->gbPlugin) && is_object($this->gbPlugin) && 
  	    is_a($this->gbPlugin, 'base_guestbook')) {
  		$this->gbPlugin->loadEntries($bookId);
  		if (isset($this->gbPlugin->entries) && is_array($this->gbPlugin->entries) 
  	      && count($this->gbPlugin->entries) > 0) {
  	    // set entries to markers
  	    foreach ($this->gbPlugin->entries as $key => $entry) {
  	    	$geoData = $this->loadGeoIpData($entry['entry_ip']);
  	    	// set base marker data
  	    	$marker = array(
  	    	  'marker_type'  => 0, // default value for invalid markers
  	    	  'marker_title' => 'Entry '.@$entry['entry_id'],
  	    	  'marker_desc'  => '<strong>'.@$entry['author'].' ('.
  	    	    date('Y-m-d', @$entry['entry_created']).
  	    	    '):</strong><br />'.@$entry['entry_text']
  	    	);
  	    	// variable data
  	    	if ($geoData['city'] && $geoData['country']) {
  	    		$marker['marker_address'] = $geoData['city'].', '.$geoData['country'];
  	    		$marker['marker_type'] = 1;
  	    	} elseif ($geoData['country']) {
  	    		$marker['marker_address'] = $geoData['country'];
  	    		$marker['marker_type'] = 1;
          }
          if ($geoData['lng'] && $geoData['lat']) {
  	    		$marker['marker_lat'] = $geoData['lat'];
  	    		$marker['marker_lng'] = $geoData['lng'];
  	    		$marker['marker_type'] = 2;
  	    	}
  	    	// set to markers array
  	    	if ($marker['marker_type'] > 0) {
  	    		$this->markers[] = $marker;
  	    	}
  	    }
  	  }
  	}
  	// return value
  	if (count($this->markers) > 0) {
  		return TRUE;
  	}
  	return FALSE;
  }
  
}
