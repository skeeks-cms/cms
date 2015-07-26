<?php
/*
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;*/

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel skeeks\cms\models\searchs\AuthItem */

/*$this->title = Yii::t('app', 'Permission');
$this->params['breadcrumbs'][] = $this->title;*/
?>
<!--<div class="role-index">

    <h1><?/*= Html::encode($this->title) */?></h1>

    <p>
        <?/*= Html::a(Yii::t('app', 'Create Permission'), ['create'], ['class' => 'btn btn-success']) */?>
    </p>

    <?php
/*    Pjax::begin([
        'enablePushState'=>false,
    ]);
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'name',
                'label' => Yii::t('app', 'Name'),
            ],
            [
                'attribute' => 'description',
                'label' => Yii::t('app', 'Description'),
            ],

            ['class' => 'yii\grid\ActionColumn',],
        ],
    ]);
    Pjax::end();
    */?>

</div>
-->


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
use skeeks\cms\modules\admin\widgets\GridView;
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider'  => $dataProvider,
    'filterModel'   => $searchModel,
    'adminController' => $controller,
    'settingsData' =>
    [
        'orderBy' => ''
    ],
    'columns' => [


        'name',
        'description',
        [
            'attribute' => 'ruleName',
            /*'filter'    => \yii\helpers\ArrayHelper::map(
                \Yii::$app->cms->findUser()->all(),
                'id',
                'name'
            )*/
        ]

        //['class' => \skeeks\cms\grid\LinkedToType::className()],
        //['class' => \skeeks\cms\grid\LinkedToModel::className()],

        //['class' => \skeeks\cms\grid\DescriptionShortColumn::className()],
        //['class' => \skeeks\cms\grid\DescriptionFullColumn::className()],

        //['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\PublishedAtColumn::className()],

        //['class' => \skeeks\cms\grid\CreatedByColumn::className()],
        //['class' => \skeeks\cms\grid\StatusColumn::className()],
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

    ],
]); ?>
