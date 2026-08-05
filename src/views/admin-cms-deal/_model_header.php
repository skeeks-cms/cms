<?php
/**
 * Compatibility header for external controllers still rendering this partial.
 *
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsDeal $model
 */

use skeeks\cms\backend\widgets\BackendModelHeader;

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->asText,
    'showBackLink' => false,
]);
