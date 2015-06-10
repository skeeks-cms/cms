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
    <?= $form->fieldSelect($model, 'fieldElement', \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList::$fieldElements); ?>
    <?= $form->buttonsStandart($model); ?>
<? ActiveForm::end(); ?>