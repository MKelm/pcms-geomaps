<?php
/**
 * Output class for geo maps content
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
 * @author Bastian Feder <info@papaya-cms.com> <rev. 0.18 extensions>
 */

/**
* Basic geo maps class
*/
require_once(dirname(__FILE__).'/base_geomaps.php');

/**
* Output for geo maps content
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
* @author Bastian Feder <info@papaya-cms.com> <extensions>
*/
class output_geomaps extends base_geomaps {

  /**
   * Param name for contents.
   *
   * @var string $paramName
   */
  var $paramName = 'gmps';

  /**
   * Keeps the current loaded api key.
   *
   * @var string $apiKey
   */
  var $apiKey = NULL;

  /**
   * A point to start at.
   *
   * @var array $centerPoint contains position and zoom data
   */
  var $centerPoint = array(
    'latitude'  => NULL,
    'longitude' => NULL,
    'zoom'      => NULL,
    'is_marker' => NULL
  );

  /**
   * Get the location of the java script files
   *
   * @return string path
   */
  function getScriptsPath() {
    return $this->getOption('scripts_path', '/');
  }

  /**
   * load the registered API key
   *
   * @param integer $mapsType type of the map to be embedded
   */
  function loadApiKey($mapsType) {
    if (!is_null($this->apiKey)) {
      return TRUE;
    }
    $key = $this->getDistinctKey($_SERVER['HTTP_HOST'], $mapsType, TRUE);
    if (!is_null($key)) {
      $this->apiKey = $key;
      return TRUE;
    }
    return FALSE;
  }

  function getFolderId($folderParam) {
    // get folder by first array value
    if (!is_null($folderParam)) {
      $folderId = array_values($folderParam);
      if (isset($folderId[0])) {
        return $folderId[0];
      }
    }
    return NULL;
  }

  function getFirstMarkerData($folderParam) {

    $folderId = $this->getFolderId($folderParam);
    // get first marker by folder to set center point data
    if (!is_null($folderId) && $this->loadMarkers($folderId, 1, 0)) {
      if (is_array($this->markers) && $this->markersCount > 0) {
        $markers = array_values($this->markers);
        if (isset($markers[0]) && is_array($markers[0])
            && count($markers[0]) > 0) {
          return $markers[0];
        }
      }
    }
    return NULL;
  }

  /**
   * Set a point to start at.
   *
   * @param reference $data content module data
   * @param array $folderParam Folder parameter name with value
   * @return boolean $result status
   */
  function setCenterPoint(&$data, $folderParam = NULL) {
    $result = FALSE;

    if (isset($data['center_first_marker']) && $data['center_first_marker'] == 1) {
      $firstMarker = $this->getFirstMarkerData($folderParam);
      if (!is_null($firstMarker)) {
        $this->centerPoint['latitude']  = $firstMarker['marker_lat'];
        $this->centerPoint['longitude'] = $firstMarker['marker_lng'];
        $this->centerPoint['is_marker'] = TRUE;
        $result = TRUE;
      }
    }

    if ($result === FALSE) {
      // or use default data
      $this->centerPoint['latitude']  = (isset($data['center_lat'])) ? $data['center_lat'] : 0;
      $this->centerPoint['longitude'] = (isset($data['center_lng'])) ? $data['center_lng'] : 0;
      $this->centerPoint['is_marker'] = FALSE;
      $result = TRUE;
    }
    $this->centerPoint['zoom'] = (isset($data['center_zoom'])) ? $data['center_zoom'] : 6;

    return $result;
  }

  function getGeoMapXML(&$data, $type, $folderParam = NULL) {
    $xml = '';

    if ($this->loadApiKey($type) && is_array($data) && count($data) > 0) {

      $width = (isset($data['map_width'])) ? $data['map_width'] : NULL;
      $height = (isset($data['map_height'])) ? $data['map_height'] : NULL;

      $id           = md5($this->paramName.microtime());
      $scriptsPath  = $this->getScriptsPath();
      $coorMode     = (isset($data['coor_mode'])) ? $data['coor_mode'] : NULL;
      $mapType      = (isset($data['map_type'])) ? $data['map_type'] : NULL;
      $noScriptText = (isset($data['noscript_text'])) ? $data['noscript_text'] : NULL;

      $ctrlBasic    = (isset($data['ctrl_basic'])) ? $data['ctrl_basic'] : NULL;
      $ctrlScale    = (isset($data['ctrl_scale'])) ? $data['ctrl_scale'] : NULL;
      $ctrlPan      = (isset($data['ctrl_pan'])) ? $data['ctrl_pan'] : NULL;
      $ctrlZoom     = (isset($data['ctrl_zoom'])) ? $data['ctrl_zoom'] : NULL;
      $ctrlType     = (isset($data['ctrl_type'])) ? $data['ctrl_type'] : NULL;
      $ctrlOverview = (isset($data['ctrl_overview'])) ? $data['ctrl_overview'] : NULL;

      $this->setCenterPoint($data, $folderParam);

      // geo map xml header / defaults
      $xml = sprintf('<geomap type="%d" api-key="%s">'.LF.
                     '<size width="%d" height="%d" />'.LF.
                     '<options id="%s" coor-mode="%d" map-type="%s" scripts-path="%s" />'.LF.
                     '<controls basic="%s" scale="%s" pan="%s" zoom="%d" type="%s" overview="%s" />'.LF.
                     '<center lat="%f" lng="%f" zoom="%d" permalink="%s" />'.LF.
                     '<no-script-text>%s</no-script-text>'.LF,
        $type, $this->apiKey['key_value'],
        $width, $height,
        $id, $coorMode, $mapType, papaya_strings::escapeHTMLChars($scriptsPath),
        $ctrlBasic, $ctrlScale, $ctrlPan, $ctrlZoom, $ctrlType, $ctrlOverview,
        $this->centerPoint['latitude'], $this->centerPoint['longitude'], $this->centerPoint['zoom'],
        papaya_strings::escapeHTMLChars($this->getPermaLinkUrl($data, $type)),
        papaya_strings::escapeHTMLChars($noScriptText)
      );

      $xml .= $this->getTripPlannerLinkXML($data, $type, $folderParam, FALSE);

      // geo maps static image (optional)
      if (isset($data['static_map']) && $data['static_map'] == 1) {
        $xml .= $this->getStaticMapXML($data, $type, $folderParam);
      }

      $xml .= $this->getGeoMapMarkersXML($data, $folderParam);

      // geo map xml footer
      $xml .= '</geomap>'.LF;

    }
    return $xml;
  }


  function getGeoMapMarkersXML(&$data, $folderParam) {
    $xml = '';

    // data url
    $pageId = (isset($data['marker_pageid'])) ? $data['marker_pageid'] : NULL;
    $viewMode = (isset($data['marker_viewmode'])) ? $data['marker_viewmode'] : NULL;

    // polyline mode
    $polyline = (isset($data['marker_polyline'])) ? $data['marker_polyline'] : NULL;
    $polylineColor = (isset($data['marker_polyline_color'])) ? $data['marker_polyline_color'] : NULL;
    $polylineSize = (isset($data['marker_polyline_size'])) ? $data['marker_polyline_size'] : NULL;

    // settings / options
    $mode = (isset($data['marker_mode'])) ? $data['marker_mode'] : NULL;
    $action = (isset($data['marker_action'])) ? $data['marker_action'] : NULL;
    $rotation = (isset($data['marker_rotation'])) ? $data['marker_rotation'] : 0;
    $description = (isset($data['marker_description'])) ? $data['marker_description'] : 0;
    $zoomFocus = (isset($data['marker_zoom_focus'])) ? $data['marker_zoom_focus'] : 0;
    $markerColor = (isset($data['marker_color'])) ? $data['marker_color'] : 0;
    // geo map markers data
    if ($pageId > 0 && is_array($folderParam) && count($folderParam) == 1) {
      // get data url
      $dataUrl = $this->getWebLink($pageId, NULL, $viewMode,
        $folderParam, $this->paramName);
      // set xml
      $xml = sprintf('<markers url="%s" mode="%s" action="%s" rotation="%d" description="%d" zoom-focus="%d" color="%s">'.LF.
                     '<polyline active="%d" color="%s" size="%d" />'.LF.
                     '</markers>'.LF,
        papaya_strings::escapeHTMLChars($dataUrl), $mode, $action, $rotation,
        $description, $zoomFocus, $markerColor,
        $polyline, $polylineColor, $polylineSize
      );
    }
    return $xml;
  }

  /**
   * generates an URL linking to a static map
   *
   * @param array_reference $data
   * @return string
   */
  function getStaticMapXML(&$data, $type, $folderParam) {

    $force = (isset($data['static_map_force'])) ? $data['static_map_force'] : 0;

    // get image url and alternative text
    $imageUrl     = $this->getStaticMapImageUrl($data, $type, $folderParam);
    $imageAltText = (isset($data['static_img_alt_text'])) ? $data['static_img_alt_text'] : NULL;
    $imageWidth = (isset($data['map_width'])) ? $data['map_width'] : NULL;
    $imageHeight = (isset($data['map_height'])) ? $data['map_height'] : NULL;

    $linkUrl    = $this->getPermaLinkUrl($data, $type, TRUE);
    $linkTarget = (isset($data['static_link_target'])) ? $data['static_link_target'] : NULL;

    // return xml
    $xml = sprintf('<static force="%d">'.LF.
                   '<image url="%s" text="%s" width="%d" height="%d" />'.LF.
                   '<link url="%s" target="%s" />'.LF,
      (int)$force,
      papaya_strings::escapeHTMLChars($imageUrl),
      papaya_strings::escapeHTMLChars($imageAltText),
      $imageWidth, $imageHeight,
      papaya_strings::escapeHTMLChars($linkUrl),
      papaya_strings::escapeHTMLChars($linkTarget)
    );

    $xml .= $this->getTripPlannerLinkXML($data, $type, $folderParam, TRUE);

    $xml .= '</static>'.LF;
    return $xml;
  }

  function getStaticMapImageUrl(&$data, $type, $folderParam) {
    switch ($type) {
    case 0: // google

      if ($this->loadApiKey($type)) {

        $staticMapWidth = (isset($data['map_width'])) ? $data['map_width'] : NULL;
        $staticMapHeight = (isset($data['map_height'])) ? $data['map_height'] : NULL;
        $staticMapType = (isset($data['static_map_type'])) ? $data['static_map_type'] : NULL;

        /*
         * http://maps.google.com/staticmap?center=40.714728,-73.998672&
         * zoom=14&size=512x512&maptype=mobile&
         * markers=40.702147,-74.015794,blues%7C40.711614,-74.012318,greeng%7C40.718217,-73.998284,redc&
         * key=MAPS_API_KEY
         */
        return sprintf('http://maps.google.com/staticmap?key=%s&center=%f,%f&zoom=%d&'.
                       'size=%s&maptype=%s&%s',
         $this->apiKey['key_value'],
         $this->centerPoint['latitude'], $this->centerPoint['longitude'], $this->centerPoint['zoom'],
         $staticMapWidth.'x'.$staticMapHeight, $staticMapType,
         $this->getMarkersUriString($data, $folderParam));
      }
      break;
    case 1: // yahoo

      if ($this->loadApiKey($type)) {
        $centerZoom = ($this->centerPoint['zoom'] < 13) ? $this->centerPoint['zoom'] : 12;

        $staticMapWidth = (isset($data['map_width'])) ? $data['map_width'] : NULL;
        $staticMapHeight = (isset($data['map_height'])) ? $data['map_height'] : NULL;
        $staticMapType = (isset($data['static_map_type'])) ? $data['static_map_type'] : NULL;

        $request = sprintf('http://api.local.yahoo.com/MapsService/V1/mapImage'.
                           '?appid=%s&latitude=%f&longitude=%f&zoom=%d'.
                           '&image_width=%d&image_height=%d&image_type=%s&output=php',
          $this->apiKey['key_value'],
          $this->centerPoint['latitude'], $this->centerPoint['longitude'], $centerZoom,
          $staticMapWidth, $staticMapHeight, $staticMapType
        );

        $response = file_get_contents($request);
        if ($response !== false) {
          $phpObj = unserialize($response);
          return $phpObj['Result'];
        }
      }
      break;
    default:
    }

    return '';
  }

  /**
   * Gets a permalink link url.
   *
   * @param reference $data content module data
   * @param integer $type map api type
   * @param boolean $static Use a static context
   * @return string|NULL link url or nothing
   */
  function getPermaLinkUrl(&$data, $type, $static = FALSE) {
    switch ($type) {
    case 0: // google

      $output     = ($static === TRUE) ? 'html' : '';

      // get image link url and link target
      $linkTpl = 'http://maps.google.com/maps?f=q&source=s_q&output=%s&q=%f,%f&z=%d';
      return sprintf($linkTpl, $output,
        $this->centerPoint['latitude'], $this->centerPoint['longitude'], $this->centerPoint['zoom']);
      break;
    case 1: // yahoo

      if ($static === FALSE) {  // no html version available!
        $centerZoom = ($this->centerPoint['zoom'] < 13) ? $this->centerPoint['zoom'] : 12;

        $mapType = 'm';
        if (isset($data['map_type'])) {
          switch ($data['map_type']) {
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
        }

        // get image link url and link target
        $linkTpl = 'http://maps.yahoo.com/maps?#mvt=%s&lat=%f&lon=%f&zoom=%d';
        return sprintf($linkTpl, $mapType,
          $this->centerPoint['latitude'], $this->centerPoint['longitude'], $centerZoom);
      }
      break;
    default:
    }

    return '';
  }

  /**
   * Gets a trip planner link url by extending the perma link url.
   *
   * @param reference $data content module data
   * @param integer $type map api type
   * @param array $folderParam markers folder parameter with value
   * @param boolean $static Use a static context
   * @return string|NULL link url or nothing
   */
  function getTripPlannerLinkUrl(&$data, $type, $folderParam, $static = FALSE) {

    // get first marker adress by folder
    $firstMarker = $this->getFirstMarkerData($folderParam);
    if (!is_null($firstMarker)) {
      $address = papaya_strings::escapeHTMLChars($firstMarker['marker_address']);
    }

    // get trip planner link by adding destination address to permalink
    if (!empty($address)) {
      $permaLink = $this->getPermaLinkUrl($data, $type, $static);
      if (!empty($permaLink)) {
        switch ($type) {
        case 0: // google
          return $permaLink.sprintf('&daddr=%s', $address);
          break;
        case 1: // yahoo
          return $permaLink.sprintf('&q2=%s', $address);
          break;
        }
      }
    }

    return NULL;
  }

  function getTripPlannerLinkXML(&$data, $type, $folderParam, $static = FALSE)  {
    if (isset($data['trip_planner']) && !empty($data['trip_planner'])) {

      $linkUrl = $this->getTripPlannerLinkUrl($data, $type, $folderParam, $static);
      if (!is_null($linkUrl)) {
        return sprintf('<trip-planner href="%s" static="%d">%s</trip-planner>'.LF,
          papaya_strings::escapeHTMLChars($linkUrl),
          (int)$static,
          papaya_strings::escapeHTMLChars($data['trip_planner'])
        );
      }
    }
    return NULL;
  }

  /**
   * fetches set markers and serializes their data to be used in query string.
   *
   * currently not implemented!!
   *
   * @param array reference $data
   * @return string
   */
  function getMarkersUriString(&$data, $folderParam) {
    $this->loadMarkers($this->getFolderId($folderParam));

    $markers = 'markers=%s&';
    $separator = '%7C'; // equals "|"

    $positions = NULL;
    $geoData = array();

    $dataMarkerColor = (isset($data['marker_color'])) ? $data['marker_color'] : 'red';
    $colorCounter = 0;
    $markerColor = 'red';
    $markerColors = array(
      'black', 'brown', 'green', 'purple', 'yellow', 'blue',
      'gray', 'orange', 'red', 'white'
    );

    if (is_array($this->markers) && $this->markersCount > 0) {
      $decoration = ''; // todo
      $size = 'default'; // todo

      foreach ($this->markers as $currentMarker) {
        if ($dataMarkerColor == 'rotate') {
          $markerColor = $markerColors[$colorCounter++];
          if ($colorCounter >= count($markerColors)) {
            $colorCounter = 0;
          }
        } else {
         $markerColor = papaya_strings::escapeHTMLChars($dataMarkerColor);
        }

        $marker[] = sprintf('%f,%f,%s%s%s',
          $currentMarker['marker_lat'],
          $currentMarker['marker_lng'],
          $size,
          $markerColor,
          ($size == 'mid') ? $decoration : ''
        );
      }

      return sprintf($markers, join($separator, $marker));
    }
    return '';
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
        if ($data == $id) {
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

}
