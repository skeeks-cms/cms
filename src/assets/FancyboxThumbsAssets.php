<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */

namespace skeeks\cms\assets;

/**
 * Class AppAsset
 * @package backend\assets
 */
class FancyboxThumbsAssets extends FancyboxAssets
{
    public $js = [
        'helpers/jquery.fancybox-thumbs.js',
    ];

    public $css = [
        'helpers/jquery.fancybox-thumbs.css',
    ];

    public $depends = [
        '\skeeks\cms\assets\FancyboxAssets',
    ];
}
