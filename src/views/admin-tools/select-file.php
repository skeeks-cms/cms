<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
/* @var $this yii\web\View */

/* @var $model \yii\db\ActiveRecord */

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
            this.GetParams              = sx.helpers.Request.getParams();
        },

        submit: function(file)
        {
            if (this.GetParams['CKEditorFuncNum'])
            {
                if (window.opener)
                {
                    if (window.opener.CKEDITOR)
                    {
                        window.opener.CKEDITOR.tools.callFunction(this.GetParams['CKEditorFuncNum'], file);
                        window.close();
                        return this;
                    }
                }
            }

            if (this.GetParams['callbackEvent'])
            {
                if (window.opener)
                {
                    if (window.opener.sx)
                    {
                        window.opener.sx.EventManager.trigger(this.GetParams['callbackEvent'], {
                            'file' : file
                        });

                        window.close();
                        return this;
                    }
                }
            }

            sx.alert(file);
            return this;
        }
    });

    sx.SelectFile = new sx.classes.SelectFile();

})(sx, sx.$, sx._);
JS
);
?>

<?php $form = ActiveForm::begin([
    'usePjax' => false
]); ?>
<!--
<?php /* if ($model) : */ ?>
    <?php /*= $form->fieldSet(\Yii::t('skeeks/cms','Files attached to records')); */ ?>

        <?php /* \yii\bootstrap\Alert::begin(['options' => [
          'class' => 'alert-info',
        ]]); */ ?>
            <?php /*=\Yii::t('skeeks/cms','At this tab displays all the files and images that are tied to the current element.')*/ ?>
        <?php /* \yii\bootstrap\Alert::end(); */ ?>


        <?php /* foreach($model->getBehaviors() as $behavior) : */ ?>
            <?php /* \yii\bootstrap\ActiveForm::begin(); */ ?>
            <?php /* if ($behavior instanceof \skeeks\cms\models\behaviors\HasStorageFile)  : */ ?>
                <?php /* foreach($behavior->fields as $fieldName) : */ ?>

                    <?php /*= $form->field($model, $fieldName)->widget(
                        \skeeks\cms\widgets\formInputs\StorageImage::className(),
                        [
                            'viewItemTemplate' => '@skeeks/cms/views/admin-tools/one-file'
                        ]
                    ); */ ?>

                <?php /* endforeach; */ ?>

            <?php /* elseif ($behavior instanceof \skeeks\cms\models\behaviors\HasStorageFileMulti) : */ ?>
                <?php /* foreach($behavior->relations as $relationName) : */ ?>


                <?php /*= $form->field($model, $relationName['relation'])->widget(
                    \skeeks\cms\widgets\formInputs\ModelStorageFiles::className(),
                    [
                        'viewItemTemplate' => '@skeeks/cms/views/admin-tools/one-file'
                    ]
                ); */ ?>

                <?php /* endforeach; */ ?>
            <?php /* endif; */ ?>
            <?php /* \yii\bootstrap\ActiveForm::end(); */ ?>
        <?php /* endforeach; */ ?>
    <?php /*= $form->fieldSetEnd(); */ ?>
--><?php /* endif; */ ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms', 'File storage')); ?>


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

<?php
$searchModel = new \skeeks\cms\models\Search(\skeeks\cms\models\CmsStorageFile::class);
$dataProvider   = $search->search(\Yii::$app->request->queryParams);
$searchModel    = $search->loadedModel;

echo $this->render('@skeeks/cms/views/admin-storage-files/_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider
]); ?>

<?php $dataProvider->pagination->defaultPageSize = 10; ?>


<?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([

    'dataProvider' => $dataProvider,
    'filterModel' => $search->getLoadedModel(),

    'pjaxOptions' => [
        'id' => 'sx-storage-files'
    ],

    'columns' => [

        [
            'class' => \yii\grid\DataColumn::className(),
            'value' => function(\skeeks\cms\models\StorageFile $model) {
                return \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> ' . \Yii::t('skeeks/cms',
                        'Choose file'), $model->src, [
                    'class' => 'btn btn-primary',
                    'onclick' => 'sx.SelectFile.submit("' . $model->src . '"); return false;',
                    'data-pjax' => 0
                ]);
            },
            'format' => 'raw'
        ],

        [
            'class' => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
            'controller' => \Yii::$app->createController('cms/admin-storage-files')[0],
            'isOpenNewWindow' => true
        ],

        [
            'class' => \yii\grid\DataColumn::className(),
            'value' => function(\skeeks\cms\models\StorageFile $model) {
                if ($model->isImage()) {

                    $smallImage = \Yii::$app->imaging->getImagingUrl($model->src,
                        new \skeeks\cms\components\imaging\filters\Thumbnail());
                    return "<a href='" . $model->src . "' data-pjax='0' class='sx-fancybox' title='" . \Yii::t('skeeks/cms',
                            'Increase') . "'>
                                    <img src='" . $smallImage . "' />
                                </a>";
                }

                return \yii\helpers\Html::tag('span', $model->extension,
                    ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
            },
            'format' => 'raw'
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
            'class' => \yii\grid\DataColumn::className(),
            'value' => function(\skeeks\cms\models\StorageFile $model) {
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
            'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->groupBy(['mime_type'])->all(),
                'mime_type', 'mime_type'),
        ],

        [
            'attribute' => 'extension',
            'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->groupBy(['extension'])->all(),
                'extension', 'extension'),
        ],

        [
            'class' => \skeeks\cms\grid\FileSizeColumnData::className(),
            'attribute' => 'size'
        ],


        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

    ],

]); ?>

<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms', 'File manager')); ?>
<?
echo \mihaildev\elfinder\ElFinder::widget([
    'controller' => 'cms/elfinder-full',
    // вставляем название контроллера, по умолчанию равен elfinder
    //'filter'           => 'image',    // фильтр файлов, можно задать массив фильтров https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#wiki-onlyMimes
    'callbackFunction' => new \yii\web\JsExpression('function(file, id){
                sx.SelectFile.submit(file.url);
            }'),
    // id - id виджета
    'frameOptions' => [
        'style' => 'width: 100%; height: 800px;'
    ]
]);
?>
<?= $form->fieldSetEnd(); ?>

<hr/>
<?= \yii\helpers\Html::a("<i class='glyphicon glyphicon-question-sign'></i>", "#", [
    'class' => 'btn btn-default',
    'onclick' => "sx.dialog({'title' : '" . \Yii::t('skeeks/cms', 'Help') . "', 'content' : '#sx-help'}); return false;"
]); ?>
<div style="display: none;" id="sx-help">
    <?php \Yii::t('skeeks/cms', 'Help in the process of writing ...') ?>
</div>

<?php ActiveForm::end(); ?>
