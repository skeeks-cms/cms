<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */

namespace skeeks\cms\assets;

use skeeks\cms\base\AssetBundle;

/**
 *
 * example:
\skeeks\cms\assets\LinkActvationAsset::register($this);
$this->registerJs(<<<JS
new sx.classes.LinkActivation('.sx-task-description');
JS
);
 *
 *
 * Class CmsAsset
 * @package skeeks\cms\assets
 */
class LinkActvationAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src/link-activation';

    public $css = [
    ];

    public $js = [
        'link-activation.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Custom',
    ];
}
