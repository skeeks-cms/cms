<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.07.2015
 */
namespace skeeks\cms\modules\admin\assets;
use skeeks\cms\assets\FancyboxAssets;
use skeeks\cms\base\AssetBundle;
use yii\helpers\Json;

/**
 * Class AdminUnauthorizedAsset
 * @package skeeks\cms\modules\admin\assets
 */
class AdminUnauthorizedAsset extends AdminAsset
{
    public $css = [
        'css/unauthorized.css',
    ];

    public $js = [
        'js/Unauthorized.js',
    ];
    public $depends = [
        '\skeeks\cms\modules\admin\assets\AdminAsset',
        '\skeeks\cms\modules\admin\assets\AdminCanvasBg',
    ];
}

