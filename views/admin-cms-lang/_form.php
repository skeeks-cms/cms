<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>


<?= $form->field($model, 'code')->textInput(); ?>
<?= $form->fieldRadioListBoolean($model, 'active'); ?>
<?= $form->fieldRadioListBoolean($model, 'def'); ?>
<?= $form->field($model, 'name')->textarea(); ?>
<?= $form->field($model, 'description')->textarea(); ?>
<?= $form->fieldInputInt($model, 'priority'); ?>

<?= $form->buttonsStandart($model) ?>

<?php ActiveForm::end(); ?>