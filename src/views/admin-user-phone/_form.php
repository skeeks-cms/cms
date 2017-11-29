<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
?>
<?php $form = ActiveForm::begin(); ?>

<?
\skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
$id = \yii\helpers\Html::getInputId($model, 'value');
$this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");
JS
);
?>

<?= $form->field($model, 'value')->textInput([
    'placeholder' => '+7 903 722-28-73'
])->hint('Формат ввода телефона: +7 903 722-28-73'); ?>

<?php if (\Yii::$app->request->get('user_id')) : ?>
    <?= $form->field($model, 'user_id')->hiddenInput(['value' => \Yii::$app->request->get('user_id')])->label(false) ?>
<?php else
    : ?>
    <?= $form->fieldSelect($model, 'user_id', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\User::find()->active()->all(),
        'id',
        'displayName'
    ), [
        'allowDeselect' => true
    ]) ?>
<?php endif;
?>


<?= $form->fieldRadioListBoolean($model, 'approved'); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
