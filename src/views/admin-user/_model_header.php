<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\widgets\admin\CmsContactModelHeader;

echo CmsContactModelHeader::widget([
    'model'         => $model,
    'title'         => $model->shortDisplayNameWithAlias,
    'favoriteType'  => $model->is_worker ? null : 'clients',
    'favoriteRoute' => $model->is_worker ? null : '/cms/admin-user/view',
    'favoriteName'  => $model->shortDisplayName,
]);
