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

    <? $form = \skeeks\cms\modules\admin\widgets\filters\AdminFiltersForm::begin([
        'action' => '/' . \Yii::$app->request->pathInfo,
    ]); ?>

        <?= $form->field($searchModel, 'q')->setVisible(); ?>

        <?= $form->field($searchModel, 'id'); ?>

        <?= $form->field($searchModel, 'active')->listBox(\yii\helpers\ArrayHelper::merge([
            '' => ' - '
        ], \Yii::$app->cms->booleanFormat()), [
            'size' => 1
        ]); ?>

        <?= $form->field($searchModel, 'has_image')->checkbox(\Yii::$app->formatter->booleanFormat, false); ?>

        <?= $form->field($searchModel, 'name') ?>
        <?= $form->field($searchModel, 'username') ?>
        <?= $form->field($searchModel, 'email') ?>
        <?= $form->field($searchModel, 'phone') ?>

        <?= $form->field($searchModel, 'created_at_from')->widget(
            \kartik\datetime\DateTimePicker::className()
        ); ?>
        <?= $form->field($searchModel, 'created_at_to')->widget(
            \kartik\datetime\DateTimePicker::className()
        ); ?>

        <?= $form->field($searchModel, 'updated_at_from')->widget(
            \kartik\datetime\DateTimePicker::className()
        ); ?>
        <?= $form->field($searchModel, 'updated_at_to')->widget(
            \kartik\datetime\DateTimePicker::className()
        ); ?>

    <? $form::end(); ?>

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

        ],
    ]); ?>

<? $pjax::end(); ?>
