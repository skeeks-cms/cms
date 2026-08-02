<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;
use skeeks\cms\backend\assets\BackendUiAsset;
use yii\widgets\PjaxAsset;

/**
 * Presentation and refresh behavior for the current-user work schedule control.
 */
class CmsUserScheduleAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/user-schedule';

    public $css = [
        'user-schedule.css',
    ];

    public $js = [
        'user-schedule.js',
    ];

    public $depends = [
        BackendUiAsset::class,
        PjaxAsset::class,
    ];
}
