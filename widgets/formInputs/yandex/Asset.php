<?php
/**
 * Набор js и css которые подключаются на всех страницах
 * AppAsset
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\formInputs\yandex;
use yii\web\AssetBundle;

/**
 * Class Asset
 * @package skeeks\cms\widgets\formInputs\yandex
 */
class Asset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/yandex/assets';

    public $css = [
        'map.css',
    ];
    public $js =
    [
        '//api-maps.yandex.ru/2.1/?load=package.full&lang=ru-RU',
        'map.js',
    ];
    public $depends = [
        '\skeeks\sx\assets\Core',
    ];
}
