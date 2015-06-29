<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */
namespace skeeks\cms\widgets\installer;
use yii\web\AssetBundle;
/**
 * Class InstallerWidgetAsset
 * @package skeeks\cms\assets
 */
class InstallerWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/installer/assets';

    public $css = [];

    public $js =
    [
        'progress-bar.js',
        'tasks.js',
        'installer-widget.js',
    ];

    public $depends = [
        '\skeeks\cms\assets\JsTaskManagerAsset',
    ];
}
