<?php

namespace skeeks\cms\assets\admin;

use skeeks\cms\backend\assets\BackendUiAsset;
use skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset;
use skeeks\cms\base\AssetBundle;

/** Stage bar of the lead card. */
class LeadStageBarAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['lead-stage-bar.css'];
    public $js = ['lead-stage-bar.js'];
    public $depends = [
        BackendUiAsset::class,
        ControllerActionsWidgetAsset::class,
        'yii\web\YiiAsset',
    ];
}
