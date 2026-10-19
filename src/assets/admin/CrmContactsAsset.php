<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\base\AssetBundle;

/** Shared look of the contact blocks in the company and lead cards. */
class CrmContactsAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['crm-contacts.css'];
    public $depends = [BackendUiAsset::class];
}
