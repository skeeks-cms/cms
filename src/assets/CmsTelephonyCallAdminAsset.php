<?php

namespace skeeks\cms\assets;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/**
 * Page-specific presentation for the administrative telephony collection.
 */
class CmsTelephonyCallAdminAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src';

    public $css = [
        'admin-cms-telephony-call.css',
    ];

    public $depends = [
        BackendUiAsset::class,
    ];
}
