<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>

<? if ($contentModel->isAllowEdit("image_id") || $contentModel->isAllowEdit("imageIds")) : ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Images')); ?>

<? if ($contentModel->isAllowEdit("image_id")) : ?>
    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\AjaxFileUploadWidget::class,
        [
            'accept'   => 'image/*',
            'multiple' => false,
        ]
    ); ?>
<? endif; ?>

<? if ($contentModel->isAllowEdit("imageIds")) : ?>
<?= $form->field($model, 'imageIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept'   => 'image/*',
        'multiple' => true,
    ]
); ?>
<? endif; ?>

<? $fieldSet::end(); ?>
<? endif; ?>
