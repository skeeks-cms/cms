<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */

?>


<?php $form = ActiveForm::begin(); ?>

<? if ($model->hasAttribute('name')) : ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<? endif; ?>

<? if ($model->hasAttribute('code')) : ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 255]) ?>
<? endif; ?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>