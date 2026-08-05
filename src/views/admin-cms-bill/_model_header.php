<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\shop\models\ShopBill $model
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendModelHeader;
use yii\helpers\Html;

$statusClass = 'sx-status--warning';
$statusText = 'Ожидает оплаты';
if ($model->closed_at) {
    $statusClass = 'sx-status--danger';
    $statusText = 'Отменён';
} elseif ($model->paid_at) {
    $statusClass = 'sx-status--success';
    $statusText = 'Оплачен';
}

$status = Html::tag('div', Html::encode($statusText), [
    'class' => 'sx-status '.$statusClass,
]);
if ($model->paid_at) {
    $status .= Html::tag('div',
        BackendIcon::render('clock', ['size' => 13]).' Оплачен '.Yii::$app->formatter->asDate($model->paid_at),
        ['class' => 'sx-model-header__status-note sx-model-header__status-note--success']
    );
} elseif (!$model->closed_at && $model->due_at) {
    $status .= Html::tag('div',
        BackendIcon::render('clock', ['size' => 13]).' Оплатить до '.Yii::$app->formatter->asDate($model->due_at),
        ['class' => 'sx-model-header__status-note']
    );
}

$metaItems = [];
if ($model->billPaySystemName) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('credit-card', ['size' => 13]).' '.Html::encode($model->billPaySystemName),
        [
            'data-toggle' => 'tooltip',
            'title'       => 'Способ оплаты',
        ]
    );
}

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->asText,
    'imageSrc'     => false,
    'metaItems'    => $metaItems,
    'status'       => $status,
    'showBackLink' => false,
]);
