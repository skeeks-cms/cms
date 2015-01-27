<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormStyled as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model common\models\Game */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet('Общая ниформация')?>
    <?= $form->field($model, 'group_id')->label('Группа пользователя')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
                'items' => \yii\helpers\ArrayHelper::map(
                     \skeeks\cms\models\UserGroup::find()->asArray()->all(),
                     "id",
                     "groupname"
                 ),
        ]);
    ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => 12]) ?>
    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'city')->textInput(); ?>
    <?= $form->field($model, 'address')->textInput(); ?>
    <?= $form->field($model, 'info')->textarea(); ?>
    <?= $form->field($model, 'status_of_life')->textarea(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Контакты')?>
    <?= $form->field($model, 'email')->textInput(); ?>
    <?= $form->field($model, 'phone')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>

