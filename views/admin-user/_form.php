<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model common\models\Game */
?>


<?php $form = ActiveFormUseTab::begin(); ?>

<?= $form->tabRun('Общая ниформация')?>
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
<?= $form->tabEnd(); ?>

<?= $form->tabRun('Контакты')?>
    <?= $form->field($model, 'email')->textInput(); ?>
    <?= $form->field($model, 'phone')->textInput(); ?>
<?= $form->tabEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveFormUseTab::end(); ?>

