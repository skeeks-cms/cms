
<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
//TODO: Заменить элемент выбора пользователя
?>


<?php $form = ActiveForm::begin(); ?>


<? if (\skeeks\cms\helpers\ComponentHelper::hasBehavior($model, \yii\behaviors\BlameableBehavior::className())) : ?>
    <?/*= $form->field($model, 'created_by')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                \Yii::$app->cms->findUser()->all(),
                'id',
                'displayName'
            ),
        ]);
    */?>
    <?= $form->field($model, 'created_by')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\SelectModelDialogUserInput::className()
    );
    ?>

<? endif;?>


<? if (\skeeks\cms\helpers\ComponentHelper::hasBehavior($model, \yii\behaviors\TimestampBehavior::className())) : ?>
    <?= $form->field($model, 'created_at')->widget(
        \kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<? if ($model->hasAttribute('updated_at')) : ?>
    <?= $form->field($model, 'updated_at')->widget(\kartik\datecontrol\DateControl::classname(), [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<? if (\skeeks\cms\helpers\ComponentHelper::hasBehavior($model, \skeeks\cms\models\behaviors\TimestampPublishedBehavior::className())) : ?>
    <?= $form->field($model, 'published_at')->widget(\kartik\datecontrol\DateControl::classname(), [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<? if ($model->hasAttribute('published_to')) : ?>
    <?= $form->field($model, 'published_to')->widget(\kartik\datecontrol\DateControl::classname(), [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>

