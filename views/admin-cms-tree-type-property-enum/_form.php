<?php
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
?>
<?php $form = ActiveForm::begin(); ?>

    <? if ($form_id = \Yii::$app->request->get('property_id')) : ?>
        <?= $form->field($model, 'property_id')->hiddenInput(['value' => $form_id])->label(false); ?>
    <? else: ?>
        <?= $form->field($model, 'property_id')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
                'items' => \yii\helpers\ArrayHelper::map(
                     \skeeks\cms\models\CmsTreeTypeProperty::find()->all(),
                     "id",
                     "name"
                ),
        ]);
    ?>
    <? endif; ?>

    <?= $form->field($model, 'value')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 32]) ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>
