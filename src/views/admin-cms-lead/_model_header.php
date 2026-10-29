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
$statusContent .= CmsLeadStatusWidget::widget(['lead' => $model, 'showStage' => true, 'stageInline' => true]);
// One amount at a time: «Сумма» for an open or rejected deal; a successful one shows «Сумма сделки» in its status.
if ($model->status !== CmsLead::STATUS_SUCCESS && $model->amountAsText !== '') {
    $statusContent .= Html::tag('span', 'Сумма '.Html::encode($model->amountAsText), ['class' => 'sx-status', 'title' => 'Ожидаемая сумма сделки']);
} elseif ($model->expectedAmountNote !== '') {
    $statusContent .= Html::tag('span', Html::encode($model->expectedAmountNote), ['class' => 'sx-collection-cell__secondary']);
}
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

// An action of this deal in the backend window: enableNoModelActions() keeps its header and tabs out of the window.
$windowLink = static function (string $label, array $route, string $variant = 'secondary', ?string $size = null): string {
    $url = (string)BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
    $action = ['isOpenNewWindow' => true, 'url' => $url];
    if ($size) {
        $action['size'] = $size;
    }

    return Html::a($label, $url, [
        'class' => 'sx-button sx-button--'.$variant.' sx-button--sm',
        'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode($action).').go(); return false;',
    ]);
};
$access = \skeeks\cms\crm\CrmAccess::instance();
$userId = (int)Yii::$app->user->id;

if ($model->status === CmsLead::STATUS_NEW && $access->canTriage($userId, $model)) {
    // As before П2а, one click takes the deal (a simple site needs nothing more);
    // «Разобрать»: who it is, add to an open deal, support or spam.
    if ($model->canBeClaimed) {
        $toolbar[] = Html::beginForm(['/cms/admin-cms-lead/claim', 'pk' => $model->id], 'post', [
            'class' => 'sx-model-header__toolbar-form',
        ])
            .Html::submitButton('<i class="fas fa-anchor" aria-hidden="true"></i> Взять в работу', [
                'class' => 'sx-button sx-button--primary sx-button--sm',
                'data-confirm' => 'Закрепить сделку за вами?',
            ])
            .Html::endForm();
    }
    $toolbar[] = $windowLink('<i class="fas fa-inbox" aria-hidden="true"></i> Разобрать', ['/cms/admin-cms-lead/triage', 'pk' => $model->id], $model->canBeClaimed ? 'secondary' : 'primary');
} elseif ($model->canBeReopenedBy((int)Yii::$app->user->id)) {
    $toolbar[] = Html::beginForm(['/cms/admin-cms-lead/reopen', 'pk' => $model->id], 'post', [
        'class' => 'sx-model-header__toolbar-form',
    ])
        .Html::submitButton('<i class="fas fa-undo" aria-hidden="true"></i> Вернуть в работу', [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-confirm' => 'Вернуть сделку в работу?',
        ])
        .Html::endForm();
    if ($model->status === CmsLead::STATUS_SUCCESS) {
        // Correcting the amount keeps the status and never re-runs the partner reward.
        $amountUrl = (string)BackendUrlHelper::createByParams([
            '/cms/admin-cms-lead/deal-amount',
            'pk' => $model->id,
        ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
        $toolbar[] = Html::a('<i class="fas fa-ruble-sign" aria-hidden="true"></i> Сумма сделки', $amountUrl, [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $amountUrl, 'size' => 'small']).').go(); return false;',
        ]);
    }
} elseif ($canWork && $model->status === CmsLead::STATUS_IN_WORK) {
    if (!$model->cms_company_id || !$model->cms_user_id) {
        $toolbar[] = $windowLink('<i class="fas fa-user-plus" aria-hidden="true"></i> Клиент сделки', ['/cms/admin-cms-lead/identity', 'pk' => $model->id]);
    }
}
if (!$model->isTerminal && $model->status !== CmsLead::STATUS_NEW && $access->canAssignLead($userId, $model)) {
    $toolbar[] = $windowLink('<i class="fas fa-share" aria-hidden="true"></i> Сменить ответственного', ['/cms/admin-cms-lead/assign', 'pk' => $model->id], 'secondary', 'small');
}

echo BackendModelHeader::widget([
    'model' => $model,
    'title' => $model->displayName,
    'status' => $status,
    'toolbar' => implode('', $toolbar),
    'actions' => false,
]);
