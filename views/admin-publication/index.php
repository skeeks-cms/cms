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

/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */

$dataProvider->setSort(['defaultOrder' => ['published_at' => SORT_DESC]])
//$dataProvider->query->orderBy('published_at DESC');
?>


<?= GridView::widget([
    'dataProvider'  => $dataProvider,
    'filterModel'   => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        [
            'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
            'controller'    => $controller
        ],

        ['class' => \skeeks\cms\grid\ImageColumn::className()],

        'name',

        //['class' => \skeeks\cms\grid\LinkedToType::className()],
        //['class' => \skeeks\cms\grid\LinkedToModel::className()],

        //['class' => \skeeks\cms\grid\DescriptionShortColumn::className()],
        //['class' => \skeeks\cms\grid\DescriptionFullColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
        ['class' => \skeeks\cms\grid\PublishedAtColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
        ['class' => \skeeks\cms\grid\StatusColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],


        /*[
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function($model)
            {
                $class = 'label-default';
                if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_ACTIVE)
                {
                    $class = 'label-success';
                } else if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_DELETED)
                {
                    $class = 'label-danger';
                } else if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_ONMODER)
                {
                    $class = 'label-warning';
                }
                return '<span class="label ' . $class . '">' . $model->getStatusText() . '</span>';
            },
            'format' => 'html'
        ],*/

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function($model)
            {
                return \yii\helpers\Html::a('<small>Смотреть</small>', $model->getPageUrl(), [
                    'target' => '_blank',
                    'title' => 'Откроется в новом окне'
                ]);
            },
            'format' => 'html'
        ],
    ],
]); ?>


