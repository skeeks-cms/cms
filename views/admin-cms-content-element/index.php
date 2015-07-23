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

/**
 * @var $content \skeeks\cms\models\CmsContent
 */
$content = \skeeks\cms\models\CmsContent::findOne('$content_id');


$autoColumns = [];
$models = $dataProvider->getModels();
$model = reset($models);

if (is_array($model) || is_object($model))
{
    foreach ($model as $name => $value) {
        $autoColumns[] = [
            'attribute' => $name,
            'visible' => false,
            'format' => 'raw',
            'class' => \yii\grid\DataColumn::className(),
            'value' => function($model, $key, $index) use ($name)
            {
                if (is_array($model->{$name}))
                {
                    return implode(",", $model->{$name});
                } else
                {
                    return $model->{$name};
                }
            },
        ];
    }

     /**
     * @var $model \skeeks\cms\models\CmsContentElement
     */
    if ($model->relatedPropertiesModel)
    {
        foreach ($model->relatedPropertiesModel->attributeValues() as $name => $value) {
            $autoColumns[] = [
                'attribute' => $name,
                'label' => \yii\helpers\ArrayHelper::getValue($model->relatedPropertiesModel->attributeLabels(), $name),
                'visible' => false,
                'format' => 'raw',
                'class' => \yii\grid\DataColumn::className(),
                'value' => function($model, $key, $index) use ($value)
                {
                    if (is_array($value))
                    {
                        return implode(",", $value);
                    } else
                    {
                        return $value;
                    }
                },
            ];
        }
    }


}
$userColumns = include_once "_columns.php";

$columns = \yii\helpers\ArrayHelper::merge($userColumns, $autoColumns);

?>

<?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([
    'dataProvider'  => $dataProvider,
    'filterModel'   => $searchModel,
    'autoColumns'   => false,
    'settingsData'  =>
    [
        'namespace' => \Yii::$app->controller->action->getUniqueId() . $content_id
    ],
    'columns' => \yii\helpers\ArrayHelper::merge(
        [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $controller
            ],
        ], $columns
    )
]); ?>

