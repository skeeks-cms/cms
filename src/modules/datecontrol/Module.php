<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */

namespace skeeks\cms\modules\datecontrol;

/**
 * Class Module
 * @package skeeks\cms\modules\datecontrol
 */
class Module extends \kartik\datecontrol\Module
{

    public $controllerNamespace = 'kartik\datecontrol\controllers';

    public $displaySettings = [
        \kartik\datecontrol\Module::FORMAT_DATE     => 'dd-MM-yyyy',
        \kartik\datecontrol\Module::FORMAT_TIME     => 'HH:mm:ss',
        \kartik\datecontrol\Module::FORMAT_DATETIME => 'dd-MM-yyyy HH:mm:ss',
    ];

    // format settings for saving each date attribute (PHP format example)
    public $saveSettings = [
        \kartik\datecontrol\Module::FORMAT_DATE     => 'php:U', // saves as unix timestamp
        \kartik\datecontrol\Module::FORMAT_TIME     => 'php:U', //'php:H:i:s',
        \kartik\datecontrol\Module::FORMAT_DATETIME => 'php:U', //'php:Y-m-d H:i:s',
    ];

    // set your display timezone
    public $displayTimezone = 'Europe/Moscow';

    // set your timezone for date saved to db
    public $saveTimezone = 'UTC';


    // automatically use kartik\widgets for each of the above formats
    public $autoWidget = true;

    // use ajax conversion for processing dates from display format to save format.
    public $ajaxConversion = true;

    // default settings for each widget from kartik\widgets used when autoWidget is true
    public $autoWidgetSettings = [
        \kartik\datecontrol\Module::FORMAT_DATE     => [
            'pluginOptions' => [
                'autoclose' => true,
                'todayBtn' => true,
            ],
        ],
        // example
        \kartik\datecontrol\Module::FORMAT_DATETIME => [
            'pluginOptions' => [
                'autoclose' => true,
                'todayBtn' => true,
            ],
        ],
        // setup if needed
        \kartik\datecontrol\Module::FORMAT_TIME     => [],
        // setup if needed
    ];

    // custom widget settings that will be used to render the date input instead of kartik\widgets,
    // this will be used when autoWidget is set to false at module or widget level.
    public $widgetSettings = [
        \kartik\datecontrol\Module::FORMAT_DATE => [
            //'class' => '\yii\jui\DatePicker', // example
            'class'   => '\kartik\datetime\DatePicker',
            'options' => [
                'dateFormat' => 'php:d-M-Y',
                'options'    => ['class' => 'form-control'],
            ],
        ],

        \kartik\datecontrol\Module::FORMAT_DATETIME => [
            'class'   => '\kartik\datetime\DateTimePicker',
            'options' => [
                'dateFormat' => 'php:d-F-Y H:i:s',
                'options'    => ['class' => 'form-control'],
            ],
        ],
    ];
    // other settings
}