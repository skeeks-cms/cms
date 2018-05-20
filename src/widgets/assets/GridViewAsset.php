<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class GridViewAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/grid-view';

    public $css = [
        'grid.css',
        'table.css',
    ];

    public $js = [];

    public $depends = [
        'yii\web\YiiAsset',
        'skeeks\sx\assets\Custom',
    ];
}