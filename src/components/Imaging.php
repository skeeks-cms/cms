<?php
/**
 * Imaging
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\components\imaging\Filter;
use skeeks\cms\components\imaging\filters\Thumbnail;
use skeeks\cms\components\imaging\Preview;
use skeeks\cms\components\storage\ClusterLocal;
use skeeks\cms\components\storage\SkeeksSuppliersCluster;
use skeeks\cms\helpers\Image;
use skeeks\cms\models\StorageFile;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class Imaging
 * @package skeeks\cms\components
 */
class Imaging extends Component
{
    /**
     * @var array   Расширения файлов с которыми работают фильтры. Может и не совсем правильно указывать их тут... но пока будет так.
     */
    public $extensions =
        [
            "jpg",
            "png",
            "jpeg",
            "webp",
            "gif"
        ];

    /**
     * @var string  Соль подмешивается к параметрам
     */
    public $sold = "sold_for_check_params";

    /**
     * Константа для разбора URL - это некая метка, с этого момента идет указание фильтра
     */
    const THUMBNAIL_PREFIX = "sx-filter__";
    
    const STORAGE_FILE_PREFIX = "sx-o-file__";
    
    const DEFAULT_THUMBNAIL_FILENAME = "sx-file";

    //TODO: подумать над этим
    /*public $previewFilters = [
        'micro' => [
            'local' => [
                'class' => Thumbnail::class,
            ],
            'sx' => [
                'class' => Thumbnail::class,
            ]
        ],
        'small' => [
            'local' => [
                'class' => Thumbnail::class,
            ],
            'sx' => [
                'class' => Thumbnail::class,
            ]
        ],
        'medium' => [
            'local' => [
                'class' => Thumbnail::class,
            ],
            'sx' => [
                'class' => Thumbnail::class,
            ]
        ],
        'big' => [
            'local' => [
                'class' => Thumbnail::class,
            ],
            'sx' => [
                'class' => Thumbnail::class,
            ]
        ],
    ];*/


    /**
     * @param StorageFile $image
     * @param Filter      $filter
     * @param             $nameForSave
     * @param             $isWebP
     * @return Preview|void
     */
    public function getPreview(StorageFile $image = null, Filter $filter, $nameForSave = '', $isWebP = null)
    {
        if (!$image) {
            return new Preview([
                'src' => Image::getCapSrc(),
            ]);
        }
        
        if ($image->cluster instanceof ClusterLocal) {
            $data = $filter->getDimensions($image);
            $data['src'] = $this->thumbnailUrlOnRequest($image->src, $filter, $nameForSave, $isWebP);
            return new Preview($data);
        }

        if ($image->cluster instanceof SkeeksSuppliersCluster) {
            $data = $filter->getDimensions($image);
            $data['src'] = (string) ArrayHelper::getValue($image->sx_data, "previews.{$filter->sx_preview}.src", "");
            return new Preview($data);
        }

        return new Preview([
            'width' => $image->image_width,
            'height' => $image->image_height,
            'src' => $image->absoluteSrc,
        ]);
    }

    /**
     * Собрать URL на thumbnail, который будет сгенерирован автоматически в момент запроса.
     *
     * @deprecated
     *
     * @param $originalSrc          Путь к оригинальному изображению
     * @param Filter $filter Объект фильтр, который будет заниматься преобразованием
     * @param string $nameForSave Название для сохраненеия файла (нужно для сео)
     * @param null|boolean $isWebP Если передано null - настройки будут взяты из настроек сайта; true - не важно какие настройки картика будет webp; false - картинка не изменит расширение
     * @return string
     */
    public function thumbnailUrlOnRequest($originalSrc, Filter $filter, $nameForSave = '', $isWebP = null)
    {
        $originalSrc = (string)$originalSrc;
        $extension = static::getExtension($originalSrc);

        if (!$extension) {
            return $originalSrc;
        }

        if ($isWebP === null) {
            if (isset(\Yii::$app->seo)) {
                //Если параметр не передан принудительно, то нужно взять из настроек сайта
                if (isset(\Yii::$app->mobileDetect) && \Yii::$app->mobileDetect->isDesktop) {
                    $isWebP = (bool) \Yii::$app->seo->is_webp;
                } else {
                    $isWebP = (bool) \Yii::$app->seo->is_mobile_webp;
                }
            }
        }

        $outExtension = null;
        if ($isWebP === true) {
            $outExtension = "webp";
        }

        if ($outExtension === null) {
            $outExtension = $extension;
        }


        if (!$this->isAllowExtension($extension)) {
            return $originalSrc;
        }

        if (!$nameForSave) {
            $nameForSave = static::DEFAULT_THUMBNAIL_FILENAME;
        }

        $params = [];
        if ($filter->getConfig()) {
            $params = $filter->getConfig();
        }

        if ($outExtension != $extension) {
            $params['ext'] = $extension;
        }


        $replacePart = DIRECTORY_SEPARATOR . static::THUMBNAIL_PREFIX . $filter->id
            . ($params ? DIRECTORY_SEPARATOR . $this->getParamsCheckString($params) : "")
            . DIRECTORY_SEPARATOR . $nameForSave;

        $imageSrcResult = str_replace('.' . $extension, $replacePart . '.' . $outExtension, $originalSrc);

        if ($params) {
            $imageSrcResult = $imageSrcResult . '?' . http_build_query($params);
        }

        return $imageSrcResult;
    }

    /**
     * @param $extension
     * @return bool
     */
    public function isAllowExtension($extension)
    {
        return (bool)in_array(strtolower($extension), $this->extensions);
    }

    /**
     * @param $filePath
     * @return string|bool
     */
    public static function getExtension($filePath)
    {
        $parts = explode(".", $filePath);
        $extension = end($parts);

        if (!$extension) {
            return false;
        }

        //Убираются гет параметры
        $extension = explode("?", $extension);
        $extension = $extension[0];
        return $extension;
    }

    /**
     * Проверочная строка параметров.
     *
     * @param array $params
     * @return string
     */
    public function getParamsCheckString($params = [])
    {
        if ($params) {
            return md5($this->sold . http_build_query($params));
        }

        return "";
    }

    /**
     * @depricated
     *
     * @param $imageSrc
     * @param Filter $filter
     */
    public function getImagingUrl($imageSrc, Filter $filter)
    {
        return $this->thumbnailUrlOnRequest($imageSrc, $filter);
    }
}