<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Images')); ?>

<?= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept'   => 'image/*',
        'multiple' => false,
    ]
); ?>

<?= $form->field($model, 'imageIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept'   => 'image/*',
        'multiple' => true,
    ]
); ?>

<? $fieldSet::end(); ?>
