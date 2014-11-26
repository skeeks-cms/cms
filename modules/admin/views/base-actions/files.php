
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
use yii\grid\GridView;

/* @var $model \skeeks\cms\models\Publication */
/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */
$groups = $model->getFilesGroups();

?>
<? if ($groupsObjects = $groups->getComponents()) : ?>
<div class="sx-select-type">
    <label>Группа файлов:</label>
    <?= \skeeks\widget\chosen\Chosen::widget([
            'name' => 'type',
            'items' => \yii\helpers\ArrayHelper::map(
                 $groupsObjects,
                 "id",
                 "name"
            ),
        ]);
    ?>
    <hr />
</div>
<? endif; ?>

<div class="sx-upload-sources">
    <a href="#" class="btn btn-primary btn-sm">Загрузить с компьютера</a>
    <a href="#" class="btn btn-primary btn-sm">Загрузить по ссылке http://</a>
    <a href="#" class="btn btn-primary btn-sm">Добавить из файлового менеджера</a>
    <hr />
</div>
<?= GridView::widget([

    'dataProvider'  => $dataProvider,
    'filterModel'   => $searchModel,

    'columns' => [

        ['class' => 'yii\grid\SerialColumn'],

        [
            'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
            'controller'    => $controller
        ],

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\StorageFile $model)
            {
                if ($model->isImage())
                {
                    return \yii\helpers\Html::img($model->src, [
                        'width' => '80'
                    ]);
                }

                return \yii\helpers\Html::tag('span', $model->extension, ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
            },
            'format' => 'html'
        ],

        'name',

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\StorageFile $model)
            {
                return \yii\helpers\Html::tag('pre', $model->src);
            },

            'format' => 'html',
            'attribute' => 'src'
        ],

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\StorageFile $model)
            {
                $model->cluster_id;
                $cluster = \Yii::$app->storage->getCluster($model->cluster_id);
                return $cluster->name;
            },

            'format' => 'html',
        ],

        //'name_to_save',
        'mime_type',
        'extension',

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
