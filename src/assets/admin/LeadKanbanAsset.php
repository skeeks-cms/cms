<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset;
use skeeks\cms\backend\widgets\sortable\assets\BackendSortableAdapterAsset;
use skeeks\cms\base\AssetBundle;

/** Lead kanban board: column layout and drag-and-drop between columns. */
class LeadKanbanAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['lead-kanban.css'];
    public $js = ['lead-kanban.js'];
    public $depends = [
        BackendUiAsset::class,
        BackendSortableAdapterAsset::class,
        ControllerActionsWidgetAsset::class,
        'yii\web\YiiAsset',
    ];
}
