<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'meta_title')->textarea(['maxlength' => 255]) ?>
<?= $form->field($model, 'meta_keywords')->textarea(['maxlength' => 1000]); ?>
<?= $form->field($model, 'meta_description')->textarea(['maxlength' => 1000]); ?>


<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

