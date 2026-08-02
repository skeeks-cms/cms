<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;
use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\assets\LinkActvationAsset;

/**
 * Presentation and behavior shared by CMS comments and activity logs.
 */
class CmsActivityAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/cms-activity';

    public $css = [
        'cms-activity.css',
    ];

    public $js = [
        'cms-activity.js',
    ];

    public $depends = [
        BackendUiAsset::class,
        LinkActvationAsset::class,
    ];
}
