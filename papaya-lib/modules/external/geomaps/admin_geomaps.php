<?php
/**
* Administration backend for geo maps
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
* Basic geo maps class
*/
require_once(dirname(__FILE__).'/base_geomaps.php');

/**
* Administration backend for geo maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class admin_geomaps extends base_geomaps {

  /**
   * Show the marker dialog.
   *
   * @var boolean $showMarkerDialog
   */
  var $showMarkerDialog = TRUE;

  /**
   * List with local module glyph images.
   *
   * @var array $localImages
   */
  var $localImages = NULL;

  var $module      = NULL;
  var $images      = NULL;
  var $msgs        = NULL;
  var $layout      = NULL;
  var $authUser    = NULL;

  function __construct() {
    // set database table names by base object
    parent::__construct();
  }

  function admin_geomaps() {
    $this->__construct();
  }

  function initialize(&$module, &$images, &$msgs, &$layout, &$authUser) {

    // set system enviroment
    $this->module   = &$module;
    $this->images   = &$images;
    $this->msgs     = &$msgs;
    $this->layout   = &$layout;
    $this->authUser = &$authUser;

    // get / set session values
    $this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();
    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('folder_id');
    $this->initializeSessionParam('mode', array('folder_id', 'cmd'));
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);

    // set parameters
    if (!isset($this->params['folder_id'])) {
      $this->params['folder_id'] = 0;
    }

    // set local images
    $imagePath = 'module:'.$this->module->guid;
    $this->localImages = array(
      'export'    => $imagePath.'/export.png',
      'sort-asc'  => $imagePath.'/sort-asc.png',
      'sort-desc' => $imagePath.'/sort-desc.png'
    );

    // get basic contents
    if (!isset($this->params['mode'])) {
      $this->params['mode'] = '';
    }
    switch ($this->params['mode']) {
    case 1:
      // load keys for list view
      $this->loadKeys();
      break;
    default:
      // load folders for list view
      $this->loadFolders();
      $this->loadMarkers((isset($this->params['folder_id']))
        ? $this->params['folder_id'] : NULL);
      break;
    }
  }

  function execute() {
    if (!isset($this->params['mode'])) {
      $this->params['mode'] = '';
    }
    if (!isset($this->params['cmd'])) {
      $this->params['cmd'] = '';
    }
    switch ($this->params['mode']) {
    case 1:
      switch ($this->params['cmd']) {
      case 'add_key':
        if ($this->module->hasPerm(4, FALSE)) {
          $this->execAddKey();
        }
        break;
      case 'edit_key':
        if ($this->module->hasPerm(4, FALSE)) {
          $this->execEditKey();
        }
        break;
      case 'del_key':
        if ($this->module->hasPerm(4, FALSE)) {
          $this->execDelKey();
        }
        break;
      }
      break;
    default:
      switch ($this->params['cmd']) {
      case 'add_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execAddFolder();
        }
        break;
      case 'edit_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execEditFolder();
        }
        break;
      case 'del_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execDelFolder();
        }
        break;
      case 'add_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execAddMarker();
        }
        break;
      case 'edit_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execEditMarker();
        }
      case 'del_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execDelMarker();
        }
        break;
      case 'set_up_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execSetMarker(-1);
        }
        break;
      case 'set_down_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execSetMarker(1);
        }
        break;
      case 'sort_markers_asc':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execSortMarkers('ASC');
        }
        break;
      case 'sort_markers_desc':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->execSortMarkers('DESC');
        }
        break;
      case 'export_markers':
        if ($this->module->hasPerm(3, FALSE)) {
          $this->execExportMarkers();
        }
        break;
      case 'generate_spatial_polygon':
        if ($this->module->hasPerm(5, FALSE)) {
          $this->execGenerateSpatialPolygon();
        }
        break;
      case 'generate_spatial_points':
        if ($this->module->hasPerm(5, FALSE)) {
          $this->execGenerateSpatialPoints();
        }
        break;
      }
    }
  }

  /**
   * Exectute spatial polygon generation to perform further spatial validations.
   *
   * @return boolean generated?
   */
  function execGenerateSpatialPolygon() {
    $success = FALSE;

    if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0
        && isset($this->folders[$this->params['folder_id']])
        && isset($this->params['confirmed']) && $this->params['confirmed'] == 1) {

      if (TRUE === $this->generateSpatialPolygonsByFolders($this->params['folder_id'])) {
        $this->addMsg(MSG_INFO, sprintf(
          $this->_gt('The spatial polygon "%s" for "%s" (%d) has been generated.'),
          md5(sprintf('geomaps_folder_%d', $this->params['folder_id'])),
          $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']
        ));
      } else {
        $this->addMsg(MSG_INFO, sprintf(
          $this->_gt('An error occured. No spatial polygon for "%s" (%d) has been generated.'),
          $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']
        ));
      }
    }

    return $success;
  }

  /**
   * Exectute spatial points generation to perform further spatial calculations.
   *
   * @return boolean generated?
   */
  function execGenerateSpatialPoints() {
    $success = FALSE;

    if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0
        && isset($this->folders[$this->params['folder_id']])
        && isset($this->params['confirmed']) && $this->params['confirmed'] == 1) {

      if (TRUE === $this->generateSpatialPointsByFolders($this->params['folder_id'])) {
        $this->addMsg(MSG_INFO, sprintf(
          $this->_gt('The spatial points for "%s" (%d) has been generated.'),
          $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']
        ));
      } else {
        $this->addMsg(MSG_INFO, sprintf(
          $this->_gt('An error occured. No spatial points for "%s" (%d) has been generated.'),
          $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']
        ));
      }
    }

    return $success;
  }

  function execAddKey() {
    if ($this->initAddKeyDialog()) {
      if (isset($this->params['save']) && $this->params['save']
          && $this->addKeyDialog->modified()
          && $this->addKeyDialog->checkDialogInput()) {

        if (!$this->validKeyExists($this->params)) {
          $this->saveKey($this->params, TRUE);
          $this->initAddKeyDialog(FALSE);
          $this->loadKeys();

        } elseif (isset($this->params['key_host'])
                  && !empty($this->params['key_host'])) {

          $apiTitle = (isset($this->params['key_type'])
                       && $this->params['key_type'] == 0)
            ? 'Google Maps API' : 'Yahoo Maps API';

          $this->addMsg(MSG_INFO, sprintf(
            $this->_gt('A %s key for "%s" exists already.'),
            $this->_gt($apiTitle), $this->params['key_host']));
        }
      }
    }
  }

  function execEditKey() {
    if (isset($this->params['key_id']) && $this->params['key_id'] >= 0) {
      if ($this->initEditKeyDialog()) {

        if (isset($this->params['save']) && $this->params['save']
            && $this->editKeyDialog->modified()
            && $this->editKeyDialog->checkDialogInput()) {

          if ($this->validKeyExists($this->params)) {
            $this->saveKey($this->params);
            $this->loadKeys();

          } elseif (isset($this->params['key_host'])
                    && !empty($this->params['key_host'])) {

            $apiTitle = (isset($this->params['key_type'])
                         && $this->params['key_type'] == 0)
              ? 'Google Maps API' : 'Yahoo Maps API';

            $this->addMsg(MSG_INFO, sprintf(
              $this->_gt('A %s key for "%s" does not exist.'),
              $this->_gt($apiTitle), $this->params['key_host']));
          }
        }
      }
    }
  }

  function execDelKey() {
    if (isset($this->params['confirm_delete'])
        && $this->params['confirm_delete'] == 1
        && isset($this->params['key_id'])
        && $this->params['key_id'] > 0) {

      if ($this->deleteKey($this->params['key_id'])) {
        $apiTitle = (isset($this->params['key_type'])
                     && $this->params['key_type'] == 0)
          ? 'Google Maps API' : 'Yahoo Maps API';

        $this->addMsg(MSG_INFO, sprintf($this->_gt('%s key (%s) deleted.'),
          $this->_gt($apiTitle), $this->params['key_id']));

        $this->keysCount--;
        $this->loadKeys();
      }

      unset($this->params['cmd']);
    }
  }

  function execAddFolder() {
    $this->params['folder_id'] = NULL;
    if ($this->initFolderDialog(TRUE)) {
      if (isset($this->params['save']) && $this->params['save']
          && $this->addFolderDialog->modified()
          && $this->addFolderDialog->checkDialogInput()) {

        $this->saveFolder($this->params, TRUE);
        $this->initFolderDialog(TRUE, FALSE);
        $this->loadFolders();
      }
    }
  }

  function execEditFolder() {
    if ($this->initFolderDialog()) {
      if (isset($this->params['save']) && $this->params['save']
          && $this->editFolderDialog->modified()
          && $this->editFolderDialog->checkDialogInput()) {

        $this->saveFolder($this->params);
        $this->loadFolders();
      }
    }
  }

  function execDelFolder() {
    if (isset($this->params['confirm_delete'])
        && $this->params['confirm_delete'] == 1) {

      if (isset($this->markers) && is_array($this->markers)
          && count($this->markers) > 0) {

        foreach ($this->markers as $markerId => $marker) {
          $this->deleteMarker($markerId);
        }
        $this->loadMarkers($this->params['folder_id']);
      }

      if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0
          && !empty($this->folders[$this->params['folder_id']]['folder_title'])
          && $this->deleteFolder($this->params)) {

        $this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) deleted.'),
          $this->folders[$this->params['folder_id']]['folder_title'],
          $this->params['folder_id']));

        $this->loadFolders();
        $this->params['folder_id'] = 0;
        $this->loadMarkers($this->params['folder_id']);

        unset($this->params['cmd']);
      }
    }
  }

  function execAddMarker() {
    if ($this->initAddMarkerDialog()) {
      if (isset($this->params['save']) && $this->params['save']
          && isset($this->params['folder_id']) && $this->params['folder_id'] >= 0
          && $this->addMarkerDialog->modified()
          && $this->addMarkerDialog->checkDialogInput()) {

        $this->saveMarker($this->params, $this->addMarkerDialog->data, TRUE);
        $this->initAddMarkerDialog(FALSE);
        $this->loadMarkers($this->params['folder_id']);

      }

      if (!isset($this->params['save']) || !$this->params['save']) {
        $host = $_SERVER['HTTP_HOST'];

        if (!$this->validKeyExists(array('key_host' => $host))) {
          $this->addMsg(MSG_ERROR, sprintf(
            $this->_gt('No suitable geo maps key found for host "%s".'), $host));
          $this->showMarkerDialog = FALSE;
        }
      }
    }
  }

  function execEditMarker() {
    if (isset($this->params['marker_id'])
        && $this->params['marker_id'] > 0 && $this->markersCount > 0) {

      if ($this->initEditMarkerDialog()) {
        if (isset($this->params['save']) && $this->params['save']
            && isset($this->params['folder_id']) && $this->params['folder_id'] >= 0
            && $this->editMarkerDialog->modified()
            && $this->editMarkerDialog->checkDialogInput()) {

            $this->saveMarker($this->params, $this->editMarkerDialog->data, FALSE);
            $this->loadMarkers($this->params['folder_id']);
        }

        $host = $_SERVER['HTTP_HOST'];
        if (!$this->validKeyExists(array('key_host' => $host))) {
          $this->addMsg(MSG_ERROR, sprintf(
            $this->_gt('No suitable geo maps key found for host "%s".'),
              $host));
            $this->showMarkerDialog = FALSE;
        }
      }
    }
  }

  function execDelMarker() {
    if (isset($this->params['confirm_delete'])
        && $this->params['confirm_delete'] == 1
        && isset($this->params['folder_id']) && $this->params['folder_id'] >= 0
        && isset($this->params['marker_id']) && $this->params['marker_id'] >= 0
        && !empty($this->markers[$this->params['marker_id']]['marker_title'])) {

      if ($this->deleteMarker($this->params['marker_id'])) {
        $this->addMsg(MSG_INFO,
          sprintf($this->_gt('Marker "%s" (%s) deleted.'),
          $this->markers[$this->params['marker_id']]['marker_title'],
          $this->params['marker_id']));
        $this->markersCount--;
        $this->loadMarkers($this->params['folder_id']);

        unset($this->params['cmd']);
      }
    }
  }

  /**
   * Set marker up or down.
   *
   * @param string $direction UP / DOWN
   */
  function execSetMarker($direction) {
    if (isset($this->params['folder_id']) && $this->params['folder_id'] >= 0
        && isset($this->params['marker_id']) && $this->params['marker_id'] > 0
        && !empty($this->markers[$this->params['marker_id']]['marker_title'])) {

      $sort = $this->checkMarkersSort($this->params['folder_id'],
        $this->params['marker_id']);

      if ($sort !== FALSE
          && (($direction > 0 && $sort < count($this->markers))
              || ($direction < 0 && $sort > 0))) {
        $this->switchMarkerPosition($this->params['marker_id'], $sort, $direction);
        $this->loadMarkers($this->params['folder_id']);

        $this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) set %s.'),
          $this->markers[$this->params['marker_id']]['marker_title'],
          $this->params['marker_id'],
          ($direction == 1) ? 'up' : 'down'));
      }
    }
  }

  /**
   * Sort markers
   *
   * @param string $direction ASC / DESC
   */
  function execSortMarkers($direction = 'ASC') {
    if (isset($this->params['folder_id']) && $this->params['folder_id'] >= 0) {
      if ($this->sortMarkersByTitle($this->params['folder_id'], $direction)) {
        $this->addMsg(MSG_INFO,
          sprintf($this->_gt('Markers have been sorted %s by title.'),
            ($direction == 'ASC') ? 'ascending' : 'descending')
        );
        $this->loadMarkers($this->params['folder_id']);
      }
    }
  }

  function execExportMarkers() {
    if (isset($this->params['folder_id']) && $this->params['folder_id'] >= 0
        && count($this->markers) > 0) {
      $this->exportMarkersKML($this->getFolderTitle($this->params['folder_id']));
    }
  }

  function switchMarkerPosition($markerId, $currentSort, $dir) {
    if ($this->markersCount > 0) {
      $newSort = $currentSort + $dir;
      $res = FALSE;
      $res = FALSE !== $this->databaseUpdateRecord($this->tableMarkers,
        array('marker_sort' => $currentSort), 'marker_sort', $newSort);
      $res = FALSE !== $this->databaseUpdateRecord($this->tableMarkers,
        array('marker_sort' => $newSort), 'marker_id', $markerId);
      return $res;
    }
    return FALSE;
  }

  function getXML() {
    $this->getBars();
    if (!isset($this->params['mode'])) {
      $this->params['mode'] = '';
    }
    if (!isset($this->params['cmd'])) {
      $this->params['cmd'] = '';
    }
    switch ($this->params['mode']) {
    case 1:
      switch ($this->params['cmd']) {
      case 'add_key':
        if ($this->module->hasPerm(4, FALSE)) {
          $this->getAddKeyDialog();
        }
        break;
      case 'edit_key':
        if ($this->module->hasPerm(4, FALSE)) {
          $this->getEditKeyDialog();
        }
        break;
      case 'del_key':
        if ($this->module->hasPerm(4, FALSE)) {
          if (!isset($this->params['confirm_delete']) ||
              (int)$this->params['confirm_delete'] == 0) {
            $this->getDeleteKeyDialog();
          }
        }
        break;
      }
      if ($this->keysCount > 0) {
        $this->getKeysList();
      } else {
        $this->addMsg(MSG_INFO, 'No keys added yet.');
      }
      $text = '<a href="http://www.google.com/apis/maps" target="_blank">Google Maps API Keys</a><br />'.
      '<a href="http://search.yahooapis.com/webservices/register_application" target="_blank">Yahoo Maps API IDs</a><br />';
      $result = '<panel title="'.papaya_strings::escapeHTMLChars($this->_gt('Get your keys here')).'">'.
        '<sheet width="100%" align="center"><text><div style="padding: 0px 5px 0px 5px; ">'.
        $text.'</div></text></sheet></panel>';
      $this->layout->addLeft($result);
      break;
    default:
      switch ($this->params['cmd']) {
      case 'add_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->getAddFolderDialog();
        }
        break;
      case 'edit_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          $this->getEditFolderDialog();
        }
        break;
      case 'del_folder':
        if ($this->module->hasPerm(2, FALSE)) {
          if (!isset($this->params['confirm_delete']) ||
              (int)$this->params['confirm_delete'] == 0) {
            $this->getDeleteFolderDialog();
          }
        }
        break;
      case 'add_marker':
        if ($this->module->hasPerm(2, FALSE) && $this->showMarkerDialog) {
          $this->getAddMarkerDialog();
        }
        break;
      case 'edit_marker':
        if ($this->module->hasPerm(2, FALSE) && $this->showMarkerDialog) {
          $this->getEditMarkerDialog();
        }
        break;
      case 'del_marker':
        if ($this->module->hasPerm(2, FALSE)) {
          if (!isset($this->params['confirm_delete']) ||
              (int)$this->params['confirm_delete'] == 0) {
            $this->getDeleteMarkerDialog();
          }
        }
        break;
      case 'generate_spatial_points':
        if ($this->module->hasPerm(5, FALSE)) {
          if (!isset($this->params['confirmed']) ||
              (int)$this->params['confirmed'] == 0) {
            $this->getGenerateSpatialPointsDialog();
          }
        }
        break;
      case 'generate_spatial_polygon':
        if ($this->module->hasPerm(5, FALSE)) {
          if (!isset($this->params['confirmed']) ||
              (int)$this->params['confirmed'] == 0) {
            $this->getGenerateSpatialPolygonDialog();
          }
        }
      }
      $this->getFoldersList();

      if ($this->markersCount > 0 && @$this->params['cmd'] != 'add_folder') {
        $this->getMarkersList();
      } elseif ($this->markersCount == 0) {
        $this->addMsg(MSG_INFO, 'No markers added yet.');
      }
    }
  }

  function getBars() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_btnbuilder.php');
    $menubar = &new base_btnbuilder;
    $menubar->images = $this->images;

    $hasCmdParam = !empty($this->param['cmd']);

    if ($this->module->hasPerm(4, FALSE)) {
      $menubar->addButton('Markers',
        $this->getLink(array('mode' => 0)),
          'items-tag', '', $this->params['mode'] == 0
        );

      $menubar->addButton('Keys',
        $this->getLink(array('mode' => 1)),
          'categories-access', '', $this->params['mode'] == 1
        );

      $menubar->addSeperator();
    }

    if (!isset($this->params['mode'])) {
      $this->params['mode'] = '';
    }
    switch ($this->params['mode']) {
    case 1:
      if ($this->module->hasPerm(4, FALSE)) {
        $menubar->addButton('Add key',
          $this->getLink(array('cmd' => 'add_key')),
            'actions-permission-add', '',
            ($hasCmdParam && $this->params['cmd'] == 'add_key')
          );

        if (!empty($this->params['key_id']) && $this->params['key_id'] > 0 &&
            $hasCmdParam && $this->params['cmd'] == 'edit_key') {
          $menubar->addButton('Delete key',
            $this->getLink(array(
                'cmd' => 'del_key', 'key_id' => $this->params['key_id'])),
              'actions-permission-delete', '',
              ($hasCmdParam && $this->params['cmd'] == 'del_key')
            );
        }
      }
      break;
    default:
      if ($this->module->hasPerm(2, FALSE)) {
        $menubar->addButton('Add folder',
          $this->getLink(array('cmd' => 'add_folder')),
            'actions-folder-add', '',
            ($hasCmdParam && $this->params['cmd'] == 'add_folder')
          );

        if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0) {
          $menubar->addButton('Edit folder',
            $this->getLink(array(
                'cmd' => 'edit_folder',
                'folder_id' => $this->params['folder_id'])),
              'actions-edit', '',
              ($hasCmdParam && $this->params['cmd'] == 'edit_folder')
            );

          $menubar->addButton('Delete folder',
            $this->getLink(array(
                'cmd' => 'del_folder',
                'folder_id' => $this->params['folder_id'])),
              'actions-folder-delete', '',
              ($hasCmdParam && $this->params['cmd'] == 'del_folder')
            );

          if (isset($this->markers) && count($this->markers) > 0
              && $this->module->hasPerm(5, FALSE)) {

            $menubar->addButton('Generate spatial points',
              $this->getLink(array(
                  'cmd' => 'generate_spatial_points',
                  'folder_id' => $this->params['folder_id']
              )),
              'actions-database-refresh', '',
              ($hasCmdParam && $this->params['cmd'] == 'generate_spatial_points')
            );

            $menubar->addButton('Generate spatial polygon',
              $this->getLink(array(
                  'cmd' => 'generate_spatial_polygon',
                  'folder_id' => $this->params['folder_id']
              )),
              'actions-database-refresh', '',
              ($hasCmdParam && $this->params['cmd'] == 'generate_spatial_polygon')
            );

          }
        }

        $toolbar = &new base_btnbuilder;
        $toolbar->images = $this->images;

        $toolbar->addButton('Add marker',
          $this->getLink(array('cmd' => 'add_marker')),
            'actions-tag-add', '',
            ($hasCmdParam && $this->params['cmd'] == 'add_marker')
          );

        if (isset($this->params['marker_id']) && $this->params['marker_id'] > 0
            && $hasCmdParam && $this->params['cmd'] == 'edit_marker') {
          $toolbar->addButton('Delete marker',
            $this->getLink(array(
                'cmd' => 'del_marker',
                'marker_id' => $this->params['marker_id'])),
              'actions-tag-delete', '',
              ($hasCmdParam && $this->params['cmd'] == 'del_marker')
            );
        }
      }

      $toolbar->addSeperator();

      $toolbar->addButton('Sort markers ascending',
        $this->getLink(array('cmd' => 'sort_markers_asc')),
        $this->localImages['sort-asc']);

      $toolbar->addButton('Sort markers descending',
        $this->getLink(array('cmd' => 'sort_markers_desc')),
        $this->localImages['sort-desc']);

      if ($this->module->hasPerm(3, FALSE) && count($this->markers) > 0) {
        $toolbar->addSeperator();

        $toolbar->addButton('Export markers',
          $this->getLink(array('cmd' => 'export_markers')),
          $this->localImages['export']);
      }

      if ($str = $toolbar->getXML()) {
        $this->layout->add(sprintf('<toolbar>%s</toolbar>'.LF, $str));
      }
    }

    if ($str = $menubar->getXML()) {
      $this->layout->addMenu(sprintf('<menu>%s</menu>'.LF, $str));
    }
  }

  function initFolderDialog($add = FALSE, $loadParams = TRUE) {
    if ($add) {
      unset($this->addFolderDialog);
      $data = array();

    } else {
      unset($this->editFolderDialog);
      if (isset($this->params['folder_id']) && $this->params['folder_id'] >= 0) {
        $hasFolder = $this->loadFolder($this->params['folder_id'], TRUE);
      }

      if ($hasFolder === TRUE) {
        $folder = &$this->folders[$this->params['folder_id']];
        $data = array(
          'folder_title' => $folder['folder_title'],
          'folder_marker_icon' => $folder['folder_marker_icon']
        );
      }
    }
    $cmd = ($add) ? 'add_folder' : 'edit_folder';

    $hidden = array(
      'cmd' => $cmd,
      'save' => 1,
    );
    $fields = array(
      'folder_title' => array(
        'Title', 'isAlphaNum', TRUE, 'input', 200,
      ),
      'folder_marker_icon' => array(
        'Global Marker Icon', 'isAlphaNum', FALSE, 'mediafile', 200
      )
    );

    include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
    if ($add) {
      $this->addFolderDialog = &new base_dialog(
        $this, $this->paramName, $fields, $data, $hidden
      );
      $this->addFolderDialog->msgs = &$this->msgs;
      if ($loadParams) {
        $this->addFolderDialog->loadParams();
      }
      if (isset($this->addFolderDialog) &&
          is_object($this->addFolderDialog)) {
        return TRUE;
      }
    } else {
      $this->editFolderDialog = &new base_dialog($this,
        $this->paramName, $fields, $data, $hidden
      );
      $this->editFolderDialog->baseLink = $this->baseLink;
      $this->editFolderDialog->dialogId = 'dlg_folder';
      $this->editFolderDialog->dialogDoubleButtons = FALSE;
      $this->editFolderDialog->msgs = &$this->msgs;
      $this->editFolderDialog->inputFieldSize = 'x-large';
      $this->editFolderDialog->tokenKeySuffix = 'gmps';
      $this->editFolderDialog->expandPapayaTags = TRUE;
      $this->editFolderDialog->loadParams();
      if (isset($this->editFolderDialog) &&
          is_object($this->editFolderDialog)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function getAddFolderDialog() {
    if (isset($this->addFolderDialog) &&
        is_object($this->addFolderDialog)) {
      $this->addFolderDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Add folder'));
      $this->layout->add($this->addFolderDialog->getDialogXML());
    }
  }

  function getEditFolderDialog() {
    if (isset($this->editFolderDialog) &&
        is_object($this->editFolderDialog)) {
      $this->addFolderDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Edit folder'));
      $this->layout->add($this->editFolderDialog->getDialogXML());
    }
  }

  function getDeleteFolderDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $hidden = array(
      'cmd' => $this->params['cmd'],
      'folder_id' => $this->params['folder_id'],
      'confirm_delete' => 1
    );
    if (isset($this->params['folder_id']) && $this->params['folder_id'] >= 0) {
      $hasFolder = $this->loadFolder($this->params['folder_id'], TRUE);
    }
    if ($hasFolder === TRUE) {
      $folder = &$this->folders[$this->params['folder_id']];
      $msg = sprintf(
        $this->_gt('Do you want to delete the folder "%s" (%d) with markers?'),
        $folder['folder_title'],
        $this->params['folder_id']);

      $this->dialog = &new base_msgdialog($this, $this->paramName,
        $hidden, $msg, 'question');
      $this->dialog->buttonTitle = $this->_gt('Yes');

      $this->layout->add($this->dialog->getMsgDialog());
    }
  }

  function getKeyEditFields() {
    $fields = array(
      'key_type' => array(
        'Type', 'isNum', TRUE, 'combo',
        array('Google Maps API', 'Yahoo Maps API'), '', 0
      ),
      'key_host' => array(
        'Host', 'isAlphaNumChar', TRUE, 'input', 200
      ),
      'key_value' => array(
        'Value', 'IsAlphaNum', TRUE, 'input', 200
      )
    );
    return $fields;
  }

  function initAddKeyDialog($loadParams = TRUE) {
    unset($this->addKeyDialog);
    $hidden = array(
      'cmd' => 'add_key',
      'save' => 1
    );
    $fields = $this->getKeyEditFields();
    $data = array();

    include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
    $this->addKeyDialog = &new base_dialog($this, $this->paramName,
      $fields, $data, $hidden);
    $this->addKeyDialog->baseLink = $this->baseLink;
    $this->addKeyDialog->dialogId = 'dlg_add_key';
    $this->addKeyDialog->dialogDoubleButtons = FALSE;
    $this->addKeyDialog->msgs = &$this->msgs;
    $this->addKeyDialog->dialogTitle =
      papaya_strings::escapeHTMLChars($this->_gt('Add key'));
    $this->addKeyDialog->inputFieldSize = 'x-large';
    $this->addKeyDialog->tokenKeySuffix = 'gmps';
    $this->addKeyDialog->expandPapayaTags = TRUE;
    if ($loadParams) {
      $this->addKeyDialog->loadParams();
    }

    if (isset($this->addKeyDialog) &&
        is_object($this->addKeyDialog)) {
      return TRUE;
    }
    return FALSE;
  }

  function initEditKeyDialog() {
    unset($this->editKeyDialog);
    if (isset($this->params['key_id']) && $this->params['key_id'] > 0) {
      $hasKey = $this->loadKey($this->params['key_id'], TRUE);
    }
    if ($hasKey === TRUE) {
      $key = &$this->keys[$this->params['key_id']];
      $data = array(
        'key_type' => $key['key_type'],
        'key_host' => $key['key_host'],
        'key_value' => $key['key_value']
      );
      $hidden = array(
        'cmd' => 'edit_key',
        'save' => 1,
        'key_id' => $this->params['key_id']
      );
      $fields = $this->getKeyEditFields();

      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->editKeyDialog = &new base_dialog($this, $this->paramName,
        $fields, $data, $hidden);
      $this->editKeyDialog->baseLink = $this->baseLink;
      $this->editKeyDialog->dialogId = 'dlg_edit_key';
      $this->editKeyDialog->dialogDoubleButtons = FALSE;
      $this->editKeyDialog->msgs = &$this->msgs;
      $this->editKeyDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Edit key'));
      $this->editKeyDialog->inputFieldSize = 'x-large';
      $this->editKeyDialog->tokenKeySuffix = 'gmps';
      $this->editKeyDialog->expandPapayaTags = TRUE;
      $this->editKeyDialog->loadParams();

      if (isset($this->editKeyDialog) &&
          is_object($this->editKeyDialog)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function getAddKeyDialog() {
    if (isset($this->addKeyDialog) &&
        is_object($this->addKeyDialog)) {
      $this->addKeyDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Add key'));
      $this->layout->add($this->addKeyDialog->getDialogXML());
    }
  }

  function getEditKeyDialog() {
    if (isset($this->editKeyDialog) &&
        is_object($this->editKeyDialog)) {
      $this->editKeyDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Edit key'));
      $this->layout->add($this->editKeyDialog->getDialogXML());
    }
  }

  function getDeleteKeyDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    if (isset($this->params['key_id']) && $this->params['key_id'] > 0) {
      $hasKey = $this->loadKey($this->params['key_id'], TRUE);
    }
    if ($hasKey === TRUE) {
      $key = &$this->keys[$this->params['key_id']];
      $hidden = array(
        'cmd' => $this->params['cmd'],
        'key_id' => $key['key_id'],
        'key_type' => $key['key_type'],
        'confirm_delete' => 1
      );
      $apiTitle = ($key['key_type'] == 0) ? 'Google Maps API' :
        'Yahoo Maps API';

      $msg = sprintf($this->_gt('Do you want to delete the %s key (%d)?'),
        $apiTitle, $this->params['key_id']);

      $this->dialog = &new base_msgdialog($this, $this->paramName,
        $hidden, $msg, 'question');
      $this->dialog->buttonTitle = $this->_gt('Yes');

      $this->layout->add($this->dialog->getMsgDialog());
    }
  }

  /**
   * Get a message dialog to confirm spatial polygon generation.
   */
  function getGenerateSpatialPolygonDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0
        && $this->folders[$this->params['folder_id']]) {

      $hidden = array(
        'cmd' => $this->params['cmd'],
        'folder_id' => $this->params['folder_id'],
        'confirmed' => 1
      );

      $msg = sprintf($this->_gt('Do you want to generate a spatial polygon for "%s" (%d)?'),
        $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']);

      $this->dialog = &new base_msgdialog($this, $this->paramName,
        $hidden, $msg, 'question');
      $this->dialog->buttonTitle = $this->_gt('Yes');

      $this->layout->add($this->dialog->getMsgDialog());
    }
  }

  /**
   * Get a message dialog to confirm spatial points generation.
   */
  function getGenerateSpatialPointsDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0
        && $this->folders[$this->params['folder_id']]) {

      $hidden = array(
        'cmd' => $this->params['cmd'],
        'folder_id' => $this->params['folder_id'],
        'confirmed' => 1
      );

      $msg = sprintf($this->_gt('Do you want to generate spatial points for "%s" (%d)?'),
        $this->folders[$this->params['folder_id']]['folder_title'], $this->params['folder_id']);

      $this->dialog = &new base_msgdialog($this, $this->paramName,
        $hidden, $msg, 'question');
      $this->dialog->buttonTitle = $this->_gt('Yes');

      $this->layout->add($this->dialog->getMsgDialog());
    }
  }

  function getMarkerEditFields() {
    $folders = array($this->_gt('Base'));
    if (isset($this->folders) && is_array($this->folders) &&
        count($this->folders) > 0) {
      foreach ($this->folders as $folderId => $folder) {
        $folders[$folderId] = $folder['folder_title'];
      }
    }
    $fields = array(
      'marker_folder' => array(
        'Folder', 'isNum', TRUE, 'combo', $folders, '',
        $this->params['folder_id']
      ),
      'marker_title' => array(
        'Title', 'isAlphaNum', TRUE, 'input', 200
      ),
      'marker_icon' => array(
        'Icon', 'isAlphaNum', FALSE, 'mediafile', 200
      ),
      'marker_desc' => array(
        'Description', 'isSomeText', FALSE, 'simplerichtext', 6
      ),
      'marker_location' => array('Coordinates', 'isGeoPos', FALSE, 'geopos', 200),
      'Address',
      'marker_addr_street' => array(
        'Street', 'isNoHTML', FALSE, 'input', 255
      ),
      'marker_addr_house' => array(
        'House number', 'isAlphaNumChar', FALSE, 'input', 10
      ),
      'marker_addr_zip' => array(
        'ZIP code', 'isAlphaNumChar', FALSE, 'input', 5
      ),
      'marker_addr_city' => array(
        'City', 'isNoHTML', FALSE, 'input', 255
      ),
      'marker_addr_country' => array(
        'Country', 'isNoHTML', FALSE, 'input', 255
      )
    );
    return $fields;
  }

  function initAddMarkerDialog($loadParams = TRUE) {
    unset($this->addMarkerDialog);
    $hidden = array(
      'cmd' => 'add_marker',
      'save' => 1
    );
    $fields = $this->getMarkerEditFields();
    $data = array();

    include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
    $this->addMarkerDialog = &new base_dialog($this, $this->paramName,
      $fields, $data, $hidden);
    $this->addMarkerDialog->baseLink = $this->baseLink;
    $this->addMarkerDialog->dialogId = 'dlg_add_marker';
    $this->addMarkerDialog->dialogDoubleButtons = FALSE;
    $this->addMarkerDialog->msgs = &$this->msgs;
    $this->addMarkerDialog->dialogTitle =
      papaya_strings::escapeHTMLChars($this->_gt('Add marker'));
    $this->addMarkerDialog->inputFieldSize = 'x-large';
    $this->addMarkerDialog->tokenKeySuffix = 'gmps';
    $this->addMarkerDialog->expandPapayaTags = TRUE;
    if ($loadParams) {
      $this->addMarkerDialog->loadParams();
    }

    if (isset($this->addMarkerDialog) &&
        is_object($this->addMarkerDialog)) {
      return TRUE;
    }
    return FALSE;
  }

  function initEditMarkerDialog() {
    unset($this->editMarkerDialog);
    if (isset($this->params['marker_id']) && $this->params['marker_id'] > 0) {
      $hasMarker = $this->loadMarker($this->params['marker_id'], TRUE);
    }
    if ($hasMarker === TRUE) {
      $marker = &$this->markers[$this->params['marker_id']];
      $data = array(
        'marker_title' => $marker['marker_title'],
        'marker_desc' => $marker['marker_desc'],
        'marker_icon' => $marker['marker_icon'],
        'marker_addr_street' => $marker['marker_addr_street'],
        'marker_addr_house' => $marker['marker_addr_house'],
        'marker_addr_zip' => $marker['marker_addr_zip'],
        'marker_addr_city' => $marker['marker_addr_city'],
        'marker_addr_country' => $marker['marker_addr_country'],
        'marker_location' => $marker['marker_lat'].','.$marker['marker_lng']
      );
      $hidden = array(
        'cmd' => 'edit_marker',
        'save' => 1,
        'marker_id' => $this->params['marker_id']
      );
      $fields = $this->getMarkerEditFields();

      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->editMarkerDialog = &new base_dialog($this, $this->paramName,
        $fields, $data, $hidden);
      $this->editMarkerDialog->baseLink = $this->baseLink;
      $this->editMarkerDialog->dialogId = 'dlg_edit_marker';
      $this->editMarkerDialog->dialogDoubleButtons = FALSE;
      $this->editMarkerDialog->msgs = &$this->msgs;
      $this->editMarkerDialog->dialogTitle =
        papaya_strings::escapeHTMLChars($this->_gt('Edit marker'));
      $this->editMarkerDialog->inputFieldSize = 'x-large';
      $this->editMarkerDialog->tokenKeySuffix = 'gmps';
      $this->editMarkerDialog->expandPapayaTags = TRUE;
      $this->editMarkerDialog->loadParams();

      if (isset($this->editMarkerDialog) &&
          is_object($this->editMarkerDialog)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function getAddMarkerDialog() {
    if (isset($this->addMarkerDialog) &&
        is_object($this->addMarkerDialog)) {
      if ($dialogXML = $this->getMarkerDialog($this->addMarkerDialog)) {
        $this->layout->add($dialogXML);
      } else {
        $this->addMsg(MSG_ERROR, sprintf(
          $this->_gt('Could not include suitable api for host "%s".'),
          $_SERVER['HTTP_HOST']));
      }
    }
  }

  function getEditMarkerDialog() {
    if (isset($this->editMarkerDialog) &&
        is_object($this->editMarkerDialog)) {
      if ($dialogXML = $this->getMarkerDialog($this->editMarkerDialog)) {
        $this->layout->add($dialogXML);
      } else {
        $this->addMsg(MSG_ERROR, sprintf(
          $this->_gt('Could not include suitable api for host "%s".'),
          $_SERVER['HTTP_HOST']));
      }
    }
  }

  /**
   * Get the dialog to add or edit markers. The dialog needs an api
   * script, which is set by getApiScript(). The methods adds a field
   * and a button to use this script too.
   *
   * @param base_dialog $dialogObj the dialog object by reference
   * @return mixed boolean FALSE if an error occurs or the dialog xml data
   */
  function getMarkerDialog(&$dialogObj) {
    $result = FALSE;

    $apiType = 0;
    $apiScript = $this->getApiScript($apiType);
    if (!$apiScript) {
      $apiType = 1;
      $apiScript = $this->getApiScript($apiType);
    }

    if ($apiScript) {
      $this->layout->addScript($apiScript);

      $getCoordinatesButton = sprintf('<dlgbutton value="%s" type="button"'.
        ' onclick="getCoordinates( %d, \'%s\');" />'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Get coordinates by address')),
        $apiType, $dialogObj->dialogId
      );
      /* insert the field and the button by replacing selected strings
         in the given xml data with additional xml data */
      $dialogXML = str_replace('</dialog>', $getCoordinatesButton.'</dialog>',
        $dialogObj->getDialogXML());

      $result = $dialogXML;
    }
    return $result;
  }

  /*
   * The method loads a valid api key for the current used host and
   * set an xml output which loads specific java scripts to include the
   * associated api.
   *
   * @param integer $type type of api: 0 is reserved for google and 1 for yahoo
   * @return mixed boolean FALSE if an error occurs or the script xml data
   */
  function getApiScript($type) {
    $apiKey = $this->getDistinctKey($_SERVER['HTTP_HOST'], $type, TRUE);

    if (!empty($apiKey) && !empty($apiKey['key_value']))  {
      $result = '';

      switch($type) {
      case 0:
        $result .= sprintf('<script type="text/javascript" '.
          'src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=%s" />'.LF,
          papaya_strings::escapeHTMLChars($apiKey['key_value']));
        break;
      case 1:
        $result .= sprintf('<script type="text/javascript" '.
          'src="http://api.maps.yahoo.com/ajaxymap?v=3.0&amp;appid=%s" />'.LF,
          papaya_strings::escapeHTMLChars($apiKey['key_value']));
        break;
      }

      $result .= sprintf('<script type="text/javascript" src="%sgeomaps.js" />'.LF,
        $this->getOption('scripts_path', '/papaya-script/geomaps/'));
      $result .= sprintf('<script type="text/javascript"> <![CDATA[ apiType = %d; ]]> </script>'.LF,
        $type);

      return $result;
    }
    return FALSE;
  }

  function getDeleteMarkerDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $hidden = array(
      'cmd' => 'del_marker',
      'marker_id' => (isset($this->params['marker_id'])) ? $this->params['marker_id'] : NULL,
      'confirm_delete' => 1,
    );

    $msg = sprintf($this->_gt('Do you want to delete marker "%s" (%d)?'),
      $this->markers[$this->params['marker_id']]['marker_title'],
      $this->params['marker_id']);
    $this->dialog = &new base_msgdialog($this, $this->paramName,
      $hidden, $msg, 'question');
    $this->dialog->buttonTitle = $this->_gt('Yes');
    $this->layout->add($this->dialog->getMsgDialog());
  }

  function getFoldersList() {
    $result = sprintf('<listview width="100%%" title="%s">'.LF,
      $this->_gt('Folders'));
    $result .= '<items>'.LF;

    $selected = '';
    if (!(isset($this->params['folder_id']) && $this->params['folder_id'] > 0)
        || (isset($this->params['cmd']) && $this->params['cmd'] == 'add_folder')) {
      $selected = ' selected="selected"';
    }
    $result .= sprintf('<listitem image="%s" title="%s" href="%s"%s/>'.LF,
      $this->images['places-desktop'], $this->_gt('Base'),
        $this->getLink(array('folder_id' => 0)), $selected);

    if (isset($this->folders) && is_array($this->folders) &&
        count($this->folders) > 0) {
      foreach ($this->folders as $folderId => $folder) {
        $selected = '';
        if (isset($this->params['folder_id']) &&
            $this->params['folder_id'] == $folderId) {
          $folderImage = 'status-folder-open';
          $selected = ' selected="selected"';
        } else {
          $folderImage = 'items-folder';
        }
        $result .= sprintf('<listitem image="%s" title="%s" indent="1" href="%s"%s/>'.LF,
          $this->images[$folderImage],
          papaya_strings::escapeHTMLChars($folder['folder_title']),
          $this->getLink(array('folder_id' => $folderId)), $selected);
      }
    }

    $result .= '</items>'.LF;
    $result .= '</listview>'.LF;
    $this->layout->addLeft($result);
  }

  function getKeysList() {

    $result = sprintf('<listview width="100%%" title="%s">'.LF,
      $this->_gt('Keys'));
    $result .= sprintf('<cols><col align="left">%s</col>'.
      '<col align="left">%s</col><col align="center">%s</col>'.
      '<col align="center">%s</col></cols>',
      $this->_gt('Type'), $this->_gt('Host'),
      $this->_gt('Edit'), $this->_gt('Delete'));

    $result .= '<items>'.LF;
    foreach ($this->keys as $keyId => $key) {
      $editLink = $this->getLink(array('cmd' => 'edit_key',
        'key_id' => $keyId));
      $apiTitle = ($key['key_type'] == 0) ? 'Google Maps API' :
        'Yahoo Maps API';
      $result .= sprintf('<listitem image="%s" href="%s" title="%s">'.LF,
        $this->images['items-permission'], $editLink,
        papaya_strings::escapeHTMLChars($apiTitle));

      $result .= sprintf('<subitem>%s</subitem>'.LF,
        papaya_strings::escapeHTMLChars($key['key_host']));
      $result .= sprintf('<subitem align="center"><a href="%s">'.
        '<glyph src="%s" /></a></subitem>'.LF,
        $editLink, $this->images['actions-edit']);
      $delLink = $this->getLink(array('cmd' => 'del_key',
        'key_id' => $keyId));
      $result .= sprintf('<subitem align="center"><a href="%s">'.
        '<glyph src="%s" /></a></subitem>'.LF,
        $delLink, $this->images['actions-permission-delete']);

      $result .= '</listitem>'.LF;
    }
    $result .= '</items>'.LF;
    $result .= '</listview>'.LF;

    $this->layout->add($result);
  }

  function getMarkersList() {
    $count = 1;
    $maxCount = count($this->markers);

    $result = sprintf('<listview width="100%%" title="%s">'.LF,
      $this->_gt('Markers'));
    $result .= sprintf('<cols><col align="left">%s</col>'.
      '<col align="left">%s</col>',
      $this->_gt('Title'), $this->_gt('Coordinates'));
    if ($this->module->hasPerm(2, FALSE)) {
      $result .= sprintf('<col align="center">%s</col><col align="center">%s</col>',
        $this->_gt('Edit'), $this->_gt('Delete'));
      if ($maxCount > 1) {
        $result .= sprintf('<col /><col />');
      }
    }
    $result .= '</cols>';


    $result .= '<items>'.LF;
    foreach ($this->markers as $markerId => $marker) {
      if ($this->module->hasPerm(2, FALSE)) {
        $editLink = $this->getLink(array('cmd' => 'edit_marker',
          'marker_id' => $markerId));
        $result .= sprintf('<listitem image="%s" href="%s" title="%s">'.LF,
          $this->images['items-tag'], $editLink,
          papaya_strings::escapeHTMLChars($marker['marker_title']));
      } else {
        $result .= sprintf('<listitem image="%s" title="%s">'.LF,
          $this->images['items-tag'],
          papaya_strings::escapeHTMLChars($marker['marker_title']));
      }

      $result .= sprintf('<subitem>%f/%f</subitem>'.LF,
        papaya_strings::escapeHTMLChars($marker['marker_lat']),
        papaya_strings::escapeHTMLChars($marker['marker_lng']));

      if ($this->module->hasPerm(2, FALSE)) {
        $result .= sprintf('<subitem align="center"><a href="%s">'.
          '<glyph src="%s" /></a></subitem>'.LF,
          $editLink, $this->images['actions-edit']);
        $delLink = $this->getLink(array('cmd' => 'del_marker',
          'marker_id' => $markerId));
        $result .= sprintf('<subitem align="center"><a href="%s">'.
          '<glyph src="%s" /></a></subitem>'.LF,
          $delLink, $this->images['actions-tag-delete']);
        if ($count > 1) {
          $pushUpLink = $this->getLink(array('cmd' => 'set_up_marker',
            'marker_id' => $markerId));
          $result .= sprintf('<subitem align="center"><a href="%s">'.
            '<glyph src="%s" /></a></subitem>'.LF,
            $pushUpLink, $this->images['actions-go-up']);
        } elseif ($maxCount > 1) {
          $result .= '<subitem />';
        }
        if ($count < $maxCount) {
          $pushDownLink = $this->getLink(array('cmd' => 'set_down_marker',
            'marker_id' => $markerId));
          $result .= sprintf('<subitem align="center"><a href="%s">'.
            '<glyph src="%s" /></a></subitem>'.LF,
            $pushDownLink, $this->images['actions-go-down']);
        } elseif ($maxCount > 1) {
          $result .= '<subitem />';
        }
      }
      $count++;
      $result .= '</listitem>'.LF;
    }
    $result .= '</items>'.LF;

    $result .= '</listview>'.LF;
    $this->layout->add($result);
  }

  function checkMarkersSort($folderId, $markerId = NULL) {
    if ($folderId >= 0) {
      $sql = 'SELECT marker_id, marker_sort
                FROM %s
               WHERE marker_folder = %d
               ORDER BY marker_sort, marker_title ASC';
      $params = array($this->tableMarkers, $folderId);

      $toFix = array();
      $sort = 0;
      $sortResult = 0;
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          if ((int)$row['marker_sort'] != $sort) {
            $toFix[] = array($row['marker_id'], $sort);
            if ($markerId != NULL && $row['marker_id'] == $markerId) {
              $sortResult = $sort;
            }
          } elseif ($markerId != NULL && $row['marker_id'] == $markerId) {
            $sortResult = (int)$row['marker_sort'];
          }
          $sort++;
        }
      }

      if (count($toFix) > 0) {
        foreach($toFix as $data) {
          $this->databaseUpdateRecord($this->tableMarkers,
            array('marker_sort' => $data[1]), 'marker_id', (int)$data[0]);
        }
      }
      $toFixCount = count($toFix);
      if ($markerId != NULL) {
        return $sortResult;
      } elseif ($toFixCount == 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function sortMarkersByTitle($folderId, $dir = 'ASC') {
    if ($folderId >= 0) {
      $sql = 'SELECT marker_id
                FROM %s
               WHERE marker_folder = %d
               ORDER BY marker_title '.$dir;
      $params = array($this->tableMarkers, $folderId);

      $toFix = array();
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $toFix[] = $row['marker_id'];
        }
      }
      if (count($toFix) > 0) {
        $sort = 0;
        foreach($toFix as $id) {
          $this->databaseUpdateRecord($this->tableMarkers,
            array('marker_sort' => $sort), 'marker_id', $id);
          $sort++;
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  function saveFolder($params, $new = FALSE) {
    $data = array(
      'folder_title' => $params['folder_title'],
      'folder_marker_icon' => $params['folder_marker_icon']
    );

    if ($new) {
      $newId = $this->databaseInsertRecord($this->tableFolders, 'folder_id', $data);
      if ($newId !== FALSE && $newId > 0) {
        $this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) added.'),
          $params['folder_title'], $newId));
      }
      return $newId !== FALSE;
    } else {
      $this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) modified.'),
        $params['folder_title'], $params['folder_id']));
      return (FALSE !== $this->databaseUpdateRecord($this->tableFolders,
        $data, 'folder_id', (int)$params['folder_id']));
    }
  }

  function deleteFolder($folderId) {
    return $this->databaseDeleteRecord($this->tableFolders,
      'folder_id', $folderId);
  }

  function validKeyExists($params) {
    if (isset($params['key_host'])) {
      $typeCond = '';
      if (isset($params['key_type'])) {
        $typeCond = ' AND key_type = '.(int)$params['key_type'];
      }
      $params['key_host'] = str_replace('%', '', $params['key_host']);
      $sql = "SELECT COUNT(*) AS found
                FROM %s
               WHERE key_host LIKE '%s'".$typeCond;
      $params = array($this->tableKeys, $params['key_host']);

      if ($res = $this->databaseQueryFmt($sql, $params)) {
        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          if ($row['found'] > 0) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  function saveKey($params, $new = FALSE) {
    $apiTitle = ($params['key_type'] == 0) ? 'Google Maps API' :
      'Yahoo Maps API';
    if ($new) {
      $newId = $this->databaseInsertRecord($this->tableKeys, 'key_id',
        array(
          'key_type'  => $params['key_type'],
          'key_host' => $params['key_host'],
          'key_value' => $params['key_value']
        )
      );
      if (FALSE !== $newId && $newId > 0) {
        $this->addMsg(MSG_INFO, sprintf($this->_gt('%s key (%s) added.'),
          $apiTitle, $newId));
      }
      return FALSE !== $newId;
    } else {
      $this->addMsg(MSG_INFO, sprintf($this->_gt('%s key (%s) modified.'),
        $apiTitle, $params['key_id']));

      $data = array(
        'key_type'  => $params['key_type'],
        'key_host' => $params['key_host'],
        'key_value' => $params['key_value']
      );
      return (FALSE !== $this->databaseUpdateRecord($this->tableKeys,
        $data, 'key_id', (int)$params['key_id']));
    }
  }

  function deleteKey($keyId) {
    return $this->databaseDeleteRecord($this->tableKeys,
      'key_id', $keyId);
  }

  function saveMarker(&$params, &$data, $new = FALSE) {
    if ($this->params['folder_id'] != $params['marker_folder']) {
      $this->params['folder_id'] = $params['marker_folder'];
      $this->sessionParams = $this->getSessionValue($this->sessionParamName);
      $this->initializeSessionParam('folder_id');
      $this->setSessionValue($this->sessionParamName, $this->sessionParams);
    }

    $id = FALSE;
    $existingId = (!empty($params['marker_id']) && $new === FALSE)
      ? $params['marker_id'] : NULL;
    $location = explode(',', $data['marker_location']);

    if (!empty($location) && is_array($location)
        && !empty($location[0]) && !empty($location[1])) {

      $id = $this->setMarker(
        $this->params['folder_id'],
        $data['marker_title'],
        $location[0], // latitude
        $location[1], // longitude
        $data['marker_icon'], // optional
        $data['marker_desc'], // optional
        $data['marker_addr_street'], // optional
        $data['marker_addr_house'], // optional
        $data['marker_addr_zip'], // optional
        $data['marker_addr_city'], // optional
        $data['marker_addr_country'], // optional
        $new, $existingId // add or update
      );
    }

    if ($new == TRUE && $id !== FALSE) {
      $this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) added.'),
        $params['marker_title'], $id));
      return TRUE;

    } elseif ($id !== FALSE) {
      $this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) modified.'),
        $params['marker_title'], $params['marker_id']));
      return TRUE;

    }
    return FALSE;
  }

  function deleteMarker($markerId) {
    return $this->databaseDeleteRecord($this->tableMarkers,
      'marker_id', $markerId);
  }

  function exportMarkersKML($folderTitle, $markers = NULL) {

    $markers = (is_array($markers) && count($markers) > 0)
      ? $markers : $this->markers;

    if (is_array($markers) && count($markers) > 0) {

      $fileName = sprintf('geo_maps_export_%d.kml', time());

      // Set kml header information
      header('Pragma: public');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Cache-Control: private', FALSE);
      header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');

      // Set content disposition
      if (!empty($_SERVER["HTTP_USER_AGENT"])
          && strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), 'msie') !== FALSE) {
        header('Content-Disposition: inline; filename="'.$fileName.'"');
      } else {
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
      }

      $result = '<?xml version="1.0" encoding="UTF-8"?>'.LF;
      $result .= $this->getMarkersKML($markers, $folderTitle, $fileName);

      echo $result;
      exit;
    }

    return FALSE;
  }

  /**
   * Generate spatial polygons by folder markers to perform further spatial validations.
   *
   * @param mixed $folderIds array or single folder id (int)
   * @return boolean generated?
   */
  function generateSpatialPolygonsByFolders($folderIds) {
    if (!empty($folderIds) && $this->initSpatialExtensions() === TRUE
        && $this->spatialExtensions->createSpatialPolygonsTable()) {

      if (!is_array($folderIds)) {
        $folderIds = array($folderIds);
      }
      $result = TRUE;

      foreach ($folderIds as $folderId) {

        $sql = "SELECT marker_id, marker_folder,
                       marker_lat, marker_lng
                  FROM %s
                 WHERE marker_folder = %d
                 ORDER BY marker_sort , marker_title ASC";
        $params = array($this->tableMarkers, $folderId);

        if ($res = $this->databaseQueryFmt($sql, $params)) {
          $points = array();

          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $points[] = array($row['marker_lat'], $row['marker_lng']);
          }

          if (count($points) > 0) {
            $this->spatialExtensions->removeSpatialPolygon($folderId);
            return FALSE !== $this->spatialExtensions->insertSpatialPolygon($folderId, $points);
          }
          unset($points);
        }
      }

      return $result;
    }

    return FALSE;
  }

  /**
   * Generate spatial points by folder markers to perform further spatial calculations.
   *
   * @param mixed $folderIds array or single folder id (int)
   * @return boolean generated?
   */
  function generateSpatialPointsByFolders($folderIds) {
    if (!empty($folderIds) && $this->initSpatialExtensions() === TRUE
        && $this->spatialExtensions->createSpatialPointsTable()) {

      if (!is_array($folderIds)) {
        $folderIds = array($folderIds);
      }
      $result = TRUE;

      foreach ($folderIds as $folderId) {

        $sql = "SELECT marker_id, marker_folder,
                       marker_lat, marker_lng
                  FROM %s
                 WHERE marker_folder = %d
                 ORDER BY marker_sort , marker_title ASC";
        $params = array($this->tableMarkers, $folderId);

        if ($res = $this->databaseQueryFmt($sql, $params)) {
          $markers = array();

          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $markers[] = array(
              $row['marker_folder'], $row['marker_id'],
              $row['marker_lat'], $row['marker_lng']
            );
          }

          if (count($markers) > 0) {
            foreach ($markers as $idx => $marker) {
              $this->spatialExtensions->removeSpatialPoint($marker[0], $marker[1]);

              $result = $result && $this->spatialExtensions->insertSpatialPoint(
                $marker[0], // folder id
                $marker[1], // marker id
                array($marker[2], $marker[3]) // latitude / longitude
              );
            }
          }
          unset($markers);
        }
      }

      return $result;
    }

    return FALSE;
  }
}
