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
?>

<?php $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>

<?php echo $this->render('_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider
]); ?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'pjax' => $pjax,
    'adminController' => \Yii::$app->controller,
    'settingsData' =>
        [
            'order' => SORT_ASC,
            'orderBy' => "priority",
        ],
    'columns' =>
        [
            'name',
            'code',

            [
                'class' => \yii\grid\DataColumn::className(),
                'label' => \Yii::t('skeeks/cms', 'Number of sections'),
                'value' => function(\skeeks\cms\models\CmsTreeType $cmsTreeType) {
                    return $cmsTreeType->getCmsTrees()->count();
                }
            ],

            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute' => 'active'
            ],
            'priority',
        ]
]); ?>

<?php $pjax::end(); ?>
