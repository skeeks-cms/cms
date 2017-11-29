<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.09.2015
 */

/* @var $widget     \skeeks\cms\widgets\formInputs\ModelStorageFiles */
/* @var $this       yii\web\View */
/* @var $model      \yii\db\ActiveRecord */

if (!$widget->viewItemTemplate) {
    $controller = \Yii::$app->createController('cms/admin-storage-files')[0];
}
?>
<?
$this->registerCss(<<<CSS
.sx-fromWidget-storageImages
{}

    .sx-fromWidget-storageImages .sx-main-image img
    {
        max-width: 250px;
        border: 2px solid silver;
    }

    .sx-fromWidget-storageImages .sx-main-image img:hover
    {
        border: 2px solid #20a8d8;
    }

    .sx-fromWidget-storageImages .sx-controlls
    {
        margin-top: 3px;
    }


    .sx-fromWidget-storageImages .sx-image
    {
        float: left;
        margin-right: 15px;
        margin-bottom: 15px;
        box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.42);
        padding: 10px;
        background: white;
    }

    .sx-fromWidget-storageImages .sx-group-images img
    {
        max-width: 100px;
        border: 1px solid silver;
        margin-bottom: 5px;
    }
    .sx-fromWidget-storageImages .sx-group-images img:hover
    {
        max-width: 100px;
        border: 1px solid #20a8d8;

    }

CSS
);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.MultiUpload = sx.classes.Component.extend({

        execute: function()
        {
            var ajaxQuery = sx.ajax.preparePostQuery(this.get('backendUrl'), this.toArray());
            new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
            ajaxQuery.execute();
        }

    });
})(sx, sx.$, sx._);
JS
);
?>
<div class="sx-fromWidget-storageImages">
    <?php \skeeks\cms\modules\admin\widgets\Pjax::begin([
        'id' => 'pjax-storage-images-widget-' . $widget->id,
        'blockPjaxContainer' => true,
    ]); ?>


    <div class="sx-group-images">
        <div class="row col-md-12">
            <?php if ($files = $widget->files) : ?>
                <?php foreach ($files as $imageFile) : ?>
                    <?php if ($imageFile instanceof \skeeks\cms\models\StorageFile) : ?>
                        <div class="sx-image">
                            <?php if (!$widget->viewItemTemplate) : ?>

                                <?php if ($imageFile->isImage()) : ?>
                                    <a href="<?= $imageFile->src; ?>" class="sx-fancybox" data-pjax="0">
                                        <img src="<?= \Yii::$app->imaging->getImagingUrl($imageFile->src,
                                            new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
                                    </a>
                                <?php else
                                    : ?>
                                    <?= $imageFile->name ? $imageFile->name : $imageFile->original_name;
                                    ?>
                                <?php endif; ?>

                                <div class="sx-controlls">
                                    <?
                                    try {
                                        $controllerTmp = clone $controller;
                                        $controllerTmp->setModel($imageFile);

                                        echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                            "actions" => $controllerTmp->modelActions,
                                            "isOpenNewWindow" => true,
                                            "clientOptions" =>
                                                [
                                                    'pjax-id' => 'pjax-storage-images-widget-' . $widget->id
                                                ],
                                        ]);
                                    } catch (\Exception $e) {
                                        echo $e->getMessage();
                                    }
                                    ?>
                                </div>
                            <?php else
                                : ?>
                                <?= $widget->renderItem($imageFile);
                                ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>

    <div class="sx-controlls">
        <?= \skeeks\cms\widgets\StorageFileManager::widget(\yii\helpers\ArrayHelper::merge([
            'clientOptions' =>
                [
                    'simpleUpload' =>
                        [
                            'options' =>
                                [
                                    'multiple' => true
                                ]
                        ],

                    'completeUploadFile' => new \yii\web\JsExpression(<<<JS
                function(data)
                {
                    var result = data.response;
                    if (result.success === true)
                    {

                        var SingleUpload = new sx.classes.MultiUpload( _.extend({$widget->getJsonString()}, {
                            'file_id' : result.file.id,
                        }) );

                        SingleUpload.execute();
                    }

                    $.pjax.reload('#pjax-storage-images-widget-{$widget->id}', {});
                }
JS
                    )
                ],
        ], $widget->controllWidgetOptions)); ?>
    </div>
</div>

