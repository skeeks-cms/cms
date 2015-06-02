<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
/* @var $this yii\web\View */

use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
?>

<?
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SelectFile = sx.classes.Component.extend({
        _init: function()
        {},

        _onDomReady: function()
        {
            this.message = window.location.search.replace(/^.*C(\d+).*$/, "$1");
            this.name = window.location.search.replace(/^.*CKEditorFuncNum=(\d+).*$/, "$1");
            this.baseUrl = window.location.search.replace(/^.*langCode=([a-z]{2}).*$/, "$1");
            this.tagname = "";
            if (this.name == this.message)
            {
                this.tagname = window.location.search.replace(/^.*BaseBackId=(\S+).*$/, "$1");
            }
        },

        submit: function(file)
        {
            window.opener.CKEDITOR.tools.callFunction(this.name, file);
            window.close();
            return this;
        },
        _onWindowReady: function()
        {}
    });

    sx.SelectFile = new sx.classes.SelectFile();

})(sx, sx.$, sx._);
JS
);
?>

<? $form = ActiveForm::begin([
    'usePjax' => false
]); ?>

<? if ($model) : ?>
    <?= $form->fieldSet('Файлы привязанные к записи'); ?>
        <?= \skeeks\cms\modules\admin\widgets\StorageFilesForModel::widget([
            'model' => $model,
            'gridColumns' =>
            [

                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        return \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> Выбрать файл', $model->src, [
                            'class' => 'btn btn-primary',
                            'onclick' => 'sx.SelectFile.submit("' . $model->src . '"); return false;'
                        ]);
                    },
                    'format' => 'raw'
                ],

                [
                    'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                    'controller'    => \Yii::$app->createController('cms/admin-storage-files')[0],
                    'isOpenNewWindow'    => true
                ],

                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        if ($model->isImage())
                        {
                            \Yii::$app->view->registerCss(<<<CSS
        .sx-img-small {
            max-height: 50px;
        }
CSS
    );

                            $smallImage = \Yii::$app->imaging->getImagingUrl($model->src, new \skeeks\cms\components\imaging\filters\Thumbnail());

                            return "<a href='{$model->src}' class='sx-fancybox'>" . \yii\helpers\Html::img($smallImage, [
                                'width' => '50',
                                'class' => 'sx-img-small'
                            ]) . '</a>';
                        }

                        return \yii\helpers\Html::tag('span', $model->extension, ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
                    },
                    'format' => 'html'
                ],



                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $file)
                    {
                        if ($groups = $file->getFilesGroups())
                        {
                            $result = \yii\helpers\ArrayHelper::map($groups, "id", "name");

                            if ($result)
                            {
                                foreach ($result as $key => $name)
                                {
                                    $result[$key] = '<span class="label label-info"><i class="glyphicon glyphicon-tag"></i> ' . $name . '</span>';
                                }
                            }

                            return implode(' ', $result);
                        }
                    },
                    'format' => 'html',
                    'label' => 'Метки'
                ],

                'name',

                [
                    'attribute' => 'mime_type',
                    'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'mime_type', 'mime_type'),
                ],

                [
                    'attribute' => 'extension',
                    'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'extension', 'extension'),
                ],


                [
                    'class' => \skeeks\cms\grid\FileSizeColumnData::className(),
                    'attribute' => 'size'
                ],
            ]
        ]); ?>
    <?= $form->fieldSetEnd(); ?>
<? endif; ?>

<?= $form->fieldSet('Файловый менеджер'); ?>
    <?
        echo \mihaildev\elfinder\ElFinder::widget([
            'language'         => 'ru',
            'controller'       => 'cms/elfinder-full', // вставляем название контроллера, по умолчанию равен elfinder
            //'filter'           => 'image',    // фильтр файлов, можно задать массив фильтров https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#wiki-onlyMimes
            'callbackFunction' => new \yii\web\JsExpression('function(file, id){
                sx.SelectFile.submit(file.url);
            }'), // id - id виджета
            'frameOptions' => [
                'style' => 'width: 100%; height: 800px;'
            ]
        ]);
    ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Файловое хранилище'); ?>


        <?

            $search = new \skeeks\cms\models\Search(\skeeks\cms\models\StorageFile::className());
            $dataProvider = $search->getDataProvider();

            $dataProvider->sort->defaultOrder = [
                'created_at' => SORT_DESC
            ];

        ?>
        <?= \skeeks\cms\widgets\StorageFileManager::widget([
            'clientOptions' =>
            [
                'completeUploadFile' => new \yii\web\JsExpression(<<<JS
                function(data)
                {
                    _.delay(function()
                    {
                        $.pjax.reload('#sx-storage-files', {});
                    }, 500)

                }
JS
        )
            ],
        ]); ?>
        <p></p>
        <? $dataProvider->pagination->defaultPageSize = 10; ?>
        <?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([

            'dataProvider'  => $dataProvider,
            'filterModel'   => $search->getLoadedModel(),

            'pjaxOptions' => [
                'id' => 'sx-storage-files'
            ],

            'columns' => [

                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        return \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> Выбрать файл', $model->src, [
                            'class' => 'btn btn-primary',
                            'onclick' => 'sx.SelectFile.submit("' . $model->src . '"); return false;'
                        ]);
                    },
                    'format' => 'raw'
                ],

                [
                    'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                    'controller'    => \Yii::$app->createController('cms/admin-storage-files')[0],
                    'isOpenNewWindow'    => true
                ],

                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        if ($model->isImage())
                        {

                            $smallImage = \Yii::$app->imaging->getImagingUrl($model->src, new \skeeks\cms\components\imaging\filters\Thumbnail());
                            return "<a href='" . $model->src . "' class='sx-fancybox' title='Увеличить'>
                                    <img src='" . $smallImage . "' />
                                </a>";
                        }

                        return \yii\helpers\Html::tag('span', $model->extension, ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
                    },
                    'format' => 'html'
                ],

                'name',

                /*[
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        return \yii\helpers\Html::tag('pre', $model->src);
                    },

                    'format' => 'html',
                    'attribute' => 'src'
                ],*/

                [
                    'class'     => \yii\grid\DataColumn::className(),
                    'value'     => function(\skeeks\cms\models\StorageFile $model)
                    {
                        $model->cluster_id;
                        $cluster = \Yii::$app->storage->getCluster($model->cluster_id);
                        return $cluster->name;
                    },

                    'filter' => \yii\helpers\ArrayHelper::map(\Yii::$app->storage->getClusters(), 'id', 'name'),
                    'format' => 'html',
                    'attribute' => 'cluster_id',
                ],

                [
                    'attribute' => 'mime_type',
                    'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'mime_type', 'mime_type'),
                ],

                [
                    'attribute' => 'extension',
                    'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'extension', 'extension'),
                ],

                [
                    'class' => \skeeks\cms\grid\FileSizeColumnData::className(),
                    'attribute' => 'size'
                ],
                [
                    'class' => \skeeks\cms\grid\LinkedToType::className(),
                    'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'linked_to_model', 'linked_to_model'),
                ],
                ['class' => \skeeks\cms\grid\LinkedToModel::className()],

                ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
                //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],

                ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
                //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

            ],

        ]); ?>

<?= $form->fieldSetEnd(); ?>


<hr />
<?= \yii\helpers\Html::a("<i class='glyphicon glyphicon-question-sign'></i>", "#", [
    'class' => 'btn btn-default',
    'onclick' => "sx.dialog({'title' : 'Справка', 'content' : '#sx-help'}); return false;"
]);?>
<div style="display: none;" id="sx-help">
    Справка в процессе написания...
</div>

<? ActiveForm::end(); ?>
