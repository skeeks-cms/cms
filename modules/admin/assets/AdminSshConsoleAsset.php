<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (—ÍËÍ—)
 * @date 26.06.2015
 */
namespace skeeks\cms\modules\admin\assets;
use yii\web\AssetBundle;

/**
 * Class AdminSshConsoleAsset
 * @package skeeks\cms\modules\admin\assets
 */
class AdminSshConsoleAsset extends AdminAsset
{
    public $css = [
        'ssh-console/ssh-console.css',
        'ssh-console/themes/ubuntu.css',
    ];
    public $js =
    [
        'ssh-console/ssh-console.js',
    ];
    public $depends = [
        '\skeeks\sx\assets\Core',
        '\skeeks\sx\assets\Widget',
        '\skeeks\widget\simpleajaxuploader\Asset',
    ];
}
