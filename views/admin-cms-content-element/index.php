<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */

$dataProvider->setSort(['defaultOrder' => ['published_at' => SORT_DESC]]);
if ($content_id = \Yii::$app->request->get('content_id'))
{
    $dataProvider->query->andWhere(['content_id' => $content_id]);
}

?>

<?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([
    'dataProvider'  => $dataProvider,
    'filterModel'   => $searchModel,
    'settingsData'  =>
    [
        'namespace' => \Yii::$app->controller->action->getUniqueId() . $content_id
    ],

    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        [
            'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
            'controller'    => $controller
        ],

        ['class' => \skeeks\cms\grid\ImageColumn::className()],

        'name',
        //'content_id',

        //['class' => \skeeks\cms\grid\LinkedToType::className()],
        //['class' => \skeeks\cms\grid\LinkedToModel::className()],

        //['class' => \skeeks\cms\grid\DescriptionShortColumn::className()],
        //['class' => \skeeks\cms\grid\DescriptionFullColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
        ['class' => \skeeks\cms\grid\PublishedAtColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
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

        /*[
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function($model)
            {

                return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->getPageUrl(), [
                    'target' => '_blank',
                    'title' => 'Посмотреть на сайте (Откроется в новом окне)',
                    'data-pjax' => '0',
                    'class' => 'btn btn-default btn-sm'
                ]);

            },
            'format' => 'raw'
        ],*/

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\CmsContentElement $model)
            {

                return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->absoluteUrl, [
                    'target' => '_blank',
                    'title' => 'Посмотреть на сайте (Откроется в новом окне)',
                    'data-pjax' => '0',
                    'class' => 'btn btn-default btn-sm'
                ]);

            },
            'format' => 'raw'
        ],
    ],
]); ?>

