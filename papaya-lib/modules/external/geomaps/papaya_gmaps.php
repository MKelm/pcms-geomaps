<?php
/**
* Backend for geo maps
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
* Backend for geo maps
*
* @package module_geomaps
* @author Martin Kelm <martinkelm@idxsolutions.de>
*/
class papaya_gmaps extends base_gmaps {
  
  var $keys = NULL;
  var $keysCount = 0;
  
  var $showMarkerDialog = TRUE;
  
  function initialize() {
  	$this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();
    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('folder_id');
    $this->initializeSessionParam('mode', array('folder_id', 'cmd'));
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);
    if (!isset($this->params['folder_id'])) {
    	$this->params['folder_id'] = 0;
    }
    parent::initialize($this->params['folder_id']);
  }
  
  function execute() {
  	switch (@$this->params['mode']) {
  	case 1:
  	  $this->loadKeys();
  	  switch (@$this->params['cmd']) {
  	  case 'add_key':
  	    if ($this->module->hasPerm(4, FALSE)) {
					if ($this->initAddKeyDialog()) {
						if (isset($this->params['save']) && $this->params['save'] &&
								$this->addKeyDialog->modified() &&
								$this->addKeyDialog->checkDialogInput()) {
						  if (!$this->validKeyExists($this->params)) {
						  	$this->saveKey($this->params, TRUE);
								$this->initAddKeyDialog(FALSE);
								$this->loadKeys();
						  } else {
						  	$apiTitle = ($this->params['key_type'] == 0) ? 'Google Maps API' :
      					  'Yahoo Maps API';
						    $this->addMsg(MSG_INFO, sprintf(
						      $this->_gt('A %s key for "%s" exists already.'),
								  $apiTitle, $this->params['key_host']));
							}
						}
					}
				}
  	    break;
  	  case 'edit_key':
				if ($this->module->hasPerm(4, FALSE)) {
					if (isset($this->params['key_id']) && $this->params['key_id'] >= 0) {
						if ($this->initEditKeyDialog()) {
							if (isset($this->params['save']) && $this->params['save'] &&
									$this->editKeyDialog->modified() &&
									$this->editKeyDialog->checkDialogInput()) {
								if (!$this->validKeyExists($this->params)) {
									$this->saveKey($this->params);
									$this->loadKeys();
								} else {
									$apiTitle = ($this->params['key_type'] == 0) ? 'Google Maps API' :
										'Yahoo Maps API';
									$this->addMsg(MSG_INFO, sprintf(
										$this->_gt('A %s key for "%s" exists already.'),
										$apiTitle, $this->params['key_host']));
								}	
							}
						}
					}
				}
				break;
			case 'del_key':
			  if ($this->module->hasPerm(4, FALSE)) {
					if (isset($this->params['confirm_delete']) && 
							(int)$this->params['confirm_delete'] == 1 &&
							isset($this->params['key_id']) && 
							(int)$this->params['key_id'] >= 0) {
						unset($this->params['cmd']);	
						if ($this->deleteKey($this->params['key_id'])) {
							$apiTitle = ($this->params['key_type'] == 0) ? 'Google Maps API' :
      					'Yahoo Maps API';
							$this->addMsg(MSG_INFO, sprintf($this->_gt('%s key (%s) deleted.'),
								$apiTitle, $this->params['key_id']));
							$this->keysCount--;
							$this->loadKeys();
						}
					}
				}
			  break;
		  }
  	default:
			$addMarkerType = 1;
			switch (@$this->params['cmd']) {
			case 'add_folder':
				if ($this->module->hasPerm(2, FALSE)) {
					$this->params['folder_id'] = NULL;
					if ($this->initFolderDialog(TRUE)) {
						if (isset($this->params['save']) && $this->params['save'] &&
								$this->addFolderDialog->modified() &&
								$this->addFolderDialog->checkDialogInput()) {
							$this->saveFolder($this->params, TRUE);
							$this->initFolderDialog(TRUE, FALSE);
							$this->loadFolders();
						}
					}
				}
				break;
			case 'edit_folder':
				if ($this->module->hasPerm(2, FALSE)) {
					if ($this->initFolderDialog()) {
						if (isset($this->params['save']) && $this->params['save'] &&
								$this->editFolderDialog->modified() &&
								$this->editFolderDialog->checkDialogInput()) {
							$this->saveFolder($this->params);
							$this->loadFolders();
						}
					}
				}
				break;
			case 'del_folder':
				if ($this->module->hasPerm(2, FALSE)) {
					if (isset($this->params['confirm_delete']) && 
							$this->params['confirm_delete'] == 1) {
						if (isset($this->markers) && is_array($this->markers) && 
								count($this->markers) > 0) {
							foreach ($this->markers as $markerId => $marker) {
								$this->deleteMarker($markerId);
							}
							$this->loadMarkers();
						}
						if (isset($this->params['folder_id']) && $this->params['folder_id'] > 0 
								&& $this->deleteFolder($this->params)) {
							$this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) deleted.'),
								$this->folders[$this->params['folder_id']]['folder_title'],
								$this->params['folder_id']));
							unset($this->params['cmd']);
							$this->params['folder_id'] = 0;
							$this->loadFolders();
							$this->loadMarkers(@$this->params['folder_id']);
						}
					}
				}
				break;
			case 'add_marker':
				if ($this->module->hasPerm(2, FALSE)) {
					if ($this->initAddMarkerDialog()) {
						if (isset($this->params['save']) && $this->params['save'] &&
								$this->addMarkerDialog->modified() &&
								$this->addMarkerDialog->checkDialogInput()) {
							if ($this->params['marker_lat'] != 0 && $this->params['marker_lng'] != 0) {
								$this->saveMarker($this->params, TRUE);
								$this->initAddMarkerDialog(FALSE);
								$this->loadMarkers(@$this->params['folder_id']);
							}	else {
								$this->addMsg(MSG_ERROR,
								  $this->_gt('Coordinates are not set properly.'));
								$this->addMsg(MSG_INFO,
								  $this->_gt('Use the last field to get coordinates if you have none.'));
							}
						}
						if (!isset($this->params['save']) || !$this->params['save']) {
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
				break;
			case 'edit_marker':
				if ($this->module->hasPerm(2, FALSE)) {
					if (isset($this->params['marker_id']) && 
							$this->params['marker_id'] >= 0 && $this->markersCount > 0) {
						if ($this->initEditMarkerDialog()) {
							if (isset($this->params['save']) && $this->params['save'] &&
									$this->editMarkerDialog->modified() &&
									$this->editMarkerDialog->checkDialogInput()) {
								if ($this->params['marker_lat'] != 0 && $this->params['marker_lng'] != 0) {
									$this->saveMarker($this->params);
									$this->loadMarkers(@$this->params['folder_id']);
								} else {
									$this->addMsg(MSG_ERROR,
								    $this->_gt('Coordinates are not set properly.'));
								  $this->addMsg(MSG_INFO,
								    $this->_gt('Use the address field to get coordinates if you have none.'));
								}
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
			case 'del_marker':
				if ($this->module->hasPerm(2, FALSE)) {
					if (isset($this->params['confirm_delete']) && 
							(int)$this->params['confirm_delete'] == 1 &&
							isset($this->params['marker_id']) && 
							(int)$this->params['marker_id'] >= 0) {
						unset($this->params['cmd']);	
						if ($this->deleteMarker($this->params['marker_id'])) {
							$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) deleted.'),
								$this->markers[$this->params['marker_id']]['marker_title'],
								$this->params['marker_id']));
							$this->markersCount--;
							$this->loadMarkers(@$this->params['folder_id']);
						}
					}
				}
				break;
			case 'export_markers':
				if (count($this->markers) > 0) {
					$this->exportMarkers();
				}
				break;
			case 'sort_markers_asc':
				if ($this->sortMarkersByTitle(@$this->params['folder_id'], 'ASC')) {
					$this->addMsg(MSG_INFO, $this->_gt('Markers sorted ascending by title.'));
					$this->loadMarkers(@$this->params['folder_id']);
				}
				break;
			case 'sort_markers_desc':
				if ($this->sortMarkersByTitle(@$this->params['folder_id'], 'DESC')) {
					$this->addMsg(MSG_INFO, $this->_gt('Markers sorted descending by title.'));
					$this->loadMarkers(@$this->params['folder_id']);
				}
				break;
			case 'set_up_marker':
				if (isset($this->params['marker_id']) && 
						$this->params['marker_id'] > 0) {
					$sort = $this->checkMarkersSort(@$this->params['folder_id'], 
										$this->params['marker_id']);
					if ($sort !== FALSE && (int)$sort > -1) {
						$this->switchMarkerPosition((int)$this->params['marker_id'], 
							(int)$sort, -1);
						$this->loadMarkers(@$this->params['folder_id']);
						$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) set up.'),
							$this->markers[$this->params['marker_id']]['marker_title'],
							$this->params['marker_id']));
					}
				}
				break;
			case 'set_down_marker':
				if (isset($this->params['marker_id']) && 
						$this->params['marker_id'] > 0) {
					$sort = $this->checkMarkersSort(@$this->params['folder_id'], 
										$this->params['marker_id']);
					if ($sort !== FALSE && (int)$sort > -1) {
						$this->switchMarkerPosition((int)$this->params['marker_id'],
							(int)$sort, 1);
						$this->loadMarkers(@$this->params['folder_id']);
						$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) set down.'),
							$this->markers[$this->params['marker_id']]['marker_title'],
							$this->params['marker_id']));
					}
				}
				break;
			}
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
  	switch (@$this->params['mode']) {
  	case 1:
  	  switch (@$this->params['cmd']) {
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
			switch (@$this->params['cmd']) {
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
    
    if ($this->module->hasPerm(4, FALSE)) {
    	$menubar->addButton('Markers', 
				$this->getLink(array('mode' => 0)),
				'categories-content-tags', '', $this->params['mode'] == 0);
		  $menubar->addButton('Keys', 
				$this->getLink(array('mode' => 1)),
				'categories-edit-access', '', $this->params['mode'] == 1);
    	$menubar->addSeperator();
    }
    
    switch (@$this->params['mode']) {
    case 1:
      if ($this->module->hasPerm(4, FALSE)) {
				$menubar->addButton('Add key', 
					$this->getLink(array('cmd' => 'add_key')),
					'actions-permission-add', '', $this->params['cmd'] == 'add_key');
				if ($this->params['key_id'] > 0 && 
				    @$this->params['cmd'] == 'edit_key') {
					$menubar->addButton('Delete key', 
						$this->getLink(array('cmd' => 'del_key', 
						'key_id' => $this->params['key_id'])),
						'actions-permission-delete', '', $this->params['cmd'] == 'del_key');
				}
			}
      break;
    default:
    	if ($this->module->hasPerm(2, FALSE)) {
				$menubar->addButton('Add folder', 
					$this->getLink(array('cmd' => 'add_folder')),
					'actions-folder-add', '', $this->params['cmd'] == 'add_folder');
				if ($this->params['folder_id'] > 0) {
					$menubar->addButton('Edit folder', 
						$this->getLink(array('cmd' => 'edit_folder', 
						'folder_id' => $this->params['folder_id'])),
						'actions-edit', '', $this->params['cmd'] == 'edit_folder');
					$menubar->addButton('Delete folder', 
						$this->getLink(array('cmd' => 'del_folder', 
						'folder_id' => $this->params['folder_id'])),
						'actions-folder-delete', '', $this->params['cmd'] == 'del_folder');
				}
				
				$toolbar = &new base_btnbuilder;
        $toolbar->images = $this->images;
        
				$toolbar->addButton('Add marker', 
					$this->getLink(array('cmd' => 'add_marker')),
					'actions-tag-add', '', $this->params['cmd'] == 'add_marker');
			  if ($this->params['marker_id'] > 0 &&
			      @$this->params['cmd'] == 'edit_marker') {
					$toolbar->addButton('Delete marker', 
						$this->getLink(array('cmd' => 'del_marker', 
						'marker_id' => $this->params['marker_id'])),
						'actions-tag-delete', '', $this->params['cmd'] == 'del_marker');
				}
			  
			}
			
      $toolbar->addSeperator();
			$toolbar->addButton('Sort markers ascending', 
			  $this->getLink(array('cmd' => 'sort_markers_asc')), 'actions/sort-asc.png');
			$toolbar->addButton('Sort markers descending', 
			  $this->getLink(array('cmd' => 'sort_markers_desc')), 'actions/sort-desc.png');
			
			if ($this->module->hasPerm(3, FALSE) && count($this->markers) > 0) {
			  $toolbar->addSeperator();
			  $toolbar->addButton('Export markers', 
				$this->getLink(array('cmd' => 'export_markers')), 'actions/save.png');
			}
			
			if ($str = $toolbar->getXML()) {
			  $this->layout->add(sprintf('<toolbar>%s</toolbar>'.LF,
				$str));
			}			
    }
    
		if ($str = $menubar->getXML()) {
		  $this->layout->addMenu(sprintf('<menu>%s</menu>'.LF,
			$str));
		}
  }
  
  function initFolderDialog($add = FALSE, $loadParams = TRUE) {
  	if ($add) {
  		unset($this->addFolderDialog);
  		$data = array();  
  	} else {
  		unset($this->editFolderDialog);
  	  $folder = $this->loadFolder($this->params['folder_id']);
  		$data = array('folder_title' => $folder['folder_title']);  
  	}
  	$cmd = ($add) ? 'add_folder' : 'edit_folder';

		$hidden = array(
			'cmd' => $cmd,
			'save' => 1,
		);
		$fields = array(
			'folder_title' => array(
				'Title', 'isAlphaNum', TRUE, 'input', 200
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
			$this->editFolderDialog->dialogDoubleButtons = FALSE;
			$this->editFolderDialog->msgs = &$this->msgs;
			$this->editFolderDialog->inputFieldSize = 'x-large';
			$this->editFolderDialog->tokenKeySuffix = 'gmaps';
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
      'cmd'     => $this->params['cmd'],
      'folder_id' => $this->params['folder_id'],
      'confirm_delete' => 1
    );
    $folder = $this->loadFolder($this->params['folder_id']);
    $msg = sprintf(
      $this->_gt('Do you want to delete the folder "%s" (%d) with markers?'),
      $folder['folder_title'], $this->params['folder_id']);
    $this->dialog = &new base_msgdialog($this, $this->paramName, 
      $hidden, $msg, 'question');
    $this->dialog->buttonTitle = $this->_gt('Yes');
    $this->layout->add($this->dialog->getMsgDialog());
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
    $this->addKeyDialog->dialogDoubleButtons = FALSE;
    $this->addKeyDialog->msgs = &$this->msgs;
    $this->addKeyDialog->dialogTitle = 
      papaya_strings::escapeHTMLChars($this->_gt('Add key'));
    $this->addKeyDialog->inputFieldSize = 'x-large';
    $this->addKeyDialog->tokenKeySuffix = 'gmaps';
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
    $key = $this->loadKey($this->params['key_id']);
				
		$data = array(
			'key_type'  => $key['key_type'],
			'key_host'  => $key['key_host'],
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
    $this->editKeyDialog->dialogDoubleButtons = FALSE;
    $this->editKeyDialog->msgs = &$this->msgs;
    $this->editKeyDialog->dialogTitle = 
      papaya_strings::escapeHTMLChars($this->_gt('Edit key'));
    $this->editKeyDialog->inputFieldSize = 'x-large';
    $this->editKeyDialog->tokenKeySuffix = 'gmaps';
    $this->editKeyDialog->expandPapayaTags = TRUE;
    $this->editKeyDialog->loadParams();

    if (isset($this->editKeyDialog) && 
        is_object($this->editKeyDialog)) {
    	return TRUE;
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
    $key = $this->loadKey($this->params['key_id']);
    $hidden = array(
      'cmd'            => $this->params['cmd'],
      'key_id'         => $this->params['key_id'],
      'key_type'       => $key['key_type'],
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
			'marker_desc' => array(
				'Description', 'isSomeText', TRUE, 'simplerichtext', 6
			),
			'marker_lat' => array(
			  'Latitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input', 20, '',
			  0
			),
			'marker_lng' => array(
			  'Longitude', '/[\+\-]?\d+(\.\d+)?/', TRUE, 'input', 20, '',
			  0
			),
			'Coordinates by address',
			'marker_address' => array(
			  'Value', 'isSomeText', FALSE, 'input', 200
			),
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
		$this->addMarkerDialog->dialogId = 'dlg';
    $this->addMarkerDialog->dialogDoubleButtons = FALSE;
    $this->addMarkerDialog->msgs = &$this->msgs;
    $this->addMarkerDialog->dialogTitle = 
      papaya_strings::escapeHTMLChars($this->_gt('Add marker'));
    $this->addMarkerDialog->inputFieldSize = 'x-large';
    $this->addMarkerDialog->tokenKeySuffix = 'gmaps';
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
    $marker = $this->loadMarker($this->params['marker_id']);
				
		$data = array(
			'marker_title' => $marker['marker_title'],
			'marker_desc' => $marker['marker_desc'],
			'marker_address' => $marker['marker_address'],
			'marker_lat' => $marker['marker_lat'],
			'marker_lng' => $marker['marker_lng']
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
		$this->editMarkerDialog->dialogId = 'dlg';
    $this->editMarkerDialog->dialogDoubleButtons = FALSE;
    $this->editMarkerDialog->msgs = &$this->msgs;
    $this->editMarkerDialog->dialogTitle = 
      papaya_strings::escapeHTMLChars($this->_gt('Edit marker'));
    $this->editMarkerDialog->inputFieldSize = 'x-large';
    $this->editMarkerDialog->tokenKeySuffix = 'gmaps';
    $this->editMarkerDialog->expandPapayaTags = TRUE;
    $this->editMarkerDialog->loadParams();

    if (isset($this->editMarkerDialog) && 
        is_object($this->editMarkerDialog)) {
    	return TRUE;
    }
    return FALSE;
  }
  
  function getAddMarkerDialog() {      
  	if (isset($this->addMarkerDialog) && 
  	    is_object($this->addMarkerDialog)) {  	
      $apiScript = $this->getApiScript(0);
  	  if (!$apiScript) {
  	  	$apiScript = $this->getApiScript(1);
  	  }
  	  if ($apiScript) {
  	  	$this->layout->add($this->addMarkerDialog->getDialogXML());
  	  	$this->layout->add($apiScript);
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
  	  
  	  $apiScript = $this->getApiScript(0);
  	  if (!$apiScript) {
  	  	$apiScript = $this->getApiScript(1);
  	  }
  	  if ($apiScript) {
  	  	$this->layout->add($this->editMarkerDialog->getDialogXML());
  	  	$this->layout->add($apiScript);
  	  } else {
  	  	$this->addMsg(MSG_ERROR, sprintf(
				  $this->_gt('Could not include suitable api for host "%s".'),
				  $_SERVER['HTTP_HOST']));
  	  }
	  }
  }
  
  function getApiScript($type) {
  	$apiKey = FALSE;
  	$sql = "SELECT key_value
							FROM %s 
						 WHERE key_host LIKE '%s'
							 AND key_type = %d";
		$params = array($this->tableKeys, $_SERVER['HTTP_HOST'],
		  $type);
		
		if ($res = $this->databaseQueryFmt($sql, $params)) {
			if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$apiKey = $row['key_value'];
			}
		}
  	
  	if ($apiKey !== FALSE)	{
  		$hiddenApiType = sprintf('<input type="hidden" id="dlg_api_type" name="api_type" value="%d" />', 
  		  $type);
  		switch($type) {
  		case 0:
  			$result = $hiddenApiType;
  			$result .= sprintf(
					'<script type="text/javascript" '.
					'src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=%s" />'.LF, 
					papaya_strings::escapeHTMLChars($apiKey)
				);
				$result .= '<script type="text/javascript" src="../papaya-script/geomaps.js" />';
				return $result;
				break;
  		case 1:
  		  $result = $hiddenApiType;
  			$result .= sprintf(
					'<script type="text/javascript" '.
					'src="http://api.maps.yahoo.com/ajaxymap?v=3.0&amp;appid=%s" />'.LF, 
					papaya_strings::escapeHTMLChars($apiKey)
				);
				$result .= '<script type="text/javascript" src="../papaya-script/geomaps.js" />';
				return $result;
  		}
  	}
  	return '';
  }
  
  function getDeleteMarkerDialog() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $hidden = array(
      'cmd' => 'del_marker',
      'marker_id' => @$this->params['marker_id'],
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
  	$result .= sprintf('<listview width="100%%" title="%s">'.LF, 
      $this->_gt('Folders'));
    $result .= '<items>'.LF;
    
    $selected = '';
    if ((!isset($this->params['folder_id']) || $this->params['folder_id'] == '0')
        && $this->params['cmd'] != 'add_folder') {
    	$selected = ' selected="selected"';
    } 
    $result .= sprintf('<listitem image="%s" title="%s" href="%s"%s/>'.LF, 
		  $this->images['items-desktop'], $this->_gt('Base'),  
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
    $result .= sprintf('<listview width="100%%" title="%s">'.LF, 
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
    $result .= sprintf('<listview width="100%%" title="%s">'.LF, 
      $this->_gt('Markers'));
    $result .= sprintf('<cols><col align="left">%s</col>'.
      '<col align="left">%s</col>',
      $this->_gt('Title'), $this->_gt('Coordinates'));
    if ($this->module->hasPerm(2, FALSE)) {
			$result .= sprintf('<col align="center">%s</col><col align="center">%s</col>',
				$this->_gt('Edit'), $this->_gt('Delete'));
			$result .= sprintf('<col align="center">%s</col><col align="center">%s</col>',
			  $this->_gt('Set up'), $this->_gt('Set down'));
    }    
    $result .= '</cols>';
    
    $count = 1;
    $maxCount = count($this->markers);
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
			  } else {
			  	$result .= '<subitem />';
			  }
			  if ($count < $maxCount) {
			  	$pushDownLink = $this->getLink(array('cmd' => 'set_down_marker', 
						'marker_id' => $markerId));
					$result .= sprintf('<subitem align="center"><a href="%s">'.
						'<glyph src="%s" /></a></subitem>'.LF,
						$pushDownLink, $this->images['actions-go-down']);
			  } else {
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
  
  function loadFolder($folderId) {
  	$sql = 'SELECT folder_id, folder_title
              FROM %s 
             WHERE folder_id = %d';
    $params = array($this->tableFolders, $folderId);
    
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row;
      }
    }
    return FALSE;
  }
  
  function loadKeys() {
  	$this->keys = array();
  	$sql = 'SELECT key_id, key_type, key_host, key_value 
              FROM %s 
             ORDER BY key_id, key_type, key_host ASC';
    $params = array($this->tableKeys);
    
    $this->keysCount = 0;
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->keys[$row['key_id']] = $row;
        $this->keysCount++;
      }
      return TRUE;      
    }
    return FALSE;
  }
  
  function loadKey($keyId) {
  	$sql = 'SELECT key_type, key_host, key_value 
              FROM %s 
             WHERE key_id = %d';
    $params = array($this->tableKeys, $keyId);
    
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row;
      }
    }
    return FALSE;
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
		if ($new) {
			$newId = $this->databaseInsertRecord($this->tableFolders, 'folder_id',
        array('folder_title' => $params['folder_title']));
      if ($newId !== FALSE && $newId > 0) {
      	$this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) added.'), 
					$params['folder_title'], $newId));
      }
      return $newId !== FALSE;
		} else {
			$this->addMsg(MSG_INFO, sprintf($this->_gt('Folder "%s" (%s) modified.'), 
				$params['folder_title'], $params['folder_id']));
		  $data = array('folder_title' => $params['folder_title']);
			return (FALSE !== $this->databaseUpdateRecord($this->tableFolders,
				$data, 'folder_id', (int)$params['folder_id']));
		}
  }
  
  function deleteFolder($folderId) {
  	return $this->databaseDeleteRecord($this->tableFolders, 
  	  'folder_id', $folderId);
  }
  
  function loadMarker($markerId) {
  	$sql = 'SELECT marker_id, marker_folder, marker_title, 
  	               marker_desc, marker_address, marker_lat, marker_lng
              FROM %s 
             WHERE marker_id = %d';
    $params = array($this->tableMarkers, $markerId);
    
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row;
      }
    }
    return FALSE;
  }
  
  function deleteKey($keyId) {
  	return $this->databaseDeleteRecord($this->tableKeys, 
  	  'key_id', $keyId);
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
  
  function saveMarker($params, $new = FALSE) {		
  	if ($this->params['folder_id'] != $params['marker_folder']) {
  		$this->params['folder_id'] = $params['marker_folder'];
			$this->sessionParams = $this->getSessionValue($this->sessionParamName);
			$this->initializeSessionParam('folder_id');
			$this->setSessionValue($this->sessionParamName, $this->sessionParams);
  	}		
		if ($new) {
			$newId = $this->databaseInsertRecord($this->tableMarkers, 'marker_id',
        array(
          'marker_folder'  => $params['marker_folder'],
          'marker_title' => $params['marker_title'],
          'marker_desc' => $this->addMarkerDialog->data['marker_desc'],
          'marker_address' => $params['marker_address'],
          'marker_lat' => $params['marker_lat'],
          'marker_lng' => $params['marker_lng']
        )
      );
      if (FALSE !== $newId && $newId > 0) {
      	$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) added.'), 
					$params['marker_title'], $newId));
      }
      return FALSE !== $newId;
		} else {
			$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) modified.'), 
				$params['marker_title'], $params['marker_id']));
				
		  $data = array(
				'marker_folder'  => $params['marker_folder'],
				'marker_title' => $params['marker_title'],
				'marker_desc' => $this->editMarkerDialog->data['marker_desc'],
				'marker_address' => $params['marker_address'],
				'marker_lat' => $params['marker_lat'],
				'marker_lng' => $params['marker_lng']
			);
			return (FALSE !== $this->databaseUpdateRecord($this->tableMarkers,
				$data, 'marker_id', (int)$params['marker_id']));
		}
  }
  
  function deleteMarker($markerId) {
  	return $this->databaseDeleteRecord($this->tableMarkers, 
  	  'marker_id', $markerId);
  }
}
