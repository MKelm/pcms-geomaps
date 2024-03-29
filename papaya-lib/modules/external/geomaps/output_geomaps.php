<?php
/**
 * Output class for geo maps content
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
 * Basic geo maps class
 */
require_once(dirname(__FILE__).'/base_geomaps.php');

/**
 * Output for geo maps content
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@shrt.ws>
 */
class output_geomaps extends base_geomaps {

  /**
   * Param name for contents.
   *
   * @var string $paramName
   */
  var $paramName = 'gmps';

  /**
   * Contains base settings, options, marker and static map settings.
   *
   * @var arra $data
   */
  var $data = NULL;

  /**
   * Modules db table to load dynamic image modules
   *
   * @var string $tableModules
   */
  var $tableModules = PAPAYA_DB_TBL_MODULES;

  /**
   * Module groups db table to load dynamic image modules
   *
   * @var string $tableModuleGroups
   */
  var $tableModuleGroups = PAPAYA_DB_TBL_MODULEGROUPS;

  /**
   * Main constructor
   */
  function __construct() {
    parent::__construct();

    // intialize data
    $this->data = array(
      'base' => NULL, // api settings and general stuff
      'settings'  => NULL, // map settings
      'markers' => NULL, // markers settings
      'static' => NULL // static map settings
    );
  }

  /**
   * PHP4 constructor
   */
  function output_geomaps() {
    $this->__construct();
  }

  /**
   * Get first marker data.
   *
   * @param integer $folderId markers folder to load from
   * @return array|NULL Marker data or nothing.
   */
  function getFirstMarkerData($folderId) {
    // get first marker by folder to set center point data
    if (!is_null($folderId) && $this->loadMarkers($folderId, 1, 0)) {
      if (is_array($this->markers) && $this->markersCount > 0) {
        $marker = array_shift($this->markers);
        $this->markers = NULL;
        if (is_array($marker) && count($marker) > 0) {
          return $marker;
        }
      }
    }
    return NULL;
  }

  /**
   * Sets api data and general stuff.
   *
   * @param integer $apiType Select a valid api type id.
   * @param integer $coorMode Activate coordinates mode or not.
   * @param string $noScriptText Default text to show if no script is avail.
   * @param string $linksTarget Set target links in output.
   * @return boolean Status
   */
  function setBaseData($apiType, $coorMode, $noScriptText, $linksTarget) {

    $apiKey = $this->getDistinctKey($_SERVER['HTTP_HOST'], $apiType, TRUE);
    if ((!empty($apiKey['key_value']) || $apiType == 2) &&
        isset($this->apiTypeNames[$apiType])) {

      $this->data['base'] = array(
        'id' => md5($this->paramName.microtime()), // string id
        'api' => array(
          'key' => (!empty($apiKey['key_value'])) ?
            $apiKey['key_value'] : '', // string key
          'type' => $this->apiTypeNames[$apiType], // string type name
        ),
        'coor_mode' => $coorMode, // int yes/no
        'scripts_path' =>
          $this->getOption('scripts_path', '/papaya-script/geomaps/'), // string
        'no_script_text' => $noScriptText, // string text
        'links_target' => $linksTarget // string
      );
      return TRUE;

    } else {
      // Set no API key error message.
      if (isset($this->apiTypeTitles[$apiType])) {
        $msg = sprintf('No API key has been set for %s.',
          $this->apiTypeTitles[$apiType]);
      } else {
        $msg = 'No API key has been set.';
      }
      $this->logMsg(PAPAYA_LOGTYPE_MODULES, MSG_ERROR, $msg);
    }

    $this->data['base'] = NULL;
    return FALSE;
  }

  /**
   * Sets map settings like map type, width, height, active control elements ...
   *
   * @param string $type Map type depends on api type.
   * @param integer $width
   * @param integer $height
   * @param array $controls Control elements depend on api type.
   * @param integer $tripPlannerActive
   * @param integer $tripPlannerCaption
   * @param float $centerLat Latitude to center map.
   * @param float $centerLng Longitude to center map.
   * @param int $zoom Zoom range depends on api type.
   * @param int $centerByMarker
   * @param int $markersFolderId (optional)
   * @return boolean Status
   */
  function setSettingsData($type, $width, $height, $controls,
                           $tripPlannerActive, $tripPlannerCaption,
                           $zoom, $centerLat, $centerLng,
                           $centerMode = NULL, $markersFolderId = NULL) {

    // depends on base data
    if (!empty($this->data['base']) && !empty($type) &&
        $width > 0 && $height > 0 &&
        is_array($controls) && count($controls) > 0) {

      // get another center mode if possible
      $centerPoint = NULL;
      switch ($centerMode) {
      case 'first_marker':
        // get the location of the first marker if available to center map
        $centerPoint = $this->getFirstMarkerData($markersFolderId);
        break;
      case 'all_markers':
        // get center position of all markers
        $centerPoint = $this->getMarkersCenterPoint($markersFolderId);
        break;
      default:
        // use default parameters
      }
      // overwrite center position
      if (!is_null($centerPoint)
          && isset($centerPoint['marker_lat'])
          && $centerPoint['marker_lat'] > -90
          && $centerPoint['marker_lat'] < 90
          && isset($centerPoint['marker_lng'])
          && $centerPoint['marker_lng'] > -180
          && $centerPoint['marker_lng'] < 180) {

        $centerLat = $centerPoint['marker_lat'];
        $centerLng = $centerPoint['marker_lng'];
      }

      // fix zoom value
      $zoom = ($zoom > 0) ? $zoom : 1;
      switch ($this->data['base']['api']['type']) {
      case $this->apiTypeNames[0]: // google v2
      case $this->apiTypeNames[3]: // google v3
        $zoom = ($zoom < 19) ? $zoom : 18;
        break;
      case $this->apiTypeNames[1]: // yahoo
        $zoom = ($zoom < 18) ? $zoom : 17;
        break;
      }

      $this->data['settings'] = array(
        'type' => $type, // string map type
        'width' => $width, // int width
        'height' => $height, // int height
        'controls' => $controls, // array contents by api type
        'trip_planner' => array(
          'active' => $tripPlannerActive, // int yes/no
          'caption' => $tripPlannerCaption, // string
        ),
        'zoom' => $zoom, // int
        'center' => array(
          'lat' => $centerLat, // float latitude
          'lng' => $centerLng, // float longitude
          'mode' => $centerMode // string
        )
      );
      return TRUE;
    }

    $this->data['settings'] = NULL;
    return FALSE;
  }

  /**
   * Set markers data like data page, mode, click action, polyline ...
   *
   * @param integer $pageId Page which contains markers data.
   * @param string $viewMode View mode to get markers data.
   * @param string $ressourceType set a specific ressource type, i.e. folder
   * @param integer $ressourceId i.e. a folder id to get markers from.
   * @param string $mode Mode to show markers.
   * @param int $rotation Rotate markers.
   * @param int $showDescription Show description.
   * @param string $descMouseAction Mouse action to open description.
   * @param int $zoomFocus Zoom markers into focus.
   * @param string $color
   * @param return Status
   */
  function setMarkersData($pageId, $viewMode, $ressourceType, $ressourceId,
                          $color, $mode, $rotation,
                          $showDescription, $mouseDescAction, $zoomIntoFocus,
                          $polylineActive, $polylineColor, $polylineSize,
                          $ressourceParams = NULL, $clusterer = 0) {

    if (!empty($mouseDescAction)) {

      $this->data['markers'] = array(
        'ressource_type' => $ressourceType,
        'ressource_id' => $ressourceId, // int id
        'ressource_params' => $ressourceParams, // optional ressource params
        'mode' => $mode, // string mode
        'rotation' => $rotation, // int yes/no
        'show_description' => $showDescription, // int yes/no
        'mouse_desc_action' => $mouseDescAction, // string action
        'zoom_into_focus' => $zoomIntoFocus, // int yes/no
        'color' => $color, // string color
        'data_page' => array(
          'page_id' => $pageId, // int id, optional to load from external page
          'view_mode' => $viewMode // string mode, optional to use specific mode
        ),
        'polyline' => array(
          'active' => $polylineActive, // int yes/no
          'color' => $polylineColor, // string color
          'size' => $polylineSize // int size
        ),
        'clusterer' => $clusterer,
        'loaded_markers' => FALSE
      );

      // get onload markers if no external page has been set
      if ($pageId == 0 && $mode != 'hide' &&
          $ressourceType == 'folder' && $ressourceId > 0) {
        if ($this->loadMarkers($ressourceId) === TRUE) {
          $this->data['markers']['loaded_markers'] = TRUE;
        }
      }
      return TRUE;
    }

    $this->data['markers'] = NULL;
    return FALSE;
  }

  /**
   * Set data for static map images.
   *
   * @param int $force Force static map image output.
   * @param string $type Depends on api type.
   * @param string $alternativeText Alt. text for static map images.
   * @return Status
   */
  function setStaticData($force, $type, $alternativeText,
                         $markersColor, $markersSize, $markersDecoration) {

    if (!empty($type)) {

      $this->data['static'] = array(
        'force' => $force, // int yes/no
        'type' => $type, // string static map type
        'alternative_text' => $alternativeText, // string image alternative text
        'markers' => array(
          'color' => $markersColor, // string
          'size' => $markersSize, // string
          'decoration' => $markersDecoration // string
        )
      );
      return TRUE;
    }

    $this->data['static'] = NULL;
    return FALSE;
  }

  /**
   * Get base xml with api data and general stuff.
   *
   * @return string $xml
   */
  function getBaseXml() {
    $xml = '';

    // depends on base data
    if (is_array($this->data['base']) && count($this->data['base']) > 0) {

      $xml .= sprintf(
        '<base id="%s" coor-mode="%d" scripts-path="%s" links-target="%s">'.LF.
        '<api key="%s" type="%s" />'.LF.
        '<no-script-text>%s</no-script-text>'.LF.
        '</base>'.LF,
        $this->data['base']['id'],
        $this->data['base']['coor_mode'],
        papaya_strings::escapeHTMLChars($this->data['base']['scripts_path']),
        $this->data['base']['links_target'],
        papaya_strings::escapeHTMLChars($this->data['base']['api']['key']),
        $this->data['base']['api']['type'],
        papaya_strings::escapeHTMLChars($this->data['base']['no_script_text'])
      );
    }

    return $xml;
  }

  /**
   * Get settings xml with data like map type, width, height, controls ...
   *
   * @return string $xml
   */
  function getSettingsXml() {
    $xml = '';

    // depends on base and settings data
    if (!empty($this->data['base']) && !empty($this->data['settings'])) {

      // get controls xml related to api type
      switch ($this->data['base']['api']['type']) {
      case $this->apiTypeNames[0]: // google v2
      case $this->apiTypeNames[3]: // google v3
        $controlsXml = sprintf(
          '<controls basic="%d" type="%s" overview="%s" scale="%s" />'.LF,
          $this->data['settings']['controls']['basic'],
          $this->data['settings']['controls']['type'],
          $this->data['settings']['controls']['overview'],
          $this->data['settings']['controls']['scale']
        );
        break;
      case $this->apiTypeNames[1]: // yahoo
        $controlsXml = sprintf(
          '<controls type="%s" pan="%s" zoom="%s" />'.LF,
          $this->data['settings']['controls']['type'],
          $this->data['settings']['controls']['pan'],
          $this->data['settings']['controls']['zoom']
        );
        break;
      case $this->apiTypeNames[2]: // open layers
        $controlsXml = sprintf(
          '<controls type="%s" pan="%s" zoom="%s" />'.LF,
          $this->data['settings']['controls']['type'],
          $this->data['settings']['controls']['pan'],
          $this->data['settings']['controls']['zoom']
        );
        break;
      }

      if (isset($controlsXml)) {
        $xml .= sprintf(
          '<settings type="%s" width="%d" height="%d" zoom="%d">'.LF.
          '%s'.LF.
          '<center lat="%f" lng="%f" mode="%s" />'.LF.
          '</settings>'.LF,
          $this->data['settings']['type'],
          $this->data['settings']['width'],
          $this->data['settings']['height'],
          $this->data['settings']['zoom'],
          $controlsXml,
          $this->data['settings']['center']['lat'],
          $this->data['settings']['center']['lng'],
          $this->data['settings']['center']['mode']
        );
      }
    }

    return $xml;
  }

  function getMarkersBaseXML() {
    $xml = '';
    $mt = microtime();
    if (is_array($this->markers) && $this->markersCount > 0) {
      foreach ($this->markers as $marker) {
        $desc = papaya_strings::ensureUTF8($marker['marker_desc']);

        // get marker icon image if available
        if (!empty($marker['marker_icon'])) {
          if (!isset($mediaDB)) {
            include_once(PAPAYA_INCLUDE_PATH.'system/base_mediadb.php');
            $mediaDB = base_mediadb::getInstance();
            $loadedMarkerIcons = array();
          }
          if (!isset($loadedMarkerIcons[$marker['marker_icon']])) {
            if (checkit::isGUID($marker['marker_icon'], TRUE)) {
              $iconFile = $mediaDB->getFileName($marker['marker_icon']);
              if (!empty($iconFile)) {
                $iconSize = getimagesize($iconFile);
                $iconMediaFile = $this->getAbsoluteURL(
                  $this->getWebMediaLink($marker['marker_icon'], 'media')
                );
              }
            }
            if (!empty($iconMediaFile) &&
                (!empty($iconSize[0]) && !empty($iconSize[1]))) {
              $loadedMarkerIcons[$marker['marker_icon']] = array(
                $iconMediaFile, $iconSize[0], $iconSize[1]
              );
            }
          }
        }
        if (!empty($loadedMarkerIcons[$marker['marker_icon']])) {
          $iconXML = sprintf(
            '<icon src="%s" width="%d" height="%d" />'.LF,
            $loadedMarkerIcons[$marker['marker_icon']][0],
            $loadedMarkerIcons[$marker['marker_icon']][1],
            $loadedMarkerIcons[$marker['marker_icon']][2]
          );
        } else {
          $iconXML = '';
        }

        $xml .= sprintf(
          '<marker title="%s" lat="%s" lng="%s">'.LF.
          '<description>%s</description>'.LF.
          '%s</marker>'.LF,
          papaya_strings::escapeHTMLChars($marker['marker_title']),
          $marker['marker_lat'], $marker['marker_lng'],
          $this->getXHTMLString($desc, FALSE),
          $iconXML
        );
      }
    }
    return $xml;
  }

  /**
   * Get markers xml with data like data page, mode, click action, polyline ...
   *
   * @return string $xml
   */
  function getMarkersXml() {
    $xml = '';

    // depends on base / settings and markers data
    if (!empty($this->data['base']) && !empty($this->data['settings'])
        && !empty($this->data['markers'])) {
      $data = &$this->data['markers'];

      if ($this->data['markers']['data_page']['page_id'] > 0) {
        // get markers params
        $params = array(
          'ressource_type' => $this->data['markers']['ressource_type'],
          'ressource_id' => $this->data['markers']['ressource_id'],
          'base_kml' => 1
        );
        if (is_array($this->data['markers']['ressource_params']) &&
            count($this->data['markers']['ressource_params']) > 0) {
          $params = array_merge($params, $this->data['markers']['ressource_params']);
        }
        $params = array('gmps' => $params);
        include_once(PAPAYA_INCLUDE_PATH.'system/Papaya/Request/Parameters.php');
        $paramsObj = new PapayaRequestParameters();
        $paramsObj->set($params, NULL, '[]');
        $queryString = '?'.$paramsObj->getQueryString('[]');

        // get data page url
        $dataPageUrl = papaya_strings::escapeHTMLChars(
          $this->getWebLink(
            $this->data['markers']['data_page']['page_id'], NULL,
            $this->data['markers']['data_page']['view_mode']
          ).$queryString
        );
        $dataPageUrlXML = sprintf('<data-page url="%s" />'.LF, $dataPageUrl);
      } else {
        $dataPageUrlXML = '';
      }

      // get markers base xml if available
      if ($this->data['markers']['loaded_markers'] === TRUE) {
        $markersBaseXML = $this->getMarkersBaseXML();
      } else {
        $markersBaseXML = '';
      }

      // set xml
      $xml = sprintf(
        '<markers mode="%s" rotation="%d" color="%s" zoom-into-focus="%d"'.
        ' show-description="%d" mouse-desc-action="%s" clusterer="%d">'.LF.
        '%s<polyline active="%d" color="%s" size="%d" />'.LF.
        '%s</markers>'.LF,
        $this->data['markers']['mode'],
        $this->data['markers']['rotation'],
        $this->data['markers']['color'],
        $this->data['markers']['zoom_into_focus'],
        $this->data['markers']['show_description'],
        $this->data['markers']['mouse_desc_action'],
        $this->data['markers']['clusterer'],
        $dataPageUrlXML,
        $this->data['markers']['polyline']['active'],
        $this->data['markers']['polyline']['color'],
        $this->data['markers']['polyline']['size'],
        $markersBaseXML
      );
    }
    return $xml;
  }

  /**
   * Get static xml with data for static map images.
   *
   * @return string $xml
   */
  function getStaticXml() {
    $xml = '';

    // depends on base / settings and static data
    if (!empty($this->data['base']) && !empty($this->data['settings']) &&
        !empty($this->data['static'])) {

      $imageUrl = $this->getStaticImageUrl();

      // return xml
      $xml = sprintf(
        '<static force="%d" image="%s" alternative-text="%s">'.LF.
        '%s%s'.
        '</static>'.LF,
        $this->data['static']['force'],
        papaya_strings::escapeHTMLChars($imageUrl),
        papaya_strings::escapeHTMLChars(
          $this->data['static']['alternative_text']
        ),
        $this->getPermaLinkXml(TRUE),
        $this->getTripPlannerLinkXml(TRUE)
      );
    }

    return $xml;
  }

  /**
   * Gets an url to the static map image.
   *
   * @return string url or nothing
   */
  function getStaticImageUrl() {
    $url = '';

    // depends on base / settings and static data
    if (!empty($this->data['base']) && !empty($this->data['settings']) &&
        !empty($this->data['static'])) {

      switch ($this->data['base']['api']['type']) {
      case $this->apiTypeNames[0]: // google v2
      case $this->apiTypeNames[3]: // google v3

        $url = sprintf(
          'http://maps.googleapis.com/maps/api/staticmap?'.
          'key=%s&center=%f,%f&sensor=false&'.
          'zoom=%d&size=%s&maptype=%s&%s',
          $this->data['base']['api']['key'],
          $this->data['settings']['center']['lat'],
          $this->data['settings']['center']['lng'],
          $this->data['settings']['zoom'],
          $this->data['settings']['width'].'x'.$this->data['settings']['height'],
          $this->data['static']['type'],
          $this->getMarkersUriString()
        );

        break;
      case $this->apiTypeNames[1]: // yahoo

        $zoom = ($this->data['settings']['zoom'] < 13)
          ? $this->data['settings']['zoom'] : 12; // fix static zoom level

        $request = sprintf(
          'http://api.local.yahoo.com/MapsService/V1/mapImage'.
          '?appid=%s&latitude=%f&longitude=%f&zoom=%d'.
          '&image_width=%d&image_height=%d&image_type=%s&output=php',
          $this->data['base']['api']['key'],
          $this->data['settings']['center']['lat'],
          $this->data['settings']['center']['lng'],
          $zoom,
          $this->data['settings']['width'],
          $this->data['settings']['height'],
          $this->data['static']['type']
        );

        $response = file_get_contents($request);
        if ($response !== false) {
          $phpObj = unserialize($response);
          $url = $phpObj['Result'];
        }

        break;
      default:
      }
    }

    return $url;
  }

  /**
   * Gets a permalink link url.
   *
   * @param boolean $static Use a static context
   * @return string|NULL link url or nothing
   */
  function getPermaLinkUrl($static = FALSE) {
    $url = '';

    // depends on base / settings data
    if (!empty($this->data['base']) && !empty($this->data['settings']) &&
        $this->data['settings']['center']['lat'] > 0 &&
        $this->data['settings']['center']['lng'] > 0) {

      switch ($this->data['base']['api']['type']) {
      case $this->apiTypeNames[0]: // google v2
      case $this->apiTypeNames[3]: // google v3

        $output = ($static === TRUE) ? 'html' : '';

        if ($static == TRUE) {
          $zoom = floor($this->data['settings']['zoom'] / 2) -9;
          if ($zoom < 0) {
            $zoom = $zoom * -1;
          }
        } else {
          $zoom = $this->data['settings']['zoom'];
        }

        // get image link url and link target
        $linkTpl = 'http://maps.google.com/maps?f=q&source=s_q&'.
          'output=%s&q=%f,%f&zoom=%d';
        $url = sprintf($linkTpl, $output,
          $this->data['settings']['center']['lat'],
          $this->data['settings']['center']['lng'],
          $zoom);

        break;
      case $this->apiTypeNames[1]: // yahoo

        if ($static === FALSE) {  // no html version available!
          switch ($this->data['settings']['type']) {
          case 'YAHOO_MAP_SAT':
            $mapType = 's';
            break;
          case 'YAHOO_MAP_HYB':
            $mapType = 'h';
            break;
          case 'YAHOO_MAP_REG':
          default:
            $mapType = 'm';
            break;
          }

          // get image link url and link target
          $linkTpl = 'http://maps.yahoo.com/maps?#mvt=%s&'.
            'lat=%f&lon=%f&zoom=%d';
          $url = sprintf($linkTpl, $mapType,
            $this->data['settings']['center']['lat'],
            $this->data['settings']['center']['lng'],
            $this->data['settings']['zoom']);
        }

        break;
      default:
      }
    }

    return $url;
  }

  /**
   * Gets a permalink link xml.
   *
   * @param boolean $static Use a static context
   * @return string link xml or nothing
   */
  function getPermaLinkXml($static = FALSE)  {
    $xml = '';

    // depends on base and settings data
    if (!empty($this->data['base']) && !empty($this->data['settings'])) {

      $linkUrl = $this->getPermaLinkUrl($static);
      if (!empty($linkUrl)) {
        $xml = sprintf(
          '<permalink url="%s" static="%d" />'.LF,
          papaya_strings::escapeHTMLChars($linkUrl),
          $static
        );
      }
    }

    return $xml;
  }

  /**
   * Gets a trip planner link url by extending the perma link url.
   *
   * @param boolean $static Use a static context
   * @return string link url or nothing
   */
  function getTripPlannerLinkUrl($static = FALSE) {
    $url = '';

    // depends on base / settings / markers data
    if (!empty($this->data['base']) && !empty($this->data['settings']) &&
        !empty($this->data['markers']) &&
        isset($this->data['markers']['ressource_type']) &&
        $this->data['markers']['ressource_type'] == 'folder' &&
        isset($this->data['markers']['ressource_id'])) {

      // get first marker adress by folder
      $firstMarkerData = $this->getFirstMarkerData(
        $this->data['markers']['ressource_id']
      );
      if (!is_null($firstMarkerData)) {
        $markerAddressFields = array(
          array('marker_addr_street', 'marker_addr_house'),
          array('marker_addr_zip', 'marker_addr_city'),
          'marker_addr_country'
        );
        $mergedAddressFields = '';
        foreach ($markerAddressFields as $key => $fields) {
          if ($mergedAddressFields !== '') {
            $mergedAddressFields .= ', ';
          }
          if (!is_array($fields)) {
            if (!empty($firstMarkerData[$fields])) {
              $mergedAddressFields .= $firstMarkerData[$fields];
            }
          } elseif (count($fields) > 0) {
            foreach ($fields as $field) {
              if ($mergedAddressFields !== '') {
                $mergedAddressFields .= ' ';
              }
              if (!empty($firstMarkerData[$field])) {
                $mergedAddressFields .= $firstMarkerData[$field];
              }
            }
          }
        }
        $address = papaya_strings::escapeHTMLChars($mergedAddressFields);
      }

      // get trip planner link by adding destination address to permalink
      if (!empty($address)) {
        $permaLink = $this->getPermaLinkUrl($static);
        if (!empty($permaLink)) {
          switch ($this->data['base']['api']['type']) {
          case $this->apiTypeNames[0]: // google v2
          case $this->apiTypeNames[3]: // google v3
            $url = $permaLink.sprintf('&daddr=%s', $address);
            break;
          case $this->apiTypeNames[1]: // yahoo
            $url = $permaLink.sprintf('&q2=%s', $address);
            break;
          }
        }
      }
    }

    return $url;
  }

  /**
   * Gets a trip planner link xml.
   *
   * @param boolean $static Use a static context
   * @return string link xml or nothing
   */
  function getTripPlannerLinkXml($static = FALSE)  {
    $xml = '';

    // depends on base / settings / markers data
    if (!empty($this->data['base']) && !empty($this->data['settings']) &&
        !empty($this->data['markers'])) {
      $data = &$this->data['settings']['trip_planner'];

      if ($data['active'] == 1 && !empty($data['caption'])) {

        $linkUrl = $this->getTripPlannerLinkUrl($static);
        if (!empty($linkUrl)) {
          $xml = sprintf(
            '<trip-planner caption="%s" url="%s" static="%d" />'.LF,
            papaya_strings::escapeHTMLChars($data['caption']),
            papaya_strings::escapeHTMLChars($linkUrl),
            (int)$static
          );
        }
      }
    }
    return $xml;
  }

  /**
   * Fetches set markers and serializes their data to be used in query string.
   * Google Maps only!
   *
   * @return string
   */
  function getMarkersUriString() {
    $uri = '';

    // depends on base / markers / static data
    if (!empty($this->data['base']) && !empty($this->data['markers']) &&
        !empty($this->data['static'])) {

      // load markers
      if (!(is_array($this->markers) && $this->markersCount > 0)) {
        // $this->data['markers']['ressource_id'] == folder!
        $this->loadMarkers($this->data['markers']['ressource_id']);
      }
      if (is_array($this->markers) && $this->markersCount > 0) {

        $markers = array();
        $separator = '%7C'; // equals "|"

        // initialize colors
        $dataColor = (!empty($this->data['static']['markers']['color']))
          ? $this->data['static']['markers']['color'] : 'red';
        if ($dataColor == 'rotate') {
	      // colors
          // 'black', 'brown', 'green', 'purple', 'yellow', 'blue',
          // 'gray', 'orange', 'red', 'white'
	      $color = 'red'; // rotation unavailable!
		} else {
		  $color = papaya_strings::escapeHTMLChars($dataColor);
		}

        // set markers size
        if ($this->data['static']['markers']['size'] != 'default') {
          $size = $this->data['static']['markers']['size'];
        } else {
          $size = '';
        }

        // set markers label (decoration)
        if (!empty($this->data['static']['markers']['decoration'])
            && $size == 'mid') {
          $label = strtoupper(
            $this->data['static']['markers']['decoration']
          );
        } else {
          $label = '';
        }

        foreach ($this->markers as $currentMarker) {
          // add marker to uri
          $marker[] = sprintf('%f,%f',
            $currentMarker['marker_lat'],
            $currentMarker['marker_lng']
          );
        }
        
        $colorString = !empty($color) ?
          sprintf('color:%s%s', $color, $separator) : '';
        
        $sizeString = !empty($size) ?
          sprintf('size:%s%s', $size, $separator) : '';
          
        $labelString = !empty($label) ?
          sprintf('label:%s%s', $label, $separator) : '';

        $uri = sprintf(
          'markers=%s%s%s%s&', 
          $colorString,
          $sizeString,
          $labelString,
          join($separator, $marker)
        );
      }
    }
    return $uri;
  }

  /**
   * Calculates the center coordinates of all available markers.
   *
   * @return array geo position
   */
  function getMarkersCenterPoint($markersFolderId) {
    $result = NULL;

    // depends on base / settings / markers data
    if (!empty($this->data['base'])) {

      // check available markers or load them
      if (!(is_array($this->markers) && $this->markersCount > 0)) {
        $this->loadMarkers($markersFolderId);
      }
      if (is_array($this->markers) && $this->markersCount > 0) {

        // get first geo data
        $marker = array_shift($this->markers);
        $minLat = $maxLat = floatval($marker['marker_lat']);
        $minLng = $maxLng = floatval($marker['marker_lng']);

        // assemble geo data
        foreach ($this->markers as $marker) {
          // get minima
          if ($minLat > floatval($marker['marker_lat'])) {
            $minLat = floatval($marker['marker_lat']);
          }
          if ($minLng > floatval($marker['marker_lng'])) {
            $minLng = floatval($marker['marker_lng']);
          }
          // get maxima
          if ($maxLat < floatval($marker['marker_lat'])) {
            $maxLat = floatval($marker['marker_lat']);
          }
          if ($maxLng < floatval($marker['marker_lng'])) {
            $maxLng = floatval($marker['marker_lng']);
          }
        }

        $result = array(
          'marker_lat' => ($maxLat-$minLat)/2 + $minLat,
          'marker_lng' => ($maxLng-$minLng)/2 + $minLng
        );
      }
    }

    return $result;
  }

  /**
   * generates a string representing a XHTML drop down menu with view modes
   *
   * @param string $name content of the node attribute 'name'
   * @param integer $currentFolder identifies the currently selected option
   * @param array $folders if set it will override the folders defined on object level
   * @return string XHTML code representing a XHTML drop down menu
   *
   * @see papaya_strings::escapeHTMLChars()
   */
  function getViewModesComboBox($name, $element, $data, $paramName) {
    $result = '';
    $selected = '';

    include_once(PAPAYA_INCLUDE_PATH.'system/papaya_alias_tree.php');
    $aliasTreeObj = &new papaya_alias_tree();
    $aliasTreeObj->loadViewModeList();

    $result .= sprintf(
      '<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
      $paramName, $name);

    if (isset($aliasTreeObj->viewModes) && is_array($aliasTreeObj->viewModes)
        && count($aliasTreeObj->viewModes) > 0) {
      foreach ($aliasTreeObj->viewModes as $id => $mode) {
        $selected = '';
        if ($data == $mode['viewmode_ext']) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf('<option value="%s"%s>%s</option>',
          $mode['viewmode_ext'], $selected,
          papaya_strings::escapeHTMLChars($mode['viewmode_ext'])
        );
      }
    }
    $result .= '</select>';
    return $result;
  }

  /**
   * generates a string representing a XHTML drop down menu with polyline colors
   *
   * @param string $name content of the node attribute 'name'
   * @param integer $currentFolder identifies the currently selected option
   * @param array $folders if set it will override the folders defined on object level
   * @return string XHTML code representing a XHTML drop down menu
   *
   * @see papaya_strings::escapeHTMLChars()
   */
  function getPolylineColorsComboBox($name, $element, $data, $paramName) {
    $result = '';
    $selected = '';

    $polylineColors = array(
      'orange'     => 'orange',
      'blue'       => 'blue',
      'lightblue'  => 'lightblue',
      'brown'      => 'brown',
      'green'      => 'green',
      'lightgreen' => 'lightgreen',
      'grey'       => 'grey',
      'black'      => 'black',
      'maroon'     => 'maroon',
      'purple'     => 'purple'
    );

    $result .= sprintf(
      '<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
      $paramName, $name);

    if (isset($polylineColors) && is_array($polylineColors)
        && count($polylineColors) > 0) {
      foreach ($polylineColors as $colorId => $color) {
        $selected = '';
        if ($data == $colorId) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf('<option value="%s"%s>%s</option>', $colorId,
          $selected, papaya_strings::escapeHTMLChars($color));
      }
    }
    $result .= '</select>';
    return $result;
  }

  function getDynamicImagesComboBox($name, $element, $data, $paramName) {
    $result = sprintf(
      '<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
      $paramName, $name);
    $result .= sprintf('<option value="">%s</option>', $this->_gt('Select'));

    $sql = "SELECT i.image_id, i.image_title
              FROM %s i
              JOIN %s m
                ON (m.module_guid = i.module_guid
                    AND m.module_type = 'image' AND m.module_active = 1)
              JOIN %s mg
                ON (mg.modulegroup_id = m.modulegroup_id
                    AND mg.modulegroup_title = '%s')
             ORDER BY i.image_title ASC";
    $params = array($this->tableDynImages,
      $this->tableModules, $this->tableModuleGroups, 'Geo maps');

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {

        $selected = '';
        if ($data == $row['image_id']) {
          $selected = ' selected="selected"';
        }

        $result .= sprintf('<option value="%s"%s>%s</option>', $row['image_id'],
          $selected, papaya_strings::escapeHTMLChars($row['image_title']));
      }
    }

    $result .= '</select>';
    return $result;
  }

}
