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
// «Неразобранное» is shown by the stage bar under the header; a status badge there read as a button (п. 13б).
if ($model->status !== CmsLead::STATUS_NEW) {
    $statusContent .= CmsLeadStatusWidget::widget(['lead' => $model, 'showStage' => true, 'stageInline' => true]);
}
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
.sx-lead-header__menu {
    position: relative;
}
.sx-lead-header__menu > summary {
    list-style: none;
    cursor: pointer;
}
.sx-lead-header__menu > summary::-webkit-details-marker {
    display: none;
}
.sx-lead-header__menu-list {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    z-index: 30;
    display: flex;
    flex-direction: column;
    min-width: 220px;
    padding: 4px;
    border: 1px solid var(--sx-menu-border-color, var(--sx-color-border));
    border-radius: var(--sx-radius-control);
    background: var(--sx-menu-background, var(--sx-color-surface-raised));
    box-shadow: var(--sx-menu-shadow, 0 8px 24px rgba(29, 35, 48, 0.16));
}
.sx-lead-header__menu-list form {
    margin: 0;
}
.sx-lead-header__menu-item {
    display: block;
    width: 100%;
    padding: 7px 10px;
    border: 0;
    border-radius: var(--sx-radius-control);
    background: transparent;
    color: var(--sx-menu-color, var(--sx-color-text));
    font: inherit;
    text-align: left;
    white-space: nowrap;
    cursor: pointer;
}
.sx-lead-header__menu-item:hover,
.sx-lead-header__menu-item:focus {
    background: var(--sx-menu-hover-background, var(--sx-color-surface-hover));
    color: var(--sx-menu-active-color, var(--sx-color-accent));
    text-decoration: none;
}
CSS
);

// An action of this deal in the backend window: enableNoModelActions() keeps its header and tabs out of the window.
$windowLink = static function (string $label, array $route, string $variant = 'secondary', ?string $size = null, ?string $class = null): string {
    $url = (string)BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
    $action = ['isOpenNewWindow' => true, 'url' => $url];
    if ($size) {
        $action['size'] = $size;
    }

    return Html::a($label, $url, [
        'class' => $class ?? 'sx-button sx-button--'.$variant.' sx-button--sm',
        'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode($action).').go(); return false;',
    ]);
};
$access = \skeeks\cms\crm\CrmAccess::instance();
$userId = (int)Yii::$app->user->id;
// The AJAX forms of the header and the rows of «Найдены совпадения» (loaded later): lead-identity.js/.css.
\skeeks\cms\assets\admin\LeadIdentityAsset::register($this);

if ($model->status === CmsLead::STATUS_NEW && $access->canTriage($userId, $model)) {
    // Two buttons, as «Принять / Отклонить» in amoCRM (п. 13б): «Взять в работу» — one window with the client
    // (the found one already chosen), the responsible is me; «Не продажа» — spam, an open deal, support.
    $toolbar[] = $windowLink('<i class="fas fa-anchor" aria-hidden="true"></i> Взять в работу',
        ['/cms/admin-cms-lead/triage', 'pk' => $model->id, 'from' => 'card'], 'primary');
    $toolbar[] = Html::tag('details',
        Html::tag('summary', 'Не продажа <i class="fas fa-chevron-down" aria-hidden="true"></i>', ['class' => 'sx-button sx-button--secondary sx-button--sm'])
        .Html::tag('div',
            Html::beginForm(['/cms/admin-cms-lead/triage', 'pk' => $model->id], 'post', ['data-sx-lead-ajax-form' => true])
            .Html::submitButton('Спам', ['name' => 'op', 'value' => 'spam', 'class' => 'sx-lead-header__menu-item', 'role' => 'menuitem',
                'data-confirm' => 'Отметить заявку как спам и закрыть её?'])
            .Html::endForm()
            .$windowLink('Уже есть сделка', ['/cms/admin-cms-lead/triage', 'pk' => $model->id, 'mode' => 'merge'], 'secondary', null, 'sx-lead-header__menu-item')
            .$windowLink('Поддержка', ['/cms/admin-cms-lead/triage', 'pk' => $model->id, 'mode' => 'support'], 'secondary', null, 'sx-lead-header__menu-item'),
            ['class' => 'sx-lead-header__menu-list', 'role' => 'menu']),
        ['class' => 'sx-lead-header__menu']);
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
}

// The actions of the deal itself — first in «⋯», over a line (owner's decision 30.10.2026): «Редактировать»
// (the edit action, not a tab), «Клиент сделки», «Сменить ответственного»; the same windows and rights.
$windowItem = static fn(string $label, string $icon, array $route, array $window = []): array => [
    'label' => $label,
    'icon' => $icon,
    'url' => (string)BackendUrlHelper::createByParams($route)->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
    'window' => $window,
];
$recordItems = [];
// The hidden tab is not among modelActions: its own action decides (accessCallback canWorkWithModel()).
$editAction = $this->context instanceof \skeeks\cms\backend\BackendController ? $this->context->createAction('edit') : null;
if ($editAction && (!$editAction instanceof \skeeks\cms\IHasPermissions || $editAction->isAllow)) {
    $editUrl = Json::encode(\yii\helpers\Url::to(['/cms/admin-cms-lead/edit', 'pk' => $model->id]));
    $recordItems['edit'] = ['label' => 'Редактировать', 'icon' => 'edit', 'options' => ['onclick' => 'window.location.href = '.$editUrl.'; return false;']];
}
if ($canWork && $model->status === CmsLead::STATUS_IN_WORK && (!$model->cms_company_id || !$model->cms_user_id)) {
    $recordItems['identity'] = $windowItem('Клиент сделки', 'user', ['/cms/admin-cms-lead/identity', 'pk' => $model->id]);
}
if (!$model->isTerminal && $model->status !== CmsLead::STATUS_NEW && $access->canAssignLead($userId, $model)) {
    $recordItems['assign'] = $windowItem('Сменить ответственного', 'users', ['/cms/admin-cms-lead/assign', 'pk' => $model->id], ['size' => 'small']);
}

// «Позвонить», «Письмо» and «⋯» of the card block («Удалить» last in «⋯»). An unsorted request keeps the right
// side for «Взять в работу» and «Не продажа»: only «⋯», the call and the letter are inside it.
$toolbar[] = \skeeks\cms\widgets\admin\CrmCardActionsWidget::headerTools($model, $model->status !== CmsLead::STATUS_NEW, $this->context, $recordItems);

echo BackendModelHeader::widget([
    'model' => $model,
    'title' => $model->displayName,
    'status' => $status,
    'toolbar' => implode('', $toolbar),
    'actions' => false,
]);
