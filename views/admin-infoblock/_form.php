<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */

?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'code')->textInput(['maxlength' => 255])->label('Уникальный код блока') ?>
<?= $form->field($model, 'description')->textarea()->label('Описание') ?>

<?= $form->field($model, 'widget')->label('Виджет')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \Yii::$app->registeredWidgets->getComponents(),
                 "id",
                 "name"
             ),
    ])->hint('В зависимости от выбранного виджета, будут меняться доступные настройки, в разделе настроек инфоблока');
?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

<?= $this->render('_about'); ?>