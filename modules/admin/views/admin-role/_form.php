<?php
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\AuthItem $model
 */
?>


    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 64]) ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 2])->label(\Yii::t('app','Description')) ?>

    <?/*=
    $form->field($model, 'ruleName')->widget('yii\jui\AutoComplete', [
        'options' => [
            'class' => 'form-control',
        ],
        'clientOptions' => [
            'source' => array_keys(Yii::$app->authManager->getRules()),
        ]
    ])
    */?>

    <?= $form->field($model, 'data')->textarea(['rows' => 6, 'readonly' => 'readonly'])->label(\Yii::t('app','Data')) ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>

    <?php ActiveForm::end(); ?>
