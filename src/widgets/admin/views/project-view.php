<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $project \skeeks\cms\models\CmsProject */
/* @var $widget \skeeks\cms\widgets\admin\CmsProjectViewWidget */
$widget = $this->context;
$project = $widget->project;

$title = $project->is_private ? "Проект закрытый" : "Проект открытый";

$actionData = \yii\helpers\Json::encode([
    "isOpenNewWindow" => true,
    "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(["/cms/admin-cms-project/view", "pk" => $project->id])->enableEmptyLayout()->enableNoActions()->url,
]);
$titleOptions = \yii\helpers\ArrayHelper::merge([
    'data-toggle' => 'tooltip',
    'data-html'   => 'true',
    'data-pjax'   => '0',
    'title'       => $title,
    'href'        => \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]),
    'onclick'     => new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
    ),
], $widget->tagNameOptions);
\yii\helpers\Html::addCssClass($titleOptions, ['sx-preview-card__title', 'sx-collection-cell__primary']);
?>
<div class="sx-preview-card sx-preview-card--project">

    <div class="sx-preview-card__media">

        <? if ($project->cmsImage) : ?>
            <a class="sx-preview-card__media-link" href="<?= \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]); ?>" data-pjax="0">
                <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($project->cmsImage ? $project->cmsImage->src : \skeeks\cms\helpers\Image::getCapSrc(),
                    new \skeeks\cms\components\imaging\filters\Thumbnail([
                        'h' => $widget->prviewImageSize,
                        'w' => $widget->prviewImageSize,
                        'm' => \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND,
                    ])); ?>" alt=""
                     class="sx-photo sx-img-size-<?= $widget->prviewImageSize; ?>"
                     title="<?= $title; ?>"
                     data-toggle="tooltip"
                     data-html="true"

                     onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                ); ?>'
                >
            </a>
        <? else : ?>
            <div class="sx-no-photo sx-img-size-<?= $widget->prviewImageSize; ?>">
                <a class="sx-preview-card__media-link" href="<?= \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]); ?>" data-pjax="0"
                onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                ); ?>'
                >
                    <?= \skeeks\cms\helpers\StringHelper::strtoupper(
                        \skeeks\cms\helpers\StringHelper::substr($project->name, 0, 2)
                    ); ?>
                </a>
            </div>
        <? endif; ?>


    </div>

    <div class="sx-preview-card__content sx-collection-cell sx-collection-cell--stack">

        <?= \yii\helpers\Html::tag($widget->tagName, $project->asText, $titleOptions); ?>

        <? if ($widget->isShowOnlyName === false) : ?>
            <div class="sx-employee sx-preview-card__meta sx-collection-cell__secondary">
                <?= $project->is_private ? "Закрытый" : "Открытый"; ?>
            </div>
        <? endif; ?>

    </div>


</div>
