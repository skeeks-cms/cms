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
    <?= $form->fieldRadioListBoolean($model, 'multiple'); ?>
    <?= $form->fieldSelect($model, 'content_id', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsContent::find()->active()->all(),
        'id',
        'name'
    )); ?>
    <?= $form->buttonsStandart($model); ?>
<? ActiveForm::end(); ?>