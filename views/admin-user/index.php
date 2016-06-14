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

    <?php echo $this->render('_search', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider
    ]); ?>

    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider'  => $dataProvider,
        'filterModel'   => $searchModel,
        'adminController'   => $controller,
        'pjax'              => $pjax,
        'columns' => [

            [
                'class'             => \skeeks\cms\grid\ImageColumn2::className(),
                'attribute'         => 'image_id',
                'relationName'      => 'image',
            ],

            'username',
            'name',
            'email',
            'phone',


            ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
            [
                'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
                'attribute' => 'logged_at'
            ],

            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\User $model)
                {
                    $result = [];

                    if ($roles = \Yii::$app->authManager->getRolesByUser($model->id))
                    {
                        foreach ($roles as $role)
                        {
                            $result[] = $role->description . " ({$role->name})";
                        }
                    }

                    return implode(', ', $result);
                },
                'format'    => 'html',
                'label'     => \Yii::t('app','Roles'),
            ],

            [
                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute'     => "active",
            ],

            [
                'class'     => \yii\grid\DataColumn::className(),
                'label'     => "Смотреть",
                'value'     => function(\skeeks\cms\models\CmsUser $model)
                {

                    return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->getProfileUrl(), [
                        'target' => '_blank',
                        'title' => \Yii::t('app','Watch to site (opens new window)'),
                        'data-pjax' => '0',
                        'class' => 'btn btn-default btn-sm'
                    ]);

                },
                'format' => 'raw'
            ]

        ],
    ]); ?>

<? $pjax::end(); ?>
