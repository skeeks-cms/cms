<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\shop\models\ShopBill
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;

$statusClass = 'is-waiting';
$statusText = 'Ожидает оплаты';
if ($model->closed_at) {
    $statusClass = 'is-closed';
    $statusText = 'Отменен';
} elseif ($model->paid_at) {
    $statusClass = 'is-paid';
    $statusText = 'Оплачен';
}

$this->registerCss(<<<CSS
.sx-bill-model-header {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
    margin-bottom: 8px;
}
.sx-bill-model-header h1 {
    margin: 0;
    line-height: 1.1;
}
.sx-bill-model-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 5px 10px;
    margin-top: 5px;
    color: silver;
    font-size: 10px;
}
.sx-bill-model-status-box {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    gap: 0;
    padding-top: 4px;
}
.sx-bill-model-side {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.sx-bill-model-status {
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 600;
    white-space: nowrap;
}
.sx-bill-model-status.is-paid {
    color: #087a2f;
    background: #e8f7ee;
}
.sx-bill-model-status.is-closed {
    color: #a51d1d;
    background: #fdecec;
}
.sx-bill-model-status.is-waiting {
    color: #6f5100;
    background: #fff4cc;
}
.sx-bill-model-due {
    color: #6f5100;
    font-weight: 600;
    white-space: nowrap;
    font-size: 0.8rem;
}
.sx-bill-model-due.is-paid {
    color: #087a2f;
}
.sx-bill-model-due i {
    margin-right: 4px;
}
@media (max-width: 900px) {
    .sx-bill-model-header {
        flex-direction: column;
    }
    .sx-bill-model-status-box {
        align-items: flex-start;
    }
    .sx-bill-model-side {
        width: 100%;
        justify-content: space-between;
    }
}
CSS);
?>

<div class="sx-bill-model-header">
    <div>
        <h1><?= Html::encode($model->asText); ?></h1>
        <div class="sx-bill-model-meta">
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

    <div class="sx-bill-model-side">
        <div class="sx-bill-model-status-box">
            <div class="sx-bill-model-status <?= Html::encode($statusClass); ?>"><?= Html::encode($statusText); ?></div>
            <?php if ($model->paid_at) : ?>
                <div class="sx-bill-model-due is-paid"><i class="fa fa-check"></i> Оплачен <?= \Yii::$app->formatter->asDate($model->paid_at); ?></div>
            <?php elseif (!$model->closed_at && $model->due_at) : ?>
                <div class="sx-bill-model-due"><i class="far fa-calendar"></i> Оплатить до <?= \Yii::$app->formatter->asDate($model->due_at); ?></div>
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
