<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsContractor $model
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;
?>

<div class="sx-model-header sx-model-header--split">
    <div class="sx-model-header__main">
        <h1 class="sx-model-header__title"><?= Html::encode($model->asText); ?></h1>
        <div class="sx-model-header__meta">
            <span data-toggle="tooltip" title="ID записи">
                <i class="fas fa-key"></i> <?= (int)$model->id; ?>
            </span>
            <?php if ($model->created_at) : ?>
                <span data-toggle="tooltip" title="Создано: <?= Yii::$app->formatter->asDatetime($model->created_at); ?>">
                    <i class="far fa-clock"></i> <?= Yii::$app->formatter->asDate($model->created_at); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->created_by && $model->createdBy) : ?>
                <span data-toggle="tooltip" title="Автор записи">
                    <i class="far fa-user"></i> <?= Html::encode($model->createdBy->shortDisplayName); ?>
                </span>
            <?php endif; ?>
            <span data-toggle="tooltip" title="Тип реквизитов">
                <i class="far fa-address-card"></i> <?= Html::encode($model->typeAsText); ?>
            </span>
        </div>
    </div>

    <div class="sx-model-header__side">
        <?php $refreshDadataAction = $controller->createAction('refresh-dadata'); ?>
        <?php if ($refreshDadataAction && $refreshDadataAction->isAllow) : ?>
            <?php
            $refreshActionData = Json::encode([
                'url'     => $refreshDadataAction->url,
                'confirm' => $refreshDadataAction->confirm,
                'method'  => $refreshDadataAction->method,
                'request' => $refreshDadataAction->request,
            ]);
            ?>
            <?= Html::a('<i class="fa fa-sync-alt sx-action-icon"></i> Актуализировать данные', '#', [
                'onclick'     => "new sx.classes.backend.widgets.Action({$refreshActionData}).go(); return false;",
                'class'       => 'btn btn-primary',
                'data-toggle' => 'tooltip',
                'title'       => 'Обновить реквизиты по ИНН через DaData',
            ]); ?>
        <?php endif; ?>

        <?php if ($deleteAction = ArrayHelper::getValue($controller->modelActions, 'delete')) : ?>
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
            <?= Html::a('<i class="fa fa-trash sx-action-icon"></i>', '#', [
                'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
                'class'       => 'btn btn-default',
                'data-toggle' => 'tooltip',
                'title'       => 'Удалить',
            ]); ?>
        <?php endif; ?>
    </div>
</div>
