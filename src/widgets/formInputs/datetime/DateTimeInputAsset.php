<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\datetime;

use yii\web\AssetBundle;

/**
 * The date and time field (DateTimeInputWidget). Registered only by pages that
 * render the field. jQuery is used only to exchange `change` events with forms.
 */
class DateTimeInputAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/datetime/assets';

    public $css = [
        'datetime.css',
    ];

    public $js = [
        'datetime.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
