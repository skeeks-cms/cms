<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */

namespace skeeks\cms\assets;

use skeeks\cms\base\AssetBundle;
use yii\web\YiiAsset;

/**
 * Class AppAsset
 * @package backend\assets
 */
class FancyboxAssets extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src/fancybox';

    public $js = [
        'jquery.fancybox.min.js',
    ];

    public $css = [
        'jquery.fancybox.min.css',
    ];
    
    /*public $depends = [
        YiiAsset::class  
    ];*/
}
