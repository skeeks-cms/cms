<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\shop\models\ShopDocument $model
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendModelHeader;
use yii\helpers\Html;

$metaItems = [];
$metaItems[] = Html::tag('span',
    BackendIcon::render('key', ['size' => 13]).' '.Html::encode($model->id),
    [
        'title'       => Yii::t('skeeks/backend', 'Record ID'),
        'data-toggle' => 'tooltip',
    ]
);

$documentDate = $model->issued_at ?: $model->created_at;
if ($documentDate) {
    $dateTime = Yii::$app->formatter->asDatetime($documentDate);
    $metaItems[] = Html::tag('span',
        BackendIcon::render('clock', ['size' => 13]).' '.Html::encode(Yii::$app->formatter->asDate($documentDate)),
        [
            'title'       => 'Дата документа: '.$dateTime,
            'data-toggle' => 'tooltip',
        ]
    );
}
if ($model->created_by && $model->createdBy) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('user', ['size' => 13]).' '.Html::encode($model->createdBy->shortDisplayName),
        [
            'title'       => Yii::t('skeeks/backend', 'Created by user #{id}', ['id' => $model->createdBy->id]),
            'data-toggle' => 'tooltip',
        ]
    );
}
$metaItems[] = Html::tag('span',
    BackendIcon::render('file', ['size' => 13]).' '.Html::encode($model->typeAsText),
    [
        'title'       => 'Тип документа',
        'data-toggle' => 'tooltip',
    ]
);

echo BackendModelHeader::widget([
    'model'              => $model,
    'title'              => $model->asText,
    'renderDefaultMeta'  => false,
    'metaItems'          => $metaItems,
    'renderDeleteAction' => (bool)$model->isEditable,
    'showBackLink'       => false,
]);
