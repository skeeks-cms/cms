<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

class LeadMatchesAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $js = ['lead-matches.js'];
    public $depends = [BackendUiAsset::class];
}
