<?php
/**
 * AdminAsset
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets\controllerActions;
use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package skeeks\cms\modules\admin
 */
class Asset extends AssetBundle
{

    public $sourcePath = '@skeeks/cms/modules/admin/widgets/controllerActions';

    public $css = [
    ];
    public $js = [
        'js/widget.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        '\skeeks\sx\assets\Custom',
    ];
}
