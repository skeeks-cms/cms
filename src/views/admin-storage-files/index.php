<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>


<?php $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>

<?php $pjaxId = $pjax->id; ?>
<?= \skeeks\cms\widgets\StorageFileManager::widget([
    'clientOptions' =>
        [
            'completeUploadFile' => new \yii\web\JsExpression(<<<JS
        function(data)
        {
            $.pjax.reload('#{$pjaxId}', {});
        }
JS
            )
        ],
]); ?>
<p></p>

<?php echo $this->render('_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider
]); ?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([

    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'adminController' => $controller,

    'pjax' => $pjax,

    'pjaxOptions' => [
        'id' => 'sx-storage-files'
    ],

    'columns' => [

        [
            'class' => \yii\grid\DataColumn::className(),
            'value' => function(\skeeks\cms\models\StorageFile $model) {
                if ($model->isImage()) {

                    $smallImage = \Yii::$app->imaging->getImagingUrl($model->src,
                        new \skeeks\cms\components\imaging\filters\Thumbnail());
                    return "<a href='" . $model->src . "' class='sx-fancybox' data-pjax='0' title='" . \Yii::t('skeeks/cms',
                            'Increase') . "'>
                            <img src='" . $smallImage . "' style='max-width: 50px;'/>
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
            'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->select(['mime_type'])->groupBy(['mime_type'])->all(),
                'mime_type', 'mime_type'),
        ],

        [
            'attribute' => 'extension',
            'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->select(['extension'])->groupBy(['extension'])->all(),
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
