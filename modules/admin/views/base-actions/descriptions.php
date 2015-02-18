<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
/* @var $form yii\widgets\ActiveForm */
?>

<?php \yii\widgets\Pjax::begin([
    'id' => 'my-pjax',
]); ?>
<?php $form = ActiveForm::begin([
    'options' => ['data-pjax' => true]
]); ?>

<?= $form->field($model, 'description_full')->widget(
    //\skeeks\widget\ckeditor\CKEditor::className()
    \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className()
    , [
        'options' => ['rows' => 20],
        'preset' => 'full',
        'callbackImages' => $model,
        'clientOptions' =>
        [
            'extraPlugins'      => 'imageselect',
            'toolbarGroups'     =>
            [
                ['name' => 'imageselect']
            ]
        ]
]) ?>

<?= $form->field($model, 'description_short')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
    'options' => ['rows' => 6],
    'preset' => 'full',
    'clientOptions' =>
    [
        'extraPlugins'      => 'imageselect',
        'toolbarGroups'     =>
        [
            ['name' => 'imageselect']
        ]
    ]
]) ?>




<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php \yii\widgets\Pjax::end(); ?>

