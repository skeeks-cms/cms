<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\admin\assets\JqueryMaskInputAsset;
use skeeks\cms\base\AssetBundle;

/**
 * Optional phone-mask behavior for editable profile pages.
 */
class CmsProfilePhoneAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/profile';

    public $js = [
        'profile-phone.js',
    ];

    public $depends = [
        CmsProfileAsset::class,
        JqueryMaskInputAsset::class,
    ];
}
