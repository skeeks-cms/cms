<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsCompany $model
 */

use skeeks\cms\backend\widgets\BackendModelHeader;

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->asText,
    'showBackLink' => false,
]);
