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
 * Presentation for backend notifications and work reminder dialogs.
 */
class CmsWebNotifyAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/web-notify';

    public $css = [
        'web-notify.css',
    ];

    public $depends = [
        BackendUiAsset::class,
    ];
}
