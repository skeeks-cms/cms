<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */

?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'name_to_save')->textInput(['maxlength' => 255]) ?>
<?/*= $form->field($model, 'description')->textarea() */?><!--
--><?/*= $form->field($model, 'multiValue')->textarea() */?>
<!--
--><?/*= $form->field($model, 'value')->widget(
    \skeeks\cms\widgets\formInputs\multiLangAndSiteTextarea\multiLangAndSiteTextarea::className(),
    [
        'lang' => \skeeks\cms\App::moduleAdmin()->getCurrentLang(),
        'site' => \skeeks\cms\App::moduleAdmin()->getCurrentSite(),
    ]
);
*/?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>