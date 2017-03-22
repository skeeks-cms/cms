<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>
<? $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>

    <?php /*echo $this->render('_search', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider
    ]); */?>

    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider'  => $dataProvider,
        'filterModel'   => $searchModel,
        'adminController'   => $controller,
        'pjax'              => $pjax,
        'columns' => [
            'name',
            'code',
            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'falseValue' => \skeeks\cms\components\Cms::BOOL_N,
                'trueValue' => \skeeks\cms\components\Cms::BOOL_Y,
                'attribute' => 'active'
            ],
        ],
    ]); ?>

<? $pjax::end(); ?>
