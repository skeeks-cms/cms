<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\widgets\admin\CmsContactModelHeader;

echo CmsContactModelHeader::widget([
    'model'             => $model,
    'title'             => $model->shortDisplayNameWithAlias,
    'showOnline'        => true,
    'canChangePassword' => Yii::$app->user->can('cms/admin-user/manage', ['model' => $model]),
]);
