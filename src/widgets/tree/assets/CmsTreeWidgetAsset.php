<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets\tree\assets;

use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package skeeks\cms\modules\admin
 */
class CmsTreeWidgetAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/tree/assets/src';

    public $css = [
        'css/style.css',
    ];
    public $js = [
    ];
    public $depends = [
        'skeeks\sx\assets\Core',
    ];
}
