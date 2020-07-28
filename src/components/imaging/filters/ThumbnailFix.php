<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\imaging\filters;

use Imagine\Image\Box;
use Imagine\Image\Point;
use skeeks\cms\components\imaging\Filter;
use skeeks\imagine\Image;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ThumbnailFix extends Filter
{
    public $w = 50;
    public $h = 0;

    protected function _save()
    {
        $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();

        if (!$this->h) {
            $this->h = $this->w;
        }

        $width = $this->w;
        $height = $this->h;

        $box = new Box($width, $height);
        $palette = new \Imagine\Image\Palette\RGB();
        $thumb = Image::getImagine()->create($box, $palette->color('#FFFFFF', 100));

        $startX = 0;
        $startY = 0;
        /*if ($size->getWidth() < $width) {
            $startX = ceil($width - $size->getWidth()) / 2;
        }
        if ($size->getHeight() < $height) {
            $startY = ceil($height - $size->getHeight()) / 2;
        }*/




        if ($size->getWidth() >= $size->getHeight()) {
            //Картинка горизонтальная
            /*$newWidth = $this->w;
            $newHeight = ($size->getHeight() * $this->w) / $size->getWidth();*/

            $originalImage = Image::getImagine()->open($this->_originalRootFilePath);
            $originalImage->resize($originalImage->getSize()->widen($this->w));;
            //$originalImage->save($this->_newRootFilePath);
        } else {
            /*$newHeight = $this->w;
            $newWidth = ($size->getWidth() * $this->w) / $size->getHeight();*/

            $originalImage = Image::getImagine()->open($this->_originalRootFilePath);
            $originalImage->resize($originalImage->getSize()->heighten($this->w));;
            //$originalImage->save($this->_newRootFilePath);
        }

        $size = $originalImage->getSize();
        if ($size->getWidth() < $width) {
            $startX = ceil($width - $size->getWidth()) / 2;
        }
        if ($size->getHeight() < $height) {
            $startY = ceil($height - $size->getHeight()) / 2;
        }

        $thumb->paste($originalImage, new Point($startX, $startY));
        $thumb->save($this->_newRootFilePath);
    }
}