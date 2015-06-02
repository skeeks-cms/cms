<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
?>
<?php $form = ActiveForm::begin(); ?>

    <?= $form->fieldSet('Постраничная навигация'); ?>
        <?= $form->fieldRadioListBoolean($model, 'enabledPjaxPagination', \Yii::$app->cms->booleanFormat()); ?>
        <?= $form->fieldInputInt($model, 'pageSize'); ?>
        <?= $form->field($model, 'pageParamName')->textInput(); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Сортировка'); ?>
        <?= $form->fieldSelect($model, 'orderBy', (new \skeeks\cms\models\CmsContentElement())->attributeLabels()); ?>
        <?= $form->fieldSelect($model, 'order', [
            SORT_ASC    => "ASC (от меньшего к большему)",
            SORT_DESC   => "DESC (от большего к меньшему)",
        ]); ?>
    <?= $form->fieldSetEnd(); ?>

    <? $columns = \skeeks\cms\helpers\UrlHelper::constructCurrent()->getSystem('columns'); ?>
    <? if ($columns) : ?>
        <?= $form->fieldSet('Поля таблицы'); ?>
            <?= $form->field($model, 'visibleColumns')->listBox($columns, [
                'size' => "15",
                'multiple' => 'multiple'
            ]); ?>
        <?= $form->fieldSetEnd(); ?>
    <? endif; ?>



<?= $form->buttonsStandart($model) ?>
<?php ActiveForm::end(); ?>