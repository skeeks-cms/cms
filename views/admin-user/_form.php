<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php  ?>

<?= $form->fieldSet('Общая ниформация')?>

    <?= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); ?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => 12])->hint('Уникальное имя пользователя. Используется для авторизации, для формирования ссылки на личный кабинет.'); ?>
    <?= $form->field($model, 'email')->textInput(); ?>

<?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
    'label'             => "Дополнительные email",
    'hint'              => "Можно привязать несколько email адресов к аккаунту.",
    'parentModel'       => $model,
    'relation'          => [
        'user_id' => 'id'
    ],
    'controllerRoute'   => 'cms/admin-user-email',
    'gridViewOptions'   => [
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],



            'value',
            'approved',

            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\user\UserEmail $model)
                {
                    if ($model->isMain())
                    {
                        return "да";
                    }

                    return '-';
                },
                'format' => 'html',
                'label' => 'Основной'
            ],


        ],
    ],
]); ?>

    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'city')->textInput(); ?>
    <?= $form->field($model, 'address')->textInput(); ?>
    <?= $form->field($model, 'info')->textarea(); ?>
    <?= $form->field($model, 'status_of_life')->textarea(); ?>
<?= $form->fieldSetEnd(); ?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
