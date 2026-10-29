<?php

namespace skeeks\cms\assets\admin;

use yii\web\AssetBundle;

/**
 * The shield of a protected client at its name (crm\CrmProtection::shield())
 * and the column «Ответственный и сотрудники» (crm\CrmClientGrid): registered
 * by the helpers themselves, only on pages that show them.
 */
class CrmShieldAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['crm-shield.css'];
}
