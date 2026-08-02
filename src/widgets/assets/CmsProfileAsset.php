<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;
use skeeks\cms\backend\assets\BackendUiAsset;

/**
 * Shared presentation and password controls for personal/profile pages.
 */
class CmsProfileAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/profile';

    public $css = [
        'profile.css',
    ];

    public $js = [
        'profile.js',
    ];

    public $depends = [
        BackendUiAsset::class,
    ];
}
