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
 * Class AppAsset
 * @package backend\assets
 */
class FancyboxAssets extends AssetBundle
{
    public $sourcePath = '@bower/fancybox/dist';

    public $js = [
        'jquery.fancybox.js',
    ];

    public $css = [
        'jquery.fancybox.css',
    ];
}
