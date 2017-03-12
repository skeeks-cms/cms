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
class CmsToolbarAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src/toolbar';

    public $css = [
        'toolbar.css',
    ];

    public $js =
    [
        'classes/window.js',
        'classes/dialog.js',
        'classes/edit-view-block.js',
        'toolbar.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Core',
    ];
}
