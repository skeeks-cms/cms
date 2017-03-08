<?php

use yii\helpers\Html;
use \skeeks\cms\modules\admin\widgets\ActiveForm;
/* @var $this yii\web\View */
?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'new_password')->passwordInput() ?>
<?= $form->field($model, 'new_password_confirm')->passwordInput() ?>
<?= $form->buttonsCreateOrUpdate($model) ?>
<?php ActiveForm::end(); ?>

