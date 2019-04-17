<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\assets;

use skeeks\cms\base\AssetBundle;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ActiveFormAjaxSubmitAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src';

    public $css = [
    ];

    public $js = [
        'classes/active-form/AjaxSubmit.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Custom',
    ];
}
