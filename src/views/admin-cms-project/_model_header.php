<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsProject
 */

$controller = $this->context;
$makeQuickAccessImageUrl = function ($model) {
    if ($model && $model->cmsImage) {
        return (string) \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
            'w' => 80,
            'h' => 80,
            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
        ]), '', true);
    }

    return null;
};

$quickAccessFavoriteItem = [
    'type'   => 'projects',
    'id'     => (int) $model->id,
    'name'   => (string) $model->name,
    'url'    => \yii\helpers\Url::to(['/cms/admin-cms-project/view', 'pk' => $model->id]),
    'action' => (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
        '/cms/admin-cms-project/view',
        'pk' => $model->id,
    ])->enableEmptyLayout()->enableNoActions()->url,
    'image'  => $makeQuickAccessImageUrl($model),
];
?>
<div class="sx-model-header sx-model-header--split">
    <div class="sx-model-header__main">
        <div class="sx-model-header__identity">
    <?php if ($model->cmsImage) : ?>
        <div class="sx-model-header__media">
            <img class="sx-model-header__image sx-model-header__image--round"
                 src="<?= \yii\helpers\Html::encode($makeQuickAccessImageUrl($model)); ?>"
                 alt="">
        </div>
    <?php endif; ?>

        <div class="sx-model-header__content">
        <h1 class="sx-user-header-h1 sx-model-header__title">
            <?= \yii\helpers\Html::encode($model->name); ?>
            <button type="button"
                    class="sx-quick-access-favorite-btn"
                    data-sx-quick-access-favorite
                    data-sx-quick-access-item="<?= \yii\helpers\Html::encode(\yii\helpers\Json::encode($quickAccessFavoriteItem)); ?>"
                    title="Добавить в избранное">
                <i class="far fa-star"></i>
            </button>
        </h1>

        <div class="sx-small-info sx-model-header__meta">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip">
                <i class="fas fa-key"></i> <?= (int) $model->id; ?>
            </span>
            <?php if ($model->created_at) : ?>
                <span data-toggle="tooltip" title="Запись создана в базе: <?= \Yii::$app->formatter->asDatetime($model->created_at); ?>">
                    <i class="far fa-clock"></i> <?= \Yii::$app->formatter->asDate($model->created_at); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->created_by && $model->createdBy) : ?>
                <span data-toggle="tooltip" title="Запись создана пользователем с ID: <?= (int) $model->createdBy->id; ?>">
                    <i class="far fa-user"></i> <?= \yii\helpers\Html::encode($model->createdBy->shortDisplayName); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->cmsCompany) : ?>
                <span data-toggle="tooltip" title="<?= \yii\helpers\Html::encode($model->cmsCompany->name); ?>">
                    <i class="far fa-building"></i> <?= \yii\helpers\Html::encode($model->cmsCompany->name); ?>
                </span>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <?php
    $modelActions = $controller->modelActions;
    $deleteAction = \yii\helpers\ArrayHelper::getValue($modelActions, "delete");
    ?>
    <?php if ($deleteAction) : ?>
        <?php
        $actionData = \yii\helpers\Json::encode([
            "url"             => $deleteAction->url,
            "isOpenNewWindow" => true,
            "confirm"         => isset($deleteAction->confirm) ? $deleteAction->confirm : "",
            "method"          => isset($deleteAction->method) ? $deleteAction->method : "",
            "request"         => isset($deleteAction->request) ? $deleteAction->request : "",
            "size"            => isset($deleteAction->size) ? $deleteAction->size : "",
        ]);
        ?>
        <div class="sx-model-header__side">
        <div class="sx-model-header__actions">
            <?= \yii\helpers\Html::a('<i class="fa fa-trash sx-action-icon"></i>', "#", [
                'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
                'class'       => "btn btn-default",
                'data-toggle' => "tooltip",
                'title'       => "Удалить",
            ]); ?>
        </div>
        </div>
    <?php endif; ?>
</div>
