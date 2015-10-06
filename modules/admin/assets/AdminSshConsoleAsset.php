<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
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
        '\skeeks\cms\modules\admin\assets\AdminAsset',
        '\skeeks\widget\simpleajaxuploader\Asset',
    ];
}
