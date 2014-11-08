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

namespace skeeks\cms\modules\admin\widgets\tree;
use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package skeeks\cms\modules\admin
 */
class Asset extends AssetBundle
{

    public $sourcePath = '@skeeks/cms/modules/admin/widgets/tree';

    public $css = [
        'css/style.css',
    ];
    public $js = [
    ];
    public $depends = [
        'skeeks\cms\modules\admin\assets\AdminAsset',
    ];
}
