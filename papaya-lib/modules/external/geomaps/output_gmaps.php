<?php
/**
* Output for geo maps content modules
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

  function getMapsScriptFooter($data, $mfParamName, $mfParamValue) {
    $result = "";
  	if (isset($data['marker_url']) && trim($data['marker_url']) != '') {
			$result .= sprintf("addMarkers('%s', '%s');".LF, 
				papaya_strings::escapeHTMLChars($data['marker_url']),
				$this->paramName.'['.$mfParamName.']='.$mfParamValue);
			if ((boolean)$data['marker_polyline'] && 
					isset($data['marker_polyline_color']) &&
					isset($data['marker_polyline_width'])) {
				$result .= sprintf("getPolyline('%s', %d);".LF, 
				  $data['marker_polyline_color'], $data['marker_polyline_width']);
			}
			if ($data['marker_mode'] != 'hide') {
				$result .= sprintf("getMarkers('%s', '%s', %d);".LF, 
					$data['marker_action'], $data['marker_mode'], 
					$data['marker_rotation']);
			}
		}
		$result .= '//-->'.LF;
		$result .= '</script>'.LF;
		$result .= '<noscript>'.
			papaya_strings::escapeHTMLChars($data['noscript_text']).
			' </noscript>';
		return $result;
  }

  function getGoogleMapsScript($data, $uniqueId, $mfParamName, $mfParamValue) {
  	$result = '';
  	$apiKey = $this->loadApiKey(0);
  	if ($apiKey) {
			$result = sprintf(
				'<script type="text/javascript" '.
				'src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=%s"> </script>'.LF, 
				papaya_strings::escapeHTMLChars($apiKey)
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
			$result .= $this->getMapsScriptFooter($data, $mfParamName, $mfParamValue);
		}
		return $result;
  }
  
  function getYahooMapsScript($data, $uniqueId, $mfParamName, $mfParamValue) {
  	$result = '';
  	$apiId = $this->loadApiKey(1);
  	if ($apiId) {
			$result = sprintf(
				'<script type="text/javascript" '.
				'src="http://api.maps.yahoo.com/ajaxymap?v=3.4&amp;appid=%s"> </script>'.LF, 
				papaya_strings::escapeHTMLChars($apiId)
			);
			$result .= '<script type="text/javascript" src="papaya-script/geomarkers.js"> </script>';
			$result .= '<script type="text/javascript" src="papaya-script/yahoomaps.js"> </script>';
			$result .= '<script type="text/javascript">';
			$result .= '<!--'.LF;	
			$result .= sprintf("initYahooMaps(%s, %d, %s, %s, %f, %f, %d, %s, '%s'); ".LF,
				papaya_strings::escapeHTMLChars($data['mode_coor']), 
				(int)$data['ctrl_zoom'], 
				papaya_strings::escapeHTMLChars($data['ctrl_pan']), 
				papaya_strings::escapeHTMLChars($data['ctrl_type']), 
				papaya_strings::escapeHTMLChars($data['center_lat']), 
				papaya_strings::escapeHTMLChars($data['center_lng']), 
				papaya_strings::escapeHTMLChars($data['center_zoom']),
				papaya_strings::escapeHTMLChars($data['map_type']), $uniqueId
			);
			$result .= $this->getMapsScriptFooter($data, $mfParamName, $mfParamValue);
		}
		return $result;
  }
  
  function loadApiKey($mapsType) {
  	$sql = "SELECT key_value 
              FROM %s 
             WHERE key_type = %d
               AND key_host LIKE '%s'";
    $params = array($this->tableKeys, $mapsType, 
      '%'.$_SERVER['HTTP_HOST'].'%');

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row['key_value'];
      } 
    }
    return FALSE;
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
  	    	  'marker_title' => 'Entry '.@$entry['entry_id'],
  	    	  'marker_desc'  => '<strong>'.@$entry['author'].' ('.
  	    	    date('Y-m-d', @$entry['entry_created']).
  	    	    '):</strong><br />'.@$entry['entry_text']
  	    	);
  	    	$setMarker = FALSE;
  	    	// variable data
  	    	if ($geoData['city'] && $geoData['country']) {
  	    		$marker['marker_address'] = $geoData['city'].', '.$geoData['country'];
  	    	} elseif ($geoData['country']) {
  	    		$marker['marker_address'] = $geoData['country'];
          }
          if ($geoData['lng'] && $geoData['lat']) {
  	    		$marker['marker_lat'] = $geoData['lat'];
  	    		$marker['marker_lng'] = $geoData['lng'];
  	    		$setMarker = TRUE;
  	    	}
  	    	// set to markers array
  	    	if ($setMarker !== FALSE) {
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
