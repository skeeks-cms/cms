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
    public $sourcePath = '@skeeks/cms/widgets/formInputs/daterange/assets/src';

    public $css = [
        /*'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css',*/
        'css/daterangepicker-3.1.css'
    ];

    public $js = [
       /* 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',*/
        'js/moment-2.18.1.min.js',
        'js/daterangepicker-3.1.min.js',
    ];

    public $depends = [
        JqueryAsset::class
    ];
}

