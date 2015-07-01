
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
/* @var $widget \skeeks\cms\modules\admin\widgets\formInputs\StorageImages */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\Publication */
$controller = \Yii::$app->createController('cms/admin-storage-files')[0];
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
    }

    .sx-fromWidget-storageImages .sx-group-images img
    {
        max-width: 100px;
        border: 2px solid silver;
    }
    .sx-fromWidget-storageImages .sx-group-images img:hover
    {
        max-width: 100px;
        border: 2px solid #20a8d8;
    }

CSS
);
?>


<div class="sx-fromWidget-storageImages">
    <? \skeeks\cms\modules\admin\widgets\Pjax::begin([
        'id' => 'pjax-storage-images-widget-' . $widget->id,
        'blockPjaxContainer' => true,
    ]);?>


    <div class="sx-group-images">
        <div class="row col-md-12">
            <? if ($images = $model->getFilesGroups()->getComponent($widget->fileGroup)->fetchFiles()) : ?>
                <? foreach($images as $imageFile) : ?>
                    <div class="sx-image">
                        <a href="<?= $imageFile->src; ?>" class="sx-fancybox" data-pjax="0">
                            <img src="<?= \Yii::$app->imaging->getImagingUrl($imageFile->src, new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>" />
                        </a>
                        <div class="sx-controlls">
                            <?
                            $controllerTmp = clone $controller;
                            $controllerTmp->setModel($imageFile);

                            echo \skeeks\cms\modules\admin\widgets\DropdownControllerActions::widget([
                                "controller"            => $controllerTmp,
                                "isOpenNewWindow"       => true,
                                "clientOptions"         =>
                                [
                                    'pjax-id' => 'pjax-storage-images-widget-' . $widget->id
                                ],
                            ]);
                            ?>
                        </div>
                    </div>
                <? endforeach; ?>
            <? endif; ?>
        </div>
    </div>

    <? \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>

    <div class="sx-controlls">
        <?= \skeeks\cms\widgets\StorageFileManager::widget([
            'model'     => $model,
            'fileGroup' => $widget->fileGroup,
            'clientOptions' =>
            [
                'completeUploadFile' => new \yii\web\JsExpression(<<<JS
                function(data)
                {
                    $.pjax.reload('#pjax-storage-images-widget-{$widget->id}', {});
                }
JS
)
            ],
        ]); ?>
    </div>
</div>

