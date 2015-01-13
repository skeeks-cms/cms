<?php
/**
 * HiddenCaptchaAssets
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 13.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\assets;
use skeeks\cms\base\AssetBundle;

/**
 * Class AppAsset
 * @package backend\assets
 */
class HiddenCaptchaAssets extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets';

    public $css = [];

    public $js =
    [
        'plugins/HiddenCaptcha.min.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Custom',
    ];
}
