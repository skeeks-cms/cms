<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */

namespace skeeks\cms\helpers;

use skeeks\cms\components\imaging\Filter;

/**
 * Class Request
 * @package skeeks\cms\helpers
 */
class Image
{
    /**
     * @return string
     */
    public static function getCapSrc()
    {
        return (string)\Yii::$app->cms->noImageUrl;
    }

    /**
     *
     * Путь до картинки, если же нет пути, то путь к заглушке.
     *
     * @param string $originalSrc
     * @param null $capSrc
     * @return string
     */
    public static function getSrc($originalSrc = '', $capSrc = null)
    {
        if ($originalSrc) {
            return (string)$originalSrc;
        }

        if (!$capSrc) {
            $capSrc = static::getCapSrc();
        }

        return (string)$capSrc;
    }

    /**
     * @param string $originalSrc
     * @param Filter $filter
     * @param string $nameForSave
     * @param null $capSrc
     * @return string
     */
    public static function thumbnailUrlOnRequest($originalSrc = '', Filter $filter, $nameForSave = '', $capSrc = null)
    {
        if ($originalSrc) {
            return \Yii::$app->imaging->thumbnailUrlOnRequest($originalSrc, $filter, $nameForSave = '');

        } else {
            return static::getCapSrc();
        }
    }
}