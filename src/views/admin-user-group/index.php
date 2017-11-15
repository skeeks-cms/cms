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

<?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        [
            'class' => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
            'controller' => $controller
        ]

        /*['class' => \skeeks\cms\grid\ImageColumn::className()]*/,

        'groupname',
        'description',


        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        ['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
        ['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

    ],
]); ?>
