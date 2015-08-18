<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
?>
<? $form = ActiveForm::begin(); ?>
    <?= $form->fieldRadioListBoolean($model, 'showDefaultPalette'); ?>
    <?= $form->fieldRadioListBoolean($model, 'useNative'); ?>
    <?= $form->fieldRadioListBoolean($model, 'showInput')->hint('Это инпут в открывающейся политре'); ?>
    <?= $form->fieldRadioListBoolean($model, 'showAlpha'); ?>
    <?= $form->fieldRadioListBoolean($model, 'showPalette'); ?>
    <?= $form->field($model, 'saveValueAs')->radioList(\skeeks\cms\widgets\ColorInput::$possibleSaveAs); ?>
    <?= $form->buttonsStandart($model); ?>
<? ActiveForm::end(); ?>