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

<?= $form->fieldSet(\Yii::t('app','General information'))?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>

    <?= $form->field($model, 'gender')->radioList([
        'men'   => \Yii::t('app','Male'),
        'women' => \Yii::t('app','Female'),
    ]); ?>

    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
    ); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => 12])->hint(\Yii::t('app','The unique username. Used for authorization and to form links to personal cabinet.')); ?>
    <?= $form->field($model, 'name')->textInput(); ?>

    <?= $form->field($model, 'email')->textInput(); ?>

    <? if ($model->email) : ?>

            <? if ($model->cmsUserEmail->approved !== \skeeks\cms\components\Cms::BOOL_Y) : ?>

                <? \yii\bootstrap\Alert::begin([
                    'options' => [
                      'class' => 'alert-warning',
                  ],
                ]); ?>

                    <?=\Yii::t('app','{email} not confirmed',['email' => 'Email'])?>
                <? \yii\bootstrap\Alert::end(); ?>

            <? else : ?>

            <? endif; ?>

    <? endif; ?>

    <?= $form->field($model, 'phone')->textInput([
        'placeholder' => '+7 903 722-28-73'
    ])->hint(\Yii::t('app','Input format phone').': +7 903 722-28-73'); ?>

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

<?= $form->fieldSet(\Yii::t('app','Группы'))?>


    <? $this->registerCss(<<<CSS
.sx-checkbox label
{
    width: 100%;
}
CSS
)?>
    <?= $form->field($model, 'roleNames')->checkboxList(
        \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description'), [
            'class' => 'sx-checkbox'
        ]
    ); ?>

<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('app','Additionally'))?>
    <?= $form->field($model, 'city')->textInput(); ?>
    <?= $form->field($model, 'address')->textInput(); ?>
    <?= $form->field($model, 'info')->textarea(); ?>
    <?= $form->field($model, 'status_of_life')->textarea(); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('app','Social profiles'))?>


    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label'             => \Yii::t('app',"Social profiles"),
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
