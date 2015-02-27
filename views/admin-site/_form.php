<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'host_name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'name')->textarea() ?>
<?= $form->field($model, 'description')->textarea() ?>
<?= $form->buttonsCreateOrUpdate($model) ?>

<?php ActiveForm::end(); ?>