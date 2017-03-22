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
$dataProvider->setSort(['defaultOrder' => ['priority' => SORT_ASC]]);
?>
<? $pjax = \yii\widgets\Pjax::begin(); ?>

    <?php echo $this->render('_search', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
    ]); ?>

    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider'      => $dataProvider,
        'filterModel'       => $searchModel,
        'autoColumns'       => false,
        'pjax'              => $pjax,
        'adminController'   => $controller,
        'columns' =>
        [
            [
                'class' => \skeeks\cms\grid\ImageColumn2::className(),
            ],
            'name',
            'code',
            [
                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute'     => "active"
            ],
            'priority',
        ]
    ]); ?>

<? \yii\widgets\Pjax::end(); ?>