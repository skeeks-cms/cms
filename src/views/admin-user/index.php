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

    <?
        $user = new \skeeks\cms\models\CmsUser();
        $searchRelatedPropertiesModel = new \skeeks\cms\models\searchs\SearchRelatedPropertiesModel();
        $searchRelatedPropertiesModel->propertyElementClassName = \skeeks\cms\models\CmsUserProperty::className();
        $searchRelatedPropertiesModel->initProperties( $user->relatedProperties );
        $searchRelatedPropertiesModel->load(\Yii::$app->request->get());
        if ($dataProvider)
        {
            $searchRelatedPropertiesModel->search($dataProvider, $user->tableName());
        }

        if ($user->relatedPropertiesModel)
        {
            $autoColumns = \skeeks\cms\modules\admin\widgets\GridViewStandart::getColumnsByRelatedPropertiesModel($user->relatedPropertiesModel, $searchRelatedPropertiesModel);
        }
    ?>
    <?php echo $this->render('_search', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider
    ]); ?>

    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider'  => $dataProvider,
        'filterModel'   => $searchModel,
        'adminController'   => $controller,
        'pjax'              => $pjax,
        'columns' => \yii\helpers\ArrayHelper::merge([

            [
                'class'             => \skeeks\cms\grid\ImageColumn2::className(),
                'attribute'         => 'image_id',
                'relationName'      => 'image',
            ],

            'username',
            'name',
            'email',
            [
                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute'     => "email_is_approved",
                'trueValue'     => 1,
                'falseValue'     => 0,
            ],

            'phone',

            [
                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute'     => "phone_is_approved",
                'trueValue'     => 1,
                'falseValue'     => 0,
            ],


            ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
            [
                'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
                'attribute' => 'logged_at'
            ],

            [
                'class'     => \yii\grid\DataColumn::className(),
                'filter'     => \yii\helpers\Html::activeListBox($searchModel, 'role',
                    \yii\helpers\ArrayHelper::merge([
                        '' => ' - '
                    ], \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description'))
                    , [
                    'size' => 1,
                    'class' => 'form-control'
                ]),
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
                'label'     => \Yii::t('skeeks/cms','Roles'),
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
                        'title' => \Yii::t('skeeks/cms','Watch to site (opens new window)'),
                        'data-pjax' => '0',
                        'class' => 'btn btn-default btn-sm'
                    ]);

                },
                'format' => 'raw'
            ],


            [
                'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
                'attribute' => 'last_activity_at',
                'visible' => false,
            ],
        ], $autoColumns),
    ]); ?>

<? $pjax::end(); ?>
