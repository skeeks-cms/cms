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
<div class="row" style="margin-bottom: 5px;">
   
    <div class="col my-auto">
            <div>
                <? echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget([
                    'task'            => $model,
                    'isShowOnlyName'  => true,
                    'tagName'         => "h1",
                    'isAction'        => false,
                    'prviewImageSize' => 50,
                ]) ?>
            </div>


            <div    class="sx-small-info" style="font-size: 10px; color: silver;">
                    <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip"><i class="fas fa-key"></i> <?php echo $model->id; ?></span>
                    <? if ($model->created_at) : ?>
                        <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана в базе: <?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>"><i
                                    class="far fa-clock"></i> <?php echo \Yii::$app->formatter->asDate($model->created_at); ?></span>
                    <? endif; ?>
                    <? if ($model->created_by) : ?>
                        <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана пользователем с ID: <?php echo $model->createdBy->id; ?>"><i
                                    class="far fa-user"></i> <?php echo $model->createdBy->shortDisplayName; ?></span>
                    <? endif; ?>
                   

                </div>

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
        <div class="col my-auto" style="text-align: right; max-width: 65px;">
            <?php echo $href; ?>
        </div>
    <?php endif; ?>
</div>