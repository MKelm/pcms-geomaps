<?php
/**
* Basic class for Geo Maps
*
* @copyright 2007-2008 by Martin Kelm - All rights reserved.
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
  var $tableMarkers = '';

  /**
   * Database table for markers' folders
   *
   * @var string $tableFolders
   */
  var $tableFolders = '';

  /**
   * Database table for api keys
   *
   * @var string $tableKeys
   */
  var $tableKeys    = '';

  /**
   * Folders list
   *
   * @protected array $folders
   */
  var $folders = array();

  /**
   * Markers list
   *
   * @protected array $markers
   */
  var $markers = array();

  /**
   * Absolute amount of markers
   *
   * @protected integer $markersCount
   */
  var $markersCount = 0;

  /**
   * Api keys list
   *
   * @var array $markers
   */
  var $keys = array();

  /**
   * Absolute amount of keys
   *
   * @var integer $keysCount
   */
  var $keysCount = 0;

  /**
   * Main constructor to set table names
   */
  function __construct() {
    $this->tableFolders = PAPAYA_DB_TABLEPREFIX.'_geomaps_folders';
    $this->tableMarkers = PAPAYA_DB_TABLEPREFIX.'_geomaps_markers';
    $this->tableKeys    = PAPAYA_DB_TABLEPREFIX.'_geomaps_keys';
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
        foreach ($this->keys as &$key) {
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
    $sql = "SELECT folder_id, folder_title
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
    $sql = "SELECT folder_id, folder_title
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
   * Load a list of markers by folder id and get the absolute amount
   *
   * @param integer $folderId
   * @return boolean status
   */
  function loadMarkers($folderId, $limit = NULL, $offset = NULL) {
    $sql = "SELECT marker_id, marker_folder, marker_title,
                   marker_desc, marker_address, marker_lat, marker_lng
              FROM %s
             WHERE marker_folder = %d
             ORDER BY marker_sort ASC";
    $params = array($this->tableMarkers, $folderId);

    if ($res = $this->databaseQueryFmt($sql, $params, $limit, $offset)) {
      $this->markers = array();
      $this->markersCount = 0;

      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->markers[$row['marker_id']] = $row;
      }
      $this->markersCount = count($this->markers);
      if ($this->markersCount > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Load a single marker by id
   *
   * @param integer $markerId
   * @param boolean $useCache use loaded values (optional)
   * @return boolean status
   */
  function loadMarker($markerId, $useCache = FALSE) {
    if ($useCache === TRUE && !empty($this->markers[$markerId])) {
      return TRUE;
    }
    $sql = "SELECT marker_id, marker_folder, marker_title,
                   marker_desc, marker_address, marker_lat, marker_lng
              FROM %s
             WHERE marker_id = %d";
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
   * @param string $uniqueId Unique geo maps content identifier.
   * @param string $styleUrl Adds a style url, see admin_geomaps::exportMarkersKML
   * @param boolean $lookAt Adds lookAt-Point, see admin_geomaps::exportMarkersKML
   * @return stringt $result xml
   */
  function getMarkersKML($markers = NULL, $uniqueId = NULL,
                         $styleUrl = NULL, $lookAt = FALSE) {

    // Use markers by param or loaded markers
    $markers = (is_array($markers) && count($markers) > 0)
      ? $markers : $this->markers;

    // Use unique identifier by param or get a new one
    $uniqueId = (empty($uniqueId)) ?
      md5(uniqid(rand()+microtime())) : $uniqueId;

    if (is_array($markers) && count($markers) > 0) {
      // Get markers xml / kml
      $result = '';
      foreach ($markers as $key => $marker) {

        $desc = papaya_strings::ensureUTF8($marker['marker_desc']);
        $result .= sprintf('<Placemark uid="%s" >'.LF.
                           '<name>%s</name>'.LF.
                           '<description>%s</description>'.LF.
                           '<Point>'.LF.
                           '<coordinates>%s</coordinates>'.LF.
                           '</Point>'.LF,
          $uniqueId,
          $marker['marker_title'],
          $this->getXHTMLString($desc, TRUE),
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

        // at a style reference (for google maps compatible kml)
        if (!is_null($styleUrl)) {
          $result .= sprintf('<styleUrl>%s</styleUrl>'.LF, $styleUrl);
        }

        $result .= '</Placemark>'.LF;
      }

      return $result;
    }

    return '';
  }



}
