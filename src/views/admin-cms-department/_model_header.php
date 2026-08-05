<?php
/**
 * Compatibility header for external controllers still rendering this partial.
 *
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsDepartment $model
 */

use skeeks\cms\backend\widgets\BackendModelHeader;

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->name,
    'showBackLink' => false,
]);
