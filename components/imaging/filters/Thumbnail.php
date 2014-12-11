<?php
/**
 * Thumbnail
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\imaging\filters;
use yii\base\Component;
use yii\imagine\Image;
use Imagine\Image\ManipulatorInterface;

/**
 * Class Thumbnail
 * @package skeeks\cms\components\imaging\filters
 */
class Thumbnail extends \skeeks\cms\components\imaging\Filter
{
    public $w       = 0;
    public $h       = 0;
    public $m       = ManipulatorInterface::THUMBNAIL_OUTBOUND;

    protected function _save()
    {
        Image::thumbnail($this->_originalRootFilePath, $this->w, $this->h, $this->m)->save($this->_newRootFilePath);
    }
}