<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset;
use skeeks\cms\backend\widgets\sortable\assets\BackendSortableAdapterAsset;
use skeeks\cms\base\AssetBundle;

/** Task views of an employee: the calendar (month, week, day) and the «Сроки» board. */
class TaskCalendarAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['task-calendar.css'];
    public $js = ['task-calendar.js'];
    public $depends = [
        BackendUiAsset::class,
        BackendSortableAdapterAsset::class,
        ControllerActionsWidgetAsset::class,
        'yii\web\YiiAsset',
    ];
}
