<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */

namespace skeeks\cms\widgets\formInputs\daterange;

use Yii;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Class ComboTextInputWidgetAsset
 * @package skeeks\cms\widgets\formInputs\comboText
 */
class DaterangeInputWidgetAsset extends AssetBundle
{
    //public $sourcePath = '@skeeks/cms/widgets/formInputs/daterange/assets';

    public $css = [
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css'
    ];

    public $js = [
        'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
    ];

    public $depends = [
        JqueryAsset::class
    ];
}

