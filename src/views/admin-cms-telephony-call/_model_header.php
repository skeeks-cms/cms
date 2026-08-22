<?php

use skeeks\cms\backend\widgets\BackendModelHeader;
use skeeks\cms\models\CmsTelephonyCall;
use yii\helpers\Html;

/** @var CmsTelephonyCall $model */

$direction = $model->isIncoming() ? 'Входящий звонок' : 'Исходящий звонок';
$statusClass = 'sx-status--warning';
if ($model->status === CmsTelephonyCall::STATUS_ANSWERED) {
    $statusClass = 'sx-status--success';
} elseif ($model->status === CmsTelephonyCall::STATUS_FAILED) {
    $statusClass = 'sx-status--danger';
}

$metaItems = [];
if ($model->provider) {
    $metaItems[] = Html::tag('span', Html::encode($model->provider->name), [
        'title' => 'Провайдер телефонии',
        'data-toggle' => 'tooltip',
    ]);
}
if ($model->provider_call_id) {
    $metaItems[] = Html::tag('span', 'ID '.Html::encode($model->provider_call_id), [
        'title' => 'ID звонка у провайдера',
        'data-toggle' => 'tooltip',
    ]);
}

echo BackendModelHeader::widget([
    'model' => $model,
    'title' => $direction.' · '.$model->client_phone,
    'metaItems' => $metaItems,
    'status' => Html::tag('span', Html::encode($model->statusAsText), [
        'class' => 'sx-status '.$statusClass,
    ]),
    'actions' => false,
    'showBackLink' => false,
]);
