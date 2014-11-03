<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<? if ($allowFields) : ?>
    <? foreach ($allowFields as $field) : ?>
        <?= $form->field($model, $field)->widget(skeeks\cms\widgets\formInputs\storageFiles\Widget::className()) ?>
    <? endforeach; ?>
<? endif; ?>

<?php ActiveForm::end(); ?>

