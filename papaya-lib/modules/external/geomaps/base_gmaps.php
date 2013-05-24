<?php
/**
* Basic class for geomaps
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
* Basic class database
*/
require_once(PAPAYA_INCLUDE_PATH.'system/sys_base_db.php');

/**
* Basic class for geomaps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class base_gmaps extends base_db {
	
  var $paramName = 'gmaps';
  
  var $tableFolders = '';
  var $tableMarkers = '';
  var $tableKeys    = '';
 
  var $folders = NULL;
  var $markers = NULL;
  
  var $markersCount = 0;

  function initialize($folderId = NULL) {
  	$this->tableFolders = PAPAYA_DB_TABLEPREFIX.'_gmaps_folders';
  	$this->tableMarkers = PAPAYA_DB_TABLEPREFIX.'_gmaps_markers';
  	$this->tableKeys    = PAPAYA_DB_TABLEPREFIX.'_gmaps_keys';
  	$this->loadFolders();
  	if ($folderId !== NULL && $folderId >= 0) {
  		$this->loadMarkers($folderId);
  	}
  }
  
  function loadMarkers($folderId = NULL) {
  	$this->markers = array();
  	if (!$folderId) {
  		$folderId = 0;
  	}
  	$sql = 'SELECT marker_id, marker_folder, marker_title, 
  	               marker_desc, marker_address, marker_lat, marker_lng
              FROM %s 
             WHERE marker_folder = %d 
             ORDER BY marker_sort, marker_title ASC';
    $params = array($this->tableMarkers, $folderId);
    
    $this->markersCount = 0;
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->markers[$row['marker_id']] = $row;
        $this->markersCount++;
      }
    }
  }
  
  function loadFolders() {
  	$this->folders = array();
  	$sql = 'SELECT folder_id, folder_title
              FROM %s 
             ORDER BY folder_title ASC';
    $params = array($this->tableFolders);

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->folders[$row['folder_id']] = $row;
      }
    }
  }
  
  function loadGeoIpData($ip) {
    $modulePath = PAPAYA_INCLUDE_PATH.'external/geoip/';
		include_once($modulePath.'geoipcity.inc');
		include_once($modulePath.'geoipregionvars.php');
		$gi = geoip_open($modulePath.$this->geoIpDataFile, GEOIP_MEMORY_CACHE);
    $result = '';
		$record = geoip_record_by_addr($gi, $ip);
		
		$data = array();
		$data['country'] = $record->country_name;
		$data['city'] = $record->city;
		$data['lat'] = $record->latitude;
		$data['lng'] = $record->longitude;
		
		geoip_close($gi);
		return $data;
  }
  
  function exportMarkers($markers = NULL) {
  	if (!$markers) {
  		$markers = @$this->markers;
  	}
  	$agentString = strtolower(@$_SERVER["HTTP_USER_AGENT"]);
		if (strpos($agentString, 'opera') !== FALSE) {
			$agent =  'OPERA';
		} elseif (strpos($agentString, 'msie') !== FALSE) {
			$agent =  'IE';
		} else {
			$agent =  'STD';
		}
		$mimeType = ($agent == 'IE' || $agent == 'OPERA')
			? 'application/octetstream' : 'application/octet-stream';
		$fileName = 'markers_'.date('Ymd_hms').'.kml';
		if ($agent == 'IE') {
			header('Content-Disposition: inline; filename="'.$fileName.'"');
		} else {
			header('Content-Disposition: attachment; filename="'.$fileName.'"');
		}
		header('Content-Type: text/xml');
  	$result = '<?xml version="1.0" encoding="utf-8"?>'.LF;
  	$result .= '<kml>'.LF;
  	$result .= '<Document>'.LF;
  	$result .= $this->getMarkersKML($markers);
    $result .= '</Document>'.LF;
  	$result .= '</kml>'.LF;
  	echo $result;
  	exit;
  }
  
  function getMarkersKML($markers = NULL) {
  	$result = '';
  	if (!$markers) {
  		$markers = @$this->markers;
  	}
  	if (is_array($markers) && count($markers) > 0) {
  		$result = '';
  		$count = 0;
  		foreach ($markers as $key => $marker) {
  			$desc = $marker['marker_desc'];
  			$desc = papaya_strings::ensureUTF8($desc);
  			$desc = $this->getXHTMLString($desc, TRUE);
  			$description = '<div class="geoMapDesc">'.$desc.'</div>';
  			
  			$result .= sprintf('<Placemark id="%s">'.LF, $this->paramName.$count);
  			$result .= sprintf('<name>%s</name>'.LF, $marker['marker_title']);
  			$result .= sprintf('<description>%s</description>'.LF, $description);

				$result .= sprintf('<address>%s</address>'.LF,
				  papaya_strings::escapeHTMLChars($marker['marker_address']));
				$result .= '<Point>'.LF;
				$result .= sprintf('<coordinates>%s</coordinates>'.LF,
					papaya_strings::escapeHTMLChars($marker['marker_lat']).','.
					papaya_strings::escapeHTMLChars($marker['marker_lng']).',0');	
				$result .= '</Point>'.LF;		
				$result .= '</Placemark>'.LF;
				$count++;
		  }
  	}
  	return $result;
  }
    
}
