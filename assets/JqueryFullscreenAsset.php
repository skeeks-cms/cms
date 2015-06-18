<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
namespace skeeks\cms\assets;
use skeeks\cms\base\AssetBundle;

/**
 * Class JqueryFullscreenAsset
 * @package skeeks\cms\assets
 */
class JqueryFullscreenAsset extends AssetBundle
{
    public $sourcePath = '@bower/jq-fullscreen';

    public $js = [
        'release/jquery.fullscreen.min.js'
    ];

    public $css = [];

    public $depends = [
        '\skeeks\sx\assets\Core',
    ];
}
