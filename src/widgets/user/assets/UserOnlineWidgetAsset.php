<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\user\assets;

use skeeks\cms\base\AssetBundle;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UserOnlineWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/user/assets/src';

    public $css = [
    ];

    public $js = [
    ];

    public $depends = [
        'skeeks\sx\assets\Custom',
    ];
}
