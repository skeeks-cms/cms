<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets\formInputs\selectTree\assets;

use yii\web\AssetBundle;

/**
 * Class SelectTreeInputWidgetAsset
 *
 * @package skeeks\cms\widgets\formInputs\selectTree\assets
 */
class SelectTreeInputWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/selectTree/assets/src';

    public $css = [
        'css/select-tree.css',
    ];

    public $js = [
        'js/select-tree.js',
    ];

    public $depends = [
        'skeeks\sx\assets\Core',
    ];
}
