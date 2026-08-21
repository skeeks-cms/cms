<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

class LeadProcessAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $js = ['lead-process.js'];
    public $depends = [BackendUiAsset::class];
}
