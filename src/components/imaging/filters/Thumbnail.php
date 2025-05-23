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

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use skeeks\cms\components\storage\SkeeksSuppliersCluster;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\StorageFile;
use skeeks\imagine\Image;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

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
     * @var int прозрачность фона картинки 100 - непрозрачный 0 - прозрачный
     */
    public $ta = 100;

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
        
        if (!$this->m) {
            $this->m = ManipulatorInterface::THUMBNAIL_INSET;
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

    /**
     * @param StorageFile $cmsStorageFile
     * @return array|int[]
     */
    public function getDimensions(StorageFile $cmsStorageFile)
    {
        $result = [];
        
        if ($cmsStorageFile->cluster instanceof SkeeksSuppliersCluster) {
            return parent::getDimensions($cmsStorageFile);
        }

        if (!$cmsStorageFile->image_height || !$cmsStorageFile->image_width) {
            return $result;
        }

        if (!$this->w) {
            //Если ширина не указана нужно ее расчитать

            //Если размер оригинальной кратинки больше, то уменьшаем его
            if ($this->s == 1) {
                if ($this->h > $cmsStorageFile->image_height) {
                    $this->h = $cmsStorageFile->image_height;
                }
            }

            $width = ($cmsStorageFile->image_width * $this->h) / $cmsStorageFile->image_height;

            //Готовый результат
            $result['width'] = (int)$width;
            $result['height'] = (int)$this->h;

        } else {
            if (!$this->h) {

                //Если размер оригинальной кратинки больше, то уменьшаем его
                if ($this->s == 1) {
                    if ($this->w > $cmsStorageFile->image_width) {
                        $this->w = $cmsStorageFile->image_width;
                    }
                }

                $height = ($cmsStorageFile->image_height * $this->w) / $cmsStorageFile->image_width;

                //Готовый результат

                $result['width'] = (int)$this->w;
                $result['height'] = (int)round($height);

            } else {

                if ($this->m == ManipulatorInterface::THUMBNAIL_OUTBOUND) {
                    //TODO: доработать
                    $result['width'] = (int)$this->w;
                    $result['height'] = (int)$this->h;
                    
                } else {
                    $result['width'] = (int)$this->w;
                    $result['height'] = (int)$this->h;
                }
            }
        }

        return $result;
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

            Image::thumbnailV2($this->_originalRootFilePath, (int)round($width), $this->h, $this->m, $this->tb, (int) $this->ta)->save($this->_newRootFilePath, [
                'jpeg_quality' => $this->q,
                'webp_quality' => $this->q,
            ]);

        } else if (!$this->h) {
            $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();
            
            //Если размер оригинальной кратинки больше, то уменьшаем его
            if ($this->s == 1) {
                if ($this->w > $size->getWidth()) {
                    $this->w = $size->getWidth();
                }
            }
            
            
            $height = ($size->getHeight() * $this->w) / $size->getWidth();
            Image::thumbnailV2($this->_originalRootFilePath, $this->w, (int)round($height), $this->m, $this->tb, (int) $this->ta)->save($this->_newRootFilePath, [
                'jpeg_quality' => $this->q,
                'webp_quality' => $this->q,
            ]);
        } else {
            $size = Image::getImagine()->open($this->_originalRootFilePath)->getSize();

            //Для этого режима важно чтобы оригинальное фото было больше
            if ($this->m == ImageInterface::THUMBNAIL_OUTBOUND) {
                if ($size->getWidth() < $this->w || $size->getHeight() < $this->h) {
                    $this->m = ImageInterface::THUMBNAIL_INSET;
                }
            }
            
            $image = Image::thumbnailV2($this->_originalRootFilePath, $this->w, $this->h, $this->m, $this->tb, (int) $this->ta)->save($this->_newRootFilePath, [
                'jpeg_quality' => $this->q,
                'webp_quality' => $this->q,
            ]);
        }
    }
}