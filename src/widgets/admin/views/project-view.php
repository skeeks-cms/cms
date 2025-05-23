<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $project \skeeks\crm\models\CrmProject */
/* @var $widget \skeeks\crm\widgets\ProjectViewWidget */
$widget = $this->context;
$project = $widget->project;

$class = 'g-brd-gray-light-v4';
$title = $project->is_private ? "Проект закрытый" : "Проект открытый";

$actionData = \yii\helpers\Json::encode([
    "isOpenNewWindow" => true,
    "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(["/cms/admin-cms-project/view", "pk" => $project->id])->enableEmptyLayout()->enableNoActions()->url,
]);
?>
<div class="g-font-weight-300 g-color-gray-dark-v6 align-items-center sx-preview-card">

    <div class="" style="float: left; margin-right: 10px;">

        <? if ($project->cmsImage) : ?>
            <a href="<?= \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]); ?>" data-pjax="0" style="border: 0;">
                <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($project->cmsImage ? $project->cmsImage->src : \skeeks\cms\helpers\Image::getCapSrc(),
                    new \skeeks\cms\components\imaging\filters\Thumbnail([
                        'h' => $widget->prviewImageSize,
                        'w' => $widget->prviewImageSize,
                        'm' => \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND,
                    ])); ?>" alt=""
                     class="sx-photo <?= $class; ?> sx-img-size-<?= $widget->prviewImageSize; ?>"
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
            <div class="sx-no-photo g-brd-gray-light-v4 sx-img-size-<?= $widget->prviewImageSize; ?>">
                <a href="<?= \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]); ?>" data-pjax="0" style="border: 0;"
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

    <div>

        <?= \yii\helpers\Html::tag($widget->tagName, $project->asText, \yii\helpers\ArrayHelper::merge([
            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-pjax'   => '0',
            'title'       => $title,
            'href'        => \yii\helpers\Url::to(["/cms/admin-cms-project/view", "pk" => $project->id]),
            'onclick' => new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
            )
        ], $widget->tagNameOptions)); ?>

        <? if ($widget->isShowOnlyName === false) : ?>
            <br/>
            <div class="sx-employee">
                <?= $project->is_private ? "Закрытый" : "Открытый"; ?>
            </div>
        <? endif; ?>

    </div>


</div>


