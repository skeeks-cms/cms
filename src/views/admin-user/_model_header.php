<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\widgets\admin\CmsContactModelHeader;

echo CmsContactModelHeader::widget([
    'model'         => $model,
    'title'         => $model->shortDisplayNameWithAlias,
    // The shield of a protected client (CrmProtection::shield()), nothing for a free one or an employee.
    'titleSuffix'   => $model->is_worker ? '' : \skeeks\cms\crm\CrmProtection::shieldFor($model),
    'favoriteType'  => $model->is_worker ? null : 'clients',
    'favoriteRoute' => $model->is_worker ? null : '/cms/admin-user/view',
    'favoriteName'  => $model->shortDisplayName,
]);
