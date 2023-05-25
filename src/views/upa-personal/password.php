<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2017
 */
/* @var $this \yii\web\View */
/* @var \skeeks\cms\models\forms\PasswordChangeForm $model */
?>
<h1>Смена пароля</h1>
<div class="row">
<div class="col-12" style="max-width: 50rem;">
<?php $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin(); ?>
    <?= $form->field($model, 'new_password')->passwordInput() ?>
    <?= $form->field($model, 'new_password_confirm')->passwordInput() ?>
    <?= $form->buttonsStandart($model) ?>
<?php $form::end(); ?>
</div>
</div>
