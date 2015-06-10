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
    <?= $form->field($model, 'type')->radioList(\skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeDate::$types); ?>
    <?= $form->buttonsStandart($model); ?>
<? ActiveForm::end(); ?>