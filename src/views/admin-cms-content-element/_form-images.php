<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Images/Files')); ?>

<?= $form->field($model, 'imageIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => true
    ]
); ?>

<?= $form->field($model, 'fileIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'multiple' => true
    ]
); ?>

<?= $form->fieldSetEnd() ?>
