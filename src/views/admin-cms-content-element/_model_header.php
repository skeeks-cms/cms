<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsContentElement
 */
$controller = $this->context;
?>
<div class="sx-model-header sx-model-header--split">
    <div class="sx-model-header__main">
        <div class="sx-model-header__identity">
    <? if ($model->image) : ?>
        <div class="sx-model-header__media">
            <img class="sx-model-header__image" src="<?php echo \Yii::$app->imaging->getImagingUrl($model->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
        </div>
    <? endif; ?>
            <div class="sx-model-header__content">
                <h1 class="sx-model-header__title">
                    <?php echo $model->name; ?>
                    <? if ($model->is_adult) : ?>
                        <span class="sx-model-header__external-id sx-text--danger">
                            <span data-toggle="tooltip" title="Этот раздел содержит информацию для взрослых. Имеет возрастные ограничения 18+">[18+]</span>
                        </span>
                    <? endif; ?>
                    <? if (!$model->isAllowIndex) : ?>
                        <span class="sx-model-header__external-id sx-text--danger">
                            <span data-toggle="tooltip" title="Этот товар не индексируется поисковыми системами">[no index]</span>
                        </span>
                    <? endif; ?>
                    <? if ($model->sx_id) : ?>
                        <?
                        $sxInfoUpdateClass = (isset($model->is_sx_info_update) && !$model->is_sx_info_update)
                            ? "sx-text--danger"
                            : "sx-text--success";
                        $sxInfoUpdateTitle = (isset($model->is_sx_info_update) && !$model->is_sx_info_update)
                            ? "SkeekS ID: {$model->sx_id}. Обновление информации из сервиса SkeekS Товары запрещено"
                            : "SkeekS ID: {$model->sx_id}. Информация обновляется из сервиса SkeekS Товары";
                        $sxMarketUrl = isset(\Yii::$app->skeeksSuppliersApi) ? \Yii::$app->skeeksSuppliersApi->getProductUrl($model->sx_id) : "#";
                        $sxIcon = "<i class='fas fa-link {$sxInfoUpdateClass}'></i>";
                        ?>
                        <span class="sx-model-header__external-id">
                            <?php echo \yii\helpers\Html::a($sxIcon, $sxMarketUrl, [
                                'target' => '_blank',
                                'data-pjax' => '0',
                                'data-toggle' => 'tooltip',
                                'title' => $sxInfoUpdateTitle,
                                'class' => $sxInfoUpdateClass,
                            ]); ?>
                        </span>
                    <? endif; ?>

                </h1>
                <div class="sx-small-info sx-model-header__meta">
                    <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip"><i class="fas fa-key"></i> <?php echo $model->id; ?></span>
                    <? if ($model->created_at) : ?>
                        <span data-toggle="tooltip" title="Запись создана в базе: <?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>"><i
                                    class="far fa-clock"></i> <?php echo \Yii::$app->formatter->asDate($model->created_at); ?></span>
                    <? endif; ?>
                    <? if ($model->created_by) : ?>
                        <span data-toggle="tooltip" title="Запись создана пользователем с ID: <?php echo $model->createdBy->id; ?>"><i
                                    class="far fa-user"></i> <?php echo $model->createdBy->shortDisplayName; ?></span>
                    <? endif; ?>
                    <? if ($model->tree_id) : ?>
                        <span data-toggle="tooltip" title="<?php echo $model->cmsTree->fullName; ?>"><i class="far fa-folder"></i> <?php echo $model->cmsTree->name; ?></span>
                    <? endif; ?>

                </div>
            </div>
        </div>
    </div>

    <div class="sx-model-header__side">
        <div class="sx-model-header__actions">
            <? if ($model->cmsContent->is_have_page) : ?>
                <a href="<?php echo $model->url; ?>" data-toggle="tooltip" class="btn btn-default" target="_blank" title="<?php echo \Yii::t('skeeks/cms', 'Watch to site (opens new window)'); ?>"><i
                            class="fas fa-external-link-alt"></i></a>
            <? endif; ?>
    <?php

    $modelActions = $controller->modelActions;
    $deleteAction = \yii\helpers\ArrayHelper::getValue($modelActions, "delete");

    if ($deleteAction) : ?>
        <?php

        $actionData = [
            "url"             => $deleteAction->url,

            //TODO:// is deprecated
            "isOpenNewWindow" => true,
            "confirm"         => isset($deleteAction->confirm) ? $deleteAction->confirm : "",
            "method"          => isset($deleteAction->method) ? $deleteAction->method : "",
            "request"         => isset($deleteAction->request) ? $deleteAction->request : "",
            "size"            => isset($deleteAction->size) ? $deleteAction->size : "",
        ];
        $actionData = \yii\helpers\Json::encode($actionData);

        $href = \yii\helpers\Html::a('<i class="fa fa-trash sx-action-icon"></i>', "#", [
            'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
            'class'       => "btn btn-default",
            'data-toggle' => "tooltip",
            'title'       => "Удалить",
        ]);
        ?>
        <?php echo $href; ?>
    <?php endif; ?>
        </div>
    </div>
</div>
