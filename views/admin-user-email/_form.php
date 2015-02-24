<?php
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
?>
<?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'value')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'user_id')->hiddenInput(['value' => \Yii::$app->request->get('user_id')])->label(false) ?>
    <?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
