<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */

?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'code')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'description')->textarea() ?>
<?= $form->field($model, 'multiValue')->textarea() ?>
<!--
--><?/*= $form->field($model, 'value')->widget(
    \skeeks\cms\widgets\formInputs\multiLangAndSiteTextarea\multiLangAndSiteTextarea::className(),
    [
        'lang' => \skeeks\cms\App::moduleAdmin()->getCurrentLang(),
        'site' => \skeeks\cms\App::moduleAdmin()->getCurrentSite(),
    ]
);
*/?>

<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>