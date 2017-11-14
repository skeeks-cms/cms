<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.03.2015
 */

namespace skeeks\cms\assets;

use skeeks\cms\base\AssetBundle;

/**
 * Class JsTaskManagerAsset
 * @package skeeks\cms\assets
 */
class JsTaskManagerAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/src';

    public $css = [
    ];

    public $js = [
        'classes/tasks/Task.js',
        'classes/tasks/AjaxTask.js',
        'classes/tasks/ProgressBar.js',
        'classes/tasks/Manager.js',
    ];

    public $depends = [
        '\skeeks\sx\assets\Custom',
    ];
}
