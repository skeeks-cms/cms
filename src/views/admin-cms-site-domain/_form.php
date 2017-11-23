<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', "Main")); ?>

<?php if ($code = \Yii::$app->request->get('cms_site_id')) : ?>
    <?= $form->field($model, 'cms_site_id')->hiddenInput(['value' => $code])->label(false); ?>
<?php else {
    : ?>
    <?= $form->field($model, 'cms_site_id')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
        'items' => \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\CmsSite::find()->all(),
            "id",
            "name"
        ),
    ]);
}
    ?>
<?php endif; ?>

<?= $form->field($model, 'domain')->textInput(); ?>

<?= $form->fieldSetEnd(); ?>
<?= $form->buttonsStandart($model) ?>

<?php ActiveForm::end(); ?>