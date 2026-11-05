<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/**
 * «Откуда узнали» in one click: the items as chips (widgets\admin\LeadReferralInputWidget).
 */
class LeadReferralAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['lead-referral.css'];
    public $js = ['lead-referral.js'];
    public $depends = [BackendUiAsset::class];
}
