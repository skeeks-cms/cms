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
<?= $form->fieldSet(\Yii::t('app', 'Showing')); ?>
    <?= $form->field($model, 'viewFile')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app', 'Data source')); ?>
    <?= $form->fieldSelect($model, 'content_id', \skeeks\cms\models\CmsContent::getDataForSelect()); ?>

    <?/*= $form->fieldSelectMulti($model, 'searchModelAttributes', [
        'image' => \Yii::t('app', 'Filter by photo'),
        'hasQuantity' => \Yii::t('app', 'Filter by availability')
    ]); */?>

    <?/*= $form->field($model, 'searchModelAttributes')->dropDownList([
        'image' => \Yii::t('app', 'Filter by photo'),
        'hasQuantity' => \Yii::t('app', 'Filter by availability')
    ], [
'multiple' => true,
'size' => 4
]); */?>

    <? if ($model->cmsContent) : ?>
        <?= $form->fieldSelectMulti($model, 'realatedProperties', \yii\helpers\ArrayHelper::map($model->cmsContent->cmsContentProperties, 'code', 'name')); ?>
    <? else: ?>
        Дополнительные свойства появятся после сохранения настроек
    <? endif; ?>



<?= $form->fieldSetEnd(); ?>

