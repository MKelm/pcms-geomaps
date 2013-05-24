<?php
/**
* Basic class for mysql spatial extensions
*
* @copyright 2007-2013 by Martin Kelm - All rights reserved.
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
* @author Martin Kelm <martinkelm@shrt.ws>
*/

/**
* Basic class for database access
*/
require_once(PAPAYA_INCLUDE_PATH.'system/sys_base_db.php');
/**
 * Basic class to check parameters / values
 */
require_once(PAPAYA_INCLUDE_PATH.'system/sys_checkit.php');

/**
* Basic class for mysql spatial extensions
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@shrt.ws>
*/
class base_spatial_extensions extends base_db {

  /**
   * Database table for polygons
   *
   * @var string $tablePolygons
   */
  var $tablePolygons = NULL;

  /**
   * Database table for points
   *
   * @var string $tablePoints
   */
  var $tablePoints = NULL;

  /**
   * Main constructor to set table names
   */
  function __construct() {
    $this->tablePolygons = PAPAYA_DB_TABLEPREFIX.'_geomaps_polygons';
    $this->tablePoints = PAPAYA_DB_TABLEPREFIX.'_geomaps_points';
  }

  /**
   * PHP4 constructor / wrapper
   */
  function base_spatial_extensions() {
    $this->__construct();
  }

  /**
   * Create a new database table before point insert.
   *
   * point_folder_id = unique geo maps folder id
   * point_marker_id = unique geo maps marker id (related to folder id)
   * point_location = spatial point (geograpic location)
   * point_reference = optional reference information, i.e. a geo maps marker id
   *
   * @return boolean sql command has been performed
   */
  function createSpatialPointsTable() {
    $sql = "CREATE TABLE IF NOT EXISTS %s (
              point_folder_id integer,
              point_marker_id integer,
              point_location point NOT NULL,
              PRIMARY KEY (point_folder_id, point_marker_id),
              SPATIAL KEY point_location (point_location)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $params = array($this->tablePoints);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Create a new database table before polygon insert.
   *
   * polygon_folder_id =
   * polygon_locations = spatial polygon points (geographic locations)
   *
   * @return boolean sql command has been performed
   */
  function createSpatialPolygonsTable() {
    $sql = "CREATE TABLE IF NOT EXISTS %s (
              polygon_folder_id integer,
              polygon_locations polygon NOT NULL,
              PRIMARY KEY (polygon_folder_id),
              SPATIAL KEY polygon_locations (polygon_locations)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $params = array($this->tablePolygons);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Uses coordinates to validate this position inside of a given polygon.
   *
   * @param float $latitude i.e. 49.488155742041045
   * @param float $longitude i.e. 8.465939998495742
   * @param integer $folderId unique geo maps folder id
   * @return boolean point is within polygon
   */
  function checkSpatialPointInPolygon($folderId, $latitude, $longitude) {

    if (checkit::isFloat($latitude, TRUE) && checkit::isFloat($longitude, TRUE)
        && (int)$folderId >= 0) {
      $sql = "SELECT Within(
                       GeomFromText('POINT(%s %s)'),
                       polygon_locations
                     ) AS result
                FROM %s
               WHERE polygon_folder_id = %d";
      $params = array($latitude, $longitude, $this->tablePolygons, $folderId);

      if ($res = $this->databaseQueryFmt($sql, $params)) {
        if ((int)$res->fetchField() == 1) {
          return TRUE;
        }
      };
    }
    return FALSE;
  }

  /**
   * Insert a single spatial polygon to use it for point validation later.
   *
   * @see checkSpatialPointInPolygon checks point locations in polygons.
   * @param integer $folderId unique folder id for polygon
   * @param array $points point list, array(array(lat, lng), ... )
   * @return string or NULL (error)
   */
  function insertSpatialPolygon($folderId, $points) {

    if (!empty($points) && is_array($points) && (int)$folderId > 0) {

      $maxIdx = count($points)-1;
      $polygonLocations = 'Polygon((';
      foreach ($points as $idx => $point) {
        if (isset($point[0]) && isset($point[1])
            && checkit::isFloat($point[0], TRUE)
            && checkit::isFloat($point[1], TRUE)) {
          $polygonLocations .= sprintf('%s %s%s',
            $point[0], $point[1], (($idx != $maxIdx) ? ',' : ''));
        }
      }
      $polygonLocations .= '))';

      $sql = "INSERT INTO %s
              VALUES (%d, GeomFromText('%s'))";
      $params = array($this->tablePolygons, $folderId, $polygonLocations);
      if (FALSE !== $this->databaseQueryFmt($sql, $params)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Removes a single polygon entry by a given folder id.
   *
   * @param integer $folderId unqiue geo maps folder id
   * @return boolean remove command has been performed
   */
  function removeSpatialPolygon($folderId) {
    if ((int)$folderId > 0) {
      return FALSE !==
        $this->databaseDeleteRecord($this->tablePolygons, 'polygon_folder_id', (int)$folderId);
    }
    return FALSE;
  }

  /**
   * Count all spatial polygon entries or a single entry by a given folder id.
   *
   * @param integer $folderId unqiue geo maps folder id
   * @return interger polygons amount
   */
  function countSpatialPolygons($folderId = NULL) {

    if ($folderId !== NULL) {
      $cond = ' WHERE '.$this->databaseGetSqlCondition('polygon_folder_id', (int)$folderId);
    } else {
      $cond = '';
    }

    $sql = "SELECT COUNT(*) AS count
              FROM %s".$cond;

    $params = array($this->tablePolygons);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      return (int)$res->fetchField();
    }

    return 0;
  }

  /**
   * Uses a coordinate to get the nearest spatial point.
   *
   * @param float $latitude i.e. 49.488155742041045
   * @param float $longitude i.e. 8.465939998495742
   * @return string $pointId
   */
  function getNearestSpatialPoint($latitude, $longitude) {

    if (checkit::isFloat($latitude, TRUE) && checkit::isFloat($longitude, TRUE)) {
      $sql = "SELECT point_folder_id, point_marker_id,
                     GLength(
                       LineStringFromWKB(
                         LineString(
                           AsBinary(point_location),
                           AsBinary(GeomFromText('POINT(%f %f)'))
                         )
                       )
                     ) AS distance
                FROM %s
               ORDER BY distance ASC LIMIT 1";
      $params = array($latitude, $longitude, $this->tablePoints);

      if ($res = $this->databaseQueryFmt($sql, $params)) {
        if ($pointData = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          return $pointData;
        }
      };
    }
    return NULL;
  }

  /**
   * Insert a single spatial polygon to use it for point validation later.
   *
   * @see countSpatialPoints checks if an point already exists
   * @param integer $folderId geo maps folder
   * @param integer $markerId geo maps marker
   * @param array $points point, array(lat, lng)
   * @return string or NULL (error)
   */
  function insertSpatialPoint($folderId, $markerId, $point) {

    if ((int)$folderId >= 0 && (int)$markerId >= 0
        && is_array($point) && count($point) == 2
        && !empty($point[0]) && !empty($point[1])
        && $this->countSpatialPoints($folderId, $markerId) == 0) {

      $sql = "INSERT INTO %s
              VALUES (%d, %d, GeomFromText('Point(%s %s)'))";
      $params = array($this->tablePoints, $folderId, $markerId, $point[0], $point[1]);
      if (FALSE !== $this->databaseQueryFmt($sql, $params)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Removes a single point entry by given folder / marker id.
   *
   * @param integer $folderId geo maps folder
   * @param integer $markerId geo maps folder
   * @return boolean remove command has been performed
   */
  function removeSpatialPoint($folderId, $markerId) {
    if (is_int($folderId) && $folderId >= 0 && is_int($markerId) && $markerId >= 0) {
      return FALSE !==
        $this->databaseDeleteRecord($this->tablePoints,
          array('point_folder_id' => $folderId, 'point_marker_id' => $markerId)
        );
    }
    return FALSE;
  }

  /**
   * Count all spatial point entries or a single entry by given folder / marker id.
   *
   * @param integer $folderId geo maps folder
   * @param integer $markerId geo maps folder
   * @return interger points amount
   */
  function countSpatialPoints($folderId = NULL, $markerId = NULL) {

    if ($folderId !== NULL && $folderId >= 0
        && $markerId !== NULL && $markerId >= 0) {
      $cond = ' WHERE '.$this->databaseGetSqlCondition('point_folder_id', $folderId).
              ' AND '.$this->databaseGetSqlCondition('point_marker_id', $markerId);
    } elseif ($folderId !== NULL && $folderId >= 0) {
      $cond = ' WHERE '.$this->databaseGetSqlCondition('point_folder_id', $folderId);
    } else {
      $cond = '';
    }

    $sql = "SELECT COUNT(*) AS count
              FROM %s".$cond;

    $params = array($this->tablePoints);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      return (int)$res->fetchField();
    }

    return 0;
  }
}

?>
