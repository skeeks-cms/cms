<?php
/**
 * ActionFilesAsset
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\assets;
use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package backend\assets
 */
class ActionFilesAsset extends AdminAsset
{
    public $css = [
    ];
    public $js =
    [
        'actions/files/files.js',
    ];
    public $depends = [
        '\skeeks\sx\assets\Core',
        '\skeeks\sx\assets\Widget',
        '\skeeks\widget\simpleajaxuploader\Asset',
    ];
}
