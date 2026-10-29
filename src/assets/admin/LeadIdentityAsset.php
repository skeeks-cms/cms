<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/**
 * «Кто это» in the deal form and sorting of «Неразобранное»: modes of the
 * company and the contact person, live duplicate hints, AJAX forms (П2а).
 */
class LeadIdentityAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['lead-identity.css'];
    public $js = ['lead-identity.js'];
    // The matches come by AJAX: the shield of a protected client (CrmProtection::shield()) is styled at once.
    public $depends = [BackendUiAsset::class, CrmShieldAsset::class];
}
