<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\shop\models\ShopBill
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;

$statusClass = 'sx-status--warning';
$statusText = 'Ожидает оплаты';
if ($model->closed_at) {
    $statusClass = 'sx-status--danger';
    $statusText = 'Отменен';
} elseif ($model->paid_at) {
    $statusClass = 'sx-status--success';
    $statusText = 'Оплачен';
}
?>

<div class="sx-model-header sx-model-header--split">
    <div class="sx-model-header__main">
        <h1 class="sx-model-header__title"><?= Html::encode($model->asText); ?></h1>
        <div class="sx-model-header__meta">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip">
                <i class="fas fa-key"></i> <?= (int)$model->id; ?>
            </span>
            <?php if ($model->created_at) : ?>
                <span data-toggle="tooltip" title="Счет создан: <?= \Yii::$app->formatter->asDatetime($model->created_at); ?>">
                    <i class="far fa-clock"></i> <?= \Yii::$app->formatter->asDate($model->created_at); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->created_by && $model->createdBy) : ?>
                <span data-toggle="tooltip" title="Счет создан пользователем с ID: <?= (int)$model->createdBy->id; ?>">
                    <i class="far fa-user"></i> <?= Html::encode($model->createdBy->shortDisplayName); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->billPaySystemName) : ?>
                <span data-toggle="tooltip" title="Способ оплаты">
                    <i class="fa fa-credit-card"></i> <?= Html::encode($model->billPaySystemName); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="sx-model-header__side">
        <div class="sx-model-header__status-stack">
            <div class="sx-status <?= Html::encode($statusClass); ?>"><?= Html::encode($statusText); ?></div>
            <?php if ($model->paid_at) : ?>
                <div class="sx-model-header__status-note sx-model-header__status-note--success"><i class="fa fa-check"></i> Оплачен <?= \Yii::$app->formatter->asDate($model->paid_at); ?></div>
            <?php elseif (!$model->closed_at && $model->due_at) : ?>
                <div class="sx-model-header__status-note"><i class="far fa-calendar"></i> Оплатить до <?= \Yii::$app->formatter->asDate($model->due_at); ?></div>
            <?php endif; ?>
        </div>

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
