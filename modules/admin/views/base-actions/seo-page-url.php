<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\Page */
/* @var $form yii\widgets\ActiveForm */
?>

Текущий адрес на сайте: <?= Html::a($model->createAbsoluteUrl(), $model->createAbsoluteUrl(), ["target" => "_blank"])?>

<?php $form = ActiveForm::begin(); ?>
<hr />

<?= $form->field($model, $model->seoPageNameAttribute)->textInput(['maxlength' => 64]) ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

