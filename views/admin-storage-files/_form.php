<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\StorageFile */

?>

<? $this->registerCss(<<<CSS
    .sx-image-controll .sx-image img
    {
        max-height: 200px;
        border: 2px solid silver;
    }
CSS
); ?>

<div class="sx-image-controll">
<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet('Основное'); ?>
    <? if ($model->isImage()) : ?>
        <div class="sx-image">
            <img src="<?= $model->src; ?>" />
        </div>
    <? endif; ?>
    <div class="">

    </div>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255])->hint('Это название обычно нужно для сео, чтобы файл был найден в поисковых системах') ?>
    <?= $form->field($model, 'name_to_save')->textInput(['maxlength' => 255])->hint('Как будет называться файл, когда его кто то будет скачивать.') ?>

<?= $form->fieldSetEnd(); ?>




<?= $form->fieldSet('Описание'); ?>

    <?= $form->field($model, 'description_full')->widget(
        //\skeeks\widget\ckeditor\CKEditor::className()
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className()
        , [
            'options' => ['rows' => 20],
            'preset' => 'full',
            'callbackImages' => $model,
            'clientOptions' =>
            [
                'extraPlugins'      => 'imageselect',
                'toolbarGroups'     =>
                [
                    ['name' => 'imageselect']
                ]
            ]
    ]) ?>

    <?= $form->field($model, 'description_short')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
        'options' => ['rows' => 6],
        'preset' => 'full',
        'clientOptions' =>
        [
            'extraPlugins'      => 'imageselect',
            'toolbarGroups'     =>
            [
                ['name' => 'imageselect']
            ]
        ]
    ]) ?>

<?= $form->fieldSetEnd() ?>



<?= $form->fieldSet('Дополнительная информация'); ?>

    <?= $form->field($model, 'original_name')->textInput([
        'maxlength' => 255,
        'disabled' => 'disabled'
    ])->hint('Так назывался файл в момент загрузки на сайт') ?>


    <div class="form-group field-storagefile-mime_type">
        <label>Размер файла</label>
        <?= Html::textInput("file-size", \Yii::$app->formatter->asShortSize($model->size), [
            'disabled' => 'disabled',
            'class' => 'form-control',
        ])?>
    </div>
    <?/*= $form->field($model, 'size')->textInput([
        'maxlength' => 255,
        'disabled' => 'disabled',
        'value' => \Yii::$app->formatter->asShortSize($model->size)
    ]); */?>

    <?= $form->field($model, 'mime_type')->textInput([
        'maxlength' => 255,
        'disabled' => 'disabled'
    ])->hint('Internet Media Types — типы данных, которые могут быть переданы посредством сети интернет с применением стандарта MIME.'); ?>

    <?= $form->field($model, 'extension')->textInput([
        'maxlength' => 255,
        'disabled' => 'disabled'
    ]); ?>

    <? if ($model->isImage()) : ?>
        <? if (!$model->image_height || !$model->image_width) : ?>
            <? $model->updateFileInfo(); ?>
        <? endif; ?>
        <div class="col-md-12">
            <div class="col-md-2">
            <?= $form->field($model, 'image_width')->textInput([
                'maxlength' => 255,
                'disabled' => 'disabled'
            ]); ?>
            </div>

            <div class="col-md-2">
            <?= $form->field($model, 'image_height')->textInput([
                'maxlength' => 255,
                'disabled' => 'disabled'
            ]); ?>
            </div>
        </div>
    <? endif; ?>



<?= $form->fieldSetEnd(); ?>


<? if ($model->isImage()) : ?>
    <?= $form->fieldSet('Уменьшенные копии изображения'); ?>
        <p>Это изображение в разных местах сайта, показывается в разных размерах.</p>

    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Редактор изображения'); ?>

    <?= $form->fieldSetEnd(); ?>
<? endif; ?>

<?/*= $form->field($model, 'description')->textarea() */?><!--
--><?/*= $form->field($model, 'multiValue')->textarea() */?>
<!--
--><?/*= $form->field($model, 'value')->widget(
    \skeeks\cms\widgets\formInputs\multiLangAndSiteTextarea\multiLangAndSiteTextarea::className(),
    [
        'lang' => \skeeks\cms\App::moduleAdmin()->getCurrentLang(),
        'site' => \skeeks\cms\App::moduleAdmin()->getCurrentSite(),
    ]
);
*/?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
</div>