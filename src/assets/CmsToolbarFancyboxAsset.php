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
 * Class CmsToolbarAssets
 * @package skeeks\cms\assets
 */
class CmsToolbarFancyboxAsset extends CmsToolbarAsset
{
    public $css = [];

    public $js =
    [
        'toolbar/classes/window-fancybox.js',
    ];

    public $depends = [
        '\skeeks\cms\assets\FancyboxAssets',
        '\skeeks\cms\assets\CmsToolbarAsset',
    ];
}
