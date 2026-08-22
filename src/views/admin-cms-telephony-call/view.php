<?php

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsTelephonyCall;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var CmsTelephonyCall $model */

$empty = Html::tag('span', 'Не указано', ['class' => 'sx-collection-cell__secondary']);
$formatTime = static function ($timestamp) use ($empty) {
    return $timestamp ? Yii::$app->formatter->asDatetime($timestamp) : $empty;
};
$entityLink = static function ($entity, string $controllerId) use ($empty) {
    if (!$entity) {
        return $empty;
    }

    return BackendEntityLink::widget([
        'controllerId' => $controllerId,
        'modelId' => $entity->id,
        'label' => $entity->asText,
    ]);
};

$directionText = $model->isIncoming() ? 'Входящий' : 'Исходящий';
$directionClass = $model->isIncoming() ? 'sx-status--info' : 'sx-status--success';
$statusClass = 'sx-status--warning';
if ($model->status === CmsTelephonyCall::STATUS_ANSWERED) {
    $statusClass = 'sx-status--success';
} elseif ($model->status === CmsTelephonyCall::STATUS_FAILED) {
    $statusClass = 'sx-status--danger';
}

$worker = $model->workerUser
    ? $entityLink($model->workerUser, '/cms/admin-user')
    : ($model->provider_user_num ? Html::encode($model->provider_user_num) : $empty);
$provider = $model->provider ? Html::encode($model->provider->name) : $empty;

$audioSrc = null;
if ($model->cms_record_file_id && $model->cmsRecordFile) {
    $audioSrc = $model->cmsRecordFile->src;
} elseif ($model->record_url) {
    $audioSrc = $model->record_url;
}

?>

<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Данные звонка',
        'raised' => true,
        'responsive' => true,
        'options' => ['class' => 'sx-surface--compact'],
    ]); ?>
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'sx-detail-view'],
            'attributes' => [
                [
                    'label' => 'Направление',
                    'format' => 'raw',
                    'value' => Html::tag('span', $directionText, [
                        'class' => 'sx-status '.$directionClass,
                    ]),
                ],
                [
                    'label' => 'Номер клиента',
                    'format' => 'raw',
                    'value' => Html::encode($model->client_phone),
                ],
                [
                    'label' => 'Сотрудник',
                    'format' => 'raw',
                    'value' => $worker,
                ],
                [
                    'label' => 'Статус',
                    'format' => 'raw',
                    'value' => Html::tag('span', Html::encode($model->statusAsText), [
                        'class' => 'sx-status '.$statusClass,
                    ]),
                ],
                [
                    'label' => 'Начало',
                    'format' => 'raw',
                    'value' => $formatTime($model->started_at),
                ],
                [
                    'label' => 'Окончание',
                    'format' => 'raw',
                    'value' => $formatTime($model->ended_at),
                ],
                [
                    'label' => 'Продолжительность',
                    'value' => Yii::$app->formatter->asDuration($model->getDuration()),
                ],
                [
                    'label' => 'Провайдер',
                    'format' => 'raw',
                    'value' => $provider,
                ],
                [
                    'label' => 'ID звонка у провайдера',
                    'format' => 'raw',
                    'value' => $model->provider_call_id ? Html::encode($model->provider_call_id) : $empty,
                ],
            ],
        ]); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Привязанные сущности',
        'hint' => 'Звонок остаётся одной записью; в карточках сущностей отображаются ссылки на него.',
        'raised' => true,
        'responsive' => true,
        'options' => ['class' => 'sx-surface--compact'],
    ]); ?>
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'sx-detail-view'],
            'attributes' => [
                [
                    'label' => 'Лид',
                    'format' => 'raw',
                    'value' => $entityLink($model->lead, '/cms/admin-cms-lead'),
                ],
                [
                    'label' => 'Компания',
                    'format' => 'raw',
                    'value' => $entityLink($model->company, '/cms/admin-cms-company'),
                ],
                [
                    'label' => 'Клиент',
                    'format' => 'raw',
                    'value' => $entityLink($model->user, '/cms/admin-user'),
                ],
            ],
        ]); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Запись разговора',
        'raised' => true,
        'responsive' => true,
        'options' => ['class' => 'sx-surface--compact'],
    ]); ?>
        <?php if ($audioSrc) : ?>
            <?= Html::tag('audio', '', [
                'class' => 'sx-call-audio',
                'controls' => true,
                'preload' => 'metadata',
                'src' => $audioSrc,
                'aria-label' => 'Запись разговора',
            ]); ?>
        <?php else : ?>
            <?= Html::tag('span', 'Запись разговора пока недоступна.', [
                'class' => 'sx-collection-cell__secondary',
            ]); ?>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>
</div>
