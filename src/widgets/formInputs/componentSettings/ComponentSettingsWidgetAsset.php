<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */

namespace skeeks\cms\widgets\formInputs\componentSettings;

use Yii;
use yii\web\AssetBundle;

/**
 * Class ComponentSettingsWidgetAsset
 * @package skeeks\cms\widgets\formInputs\componentSettings
 */
class ComponentSettingsWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/componentSettings/assets';

    public $css = [];

    public $js =
        [
            'component-settings.js',
        ];

    public $depends = [
        '\skeeks\sx\assets\Core',
    ];
}

