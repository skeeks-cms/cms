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

        [
            'class' => \skeeks\cms\grid\ImageColumn::className(),
        ],

        'name',
        //'content_id',

        //['class' => \skeeks\cms\grid\LinkedToType::className()],
        //['class' => \skeeks\cms\grid\LinkedToModel::className()],

        //['class' => \skeeks\cms\grid\DescriptionShortColumn::className()],
        //['class' => \skeeks\cms\grid\DescriptionFullColumn::className()],

        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
        ['class' => \skeeks\cms\grid\PublishedAtColumn::className()],
        [
            'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
            'attribute' => "published_to"
        ],

        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
        //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\CmsContentElement $model)
            {

                return $model->cmsTree->name;

            },
            'format' => 'raw',
            'attribute' => 'tree_id'
        ],

        [
            'class'     => \yii\grid\DataColumn::className(),
            'value'     => function(\skeeks\cms\models\CmsContentElement $model)
            {
                $result = [];

                if ($model->cmsContentElementTrees)
                {
                    foreach ($model->cmsContentElementTrees as $contentElementTree)
                    {
                        $result[] = $contentElementTree->tree->name;
                    }
                }

                return implode(', ', $result);

            },
            'format' => 'raw',
            'label' => 'Дополнительные разделы',
        ],

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

