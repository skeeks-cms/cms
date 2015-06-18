<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */

 if ($model->isNewRecord)
 {
     if ($tree_id = \Yii::$app->request->get("tree_id"))
     {
         $model->tree_id = $tree_id;
     }
 }
?>

<?php $form = ActiveForm::begin(); ?>

<? if ($model->isNewRecord) : ?>
    <? if ($content_id = \Yii::$app->request->get("content_id")) : ?>
        <? $model->content_id = $content_id; ?>
        <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>
    <? endif; ?>
<? endif; ?>

<?= $form->fieldSet('Основное'); ?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <div class="row">
        <div class="col-md-3">
            <?= $form->field($model, 'published_at')->widget(\kartik\datecontrol\DateControl::classname(), [
                //'displayFormat' => 'php:d-M-Y H:i:s',
                'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
            ]); ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'published_to')->widget(\kartik\datecontrol\DateControl::classname(), [
                //'displayFormat' => 'php:d-M-Y H:i:s',
                'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
            ]); ?>
        </div>
    </div>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 255])->hint("Этот параметр влияет на адрес страницы"); ?>

<?= $form->fieldSetEnd()?>





<?= $form->fieldSet('Анонс'); ?>
    <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_short_type',
        ]);
    ?>

<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Подробно'); ?>

    <?= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_full_type',
        ]);
    ?>

<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Разделы'); ?>
    <?= $form->field($model, 'treeIds')->label('Разделы сайта')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [
            "attributeMulti" => "treeIds"
        ])->hint('Укажите разделы сайт, где бы хотелось видеть эту публикацию');
    ?>

<?= $form->fieldSetEnd()?>



<?= $form->fieldSet('SEO'); ?>
    <?= $form->field($model, 'meta_title')->textarea(); ?>
    <?= $form->field($model, 'meta_description')->textarea(); ?>
    <?= $form->field($model, 'meta_keywords')->textarea(); ?>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Изображения'); ?>
     <?= $form->field($model, 'image')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'image',
        ]
    )->label('Главное изображение'); ?>


    <?/*= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); */?>
    <?= $form->field($model, 'images')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'images',
        ]
    )->label('Изображения');; ?>
<?= $form->fieldSetEnd()?>

<? if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet('Дополнительно'); ?>
        <?= $form->fieldSelect($model, 'content_id', \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\CmsContent::find()->active()->all(),
            'id',
            'name'
        )); ?>
        <?= $form->fieldInputInt($model, 'priority'); ?>

    <?= $form->fieldSetEnd() ?>
<? endif; ?>


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

<? endif; ?>