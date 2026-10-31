<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset;
use skeeks\cms\base\AssetBundle;

/** Gantt of an employee's tasks: rows, a time scale, bars to drag and stretch. */
class TaskGanttAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['task-gantt.css'];
    public $js = ['task-gantt.js'];
    public $depends = [
        BackendUiAsset::class,
        ControllerActionsWidgetAsset::class,
        'yii\web\YiiAsset',
    ];
}
