<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\helpers;

/**
 * Class Request
 * @package skeeks\cms\helpers
 */
class Image
{
    /**
     *
     * Путь до картинки, если же нет пути, то путь к заглушке.
     *
     * @param string $originalSrc
     * @param null $capSrc
     * @return string
     */
    static public function getSrc($originalSrc = '', $capSrc = null)
    {
        if ($originalSrc)
        {
            return (string) $originalSrc;
        }

        if (!$capSrc)
        {
            $capSrc = \Yii::$app->cms->noImageUrl;
        }

        return (string) $capSrc;
    }
}