<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $console \skeeks\cms\controllers\AdminUserController */

?>


<?php $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>
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

    <div class="row">
        <div class="col-md-5">
            <?= $form->field($model, 'username')->textInput(['maxlength' => 12])->hint(\Yii::t('app','The unique username. Used for authorization and to form links to personal cabinet.')); ?>
        </div>
        <div class="col-md-5">
            <?= $form->field($model, 'name')->textInput(); ?>
        </div>
    </div>



    <div class="row">
        <div class="col-md-5">
            <?= $form->field($model, 'email')->textInput(); ?>
            <? if (\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_USER_FULL_EDIT)) : ?>
                <?= $form->field($model, 'email_is_approved')->checkbox(\Yii::$app->formatter->booleanFormat); ?>
            <? endif; ?>
        </div>
        <div class="col-md-5">
            <?= $form->field($model, 'phone')->textInput([
                'placeholder' => '+7 903 722-28-73'
            ]); ?>
            <? if (\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_USER_FULL_EDIT)) : ?>
                <?= $form->field($model, 'phone_is_approved')->checkbox(\Yii::$app->formatter->booleanFormat); ?>
            <? endif; ?>
        </div>
    </div>


    <? if ($model->relatedProperties) : ?>
        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('app', 'Additional properties')
        ]); ?>
        <? if ($properties = $model->relatedProperties) : ?>
            <? foreach ($properties as $property) : ?>
                <?= $property->renderActiveForm($form, $model)?>
            <? endforeach; ?>
        <? endif; ?>

    <? else : ?>
        <?/*= \Yii::t('app','Additional properties are not set')*/?>
    <? endif; ?>


<?= $form->fieldSetEnd(); ?>

<? if (\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_USER_FULL_EDIT)) : ?>
    <?= $form->fieldSet(\Yii::t('app','Groups'))?>

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
<? endif; ?>

<?= $form->fieldSet(\Yii::t('app','Password')); ?>

    <?= $form->field($passwordChange, 'new_password')->passwordInput() ?>
    <?= $form->field($passwordChange, 'new_password_confirm')->passwordInput() ?>

<?= $form->fieldSetEnd(); ?>

<?/*= $form->fieldSet(\Yii::t('app','Additionally'))*/?><!--
    <?/*= $form->field($model, 'city')->textInput(); */?>
    <?/*= $form->field($model, 'address')->textInput(); */?>
    <?/*= $form->field($model, 'info')->textarea(); */?>
    <?/*= $form->field($model, 'status_of_life')->textarea(); */?>
--><?/*= $form->fieldSetEnd(); */?>

<? if (!$model->isNewRecord) : ?>
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
<? endif; ?>

<?= $form->buttonsStandart($model); ?>
<?php \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>
