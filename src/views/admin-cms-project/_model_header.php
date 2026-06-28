<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsProject
 */

$controller = $this->context;
$this->registerCss(<<<CSS
button.sx-quick-access-favorite-btn {
    all: unset;
    display: inline-flex;
    width: auto !important;
    height: auto !important;
    min-width: 0 !important;
    min-height: 0 !important;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    color: #a8b0ba;
    font-size: 17px;
    line-height: 1;
    vertical-align: 4px;
    background: transparent none !important;
    box-shadow: none !important;
    cursor: pointer;
    transition: color .15s ease;
}
button.sx-quick-access-favorite-btn:hover,
button.sx-quick-access-favorite-btn:focus {
    color: #d99a00;
    background: transparent none !important;
    box-shadow: none !important;
    outline: 0;
}
button.sx-quick-access-favorite-btn.is-active {
    color: #f0ad00;
}
CSS
);

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
<div class="row" style="margin-bottom: 5px;">
    <?php if ($model->cmsImage) : ?>
        <div class="col my-auto" style="max-width: 60px">
            <img style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #ededed; border-radius: 50%;"
                 src="<?= \yii\helpers\Html::encode($makeQuickAccessImageUrl($model)); ?>"
                 alt="">
        </div>
    <?php endif; ?>

    <div class="col my-auto">
        <h1 class="sx-user-header-h1" style="margin-bottom: 0; line-height: 1.1;">
            <?= \yii\helpers\Html::encode($model->name); ?>
            <button type="button"
                    class="sx-quick-access-favorite-btn"
                    data-sx-quick-access-favorite
                    data-sx-quick-access-item="<?= \yii\helpers\Html::encode(\yii\helpers\Json::encode($quickAccessFavoriteItem)); ?>"
                    title="Добавить в избранное">
                <i class="far fa-star"></i>
            </button>
        </h1>

        <div class="sx-small-info" style="font-size: 10px; color: silver;">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip">
                <i class="fas fa-key"></i> <?= (int) $model->id; ?>
            </span>
            <?php if ($model->created_at) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана в базе: <?= \Yii::$app->formatter->asDatetime($model->created_at); ?>">
                    <i class="far fa-clock"></i> <?= \Yii::$app->formatter->asDate($model->created_at); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->created_by && $model->createdBy) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана пользователем с ID: <?= (int) $model->createdBy->id; ?>">
                    <i class="far fa-user"></i> <?= \yii\helpers\Html::encode($model->createdBy->shortDisplayName); ?>
                </span>
            <?php endif; ?>
            <?php if ($model->cmsCompany) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="<?= \yii\helpers\Html::encode($model->cmsCompany->name); ?>">
                    <i class="far fa-building"></i> <?= \yii\helpers\Html::encode($model->cmsCompany->name); ?>
                </span>
            <?php endif; ?>
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
        <div class="col my-auto" style="text-align: right; max-width: 65px;">
            <?= \yii\helpers\Html::a('<i class="fa fa-trash sx-action-icon"></i>', "#", [
                'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
                'class'       => "btn btn-default",
                'data-toggle' => "tooltip",
                'title'       => "Удалить",
            ]); ?>
        </div>
    <?php endif; ?>
</div>
