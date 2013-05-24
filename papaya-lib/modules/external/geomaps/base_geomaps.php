<?php
/**
* Basic class for Geo Maps
*
* @copyright 2007-2009 by Martin Kelm - All rights reserved.
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
* Basic class for Geo Maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class base_geomaps extends base_db {

  /**
   * Database table for markers
   *
   * @var string $tableMarkers
   */
  var $tableMarkers = NULL;

  /**
   * Database table for markers' folders
   *
   * @var string $tableFolders
   */
  var $tableFolders = NULL;

  /**
   * Database table for api keys
   *
   * @var string $tableKeys
   */
  var $tableKeys = NULL;

  /**
   * Database table to get dynamic image modules.
   *
   * @var string $tableDynImages
   */
  var $tableDynImages = PAPAYA_DB_TBL_IMAGES;

  /**
   * Folders list
   *
   * @protected array $folders
   */
  var $folders = NULL;

  /**
   * Markers list
   *
   * @protected array $markers
   */
  var $markers = NULL;

  /**
   * Absolute amount of markers
   *
   * @protected integer $markersCount
   */
  var $markersCount = NULL;

  /**
   * Api keys list
   *
   * @var array $markers
   */
  var $keys = NULL;

  /**
   * Absolute amount of keys
   *
   * @var integer $keysCount
   */
  var $keysCount = NULL;

  /**
   * Contains api type names
   *
   * @var array $apiTypeNames
   */
  var $apiTypeNames = NULL;

  /**
   * Contains api type titles
   *
   * @var array $apiTypeTitles
   */
  var $apiTypeTitles = NULL;

  /**
   * An object to use mysql spatial extensions.
   *
   * @var object $spatialExtensions base_spatial_extensions
   */
  var $spatialExtensions = NULL;

  /**;
   * Main constructor to set table names
   */
  function __construct() {
    // initialize db folder names
    $this->tableFolders = PAPAYA_DB_TABLEPREFIX.'_geomaps_folders';
    $this->tableMarkers = PAPAYA_DB_TABLEPREFIX.'_geomaps_markers';
    $this->tableKeys = PAPAYA_DB_TABLEPREFIX.'_geomaps_keys';

    // initialize arrays
    $this->folders = array();
    $this->markers = array();
    $this->keys = array();

    // initialize counters
    $this->markersCount = 0;
    $this->keysCount = 0;

    // initialize api names
    $this->apiTypeNames = array(
      0 => 'google',
      1 => 'yahoo',
      2 => 'ol'
    );

    // initialize api titles
    $this->apiTypeTitles = array(
      0 => 'Google Maps',
      1 => 'Yahoo Maps',
      2 => 'Open Layers'
    );
  }

  /**
   * PHP4 constructor
   */
  function base_geomaps() {
    $this->__construct();
  }

  /**
   * Initializes a new spatial extensions object to use mysql spatial extensions for
   * point validations.
   *
   * @return boolean initialized?
   */
  function initSpatialExtensions() {
    if (!(isset($this->spatialExtensions) && is_object($this->spatialExtensions))) {
      include_once(dirname(__FILE__).'/base_spatial_extensions.php');
      $this->spatialExtensions = &new base_spatial_extensions();
      if (isset($this->spatialExtensions) && is_object($this->spatialExtensions)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Uses coordinates to validate this position inside of a given polygon.
   *
   * @param integer $folderId unique folder id
   * @param float $latitude i.e. 49.488155742041045
   * @param float $longitude i.e. 8.465939998495742
   * @return boolean point is within polygon
   */
  function checkSpatialPointInPolygon($folderId, $latitude, $longitude) {
    if ($this->initSpatialExtensions() === TRUE) {
      return $this->spatialExtensions->checkSpatialPointInPolygon(
        $folderId, $latitude, $longitude
      );
    }
    return FALSE;
  }

  /**
   * Get a option by module options
   *
   * @param string $option
   * @param string $defaultValue optional
   * @return string value
   */
  function getOption($option, $defaultValue = NULL) {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_module_options.php');
    $moduleOptionsObj = &base_module_options::getInstance();
    return $moduleOptionsObj->readOption('630cd0c01fd9044826e95e181c5a27d2',
      $option, $defaultValue);
  }

  /**
   * Load a list of keys and get the absolute amount
   *
   * @return boolean status
   */
  function loadKeys() {
    $sql = "SELECT key_id, key_type, key_host, key_value
              FROM %s
             ORDER BY key_host, key_type ASC";
    $params = array($this->tableKeys);

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      $this->keys = array();
      $this->keysCount = 0;

      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->keys[$row['key_id']] = $row;
      }
      $this->keysCount = count($this->keys);
      if ($this->keysCount > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Load a single key by id
   *
   * @param integer $keyId
   * @param boolean $useCache use loaded values (optional)
   * @return boolean status
   */
  function loadKey($keyId, $useCache = FALSE) {
    if ($useCache === TRUE && !empty($this->keys[$keyId])) {
      return TRUE;
    }
    $sql = "SELECT key_id, key_type, key_host, key_value
              FROM %s
             WHERE key_id = %d";
    $params = array($this->tableKeys, $keyId);

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->keys[$row['key_id']] = $row;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Load a distinct key id by host and type
   *
   * @param integer $keyId
   * @param boolean $useCache use loaded values (optional)
   * @return boolean status
   */
  function getDistinctKey($host, $keyType, $useCache = FALSE) {
    if (!empty($host)) {
      if ($useCache === TRUE && !empty($keyType) && count($this->keys) > 0) {
        foreach ($this->keys as $key) {
          if ($key['key_type'] == $keyType
              && strpos(strtolower($key['key_host']), strtolower($host)) !== FALSE) {
            return $key;
          }
        }
      }

      $sql = "SELECT key_id, key_type, key_host, key_value
                FROM %s
               WHERE key_host LIKE '%%%s%%'
                 AND key_type = %d";
      $params = array($this->tableKeys, $host, $keyType);
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          return $row;
        }
      }
    }
    return NULL;
  }

  /**
   * Load a list of folders
   */
  function loadFolders() {
    $this->folders = array();
    $sql = "SELECT folder_id, folder_title, folder_marker_icon
              FROM %s
             ORDER BY folder_title ASC";
    $params = array($this->tableFolders);

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      $this->folders = array();
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->folders[$row['folder_id']] = $row;
      }
      if (count($this->folders) > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Load a single folder by id
   *
   * @param integer $folderId
   * @param boolean $useCache use loaded values (optional)
   * @return boolean status
   */
  function loadFolder($folderId, $useCache = FALSE) {
    if ($useCache === TRUE && !empty($this->folders[$folderId])) {
      return TRUE;
    }
    $sql = "SELECT folder_id, folder_title, folder_marker_icon
              FROM %s
             WHERE folder_id = %d";
    $params = array($this->tableFolders, $folderId);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->folders[$row['folder_id']] = $row;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Adds a new folder with title and marker icon
   *
   * @param string $title
   * @param string $markerIcon 32-char id
   * @return boolean status or integer id
   */
  function addFolder($title, $markerIcon = '') {
  $data = array(
      'folder_title' => $title,
      'folder_marker_icon' => $markerIcon
    );
  $newId = $this->databaseInsertRecord(
    $this->tableFolders, 'folder_id', $data
  );
  if ($newId > 0) {
    return $newId;
  } else {
    return FALSE;
  }
  }

  /**
   * Returns a folder title by folder id
   *
   * @param integer $folderId
   * @return string title or nothing
   */
  function getFolderTitle($folderId) {
    $this->loadFolder($folderId, TRUE);

    if (is_array($this->folders[$folderId])
        && !empty($this->folders[$folderId]['folder_title'])) {
      return $this->folders[$folderId]['folder_title'];
    }
    return '';
  }

  /**
   * Add or update marker.
   *
   * @param integer $folderId Markers folder
   * @param string $title Title
   * @param integer $lat Latitude
   * @param integer $lng Longitude
   * @param string $icon Icon image to show on map
   * @param string $description Description
   * @param string $street Street
   * @param string $house House number
   * @param string $zip ZIP code
   * @param string $city City name
   * @param string $country Country name
   * @param boolean $new Set a new marker
   * @param integer $existingId Existing marker id
   * @return boolean|integer Error status FALSE or marker id
   */
  function setMarker($folderId, $title, $lat, $lng, $icon = NULL,
                     $description = NULL, $street = NULL, $house = NULL,
                     $zip = NULL, $city = NULL, $country = NULL,
                     $new = TRUE, $existingId = NULL) {

    $data = array(
      'marker_folder' => $folderId,
      'marker_title' => $title,
      'marker_icon' => $icon,
      'marker_desc' => $description,
      'marker_addr_street' => $street,
      'marker_addr_house' => $house,
      'marker_addr_zip' => $zip,
      'marker_addr_city' => $city,
      'marker_addr_country' => $country,
      'marker_lat' => $lat,
      'marker_lng' => $lng
    );

    if ($new === TRUE) {
      $id = $this->databaseInsertRecord($this->tableMarkers,
        'marker_id', $data);
    } else {
      $id = $this->databaseUpdateRecord($this->tableMarkers, $data,
        'marker_id', $existingId);
    }

    if ($id !== FALSE && $id > 0) {
      return $id;
    }
    return FALSE;
  }

  /**
   * Deletes all markers in a specified folder
   * @param integer $folderId
   * @boolean status
   */
  function deleteMarkersByFolder($folderId) {
    return FALSE != $this->databaseDeleteRecord($this->tableMarkers,
      'marker_folder', $folderId);
  }

  /**
   * Load a list of markers by folder id and get the absolute amount
   *
   * @param integer $folderId
   * @return boolean status
   */
  function loadMarkers($folderId, $limit = NULL, $offset = NULL, $markerIds = NULL) {
    if (!empty($markerIds) && !is_array($markerIds)) {
      $markerIds = array($markerIds);
    }

    if (is_array($markerIds) && count($markerIds) > 0) {
      $markersCond = ' AND '.$this->databaseGetSQLCondition('marker_id', $markerIds);
    } else {
      $markersCond = '';
    }

    $sql = "SELECT marker_id, marker_folder,
                   marker_title, marker_desc, marker_icon,
                   marker_addr_street, marker_addr_house, marker_addr_zip,
                   marker_addr_city, marker_addr_country,
                   marker_lat, marker_lng
              FROM %s
             WHERE marker_folder = %d$markersCond
             ORDER BY marker_sort ASC";
    $params = array($this->tableMarkers, $folderId);

    if ($res = $this->databaseQueryFmt($sql, $params, $limit, $offset)) {
      $this->markers = array();
      $this->markersCount = 0;

      if (empty($this->folders[$folderId])) {
        // load folder icon image data if available
        $this->loadFolder($folderId, TRUE);
      }
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        // check if an icon image has been set ...
        if (empty($row['marker_icon']) && !empty($this->folders[$folderId])
            && !empty($this->folders[$folderId]['folder_marker_icon'])) {
          // ... or use folder icon image data instead if available
          $row['marker_icon'] = $this->folders[$folderId]['folder_marker_icon'];
        }
        $this->markers[$row['marker_id']] = $row;
      }
      $this->markersCount = count($this->markers);
      if ($this->markersCount > 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  function decorateMarkerIcons($dynamicImageId, $width, $height) {

    if ($this->markersCount > 0 && !empty($dynamicImageId)) {

      $sql = "SELECT image_ident
                FROM %s
               WHERE image_id = %d";
      $params = array($this->tableDynImages, $dynamicImageId);

      if ($res = $this->databaseQueryFmt($sql, $params)) {
        if ($imageIdent = $res->fetchField()) {
          $iconImageIds = array();

          // replace marker images
          foreach ($this->markers as $markerId => $marker) {

            if (!empty($marker['marker_icon']) && strlen($marker['marker_icon']) == 32) {

              if (!isset($iconImageIdsTo[$marker['marker_icon']])) {
                $dynImage = sprintf('%s.image.png?img[image_guid]=%s&img[width]=%d&img[height]=%d',
                  $imageIdent, $marker['marker_icon'], $width, $height);
                $iconImageIdsTo[$marker['marker_icon']] = $this->getAbsoluteURL($dynImage);
              }
              $this->markers[$markerId]['marker_icon'] = $iconImageIdsTo[$marker['marker_icon']];
            }
          }
        }
      }
    }
  }

  /**
   * Load a single marker by id
   *
   * @param integer $markerId
   * @param boolean $useCache use loaded values (optional)
   * @param integer $folderId Additional folder id condition
   * @return boolean status
   */
  function loadMarker($markerId, $useCache = FALSE, $folderId = NULL) {
    if ($useCache === TRUE && !empty($this->markers[$markerId])) {
      return TRUE;
    }
    $sql = "SELECT marker_id, marker_folder,
                   marker_title, marker_desc, marker_icon,
                   marker_addr_street, marker_addr_house, marker_addr_zip,
                   marker_addr_city, marker_addr_country,
                   marker_lat, marker_lng
              FROM %s
             WHERE marker_id = %d";
    if ($folderId !== NULL) {
      $sql .= " AND marker_folder = ".(int)$folderId;
    }
    $params = array($this->tableMarkers, $markerId);

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->markers[$row['marker_id']] = $row;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get markers KML data for content output or backend export
   * with additional Google Earth specific data.
   *
   * @param array $markers A list of markers.
   * @param string $styleUrl Adds a style url, see admin_geomaps::exportMarkersKML
   * @param boolean $lookAt Adds lookAt-Point, see admin_geomaps::exportMarkersKML
   * @param integer $fixedIconWidth use a fixed icon width to get dynamic icon images
   * @param integer $fixedIconHeight use a fixed icon height to get dyanmic icon images
   * @return stringt $result xml
   */
  function getMarkersBaseKML($markers = NULL, $styleUrl = NULL, $lookAt = FALSE,
                             $fixedIconWidth = NULL, $fixedIconHeight = NULL) {

    // Use markers by param or loaded markers
    $markers = (is_array($markers) && count($markers) > 0)
      ? $markers : $this->markers;

    if (is_array($markers) && count($markers) > 0) {
      // Get markers xml / kml
      $result = '';
      foreach ($markers as $key => $marker) {

        $desc = papaya_strings::ensureUTF8($marker['marker_desc']);
        $result .= sprintf('<Placemark>'.LF.
                           '<name>%s</name>'.LF.
                           '<description>%s</description>'.LF.
                           '<Point>'.LF.
                           '<coordinates>%s</coordinates>'.LF.
                           '</Point>'.LF,
          $marker['marker_title'],
          $this->getXHTMLString($desc, FALSE),
          papaya_strings::escapeHTMLChars($marker['marker_lng']).','.
          papaya_strings::escapeHTMLChars($marker['marker_lat']).',0');

        // set a look at point (for google maps compatible kml)
        if ($lookAt === TRUE) {
          $result .= sprintf('<LookAt>'.LF.
                             '<longitude>%s</longitude>'.LF.
                             '<latitude>%s</latitude>'.LF.
                             '<altitude>0</altitude>'.LF.
                             '<range>0</range>'.LF.
                             '<tilt>0</tilt>'.LF.
                             '<heading>0</heading>'.LF.
                             '<altitudeMode>clampToGround</altitudeMode>'.LF.
                             '</LookAt>'.LF,
            papaya_strings::escapeHTMLChars($marker['marker_lng']),
            papaya_strings::escapeHTMLChars($marker['marker_lat'])
          );
        }

        // at a style reference (for google maps compatible kml and custom icons)
        if (!empty($marker['marker_icon'])) {

          if (!isset($mediaDB)) {
            include_once(PAPAYA_INCLUDE_PATH.'system/base_mediadb.php');
            $mediaDB = base_mediadb::getInstance();
            $loadedMarkerIconsXML = array();
          }

          if (!isset($loadedMarkerIconsXML[$marker['marker_icon']])) {
            if (checkit::isGUID($marker['marker_icon'], TRUE)) {
              $iconFile = $mediaDB->getFileName($marker['marker_icon']);
              if (!empty($iconFile)) {
                if ($fixedIconWidth > 0 && $fixedIconHeight > 0) {
                  $iconSize = array($fixedIconWidth, $fixedIconHeight);
                } else {
                  $iconSize = getimagesize($iconFile);
                }
                $iconMediaFile = $this->getAbsoluteURL(
                  $this->getWebMediaLink($marker['marker_icon'], 'media')
                );
              }
            }
            if (!empty($iconMediaFile) &&
                (!empty($iconSize[0]) && !empty($iconSize[1]))) {

              $loadedMarkerIconsXML[$marker['marker_icon']] = sprintf(
                '<Style id="customPlacemark%d">'.LF.
                '<IconStyle>'.LF.
                '<Icon>'.LF.
                '<href>%s</href>'.LF.
                '<size x="%d" y="%d" xunits="pixels" yunits="pixels"/>'.LF.
                '</Icon>'.LF.
                '</IconStyle>'.LF.
                '</Style>'.LF,
                $key,
                $iconMediaFile,
                $iconSize[0],
                $iconSize[1]
              );
              $loadedMarkerIconsXML[$marker['marker_icon']] .= sprintf(
                '<styleUrl>customPlacemark%d</styleUrl>'.LF,
                $key
              );
              $result .= $loadedMarkerIconsXML[$marker['marker_icon']];
            }
          } else {
            $result .= $loadedMarkerIconsXML[$marker['marker_icon']];
          }

        } elseif (!is_null($styleUrl)) {
          $result .= sprintf('<styleUrl>%s</styleUrl>'.LF, $styleUrl);
        }

        $result .= '</Placemark>'.LF;
      }

      return $result;
    }

    return '';
  }

  /**
   * Gets additional kml data and uses getMarkersBaseKML
   * with additional Google Earth specific data.
   *
   * @param array $markers A list of markers.
   * @param string $folderTitle
   * @param string $fileName
   * @param integer $fixedIconWidth use a fixed icon width to get dynamic icon images
   * @param integer $fixedIconHeight use a fixed icon height to get dyanmic icon images
   * @return stringt $result xml
   */
  function getMarkersKML($markers, $folderTitle = NULL, $fileName = NULL,
                         $fixedIconWidth = NULL, $fixedIconHeight = NULL) {

    if (empty($folderTitle)) {
      // Set folder title
      $defaultFolderTitle = $this->getOption(
        'default_folder_title', 'No folder title'
      );
      $folderTitle = $defaultFolderTitle;
    }

    if (empty($fileName)) {
      $fileName = sprintf('geo_maps_export_%d.kml', time());
    }

    $result = sprintf('<kml xmlns="http://earth.google.com/kml/2.2">'.LF.
                      '<name>%s</name>'.LF.
                      '<Style id="sh_ylw-pushpin">'.LF.
                      '<IconStyle>'.LF.
                      '<scale>1.3</scale>'.LF.
                      '<Icon>'.LF.
                      '<href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href>'.LF.
                      '</Icon>'.LF.
                      '<hotSpot x="20" y="2" xunits="pixels" yunits="pixels"/>'.LF.
                      '</IconStyle>'.LF.
                      '</Style>'.LF.
                      '<Style id="sn_ylw-pushpin">'.LF.
                      '<IconStyle>'.LF.
                      '<scale>1.1</scale>'.LF.
                      '<Icon>'.LF.
                      '<href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href>'.LF.
                      '</Icon>'.LF.
                      '<hotSpot x="20" y="2" xunits="pixels" yunits="pixels"/>'.LF.
                      '</IconStyle>'.LF.
                      '</Style>'.LF.
                      '<StyleMap id="msn_ylw-pushpin">'.LF.
                      '<Pair>'.LF.
                      '<key>normal</key>'.LF.
                      '<styleUrl>#sn_ylw-pushpin</styleUrl>'.LF.
                      '</Pair>'.LF.
                      '<Pair>'.LF.
                      '<key>highlight</key>'.LF.
                      '<styleUrl>#sh_ylw-pushpin</styleUrl>'.LF.
                      '</Pair>'.LF.
                      '</StyleMap>'.LF.
                      '<Document>'.LF.
                      '<Folder>'.LF.
                      '<name>%s</name>'.LF.
                      '%s'.LF.
                      '</Folder>'.LF.
                      '</Document>'.LF.
                      '</kml>'.LF,
      $fileName,
      $folderTitle,
      $this->getMarkersBaseKML(
        $markers, '#msn_ylw-pushpin', TRUE, $fixedIconWidth, $fixedIconHeight
      )
    );

    return $result;
  }

  /**
   * generates a string representing a XHTML drop down menu with map data folders
   *
   * @param string $name content of the node attribute 'name'
   * @param integer $currentFolder identifies the currently selected option
   * @param array $folders if set it will override the folders defined on object level
   * @return string XHTML code representing a XHTML drop down menu
   *
   * @see papaya_strings::escapeHTMLChars()
   */
  function getFoldersComboBox($name, $element, $data, $paramName) {
    $result = '';
    $selected = '';

    if (!(is_array($this->folders) && count($this->folders) > 0)) {
      $this->loadFolders();
    }

    $result .= sprintf(
      '<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
      $paramName, $name);

    if ($data == 0) {
      $selected = ' selected="selected"';
    }
    $result .= sprintf('<option value="%d"%s>%s</option>', 0,
      $selected, papaya_strings::escapeHTMLChars($this->_gt('Base')));

    if (isset($this->folders) && is_array($this->folders)
        && count($this->folders) > 0) {
      foreach ($this->folders as $folderId => $folder) {
        $selected = '';
        if ($data == $folderId) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf('<option value="%d"%s>%s</option>', $folderId,
          $selected, papaya_strings::escapeHTMLChars($folder['folder_title']));
      }
    }
    $result .= '</select>';
    return $result;
  }

}
