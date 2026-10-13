<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/** Lead report page: filters, bars and tables on the backend semantic tokens. */
class LeadReportAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['lead-report.css'];
    public $depends = [BackendUiAsset::class];
}
