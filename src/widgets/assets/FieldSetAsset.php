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
class FieldSetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/field-set';

    public $css = [
        'field-set.css',
    ];

    public $js = [
        'url.min.js',
        'field-set.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'skeeks\sx\assets\Custom',
    ];
}