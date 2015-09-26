<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php  ?>

<?= $form->fieldSet('Общая информация')?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>

    <?= $form->field($model, 'gender')->radioList([
        'men'   => 'Муж',
        'women' => 'Жен',
    ]); ?>

    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
    ); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => 12])->hint('Уникальное имя пользователя. Используется для авторизации, для формирования ссылки на личный кабинет.'); ?>
    <?= $form->field($model, 'name')->textInput(); ?>

    <?= $form->field($model, 'email')->textInput(); ?>

    <? if ($model->email) : ?>

            <? if ($model->cmsUserEmail->approved !== \skeeks\cms\components\Cms::BOOL_Y) : ?>

                <? \yii\bootstrap\Alert::begin([
                    'options' => [
                      'class' => 'alert-warning',
                  ],
                ]); ?>

                    Email не подтвержден
                <? \yii\bootstrap\Alert::end(); ?>

            <? else : ?>

            <? endif; ?>

    <? endif; ?>

    <?= $form->field($model, 'phone')->textInput([
        'placeholder' => '+7 903 722-28-73'
    ])->hint('Формат ввода телефона: +7 903 722-28-73'); ?>

<?/*= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
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
            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute' => 'approved',
            ],

            [
                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute'     => "def"
            ],
        ],
    ],
]); */?>


<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Дополнительно')?>
    <?= $form->field($model, 'city')->textInput(); ?>
    <?= $form->field($model, 'address')->textInput(); ?>
    <?= $form->field($model, 'info')->textarea(); ?>
    <?= $form->field($model, 'status_of_life')->textarea(); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Социальные профили')?>


    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label'             => "Социальные профили",
        'hint'              => "",
        'parentModel'       => $model,
        'relation'          => [
            'user_id' => 'id'
        ],
        'controllerRoute'   => 'cms/admin-user-auth-client',
        'gridViewOptions'   => [
            'columns' => [
                'displayName'
            ],
        ],
    ]); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
