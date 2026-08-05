<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsContractor $model
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendModelHeader;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;
$metaItems = [
    Html::tag('span',
        BackendIcon::render('info', ['size' => 13]).' '.Html::encode($model->typeAsText),
        [
            'data-toggle' => 'tooltip',
            'title'       => 'Тип реквизитов',
        ]
    ),
];

$toolbar = '';
$refreshDadataAction = $controller->createAction('refresh-dadata');
if ($refreshDadataAction && $refreshDadataAction->isAllow) {
    $refreshActionData = Json::encode([
        'url'     => $refreshDadataAction->url,
        'confirm' => $refreshDadataAction->confirm,
        'method'  => $refreshDadataAction->method,
        'request' => $refreshDadataAction->request,
    ]);
    $toolbar = Html::a(
        BackendIcon::render('refresh', ['size' => 16]).' Актуализировать данные',
        '#',
        [
            'onclick'     => "new sx.classes.backend.widgets.Action({$refreshActionData}).go(); return false;",
            'class'       => 'btn btn-primary',
            'data-toggle' => 'tooltip',
            'title'       => 'Обновить реквизиты по ИНН через DaData',
        ]
    );
}

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->asText,
    'imageSrc'     => false,
    'metaItems'    => $metaItems,
    'toolbar'      => $toolbar,
    'showBackLink' => false,
]);
