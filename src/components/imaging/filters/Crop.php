<?php
/**
 * Filter
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\imaging\filters;

use yii\base\Component;
use skeeks\imagine\Image;

/**
 * Class Filter
 * @package skeeks\cms\components\imaging
 */
class Crop extends \skeeks\cms\components\imaging\Filter
{
    public $w = 0;
    public $h = 0;
    public $s = [0, 0];

    protected function _save()
    {
        Image::crop($this->_originalRootFilePath, $this->w, $this->h, $this->s)->save($this->_newRootFilePath);
        Image::thumbnail($this->_originalRootFilePath, $this->w, $this->h, $this->s)->save($this->_newRootFilePath);
    }
}