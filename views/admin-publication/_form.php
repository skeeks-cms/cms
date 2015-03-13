<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Tree */
?>

<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet('Основное'); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'type')->label('Тип публикации')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
                'items' => \yii\helpers\ArrayHelper::map(
                     \Yii::$app->registeredModels->getDescriptor($model)->getTypes()->getComponents(),
                     "id",
                     "name"
                 ),
        ])->hint('От выбранного типа публикации может зависеть, то, как она будет отображаться.');
    ?>

<?= $form->fieldSetEnd()?>

<?= $form->fieldSet('Покзывать в разделах'); ?>
    <?= $form->field($model, 'tree_ids')->label('Разделы сайта')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [

        ])->hint('Укажите разделы сайт, где бы хотелось видеть эту публикацию');
    ?>

<?= $form->fieldSetEnd()?>

<?= $form->fieldSet('Изображения'); ?>
    <?= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); ?>
<?= $form->fieldSetEnd()?>


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


<?= $form->buttonsCreateOrUpdate($model); ?>


<?php ActiveForm::end(); ?>




