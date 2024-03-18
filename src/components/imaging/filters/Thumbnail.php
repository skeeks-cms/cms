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

use Imagine\Image\ManipulatorInterface;
use skeeks\imagine\Image;
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

    /**
     * @var int Если не задана ширина или высота, то проверять оригинальный размер файла и новый файл не будет крупнее
     */
    public $s = 1;

    /**
     * @var int прозрачность фона картинки
     */
    public $ta = 0;

    /**
     * @var string фоновый цвет превью картинки
     */
    public $tb = "FFF";
    
    public $m = ManipulatorInterface::THUMBNAIL_INSET;

    public function init()
    {
        parent::init();

        if (!$this->w && !$this->h) {
            throw new Exception("Необходимо указать ширину или высоту");
        }

        $q = (int)$this->q;
        if (!$q) {
            $this->q = (int)\Yii::$app->seo->img_preview_quality;
        }

        $q = (int)$this->q;

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
            //Если ширина не указана нужно ее расчитать
            $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();

            //Если размер оригинальной кратинки больше, то уменьшаем его
            if ($this->s == 1) {
                if ($this->h > $size->getHeight()) {
                    $this->h = $size->getHeight();
                }
            }
            

            $width = ($size->getWidth() * $this->h) / $size->getHeight();

            Image::thumbnailV2($this->_originalRootFilePath, (int)round($width), $this->h, $this->m, $this->tb, $this->ta)->save($this->_newRootFilePath, [
                'jpeg_quality' => $this->q,
                'webp_quality' => $this->q,
            ]);

        } else {
            if (!$this->h) {
                $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();
                
                //Если размер оригинальной кратинки больше, то уменьшаем его
                if ($this->s == 1) {
                    if ($this->w > $size->getWidth()) {
                        $this->w = $size->getWidth();
                    }
                }
                
                
                $height = ($size->getHeight() * $this->w) / $size->getWidth();
                Image::thumbnailV2($this->_originalRootFilePath, $this->w, (int)round($height), $this->m, $this->tb, $this->ta)->save($this->_newRootFilePath, [
                    'jpeg_quality' => $this->q,
                    'webp_quality' => $this->q,
                ]);
            } else {
                $image = Image::thumbnailV2($this->_originalRootFilePath, $this->w, $this->h, $this->m, $this->tb, $this->ta)->save($this->_newRootFilePath, [
                    'jpeg_quality' => $this->q,
                    'webp_quality' => $this->q,
                ]);
            }
        }
    }
}