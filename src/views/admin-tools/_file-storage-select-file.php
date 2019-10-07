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
<?php $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>

<?

$search = new \skeeks\cms\models\Search(\skeeks\cms\models\StorageFile::className());
$dataProvider = $search->getDataProvider();

$dataProvider->sort->defaultOrder = [
    'created_at' => SORT_DESC
];

?>
<?
$id = $pjax->id;

echo \skeeks\cms\widgets\StorageFileManager::widget([
    'clientOptions' =>
        [
            'completeUploadFile' => new \yii\web\JsExpression(<<<JS
                function(data)
                {
                    _.delay(function()
                    {
                        $.pjax.reload('#{$id}', {});
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

/*echo $this->render('@skeeks/cms/views/admin-storage-files/_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider
]); */?>

<?php $dataProvider->pagination->defaultPageSize = 10; ?>

<?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([

    'dataProvider' => $dataProvider,
    'filterModel' => $search->getLoadedModel(),

    'pjax' => $pjax,

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

<?php $pjax::end(); ?>
