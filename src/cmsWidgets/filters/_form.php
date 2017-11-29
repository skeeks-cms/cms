<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
/* @var $contentType \skeeks\cms\models\CmsContentType */
/* @var $model \skeeks\cms\shop\cmsWidgets\filters\ShopProductFiltersWidget */

?>
<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Showing')); ?>
<?= $form->field($model, 'viewFile')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Data source')); ?>
<?= $form->fieldSelect($model, 'content_id', \skeeks\cms\models\CmsContent::getDataForSelect()); ?>

<?php /*= $form->fieldSelectMulti($model, 'searchModelAttributes', [
        'image' => \Yii::t('skeeks/cms', 'Filter by photo'),
        'hasQuantity' => \Yii::t('skeeks/cms', 'Filter by availability')
    ]); */ ?>

<?php /*= $form->field($model, 'searchModelAttributes')->dropDownList([
        'image' => \Yii::t('skeeks/cms', 'Filter by photo'),
        'hasQuantity' => \Yii::t('skeeks/cms', 'Filter by availability')
    ], [
'multiple' => true,
'size' => 4
]); */ ?>

<?php if ($model->cmsContent) : ?>
    <?= $form->fieldSelectMulti($model, 'realatedProperties',
        \yii\helpers\ArrayHelper::map($model->cmsContent->cmsContentProperties, 'code', 'name')); ?>
<?php else : ?>
    Дополнительные свойства появятся после сохранения настроек
<?php endif; ?>



<?= $form->fieldSetEnd(); ?>

