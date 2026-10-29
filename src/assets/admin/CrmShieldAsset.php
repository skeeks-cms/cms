<?php

namespace skeeks\cms\assets\admin;

use yii\web\AssetBundle;

/**
 * The shield of a protected client at its name (crm\CrmProtection::shield()):
 * registered by the helper itself, only on pages that show one.
 */
class CrmShieldAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['crm-shield.css'];
}
