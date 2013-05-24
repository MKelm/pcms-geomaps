<?php
/**
* Backend for geo maps
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
  
  function initialize() {
  	$this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();
    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('folder_id');
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);
    if (!isset($this->params['folder_id'])) {
    	$this->params['folder_id'] = 0;
    }    
    parent::initialize($this->params['folder_id']);
  }
  
  function execute() {
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
  	case 'add_marker_coor':
      $addMarkerType = 2;
    case 'add_marker_addr':
      if ($this->module->hasPerm(2, FALSE)) {
				if ($this->initAddMarkerDialog($addMarkerType)) {
					if (isset($this->params['save']) && $this->params['save'] &&
							$this->addMarkerDialog->modified() &&
							$this->addMarkerDialog->checkDialogInput()) {
						$this->saveMarker($this->params, TRUE);
						$this->initAddMarkerDialog($addMarkerType, FALSE);
						$this->loadMarkers(@$this->params['folder_id']);
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
							$this->saveMarker($this->params);
							$this->loadMarkers(@$this->params['folder_id']);
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
    case 'move_up_marker':
      if (isset($this->params['marker_id']) && 
          $this->params['marker_id'] > 0) {
      	$sort = $this->checkMarkersSort(@$this->params['folder_id'], 
      	          $this->params['marker_id']);
      	if ($sort !== FALSE && (int)$sort > -1) {
     			$this->moveMarker((int)$this->params['marker_id'], 
     			  (int)$sort, -1);
     			$this->loadMarkers(@$this->params['folder_id']);
     			$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) moved up.'),
      		  $this->markers[$this->params['marker_id']]['marker_title'],
      		  $this->params['marker_id']));
     	  }
      }
      break;
    case 'move_down_marker':
      if (isset($this->params['marker_id']) && 
          $this->params['marker_id'] > 0) {
      	$sort = $this->checkMarkersSort(@$this->params['folder_id'], 
      	          $this->params['marker_id']);
      	if ($sort !== FALSE && (int)$sort > -1) {
      		$this->moveMarker((int)$this->params['marker_id'],
      		  (int)$sort, 1);
      		$this->loadMarkers(@$this->params['folder_id']);
      		$this->addMsg(MSG_INFO, sprintf($this->_gt('Marker "%s" (%s) moved down.'),
      		  $this->markers[$this->params['marker_id']]['marker_title'],
      		  $this->params['marker_id']));
      	}
      }
      break;
  	}
  }
  
  function moveMarker($markerId, $currentSort, $dir) {
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
  	$this->getToolbar();
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
  	case 'add_marker_coor':
		case 'add_marker_addr':
		  if ($this->module->hasPerm(2, FALSE)) {
				$this->getAddMarkerDialog();
		  }
			break;
		case 'edit_marker':
		  if ($this->module->hasPerm(2, FALSE)) {
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
  	
  	include_once(PAPAYA_INCLUDE_PATH.'system/base_switch_richtext.php');
    $this->switchRichtext = &base_switch_richtext::getInstance($this);
    $this->layout->addLeft($this->switchRichtext->getSwitchRichtextDialog());
  }
  
  function getToolbar() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_btnbuilder.php');
    $toolbar = &new base_btnbuilder;
    $toolbar->images = $this->images;
    
    if ($this->module->hasPerm(2, FALSE)) {
    	$toolbar->addButton('Add folder', 
				$this->getLink(array('cmd' => 'add_folder')),
				59, '', $this->params['cmd'] == 'add_folder');
		  if ($this->params['folder_id'] > 0) {
		  	$toolbar->addButton('Edit folder', 
					$this->getLink(array('cmd' => 'edit_folder', 
					'folder_id' => $this->params['folder_id'])),
					67, '', $this->params['cmd'] == 'edit_folder');
			  $toolbar->addButton('Delete folder', 
					$this->getLink(array('cmd' => 'del_folder', 
					'folder_id' => $this->params['folder_id'])),
					56, '', $this->params['cmd'] == 'del_folder');
		  }
		  
    	$toolbar->addSeperator();
			$toolbar->addButton('Add marker by coordinates', 
				$this->getLink(array('cmd' => 'add_marker_coor')),
				'addpin.gif', '', $this->params['cmd'] == 'add_marker_coor');
			$toolbar->addButton('Add marker by address', 
				$this->getLink(array('cmd' => 'add_marker_addr')),
				'addpin.gif', '', $this->params['cmd'] == 'add_marker_addr');
    }
    
    $toolbar->addSeperator();
    $toolbar->addButton('Sort markers ascending', 
		  $this->getLink(array('cmd' => 'sort_markers_asc')), 147);
		$toolbar->addButton('Sort markers descending', 
		  $this->getLink(array('cmd' => 'sort_markers_desc')), 148);
		  
    if ($this->module->hasPerm(3, FALSE) && count($this->markers) > 0) {
    	$toolbar->addSeperator();
			$toolbar->addButton('Export markers as kml', 
				$this->getLink(array('cmd' => 'export_markers')), 96);
    }
		      
    if ($str = $toolbar->getXML()) {
      $this->layout->addMenu(sprintf('<menu ident="%s">%s</menu>'.LF,
        'add', $str));
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
    $this->dialog = &new base_msgdialog($this, $this->paramName, $hidden, $msg,
      'question');
    $this->dialog->buttonTitle = $this->_gt('Yes');
    $this->layout->add($this->dialog->getMsgDialog());
  }
  
  
  function getMarkerEditFields($markerType) {
  	$folders = array($this->_gt('Base'));
  	if (isset($this->folders) && is_array($this->folders) && 
		    count($this->folders) > 0) {
		  foreach ($this->folders as $folderId => $folder) {
		  	$folders[$folderId] = $folder['folder_title'];
		  }  	
  	}  	
  	$fields = array(
  	  'marker_folder' => array('Folder', 'isNum', TRUE, 'combo', 
  	    $folders, '', $this->params['folder_id']),
			'marker_title' => array(
				'Title', 'isAlphaNum', TRUE, 'input', 200
			),
			'marker_desc' => array(
				'Description', 'isSomeText', TRUE, 'simplerichtext', 6
			)
		);
		if ($markerType == 1) {
			$fields['marker_address'] = array('Address', 'isSomeText', 
				TRUE, 'input', 400);
		} else {
			$fields['marker_lat'] = array('Latitude', '/[\+\-]?\d+(\.\d+)?/', 
				TRUE, 'input', 20);
			$fields['marker_lng'] = array('Longitude', '/[\+\-]?\d+(\.\d+)?/', 
				TRUE, 'input', 20);
		}
		return $fields;
  }
  
  function initAddMarkerDialog($markerType, $loadParams = TRUE) {
  	unset($this->addMarkerDialog);
		
		$cmd = ($markerType == 2) ? 
			'add_marker_coor' : 'add_marker_addr';
		$hidden = array(
			'cmd' => $cmd,
			'save' => 1,
			'marker_type' => $markerType,
		);
		      
		$fields = $this->getMarkerEditFields($markerType);
		$data = array(); 
		
		include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
		$this->addMarkerDialog = &new base_dialog($this, $this->paramName, 
		  $fields, $data, $hidden);
		$this->addMarkerDialog->baseLink = $this->baseLink;
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
			'marker_desc' => $marker['marker_desc']
		);
		if ($marker['marker_type'] == 1)  {
			$data['marker_address'] = $marker['marker_address'];
		} else {
			$data['marker_lat'] = $marker['marker_lat'];
			$data['marker_lng'] = $marker['marker_lng'];
		}
		
		$hidden = array(
			'cmd' => 'edit_marker',
			'save' => 1,
			'marker_type' => $marker['marker_type'],
			'marker_id' => $this->params['marker_id']
		);
		$fields = $this->getMarkerEditFields($marker['marker_type']);
		
		include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
		$this->editMarkerDialog = &new base_dialog($this, $this->paramName, 
		  $fields, $data, $hidden);
		$this->editMarkerDialog->baseLink = $this->baseLink;
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
      $this->layout->add($this->addMarkerDialog->getDialogXML());
	  }
  }
  
  function getEditMarkerDialog() {      
  	if (isset($this->editMarkerDialog) && 
  	    is_object($this->editMarkerDialog)) {  	
			$this->layout->add($this->editMarkerDialog->getDialogXML());
	  }
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
		  $this->images[55], $this->_gt('Base'),  
		    $this->getLink(array('folder_id' => 0)), $selected);
		  
		if (isset($this->folders) && is_array($this->folders) && 
		    count($this->folders) > 0) {
			foreach ($this->folders as $folderId => $folder) {
				$selected = '';
				if (isset($this->params['folder_id']) && $this->params['folder_id'] == $folderId) {
					$folderImage = 57;
					$selected = ' selected="selected"';
				} else {
					$folderImage = 56;
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
  
	function getMarkersList() {		
    $result .= sprintf('<listview width="100%%" title="%s">'.LF, 
      $this->_gt('Markers'));
    $result .= sprintf('<cols><col align="left">%s</col>'.
      '<col align="left">%s</col>',
      $this->_gt('Title'), $this->_gt('Location'));
    if ($this->module->hasPerm(2, FALSE)) {
			$result .= sprintf('<col align="center">%s</col><col align="center">%s</col>',
				$this->_gt('Edit'), $this->_gt('Delete'));
			$result .= sprintf('<col align="center">%s</col><col align="center">%s</col>',
			  $this->_gt('Up'), $this->_gt('Down'));
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
				  $this->images[163], $editLink,
				  papaya_strings::escapeHTMLChars($marker['marker_title']));
    	} else {
    		$result .= sprintf('<listitem image="%s" title="%s">'.LF, 
				  $this->images[163], 
				  papaya_strings::escapeHTMLChars($marker['marker_title']));
    	}

      switch ($marker['marker_type']) {
      case '1':
         $result .= sprintf('<subitem>%s</subitem>'.LF,
           papaya_strings::escapeHTMLChars($marker['marker_address'])); 
      	 break;
      default: 
        $result .= sprintf('<subitem>%f/%f</subitem>'.LF,
          papaya_strings::escapeHTMLChars($marker['marker_lat']), 
          papaya_strings::escapeHTMLChars($marker['marker_lng'])); 
      }
      
      if ($this->module->hasPerm(2, FALSE)) {
				$result .= sprintf('<subitem align="center"><a href="%s">'.
					'<glyph src="%s" /></a></subitem>'.LF,
					$editLink, $this->images[14]);
				$delLink = $this->getLink(array('cmd' => 'del_marker', 
					'marker_id' => $markerId));
				$result .= sprintf('<subitem align="center"><a href="%s">'.
					'<glyph src="%s" /></a></subitem>'.LF,
					$delLink, $this->images[10]);
				if ($count > 1) {
			  	$pushUpLink = $this->getLink(array('cmd' => 'move_up_marker', 
						'marker_id' => $markerId));
					$result .= sprintf('<subitem align="center"><a href="%s">'.
						'<glyph src="%s" /></a></subitem>'.LF,
						$pushUpLink, $this->images[6]);
			  } else {
			  	$result .= '<subitem />';
			  }
			  if ($count < $maxCount) {
			  	$pushDownLink = $this->getLink(array('cmd' => 'move_down_marker', 
						'marker_id' => $markerId));
					$result .= sprintf('<subitem align="center"><a href="%s">'.
						'<glyph src="%s" /></a></subitem>'.LF,
						$pushDownLink, $this->images[7]);
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
  	$sql = 'SELECT marker_id, marker_folder, marker_type, marker_title, 
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
  
  function saveMarker($params, $new = FALSE) {
  	if ($params['marker_type'] == 1) {
  		$params['marker_lat'] = 0.000000;
  		$params['marker_lng'] = 0.000000;
  	} else {
  		$params['marker_address'] = '';
  	}  	
  	if ($this->params['folder_id'] != $params['marker_folder']) {
  		$this->params['folder_id'] = $params['marker_folder'];
			$this->sessionParams = $this->getSessionValue($this->sessionParamName);
			$this->initializeSessionParam('folder_id');
			$this->setSessionValue($this->sessionParamName, $this->sessionParams);
  	}		
		if ($new) {
			$newId = $this->databaseInsertRecord($this->tableMarkers, 'marker_id',
        array(
          'marker_type'  => $params['marker_type'],
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
				'marker_type'  => $params['marker_type'],
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
