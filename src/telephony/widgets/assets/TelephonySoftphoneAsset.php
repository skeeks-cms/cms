<?php

namespace skeeks\cms\telephony\widgets\assets;

use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\base\AssetBundle;

class TelephonySoftphoneAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/telephony/widgets/assets/src';

    public $css = [
    ];

    public $js = [
        'https://jssip.net/download/releases/jssip-3.10.0.min.js',
        'softphone.js',
    ];

    public $depends = [
        'skeeks\sx\assets\Custom',
    ];
}
