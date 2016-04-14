<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 01.09.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \skeeks\cms\models\CmsContentElement */
$dataProvider->query->with('messages');
?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider'          => $dataProvider,
    'filterModel'           => $searchModel,
    'adminController'       => $controller,

    'columns'               =>
    [
        /*[
            'class' => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
        ],*/

        [
            'attribute' => 'id',
            'value' => function ($model, $index, $dataColumn) {
                return $model->id;
            },
            'filter' => false
        ],

        [
            'attribute' => 'message',
            'format' => 'raw',
            'value' => function ($model, $index, $widget) {
                return $model->message;
            }
        ],
        [
            'attribute' => 'category',
            'value' => function ($model, $index, $dataColumn) {
                return $model->category;
            },
            'filter' => \yii\helpers\ArrayHelper::map($searchModel::getCategories(), 'category', 'category')
        ],
        [
            'attribute' => 'status',
            'value' => function ($model, $index, $widget) {
                /** @var \skeeks\cms\models\SourceMessage $model */
                return $model->isTranslated() ? 'Translated' : 'Not translated';
            },
            'filter' => $searchModel->getStatus()
        ]
    ]
]); ?><!--

-->