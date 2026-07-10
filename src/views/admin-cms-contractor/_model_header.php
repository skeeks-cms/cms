<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsContractor $model
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$controller = $this->context;

$this->registerCss(<<<CSS
.sx-contractor-model-header {
    display: flex;
    justify-content: space-between;
    gap: 1.125rem;
    align-items: flex-start;
    margin-bottom: .5rem;
}
.sx-contractor-model-header h1 {
    margin: 0;
    line-height: 1.1;
}
.sx-contractor-model-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem .65rem;
    margin-top: .3rem;
    color: silver;
    font-size: .625rem;
}
.sx-contractor-model-side {
    display: flex;
    align-items: center;
    gap: .75rem;
}
@media (max-width: 56.25rem) {
    .sx-contractor-model-header {
        flex-direction: column;
    }
    .sx-contractor-model-side {
        width: 100%;
        justify-content: space-between;
    }
}
CSS
);
?>

<div class="sx-contractor-model-header">
    <div>
        <h1><?= Html::encode($model->asText); ?></h1>
        <div class="sx-contractor-model-meta">
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

    <div class="sx-contractor-model-side">
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
