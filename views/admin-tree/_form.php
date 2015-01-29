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
    ])->label('Тип раздела')->hint('От выбранного типа раздела может зависеть, то, как она будет отображаться.');
?>



<?= $form->field($model, 'tree_ids')->widget(
    \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
    [

    ])->label('Дополнительные разделы сайта')->hint('Дополнительные разделы сайта, где бы хотелось видеть этот раздел.');
?>

<?= $form->field($model, 'tree_menu_ids')->label('Меню')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\TreeMenu::find()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ])->hint('Вы можете выбрать один или несколько меню, в которых будет показываться этот раздел');
?>

<?= $form->field($model, 'priority')->label("Приоритет")->hint("Вы можете оставить это поле пустым, оно будет влиять на порядок в некоторых случаях")->textInput([
    'maxlength' => 255
]) ?>
<?= $form->field($model, 'redirect')->textInput(['maxlength' => 500])->label('Редиррект') ?>


<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>