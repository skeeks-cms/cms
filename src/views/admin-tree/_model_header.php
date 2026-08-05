<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\Tree $model
 */

use skeeks\cms\widgets\admin\CmsPublicModelHeader;

echo CmsPublicModelHeader::widget([
    'model'        => $model,
    'publicUrl'    => $model->url,
    'parentLabel'  => $model->pid ? $model->fullName : null,
    'showBackLink' => false,
]);
