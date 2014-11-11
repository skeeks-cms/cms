<?php

use yii\helpers\Html;
use \skeeks\cms\modules\admin\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'new_password')->textInput() ?>
<?= $form->field($model, 'new_password_confirm')->textInput() ?>
<?= $form->buttonsCreateOrUpdate($model) ?>
<?php ActiveForm::end(); ?>

