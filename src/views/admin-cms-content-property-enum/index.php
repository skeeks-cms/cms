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
/* @var $model \skeeks\cms\models\CmsContentElement */

$sortAttr = $dataProvider->getSort()->attributes;
$query = $dataProvider->query;
$query->joinWith('property as p');
$query->select([\skeeks\cms\models\CmsContentPropertyEnum::tableName() . '.*', 'p.name as p_name']);

$dataProvider->getSort()->attributes = \yii\helpers\ArrayHelper::merge($sortAttr, [
    'p.name' => [
        'asc' => ['p.name' => SORT_ASC],
        'desc' => ['p.name' => SORT_DESC],
        'label' => \Yii::t('skeeks/cms', 'Property'),
        'default' => SORT_ASC
    ]
]);

?>
<?php $pjax = \yii\widgets\Pjax::begin(); ?>

<?php echo $this->render('_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider,
]); ?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    //'autoColumns'       => false,
    'pjax' => $pjax,
    'adminController' => $controller,
    'columns' =>
        [
            'id',
            [
                'label' => \Yii::t('skeeks/cms', 'Property'),
                'attribute' => 'p.name',
                'value' => function(\skeeks\cms\models\CmsContentPropertyEnum $cmsContentPropertyEnum) {
                    return $cmsContentPropertyEnum->property->name;
                }
            ],
            'value',

            'code',
            'priority',
        ]
]); ?>

<?php \yii\widgets\Pjax::end(); ?>