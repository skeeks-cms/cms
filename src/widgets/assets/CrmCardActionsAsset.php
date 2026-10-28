<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;

/**
 * The block of actions of a CRM card, {@see \skeeks\cms\widgets\admin\CrmCardActionsWidget}.
 */
class CrmCardActionsAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/crm-card-actions';

    public $css = [
        'crm-card-actions.css',
    ];

    public $js = [
        'crm-card-actions.js',
    ];

    public $depends = [
        CmsActivityAsset::class,
    ];
}
