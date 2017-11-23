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
use yii\base\Component;

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
    const DEFAULT_THUMBNAIL_FILENAME = "sx-file";


    /**
     * Собрать URL на thumbnail, который будет сгенерирован автоматически в момент запроса.
     *
     *
     * @param $originalSrc          Путь к оригинальному изображению
     * @param Filter $filter Объект фильтр, который будет заниматься преобразованием
     * @param string $nameForSave Название для сохраненеия файла (нужно для сео)
     * @return string
     */
    public function thumbnailUrlOnRequest($originalSrc, Filter $filter, $nameForSave = '')
    {
        $originalSrc = (string)$originalSrc;
        $extension = static::getExtension($originalSrc);

        if (!$extension) {
            return $originalSrc;
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


        $replacePart = DIRECTORY_SEPARATOR . static::THUMBNAIL_PREFIX . $filter->id
            . ($params ? DIRECTORY_SEPARATOR . $this->getParamsCheckString($params) : "")
            . DIRECTORY_SEPARATOR . $nameForSave;

        $imageSrcResult = str_replace('.' . $extension, $replacePart . '.' . $extension, $originalSrc);

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
     * TODO:: depricated
     * @param $imageSrc
     * @param Filter $filter
     */
    public function getImagingUrl($imageSrc, Filter $filter)
    {
        return $this->thumbnailUrlOnRequest($imageSrc, $filter);
    }


    /**
     * @param $filterCode
     * @return string
     */
    protected function _assembleParams(Filter $filter)
    {
        /*$params[] = $filter->getId();

        if ($filter->getConfig())
        {
            $params[] = $filter->getConfig();
        }

        $result = String::compressBase64EncodeUrl($params);

        $result = str_split($result, $this->splitLongNames);
        $result = implode("/", $result);

        return $result;*/
    }
}