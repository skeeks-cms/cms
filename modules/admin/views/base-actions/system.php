
<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use \skeeks\sx\validate\Validate;
use \skeeks\cms\validators\HasBehavior;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
?>


<?php $form = ActiveForm::begin(); ?>


<? if (Validate::validate( new HasBehavior(\yii\behaviors\BlameableBehavior::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'created_by')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                \Yii::$app->cms->findUser()->all(),
                'id',
                'username'
            ),
        ]);
    ?>

<? endif;?>


<? if (Validate::validate( new HasBehavior(\yii\behaviors\TimestampBehavior::className()), $model)->isValid()) : ?>
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

<? if (Validate::validate( new HasBehavior(\skeeks\cms\models\behaviors\TimestampPublishedBehavior::className()), $model)->isValid()) : ?>
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

