<?php

namespace skeeks\cms\telephony\widgets\assets;

use skeeks\cms\base\AssetBundle;
use skeeks\cms\backend\assets\BackendUiAsset;

class TelephonyAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/telephony/widgets/assets/src';

    public $css = [
        'telephony.css',
    ];

    public $js = [
        'telephony.js',
    ];

    public $depends = [
        BackendUiAsset::class,
        'skeeks\sx\assets\Custom',
    ];
}
