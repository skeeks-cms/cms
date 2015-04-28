<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\db\ActiveRecord */
?>

<?php $form = ActiveForm::begin([]); ?>
<?= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
        [
            'options'       => ['rows' => 20],
            'preset'        => 'full',
            'relatedModel'  => $model,
        ])
    ?>

    <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
        [
            'options'       => ['rows' => 6],
            'preset'        => 'full',
            'relatedModel'  => $model,
        ])
    ?>


<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>


