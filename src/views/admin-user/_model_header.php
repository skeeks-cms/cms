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
?>
<div class="row" style="margin-bottom: 5px;">
    <? if ($model->image) : ?>
        <div class="col my-auto" style="max-width: 60px">
            <img style="border: 2px solid #ededed; border-radius: 50%;" src="<?php echo \Yii::$app->imaging->getImagingUrl($model->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
        </div>
    <? endif; ?>
    <div class="col my-auto">
        <h1 style="margin-bottom: 0px; line-height: 1.1;"><?php echo $model->displayName; ?>
            <?php echo $model->is_active ? '<span data-html="true" data-toggle="tooltip" title="Пользователь активен<br />Значит он может авторизоваться на сайте."  style="font-size: 20px; color: green;">✓</span>' : '<span data-toggle="tooltip" title="Товар не активен" style="color: red; font-size: 20px;">x</span>' ?>

            <?

            $actionData = \yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    "/cms/admin-user/update",
                    'pk' => $model->id,
                ])->enableEmptyLayout()->enableNoActions()->url,
            ]);
            ?>

            <i class="fas fa-pencil-alt" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
            ); ?>' title="Редактировать основную информацию: фото, активность, имя, фамилия и т.д." data-toggle="tooltip" style="font-size: 17px; color: silver; cursor: pointer;"></i>

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
</div>
