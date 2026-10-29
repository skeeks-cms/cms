<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\widgets\admin\CmsContactModelHeader;

$isClient = !$model->is_worker;
echo CmsContactModelHeader::widget(array_merge([
    'model'         => $model,
    'title'         => $model->shortDisplayNameWithAlias,
    // The shield of a protected client (CrmProtection::shield()), nothing for a free one or an employee.
    'titleSuffix'   => $model->is_worker ? '' : \skeeks\cms\crm\CrmProtection::shieldFor($model),
    'favoriteType'  => $model->is_worker ? null : 'clients',
    'favoriteRoute' => $model->is_worker ? null : '/cms/admin-user/view',
    'favoriteName'  => $model->shortDisplayName,
], $isClient ? [
    // A client: «Позвонить», «Письмо» and «⋯» of the card block instead of tel:, SMS and mailto:;
    // «Удалить» is the last item of «⋯».
    'showContactActions' => false,
    'toolbar'            => \skeeks\cms\widgets\admin\CrmCardActionsWidget::headerTools($model, true, $this->context),
    'actions'            => false,
] : []));
