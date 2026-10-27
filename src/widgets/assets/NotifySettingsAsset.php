<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;
use skeeks\cms\backend\assets\BackendUiAsset;

/**
 * The personal notification settings page (profile → "Уведомления").
 */
class NotifySettingsAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/notify-settings';

    public $css = [
        'notify-settings.css',
    ];

    public $js = [
        'notify-settings.js',
    ];

    public $depends = [
        BackendUiAsset::class,
    ];
}
