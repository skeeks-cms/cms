<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
use skeeks\modules\cms\form\widgets\ActiveForm;
?>
<? $form = ActiveForm::begin(); ?>
    <?= $form->fieldSelect($model, 'fieldElement', \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeText::$fieldElements); ?>
    <?= $form->fieldInputInt($model, 'textareaRows'); ?>
<? ActiveForm::end(); ?>