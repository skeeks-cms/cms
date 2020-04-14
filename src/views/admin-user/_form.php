<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $console \skeeks\cms\controllers\AdminUserController */

/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
$controller = $this->context;
$action = $controller->action;

?>



<?php $form = $action->beginActiveForm(); ?>
<?php echo $form->errorSummary([$model, $relatedModel]); ?>


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'General information')) ?>

<?php if (\Yii::$app->user->can('cms/admin-user/update-advanced')) : ?>
<?= $form->field($model, 'active')->listBox(\Yii::$app->cms->booleanFormat(), ['size' => 1]); ?>
<?php endif; ?>
<?= $form->field($model, 'gender')->radioList([
    'men' => \Yii::t('skeeks/cms', 'Male'),
    'women' => \Yii::t('skeeks/cms', 'Female'),
]); ?>

<?= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => false
    ]
); ?>

<?= $form->field($model, 'username')->textInput(['maxlength' => 25])->hint(\Yii::t('skeeks/cms',
    'The unique username. Used for authorization and to form links to personal cabinet.')); ?>

<?= $form->field($model, 'first_name')->textInput(); ?>
<?= $form->field($model, 'last_name')->textInput(); ?>
<?= $form->field($model, 'patronymic')->textInput(); ?>

<?= $form->field($model, 'email')->textInput(); ?>
        <?php if (\Yii::$app->user->can('cms/admin-user/update-advanced')) : ?>
            <?= $form->field($model, 'email_is_approved')->checkbox(\Yii::$app->formatter->booleanFormat); ?>
        <?php endif; ?>


<?
        \skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
        $id = \yii\helpers\Html::getInputId($model, 'phone');
        $this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");
JS
        );
        ?>

        <?= $form->field($model, 'phone')->textInput([
            'placeholder' => '+7 903 722-28-73'
        ]); ?>
        <?php if (\Yii::$app->user->can('cms/admin-user/update-advanced')) : ?>
            <?= $form->field($model, 'phone_is_approved')->checkbox(\Yii::$app->formatter->booleanFormat); ?>
        <?php endif; ?>



<?php if ($model->relatedProperties) : ?>
    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additional properties')
    ]); ?>
    <?php if ($properties = $model->relatedProperties) : ?>
        <?php foreach ($properties as $property) : ?>
            <?= $property->renderActiveForm($form, $model) ?>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else
    : ?>
    <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<?php endif;
?>


<? $fieldSet::end(); ?>

<?php if (\Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $model]) && $model->id != \Yii::$app->user->id) : ?>
    <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Groups')) ?>

    <?php $this->registerCss(<<<CSS
    .sx-checkbox label
    {
        width: 100%;
    }
CSS
    ) ?>
    <?= $form->field($model, 'roleNames')->checkboxList(
        \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getAvailableRoles(), 'name', 'description'), [
            'class' => 'sx-checkbox'
        ]
    )->label(false); ?>

    <? $fieldSet::end(); ?>
<?php endif; ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Password')); ?>

<?= $form->field($passwordChange, 'new_password')->passwordInput() ?>
<?= $form->field($passwordChange, 'new_password_confirm')->passwordInput() ?>

<? $fieldSet::end(); ?>


<?= $form->buttonsStandart($model); ?>
<?php echo $form->errorSummary([$model, $relatedModel]); ?>
<?php $form::end(); ?>
