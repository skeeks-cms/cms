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
            <img class="sx-model-header__image sx-model-header__image--round" src="<?php echo \Yii::$app->imaging->getImagingUrl($model->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
        </div>
    <? endif; ?>
            <div class="sx-model-header__content">

                <h1 class="sx-user-header-h1 sx-model-header__title"><?php echo $model->shortDisplayNameWithAlias; ?>
                    <?php if (!$model->is_active) : ?>
                        <span class="sx-status sx-status--danger sx-model-header__state"
                              data-toggle="tooltip"
                              title="Пользователь отключен, значит не может авторизоваться на сайте!">Отключен</span>
                    <?php endif; ?>


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
                    <? if ($model->email) : ?>
                        <span><i class="far fa-envelope"></i> <?php echo $model->email; ?></span>
                    <? endif; ?>
                    <? if ($model->phone) : ?>
                        <span><i class="fas fa-phone"></i> <?php echo $model->phone; ?></span>
                    <? endif; ?>


                </div>
            </div>
        </div>
    </div>

    <div class="sx-model-header__side">
            <div class="sx-model-header__toolbar">

        <span>
            <?

            $actionData = \yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                "size"            => 'small',
                "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    "/cms/admin-user/update",
                    'pk' => $model->id,
                ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
            ]);
            ?>

            <a href="#" class="btn btn-default" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
            ); ?>' title="Редактировать основную информацию: фото, активность, имя, фамилия и т.д." data-toggle="tooltip">
                <i class="fas fa-pencil-alt"></i>
            </a>
</span>

                <span>
            <?

            $actionData = \yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                "size"            => 'small',
                "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    "/cms/admin-user/change-password",
                    'pk' => $model->id,
                ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
            ]);
            ?>

            <a href="#" class="btn btn-default" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
            ); ?>' title="Изменить пароль" data-toggle="tooltip">
                <i class="fas fa-key"></i>
            </a>

        </span>

                <?php if ($model->phone) : ?>
                    <span>
                <a href="#" class="btn btn-default" title="Позвонить" data-toggle="tooltip"><i class="fas fa-phone"></i></a>
            </span>
                    <span>
                <a href="#" class="btn btn-default sx-send-sms-trigger" data-phone="<?php echo $model->phone; ?>" title="Написать sms" data-toggle="tooltip"><i class="fas fa-sms"></i></a>
            </span>
                <?php endif; ?>
                <?php if ($model->email) : ?>
                    <span>
                <a href="#" class="btn btn-default" title="Написать письмо" data-toggle="tooltip"><i class="far fa-envelope"></i></a>
            </span>
                <?php endif; ?>
            </div>

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
        <div class="sx-model-header__actions">
            <?php echo $href; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
