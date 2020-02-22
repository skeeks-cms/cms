<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'SEO'), ['isOpen' => false]); ?>
<?= $form->field($model, 'seo_h1'); ?>
<?= $form->field($model, 'meta_title')->textarea(); ?>
<?= $form->field($model, 'meta_description')->textarea(); ?>
<?= $form->field($model, 'meta_keywords')->textarea(); ?>
<? $fieldSet::end(); ?>
