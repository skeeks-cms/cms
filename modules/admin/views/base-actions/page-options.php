<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */


$dataProvider = new \yii\data\ArrayDataProvider([
    'allModels' => \Yii::$app->pageOptions->getComponents(),
    'sort' => [
        'attributes' => ['name'],
    ],
    'pagination' => [
        'pageSize' => 100,
    ],
]);
?>

<?=
    \yii\grid\GridView::widget([
    'dataProvider'  => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        'id',
        'name',

        [
            'class'         => \yii\grid\DataColumn::className(),
            'attribute'     => 'id',
            'label'         => 'Значение',
            'format'         => 'html',
            'value'     =>     function(\skeeks\cms\models\PageOption $pageOption)
            {
                return '';
            }

        ],

        [
            'class'         => \yii\grid\DataColumn::className(),
            'attribute'     => 'id',
            'label'         => '',
            'format'         => 'html',
            'value' => function(\skeeks\cms\models\PageOption $model)
            {
                return Html::a('Настроить', '#' . $model->id, ['class' => 'btn btn-primary btn-xs']);
            }
        ],


        /*[
            'class'         => \yii\grid\DataColumn::className(),
            'attribute'     => 'schemaName',
            'label'         => 'Количество внешних ключей',
            'value' => function(yii\db\TableSchema $model)
            {
                return count($model->foreignKeys);
            }
        ],*/
    ],
]); ?>
