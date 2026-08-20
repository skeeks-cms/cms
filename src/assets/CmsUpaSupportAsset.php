<?php

namespace skeeks\cms\assets;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/**
 * Presentation used only by client portal support task screens.
 */
class CmsUpaSupportAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src';

    public $css = [
        'upa-support.css',
    ];

    public $depends = [
        BackendUiAsset::class,
    ];
}
