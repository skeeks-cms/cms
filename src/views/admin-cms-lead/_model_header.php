<?php

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\BackendModelHeader;
use skeeks\cms\models\CmsLead;
use skeeks\cms\widgets\admin\CmsLeadStatusWidget;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var CmsLead $model */

$toolbar = [];
$canWork = $model->canBeWorkedBy((int)Yii::$app->user->id);
$statusContent = '';
if ($model->executor) {
    $statusContent .= Html::tag('div',
        Html::tag('span', 'Ответственный', ['class' => 'sx-collection-cell__secondary'])
        .CmsWorkerViewWidget::widget(['user' => $model->executor, 'isSmall' => true]),
        ['class' => 'sx-lead-header__responsible']
    );
}
$statusContent .= CmsLeadStatusWidget::widget(['lead' => $model]);
$status = Html::tag('div', $statusContent, ['class' => 'sx-lead-header__status-row']);

$this->registerCss(<<<CSS
.sx-lead-header__status-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
}
.sx-lead-header__responsible {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}
.sx-lead-header__responsible > .sx-collection-cell__secondary {
    flex: 0 0 auto;
}
@media (max-width: 900px) {
    .sx-lead-header__status-row {
        justify-content: flex-start;
    }
}
CSS
);

if ($model->canBeClaimed) {
    $toolbar[] = Html::beginForm(['/cms/admin-cms-lead/claim', 'pk' => $model->id], 'post', [
        'class' => 'sx-model-header__toolbar-form',
    ])
        .Html::submitButton('<i class="fas fa-anchor" aria-hidden="true"></i> Взять в работу', [
            'class' => 'sx-button sx-button--primary sx-button--sm',
            'data-confirm' => 'Закрепить лид за вами?',
        ])
        .Html::endForm();
} elseif ($model->canBeReopenedBy((int)Yii::$app->user->id)) {
    $toolbar[] = Html::beginForm(['/cms/admin-cms-lead/reopen', 'pk' => $model->id], 'post', [
        'class' => 'sx-model-header__toolbar-form',
    ])
        .Html::submitButton('<i class="fas fa-undo" aria-hidden="true"></i> Вернуть в работу', [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-confirm' => 'Вернуть лид в работу?',
        ])
        .Html::endForm();
} elseif ($canWork && $model->status === CmsLead::STATUS_IN_WORK) {
    $currentPanelLink = static function (string $label, string $url): string {
        $action = Json::encode(['isOpenNewWindow' => true, 'url' => $url]);

        return Html::a($label, $url, [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'onclick' => 'new sx.classes.backend.widgets.Action('.$action.').go(); return false;',
        ]);
    };

    if (!$model->cms_company_id) {
        $companyUrl = (string)BackendUrlHelper::createByParams([
            '/cms/admin-cms-lead-company/create',
            'lead_id' => $model->id,
            'CmsCompany' => ['name' => $model->name, 'description' => $model->description],
        ])->enableEmptyLayout()->enableNoActions()->url;
        $toolbar[] = $currentPanelLink('Создать компанию', $companyUrl);
    }

    if (!$model->cms_user_id) {
        $clientUrl = (string)BackendUrlHelper::createByParams([
            '/cms/admin-cms-lead-client/create',
            'lead_id' => $model->id,
            'CmsUser' => ['first_name' => $model->name, 'email' => $model->mainEmail ? $model->mainEmail->value : null],
        ])->enableEmptyLayout()->enableNoActions()->url;
        $toolbar[] = $currentPanelLink('Создать клиента', $clientUrl);
    }
}

echo BackendModelHeader::widget([
    'model' => $model,
    'title' => $model->displayName,
    'status' => $status,
    'toolbar' => implode('', $toolbar),
    'actions' => false,
]);
