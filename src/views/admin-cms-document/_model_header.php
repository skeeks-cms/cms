<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\shop\models\ShopDocument
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;

$documentDate = $model->issued_at ?: $model->created_at;

$this->registerCss(<<<CSS
.sx-document-model-header {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
    margin-bottom: 8px;
}
.sx-document-model-header h1 {
    margin: 0;
    line-height: 1.1;
}
.sx-document-model-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 5px 10px;
    margin-top: 5px;
    color: silver;
    font-size: 10px;
}
.sx-document-model-side {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
@media (max-width: 900px) {
    .sx-document-model-header {
        flex-direction: column;
    }
    .sx-document-model-side {
        width: 100%;
        justify-content: space-between;
    }
}
CSS);
?>

<div class="sx-document-model-header">
    <div>
        <h1><?= Html::encode($model->asText); ?></h1>
        <div class="sx-document-model-meta">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip">
                <i class="fas fa-key"></i> <?= (int)$model->id; ?>
            </span>
            <?php if ($documentDate) : ?>
                <span data-toggle="tooltip" title="Дата документа: <?= \Yii::$app->formatter->asDatetime($documentDate); ?>">
                    <i class="far fa-clock"></i> <?= \Yii::$app->formatter->asDate($documentDate); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->created_by && $model->createdBy) : ?>
                <span data-toggle="tooltip" title="Документ создан пользователем с ID: <?= (int)$model->createdBy->id; ?>">
                    <i class="far fa-user"></i> <?= Html::encode($model->createdBy->shortDisplayName); ?>
                </span>
            <?php endif; ?>
            <span data-toggle="tooltip" title="Тип документа">
                <i class="fa fa-file"></i> <?= Html::encode($model->typeAsText); ?>
            </span>
        </div>
    </div>

    <div class="sx-document-model-side">
        <?php if ($model->isEditable && ($deleteAction = ArrayHelper::getValue($controller->modelActions, 'delete'))) : ?>
            <?php
            $actionData = Json::encode([
                'url'             => $deleteAction->url,
                'isOpenNewWindow' => true,
                'confirm'         => isset($deleteAction->confirm) ? $deleteAction->confirm : '',
                'method'          => isset($deleteAction->method) ? $deleteAction->method : '',
                'request'         => isset($deleteAction->request) ? $deleteAction->request : '',
                'size'            => isset($deleteAction->size) ? $deleteAction->size : '',
            ]);
            ?>
            <div style="text-align: right; min-width: 48px;">
                <?= Html::a('<i class="fa fa-trash sx-action-icon"></i>', '#', [
                    'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
                    'class'       => 'btn btn-default',
                    'data-toggle' => 'tooltip',
                    'title'       => 'Удалить',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
