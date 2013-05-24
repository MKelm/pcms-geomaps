<?php
/**
 * Dynamic image module to decorate marker icons
 *
 * @copyright 2007 by Martin Kelm - All rights reserved.
 * @link http://www.idxsolutions.de
 * @licence GNU General Public Licence (GPL) 2 http://www.gnu.org/copyleft/gpl.html
 *
 * You can redistribute and/or modify this script under the terms of the GNU General
 * Public License (GPL) version 2, provided that the copyright and license notes,
 * including these lines, remain unmodified. This script is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@idxsolutions.de>
 */

/**
 * Base class for image plugins
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_dynamicimage.php');

/**
 * Dynamic image module to decorate marker icons
 *
 * @package module_geomaps
 * @author Martin Kelm <martinkelm@idxsolutions.de>
 */
class image_geomaps_icon extends base_dynamicimage {

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'Anchor',
    'anchor_image_id' => array('Image ID', 'isAlphaNum', FALSE, 'mediafile', 200),
    'anchor_alignment' => array('Alignment', 'isNum', TRUE, 'combo',
      array(1 => 'bottom', 2 => 'left', 3 => 'top', 4 => 'right'), '', 1)
  );

  /**
  * Attribute fields
  * @var array $attributeFields
  */
  var $attributeFields = array(
    'image_guid' => array('Image', 'isGuid', FALSE, 'imagefixed', 32, '', ''),
    'width' => array('Width', 'isNum', FALSE, 'input', 10, '', 0),
    'height' => array('Height', 'isNum', FALSE, 'input', 10, '', 0)
  );

  /**
   * Thumbnail object to get icon thumbnails
   * @var object $thumbnailObj
   */
  var $thumbnailObj = NULL;

  /**
   * Initialize thumbnail object
   * @return boolean status
   */
  function initThumbnailObj() {
    if (!is_object($this->thumbnailObj) || !is_a($this->thumbnailObj, 'base_thumbnail')) {
      include_once(PAPAYA_INCLUDE_PATH.'system/base_thumbnail.php');
      $this->thumbnailObj = &new base_thumbnail();

      if (is_object($this->thumbnailObj) && is_a($this->thumbnailObj, 'base_thumbnail')) {
        return TRUE;
      }
    } else {
      return TRUE;
    }
    return FALSE;
  }

  /**
  * generate the image
  *
  * @param object base_imagegenerator &$controller controller object
  * @access public
  * @return image $result resource image
  */
  function &generateImage(&$controller) {
    $result = NULL;

    // validate anchor image
    if (empty($this->data['anchor_image_id']) || empty($this->data['anchor_alignment']) ||
        !($anchorImage = &$controller->getMediaFileImage(
          $this->data['anchor_image_id']
        ))) {
      return $result;
    }

    // validate icon image
    if (empty($this->attributes['image_guid']) ||
        !($iconImage = &$controller->getMediaFileImage(
          $this->attributes['image_guid']
        ))) {
      return $result;
    }

    // generate thumb image from original icon image
    if (!empty($this->attributes['width']) && !empty($this->attributes['height']) &&
        $this->initThumbnailObj()) {

      $thumbFileName = $this->thumbnailObj->getThumbnail(
        $this->attributes['image_guid'], NULL,
        $this->attributes['width'], $this->attributes['height'], 'mincrop', NULL
      );

      $thumbFile = PAPAYA_PATH_THUMBFILES.
        $this->thumbnailObj->getThumbFilePath($thumbFileName).$thumbFileName;

      $iconImage = &$controller->loadImage($thumbFile);
    }

    // get max image size and new image positions
    $newAnchorPos = array(0, 0);
    $newIconPos = array(0, 0);
    switch ($this->data['anchor_alignment']) {
    case 1: // bottom
    case 3: // top
      $newWidth = imagesx($iconImage);
      if (imagesx($anchorImage) > $newWidth) {
        $newWidth = imagesx($anchorImage);
        // set new image positions
        $newIconPos[0] = (int)(($newWidth - imagesx($iconImage)) / 2);
        $newAnchorPos[0] = 0;
      } else {
        $newIconPos[0] = 0;
        $newAnchorPos[0] = (int)(($newWidth - imagesx($anchorImage)) / 2);
      }
      $newHeight = imagesy($iconImage) + imagesy($anchorImage);
      break;
    case 2: // left
    case 4: // right
      $newHeight = imagesy($iconImage);
      if (imagesy($anchorImage) > $newHeight) {
        $newHeight = imagesy($anchorImage);
        // set new image positions
        $newIconPos[1] = (int)(($newHeight - imagesy($iconImage)) / 2);
        $newAnchorPos[1] = 0;
      } else {
        $newIconPos[1] = 0;
        $newAnchorPos[1] = (int)(($newHeight - imagesy($anchorImage)) / 2);
      }
      $newWidth = imagesx($iconImage) + imagesx($anchorImage);
      break;
    }

    // get missing destination positions
    switch ($this->data['anchor_alignment']) {
    case 1: // bottom
      $newIconPos[1] = 0;
      $newAnchorPos[1] = imagesy($iconImage);
      break;
    case 2: // left
      $newIconPos[0] = imagesx($anchorImage);
      $newAnchorPos[0] = 0;
      break;
    case 3: // top
      $newIconPos[1] = imagesy($anchorImage);
      $newAnchorPos[1] = 0;
      break;
    case 4: // right
      $newIconPos[0] = 0;
      $newAnchorPos[0] = imagesx($iconImage);
      break;
    }

    // create empty image
    $result = &$this->imageCreateAlpha($newWidth, $newHeight);

    // copy icon image
    imagecopy($result, $iconImage, $newIconPos[0], $newIconPos[1], 0, 0,
      imagesx($iconImage), imagesy($iconImage));

    // copy anchor image
    imagecopy($result, $anchorImage, $newAnchorPos[0], $newAnchorPos[1], 0, 0,
      imagesx($anchorImage), imagesy($anchorImage));

    return $result;

  }

  /**
  * create a image with transparent background
  *
  * @access public
  * @return image resource id
  */
  function &imageCreateAlpha($imageWidth, $imageHeight) {
    $result = imageCreateTrueColor($imageWidth, $imageHeight);
    imageSaveAlpha($result, TRUE);
    imageAlphaBlending($result, FALSE);
    $bgColor = imagecolorallocatealpha($result, 220, 220, 220, 127);
    imagefill($result, 0, 0, $bgColor);
    imageAlphaBlending($result, TRUE);
    return $result;
  }

}
?>
