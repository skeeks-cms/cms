<?php

namespace skeeks\cms\telephony\widgets\assets;

use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\base\AssetBundle;

class TelephonyAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/telephony/widgets/assets/src';

    public $css = [
    ];

    public $js = [
        'telephony.js',
    ];

    public $depends = [
        'skeeks\sx\assets\Custom',
    ];
}
