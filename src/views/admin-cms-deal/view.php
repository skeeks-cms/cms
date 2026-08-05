<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsDeal */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */

use skeeks\cms\backend\assets\BackendPanelAsset;
use skeeks\cms\backend\widgets\BackendEntityLink;
use yii\helpers\Html;

$controller = $this->context;
$action = $controller->action;
$model = $action->model;

BackendPanelAsset::register($this);

$formatValue = static function ($value, $empty = 'Не указано') {
    $value = trim((string)$value);

    return $value === ''
        ? '<span class="sx-deal-muted">'.Html::encode($empty).'</span>'
        : Html::encode($value);
};

$entityCard = static function ($controllerId, $entity, $title, $displayName, $subtitle = '', $icon = 'fa fa-link') {
    if (!$entity) {
        return '';
    }

    $content = '<div class="sx-surface sx-deal-entity">'
        . '<div class="sx-deal-entity-icon"><i class="'.$icon.'"></i></div>'
        . '<div class="sx-deal-entity-body">'
        . '<div class="sx-deal-entity-label">'.Html::encode($title).'</div>'
        . '<div class="sx-deal-entity-title">'.Html::encode($displayName).'</div>';

    if ($subtitle) {
        $content .= '<div class="sx-deal-entity-subtitle">'.Html::encode($subtitle).'</div>';
    }

    $content .= '</div></div>';

    return BackendEntityLink::widget([
        'controllerId' => $controllerId,
        'modelId'      => $entity->id,
        'content'      => $content,
        'options'      => [
            'class' => 'sx-deal-entity-link',
        ],
    ]);
};

$isExpired = $model->is_active && $model->end_at && time() > $model->end_at;
if (!$model->is_active) {
    $statusTitle = 'Не активна';
    $statusClass = 'is-inactive';
} elseif ($isExpired) {
    $statusTitle = 'Просрочена';
    $statusClass = 'is-expired';
} else {
    $statusTitle = 'Активна';
    $statusClass = 'is-active';
}

$periodParts = [];
if ($model->start_at) {
    $periodParts[] = Yii::$app->formatter->asDate($model->start_at);
}
if ($model->end_at) {
    $periodParts[] = Yii::$app->formatter->asDate($model->end_at);
}
$datePeriod = implode(' — ', $periodParts);

$this->registerCss(<<<CSS
.sx-deal-card {
    overflow: hidden;
}
.sx-deal-section {
    padding: var(--sx-surface-padding);
    border-bottom: 1px solid var(--sx-color-border);
}
.sx-deal-section:last-child {
    border-bottom: 0;
}
.sx-deal-section-title {
    margin: 0 0 14px;
    font-size: 18px;
    font-weight: 600;
}
.sx-deal-overview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.sx-deal-overview-item {
    min-height: 82px;
    padding: 14px;
}
.sx-deal-overview-label,
.sx-deal-entity-label {
    color: var(--sx-color-text-subtle);
    font-size: 12px;
    margin-bottom: 4px;
}
.sx-deal-overview-value {
    color: var(--sx-color-text);
    font-weight: 600;
    overflow-wrap: anywhere;
}
.sx-deal-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 16px;
}
.sx-deal-status:before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sx-color-text-subtle);
}
.sx-deal-status.is-active {
    color: var(--sx-color-success);
}
.sx-deal-status.is-active:before {
    background: var(--sx-color-success);
}
.sx-deal-status.is-expired {
    color: var(--sx-color-danger);
}
.sx-deal-status.is-expired:before {
    background: var(--sx-color-danger);
}
.sx-deal-status.is-inactive {
    color: var(--sx-color-text-muted);
}
.sx-deal-entities {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.sx-deal-entity,
.sx-deal-entity-link {
    box-sizing: border-box;
    display: block;
    color: inherit;
    text-decoration: none;
}
.sx-deal-entity-link {
    height: 100%;
    cursor: pointer;
}
.sx-deal-entity {
    min-height: 84px;
    height: 100%;
    padding: 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.sx-deal-entity-link:hover,
.sx-deal-entity-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-deal-entity-link:hover .sx-deal-entity,
.sx-deal-entity-link:focus .sx-deal-entity {
    border-color: var(--sx-color-accent-border);
    box-shadow: var(--sx-button-focus-shadow);
}
.sx-deal-entity-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--sx-color-surface-muted);
    color: var(--sx-color-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-size: 15px;
}
.sx-deal-entity-title {
    font-weight: 600;
    overflow-wrap: anywhere;
}
.sx-deal-entity-subtitle {
    color: var(--sx-color-text-muted);
    font-size: 13px;
    margin-top: 4px;
}
.sx-deal-description {
    margin: 0;
    color: var(--sx-color-text-muted);
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.sx-deal-muted {
    color: var(--sx-color-text-subtle);
}
@media (max-width: 1100px) {
    .sx-deal-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 700px) {
    .sx-deal-overview,
    .sx-deal-entities {
        grid-template-columns: 1fr;
    }
}
CSS
);
?>

<div class="sx-panel sx-deal-card">
    <section class="sx-deal-section">
        <div class="sx-deal-overview">
            <div class="sx-surface sx-deal-overview-item">
                <div class="sx-deal-overview-label">Статус</div>
                <div class="sx-deal-overview-value sx-deal-status <?= $statusClass; ?>">
                    <?= Html::encode($statusTitle); ?>
                </div>
            </div>
            <div class="sx-surface sx-deal-overview-item">
                <div class="sx-deal-overview-label">Тип сделки</div>
                <div class="sx-deal-overview-value"><?= $formatValue($model->dealType ? $model->dealType->name : ''); ?></div>
            </div>
            <div class="sx-surface sx-deal-overview-item">
                <div class="sx-deal-overview-label">Сумма</div>
                <div class="sx-deal-overview-value"><?= $formatValue($model->moneyAsText, 'Без суммы'); ?></div>
            </div>
            <div class="sx-surface sx-deal-overview-item">
                <div class="sx-deal-overview-label">Период действия</div>
                <div class="sx-deal-overview-value"><?= $formatValue($datePeriod, 'Без ограничения'); ?></div>
            </div>
        </div>
    </section>

    <?php if ($model->company || $model->user) : ?>
        <section class="sx-deal-section">
            <h3 class="sx-deal-section-title">Участники</h3>
            <div class="sx-deal-entities">
                <?php if ($model->company) : ?>
                    <?= $entityCard('/cms/admin-cms-company', $model->company, 'Компания', $model->company->name, '', 'fa fa-building'); ?>
                <?php endif; ?>
                <?php if ($model->user) : ?>
                    <?= $entityCard('/cms/admin-user', $model->user, 'Контакт', $model->user->shortDisplayName, $model->user->email ?: $model->user->phone, 'fa fa-user'); ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (trim((string)$model->description) !== '') : ?>
        <section class="sx-deal-section">
            <h3 class="sx-deal-section-title">Описание</h3>
            <p class="sx-deal-description"><?= Html::encode($model->description); ?></p>
        </section>
    <?php endif; ?>
</div>
