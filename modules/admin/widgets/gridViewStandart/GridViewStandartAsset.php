<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.07.2015
 */
namespace skeeks\cms\modules\admin\widgets\gridViewStandart;
use yii\web\AssetBundle;

/**
 * Class GridViewStandartAsset
 * @package skeeks\cms\modules\admin\widgets\gridViewStandart
 */
class GridViewStandartAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/modules/admin/widgets/gridViewStandart';

    public $css = [
    ];
    public $js = [
        'js/gridViewStandart.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        '\skeeks\sx\assets\Custom',
    ];
}
