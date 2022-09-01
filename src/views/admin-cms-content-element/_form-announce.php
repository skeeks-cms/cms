<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? if ($contentModel->isAllowEdit("description_short")) : ?>

    <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Короткое описание'), ['isOpen' => false]); ?>
    <? /*= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => false
    ]
); */ ?>
    <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_short_type',
        ])->label(false);
    ?>
    <? $fieldSet::end(); ?>

<? endif; ?>
