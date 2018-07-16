<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */

namespace skeeks\cms\widgets\assets;

use skeeks\cms\base\AssetBundle;

/**
 * Class DualSelectAsset
 * @package skeeks\cms\assets
 */
class DualSelectAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/dual-select';

    public $css = [
        'dual-select.css'
    ];

    public $js = [
        'dual-select.js',
    ];

    public $depends = [
        'skeeks\sx\assets\Custom',
    ];
}
