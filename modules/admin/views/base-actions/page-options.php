<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */
/* @var $pageOption \skeeks\cms\models\PageOption */


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
        'description',

        [
            'class'         => \yii\grid\DataColumn::className(),
            'attribute'     => 'id',
            'label'         => 'Значение',
            'format'         => 'html',
            'value'     =>     function(\skeeks\cms\models\PageOption $pageOption)
            {
                $params = \Yii::$app->request->getQueryParams();
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
                $params = \Yii::$app->request->getQueryParams();
                $params['page-option'] = $model->id;

                return Html::a('Настроить', \yii\helpers\Url::to($params), ['class' => 'btn btn-primary btn-xs']);
            }
        ],
    ],
]); ?>


<? if ($pageOption) : ?>
    <hr />
    <h2>Настройка свойства — <?= $pageOption->name; ?></h2>
    <?= $pageOption->getValue()->renderForm([
        'modelEntity' => $model
    ]); ?>
<? endif;?>