
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
/* @var $widget \skeeks\cms\widgets\formInputs\StorageImages */
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
        height: 150px;
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

<? \skeeks\cms\modules\admin\widgets\Pjax::begin([
    'id' => 'pjax-storage-images-widget',
    'blockPjaxContainer' => true,
]);?>
<div class="sx-fromWidget-storageImages">
    <div class="sx-group-mainImage">
        <label>Главное изображение</label>
        <? if ($model->hasMainImage()) : ?>
            <div class="sx-main-image">
                <? $mainImage = $model->getFilesGroups()->getComponent('image')->findFiles()->one()?>
                <img src="<?= $mainImage->src; ?>" />
                <div class="sx-controlls">
                    <?
                        $controllerTmp = clone $controller;
                        $controllerTmp->setModel($mainImage);

                        echo \skeeks\cms\modules\admin\widgets\DropdownControllerActions::widget([
                            "controller"            => $controllerTmp,
                            "isOpenNewWindow"       => true,
                            "clientOptions"         =>
                            [
                                'pjax-id' => 'pjax-storage-images-widget'
                            ],
                        ]);

                        ?>
                </div>
            </div>
        <? else: ?>
            <div class="sx-main-image">
                <img src="<?= \Yii::$app->cms->moduleAdmin()->noImage; ?>" />
            </div>
        <? endif; ?>
    </div>

    <? if ($images = $model->getFilesGroups()->getComponent('images')->fetchFiles()) : ?>
    <div class="sx-group-images">
        <label>Все изображения</label>
        <div class="row col-md-12">

                <? foreach($images as $imageFile) : ?>
                    <div class="sx-image">
                        <img src="<?= $imageFile->src; ?>" />
                        <div class="sx-controlls">
                            <?
                            $controllerTmp = clone $controller;
                            $controllerTmp->setModel($imageFile);

                            echo \skeeks\cms\modules\admin\widgets\DropdownControllerActions::widget([
                                "controller"            => $controllerTmp,
                                "isOpenNewWindow"       => true,
                                "clientOptions"         =>
                                [
                                    'pjax-id' => 'pjax-storage-images-widget'
                                ],
                            ]);

                            ?>
                            <!--<a href="#" class="btn btn-default btn-xs" title="Сделать главным"><i class="glyphicon glyphicon-asterisk"></i></a>-->
                        </div>
                    </div>
                <? endforeach; ?>
        </div>
    </div>
    <? endif; ?>

    <div class="sx-controlls">
        <a href="#" onclick="sx.InputImagesWidget.openUploaderImage(); return false;" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-download-alt"></i> Загрузить главное изображение</a>
        <a href="#" onclick="sx.InputImagesWidget.openUploader(); return false;" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-download-alt"></i> Загрузить изображения</a>
    </div>
</div>


<?
    $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.InputImagesWidget = sx.classes.Component.extend({

            _init: function()
            {
                var self = this;
                this.uploaderWindow = new sx.classes.Window(this.get('uploaderUrl'));
                this.uploaderWindowImage = new sx.classes.Window(this.get('uploaderUrlImage'));

                this.uploaderWindow.bind('close', function()
                {
                    self.reload();
                });

                this.uploaderWindowImage.bind('close', function()
                {
                    self.reload();
                });
            },

            reload: function()
            {
                this.onDomReady(function()
                {
                    $.pjax.reload('#pjax-storage-images-widget', {});
                });
            },

            openUploader: function()
            {
                this.uploaderWindow.open();
            },

            openUploaderImage: function()
            {
                this.uploaderWindowImage.open();
            },

            _onDomReady: function()
            {},

            _onWindowReady: function()
            {}
        });

        sx.InputImagesWidget = new sx.classes.InputImagesWidget({
            'uploaderUrlImage' : '{$uploaderUrlImage}',
            'uploaderUrl' : '{$uploaderUrl}'
        });

    })(sx, sx.$, sx._);
JS
    );
?>

<? \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>