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
 * Class CmsAsset
 * @package skeeks\cms\assets
 */
class AdultAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src/adult';

    public $css = [
        'adult.css',
    ];

    public $js = [
        'adult.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Custom',
    ];
}
