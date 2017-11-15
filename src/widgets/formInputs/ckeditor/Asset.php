<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\widgets\formInputs\ckeditor;

use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package backend\assets
 */
class Asset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/ckeditor/assets';
    public $css = [];
    public $js = [
        'imageselect.png'
    ];
    public $depends = [];
}
