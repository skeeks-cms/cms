<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsLang */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\formInputs\StorageImage::className()
); ?>
<?= $form->field($model, 'code')->textInput(); ?>
<?= $form->fieldRadioListBoolean($model, 'active')->hint(\Yii::t('skeeks/cms','On the site must be included at least one language')); ?>
<?= $form->field($model, 'name')->textarea(); ?>
<?= $form->field($model, 'description')->textarea(); ?>
<?= $form->fieldInputInt($model, 'priority'); ?>

<?= $form->buttonsStandart($model) ?>

<?php ActiveForm::end(); ?>