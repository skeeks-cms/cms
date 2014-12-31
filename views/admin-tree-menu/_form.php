<?php

use yii\helpers\Html;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'description')->textarea() ?>
<?= $form->buttonsCreateOrUpdate($model); ?>

<?php \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>