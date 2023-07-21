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
use skeeks\imagine\Image;
use Imagine\Image\ManipulatorInterface;
use yii\base\Exception;

/**
 * Class Thumbnail
 * @package skeeks\cms\components\imaging\filters
 */
class Thumbnail extends \skeeks\cms\components\imaging\Filter
{
    public $w = 50;
    public $h = 50;
    /**
     * @var int Качество сохраняемого фото
     */
    public $q;
    public $m = ManipulatorInterface::THUMBNAIL_INSET;

    public function init()
    {
        parent::init();

        if (!$this->w && !$this->h) {
            throw new Exception("Необходимо указать ширину или высоту");
        }

        $q = (int) $this->q;
        if (!$q) {
            $this->q = (int) \Yii::$app->seo->img_preview_quality;
        }

        $q = (int) $this->q;

        if ($q < 10) {
            $this->q = 10;
        }

        if ($q > 100) {
            $this->q = 100;
        }

    }

    protected function _save()
    {
        /*if (!$this->m) {
            $this->m = ManipulatorInterface::THUMBNAIL_INSET;
        }*/
        if (!$this->w) {
            $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();
            $width = ($size->getWidth() * $this->h) / $size->getHeight();

            Image::thumbnailV2($this->_originalRootFilePath, (int)round($width), $this->h,
                $this->m)->save($this->_newRootFilePath, [
                        'jpeg_quality' => $this->q,
                        'webp_quality' => $this->q,
                    ]);

        } else {
            if (!$this->h) {
                $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();
                $height = ($size->getHeight() * $this->w) / $size->getWidth();
                Image::thumbnailV2($this->_originalRootFilePath, $this->w, (int)round($height),
                    $this->m)->save($this->_newRootFilePath, [
                        'jpeg_quality' => $this->q,
                        'webp_quality' => $this->q,
                    ]);
            } else {
                $image = Image::thumbnailV2($this->_originalRootFilePath, $this->w, $this->h,
                    $this->m)->save($this->_newRootFilePath, [
                        'jpeg_quality' => $this->q,
                        'webp_quality' => $this->q,
                    ]);
            }
        }
    }
}