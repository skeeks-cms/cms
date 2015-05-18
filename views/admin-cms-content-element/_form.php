<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
?>

<?php $form = ActiveForm::begin(); ?>

<? if ($model->isNewRecord) : ?>
    <? if ($content_id = \Yii::$app->request->get("content_id")) : ?>
        <? $model->content_id = $content_id; ?>
        <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>
    <? endif; ?>
<? endif; ?>

<?= $form->fieldSet('Основное'); ?>
    <?= $form->field($model, 'image')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'image',
        ]
    )->label('Главное изображение'); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

<?= $form->fieldSetEnd()?>

<?= $form->fieldSet('Показывать в разделах'); ?>
    <?/*= $form->field($model, 'tree_ids')->label('Разделы сайта')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [

        ])->hint('Укажите разделы сайт, где бы хотелось видеть эту публикацию');
    */?>

<?= $form->fieldSetEnd()?>

<?= $form->fieldSet('Изображения'); ?>
    <?/*= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); */?>
    <?= $form->field($model, 'images')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'images',
        ]
    )->label('Изображения');; ?>
<?= $form->fieldSetEnd()?>


<?= $form->fieldSet('Описание'); ?>

    <?= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
        [
            'options'       => ['rows' => 20],
            'preset'        => 'full',
            'relatedModel'  => $model,
        ])
    ?>

    <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
        [
            'options'       => ['rows' => 6],
            'preset'        => 'full',
            'relatedModel'  => $model,
        ])
    ?>

<?= $form->fieldSetEnd() ?>


<?= $form->buttonsCreateOrUpdate($model); ?>


<?php ActiveForm::end(); ?>



<?/* if ($model->relatedProperties) : */?><!--
    <?/* foreach($model->relatedProperties as $relatedProperty) : */?>
        <?/*= $relatedProperty->name; */?>
    <?/* endforeach; */?>
<?/* endif; */?>

<?/* if ($model->relatedElementProperties) : */?>
    <?/* foreach($model->relatedElementProperties as $relatedElementProperty) : */?>
        <?/*= $relatedElementProperty->value; */?>
    <?/* endforeach; */?>
--><?/* endif; */?>
<? if (!$model->isNewRecord && $model->relatedProperties) : ?>
<div class="sx-box sx-mt-10">
    <div class="sx-box-head sx-p-10">
        <h2>Дополнительные свойства</h2>
    </div>
    <div class="sx-box-body sx-p-10">
        <?= $model->renderRelatedPropertiesForm(); ?>
    </div>
</div>
<? endif; ?>