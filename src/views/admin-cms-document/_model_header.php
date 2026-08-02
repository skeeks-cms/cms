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
?>

<div class="sx-model-header sx-model-header--split">
    <div class="sx-model-header__main">
        <h1 class="sx-model-header__title"><?= Html::encode($model->asText); ?></h1>
        <div class="sx-model-header__meta">
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

    <div class="sx-model-header__side">
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
            <div class="sx-model-header__actions">
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
