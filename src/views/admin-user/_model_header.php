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
$this->registerCss(<<<CSS
.sx-user-header-h1 .sx-controlls {
    opacity: 0;
    transition: 0.5s;
}
.sx-user-header-h1:hover .sx-controlls {
    opacity: 1;
}
CSS
);
?>
<div class="row" style="margin-bottom: 5px;">
    <? if ($model->image) : ?>
        <div class="col my-auto" style="max-width: 60px">
            <img style="border: 2px solid #ededed; border-radius: 50%;" src="<?php echo \Yii::$app->imaging->getImagingUrl($model->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
        </div>
    <? endif; ?>
    <div class="col my-auto">
        <h1 class="sx-user-header-h1" style="margin-bottom: 0px; line-height: 1.1;"><?php echo $model->shortDisplayNameWithAlias; ?>
            <?php echo $model->is_active ? '' : '<span data-toggle="tooltip" title="Пользователь отключен, значит не может авторизоваться на сайте!" style="color: red; font-size: 20px;">(отключен!)</span>' ?>


        </h1>

        <div class="sx-small-info" style="font-size: 10px; color: silver;">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip"><i class="fas fa-key"></i> <?php echo $model->id; ?></span>
            <? if ($model->created_at) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана в базе: <?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>"><i
                            class="far fa-clock"></i> <?php echo \Yii::$app->formatter->asDate($model->created_at); ?></span>
            <? endif; ?>
            <? if ($model->created_by) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана пользователем с ID: <?php echo $model->createdBy->id; ?>"><i
                            class="far fa-user"></i> <?php echo $model->createdBy->shortDisplayName; ?></span>
            <? endif; ?>
            <? if ($model->email) : ?>
                <span style="margin-left: 5px;"><i class="far fa-envelope"></i> <?php echo $model->email; ?></span>
            <? endif; ?>
            <? if ($model->phone) : ?>
                <span style="margin-left: 5px;"><i class="fas fa-phone"></i> <?php echo $model->phone; ?></span>
            <? endif; ?>


        </div>
    </div>

    <div class="col my-auto text-right">
        
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
</div>
