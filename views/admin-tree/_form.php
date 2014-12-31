<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

<?= $form->field($model, 'type')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \Yii::$app->registeredModels->getDescriptor($model)->getTypes()->getComponents(),
                 "id",
                 "name"
             ),
    ]);
?>

<?= $form->field($model, 'priority')->textInput([
    'maxlength' => 255
]) ?>
<?= $form->field($model, 'redirect')->textInput(['maxlength' => 500]) ?>

<?= $form->field($model, 'tree_ids')->widget(
    \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
    [

    ]);
?>

<?= $form->field($model, 'tree_menu_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\TreeMenu::find()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]);
?>

<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>