<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */

namespace skeeks\cms\widgets\formInputs\comboText;

use Yii;
use yii\web\AssetBundle;

/**
 * Class ComboTextInputWidgetAsset
 * @package skeeks\cms\widgets\formInputs\comboText
 */
class ComboTextInputWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/comboText/assets';

    public $css = [];

    public $js =
        [
            'combo-widget.js',
        ];

    public $depends = [
        '\skeeks\sx\assets\Core',
    ];
}

