<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 16.07.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider'      => $dataProvider,
    'filterModel'       => $searchModel,
    'adminController'   => $controller,
    'columns' => [
        'id',
        'name',
        'description',

        [
            'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
            'attribute'     => "last_exec_at"
        ],

        [
            'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
            'attribute'     => "next_exec_at"
        ],

        [
            'attribute'     => "agent_interval"
        ],

        [
            'class'         => \skeeks\cms\grid\BooleanColumn::className(),
            'attribute'     => "active"
        ],

    ],
]); ?>
