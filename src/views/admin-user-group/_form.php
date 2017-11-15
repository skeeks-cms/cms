<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'groupname')->textInput(['maxlength' => 12]) ?>
<?= $form->field($model, 'description')->textarea() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('skeeks/cms', 'Create') : Yii::t('skeeks/cms', 'Update'),
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>