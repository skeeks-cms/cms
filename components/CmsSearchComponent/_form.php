<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Основное'); ?>
    <?= $form->field($model, 'searchQueryParamName')->textInput()
            ->hint('Название параметра для адресной строки'); ?>


    <?= $form->fieldInputInt($model, 'phraseLiveTime')
            ->hint('Если указано 0 то поисковые запросы не будут удалятся никогда'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Поиск элементов'); ?>
    <?= $form->fieldSelectMulti($model, 'searchElementContentIds', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsContent::find()->active()->all(), 'id', 'name'
    ) ); ?>
    <?= $form->fieldSelectMulti($model, 'searchElementFields', (new \skeeks\cms\models\CmsContentElement())->attributeLabels() ); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledElementProperties')->hint('Включая эту опцию, поиск начнет учитывать дополнительные поля элементов.'); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledElementPropertiesSearchable')->hint('Каждое дополнительное свойство имеет свои настройки. Эта опция включит поиск не по всем дополнительным свойствам, а только с включеной опцией "Значения свойства участвуют в поиске"'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Поиск разделов'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


