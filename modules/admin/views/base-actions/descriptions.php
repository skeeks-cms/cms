<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'description_full')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
    'options' => ['rows' => 20],
    'preset' => 'full'
]) ?>

<?= $form->field($model, 'description_short')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
    'options' => ['rows' => 6],
    'preset' => 'full'
]) ?>




<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

