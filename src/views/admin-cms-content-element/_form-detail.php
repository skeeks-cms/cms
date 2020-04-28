<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? if ($contentModel->isAllowEdit("description_full") || $contentModel->isAllowEdit("image_full_id")) : ?>

    <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'In detal'), ['isOpen' => false]); ?>

    <? if ($contentModel->isAllowEdit("image_full_id")) : ?>
        <?= $form->field($model, 'image_full_id')->widget(
            \skeeks\cms\widgets\AjaxFileUploadWidget::class,
            [
                'accept'   => 'image/*',
                'multiple' => false,
            ]
        ); ?>
    <? endif; ?>

    <? if ($contentModel->isAllowEdit("description_full")) : ?>
        <?= $form->field($model, 'description_full')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'modelAttributeSaveType' => 'description_full_type',
            ])->label(false);
        ?>
    <? endif; ?>
    <? $fieldSet::end(); ?>
<? endif; ?>
